<?php
/**
 * Core.php
 *
 * Provides core plugin-related utility functions
 *
 * @copyright Ingenesis Limited, May 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Core
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppCore extends ShoppCoreFormatting {

	/**
	 * Detects if Shopp is unsupported in the current hosting environment
	 *
	 * @since 1.1
	 * @version 1.5
	 *
	 * @return boolean True if requirements are missing, false if no errors were detected
	 **/
	public static function unsupported() {
		$declared_support = defined('SHOPP_UNSUPPORTED');
		if ( $declared_support )
			return SHOPP_UNSUPPORTED;

		$SupportedEnvironment = new ShoppPluginSupported();
		$supported = $SupportedEnvironment->supported();
		if ( ! $declared_support )
			define('SHOPP_UNSUPPORTED', ! $supported);

		if ( $supported )
			return SHOPP_UNSUPPORTED;

		if ( $SupportedEnvironment->activating() ) {
			$SupportedEnvironment->messaging();
			return SHOPP_UNSUPPORTED;
		}

		$SupportedEnvironment->log();
		$SupportedEnvironment->force_deactivate();

		return SHOPP_UNSUPPORTED;
	}

	/**
	 * Detect if the Shopp installation needs maintenance
	 *
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public static function maintenance () {
		return ( self::upgradedb() || shopp_setting_enabled('maintenance') );
	}

	/**
	 * Detect if a database schema upgrade is required
	 *
	 * @since 1.3
	 *
	 * @return boolean
	 **/
	public static function upgradedb () {
		return ( ! ShoppSettings()->available() || ShoppSettings()->dbversion() != ShoppVersion::db() );
	}

	/**
	 * Provides access to WP_Filesystem
	 *
	 * @since 1.5
	 * @return WP_Filesystem Setup credentials if needed and provide access to the WP_Filesystem global
	 **/
	public static function filesystem( $url, $fields ) {
		global $wp_filesystem;

		if ( false === ( $credentials = request_filesystem_credentials($url, '', false, false, $fields) ) )
			return $wp_filesystem;

		if ( ! WP_Filesystem($credentials) ) // credentials were no good, ask for them again
			request_filesystem_credentials($url, '', true, false, $fields);

		return $wp_filesystem;
	}

	/**
	 * Returns readable php.ini data size settings
	 *
	 * @since 1.2
	 *
	 * @param string $name The name of the setting to read
	 * @return string The readable config size
	 **/
	public static function ini_size ($name) {
		$setting = ini_get($name);
		if (preg_match('/\d+\w+/',$setting) !== false) return $setting;
		else Shopp::readableFileSize($setting);
	}

	/**
	 * Detects image data in a binary string
	 *
	 * @since 1.5
	 *
	 * @return string|bool Image mime type if detected, false otherwise
	 **/
	public static function is_image ( $string ) {

		$types = array('image/jpeg' => "\xFF\xD8\xFF", 'image/gif' => 'GIF', 'image/png' => "\x89\x50\x4e\x47\x0d\x0a", 'image/bmp' => 'BM', 'image/psd' => '8BPS', 'image/swf' => 'FWS');
		foreach ( $types as $mimetype => $header )
			if ( false !== strpos($string, $header) ) return $mimetype;

		return false;

	}

	/**
	 * Determines the effective tax rate (a single rate) for the store or an item based
	 *
	 * @since 1.0
	 * @version 1.3
	 *
	 * @param Object $Item (optional) The ShoppProduct, ShoppCartItem or ShoppPurchased object to find tax rates for
	 * @return float The determined tax rate
	 **/
	public static function taxrate ( $Item = null ) {

		$taxes = self::taxrates($Item);

		if ( empty($taxes) ) $taxrate = 0.0; // No rates given
		if ( count($taxes) == 1 ) {
			$TaxRate = current($taxes);
			$taxrate = (float)$TaxRate->rate; // Use the given rate
		} else $taxrate = (float)( ShoppTax::calculate($taxes, 100) ) / 100; // Calculate the "effective" rate (note: won't work with compound taxes)

		return apply_filters('shopp_taxrate', $taxrate);

	}

	/**
	 * Determines all applicable tax rates for the store or an item
	 *
	 * @since 1.0
	 * @version 1.3
	 *
	 * @param Object $Item (optional) The ShoppProduct, ShoppCartItem or ShoppPurchased object to find tax rates for
	 * @return float The determined tax rate
	 **/
	public static function taxrates ( $Item = null ) {

		$Tax = new ShoppTax();

		$Order = ShoppOrder(); // Setup taxable address
		$Tax->address($Order->Billing, $Order->Shipping, $Order->Cart->shipped());

		$taxes = array();
		if ( is_null($Item) ) $Tax->rates($taxes);
		else $Tax->rates($taxes, $Tax->item($Item));

		return apply_filters('shopp_taxrates', $taxes);

	}

}

/**
 * Handles sanitizing URLs for use in markup HREF attributes
 *
 * Wrapper for securing URLs generated with the WordPress
 * add_query_arg() function
 *
 * @since 1.0
 *
 * @param mixed $param1 Either newkey or an associative_array
 * @param mixed $param2 Either newvalue or oldquery or uri
 * @param mixed $param3 Optional. Old query or uri
 * @return string New URL query string.
 **/
if ( ! function_exists('href_add_query_arg')) {
	function href_add_query_arg () {
		$args = func_get_args();
		$url = call_user_func_array('add_query_arg',$args);
		list($uri,$query) = explode("?",$url);
		return $uri.'?'.htmlspecialchars($query);
	}
}

if ( ! function_exists('mkobject')) {
	/**
	 * Converts an associative array to a stdClass object
	 *
	 * Uses recursion to convert nested associative arrays to a
	 * nested stdClass object while maintaing numeric indexed arrays
	 * and converting associative arrays contained within the
	 * numeric arrays
	 *
	 *
	 * @param array $data The associative array to convert
	 * @return void
	 **/
	function mkobject (&$data) {
		$numeric = false;
		foreach ($data as $p => &$d) {
			if (is_array($d)) mkobject($d);
			if (is_int($p)) $numeric = true;
		}
		if (!$numeric) settype($data,'object');
	}
}

if ( ! function_exists('sanitize_path') ) {
	/**
	 * Normalizes path separators to always use forward-slashes
	 *
	 * PHP path functions on Windows-based systems will return paths with
	 * backslashes as the directory separator.  This function is used to
	 * ensure we are always working with forward-slash paths
	 *
	 * @since 1.0
	 *
	 * @param string $path The path to clean up
	 * @return string $path The forward-slash path
	 **/
	function sanitize_path( $path ) {
		return str_replace('\\', '/', $path);
	}
}

if ( ! function_exists('get_class_property') ) {
	/**
	 * Gets the property of an uninstantiated class
	 *
	 * Provides support for getting a property of an uninstantiated
	 * class by dynamic name.  As of PHP 5.3.0 this function is no
	 * longer necessary as you can simply reference as $Classname::$property
	 *
	 * @since PHP 5.3.0
	 *
	 * @param string $classname Name of the class
	 * @param string $property Name of the property
	 * @return mixed Value of the property
	 **/
	function get_class_property ($classname, $property) {
		if( ! class_exists($classname, false) ) return;
		if( ! property_exists($classname, $property) ) return;

		$vars = get_class_vars($classname);
		return $vars[ $property ];
	}
}


/** Deprecated global function aliases **/

/**
 * @deprecated Use Shopp::datecalc()
 **/
function datecalc ( $week = -1, $dayOfWeek = -1, $month = -1, $year = -1 ) {
	return Shopp::datecalc($week, $dayOfWeek, $month, $year);
}

/**
 * @deprecated Use Shopp::date_format_order()
 **/
function date_format_order ($fields=false) {
	return Shopp::date_format_order($fields);
}

/**
 * @deprecated Use Shopp::debug_caller()
 **/
function debug_caller () {
	return Shopp::debug_caller();
}

/**
 * @deprecated Use Shopp::duration()
 **/
function duration ($start,$end) {
	return Shopp::duration($start,$end);
}

/**
 * @deprecated Use Shopp::esc_attrs()
 **/
function esc_attrs ($value) {
	return Shopp::esc_attrs($value);
}

/**
 * @deprecated Use Shopp::file_mimetype()
 **/
function file_mimetype ($file,$name=false) {
	return Shopp::file_mimetype($file,$name);
}

/**
 * @deprecated Use Shopp::force_ssl()
 **/
function force_ssl ($url,$rewrite=false) {
	return Shopp::force_ssl($url,$rewrite);
}

/**
 * @deprecated Use Shopp::inputattrs()
 **/
function inputattrs ($options,$allowed=array()) {
	return Shopp::inputattrs($options,$allowed);
}

/**
 * @deprecated Use Shopp::is_robot()
 **/
function is_robot() {
	return Shopp::is_robot();
}

/**
 * @deprecated Use Shopp::
 **/
function is_shopp_userlevel () { return; }


/**
 * @deprecated Using WP function instead
 **/
function is_shopp_secure () {
	return is_ssl();
}

/**
 * @deprecated Use Shopp::linkencode()
 **/
function linkencode ($url) {
	return Shopp::linkencode($url);
}

/**
 * @deprecated Use Shopp::locate_shopp_template()
 **/
function locate_shopp_template ($template_names, $load = false, $require_once = false ) {
	return Shopp::locate_template($template_names, $load, $require_once);
}

/**
 * @deprecated Use Shopp::lzw_compress()
 **/
function lzw_compress ($s) {
	return Shopp::lzw_compress($s);
}

/**
 * @deprecated Use Shopp::mktimestamp()
 **/
function mktimestamp ($datetime) {
	return Shopp::mktimestamp($datetime);
}

/**
 * @deprecated Use Shopp::mkdatetime()
 **/
function mkdatetime ($timestamp) {
	return Shopp::mkdatetime($timestamp);
}

/**
 * @deprecated Use Shopp::mk24hour()
 **/
function mk24hour ($hour, $meridiem) {
	return Shopp::mk24hour($hour, $meridiem);
}

/**
 * @deprecated Use Shopp::menuoptions()
 **/
function menuoptions ($list,$selected=null,$values=false,$extend=false) {
	return Shopp::menuoptions($list,$selected,$values,$extend);
}

/**
 * @deprecated Use Shopp::money()
 **/
function money ($amount, $format = array()) {
	return Shopp::money($amount, $format);
}

/**
 * @deprecated Use Shopp::numeric_format()
 **/
function numeric_format ($number, $precision=2, $decimals='.', $separator=',', $grouping=array(3)) {
	return Shopp::numeric_format($number, $precision, $decimals, $separator, $grouping);
}

/**
 * @deprecated Use Shopp::parse_phone()
 **/
function parse_phone ($num) {
	return Shopp::parse_phone($num);
}

/**
 * @deprecated Use Shopp::phone()
 **/
function phone ($num) {
	return Shopp::phone($num);
}

/**
 * @deprecated Use Shopp::percentage()
 **/
function percentage ( $amount, $format = array() ) {
	return Shopp::percentage( $amount, $format);
}

/**
 * @deprecated Use Shopp::raw_request_url()
 **/
function raw_request_url () {
	return Shopp::raw_request_url();
}

/**
 * @deprecated Use Shopp::readableFileSize()
 **/
function readableFileSize ($bytes,$precision=1) {
	return Shopp::readableFileSize($bytes,$precision);
}

/**
 * @deprecated Use Shopp::roundprice()
 **/
function roundprice ($amount, $format = array()) {
	return Shopp::roundprice($amount, $format);
}

/**
 * @deprecated Use Shopp::rsa_encrypt()
 **/
function rsa_encrypt ($data, $pkey) {
	return Shopp::rsa_encrypt($data, $pkey);
}

/**
 * @deprecated Use Shopp::daytimes()
 **/

function shopp_daytimes () {
	return ShippingFramework::daytimes();
}

/**
 * @deprecated Use Shopp::email()
 **/
function shopp_email ($template,$data=array()) {
	return Shopp::email($template,$data);
}

/**
 * @deprecated Use Shopp::rss()
 **/
function shopp_rss ($data) {
	return Shopp::rss($data);
}

/**
 * @deprecated Use Shopp::pagename()
 **/
function shopp_pagename ($page) {
	return Shopp::pagename($page);
}

/**
 * @deprecated Use Shopp::parse_options()
 **/
function shopp_parse_options ($options) {
	return Shopp::parse_options($options);
}

/**
 * @deprecated Use Shopp::redirect()
 **/
function shopp_redirect ($uri, $exit=true, $status=302) {
	Shopp::redirect($uri, $exit, $status);
}

/**
 * @deprecated Use Shopp::safe_redirect()
 **/
function shopp_safe_redirect ($location, $status = 302) {
	Shopp::safe_redirect($location, $status);
}

/**
 * @deprecated Use Shopp::template_prefix()
 **/
function shopp_template_prefix ($name) {
	return Shopp::template_prefix($name);
}

/**
 * @deprecated Use Shopp::template_url()
 **/
function shopp_template_url ($name) {
	return Shopp::template_url($name);
}

/**
 * @deprecated Use Shopp::url()
 **/
function shoppurl ($request=false,$page='catalog',$secure=null) {
	return Shopp::url($request,$page,$secure);
}

/**
 * @deprecated Use Shopp::str_true()
 **/
function str_true ( $string, $istrue = array('yes', 'y', 'true','1','on','open') ) {
	return Shopp::str_true($string,$istrue);
}

/**
 * @deprecated Use Shopp::valid_input()
 **/
function valid_input ($type) {
	return Shopp::valid_input($type);
}
<?php
/**
 * error.php
 *
 * ShoppErrorThemeAPI provides shopp('error') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2014
 * @package Shopp\API\Theme\Error
 * @version 1.3
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * shopp('customer','...') tags
 *
 * Supports adding errors through the Theme API.
 *
 * @since 1.2
 * @version 1.3
 **/
class ShoppErrorThemeAPI implements ShoppAPI {

	/**
	 * @var array The registry of available `shopp('error')` tags
	 * @internal
	 **/
	static $register = array(
		'trxn' => 'trxn',
		'auth' => 'auth',
		'addon' => 'addon',
		'comm' => 'comm',
		'stock' => 'stock',
		'admin' => 'admin',
		'db' => 'db',
		'debug' => 'debug'
	);

	/**
	 * Provides the authoritative Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		return 'error';
	}

	/**
	 * Returns the proper global context object used in a shopp('collection') call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param ShoppErrors $Object The ShoppOrder object to set as the working context
	 * @param string      $context The context being worked on by the Theme API
	 * @return ShoppErrors The active object context
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'ShoppErrors') ) return $Object;

		if ( strtolower($object) != 'error' ) return $Object; // not mine
		return ShoppErrors();
	}

	/**
	 * Adds a transaction error message
	 *
	 * @example `shopp('error.trxn', 'Error message')`
	 *
	 * @api `shopp('error.trxn')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function trxn ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_TRXN_ERR);
	}

	/**
	 * Adds an authorization error message
	 *
	 * @example `shopp('error.auth', 'Error message')`
	 *
	 * @api `shopp('error.auth')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function auth ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_AUTH_ERR);
	}

	/**
	 * Adds an addon error message
	 *
	 * @example `shopp('error.addon', 'Error message')`
	 *
	 * @api `shopp('error.addon')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function addon ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_ADDON_ERR);
	 }

 	/**
 	 * Adds a communication error message
 	 *
	 * @example `shopp('error.comm', 'Error message')`
 	 *
 	 * @api `shopp('error.comm')`
 	 * @since 1.1
 	 *
 	 * @param string      $result  The output
 	 * @param array       $options The options
 	 * @param ShoppErrors $O       The working object
 	 * @return void
 	 **/
	public static function comm ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_COMM_ERR);
	}

	/**
	 * Adds an inventory error message
	 *
	 * @example `shopp('error.stock', 'Error message')`
	 *
	 * @api `shopp('error.stock')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function stock ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_STOCK_ERR);
	}

	/**
	 * Adds an admin error message
	 *
	 * @example `shopp('error.admin', 'Error message')`
	 *
	 * @api `shopp('error.admin')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function admin ( $result, $options, $O ) {
		if (empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_ADMIN_ERR);
	}

	/**
	 * Adds a DB error message
	 *
	 * @example `shopp('error.db', 'Error message')`
	 *
	 * @api `shopp('error.db')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function db ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_DB_ERR);
	}

	/**
	 * Adds a debug error message
	 *
	 * @example `shopp('error.debug', 'Error message')`
	 *
	 * @api `shopp('error.debug')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppErrors $O       The working object
	 * @return void
	 **/
	public static function debug ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_DEBUG_ERR);
	}

}
<?php
/**
* ShoppErrorThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppErrorThemeAPI
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides functionality for the shopp('error') tags
 *
 * Support for triggering errors through the Theme API.
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppErrorThemeAPI implements ShoppAPI {
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

	public static function _apicontext () { return 'error'; }

	/**
	 * _setobject - returns the global context object used in the shopp('error') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'ShoppErrors') ) return $Object;

		if ( strtolower($object) != 'error' ) return $Object; // not mine
		return ShoppErrors();
	}

	public static function trxn ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_TRXN_ERR);
	}

	public static function auth ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_AUTH_ERR);
	}

	public static function addon ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_ADDON_ERR);
	 }

	public static function comm ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_COMM_ERR);
	}

	public static function stock ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_STOCK_ERR);
	}

	public static function admin ( $result, $options, $O ) {
		if (empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_ADMIN_ERR);
	}

	public static function db ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_DB_ERR);
	}

	public static function debug ( $result, $options, $O ) {
		if ( empty($options) ) return false;
		new ShoppError(key($options), 'template_error', SHOPP_DEBUG_ERR);
	}
}
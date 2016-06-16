<?php
/**
 * Script API
 *
 * Plugin API functions for script management
 *
 * @copyright Ingenesis Limited, March, 2013
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Settings
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Register new JavaScript file for Shopp
 *
 * @api
 * @since 1.2
 *
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 *
 * @param string      $handle    Name of the script. Should be unique.
 * @param string      $src       Path to the script from the WordPress root directory. Example: '/js/myscript.js'.
 * @param array       $deps      Optional. An array of registered script handles this script depends on. Set to false if there
 *                               are no dependencies. Default empty array.
 * @param string|bool $ver       Optional. String specifying script version number, if it has one, which is concatenated
 *                               to end of path as a query string. If no version is specified or set to false, a version
 *                               number is automatically added equal to current installed WordPress version.
 *                               If set to null, no version is added. Default 'false'. Accepts 'false', 'null', or 'string'.
 * @param bool        $in_footer Optional. Whether to enqueue the script before </head> or before </body>.
 *                               Default 'false'. Accepts 'false' or 'true'.
 */
function shopp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
	global $ShoppScripts;
	if ( !is_a($ShoppScripts, 'ShoppScripts') )
		$ShoppScripts = new ShoppScripts();

	$ShoppScripts->add( $handle, $src, $deps, $ver );
	if ( $in_footer )
		$ShoppScripts->add_data( $handle, 'group', 1 );
}

/**
 * Localize a Shopp JavaScript asset.
 *
 * @api
 * @since 1.2
 *
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 *
 * @param string $handle      Script handle the data will be attached to.
 * @param string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
 *                            Example: '/[a-zA-Z0-9_]+/'.
 * @param array $l10n         The data itself. The data can be either a single or multi-dimensional array.
 * @return bool True if the script was successfully localized, false otherwise.
 **/
function shopp_localize_script( $handle, $object_name, $l10n ) {
	global $ShoppScripts;
	if ( !is_a($ShoppScripts, 'ShoppScripts') )
		return false;

	return $ShoppScripts->localize( $handle, $object_name, $l10n );
}

/**
 * Add custom ad-hoc JavaScript for a given JavaScript asset.
 *
 * @api
 * @since 1.2
 *
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 *
 * @param string $handle Script handle the data will be attached to.
 * @param array  $code   The Javascript code to add
 * @return bool True if the script was successfully localized, false otherwise.
 **/
function shopp_custom_script ( $handle, $code ) {
	global $ShoppScripts;
	if ( !is_a($ShoppScripts, 'ShoppScripts') )
		return false;

	$code = !empty($ShoppScripts->registered[$handle]->extra['code'])?$ShoppScripts->registered[$handle]->extra['code'].$code:$code;
	return $ShoppScripts->add_data( $handle, 'code', $code );
}

/**
 * Remove a registered script.
 *
 * @api
 * @since 1.2
 *
 * @see WP_Dependencies::remove()
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 *
 * @param string $handle Name of the script to be removed.
 **/
function shopp_deregister_script( $handle ) {
	global $ShoppScripts;
	if ( !is_a($ShoppScripts, 'ShoppScripts') )
		$ShoppScripts = new ShoppScripts();

	$ShoppScripts->remove( $handle );
}

/**
 * Enqueue a Shopp script.
 *
 * Registers the script without overwriting if $src is provided, and enqueues it.
 *
 * @api
 * @since 1.2
 *
 * @see WP_Dependencies::add(), WP_Dependencies::add_data(), WP_Dependencies::enqueue()
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 *
 * @param string      $handle    Name of the script.
 * @param string|bool $src       Path to the script from the root directory of WordPress. Example: '/js/myscript.js'.
 * @param array       $deps      An array of registered handles this script depends on. Default empty array.
 * @param string|bool $ver       Optional. String specifying the script version number, if it has one. This parameter
 *                               is used to ensure that the correct version is sent to the client regardless of caching,
 *                               and so should be included if a version number is available and makes sense for the script.
 * @param bool        $in_footer Optional. Whether to enqueue the script before </head> or before </body>.
 *                               Default 'false'. Accepts 'false' or 'true'.
 */
function shopp_enqueue_script( $handle, $src = false, $deps = array(), $ver = false, $in_footer = false ) {
	global $ShoppScripts;
	if ( !is_a($ShoppScripts, 'ShoppScripts') )
		$ShoppScripts = new ShoppScripts();

	if ( $src ) {
		$_handle = explode('?', $handle);
		$ShoppScripts->add( $_handle[0], $src, $deps, $ver );
		if ( $in_footer )
			$ShoppScripts->add_data( $_handle[0], 'group', 1 );
	}
	$ShoppScripts->enqueue( $handle );
}

/**
 * Check whether script has been added to WordPress Scripts.
 *
 * The values for list defaults to 'queue', which is the same as enqueue for
 * scripts.
 *
 * @api
 * @since 1.2
 *
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 *
 * @param string $handle Handle used to add script.
 * @param string $list Optional, defaults to 'queue'. Others values are 'registered', 'queue', 'done', 'to_do'
 * @return bool
 */
function shopp_script_is( $handle, $list = 'queue' ) {
	global $ShoppScripts;
	if ( !is_a($ShoppScripts, 'ShoppScripts') )
		$ShoppScripts = new ShoppScripts();

	$query = $ShoppScripts->query( $handle, $list );

	if ( is_object( $query ) )
		return true;

	return $query;
}

/**
 * Handle Shopp script dependencies in the WP script queue
 *
 * @api
 * @since 1.1
 *
 * @global ShoppScripts $ShoppScripts The ShoppScripts controller object for adding scripts to a page.
 * @global WP_Scripts $wp_scripts The WP_Scripts object for printing scripts.
 *
 * @return void
 **/
function shopp_dependencies () {
	global $ShoppScripts, $wp_scripts;
	if ( ! is_a($ShoppScripts, 'ShoppScripts') )
		$ShoppScripts = new ShoppScripts();

	foreach ( $wp_scripts->queue as $handle ) {
		$deps = $wp_scripts->registered[ $handle ]->deps;
		$shoppdeps = array_intersect($deps, array_keys($ShoppScripts->registered));
		foreach ( $shoppdeps as $key => $s_handle ) {
			shopp_enqueue_script($s_handle);
			array_splice($deps, $key, 1);
		}
		$wp_scripts->registered[ $handle ]->deps = $deps;
	}
}
<?php
/**
 * Style API
 *
 * Plugin API functions for stylesheet management
 *
 * @copyright Ingenesis Limited, May 2014
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @version 1.0
 * @since 1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Register a new stylesheet to ShoppStyles service
 *
 * @since 1.4
 *
 * @param string $handle Reference name for the stylesheet
 * @param string $src Path to the stylesheet
 * @param array $deps (optional) List of registered stylesheet handles that this stylesheet depends on
 * @param string $ver The version number of the stylesheet for proper cache handling
 * @param string $media (optional) The media type for the stylesheet
 * @return void
 **/
function shopp_register_style ( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	global $ShoppStyles;
	if ( ! is_a($ShoppStyles, 'ShoppStyles') )
		$ShoppStyles = new ShoppStyles();

	$ShoppStyles->add( $handle, $src, $deps, $ver, $media );
}

/**
 * Remove a registered stylesheet
 *
 * @since 1.4
 *
 * @param string $handle Reference name for the stylesheet
 * @return void
 **/
function shopp_deregister_style ( $handle ) {
	global $ShoppStyles;
	if ( ! is_a($ShoppStyles, 'ShoppStyles') )
		$ShoppStyles = new ShoppStyles();

	$ShoppStyles->remove( $handle );
}

/**
 * Add an inline style to a stylesheet
 *
 * @author Jonathan Davis
 * @since 1.4
 *
 * @param string $handle Name of the stylesheet to add extra styles to
 * @param string $code The CSS to add
 * @return bool True on success, false otherwise
 **/
function shopp_inline_style ( $handle, $code ) {
	global $ShoppStyles;
	if ( ! is_a($ShoppStyles, 'ShoppStyles') )
		return false;

	// @todo add warning for style tags
	// if ( false !== stripos( $data, '</style>' ) ) {
	// 	_doing_it_wrong( __FUNCTION__, 'Do not pass style tags to wp_add_inline_style().', '3.7' );
	// 	$data = trim( preg_replace( '#<style[^>]*>(.*)</style>#is', '$1', $data ) );
	// }

	return $ShoppStyles->add_inline_style( $handle, $code );
}


/**
 * Enqueues a style.
 *
 * Registers the script if src provided (does NOT overwrite) and enqueues.
*/

/**
 * Adds a stylesheet to the queue to be included
 *
 *
 * @since 1.4
 *
 * @param string $handle Reference name for the stylesheet
 * @param string $src (optional) Path to the stylesheet
 * @param array $deps (optional) List of registered stylesheet handles that this stylesheet depends on
 * @param string $ver The version number of the stylesheet for proper cache handling
 * @param string $media (optional) The media type for the stylesheet
 * @return void
 **/
function shopp_enqueue_style ( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
	global $ShoppStyles;
	if ( ! is_a($ShoppStyles, 'ShoppStyles') )
		$ShoppStyles = new ShoppStyles();

	if ( $src ) {
		list($name,) = explode('?', $handle);
		$ShoppStyles->add( $name, $src, $deps, $ver );
	}

	$ShoppStyles->enqueue( $handle );
}

/**
 * Check whether style has been added to ShoppStyles controller
 *
 * The values for list defaults to 'queue', which is the same as enqueue for
 * scripts.
 *
 * @param string $handle Handle used to add script.
 * @param string $list Optional, defaults to 'enqueued'. Others values are 'registered', 'queue', 'done', 'to_do'
 * @return bool
 */
function shopp_style_is ( $handle, $list = 'enqueued' ) {
	global $ShoppStyles;
	if ( ! is_a($ShoppStyles, 'ShoppStyles') )
		$ShoppStyles = new ShoppStyles();

	$query = $ShoppStyles->query($handle, $list);

	if ( is_object( $query ) ) return true;

	return $query;
}
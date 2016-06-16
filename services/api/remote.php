<?php
/**
 * Shopp Remote Developer API
 *
 * Provides developers access to create their own Remote API request handlers
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, August 29, 2012
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package Shopp
 * @since 1.3
 * @subpackage Remote
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Adds a Remote API request handler
 *
 * @author Jonathan Davis
 * @since 1.3
 *
 * @param string $request A request name
 * @param callable $callback The callback handler (function/method) to use for processing the request
 * @param array $capabilities The capabilities required by the request
 * @return boolean
 **/
function shopp_add_remoteapi ( $request, $callback, $capabilities = '' ) {
	if ( ! class_exists('ShoppRemoteAPIServer') ) return false;
	$capabilities = explode(',',$capabilities);
	return ShoppRemoteAPIServer::register( $request, $callback, $capabilities);
}
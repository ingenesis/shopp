<?php

/**
 * remote.php
 *
 * Provides secure remote access to Shopp data
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, August, 2012
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.1
 * @subpackage remoteapi
 **/

class ShoppRemoteAPIServer {

	static $services = array('rest' => 'ShoppRESTService','xmlrpc' => 'ShoppXMLRPCService');
	static $methods = array('get','head','option','post','put','patch','delete');
	static $capabilities = array();

	/**
	 * The remote server processor
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $service The name of the service to use (REST/XMLRPC)
	 * @param array $resource The requested resource (['command','id'])
	 * @param array $query (optional) Request arguments/options
	 * @param string $method (optional) Request method (get,head,option,post,put,patch,delete)
	 * @return void
	 **/
	static public function start ($service,$resource,$query = false,$method = 'GET') {
		// Load libraries
		define('SHOPP_REMOTE_APIS',SHOPP_PATH.'/api/remote');
		if (!class_exists('ShoppRemoteAPIServiceFramework')) require(SHOPP_PATH.'/core/flow/Remote.php');
		if (!function_exists('shopp_add_remoteapi')) require(SHOPP_PATH.'/api/remote.php');

		// Setup built-in authentication
		add_action('shopp_remoteapi_client_authentication',array(__CLASS__,'authenticate'));

		// Normalize key names
		$service = sanitize_key($service);
		$method = sanitize_key($method);

		// Hook up Remote API resource handlers
		new ShoppRemoteAPIModules();
		do_action('shopp_remoteapi_registration');

		// Hook up service provider
		$bootservice = apply_filters('shopp_remoteapi_start_service',array(__CLASS__,'service'));
		if ( ! is_callable($bootservice) ) ShoppRemoteAPIServiceFramework::notimplemented();
		call_user_func($bootservice);

		// Initialize command processing values
		$authed = false;		// Clients are not authorized by default
		$allow = true;			// Clients are allowed access unless prevented by capability restrictions
		$response = null;		// If nothing alters the response, a "not found" response is given

		// Authenticate the client
		if ( ! apply_filters('shopp_remoteapi_client_authentication',$authed,$resource,$query) )
			do_action('shopp_remoteapi_unauthorized');

		// Check if the client has the capabilities to access the request
		if ( ! apply_filters('shopp_remoteapi_client_access',$allow,$resource,$service) )
			do_action('shopp_remoteapi_forbidden');


		// Call the service handler for the request method
		if ( in_array( $method, self::$methods) )
			$response = apply_filters('shopp_remoteapi_service_'.join( '_', array($service,$method) ),$response,$resource,$query);

		// Call the generic service handler for all requests
		$response = apply_filters('shopp_remoteapi_service_'.strtolower($service),$response,$resource,$query);

		// No response at all, requested resource was not found
		if ( is_null($response) ) do_action('shopp_remoteapi_notfound');

		// Good response
		status_header('200');	// Tell the client 'Ok'
		echo $response;			// Encoded response
		self::done();			// Fin!
	}

	/**
	 * Registers a request handler callback with capability requirements
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $name The name of the request
	 * @param callable $callback The callback handler
	 * @param array $capabilities The list of required capabilities
	 * @return void
	 **/
	static public function register ( $name, $callback, array $capabilities = array() ) {
		$name = sanitize_key($name);
		add_filter("shopp_remoteapi_request_$name",$callback,10,3);
		if ( is_array($capabilities) && ! empty($capabilities) ) {
			self::$capabilities[ $name ] = $capabilities;
			add_filter('shopp_remoteapi_client_access',array(__CLASS__,'access'),10,3);
		}
	}

	/**
	 * Filter to check that the authenticated client has all of the required capabilities for the resource request
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param boolean $access The current access
	 * @param array $resource The resource request
	 * @return boolean True if the client has all of the required capabilities
	 **/
	static public function access ( $access, $resource = array() ) {
		$request = reset($resource);
		if ( ! array_key_exists($request,self::$capabilities)) return true; // Does not have capability/role requirements

		$required = self::$capabilities[ $request ];

		$capabilities = 0;
		foreach ($required as $capability)
			if ( current_user_can($capability) ) $capabilities++;

		return ($capabilities == count($required));
	}

	/**
	 * Authenticate the client using provided credentials
	 *
	 * Original code used under GPLv2 from the WordPress class-wp-atom-server.php,
	 * Written by matt, ryan, joostdevalk, otto42, westi
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if authentication was sucessful
	 **/
	function authenticate () {

		// Workarounds
		if ( isset($_SERVER['HTTP_AUTHORIZATION']) ) {
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
				explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		} elseif ( isset($_SERVER['REDIRECT_REMOTE_USER']) ) {
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
				explode(':', base64_decode(substr($_SERVER['REDIRECT_REMOTE_USER'], 6)));
		}

		// Basic Auth
		if ( isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) ) {

			$user = wp_authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
			if ( $user && !is_wp_error($user) ) {
				wp_set_current_user($user->ID);
				return true;
			}
		}

		return false;
	}

	/**
	 * Provides overridable die handlers
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $message (optional) The message to output
	 * @param string $title (optional) The title of the page
	 * @param array $args (optional) Custom arguments for the die handler
	 * @return void
	 **/
	static public function done ( $message = '', $title = '', $args = array() ) {
		$function = apply_filters( 'shopp_remoteapi_done_handler', array(__CLASS__,'justdie') );
		call_user_func( $function, $message, $title, $args );
	}

	/**
	 * Default die handler for the remote server
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $message (optional) The message to output before die()ing
	 * @return void
	 **/
	static public function justdie ( $message = '' ) {
		if ( is_scalar( $message ) )
			die( (string) $message );
		die(); // Aaaaaaaaaaggggghhh
	}

	/**
	 * Boot default service provider (REST)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	static public function service () {
		$services = apply_filters('shopp_remoteapi_default_services',self::$services);

		foreach ($services as $service => $ServiceClass) {
			if (class_exists($ServiceClass)) {
				new $ServiceClass(); break;
			}
		}
	}

}

/** Stubs **/
if (!function_exists('shopp_prereqs')) {
	function shopp_prereqs() {
		define('SHOPP_UNSUPPORTED',false);
	}
}

if ( ! defined('SHORTINIT')) define('SHORTINIT',true);
require 'Loader.php';

// Bootstrap WordPress environment
if ( ! defined('ABSPATH') && $loadfile = ShoppLoader::find_wpload() )
	require($loadfile);

/** Server **/
$method = $_SERVER['REQUEST_METHOD'];
$resource = false;
$query = $_SERVER['QUERY_STRING'];

if (false !== strpos($query,'&'))
	$query = explode('&',$query);

$resource = explode('/',array_shift($query));
$service = array_shift($resource);
$querystring = join('&',$query);
$query = array();
wp_parse_str($querystring,$query);

/** Here we go! **/
ShoppRemoteAPIServer::start($service,$resource,$query,$method);
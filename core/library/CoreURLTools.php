<?php
/**
 * CoreURLTools.php
 *
 * Provides core utility functions
 *
 * @copyright Ingenesis Limited, May 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Core
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppCoreURLTools extends ShoppCoreTools {

	/**
	 * Generates canonical storefront URLs that respects the WordPress permalink settings
	 *
	 * @since 1.1
	 * @version 1.2
	 *
	 * @param mixed $request Additional URI requests
	 * @param string $page The gateway page
	 * @param boolean $secure (optional) True for secure URLs, false to force unsecure URLs
	 * @return string The final URL
	 **/
	public static function url( $request = false, $page = 'catalog', $secure = null ) {
		$PageURL = new ShoppPageURL($page, $request, $secure);
		return $PageURL->url();
	}

	/**
	 * Redirects the browser to a specified URL
	 *
	 * A wrapper for the wp_redirect function
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $uri The URI to redirect to
	 * @param boolean $exit (optional) Exit immediately after the redirect (defaults to true, set to false to override)
	 * @return void
	 **/
	public static function redirect( $uri, $exit = true, $status = 302 ) {
		shopp_debug("Redirecting to: $uri");

		remove_action('shutdown', array(ShoppShopping(), 'save'));
		ShoppShopping()->save();

		wp_redirect($uri, $status);
		if ( $exit )
			exit();
	}

	/**
	 * Safely handles redirect requests to ensure they remain onsite
	 *
	 * Derived from WP 2.8 wp_safe_redirect
	 *
	 * @author Mark Jaquith, Ryan Boren
	 * @since 1.1
	 *
	 * @param string $location The URL to redirect to
	 * @param int $status (optional) The HTTP status to send to the browser
	 * @return void
	 **/
	public static function safe_redirect( $location, $status = 302 ) {

		// Need to look at the URL the way it will end up in wp_redirect()
		$location = wp_sanitize_redirect($location);

		// browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
		if ( substr($location, 0, 2) == '//' )
			$location = 'http:' . $location;

		// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
		$test = ( $cut = strpos($location, '?') ) ? substr( $location, 0, $cut ) : $location;

		$lp  = parse_url($test);
		$wpp = parse_url(get_option('home'));

		$allowed_hosts = (array) apply_filters('allowed_redirect_hosts', array($wpp['host']), isset($lp['host']) ? $lp['host'] : '');

		if ( isset($lp['host']) && ( !in_array($lp['host'], $allowed_hosts) && $lp['host'] != strtolower($wpp['host'])) )
			$location = Shopp::url(false,'account');

		self::redirect($location, true, $status);
	}

	/**
	 * Appends a string to the end of URL as a query string
	 *
	 * @since 1.1
	 *
	 * @param string $string The string to add
	 * @param string $url The url to append to
	 * @return string
	 **/
	public static function add_query_string( $string, $url ) {
		return $url . ( strpos($url, '?') === false ? '?' : '&' ) . $string;
	}

	/**
	 * Modifies URLs to use SSL connections
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $url Source URL to rewrite
	 * @return string $url The secure URL
	 **/
	public static function force_ssl( $url, $rewrite = false ) {
		if ( is_ssl() || $rewrite )
			$url = str_replace('http://', 'https://', $url);
		return $url;
	}

	/**
	 * Encodes an all parts of a URL
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $url The URL of the link to encode
	 * @return string The encoded link
	 **/
	public static function linkencode( $url ) {
		$search = array('%2F','%3A','%3F','%3D','%26');
		$replace = array('/',':','?','=','&');
		$url = rawurlencode($url);
		return str_replace($search, $replace, $url);
	}

	/**
	 * Returns the raw url that was requested
	 *
	 * Useful for getting the complete value of the requested url
	 *
	 * @author Jonathan Davis, John Dillick
	 * @since 1.1
	 *
	 * @return string raw request url
	 **/
	public static function raw_request_url() {
		$options = array(
			'HTTP_HOST' => FILTER_SANITIZE_STRING,
			'REQUEST_URI' => FILTER_SANITIZE_STRING,
			'QUERY_STRING' => FILTER_SANITIZE_STRING
		);
		$server = filter_var_array($_SERVER, $options);
		return esc_url(
			'http' .
			( is_ssl() ? 's' : '' ) .
			'://' .
			$server['HTTP_HOST'] .
			$server['REQUEST_URI'] .
			( ! empty($server['QUERY_STRING']) ? '?' : '' ) . $server['QUERY_STRING']
		);
	}

}
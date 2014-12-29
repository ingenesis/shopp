<?php
/**
 * Plugin Name: Shopp Services
 * Plugin URI: http://shopplugin.com
 * Description: Provides performance improvements for Shopp asset services
 * Version: 1.0
 * Author: Ingenesis Limited
 * Author URI: http://ingenesis.net
 * Requires at least: 3.5
 * Tested up to: 4.0
 *
 *    Portions created by Ingenesis Limited are Copyright © 2014 by Ingenesis Limited
 *
 *    This file is part of Shopp.
 *
 *    Shopp is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Shopp is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Shopp.  If not, see <http://www.gnu.org/licenses/>.
 *
 **/

// Prevent direct access
defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit;

if ( empty($Shopp) ) // Only load in mu-plugins context
	ShoppServices::load();

final class ShoppServices {

	/**
	 * Detects script requests
	 *
	 * @return bool True if a script request, false otherwise
	 **/
	private static function scripts () {
		return ( isset($_GET['load']) && false !== strpos($_SERVER['REQUEST_URI'], 'sp-scripts.js') );
	}

	/**
	 * Detects style requests
	 *
	 * @return bool True if a style request, false otherwise
	 **/
	private static function styles () {
		return ( isset($_GET['load']) && false !== strpos($_SERVER['REQUEST_URI'], 'sp-styles.css') );
	}

	/**
	 * Detects image requests
	 *
	 * @return bool True if a image request, false otherwise
	 **/
	private static function images () {
		return ( isset($_GET['siid']) || 1 == preg_match('{^/.+?/images/\d+/.*$}', $_SERVER['REQUEST_URI']) );
	}

	/**
	 * Detects any ShoppServices requests
	 *
	 * @return bool True if a image request, false otherwise
	 **/
	private static function requested () {
		return ( self::scripts() || self::styles() || self::images() );
	}

	/**
	 * Routes service requests to the proper service
	 *
	 * @return void
	 **/
	public static function serve () {
		$services = dirname(ShoppLoader()->basepath()) . '/services';

		// Image Server request handling
		if ( self::images() )
			return require "$services/image.php";

		// Script Server request handling
		if ( self::scripts() )
			return require "$services/scripts.php";

		// Script Server request handling
		if ( self::styles() )
			return require "$services/styles.php";
	}

	/**
	 * Handles loading service requests without third-party plugin interference
	 *
	 * @return void
	 **/
	public static function load () {
		if ( ! self::requested() ) return;

		$excludes = array('ShoppServices', 'excludes');
		add_filter( 'option_active_plugins', $excludes);
		add_filter( 'site_option_active_sitewide_plugins', $excludes);
	}

	/**
	 * Filters to exclude third-party plugins while loading Shopp and other select plugins
	 *
	 * @param array $plugins The plugins list that WordPress will load
	 * @return array Modified list of plugins for WordPress to load
	 **/
	private static function excludes ( array $plugins = array() ) {
		$load = (array) get_option('shopp_services_plugins');
		foreach ( $plugins as $i => $name ) {
			if ( false !== strpos( $name, 'Shopp.php' ) || isset($load[ $name ]) ) continue;
			unset($plugins[ $i ]);
		}
		return $plugins;
	}

}
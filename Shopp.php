<?php
/**
 * Plugin Name: Shopp
 * Plugin URI: http://shopplugin.com
 * Description: An ecommerce framework for WordPress.
 * Version: 1.4
 * Author: Ingenesis Limited
 * Author URI: http://ingenesis.net
 * Requires at least: 4.4
 * Tested up to: 4.9.5
 *
 *    Portions created by Ingenesis Limited are Copyright © 2008-2014 by Ingenesis Limited
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

// Start the bootloader
require 'core/library/Loader.php';

// Load Composer dependencies
require 'vendor/autoload.php';

// Prevent loading the plugin in special circumstances
if ( Shopp::services() || Shopp::unsupported() ) return;

/* Start the core */
Shopp::plugin();
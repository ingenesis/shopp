<?php
/**
 * Plugin Name: Webshop1310
 * Plugin URI: http://www.webshop1310.com
 * Description: An ecommerce framework for WordPress based on Shopp.
 * Version: 1.3.10.1
 * Author: Jonathan Davis - Ingenesis Limited, Webshop1310
 * Author URI: http://webshop1310.com
 * Requires at least: 3.5
 * Tested up to: 4.5.2
 *
 *    Portions created by Ingenesis Limited are Copyright © 2008-2016 by Ingenesis Limited
 *
 *    This file is part of Webshop1310.
 *
 *    Webshop1310 is based on Shopp version 1.3.10.
 *
 *    Webshop1310 is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Webshop1310 is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Webshop1310.  If not, see <http://www.gnu.org/licenses/>.
 *
 **/

// Prevent direct access
defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit;

// Start the bootloader
require 'core/library/Loader.php';

// Prevent loading the plugin in special circumstances
if ( Shopp::services() || Shopp::unsupported() ) return;

/* Start the core */
Shopp::plugin();
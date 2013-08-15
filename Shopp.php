<?php
/**
 * Plugin Name: Shopp
 * Plugin URI: http://shopplugin.com
 * Description: An ecommerce framework for WordPress.
 * Version: 1.3dev
 * Author: Ingenesis Limited
 * Author URI: http://ingenesis.net
 * Requires at least: 3.5
 * Tested up to: 3.5.2
 *
 *    Portions created by Ingenesis Limited are Copyright © 2008-2013 by Ingenesis Limited
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

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

require 'core/library/Core.php'; // Load core function library

/**
 * Shopp core plugin management class
 *
 * @author Jonathan Davis
 * @since 1.0
 * @version 1.3
 * @package shopp
 **/
class Shopp extends ShoppCore {

	const VERSION = '1.3dev';
	const CODENAME = 'Mars';

	private static $object = false;

	private function __construct () {

		require 'core/library/Loader.php';

		$this->paths();				// Determine Shopp paths
		$this->constants();			// Setup Shopp constants

		load_plugin_textdomain( 'Shopp', false, SHOPP_DIR . '/lang' );

		// Load the Developer API
		ShoppDeveloperAPI::load( SHOPP_PATH );

		// Initialize error system
		ShoppErrors();

		// Initialize application control processing
		$this->Flow = new ShoppFlow();

		// Init deprecated properties for legacy add-on module compatibility
		$this->Shopping = ShoppShopping();
		$this->Settings = ShoppSettings();

		// Hooks
		add_action('init', array($this, 'init'));

		// Core WP integration
		add_action('shopp_init', array($this, 'pages'));
		add_action('shopp_init', array($this, 'collections'));
		add_action('shopp_init', array($this, 'taxonomies'));
		add_action('shopp_init', array($this, 'products'), 99);
		add_action('shopp_init', array($this, 'rebuild'), 99);

		add_filter('rewrite_rules_array', array($this, 'rewrites'));
		add_filter('query_vars', array($this, 'queryvars'));

		// Theme integration
		add_action('widgets_init', array($this, 'widgets'));

		// Plugin management
		add_action('after_plugin_row_' . SHOPP_PLUGINFILE, array('ShoppSupport', 'status'), 10, 2);
		add_action('install_plugins_pre_plugin-information', array('ShoppSupport', 'changelog'));

		$updates = array('load-plugins', 'load-update.php', 'load-update-core.php', 'wp_update_plugins', 'shopp_check_updates');
		foreach ( $updates as $action ) add_action($action, array('ShoppSupport', 'updates'));

	}

	/**
	 * Singleton accessor method
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return Shopp Provides the running Shopp object
	 **/
	public static function object () {
		if ( ! self::$object instanceof self ) {
			self::$object = new self;
			do_action('shopp_loaded');
		}
		return self::$object;
	}

	/**
	 * Initializes the Shopp runtime environment
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function init () {

		$this->Collections = array();
		$this->Order = new ShoppOrder();
		$this->Gateways = new GatewayModules();
		$this->Shipping = new ShippingModules();
		$this->Storage = new StorageEngines();
		$this->APIs = new ShoppAPIModules();

		new ShoppLogin();
		do_action('shopp_init');
	}

	/**
	 * Setup configurable constants
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function constants () {
		if ( ! defined('SHOPP_VERSION') )				define( 'SHOPP_VERSION', self::VERSION );
		if ( ! defined('SHOPP_GATEWAY_USERAGENT') )		define( 'SHOPP_GATEWAY_USERAGENT', 'WordPress Shopp Plugin/' . SHOPP_VERSION );

		// @deprecated
		if ( ! defined('SHOPP_HOME') )					define( 'SHOPP_HOME', ShoppSupport::HOMEPAGE );
		if ( ! defined('SHOPP_CUSTOMERS') )				define( 'SHOPP_CUSTOMERS', ShoppSupport::FORUMS);
		if ( ! defined('SHOPP_DOCS') )					define( 'SHOPP_DOCS', ShoppSupport::DOCS );

		// Helper for line break output
		if ( ! defined('BR') ) 							define('BR', '<br />');

		// Overrideable config macros
		if ( ! defined('SHOPP_NOSSL') )					define('SHOPP_NOSSL', false);					// Require SSL to protect transactions, overrideable for development
		if ( ! defined('SHOPP_PREPAYMENT_DOWNLOADS') )	define('SHOPP_PREPAYMENT_DOWNLOADS', false);		// Require payment capture granting access to downloads
		if ( ! defined('SHOPP_SESSION_TIMEOUT') )		define('SHOPP_SESSION_TIMEOUT', 7200);			// Sessions live for 2 hours
		if ( ! defined('SHOPP_CART_EXPIRES') )			define('SHOPP_CART_EXPIRES', 1209600);			// Carts are stashed for up to 2 weeks
		if ( ! defined('SHOPP_QUERY_DEBUG') )			define('SHOPP_QUERY_DEBUG', false);				// Debugging queries is disabled by default
		if ( ! defined('SHOPP_GATEWAY_TIMEOUT') )		define('SHOPP_GATEWAY_TIMEOUT', 10);				// Gateway connections timeout after 10 seconds
		if ( ! defined('SHOPP_SHIPPING_TIMEOUT') )		define('SHOPP_SHIPPING_TIMEOUT', 10);			// Shipping provider connections timeout after 10 seconds
		if ( ! defined('SHOPP_TEMP_PATH') )				define('SHOPP_TEMP_PATH', sys_get_temp_dir());	// Use the system defined temporary directory
		if ( ! defined('SHOPP_NAMESPACE_TAXONOMIES') )	define('SHOPP_NAMESPACE_TAXONOMIES', true);		// Add taxonomy namespacing for permalinks /shop/category/category-name, /shopp/tag/tag-name
	}

	/**
	 * Setup path related constants
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function paths () {

		$path = sanitize_path(dirname(__FILE__));
		$file = basename(__FILE__);
		$directory = basename($path);

		// Paths
		define('SHOPP_PATH', $path );
		define('SHOPP_DIR', $directory );
		define('SHOPP_PLUGINFILE', "$directory/$file" );
		define('SHOPP_PLUGINURI', set_url_scheme(WP_PLUGIN_URL . "/$directory") );

		define('SHOPP_WPADMIN_URL', admin_url() ); // @deprecated, use admin_url() instead

		define('SHOPP_ADMIN_DIR', '/core/ui');
		define('SHOPP_ADMIN_PATH', SHOPP_PATH . SHOPP_ADMIN_DIR);
		define('SHOPP_ADMIN_URI',  SHOPP_PLUGINURI . SHOPP_ADMIN_DIR);
		define('SHOPP_ICONS_URI',  SHOPP_ADMIN_URI . '/icons');
		define('SHOPP_FLOW_PATH',  SHOPP_PATH . '/core/flow');
		define('SHOPP_MODEL_PATH', SHOPP_PATH . '/core/model');
		define('SHOPP_GATEWAYS',   SHOPP_PATH . '/gateways');
		define('SHOPP_SHIPPING',   SHOPP_PATH . '/shipping');
		define('SHOPP_STORAGE',    SHOPP_PATH . '/storage');
		define('SHOPP_THEME_APIS', SHOPP_PATH . '/api/theme');
		define('SHOPP_DBSCHEMA',   SHOPP_MODEL_PATH . '/schema.sql');

	}

	/**
	 * Sets up permalink handling for Storefront pages
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function pages () {

		shopp_register_page( 'ShoppCatalogPage' );
		shopp_register_page( 'ShoppAccountPage' );
		shopp_register_page( 'ShoppCartPage' );
		shopp_register_page( 'ShoppCheckoutPage' );
		shopp_register_page( 'ShoppConfirmPage' );
		shopp_register_page( 'ShoppThanksPage' );

		do_action( 'shopp_init_storefront_pages' );

	}

	/**
	 * Register smart collections
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function collections () {

		shopp_register_collection( 'CatalogProducts' );
		shopp_register_collection( 'NewProducts' );
		shopp_register_collection( 'FeaturedProducts' );
		shopp_register_collection( 'OnSaleProducts' );
		shopp_register_collection( 'BestsellerProducts' );
		shopp_register_collection( 'SearchResults' );
		shopp_register_collection( 'MixProducts' );
		shopp_register_collection( 'TagProducts' );
		shopp_register_collection( 'RelatedProducts' );
		shopp_register_collection( 'AlsoBoughtProducts' );
		shopp_register_collection( 'ViewedProducts' );
		shopp_register_collection( 'RandomProducts' );
		shopp_register_collection( 'PromoProducts' );

	}

	/**
	 * Register custom taxonomies
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function taxonomies () {
		ProductTaxonomy::register( 'ProductCategory' );
		ProductTaxonomy::register( 'ProductTag' );
	}

	/**
	 * Register the product custom post type
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function products () {
		WPShoppObject::register( 'Product', ShoppPages()->baseslug() );
	}

	/**
	 * Registers theme widgets
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.3
	 *
	 * @return void
	 **/
	public function widgets () {

		register_widget( 'ShoppAccountWidget' );
		register_widget( 'ShoppCartWidget' );
		register_widget( 'ShoppCategoriesWidget' );
		register_widget( 'ShoppFacetedMenuWidget' );
		register_widget( 'ShoppProductWidget' );
		register_widget( 'ShoppSearchWidget' );
		register_widget( 'ShoppCategorySectionWidget' );
		register_widget( 'ShoppShoppersWidget' );
		register_widget( 'ShoppTagCloudWidget' );

	}

	/**
	 * Adds Shopp-specific mod_rewrite rule for low-resource, speedy image server and downloads request handler
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $wp_rewrite_rules An array of existing WordPress rewrite rules
	 * @return array Rewrite rules
	 **/
 	public function rewrites ($wp_rewrite_rules) {
 		global $is_IIS;
 		$structure = get_option('permalink_structure');
 		if ('' == $structure) return $wp_rewrite_rules;
 		$path = str_replace('%2F', '/', urlencode(join('/', array(PLUGINDIR, SHOPP_DIR, 'services'))));

 		// Download URL rewrites
		$AccountPage = ShoppPages()->get('account');
 		$downloads = array( ShoppPages()->baseslug(), $AccountPage->slug(), 'download', '([a-f0-9]{40})', '?$' );
 		if ( $is_IIS && 0 === strpos($structure, '/index.php/') ) array_unshift($downloads, 'index.php');
 		$rules = array( join('/', $downloads)
 				=> 'index.php?src=download&shopp_download=$matches[1]',
 		);

 		// Image URL rewrite
 		$images = array( ShoppPages()->baseslug(), 'images', '(\d+)', "?\??(.*)$" );
 		add_rewrite_rule(join('/', $images), $path.'/image.php?siid=$1&$2');

 		return $rules + (array)$wp_rewrite_rules;
 	}

	/**
	 * Force rebuilding rewrite rules when necessary
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function rebuild () {
		if ( ! shopp_setting_enabled('rebuild_rewrites') ) return;

		flush_rewrite_rules();
		shopp_set_setting('rebuild_rewrites', 'off');
	}

	/**
	 * Registers the query variables used by Shopp
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @param array $vars The current list of handled WordPress query vars
	 * @return array Augmented list of query vars including Shopp vars
	 **/
	public function queryvars ($vars) {

		$vars[] = 's_iid';			// Shopp image id
		$vars[] = 's_cs';			// Catalog (search) flag
		$vars[] = 's_ff';			// Category filters
		$vars[] = 'src';			// Shopp resource
		$vars[] = 'shopp_page';
		$vars[] = 'shopp_download';

		return $vars;
	}

	/**
	 * Handles request services like the image server and script server
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean The service load status
	 **/
	public static function services () {
		if ( WP_DEBUG ) define('SHOPP_MEMORY_PROFILE_BEFORE', memory_get_peak_usage(true) );

		// Image Server request handling
		if ( isset($_GET['siid']) || (false !== strpos($_SERVER['REQUEST_URI'], '/images/') && sscanf($_SERVER['REQUEST_URI'], '%s/images/%d/')))
			return require 'services/image.php';

		// Script Server request handling
		if ( isset($_GET['sjsl']) )
			return require 'services/scripts.php';
	}

	// Deprecated properties

	public $Settings;		// @deprecated Shopp settings registry
	public $Flow;			// @deprecated Controller routing
	public $Catalog;		// @deprecated The main catalog
	public $Category;		// @deprecated Current category
	public $Product;		// @deprecated Current product
	public $Purchase; 		// @deprecated Currently requested order receipt
	public $Shopping; 		// @deprecated The shopping session
	public $Errors;			// @deprecated Error system
	public $Order;			// @deprecated The current session Order
	public $Promotions;		// @deprecated Active promotions registry
	public $Collections;	// @deprecated Collections registry
	public $Gateways;		// @deprecated Gateway modules
	public $Shipping;		// @deprecated Shipping modules
	public $APIs;			// @deprecated Loaded API modules
	public $Storage;		// @deprecated Storage engine modules

} // END class Shopp

if ( Shopp::services() || Shopp::unsupported() ) return; // Prevent loading the plugin

/* Start the core */
$Shopp = Shopp::object();
add_action('shopp_loaded');
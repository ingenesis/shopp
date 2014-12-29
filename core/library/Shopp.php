<?php
/**
 * Shopp.php
 *
 * Plugin integration with WordPress
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, 2008-2014
 * @license (@see license.txt)
 * @package shopp
 * @since 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Shopp core plugin management class
 *
 * @author Jonathan Davis
 * @since 1.0
 * @version 1.3
 * @package shopp
 **/
final class Shopp extends ShoppCore {

	private static $object = false;

	private function __construct () {

		$this->paths();      // Determine Shopp paths
		$this->constants();  // Setup Shopp constants
		$this->textdomain(); // Load the translation file

		// Load the Developer API
		ShoppDeveloperAPI::load( SHOPP_PATH );

		// Initialize error system
		ShoppErrors();

		// Initialize application control processing
		$this->Flow = new ShoppFlow();

		// Initialize Settings
		$this->Settings = ShoppSettings();

		// Hooks
		add_action('init', array($this, 'init'));

		// Core WP integration
		add_action('shopp_init', array($this, 'pages'));
		add_action('shopp_init', array($this, 'collections'));
		add_action('shopp_init', array($this, 'taxonomies'));
		add_action('shopp_init', array($this, 'products'), 99);

		// Theme integration
		add_action('widgets_init', array($this, 'widgets'));

		// Request handling
		add_filter('rewrite_rules_array', array($this, 'rewrites'));
		add_filter('query_vars', array($this, 'queryvars'));

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
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

	/**
	 * Boot up the core plugin
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public static function plugin () {
		global $Shopp; // Provide global for backwards compatibility
		$Shopp = Shopp::object();
		do_action('shopp_loaded');
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

		// Start the shopping session
		$this->Shopping = ShoppShopping();

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
		if ( ! defined('SHOPP_VERSION') )              define( 'SHOPP_VERSION', ShoppVersion::release() );
		if ( ! defined('SHOPP_GATEWAY_USERAGENT') )    define( 'SHOPP_GATEWAY_USERAGENT', ShoppVersion::agent() );

		// @deprecated
		if ( ! defined('SHOPP_HOME') )                 define( 'SHOPP_HOME', ShoppSupport::HOMEPAGE );
		if ( ! defined('SHOPP_CUSTOMERS') )            define( 'SHOPP_CUSTOMERS', ShoppSupport::FORUMS);
		if ( ! defined('SHOPP_DOCS') )                 define( 'SHOPP_DOCS', ShoppSupport::DOCS );

		// Helper for line break output
		if ( ! defined('BR') )                         define('BR', '<br />');

		// Overrideable config macros
		if ( ! defined('SHOPP_NOSSL') )                define('SHOPP_NOSSL', false);                             // Require SSL to protect transactions, overrideable for development
		if ( ! defined('SHOPP_PREPAYMENT_DOWNLOADS') ) define('SHOPP_PREPAYMENT_DOWNLOADS', false);              // Require payment capture granting access to downloads
		if ( ! defined('SHOPP_SESSION_TIMEOUT') )      define('SHOPP_SESSION_TIMEOUT', 172800);                  // Sessions live for 2 days
		if ( ! defined('SHOPP_CART_EXPIRES') )         define('SHOPP_CART_EXPIRES', 1209600);                    // Carts are stashed for up to 2 weeks
		if ( ! defined('SHOPP_QUERY_DEBUG') )          define('SHOPP_QUERY_DEBUG', false);                       // Debugging queries is disabled by default
		if ( ! defined('SHOPP_GATEWAY_TIMEOUT') )      define('SHOPP_GATEWAY_TIMEOUT', 10);                      // Gateway connections timeout after 10 seconds
		if ( ! defined('SHOPP_SHIPPING_TIMEOUT') )     define('SHOPP_SHIPPING_TIMEOUT', 10);                     // Shipping provider connections timeout after 10 seconds
		if ( ! defined('SHOPP_SUBMIT_TIMEOUT') )       define('SHOPP_SUBMIT_TIMEOUT', 20);                       // Order submission timeout
		if ( ! defined('SHOPP_TEMP_PATH') )            define('SHOPP_TEMP_PATH', sys_get_temp_dir());            // Use the system defined temporary directory
		if ( ! defined('SHOPP_ADDONS') )               define('SHOPP_ADDONS', WP_CONTENT_DIR . '/shopp-addons'); // A configurable directory to keep Shopp addons
		if ( ! defined('SHOPP_NAMESPACE_TAXONOMIES') ) define('SHOPP_NAMESPACE_TAXONOMIES', true);               // Add taxonomy namespacing for permalinks /shop/category/category-name, /shopp/tag/tag-name

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

		// This should only run once
		if ( defined( 'SHOPP_PATH' ) ) return;

		$filepath = dirname(ShoppLoader::basepath()) . "/Shopp.php";

		$path = sanitize_path(dirname($filepath));
		$file = basename($filepath);
		$directory = basename($path);

		// Paths
		define('SHOPP_PATH', $path );
		define('SHOPP_DIR',  $directory );

		define('SHOPP_PLUGINFILE', "$directory/$file" );
		define('SHOPP_PLUGINURI',  set_url_scheme(plugins_url() . "/$directory") );

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

		// @deprecated
		define('SHOPP_DBSCHEMA',   SHOPP_PATH . '/core/schema/schema.sql');

	}

	/**
	 * Load the text domain translation file
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 * @uses SHOPP_LANG_DIR
	 * @uses SHOPP_ADDONS
	 * @uses SHOPP_DIR
	 *
	 * @return void
	 **/
	public function textdomain () {

		if ( ! defined('SHOPP_LANG_DIR') )	// Add configurable path for language files
			define('SHOPP_LANG_DIR', ( is_dir(SHOPP_ADDONS . '/languages') ? SHOPP_ADDONS . '/languages' : SHOPP_PATH . '/lang' ) );

		load_textdomain(__CLASS__, SHOPP_LANG_DIR . '/' . __CLASS__ . '-' . get_locale() . '.mo');

	}

	/**
	 * Sets up permalink handling for ShoppStorefront pages
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
		WPShoppObject::register( 'ShoppProduct', ShoppPages()->baseslug() );
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
 		if ( '' == $structure ) return $wp_rewrite_rules;

 		$path = str_replace('%2F', '/', urlencode(join('/', array(PLUGINDIR, SHOPP_DIR, 'services'))));

 		// Download URL rewrites
		$AccountPage = ShoppPages()->get('account');
		if ( empty($AccountPage) ) { // Ensure a ShoppAccountPage is available #2862
			ShoppPages()->register('ShoppAccountPage');
			$AccountPage = ShoppPages()->get('account');
		}

 		$downloads = array( ShoppPages()->baseslug(), $AccountPage->slug(), 'download', '([a-f0-9]{40})', '?$' );
 		if ( $is_IIS && 0 === strpos($structure, '/index.php/') ) array_unshift($downloads, 'index.php');
 		$rules = array( join('/', $downloads)
 				=> 'index.php?src=download&shopp_download=$matches[1]',
 		);

 		// Image URL rewrite
 		$images = array( ShoppPages()->baseslug(), 'images', '(\d+)', "?\??(.*)$" );
 		add_rewrite_rule(join('/', $images), $path . '/image.php?siid=$1&$2');

 		return $rules + (array) $wp_rewrite_rules;
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

		$vars[] = 'siid';			// Shopp image id
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
		ShoppServices::serve();
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
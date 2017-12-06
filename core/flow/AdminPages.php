<?php
/**
 * AdminPages.php
 *
 * The admin pages controller integrates Shopp admin into WordPress
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Admin pages controller
 *
 * @since 1.4
 **/
class ShoppAdminPages {

	/** @var array $Ajax List of AJAX controllers. */
	public $Ajax = array();

	/** @var ShoppAdminPage $Page The current Shopp admin page object. */
	public $Page = false;

	/** @var array $menu The current menu. */
	public $menu = false;

	/** @var ShoppAdminPages $object The singleton instance. */
	private static $object = false;

	/** @var array $pages Defines a map of pages to create menus from. */
	private $pages = array();

	/** @var array $menus Map of page names to WP screen IDs for initialized Shopp menus. */
	private $menus = array();

	/** @var array $tabs Registered screen tabs (sub-screens) for admin pages. */
	private $tabs = array();

	/** @var string $mainmenu The hook name for the main menu of Shopp. */
	private $mainmenu = false;

	/** @var array $caps Maps Shopp admin pages to Shopp capabilities. */
	private $caps = array(                                     // Initialize the capabilities, mapping to pages
		'main'        => 'shopp_menu',                         // Capabilities                  Role
		'orders'      => 'shopp_orders',                       // _______________________________________________
		'customers'   => 'shopp_customers',                    // shopp_settings                administrator
		'reports'     => 'shopp_financials',                   // shopp_settings_checkout
		'memberships' => 'shopp_products',                     // shopp_settings_payments
		'products'    => 'shopp_products',                     // shopp_settings_shipping
		'categories'  => 'shopp_categories',                   // shopp_settings_taxes
		'discounts'   => 'shopp_promotions',                   // shopp_settings_system
															   // shopp_settings_update
		'settings'              => 'shopp_settings',           // shopp_financials              shopp-merchant
		'settings-payments'     => 'shopp_settings_payments',  // shopp_settings_taxes
		'settings-shipping'     => 'shopp_settings_shipping',  // shopp_settings_presentation
		'settings-boxes'        => 'shopp_settings_shipping',  // shopp_promotions
		'settings-taxes'        => 'shopp_settings_taxes',     // shopp_products
		'settings-advanced'     => 'shopp_settings_system',    // shopp_categories
		'settings-storage'      => 'shopp_settings_system',
		'settings-log'          => 'shopp_settings_system',
		'settings-core'         => 'shopp_settings',
		'settings-orders'       => 'shopp_settings_checkout',
		'settings-downloads'    => 'shopp_settings_checkout',
		'settings-presentation' => 'shopp_settings_presentation',
		'settings-images'       => 'shopp_settings_presentation',
		'settings-pages'        => 'shopp_settings_presentation',
		'welcome'               => 'shopp_menu',
		'credits'               => 'shopp_menu'
	);

	/**
	 * Defines the Shopp pages used to create WordPress menus
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	private function __construct () {

		// Orders menu
		$this->add('orders',     Shopp::__('All Orders'), 'ShoppAdminOrders');
		$this->add('customers',  Shopp::__('Customers'),  'ShoppAdminCustomers');
		$this->add('reports',  	 Shopp::__('Reports'),    'ShoppAdminReports');
		$this->add('settings',   Shopp::__('Settings'),   'ShoppAdminSettings');

		// Settings pages
		$this->add('settings-core',         Shopp::__('Setup'),          'ShoppAdminSettings', 'settings', 'shoppui-th-list');
		$this->add('settings-shipping',     Shopp::__('Shipping Rates'), 'ShoppAdminSettings', 'settings', 'shoppui-map-marker');
		// $this->add('settings-boxes',        Shopp::__('Shipment Boxes'), 'ShoppAdminSettings', 'settings', 'shoppui-archive');
		$this->add('settings-downloads',    Shopp::__('Downloads'),      'ShoppAdminSettings', 'settings', 'shoppui-download');
		$this->add('settings-orders',       Shopp::__('Orders'),         'ShoppAdminSettings', 'settings', 'shoppui-flag');
		$this->add('settings-payments',     Shopp::__('Payments'),       'ShoppAdminSettings', 'settings', 'shoppui-credit');
		$this->add('settings-taxes',        Shopp::__('Taxes'),	         'ShoppAdminSettings', 'settings', 'shoppui-govt');
		$this->add('settings-presentation', Shopp::__('Presentation'),   'ShoppAdminSettings', 'settings', 'shoppui-th-large');
		$this->add('settings-pages',        Shopp::__('Pages'),          'ShoppAdminSettings', 'settings', 'shoppui-file');
		$this->add('settings-images',       Shopp::__('Images'),         'ShoppAdminSettings', 'settings', 'shoppui-picture');
		$this->add('settings-storage',      Shopp::__('Storage'),        'ShoppAdminSettings', 'settings', 'shoppui-cloud');
		$this->add('settings-advanced',     Shopp::__('Advanced'),       'ShoppAdminSettings', 'settings', 'shoppui-cog');

		if ( ShoppErrorLogging()->size() > 0 )
			$this->add('settings-log', Shopp::__('Log'), 'ShoppAdminSettings', 'settings', 'shoppui-info-2');

		// Catalog menu
		$this->add('products',   Shopp::__('Products'),   'ShoppAdminProducts',  'products');
		$this->add('categories', Shopp::__('Categories'), 'ShoppAdminCategories', 'products');

		// Add Shopp taxonomies and custom Shopp taxonomies to menus
		$taxonomies = get_object_taxonomies(ShoppProduct::$posttype, 'object');
		foreach ( $taxonomies as $t ) {
			if ( 'shopp_category' == $t->name ) continue;
			$pagehook = str_replace('shopp_', '', $t->name);
			$this->add($pagehook, $t->labels->menu_name, 'ShoppAdminCategories',  'products');
		}

		$this->add('discounts', Shopp::__('Discounts'), 'ShoppAdminDiscounts', 'products');

		// Adds extra screens
		$this->add('welcome', Shopp::__('Welcome'), 'ShoppAdminWelcome', 'welcome');
		$this->add('credits', Shopp::__('Credits'), 'ShoppAdminWelcome', 'credits');

		// Filter hook for adding/modifying Shopp page definitions
		$this->pages = apply_filters('shopp_admin_pages', $this->pages);
		$this->caps = apply_filters('shopp_admin_caps', $this->caps);

		reset($this->pages);
		$this->mainmenu = key($this->pages);

		add_action('admin_menu', array($this, 'taxonomies'), 100);

		shopp_enqueue_style('menus');

		// Set the currently requested page and menu
		$page = ShoppFlow()->request('page');
		if ( self::posteditor() ) $page = 'shopp-products';
		if ( empty($page) ) return;

		if ( isset($this->pages[ $page ]) ) $this->Page = $this->pages[ $page ];
		if ( isset($this->menus[ $page ]) ) $this->menu = $this->menus[ $page ];

	}

	/**
	 * The singleton access method
	 *
	 * @since 1.4
	 *
	 * @return ShoppAdminPages The singleton instance
	 **/
	public static function object () {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

	/**
	 * Generates the Shopp admin menus
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function menus () {

		global $menu, $plugin_page;

		$access = 'shopp_menu';
		if ( Shopp::maintenance() ) $access = 'manage_options';

		// Add main menus
		$position = shopp_admin_add_menu(Shopp::__('Shopp'), 'orders', 40, false, 'shopp_orders', Shopp::clearpng());
		shopp_admin_add_menu(Shopp::__('Catalog'), 'products', $position, false, 'shopp_products', Shopp::clearpng());

		// Add after the Shopp menus to avoid being purged by the duplicate separator check
		$menu[ $position - 1 ] = array( '', 'read', 'separator-shopp', '', 'wp-menu-separator' );

		// Add menus to WordPress admin
		foreach ( $this->pages as $page ) $this->submenus($page);

		$parent = get_admin_page_parent();

		if ( isset($this->menus[ $parent ]) && false === strpos($this->menus[ $parent ], 'toplevel') ) {
			$current_page = $plugin_page;
			$plugin_page = $parent;
			add_action('adminmenu', create_function('','global $plugin_page; $plugin_page = "' . $current_page. '";'));
		}

	}

	/**
	 * Provide admin support for custom Shopp taxonomies
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function taxonomies () {
		global $submenu;
		if ( ! is_array($submenu) ) return;

		$taxonomies = get_object_taxonomies(ShoppProduct::$posttype);
		foreach ( $submenu['shopp-products'] as &$submenus ) {
			$name = str_replace('-', '_', $submenus[2]);
			if ( ! in_array($name, $taxonomies) ) continue;

			$submenus[2] = "edit-tags.php?taxonomy=$name";

			add_filter('manage_edit-' . $name . '_columns', array('ShoppAdminTaxonomies', 'columns'));
			add_filter('manage_' . $name . '_custom_column', array('ShoppAdminTaxonomies', 'products'), 10, 3);
		}

		add_action('admin_print_styles-edit-tags.php',array(ShoppAdmin(), 'styles'));
		add_action('admin_head-edit-tags.php', array('ShoppAdminTaxonomies', 'parentmenu'));
	}

	/**
	 * Registers a new page to the Shopp admin pages
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $name The internal reference name for the page.
	 * @param string $label The label displayed in the WordPress admin menu.
	 * @param string $controller The name of the controller to use for the page.
	 * @param string $parent The internal reference for the parent page.
	 * @param string $icon The Shopp icon CSS class name.
	 * @return void
	 **/
	private function add ( $name, $label, $controller = null, $parent = null, $icon = null ) {
		$page = ShoppAdmin::pagename($name);

		if ( isset($parent) ) $parent = ShoppAdmin::pagename($parent);
		$capability = isset($this->caps[ $name ]) ? $this->caps[ $name ] : 'shopp_menu';
		$this->pages[ $page ] = new ShoppAdminPage($name, $page, $label, $capability, $controller, $parent, $icon);
	}

	/**
	 * Adds a ShoppAdminPage entry to the WordPress menus under the Shopp menus
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param ShoppAdminPage $Page A ShoppAdminPage object
	 * @return void
	 **/
	private function submenus ( ShoppAdminPage $Page ) {

		$name = $Page->name;
		$pagehook = $Page->page;

		// Set capability
		$capability = isset($this->caps[ $name ]) ? $this->caps[ $name ] : 'none';
		$taxonomies = get_object_taxonomies(ShoppProduct::$posttype, 'names');
		if ( in_array("shopp_$name", $taxonomies) ) $capability = 'shopp_categories';

		// Set controller (callback handler)
		$controller = array(ShoppAdmin(), 'route');

		if ( Shopp::upgradedb() ) $controller = array('ShoppAdmin', 'updatedb');

		$menu = $Page->parent ? $Page->parent : $this->mainmenu;

		shopp_admin_add_submenu(
			$Page->label,
			$pagehook,
			$menu,
			$controller,
			$capability
		);

	}

	/**
	 * Gets or add a ShoppAdmin menu entry.
	 *
	 * @since 1.3
	 *
	 * @param string $name The Shopp-internal name of the menu
	 * @param string $menu The WordPress screen ID
	 * @return string The screen id of the given menu name
	 **/
	public function menu ( $name, $menu = null ) {

		if ( isset($menu) ) $this->menus[ $name ] = $menu;
		if ( isset($this->menus[ $name ]) ) return $this->menus[ $name ];

		return false;

	}

	/**
	 * Provides an array of tabs for the current screen.
	 *
	 * @since 1.4
	 *
	 * @param string $page The page to get tabs for.
	 * @return array The list of page tabs.
	 **/
	public function tabs ( $page ) {
		global $submenu;
		if ( ! isset($this->tabs[ $page ]) ) return array();
		$parent = $this->tabs[ $page ];

		if ( isset($submenu[ $parent ]) ) {
			$tabs = array();
			foreach ( $submenu[ $parent ] as $entry ) {
				$title = $entry[0];
				$tab = $entry[2];

				if ( isset($this->pages[ $tab ]) ) {
					$ShoppPage = $this->pages[ $tab ];
					$icon = $ShoppPage->icon;
				}

				$tabs[ $tab ] = array(
					$title,
					$tab,
					$parent,
					$icon
				);

			}
			return $tabs;
		}

		return array();
	}

	/**
	 * Adds a tab to a parent page.
	 *
	 * @since 1.4
	 *
	 * @param string $tab Tab hook name.
	 * @param string $parent The parent page to register the tabs to.
	 * @return void
	 **/
	public function addtab ( $tab, $parent ) {
		$this->tabs[ $parent ] = $parent;
		$this->tabs[ $tab ] = $parent;
	}

	/**
	 * Gets the name of the controller for the current request or the specified page resource
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $page (optional) The fully qualified reference name for the page
	 * @return string|boolean The name of the controller or false if not available
	 **/
	public function controller ( $page = false ) {

		if ( ! $page && isset($this->Page->controller) )
			return $this->Page->controller;

		if ( isset($this->pages[ $page ]) && isset($this->pages[ $page ]->controller) )
			return $this->pages[ $page ]->controller;

		return false;
	}

	/**
	 * Gets the Shopp-internal name of the main menu
	 *
	 * @since 1.3
	 *
	 * @return string The menu name
	 **/
	public function mainmenu () {
		return $this->mainmenu;
	}

	/**
	 * Determines if the current request is a Shopp admin screen
	 *
	 * @since 1.4
	 *
	 * @return boolean True if the current screen is Shopp admin screen, false otherwise
	 **/
	public function shoppscreen () {
		global $hook_suffix, $taxonomy;

		if ( self::posteditor() ) return true;
		if ( in_array($hook_suffix, $this->menus) ) return true;

		if ( isset($taxonomy) ) { // Prevent loading styles if not on Shopp taxonomy editor
			$taxonomies = get_object_taxonomies(ShoppProduct::$posttype);
			if ( in_array($taxonomy, $taxonomies)) return true;
		}

		return false;
	}

	/**
	 * Detects a Shopp post type editor.
	 *
	 * @since 1.4
	 *
	 * @return bool True if the admin page is a Shopp post-type editor, false otherwise.
	 **/
	public static function posteditor () {
		return ShoppProduct::posttype() == ShoppFlow()->request('post_type') &&  'edit' == ShoppFlow()->request('action');
	}

} // end ShoppAdminMenus

/**
 * A property container for Shopp's admin page meta
 *
 * @since 1.1
 **/
class ShoppAdminPage {

	/** @var string $name The name of the page. */
	public $name = '';

	/** @var string $page The page hook name. */
	public $page = '';

	/** @var string $label The visual label for the page. */
	public $label = '';

	/** @var string $capability The capability required to view the page. */
	public $capability = 'shopp_menu';

	/** @var string $controller The controller class name. */
	public $controller = '';

	/** @var string $parent The parent page hook name. */
	public $parent = false;

	/** @var string $icon The Shopp icon CSS class name. */
	public $icon = false;

	/**
	 * Constructor.
	 *
	 * @since 1.4
	 *
	 * @param string $name The name of the page.
	 * @param string $page The page hook name.
	 * @param string $label The visual label for the page.
	 * @param string $capability The capability required to view the page.
	 * @param string $controller The controller class name.
	 * @param string $parent The parent page hook name.
	 * @param string $icon The Shopp icon CSS class name.
	 * @return void
	 **/
	public function __construct ( $name, $page, $label, $capability, $controller, $parent = null, $icon = null ) {
		$this->name = $name;
		$this->page = $page;
		$this->label = $label;
		$this->capability = $capability;
		$this->controller = $controller;
		$this->parent = $parent;
		$this->icon = $icon;
	}

	/**
	 * The page hook.
	 *
	 * @since 1.4
	 *
	 * @return string The 'shopp' page hook
	 **/
	public function hook () {
		global $admin_page_hooks;
		if ( isset($admin_page_hooks[ $this->parent ]) ) return $admin_page_hooks[ $this->parent ];
		return 'shopp';
	}

} // END class ShoppAdminPage
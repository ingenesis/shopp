<?php
/**
 * Admin.php
 *
 * Super-controller providing Shopp integration with the WordPress Admin
 *
 * @copyright Ingenesis Limited, January 2010-2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Flow/Admin
 * @version   1.4
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * The Shopp admin flow controller.
 *
 * @since 1.1
 **/
class ShoppAdmin extends ShoppFlowController {

	/** @var ShoppAdminController $Controller The current page admin controller. */
	protected $Controller = false;

	/** @var array $request The list of default request parameters. */
	protected $request = array(
		'page' => ''
	);

	/**
	 * Constructor.
	 *
	 * @since 1.1
	 *
	 * @param array $request The list of page request parameters.
	 * @return void
	 **/
	public function __construct ( array $request = array() ) {

		$this->request = array_merge($this->defaults, $request);

		$this->legacyupdate();

		// Start the screen controller
		add_action('current_screen', array($this, 'controller'));

		// Boot up the menus & admin bar
		// add_action( 'admin_bar_menu', array($this, 'adminbar'), 50 );
		// Dashboard widget support
		add_action('wp_dashboard_setup', array('ShoppAdminDashboard', 'init'));

		add_action('admin_init', array($this, 'updates'));
		add_action('admin_init', array('ShoppTinyMCE', 'init'));

		add_action('switch_theme',    array($this, 'themepath'));

		// Admin favorites menu
		add_filter('favorite_actions', array($this, 'favorites'));

		// WordPress custom theme menus
		add_action('load-nav-menus.php', array('ShoppCustomThemeMenus', 'init'));

		add_filter('wp_dropdown_pages', array($this, 'storepages'));
		add_filter('pre_update_option_page_on_front', array($this, 'frontpage'));

	}

	/**
	 * Bootloader for the current page controller.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function controller () {
		$ControllerClass = ShoppAdminPages()->controller();
		if ( ! $ControllerClass ) return;
		$this->Controller = new $ControllerClass($this->request);
	}

	/**
	 * Routes the screen display to the page controller's screen handler.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function route () {
		$this->Controller->screen();
	}

	/**
	 * Takes an internal page name reference and builds the full path name
	 *
	 * @since 1.1
	 *
	 * @param string $page The internal reference name for the page
	 * @return string The fully qualified resource name for the admin page
	 **/
	public static function pagename ( $page ) {
		$base = sanitize_key(SHOPP_DIR);
		return "$base-$page";
	}

	/**
	 * Adds update availability notices for new Shopp releases and installed add-ons
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function updates () {

		add_filter('plugin_row_meta', array('ShoppSupport', 'addons'), 10, 2); // Show installed addons

		if ( ShoppSupport::activated() ) return;

		add_action('in_plugin_update_message-' . SHOPP_PLUGINFILE, array('ShoppSupport', 'wpupdate'), 10, 2);
		add_action('after_plugin_row_' . SHOPP_PLUGINFILE, array('ShoppSupport', 'pluginsnag'), 10, 2);

	}

	/**
	 * Displays the database update screen
	 *
	 * @since 1.3
	 *
	 * @return boolean
	 **/
	public static function updatedb () {
		$uri = SHOPP_ADMIN_URI . '/styles';
		wp_enqueue_style('shopp.welcome', "$uri/welcome.css", array(), ShoppVersion::cache(), 'screen');
		include( SHOPP_ADMIN_PATH . '/help/update.php');
	}

	/**
	 * Adds a 'New Product' shortcut to the WordPress admin favorites menu
	 *
	 * @since 1.1
	 *
	 * @param array $actions List of actions in the menu
	 * @return array Modified actions list
	 **/
	public function favorites ( array $actions = array() ) {
		$key = esc_url(add_query_arg(array('page' => $this->pagename('products'), 'id' => 'new'), admin_url('admin.php')));
	    $actions[ $key ] = array(Shopp::__('New Product'), 8);
		return $actions;
	}

	/**
	 * Update the stored path to the activated theme
	 *
	 * Automatically updates the Shopp theme path setting when the
	 * a new theme is activated.
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function themepath () {
		shopp_set_setting('theme_templates', addslashes(sanitize_path(STYLESHEETPATH . '/' . "shopp")));
	}

	/**
	 * Handle auto-updates from Shopp 1.0
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function legacyupdate () {
		global $plugin_page;
		if ( 'shopp-settings-update' == $plugin_page && isset($_GET['updated']) && $_GET['updated'] == 'true' )
				Shopp::redirect(add_query_arg('page', ShoppAdmin::pagename('orders'), admin_url('admin.php')));
	}


	/**
	 * Adds Shopp pages to the page_on_front menu
	 *
	 * @since 1.3
	 *
	 * @param string $menu The current page_on_front menu
	 * @return string The page_on_front menu with the Shopp storefront page included
	 **/
	public function storepages ( $menu ) {
		$CatalogPage = ShoppPages()->get('catalog');
		$shoppid = ShoppCatalogPage::frontid(); // uses impossibly long number ("Shopp" in decimal)

		$id = "<select name='page_on_front' id='page_on_front'>\n";
		if ( false === strpos($menu, $id) ) return $menu;
		$token = '<option value="0">&mdash; Select &mdash;</option>';

		if ( $shoppid == get_option('page_on_front') ) $selected = ' selected="selected"';
		$storefront = '<optgroup label="' . __('Shopp','Shopp') . '"><option value="' . $shoppid . '"' . $selected . '>' . esc_html($CatalogPage->title()) . '</option></optgroup><optgroup label="' . __('WordPress') . '">';

		$newmenu = str_replace($token, $token . $storefront, $menu);

		$token = '</select>';
		$newmenu = str_replace($token, '</optgroup>' . $token, $newmenu);
		return $newmenu;
	}

	/**
	 * Filters the page_on_front option during save to handle the bigint on non 64-bit environments
	 *
	 * @since 1.3
	 *
	 * @param mixed $value The value to save
	 * @return mixed The value to save
	 **/
	public function frontpage ( $value ) {
		if ( ! isset($_POST['page_on_front']) ) return $value;
		$shoppid = ShoppCatalogPage::frontid(); // uses impossibly long number ("Shopp" in decimal)
		if ( $_POST['page_on_front'] == $shoppid ) return "$shoppid";
		else return $value;
	}

	/**
	 * Gets the current screen id.
	 *
	 * @since 1.4
	 *
	 * @return string The current screen ID.
	 **/
	public static function screen () {
		return get_current_screen()->id;
	}

} // END class ShoppAdmin

/**
 * Admin controller that proxies screen controllers
 *
 * This abstract class is the foundation for creating a
 * proxy between the WP_Screen system and the ShoppAdmin controller.
 * The ShoppFlow super-controller uses ShoppAdminPages to configure
 * the pages and setup the WP menus.
 *
 * ShoppAdmin determines the proper admin controller to route to
 * and builds it. The ShoppAdminController in turn parses the request
 * to understand which ShoppScreenController should be used (this
 * easily allows for subscreens under a single menu entry). Constructing
 * the ShoppScreenController early allows for form parsing operations (ops)
 * to occur before the screen is ever called on, but keeps a controller
 * model active to hold notices or allow for screen redirects before
 * the WP menu callback is called.
 *
 * The default screen callback set for the WP menus is
 * ShoppAdmin->route() which calls ShoppAdminController->screen() which
 * acts as a proxy to ShoppScreenController->screen().
 *
 * @uses ShoppScreenController
 * @package Shopp/Flow/Admin
 * @since 1.4
 **/
abstract class ShoppAdminController extends ShoppFlowController {

	/** @var string The URL for this admin screen */
	protected $ui = false;

	/** @var array The default request parameters for this screen */
	protected $defaults = array(
		'page' => ''
	);

	/** @var ShoppScreen The URL for this admin screen */
	protected $Screen = false;

	/** @var array Registry of notices to display to hand off to the screen */
	protected $notices = array();

	/**
	 * Constructor
	 *
	 * @since 1.2
	 * @return void
	 **/
	public function __construct () {

		if ( ! ShoppAdminPages()->shoppscreen() ) return;

		// Get the query request
		$this->query();

		// Setup the screen controller
		$ControllerClass = $this->route();
		if ( false == $ControllerClass || ! class_exists($ControllerClass) ) return;

		$this->Screen = new $ControllerClass($this->ui);

		// Queue JavaScript & CSS
		add_action('admin_enqueue_scripts', array($this, 'assets'), 50);

		// Screen setup
		$screen = ShoppAdmin::screen();
		add_action('load-' . $screen, array($this, 'help'));
		add_action('load-' . $screen, array($this, 'layout'));
		add_action('load-' . $screen, array($this, 'maintenance'));

	}

	/**
	 * Used to route control to screens
	 *
	 * @since 1.4
	 * @return string Return the class name as a string of the subordinate screen controller
	 **/
	protected function route () {
		/** Optionally implement in concrete classes **/
	}

	/**
	 * Calls the subordinate Screen help handler
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function help () {
		$this->Screen->help();
	}

	/**
	 * Calls the subordinate Screen layout handler
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function layout () {
		$this->Screen->layout();
	}

	/**
	 * Calls the subordinate Screen's screen method handler
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function screen () {

		if ( ! current_user_can(ShoppAdminPages()->Page->capability) )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$this->Screen->screen();

	}

	/**
	 * Dynamically includes necessary JavaScript and stylesheets for the admin
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function assets () {
		// Global scripts
		shopp_enqueue_script('shopp');

		// Global styles
		shopp_enqueue_style('colorbox');
		shopp_enqueue_style('admin');
		shopp_enqueue_style('icons');
		shopp_enqueue_style('selectize');

		if ( 'rtl' == get_bloginfo('text_direction') )
			shopp_enqueue_style('admin-rtl');

		// Screen assets (scripts & styles)
		$this->Screen->assets();

		do_action('shopp_' . $this->Screen->slug(). '_admin_scripts');

	}

	/**
	 * Adds a maintenance mode notice to every admin screen
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function maintenance () {

		if ( ShoppLoader::is_activating() || Shopp::upgradedb() ) return;

		$setting = isset($_POST['settings']['maintenance']);
		$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : false;

		if ( false !== $setting && wp_verify_nonce($nonce, 'shopp-setup') )
			shopp_set_setting('maintenance', $setting);

		if ( ! Shopp::maintenance() ) return;

		if ( wp_verify_nonce($this->request('_wpnonce'), 'shopp_disable_maintenance') ) {
			shopp_set_setting('maintenance', 'off');
		} else {
			$url = wp_nonce_url(add_query_arg('page', ShoppAdmin::pagename('settings'), admin_url('admin.php')), 'shopp_disable_maintenance');
			$this->Screen->notice(Shopp::__('Shopp is currently in maintenance mode. %sDisable Maintenance Mode%s', '<a href="' . $url . '" class="button">', '</a>'), 'error', 1);
		}
	}

}

/**
 * A variant admin controller for handling form input
 *
 * This is for HTTP-POST data handling in addition to HTTP-GET query handling.
 *
 * @since 1.4
 **/
class ShoppAdminPostController extends ShoppAdminController {

	public function __construct () {

		if ( ! ShoppAdminPages()->shoppscreen() ) return;
        $this->posted();
		parent::__construct();

	}

}

/**
 * Customizes Shopp taxonomy screens.
 *
 * @since 1.4
 **/
class ShoppAdminTaxonomies {

	/**
	 * Resets the parent menu to the Shopp Catalog menu.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function parentmenu () {
		global $parent_file, $taxonomy;
		$taxonomies = get_object_taxonomies(ShoppProduct::$posttype);
		if ( in_array($taxonomy, $taxonomies) ) $parent_file = 'shopp-products';
	}

	/**
	 * Defines the column layout for Shopp taxonomy list screens.
	 *
	 * @since 1.4
	 *
	 * @param array $cols The default columns for taxonomy screens.
	 * @return Defines the columns for Shopp taxonomy screens.
	 **/
	public function columns ( array $cols ) {
		return array(
			'cb'          => '<input type="checkbox" />',
			'name'        => __('Name'),
			'description' => __('Description'),
			'slug'        => __('Slug'),
			'products'    => Shopp::__('Products')
		);
	}

	/**
	 * Generates the product column markup for taxonomy list screens
	 *
	 * @since 1.2
	 *
	 * @param string $markup The markup for the product column.
	 * @param string $name The name of the taxonomy term.
	 * @param int $term_id The taxonomy term ID.
	 * @return string The markup for the product column.
	 **/
	public function products ( $markup, $name, $term_id ) {
		global $taxonomy;
		if ( 'products' != $name ) return;

		$term = get_term($term_id, $taxonomy);
		$markup = '<a href="admin.php?page=shopp-products&' . $taxonomy . '=' . $term->slug . '">' . $term->count . '</a>';

		return $markup;
	}

} // end ShoppAdminTaxonomies

/**
 * Adds the Shopp button to the TinyMCE toolbar.
 *
 * @access private
 *
 * @since 1.4
 **/
class ShoppTinyMCE {

	/**
	 * Initializes the runtime object.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public static function init () {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) return;
		new self();
	}

	/**
	 * Constructor
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function __construct () {

		$len = strlen( ABSPATH );
		$p = '';

		for ( $i = 0; $i < $len; $i++ )
			$p .= 'x' . dechex( ord( substr( ABSPATH, $i, 1 ) ) + $len );

		// Add TinyMCE buttons when using rich editor
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			global $pagenow, $plugin_page;
			$pages = array( 'post.php', 'post-new.php', 'page.php', 'page-new.php' );
			$editors = array( 'shopp-products', 'shopp-categories' );
			if ( ! ( in_array( $pagenow, $pages ) || ( in_array( $plugin_page, $editors ) && ! empty( $_GET['id'] ) ) ) )
				return false;

			wp_localize_script( 'editor', 'ShoppDialog', array(
				'title' => __( 'Insert Product/Category', 'Shopp' ),
				'desc' => __( 'Insert a product or category from Shopp...', 'Shopp' ),
				'p' => $p
			));

			add_filter( 'mce_external_plugins', array( $this, 'plugin' ), 5 );
			add_filter( 'mce_buttons', array( $this, 'button' ), 5 );
		}
	}

	/**
	 * Adds the Shopp TinyMCE plugin to the list of loaded plugins
	 *
	 * @since 1.0
	 *
	 * @param array $plugins The current list of plugins to load
	 * @return array The updated list of plugins to laod
	 **/
	public function plugin ( $plugins ) {
		// Add a changing query string to keep the TinyMCE plugin from being cached & breaking TinyMCE in Safari/Chrome
		$plugins['Shopp'] = SHOPP_ADMIN_URI . '/behaviors/tinymce/tinyshopp.js?ver=' . time();

		return $plugins;
	}

	/**
	 * Adds the Shopp button to the TinyMCE editor
	 *
	 * @since 1.0
	 *
	 * @param array $buttons The current list of buttons in the editor
	 * @return array The updated list of buttons in the editor
	 **/
	public function button ( $buttons ) {
		array_push($buttons, '|', 'Shopp');
		return $buttons;
	}

}

/**
 * Adds ShoppPages and SmartCollection support to WordPress theme menus system.
 *
 * @access private
 * @since 1.4
 **/
class ShoppCustomThemeMenus {

	/**
	 * Initializes the runtime object.
	 *
	 * @since
	 *
	 * @return void Description...
	 **/
	public static function init () {
		new self();
	}

	/**
	 * Adds ShoppPages and SmartCollection support to WordPress theme menus system
	 *
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function __construct () {

		$this->request();
		add_action('wp_setup_nav_menu_item', array($this, 'setup'));

		new ShoppPagesMenusBox('nav-menus', 'side', 'low');
		new ShoppCollectionsMenusBox('nav-menus', 'side', 'low');

		$this->enable();
		
	}

	/**
	 * Modify the request for ShoppPages
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	protected function request () {
		global $nav_menu_selected_id;

		$request = $_POST;

		if ( ! isset($request['add-shopp-menu-item']) ) return;

		if ( ! isset($request['menu-item']) ) return;

		// $pages = ShoppStorefront::pages_settings();
		$nav_menu_selected_id = isset( $_POST['menu'] ) ? (int) $_POST['menu'] : 0;

		foreach ( (array) $request['menu-item'] as $key => $item ) {
			if ( ! isset($item['menu-item-shopp-page']) ) continue;

			$requested = $item['menu-item-shopp-page'];
			$Page = ShoppPages()->get($requested);

			$menuitem = &$_REQUEST['menu-item'][ $key ];
			$menuitem['menu-item-db-id'] = 0;
			$menuitem['menu-item-object-id'] = $requested;
			$menuitem['menu-item-object'] = $requested;
			$menuitem['menu-item-type'] = ShoppPages::QUERYVAR;
			$menuitem['menu-item-title'] = $Page->title();
		}

	}

	/**
	 * Filters menu items to set the type labels shown for WordPress theme menus
	 *
	 * @since 1.2
	 *
	 * @param object $menuitem The menu item object
	 * @return object The menu item object
	 **/
	public function setup ( $menuitem ) {

		switch ( $menuitem->type ) {
			case 'shopp_page':       $menuitem->type_label = 'Shopp'; break;
			case 'shopp_collection': $menuitem->type_label = 'Collection'; break;
		}

		return $menuitem;
	}
	
	/**
	 * Updates user preferences to automatically show all Shopp menus by default
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	protected function enable () {

		if ( get_user_option( 'metaboxhidden_nav-menus-shopp-enabled' ) !== false )
			return; // Only do this once, otherwise skip it to allow user preference overrides
		
		$user = wp_get_current_user();
		$hidden = get_user_option( 'metaboxhidden_nav-menus', $user->ID);
		$updates = array_filter($options, function ($id) {
			return false === strpos($id, 'shopp');
		});
		
		if ( $hidden !== $updates)
			update_user_option( $user->ID, 'metaboxhidden_nav-menus', $updates, true );
		
		update_user_option( $user->ID, 'metaboxhidden_nav-menus-shopp-enabled', true, true );
	}

} // class ShoppCustomThemeMenu

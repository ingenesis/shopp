<?php
/**
 * Flow.php
 *
 * Super controller and base controller classes for handling low level request processing
 *
 * @copyright Ingenesis Limited, January 2010-2014
 * @version 1.3
 * @package Shopp\Flow
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * The super controller that does intial request routing to the handling controller
 *
 * @since 1.1
 * @package Shopp\Flow
 **/
final class ShoppFlow {

	/** @var ShoppFlowController $Controller The current flow controller instance */
	public $Controller = false; // @todo make this private

	/** @var ShoppAdminController $Admin The admin flow controller */
	public $Admin = false; // @todo Make this private or move the Admin to a singleton?

	/** @var ShoppInstallation $Installer The installer instance */
	private $Installer = false;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function __construct () {

		// Plugin activation & deactivation
		register_deactivation_hook( SHOPP_PLUGINFILE, array($this, 'deactivate') );
		register_activation_hook( SHOPP_PLUGINFILE, array($this, 'activate') );

		// Handle AJAX requests
		add_action( 'admin_init', array($this, 'ajax') );
		add_action( 'admin_init', array($this, 'upgrades') );

		// Boot up the menus & admin bar
		add_action( 'admin_menu', array($this, 'menu'), 50 );
		add_action( 'admin_bar_menu', array($this, 'adminbar'), 50 );

		// Parse the request
		if ( defined('WP_ADMIN') ) add_action( 'current_screen', array($this, 'parse') );
		else add_action( 'parse_request', array($this, 'parse') );
	}

	/**
	 * Parses requests and hands off processing to specific subcontrollers
	 *
	 * @since 1.0
	 *
	 * @param WP|array $request	The request to process
	 * @return void
	 **/
	public function parse ( $request = false ) {

		if ( is_a($request,'WP') )
			$request = empty($request->query_vars) ? $_GET : $request->query_vars;
		else $request = $_GET;

		if ( isset($request['src']) )
			$this->resources($request);

		if ( defined('WP_ADMIN') ) {
			if ( ! isset($_GET['page']) ) return;

			if ( false === $this->Admin )
				$this->Admin = new ShoppAdmin();

			$this->handler();

		} else $this->handler('ShoppStorefront');

	}

	/**
	 * Loads a specified flow controller
	 *
	 * @since 1.1
	 *
	 * @param string|ShoppFlowController $controller The name of the controller class or a controller instance
	 * @return bool True if a controller is set and initialized, false otherwise
	 **/
	public function handler ( $controller = null ) {
		if ( defined('WP_ADMIN') && is_null($controller) && isset($_GET['page']) )
			$controller = $this->Admin->controller($_GET['page']);

		if ( ! $controller ) return false;
		if ( is_a($this->Controller, $controller) ) return true; // Already initialized
		if ( ! class_exists($controller) ) return false;

		if ( ShoppFlow::welcome() ) $controller = 'ShoppAdminWelcome';

		$this->Controller = new $controller();
		do_action('shopp_' . sanitize_key($controller) . '_init');

		return true;
	}

	/**
	 * Initializes the Admin controller
	 *
	 * @since 1.0
	 *
	 * @return boolean True if the admin controller is established, false otherwise
	 **/
	public function admin () {
		if ( ! defined('WP_ADMIN') ) return false;

		if ( $this->handler() ) {
			$this->Controller->admin();
			return true;
		}

		return false;
	}

	/**
	 * Defines the Shopp admin page and menu structure
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function menu () {
		if ( ! defined('WP_ADMIN') ) return;
		$this->Admin = new ShoppAdmin;
		$this->Admin->menus();
		do_action('shopp_admin_menu');
	}

	/**
	 * Start the AJAX controller to handle AJAX requests
	 **/
	public function ajax () {
		if ( ! isset($_REQUEST['action']) || ! defined('DOING_AJAX') ) return;
		new ShoppAjax;
	}

	/**
	 * Start the resource controller to handle resource requests
	 *
	 * Resources requests refers to requests for file resources such as
	 * product downloads or exports
	 *
	 * @param array $request The resource request to process
	 * @return void
	 **/
	public function resources ( array $request ) {
		$this->Controller = new ShoppResources( $request );
	}

	/**
	 * Activates the plugin
	 *
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function activate () {
		$this->installation();
		do_action('shopp_activate');
	}

	/**
	 * Deactivates the plugin
	 *
	 * Resets the data_model to prepare for potential upgrades/changes to the table schema
	 *
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function deactivate() {
		$this->installation();
		do_action('shopp_deactivate');
	}

	/**
	 * Begins the installer
	 *
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function installation () {
		if ( ! defined('WP_ADMIN') ) return;
		// Prevent a new instance if already running
		if ( false !== $this->Installer ) return;

		if ( ! $this->Installer )
			$this->Installer = new ShoppInstallation();
	}

	/**
	 * Begins database updates
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 **/
	public function upgrades () {

		if ( empty($_GET['action']) || 'shopp-upgrade' != $_GET['action'] ) return;

		// Prevent unauthorized users from upgrading (without giving admin's a chance to backup)
		if ( ! current_user_can('activate_plugins') ) return;

		// Prevent outsiders from the upgrade process
		check_admin_referer('shopp-upgrade');

		$Installer = new ShoppInstallation();
		$Installer->upgrade();

		$welcome = add_query_arg( array('page' => $this->Admin->pagename('welcome')), admin_url('admin.php'));
		Shopp::redirect($welcome, true);

	}

	/**
	 * Saves form settings in bulk
	 *
	 * @since 1.2
	 *
	 * @return bool True if settings are saved, false otherwise
	 **/
	public function save_settings () {
		if ( empty($_POST['settings']) || ! is_array($_POST['settings']) ) return false;
		foreach ( $_POST['settings'] as $setting => $value )
			shopp_set_setting($setting, $value);
		return true;
	}

	/**
	 * Adds Shopp shortcuts to the WordPress Admin Bar
	 *
	 * @since 1.2
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance
	 * @return void
	 **/
	public function adminbar ( $wp_admin_bar ) {

		$posttype = get_post_type_object(ShoppProduct::posttype());
		if ( empty( $posttype ) || ! current_user_can($posttype->cap->edit_post) ) return;

		$wp_admin_bar->add_menu(array(
			'parent' => 'new-content',
			'id' => 'new-' . ShoppProduct::posttype(),
			'title' => $posttype->labels->singular_name,
			'href' => admin_url(str_replace('%d', 'new', $posttype->_edit_link))
		));

		$object = get_queried_object();
		if ( ! empty($object) && isset($object->post_type)
			 && $object->post_type == $posttype->name ) {

			$wp_admin_bar->add_menu(array(
				'id' => 'edit',
				'title' => $posttype->labels->edit_item,
				'href' => get_edit_post_link($object->ID)
			));

		}

	}

	/**
	 * Displays the welcome screen
	 *
	 * @since 1.3
	 *
	 * @return boolean
	 **/
	public static function welcome () {
		return defined('WP_ADMIN') && shopp_setting_enabled('display_welcome') && empty($_POST['setup']);
	}

} // End class ShoppFlow

/**
 * ShoppFlowController
 *
 * Provides a template for flow controllers
 *
 * @since 1.1
 * @package shopp
 * @author Jonathan Davis
 **/
abstract class ShoppFlowController  {

	/**
	 * ShoppFlowController constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		/* Implemented in concrete classes */
	}


} // END class ShoppFlowController

/**
 * ShoppAdminController
 *
 * Provides a template for admin controllers
 *
 * @since 1.1
 * @package Shopp\Flow
 **/
abstract class ShoppAdminController extends ShoppFlowController {

	/** @var ShoppAdmin The ShoppAdmin instance for backwards compatibility */
	public $Admin = false;

	/** @var string The URL for this admin screen */
	public $url;

	/** @var string The current screen id */
	public $screen;

	/** @var string The current page of the screen pagination */
	public $page;

	/** @var string The current screen page name */
	public $pagename;

	/** @var array The list of registered notices to show on the screen */
	protected $notices = array();

	/**
	 * ShoppAdminController constructor
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {

		$request = isset($_GET['page']) ? $_GET['page'] : null;

		$Admin = ShoppAdmin();
		if ( ! empty($Admin) ) $this->Admin = $Admin;

		if ( is_null($request) ) return;

		global $plugin_page;
		$this->page = $plugin_page;
		$this->url = add_query_arg('page', $request, admin_url('admin.php'));

		if ( function_exists('get_current_screen') && $screen = get_current_screen() )
			$this->screen = $screen->id;

		if ( false !== strpos($request, '-') ) {
			$pages = explode('-', $_GET['page']);
			$this->pagename = end($pages);
		}

		Shopping::restore('admin_notices', $this->notices);
		add_action('shopp_admin_notices', array($this, 'notices'));

		$this->maintenance();

	}

	/**
	 * Admin screen routing
	 *
	 * Implemented in the concrete classes
	 *
	 * @since 1.1
	 **/
	public function admin () {
		/* Implemented in the concrete classes */
	}

	/**
	 * Adds a notice to the screen
	 *
	 * @since 1.3
	 *
	 * @param string $message The message to add
	 * @param string $style `updated` (optional) The notice style class to use
	 * @param int $priority The priority (order) of the message
	 * @return void
	 **/
	public function notice ( $message, $style = 'updated', $priority = 10 ) {

		$styles = array('updated', 'error');

		$notice = new StdClass();
		$notice->message = $message;
		$notice->style = in_array($style, $styles) ? $style : $styles[0];

		// Prevent duplicates
		$notices = array_map('md5', array_map('json_encode', $this->notices));
		if ( in_array(md5(json_encode($notice)), $notices) ) return;

		array_splice($this->notices, $priority, 0, array($notice));
	}

	/**
	 * Displays registered screen notices
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function notices () {

		if ( empty($this->notices) && ShoppSupport::activated() ) return;

		$markup = array();
		foreach ( $this->notices as $notice ) {
			$markup[] = '<div class="' . $notice->style . '">';
			$markup[] = '<p>' . $notice->message . '</p>';
			$markup[] = '</div>';
		}

		$markup[] = ShoppSupport::reminder();

		if ( ! empty($markup) ) echo join('', $markup);
		$this->notices = array(); // Reset output buffer

	}

	/**
	 * Provides the admin screen page value
	 *
	 * @since 1.3
	 *
	 * @return string The prefixed admin page name
	 **/
	public function page () {
		return ShoppAdmin()->pagename($this->pagename);
	}

	/**
	 * Renders screen tabs from a given associative array
	 *
	 * The tab array uses a tab page slug as the key and the
	 * localized title as the value.
	 *
	 * @since 1.3
	 *
	 * @param array $tabs The tab map array
	 * @return void
	 **/
	protected function tabs ( array $tabs = array() ) {
		global $plugin_page;

		$pagehook = sanitize_key($plugin_page);

		$markup = array();
		if ( empty($tabs) ) $tabs = $this->tabs;
		$default = key($this->tabs);

		foreach ( $tabs as $tab => $title ) {
			$classes = array('nav-tab');
			if ( (! isset($this->tabs[ $plugin_page ]) && $default == $tab) || $plugin_page == $tab )
				$classes[] = 'nav-tab-active';
			$markup[] = '<a href="' . add_query_arg(array('page' => $tab), admin_url('admin.php')) . '" class="' . join(' ', $classes) . '">' . $title . '</a>';
		}

		echo '<h2 class="nav-tab-wrapper">' . join('', apply_filters('shopp_admin_' . $pagehook . '_screen_tabs', $markup)) . '</h2>';
	}

	/**
	 * Handles maintenance mode messages
	 **/
	private function maintenance () {
		if ( ShoppLoader::is_activating() || Shopp::upgradedb() ) return;

		if ( isset($_POST['settings']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'shopp-setup') ) {
			if ( isset($_POST['settings']['maintenance']))
				shopp_set_setting('maintenance', $_POST['settings']['maintenance']);

		}

		if ( Shopp::maintenance() ) {
			if ( isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'shopp_disable_maintenance') ) {
				shopp_set_setting('maintenance', 'off');
			} else {
				$url = wp_nonce_url(add_query_arg('page', $this->Admin->pagename('setup'), admin_url('admin.php')), 'shopp_disable_maintenance');
				$this->notice(Shopp::__('Shopp is currently in maintenance mode. %sDisable Maintenance Mode%s', '<a href="' . $url . '" class="button">', '</a>'), 'error', 1);
			}
		}
	}

	/**
	 * Generates the full URL for the current admin screen
	 *
	 * @since 1.3
	 *
	 * @param array $params (optional) The parameters to include in the URL
	 * @return string The generated URL with parameters
	 **/
	static function url ( $params = array() ) {
		$params = array_map('esc_attr', $params);
		$params['page'] = esc_attr($_GET['page']);
		return add_query_arg($params, admin_url('admin.php'));
	}

	/**
	 * Helper to load a UI view template
	 *
	 * Used with `include` statements so that any local variables
	 * are still in scope when the template is included.
	 *
	 * @since 1.3
	 *
	 * @param string $file The file to include
	 * @return string|bool The file path or false if not found
	 **/
	protected function ui ( $file ) {
		$path = join('/', array(SHOPP_ADMIN_PATH, $this->ui, $file));
		if ( is_readable($path) )
			return $path;

		$this->notice(Shopp::__('The requested setting screen was not found.'),'error');
		echo '<div class="wrap shopp"><div class="icon32"></div><h2>Oops.</h2></div>';
		do_action('shopp_admin_notices');
		return false;
	}

}

/**
 * Helper to access the Shopp Storefront contoller
 *
 * @since 1.1.5
 *
 * @return ShoppStorefront|false
 **/
function &ShoppStorefront () {
	$false = false;
	$Shopp = Shopp::object();
	if ( ! isset($Shopp->Flow) || ! is_object($Shopp->Flow->Controller) ) return $false;
	if ( get_class($Shopp->Flow->Controller) != 'ShoppStorefront' ) return $false;
	return $Shopp->Flow->Controller;
}

/**
 * Provides the Admin controller instance
 *
 * @since 1.2
 *
 * @return ShoppAdminController|bool The ShoppAdminController instance or false
 **/
function &ShoppAdmin() {
	$false = false;
	$Shopp = Shopp::object();
	if ( ! isset($Shopp->Flow) || ! isset($Shopp->Flow->Admin) || empty($Shopp->Flow->Admin) ) return $false;
	return $Shopp->Flow->Admin;
}
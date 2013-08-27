<?php
/**
 * Flow.php
 *
 * Super controller and base controller classes for handling low level request processing
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, January, 2010
 * @package shopp
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppFlow
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ShoppFlow {

	public $Controller = false;
	public $Admin = false;
	public $Installer = false;
	public $Logins = false;

	/**
	 * Flow constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct () {

		// Plugin activation & deactivation
		register_deactivation_hook( SHOPP_PLUGINFILE, array($this, 'deactivate') );
		register_activation_hook( SHOPP_PLUGINFILE, array($this, 'activate') );

		// Handle AJAX requests
		add_action( 'admin_init', array($this, 'ajax') );

		// Boot up the menus & admin bar
		add_action( 'admin_menu', array($this, 'menu'), 50 );
		add_action( 'admin_bar_menu', array($this, 'adminbar'), 50 );

		// Handle automatic updates
		add_action('update-custom_shopp', array($this, 'update'));

		// Parse the request
		if ( defined('WP_ADMIN') ) add_action( 'current_screen', array($this, 'parse') );
		else add_action( 'parse_request', array($this, 'parse') );
	}

	/**
	 * Parses requests and hands off processing to specific subcontrollers
	 *
	 * @author Jonathan Davis
	 *
	 * @return boolean
	 **/
	public function parse ( $request = false ) {
		if ( is_a($request,'WP') ) $request = empty($request->query_vars) ? $_GET : $request->query_vars;
		else $request = $_GET;

		if ( isset($request['src']) ) $this->resources($request);

		if ( defined('WP_ADMIN') ) {
			if ( ! isset($_GET['page']) ) return;
			if ( false === $this->Admin )
				$this->Admin = new ShoppAdmin();

			$this->handler();

		} else $this->handler('Storefront');
	}

	/**
	 * Loads a specified flow controller
	 *
	 * @author Jonathan Davis
	 *
	 * @param string $controller The base name of the controller file
	 * @return void
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
	 * @author Jonathan Davis
	 *
	 * @return boolean
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
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function menu () {
		if ( ! defined('WP_ADMIN') ) return false;
		$this->Admin = new ShoppAdmin;
		$this->Admin->menus();
		do_action('shopp_admin_menu');
	}

	public function ajax () {
		if ( ! isset($_REQUEST['action']) || ! defined('DOING_AJAX') ) return;
		new ShoppAjax;
	}

	public function resources ( $request ) {
		$this->Controller = new ShoppResources( $request );
	}

	/**
	 * Activates the plugin
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function activate () {
		$this->installation();
		do_action('shopp_activate');
	}

	/**
	 * deactivate()
	 * Resets the data_model to prepare for potential upgrades/changes to the table schema */
	public function deactivate() {
		$this->installation();
		do_action('shopp_deactivate');
	}

	public function installation () {
		if ( ! defined('WP_ADMIN') ) return;
		if ( false !== $this->Installer ) return;

		if ( ! $this->Installer )
			$this->Installer = new ShoppInstallation;
	}

	public function update () {
		$this->installation();
		do_action('shopp_autoupdate');
	}

	public function save_settings () {
		if (empty($_POST['settings']) || !is_array($_POST['settings'])) return false;
		foreach ($_POST['settings'] as $setting => $value)
			shopp_set_setting($setting,$value);
		return true;
	}

	// Admin Bar
	public function adminbar ( $wp_admin_bar ) {
		$posttype = get_post_type_object(Product::posttype());
		if (empty( $posttype ) || !current_user_can( $posttype->cap->edit_post )) return;
		$wp_admin_bar->add_menu( array(
			'parent' => 'new-content',
			'id' => 'new-'.Product::posttype(),
			'title' => $posttype->labels->singular_name,
			'href' => admin_url( str_replace('%d','new',$posttype->_edit_link) )
		) );

		$object = get_queried_object();
		if (!empty($object) && isset($object->post_type)
				&& $object->post_type == $posttype->name) {
			$wp_admin_bar->add_menu( array(
				'id' => 'edit',
				'title' => $posttype->labels->edit_item,
				'href' => get_edit_post_link( $object->ID )
			) );
		}

	}

	/**
	 * Displays the welcome screen
	 *
	 * @return boolean
	 * @author Jonathan Davis
	 **/
	public static function welcome () {
		return defined('WP_ADMIN') && shopp_setting_enabled('display_welcome') && empty($_POST['setup']);
	}


} // End class ShoppFlow

/**
 * FlowController
 *
 * Provides a template for flow controllers
 *
 * @since 1.1
 * @package shopp
 * @author Jonathan Davis
 **/
abstract class FlowController  {

	/**
	 * FlowController constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		/* Implemented in concrete classes */
	}


} // END class FlowController

/**
 * AdminController
 *
 * Provides a template for admin controllers
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
abstract class AdminController extends FlowController {

	public $Admin = false;
	public $url;
	public $screen;
	public $page;
	public $pagename;

	protected $tabs = array();
	protected $tab = false;

	protected $notices = array();

	/**
	 * AdminController constructor
	 *
	 * @author Jonathan Davis
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

		$tabs = $this->Admin->tabs($this->page);
		if ( ! empty($tabs) ) {
			foreach ($tabs as $tab)
				$this->addtab($this->Admin->pagename($tab->name), $tab->label);
		}

		ShoppingObject::store('admin_notices', $this->notices);
		add_action('shopp_admin_notices', array($this, 'notices'));

		$this->maintenance();

	}

	public function admin () {
		/* Implemented in the concrete classes */
	}

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

	public function notices () {

		if ( empty($this->notices) ) return;
		$markup = array();
		foreach ( $this->notices as $notice ) {
			$markup[] = '<div class="' . $notice->style . '">';
			$markup[] = '<p>' . $notice->message . '</p>';
			$markup[] = '</div>';
		}
		if ( ! empty($markup) ) echo join('', $markup);
		$this->notices = array(); // Reset output buffer

	}

	private function maintenance () {
		if ( Shopp::maintenance() ) {
			if ( isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'shopp_disable_maintenance') ) {
				shopp_set_setting('maintenance', 'off');
			} else {
				$url = wp_nonce_url(add_query_arg('page', $this->Admin->pagename('setup-pages'), admin_url('admin.php')), 'shopp_disable_maintenance');
				$this->notice(Shopp::__('Shopp is currently in maintenance mode. %sDisable Maintenance Mode%s', '<a href="' . $url . '" class="button">', '</a>'), 'error', 1);
			}
		}
	}

	static function url ( $args = array() ) {
		$args = array_map('esc_attr',$args);
		return add_query_arg( array_merge($args,array('page'=>esc_attr($_GET['page'])) ),admin_url('admin.php'));
	}

	protected function addtab ( string $pagehook, $title ) {
		$this->tabs[ $pagehook ] = $title;
	}

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

	protected function ui ( string $file ) {
		$path = join('/', array(SHOPP_ADMIN_PATH, $this->ui, $file));
		if ( is_readable($path) )
			return $path;

		$this->notice(Shopp::__('The requested setting screen was not found.'),'error');
		echo '<div class="wrap shopp"><div class="icon32"></div><h2>Ooops.</h2></div>';
		do_action('shopp_admin_notices');
		return false;
	}

}

/**
 * Helper to access the Shopp Storefront contoller
 *
 * @author Jonathan Davis
 * @since 1.1.5
 *
 * @return Storefront|false
 **/
function &ShoppStorefront () {
	$false = false;
	$Shopp = Shopp::object();
	if ( ! isset($Shopp->Flow) || ! is_object($Shopp->Flow->Controller) ) return $false;
	if ( get_class($Shopp->Flow->Controller) != 'Storefront' ) return $false;
	return $Shopp->Flow->Controller;
}

function &ShoppAdmin() {
	$false = false;
	$Shopp = Shopp::object();
	if ( ! isset($Shopp->Flow) || ! isset($Shopp->Flow->Admin) || empty($Shopp->Flow->Admin) ) return $false;
	return $Shopp->Flow->Admin;
}

add_filter('shopp_update_key','shopp_keybind');
add_filter('shopp_update_key','base64_encode');
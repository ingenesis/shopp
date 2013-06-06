<?php
/**
 * ShoppFlow
 *
 * Super controller for handling low level request processing
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, January 6, 2010
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
	function __construct () {

		// Plugin activation & deactivation
		register_deactivation_hook( SHOPP_PLUGINFILE, array($this, 'deactivate') );
		register_activation_hook( SHOPP_PLUGINFILE, array($this, 'activate') );

		// Handle AJAX requests
		add_action( 'admin_init', array($this,'ajax') );

		// Boot up the menus & admin bar
		add_action( 'admin_menu', array($this,'menu') );
		add_action( 'admin_bar_menu', array($this, 'adminbar'), 50 );

		// Handle automatic updates
		add_action('update-custom_shopp',array($this,'update'));

		// Parse the request
		if ( defined('WP_ADMIN') ) add_action( 'current_screen', array($this,'parse') );
		else add_action( 'parse_request', array($this,'parse') );
	}

	/**
	 * Parses requests and hands off processing to specific subcontrollers
	 *
	 * @author Jonathan Davis
	 *
	 * @return boolean
	 **/
	function parse ( $request = false ) {
		if ( is_a($request,'WP') ) $request = empty($request->query_vars) ? $_GET : $request->query_vars;
		else $request = $_GET;

		if ( isset($request['src']) ) $this->resources($request);

		if ( defined('WP_ADMIN') ) {
			if ( ! isset($_GET['page']) ) return;
			if ( false === $this->Admin) {
				require(SHOPP_FLOW_PATH.'/Admin.php');
				$this->Admin = new ShoppAdmin();
			}
			$controller = $this->Admin->controller(strtolower($request['page']));

			if (!empty($controller)) $this->handler($controller);
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
	function handler ($controller) {
		if ( ! $controller ) return false;
		if ( is_a($this->Controller,$controller) ) return true; // Already initialized
		if ( ! class_exists($controller) ) require(SHOPP_FLOW_PATH."/$controller.php");

		$this->Controller = new $controller();
		do_action('shopp_'.strtolower($controller).'_init');
		return true;
	}

	/**
	 * Initializes the Admin controller
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	function admin () {
		if ( ! defined('WP_ADMIN') ) return false;
		$controller = $this->Admin->controller($_GET['page']);
		$this->handler($controller);
		$this->Controller->admin();
		return true;
	}

	/**
	 * Defines the Shopp admin page and menu structure
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	function menu () {
		if ( ! defined('WP_ADMIN') ) return false;
		$this->Admin = new ShoppAdmin;
		$this->Admin->menus();
	}

	function ajax () {
		if ( ! isset($_REQUEST['action']) || ! defined('DOING_AJAX') ) return;
		$this->Ajax = new ShoppAjax;
	}

	function resources ($request) {
		$this->Controller = new ShoppResources($request);
	}

	/**
	 * Activates the plugin
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function activate () {
		$this->installation();
		do_action('shopp_activate');
	}

	/**
	 * deactivate()
	 * Resets the data_model to prepare for potential upgrades/changes to the table schema */
	function deactivate() {
		$this->installation();
		do_action('shopp_deactivate');
	}

	function installation () {
		if ( ! defined('WP_ADMIN') ) return;
		if ( false !== $this->Installer ) return;

		if ( ! $this->Installer )
			$this->Installer = new ShoppInstallation();
	}

	function update () {
		$this->installation();
		do_action('shopp_autoupdate');
	}

	function save_settings () {
		if (empty($_POST['settings']) || !is_array($_POST['settings'])) return false;
		foreach ($_POST['settings'] as $setting => $value)
			shopp_set_setting($setting,$value);
		return true;
	}

	// Admin Bar
	function adminbar ( $wp_admin_bar ) {
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

} // End class Flow

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
	function __construct () {
		// if (defined('WP_ADMIN')) {
		// 	add_action('admin_init',array(&$this,'settings'));
		// 	$this->settings();
		// } else add_action('shopp_loaded',array(&$this,'settings'));
	}

	// function settings () {
	// 	ShoppSettings();
	// }

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

	private $notices = array();

	/**
	 * AdminController constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function __construct () {
		// parent::__construct();
		$Shopp = Shopp::object();
		if (!empty($Shopp->Flow->Admin)) $this->Admin = &$Shopp->Flow->Admin;
		$screen = get_current_screen();

		$this->screen = $screen->id;

		$this->url = add_query_arg(array('page'=>esc_attr($_GET['page'])),admin_url('admin.php'));

		add_action('shopp_admin_notices',array($this,'notices'));
	}

	function notice ( $message, $style='updated', $priority = 10 ) {
		$notice = new StdClass();
		$notice->message = $message;
		$notice->style = $style;
		array_splice($this->notices,$priority,0,array($notice));
	}

	function notices () {
		if (empty($this->notices)) return;
		$markup = array();
		foreach ($this->notices as $notice) {
			$markup[] = '<div class="'.$notice->style.' below-h2">';
			$markup[] = '<p>'.$notice->message.'</p>';
			$markup[] = '</div>';
		}
		if ( ! empty($markup) ) echo join('',$markup);
		$this->notices = array(); // Reset output buffer
	}

	static function url ( $args = array() ) {
		$args = array_map('esc_attr',$args);
		return add_query_arg( array_merge($args,array('page'=>esc_attr($_GET['page'])) ),admin_url('admin.php'));
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
	$Shopp = Shopp::object();
	if (!isset($Shopp->Flow) || !is_object($Shopp->Flow->Controller)) return false;
	if (get_class($Shopp->Flow->Controller) != "Storefront") return false;
	return $Shopp->Flow->Controller;
}

function &ShoppAdmin() {
	$Shopp = Shopp::object();
	if ( ! isset($Shopp->Flow) || ! isset($Shopp->Flow->Admin) || empty($Shopp->Flow->Admin) ) return false;
	return $Shopp->Flow->Admin;
}

add_filter('shopp_update_key','shopp_keybind');
add_filter('shopp_update_key','base64_encode');
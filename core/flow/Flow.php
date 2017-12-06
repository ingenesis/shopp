<?php
/**
 * Flow.php
 *
 * Shopp application domain router
 *
 * @copyright Ingenesis Limited, January 2010-2015
 * @version 1.4
 * @package Shopp\Flow
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * The super controller that does intial request routing to the handling controller
 *
 * @since 1.1
 * @package Shopp\Flow
 **/
final class ShoppFlow extends ShoppFlowController {

	/** @var ShoppFlowController $Controller The current flow controller instance */
	private $Controller = false; // @todo make this private

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

		// Handle AJAX & download requests as quickly as possible
		$this->resources();

		// Boot up the menus & admin bar
		add_action( 'admin_menu', array($this, 'menu'), 50 );
		add_action( 'admin_bar_menu', array($this, 'adminbar'), 50 );

		add_action( 'parse_request', array($this, 'parse') );

		// add_action( 'admin_init', array($this, 'upgrades') );

	}

	public function query ( $request = false ) {

		if ( is_a($request,'WP') )
			$request = empty($request->query_vars) ? $_GET : $request->query_vars;
		else $request = $_GET;

		$this->request = ShoppRequestProcessing::process($request, $this->defaults);

	}

	public function resources () {

		$this->query();

		$controller = null;
		// Check for src (Shopp Resource) requests
		if ( defined('DOING_AJAX') )
			$controller = 'ShoppAjax';
		elseif ( isset($this->request['src']) )
			$controller = 'ShoppResources';
		elseif ( is_admin() )
			$controller = 'ShoppAdmin';
		else apply_filters('shopp_flow_controller', false);

		$this->controller($controller);

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

		$this->query($request);
		$this->controller('ShoppStorefront');

	}

	/**
	 * Get the current domain controller or setup a new domain controller
	 *
	 * @since 1.4
	 *
	 * @param string $ControllerClass (optional) The class name to setup a new domain controller
	 * @return ShoppFlowController|boolean The current domain flow controller or false otherwise
	 **/
	public function controller ( $ControllerClass = null ) {
		if ( empty($ControllerClass) ) return $this->Controller;

		$this->Controller = new $ControllerClass( $this->request );
		do_action('shopp_' . sanitize_key($ControllerClass) . '_init');

		return $this->Controller;
	}

	/**
	 * Defines the Shopp admin page and menu structure
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function menu () {
		if ( ! is_admin() ) return;

		$Pages = ShoppAdminPages();
		do_action('shopp_admin_menu');
		$Pages->menus();

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
abstract class ShoppFlowController extends ShoppRequestFormFramework {

	/**
	 * ShoppFlowController constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		$this->query();
	}

} // END class ShoppFlowController

function ShoppFlow () {
	$Shopp = Shopp::object();
	if ( isset($Shopp->Flow) ) return $Shopp->Flow;
	else return false;
}

function ShoppFlowController () {
	if ( ! ( $Flow = ShoppFlow() ) || ! $Flow->controller() ) return false;
	return $Flow->controller();
}

/**
 * Helper to access the Shopp Storefront contoller
 *
 * @since 1.1.5
 *
 * @return ShoppStorefront|false
 **/
function ShoppStorefront () {
	$Controller = ShoppFlowController();
	if ( ! $Controller || 'ShoppStorefront' != get_class($Controller) ) return false;
	return $Controller;
}

/**
 * Provides the Admin controller instance
 *
 * @since 1.2
 *
 * @return ShoppAdminController|bool The ShoppAdmin super-controller instance or false
 **/
function ShoppAdmin () {
	$Controller = ShoppFlowController();
	if ( ! $Controller || 'ShoppAdmin' != get_class($Controller) ) return false;
	return $Controller;
}

function ShoppAdminPages () {
	return ShoppAdminPages::object();
}
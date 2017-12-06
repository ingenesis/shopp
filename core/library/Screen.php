<?php
/**
 * Admin.php
 *
 * Abstract admin screen controller
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Controllers/Screen
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppAdminController
 *
 * Provides a template for admin controllers
 *
 * @since 1.1
 * @package Shopp\Flow
 **/
abstract class ShoppScreenController extends ShoppRequestFormFramework {

	/** @var string The current page of the screen pagination */
	public $id = false;

	/** @var string The current page of the screen pagination */
	protected $page = false;

	/** @var string The current screen page name */
	protected $pagename = false;

	protected $nonce = false;

	/** @var string The URL for this admin screen */
	protected $ui = false;

	/** @var array Registry of notices to display for this screen */
	protected $notices = array();

	/** @var array The request values for this screen */
	protected $request = array(
		'page' => '',
		'id' => false
	);

	/** @var ShoppAdminTable A ShoppAdminTable object */
	protected $Table = false;

	/** @var Object The object model for the screen */
	protected $Model = false;

	/**
	 * ShoppAdminController constructor
	 *
	 * @since 1.1
	 *
	 * @param string $ui The directory path to the UI templates
	 * @param array $request The screen request parameters
	 * @return void
	 **/
	public function __construct ( $ui ) {
		global $plugin_page;

		// Setup helper properties
		$this->ui = $ui;
		$this->id = ShoppAdmin::screen();
		$this->pagename = $plugin_page;

		// Setup notices before actions and process ops
		Shopping::restore('admin_notices', $this->notices);
		add_action('shopp_admin_notices', array($this, 'notices'));

		// Parse query request
		if ( $this->query() ) {
			// Flag new model requests
			if ( 'new' == $this->request('id') )
				$this->request['new'] = true;

			$this->handlers('actions', (array)$this->actions());
			do_action('shopp_admin_' . $this->slug() . '_actions');
		}

		// Setup the working object model
		$this->Model = $this->process( $this->load() );

	}

	/**
	 * Provides the page slug for this screen
	 *
	 * @since 1.4
	 * @return string The page slug
	 **/
	public function slug () {
		return substr($this->pagename, strrpos($this->pagename, '-') + 1);
	}

	/**
	 * Prototype load method to load the data model for the screen
	 *
	 * @since 1.4
	 * @return Object Some object data to pass back to the screen
	 **/
	public function load () {
		/** Optionally implemented in the concrete class **/
	}

	/**
	 * Used to register actions this screen handles
	 *
	 * Providing a hook name
	 *
	 * @since 1.4
	 * @return array A list of user request action hook names
	 **/
	public function actions () {
		/** Optionally implemented in the concrete class **/
		return array();
	}

	/**
	 * Used to handle operations on the loaded data model
	 *
	 * @since 1.4
	 * @return array A list of operations to perform
	 **/
	public function ops () {
		/** Optionally implemented in the concrete class **/
		return array();
	}

	/**
	 * Layout method for user interface setup
	 *
	 * This is normally used for registering table column names, or meta boxes
	 * or other references used in the user interface.
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function layout () {
		/** Optionally implement in concrete classes **/
	}

	/**
	 * Displays the interface
	 *
	 * This is the main event. It includes a UI template for display.
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function screen () {
		include $this->ui($this->slug() . '.php');
	}

	/**
	 * Dynamically includes necessary JavaScript and stylesheets for the screen
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets () {
		do_action('shopp_' . $this->slug(). '_admin_scripts');
	}

	/**
	 * Adds contextually appropriate help information to interfaces
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function help () {

		$file = join('/', array(SHOPP_ADMIN_PATH, 'help', $this->slug() . '.php'));
		if ( file_exists($file) )
			return include $file;

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
	 * Create or get the admin table for this screen
	 *
	 * @since 1.4
	 * @param string $classname The class name for the admin table renderer
	 * @return ShoppAdminTable|bool The ShoppAdminTable object or false
	 **/
	protected function table ( $classname = null ) {
		if ( isset($classname) ) {
			$classname = apply_filters('shopp_screen_' . $this->slug() . '_table_class', $classname);
			if ( class_exists($classname) )
				$this->Table = new $classname(array('screen' => get_current_screen()));
		}

		return $this->Table;
	}

	/**
	 * Process posted form data and/or request action operations
	 *
	 * @since 1.4
	 * @param Object $Object The model object for this screen
	 * @return Object The model object for this screen
	 **/
	protected function process ( $Object ) {
		if ( ! $this->posted() ) return $Object; // Passthru if no data is submitted

		if ( ! empty($this->nonce) )
			check_admin_referer($this->nonce);   // Validate the ops nonce

		$this->handlers('ops', (array)$this->ops()); // Setup the implementation screen's operations callbacks

		// Run the implemented screen's processing filters
		$Object = apply_filters('shopp_admin_' . $this->slug() . '_ops', $Object);

		// Automatic ShoppDatabaseObject save (override with a custom ShoppScreenController::save() method)
		if ( method_exists($this, 'save') )
			$this->save($Object);
		elseif ( method_exists($Object, 'save') )
			$Object->save();

		return $Object;
	}

	/**
	 * Renders screen tabs from a given associative array
	 *
	 * The tab array uses a tab page slug as the key and the
	 * localized title as the value.
	 *
	 * @since 1.3
	 * @param array $tabs The tab map array
	 * @return void
	 **/
	protected function tabs () {

		$pagehook = sanitize_key($this->pagename);

		$markup = array();
		if ( empty($tabs) ) $tabs = $this->tabs;
		$default = key($this->tabs);

		foreach ( $tabs as $tab => $title ) {
			$classes = array('nav-tab');
			if ( ( ! isset($this->tabs[ $this->pagename ]) && $default == $tab) || $this->pagename == $tab )
				$classes[] = 'nav-tab-active';
			$markup[] = '<a href="' . add_query_arg(array('page' => $tab), admin_url('admin.php')) . '" class="' . join(' ', $classes) . '">' . $title . '</a>';
		}

		echo '<h2 class="nav-tab-wrapper">' . join('', apply_filters('shopp_admin_' . $pagehook . '_screen_tabs', $markup)) . '</h2>';

	}

	/**
	 * Generates the full URL for the current admin screen
	 *
	 * @since 1.3
	 * @param array $params (optional) The parameters to include in the URL
	 * @return string The generated URL with parameters
	 **/
	public function url ( $params = array() ) {
		$params['_wpnonce'] = $params['_wp_http_referer'] = false;
		$params = array_merge($this->request, $params);

		return add_query_arg($params, admin_url('admin.php'));
	}

	/**
	 * Generate a nonce field, nonce URL or get the current nonce name
	 *
	 * @since 1.4
	 * @param string $nonce The nonce type ('field', 'url') or the nonce name
	 * @param string $url (optional) The URL to nonce
	 * @return string|void The nonce URL, nonce name or void when genrating the nonce field
	 **/
	public function nonce ( $nonce, $url = null ) {
		if ( empty($this->nonce) )
			$this->nonce = $this->id;

		if ( 'field' == $nonce ) {
			wp_nonce_field($this->nonce);
		} elseif ( 'url' == $nonce ) {
			$url = isset($url) ? $url : $this->url();
			return wp_nonce_url($url, $this->nonce);
		} elseif ( ! empty($nonce) ) {
			$this->nonce = $nonce;
		} else return $this->nonce;
	}

	/**
	 * Helper to load a UI view template
	 *
	 * Used with `include` statements so that any local variables
	 * are still in scope when the template is included.
	 *
	 * @since 1.3
	 * @param string $file The file to include
	 * @return string|bool The file path or false if not found
	 **/
	protected function ui ( $file ) {
		$path = join('/', array(SHOPP_ADMIN_PATH, $this->ui, $file));
		if ( is_readable($path) )
			return $path;

		echo '<div class="wrap shopp"><div class="icon32"></div><h2></h2></div>';
		$this->notice(Shopp::__('The requested screen was not found.'), 'error');
		do_action('shopp_admin_notices');
		return false;
	}

	/**
	 * Registers callback handlers for actions or ops
	 *
	 * @since 1.4
	 * @return void
	 **/
	private function handlers ( $action, array $methods = array() ) {
		if ( empty($methods) ) return;
		if ( ! in_array($action, array('actions', 'ops')) ) return;
		foreach ( $methods as $method ) {
			if ( is_callable(array($this, $method)) )
			add_action('shopp_admin_' . $this->slug() . '_' . $action, array($this, $method));
		}
	}

}
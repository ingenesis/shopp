<?php
/**
 * API.php
 *
 * Shopp's Application Programming Interface library manager
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May 12, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

interface ShoppAPI {
	public static function _apicontext(); // returns the correct contextual object, if possible
}

final class ShoppDeveloperAPI {

	static $core = array(
		'core', 'theme', 'script',
		'admin', 'asset', 'cart', 'collection',
		'customer', 'meta', 'order', 'product',
		'settings'
	);

	// Load public development API
	public static function load ( $basepath, $load = array() ) {
		$path = realpath("$basepath/api");

		$custom = apply_filters('shopp_developerapi_files',array());

		// Add custom Developer API files to core
		$files = array_merge(self::$core,$custom);

		// Make sure requested APIs exist
		$apis = array_intersect($files,$load);

		// If requested APIs are empty, use defaults instead
		if ( empty($apis) ) $apis = $files;

		foreach ( $apis as $api ) {
			if ( false === strpos($api,'.php') )
				require "$path/$api.php";
			else include $api;
		}
	}

}

/**
 * ShoppAPILoader
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class ShoppAPIModules extends ModuleLoader {

	protected $loader = 'ShoppAPIFile';

	protected $interface = 'ShoppAPI';
	protected $paths = array(SHOPP_THEME_APIS, SHOPP_ADDONS);

	/**
	 * API constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct () {

		$this->installed(); // Find modules
		$this->load(true);  // Load all

		add_action('shopp_init', __CLASS__ . '::functions');
	}

	/**
	 * Loads the theme templates `shopp/functions.php` if present
	 *
	 * If theme content templates are enabled, checks for and includes a functions.php file (if present).
	 * This allows developers to add Shopp-specific presentation logic with the added convenience of knowing
	 * that shopp_init has run.
	 *
	 * @author Barry Hughes
	 * @since 1.3
	 *
	 * @return void
	 **/
	public static function functions () {
		if ( ! Shopp::str_true( shopp_setting( 'theme_templates' ) ) ) return;
		Shopp::locate_template( array( 'functions.php' ), true );
	}

} // END class ShoppAPILoader

class ShoppAPIFile extends ModuleFile {

	public function load () {
		require $this->file;
		add_action( 'shopp_init', array($this, 'register') );
	}

	public function register () {
		// Hook _context
		$api = $this->classname;
		$apicontext = method_exists($api,'_apicontext') ? call_user_func(array($api, '_apicontext')) : false;

		$setobject_call = method_exists($api,'_setobject') ? array($api, '_setobject') : array($this, 'setobject');
		add_filter('shopp_themeapi_object', $setobject_call, 10, 3);

		// Define a static $map property as an associative array or tag => member function names.
		// Without the tag key, it will be registered as a general purpose filter for all tags in this context
		$register = get_class_property($api, 'register');
		if ( ! empty($register) ) {
			foreach ( $register as $tag => $method ) {
				$apiclass = $api;

				if ( is_array($method) ) {
					$apiclass = $method[0];
					$method = $method[1];
				} elseif ( is_string($method) && strpos($method, '::') !== false )
					list($apiclass, $method) = explode('::', $method);

 				if ( is_callable( array($apiclass, $method) ) ) {
					if ( is_numeric($tag) ) add_filter( 'shopp_themeapi_'.strtolower($apicontext), array($apiclass, $method), 9, 4 ); // general filter
					else add_filter( 'shopp_themeapi_'.strtolower($apicontext.'_'.$tag), array($apiclass, $method), 9, 3 );
				}
			}
			return;
		}

		// Otherwise, the register function will assume that all method names (excluding _ prefixed methods) correspond to tag you want.
		// _ prefix members can be used as helper functions
		$methods = array_filter( get_class_methods ($api), function ( $m ) {
			return ( "_" != $m{0} );
		} );
		foreach ( $methods as $tag )
			add_filter( 'shopp_themeapi_'.strtolower($apicontext.'_'.$tag), array($api, $tag), 9, 3 );

	}

	public function setobject ($Object, $context) {
		if ( is_object($Object) ) return $Object;  // always use if first argument is an object

		$api = $this->classname;
		$apicontext = method_exists($api,'_apicontext') ? call_user_func(array($api, '_apicontext')) : false;

		if (strtolower($context) != strtolower($apicontext)) return $Object; // do nothing

		$Shopp = Shopp::object();
		$property = ucfirst($apicontext);
		if (property_exists($Shopp,$property))
			return $Shopp->{$property};

		return false;
	}

}

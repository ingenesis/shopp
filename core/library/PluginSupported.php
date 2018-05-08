<?php
/**
 * PluginSupported.php
 *
 * Determines if the server environment supports the Shopp plugin.
 *
 * @copyright Ingenesis Limited, May 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Core
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPluginSupported {

	/** @var string PHP_VERSION_REQUIRED The minimum PHP version required to work with Shopp's codebase */
	const PHP_VERSION_REQUIRED = '5.2.4';

	/** @var string WP_VERSION_REQUIRED The minimum WP version required to work with Shopp's codebase */
	const WP_VERSION_REQUIRED = '3.5';

	/** @var array $checks A list of check methods to verify the server environment will support Shopp */
	protected $checks = array('check_php', 'check_wp', 'check_gd');

	/** @var array $notices A list of messages to tell the user about any incompatibilities */
	protected $notices = array();

	/** @var string $basepath The base path of the plugin */
	protected $basepath = false;

	/**
	 * Constructor
	 *
	 * Loads the localization text domain in case there are messages to give to the user.
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	public function __construct() {
		$this->basepath = dirname(ShoppLoader::basepath());

		// Manually load text domain for translated activation errors
		load_plugin_textdomain('Shopp', false, "$this->basepath/lang");
	}

	/**
	 * Runs all of the registered check methods
	 *
	 * @since 1.5
	 *
	 * @return boolean True if Shopp is supported, false otherwise
	 **/
	public function supported() {
		$pass = true;
		foreach ( $this->checks as $check ) {
			if ( method_exists(__CLASS__, $check) )
				$pass = $pass && $this->$check();
		}
		return $pass;
	}

	/**
	 * Register or retrieve a named notice
	 *
	 * Notices are messages given to the user in the form of an error screen
	 * or in the server log files.
	 *
	 * @since 1.5
	 *
	 * @return string A string of the message, or boolean true when a named message is registered
	 **/
	public function notice( $name, $message = null ) {
		if ( ! is_null($message) )
			return true && $this->notices[ $name ] = $message;

		if ( isset($this->notices[ $name ]) )
			return $this->notices[ $name ];
		return '';
	}

	/**
	 * Show the error messaging that includes any incompatibility notices
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	public function messaging() {
		$errors = array_keys($this->notices);

		ob_start();
		isset($errors);
		include $this->ui('unsupported.php');
		$error = ob_get_clean();

		wp_die($error);
	}

	/**
	 * Trigger PHP errors for incompatibility notices
	 *
	 * Sends the errors to the server log file too.
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	public function log() {
		if ( ! empty($this->notices) )
			foreach ( $this->notices as $notice )
				trigger_error($notice, E_USER_WARNING);
	}

	/**
	 * Forces deactivation of the Shopp plugin
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	public function force_deactivate() {
		if ( ! function_exists('deactivate_plugins') )
			require( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin = dirname(ShoppLoader::basepath()) . "/Shopp.php";
		deactivate_plugins($plugin, true);
	}

	/**
	 * Detects if the user is currently activating a plugin
	 *
	 * @since 1.5
	 *
	 * @return boolean True if activating, false otherwise
	 **/
	public function activating() {
		$activation = $plugin = false;
		$request = filter_input_array(INPUT_GET, array(
			'action' => FILTER_REQUIRE_SCALAR,
			'plugin' => FILTER_REQUIRE_SCALAR
		));

		if ( isset($request['action'], $request['plugin']) )
			$activation = 'activate' == $request['action'];

		if ( ! $activation )
			return false;

		$plugin = $request['plugin'];
		if ( function_exists('check_admin_referer') )
			check_admin_referer('activate-plugin_' . $plugin);

		return true;
	}

	/**
	 * Checks if the PHP version of the server meets the minimum requirements
	 *
	 * @since 1.5
	 *
	 * @return boolean True if the server PHP version satisfies the requirements, false otherwise
	 **/
	protected function check_php() {
		if ( version_compare(PHP_VERSION, self::PHP_VERSION_REQUIRED, '<') ) {
			$this->notice('phpversion', sprintf(Shopp::_x('Your server is running PHP %s!', 'Shopp activation error'), PHP_VERSION));
			$this->notice('phprequired', sprintf(Shopp::_x('Shopp requires PHP %s+.', 'Shopp activation error'), self::PHP_VERSION_REQUIRED));
			return false;
		}
		return true;
	}

	/**
	 * Checks if the WordPress version meets the minimum requirements
	 *
	 * @since 1.5
	 *
	 * @return boolean True if the WordPress version satisfies the requirements, false otherwise
	 **/
	protected function check_wp() {
		if ( version_compare(get_bloginfo('version'), self::WP_VERSION_REQUIRED, '<') ) {
			$this->notice('wpversion', sprintf(Shopp::_x('This site is running WordPress %s!', 'Shopp activation error'), get_bloginfo('version')));
			$this->notice('wprequired', sprintf(Shopp::_x('Shopp requires WordPress 3.5.', 'Shopp activation error'), self::WP_VERSION_REQUIRED));
			return false;
		}
		return true;
	}

	/**
	 * Checks for basic GD library support
	 *
	 * @since 1.5
	 *
	 * @return boolean True if the necessary GD library functionality is available, false otherwise
	 **/
	protected function check_gd() {
		if ( ! function_exists('gd_info') )
			return false && $this->notice('gdsupport', Shopp::_x('Your server does not have GD support! Shopp requires the GD image library with JPEG support for generating gallery and thumbnail images.', 'Shopp activation error'));
		elseif ( ! array_keys( gd_info(), array('JPG Support', 'JPEG Support')) )
			return false && $this->notice('jpgsupport', Shopp::_x('Your server does not have JPEG support for the GD library! Shopp requires JPEG support in the GD image library to generate JPEG images.', 'Shopp activation error'));
		return true;
	}

	/**
	 * Helper to load a UI view template
	 *
	 * Used with `include` statements so that any local variables
	 * are still in scope when the template is included.
	 *
	 * @since 1.5
	 * @param string $file The file to include
	 * @return string|bool The file path or false if not found
	 **/
	protected function ui ( $file ) {
		$path = join('/', array($this->basepath, 'core/ui/help', $file));

		if ( is_readable($path) )
			return $path;

		return false;
	}

}
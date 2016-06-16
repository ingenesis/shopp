<?php
/**
 * Loginname.php
 *
 * Generates login names
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, November 2013
 * @license (@see license.txt)
 * @package
 * @since 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppLoginGenerator {

	private static $object;
	private static $generators = array();

	private function __construct () {
		$methods = get_class_methods(__CLASS__);
		$ignore = array('__construct', 'object', 'register', 'name');
		foreach ( $methods as $method ) {
			if ( in_array($method, $ignore) ) continue;
			self::register(array(__CLASS__, $method));
		}
	}

	public static function object () {
		if ( ! self::$object instanceof self ) {
			self::$object = new self;
			do_action('shopp_loginname_generators');
		}
		return self::$object;
	}

	public static function name () {
		$name = false;

		$methods = array();
		foreach ( self::$generators as $priority => $callbacks )
			$methods = array_merge($methods, $callbacks);

		foreach ( $methods as $callback ) {
			$handle = strtolower( (string) call_user_func($callback) );
			if ( username_exists($handle) ) continue;

			return $handle;
		}

		return false;
	}

	public static function register ( $callback, $priority = 10 ) {
		if ( is_callable($callback) ) {
			if ( ! isset(self::$generators[ $priority ]) ) {
				self::$generators[ $priority ] = array();
				ksort(self::$generators);
			}
			self::$generators[ $priority ][] = $callback;
		}
	}

	public static function email_handle () {
		list($handle, ) = explode('@', $_POST['email']);
		return $handle;
	}

	public static function email_name () {
		return $_POST['email'];
	}

	public static function firstname_lastinitial () {
		return $_POST['firstname'] . substr($_POST['lastname'], 0, 1);
	}

	public static function firstinitial_lastname () {
		return substr($_POST['firstname'], 0, 1) . $_POST['lastname'];
	}

	public static function email_handle_randnum () {
		return self::email_handle() . rand(1000, 9999);
	}

}
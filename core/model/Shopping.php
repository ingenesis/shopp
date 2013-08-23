<?php
/**
 * Shopping
 *
 * Flow controller for the customer shopping experience
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January 6, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.1
 * @subpackage shopping
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Shopping class
 *
 * The Shopping class is a specific implementation of a SessionObject that
 * provides automated session storage of data of any kind.  Data must be
 * registered to the Shopping class by statically calling the
 * ShoppingObject::store method to be stored in and loaded from the
 * session.
 *
 * Storing objects requires the use of the ShoppingObject helper class in
 * order to maintain initialized instances {@see ShoppingObject}
 *
 * @author Jonathan Davis
 * @package shopp
 * @since 1.1
 **/
class Shopping extends SessionObject {

	private static $object;

	/**
	 * Shopping constructor
	 *
	 * @author Jonathan Davis
	 * @todo Change table to 'shopping' and update schema
	 *
	 * @return void
	 **/
	public function __construct () {
		// Set the database table to use
		$this->_table = DatabaseObject::tablename('shopping');

		// Initialize the session handlers
		parent::__construct();

		// Queue the session to start
		// prioritize really early (before errors priority 5)
		add_action('init',array($this,'init'),2);
	}

	/**
	 * The singleton access method
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return Shopping
	 **/
	public static function object () {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

	/**
	 * Starts the session
	 *
	 * Initializes the session if not already started
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function init () {
		@session_start();
		add_action('shopp_cart_updated',array($this,'savecart'));
	}

	/**
	 * Resets the entire session
	 *
	 * Generates a new session ID and reassigns the current session
	 * to the new ID, then wipes out the Cart contents.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean
	 **/
	function reset () {
		session_regenerate_id();
		$this->session = session_id();
		session_write_close();
		do_action('shopp_session_reset');
		return true;
	}

	/**
	 * Reset the shopping session
	 *
	 * Controls the cart to allocate a new session ID and transparently
	 * move existing session data to the new session ID.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean True on success
	 **/
	static function resession ( $session = false ) {

		do_action('shopp_pre_resession', $session);

		$Shopping = ShoppShopping();

		// commit current session
		session_write_close();
		$Shopping->handling(); // Workaround for PHP 5.2 bug #32330

		if ($session) { // loading session
			session_id($session); // session_id while session is closed
			$Shopping->session = session_id(); // Get the new session assignment
			$Shopping->init();
			return true;
		}

		$Shopping->init();
		session_regenerate_id(); // Generate new ID while session is started

		// Ensure we have the newest session ID
		$Shopping->session = session_id();

		// Commit the session and restart
		session_write_close();
		$Shopping->handling(); // Workaround for PHP 5.2 bug #32330
		$Shopping->init();

		do_action('shopp_reset_session'); // @deprecated do_action('shopp_reset_session')
		do_action('shopp_resession');
		return true;
	}

	function clean ( $lifetime = false ) {
		if ( empty($this->session) ) return false;

		$timeout = SHOPP_SESSION_TIMEOUT;
		$expired = SHOPP_CART_EXPIRES;
		$now = current_time('mysql');

		$meta_table = DatabaseObject::tablename('meta');

		DB::query("INSERT INTO $meta_table (context,type,name,value,created,modified)
					SELECT 'shopping','session',session,data,created,modified FROM $this->_table WHERE $timeout < UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(modified) AND stash=1");

		DB::query("DELETE LOW_PRIORITY FROM $meta_table WHERE context='shopping' AND type='session' AND $expired < UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(modified)");

		return parent::clean($lifetime);

	}

	function stashing ( $stashing = null ) {
		if ( is_bool($stashing) ) $this->stash = $stashing ? 1 : 0;
		return ( 1 === $this->stash );
	}

	function savecart ( $Cart ) {
		$this->stashing( ! empty($Cart->contents) );
	}

} // END class Shopping

/**
 * ShoppingObject class
 *
 * A helper class that uses a Factory-like approach in instantiating objects
 * ensuring that the correct instantiation of the object is always provided.
 * When planning to store an entire object in the session, the object must
 * be initialized by calling the __new method of the ShoppingObject and
 * providing the class name as the only argument:
 *
 * $object = &ShoppingObject::__new('ObjectClass');
 *
 * The ShoppingObject then determines if the object has already been
 * initialized from a previous session, or if a new instance is required
 * returning a reference to the instance object.
 *
 * NOTE: It is important to realize that any ShoppingObject-instantiated
 * objects that use action hooks will need to re-establish those action
 * hooks after the session is reloaded because the unserialized instance of
 * the object will lose its hook callbacks.  This can be done by defining
 * a new method for initalizing all the applicable action listeners, then
 * calling that method both in the object constructor and using the __wakeup
 * magic method.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ShoppingObject {

	static function &__new ($class, &$ref=false) {
		$Shopping = ShoppShopping();

		if ( is_object($ref) && method_exists($ref, '__destruct') ) $ref->__destruct();

		if (isset($Shopping->data->{$class})) { // Restore the object
			$object = $Shopping->data->{$class};
		} else {
			$object = new $class();					// Create a new object
			$Shopping->data->{$class} = &$object; // Register storage
		}

		return $object;
	}

	/**
	 * Handles data to be stored in the shopping session
	 *
	 * Registers non-object data to be stored in the session and restores the
	 * data when the property exists (was loaded) from the session data.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $property Property name to use
	 * @param object $data The data to store
	 * @return void
	 **/
	static function store ($property, &$data) {
		$Shopping = ShoppShopping();
		if (isset($Shopping->data->{$property}))	// Restore the data
			$data = $Shopping->data->{$property};

		$Shopping->data->{$property} = &$data;	// Keep a reference
	}

}

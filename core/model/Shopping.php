<?php
/**
 * Shopping
 *
 * Shopp session management
 *
 * @copyright Ingenesis Limited, January 2010-2014
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package Shopp\Session
 * @version 1.0
 * @since 1.1
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides a Shopp-integrated session implementation of the ShoppSessionFramework
 *
 * The Shopping class is a specific implementation of the ShoppSessionFramework that
 * provides automated session storage of data of any kind. Data must be registered
 * to the Shopping class by statically calling the Shopping::restore method to be
 * stored in and loaded from the session.
 *
 * Storing functional objects requires the use of the Shopping::restart method in
 * order to maintain initialized instances.
 *
 * @since 1.1
 **/
class Shopping extends ShoppSessionFramework {

	/** @var Shopping $object The Shopping instance */
	private static $object;

	/**
	 * Shopping constructor
	 *
	 * @return void
	 **/
	public function __construct () {
		// Set the database table to use
		$this->_table = ShoppDatabaseObject::tablename('shopping');

		if ( Shopp::is_robot() ) return;

		// Initialize the session handlers
		parent::__construct();

		add_action('shopp_init', array($this, 'unlock'), 100);
		add_action('shopp_cart_updated', array($this, 'savecart'));
	}

	/**
	 * The singleton access method
	 *
	 * @since 1.3
	 *
	 * @return Shopping The Shopping object instance
	 **/
	public static function object () {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

	/**
	 * Resets the entire session
	 *
	 * Generates a new session ID and reassigns the current session
	 * to the new ID, then wipes out the Cart contents.
	 *
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function reset () {
		$this->destroy(); // Clear the session data
		$this->open();    // Reset session info
		$this->cook();    // Bake me a session as fast as you can
		do_action('shopp_session_reset');
	}

	/**
	 * Generates a new session ID
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 */
	public function reprovision () {
		$this->session(true); // Generate a new ID
		$this->cook();
	}

	/**
	 * Reset the shopping session
	 *
	 * Controls the cart to allocate a new session ID and transparently
	 * move existing session data to the new session ID.
	 *
	 * @since 1.0
	 *
	 * @param string $session (optional) Specify a session to load into this one
	 * @return bool True on success, false otherwise
	 **/
	static function resession ( $session = false ) {
		$Shopping = ShoppShopping();

		do_action('shopp_pre_resession', $session);

		// Save the current session
		$Shopping->save();

		if ( $session ) { // loading session

			$Shopping->preload($session);
			do_action('shopp_resession');
			return true;

		} else $Shopping->reprovision();

		do_action('shopp_reset_session'); // @deprecated do_action('shopp_reset_session')
		do_action('shopp_resession');

		return true;
	}

	/**
	 * Adds session stashing to keep expired carts in cold storage
	 *
	 * @since 1.3
	 *
	 * @return void
	 */
	public function clean () {
		$timeout = SHOPP_SESSION_TIMEOUT;
		$expired = SHOPP_CART_EXPIRES;
		$now = current_time('mysql');

		$meta_table = ShoppDatabaseObject::tablename('meta');

		sDB::query("INSERT INTO $meta_table (context,type,name,value,created,modified)
					SELECT 'shopping','session',session,data,created,'$now' FROM $this->_table WHERE $timeout < UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(modified) AND stash=1");

		// Delete session data preserved in meta after SHOPP_CART_EXPIRES
		sDB::query("DELETE FROM $meta_table WHERE context='shopping' AND type='session' AND $expired < UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(modified)");

		// Delete failed purchase registration data after SHOPP_CART_EXPIRES
		sDB::query("DELETE FROM $meta_table WHERE context='purchase' AND name='registration' AND type='meta' AND $expired < UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(modified)");

		return parent::clean();
	}

	/**
	 * Gets or sets the stashing flag of a session
	 *
	 * @param bool $stashing (optional) The stashing setting to set
	 * @return bool True if the session is flagged to be stashed, false otherwise
	 */
	public function stashing ( $stashing = null ) {
		if ( is_bool($stashing) ) $this->stash = $stashing ? 1 : 0;
		return ( 1 === $this->stash );
	}

	/**
	 * Ensure empty carts will not be stashed
	 *
	 * @since 1.3
	 *
	 * @param ShoppCart $Cart The shopping cart object to check
	 * @return void
	 */
	public function savecart ( $Cart ) {
		$this->stashing( ! empty($Cart->contents) );
	}


	/**
	 * Loads session data from another session into this one
	 *
	 * Used to access third-party session data. This only happens when
	 * a payment system uses server-to-server communication that needs
	 * session-specific information about the customer or transaction.
	 *
	 * @since 1.3.6
	 *
	 * @param string $session The session ID to load
	 * @return bool True if successful, false otherwise
	 */
	public function preload ( $session ) {

		if ( ! $this->exists($session) ) {
			trigger_error('Could not reload the specified session.');
			return false;
		}

		$this->destroy();
		$this->open();
		$this->load($session);
		$this->cook();

		shopp_debug('Session started ' . str_repeat('-', 64));
		return true;

	}

	/**
	 * Reload a stashed session into an active session
	 *
	 * @since 1.3
	 *
	 * @param string $session The session ID to load from cold storage
	 * @return void
	 */
	public function reload ( $session ) {

		$meta_table = ShoppDatabaseObject::tablename('meta');
		$now = current_time('mysql');

		$query = "UPDATE $this->_table AS s, $meta_table AS m
					SET s.created=m.created,s.modified='$now',s.data=m.value
					WHERE s.session=m.name AND m.context='shopping' AND m.type='session' AND m.name='" . sDB::escape($session) . "'";

		if ( sDB::query($query) )
			$this->load();

		do_action('shopp_reload');
		do_action('shopp_resession');

	}

	/**
	 * Unlock an encrypted session
	 *
	 * This queues up a redirect to bounce the browser back to an
	 * HTTPS url with an unlock request.
	 *
	 * When the unlock request is received, sensitive payment
	 * credentials are destroyed and the session is saved
	 * unencrypted. The browser is then redirected back to
	 * the original HTTP request.
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 */
	public function unlock () {

		// Intercept unlock requests to unencrypt the session
		if ( ! empty($_REQUEST['unlock']) ) {
			ShoppOrder()->securecard();
			$this->secured(false);
			Shopp::safe_redirect($_REQUEST['unlock'], 307);
			exit;
		}

		// When no secure key is available, bounce to SSL and back to unlock the session
		add_action('shopp_init', array($this, 'bouncer'), 100);

	}

	/**
	 * Bounce the browser to the secure unlock request
	 *
	 * The redirect uses HTTP 307 to encourage browsers to resubmit
	 * their POST data to the redirected URL.
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 */
	public function bouncer () {
		$this->data = false; // Prevent saving the session
		$https = Shopp::url(array('unlock' => Shopp::raw_request_url()), 'checkout', true);
		Shopp::redirect($https, true, 307);
	}

	/**
	 * A helper method that uses a Factory-like approach in instantiating objects
	 * ensuring that the correct instantiation of the object is always provided.
	 * When planning to store an entire object in the session, the object must
	 * be initialized by calling the Shopping::restart() method and providing
	 * the class name as the only argument:
	 *
	 * $object = Shopping::restart('ObjectClass');
	 *
	 * The method then determines if the object has already been
	 * initialized from a previous session, or if a new instance is required
	 * returning a reference to the instance object.
	 *
	 * NOTE: It is important to realize that any ShoppingSession-instantiated
	 * objects that use action hooks will need to re-establish those action
	 * hooks after the session is reloaded because the unserialized instance of
	 * the object will lose its hook callbacks.  This can be done by defining
	 * a new method for initalizing all the applicable action listeners, then
	 * calling that method both in the object constructor and using the __wakeup
	 * magic method.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $class The name of an object class
	 * @param Object $ref (optional) The reference to an object to be replaced
	 * @return Object The object reference
	 **/
	public static function &restart ( $class, &$ref = false ) {
		$Shopping = self::object();

		if ( is_object($ref) && method_exists($ref, '__destruct') ) $ref->__destruct();

		if ( isset($Shopping->data->$class) ) { // Restore the object
			$object = $Shopping->data->$class;
		} else {
			$object = new $class();					// Create a new object
			$Shopping->data->$class = &$object; // Register storage
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
	public static function restore ( $property, &$data ) {
		$Shopping = self::object();

		if ( isset($Shopping->data->$property) )	// Restore the data
			$data = $Shopping->data->$property;

		$Shopping->data->$property = &$data;	// Keep a reference
	}

}

/**
 * Manager for session data storage and retrieval.
 *
 * @deprecated Use Shopping::restart() and Shopping::restore() instead
 **/
final class ShoppingObject {

	/**
	 * Do not use. Deprecated.
	 *
	 * @deprecated ShoppingObject::__new
	 * @see Shopping::restart
	 *
	 * @param string $class The name of an object class
	 * @param Object $ref (optional) The reference to an object to be replaced
	 * @return Object The object reference
	 **/
	public static function &__new ( $class, &$ref = false ) {
		return Shopping::restart($class, $ref);
	}

	/**
	 * Do not use. Deprecated.
	 *
	 * @deprecated ShoppingObject::store
	 * @see Shopping::restore
	 *
	 * @param string $property Property name to use
	 * @param object $data The data to store
	 * @return void
	 **/
	public static function store ( $property, &$data ) {
		return Shopping::restore($property, $data);
	}
}
<?php
/**
 * Framework
 *
 * Library of abstract design pattern templates
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May  5, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package
 * @since 1.0
 * @subpackage framework
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Implements a list manager with internal iteration support
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class ListFramework implements Iterator {

	protected $_added;
	protected $_checks;
	protected $_list = array();

	/**
	 * Add an entry to the list
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @param string $key The key to add the entry to
	 * @param mixed $entry The entry to add to the list
	 * @return mixed Returns the entry
	 **/
	public function &add ( $key, $entry ) {
		$this->_list[$key] = $entry;
		$this->_added = $key;
		return $this->get($key);
	}

	/**
	 * Set or get the last added entry
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $key The key to set as the added record
	 * @return The added entry or false if no added entries
	 **/
	public function added ( $key = null ) {
		if ( ! is_null($key) && $this->exists($key) )
			$this->_added = $key;
		if ( $this->exists($this->_added) )
			return $this->get($this->_added);
		return false;
	}

	/**
	 * Populate the list from a set of records
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @param array $records The associative array to add
	 * @return void
	 **/
	public function populate ( array $records ) {
		$this->_list = array_merge($this->_list, $records);
	}

	/**
	 * Sorts the list by keys or by callback
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param callback $callback A callback function to use for sorting instead of the default key sorting
	 * @param string $orderby (optional) The property to use for sorting ('keys' to sort by keys, otherwise uses the values)
	 * @return boolean TRUE on success, FALSE on failure
	 **/
	public function sort ( $callback = null, $orderby = false ) {
		if ( is_null($callback) ) return ksort($this->_list);

		if ( 'keys' == $orderby ) return uksort($this->_list, $callback);
		else return uasort($this->_list, $callback);
	}

	/**
	 * Updates an entry
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @param string $key $var Description...
	 * @return boolean True if successful, false otherwise
	 **/
	public function update ( $key, $entry ) {
		if ( ! $this->exists($key) ) return false;
		if ( is_array($this->_list[ $key ]) && is_array($entry) )
			$entry = array_merge($this->_list[$key],$entry);
		$this->_list[ $key ] = $entry;
		return true;
	}

	/**
	 * Provides the count of records in the list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return int The total number of records in the list
	 **/
	public function count () {
		return count($this->_list);
	}

	/**
	 * Empties the list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function clear () {
		$this->_list = array();
		$this->_added = null;
	}

	/**
	 * Gets a record by key
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @param string $key The key of the entry to get
	 * @return mixed A reference to the entry, or false if not found
	 **/
	public function &get ( $key ) {
		$false = false;
		if ( $this->exists($key) )
			return $this->_list[$key];
		else return $false;
	}

	/**
	 * Checks if a given entry exists
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @param string $key The key of the entry to check
	 * @return boolean True if it exists, false otherwise
	 **/
	public function exists ($key) {
		if ( ! $key ) return false;
		return array_key_exists($key, $this->_list);
	}

	/**
	 * Remove an entry from the list
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @param string $key The key of the entry to remove
	 * @return boolean True if successful, false otherwise
	 **/
	public function remove ($key) {
		if ( $this->exists($key) ) {
			unset($this->_list[$key]);
			return true;
		}
		return false;
	}

	/**
	 * Gets the keys in the list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array An array of entry keys in the list
	 **/
	public function keys () {
		return array_keys($this->_list);
	}

	/**
	 * Gets the current entry using the internal list pointer
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return mixed The current entry in the list
	 **/
	public function current () {
		return current($this->_list);
	}

	/**
	 * Gets the key for the current internal list pointer entry
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string The key for the current entry
	 **/
	public function key ( ) {
		return key($this->_list);
	}

	/**
	 * Moves the internal pointer to the next entry and returns the entry
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return mixed The next entry in the list
	 **/
	public function next () {
		return next($this->_list);
	}

	/**
	 * Moves the internal pointer to the previous entry and returns the entry
	 *
	 * @author Aaron Campbell
	 * @since 1.3.1
	 *
	 * @return mixed The previous entry in the list
	 **/
	public function prev () {
		return prev($this->_list);
	}

	/**
	 * Moves the internal pointer to the beginning of the list and returns the first entry
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return mixed The first entry in the list
	 **/
	public function rewind () {
		return reset($this->_list);
	}

	/**
	 * Determines in the current entry in the list is valid
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return boolean True if the entry exists, false otherwise
	 **/
	public function valid () {
		return null !== $this->key();
	}

	/**
	 * Encodes the list to a JSON string
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string The JSON encoded string
	 **/
	public function __toString () {
		return json_encode($this->_list);
	}

	/**
	 * Preserves only the necessary properties when storing the object
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function __sleep () {
		return array('_added', '_checks', '_list');
	}

	/**
	 * Tracks when changes occur in the list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return True if changed, false otherwise
	 **/
	public function changed ( $state = null ) {
		if ( null === $state ) $state = $this->_checks; // Keep current checksum
		$this->_checks = $this->state(); // Get the current state

		// If no prior state but the list is not empty it has changed
		if ( null === $state && ! empty($this->_list) ) return true;

		// Check if the list has changed from the prior state
		return ( $state != $this->_checks );
	}

	/**
	 * Return a checksum of the state of the list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string A hash of the list
	 **/
	public function state () {
		// Use crc32b for fastest, short but specific enough checksum
		return hash('crc32b', serialize($this->_list) );
	}

} // class ListFramework

/**
 * Implements a Singleton pattern object
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class SingletonFramework {

	// @todo Requires Late-Static Binding in PHP 5.3 before extending the framework for instance return method to work

	// protected static $object;

	// public static function object () {
	// 	if ( ! self::$object instanceof self)
	// 		self::$object = new self;
	// 	return self::$object;
	// }

	/**
	 * Prevents constructing new instances of singletons
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	protected function __construct () {}

	/**
	 * Prevents cloning singletons
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	protected function __clone () {}

}

/**
 * Constructs an object defined by an associative array and defined object properties in the concrete class
 *
 * @author Jonathan Davis
 * @since 1.2
 * @version 1.3
 * @package shopp
 **/
class AutoObjectFramework {

	/**
	 * Constructor
	 *
	 * Matches array keys with defined properties of the object and populates the object from the passed array
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function __construct ( $input = null ) {
		$properties = get_object_vars($this);
		$args = func_num_args();
		if ( $args > 1 ) {
			$params = func_get_args();
			$propkeys = array_keys($properties);
			$keys = array_splice($propkeys, 0, $args);
			$inputs = array_combine($keys, $params);
		}
		else $inputs = $input;

		if ( ! is_array($inputs) || empty($inputs) ) return;

		$this->update($inputs);

	}

	/**
	 * Updates the object from an associative array
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function update ( array $inputs = array() ) {

		if ( empty($inputs) ) return;
		foreach ( $inputs as $name => $value )
			if ( property_exists($this, $name) )
				$this->$name = $value;

	}

}

class FormPostFramework {

	protected $form = array();
	protected $defaults = array();

	public function form ( $key = null, $latest = null ) {

		if ( true === $latest ) $this->updateform();

		if ( isset($key) ) {
			if ( isset($this->form[ $key ]) )
				return $this->form[ $key ];
			else return false;
		}

		return $this->form;
	}

	public function updateform () {
		$submitted = stripslashes_deep($_POST);					// Clean it up
		$this->form = array_merge($this->defaults, $submitted);	// Capture it
	}

}

/**
 * Provides a basic message dispatch object
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class SubscriberFramework {

	private $subscribers = array();

	/**
	 * Registers a subscriber object and callback handler
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param Object $target The target class/object
	 * @param string $method The callback method
	 * @return void
	 **/
	public function subscribe ( $target, $method) {
		if ( ! isset($this->subscribers[ get_class($target) ]) )
			$this->subscribers[ get_class($target) ] = array($target, $method);
	}

	/**
	 * Dispatches the message to all subscribers
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function send () {
		$args = func_get_args();
		foreach ( $this->subscribers as $callback ) {
			call_user_func_array($callback, $args);
		}
	}

}
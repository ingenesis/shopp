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

	protected $_list = array();
	protected $_added = null;
	protected $_checks = 0;

	public function &add ( string $key, $entry ) {
		$this->_list[$key] = $entry;
		$this->_added = $key;
		return $this->get($key);
	}

	public function added ( string $key = null ) {
		if ( ! is_null($key) && $this->exists($key) )
			$this->_added = $key;
		if ( $this->exists($this->_added) )
			return $this->get($this->_added);
		return false;
	}

	public function populate ( array $records ) {
		$this->_list = array_merge($this->_list,$records);
	}

	public function sort ( callable $callback = null ) {
		if ( is_null($callback) ) ksort($this->_list);
		uksort($this->_list,$callback);
	}

	public function update ( string $key, $entry ) {
		if ( ! $this->exists($key) ) return false;
		if ( is_array($this->_list[ $key ]) && is_array($entry) )
			$entry = array_merge($this->_list[$key],$entry);
		$this->_list[ $key ] = $entry;
		return true;
	}

	public function count () {
		return count($this->_list);
	}

	public function clear () {
		$this->_list = array();
		$this->_added = null;
	}

	public function &get ($key) {
		$false = false;
		if ( $this->exists($key) )
			return $this->_list[$key];
		else return $false;
	}

	public function exists ($key) {
		return array_key_exists($key,$this->_list);
	}

	public function remove ($key) {
		if ( $this->exists($key) ) {
			unset($this->_list[$key]);
			return true;
		}
		return false;
	}

	public function keys () {
		return array_keys($this->_list);
	}

	public function current () {
		return current($this->_list);
	}

	public function key ( ) {
		return key($this->_list);
	}

	public function next () {
		return next($this->_list);
	}

	public function rewind () {
		return reset($this->_list);
	}

	public function valid () {
		return null !== $this->key();
	}

	public function __toString () {
		return json_encode($this->_list);
	}

	public function __sleep () {
		return array('_added', '_checks', '_list');
	}

	public function changed () {

		$lastcheck = $this->_checks; // Keep current checksum
		$this->_checks = hash('crc32b', serialize($this->_list) );

		if ( 0 == $lastcheck ) return true;
		return ( $lastcheck != $this->_checks );

	}

} // END class ListFramework

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

class AutoObjectFramework {

	public function __construct ( $input ) {
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

	public function update ( array $inputs = array() ) {

		if ( empty($inputs) ) return;
		foreach ($inputs as $name => $value)
			if (property_exists($this,$name))
				$this->$name = $value;

	}

}

class SubscriberFramework {

	private $subscribers = array();

	public function subscribe ($target,$method) {
		if ( ! isset($this->subscribers[get_class($target)]))
			$this->subscribers[get_class($target)] = array(&$target,$method);
	}

	public function send () {
		$args = func_get_args();
		foreach ($this->subscribers as $callback) {
			call_user_func_array($callback,$args);
		}
	}

}
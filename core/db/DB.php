<?php
/**
 * DB.php
 *
 * Database communication management library
 *
 * @copyright Ingenesis Limited, March 2008-2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.3
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( ! defined('SHOPP_DBPREFIX') )
	define('SHOPP_DBPREFIX', 'shopp_');

if ( ! defined('SHOPP_QUERY_DEBUG') )
	define('SHOPP_QUERY_DEBUG', false);

/**
 * The database query interface for Shopp
 *
 * @since 1.0
 * @version 1.3
 **/
class sDB extends ShoppDBQuery {

	/** @var sDB $object sDB instance */
	protected static $object;

	/** @var array $datatypes Defines datatypes for MySQL */
	private static $datatypes = array(
		'int'		=> array('int', 'bit', 'bool', 'boolean'),
		'float'		=> array('float', 'double', 'decimal', 'real'),
		'string'	=> array('char', 'binary', 'text', 'blob'),
		'list' 		=> array('enum','set'),
		'date' 		=> array('date', 'time', 'year')
	);

	/** @var array $queries A runtime log of queries that have been executed */
	public $queries = array();

	/** @var ShoppDBInterface $api A DB API engine instance compatible with the ShoppDBInterface */
	public $api = false;

	/** @var resource $dbh The database connection handle */
	public $dbh = false;

	/** @var boolean|int $found The number of records found in the most recent query */
	public $found = false;

	/**
	 * Initializes the DB object
	 *
	 * Uses the WordPress DB connection when available
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	protected function __construct() {
		if ( isset($GLOBALS['wpdb']) )
			$this->wpdb();
		else $this->connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

		if ( empty($this->api) ) {
			$this->error("Could not load a valid Shopp database engine.");
			return;
		}
	}

	/**
	 * Provides a reference to the running sDB object
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return DB Returns a reference to the DB object
	 **/
	public static function get() {
		return self::object();
	}

	/**
	 * The singleton access method
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return DB Returns the a reference to the running DB object
	 **/
	public static function object() {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

	/**
	 * Determines the calling stack of functions or class/methods of a query for debugging
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string The call stack
	 **/
	public static function caller() {
		$backtrace  = debug_backtrace();
		$stack = array();

		foreach ( $backtrace as $caller )
			$stack[] = isset( $caller['class'] ) ?
				"{$caller['class']}->{$caller['function']}"
				: $caller['function'];

		return join( ', ', $stack );
	}

	/**
	 * Maps the SQL data type to primitive data types used by the DB class
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $type The SQL data type
	 * @return string|boolean The primitive datatype or false if not found
	 **/
	public static function datatype( $type ) {
		foreach( (array)sDB::$datatypes as $datatype => $patterns ) {
			foreach( (array)$patterns as $pattern )
				if ( strpos($type, $pattern) !== false)
					return $datatype;
		}
		return false;
	}

}
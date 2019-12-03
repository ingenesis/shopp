<?php
/**
 * DB.php
 *
 * Database management classes
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, 28 March, 2008
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage db
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( ! defined('SHOPP_DBPREFIX') ) define('SHOPP_DBPREFIX', 'shopp_');
if ( ! defined('SHOPP_QUERY_DEBUG') ) define('SHOPP_QUERY_DEBUG', false);

/**
 * The database query interface for Shopp
 *
 * @author Jonathan Davis
 * @since 1.0
 * @version 1.2
 **/
class sDB extends SingletonFramework {

	protected static $object;

	// Define datatypes for MySQL
	private static $datatypes = array(
		'int'    => array('int', 'bit', 'bool', 'boolean'),
		'float'  => array('float', 'double', 'decimal', 'real'),
		'string' => array('char', 'binary', 'text', 'blob'),
		'list'   => array('enum','set'),
		'date'   => array('date', 'time', 'year')
	);

	public $results = array(); // @deprecated No longer used
	public $queries = array(); // A runtime log of queries that have been run
	public $api     = false;   // The DB API engine instance
	public $dbh     = false;   // The
	public $found   = false;

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
	protected function __construct () {

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
	public static function get () {
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
	public static function object () {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

	/**
	 * Tethers an available WPDB connection
	 *
	 * @since 1.3.3
	 *
	 * @return void
	 **/
	protected function wpdb () {
		global $wpdb;

		if ( empty($wpdb->dbh) ) return;

		$this->api = new ShoppMySQLiEngine();

		$this->api->tether($wpdb->dbh);
	}

	/**
	 * Sets up the appropriate database engine
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected function engine () {
		$this->api = new ShoppMySQLiEngine();
	}

	/**
	 * Connects to the database server
	 *
	 * @since 1.0
	 *
	 * @param string $host The host name of the server
	 * @param string $user The database username
	 * @param string $password The database password
	 * @param string $database The database name
	 * @return void
	 **/
	protected function connect ( $host, $user, $password, $database ) {

		$this->engine();

		if ( $this->api->connect($host, $user, $password) )
			$this->db($database);
		else $this->error("Could not connect to the database server '$host'.");

	}

	/**
	 * Database system initialization error handler
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected function error ( $message ) {
		trigger_error($message);
	}

	/**
	 * Check if we have a good connection, and if not reconnect
	 *
	 * @author Jonathan Davis
	 * @since 1.1.7
	 *
	 * @return boolean
	 **/
	public function reconnect () {
		if ( $this->api->ping() ) return true;

		$this->api->close($this->dbh);
		$this->connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if ( $this->dbh ) {
			global $wpdb;
			$wpdb->dbh = $this->dbh;
		}
		return ! empty($this->dbh);
	}

	/**
	 * Selects the database to use for querying
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $database The database name
	 * @return void
	 **/
	public function db ( $database ) {
		if ( ! $this->api->select($database) )
			$this->error("Could not select the '$database' database.");

	}

	/**
	 * Determines if a table exists in the database
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean True if the table exists, otherwise false
	 **/
	public function hastable ( $table ) {
		$table = sDB::escape($table);
		$result = sDB::query("SHOW TABLES FROM " . DB_NAME . " LIKE '$table'", 'auto', 'col');
		return ! empty($result);
	}

	/**
	 * Generates a timestamp from a MySQL datetime format
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $datetime A MySQL date time string
	 * @return int A timestamp number usable by PHP date functions
	 **/
	public static function mktime ( $datetime ) {
		$h = $mn = $s = 0;
		list($Y, $M, $D, $h, $mn, $s) = sscanf($datetime, '%d-%d-%d %d:%d:%d');
		if (max($Y, $M, $D, $h, $mn, $s) == 0) return 0;
		return mktime($h, $mn, $s, $M, $D, $Y);
	}

	/**
	 * Converts a timestamp number to an SQL datetime formatted string
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $timestamp A timestamp number
	 * @return string An SQL datetime formatted string
	 **/
	public static function mkdatetime ( $timestamp ) {
		return date('Y-m-d H:i:s', $timestamp);
	}

	/**
	 * Escape the contents of data for safe insertion into the database
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string|array|object $data Data to be escaped
	 * @param boolean $unescape Whether or not to unescape() before escape()
	 * @return string Database-safe data
	 **/
	public static function escape ( $data, $unescape = true ) {
		// Prevent double escaping by stripping any existing escapes out
		if ( is_array($data) ) array_map(array(__CLASS__, 'escape'), $data);
		elseif ( is_object($data) ) {
			foreach ( get_object_vars($data) as $p => $v )
				$data->$p = self::escape($v);
		} else {
			$db = sDB::get();
			if ( $unescape === true )
				$data = self::unescape($data); // Prevent double-escapes

			$data = $db->api->escape($data);
		}
		return $data;
	}

	protected static function unescape ( $data ) {
	    return str_replace(
			array("\\\\", "\\0", "\\n", "\\r", "\Z",   "\'", '\"'),
			array("\\",   "\0",  "\n",  "\r",  "\x1a", "'",  '"'),
			$data
		);
	}

	/**
	 * Determines if the data contains serialized information
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if the data is serialized, false otherwise
	 **/
	public static function serialized ( $data ) {
		if ( ! is_string($data) ) return false;
		$data = trim($data);

	 	if ( 'N;' == $data ) return true;

		$length = strlen($data);
		if ( $length < 4 ) return false;
		if ( ':' !== $data[1] ) return false;

		$end = $data[ $length - 1 ];
		if ( ';' !== $end && '}' !== $end ) return false;

		$token = $data[0];
		switch ( $token ) {
			case 's' : return ( '"' === $data[ $length - 2 ] );
			case 'a' :
			case 'O' : return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b' : return '0' == $data[2] || '1' == $data[2];
			case 'i' :
			case 'd' : return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$/", $data );
		}
		return false;
	}

	/**
	 * Sanitize and normalize data strings
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string|array|object $data Data to be sanitized
	 * @return string Cleaned up data
	 **/
	public static function clean ( $data ) {
		if ( is_array($data) ) array_map(array(__CLASS__, 'clean'), $data);
		if ( is_string($data) ) $data = rtrim($data);
		return $data;
	}

	/**
	 * Determines the calling stack of functions or class/methods of a query for debugging
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string The call stack
	 **/
	public static function caller () {
		$backtrace  = debug_backtrace();
		$stack = array();

		foreach ( $backtrace as $caller )
			$stack[] = isset( $caller['class'] ) ?
				"{$caller['class']}->{$caller['function']}"
				: $caller['function'];

		return join( ', ', $stack );
	}

	/**
	 * Send a query to the database and retrieve the results
	 *
	 * Results can be formatted using 'auto', 'object' or 'array'.
	 *
	 *    auto - Automatically detects 'object' and 'array' results (default)
	 *  object - Provides a single object as the result
	 *   array - Provides a list of records/objects
	 *
	 * Processing results can also be automated by specifying a record processor
	 * function. A custom callback function can be provided using standard PHP
	 * callback notation, or there are builtin record processing methods
	 * supported that can be specified as a string in the callback
	 * parameter: 'auto', 'index' or 'col'
	 *
	 *  auto - Simply adds a record to the result set as a numerically indexed array of records
	 *
	 * index - Indexes record objects into an associative array using a given column name as the key
	 *         sDB::query('query', 'format', 'index', 'column', (bool)collate)
	 *         A column name is provided (4th argument) for the index key value
	 *         A 'collate' boolean flag can also be provided (5th argument) to collect records with identical index column values into an array
	 *
	 *   col - Builds records as an associative array with a single column as the array value
	 *         sDB::query('query', 'format', 'column', 'indexcolumn', (bool)collate)
	 *         A column name is provided (4th argument) as the column for the array value
	 *         An index column name can be provided (5th argument) to index records as an associative array using the index column value as the key
	 *         A 'collate' boolean flag can also be provided (6th argument) to collect records with identical index column values into an array
	 *
	 * Collating records using the 'index' or 'col' record processors require an index column.
	 * When a record's column value matches another record, the two records are collected into
	 * a nested array. The results array will have a single entry where the key is the
	 * index column's value and the value of the entry is an array of all the records that share
	 * the index column value.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @param string $query The SQL query to send
	 * @param string $format (optional) Supports 'auto' (default), 'object', or 'array'
 	 * @return array|object The query results as an object or array of result rows
	 **/
	public static function query ( $query, $format = 'auto', $callback = false ) {
		$db = sDB::get();

		$args = func_get_args();
		$args = ( count($args) > 3 ) ? array_slice($args, 3) : array();

		if ( SHOPP_QUERY_DEBUG ) $timer = microtime(true);

		$result = $db->api->query($query);

		if ( SHOPP_QUERY_DEBUG ) $db->queries[] = array($query, microtime(true) - $timer, sDB::caller());

		// Error handling
		if ( $db->dbh && $error = $db->api->error() ) {
			shopp_add_error( Shopp::__('Query failed: %s - DB Query: %s', $error, str_replace("\n", "", $query) ), SHOPP_DB_ERR);
			return false;
		}

		/** Results handling **/

		// Handle special cases
		if ( preg_match("/^\\s*(create|drop|insert|delete|update|replace) /i", $query) ) {
			if ( ! $result ) return false;
			$db->affected = $db->api->affected();
			if ( preg_match("/^\\s*(insert|replace) /i", $query) ) {
				$insert = $db->api->object( $db->api->query("SELECT LAST_INSERT_ID() AS id") );
				if ( ! empty($insert->id) )
					return (int)$insert->id;
			}

			if ( $db->affected > 0 ) return $db->affected;
			else return true;
		} elseif ( preg_match("/ SQL_CALC_FOUND_ROWS /i", $query) ) {
			$rows = $db->api->object( $db->api->query("SELECT FOUND_ROWS() AS found") );
		}

		// Default data processing
		if ( is_bool($result) ) return (boolean)$result;

		// Setup record processing callback
		if ( is_string($callback) && ! function_exists($callback) )
			$callback = array(__CLASS__, $callback);

		// Failsafe if callback isn't valid
		if ( ! $callback || ( is_array($callback) && ! method_exists($callback[0], $callback[1]) ) )
			$callback = array(__CLASS__, 'auto');

		// Process each row through the record processing callback
		$records = array();
		while ( $row = $db->api->object($result) )
			call_user_func_array($callback, array_merge( array(&$records, &$row), $args) );


		// Save the found count if it is present
		if ( isset($rows->found) ) $db->found = (int) $rows->found;

		// Free the results immediately to save memory
		// Added check for empty result to prevent warning 'Could not fetch mysqli_result'
		if ( isset($db->found) && $db->found > 0 ) $db->api->free();

		// Handle result format post processing
		switch (strtolower($format)) {
			case 'object': return reset($records); break;
			case 'array':  return $records; break;
			default:       return (count($records) == 1) ? reset($records) : $records; break;
		}
	}

	/**
	 * Builds a select query from an array of query fragments
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $options The SQL fragments
	 * @return string The complete SELECT SQL statement
	 **/
	public static function select ( $options = array() ) {
		$defaults = array(
			'columns'  => '*',
			'useindex' => '',
			'joins'    => array(),
			'table'    => '',
			'where'    => array(),
			'groupby'  => false,
			'having'   => array(),
			'limit'    => false,
			'orderby'  => false
		);
		$options = array_merge($defaults, $options);
		extract ($options);

		if ( empty($table) ) return shopp_add_error('No table specified for SELECT query.', SHOPP_DB_ERR);

		$useindex 	= empty($useindex)?'':"FORCE INDEX($useindex)";
		$joins 		= empty($joins)?'':"\n\t\t".join("\n\t\t",$joins);
		$where 		= empty($where)?'':"\n\tWHERE ".join(' AND ',$where);
		$groupby 	= empty($groupby)?'':"GROUP BY $groupby";
		$having 	= empty($having)?'':"HAVING ".join(" AND ",$having);
		$orderby	= empty($orderby)?'':"\n\tORDER BY $orderby";
		$limit 		= empty($limit)?'':"\n\tLIMIT $limit";

		return "SELECT $columns\n\tFROM $table $useindex $joins $where $groupby $having $orderby $limit";
	}

	/**
	 * Provides the number of records found in the last query
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return int The number of records found
	 **/
	public static function found () {
		$db = sDB::get();
		$found = $db->found;
		$db->found = false;
		return $found;
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
	public static function datatype ( $type ) {
		foreach( (array)sDB::$datatypes as $datatype => $patterns ) {
			foreach( (array)$patterns as $pattern )
				if ( strpos($type, $pattern) !== false)
					return $datatype;
		}
		return false;
	}

	/**
	 * Prepares a ShoppDatabaseObject for entry into the database
	 *
	 * Iterates the properties of a ShoppDatabaseObject and formats the data
	 * according to the datatype meta available for the property to create
	 * an array of key/value pairs that are easy concatenate into a valid
	 * SQL query
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param ShoppDatabaseObject $Object The object to be prepared
	 * @return array Data structure ready for query building
	 **/
	public static function prepare ( $Object, array $mapping = array() ) {
		$data = array();

		// Go through each data property of the object
		$properties = get_object_vars($Object);
		foreach ( $properties as $var => $value) {
			$property = isset($mapping[ $var ]) ? $mapping[ $var ] : $var;
			if ( ! isset($Object->_datatypes[ $property ]) ) continue;

			// If the property is has a _datatype
			// it belongs in the database and needs
			// to be prepared

			// Process the data
			switch ( $Object->_datatypes[ $property ] ) {
				case 'string':
					// Escape characters in strings as needed
					if ( is_array($value) || is_object($value) ) $data[ $property ] = "'" . addslashes(serialize($value)) . "'";
					else $data[ $property ] = "'" . sDB::escape($value) . "'";
					break;
				case 'list':
					// If value is empty, skip setting the field
					// so it inherits the default value in the db
					if ( ! empty($value) )
						$data[ $property ] = "'$value'";
					break;
				case 'date':
					// If it's an empty date, set it to the current time
					if ( is_null($value) ) {
						$value = current_time('mysql');
					// If the date is an integer, convert it to an
					// sql YYYY-MM-DD HH:MM:SS format
					} elseif ( ! empty($value) && ( is_int($value) || intval($value) > 86400) ) {
						$value = sDB::mkdatetime( intval($value) );
					}

					$data[$property] = "'$value'";
					break;
				case 'float':

					// Sanitize without rounding to protect precision
					if ( is_string($value) && method_exists('ShoppCore', 'floatval') ) $value = ShoppCore::floatval($value, false);
					else $value = floatval($value);

				case 'int':
					// Normalize for MySQL float representations (@see bug #853)
					// Force formating with full stop (.) decimals
					// Trim excess 0's followed by trimming (.) when there is no fractional value
					$value = rtrim(rtrim( number_format((double)$value, 6, '.', ''), '0'), '.');

					$data[ $property ] = "'$value'";
					if ( empty($value) ) $data[ $property ] = "'0'";

					// Special exception for id fields
					if ( 'id' == $property && empty($value) ) $data[ $property ] = "NULL";
					break;
				default:
					// Anything not needing processing
					// passes through into the structure
					$data[ $property ] = "'$value'";
			}

		}

		return $data;
	}

	/**
	 * Get the list of possible values for an SQL enum or set column
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $table The table to read column data from
	 * @param string $column The column name to inspect
	 * @return array List of values
	 **/
	public static function column_options ( $table = null, $column = null ) {
		if ( ! ( $table && $column ) ) return array();
		$r = sDB::query("SHOW COLUMNS FROM $table LIKE '$column'");
		if ( strpos($r[0]->Type, "enum('") )
			$list = substr($r[0]->Type, 6, strlen($r[0]->Type) - 8);

		if ( strpos($r[0]->Type, "set('") )
			$list = substr($r[0]->Type, 5, strlen($r[0]->Type) - 7);

		return explode("','", $list);
	}

	/**
	 * Processes a bulk string of semi-colon terminated SQL queries
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @param string $queries Long string of multiple queries
	 * @return boolean
	 **/
	public function loaddata ( $queries ) {
		$queries = explode(";\n", $queries);

		foreach ($queries as $query)
			if ( ! empty($query) )
				sDB::query($query);

		return true;
	}

	/**
	 * Add a record to the record set
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records The record set
	 * @param object $record The record to process
	 * @return void
	 **/
	private static function auto ( &$records, &$record ) {
		$records[] = $record;
	}

	/**
	 * Add a record to the set and index it by a given column name
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records The record set
	 * @param object $record The record to process
	 * @param string $column The column name to use as the key for record
	 * @param boolean $collate (optional) Set to true to collate the records (defaults to false)
	 * @return void
	 **/
	private static function index ( &$records, &$record, $column, $collate = false ) {
		if ( isset($record->$column) ) $col = $record->$column;
		else $col = null;

		if ( $collate ) {

			if ( isset($records[ $col ]) ) $records[ $col ][] = $record;
			else $records[ $col ] = array($record);

		} else $records[ $col ] = $record;
	}

	/**
	 * Add a record to the set and index it by a given column name
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records The record set
	 * @param object $record The record to process
	 * @param string $column The column name to use as the value for the record
	 * @param string $index The index column name to use as the key for record
	 * @param boolean $collate (optional) Set to true to collate the records (defaults to false)
	 * @return void
	 **/
	private static function col ( &$records, &$record, $column = false, $index = false, $collate = false ) {

		$columns = get_object_vars($record);

		if ( isset($record->$column) ) $col = $record->$column;
		else $col = reset($columns); // No column specified, get first column

		if ( $index ) {
			if ( isset($record->$index) ) $id = $record->$index;
			else $id = 0;

			if ( $collate && ! empty($id) ) {

				if ( isset($records[ $id ]) ) $records[ $id ][] = $col;
				else $records[ $id ] = array($col);

			} else $records[ $id ] = $col;

		} else $records[] = $col;
	}


} // END class sDB

if ( ! class_exists('DB',false) ) {
	class DB extends sDB {
		/* @deprecated use sDB class */

		public static function get () {
			return sDB::object();
		}

		public static function instance () {
			return sDB::object();
		}

	}
}

/**
 * The interface for Shopp DB engines
 *
 * @since 1.3.3
 * @package sDB
 **/
interface ShoppDBInterface {

	public function connect ( $host, $user, $password );
	public function tether ( $connection );
	public function db ( $database );
	public function ping ();
	public function close ();
	public function query ( $query );
	public function error ();
	public function affected ();
	public function object ( $results = null );
	public function free ();
	public function escape ( $string );
}


/**
 * Implements the PHP mysqli extension
 *
 * @since 1.3.3
 * @package sDB
 **/
class ShoppMySQLiEngine implements ShoppDBInterface {

	private $connection;
	private $results;

	public function tether ( $connection ) {
		$this->connection = $connection;
	}

	public function connect ( $host, $user, $password ) {
		$this->connection = new mysqli();
		@$this->connection->real_connect($host, $user, $password);
		return $this->connection;
	}

	public function db ( $database ) {
		return @$this->connection->select_db($database);
	}

	public function ping () {
		return $this->connection->ping();

	}

	public function close () {
		return @$this->connection->close();
	}

	public function query ( $query ) {
		$this->results = @$this->connection->query($query);
		return $this->results;
	}

	public function error () {
		return $this->connection->error;
	}

	public function affected () {
		return $this->connection->affected_rows;
	}

	public function object ( $results = null ) {
		if ( empty($results) ) $results = $this->results;
		if ( ! is_a($results, 'mysqli_result') ) return false;
		return $results->fetch_object();
	}

	public function free () {
		if ( ! is_a($this->results, 'mysqli_result') ) return false;
			return $this->results->free();
	}

	public function escape ( $string ) {
		return $this->connection->real_escape_string($string);
	}

}

/**
 * Provides interfacing between database records and active data objects
 *
 * @author Jonathan Davis
 * @since 1.0
 * @version 1.2
 **/
abstract class ShoppDatabaseObject implements Iterator {

	protected $_position = 0;
	protected $_properties = array();
	protected $_ignores = array('_');
	protected $_key = '';
	protected $_map = array();

	/**
	 * Initializes the ShoppDatabaseObject with functional necessities
	 *
	 * A ShoppDatabaseObject tracks meta data relevant to translating PHP object
	 * data into SQL-ready data.  This is done by reading and caching the
	 * table schema so the properties and their data types can be known
	 * in order to automate query building.
	 *
	 * The table schema is stored in an array structure that contains
	 * the columns and their datatypes.  This structure is cached as the
	 * current data_model setting. If a table is missing from the data_model
	 * a new table schema structure is generated on the fly.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $table The base table name (without prefixes)
	 * @param string $key (optional) The column name of the primary key
	 * @return boolean True if init was successful, otherwise false
	 **/
	public function init ( $table, $key = null ) {

		if ( is_null($key) ) $key = 'id';

		$Settings = ShoppSettings();

		// So we know what the table name is
		if ( ! empty($table) && ( ! isset($this->_table) || empty($this->_table) )  )
			$this->_table = $this->tablename($table);

		if ( empty($this->_table) ) return false;

		$this->_key = $key;				// So we know what the primary key is
		$this->_datatypes = array();	// So we know the format of the table
		$this->_lists = array();		// So we know the options for each list
		$defaults = array();			// So we know the default values for each field

		$map = ! empty($this->_map) ? array_flip($this->_map) : array();

		$Tables = $Settings->available() ? $Settings->get('data_model') : array();

		if ( isset($Tables[ $this->_table ]) ) {
			$this->_datatypes = $Tables[ $this->_table ]->_datatypes;
			$this->_lists = $Tables[ $this->_table ]->_lists;
			$defaults = $Tables[ $this->_table ]->_defaults;

			foreach ( $this->_datatypes as $var => $type ) {
				$property = isset($map[ $var ]) ? $map[ $var ] : $var;

				if ( ! isset($this->$property) )
					$this->{$property} = isset($defaults[$var]) ? $defaults[$var] : '';
				if ( 'date' == $type
					&& ('0000-00-00 00:00:00' == $this->{$property} || empty($this->{$property}) ))
					$this->{$property} = null;
			}

			return true;
		}

		if ( ! $r = sDB::query("SHOW COLUMNS FROM $this->_table", 'array') ) return false;

		// Map out the table definition into our data structure
		foreach ( $r as $object ) {
			$var = $object->Field;

			$this->_datatypes[ $var ] = sDB::datatype($object->Type);
			$this->_defaults[ $var ] = $object->Default;

			// Grab out options from list fields
			if ('list' == sDB::datatype($object->Type)) {
				$values = str_replace("','", ",", substr($object->Type,strpos($object->Type,"'")+1,-2));
				$this->_lists[$var] = explode(",",$values);
			}

			if ( ! empty($map) && ! isset($map[ $var ]) ) continue;

			// Remap properties if a property map is available
			$property = isset($map[$var])?$map[$var]:$var;
			if (!isset($this->{$property}))
				$this->{$property} = $this->_defaults[$var];

		}

		if ( $Settings->available() ) {
			if ( empty($Tables) ) $Tables = array();
			$Tables[ $this->_table ] = new StdClass();
			$Tables[ $this->_table ]->_datatypes =& $this->_datatypes;
			$Tables[ $this->_table ]->_lists =& $this->_lists;
			$Tables[ $this->_table ]->_defaults =& $this->_defaults;

			$Settings->save('data_model', $Tables);
		}
		return true;
	}

	/**
	 * Load a single record by the primary key or a custom query
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param $where - An array of key/values to be built into an SQL where clause
	 * or
	 * @param $id - A string containing the id for db object's predefined primary key
	 * or
	 * @param $id - A string containing the object's id value
	 * @param $key - A string of the name of the db object's primary key
	 **/
	public function load () {
		$args = func_get_args();
		if ( empty($args[0]) ) return false;

		$where = "";
		if ( is_array($args[0]) ) {
			foreach ( $args[0] as $key => $id )
				$where .= ( $where == "" ? "" : " AND " ) . "$key='" . sDB::escape($id) . "'";
		} else {
			$id = $args[0];
			$key = $this->_key;
			if ( ! empty($args[1]) ) $key = $args[1];
			$where = $key . "='" . sDB::escape($id) . "'";
		}

		$r = sDB::query("SELECT * FROM $this->_table WHERE $where LIMIT 1", 'object');
		$this->populate($r);

		if ( ! empty($this->id) ) return true;
		return false;
	}

	/**
	 * Callback for loading objects from a record set
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records A reference to the loaded record set
	 * @param object $record A reference to the individual record to process
	 * @param string $DatabaseObject (optional) The ShoppDatabaseObject class name to convert the record to
	 * @param string $index (optional) The record column to use as the index in the record set
	 * @param boolean $collate (optional) Flag to collate the records (records with matching index columns are collected into a nested array on the index in the set)
	 * @param object $record Result record data object
	 * @return void
	 **/
	public function loader ( array &$records, &$record, $DatabaseObject = false, $index='id', $collate = false ) {

		if ( isset($this) ) {
			if ( 'id' == $index ) $index = $this->_key;
			$DatabaseObject = get_class($this);
		}
		$index = isset($record->$index) ? $record->$index : '!NO_INDEX!';
		if ( ! isset($DatabaseObject) || ! class_exists($DatabaseObject) ) return;
		$Object = new $DatabaseObject();
		$Object->populate($record);
		if ( method_exists($Object, 'expopulate') )
			$Object->expopulate();

		if ( $collate ) {
			if ( ! isset($records[ $index ]) ) $records[$index] = array();
			$records[ $index ][] = $Object;
		} else $records[ $index ] = $Object;

	}

	/**
	 * Callback for loading object-related meta data into properties
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records A reference to the loaded record set
	 * @param object $record Result record data object
	 * @param array $objects
	 * @param string $id
	 * @param string $property
	 * @param boolean $collate
	 * @param boolean $merge
	 * @return void
	 **/
	public function metaloader ( &$records, &$record, $objects = array(), $id = 'id', $property = '', $collate = false, $merge = false ) {

		if ( is_array($objects) && isset($record->$id) && isset($objects[ $record->$id ]) ) {
			$target = $objects[ $record->$id ];
		} elseif ( isset($this) ) {
			$target = $this;
		}

		// Remove record ID before attaching record (duplicates $this->id)
		unset( $record->$id );

		if ( $collate ) {
			if ( ! isset($target->$property) || ! is_array($target->$property) )
				$target->$property = array();

			// Named collation if collate is a valid record property
			if ( isset($record->$collate) ) {

				// If multiple entries line up on the same key, build a list inside that key
				if ( isset($target->{$property}[ $record->$collate ]) ) {
					if ( ! is_array($target->{$property}[ $record->$collate ]) )
						$target->{$property}[ $record->$collate ] = array($target->{$property}[ $record->$collate ]->id => $target->{$property}[ $record->$collate ]);
					$target->{$property}[ $record->$collate ][ $record->id ] = $record;

				} else $target->{$property}[ $record->$collate ] = $record; // or index directly on the key

			} else $target->{$property}[] = $record; // Build a non-indexed list

		} else $target->$property = $record; // Map a single property

		if ( $merge ) {
			foreach ( get_object_vars($record) as $name => $value ) {
				if ( 'id' == $name // Protect $target object's' id column from being overwritten by meta data
					|| ( isset($target->_datatypes ) && in_array($name, $target->_datatypes) ) ) continue; // Protect $target object's' db columns
				$target->$name = &$record->$name;
			}
		}
	}

	/**
	 * Builds a table name from the defined WP table prefix and Shopp prefix
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $table The base table name
	 * @return string The full, prefixed table name
	 **/
	public static function tablename ( $table = '' ) {
		global $wpdb;
		return apply_filters('shopp_table_name', $wpdb->get_blog_prefix() . SHOPP_DBPREFIX . $table, $table);
	}

	/**
	 * Saves the current state of the ShoppDatabaseObject to the database
	 *
	 * Intelligently saves a ShoppDatabaseObject, using an UPDATE query when the
	 * value for the primary key is set, and using an INSERT query when the
	 * value of the primary key is not set.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return boolean|int Returns true when UPDATEs are successful; returns an integer with the record ID
	 **/
	public function save () {

		$classhook = strtolower( get_class($this) );
		$data = sDB::prepare($this, $this->_map);

		$id = isset($this->{$this->_key}) ? $this->{$this->_key} : false;
		if ( ! empty($this->_map) ) {
			$remap = array_flip($this->_map);
			if ( isset($remap[ $this->_key ]) )
				$id = $this->{$remap[ $this->_key ]};
		}

		$time = current_time('mysql');
		if ( isset($data['modified']) ) $data['modified'] = "'$time'";

		if ( empty($id) ) { // Insert new record

			if ( isset($data['created']) ) $data['created'] = "'$time'";
			$dataset = ShoppDatabaseObject::dataset($data);
			$this->id = sDB::query("INSERT $this->_table SET $dataset");

			do_action_ref_array( "shopp_save_$classhook", array($this) );
			do_action_ref_array( "shopp_create_$classhook", array($this) );
			return $this->id;

		}

		// Update record
		unset($data['id']);
		$dataset = ShoppDatabaseObject::dataset($data);
		sDB::query("UPDATE $this->_table SET $dataset WHERE $this->_key='$id'");

		do_action_ref_array( "shopp_save_$classhook", array($this) );
		return true;

	}

	/**
	 * Deletes the database record associated with the ShoppDatabaseObject
	 *
	 * Deletes the record that matches the primary key of the current
	 * ShoppDatabaseObject
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean
	 **/
	public function delete () {

		$id = $this->{$this->_key};

		if ( empty($id) ) return false;

		$classhook = sanitize_key( get_class($this) );
		do_action( "shopp_delete_$classhook", $this );

		return sDB::query("DELETE FROM $this->_table WHERE $this->_key='$id'");

	}

	/**
	 * Verify the loaded record actually exists in the database
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function exists ( $verify = false ) {
		$key = $this->_key;
		if ( empty($this->$key) ) return false;

		if ( $verify ) {
			$id = $this->$key;
			$exists = sDB::query("SELECT id FROM $this->_table WHERE $key='$id' LIMIT 1", 'auto', 'col', 'id');
			return ( ! empty($exists) );
		}

		return true;
	}

	/**
	 * Populates the ShoppDatabaseObject properties from a db query result object
	 *
	 * Uses the available data model built from the table schema to
	 * automatically set the object properties, taking care to convert
	 * special data such as dates and serialized structures.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param object $data The query results
	 * @return void
	 **/
	public function populate ( $data ) {
		if ( empty($data) ) return false;

		$properties = get_object_vars($data);
		foreach( (array)$properties as $var => $value ) {

			$mapping = empty($this->_map) ? array() : array_flip($this->_map);
			if ( ! isset($this->_addmap) && ! empty($mapping) && ! isset($mapping[ $var ]) ) continue;
			$property = isset($mapping[ $var ]) ? $mapping[ $var ] : $var;

			if ( empty($this->_datatypes[ $var ]) ) continue;

			// Process the data
			switch ( $this->_datatypes[ $var ] ) {
				case 'date':
					$this->$property = sDB::mktime($value);
					break;
				case 'float': $this->$property = (float)$value; break;
				case 'int': $this->$property = (int)$value; break;
				case 'string':
					// If string has been serialized, unserialize it
					if ( sDB::serialized($value) )
						$value = @unserialize($value);
				default:
					// Anything not needing processing
					// passes through into the object
					$this->$property = $value;
			}
		}
	}

	/**
	 * Builds an SQL-ready string of prepared data for entry into the database
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $data The prepared data
	 * @return string The query fragment of column value updates
	 **/
	public static function dataset ( array $data ) {
		$sets = array();
		foreach ( $data as $property => $value )
			$sets[] = "$property=$value";
		return join(',', $sets);
	}

	/**
	 * Populate the object properties from an array
	 *
	 * Updates the ShoppDatabaseObject properties when the key of the array
	 * entry matches the name of the ShoppDatabaseObject property
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $data The array of updated values
	 * @param array $ignores (optional) A list of properties to skip updating
	 * @return void
	 **/
	public function updates ( array $data, array $ignores = array() ) {
		if ( ! is_array($data)) return;
		foreach ($data as $key => $value) {
			if (!is_null($value)
				&& ($ignores === false
					|| (is_array($ignores)
							&& !in_array($key,$ignores)
						)
					) && property_exists($this, $key) ) {
				$this->$key = sDB::clean($value);
			}
		}
	}

	/**
	 * Copy property values into the current DatbaseObject from another object
	 *
	 * Copies the property values from a specified object into the current
	 * ShoppDatabaseObject where the property names match.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param object $data The source object or array to copy from
	 * @param string $prefix (optional) A property prefix
	 * @param array $ignores (optional) List of property names to ignore copying from
	 * @return void
	 **/
	public function copydata ( $data, $prefix = '', $ignores = false ) {
		if ( ! is_array($ignores) || $ignores === false ) $ignores = array('_datatypes', '_table', '_key', '_lists', '_map', 'id', 'created', 'modified');

		$properties = is_object($data) ? get_object_vars($data) : $data;
		foreach ( (array)$properties as $property => $value ) {
			$property = $prefix . $property;
			if ( property_exists($this, $property) && ! in_array($property, $ignores) )
					$this->$property = sDB::clean($value);
		}
	}

	public function clear () {
		$ObjectClass = get_class($this);
		$new = new $ObjectClass();
		$this->copydata($new, '', array());
	}

	/**
	 * Shrinks a ShoppDatabaseObject to json-friendly data size
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array JSON-ready data set
	 **/
	public function json ( array $ignores = array() ) {
		$this->_ignores = array_merge($this->_ignores, $ignores);
		$this->_properties = $this->_properties(true);
		$json = array();
		foreach ( $this as $name => $property ) $json[ $name ] = $property;
		return $json;
	}

	/**
	 * shopp('...','...') tags
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 * @deprecated Retained for compatibility
	 *
	 * @param string $property The property to handle
	 * @param array $options (optional) The tag options to process
	 * @return mixed
	 **/
	public function tag ( $property, array $options = array() ) {
		$options = array_merge( array('return' => true), shopp_parse_options($options) );
		return shopp($this, $property, $options);
	}

	/** Iterator Support **/

	public function current () {
		return $this->{$this->_properties[ $this->_position ]};
	}

	public function key () {
		return $this->_properties[ $this->_position ];
	}

	public function next () {
		++$this->_position;
	}

	public function rewind () {
		$this->_position = 0;
	}

	public function valid () {
		return ( isset($this->_properties[ $this->_position ]) && isset($this->{$this->_properties[ $this->_position ]}) );
	}

	/**
	 * Get the a list of the current property names in the object
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param boolean $compact (optional) Set to true for a compact list of properties (skip the ignored properties)
	 * @return array The list of property names
	 **/
	private function _properties ( $compact = null ) {
		$properties = array_keys( get_object_vars($this) );
		if ( $compact ) $properties = array_values( array_filter($properties, array($this, '_ignored')) );
		return $properties;
	}

	/**
	 * Checks if a property should be ignored
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $property The name of the property to check
	 * @return boolean True if ignored, false otherwise
	 **/
	private function _ignored ( $property ) {
		return (! (
					in_array($property, $this->_ignores)
					|| (
						in_array('_', $this->_ignores)
						&& '_' == $property[0])
					)
				);

	}

	/**
	 * Streamlines data for serialization
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of properties to serialize
	 **/
	public function __sleep () {
		return $this->_properties(true);
	}

	/**
	 * Reanimate the object
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function __wakeup () {
		$classname = get_class($this);
		$tablename = get_class_property($classname,'table');
		$this->init($tablename);
	}

} // END class ShoppDatabaseObject

/**
 * Integrates Shopp ShoppDatabaseObjects with WordPress data tables
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package DB
 **/
class WPDatabaseObject extends ShoppDatabaseObject {

	public $post_author = '';

	/**
	 * Builds a table name from the defined WP table prefix
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $table The base table name
	 * @return string The full, prefixed table name
	 **/
	static function tablename ($table = '') {
		global $wpdb;
		return $wpdb->get_blog_prefix() . $table;
	}

	/**
	 * Adds the save_post event to Shopp custom post saves
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function save () {
		parent::save();
		do_action('save_post', $this->id, get_post($this->id), $update = true);
	}

}

/**
 * A foundational Shopp/WordPress CPT DatabaseObject
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package DB
 **/
class WPShoppObject extends WPDatabaseObject {
	static $posttype = 'shopp_post';

	public function load () {
		$args = func_get_args();
		if (empty($args[0])) return false;

		if (count($args) == 2) {
			list($id,$key) = $args;
			if (empty($key)) $key = $this->_key;
			$p = array($key => $id);
		}
		if (is_array($args[0])) $p = $args[0];

		$class = get_class($this);
		$p['post_type'] = get_class_property($class, 'posttype');

		parent::load($p);
	}

	public static function labels () {
		return array(
			'name'          => Shopp::__('Posts'),
			'singular_name' => Shopp::__('Post')
		);
	}

	public static function capabilities () {
		return apply_filters( 'shopp_product_capabilities', array(
			'edit_post'   => self::$posttype,
			'delete_post' => self::$posttype
		) );
	}

	public static function supports () {
		return array(
			'title',
			'editor'
		);
	}

	public static function register ($class,$slug) {
		$posttype = get_class_property($class, 'posttype');
		register_post_type( $posttype, array(
			'labels'       => call_user_func(array($class, 'labels')),
			'capabilities' => call_user_func(array($class, 'capabilities')),
			'supports'     => call_user_func(array($class, 'supports')),
			'rewrite'      => array( 'slug' => $slug, 'with_front' => false ),
			'public'       => true,
			'has_archive'  => true,
			'show_ui'      => false,
			'_edit_link'   => 'admin.php?page=shopp-products&id=%d'
		));
	}
}

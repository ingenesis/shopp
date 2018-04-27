<?php
/**
 * DBQuery.php
 *
 * Provides database query functionality
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppDBQuery extends ShoppDBDataAdapter {

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
	public static function query( $query, $format = 'auto', $callback = false ) {
		$db = sDB::get();

		$args = func_get_args();
		$args = ( count($args) > 3 ) ? array_slice($args, 3) : array();

		if ( SHOPP_QUERY_DEBUG ) $timer = microtime(true);

		$result = $db->api->query($query);

		if ( SHOPP_QUERY_DEBUG ) $db->queries[] = array($query, microtime(true) - $timer, sDB::caller());

		// Error handling
		if ( $db->dbh && $error = $db->api->error() ) {
			shopp_add_error( sprintf('Query failed: %s - DB Query: %s', $error, str_replace("\n", "", $query) ), SHOPP_DB_ERR);
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
		if ( isset($rows->found) )
            $db->found = (int) $rows->found;

		// Free the results immediately to save memory
		if ( $db->found > 0 )
            $db->api->free();

		// Handle result format post processing
		switch (strtolower($format)) {
			case 'object': return reset($records); break;
			case 'array':  return $records; break;
			default:       return (count($records) == 1)?reset($records):$records; break;
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
	public static function select( $options = array() ) {
		$defaults = array(
			'columns' => '*',
			'useindex' => '',
			'joins' => array(),
			'table' => '',
			'where' => array(),
			'groupby' => false,
			'having' => array(),
			'limit' => false,
			'orderby' => false
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( empty($table) )
			return shopp_add_error('No table specified for SELECT query.', SHOPP_DB_ERR);

		$columns    = empty($columns) ? '*' : $columns;
		$useindex 	= empty($useindex) ? '' : "FORCE INDEX($useindex)";
		$joins 		= empty($joins) ? '' : "\n\t\t" . join("\n\t\t", $joins);
		$where 		= empty($where) ? '' : "\n\tWHERE " . join(' AND ', $where);
		$groupby 	= empty($groupby) ? '' : "GROUP BY $groupby";
		$having 	= empty($having) ? '' : "HAVING " . join(" AND ", $having);
		$orderby	= empty($orderby) ? '' : "\n\tORDER BY $orderby";
		$limit 		= empty($limit) ? '' : "\n\tLIMIT $limit";

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
	public static function found() {
		$db = sDB::get();
		$found = $db->found;
		$db->found = false;
		return $found;
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
	public static function column_options( $table = null, $column = null ) {
		if ( ! ( $table && $column ) ) return array();
		$r = sDB::query("SHOW COLUMNS FROM $table LIKE '$column'");
		if ( strpos($r[0]->Type, "enum('") )
			$list = substr($r[0]->Type, 6, strlen($r[0]->Type) - 8);

		if ( strpos($r[0]->Type, "set('") )
			$list = substr($r[0]->Type, 5, strlen($r[0]->Type) - 7);

		return explode("','", $list);
	}

	/**
	 * Determines if a table exists in the database
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean True if the table exists, otherwise false
	 **/
	public function hastable( $table ) {
		$table = sDB::escape($table);
		$result = sDB::query("SHOW TABLES FROM " . DB_NAME . " LIKE '$table'", 'auto', 'col');
		return ! empty($result);
	}

	/**
	 * Processes a bulk string of semi-colon terminated SQL queries
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @param string $queries Long string of multiple queries
	 * @return boolean
	 **/
	public function loaddata( $queries ) {
		$queries = explode(";\n", $queries);

		foreach ( $queries as $query )
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
	private static function auto( &$records, &$record ) {
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
	private static function index( &$records, &$record, $column, $collate = false ) {
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
	private static function col( &$records, &$record, $column = false, $index = false, $collate = false ) {

		$columns = get_object_vars($record);

		if ( isset($record->$column) )
			$col = $record->$column;
		else $col = reset($columns); // No column specified, get first column

		if ( $index ) {
			if ( isset($record->$index) )
				$id = $record->$index;
			else $id = 0;

			if ( $collate && ! empty($id) ) {

				if ( isset($records[ $id ]) )
					$records[ $id ][] = $col;
				else $records[ $id ] = array($col);

			} else $records[ $id ] = $col;

		} else $records[] = $col;
	}

}
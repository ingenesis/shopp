<?php
/**
 * ShoppDBDataAdapter.php
 *
 * Provides database data conversion methods
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppDBDataAdapter extends ShoppDBManager {

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
	public static function prepare( $Object, array $mapping = array() ) {
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
					if (is_null($value))
						$value = current_time( 'mysql' );
					// SQL YYYY-MM-DD HH:MM:SS format
					$value = sDB::mkdatetime( $value );

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
	 * Generates a timestamp from a MySQL datetime format
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $datetime A MySQL date time string
	 * @return int A timestamp number usable by PHP date functions
	 **/
	public static function mktime( $datetime ) {
		$h = $mn = $s = 0;
		list($Y, $M, $D, $h, $mn, $s) = sscanf($datetime, '%d-%d-%d %d:%d:%d');
		if ( max($Y, $M, $D, $h, $mn, $s) == 0 ) return 0;
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
	public static function mkdatetime( $timestamp ) {
		$datetime = '0000-00-00 00:00:00';

		if ( is_string($timestamp) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp) )
				return $timestamp; // Ignore already properly formatted strings

		// Check > 0 to prevent 0 from becoming epoch datetime and passthrough negative integers
		if ( intval($timestamp) > 0 )
				return date('Y-m-d H:i:s', $timestamp);

		return $datetime;
	}

	/**
	 * Escape the contents of data for safe insertion into the database
	 *
	 * @since 1.0
	 * @param string|array|object $data Data to be escaped
	 * @return string Database-safe data
	 **/
	public static function escape( $data ) {
		if ( is_array($data) )
			array_map(array(__CLASS__, 'escape'), $data);
		elseif ( is_object($data) )
			foreach ( get_object_vars($data) as $p => $v )
				$data->$p = self::escape($v);
		else // Unescape to prevent double escapes
			$data = self::str_escape( self::unescape($data) );
		return $data;
	}

	/**
	 * Unescape already escaped data
	 *
	 * @since 1.1
	 * @param mixed $data The data to unescape
	 * @return string The unescaped data
	 **/
	protected static function unescape( $data ) {
	    return str_replace(
			array("\\\\", "\\0", "\\n", "\\r", "\\Z", "\\'", '\"'),
			array("\\",   "\0",  "\n",  "\r",  "\x1a", "'",  '"'),
			$data
		);
	}

	/**
	 * Escape a single string
	 *
	 * @since 1.5
	 * @param string $data The string to escape
	 * @return string The escaped string
	 **/
	public static function str_escape( $string ) {
		$db = sDB::get();
		return $db->api->escape($string);
	}

	/**
	 * Determines if the data contains serialized information
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if the data is serialized, false otherwise
	 **/
	public static function serialized( $data ) {
		if ( ! is_string($data) )
			return false;
		$data = trim($data);

	 	if ( 'N;' == $data )
			return true;

		$length = strlen($data);
		if ( $length < 4 )
			return false;
		if ( ':' !== $data[1] )
			return false;

		$end = $data[ $length - 1 ];
		if ( ';' !== $end && '}' !== $end )
			return false;

		$token = $data[0];
		switch ( $token ) {
			case 's' :
				return ( '"' === $data[ $length - 2 ] );
			case 'a' :
			case 'O' :
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b' :
				return '0' == $data[2] || '1' == $data[2];
			case 'i' :
			case 'd' :
				return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$/", $data );
		}
		return false;
	}

	/**
	 * Sanitize and normalize data strings
	 *
	 * @since 1.0
	 *
	 * @param string|array|object $data Data to be sanitized
	 * @return string Cleaned up data
	 **/
	public static function clean( $data ) {
		if ( is_array($data) ) array_map(array(__CLASS__, 'clean'), $data);
		if ( is_string($data) ) $data = rtrim($data);
		return $data;
	}

}
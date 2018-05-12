<?php
/**
 * DatabaseObject.php
 *
 * Provides interfacing between database records and active data objects
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.2
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

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

        $Tables = array();
        if ( $Settings->available() ) {
            $datamodel = $Settings->get('data_model');
            if ( ! empty($datamodel) )
                $Tables = $datamodel;
        }

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
		if ( ! is_array($ignores) || $ignores === false )
			$ignores = array('_datatypes', '_table', '_key', '_lists', '_map', 'id', 'created', 'modified');

		$properties = is_object($data) ? get_object_vars($data) : $data;
		foreach ( (array)$properties as $property => $value ) {
			$property = $prefix . $property;
			if ( property_exists($this, $property) && ! in_array($property, $ignores) )
					$this->$property = sDB::clean($value);
		}
	}

	/**
	 * Clear all of the data properties for the current object
	 *
	 * @since 1.0
	 * @return void
	 **/
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
		$options = array_merge( array('return' => true), Shopp::parse_options($options) );
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
	private function _ignored ($property) {
		return (! (
					in_array($property,$this->_ignores)
					|| (
						in_array('_',$this->_ignores)
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

}
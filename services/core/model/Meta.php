<?php
/**
 * Meta.php
 *
 * The meta object abstract
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February 10, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage meta
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppMetaObject
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage meta
 **/
class ShoppMetaObject extends ShoppDatabaseObject {
	static $table = 'meta';

	public $context = 'product';
	public $type = 'meta';

	/**
	 * Meta constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	function __construct ( $id = false, $key = 'id' ) {
		$this->init(self::$table);
		if ( ! $id ) return;

		if ( is_array($id) )
			$this->load($id);
		else $this->load(array($key => $id, 'type' => $this->type));

		if ( ! empty($this->id) && ! empty($this->_xcols) )
			$this->expopulate();
	}

	/**
	 * Populate extended fields loaded from the ShoppMetaObject
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function expopulate () {
		if ( ! is_object($this->value) ) return;
		$properties = $this->value;
		$this->copydata($properties);
		unset($this->value);
	}

	/**
	 * Save the object back to the database
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function save () {
		if (!empty($this->_xcols)) {
			$value = new stdClass();
			foreach ((array)$this->_xcols as $col)
				$value->{$col} = $this->{$col};
			$this->value = $value;
		}
		parent::save();
	}

} // END class Meta

/**
 * MetasetObject
 *
 * Constructs a runtime object from a set of meta records
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage meta
 **/
abstract class MetasetObject extends ShoppDatabaseObject {
	static $table = 'meta';

	public $_table = false;	// Fully qualified table name
	public $_loaded = false;	// If the record is successfully loaded
	public $_meta = array();	// The meta record definitions
	public $_context = 'meta';	// The meta context
	public $_parent = 0;		// Linking reference to the root record
	public $_type = false;		// Type (class) of object

	public $id = false;		// The root record for the set

	function __construct ($id=false,$key='id') {
		$this->init(self::$table);
		$this->load($id,$key);
	}

	function init ($table,$key='id') {
		$this->_table = ShoppDatabaseObject::tablename(MetasetObject::$table);
		$this->_type = get_class($this);
		$properties = array_keys(get_object_vars($this));
		$this->_properties = array_filter($properties,array('MetasetObject','_ignore_'));
	}

	function load () {

		$args = func_get_args();
		if (empty($args[0])) return false;
		if (is_array($args[0])) {
			foreach ($args[0] as $key => $id)
				$where .= ($where == ""?"":" AND ")."$key='".sDB::escape($id)."'";
		} else $where = "{$args[1]}='{$args[0]}' OR (parent={$args[0]} AND context='meta')";

		$r = sDB::query("SELECT * FROM $this->_table WHERE $where",'array');

		foreach ($r as $row) {
			$meta = new ShoppMetaObject();
			$meta->populate($row,'',array());
			$this->_meta[$meta->name] = $meta;

			// Seed properties
			$property = $meta->name;
			if ($property[0] != "_" && in_array($property,$this->_properties))
				$this->{$property} = $meta->value;

		}

		if (count($row) == 0) $this->_loaded = false;
		$this->_loaded = true;

		return $this->loaded;
	}

	/**
	 * Saves updates or creates records for the defined object properties
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function save ($op='update') {

		if (empty($this->id)) {
			$meta = new ShoppMetaObject();
			$meta->parent = 0;
			$meta->context = $this->_context;
			$meta->type = $this->_type;
			$meta->name = 'id';
			$meta->value = $meta->save();
			$this->_parent = $meta->value;
			$this->id = $meta->value;
			$this->_meta['id'] = $meta;
		}

		// Go through each data property of the object
		foreach(get_object_vars($this) as $property => $value) {
			if ($property[0] == "_") continue; // Skip mapping properties
			if (!isset($this->_meta[$property])) {
				$meta = new ShoppMetaObject();
				$meta->parent = $this->_parent;
				$meta->context = $this->_context;
				$meta->type = $this->_type;
				$meta->name = $property;
				$meta->value = $value;
				$this->_meta[$property] = $meta;
			} else $this->_meta[$property]->value = $value;
			$this->_meta[$property]->save();
		}

	}

	/**
	 * Deletes the entire set of meta entries for the combined record
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	function delete () {
		// Delete records
		if ( ! empty($this->id) && ! empty($this->_parent) )
			return sDB::query("DELETE FROM $this->_table WHERE (id='$this->_parent' AND parent=0 AND context='$this->_meta') OR (parent='$this->_parent' AND context='$this->_meta')");
		else return false;
	}

	function _ignore_ ($property) {
		return ($property[0] != "_");
	}

}

/**
 * ObjectMeta
 *
 * Loads a group of meta data records that have been attached to another object
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage meta
 **/
class ObjectMeta {
	static $table = "meta";

	public $_loaded = false;
	public $meta = array();
	public $named = array();

	function __construct ($parent=false,$context='product',$type=false,$sort='sortorder') {
		$this->_table = ShoppDatabaseObject::tablename(self::$table);

		$params = array(
			'parent' => $parent,
			'context' => $context
		);

		if ($type !== false) $params['type'] = $type;
		if ($parent !== false) $this->load($params);
	}

	function load () {

		$args = func_get_args();
		if (empty($args[0])) return false;
		if (!is_array($args[0])) return false;

		$where = "";
		foreach ($args[0] as $key => $id)
			$where .= ($where == ""?"":" AND ")."$key='".sDB::escape($id)."'";

		$r = sDB::query("SELECT * FROM $this->_table WHERE $where",'array');

		foreach ($r as $row) {
			$meta = new ShoppMetaObject();
			$meta->populate($row,'',array());

			$this->meta[$meta->id] = $meta;
			$this->named[$meta->name] =& $this->meta[$meta->id];
		}

		if (isset($row) && count($row) == 0) $this->_loaded = false;
		$this->_loaded = true;

		return $this->_loaded;
	}

	function is_empty () {
		if (!$this->_loaded) return true;
		return (empty($this->meta));
	}

}
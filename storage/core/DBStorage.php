<?php
/**
 * DBStorage
 *
 * Provides database storage in the asset table
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, February 18, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @version 1.0
 * @since 1.1
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * DBStorage
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class DBStorage extends StorageModule implements StorageEngine {

	public $_table = 'asset';
	public $_metatable = 'meta';
	public $_key = 'id';

	public function __construct () {
		parent::__construct();
		$this->name = Shopp::__('Database');
		$this->_table = ShoppDatabaseObject::tablename($this->_table);
		$this->_metatable = ShoppDatabaseObject::tablename($this->_metatable);
	}

	/**
	 * Save an asset to the database
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param Asset $asset Asset object the data is associated with
	 * @param string $data Binary data or path to the file to be saved
	 * @param string $type (optional) Type of data provided - binary (default) or file
	 * @return string|boolean A URI for the resource or false if failed
	 **/
	public function save ( $asset, $data, $type = 'binary' ) {

		if ( empty($data) ) return false;

		if ( 'binary' != $type ) {
			if ( ! is_readable($data) ) die("Could not read the file."); // Die because we can't use ShoppError
			$data = file_get_contents($data);
		}

		$data = sDB::escape($data);

		if ( ! $asset->id ) $uri = sDB::query("INSERT $this->_table SET data='$data'");
		else sDB::query("UPDATE $this->_table SET data='$data' WHERE $this->_key='$asset->uri'");

		if ( isset($uri) ) return $uri;
		return false;
	}

	/**
	 * Gets the size and mimetype meta of a stored asset
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $uri The URI for the resource
	 * @param string $filename (optional) File name of the asset
	 * @return array A named list of the stored file size and mimetype
	 **/
	public function meta ( $uri = false, $filename = false ) {
		$_ = array();
		if ( empty($uri) ) return $_;

		$file = sDB::query("SELECT LENGTH(data) AS size FROM $this->_table WHERE $this->_key='$uri' LIMIT 1");

		if ( $file && isset($file->size) ) $_['size'] = $file->size;
		if ( $filename !== false ) $_['mime'] = file_mimetype(false, $filename);

		return $_;
	}

	/**
	 * Determine if the provided resource exists
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $uri The URI for the resource
	 * @return boolean True if the resource exists
	 **/
	public function exists ( $uri ) {
		if ( strpos($uri, '.') !== false || (int)$uri == 0 ) return false;
		$file = sDB::query("SELECT id FROM $this->_table WHERE $this->_key='$uri' LIMIT 1");
		return ( ! empty($file) );
	}

	/**
	 * Load the binary data of a specified resource
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $uri The URI for the resource
	 * @return string Binary data
	 **/
	public function load ( $uri ) {
		if ( ! $uri ) return false;
		$file = sDB::query("SELECT * FROM $this->_table WHERE $this->_key='$uri' LIMIT 1");
		if ( empty($file) ) {
			shopp_error(Shopp::__( 'The requested asset could not be loaded from the database.' ), SHOPP_ADMIN_ERR);
			return false;
		}
		return $file->data;
	}

} // END class DBStorage

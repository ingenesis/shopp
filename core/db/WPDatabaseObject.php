<?php
/**
 * WPDatabaseObject.php
 *
 * Integrates Shopp ShoppDatabaseObjects with WordPress data tables
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class WPDatabaseObject extends ShoppDatabaseObject {

	public $post_author = '';

	/**
	 * Builds a table name from the defined WP table prefix
	 *
	 * @since 1.0
	 *
	 * @param string $table The base table name
	 * @return string The full, prefixed table name
	 **/
	static function tablename( $table = '' ) {
		global $wpdb;
		return $wpdb->get_blog_prefix() . $table;
	}

	/**
	 * Adds the save_post event to Shopp custom post saves
	 *
	 * @since 1.2
	 *
	 * @return void
	 **/
	function save() {
		parent::save();
		do_action('save_post', $this->id, get_post($this->id), $update = true);
	}

}
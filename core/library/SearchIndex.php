<?php
/**
 * SearchIndex.php
 *
 * Search index controller
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Library/Controller
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppSearchIndexController {

	/**
	 * Ajax reindex handler
	 *
	 * @return void
	 **/
	public static function reindex () {

		check_admin_referer('wp_ajax_shopp_rebuild_search_index');

		add_action('shopp_rebuild_search_index_init', array(__CLASS__, 'init'), 10, 3);
		add_action('shopp_rebuild_search_index_progress', array(__CLASS__, 'progress'), 10, 3);
		add_action('shopp_rebuild_search_index_completed', array(__CLASS__, 'completed'), 10, 3);

		self::clean();
		self::build();

		wp_die('', '', array('response' => 200));

	}

	/**
	 * Initializes the progress reporting iframe content
	 *
	 * Provides AJAX-related data
	 *
	 * @access private
	 * @param int $indexed The number of entries indexed
	 * @param int $total The total number of entries to index
	 * @param int $start The start timetamp of the index process
	 * @return void
	 **/
	public static function init ( $indexed, $total, $start ) {
		header('X-Accel-Buffering: no');
		header('Content-Encoding: none');
		echo str_pad('<html><body><script type="text/javascript">var indexProgress = 0;</script>' . "\n", 4096, ' ');
		@ob_flush();
		@flush();
	}

	/**
	 * Provides index progress updates to iframe content
	 *
	 * AJAX-related data
	 *
	 * @access private
	 * @param int $indexed The number of entries indexed
	 * @param int $total The total number of entries to index
	 * @param int $start The start timetamp of the index process
	 * @return void
	 **/
	public static function progress ( $indexed, $total, $start ) {
		if ( $total == 0 ) return;
		echo str_pad('<script type="text/javascript">indexProgress = ' . $indexed/(int)$total . ';</script>' . "\n", 4096, ' ');
		if ( ob_get_length() ) {
			@ob_flush();
			@flush();
		}
	}

	/**
	 * Closes the index progress data in the iframe
	 *
	 * Provides AJAX-related data
	 *
	 * @access private
	 * @param int $indexed The number of entries indexed
	 * @param int $total The total number of entries to index
	 * @param int $start The start timetamp of the index process
	 * @return void
	 **/
	public static function completed ( $indexed, $total, $start ) {
		echo str_pad('</body><html>'."\n", 4096, ' ');
		if ( ob_get_length() )
			@ob_end_flush();
	}

	/**
	 * Build the Shopp product search index
	 *
	 * @api
	 * @since 1.3
	 *
	 * @return boolean Status of rebuild
	 **/
	public static function build () {
		global $wpdb;

		new ContentParser();

		$set = 10; // Process 10 at a time

		$from = "FROM $wpdb->posts";
		$where = "WHERE post_status='publish' AND post_type='" . ShoppProduct::$posttype . "'";

		$total = sDB::query("SELECT count(*) AS products,now() as start $from $where");
		if ( empty($total->products) ) return false;

		set_time_limit(0); // Prevent timeouts

		$indexed = 0;
		do_action_ref_array('shopp_rebuild_search_index_init', array($indexed, $total->products, $total->start));
		for ( $i = 0; $i * $set < $total->products; $i++ ) { // Outer loop to support buffering
			$products = sDB::query("SELECT ID $from $where LIMIT " . ($i * $set) . ",$set", 'array', 'col', 'ID');
			foreach ( $products as $id ) {
				$Indexer = new IndexProduct($id);
				$Indexer->index();
				$indexed++;
				do_action_ref_array('shopp_rebuild_search_index_progress', array($indexed, $total->products, $total->start));
			}
		}

		do_action_ref_array('shopp_rebuild_search_index_completed', array($indexed, $total->products, $total->start));

		return true;
	}

	/**
	 * Destroy the entire product search index
	 *
	 * @api
	 * @since 1.3
	 *
	 * @return void
	 **/
	public static function clean () {

		$index_table = ShoppDatabaseObject::tablename(ContentIndex::$table);
		if ( sDB::query("DELETE FROM $index_table") ) return true;

		return false;

	}

}
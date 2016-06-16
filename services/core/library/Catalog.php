<?php
/**
 * Catalog class
 *
 * Catalog navigational experience data manager
 *
 * @author Jonathan Davis
 * @version 1.3
 * @since 1.0
 * @copyright Ingenesis Limited, April 2013
 * @package Shopp
 * @subpackage catalog
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCatalog {

	public $categories = array();
	public $outofstock = false;

	public function __construct () {
		$this->outofstock = shopp_setting_enabled('outofstock_catalog');
	}

	/**
	 * Load categories from the catalog index
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param array $loading (optional) Loading options for building the query
	 * @param boolean $showsmart (optional) Include smart categories in the listing
	 * @param boolean $results (optional) Return the raw structure of results without aggregate processing
	 * @return boolean|object True when categories are loaded and processed, object of results when $results is set
	 **/
	public function load_categories ( array $loading = array(), $showsmart = false, $results = false ) {

		$terms = get_terms(ProductCategory::$taxon, $loading);

		foreach ( (array)$terms as $term )
			$this->categories[] = new ProductCategory($term);

		if ( in_array($showsmart, array('before', 'after')) )
			$this->collections($showsmart);

	}

	/**
	 * Returns a list of known built-in smart categories
	 *
	 * Operates on the list of already loaded categories in the $this->category property
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param string $method Add smart categories 'before' the list of the loaded categores or 'after' (defaults after)
	 * @return void
	 **/
	public function collections ( $method = 'after' ) {
		$Shopp = Shopp::object();
		$collections = $Shopp->Collections;

		if ( 'before' == $method) krsort($collections);
		else ksort($collections);

		foreach ( $collections as $Collection ) {
			$auto = get_class_property($Collection, '_menu');
			if ( ! $auto ) continue;
			$category = new $Collection( array('noload' => true) );
			switch($method) {
				case 'before': array_unshift($this->categories, $category); break;
				default: array_push($this->categories, $category);
			}
		}
	}

	/**
	 * Load the tags assigned to products across the entire catalog
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.0
	 *
	 * @param array $limits Query limits in the format of [offset,count]
	 * @return boolean True when tags are loaded
	 **/
	public function load_tags ( array $limits = array() ) {

		if ( ! empty($limits) ) $limit = " LIMIT {$limits[0]},{$limits[1]}";
		else $limit = '';

		$query = "SELECT t.*,count(sc.product) AS products FROM $this->_table AS sc LEFT JOIN $tagtable AS t ON sc.parent=t.id WHERE sc.taxonomy='$taxonomy' GROUP BY t.id ORDER BY t.name ASC$limit";
		$this->tags = sDB::query($query,'array');
		return true;
	}

	/**
	 * Load a any category from the catalog including smart categories
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param string|int $category The identifying element of a category (by id/slug or uri)
	 * @param array $options (optional) Any shopp() tag-compatible options to pass on to smart categories
	 * @return object The loaded Category object
	 **/
	static function load_collection ( $slug, array $options = array() ) {
		$Shopp = Shopp::object();

		foreach ( (array)$Shopp->Collections as $Collection ) {
			$slugs = SmartCollection::slugs($Collection);
			if ( in_array($slug, $slugs) ) {
				return new $Collection($options);
			}
		}

		$key = 'id';
		if ( ! preg_match('/^\d+$/', $slug) ) $key = 'slug';
		return new ProductCategory($slug, $key);

	}

}
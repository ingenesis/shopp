<?php
/**
 * Core
 *
 * Interface for getting and setting global objects.
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February 25, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppProduct - get and set the global Product object
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @param Product (optional) $Object the product object to set to the global context.
 * @return mixed if the global Product context isn't set, bool false will be returned, otherwise the global Product object will be returned
 **/
function ShoppProduct ( ShoppProduct $Object = null ) {
	$Shopp = Shopp::object();
	if ( isset($Object) )
		$Shopp->Product = $Object;
	return $Shopp->Product;
}

/**
 * ShoppCustomer - get and set the global Customer object
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param Customer $Object (optional) the specified Customer object
 * @return Customer the current global customer object
 **/
function ShoppCustomer ( $Object = false ) {
	$Order = ShoppOrder();
	if ( $Object && is_a($Object, 'Customer') )
		$Order->Customer = $Object;
	return $Order->Customer;
}

/**
 * ShoppCollection - get and set the global Collection object (ie. ProductCategory, SmartCollection)
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param Collection (optional) $Object the Collection object to set to the global context.
 * @return mixed if the global Collection context isn't set, bool false will be returned, otherwise the global Collection object will be returned
 **/
function ShoppCollection ( ProductCollection $Object = null ) {
	$Shopp = Shopp::object();
	if ( isset($Object) ) $Shopp->Category = $Object;
	return $Shopp->Category;
}

/**
 * ShoppCatalog - get and set the global Catalog object
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param Catalog (optional) $Object the Catalog object to set to the global context.
 * @return mixed if the global Catalog context isn't set, bool false will be returned, otherwise the global Catalog object will be returned
 **/
function ShoppCatalog ( ShoppCatalog $Object = null ) {
	$Shopp = Shopp::object();
	if ( isset($Object) ) $Shopp->Catalog = $Object;
	if ( ! $Object && ! $Shopp->Catalog ) $Shopp->Catalog = new ShoppCatalog();
	return $Shopp->Catalog;
}

/**
 * ShoppPurchase - get and set the global Purchase object
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param Purchase (optional) $Object the Catalog object to set to the global context.
 * @return mixed if the global Purchase context isn't set, bool false will be returned, otherwise the global Purchase object will be returned
 **/
function ShoppPurchase ( $Object = false ) {
	$Shopp = Shopp::object();
	if (empty($Shopp)) return false;
	if ($Object !== false) $Shopp->Purchase = $Object;
	return $Shopp->Purchase;
}

/**
 * Get and set the Order object
 *
 * @author Jonathan Davis
 * @since 1.0
 *
 * @return Order
 **/
function ShoppOrder ( $Object = false ) {
	$Shopp = Shopp::object();
	if (empty($Shopp)) return false;
	if ($Object !== false) $Shopp->Order = $Object;
	return $Shopp->Order;
}


/**
 * Helper to access the Shopp settings registry
 *
 * @author Jonathan Davis
 * @since 1.1
 *
 * @return ShoppSettings The ShoppSettings object
 **/
function ShoppSettings () {
	return ShoppSettings::object();
}

function ShoppShopping() {
	return Shopping::object();
}

/**
 * Helper to access the error system
 *
 * @author Jonathan Davis
 * @since 1.0
 *
 * @return void
 **/
function ShoppErrors () {
	return ShoppErrors::object();
}

function ShoppErrorLogging () {
	return ShoppErrorLogging::object();
}

function ShoppErrorNotification () {
	return ShoppErrorNotification::object();
}

function ShoppErrorStorefrontNotices () {
	return ShoppErrorStorefrontNotices::object();
}

function ShoppPages () {
	return ShoppPages::object();
}

function shopp_get_page ( string $pagename ) {
	return ShoppPages()->get($pagename);
}

function shopp_register_page ( string $classname ) {
	ShoppPages()->register($classname);
}

/**
 * Detects ShoppError objects
 *
 * @author Jonathan Davis
 * @since 1.0
 *
 * @param object $e The object to test
 * @return boolean True if the object is a ShoppError
 **/
function is_shopperror ($e) {
	return ( get_class($e) == 'ShoppError' );
}

/**
 * Determines if the requested page is a catalog page
 *
 * Returns true for the catalog front page, Shopp taxonomy (categories, tags) pages,
 * smart collections and product pages
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_catalog_page ( $wp_query = false ) {
	return is_shopp_page('catalog', $wp_query);
}

/**
 * Determines if the requested page is the catalog front page.
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_catalog_frontpage ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }
	return is_shopp_page('catalog', $wp_query) && ! ( is_shopp_product($wp_query) || is_shopp_collection($wp_query) );
}

/**
 * Determines if the requested page is the account page.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_account_page ( $wp_query = false ) {
	return is_shopp_page('account', $wp_query);
}

/**
 * Determines if the requested page is the cart page.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_cart_page ( $wp_query = false ) {
	return is_shopp_page('cart', $wp_query);
}

/**
 * Determines if the requested page is the checkout page.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_checkout_page ( $wp_query = false ) {
	return is_shopp_page('checkout', $wp_query);
}

/**
 * Determines if the requested page is the confirm order page.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_confirm_page ( $wp_query = false ) {
	return is_shopp_page('confirm', $wp_query);
}

/**
 * Determines if the requested page is the thanks page.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_thanks_page ( $wp_query = false ) {
	return is_shopp_page('thanks', $wp_query);
}

/**
 * Determines if the requested page is the shopp search page.
 *
 * @author John Dillick
 * @since 1.2.1
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_search ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }
	return array_key_exists('s', $_REQUEST) && $wp_query->get('s_cs');
}

/**
 * Determines if the requested page is a Shopp page or if it matches a given Shopp page
 *
 * Also checks to see if the current loaded query is a Shopp product or product taxonomy.
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.0
 *
 * @param string $page (optional) System page name ID for the correct Storefront page {@see ShoppPages class}
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the provided WP_Query object
 * @return boolean
 **/
function is_shopp_page ( $page = false, $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query = $wp_the_query; }
	if ( empty($wp_query->query_vars) ) shopp_debug('Conditional is_shopp_page functions do not work before the WordPress query is run. Before then, they always return false.');

	$is_shopp_page = false;
	$Page = ShoppPages()->requested();

	if ( false === $page ) { // Check if the current request is a shopp page request
		// Product and collection pages are considered a Shopp page request
		if ( is_shopp_product($wp_query) || $wp_query->get('post_type') == ShoppProduct::$posttype ) $is_shopp_page = true;
		if ( is_shopp_collection($wp_query) ) $is_shopp_page = true;
		if ( false !== $Page ) $is_shopp_page = true;

	} elseif ( false !== $Page ) { // Check if the given shopp page name is the current request
		if ( $Page->name() == $page ) $is_shopp_page = true;
	}

	return $is_shopp_page;
}

/**
 * Determines if the passed WP_Query object is a Shopp storefront page, Shopp product collection, Shopp product taxonomy, or Shopp product query.
 * Alias for is_shopp_page() with reordered arguments, as it will usually be used for testing parse_query action referenced objects for custom WP_Query loops.
 *
 * @author John Dillick
 * @since 1.2.1
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @param string $page (optional) System page name ID for the correct Storefront page {@see ShoppPages class}
 * @return bool
 **/
function is_shopp_query ( $wp_query = false, $page = false ) {
	return is_shopp_page( $page, $wp_query );
}

/**
 * Determines if the current request is for any shopp smart collection, product taxonomy term, or search collection
 *
 * NOTE: This function will not identify PHP loaded collections, it only
 * compares the page request, meaning using is_shopp_collection on the catalog landing
 * page, even when the landing page (catalog.php) template loads the CatalogProducts collection
 * will return false, because CatalogProducts is loaded in the template and not directly
 * from the request.
 *
 * @author John Dillick, Jonathan Davis
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_collection ( $wp_query = false ) {
	return is_shopp_smart_collection($wp_query) || is_shopp_taxonomy($wp_query) || is_shopp_search($wp_query);
}

/**
 * Determines if the current request is for a registered dynamic Shopp collection
 *
 * NOTE: This function will not identify PHP loaded collections, it only
 * compares the page request, meaning using is_shopp_collection on the catalog landing
 * page, even when the landing page (catalog.php) template loads the CatalogProducts collection
 * will return false, because CatalogProducts is loaded in the template and not directly
 * from the request.
 *
 * @author John Dillick, Jonathan Davis
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_smart_collection ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }

	$slug = $wp_query->get('shopp_collection');
	if ( empty($slug) ) return false;

	$Shopp = Shopp::object();

	foreach ( (array)$Shopp->Collections as $Collection ) {
		$slugs = SmartCollection::slugs($Collection);
		if ( in_array($slug, $slugs) ) return true;
	}
	return false;
}

/**
 * Determines if the current request is for a Shopp product taxonomy
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_taxonomy ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query = $wp_the_query; }

	$object = $wp_query->get_queried_object();
	$taxonomies = get_object_taxonomies(Product::$posttype, 'names');

	return isset($object->taxonomy) && in_array($object->taxonomy, $taxonomies);
}

/**
 * Determines if the current request is for a Shopp product custom post type
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_product ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }
	$product = $wp_query->get(Product::$posttype);
	return (bool) $product;
}

function shopp_add_error ( $message, $level = null ) {
	if ( is_null($level) ) $level = SHOPP_ERR;
	return new ShoppError( $message, false, $level );
}

function shopp_add_notice ( $message ) {
	return shopp_add_error($message,SHOPP_ERR);
}

function shopp_debug ( $message, $backtrace = false ) {
	if ( ! SHOPP_DEBUG ) return false;
	if ( $backtrace ) $callstack = debug_caller();
	return shopp_add_error( $message . $backtrace, SHOPP_DEBUG_ERR );
}

function shopp_rebuild_search_index () {

	global $wpdb;

	new ContentParser();

	$set = 10; // Process 10 at a time
	$index_table = DatabaseObject::tablename(ContentIndex::$table);

	$total = DB::query("SELECT count(*) AS products,now() as start FROM $wpdb->posts WHERE post_type='" . ShoppProduct::$posttype . "'");
	if ( empty($total->products) ) false;

	set_time_limit(0); // Prevent timeouts

	$indexed = 0;
	do_action_ref_array('shopp_rebuild_search_index_init', array($indexed, $total->products, $total->start));
	for ( $i = 0; $i * $set < $total->products; $i++ ) { // Outer loop to support buffering
		$products = sDB::query("SELECT ID FROM $wpdb->posts WHERE post_type='" . ShoppProduct::$posttype . "' LIMIT " . ($i * $set) . ",$set", 'array', 'col', 'ID');
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

function shopp_empty_search_index () {

	$index_table = DatabaseObject::tablename(ContentIndex::$table);
	if ( sDB::query("DELETE FROM $index_table") ) return true;

	return false;

}
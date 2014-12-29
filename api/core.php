<?php
/**
 * Core
 *
 * Interface for getting and setting global objects.
 *
 * @copyright Ingenesis Limited, February 25, 2011
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Core
 * @version   1.0
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Get or set the global ShoppProduct object
 *
 * @api
 * @since 1.2
 *
 * @param ShoppProduct $Object (optional) The product object to set to the global context.
 * @return mixed if the global Product context isn't set, bool false will be returned, otherwise the global Product object will be returned
 **/
function ShoppProduct ( ShoppProduct $Object = null ) {
	$Shopp = Shopp::object();
	if ( isset($Object) )
		$Shopp->Product = $Object;
	return $Shopp->Product;
}

/**
 * Get and set the global ShoppCustomer object
 *
 * @api
 * @since 1.2
 *
 * @param ShoppCustomer $Object (optional) the specified ShoppCustomer object
 * @return ShoppCustomer the current global customer object
 **/
function ShoppCustomer ( $Object = false ) {
	$Order = ShoppOrder();
	if ( $Object && is_a($Object, 'ShoppCustomer') )
		$Order->Customer = $Object;
	return $Order->Customer;
}

/**
 * Get and set the global ShoppCollection object (ie. ShoppProductCategory, ShoppSmartCollection)
 *
 * @api
 * @since 1.2
 *
 * @param ShoppCollection $Object (optional) The ShoppCollection object to set to the global context.
 * @return mixed if the global ShoppCollection context isn't set, bool false will be returned, otherwise the global ShoppCollection object will be returned
 **/
function ShoppCollection ( ProductCollection $Object = null ) {
	$Shopp = Shopp::object();
	if ( isset($Object) ) $Shopp->Category = $Object;
	return $Shopp->Category;
}

/**
 * Get and set the global ShoppCatalog object
 *
 * @api
 * @since 1.2
 *
 * @param ShoppCatalog $Object (optional) the ShoppCatalog object to set to the global context.
 * @return mixed if the global ShoppCatalog context isn't set, bool false will be returned, otherwise the global ShoppCatalog object will be returned
 **/
function ShoppCatalog ( ShoppCatalog $Object = null ) {
	$Shopp = Shopp::object();
	if ( isset($Object) ) $Shopp->Catalog = $Object;
	if ( ! $Object && ! $Shopp->Catalog ) $Shopp->Catalog = new ShoppCatalog();
	return $Shopp->Catalog;
}

/**
 * Get and set the global ShoppPurchase object
 *
 * @api
 * @since 1.2
 *
 * @param ShoppPurchase $Object (optional) the ShoppPurchase object to set to the global context.
 * @return mixed if the global ShoppPurchase context isn't set, bool false will be returned, otherwise the global ShoppPurchase object will be returned
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
 * @api
 * @since 1.0
 *
 * @param ShoppOrder $Object (optional) Set the global ShoppOrder object
 * @return ShoppOrder The current global ShoppOrder object
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
 * @api
 * @since 1.1
 *
 * @return ShoppSettings The ShoppSettings object
 **/
function ShoppSettings () {
	return ShoppSettings::object();
}

/**
 * Helper to access the Shopp-ing session instance
 *
 * @api
 * @since 1.2
 *
 * @return Shopping
 **/
function ShoppShopping() {
	return Shopping::object();
}

/**
 * Helper to access the error system
 *
 * @api
 * @since 1.0
 *
 * @return ShoppErrors
 **/
function ShoppErrors () {
	return ShoppErrors::object();
}

/**
 * Provides the ShoppErrorLogging instance
 *
 * @since 1.3
 *
 * @return ShoppErrorLogging The running ShoppErrorLogging instance
 **/
function ShoppErrorLogging () {
	return ShoppErrorLogging::object();
}

/**
 * Provides the ShoppErrorNotification instance
 *
 * @since 1.3
 *
 * @return ShoppErrorNotification The running ShoppErrorNotification instance
 **/
function ShoppErrorNotification () {
	return ShoppErrorNotification::object();
}

/**
 * Provides the ShoppErrorStorefrontNotices instance
 *
 * @since 1.3
 *
 * @return ShoppErrorStorefrontNotices The running ShoppErrorStorefrontNotices instance
 **/
function ShoppErrorStorefrontNotices () {
	return ShoppErrorStorefrontNotices::object();
}

/**
 * The ShoppPages controller instance
 *
 * @since 1.3
 *
 * @return ShoppPages The running ShoppPages controller
 **/
function ShoppPages () {
	return ShoppPages::object();
}

/**
 * Get a specified ShoppPage
 *
 * @since 1.3
 *
 * @param string $pagename The name of the ShoppPage to retrieve
 * @return ShoppPage The requested ShoppPage if found, false otherwise
 **/
function shopp_get_page ( $pagename ) {
	return ShoppPages()->get($pagename);
}

/**
 * Register a new ShoppPage class to the ShoppPages controller
 *
 * @since 1.3
 *
 * @param string $classname The class name of the new type of ShoppPage
 * @return void
 **/
function shopp_register_page ( $classname ) {
	ShoppPages()->register($classname);
}

/**
 * Detects ShoppError objects
 *
 * @api
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
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_catalog_page ( $wp_query = false ) {
	return is_shopp_page('catalog', $wp_query);
}

if ( ! function_exists('is_catalog_page') ) {
	/**
	 * Determines if the requested page is a catalog page
	 *
	 * @deprecated Use is_shopp_catalog_page()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_catalog_page ( $wp_query = false ) {
		return is_shopp_catalog_page($wp_query);
	}
}

/**
 * Determines if the requested page is the storefront catalog page
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_catalog_frontpage ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }
	return is_shopp_page('catalog', $wp_query) && ! ( is_shopp_product($wp_query) || is_shopp_collection($wp_query) );
}

if ( ! function_exists('is_catalog_frontpage') ) {
	/**
	 * Determines if the requested page is the storefront catalog page
	 * @deprecated Use is_shopp_catalog_frontpage()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_catalog_frontpage ( $wp_query = false ) {
		return is_shopp_catalog_frontpage($wp_query);
	}
}

/**
 * Determines if the requested page is the account page.
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_account_page ( $wp_query = false ) {
	return is_shopp_page('account', $wp_query);
}

if ( ! function_exists('is_account_page') ) {
	/**
	 * Determines if the requested page is the account page.
	 * @deprecated Use is_shopp_account_page()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_account_page ( $wp_query = false ) {
		return is_shopp_account_page($wp_query);
	}
}

/**
 * Determines if the requested page is the cart page.
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_cart_page ( $wp_query = false ) {
	return is_shopp_page('cart', $wp_query);
}

if ( ! function_exists('is_cart_page') ) {
	/**
	 * Determines if the requested page is the cart page.
	 * @deprecated Use is_shopp_cart_page()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_cart_page ( $wp_query = false ) {
		return is_shopp_cart_page($wp_query);
	}
}

/**
 * Determines if the requested page is the checkout page.
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_checkout_page ( $wp_query = false ) {
	return is_shopp_page('checkout', $wp_query);
}

if ( ! function_exists('is_checkout_page') ) {
	/**
	 * Determines if the requested page is the checkout page.
	 * @deprecated Use is_shopp_checkout_page()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_checkout_page ( $wp_query = false ) {
		return is_shopp_checkout_page($wp_query);
	}
}

/**
 * Determines if the requested page is the confirm order page.
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_confirm_page ( $wp_query = false ) {
	return is_shopp_page('confirm', $wp_query);
}

if ( ! function_exists('is_confirm_page') ) {
	/**
	 * Determines if the requested page is the confirm order page.
	 * @deprecated Use is_shopp_confirm_page()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_confirm_page ( $wp_query = false ) {
		return is_shopp_confirm_page($wp_query);
	}
}

/**
 * Determines if the requested page is the thanks page.
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_thanks_page ( $wp_query = false ) {
	return is_shopp_page('thanks', $wp_query);
}

if ( ! function_exists('is_thanks_page') ) {
	/**
	 * Determines if the requested page is the thanks page.
	 * @deprecated Use is_shopp_thanks_page()
	 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
	 **/
	function is_thanks_page ( $wp_query = false ) {
		return is_shopp_thanks_page($wp_query);
	}
}

/**
 * Determines if the requested page is the shopp search page.
 *
 * @api
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
 * @api
 * @since 1.0
 *
 * @param string $page (optional) System page name ID for the correct ShoppStorefront page {@see ShoppPages class}
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
 * @api
 * @since 1.2.1
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @param string $page (optional) System page name ID for the correct ShoppStorefront page {@see ShoppPages class}
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
 * @api
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
 * @api
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
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_taxonomy ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query = $wp_the_query; }

	if ( empty($wp_query->tax_query) ) return false; // No taxonomy request {@see #2748}

	if ( ! isset($wp_query->post) ) $wp_query->post = null; // Prevent PHP notices

	$object = $wp_query->get_queried_object();

	$taxonomies = get_object_taxonomies(ShoppProduct::$posttype, 'names');

	return isset($object->taxonomy) && in_array($object->taxonomy, $taxonomies);
}

/**
 * Determines if the current request is for a Shopp product custom post type
 *
 * @api
 * @since 1.2
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_shopp_product ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }
	$product = $wp_query->get(ShoppProduct::$posttype);
	return (bool) $product;
}

/**
 * Determines if the current request is for a single Shopp product
 *
 * @api
 * @since 1.3
 *
 * @param WP_Query $wp_query (optional) will use the global wp_query by default if false, or the WP_Query object to evaluation
 * @return boolean
 **/
function is_single_product ( $wp_query = false ) {
	if ( false === $wp_query ) { global $wp_the_query; $wp_query =& $wp_the_query; }

	return ( is_shopp_product($wp_query) && $wp_query->is_single() );
}


/**
 * Add an error to Shopp
 *
 * @api
 * @since 1.3
 *
 * @param string $message The error message to add
 * @param int $level The error type (SHOPP_ERR, SHOPP_ADMIN_ERR)
 * @return ShoppError The ShoppError object
 **/
function shopp_add_error ( $message, $level = null ) {
	if ( is_null($level) ) $level = SHOPP_ERR;
	return new ShoppError( $message, false, $level );
}

/**
 * Add an error message that will be displayed to visitors on the storefront
 *
 * @api
 * @since 1.3
 *
 * @param string $message The error message to add
 * @return ShoppError The ShoppError object
 **/
function shopp_add_notice ( $message ) {
	return shopp_add_error($message, SHOPP_ERR);
}

/**
 * Add a developer debug error message to the Shopp log file
 *
 * @api
 * @since 1.3
 *
 * @param string $message The error message to add
 * @param boolean $backtrace Include the call stack in the logged message
 * @return ShoppError The ShoppError object
 **/
function shopp_debug ( $message, $backtrace = false ) {
	if ( ! SHOPP_DEBUG ) return false;
	$callstack = false;
	if ( $backtrace )
		$callstack = ' ' . debug_caller();
	return shopp_add_error( $message . $callstack, SHOPP_DEBUG_ERR );
}

/**
 * Rebuild the Shopp product search index
 *
 * @api
 * @since 1.3
 *
 * @return void
 **/
function shopp_rebuild_search_index () {

	global $wpdb;

	new ContentParser();

	$set = 10; // Process 10 at a time
	$index_table = ShoppDatabaseObject::tablename(ContentIndex::$table);

	$total = sDB::query("SELECT count(*) AS products,now() as start FROM $wpdb->posts WHERE post_type='" . ShoppProduct::$posttype . "'");
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

/**
 * Destroy the entire product search index
 *
 * @api
 * @since 1.3
 *
 * @return void
 **/
function shopp_empty_search_index () {

	$index_table = ShoppDatabaseObject::tablename(ContentIndex::$table);
	if ( sDB::query("DELETE FROM $index_table") ) return true;

	return false;

}
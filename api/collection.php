<?php
/**
 * Collection API
 *
 * @copyright Ingenesis Limited, February 25, 2011
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Collection
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Registers a smart collection of products
 *
 * @api
 * @since 1.2
 *
 * @param string $name Class name of the smart collection
 * @return void
 **/
function shopp_register_collection ( $name = '' ) {

	if ( empty($name) ) {
		shopp_debug(__FUNCTION__ . ' failed: Collection name required.');
		return false;
	}

	$Shopp = Shopp::object();
	$namespace = apply_filters('shopp_smart_collections_slug', SmartCollection::$namespace);
	$permastruct = SmartCollection::$taxon;

	$slugs = SmartCollection::slugs($name);
	$slug = $slugs[0];
	$Shopp->Collections[ $slug ] = $name;

	do_action('shopp_register_collection', $name, $slug);
	$slugs = SmartCollection::slugs($name);

	add_rewrite_tag( "%$permastruct%", "([^/]+)" );
	add_permastruct( $permastruct, ShoppPages()->baseslug() . "/$namespace/%shopp_collection%", false );

	add_filter( $permastruct . '_rewrite_rules', array('ProductCollection', 'pagerewrites') );

	$apicall = function ( $result, $options, $O ) use ( $name ) {
		ShoppCollection( new $name($options) );
		return ShoppStorefrontThemeAPI::category($result, $options, $O);
	};

	foreach ( (array)$slugs as $collection) {
		$collection = str_replace(array('-','_'), '', $collection); // Sanitize slugs
		add_filter( 'shopp_themeapi_storefront_' . $collection . 'products', $apicall, 10, 3 ); // @deprecated
		add_filter( 'shopp_themeapi_storefront_' . $collection . 'collection', $apicall, 10, 3 );
	}

	// Add special default permalink handling for collection URLs (only add it once)
	global $wp_rewrite;
	if ( ! $wp_rewrite->using_permalinks() && false === has_filter('term_link', array('SmartCollection', 'defaultlinks')) )
		add_filter('term_link', array('SmartCollection', 'defaultlinks'), 10, 3);

}

/**
 * Register a Shopp product taxonomy
 *
 * @api
 * @since 1.2
 *
 * @param string $taxonomy The taxonomy name
 * @param array $args register_taxonomy arguments
 * @return void
 **/
function shopp_register_taxonomy ( $taxonomy, $args = array() ) {
	$taxonomy     = sanitize_key($taxonomy);
	$rewrite_slug = $taxonomy;
	$taxonomy     = "shopp_$taxonomy";
	if ( isset($args['rewrite'] ) && isset($args['rewrite']['slug']) ) $rewrite_slug = $args['rewrite']['slug'];
	if ( ! isset($args['rewrite']) ) $args['rewrite'] = array();

	$args['rewrite']['slug'] = SHOPP_NAMESPACE_TAXONOMIES ? ShoppPages()->baseslug() . '/' . $rewrite_slug : $rewrite_slug;
	register_taxonomy($taxonomy, ShoppProduct::$posttype, $args);
}

/**
 * Add a product category
 *
 * @api
 * @since 1.2
 *
 * @param string $name (required) The category name.
 * @param string $description (optional) The category description.
 * @param int $parent (optional) Parent category id.
 * @return bool|int false on error, int category id on success.
 **/
function shopp_add_product_category ( $name = '', $description = '', $parent = false ) {
	if ( empty($name) ) {
		shopp_debug(__FUNCTION__ . ' failed: Category name required.');
		return false;
	}

	$args = array();
	$args['description'] = ( $description ? $description : '');
	$args['parent']      = ( $parent ? $parent : 0 );

	$term = wp_insert_term($name, ProductCategory::$taxon, $args);

	if ( ! is_wp_error($term) && isset($term['term_id']) ) {
		$hierarchy = _get_term_hierarchy(ProductCategory::$taxon);
		if ( $parent && (! in_array($parent, array_keys($hierarchy)) || ! in_array($term['term_id'], $hierarchy[ $parent ])) ) {
			// update hierarchy if necessary
			if ( ! isset($hierarchy[ $parent ])) $hierarchy[ $parent ] = array();
			$hierarchy[ $parent ][] = $term['term_id'];
			update_option(ProductCategory::$taxon . '_children', $hierarchy);
		}

		return $term['term_id'];
	}
	return false;
}

/**
 * Get a ShoppProductCategory object
 *
 * @api
 * @since 1.2
 *
 * @param int $cat the category id
 * @param array $options (optional) loading options
 * @return bool|ProductCategory returns false on error, ProductCategory on success
 **/
function shopp_product_category ( $cat = false, $options = array() ) {
	if ( ! $cat ) {
		shopp_debug(__FUNCTION__ . ' failed: Category id required.');
		return false;
	}

	$Cat = new ProductCategory( (int) $cat );
	if ( ! $Cat->id ) return false;

	$defaults = array(
		'pagination' => false
	);
	$options = array_merge($defaults, $options);

	$Cat->load($options);

	return $Cat;
}

/**
 * Remove a product category by id
 *
 * @api
 * @since 1.2
 *
 * @param int $id (required) The category id
 * @return bool true on success, false on failure
 **/
function shopp_rmv_product_category ( $id = false ) {
	if ( ! $id ) {
		shopp_debug(__FUNCTION__ . ' failed: Category id required.');
		return false;
	}
	$Category = new ProductCategory($id);
	if ( empty($Category->id) ) return false;

	return $Category->delete();
}

/**
 * Get a ShoppProductTag object
 *
 * @api
 * @since 1.2
 *
 * @param mixed $tag tag id or tag name
 * @param array $options (optional) loading options
 * @return bool|ProductTag returns false on error, ProductTag object on success
 **/
function shopp_product_tag ( $tag = false, $options = array() ) {
	if ( ! $tag ) {
		shopp_debug(__FUNCTION__ . ' failed: Tag id or string required.');
		return false;
	}

	$tag = is_numeric($tag) ? (int) $tag : $tag;
	$Tag = new ProductTag( $tag, is_string($tag) ? 'name' : 'id' );

	if ( ! $Tag->id ) return false;

	$Tag->load($options);
	return $Tag;
}


/**
 * Add a product tag term.
 *
 * If the tag already exists, will return the id of that tag.
 *
 * @api
 * @since 1.2
 *
 * @param string $tag (required) The tag term to add.
 * @return bool/int - The tag id, false on failure
 **/
function shopp_add_product_tag ( $tag = '' ) {
	if ( ! $tag  ) {
		shopp_debug(__FUNCTION__ . ' failed: Tag name required.');
		return false;
	}
	$Tag       = new ProductTag( array('name' => $tag) );
	$Tag->name = $tag;

	if ( empty($Tag->id) ) $Tag->save();

	return $Tag->id;
}

/**
 * Remove a tag term by name or id
 *
 * @api
 * @since 1.2
 *
 * @param mixed $tag (required) int tag term_id or string tag name
 * @return bool true on success, false on failure
 **/
function shopp_rmv_product_tag ( $tag = '' ) {
	if ( ! $tag ) {
		shopp_debug(__FUNCTION__ . ' failed: Tag name or id required.');
		return false;
	}

	$tag = is_numeric($tag) ? (int) $tag : $tag;
	$Tag = new ProductTag( $tag, is_string($tag) ? 'name' : 'id' );

	if ( empty($Tag->id) ) return false;

	$success = shopp_rmv_meta ( $Tag->id, 'tag' );
	return $success && $Tag->delete();
}

/**
 * Create a new taxonimical term.
 *
 * @api
 * @since 1.2
 *
 * @param string $term (required) the new term name.
 * @param string $taxonomy (optional default:shopp_category) The taxonomy name.  The taxonomy specified must be of the Shopp product object type.
 * @param int $parent (option default:0) the parent term id.
 * @return int|bool term id on success, false on failure
 **/
function shopp_add_product_term ( $term = '', $taxonomy = 'shopp_category', $parent = false ) {
	if ( ! in_array($taxonomy, get_object_taxonomies(ShoppProduct::$posttype) ) ) {
		shopp_debug(__FUNCTION__ . " failed: $taxonomy not a shopp product taxonomy.");
		return false;
	}
	if ( ! $term ) {
		shopp_debug(__FUNCTION__ . ' failed: term required.');
		return false;
	}

	$term_array = term_exists( $term, $taxonomy, $parent ? $parent : 0 );
	if ( ! $term_array )
		$term_array = wp_insert_term( $term, $taxonomy, ($parent ? array('parent' => $parent) : array()) );

	if ( is_array($term_array) && $term_array['term_id'] ) {
		$hierarchy = _get_term_hierarchy($taxonomy);
		if ( $parent && (! in_array($parent, array_keys($hierarchy)) || ! in_array($term_array['term_id'], $hierarchy[ $parent ]) ) ) {
			// update hierarchy if necessary
			if ( ! isset($hierarchy[ $parent ])) $hierarchy[ $parent ] = array();
			$hierarchy[ $parent ][] = $term_array['term_id'];
			update_option("{$taxonomy}_children", $hierarchy);
		}

		return $term_array['term_id'];
	}
	return false;
}

/**
 * Get a ShoppProductTaxonomy object.
 *
 * @api
 * @since 1.2
 *
 * @param int $term the term id
 * @param string $taxonomy (optional default:shopp_category) the taxonomy name
 * @param array $options (optional) loading options
 * @return bool|ProductTaxonomy returns false on error, ProductTaxonomy object on success
 **/
function shopp_product_term ( $term = false, $taxonomy = 'shopp_category', $options = array() ) {
	global $ShoppTaxonomies;

	if ( ! $term ) {
		shopp_debug(__FUNCTION__ . ' failed: Term id required.');
		return false;
	}


	if ( in_array( $taxonomy, array_keys($ShoppTaxonomies) ) ) { // Shopp ProductTaxonomy class type
		$Term = new $ShoppTaxonomies[ $taxonomy ]($term);
	} else {
		$term = term_exists( $term, $taxonomy );
		if ( ! is_array($term) ) return false;

		$Term = new ProductTaxonomy;
		$Term->taxonomy = $taxonomy;
		$Term->load_term($term['term_id']);
	}

	if ( ! $Term->id ) return false;

	$Term->load($options);
	return $Term;
}

/**
 * Remove a taxonomical term
 *
 * @api
 * @since 1.2
 *
 * @param int $term (required) The term id
 * @param string $taxonomy (optional default=shopp_category) The taxonomy name the term belongs to.  The taxonomy specified must be of the Shopp product object type.
 * @return bool true on success, false on failure
 **/
function shopp_rmv_product_term ( $term = '', $taxonomy = 'shopp_category' ) {
	if ( ! in_array($taxonomy, get_object_taxonomies(ShoppProduct::$posttype) ) ) {
		shopp_debug(__FUNCTION__ . " failed: $taxonomy not a shopp product taxonomy.");
		return false;
	}
	if ( ! $term ) {
		shopp_debug(__FUNCTION__ . ' failed: term required.');
		return false;
	}

	return true === wp_delete_term( $term, $taxonomy );
}

/**
 * Get an array of all product categories
 *
 * @api
 * @since 1.2
 * @uses get_terms - defaults get=>all, hide_empty=>false
 *
 * @param array $args get_terms parameters
 * @return array ProductCategoy objects
 **/
function shopp_product_categories ( $args = array() ) {
	$defaults = array( 'get' => 'all', 'hide_empty' => false );
	$args = wp_parse_args ( $args, $defaults );
	$args['fields'] = 'ids';

	// load options for ProductTag object
	if ( isset($args['load']) ) {
		if ( is_array($args['load']) ) $load = $args['load'];
		unset($args['load']);
	}

	// index options
	$index_options = array('id','name','slug');
	$index = 'id';
	if ( isset($args['index']) && in_array($args['index'], $index_options)) {
		$index = $args['index'];
		unset($args['index']);
	}


	$terms = get_terms( ProductCategory::$taxon, $args );
	if ( ! is_array($terms) ) return false;

	$categories = array();
	foreach ( $terms as $term ) {
		$Cat = new ProductCategory($term);
		$categories[ $Cat->{$index} ] = $Cat;
		if ( isset($load) ) $categories[ $Cat->{$index} ]->load($load);
	}
	return $categories;
}

/**
 * Get an array of all product tags
 *
 * @api
 * @since 1.2
 * @uses get_terms - defaults get=>all, hide_empty=>false
 *
 * @param array $args get_terms parameters
 * @return array ProductTags objects
 **/
function shopp_product_tags ( $args = array() ) {
	$defaults = array( 'get' => 'all', 'hide_empty' => false );
	$args = wp_parse_args ( $args, $defaults );
	$args['fields'] = 'ids';

	// load options for ProductTag object
	if ( isset($args['load']) ) {
		if ( is_array($args['load']) ) $load = $args['load'];
		unset($args['load']);
	}

	// index options
	$index_options = array('id','name','slug');
	$index = 'id';
	if ( isset($args['index']) && in_array($args['index'], $index_options)) {
		$index = $args['index'];
		unset($args['index']);
	}

	$terms = get_terms( ProductTag::$taxon, $args );
	if ( ! is_array($terms) ) return false;

	$tags = array();
	foreach ( $terms as $term ) {
		$Tag = new ProductTag($term);
		$tags[ $Tag->{$index} ] = $Tag;
		if ( isset($load) ) $tags[ $Tag->{$index} ]->load($load);
	}

	return $tags;
}

/**
 * Get array of category objects that are children of specified category
 *
 * @api
 * @since 1.2
 *
 * @param int $category id of the category you wish to get subcategories for
 * @return array ProductCategory objects
 **/
function shopp_subcategories ( $category = 0, $args = array() ) {
	$defaults = array( 'get' => '', 'child_of' => $category );
	$args = wp_parse_args ( $args, $defaults );

	return shopp_product_categories ( $args );
}

/**
 * Get a list of product objects for the given category
 *
 * @api
 * @since 1.2
 *
 * @param int $category (required) The category id
 * @param array $options loading options
 * @return array Product objects
 **/
function shopp_category_products ( $category = 0, $options = array() ) {
	$defaults = array(
		'limit' => 1000,
		'debug' => false
	);

	$options = wp_parse_args($options, $defaults);

	if ( ! term_exists( (int) $category, ProductCategory::$taxon ) ) {
		shopp_debug(__FUNCTION__ . " failed: $category not a valid Shopp product category.");
		return false;
	}

	$Category = shopp_product_category( $category, $options );

	return ! empty($Category->products) ? $Category->products : array();
}

/**
 * Get a list of product objects for the given tag
 *
 * @api
 * @since 1.2
 *
 * @param int|string $tag (required) The product tag name/id
 * @param array $options loading options
 * @return array Product objects
 **/
function shopp_tag_products ( $tag = false, $options = array() ) {
	$defaults = array(
		'limit' => 1000,
		'debug' => false
	);

	$options = wp_parse_args($options, $defaults);

	if ( ! $term = term_exists( $tag, ProductTag::$taxon ) ) {
		shopp_debug(__FUNCTION__ . " failed: $tag not a valid Shopp product tag.");
		return false;
	}

	$Tag = new ProductTag( $term['term_id'] );
	$Tag->load( $options );

	return ! empty($Tag->products) ? $Tag->products : array();
}

/**
 * Get a list of product objects for the given term and taxonomy
 *
 * @api
 * @since 1.2
 *
 * @param int $term (required) The term id
 * @param string $taxonomy The taxonomy name
 * @param array $options loading options
 * @return array Product objects
 **/
function shopp_term_products ( $term = false, $taxonomy = 'shopp_category', $options = array() ) {
	$defaults = array(
		'limit' => 1000,
		'debug' => false
	);

	$options = wp_parse_args($options, $defaults);

	if ( ! taxonomy_exists( $taxonomy ) || ! in_array($taxonomy, get_object_taxonomies(ShoppProduct::$posttype) ) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid Shopp taxonomy $taxonomy.");
		return false;
	}

	if ( ! $term = term_exists( $term, $taxonomy ) ) {
		shopp_debug(__FUNCTION__ . " failed: $term not a valid Shopp $taxonomy term.");
		return false;
	}

	$Tax = new ProductTaxonomy();
	$Tax->id               = $term['term_id'];
	$Tax->term_taxonomy_id = $term['term_taxonomy_id'];
	$Tax->taxonomy         = $taxonomy;
	$Tax->load( $options );

	return ! empty($Tax->products) ? $Tax->products : array();
}

/**
 * Get a count of all products in the catalog
 *
 * @api
 * @since 1.2
 *
 * @param string $status (optional default:publish) the product's publish status
 * @return int number of products
 **/
function shopp_catalog_count ( $status = 'publish' ) {
	$C = wp_count_posts( ShoppProduct::$posttype );
	$counts = get_object_vars($C);

	if ( 'total' == $status ) {
		$total = 0;
		foreach ( $counts as $count ) $total += $count;
		return $total;
	}

	if ( isset($counts[ $status ]) ) return $counts[ $status ];

	return 0;
}

/**
 * Get a count of all products in the category
 *
 * @api
 * @since 1.2
 *
 * @param int $category (required) the category id
 * @param bool $children (optional default:false) include the children in the count
 * @return int number of products in the category
 **/
function shopp_category_count (	$category = 0, $children = false ) {
	if ( ! $category || ! ( $Category = new ProductCategory( (int) $category) ) || $category != $Category->id ) {
		shopp_debug(__FUNCTION__ . " failed: $category not a valid Shopp product category.");
		return false;
	}

	$count = $Category->count;
	if ( $children )
		foreach( shopp_subcategories($category) as $cat ) $count += $cat->count;

	return $count;
}

/**
 * Get count of sub categories in a product category
 *
 * @api
 * @since 1.2
 *
 * @param int $category (required) the category id
 * @return int count of subcategories
 **/
function shopp_subcategory_count ( $category = 0 ) {
	if ( ! term_exists( (int) $category, ProductCategory::$taxon ) ) {
		shopp_debug(__FUNCTION__ . " failed: $category not a valid Shopp product category.");
		return false;
	}

	$children = get_term_children( $category, ProductCategory::$taxon );

	return count($children);
}

/**
 * Get count of categories associated with a product
 *
 * @api
 * @since 1.2
 *
 * @param int $product (required) the product id
 * @return int count of categories
 **/
function shopp_product_categories_count ( $product ) {
	$Product = new ShoppProduct( $product );
	if ( empty($Product->id) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid product $product.");
		return false;
	}
	$terms = wp_get_post_terms( $product, ProductCategory::$taxon, array());

	return count($terms);
}
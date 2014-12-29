<?php
/**
 * Collection classes
 *
 * Library product collection models
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package Shopp
 * @since 1.0
 * @subpackage collections
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Defines base functionality for ProductCollection classes
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package collections
 **/
class ProductCollection implements Iterator {

	public $api = 'collection';
	public $slug = false;
	public $loaded = false;
	public $paged = false;
	public $pages = 1;
	public $pagination = false;
	public $tag = false;
	public $smart = false;
	public $filters = false;
	public $products = array();
	public $total = 0;
	public $pricerange = 'auto';

	private $_keys = array();
	private $_position = array();

	public function load ( array $options = array() ) {

		$thisclass = get_class($this);
		$slug = isset($this->slug) ? $this->slug : sanitize_key($thisclass);

		$Storefront = ShoppStorefront();
		$Shopping = ShoppShopping();
		$Processing = new ShoppProduct();
		$summary_table = ShoppDatabaseObject::tablename(ProductSummary::$table);

		$defaults = array(
			'columns' => false,		// Include extra columns (string) 'c.col1,c.col2…'
			'useindex' => false,	// FORCE INDEX to be used on the product table (string) 'indexname'
			'joins' => array(),		// JOIN tables array('INNER JOIN table AS t ON p.id=t.column')
			'where' => array(),		// WHERE query conditions array('x=y OR x=z','a!=b'…) (array elements are joined by AND
			'groupby' => false,		// GROUP BY column (string) 'column'
			'orderby' => false,		// ORDER BY columns ASC|DESC (string)
			'having' => array(),	// HAVING filters
			'limit' => false,		// Limit
			'order' => false,		// ORDER BY columns or named methods (string)
									// 'bestselling','highprice','lowprice','newest','oldest','random','chaos','title'

			'page' => false,		// Current page number to load
			'paged' => false,		// Entries per page to load
			'nostock' => null,		// Override to show products that are out of stock (string) 'on','off','yes','no'…
			'pagination' => true,	// Enable alpha pagination (string) 'alpha'
			'published' => true,	// Load published or include unpublished products (string) 'on','off','yes','no'…
			'ids' => false,			// Flag for loading product IDs only
			'adjacent' => false,	//
			'product' => false,		//
			'load' => array('coverimages'),		// Product data to load
			'inventory' => false,	// Flag for detecting inventory-based queries
			'taxquery' => false,	// Cross taxonomy queries
			'debug' => false		// Output the query for debugging
		);
		$loading = array_merge($defaults, $options);
		$loading = apply_filters("shopp_{$slug}_collection_load_options", $loading);
		extract($loading);

		// Setup pagination
		$this->paged = false;
		$this->pagination = (false === $paged)?shopp_setting('catalog_pagination'):$paged;
		$page = (false === $page)?get_query_var('paged'):$page;
		$this->page = ((int)$page > 0 || preg_match('/(0\-9|[A-Z])/',$page) )?$page:1;

		// Hard product limit per category to keep resources "reasonable"
		$hardlimit = apply_filters('shopp_category_products_hardlimit',1000);

		// Enforce the where parameter as an array
		if ( ! is_array($where) ) return shopp_debug('The "where" parameter for ' . __METHOD__ . ' must be formatted as an array.');

		// Inventory filtering
		if ( shopp_setting_enabled('inventory') && ( ( is_null($nostock) && ! shopp_setting_enabled('outofstock_catalog') ) || ( ! is_null($nostock) && ! Shopp::str_true($nostock) ) ) )
			$where[] = "( s.inventory='off' OR (s.inventory='on' AND s.stock > 0) )";

		if ( Shopp::str_true($published) ) $where[] = "p.post_status='publish'";

		// Multiple taxonomy queries
		if ( is_array($taxquery) ) {
			$tqdefaults = array(
				'relation' => 'AND',
				'include_children' => true,
			);
			$taxquery = array_merge($tqdefaults, $taxquery);

	 		$TQ = new WP_Tax_Query($taxquery);
	 		$sql = $TQ->get_sql( $Processing->_table, 'ID' );
			unset($TQ);
			$joins['taxquery'] = self::taxquery( $sql['join'] );
			$where[] = self::taxquery( $sql['where'] );
		}

		// Sort Order
		if ( ! $orderby ) {

			$titlesort = "p.post_title ASC";
			$defaultsort = empty($order) ? $titlesort : $order;

			// Define filterable built-in sort methods (you're welcome)
			$sortmethods = apply_filters('shopp_collection_sort_methods', array(
				'bestselling' => "s.sold DESC,$titlesort",
				'highprice'   => "maxprice DESC,$titlesort",
				'lowprice'    => "minprice ASC,$titlesort",
				'newest'      => "p.post_date DESC,$titlesort",
				'oldest'      => "p.post_date ASC,$titlesort",
				'random'      => "RAND(".crc32($Shopping->session).")",
				'chaos'       => "RAND(".time().")",
				'reverse'     => "p.post_title DESC",
				'title'       => $titlesort,
				'custom'      => is_subclass_of($this,'ProductTaxonomy') ? "tr.term_order ASC,$titlesort" : $defaultsort,
				'recommended' => is_subclass_of($this,'ProductTaxonomy') ? "tr.term_order ASC,$titlesort" : $defaultsort,
				'default'     => $defaultsort
			));

			// Handle valid user browsing sort change requests
			if ( isset($_REQUEST['sort']) && !empty($_REQUEST['sort']) && array_key_exists(strtolower($_REQUEST['sort']), $sortmethods) )
				$Storefront->browsing['sortorder'] = strtolower($_REQUEST['sort']);

			// Collect sort setting sources (Shopp admin setting, User browsing setting, programmer specified setting)
			$sortsettings = array(
				shopp_setting('default_product_order'),
				isset($Storefront->browsing['sortorder']) ? $Storefront->browsing['sortorder'] : false,
				!empty($order) ? $order : false
			);

			// Go through setting sources to determine most applicable setting
			$sorting = 'title';
			foreach ($sortsettings as $setting)
				if ( ! empty($setting) && isset($sortmethods[ strtolower($setting) ]) )
					$sorting = strtolower($setting);

			$orderby = $sortmethods[ $sorting ];
		}

		if ( empty($orderby) ) $orderby = 'p.post_title ASC';

		// Pagination
		if ( empty($limit) ) {
			if ( $this->pagination > 0 && is_numeric($this->page) && Shopp::str_true($pagination) ) {
				if( !$this->pagination || $this->pagination < 0 )
					$this->pagination = $hardlimit;
				$start = ( $this->pagination * ($this->page - 1) );

				$limit = "$start,$this->pagination";
			} else $limit = $hardlimit;
			$limited = false;	// Flag that the result set does not have forced limits
		} else $limited = true; // The result set has forced limits

		// Core query components

		// Load core product data and product summary columns
		$cols = array(	'p.ID', 'p.post_title', 'p.post_name', 'p.post_excerpt', 'p.post_status', 'p.post_date', 'p.post_modified',
						's.modified AS summed', 's.sold', 's.grossed', 's.maxprice', 's.minprice', 's.ranges', 's.taxed',
						's.stock', 's.lowstock', 's.inventory', 's.featured', 's.variants', 's.addons', 's.sale');

		if ($ids) $cols = array('p.ID');

		$columns = "SQL_CALC_FOUND_ROWS " . join(',', $cols) . ( $columns !== false ? ','.$columns : '' );
		$table = "$Processing->_table AS p";
		$where[] = "p.post_type='" . ShoppProduct::posttype() . "'";
		$joins[$summary_table] = "LEFT OUTER JOIN $summary_table AS s ON s.product=p.ID";
		$options = compact('columns', 'useindex', 'table', 'joins', 'where', 'groupby', 'having', 'limit', 'orderby');


		// Alphabetic pagination
		if ( 'alpha' === $pagination || preg_match('/(0\-9|[A-Z])/',$page) ) {
			// Setup Roman alphabet navigation
			$alphanav = array_merge(array('0-9'), range('A', 'Z'));
			$this->alpha = array_combine($alphanav, array_fill(0, count($alphanav), 0));

			// Setup alphabetized index query
			$a = $options;
			$a['columns'] = "count(DISTINCT p.ID) AS total,IF(LEFT(p.post_title,1) REGEXP '[0-9]',LEFT(p.post_title,1),LEFT(SOUNDEX(p.post_title),1)) AS letter";
			$a['groupby'] = "letter";
			$alphaquery = sDB::select($a);

			$cachehash = 'collection_alphanav_' . md5($alphaquery);
			$cached = wp_cache_get($cachehash, 'shopp_collection');
			if ($cached) { // Load from object cache,  if available
				$this->alpha = $cached;
				$cached = false;
			} else { // Run query and cache results
				$expire = apply_filters('shopp_collection_cache_expire', 43200);
				$alpha = sDB::query($alphaquery, 'array', array($this, 'alphatable'));
				wp_cache_set($cachehash, $alpha, 'shopp_collection_alphanav');
			}

			$this->paged = true;
			if ($this->page == 1) $this->page = '0-9';
			$alphafilter = $this->page == "0-9" ?
				"(LEFT(p.post_title,1) REGEXP '[0-9]') = 1" :
				"IF(LEFT(p.post_title,1) REGEXP '[0-9]',LEFT(p.post_title,1),LEFT(SOUNDEX(p.post_title),1))='$this->page'";
			$options['where'][] = $alphafilter;
		}

		$query = sDB::select( apply_filters('shopp_collection_query', $options) );

		if ( $debug ) echo $query.BR.BR;

		// Load from cached results if available, or run the query and cache the results
		$cachehash = 'collection_' . md5($query);
		$cached = wp_cache_get($cachehash, 'shopp_collection');
		if ( $cached ) {
			$this->products = $cached->products;
			$this->total = $cached->total;
		} else {
			$expire = apply_filters('shopp_collection_cache_expire', 43200);

			$cache = new stdClass();

			if ( $ids ) $cache->products = $this->products = sDB::query($query, 'array', 'col', 'ID');
			else $cache->products = $this->products = sDB::query($query, 'array', array($Processing, 'loader'));

			$cache->total = $this->total = sDB::found();

			// If running a limited set, the reported total found should not exceed the limit (but can because of SQL_CALC_FOUND_ROWS)
			// Don't use the limit if it is offset
			if ($limited && false === strpos($limit, ',')) $cache->total = $this->total = min($limit, $this->total);

			wp_cache_set($cachehash,$cache,'shopp_collection');
		}
		if ( false === $this->products ) $this->products = array();

		if ( $ids ) return ( $this->size() > 0 );

		// Finish up pagination construction
		if ( $this->pagination > 0 && $this->total > $this->pagination ) {
			$this->pages = ceil($this->total / $this->pagination);
			if ($this->pages > 1) $this->paged = true;
		}

		// Load all requested product meta from other data sources
		$Processing->load_data($load, $this->products);

		// If products are missing summary data, resum them
		if ( isset($Processing->resum) && ! empty($Processing->resum) )
			$Processing->load_data(array('prices'), $Processing->resum);

		unset($Processing); // Free memory

		$this->loaded = true;

		return ( $this->size() > 0 );
	}

	public function pagelink ( $page ) {
		global $wp_rewrite;
		$prettyurls = $wp_rewrite->using_permalinks();

		$alpha = ( false !== preg_match('/([a-z]|0\-9)/', $page) );

		$namespace = get_class_property( get_class($this) ,'namespace');
		$prettyurl = "$namespace/$this->slug" . ($page > 1 || $alpha ? "/page/$page" : "");

		// Handle catalog landing page category pagination
		if ( is_catalog_frontpage() ) $prettyurl = ($page > 1 || $alpha ? "page/$page" : "");

		$queryvars = array($this->taxonomy => $this->uri);
		if ( $page > 1 || $alpha ) $queryvars['paged'] = $page;

		return apply_filters('shopp_paged_link', Shopp::url($prettyurls ? user_trailingslashit($prettyurl) : $queryvars, false), $page );
	}

	// Add alpha-pagination support to category/collection pagination rules
	public static function pagerewrites ( $rewrites ) {
		$rules = array_keys($rewrites);
		$queries = array_values($rewrites);

		foreach ( $rules as &$rule )
			if ( false !== strpos($rule,'/?([0-9]{1,})/?$') )
				$rule = str_replace('[0-9]','0\-9|[A-Z0-9]', $rule);

		return array_combine($rules, $queries);
	}

	public function alphatable ( &$records, &$record ) {
		if ( is_numeric($record->letter) ) $this->alpha['0-9'] += $record->total;
		elseif ( isset($this->alpha[ strtoupper($record->letter) ]) ) $this->alpha[ strtoupper($record->letter) ] = $record->total;
	}

	/**
	 * Iterates loaded products in buffered batches and generates a feed-friendly item record
	 *
	 * NOTE: To modify the output of the RSS generator, use
	 * the filter hooks provided in a separate plugin or
	 * in the theme functions.php file.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @return string A feed item record
	 **/
	public function feed () {

		$paged = 100; // Buffer 100 products at a time.
		$loop = false;

		$product = ShoppProduct();
		if ( $product ) {
			$loop = shopp($this, 'products');

			if ( ! $loop ) $product = false;
			else $product = ShoppProduct();
		}

		if ( ! ($product || $loop) ) {

			if ( ! $this->products ) $page = 1;
			else $page = $this->page + 1;

			if ( $this->pages > 0 && $page > $this->pages ) return false;
			$this->load( array('load' => array('prices', 'specs', 'categories', 'coverimages'), 'paged' => $paged, 'page' => $page) );
			$loop = shopp($this, 'products');

			if ( ! $loop ) return false; // Loop ended, bail out

			$product = ShoppProduct();
			if ( ! $product ) return false; // No products, bail
		}

		$item = array();
		$item['guid'] = shopp($product, 'get-id');
		$item['title'] = $product->name;
		$item['link'] =  shopp($product, 'get-url');
		$item['pubDate'] = date('D, d M Y H:i O', $product->publish);

		// Item Description
		$item['description'] = '';

		$item['description'] .= '<table><tr>';
		$Image = current($product->images);
		if ( ! empty($Image) ) {
			$item['description'] .= '<td><a href="' . $item['link'] . '" title="' . $product->name . '">';
			$item['description'] .= '<img src="' . esc_attr(add_query_string($Image->resizing(75, 75, 0), Shopp::url($Image->id, 'images'))) . '" alt="' . $product->name . '" width="75" height="75" />';
			$item['description'] .= '</a></td>';
		}

		$pricing = "";
		$priceindex = 'price';
		if ( Shopp::str_true($product->sale) ) $priceindex = 'saleprice';

		if ( $product->min[ $priceindex ] != $product->max[ $priceindex ] )
			$pricing .= Shopp::__('from') . ' ';
		$pricing .= money($product->min[ $priceindex ]);

		$item['description'] .= "<td><p><big>$pricing</big></p>";

		$item['description'] .= apply_filters('shopp_rss_description', $product->summary, $product) . '</td></tr></table>';
		$item['description'] =
		 	'<![CDATA[' . $item['description'] . ']]>';

		// Google Base Namespace
		// http://www.google.com/support/merchants/bin/answer.py?hl=en&answer=188494

		// Below are Google Base specific attributes
		// You can use the shopp_rss_item filter hook to add new item attributes or change the existing attributes

		if ( $Image )
			$item['g:image_link'] = add_query_string($Image->resizing(400, 400, 0), Shopp::url($Image->id, 'images'));
		$item['g:condition'] = 'new';
		$item['g:availability'] = shopp_setting_enabled('inventory') && $product->outofstock ? 'out of stock' : 'in stock';

		$price = Shopp::floatval(Shopp::str_true($product->sale) ? $product->min['saleprice'] : $product->min['price']);
		if ( ! empty($price) )	{
			$item['g:price'] = $price;
			$item['g:price_type'] = "starting";
		}

		// Include product_type using Shopp category taxonomies
		foreach ( $product->categories as $category ) {
			$ancestry = array($category->name);
			$ancestors = get_ancestors($category->term_id, $category->taxonomy);
			foreach ((array)$ancestors as $ancestor) {
				$term = get_term($ancestor, $category->taxonomy);
				if ($term) array_unshift($ancestry, $term->name);
			}
			$item['g:product_type[' . $category->term_id . ']'] = join(' > ', $ancestry);
		}

		$brand = shopp($product, 'get-spec', 'name=Brand');
		if ( ! empty($brand) ) $item['g:brand'] = $brand;

		$gtins = array('UPC', 'EAN', 'JAN', 'ISBN-13', 'ISBN-10', 'ISBN');
		foreach ( $gtins as $id ) {
			$gtin = shopp($product, 'get-spec', 'name=' . $id);
			if ( ! empty($gtin) ) {
				$item['g:gtin'] = $gtin; break;
			}
		}
		$mpn = shopp($product, 'get-spec', 'name=MPN');
		if ( ! empty($mpn) ) $item['g:mpn'] = $mpn;

		// Check the product specs for matching Google Base information
		$g_props = array(
			'MPN' => 'mpn',
			'Color' => 'color',
			'Material' => 'material',
			'Pattern' => 'pattern',
			'Size' => 'size',
			'Gender' => 'gender',
			'Age Group' => 'age_group',
			'Google Product Category' => 'google_product_category'
		);
		foreach ( apply_filters('shopp_googlebase_spec_map', $g_props) as $name => $key ) {
			$value = shopp($product, 'get-spec', 'name=' . $name);
			if ( ! empty($value) ) $item[ "g:$key" ] = $value;
		}

		return apply_filters('shopp_rss_item', $item, $product);
	}

	public function feeditem ( $item ) {
		foreach ( $item as $key => $value ) {
			$key = preg_replace('/\[\d+\]$/', '', $key); // Remove duplicate tag identifiers
			$attrs = '';
			if ( is_array($value) ) {
				$rss = $value;
				$value = '';
				foreach ($rss as $name => $content) {
					if (empty($name)) $value = $content;
					else $attrs .= ' '.$name.'="'.esc_attr($content).'"';
				}
			}
			if ( false === strpos($value, '<![CDATA[') ) $value = esc_html($value);
			if ( ! empty($value) ) echo "\t\t<$key$attrs>$value</$key>\n";
			else echo "\t\t<$key$attrs />\n";
		}
	}

	static private function taxquery ( $sql ) {
		$tablename = WPShoppObject::tablename(ShoppProduct::$table);
		$sql = str_replace($tablename . '.', 'p.', $sql);
		$sql = ltrim($sql, ' AND ');
		return $sql;
	}

	public function worklist () {
		return $this->products;
	}

	public function size () {
		return count($this->products);
	}

	/** Iterator implementation **/

	public function current () {
		return $this->products[ $this->_keys[ $this->_position ] ];
	}

	public function key () {
		return $this->_position;
	}

	public function next () {
		++$this->_position;
	}

	public function rewind () {
		$this->_position = 0;
		$this->_keys = array_keys($this->products);
	}

	public function valid () {
		return isset($this->_keys[ $this->_position ]) && isset($this->products[ $this->_keys[$this->_position] ]);
	}

}

$ShoppTaxonomies = array();

/**
 * Defines a WordPress taxonomy based Shopp ProductCollection
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package collections
 **/
class ProductTaxonomy extends ProductCollection {

	static $taxon = 'shopp_group';
	static $namespace = 'group';
	static $hierarchical = true;

	protected $context = 'group';

	public $api = 'taxonomy';
	public $id = false;
	public $meta = array();
	public $images = array();

	public function __construct ( $id = false, $key = 'id' ) {
		if ( ! $id ) return;
		if ( 'id' != $key ) $this->loadby($id, $key);
		else $this->load_term($id);
	}

	static function register ( $class ) {
		global $Shopp,$ShoppTaxonomies;

		$namespace = get_class_property($class, 'namespace');
		$taxonomy = get_class_property($class, 'taxon');
		$hierarchical = get_class_property($class, 'hierarchical');
		$slug = SHOPP_NAMESPACE_TAXONOMIES ? ShoppPages()->baseslug() . '/' . $namespace : $namespace;
		register_taxonomy($taxonomy, array(ShoppProduct::$posttype), array(
			'hierarchical' => $hierarchical,
			'labels' => call_user_func(array($class,'labels'),$class),
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => $slug, 'with_front' => false ),
			'update_count_callback' => array('ProductTaxonomy', 'recount'),
			'capabilities' => array(
				'manage_terms' => 'shopp_categories',
				'edit_terms'   => 'shopp_categories',
				'delete_terms' => 'shopp_categories',
				'assign_terms' => 'shopp_categories',
			)
		));

		add_filter($taxonomy.'_rewrite_rules',array('ProductCollection','pagerewrites'));

		$ShoppTaxonomies[$taxonomy] = $class;
	}

	static function labels () {
		return array(
			'name' => __('Catalog Groups','Shopp'),
			'singular_name' => __('Catalog Group','Shopp'),
			'search_items' => __('Search Catalog Group','Shopp'),
			'popular_items' => __('Popular','Shopp'),
			'all_items' => __('Show All','Shopp'),
			'parent_item' => __('Parent Catalog Group','Shopp'),
			'parent_item_colon' => __('Parent Catalog Group:','Shopp'),
			'edit_item' => __('Edit Catalog Group','Shopp'),
			'update_item' => __('Update Catalog Group','Shopp'),
			'add_new_item' => __('New Catalog Group','Shopp'),
			'new_item_name' => __('New Catalog Group Name','Shopp'),
			'separate_items_with_commas' => __('Separate catalog groups with commas','Shopp'),
			'add_or_remove_items' => __('Add or remove catalog groups','Shopp'),
			'choose_from_most_used' => __('Choose from the most used catalog groups','Shopp')
		);
	}

	public function load ( array $options = array() ) {

		global $wpdb;
		$summary_table = ShoppDatabaseObject::tablename(ProductSummary::$table);

		$options['joins'][ $wpdb->term_relationships ] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id AND tr.term_taxonomy_id=$this->term_taxonomy_id)";
		$options['joins'][ $wpdb->term_taxonomy ] = "INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.term_id=$this->id)";

		$loaded =  parent::load( apply_filters('shopp_taxonomy_load_options', $options) );

		if ( 'auto' == $this->pricerange ) {
			$query = "SELECT (AVG(maxprice)+AVG(minprice))/2 AS average,MAX(maxprice) AS max,MIN(IF(minprice>0,minprice,NULL)) AS min FROM $summary_table " . str_replace('p.ID', 'product', join(' ', $options['joins']));
			$this->pricing = sDB::query($query);
		}

		return $loaded;
	}

	public function load_term ( $id ) {
		$term = get_term($id, $this->taxonomy);
		if ( empty($term->term_id) ) return false;
		$this->populate($term);
	}

	/**
	 * Load a taxonomy by slug name
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $slug The slug name to load
	 * @return boolean loaded successfully or not
	 **/
	public function loadby ( $id, $key = 'id' ) {
		$term = get_term_by($key, $id, $this->taxonomy);
		if (empty($term->term_id)) return false;
		$this->populate($term);
	}

	public function populate ( $data ) {
		foreach(get_object_vars($data) as $var => $value)
			$this->$var = $value;

		$this->id = $this->term_id;
		$this->term_taxonomy_id = $this->term_taxonomy_id;
	}

	public function load_meta () {
		if ( empty($this->id) ) return;
		$meta = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		sDB::query("SELECT * FROM $meta WHERE parent=$this->id AND context='$this->context' AND type='meta'", 'array', array($this, 'metaloader'), 'type');
	}

	public function metaloader ( &$records, &$record, $property = false ) {

		if ( empty($record->name) ) return;

		$metamap = array(
			'image' => 'images',
			'meta' => 'meta'
		);

		$metaclass = array(
			'image' => 'CategoryImage',
			'meta' => 'ShoppMetaObject'
		);

		if ('type' == $property)
			$property = isset($metamap[ $record->type ]) ? $metamap[ $record->type ] : 'meta';

		if ( ! isset($metaclass[ $record->type ]) ) $type = 'meta';

		$ObjectClass = $metaclass[ $record->type ];
		$Object = new $ObjectClass();
		$Object->populate($record);
		if (method_exists($Object, 'expopulate'))
			$Object->expopulate();

		$this->{$property}[ $Object->id ] = &$Object;

		if ('meta' == $property) {
			if ( !isset($this->{$Object->name}) || empty($this->{$Object->name}) )
				$this->{$Object->name} = &$Object->value;
		}

		$record = $Object;

	}

	public function save () {
		$properties = array('name' => null, 'slug' => null, 'description' => null, 'parent' => null);
		$updates = array_intersect_key(get_object_vars($this), $properties);

		remove_filter('pre_term_description', 'wp_filter_kses'); // Allow HTML in category descriptions

		if ($this->id) wp_update_term($this->id, $this->taxonomy, $updates);
		else list($this->id, $this->term_taxonomy_id) = array_values(wp_insert_term($this->name, $this->taxonomy, $updates));

		if ( ! $this->id ) return false;

		// If the term successfully saves, save all meta data too
		foreach ( $this->meta as $name => $Meta ) {

			if ( is_a($Meta,'ShoppMetaObject') ) {
				$MetaObject = $Meta;
			} else {
				$MetaObject = new ShoppMetaObject();
				$MetaObject->populate($Meta);
			}

			$MetaObject->parent = $this->id;
			$MetaObject->context = 'category';
			$MetaObject->save();
		}
		return true;
	}

	public function delete () {
		if ( empty($this->id) ) return false;

		// Remove WP taxonomy term
		$status = wp_delete_term($this->id, $this->taxonomy);

		// Remove meta data & images
		$status = $status && shopp_rmv_meta ( $this->id, 'category' );
		return $status;

	}

	static function tree ( $taxonomy, $terms, &$children, &$count, &$results = array(), $page = 1, $per_page = 0, $parent = 0, $level = 0) {

		$start = ($page - 1) * $per_page;
		$end = $start + $per_page;

		foreach ( $terms as $id => $term_parent ) {
			if ( $end > $start && $count >= $end ) break;
			if ( $term_parent != $parent ) continue;

			// Render parents when pagination starts in a branch
			if ( $start > 0 && $count == $start && $term_parent > 0 ) {
				$parents = $parent_ids = array();
				$p = $term_parent;
				while ( $p ) {
					$terms_parent = get_term( $p, $taxonomy );
					$terms_parent->id = $terms_parent->term_id;
					$parents[] = $terms_parent;
					$p = $terms_parent->parent;

					if (in_array($p,$parent_ids)) break;

					$parent_ids[] = $p;
				}
				unset($parent_ids);

				$parent_count = count($parents);
				while ($terms_parent = array_pop($parents)) {
					$results[ $terms_parent->term_id ] = $terms_parent;
					$results[ $terms_parent->term_id ]->level = $level - ($parent_count--);
				}
			}

			if ( $count >= $start ) {
				if ( isset($results[ $id ]) ) continue;
				$results[ $id ] = get_term($id, $taxonomy);
				$results[ $id ]->id = $results[ $id ]->term_id;
				$results[ $id ]->level = $level;
				$results[ $id ]->_children = isset($children[ $id ]);
			}
			++$count;
			unset($terms[ $id ]);

			if ( isset($children[ $id ]) )
				self::tree($taxonomy, $terms, $children, $count, $results, $page, $per_page, $id, $level + 1);
		}
	}

	public function pagelink ( $page ) {
		global $wp_rewrite;

		$alpha = ( false !== preg_match('/([A-Z]|0\-9)/', $page) );
		$base = shopp($this, 'get-url');

		if ( (int) $page > 1 || $alpha )
	        $url = $wp_rewrite->using_permalinks() ? user_trailingslashit( trailingslashit($base) . "page/$page") : add_query_arg('paged', $page, $base);

		return apply_filters('shopp_paged_link', $url, $page);
	}

	static function recount ( $terms, $taxonomy ) {
		global $wpdb;
		$summary_table = ShoppDatabaseObject::tablename(ProductSummary::$table);

		foreach ( (array)$terms as $term ) {
			$where = array(
				"$wpdb->posts.ID = $wpdb->term_relationships.object_id",
				"post_status='publish'",
				"post_type='" . ShoppProduct::$posttype . "'"
			);

			if ( shopp_setting_enabled('inventory') && ! shopp_setting_enabled('outofstock_catalog') )
				$where[] = "( s.inventory='off' OR (s.inventory='on' AND s.stock > 0) )";

			$where[] = "term_taxonomy_id=" . (int)$term;
			$query = "SELECT COUNT(*) AS c FROM $wpdb->term_relationships, $wpdb->posts LEFT OUTER JOIN $summary_table AS s ON s.product=$wpdb->posts.ID WHERE " . join(' AND ', $where);
			$count = (int) sDB::query($query, 'auto', 'col', 'c');

			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}

	}

}

/**
 * Defines a Shopp product category collection
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package collection
 **/
class ProductCategory extends ProductTaxonomy {

	static $taxon = 'shopp_category';
	static $namespace = 'category';
	static $hierarchical = true;

	protected $context = 'category';
	public $api = 'category';
	public $name = '';
	public $description = '';
	public $facets = array();
	public $filters = array();

	public $parent = false;
	public $children = array();
	public $child = false;

	public function __construct ( $id = false, $key = 'id', $taxonomy = false ) {
		$this->taxonomy = $taxonomy ? $taxonomy : self::$taxon;
		parent::__construct($id, $key);
		if ( ! empty($this->id) ) $this->load_meta();
		if ( isset($this->facetedmenus) && Shopp::str_true($this->facetedmenus) )
			$this->filters();
	}

	static function labels () {
		return array(
			'name' => Shopp::__('Catalog Categories'),
			'singular_name' => Shopp::__('Category'),
			'search_items' => Shopp::__('Search Categories'),
			'popular_items' => Shopp::__('Popular'),
			'all_items' => Shopp::__('Show All'),
			'parent_item' => Shopp::__('Parent Category'),
			'parent_item_colon' => Shopp::__('Parent Category:'),
			'edit_item' => Shopp::__('Edit Category'),
			'update_item' => Shopp::__('Update Category'),
			'add_new_item' => Shopp::__('New Category'),
			'new_item_name' => Shopp::__('New Category Name'),
			'menu_name' => Shopp::__('Categories')
		);
	}

	public function load ( array $options = array() ) {

		// $options['debug'] = true;
		if ($this->filters) add_filter('shopp_taxonomy_load_options',array($this,'facetsql'));

		// Include loading overrides (generally from the Theme API)
		if ( ! empty($this->loading) && is_array($this->loading) )
			$options = array_merge($options,$this->loading);

		return parent::load($options);
	}

	/**
	 * Parses parametric search filters from the requests query string
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function filters () {
		if ( 'off' == $this->facetedmenus ) return;
		$Storefront = ShoppStorefront();
		if ( ! $Storefront ) return;

		if ( ! empty($this->facets) ) return;

		$specs = &$this->specs;
		if ( is_null($specs) ) $specs = array();

		$this->facets = array();
		if ( isset($Storefront->browsing[ $this->slug ]) )
			$this->filters = $Storefront->browsing[ $this->slug ];

		if ( 'disabled' != $this->pricerange ) {
			array_unshift($specs, array(
				'name'        => apply_filters('shopp_category_price_facet_label', __('Price Filter','Shopp')),
				'slug'        => 'price',
				'facetedmenu' => $this->pricerange
			));
		}

		foreach ( $specs as $spec ) {
			if ( ! isset($spec['facetedmenu']) || 'disabled' == $spec['facetedmenu'] ) continue;

			if ( isset($spec['slug']) ) $slug = $spec['slug'];
			else $slug = sanitize_title_with_dashes($spec['name']);

			$selected = isset($_GET[ $slug ]) && Shopp::str_true(get_query_var('s_ff')) ? $_GET[ $slug ] : false;

			$Facet = new ProductCategoryFacet();
			$Facet->name = $spec['name'];
			$Facet->slug = $slug;
			$Facet->type = $spec['facetedmenu'];
			if ( false !== $selected ) {
				$Facet->selected = $selected;
				$this->filters[ $slug ] = $selected;
			} elseif ( isset($this->filters[ $slug ]) ) $Facet->selected = $this->filters[ $slug ];
			$this->facets[ $slug ] = $Facet;
		}

		$this->filters = array_filter($this->filters);
		$Storefront->browsing[ $this->slug ] = $this->filters; // Save currently applied filters
	}

	public function facetsql ( $options ) {
		if ( ! $this->filters ) return array();

		$joins = $options['joins'];

		if ( ! isset($options['where']) ) $options['where'] = array();

		$f = 1;
		$where = array();
		$filters = array();
		$facets = array();
		$numeric = array();
		foreach ( $this->filters as $filtered => $value ) {
			$Facet = $this->facets[ $filtered ];
			if ( empty($value) ) continue;
			$name = $Facet->name;
			$value = urldecode($value);

			if ( ! is_array($value) && preg_match('/^.*?(\d+[\.\,\d]*).*?\-.*?(\d+[\.\,\d]*).*$/', $value, $matches) ) {
				if ('price' == $Facet->slug) { // Prices require complex matching on summary prices in the main collection query
					list(, $min, $max) = array_map(array('Shopp', 'floatval'), $matches);
					if ( $min > 0 ) $options['where'][] = "(s.minprice >= $min)";
					if ( $max > 0 ) $options['where'][] = "(s.minprice > 0 AND s.minprice <= $max)";

				} else { // Spec-based numbers are somewhat more straightforward
					list(, $min, $max) = $matches;
					$ranges = array();
					if ( $min > 0 ) $ranges[] = "numeral >= $min";
					if ( $max > 0 ) $ranges[] = "numeral <= $max";
					// $filters[] = "(".join(' AND ',$ranges).")";
					$numeric[] = sDB::escape($name);
					$facets[] = sprintf("name='%s' AND %s", sDB::escape($name), join(' AND ', $ranges));
					$filters[] = sprintf("FIND_IN_SET('%s', facets)", sDB::escape($name));

				}

			} else { // No range, direct value match
				$filters[] = sprintf("FIND_IN_SET('%s=%s',facets)", sDB::escape($name), sDB::escape($value));
				$facets[] = sprintf("name='%s' AND value='%s'", sDB::escape($name), sDB::escape($value));
			}

		}

		$spectable = ShoppDatabaseObject::tablename(Spec::$table);
		$jointables = str_replace('p.ID', 'm.parent', join(' ', $joins)); // Rewrite the joins to use the spec table reference
		$having = "HAVING " . join(' AND ', $filters);

		$query = "SELECT m.parent,GROUP_CONCAT(m.name,
			IF(0<FIND_IN_SET('" . join(',', $numeric) . "',m.name),'','='),
			IF(0<FIND_IN_SET('" . join(',', $numeric) . "',m.name),'',m.value)) AS facets
					FROM $spectable AS m $jointables
					WHERE context='product' AND type='spec' AND (" . join(' OR ', $facets) . ")
					GROUP BY m.parent
					$having";

		// Support cache accelleration
		$cachehash = 'collection_facet_' . md5($query);
		$cached = wp_cache_get($cachehash, 'shopp_collection_facet');
		if ( $cached ) $set = $cached;
		else {
			$set = sDB::query($query, 'array', 'col', 'parent');
			wp_cache_set($cachehash, $set, 'shopp_collection_facet');
		}

		if ( ! empty($set) ) {
			$options['where'][] = "p.id IN (" . join(',', $set) . ")";
			// unset($options['joins']);
		}

		return $options;
	}

	public function load_facets () {
		if ('off' == $this->facetedmenus) return;
		$output = '';
		$this->filters();
		$Storefront = ShoppStorefront();
		if (!$Storefront) return;
		$CategoryFilters =& $Storefront->browsing[$this->slug];

		$Filtered = new ProductCategory($this->id);
		$filtering = array_merge($Filtered->facetsql(array()),array('ids'=>true,'limit'=>1000));
		$Filtered->load($filtering);
		$ids = join(',',$Filtered->worklist());

		// Load price facet filters first
		if ( 'disabled' != $this->pricerange ) {
			$Facet = $this->facets['price'];
			$Facet->link = add_query_arg(array('s_ff'=>'on',urlencode($Facet->slug) => ''),shopp('category','get-url'));

			if ( ! $this->loaded ) $this->load();
			if ('auto' == $this->pricerange) $ranges = auto_ranges($this->pricing->average, $this->pricing->max, $this->pricing->min, $this->pricing->uniques);
			else $ranges = $this->priceranges;

			if ( ! empty($ranges) ) {
				$casewhen = '';
				foreach ($ranges as $index => $r) {
					$minprice = $r['max'] > 0 ? " AND minprice <= {$r['max']}":"";
					$casewhen .= " WHEN (minprice >= {$r['min']}$minprice) THEN $index";
				}

				$sumtable = ShoppDatabaseObject::tablename(ProductSummary::$table);
				$query = "SELECT count(*) AS total, CASE $casewhen END AS rangeid
					FROM $sumtable
					WHERE product IN ($ids) GROUP BY rangeid";
				$counts = sDB::query($query,'array','col','total','rangeid');

				foreach ($ranges as $id => $range) {
					if ( ! isset($counts[$id]) || $counts[$id] < 1 ) continue;
					$label = money($range['min']).' &mdash; '.money($range['max']);
					if ($range['min'] == 0) $label = sprintf(__('Under %s','Shopp'),money($range['max']));
					if ($range['max'] == 0) $label = sprintf(__('%s and up','Shopp'),money($range['min']));

					$FacetFilter = new ProductCategoryFacetFilter();
					$FacetFilter->label = $label;
					$FacetFilter->param = urlencode($range['min'].'-'.$range['max']);
					$FacetFilter->count = $counts[$id];
					$Facet->filters[$FacetFilter->param] = $FacetFilter;
				}
			} // END !empty($ranges)

		}

		// Identify facet menu types to treat numeric and string contexts properly @bug #2014
		$custom = array();
		foreach ( $this->facets as $Facet )
			if ( 'custom' == $Facet->type ) $custom[] = sDB::escape($Facet->name);

		// Load spec aggregation data
		$spectable = ShoppDatabaseObject::tablename(Spec::$table);

		$query = "SELECT spec.name,spec.value,
			IF(0 >= FIND_IN_SET(spec.name,'".join(",",$custom)."'),IF(spec.numeral > 0,spec.name,spec.value),spec.value) AS merge, count(DISTINCT spec.value) AS uniques,
			count(*) AS count,avg(numeral) AS avg,max(numeral) AS max,min(numeral) AS min
			FROM $spectable AS spec
			WHERE spec.parent IN ($ids) AND spec.context='product' AND spec.type='spec' AND (spec.value != '' OR spec.numeral > 0) GROUP BY merge";
		$specdata = sDB::query($query, 'array', 'index', 'name', true);

		foreach ($this->specs as $spec) {
			if ('disabled' == $spec['facetedmenu']) continue;
			$slug = sanitize_title_with_dashes($spec['name']);
			if (!isset($this->facets[ $slug ])) continue;
			$Facet = &$this->facets[ $slug ];
			$Facet->link = add_query_arg(array('s_ff'=>'on', urlencode($Facet->slug) => ''), shopp('category', 'get-url'));

			// For custom menu presets

			switch ($spec['facetedmenu']) {
				case 'custom':
					$data = $specdata[ $Facet->name ];
					$counts = array();
					foreach ($data as $d) $counts[ $d->value ] = $d->count;
					foreach ($spec['options'] as $option) {
						if (!isset($counts[ $option['name'] ]) || $counts[ $option['name'] ] < 1) continue;
						$FacetFilter = new ProductCategoryFacetFilter();
						$FacetFilter->label = $option['name'];
						$FacetFilter->param = urlencode($option['name']);
						$FacetFilter->count = $counts[ $FacetFilter->label ];
						$Facet->filters[$FacetFilter->param] = $FacetFilter;
					}
					break;
				case 'ranges':
					foreach ($spec['options'] as $i => $option) {
						$matches = array();
						$format = '%s-%s';
						$next = 0;
						if (isset($spec['options'][$i+1])) {
							if (preg_match('/(\d+[\.\,\d]*)/',$spec['options'][$i+1]['name'],$matches))
								$next = $matches[0];
						}
						$matches = array();
						$range = array("min" => 0,"max" => 0);
						if (preg_match('/^(.*?)(\d+[\.\,\d]*)(.*)$/',$option['name'],$matches)) {
							$base = $matches[2];
							$format = $matches[1].'%s'.$matches[3];
							if (!isset($spec['options'][$i+1])) $range['min'] = $base;
							else $range = array("min" => $base, "max" => ($next-1));
						}
						if ($i == 1) {
							$href = add_query_arg($slug, urlencode(sprintf($format,'0',$range['min'])),$link);
							$label = __('Under ','Shopp').sprintf($format,$range['min']);
							$list .= '<li><a href="'.$href.'">'.$label.'</a></li>';
						}

						$href = add_query_arg($slug, urlencode(sprintf($format,$range['min'],$range['max'])), $link);
						$label = sprintf($format,$range['min']).' &mdash; '.sprintf($format,$range['max']);
						if ($range['max'] == 0) $label = sprintf($format,$range['min']).' '.__('and up','Shopp');
						$list .= '<li><a href="'.$href.'">'.$label.'</a></li>';
					}
					break;
				default:
					if (!isset($specdata[ $Facet->name  ])) break;
					$data = $specdata[ $Facet->name ];
					if ( ! is_array($data) ) $data = array($data);
					if ( $data[0]->min + $data[0]->max + $data[0]->avg == 0 ) { // Generate facet filters from text values
						foreach ($data as $option) {
							$FacetFilter = new ProductCategoryFacetFilter();
							$FacetFilter->label = $option->value;
							$FacetFilter->param = urlencode($option->value);
							$FacetFilter->count = $option->count;
							$Facet->filters[$FacetFilter->param] = $FacetFilter;
						}
					} else {
						$data = reset($data);

						$format = '%s';
						if (preg_match('/^(.*?)(\d+[\.\,\d]*)(.*)$/',$data->value,$matches))
							$format = $matches[1].'%s'.$matches[3];

						$ranges = auto_ranges($data->avg,$data->max,$data->min,$data->uniques);

						if ( ! empty($ranges) ) {
							$casewhen = '';
							foreach ($ranges as $index => $r) {
								$max = $r['max'] > 0 ? " AND spec.numeral <= {$r['max']}":"";
								$casewhen .= " WHEN (spec.numeral >= {$r['min']}$max) THEN $index";
							}

							$query = "SELECT count(*) AS total, CASE $casewhen END AS rangeid
								FROM $spectable AS spec
								WHERE spec.parent IN ($ids) AND spec.name='$Facet->name' AND spec.context='product' AND spec.type='spec' AND spec.numeral > 0 GROUP BY rangeid";
							$counts = sDB::query($query,'array','col','total','rangeid');

							foreach ($ranges as $id => $range) {
								if ( ! isset($counts[$id]) || $counts[$id] < 1 ) continue;

								$label = sprintf($format,$range['min']).' &mdash; '.sprintf($format,$range['max']);
								if ($range['min'] == 0) $label = __('Under ','Shopp').sprintf($format,$range['max']);
								elseif ($range['max'] == 0) $label = sprintf($format,$range['min']).' '.__('and up','Shopp');

								$FacetFilter = new ProductCategoryFacetFilter();
								$FacetFilter->label = $label;
								$FacetFilter->param = urlencode($range['min'].'-'.$range['max']);
								$FacetFilter->count = $counts[$id];
								$Facet->filters[$FacetFilter->param] = $FacetFilter;
							}

						} // END !empty($ranges)
					}

			} // END switch
		}
	}

	/**
	 * Load sub-categories
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @see get_terms() in wp-includes/taxonomy.php for accepted $options values
	 *
	 * @param array $options Named array for WP get_terms
	 * @return boolean successfully loaded or not
	 **/
	public function load_children ( array $options = array() ) {

		if ( empty($this->id) ) return false;

		$taxonomy = self::$taxon;
		$categories = array(); $count = 0;
		$options = array_merge($options, array('child_of' => $this->id, 'fields' => 'all'));
		$terms = get_terms( $taxonomy, $options );

		$this->children = array();
		foreach ( $terms as $term ) {
			$this->children[ $term->term_id ] = new ProductCategory($term->term_id);
			$this->children[ $term->term_id ]->populate($term);
		}

		return ( ! empty($this->children) );

	}

	/**
	 * Loads images assigned to this category
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return boolean Successful load or not
	 **/
	public function load_images () {

		$ordering = shopp_setting('product_image_order');
		$orderby = shopp_setting('product_image_orderby');

		if ('RAND' == $ordering) $orderby = 'RAND()';
		else $orderby .= ' '.$ordering;

		$table = ShoppDatabaseObject::tablename(CategoryImage::$table);
		if (empty($this->id)) return false;
		$query = "SELECT * FROM $table WHERE parent=$this->id AND context='category' AND type='image' ORDER BY $orderby";
		$records = sDB::query($query,'array',array($this,'metaloader'),'type');

		return true;
	}

	// function alphapages ($loading=array()) {
	// 	$db =& sDB::get();
	//
	// 	$catalogtable = ShoppDatabaseObject::tablename(ShoppCatalog::$table);
	// 	$producttable = ShoppDatabaseObject::tablename(ShoppProduct::$table);
	// 	$pricetable = ShoppDatabaseObject::tablename(ShoppPrice::$table);
	//
	// 	$alphanav = range('A','Z');
	//
	// 	$ac =   "SELECT count(*) AS total,
	// 					IF(LEFT(p.name,1) REGEXP '[0-9]',LEFT(p.name,1),LEFT(SOUNDEX(p.name),1)) AS letter,
	// 					AVG((p.maxprice+p.minprice)/2) as avgprice
	// 				FROM $producttable AS p {$loading['useindex']}
	// 				{$loading['joins']}
	// 				WHERE {$loading['where']}
	// 				GROUP BY letter";
	//
	// 	$alpha = sDB::query($ac,'array');
	//
	// 	$entry = new stdClass();
	// 	$entry->letter = false;
	// 	$entry->total = $entry->avg = 0;
	//
	// 	$existing = current($alpha);
	// 	if (!isset($this->alpha['0-9'])) {
	// 		$this->alpha['0-9'] = clone $entry;
	// 		$this->alpha['0-9']->letter = '0-9';
	// 	}
	//
	// 	while (is_numeric($existing->letter)) {
	// 		$this->alpha['0-9']->total += $existing->total;
	// 		$this->alpha['0-9']->avg = ($this->alpha['0-9']->avg+$existing->avg)/2;
	// 		$this->alpha['0-9']->letter = '0-9';
	// 		$existing = next($alpha);
	// 	}
	//
	// 	foreach ($alphanav as $letter) {
	// 		if ($existing->letter == $letter) {
	// 			$this->alpha[$letter] = $existing;
	// 			$existing = next($alpha);
	// 		} else {
	// 			$this->alpha[$letter] = clone $entry;
	// 			$this->alpha[$letter]->letter = $letter;
	// 		}
	// 	}
	//
	// }

	/**
	 * Returns the product adjacent to the requested product in the category
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $next (optional) Which product to get (-1 for previous, defaults to 1 for next)
	 * @return object The Product object
	 **/
	public function adjacent_product($next=1) {
		$Shopp = Shopp::object();

		if ($next < 0) $this->loading['adjacent'] = "previous";
		else $this->loading['adjacent'] = "next";

		$this->loading['limit'] = '1';
		$this->loading['product'] = $Shopp->Requested;
		$this->load_products();

		if (!$this->loaded) return false;

		reset($this->products);
		$product = key($this->products);
		return new ShoppProduct($product);
	}

	/**
	 * Updates the sort order of category image assets
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param array $ordering List of image ids in order
	 * @return boolean true on success
	 **/
	public function save_imageorder ($ordering) {
		$table = ShoppDatabaseObject::tablename(CategoryImage::$table);
		foreach ($ordering as $i => $id)
			sDB::query("UPDATE $table SET sortorder='$i' WHERE (id='$id' AND parent='$this->id' AND context='category' AND type='image')");
		return true;
	}

	/**
	 * Updates the assigned parent id of images to link them to the category
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param array $images List of image ids
	 * @return boolean true on successful update
	 **/
	public function link_images ($images) {
		if (empty($images) || !is_array($images)) return false;

		$table = ShoppDatabaseObject::tablename(CategoryImage::$table);
		$set = "id=".join(' OR id=',$images);
		$query = "UPDATE $table SET parent='$this->id',context='category' WHERE ".$set;
		sDB::query($query);

		return true;
	}

	/**
	 * Deletes image assignments to the category and metadata (not the binary data)
	 *
	 * Removes the meta table record that assigns the image to the category and all
	 * cached image metadata built from the original image. Does NOT delete binary
	 * data.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param array $images List of image ids to delete
	 * @return boolean true on success
	 **/
	public function delete_images ($images) {
		$imagetable = ShoppDatabaseObject::tablename(CategoryImage::$table);
		$imagesets = "";
		foreach ($images as $image) {
			$imagesets .= (!empty($imagesets)?" OR ":"");
			$imagesets .= "((context='category' AND parent='$this->id' AND id='$image') OR (context='image' AND parent='$image'))";
		}
		sDB::query("DELETE FROM $imagetable WHERE type='image' AND ($imagesets)");
		return true;
	}

	/**
	 * A functional list of support category sort options
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array The list of supported sort methods
	 **/
	static function sortoptions () {
		return apply_filters('shopp_category_sortoptions', array(
			"title" => __('Title','Shopp'),
			"custom" => __('Recommended','Shopp'),
			"bestselling" => __('Bestselling','Shopp'),
			"highprice" => __('Price High to Low','Shopp'),
			"lowprice" => __('Price Low to High','Shopp'),
			"newest" => __('Newest to Oldest','Shopp'),
			"oldest" => __('Oldest to Newest','Shopp'),
			"random" => __('Random','Shopp')
		));
	}

} // END class ProductCategory

/**
 * Defines a product category facet entry
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class ProductCategoryFacet {

	public $name;	// Display name
	public $slug;	// Sanitized name
	public $type;
	public $link;	// Link to remove facet
	// public $min;	// Min numeral val
	// public $max;	// Max numeral val
	// public $avg;	// Avg numeral val

	public $filters = array();

	static function range_labels ($range) {

		if (preg_match('/^(.*?(\d+[\.\,\d]*).*?)\-(.*?(\d+[\.\,\d]*).*)$/',$range,$matches)) {
			$label = $matches[1].' &mdash; '.$matches[3];
			if ($matches[2] == 0) $label = __('Under ','Shopp').$matches[3];
			if ($matches[4] == 0) $label = $matches[1].' '.__('and up','Shopp');
			$range = $label;
		}

		return $range;
	}

}

/**
 * Defines a structured container for facet filters (applied filters)
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class ProductCategoryFacetFilter {

	public $label; // Display name
	public $param;	// Santized name
	public $count = 0;	// Product count

}

/**
 * Defines a Shopp product tag collection
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class ProductTag extends ProductTaxonomy {

	static $taxon = 'shopp_tag';
	static $namespace = 'tag';
	static $hierarchical = false;

	protected $context = 'tag';

	public $api = 'category';

	public function __construct ( $id = false, $key = 'id', $taxonomy = false ) {
		$this->taxonomy = $taxonomy ? $taxonomy : self::$taxon;
		parent::__construct($id, $key);
	}

	static function labels () {
		return array(
			'name' => __('Catalog Tags','Shopp'),
			'singular_name' => __('Tag','Shopp'),
			'search_items' => __('Search Tag','Shopp'),
			'popular_items' => __('Popular','Shopp'),
			'all_items' => __('Show All','Shopp'),
			'edit_item' => __('Edit Tag','Shopp'),
			'update_item' => __('Update Tag','Shopp'),
			'add_new_item' => __('New Tag','Shopp'),
			'new_item_name' => __('New Tag Name','Shopp'),
			'separate_items_with_commas' => __('Separate tags with commas','Shopp'),
			'add_or_remove_items' => sprintf(__('Type a tag name and press tab %s to add it.','Shopp'),'<abbr title="'.__('tab key','Shopp').'">&#8677;</abbr>'),
			'choose_from_most_used' => __('Type to search, or wait for popular tags&hellip;','Shopp'),
			'menu_name' => __('Tags','Shopp')
		);
	}

}

/**
 * Defines the base functionality for a Shopp "smart collection"
 *
 * Smart collections are pre-programmed collections that use designer queries
 * to create a grouping of products dynamically.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class SmartCollection extends ProductCollection {

	public static $taxon = 'shopp_collection';
	public static $namespace = 'collection';
	public static $slugs = array();
	public static $_menu = true;

	public $smart = true;
	public $uri = false;
	public $slug = false;
	public $name = false;
	public $loading = array();
	protected $_options = array();

	public function __construct ( array $options = array() ) {

		$this->taxonomy = self::$taxon;

		$thisclass = get_class($this);
		$slugs = SmartCollection::slugs($thisclass);

		$this->slug = $this->uri = $slugs[0];

		$this->name = call_user_func(array($thisclass, 'name'));
		$this->_options = $options;
	}

	public static function name () {
		return Shopp::__('Collection');
	}

	public static function slugs ( $class ) {
		return apply_filters( 'shopp_' . strtolower($class) . '_collection_slugs', get_class_property($class, 'slugs') );
	}

	public function load ( array $options = array() ) {
		$this->loading = array_merge( $this->_options, $options );

		if ( isset($this->loading['show']) ) {
			$this->loading['limit'] = $this->loading['show'];
			unset($this->loading['show']);
		}

		if ( isset($options['pagination']) )
			$this->loading['pagination'] = $options['pagination'];

		if ( isset($options['exclude']) ) {
			$exclude = $options['exclude'];

			if ( is_numeric(str_replace(',','',$exclude)) ) {
				global $wpdb;
				$this->loading['joins'][] = "INNER JOIN $wpdb->term_relationships as tr ON p.ID = tr.object_id";
				$this->loading['joins'][] = "INNER JOIN $wpdb->term_taxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
				$this->loading['where'][] = "tr.term_taxonomy_id NOT IN ($exclude)";
				$this->loading['where'][] = "tt.taxonomy = 'shopp_category'";
			}
		}

		$this->smart($this->loading);

		parent::load($this->loading);
	}

	public static function defaultlinks ( $termlink, $term, $taxonomy ) {
		if ( false !== strpos($termlink, "taxonomy=" . self::$taxon) )
			return home_url("?" . self::$taxon . "=$term->slug");
		return $termlink;
	}

	public function register () {
		global $wp_rewrite;
		if ( ! $wp_rewrite->using_permalinks() ) return;

		$args['rewrite'] = wp_parse_args($args['rewrite'], array(
			'slug' => sanitize_title_with_dashes($taxonomy),
			'with_front' => false,
		));

		add_rewrite_tag("%$taxonomy%", '([^/]+)', $args['query_var'] ? "{$args['query_var']}=" : "taxonomy=$taxonomy&term=");
		add_permastruct($taxonomy, "{$args['rewrite']['slug']}/%$taxonomy%", $args['rewrite']['with_front']);

	}

}

/**
 * A smart collection for **all** published products in the catalog
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class CatalogProducts extends SmartCollection {

	public static $slugs = array('catalog');

	public static function name () {
		return Shopp::__('Catalog Products');
	}

	public function smart ( array $options = array() ) {
		if ( isset($options['order']) )
			$this->loading['order'] = $options['order'];
	}

}

/**
 * A smart collection to get the newest products.
 *
 * @todo Setup a threshold cutoff date query
 *
 * This smart collection really just uses sort order to force sorting products
 * newest to oldest. Ideally we would set a time limit as a threshold cutoff.
 * The problem is that the timeframes for adding new products to catalog vary
 * wildly and there is no way to know how often that might happen from store
 * to store.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class NewProducts extends SmartCollection {

	public static $slugs = array('new');

	public static function name () {
		return Shopp::__('New Products');
	}

	public function smart ( array $options = array() ) {
		$this->loading['order'] = 'newest';

		if ( isset($options['columns']) )
			$this->loading['columns'] = $options['columns'];
	}

}

/**
 * A smart collection to group products that are marked as a **featured** product
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class FeaturedProducts extends SmartCollection {

	public static $slugs = array('featured');

	public static function name () {
		return Shopp::__('Featured Products');
	}

	public function smart ( array $options = array() ) {
		$this->loading['where'] = array("s.featured='on'");
		$this->loading['order'] = 'newest';
	}

}

/**
 * A smart collection to group products that are on sale
 *
 * This collection will collect products that have a sale price that is enabled
 * in the product editor or products that have a catalog discount applied to them
 * because the product summary system will detect the sale price and force on the
 * on sale flag.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class OnSaleProducts extends SmartCollection {

	public static $slugs = array('onsale');

	public static function name () {
		return Shopp::__('On Sale');
	}

	public function smart ( array $options = array() ) {
		$this->loading['where'] = array("s.sale='on'");
		$this->loading['order'] = 'p.post_modified DESC';
	}

}

/**
 * A smart collection that uses past purchase data to group popular selling products
 *
 * By default this collection will use a sales threshold determine by the mean average
 * product sold of all products in the catalog (derived from the cached summary `sold` column).
 * Any product above that average will appear in the collection.
 *
 * Alternatively, a range option can be provided with a start and/or end date range.
 * The date range option must be provided as an array. If the second element of the
 * array (the end date) is not provided, the current timestamp will be used. Dates should be
 * provided in a 'YYYY-MM-DD HH:MM:SS' format date('Y-m-d H:i:s')
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class BestsellerProducts extends SmartCollection {

	public static $slugs = array('bestsellers', 'bestseller', 'bestselling');


	public static function name () {
		return Shopp::__('Bestsellers');
	}

	public function smart ( array $options = array() ) {
		if ( isset($options['range']) && is_array($options['range']) ) {
			$start = $options['range'][0];
			$end = $options['range'][1];
			if (!$end) $end = current_time('timestamp');
			$purchased = ShoppDatabaseObject::tablename(Purchased::$table);
			$this->loading['columns'] = "COUNT(*) AS sold";
			$this->loading['joins'] = array($purchased => "INNER JOIN $purchased as pur ON pur.product=p.id");
			$this->loading['where'] = array("pur.created BETWEEN '".sDB::mkdatetime($start)."' AND '".sDB::mkdatetime($end)."'");
			$this->loading['orderby'] = 'sold DESC';
			$this->loading['groupby'] = 'pur.product';
		} else {
			$this->loading['where'] = array(BestsellerProducts::threshold()." < s.sold");
			$this->loading['order'] = 'bestselling';	// Use overall bestselling stats
			$this->loading = array_merge($options, $this->loading);
		}
	}

	static function threshold () {
		// Get mean sold for bestselling threshold
		$summary = ShoppDatabaseObject::tablename(ProductSummary::$table);
		return (float)sDB::query("SELECT AVG(sold) AS threshold FROM $summary WHERE 0 != sold", 'auto', 'col', 'threshold');
	}

}

/**
 * A smart collection for search results
 *
 * The Shopp search engine uses a smart collection to carry out grouping
 * product search result hits. It is designed to use the Shopp product index
 * and includes advanced functionality for query parsing, and result scoring
 * for complete control of search precision (relevant results) and
 * recall (quantity of results).
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class SearchResults extends SmartCollection {

	public static $slugs = array('search-results', 'search');

	public static $_menu = false;
	public $search = false;

	public function __construct ( array $options = array() ) {
		parent::__construct($options);
		add_filter('shopp_themeapi_collection_url', array($this, 'url'), 10, 3);

		$options['search'] = empty($options['search']) ? '' : stripslashes($options['search']);
		$this->search = $options['search'];
	}

	public static function name () {
		 return Shopp::__('Search Results');
	}

	public function smart ( array $options = array() ) {

		// $this->loading['debug'] = true;
		// Load search engine components
		new SearchParser;
		new BooleanParser;
		new ShortwordParser;

		$search = $this->search;
		if ( ShoppStorefront() ) ShoppStorefront()->search = $search;

		// Sanitize the search string

		// Price matching
		$prices = SearchParser::PriceMatching($search);
		if ($prices) {
			$pricematch = false;
			switch ($prices->op) {
				case '>': $pricematch = "minprice > $prices->target OR maxprice > $prices->target"; break;
				case '<': $pricematch = "minprice < $prices->target OR maxprice < $prices->target"; break;
				default: $pricematch = "minprice >= $prices->min AND maxprice <= $prices->max"; break;
			}
		}

		// Boolean keyword search
		$boolean = apply_filters('shopp_boolean_search', $search);

		// Exact shortword search
		$shortwords = '';
		if ( ! (defined('SHOPP_DISABLE_SHORTWORD_SEARCH') && SHOPP_DISABLE_SHORTWORD_SEARCH) )
			$shortwords = apply_filters('shopp_shortword_search', $search);

		// Natural language search for relevance
		$search = apply_filters('shopp_search_query', $search);

		if ( strlen($options['search'] ) > 0 && empty($boolean) ) $boolean = $options['search'];

		$score = "SUM(MATCH(terms) AGAINST ('$search'))";
		$where = "MATCH(terms) AGAINST ('$boolean' IN BOOLEAN MODE)";
		if ( ! empty($shortwords) ) {
			$score = "SUM(MATCH(terms) AGAINST ('$search'))+SUM(terms REGEXP '[[:<:]](" . str_replace(' ', '|', $shortwords) . ")[[:>:]]')";
			$where = "($where OR terms REGEXP '[[:<:]](" . str_replace(' ','|',$shortwords) . ")[[:>:]]')";
		}

		$index = ShoppDatabaseObject::tablename(ContentIndex::$table);
		$this->loading['joins']   = array($index => "INNER JOIN $index AS search ON search.product=p.ID");
		$this->loading['columns'] = "$score AS score";
		$this->loading['where']   = array($where);
		$this->loading['groupby'] = 'p.ID';
		$this->loading['orderby'] = 'score DESC';
		if ( ! empty($pricematch) ) $this->loading[ empty( $search ) ? 'where' : 'having' ] = array($pricematch);
		if ( isset($options['show']) ) $this->loading['limit'] = $options['show'];
		if ( isset($options['published']) ) $this->loading['published'] = $options['published'];
		if ( isset($options['paged']) ) $this->loading['paged'] = $options['paged'];

		// No search
		if ( empty($options['search']) ) $options['search'] = __('(no search terms)', 'Shopp');
		$this->name = sprintf(__('Search Results for: %s', 'Shopp'), esc_html($options['search']));

	}

	public function pagelink ($page) {
		$link = parent::pagelink($page);
		return add_query_arg(array('s' => urlencode($this->search), 's_cs' => 1), $link);
	}

	public function url ($result, $options, $O) {
		if ( get_class($this) != get_class($O) ) return $result;
		if ( ! isset($this->search) || ! isset($O->search) ) return $result;
		if ( $this->search != $O->search ) return $result;
		return add_query_arg(array('s' => urlencode($this->search), 's_cs' => 1), $result);
	}

}


/**
 * A smart collection for grouping products using multiple WordPress taxonomies
 *
 * @deprecated Multiple taxonomy support is enabled across all collections
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class MixProducts extends SmartCollection {

	public static $slugs = array('mixed');
	public static $_menu = false;

	public static function name () {
		return Shopp::__('Mixed Products');
	}

	public function smart ( array $options = array() ) {

		$defaults = array(
			'name' => self::name(),
			'relation' => 'AND',
			'field' => 'name',
			'include_children' => true,
			'operator' => 'IN',
			'taxquery' => false
		);
		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		$relationships = array('AND', 'OR');
		$relation = in_array($relation,$relationships)?$relation:$defaults['relation'];

		$operators = array('IN', 'NOT IN', 'AND');
		$operator = in_array($operator,$operators)?$operator:$defaults['operator'];


		$settings = array(
			'relation' => $relation,
			'include_children' => $include_children,
			'field' => $field,
		);

		if (false === $taxquery) {
			$taxquery = $settings;
			// Parse taxonomy term options
			foreach ($options['taxonomy'] as $i => $taxonomy)
				$taxquery[ $i ]['taxonomy'] = $taxonomy;

			foreach ($options['terms'] as $i => $terms) {
				$taxquery[ $i ]['terms'] = explode(',',$terms);
				$taxquery[ $i ]['field'] = $field;
				$taxquery[ $i ]['operator'] = $operator;
			}
		} else $taxquery = array_merge($settings,$taxquery);

		$this->loading['taxquery'] = $taxquery;
		$this->loading['debug'] = false;

	}

}

/**
 * A smart collection to get products that have at least one of the specified tags
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class TagProducts extends SmartCollection {

	public static $slugs = array('tag');
	static $_menu = false;

	public static function name () {
		return Shopp::__('Tagged Products');
	}

	public function smart ( array $options = array() ) {
		if ( ! isset($options['tag'])) {
			new ShoppError('No tag option provided for the requested TagProducts collection','doing_it_wrong',SHOPP_DEBUG_ERR);
			return false;
		}

		$this->tag = stripslashes(urldecode($options['tag']));

		$terms = array();

		$term = get_term_by('name', $this->tag, ProductTag::$taxon);

		if ( false !== strpos($options['tag'], ',') ) {
			$tags = explode(',', $options['tag']);
			foreach ( $tags as $tag ) {
				$term = get_term_by('name', $tag, ProductTag::$taxon);
				$terms[] = $term->term_id;
			}
		} else $terms[] = $term->term_id;

		if ( empty($terms) ) return;

		$this->name = isset($options['title']) ? $options['title'] : Shopp::__('Products tagged &quot;%s&quot;', $this->tag);
		$this->uri = urlencode($this->tag);

		global $wpdb;
		$joins = array();
		$joins[ $wpdb->term_relationships ] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
		$joins[ $wpdb->term_taxonomy ] = "INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id=tt.term_taxonomy_id";
		$where = array("tt.term_id IN (" . join(',', $terms) . ")");
		$columns = 'COUNT(p.ID) AS score';
		$groupby = 'p.ID';
		$orderby = 'score DESC';
		$loading = compact('columns', 'joins', 'where', 'groupby', 'orderby');
		$this->loading = array_merge($options, $loading);

	}

	public function pagelink ($page) {
		global $wp_rewrite;
		$termurl = get_term_link($this->tag,ProductTag::$taxon);

		$alpha = (false !== preg_match('/([A-Z]|0\-9)/',$page));
		$prettyurl = trailingslashit($termurl).($page > 1 || $alpha?"page/$page":"");

		$queryvars = array($this->taxonomy=>$this->slug);
		if ($page > 1 || $alpha) $queryvars['paged'] = $page;

		$url = $wp_rewrite->using_permalinks() ? user_trailingslashit($prettyurl) : add_query_arg($queryvars,$categoryurl);

		return apply_filters('shopp_paged_link', $url, $page);
	}
}

/**
 * A smart collection that groups related products by scoring the number of tags shared across products
 *
 * This collection works very differently than the TagProducts collection. TagProducts finds products by
 * tags that you provide. RelatedProducts finds products using tags of a product that you provide.
 * The other difference is that RelatedProducts will score the results so that products that have more
 * tags in common with the provided product will be scored with higher relavancy and shown first.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class RelatedProducts extends SmartCollection {

	public static $slugs = array('related');
	public static $_menu = false;
	public $product = false;

	public static function name () {
		return Shopp::__('Related Products');
	}

	public function smart ( array $options = array() ) {
		$where = array();
		$scope = array();

		$Product = ShoppProduct();
		$Order = ShoppOrder();
		$Cart = $Order->Cart;

		// Use the current product if available
		if ( ! empty($Product->id) )
			$this->product = ShoppProduct();

		// Or load a product specified
		if ( isset($options['product']) ) {
			if ( 'recent-cartitem' == $options['product'] ) {			// Use most recently added item in the cart
				$this->product = new ShoppProduct($Cart->added()->product);
			} elseif ( intval($options['product']) > 0 ) { 	// Load by specified id
				$this->product = new ShoppProduct( intval($options['product']) );
			} else {
				$this->product = new ShoppProduct($options['product'],'slug'); // Load by specified slug
			}
		}

		if ( isset($options['tagged']) ) {
			$tagged = new ProductTag($options['tagged'],'name');
			if (!empty($tagged->id)) $scope[] = $tagged->id;
			$name = $tagged->name;
			$slug = $tagged->slug;
		}

		if ( ! empty($this->product->id) ) {
			$name = $this->product->name;
			$slug = $this->product->slug;
			$where = array("p.id != {$this->product->id}");
			// Load the product's tags if they are not available
			if ( empty($this->product->tags) )
				$this->product->load_data(array('tags'));

			if ( empty($scope) ) $scope = array_keys($this->product->tags);
		}

		if ( empty($scope) ) return false;

		$this->name = __("Products related to","Shopp")." &quot;".stripslashes($name)."&quot;";
		$this->uri = urlencode($slug);
		$this->controls = false;

		global $wpdb;
		$joins[ $wpdb->term_relationships ] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
		$joins[ $wpdb->term_taxonomy ] = "INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id=tt.term_taxonomy_id";
		$where[] = "tt.term_id IN (" . join(',', $scope) . ")";
		$columns = 'COUNT(p.ID) AS score';
		$groupby = 'p.ID';
		$orderby = 'score DESC';
		$loading = compact('columns', 'joins', 'where', 'groupby', 'orderby');

		$this->loading = array_merge($options, $loading);

		if ( isset($options['order']) ) $this->loading['order'] = $options['order'];
		if ( isset($options['controls']) && Shopp::str_true($options['controls']) )
			unset($this->controls);

	}

}

/**
 * A smart collection for products bought by other customers similar to a given product
 *
 * This uses an advanced algorithm known as the Pearson correlation coefficient to
 * find relevant relationships between the given product and products purchased
 * by other customers. {@see http://en.wikipedia.org/wiki/Pearson_correlation_coefficient}
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class AlsoBoughtProducts extends SmartCollection {

	public static $slugs = array('alsobought');

	public static $_menu = false;
	public $product = false;

	public static function name () {
		return Shopp::__('Also Bought Products');
	}

	public function smart ( array $options = array() ) {
		$this->name = __('Customers also bought&hellip;','Shopp');
		$this->controls = false;

		$where = array("true=false");
		$scope = array();

		$Product = ShoppProduct();
		$Order = ShoppOrder();
		$Cart = $Order->Cart;

		// Use the current product is available
		if ( ! empty($Product->id) )
			$this->product = $Product;

		// Or load a product specified
		if ( ! empty($options['product']) ) {
			if ( 'recent-cartitem' == $options['product'] ) { 			// Use most recently added item in the cart
				$this->product = new ShoppProduct($Cart->added()->product);
			} elseif (preg_match('/^[\d+]$/',$options['product'])) {	// Load by specified id
				$this->product = new ShoppProduct($options['product']);
			} else {
				$this->product = new ShoppProduct($options['product'], 'slug'); // Load by specified slug
			}
		}

		if ( empty($this->product->id) ) {
			$loading = compact('where');
			$this->loading = array_merge($options, $loading);
			return;
		}

		$this->name = Shopp::__('Customers that bought &quot;%s&quot; also bought&hellip;', $this->product->name);

		$purchased = ShoppDatabaseObject::tablename(Purchased::$table);
		$query = "SELECT  p2,((psum - (sum1 * sum2 / n)) / sqrt((sum1sq - pow(sum1, 2.0) / n) * (sum2sq - pow(sum2, 2.0) / n))) AS r, n
								FROM (
									SELECT n1.product AS p1,n2.product AS p2,SUM(n1.quantity) AS sum1,SUM(n2.quantity) AS sum2,
										SUM(n1.quantity * n1.quantity) AS sum1sq,SUM(n2.quantity * n2.quantity) AS sum2sq,
										SUM(n1.quantity * n2.quantity) AS psum,COUNT(*) AS n
									FROM $purchased AS n1
									LEFT JOIN $purchased AS n2 ON n1.purchase = n2.purchase
									WHERE n1.product != n2.product
									GROUP BY n1.product,n2.product
								) AS step1
								ORDER BY r DESC, n DESC";

		$cachehash = 'alsobought_' . md5($query);
		$cached = wp_cache_get($cachehash, 'shopp_collection_alsobought');
		if ( $cached ) $matches = $cached;
		else {
			$matches = sDB::query($query, 'array', 'col', 'p2');
			wp_cache_set($cachehash, $matches, 'shopp_collection_alsobought');
		}

		if ( empty($matches) ) {
			$loading = compact('where');
			$this->loading = array_merge($options, $loading);
			return;
		}

		$where = array("p.id IN (".join(',',$matches).")");
		$loading = compact('columns','joins','where','groupby','order');
		$this->loading = array_merge($options, $loading);

		if (isset($options['controls']) && Shopp::str_true($options['controls']))
			unset($this->controls);
	}

}

/**
 * A smart collection for getting random products from the catalog
 *
 * It's important to note that the randomization is locked to each browsing session.
 * That means that the randomness is generated at the beginning of the session, and
 * each time the collection is viewed the random order will remain in the same state
 * of randomness for each page load. This is useful for showing a random set of products
 * but allowing pagination to not repeat products, which would happen for a random set
 * of products each page load. If you're after that, use the 'chaos' sort order on
 * the CatalogProducts collection.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class RandomProducts extends SmartCollection {

	public static $slugs = array('random');

	public static function name () {
		return Shopp::__('Random Products');
	}

	public function smart ( array $options = array() ) {

		$this->loading['order'] = 'random';

		if ( isset($options['order']) && 'chaos' == strtolower($options['order']) )
			$this->loading['order'] = 'chaos';

		if ( isset($options['exclude']) ) {
			$where = array();
			$excludes = explode(",",$options['exclude']);
			if ( in_array('current-product',$excludes) &&
				isset(ShoppProduct()->id) ) $where[] = '(p.id != '.ShoppProduct()->id.')';
			if ( in_array('featured',$excludes) ) $where[] = "(p.featured='off')";
			if ( in_array('onsale',$excludes) ) $where[] = "(pd.sale='off' OR pr.discount=0)";
			$this->loading['where'] = $where;
		}

		if ( isset($options['columns']) ) $this->loading['columns'] = $options['columns'];
	}
}

/**
 * A smart collection that groups products the visitor has recently viewed
 *
 * A product is considered viewed when the product page is loaded. Up to 25 recently viewed products
 * are kept in the session. The total kept can be adjusted using the shopp_recently_viewed_limit filter hook.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class ViewedProducts extends SmartCollection {

	public static $slugs = array('viewed');

	public static function name () {
		return Shopp::__('Recently Viewed');
	}

	public function smart ( array $options = array() ) {
		$Storefront = ShoppStorefront();
		$viewed = isset($Storefront->viewed) ? array_filter($Storefront->viewed) : array();
		if ( empty($viewed) ) $this->loading['where'] = 'true=false';
		$this->loading['where'] = array("p.id IN (" . join(',', $viewed) . ")");
		if ( isset($options['columns']) ) $this->loading['columns'] = $options['columns'];
	}
}

/**
 * A smart collection that dynamically builds groups of products based on a catalog promotion.
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package collections
 **/
class PromoProducts extends SmartCollection {

	public static $slugs = array('promo');

	public static $_menu = false;

	public static function name () {
		return Shopp::__('Promotional Products');
	}

	public function smart ( array $options = array() ) {
		$id = urldecode($options['id']);

		$Promo = new ShoppPromo($id);
		$this->name = $Promo->name;
		$this->slug = $this->uri = sanitize_title_with_dashes($this->name);

		$pricetable = ShoppDatabaseObject::tablename(ShoppPrice::$table);
		$this->loading['where'] = array("p.id IN (SELECT product FROM $pricetable WHERE 0 < FIND_IN_SET($Promo->id,discounts))");
	}

}

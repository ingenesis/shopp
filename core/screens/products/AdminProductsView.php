<?php
/**
 * AdminProductsView.php
 *
 * Products admin view query generator for sub-views
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Screen view controller for sub-views
 *
 * @since 1.4
 **/
class ShoppAdminProductsView {

	public $views = array();

	protected $view = 'all';

	protected $where = array();
	protected $joins = array();
	protected $limit = false;
	protected $load = array('categories', 'coverimages');
	protected $order = false;
	protected $orderby = false;
	protected $nostock = true;
	protected $debug = false;

	/**
	 * Constructor
	 *
	 * Define available views for this screen and set the initial view
	 *
	 * @param string $view The initial view
	 **/
	public function __construct ( $view = 'all' ) {

		$pricetable = ShoppDatabaseObject::tablename(ShoppPrice::$table);

		$views = array(
			'all' => array(
				'label' => Shopp::__('All'),
				'where' => array("p.post_status!='trash'")
			),

			'published' => array(
				'label' => Shopp::__('Published'),
				'where' => array("p.post_status='publish'")
			),

			'drafts' => array(
				'label' => Shopp::__('Drafts'),
				'where' => array("p.post_status='draft'")
			),

			'onsale' => array(
				'label' => Shopp::__('On Sale'),
				'where' => array("s.sale='on' AND p.post_status != 'trash'")
			),

			'featured' => array(
				'label' => Shopp::__('Featured'),
				'where' => array("s.featured='on' AND p.post_status != 'trash'")
			),

			'bestselling' => array(
				'label' => Shopp::__('Bestselling'),
				'where' => array("p.post_status!='trash'", BestsellerProducts::threshold() . " < s.sold"),
				'order' => 'bestselling'
			),

			'inventory' => array(
				'label'   => Shopp::__('Inventory'),
				'columns' => "pt.id AS stockid,IF(pt.context='variation',CONCAT(p.post_title,': ',pt.label),p.post_title) AS post_title,pt.sku AS sku,pt.stock AS stock",
				'joins'   => array($pricetable => "LEFT JOIN $pricetable AS pt ON p.ID=pt.product"),
				'where'   => array("s.inventory='on' AND p.post_status != 'trash'"),
				'groupby' => 'pt.id',
			),

			'trash' => array(
				'label' => Shopp::__('Trash'),
				'where' => array("p.post_status='trash'")
			)
		);

		// Remove the inventory view when the inventory setting is disabled
		if ( ! shopp_setting_enabled('inventory') )
			unset($views['inventory']);

		$this->views = apply_filters('shopp_products_views', $views);

		if ( ! $view )
			$view = 'all';

		$this->view($view);
	}

	/**
	 * Get or set the current view
	 *
	 * @since 1.4
	 *
	 * @param string $view The view slug to set
	 * @return string The current view slug
	 **/
	public function view ( $view = false ) {
		if ( false === $view )
			return $this->view;

		if ( ! isset($this->views[ $view ]) )
			$view = 'all';

		$this->view = $view;

		$view = $this->views[ $view ];
		foreach ( $view as $property => $value )
			$this->$property = $value;

		return $this->view;
	}

	/**
	 * Set the current view page limit parameter
	 *
	 * @since 1.4
	 *
	 * @param int $page The page number to set
	 * @return void
	 **/
	public function page ( $page = 1 ) {
		if ( ! $page ) $page = 1;

		$this->page = absint($page);

		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$perpage = absint($per_page_option['default']);
		if ( false !== ( $user_perpage = get_user_option($per_page_option['option']) ) )
			$perpage = absint($user_perpage);

		$start = $perpage * ( $this->page - 1 );
		$this->limit = "$start,$perpage";
	}

	/**
	 * Set where clause additions for a product search query
	 *
	 * @since 1.4
	 * @param string $query The product search query
	 * @return void
	 **/
	public function search ( $query ) {
		$SearchResults = new SearchResults(array('search' => $query, 'nostock' => 'on', 'published' => 'off', 'paged' => -1));
		$SearchResults->load();
		$ids = array_keys($SearchResults->products);
		$this->where[] = "p.ID IN (" . join(',', $ids) . ")";
	}

	/**
	 * Set the category filter query parameters
	 *
	 * @since 1.4
	 * @param int $id The category term id
	 * @return void
	 **/
	public function category ( $id ) {
		global $wpdb;
		$this->joins[ $wpdb->term_relationships ] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";

		if ( $id > 0 )
			$this->joins[ $wpdb->term_taxonomy ] = "INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.term_id=$id)";

		if ( -1 == $id ) {
			unset($this->joins[ $wpdb->term_taxonomy ]);
			$this->joins[ $wpdb->term_relationships ] = "LEFT JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
			$this->where[] = 'tr.object_id IS NULL';
		}
	}

	/**
	 * Set query parameters for custom taxonomy filters
	 *
	 * @since 1.4
	 * @param array $list The list of custom taxonomy names and taxonomy term
	 * @return void
	 **/
	public function taxonomies ( array $list ) {
		global $wpdb;
		foreach ( $list as $n => $taxonomy ) {
			global $wpdb;
			$term = get_term_by('slug', $_GET[ $taxonomy ], $taxonomy);
			if ( ! empty($term->term_id) ) {
				$this->joins[ $wpdb->term_relationships . '_' . $n ] = "INNER JOIN $wpdb->term_relationships AS tr$n ON (p.ID=tr$n.object_id)";
				$this->joins[ $wpdb->term_taxonomy . '_' . $n ] = "INNER JOIN $wpdb->term_taxonomy AS tt$n ON (tr$n.term_taxonomy_id=tt$n.term_taxonomy_id AND tt$n.term_id=$term->term_id)";
			}
		}
	}

	/**
	 * Set the query parameter for stock level filters
	 *
	 * @since 1.4
	 * @param string $level The stock level filter
	 * @return void
	 **/
	public function stocklevel ( $level ) {
		switch( $level ) {
			case "ns":  // No stock
				foreach ( $this->where as &$w )
					$w = str_replace("s.inventory='on'", "s.inventory='off'", $w);
				$this->where[] = "s.inventory='off'";
				break;
			case "oos": // Out-of-stock
				$this->where[] = "(s.inventory='on' AND s.stock = 0)";
				break;
			case "ls": // Low stock
				$ls = shopp_setting('lowstock_level');
				if ( empty($ls) )
					$ls = '0';
				$this->where[] = "(s.inventory='on' AND s.lowstock != 'none')";
				break;
			case "is":
				$this->where[] = "(s.inventory='on' AND s.stock > 0)";
		}
	}

	/**
	 * Set the query parameter for column ordering
	 *
	 * @since 1.4
	 * @param string $column The column name
	 * @param string $order The order direction ('asc' or 'desc')
	 * @return void
	 **/
	public function orderby ( $column, $order = 'asc' ) {

		$column = strtolower($column);
		$order = strtolower($order);

		if ( in_array($order, array('asc', 'desc')) )
			$direction = $order;

		if ( isset($this->views[ $this->view ]['order']) )
			$this->order = $this->views[ $this->view ]['order'];

		$columns = array(
			'name' => array('asc' => 'title', 'desc' => 'reverse'),
			'price' => array('asc' => 'lowprice', 'desc' => 'highprice'),
			'date' => array('asc' => 'newest', 'desc' => 'oldest'),
		);

		if ( isset($columns[ $column ][ $direction ]) ) {
			$this->order = $columns[ $column ][ $direction ];
			return;
		}

		switch ( $column ) {
			case 'sold': $this->orderby = "s.sold $direction"; break;
			case 'gross': $this->orderby = "s.grossed $direction"; break;
			case 'inventory': $this->orderby = "s.stock $direction"; break;
			case 'sku': $this->orderby = "pt.sku $direction"; break;
		}

		if ( 'inventory' == $this->view )
			$this->orderby = str_replace('s.', 'pt.', $this->orderby);
	}

	/**
	 * Produces the ShoppProduct query loading parameters
	 *
	 * @since 1.4
	 * @return array The loading parameters
	 **/
	public function loading () {

		$summarytable = ShoppDatabaseObject::tablename(ProductSummary::$table);
		if ( in_array($this->view, array('onsale', 'featured', 'inventory')) )
			$this->joins[ $summarytable ] = "INNER JOIN $summarytable AS s ON p.ID=s.product";

		$loading = array(
			'where'		=> $this->where,
			'joins'		=> $this->joins,
			'limit'		=> $this->limit,
			'load'		=> $this->load,
			'published' => $this->published,
			'nostock'   => $this->nostock,
			'debug'	 => $this->debug
		);

		if ( ! empty($this->order) )
			$loading['order'] = $this->order;

		if ( ! empty($this->orderby) )
			$loading['orderby'] = $this->orderby;

		return $loading;
	}

	/**
	 * Calculates cached sub-view product totals based on the view queries
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function totals () {

		// Get sub-screen counts
		$subcounts = wp_cache_get('shopp_product_subcounts', 'shopp_admin');

		if ( $subcounts ) {
			foreach ($subcounts as $name => $total)
				if ( isset($this->views[ $name ]) ) $this->views[ $name ]['total'] = $total;
			return;
		}

		// Setup queries
		$products = WPDatabaseObject::tablename(ShoppProduct::$table);
		$summary = ShoppDatabaseObject::tablename(ProductSummary::$table);

		$subcounts = array();
		foreach ( $this->views as $name => &$subquery ) {
			$subquery['total'] = 0;
			$query = array(
				'columns' => "count(*) AS total",
				'table' => "$products as p",
				'joins' => array(),
				'where' => array(),
			);

			$query = array_merge($query, $subquery);
			$query['where'][] = "p.post_type='shopp_product'";

			if ( in_array($name, array('onsale', 'bestselling', 'featured', 'inventory')) )
				$query['joins'][ $summary ] = "INNER JOIN $summary AS s ON p.ID=s.product";

			$query = sDB::select($query);
			$subquery['total'] = sDB::query($query, 'auto', 'col', 'total');
			$subcounts[ $name ] = $subquery['total'];
		}
		wp_cache_set('shopp_product_subcounts', $subcounts, 'shopp_admin');

	}

}
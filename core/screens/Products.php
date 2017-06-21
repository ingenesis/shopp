<?php
/**
 * Products.php
 *
 * Products index editor controls
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Package
 * @version   1.0
 * @since
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product admin router
 * 
 * Routes requests to the proper screen controller
 *
 * @since 1.4
 **/
class ShoppAdminProducts extends ShoppAdminPostController {

	protected $ui = 'products';

	protected function route () {
		// @todo implement post type editor
		// if ( ! empty($this->request('post')) && ShoppProduct::posttype() == $this->request('post_type') && 'edit' == $this->request('action') )
		// 	return 'ShoppScreenProductEditor';

		if ( $this->request('id') )
			return 'ShoppScreenProductEditor';
		else return 'ShoppScreenProducts';
	}

}

/**
 * Screen controller for the catalog manager
 *
 * @since 1.4
 **/
class ShoppScreenProducts extends ShoppScreenController {

	public $worklist = array();
	public $products = array();
	public $views = array();

	protected $ui = 'products';

	/**
	 * Registers actions for the catalog products screen
	 *
	 * @version 1.4
	 *
	 * @return array The list of actions to handle
	 **/
	public function actions () {
		return array(
			'bulkaction',
			'emptytrash',
			'duplicate'
		);
	}

	/**
	 * Handle bulk actions
	 *
	 * Publish, Unpublish,Move to Trash, Feature and De-feature
	 *
	 * @version 1.4
	 * @return void
	 **/
	public function bulkaction() {
		$actions = array('publish', 'unpublish', 'trash', 'restore', 'feature', 'defeature');

		$request = $this->request('action');
		$selected = (array)$this->request('selected');
		$selected = array_map('intval', $selected);

		if ( ! in_array($request, $actions) ) return;
		elseif ( empty($selected) ) return;

		if ( 'publish' == $request )
			ShoppProduct::publishset($selected, 'publish');
		elseif ( 'unpublish' == $request )
			ShoppProduct::publishset($selected, 'draft');
		elseif ( 'trash' == $request )
			ShoppProduct::publishset($selected, 'trash');
		elseif ( 'restore' == $request )
			ShoppProduct::publishset($selected, 'draft');
		elseif( 'feature' == $request )
			ShoppProduct::featureset($selected, 'on');
		elseif ( 'defeature' == $request )
			ShoppProduct::featureset($selected, 'off');

		Shopp::redirect( $this->url(array('action' => null, 'selected' => null)) );
	}

	/**
	 * Duplicates a requested product
	 *
	 * @version 1.4
	 * @return void
	 **/
	public function duplicate () {
		$duplicate = $this->request('duplicate');
		if ( ! $duplicate ) return;
		if ( ! current_user_can('shopp_products') ) return;

		$Product = new ShoppProduct($duplicate);
		$Product->duplicate();
		$this->index($Product);

		Shopp::redirect( $this->url(array('duplicate' => null)) );
	}
	
	/**
	 * Handles emptying products in the trash view
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function emptytrash () {
		if ( ! $this->request('delete_all') ) return;
		
		$Template = new ShoppProduct();
		$trash = sDB::query("SELECT ID FROM $Template->_table WHERE post_status='trash' AND post_type='" . ShoppProduct::$posttype . "'", 'array', 'col', 'ID');
		foreach ( $trash as $id ) {
			$Product = new ShoppProduct($id);
			$Product->delete();
		}
		
		Shopp::redirect( $this->url(array('delete_all' => null)) );		
	}

	/**
	 * Handles loading, saving and deleting products in the context of workflows
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return void
	 **/
	// public function workflow () {
	//	 global $Shopp,$post;
	//
	//	 $defaults = array(
	//		 'page' => false,
	//		 'action' => false,
	//		 'selected' => array(),
	//		 'id' => false,
	//		 'save' => false,
	//		 'duplicate' => false,
	//		 'next' => false
	//	 );
	//	 $args = array_merge($defaults, $_REQUEST);
	//	 extract($args, EXTR_SKIP);
	//
	//	 if ( ! is_array($selected) ) $selected = array($selected);
	//
	//	 if ( ! defined('WP_ADMIN') || ! isset($page)
	//		 || $this->Admin->pagename('products') != $page )
	//			 return false;
	//
	//	 $adminurl = admin_url('admin.php');
	//
	//	 if ( $this->Admin->pagename('products') == $page && ( false !== $action || isset($_GET['delete_all']) ) ) {
	//		 if (isset($_GET['delete_all'])) $action = 'emptytrash';
	//		 switch ($action) {
	//			 case 'publish':	 ShoppProduct::publishset($selected,'publish'); break;
	//			 case 'unpublish':	 ShoppProduct::publishset($selected,'draft'); break;
	//			 case 'feature':	 ShoppProduct::featureset($selected,'on'); break;
	//			 case 'defeature':	 ShoppProduct::featureset($selected,'off'); break;
	//			 case 'restore':	 ShoppProduct::publishset($selected,'draft'); break;
	//			 case 'trash':		 ShoppProduct::publishset($selected,'trash'); break;
	//			 case 'delete':
	//				 foreach ($selected as $id) {
	//					 $P = new ShoppProduct($id); $P->delete();
	//				 } break;
	//			 case 'emptytrash':
	//				 $Template = new ShoppProduct();
	//				 $trash = sDB::query("SELECT ID FROM $Template->_table WHERE post_status='trash' AND post_type='".ShoppProduct::posttype()."'",'array','col','ID');
	//				 foreach ($trash as $id) {
	//					 $P = new ShoppProduct($id); $P->delete();
	//				 } break;
	//		 }
	//		 wp_cache_delete( 'shopp_product_subcounts' );
	//		 $redirect = add_query_arg( $_GET, $adminurl );
	//		 $redirect = remove_query_arg( array('action','selected','delete_all'), $redirect );
	//		 Shopp::redirect( $redirect );
	//	 }
	//
	//	 if ($duplicate) {
	//		 $Product = new ShoppProduct($duplicate);
	//		 $Product->duplicate();
	//		 $this->index($Product);
	//		 Shopp::redirect( add_query_arg(array('page' => $this->Admin->pagename('products'), 'paged' => $_REQUEST['paged']), $adminurl) );
	//	 }
	//
	//	 if (isset($id) && $id != "new") {
	//		 $Shopp->Product = new ShoppProduct($id);
	//		 $Shopp->Product->load_data();
	//
	//		 // Adds CPT compatibility support for third-party plugins/themes
	//		 global $post;
	//		 if( is_null($post) ) $post = get_post($Shopp->Product->id);
	//
	//	 } else $Shopp->Product = new ShoppProduct();
	//
	//	 if ($save) {
	//		 wp_cache_delete('shopp_product_subcounts');
	//		 $this->save($Shopp->Product);
	//		 $this->notice( sprintf(__('%s has been saved.','Shopp'),'<strong>'.stripslashes($Shopp->Product->name).'</strong>') );
	//
	//		 // Workflow handler
	//		 if (isset($_REQUEST['settings']) && isset($_REQUEST['settings']['workflow'])) {
	//			 $workflow = $_REQUEST['settings']['workflow'];
	//			 $worklist = $this->worklist;
	//			 $working = array_search($id,$this->worklist);
	//
	//			 switch($workflow) {
	//				 case 'close': $next = 'close'; break;
	//				 case 'new': $next = 'new'; break;
	//				 case 'next': $key = $working+1; break;
	//				 case 'previous': $key = $working-1; break;
	//				 case 'continue': $next = $id; break;
	//			 }
	//
	//			 if (isset($key)) $next = isset($worklist[$key]) ? $worklist[$key] : 'close';
	//		 }
	//
	//		 if ($next) {
	//			 $query = $_GET;
	//			 if ( isset($this->worklist['query']) ) $query = array_merge($_GET, $this->worklist['query']);
	//			 $redirect = add_query_arg($query,$adminurl);
	//			 $cleanup = array('action','selected','delete_all');
	//			 if ('close' == $next) { $cleanup[] = 'id'; $next = false; }
	//			 $redirect = remove_query_arg($cleanup, $redirect);
	//			 if ($next) $redirect = add_query_arg('id',$next,$redirect);
	//			 Shopp::redirect($redirect);
	//		 }
	//
	//		 if (empty($id)) $id = $Shopp->Product->id;
	//		 $Shopp->Product = new ShoppProduct($id);
	//		 $Shopp->Product->load_data();
	//	 }
	//
	//	 // WP post type editing support for other plugins
	//	 if (!empty($Shopp->Product->id))
	//		 $post = get_post($Shopp->Product->id);
	//
	// }


	/**
	 * Loads products for this screen view
	 * 
	 * @since 1.4
	 * @return void
	 **/
	public function loader () {

		if ( ! current_user_can('shopp_products') ) return;

		$View = new ShoppScreenProductsView($this->request('view'));
		$View->page($this->request('paged'));

		if ( $query = $this->request('s') )
			$View->search($query);

		if ( $category_id = $this->request('cat') );
			$View->category($category_id);

		// Detect custom taxonomies
		$taxonomies = array_intersect(get_object_taxonomies(ShoppProduct::$posttype), array_keys($_GET));
		if ( $taxonomies )
			$View->taxonomies($taxonomies);

		if ( $stocklevel = $this->request('sl') )
			$View->stocklevel($stocklevel);

		$View->orderby($this->request('orderby'), $this->request('order'));

		$loading = $View->loading();

		$this->products = new ProductCollection();
		$this->products->load($loading);

		// Return a list of product keys for workflow list requests
		// if ( $workflow )
		//			 return $this->products->worklist();

		$View->totals(); // Get sub-screen counts

		// Keep track of our views
		$this->views = $View->views;
		$this->view = $View->view();
	}

	/**
	 * Interface processor for the product list manager
	 *
	 * @since 1.0
	 * @version 1.4
	 *
	 * @param boolean $workflow True to get workflow data
	 * @return void
	 **/
	public function screen () {

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Explicitly recall the loader to load products inside the admin content
		$this->loader();

		$categories_menu = wp_dropdown_categories(array(
			'show_option_all'  => Shopp::__('View all categories'),
			'show_option_none' => Shopp::__('Uncategorized'),
			'hide_empty'	   => 0,
			'hierarchical'	   => 1,
			'show_count'	   => 0,
			'orderby'		   => 'name',
			'selected'		   => $this->request('cat'),
			'echo'			   => 0,
			'taxonomy'		   => 'shopp_category'
		));

		if ( shopp_setting_enabled('inventory') ) {
			$inventory_filters = array(
				'all' => Shopp::__('View all products'),
				'is'  => Shopp::__('In stock'),
				'ls'  => Shopp::__('Low stock'),
				'oos' => Shopp::__('Out-of-stock'),
				'ns'  => Shopp::__('Not stocked')
			);
			$inventory_menu = '<select name="sl">' . Shopp::menuoptions($inventory_filters, $this->request('sl'), true) . '</select>';
		}

		$actions_menu = array(
			'publish'   => Shopp::__('Publish'),
			'unpublish' => Shopp::__('Unpublish'),
			'feature'   => Shopp::__('Feature'),
			'defeature' => Shopp::__('De-feature'),
			'trash'	 => Shopp::__('Move to trash')
		);

		if ( 'trash' == $this->view ) {
			$actions_menu = array(
				'restore' => Shopp::__('Restore'),
				'delete'  => Shopp::__('Delete permanently')
			);
		}

		// Setup URL for this page
		$url = add_query_arg(
			array_merge($this->request(), array('page' => $this->pagename) ),
			admin_url('admin.php')
		);

		// Get user defined pagination preferences
		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$per_page = $per_page_option['default'];
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) )
			$per_page = $user_per_page;


		// Setup UI
		$views = $this->views;
		$view = $this->view;

		$ui = 'products.php';
		if ( 'inventory' == $view ) {
			$ui = 'inventory.php';
			$per_page = 50;
		}

		// Setup pagination for the list table
		$num_pages = ceil($this->products->total / $per_page);
		$ListTable = ShoppUI::table_set_pagination($this->id, $this->products->total, $num_pages, $per_page );

		include $this->ui($ui, array($categories_menu, $inventory_menu, $actions_menu, $url, $views, $view, $ListTable));
	}

	/**
	 * Registers the column headers for the product list manager
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function layout () {

		$headings = array(
			'default' => array(
				'cb'		=> '<input type="checkbox" />',
				'name'	  => Shopp::__('Name'),
				'category'  => Shopp::__('Category'),
				'price'	 => Shopp::__('Price'),
				'inventory' => Shopp::__('Inventory'),
				'featured'  => Shopp::__('Featured'),
				'date'	  => Shopp::__('Date')
			),
			'inventory' => array(
				'inventory' => Shopp::__('Inventory'),
				'sku'	   => Shopp::__('SKU'),
				'name'	  => Shopp::__('Name')
			),
			'bestselling' => array(
				'cb'		=> '<input type="checkbox" />',
				'name'	  => Shopp::__('Name'),
				'sold'	  => Shopp::__('Sold'),
				'gross'	 => Shopp::__('Sales'),
				'price'	 => Shopp::__('Price'),
				'inventory' => Shopp::__('Inventory'),
				'featured'  => Shopp::__('Featured'),
				'date'	  => Shopp::__('Date')
			)
		);

		$columns = isset($headings[ $this->request('view') ]) ? $headings[ $this->request('view') ] : $headings['default'];

		add_screen_option( 'per_page', array(
			'label' => Shopp::__('Products Per Page'),
			'default' => 20,
			'option' => 'edit_' . ShoppProduct::$posttype . '_per_page'
		));

		// Remove inventory column if inventory tracking is disabled
		if ( ! shopp_setting_enabled('inventory') ) 
			unset($columns['inventory']);

		// Remove category column from the "trash" view
		if ( 'trash' == $this->view ) 
			unset($columns['category']);

		ShoppUI::register_column_headers('toplevel_page_shopp-products', apply_filters('shopp_manage_product_columns', $columns));
	}

	/**
	 * Loads all categories for the product list manager category filter menu
	 *
	 * @author Jonathan Davis
	 * @return string HTML for a drop-down menu of categories
	 **/
	public function category ($id) {
		global $wpdb;
		$p = "$wpdb->posts AS p";
		$where = array();
		$joins[$wpdb->term_relationships] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
		$joins[$wpdb->term_taxonomy] = "INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.term_id=$id)";

		if (-1 == $id) {
			$joins[$wpdb->term_relationships] = "LEFT JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
			unset($joins[$wpdb->term_taxonomy]);
			$where[] = 'tr.object_id IS NULL';
			$where[] = "p.post_status='publish'";
			$where[] = "p.post_type='shopp_product'";
		}

		$where = empty($where) ? '' : ' WHERE '.join(' AND ',$where);

		if ('catalog-products' == $id)
			$products = sDB::query("SELECT p.id,p.post_title AS name FROM $p $where ORDER BY name ASC",'array','col','name','id');
		else $products = sDB::query("SELECT p.id,p.post_title AS name FROM $p ".join(' ',$joins).$where." ORDER BY name ASC",'array','col','name','id');

		return menuoptions($products,0,true);
	}

	public function index ($Product) {
		$Indexer = new IndexProduct($Product->id);
		$Indexer->index();
	}

}

/**
 * Screen view controller for sub-views
 *
 * @since 1.4
 **/
class ShoppScreenProductsView {

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

/**
 * Controller for updating a product from a submitted product form
 *
 * @since 1.4
 **/
class ShoppAdminProductUpdate extends ShoppRequestFormFramework {
	
	/** @var ShoppProduct $Product The target ShoppProduct object to update */
	private $Product = false;
	
	/**
	 * Constructor.
	 *
	 * @since 1.4
	 * @param ShoppProduct $Product The target ShoppProduct object to update
	 * @return void
	 **/
	public function __construct( ShoppProduct $Product ) {
		$this->Product = $Product;
		$this->posted();
	}
	
	/**
	 * Update the product publishing status and publish date
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function status() {

		$status = $this->form('status');
		$publish = (array)$this->form('publish');
		unset($this->form['publish']);
		
		$Product->publish = 0;
		
		// Save current status
		$this->form['prestatus'] = $this->Product->status;
		
		// Set publish date
		if ( 'publish' == $status ) {
			$fields = array('month' => '', 'date' => '', 'year' => '', 'hour' => '', 'minute' => '', 'meridiem' => '');
			$publish = array_intersect_key($publish, $fields);
			
			$publishfields = join('', $publish);
			$this->Product->publish = null;
			if ( ! empty($publishfields) ) {

				if ( 'PM' == $publish['meridiem'] && $publish['hour'] < 12 )
					$publish['hour'] += 12;
				
				$this->Product->publish = mktime($publish['hour'], $publish['minute'], 0, $publish['month'], $publish['date'], $publish['year']);
				
				$Product->status = 'future';
				unset($this->form['status']);
			}		
		}
	}
	
	/**
	 * Update the product with core product data from the form
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function updates () {
		// Set a unique product slug
		if ( empty($this->Product->slug) )
			$this->Product->slug = sanitize_title($this->form('name'));
		$this->Product->slug = wp_unique_post_slug($this->Product->slug, $this->Product->id, $this->Product->status, ShoppProduct::posttype(), 0);

		$this->Product->featured = 'off';
		$this->form['description'] = $this->form('content');
		
		$this->Product->updates($this->form(), array('meta', 'categories', 'prices', 'tags'));
	}
 
 	/**
 	 * Update or delete prices
 	 *
 	 * Depends on ShoppAdminProductPriceUpdate
 	 * 
 	 * @since 1.4
 	 * @return void
 	 **/
	public function prices() {
		
		$deleting = $this->form('deletePrices');
		
		if ( ! empty($deleting) ) {
			$deletes = explode(',', $deletes);

			foreach( $deletes as $option ) {
				$Price = new ShoppPrice($option);
				$Price->delete();
			}
		}
		
		$this->Product->resum();
		
		$formprice = $this->form('price');
		$sortorder = $this->form('sortorder');
		
		if ( ! is_array($formprice) )
			return;
		
		foreach ( $formprice as $index => $form ) {
			$id = empty($form['id']) ? null : intval($form['id']);
			$form['product'] = $this->Product->id;
			$Price = new ShoppPrice($id);
			$PriceUpdate = new ShoppAdminProductPriceUpdate($this->Product, $Price, $form, $sortorder);
			$PriceUpdate->updates($index);
			$PriceUpdate->meta();
			$PriceUpdate->download();

			$this->Product->sumprice($Price);
			unset($Price, $PriceUpdate);
		}

		$this->Product->load_sold($this->Product->id); // Refresh accurate product sales stats
		$this->Product->sumup();
	}
	
	/**
	 * Delete leftover ShoppPrice entries when the defined variant/addon options are deleted
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function emptyprices() {
		// No variation options at all, delete all variation-pricelines
		if ( ! is_array($this->Product->prices) )
			return;
		
		$metadata = $this->form('meta');
		$options = isset($metadata['options']) ? stripslashes_deep($metadata['options']) : false;

		if ( ! empty($options['v']) && ! empty($options['a']) )
			return;
				
		foreach ( $this->Product->prices as $priceline ) {
			if ( $priceline->optionkey == 0 ) 
				continue; // Skip priceline if not tied to variation options
			elseif ( ! empty($options[ substr($priceline->context, 0, 1) ]) ) // skip priceline for 
				continue; // non-empty $options['a'] or $options['v'] depending on priceline context of 'addon' or 'variation'
						
			$Price = new ShoppPrice($priceline->id);
			$Price->delete();
		}
	}
  
  	/**
  	 * Delete, link or update images for the product
  	 *
  	 * @since 1.4
  	 * @return void
  	 **/
	public function images() {
		$deleting = $this->form('deleteImages');
		
		// Remove deleted images
		if ( ! empty($deleting) ) {
			$deletes = array($deleting);
			if ( false !== strpos($deleting, ',') ) 
				$deletes = explode(',', $deleting);
			$this->Product->delete_images($deletes);
		}

		$images = $this->form('images');
		$details = $this->form('imagedetails');
		
		// Update image data
		if ( is_array($images) ) {
			$this->Product->link_images($images);
			$this->Product->save_imageorder($images);

			$this->Product->update_images($details);
		}
	}
	
	/**
	 * Update taxonomies added or removed from the product
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function taxonomies() {
		// Update taxonomies after pricing summary is generated
		// Summary table entry is needed for ProductTaxonomy::recount() to
		// count properly based on aggregate product inventory, see #2968
		$taxonomies = get_object_taxonomies(ShoppProduct::$posttype);
		foreach ( $taxonomies as $taxonomy ) {
			$tags = '';
			$taxonomy_obj = get_taxonomy($taxonomy);
			$tax_input = $this->form('tax_input');

			if ( isset($tax_input[ $taxonomy ]) ) {
				$tags = $tax_input[ $taxonomy ];
				if ( is_array($tags) ) // array = hierarchical, string = non-hierarchical.
					$tags = array_filter($tags);
			}

			if ( current_user_can($taxonomy_obj->cap->assign_terms) )
				wp_set_post_terms( $this->Product->id, $tags, $taxonomy );
		}

		// Ensure taxonomy counts are updated on status changes, see #2968
		if ( $this->form('prestatus') != $this->form('status') ) {
			$Post = new StdClass;
			$Post->ID = $this->Product->id;
			$Post->post_type = ShoppProduct::$posttype;
			wp_transition_post_status($this->form('prestatus'), $this->Product->status, $Post);
		}
		
	}
	
	/**
	 * Delete or update product specs
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function specs() {
		
		$deleting = $this->form('deletedSpecs');
		if ( ! empty($deleting) ) { // Delete specs queued for removal
			$ids = array();
			$deletes = array_map('intval', explode(',', $deleting));

			$ids = sDB::escape(join(',', $deletes));
			$Spec = new Spec();
			sDB::query("DELETE FROM $Spec->_table WHERE id IN ($ids)");
		}
		
		$details = $this->form('details');
		if ( ! is_array($details) )
			return;
		
		$sortorder = $this->form('details-sortorder');
		foreach ( $details as $index => $spec ) {
			$id = isset($spec['new']) ? false : intval($spec['id']);
			if ( in_array($id, $deletes) )
				continue; // Skip deleted specs
			
			$Spec = new Spec($id); // Create or load an existing spec for updates
			$spec['parent'] = $this->Product->id;
			// Sort order is not 0-indexed, so start with 1
			$spec['sortorder'] = 1 + array_search($index, $sortorder);

			$Spec->updates($spec);
			$Spec->save();
		}
		
	}
	
	/**
	 * Update product meta data
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function meta() {
		
		$metadata = $this->form('meta');
		
		if ( ! is_array($metadata) )
			return;

		foreach ( $metadata as $name => $value ) {
			if ( isset($this->Product->meta[ $name ]) ) {
				$Meta = $this->Product->meta[ $name ];
				if ( is_array($Meta) ) 
					$Meta = reset($Meta);
			} else $Meta = new ShoppMetaObject(array(
				'parent' => $this->Product->id,
				'context' => 'product',
				'type' => 'meta',
				'name' => $name
			));
			
			$Meta->parent = $this->Product->id;
			$Meta->context = 'product';
			$Meta->name = $name;
			$Meta->value = $value;
			$Meta->save();
		}
	}

} // end ShoppAdminProductUpdate

/**
 * Controller for updating a product price from a submitted product form
 *
 * @since 1.4
 **/
class ShoppAdminProductPriceUpdate extends ShoppRequestFormFramework {
	
	/** @var ShoppProduct $Product The target ShoppProduct to update */
	private $Product = false;
	
	/** @var ShoppPrice $Price The target ShoppPrice to update */
	private $Price = false;
	
	/** @var array $sortorder The sort order for price options set by the user */
	private $sortorder = false;
	
	/**
	 * Constructor.
	 *
	 * @since 1.4
	 * @param ShoppProduct $Product The target ShoppProduct to update
	 * @param ShoppPrice $Price The target ShoppPrice to update
	 * @param array $form The processed form data
	 * @param array $sortorder The sort order for price options set by the user
	 * @return void Description...
	 **/
	public function __construct( ShoppProduct $Product, ShoppPrice $Price, array $form, $sortorder) {
		$this->Product = $Product;
		$this->Price = $Price;
		$this->form = $form;
		$this->sortorder = $sortorder;
	}
	
	/**
	 * Update the price object from form data
	 *
	 * @since 1.4
	 * @param int $index The index of the price entry in the form data to match with the sortorder
	 * @return void
	 **/
	public function updates( $index ) {
		$form = $this->form;
		$form['sortorder'] = 1 + array_search($index, $this->sortorder);
		$form['shipfee'] = Shopp::floatval($form['shipfee']);
		
		if ( isset($form['recurring']['trialprice']) )
			$form['recurring']['trialprice'] = Shopp::floatval($form['recurring']['trialprice']);
		
		if ( $this->Price->stock != $form['stocked'] ) {
			$form['stock'] = (int) $form['stocked'];
			do_action('shopp_stock_product', $form['stock'], $this->Price, $this->Price->stock, $this->Price->stocklevel);
		} else unset($form['stocked']);
		
		$this->Price->updates($form);
		$this->Price->save();
	}
	
	/**
	 * Update meta data for the product
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function meta() {
		$form = $this->form;
		
		// Save 'price' meta records after saving the price record
		if ( isset($form['dimensions']) && is_array($form['dimensions']) )
			$form['dimensions'] = array_map(array('Shopp', 'floatval'), $form['dimensions']);

		$settings = array('donation', 'recurring', 'membership', 'dimensions');

		$form['settings'] = array();
		foreach ( $settings as $setting )
			if ( isset($form[ $setting ]) )
				$form['settings'][ $setting ] = $form[ $setting ];

		if ( ! empty($form['settings']) ) 
			shopp_set_meta($this->Price->id, 'price', 'settings', $form['settings']);
		
		if ( ! empty($form['options']) ) 
			shopp_set_meta($this->Price->id, 'price', 'options', $form['options']);		
	}
	
	public function download() {
		if ( ! empty($form['download']) )
			$this->Price->attach_download($form['download']);
		elseif ( ! empty($form['downloadpath']) ) {
			$filename = ! empty($form['downloadfile']) ? $form['downloadfile'] : basename(sanitize_path($form['downloadpath']));
			$this->Price->attach_download_by_path($form['downloadpath'], $filename);
		}
	}

} // end ShoppAdminProductPriceUpdate

/**
 * Screen controller for the product editor
 *
 * @since 1.4
 **/
class ShoppScreenProductEditor extends ShoppScreenController {

	/**
	 * Load the requested product for the editor
	 *
	 * @since 1.4
	 * @return ShoppProduct The loaded product based on the request
	 **/
	public function load () {
		$id = $this->request('id');
		$Product = new ShoppProduct($id);
		ShoppProduct($Product);
		return ShoppProduct();
	}

	/**
	 * Setup the screen UI for the product editor
	 *
	 * @return void
	 **/
	public function screen () {
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ( empty($Shopp->Product) ) {
			$Product = new ShoppProduct();
			$Product->status = "publish";
		} else $Product = $Shopp->Product;

		$Product->slug = apply_filters('editable_slug', $Product->slug);
		$permalink = trailingslashit(Shopp::url());

		$pricetypes = ShoppPrice::types();
		$billperiods = ShoppPrice::periods();

		$workflows = array(
			'continue' => Shopp::__('Continue Editing'),
			'close'	=> Shopp::__('Products Manager'),
			'new'	  => Shopp::__('New Product'),
			'next'	 => Shopp::__('Edit Next'),
			'previous' => Shopp::__('Edit Previous')
		);

		$taglist = array();
		foreach ( $Product->tags as $tag )
			$taglist[] = $tag->name;

		if ( $Product->id && ! empty($Product->images) ) {
			$ids = join(',', array_keys($Product->images));
			$CoverImage = reset($Product->images);
			$image_table = $CoverImage->_table;
			$Product->cropped = sDB::query("SELECT * FROM $image_table WHERE context='image' AND type='image' AND '2'=SUBSTRING_INDEX(SUBSTRING_INDEX(name,'_',4),'_',-1) AND parent IN ($ids)",'array','index','parent');
		}

		$shiprates = shopp_setting('shipping_rates');
		if ( ! empty($shiprates) )
			ksort($shiprates);

		$uploader = shopp_setting('uploader_pref');
		if ( ! $uploader ) $uploader = 'flash';

		//$_POST['action'] = add_query_arg(array_merge($_GET, array('page' => ShoppAdmin::pagename('products'))), admin_url('admin.php'));
		$post_type = ShoppProduct::posttype();

		// Re-index menu options to maintain order in JS #2930
		self::keyoptions($Product->options);

		do_action('add_meta_boxes', ShoppProduct::$posttype, $Product);
		do_action('add_meta_boxes_' . ShoppProduct::$posttype, $Product);

		do_action('do_meta_boxes', ShoppProduct::$posttype, 'normal', $Product);
		do_action('do_meta_boxes', ShoppProduct::$posttype, 'advanced', $Product);
		do_action('do_meta_boxes', ShoppProduct::$posttype, 'side', $Product);

		include $this->ui('editor.php', array($post_type, $workflows, $permalink, $pricetypes, $billperiods));
	}

	/**
	 * Re-key product options to maintain order in JS @see #2930
	 * 
	 * @since 1.4
	 * @param array $options The array of product options
	 * @return void
	 **/
	protected static function keyoptions ( &$options ) {
		if ( isset($options['v']) || isset($options['a']) ) {
			$optiontypes = array_keys($options);
			foreach ( $optiontypes as $type ) {
				foreach( $options[ $type ] as $id => $menu ) {
					$options[ $type ][ $type . $id ] = $menu;
					$options[ $type ][ $type . $id ]['options'] = array_values($menu['options']);
					unset($options[ $type ][ $id ]);
				}
			}
		} else {
			foreach ( $options as &$menu )
				$menu['options'] = array_values($menu['options']);
		}
	}

	/**
	 * Handles saving updates from the product editor
	 *
	 * Saves all product related information which includes core product data
	 * and supporting elements such as images, digital downloads, tags,
	 * assigned categories, specs and pricing variations.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param ShoppProduct $Product
	 * @return void
	 **/
	public function save ( ShoppProduct $Product ) {
		check_admin_referer('shopp-save-product');

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		ShoppSettings()->saveform(); // Save workflow setting

		$Update = new ShoppAdminProductUpdate($Product);

		$Update->status();
		$Update->updates();

		do_action('shopp_pre_product_save');
		$Product->save();

		$Update->prices();
		$Product->load_sold($Product->id); // Refresh accurate product sales stats
		$Product->sumup();
		$Update->emptyprices(); // Must occur after sumup()
		
		$Update->images();
		$Update->taxonomies();
		$Update->specs();
		$Update->meta();
		
		$Product->load_data(); // Reload data so everything is fresh for shopp_product_saved

		do_action_ref_array('shopp_product_saved', array(&$Product));
		unset($Product);
	}

	/**
	 * AJAX behavior to process uploaded files intended as digital downloads
	 *
	 * Handles processing a file upload from a temporary file to a
	 * the correct storage container (DB, file system, etc)
	 **/
	public static function downloads () {
		
		$json_error = array('errors' => false);
		$error_contact = ' ' . ShoppLookup::errors('contact', 'server-manager');
		
		if ( isset($_FILES['Filedata']['error']) )
			$json_error['errors'] = ShoppLookup::errors('uploads', $_FILES['Filedata']['error']);
		elseif ( ! is_uploaded_file($_FILES['Filedata']['tmp_name']) )
			$json_error['errors'] = Shopp::__('The file could not be saved because the upload was not found on the server.') . $error_contact;
		elseif ( ! is_readable($_FILES['Filedata']['tmp_name']) )
			$json_error['errors'] = Shopp::__('The file could not be saved because the web server does not have permission to read the upload.') . $error_contact;
		elseif ( $_FILES['Filedata']['size'] == 0 )
			$json_error['errors'] = Shopp::__('The file could not be saved because the uploaded file is empty.') . $error_contact;

		if ( $json_error['errors'] )
			wp_die( json_encode($json_error) );

		list(/* extension */, $mimetype, $properfile) = wp_check_filetype_and_ext($_FILES['Filedata']['tmp_name'], $File->name);

		// Save the uploaded file
		$File = new ProductDownload();
		$File->parent = 0;
		$File->context = "price";
		$File->type = "download";
		
		$File->mime = empty($mimetype) ? 'application/octet-stream' : $mimetype;
		$File->name = $File->filename = empty($properfile) ? $_FILES['Filedata']['name'] : $properfile;

		$File->size = filesize($_FILES['Filedata']['tmp_name']);
		$File->store($_FILES['Filedata']['tmp_name'],'upload');

		$Error = ShoppErrors()->code('storage_engine_save');
		if ( ! empty($Error) )
			wp_die( json_encode( array('error' => $Error->message(true)) ) );

		$File->save();

		do_action('add_product_download', $File, $_FILES['Filedata']);

		echo json_encode(array('id' => $File->id, 'name' => stripslashes($File->name), 'type' => $File->mime, 'size' => $File->size));
	}

	/**
	 * AJAX behavior to process uploaded images
	 **/
	public static function images () {

		$json_error = array('errors' => false);
		$error_contact = ' ' . ShoppLookup::errors('contact', 'server-manager');
		
		$ImageClass = false;
		
		$contexts = array(
			'product' => 'ProductImage', 
			'category' => 'CategoryImage'
		);
		
		$parent = $_REQUEST['parent'];
		$type = strtolower($_REQUEST['type']);

		if ( isset($contexts[ $type ]) )
			$ImageClass = $contexts[ $type ];
		
		if ( isset($_FILES['Filedata']['error']) )
			$json_error['errors'] = ShoppLookup::errors('uploads', $_FILES['Filedata']['error']);
		elseif ( ! $ImageClass )
			$json_error['errors'] = Shopp::__('The file could not be saved because the server cannot tell whether to attach the asset to a product or a category.');
		elseif ( ! is_uploaded_file($_FILES['Filedata']['tmp_name']) )
			$json_error['errors'] = Shopp::__('The file could not be saved because the upload was not found on the server.') . $error_contact;
		elseif ( ! is_readable($_FILES['Filedata']['tmp_name']) )
			$json_error['errors'] = Shopp::__('The file could not be saved because the web server does not have permission to read the upload from the server\'s temporary directory.');
		elseif ( $_FILES['Filedata']['size'] == 0 )
			$json_error['errors'] = Shopp::__('The file could not be saved because the uploaded file is empty.');

		if ( $json_error['errors'] )
			wp_die( json_encode($json_error) );

		// Save the source image
		$Image = new $ImageClass();
		$Image->parent = $parent;
		$Image->type = "image";
		$Image->name = "original";
		$Image->filename = $_FILES['Filedata']['name'];

		list($Image->width, $Image->height, $Image->mime, $Image->attr) = getimagesize($_FILES['Filedata']['tmp_name']);
		$Image->mime = image_type_to_mime_type($Image->mime);
		$Image->size = filesize($_FILES['Filedata']['tmp_name']);

		if ( ! $Image->unique() ) {
			$json_error['errors'] = Shopp::__('The image already exists, but a new filename could not be generated.');
			wp_die(json_encode($json_error));
		}
		
		$Image->store($_FILES['Filedata']['tmp_name'], 'upload');
		$Error = ShoppErrors()->code('storage_engine_save');
		if ( ! empty($Error) )
			wp_die( json_encode( array('error' => $Error->message(true)) ) );

		$Image->save();

		if ( empty($Image->id) )
			wp_die(json_encode(array("error" => Shopp::__('The image reference was not saved to the database.'))));

		wp_die(json_encode(array("id" => $Image->id)));
	}

	/**
	 * Enqueue scripts and style dependencies
	 *
	 * @since 1.2
	 * @return void
	 **/
	public function assets () {
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('postbox');
		wp_enqueue_script('wp-lists');

		if ( user_can_richedit() ) {
			wp_enqueue_script('editor');
			wp_enqueue_script('quicktags');
			add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 20 );
		}

		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('editors');
		shopp_enqueue_script('scalecrop');
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('product-editor');
		shopp_enqueue_script('priceline');
		shopp_enqueue_script('ocupload');
		shopp_enqueue_script('swfupload');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('suggest');
		shopp_enqueue_script('search-select');
		shopp_enqueue_script('shopp-swfupload-queue');

		do_action('shopp_product_editor_scripts');
	}

	/**
	 * Provides overall layout for the product editor interface
	 *
	 * Makes use of WordPress postboxes to generate panels (box) content
	 * containers that are customizable with drag & drop, collapsable, and
	 * can be toggled to be hidden or visible in the interface.
	 *
	 * @author Jonathan Davis
	 * @return
	 **/
	public function layout () {
		$Product = $this->Model;
		$Product->load_data();

		new ShoppAdminProductSaveBox($this, 'side', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));

		// Load all Shopp product taxonomies
		foreach ( get_object_taxonomies(ShoppProduct::$posttype) as $taxonomy_name ) {
			$taxonomy = get_taxonomy($taxonomy_name);
			$label = $taxonomy->labels->name;

			if ( is_taxonomy_hierarchical($taxonomy_name) )
				new ShoppAdminProductCategoriesBox(ShoppProduct::$posttype, 'side', 'core', array( 'Product' => $Product, 'taxonomy' => $taxonomy_name, 'label' => $label ));
			else new ShoppAdminProductTaggingBox(ShoppProduct::$posttype, 'side', 'core', array( 'Product' => $Product, 'taxonomy' => $taxonomy_name, 'label' => $label ));

		}

		new ShoppAdminProductSettingsBox($this, 'side', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductSummaryBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductDetailsBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductImagesBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductPricingBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));

	}



} // class ShoppScreenProductEditor

/**
 * Product editor save meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductSaveBox extends ShoppAdminMetabox {

	protected $id = 'product-save';
	protected $view = 'products/save.php';

	protected function title () {
		return Shopp::__('Save');
	}

}

/**
 * Product editor taxonomy meta box
 *
 * @since 1.4
 **/
class ShoppAdminTaxonomyMetabox extends ShoppAdminMetabox {

	protected $id = '-taxonomy-box';

	public function __construct ( $posttype, $context, $priority, array $args = array() ) {

		$this->references = $args;
		$this->init();
		$this->request($_POST);

		$this->label = $this->references['label'];
		$this->id = $this->references['taxonomy'] . $this->id;

		add_meta_box($this->id, $this->title() . self::help($this->id), array($this, 'box'), $posttype, $context, $priority, $args);

	}

	protected function title () {
		return $this->references['label'];
	}

}

/**
 * Product editor categories meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductCategoriesBox extends ShoppAdminTaxonomyMetabox {

	protected $view = 'products/categories.php';

	public static function popular_terms_checklist ( $post_ID, $taxonomy, $number = 10 ) {
		if ( $post_ID )
			$checked_terms = wp_get_object_terms($post_ID, $taxonomy, array('fields'=>'ids'));
		else
			$checked_terms = array();

		$terms = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $number, 'hierarchical' => false ) );

		$tax = get_taxonomy($taxonomy);
		if ( ! current_user_can($tax->cap->assign_terms) )
			$disabled = 'disabled="disabled"';
		else
			$disabled = '';

		$popular_ids = array();
		foreach ( (array) $terms as $term ) {
			$popular_ids[] = $term->term_id;
			$id = "popular-$taxonomy-$term->term_id";
			$checked = in_array( $term->term_id, $checked_terms ) ? 'checked="checked"' : '';
			?>

			<li id="<?php echo $id; ?>" class="popular-category">
				<label class="selectit">
				<input id="in-<?php echo $id; ?>" type="checkbox" <?php echo $checked; ?> value="<?php echo (int) $term->term_id; ?>" <?php echo $disabled ?>/>
					<?php echo esc_html( apply_filters( 'the_category', $term->name ) ); ?>
				</label>
			</li>

			<?php
		}
		return $popular_ids;
	}

}

/**
 * Product editor tags meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductTaggingBox extends ShoppAdminTaxonomyMetabox {

	protected $view = 'products/tagging.php';

}

/**
 * Product editor settings meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductSettingsBox extends ShoppAdminMetabox {

	protected $id = 'product-settings';
	protected $view = 'products/settings.php';

	protected function title () {
		return Shopp::__('Settings');
	}

	protected function init () {
		$Shopp = Shopp::object();
		$this->references['shiprealtime'] = $Shopp->Shipping->realtime;
	}

}

/**
 * Product editor summary meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductSummaryBox extends ShoppAdminMetabox {

	protected $id = 'product-summary';
	protected $view = 'products/summary.php';

	protected function title () {
		return Shopp::__('Summary');
	}

}

/**
 * Product editor details meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductDetailsBox extends ShoppAdminMetabox {

	protected $id = 'product-details';
	protected $view = 'products/details.php';

	protected function title () {
		return Shopp::__('Details &amp; Specs');
	}

}

/**
 * Product editor images meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductImagesBox extends ShoppAdminMetabox {

	protected $id = 'product-images';
	protected $view = 'products/images.php';

	protected function title () {
		return Shopp::__('Product Images');
	}

}

/**
 * Product editor price meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductPricingBox extends ShoppAdminMetabox {

	protected $id = 'product-pricing-box';
	protected $view = 'products/pricing.php';

	protected function title () {
		return Shopp::__('Pricing');
	}

}

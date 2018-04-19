<?php
/**
 * ScreenProducts.php
 *
 * Screen controller for the catalog products table screen
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenProducts extends ShoppScreenController {

	public $worklist = array();
	public $products = array();
	public $views = array();

	protected $ui = 'products';

	/**
	 * Registers actions for the catalog products screen
	 *
	 * @version 1.5
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
	 * Publish, Unpublish, Move to Trash, Feature and De-feature
	 *
	 * @version 1.5
	 * @return void
	 **/
	public function bulkaction() {
		$actions = array('publish', 'unpublish', 'trash', 'restore', 'feature', 'defeature', 'delete');

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
		elseif ( 'delete' == $request)
			array_walk($selected, 'shopp_rmv_product');

		Shopp::redirect( $this->url(array('action' => null, 'selected' => null)) );
	}

	/**
	 * Duplicates a requested product
	 *
	 * @version 1.5
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
	 * @since 1.5
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
	 * @since 1.5
	 * @return void
	 **/
	public function loader () {

		if ( ! current_user_can('shopp_products') ) return;

		$View = new ShoppScreenProductsView($this->request('view'));
		$View->page($this->request('paged'));

		if ( $query = $this->request('s') )
			$View->search($query);

		if ( $category_id = $this->request('cat') )
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
	 * @version 1.5
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
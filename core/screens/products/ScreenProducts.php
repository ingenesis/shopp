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

	public $products = array();
	public $views = array();

	protected $ui = 'products';

	/**
	 * Registers actions for the catalog products screen
	 *
	 * @version 1.5
	 * @return array The list of actions to handle
	 **/
	public function actions() {
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
		$actions = array(
			'publish' => array(array('ShoppProduct', 'publishset'), 'publish'),
			'unpublish' => array(array('ShoppProduct', 'publishset'), 'draft'),
			'trash' => array(array('ShoppProduct', 'publishset'), 'trash'),
			'restore' => array(array('ShoppProduct', 'publishset'), 'draft'),
			'feature' => array(array('ShoppProduct', 'featureset'), 'on'),
			'defeature' => array(array('ShoppProduct', 'featureset'), 'off'),
			'delete' => array('array_walk', 'shopp_rmv_product')
		);

		$request = $this->request('action');
		if ( ! isset($actions[ $request ]) )
			return;

		$selected = (array)$this->request('selected');
		if ( empty($selected) )
			return;

		$selected = array_map('intval', $selected);

		list($callback, $value) = $actions[ $request ];

		call_user_func($callback, $selected, $value);

		Shopp::redirect( $this->url(array('action' => null, 'selected' => null)) );
	}

	/**
	 * Duplicates a requested product
	 *
	 * @version 1.5
	 * @return void
	 **/
	public function duplicate() {
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
	public function emptytrash() {
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
	 * Loads products for this screen view
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function loader() {
		if ( ! current_user_can('shopp_products') ) return;

		$View = ShoppAdminProducts::view();

		// Handle pagination here instead of AdminProducts
		$page = max(1, absint($this->request('paged')));
		$View->page($page);

		$this->products = new ProductCollection();
		$this->products->load( $View->loading() );

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
	public function screen() {

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
			'trash'	    => Shopp::__('Move to trash')
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
	 * @since 1.3
	 * @return void
	 **/
	public function layout() {

		$headings = array(
			'default' => array(
				'cb'		=> '<input type="checkbox" />',
				'name'	    => Shopp::__('Name'),
				'category'  => Shopp::__('Category'),
				'price'	    => Shopp::__('Price'),
				'inventory' => Shopp::__('Inventory'),
				'featured'  => Shopp::__('Featured'),
				'date'	    => Shopp::__('Date')
			),
			'inventory' => array(
				'inventory' => Shopp::__('Inventory'),
				'sku'	    => Shopp::__('SKU'),
				'name'	    => Shopp::__('Name')
			),
			'bestselling' => array(
				'cb'		=> '<input type="checkbox" />',
				'name'	    => Shopp::__('Name'),
				'sold'	    => Shopp::__('Sold'),
				'gross'	    => Shopp::__('Sales'),
				'price'	    => Shopp::__('Price'),
				'inventory' => Shopp::__('Inventory'),
				'featured'  => Shopp::__('Featured'),
				'date'	    => Shopp::__('Date')
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
	 * @since 1.3
	 *
	 * @param string|int The Shopp product category slug or id
	 * @return string HTML for a drop-down menu of categories
	 **/
	public function category( $id ) {
		global $wpdb;
		$p = "$wpdb->posts AS p";
		$where = array();
		$joins[ $wpdb->term_relationships ] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
		$joins[ $wpdb->term_taxonomy ] = "INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.term_id=$id)";

		if ( -1 == $id ) {
			$joins[ $wpdb->term_relationships ] = "LEFT JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
			unset($joins[ $wpdb->term_taxonomy ]);
			$where[] = 'tr.object_id IS NULL';
			$where[] = "p.post_status='publish'";
			$where[] = "p.post_type='shopp_product'";
		}

		$where = empty($where) ? '' : ' WHERE '.join(' AND ',$where);

		if ('catalog-products' == $id)
			$products = sDB::query("SELECT p.id,p.post_title AS name FROM $p $where ORDER BY name ASC",'array','col','name','id');
		else $products = sDB::query("SELECT p.id,p.post_title AS name FROM $p ".join(' ',$joins).$where." ORDER BY name ASC",'array','col','name','id');

		return Shopp::menuoptions($products, 0, true);
	}

	/**
	 * Creates a search index for a product
	 *
	 * @since 1.3
	 *
	 * @param ShoppProduct $Product The ShoppProduct to index
	 * @return void
	 **/
	public function index ( ShoppProduct $Product ) {
		$Indexer = new IndexProduct($Product->id);
		$Indexer->index();
	}

}
<?php
/**
 * Warehouse.php
 *
 * Flow controller for product management interfaces
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, September 15, 2011
 * @package shopp
 * @subpackage products
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminWarehouse extends ShoppAdminController {

	public $views = array('published', 'drafts', 'onsale', 'featured', 'bestselling', 'inventory', 'trash');
	public $view = 'all';
	public $worklist = array();
	public $products = array();
	public $subs = array();

	protected $ui = 'products';

	/**
	 * Store constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @return void
	 **/
	public function __construct () {
		parent::__construct();

		Shopping::restore('worklist', $this->worklist);

		if ( 'off' == shopp_setting('inventory') )
			array_splice($this->views, 4, 1);

		if ( isset($_GET['view']) && in_array($_GET['view'], $this->views) )
			$this->view = $_GET['view'];

		if ( get_current_screen() )
			get_current_screen()->post_type = ShoppProduct::$posttype;

		if ( ! empty($_GET['id']) ) {
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
			add_action('admin_head',array(&$this,'layout'));

		} else {
			add_action('load-' . $this->screen, array($this,'loader'));
			add_action('admin_print_scripts', array($this, 'columns'));
		}

		if ( 'inventory' == $this->view && shopp_setting_enabled('inventory') )
			do_action('shopp_inventory_manager_scripts');

		add_action('load-' . $this->screen, array($this, 'workflow'));

		do_action('shopp_product_admin_scripts');

		new ContentParser();
		add_action('shopp_product_saved', array($this, 'index'), 99, 1);
	}

	/**
	 * Parses admin requests to determine which interface to display
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @return void
	 **/
	public function admin () {
		if ( ! empty($_GET['id']) ) {
			$this->editor();
		} else {
			$this->manager();

			global $Products;
			if ( 0 == $Products->total ) return;

			// Save workflow list
			$this->worklist = $this->loader(true);
			$this->worklist['query'] = $_GET;
		}
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
	public function workflow () {
		global $Shopp,$post;

		$defaults = array(
			'page' => false,
			'action' => false,
			'selected' => array(),
			'id' => false,
			'save' => false,
			'duplicate' => false,
			'next' => false
		);
		$args = array_merge($defaults, $_REQUEST);
		extract($args, EXTR_SKIP);

		if ( ! is_array($selected) ) $selected = array($selected);

		if ( ! defined('WP_ADMIN') || ! isset($page)
			|| $this->Admin->pagename('products') != $page )
				return false;

		$adminurl = admin_url('admin.php');

		if ( $this->Admin->pagename('products') == $page && ( false !== $action || isset($_GET['delete_all']) ) ) {
			if (isset($_GET['delete_all'])) $action = 'emptytrash';
			switch ($action) {
				case 'publish': 	ShoppProduct::publishset($selected,'publish'); break;
				case 'unpublish': 	ShoppProduct::publishset($selected,'draft'); break;
				case 'feature': 	ShoppProduct::featureset($selected,'on'); break;
				case 'defeature': 	ShoppProduct::featureset($selected,'off'); break;
				case 'restore': 	ShoppProduct::publishset($selected,'draft'); break;
				case 'trash': 		ShoppProduct::publishset($selected,'trash'); break;
				case 'delete':
					foreach ($selected as $id) {
						$P = new ShoppProduct($id); $P->delete();
					} break;
				case 'emptytrash':
					$Template = new ShoppProduct();
					$trash = sDB::query("SELECT ID FROM $Template->_table WHERE post_status='trash' AND post_type='".ShoppProduct::posttype()."'",'array','col','ID');
					foreach ($trash as $id) {
						$P = new ShoppProduct($id); $P->delete();
					} break;
			}
			wp_cache_delete( 'shopp_product_subcounts' );
			$redirect = add_query_arg( $_GET, $adminurl );
			$redirect = remove_query_arg( array('action','selected','delete_all'), $redirect );
			Shopp::redirect( $redirect );
		}

		if ($duplicate) {
			$Product = new ShoppProduct($duplicate);
			$Product->duplicate();
			$this->index($Product);
			Shopp::redirect( add_query_arg(array('page' => $this->Admin->pagename('products'), 'paged' => $_REQUEST['paged']), $adminurl) );
		}

		if (isset($id) && $id != "new") {
			$Shopp->Product = new ShoppProduct($id);
			$Shopp->Product->load_data();

			// Adds CPT compatibility support for third-party plugins/themes
			global $post;
			if( is_null($post) ) $post = get_post($Shopp->Product->id);

		} else $Shopp->Product = new ShoppProduct();

		if ($save) {
			wp_cache_delete('shopp_product_subcounts');
			$this->save($Shopp->Product);
			$this->notice( sprintf(__('%s has been saved.','Shopp'),'<strong>'.stripslashes($Shopp->Product->name).'</strong>') );

			// Workflow handler
			if (isset($_REQUEST['settings']) && isset($_REQUEST['settings']['workflow'])) {
				$workflow = $_REQUEST['settings']['workflow'];
				$worklist = $this->worklist;
				$working = array_search($id,$this->worklist);

				switch($workflow) {
					case 'close': $next = 'close'; break;
					case 'new': $next = 'new'; break;
					case 'next': $key = $working+1; break;
					case 'previous': $key = $working-1; break;
					case 'continue': $next = $id; break;
				}

				if (isset($key)) $next = isset($worklist[$key]) ? $worklist[$key] : 'close';
			}

			if ($next) {
				$query = $_GET;
				if ( isset($this->worklist['query']) ) $query = array_merge($_GET, $this->worklist['query']);
				$redirect = add_query_arg($query,$adminurl);
				$cleanup = array('action','selected','delete_all');
				if ('close' == $next) { $cleanup[] = 'id'; $next = false; }
				$redirect = remove_query_arg($cleanup, $redirect);
				if ($next) $redirect = add_query_arg('id',$next,$redirect);
				Shopp::redirect($redirect);
			}

			if (empty($id)) $id = $Shopp->Product->id;
			$Shopp->Product = new ShoppProduct($id);
			$Shopp->Product->load_data();
		}

		// WP post type editing support for other plugins
		if (!empty($Shopp->Product->id))
			$post = get_post($Shopp->Product->id);

	}

	public function loader ( $workflow = false ) {

		if ( ! current_user_can('shopp_products') ) return;

		add_screen_option( 'per_page', array( 'label' => __('Products Per Page','Shopp'), 'default' => 20, 'option' => 'edit_' . ShoppProduct::$posttype . '_per_page' ) );
		$per_page_option = get_current_screen()->get_option( 'per_page' );

		$defaults = array(
			'cat' => false,
			'paged' => 1,
			'per_page' => $per_page_option['default'],
			's' => '',
			'sl' => '',
			'matchcol' => '',
			'view' => $this->view,
			'is_inventory' => false,
			'is_trash' => false,
			'is_bestselling' => false,
			'categories_menu' => false,
			'inventory_menu' => false,
			'lowstock' => 0,
			'columns' => '',
			'orderby' => '',
			'order' => '',
			'where' => array(),
			'joins' => array()
		);

		$args = array_merge($defaults, $_GET);
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) ) $args['per_page'] = $user_per_page;
		extract($args, EXTR_SKIP);

		$url = ShoppAdminController::url($_GET);

		$subs = array(
			'all' =>        array('label' => Shopp::__('All'),         'where' => array("p.post_status!='trash'")),
			'published' =>  array('label' => Shopp::__('Published'),   'where' => array("p.post_status='publish'")),
			'drafts' =>     array('label' => Shopp::__('Drafts'),      'where' => array("p.post_status='draft'")),
			'onsale' =>     array('label' => Shopp::__('On Sale'),     'where' => array("s.sale='on' AND p.post_status != 'trash'")),
			'featured' =>   array('label' => Shopp::__('Featured'),	   'where' => array("s.featured='on' AND p.post_status != 'trash'")),
			'bestselling'=> array('label' => Shopp::__('Bestselling'), 'where' => array("p.post_status!='trash'", BestsellerProducts::threshold() . " < s.sold"),'order' => 'bestselling'),
			'inventory' => 	array('label' => Shopp::__('Inventory'),   'where' => array("s.inventory='on' AND p.post_status != 'trash'")),
			'trash' =>      array('label' => Shopp::__('Trash'),       'where' => array("p.post_status='trash'"))
		);

		if ( ! shopp_setting_enabled('inventory') ) unset($subs['inventory']);

		switch ( $view ) {
			case 'inventory':
				if ( shopp_setting_enabled('inventory') ) $is_inventory = true;
				else Shopp::redirect(add_query_arg('view', null, $url), true);
				break;
			case 'trash': $is_trash = true; break;
			case 'bestselling': $is_bestselling = true; break;
		}

		if ( $is_inventory ) $per_page = 50;
		$pagenum = absint( $paged );
		$start = ( $per_page * ( $pagenum - 1 ) );

		$where = $subs[ $this->view ]['where'];

		if ( ! empty($s) ) {
			$SearchResults = new SearchResults(array('search' => $s, 'nostock' => 'on', 'published' => 'off', 'paged' => -1));
			$SearchResults->load();
			$ids = array_keys($SearchResults->products);
			$where[] = "p.ID IN (" . join(',', $ids) . ")";
		}

		if ( ! empty($cat) ) {
			global $wpdb;
			$joins[ $wpdb->term_relationships ] = "INNER JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
			$joins[ $wpdb->term_taxonomy ] = "INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.term_id=$cat)";
			if (-1 == $cat) {
				unset($joins[ $wpdb->term_taxonomy ]);
				$joins[ $wpdb->term_relationships ] = "LEFT JOIN $wpdb->term_relationships AS tr ON (p.ID=tr.object_id)";
				$where[] = 'tr.object_id IS NULL';
			}
		}

		// Detect custom taxonomies
		$taxonomies = array_intersect(get_object_taxonomies(ShoppProduct::$posttype), array_keys($_GET));
		if ( ! empty($taxonomies) ) {
			foreach ($taxonomies as $n => $taxonomy) {
				global $wpdb;
				$term = get_term_by('slug', $_GET[ $taxonomy ], $taxonomy);
				if ( ! empty($term->term_id) ) {
					$joins[ $wpdb->term_relationships . '_' . $n ] = "INNER JOIN $wpdb->term_relationships AS tr$n ON (p.ID=tr$n.object_id)";
					$joins[ $wpdb->term_taxonomy . '_' . $n ] = "INNER JOIN $wpdb->term_taxonomy AS tt$n ON (tr$n.term_taxonomy_id=tt$n.term_taxonomy_id AND tt$n.term_id=$term->term_id)";
				}
			}
		}

		if ( ! empty($sl) && shopp_setting_enabled('inventory') ) {
			switch( $sl ) {
				case "ns":
					foreach ( $where as &$w ) $w = str_replace("s.inventory='on'", "s.inventory='off'", $w);
					$where[] = "s.inventory='off'";
					break;
				case "oos":
					$where[] = "(s.inventory='on' AND s.stock = 0)";
					break;
				case "ls":
					$ls = shopp_setting('lowstock_level');
					if ( empty($ls) ) $ls = '0';
					$where[] = "(s.inventory='on' AND s.lowstock != 'none')";
					break;
				case "is": $where[] = "(s.inventory='on' AND s.stock > 0)";
			}
		}

		$lowstock = shopp_setting('lowstock_level');

		// Setup queries
		$pd = WPDatabaseObject::tablename(ShoppProduct::$table);
		$pt = ShoppDatabaseObject::tablename(ShoppPrice::$table);
		$ps = ShoppDatabaseObject::tablename(ProductSummary::$table);

		$orderdirs = array('asc', 'desc');
		if ( in_array($order, $orderdirs) ) $orderd = strtolower($order);
		else $orderd = 'asc';

		if ( isset($subs[ $this->view ]['order']) ) $order = $subs[ $this->view ]['order'];

		$ordercols = '';
		switch ( $orderby ) {
			case 'name': $order = 'title'; if ('desc' == $orderd) $order = 'reverse'; break;
			case 'price': $order = 'lowprice'; if ('desc' == $orderd) $order = 'highprice'; break;
			case 'date': $order = 'newest'; if ('desc' == $orderd) $order = 'oldest'; break;

			case 'sold': $ordercols = 's.sold '.$orderd; break;
			case 'gross': $ordercols = 's.grossed '.$orderd; break;
			case 'inventory': $ordercols = 's.stock '.$orderd; break;
			case 'sku': $ordercols = 'pt.sku '.$orderd; break;
		}

		if ( in_array($this->view, array('onsale', 'featured', 'inventory')) )
			$joins[ $ps ] = "INNER JOIN $ps AS s ON p.ID=s.product";

		$loading = array(
			'where'     => $where,
			'joins'     => $joins,
			'limit'     => "$start,$per_page",
			'load'      => array('categories', 'coverimages'),
			'published' => false,
			'order'     => $order,
			'nostock'   => true,
			// 'debug'  => true
		);

		if ( ! empty($ordercols) ) {
			unset($loading['order']);
			$loading['orderby'] = $ordercols;
		}

		if ( $is_inventory ) { // Override for inventory products
			$where[] = "(pt.context='product' OR pt.context='variation') AND pt.type != 'N/A'";
			$loading = array(
				'columns'   => "pt.id AS stockid,IF(pt.context='variation',CONCAT(p.post_title,': ',pt.label),p.post_title) AS post_title,pt.sku AS sku,pt.stock AS stock",
				'joins'     => array_merge(array($pt => "LEFT JOIN $pt AS pt ON p.ID=pt.product"),$joins),
				'where'     => $where,
				'groupby'   => 'pt.id',
				'orderby'   => str_replace('s.', 'pt.', $ordercols),
				'limit'     => "$start,$per_page",
				'nostock'   => true,
				'published' => false,
				// 'debug'  => true
			);
		}

		// Override loading product meta and limiting by pagination in the workflow list
		if ( $workflow ) {
			unset($loading['limit']);
			$loading['ids'] = true;
			$loading['pagination'] = false;
			$loading['load'] = array();
		};

		$this->products = new ProductCollection();
		$this->products->load($loading);

		// Overpagination protection, redirect to page 1 if the requested page doesn't exist
		$num_pages = ceil($this->products->total / $per_page);
		if ( $paged > 1 && $paged > $num_pages ) Shopp::redirect( add_query_arg('paged', null, $url ) );

		// Return a list of product keys for workflow list requests
		if ( $workflow ) return $this->products->worklist();

		// Get sub-screen counts
		$subcounts = wp_cache_get('shopp_product_subcounts','shopp_admin');
		if ( $subcounts ) {
			foreach ($subcounts as $name => $total)
				if ( isset($subs[ $name ]) ) $subs[ $name ]['total'] = $total;
		} else {
			$subcounts = array();
			foreach ( $subs as $name => &$subquery ) {
				$subquery['total'] = 0;
				$query = array(
					'columns' => "count(*) AS total",
					'table' => "$pd as p",
					'joins' => array(),
					'where' => array(),
				);

				$query = array_merge($query, $subquery);
				$query['where'][] = "p.post_type='shopp_product'";

				if ( in_array($name, array('onsale', 'bestselling', 'featured', 'inventory')) )
					$query['joins'][ $ps ] = "INNER JOIN $ps AS s ON p.ID=s.product";

				$query = sDB::select($query);
				$subquery['total'] = sDB::query($query, 'auto', 'col', 'total');
				$subcounts[ $name ] = $subquery['total'];
			}
			wp_cache_set('shopp_product_subcounts', $subcounts, 'shopp_admin');
		}

		$this->subs = $subs;
	}

	/**
	 * Interface processor for the product list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @param boolean $workflow True to get workflow data
	 * @return void
	 **/
	public function manager () {

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Explicitly recall the loader to reload products inside the admin content
		$this->loader();

		$defaults = array(
			'cat' => false,
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			'sl' => '',
			'matchcol' => '',
			'view' => $this->view,
			'is_inventory' => false,
			'is_trash' => false,
			'is_bestselling' => false,
			'categories_menu' => false,
			'inventory_menu' => false,
			'lowstock' => 0,
			'columns' => '',
			'orderby' => '',
			'order' => '',
			'where' => array(),
			'joins' => array()
		);

		$args = array_merge($defaults,$_GET);
		$per_page_option = get_current_screen()->get_option( 'per_page' );
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) ) $args['per_page'] = $user_per_page;
		extract($args,EXTR_SKIP);

		$url = add_query_arg(array_merge($_GET, array('page' => $this->Admin->pagename('products'))), admin_url('admin.php'));

		if ( empty($categories) ) $categories = array('');

		$categories_menu = wp_dropdown_categories(array(
			'show_option_all' => __('View all categories', 'Shopp'),
			'show_option_none' => __('Uncategorized', 'Shopp'),
			'hide_empty' => 0,
			'hierarchical' => 1,
			'show_count' => 0,
			'orderby' => 'name',
			'selected' => $cat,
			'echo' => 0,
			'taxonomy' => 'shopp_category'
		));

		if ( 'on' == shopp_setting('inventory') ) {
			$inventory_filters = array(
				'all' => __('View all products','Shopp'),
				'is' => __('In stock','Shopp'),
				'ls' => __('Low stock','Shopp'),
				'oos' => __('Out-of-stock','Shopp'),
				'ns' => __('Not stocked','Shopp')
			);
			$inventory_menu = '<select name="sl">'.Shopp::menuoptions($inventory_filters, $sl, true) . '</select>';
		}

		if ( 'off' == shopp_setting('inventory') ) unset($this->subs['inventory']);
		switch ($view) {
			case 'inventory':
				if ( shopp_setting_enabled('inventory') ) $is_inventory = true;
				break;
			case 'trash': $is_trash = true; break;
			case 'bestselling': $is_bestselling = true; break;
		}

		if ($is_inventory) $per_page = 50;

		$pagenum = absint( $paged );
		$start = ($per_page * ($pagenum-1));

		$actions_menu = array(
			'publish' => __('Publish','Shopp'),
			'unpublish' => __('Unpublish','Shopp'),
			'feature' => __('Feature','Shopp'),
			'defeature' => __('De-feature','Shopp'),
			'trash' => __('Move to trash','Shopp')
		);

		if ($is_trash) {
			$actions_menu = array(
				'restore' => __('Restore','Shopp'),
				'delete' => __('Delete permanently','Shopp')
			);
		}

		global $Products;
		$Products = $this->products;
		$num_pages = ceil($Products->total / $per_page);
		$ListTable = ShoppUI::table_set_pagination ($this->screen, $Products->total, $num_pages, $per_page );

		$subs = $this->subs;

		switch ($view) {
			case 'inventory': if (shopp_setting_enabled('inventory')) $ui = 'inventory.php'; break;
			default: $ui = 'products.php'; break;
		}

		include(SHOPP_ADMIN_PATH . '/products/' . $ui);
	}

	/**
	 * Registers the column headers for the product list manager
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function columns () {

		$headings = array(
			'default' => array(
				'cb'=>'<input type="checkbox" />',
				'name'=>__('Name','Shopp'),
				'category'=>__('Category','Shopp'),
				'price'=>__('Price','Shopp'),
				'inventory'=>__('Inventory','Shopp'),
				'featured'=>__('Featured','Shopp'),
				'date'=>__('Date','Shopp')
			),
			'inventory' => array(
				'inventory'=>__('Inventory','Shopp'),
				'sku'=>__('SKU','Shopp'),
				'name'=>__('Name','Shopp')
			),
			'bestselling' => array(
				'cb'=>'<input type="checkbox" />',
				'name'=>__('Name','Shopp'),
				'sold'=>__('Sold','Shopp'),
				'gross'=>__('Sales','Shopp'),
				'price'=>__('Price','Shopp'),
				'inventory'=>__('Inventory','Shopp'),
				'featured'=>__('Featured','Shopp'),
				'date'=>__('Date','Shopp')
			)
		);

		$columns = isset($headings[$this->view]) ? $headings[$this->view] : $headings['default'];

		// Remove inventory column if inventory tracking is disabled
		if ( ! shopp_setting_enabled('inventory') ) unset($columns['inventory']);

		// Remove category column from the "trash" view
		if ('trash' == $this->view) unset($columns['category']);

		ShoppUI::register_column_headers('toplevel_page_shopp-products', apply_filters('shopp_manage_product_columns',$columns));
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
		$Admin = $this->Admin;
		include(SHOPP_ADMIN_PATH."/products/ui.php");
	}

	/**
	 * Interface processor for the product editor
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function editor () {
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ( empty($Shopp->Product) ) {
			$Product = new ShoppProduct();
			$Product->status = "publish";
		} else $Product = $Shopp->Product;

		$Product->slug = apply_filters('editable_slug', $Product->slug);
		$permalink = trailingslashit(Shopp::url());

		$Price = new ShoppPrice();

		$priceTypes = ShoppPrice::types();
		$billPeriods = ShoppPrice::periods();

		$workflows = array(
			'continue' => Shopp::__('Continue Editing'),
			'close' =>    Shopp::__('Products Manager'),
			'new' =>      Shopp::__('New Product'),
			'next' =>     Shopp::__('Edit Next'),
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

		$process = empty($Product->id) ? 'new' : $Product->id;
		$_POST['action'] = add_query_arg(array_merge($_GET, array('page' => $this->Admin->pagename('products'))), admin_url('admin.php'));
		$post_type = ShoppProduct::posttype();

		// Re-index menu options to maintain order in JS #2930
		if ( isset($Product->options['v']) || isset($Product->options['a']) ) {
			$options = array_keys($Product->options);
			foreach ( $options as $type ) {
				foreach( $Product->options[ $type ] as $id => $menu ) {
					$Product->options[ $type ][ $type . $id ] = $menu;
					$Product->options[ $type ][ $type . $id ]['options'] = array_values($menu['options']);
					unset($Product->options[ $type ][ $id ]);
				}
			}
		} else {
			foreach ( $Product->options as &$menu )
				$menu['options'] = array_values($menu['options']);
		}

		do_action('add_meta_boxes', ShoppProduct::$posttype, $Product);
		do_action('add_meta_boxes_' . ShoppProduct::$posttype, $Product);

		do_action('do_meta_boxes', ShoppProduct::$posttype, 'normal', $Product);
		do_action('do_meta_boxes', ShoppProduct::$posttype, 'advanced', $Product);
		do_action('do_meta_boxes', ShoppProduct::$posttype, 'side', $Product);

		include $this->ui('editor.php');
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
	 * @param Product $Product
	 * @return void
	 **/
	public function save ( ShoppProduct $Product ) {
		check_admin_referer('shopp-save-product');

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		ShoppSettings()->saveform(); // Save workflow setting

		$status = $Product->status;
		// Set publish date
		if ('publish' == $_POST['status']) {
			$publishing = isset($_POST['publish'])?$_POST['publish']:array();
			$fields = array('month' => '','date' => '','year' => '','hour'=>'','minute'=>'','meridiem'=>'');
			$publishdate = join('',array_merge($fields,$publishing));
			if (!empty($publishdate)) {
				$publish =& $_POST['publish'];
				if ($publish['meridiem'] == "PM" && $publish['hour'] < 12)
					$publish['hour'] += 12;
				$publish = mktime($publish['hour'],$publish['minute'],0,$publish['month'],$publish['date'],$publish['year']);
				$Product->status = 'future';
				unset($_POST['status']);
			} else {
				unset($_POST['publish']);
				// Auto set the publish date if not set (or more accurately, if set to an irrelevant timestamp)
				if ($Product->publish <= 86400) $Product->publish = null;
			}
		} else {
			unset($_POST['publish']);
			$Product->publish = 0;
		}

		// Set a unique product slug
		if ( empty($Product->slug) )
			$Product->slug = sanitize_title($_POST['name']);
		$Product->slug = wp_unique_post_slug($Product->slug, $Product->id, $Product->status, ShoppProduct::posttype(), 0);

		$Product->featured = 'off';

		if ( isset($_POST['content']) ) $_POST['description'] = $_POST['content'];
		$Product->updates($_POST,array('meta','categories','prices','tags'));

		do_action('shopp_pre_product_save');
		$Product->save();

		// Remove deleted images
		if (!empty($_POST['deleteImages'])) {
			$deletes = array();
			if (strpos($_POST['deleteImages'],",") !== false) $deletes = explode(',',$_POST['deleteImages']);
			else $deletes = array($_POST['deleteImages']);
			$Product->delete_images($deletes);
		}

		// Update image data
		if (!empty($_POST['images']) && is_array($_POST['images'])) {
			$Product->link_images($_POST['images']);
			$Product->save_imageorder($_POST['images']);
			if (!empty($_POST['imagedetails']))
				$Product->update_images($_POST['imagedetails']);
		}

		// Update Prices
		if ( ! empty($_POST['price']) && is_array($_POST['price']) ) {

			// Delete prices that were marked for removal
			if (!empty($_POST['deletePrices'])) {
				$deletes = array();
				if (strpos($_POST['deletePrices'],","))	$deletes = explode(',',$_POST['deletePrices']);
				else $deletes = array($_POST['deletePrices']);

				foreach($deletes as $option) {
					$Price = new ShoppPrice($option);
					$Price->delete();
				}
			}

			$Product->resum();

			// Save prices that there are updates for
			foreach($_POST['price'] as $i => $priceline) {

				if (empty($priceline['id'])) {
					$Price = new ShoppPrice();
					$priceline['product'] = $Product->id;
				} else $Price = new ShoppPrice($priceline['id']);

				$priceline['sortorder'] = array_search($i,$_POST['sortorder'])+1;

				$priceline['shipfee'] = Shopp::floatval($priceline['shipfee']);
				if (isset($priceline['recurring']['trialprice']))
					$priceline['recurring']['trialprice'] = Shopp::floatval($priceline['recurring']['trialprice']);

				if ($Price->stock != $priceline['stocked']) {
					$priceline['stock'] = (int) $priceline['stocked'];
					do_action('shopp_stock_product', $priceline['stock'], $Price, $Price->stock, $Price->stocklevel);
				} else unset($priceline['stocked']);
				$Price->updates($priceline);
				$Price->save();

				// Save 'price' meta records after saving the price record
				if (isset($priceline['dimensions']) && is_array($priceline['dimensions']))
					$priceline['dimensions'] = array_map(array('Shopp', 'floatval'), $priceline['dimensions']);

				$settings = array('donation','recurring','membership','dimensions');

				$priceline['settings'] = array();
				foreach ($settings as $setting) {
					if (! isset($priceline[$setting]) ) continue;
					$priceline['settings'][$setting] = $priceline[$setting];
				}

				if ( ! empty($priceline['settings']) ) shopp_set_meta($Price->id, 'price', 'settings', $priceline['settings']);
				if ( ! empty($priceline['options']) ) shopp_set_meta($Price->id, 'price', 'options', $priceline['options']);

				$Product->sumprice($Price);

				if ( ! empty($priceline['download']) ) $Price->attach_download($priceline['download']);

				if ( ! empty($priceline['downloadpath']) ) { // Attach file specified by URI/path
					if ( ! empty($Price->download->id) || ( empty($Price->download) && $Price->load_download() ) ) {
						$File = $Price->download;
					} else $File = new ProductDownload();

					$stored = false;
					$tmpfile = sanitize_path($priceline['downloadpath']);

					$File->storage = false;
					$Engine = $File->engine(); // Set engine from storage settings

					$File->parent = $Price->id;
					$File->context = "price";
					$File->type = "download";
					$File->name = !empty($priceline['downloadfile'])?$priceline['downloadfile']:basename($tmpfile);
					$File->filename = $File->name;

					if ( $File->found($tmpfile) ) {
						$File->uri = $tmpfile;
						$stored = true;
					} else $stored = $File->store($tmpfile,'file');

					if ( $stored ) {
						$File->readmeta();
						$File->save();
					}

				} // END attach file by path/uri
			} // END foreach()
			unset($Price);
		} // END if (!empty($_POST['price']))

		$Product->load_sold($Product->id); // Refresh accurate product sales stats
		$Product->sumup();

		// Update taxonomies after pricing summary is generated
		// Summary table entry is needed for ProductTaxonomy::recount() to
		// count properly based on aggregate product inventory, see #2968
		foreach ( get_object_taxonomies(Product::$posttype) as $taxonomy ) {
			$tags = '';
			$taxonomy_obj = get_taxonomy($taxonomy);

			if ( isset($_POST['tax_input']) && isset($_POST['tax_input'][$taxonomy]) ) {
				$tags = $_POST['tax_input'][$taxonomy];
				if ( is_array($tags) ) // array = hierarchical, string = non-hierarchical.
					$tags = array_filter($tags);
			}

			if ( current_user_can($taxonomy_obj->cap->assign_terms) )
				wp_set_post_terms( $Product->id, $tags, $taxonomy );
		}

		// Ensure taxonomy counts are updated on status changes, see #2968
		if ( $status != $_POST['status'] ) {
			$Post = new StdClass;
			$Post->ID = $Product->id;
			$Post->post_type = ShoppProduct::$posttype;
			wp_transition_post_status($_POST['status'], $Product->status, $Post);
		}

		if (!empty($_POST['meta']['options']))
			$_POST['meta']['options'] = stripslashes_deep($_POST['meta']['options']);
		else $_POST['meta']['options'] = false;

		// No variation options at all, delete all variation-pricelines
		if (!empty($Product->prices) && is_array($Product->prices)
				&& (empty($_POST['meta']['options']['v']) || empty($_POST['meta']['options']['a']))) {

			foreach ($Product->prices as $priceline) {
				// Skip if not tied to variation options
				if ($priceline->optionkey == 0) continue;
				if ((empty($_POST['meta']['options']['v']) && $priceline->context == "variation")
					|| (empty($_POST['meta']['options']['a']) && $priceline->context == "addon")) {
						$Price = new ShoppPrice($priceline->id);
						$Price->delete();
				}
			}
		}

		// Handle product spec/detail data
		if (!empty($_POST['details']) || !empty($_POST['deletedSpecs'])) {

			// Delete specs queued for removal
			$ids = array();
			$deletes = array();
			if (!empty($_POST['deletedSpecs'])) {
				if (strpos($_POST['deleteImages'],",") !== false) $deletes = explode(',',$_POST['deleteImages']);
				else $deletes = array($_POST['deletedSpecs']);

				$ids = db::escape($_POST['deletedSpecs']);
				$Spec = new Spec();
				db::query("DELETE FROM $Spec->_table WHERE id IN ($ids)");
			}

			if ( is_array($_POST['details']) ) {
				foreach ($_POST['details'] as $i => $spec) {
					if (in_array($spec['id'],$deletes)) continue;
					if (isset($spec['new'])) {
						$Spec = new Spec();
						$spec['id'] = '';
						$spec['parent'] = $Product->id;
					} else $Spec = new Spec($spec['id']);
					$spec['sortorder'] = array_search($i,$_POST['details-sortorder'])+1;

					$Spec->updates($spec);
					$Spec->save();
				}
			}
		}

		// Save any meta data
		if ( isset($_POST['meta']) && is_array($_POST['meta']) ) {
			foreach ( $_POST['meta'] as $name => $value ) {
				if (isset($Product->meta[$name])) {
					$Meta = $Product->meta[$name];
					if (is_array($Meta)) $Meta = reset($Product->meta[$name]);
				} else $Meta = new ShoppMetaObject(array('parent'=>$Product->id,'context'=>'product','type'=>'meta','name'=>$name));
				$Meta->parent = $Product->id;
				$Meta->name = $name;
				$Meta->value = $value;
				$Meta->save();
			}
		}

		$Product->load_data(); // Reload data so everything is fresh for shopp_product_saved

		do_action_ref_array('shopp_product_saved',array(&$Product));

		unset($Product);
	}

	/**
	 * AJAX behavior to process uploaded files intended as digital downloads
	 *
	 * Handles processing a file upload from a temporary file to a
	 * the correct storage container (DB, file system, etc)
	 *
	 * @author Jonathan Davis
	 * @return string JSON encoded result with DB id, filename, type & size
	 **/
	public static function downloads () {

		$error = false;
		if ( isset($_FILES['Filedata']['error']) ) $error = $_FILES['Filedata']['error'];
		if ( $error ) die( json_encode(array("error" => Lookup::errors('uploads', $error))) );

		if (!is_uploaded_file($_FILES['Filedata']['tmp_name']))
			die(json_encode(array("error" => __('The file could not be saved because the upload was not found on the server.','Shopp'))));

		if (!is_readable($_FILES['Filedata']['tmp_name']))
			die(json_encode(array("error" => __('The file could not be saved because the web server does not have permission to read the upload.','Shopp'))));

		if ($_FILES['Filedata']['size'] == 0)
			die(json_encode(array("error" => __('The file could not be saved because the uploaded file is empty.','Shopp'))));

		FileAsset::mimetypes();

		// Save the uploaded file
		$File = new ProductDownload();
		$File->parent = 0;
		$File->context = "price";
		$File->type = "download";
		$File->name = $_FILES['Filedata']['name'];
		$File->filename = $File->name;

		list($extension, $mimetype, $properfile) = wp_check_filetype_and_ext($_FILES['Filedata']['tmp_name'],$File->name);
		if ( empty($mimetype) ) $mimetype = 'application/octet-stream';
		$File->mime = $mimetype;

		if ( ! empty($properfile))
			$File->name = $File->filename = $properfile;

		$File->size = filesize($_FILES['Filedata']['tmp_name']);
		$File->store($_FILES['Filedata']['tmp_name'],'upload');

		$Error = ShoppErrors()->code('storage_engine_save');
		if (!empty($Error)) die( json_encode( array('error' => $Error->message(true)) ) );

		$File->save();

		do_action('add_product_download', $File,$_FILES['Filedata']);

		echo json_encode(array('id' => $File->id, 'name' => stripslashes($File->name), 'type' => $File->mime, 'size' => $File->size));
	}

	/**
	 * AJAX behavior to process uploaded images
	 *
	 * @author Jonathan Davis
	 * @return string JSON encoded result with thumbnail id and src
	 **/
	public static function images () {
		$context = false;

		$error = false;
		if (isset($_FILES['Filedata']['error'])) $error = $_FILES['Filedata']['error'];
		if ( $error ) die( json_encode(array("error" => Lookup::errors('uploads', $error))) );

		$valid_contexts = array('product','category');

		if (isset($_REQUEST['type']) && in_array(strtolower($_REQUEST['type']),$valid_contexts) ) {
			$parent = $_REQUEST['parent'];
			$context = strtolower($_REQUEST['type']);
		}

		if (!$context)
			die(json_encode(array("error" => __('The file could not be saved because the server cannot tell whether to attach the asset to a product or a category.','Shopp'))));

		if (!is_uploaded_file($_FILES['Filedata']['tmp_name']))
			die(json_encode(array("error" => __('The file could not be saved because the upload was not found on the server.','Shopp'))));

		if (!is_readable($_FILES['Filedata']['tmp_name']))
			die(json_encode(array("error" => __('The file could not be saved because the web server does not have permission to read the upload from the server\'s temporary directory.','Shopp'))));

		if ($_FILES['Filedata']['size'] == 0)
			die(json_encode(array("error" => __('The file could not be saved because the uploaded file is empty.','Shopp'))));

		// Save the source image
		if ($context == "category") $Image = new CategoryImage();
		else $Image = new ProductImage();

		$Image->parent = $parent;
		$Image->type = "image";
		$Image->name = "original";
		$Image->filename = $_FILES['Filedata']['name'];
		list($Image->width, $Image->height, $Image->mime, $Image->attr) = getimagesize($_FILES['Filedata']['tmp_name']);
		$Image->mime = image_type_to_mime_type($Image->mime);
		$Image->size = filesize($_FILES['Filedata']['tmp_name']);

		if ( ! $Image->unique() ) die(json_encode(array("error" => __('The image already exists, but a new filename could not be generated.','Shopp'))));

		$Image->store($_FILES['Filedata']['tmp_name'],'upload');
		$Error = ShoppErrors()->code('storage_engine_save');
		if (!empty($Error)) die( json_encode( array('error' => $Error->message(true)) ) );

		$Image->save();

		if (empty($Image->id))
			die(json_encode(array("error" => __('The image reference was not saved to the database.','Shopp'))));

		echo json_encode(array("id"=>$Image->id));
		exit;
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

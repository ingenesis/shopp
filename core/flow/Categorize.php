<?php
/**
 * Categorize
 *
 * Flow controller for category management interfaces
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, September 15, 2011
 * @package shopp
 * @subpackage categories
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCategorize extends ShoppAdminController {

	public $worklist = array();
	protected $ui = 'categories';

	/**
	 * Categorize constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function __construct () {
		parent::__construct();

		ShoppingObject::store('worklist', $this->worklist);

		if ('shopp-tags' == $_GET['page']) {
			wp_redirect(add_query_arg(array('taxonomy'=>ProductTag::$taxon), admin_url('edit-tags.php')));
			return;
		}

		if (!empty($_GET['id']) && !isset($_GET['a'])) {

			wp_enqueue_script('postbox');
			if ( user_can_richedit() ) {
				wp_enqueue_script('editor');
				wp_enqueue_script('quicktags');
				add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 20 );
			}

			shopp_enqueue_script('colorbox');
			shopp_enqueue_script('editors');
			shopp_enqueue_script('category-editor');
			shopp_enqueue_script('priceline');
			shopp_enqueue_script('ocupload');
			shopp_enqueue_script('swfupload');
			shopp_enqueue_script('shopp-swfupload-queue');

			do_action('shopp_category_editor_scripts');
			add_action('admin_head',array($this,'layout'));
		} elseif (!empty($_GET['a']) && $_GET['a'] == 'arrange') {
			shopp_enqueue_script('category-arrange');
			do_action('shopp_category_arrange_scripts');
			add_action('admin_print_scripts',array($this,'arrange_cols'));
		} elseif (!empty($_GET['a']) && $_GET['a'] == 'products') {
			shopp_enqueue_script('products-arrange');
			do_action('shopp_category_products_arrange_scripts');
			add_action('admin_print_scripts',array($this,'products_cols'));
		} else add_action('admin_print_scripts',array($this,'columns'));
		do_action('shopp_category_admin_scripts');

		add_action('load-' . $this->screen, array($this, 'workflow'));
	}

	/**
	 * Parses admin requests to determine which interface to display
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function admin () {
		if ('shopp-tags' == $_GET['page']) return;

		if (!empty($_GET['id']) && !isset($_GET['a'])) $this->editor();
		elseif (!empty($_GET['id']) && isset($_GET['a']) && $_GET['a'] == "products") $this->products();
		else {
			$this->categories();

			// Save workflow list
			$this->worklist = $this->categories(true);
			$this->worklist['query'] = $_GET;
		}
	}

	/**
	 * Handles loading, saving and deleting categories in a workflow context
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function workflow () {
		$Shopp = Shopp::object();

		$defaults = array(
			'apply' => false,
			'page' => false,
			'deleting' => false,
			'delete' => false,
			'id' => false,
			'save' => false,
			'duplicate' => false,
			'next' => false
			);
		$args = array_merge($defaults,$_REQUEST);
		extract($args,EXTR_SKIP);

		if (!defined('WP_ADMIN') || !isset($page)
			|| $page != $this->Admin->pagename('categories'))
				return false;

		$adminurl = admin_url('admin.php');

		if ( $page == $this->Admin->pagename('categories') && false !== $apply && ! empty($delete) ) {
			foreach($delete as $deletion) {
				$Category = new ProductCategory($deletion);
				if (empty($Category->id)) continue;
				$Category->delete();
			}
			$redirect = (add_query_arg(array_merge($_GET,array('delete'=>null,'apply'=>null)),$adminurl));
			shopp_redirect($redirect);
		}

		if ($id && $id != "new")
			$Shopp->Category = new ProductCategory($id);
		else $Shopp->Category = new ProductCategory();

		$meta = array('specs','priceranges','options','prices');
		foreach ($meta as $prop)
			if (!isset($Shopp->Category->$prop)) $Shopp->Category->$prop = array();

		if ($save) {
			$this->save($Shopp->Category);
			$this->notice( sprintf(__('%s has been saved.','Shopp'),'<strong>'.stripslashes($Shopp->Category->name).'</strong>') );

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
				}

				if (isset($key)) $next = isset($worklist[$key]) ? $worklist[$key] : 'close';

			}

			if ($next) {
				if ($next != "new")
					$Shopp->Category = new ProductCategory($next);
				else $Shopp->Category = new ProductCategory();
			} else {
				if (empty($id)) $id = $Shopp->Category->id;
				$Shopp->Category = new ProductCategory($id);
			}

		}

	}

	public function load_category ($term,$taxonomy) {
		$Category = new ProductCategory();
		$Category->populate($term);

		return $Category;
	}
	/**
	 * Interface processor for the category list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function categories ($workflow=false) {

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$defaults = array(
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			'a' => ''
			);
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);

		if ('arrange' == $a)  {
			$this->init_positions();
			$per_page = 300;
		}

		$paged = absint( $paged );
		$start = ($per_page * ($paged-1));
		$end = $start + $per_page;

		$url = add_query_arg(array_merge($_GET,array('page'=>$this->Admin->pagename('categories'))),admin_url('admin.php'));

		$taxonomy = 'shopp_category';

		$filters = array('hide_empty' => 0,'fields'=>'id=>parent');
		add_filter('get_shopp_category',array($this,'load_category'),10,2);

		// $filters['limit'] = "$start,$per_page";
		if (!empty($s)) $filters['search'] = $s;

		$Categories = array(); $count = 0;
		$terms = get_terms( $taxonomy, $filters );
		if (empty($s)) {
			$children = _get_term_hierarchy($taxonomy);
			ProductCategory::tree($taxonomy,$terms,$children,$count,$Categories,$paged,$per_page);
			$this->categories = $Categories;
		} else {
			foreach ($terms as $id => $parent)
				$Categories[$id] = get_term($id,$taxonomy);
		}

		$ids = array_keys($Categories);
		if ($workflow) return $ids;

		$meta = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		if ( ! empty($ids) ) DB::query("SELECT * FROM $meta WHERE parent IN (".join(',',$ids).") AND context='category' AND type='meta'",'array',array($this,'metaloader'));

		$count = wp_count_terms('shopp_category');
		$num_pages = ceil($count / $per_page);

		$ListTable = ShoppUI::table_set_pagination ($this->screen, $count, $num_pages, $per_page );

		$action = esc_url(
			add_query_arg(
				array_merge(stripslashes_deep($_GET),array('page'=>$this->Admin->pagename('categories'))),
				admin_url('admin.php')
			)
		);

		// @todo Rework category arrange ui and updating to use WP taxonomies
		// if ('arrange' == $a) {
		// 	include(SHOPP_ADMIN_PATH."/categories/arrange.php");
		// 	return;
		// }

		include $this->ui('categories.php');
	}

	public function metaloader (&$records,&$record) {
		if (empty($this->categories)) return;
		if (empty($record->name)) return;

		if (is_array($this->categories) && isset($this->categories[ $record->parent ])) {
			$target = $this->categories[ $record->parent ];
		} else return;

		$Meta = new ShoppMetaObject();
		$Meta->populate($record);
		$target->meta[$record->name] = $Meta;
		if (!isset($this->{$record->name}))
			$target->{$record->name} = &$Meta->value;

	}

	/**
	 * Registers column headings for the category list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function columns () {
		ShoppUI::register_column_headers($this->screen, array(
			'cb' => '<input type="checkbox" />',
			'name' => Shopp::__('Name'),
			'slug' => Shopp::__('Slug'),
			'products' => Shopp::__('Products'),
			'templates' => Shopp::__('Templates'),
			'menus' => Shopp::__('Menus')
		));
	}

	/**
	 * Provides the core interface layout for the category editor
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function layout () {
		$Shopp = Shopp::object();
		$Admin =& $Shopp->Flow->Admin;
		include $this->ui('ui.php');
	}

	/**
	 * Registers column headings for the category list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function arrange_cols () {
		register_column_headers('shopp_page_shopp-categories', array(
			'cat' => Shopp::__('Category'),
			'move' => '<div class="move">&nbsp;</div>')
		);
	}

	/**
	 * Interface processor for the category editor
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function editor () {
		global $Shopp,$CategoryImages;

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if (empty($Shopp->Category)) $Category = new ProductCategory();
		else $Category = $Shopp->Category;
		$Category->load_meta();
		$Category->load_images();

		$Price = new ShoppPrice();
		$priceTypes = Price::types();
		$billPeriods = Price::periods();

		// Build permalink for slug editor
		$permalink = trailingslashit(Shopp::url())."category/";
		$Category->slug = apply_filters('editable_slug',$Category->slug);

		$pricerange_menu = array(
			"disabled" => __('Price ranges disabled','Shopp'),
			"auto" => __('Build price ranges automatically','Shopp'),
			"custom" => __('Use custom price ranges','Shopp'),
		);

		$uploader = shopp_setting('uploader_pref');
		if (!$uploader) $uploader = 'flash';

		$workflows = array(
			"continue" => __('Continue Editing','Shopp'),
			"close" => __('Categories Manager','Shopp'),
			"new" => __('New Category','Shopp'),
			"next" => __('Edit Next','Shopp'),
			"previous" => __('Edit Previous','Shopp')
			);

		do_action('add_meta_boxes', ProductCategory::$taxon, $Category);
		do_action('add_meta_boxes_'.ProductCategory::$taxon, $Category);

		do_action('do_meta_boxes', ProductCategory::$taxon, 'normal', $Category);
		do_action('do_meta_boxes', ProductCategory::$taxon, 'advanced', $Category);
		do_action('do_meta_boxes', ProductCategory::$taxon, 'side', $Category);

		include $this->ui('category.php');
	}

	/**
	 * Handles saving updated category information from the category editor
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function save ( $Category ) {
		$Shopp = Shopp::object();
		$db = DB::get();
		check_admin_referer('shopp-save-category');

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		shopp_set_formsettings(); // Save workflow setting

		if (empty($Category->meta))
			$Category->load_meta();

		if (isset($_POST['content'])) $_POST['description'] = $_POST['content'];

		$Category->name = $_POST['name'];
		$Category->description = $_POST['description'];
		$Category->parent = $_POST['parent'];
		$Category->prices = array();

		// Variation price templates
		if (!empty($_POST['price']) && is_array($_POST['price'])) {
			foreach ($_POST['price'] as &$pricing) {
				$pricing['price'] = Shopp::floatval($pricing['price'],false);
				$pricing['saleprice'] = Shopp::floatval($pricing['saleprice'],false);
				$pricing['shipfee'] = Shopp::floatval($pricing['shipfee'],false);
				$pricing['dimensions'] = array_map('Shopp::floatval',$pricing['dimensions']);
			}
		}

		$_POST['prices'] = isset($_POST['price'])?$_POST['price']:array();

		if (empty($_POST['specs'])) $Category->specs = array();

		/* @todo Move the rest of category meta inputs to [meta] inputs eventually */
		if (isset($_POST['meta']) && isset($_POST['meta']['options'])) {
			// Moves the meta options input to 'options' index for compatibility
			$_POST['options'] = $_POST['meta']['options'];
		}

		if (empty($_POST['meta']['options'])
			|| (count($_POST['meta']['options']['v']) == 1 && !isset($_POST['meta']['options']['v'][1]['options']) ) ) {
				$_POST['options'] = $Category->options = array();
				$_POST['prices'] = $Category->prices = array();
		}

		$meta = array('spectemplate','facetedmenus','variations','pricerange','priceranges','specs','options','prices');
		$metadata = array_filter_keys($_POST,$meta);
		foreach ($metadata as $name => $data) {
		    if ( ! isset($Category->meta[ $name ]) || !is_a($Category->meta[ $name ],'ShoppMetaObject') ) {
		        $Meta = new ShoppMetaObject();
		        $Meta->name = $name;
		        $Category->meta[ $name ] = $Meta;
		    }
			$Category->meta[$name]->value = stripslashes_deep($data);
		}

		$Category->save();

		if (!empty($_POST['deleteImages'])) {
			$deletes = array();
			if (strpos($_POST['deleteImages'],","))	$deletes = explode(',', $_POST['deleteImages']);
			else $deletes = array($_POST['deleteImages']);
			$Category->delete_images($deletes);
		}

		if (!empty($_POST['images']) && is_array($_POST['images'])) {
			$Category->link_images($_POST['images']);
			$Category->save_imageorder($_POST['images']);
			if (!empty($_POST['imagedetails']) && is_array($_POST['imagedetails'])) {
				foreach($_POST['imagedetails'] as $i => $data) {
					$Image = new CategoryImage($data['id']);
					$Image->title = $data['title'];
					$Image->alt = $data['alt'];
					$Image->save();
				}
			}
		}

		do_action_ref_array('shopp_category_saved', array($Category));

		$this->notice(Shopp::__('%s category saved.', '<strong>' . $Category->name . '</strong>'));

	}

	/**
	 * Set
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function init_positions () {
		// Load the entire catalog structure and update the category positions
		$Catalog = new ShoppCatalog();
		$Catalog->outofstock = true;

		$filters['columns'] = "cat.id,cat.parent,cat.priority";
		$Catalog->load_categories($filters);

		foreach ($Catalog->categories as $Category)
			if (!isset($Category->_priority) // Check previous priority and only save changes
					|| (isset($Category->_priority) && $Category->_priority != $Category->priority))
				DB::query("UPDATE $Category->_table SET priority=$Category->priority WHERE id=$Category->id");

	}

	/**
	 * Registers column headings for the category list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function products_cols () {
		register_column_headers($this->screen, array(
			'name'      => '<div class="shoppui-spin-align"><div class="shoppui-spinner shoppui-spinfx shoppui-spinfx-steps8"></div></div>',
			'title'     => Shopp::__('Product'),
			'sold'      => Shopp::__('Sold'),
			'gross'     => Shopp::__('Sales'),
			'price'     => Shopp::__('Price'),
			'inventory' => Shopp::__('Inventory'),
			'featured'  => Shopp::__('Featured'),
		));
		add_action('manage_' . $this->screen . '_columns', array($this, 'products_manage_cols'));
	}

	public function products_manage_cols ( $columns ) {
		unset($columns['move']);
		return $columns;
	}

	/**
	 * Interface processor for the product list manager
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function products ( $workflow = false ) {
		$Shopp = Shopp::object();
		$db = DB::get();

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$defaults = array(
			'pagenum' => 1,
			'per_page' => 500,
			'id' => 0,
			's' => ''
		);
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);

		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$start = ($per_page * ($pagenum-1));

		$CategoryProducts = new ProductCategory($id);
		$CategoryProducts->load(array('order'=>'recommended','pagination'=>false));

		$num_pages = ceil($CategoryProducts->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( array('edit'=>null,'pagenum' => '%#%' )),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));

		$action = esc_url(
			add_query_arg(
				array_merge(stripslashes_deep($_GET),array('page'=>$this->Admin->pagename('categories'))),
				admin_url('admin.php')
			)
		);

		include $this->ui('products.php');
	}

}

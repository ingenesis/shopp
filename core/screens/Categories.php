<?php
/**
 * Categories.php
 *
 * Categories admin screen controllers
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Screens/Categories
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Admin controller for product category admin screens
 *
 * This controller routes requests to the proper category sub-screen, and
 * handles overall logic for deleting and saving categories. Special
 * logic is included to handle Category editor workflow behaviors.
 *
 * @todo Need to provide a way for notices to route from this controller to the proper screen display controller
 **/
class ShoppAdminCategories extends ShoppAdminPostController {

	protected $ui = 'categories';

	protected function route () {

		$this->ops();
		$this->workflow();

		if ( 'products' == $this->request('a') && $this->request('id') )
			return 'ShoppScreenCategoryArrangeProducts';
		elseif ( $this->request('id') )
			return 'ShoppScreenCategoryEditor';
		else return 'ShoppScreenCategories';
	}

	/**
	 * Handles other category operations
	 *
	 * Currently only handles delete action.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
    public function ops () {
		$defaults = array(
			'action' => false,
			'selected' => array(),
			'page' => false,
			'id' => false,
			'save' => false,
			'next' => false,
			'_wpnonce' => false
		);
		$args = array_merge($defaults, $_REQUEST);
		extract($args, EXTR_SKIP);

		add_screen_option( 'per_page', array( 'label' => __('Categories Per Page','Shopp'), 'default' => 20, 'option' => 'edit_' . ProductCategory::$taxon . '_per_page' ) );

		if ( 'delete' == $action && wp_verify_nonce($_wpnonce, 'shopp_categories_manager') ) {
			if ( ! empty($id) ) $selected = array($id);
			$total = count($selected);
			foreach ( $selected as $selection ) {
				$DeletedCategory = new ProductCategory($selection);
				$deleted = $DeletedCategory->name;
				$DeletedCategory->delete();
			}
			// TODO Fix $this->notice() calls which can't happen from here (notice() is a ScreenController method, not Admin router method)
			// $this->notice( 1 == $total ? Shopp::__('Deleted %s category.', "<strong>$deleted</strong>") :
			// 							 Shopp::__('Deleted %s categories.', "<strong>$total</strong>") );

			$reset = array('selected' => null, 'action' => null, 'next' => null, 'id' => null, '_wpnonce' => null, );
			$redirect = add_query_arg(array_merge($_GET, $reset), admin_url('admin.php'));
			Shopp::redirect( $redirect );
			exit;
		}
	}

	/**
	 * Handles loading and saving categories in a workflow context
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function workflow () {

		$id = $this->form('id');
		$Category = self::loader($id);

		if ( $this->form('save') ) // Save updates from the editor
			$Category = $this->save($Category);

		$settings = $this->form('settings');
		$workflow = isset($settings['workflow']) ? $settings['workflow'] : false;

		if ( ! $workflow ) return;

		$worklist = $this->worklist();
		$working = array_search($id, $this->worklist());
		$next = 'close';

		switch( $workflow ) {
			case 'new': $next = 'new'; break;
			case 'next': $next = isset($worklist[ ++$working ]) ? $worklist[ $working ] : 'close'; break;
			case 'previous': $next = isset($worklist[ --$working ]) ? $worklist[ $working ] : 'close'; break;
			case 'continue': $next = $Category->id; break;
			case 'close':
			default: $next = 'close';
		}

		if ( 'close' == $next ) {
			$reset = array('action' => null, 'id' => null, '_wpnonce' => null, );
			$redirect = add_query_arg(array_merge($_GET, $reset), admin_url('admin.php'));
			Shopp::redirect( $redirect );
			return;
		}

		$_GET['workflow'] = $next; // Rewrite the request
		$this->query(); // Reprocess the request query

	}

	/**
	 * Builds a list of category IDs based on the current request
	 *
	 * This is used for workflow next/previous handling.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function worklist () {

		$per_page_option = get_current_screen()->get_option( 'per_page' );

		$defaults = array(
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			'a' => ''
		);
		$args = array_merge($defaults, $_GET);
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) ) $args['per_page'] = $user_per_page;
		extract($args, EXTR_SKIP);

		if ('arrange' == $a)  {
			$this->init_positions();
			$per_page = 300;
		}

		$paged = absint( $paged );
		$start = ($per_page * ($paged-1));
		$end = $start + $per_page;

		$url = add_query_arg(array_merge($_GET,array('page'=>ShoppAdmin::pagename('categories'))),admin_url('admin.php'));

		$taxonomy = 'shopp_category';

		$filters = array('hide_empty' => 0, 'fields' => 'id=>parent');
		add_filter('get_shopp_category', array(__CLASS__, 'termcategory'),10,2);

		// $filters['limit'] = "$start,$per_page";
		if (!empty($s)) $filters['search'] = $s;

		$Categories = array(); $count = 0;
		$terms = get_terms( $taxonomy, $filters );
		if (empty($s)) {
			$children = _get_term_hierarchy($taxonomy);
			ProductCategory::tree($taxonomy, $terms, $children, $count, $Categories, $paged, $per_page);
			$this->categories = $Categories;
		} else {
			foreach ($terms as $id => $parent)
				$Categories[$id] = get_term($id,$taxonomy);
		}

		$ids = array_keys($Categories);
		return $ids;
	}

	/**
	 * Handles saving updated category information from the category editor
	 *
	 * @todo refactor complexity
	 * @todo avoid direct access to $_POST
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function save ( $Category ) {
		$Shopp = Shopp::object();

		check_admin_referer('shopp-save-category');

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		shopp_set_formsettings(); // Save workflow setting

		if (empty($Category->meta))
			$Category->load_meta();

		$form = $this->form();

		$Category->name = $this->form('name');
		$Category->description = $this->form('content');
		$Category->parent = $this->form('parent');

		// Sanitize variation price template data
		$Category->prices = array();
		if ( is_array($this->form('price')) ) {
			$prices = $this->form('price');
			foreach ( $prices as &$pricing ) {
				$pricing['price']      = Shopp::floatval($pricing['price'], false);
				$pricing['saleprice']  = Shopp::floatval($pricing['saleprice'], false);
				$pricing['shipfee']    = Shopp::floatval($pricing['shipfee'], false);
				$pricing['dimensions'] = array_map(array('Shopp', 'floatval'), $pricing['dimensions']);
			}
		}

		$Category->specs = array();

		$metafields = array('spectemplate', 'facetedmenus', 'variations', 'pricerange', 'priceranges', 'specs', 'prices');
		$metadata = Shopp::array_filter_keys($this->form(), $metafields);

		// Add meta[options] inputs from varition templates to stored metadata
		$meta = $this->form('meta');
		if ( isset($meta['options']) )
			$metadata['options'] = $meta['options'];

		if ( empty($metadata['options']) || ( 1 == count($metadata['options']['v']) && ! isset($metadata['options']['v'][1]['options']) ) ) {
			// Remove prices or options if no templates are specified or if 1 empty option exists
			unset($metadata['options'], $metadata['prices']);
			$Category->options = $Category->prices = array();
		}

		// Update existing entries
		$updates = array();
		foreach ($Category->meta as $id => $MetaObject) {
			$name = $MetaObject->name;
			if ( isset($metadata[ $name ]) ) {
				$MetaObject->value = stripslashes_deep($metadata[ $name ]);
				$updates[] = $name;
			}
		}

		// Create any new missing meta entries
		$new = array_diff(array_keys($metadata), $updates); // Determine new entries from the exsting updates
		foreach ( $new as $name ) {
			if ( ! isset($metadata[ $name ]) ) continue;
			$Meta = new MetaObject();
			$Meta->name = $name;
			$Meta->value = stripslashes_deep($metadata[ $name ]);
			$Category->meta[] = $Meta;
		}

		$Category->save();

		$deletelist = $this->form('deleteImages');
		if ( ! empty($deletelist) ) {
			$deletes = array();
			if ( false !== strpos($deletelist, ',') )
				$deletes = explode(',', $deletelist);
			else $deletes = array($deletelist);
			$Category->delete_images($deletes);
		}

		$images = $this->form('images');
		if ( ! empty($images) && is_array($images) ) {
			$Category->link_images($images);
			$Category->save_imageorder($images);

			$imgdetails = $this->form('imagedetails');
			if ( ! empty($imagedetails) && is_array($imagedetails) ) {
				foreach($imagedetails as $i => $data) {
					$Image = new CategoryImage($data['id']);
					$Image->title = $data['title'];
					$Image->alt = $data['alt'];
					$Image->save();
				}
			}
		}

		do_action_ref_array('shopp_category_saved', array($Category));

		// TODO fix notice() call
		// $this->notice(Shopp::__('%s category saved.', '<strong>' . $Category->name . '</strong>'));

		return $Category;
	}

	/**
	 * Convert a term to a Product Category
	 *
	 * @since 1.4
	 * @return void
	 */
	public static function termcategory ( $term, $taxonomy ) {
		$Category = new ProductCategory();
		$Category->populate($term);
		return $Category;
	}

	/**
	 * Load a product category for editing
	 *
	 * @since 1.4
	 * @return void
	 */
	public static function loader ( $id ) {
		$Category = new ProductCategory($id);

		$meta = array('specs', 'priceranges', 'options', 'prices');
		foreach ( $meta as $prop )
			if ( ! isset($Category->$prop) ) $Category->$prop = array();

		return $Category;
	}


} // END class ShoppAdminCategories

/**
 * Screen to control the display of the list of categories
 *
 * @since 1.4
 **/
class ShoppScreenCategories extends ShoppScreenController {

	/**
	 * Parses admin requests to determine which interface to display
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function admin () {

		if (!empty($_GET['id']) && !isset($_GET['a'])) $this->editor();
		elseif (!empty($_GET['id']) && isset($_GET['a']) && $_GET['a'] == "products") $this->products();
		else $this->categories();

	}

	/**
	 * Interface processor for the category list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function screen ( $workflow = false ) {

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$per_page_option = get_current_screen()->get_option( 'per_page' );

		$defaults = array(
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			'a' => ''
		);
		$args = array_merge($defaults, $_GET);
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) )
			$args['per_page'] = $user_per_page;
		extract($args, EXTR_SKIP);

		if ('arrange' == $a)  {
			$this->init_positions();
			$per_page = 300;
		}

		$paged = absint( $paged );
		$start = ($per_page * ($paged-1));
		$end = $start + $per_page;

		$url = add_query_arg(array_merge($_GET, array('page' => ShoppAdmin::pagename('categories'))), admin_url('admin.php'));

		$taxonomy = 'shopp_category';

		$filters = array('hide_empty' => 0,'fields'=>'id=>parent');
		add_filter('get_shopp_category', array('ShoppAdminCategories', 'termcategory'), 10, 2);

		// $filters['limit'] = "$start,$per_page";
		if ( ! empty($s) )
			$filters['search'] = $s;

		$count = 0;
		$Categories = array();
		$terms = get_terms( $taxonomy, $filters );
		if ( empty($s) ) {
			$children = _get_term_hierarchy($taxonomy);
			ProductCategory::tree($taxonomy, $terms, $children, $count, $Categories, $paged, $per_page);
			$this->categories = $Categories;
		} else {
			foreach ( $terms as $id => $parent )
				$Categories[ $id ] = get_term($id, $taxonomy);
		}

		$ids = array_keys($Categories);
		if ( $workflow ) return $ids;

		$meta = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		if ( ! empty($ids) ) sDB::query("SELECT * FROM $meta WHERE parent IN (".join(',',$ids).") AND context='category' AND type='meta'",'array', array($this,'metaloader'));

		$count = wp_count_terms('shopp_category');
		$num_pages = ceil($count / $per_page);

		$ListTable = ShoppUI::table_set_pagination ($this->id, $count, $num_pages, $per_page );

		$action = esc_url(
			add_query_arg(
				array_merge( stripslashes_deep($_GET), array('page'=> ShoppAdmin::pagename('categories')) ),
				admin_url('admin.php')
			)
		);

		include $this->ui('categories.php');
	}

	public function metaloader (&$records, &$record) {
		if ( empty($this->categories) ) return;
		if ( empty($record->name) ) return;

		if ( is_array($this->categories) && isset($this->categories[ $record->parent ]) ) {
			$target = $this->categories[ $record->parent ];
		} else return;

		$Meta = new ShoppMetaObject();
		$Meta->populate($record);
		$target->meta[$record->name] = $Meta;
		if ( ! isset($this->{$record->name}) )
			$target->{$record->name} = &$Meta->value;

	}

	/**
	 * Registers column headings for the category list manager
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @return void
	 **/
	public function layout () {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'id'        => Shopp::__('ID'),
			'name'      => Shopp::__('Name'),
			'slug'      => Shopp::__('Slug'),
			'products'  => Shopp::__('Products'),
			'templates' => Shopp::__('Templates'),
			'menus'     => Shopp::__('Menus')
		);
		ShoppUI::register_column_headers($this->id, apply_filters('shopp_manage_category_columns', $columns));
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
	 * Set the positions of categories
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

		foreach ( $Catalog->categories as $Category )
			if ( ! isset($Category->_priority) // Check previous priority and only save changes
					|| (isset($Category->_priority) && $Category->_priority != $Category->priority) )
				sDB::query("UPDATE $Category->_table SET priority=$Category->priority WHERE id=$Category->id");

	}

} // End class ShoppScreenCategories

/**
 * Screen to control setting the product order in a category
 *
 * @since 1.4
 **/
class ShoppScreenCategoryArrangeProducts extends ShoppScreenController {

	/**
	 * Prepare assets for the the interface
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets () {
		shopp_enqueue_script('products-arrange');
		do_action('shopp_category_products_arrange_scripts');
		add_action('admin_print_scripts', array($this, 'products_cols'));
	}

	/**
	 * Registers column headings for the category list manager
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function layout () {
		register_column_headers($this->id, array(
			'name'	    => '<div class="shoppui-spin-align"><div class="shoppui-spinner shoppui-spinfx shoppui-spinfx-steps8 hidden"></div></div>',
			'title'	    => Shopp::__('Product'),
			'sold'	    => Shopp::__('Sold'),
			'gross'	    => Shopp::__('Sales'),
			'price'	    => Shopp::__('Price'),
			'inventory' => Shopp::__('Inventory'),
			'featured'  => Shopp::__('Featured'),
		));
		add_action('manage_' . $this->id . '_columns', array($this, 'products_manage_cols'));
	}

	/**
	 * Removes the move column from the list of columns in the table
	 *
	 * @since 1.4
	 *
	 * @return array list of columns
	 **/
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
	public function screen ( $workflow = false ) {
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
			'base' => add_query_arg( array('edit' => null,'pagenum' => '%#%' )),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));

		$action = esc_url(
			add_query_arg(
				array_merge(stripslashes_deep($_GET),array('page'=>ShoppAdmin::pagename('categories'))),
				admin_url('admin.php')
			)
		);

		include $this->ui('products.php');
	}

} // End class ShoppScreenCategoryArrangeProducts

/**
 * Screen controller of the category editor
 *
 * @since 1.4
 **/
class ShoppScreenCategoryEditor extends ShoppScreenController {

	/**
	 * Load scripts needed for the user interface
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets () {
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
	}

	/**
	 * Provides the core interface layout for the category editor
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function layout () {

		$Category = $this->Model;

		new ShoppAdminCategorySaveBox($this, 'side', 'core', array('Category' => $Category));
		new ShoppAdminCategorySettingsBox($this, 'side', 'core', array('Category' => $Category));

		new ShoppAdminCategoryImagesBox($this, 'normal', 'core', array('Category' => $Category));
		new ShoppAdminCategoryTemplatesBox($this, 'normal', 'core', array('Category' => $Category));

	}

	/**
	 * Load a requested category for the editor
	 *
	 * Handles requested category ID by default, or a blank new category object,
	 * or a workflow requested category ID.
	 *
	 * @since 1.4
	 * @return ProductCategory The loaded category
	 */
	public function load () {

		// Load the requested category ID by default
		$id = (int)$this->request('id');

		// Override to create a new category
		if ( $this->request('new') )
			$id = false;

		// Override with workflow ID
		if ( $this->request('workflow') )
			$id = $this->request('workflow');

		$Category = new ProductCategory($id);

		$meta = array('specs', 'priceranges', 'options', 'prices');
		foreach ( $meta as $prop )
			if ( ! isset($Category->$prop) ) $Category->$prop = array();

		// $Category = ShoppCollection();
		// if ( empty($Category) ) $Category = new ProductCategory();

		$Category->load_meta();
		$Category->load_images();

		return $Category;
	}

	/**
	 * Setup the user interface for the category editor
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function screen () {
		global $CategoryImages;
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$Category = $this->Model;

		$Price = new ShoppPrice();
		$priceTypes = ShoppPrice::types();
		$billPeriods = ShoppPrice::periods();

		// Build permalink for slug editor
		$permalink = trailingslashit(Shopp::url()) . "category/";
		$Category->slug = apply_filters('editable_slug', $Category->slug);

		$uploader = shopp_setting('uploader_pref');
		if (!$uploader) $uploader = 'flash';

		do_action('add_meta_boxes', ProductCategory::$taxon, $Category);
		do_action('add_meta_boxes_'.ProductCategory::$taxon, $Category);

		do_action('do_meta_boxes', ProductCategory::$taxon, 'normal', $Category);
		do_action('do_meta_boxes', ProductCategory::$taxon, 'advanced', $Category);
		do_action('do_meta_boxes', ProductCategory::$taxon, 'side', $Category);

		include $this->ui('category.php');
	}

	/**
	 * Overload Screen process() save calls
	 *
	 * This is a no-op method to allow ShoppAdminCategories::save() to handle saving
	 * during ShoppAdminCategories::workflow()
	 *
	 * @since 1.4
	 * @return void
	 */
	public function save () {
		return;
	}

} // End class ShoppScreenCategoryEditor

/**
 * Sets up the Save box on the category editor screen
 *
 * @since 1.4
 **/
class ShoppAdminCategorySaveBox extends ShoppAdminMetabox {

	protected $id = 'category-save';
	protected $view = 'categories/save.php';

	protected function title () {
		return Shopp::__('Save');
	}

	public function box () {
		$options = array(
			'continue' => Shopp::__('Continue Editing'),
			'close'    => Shopp::__('Category Manager'),
			'new'      => Shopp::__('New Category'),
			'next'     => Shopp::__('Edit Next'),
			'previous' => Shopp::__('Edit Previous')
		);

		$this->references['workflows'] = Shopp::menuoptions($options, shopp_setting('workflow'), true);
		parent::box();
	}

}

/**
 * Sets up the Settings box on the category editor screen
 *
 * @since 1.4
 **/
class ShoppAdminCategorySettingsBox extends ShoppAdminMetabox {

	protected $id = 'category-settings';
	protected $view = 'categories/settings.php';

	protected function title () {
		return Shopp::__('Settings');
	}

	public function box () {
		$this->references['tax'] = get_taxonomy($this->references['Category']->taxonomy);
		parent::box();
	}

}

/**
 * Sets up the Images box on the category editor screen
 *
 * @since 1.4
 **/
class ShoppAdminCategoryImagesBox extends ShoppAdminMetabox {

	protected $id = 'category-images';
	protected $view = 'categories/images.php';

	protected function title () {
		return Shopp::__('Images');
	}

}

/**
 * Sets up the Templates box on the category editor screen
 *
 * @since 1.4
 **/
class ShoppAdminCategoryTemplatesBox extends ShoppAdminMetabox {

	protected $id = 'category-templates';
	protected $view = 'categories/templates.php';

	protected function title () {
		return Shopp::__('Product Templates &amp; Menus');
	}

	public function box () {
		$options = array(
			'disabled' => Shopp::__('Price ranges disabled'),
			'auto'     => Shopp::__('Build price ranges automatically'),
			'custom'   => Shopp::__('Use custom price ranges'),
		);

		$this->references['pricemenu'] = menuoptions($options, $this->references['Category']->pricerange, true);
		parent::box();
	}

}
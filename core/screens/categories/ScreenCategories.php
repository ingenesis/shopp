<?php
/**
 * ScreenCategories.php
 *
 * Screen controller to display the list of categories.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

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

}
<?php
/**
 * AdminProducts.php
 *
 * Products admin request router
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminProducts extends ShoppAdminPostController {

	protected $ui = 'products';

    /**
     * Handles the admin page request
     *
     * @since 1.5
     *
     * @return string ShoppScreenController The screen controller class name to handle the request
     **/
	protected function route() {
		// @todo implement post type editor
		// if ( ! empty($this->request('post')) && ShoppProduct::posttype() == $this->request('post_type') && 'edit' == $this->request('action') )
		// 	return 'ShoppScreenProductEditor';

		$this->workflow();

		if ( $this->request('id') )
			return 'ShoppScreenProductEditor';
		else return 'ShoppScreenProducts';
	}

	/**
	 * Handles loading, saving and deleting products in the context of workflows
	 *
	 * @since 1.0
	 * @version 1.5
	 *
	 * @return void
	 **/
	public function workflow() {
		$id = $this->form('id');
		$Product = new ShoppProduct($id);
		$Product->load_data();
		ShoppProduct($Product);

		if ( $this->form('save') ) // Save updates form the editor
			$this->save($Product);

		$settings = $this->form('settings');
		$workflow = isset($settings['workflow']) ? $settings['workflow'] : false;
		$redirect = false;

		if ( ! $workflow ) return;

		$worklist = (array)$this->worklist();
		$working = array_search($id, $worklist);
		$next = 'close';

		switch( $workflow ) {
			case 'next':
				$next = isset($worklist[ ++$working ]) ? $worklist[ $working ] : 'close';
				break;
			case 'previous':
				$next = isset($worklist[ --$working ]) ? $worklist[ $working ] : 'close';
				break;
			case 'continue':
				$next = $Product->id;
				break;
			case 'new':
			case 'close':
			default:
				$next = $workflow;
		}

		if ( 'close' == $next )
			$redirect = $this->url(array('action' => null, 'id' => null, '_wpnonce' => null));
		else $redirect = $this->url(array('id' => $next));

		if ( $redirect )
			Shopp::redirect($redirect, true);

	}

	/**
	 * Loads products for this screen view
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function worklist() {
		if ( ! current_user_can('shopp_products') ) return;

		$View = self::view();

		$Products = new ProductCollection();
		$Products->load( $View->loading() );

		return array_keys($Products->worklist());
	}

	/**
	 * Get the DB query for the requested view parameters
	 *
	 * Admin product views manage all of the requests
	 *
	 * @since 1.5
	 *
	 * @return ShoppAdminProductsView The view for the ShoppAdmin request
	 **/
	public static function view() {
		$ShoppAdmin = ShoppAdmin();

		$View = new ShoppAdminProductsView($ShoppAdmin->request('view'));

		if ( $query = $ShoppAdmin->request('s') )
			$View->search($query);

		if ( $category_id = $ShoppAdmin->request('cat') )
			$View->category($category_id);

		// Detect custom taxonomies
		$taxonomies = array_intersect(get_object_taxonomies(ShoppProduct::$posttype), array_keys($_GET));
		if ( $taxonomies )
			$View->taxonomies($taxonomies);

		if ( $stocklevel = $ShoppAdmin->request('sl') )
			$View->stocklevel($stocklevel);

		$View->orderby($ShoppAdmin->request('orderby'), $ShoppAdmin->request('order'));

		return $View;
	}

	/**
	 * Builds a list of category IDs based on the current request
	 *
	 * This is used for workflow next/previous handling.
	 *
	 * @since 1.5
	 * @return void
	 */
	// public function worklist_old () {
	// 	return array();
	//
	// 	$defaults = array(
	// 		'per_page' => 20,
	// 		's' => '',
	// 	);
	// 	$args = array_merge($defaults, $this->request());
	//
	// 	$per_page_option = get_current_screen()->get_option( 'per_page' );
	// 	if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) ) $args['per_page'] = $user_per_page;
	// 	extract($args, EXTR_SKIP);
	//
	// 	if ( 'arrange' == $this->request('a') )  {
	// 		$this->init_positions();
	// 		$per_page = 300;
	// 	}
	//
	// 	$paged = absint( $this->request('paged') );
	//
	// 	$taxonomy = 'shopp_category';
	//
	// 	$filters = array('hide_empty' => 0, 'fields' => 'id=>parent');
	// 	add_filter('get_shopp_category', array(__CLASS__, 'termcategory'), 10, 2);
	//
	// 	$search = $this->request('s');
	// 	if ( ! empty($search))
	// 		$filters['search'] = $search;
	//
	// 	$Categories = array(); $count = 0;
	// 	$terms = get_terms( $taxonomy, $filters );
	//
	// 	if ( empty($search) ) {
	// 		$children = _get_term_hierarchy($taxonomy);
	// 		ProductCategory::tree($taxonomy, $terms, $children, $count, $Categories, $paged, $per_page);
	// 		$this->categories = $Categories;
	// 	} else {
	// 		$term_ids = array_keys($terms);
	// 		foreach ( $term_ids as $id )
	// 			$Categories[$id] = get_term($id, $taxonomy);
	// 	}
	//
	// 	$ids = array_keys($Categories);
	// 	return $ids;
	// }

	/**
	 * Handles saving updates from the product editor
	 *
	 * Saves all product related information which includes core product data
	 * and supporting elements such as images, digital downloads, tags,
	 * assigned categories, specs and pricing variations.
	 *
	 * @since 1.0
	 * @version 1.5
	 *
	 * @param ShoppProduct $Product
	 * @return void
	 **/
	public function save( ShoppProduct $Product ) {
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
		$Update->trimprices(); // Must occur after sumup()

		$Update->images();
		$Update->taxonomies();
		$Update->specs();
		$Update->meta();

		// Reload product to refresh all of the saved data
		// so everything is fresh for shopp_product_saved
		$Product = new ShoppProduct($Product->id);
		$Product->load_data();

		do_action_ref_array('shopp_product_saved', array($Product));
	}

	/**
	 * Generates the full URL for the current admin screen
	 *
	 * @since 1.3
	 * @param array $params (optional) The parameters to include in the URL
	 * @return string The generated URL with parameters
	 **/
	private function url( $params = array() ) {var_dump($this->pagename);
		$params['_wpnonce'] = $params['_wp_http_referer'] = false;
		$params = array_merge($this->request(), $params, array('page' => $this->pagename));
		return add_query_arg($params, admin_url('admin.php'));
	}

}
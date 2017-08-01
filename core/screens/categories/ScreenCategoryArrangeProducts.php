<?php
/**
 * ScreenCategoryArrangeProducts.php
 *
 * Screen controller to allow arrangement of products for a specific category.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

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

}
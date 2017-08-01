<?php
/**
 * ScreenDiscounts.php
 *
 * Screen controller to display the list of discounts.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Discounts
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenDiscounts extends ShoppScreenController {

	public function layout () {
		register_column_headers($this->id, array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name','Shopp'),
			'discount' => __('Discount','Shopp'),
			'applied' => __('Type','Shopp'),
			'eff' => __('Status','Shopp'))
		);
	}

	public function screen () {
		if ( ! current_user_can('shopp_promotions') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$table = ShoppDatabaseObject::tablename(ShoppPromo::$table);

		$defaults = array(
			'page' => false,
			'status' => false,
			'type' => false,
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			);

		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);

		$url = add_query_arg(array_merge($_GET, array('page'=>$this->page)), admin_url('admin.php'));
		$f = array('action','selected','s');
		$url = remove_query_arg($f, $url);

		$pagenum = absint( $paged );
		$start = ($per_page * ($pagenum-1));

		$where = array();
		if ( ! empty($s) ) $where[] = "name LIKE '%$s%'";
		if ( $status ) {
			$datesql = ShoppPromo::activedates();
			switch (strtolower($status)) {
				case 'active': $where[] = "status='enabled' AND $datesql"; break;
				case 'inactive': $where[] = "status='enabled' AND NOT $datesql"; break;
				case 'enabled': $where[] = "status='enabled'"; break;
				case 'disabled': $where[] = "status='disabled'"; break;
			}
		}
		if ( $type ) {
			switch (strtolower($type)) {
				case 'catalog': $where[] = "target='Catalog'"; break;
				case 'cart': $where[] = "target='Cart'"; break;
				case 'cartitem': $where[] = "target='Cart Item'"; break;
			}
		}

		$select = sDB::select(array(
			'table' => $table,
			'columns' => 'SQL_CALC_FOUND_ROWS *',
			'where' => $where,
			'orderby' => 'created DESC',
			'limit' => "$start,$per_page"
		));

		$Promotions = sDB::query($select,'array');
		$count = sDB::found();

		$num_pages = ceil($count / $per_page);
		$ListTable = ShoppUI::table_set_pagination($this->id, $count, $num_pages, $per_page );

		$states = array(
			'active' => __('Active','Shopp'),
			'inactive' => __('Not Active','Shopp'),
			'enabled' => __('Enabled','Shopp'),
			'disabled' => __('Disabled','Shopp')
		);

		$types = array(
			'catalog' => __('Catalog Discounts','Shopp'),
			'cart' => __('Cart Discounts','Shopp'),
			'cartitem' => __('Cart Item Discounts','Shopp')
		);

		$num_pages = ceil($count / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));

		include $this->ui('discounts.php');
	}

}
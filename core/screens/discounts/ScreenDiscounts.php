<?php
/**
 * ScreenDiscounts.php
 *
 * Screen controller to display the list of discounts.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Discounts
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenDiscounts extends ShoppScreenController {

	const DEFAULT_PER_PAGE = 20;

	public function layout () {
		register_column_headers($this->id, array(
			'cb' => '<input type="checkbox" />',
			'name' => Shopp::__('Name'),
			'discount' => Shopp::__('Discount'),
			'applied' => Shopp::__('Type'),
			'eff' => Shopp::__('Status'))
		);

		add_screen_option( 'per_page', array(
			'default' => self::DEFAULT_PER_PAGE,
			'option' => 'shopp_' . $this->slug() . '_per_page'
		));
	}


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
		$actions = array('enable', 'disable', 'delete');

		$request = $this->request('action');
		$selected = (array)$this->request('selected');
		$selected = array_map('intval', $selected);

		if ( ! in_array($request, $actions) ) return;
		elseif ( empty($selected) ) return;

		switch ( $request ) {
			case 'enable':
				ShoppPromo::enableset($selected);
				break;
			case 'disable':
				ShoppPromo::disableset($selected);
				break;
			case 'delete':
				ShoppPromo::deleteset($selected);
				break;
		}

		Shopp::redirect( $this->url(array('action' => null, 'selected' => null)) );
	}

	public function duplicate () {
		$request = $this->request('action');
		if ( 'duplicate' !== $request) return;

		$selected = intval($this->request('selected'));
		if ( empty($selected) ) return;

		$Promo = new ShoppPromo($selected);
		$Promo->duplicate();

		Shopp::redirect( $this->url(array('action' => null, 'selected' => null)) );
	}


	public function screen () {
		if ( ! current_user_can('shopp_promotions') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$table = ShoppDatabaseObject::tablename(ShoppPromo::$table);

		$defaults = array(
			'status' => false,
			'type' => false,
			'paged' => 1,
			's' => '',
		);

		$args = array_merge($defaults, $this->request());
		extract($args, EXTR_SKIP);

		// Get user defined pagination preferences
		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$per_page = self::DEFAULT_PER_PAGE;

		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) )
			$per_page = $user_per_page;

		$pagenum = absint( $paged );
		$start = ($per_page * ($pagenum-1));

		$where = array();
		if ( ! empty($s) ) $where[] = "name LIKE '%$s%'";
		if ( $status ) {
			$datesql = ShoppPromo::activedates();
			switch ( strtolower($status) ) {
				case 'active':   $where[] = "status='enabled' AND $datesql"; break;
				case 'inactive': $where[] = "status='enabled' AND NOT $datesql"; break;
				case 'enabled':  $where[] = "status='enabled'"; break;
				case 'disabled': $where[] = "status='disabled'"; break;
			}
		}
		if ( $type ) {
			switch ( strtolower($type) ) {
				case 'catalog':  $where[] = "target='Catalog'"; break;
				case 'cart':     $where[] = "target='Cart'"; break;
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
		$ListTable = ShoppUI::table_set_pagination($this->id, $count, $num_pages, $per_page);

		$actions_menu = array(
			'enable'   => Shopp::__('Enable'),
			'disable'  => Shopp::__('Disable'),
			'delete'   => Shopp::__('Delete')
		);

		$states = array(
			'active'   => Shopp::__('Active'),
			'inactive' => Shopp::__('Not Active'),
			'enabled'  => Shopp::__('Enabled'),
			'disabled' => Shopp::__('Disabled')
		);

		$types = array(
			'catalog'  => Shopp::__('Catalog Discounts'),
			'cart'     => Shopp::__('Cart Discounts'),
			'cartitem' => Shopp::__('Cart Item Discounts')
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
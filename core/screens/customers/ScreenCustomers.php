<?php
/**
 * ScreenCustomers.php
 *
 * Screen controller for the customer list screen.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenCustomers extends ShoppScreenController {

	const DEFAULT_PER_PAGE = 20;

	public function assets () {
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('daterange');

		do_action('shopp_customer_admin_scripts');
	}

	/**
	 * Registers the column headers for the customer list screen
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function layout () {
		register_column_headers($this->id, array(
			'cb'                => '<input type="checkbox" />',
			'customer-name'     => Shopp::__('Name'),
			'customer-login'    => Shopp::__('Login'),
			'email'             => Shopp::__('Email'),
			'customer-location' => Shopp::__('Location'),
			'customer-orders'   => Shopp::__('Orders'),
			'customer-joined'   => Shopp::__('Joined')
		));

		add_screen_option( 'per_page', array(
			'default' => self::DEFAULT_PER_PAGE,
			'option' => 'shopp_' . $this->slug() . '_per_page'
		));
	}

	public function actions () {
		return array(
			'delete'
		);
	}

	public function delete () {
		$request = $this->request('deleting');
		if ( 'customer' !== $request) return;

		$selected = (array)$this->request('selected');
		if ( empty($selected) ) return;

		foreach ( $selected as $id ) {
			$Customer = new ShoppCustomer($id);
			$Billing = new BillingAddress($Customer->id, 'customer');
			$Billing->delete();
			$Shipping = new ShippingAddress($Customer->id, 'customer');
			$Shipping->delete();
			$Customer->delete();
		}

		Shopp::redirect( $this->url(array('deleting' => null, 'selected' => null)) );
	}

	public function screen () {

		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$per_page = self::DEFAULT_PER_PAGE;
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) )
			$per_page = $user_per_page;

		$select = $this->sql($per_page);
		$query = sDB::select($select);
		$Customers = sDB::query($query, 'array', 'index', 'id');
		$total = sDB::found();

		$this->addorders($Customers);

		$num_pages = ceil($total / $per_page);
		$ListTable = ShoppUI::table_set_pagination(ShoppAdmin::screen(), $total, $num_pages, $per_page);

		$ranges = ShoppAdminLookup::daterange_options();
		$ranges['all'] = Shopp::__('Show All Customers');

		$exports = array(
			'tab' => Shopp::__('Tab-separated.txt'),
			'csv' => Shopp::__('Comma-separated.csv'),
		);

		$formatPref = shopp_setting('customerexport_format');
		if ( ! $formatPref )
			$formatPref = 'tab';

		$columns = array_merge(Customer::exportcolumns(), BillingAddress::exportcolumns(), ShippingAddress::exportcolumns());
		$selected = shopp_setting('customerexport_columns');
		if ( empty($selected) )
			$selected = array_keys($columns);

		$authentication = shopp_setting('account_system');

		$action = add_query_arg( array('page'=> ShoppAdmin::pagename('customers') ), admin_url('admin.php'));

		isset($ListTable, $action, $exports, $authentication);

		include $this->ui('customers.php');
	}

	private function sql($per_page) {
		global $wpdb;
		$customer_table = ShoppDatabaseObject::tablename(Customer::$table);
		$billing_table = ShoppDatabaseObject::tablename(BillingAddress::$table);
		$users_table = $wpdb->users;

		$where = array();
		if ( $this->request('s') )
			$where = array_merge($where, $this->search());

		if ( $this->request('start') ) {
			list($startmonth, $startday, $startyear) = explode('/', $this->request('start'));
			$starts = mktime(0, 0, 0, $startmonth, $startday, $startyear);
		}

		if ( $this->request('end') ) {
			list($endmonth, $endday, $endyear) = explode('/', $this->request('end'));
			$ends = mktime(23, 59, 59, $endmonth, $endday, $endyear);
		}

		if ( ! empty($starts) && ! empty($ends) )
			$where[] = " (UNIX_TIMESTAMP(c.created) >= $starts AND UNIX_TIMESTAMP(c.created) <= $ends)";

		$page = max(1, absint( $this->request('paged') ));
		$index = $per_page * ( $page - 1 );

		return array(
			'columns' => 'SQL_CALC_FOUND_ROWS c.*,city,state,country,user_login',
			'table' => "$customer_table as c",
			'joins' => array(
					$billing_table => "LEFT JOIN $billing_table AS b ON b.customer=c.id AND b.type='billing'",
					$users_table => "LEFT JOIN $users_table AS u ON u.ID=c.wpuser AND (c.wpuser IS NULL OR c.wpuser != 0)"
				),
			'where' => $where,
			'groupby' => "c.id",
			'orderby' => "c.created DESC",
			'limit' => "$index,$per_page"
		);
	}

	private function addorders( $Customers ) {
		$purchase_table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		// Add order data to customer records in this view
		$orders = sDB::query("SELECT customer,SUM(total) AS total,count(id) AS orders
								FROM $purchase_table
							   WHERE customer IN (" . join(',', array_keys($Customers)) . ") GROUP BY customer",
							   'array', 'index', 'customer');

		foreach ( $Customers as &$record ) {
			$record->total = 0; $record->orders = 0;
			if ( ! isset($orders[ $record->id ]) )
				continue;
			$record->total = $orders[ $record->id ]->total;
			$record->orders = $orders[ $record->id ]->orders;
		}
	}

	private function search() {
		if ( empty($this->request('s')) )
			return array();

		$string = stripslashes($this->request('s'));

		$props = array(
			'company'  => "c.company LIKE '%%%s%%'",
			'login'    => "u.user_login LIKE '%%%s%%'",
			'address'  => "(b.address LIKE '%%%s%%' OR b.xaddress='%%%s%%')",
			'city'     => "b.city LIKE '%%%s%%'",
			'province' => "b.state='%s'",
			'state'    => "b.state='%s'",
			'zip'      => "b.postcode='%s'",
			'zipcode'  => "b.postcode='%s'",
			'postcode' => "b.postcode='%s'",
			'country'  => "b.country='%s'"
		);

		if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $string, $matches, PREG_SET_ORDER) ) {
			foreach ( $matches as $tokens ) {
				$keyword = ! empty($tokens[2]) ? $tokens[2] : $tokens[3];
				$prop = strtolower($tokens[1]);
				if ( isset($props[ $prop ]) )
					$search[] = sprintf($props[ $prop ], $keyword);
			}
		} elseif ( false !== strpos($string, '@') ) {
			 $search[] = "c.email='$string'";
		} elseif ( is_numeric($string) ) {
			$search[] = "c.id='$string'";
		} else $search[] = "(CONCAT(c.firstname,' ',c.lastname) LIKE '%$string%' OR c.company LIKE '%$string%')";

		return $search;
	}

}
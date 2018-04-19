<?php
/**
 * ScreenCustomers.php
 *
 * Screen controller for the customer list screen.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     1.4
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
		global $wpdb;

		$defaults = array(
			'page' => false,
			'update' => false,
			'newstatus' => false,
			'pagenum' => 1,
			'paged' => 1,
			'per_page' => 20,
			'start' => '',
			'end' => '',
			'status' => false,
			's' => '',
			'range' => '',
			'startdate' => '',
			'enddate' => '',
		);

		$args = array_merge($defaults, $this->request());
		extract($args, EXTR_SKIP);

		$updated = false;

		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$per_page = self::DEFAULT_PER_PAGE;
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) )
			$per_page = $user_per_page;

		$pagenum = min(1, absint( $paged ));
		$index = $per_page * ( $pagenum - 1 );

		if (!empty($start)) {
			$startdate = $start;
			list($month, $day, $year) = explode('/', $startdate);
			$starts = mktime(0, 0, 0, $month, $day, $year);
		}
		if (!empty($end)) {
			$enddate = $end;
			list($month,$day,$year) = explode("/",$enddate);
			$ends = mktime(23,59,59,$month,$day,$year);
		}

		$customer_table = ShoppDatabaseObject::tablename(Customer::$table);
		$billing_table = ShoppDatabaseObject::tablename(BillingAddress::$table);
		$purchase_table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$users_table = $wpdb->users;

		$where = array();
		if ( ! empty($s) ) {
			$s = stripslashes($s);
			if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $s, $props, PREG_SET_ORDER) ) {
				foreach ( $props as $search ) {
					$keyword = ! empty($search[2]) ? $search[2] : $search[3];
					switch(strtolower( $search[1]) ) {
						case "company":  $where[] = "c.company LIKE '%$keyword%'"; break;
						case "login":    $where[] = "u.user_login LIKE '%$keyword%'"; break;
						case "address":  $where[] = "(b.address LIKE '%$keyword%' OR b.xaddress='%$keyword%')"; break;
						case "city":     $where[] = "b.city LIKE '%$keyword%'"; break;
						case "province":
						case "state":    $where[] = "b.state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode": $where[] = "b.postcode='$keyword'"; break;
						case "country":  $where[] = "b.country='$keyword'"; break;
					}
				}
			} elseif ( false !== strpos($s, '@') ) {
				 $where[] = "c.email='$s'";
			} elseif ( is_numeric($s) ) {
				$where[] = "c.id='$s'";
			} else $where[] = "(CONCAT(c.firstname,' ',c.lastname) LIKE '%$s%' OR c.company LIKE '%$s%')";

		}

		if ( ! empty($starts) && ! empty($ends) )
			$where[] = " (UNIX_TIMESTAMP(c.created) >= $starts AND UNIX_TIMESTAMP(c.created) <= $ends)";

		$select = array(
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
		$query = sDB::select($select);
		$Customers = sDB::query($query, 'array', 'index', 'id');

		$total = sDB::found();

		// Add order data to customer records in this view
		$orders = sDB::query("SELECT customer,SUM(total) AS total,count(id) AS orders FROM $purchase_table WHERE customer IN (" . join(',', array_keys($Customers)) . ") GROUP BY customer", 'array', 'index', 'customer');
		foreach ( $Customers as &$record ) {
			$record->total = 0; $record->orders = 0;
			if ( ! isset($orders[ $record->id ]) )
				continue;
			$record->total = $orders[ $record->id ]->total;
			$record->orders = $orders[ $record->id ]->orders;
		}

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

		include $this->ui('customers.php');
	}
}
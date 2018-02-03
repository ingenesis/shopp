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
	}

	public function screen () {
		global $wpdb;

		$defaults = array(
			'page' => false,
			'deleting' => false,
			'selected' => false,
			'update' => false,
			'newstatus' => false,
			'pagenum' => 1,
			'paged' => false,
			'per_page' => 20,
			'start' => '',
			'end' => '',
			'status' => false,
			's' => '',
			'range' => '',
			'startdate' => '',
			'enddate' => '',
		);

		$args = array_merge($defaults,$_GET);
		extract($args, EXTR_SKIP);

		if ( $page == ShoppAdmin::pagename('customers')
				&& ! empty($deleting)
				&& ! empty($selected)
				&& is_array($selected)
				&& current_user_can('shopp_delete_customers')) {
			foreach ( $selected as $deletion ) {
				$Customer = new ShoppCustomer($deletion);
				$Billing = new BillingAddress($Customer->id, 'customer');
				$Billing->delete();
				$Shipping = new ShippingAddress($Customer->id, 'customer');
				$Shipping->delete();
				$Customer->delete();
			}
		}

		$updated = false;
		// if (!empty($_POST['save'])) {
		// 	check_admin_referer('shopp-save-customer');
		// 	$wp_integration = ('wordpress' === shopp_setting( 'account_system' ));
		//
		// 	if ($_POST['id'] !== 'new') {
		// 		$Customer = new ShoppCustomer($_POST['id']);
		// 		$Billing = new BillingAddress($Customer->id, 'customer');
		// 		$Shipping = new ShippingAddress($Customer->id, 'customer');
		// 	} else $Customer = new ShoppCustomer();
		//
		// 	if (!empty($Customer->wpuser)) $user = get_user_by('id',$Customer->wpuser);
		// 	$new_customer = empty( $Customer->id );
		//
		// 	$Customer->updates($_POST);
		//
		// 	// Reassign WordPress login
		// 	if ($wp_integration && isset($_POST['userlogin']) && $_POST['userlogin'] !=  $user->user_login) {
		// 		$newlogin = get_user_by('login', $_POST['userlogin']);
		// 		if ( ! empty($newlogin->ID) ) {
		// 			if (sDB::query("SELECT count(*) AS used FROM $Customer->_table WHERE wpuser=$newlogin->ID",'auto','col','used') == 0) {
		// 				$Customer->wpuser = $newlogin->ID;
		// 				$updated = sprintf(__('Updated customer login to %s.','Shopp'),"<strong>$newlogin->user_login</strong>");
		// 			} else $updated = sprintf(__('Could not update customer login to &quot;%s&quot; because that user is already assigned to another customer.','Shopp'),'<strong>'.sanitize_user($_POST['userlogin']).'</strong>');
		//
		// 		} else $updated = sprintf(__('Could not update customer login to &quot;%s&quot; because the user does not exist in WordPress.','Shopp'),'<strong>'.sanitize_user($_POST['userlogin']).'</strong>');
		// 		if ( empty($_POST['userlogin']) ) $Customer->wpuser = 0;
		// 	}
		//
		// 	if ( ! empty($_POST['new-password']) && !empty($_POST['confirm-password'])
		// 		&& $_POST['new-password'] == $_POST['confirm-password']) {
		// 			$Customer->password = wp_hash_password($_POST['new-password']);
		// 			if (!empty($Customer->wpuser)) wp_set_password($_POST['new-password'], $Customer->wpuser);
		// 		}
		//
		// 	$valid_email = filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL );
		// 	$password = !empty( $_POST['new_password'] );
		//
		// 	if ($wp_integration && $new_customer && $valid_email && $password) {
		// 		$Customer->loginname = $_POST['userlogin'];
		// 		$Customer->email = $_POST['email'];
		// 		$Customer->firstname = $_POST['firstname'];
		// 		$Customer->lastname = $_POST['lastname'];
		//
		// 		$return = $Customer->create_wpuser();
		//
		// 		if ( $return ) {
		// 			$updated = sprintf( __( 'The Shopp and WordPress accounts have been created with the username &quot;%s&quot;.', 'Shopp'), '<strong>'.sanitize_user($_POST['userlogin']).'</strong>');
		// 		} else {
		// 			$updated = sprintf( __( 'Could not create a WordPress account for customer &quot;%s&quot;.','Shopp'), '<strong>'.sanitize_user($_POST['userlogin']).'</strong>');
		// 		}
		// 	}
		// 	elseif ($new_customer && ( !$valid_email || !$password ) ) {
		// 		$updated = __( 'Could not create new user. You must enter a valid email address and a password first.', 'Shopp' );
		// 		$no_save = true;
		// 	}
		//
		// 	if ( !isset( $new_save ) ) {
		// 		$Customer->info = false; // No longer used from DB
		// 		$Customer->save();
		// 	}
		//
		//
		// 	if (isset($_POST['info']) && !empty($_POST['info'])) {
		// 		foreach ((array)$_POST['info'] as $id => $info) {
		// 			$Meta = new ShoppMetaObject($id);
		// 			$Meta->value = $info;
		// 			$Meta->save();
		// 		}
		// 	}
		//
		// 	if (isset($Customer->id)) $Billing->customer = $Customer->id;
		// 	$Billing->updates($_POST['billing']);
		// 	$Billing->save();
		//
		// 	if (isset($Customer->id)) $Shipping->customer = $Customer->id;
		// 	$Shipping->updates($_POST['shipping']);
		// 	$Shipping->save();
		// 	if (!$updated) __('Customer updated.','Shopp');
		// 	$Customer = false;
		//
		// }

		$pagenum = absint( $paged );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$index = ($per_page * ($pagenum-1));

		if (!empty($start)) {
			$startdate = $start;
			list($month,$day,$year) = explode("/",$startdate);
			$starts = mktime(0,0,0,$month,$day,$year);
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
		if (!empty($s)) {
			$s = stripslashes($s);
			if (preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/',$s,$props,PREG_SET_ORDER)) {
				foreach ($props as $search) {
					$keyword = !empty($search[2])?$search[2]:$search[3];
					switch(strtolower($search[1])) {
						case "company": $where[] = "c.company LIKE '%$keyword%'"; break;
						case "login": $where[] = "u.user_login LIKE '%$keyword%'"; break;
						case "address": $where[] = "(b.address LIKE '%$keyword%' OR b.xaddress='%$keyword%')"; break;
						case "city": $where[] = "b.city LIKE '%$keyword%'"; break;
						case "province":
						case "state": $where[] = "b.state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode": $where[] = "b.postcode='$keyword'"; break;
						case "country": $where[] = "b.country='$keyword'"; break;
					}
				}
			} elseif (strpos($s,'@') !== false) {
				 $where[] = "c.email='$s'";
			} elseif (is_numeric($s)) {
				$where[] = "c.id='$s'";
			} else $where[] = "(CONCAT(c.firstname,' ',c.lastname) LIKE '%$s%' OR c.company LIKE '%$s%')";

		}
		if (!empty($starts) && !empty($ends)) $where[] = ' (UNIX_TIMESTAMP(c.created) >= '.$starts.' AND UNIX_TIMESTAMP(c.created) <= '.$ends.')';

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
		$Customers = sDB::query($query,'array','index','id');

		$total = sDB::found();

		// Add order data to customer records in this view
		$orders = sDB::query("SELECT customer,SUM(total) AS total,count(id) AS orders FROM $purchase_table WHERE customer IN (".join(',',array_keys($Customers)).") GROUP BY customer",'array','index','customer');
		foreach ($Customers as &$record) {
			$record->total = 0; $record->orders = 0;
			if ( ! isset($orders[$record->id]) ) continue;
			$record->total = $orders[$record->id]->total;
			$record->orders = $orders[$record->id]->orders;
		}

		$num_pages = ceil($total / $per_page);
		$ListTable = ShoppUI::table_set_pagination(ShoppAdmin::screen(), $total, $num_pages, $per_page );

		$ranges = array(
			'all' => __('Show New Customers','Shopp'),
			'today' => __('Today','Shopp'),
			'week' => __('This Week','Shopp'),
			'month' => __('This Month','Shopp'),
			'quarter' => __('This Quarter','Shopp'),
			'year' => __('This Year','Shopp'),
			'yesterday' => __('Yesterday','Shopp'),
			'lastweek' => __('Last Week','Shopp'),
			'last30' => __('Last 30 Days','Shopp'),
			'last90' => __('Last 3 Months','Shopp'),
			'lastmonth' => __('Last Month','Shopp'),
			'lastquarter' => __('Last Quarter','Shopp'),
			'lastyear' => __('Last Year','Shopp'),
			'lastexport' => __('Last Export','Shopp'),
			'custom' => __('Custom Dates','Shopp')
			);

		$exports = array(
			'tab' => __('Tab-separated.txt','Shopp'),
			'csv' => __('Comma-separated.csv','Shopp'),			
			);


		$formatPref = shopp_setting('customerexport_format');
		if (!$formatPref) $formatPref = 'tab';

		$columns = array_merge(Customer::exportcolumns(),BillingAddress::exportcolumns(),ShippingAddress::exportcolumns());
		$selected = shopp_setting('customerexport_columns');
		if (empty($selected)) $selected = array_keys($columns);

		$authentication = shopp_setting('account_system');

		$action = add_query_arg( array('page'=> ShoppAdmin::pagename('customers') ), admin_url('admin.php'));

		include $this->ui('customers.php');
	}
}
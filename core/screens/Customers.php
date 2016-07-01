<?php
/**
 * Account.php
 *
 * Flow controller for the customer management interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January  6, 2010
 * @package shopp
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomers extends ShoppAdminController {

	protected $ui = 'customers';

	protected function route () {
		if ( $this->request('id') )
			return 'ShoppScreenCustomerEditor';
		else return 'ShoppScreenCustomers';
	}

}

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


class ShoppScreenCustomerEditor extends ShoppScreenController {

	protected $nonce = 'shopp-save-customer';

	protected $defaults = array(
		'page' => '',
		'id' => 'new'
	);

	public function load () {
		$id = (int) $this->request('id');
		if ( empty($id) ) return;

		if ( $this->request('new') ) return new ShoppCustomer();

		$Customer = new ShoppCustomer($id);
		if ( ! $Customer->exists() )
			wp_die(Shopp::__('The requested customer record does not exist.'));

		$Customer->Billing = new BillingAddress($Customer->id, 'customer');
		$Customer->Shipping = new ShippingAddress($Customer->id, 'customer');

		return $Customer;
	}

	public function ops () {

		add_action('shopp_admin_customers_ops', array($this, 'updates') );
		add_action('shopp_admin_customers_ops', array($this, 'password') );
		add_action('shopp_admin_customers_ops', array($this, 'userlogin') );
		add_action('shopp_admin_customers_ops', array($this, 'billaddress') );
		add_action('shopp_admin_customers_ops', array($this, 'shipaddress') );
		add_action('shopp_admin_customers_ops', array($this, 'info') );

		return;

		// $valid_email = filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL );
		// $password = !empty( $_POST['new_password'] );
		//
		// if ($wp_integration && $new_customer && $valid_email && $password) {
		// 	$Customer->loginname = $_POST['userlogin'];
		// 	$Customer->email = $_POST['email'];
		// 	$Customer->firstname = $_POST['firstname'];
		// 	$Customer->lastname = $_POST['lastname'];
		//
		// 	$return = $Customer->create_wpuser();
		//
		// 	if ( $return ) {
		// 		$updated = sprintf( __( 'The Shopp and WordPress accounts have been created with the username &quot;%s&quot;.', 'Shopp'), '<strong>'.sanitize_user($_POST['userlogin']).'</strong>');
		// 	} else {
		// 		$updated = sprintf( __( 'Could not create a WordPress account for customer &quot;%s&quot;.','Shopp'), '<strong>'.sanitize_user($_POST['userlogin']).'</strong>');
		// 	}
		// }
		// elseif ($new_customer && ( !$valid_email || !$password ) ) {
		// 	$updated = __( 'Could not create new user. You must enter a valid email address and a password first.', 'Shopp' );
		// 	$no_save = true;
		// }
		//
		// if ( !isset( $new_save ) ) {
		// 	$Customer->info = false; // No longer used from DB
		// 	$Customer->save();
		// }
		//
		//
		// if (isset($_POST['info']) && !empty($_POST['info'])) {
		// 	foreach ((array)$_POST['info'] as $id => $info) {
		// 		$Meta = new ShoppMetaObject($id);
		// 		$Meta->value = $info;
		// 		$Meta->save();
		// 	}
		// }
		//
		// if (isset($Customer->id)) $Billing->customer = $Customer->id;
		// $Billing->updates($_POST['billing']);
		// $Billing->save();
		//
		// if (isset($Customer->id)) $Shipping->customer = $Customer->id;
		// $Shipping->updates($_POST['shipping']);
		// $Shipping->save();
		// if ( ! $updated ) __('Customer updated.','Shopp');
		// $Customer = false;

	}

	public function userlogin ( ShoppCustomer $Customer ) {

		if ( 'wordpress' !== shopp_setting('account_system') || false === $this->form('userlogin') ) return $Customer;
		$userlogin = $this->form('userlogin');

		if ( 0 != $Customer->wpuser && empty($userlogin) ) { // Unassign the WP User login
			$Customer->wpuser = 0;
			$this->notice(Shopp::__('Unassigned customer login.'));
			return $Customer;
		} elseif ( empty($userlogin) ) return $Customer;

		// Get WP User by the given login name
		$newuser = get_user_by('login', $userlogin);
		$login = '<strong>' . sanitize_user($userlogin).'</strong>';

		if ( empty($newuser->ID) )
			return $this->notice(Shopp::__('Could not update customer login to &quot;%s&quot; because the user does not exist in WordPress.', $login), 'error');

		if ( $newuser->ID == $Customer->wpuser ) return $Customer;

		if ( 0 == sDB::query("SELECT count(*) AS used FROM $Customer->_table WHERE wpuser=$newuser->ID", 'auto', 'col', 'used') ) {
			$Customer->wpuser = $newuser->ID;
			$this->notice(Shopp::__('Updated customer login to %s.', "<strong>$newuser->user_login</strong>"));
		} else $this->notice(Shopp::__('Could not update customer login to &quot;%s&quot; because that user is already assigned to another customer.', $login), 'error');

		return $Customer;
	}

	public function password ( ShoppCustomer $Customer ) {

		if ( false === $this->form('new-password') ) return $Customer;

		if ( false === $this->form('confirm-password') )
			return $this->notice(Shopp::__('You must provide a password for your account and confirm it for correct spelling.'), 'error');

		if ( $this->form('new-password') != $this->form('confirm-password') )
			return $this->notice(Shopp::__('The passwords you entered do not match. Please re-enter your passwords.'));

		$Customer->password = wp_hash_password($this->form('new-password'));
		if ( ! empty($Customer->wpuser) )
			wp_set_password($this->form('new-password'), $Customer->wpuser);

		$this->valid_password = true;

		return $Customer;

	}

	public function info ( ShoppCustomer $Customer ) {

		if ( false === $this->form('info') ) return $Customer;

		$info = $this->form('info');
		foreach ( (array)$field as $id => $value) {
			$Meta = new ShoppMetaObject($id);
			$Meta->value = $value;
			$Meta->save();
		}

		return $Customer;
	}

	public function billaddress ( ShoppCustomer $Customer ) {

		if ( false == $this->form('billing') ) return $Customer;

		$Billing = $Customer->Billing;

		if (isset($Customer->id)) $Billing->customer = $Customer->id;
		$Billing->updates($this->form('billing'));
		$Billing->save();

		return $Customer;
	}

	public function shipaddress ( ShoppCustomer $Customer ) {

		if ( false == $this->form('shipping') ) return $Customer;

		$Shipping = $Customer->Shipping;

		if (isset($Customer->id)) $Shipping->customer = $Customer->id;
		$Shipping->updates($this->form('shipping'));
		$Shipping->save();

		return $Customer;
	}

	public function updates ( ShoppCustomer $Customer ) {

		if ( ! filter_var( $this->form('email'), FILTER_VALIDATE_EMAIL ) ) {
			$this->notice(Shopp::__('%s is not a valid email address.', $this->form('email')), 'error');
			unset($this->form['email']);
		} else $this->valid_email = true;

		$checksum = md5(serialize($Customer));
		$Customer->updates($this->form());
		$Customer->info = false; // No longer used from DB
		if ( md5(serialize($Customer)) != $checksum )
			$this->notice(Shopp::__('Customer updated.', $this->form('email')));

		return $Customer;
	}

	public function save ( ShoppCustomer $Customer ) {

		if ( $this->request('new') ) {

			if ( ! isset($this->valid_email) )
				return $this->notice(Shopp::__('Could not create new customer. You must enter a valid email address.'));

			if ( ! isset($this->valid_password) )
				$this->password = wp_hash_password(wp_generate_password(12, true));

			if ( 'wordpress' !== shopp_setting('account_system') ) {
				$wpuser = $Customer->create_wpuser();
				$login = '<strong>' . sanitize_user($this->form('userlogin')) . '</strong>';

				if ( $wpuser )
					$this->notice(Shopp::__('A new customer has been created with the WordPress login &quot;%s&quot;.', $login), 'error');
				else $this->notice(Shopp::__('Could not create the WordPress login &quot;%s&quot; for the new customer.', $login), 'error');
			}

			$this->notice(Shopp::__('New customer created.'));
		}

		$Customer->save();

	}

	public function assets () {
		wp_enqueue_script('postbox');
		wp_enqueue_script('password-strength-meter');

		shopp_enqueue_script('suggest');
		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('selectize');
		shopp_enqueue_script('address');
		shopp_enqueue_script('customers');

		do_action('shopp_customer_editor_scripts');
	}

	/**
	 * Builds the interface layout for the customer editor
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function layout () {
		$Shopp = Shopp::object();
		$Admin = ShoppAdmin();

		$Customer = $this->Model;

		$default = array('' => '&nbsp;');
		$countries = array_merge($default, ShoppLookup::countries());
		$Customer->_countries = $countries;

		$states = ShoppLookup::country_zones(array($Customer->Billing->country,$Customer->Shipping->country));

		$Customer->_billing_states = array_merge($default, (array)$states[ $Customer->Billing->country ]);
		$Customer->_shipping_states = array_merge($default, (array)$states[ $Customer->Shipping->country ]);

		new ShoppAdminCustomerSaveBox($this->id, 'side', 'core', array('Customer' => $Customer));
		new ShoppAdminCustomerSettingsBox($this->id, 'side', 'core', array('Customer' => $Customer));
		new ShoppAdminCustomerLoginBox($this->id, 'side', 'core', array('Customer' => $Customer));

		new ShoppAdminCustomerContactBox($this->id, 'normal', 'core', array('Customer' => $Customer));

		if ( ! empty($Customer->info->meta) && is_array($Customer->info->meta) )
			new ShoppAdminCustomerInfoBox($this->id, 'normal', 'core', array('Customer' => $Customer));

		new ShoppAdminCustomerBillingAddressBox($this->id, 'normal', 'core', array('Customer' => $Customer));
		new ShoppAdminCustomerShippingAddressBox($this->id, 'normal', 'core', array('Customer' => $Customer));

	}

	/**
	 * Interface processor for the customer editor
	 *
	 * Handles rendering the interface, processing updated customer details
	 * and handing saving them back to the database
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function screen () {

		if ( ! current_user_can('shopp_customers') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$Customer = $this->load();


		if ( $Customer->exists() ) {
			$purchase_table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
			$r = sDB::query("SELECT count(id) AS purchases,SUM(total) AS total FROM $purchase_table WHERE customer='$Customer->id' LIMIT 1");

			$Customer->orders = $r->purchases;
			$Customer->total = $r->total;
		}

		$regions = ShoppLookup::country_zones();


		include $this->ui('editor.php');
	}

}

class ShoppAdminCustomerSaveBox extends ShoppAdminMetabox {

	protected $id = 'customer-save';
	protected $view = 'customers/save.php';

	protected function title () {
		return Shopp::__('Save');
	}

}

class ShoppAdminCustomerSettingsBox extends ShoppAdminMetabox {

	protected $id = 'customer-settings';
	protected $view = 'customers/settings.php';

	protected function title () {
		return Shopp::__('Settings');
	}

}

class ShoppAdminCustomerLoginBox extends ShoppAdminMetabox {

	protected $id = 'customer-login';
	protected $view = 'customers/login.php';

	protected function title () {
		return Shopp::__('Login &amp; Password');
	}

	public function box () {
		extract($this->references);

		$this->references['wp_user'] = get_userdata($Customer->wpuser);
		$this->references['avatar'] = get_avatar($Customer->wpuser, 48);
		$this->references['userlink'] = add_query_arg('user_id', $Customer->wpuser, admin_url('user-edit.php'));

		parent::box();
	}
}

class ShoppAdminCustomerContactBox extends ShoppAdminMetabox {

	protected $id = 'customer-contact';
	protected $view = 'customers/contact.php';

	protected function title () {
		return Shopp::__('Contact');
	}

}

class ShoppAdminCustomerInfoBox extends ShoppAdminMetabox {

	protected $id = 'customer-info';
	protected $view = 'customers/info.php';

	protected function title () {
		return Shopp::__('Details');
	}

}

class ShoppAdminCustomerShippingAddressBox extends ShoppAdminMetabox {

	protected $id = 'customer-shipping';
	protected $view = 'customers/shipping.php';

	protected function title () {
		return Shopp::__('Shipping Address');
	}

	public static function editor ( $Customer, $type = 'shipping' ) {
		ob_start();
		include SHOPP_ADMIN_PATH . '/customers/address.php';
		return ob_get_clean();
	}

}

class ShoppAdminCustomerBillingAddressBox extends ShoppAdminMetabox {

	protected $id = 'customer-billing';
	protected $view = 'customers/billing.php';

	protected function title () {
		return Shopp::__('Billing Address');
	}

	public static function editor ( $Customer, $type = 'billing' ) {
		shopp_custom_script('orders', 'var address = [];');
		ob_start();
		include SHOPP_ADMIN_PATH . '/customers/address.php';
		return ob_get_clean();
	}

}

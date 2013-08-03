<?php
/**
 * Customer.php
 *
 * Customer management classes
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, May 2013
 * @package shopp
 * @since 1.0
 * @subpackage customer
 **/

class Customer extends DatabaseObject {
	static $table = 'customer';

	public $login = false;			 // Login authenticated flag
	public $info = false;			 // Custom customer info fields
	public $newuser = false;		 // New WP user created flag
	public $loginname = false;		 // Account login name
	public $_password_change = null; // Password changed (true:successful false:failed null:unchanged)

	public function __construct ( $id = false, $key = 'id' ) {
		$this->init(self::$table);
		$this->load($id, $key);

		if ( ! empty($this->id) )
			$this->load_info();

		$this->listeners();
	}

	public function __wakeup () {
		$this->init(self::$table);
		$this->listeners();
	}

	public function reset () {
		$this->newuser = false;
	}

	public function listeners () {
		// add_action('parse_request',array($this,'menus'));
		// add_action('shopp_account_management',array($this,'management'));
		add_action('shopp_logged_out', array($this, 'logout'));
	}

	/**
	 * simplify - get a simplified version of the customer object
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return stdClass returns stdClass simplified version of the customer object
	 **/
	public function simplify () {
		$map = array('id', 'wpuser', 'firstname', 'lastname', 'email', 'phone', 'company', 'marketing', 'type');
		$_ = new stdClass;

		foreach ( $map as $property ) {
			if ( isset($this->{$property}) ) $_->{$property} = $this->{$property};
		}

		if ( isset($this->info) && ! empty($this->info) ) $_->info = $this->info;
		return $_;
	}

	/**
	 * Loads customer 'info' meta data
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function load_info () {
		$this->info = new ObjectMeta($this->id,'customer');
		if (!$this->info) $this->info = new ObjectMeta();
	}

	public function save () {
		parent::save();

		if (empty($this->info) || !is_array($this->info)) return true;
		foreach ((array)$this->info as $name => $value) {
			$Meta = new MetaObject(array(
				'parent' => $this->id,
				'context' => 'customer',
				'type' => 'meta',
				'name' => $name
			));
			$Meta->parent = $this->id;
			$Meta->context = 'customer';
			$Meta->type = 'meta';
			$Meta->name = $name;
			$Meta->value = $value;
			$Meta->save();
		}
	}

	/**
	 * Determines if the customer is logged in, and checks for wordpress login if necessary
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return bool true if logged in, false if not logged in
	 **/
	public function logged_in () {
		if ( 'wordpress' == shopp_setting('account_system') ) {
			$user = wp_get_current_user();
			return apply_filters('shopp_customer_login_check', $user->ID == $this->wpuser && $this->login );
		}

		return apply_filters('shopp_customer_login_check', $this->login);
	}

	public function logout () {
		$this->login = false;
	}

	public function order () {
		$Shopp = Shopp::object();

		if (!empty($_POST['vieworder']) && !empty($_POST['purchaseid'])) {

			$Purchase = new Purchase($_POST['purchaseid']);
			if ($Purchase->email == $_POST['email']) {
				$Shopp->Purchase = $Purchase;
				$Purchase->load_purchased();
				ob_start();
				locate_shopp_template(array('receipt.php'),true);
				$content = ob_get_contents();
				ob_end_clean();
				return apply_filters('shopp_account_vieworder',$content);
			}
		}

		$request = false; $id = false;
		$Storefront = ShoppStorefront();

		if (isset($Storefront->account)) extract($Storefront->account);
		else {
			if (isset($_GET['acct'])) $request = $_GET['acct'];
			if (isset($_GET['id'])) $request = (int)$_GET['id'];
		}

		if ($this->logged_in() && 'order' == $request && false !== $id) {
			$Purchase = new Purchase((int)$id);
			if ($Purchase->customer == $this->id) {
				ShoppPurchase($Purchase);
				$Purchase->load_purchased();
				ob_start();
				locate_shopp_template(array('account-receipt.php','receipt.php'),true);
				$content = ob_get_contents();
				ob_end_clean();
			} else {
				new ShoppError(sprintf(__('Order number %s could not be found in your order history.','Shopp'),esc_html($_GET['id'])),'customer_order_history',SHOPP_AUTH_ERR);
				unset($_GET['acct']);
				return false;
			}

		}
	}

	public function notification () {
		$Shopp = Shopp::object();
		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$_ = array();
		$_[] = 'From: "'.$blogname.'" <'.shopp_setting('merchant_email').'>';
		$_[] = 'To: '.shopp_setting('merchant_email');
		$_[] = 'Subject: '.sprintf(__('[%s] New Customer Registration','Shopp'),$blogname);
		$_[] = '';
		$_[] = sprintf(__('New customer registration on your "%s" store:','Shopp'), $blogname);
		$_[] = sprintf(__('E-mail: %s','Shopp'), stripslashes($this->email));

		$_[] = apply_filters('shopp_merchant_new_customer_notification',$_);

		if (!shopp_email(join("\n",$_)))
			new ShoppError('The new account notification e-mail could not be sent.','new_account_email',SHOPP_ADMIN_ERR);
		else shopp_debug('A new account notification e-mail was sent to the merchant.');
		if (empty($this->password)) return;

		$_ = array();
		$_[] = 'From: "'.get_option('blogname').'" <'.shopp_setting('merchant_email').'>';
		$_[] = 'To: '.$this->email;
		$_[] = 'Subject: '.sprintf(__('[%s] New Customer Registration','Shopp'),$blogname);
		$_[] = '';
		$_[] = sprintf(__('New customer registration on your "%s" store:','Shopp'), $blogname);
		$_[] = sprintf(__('E-mail: %s','Shopp'), stripslashes($this->email));
		$_[] = sprintf(__('Password: %s'), $this->password);
		$_[] = '';
		$_[] = Shopp::url(false,'account',$Shopp->Gateways->secure);

		$_[] = apply_filters('shopp_new_customer_notification',$_);

		if (!shopp_email(join("\n",$_)))
			new ShoppError('The customer\'s account notification e-mail could not be sent.','new_account_email',SHOPP_ADMIN_ERR);
		else shopp_debug('Successfully created the WordPress user for the Shopp account.');
	}

	public function load_downloads () {
		$Storefront = ShoppStorefront();
		if (empty($this->id)) return false;
		$orders = DatabaseObject::tablename(Purchase::$table);
		$purchases = DatabaseObject::tablename(Purchased::$table);
		$asset = DatabaseObject::tablename(ProductDownload::$table);
		$query = "(SELECT p.dkey AS dkey,p.id,p.purchase,p.download as download,p.name AS name,p.optionlabel,p.downloads,o.total,o.created,f.id as download,f.name as filename,f.value AS filedata
			FROM $purchases AS p
			LEFT JOIN $orders AS o ON o.id=p.purchase
			LEFT JOIN $asset AS f ON f.parent=p.price
			WHERE o.customer=$this->id AND f.context='price' AND f.type='download')
			UNION
			(SELECT a.name AS dkey,p.id,p.purchase,a.value AS download,ao.name AS name,p.optionlabel,p.downloads,o.total,o.created,f.id as download,f.name as filename,f.value AS filedata
			FROM $purchases AS p
			RIGHT JOIN $asset AS a ON a.parent=p.id AND a.type='download' AND a.context='purchased'
			LEFT JOIN $asset AS ao ON a.parent=p.id AND ao.type='addon' AND ao.context='purchased'
			LEFT JOIN $orders AS o ON o.id=p.purchase
			LEFT JOIN $asset AS f on f.id=a.value
			WHERE o.customer=$this->id AND f.context='price' AND f.type='download') ORDER BY created DESC";
		$Storefront->downloads = DB::query($query,'array');
		foreach ($Storefront->downloads as &$download) {
			$download->filedata = unserialize($download->filedata);
			foreach ($download->filedata as $property => $value) {
				$download->{$property} = $value;
			}
		}
	}

	public function load_orders ($filters=array()) {
		if (empty($this->id)) return false;

		$request = false; $id = false;
		$Storefront = ShoppStorefront();

		if (isset($Storefront->account)) extract((array)$Storefront->account);
		else {
			if (isset($_GET['acct'])) $request = $_GET['acct'];
			if (isset($_GET['id'])) $id = (int)$_GET['id'];
		}

		if ($this->logged_in() && 'orders' == $request && !empty($id)) {
			$Purchase = new Purchase((int)$id);
			if ($Purchase->customer == $this->id) {
				ShoppPurchase($Purchase);
				$Purchase->load_purchased();
			} else {
				new ShoppError(sprintf(__('Order number %s could not be found in your order history.','Shopp'),esc_html($_GET['id'])),'customer_order_history',SHOPP_AUTH_ERR);
				unset($_GET['acct']);
				return false;
			}

		}

		$where = '';
		if (isset($filters['where'])) $where = " AND {$filters['where']}";
		$orders = DatabaseObject::tablename(Purchase::$table);
		$purchases = DatabaseObject::tablename(Purchased::$table);
		$query = "SELECT o.* FROM $orders AS o WHERE o.customer=$this->id $where ORDER BY created DESC";

		$PurchaseLoader = new Purchase();
		$purchases = DB::query($query, 'array', array($PurchaseLoader, 'loader'));
		$Storefront->purchases = (array)$purchases;
	}

	public function create_wpuser () {
		require(ABSPATH.'/wp-includes/registration.php');
		if (empty($this->loginname)) return false;
		if (!validate_username($this->loginname)) {
			new ShoppError(__('This login name is invalid because it uses illegal characters. Please enter a valid login name.','Shopp'),'login_exists',SHOPP_ERR);
			return false;
		}
		if (username_exists($this->loginname)){
			new ShoppError(__('The login name is already registered. Please choose another login name.','Shopp'),'login_exists',SHOPP_ERR);
			return false;
		}
		if (empty($this->password)) $this->password = wp_generate_password(12,true);

		// Create the WordPress account
		$wpuser = wp_insert_user(array(
			'user_login' => $this->loginname,
			'user_pass' => $this->password,
			'user_email' => $this->email,
			'display_name' => $this->firstname.' '.$this->lastname,
			'nickname' => $handle,
			'first_name' => $this->firstname,
			'last_name' => $this->lastname
		));
		if (!$wpuser) return false;

		// Link the WP user ID to this customer record
		$this->wpuser = $wpuser;

		if (apply_filters('shopp_notify_new_wpuser',true)) {
			// Send email notification of the new account
			wp_new_user_notification( $wpuser, $this->password );
		}

		shopp_debug('Successfully created the WordPress user for the Shopp account.');

		$this->newuser = true;

		return true;
	}

	/**
	 * Handler for profile updates in the account dashboard
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean|string output based on the account menu request
	 **/
	public function profile () {
		if (empty($_POST['customer'])) return; // Not a valid customer profile update request

		$_POST['phone'] = preg_replace('/[^\d\(\)\-+\. (ext|x)]/','',$_POST['phone']);
		$this->updates($_POST);
		if (isset($_POST['info'])) $this->info = $_POST['info'];

		if (!empty($_POST['password']) && $_POST['password'] == $_POST['confirm-password']) {
			$this->password = wp_hash_password($_POST['password']);
			if ( 'wordpress' == shopp_setting('account_system') && !empty($this->wpuser)) wp_set_password( $_POST['password'], $this->wpuser );
			$this->_password_change = true;
		} else {
			if (!empty($_POST['password'])) new ShoppError(__('The passwords you entered do not match. Please re-enter your passwords.','Shopp'), 'customer_account_management');
			$this->_password_change = false;
		}

		do_action('shopp_customer_update',$this);

		$this->save();
		$this->load_info();

		$addresses = array('Billing'=>'BillingAddress','Shipping'=>'ShippingAddress');
		foreach ($addresses as $Address => $class) {
			$type = strtolower($Address);
			if (isset($_POST[$type]) && !empty($_POST[$type])) {
				$Updated = new $class($this->id,'customer');
				$Updated->customer = $this->id;
				$Updated->updates($_POST[$type]);
				$Updated->save();
				ShoppOrder()->$Address = $Updated;
			}
		}

		$this->_saved = true;

	}

	public function exportcolumns () {
		$prefix = "c.";
		return array(
			$prefix.'firstname' => __('Customer\'s First Name','Shopp'),
			$prefix.'lastname' => __('Customer\'s Last Name','Shopp'),
			$prefix.'email' => __('Customer\'s Email Address','Shopp'),
			$prefix.'phone' => __('Customer\'s Phone Number','Shopp'),
			$prefix.'company' => __('Customer\'s Company','Shopp'),
			$prefix.'marketing' => __('Customer\'s Marketing Preference','Shopp'),
			// $prefix.'info' => __('Customer\'s Custom Information','Shopp'), @todo Re-enable by switching to customer meta data in 1.2
			$prefix.'created' => __('Customer Created Date','Shopp'),
			$prefix.'modified' => __('Customer Last Updated Date','Shopp'),
			);
	}

} // END class Customer

class CustomersExport {

	public $sitename = "";
	public $headings = false;
	public $data = false;
	public $defined = array();
	public $customer_cols = array();
	public $billing_cols = array();
	public $shipping_cols = array();
	public $selected = array();
	public $recordstart = true;
	public $content_type = "text/plain";
	public $extension = "txt";
	public $set = 0;
	public $limit = 1024;

	public function __construct () {

		$Shopp = Shopp::object();

		$this->customer_cols = Customer::exportcolumns();
		$this->billing_cols = BillingAddress::exportcolumns();
		$this->shipping_cols = ShippingAddress::exportcolumns();
		$this->defined = array_merge($this->customer_cols, $this->billing_cols, $this->shipping_cols);

		$this->sitename = get_bloginfo('name');
		$this->headings = (shopp_setting('customerexport_headers') == "on");
		$this->selected = shopp_setting('customerexport_columns');
		shopp_set_setting('customerexport_lastexport',current_time('timestamp'));
	}

	public function query ($request=array()) {
		if (empty($request)) $request = $_GET;

		if (!empty($request['start'])) {
			list($month,$day,$year) = explode("/",$request['start']);
			$starts = mktime(0,0,0,$month,$day,$year);
		}

		if (!empty($request['end'])) {
			list($month,$day,$year) = explode("/",$request['end']);
			$ends = mktime(0,0,0,$month,$day,$year);
		}

		$where = "WHERE c.id IS NOT NULL ";
		if (isset($request['s']) && !empty($request['s'])) $where .= " AND (id='{$request['s']}' OR firstname LIKE '%{$request['s']}%' OR lastname LIKE '%{$request['s']}%' OR CONCAT(firstname,' ',lastname) LIKE '%{$request['s']}%' OR transactionid LIKE '%{$request['s']}%')";
		if (!empty($request['start']) && !empty($request['end'])) $where .= " AND  (UNIX_TIMESTAMP(c.created) >= $starts AND UNIX_TIMESTAMP(c.created) <= $ends)";

		$customer_table = DatabaseObject::tablename(Customer::$table);
		$billing_table = DatabaseObject::tablename(BillingAddress::$table);
		$shipping_table = DatabaseObject::tablename(ShippingAddress::$table);
		$offset = $this->set*$this->limit;

		$c = 0; $columns = array();
		foreach ($this->selected as $column) $columns[] = "$column AS col".$c++;
		$query = "SELECT ".join(",",$columns)." FROM $customer_table AS c LEFT JOIN $billing_table AS b ON c.id=b.customer LEFT JOIN $shipping_table AS s ON c.id=s.customer $where GROUP BY c.id ORDER BY c.created ASC LIMIT $offset,$this->limit";
		$this->data = DB::query($query,'array');
	}

	// Implement for exporting all the data
	public function output () {
		if (!$this->data) $this->query();
		if (!$this->data) return false;
		header("Content-type: $this->content_type; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"$this->sitename Customer Export.$this->extension\"");
		header("Content-Description: Delivered by WordPress/Shopp ".SHOPP_VERSION);
		header("Cache-Control: maxage=1");
		header("Pragma: public");

		$this->begin();
		if ($this->headings) $this->heading();
		$this->records();
		$this->end();
	}

	public function begin() {}

	public function heading () {
		foreach ($this->selected as $name)
			$this->export($this->defined[$name]);
		$this->record();
	}

	public function records () {
		while (!empty($this->data)) {
			foreach ($this->data as $key => $record) {
				foreach(get_object_vars($record) as $column)
					$this->export($this->parse($column));
				$this->record();
			}
			$this->set++;
			$this->query();
		}
	}

	public function parse ($column) {
		if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/",$column)) {
			$list = unserialize($column);
			$column = "";
			foreach ($list as $name => $value)
				$column .= (empty($column)?"":";")."$name:$value";
		}

		return $this->escape($column);
	}

	public function end() {}

	// Implement for exporting a single value
	public function export ($value) {
		echo ($this->recordstart?"":"\t").$this->escape($value);
		$this->recordstart = false;
	}

	public function record () {
		echo "\n";
		$this->recordstart = true;
	}

	public function escape ($value) {
		return $value;
	}

} // END class CustomersExport

class CustomersTabExport extends CustomersExport {

	public function __construct () {
		parent::__construct();
		$this->output();
	}

	public function escape ($value) {
		$value = str_replace(array("\n", "\r"), ' ', $value); // No newlines
		if ( false !== strpos($value, "\t") && false === strpos($value,'"') )	// Quote tabs
			$value = '"' . $value . '"';
		return $value;
	}

} // END class CustomersTabExport

class CustomersCSVExport extends CustomersExport {

	public function __construct () {
		parent::__construct();
		$this->content_type = "text/csv";
		$this->extension = "csv";
		$this->output();
	}

	public function export ($value) {
		echo ($this->recordstart?"":",").$value;
		$this->recordstart = false;
	}

	public function escape ($value) {
		$value = str_replace('"','""',$value);
		if ( preg_match('/^\s|[,"\n\r]|\s$/',$value) )
			$value = '"'.$value.'"';
		return $value;
	}

} // END class CustomersCSVExport

class CustomersXLSExport extends CustomersExport {

	public function __construct () {
		parent::__construct();
		$this->content_type = "application/vnd.ms-excel";
		$this->extension = "xls";
		$this->c = 0; $this->r = 0;
		$this->output();
	}

	public function begin () {
		echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
	}

	public function end () {
		echo pack("ss", 0x0A, 0x00);
	}

	public function export ($value) {
		if (preg_match('/^[\d\.]+$/',$value)) {
		 	echo pack("sssss", 0x203, 14, $this->r, $this->c, 0x0);
			echo pack("d", $value);
		} else {
			$l = strlen($value);
			echo pack("ssssss", 0x204, 8+$l, $this->r, $this->c, 0x0, $l);
			echo $value;
		}
		$this->c++;
	}

	public function record () {
		$this->c = 0;
		$this->r++;
	}
} // END class CustomerXLSExport

/**
 * CustomerAccountPage class
 *
 * A property container for Shopp's customer account page meta
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package customer
 **/
class CustomerAccountPage {

	public $request = '';
	public $label = '';
	public $handler = false;

	public function __construct ( $request, $label, $handler ) {
		$this->request = $request;
		$this->label = $label;
		$this->handler = $handler;
	}

} // END class CustomerAccountPage
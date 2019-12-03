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

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCustomer extends ShoppDatabaseObject {

	const LOGIN  = 1;
	const GUEST  = 2;
	const WPUSER = 4;

	const PASSWORD = 1;
	const PROFILE  = 2;

	static $table = 'customer';

	public $info      = false;		// Custom customer info fields
	public $loginname = false;		// Account login name

	protected $session = 0;         // Tracks Customer session flags
	protected $updates = 0;         // Tracks updated setting flags

	public $_download    = false;   // Current download item in loop
	protected $downloads = array(); // List of purchased downloadable items


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

	public function __sleep () {
		$properties = array_keys( get_object_vars($this) );
		return array_diff($properties, array('updates', 'downloads', '_download'));
	}

	/**
	 * Reset the status flags
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function reset () {
		$this->flag(self::GUEST, false);
		$this->flag(self::WPUSER, false);
	}

	/**
	 * Re-establish actions relavant to this account
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function listeners () {
		add_action('shopp_logged_out', array($this, 'logout'));
	}

	/**
	 * Get session flags or set a session flag
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $flag The flag named constant (ShoppCustomer::LOGIN, ShoppCustomer::GUEST, ShoppCustomer::WPUSER)
	 * @return boolean True if flag is set, false otherwise
	 **/
	public function session ( $flag, $setting = null ) {
		if ( null === $setting ) {
			return ( ($this->session & $flag) == $flag );
		} else return $this->flag('session', $flag, $setting);
	}

	/**
	 * Get customer update flags or set an update flag
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $flag The flag named constant (ShoppCustomer::PASSWORD, ShoppCustomer::PROFILE)
	 * @return boolean True if the flag is set, false otherwise
	 **/
	public function updated ( $flag, $setting = null ) {
		if ( null === $setting ) {
			return ( ($this->updates & $flag) == $flag );
		} else return $this->flag('updates', $flag, $setting);
	}

	/**
	 * Set or get property flags
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $property The flag property to change
	 * @param int $flag The named constant
	 * @param boolean $setting True for on, false for off
	 * @return boolean True if set, false otherwise
	 **/
    protected function flag ( $property, $flag, $setting = false ) {

		if ( ! property_exists($this, $property ) ) return false;

		if ( $setting )
			$this->$property |= $flag;
		else
			$this->$property &= ~$flag;

		return true;

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
		$this->info = new ObjectMeta($this->id, 'customer');
		if ( ! $this->info ) $this->info = new ObjectMeta();
	}

	/**
	 * Save the record to the database
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function save () {

		if ( empty($this->password) ) // Do not save empty password updates
			unset($this->password);

		parent::save();

		if ( empty($this->info) || !is_array($this->info) ) return true;

		foreach ( (array)$this->info as $name => $value ) {
			$Meta = new ShoppMetaObject(array(
				'parent'  => $this->id,
				'context' => 'customer',
				'type'    => 'meta',
				'name'    => $name
			));
			$Meta->parent  = $this->id;
			$Meta->context = 'customer';
			$Meta->type    = 'meta';
			$Meta->name    = $name;
			$Meta->value   = $value;

			$Meta->save();
		}
	}

	/**
	 * Determines if the customer is logged in, and checks for wordpress login if necessary
	 *
	 * @author John Dillick, Jonathan Davis
	 * @since 1.2
	 *
	 * @return bool true if logged in, false if not logged in
	 **/
	public function loggedin () {
		if ( 'wordpress' == shopp_setting('account_system') ) {
			$user = wp_get_current_user();
			return apply_filters('shopp_customer_login_check', $user->ID == $this->wpuser && $this->session(self::LOGIN) );
		}

		return apply_filters('shopp_customer_login_check', $this->session(self::LOGIN));
	}

	/**
	 * Set the customer as logged in
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function login () {
		$this->session(self::LOGIN, true);
	}

	/**
	 * Set the customer as logged out
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function logout () {
		$this->session(self::LOGIN, false);
	}

	/**
	 * Send new customer notification emails
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function notification () {

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$business = shopp_setting('business_name');
		$merchant = shopp_setting('merchant_email');

		$_ = array();
		$_[] = 'From: ' . Shopp::email_from( $merchant, $business );
		$_[] = 'To: ' . Shopp::email_to( $merchant );
		$_[] = 'Subject: ' . Shopp::__('[%s] New Customer Registration', $blogname);
		$_[] = '';
		$_[] = Shopp::__('New customer registration on your &quot;%s&quot; store:', $blogname);
		$_[] = Shopp::__('E-mail: %s', stripslashes($this->email));

		$_ = apply_filters('shopp_merchant_new_customer_notification', $_);

		if ( ! Shopp::email(join("\n", $_)) )
			shopp_add_error('The new account notification e-mail could not be sent.', SHOPP_ADMIN_ERR);
		else shopp_debug('A new account notification e-mail was sent to the merchant.');
		if ( empty($this->password) ) return;

		$_ = array();
		$_[] = 'From: ' . Shopp::email_from( $merchant, $business );
		$_[] = 'To: ' . $this->email;
		$_[] = 'Subject: ' . Shopp::__('[%s] New Customer Registration', $blogname);
		$_[] = '';
		$_[] = Shopp::__('New customer registration on your &quot;%s&quot; store:', $blogname);
		$_[] = Shopp::__('E-mail: %s', stripslashes($this->email));
		$_[] = Shopp::__('Password: %s', $this->password);
		$_[] = '';
		$_[] = Shopp::url(false,'account', ShoppOrder()->security());

		$_ = apply_filters('shopp_new_customer_notification',$_);

		if ( ! Shopp::email(join("\n", $_)) )
			shopp_add_error('The customer&apos;s account notification e-mail could not be sent.', SHOPP_ADMIN_ERR);
		else shopp_debug('A new account notification e-mail was sent to the customer.');
	}

	/**
	 * Loads orders related to this customer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $filters A list of SQL filter parameters
	 * @return void
	 **/
	public function load_orders ( array $filters = array() ) {
		if ( empty($this->id) ) return;

		$Storefront = ShoppStorefront();

		if ( isset($Storefront->account) )
			extract((array)$Storefront->account);

		if ( ! empty($id) )
			$this->order($id);

		$where = array("o.customer=$this->id");
		if ( isset($filters['where']) ) $where[] = $filters['where'];
		$where = join(' AND ', $where);

		$orders = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$query  = "SELECT o.* FROM $orders AS o WHERE $where ORDER BY created DESC";

		$PurchaseLoader = new ShoppPurchase();
		$purchases      = sDB::query($query, 'array', array($PurchaseLoader, 'loader'));

		$Storefront->purchases = (array)$purchases;
	}

	/**
	 * Loads an order by id associated with only this customer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $id The purchase record ID
	 * @return void
	 **/
	public function order ( $id ) {
		$Purchase = new ShoppPurchase(array('id' => (int) $id, 'customer' => $this->id));

		if ( $Purchase->exists() )  {
			ShoppPurchase($Purchase);
			$Purchase->load_purchased();
			return;
		}

		shopp_add_error(Shopp::__('Order number %s could not be found in your order history.', (int) $id), SHOPP_AUTH_ERR);
	}

	/**
	 * Generates a hashed version of the password
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function hashpass () {

		if ( empty($this->password) ) return;

		$password = $this->password;
		$this->clearpass();
		$this->passhash = wp_hash_password($password);

	}

	/**
	 * Clear the password data
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function clearpass () {
		$this->password = '';
		if ( isset($this->_confirm_password) )
			unset($this->_confirm_password);
	}

	/**
	 * Create a new WordPress user associated with this customer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if successful, false otherwise
	 **/
	public function create_wpuser () {

		if ( empty($this->loginname) ) return false;

		if ( ! validate_username($this->loginname) ) {
			shopp_add_error(Shopp::__('This login name is invalid because it uses illegal characters. Valid login names include: letters, numbers, spaces, . - @ _'));
			return false;
		}

		if ( username_exists($this->loginname) ) {
			shopp_add_error(Shopp::__('The login name is already registered. Please choose another login name.'));
			return false;
		}

		if ( empty($this->password) ) $this->password = wp_generate_password(12, true);

		// Create the WordPress account
		$wpuser = wp_insert_user(array(
			'user_login'   => $this->loginname,
			'user_pass'    => $this->password,
			'user_email'   => $this->email,
			'display_name' => $this->firstname . ' ' . $this->lastname,
			'nickname'     => $this->firstname,
			'first_name'   => $this->firstname,
			'last_name'    => $this->lastname
		));

		if ( ! $wpuser ) return false;

		// Link the WP user ID to this customer record
		$this->wpuser = $wpuser;

		if ( isset($this->passhash) ) {
			global $wpdb;
			$wpdb->update( $wpdb->users, array('user_pass' => $this->passhash), array('ID' => $wpuser) );
		}

		if ( apply_filters('shopp_notify_new_wpuser', true) ) {
			// Send email notification of the new account
			$password = isset($this->passhash) ? '*******' : $this->password; // Only include generated passwords
			wp_new_user_notification( $wpuser, $password );
		}

		shopp_debug(sprintf('Successfully created the WordPress user "%s" for the Shopp account.', $this->loginname));

		// Set the WP user created flag
		$this->session(self::WPUSER, true);

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
		if ( empty($_POST['customer']) ) return; // Not a valid customer profile update request

		check_admin_referer('shopp_profile_update');

		$defaults = array(
			'phone'            => '',
			'password'         => null,
			'confirm-password' => null,
			'info'             => null,
			'billing'          => array(),
			'shipping'         => array()
		);
		$updates = array_merge($defaults, $_POST);
		extract($updates, EXTR_SKIP);

		$phone = preg_replace('/[^\d\(\)\-+\. (ext|x)]/', '', $phone);

		// Update this ShoppCustomer model
		$this->updates($updates);

		if ( is_array($info) ) $this->info = $info; // Add info fields

		if ( '' !=  $password . $updates['confirm-password'] && $password == $updates['confirm-password'] ) {

			$this->password = wp_hash_password($password);
			if ( 'wordpress' == shopp_setting('account_system') && ! empty($this->wpuser) )
				wp_set_password( $password, $this->wpuser );

			$this->_password_change = true;

		} else {

			if ( ! empty($password) )
				shopp_add_error(Shopp::__('The passwords you entered do not match. Please re-enter your passwords.'));

			$this->_password_change = false;

		}

		do_action('shopp_customer_update', $this);

		$this->save();
		$this->load_info();

		$addresses = array('billing' => 'Billing', 'shipping' => 'Shipping');
		foreach ( $addresses as $type => $Address ) {
			if ( empty($updates[ $type ]) ) continue;

			$Updated           = ShoppOrder()->$Address;
			$Updated->customer = $this->id;

			$Updated->updates($updates[ $type ]);
			$Updated->save();
		}

		$this->updated(self::PROFILE, true);

		if ( $this->_password_change )
			Shopp::redirect(Shopp::url(false, 'account'));

	}

	/**
	 * Indicates if the customer has purchased downloadable assets.
	 *
	 * @return bool
	 */
	public function has_downloads ( array $options ) {
		$this->load_downloads($options);
		return ( ! empty($this->downloads) );
	}

	/**
	 * Loads downloadable purchase data for this customer (populates the downloads property).
	 */
	public function load_downloads ( array $options = array() ) {
		$defaults = array(
			'show'    => false,
			'from'    => false,
			'to'      => false,
			'orderby' => 'created',
			'order'   => 'DESC'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		$paidonly = $downloads = true;

		// Bail out if we can't load customer order data
		if ( ! ( $purchases = shopp_orders($from, $to, true, array($this->id), $show, $order, $orderby, $paidonly, $downloads) ) ) return;
		$this->downloads = array();

		foreach ( $purchases as $Purchase ) {
			if ( ! in_array($Purchase->txnstatus, array('captured')) ) continue;
			reset($Purchase->purchased);
			$this->extract_downloads($Purchase->purchased);
		}
	}

	protected function extract_downloads ( $items ) {
		$this->_filemap = array(); // Temporary property to hold the file mapping index

		foreach( $items as $item ) {
			list($index, $Purchased) = $item;

			// Check for downloadable addons
			if ( isset($Purchased->addons->meta) && count($Purchased->addons->meta) >= 1 ) {
				$this->extract_downloads($Purchased->addons->meta);
			}

			// Is *this* item an addon?
			if ( is_a($Purchased, 'ShoppMetaObject') ) $Purchased = $Purchased->value;

			// Is it a downloadable item? Do not add the same dkey twice
			if ( empty($Purchased->download) ) continue;

			// Load download file data
			$this->downloads[ $Purchased->dkey ]    = $Purchased;
			$this->_filemap[ $Purchased->download ] = $Purchased->dkey;
		}

		$this->load_downloadfiles( array_keys($this->_filemap) );

	}

	/**
	 * Loads the extra download file data for the loaded customer downloads
	 *
	 * @author Jonathan Davis
	 * @since 1.3.2
	 *
	 * @param array $downloads a list of download meta record ids to load
	 * @return void
	 **/
	public function load_downloadfiles ( array $downloads = array() ) {

		if ( empty($downloads) ) return false;

		$meta_table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		sDB::query("SELECT * FROM $meta_table WHERE id IN (" . join(',', $downloads) . ")", 'array', array($this, 'download_loader'));

		unset($this->_filemap);
	}

	/**
	 * Download record loader to map download file data to the loaded downloads for the customer
	 *
	 * @author Jonathan Davis
	 * @since 1.3.2
	 *
	 * @param array $records The records to map to (unused becase they are mapped to the Customer->downloads records)
	 * @param array $record	The record data loaded from the query
	 * @return void
	 **/
	public function download_loader ( &$records, &$record ) {

		// Lookup the download key
		if ( empty($this->_filemap[ $record->id ]) ) return;
		$dkey = $this->_filemap[ $record->id ];

		// Find the purchased download
		if ( empty($this->downloads[ $dkey ]) ) return;
		$Purchased = &$this->downloads[ $dkey ];

		// Unserialize the file data
		$data = maybe_unserialize($record->value);
		if ( is_object($data) ) $properties = get_object_vars($data);
		else return;

		// Map the file data to the purchased download record
		foreach ( (array)$properties as $property => $value )
			$Purchased->$property = sDB::clean($value);

	}

	public function reset_downloads () {
		reset($this->downloads);
	}

	public function each_download () {
		if ( empty($this->downloads) ) return false;
		$this->_download = current($this->downloads);
		next($this->downloads);
		return $this->_download;
	}

	public static function exportcolumns () {
		$prefix = "c.";
		return array(
			$prefix . 'firstname' => Shopp::__('Customer\'s First Name'),
			$prefix . 'lastname'  => Shopp::__('Customer\'s Last Name'),
			$prefix . 'email'     => Shopp::__('Customer\'s Email Address'),
			$prefix . 'phone'     => Shopp::__('Customer\'s Phone Number'),
			$prefix . 'company'   => Shopp::__('Customer\'s Company'),
			$prefix . 'type'      => Shopp::__('Customer Type'),
			$prefix . 'marketing' => Shopp::__('Customer\'s Marketing Preference'),
			$prefix . 'created'   => Shopp::__('Customer Created Date'),
			$prefix . 'modified'  => Shopp::__('Customer Last Updated Date'),
			// $prefix.'info' => Shopp::__('Customer\'s Custom Information'), @todo Re-enable by switching to customer meta data in 1.2
			);
	}

} // END class ShoppCustomer

class CustomersExport {

	public $sitename      = '';
	public $headings      = false;
	public $data          = false;
	public $defined       = array();
	public $customer_cols = array();
	public $billing_cols  = array();
	public $shipping_cols = array();
	public $selected      = array();
	public $recordstart   = true;
	public $content_type  = 'text/plain';
	public $extension     = 'txt';
	public $set           = 0;
	public $limit         = 1024;

	public function __construct () {

		$this->customer_cols = ShoppCustomer::exportcolumns();
		$this->billing_cols  = BillingAddress::exportcolumns();
		$this->shipping_cols = ShippingAddress::exportcolumns();
		$this->defined       = array_merge($this->customer_cols, $this->billing_cols, $this->shipping_cols);

		$this->sitename = get_bloginfo('name');
		$this->headings = (shopp_setting('customerexport_headers') == 'on');
		$this->selected = shopp_setting('customerexport_columns');
		shopp_set_setting('customerexport_lastexport', current_time('timestamp'));
	}

	public function query ($request=array()) {
		if ( empty($request) ) $request = $_GET;

		if ( ! empty($request['start']) ) {
			list($month, $day, $year) = explode('/', $request['start']);
			$starts                   = mktime(0, 0, 0, $month, $day, $year);
		}

		if ( ! empty($request['end']) ) {
			list($month, $day, $year) = explode('/', $request['end']);
			$ends                     = mktime(0, 0, 0, $month, $day, $year);
		}

		$where = "WHERE c.id IS NOT NULL ";
		if ( isset($request['s']) && ! empty($request['s']) ) $where .= " AND (id='{$request['s']}' OR firstname LIKE '%{$request['s']}%' OR lastname LIKE '%{$request['s']}%' OR CONCAT(firstname,' ',lastname) LIKE '%{$request['s']}%' OR transactionid LIKE '%{$request['s']}%')";
		if ( ! empty($request['start']) && ! empty($request['end']) ) $where .= " AND  (UNIX_TIMESTAMP(c.created) >= $starts AND UNIX_TIMESTAMP(c.created) <= $ends)";

		$customer_table = ShoppDatabaseObject::tablename(Customer::$table);
		$billing_table  = ShoppDatabaseObject::tablename(BillingAddress::$table);
		$shipping_table = ShoppDatabaseObject::tablename(ShippingAddress::$table);
		$offset         = $this->set*$this->limit;

		$c       = 0;
		$columns = array();

		foreach ( $this->selected as $column ) $columns[] = "$column AS col" . $c++;

		$query = "SELECT " . join(",", $columns)." FROM $customer_table AS c LEFT JOIN $billing_table AS b ON c.id=b.customer LEFT JOIN $shipping_table AS s ON c.id=s.customer $where GROUP BY c.id ORDER BY c.created ASC LIMIT $offset,$this->limit";
		$this->data = sDB::query($query, 'array');
	}

	// Implement for exporting all the data
	public function output () {
		if ( ! $this->data) $this->query();
		if ( ! $this->data) return false;
		header("Content-type: $this->content_type; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"$this->sitename Customer Export.$this->extension\"");
		header("Content-Description: Delivered by " . ShoppVersion::agent());
		header("Cache-Control: maxage=1");
		header("Pragma: public");

		$this->begin();
		if ( $this->headings ) $this->heading();
		$this->records();
		$this->end();
	}

	public function begin() {}

	public function heading () {
		foreach ( $this->selected as $name )
			$this->export($this->defined[$name]);

		$this->record();
	}

	public function records () {
		while ( ! empty($this->data) ) {

			foreach ( $this->data as $record ) {

				foreach( get_object_vars($record) as $column )
					$this->export($this->parse($column));

				$this->record();
			}
			$this->set++;
			$this->query();
		}
	}

	public function parse ( $column ) {
		if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/", $column)) {
			$list   = unserialize($column);
			$column = '';
			foreach ( $list as $name => $value )
				$column .= ( empty($column) ? '' : ';' ) . "$name:$value";
		}

		return $this->escape($column);
	}

	public function end() {}

	// Implement for exporting a single value
	public function export ( $value ) {
		echo ($this->recordstart ? '' : "\t") . $this->escape($value);
		$this->recordstart = false;
	}

	public function record () {
		echo "\n";
		$this->recordstart = true;
	}

	public function escape ( $value ) {
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
		$this->content_type = 'text/csv';
		$this->extension    = 'csv';
		$this->output();
	}

	public function export ( $value ) {
		echo ( $this->recordstart ? '' : ',' ) . $value;
		$this->recordstart = false;
	}

	public function escape ( $value ) {
		$value = str_replace('"','""', $value);
		if ( preg_match('/^\s|[,"\n\r]|\s$/', $value) )
			$value = '"' . $value . '"';
		return $value;
	}

} // END class CustomersCSVExport

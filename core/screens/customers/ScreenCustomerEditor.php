<?php
/**
 * ScreenCustomerEditor.php
 *
 * Screen controller for the customer editor screen.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

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

		new ShoppAdminCustomerSaveBox($this, 'side', 'core', array('Customer' => $Customer));
		new ShoppAdminCustomerSettingsBox($this, 'side', 'core', array('Customer' => $Customer));
		new ShoppAdminCustomerLoginBox($this, 'side', 'core', array('Customer' => $Customer));

		new ShoppAdminCustomerContactBox($this, 'normal', 'core', array('Customer' => $Customer));

		if ( ! empty($Customer->info->meta) && is_array($Customer->info->meta) )
			new ShoppAdminCustomerInfoBox($this, 'normal', 'core', array('Customer' => $Customer));

		new ShoppAdminCustomerBillingAddressBox($this, 'normal', 'core', array('Customer' => $Customer));
		new ShoppAdminCustomerShippingAddressBox($this, 'normal', 'core', array('Customer' => $Customer));

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
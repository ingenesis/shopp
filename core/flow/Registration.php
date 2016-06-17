<?php
/**
 * Registration.php
 *
 * Handles customer registration form processing
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage order
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Customer registration manager
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1
 * @package order
 **/
class ShoppRegistration extends FormPostFramework {

	const PROCESS = 'shopp_registration';

	protected $defaults = array(
		'sameaddress' => 'off',
		'firstname' => '',
		'lastname' => '',
		'phone' => '',
		'company' => '',
		'billing' => array(),
		'shipping' => array(),
		'info' => array(),
		'marketing' => '',
		'loginname' => '',
		'password' => '',
		'confirm-password' => ''
	);

	public function __construct () {

		if ( empty($_POST) ) return;

		$this->updateform();

		if ( ! self::submitted() ) return;

		add_action('parse_request', array($this, 'info'));
		add_action('parse_request', array($this, 'customer'));
		add_action('parse_request', array($this, 'shipaddress'));
		add_action('parse_request', array($this, 'billaddress'));

		add_action('parse_request', array(__CLASS__, 'process'));

		add_filter('shopp_validate_registration', array('ShoppFormValidation', 'names'));
		add_filter('shopp_validate_registration', array('ShoppFormValidation', 'email'));
		add_filter('shopp_validate_registration', array('ShoppFormValidation', 'login'));
		add_filter('shopp_validate_registration', array('ShoppFormValidation', 'passwords'));
		add_filter('shopp_validate_registration', array('ShoppFormValidation', 'shipaddress'));
		add_filter('shopp_validate_registration', array('ShoppFormValidation', 'billaddress'));

		// Catch custmer login changes from ShoppFormValidation::login @see #3044 & #3053
		add_action('shopp_customer_registration', array($this, 'customer'));

		add_filter('shopp_registration_redirect', '__return_true');

	}

	public static function submitted () {
		return isset($_POST[ self::PROCESS ]);
	}

	public function info () {
		$Customer = ShoppOrder()->Customer;

		if ( $this->form('info') )
			$Customer->info = $this->form('info');

	}

	public function customer () {

		$Customer = ShoppOrder()->Customer;

		$updates = array(
			'firstname' => $this->form('firstname'),
			'lastname' => $this->form('lastname'),
			'company' => $this->form('company'),
			'email' => $this->form('email'),
			'phone' => $this->form('phone'),
			'info' => $this->form('info'),
			'marketing' => $this->form('marketing'),
			'password' => $this->form('password', true),
			'loginname' => $this->form('loginname', true)
		);

		// Remove invalid characters from the phone number
		$updates['phone'] = preg_replace('/[^\d\(\)\-+\. (ext|x)]/','', $updates['phone'] );

		if ( empty($Customer) ) $Customer = new ShoppCustomer();
		else $Customer->reset();

		$Customer->updates($updates);

		// Keep confirm-password field value when showing checkout validation errors
		$confirmpass = $this->form('confirm-password', true);
		if ( ! empty($confirmpass) )
			$Customer->_confirm_password = $confirmpass;


	}

	public function shipaddress () {

		$ShippingAddress = ShoppOrder()->Shipping;
		$BillingAddress = ShoppOrder()->Billing;

		if ( empty($ShippingAddress) )
			$ShippingAddress = new ShippingAddress();

		$form = $this->form('shipping');

		if ( ! empty($form) ) $ShippingAddress->updates($form);

		// Handle same address copying
		ShoppOrder()->sameaddress = strtolower( $this->form('sameaddress') );

		if ( 'billing' == ShoppOrder()->sameaddress )
			$BillingAddress->updates($form);

	}

	public function billaddress () {

		$BillingAddress = ShoppOrder()->Billing;
		$ShippingAddress = ShoppOrder()->Shipping;

		if ( empty($BillingAddress) )
			$BillingAddress = new BillingAddress();

		$form = $this->form('billing');

		// Prevent overwriting the card data when updating the BillingAddress
		$ignore = array();
		if ( ! empty($form['card']) && preg_replace('/[^\d]/', '', $form['card']) == substr($BillingAddress->card, -4) )
			$ignore[] = 'card';

		$BillingAddress->updates($form, $ignore);

		// Handle same address copying
		ShoppOrder()->sameaddress = strtolower( $this->form('sameaddress') );
		if ( 'shipping' == ShoppOrder()->sameaddress )
			$ShippingAddress->updates($form);

	}

	public static function process () {

		// We have to avoid truthiness, hence the strange logic expression
		if ( true !== apply_filters('shopp_validate_registration', true) ) return;

		$Customer = ShoppOrder()->Customer;
		do_action('shopp_customer_registration', $Customer);

		if ( $Customer->session(ShoppCustomer::GUEST) ) {
			$Customer->type = __('Guest', 'Shopp'); // No cuts
			$Customer->wpuser = 0;                  // No buts
			unset($Customer->password);             // No coconuts
		} else {

			// WordPress account integration used, customer has no wp user
			if ( 'wordpress' == shopp_setting('account_system') && empty($Customer->wpuser) ) {
				if ( $wpuser = get_current_user_id() ) $Customer->wpuser = $wpuser; // use logged in WordPress account
				else $Customer->create_wpuser(); // not logged in, create new account
			}

			if ( ! $Customer->exists(true) ) {
				$Customer->id = false;
				shopp_debug('Creating new Shopp customer record');
				if ( empty($Customer->password) )
					$Customer->password = wp_generate_password(12, true);

				if ( 'shopp' == shopp_setting('account_system') ) $Customer->notification();
				$Customer->password = wp_hash_password($Customer->password);
				if ( isset($Customer->passhash) ) $Customer->password = $Customer->passhash;
			} else unset($Customer->password); // Existing customer, do not overwrite password field!

		}

		// New customer, save hashed password
		$Customer->save();
        $Customer->password = '';

		// Update billing and shipping addresses
		$addresses = array('Billing', 'Shipping');
		foreach ( $addresses as $Address ) {
			if ( empty(ShoppOrder()->$Address->address) ) continue;
			$Address = ShoppOrder()->$Address;
			$Address->customer = $Customer->id;
			$Address->save();
		}

		do_action('shopp_customer_registered', $Customer);

		// Auto-login
		$Customer->login(); // Login the customer
		if ( ! empty($Customer->wpuser) ) // Log the WordPress user in
			ShoppLogin::wpuser(get_user_by('id', $Customer->wpuser));

        if ( apply_filters('shopp_registration_redirect', false) )
			Shopp::redirect( Shopp::url(false, 'account') );
	}

}
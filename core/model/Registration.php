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
class ShoppRegistration {

	const PROCESS = 'shopp_registration';

	private $form = array();		// Holds the cleaned up POST data

	static $defaults = array(
		'sameaddress' => 'off',
		'firstname' => '',
		'lastname' => '',
		'phone' => '',
		'company' => '',
		'billing' => array(),
		'shipping' => array(),
		'info' => array(),
	);

	public function __construct ( $must_setup = false ) {

		$_POST = apply_filters('shopp_customer_registration',$_POST);

		$submitted = stripslashes_deep($_POST);					// Clean it up
		$this->form = array_merge(self::$defaults, $submitted);	// Capture it

		if ( ! self::submitted() && ! $must_setup ) return;

		add_action('parse_request', array($this, 'info'));
		add_action('parse_request', array($this, 'customer'));
		add_action('parse_request', array($this, 'shipaddress'));
		add_action('parse_request', array($this, 'billaddress'));
		add_action('parse_request', array($this, 'process'));

		add_action('shopp_validate_registration', 'ShoppFormValidation::names');
		add_action('shopp_validate_registration', 'ShoppFormValidation::email');
		add_action('shopp_validate_registration', 'ShoppFormValidation::login');
		add_action('shopp_validate_registration', 'ShoppFormValidation::passwords');
		add_action('shopp_validate_registration', 'ShoppFormValidation::shipaddress');
		add_action('shopp_validate_registration', 'ShoppFormValidation::billaddress');

	}

	public static function submitted () {
		return isset($_POST[ self::PROCESS ]);
	}


	public function form ( string $key = null ) {
		if ( isset($key) ) {
			if ( isset($this->form[ $key ]) )
				return $this->form[ $key ];
			else return false;
		}

		return $this->form;
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
			'info' => $this->form('info')
		);

		// Remove invalid characters from the phone number
		$updates['phone'] = preg_replace('/[^\d\(\)\-+\. (ext|x)]/','', $updates['phone'] );

		if ( empty($Customer) ) $Customer = new Customer();
		else $Customer->reset();

		$Customer->updates($updates);

		// Keep confirm-password field value when showing checkout validation errors
		$confirmpass = $this->form('confirm-password');
		if ( ! empty($confirmpass) )
			$Customer->_confirm_password = $confirmpass;

	}

	public function shipaddress () {

		$ShippingAddress = ShoppOrder()->Shipping;
		$BillingAddress = ShoppOrder()->Billing;

		if ( empty($ShippingAddress) )
			$ShippingAddress = new ShippingAddress();

		$form = $this->form('shipped');

		if ( ! empty($form) ) $ShippingAddress->updates($form);

		// Handle same address copying
		$copy = strtolower( $this->form('sameaddress') );
		if ( 'billing' == $copy ) {
			ShoppOrder()->sameaddress = $copy;
			$BillingAddress->updates($form);
		}

	}

	public function billaddress () {

		$BillingAddress = ShoppOrder()->Billing;
		$ShippingAddress = ShoppOrder()->Shipping;

		if ( empty($BillingAddress) )
			$BillingAddress = new BillingAddress();

		$form = $this->form('billing');

		// Prevent overwriting the card data when updating the BillingAddress
		$ignore = array();
		if ( ! empty($form['card']) && preg_replace('/[^\d]/','',$form['card']) == substr($BillingAddress->card,-4) )
			$ignore[] = 'card';

		$BillingAddress->updates($form,$ignore);

		// Handle same address copying
		$copy = strtolower( $this->form('sameaddress') );
		if ( 'shipping' == $copy ) {
			ShoppOrder()->sameaddress = $copy;
			$ShippingAddress->updates($form);
		}

	}

	public function process () {

		if ( true !== apply_filters('shopp_validate_registration', false) ) return;

		$Customer = ShoppOrder()->Customer;
		$BillingAddress = ShoppOrder()->Billing;
		$ShippingAddress = ShoppOrder()->Shipping;


		if ( $Customer->guest ) {

			$Customer->type = __('Guest', 'Shopp');

		} else {

			// WordPress account integration used, customer has no wp user
			if ( 'wordpress' == shopp_setting('account_system') && empty($Customer->wpuser) ) {
				if ( $wpuser = get_current_user_id() ) $Customer->wpuser = $wpuser; // use logged in WordPress account
				else $Customer->create_wpuser(); // not logged in, create new account
			}

		}

		// New customer, save hashed password
		if ( ! $Customer->exists() && ! $Customer->guest ) {
			$Customer->id = false;
			shopp_debug('Creating new Shopp customer record');
			if ( empty($Customer->password) )
				$Customer->password = wp_generate_password(12, true);

			if ( 'shopp' == shopp_setting('account_system') ) $Customer->notification();
			$Customer->password = wp_hash_password($Customer->password);
		} else unset($Customer->password); // Existing customer, do not overwrite password field!

		$Customer->save();

		// Update billing address
		if ( ! empty($BillingAddress->address) ) {
			$BillingAddress->customer = $Customer->id;
			$BillingAddress->save();
		}

		// Update shipping address
		if ( ! empty($ShippingAddress->address) ) {
			$ShippingAddress->customer = $Customer->id;
			$ShippingAddress->save();
		}

		do_action('shopp_customer_registered', $Customer);

		shopp_redirect( shoppurl(false, 'account') );

	}

}
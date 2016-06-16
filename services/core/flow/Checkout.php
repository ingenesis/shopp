<?php
/**
 * Checkout.php
 *
 * Handles checkout form processing
 *
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @version 1.3
 * @package Shopp/Flow/Checkout
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Checkout manager
 *
 * @since 1.1
 * @version 1.2
 **/
class ShoppCheckout extends FormPostFramework {

	/** @var boolean $confirmed Flag to indicate confirmed orders. */
	private $confirmed = false;

	/** @var ShoppRegistration $Register The ShoppRegistration manager. */
	private $Register = false;

	/** @var array $defaults The default inputs of the checkout form. */
	protected $defaults = array(
		'guest'       => false,
		'sameaddress' => 'off',
		'firstname'   => '',
		'lastname'    => '',
		'phone'       => '',
		'company'     => '',
		'shipmethod'  => false,
		'billing'     => array(),
		'shipping'    => array(),
		'info'        => array(),
		'data'        => array()
	);

	/**
	 * Constructor.
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct() {

		Shopping::restore('confirmed', $this->confirmed);

		if ( empty($_POST) ) return;

		$this->updateform();

		$action = $this->form('checkout');

		add_action('shopp_confirm_order', array($this, 'confirmed'));

		if ( empty($action) ) return;

		$this->Register = new ShoppRegistration();

		add_action('shopp_process_shipmethod', array($this, 'shipmethod'));

		add_action('shopp_process_checkout', array($this, 'data'));
		add_action('shopp_process_checkout', array($this, 'customer'));
		add_action('shopp_process_checkout', array($this, 'shipaddress'));
		add_action('shopp_process_checkout', array($this, 'shipmethod'));
		add_action('shopp_process_checkout', array($this, 'billaddress'));
		add_action('shopp_process_checkout', array($this, 'payment'));
		add_action('shopp_process_checkout', array($this, 'process'));

		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'names'));
		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'email'));
		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'data'));
		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'login'));
		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'passwords'));
		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'billaddress'));
	}

	/**
	 * Adds custom order data to the order data registry.
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function data() {

		if ( $this->form('data') )
			ShoppOrder()->data = $this->form('data');

	}

	/**
	 * Processes customer account fields.
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function customer() {

		$Customer = ShoppOrder()->Customer;

		// Update guest checkout flag
		$guest = Shopp::str_true($this->form('guest'));

		// Set the customer guest flag
		$Customer->session(ShoppCustomer::GUEST, apply_filters('shopp_guest_checkout', $guest));

		$this->Register->customer();

	}

	/**
	 * Processes changes to the shipping address.
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function shipaddress() {

		$Cart = ShoppOrder()->Cart;
		$ShippingAddress = ShoppOrder()->Shipping;

		if ( ! $Cart->shipped() ) // Use blank shipping for non-Shipped orders
			return $ShippingAddress = new ShippingAddress();

		$this->Register->shipaddress();

		if ( $Cart->shipped() )
			do_action('shopp_update_destination');

	}

	/**
	 * Processes changes to the shipping method.
	 *
	 * Handles changes to the shipping method outside of other
	 * checkout processes.
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function shipmethod() {
		$Shiprates = ShoppOrder()->Shiprates;
		$ShippingAddress = ShoppOrder()->Shipping;

		if ( $Shiprates->disabled() ) return;

		if ( empty($ShippingAddress) )
			$ShippingAddress = new ShippingAddress();

		$selection = $this->form('shipmethod');
		$selected = isset($Shiprates->selected()->slug) ? $Shiprates->selected()->slug : '';
		if ( $selection == $selected ) return;

		// Verify shipping method exists first
		if ( ! $Shiprates->exists($selection) ) return;

		$selected = $Shiprates->selected( $selection );

		$ShippingAddress->option = $selected->name;
		$ShippingAddress->method = $selected->slug;
	}


	/**
	 * Processes changes to the billing address.
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function billaddress() {
		$Cart = ShoppOrder()->Cart;
		$BillingAddress = ShoppOrder()->Billing;

		$this->Register->billaddress();

		// Special case for updating/tracking billing locale
		$form = $this->form('billing');
		if ( ! empty($form['locale']) )
			$BillingAddress->locale = $form['locale'];

		if ( ! $Cart->shipped() || ! empty($form['locale']) || 'shipping' == ShoppOrder()->sameaddress )
			do_action('shopp_update_destination');

	}

	/**
	 * Processes payment information changes.
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function payment() {
		if ( ! $this->paycard() ) return;

		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'paycard'));

		$Billing = ShoppOrder()->Billing;
		$Payments = ShoppOrder()->Payments;
		$form = $this->form('billing');

		// Change the default cardtype to the selected payment service option label
		$Billing->cardtype = empty($form['cardtype']) ? $Payments->selected()->label : $form['cardtype'];

		// If the card number is provided over a secure connection
		// Change the cart to operate in secure mode
		if ( ! empty($form['card']) && is_ssl() )
			ShoppShopping()->secured(true);

		// Sanitize the card number to ensure it only contains numbers
		if ( strlen( $PAN = self::digitsonly($form['card']) ) > 4 )
			$Billing->card = $PAN;

		if ( ! empty($form['cardexpires-mm']) && ! empty($form['cardexpires-yy']) ) {
			$exmm = self::digitsonly($form['cardexpires-mm']);
			$exyy = self::digitsonly($form['cardexpires-yy']);
			$Billing->cardexpires = mktime(0, 0, 0, $exmm, 1, $exyy + 2000);
		} else $Billing->cardexpires = 0;

		$Billing->cvv = self::digitsonly($form['cvv']);

		// Extra card security check fields
		if ( ! empty($form['xcsc']) ) {
			$Billing->xcsc = array();
			foreach ( (array) $form['xcsc'] as $field => $value ) {
				$Billing->Billing->xcsc[] = $field;	// Add to the XCSC registry of fields
				$Billing->$field = $value;			// Add the property
			}
		}

	}

	/**
	 * Determine if payment card data has been submitted.
	 *
	 * @since 1.1
	 *
	 * @return boolean True if payment card information was submitted, false otherwise.
	 **/
	public function paycard() {
		$fields = array('card', 'cardexpires-mm', 'cardexpires-yy', 'cvv');
		$billing = $this->form('billing');
		foreach ( $fields as $field )
			if ( isset($billing[ $field ]) ) return true;
		return false;
	}


	/*
	 * Checkout form processing.
	 *
	 * Handles taking user input from the checkout form and
	 * processing the information into useable order data.
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function process() {

		$action = $this->form('checkout');
		if ( 'process' != $action ) return;

		$Payments = ShoppOrder()->Payments;
		$Cart = ShoppOrder()->Cart;

		$forcedconfirm = 'always' == shopp_setting('order_confirmation');
		$wasfree = $Cart->orderisfree(); // Get current free status
		$estimated = $Cart->total();     // Get current total

		$Cart->totals(); // Retotal after checkout to capture order total changes

		// We have to avoid truthiness, hence the strange logic expression
		if ( true !== apply_filters('shopp_validate_checkout', true) ) return;
		else $this->customer(); // Catch changes from validation

		// Catch originally free orders that get extra (shipping) costs added to them
		if ( $wasfree && $Payments->count() > 1 && ! $Cart->orderisfree() && empty($Payments->selected()->cards) ) {
			shopp_add_error( Shopp::__('The order amount changed and requires that you select a payment method.') );
			Shopp::redirect( Shopp::url(false, 'checkout', ShoppOrder()->security()) );
		}

		// Do not use shopp_checkout_processed for payment gateway redirect actions
		// Free order processing doesn't take over until the order is submitted for processing in `shopp_process_order`
		do_action('shopp_checkout_processed');

		// If the cart's total changes at all, confirm the order
		if ( apply_filters('shopp_order_confirm_needed', $estimated != $Cart->total() || $forcedconfirm ) ) {
			Shopp::redirect( Shopp::url(false, 'confirm', ShoppOrder()->security()) );
			return;
		}

		do_action('shopp_process_order');
	}

	/**
	 * Account registration processing.
	 *
	 * @since 1.3
	 **/
	public function registration() {

		// Validation already conducted during the checkout process
        add_filter('shopp_validate_registration', '__return_true');

		// Prevent redirection to account page after registration
        add_filter('shopp_registration_redirect', '__return_false');

		ShoppRegistration::process();

	}

	/**
	 * Confirms the order and starts order processing.
	 *
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function confirmed() {

		if ( 'confirmed' == $this->form('checkout') ) {
			$this->confirmed = true;
			do_action('shopp_process_order');
		}

	}

	/**
	 * Filters a string to provide only the digits found in the string.
	 *
	 * @since 1.3.9
	 *
	 * @param string $string The string to filter
	 * @return string The string of digits
	 **/
	protected static function digitsonly( $string ) {
		$filtered = filter_var($string, FILTER_SANITIZE_NUMBER_INT);
		return str_replace(array('+', '-'), '', $filtered);
	}

}
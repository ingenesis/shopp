<?php
/**
 * Checkout.php
 *
 * Handles checkout form processing
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
 * Checkout manager
 *
 * @author Jonathan Davis
 * @since 1.1
 * @version 1.2
 * @package order
 **/
class ShoppCheckout extends FormPostFramework {

	private $confirmed = false;		// Flag to indicate
	private $Register = false;		// The ShoppRegistration manager

	protected $defaults = array(
		'guest' => false,
		'sameaddress' => 'off',
		'firstname' => '',
		'lastname' => '',
		'phone' => '',
		'company' => '',
		'shipmethod' => false,
		'billing' => array(),
		'shipping' => array(),
		'info' => array(),
		'data' => array()
	);

	public function __construct () {

		Shopping::restore('confirmed',$this->confirmed);

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

	public function data () {

		if ( $this->form('data') )
			ShoppOrder()->data = $this->form('data');

	}

	public function customer () {

		$Customer = ShoppOrder()->Customer;

		// Update guest checkout flag
		$guest = Shopp::str_true($this->form('guest'));

		// Set the customer guest flag
		$Customer->session(ShoppCustomer::GUEST, apply_filters('shopp_guest_checkout', $guest));

		$this->Register->customer();

	}

	public function shipaddress () {

		$Cart = ShoppOrder()->Cart;
		$ShippingAddress = ShoppOrder()->Shipping;

		if ( ! $Cart->shipped() ) // Use blank shipping for non-Shipped orders
			return $ShippingAddress = new ShippingAddress();

		$this->Register->shipaddress();

		if ( $Cart->shipped() )
			do_action('shopp_update_destination');

	}

	/**
	 * Processes changes to the shipping method
	 *
	 * Handles changes to the shipping method outside of other
	 * checkout processes
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function shipmethod () {
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


	public function billaddress () {
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

	public function payment () {
		$Billing = ShoppOrder()->Billing;
		$Payments = ShoppOrder()->Payments;

		// Change the cardtype to the selected payment service option label
		$Billing->cardtype = $Payments->selected()->label;

		if ( ! $this->paycard() ) return;

		add_filter('shopp_validate_checkout', array('ShoppFormValidation', 'paycard'));

		$form = $this->form('billing');

		// If the card number is provided over a secure connection
		// Change the cart to operate in secure mode
		if ( ! empty($form['card']) && is_ssl() )
			ShoppShopping()->secured(true);

		// Sanitize the card number to ensure it only contains numbers
		if ( ! empty($form['card']) )
			$Billing->card = preg_replace('/[^\d]/', '', $form['card']);

		$form['cardexpires'] = sprintf('%02d%02d', $form['cardexpires-mm'], $form['cardexpires-yy']);

		if ( ! empty($form['cardexpires-mm']) && ! empty($form['cardexpires-yy'])) {
			$exmm = preg_replace('/[^\d]/', '', $form['cardexpires-mm']);
			$exyy = preg_replace('/[^\d]/', '', $form['cardexpires-yy']);
			$Billing->cardexpires = mktime(0,0,0,$exmm,1,($exyy)+2000);
		} else $Billing->cardexpires = 0;

		$Billing->cvv = preg_replace('/[^\d]/', '', $form['cvv']);

		// Extra card security check fields
		if ( ! empty($form['xcsc']) ) {
			$Billing->xcsc = array();
			foreach ( (array)$form['xcsc'] as $field => $value ) {
				$Billing->Billing->xcsc[] = $field;	// Add to the XCSC registry of fields
				$Billing->$field = $value;			// Add the property
			}
		}

	}

	/**
	 * Determine if payment card data has been submitted
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function paycard () {
		$fields = array('card','cardexpires-mm','cardexpires-yy','cvv');
		$billing = $this->form('billing');
		foreach ( $fields as $field )
			if ( isset($billing[ $field ]) ) return true;
		return false;
	}


	/*
	 * Checkout form processing
	 *
	 * Handles taking user input from the checkout form and
	 * processing the information into useable order data
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function process () {
		$Payments = ShoppOrder()->Payments;
		$Cart = ShoppOrder()->Cart;

		$forcedconfirm = 'always' == shopp_setting('order_confirmation');

		$action = $this->form('checkout');

		if ( ! $action || 'process' != $action) return;

		$wasfree = $Cart->orderisfree(); // Get current free status
		$estimated = $Cart->total();     // Get current total

		$Cart->totals(); // Retotal after checkout to capture order total changes

		// We have to avoid truthiness, hence the strange logic expression
		if ( true !== apply_filters('shopp_validate_checkout', true) ) return;
		else $this->customer(); // Catch changes from validation

		// Catch originally free orders that get extra (shipping) costs added to them
		if ( $wasfree && $Payments->count() > 1 && ! $Cart->orderisfree() && empty($Payments->selected()->cards) ) {
			shopp_add_error( __('The order amount changed and requires that you select a payment method.','Shopp') );
			Shopp::redirect( Shopp::url(false,'checkout', ShoppOrder()->security()) );
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
	 * Account registration processing
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 **/
	public function registration () {

		// Validation already conducted during the checkout process
        add_filter('shopp_validate_registration', '__return_true');

		// Prevent redirection to account page after registration
        add_filter('shopp_registration_redirect', '__return_false');

		ShoppRegistration::process();

	}

	/**
	 * Confirms the order and starts order processing
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function confirmed () {

		if ( 'confirmed' == $this->form('checkout') ) {
			$this->confirmed = true;
			do_action('shopp_process_order');
		}

	}

}
<?php
/**
 * Order.php
 *
 * Order controller that manages the relevant order objects
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
 * Order controller
 *
 * @author Jonathan Davis
 * @since 1.1
 * @version 1.3
 * @package order
 **/
class ShoppOrder {

	public $Customer  = false;			// The current customer
	public $Shipping  = false;			// The shipping address
	public $Billing   = false;			// The billing address
	public $Cart      = false;			// The shopping cart
	public $Tax       = false;			// The tax calculator
	public $Shiprates = false;			// The shipping service rates calculator
	public $Discounts = false;			// The discount manager

	public $Promotions = false;			// The promotions loader
	public $Payments   = false;			// The payments manager
	public $Checkout   = false;			// The checkout processor

	public $data        = array();		// Extra/custom order data
	public $sameaddress = false;		// Toggle for copying a primary address to the secondary address

	// Post processing properties
	public $inprogress = false;			// Generated purchase ID
	public $purchase   = false;			// Purchase ID of the finalized sale
	public $txnid      = false;			// The transaction ID reported by the gateway

	public $state;						// Checksum state of the items in the cart

	/**
	 * Order constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct () {

		$this->init();

		// Set locking timeout for concurrency operation protection
		if ( ! defined('SHOPP_TXNLOCK_TIMEOUT') ) define('SHOPP_TXNLOCK_TIMEOUT', 10);

		// Request handling
		add_action('parse_request', array($this, 'request'));
		add_action('parse_request', array($this->Discounts, 'requests'));


		// Address handling
		add_action('shopp_update_destination', array($this->Billing, 'fromshipping'));
		add_action('shopp_update_destination', array($this, 'taxlocale'));


		// Order processing
		add_action('shopp_process_order', array($this, 'freebie'), 7);
		add_action('shopp_process_order', array($this, 'validate'), 7);
		add_action('shopp_process_order', array($this, 'submit'), 100);

		// Creating the purchase order
		add_action('shopp_purchase_order_event', array($this, 'purchase'));
		add_action('shopp_purchase_order_created', array($this, 'meta'));
		add_action('shopp_purchase_order_created', array($this, 'invoice'));
		add_action('shopp_purchase_order_created', array($this, 'process'));

		// Handle authed transactions
		add_action('shopp_authed_order_event', array($this, 'unstock'));
		add_action('shopp_authed_order_event', array($this, 'captured'));

		// Ensure payment card PAN is truncated after successful processing
		add_action('shopp_authed_order_event', array($this, 'securecard'));

		// Session handling
		add_action('shopp_session_save', array($this, 'secure_session'));
		
		// Reset handlers
		add_action('shopp_resession', array($this, 'init'));
		add_action('shopp_pre_resession', array($this, 'clear'), 100);

		// Collect available payment methods from active gateways
		// Schedule for after the gateways are loaded (priority 20)
		add_action('shopp_init', array($this->Payments, 'options'), 20);

		// Process customer selected payment methods after gateways are loaded (priority 20)
		add_action('shopp_init', array($this->Payments, 'request'), 20);

		// Select the default gateway processor
		// Schedule for after the gateways are loaded (priority 20)
		add_action('shopp_init', array($this->Payments, 'selected'), 20);

		// Handle remote transaction processing (priority 20)
		// Needs to happen after the processor is selected in the session,
		// but before gateway-order specific handlers are established
		add_action('shopp_init', array($this, 'txnupdates'), 20);

		// Initialize/reinitalize the current location
		add_action('shopp_init', array($this, 'locate'), 20);

	}

	function init () {

		$this->Cart     = Shopping::restart( 'ShoppCart', $this->Cart );
		$this->Customer = Shopping::restart( 'ShoppCustomer', $this->Customer );

		$this->Billing  = Shopping::restart( 'BillingAddress', $this->Billing );
		$this->Shipping = Shopping::restart( 'ShippingAddress', $this->Shipping );
		$this->Tax      = Shopping::restart( 'ShoppTax', $this->Tax );

		$this->Shiprates = Shopping::restart( 'ShoppShiprates', $this->Shiprates );
		$this->Discounts = Shopping::restart( 'ShoppDiscounts', $this->Discounts );

		// Store order custom data and post processing data
		Shopping::restore('data', $this->data);
		Shopping::restore('sameaddress', $this->sameaddress);
		Shopping::restore('inprogress', $this->inprogress);
		Shopping::restore('purchase', $this->purchase);
		Shopping::restore('txnid', $this->txnid);
		Shopping::restore('orderstate', $this->state);

		$this->Promotions = new ShoppPromotions;
		$this->Payments   = new ShoppPayments;
		$this->Checkout   = new ShoppCheckout;

	}

	/**
	 * Handles checkout request flow control
	 *
	 * @author Jonathan Davis
	 * @since 1.2.3
	 *
	 * @return void
	 **/
	public function request () {

		// Check if an in progress order was already completed
		if ( ! empty($this->inprogress) ) {
			$Purchase = new ShoppPurchase($this->inprogress);
			if ( $Purchase->exists() && in_array($Purchase->txnstatus, array('authed', 'captured')) )
				return $this->success();
		}

		// Check if an in progress order processing (from another process/tab/window) completed
		if ( ! empty($_REQUEST['inprogress']) && ! empty($this->purchase) ) {
				$Purchase = new ShoppPurchase($this->purchase);
				if ( $Purchase->exists() ) // Verify it exists and redirect to thanks
					Shopp::redirect(Shopp::url(false, 'thanks'));
		}

		if ( ! empty($_REQUEST['rmtpay']) )
			return do_action('shopp_remote_payment');

		if ( array_key_exists('checkout', $_POST) ) {

			$checkout = strtolower($_POST['checkout']);
			switch ( $checkout ) {
				case 'process':		do_action('shopp_process_checkout'); break;
				case 'confirmed':	do_action('shopp_confirm_order'); break;
			}

		} elseif ( array_key_exists('shipmethod', $_POST) ) {

			do_action('shopp_process_shipmethod');

		}

	}

	/**
	 * Handles remote transaction update request flow control
	 *
	 * Moved from the Flow class in 1.2.3
	 *
	 * @author Jonathan Davis
	 * @since 1.2.3
	 *
	 * @return void
	 **/
	public function txnupdates () {

		if ( empty($_REQUEST['_txnupdate']) ) return;

		// Check for remote transaction update messages
		add_action('shopp_txn_update', function () {
			status_header('200');
			exit();
		}, 101); // Default shopp_txn_update requests to HTTP status 200
		do_action('shopp_txn_update');

	}

	/**
	 * Update addresses throughout the system
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function locate () {

		$request = isset($_REQUEST['shipping']) ? $_REQUEST['shipping'] : false;

		$this->Billing->locate($request);
		$this->Shipping->locate($request);

		do_action_ref_array( 'shopp_update_destination', array($request) );

	}

	/**
	 * Send the current taxable address to the Tax system
	 *
	 * @author Jonathan Davis
	 * @since 1.3.2
	 *
	 * @return void
	 **/
	public function taxlocale ( ) {

		$Address = $this->Billing;
		if ( $this->Cart->shipped() || shopp_setting_enabled('tax_destination') ) // @todo add setting for "Apply tax to the shipping address"
			$Address = $this->Shipping;

		// Locale is always tracked with the billing address even though it may be a shipping locale
		$locale = empty($this->Billing->locale) ? null : $this->Billing->locale;

		$this->Tax->location($Address->country, $Address->state, $locale); // Update the ShoppTax working location
		$this->Tax->customer($this->Customer);
	}

	/**
	 * Submits the order to create a Purchase record
	 *
	 * Uses WP Transients API to establish a lock on order creation for the session to
	 * prevent duplicate orders.
	 *
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function submit () {

		if ( ! $this->Payments->processor() ) return; // Don't do anything if there is no payment processor

		// Duplicate purchase prevention #3142
		$timeout = SHOPP_GATEWAY_TIMEOUT + 5;
		$lockid  = 'shopp_order_' . ShoppShopping()->session();
		if ( get_transient($lockid) ) {
			shopp_debug("Lock $lockid already established, waiting for other process to complete...");

			// Wait until the lock is cleared
			$waited = 0;
			while ( get_transient($lockid) && $waited++ < $timeout )
				sleep(1);

			shopp_debug("Lock $lockid process appears to have completed, redirecting...");

			// Otherwise an error must have occurred, bounce back to checkout
			Shopp::redirect(Shopp::url(array('inprogress' => '1'), 'checkout', $this->security()));
			return;

		} else {
			set_transient($lockid, true, $timeout);
			add_action('shutdown', function () use ( $lockid ) {
				delete_transient($lockid);
			} );
		}

		shopp_add_order_event(false, 'purchase', array(
			'gateway' => $this->Payments->processor()
		));

		delete_transient( $lockid ); // Remove the lock

	}

	/**
	 * Creates an invoice transaction event to setup the payment balance
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function invoice ( ShoppPurchase $Purchase ) {
		shopp_add_order_event($Purchase->id, 'invoiced', array(
			'gateway' => $Purchase->gateway,			// Gateway handler name (module name from @subpackage)
			'amount'  => $Purchase->total				// Capture of entire order amount
		));
	}

	/**
	 * Fires an unstock order event for a purchase to deduct stock from inventory
	 *
	 * @author Jonathan Davis
	 * @since 1.2.1
	 *
	 * @return void
	 **/
	function unstock ( AuthedOrderEvent $Event ) {
		if ( ! shopp_setting_enabled('inventory') ) return false;

		$Purchase = ShoppPurchase();
		if ( ! isset($Purchase->id) || empty($Purchase->id) || $Event->order != $Purchase->id )
			$Purchase = new ShoppPurchase($Event->order);

		if ( $Purchase->did('unstock') ) return true; // Unstock already occurred, do nothing

		$Purchase->load_purchased(); // Reload purchased to ensure we have inventory status
		if ( ! $Purchase->stocked ) return false;

		shopp_add_order_event($Purchase->id, 'unstock');
	}

	/**
	 * Marks an order as captured
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function captured ( $Event ) {

		if ( 'authed' == $Event->name ) {
			if ( ! isset($Event->capture) ) return;
			if ( ! $Event->capture ) return;
			$Event->fees = 0;
		}

		shopp_add_order_event($Event->order, 'captured', array(
			'txnid'   => $Event->txnid,				// Can be either the original transaction ID or an ID for this transaction
			'amount'  => $Event->amount,			// Capture of entire order amount
			'fees'    => $Event->fees,				// Transaction fees taken by the gateway net revenue = amount-fees
			'gateway' => $Event->gateway			// Gateway class name
		));

	}

	/**
	 * Order processing decides the type of transaction processing request to make
	 *
	 * Decides which processing operation to perform:
	 *     auth - Get authorization from the payment processor to charge the order amount
	 *     sale - Get authorization and immediate capture of the payment
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @param $Purchase
	 * @return void
	 */
	public function process ( $Purchase ) {

		// Authorize payments for shipped orders, use sale (auth+capture) for anything else
		$processing = $this->Cart->shipped() ? 'auth' : 'sale';

		$default = array($this, $processing);

		// Gateway modules can use 'shopp_purchase_order_gatewaymodule_processing' filter hook to override order processing
		// Return a string of 'auth' for auth processing, or 'sale' for sale processing
		// For advanced overrides, gateways can provide custom callbacks as a standard PHP object callback array: array($this,'customhandler')
		if ( ! empty($Purchase->gateway) ) {
			$processing = apply_filters('shopp_purchase_order_' . sanitize_key($Purchase->gateway) . '_processing', $processing, $Purchase);
			$processing = apply_filters('shopp_purchase_order_' . GatewayModules::hookname($Purchase->gateway) . '_processing', $processing, $Purchase);
		}

		// General order processing filter override
		$processing = apply_filters('shopp_purchase_order_processing', $processing, $Purchase);

		if ( is_string($processing) ) $callback = array($this, $processing);
		elseif ( is_array($processing) ) $callback = $processing;

		if ( ! is_callable($callback) ) $callback = $default;

		call_user_func($callback, $Purchase);

	}

	/**
	 * Sets up order events for Auth-only transaction processing
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function auth ( $Purchase ) {

		add_action('shopp_authed_order_event', array($this, 'accounts'));
		add_action('shopp_authed_order_event', array($this, 'notify'));
		add_action('shopp_authed_order_event', array($this, 'success'));

		shopp_add_order_event($Purchase->id,'auth',array(
			'gateway' => $Purchase->gateway,
			'amount'  => $Purchase->total
		));

	}

	/**
	 * Sets up order events for Auth-Capture "Sale" transaction processing
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function sale ( $Purchase ) {

		add_action('shopp_captured_order_event', array($this, 'accounts'));
		add_action('shopp_captured_order_event', array($this, 'notify'));
		add_action('shopp_captured_order_event', array($this, 'success'));

		shopp_add_order_event($Purchase->id, 'sale', array(
			'gateway' => $Purchase->gateway,
			'amount'  => $Purchase->total
		));

	}

	/**
	 * Handles processing free orders, overriding any configured gateways
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function freebie () {

		if ( ! $this->Cart->orderisfree() ) return;

		$this->Payments->free();
		$this->Billing->cardtype = Shopp::__('Free Order');

		do_action('shopp_process_free_order');

	}

	/**
	 * Converts a shopping session order to a Purchase record
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function purchase ( PurchaseOrderEvent $Event ) {

		$Shopping = ShoppShopping();
		$changed  = $this->changed();

		// No auth message, bail
		if ( empty($Event) ) {
			shopp_debug('Order failure: An empty order event message was received by the order processor.');
			return;
		}

		// Copy details from Auth message
		$this->txnstatus = $Event->name;
		$this->gateway = $Event->gateway;

		$paycard = Lookup::paycard($this->Billing->cardtype);
		$this->Billing->cardtype = ! $paycard ? $this->Billing->cardtype : $paycard->name;

		$Paymethod = $this->Payments->selected();
		$shipoption = $this->Shiprates->selected();

		if ( empty($this->inprogress) ) {
			// Create a new order
			$Purchase = new ShoppPurchase();
		} elseif ( empty(ShoppPurchase()->id) ) {
			// Handle updates to an existing order from checkout reprocessing
			$Purchase = new ShoppPurchase($this->inprogress);
		} else {
			// Update existing order
			$Purchase = ShoppPurchase();
		}

		// Capture early event transaction IDs
		if ( isset($Event->txnid) ) $Purchase->txnid = $this->txnid = $Event->txnid;

		$Purchase->order = 0;
		$Purchase->copydata($this);
		$Purchase->copydata($this->Customer);
		$Purchase->copydata($this->Billing);
		$Purchase->copydata($this->Shipping, 'ship');
		$Purchase->copydata($this->Cart->Totals->data());
		$Purchase->subtotal = $Purchase->order; // Remap order to subtotal
		$Purchase->paymethod = isset($Paymethod->slug) ? $Paymethod->slug : '';
		$Purchase->customer = $this->Customer->id;
		$Purchase->taxing = shopp_setting_enabled('tax_inclusive') ? 'inclusive' : 'exclusive';
		$Purchase->freight = $this->Cart->total('shipping');
		$Purchase->shipoption = isset($shipoption->name) ? $shipoption->name : '';
		$Purchase->ip         = $Shopping->ip;
		$Purchase->created    = current_time('mysql');

		unset($Purchase->order);
		$Purchase->save();

		// Catch Purchase record save errors
		if ( empty($Purchase->id) ) {
			shopp_add_error( Shopp::__('The order could not be created because of a technical problem on the server. Please try again, or contact the website administrator.') );
			return;
		}

		ShoppPurchase($Purchase);

		// Process the order events if updating an existing order
		if ( ! empty($this->inprogress) ) {

			if ( $changed ) { // The order has changed since the last order attempt

				// Rebuild purchased records from cart items
				$Purchase->delete_purchased();

				remove_action( 'shopp_order_event', array($Purchase, 'notifications') );

				// Void prior invoiced balance
				shopp_add_order_event($Purchase->id, 'voided', array(
					'txnorigin' => '', 'txnid' => '',
					'gateway' => $Purchase->gateway
				));

				// Recreate purchased records from the cart and re-invoice for the new order total
				$this->items($Purchase->id);
				$this->meta($Purchase);
				$this->invoice($Purchase);

				add_action( 'shopp_order_event', array($Purchase, 'notifications') );
			} elseif ( 'voided' == $Purchase->txnstatus ) {
				$this->invoice($Purchase); // Re-invoice cancelled orders that are still in-progress @see #1930
			} elseif ( $Purchase->did('invoiced') ) {
 				// Reset the order status to invoiced without invoicing it again @see #3301
 				$Purchase->txnstatus = 'invoiced';
 				$Purchase->save();
 			}

			$this->process($Purchase);
			return;
		}

		$this->items($Purchase->id);		// Create purchased records from the cart items

		$this->purchase = false; 			// Clear last purchase in prep for new purchase
		$this->inprogress = $Purchase->id;	// Keep track of the purchase record in progress for transaction updates

		shopp_debug('Purchase ' . $Purchase->id . ' was successfully saved to the database.');

		// Start the transaction processing events
		do_action('shopp_purchase_order_created', $Purchase);

	}

	/**
	 * Builds purchased records from cart items attached to the given Purchase ID
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @param int $purchaseid The Purchase id to attach the purchased records to
	 * @return void
	 **/
	public function items ( $purchaseid ) {
		foreach( $this->Cart as $Item ) {	// Build purchased records from cart items
			$Purchased = new ShoppPurchased();
			$Purchased->purchase = $purchaseid;
			$Purchased->copydata($Item);
			$Purchased->save();
		}
		$this->checksum = $this->Cart->checksum;	// Track the cart contents checksum to detect changes.
	}

	public function meta ( ShoppPurchase $Purchase ) {
		// Save the discounts applied
		if ( $this->Discounts->count() > 0 )
			$Purchase->discounts($this->Discounts);

		// Save the taxes applied
		$TotalTaxes = $this->Cart->Totals->entry('tax');
		if ( ! empty($TotalTaxes) )
			$Purchase->taxes($TotalTaxes);

		// Save new user registration with order
		if ( ! $this->Customer->exists() )
			$Purchase->registration($this->Customer, $this->Billing, $this->Shipping);
	}

	/**
	 * Detect changes to the order (in the cart)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if the order changed from the last time
	 **/
	public function changed () {
		$before = $this->state;
		$this->state = $this->Cart->state( $before );
		return ( $before != $this->state );
	}

	/**
	 * Creates a customer record (and WordPress user) and attaches the order to it
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function accounts ( $Event ) {

		$Purchase = $Event->order();
		if ( ! $Purchase ) {
			shopp_debug('No purchase available for account registration processing.');
			return;
		}

		if ( ! $this->Customer->exists() ) {

			$registration = $Purchase->registration();
			if ( empty($registration) ) {
				shopp_debug('No purchase registration data available for account registration processing.');
				return;
			}

			$this->Customer->copydata($registration['Customer']);
			$this->Billing->copydata($registration['Billing']);
			$this->Shipping->copydata($registration['Shipping']);

			// Validation already conducted during the checkout process
	        add_filter('shopp_validate_registration', '__return_true');

			// Prevent redirection to account page after registration
	        add_filter('shopp_registration_redirect', '__return_false');

		}

		ShoppRegistration::process();

		// Update Purchase with link to created customer record
		if ( ! empty($this->Customer->id) ) {
			$Purchase = ShoppPurchase();

			if ($Purchase->id != $Event->order)
				$Purchase = new ShoppPurchase($Event->order);

			$Purchase->customer = $this->Customer->id;
			$Purchase->billing  = $this->Billing->id;
			$Purchase->shipping = $this->Shipping->id;
			$Purchase->save();
		}

	}

	/**
	 * Send out new order notifications
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function notify ( $Event ) {

		$Purchase = $Event->order();
		if ( ! $Purchase ) return;

		do_action('shopp_order_notifications', $Purchase);

	}

	/**
	 * Resets the session and redirects to the thank you page
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function success () {

		if ( ! empty($this->inprogress) ) {

			$this->purchase = $this->inprogress;
			ShoppPurchase(new ShoppPurchase($this->purchase));
			$this->inprogress = false;

			// Remove the order processing lock
			delete_transient( 'shopp_order_' . ShoppShopping()->session() );

			do_action('shopp_order_success', ShoppPurchase());

			Shopping::resession();
		}

		if ( false !== $this->purchase )
			Shopp::redirect( Shopp::url(false, 'thanks') );

	}

	/**
	 * Validates an order prior to submitting for payment processing
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return boolean True if the order is ready for processing, or void with redirect back to checkout
	 **/
	public function validate () {
		if ( apply_filters('shopp_valid_order', $this->isvalid()) ) return true;

		$redirect = Shopp::url(false, 'checkout', $this->security());
		Shopp::redirect( apply_filters('shopp_invalid_order_redirect', $redirect), true );
	}

	/**
	 * Validate order data before transaction processing
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean Validity of the order
	 **/
	public function isvalid ( $report = true ) {

		$Customer  = $this->Customer;
		$Shipping  = $this->Shipping;
		$Shiprates = $this->Shiprates;
		$Payments  = $this->Payments;
		$Cart      = $this->Cart;

		$valid    = true;
		$errlevel = $report ? SHOPP_TRXN_ERR : SHOPP_DEBUG_ERR;

		shopp_debug('Validating order data for processing');

		if ( 0 == $Cart->count() ) {
			$valid = apply_filters('shopp_ordering_empty_cart', false);
			shopp_add_error(Shopp::__('There are no items in the cart.'), $errlevel);
		}

		$stock = true;
		foreach ( $Cart as $item ) {
			if ( ! $item->instock() ){
				$valid = apply_filters('shopp_ordering_items_outofstock', false);
				shopp_add_error( Shopp::__('%s does not have sufficient stock to process order.',
					$item->name . ( empty($item->option->label) ? '' : '(' . $item->option->label . ')' )
				), $errlevel);
				$stock = false;
			}
		}

		$valid_customer = true;
		if ( ! $Customer ) $valid_customer = apply_filters('shopp_ordering_empty_customer', false); // No Customer

		// Always require name and email
		if ( empty($Customer->firstname) ) $valid_customer = apply_filters('shopp_ordering_empty_firstname', false);
		if ( empty($Customer->lastname) ) $valid_customer = apply_filters('shopp_ordering_empty_lastname', false);
		if ( empty($Customer->email) ) $valid_customer = apply_filters('shopp_ordering_empty_email', false);

		if ( ! $valid_customer ) {
			$valid = false;
			shopp_add_error(Shopp::__('There is not enough customer information to process the order.'), $errlevel);
		}

		// Check for shipped items but no Shipping information
		$valid_shipping = true;
		if ( $Cart->shipped() && shopp_setting_enabled('shipping') ) {
			if ( empty($Shipping->address) )
				$valid_shipping = apply_filters('shopp_ordering_empty_shipping_address', false);
			if ( empty($Shipping->country) )
				$valid_shipping = apply_filters('shopp_ordering_empty_shipping_country', false);
			if ( empty($Shipping->postcode) )
				$valid_shipping = apply_filters('shopp_ordering_empty_shipping_postcode', false);

			if ( $Shiprates->count() == 0 && ! $Shiprates->free() ) {
				$valid = apply_filters('shopp_ordering_no_shipping_costs',false);

				$message = Shopp::__('The order cannot be processed. No shipping is available to the address you provided. Please return to %scheckout%s and try again.', '<a href="' . shopp('checkout.get-url') . '">', '</a>');

				if ( $Shiprates->realtime() )
					$message = Shopp::__('The order cannot be processed. The shipping rate service did not provide rates because of a problem and no other shipping is available to the address you provided. Please return to %scheckout%s and try again or contact the store administrator.');

				if ( ! $valid ) shopp_add_error( sprintf($message, '<a href="' . Shopp::url(false, 'checkout', $this->security()) . '">', '</a>'), $errlevel );
			}

		}

		if ( ! $valid_shipping ) {
			$valid = false;
			shopp_add_error(Shopp::__('The shipping address information is incomplete. The order cannot be processed.'), $errlevel);
		}

		// Alert when no gateway is configured (and the order is not free)
		if ( $Payments->count() == 0 && $Cart->total() > 0 ) {
			$valid = false;
			shopp_add_error( Lookup::errors('gateway', 'nogateways'), $errlevel);
		}

		return $valid;
	}

	/**
	 * Evaluates if checkout process needs to be secured
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean Whether the checkout form should be secured
	 **/
	public function security () {
		return $this->Payments->secure() || is_ssl();
	}

	/**
	 * Secures the payment card by removing it from the session
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function securecard () {
		$this->Billing->cardtype = '';
		$this->Billing->card = '';
		$this->Billing->cvv = '';
		$this->Billing->cardexpires = 0;
		$this->Billing->cardholder = '';
		
		if ( ! empty( $this->Billing->xcsc ) ) {
			foreach( $this->Billing->xcsc as $field ) {
				unset( $this->Billing->$field );
			}
			unset( $this->Billing->xcsc ); 
		}
		
		// Card data is gone, switch the cart to normal mode
		ShoppShopping()->secured(false);
	}

	/**
	 * Provides the currently selected payment method
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @deprecated ShoppOrder::paymethod(); Use ShoppOrder()->Payments->selected()
	 * @return ShoppPaymentOption The selected payment option
	 **/
	public function paymethod () {
		return $this->Payments->selected();
	}

	/**
	 * Clear order-specific information to prepare for a new order
	 *
	 * @author Jonathan Davis
	 * @since 1.1.5
	 *
	 * @return void
	 **/
	public function clear () {

		$this->Cart->clear();
		$this->Discounts->clear();
		$this->Promotions->clear();
		$this->Shiprates->clear();

		$this->data       = array();
		$this->inprogress = false;
		$this->txnid      = false;

	}

	/**
	 * Secure session if secure information is in the order
	 *
	 * @author Matthew Sigley
	 * @since 1.3.12
	 *
	 * @return void
	 **/
	public function secure_session () {
		$Shopping = ShoppShopping();

		if ( $Shopping->secured() )
			return;
		
		if ( ! empty($this->Billing->cardtype) 
			|| ! empty($this->Billing->card) 
			|| ! empty($this->Billing->cvv) 
			|| ! empty($this->Billing->cardexpires) 
			|| ! empty($this->Billing->cardholder)
			|| ! empty($this->Billing->xcsc) )
				$Shopping->secured(true);
	}
}

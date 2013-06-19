<?php
/**
 * PayPal Standard
 * @class PayPalStandard
 *
 * @author Jonathan Davis, John Dillick
 * @version 1.2.2
 * @copyright Ingenesis Limited, 27 May, 2009
 * @package Shopp
 * @since 1.2
 * @subpackage PayPalStandard
 *
 * $Id$
 **/

class PayPalStandard extends GatewayFramework implements GatewayModule {

	// Settings
	var $secure = false; // do not require SSL or session encryption
	var $saleonly = true; // force sale event on processing (no auth)
	var $recurring = true; // support for recurring payment

	// URLs
	var $buttonurl = 'http://www.paypal.com/%s/i/btn/btn_xpressCheckout.gif';
	var $sandboxurl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	var $checkouturl = 'https://www.paypal.com/cgi-bin/webscr';

	// Internals
	var $baseop = array();
	var $currencies = array("USD", "AUD", "BRL", "CAD", "CZK", "DKK", "EUR", "HKD", "HUF",
	 						"ILS", "JPY", "MYR", "MXN", "NOK", "NZD", "PHP", "PLN", "GBP",
	 						"SGD", "SEK", "CHF", "TWD", "THB");
	var $locales = array("AT" => "de_DE", "AU" => "en_AU", "BE" => "en_US", "CA" => "en_US",
							"CH" => "de_DE", "CN" => "zh_CN", "DE" => "de_DE", "ES" => "es_ES",
							"FR" => "fr_FR", "GB" => "en_GB", "GF" => "fr_FR", "GI" => "en_US",
							"GP" => "fr_FR", "IE" => "en_US", "IT" => "it_IT", "JP" => "ja_JP",
							"MQ" => "fr_FR", "NL" => "nl_NL", "PL" => "pl_PL", "RE" => "fr_FR",
							"US" => "en_US");
	// status to event mapping
	var $events = array(
		'Voided' => 'voided',
		'Denied' => 'voided',
		'Expired' => 'voided',
		'Failed' => 'voided',
		'Refunded' => 'refunded',
		'Reversed' => 'refunded',
		'Canceled_Reversal' => 'captured',
		'Canceled-Reversal' => 'captured',
		'Completed' => 'captured',
		'Pending' => 'purchase',
		'Processed' => 'purchase',
		);


	var $pending_reasons = array();
	var $eligibility = array();
	var $reversals = array();
	var $txn_types = array();

	function __construct () {
		parent::__construct();

		$this->setup('account','pdtverify','pdttoken','testmode');

		$this->settings['currency_code'] = $this->currencies[0];
		if (in_array($this->baseop['currency']['code'],$this->currencies))
			$this->settings['currency_code'] = $this->baseop['currency']['code'];

		if (array_key_exists($this->baseop['country'],$this->locales))
			$this->settings['locale'] = $this->locales[$this->baseop['country']];
		else $this->settings['locale'] = $this->locales['US'];

		$this->buttonurl = sprintf(force_ssl($this->buttonurl), $this->settings['locale']);

		if (!isset($this->settings['label'])) $this->settings['label'] = "PayPal";

		$this->pending_reasons = array(
			'address' => __('The customer did not include a confirmed shipping address.', 'Shopp'),
			'echeck' => __('The eCheck that has not yet cleared', 'Shopp'),
			'intl' => __('You must manually accept or deny transactions for your non-US account.', 'Shopp'),
			'multi-currency' => __('You must manually accept or deny a transaction in this currency.', 'Shopp'),
			'order' => __('You set the payment action to Order and have not yet captured funds.', 'Shopp'),
			'paymentreview' => __('The payment is pending while it is being reviewed by PayPal for risk.', 'Shopp'),
			'unilateral' => __('The payment is pending because it was made to an email address that is not yet registered or confirmed.', 'Shopp'),
			'upgrade' => __('Contact PayPal Customer Service to see if your account needs to be upgraded.', 'Shopp'),
			'verify' => __('Your account is not yet verified.', 'Shopp'),
			'other' => __('Contact PayPal Customer Service to determine why payment was not completed.', 'Shopp'),
		);

		$this->eligibility = array(
			'ExpandedSellerProtection' => __("Eligible for PayPal’s Expanded Seller Protection",'Shopp'),
			'SellerProtection' => __("Eligible for PayPal’s Seller Protection", 'Shopp'),
			'None' => __("Not Eligible for PayPal’s Seller Protection", 'Shopp')
		);

		$this->reversals = array(
			'adjustment_reversal' => __("Reversal of an adjustment", 'Shopp'),
			'buyer-complaint' => __("Reversal on customer complaint.", 'Shopp'),
			'buyer_complaint' => __("Reversal on customer complaint.", 'Shopp'),
			'chargeback' => __("Reversal on chargeback.", 'Shopp'),
			'chargeback_reimbursement' => __("Reimbursement for a chargeback", 'Shopp'),
			'chargeback_settlement' => __("Settlement of a chargeback", 'Shopp'),
			'guarantee' => __("Reversal due to a money-back guarantee.", 'Shopp'),
			'other' => __("Non-specified reversal.", 'Shopp'),
			'refund' => __("Reversal by merchant refund.", 'Shopp'),
		);

		$this->txn_types = array(
			'chargeback' => __('A credit card chargeback has occurred', 'Shopp'),
			'adjustment' => __('A dispute has been resolved and closed', 'Shopp'),
			'cart' => false,
			'new_case' => __('A payment dispute has been filed.','Shopp'),
			'recurring_payment' => __('Recurring payment received','Shopp'),
			'recurring_payment_expired' => __('Recurring payment expired','Shopp'),
			'recurring_payment_profile_created' => __('Recurring payment profile created','Shopp'),
			'recurring_payment_skipped' => __('Recurring payment skipped','Shopp'),
			'subscr_cancel' => __('Subscription canceled','Shopp'),
			'subscr_eot' => __('Subscription expired','Shopp'),
			'subscr_failed' => __('Subscription signup failed','Shopp'),
			'subscr_payment' => __('Subscription payment received','Shopp'),
			'subscr_signup' => __('Subscription started','Shopp')
		);

		add_filter('shopp_themeapi_cart_paypal',array($this,'sendcart'),10,2); // provides shopp('cart','paypal') checkout button
		add_filter('shopp_checkout_submit_button',array($this,'submit'),10,3); // replace submit button with paypal image

		// request handlers
		add_action('shopp_remote_payment',array($this,'remote')); // process sync return from PayPal
		add_action('shopp_txn_update',array($this,'ipn')); // process IPN

		// order event handlers
		add_action('shopp_paypalstandard_sale', array($this,'sale'));
	}

	/**
	 * actions
	 *
	 * These action callbacks are only established when the current Order::processor() is set to this module.
	 * All other general actions belong in the constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function actions () {
		add_action('shopp_order_confirm_needed', array($this,'force_confirm'),9); // intercept checkout request, force confirm
		add_action('shopp_init_confirmation',array($this,'confirmation')); // replace confirm order page with paypal form
		add_action('template_redirect',array($this,'returned')); // wipes shopping session on thanks page load
	}


	// ORDER EVENT HANDLER

	/**
	 * sale
	 *
	 * the shopp_paypalstandard_sale event handler, responsible for issuing authed event (with or without capture)
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return void
	 **/
	function sale ( $Event ) {
		// check payer_status
		if ( isset($this->response->payer_status) ) {
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'payer_status',
				'note' => $this->response->payer_status
			));
		}

		// check pending_reason
		if ( isset($this->response->pending_reason) ) {
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'pending_reasons',
				'note' => (
					in_array( $this->response->pending_reason, array_keys( $this->pending_reasons ) ) ?
					$this->pending_reasons[$this->response->pending_reason] :
					$this->pending_reasons['other']
					)
			));
		}

		// check protection_eligibility
		if ( isset($this->response->protection_eligibility) ) {
			$eligibility = $this->response->protection_eligibility;
			if ( in_array($this->response->protection_eligibility, array_keys($this->eligibility)) ) {
				$eligibility = $this->eligibility[$this->response->protection_eligibility];
			}
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'protection_eligibility',
				'note' => $eligibility
			));
		}

		$Paymethod = $this->Order->paymethod();
		$Billing = $this->Order->Billing;

		$message = array(
			'txnid' => $this->response->txnid,						// Transaction ID
			'amount' => $this->response->amount,					// Gross amount authorized
			'gateway' => $this->module,								// Gateway handler name (module name from @subpackage)
			'paymethod' => $Paymethod->label,						// Payment method (payment method label from payment settings)
			'paytype' => $Billing->cardtype,						// Type of payment (check, MasterCard, etc)
			'payid' => $Billing->card,								// Payment ID (last 4 of card or check number)
			'capture' => ( 'captured' == $this->response->event )	// Capture flag
		);
		if ( 'captured' == $this->response->event && isset($this->response->fees) )
			$message['fees'] = $this->response->fees;

		shopp_add_order_event($Event->order, 'authed', $message);

	}

	/**
	 * captured
	 *
	 * handled notification from PayPal indicating that payment has been captured.  Payment was previously pending.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return void
	 **/
	function captured ( $Purchase ) {
		$Purchase->load_events();
		if ( ! ($Purchase->balance > 0) ) return; // no more to capture

		// check for reason_code
		if ( isset($this->response->reason_code) ) {
			// $this->reversals
			shopp_add_order_event( $Purchase->order, 'review', array(
				'kind' => 'reason',
				'note' => (
					in_array( $this->response->reason_code, array_keys( $this->reversals ) ) ?
					$this->reversals[$this->response->reason_code] :
					$this->reversals['other']
					)
			));
		}

		shopp_add_order_event($Purchase->id, 'captured', array(
			'txnid' => $this->response->txnid,		// Transaction ID of the CAPTURE event
			'amount' => $this->response->amount,	// Amount captured
			'fees' => $this->response->fees,		// Transaction fees taken by the gateway net revenue = amount-fees
			'gateway' => $this->module				// Gateway handler name (module name from @subpackage)
		));
	}

	/**
	 * refunded
	 *
	 * handled notification from PayPal indicating that refund has been issued.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return void
	 **/
	function refunded ( $Purchase ) {
		if ( $Purchase->refunded == $Purchase->total ) return; // no more refunds

		// check for reason_code
		if ( isset($this->response->reason_code) ) {
			// $this->reversals
			shopp_add_order_event( $Purchase->id, 'review', array(
				'kind' => 'reason',
				'note' => (
					in_array( $this->response->reason_code, array_keys( $this->reversals ) ) ?
					$this->reversals[$this->response->reason_code] :
					$this->reversals['other']
					)
			));
		}

		shopp_add_order_event($Purchase->id, 'refunded', array(
			'txnid' => $this->response->txnid,		// Transaction ID for the REFUND event
			'amount' => $this->response->amount,	// Amount refunded
			'gateway' => $this->module				// Gateway handler name (module name from @subpackage)
		));
	}


	function voided ( $Purchase ) {
		if ( $Purchase->isvoid() ) return; // already voided

		shopp_add_order_event($Purchase->id, 'voided', array(
			'txnid' => $this->response->new_txnid,	// Transaction ID
			'txnorigin' => $this->response->txnid,	// Original Transaction ID
			'gateway' => $this->module				// Gateway handler name (module name from @subpackage)
		));
	}

	/**
	 * confirmation
	 *
	 * replaces the confirm order form to submit cart to PPS
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function confirmation () {
		add_filter('shopp_confirm_url',array($this,'url'));
		add_filter('shopp_confirm_form',array($this,'form'));
		add_filter('shopp_themeapi_checkout_confirmbutton',array($this,'confirm'),10,3); // replace submit button with paypal image
	}

	/**
	 * force_confirm
	 *
	 * forces the checkout request to go to order confirmation so that the confirm order form can be replaced for PPS
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function force_confirm ( $confirm ) {
		$this->Order->Billing->cardtype = "PayPal";
		$this->Order->confirm = true;
		return true;
	}

	/**
	 * submit
	 *
	 * replaces the submit button the checkout form with a PayPal checkout button image
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array The modified list of button tags
	 **/
	function submit ($tag=false,$options=array(),$attrs=array()) {
		$tag[$this->settings['label']] = '<input type="image" name="process" src="'.$this->buttonurl.'" class="checkout-button" '.inputattrs($options,$attrs).' />';
		return $tag;
	}

	/**
	 * Replaces the confirm button with the PayPal checkout button image
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	function confirm ($tag=false,$options=array(),$attrs=array()) {
		return join('', $this->submit(array(),$options,$attrs));
	}

	/**
	 * url
	 *
	 * url returns the live or test paypal url, depending on testmode setting
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string checkout url
	 **/
	function url ($url=false) {
		if ($this->settings['testmode'] == "on") return $this->sandboxurl;
		else return $this->checkouturl;
	}

	/**
	 * sendcart
	 *
	 * builds a form appropriate for sending to PayPal directly from the cart.. used by shopp('cart','paypal')
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string PayPal cart form
	 **/
	function sendcart () {
		$result = '<form action="'.$this->url().'" method="POST">';
		$result .= $this->form('',array('address_override'=>0));
		$result .= $this->submit();
		$result .= '</form>';
		return $result;
	}

	/**
	 * form
	 *
	 * Builds a hidden form to submit to PayPal when confirming the order for processing
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return string PayPal cart form contents
	 **/
	function form ($form,$options=array()) {
		$Shopping = ShoppShopping();
		$Order = ShoppOrder();
		$Customer = $Order->Customer;

		$_ = array();

		$_['cmd'] 					= "_cart";
		$_['upload'] 				= 1;
		$_['business']				= $this->settings['account'];
		$_['invoice']				= time();
		$_['custom']				= $Shopping->session;

		// Options
		if ( str_true($this->settings['pdtverify']) )
			$_['return']			= apply_filters( 'shopp_paypalstandard_returnurl', shoppurl(array('rmtpay'=>'PPS','utm_nooverride'=>'1'),'checkout',false) );
		else $_['return']				= shoppurl(false,'thanks');

		$_['cancel_return']			= shoppurl(false,'cart');
		$_['notify_url']			= shoppurl(array('_txnupdate'=>'PPS'),'checkout');
		$_['rm']					= 1; // Return with no transaction data

		// Pre-populate PayPal Checkout
		$_['lc']					= $this->baseop['country'];
		$_['charset']				= 'utf-8';
		$_['bn']					= 'shopplugin.net[WPS]';

		$_['first_name']			= $Customer->firstname;
		$_['last_name']				= $Customer->lastname;

		$AddressType = 'Shipping';
		// Disable shipping fields if no shipped items in cart
		if (empty($Order->Cart->shipped)) {
			$AddressType = 'Billing';
			$_['no_shipping'] 		= 1;
		}
		$Address = $Order->$AddressType;

		if (!empty($Order->Cart->shipped)) {
			$shipname = explode(' ',$Address->name);
			$_['first_name'] = array_shift($shipname);
			$_['last_name'] = join(' ',$shipname);
		}

		$_['address_override'] 		= 1;

		$_['address1']				= $Address->address;
		if (!empty($Address->xaddress))
			$_['address2']			= $Address->xaddress;
		$_['city']					= $Address->city;
		$_['state']					= $Address->state;
		$_['zip']					= $Address->postcode;
		$_['country']				= $Address->country;
		$_['email']					= $Customer->email;

		$phone = parse_phone($Order->Customer->phone);
		if ( in_array($Order->Billing->country,array('US','CA')) ) {
			$_['night_phone_a']			= $phone['area'];
			$_['night_phone_b']			= $phone['prefix'];
			$_['night_phone_c']			= $phone['exchange'];
		} else $_['night_phone_b']		= $phone['raw'];

		// Include page style option, if provided
		if (isset($_GET['pagestyle'])) $_['pagestyle'] = $_GET['pagestyle'];

		// Transaction
		$_['currency_code']			= $this->settings['currency_code'];

		// Recurring Non-Free Item
		if ( $Order->Cart->recurring() && $Order->Cart->recurring[0]->unitprice > 0 ) {
			$tranges = array(
				'D'=>array('min'=>1,'max'=>90),
				'W'=>array('min'=>1,'max'=>52),
				'M'=>array('min'=>1,'max'=>24),
				'Y'=>array('min'=>1,'max'=>5),
				);

			$Item = $Order->Cart->recurring[0];

			$recurring = $Item->recurring();
			$recurring['period'] = strtoupper($recurring['period']);

			//normalize recurring interval
			$recurring['interval'] = min(max($recurring['interval'], $tranges[$recurring['period']]['min']), $tranges[$recurring['period']]['max']);

			$_['cmd']	= '_xclick-subscriptions';
			$_['rm']	= 2; // Return with transaction data

			$_['item_number'] = $Item->product;
			$_['item_name'] = $Item->name.((!empty($Item->option->label))?' ('.$Item->option->label.')':'');

			// Trial pricing
			if ( $Item->has_trial() ) {
				$trial = $Item->trial();
				$trial['period'] = strtoupper($trial['period']);

				// normalize trial interval
				$trial['interval'] = min(max($trial['interval'], $tranges[$trial['period']]['min']), $tranges[$trial['period']]['max']);

				$_['a1']	= $this->amount($trial['price']);
				$_['p1']	= $trial['interval'];
				$_['t1']	= $trial['period'];
			}
			$_['a3']	= $this->amount($Item->subprice);
			$_['p3']	= $recurring['interval'];
			$_['t3']	= $recurring['period'];

			$_['src']	= 1;

			if ( $recurring['cycles'] ) $_['srt'] = (int) $recurring['cycles'];

		} else {

			// Line Items
			foreach($Order->Cart->contents as $i => $Item) {
				$id=$i++;
				$_['item_number_'.$id]		= $id;
				$_['item_name_'.$id]		= $Item->name.((!empty($Item->option->label))?' '.$Item->option->label:'');
				$_['amount_'.$id]			= $this->amount($Item->unitprice);
				$_['quantity_'.$id]			= $Item->quantity;
				// $_['weight_'.$id]			= $Item->quantity;
			}

			// Workaround a PayPal limitation of not correctly handling no subtotals or
			// handling discounts in the amount of the item subtotals by adding the
			// shipping fee to the line items to get included in the subtotal. If no
			// shipping fee is available use 0.01 to satisfy minimum order amount requirements
			// Additionally, this condition should only be possible when using the shopp('cart','paypal')
			// Theme API tag which would circumvent normal checkout and use PayPal even for free orders
			if ((float)$this->amount('subtotal') == 0 || (float)$this->amount('subtotal')-(float)$this->amount('discount') == 0) {
				$id++;
				$_['item_number_'.$id]		= $id;
				$_['item_name_'.$id]		= apply_filters('paypal_freeorder_handling_label',
															__('Shipping & Handling','Shopp'));
				$_['amount_'.$id]			= $this->amount( max((float)$this->amount('shipping'),0.01) );
				$_['quantity_'.$id]			= 1;
			} else
				$_['handling_cart']			= $this->amount('shipping');

			$_['discount_amount_cart'] 		= $this->amount('discount');
			$_['tax_cart']					= $this->amount('tax');
			$_['amount']					= $this->amount('total');

		}

		$_ = apply_filters('shopp_paypal_standard_form', array_merge($_, $options));

		return $form.$this->format($_);
	}

	/**
	 * pdtpassthru
	 *
	 * If order data validation fails, causes redirect to thank you page
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return bool valid order
	 **/
	function pdtpassthru ($valid) {
		if ($valid) return $valid;
		// If the order data validation fails, passthru to the thank you page
		shopp_redirect( shoppurl(false,'thanks') );
	}

	/**
	 * returned
	 *
	 * resets shopping session in preparation for loading thanks page
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function returned () {
		if ( ! is_thanks_page() ) return;

		// Session has already been reset after a processed transaction
		if ( ! empty(ShoppPurchase()->id) ) return;

		// Customer returned from PayPal
		// but no transaction processed yet
		// reset the session to preserve original order
		Shopping::resession();

	}

	/**
	 * remote
	 *
	 * handles the synchronous return from PPS
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return void
	 **/
	function remote () {
		if ( 'PPS' != $_REQUEST['rmtpay'] || ! isset($_REQUEST['tx']) ) return; // not PDT message

		shopp_debug('Processing PDT packet: '._object_r($_REQUEST));

		// Verify the message is authentically from PayPal
		$authentic = false;
		if (str_true($this->settings['pdtverify'])) {
			$pdtstatus = $this->verifypdt();
			if (!$pdtstatus) {
				new ShoppError(__('The transaction was not verified by PayPal.','Shopp'),false,SHOPP_DEBUG_ERR);
				shopp_redirect(shoppurl(false,'checkout',false));
			}
			$authentic = true;
		}

		// Parse the message
		$message = array(
			'amt' => 0,		// Amount of the transaction
			'cc' => '',		// Currency code
			'cm' => '', 	// Custom message
			'sig' => '', 	// Not documented
			'st' => '',		// Transaction status
			'tx' => ''		// Transaction ID/PDT token
		);
		$message = array_intersect_key($_GET,$message);
		extract($message);

		// Attempt to load a previous order from the transaction ID
		// This can happen when IPN async messages are received before
		// the customer returns to the storefront
		$Purchase = new Purchase($tx,'txnid');

		// create new purchase on PDT if necessary
		if ( empty($Purchase->id) ) {
			$event = isset($this->events[$st])?$this->events[$st]:'purchase';
			if ( $event == 'voided') return; // the transaction is void of the starting gate. Don't create a purchase.

			// PDT data into response object

			// build response object
			$this->response = new stdClass;
			$this->response->status = $st;
			$this->response->event = $event;
			$this->response->txnid = $tx;
			$this->response->fees = 0;
			$this->response->amount = abs($amt);
			// if ( isset($_POST['payer_status']) ) $this->response->payer_status = ( 'verified' == $_POST['payer_status'] ? __('Payer verified', 'Shopp') : __('Payer unverified', 'Shopp') );
			// if ( isset($_POST['pending_reason']) ) $this->response->pending_reason = $_POST['pending_reason'];
			// if ( isset($_POST['protection_eligibility']) ) $this->response->protection_eligibility = $_POST['protection_eligibility'];
			// if ( isset($_POST['reason_code']) ) $this->response->reason_code = $_POST['reason_code'];

			shopp_debug('PDT to response protocol: '._object_r($this->response));

			// only permit purchase creation on unathenticated status
			if ( ! $authentic ) {
				shopp_debug('PDT response was not authenticated.  Downgrading status to Pending.');
				$this->response->status = 'Pending';
				$this->response->event = 'purchase';
			}

			shopp_add_order_event(false, 'purchase', array(
				'gateway' => $this->module,
				'txnid' => $tx
			));

			return; // end after purchase creation
		}

		ShoppOrder()->purchase = $Purchase->id;
		shopp_redirect(shoppurl(false,'thanks',false));
	}

	/**
	 * Updates purchase records from an IPN message
	 *
	 * @author Jonathan Davis, John Dillick
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return void
	 **/
	function ipn () {

		// Not an IPN for PPS
		if ( 'PPS' != $_REQUEST['_txnupdate'] ) return;

		// chargeback types vary
		if ( isset($_POST['txn_type']) && false !== strpos(strtolower($_POST['txn_type']), 'chargeback') ) $_POST['txn_type'] = 'chargeback';


		// Cancel processing if this is not a PayPal IPN message (invalid)
		if ( ! isset($_POST['txn_type']) || ! in_array($_POST['txn_type'], array_keys($this->txn_types)) ) {
			shopp_debug('Not a PayPal IPN message. Missing or invalid txn_type.');
			return false;
		}

		$txnid = false;
		// if no parent transaction id, this is a new transaction
		if ( isset($_POST['txn_id']) && ! isset($_POST['parent_txn_id']) ) {
			$txnid = $_POST['txn_id'];
		// if a parent transaction id exists, this is associated with our existing purchase
		} elseif ( ! empty($_POST['parent_txn_id']) ) {
			$txnid = $_POST['parent_txn_id'];
		}

		$event = 'purchase';
		$txnstatus = $_POST['payment_status'];
		if ( $txnstatus && isset($this->events[$txnstatus]) )
			$event = $this->events[$txnstatus];

		// No transaction target: invalid IPN, silently ignore the message
		if ( ! $txnid ) {
			shopp_debug("Invalid IPN request.  Missing txn_id or parent_txn_id.");
			return;
		}

		// Validate the order notification
		if ( ! $this->verifyipn() ) {
			new ShoppError(sprintf(__('An unverifiable order update notification was received from PayPal for transaction: %s. Possible fraudulent notification!  The order will not be updated.  IPN message: %s','Shopp'),$txnid,_object_r($_POST)),'paypal_txn_verification',SHOPP_TRXN_ERR);
			return false;
		}
		shopp_debug('IPN: '._object_r($_POST));

		// IPN data into response object
		$fees = 0;
		$amount = 0;
		if ( isset($_POST['mc_fee']) ) $fees = abs($_POST['mc_fee']);
		$amount = isset($_POST['mc_gross']) ? abs($_POST['mc_gross']) : $Purchase->total;

		// build response object
		$this->response = new stdClass;
		$this->response->txn_type = $_POST['txn_type'];
		$this->response->status = $txnstatus;
		$this->response->event = $event;
		$this->response->txnid = $txnid;
		$this->response->fees = $fees;
		$this->response->amount = $amount;

		if ( in_array($event,array('captured','voided')) ) {
			$new_txnid = isset($_POST['txn_id']) ? $_POST['txn_id'] : $txnid;
			$this->response->txnid = $new_txnid;
			$this->response->txnorigin = $txnid;
		}
		if ( isset($_POST['payer_status']) ) $this->response->payer_status = ( 'verified' == $_POST['payer_status'] ? __('Payer verified', 'Shopp') : __('Payer unverified', 'Shopp') );
		if ( isset($_POST['pending_reason']) ) $this->response->pending_reason = $_POST['pending_reason'];
		if ( isset($_POST['protection_eligibility']) ) $this->response->protection_eligibility = $_POST['protection_eligibility'];
		if ( isset($_POST['reason_code']) ) $this->response->reason_code = $_POST['reason_code'];

		shopp_debug('IPN to response protocol: '._object_r($this->response));

		$Purchase = new Purchase( $txnid, 'txnid' );
		// create new purchase by IPN
		if ( empty($Purchase->id) ) {
			if ( 'voided' == $event ) return; // the transaction is void of the starting gate. Don't create a purchase.

			if ( ! isset($_POST['custom']) ) {
				new ShoppError(sprintf(__('No reference to the pending order was available in the PayPal IPN message. Purchase creation failed for transaction %s.'),$txnid),'paypalstandard_process_neworder',SHOPP_TRXN_ERR);
				die('PayPal IPN failed.');
			}
			shopp_debug('preparing to load session '.$_POST['custom']);
			add_filter('shopp_agent_is_robot', array($this, 'is_robot_override'));

			// load the desired session, which leaves the previous/defunct Order object intact
			Shopping::resession($_POST['custom']);

			// destroy the defunct Order object from defunct session and restore the Order object from the loaded session
			// also assign the restored Order object as the global Order object
			$this->Order = ShoppOrder( ShoppingObject::__new( 'Order', ShoppOrder() ) );

			$Shopping = ShoppShopping();

			// Couldn't load the session data
			if ($Shopping->session != $_POST['custom'])
				return new ShoppError("Session could not be loaded: {$_POST['custom']}",false,SHOPP_DEBUG_ERR);
			else new ShoppError("PayPal successfully loaded session: {$_POST['custom']}",false,SHOPP_DEBUG_ERR);

			// process shipping address changes from IPN message
			$this->ipnupdates();

			// Create new purchase
			shopp_add_order_event(false, 'purchase', array(
				'gateway' => $this->module,
				'txnid' => $txnid
			));

			if ( empty(ShoppPurchase()->id) ) new ShoppError('Purchase save failed.',false,SHOPP_DEBUG_ERR);

			return; // end after new purchase creation
		}

		// Process update events as needed
		if ( 'purchase' != $event ) {
			// Review event on transaction type
			if ( $this->txn_types[$this->response->txn_type] ) {
				shopp_add_order_event( $Purchase->order, 'review', array(
					'kind' => 'txn_type',
					'note' => $this->txn_types[$this->response->txn_type]
				));
			}

			$this->$event( $Purchase );

		}

		die('PayPal IPN processed.');
	}

	/**
	 * is_robot_override
	 *
	 * PayPal Sandbox doesn't return a user agent.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return bool is_robot() override on empty user agent
	 **/
	function is_robot_override ( $is_robot ) {
		if ( ! isset($_SERVER['HTTP_USER_AGENT']) ) return false;
		return $is_robot;
	}

	/**
	 * Process customer and shipping record changes from IPN message
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function ipnupdates () {
		$Order = $this->Order;
		$data = stripslashes_deep($_POST);

		$fields = array(
			'Customer' => array(
				'firstname' => 'first_name',
				'lastname' => 'last_name',
				'email' => 'payer_email',
				'phone' => 'contact_phone',
				'company' => 'payer_business_name'
			),
			'Shipping' => array(
				'address' => 'address_street',
				'city' => 'address_city',
				'state' => 'address_state',
				'country' => 'address_country_code',
				'postcode' => 'address_zip'
			)
		);

		foreach ($fields as $Object => $set) {
			$changes = false;
			foreach ($set as $shopp => $paypal) {
				if (isset($data[$paypal]) && (empty($Order->{$Object}->{$shopp}) || $changes)) {
					$Order->{$Object}->{$shopp} = $data[$paypal];
					// If any of the fieldset is changed, change the rest to keep data sets in sync
					$changes = true;
				}
			}
		}
	}

	/**
	 * Verify the authenticity of an IPN message sent by PayPal
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return boolean True if the IPN message is authentic, false otherwise
	 **/
	function verifyipn () {

		if ( str_true($this->settings['testmode']) ) return true;

		$_ = array();
		$_['cmd'] = "_notify-validate";

		$message = $this->encode(array_merge($_POST,$_));
		$response = $this->send($message);

		shopp_debug('PayPal IPN notification verification response received: '.$response);

		return ('VERIFIED' == $response);

	}

	/**
	 * Verify the authenticity of a PDT message sent by PayPal
	 *
	 * @author Jonathan Davis, John Dillick
	 * @since 1.0
	 *
	 * @return boolean True if verified, false otherwise
	 **/
	function verifypdt () {
		if ($this->settings['pdtverify'] != "on") return false;
		if ($this->settings['testmode'] == "on") return "VERIFIED";
		$_ = array();
		$_['cmd'] = "_notify-synch";
		$_['at'] = $this->settings['pdttoken'];
		$_['tx'] = $_GET['tx'];

		$message = $this->encode($_);
		$response = $this->send($message);
		return (strpos($response,"SUCCESS") !== false);
	}

	/**
	 * Reads PayPal transaction errors and generates Shopp errors
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return ShoppError The Shopp error message object of the PayPal error message
	 **/
	function error () {
		if (!empty($this->Response)) {

			$message = join("; ",$this->Response->l_longmessage);
			if (empty($message)) return false;
			return new ShoppError($message,'paypal_express_transacton_error',SHOPP_TRXN_ERR,
				array('code'=>$code));
		}
	}

	/**
	 * Wrapper to call the framework send() method with the PayPal-specific server URL
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string The response string from the request
	 **/
	function send ($data, $url=false, $deprecated=false, $options = array()) {
		return parent::send($data,$this->url());
	}

	/**
	 * Defines the settings interface
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function settings () {

		$this->ui->text(0,array(
			'name' => 'account',
			'value' => $this->settings['account'],
			'size' => 30,
			'label' => __('Enter your PayPal account email.','Shopp')
		));

		$this->ui->checkbox(0,array(
			'name' => 'pdtverify',
			'checked' => $this->settings['pdtverify'],
			'label' => __('Enable order verification','Shopp')
		));

		$this->ui->text(0,array(
			'name' => 'pdttoken',
			'size' => 30,
			'value' => $this->settings['pdttoken'],
			'label' => __('PDT identity token for validating orders.','Shopp')
		));

		$this->ui->checkbox(0,array(
			'name' => 'testmode',
			'label' => sprintf(__('Use the %s','Shopp'),'<a href="http://docs.shopplugin.net/PayPal_Sandbox" target="shoppdocs">PayPal Sandbox</a>'),
			'checked' => $this->settings['testmode']
		));

		$this->ui->behaviors($this->tokenjs());

	}

	/**
	 * Custom behaviors for the settings interface
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @return string JavaScript behaviors to add to the payment settings interface
	 **/
	function tokenjs () {
		ob_start(); ?>
jQuery(document).bind('paypalstandardSettings',function() {
	var $ = jqnc(),p = '#paypalstandard-pdt',v = $(p+'verify'),t = $(p+'token');
	v.change(function () { v.attr('checked')? t.parent().fadeIn('fast') : t.parent().hide(); }).change();
});
<?php
		$script = ob_get_contents(); ob_end_clean();
		return $script;
	}

} // END class PayPalStandard

?>

<?php
/**
 * PayPal Express
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, 26 August, 2008
 * @package shopp
 * @version 1.2
 * @since 1.2
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppGatewayPayPalExpress extends GatewayFramework implements GatewayModule {

	// Settings
	public $secure = false;
	public $refunds = true;
	public $authonly = true;

	// URLs
	public $buttonurl = 'http://www.paypal.com/%s/i/btn/btn_xpressCheckout.gif';
	public $sandboxurl = 'https://www.sandbox.paypal.com/%s/cgi-bin/webscr?cmd=_express-checkout';
	public $liveurl = 'https://www.paypal.com/%s/cgi-bin/webscr?cmd=_express-checkout';
	public $sandboxapi = 'https://api-3t.sandbox.paypal.com/nvp';
	public $liveapi = 'https://api-3t.paypal.com/nvp';

	// Internals
	public $baseop = array();
	public $currencies = array("USD", "AUD", "BRL", "CAD", "CZK", "DKK", "EUR", "HKD", "HUF",
	 						"ILS", "JPY", "MYR", "MXN", "NOK", "NZD", "PHP", "PLN", "GBP",
	 						"SGD", "SEK", "CHF", "TWD", "THB");

	public $locales = array("AT" => "de_DE", "AU" => "en_AU", "BE" => "en_US", "CA" => "en_US",
							"CH" => "de_DE", "CN" => "zh_CN", "DE" => "de_DE", "ES" => "es_ES",
							"FR" => "fr_FR", "GB" => "en_GB", "GF" => "fr_FR", "GI" => "en_US",
							"GP" => "fr_FR", "IE" => "en_US", "IT" => "it_IT", "JP" => "ja_JP",
							"MQ" => "fr_FR", "NL" => "nl_NL", "PL" => "pl_PL", "RE" => "fr_FR",
							"US" => "en_US");

	public $status = array('Pending' => 'authed',
						'Processed' => 'authed',
						'Canceled-Reversal' => 'captured',
						'Completed' => 'captured',
						'Refunded' => 'refunded',
						'Reversed' => 'refunded',
						'Denied' => 'voided',
						'Expired' => 'voided',
						'Failed' => 'voided',
						'Voided' => 'voided');

	public $shiprequired = array('en_GB');

	public function __construct () {
		parent::__construct();

		$this->setup('username','password','signature','testmode');

		$this->settings['currency_code'] = $this->currencies[0];
		if (in_array($this->baseop['currency']['code'],$this->currencies))
			$this->settings['currency_code'] = $this->baseop['currency']['code'];

		if (array_key_exists($this->baseop['country'],$this->locales))
			$this->settings['locale'] = $this->locales[$this->baseop['country']];
		else $this->settings['locale'] = $this->locales['US'];

		$this->buttonurl = sprintf(force_ssl($this->buttonurl), $this->settings['locale']);
		$this->sandboxurl = sprintf($this->sandboxurl, $this->settings['locale']);
		$this->liveurl = sprintf($this->liveurl, $this->settings['locale']);

		if (!isset($this->settings['label'])) $this->settings['label'] = "PayPal";

		add_action('shopp_txn_update',array($this,'ipn'));
		add_filter('shopp_tag_cart_paypalexpress',array($this,'cartcheckout'),10,2);
		add_filter('shopp_checkout_submit_button',array($this,'submit'),10,3);

		// Order Event Handlers
		add_action('shopp_paypalexpress_auth', array($this, 'auth'));
		add_action('shopp_paypalexpress_refund', array($this, 'refund'));
	}

	public function actions () {
		add_action('shopp_checkout_processed', array($this, 'checkout'));
		add_action('shopp_init_confirmation', array($this, 'GetExpressCheckoutDetails'));
		add_action('shopp_remote_payment', array($this, 'returned'));
	}

	/**
	 * Renders the Checkout with PayPal button on the checkout form
	 * (or cart when used with the shopp('cart','paypalexpress') Theme API call)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array List of checkout buttons
	 **/
	public function submit ($tag=false,$options=array(),$attrs=array()) {
		$tag[$this->settings['label']] = '<input type="image" name="process" src="'.$this->buttonurl.'" class="checkout-button" '.inputattrs($options,$attrs).' />';
		return $tag;
	}

	/**
	 * Provides client side URLs to send customer for payment
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string The PayPal payment URL
	 **/
	public function url ($url=false) {
		if ( Shopp::str_true($this->settings['testmode']) ) return $this->sandboxurl;
		else return $this->liveurl;
	}

	/**
	 * Provides server side URLs for making API calls
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string The PayPal API server URL
	 **/
	public function apiurl ($url=false) {
		if ( Shopp::str_true($this->settings['testmode']) ) return $this->sandboxapi;
		else return $this->liveapi;
	}

	/**
	 * Process handler when returning from making an Express Checkout payment
	 *
	 * Stores the payment token and Payer ID values in the session for use when
	 * completing the payment.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function returned () {
		$Order = ShoppOrder();

		if ( ! empty($_GET['token']) ) $Order->token = $_GET['token'];
		if ( ! empty($_GET['PayerID']) ) $Order->payerid = $_GET['PayerID'];

		if ('confirmed' == $_POST['checkout']) do_action('shopp_confirm_order');
	}

	/**
	 * After checkout form processing sets up the Express Checkout and sends the customer to PayPal for payment
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function checkout () {
		// because shopp_process_free_order occurs after shopp_checkout_processed
		// we do not want to redirect if the order is free at shopp_checkout_processed
		if ( $this->Order->Cart->orderisfree() ) return;

		$response = $this->SetExpressCheckout();
		shopp_redirect(add_query_arg('token',$response->token,$this->url()));

	}

	/**
	 * Handles checkout directly from the shopping cart via the shopp('cart','paypalexpress') Theme API tag
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string The generated Express Checkout form
	 **/
	public function cartcheckout () {

		$response = $this->SetExpressCheckout();

		$action = add_query_arg('token',$response->token,$this->url());

		$submit = $this->submit(array());
		$submit = $submit[$this->settings['label']];

		return '<form action="'.esc_attr($action).'" method="POST">'.$submit.'</form>';

	}

	/**
	 * Handles auth-capture order processing
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param ShoppOrderEvent $Event The auth order event
	 * @return void
	 **/
	public function auth ( $Event ) {

		$response = $this->DoExpressCheckoutPayment();
		$status = $this->status[ $response->paymentinfo_0_paymentstatus ];

		new ShoppError("PayPal Express DoExpressCheckoutPayment STATUS: $response->paymentinfo_0_paymentstatus = $status",false,SHOPP_DEBUG_ERR);

		$txnid = $response->paymentinfo_0_transactionid;
		$amount = $response->paymentinfo_0_amt;
		$fees = $response->paymentinfo_0_feeamt;
		$type = $response->paymentinfo_0_paymenttype;

		shopp_add_order_event ( $Event->order, 'authed', array(
			'txnid' 	=> $txnid,						// Transaction ID
			'amount' 	=> $amount,						// Amount captured
			'fees' 		=> $fees,						// Transaction fees taken by the gateway net revenue = amount-fees
			'gateway' 	=> $Event->gateway,				// Gateway handler name (module name from @subpackage)
			'paymethod' => $this->settings['label'],	// Payment method (payment method label from payment settings)
			'paytype' 	=> $type,						// Type of payment (check, MasterCard, etc)
			'payid' 	=> '',							// Payment ID (last 4 of card or check number)
			'capture' 	=> ( 'captured' == $status )	// Captured payment flag
		));

	}

	/**
	 * Handles refund processing from the order manager
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param ShoppOrderEvent $Event The refund order event
	 * @return void
	 **/
	public function refund ( $Event ) {

		$response = $this->RefundTransaction($Event->order,$Event->amount,$Event->reason);

		if ( is_a($response,'ShoppError') ) {
			shopp_add_order_event ( $Event->order, 'refund-fail', array(
				'amount' 	=> $Event->amount,					// Amount to be refunded
				'error' 	=> $response->code,					// Error code (if provided)
				'message' 	=> join(' ',$response->messages),	// Error message reported by the gateway
				'gateway' 	=> $Event->gateway					// Gateway handler name (module name from @subpackage)
			));
		}

		shopp_add_order_event ( $Event->order, 'refunded', array(
			'txnid' 	=> $response->refundtransactionid, 			// Transaction ID for the REFUND event
			'amount' 	=> floatval($response->grossrefundamt),		// Amount refunded
			'gateway' 	=> $Event->gateway							// Gateway handler name (module name from @subpackage)
		));

	}

	/**
	 * Includes the Express Checkout API credentials to requests
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array List of credentials for the request
	 **/
	public function RequestSignature () {
		$_ = array();

		$_['USER'] 					= $this->settings['username'];
		$_['PWD'] 					= $this->settings['password'];
		$_['SIGNATURE']				= $this->settings['signature'];
		$_['VERSION']				= '84.0';

		return $_;
	}

	/**
	 * Sets up the Express Checkout transaction.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return object The API response or ShoppError
	 **/
	public function SetExpressCheckout () {
		$_ = $this->RequestSignature();

		// Options
		$_['METHOD']							= 'SetExpressCheckout';
		$_['PAYMENTREQUEST_0_PAYMENTACTION']	= 'Sale';
		$_['LANDINGPAGE']						= 'Billing';

		// Include page style option, if provided
		if (isset($_GET['pagestyle'])) $_['PAGESTYLE'] = $_GET['pagestyle'];

		if (isset($this->Order->data['paypal-custom']))
			$_['CUSTOM'] = htmlentities($this->Order->data['paypal-custom']);

		$_['RETURNURL']			= Shopp::url(array('rmtpay'=>'process'),'confirm');

		$_['CANCELURL']			= Shopp::url(false,'cart');

		$_ = array_merge($_,$this->PaymentRequest());

		$response = $this->send($_);

		if ( is_a($response,'ShoppError') ) return $response;

		if ( SHOPP_DEBUG &&  'Success' != $response->ack ) new ShoppError('In '.__FUNCTION__.': '.$response->debuglog, 'debug'.__FUNCTION__, SHOPP_DEBUG_ERR);

		if ( ! in_array($response->ack,array('Success','SuccessWithWarning')) ) {
			$message = join("; ", $response->longmessage);
			if (empty($message)) $message = __('The transaction failed for an unknown reason. PayPal did not provide any indication of why it failed.','Shopp');
			return new ShoppError($message,'paypal_express_transacton_error',SHOPP_TRXN_ERR,array('codes'=>join('; ',$response->errorcode)));
		}

		if ( ! isset($response->token) )return new ShoppError(__('The transaction failed because PayPal did not issue a payment token.','Shopp'),'paypalexpress_no_token',SHOPP_TRXN_ERR);

		return $response;
	}

	/**
	 * Updates the in-progress order with information from the PayPal transaction
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function GetExpressCheckoutDetails () {
		$Order = ShoppOrder();

		if (!isset($Order->token) || !isset($Order->payerid)) return false;

		$_ = $this->RequestSignature();

   		$_['METHOD'] 				= 'GetExpressCheckoutDetails';
		$_['TOKEN'] 				= $Order->token;

		// Get transaction details
		$response = false;
		for ($attempts = 0; $attempts < 2 && !$response; $attempts++) {
			$response = $this->send($_);
		}

		$fields = array(
			'Customer' => array(
				'firstname' => 'firstname',
				'lastname' => 'lastname',
				'email' => 'email',
				'phone' => 'phonenum',
				'company' => 'payerbusiness'
			),
			'Shipping' => array(
				'address' => 'shiptostreet',
				'xaddress' => 'shiptostreet2',
				'city' => 'shiptocity',
				'state' => 'shiptostate',
				'country' => 'shiptocountrycode',
				'postcode' => 'shiptozip'
			)
		);

		foreach ($fields as $Object => $set) {
			$changes = false;
			foreach ($set as $shopp => $paypal) {
				if (isset($response->{$paypal}) && (empty($Order->{$Object}->{$shopp}) || $changes)) {
					$Order->{$Object}->{$shopp} = $response->{$paypal};
					// If any of the fieldset is changed, change the rest to keep data sets in sync
					$changes = true;
				}
			}
		}

		if (empty($Order->Shipping->state) && empty($Order->Shipping->country))
			add_filter('shopp_cart_taxrate',create_function('$rate','return false;')); // Force no tax

		$targets = shopp_setting('target_markets');
		if ( ! in_array($Order->Shipping->country,array_keys($targets)) ) {
			new ShoppError(__('The location you are purchasing from is outside of our market regions. This transaction cannot be processed.','Shopp'),'paypalexpress_market',SHOPP_TRXN_ERR);
			shopp_redirect(Shopp::url(false,'checkout'));
		}

	}

	/**
	 * Sends a payment request to complete the Express Checkout transaction, including the actual total amount of the order.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return Object The response object on success (redirects on error)
	 **/
	public function DoExpressCheckoutPayment () {
		$Order = ShoppOrder();
		if ( ! isset($Order->token) || ! isset($Order->payerid) ) return false;

		$_ = $this->RequestSignature();

		$_['METHOD'] 							= 'DoExpressCheckoutPayment';
		$_['PAYMENTREQUEST_0_PAYMENTACTION']	= 'Sale';
		$_['TOKEN'] 							= $Order->token;
		$_['PAYERID'] 							= $Order->payerid;
		$_['BUTTONSOURCE']						= 'shopplugin.net[PPE]';
		$_['PAYMENTREQUEST_0_NOTIFYURL']		= Shopp::url(array('_txnupdate'=>'PPE'),'checkout');

		// Transaction
		$_ = array_merge($_,$this->PaymentRequest());
		$response = $this->send($_);

		if ( is_a($response,'ShoppError') ) {
			new ShoppError(__('No response was received from PayPal. The order cannot be processed.','Shopp'),'paypalexpress_noresults',SHOPP_COMM_ERR);
			shopp_redirect(Shopp::url(false,'checkout'));
		}

		if ( SHOPP_DEBUG && 'Success' != $response->ack ) new ShoppError('In '.__FUNCTION__.': '.$response->debuglog, 'debug'.__FUNCTION__, SHOPP_DEBUG_ERR);

		if ( ! in_array($response->ack,array('Success','SuccessWithWarning')) ) {
			$message = join("; ", $response->longmessages);
			if (empty($message)) $message = __('The transaction failed for an unknown reason. PayPal did not provide any indication of why it failed.','Shopp');
			new ShoppError($message,'paypal_express_transacton_error',SHOPP_TRXN_ERR,array('codes'=>join('; ',$response->errorcode)));
			shopp_redirect(Shopp::url(false,'checkout'));
		}

		return $response;

	}

	/**
	 * Sends a refund transaction request to PayPal
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param int $order The Purchase record ID for the order
	 * @param float|string $amount The amount of the refund
	 * @param string $reason The reason note from the refund event
	 * @return Object The successful response object or a ShoppError
	 **/
	public function RefundTransaction ( $order, $amount, $reason = '' ) {
		$Purchase = shopp_order($order);

		$_ = $this->RequestSignature();
		$_['METHOD'] = 'RefundTransaction';
		$_['TRANSACTIONID'] = $Purchase->txnid;
		$type = 'Full';
		if ( ! $Purchase->refunded && $amount < $Purchase->total ) {
			$type = 'Partial';
			$_['AMT'] = number_format($amount, $this->precision);
			$_['CURRENCYCODE'] = $this->settings['currency_code'];
		}
		$_['REFUNDTYPE'] = $type;
		if ( !empty($reason) ) $_['NOTE'] = $reason;

		$response = $this->send($_);

		if ( SHOPP_DEBUG && 'Success' != $response->ack ) new ShoppError('In '.__FUNCTION__.': '.$response->debuglog, 'debug'.__FUNCTION__, SHOPP_DEBUG_ERR);

		if ( ! in_array($response->ack,array('Success','SuccessWithWarning')) ) {
			$message = join("; ", $response->longmessages);
			if (empty($message)) $message = __('The transaction failed for an unknown reason. PayPal did not provide any indication of why it failed.','Shopp');
			return new ShoppError($message,'paypal_express_transacton_error',SHOPP_TRXN_ERR,array('codes'=>join('; ',$response->errorcode)));
		}

	}

	/**
	 * Builds the order details portion of PayPal order processing requests
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array The list of order detail fields
	 **/
	public function PaymentRequest () {
		// Localize Order objects
		$Order = ShoppOrder();
		$Customer = $Order->Customer;
		$Billing = $Order->Billing;
		$Shipping = $Order->Shipping;
		$Cart = $Order->Cart;
		$Totals = $Order->Cart->Totals;

		$_ = array();

		$_['EMAIL']							= $Customer->email;
		$_['SHIPTOPHONENUM']				= $Customer->phone;

		if (isset($Order->data['paypal-custom']))
			$_['CUSTOM'] = htmlentities($Order->data['paypal-custom']);

		// Shipping address override
		if (!empty($Shipping->address) && !empty($Shipping->postcode)) {
			$_['ADDROVERRIDE'] 							= 1;
			$_['PAYMENTREQUEST_0_SHIPTOSTREET'] 		= $Shipping->address;
			if (!empty($Shipping->xaddress))
				$_['PAYMENTREQUEST_0_SHIPTOSTREET2']	= $Shipping->xaddress;
			$_['PAYMENTREQUEST_0_SHIPTOCITY']			= $Shipping->city;
			$_['PAYMENTREQUEST_0_SHIPTOSTATE']			= $Shipping->state;
			$_['PAYMENTREQUEST_0_SHIPTOZIP']			= $Shipping->postcode;
			$_['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']	= $Shipping->country;
		}

		if (empty($this->Order->Cart->shipped) &&
			!in_array($this->settings['locale'],$this->shiprequired)) $_['NOSHIPPING'] = 1;

		// Line Items
		foreach($Cart->contents as $i => $Item) {
			$_['L_PAYMENTREQUEST_0_NAME'.$i]		= $Item->name.((!empty($Item->optionlabel))?' '.$Item->optionlabel:'');
			$_['L_PAYMENTREQUEST_0_AMT'.$i]			= $this->amount($Item->unitprice);
			$_['L_PAYMENTREQUEST_0_NUMBER'.$i]		= $i;
			$_['L_PAYMENTREQUEST_0_QTY'.$i]			= $Item->quantity;
		}

		if ($Totals->discount != 0) {
			$discounts = array();
			foreach($Cart->discounts as $discount)
				$discounts[] = $discount->name;

			$i++;
			$_['L_PAYMENTREQUEST_0_NUMBER'.$i]		= $i;
			$_['L_PAYMENTREQUEST_0_NAME'.$i]		= htmlentities(join(", ",$discounts));
			$_['L_PAYMENTREQUEST_0_AMT'.$i]			= $this->amount($Totals->discount*-1);
			$_['L_PAYMENTREQUEST_0_QTY'.$i]			= 1;
		}

		// Transaction
		$_['PAYMENTREQUEST_0_CURRENCYCODE']			= $this->settings['currency_code'];
		$_['PAYMENTREQUEST_0_ITEMAMT']				= (float)$this->amount('subtotal')-(float)$this->amount('discount');
		$_['PAYMENTREQUEST_0_TAXAMT']				= $this->amount('tax');
		$_['PAYMENTREQUEST_0_AMT']					= $this->amount('total');

		// Workaround a PayPal limitation that does not handle a 0.00 subtotal amount/
		// that may happen because of discounts (subtotal-discount=0.00). We handle this
		// situation by moving shipping fees to a line item instead of a shipping amount,
		// and lacking shipping fees, use a subtotal of 0.01 to satisfy minimum order
		// amount requirements. Yes it is ugly. Thanks PayPal.

		if ($_['PAYMENTREQUEST_0_ITEMAMT'] == 0) {

			$amount = $this->amount( max(0.01,$this->amount('shipping')) ); // Choose the higher amount of shipping costs or 0.01
			$i++;
			$_['L_PAYMENTREQUEST_0_NUMBER'.$i]		= $i;
			$_['L_PAYMENTREQUEST_0_NAME'.$i]		= apply_filters('paypal_freeorder_handling_label',__('Shipping & Handling','Shopp'));
			$_['L_PAYMENTREQUEST_0_AMT'.$i]			= $amount;
			$_['L_PAYMENTREQUEST_0_QTY'.$i]			= 1;

			// Adjust subtotals accordingly
			$_['PAYMENTREQUEST_0_ITEMAMT'] += $amount;
			$_['PAYMENTREQUEST_0_AMT'] += $amount;

		} else $_['PAYMENTREQUEST_0_SHIPPINGAMT']	= $this->amount('shipping');

		return $_;
	}

	/**
	 * Process IPN messages for Express Checkout transactions
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function ipn () {
		shopp_debug("IPN message: "._object_r($_POST));

		// Cancel processing if this is not a PayPal Website Payments Standard/Express Checkout IPN
		if (isset($_POST['txn_type']) && $_POST['txn_type'] != "cart") return false;

		$target = isset($_POST['parent_txn_id'])?$_POST['parent_txn_id']:$_POST['txn_id'];

		$Purchase = shopp_order( $target,'trans' );

		if ( $Purchase->txnid != $target || empty($Purchase->id) ) return; // No Purchase found to update
		if ( $Purchase->gateway != $this->module ) return; // Not a PPE order, don't touch it

		// Validate the order notification
		if ( ! $this->verifyipn() ) {
			new ShoppError(sprintf(__('An unverifiable order update notification was received from PayPal for transaction: %s. Possible fraudulent notification!  The order will not be updated.  IPN message: %s','Shopp'),$target,_object_r($_POST)),'paypal_txn_verification',SHOPP_TRXN_ERR);
			return false;
		}

		$txnstatus = $this->status[$_POST['payment_status']];
		if ( ! $txnstatus || $Purchase->txnstatus == $txnstatus ) return; // no change

		$fees = 0;
		$amount = 0;
		if ( isset($_POST['mc_fee']) ) $fees = abs($_POST['mc_fee']);
		$amount = isset($_POST['mc_gross']) ? abs($_POST['mc_gross']) : $Purchase->total;

		switch ($txnstatus) {
			case 'captured':
				shopp_add_order_event ( $Purchase->id, 'captured', array(
					'txnid' => $Purchase->txnid,	// Transaction ID
					'amount' => $amount,			// Amount captured
					'fees' => $fees,				// Transaction fees taken by the gateway net revenue = amount-fees
					'gateway' => $Purchase->gateway	// Gateway handler name (module name from @subpackage)
				)); break;

			case 'refunded':
				shopp_add_order_event ( $Purchase->id, 'refunded', array(
					'txnid' 	=> $_POST['txn_id'], 	// Transaction ID for the REFUND event
					'amount' 	=> $amount,				// Amount refunded
					'gateway' 	=> $Purchase->gateway	// Gateway handler name (module name from @subpackage)
				)); break;

			case 'voided':
				shopp_add_order_event ( $Purchase->id, 'voided', array(
					'txnorigin' => $Purchase->txnid,		// Original transaction ID (txnid of original Purchase record)
					'txnid' 	=> $_POST['txn_id'],		// Transaction ID for the VOID event
					'gateway' 	=> $Purchase->gateway		// Gateway handler name (module name from @subpackage)
				)); break;
		}

		shopp_debug('PayPal IPN update processed for transaction: '.$target);

		die('PayPal IPN update processed.');
	}

	/**
	 * Authenticates the validity of an IPN message
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return boolean True for an authentic message, false otherwise
	 **/
	public function verifyipn () {
		if ( Shopp::str_true($this->settings['testmode']) ) return true;

		$_ = array();
		$_['cmd'] = '_notify-validate';

		$message = array_merge($_POST,$_);
		$response = $this->send($message);

		shopp_debug('PayPal IPN notification verfication response received: '.$response);

		return ('VERIFIED' == $response);
	}

	/**
	 * Sends messages to the appropriate PayPal API server
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return Object The response from the server or a ShoppError
	 **/
	public function send ($message) {
		shopp_debug('message: '._object_r($message));
		$response = parent::send($message,$this->apiurl());

		if (!$response) return new ShoppError($this->name.": ".Lookup::errors('gateway','noresponse'),'gateway_comm_err',SHOPP_COMM_ERR);
		$response = $this->response($response);

		return $response;
	}

	/**
	 * Translates a PayPal response to an accessible standard object
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $buffer The raw response string
	 * @return Object The standard class object containing response data
	 **/
	public function response ($buffer) {
		$_ = new stdClass();
		$_->debuglog = '';

		$r = array();
		$pairs = explode("&",$buffer);
		foreach($pairs as $pair) {
			list($key,$value) = explode("=",$pair);
			if (preg_match("/l_(\w+?)(\d+)/i",$key,$matches)) {
				// Capture line item data into an array structure
				if (!isset($r[$matches[1]])) $r[$matches[1]] = array();
				// Skip non-line item data
				if (is_array($r[$matches[1]])) $r[$matches[1]][$matches[2]] = urldecode($value);
			} else $r[$key] = urldecode($value);
		}

		// Remap array to object
		foreach ($r as $key => $value) {
			if (empty($key)) continue;
			$key = strtolower($key);
			$_->{$key} = $value;
		}

		$i = 0;
		if ( isset($response->correlationid) )$_->debuglog = "CorrelationID: $response->correlationid ";

		while ( isset($_->{"l_errorcode$i"}) ) {
			if ( ! isset($_->errorcodes) ) $_->errorcodes = array();
			if ( ! isset($_->shortmessages) ) $_->errorcodes = array();
			if ( ! isset($_->longmessages) ) $_->errorcodes = array();

			$_->errorcodes[$i] = $_->{"l_errorcode$i"};
			$_->debuglog .= "Code: ".$_->errorcodes[$i]." ";

			$_->shortmessages[$i] = $_->{"l_shortmessage$i"};
			$_->debuglog .= "Short: ".$_->shortmessages[$i]." ";

			$_->longmessages[$i] = $_->{"l_longmessage$i"};
			$_->debuglog .= "Long: ".$_->longmessages[$i]."; ";

			$i++;
		}
		shopp_debug(_object_r($_));

		return $_;
	}

	/**
	 * Builds the settings UI in for the payment settings screen
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function settings () {
		$this->ui->text(0,array(
			'name' => 'username',
			'size' => 30,
			'value' => $this->settings['username'],
			'label' => __('Enter your PayPal API Username.','Shopp')
		));

		$this->ui->password(0,array(
			'name' => 'password',
			'size' => 16,
			'value' => $this->settings['password'],
			'label' => __('Enter your PayPal API Password.','Shopp')
		));

		$this->ui->text(0,array(
			'name' => 'signature',
			'size' => 48,
			'value' => $this->settings['signature'],
			'label' => __('Enter your PayPal API Signature.','Shopp')
		));

		$this->ui->checkbox(0,array(
			'name' => 'testmode',
			'checked' => $this->settings['testmode'],
			'label' => sprintf(__('Use the %s','Shopp'),'<a href="https://shopplugin.net/PayPal_Sandbox" target="shoppdocs">PayPal Sandbox</a>')
		));
	}

}
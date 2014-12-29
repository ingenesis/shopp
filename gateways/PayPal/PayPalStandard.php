<?php
/**
 * PayPal Standard
 *
 * @author Jonathan Davis, John Dillick
 * @copyright Ingenesis Limited, May 2009
 * @package shopp
 * @version 1.3.2
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPayPalStandard extends GatewayFramework implements GatewayModule {

	// Settings
	public $secure = false; // do not require SSL or session encryption
	public $saleonly = true; // force sale event on processing (no auth)
	public $recurring = true; // support for recurring payment

	private $Message; // PDT and IPN message

	static $currencies = array(
		'USD', 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF',
		'ILS', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP',
		'SGD', 'SEK', 'CHF', 'TWD', 'THB'
	);

	static $locales = array(
		'AT' => 'de_DE', 'AU' => 'en_AU', 'BE' => 'en_US', 'CA' => 'en_US',
		'CH' => 'de_DE', 'CN' => 'zh_CN', 'DE' => 'de_DE', 'ES' => 'es_ES',
		'FR' => 'fr_FR', 'GB' => 'en_GB', 'GF' => 'fr_FR', 'GI' => 'en_US',
		'GP' => 'fr_FR', 'IE' => 'en_US', 'IT' => 'it_IT', 'JP' => 'ja_JP',
		'MQ' => 'fr_FR', 'NL' => 'nl_NL', 'PL' => 'pl_PL', 'RE' => 'fr_FR',
		'US' => 'en_US'
	);

	const APIURL = 'https://www.paypal.com/cgi-bin/webscr';
	const DEVURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	const BUTTON = 'http://www.paypal.com/%s/i/btn/btn_xpressCheckout.gif';

	public function __construct () {

		parent::__construct();

		$this->setup( 'account', 'pdtverify', 'pdttoken', 'testmode' );

		if ( ! isset($this->settings['label']) )
			$this->settings['label'] = 'PayPal';

		add_filter( 'shopp_gateway_currency', array( __CLASS__, 'currencies' ) );
		add_filter( 'shopp_themeapi_cart_paypal', array( $this, 'cartapi' ) );
		// add_filter('shopp_themeapi_cart_paypal', array($this, 'sendcart'), 10, 2); // provides shopp('cart.paypal') checkout button
		add_filter( 'shopp_checkout_submit_button', array($this, 'submit'), 10, 3 ); // replace submit button with paypal image

		// Prevent inclusive taxes from adding extra taxes to the order
		add_filter( 'shopp_gateway_tax_amount', array($this, 'notaxinclusive' ) );

		// request handlers
		add_action( 'shopp_remote_payment', array( $this, 'pdt' ) ); // process sync return from PayPal
		add_action( 'shopp_txn_update', array( $this, 'ipn' ) ); // process IPN

		// order event handlers
		add_filter( 'shopp_purchase_order_paypalstandard_processing', array( $this, 'processing' ) );
		add_action( 'shopp_paypalstandard_sale', array( $this, 'auth' ) );
		add_action( 'shopp_paypalstandard_auth', array( $this, 'auth' ) );
		add_action( 'shopp_paypalstandard_capture', array( $this, 'capture' ) );
		add_action( 'shopp_paypalstandard_refund', array( $this, 'refund' ) );
		add_action( 'shopp_paypalstandard_void', array( $this, 'void' ) );

	}

	/**
	 * These action callbacks are only established when the payment method is set to this module.
	 * All other general actions belong in the constructor.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function actions () {
		add_filter( 'shopp_themeapi_checkout_confirmbutton', array( $this, 'confirm' ), 10, 3 ); // replace submit button with paypal image
	}

	public function processing ( $processing ) {
		return array( $this, 'uploadcart' );
	}


	/**
	 * Process a sale
	 *
	 * Hooks the notify, accounts and success order handlers to the authed
	 * to ensure order emails and logins are created once a payment is in progress.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param Purchase $Purchase The purchase order to process a sale for
	 * @return void
	 **/
	public function sale ( ShoppPurchase $Purchase ) {

		add_action( 'shopp_authed_order_event', array( ShoppOrder(), 'notify' ) );
		add_action( 'shopp_authed_order_event', array( ShoppOrder(), 'accounts' ) );
		add_action( 'shopp_authed_order_event', array( ShoppOrder(), 'success' ) );

		shopp_add_order_event( $Purchase->id, 'sale', array(
			'gateway' => $Purchase->gateway,
			'amount' => $Purchase->total
		) );

	}

	// ORDER EVENT HANDLERS

	/**
	 * Marks the order as authorized (+captured when payment is completed)
	 *
	 * @author John Dillick, Jonathan Davis
	 * @since 1.2
	 *
	 * @param AuthOrderEvent|SaleOrderEvent $Event The 'auth' event message
	 * @return void
	 **/
	public function auth ( OrderEventMessage $Event ) {

		$Message = $this->Message;
		if ( ! $Message ) return; // Requires an IPN/PDT message

		shopp_debug(__METHOD__ . ': ' . _object_r($Message));

		if ( $payer_status = $Message->payer() ) { // Note the payer status
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'payer_status',
				'note' => $payer_status
			));
		}


		if ( $pending_reasons = $Message->reason() ) { // Note pending reasons
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'pending_reasons',
				'note' => $pending_reasons
			));
		}

		if ( $protection_eligibility = $Message->protection() ) { // Note protection eligibility
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'protection_eligibility',
				'note' => $protection_eligibility
			));
		}

		$authed = array(
			'txnid' => $Message->txnid(),						// Transaction ID
			'amount' => $Message->amount(),						// Gross amount authorized
			'gateway' => $this->module,							// Gateway handler name (module name from @subpackage)
			'paymethod' => $this->settings['label'],			// Payment method (payment method label from payment settings)
			'paytype' => $Message->paytype(),					// Type of payment (eCheck, or instant payment)
			'payid' => $Message->email(),						// PayPal account email address
			'capture' => ( $captured = $Message->captured() )	// Capture flag
		);

		if ( $captured && $fees = $Message->fees() )
			$authed['fees'] = $fees;

		shopp_add_order_event( $Event->order, 'authed', $authed );

	}

	/**
	 * Mark an order payment as 'captured' (completed)
	 *
	 * @author Jonathan Davis
	 * @version 1.3
	 * @since 1.2
	 *
	 * @param CaptureOrderEvent $Event The 'capture' event message
	 * @return void
	 **/
	public function capture ( CaptureOrderEvent $Event ) {
		$Message = $this->Message;
		if ( ! $Message ) return; // Requires an IPN/PDT message

		if ( $reversal = $Message->reversal() ) { // Log any reversal messages
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'reason',
				'note' => $reversal
			));
		}

		shopp_add_order_event($Event->order, 'captured', array(
			'txnid' => $Message->txnid(),		// Transaction ID of the CAPTURE event
			'amount' => $Event->amount,		// Amount captured
			'fees' => $Event->fees,			// Transaction fees taken by the gateway net revenue = amount-fees
			'gateway' => $this->module		// Gateway handler name (module name from @subpackage)
		));
	}

	/**
	 * Mark an order as 'refunded'
	 *
	 * @author John Dillick, Jonathan Davis
	 * @version 1.3
	 * @since 1.2
	 *
	 * @param RefundOrderEvent $Event The 'refund' order event message
	 * @return void
	 **/
	public function refund ( RefundOrderEvent $Event ) {
		$Message = $this->Message;
		if ( ! $Message ) return; // Requires an IPN/PDT message

		if ( $reversal = $Message->reversal() ) { // Log any reversal messages
			shopp_add_order_event( $Event->order, 'review', array(
				'kind' => 'reason',
				'note' => $reversal
			));
		}

		shopp_add_order_event($Event->order, 'refunded', array(
			'txnid' => $Message->txnid(),		// Transaction ID for the REFUND event
			'amount' => $Message->amount(),		// Amount refunded
			'gateway' => $this->module			// Gateway handler name (module name from @subpackage)
		));

		$this->void( $Event );
	}

	/**
	 * Mark an order as 'voided' (cancelled)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param OrderEventMessage $Event A RefundOrderEvent or VoidOrderEvent message
	 * @return void
	 **/
	public function void ( OrderEventMessage $Event ) {
		$Message = $this->Message;
		if ( ! $Message ) return; // Requires an IPN/PDT message

		shopp_add_order_event($Event->order, 'voided', array(
			'txnid' => $Message->txnid(),			// Transaction ID
			'txnorigin' => $Message->txnorigin(),	// Original Transaction ID
			'gateway' => $this->module				// Gateway handler name (module name from @subpackage)
		));
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
	public function submit ( $tag = false, array $options = array(), array $attrs = array() ) {
		$tag[ $this->settings['label'] ] = '<input type="image" name="process" src="' . esc_url( $this->buttonurl() ) . '" class="checkout-button" ' . inputattrs($options, $attrs) . ' />';
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
	public function confirm ( $tag = false, array $options = array(), $O = null ) {
		$attrs = array( 'title', 'class', 'value', 'disabled', 'tabindex', 'accesskey' );
		return join( '', $this->submit( array(), $options, $attrs ) );
	}

	/**
	 * Adds shopp('cart','paypal') support. Build a form appropriate for sending to PayPal directly from the cart. used by shopp('cart','paypal')
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string PayPal cart form
	 **/
	public function cartapi () {
		$result = '<form action="' . $this->url() . '" method="POST">';
		$result .= $this->form( '', array( 'address_override' => 0 ) );
		$result .= $this->submit();
		$result .= '</form>';
		return $result;
	}

	/**
	 * Provides the live or sandbox url, depending on testmode setting
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string checkout url
	 **/
	public function url () {
		return Shopp::str_true( $this->settings['testmode'] ) ? self::DEVURL : self::APIURL;
	}

	/**
	 * Provides the locale-aware checkout button URL.
	 *
	 * A common customization request is to swap the standard button image for something else and this
	 * can be accomplished via the shopp_paypapstandard_buttonurl hook. It is the merchant's/implementing
	 * developer's responsibility to comply with PayPal guidelines if they choose to do this.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string The URL of the "Checkout with PayPal"
	 **/
	private function buttonurl () {
		$buttonurl = apply_filters( 'shopp_paypalstandard_buttonurl', sprintf( self::BUTTON, $this->locale() ) );
		return Shopp::force_ssl( $buttonurl );
	}

	/**
	 * Provides the locale based on the base of operations
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string The locale string
	 **/
	private function locale () {
		$country = 'US';
		if ( array_key_exists( $this->baseop['country'], self::$locales ) )
			$country = $this->baseop['country'];
		return self::$locales[ $country ];
	}

	/**
	 * Builds a form to send the order to PayPal for processing
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string PayPal cart form
	 **/
	public function uploadcart ( ShoppPurchase $Purchase ) {
		$id = sanitize_key( $this->module );
		$title = Shopp::__( 'Sending order to PayPal&hellip;' );
		$message = '<form id="' . $id . '" action="' . $this->url() . '" method="POST">' .
					$this->form( $Purchase ) .
					'<h1>' . $title . '</h1>' .
					'<noscript>' .
					'<p>' . Shopp::__( 'Click the &quot;Checkout with PayPal&quot; button below to submit your order to PayPal for payment processing:' ) . '</p>' .
					'<p>' . join( '', $this->submit() ) . '</p>' .
					'</noscript>' .
					'</form>' .
					'<script type="text/javascript">document.getElementById("' . $id . '").submit();</script></body></html>';

		wp_die( $message, $title, array( 'response' => 200 ) );
	}

	/**
	 * Builds a hidden form to submit the order to PayPal
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param ShoppPurchase $Purchase The order to submit to PayPal
	 * @return string PayPal order form contents
	 **/
	public function form ( ShoppPurchase $Purchase ) {
		$Shopping = ShoppShopping();
		$Order = ShoppOrder();
		$Cart = $Order->Cart;
		$Customer = $Order->Customer;

		$_ = array();

		$_['cmd'] 					= '_cart';
		$_['upload'] 				= 1;
		$_['business']				= $this->settings['account'];
		$_['invoice']				= $Purchase->id;
		$_['custom']				= $Shopping->session;

		// Options
		if ( Shopp::str_true($this->settings['pdtverify']) )
			$_['return']			= apply_filters( 'shopp_paypalstandard_returnurl', Shopp::url(array('rmtpay' => $this->id(), 'utm_nooverride' => '1'), 'checkout', false) );
		else $_['return']			= Shopp::url(false, 'thanks');

		$_['cancel_return']			= Shopp::url(false, 'cart');
		$_['notify_url']			= $this->ipnurl();
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

		if ( ! empty($Order->Cart->shipped) ) {
			$shipname = explode(' ', $Address->name);
			$_['first_name'] = array_shift($shipname);
			$_['last_name'] = join(' ', $shipname);
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
		if ( ! empty($phone) && in_array($Order->Billing->country, array('US', 'CA')) ) {
			$_['night_phone_a']		= $phone['area'];
			$_['night_phone_b']		= $phone['prefix'];
			$_['night_phone_c']		= $phone['exchange'];
		} else $_['night_phone_b']	= $phone['raw'];

		// Include page style option, if provided
		if ( isset($_GET['pagestyle']) ) $_['pagestyle'] = $_GET['pagestyle'];

		// Transaction
		$_['currency_code']	= $this->currency();

		// Recurring Non-Free Item
		$Cart->recurring();
		$Subscription = reset($Cart->recurring);
		if ( $Cart->recurring() && $Subscription->unitprice > 0 ) {

			$tranges = array(
				'D' => array( 'min' => 1, 'max' => 90 ),
				'W' => array( 'min' => 1, 'max' => 52 ),
				'M' => array( 'min' => 1, 'max' => 24 ),
				'Y' => array( 'min' => 1, 'max' => 5 ),
			);

			$recurring = $Subscription->recurring();
			$recurring['period'] = strtoupper( $recurring['period'] );

			//normalize recurring interval
			$recurring['interval'] = min( max( $recurring['interval'], $tranges[$recurring['period']]['min'] ), $tranges[$recurring['period']]['max'] );

			$_['cmd']	= '_xclick-subscriptions';
			$_['rm']	= 2; // Return with transaction data

			$_['item_number'] = $Subscription->product;
			$_['item_name'] = $Subscription->name . ( ( ! empty( $Subscription->option->label ) ) ? ' (' . $Subscription->option->label . ')' : '' );

			$trial_discounts = apply_filters('shopp_paypalstandard_discount_trials', false);

			// Trial pricing
			if ( $Subscription->has_trial() ) {
				$trial = $Subscription->trial();
				$trial['period'] = strtoupper($trial['period']);
				$trialprice = $this->amount( $trial['price'] );

				if ( $this->amount('discount') > 0 && $trial_discounts )
					$trialprice -= $this->amount('discount');

				// normalize trial interval
				$trial['interval'] = min( max( $trial['interval'], $tranges[$trial['period']]['min'] ), $tranges[$trial['period']]['max'] );

				$_['a1']	= $this->amount( $trial['price'] );
				$_['p1']	= $trial['interval'];
				$_['t1']	= $trial['period'];
			} elseif ( $this->amount('discount') > 0 && $trial_discounts ) {
				// When no trial discounts are created, add a discount to a trial offer using
				// the interval and period of the normal subscription, but at a discounted price
				$_['a1']	= $this->amount( $Subscription->subprice ) - $this->amount('discount');
				$_['p1']	= $recurring['interval'];
				$_['t1']	= $recurring['period'];
			}

			$subprice = $this->amount( $Subscription->subprice );

			if ( $this->amount('discount') > 0 && ! $trial_discounts )
				$subprice -= $this->amount('discount');
			$_['a3']	= $subprice;
			$_['p3']	= $recurring['interval'];
			$_['t3']	= $recurring['period'];

			$_['src']	= 1;

			if ( $recurring['cycles'] ) $_['srt'] = (int) $recurring['cycles'];

		} else {

			// Line Items
			$id = 0;
			foreach ( $Order->Cart as $i => $Item ) {
				$id++;
				$_[ 'item_number_' . $id ]		= $id;
				$_[ 'item_name_' . $id ]		= $Item->name . ( ! empty($Item->option->label) ? ' ' . $Item->option->label : '');
				$_[ 'amount_' . $id ]			= $this->amount($Item->unitprice);
				$_[ 'quantity_' . $id ]			= $Item->quantity;
				// $_['weight_'.$id]			= $Item->quantity;
			}

			// Workaround a PayPal limitation of not correctly handling no subtotals or
			// handling discounts in the amount of the item subtotals by adding the
			// shipping fee to the line items to get included in the subtotal. If no
			// shipping fee is available use 0.01 to satisfy minimum order amount requirements
			// Additionally, this condition should only be possible when using the shopp('cart','paypal')
			// Theme API tag which would circumvent normal checkout and use PayPal even for free orders
			if ( (float) $this->amount('order') == 0 || (float) $this->amount('order') - (float) $this->amount('discount') == 0 ) {
				$id++;
				$_['item_number_'.$id]		= $id;
				$_['item_name_'.$id]		= apply_filters('paypal_freeorder_handling_label',
															__('Shipping & Handling','Shopp'));
				$_['amount_'.$id]			= $this->amount( max((float)$this->amount('shipping'), 0.01) );
				$_['quantity_'.$id]			= 1;
			} else
				$_['handling_cart']			= $this->amount('shipping');

			$_['discount_amount_cart'] 		= $this->amount('discount');
			$_['tax_cart']					= $this->amount('tax');
			$_['amount']					= $this->amount('total');

		}

		$_ = apply_filters('shopp_paypal_standard_form', $_);

		return $this->format($_);
	}

	private function process ( $event, ShoppPurchase $Purchase ) {

		if ( ! $Purchase->lock() ) return false; // Only process order updates if this process can get a lock

		$Message = $this->Message;

		if ( in_array( $event, array( 'sale', 'auth', 'capture' ) ) ) {

			$this->updates();

			// Make sure purchase orders are invoiced
			if ( 'purchase' === $Purchase->txnstatus )
				ShoppOrder()->invoice($Purchase);
			elseif ( 'invoiced' === $Purchase->txnstatus )
				$this->sale($Purchase);
			elseif ( 'capture' === $event ) {

				if ( ! $Purchase->capturable() )
					return ShoppOrder()->success(); // Already captured

				if ( 'voided' === $Purchase->txnstatus )
					ShoppOrder()->invoice($Purchase); // Reinvoice for cancel-reversals

				shopp_add_order_event($Purchase->id, 'capture', array(
					'txnid' => $Purchase->txnid,
					'gateway' => $Purchase->gateway,
					'amount' => $Message->amount(),
					'user' => $this->settings['label']
				));
			}
		} elseif ( 'void' == $event ) {
			shopp_add_order_event($Purchase->id, 'void', array(
				'txnid' => $Purchase->txnid,
				'gateway' => $this->module,
				'reason' => $Message->reversal(),
				'user' => $this->settings['label'],
				'note' => $Message->reversal()
			));
		} elseif ( 'refund' == $event ) {
			shopp_add_order_event($Purchase->id, 'refund', array(
				'txnid' => $Purchase->txnid,
				'gateway' => $this->module,
				'amount' => $Message->amount(),
				'reason' => $Message->reversal(),
				'user' => $this->settings['label']
			));
		} elseif ( $txn_type = $Message->type() ) {
			shopp_add_order_event($Purchase->id, 'review', array(
				'kind' => 'txn_type',
				'note' => $Message->type()
			));
		}

		$Purchase->unlock();

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
	public function ipn () {

		if ( ! $this->ipnvalid() ) return;

		$Message = $this->Message;

		shopp_debug( 'PayPal IPN response protocol: ' . Shopp::object_r( $Message ) );

		$id = $Message->order();
		$event = $Message->event();

		$Purchase = new ShoppPurchase($id);

		if ( empty($Purchase->id) ) {
			$error = 'The IPN failed because the given order does not exist.';
			shopp_debug( $error );
			status_header( '404' );
			die( $error );
		}

		$this->process($event, $Purchase);

		status_header( '200' );
		die( 'OK' );
	}

	protected function ipnurl () {
		$url = Shopp::url( array( '_txnupdate' => $this->id() ), 'checkout' );
		return apply_filters( 'shopp_paypalstandard_ipnurl', $url );
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
	protected function ipnvalid () {

		shopp_debug( 'PayPal IPN ' . __METHOD__ );
		$ids = array( $this->id(), 'PPS' ); // 'PPS' is a backwards compatible ID for IPN requests
		if ( ! in_array( $_REQUEST['_txnupdate'], $ids ) ) return false; // Not an IPN request for PayPal Standard
		shopp_debug('PayPal IPN detected');

		$this->Message = new ShoppPayPalStandardMessage( $_POST );
		shopp_debug('PayPal IPN request: ' . json_encode( $_POST ) );

		if ( ! $this->Message->valid() ) return false;
		if ( Shopp::str_true( $this->settings['testmode'] ) ) return true;

		$_ = array();
		$_['cmd'] = '_notify-validate';

		$message = $this->encode( array_merge($_POST, $_ ) );
		$response = $this->send( $message );

		shopp_debug( 'PayPal IPN validation response: ' . var_export( $response, true ) );

		return ( 'VERIFIED' == $response );

	}

	/**
	 * Process customer and shipping record changes from a PayPal message
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	protected function updates () {
		$Order = $this->Order;
		$data = $this->Message->data();

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

		foreach ( $fields as $Object => $set ) {
			if ( ! isset( $Order->$Object ) ) continue;
			$changes = false;
			foreach ( $set as $shopp => $paypal ) {
				if ( isset($data[ $paypal ]) && ( empty($Order->$Object->$shopp) || $changes ) ) {
					$Order->$Object->$shopp = $data[ $paypal ];
					$changes = true; // If any of the fieldset is changed, change the rest to keep data sets in sync
				}
			}
		}

	}

	/**
	 * Handle the synchronous return from PPS (PDT and default return)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 **/
	public function pdt () {
		$Order = ShoppOrder();

		if ( ! $this->pdtvalid() ) return;

		$Message = $this->Message;

		$id = $Message->order();
		$event = $Message->event();

		$Purchase = new ShoppPurchase($id);

		if ( empty($Purchase->id) ) {
			shopp_debug('PDT processing could not load the in progress order from the database.');
			return Shopp::redirect( Shopp::url(false, 'thanks', false ) );
		}

		$Order->inprogress = $Purchase->id;
		$this->process($event, $Purchase);
		Shopp::redirect( Shopp::url( false, 'thanks', false ) );
	}

	/**
	 * Verify the authenticity of a PDT message sent by PayPal
	 *
	 * @author Jonathan Davis, John Dillick
	 * @since 1.0
	 *
	 * @return boolean True if valid, false otherwise
	 **/
	protected function pdtvalid () {

		$ids = array( $this->id(), 'PPS' ); // 'PPS' is a backwards compatible ID for PDT requests
		if ( ! in_array( $_REQUEST['rmtpay'], $ids ) ) return false; // not PDT message

		shopp_debug( 'Processing PDT request: ' . json_encode( $_REQUEST ) );

		if ( ! Shopp::str_true($this->settings['pdtverify']) || ! isset($_REQUEST['tx']) ) {
			ShoppOrder()->success();
			return true; // if PDT verify is off, skip this process
		}

		$_ = array();
		$_['cmd'] = '_notify-synch';
		$_['at'] = $this->settings['pdttoken'];
		$_['tx'] = $_REQUEST['tx'];

		$message = $this->encode($_);			// Build the request
		$response = $this->send($message);		// Send it
		$response = $this->pdtreply($response);	// Parse the response into a ShoppPayPalStandardMessage-compatible structure
		shopp_debug('PayPal PDT _notify-synch reply: ' . json_encode($response));

		// Shift the first element off to get the verification status and have a clean data array for ShoppPayPalStandardMessage
		if ( 'SUCCESS' != array_shift($response) ) {
			shopp_debug('The transaction was not verified by PayPal.');

			// We run the success() method here to reset the shopping session and
			// redirect the shopper to the "thanks" page with an "order in progress" message
			// so the cart will be ready for a new order. Otherwise, the customer could resubmit the
			// prior order and PayPal will give them "that transaction has already been completed" message.
			ShoppOrder()->success();

			return false;
		}

		$this->Message = new ShoppPayPalStandardMessage($response);
		shopp_debug('PayPal PDT response protocol: ' . _object_r($this->Message));

		// Everything looks good, return true and let the order PDT order processing handle it from here
		return true;
	}

	/**
	 * Parses the PDT response
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array
	 **/
	protected function pdtreply ( $string ) {
		$response = array();
		$lines = explode("\n", $string);
		foreach ( $lines as $line ) {
			if ( empty($line) ) continue;
			if ( false !== strpos($line, '=') ) {
				list($key, $value) = explode('=', $line);
				$response[ $key ] = $value;
			} else $response[] = $line;
		}
		return $response;
	}

	/**
	 * Wrapper to call the framework send() method with the PayPal-specific server URL
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $data
	 * @param bool $url
	 * @return string The response string from the request
	 */
	public function send ( $data, $url = false ) {
		$options['httpversion'] = '1.1';
		return parent::send($data, $this->url(), $options);
	}

	/**
	 * Defines the settings interface
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function settings () {

		$this->ui->text(0, array(
			'name' => 'account',
			'value' => $this->settings['account'],
			'size' => 30,
			'label' => Shopp::__('Enter your PayPal account email.')
		));

		$this->ui->checkbox(0, array(
			'name' => 'pdtverify',
			'checked' => $this->settings['pdtverify'],
			'label' => Shopp::__('Enable order verification')
		));

		$this->ui->text(0, array(
			'name' => 'pdttoken',
			'size' => 30,
			'value' => $this->settings['pdttoken'],
			'label' => Shopp::__('PDT identity token for validating orders.')
		));

		$this->ui->checkbox(0, array(
			'name' => 'testmode',
			'label' => Shopp::_mi('Use the [PayPal Sandbox](%s)', ShoppSupport::DOCS . 'payment-processing/paypal-standard/'),
			'checked' => $this->settings['testmode']
		));

		$script = "var s='shopppaypalstandard';jQuery(document).bind(s+'Settings',function(){var $=jQuery,p='#'+s+'-pdt',v=$(p+'verify'),t=$(p+'token');v.change(function(){v.prop('checked')?t.parent().fadeIn('fast'):t.parent().hide();}).change();});";
		$this->ui->behaviors($script);

	}

	public static function currencies ( $currency ) {

		if ( in_array($currency, self::$currencies) )
			return $currency;
		else return 'USD';

	}

}

/**
 * A standardized Shopp protocol for PayPal messages
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopp
 **/
class ShoppPayPalStandardMessage {

	protected $amount = 0;
	protected $email = '';
	protected $fees = 0;
	protected $order = 0;
	protected $payer = '';
	protected $payment = '';
	protected $paytype = '';
	protected $protection = '';
	protected $reason = '';
	protected $reversal = '';
	protected $txnid = '';
	protected $type = '';

	protected $data = array();

	protected static $map = array(
		'invoice' => 'order',
		'mc_fee' => 'fee',
		'mc_gross' => 'amount',
		'parent_txn_id' => 'txnorigin',
		'payer_email' => 'email',
		'payer_status' => 'payer',
		'payment_status' => 'payment',
		'payment_type' => 'paytype',
		'pending_reason' => 'reason',
		'protection_eligibility' => 'protection',
		'reason_code' => 'reversal',
		'txn_id' => 'txnid',
		'txn_type' => 'type'
	);

	protected static $events = array(
		'Voided' => 'void',
		'Denied' => 'void',
		'Expired' => 'void',
		'Failed' => 'void',
		'Refunded' => 'refund',
		'Reversed' => 'refund',
		'Canceled_Reversal' => 'capture',
		'Canceled-Reversal' => 'capture',
		'Completed' => 'capture',
		'Pending' => 'auth',
		'Processed' => 'auth',
	);

	protected static $eligibility = array();
	protected static $reasons = array();
	protected static $reversals = array();
	protected static $types = array();

	public function __construct ( array $data ) {

		$data = array_map('rawurldecode', $data);

		$this->data = $data; // Capture the source data

		// Map the source data to message properties
		foreach ( $data as $key => $value ) {
			$property = isset(self::$map[ $key ]) ? self::$map[ $key ] : false;
			if ( property_exists($this, $property) ) $this->$property = $value;
		}

		$this->labels(); // Initialize labels
	}

	protected static function labels () {
		self::$eligibility = array(
			'ExpandedSellerProtection' => Shopp::__('Eligible for PayPal’s Expanded Seller Protection'),
			'SellerProtection'         => Shopp::__('Eligible for PayPal’s Seller Protection'),
			'None'                     => Shopp::__('Not Eligible for PayPal’s Seller Protection')
		);

		self::$reasons = array(
			'address' 	     => Shopp::__('The customer did not include a confirmed shipping address.'),
			'echeck'         => Shopp::__('The eCheck has not yet cleared.'),
			'intl'           => Shopp::__('You must manually accept or deny transactions for your non-US account.'),
			'multi-currency' => Shopp::__('You must manually accept or deny a transaction in this currency.'),
			'order'          => Shopp::__('You set the payment action to Order and have not yet captured funds.'),
			'paymentreview'  => Shopp::__('The payment is pending while it is being reviewed by PayPal for risk.'),
			'unilateral'     => Shopp::__('The payment is pending because it was made to an email address that is not yet registered or confirmed.'),
			'upgrade'        => Shopp::__('Contact PayPal Customer Service to see if your account needs to be upgraded.'),
			'verify'         => Shopp::__('Your account is not yet verified.'),
			'other'          => Shopp::__('Contact PayPal Customer Service to determine why payment was not completed.'),
		);

		self::$reversals = array(
			'adjustment_reversal'      => Shopp::__('Reversal of an adjustment'),
			'buyer-complaint'          => Shopp::__('Reversal on customer complaint.'),
			'buyer_complaint'          => Shopp::__('Reversal on customer complaint.'),
			'chargeback'               => Shopp::__('Reversal on chargeback.'),
			'chargeback_reimbursement' => Shopp::__('Reimbursement for a chargeback'),
			'chargeback_settlement'    => Shopp::__('Settlement of a chargeback'),
			'guarantee'                => Shopp::__('Reversal due to a money-back guarantee.'),
			'other'                    => Shopp::__('Non-specified reversal.'),
			'refund'                   => Shopp::__('Reversal by merchant refund.'),
		);

		self::$types = array(
			'chargeback'                        => Shopp::__('A credit card chargeback has occurred'),
			'adjustment'                        => Shopp::__('A dispute has been resolved and closed'),
			'cart'                              => true,
			'new_case'                          => Shopp::__('A payment dispute has been filed.'),
			'recurring_payment'                 => Shopp::__('Recurring payment received'),
			'recurring_payment_expired'         => Shopp::__('Recurring payment expired'),
			'recurring_payment_profile_created' => Shopp::__('Recurring payment profile created'),
			'recurring_payment_skipped'         => Shopp::__('Recurring payment skipped'),
			'subscr_cancel'                     => Shopp::__('Subscription canceled'),
			'subscr_eot'                        => Shopp::__('Subscription expired'),
			'subscr_failed'                     => Shopp::__('Subscription signup failed'),
			'subscr_payment'                    => Shopp::__('Subscription payment received'),
			'subscr_signup'                     => Shopp::__('Subscription started')
		);

	}

	public function captured () {
		return in_array($this->payment, array('Completed', 'Canceled_Reversal', 'Canceled-Reversal'));
	}

	public function amount () {
		return (float)abs($this->amount);
	}

	public function fees () {
		return (float)abs($this->fees);
	}

	public function order () {
		if ( empty($this->order) ) return false;
		return (int)abs($this->order);
	}

	public function payer () {
		if ( empty($this->payer) ) return false;
		return ( 'verified' == $this->payer ? Shopp::__('Payer verified') : Shopp::__('Payer unverified') );
	}

	public function reason () {
		if ( isset(self::$reasons[ $this->reason ]) )
			return self::$reasons[ $this->reason ];
		elseif ( ! empty($this->reason) )
			return self::$reasons['other'];
		return false;
	}

	public function protection () {
		if ( isset(self::$eligibility[ $this->protection ]) )
			return self::$eligibility[ $this->protection ];
		return false;
	}

	public function event () {
		if ( isset(self::$events[ $this->payment ]) )
			return self::$events[ $this->payment ];
		return false;
	}

	public function paytype () {
		switch ( $this->paytype ) {
			case 'echeck': return 'eCheck';
			default: return 'PayPal.com';
		}
	}

	public function email () {
		if ( ! empty($this->email) )
			return $this->email;
		return false;
	}

	public function type () {

		$type = strtolower($this->type);

		// chargeback types vary
		if ( false !== strpos($this->type, 'chargeback') )
			$type = 'chargeback';

		if ( isset(self::$types[ $type ]) )
			return self::$types[ $type ];

		return false;
	}

	public function txnid () {
		if ( ! empty($this->txnid) )
			return $this->txnid;
		return false;
	}

	public function txnorigin () {
		if ( ! empty($this->txnorigin) )
			return $this->txnorigin;
		return false;
	}

	public function reversal () {
		$reversal = strtolower($this->reversal);
		if ( isset(self::$reversals[ $reversal ]) )
			return self::$reversals[ $reversal ];
		return false;
	}
	public function data () {
		if ( empty($this->data) || ! is_array($this->data) ) return array();
		return $this->data;
	}

	public function valid () {

		if ( ! $this->order() ) { // boolean false and 0 are both invalid
			shopp_debug('PayPal messsage invalid. Missing or invalid "invoice" field.');
			return false;
		}

		if ( false === $this->txnid() && false === $this->txnorigin() ) {
			shopp_debug('PayPal messsage invalid. Missing txn_id or parent_txn_id.');
			return false;
		}

		return true;
	}
}
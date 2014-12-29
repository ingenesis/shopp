<?php
/**
 * 2Checkout.com
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, May 2009-2014
 * @package shopp
 * @version 1.3.4
 * @since 1.1
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class Shopp2Checkout extends GatewayFramework implements GatewayModule {

	// Settings
	public $secure = false;
	public $saleonly = true;

	// URLs
	const LIVEURL = 'https://www.2checkout.com/checkout/purchase';
	const SANDBOX = 'https://sandbox.2checkout.com/checkout/purchase';

	public function __construct () {
		parent::__construct();

		$this->setup('sid', 'verify', 'secret', 'testmode');

		add_filter('shopp_purchase_order_2checkout_processing', array($this, 'processing'));
		add_action('shopp_remote_payment', array($this, 'returned'));

	}

	public function actions () { /* Not implemented */ }

	public function processing () {
		return array($this, 'submit');
	}

	public function form ( ShoppPurchase $Purchase ) {

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);

		$Order = $this->Order;
		$Customer = $Order->Customer;
		$Billing = $Order->Billing;
		$Shipping = $Order->Shipping;

		// Build the transaction
		$_ = array();

		// Required
		$_['sid']               = $this->settings['sid'];
		$_['mode']              = '2CO';
		$_['total']             = $this->amount('total');
		$_['merchant_order_id'] = $Purchase->id;
		$_['id_type']           = 1;

		// Extras
		if ( Shopp::str_true($this->settings['testmode']) )
			$_['demo']          = "Y";

		$_['fixed']             = "Y";
		$_['skip_landing']      = "1";
		$_['currency_code']     = $this->currency();

		$_['x_receipt_link_url'] = $this->settings['returnurl'];

		// Line Items
		$i = 0;
		foreach ( $Order->Cart as $id => $Item ) {
			$_['li_' . $i . '_product_id'] = 'shopp_pid-'.$Item->product.','.$Item->quantity;
			$_['li_' . $i . '_type']       = 'product';
			$_['li_' . $i . '_name']	   = $this->itemname($Item);
			$_['li_' . $i . '_quantity']   = $Item->quantity;
			$_['li_' . $i . '_price']      = $this->amount($Item->unitprice);
			$_['li_' . $i . '_tangible']   = $Item->shipped ? 'Y' : 'N';
			$i++;
		}

		// Shipping
		$_['li_' . $i . '_type']     = 'shipping';
		$_['li_' . $i . '_name']     = Shopp::__('Shipping');
		$_['li_' . $i . '_quantity'] = 1;
		$_['li_' . $i . '_price']    = $this->amount('shipping');
		$_['li_' . $i . '_tangible'] = 'N';

		$i++;

		// Taxes
		$_['li_' . $i . '_type']     = 'tax';
		$_['li_' . $i . '_name']     = Shopp::__('Taxes');
		$_['li_' . $i . '_quantity'] = 1;
		$_['li_' . $i . '_price']    = $this->amount('tax');
		$_['li_' . $i . '_tangible'] = 'N';


		$_['card_holder_name'] 		= $Billing->name;
		$_['street_address'] 		= $Billing->address;
		$_['street_address2'] 		= $Billing->xaddress;
		$_['city'] 					= $Billing->city;
		$_['state'] 				= $Billing->state;
		$_['zip'] 					= $Billing->postcode;
		$_['country'] 				= $Billing->country;
		$_['email'] 				= $Customer->email;
		$_['phone'] 				= $Customer->phone;

		$_['ship_name'] 			= $Shipping->name;
		$_['ship_street_address'] 	= $Shipping->address;
		$_['ship_street_address2'] 	= $Shipping->xaddress;
		$_['ship_city'] 			= $Shipping->city;
		$_['ship_state'] 			= $Shipping->state;
		$_['ship_zip'] 				= $Shipping->postcode;
		$_['ship_country'] 			= $Shipping->country;

		return $this->format($_);
	}

	/**
	 * Builds a form to send the order to PayPal for processing
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string PayPal cart form
	 **/
	public function submit ( ShoppPurchase $Purchase ) {
		$id = sanitize_key( $this->module );
		$title = Shopp::__( 'Sending order to 2Checkout&hellip;' );
		$message = '<form id="' . $id . '" action="' . self::LIVEURL . '" method="POST">' .
					$this->form( $Purchase ) .
					'<h1>' . $title . '</h1>' .
					'<noscript>' .
					'<p>' . Shopp::__( 'Click the &quot;Submit Order to 2Checkout&quot; button below to submit your order to 2Checkout for payment processing:' ) . '</p>' .
					'<p><input type="submit" name="submit" value="' . Shopp::__('Submit Order to 2Checkout'). '" id="' . $id . '" /></p>' .
					'</noscript>' .
					'</form>' .
					'<script type="text/javascript">document.getElementById("' . $id . '").submit();</script></body></html>';

		wp_die( $message, $title, array( 'response' => 200 ) );
	}


	public function returned () {

		if ( $this->id() != $_GET['rmtpay'] ) return; // Not our offsite payment

		$request = array_merge(array(
			'merchant_order_id' => false,
			'key' => false,
			'order_number' => false,
			'total' => false,
			'credit_card_processed' => false,
			'invoice_id' => false,
			'pay_method' => false
		), $_GET);
		extract($request, EXTR_SKIP);

		if ( Shopp::str_true($this->settings['verify']) && ! $this->verify($key) ) {
			shopp_add_error(Shopp::__('The order submitted by 2Checkout could not be verified.'), SHOPP_TRXN_ERR);
			Shopp::redirect(Shopp::url(false, 'checkout'));
		}

		if ( empty($merchant_order_id) ) {
			shopp_add_error(Shopp::__('The order submitted by 2Checkout did not specify a transaction ID.'), SHOPP_TRXN_ERR);
			Shopp::redirect(Shopp::url(false, 'checkout'));
		}

		$Purchase = ShoppPurchase(new ShoppPurchase((int)$merchant_order_id));
		if ( ! $Purchase->exists() ) {
			shopp_add_error(Shopp::__('The order submitted by 2Checkout did not match any submitted orders.'), SHOPP_TRXN_ERR);
			Shopp::redirect(Shopp::url(false, 'checkout'));
		}

		if ( 'Y' != $credit_card_processed ) {
			shopp_add_order_event($Purchase->id, 'auth-fail', array(
				'amount' => $total, // Amount to be authorized
				'error' => 'Declined', // Error code (if provided)
				'message' => Shopp::__('The payment was not completed succesfully'), // Error message reported by the gateway
				'gateway' => $this->module, // The gateway module name
			));
			shopp_add_error(Shopp::__('The order submitted by 2Checkout did not match any submitted orders.'), SHOPP_TRXN_ERR);
			Shopp::redirect(Shopp::url(false, 'checkout'));
		}


		$this->Order->inprogress = $Purchase->id;
		add_action( 'shopp_authed_order_event', array( ShoppOrder(), 'notify' ) );
		add_action( 'shopp_authed_order_event', array( ShoppOrder(), 'accounts' ) );
		add_action( 'shopp_authed_order_event', array( ShoppOrder(), 'success' ) );

		shopp_add_order_event($Purchase->id, 'authed', array(
			'txnid' => $order_number,   // Transaction ID
			'amount' => (float)$total,  // Gross amount authorized
			'fees' => false,            // Fees associated with transaction
			'gateway' => $this->module, // The gateway module name
			'paymethod' => '2Checkout', // Payment method (payment method label from payment settings)
			'paytype' => $pay_method,   // Type of payment (check, MasterCard, etc)
			'payid' => $invoice_id,     // Payment ID (last 4 of card or check number or other payment id)
			'capture' => true           // Capture flag
		));

		Shopp::redirect( Shopp::url(false, 'thanks', false) );
	}

	public function authed ( ShoppPurchase $Order ) {

		$Paymethod = $this->Order->paymethod();
		$Billing = $this->Order->Billing;

		shopp_add_order_event($Order->id, 'authed', array(
			'txnid' => $_POST['order_number'],						// Transaction ID
			'amount' => $_POST['total'],							// Gross amount authorized
			'gateway' => $this->module,								// Gateway handler name (module name from @subpackage)
			'paymethod' => $Paymethod->label,						// Payment method (payment method label from payment settings)
			'paytype' => $Billing->cardtype,						// Type of payment (check, MasterCard, etc)
			'payid' => $Billing->card,								// Payment ID (last 4 of card or check number)
			'capture' => true										// Capture flag
		));

	}

	protected function verify ( $key ) {
		if ( Shopp::str_true($this->settings['testmode']) ) return true;
		$order = $_GET['order_number'];
		$total = $_GET['total'];

		$verification = strtoupper(md5($this->settings['secret'] .
							$this->settings['sid'] .
							$order .
							$total
						));

		return ( $verification == $key );
	}

	protected function returnurl () {
		return add_query_arg('rmtpay', $this->id(), Shopp::url(false, 'thanks'));
	}

	protected function itemname ( $Item ) {
		$name = $Item->name . ( empty($Item->option->label) ? '' : ' ' . $Item->option->label );
		$name = str_replace(array('<', '>'), '', $name);
		return substr($name, 0, 128);
	}

	public function settings () {

		$this->ui->text(0,array(
			'name' => 'sid',
			'size' => 10,
			'value' => $this->settings['sid'],
			'label' => __('Your 2Checkout vendor account number.','Shopp')
		));


		$this->ui->checkbox(0,array(
			'name' => 'verify',
			'checked' => $this->settings['verify'],
			'label' => __('Enable order verification','Shopp')
		));

		$this->ui->text(0,array(
			'name' => 'secret',
			'size' => 10,
			'value' => $this->settings['secret'],
			'label' => __('Your 2Checkout secret word for order verification.','Shopp')
		));

		$this->ui->checkbox(0,array(
			'name' => 'testmode',
			'checked' => $this->settings['testmode'],
			'label' => __('Enable test mode','Shopp')
		));

		$this->ui->text(1, array(
			'name' => 'returnurl',
			'size' => 64,
			'value' => $this->returnurl(),
			'readonly' => 'readonly',
			'class' => 'selectall',
			'label' => __('Copy as the <strong>Approved URL</strong> & <strong>Pending URL</strong> in your 2Checkout Vendor Area under the <strong>Account &rarr; Site Management</strong> settings page.','Shopp')
		));

		$script = "var tc ='shopp2checkout';jQuery(document).bind(tc+'Settings',function(){var $=jQuery,p='#'+tc+'-',v=$(p+'verify'),t=$(p+'secret');v.change(function(){v.prop('checked')?t.parent().fadeIn('fast'):t.parent().hide();}).change();});";
		$this->ui->behaviors( $script );
	}

}
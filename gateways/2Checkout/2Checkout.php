<?php
/**
 * 2Checkout.com
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, 27 May, 2009
 * @package shopp
 * @version 1.3
 * @since 1.1
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class Shopp2Checkout extends GatewayFramework implements GatewayModule {

	// Settings
	public $secure = false;
	public $saleonly = true;
	public $precision = 2;
	public $decimals = '.';
	public $thousands = '';

	// URLs
	public $url = 'https://www.2checkout.com/checkout/purchase';	// Multi-page checkout
	public $surl = 'https://www.2checkout.com/checkout/spurchase'; // Single-page, CC-only checkout

	public function __construct () {
		parent::__construct();

		$this->setup('sid','verify','secret','returnurl','testmode','singlepage');

		$this->settings['returnurl'] = add_query_arg('rmtpay','process',Shopp::url(false,'thanks',false));

		// add_action('shopp_txn_update',array(&$this,'notifications'));
		add_action('shopp__2checkout_sale', array($this,'sale'));

	}

	public function actions () {
		add_action('shopp_process_checkout', array(&$this,'checkout'),9);
		add_action('shopp_init_checkout',array(&$this,'init'));

		add_action('shopp_init_confirmation',array(&$this,'confirmation'));
		add_action('shopp_remote_payment',array(&$this,'returned'));
	}

	public function confirmation () {
		add_filter('shopp_confirm_url',array(&$this,'url'));
		add_filter('shopp_confirm_form',array(&$this,'form'));
	}

	public function checkout () {
		$this->Order->confirm = true;
	}

	public function url ($url) {
		if ($this->settings['singlepage'] == "on") return $this->surl;
		return $this->url;
	}

	public function form ($form) {

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$next = DB::query("SELECT IF ((MAX(id)) > 0,(MAX(id)+1),1) AS id FROM $purchasetable LIMIT 1");

		$Order = $this->Order;
		$Customer = $Order->Customer;
		$Billing = $Order->Billing;
		$Shipping = $Order->Shipping;
		$Order->_2COcart_order_id = date('mdy').'-'.date('His').'-'.$next->id;

		// Build the transaction
		$_ = array();

		// Required
		$_['sid']				= $this->settings['sid'];
		$_['total']				= $this->amount('total');
		$_['cart_order_id']		= $Order->_2COcart_order_id;
		$_['vendor_order_id']	= $this->session;
		$_['id_type']			= 1;

		// Extras
		if ($this->settings['testmode'] == "on")
			$_['demo']			= "Y";

		$_['fixed'] 			= "Y";
		$_['skip_landing'] 		= "1";

		$_['x_Receipt_Link_URL'] = $this->settings['returnurl'];

		// Line Items
		foreach($this->Order->Cart->contents as $i => $Item) {
			$id = $i+1;
			$_['c_prod_'.$id]			= 'shopp_pid-'.$Item->product.','.$Item->quantity;
			$_['c_name_'.$id]			= $Item->name;
			$_['c_description_'.$id]	= !empty($Item->option->label)?$Item->option->label:'';
			$_['c_price_'.$id]			= $this->amount($Item->unitprice);

		}

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

		return $form.$this->format($_);
	}

	public function returned () {

		if (Shopp::str_true($this->settings['verify']) && !$this->verify($_POST['key'])) {
			new ShoppError(__('The order submitted to 2Checkout could not be verified.','Shopp'),'2co_validation_error',SHOPP_TRXN_ERR);
			shopp_redirect(Shopp::url(false,'checkout'));

		}

		if (empty($_POST['order_number'])) {
			new ShoppError(__('The order submitted by 2Checkout did not specify a transaction ID.','Shopp'),'2co_validation_error',SHOPP_TRXN_ERR);
			shopp_redirect(Shopp::url(false,'checkout'));
		}

		// Create the order and begin processing it
		shopp_add_order_event(false, 'purchase', array(
			'gateway' => $this->module,
			'txnid' => $_POST['order_number']
		));

		ShoppOrder()->purchase = ShoppPurchase()->id;
		shopp_redirect(Shopp::url(false,'thanks',false));

	}

	public function sale ($Event) {

		$Paymethod = $this->Order->paymethod();
		$Billing = $this->Order->Billing;

		shopp_add_order_event($Event->order, 'authed', array(
			'txnid' => $_POST['order_number'],						// Transaction ID
			'amount' => $_POST['total'],							// Gross amount authorized
			'gateway' => $this->module,								// Gateway handler name (module name from @subpackage)
			'paymethod' => $Paymethod->label,						// Payment method (payment method label from payment settings)
			'paytype' => $Billing->cardtype,						// Type of payment (check, MasterCard, etc)
			'payid' => $Billing->card,								// Payment ID (last 4 of card or check number)
			'capture' => true										// Capture flag
		));

	}

	public function notification () {
		// INS updates not implemented
	}

	public function verify ($key) {
		if ( Shopp::str_true($this->settings['testmode']) ) return true;
		$order = $_POST['order_number'];

		$verification = strtoupper(md5($this->settings['secret'].
							$this->settings['sid'].
							$order.
							$this->amount($this->Order->Cart->Totals->total))
						);

		return ($verification == $key);
	}

	public function settings () {

		$this->ui->text(0,array(
			'name' => 'sid',
			'size' => 10,
			'value' => $this->settings['sid'],
			'label' => __('Your 2Checkout vendor account number.','Shopp')
		));

		$this->ui->text(0,array(
			'name' => 'returnurl',
			'size' => 40,
			'value' => $this->settings['returnurl'],
			'readonly' => 'readonly',
			'classes' => 'selectall',
			'label' => __('Copy as the <strong>Approved URL</strong> & <strong>Pending URL</strong> in your 2Checkout Vendor Area under the <strong>Account &rarr; Site Management</strong> settings page.','Shopp')
		));

		$this->ui->checkbox(1,array(
			'name' => 'testmode',
			'checked' => $this->settings['testmode'],
			'label' => __('Enable test mode','Shopp')
		));

		$this->ui->checkbox(1,array(
			'name' => 'singlepage',
			'checked' => $this->settings['singlepage'],
			'label' => __('Single-page, credit card only checkout','Shopp')
		));

		$this->ui->checkbox(1,array(
			'name' => 'verify',
			'checked' => $this->settings['verify'],
			'label' => __('Enable order verification','Shopp')
		));

		$this->ui->text(1,array(
			'name' => 'secret',
			'size' => 10,
			'value' => $this->settings['secret'],
			'label' => __('Your 2Checkout secret word for order verification.','Shopp')
		));

		$this->ui->p(1,array(
			'name' => 'returnurl',
			'size' => 40,
			'value' => $this->settings['returnurl'],
			'readonly' => 'readonly',
			'classes' => 'selectall',
			'content' => '<span style="width: 300px;">&nbsp;</span>'
		));

		$this->ui->behaviors( $this->secretjs() );
	}

	public function secretjs () {
		ob_start(); ?>
jQuery(document).bind('_2checkoutSettings',function () {
	var $=jqnc(),p='#_2checkout-',v=$(p+'verify'),s=$(p+'secret');
	v.change(function () { v.attr('checked') ? s.parent().fadeIn('fast') : s.parent().hide(); }).change();
});
<?php
		$script = ob_get_contents(); ob_end_clean();
		return $script;
	}

}
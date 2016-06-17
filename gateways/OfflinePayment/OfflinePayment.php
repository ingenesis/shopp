<?php
/**
 * Offline Payment
 *
 * Provides offline payment handling
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, April, 2008
 * @package shopp
 * @version 1.3
 * @since 1.3
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppOfflinePayment extends GatewayFramework implements GatewayModule {

	public $secure = false;		// SSL not required
	public $authonly = true;	// Auth only transactions
	public $multi = true;		// Support multiple methods
	public $captures = true;	// Supports Auth-only
	public $refunds = true;		// Supports refunds

	public $methods = array(); // List of active OfflinePayment payment methods

	/**
	 * Setup the Offline Payment module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		parent::__construct();

		// Reset the index count to shift setting indices so we don't break the JS environment
		if ( isset($this->settings['label']) && is_array($this->settings['label']) )
			$this->settings['label'] = array_merge(array(), $this->settings['label']);
		if ( isset($this->settings['instructions']) && is_array($this->settings['instructions']) )
		$this->settings['instructions'] = array_merge(array(), $this->settings['instructions']);

		// Scan and build a runtime index of active payment methods
		if ( isset($this->settings['label']) && is_array($this->settings['label']) ) {
			foreach ( $this->settings['label'] as $i => $entry )
				if ( isset($this->settings['instructions']) && isset($this->settings['instructions'][ $i ]) )
					$this->methods[ $entry ] = $this->settings['instructions'][ $i ];
		}

		add_filter('shopp_themeapi_checkout_offlineinstructions', array($this, 'instructions'), 10, 2);

		add_action('shopp_offlinepayment_sale', array($this, 'auth')); // Process sales as auth-only
		add_action('shopp_offlinepayment_auth', array($this, 'auth'));
		add_action('shopp_offlinepayment_capture', array($this, 'capture'));
		add_action('shopp_offlinepayment_refund', array($this, 'refund'));
		add_action('shopp_offlinepayment_void', array($this, 'void'));

	}

	public function actions () { /* Not Implemented */ }

	/**
	 * Process the order
	 *
	 * Process the order but leave it in PENDING status.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function auth ( $Event ) {
		$Order = $this->Order;
		$Billing = $Order->Billing;
		$Paymethod = $Order->Payments->selected();

		shopp_add_order_event($Event->order, 'authed', array(
			'txnid' => time(),
			'amount' => $this->amount('total'),
			'fees' => 0,
			'gateway' => $Paymethod->processor,
			'paymethod' => $Paymethod->label,
			'paytype' => $Billing->cardtype,
			'payid' => $Billing->card
		));
	}

	public function capture ( $Event ) {
		shopp_add_order_event($Event->order, 'captured', array(
			'txnid' => time(),			// Transaction ID of the CAPTURE event
			'amount' => $Event->amount,	// Amount captured
			'fees' => 0,
			'gateway' => $this->module	// Gateway handler name (module name from @subpackage)
		));
	}

	public function refund ( $Event ) {
		shopp_add_order_event($Event->order, 'refunded', array(
			'txnid' => time(),			// Transaction ID for the REFUND event
			'amount' => $Event->amount,	// Amount refunded
			'gateway' => $this->module	// Gateway handler name (module name from @subpackage)
		));
	}

	public function void ( $Event ) {
		shopp_add_order_event($Event->order, 'voided', array(
			'txnorigin' => $Event->txnid,	// Original transaction ID (txnid of original Purchase record)
			'txnid' => time(),				// Transaction ID for the VOID event
			'gateway' => $this->module		// Gateway handler name (module name from @subpackage)
		));
	}

	/**
	 * Render the settings for this gateway
	 *
	 * Uses ModuleSettingsUI to generate a JavaScript/jQuery based settings
	 * panel.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function settings () {

		$instructions = isset($this->settings['instructions']) ? $this->settings['instructions'] : '';

		$this->ui->textarea(0,array(
			'name' => 'instructions',
			'value' => stripslashes_deep($instructions)
		));

		$this->ui->p(1,array(
			'name' => 'help',
			'label' => __('Offline Payment Instructions', 'Shopp'),
			'content' => __('Use this area to provide your customers with instructions on how to make payments offline.', 'Shopp')
		));

	}

	/**
	 * Adds shopp('checkout.offlineinstructions') Theme API support
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function instructions ( $result, $options ) {
		add_filter('shopp_offline_payment_instructions', 'stripslashes');
		add_filter('shopp_offline_payment_instructions', 'wptexturize');
		add_filter('shopp_offline_payment_instructions', 'convert_chars');
		add_filter('shopp_offline_payment_instructions', 'wpautop');

		$paymethod = shopp('purchase','get-paymethod');
		$Payments = ShoppOrder()->Payments;

		if ( ! $Payments->exists($paymethod) ) return false;

		$Paymethod = $Payments->get($paymethod);
		list($module, $id) = explode('-', $Paymethod->setting);

		if ( ! isset($this->settings[ $id ]) ) return false;

		$settings = $this->settings[ $id ];

		if ( ! empty($settings['instructions']) )
			return apply_filters('shopp_offline_payment_instructions', $settings['instructions']);

		return false;
	}

	public function methods ( $methods ) {
		return $methods + (count($this->methods) - 1);
	}

}
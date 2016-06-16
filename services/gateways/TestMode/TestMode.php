<?php
/**
 * Test Mode
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, July 2011
 * @package shopp
 * @version 1.2
 * @since 1.1
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppTestMode extends GatewayFramework implements GatewayModule {

	public $secure = false;									// SSL not required

	public $refunds = true;
	public $captures = true;
	public $cards = array('visa', 'mc', 'disc', 'amex');	// Supported cards

	/**
	 * Setup the TestMode gateway
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		parent::__construct();
		$this->setup('cards', 'error');

		// Autoset useable payment cards
		$this->settings['cards'] = array();
		foreach ( $this->cards as $card )
			$this->settings['cards'][] = $card->symbol;

		add_action('shopp_testmode_sale', array($this, 'sale'));
		add_action('shopp_testmode_auth', array($this, 'auth'));
		add_action('shopp_testmode_capture', array($this, 'capture'));
		add_action('shopp_testmode_refund', array($this, 'refund'));
		add_action('shopp_testmode_void', array($this, 'void'));
	}

	/**
	 * Process the order
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function process () {
		// If the error option is checked, always generate an error
		if (Shopp::str_true($this->settings['error']))
			return new ShoppError(__("This is an example error message. Disable the 'always show an error' setting to stop displaying this error.", "Shopp"), 'test_mode_error', SHOPP_TRXN_ERR);
		return true;
	}

	public function sale ( OrderEventMessage $Event ) {
		$this->handler('authed', $Event);
		$this->handler('captured', $Event);
	}

	public function auth ( OrderEventMessage $Event ) {
		$this->handler('authed', $Event);
	}

	public function capture ( OrderEventMessage $Event ) {
		$this->handler('captured', $Event);
	}

	public function refund ( OrderEventMessage $Event ) {
		$this->handler('refunded', $Event);
	}

	public function void ( OrderEventMessage $Event ) {
		$this->handler('voided', $Event);
	}

	public function handler ( $type, OrderEventMessage $Event ) {
		if( ! isset($Event->txnid) || empty($Event->txnid) ) $Event->txnid = time();
		if ( Shopp::str_true($this->settings['error']) ) {
			$error = Shopp::__("This is an example error message. Disable the 'always show an error' setting to stop displaying this error.");
			new ShoppError($error, 'testmode_error', SHOPP_TRXN_ERR);
			return shopp_add_order_event($Event->order, $Event->type . '-fail', array(
				'amount' => $Event->amount,
				'error' => 0,
				'message' => $error,
				'gateway' => $this->module
			));
		}

		shopp_add_order_event($Event->order, $type, array(
			'txnid' => $Event->txnid,
			'txnorigin' => $Event->txnid,
			'fees' => 0,
			'paymethod' => '',
			'paytype' => '',
			'payid' => '1111',
			'amount' => $Event->amount,
			'gateway' => $this->module
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
		$this->ui->checkbox(0, array(
			'name' => 'error',
			'label' => 'Always show an error',
			'checked' => $this->settings['error']
		));
	}

	public static function help () {
		return Shopp::_m(
'### Test Mode

The Test Mode payment gateway provides a simple method for testing your WordPress/Shopp setup. With Test Mode set as your primary payment gateway, Shopp will process each checkout request as a normal order, but it will not verify any payment card information.

Note: It is important to realize that the Test Mode payment gateway is different from the **Enable test mode** setting available on many of the other payment systems. As a payment gateway, it acts like a payment processor without verifying any payment information or processing any transfer of money.

#### Test error response

The Test Mode payment gateway module has one setting for toggling errors on or off. By default the Test Mode gateway treats every order as a success. Simply toggle on the Test error response setting and the Test Mode gateway will report an error for every order it processes.');

	}

}
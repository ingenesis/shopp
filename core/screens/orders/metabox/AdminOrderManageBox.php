<?php
/**
 * AdminOrderManageBox.php
 *
 * Renders the order billing address metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderManageBox extends ShoppAdminMetabox {

	protected $id = 'order-manage';
	protected $view = 'orders/manage.php';

	protected function title() {
		return Shopp::__('Management');
	}

	public function references() {
		$Purchase = $this->references['Purchase'];
		$Gateway = $Purchase->gateway();
		
		$this->references['gateway_name'] = $Gateway ? $Gateway->name : '';
		$this->references['gateway_module'] = $Gateway ? $Gateway->module : '';
		$this->references['gateway_refunds'] = $Gateway ? $Gateway->refunds : false;
		$this->references['gateway_captures'] = $Gateway ? $Gateway->captures : false;

		$carriers = $this->Screen->shipcarriers();
		$menu = array();
		foreach ( $carriers as $id => $entry )
			$menu[ $id ] = $entry[0];

		$this->references['carriers_menu'] = $menu;
	}

	protected function init() {
		$Purchase = $this->references['Purchase'];
		$Purchase->load_events();
	}

	protected function ops() {
		return array(
			'shipnotice',
			'refund',
			'cancel',
			'charge'
		);
	}

	public function shipnotice() {
		$Purchase = $this->references['Purchase'];
		if ( ! $shipments = $this->form('shipment') ) return;

		foreach ( (array) $shipments as $shipment ) {
			shopp_add_order_event($Purchase->id, 'shipped', array(
				'tracking' => $shipment['tracking'],
				'carrier' => $shipment['carrier']
			));
		}

		$this->notice(Shopp::__('Shipping notice sent.'));

		// Save shipping carrier default preference for the user
		$userid = get_current_user_id();
		$setting = 'shopp_shipping_carrier';
		if ( ! get_user_meta($userid, $setting, true) )
			add_user_meta($userid, $setting, $shipment['carrier']);
		else update_user_meta($userid, $setting, $shipment['carrier']);

	}

	public function refund() {
		if ( 'refund' != $this->form('order-action') ) return;

		if ( ! current_user_can('shopp_refund') )
			wp_die(Shopp::__('You do not have sufficient permissions to carry out this action.'));

		extract($this->references);
		$Purchase = $this->references['Purchase'];
		$gateway_module = $this->references['gateway_module'];
		
		$user = wp_get_current_user();
		$reason = (int)$this->form('reason');
		$amount = Shopp::floatval($this->form('amount'));

		if ( $this->form('message') )
			$Purchase->message['note'] = $this->form('message');

		if ( Shopp::str_true($this->form('send')) ) {

			// Submit the refund request to the payment gateway
			shopp_add_order_event($Purchase->id, 'refund', array(
				'txnid'   => $Purchase->txnid,
				'gateway' => $gateway_module,
				'amount'  => $amount,
				'reason'  => $reason,
				'user'	=> $user->ID
			));

		} else {

			// Force the order status to be refunded (without talking to the gateway)

			// Email a refund notice to the customer
			shopp_add_order_event($Purchase->id, 'notice', array(
				'user'   => $user->ID,
				'kind'   => 'refunded',
				'notice' => Shopp::__('Marked Refunded')
			));

			// Log the refund event
			shopp_add_order_event($Purchase->id, 'refunded', array(
				'txnid'   => $Purchase->txnid,
				'amount'  => $amount,
				'gateway' => $Gateway->module

			));

			// Cancel the order
			shopp_add_order_event($Purchase->id, 'voided', array(
				'gateway'   => $Gateway->module,
				'txnorigin' => $Purchase->txnid,
				'txnid'	 => current_time('timestamp')
			));

			$this->notice(Shopp::__('Order marked refunded.'));

		}

	}

	public function cancel() {
		if ( 'cancel' != $this->form('order-action') ) return;

		if ( ! current_user_can('shopp_void') )
			wp_die(Shopp::__('You do not have sufficient permissions to carry out this action.'));

		extract($this->references);

		// unset($_POST['refund-order']);
		$user = wp_get_current_user();
		$reason = (int)$_POST['reason'];

		$message = '';
		if ( $message = $this->form('message') )
			$Purchase->message['note'] = $message;

		if ( Shopp::str_true($this->form('send')) ) {

			// Submit the void request to the payment gateway
			shopp_add_order_event($Purchase->id, 'void', array(
				'gateway' => $Gateway->module,
				'txnid'   => $Purchase->txnid,
				'reason'  => $reason,
				'user'	=> $user->ID,
				'note'	=> $message
			));

		} else {

			// Force the order status to be cancelled (without talking to the gateway)

			// Email a notice to the customer
			shopp_add_order_event($Purchase->id, 'notice', array(
				'user'   => $user->ID,
				'kind'   => 'cancelled',
				'notice' => Shopp::__('Marked Cancelled')
			));

			// Cancel the order
			shopp_add_order_event($Purchase->id, 'voided', array(
				'gateway' => $Gateway->module,
				'txnorigin' => $Purchase->txnid,
				'txnid' => current_time('timestamp'),
			));

		}
	}

	public function charge() {
		if ( ! $this->form('charge') ) return;

		$Purchase = $this->references['Purchase'];
		$gateway_captures = $this->references['gateway_captures'];
				
		if ( ! $gateway_captures ) return;

		if ( ! current_user_can('shopp_capture') )
			wp_die(Shopp::__('You do not have sufficient permissions to carry out this action.'));

		$user = wp_get_current_user();

		shopp_add_order_event($Purchase->id, 'capture', array(
			'txnid'   => $Purchase->txnid,
			'gateway' => $Purchase->gateway,
			'amount'  => $Purchase->capturable(),
			'user'	=> $user->ID
		));
	}

}
<?php
/**
 * AdminOrderContactBox.php
 *
 * Renders the order data metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderContactBox extends ShoppAdminMetabox {

	protected $id = 'order-contact';
	protected $view = 'orders/contact.php';

	protected function title() {
		return Shopp::__('Customer');
	}

	protected function ops() {
		return array(
			'updates',
			'reassign',
			'add',
			'unedit'
		);
	}

	public function updates() {
		if ( 'update-customer' != $this->form('order-action') ) return;
		if ( ! $updates = $this->form('customer') ) return;
		if ( ! is_array($updates) ) return;

		extract($this->references, EXTR_SKIP);
		$Purchase->updates($updates);
		$Purchase->save();
	}


	public function reassign() {
		if ( 'change-customer' != $this->form('order-action') ) return;

		$Customer = new ShoppCustomer((int)$this->request('customerid'));
		if ( ! $Customer->exists() )
			return $this->notice(Shopp::__('The selected customer was not found.'), 'error');

		extract($this->references, EXTR_SKIP);

		$Purchase->copydata($Customer);
		$Purchase->customer = $Customer->id;
		$Purchase->save();
	}

	public function add() {
		if ( 'new-customer' != $this->form('order-action') ) return;

		$updates = $this->form('customer');
		if ( ! ( $updates || is_array($updates) ) ) return;

		extract($this->references, EXTR_SKIP);

		// Create the new customer record
		$Customer = new ShoppCustomer();
		$Customer->updates($updates);
		$Customer->password = wp_generate_password(12, true);

		if ( 'wordpress' == shopp_setting('account_system') )
			$Customer->create_wpuser();
		else unset($this->form['loginname']);

		$Customer->save();

		if ( ! $Customer->exists() )
			return $this->notice(Shopp::__('An unknown error occurred. The customer could not be created.'), 'error');

		$Purchase->customer = $Customer->id;
		$Purchase->copydata($Customer);
		$Purchase->save();

		// Create a new billing address record for the new customer
		$billing = $this->form('billing');
		if ( is_array($billing) && empty($billing['id']) ) {
			$Billing = new BillingAddress($billing);
			$Billing->customer = $Customer->id;
			$Billing->save();
		}

		// Create a new shipping address record for the new customer
		$shipping = $this->form('shipping');
		if ( is_array($shipping) && empty($shipping['id']) ) {
			$Shipping = new ShippingAddress($shipping);
			$Shipping->customer = $Customer->id;
			$Shipping->save();
		}
	}

	public function unedit() {
		if ( ! $this->form('cancel-edit-customer') ) return;
		unset($this->form['order-action'], $this->form['edit-customer'], $this->form['select-customer']);
	}


}
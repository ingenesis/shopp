<?php
/**
 * AdminOrderShippingAddressBox.php
 *
 * Renders the order shipping address metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderShippingAddressBox extends ShoppAdminOrderBillingAddressBox {

	protected $id = 'order-shipping';
	protected $view = 'orders/shipping.php';
	protected $type = 'shipping';

	protected $Purchase = false;

	protected function title() {
		return Shopp::__('Shipping Address');
	}

	public function updates() {

		if ( ! $shipping = $this->form('shipping') ) return;
		if ( ! is_array($shipping) ) return;

		extract($this->references, EXTR_SKIP);

		$updates = array();
		foreach ( $shipping as $name => $value )
			$updates[ "ship$name" ] = $value;

		$Purchase->updates($updates);
		$Purchase->shipname = $updates['shipfirstname'] . ' ' . $updates['shiplastname'];
		$Purchase->save();

		$this->notice(Shopp::__('Shipping address updated.'));

	}

	public function has_address() {
		$Purchase = $this->Purchase;
		$address = $Purchase->shipaddress . $Purchase->shipxaddress;
		return ! ( empty($address)
								|| empty($Purchase->shipcity)
								|| empty($Purchase->shippostcode)
								|| empty($Purchase->shipcountry)
		);
	}

	public function data() {
		$Purchase = $this->Purchase;
		$names = explode(' ', $Purchase->shipname);

		$firstname = array_shift($names);
		$lastname = join(' ', $names);

		if ( empty($Purchase->_shipping_states) && ! empty($Purchase->shipstate) )
			$statemenu = array($Purchase->shipstate => $Purchase->shipstate);
		else Shopp::menuoptions($Purchase->_shipping_states, $Purchase->shipstate, true);

		return array(
			'${type}' => 'shipping',
			'${firstname}' => $firstname,
			'${lastname}' => $lastname,
			'${address}' => $Purchase->shipaddress,
			'${xaddress}' => $Purchase->shipxaddress,
			'${city}' => $Purchase->shipcity,
			'${state}' => $Purchase->shipstate,
			'${postcode}' => $Purchase->shippostcode,
			'${country}' => $Purchase->shipcountry,
			'${statemenu}' => $statemenu,
			'${countrymenu}' => Shopp::menuoptions($Purchase->_countries, $Purchase->shipcountry, true)
		);
	}


}
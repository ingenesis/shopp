<?php
/**
 * AdminOrderBillingAddressBox.php
 *
 * Renders the order billing address metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderBillingAddressBox extends ShoppAdminMetabox {

	protected $id = 'order-billing';
	protected $view = 'orders/billing.php';
	protected $type = 'billing';
	protected $Purchase = false;

	protected function title() {
		return Shopp::__('Billing Address');
	}

	protected function references() {
		$this->references['targets'] = shopp_setting('target_markets');
	}

	protected function ops () {
		return array('updates');
	}

	public function updates() {
		if ( ! $billing = $this->form('billing') ) return;
		if ( ! is_array($billing) ) return;

		extract($this->references, EXTR_SKIP);

		$Purchase->updates($billing);
		$Purchase->save();

		$this->notice(Shopp::__('Updated billing address.'));
	}

	public function purchase( ShoppPurchase $Purchase = null ) {
		if ( isset($Purchase) )
			$this->Purchase = $Purchase;
	}

	public function editor() {
		$type = $this->type;
		$Purchase = $this->Purchase;

		ob_start();
		include $this->ui('orders/address.php', array($Purchase, $type));
		return ob_get_clean();
	}

	public function editing() {
		return isset($_POST['edit-' . $this->type . '-address']) || ! $this->has_address();
	}

	public function has_address() {
		$Purchase = $this->Purchase;
		$address = $Purchase->address . $Purchase->xaddress;
		return ! ( empty($address)
				|| empty($Purchase->city)
				|| empty($Purchase->postcode)
				|| empty($Purchase->country)
		);
	}

	public function data() {
		$Purchase = $this->Purchase;

		if ( empty($Purchase->_billing_states) && ! empty($Purchase->state) )
			$statemenu = array($Purchase->state => $Purchase->state);
		else Shopp::menuoptions($Purchase->_billing_states, $Purchase->state, true);

		return array(
			'${action}' => 'update-address',
			'${type}' => 'billing',
			'${firstname}' => $Purchase->firstname,
			'${lastname}' => $Purchase->lastname,
			'${address}' => $Purchase->address,
			'${xaddress}' => $Purchase->xaddress,
			'${city}' => $Purchase->city,
			'${state}' => $Purchase->state,
			'${postcode}' => $Purchase->postcode,
			'${country}' => $Purchase->country,
			'${statemenu}' => $statemenu,
			'${countrymenu}' => Shopp::menuoptions($Purchase->_countries, $Purchase->country, true)
		);
	}

	public function json( array $data = array() ) {
		$data = preg_replace('/\${([-\w]+)}/', '$1', json_encode($data));
		shopp_custom_script('orders', 'address["' . $this->type . '"] = ' . $data . ';');
	}

}
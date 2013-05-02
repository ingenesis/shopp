<?php
/**
 * Payments.php
 *
 * Payment option collection and selection logic controller
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage order
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppPayments
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1.0
 * @package payments
 **/
class ShoppPayments extends ListFramework {

	private $cards = array();
	private $processors = array();
	private $selected = false;
	private $userset = false;
	private $secure = false;

	/**
	 * Builds a list of payment method options
	 *
	 * @author Jonathan Davis
	 * @since 1.1.5
	 *
	 * @return void
	 **/
	public function options () {

		$options = array();
		$accepted = array();

		$gateways = explode(',', shopp_setting('active_gateways'));

		foreach ($gateways as $gateway) {
			$id	= false;

			if ( false !== strpos($gateway, '-') )
				list($module, $id) = explode('-', $gateway);
			else $module = $gateway;

			$GatewayModule = $this->modules($module);

			if ( ! $GatewayModule ) continue;

			if ( $GatewayModule->secure ) $this->secure = true;

			$settings = $GatewayModule->settings;

			if ( false !== $id && isset($settings[ $id ]) )
				$settings = $settings[ $id ];

			$slug = sanitize_title_with_dashes($settings['label']);
			$PaymentOption = new ShoppPaymentOption(
				$slug,
				$settings['label'],
				$GatewayModule->module,
				$gateway,
				array_keys($GatewayModule->cards())
			);

			$options[ $slug ] = $PaymentOption;
			$processors[ $PaymentOption->processor ] = $slug;
			$accepted = array_merge($accepted, $GatewayModule->cards());
		}

		$this->populate($options);
		$this->cards = $accepted;
		$this->processors = $processors;

		// Always include FreeOrder in the list of available payment processors
		$this->processors['FreeOrder'] = 'freeorder';

	}

	public function request () {
		if ( ! isset($_POST['paymethod']) ) return;
		if ( 'freeorder' == $_POST['paymethod'] ) return; // Ah, ah, ah! Shoppers can't just select free order processing

		$selected = $this->selected($_POST['paymethod']);
		if ( ! $this->modules($selected->processor) )
			shopp_add_error(__('The payment method you selected is no longer available. Please choose another.','Shopp'));

		if ( $selected ) $this->userset = true;

		unset($_POST['paymethod']); // Prevent unnecessary reprocessing on subsequent calls
	}

	public function initial () {

		if ( $this->count() == 0 ) return false;

		$this->rewind();
		$selected = $this->key();

		return $this->selected($selected);

	}

	public function selected ( string $selection = null ) {

		if ( isset($selection) ) {
			if ( $this->exists($selection) )
				$this->selected = $selection;
		}

		if ( ! $this->exists($this->selected) )
			$this->initial();

		if ( $this->exists($this->selected) )
			return $this->get($this->selected);

		return false;
	}

	public function processor ( string $processor = null ) {

		$selected = $this->selected();

		if ( isset($processor) && isset($this->processors[ $processor ]) ) {
			$selection = $this->processors[ $processor ];
			$selected = $this->selected($selection);
		}

		if ( ! ($selected || $this->modules($selected->processor)) )
			$selected = $this->initial();

		if ( ! $selected ) {
			if ( isset($_POST['checkout']) )
				shopp_add_error( Lookup::errors('gateway','nogateways') );
			return false;
		}

		return $selected->processor;
	}

	public function accepted () {
		return $this->cards;
	}

	public function userset () {
		return $this->userset;
	}

	public function secure () {
		return $this->secure;
	}

	private function modules ( string $module = null ) {
		global $Shopp;

		if ( is_null($module) ) return $Shopp->Gateways->active;

		if ( isset($Shopp->Gateways->active[ $module ]) )
			return $Shopp->Gateways->active[ $module ];
		else return false;
	}

	private static function freeorder () {
		global $Shopp;
		$Module = $Shopp->Gateways->freeorder;
		return new ShoppPaymentOption(
			'freeorder',
			$Module->name,
			$Module->module,
			false,
			false
		);
	}


} // end ShoppPayments

class ShoppPaymentOption extends AutoObjectFramework {

	public $slug;
	public $label;
	public $processor;
	public $setting;
	public $cards;

}
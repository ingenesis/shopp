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
 * The Payments subsystem deals with several layers of the payment
 * handling. Each of these layers have different terms that represent
 * similar but different aspects of the payment system.
 *
 * Method - roughly analagous to the payment setting configured in Shopp > System > Payments
 * Option - The payment option shown to customers
 * Module - The payment gateway processing module (the Shopp addon or processing class)
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

	public function __construct () {
		Shopping::restore('paymethod', $this->selected);
		Shopping::restore('payselected', $this->userset);
	}

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
		$processors = array();

		$gateways = explode(',', shopp_setting('active_gateways'));

		foreach ( $gateways as $gateway ) {
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

	}

	/**
	 * Processes payment method selection changes by the shopper
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function request () {

		if ( ! isset($_POST['paymethod']) ) return;
		if ( 'freeorder' == $_POST['paymethod'] ) return; // Ah, ah, ah! Shoppers can't just select free order processing

		$selected = $this->selected($_POST['paymethod']);
		if ( ! $this->modules($selected->processor) )
			shopp_add_error(__('The payment method you selected is no longer available. Please choose another.','Shopp'));

		if ( $selected ) $this->userset = true;

		unset($_POST['paymethod']); // Prevent unnecessary reprocessing on subsequent calls
	}

	/**
	 * Chooses the inital payment method
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return ShoppPaymentOption The selected payment option
	 **/
	public function initial () {

		if ( $this->count() == 0 ) return false;

		$this->rewind();
		$selected = $this->key();

		if ( empty($selected) ) return false;

		return $this->selected($selected);

	}

	/**
	 * Get or set the selected payment method
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $selection The payment option slug
	 * @return ShoppPaymentOption The selected payment option (or false)
	 **/
	public function selected ( $selection = null ) {

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

	/**
	 * Get or set the payment processor
	 *
	 * Gets the payment processor for the currently selected payment method or,
	 * when a payment processor class is provided, sets the processor (and selected
	 * payment method) to the new processor.
	 *
	 * The payment processor must be a valid module that is activated in Shopp System settings.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $processor The payment processor class
	 * @return string The payment processor class name
	 **/
	public function processor ( $processor = null ) {

		$selected = $this->selected();

		if ( isset($processor) && isset($this->processors[ $processor ]) ) {
			$selection = $this->processors[ $processor ];
			$selected = $this->selected($selection);
		}

		if ( ! $selected || ! $this->modules($selected->processor) )
			$selected = $this->initial();

		if ( ! $selected ) return false;

		return $selected->processor;
	}

	/**
	 * Provides the list of accepted payment cards for all of the
	 * active payment methods
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of payment cards
	 **/
	public function accepted () {
		return $this->cards;
	}

	/**
	 * Detects if the user has set a selected payment method
	 * (as opposed to the inital/default payment method)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True when the user has selected a payment method
	 **/
	public function userset () {
		return $this->userset;
	}

	/**
	 * Determines if a secure session is needed by any of the payment methods
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True when secure encryption is needed
	 **/
	public function secure () {
		return $this->secure;
	}

	/**
	 * Set the payment processor to the Free Order processor
	 *
	 * Adds the free order payment processor and payment option
	 * just-in-time and selects it as the selected processor. This
	 * prevents the free order processor from being able to be
	 * called up by outside requests.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function free () {
		$Payment = $this->freemodule();
		$this->processors[ $Payment->processor ] = $Payment->slug;
		$this->add($Payment->slug, $Payment);
		$this->selected = $Payment->slug;
	}

	/**
	 * Get the specified payment module
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $module The payment module class name
	 * @return GatewayFramework A payment gateway class (or boolean false if not valid)
	 **/
	private function modules ( $module = null ) {
		$Shopp = Shopp::object();

		if ( is_null($module) ) return $Shopp->Gateways->active;

		$FreeModule = $this->freemodule();

		if ( $module == $FreeModule->processor ) {
			return $Shopp->Gateways->freeorder;

		} elseif ( isset($Shopp->Gateways->active[ $module ]) ) {
			return $Shopp->Gateways->active[ $module ];

		} else return false;
	}

	/**
	 * Provides the Free Order payment option
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return ShoppPaymentOption The free order processor payment option
	 **/
	private static function freemodule () {
		$Shopp = Shopp::object();
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

/**
 * A structured payment option object
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopp
 **/
class ShoppPaymentOption extends AutoObjectFramework {

	public $slug;
	public $label;
	public $processor;
	public $setting;
	public $cards;

}
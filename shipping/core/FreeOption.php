<?php
/**
 * Free Option
 *
 * Provides a free shipping rate not included in shipping estimates
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, January 19, 2010
 * @package shopp
 * @since 1.2
 * @subpackage FreeOption
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class FreeOption extends ShippingFramework implements ShippingModule {

	public function methods () {
		return Shopp::__('Free Option');
	}

	public function init () {}
	public function calcitem ( $id, $Item ) {}

	public function calculate ( &$options, $Order ) {

		foreach ( $this->methods as $slug => $method ) {
			$amount = $this->tablerate($method['table']);
			if ($amount === false) continue; // Skip methods that don't match at all
			$rate = array(
				'slug' => $slug,
				'name' => $method['label'],
				'amount' => $amount,
				'delivery' => $this->delivery($method),
				'items' => false
			);
			$options[ $slug ] = new ShippingOption($rate, false);
		}
		return $options;
	}

	public function settings () {

		$this->ui->flatrates(0, array(
			'norates' => true,
			'table' => $this->settings['table']
		));

	}

}
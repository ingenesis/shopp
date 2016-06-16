<?php
/**
 * Order Rates
 *
 * Provides flat rates on entire order
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, June 14, 2011
 * @package shopp
 * @version 1.2
 * @since 1.2
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class OrderRates extends ShippingFramework implements ShippingModule {

	public function methods () {
		return Shopp::__('Flat Order Rates');
	}

	public function init () {
		/* Not implemented */
	}

	public function calcitem ( $id, $Item ) {
		/* Not implemented */
	}

	public function calculate ( &$options, $Order ) {

		foreach ($this->methods as $slug => $method) {

			$amount = isset($method['table']) ? $this->tablerate($method['table']) : false;
			if ( false === $amount ) continue; // Skip methods that don't match at all

			$rate = array(
				'slug' => $slug,
				'name' => $method['label'],
				'amount' => $amount,
				'delivery' => $this->delivery($method),
				'items' => false
			);

			$options[ $slug ] = new ShippingOption($rate);

		}

		return $options;
	}

	public function settings () {

		$this->setup('table');

		$this->ui->flatrates(0, array(
			'table' => $this->settings['table']
		));

	}

}
<?php
/**
 * Item Rates
 *
 * Provides flat rates per item
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, June 14, 2011
 * @package shopp
 * @since 1.2
 * @subpackage ItemRates
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ItemRates extends ShippingFramework implements ShippingModule {

	function methods () {
		return __('Flat Item Rates','Shopp');
	}

	function init () {
		$this->items = 0;
	}

	function calcitem ( $id, $Item ) {
		if ( ! $Item->freeshipping ) $this->items += $Item->quantity;
	}

	function calculate ( &$options, $Order ) {

		foreach ( $this->methods as $slug => $method ) {

			$amount = $this->tablerate($method['table']);
			if ($amount === false) continue; // Skip methods that don't match at all
			$rate = array(
				'slug' => $slug,
				'name' => $method['label'],
				'amount' => ($this->items*$amount),
				'delivery' => $this->delivery($method),
				'items' => $this->items
			);

			$options[ $slug ] = new ShippingOption($rate);

		}

		return $options;
	}

	function settings () {
		$this->ui->flatrates(0, array(
			'table' => $this->settings['table']
		));
	}

}
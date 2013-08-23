<?php
/**
 * Item Quantity Tiers
 *
 * Provides shipping calculations based on the total quantity of items ordered
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, 27 April, 2008
 * @package shopp
 * @since 1.2
 * @subpackage ItemQuantity
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ItemQuantity extends ShippingFramework implements ShippingModule {

	public $items = 0;

	function methods () {
		return Shopp::__('Item Quantity Tiers');
	}

	function init () {
		$this->items = 0;
	}

	function calcitem ( $id, $Item ) {
		$this->items += $Item->quantity;
	}

	function calculate ( &$options, $Order ) {

		foreach ( $this->methods as $slug => $method ) {

			$tiers = $this->tablerate($method['table']);
			if ( $tiers === false ) continue; // Skip methods that don't match at all

			$amount = 0;
			$tiers = array_reverse($tiers);
			foreach ( $tiers as $tier ) {
				extract($tier);
				$amount = Shopp::floatval($rate);			// Capture the rate amount
				if ((int)$this->items >= (int)$threshold) break;
			}

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

	function settings () {
		$this->ui->tablerates(0, array(
			'unit' => array(Shopp::__('Item Quantity'), Shopp::__('items')),
			'table' => $this->settings['table'],
			'rate_class' => 'money'
		));
	}

}
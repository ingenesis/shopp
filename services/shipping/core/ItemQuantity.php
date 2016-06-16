<?php
/**
 * Item Quantity Tiers
 *
 * Provides shipping calculations based on the total quantity of items ordered
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, 27 April, 2008
 * @package shopp
 * @version 1.2
 * @since 1.2
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ItemQuantity extends ShippingFramework implements ShippingModule {

	public $items = 0;

	public function methods () {
		return Shopp::__('Item Quantity Tiers');
	}

	public function init () {
		$this->items = 0;
	}

	public function calcitem ( $id, $Item ) {
		if ( $Item->shipsfree ) return;
		$this->items += $Item->quantity;
	}

	public function calculate ( &$options, $Order ) {

		foreach ( $this->methods as $slug => $method ) {

			$tiers = isset($method['table']) ? $this->tablerate($method['table']) : false;
			if ( $tiers === false ) continue; // Skip methods that don't match at all

			$amount = 0;
			$matched = false;
			$tiers = array_reverse($tiers);

			foreach ( $tiers as $tier ) {
				extract($tier);
				$amount = Shopp::floatval($rate);			// Capture the rate amount

				if ( (int)$this->items >= (int)$threshold ) {
					$matched = true;
					break;
				}
			}

			if ( ! $matched ) return $options;

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

		$this->ui->tablerates(0, array(
			'unit' => array(Shopp::__('Item Quantity'), Shopp::__('items')),
			'table' => $this->settings['table'],
			'rate_class' => 'money'
		));

	}

}
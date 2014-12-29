<?php
/**
 * Percentage Amount Tiers
 *
 * Provides shipping calculations based on a percentage of order amount ranges
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, 12 July, 2011
 * @package shopp
 * @version 1.2
 * @since 1.2
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class PercentageAmount extends ShippingFramework implements ShippingModule {

	public function init () { /* Not implemented */ }
	public function calcitem ( $id, $Item ) { /* Not implemented */ }

	public function methods () {
		return __('Percentage Rate Tiers','Shopp');
	}

	public function calculate ( &$options, $Order ) {

		foreach ( $this->methods as $slug => $method ) {

			$tiers = isset($method['table']) ? $this->tablerate($method['table']) : false;
			if ( false === $tiers ) continue; // Skip methods that don't match at all

			$amount = 0;
			$matched = false;
			$tiers = array_reverse($tiers);
			
			foreach ( $tiers as $tier ) {
				extract($tier);
				$amount = (Shopp::floatval($rate) / 100) * $Order->Cart->total('order');
				
				if ( $Order->Cart->total('order') >= Shopp::floatval($threshold) ) {
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

		$this->ui->tablerates(0,array(
			'unit' => array(Shopp::__('Order Subtotal')),
			'table' => $this->settings['table'],
			'threshold_class' => 'money',
			'rate_class' => 'percentage'
		));

	}

}
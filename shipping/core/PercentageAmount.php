<?php
/**
 * Percentage Amount Tiers
 *
 * Provides shipping calculations based on a percentage of order amount ranges
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, 12 July, 2011
 * @package shopp
 * @since 1.2
 * @subpackage PercentageAmount
 *
 **/

class PercentageAmount extends ShippingFramework implements ShippingModule {

	function init () { /* Not implemented */ }
	function calcitem ($id,$Item) { /* Not implemented */ }

	function methods () {
		return __('Percentage Rate Tiers','Shopp');
	}

	function calculate (&$options,$Order) {

		foreach ($this->methods as $slug => $method) {

			$tiers = $this->tablerate($method['table']);
			if ($tiers === false) continue; // Skip methods that don't match at all

			$amount = 0;
			$tiers = array_reverse($tiers);
			foreach ($tiers as $tier) {
				extract($tier);
				$amount = (Shopp::floatval($rate)/100)* $Order->Cart->Totals->subtotal;
				if (Shopp::floatval($Order->Cart->Totals->subtotal) >= Shopp::floatval($threshold)) break;
			}

			$rate = array(
				'slug' => $slug,
				'name' => $method['label'],
				'amount' => $amount,
				'delivery' => $this->delivery($method),
				'items' => false
			);

			$options[$slug] = new ShippingOption($rate);

		}

		return $options;
	}

	function settings () {

		$this->ui->tablerates(0,array(
			'unit' => array(__('Order Subtotal','Shopp')),
			'table' => $this->settings['table'],
			'threshold_class' => 'money',
			'rate_class' => 'percentage'
		));

	}

} // end flatrates class

?>
<?php
/**
 * TaxItemRates.php
 *
 * Provides the applicable tax rates for a given catalog product or item.
 *
 * @copyright Ingenesis Limited, May 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Tax
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppTaxItemRates {

	/**
	 * Determines the effective tax rate (a single rate) for the store or an item based
	 *
	 * @since 1.0
	 * @version 1.5
	 *
	 * @param Object $Item (optional) The ShoppProduct, ShoppCartItem or ShoppPurchased object to find tax rates for
	 * @return float The determined tax rate
	 **/
	public static function effective( $Item = null ) {

		$taxes = self::applicable($Item);

		// No rates given
		$taxrate = 0.0;

		if ( count($taxes) == 1 ) {
			$TaxRate = current($taxes);
			$taxrate = (float)$TaxRate->rate; // Use the given rate
		} else $taxrate = (float)( ShoppTax::calculate($taxes, 100) ) / 100; // Calculate the "effective" rate (note: won't work with compound taxes)

		return apply_filters('shopp_taxrate', $taxrate);

	}

	/**
	 * Determines all applicable tax rates for the store or an item
	 *
	 * @since 1.0
	 * @version 1.3
	 *
	 * @param Object $Item (optional) The ShoppProduct, ShoppCartItem or ShoppPurchased object to find tax rates for
	 * @return float The determined tax rate
	 **/
	public static function applicable( $Item = null ) {
		$Tax = new ShoppTax();

		$Order = ShoppOrder(); // Setup taxable address
		$Tax->address($Order->Billing, $Order->Shipping, $Order->Cart->shipped());

		$TaxableItem = is_null($Item) ? $Tax->item($Item) : null;

		$taxes = array();
		$Tax->rates($taxes, $TaxableItem);

		return apply_filters('shopp_taxrates', $taxes);
	}

}
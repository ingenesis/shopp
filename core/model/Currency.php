<?php
/**
 * Currency.php
 *
 * Provides number formatting for monetary values
 *
 * @copyright Ingenesis Limited, December 2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Currency
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCurrency {

	public function __construct ( $amount ) {
		$this->amount = $amount;
	}


}
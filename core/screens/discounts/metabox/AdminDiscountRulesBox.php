<?php
/**
 * AdminDiscountRulesBox.php
 *
 * Renders the discount rules metabox.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Discounts
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminDiscountRulesBox extends ShoppAdminMetabox {

	protected $id = 'discount-rules';
	protected $view = 'discounts/rules.php';

	protected function title () {
		return Shopp::__('Conditions');
	}

}
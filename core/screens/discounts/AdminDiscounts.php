<?php
/**
 * AdminDiscounts.php
 *
 * Discounts admin request router
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Discounts
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminDiscounts extends ShoppAdminPostController {

	protected $ui = 'discounts';

	protected function route () {
		if ( $this->request('id') )
			return 'ShoppScreenDiscountEditor';
		else return 'ShoppScreenDiscounts';
	}

}
<?php
/**
 * AdminCustomerShippingAddressBox.php
 *
 * Customer editor shipping address box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerShippingAddressBox extends ShoppAdminMetabox {

	protected $id = 'customer-shipping';
	protected $view = 'customers/shipping.php';

	protected function title () {
		return Shopp::__('Shipping Address');
	}

	public static function editor ( $Customer, $type = 'shipping' ) {
		ob_start();
		include SHOPP_ADMIN_PATH . '/customers/address.php';
		return ob_get_clean();
	}

}

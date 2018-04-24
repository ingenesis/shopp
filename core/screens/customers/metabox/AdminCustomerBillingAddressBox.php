<?php
/**
 * AdminCustomerBillingAddressBox.php
 *
 * Customer editor billing address box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerBillingAddressBox extends ShoppAdminMetabox {

	protected $id = 'customer-billing';
	protected $view = 'customers/billing.php';

	protected function title () {
		return Shopp::__('Billing Address');
	}

	public static function editor ( $Customer, $type = 'billing' ) {
		shopp_custom_script('orders', 'var address = [];');
		ob_start();
		include SHOPP_ADMIN_PATH . '/customers/address.php';
		return ob_get_clean();
	}

}
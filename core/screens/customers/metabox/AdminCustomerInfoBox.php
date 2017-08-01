<?php
/**
 * AdminCustomerInfoBox.php
 *
 * Customer editor info box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerInfoBox extends ShoppAdminMetabox {

	protected $id = 'customer-info';
	protected $view = 'customers/info.php';

	protected function title () {
		return Shopp::__('Details');
	}

}
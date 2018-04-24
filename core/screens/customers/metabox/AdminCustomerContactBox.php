<?php
/**
 * AdminCustomerContactBox.php
 *
 * Customer editor contact box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerContactBox extends ShoppAdminMetabox {

	protected $id = 'customer-contact';
	protected $view = 'customers/contact.php';

	protected function title () {
		return Shopp::__('Contact');
	}

}

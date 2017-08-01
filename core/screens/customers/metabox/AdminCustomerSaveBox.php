<?php
/**
 * AdminCustomerSaveBox.php
 *
 * Customer editor save box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerSaveBox extends ShoppAdminMetabox {

	protected $id = 'customer-save';
	protected $view = 'customers/save.php';

	protected function title () {
		return Shopp::__('Save');
	}

}
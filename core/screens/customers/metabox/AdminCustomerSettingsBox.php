<?php
/**
 * AdminCustomerSettingsBox.php
 *
 * Customer editor settings box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerSettingsBox extends ShoppAdminMetabox {

	protected $id = 'customer-settings';
	protected $view = 'customers/settings.php';

	protected function title () {
		return Shopp::__('Settings');
	}

}

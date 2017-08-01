<?php
/**
 * AdminCustomers.php
 *
 * Flow controller for the customer management interfaces.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomers extends ShoppAdminController {

	protected $ui = 'customers';

	protected function route () {
		if ( $this->request('id') )
			return 'ShoppScreenCustomerEditor';
		else return 'ShoppScreenCustomers';
	}

}
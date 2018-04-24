<?php
/**
 * AdminCustomerLoginBox.php
 *
 * Customer editor login and password box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Customers
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCustomerLoginBox extends ShoppAdminMetabox {

	protected $id = 'customer-login';
	protected $view = 'customers/login.php';

	protected function title () {
		return Shopp::__('Login &amp; Password');
	}

	public function box () {
		extract($this->references);

		$this->references['wp_user'] = get_userdata($Customer->wpuser);
		$this->references['avatar'] = get_avatar($Customer->wpuser, 48);
		$this->references['userlink'] = add_query_arg('user_id', $Customer->wpuser, admin_url('user-edit.php'));

		parent::box();
	}
}

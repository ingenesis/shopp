<?php
/**
 * AdminOrderNotesBox.php
 *
 * Renders the order history metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderHistoryBox extends ShoppAdminMetabox {

	protected $id = 'order-history';
	protected $view = 'orders/history.php';

	protected function title () {
		return Shopp::__('Order History');
	}

}
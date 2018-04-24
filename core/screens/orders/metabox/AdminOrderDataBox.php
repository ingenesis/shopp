<?php
/**
 * AdminOrderDataBox.php
 *
 * Renders the order data metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminOrderDataBox extends ShoppAdminMetabox {

	protected $id = 'order-data';
	protected $view = 'orders/data.php';

	protected function title () {
		return Shopp::__('Details');
	}

	public static function name ( $name ) {
		echo esc_html($name);
	}

	public static function data ( $name, $data ) {

		if ( $type = Shopp::is_image($data) ) {
			$src = "data:$type;base64," . base64_encode($data);
			$result = '<a href="' . $src . '" class="shopp-zoom"><img src="' . $src . '" /></a>';
		} elseif ( is_string($data) && false !== strpos(data, "\n") ) {
			$result = '<textarea name="orderdata[' . esc_attr($name) . ']" readonly="readonly" cols="30" rows="4">' . esc_html($data) . '</textarea>';
		} else {
			$result = esc_html($data);
		}

		echo $result;

	}

}
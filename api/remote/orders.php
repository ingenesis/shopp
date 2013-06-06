<?php
/**
* ShoppOrdersRemoteAPI - Provides remote API calls to access Shopp orders
*
* @version 1.0
* @since 1.3
* @package shopp
* @subpackage ShoppOrdersRemoteAPI
 **/

/**
 * ShoppOrdersRemoteAPI
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package remoteapi
 **/
class ShoppOrdersRemoteAPI {

	/**
	 * order constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	static function _register () {
		shopp_add_remoteapi('orders', array(__CLASS__, 'orders'), 'read');
	}

	static function orders ($response, $resource, $options) {
		list($request, $id) = $resource;
		if ( ! empty($id) ) return shopp_order((int) $id);

		$options = wp_parse_args($options, array(
			'from' => false,
			'to' => false,
			'itmes' => true,
			'customers' => array(),
			'limit' => false,
			'order' => 'DESC'
		));
		extract($options, EXTR_SKIP);
		return shopp_orders($from, $to, $items, $customers, $limit, $order);
	}

} // END class ShoppOrdersRemoteAPI
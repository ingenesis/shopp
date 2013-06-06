<?php
/**
* ShoppCustomersRemoteAPI - Provides remote API calls to access Shopp customers
*
* @version 1.0
* @since 1.3
* @package shopp
* @subpackage ShoppCustomersRemoteAPI
 **/

/**
 * ShoppCustomersRemoteAPI
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package remoteapi
 **/
class ShoppCustomersRemoteAPI {

	/**
	 * order constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	static function _register () {
		shopp_add_remoteapi('customers', array(__CLASS__, 'customers'), 'read');
	}

	static function cutomers ($response,$resource,$options) {
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
		return true;
	}

} // END class ShoppCustomersRemoteAPI
<?php
/**
* ShoppProductRemoteAPI - Provides remote API calls to access catalog products
*
* @version 1.0
* @since 1.3
* @package shopp
* @subpackage ShoppProductRemoteAPI
*
**/

/**
 * Provides remote product API
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package remoteapi
 **/
class ShoppProductRemoteAPI {

	static function _register () {
		shopp_add_remoteapi('product', array(__CLASS__,'product'), 'read');
		shopp_add_remoteapi('add_product', array(__CLASS__,'add'), 'read');
		shopp_add_remoteapi('product-specs', array(__CLASS__,'specs'), 'read');
	}

	static function product ( $result, $resource, $options ) {
		list($request, $id) = $resource;
		$Product = shopp_product($id);
		return $Product;
	}

	static function add ( $result, $resource, $options) {

	}

	static function specs ( $result, $resource, $options ) {
		list($request, $id) = $resource;
 		return shopp_product_specs($id);
	}

}

?>
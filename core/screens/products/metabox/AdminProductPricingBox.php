<?php
/**
 * AdminProductPricingBox.php
 *
 * Product editor pricing metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product editor price meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductPricingBox extends ShoppAdminMetabox {

	protected $id = 'product-pricing-box';
	protected $view = 'products/pricing.php';

	protected function title () {
		return Shopp::__('Pricing');
	}

}
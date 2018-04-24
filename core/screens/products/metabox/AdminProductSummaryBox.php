<?php
/**
 * AdminProductSummaryBox.php
 *
 * Product editor Summary metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product editor summary meta box
 *
 * @since 1.5
 **/
class ShoppAdminProductSummaryBox extends ShoppAdminMetabox {

	protected $id = 'product-summary';
	protected $view = 'products/summary.php';

	protected function title () {
		return Shopp::__('Summary');
	}

}
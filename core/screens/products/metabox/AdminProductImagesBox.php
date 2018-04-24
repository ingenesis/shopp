<?php
/**
 * AdminProductImagesBox.php
 *
 * Product editor Images metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product editor images meta box
 *
 * @since 1.5
 **/
class ShoppAdminProductImagesBox extends ShoppAdminMetabox {

	protected $id = 'product-images';
	protected $view = 'products/images.php';

	protected function title () {
		return Shopp::__('Product Images');
	}

}
<?php
/**
 * AdminProductSaveBox.php
 *
 * Product editor Save metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminProductSaveBox extends ShoppAdminMetabox {

	protected $id = 'product-save';
	protected $view = 'products/save.php';

	protected function title () {
		return Shopp::__('Save');
	}

}
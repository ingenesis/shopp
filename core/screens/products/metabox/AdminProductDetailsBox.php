<?php
/**
 * AdminProductDetailsBox.php
 *
 * Product editor Details & Specs metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product editor details meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductDetailsBox extends ShoppAdminMetabox {

	protected $id = 'product-details';
	protected $view = 'products/details.php';

	protected function title () {
		return Shopp::__('Details &amp; Specs');
	}

}
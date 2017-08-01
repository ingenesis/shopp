<?php
/**
 * AdminProductSettingsBox.php
 *
 * Product editor Settings metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product editor settings meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductSettingsBox extends ShoppAdminMetabox {

	protected $id = 'product-settings';
	protected $view = 'products/settings.php';

	protected function title () {
		return Shopp::__('Settings');
	}

	protected function init () {
		$Shopp = Shopp::object();
		$this->references['shiprealtime'] = $Shopp->Shipping->realtime;
	}

}
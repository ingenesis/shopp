<?php
/**
 * AdminCategoryImagesBox.php
 *
 * Category editor images box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCategoryImagesBox extends ShoppAdminMetabox {

	protected $id = 'category-images';
	protected $view = 'categories/images.php';

	protected function title () {
		return Shopp::__('Images');
	}

}
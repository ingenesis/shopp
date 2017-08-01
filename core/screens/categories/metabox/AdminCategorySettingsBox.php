<?php
/**
 * AdminCategorySettingsBox.php
 *
 * Category editor settings box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCategorySettingsBox extends ShoppAdminMetabox {

	protected $id = 'category-settings';
	protected $view = 'categories/settings.php';

	protected function title () {
		return Shopp::__('Settings');
	}

	public function box () {
		$this->references['tax'] = get_taxonomy($this->references['Category']->taxonomy);
		parent::box();
	}

}
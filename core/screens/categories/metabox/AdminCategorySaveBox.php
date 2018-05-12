<?php
/**
 * AdminCategorySaveBox.php
 *
 * Category editor save box.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCategorySaveBox extends ShoppAdminMetabox {

	protected $id = 'category-save';
	protected $view = 'categories/save.php';

	protected function title () {
		return Shopp::__('Save');
	}

	public function box () {
		$options = array(
			'continue' => Shopp::__('Continue Editing'),
			'close'    => Shopp::__('Category Manager'),
			'new'      => Shopp::__('New Category'),
			'next'     => Shopp::__('Edit Next'),
			'previous' => Shopp::__('Edit Previous')
		);

		$this->references['workflows'] = Shopp::menuoptions($options, shopp_setting('workflow'), true);
		parent::box();
	}

}
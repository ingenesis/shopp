<?php
/**
 * AdminCategoryTemplatesBox.php
 *
 * Category editor templates box. 
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCategoryTemplatesBox extends ShoppAdminMetabox {

	protected $id = 'category-templates';
	protected $view = 'categories/templates.php';

	protected function title () {
		return Shopp::__('Product Templates &amp; Menus');
	}

	public function box () {
		$options = array(
			'disabled' => Shopp::__('Price ranges disabled'),
			'auto'     => Shopp::__('Build price ranges automatically'),
			'custom'   => Shopp::__('Use custom price ranges'),
		);

		$this->references['pricemenu'] = menuoptions($options, $this->references['Category']->pricerange, true);
		parent::box();
	}

}
<?php
/**
 * AdminDiscountBox.php
 *
 * Renders the discount metabox.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Discounts
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminDiscountBox extends ShoppAdminMetabox {

	protected $id = 'discount-discount';
	protected $view = 'discounts/discount.php';

	protected function title () {
		return Shopp::__('Discount');
	}

	public function box () {
		$this->references['types_menu'] = menuoptions(ShoppScreenDiscountEditor::types(), $this->references['Promotion']->type, true);
		parent::box();
	}

}
<?php
/**
 * Deprecated.php
 *
 * Deprecated class definitions.
 *
 * @author Barry Hughes
 * @copyright Ingenesis Limited, 27 August 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @version 1.3
 * @since 1.3
 **/

// Prevent direct access
defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit;

// Allow devs to stop these definitions from being loaded
if ( defined('SHOPP_DISALLOW_DEPRECATED_CLASSES') && SHOPP_DISALLOW_DEPRECATED_CLASSES ) return;

// Straightforward aliases for deprecated classes
if ( ! class_exists('Address', false) ) { class Address extends ShoppAddress {} }
if ( ! class_exists('AdminController', false) ) { class AdminController extends ShoppAdminController {} }
if ( ! class_exists('Customer', false) ) { class Customer extends ShoppCustomer {} }
if ( ! class_exists('FlowController', false) ) { class FlowController extends ShoppFlowController {} }
if ( ! class_exists('MetaObject', false) ) { class MetaObject extends ShoppMetaObject {} }
if ( ! class_exists('Price', false) ) {	class Price extends ShoppPrice {} }
if ( ! class_exists('Product', false) ) { class Product extends ShoppProduct {} }
if ( ! class_exists('Promotion', false) ) { class Promotion extends ShoppPromo {} }
if ( ! class_exists('Purchase', false) ) { class Purchase extends ShoppPurchase {} }
if ( ! class_exists('Purchased', false) ) { class Purchased extends ShoppPurchased {} }
if ( ! class_exists('Storefront', false) ) { class Storefront extends ShoppStorefront {} }

// The Cart class additionally needs stub methods for backwards compatibility
if ( ! class_exists('Cart', false) ) {
	class Cart extends ShoppCart {
		/**
		 * @deprecated Stubbed for backwards-compatibility
		 **/
		public function changed ( $changed = false ) {}

		/**
		 * @deprecated Stubbed for backwards-compatibility
		 **/
		public function retotal () {}
	}
}


/**
 * @deprecated Replaced by the OrderTotals system
 **/
class CartTotals {

	public $taxrates = array();		// List of tax figures (rates and amounts)
	public $quantity = 0;			// Total quantity of items in the cart
	public $subtotal = 0;			// Subtotal of item totals
	public $discount = 0;			// Subtotal of cart discounts
	public $itemsd = 0;				// Subtotal of cart item discounts
	public $shipping = 0;			// Subtotal of shipping costs for items
	public $taxed = 0;				// Subtotal of taxable item totals
	public $tax = 0;				// Subtotal of item taxes
	public $total = 0;				// Grand total

} // END class CartTotals

/**
 * @deprecated Do not use. Replaced by ShoppPromotions
 **/
class CartPromotions {

	public $promotions = array();

	/**
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function load () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function reload () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;

	}

	/**
	 * @deprecated Do not use
	 **/
	public function available () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartPromotions

/**
 * @deprecated Do not use. Replaced with ShoppDiscounts
 **/
class CartDiscounts {

	// Registries
	public $Cart = false;
	public $promos = array();

	// Settings
	public $limit = 0;

	// Internals
	public $itemprops = array('Any item name','Any item quantity','Any item amount');
	public $cartitemprops = array('Name','Category','Tag name','Variation','Input name','Input value','Quantity','Unit price','Total price','Discount amount');
	public $matched = array();

	/**
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
	}

	/**
	 * @deprecated Do not use
	 **/
	public function calculate () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function applypromos () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;	}

		/**
		 * @deprecated Do not use
		 **/
	public function discount ($promo,$discount) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function remove ($id) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function promocode ($rule) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function _active_discounts ($a,$b) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function _filter_promocode_rule ($rule) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartDiscounts

/**
 * @deprecated Do not use. Replaced by ShoppShiprates
 **/
class CartShipping {

	public $options = array();
	public $modules = false;
	public $disabled = false;
	public $fees = 0;
	public $handling = 0;

	/**
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
	}

	/**
	 * @deprecated Do not use
	 **/
	public function status () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function calculate () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function options () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function selected () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	static function sort ($a,$b) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartShipping

/**
 * @deprecated No longer used. Replaced by OrderTotals and ShoppTax
 **/
class CartTax {

	public $Order = false;
	public $enabled = false;
	public $shipping = false;
	public $rates = array();

	/**
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function rate ($Item=false,$settings=false) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function float ($rate) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function calculate () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartTax

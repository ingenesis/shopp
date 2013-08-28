<?php
/**
 * Deprecated.php
 *
 * Deprecated class definitions.
 *
 * @author Barry Hughes
 * @version 1.3
 * @copyright Ingenesis Limited, 27 August 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.3
 **/

if ( defined('SHOPP_DISALLOW_DEPRECATED_CLASSES') && SHOPP_DISALLOW_DEPRECATED_CLASSES ) return;

if ( ! class_exists('Address', false) ) {
	class Address extends ShoppAddress {}
}

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

if ( ! class_exists('Price', false) ) {
	class Price extends ShoppPrice {}
}

if ( ! class_exists('Product', false) ) {
	class Product extends ShoppProduct {}
}

if ( ! class_exists('Promotion', false) ) {
	class Promotion extends ShoppPromo {}
}

if ( ! class_exists('Purchase', false) ) {
	class Purchase extends ShoppPurchase {}
}

if ( ! class_exists('Storefront', false) ) {
	class Storefront extends ShoppStorefront {}
}
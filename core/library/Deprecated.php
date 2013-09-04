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
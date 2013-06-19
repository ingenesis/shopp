<?php
/**
 * Cart API
 *
 * Plugin api calls for manipulating the cart contents.
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, June 23, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.2
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * shopp_add_cart_variant - add a product to the cart by variant id
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $variant (required) variant id to add
 * @param int $quantity (optional default: 1) quantity of product to add
 * @return bool true on success, false on failure
 **/
function shopp_add_cart_variant ( $variant = false, $quantity = 1, $key = 'id') {
	$keys = array('id','optionkey','label','sku');
	if ( false === $variant ) {
		shopp_debug(__FUNCTION__ . " failed: Variant parameter required.");
	}
	if (!in_array($key,$keys)) {
		shopp_debug(__FUNCTION__ . " failed: Variant key $key invalid.");
	}
	$Price = new Price( $variant, $key);
	if ( empty($Price->id) ) {
		shopp_debug(__FUNCTION__ . " failed: Product variant $variant invalid.");
		return false;
	}

	return shopp_add_cart_product($Price->product, $quantity, $Price->id);
}

/**
 * shopp_add_cart_product - add a product to the cart
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $product (required) product id to add
 * @param int $quantity (optional default: 1) quantity of product to add
 * @param int $variant (optional) variant id to use
 * @return bool true on success,
 * false on failure
 **/
function shopp_add_cart_product ( $product = false, $quantity = 1, $variant = false, $data = array() ) {
	$Order = ShoppOrder();
	if ( (int) $quantity < 1 ) $quantity = 1;

	if ( false === $product ) {
		shopp_debug(__FUNCTION__ . " failed: Product parameter required.");
		return false;
	}

	$Product = new Product( $product );
	if ( empty($Product->id) ) {
		shopp_debug(__FUNCTION__ . " failed: Product $product invalid");
		return false;
	}


	if ( false !== $variant ) {
		$Price = new Price( $variant );
		if ( empty($Price->id) || $Price->product != $product) {
			shopp_debug(__FUNCTION__ . " failed: Product variant $variant invalid.");
			return false;
		}
	}

	if ( !empty($data) ) {
		if ( !is_array($data)) {
			shopp_debug(__FUNCTION__ . " failed: Product custom input data must be an array.");
			return false;
		}
	}

	$added = $Order->Cart->add($quantity, $Product, $variant, false, $data);
	$Order->Cart->totals();
	return $added;
}


/**
 * shopp_rmv_cart_item - remove a specific item from the cart
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $item (required) the numeric index of the item contents array to remove ( 0 indexed )
 * @return bool true for success, false on failure
 **/
function shopp_rmv_cart_item ( $item = false ) {
	$Order = ShoppOrder();
	if ( false === $item ) {
		shopp_debug(__FUNCTION__ . " failed: Missing item parameter.");
		return false;
	}

	if ( 0 == $count = count($Order->Cart) ) return true;
	if ( ! $Order->Cart->exists($item) ) {
		shopp_debug(__FUNCTION__ . " failed: No such item $item");
		return false;
	}
	$remove = $Order->Cart->rmvitem($item);
	$Order->Cart->totals();
	return $remove;
}

/**
 * Update the quantity of a specific product (in the cart)
 *
 * @author Hiranthi Molhoek-Herlaar, Jonathan Davis
 *
 * @param int $item Index of the item in Cart contents
 * @param int $quantity New quantity to update the item to, defaults to 1
 **/
function shopp_set_cart_item_quantity ( $item = false, $quantity = 1 ) {
	if ( false === $item ) {
		shopp_debug(__FUNCTION__ . ' failed: Missing item parameter.');
		return false;
	}

    $Order = ShoppOrder();
    return $Order->Cart->setitem($item, $quantity);
}

/**
 * shopp_cart_items - get a list of the items in the cart
 *
 * @author John Dillick
 * @since 1.2
 *
 * @return array list of items in the cart
 **/
function shopp_cart_items () {
	$Items = array();
	foreach ( ShoppOrder()->Cart as $id => $Item )
		$Items[$id] = $Item;
	return $Items;
}

/**
 * shopp_cart_items_count - get count of items in the cart
 *
 * @author John Dillick
 * @since 1.2
 *
 * @return void Description...
 **/
function shopp_cart_items_count () {
	return ShoppOrder()->Cart->count();
}

/**
 * shopp_cart_item - get an object representing the item in the cart.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int|string $item the integer index of the item in the cart, string 'recent-cartitem' for last added cart item
 * @return stdClass object with quantity, product id, variant id, and list of addons of the item.
 **/
function shopp_cart_item ( $item = false ) {
	$Order = ShoppOrder();
	if ( false === $item ) {
		shopp_debug(__FUNCTION__ . " failed: Missing item parameter.");
	}

	if ( 'recent-cartitem' === $item ) return $Order->Cart->added();

	$items = shopp_cart_items();

	if ( ! array_key_exists($item, $items) ) {
		shopp_debug(__FUNCTION__ . " failed: No such item $item");
		return false;
	}
	return $items[$item];
}

/**
 * Empty the contents of the cart
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @return void
 **/
function shopp_empty_cart () {
	ShoppOrder()->Cart->clear();
}

/**
 * Apply a promocode to the cart
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @param string $code The promotion code to apply
 * @return void
 **/
function shopp_add_cart_promocode ($code = false) {
	if ( false === $code || empty($code) ) {
		shopp_debug(__FUNCTION__ . " failed: Missing code parameter.");
	}

	$Cart = ShoppOrder()->Cart;
	$Cart->promocode = esc_attr($code);
	$Cart->totals();
}

// todo: implement shopp_add_cart_item_addon in plugin api
function shopp_add_cart_item_addon ( $itemkey = false, $addonkey = false ) {
	$Order = ShoppOrder();

	if ( false === $itemkey || false === $addonkey ) {
		shopp_debug(__FUNCTION__ . " failed: item and addon parameter required.");
		return false;
	}
	if ( ! ( $item = shopp_cart_item($itemkey) ) ) {
		shopp_debug(__FUNCTION__ . " failed: no such item $itemkey");
		return false;
	}
	if ( ! ( $addon = shopp_product_addon($addonkey) ) ) {
		shopp_debug(__FUNCTION__ . " failed: addon $addonkey is not available for item $itemkey");
		return false;
	}

	if ( false === ( $addons = shopp_cart_item_addons($itemkey) ) ) {
		return false; // Debug message will already have been generated in shopp_cart_item_addons()
	}

	foreach ($addons as $existing) {
		if ( $existing->id == $addonkey ) {
			shopp_debug(__FUNCTION__ . " failed: item $itemkey already includes addon $addonkey");
			return false;
		}
	}

	$addons[] = $addon;
	foreach ($addons as &$addon) $addon = $addon->id; // Convert to an array of ids

	return $Order->Cart->change($itemkey, $item->product, (int) $item->priceline, $addons);
}

// todo: implement shopp_rmv_cart_item_addon in plugin api
function shopp_rmv_cart_item_addon ( $item = false, $addon = false ) {
	// $Order = ShoppOrder();
	// if ( false === $item || false === $addon ) {
	// 	shopp_debug(__FUNCTION__ . " failed: item and addon parameter required.");
	// 	return false;
	// }
	// if ( $item < 0 || $item >= shopp_cart_items_count() ) {
	// 	shopp_debug(__FUNCTION__ . " failed: No such item $item");
	// 	return false;
	// }
	// $Item = $Order->Cart->contents[$item];
	// if ( $addon < 0 || $addon >= count( $Item->addons ) ) {
	// 	shopp_debug(__FUNCTION__ . " failed: No such addon $addon on this item.");
	// 	return false;
	// }
}

/**
 * Returns an array of item addons (may be an empty array) or fals if the item does not exist/no item is specified.
 *
 * @param bool $itemkey
 * @return array|bool
 */
function shopp_cart_item_addons ( $itemkey = false ) {
	if ( false === $itemkey ) {
		shopp_debug(__FUNCTION__ . " failed: item and addon parameter required.");
		return false;
	}
	if ( ! ( $item = shopp_cart_item($itemkey) ) ) {
		shopp_debug(__FUNCTION__ . " failed: no such item $itemkey");
		return false;
	}

	return (array) $item->addons;
}

// todo: implement shopp_cart_item_addons_count in plugin api
function shopp_cart_item_addons_count ($item) {}
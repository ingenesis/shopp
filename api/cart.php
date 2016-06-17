<?php
/**
 * Cart API
 *
 * Plugin api calls for manipulating the cart contents.
 *
 * @copyright Ingenesis Limited, June 23, 2011
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Cart
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Add a product to the cart by variant ShoppPrice id
 *
 * @api
 * @since 1.2
 *
 * @param int $variant (required) variant id to add
 * @param int $quantity (optional default: 1) quantity of product to add
 * @param string $key (optional) specify the column key specified in $variant
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
	$Price = new ShoppPrice($variant, $key);
	if ( empty($Price->id) ) {
		shopp_debug(__FUNCTION__ . " failed: Product variant $variant invalid.");
		return false;
	}

	return shopp_add_cart_product($Price->product, $quantity, $Price->id);
}

/**
 * shopp_add_cart_product - add a product to the cart
 *
 * @api
 * @since 1.2
 *
 * @param int $product (required) product id to add
 * @param int $quantity (optional default: 1) quantity of product to add
 * @param int $variant (optional) variant id to use
 * @param array $data (optional) associative array of custom product input name/values
 * @return bool true on success, false on failure
 **/
function shopp_add_cart_product ( $product = false, $quantity = 1, $variant = false, $data = array() ) {
	$Order = ShoppOrder();
	if ( (int) $quantity < 1 ) $quantity = 1;

	if ( false === $product ) {
		shopp_debug(__FUNCTION__ . " failed: Product parameter required.");
		return false;
	}

	$Product = new ShoppProduct( $product );
	if ( empty($Product->id) ) {
		shopp_debug(__FUNCTION__ . " failed: Product $product invalid");
		return false;
	}


	if ( false !== $variant ) {
		$Price = new ShoppPrice( $variant );
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

	$added = $Order->Cart->additem($quantity, $Product, $variant, false, $data);
	$Order->Cart->totals();
	return $added;
}

/**
 * shopp_rmv_cart_item - remove a specific item from the cart
 *
 * @api
 * @since 1.2
 *
 * @param int|string $item (required) the numeric index or key name of the item to remove
 * @return bool true for success, false on failure
 **/
function shopp_rmv_cart_item ( $item = false ) {
	if ( false === $item ) {
		shopp_debug(__FUNCTION__ . " failed: Missing item parameter.");
		return false;
	}

	$Cart = ShoppOrder()->Cart;
	if ( $Cart->count() == 0 ) return false;

	if ( is_int($item) ) {
		$items = $Cart->keys();

		if ( isset( $items[ $item ]) )
			$key = $items[ $item ];

	} else $key = $item;

	if ( ! $Cart->exists($key) ) {
		shopp_debug(__FUNCTION__ . " failed: No such item $item");
		return false;
	}

	$remove = $Cart->rmvitem($key);
	$Cart->totals();
	return $remove;
}

/**
 * Update the quantity of a specific product (in the cart)
 *
 * @api
 * @since 1.3
 *
 * @param int $item Index of the item in Cart contents
 * @param int $quantity New quantity to update the item to, defaults to 1
 * @return bool true for success, false on failure
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
 * Change the selected variant of an item in the cart
 *
 * @api
 * @since 1.3
 *
 * @param string $item The item fingerprint index identifier
 * @param int|array|Price $variant ShoppPrice record ID or an array of pricing record IDs or a ShoppPrice object
 * @return bool true for success, false on failure
 **/
function shopp_set_cart_item_variant ( $item = null, $variant = null ) {

	if ( null === $item ) {
		shopp_debug(__FUNCTION__ . ' failed: Missing item parameter.');
		return false;
	}

	if ( null === $variant ) {
		shopp_debug(__FUNCTION__ . ' failed: Missing variant parameter.');
		return false;
	}

	if ( ! $Cart->exists($item) ) {
		shopp_debug(__FUNCTION__ . " failed: No such item $item");
		return false;
	}

	$Item = $Cart->get($item);
	return ShoppOrder()->Cart->change($item, $Item->product, $variant);
}


/**
 * shopp_cart_items - get a list of the items in the cart
 *
 * @api
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
 * @api
 * @since 1.2
 *
 * @return void
 **/
function shopp_cart_items_count () {
	return ShoppOrder()->Cart->count();
}

/**
 * shopp_cart_item - get an object representing the item in the cart.
 *
 * @api
 * @since 1.2
 *
 * @param int|string $item the integer index of the item in the cart, string 'recent-cartitem' for last added cart item
 * @return stdClass object with quantity, product id, variant id, and list of addons of the item.
 **/
function shopp_cart_item ( $item = false ) {
	$Cart = ShoppOrder()->Cart;
	if ( false === $item ) {
		shopp_debug(__FUNCTION__ . " failed: Missing item parameter.");
	}

	if ( 'recent-cartitem' === $item ) return $Cart->added();

	if ( is_int($item) ) {
		$items = $Cart->keys();

		if ( isset( $items[ $item ]) )
			$key = $items[ $item ];

	} else $key = $item;


	if ( ! $Cart->exists($key) ) {
		shopp_debug(__FUNCTION__ . " failed: No such item $item");
		return false;
	}

	return $Cart->get($key);
}

/**
 * Empty the contents of the cart
 *
 * @api
 * @since 1.2
 *
 * @return void
 **/
function shopp_empty_cart () {
	ShoppOrder()->Cart->clear();
}

/**
 * Apply a promo code to the cart
 * 
 * @deprecated Use shopp_add_cart_discount_code
 * 
 * @param string $code The promotion code to apply
 * @return void
 **/
function shopp_add_cart_promocode ( $code = false ) {
	shopp_debug(__FUNCTION__ . " deprecated: Use shopp_add_cart_discount_code() instead.");
	return shopp_add_cart_discount_code($code);
}


/**
 * Apply a discount code to the cart
 *
 * @api
 * @since 1.2
 *
 * @param string $code The promotion code to apply
 * @return void
 **/
function shopp_add_cart_discount_code( $code = null ) {
	if ( null === $code || empty($code) ) {
		shopp_debug(__FUNCTION__ . " failed: Missing code parameter.");
	}

	$Cart = ShoppOrder()->Cart;
	$Discounts = ShoppOrder()->Discounts;
	$Discounts->request($code);
	$Cart->totals();


}

/**
 * Adds an addon to an existing cartitem. 
 * 
 * The $addonid should be the numeric ID of the addon.
 *
 * @api
 * 
 * @param mixed $itemkey
 * @param mixed $addonid
 * @return bool
 */
function shopp_add_cart_item_addon ( $itemkey = false, $addonid = false ) {
	if ( false === $itemkey || false === $addonid ) {
		shopp_debug(__FUNCTION__ . " failed: item and addon parameter required.");
		return false;
	}
	if ( ! ( $Item = shopp_cart_item($itemkey) ) ) {
		shopp_debug(__FUNCTION__ . " failed: no such item $itemkey");
		return false;
	}
	if ( ! shopp_product_has_addon($Item->product, $addonid) ) {
		shopp_debug(__FUNCTION__ . " failed: addon $addonid is not available for item $itemkey");
		return false;
	}
	if ( false === ( $addons = shopp_cart_item_addons($itemkey) ) ) {
		return false; // Debug message will already have been generated in shopp_cart_item_addons()
	}

	foreach ( $addons as $existing ) {
		if ( $existing->id == $addonid ) {
			shopp_debug(__FUNCTION__ . " failed: item $itemkey already includes addon $addonid");
			return false;
		}
	}

	foreach ( $addons as &$addon ) $addon = $addon->id; // Convert to an array of ids
	$addons[] = $addonid;

	$Order = ShoppOrder();
	return $Order->Cart->change($itemkey, $Item->product, (int) $Item->priceline, $addons);
}

/**
 * Removes an addon from a cartitem. The addonkey should be the numeric ID of the addon.
 *
 * @api
 * 
 * @param mixed $itemkey
 * @param mixed $addonkey
 * @return bool
 */
function shopp_rmv_cart_item_addon ( $itemkey = false, $addonkey = false ) {
	$Order = ShoppOrder();
	$exists = false;

	if ( false === $itemkey || false === $addonkey ) {
		shopp_debug(__FUNCTION__ . " failed: item and addon parameter required.");
		return false;
	}
	if ( ! ( $item = shopp_cart_item($itemkey) ) ) {
		shopp_debug(__FUNCTION__ . " failed: no such item $itemkey");
		return false;
	}
	if ( false === ( $addons = shopp_cart_item_addons($itemkey) ) ) {
		return false; // Debug message will already have been generated in shopp_cart_item_addons()
	}

	if ( ! isset($addons[ $addonkey ]) ) {
		shopp_debug(__FUNCTION__ . " failed: addon $addonkey was not found in item $itemkey");
		return false;
	}

	$revised = array();
	foreach ( $addons as $addon ) {
		if ( $addonkey == $addon->id ) $revised[] = (int)($addonkey * -1);
		else $revised[] = $addon->id;
	}

	return $Order->Cart->change($itemkey, $item->product, (int) $item->priceline, $revised);
}

/**
 * Returns an array of item addons (may be an empty array) or fals if the item does not exist/no item is specified.
 *
 * @api
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

/**
 * Returns the number of addons added to the cartitem.
 *
 * @api
 * 
 * @param $itemkey
 * @return bool|int
 */
function shopp_cart_item_addons_count ($itemkey) {
	if ( false === $itemkey ) {
		shopp_debug(__FUNCTION__ . " failed: itemkey parameter required.");
		return false;
	}
	if ( ! ( $item = shopp_cart_item($itemkey) ) ) {
		shopp_debug(__FUNCTION__ . " failed: no such item $itemkey");
		return false;
	}
	if ( false === ( $addons = shopp_cart_item_addons($itemkey) ) ) {
		return false; // Debug message will already have been generated in shopp_cart_item_addons()
	}
	return (int) count($addons);
}
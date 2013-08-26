<?php
/**
* ShoppCartThemeAPI - Provided theme api tags.
*
* @api
* @version 1.0
* @since 1.2
* @package shopp
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides shopp('cart') theme api functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppCartThemeAPI implements ShoppAPI {
	static $register = array(
		'_cart',
		'applycode' => 'applycode',
		'discount' => 'discount',
		'discountapplied' => 'discount_applied',
		'discountname' => 'discount_name',
		'discounts' => 'discounts',
		'discountsavailable' => 'discounts_available',
		'downloaditems' => 'download_items',
		'emptybutton' => 'empty_button',
		'function' => 'cart_function',
		'hasdiscount' => 'has_discount',
		'hasdiscounts' => 'has_discounts',
		'hasdownloads' => 'has_downloads',
		'hasitems' => 'has_items',
		'hasshipcosts' => 'has_ship_costs',
		'hasshipped' => 'has_shipped',
		'hasshippingmethods' => 'has_shipping_methods',
		'hastaxes' => 'has_taxes',
		'items' => 'items',
		'lastitem' => 'last_item',
		'needsshipped' => 'needs_shipped',
		'needsshippingestimates' => 'needs_shipping_estimates',
		'referer' => 'referrer',
		'referrer' => 'referrer',
		'shipping' => 'shipping',
		'shippingestimates' => 'shipping_estimates',
		'shippeditems' => 'shipped_items',
		'sidecart' => 'sidecart',
		'subtotal' => 'subtotal',
		'tax' => 'tax',
		'total' => 'total',
		'totaldiscounts' => 'total_discounts',
		'totalitems' => 'total_items',
		'totalquantity' => 'total_quantity',
		'updatebutton' => 'update_button',
		'url' => 'url',

		/* Deprecated tag names - do not use */
		'haspromos' => 'has_discounts',
		'promocode' => 'applycode',
		'promos' => 'discounts',
		'promosavailable' => 'discounts_available',
		'promodiscount' => 'discount_applied',
		'promoname' => 'discount_name',
		'totalpromos' => 'total_discounts',
	);

	public static function _apicontext () {
		return 'cart';
	}

	/**
	 * _setobject - returns the global context object used in the shopp('cart') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'Order') && isset($Object->Cart) && 'cart' == strtolower($object) )
			return $Object->Cart;
		else if ( strtolower($object) != 'cart' ) return $Object; // not mine, do nothing

		$Order = ShoppOrder();
		return $Order->Cart;
	}

	public static function _cart ( $result, $options, $property, $O) {
		// Passthru for non-monetary results
		$monetary = array('discount', 'subtotal', 'shipping', 'tax', 'total');
		if ( ! in_array($property, $monetary) || ! is_numeric($result) ) return $result;

		// @deprecated currency parameter
		if ( isset($options['currency']) ) $options['money'] = $options['currency'];
		// @deprecated wrapper parameter
		if ( isset($options['wrapper']) ) $options['wrap'] = $options['wrapper'];

		$defaults = array(
			'wrap' => 'on',
			'money' => 'on',
			'number' => false,
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( Shopp::str_true($number) ) return $result;
		if ( Shopp::str_true($money)  ) $result = money( roundprice($result) );
		if ( Shopp::str_true($wrap)   ) return '<span class="shopp-cart cart-' . strtolower($property) . '">' . $result . '</span>';

		return $result;
	}

	public static function applycode ( $result, $options, $O ) {

		$submit_attrs = array('title','value','disabled','tabindex','accesskey','class');

		// Skip if discounts are not available
		if ( ! self::discounts_available(false, false, $O) ) return false;

		if ( ! isset($options['value']) ) $options['value'] = __('Apply Discount', 'Shopp');

		$result = '<div class="applycode">';

		$defaults = array(
			'before' => '<p class="error">',
			'after' => '</p>'
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$Errors = ShoppErrorStorefrontNotices();
		if ( $Errors->exist() ) {
			while ( $Errors->exist() )
				$result .=  $before . $Errors->message() . $after;
		}

		$result .= '<span><input type="text" id="discount-code" name="discountcode" value="" size="10" /></span>';
		$result .= '<span><input type="submit" id="apply-code" name="update" '.inputattrs($options, $submit_attrs).' /></span>';
		$result .= '</div>';
		return $result;
	}

	public static function discount ( $result, $options, $O ) {
		return abs($O->Totals->total('discount'));
	}

	public static function discount_applied ( $result, $options, $O ) {
		$Discount = ShoppOrder()->Discounts->current();
		if ( $Discount->amount() == 0 && ! $Discount->shipfree() ) return false;

		$defaults = array(
			'label' => __('%s Off!', 'Shopp'),
			'before' => '',
			'after' => '',
			'remove' => 'on'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( false === strpos($label, '%s') )
			$label = "%s $label";

		$string = $before;

		switch ( $Discount->type() ) {
			case ShoppOrderDiscount::SHIP_FREE:		$string .= sprintf(esc_html($label), money($Discount->amount())); break;
			case ShoppOrderDiscount::PERCENT_OFF:	$string .= sprintf(esc_html($label), percentage($Discount->discount(), array('precision' => 0))); break;
			case ShoppOrderDiscount::AMOUNT_OFF:	$string .= sprintf(esc_html($label), money($Discount->discount())); break;
			case ShoppOrderDiscount::BOGOF:			list($buy, $get) = $Discount->discount(); $string .= Shopp::esc_html__('Buy %s get %s free', $buy, $get); break;
		}

		if ( Shopp::str_true($remove) )
			$string .= '&nbsp;<a href="' . Shopp::url(array('removecode' => $Discount->id()), 'cart') . '" class="shoppui-remove-sign"><span class="hidden">' . Shopp::esc_html__('Remove Discount') . '</span></a>';

		$string .= $after;

		return $string;
	}

	public static function discount_name ( $result, $options, $O ) {
		$Discount = ShoppOrder()->Discounts->current();
		if ( $Discount->amount() == 0 && ! $Discount->shipfree() ) return false;
		return $Discount->name();
	}

	public static function discounts ( $result, $options, $O ) {

		$O = ShoppOrder()->Discounts;
		if ( ! isset($O->_looping) ) {
			$O->rewind();
			$O->_looping = true;
		} else $O->next();

		if ( $O->valid() ) return true;
		else {
			unset($O->_looping);
			$O->rewind();
			return false;
		}

	}

	public static function discounts_available ( $result, $options, $O ) {
		// Discounts are not available if there are no configured discounts loaded (Promotions)
		if ( ! ShoppOrder()->Promotions->available() ) return false;

		// Discounts are not available if the discount limit has been reached
		if ( shopp_setting('promo_limit') > 0 && ShoppOrder()->Discounts->count() >= shopp_setting('promo_limit') ) return false;
		return true;
	}

	public static function download_items ( $result, $options, $O ) {
		if ( ! isset($O->_downloads_loop) ) {
			reset($O->downloads);
			$O->_downloads_loop = true;
		} else next($O->downloads);

		if ( current($O->downloads) ) return true;
		else {
			unset($O->_downloads_loop);
			reset($O->downloads);
			return false;
		}
	}

	public static function empty_button ( $result, $options, $O ) {
		$submit_attrs = array('title', 'value', 'disabled', 'tabindex', 'accesskey', 'class');
		if ( ! isset($options['value']) ) $options['value'] = __('Empty Cart', 'Shopp');
		return '<input type="submit" name="empty" id="empty-button" ' . inputattrs($options,$submit_attrs) . ' />';
	}

	public static function cart_function ( $result, $options, $O ) {
		return '<div class="hidden"><input type="hidden" id="cart-action" name="cart" value="true" /></div><input type="submit" name="update" id="hidden-update" />';
	}

	public static function has_discount ( $result, $options, $O ) {
		return ( abs($O->Totals->total('discount')) > 0 );
	}

	public static function has_discounts ( $result, $options, $O ) {
		$Discounts = ShoppOrder()->Discounts;
		$Discounts->rewind();
		return ($Discounts->count() > 0);
	}

	public static function has_downloads ( $result, $options, $O ) {
		reset($O->downloads);
		return $O->downloads();
	}

	public static function has_items ( $result, $options, $O ) {
		$O->rewind();
		return $O->count() > 0;
	}

	public static function has_ship_costs ( $result, $options, $O ) {
		return ($O->Totals->total('shipping') > 0);
	}

	public static function has_shipped ( $result, $options, $O ) {
		reset($O->shipped);
		return $O->shipped();
	}

	public static function has_shipping_methods ( $result, $options, $O ) {
		return ShoppShippingThemeAPI::has_options( $result, $options, $O );
	}

	public static function has_taxes ( $result, $options, $O ) {
		return ($O->Totals->total('tax') > 0);
	}

	public static function items ( $result, $options, $O ) {
		if ( ! isset($O->_item_loop) ) {
			$O->rewind();
			$O->_item_loop = true;
		} else $O->next();

		if ( $O->valid() ) return true;
		else {
			unset($O->_item_loop);
			$O->rewind();
			return false;
		}
	}

	public static function last_item ( $result, $options, $O ) {
		return $O->added();
	}

	public static function needs_shipped ( $result, $options, $O ) {
		return ( ! empty($O->shipped) );
	}

	public static function needs_shipping_estimates ( $result, $options, $O ) {
		// Shipping must be enabled, without free shipping and shipped items must be present in the cart
		return ( shopp_setting_enabled('shipping') && !( $O->freeship && empty($O->shipped) ) );
	}

	public static function referrer ( $result, $options, $O ) {
		$Shopping = ShoppShopping();
		$referrer = $Shopping->data->referrer;
		if ( ! $referrer ) $referrer = shopp('catalog', 'url', 'return=1');
		return $referrer;
	}

	public static function shipped_items ( $result, $options, $O ) {
		if (!isset($O->_shipped_loop)) {
			reset($O->shipped);
			$O->_shipped_loop = true;
		} else next($O->shipped);

		if (current($O->shipped)) return true;
		else {
			unset($O->_shipped_loop);
			reset($O->shipped);
			return false;
		}
	}

	public static function shipping ( $result, $options, $O ) {
		if ( empty($O->shipped) ) return "";
		if ( isset($options['label']) ) {
			$options['currency'] = "false";
			if ( $O->freeshipping ) {
				$result = shopp_setting('free_shipping_text');
				if ( empty($result) ) $result = __('Free Shipping!','Shopp');
			}

			else $result = $options['label'];
		} else {
			if ( false === $O->Totals->total('shipping') )
				return __("Enter Postal Code","Shopp");
			elseif ( false === $O->Totals->shipping )
				return __("Not Available","Shopp");
			else $result = $O->Totals->total('shipping');
		}
		return $result;
	}

	public static function shipping_estimates ( $result, $options, $O ) {
		$defaults = array(
			'postcode' => true,
			'class' => 'ship-estimates'
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( empty($O->shipped) ) return '';

		$base = shopp_setting('base_operations');
		$markets = shopp_setting('target_markets');
		$Shipping = ShoppOrder()->Shipping;

		if (empty($markets)) return '';

		foreach ($markets as $iso => $country) $countries[$iso] = $country;
		if ( ! empty($Shipping->country) ) $selected = $Shipping->country;
		else $selected = $base['country'];
		$postcode = ( Shopp::str_true($postcode) || $O->showpostcode );

		$button = isset($button) ? esc_attr($button) : __('Estimate Shipping & Taxes', 'Shopp');

		$_ = '<div class="' . $class . '">';
		if ( count($countries) > 1 ) {
			$_ .= '<span>';
			$_ .= '<select name="shipping[country]" id="shipping-country">';
			$_ .= menuoptions($countries, $selected, true);
			$_ .= '</select>';
			$_ .= '</span>';
		} else {
			$_ .= '<input type="hidden" name="shipping[country]" id="shipping-country" value="' . key($markets) . '" />';
		}
		if ( $postcode ) {
			$_ .= '<span>';
			$_ .= '<input type="text" name="shipping[postcode]" id="shipping-postcode" size="6" value="' . $Shipping->postcode . '"' . inputattrs($options) . ' />&nbsp;';
			$_ .= '</span>';
			$_ .= shopp('cart','get-update-button', array('value' => $button));
		}

		return $_ . '</div>';
	}

	public static function sidecart ( $result, $options, $O ) {
		if ( ! shopp_setting_enabled('shopping_cart') ) return '';
		ob_start();
		locate_shopp_template(array('sidecart.php'), true);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public static function subtotal ( $result, $options, $O ) {
		return $O->Totals->total('order');
	}

	public static function tax ( $result, $options, $O ) {
		$defaults = array(
			'label' => false,
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! empty($label) ) return $label;

		return (float) $O->Totals->total('tax');

	 }

	public static function total ( $result, $options, $O ) {
		return $O->Totals->total();
	}

	public static function total_items ( $result, $options, $O ) {
	 	return $O->count();
	}

	public static function total_discounts ( $result, $options, $O ) {
		return ShoppOrder()->Discounts->count();
	}

	public static function total_quantity ( $result, $options, $O ) {
	 	return $O->Totals->total('quantity');
	}

	public static function update_button ( $result, $options, $O ) {
		$submit_attrs = array('title', 'value', 'disabled', 'tabindex', 'accesskey', 'class');
		if ( ! isset($options['value']) ) $options['value'] = __('Update Subtotal', 'Shopp');
		if ( isset($options['class']) ) $options['class'] .= ' update-button';
		else $options['class'] = 'update-button';
		return '<input type="submit" name="update"' . inputattrs($options, $submit_attrs) . ' />';
	}

	public static function url ( $result, $options, $O ) {
		return Shopp::url(false, 'cart');
	}

}
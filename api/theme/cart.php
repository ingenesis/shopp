<?php
/**
 * cart.php
 *
 * ShoppCartThemeAPI provides shopp('cart') Theme API tags
 *
 * @copyright Ingenesis Limited, 2012-2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp\API\Theme\Cart
 * @version   1.3
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides shopp('cart') Theme API behaviors
 *
 * @api
 **/
class ShoppCartThemeAPI implements ShoppAPI {
	/**
	 * @var array The registry of available `shopp('cart')` properties
	 * @internal
	 **/
	static $register = array(
		'_cart',
		'applycode'              => 'applycode',
		'applygiftcard'          => 'applygiftcard',
		'discount'               => 'discount',
		'discountapplied'        => 'discount_applied',
		'discountname'           => 'discount_name',
		'discountremove'         => 'discount_remove',
		'discounts'              => 'discounts',
		'discountsavailable'     => 'discounts_available',
		'downloaditems'          => 'download_items',
		'emptybutton'            => 'empty_button',
		'function'               => 'cart_function',
		'hasdiscount'            => 'has_discount',
		'hasdiscounts'           => 'has_discounts',
		'hasdownloads'           => 'has_downloads',
		'hasitems'               => 'has_items',
		'hasshipcosts'           => 'has_ship_costs',
		'hasshipped'             => 'has_shipped',
		'hasshippingmethods'     => 'has_shipping_methods',
		'hastaxes'               => 'has_taxes',
		'items'                  => 'items',
		'lastitem'               => 'last_item',
		'needsshipped'           => 'needs_shipped',
		'needsshippingestimates' => 'needs_shipping_estimates',
		'referer'                => 'referrer',
		'referrer'               => 'referrer',
		'shipping'               => 'shipping',
		'shippingestimates'      => 'shipping_estimates',
		'shippeditems'           => 'shipped_items',
		'sidecart'               => 'sidecart',
		'subtotal'               => 'subtotal',
		'tax'                    => 'tax',
		'total'                  => 'total',
		'totaldiscounts'         => 'total_discounts',
		'totalitems'             => 'total_items',
		'totalquantity'          => 'total_quantity',
		'updatebutton'           => 'update_button',
		'url'                    => 'url',
		'hassavings'             => 'has_savings',
		'savings'                => 'savings',

		/* @deprecated tag names - do not use */
		'haspromos'       => 'has_discounts',
		'promocode'       => 'applycode',
		'promos'          => 'discounts',
		'promosavailable' => 'discounts_available',
		'promodiscount'   => 'discount_applied',
		'promoname'       => 'discount_name',
		'totalpromos'     => 'total_discounts',
	);

	/**
	 * Provides the Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		return 'cart';
	}

	/**
	 * Returns the global context object used in the shopp('cart') call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param ShoppCart $Object The ShoppCart object to set as the working context
	 * @param string    $object The context being worked on by the Theme API
	 * @return ShoppCart The active ShoppCart context
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'ShoppOrder') && isset($Object->Cart) && 'cart' == strtolower($object) )
			return $Object->Cart;
		else if ( strtolower($object) != 'cart' ) return $Object; // not mine, do nothing

		$Order = ShoppOrder();
		return $Order->Cart;
	}

	/**
	 * Filter callback to add standard monetary option behaviors
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param string    $result    The output
	 * @param array     $options   The options
	 * - **wrap**: `on` (on, off) Wrap the amount in DOM-accessible markup
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * @param string    $property  The tag property name
	 * @param ShoppCart $O         The working object
	 * @return ShoppCart The active ShoppCart context
	 **/
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

	/**
	 * Displays the discount code input widget
	 *
	 * @api `shopp('cart.applycode')`
	 * @since 1.0
	 *
	 * @param string   $result  The output
	 * @param array    $options The options
	 * - **before**: `<p class="error">` Markup to add before the widget
	 * - **after**: `</p>` Markup to add after the widget
	 * - **label**: The label to use for the submit button
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]` `[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * @param ShoppCart $O      The working object
	 * @return string The modified output
	 **/
	public static function applycode ( $result, $options, $O ) {

		$submit_attrs = array( 'title', 'value', 'disabled', 'tabindex', 'accesskey', 'class', 'autocomplete', 'placeholder', 'required' );

		// Skip if discounts are not available
		if ( ! self::discounts_available(false, false, $O) ) return false;

		$defaults = array(
			'before' => '<p class="error">',
			'after'  => '</p>',
			'label'  => Shopp::__('Apply Discount')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$result = '<div class="applycode">';

		$Errors = ShoppErrorStorefrontNotices();
		if ( $Errors->exist() ) {
			while ( $Errors->exist() )
				$result .=  $before . $Errors->message() . $after;
		}

		$result .= '<span><input type="text" id="discount-code" name="discount" value="" size="10" /></span>';
		$result .= '<span><input type="submit" id="apply-code" name="update" ' . inputattrs($options, $submit_attrs) . ' /></span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Displays a gift card code input widget
	 *
	 * @api `shopp('cart.applygiftcard')`
	 * @since 1.3
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **before**: `<p class="error">` Markup to add before the widget
	 * - **after**: `</p>` Markup to add after the widget
	 * - **label**: The label to use for the submit button
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * @param ShoppCart $O       The working object
	 * @return string The modified output
	 **/
	public static function applygiftcard ( $result, $options, $O ) {

		$defaults = array(
			'before' => '<p class="error">',
			'after'  => '</p>',
			'label'  => Shopp::__('Add Gift Card')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$submit_attrs = array( 'title', 'value', 'disabled', 'tabindex', 'accesskey', 'class', 'autocomplete', 'placeholder', 'required' );

		$result = '<div class="apply-giftcard">';

		$Errors = ShoppErrorStorefrontNotices();
		if ( $Errors->exist() ) {
			while ( $Errors->exist() )
				$result .=  $before . $Errors->message() . $after;
		}

		$result .= '<span><input type="text" id="giftcard" name="credit" value="" size="20" /></span>';
		$result .= '<span><input type="submit" id="apply-giftcard" name="giftcard" ' . inputattrs($options, $submit_attrs) . ' /></span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Provides the total discount amount
	 *
	 * @api `shopp('cart.discount')`
	 * @since 1.1
	 *
	 * @param string    $result   The output
	 * @param array     $options  The options
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * - **wrap**: `on` (on, off) Wrap the amount in DOM-accessible markup
	 * @param ShoppCart $O        The working object
	 * @return float The current total discount amount applied to the cart
	 **/
	public static function discount ( $result, $options, $O ) {
		return abs($O->total('discount'));
	}

	/**
	 * Provides a labeled version of the current applied discount
	 *
	 * This is used within a discount loop.
	 *
	 * @see ShoppCartThemeAPI::discounts() Used within a shopp('cart.discounts') loop
	 * @api `shopp('cart.discount-applied')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **label**: `%s off` The label format where `%s` is a token replaced with the discount name
	 * - **creditlabel**: `%s applied` The label for credits (not discounts) where `%s` is the credit name
	 * - **before**: Markup to use before the entry
	 * - **after**: Markup to use after the entry,
	 * - **remove**: `on` (on, off) Include a remove link that unapplies the discount
	 * @param ShoppCart $O       The working object
	 * @return The discount label
	 **/
	public static function discount_applied ( $result, $options, $O ) {
		$Discount = ShoppOrder()->Discounts->current();
		if ( ! $Discount->applies() ) return false;

		$defaults = array(
			'label'       => __('%s off', 'Shopp'),
			'creditlabel' => __('%s applied', 'Shopp'),
			'before'      => '',
			'after'       => '',
			'remove'      => 'on'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( false === strpos($label, '%s') )
			$label = "%s $label";

		$string = $before;

		switch ( $Discount->type() ) {
			case ShoppOrderDiscount::SHIP_FREE:		$string .= Shopp::esc_html__( 'Free Shipping!' ); break;
			case ShoppOrderDiscount::PERCENT_OFF:	$string .= sprintf(esc_html($label), percentage((float)$Discount->discount(), array('precision' => 0))); break;
			case ShoppOrderDiscount::AMOUNT_OFF:	$string .= sprintf(esc_html($label), money($Discount->discount())); break;
			case ShoppOrderDiscount::CREDIT:		$string .= sprintf(esc_html($creditlabel), money($Discount->amount())); break;
			case ShoppOrderDiscount::BOGOF:			list($buy, $get) = $Discount->discount(); $string .= ucfirst(strtolower(Shopp::esc_html__('Buy %s Get %s Free', $buy, $get))); break;
		}

		$options['label'] = '';
		if ( Shopp::str_true($remove) )
			$string .= '&nbsp;' . self::discount_remove('', $options, $O);

		$string .= $after;

		return $string;
	}

	/**
	 * Provides the discount name of the current applied discount
	 *
	 * @see ShoppCartThemeAPI::discounts() Used within a shopp('cart.discounts') loop
	 * @api `shopp('cart.discount-name')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return string The discount name
	 **/
	public static function discount_name ( $result, $options, $O ) {
		$Discount = ShoppOrder()->Discounts->current();
		if ( ! $Discount->applies() ) return false;
		return $Discount->name();
	}

	/**
	 * Displays a remove link the shopper can click to unapply a discount
	 *
	 * @see ShoppCartThemeAPI::discounts() Used within a shopp('cart.discounts') loop
	 * @api `shopp('cart.discount-remove')`
	 * @since 1.1
	 *
	 * @param string     $result  The output
	 * @param array      $options The options
	 * - **class**: `shoppui-remove-sign` The class attribute for the link
	 * - **label**: `<span class="hidden">Remove Discount</span>` The text label of the link
	 * @param ShoppCart  $O       The working object
	 * @return string The remove discount link
	 **/
	public static function discount_remove ( $result, $options, $O ) {
		$Discount = ShoppOrder()->Discounts->current();
		if ( ! $Discount->applies() ) return false;

		$defaults = array(
			'class' => 'shoppui-remove-sign',
			'label' => '<span class="hidden">' . Shopp::esc_html__('Remove Discount') . '</span>'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		return '<a href="' . Shopp::url(array('removecode' => $Discount->id()), 'cart') . '" class="' . $class . '">' . $label . '</a>';
	}

	/**
	 * Loops over the currently applied discounts
	 *
	 * Example usage:
	 * 	if ( shopp('cart.has-discounts') )
	 * 		while ( shopp('cart.discounts') )
	 * 			shopp('cart.discount-label');
	 *
	 * @api `shopp('cart.discounts')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there is a discount available, false otherwise
	 **/
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

	/**
	 * Determines if any discounts are available to be applied
	 *
	 * Discounts are available if there are discount promotions configured and
	 * the current discount limit has not been reached.
	 *
	 * @api `shopp('cart.discounts-available')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there are discounts available, false otherwise
	 **/
	public static function discounts_available ( $result, $options, $O ) {
		// Discounts are not available if there are no configured discounts loaded (Promotions)
		if ( ! ShoppOrder()->Promotions->available() ) return false;

		// Discounts are not available if the discount limit has been reached
		if ( shopp_setting('promo_limit') > 0 && ShoppOrder()->Discounts->count() >= shopp_setting('promo_limit') ) return false;
		return true;
	}

	/**
	 * Loops over only the downloadable items in the cart
	 *
	 * Example usage:
	 * 	if ( shopp('cart.has-downloads') )
	 * 		while ( shopp('cart.download-items') )
	 * 			shopp('cartitem.name');
	 *
	 * @api `shopp('cart.download-items')`
	 * @since 1.2
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if a there is a download item available, false otherwise
	 */
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

	/**
	 * Displays an empty cart button that when clicked will submit a request to empty the contents of the cart.
	 *
	 * @api `shopp('cart.empty-button')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * - **label**: `Empty Cart` Specifies the label value of the button
	 * @param ShoppCart $O       The working object
	 * @return string The empty button markup
	 **/
	public static function empty_button ( $result, $options, $O ) {
		$submit_attrs = array( 'title', 'value', 'disabled', 'tabindex', 'accesskey', 'class', 'autocomplete', 'placeholder', 'required' );

		$defaults = array(
			'label' => Shopp::__('Empty Cart'),
			'class' => 'empty-button'
		);
		$options = array_merge($defaults, $options);

		if ( false !== strpos($options['class'], 'empty-button') ) $options['class'] .= ' empty-button';

		return '<input type="submit" name="empty" id="empty-button" ' . inputattrs($options, $submit_attrs) . ' />';
	}

	/**
	 * Provides hidden inputs necessary for the cart to function properly
	 *
	 * @api `shopp('cart.function')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return string The hidden input markup
	 */
	public static function cart_function ( $result, $options, $O ) {
		return '<div class="hidden"><input type="hidden" id="cart-action" name="cart" value="true" /></div><input type="submit" name="update" id="hidden-update" />';
	}

	/**
	 * Determines if there is a discount amount applied to the cart.
	 *
	 * If there is a discount amount over 0, a discount is applied to the cart.
	 *
	 * @api `shopp('cart.has-discount')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there is a discount applied, false otherwise
	 */
	public static function has_discount ( $result, $options, $O ) {
		return ( abs($O->total('discount')) > 0 );
	}

	/**
	 * Determines if there are configured promotional discounts that apply.
	 *
	 * Note that this is different from `shopp('cart.has-discount')`. This
	 * tag determines if there are any configured promotional discount entries
	 * that are applied to the cart (whether they provide and discount amount or not).
	 *
	 * @api `shopp('cart.has-discounts')`
	 * @since 1.2
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there are discount entries applied, false otherwise
	 **/
	public static function has_discounts ( $result, $options, $O ) {
		$Discounts = ShoppOrder()->Discounts;
		$Discounts->rewind();
		return ($Discounts->count() > 0);
	}

	/**
	 * Determines if the cart has any downloadable items
	 *
	 * @api `shopp('cart.has-downloads')`
	 * @since 1.2
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there are downloads in the cart, false otherwise
	 **/
	public static function has_downloads ( $result, $options, $O ) {
		reset($O->downloads);
		return $O->downloads();
	}

	/**
	 * Determines if the cart has any items
	 *
	 * @api `shopp('cart.has-items')
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if the cart has items, false otherwise
	 **/
	public static function has_items ( $result, $options, $O ) {
		$O->rewind();
		return $O->count() > 0;
	}

	/**
	 * Determines if there are any shipping costs applied to the cart
	 *
	 * @api `shopp('cart.has-ship-costs')
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there are shipping costs, false otherwise
	 **/
	public static function has_ship_costs ( $result, $options, $O ) {
		return ($O->total('shipping') > 0);
	}

	/**
	 * Determines if there are any shipped items in the cart
	 *
	 * @api `shopp('cart.has-shipped')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there are shipped items in the cart, false otherwise
	 **/
	public static function has_shipped ( $result, $options, $O ) {
		reset($O->shipped);
		return $O->shipped();
	}

	/**
	 * Determines if there are shipping method options available for items in the cart
	 *
	 * @api `shopp('cart.has-shipping-methods')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if shipping methods are available, false otherwise
	 **/
	public static function has_shipping_methods ( $result, $options, $O ) {
		return ShoppShippingThemeAPI::has_options( $result, $options, $O );
	}

	/**
	 * Determines if there is a tax amount applied to the cart
	 *
	 * @api `shopp('cart.has-taxes')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there is a tax amount, false otherwise
	 **/
	public static function has_taxes ( $result, $options, $O ) {
		return ($O->total('tax') > 0);
	}

	/**
	 * Loops over the items in the cart
	 *
	 * Example usage:
	 * 	if ( shopp('cart.has-items') )
	 * 		while ( shopp('cart.items') )
	 * 			shopp('cartitem.name');
	 *
	 * @api `shopp('cart.items')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there is a next item, false otherwise
	 **/
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

	/**
	 * Provides the last item added to the cart
	 *
	 * @api `shopp('cart.last-item')`
	 * @since 1.3
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return ShoppCartItem The ShoppCartItem object last added to the cart
	 **/
	public static function last_item ( $result, $options, $O ) {
		return $O->added();
	}

	/**
	 * Detects if there are shipped items in the cart
	 *
	 * @api `shopp('cart.needs-shipped')`
	 * @since 1.2
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if there are shipped items in the cart, false otherwise
	 **/
	public static function needs_shipped ( $result, $options, $O ) {
		return ( ! empty($O->shipped) );
	}

	/**
	 * Determines if shipping cost estimates need to be calculated for the cart
	 *
	 * Shipping costs only need calculated when shipping is enabled, there are
	 * shipped items in the cart, and there isn't any free shipping discount
	 * applied to the cart.
	 *
	 * @api `shopp('cart.needs-shipping-estimates')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if shipping costs need calculated, false otherwise
	 **/
	public static function needs_shipping_estimates ( $result, $options, $O ) {
		return ( shopp_setting_enabled('shipping') && ! ShoppOrder()->Shiprates->free() && ! empty($O->shipped) );
	}

	/**
	 * Provides the URL for the referring page
	 *
	 * The referrer is the page the shopper was visiting before being
	 * sent to the cart page. If no referring page is available, the
	 * catalog page URL is given instead.
	 *
	 * @api `shopp('cart.referrer')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return string The referring page's URL
	 **/
	public static function referrer ( $result, $options, $O ) {
		$Shopping = ShoppShopping();
		$referrer = $Shopping->data->referrer;
		if ( ! $referrer ) $referrer = shopp('catalog', 'url', 'return=1');
		return $referrer;
	}

	/**
	 * Loops through only the shipped items in the cart
	 *
	 * @api `shopp('cart.shipped-items')`
	 * @since 1.2
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True for the next item, false otherwise
	 **/
	public static function shipped_items ( $result, $options, $O ) {
		if ( ! isset($O->_shipped_loop) ) {
			reset($O->shipped);
			$O->_shipped_loop = true;
		} else next($O->shipped);

		if ( current($O->shipped) ) return true;
		else {
			unset($O->_shipped_loop);
			reset($O->shipped);
			return false;
		}
	}

	/**
	 * Displays the shipping cost, or status of the shipping cost
	 *
	 * @api `shopp('cart.shipping')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **id**: Specify the id for the shipping cost to display (where multiple shipping costs are active)
	 * - **label**: The label for the shipping costs
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * - **wrap**: `on` (on, off) Wrap the amount in DOM-accessible markup
	 * @param ShoppCart $O       The working object
	 * @return string The status or amount of shipping costs
	 **/
	public static function shipping ( $result, $options, $O ) {
		if ( empty($O->shipped) ) return "";
		if ( isset($options['label']) ) {
			$options['currency'] = "false";
			if ( ShoppOrder()->Shiprates->free() ) {
				$result = shopp_setting('free_shipping_text');
				if ( empty($result) ) $result = Shopp::__('Free Shipping!');
			}

			else $result = $options['label'];
		} else {

			$shipping = $O->total('shipping');
			if ( isset($options['id']) ) {
				$Entry = $O->Totals->entry('shipping', $options['id']);
				if ( ! $Entry ) $shipping = false;
				else $shipping = $Entry->amount();
			}

			if ( false === $shipping )
				return Shopp::__('Enter Postal Code');
			elseif ( false === $shipping )
				return Shopp::__('Not Available');
			else $result = (float) $shipping;

		}
		return $result;
	}

	/**
	 * Displays the shipping estimate widget
	 *
	 * The shipping estimate widget allows shoppers to provide location
	 * information so that shipping costs can be calculated.
	 *
	 * @api `shopp('cart.shipping-estimates')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **class**: CSS class names to apply to the widget
	 * - **postcode**: `on` (on, off) Show the post code field in the widget
	 * @param ShoppCart $O       The working object
	 * @return string The markup for the shipping estimate widget
	 **/
	public static function shipping_estimates ( $result, $options, $O ) {
		$defaults = array(
			'postcode' => 'on',
			'class'    => 'ship-estimates',
			'label'    => Shopp::__('Estimate Shipping & Taxes')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( empty($O->shipped) ) return '';

		$base = shopp_setting('base_operations');
		$markets = shopp_setting('target_markets');
		$Shipping = ShoppOrder()->Shipping;

		if ( empty($markets) ) return '';

		foreach ($markets as $iso => $country) $countries[ $iso ] = $country;
		if ( ! empty($Shipping->country) ) $selected = $Shipping->country;
		else $selected = $base['country'];
		$postcode = ( Shopp::str_true($postcode) || $O->showpostcode );

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
			$_ .= shopp('cart','get-update-button', array('value' => $label));
		}

		return $_ . '</div>';
	}

	/**
	 * Displays the side cart widget
	 *
	 * The side cart widget shows a small summarized version of the
	 * shopping cart. It uses the `sidecart.php` Shopp content template
	 * for markup and layout.
	 *
	 * @api `shopp('cart.sidecart')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return string The markup for the sidecart widget
	 **/
	public static function sidecart ( $result, $options, $O ) {
		if ( ! shopp_setting_enabled('shopping_cart') ) return '';

		ob_start();
		locate_shopp_template(array('sidecart.php'), true);
		return ob_get_clean();

	}

	/**
	 * Provides the subtotal amount of the cart
	 *
	 * @api `shopp('cart.subtotal')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **wrap**: `on` (on, off) Wrap the amount in DOM-accessible markup
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * - **taxes**: `on` (on, off) Include taxes in the subtotal amount when inclusive taxes are used (or off to exclude them)
	 * @param ShoppCart $O       The working object
	 * @return string The subtotal amount
	 **/
	public static function subtotal ( $result, $options, $O ) {
		$defaults = array(
			'taxes' => 'on'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		$subtotal = $O->total('order');

		// Handle no-tax option for tax inclusive storefronts
		if ( ! Shopp::str_true($taxes) && shopp_setting_enabled('tax_inclusive') ) {
			$tax = $O->Totals->entry('tax', 'Tax');
			if ( is_a($tax, 'OrderAmountItemTax') )
				$subtotal -= $tax->amount();
		}

		return (float)$subtotal;
	}

	/**
	 * Provides the tax amount for the cart
	 *
	 * @api `shopp('cart.tax')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * - **id**: Specify the tax amount to display when multiple taxes are applied to the cart
	 * - **label**: Provide the tax label instead of the amount
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * - **wrap**: `on` (on, off) Wrap the amount in DOM-accessible markup
	 * @param ShoppCart $O       The working object
	 * @return string The tax amount
	 **/
	public static function tax ( $result, $options, $O ) {
		$defaults = array(
			'label' => false,
			'id' => false
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! empty($label) ) return $label;

		$tax = (float) $O->total('tax');
		if ( ! empty($id) ) {
			$Entry = $O->Totals->entry('tax', $id);
			if ( empty($Entry) ) return false;
			$tax = (float) $Entry->amount();
		}

		return $tax;

	 }

 	/**
 	 * Provides the total amount of the cart
 	 *
 	 * @api `shopp('cart.total')`
 	 * @since 1.0
 	 *
 	 * @param string    $result  The output
 	 * @param array     $options The options
 	 * - **money**: `on` (on, off) Format the amount in the current currency format
 	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
 	 * - **wrap**: `on` (on, off) Wrap the amount in DOM-accessible markup
 	 * @param ShoppCart $O       The working object
 	 * @return string The total amount
 	 **/
	public static function total ( $result, $options, $O ) {
		return (float)$O->total();
	}

 	/**
 	 * Provides the count of the total number of different items in the cart
 	 *
 	 * @api `shopp('cart.total-items')`
 	 * @since 1.2
 	 *
 	 * @param string    $result  The output
 	 * @param array     $options The options
 	 * @param ShoppCart $O       The working object
 	 * @return int The number of items in the cart
 	 **/
	public static function total_items ( $result, $options, $O ) {
	 	return (int)$O->count();
	}

 	/**
 	 * Provides the count of the total number of discounts applied to the cart
 	 *
 	 * @api `shopp('cart.total-discounts')`
 	 * @since 1.2
 	 *
 	 * @param string    $result  The output
 	 * @param array     $options The options
 	 * @param ShoppCart $O       The working object
 	 * @return int The number of discounts on the cart
 	 **/
	public static function total_discounts ( $result, $options, $O ) {
		return (int)ShoppOrder()->Discounts->count();
	}

 	/**
 	 * Provides the sum of the item quantities in the cart
 	 *
 	 * @api `shopp('cart.total-quantity')`
 	 * @since 1.2
 	 *
 	 * @param string    $result  The output
 	 * @param array     $options The options
 	 * @param ShoppCart $O       The working object
 	 * @return int The total quantity of items in the cart
 	 **/
	public static function total_quantity ( $result, $options, $O ) {
	 	return (int)$O->total('quantity');
	}

	/**
	 * Display a cart update button
	 *
	 * When a shopper clicks the update button, the cart is submitted and all
	 * cart totals are recalculated.
	 *
	 * @api	`shopp('cart.update-button')`
	 * @since 1.0
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * - **label**: Specifies the button label value
	 * @return string The markup for the update button
	 */
	public static function update_button ( $result, $options, $O ) {
		$submit_attrs = array( 'title', 'value', 'disabled', 'tabindex', 'accesskey', 'class', 'autocomplete', 'placeholder', 'required' );

		$defaults = array(
			'label' => Shopp::__('Update Subtotal'),
			'class' => 'update-button'
		);
		$options = array_merge($defaults, $options);

		if ( false !== strpos($options['class'], 'update-button') ) $options['class'] .= ' update-button';

		return '<input type="submit" name="update"' . inputattrs($options, $submit_attrs) . ' />';
	}

	/**
	 * Provides the full URL for the shopping cart page
	 *
	 * @api `shopp('cart.url')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return string The cart page URL
	 */
	public static function url ( $result, $options, $O ) {
		return Shopp::url(false, 'cart');
	}

	/**
	 * Check if any of the items in the cart are on sale
	 *
	 * @api `shopp('cart.has-savings')`
	 * @since 1.1
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return bool True if an item is on sale, false otherwise
	 */
	public static function has_savings ( $result, $options, $O ) {
		// loop thru cart looking for $Item->sale == 'on' or '1' etc
		foreach( $O as $item ) {
			if ( str_true( $item->sale ) ) return true;
		}

		return false;
	}

	/**
	 * Provides the total discount savings on the order
	 *
	 * This figure includes products with "On Sale" pricing plus any
	 * discounts applied to the order
	 *
	 * @api `shopp('cart.savings')`
	 * @since 1.3
	 *
	 * @param string    $result  The output
	 * @param array     $options The options
	 * @param ShoppCart $O       The working object
	 * @return float The total savings on the order
	 */
	public static function savings ( $result, $options, $O ) {
		$total = 0;

		foreach( $O as $item ){
			$total += $item->option->price * $item->quantity;
		}

		return $total - ( $O->total('order') + $O->total('discount') );
	}

}

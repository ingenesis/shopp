<?php
/**
 * cartitem.php
 *
 * ShoppCartItemThemeAPI provides shopp('cartitem') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2014
 * @package Shopp\API\Theme\CartItem
 * @version 1.3
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_cartitem_input_data', 'wpautop');

/**
 * Provides support for the shopp('cartitem') tags
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppCartItemThemeAPI implements ShoppAPI {
	/**
	 * @var array The registry of available `shopp('cart')` properties
	 * @internal
	 **/
	static $register = array(
		'_cartitem',
		'id' => 'id',
		'product' => 'product',
		'name' => 'name',
		'type' => 'type',
		'link' => 'url',
		'url' => 'url',
		'sku' => 'sku',
		'description' => 'description',
		'discount' => 'discount',
		'unitprice' => 'unitprice',
		'unittax' => 'unittax',
		'discounts' => 'discounts',
		'tax' => 'tax',
		'total' => 'total',
		'taxrate' => 'taxrate',
		'quantity' => 'quantity',
		'remove' => 'remove',
		'onsale' => 'onsale',
		'optionlabel' => 'option_label',
		'options' => 'options',
		'price' => 'price',
		'prices' => 'prices',
		'hasaddons' => 'has_addons',
		'addons' => 'addons',
		'addon' => 'addon',
		'addonslist' => 'addons_list',
		'hasinputs' => 'has_inputs',
		'incategory' => 'in_category',
		'inputs' => 'inputs',
		'input' => 'input',
		'inputslist' => 'inputs_list',
		'coverimage' => 'coverimage',
		'thumbnail' => 'coverimage',
		'saleprice' => 'saleprice',
		'saleprices' => 'saleprices',
	);

	/**
	 * Provides the API context name
	 *
	 * @internal
	 * @return string The API context name
	 **/
	public static function _apicontext () {
		return 'cartitem';
	}

	/**
	 * _setobject - returns the global context object used in the shopp('cartitem) call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return ShoppCartItem|bool The working ShoppCartItem context
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'Item') ) return $Object;

		if ( strtolower($object) != 'cartitem' ) return $Object; // not mine, do nothing
		else {
			$Cart = ShoppOrder()->Cart;
			$Item = false;
			if ( isset($Cart->_item_loop) ) { $Item = $Cart->current(); $Item->_id = $Cart->key(); return $Item; }
			elseif ( isset($Cart->_shipped_loop) ) { $Item = current($Cart->shipped); $Item->_id = key($Cart->shipped); return $Item; }
			elseif ( isset($Cart->_downloads_loop) ) { $Item = current($Cart->downloads); $Item->_id = key($Cart->downloads); return $Item; }
			return false;
		}
	}

	/**
	 * Filter callback to add standard monetary option behaviors
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param string    $result    The output
	 * @param array     $options   The options
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * @param string    $property  The tag property name
	 * @param ShoppCart $O         The working object
	 * @return ShoppCart The active ShoppCart context
	 **/
	public static function _cartitem ( $result, $options, $property, $O ) {

		// Passthru for non-monetary results
		$monetary = array('discount', 'unitprice', 'unittax', 'discounts', 'tax', 'total', 'price', 'prices', 'saleprice', 'saleprices');
		if ( ! in_array($property, $monetary) || ! is_numeric($result) ) return $result;

		// @deprecated currency parameter
		if ( isset($options['currency']) ) $options['money'] = $options['currency'];

		$defaults = array(
			'money' => 'on',
			'number' => false,
			'show' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( in_array($show, array('%', 'percent')) )
			return $result; // Pass thru percentage rendering

		if ( Shopp::str_true($number) ) return $result;
		if ( Shopp::str_true($money)  ) $result = money( roundprice($result) );

		return $result;

	}

	/**
	 * Provides the cart item ID (fingerprint)
	 *
	 * @api `shopp('cartitem.id')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The item id (fingerprint)
	 **/
	public static function id ( $result, $options, $O ) {
		return $O->_id;
	}

	/**
	 * Provides the product ID (or price record ID)
	 *
	 * @api
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **priceline**: Requests the currently selected price record ID for the cart item
	 * @param ShoppCartItem $O       The working object
	 * @return int The id for the product
	 **/
	public static function product ( $result, $options, $O ) {
		if ( isset($options['priceline']) && Shopp::str_true($options['priceline']) )
			return $O->priceline;
		return $O->product;
	}

	/**
	 * Provides the product name of the cart item
	 *
	 * @api `shopp('cartitem.name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function name ( $result, $options, $O ) {
		return $O->name;
	}

	/**
	 * The type of product for the cart item
	 *
	 * This is currently one of: shipped, download, virtual, donation, or recurring
	 *
	 * @api `shopp('cartitem.type')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The product type for the cart item
	 **/
	public static function type ( $result, $options, $O ) {
		return $O->type;
	}

	/**
	 * The URL of the product for the cart item
	 *
	 * @api `shopp('cartitem.url')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The product URL
	 **/
	public static function url ( $result, $options, $O ) {
		return Shopp::url( '' == get_option('permalink_structure') ? array(ShoppProduct::$posttype => $O->slug ) : $O->slug, false );
	}

	/**
	 * Provides the product SKU (stock keeping unit)
	 *
	 * @api `shopp('cartitem.sku')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The product SKU
	 **/
	public static function sku ( $result, $options, $O ) {
		return $O->sku;
	}

	/**
	 * The product description of the cart item
	 *
	 * @api `shopp('cartitem.description')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The product description
	 **/
	public static function description ( $result, $options, $O ) {
		return $O->description;
	}

	/**
	 * Provides the per-unit discount amount currently applied to the cart item
	 *
	 * @api `shopp('cartitem.discount')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **catalog**: Provide the catalog discount applied (catalog sale price discount amount, not the entire discount)
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * - **show**: (%, percent) Provide the percent discount instead of the amount
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function discount ( $result, $options, $O ) {

		$defaults = array(
			'catalog' => false,
			'show' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		// Item unit discount
		$discount = $O->discount;

		if ( Shopp::str_true($catalog) )
			$discount = $O->option->price - $O->option->promoprice;

		if ( in_array($show, array('%', 'percent')) )
			return percentage( ( $discount / $O->option->price ) * 100, array('precision' => 0) );

		return (float) $discount;
	}

	/**
	 * Provides the total discounts applied to the cart item
	 *
	 * This is the unit discount * quantity
	 *
	 * @api `shopp('cartitem.discounts')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **catalog**: Provide the catalog discount applied (catalog sale price discount amount, not the entire discount)
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * - **show**: (%, percent) Provide the percent discount instead of the amount
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function discounts ( $result, $options, $O ) {
		$defaults = array(
			'catalog' => false,
			'show' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		// Unit discount * quantity
		$discounts = $O->discounts;

		if ( Shopp::str_true($catalog) )
			$discounts =  $O->quantity * self::discount('', array('catalog' => true, 'number' => true), $O);

		if ( in_array($show, array('%', 'percent')) )
			return percentage( ( $discounts / self::prices('', array('number' => true), $O) ) * 100, array('precision' => 0) );

		return (float) $discounts;
	}

	/**
	 * The unit price of the cart item
	 *
	 * @api `shopp('cartitem.unitprice')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **taxes**: `null` (on, off) On to include taxes in the unit price, off to exclude taxes
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function unitprice ( $result, $options, $O ) {

		$taxes = isset( $options['taxes'] ) ? Shopp::str_true( $options['taxes'] ) : null;

		$unitprice = (float) $O->unitprice;
		$unitprice = self::_taxes($unitprice, $O, $taxes);
		return (float) $unitprice;
	}

	/**
	 * The unit tax amount applied to the cart item
	 *
	 * @api `shopp('cartitem.unit-tax')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function unittax ( $result, $options, $O ) {
		return (float) $O->unittax;
	}

	/**
	 * The total tax applied to the cart item
	 *
	 * @api	`shopp('cartitem.tax')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function tax ( $result, $options, $O ) {
		return (float) $O->tax;
	}

	/**
	 * The total cost of the cart item
	 *
	 * @api `shopp('cartitem.total')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function total ( $result, $options, $O ) {
		$taxes = isset( $options['taxes'] ) ? Shopp::str_true( $options['taxes'] ) : null;

		$total = (float) $O->total;
		$total = self::_taxes($total, $O, $taxes, $O->quantity);

		return (float) $total;
	}

	/**
	 * Provides the tax rate percentage
	 *
	 * @api	`shopp('cartitem.taxrate')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The tax rate percentage (10.1%)
	 **/
	public static function taxrate ( $result, $options, $O ) {

		if ( count($O->taxes) == 1 ) {
			$Tax = reset($O->taxes);
			return percentage( $Tax->rate * 100, array( 'precision' => 1 ) );
		}

		$compounding = false;
		$rate = 0;
		foreach ( $O->taxes as $Tax ) {
			$rate += $Tax->rate;
			if ( Shopp::str_true($Tax->compound) ) {
				$compounding = true;
				break;
			}
		}

		if ( $compounding )
			$rate = $O->unittax / $O->unitprice;

		return percentage( $rate * 100, array( 'precision' => 1 ) );

	}

	/**
	 * Provides the quantity selector and current cart item quantity
	 *
	 * @api `shopp('cartitem.quantity')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **input**: (menu, text, hidden) Sets the type of input element to render. Menu for a `<select>` menu, text for text box, hidden for a hidden input
	 * - **options**: `1-15,20,25,30,35,40,45,50,60,70,80,90,100` Defines the default options when **input** is set to `menu`. Values are separated by commas. Ranges will automatically generate number options within the range.
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **alt**: Specifies an alternate text for images (only for type="image")
	 * - **checked**: Specifies that an `<input>` element should be pre-selected when the page loads (for type="checkbox" or type="radio")
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **format**: Specifies special field formatting class names for JS validation
	 * - **minlength**: Sets a minimum length for the field enforced by JS validation
	 * - **maxlength**: Specifies the maximum number of characters allowed in an `<input>` element
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **readonly**: Specifies that an input field is read-only
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **size**: `5` Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCartItem $O       The working object
	 * @return string The cart item quantity or quantity element markup
	 **/
	public static function quantity ( $result, $options, $O ) {
		$result = $O->quantity;
		if ( 'Donation' === $O->type && 'on' === $O->donation['var'] ) return $result;
		if ( 'Subscription' === $O->type || 'Membership' === $O->type ) return $result;
		if ( 'Download' === $O->type && ! shopp_setting_enabled('download_quantity') ) return $result;
		if ( isset($options['input']) && 'menu' === $options['input'] ) {
			if ( ! isset($options['value']) ) $options['value'] = $O->quantity;
			if ( ! isset($options['options']) )
				$values = '1-15,20,25,30,35,40,45,50,60,70,80,90,100';
			else $values = $options['options'];

			if ( strpos($values, ',') !== false ) $values = explode(',', $values);
			else $values = array($values);
			$qtys = array();
			foreach ( $values as $value ) {
				if ( false !== strpos($value,'-') ) {
					$value = explode("-", $value);
					if ($value[0] >= $value[1]) $qtys[] = $value[0];
					else for ($i = $value[0]; $i < $value[1]+1; $i++) $qtys[] = $i;
				} else $qtys[] = $value;
			}
			$result = '<select name="items['.$O->_id.'][quantity]">';
			foreach ( $qtys as $qty )
				$result .= '<option' . ( ($qty == $O->quantity) ? ' selected="selected"' : '' ) . ' value="' . $qty . '">' . $qty . '</option>';
			$result .= '</select>';
		} elseif ( isset($options['input']) && Shopp::valid_input($options['input']) ) {
			if ( ! isset($options['size']) ) $options['size'] = 5;
			if ( ! isset($options['value']) ) $options['value'] = $O->quantity;
			$result = '<input type="' . $options['input'] . '" name="items[' . $O->_id . '][quantity]" id="items-' . $O->_id . '-quantity" ' . inputattrs($options) . '/>';
		} else $result = $O->quantity;
		return $result;
	}

	/**
	 * Generates markup for an element to remove a cart item
	 *
	 * By default, the remove element is a plain text link.
	 *
	 * @api `shopp('cartitem.remove')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **label**: `Remove` The text label shown
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **input**: (button, checkbox) Display the remove element as an input instead of a link.
	 * @param ShoppCartItem $O       The working object
	 * @return string The remove button markup
	 **/
	public static function remove ( $result, $options, $O ) {
		$label = __('Remove', 'Shopp');
		if ( isset($options['label']) ) $label = $options['label'];
		if ( isset($options['class']) ) $class = ' class="'.$options['class'].'"';
		else $class = ' class="remove"';
		if ( isset($options['input']) ) {
			switch ($options['input']) {
				case "button":
					$result = '<button type="submit" name="remove[' . $O->_id . ']" value="' . $O->_id . '"' . $class . ' tabindex="">' . $label . '</button>'; break;
				case "checkbox":
				    $result = '<input type="checkbox" name="remove[' . $O->_id . ']" value="' . $O->_id . '"' . $class . ' tabindex="" title="' . $label . '"/>'; break;
			}
		} else {
			$result = '<a href="' . href_add_query_arg(array('cart' => 'update', 'item' => $O->_id, 'quantity' => 0), Shopp::url(false, 'cart')) . '"' . $class . '>' . $label . '</a>';
		}
		return $result;
	}

	/**
	 * Displays the label of a cart item variant option
	 *
	 * @api `shopp('cartitem.option-label')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The variant option label
	 **/
	public static function option_label ( $result, $options, $O ) {
		return $O->option->label;
	}

	/**
	 * Displays the currently selected product variation for the item or a drop-down menu to change the selection
	 *
	 * @api `shopp('cartitem.options')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **before**: ` ` Markup to add before the options
	 * - **after**: ` ` Markup to add after the options
	 * - **show**: (selected) Set to `selected` to provide the currently selected option label @see `shopp('cartitem.option-label')`
	 * - **class**: The class attribute specifies one or more class-names for the menu element
	 * @param ShoppCartItem $O       The working object
	 * @return string The options markup
	 **/
	public static function options ( $result, $options, $O ) {
		$class = "";
		if ( ! isset($options['before']) ) $options['before'] = '';
		if ( ! isset($options['after']) ) $options['after'] = '';
		if ( isset($options['show']) &&	strtolower($options['show']) == "selected" )
			return ( ! empty($O->option->label) ) ? $options['before'] . $O->option->label . $options['after'] : '';
		if ( isset($options['class']) ) $class = ' class="' . $options['class'] . '" ';
		if ( count($O->variants) > 1 ) {
			$result .= $options['before'];
			$result .= '<input type="hidden" name="items[' . $O->_id . '][product]" value="' . $O->product . '"/>';
			$result .= ' <select name="items[' . $O->_id . '][price]" id="items-' . $O->_id . '-price"' . $class . '>';
			$result .= $O->options($O->priceline);
			$result .= '</select>';
			$result .= $options['after'];
		}
		return $result;
	}

	/**
	 * Checks if the item has addon options
	 *
	 * @api	`shopp('cartitem.has-addons')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return bool True if the cart item has addon options, false otherwise
	 **/
	public static function has_addons ( $result, $options, $O ) {
		reset($O->addons);
		return ( count($O->addons) > 0 );
	}

	/**
	 * Iterate over the available addon options for the item
	 *
	 * @api `shopp('cartitem.addons')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return bool True if the next item exists, false otherwise
	 **/
	public static function addons ( $result, $options, $O ) {
		if ( ! isset($O->_addons_loop) ) {
			reset($O->addons);
			$O->_addons_loop = true;
		} else next($O->addons);

		if ( false !== current($O->addons) ) return true;

		unset($O->_addons_loop);
		reset($O->addons);
		return false;
	}

	/**
	 * Displays one or more properties of the current cart item addon.
	 *
	 *
	 *
	 * @api `shopp('cartitem.addon')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **id**: (on, off) Includes the addon option ID (the ShoppPrice record ID)
	 * - **inventory**: (on, off) Include the inventory status of the addon option
	 * - **label**: (on, off) Includes the cart item addon option label
	 * - **menu**: (on, off) Includes the menu label the addon option is assigned to
	 * - **price**: (on, off) Include the regular price of the addon option
	 * - **sale**: (on, off) Include the sale status of the addon option
	 * - **saleprice**: (on, off) Include the sale price of the addon option
	 * - **separator**: The separator to use beteween requested fields
	 * - **shipfee**: (on, off) Include the shipping fee of the addon option
	 * - **sku**: (on, off) Include the SKU (Stock Keeping Unit) of the addon option
	 * - **stock**: (on, off) Include the stock level of the addon option
	 * - **type: (on, off) Includes the addon type (Shipped, Download, Donation, Subscription, Virtual)
	 * - **unitprice**: (on, off) Include the actual unit price of the addon option
	 * - **weight**: (on, off) Include the weight of the cart item addon option
	 * @param ShoppCartItem $O       The working object
	 * @return void
	 **/
	public static function addon ( $result, $options, $O ) {
		if ( empty($O->addons) ) return false;
		$addon = current($O->addons);
		$defaults = array(
			'separator' => ' '
		);
		$options = array_merge($defaults, $options);

		$fields = array('id', 'type', 'menu', 'label', 'sale', 'saleprice', 'price', 'inventory', 'stock', 'sku', 'weight', 'shipfee', 'unitprice');

		$fieldset = array_intersect($fields, array_keys($options));
		if ( empty($fieldset) ) $fieldset = array('label');

		$_ = array();
		foreach ( $fieldset as $field ) {
			switch ( $field ) {
				case 'menu':
					list($menus, $menumap) = self::_addon_menus();
					$_[] = isset( $menumap[ $addon->options ]) ? $menus[ $menumap[ $addon->options ] ] : '';
					break;
				case 'weight': $_[] = $addon->dimensions['weight'];
				case 'saleprice':
				case 'price':
				case 'shipfee':
				case 'unitprice':
					if ( $field === 'saleprice' ) $field = 'promoprice';
					if ( isset($addon->$field) ) {
						$_[] = ( isset($options['currency']) && Shopp::str_true($options['currency']) ) ?
							 money($addon->$field) : $addon->$field;
					}
					break;
				default:
					if ( isset($addon->$field) )
						$_[] = $addon->$field;
			}

		}
		return join($options['separator'], $_);
	}

	/**
	 * Displays all of the product addons for the cart item in an unordered list
	 *
	 * @api `shopp('cartitem.addons-list')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **before**: ` ` Markup to add before the list
	 * - **after**: ` ` Markup to add after the list
	 * - **class**: The class attribute specifies one or more class-names for the list
	 * - **exclude**: Used to specify addon labels to exclude from the list. Multiple addons can be excluded by separating them with a comma: `Addon Label 1,Addon Label 2...`
	 * - **separator**: `: ` The separator to use between the menu name and the addon options
	 * - **prices**: `on` (on, off) Shows or hides prices with the addon label
	 * - **taxes**: `on` (on, off) Include taxes in the addon option price shown when `prices=on`
	 * @param ShoppCartItem $O       The working object
	 * @return string The addon list markup
	 **/
	public static function addons_list ( $result, $options, $O ) {
		if ( empty($O->addons) ) return false;
		$defaults = array(
			'before' => '',
			'after' => '',
			'class' => '',
			'exclude' => '',
			'separator' => ': ',
			'prices' => true,
			'taxes' => shopp_setting('tax_inclusive')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$classes = ! empty($class) ? ' class="' . esc_attr($class) . '"' : '';
		$excludes = explode(',', $exclude);
		$prices = Shopp::str_true($prices);
		$taxes = Shopp::str_true($taxes);

		// Get the menu labels list and addon options to menus map
		list($menus, $menumap) = self::_addon_menus();

		$result .= $before . '<ul' . $classes . '>';
		foreach ( $O->addons as $id => $addon ) {

			if ( in_array($addon->label, $excludes) ) continue;
			$menu = isset( $menumap[ $addon->options ]) ? $menus[ $menumap[ $addon->options ] ] . $separator : false;

			$price = ( Shopp::str_true($addon->sale) ? $addon->promoprice : $addon->price );
			if ( $taxes && $O->taxrate > 0 )
				$price = $price + ( $price * $O->taxrate );

			if ( $prices ) $pricing = " (" . ( $addon->price < 0 ?'-' : '+' ) . money($price) . ')';
			$result .= '<li>' . $menu . $addon->label . $pricing . '</li>';
		}
		$result .= '</ul>' . $after;
		return $result;
	}

	/**
	 * Checks if the cart item has any custom product input data assigned to it
	 *
	 * @api `shopp('cartitem.has-inputs')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return bool True if the cart item has custom input data, false otherwise
	 **/
	public static function has_inputs ( $result, $options, $O ) {
		reset($O->data);
		return ( count($O->data) > 0 );
	}

	/**
	 * Checks if the cart item is in a specified category
	 *
	 * @api `shopp('cartitem.in-category')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **id**: Specify a category ID to check if the cart item is assigned to the category
	 * - **name**: Specify a category name to check if the cart item is assigned to the category
	 * @param ShoppCartItem $O       The working object
	 * @return bool True if the cart item is assigned to the category, false otherwise
	 **/
	public static function in_category ( $result, $options, $O ) {
		if ( empty($O->categories) ) return false;
		if ( isset($options['id']) ) $field = "id";
		if ( isset($options['name']) ) $field = "name";
		foreach ( $O->categories as $id => $name ) {
			switch ( strtolower($field) ) {
				case 'id': if ($options['id'] == $id) return true;
				case 'name': if ($options['name'] == $name) return true;
			}
		}
		return false;
	}

	/**
	 * Iterates over the custom input data set on the cart item
	 *
	 * @api `shopp('cartitem.inputs')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return bool True if the next input exists, false otherwise
	 **/
	public static function inputs ( $result, $options, $O ) {
		if ( ! isset($O->_data_loop) ) {
			reset($O->data);
			$O->_data_loop = true;
		} else next($O->data);

		if ( false !== current($O->data) ) return true;

		unset($O->_data_loop);
		reset($O->data);
		return false;
	}

	/**
	 * Displays the current input data (or name)
	 *
	 * Used when looping through the cart item custom input data.
	 * To show the name instead of the data use `shopp('cartitem.input', 'name')`
	 *
	 * @api `shopp('cartitem.input')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **name**: Show the name of the input instead of the data
	 * @param ShoppCartItem $O       The working object
	 * @return string The input name or data
	 **/
	public static function input ( $result, $options, $O ) {
		$data = current($O->data);
		$name = key($O->data);
		if ( isset($options['name']) ) return apply_filters('shopp_cartitem_input_name', $name);
		return apply_filters('shopp_cartitem_input_data', $data, $name);
	}

	/**
	 * Displays all of the custom data inputs for the item in an unordered list
	 *
	 * @api `shopp('cartitem.inputs-list')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **before**: ` ` Markup to add before the list
	 * - **after**: ` ` Markup to add after the list
	 * - **class**: The class attribute specifies one or more class-names for the list
	 * - **exclude**: Used to specify the inputs to exclude from the list. Multiple inputs can be excluded by separating them with a comma: `Custom Input,Custom Input 2...`
	 * - **separator**: `<br />` The separator to use between the input name and the input data
	 * @param ShoppCartItem $O       The working object
	 * @return string The markup of the input list
	 **/
	public static function inputs_list ( $result, $options, $O ) {
		if ( empty($O->data) ) return false;
		$defaults = array(
			'class' => '',
			'exclude' => array(),
			'before' => '',
			'after' => '',
			'separator' => '<br />'
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! empty($exclude) ) $exclude = explode(',', $exclude);

		$classes = '';
		if ( ! empty($class) ) $classes = ' class="' . $class . '"';

		$result .= $before . '<ul' . $classes . '>';
		foreach ( $O->data as $name => $data ) {
			if (in_array($name,$exclude)) continue;
			if (is_array($data)) $data = join($separator, $data);
			$result .= '<li><strong>' . apply_filters('shopp_cartitem_input_name', $name) . '</strong>: ' . apply_filters('shopp_cartitem_input_data', $data, $name) . '</li>';
		}
		$result .= '</ul>'.$after;
		return $result;
	}

	/**
	 * Provides the markup to show the cover image for the cart item's product
	 *
	 * The **cover image** of a product is the image that is set as the first image
	 * when using a customer image order, or which ever image is automatically sorted
	 * to be first when using other image order settings in the Presentation settings screen.
	 *
	 * @api `shopp('cartitem.coverimage')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The image markup
	 **/
	public static function coverimage ( $result, $options, $O ) {
		if ( false === $O->image ) return false;
		$O->images = array($O->image);
		$options['index'] = 0;
		return ShoppStorefrontThemeAPI::image( $result, $options, $O );
	}

	/**
	 * Detects if the cart item is on sale
	 *
	 * An on sale item either has the sale price enabled or a discount is applied
	 * to it.
	 *
	 * @api `shopp('cartitem.onsale')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return bool True if the cart item is on sale, false otherwise
	 *
	 **/
	public static function onsale ( $result, $options, $O ) {
		return Shopp::str_true( $O->sale );
	}

	/**
	 * Provides the regular, non-discounted price of the cart item
	 *
	 * @api `shopp('cartitem.price')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The price of the current cart item (or selected variant)
	 **/
	public static function price ( $result, $options, $O ) {
		return $O->option->price;
	}

	/**
	 * Provides the regular, non-discounted line item total price (price * quantity)
	 *
	 * @api `shopp('cartitem.prices')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The total cart line item price
	 */
	public static function prices ( $result, $options, $O ) {
		return ( $O->option->price * $O->quantity );
	}

	/**
	 * Returns the sale price of the cart item
	 *
	 * @api `shopp('cartitem.saleprice')`
	 * @since 1.0
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The sale price of the cart item (or selected variant)
	 **/
	public static function saleprice ( $result, $options, $O ) {
		return $O->option->promoprice;
	}

	/**
	 * Provides the total line item sale price
	 *
	 * @api `shopp('cartitem.saleprices')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCartItem $O       The working object
	 * @return string The line item total price with discounts
	 **/
	public static function saleprices ( $result, $options, $O ) {
		return ( $O->option->promoprice * $O->quantity );
	}

	/**
	 * Helper to determine when inclusive taxes apply
	 *
	 * @internal
	 *
	 * @param ShoppCartItem $O The cart item to evaluate
	 * @return bool True if inclusive taxes apply, false otherwise
	 **/
	private static function _inclusive_taxes ( ShoppCartItem $O ) {
		return shopp_setting_enabled('tax_inclusive') && $O->includetax;
	}

	/**
	 * Helper to apply or exclude taxes from a single amount based on inclusive tax settings and the tax option
	 *
	 * @internal
	 * @since 1.3
	 *
	 * @param float $amount The amount to add taxes to, or exclude taxes from
	 * @param ShoppProduct $O The product to get properties from
	 * @param boolean $istaxed Whether the amount can be taxed
	 * @param boolean $taxoption The Theme API tax option given the the tag
	 * @param array $taxrates A list of taxrates that apply to the product and amount
	 * @return float The amount with tax added or tax excluded
	 **/
	private static function _taxes ( $amount, ShoppCartItem $O, $taxoption = null, $quantity = 1) {
		// if ( empty($taxrates) ) $taxrates = Shopp::taxrates($O);

		if ( ! $O->istaxed ) return $amount;
		if ( 0 == $O->unittax ) return $amount;

		$inclusivetax = self::_inclusive_taxes($O);
		if ( isset($taxoption) && ( $inclusivetax ^ $taxoption ) ) {

			if ( $taxoption ) $amount += ( $O->unittax * $quantity );
			else $amount = $amount -= ( $O->unittax * $quantity );
		}

		return (float) $amount;
	}

	/**
	 * Helper function that maps the current cart item's addons to the cart item's configured product menu options
	 *
	 * @internal
	 * @since 1.3
	 *
	 * @param int $id The product ID to retrieve addon menus from
	 * @return array A combined list of the menu labels list and addons menu map
	 **/
	private static function _addon_menus () {
		return ShoppProductThemeAPI::_addon_menus(shopp('cartitem.get-product'));
	}

}
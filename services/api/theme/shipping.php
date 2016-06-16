<?php
/**
 * shipping.php
 *
 * ShoppShippingThemeAPI provides shopp('shipping') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2014
 * @package Shopp\API\Theme\Shipping
 * @version 1.3
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides shopp('shipping') theme API functionality
 *
 * Used primarily in the summary.php template
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 * @version 1.3
 **/
class ShoppShippingThemeAPI implements ShoppAPI {

	/**
	 * @var array The registry of available `shopp('shipping')` properties
	 * @internal
	 **/
	static $register = array(
		'url' => 'url',
		'hasestimates' => 'has_options',
		'hasoptions' => 'has_options',
		'options' => 'options',
		'methods' => 'options',
		'optionmenu' => 'option_menu',
		'methodmenu' => 'option_menu',
		'optionname' => 'option_name',
		'methodname' => 'option_name',
		'methodslug' => 'option_slug',
		'optionslug' => 'option_slug',
		'optionselected' => 'option_selected',
		'methodselected' => 'option_selected',
		'optioncost' => 'option_cost',
		'methodcost' => 'option_cost',
		'optionselector' => 'option_selector',
		'methodselector' => 'option_selector',
		'optiondelivery' => 'option_delivery',
		'methoddelivery' => 'option_delivery',
		'updatebutton' => 'update_button'
	);

	/**
	 * Provides the authoritative Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		return 'shipping';
	}

	/**
	 * Returns the proper global context object used in a shopp('collection') call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param ShoppShiprates $Object The ShoppOrder object to set as the working context
	 * @param string           $context The context being worked on by the Theme API
	 * @return ShoppShiprates The active object context
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'ShoppOrder') && isset($Object->Shiprates) && 'shipping' == strtolower($object) )
			return $Object->Shiprates;
		else if ( strtolower($object) != 'shipping' ) return $Object; // not mine, do nothing

		return ShoppOrder()->Shiprates;
	}

	/**
	 * Checks if the current in progress order has shipping method estimates
	 *
	 * @api `shopp('shipping.has-options')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return bool True if shipping method estimates exist, false otherwise
	 **/
	public static function has_options ( $result, $options, $O ) {
		$Shiprates = ShoppOrder()->Shiprates;
		return apply_filters('shopp_shipping_hasestimates', $Shiprates->exist(), $Shiprates );
	}

	/**
	 * Provides markup for a selector input for the current shipping option in the options loop
	 *
	 * Generates a radio button input.
	 *
	 * @api `shopp('shipping.option-selector')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return string The option selector markup
	 **/
	public static function option_selector ( $result, $options, $O ) {

		$checked = '';
		$selected = $O->selected();
		$option = $O->current();

		if ( $selected->slug == $option->slug )
			$checked = ' checked="checked"';

		$result = '<input type="radio" name="shipmethod" value="' . esc_attr($option->slug) . '" class="shopp shipmethod" ' . $checked . ' />';
		return $result;
	}

	/**
	 * Checks if the current shipping option in the options loop is the currently selected one
	 *
	 * @api `shopp('shipping.option-selected')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return bool True if it is selected, false otherwise
	 **/
	public static function option_selected ( $result, $options, $O ) {
		$option = $O->current();
		$selected = $O->selected();
		return ( $selected->slug == $option->slug );
	}

	/**
	 * Provides the slug for the current shipping option in the options loop
	 *
	 * @api `shopp('shipping.option-slug')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return string The option slug
	 **/
	public static function option_slug ( $result, $options, $O ) {
		$option = $O->current();
		return $option->slug;
	}

	/**
	 * Provides the cost amount of the current shipping option in the options loop
	 *
	 * @api `shopp('shipping.option-cost')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return string The shipping option amount
	 **/
	public static function option_cost ( $result, $options, $O ) {
		$option = $O->current();
		return money($option->amount);
	}

	/**
	 * Provides the estimated delivery time frame for the current shipping option in the options loop
	 *
	 * @api `shopp('shipping.option-delivery')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * - **dateformat**: Sets the PHP date formatting to use. Defaults to the WordPress date and time formats
	 * - **dateseparator**: `&mdash;` Sets the separator character between the two dates in the delivery estimate
	 * @param ShoppShiprates $O       The working object
	 * @return string The option delivery estimate
	 **/
	public static function option_delivery ( $result, $options, $O ) {
		$option = $O->current();
		if ( ! $option->delivery ) return '';
		return self::_delivery_format($option->delivery, $options);
	}

	/**
	 * Provides markup for a menu of the shipping options
	 *
	 * @api `shopp('shipping.option-menu')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * - **difference**: `on` (on,off) Provide the cost difference relative to the currently selected shipping method option
	 * - **times**: `off` (on,off) Show the estimated delivery time frames in the menu
	 * - **class**: The class attribute specifies one or more class-names for menu
	 * - **dateformat**: Sets the PHP date formatting to use. Defaults to the WordPress date and time formats
	 * - **dateseparator**: `&mdash;` Sets the separator character between the two dates in the delivery estimate
	 * @param ShoppShiprates $O       The working object
	 * @return string The menu markup
	 **/
	public static function option_menu ( $result, $options, $O ) {
		$Order = ShoppOrder();
		$Shiprates = $Order->Shiprates;

		$defaults = array(
			'difference' => true,
			'times' => false,
			'class' => false,
			'dateformat' => get_option('date_format'),
			'dateseparator' => '&mdash;',
		);

		$options = array_merge($defaults, $options);
		extract($options);

		$classes = 'shopp shipmethod';
		if ( ! empty($class) ) $classes = $class . ' ' . $classes;

		$_ = array();
		$selected_option = $Shiprates->selected();

		$_[] = '<select name="shipmethod" class="' . $classes . '">';
		foreach ( $O as $method ) {
			$cost = money($method->amount);
			$delivery = false;
			if ( Shopp::str_true($times) && ! empty($method->delivery) ) {
				$delivery = self::_delivery_format($method->delivery, $options) . ' ';
			}
			if ( $selected_option && Shopp::str_true($difference) ) {
				$diff = $method->amount - $selected_option->amount;
				$pre = $diff < 0 ? '-' : '+';
				$cost = $pre . money(abs($diff));
			}

			$selected = $selected_option && $selected_option->slug == $method->slug ? ' selected="selected"' : false;

			$_[] = '<option value="' . esc_attr($method->slug) . '"' . $selected . '>' . $method->name . ' ( ' . $delivery . $cost . ' )</option>';
		}
		$_[] = '</select>';
		return join('', $_);
	}

	/**
	 * Provides the name of the current option in the options list
	 *
	 * @api `shopp('shipping.option-name')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return string The name of the option
	 **/
	public static function option_name ( $result, $options, $O ) {
		$option = $O->current();
		return $option->name;
	}

	/**
	 * Iterates over the available shipping option methods
	 *
	 * @api `shopp('shipping.options')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return bool True if the next option exists, false otherwise
	 **/
	public static function options ( $result, $options, $O ) {
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
	 * Provides the context appropriate URL for selecting shipping options
	 *
	 * @api `shopp('context.property')`
	 * @since 1.0
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return void
	 **/
	public static function url ( $result, $options, $O ) {
		return is_shopp_page('checkout') ? Shopp::url(false, 'confirm-order') : Shopp::url(false, 'cart');
	}

	/**
	 * Displays an update button for shipping method form if JavaScript is disabled
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/

	/**
	 * Provides markup for a submit button to update shipping estimates
	 *
	 * By default this button is hidden unless the JS environment is unavailable (disabled or broken).
	 *
	 * @api `shopp('shipping.update-button')`
	 * @since 1.2
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * - **label**: `Update Shipping` The label for the submit button
	 * - **class**: `update-button hide-if-js`
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * @param ShoppShiprates $O       The working object
	 * @return string The button markup
	 **/
	public static function update_button ( $result, $options, $O ) {
		$submit_attrs = array('title','label','disabled','tabindex','accesskey','class');
		$stdclasses = 'update-button hide-if-js';
		$defaults = array(
			'label' => Shopp::__('Update Shipping'),
			'class' => ''
		);
		$options = array_merge($defaults, $options);
		$options['class'] .= " $stdclasses";
		return '<input type="submit" name="update-shipping"' . inputattrs($options, $submit_attrs) . ' />';
	}

	/**
	 * Helper to provide formated timeframes
	 *
	 * @internal
	 * @since 1.0
	 *
	 * @param string         $result  The output
	 * @param array          $options The options
	 * @param ShoppShiprates $O       The working object
	 * @return string The formatted estimate
	 **/
	private static function _delivery_format( $estimate, $options = array() ) {
		$periods = array('h' => 3600, 'd' => 86400, 'w' => 604800, 'm' => 2592000);
		$defaults = array(
			'dateformat' => get_option('date_format'),
			'dateseparator' => '&mdash;',
		);
		$options = array_merge($defaults, $options);
		extract( $options, EXTR_SKIP );
		if ( ! $dateformat ) $dateformat = 'F j, Y';

		$estimates = explode("-",$estimate);
		if ( empty($estimates) ) return "";

		if ( count($estimates) > 1 && $estimates[0] == $estimates[1] )
			$estimates = array($estimates[0]);

		$result = "";
		for ( $i = 0; $i < count($estimates); $i++ ) {
			list ( $interval, $p ) = sscanf($estimates[ $i ], '%d%s');
			if ( empty($interval) ) $interval = 1;
			if ( empty($p) ) $p = 'd';
			if ( ! empty($result) ) $result .= $dateseparator;
			$result .= _d( $dateformat, current_time('timestamp') + $interval * $periods[ $p ] );
		}
		return $result;
	}


}
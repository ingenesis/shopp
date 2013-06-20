<?php
/**
* ShoppShippingThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppShippingThemeAPI
*
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
 *
 **/
class ShoppShippingThemeAPI implements ShoppAPI {
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

	static function _apicontext () { return 'shipping'; }

	/**
	 * _setobject - returns the global context object used in the shopp('cart') call
	 *
	 * @author John Dillick, Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 **/
	static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'Order') && isset($Object->Shiprates) && 'shipping' == strtolower($object) )
			return $Object->Shiprates;
		else if ( strtolower($object) != 'shipping' ) return $Object; // not mine, do nothing

		return ShoppOrder()->Shiprates;
	}

	static function has_options ($result, $options, $O) {
		$Shiprates = ShoppOrder()->Shiprates;
		return apply_filters('shopp_shipping_hasestimates', $Shiprates->exist(), $Shiprates );
	}

	static function option_selector ($result, $options, $O) {

		$checked = '';
		$selected = $O->selected();
		$option = $O->current();

		if ( $selected->slug == $option->slug )
			$checked = ' checked="checked"';

		$result = '<input type="radio" name="shipmethod" value="' . esc_attr($option->slug) . '" class="shopp shipmethod" ' . $checked . ' />';
		return $result;
	}

	static function option_selected ($result, $options, $O) {
		$option = $O->current();
		$selected = $O->selected();
		return ( $selected->slug == $option->slug );
	}

	static function option_slug ($result, $options, $O) {
		$option = $O->current();
		return $option->slug;
	}

	static function option_cost ($result, $options, $O) {
		$option = $O->current();
		return money($option->amount);
	}

	static function option_delivery ($result, $options, $O) {
		$option = $O->current();
		if ( ! $option->delivery ) return "";
		return self::_delivery_format($option->delivery, $options);
	}

	static function _delivery_format( $estimate, $options = array() ) {
		$periods = array("h"=>3600,"d"=>86400,"w"=>604800,"m"=>2592000);
		$defaults = array(
			'dateformat' => get_option('date_format'),
			'dateseparator' => '&mdash;',
		);
		$options = array_merge($defaults, $options);
		extract( $options );
		if ( ! $dateformat ) $dateformat = 'F j, Y';

		$estimates = explode("-",$estimate);
		if ( empty($estimates) ) return "";

		if (count($estimates) > 1 && $estimates[0] == $estimates[1])
			$estimates = array($estimates[0]);

		$result = "";
		for ( $i = 0; $i < count($estimates); $i++ ) {
			list ( $interval, $p ) = sscanf($estimates[$i], '%d%s');
			if ( empty($interval) ) $interval = 1;
			if ( empty($p) ) $p = 'd';
			if ( ! empty($result) ) $result .= $dateseparator;
			$result .= _d( $dateformat, current_time('timestamp') + $interval * $periods[$p] );
		}
		return $result;
	}

	static function option_menu ($result, $options, $O) {
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
		if ( ! empty($class) ) $classes = $class.' '.$classes;

		$_ = array();
		$selected_option = $Shiprates->selected();

		$_[] = '<select name="shipmethod" class="'.$classes.'">';
		foreach ( $O as $method ) {
			$cost = money($method->amount);
			$delivery = false;
			if ( Shopp::str_true($times) && ! empty($method->delivery) ) {
				$delivery = self::_delivery_format($method->delivery, $options).' ';
			}
			if ( $selected_option && Shopp::str_true($difference) ) {
				$diff = $method->amount - $selected_option->amount;
				$pre = $diff < 0 ? '-' : '+';
				$cost = $pre.money(abs($diff));
			}

			$selected = $selected_option && $selected_option->slug == $method->slug ?' selected="selected"' : false;

			$_[] = '<option value="' . esc_attr($method->slug) . '"' . $selected . '>' . $method->name . ' ( ' . $delivery.$cost . ' )</option>';
		}
		$_[] = '</select>';
		return join("",$_);
	}

	static function option_name ($result, $options, $O) {
		$option = $O->current();
		return $option->name;
	}

	static function options ($result, $options, $O) {
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

	static function url ($result, $options, $O) {
		return is_shopp_page('checkout') ? shoppurl(false, 'confirm-order') : shoppurl(false, 'cart');
	}

	/**
	 * Displays an update button for shipping method form if JavaScript is disabled
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	static function update_button ($result, $options, $O) {
		$submit_attrs = array('title','value','disabled','tabindex','accesskey','class');
		$stdclasses = 'update-button hide-if-js';
		$defaults = array(
			'value' => __('Update Shipping','Shopp'),
			'class' => ''
		);
		$options = array_merge($defaults, $options);
		$options['class'] .= " $stdclasses";
		return '<input type="submit" name="update-shipping"' . inputattrs($options, $submit_attrs) . ' />';
	}

}
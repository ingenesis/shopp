<?php
/**
* ShoppCheckoutThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppCheckoutThemeAPI
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

// Default text filters for checkout Theme API tags
add_filter('shopp_checkout_clickwrap_terms', 'wptexturize');
add_filter('shopp_checkout_clickwrap_terms', 'convert_chars');
add_filter('shopp_checkout_clickwrap_terms', 'wpautop');
add_filter('shopp_checkout_clickwrap_terms', 'do_shortcode', 11); // AFTER wpautop()

add_filter('shopp_checkout_order_data', 'esc_html');
add_filter('shopp_checkout_order_data', 'wptexturize');
add_filter('shopp_checkout_order_data', 'convert_chars');
add_filter('shopp_checkout_order_data', 'wpautop');

/**
 * Provides shopp('checkout') theme API functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 **/
class ShoppCheckoutThemeAPI implements ShoppAPI {
	static $register = array(
		// Aliased methods
		'accountlogin' => 'ShoppCustomerThemeAPI::account_login',
		'billingaddress' => 'ShoppCustomerThemeAPI::billing_address',
		'billingcity' => 'ShoppCustomerThemeAPI::billing_city',
		'billingcountry' => 'ShoppCustomerThemeAPI::billing_country',
		'billingpostcode' => 'ShoppCustomerThemeAPI::billing_postcode',
		'billingprovince' => 'ShoppCustomerThemeAPI::billing_state',
		'billingstate' => 'ShoppCustomerThemeAPI::billing_state',
		'billingxaddress' => 'ShoppCustomerThemeAPI::billing_xaddress',
		'company' => 'ShoppCustomerThemeAPI::company',
		'email' => 'ShoppCustomerThemeAPI::email',
		'emaillogin' => 'ShoppCustomerThemeAPI::account_login',
		'firstname' => 'ShoppCustomerThemeAPI::first_name',
		'lastname' => 'ShoppCustomerThemeAPI::last_name',
		'loggedin' => 'ShoppCustomerThemeAPI::logged_in',
		'loginname' => 'ShoppCustomerThemeAPI::login_name',
		'loginnamelogin' => 'ShoppCustomerThemeAPI::account_login',
		'marketing' => 'ShoppCustomerThemeAPI::marketing',
		'organization' => 'ShoppCustomerThemeAPI::company',
		'password' => 'ShoppCustomerThemeAPI::password',
		'passwordlogin' => 'ShoppCustomerThemeAPI::password_login',
		'phone' => 'ShoppCustomerThemeAPI::phone',
		'sameshippingaddress' => 'ShoppCustomerThemeAPI::same_shipping_address',
		'shipping' => 'ShoppCustomerThemeAPI::shipping',
		'shippingaddress' => 'ShoppCustomerThemeAPI::shipping_address',
		'shippingcity' => 'ShoppCustomerThemeAPI::shipping_city',
		'shippingcountry' => 'ShoppCustomerThemeAPI::shipping_country',
		'shippingpostcode' => 'ShoppCustomerThemeAPI::shipping_postcode',
		'shippingprovince' => 'ShoppCustomerThemeAPI::shipping_state',
		'shippingstate' => 'ShoppCustomerThemeAPI::shipping_state',
		'shippingxaddress' => 'ShoppCustomerThemeAPI::shipping_xaddress',

		// Organic methods
		'billingcard' => 'billing_card',
		'billingcardexpiresmm' => 'billing_card_expires_mm',
		'billingcardexpiresyy' => 'billing_card_expires_yy',
		'billingcardholder' => 'billing_card_holder',
		'billingcardtype' => 'billing_card_type',
		'billingcvv' => 'billing_cvv',
		'billinglocale' => 'billing_locale',
		'billinglocalities' => 'billing_localities',
		'billingname' => 'billing_name',
		'billingrequired' => 'card_required',
		'cardrequired' => 'card_required',
		'billingxco' => 'billing_xco',
		'billingxcsc' => 'billing_xcsc',
		'billingxcscrequired' => 'billing_xcsc_required',
		'cartsummary' => 'cart_summary',
		'clickwrap' => 'clickwrap',
		'completed' => 'completed',
		'confirmbutton' => 'confirm_button',
		'confirmpassword' => 'confirm_password',
		'customerinfo' => 'customer_info',
		'data' => 'data',
		'errors' => 'error',
		'error' => 'error',
		'function' => 'checkout_function',
		'gatewayinputs' => 'gateway_inputs',
		'guest' => 'guest',
		'hasdata' => 'has_data',
		'localpayment' => 'local_payment',
		'notloggedin' => 'not_logged_in',
		'orderdata' => 'order_data',
		'payoption' => 'payoption',
		'paymentoption' => 'payoption',
		'payoptions' => 'payoptions',
		'paymentoptions' => 'payoptions',
		'receipt' => 'receipt',
		'residentialshippingaddress' => 'residential_shipping_address',
		'samebillingaddress' => 'same_billing_address',
		'shippingname' => 'shipping_name',
		'submit' => 'submit',
		'submitlogin' => 'submit_login',
		'loginbutton' => 'submit_login',
		'url' => 'url',
		'xcobuttons' => 'xco_buttons'
	);

	public static function _apicontext () { return 'checkout'; }

	/**
	 * _setobject - returns the global context object used in the shopp('checkout) call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	public static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'Order') && 'checkout' == strtolower($object) ) return $Object;
		else if ( strtolower($object) != 'checkout' ) return $Object; // not mine, do nothing

		return ShoppOrder();
	}

	public static function billing_name ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Billing->name;
		if (!empty($O->Billing->name))
			$options['value'] = $O->Billing->name;
		return '<input type="text" name="billing[name]" id="billing-name" '.inputattrs($options).' />';
	}

	public static function billing_address ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Billing->address;
		if (!empty($O->Billing->address))
			$options['value'] = $O->Billing->address;
		return '<input type="text" name="billing[address]" id="billing-address" '.inputattrs($options).' />';
	}

	public static function billing_card ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if (!empty($O->Billing->card) && strlen($O->Billing->card) > 4)
			$options['value'] = str_repeat('X',strlen($O->Billing->card)-4).substr($O->Billing->card,-4);
		if ($options['mode'] == "value") return $options['value'];
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		return '<input type="text" name="billing[card]" id="billing-card" '.inputattrs($options).' />';
	}

	public static function billing_card_expires_mm ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return date("m",$O->Billing->cardexpires);
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($O->Billing->cardexpires))
			$options['value'] = date("m",$O->Billing->cardexpires);
		return '<input type="text" name="billing[cardexpires-mm]" id="billing-cardexpires-mm" '.inputattrs($options).' />';
	}

	public static function billing_card_expires_yy ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return date("y",$O->Billing->cardexpires);
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($O->Billing->cardexpires))
			$options['value'] = date("y",$O->Billing->cardexpires);
		return '<input type="text" name="billing[cardexpires-yy]" id="billing-cardexpires-yy" '.inputattrs($options).' />';
	}

	public static function billing_card_holder ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Billing->cardholder;
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($O->Billing->cardholder))
			$options['value'] = $O->Billing->cardholder;
		return '<input type="text" name="billing[cardholder]" id="billing-cardholder" '.inputattrs($options).' />';
	}

	public static function billing_card_type ($result, $options, $O) {
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if ( ! isset($options['mode']) ) $options['mode'] = "input";
		if ( 'value' == $options['mode']) return $O->Billing->cardtype;
		$options['class'] = isset($options['class']) ? $options['class'] . ' paycard' : 'paycard';
		if ( ! isset($options['selected']) ) $options['selected'] = false;
		if ( ! empty($O->Billing->cardtype) )
			$options['selected'] = $O->Billing->cardtype;

		$cards = array();
		$accepted = $O->Payments->accepted();
		foreach ( $accepted as $paycard ) {
			// Convert full card type names to card type symbols
			if ( $options['selected'] == $paycard->name ) $options['selected'] = $paycard->symbol;
			$cards[$paycard->symbol] = $paycard->name;
		}

		$label = ( ! empty($options['label']) ) ? $options['label'] : '';
		$output = '<select name="billing[cardtype]" id="billing-cardtype" ' . inputattrs($options, $select_attrs) . '>';
		$output .= '<option value="">' . $label . '</option>';
	 	$output .= menuoptions($cards,$options['selected'], true);
		$output .= '</select>';

		$js = array();
		$js[] = "var paycards = {};";
		foreach ($accepted as $slug => $paycard) {
			$js[] = "paycards['" . $slug . "'] = " . json_encode($paycard) . ";";
		}
		add_storefrontjs(join("", $js), true);

		return $output;
	}

	public static function billing_cvv ($result, $options, $O) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($_POST['billing']['cvv']))
			$options['value'] = $_POST['billing']['cvv'];
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		return '<input type="text" name="billing[cvv]" id="billing-cvv" '.inputattrs($options).' />';
	}

	public static function billing_locale ($result, $options, $O) {
		$Shopp = Shopp::object();

		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');
		$output = false;

		if ( "value" == $options['mode'] ) { return $O->Billing->locale; }

		if ( ! isset($options['selected']) ) {
			$options['selected'] = $O->Billing->locale ? $O->Billing->locale : false;
		}

		$rates = shopp_setting('taxrates');
		foreach ( $rates as $rate ) { // @todo - what if more than one set of local rates applies to current country/zone? ie. conditions
			if ( isset( $rate['locals'] ) ) {
				$locales[$rate['country'].$rate['zone']] = array_keys($rate['locals']);
			}
		}

		// if there are no local tax jurisdictions in settings
		if ( ! empty($locales) ) {
			// Add all the locales to the javascript environment
			add_storefrontjs('var locales = '.json_encode($locales).';',true);

			$Taxes = new CartTax();

			// Check for local rates applying to current country/zone
			$setting = true; // return the whole rate setting, not just the percentage
			$Item = false; // Item to pass to tax rate lookup
			$rate = $Taxes->rate($Item,$setting);

			// If the current country.state combination doesn't match any of the local jurisdictions,
			// check for local jurisdiction rate setting that has a product-specific condition.
			if( ! isset($rate['locals']) ) {
				foreach ( $O->Cart->contents as $Item ) {
					if ( ( $rate = $Taxes->rate($Item,$setting) ) && isset($rate['locals']) ) {
						break;
					}
				}
			}

			// names of local tax jurisdictions that apply to current country.zone
			$localities = array();
			if ( isset($rate['locals']) ) {
				$localities = array_keys($rate['locals']);
			}

			// Make this a required field
			$options['required'] = true;

			// disable this field automatically if no local jurisdictions apply to current country.zone
			if ( empty($localities) ) $options['disabled'] = 'disabled';

			// Start stub select menu for billing local tax jurisdiction (needed for javascript to populate)
			$output = '<select name="billing[locale]" id="billing-locale" '.inputattrs($options,$select_attrs).'>';

		 	if ( ! empty($localities) ) { $output .= "<option></option>".menuoptions($localities, $options['selected']); }

			// End stub select menu for billing local tax jurisdiction
			$output .= '</select>';
		}

		return $output;

	} // end function billing_locale

	public static function billing_localities ($result, $options, $O) {
		$rates = shopp_setting("taxrates");
		foreach ((array)$rates as $rate) if (isset($rate['locals']) && is_array($rate['locals'])) return true;
		return false;
	}

	/**
	 * @since 1.0
	 * @deprecated 1.1
	 **/
	public static function billing_xco ($result, $options, $O) { return; }

	public static function billing_xcsc ($result, $options, $O) {
		if (empty($options['input'])) return;
		$input = $options['input'];

		$cards = array();
		$valid = array();
		// Collect valid card inputs for all gateways
		foreach ($O->payoptions as $payoption) {
			foreach ($payoption->cards as $card) {
				$PayCard = Lookup::paycard($card);
				if (empty($PayCard->inputs)) continue;
				$cards[] = $PayCard->symbol;
				foreach ($PayCard->inputs as $field => $size)
					$valid[$field] = $size;
			}
		}

		if (!array_key_exists($input,$valid)) return;

		if (!empty($_POST['billing']['xcsc'][$input]))
			$options['value'] = $_POST['billing']['xcsc'][$input];
		$options['class'] = isset($options['class']) ? $options['class'].' paycard xcsc':'paycard xcsc';

		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		$string = '<input type="text" name="billing[xcsc]['.$input.']" id="billing-xcsc-'.$input.'" '.inputattrs($options).' />';
		return $string;
	}

	public static function billing_xcsc_required ($result, $options, $O) {
		$Shopp = Shopp::object();
		$Gateways = $Shopp->Gateways->active;
		foreach ($Gateways as $Gateway) {
			foreach ((array)$Gateway->settings['cards'] as $card) {
				$PayCard = Lookup::paycard($card);
				if (!empty($PayCard->inputs)) return true;
			}
		}
		return false;
	}

	public static function card_required ($result, $options, $O) {
		$Shopp = Shopp::object();
		if ($O->Cart->Totals->total() == 0) return false;
		foreach ($Shopp->Gateways->active as $gateway)
			if (!empty($gateway->cards)) return true;
		return false;
	}

	public static function cart_summary ($result, $options, $O) {
		ob_start();
		locate_shopp_template(array('summary.php'),true);
		$content = ob_get_contents();
		ob_end_clean();

		// If inside the checkout form, strip the extra <form> tag so we don't break standards
		// This is ugly, but necessary given the different markup contexts the cart summary is used in
		$Storefront =& ShoppStorefront();
		if ($Storefront !== false && $Storefront->checkout)
			$content = preg_replace('/<\/?form.*?>/','',$content);

		return $content;
	}

	public static function completed ($result, $options, $O) {
		if ( $O->purchase === false ) return false;
		if ( ! ShoppPurchase() || empty(ShoppPurchase()->id) ) {
			ShoppPurchase(new Purchase($O->purchase));
			ShoppPurchase()->load_purchased();
		}
		return (!empty(ShoppPurchase()->id));
	}

	public static function confirm_button ($result, $options, $O) {
		$submit_attrs = array('title','class','value','disabled','tabindex','accesskey');

		if (empty($options['errorlabel'])) $options['errorlabel'] = __('Return to Checkout','Shopp');
		if (empty($options['value'])) $options['value'] = __('Confirm Order','Shopp');

		$button = '<input type="submit" name="confirmed" id="confirm-button" '.inputattrs($options,$submit_attrs).' />';
		$return = '<a href="'.Shopp::url(false,'checkout',$O->security()).'"'.inputattrs($options,array('class')).'>'.
						$options['errorlabel'].'</a>';

		if ( ! $O->isvalid() ) $markup = $return;
		else $markup = $button;
		return apply_filters('shopp_checkout_confirm_button',$markup,$options,$submit_attrs);
	}

	public static function confirm_password ($result, $options, $O) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($O->Customer->_confirm_password))
			$options['value'] = $O->Customer->_confirm_password;
		return '<input type="password" name="confirm-password" id="confirm-password" '.inputattrs($options).' />';
	}

	public static function customer_info ($result, $options, $O) {
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');
		$defaults = array(
			'name' => false, // REQUIRED
			'info' => false,
			'mode' => false,
			'title' => '',
			'type' => 'hidden',
			'value' => '',
			'options' => ''
		);
		if ('textarea' == $defaults['type']) {
			$defaults['cols'] = '30';
			$defaults['rows'] = '3';
		}
		$op = array_merge($defaults,$options);
		extract($op);

		// Allowed input types
		$allowed_types = array("text","hidden","password","checkbox","radio","textarea","menu");

		// Input types that can override option-specified value with the loaded data value
		$value_override = array("text","hidden","password","textarea","menu");

		/// Allowable attributes for textarea inputs
		$textarea_attrs = array('accesskey','title','tabindex','class','disabled','required');

		if (!$name) { // Iterator for order data
			if (!isset($O->_customer_info_loop)) {
				reset($O->Customer->info->named);
				$O->_customer_info_loop = true;
			} else next($O->Customer->info->named);

			if (current($O->Customer->info->named) !== false) return true;
			else {
				unset($O->_customer_info_loop);
				return false;
			}
		}

		if (is_array($O->Customer->info) && isset($O->Customer->info[$name])) $info = $O->Customer->info[$name];
		if ($name && $mode == "value") return $info;
		if (empty($value) && !empty($info)) $op['value'] = $info;

		if (!in_array($type,$allowed_types)) $type = 'hidden';
		if (empty($title)) $op['title'] = $name;
		$id = 'customer-info-'.sanitize_title_with_dashes($name);

		if (in_array($type,$value_override) && !empty($info))
			$value = $info;
		switch (strtolower($type)) {
			case "textarea":
				return '<textarea name="info['.$name.']" cols="'.$cols.'" rows="'.$rows.'" id="'.$id.'" '.inputattrs($op,$textarea_attrs).'>'.$value.'</textarea>';
				break;
			case "menu":
				if (is_string($options)) $options = explode(',',$options);
				return '<select name="info['.$name.']" id="'.$id.'" '.inputattrs($op,$select_attrs).'>'.menuoptions($options,$value).'</select>';
				break;
			default:
				return '<input type="'.$type.'" name="info['.$name.']" id="'.$id.'" '.inputattrs($op).' />';
				break;
		}
	}

	public static function data ($result, $options, $O) {
		if (!is_array($O->data)) return false;
		$data = current($O->data);
		$name = key($O->data);
		if (isset($options['name'])) return $name;
		return $data;
	}

	public static function error ($result, $options, $O) {
		return ShoppStorefrontThemeAPI::errors($result,$options,$O);
	}

	public static function checkout_function ($result, $options, $O) {
		$Payments = $O->Payments;
		$defaults = array(
			'updating' => '<img src="'.SHOPP_ADMIN_URI.'/icons/updating.gif" alt="'.__('Updating','Shopp').'" width="16" height="16" />'
		);
		$options = array_merge($defaults,$options);
		extract($options);
		$regions = Lookup::country_zones();
		$base = shopp_setting('base_operations');

		$js = "var regions=" . json_encode($regions) . "," .
				  "c_upd='" . $updating . "'," .
				  "d_pm='" . $Payments->selected()->slug . "'," .
				  "pm_cards={};";

		foreach ($Payments as $slug => $option) {
			if (empty($option->cards)) continue;
			$js .= "pm_cards['" . $slug . "'] = " . json_encode($option->cards) . ";";
		}
		add_storefrontjs($js,true);

		if ( ! empty($options['value']) ) $value = $options['value'];
		else $value = 'process';
		$output = '
<script type="text/javascript">
//<![CDATA[
	document.body.className += \' js-on\';
//]]>
</script>
<div><input id="shopp-checkout-function" type="hidden" name="checkout" value="'.$value.'" /></div>
		';

		if ( 'confirmed' == $value ) $output = apply_filters('shopp_confirm_form', $output);
		else $output = apply_filters('shopp_checkout_form', $output);
		return $output;
	}

	public static function gateway_inputs ($result, $options, $O) { return apply_filters('shopp_checkout_gateway_inputs',false); }

	public static function guest ($result, $options, $O) {
		$allowed = array('class','checked','title');
		$defaults = array(
			'label' => __('Checkout as a guest','Shopp'),
			'checked' => 'off'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if ( Shopp::str_true($O->guest) ||Shopp::str_true($checked) )
			$options['checked'] = 'on';

		$_ = array();
		if (!empty($label))
			$_[] = '<label for="guest-checkout">';
		$_[] = '<input type="hidden" name="guest" value="no" />';
		$_[] = '<input type="checkbox" name="guest" value="yes" id="guest-checkout"'.inputattrs($options,$allowed).' />';
		if (!empty($label))
			$_[] = "&nbsp;$label</label>";

		return join('',$_);
	}

	public static function has_data ($result, $options, $O) {
		reset($O->data);
		return ( is_array($O->data) && count($O->data) > 0 );
	}

	public static function clickwrap ($result, $options, $O) {
		$modes = array('input','value');
		$name = 'clickwrap';
		$value = isset($O->data[$name]) ? $O->data[$name] : false;
		$defaults = array(
			'mode' => 'input',
			'terms' => false,
			'termsclass' => false,
			'class' => 'required',
			'value' => $value
		);
		$options = array_merge($defaults,$options);
		extract($options);
		$frame = false;

		if (!in_array($mode,$modes)) $mode = $modes[0];

		if ('value' == $mode) return $value;

		$attrs = array('accesskey','alt','checked','class','disabled','format',
			'minlength','maxlength','readonly','size','src','tabindex',
			'title');

		if ('agreed' == $value) $options['checked'] = 'checked';

		if (false !== $agreement) {
			$page = get_page_by_path($agreement);
			$frame = '<div class="scrollable clickwrap clickwrap-terms'.( $termsclass ? " $termsclass" : "" ).'">'.apply_filters('shopp_checkout_clickwrap_terms',$page->post_content).'</div>';
		}
		$input = '<input type="hidden" name="data[clickwrap]" value="no" /><input type="checkbox" name="data[clickwrap]" id="clickwrap" value="agreed" '.inputattrs($options,$attrs).' />';
		return $frame.$input;
	}


	public static function not_logged_in ($result, $options, $O) { return (!$O->Customer->logged_in() && shopp_setting('account_system') != "none"); }

	public static function order_data ($result, $options, $O) {
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');
		$defaults = array(
			'name' => false, // REQUIRED
			'data' => false,
			'mode' => false,
			'title' => '',
			'type' => 'hidden',
			'value' => '',
			'options' => ''
		);
		if ('textarea' == $defaults['type']) {
			$defaults['cols'] = '30';
			$defaults['rows'] = '3';
		}
		$op = array_merge($defaults,$options);
		extract($op);

		// Allowed input types
		$allowed_types = array("text","hidden","password","checkbox","radio","textarea","menu");

		// Input types that can override option-specified value with the loaded data value
		$value_override = array("text","hidden","password","textarea","menu");

		/// Allowable attributes for textarea inputs
		$textarea_attrs = array('accesskey','title','tabindex','class','disabled','required');

		if (!$name) { // Iterator for order data
			if (!isset($O->_data_loop)) {
				reset($O->data);
				$O->_data_loop = true;
			} else next($O->data);

			if (current($O->data) !== false) return true;
			else {
				unset($O->_data_loop);
				return false;
			}
		}

		if (isset($O->data[$name])) $data = $O->data[$name];
		if ($name && $mode == "value") return apply_filters('shopp_checkout_order_data', $data);

		if (!in_array($type,$allowed_types)) $type = 'hidden';
		if (empty($title)) $title = $name;
		$id = 'order-data-'.sanitize_title_with_dashes($name);

		if (in_array($type,$value_override) && !empty($data))
			$op['value'] = $value = $data;

		switch (strtolower($type)) {
			case "textarea":
				return '<textarea name="data['.$name.']" cols="'.$cols.'" rows="'.$rows.'" id="'.$id.'" '.inputattrs($op,$textarea_attrs).'>'.$value.'</textarea>';
				break;
			case "menu":
				if (is_string($options)) $options = explode(',',$options);
				return '<select name="data['.$name.']" id="'.$id.'" '.inputattrs($op,$select_attrs).'>'.menuoptions($options,$value).'</select>';
				break;
			default:
				return '<input type="'.$type.'" name="data['.$name.']" id="'.$id.'" '.inputattrs($op).' />';
				break;
		}
	}

	public static function payoption ($result, $options, $O) {
		$payoption = current($O->payoptions);
		$defaults = array(
			'labelpos' => 'after',
			'labeling' => false,
			'type' => 'hidden',
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if (Shopp::str_true($return)) return $payoption;

		$types = array('radio','checkbox','hidden');
		if (!in_array($type,$types)) $type = 'hidden';

		if (empty($options['value'])) $options['value'] = key($O->payoptions);

		$_ = array();
		if (Shopp::str_true($labeling)) {
			$_[] = '<label class="'.esc_attr($options['value']).'">';
			if ($labelpos == "before") $_[] = $payoption->label;
		}
		$_[] = '<input type="'.$type.'" name="paymethod" id="paymethod-'.esc_attr($options['value']).'"'.inputattrs($options).' />';
		if (Shopp::str_true($labeling)) {
			if ($labelpos == "after") $_[] = $payoption->label;
			$_[] = '</label>';
		}

		return join("",$_);
	}

	public static function payoptions ($result, $options, $O) {
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if ($O->Cart->orderisfree()) return false;
		$payment_methods = apply_filters('shopp_payment_methods',count($O->payoptions));
		if ($payment_methods <= 1) return false; // Skip if only one gateway is active
		$defaults = array(
			'default' => false,
			'exclude' => false,
			'type' => 'menu',
			'mode' => false
		);
		$options = array_merge($defaults,$options);
		extract($options);
		unset($options['type']);

		if ('loop' == $mode) {
			if (!isset($O->_pay_loop)) {
				reset($O->payoptions);
				$O->_pay_loop = true;
			} else next($O->payoptions);

			if (current($O->payoptions) !== false) return true;
			else {
				unset($O->_pay_loop);
				return false;
			}
			return true;
		}

		$excludes = array_map('sanitize_title_with_dashes',explode(",",$exclude));
		$payoptions = array_keys($O->payoptions);

		$payoptions = array_diff($payoptions,$excludes);
		$paymethod = current($payoptions);

		if ($default !== false && ! $O->Payments->userset() ) {
			$default = sanitize_title_with_dashes($default);
			if (in_array($default,$payoptions)) $paymethod = $default;
		}

		if ( ! $O->Payments->userset() && $O->paymethod != $paymethod ) {
			$O->paymethod = $paymethod;
			$processor = $O->payoptions[$O->paymethod]->processor;
			if (!empty($processor)) $O->processor($processor);
		}

		$output = '';
		switch ($type) {
			case "list":
				$output .= '<span><ul>';
				foreach ($payoptions as $value) {
					if (in_array($value,$excludes)) continue;
					$payoption = $O->payoptions[$value];
					$options['value'] = $value;
					$options['checked'] = ($O->paymethod == $value);
					if ($options['checked'] === false) unset($options['checked']);
					$output .= '<li><label><input type="radio" name="paymethod" '.inputattrs($options).' /> '.$payoption->label.'</label></li>';
				}
				$output .= '</ul></span>';
				break;
			case "hidden":
				if (!isset($options['value']) && $default) $options['value'] = $O->paymethod;
				$output .= '<input type="hidden" name="paymethod"'.inputattrs($options).' />';
				break;
			default:
				$output .= '<select name="paymethod" '.inputattrs($options,$select_attrs).'>';
				foreach ($payoptions as $value) {
					if (in_array($value,$excludes)) continue;
					$payoption = $O->payoptions[$value];
					$selected = ($O->paymethod == $value)?' selected="selected"':'';
					$output .= '<option value="'.$value.'"'.$selected.'>'.$payoption->label.'</option>';
				}
				$output .= '</select>';
				break;
		}

		return $output;
	}

	public static function receipt ($result, $options, $O) {
		$Shopp = Shopp::object();
		if (!empty($Shopp->Purchase->id)) return $Shopp->Purchase->receipt();
	}

	public static function residential_shipping_address ($result, $options, $O) {
		$allowed = array('class','checked','title');
		$defaults = array(
			'label' => __('Residential shipping address','Shopp'),
			'checked' => 'on'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if ( ( isset($O->Shipping->residential) && ! Shopp::str_true($O->Shipping->residential) ) || ! Shopp::str_true($checked) )
			$options['checked'] = 'off';

		$_ = array();
		$_[] = '<label for="residential-shipping">';
		$_[] = '<input type="hidden" name="shipping[residential]" value="no" />';
		$_[] = '<input type="checkbox" name="shipping[residential]" value="yes" id="residential-shipping"'.inputattrs($options,$allowed).' />';
		$_[] = "&nbsp;$label</label>";

		return join('',$_);
	}

	public static function same_billing_address ($result, $options, $O) {
		$allowed = array('class','checked');
		$defaults = array(
			'label' => __('Same billing address','Shopp'),
			'checked' => 'on',
			'type' => 'billing',
			'class' => ''
		);
		$options = array_merge($defaults,$options);
		$options['type'] = 'billing';
		return self::same_shipping_address($result,$options,$O);
	}

	public static function shipping_name ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Shipping->name;
		if (!empty($O->Shipping->name))
			$options['value'] = $O->Shipping->name;
		return '<input type="text" name="shipping[name]" id="shipping-name" '.inputattrs($options).' />';
	}

	public static function shipping_country ($result, $options, $O) {
		$base = shopp_setting('base_operations');
		$countries = shopp_setting('target_markets');
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Shipping->country;
		if (!empty($O->Shipping->country))
			$options['selected'] = $O->Shipping->country;
		else if (empty($options['selected'])) $options['selected'] = $base['country'];
		$output = '<select name="shipping[country]" id="shipping-country" '.inputattrs($options,$select_attrs).'>';
	 	$output .= menuoptions($countries,$options['selected'],true);
		$output .= '</select>';
		return $output;
	}

	public static function submit ($result, $options, $O) {
		$submit_attrs = array('title','class','value','disabled','tabindex','accesskey');

		if (!isset($options['value'])) $options['value'] = __('Submit Order','Shopp');
		$options['class'] = isset($options['class'])?$options['class'].' checkout-button':'checkout-button';

		$wrapclass = '';
		if (isset($options['wrapclass'])) $wrapclass = ' '.$options['wrapclass'];

		$buttons = array('<input type="submit" name="process" id="checkout-button" '.inputattrs($options,$submit_attrs).' />');

		if (!$O->Cart->orderisfree())
			$buttons = apply_filters('shopp_checkout_submit_button',$buttons,$options,$submit_attrs);

		$_ = array();
		foreach ($buttons as $label => $button)
			$_[] = '<span class="payoption-button payoption-'.sanitize_title_with_dashes($label).($label === 0?$wrapclass:'').'">'.$button.'</span>';

		return join("\n",$_);
	}

	public static function submit_login ($result, $options, $O) {
		$string = '<input type="submit" name="submit-login" id="submit-login" '.inputattrs($options).' />';
		return $string;
	}

	public static function url ($result, $options, $O) {
		$link = Shopp::url(false,'checkout',$O->security());
		$Storefront = ShoppStorefront();

		// Pass any arguments along
		$args = $_GET;
		unset($args['shopp_page'],$args['acct']);
		$link = esc_url(add_query_arg($args,$link));

		if (isset($Storefront->_confirm_page_content)) $link = apply_filters('shopp_confirm_url',$link);
		else $link = apply_filters('shopp_checkout_url',$link);
		return $link;
	}

}

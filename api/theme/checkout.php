<?php
/**
 * checkout.php
 *
 * ShoppCheckoutThemeAPI provides shopp('checkout') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-3013
 * @package shopp
 * @since 1.2
 * @version 1.3
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
		'accountlogin' => array('ShoppCustomerThemeAPI', 'account_login'),
		'billingaddress' => array('ShoppCustomerThemeAPI', 'billing_address'),
		'billingcity' => array('ShoppCustomerThemeAPI', 'billing_city'),
		'billingcountry' => array('ShoppCustomerThemeAPI', 'billing_country'),
		'billingpostcode' => array('ShoppCustomerThemeAPI', 'billing_postcode'),
		'billingprovince' => array('ShoppCustomerThemeAPI', 'billing_state'),
		'billingstate' => array('ShoppCustomerThemeAPI', 'billing_state'),
		'billingxaddress' => array('ShoppCustomerThemeAPI', 'billing_xaddress'),
		'company' => array('ShoppCustomerThemeAPI', 'company'),
		'email' => array('ShoppCustomerThemeAPI', 'email'),
		'emaillogin' => array('ShoppCustomerThemeAPI', 'account_login'),
		'firstname' => array('ShoppCustomerThemeAPI', 'first_name'),
		'lastname' => array('ShoppCustomerThemeAPI', 'last_name'),
		'loggedin' => array('ShoppCustomerThemeAPI', 'logged_in'),
		'loginname' => array('ShoppCustomerThemeAPI', 'login_name'),
		'loginnamelogin' => array('ShoppCustomerThemeAPI', 'account_login'),
		'marketing' => array('ShoppCustomerThemeAPI', 'marketing'),
		'organization' => array('ShoppCustomerThemeAPI', 'company'),
		'password' => array('ShoppCustomerThemeAPI', 'password'),
		'passwordlogin' => array('ShoppCustomerThemeAPI', 'password_login'),
		'phone' => array('ShoppCustomerThemeAPI', 'phone'),
		'sameshippingaddress' => array('ShoppCustomerThemeAPI', 'same_shipping_address'),
		'shipping' => array('ShoppCustomerThemeAPI', 'shipping'),
		'shippingaddress' => array('ShoppCustomerThemeAPI', 'shipping_address'),
		'shippingcity' => array('ShoppCustomerThemeAPI', 'shipping_city'),
		'shippingcountry' => array('ShoppCustomerThemeAPI', 'shipping_country'),
		'shippingpostcode' => array('ShoppCustomerThemeAPI', 'shipping_postcode'),
		'shippingprovince' => array('ShoppCustomerThemeAPI', 'shipping_state'),
		'shippingstate' => array('ShoppCustomerThemeAPI', 'shipping_state'),
		'shippingxaddress' => array('ShoppCustomerThemeAPI', 'shipping_xaddress'),

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

	public static function _apicontext () {
		return 'checkout';
	}

	/**
	 * _setobject - returns the global context object used in the shopp('checkout) call
	 *
	 * @author John Dillick, Jonathan Davis
	 * @since 1.2
	 *
	 **/
	public static function _setobject ( $Object, $object, $tag ) {

		if ( is_object($Object) && is_a($Object, 'ShoppOrder') && 'checkout' == strtolower($object) ) return $Object;
		else if ( strtolower($object) != 'checkout' ) return $Object; // not mine, do nothing

		$handler = self::$register[ $tag ];
		if ( is_array($handler) && 'ShoppCustomerThemeAPI' == $handler[0] )
			return ShoppCustomer();

		return ShoppOrder();
	}

	public static function billing_name ( $result, $options, $O ) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Billing->name;
		if (!empty($O->Billing->name))
			$options['value'] = $O->Billing->name;
		return '<input type="text" name="billing[name]" id="billing-name" '.inputattrs($options).' />';
	}

	public static function billing_address ( $result, $options, $O ) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Billing->address;
		if (!empty($O->Billing->address))
			$options['value'] = $O->Billing->address;
		return '<input type="text" name="billing[address]" id="billing-address" '.inputattrs($options).' />';
	}

	public static function billing_card ( $result, $options, $O ) {
		$card = $O->Billing->card;
		$modes = array('input', 'value');
		$classes = array('paycard');

		$defaults = array(
			'class' => '',
			'mode' => 'input',
			'mask' => 'X',
		);
		$options = array_merge($defaults, $options);

		if ( ! in_array($options['mode'], $modes) ) $options['mode'] = reset($modes);
		$options['value'] = PayCard::mask($card, $options['mask']);
		$classes[] = $options['class'];

		extract($options, EXTR_SKIP);

		if ( 'value' == $mode ) return $value;

		$options['class'] = join(' ', $classes);

		if ( ! isset($options['autocomplete']) ) $options['autocomplete'] = 'off';
		return '<input type="text" name="billing[card]" id="billing-card" ' . inputattrs($options) . ' />';
	}

	public static function billing_card_expires_mm ( $result, $options, $O ) {

		$name = 'billing[cardexpires-mm]';
		$id = 'billing-cardexpires-mm';

		$defaults = array(
			'mode' => 'input',
			'class' => 'paycard',
			'autocomplete' => 'off',
			'type' => 'menu',
			'value' => $O->Billing->cardexpires > 0 ? date("m",$O->Billing->cardexpires) : '',
		);
		$options = array_merge($defaults, $options);

		if ( 'value' == $options['mode'] ) return date('m', $O->Billing->cardexpires);

		if ( 'text' == $options['type'] )
			return '<input type="text" name="' . $name . '" id="' . $id . '" ' . inputattrs($options) . ' />';

		$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');

		$menu = array();
		$menu[] = '<select name="' . $name . '" id="' . $id . '">';
		$menu[] = '<option></option>';
		$menu[] = menuoptions($months, $options['value']);
		$menu[] = '</select>';

		return join('', $menu);
	}

	public static function billing_card_expires_yy ( $result, $options, $O ) {

		$name = 'billing[cardexpires-yy]';
		$id = 'billing-cardexpires-yy';

		$defaults = array(
			'mode' => 'input',
			'class' => 'paycard',
			'autocomplete' => 'off',
			'type' => 'menu',
			'value' => $O->Billing->cardexpires > 0 ? date('y', $O->Billing->cardexpires) : '',
			'max' => 20
		);
		$options = array_merge($defaults, $options);

		if ( 'value' == $options['mode'] ) return date('m', $O->Billing->cardexpires);

		if ( 'text' == $options['type'] )
			return '<input type="text" name="' . $name . '" id="' . $id . '" ' . inputattrs($options) . ' />';

		$time = current_time('timestamp');
		$thisyear = date('y', $time);
		$years = array_map( create_function('$n','return sprintf("%02d", $n);'), range((int)$thisyear, (int)$thisyear + $options['max'] ) );

		$menu = array();
		$menu[] = '<select name="' . $name . '" id="' . $id . '">';
		$menu[] = '<option></option>';
		$menu[] = menuoptions($years, $options['value']);
		$menu[] = '</select>';

		return join('', $menu);

	}

	public static function billing_card_holder ( $result, $options, $O ) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Billing->cardholder;
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($O->Billing->cardholder))
			$options['value'] = $O->Billing->cardholder;
		return '<input type="text" name="billing[cardholder]" id="billing-cardholder" '.inputattrs($options).' />';
	}

	public static function billing_card_type ( $result, $options, $O ) {
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

	public static function billing_cvv ( $result, $options, $O ) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($_POST['billing']['cvv']))
			$options['value'] = $_POST['billing']['cvv'];
		$options['class'] = isset($options['class']) ? $options['class'].' paycard':'paycard';
		return '<input type="text" name="billing[cvv]" id="billing-cvv" '.inputattrs($options).' />';
	}

	public static function billing_locale ( $result, $options, $O ) {
		$Shopp = Shopp::object();

		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');
		$output = false;

		if ( ! empty($options['mode']) && 'value' == $options['mode'] )
			return $O->Billing->locale;

		if ( ! isset($options['selected']) ) {
			$options['selected'] = $O->Billing->locale ? $O->Billing->locale : false;
		}

		$rates = shopp_setting('taxrates');
		foreach ( $rates as $rateset ) { // @todo - what if more than one set of local rates applies to current country/zone? ie. conditions
			if ( isset( $rateset['locals'] ) ) {
				$locales[ $rateset['country'] . $rateset['zone'] ] = array_keys($rateset['locals']);
			}
		}

		// if there are local tax jurisdictions in settings
		if ( ! empty($locales) ) {
			// Add all the locales to the javascript environment
			add_storefrontjs('var locales = '.json_encode($locales).';',true);

			// $Taxes = new CartTax();
			$Tax = ShoppOrder()->Tax;

			// Check for local rates applying to current country/zone

			$settings = $Tax->settings();
			foreach ( $settings as $setting ) {
				if ( isset($setting['locals']) ) {
					$localities = array_keys($setting['locals']);
					break;
				}
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

	public static function billing_localities ( $result, $options, $O ) {
		$rates = shopp_setting("taxrates");
		foreach ((array)$rates as $rate) if (isset($rate['locals']) && is_array($rate['locals'])) return true;
		return false;
	}

	public static function billing_xcsc ( $result, $options, $O ) {
		$Payments = $O->Payments;
		$defaults = array(
			'input' => false,
			'class' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( empty($input) ) return;

		$classes = array('paycard', 'xcsc');
		if ( ! empty($class) ) $classes[] = $class;
		$options['class'] = join(' ', $classes);

		// Load valid extra fields for accepted payment cards
		$valid = get_transient('shopp_billing_xcsc_valid_fields');
		if ( ! $valid ) {
			$cards = $Payments->accepted();
			foreach ( $cards as $card ) {
				if ( empty($card->inputs) ) continue;
				foreach ( $card->inputs as $field => $size )
					$valid[ $field ] = $size;
			}

			set_transient('shopp_billing_xcsc_valid_fields', $valid, 86400);
		}

		if ( ! array_key_exists($input, $valid) ) return;

		if ( isset($_POST['billing']['xcsc'][ $input ]) && ! empty($_POST['billing']['xcsc'][ $input ]) )
			$options['value'] = $_POST['billing']['xcsc'][ $input ];

		$id = 'billing-xcsc-' . sanitize_title_with_dashes($input);

		if ( ! isset( $options['autocomplete']) ) $options['autocomplete'] = 'off';
		return '<input type="text" name="billing[xcsc][' . esc_attr($input) . ']" id="' . $id . '" ' . inputattrs($options) . ' />';
	}

	public static function billing_xcsc_required ( $result, $options, $O ) {
		$Payments = $O->Payments;
		$cards = $Payments->accepted();

		foreach ( $cards as $card )
			if ( ! empty($card->inputs) ) return true;

		return false;
	}

	public static function card_required ( $result, $options, $O ) {
		if ($O->Cart->total() == 0) return false;

		$Payments = $O->Payments;
		$cards = $Payments->accepted();
		return ! empty($cards);
	}

	public static function cart_summary ( $result, $options, $O ) {

		$templates = array('summary.php');
		$context = ShoppStorefront::intemplate(); // Set summary context
		if ( ! empty($context) ) // Prepend the summary-context.php template file
			array_unshift($templates, "summary-$context");

		ob_start();
		locate_shopp_template($templates, true);
		$content = ob_get_clean();

		// If inside the checkout form, strip the extra <form> tag so we don't break standards
		// This is ugly, but necessary given the different markup contexts the cart summary is used in
		if ( 'checkout.php' == $context )
			$content = preg_replace('/<\/?form.*?>/', '', $content);

		return $content;
	}

	public static function completed ( $result, $options, $O ) {
		if ( $O->purchase === false ) return false;
		if ( ! ShoppPurchase() || empty(ShoppPurchase()->id) ) {
			ShoppPurchase(new ShoppPurchase($O->purchase));
			ShoppPurchase()->load_purchased();
		}
		return (!empty(ShoppPurchase()->id));
	}

	public static function confirm_button ( $result, $options, $O ) {
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

	public static function confirm_password ( $result, $options, $O ) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($O->Customer->_confirm_password))
			$options['value'] = $O->Customer->_confirm_password;
		return '<input type="password" name="confirm-password" id="confirm-password" '.inputattrs($options).' />';
	}

	public static function customer_info ( $result, $options, $O ) {
		$fields = $O->Customer->info;
		$defaults = array(
			'name' => false, // REQUIRED
			'info' => false,
			'mode' => false,
			'title' => '',
			'type' => 'hidden',
			'value' => '',
			'options' => ''
		);

		if ( isset($options['name']) && is_array($fields) && isset($fields[ $options['name'] ]) )
			$defaults['value'] = $info = $fields[ $options['name'] ];

		if ('textarea' == $defaults['type']) {
			$defaults['cols'] = '30';
			$defaults['rows'] = '3';
		}
		$op = array_merge($defaults, $options);
		extract($op, $EXTR_SKIP);

		// Allowed input types
		$allowed_types = array('text', 'hidden', 'password', 'checkbox', 'radio', 'textarea', 'menu');

		// Input types that can override option-specified value with the loaded data value
		$value_override = array('text', 'hidden', 'password', 'textarea', 'menu');

		/// Allowable attributes for textarea inputs
		$textarea_attrs = array('accesskey', 'title', 'tabindex', 'class', 'disabled', 'required');
		$select_attrs = array( 'title', 'required', 'class', 'disabled', 'required', 'size', 'tabindex', 'accesskey', 'placeholder' );

		if ( ! $name ) {// Iterator for customer info fields
			if ( ! isset($O->_customer_info_loop) ) {
				reset($fields->named);
				$O->_customer_info_loop = true;
			} else next($fields->named);

			if (current($fields->named) !== false) return true;
			else {
				unset($O->_customer_info_loop);
				return false;
			}
		}

		if ( $name && 'value' == $mode ) return $info;

		if ( ! in_array($type, $allowed_types) ) $type = 'hidden';
		if ( empty($title) ) $op['title'] = $name;
		$id = 'customer-info-' . sanitize_title_with_dashes($name);

		if ( in_array($type, $value_override) && ! empty($info) )
			$value = $info;

		switch ( strtolower($type) ) {
			case 'textarea':
				return '<textarea name="info[' . esc_attr($name) . ']" cols="' . (int) $cols . '" rows="' . (int) $rows . '" id="' . $id . '" ' . inputattrs($op, $textarea_attrs) . '>' . esc_html($value) . '</textarea>';
				break;
			case 'menu':
				if ( is_string($options) ) $options = explode(',', $options);
				return '<select name="info[' . esc_attr($name) . ']" id="' . $id . '" ' . inputattrs($op, $select_attrs) . '>' . menuoptions($options, $value) . '</select>';
				break;
			default:
				return '<input type="' . $type . '" name="info[' . esc_attr($name) . ']" id="' . $id . '" ' . inputattrs($op) . ' />';
				break;
		}
	}

	public static function data ( $result, $options, $O ) {
		if ( ! is_array($O->data) ) return false;
		$data = current($O->data);
		$name = key($O->data);
		if ( isset($options['name']) ) return $name;
		return $data;
	}

	public static function error ( $result, $options, $O ) {
		return ShoppStorefrontThemeAPI::errors($result, $options, $O);
	}

	public static function checkout_function ( $result, $options, $O ) {
		$Payments = $O->Payments;
		$defaults = array(
			'updating' => '<div class="shoppui-spinfx-align"><span class="shoppui-spinner shoppui-spinfx shoppui-spinfx-steps8"></span></div>'
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
		$output = '<div><input id="shopp-checkout-function" type="hidden" name="checkout" value="' . $value . '" /></div>';

		if ( 'confirmed' == $value ) $output = apply_filters('shopp_confirm_form', $output);
		else $output = apply_filters('shopp_checkout_form', $output);
		return $output;
	}

	public static function gateway_inputs ( $result, $options, $O ) {
		return apply_filters('shopp_checkout_gateway_inputs', false);
	}

	public static function guest ( $result, $options, $O ) {
		$allowed = array('class','checked','title');
		$defaults = array(
			'label' => __('Checkout as a guest','Shopp'),
			'checked' => 'off'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if ( $O->Customer->session(ShoppCustomer::GUEST) || Shopp::str_true($checked) )
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

	public static function has_data ( $result, $options, $O ) {
		reset($O->data);
		return ( is_array($O->data) && count($O->data) > 0 );
	}

	public static function clickwrap ( $result, $options, $O ) {
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

		if ( "false" !== $agreement && ! empty( $agreement ) ) {
			$page = get_page_by_path($agreement);
			$frame = '<div class="scrollable clickwrap clickwrap-terms'.( $termsclass ? " $termsclass" : "" ).'">'.apply_filters('shopp_checkout_clickwrap_terms',$page->post_content).'</div>';
		}
		$input = '<input type="hidden" name="data[clickwrap]" value="no" /><input type="checkbox" name="data[clickwrap]" id="clickwrap" value="agreed" '.inputattrs($options,$attrs).' />';
		return $frame.$input;
	}


	public static function not_logged_in ( $result, $options, $O ) {
		return ( ! $O->Customer->loggedin() && 'none' !== shopp_setting('account_system') );
	}

	public static function order_data ( $result, $options, $O ) {
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

		if ( isset($options['name']) && array_key_exists($options['name'], ShoppOrder()->data) )
			$defaults['value'] = ShoppOrder()->data[ $options['name'] ];

		if ( isset($options['type']) && 'textarea' == $options['type'] ) {
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
		$textarea_attrs = array('accesskey','title','tabindex','class','disabled','required','maxlength');

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

	public static function payoption ( $result, $options, $O ) {
		$payoption = $O->Payments->current();
		$defaults = array(
			'labelpos' => 'after',
			'labeling' => false,
			'type' => 'hidden',
			'value' => $payoption->slug
		);
		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		if ( Shopp::str_true($return) ) return $value;

		$types = array('radio','checkbox','hidden');
		if ( ! in_array($type, $types) ) $type = 'hidden';

		$_ = array();
		if ( Shopp::str_true($labeling) ) {
			$_[] = '<label class="'.esc_attr($options['value']).'">';
			if ($labelpos == "before") $_[] = $payoption->label;
		}
		$_[] = '<input type="' . $type . '" name="paymethod" id="paymethod-' . esc_attr($options['value']) . '"' . Shopp::inputattrs($options) . ' />';
		if ( Shopp::str_true($labeling) ) {
			if ($labelpos == "after") $_[] = $payoption->label;
			$_[] = '</label>';
		}

		return join("",$_);
	}

	public static function payoptions ( $result, $options, $O ) {
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if ( $O->Cart->orderisfree() ) return false;
		$Payments = $O->Payments;
		$payment_methods = apply_filters('shopp_payment_methods', $Payments->count() );
		if ( $payment_methods <= 1 ) return false; // Skip if only one gateway is active
		$defaults = array(
			'default' => null,
			'exclude' => false,
			'type' => 'menu',
			'mode' => false,
			'logos' => false
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);
		unset($options['type']);

		if ( $mode === 'loop' ) {
			if ( ! isset($O->_pay_loop) ) {
				$Payments->rewind();
				$O->_pay_loop = true;
			} else $Payments->next();

			if ( false !== $Payments->current() ) return true;
			else {
				unset($O->_pay_loop);
				return false;
			}
		}

		$excludes = array_map('sanitize_title_with_dashes', explode(',', $exclude));
		$payoptions = $Payments->keys();

		$payoptions = array_diff($payoptions, $excludes);
		if ( ! $Payments->userset() ) $Payments->selected($default);
		$SelectedPayment = $Payments->selected();

		$output = '';
		switch ($type) {
			case "list":
				$output .= '<span><ul>';

				if ( $logos ) { // Add payment logos
					$logos = explode(' ', strtolower($logos) );
					$logoclasses = array('shoppui-cards');
					foreach ( $logos as $ls ) {
						if ( in_array($ls, array('icon','small','big','huge','shadow')) )
							$logoclasses[] = $ls;
					}
					$logoclasses = join(' ', $logoclasses);
				}

				foreach ( $payoptions as $value ) {
					if ( in_array($value, $excludes) ) continue;
					$Payoption = $Payments->get($value);
					$options['value'] = $value;
					$options['checked'] = ( $SelectedPayment->slug == $Payoption->slug ) ? true : false;
					if ( false === $options['checked'] ) unset($options['checked']);
					$label = $Payoption->label;

					if ( $logos ) {
						$label = '&nbsp;<span class="' . esc_attr($logoclasses) . '">';
						if ( empty($Payoption->cards) ) $label .= '<span class="shoppui-' . esc_attr($Payoption->slug) . '">' . esc_html($Payoption->label) . '</span>&nbsp;';
						else {
							foreach ( $Payoption->cards as $card )
								$label .= '<span class="shoppui-' . esc_attr($card) . '">' . esc_html($card) . '</span>';
						}
						$label .= '</span>';
					}
					$output .= '<li><label><input type="radio" name="paymethod" ' . Shopp::inputattrs($options) . ' /> ' . $label . '</label></li>';
				}
				$output .= '</ul></span>';
				break;
			case "hidden":
				if (!isset($options['value']) && $default) $options['value'] = $O->paymethod;
				$output .= '<input type="hidden" name="paymethod"' . Shopp::inputattrs($options) . ' />';
				break;
			default:
				$output .= '<select name="paymethod" ' . Shopp::inputattrs($options, $select_attrs) . '>';
				foreach ( $payoptions as $value ) {
					if ( in_array($value, $excludes) ) continue;
					$Payoption = $Payments->get($value);
					$selected = ( $SelectedPayment->slug == $Payoption->slug ) ? ' selected="selected"' : '';
					$output .= '<option value="' . $value . '"' . $selected . '>' . $Payoption->label . '</option>';
				}
				$output .= '</select>';
				break;
		}

		return $output;
	}

	public static function receipt ( $result, $options, $O ) {
		$Purchase = ShoppPurchase();
		if ( ! $Purchase ) return false;
		if ( ! $Purchase->exists() ) return false;
		return $Purchase->receipt();
	}

	public static function residential_shipping_address ( $result, $options, $O ) {
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

	public static function same_billing_address ( $result, $options, $O ) {
		$allowed = array('class','checked');
		$defaults = array(
			'label' => __('Same billing address','Shopp'),
			'checked' => 'on',
			'type' => 'billing',
			'class' => ''
		);
		$options = array_merge($defaults,$options);
		$options['type'] = 'billing';
		return ShoppCustomerThemeAPI::same_shipping_address($result,$options,$O);
	}

	public static function shipping_name ( $result, $options, $O ) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->Shipping->name;
		if (!empty($O->Shipping->name))
			$options['value'] = $O->Shipping->name;
		return '<input type="text" name="shipping[name]" id="shipping-name" '.inputattrs($options).' />';
	}

	public static function shipping_country ( $result, $options, $O ) {
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

	public static function submit ( $result, $options, $O ) {
		$submit_attrs = array('title','class','value','disabled','tabindex','accesskey');

		if (!isset($options['value'])) $options['value'] = __('Submit Order','Shopp');
		$options['class'] = isset($options['class'])?$options['class'].' checkout-button':'checkout-button';

		$wrapclass = '';
		if (isset($options['wrapclass'])) $wrapclass = ' '.$options['wrapclass'];

		$buttons = array('<input type="submit" name="process" id="checkout-button" '.inputattrs($options,$submit_attrs).' />');

		if ( ! $O->Cart->orderisfree() )
			$buttons = apply_filters('shopp_checkout_submit_button', $buttons, $options, $submit_attrs);

		$_ = array();
		foreach ($buttons as $label => $button)
			$_[] = '<span class="payoption-button payoption-'.sanitize_title_with_dashes($label).($label === 0?$wrapclass:'').'">'.$button.'</span>';

		return join("\n", $_);
	}

	public static function submit_login ( $result, $options, $O ) {
		$string = '<input type="submit" name="submit-login" id="submit-login" '.inputattrs($options).' />';
		return $string;
	}

	public static function url ( $result, $options, $O ) {
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

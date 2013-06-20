<?php
/**
 * ShoppCustomerThemeAPI - Provided theme api tags.
 *
 * @version 1.0
 * @since 1.2
 * @package shopp
 * @subpackage ShoppCustomerThemeAPI
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_customer_info', 'esc_html');
add_filter('shopp_customer_info', 'wptexturize');
add_filter('shopp_customer_info', 'convert_chars');
add_filter('shopp_customer_info', 'wpautop');

class ShoppCustomerThemeAPI implements ShoppAPI {
	static $register = array(
		'accounts' => 'accounts',
		'accounturl' => 'account_url',
		'action' => 'action',
		'billingaddress' => 'billing_address',
		'billingcity' => 'billing_city',
		'billingcountry' => 'billing_country',
		'billingpostcode' => 'billing_postcode',
		'billingprovince' => 'billing_state',
		'billingstate' => 'billing_state',
		'billingxaddress' => 'billing_xaddress',
		'company' => 'company',
		'confirmpassword' => 'confirm_password',
		'download' => 'download',
		'downloads' => 'downloads',
		'email' => 'email',
		'emaillogin' => 'account_login',
		'loginnamelogin' => 'account_login',
		'accountlogin' => 'account_login',
		'errors' => 'errors',
		'errorsexist' => 'errors_exist',
		'firstname' => 'first_name',
		'hasaccount' => 'has_account',
		'hasdownloads' => 'has_downloads',
		'hasinfo' => 'has_info',
		'haspurchases' => 'has_purchases',
		'info' => 'info',
		'lastname' => 'last_name',
		'loggedin' => 'logged_in',
		'loginerrors' => 'errors',
		'loginlabel' => 'login_label',
		'loginname' => 'login_name',
		'management' => 'management',
		'marketing' => 'marketing',
		'menu' => 'menu',
		'notloggedin' => 'not_logged_in',
		'orderlookup' => 'order_lookup',
		'password' => 'password',
		'passwordchangefail' => 'password_change_fail',
		'passwordchanged' => 'password_changed',
		'passwordlogin' => 'password_login',
		'phone' => 'phone',
		'process' => 'process',
		'profilesaved' => 'profile_saved',
		'purchases' => 'purchases',
		'receipt' => 'order',
		'order' => 'order',
		'recoverbutton' => 'recover_button',
		'recoverurl' => 'recover_url',
		'register' => 'register',
		'registrationerrors' => 'registration_errors',
		'registrationform' => 'registration_form',
		'residentialshippingaddress' => 'residential_shipping_address',
		'sameshippingaddress' => 'same_shipping_address',
		'savebutton' => 'save_button',
		'shipping' => 'shipping',
		'shippingaddress' => 'shipping_address',
		'shippingcity' => 'shipping_city',
		'shippingcountry' => 'shipping_country',
		'shippingpostcode' => 'shipping_postcode',
		'shippingprovince' => 'shipping_state',
		'shippingstate' => 'shipping_state',
		'shippingxaddress' => 'shipping_xaddress',
		'submitlogin' => 'submit_login',
		'type' => 'type',
		'loginbutton' => 'submit_login',
		'url' => 'url',
		'wpusercreated' => 'wpuser_created'
	);


	public static function _apicontext () { return 'customer'; }

	/**
	 * _setobject - returns the global context object used in the shopp('customer') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	public static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'Customer') ) return $Object;

		if ( strtolower($object) != 'customer' ) return $Object; // not mine, do nothing
		else {
			return ShoppCustomer();
		}
		return false;
	}

	public static function account_login ($result, $options, $O) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($_POST['account-login']))
			$options['value'] = $_POST['account-login'];
		return '<input type="text" name="account-login" id="account-login"'.inputattrs($options).' />';
	}

	public static function accounts ($result, $options, $O) { return shopp_setting('account_system'); }

	public static function account_url ($result, $options, $O) { return shoppurl(false,'account'); }

	public static function action ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		$request = false; $id = false;
		if (isset($Storefront->account)) extract($Storefront->account);
		return shoppurl(array($request=>''),'account');
	}

	public static function billing_address ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Billing->address;
		if (!empty(ShoppOrder()->Billing->address))
			$options['value'] = ShoppOrder()->Billing->address;
		return '<input type="text" name="billing[address]" id="billing-address" '.inputattrs($options).' />';
	}

	public static function billing_city ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Billing->city;
		if (!empty(ShoppOrder()->Billing->city))
			$options['value'] = ShoppOrder()->Billing->city;
		return '<input type="text" name="billing[city]" id="billing-city" '.inputattrs($options).' />';
	}

	public static function billing_country ($result, $options, $O) {
		$Shopp = Shopp::object();

		$base = shopp_setting('base_operations');
		$countries = shopp_setting('target_markets');
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Billing->country;
		if (!empty(ShoppOrder()->Billing->country))
			$options['selected'] = ShoppOrder()->Billing->country;
		else if (empty($options['selected'])) $options['selected'] = $base['country'];
		$output = '<select name="billing[country]" id="billing-country" '.inputattrs($options,$select_attrs).'>';
		$output .= menuoptions($countries,$options['selected'],true);
		$output .= '</select>';
		return $output;
	}

	public static function billing_postcode ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Billing->postcode;
		if (!empty(ShoppOrder()->Billing->postcode))
			$options['value'] = ShoppOrder()->Billing->postcode;
		return '<input type="text" name="billing[postcode]" id="billing-postcode" '.inputattrs($options).' />';
	}


	public static function billing_state ($result, $options, $O) {
		$Shopp = Shopp::object();
		$base = shopp_setting('base_operations');
		$countries = shopp_setting('target_markets');
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Billing->state;
		if (!isset($options['selected'])) $options['selected'] = false;
		if (!empty(ShoppOrder()->Billing->state)) {
			$options['selected'] = ShoppOrder()->Billing->state;
			$options['value'] = ShoppOrder()->Billing->state;
		}

		$output = false;
		$country = $base['country'];
		if (!empty(ShoppOrder()->Billing->country))
			$country = ShoppOrder()->Billing->country;
		if (!array_key_exists($country,$countries)) $country = key($countries);

		$regions = Lookup::country_zones();
		$states = $regions[$country];

		if (isset($options['options']) && empty($states)) $states = explode(",",$options['options']);

		if (isset($options['type']) && $options['type'] == "text")
			return '<input type="text" name="billing[state]" id="billing-state" '.inputattrs($options).'/>';

		$classname = ( isset($options['class']) ? $options['class'].' ' : '' ).'billing-state';

		$label = (!empty($options['label']))?$options['label']:'';
		$options['disabled'] = 'disabled';
		$options['class'] = ($classname?"$classname ":"").'disabled hidden';

		$output .= '<select name="billing[state]" id="billing-state-menu" '.inputattrs($options,$select_attrs).'>';
		$output .= '<option value="">'.$label.'</option>';
		if (is_array($states) && !empty($states)) $output .= menuoptions($states,$options['selected'],true);
		$output .= '</select>';
		unset($options['disabled']);
		$options['class'] = $classname;
		$output .= '<input type="text" name="billing[state]" id="billing-state" '.inputattrs($options).'/>';

		return $output;
	}

	public static function billing_xaddress ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Billing->xaddress;
		if (!empty(ShoppOrder()->Billing->xaddress))
			$options['value'] = ShoppOrder()->Billing->xaddress;
		return '<input type="text" name="billing[xaddress]" id="billing-xaddress" '.inputattrs($options).' />';
	}

	public static function company ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->company;
		if (!empty(ShoppOrder()->Customer->company))
			$options['value'] = ShoppOrder()->Customer->company;
		return '<input type="text" name="company" id="company" '.inputattrs($options).' />';
	}

	public static function confirm_password ($result, $options, $O) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		$options['value'] = "";
		return '<input type="password" name="confirm-password" id="confirm-password"'.inputattrs($options).' />';
	}

	public static function download ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		$download = current($Storefront->downloads);
		$df = get_option('date_format');
		$string = '';
		if (array_key_exists('id',$options)) $string .= $download->download;
		if (array_key_exists('purchase',$options)) $string .= $download->purchase;
		if (array_key_exists('name',$options)) $string .= $download->name;
		if (array_key_exists('variation',$options)) $string .= $download->optionlabel;
		if (array_key_exists('downloads',$options)) $string .= $download->downloads;
		if (array_key_exists('key',$options)) $string .= $download->dkey;
		if (array_key_exists('created',$options)) $string .= $download->created;
		if (array_key_exists('total',$options)) $string .= money($download->total);
		if (array_key_exists('filetype',$options)) $string .= $download->mime;
		if (array_key_exists('size',$options)) $string .= readableFileSize($download->size);
		if (array_key_exists('date',$options)) $string .= _d($df,mktimestamp($download->created));
		if (array_key_exists('url',$options))
			$string .= shoppurl( ('' == get_option('permalink_structure') ?
					array('src'=>'download','shopp_download'=>$download->dkey) : 'download/'.$download->dkey),
				'account');
		return $string;
	}

	public static function downloads ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		if (empty($Storefront->downloads)) return false;
		if (!isset($Storefront->_downloads_loop)) {
			reset($Storefront->downloads);
			$Storefront->_downloads_loop = true;
		} else next($Storefront->downloads);

		if (current($Storefront->downloads) !== false) return true;
		else {
			unset($Storefront->_downloads_loop);
			reset($Storefront->downloads);
			return false;
		}
	}

	public static function email ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->email;
		if (!empty(ShoppOrder()->Customer->email))
			$options['value'] = ShoppOrder()->Customer->email;
		return '<input type="text" name="email" id="email" '.inputattrs($options).' />';
	}

	// Disabled and Deprecated
	public static function errors ($result, $options, $O) {
		// Now handled in Storefront controller always.
		return false;
	}
	public static function errors_exist ($result, $options, $O) {
		// Now handled in Storefront controller always.
		return false;
	}

	public static function first_name ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->firstname;
		if (!empty(ShoppOrder()->Customer->firstname))
			$options['value'] = ShoppOrder()->Customer->firstname;
		return '<input type="text" name="firstname" id="firstname" '.inputattrs($options).' />';
	}

	public static function has_account ($result, $options, $O) {
		$system = shopp_setting('account_system');
		if ($system == "wordpress") return ($O->wpuser != 0);
		elseif ($system == "shopp") return (!empty($O->password));
		else return false;
	}

	public static function has_downloads ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		if ( ! empty($Storefront->downloads) )
			reset($Storefront->downloads);
		return ( ! empty($Storefront->downloads) );
	}

	public static function has_info ($result, $options, $O) {
		if ( ! is_object($O->info) || empty($O->info->meta) ) return false;
		if ( ! isset($O->_info_looping) ) {
			reset($O->info->meta);
			$O->_info_looping = true;
		} else next($O->info->meta);

		if ( current($O->info->meta) !== false ) return true;
		else {
			unset($O->_info_looping);
			reset($O->info->meta);
			return false;
		}
	}

	public static function has_purchases ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		$filters = array();
		if ( isset($options['daysago']) )
			$filters['where'] = "UNIX_TIMESTAMP(o.created) > UNIX_TIMESTAMP()-".($options['daysago']*86400);

		if ( empty($Storefront->purchases) ) $O->load_orders($filters);
		reset($Storefront->purchases);
		return ( ! empty($Storefront->purchases) );
	}

	public static function info ($result, $options, $O) {
		$defaults = array(
			'mode' => 'input',
			'type' => 'text',
			'name' => false,
			'value' => false
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if ($O->_info_looping)
			$info = current($O->info->meta);
		elseif ($name !== false && is_object($O->info->named[$name]))
			$info = $O->info->named[$name];

		switch ($mode) {
			case "name": return $info->name; break;
			case "value": return apply_filters('shopp_customer_info', $info->value); break;
		}

		if (!$name && !empty($info->name)) $options['name'] = $info->name;
		elseif (!$name) return false;

		if (!$value && !empty($info->value)) $options['value'] = $info->value;

		$allowed_types = array("text","password","hidden","checkbox","radio");
		$type = in_array($type,$allowed_types)?$type:'hidden';
		return '<input type="'.$type.'" name="info['.$options['name'].']" id="customer-info-'.sanitize_title_with_dashes($options['name']).'"'.inputattrs($options).' />';
	}

	public static function last_name ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->lastname;
		if (!empty(ShoppOrder()->Customer->lastname))
			$options['value'] = ShoppOrder()->Customer->lastname;
		return '<input type="text" name="lastname" id="lastname" '.inputattrs($options).' />';
	}

	public static function logged_in ($result, $options, $O) { return ShoppCustomer()->logged_in(); }

	public static function login_label ($result, $options, $O) {
		$accounts = shopp_setting('account_system');
		$label = __('Email Address','Shopp');
		if ($accounts == "wordpress") $label = __('Login Name','Shopp');
		if (isset($options['label'])) $label = $options['label'];
		return $label;
	}

	public static function login_name ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->loginname;
		if (!empty(ShoppOrder()->Customer->loginname))
			$options['value'] = ShoppOrder()->Customer->loginname;
		return '<input type="text" name="loginname" id="login" '.inputattrs($options).' />';
	}

	public static function marketing ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->marketing;
		if (!empty(ShoppOrder()->Customer->marketing))
			$options['value'] = ShoppOrder()->Customer->marketing;
		$attrs = array("accesskey","alt","checked","class","disabled","format",
			"minlength","maxlength","readonly","size","src","tabindex",
			"title");
		if (Shopp::str_true($options['value'])) $options['checked'] = 'checked';
		$input = '<input type="hidden" name="marketing" value="no" />';
		$input .= '<input type="checkbox" name="marketing" id="marketing" value="yes" '.inputattrs($options,$attrs).' />';
		return $input;
	}


	public static function not_logged_in ($result, $options, $O) {
		return (! ShoppCustomer()->logged_in() && shopp_setting('account_system') != "none");
	}

	public static function order ($result, $options, $O) {
		return shoppurl(array('orders'=>ShoppPurchase()->id),'account');
	}

	public static function order_lookup ($result, $options, $O) {
		if (!empty($_POST['vieworder']) && !empty($_POST['purchaseid'])) {
			ShoppPurchase( new Purchase((int)$_POST['purchaseid']) );
			if (ShoppPurchase()->email == $_POST['email']) {
				ShoppPurchase()->load_purchased();
				ob_start();
				locate_shopp_template(array('receipt.php'),true);
				$content = ob_get_contents();
				ob_end_clean();
				return apply_filters('shopp_order_lookup',$content);
			} else {
				shopp_add_error( __('No order could be found with that information.','Shopp'), SHOPP_AUTH_ERR );
			}
		}

		ob_start();
		include(SHOPP_ADMIN_PATH."/orders/account.php");
		$content = ob_get_contents();
		ob_end_clean();
		return apply_filters('shopp_order_lookup',$content);
	}

	public static function password ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if ($options['mode'] == "value")
			return strlen(ShoppOrder()->Customer->password) == 34?str_pad('&bull;',8):ShoppOrder()->Customer->password;
		if (!empty(ShoppOrder()->Customer->password))
			$options['value'] = ShoppOrder()->Customer->password;
		return '<input type="password" name="password" id="password" '.inputattrs($options).' />';
	}

	public static function password_changed ($result, $options, $O) {
		$change = (isset($O->_password_change) && $O->_password_change);
		unset($O->_password_change);
		return $change;
	}

	public static function password_change_fail ($result, $options, $O) {
		$change = (isset($O->_password_change) && !$O->_password_change);
		unset($O->_password_change);
		return $change;
	}

	public static function password_login ($result, $options, $O) {
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if (!empty($_POST['password-login']))
			$options['value'] = $_POST['password-login'];
		return '<input type="password" name="password-login" id="password-login" '.inputattrs($options).' />';
	}

	public static function phone ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Customer->phone;
		if (!empty(ShoppOrder()->Customer->phone))
			$options['value'] = ShoppOrder()->Customer->phone;
		return '<input type="text" name="phone" id="phone" '.inputattrs($options).' />';
	}


	public static function profile_saved ($result, $options, $O) {
		$saved = (isset($O->_saved) && $O->_saved);
		unset($O->_saved);
		return $saved;
	}

	public static function purchases ($result, $options, $O) {
		$null = null;
		$Storefront = ShoppStorefront();
		if (!isset($Storefront->_purchases_loop)) {
			reset($Storefront->purchases);
			ShoppPurchase( current($Storefront->purchases) );
			$Storefront->_purchases_loop = true;
		} else {
			ShoppPurchase( next($Storefront->purchases) );
		}

		if (current($Storefront->purchases) !== false) return true;
		else {
			unset($Storefront->_purchases_loop);
			ShoppPurchase($null);
			return false;
		}
	}

	public static function recover_button ($result, $options, $O) {
		if (!isset($options['value'])) $options['value'] = __('Get New Password','Shopp');
		return '<input type="submit" name="recover-login" id="recover-button"'.inputattrs($options).' />';
	}

	public static function recover_url ($result, $options, $O) { return add_query_arg('recover','',shoppurl(false,'account')); }

	public static function register ($result, $options, $O) {
		$defaults = array(
			'label' => __('Register','Shopp')
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$submit_attrs = array('title','class','value','disabled','tabindex','accesskey');

		return '<input type="submit" name="shopp_registration" '.inputattrs($options,$submit_attrs).' />';
	}

	public static function registration_errors ($result, $options, $O) {
		$Errors =& ShoppErrors();
		if (!$Errors->exist(SHOPP_ERR)) return false;
		ob_start();
		locate_shopp_template(array('errors.php'),true);
		$markup = ob_get_contents();
		ob_end_clean();
		return $markup;
	}

	public static function registration_form ($result, $options, $O) {
		$regions = Lookup::country_zones();
		add_storefrontjs("var regions = ".json_encode($regions).";",true);
		shopp_enqueue_script('address');
		return $_SERVER['REQUEST_URI'];
	}

	public static function residential_shipping_address ($result, $options, $O) {
		$label = __("Residential shipping address","Shopp");
		if (isset($options['label'])) $label = $options['label'];
		if (isset($options['checked']) && Shopp::str_true($options['checked'])) $checked = ' checked="checked"';
		$output = '<label for="residential-shipping"><input type="hidden" name="shipping[residential]" value="no" /><input type="checkbox" name="shipping[residential]" value="yes" id="residential-shipping" '.$checked.' /> '.$label.'</label>';
		return $output;
	}

	public static function same_shipping_address ($result, $options, $O) {
		$allowed = array('class','checked');
		$defaults = array(
			'label' => __('Same shipping address','Shopp'),
			'checked' => 'on',
			'type' => 'shipping',
			'class' => ''
		);
		$options = array_merge($defaults,$options);
		extract($options);

		// Doing it wrong
		if ( 'shipping' == $type && 'billing' == ShoppOrder()->sameaddress ) return '';
		if ( 'billing' == $type && 'shipping' == ShoppOrder()->sameaddress ) return '';

		// Order->sameaddress defaults to false
		if ( ShoppOrder()->sameaddress ) {
			if ( 'off' == ShoppOrder()->sameaddress ) $options['checked'] = 'off';
			if ( ShoppOrder()->sameaddress == $type ) $options['checked'] = 'on';
		}

		$options['class'] = trim($options['class'].' sameaddress '.$type);
		$id = "same-address-$type";

		$_ = array();
		$_[] = '<label for="'.$id.'">';
		$_[] = '<input type="hidden" name="sameaddress" value="off" />';
		$_[] = '<input type="checkbox" name="sameaddress" value="'.$type.'" id="'.$id.'" '.inputattrs($options,$allowed).' />';
		$_[] = "&nbsp;$label</label>";

		return join('',$_);
	}

	public static function save_button ($result, $options, $O) {
		if (!isset($options['label'])) $options['label'] = __('Save','Shopp');
		$result = '<input type="hidden" name="customer" value="true" />';
		$result .= '<input type="submit" name="save" id="save-button"'.inputattrs($options).' />';
		return $result;
	}

	public static function shipping ($result, $options, $O) {
		return ShoppOrder()->Shipping;
	}

	public static function shipping_address ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Shipping->address;
		if (!empty(ShoppOrder()->Shipping->address))
			$options['value'] = ShoppOrder()->Shipping->address;
		return '<input type="text" name="shipping[address]" id="shipping-address" '.inputattrs($options).' />';
	}

	public static function shipping_city ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Shipping->city;
		if (!empty(ShoppOrder()->Shipping->city))
			$options['value'] = ShoppOrder()->Shipping->city;
		return '<input type="text" name="shipping[city]" id="shipping-city" '.inputattrs($options).' />';
	}

	public static function shipping_country ($result, $options, $O) {
		$base = shopp_setting('base_operations');
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if ( isset($options['mode']) && "value" == $options['mode'] ) return ShoppOrder()->Shipping->country;
		if (!empty(ShoppOrder()->Shipping->country))
			$options['selected'] = ShoppOrder()->Shipping->country;
		else if (empty($options['selected'])) $options['selected'] = $base['country'];

		$countries = shopp_setting('target_markets');

		$output = '<select name="shipping[country]" id="shipping-country" '.inputattrs($options,$select_attrs).'>';
		$output .= menuoptions($countries,$options['selected'],true);
		$output .= '</select>';
		return $output;
	}

	public static function shipping_postcode ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Shipping->postcode;
		if (!empty(ShoppOrder()->Shipping->postcode))
			$options['value'] = ShoppOrder()->Shipping->postcode;
		return '<input type="text" name="shipping[postcode]" id="shipping-postcode" '.inputattrs($options).' />';
	}

	public static function shipping_state ($result, $options, $O) {
		$Shopp = Shopp::object();

		$base = shopp_setting('base_operations');
		$countries = shopp_setting('target_markets');
		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');

		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Shipping->state;
		if (!isset($options['selected'])) $options['selected'] = false;
		if (!empty(ShoppOrder()->Shipping->state)) {
			$options['selected'] = ShoppOrder()->Shipping->state;
			$options['value'] = ShoppOrder()->Shipping->state;
		}

		$output = false;
		$country = $base['country'];
		if (!empty(ShoppOrder()->Shipping->country))
			$country = ShoppOrder()->Shipping->country;
		if (!array_key_exists($country,$countries)) $country = key($countries);

		$classname = "shipping-state".(isset($options['class']) ? ' '.$options['class'] : '');

		$regions = Lookup::country_zones();
		$states = $regions[$country];

		if (isset($options['options']) && empty($states)) $states = explode(",",$options['options']);

		if (isset($options['type']) && $options['type'] == "text")
			return '<input type="text" name="shipping[state]" id="shipping-state" '.inputattrs($options).'/>';

		$label = (!empty($options['label']))?$options['label']:'';
		$options['disabled'] = 'disabled';
		$options['class'] = ($classname?"$classname ":"").'disabled hidden';

		$output .= '<select name="shipping[state]" id="shipping-state-menu" '.inputattrs($options,$select_attrs).'>';
		$output .= '<option value="">'.$label.'</option>';
		if (is_array($states) && !empty($states)) $output .= menuoptions($states,$options['selected'],true);
		$output .= '</select>';
		unset($options['disabled']);
		$options['class'] = $classname;
		$output .= '<input type="text" name="shipping[state]" id="shipping-state" '.inputattrs($options).'/>';

		return $output;
	}

	public static function shipping_xaddress ($result, $options, $O) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return ShoppOrder()->Shipping->xaddress;
		if (!empty(ShoppOrder()->Shipping->xaddress))
			$options['value'] = ShoppOrder()->Shipping->xaddress;
		return '<input type="text" name="shipping[xaddress]" id="shipping-xaddress" '.inputattrs($options).' />';
	}

	public static function submit_login ($result, $options, $O) {
		if (!isset($options['value'])) $options['value'] = __('Login','Shopp');
		$string = "";
		$id = "submit-login";

		$request = $_GET;
		if (isset($request['acct']) && $request['acct'] == "logout") unset($request['acct']);

		if (ShoppStorefront()->checkout) {
			$id .= "-checkout";
			$string .= '<input type="hidden" name="redirect" value="checkout" />';
		} else $string .= '<input type="hidden" name="redirect" value="'.esc_attr(shoppurl($request,'account',ShoppOrder()->security())).'" />';
		$string .= '<input type="submit" name="submit-login" id="'.$id.'"'.inputattrs($options).' />';
		return $string;
	}

	/**
	 * Returns the customer type string. Optional parameter "placeholder" can be used to
	 * specify a string to return should the type field be empty:
	 *
	 *  shopp('customer.type', 'placeholder=Regular Customer')
	 */
	public static function type ($result, $options, $O) {
		$type = $O->type;
		if (empty($type) && isset($options['placeholder'])) $type = $options['placeholder'];
		return esc_html($type);
	}

	public static function url ($result, $options, $O) {
		$Shopp = Shopp::object();
		return shoppurl(array('acct'=>null),'account',$Shopp->Gateways->secure);
	}

	public static function wpuser_created ($result, $options, $O) { return $O->newuser; }

	/*************
	 * DEPRECATED *
	 **************/

	/**
	 * @deprecated Replaced by shopp('storefront','account-menu')
	 **/
	public static function menu ($result, $options, $O) {
		return ShoppStorefrontThemeAPI::account_menu($result,$options,$O);
	}

	/**
	 * @deprecated No longer necessary
	 **/
	public static function process ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		return $Storefront->account['request'];
	}

	/**
	 * @deprecated Replaced by shopp('storefront','account-menuitem')
	 **/
	public static function management ($result, $options, $O) {
		return ShoppStorefrontThemeAPI::account_menuitem($result,$options,$O);
	}

}

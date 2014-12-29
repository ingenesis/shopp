<?php
/**
 * customer.php
 *
 * ShoppCustomerThemeAPI provides shopp('customer') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2014
 * @package Shopp\API\Theme\Customer
 * @version 1.3
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_customer_info', 'esc_html');
add_filter('shopp_customer_info', 'wptexturize');
add_filter('shopp_customer_info', 'convert_chars');
add_filter('shopp_customer_info', 'wpautop');

/**
 * shopp('customer','...') tags
 *
 * @since 1.2
 * @version 1.3
 **/
class ShoppCustomerThemeAPI implements ShoppAPI {

	/**
	 * @var array The registry of available `shopp('cart')` properties
	 * @internal
	 **/
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
		'firstname' => 'first_name',
		'hasaccount' => 'has_account',
		'hasdownloads' => 'has_downloads',
		'hasinfo' => 'has_info',
		'haspurchases' => 'has_purchases',
		'info' => 'info',
		'lastname' => 'last_name',
		'loggedin' => 'logged_in',
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

	/**
	 * @var array References for the billing and shipping addresses
	 * @internal
	 **/
	static $addresses;

	/**
	 * Provides the authoritative Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		if ( null === self::$addresses ) {
			self::$addresses = array(
				'billing' => ShoppOrder()->Billing,
				'shipping' => ShoppOrder()->Shipping
			);
		}

		return 'customer';
	}

	/**
	 * Returns the proper global context object used in a shopp('collection') call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param ShoppCustomer $Object The ShoppOrder object to set as the working context
	 * @param string        $context The context being worked on by the Theme API
	 * @return ShoppCustomer The active object context
	 **/
	public static function _setobject ($Object, $object) {

		if ( is_object($Object) && is_a($Object, 'ShoppCustomer') ) return $Object;

		if ( strtolower($object) != 'customer' ) return $Object; // not mine, do nothing
		else {
			return ShoppCustomer();
		}
	}

	/**
	 * Provides the account login name field
	 *
	 * @api `shopp('customer.account-login')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The account login markup
	 **/
	public static function account_login ( $result, $options, $O ) {
		if ( ! isset($options['autocomplete']) ) $options['autocomplete'] = "off";
		if ( ! empty($_POST['account-login']) )
			$options['value'] = $_POST['account-login'];
		return '<input type="text" name="account-login" id="account-login"' . inputattrs($options) . ' />';
	}

	/**
	 * Provides the account system setting value
	 *
	 * @api `shopp('customer.accounts')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The account system setting (none,shopp,wordpress)
	 **/
	public static function accounts ( $result, $options, $O ) {
		return shopp_setting('account_system');
	}

	/**
	 * Provides the account page URL
	 *
	 * @api `shopp('customer.account-url')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The account URL
	 **/
	public static function account_url ( $result, $options, $O ) {
		return Shopp::url(false, 'account');
	}

	/**
	 * Provide the account form action URL
	 *
	 * @api `shopp('customer.action')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The account form action url
	 **/
	public static function action ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();
		$request = false; $id = false;
		if ( isset($Storefront->account) )
			extract($Storefront->account);
		return Shopp::url(array($request => ''), 'account');
	}

	/**
	 * Provides the billing street address input field
	 *
	 * @api `shopp('customer.billing-address')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The billing street address input markup
	 **/
	public static function billing_address ( $result, $options, $O ) {
		$options['address'] = 'billing';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides the billing address city input field
	 *
	 * @api `shopp('customer.billing-city')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function billing_city ( $result, $options, $O ) {
		$options['address'] = 'billing';
		$options['property'] = 'city';
		return self::address( $result, $options, $O );
	}


	/**
	 * Provides the billing address country input field
	 *
	 * @api `shopp('customer.billing-country')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **address**: `billing` The address entry to change
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function billing_country ( $result, $options, $O ) {
		$options['address'] = 'billing';
		return self::country( $result, $options, $O );
	}

	/**
	 * Provides the billing address postcode input field
	 *
	 * @api `shopp('customer.billing-postcode')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function billing_postcode ( $result, $options, $O ) {
		$options['address'] = 'billing';
		$options['property'] = 'postcode';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides a billing address state input field
	 *
	 * @api `shopp('customer.billing-state')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **type**: `menu` (menu, text) Changes the input type to a drop-down menu or text input field
	 * - **options**: A comma-separated list of options for the drop-down menu when the **type** is set to `menu`
	 * - **required**: `auto` (auto,on,off) Sets the field to be required automatically, always `on` or disabled `off`
	 * - **class**: The class attribute specifies one or more class-names for the input
	 * - **label**: The label shown as the default option of the drop-down menu when the **type** is set to `menu`
	 * @param ShoppCustomer $O       The working object
	 * @return string The state input markup
	 **/
	public static function billing_state ( $result, $options, $O ) {
		$options['address'] = 'billing';
		return self::state( $result, $options, $O );
	}

	/**
	 * Provides the billing address extra street address input field
	 *
	 * @api `shopp('customer.billing-xaddress')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function billing_xaddress ( $result, $options, $O ) {
		$options['address'] = 'billing';
		$options['property'] = 'xaddress';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides the company name input field
	 *
	 * @api `shopp('customer.billing-company')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function company ( $result, $options, $O ) {
		if ( ! isset($options['mode']) ) $options['mode'] = 'input';
		if ( $options['mode'] == 'value' ) return $O->company;
		if ( ! empty($O->company) )
			$options['value'] = $O->company;
		return '<input type="text" name="company" id="company" ' . inputattrs($options) . ' />';
	}

	/**
	 * Provides the confirm password input field
	 *
	 * @api `shopp('customer.confirm-password')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function confirm_password ( $result, $options, $O ) {
		if ( ! isset($options['autocomplete']) )
			$options['autocomplete'] = 'off';
		$options['value'] = '';
		return '<input type="password" name="confirm-password" id="confirm-password"' . inputattrs($options) . ' />';
	}

	/**
	 * Provides a property or list of properties for the current download from the downloads loop
	 *
	 * @api `shopp('customer.download')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The download property list
	 **/
	public static function download ( $result, $options, $O ) {
		$download = $O->_download;

		$df = get_option('date_format');
		$string = '';
		if ( array_key_exists('id', $options) ) $string .= $download->download;
		if ( array_key_exists('purchase', $options) ) $string .= $download->purchase;
		if ( array_key_exists('name', $options) ) $string .= $download->name;
		if ( array_key_exists('variation', $options) ) $string .= $download->optionlabel;
		if ( array_key_exists('downloads', $options) ) $string .= $download->downloads;
		if ( array_key_exists('key', $options) ) $string .= $download->dkey;
		if ( array_key_exists('created', $options) ) $string .= $download->created;
		if ( array_key_exists('total', $options) ) $string .= money($download->total);
		if ( array_key_exists('filetype', $options) ) $string .= $download->mime;
		if ( array_key_exists('size', $options) ) $string .= readableFileSize($download->size);
		if ( array_key_exists('date', $options) ) $string .= _d($df, $download->created);
		if ( array_key_exists('url', $options) )
			$string .= Shopp::url( ('' == get_option('permalink_structure') ?
					array('src' => 'download', 'shopp_download' => $download->dkey) : 'download/' . $download->dkey),
				'account');
		return $string;
	}

	/**
	 * Iterates over the downloads accessible to the customer
	 *
	 * @api `shopp('customer.downloads')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the next download exists, false otherwise
	 **/
	public static function downloads ( $result, $options, $O ) {
		if ( $O->each_download() ) return true;
		else {
			$O->reset_downloads();
			return false;
		}
	}

	/**
	 * Provides the customer email address input field
	 *
	 * @api `shopp('customer.email')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function email ( $result, $options, $O ) {
		if ( ! isset($options['mode']) ) $options['mode'] = 'input';
		if ( 'value' == $options['mode'] ) return $O->email;
		if ( ! empty($O->email) )
			$options['value'] = $O->email;
		return '<input type="text" name="email" id="email" ' . inputattrs($options) . ' />';
	}

	/**
	 * Provides the customer first name input field
	 *
	 * @api `shopp('customer.first-name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function first_name ( $result, $options, $O ) {
		if ( ! isset($options['mode']) ) $options['mode'] = 'input';
		if ( 'value' == $options['mode'] ) return $O->firstname;
		if ( ! empty($O->firstname) )
			$options['value'] = $O->firstname;
		return '<input type="text" name="firstname" id="firstname" ' . inputattrs($options) . ' />';
	}

	/**
	 * Checks if this customer has an account
	 *
	 * Works when the account system setting is set to Shopp-only or WP user integrated.
	 *
	 * @api `shopp('customer.has-account')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the customer has an account, false otherwise
	 **/
	public static function has_account ( $result, $options, $O ) {
		$system = shopp_setting('account_system');
		if ( 'wordpress' == $system ) return ( $O->wpuser != 0 );
		elseif ( 'shopp' == $system ) return ( ! empty($O->password) );
		else return false;
	}

	/**
	 * Detects if the customer has access to any downloads and, if so, loads them
	 *
	 * @api `shopp('customer.has-downloads')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **from**: A timestamp or datetime (2000-01-01 00:00:00) to get purchases after
	 * - **to**: A timestamp or datetime (2000-01-01 00:00:00) to get purchases before
	 * - **orderby**: `created` (id,created,modified) The column used to sort the downloads
	 * - **order**: `DESC` The order to sort by ascending `ASC` or descending `DESC`
	 * - **show**: The maximum number of downloads to show
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if there are downloads, false otherwise
	 **/
	public static function has_downloads ( $result, $options, $O ) {
		$defaults = array(
			'show' => false,
			'from' => false,
			'to' => false,
			'orderby' => 'created',
			'order' => 'DESC'
		);
		$options = array_merge($defaults, $options);
		return $O->has_downloads($options);
	}

	/**
	 * Iterate over custom customer information fields
	 *
	 * @api `shopp('customer.has-info')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the next field exists, false otherwise
	 **/
	public static function has_info ( $result, $options, $O ) {
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

	/**
	 * Checks if the customer has any purchases
	 *
	 * @api `shopp('customer.has-purchases')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **daysago**: Limit to find orders up to a given number of days from today
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if purchases are found, false otherwise
	 **/
	public static function has_purchases ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();

		$filters = array();
		if ( isset($options['daysago']) )
			$filters['where'] = "UNIX_TIMESTAMP(o.created) > UNIX_TIMESTAMP()-".($options['daysago']*86400);

		if ( empty($Storefront->purchases) ) $O->load_orders($filters);
		reset($Storefront->purchases);
		return ( ! empty($Storefront->purchases) );
	}

	/**
	 * Provides markup for a custom information input field to assign to the customer
	 *
	 * @api `shopp('customer.info')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **type**: `text` (text, password, hidden, checkbox, radio) The type of input field to generate
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function info ( $result, $options, $O ) {
		$fields = $O->info;
		$defaults = array(
			'mode' => 'input',
			'type' => 'text',
			'name' => false,
			'value' => false
		);

		if ( is_array($fields) && isset($options['name']) && isset($fields[ $options['name'] ]) )
			$defaults['value'] = $fields[ $options['name'] ];

		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( $O->_info_looping )
			$info = current($fields->meta);
		elseif ( $name !== false && is_object($fields->named[ $name ]) )
			$info = $fields->named[ $name ];

		switch ( strtolower($mode) ) {
			case 'name': return $info->name;
			case 'value': return apply_filters('shopp_customer_info', $info->value);
		}

		if ( ! $name && ! empty($info->name) ) $name = $info->name;
		elseif ( ! $name ) return false;

		if ( ! $value && ! empty($info->value) ) $options['value'] = $info->value;

		$allowed_types = array('text', 'password', 'hidden', 'checkbox', 'radio');
		$type = in_array($type, $allowed_types) ? $type : 'hidden';
		$id = 'customer-info-' . sanitize_title_with_dashes($name);

		return '<input type="' . $type . '" name="info[' . esc_attr($name) . ']" id="' . $id . '"' . inputattrs($options) . ' />';
	}

	/**
	 * Provides the customer last name input field
	 *
	 * @api `shopp('customer.last-name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function last_name ( $result, $options, $O ) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if ($options['mode'] == "value") return $O->lastname;
		if (!empty($O->lastname))
			$options['value'] = $O->lastname;
		return '<input type="text" name="lastname" id="lastname" '.inputattrs($options).' />';
	}

	/**
	 * Checks if the customer is currently logged in
	 *
	 * @api `shopp('customer.logged-in')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if logged in, false otherwise
	 **/
	public static function logged_in ( $result, $options, $O ) {
		return ShoppCustomer()->loggedin() && 'none' != shopp_setting('account_system');
	}

	/**
	 * Provides the account system appropriate login field label name
	 *
	 * @api `shopp('customer.login-label')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The label text
	 **/
	public static function login_label ( $result, $options, $O ) {
		$accounts = shopp_setting('account_system');
		$label = Shopp::__('Email Address');
		if ( 'wordpress' == $accounts ) $label = Shopp::__('Login Name');
		if ( isset($options['label']) ) $label = $options['label'];
		return $label;
	}

	/**
	 * Provides the customer login name input field
	 *
	 * @api `shopp('customer.login-name')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function login_name ( $result, $options, $O ) {
		if (!isset($options['mode'])) $options['mode'] = "input";
		if (!isset($options['autocomplete'])) $options['autocomplete'] = "off";
		if ($options['mode'] == "value") return $O->loginname;
		if (!empty($O->loginname))
			$options['value'] = $O->loginname;
		return '<input type="text" name="loginname" id="login" '.inputattrs($options).' />';
	}

	/**
	 * Provides the customer marketing toggle input field
	 *
	 * @api `shopp('customer.marketing')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **alt**: Specifies an alternate text for images (only for type="image")
	 * - **checked**: Specifies that an `<input>` element should be pre-selected when the page loads (for type="checkbox" or type="radio")
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **format**: Specifies special field formatting class names for JS validation
	 * - **minlength**: Sets a minimum length for the field enforced by JS validation
	 * - **maxlength**: Specifies the maximum number of characters allowed in an `<input>` element
	 * - **readonly**: Specifies that an input field is read-only
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function marketing ( $result, $options, $O ) {
		if ( ! isset($options['mode']) ) $options['mode'] = 'input';
		if ( $options['mode'] == 'value' ) return $O->marketing;
		if ( ! empty($O->marketing) )
			$options['value'] = $O->marketing;
		$attrs = array('accesskey', 'alt', 'checked', 'class', 'disabled', 'format',
			'minlength', 'maxlength', 'readonly', 'size', 'src', 'tabindex',
			'title');
		if ( Shopp::str_true($options['value']) ) $options['checked'] = true;
		$input = '<input type="hidden" name="marketing" value="no" />';
		$input .= '<input type="checkbox" name="marketing" id="marketing" value="yes" ' . inputattrs($options, $attrs) . ' />';
		return $input;
	}

	/**
	 * Checks if the customer is not logged in
	 *
	 * @api `shopp('customer.not-logged-in')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if not logged in, false otherwise
	 **/
	public static function not_logged_in ( $result, $options, $O ) {
		return ( ! ShoppCustomer()->loggedin() && 'none' != shopp_setting('account_system') );
	}

	/**
	 * Provides the URL for viewing an order
	 *
	 * @api `shopp('customer.order')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The order URL
	 **/
	public static function order ( $result, $options, $O ) {
		return Shopp::url(array('orders' => ShoppPurchase()->id), 'account');
	}

	/**
	 * Provides a markup widget to lookup an order by order ID and customer email address
	 *
	 * @api `shopp('customer.order-lookup')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The widget markup
	 **/
	public static function order_lookup ( $result, $options, $O ) {
		if ( ! empty($_POST['vieworder']) && ! empty($_POST['purchaseid']) ) {
			ShoppPurchase( new ShoppPurchase((int)$_POST['purchaseid']) );
			if ( ShoppPurchase()->exists() && ShoppPurchase()->email == $_POST['email'] ) {
				ShoppPurchase()->load_purchased();
				ob_start();
				locate_shopp_template(array('receipt.php'), true);
				$content = ob_get_clean();
				return apply_filters('shopp_order_lookup', $content);
			} else {
				shopp_add_error( Shopp::__('No order could be found with that information.'), SHOPP_AUTH_ERR );
			}
		}

		ob_start();
		include SHOPP_ADMIN_PATH . "/orders/account.php";
		$content = ob_get_clean();
		return apply_filters('shopp_order_lookup', $content);
	}

	/**
	 * Provides the customer password input field
	 *
	 * @api `shopp('customer.password')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **autocomplete**: `off` (on, off) Specifies whether an `<input>` element should have autocomplete enabled
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function password ( $result, $options, $O ) {
		if ( ! isset($options['mode']) ) $options['mode'] = 'input';
		if ( ! isset($options['autocomplete']) ) $options['autocomplete'] = 'off';
		if ( 'value' == $options['mode'] )
			return strlen($O->password) == 34 ? str_pad('&bull;', 8) : $O->password;
		if ( ! empty($O->password) )
			$options['value'] = $O->password;
		return '<input type="password" name="password" id="password" ' . inputattrs($options) . ' />';
	}

	/**
	 * Detects if the customer password changed
	 *
	 * @api `shopp('customer.password-changed')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the passsword changed, false otherwise
	 **/
	public static function password_changed ( $result, $options, $O ) {
		return $O->updated(ShoppCustomer::PASSWORD);
	}

	/**
	 * Detects if the customer password change failed
	 *
	 * @api `shopp('customer.password-change-fail')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the password change failed, false otherwise
	 **/
	public static function password_change_fail ( $result, $options, $O ) {
		return $O->updated(ShoppCustomer::PROFILE) && ! $O->updated(ShoppCustomer::PASSWORD);
	}

	/**
	 * Provides the login password for login forms in the checkout process
	 *
	 * @api `shopp('customer.password-login')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **autocomplete**: `off` (on, off) Specifies whether an `<input>` element should have autocomplete enabled
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function password_login ( $result, $options, $O ) {
		if ( ! isset($options['autocomplete']) ) $options['autocomplete'] = "off";
		if ( ! empty($_POST['password-login']) )
			$options['value'] = $_POST['password-login'];
		return '<input type="password" name="password-login" id="password-login" ' . inputattrs($options) . ' />';
	}

	/**
	 * Provides the customer phone number input field
	 *
	 * @api `shopp('customer.phone')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **autocomplete**: `off` (on, off) Specifies whether an `<input>` element should have autocomplete enabled
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function phone ( $result, $options, $O ) {
		if ( ! isset($options['mode']) ) $options['mode'] = 'input';
		if ( 'value' == $options['mode'] ) return $O->phone;
		if ( ! empty($O->phone) )
			$options['value'] = $O->phone;
		return '<input type="text" name="phone" id="phone" ' . inputattrs($options) . ' />';
	}

	/**
	 * Checks if the customer profile updates were saved
	 *
	 * @api `shopp('customer.profile-saved')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the profile was saved, false otherwise
	 **/
	public static function profile_saved ( $result, $options, $O ) {
		return $O->updated(ShoppCustomer::PROFILE);
	}

	/**
	 * Iterate over the customer purchases
	 *
	 * @api `shopp('customer.purchases')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if the next order exists, false otherwise
	 **/
	public static function purchases ( $result, $options, $O ) {
		$null = null;
		$Storefront = ShoppStorefront();
		if ( ! isset($Storefront->_purchases_loop) ) {
			reset($Storefront->purchases);
			ShoppPurchase( current($Storefront->purchases) );
			$Storefront->_purchases_loop = true;
		} else {
			ShoppPurchase( next($Storefront->purchases) );
		}

		if ( current($Storefront->purchases) !== false ) return true;
		else {
			unset($Storefront->_purchases_loop);
			ShoppPurchase($null);
			return false;
		}
	}

	/**
	 * Provides markup for a button to request password recovery for the customer
	 *
	 * @api `shopp('customer.recover-button')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The button markup
	 **/
	public static function recover_button ( $result, $options, $O ) {
		if ( ! isset($options['value']) ) $options['value'] = Shopp::__('Get New Password');
		return '<input type="submit" name="recover-login" id="recover-button"' . inputattrs($options) . ' />';
	}

	/**
	 * Provides the recover password URL
	 *
	 * @api `shopp('customer.recover-url')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The recover password URL
	 **/
	public static function recover_url ( $result, $options, $O ) {
		return add_query_arg('recover', '', Shopp::url(false, 'account'));
	}

	/**
	 * Provides markup for a button to submit new customer registration
	 *
	 * @api `shopp('customer.register')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * - **label**: Specifies the label of the button
	 * @param ShoppCustomer $O       The working object
	 * @return string The button markup
	 **/
	public static function register ( $result, $options, $O ) {
		$defaults = array(
			'label' => Shopp::__('Register')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$submit_attrs = array('title', 'class', 'label', 'value', 'disabled', 'tabindex', 'accesskey');

		return '<input type="submit" name="shopp_registration" ' . inputattrs($options, $submit_attrs) . ' />';
	}

	/**
	 * Provides new account registration error messages
	 *
	 * @api `shopp('customer.registration-errors')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The error markup
	 **/
	public static function registration_errors ( $result, $options, $O ) {
		$Errors = ShoppErrors();
			if ( ! $Errors->exist(SHOPP_ERR) ) return false;
		ob_start();
		locate_shopp_template(array('errors.php'), true);
		return ob_get_clean();
	}

	/**
	 * Provides the registration form action URL
	 *
	 * @api `shopp('customer.registration-form')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The registration form URL
	 **/
	public static function registration_form ( $result, $options, $O ) {
		$regions = Lookup::country_zones();
		add_storefrontjs('var regions = ' . json_encode($regions) . ';', true);
		shopp_enqueue_script('address');
		return Shopp::raw_request_url();
	}

	/**
	 * Provides a checkbox toggle to mark the shipping address as a residential address
	 *
	 * @api `shopp('customer.residential-shipping-address')`
	 * @since 1.2
	 *
	 * @param string       $result  The output
	 * @param array        $options The options
	 * - **label**: `Residential shipping address` The label for the checkbox input
	 * - **checked**: `on` (on, off) Specifies that an `<input>` element should be pre-selected when the page loads
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **title**: Specifies extra information about an element
	 * @param ShoppCustomer $O       The working object
	 * @return string Markup for the residential address checkbox toggle
	 **/
	public static function residential_shipping_address ( $result, $options, $O ) {
		$label = Shopp::__('Residential shipping address');
		if ( isset($options['label']) ) $label = $options['label'];
		if ( isset($options['checked']) && Shopp::str_true($options['checked']) ) $checked = ' checked="checked"';
		$output = '<label for="residential-shipping"><input type="hidden" name="shipping[residential]" value="no" /><input type="checkbox" name="shipping[residential]" value="yes" id="residential-shipping" ' . $checked . ' /> ' . $label . '</label>';
		return $output;
	}

	/**
	 * Provides a checkbox toggle to submit the address in the shipping address fields as the billing address
	 *
	 * @api `shopp('customer.same-billing-address')`
	 * @since 1.1
	 *
	 * @param string       $result  The output
	 * @param array        $options The options
	 * - **label**: `Same billing address` The label for the checkbox input
	 * - **checked**: `on` (on, off) Specifies that an `<input>` element should be pre-selected when the page loads
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * @param ShoppCustomer $O       The working object
	 * @return string The toggle markup
	 **/
	public static function same_shipping_address ( $result, $options, $O ) {
		$allowed = array('class', 'checked');
		$defaults = array(
			'label' => Shopp::__('Same shipping address'),
			'checked' => 'on',
			'type' => 'shipping',
			'class' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options);

		// Doing it wrong
		if ( 'shipping' == $type && 'billing' == ShoppOrder()->sameaddress ) return '';
		if ( 'billing' == $type && 'shipping' == ShoppOrder()->sameaddress ) return '';

		// Order->sameaddress defaults to false
		if ( ShoppOrder()->sameaddress ) {
			if ( 'off' == ShoppOrder()->sameaddress ) $options['checked'] = 'off';
			if ( ShoppOrder()->sameaddress == $type ) $options['checked'] = 'on';
		}

		$options['class'] = trim($options['class'] . ' sameaddress ' . $type);
		$id = "same-address-$type";

		$_ = array();
		$_[] = '<label for="' . $id . '">';
		$_[] = '<input type="hidden" name="sameaddress" value="off" />';
		$_[] = '<input type="checkbox" name="sameaddress" value="' . $type . '" id="' . $id . '" ' . inputattrs($options,$allowed) . ' />';
		$_[] = "&nbsp;$label</label>";

		return join('', $_);
	}

	/**
	 * Provides the customer profile save button
	 *
	 * @api `shopp('customer.save-button')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * - **label**: Specifies the value of the button element
	 * @param ShoppCustomer $O       The working object
	 * @return string The button markup
	 **/
	public static function save_button ( $result, $options, $O ) {
		if ( ! isset($options['label']) ) $options['label'] = Shopp::__('Save');
		$result = '<input type="hidden" name="customer" value="true" />';
		$result .= '<input type="submit" name="save" id="save-button"' . inputattrs($options) . ' />';
		return $result;
	}

	/**
	 * Provides the ShoppShipping address object
	 *
	 * @api `shopp('customer.shipping')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return ShoppShipping The shipping address object
	 **/
	public static function shipping ( $result, $options, $O ) {
		return ShoppOrder()->Shipping;
	}

	/**
	 * Provides the shipping street address input field
	 *
	 * @api `shopp('customer.shipping-address')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The billing street address input markup
	 **/
	public static function shipping_address ( $result, $options, $O ) {
		$options['address'] = 'shipping';
		$options['property'] = 'address';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides the shipping address city input field
	 *
	 * @api `shopp('customer.shipping-city')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function shipping_city ( $result, $options, $O ) {
		$options['address'] = 'shipping';
		$options['property'] = 'city';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides the shipping address country input field
	 *
	 * @api `shopp('customer.shipping-country')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **address**: `billing` The address entry to change
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function shipping_country ( $result, $options, $O ) {
		$options['address'] = 'shipping';
		return self::country( $result, $options, $O );
	}

	/**
	 * Provides the shipping address postcode input field
	 *
	 * @api `shopp('customer.shipping-postcode')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function shipping_postcode ( $result, $options, $O ) {
		$options['address'] = 'shipping';
		$options['property'] = 'postcode';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides a shipping address state input field
	 *
	 * @api `shopp('customer.shipping-state')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **type**: `menu` (menu, text) Changes the input type to a drop-down menu or text input field
	 * - **options**: A comma-separated list of options for the drop-down menu when the **type** is set to `menu`
	 * - **required**: `auto` (auto,on,off) Sets the field to be required automatically, always `on` or disabled `off`
	 * - **class**: The class attribute specifies one or more class-names for the input
	 * - **label**: The label shown as the default option of the drop-down menu when the **type** is set to `menu`
	 * @param ShoppCustomer $O       The working object
	 * @return string The state input markup
	 **/
	public static function shipping_state ( $result, $options, $O ) {
		$options['address'] = 'shipping';
		return self::state( $result, $options, $O );
	}

	/**
	 * Provides the shipping address extra street input field
	 *
	 * @api `shopp('customer.shipping-xaddress')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	public static function shipping_xaddress ( $result, $options, $O ) {
		$options['address'] = 'shipping';
		$options['property'] = 'xaddress';
		return self::address( $result, $options, $O );
	}

	/**
	 * Provides the submit login button markup
	 *
	 * @api `shopp('customer.submit-login')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **autocomplete**: (on, off) Specifies whether an `<input>` element should have autocomplete enabled
	 * - **accesskey**: Specifies a shortcut key to activate/focus an element. Linux/Windows: `[Alt]`+`accesskey`, Mac: `[Ctrl]``[Opt]`+`accesskey`
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **disabled**: Specifies that an `<input>` element should be disabled
	 * - **placeholder**: Specifies a short hint that describes the expected value of an `<input>` element
	 * - **required**: Adds a class that specified an input field must be filled out before submitting the form, enforced by JS
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **title**: Specifies extra information about an element
	 * - **label**: Specifies the value of the button element
	 * @param ShoppCustomer $O       The working object
	 * @return string The button markup
	 **/
	public static function submit_login ( $result, $options, $O ) {
		if ( ! isset($options['label']) ) $options['label'] = Shopp::__('Login');
		$string = '';
		$id = 'submit-login';

		$request = $_GET;
		if ( isset($request['acct']) && 'logout' == $request['acct'] ) unset($request['acct']);

		$context = ShoppStorefront::intemplate();
		if ( 'checkout.php' == $context ) {
			$id .= '-checkout';
			$string .= '<input type="hidden" name="redirect" value="checkout" />';
		} else $string .= '<input type="hidden" name="redirect" value="' . esc_attr(Shopp::url($request, 'account', ShoppOrder()->security())) . '" />';
		$string .= '<input type="submit" name="submit-login" id="' . $id . '"' . inputattrs($options) . ' />';
		return $string;
	}

	/**
	 * Returns the customer type string. Optional parameter "placeholder" can be used to
	 * specify a string to return should the type field be empty:
	 *
	 *  shopp('customer.type', 'placeholder=Regular Customer')
	 */

	/**
	 * Provides the customer type property
	 *
	 * @api `shopp('customer.type')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **placeholder**: Specify a default result if the customer type is empty
	 * @param ShoppCustomer $O       The working object
	 * @return void
	 **/
	public static function type ( $result, $options, $O ) {
		$type = $O->type;
		if ( empty($type) && isset($options['placeholder']) ) $type = $options['placeholder'];
		return esc_html($type);
	}

	/**
	 * Provide the customer account page URL
	 *
	 * @api `shopp('customer.url')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string The page URL
	 **/
	public static function url ( $result, $options, $O ) {
		$Shopp = Shopp::object();
		return Shopp::url(array('acct' => null), 'account', $Shopp->Gateways->secure);
	}

	/**
	 * Checks if a WP user account was created for the customer
	 *
	 * @api `shopp('customer.wpuser-created')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return bool True if an account was created, false otherwise
	 **/
	public static function wpuser_created ( $result, $options, $O ) {
		return $O->session(ShoppCustomer::WPUSER);
	}

	/** Address helper methods **/

	/**
	 * Verifies if the address entry exists
	 *
	 * @access private
	 * @since 1.3
	 *
	 * @param string $address The address entry to check
	 * @return string The address key
	 **/
	private static function valid_address ( $address ) {
		if ( isset(self::$addresses[ $address ]) ) return $address;
		return key(self::$addresses); // return the first key
	}

	/**
	 * Provides the address object for a given address entry
	 *
	 * @access private
	 * @since 1.3
	 *
	 * @param string $address The address entry to check
	 * @return ShoppAddress The address object;
	 **/
	private static function AddressObject ( $address ) {
		return self::$addresses[ self::valid_address($address) ];
	}

	/**
	 * Helper to provide address input field markup
	 *
	 * @internal
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
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
	 * - **size**: Specifies the width, in characters, of an `<input>` element
	 * - **src**: Specifies the URL of the image to use as a submit button (only for type="image")
	 * - **tabindex**: Specifies the tabbing order of an element
	 * - **cols**: Specifies the visible width of a `<textarea>`
	 * - **rows**: Specifies the visible number of lines in a `<textarea>`
	 * - **title**: Specifies extra information about an element
	 * - **value**: Specifies the value of an `<input>` element
	 * @param ShoppCustomer $O       The working object
	 * @return void
	 **/
	private static function address ( $result, $options, $O ) {
		$defaults = array(
			'mode' => 'input',
			'address' => 'billing',
			'property' => 'address'
		);
		$options = array_merge($defaults, $options);
		$options['address'] = self::valid_address($options['address']);
		$Address = self::AddressObject($options['address']);
		if ( ! property_exists($Address, $options['property']) ) $options['property'] = 'address';
		$options['value'] = $Address->{$options['property']};
		extract($options, EXTR_SKIP);

		if ( 'value' == $mode ) return $value;
		return '<input type="text" name="' . $address . '[' . $property . ']" id="' . $address . '-' . $property . '" '.inputattrs($options).' />';
	}

	/**
	 * Helper to provide country address input field markup
	 *
	 * @internal
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **address**: `billing` The address entry to change
	 * @param ShoppCustomer $O       The working object
	 * @return string The input markup
	 **/
	private static function country ( $result, $options, $O ) {
		$defaults = array(
			'mode' => 'input',
			'address' => 'billing',
		);
		$options = array_merge($defaults, $options);

		$options['address'] = self::valid_address($options['address']);
		$Address = self::AddressObject($options['address']);
		$options['value'] = $Address->country;
		$options['selected'] = $options['value'];
		$options['id'] = "{$options['address']}-country";
		extract($options, EXTR_SKIP);

		if ( 'value' == $mode ) return $value;

		$base = shopp_setting('base_operations');
		$countries = shopp_setting('target_markets');
		$select_attrs = array('title', 'required', 'class', 'disabled', 'required', 'size', 'tabindex', 'accesskey');

		if ( empty($selected) ) $selected = $base['country'];

		return '<select name="' . $address . '[country]" id="' . $id . '" ' . inputattrs($options, $select_attrs) . '>' .
			 		menuoptions($countries, $selected, true) .
					'</select>';
	}

	/**
	 * Helper method to render markup for state/province input fields
	 *
	 * @internal
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **mode**: `input` (input, value) Displays the field `input` or the current value of the property
	 * - **type**: `menu` (menu, text) Changes the input type to a drop-down menu or text input field
	 * - **options**: A comma-separated list of options for the drop-down menu when the **type** is set to `menu`
	 * - **required**: `auto` (auto,on,off) Sets the field to be required automatically, always `on` or disabled `off`
	 * - **class**: The class attribute specifies one or more class-names for the input
	 * - **label**: The label shown as the default option of the drop-down menu when the **type** is set to `menu`
	 * - **address**: `billing` (billing,shipping) Used to specify which address the field takes input for
	 * @param ShoppCustomer $O       The working object
	 * @return string The state input markup
	 **/
	private static function state ( $result, $options, $O ) {

		$defaults = array(
			'mode' => 'input',
			'type' => 'menu',
			'options' => '',
			'required' => 'auto',
			'class' => '',
			'label' => '',
			'address' => 'billing',
		);
		$options = array_merge($defaults, $options);

		$options['address'] = self::valid_address($options['address']);
		$Address = self::AddressObject($options['address']);
		$options['value'] = $Address->state;
		$options['selected'] = $options['value'];
		$options['id'] = "{$options['address']}-state";
		extract($options, EXTR_SKIP);

		if ( 'value' == $mode ) return $value;

		$base = (array) shopp_setting('base_operations');
		$countries = (array) shopp_setting('target_markets');
		$select_attrs = array('title', 'required', 'class', 'disabled', 'required', 'size', 'tabindex', 'accesskey');

		$country = $base['country'];

		if ( ! empty($Address->country) )
			$country = $Address->country;

		if ( ! array_key_exists($country, $countries) )
			$country = key($countries);

		$regions = Lookup::country_zones();
		$states = isset($regions[ $country ]) ? $regions[ $country ] : array();

		if ( ! empty($options['options']) && empty($states) ) $states = explode(',', $options['options']);

		$classes = false === strpos($class, ' ') ? explode(' ', $class) : array();
		$classes[] = $id;
		if ( 'auto' == $required ) {
			unset($options['required']); // prevent inputattrs from handling required=auto
			$classes[] = 'auto-required';
		}

		$options['class'] = join(' ', $classes);

		if ( 'text' == $type )
			return '<input type="text" name="' . $address . '[state]" id="' . $id . '" ' . inputattrs($options) . '/>';

		$options['disabled'] = 'disabled';
		$options['class'] = join(' ', array_merge($classes, array('disabled', 'hidden')));

		$result = '<select name="' . $address .'[state]" id="' . $id . '-menu" ' . inputattrs($options, $select_attrs) . '>' .
					'<option value="">' . $label . '</option>' .
					( ! empty($states) ? menuoptions($states, $selected, true) : '' ) .
					'</select>';

		unset($options['disabled']);

		$options['class'] = join(' ', $classes);
		$result .= '<input type="text" name="' . $address . '[state]" id="' . $id . '" ' . inputattrs($options) . '/>';

		return $result;
	}

	/**
	 * No longer used
	 *
	 * @deprecated Use shopp('storefront','account-menu') instead.
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string
	 **/
	public static function menu ( $result, $options, $O ) {
		return ShoppStorefrontThemeAPI::account_menu($result, $options, $O);
	}

	/**
	 * No longer necessary
	 *
	 * @deprecated No longer necessary
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string
	 **/
	public static function process ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();
		return $Storefront->account['request'];
	}

	/**
	 * No longer used
	 *
	 * @deprecated Use shopp('storefront','account-menuitem') instead.
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppCustomer $O       The working object
	 * @return string
	 **/
	public static function management ( $result, $options, $O ) {
		return ShoppStorefrontThemeAPI::account_menuitem($result, $options, $O);
	}

}

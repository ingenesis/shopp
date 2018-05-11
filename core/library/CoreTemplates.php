<?php
/**
 * CoreTemplates.php
 *
 * Provides core utility functions
 *
 * @copyright Ingenesis Limited, May 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Core
 * @version   1.0
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppCoreTemplates extends ShoppCoreURLTools {

	/**
	 * Locates Shopp-supported template files
	 *
	 * Uses WP locate_template() to add child-theme aware template support toggled
	 * by the theme template setting.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $template_names Array of template files to search for in priority order.
	 * @param bool $load (optional) If true the template file will be loaded if it is found.
	 * @param bool $require_once (optional) Whether to require_once or require. Default true. Has no effect if $load is false.
	 * @return string The full template file path, if one is located
	 **/
	public static function locate_template( $template_names, $load = false, $require_once = false ) {
		if ( ! is_array($template_names) )
			return '';

		$located = '';

		if ( 'off' != shopp_setting('theme_templates') ) {
			$templates = array_map(array(__CLASS__, 'template_prefix'), $template_names);
			$located = locate_template($templates);
		}

		// If a template is already located, skip the manual search
		if ( ! empty($located) )
			$template_names = array();

		foreach ( $template_names as $template_name ) {
			if ( ! $template_name )
				continue;

			$template_path = SHOPP_PATH . '/templates/' . $template_name;
			if ( ! file_exists($template_path) )
				continue;

			$located = $template_path;
			break;
		}

		if ( $load && '' != $located )
			self::load_template($located, $require_once);

		return $located;
	}

	/**
	 * Loads a template file while maintaining existing ShoppStorefront context
	 *
	 * @since 1.5
	 *
	 * @param string $template The located path to a template file
	 * @param bool $require_once (optional) Whether to require_once or require. Default false.
	 * @return void
	 **/
	public static function load_template( $template, $require_once = false ) {
		$context = ShoppStorefront::intemplate();
		ShoppStorefront::intemplate($template);
		load_template($template, $require_once);
		ShoppStorefront::intemplate($context);
	}

	/**
	 * Helper to prefix theme template file names
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $name The name of the template file
	 * @return string Prefixed template file
	 **/
	public static function template_prefix ( $name ) {
		return apply_filters('shopp_template_directory', 'shopp') . '/' . $name;
	}

	/**
	 * Returns the URI for a template file
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $name The name of the template file
	 * @return string The URL for the template file
	 **/
	public static function template_url ( $name ) {
		$themepath = get_stylesheet_directory();
		$themeuri = get_stylesheet_directory_uri();
		$builtin = SHOPP_PLUGINURI . '/templates';
		$template = rtrim(Shopp::template_prefix(''), '/');

		$path = "$themepath/$template";

		if ( 'off' != shopp_setting('theme_templates') && is_dir(sanitize_path( $path )) )
			$url = "$themeuri/$template/$name";
		else $url = "$builtin/$name";

		return sanitize_path($url);
	}


	/**
	 * Parses tag option strings or arrays
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string|array $options URL-compatible query string or associative array of tag options
	 * @return array API-ready options list
	 **/
	public static function parse_options ($options) {

		$paramset = array();
		if ( empty($options) ) return $paramset;
		if ( is_string($options) ) parse_str($options,$paramset);
		else $paramset = $options;

		$options = array();
		foreach ( array_keys($paramset) as $key )
			$options[ strtolower($key) ] = $paramset[$key];

		if ( get_magic_quotes_gpc() )
			$options = stripslashes_deep( $options );

		return $options;

	}

	/**
	 * Evaluates natural language strings to boolean equivalent
	 *
	 * Used primarily for handling boolean text provided in shopp() tag options.
	 * All values defined as true will return true, anything else is false.
	 *
	 * Boolean values will be passed through.
	 *
	 * Replaces the 1.0-1.1 value_is_true()
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $string The natural language value
	 * @param array $istrue A list strings that are true
	 * @return boolean The boolean value of the provided text
	 **/
	public static function str_true ( $string, $istrue = array('yes', 'y', 'true','1','on','open') ) {
		if (is_array($string)) return false;
		if (is_bool($string)) return $string;
		return in_array(strtolower($string),$istrue);
	}


	/**
	 * Adds JavaScript to be included in the footer on shopping pages
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $script JavaScript fragment
	 * @param boolean $global (optional) Include the script in the global namespace
	 * @return void
	 **/
	public static function add_storefrontjs ($script,$global=false) {
		$Storefront = ShoppStorefront();
		if ($Storefront === false) return;
		if ($global) {
			if (!isset($Storefront->behaviors['global'])) $Storefront->behaviors['global'] = array();
			$Storefront->behaviors['global'][] = trim($script);
		} else $Storefront->behaviors[] = $script;
	}

	/**
	 * Determines if a specified type is a valid HTML input element
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $type The HTML element type name
	 * @return boolean True if valid, false if not
	 **/
	public static function valid_input( $type ) {
		$inputs = array('text', 'hidden', 'checkbox', 'radio', 'button', 'submit');
		if ( in_array($type, $inputs) !== false )
			return true;
		return false;
	}

	/**
	 * Generates attribute markup for HTML inputs based on specified options
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $options An associative array of options
	 * @param array $allowed (optional) Allowable attribute options for the element
	 * @return string Attribute markup fragment
	 **/
	public static function inputattrs ( $options, array $allowed = array() ) {
		if ( ! is_array($options) )
			return '';

		if ( empty($allowed) )
			$allowed = array('autocomplete', 'accesskey', 'alt', 'checked', 'class', 'disabled', 'format',
				'minlength', 'maxlength', 'placeholder', 'readonly', 'required', 'size', 'src', 'tabindex', 'cols', 'rows',
				'title', 'value');

		$allowed = apply_filters( 'shopp_allowed_inputattrs', $allowed, $options );


		if ( isset($options['label']) && ! isset($options['value']) )
			$options['value'] = $options['label'];

		$attrs = array();
		$attrs = array_merge($attrs, self::inputattrs_parse($options, $allowed));

		return ' ' . join(' ', $attrs);
	}

	private static function inputattrs_parse( array $options, array $allowed ) {
		$default_callback = array(__CLASS__, 'input_attribute');

		$attrs = array('classes' => array());
		foreach ( $options as $key => $value ) {
			if ( ! in_array($key, $allowed) )
				continue;

			if ( method_exists(__CLASS__, "input_attr_$key") )
				$attrs = call_user_func(array(__CLASS__, "input_attr_$key"), $attrs, $value);
			else $attrs = call_user_func($default_callback, $attrs, $key, $value);
		}

		$classes = $attrs['classes'];
		unset($attrs['classes']);
		$attrs[] = 'class="' . join(' ', $classes). '"';

		return $attrs;
	}

	private static function input_attribute( $attrs, $key, $value ) {
		$attrs[] = $key . '="' . esc_attr($value) . '"';
		return $attrs;
	}

	private static function input_attr_class( $attrs, $value ) {
		$attrs['classes'][] = esc_attr($value);
		return $attrs;
	}

	private static function input_attr_checked( $attrs, $value ) {
		if ( Shopp::str_true($value) )
			$attrs[] = 'checked="checked"';

		return $attrs;
	}

	private static function input_attr_disabled( $attrs, $value ) {
		if ( ! Shopp::str_true($value) )
			return $attrs;

		$attrs[] = 'disabled="disabled"';
		$attrs['classes'][] = 'disabled';
		return $attrs;
	}

	private static function input_attr_readonly( $attrs, $value ) {
		if ( ! Shopp::str_true($value) )
			return $attrs;

		$attrs[] = 'readonly="readonly"';
		$attrs['classes'][] = 'readonly';
		return $attrs;
	}

	private static function input_attr_required( $attrs, $value ) {
		$attrs['classes'][] = 'required';
		return $attrs;
	}

	private static function input_attr_minlength( $attrs, $value ) {
		$attrs['classes'][] = "min" . intval($value);
		return $attrs;
	}

	private static function input_attr_format( $attrs, $value ) {
		$attrs['classes'][] = esc_attr($value);
		return $attrs;
	}

	/**
	 * Returns a list marked-up as drop-down menu options */
	/**
	 * Generates HTML markup for the options of a drop-down menu
	 *
	 * Takes a list of options and generates the option elements for an HTML
	 * select element.  By default, the option values and labels will be the
	 * same.  If the values option is set, the option values will use the
	 * key of the associative array, and the option label will be the value in
	 * the array.  The extend option can be used to ensure that if the selected
	 * value does not exist in the menu, it will be automatically added at the
	 * top of the list.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $list The list of options
	 * @param int|string $selected The array index, or key name of the selected value
	 * @param boolean $values (optional) Use the array key as the option value attribute (defaults to false)
	 * @param boolean $extend (optional) Use to add the selected value if it doesn't exist in the specified list of options
	 * @return string The markup of option elements
	 **/
	public static function menuoptions ( array $list, $selected = null, $values = false, $extend = false ) {
		if ( ! is_array($list) )
            return "";

		$_ = array();

		// Extend the options if the selected value doesn't exist
		if ( ( ! in_array($selected, $list) && ! isset($list[ $selected ])) && $extend )
			$_[] = '<option value="' . esc_attr($selected) . '">' . esc_html($selected) . '</option>';

		foreach ( $list as $value => $text ) {
			$valueattr = $selectedattr = '';
			$selection = $text;

			if ( $values ) {
				$selection = $value;
				$valueattr = ' value="' . esc_attr($value) . '"';
			}

			if ( (string)$selection === (string)$selected )
				$selectedattr = ' selected="selected"';

			if ( is_array($text) ) {
				$label = $value;
				$_[] = '<optgroup label="' . esc_attr($label) . '">';
				$_[] = self::menuoptions($text, $selected, $values);
				$_[] = '</optgroup>';
				continue;
			}

			$_[] = "<option$valueattr$selectedattr>$text</option>";
		}

		return join('', $_);
	}

	/**
	 * Sends an email message based on a specified template file
	 *
	 * Sends an e-mail message in the format of a specified e-mail
	 * template file using variable substitution for variables appearing in
	 * the template as a bracketed [variable] with data from the
	 * provided data array or the super-global $_POST array
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $template Email template file path (or a string containing the template itself)
	 * @param array $data The data to populate the template with
	 * @return boolean True on success, false on failure
	 **/
	public static function email ( $template, array $data = array() ) {

		$debug = defined('SHOPP_DEBUG_EMAIL') && SHOPP_DEBUG_EMAIL;

		$headers = array();
		$to = $subject = $message = '';
		$addrs = array('from', 'sender', 'reply-to', 'to', 'cc', 'bcc');
		$protected = array_merge($addrs, array('subject'));
		if ( false == strpos($template, "\n") && file_exists($template) ) {
			$templatefile = $template;
			// Include to parse the PHP and Theme API tags
			ob_start();
			ShoppStorefront::intemplate($templatefile);
			include $templatefile;
			ShoppStorefront::intemplate('');
			$template = ob_get_clean();
			if ( empty($template) )
				return shopp_add_error(Shopp::__('Could not open the email template because the file does not exist or is not readable.'), SHOPP_ADMIN_ERR, array('template' => $templatefile));
		}

		// Sanitize line endings
		$template = str_replace(array("\r\n", "\r"), "\n", $template);
		$lines = explode("\n", $template);

		// Collect headers
		while ( $line = array_shift($lines) ) {
			if ( false === strpos($line, ':') ) continue; // Skip invalid header lines

			list($header, $value) = explode(':', $line, 2);
			$header = strtolower($header);

			if ( in_array($header, $protected) ) // Protect against header injection
				$value = str_replace(array("\n", "\r"), '', rawurldecode($value));

			if ( in_array($header, array('to', 'subject')) )
				$headers[ $header ] = trim($value);
			else $headers[ $header ] = $line;
		}
		$message = join("\n", $lines);
		// If not already in place, setup default system email filters
		ShoppEmailDefaultFilters::init();
		// Message filters first
		$message = apply_filters('shopp_email_message', $message, $headers);

		$headers = apply_filters('shopp_email_headers', $headers, $message);
		$to = $headers['to']; unset($headers['to']);
		$subject = $headers['subject']; unset($headers['subject']);

		$sent = wp_mail($to, $subject, $message, $headers);

		do_action('shopp_email_completed');

		if ( $debug ) {
			shopp_debug("To: " . htmlspecialchars($to) . "\n");
			shopp_debug("Subject: $subject\n\n");
			shopp_debug("Headers:\n");
			shopp_debug("\nMessage:\n$message\n");
		}

		return $sent;
	}

	/**
	 * Returns a string value for use in an email's "from" header.
	 *
	 * The idea is that where multiple comma separated addresses have been provided
	 * (such as in the merchant email field) only the first of these is used.
	 * Thus, if the addressee is "Supplies Unlimited" and the addresses are
	 * "info@merchant.com, dispatch@merchant.com, partners@other.co" this method
	 * should return:
	 *
	 *     "Supplies Unlimited" <info@merchant.com>
	 *
	 * Preventing the other addresses from being exposed in the email header. NB:
	 * if no addressee is supplied we will simply get back a solitary email address
	 * without enclosing angle brackets:
	 *
	 *     info@merchant.com
	 *
	 * @see ShoppCore::email_to()
	 * @param string $addresses
	 * @param string $addressee = ''
	 * @return string
	 */
	public static function email_from ( $addresses, $addressee = '' ) {
		// If multiple addresses were provided, use only the first
		if ( false !== strpos($addresses, ',') ) {
			$addresses = explode(',', $addresses);
			$address = array_shift($addresses);
		}
		else $address = $addresses;

		// Clean up
		$address = trim($address);
		$addressee = wp_specialchars_decode( trim($addressee), ENT_QUOTES );

		// Add angle brackets/quotes where needed
		if ( empty($address) ) return $addressee;
		if ( empty($addressee) ) return $address;
		return '"' . $addressee . '" <' . $address . '>';
	}

	/**
	 * Returns a string for use in an email's "To" header.
	 *
	 * Ordinarily multiple comma separated email addresses will only be a factor
	 * where notices are being sent back to the merchant and they want to copy in
	 * other staff, partner organizations etc. Given as the $addresses:
	 *
	 *     "info@merchant.com, dispatch@merchant.com, partners@other.co"
	 *
	 * And as the $addressee:
	 *
	 *     "Supplies Unlimited"
	 *
	 * This will return:
	 *
	 *     "Supplies Unlimited" <info@merchant.com>, dispatch@merchant.com, partners@other.co"
	 *
	 * However, if there is only a single email address rather than several seperated by
	 * commas it will simply return:
	 *
	 *     "Supplies Unlimited" <info@merchant.com>"
	 *
	 * @see ShoppCore::email_from()
	 * @param $addresses
	 * @param $addressee
	 * @return string
	 */
	public static function email_to ( $addresses, $addressee = '' ) {
		$addressee = wp_specialchars_decode( trim( $addressee ), ENT_QUOTES );
		$source_list = explode( ',', $addresses );
		$addresses = array();

		foreach ( $source_list as $address ) {
			$address = trim( $address );
			if ( ! empty( $address ) ) $addresses[] = $address;
		}

		if ( isset($addresses[0]) && ! empty( $addressee ) )
			$addresses[0] = '"' . $addressee . '" <' . $addresses[0] . '>';

		return join(',', $addresses);
	}

}
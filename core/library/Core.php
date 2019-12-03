<?php
/**
 * Core.php
 *
 * Namespaced utility library for Shopp
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, June 2013
 * @package shopp
 * @subpackage shopplib
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides a library of utility functions
 *
 * To call, use Shopp::{static_method_name}()
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopplib
 **/
abstract class ShoppCore {

	/**
	 * Detects if Shopp is unsupported in the current hosting environment
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean True if requirements are missing, false if no errors were detected
	 **/
	public static function unsupported () {
		if ( defined('SHOPP_UNSUPPORTED') ) return SHOPP_UNSUPPORTED;
		$activation = false;
		if ( isset($_GET['action']) && isset($_GET['plugin']) ) {
			$activation = ('activate' == $_GET['action']);
			if ($activation) {
				$plugin = $_GET['plugin'];
				if (function_exists('check_admin_referer'))
					check_admin_referer('activate-plugin_' . $plugin);
			}
		}

		$errors = array();

		// Check PHP version
		if ( version_compare(PHP_VERSION, '5.2.4', '<') ) array_push($errors, 'phpversion', 'php524');

		// Check WordPress version
		if ( version_compare(get_bloginfo('version'), '3.5', '<') )
			array_push($errors, 'wpversion', 'wp35');

		// Check for GD
		if ( ! function_exists('gd_info') ) $errors[] = 'gdsupport';
		elseif ( ! array_keys( gd_info(), array('JPG Support', 'JPEG Support')) ) $errors[] = 'jpgsupport';

		if ( empty($errors) ) {
			if ( ! defined('SHOPP_UNSUPPORTED') ) define('SHOPP_UNSUPPORTED', false);
			return false;
		}

		$plugin_path = dirname(__FILE__);
		// Manually load text domain for translated activation errors
		$languages_path = str_replace('\\', '/', $plugin_path.'/lang');
		load_plugin_textdomain('Shopp', false, $languages_path);

		// Define translated messages
		$_ = array(
			'header'	    => Shopp::_x('Shopp Activation Error', 'Shopp activation error'),
			'intro'	        => Shopp::_x('Sorry! Shopp cannot be activated for this WordPress install.', 'Shopp activation error'),
			'phpversion'	=> Shopp::_x(Shopp::__('Your server is running PHP %s!', PHP_VERSION), 'Shopp activation error'),
			'php524'	    => Shopp::_x('Shopp requires PHP 5.2.4+.', 'Shopp activation error'),
			'wpversion'	    => Shopp::_x(Shopp::__('This site is running WordPress %s!', get_bloginfo('version')), 'Shopp activation error'),
			'wp35'	        => Shopp::_x('Shopp requires WordPress 3.5.', 'Shopp activation error'),
			'gdsupport'	    => Shopp::_x('Your server does not have GD support! Shopp requires the GD image library with JPEG support for generating gallery and thumbnail images.', 'Shopp activation error'),
			'jpgsupport'	=> Shopp::_x('Your server does not have JPEG support for the GD library! Shopp requires JPEG support in the GD image library to generate JPEG images.', 'Shopp activation error'),
			'nextstep'	    => Shopp::_x(Shopp::__('Try contacting your web hosting provider or server administrator to upgrade your server. For more information about the requirements for running Shopp, see the %sShopp Documentation%s', '<a href="' . ShoppSupport::DOCS . 'system-requirements">', '</a>'), 'Shopp activation error'),
			'continue'	    => Shopp::_x('Return to Plugins page', 'Shopp activation error')
		);

		if ( $activation ) {
			$string = '<h1>'.$_['header'].'</h1><p>'.$_['intro'].'</h1></p><ul>';
			foreach ( (array)$errors as $error ) if ( isset($_[ $error ]) ) $string .= "<li>{$_[$error]}</li>";
			$string .= '</ul><p>' . $_['nextstep'] . '</p><p><a class="button" href="' . admin_url('plugins.php') . '">&larr; ' . $_['continue'] . '</a></p>';
			wp_die($string);
		}

		if ( ! function_exists('deactivate_plugins') )
			require( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugin = basename($plugin_path).__FILE__;
		deactivate_plugins($plugin, true);

		$phperror = '';
		if ( is_array($errors) && ! empty($errors) ) {
			foreach ( $errors as $error ) {
				if ( isset($_[$error]) )
					$phperror .= $_[$error].' ';
				trigger_error($phperror, E_USER_WARNING);
			}
		}

		if ( ! defined('SHOPP_UNSUPPORTED') )
			define('SHOPP_UNSUPPORTED', true);

		return true;
	}

	/**
	 * Detect if the Shopp installation needs maintenance
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public static function maintenance () {
		return ( self::upgradedb() || shopp_setting_enabled('maintenance') );
	}

	/**
	 * Detect if a database schema upgrade is required
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean
	 **/
	public static function upgradedb () {
		return ( ! ShoppSettings()->available() || ShoppSettings()->dbversion() != ShoppVersion::db() );
	}

	/**
	 * Shopp wrapper for gettext translation strings (with optional context and Markdown support)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function translate ( $text, $context = null ) {
		$domain = 'Shopp';

		// Fix edge case where directly loaded image server throws fatal
		// error because WP functions are not available. Remove after mu-plugin.
		if ( ! function_exists('translate') ) return $text;

		if ( is_null($context) ) $string = translate( $text, $domain );
		else $string = translate_with_gettext_context($text, $context, $domain);

		return $string;
	}

	/**
	 * Shopp wrapper to return gettext translation strings
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated text
	 **/
	public static function __ () {
		$args = func_get_args(); // Handle sprintf rendering
		$text = array_shift($args);
		$translated = Shopp::translate($text);
		return vsprintf($translated, $args);
	}

	/**
	 * Shopp wrapper to output gettext translation strings
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated text
	 **/
	public static function _e () {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '__'), $args);
	}

	/**
	 * Shopp wrapper to return gettext translation strings with context support
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _x () {
		$args = func_get_args();
		$text = array_shift($args);
		$context = array_shift($args);
		$translated = Shopp::translate($text, $context);

		if ( 0 == count($args) ) return $translated;
		else return vsprintf($translated, $args);
	}

	/**
	 * Get translated Markdown rendered HTML
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated Markdown-rendered HTML text
	 **/
	public static function _m () {
		$args = func_get_args();
		$translated = call_user_func_array(array(__CLASS__, '__'), $args);
		if ( false === $translated ) return '';

		return new MarkdownText($translated);
	}

	/**
	 * Output translated Markdown rendered HTML
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return void
	 **/
	public static function _em ( $text ) {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '_m'), $args);
	}

	/**
	 * Get translated inline-Markdown rendered HTML (use for single-line Markdown)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated Markdown-rendered HTML text
	 **/
	public static function _mi () {
		$args = func_get_args();
		$markdown = call_user_func_array(array(__CLASS__, '_m'), $args);
		return str_replace(array('<p>', '</p>'), '', $markdown);
	}

	/**
	 * Output translated Markdown rendered HTML with translator context
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _emi ( $text ) {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '_mi'), $args);
	}

	/**
	 * Get translated Markdown rendered HTML with translator context
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _mx ( $text, $context ) {
		$args = func_get_args();
		$translated = call_user_func_array(array(__CLASS__, '_x'), $args);
		if ( false === $translated ) return '';

		return new MarkdownText($translated);
	}

	/**
	 * Output translated Markdown rendered HTML with translator context
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _emx ( $text, $context ) {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '_mx'), $args);
	}

	public static function esc_attr__ ( $text ) {
		$args = func_get_args();
		return esc_attr(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function esc_attr_e ( $text ) {
		$args = func_get_args();
		echo esc_attr(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function esc_html__ ( $text ) {
		$args = func_get_args();
		return esc_html(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function esc_html_e ( $text ) {
		$args = func_get_args();
		echo esc_html(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	/**
	 * Converts timestamps to formatted localized date/time strings
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $format A date() format string
	 * @param int $timestamp (optional) The timestamp to be formatted (defaults to current timestamp)
	 * @return string The formatted localized date/time
	 **/
	public static function _d ( $format, $timestamp = null ) {

		$tokens = array(
			'D' => array('Mon' => Shopp::__('Mon'), 'Tue' => Shopp::__('Tue'),
						'Wed' => Shopp::__('Wed'), 'Thu' => Shopp::__('Thu'),
						'Fri' => Shopp::__('Fri'), 'Sat' => Shopp::__('Sat'),
						'Sun' => Shopp::__('Sun')),
			'l' => array('Monday' => Shopp::__('Monday'), 'Tuesday' => Shopp::__('Tuesday'),
						'Wednesday' => Shopp::__('Wednesday'), 'Thursday' => Shopp::__('Thursday'),
						'Friday' => Shopp::__('Friday'), 'Saturday' => Shopp::__('Saturday'),
						'Sunday' => Shopp::__('Sunday')),
			'F' => array('January' => Shopp::__('January'), 'February' => Shopp::__('February'),
						'March' => Shopp::__('March'), 'April' => Shopp::__('April'),
						'May' => Shopp::__('May'), 'June' => Shopp::__('June'),
						'July' => Shopp::__('July'), 'August' => Shopp::__('August'),
						'September' => Shopp::__('September'), 'October' => Shopp::__('October'),
						'November' => Shopp::__('November'), 'December' => Shopp::__('December')),
			'M' => array('Jan' => Shopp::__('Jan'), 'Feb' => Shopp::__('Feb'),
						'Mar' => Shopp::__('Mar'), 'Apr' => Shopp::__('Apr'),
						'May' => Shopp::__('May'), 'Jun' => Shopp::__('Jun'),
						'Jul' => Shopp::__('Jul'), 'Aug' => Shopp::__('Aug'),
						'Sep' => Shopp::__('Sep'), 'Oct' => Shopp::__('Oct'),
						'Nov' => Shopp::__('Nov'), 'Dec' => Shopp::__('Dec'))
		);

		if ( is_null($timestamp) ) $date = date($format);
		else $date = date($format, $timestamp);

		foreach ($tokens as $token => $strings) {
			if ( $pos = strpos($format, $token) === false) continue;
			$string = ! $timestamp ? date($token) : date($token, $timestamp);
			$date = str_replace($string, $strings[ $string ], $date);
		}

		return $date;
	}

	/**
	 * JavaScript encodes translation strings
	 *
	 * @author Jonathan Davis
	 * @since 1.1.7
	 *
	 * @param string $text Text to translate
	 * @return void
	 **/
	public static function _jse ( $text) {
		echo json_encode(Shopp::translate($text));
	}

	/**
	 * Generates a representation of the current state of an object structure
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param object $object The object to display
	 * @return string The object structure
	 **/
	public static function object_r ( $object ) {
		$Shopp = Shopp::object();
		ob_start();
		print_r($object);
		$result = ob_get_clean();
		return $result;
	}

	/**
	 * _var_dump
	 *
	 * like _object_r, but in var_dump format.  Useful when you need to know both object and scalar types.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return string var_dump output
	 **/
	public static function var_dump() {
		$args = func_get_args();
		ob_start();
		var_dump($args);
		$ret_val = ob_get_contents();
		ob_end_clean();
		return $ret_val;
	}

	/**
	 * Appends a string to the end of URL as a query string
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $string The string to add
	 * @param string $url The url to append to
	 * @return string
	 **/
	public static function add_query_string ($string,$url) {
		if(strpos($url,'?') !== false) return "$url&$string";
		else return "$url?$string";
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
	 * Filters associative array with a mask array of keys to keep
	 *
	 * Compares the keys of the associative array to values in the mask array and
	 * keeps only the elements of the array that exist as a value of the mask array.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $array The array to filter
	 * @param array $mask A list of keys to keep
	 * @return array The filtered array
	 **/
	public static function array_filter_keys ($array,$mask) {
		if ( !is_array($array) ) return $array;

		foreach ($array as $key => $value)
			if ( !in_array($key,$mask) ) unset($array[$key]);

		return $array;
	}

	/**
	 * Automatically generates a list of number ranges distributed across a number set
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $avg Mean average number in the distribution
	 * @param int $max The max number in the distribution
	 * @param int $min The minimum in the distribution
	 * @return array A list of number ranges
	 **/
	public static function auto_ranges ($avg, $max, $min, $values) {

		$ranges = array();
		if ($avg == 0 || $max == 0) return $ranges;
		$power = floor(log10($avg));
		$scale = pow(10,$power);
		$mean = round($avg/$scale)*$scale;
		$range = $max-$min;

		if ($range == 0) return $ranges;

		$steps = $values;
		if ($steps > 7) $steps = 7;
		elseif ($steps < 2) {
			$scale = $scale/2;
			$steps = ceil($range/$scale);
			if ($steps > 7) $steps = 7;
			elseif ($steps < 2) $steps = 2;
		}

		$base = max($mean-($scale*floor(($steps-1)/2)),$scale);

		for ($i = 0; $i < $steps; $i++) {
			$range = array("min" => 0,"max" => 0);
			if ($i == 0) $range['max'] = $base;
			else if ($i+1 >= $steps) $range['min'] = $base;
			else $range = array("min" => $base, "max" => $base+$scale);
			$ranges[] = $range;
			if ($i > 0) $base += $scale;
		}

		return $ranges;
	}

	/**
	 * Converts weight units from base setting to needed unit value
	 *
	 * @author John Dillick, Jonathan Davis
	 * @since 1.1
	 * @version 1.1
	 *
	 * @param float $value The value that needs converted
	 * @param string $unit The unit that we are converting to
	 * @param string $from (optional) The unit that we are converting from - defaults to system settings
	 * @return float|boolean The converted value, false on error
	 **/
	public static function convert_unit ($value = 0, $unit, $from=false) {
		if ($unit == $from || $value == 0) return $value;

		$defaults = array(
			'mass' => shopp_setting('weight_unit'),
			'dimension' => shopp_setting('dimension_unit')
		);

		// Conversion table to International System of Units (SI)
		$table = array(
			'mass' => array(		// SI base unit "grams"
				'lb' => 453.59237, 'oz' => 28.349523125, 'g' => 1, 'kg' => 1000
			),
			'dimension' => array(	// SI base unit "meters"
				'ft' => 0.3048, 'in' => 0.0254, 'mm' => 0.001, 'cm' => 0.01, 'm' => 1
			)
		);

		if ( $from && in_array( $from, array_keys($table['mass']) ) ) $defaults['mass'] = $from;
		if ( $from && in_array( $from, array_keys($table['dimension']) ) ) $defaults['dimension'] = $from;

		$table = apply_filters('shopp_unit_conversion_table',$table);

		// Determine which chart to use
		foreach ($table as $attr => $c) {
			if (isset($c[$unit])) { $chart = $attr; $from = $defaults[$chart]; break; }
		}

		if ($unit == $from) return $value;

		// If we don't know about unit, return 0.
		if ( ! isset($table[$chart][$from]) ) return 0;
		if ( ! isset($table[$chart][$unit]) ) return 0;

		$siv = $value * $table[$chart][$from];	// Convert to SI unit value
		return $siv/$table[$chart][$unit];		// Return target units
	}

	/**
	 * Copies the builtin template files to the active WordPress theme
	 *
	 * Handles copying the builting template files to the shopp/ directory of
	 * the currently active WordPress theme.  Strips out the header comment
	 * block which includes a warning about editing the builtin templates.
	 *
	 * @author Jonathan Davis, John Dillick
	 * @since 1.0
	 *
	 * @param string $src The source directory for the builtin template files
	 * @param string $target The target directory in the active theme
	 * @return void
	 **/
	public static function copy_templates ( $src, $target ) {
		$builtin = array_filter(scandir($src), "filter_dotfiles");
		foreach ( $builtin as $template ) {
			$target_file = $target.'/'.$template;
			if ( ! file_exists($target_file) ) {
				$src_file = file_get_contents($src . '/' . $template);
				$file = fopen($target_file, 'w');
				$src_file = preg_replace('/^<\?php\s\/\*\*\s+(.*?\s)*?\*\*\/\s\?>\s/', '', $src_file); // strip warning comments

				fwrite($file, $src_file);
				fclose($file);
				chmod($target_file, 0666);
			}
		}
	}

	/**
	 * Calculates a cyclic redundancy checksum polynomial of 16-bit lengths of the data
	 *
	 * @author Ashley Roll {@link ash@digitalnemesis.com}, Scott Dattalo
	 * @since 1.1
	 *
	 * @return int The checksum polynomial
	 **/
	public static function crc16 ($data) {
		$crc = 0xFFFF;
		for ($i = 0; $i < strlen($data); $i++) {
			$x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
			$x ^= $x >> 4;
			$crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
		}
		return $crc;
	}

	/**
	 * Provides a data-uri scheme transparent PNG image for embedding in CSS or HTML <img> tags
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string Clear PNG data-URI
	 **/
	public static function clearpng () {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA1BMVEX///+nxBvIAAAAAXRSTlMAQObYZgAAABRJREFUeF6VwIEAAAAAgKD9qWeo0AAwAAEnvySkAAAAAElFTkSuQmCC';
	}

	/**
	 * remove_class_actions
	 *
	 * Removes all WordPress actions/filters registered by a particular class or its children.
	 *
	 * @author John Dillick
	 * @since 1.1.4.1
	 *
	 * @param array/string $tags the action/filter name(s) to be removed
	 * @param string $class the classname of the objects you wish to remove actions from
	 * @param int $priority
	 * @return void
	 **/
	public static function remove_class_actions ( $tags = false, $class = 'stdClass', $priority = false ) {
		global $wp_filter;

		// action tags are required
		if ( false === $tags ) { return; }

		foreach ( (array) $tags as $tag) {
			if ( ! isset($wp_filter[$tag]) ) continue;

			foreach ( $wp_filter[$tag] as $pri_index => $callbacks ) {
				if ( $priority !== $pri_index && false !== $priority ) { continue; }
				foreach( $callbacks as $idx => $callback ) {
					if ( $tag == $idx ) continue; // idx will be the same as tag for non-object function callbacks

					if ( $callback['function'][0] instanceof $class ) {
						remove_filter($tag,$callback['function'], $pri_index, $callback['accepted_args']);
					}
				}
			}
		}
		return;
	}

	/**
	 * Determines the currency format to use
	 *
	 * Uses the locale-based currency format (set by the Base of Operations setting)
	 * as a base format. If one is not set, a default format of $#,###.## is used. If
	 * a $format is provided, it will be merged with the base format overriding any
	 * specific settings made while keeping the settings from the base format that are
	 * not specified.
	 *
	 * The currency format settings consist of a named array with the following:
	 * cpos 		boolean	The position of the currency symbol: true to prefix the number, false for suffix
	 * currency		string	The currency symbol
	 * precision	int		The decimal precision
	 * decimals		string	The decimal delimiter
	 * thousands	string	The thousands separator
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @param array $format (optional) A currency format settings array
	 * @return array Format settings array
	 **/
	public static function currency_format ( array $format = array() ) {

		$default = array(
			'cpos' => true,
			'currency' => '$',
			'precision' => 2,
			'decimals' => '.',
			'thousands' => ',',
			'grouping' => 3
		);

		// Merge base of operations locale settings
		$locale = shopp_setting('base_operations');
		if ( ! empty($locale['currency']) && ! empty($locale['currency']['format']) )
			$default = array_merge($default, $locale['currency']['format']);

		// No format provided, use default
		if ( empty($format) ) return $default;

		// Merge the format options with the default
		return array_merge($default, $format);

	}

	/**
	 * Calculates the timestamp of a day based on a repeating interval (Fourth Thursday in November (Thanksgiving))
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int|string $week The week of the month (1-4, -1 or first-fourth, last)
	 * @param int|string $dayOfWeek The day of the week (0-6 or Sunday-Saturday)
	 * @param int $month The month, uses current month if none provided
	 * @param int $year The year, uses current year if none provided
	 * @return void
	 **/
	public static function datecalc($week=-1,$dayOfWeek=-1,$month=-1,$year=-1) {
		$weekdays = array("sunday" => 0, "monday" => 1, "tuesday" => 2, "wednesday" => 3, "thursday" => 4, "friday" => 5, "saturday" => 6);
		$weeks = array("first" => 1, "second" => 2, "third" => 3, "fourth" => 4, "last" => -1);

		if ($month == -1) $month = date ("n");	// No month provided, use current month
		if ($year == -1) $year = date("Y");   	// No year provided, use current year

		// Day of week is a string, look it up in the weekdays list
		if (!is_numeric($dayOfWeek)) {
			foreach ($weekdays as $dayName => $dayNum) {
				if (strtolower($dayOfWeek) == substr($dayName,0,strlen($dayOfWeek))) {
					$dayOfWeek = $dayNum;
					break;
				}
			}
		}
		if ($dayOfWeek < 0 || $dayOfWeek > 6) return false;

		if (!is_numeric($week)) $week = $weeks[$week];

		if ($week == -1) {
			$lastday = date("t", mktime(0,0,0,$month,1,$year));
			$tmp = (date("w",mktime(0,0,0,$month,$lastday,$year)) - $dayOfWeek) % 7;
			if ($tmp < 0) $tmp += 7;
			$day = $lastday - $tmp;
		} else {
			$tmp = ($dayOfWeek - date("w",mktime(0,0,0,$month,1,$year))) % 7;
			if ($tmp < 0) $tmp += 7;
			$day = (7 * $week) - 6 + $tmp;
		}

		return mktime(0,0,0,$month,$day,$year);
	}

	/**
	 * Builds an array of the current WP date_format setting
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.1
	 *
	 * @param boolean $fields Ensure all date elements are present for field order (+1.1.6)
	 * @return array The list version of date_format
	 **/
	public static function date_format_order ($fields=false) {
		$format = get_option('date_format');

		$default = array('month' => 'F','day' => 'j','year' => 'Y');

		$tokens = array(
			'day' => 'dDjl',
			'month' => 'FmMn',
			'year' => 'yY'
		);

		$dt = join('',$tokens);
		$_ = array(); $s = 0;
		preg_match_all("/(.{1})/",$format,$matches);
		foreach ($matches[1] as $i => $token) {
			foreach ($tokens as $type => $pattern) {
				if (preg_match("/[$pattern]/",$token)) {
					$_[$type] = $token;
					break;
				} elseif (preg_match("/[^$dt]/",$token)) {
					$_['s'.$s++] = $token;
					break;
				}
			}
		}

		if ($fields) $_ = array_merge($_,$default,$_);

		return $_;
	}

	public static function debug_caller () {
		$backtrace  = debug_backtrace();
		$stack = array();

		foreach ( $backtrace as $caller ) {
			if ( 'debug_caller' == $caller['function'] ) continue;
			$stack[] = isset( $caller['class'] ) ?
				"{$caller['class']}->{$caller['function']}"
				: $caller['function'];
		}

		return join( ', ', $stack );

	}

	/**
	 * Outputs debug structures to the browser console.
	 *
	 * @since 1.3.9
	 *
	 * @param mixed $data The data to display in the console.
	 * @return void
	 **/
	public static function debug ( $data ) {

		$backtrace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

		list($debugcall, $callby, ) = $backtrace;

		$stack = array();
		foreach ( $backtrace as $id => $call ) {
			if ( 'debug' == $caller['function'] ) continue;
			$ref = empty($call['file']) ? 'Call #' . $id : basename($call['file']) . ' @ '. $call['line'];

			$stack[ $ref ] = isset( $call['class'] ) ?
				$call['class'] . $call['type'] . $call['function'] . "()"
				: $call['function'];
		}
		$callstack = (object) $stack;

		$caller = ( empty($callby['class']) ? '' : $callby['class'] . $callby['type'] ) . $callby['function'] . '() from ' . $debugcall['file'] . ' @ ' . $debugcall['line'];

		shopp_custom_script('shopp', "
			console.group('Debug " . $caller . "');
			console.debug(" . json_encode($data) . ");
			console.log('Call stack: %O', " . json_encode($stack) . ");
			console.groupEnd();
		");
	}

	/**
	 * Returns the duration (in days) between two timestamps
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $start The starting timestamp
	 * @param int $end The ending timestamp
	 * @return int	Number of days between the start and end
	 **/
	public static function duration ($start,$end) {
		return ceil(($end - $start) / 86400);
	}

	/**
	 * Escapes nested data structure values for safe output to the browser
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param mixed $value The data to escape
	 * @return mixed
	 **/
	public static function esc_attrs ($value) {
		 $value = is_array($value)?array_map('esc_attrs', $value):esc_attr($value);
		 return $value;
	}

	/**
	 * Callback to filter out files beginning with a dot
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $name The filename to check
	 * @return boolean
	 **/
	public static function filter_dotfiles ($name) {
		return (substr($name,0,1) != ".");
	}

	/**
	 * Find a target file starting at a given directory
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @param string $filename The target file to find
	 * @param string $directory The starting directory
	 * @param string $root The original starting directory
	 * @param array $found Result array that matching files are added to
	 * @deprecated 1.3 please use filefind() instead
	 **/
	public static function find_filepath ($filename, $directory, $root, &$found) {
		if (is_dir($directory)) {
			$Directory = @dir($directory);
			if ($Directory) {
				while (( $file = $Directory->read() ) !== false) {
					if (substr($file,0,1) == "." || substr($file,0,1) == "_") continue;				// Ignore .dot files and _directories
					if (is_dir($directory.'/'.$file) && $directory == $root)		// Scan one deep more than root
						self::find_filepath($filename,$directory.'/'.$file,$root, $found);	// but avoid recursive scans
					elseif ($file == $filename)
						$found[] = substr($directory,strlen($root)).'/'.$file;		// Add the file to the found list
				}
				return true;
			}
		}
		return false;
	}

	public static function findfile ( $filename, $directory, array &$matches = array(), $greedy = true ) {
		if ( ! is_dir($directory) ) return false;

		try {
			foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator($directory) ) as $file ) {
				if ( $file->getFilename() === $filename ) {
					$matches[] = $file->getPathname();
					if ( ! $greedy ) break;
				}
			}
		}
		catch (Exception $e) {}

		return ( 1 <= count($matches) );
	}

	/**
	 * Determines the mimetype of a file
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $file The path to the file
	 * @param string $name (optional) The name of the file
	 * @return string The mimetype of the file
	 **/
	public static function file_mimetype ($file,$name=false) {
		if (!$name) $name = basename($file);
		if (file_exists($file)) {
			if (function_exists('finfo_open')) {
				// Try using PECL module
				$f = finfo_open(FILEINFO_MIME);
				list($mime,$charset) = explode(";",finfo_file($f, $file));
				finfo_close($f);
				shopp_debug('File mimetype detection (finfo_open): ' . $mime);
				if (!empty($mime)) return $mime;
			} elseif (class_exists('finfo')) {
				// Or class
				$f = new finfo(FILEINFO_MIME);
				shopp_debug('File mimetype detection (finfo class): ' . $f->file($file));
				return $f->file($file);
			} elseif (function_exists('mime_content_type') && $mime = mime_content_type($file)) {
				// Try with magic-mime if available
				shopp_debug('File mimetype detection (mime_content_type()): ' . $mime);
				return $mime;
			}
		}

		if (!preg_match('/\.([a-z0-9]{2,4})$/i', $name, $extension)) return false;

		switch (strtolower($extension[1])) {
			// misc files
			case 'txt':	return 'text/plain';
			case 'htm': case 'html': case 'php': return 'text/html';
			case 'css': return 'text/css';
			case 'js': return 'application/javascript';
			case 'json': return 'application/json';
			case 'xml': return 'application/xml';

			// images
			case 'jpg': case 'jpeg': case 'jpe': return 'image/jpg';
			case 'png': case 'gif': case 'bmp': case 'tiff': return 'image/'.strtolower($extension[1]);
			case 'tif': return 'image/tif';
			case 'svg': case 'svgz': return 'image/svg+xml';

			// archives
			case 'zip':	return 'application/zip';
			case 'rar':	return 'application/x-rar-compressed';
			case 'exe':	case 'msi':	return 'application/x-msdownload';
			case 'tar':	return 'application/x-tar';
			case 'cab': return 'application/vnd.ms-cab-compressed';

			// audio/video
			case 'flv':	return 'video/x-flv';
			case 'mpeg': case 'mpg':	case 'mpe': return 'video/mpeg';
			case 'mp4s': return 'application/mp4';
			case 'm4a': return 'audio/mp4';
			case 'mp3': return 'audio/mpeg3';
			case 'wav':	return 'audio/wav';
			case 'aiff': case 'aif': return 'audio/aiff';
			case 'avi':	return 'video/msvideo';
			case 'wmv':	return 'video/x-ms-wmv';
			case 'mov':	case 'qt': return 'video/quicktime';

			// ms office
			case 'doc':	case 'docx': return 'application/msword';
			case 'xls':	case 'xlt':	case 'xlm':	case 'xld':	case 'xla':	case 'xlc':	case 'xlw':	case 'xll':	return 'application/vnd.ms-excel';
			case 'ppt':	case 'pps':	return 'application/vnd.ms-powerpoint';
			case 'rtf':	return 'application/rtf';

			// adobe
			case 'pdf':	return 'application/pdf';
			case 'psd': return 'image/vnd.adobe.photoshop';
		    case 'ai': case 'eps': case 'ps': return 'application/postscript';

			// open office
		    case 'odt': return 'application/vnd.oasis.opendocument.text';
		    case 'ods': return 'application/vnd.oasis.opendocument.spreadsheet';
		}

		return false;
	}

	/**
	 * Converts a numeric string to a floating point number
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $value Numeric string to be converted
	 * @param boolean $round (optional) Whether to round the value (default true for to round)
	 * @param array $format (optional) The currency format to use for precision (defaults to the current base of operations)
	 * @return float
	 **/
	public static function floatval ( $value, $round = true, array $format = array() ) {
		$format = ShoppCore::currency_format($format); // Use ShoppCore here instead of Shopp here
		extract($format, EXTR_SKIP);

		$float = false;
		if ( is_float($value) ) $float = $value;

		$value = str_replace($currency, '', $value); // Strip the currency symbol

		if ( ! empty($thousands) )
			$value = str_replace($thousands, '', $value); // Remove thousands

		$value = preg_replace('/[^\d\,\.\·\'\-]/', '', $value); // Remove any non-numeric string data

		// If we have full-stop decimals, try casting it to skip the funky stuff
		if ( '.' == $decimals && (float)$value > 0 ) $float = (float)$value;

		if ( false === $float ) { // Nothing else worked, time to get down and dirty
			$value = preg_replace('/^\./', '', $value); // Remove any decimals at the beginning of the string

			if ( $precision > 0 ) // Don't convert decimals if not required
				$value = preg_replace('/\\'.$decimals.'/', '.', $value); // Convert decimal delimter

			$float = (float)$value;
		}

		return $round ? round($float, $precision) : $float;
	}

	/**
	 * Modifies URLs to use SSL connections
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $url Source URL to rewrite
	 * @return string $url The secure URL
	 **/
	public static function force_ssl ($url,$rewrite=false) {
		if(is_ssl() || $rewrite)
			$url = str_replace('http://', 'https://', $url);
		return $url;
	}


	/**
	 * Determines the gateway path to a gateway file
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $file The target gateway file
	 * @return string The path fragment for the gateway file
	 **/
	public static function gateway_path ($file) {
		return basename(dirname($file)).'/'.basename($file);
	}

	/**
	 * Returns readable php.ini data size settings
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $name The name of the setting to read
	 * @return string The readable config size
	 **/
	public static function ini_size ($name) {
		$setting = ini_get($name);
		if (preg_match('/\d+\w+/',$setting) !== false) return $setting;
		else Shopp::readableFileSize($setting);
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

		if ( ! is_array($options) ) return '';
		if ( empty($allowed) ) {
			$allowed = array('autocomplete','accesskey','alt','checked','class','disabled','format',
				'minlength','maxlength','placeholder','readonly','required','size','src','tabindex','cols','rows',
				'title','value');
		}
		$allowed = apply_filters( 'shopp_allowed_inputattrs', $allowed, $options );
		$string = "";
		$classes = "";

		if ( isset($options['label']) && !isset($options['value']) ) $options['value'] = $options['label'];
		foreach ( $options as $key => $value ) {
			if ( ! in_array($key, $allowed) ) continue;
			switch($key) {
				case "class": $classes .= " $value"; break;
				case "checked":
					if (Shopp::str_true($value)) $string .= ' checked="checked"';
					break;
				case "disabled":
					if (Shopp::str_true($value)) {
						$classes .= " disabled";
						$string .= ' disabled="disabled"';
					}
					break;
				case "readonly":
					if (Shopp::str_true($value)) {
						$classes .= " readonly";
						$string .= ' readonly="readonly"';
					}
					break;
				case "required": if (Shopp::str_true($value)) $classes .= " required"; break;
				case "minlength": $classes .= " min$value"; break;
				case "format": $classes .= " $value"; break;
				default:
					$string .= ' '.$key.'="'.esc_attr($value).'"';
			}
		}
		if ( ! empty($classes) ) $string .= ' class="' . esc_attr(trim($classes)) . '"';
	 	return $string;
	}

	/**
	 * Determines if the current client is a known web crawler bot
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean Returns true if a bot user agent is detected
	 **/
	public static function is_robot() {
		$bots = array('Googlebot', 'TeomaAgent', 'Zyborg', 'Gulliver', 'Architext spider', 'FAST-WebCrawler', 'Slurp', 'Ask Jeeves', 'ia_archiver', 'Scooter', 'Mercator', 'crawler@fast', 'Crawler', 'InfoSeek sidewinder', 'Lycos_Spider_(T-Rex)', 'Fluffy the Spider', 'Ultraseek', 'MantraAgent', 'Moget', 'MuscatFerret', 'VoilaBot', 'Sleek Spider', 'KIT_Fireball', 'WebCrawler');
		if ( ! isset($_SERVER['HTTP_USER_AGENT']) ) return apply_filters('shopp_agent_is_robot', true, '');
		foreach ( $bots as $bot )
			if ( false !== strpos(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower($bot))) return apply_filters('shopp_agent_is_robot', true, esc_attr($_SERVER['HTTP_USER_AGENT']));
		return apply_filters('shopp_agent_is_robot', false, esc_attr($_SERVER['HTTP_USER_AGENT']));
	}

	/**
	 * Encodes an all parts of a URL
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $url The URL of the link to encode
	 * @return void
	 **/
	public static function linkencode ($url) {
		$search = array('%2F','%3A','%3F','%3D','%26');
		$replace = array('/',':','?','=','&');
		$url = rawurlencode($url);
		return str_replace($search, $replace, $url);
	}

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
	public static function locate_template ($template_names, $load = false, $require_once = false ) {
		if ( ! is_array($template_names) ) return '';

		$located = '';

		if ('off' != shopp_setting('theme_templates')) {
			$templates = array_map('shopp_template_prefix',$template_names);
			$located = locate_template($templates,false);
		}

		if ('' == $located) {
			foreach ( $template_names as $template_name ) {
				if ( ! $template_name ) continue;

				if ( file_exists(SHOPP_PATH . '/templates/' . $template_name)) {
					$located = SHOPP_PATH . '/templates/' . $template_name;
					break;
				}

			}
		}

		if ( $load && '' != $located ) {
			$context = ShoppStorefront::intemplate();
			ShoppStorefront::intemplate($located);
			load_template( $located, $require_once );
			ShoppStorefront::intemplate($context);
		}

		return $located;
	}

	/**
	 * Generates a timestamp from a MySQL datetime format
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $datetime A MySQL date time string
	 * @return int A timestamp number usable by PHP date functions
	 **/
	public static function mktimestamp ($datetime) {
		$h = $mn = $s = 0;
		list($Y, $M, $D, $h, $mn, $s) = sscanf($datetime,"%d-%d-%d %d:%d:%d");
		if (max($Y, $M, $D, $h, $mn, $s) == 0) return 0;
		return mktime($h, $mn, $s, $M, $D, $Y);
	}

	/**
	 * Converts a timestamp number to an SQL datetime formatted string
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $timestamp A timestamp number
	 * @return string An SQL datetime formatted string
	 **/
	public static function mkdatetime ($timestamp) {
		return date("Y-m-d H:i:s",$timestamp);
	}

	/**
	 * Returns the 24-hour equivalent of a the Ante Meridiem or Post Meridem hour
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $hour The hour of the meridiem
	 * @param string $meridiem Specified meridiem of "AM" or "PM"
	 * @return int The 24-hour equivalent
	 **/
	public static function mk24hour ($hour, $meridiem) {
		if ($hour < 12 && $meridiem == "PM") return $hour + 12;
		if ($hour == 12 && $meridiem == "AM") return 0;
		return (int) $hour;
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
	public static function menuoptions ( $list, $selected = null, $values = false, $extend = false ) {
		if ( ! is_array($list) ) return "";

		$_ = array();
		// Extend the options if the selected value doesn't exist
		if ( ( ! in_array($selected, $list) && ! isset($list[ $selected ])) && $extend )
			$_[] = '<option value="' . esc_attr($selected) . '">' . esc_html($selected) . '</option>';

		foreach ( $list as $value => $text ) {

			$valueattr = $selectedattr = '';

			if ( $values ) $valueattr = ' value="' . esc_attr($value) . '"';

			if ( ($values && (string)$value === (string)$selected)
				|| (! $values && (string)$text === (string)$selected) )
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
		return join('',$_);
	}

	/**
	 * Formats a number amount using a specified currency format
	 *
	 * The number is formatted based on a currency formatting configuration
	 * array that  includes the currency symbol position (cpos), the currency
	 * symbol (currency), the decimal precision (precision), the decimal character
	 * to use (decimals) and the thousands separator (thousands).
	 *
	 * If the currency format is not specified, the currency format from the
	 * store setting is used.  If no setting is available, the currency format
	 * for US dollars is used.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param float $amount The amount to be formatted
	 * @param array $format The currency format to use
	 * @return string The formatted amount
	 **/
	public static function money ( $amount, array $format = array() ) {

		$format = apply_filters('shopp_money_format', Shopp::currency_format($format) );
		extract($format, EXTR_SKIP);

		$amount = apply_filters('shopp_money_amount', $amount);
		$number = Shopp::numeric_format(abs($amount), $precision, $decimals, $thousands, $grouping);

		if ( $cpos ) return ( $amount < 0 ? '-' : '' ) . $currency . $number;
		else return $number . $currency;

	}

	/**
	 * Formats a number with typographically accurate multi-byte separators and variable algorisms
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param float $number A floating point or integer to format
	 * @param int $precision (optional) The number of decimal precision to format to [default: 2]
	 * @param string $decimals The decimal separator character [default: .]
	 * @param string $separator The number grouping separator character [default: ,]
	 * @param int|array $grouping The number grouping pattern [default: array(3)]
	 * @return string The formatted number
	 **/
	public static function numeric_format ($number, $precision=2, $decimals='.', $separator=',', $grouping=array(3)) {
		$n = sprintf("%0.{$precision}F",$number);
		$whole = $fraction = 0;

		if (strpos($n,'.') !== false) list($whole,$fraction) = explode('.',$n);
		else $whole = $n;

		if (!is_array($grouping)) $grouping = array($grouping);

		$i = 0;
		$lg = count($grouping)-1;
		$ng = array();
		while(strlen($whole) > $grouping[min($i,$lg)] && !empty($grouping[min($i,$lg)])) {
			$divide = strlen($whole) - $grouping[min($i++,$lg)];
			$sequence = $whole;
			$whole = substr($sequence,0,$divide);
			array_unshift($ng,substr($sequence,$divide));
		}
		if (!empty($whole)) array_unshift($ng,$whole);

		$whole = join($separator,$ng);
		$whole = str_pad($whole,1,'0');

		// echo BR.$fraction.BR;

		$fraction = rtrim(substr($fraction,0,$precision),'0');
		$fraction = str_pad($fraction,$precision,'0');

		$n = $whole.(!empty($fraction)?$decimals.$fraction:'');

		return $n;
	}

	/**
	 * Parse a US or Canadian telephone number
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param int $num The number to format
	 * @return array A list of phone number components
	 **/
	public static function parse_phone ( $num ) {
		if ( empty($num) ) return '';
		$raw = preg_replace('/[^\d]/', '', $num);

		if ( strlen($raw) == 7 ) sscanf($raw, "%3s%4s", $prefix, $exchange);
		if ( strlen($raw) == 10 ) sscanf($raw, "%3s%3s%4s", $area, $prefix, $exchange);
		if ( strlen($raw) == 11 ) sscanf($raw, "%1s%3s%3s%4s", $country, $area, $prefix, $exchange);

		return compact('country', 'area', 'prefix', 'exchange', 'raw');
	}

	/**
	 * Formats a number to telephone number style
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $num The number to format
	 * @return string The formatted telephone number
	 **/
	public static function phone ( $num ) {
		if ( empty($num) ) return '';

		$parsed = Shopp::parse_phone($num);
		extract($parsed);

		$string = '';
		$string .= ( isset($country) )  ? "$country "  : '';
		$string .= ( isset($area) )     ? "($area) "   : '';
		$string .= ( isset($prefix) )   ? $prefix      : '';
		$string .= ( isset($exchange) ) ? "-$exchange" : '';
		$string .= ( isset($ext) )      ? " x$ext"     : '';

		return $string;
	}

	/**
	 * Formats a numeric amount to a percentage using a specified format
	 *
	 * Uses a format configuration array to specify how the amount needs to be
	 * formatted.  When no format is specified, the currency format setting
	 * is used only paying attention to the decimal precision, decimal symbol and
	 * thousands separator.  If no setting is available, a default configuration
	 * is used (precision: 1) (decimal separator: .) (thousands separator: ,)
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param float $amount The amount to format
	 * @param array $format A specific format for the number
	 * @return string The formatted percentage
	 **/
	public static function percentage ( $amount, $format = array() ) {
		$format = Shopp::currency_format($format);
		extract($format, EXTR_SKIP);
		$float = Shopp::floatval($amount, true, $format);
		$percent = Shopp::numeric_format($float, $precision, $decimals, $thousands, $grouping);
		if ( false !== strpos($percent, $decimals) ) { // Only remove trailing 0's after the decimal
			$percent = rtrim($percent, '0');
			$percent = rtrim($percent, $decimals);
		}
		return "$percent%";
	}

	/**
	 * Translate callback function for preg_replace_callback.
	 *
	 * Helper function for copy_shopp_templates to translate strings in core template files.
	 *
	 * @author John Dillick
	 * @since 1.1
	 *
	 * @param array $matches preg matches array, expects $1 to be type and $2 to be string
	 * @return string _e translated string
	 * @deprecated 1.3
	 **/
	public static function preg_e_callback ( array $matches ) {
		return ( 'e' == $matches[1] ) ? Shopp::__($matches[2]) : "'" . Shopp::__($matches[2]) . "'";
	}

	/**
	 * Returns the raw url that was requested
	 *
	 * Useful for getting the complete value of the requested url
	 *
	 * @author Jonathan Davis, John Dillick
	 * @since 1.1
	 *
	 * @return string raw request url
	 **/
	public static function raw_request_url () {
		return esc_url(
			'http'.
			(is_ssl()?'s':'').
			'://'.
			$_SERVER['HTTP_HOST'].
			$_SERVER['REQUEST_URI'].
			('' != get_option('permalink_structure') ? (
				(!empty($_SERVER['QUERY_STRING']) ? '?' : '' ).$_SERVER['QUERY_STRING'] ):''
			)
		);
	}

	/**
	 * Converts bytes to the largest applicable human readable unit
	 *
	 * Supports up to petabyte sizes
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $bytes The number of bytes
	 * @return string The formatted unit size
	 **/
	public static function readableFileSize($bytes, $precision=1) {
		$units = array(Shopp::__('bytes'), 'KB', 'MB', 'GB', 'TB', 'PB');
		$sized = $bytes*1;
		if ( $sized == 0 ) return $sized;
		$unit = 0;
		while ( $sized >= 1024 && ++$unit ) $sized = $sized/1024;
		return round($sized, $precision) . " " . $units[ $unit ];
	}

	/**
	 * Rounds a price amount with the store's currency format
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param float $amount The number to be rounded
	 * @param array $format (optional) The formatting settings to use,
	 * @return float The rounded float
	 **/
	public static function roundprice ($amount, $format = array() ) {
		$format = Shopp::currency_format($format);
		extract($format);
		return round($amount, $precision);
	}

	/**
	 * Uses builtin php openssl library to encrypt data.
	 *
	 * @author John Dillick
	 * @since 1.1
	 *
	 * @param string $data data to be encrypted
	 * @param string $pkey PEM encoded RSA public key
	 * @return string Encrypted binary data
	 **/
	public static function rsa_encrypt ( $data, $pkey ) {
		openssl_public_encrypt($data, $encrypted, $pkey);
		return ($encrypted) ? $encrypted : false;
	}


	/**
	 * Scans a formatted string to build a list of currency formatting settings
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @param string $format A currency formatting string such as $#,###.##
	 * @return array Formatting options list
	 **/
	public static function scan_money_format ( $format ) {
		$f = array(
			'cpos' => true,
			'currency' => '',
			'precision' => 0,
			'decimals' => '',
			'thousands' => '',
			'grouping' => 3
		);

		$decimals = array('.', ',', '·', "'");

		$ds = strpos($format, '#'); // Position of the first digit
		$de = strrpos($format, '#') + 1; // Position of the last digit
		$df = substr($format, $ds, ($de - $ds)); // Digit formatting from first to last #

		// Currency symbol
		$f['cpos'] = true; // True means symbol prefixes number
		if ( 0 != $ds ) { // If starting digit is not at 0, currency symbol is in front of it
			$f['currency'] = substr($format, 0, $ds);
			if ( '#' != substr($format, $de) )
				$f['decimals'] = substr($format, $de);
		} else {
			$currency = substr($format, $de);
			if ( in_array($currency{0}, $decimals) ) {
				$f['decimals'] = $currency{0};
				$f['currency'] = substr($currency, 1);
			} else {
				$f['currency'] = substr($format, $de);
			}
			$f['cpos'] = false;
		}
		$f['currency'] = trim($f['currency']);

		$found = array();
		if ( ! preg_match_all('/([^#]+)/', $df, $found) || empty($found) ) return $f;

		$dl = $found[0];
		$dd = 0; // Decimal digits

		if ( count($dl) > 1 ) {
			if ( $dl[0] == $dl[1] && ! isset($dl[2]) ) {
				$f['thousands'] = $dl[1];
				$f['precision'] = 0;
			} else {
				$f['decimals'] = $dl[ count($dl) - 1 ];
				$f['thousands'] = $dl[0];
			}
		} elseif ( ! empty($f['decimals']) && $dl[0] != $f['decimals'] ) {
			$f['thousands'] = $dl[0];
		} else $f['decimals'] = $dl[0];

		$dfc = $df;
		// Count for precision
		if ( ! empty($f['decimals']) && strpos($df, $f['decimals']) !== false) {
			list($dfc,$dd) = explode($f['decimals'], $df);
			$f['precision'] = strlen($dd);
		}

		if ( ! empty($f['thousands']) && false !== strpos($df, $f['thousands']) ) {
			$groupings = explode($f['thousands'], $dfc);
			$grouping = array();

			foreach ( $groupings as $grouping ) {
				list($i, $g) = $grouping;

				if ( strlen($g) > 1 ) {
					array_unshift($grouping, strlen($g) );
				}
			}

			$f['grouping'] = $grouping;
		}

		return $f;
	}

	/**
	 * Wrapper function to set the WP and WP_Query query_vars
	 *
	 * This wrapper is to make it easier to set query_vars in the
	 * WP global object and the WP_Query simultaneously. This makes
	 * it easier to manipulate requests as necessary
	 * (especially in the case of Shopp searches)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $var Name of the var to set
	 * @param string $value Value to set
	 * @return void
	 **/
	public static function set_wp_query_var ($var,$value) {
		global $wp;
		$wp->set_query_var($var,$value);
		set_query_var($var,$value);
	}

	/**
	 * Wrapper function to get a WP query_var
	 *
	 * This is used only in contexts where the WP_Query public API
	 * call get_query_var() doesn't work (specifically during parse_request)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $key The name of the query_var to retrieve
	 * @return mixed
	 **/
	public static function get_wp_query_var ($key) {
		global $wp;
		if (isset($wp->query_vars[$key]))
			return $wp->query_vars[$key];
	}

	/**
	 * Wraps mark-up in a #shopp container, if needed
	 *
	 * @deprecated Use ShoppStorefront::wrapper() instead
	 **/
	public static function div ($string) {
		return ShoppStorefront::wrapper($string);
	}

	public static function daytimes () {
		$args = func_get_args();
		$periods = array("h"=>3600,"d"=>86400,"w"=>604800,"m"=>2592000);

		$total = 0;
		foreach ($args as $timeframe) {
			if (empty($timeframe)) continue;
			list($i,$p) = sscanf($timeframe,'%d%s');
			$total += $i*$periods[$p];
		}
		return ceil($total/$periods['d']).'d';
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

	/**
	 * Generates RSS markup in XML from a set of provided data
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @deprecated Functionality moved to the ShoppStorefront
	 *
	 * @param array $data The data to populate the RSS feed with
	 * @return string The RSS markup
	 **/
	public static function rss ($data) {
		// RSS filters
		add_filter('shopp_rss_description','convert_chars');
		add_filter('shopp_rss_description','ent2ncr');

		$xmlns = '';
		if ( isset($data['xmlns']) && is_array($data['xmlns']) )
			foreach ($data['xmlns'] as $key => $value)
				$xmlns .= 'xmlns:'.$key.'="'.$value.'" ';

		$xml = "<?xml version=\"1.0\""." encoding=\"utf-8\"?>\n";
		$xml .= "<rss version=\"2.0\" $xmlns>\n";
		$xml .= "<channel>\n";

		$xml .= '<atom:link href="'.esc_attr($data['link']).'" rel="self" type="application/rss+xml" />'."\n";
		$xml .= "<title>".esc_html($data['title'])."</title>\n";
		$xml .= "<description>".esc_html($data['description'])."</description>\n";
		$xml .= "<link>".esc_html($data['link'])."</link>\n";
		$xml .= "<language>".get_option('rss_language')."</language>\n";
		$xml .= "<copyright>".esc_html("Copyright ".date('Y').", ".$data['sitename'])."</copyright>\n";

		if ( isset($data['items']) && is_array($data['items']) ) {
			foreach($data['items'] as $item) {
				$xml .= "\t<item>\n";
				foreach ($item as $key => $value) {
					$attrs = '';
					if (is_array($value)) {
						$data = $value;
						$value = '';
						foreach ($data as $name => $content) {
							if (empty($name)) $value = $content;
							else $attrs .= ' '.$name.'="'.esc_attr($content).'"';
						}
					}
					if (strpos($value,'<![CDATA[') === false) $value = esc_html($value);
					if (!empty($value)) $xml .= "\t\t<$key$attrs>$value</$key>\n";
					else $xml .= "\t\t<$key$attrs />\n";
				}
				$xml .= "\t</item>\n";
			}
		}

		$xml .= "</channel>\n";
		$xml .= "</rss>\n";

		return $xml;
	}

	/**
	 * Returns the platform appropriate page name for Shopp internal pages
	 *
	 * IIS rewriting requires including index.php as part of the page
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $page The normal page name
	 * @return string The modified page name
	 **/
	public static function pagename ($page) {
		global $is_IIS;
		$prefix = strpos($page,"index.php/");
		if ($prefix !== false) return substr($page,$prefix+10);
		else return $page;
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
	 * Redirects the browser to a specified URL
	 *
	 * A wrapper for the wp_redirect function
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $uri The URI to redirect to
	 * @param boolean $exit (optional) Exit immediately after the redirect (defaults to true, set to false to override)
	 * @return void
	 **/
	public static function redirect ($uri,$exit=true,$status=302) {
		shopp_debug("Redirecting to: $uri");

		remove_action('shutdown', array(ShoppShopping(), 'save'));
		ShoppShopping()->save();

		wp_redirect($uri, $status);
		if ($exit) exit();
	}

	/**
	 * Safely handles redirect requests to ensure they remain onsite
	 *
	 * Derived from WP 2.8 wp_safe_redirect
	 *
	 * @author Mark Jaquith, Ryan Boren
	 * @since 1.1
	 *
	 * @param string $location The URL to redirect to
	 * @param int $status (optional) The HTTP status to send to the browser
	 * @return void
	 **/
	public static function safe_redirect($location, $status = 302) {

		// Need to look at the URL the way it will end up in wp_redirect()
		$location = wp_sanitize_redirect($location);

		// browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
		if ( substr($location, 0, 2) == '//' )
			$location = 'http:' . $location;

		// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
		$test = ( $cut = strpos($location, '?') ) ? substr( $location, 0, $cut ) : $location;

		$lp  = parse_url($test);
		$wpp = parse_url(get_option('home'));

		$allowed_hosts = (array) apply_filters('allowed_redirect_hosts', array($wpp['host']), isset($lp['host']) ? $lp['host'] : '');

		if ( isset($lp['host']) && ( !in_array($lp['host'], $allowed_hosts) && $lp['host'] != strtolower($wpp['host'])) )
			$location = Shopp::url(false,'account');

		self::redirect($location, true, $status);
	}

	/**
	 * Determines the effective tax rate (a single rate) for the store or an item based
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.3
	 *
	 * @param Object $Item (optional) The ShoppProduct, ShoppCartItem or ShoppPurchased object to find tax rates for
	 * @return float The determined tax rate
	 **/
	public static function taxrate ( $Item = null ) {

		$taxes = self::taxrates($Item);

		if ( empty($taxes) ) $taxrate = 0.0; // No rates given
		if ( count($taxes) == 1 ) {
			$TaxRate = current($taxes);
			$taxrate = (float)$TaxRate->rate; // Use the given rate
		} else $taxrate = (float)( ShoppTax::calculate($taxes, 100) ) / 100; // Calculate the "effective" rate (note: won't work with compound taxes)

		return apply_filters('shopp_taxrate', $taxrate);

	}

	/**
	 * Determines all applicable tax rates for the store or an item
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.3
	 *
	 * @param Object $Item (optional) The ShoppProduct, ShoppCartItem or ShoppPurchased object to find tax rates for
	 * @return float The determined tax rate
	 **/
	public static function taxrates ( $Item = null ) {

		$Tax = new ShoppTax();

		$Order = ShoppOrder(); // Setup taxable address
		$Tax->address($Order->Billing, $Order->Shipping, $Order->Cart->shipped());

		$taxes = array();
		if ( is_null($Item) ) $Tax->rates($taxes);
		else $Tax->rates($taxes, $Tax->item($Item));

		return apply_filters('shopp_taxrates', $taxes);

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
	 * Generates canonical storefront URLs that respects the WordPress permalink settings
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @param mixed $request Additional URI requests
	 * @param string $page The gateway page
	 * @param boolean $secure (optional) True for secure URLs, false to force unsecure URLs
	 * @return string The final URL
	 **/
	public static function url ( $request = false, $page = 'catalog', $secure = null ) {
		global $wp_rewrite;

		$prettyurls = $wp_rewrite->using_permalinks();

		// Support IIS index.php/ prefixed permalinks
		if ( $wp_rewrite->using_index_permalinks() )
			$path[] = 'index.php';

		$path[] = ShoppPages()->baseslug();

		// Build request path based on Storefront shopp_page requested
		if ( 'images' == $page ) {
			$path[] = 'images';
			if ( ! $prettyurls ) $request = array('siid' => $request);
		} else {
			if ( false !== $page ) {
				$Page = ShoppPages()->get($page);
				if ( method_exists($Page, 'slug') )
					$page_slug = $Page->slug();
			}

			if ( $page != 'catalog' && ! empty($page_slug) )
				$path[] = $page_slug;
		}

		// Change the URL scheme as necessary
		$scheme = null; // Full-auto
		if ( $secure === false ) $scheme = 'http'; // Contextually forced off
		elseif ( ( $secure || is_ssl() ) && ! SHOPP_NOSSL ) $scheme = 'https'; // HTTPS required

		$url = home_url(false,$scheme);
		if ( $prettyurls ) $url = home_url(join('/', $path), $scheme);
		if ( false !== strpos($url, '?') ) list($url, $query) = explode('?', $url);

		$url = trailingslashit($url);

		if ( ! empty($query) ) {
			parse_str($query, $home_queryvars);
			if ( false === $request ) {
				$request = array_merge($home_queryvars, array());
			} else {
				$request = array($request);
				array_push($request, $home_queryvars);
			}
		}

		if ( ! $prettyurls ) $url = isset($page_slug) ? add_query_arg('shopp_page', $page_slug, $url) : $url;

		// No extra request, return the complete URL
		if ( ! $request ) return apply_filters('shopp_url', $url);

		// Filter URI request
		$uri = false;
		if ( ! is_array($request)) $uri = urldecode($request);
		if ( is_array($request) && isset($request[0]) ) $uri = array_shift($request);
		if ( ! empty($uri) ) $uri = join('/', array_map('urlencode', explode('/', $uri))); // sanitize

		$url .= $uri;

		if ( false === strpos(basename($uri), '.') ) // Not an image URL
			$url = user_trailingslashit($url);

		if ( ! empty($request) && is_array($request) ) {
			$request = array_map('urldecode', $request);
			$request = array_map('urlencode', $request);
			$url = add_query_arg($request, $url);
		}

		return apply_filters('shopp_url', $url);
	}

	/**
	 * Recursively sorts a heirarchical tree of data
	 *
	 * @param array $item The item data to be sorted
	 * @param int $parent (internal) The parent item of the current iteration
	 * @param int $key (internal) The identified index of the parent item in the current iteration
	 * @param int $depth (internal) The number of the nested depth in the current iteration
	 * @return array The sorted tree of data
	 * @author Jonathan Davis
	 * @deprecated 1.3
	 **/
	public static function sort_tree ($items,$parent=0,$key=-1,$depth=-1) {
		$depth++;
		$position = 1;
		$result = array();
		if ($items) {
			foreach ($items as $item) {
				// Preserve initial priority
				if (isset($item->priority))	$item->_priority = $item->priority;
				if ($item->parent == $parent) {
					$item->parentkey = $key;
					$item->depth = $depth;
					$item->priority = $position++;
					$result[] = $item;
					$children = Shopp::sort_tree($items, $item->id, count($result)-1, $depth);
					$result = array_merge($result,$children); // Add children in as they are found
				}
			}
		}
		$depth--;
		return $result;
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
	 * @deprecated
	 **/
	public static function value_is_true ($value) {
		return str_true ($value);
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
	public static function valid_input ($type) {
		$inputs = array('text', 'hidden', 'checkbox', 'radio', 'button', 'submit');
		if ( in_array($type, $inputs) !== false ) return true;
		return false;
	}

	/**
	 * Detect Suhosin enabled with problematic settings
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return boolean True if Suhosin is dectected and has configuration issues
	 **/
	public static function suhosin_warning () {

		return ( // Is Suhosin loaded or available?
				(extension_loaded('Suhosin') || (defined('SUHOSIN_PATCH') && SUHOSIN_PATCH))
				&& // Are the known problem settings defined?
				(
					@ini_get('suhosin.max_array_index_length') > 0 && @ini_get('suhosin.max_array_index_length') < 256
					&& @ini_get('suhosin.post.max_array_index_length') > 0 && @ini_get('suhosin.post.max_array_index_length') < 256
					&& @ini_get('suhosin.post.max_totalname_length') > 0 && @ini_get('suhosin.post.max_totalname_length') < 65535
					&& @ini_get('suhosin.post.max_vars') > 0 && @ini_get('suhosin.post.max_vars') < 1024
					&& @ini_get('suhosin.request.max_array_index_length') > 0 && @ini_get('suhosin.request.max_array_index_length') < 256
					&& @ini_get('suhosin.request.max_totalname_length') > 0 && @ini_get('suhosin.request.max_totalname_length') < 65535
					&& @ini_get('suhosin.request.max_vars') > 0 && @ini_get('suhosin.request.max_vars') < 1024
				)
		);
	}

	/**
	 * Trim whitespace from the beggingin
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void Description...
	 **/
	public static function trim_deep ( $value ) {

		if ( is_object($value) ) {
			$vars = get_object_vars( $value );
			foreach ( $vars as $key => $data )
				$value->{$key} = self::trim_deep( $data );
		} elseif ( is_array($value) ) {
			$value = array_map(array(__CLASS__, 'trim_deep'), $value);
		} elseif ( is_string( $value ) ) {
			$value = trim($value);
		}

		return $value;

	}

	/**
	 * Keyed wrapper for wp_cache_set
	 *
	 * @author Clifton Griffin
	 * @since 1.4
	 *
	 * @param mixed $key
	 * @param mixed $data
	 * @param mixed $group (default: null)
	 * @param mixed $expire (default: null)
	 *
	 * @return True
	 */
	public static function cache_set ( $key, $data, $group = null, $expire = null ) {
		// Allows us to gracefully expire cache when required
		$ns_key = wp_cache_get( 'shopp_cache_key' );

		// If cache key doesn't exist, create it
		if ( $ns_key === false ) {
			$ns_key = 1;
			wp_cache_set( 'shopp_cache_key', $ns_key );
		}

		return wp_cache_set($key . $ns_key, $data, $group, $expire);
	}

	/**
	 * Keyed wrapper for wp_cache_get function.
	 *
	 * @author Clifton Griffin
	 * @since 1.4
	 *
	 * @param mixed $key
	 * @param mixed $group (default: null)
	 * @param mixed $force (default: null)
	 * @param mixed $found (default: null)
	 *
	 * @return False on failure to retrieve contents or the cache contents on success
	 */
	public static function cache_get ( $key, $group = null, $force = null, $found = null ) {
		// Seed request for cache
		$ns_key = wp_cache_get( 'shopp_cache_key' );

		return wp_cache_get( $key . $ns_key, $group, $force, $found );
	}

	/**
	 * Increment the cache key to gracefully invalidate Shopp specific caches
	 *
	 * @author Clifton Griffin
	 * @since 1.4
	 *
	 * @return void
	 */
	public static function cache_invalidate() {
		wp_cache_incr( 'shopp_cache_key' );

		do_action('shopp_cache_invalidate');
	}

} // End abstract class ShoppCore


























/**
 * Handles sanitizing URLs for use in markup HREF attributes
 *
 * Wrapper for securing URLs generated with the WordPress
 * add_query_arg() function
 *
 * @author Jonathan Davis
 * @since 1.0
 *
 * @param mixed $param1 Either newkey or an associative_array
 * @param mixed $param2 Either newvalue or oldquery or uri
 * @param mixed $param3 Optional. Old query or uri
 * @return string New URL query string.
 **/
if ( ! function_exists('href_add_query_arg')) {
	function href_add_query_arg () {
		$args = func_get_args();
		$url = call_user_func_array('add_query_arg',$args);
		list($uri,$query) = explode("?",$url);
		return $uri.'?'.htmlspecialchars($query);
	}
}

if ( ! function_exists('mkobject')) {
	/**
	 * Converts an associative array to a stdClass object
	 *
	 * Uses recursion to convert nested associative arrays to a
	 * nested stdClass object while maintaing numeric indexed arrays
	 * and converting associative arrays contained within the
	 * numeric arrays
	 *
	 * @author Jonathan Davis
	 *
	 * @param array $data The associative array to convert
	 * @return void
	 **/
	function mkobject (&$data) {
		$numeric = false;
		foreach ($data as $p => &$d) {
			if (is_array($d)) mkobject($d);
			if (is_int($p)) $numeric = true;
		}
		if (!$numeric) settype($data,'object');
	}
}
if ( ! function_exists('sanitize_path') ) {
	/**
	 * Normalizes path separators to always use forward-slashes
	 *
	 * PHP path functions on Windows-based systems will return paths with
	 * backslashes as the directory separator.  This function is used to
	 * ensure we are always working with forward-slash paths
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $path The path to clean up
	 * @return string $path The forward-slash path
	 **/
	function sanitize_path ($path) {
		return str_replace('\\', '/', $path);
	}
}

if ( ! function_exists('get_class_property') ) {
	/**
	 * Gets the property of an uninstantiated class
	 *
	 * Provides support for getting a property of an uninstantiated
	 * class by dynamic name.  As of PHP 5.3.0 this function is no
	 * longer necessary as you can simply reference as $Classname::$property
	 *
	 * @author Jonathan Davis
	 * @since PHP 5.3.0
	 *
	 * @param string $classname Name of the class
	 * @param string $property Name of the property
	 * @return mixed Value of the property
	 **/
	function get_class_property ($classname, $property) {
		if( ! class_exists($classname, false) ) return;
		if( ! property_exists($classname, $property) ) return;

		$vars = get_class_vars($classname);
		return $vars[ $property ];
	}
}

/** Deprecated global function aliases **/

/**
 * @deprecated Use Shopp::_d()
 **/
function _d ( $format, $timestamp = false ) {
	return Shopp::_d($format, $timestamp);
}

/**
 * @deprecated Use Shopp::_jse()
 **/
function _jse ( $text, $domain = 'default' ) {
	Shopp::_jse($text, $domain );
}

/**
 * @deprecated Use Shopp::_object_r()
 **/
function _object_r ($object) {
	return Shopp::object_r($object);
}

/**
 * @deprecated Use Shopp::add_query_string()
 **/
function add_query_string ( $string, $url) {
	return Shopp::add_query_string($string, $url);
}

/**
 * @deprecated Use Shopp::add_storefrontjs()
 **/
function add_storefrontjs ($script,$global=false) {
	Shopp::add_storefrontjs($script,$global);
}

/**
 * @deprecated Use Shopp::array_filter_keys()
 **/
function array_filter_keys ($array,$mask) {
	return Shopp::array_filter_keys($array,$mask);
}

/**
 * @deprecated Use Shopp::auto_ranges()
 **/
function auto_ranges ($avg, $max, $min, $values) {
	return Shopp::auto_ranges($avg, $max, $min, $values);
}

/**
 * @deprecated Use Shopp::convert_unit()
 **/
function convert_unit ($value = 0, $unit, $from=false) {
	return Shopp::convert_unit($value, $unit, $from);
}

/**
 * @deprecated Use Shopp::copy_templates()
 **/
function copy_shopp_templates ( $src, $target ) {
	Shopp::copy_templates($src, $target);
}

/**
 * @deprecated Use Shopp::crc16()
 **/
function crc16 ($data) {
	return Shopp::crc16($data);
}

/**
 * @deprecated Use Shopp::currency_format()
 **/
function currency_format ( $format = array() ) {
	return Shopp::currency_format($format);
}

/**
 * @deprecated Use Shopp::datecalc()
 **/
function datecalc ( $week = -1, $dayOfWeek = -1, $month = -1, $year = -1 ) {
	return Shopp::datecalc($week, $dayofWeek, $month, $year);
}

/**
 * @deprecated Use Shopp::date_format_order()
 **/
function date_format_order ($fields=false) {
	return Shopp::date_format_order($fields);
}

/**
 * @deprecated Use Shopp::debug_caller()
 **/
function debug_caller () {
	return Shopp::debug_caller();
}

/**
 * @deprecated Use Shopp::duration()
 **/
function duration ($start,$end) {
	return Shopp::duration($start,$end);
}

/**
 * @deprecated Use Shopp::esc_attrs()
 **/
function esc_attrs ($value) {
	return Shopp::esc_attrs($value);
}

/**
 * @deprecated Use Shopp::filter_dotfiles()
 **/
function filter_dotfiles ($name) {
	return Shopp::filter_dotfiles($name);
}

/**
 * @deprecated Use Shopp::find_filepath()
 **/
function findfile ($filename, $directory, $root, &$found) {
	return Shopp::find_filepath($filename, $directory, $root, $found);
}

/**
 * @deprecated Use Shopp::file_mimetype()
 **/
function file_mimetype ($file,$name=false) {
	return Shopp::file_mimetype($file,$name);
}

/**
 * @deprecated Use Shopp::floatval()
 **/
function floatvalue ($value, $round=true, $format = array()) {
	return Shopp::floatval($value, $round, $format);
}

/**
 * @deprecated Use Shopp::force_ssl()
 **/
function force_ssl ($url,$rewrite=false) {
	return Shopp::force_ssl($url,$rewrite);
}

/**
 * @deprecated Use Shopp::gateway_path()
 **/
function gateway_path ($file) {
	return Shopp::gateway_path($file);
}

/**
 * @deprecated Use Shopp::ini_size()
 **/
function ini_size ($name) {
	return Shopp::ini_size($name);
}

/**
 * @deprecated Use Shopp::inputattrs()
 **/
function inputattrs ($options,$allowed=array()) {
	return Shopp::inputattrs($options,$allowed);
}

/**
 * @deprecated Use Shopp::is_robot()
 **/
function is_robot() {
	return Shopp::is_robot();
}

/**
 * @deprecated Use Shopp::
 **/
function is_shopp_userlevel () { return; }


/**
 * @deprecated Using WP function instead
 **/
function is_shopp_secure () {
	return is_ssl();
}

/**
 * @deprecated Use Shopp::linkencode()
 **/
function linkencode ($url) {
	return Shopp::linkencode($url);
}

/**
 * @deprecated Use Shopp::locate_shopp_template()
 **/
function locate_shopp_template ($template_names, $load = false, $require_once = false ) {
	return Shopp::locate_template($template_names, $load, $require_once);
}

/**
 * @deprecated Use Shopp::lzw_compress()
 **/
function lzw_compress ($s) {
	return Shopp::lzw_compress($s);
}

/**
 * @deprecated Use Shopp::mktimestamp()
 **/
function mktimestamp ($datetime) {
	return Shopp::mktimestamp($datetime);
}

/**
 * @deprecated Use Shopp::mkdatetime()
 **/
function mkdatetime ($timestamp) {
	return Shopp::mkdatetime($timestamp);
}

/**
 * @deprecated Use Shopp::mk24hour()
 **/
function mk24hour ($hour, $meridiem) {
	return Shopp::mk24hour($hour, $meridiem);
}

/**
 * @deprecated Use Shopp::menuoptions()
 **/
function menuoptions ($list,$selected=null,$values=false,$extend=false) {
	return Shopp::menuoptions($list,$selected,$values,$extend);
}

/**
 * @deprecated Use Shopp::money()
 **/
function money ($amount, $format = array()) {
	return Shopp::money($amount, $format);
}

/**
 * @deprecated Use Shopp::numeric_format()
 **/
function numeric_format ($number, $precision=2, $decimals='.', $separator=',', $grouping=array(3)) {
	return Shopp::numeric_format($number, $precision, $decimals, $separator, $grouping);
}

/**
 * @deprecated Use Shopp::parse_phone()
 **/
function parse_phone ($num) {
	return Shopp::parse_phone($num);
}

/**
 * @deprecated Use Shopp::phone()
 **/
function phone ($num) {
	return Shopp::phone($num);
}

/**
 * @deprecated Use Shopp::percentage()
 **/
function percentage ( $amount, $format = array() ) {
	return Shopp::percentage( $amount, $format);
}

/**
 * @deprecated Use Shopp::preg_e_callback()
 **/
function preg_e_callback ($matches) {
	return Shopp::preg_e_callback($matches);
}

/**
 * @deprecated Use Shopp::raw_request_url()
 **/
function raw_request_url () {
	return Shopp::raw_request_url();
}

/**
 * @deprecated Use Shopp::readableFileSize()
 **/
function readableFileSize ($bytes,$precision=1) {
	return Shopp::readableFileSize($bytes,$precision);
}

/**
 * @deprecated Use Shopp::roundprice()
 **/
function roundprice ($amount, $format = array()) {
	return Shopp::roundprice($amount, $format);
}

/**
 * @deprecated Use Shopp::rsa_encrypt()
 **/
function rsa_encrypt ($data, $pkey) {
	return Shopp::rsa_encrypt($data, $pkey);
}


/**
 * @deprecated Use Shopp::scan_money_format()
 **/
function scan_money_format ( $format ) {
	return Shopp::scan_money_format( $format );
}

/**
 * @deprecated Use Shopp::set_wp_query_var()
 **/
function set_wp_query_var ($var,$value) {
	return Shopp::set_wp_query_var($var,$value);
}

/**
 * @deprecated Use Shopp::get_wp_query_var()
 **/
function get_wp_query_var ($key) {
	return Shopp::get_wp_query_var($key);
}

/**
 * @deprecated Use Shopp::div()
 **/
function shoppdiv ($string) {
	return Shopp::div($string);
}

/**
 * @deprecated Use Shopp::daytimes()
 **/

function shopp_daytimes () {
	return Shopp::daytimes();
}

/**
 * @deprecated Use Shopp::email()
 **/
function shopp_email ($template,$data=array()) {
	return Shopp::email($template,$data);
}

/**
 * @deprecated Use Shopp::rss()
 **/
function shopp_rss ($data) {
	return Shopp::rss($data);
}

/**
 * @deprecated Use Shopp::pagename()
 **/
function shopp_pagename ($page) {
	return Shopp::pagename($page);
}

/**
 * @deprecated Use Shopp::parse_options()
 **/
function shopp_parse_options ($options) {
	return Shopp::parse_options($options);
}

/**
 * @deprecated Use Shopp::redirect()
 **/
function shopp_redirect ($uri, $exit=true, $status=302) {
	Shopp::redirect($uri, $exit, $status);
}

/**
 * @deprecated Use Shopp::safe_redirect()
 **/
function shopp_safe_redirect ($location, $status = 302) {
	Shopp::safe_redirect($location, $status);
}

/**
 * @deprecated Use Shopp::taxrate()
 **/
function shopp_taxrate ($override=null,$taxprice=true,$Item=false) {
	return Shopp::taxrate($Item);
}

/**
 * @deprecated Use Shopp::template_prefix()
 **/
function shopp_template_prefix ($name) {
	return Shopp::template_prefix($name);
}

/**
 * @deprecated Use Shopp::template_url()
 **/
function shopp_template_url ($name) {
	return Shopp::template_url($name);
}

/**
 * @deprecated Use Shopp::url()
 **/
function shoppurl ($request=false,$page='catalog',$secure=null) {
	return Shopp::url($request,$page,$secure);
}

/**
 * @deprecated Use Shopp::sort_tree()
 **/
function sort_tree ($items,$parent=0,$key=-1,$depth=-1) {
	return Shopp::sort_tree($items,$parent,$key,$depth);
}

/**
 * @deprecated Use Shopp::str_true()
 **/

function str_true ( $string, $istrue = array('yes', 'y', 'true','1','on','open') ) {
	return Shopp::str_true($string,$istrue);
}

/**
 * @deprecated Use Shopp::str_true()
 **/
function value_is_true ($value) {
	return Shopp::str_true($value);
}

/**
 * @deprecated Use Shopp::valid_input()
 **/
function valid_input ($type) {
	return Shopp::valid_input($type);
}

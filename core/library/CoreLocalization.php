<?php
/**
 * CoreLocalization.php
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

abstract class ShoppCoreLocalization extends ShoppCoreTemplates {

	const DOMAIN = 'Shopp';

	/**
	 * Shopp wrapper to return gettext translation strings
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated text
	 **/
	public static function __() {
		$args = func_get_args(); // Handle sprintf rendering
		$text = array_shift($args);
		$translated = self::translate($text);
		if ( count($args) > 0 )
			return vsprintf($translated, $args);
		return $translated;
	}

	/**
	 * Shopp wrapper to output gettext translation strings
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated text
	 **/
	public static function _e() {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '__'), $args);
	}

	/**
	 * Shopp wrapper to return gettext translation strings with context support
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _x() {
		$args = func_get_args();
		$text = array_shift($args);
		$context = array_shift($args);
		$translated = self::translate($text, $context);

		if ( 0 == count($args) ) return $translated;
		else return vsprintf($translated, $args);
	}

	/**
	 * Get translated Markdown rendered HTML
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated Markdown-rendered HTML text
	 **/
	public static function _m() {
		$args = func_get_args();
		$translated = call_user_func_array(array(__CLASS__, '__'), $args);
		if ( false === $translated ) return '';

		return new MarkdownText($translated);
	}

	/**
	 * Output translated Markdown rendered HTML
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return void
	 **/
	public static function _em( $text ) {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '_m'), $args);
	}

	/**
	 * Get translated inline-Markdown rendered HTML (use for single-line Markdown)
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @return string The translated Markdown-rendered HTML text
	 **/
	public static function _mi() {
		$args = func_get_args();
		$markdown = call_user_func_array(array(__CLASS__, '_m'), $args);
		return str_replace(array('<p>', '</p>'), '', $markdown);
	}

	/**
	 * Output translated Markdown rendered HTML with translator context
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _emi( $text ) {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '_mi'), $args);
	}

	/**
	 * Get translated Markdown rendered HTML with translator context
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _mx( $text, $context ) {
		$args = func_get_args();
		$translated = call_user_func_array(array(__CLASS__, '_x'), $args);
		if ( false === $translated ) return '';

		return new MarkdownText($translated);
	}

	/**
	 * Output translated Markdown rendered HTML with translator context
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explination of how and where the text is used
	 * @return string The translated text
	 **/
	public static function _emx( $text, $context ) {
		$args = func_get_args();
		echo call_user_func_array(array(__CLASS__, '_mx'), $args);
	}

	public static function esc_attr__( $text ) {
		$args = func_get_args();
		return esc_attr(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function esc_attr_e( $text ) {
		$args = func_get_args();
		echo esc_attr(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function esc_html__( $text ) {
		$args = func_get_args();
		return esc_html(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function esc_html_e( $text ) {
		$args = func_get_args();
		echo esc_html(call_user_func_array(array(__CLASS__, '__'), $args));
	}

	public static function _n( $single, $plural, $number ) {
		$args = array_slice(func_get_args(), 2);
		$translated = _n($single, $plural, $number, 'Shopp');
		return vsprintf($translated, $args);
	}

	public static function _ne( $single, $plural, $number ) {
		$args = func_get_args();
		echo esc_html(call_user_func_array(array(__CLASS__, '_n'), $args));
	}

	/**
	 * Converts timestamps to formatted localized date/time strings
	 *
	 * @since 1.0
	 *
	 * @param string $format A date() format string
	 * @param int $timestamp (optional) The timestamp to be formatted (defaults to current timestamp)
	 * @return string The formatted localized date/time
	 **/
	public static function _d( $format, $timestamp = null ) {
		$tokens = array(
			'D' => array('Mon' => Shopp::__('Mon'), 'Tue' => Shopp::__('Tue'),
						'Wed'  => Shopp::__('Wed'), 'Thu' => Shopp::__('Thu'),
						'Fri'  => Shopp::__('Fri'), 'Sat' => Shopp::__('Sat'),
						'Sun'  => Shopp::__('Sun')),
			'l' => array('Monday'   => Shopp::__('Monday'),    'Tuesday'  => Shopp::__('Tuesday'),
						'Wednesday' => Shopp::__('Wednesday'), 'Thursday' => Shopp::__('Thursday'),
						'Friday'    => Shopp::__('Friday'),    'Saturday' => Shopp::__('Saturday'),
						'Sunday'    => Shopp::__('Sunday')),
			'F' => array('January'  => Shopp::__('January'),   'February' => Shopp::__('February'),
						'March'     => Shopp::__('March'),     'April'    => Shopp::__('April'),
						'May'       => Shopp::__('May'),       'June'     => Shopp::__('June'),
						'July'      => Shopp::__('July'),      'August'   => Shopp::__('August'),
						'September' => Shopp::__('September'), 'October'  => Shopp::__('October'),
						'November'  => Shopp::__('November'),  'December' => Shopp::__('December')),
			'M' => array('Jan' => Shopp::__('Jan'), 'Feb' => Shopp::__('Feb'),
						'Mar'  => Shopp::__('Mar'), 'Apr' => Shopp::__('Apr'),
						'May'  => Shopp::__('May'), 'Jun' => Shopp::__('Jun'),
						'Jul'  => Shopp::__('Jul'), 'Aug' => Shopp::__('Aug'),
						'Sep'  => Shopp::__('Sep'), 'Oct' => Shopp::__('Oct'),
						'Nov'  => Shopp::__('Nov'), 'Dec' => Shopp::__('Dec'))
		);

		$date = is_null($timestamp) ? date($format) : date($format, $timestamp);

		foreach ( $tokens as $token => $strings ) {
			if ( strpos($format, $token) === false )
				continue;
			$string = ! $timestamp ? date($token) : date($token, $timestamp);
			$date = str_replace($string, $strings[ $string ], $date);
		}
		return $date;
	}

	/**
	 * JavaScript encodes translation strings
	 *
	 * @since 1.1.7
	 *
	 * @param string $text Text to translate
	 * @return void
	 **/
	public static function _jse ( $text) {
		echo json_encode(self::translate($text));
	}

	/**
	 * Shopp wrapper for gettext translation strings (with optional context and Markdown support)
	 *
	 * @since 1.3
	 *
	 * @param string $text The text to translate
	 * @param string $context An explanation of how and where the text is used
	 * @return string The translated text
	 **/
	private static function translate( $text, $context = null ) {
		if ( is_null($context) )
			return translate($text, self::DOMAIN);
		else return translate_with_gettext_context($text, $context, self::DOMAIN);
	}

}
<?php
/**
 * CoreFormatting.php
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

abstract class ShoppCoreFormatting extends ShoppCoreLocalization {

	/**
	 * Escapes nested data structure values for safe output to the browser
	 *
	 * @since 1.1
	 *
	 * @param mixed $value The data to escape
	 * @return mixed
	 **/
	public static function esc_attrs( $value ) {
		 return is_array($value) ? array_map(array(__CLASS__, 'esc_attrs'), $value) : esc_attr($value);
	}

	/**
	 * Converts a numeric string to a floating point number
	 *
	 * @since 1.0
	 *
	 * @param string $value Numeric string to be converted
	 * @param boolean $round (optional) Whether to round the value (default true for to round)
	 * @param array $format (optional) The currency format to use for precision (defaults to the current base of operations)
	 * @return float
	 **/
	public static function floatval( $value, $round = true, array $format = array() ) {
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
	 * Returns the duration (in days) between two timestamps
	 *
	 * @since 1.0
	 *
	 * @param int $start The starting timestamp
	 * @param int $end The ending timestamp
	 * @return int	Number of days between the start and end
	 **/
	public static function duration( $start, $end ) {
		return ceil(( $end - $start ) / 86400);
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
	 * @since 1.1
	 * @version 1.2
	 *
	 * @param array $format (optional) A currency format settings array
	 * @return array Format settings array
	 **/
	public static function currency_format( array $format = array() ) {
		$default = ShoppBaseCurrency()->settings();

		// No format provided, use default
		if ( empty($format) )
			return $default;

		// Merge the format options with the default
		return array_merge($default, $format);
	}

	/**
	 * Calculates the timestamp of a day based on a repeating interval (Fourth Thursday in November (Thanksgiving))
	 *
	 * @since 1.0
	 *
	 * @param int|string $week The week of the month (1-4, -1 or first-fourth, last)
	 * @param int|string $dayOfWeek The day of the week (0-6 or Sunday-Saturday)
	 * @param int $month The month, uses current month if none provided
	 * @param int $year The year, uses current year if none provided
	 * @return void
	 **/
	public static function datecalc( $week = -1, $dayOfWeek = -1, $month = -1, $year = -1 ) {
		if ( $dayOfWeek < 0 || $dayOfWeek > 6 )
			return false;

		if ( $month == -1 )
			$month = date ('n'); // No month provided, use current month

		if ( $year == -1 )
			$year = date('Y');    // No year provided, use current year

		// Day of week is a string, look it up in the weekdays list
		$dayOfWeek = self::find_dayofweek($dayOfWeek);

		$weeks = array('first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'last' => -1);
		if ( isset($weeks[ $week ]) )
			$week = $weeks[ $week ];

		$startday = ( 7 * $week ) - 6;
		$lastday = date('t', mktime(0, 0, 0, $month, 1, $year));

		$delta = $week == -1 ?
			( date("w", mktime(0, 0, 0, $month, $lastday, $year)) - $dayOfWeek ) % 7:
			( $dayOfWeek - date('w', mktime(0, 0, 0, $month, 1, $year)) ) % 7;

		if ( $delta < 0 )
			$delta += 7;

		$day = $week == -1 ? $lastday - $delta : $startday + $delta;

		return mktime(0, 0, 0, $month, $day, $year);
	}

	/**
	 * Helper for datecalc() to convert a specified day of the week to an index
	 *
	 * Finds partial matches (e.g. "sun" is 0, "thur" is 4, etc.)
	 *
	 * @since 1.5
	 *
	 * @param int|string $dayofweek A value for the day of the week
	 * @return int An index value (0-6) for the day of the week
	 **/
	private static function find_dayofweek( $dayofweek ) {
		if ( is_numeric($dayofweek) )
			return $dayofweek;

		$weekdays = array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);
		$dayofweek = strtolower($dayofweek);
		if ( isset($week[ $dayofweek ]) )
			return $week[ $dayofweek ];

		$charlen = strlen($dayofweek);
		foreach ( $weekdays as $name => $index ) {
			if ( $dayofweek == substr($name, 0, $charlen) ) {
				$dayofweek = $index;
				break;
			}
		}
		return $dayofweek;
	}

	/**
	 * Builds an array of the current WP date_format setting
	 *
	 * @since 1.1
	 * @version 1.1
	 *
	 * @param boolean $fields Ensure all date elements are present for field order (+1.1.6)
	 * @return array The list version of date_format
	 **/
	public static function date_format_order( $required = array() ) {
		$format = get_option('date_format');

		$datefields = array('month' => 'F', 'day' => 'j', 'year' => 'Y');

		$tokens = array(
			'd' => 'day', 'D' => 'day', 'j' => 'day', 'l' => 'day',
			'F' => 'month', 'm' => 'month', 'M' => 'month', 'n' => 'month',
			'y' => 'year', 'Y' => 'year'
		);

		$s = 0;
		$_ = array();
		$format_tokens = str_split($format);
		foreach ( (array)$format_tokens as $token ) {
			if ( isset($tokens[ $token ]) )
				$_[ $tokens[ $token ] ] = $token;
			else $_[ 's' . $s++ ] = $token;
		}

		if ( ! empty($required) ) {
			$required = array_intersect_key($datefields, array_flip($required));
			$_ = array_merge($_, $required, $_);
		}

		return $_;
	}

	/**
	 * Generates a timestamp from a MySQL datetime format
	 *
	 * @since 1.0
	 *
	 * @param string $datetime A MySQL date time string
	 * @return int A timestamp number usable by PHP date functions
	 **/
	public static function mktimestamp( $datetime ) {
		$h = $mn = $s = 0;
		list($Y, $M, $D, $h, $mn, $s) = sscanf($datetime, "%d-%d-%d %d:%d:%d");
		if ( max($Y, $M, $D, $h, $mn, $s) == 0 )
			return 0;
		return mktime($h, $mn, $s, $M, $D, $Y);
	}

	/**
	 * Converts a timestamp number to an SQL datetime formatted string
	 *
	 * @since 1.0
	 *
	 * @param int $timestamp A timestamp number
	 * @return string An SQL datetime formatted string
	 **/
	public static function mkdatetime( $timestamp ) {
		return date("Y-m-d H:i:s", $timestamp);
	}

	/**
	 * Returns the 24-hour equivalent of a the Ante Meridiem or Post Meridiem hour
	 *
	 * @since 1.0
	 *
	 * @param int $hour The hour of the meridiem
	 * @param string $meridiem Specified meridiem of "AM" or "PM"
	 * @return int The 24-hour equivalent
	 **/
	public static function mk24hour( $hour, $meridiem ) {
		if ( $hour < 12 && $meridiem == 'PM' )
			return $hour + 12;
		if ( $hour == 12 && $meridiem == 'AM' )
			return 0;
		return (int) $hour;
	}

	/**
	 * Converts weight units from base setting to needed unit value
	 *
	 * @since 1.1
	 * @version 1.5
	 *
	 * @param float $value The value that needs converted
	 * @param string $unit The unit that we are converting to
	 * @param string $from (optional) The unit that we are converting from - defaults to system settings
	 * @return float|boolean The converted value, false on error
	 **/
	public static function convert_unit( $value = 0, $unit, $from = false ) {
		if ( $unit === $from || $value == 0 )
			return $value;

		$defaults = array(
			'mass' => shopp_setting('weight_unit'),
			'dimension' => shopp_setting('dimension_unit')
		);

		// Conversion table to International System of Units (SI)
		$table = apply_filters('shopp_unit_conversion_table', array(
			// SI base unit "grams"
			'mass' => array('lb' => 453.59237, 'oz' => 28.349523125, 'g' => 1, 'kg' => 1000),
			// SI base unit "meters"
			'dimension' => array('ft' => 0.3048, 'in' => 0.0254, 'mm' => 0.001, 'cm' => 0.01, 'm' => 1)
		));

		$charts = array_keys($table);
		foreach ( $charts as $chart )
			if ( isset($table[ $chart ][ $unit ]) )
				break;

		// If unit is unknown or the chart is unknown, return 0.
		if ( ! isset($table[ $chart ][ $unit ]) )
			return 0;

		// If we don't know about the unit to convert from use the system default
		if ( ! isset($table[ $chart ][ $from ]) )
			$from = $defaults[ $chart ];

		if ( $unit === $from )
			return $value;

		$siv = $value * $table[ $chart ][ $from ];	// Convert to SI unit value
		return $siv / $table[ $chart ][ $unit ];	// Return target units
	}

	/**
	 * Automatically generates a list of numeric ranges distributed across a number set
	 *
	 * @since 1.0
	 * @version 1.5
	 *
	 * @param int $avg Mean average number in the distribution
	 * @param int $max The max number in the distribution
	 * @param int $min The minimum in the distribution
	 * @param int $values The number of ranges to generate
	 * @return array A list of number ranges
	 **/
	public static function auto_ranges( $avg, $max, $min, $values ) {
		$ranges = array();
		if ( $avg == 0 || $max == 0 )
			return $ranges;

		$power = floor( log10($avg) );
		$scale = pow(10, $power);
		$mean = round( $avg / $scale ) * $scale;
		$range = $max - $min;

		if ( $range == 0 )
			return $ranges;

		$steps = min(7, $values); // No more than 7 steps
		if ( $steps < 2 ) {
			$scale = $scale / 2;
			$steps = max(2, min(7, ceil( $range / $scale ) ));
		}

		$base = max( $mean - ( $scale * floor(( $steps - 1 ) / 2 ) ), $scale);

		$ranges[0] = array('min' => 0, 'max' => $base);
		for ( $i = 1; $i < $steps; $i++ ) {
			$range = array('min' => $base, 'max' => $base + $scale);
			if ( $i + 1 >= $steps )
				$range['max'] = 0;
			$ranges[] = $range;
			$base += $scale;
		}

		return $ranges;
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
	 * @since 1.0
	 * @version 1.3
	 *
	 * @param float $amount The amount to be formatted
	 * @param array $format The currency format to use
	 * @return string The formatted amount
	 **/
	public static function money( $amount, array $format = array() ) {
		$format = apply_filters('shopp_money_format', Shopp::currency_format($format) );
		extract($format, EXTR_SKIP);

		$amount = apply_filters('shopp_money_amount', $amount);
		$number = Shopp::numeric_format(abs($amount), $precision, $decimals, $thousands, $grouping);

		if ( $cpos )
			return ( $amount < 0 ? '-' : '' ) . $currency . $number;
		else return $number . $currency;
	}

	/**
	 * Formats a number with typographically accurate multi-byte separators and variable algorisms
	 *
	 * @since 1.1
	 *
	 * @param float $number A floating point or integer to format
	 * @param int $precision (optional) The number of decimal precision to format to [default: 2]
	 * @param string $decimals The decimal separator character [default: .]
	 * @param string $separator The number grouping separator character [default: ,]
	 * @param int|array $grouping The number grouping pattern [default: array(3)]
	 * @return string The formatted number
	 **/
	public static function numeric_format( $number, $precision = 2, $decimals = '.', $separator=',', $grouping = array(3) ) {
		$n = sprintf("%0.{$precision}F", $number);
		$whole = $fraction = 0;

		if ( strpos($n, '.') !== false )
			list($whole, $fraction) = explode('.', $n);
		else $whole = $n;

		if ( ! is_array($grouping) )
			$grouping = array($grouping);

		$i = 0;
		$lg = count($grouping) - 1;
		$ng = array();
		while( strlen($whole) > $grouping[ min($i, $lg) ] && ! empty($grouping[ min($i, $lg) ]) ) {
			$divide = strlen($whole) - $grouping[ min($i++, $lg) ];
			$sequence = $whole;
			$whole = substr($sequence, 0, $divide);
			array_unshift($ng, substr($sequence, $divide));
		}
		if ( ! empty($whole) )
			array_unshift($ng, $whole);

		$whole = join($separator, $ng);
		$whole = str_pad($whole, 1, '0');

		$fraction = rtrim(substr($fraction, 0, $precision), '0');
		$fraction = str_pad($fraction, $precision, '0');

		$n = $whole . ( ! empty($fraction) ? $decimals . $fraction : '');

		return $n;
	}

	/**
	 * Parse a US or Canadian telephone number
	 *
	 * @since 1.2
	 *
	 * @param int $num The number to format
	 * @return array A list of phone number components
	 **/
	public static function parse_phone( $num ) {
		if ( empty($num) ) return '';
		$raw = preg_replace('/[^\d]/', '', $num);

		if ( strlen($raw) == 7 )
			sscanf($raw, "%3s%4s", $prefix, $exchange);
		if ( strlen($raw) == 10 )
			sscanf($raw, "%3s%3s%4s", $area, $prefix, $exchange);
		if ( strlen($raw) == 11 )
			APCIteratorsscanf($raw, "%1s%3s%3s%4s", $country, $area, $prefix, $exchange);

		return compact('country', 'area', 'prefix', 'exchange', 'raw');
	}

	/**
	 * Formats a number to telephone number style
	 *
	 * @since 1.0
	 *
	 * @param int $num The number to format
	 * @return string The formatted telephone number
	 **/
	public static function phone( $num ) {
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
	 * @since 1.0
	 *
	 * @param float $amount The amount to format
	 * @param array $format A specific format for the number
	 * @return string The formatted percentage
	 **/
	public static function percentage( $amount, $format = array() ) {
		$format = Shopp::currency_format($format);
		extract($format, EXTR_SKIP);

		$float = Shopp::floatval($amount, true, $format);
		$percent = Shopp::numeric_format($float, $precision, $decimals, $thousands, $grouping);
		if ( false !== strpos($percent, $decimals) ) // Only remove trailing 0's after the decimal
			$percent = rtrim(rtrim($percent, '0'), $decimals);
		return "$percent%";
	}

	/**
	 * Rounds a price amount with the store's currency format
	 *
	 * @since 1.1
	 *
	 * @param float $amount The number to be rounded
	 * @param array $format (optional) The formatting settings to use,
	 * @return float The rounded float
	 **/
	public static function roundprice( $amount, $format = array() ) {
		$format = Shopp::currency_format($format);
		extract($format, EXTR_SKIP);
		return round($amount, $precision);
	}


	/**
	 * Converts bytes to the largest applicable human readable unit
	 *
	 * Supports up to petabyte sizes
	 *
	 * @since 1.0
	 *
	 * @param int $bytes The number of bytes
	 * @return string The formatted unit size
	 **/
	public static function readableFileSize( $bytes, $precision = 1 ) {
		$units = array(Shopp::__('bytes'), 'KB', 'MB', 'GB', 'TB', 'PB');
		$sized = $bytes * 1;
		if ( $sized == 0 )
			return $sized;
		$unit = 0;
		while ( $sized >= 1024 && ++$unit )
			$sized = $sized / 1024;
		return round($sized, $precision) . " " . $units[ $unit ];
	}

}
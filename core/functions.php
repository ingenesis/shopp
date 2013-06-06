<?php
/**
 * functions.php
 * A library of global utility functions for Shopp
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, November 18, 2009
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

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
function _d($format,$timestamp=false) {
	$tokens = array(
		'D' => array('Mon' => __('Mon','Shopp'),'Tue' => __('Tue','Shopp'),
					'Wed' => __('Wed','Shopp'),'Thu' => __('Thu','Shopp'),
					'Fri' => __('Fri','Shopp'),'Sat' => __('Sat','Shopp'),
					'Sun' => __('Sun','Shopp')),
		'l' => array('Monday' => __('Monday','Shopp'),'Tuesday' => __('Tuesday','Shopp'),
					'Wednesday' => __('Wednesday','Shopp'),'Thursday' => __('Thursday','Shopp'),
					'Friday' => __('Friday','Shopp'),'Saturday' => __('Saturday','Shopp'),
					'Sunday' => __('Sunday','Shopp')),
		'F' => array('January' => __('January','Shopp'),'February' => __('February','Shopp'),
					'March' => __('March','Shopp'),'April' => __('April','Shopp'),
					'May' => __('May','Shopp'),'June' => __('June','Shopp'),
					'July' => __('July','Shopp'),'August' => __('August','Shopp'),
					'September' => __('September','Shopp'),'October' => __('October','Shopp'),
					'November' => __('November','Shopp'),'December' => __('December','Shopp')),
		'M' => array('Jan' => __('Jan','Shopp'),'Feb' => __('Feb','Shopp'),
					'Mar' => __('Mar','Shopp'),'Apr' => __('Apr','Shopp'),
					'May' => __('May','Shopp'),'Jun' => __('Jun','Shopp'),
					'Jul' => __('Jul','Shopp'),'Aug' => __('Aug','Shopp'),
					'Sep' => __('Sep','Shopp'),'Oct' => __('Oct','Shopp'),
					'Nov' => __('Nov','Shopp'),'Dec' => __('Dec','Shopp'))
	);

	if (!$timestamp) $date = date($format);
	else $date = date($format,$timestamp);

	foreach ($tokens as $token => $strings) {
		if ($pos = strpos($format,$token) === false) continue;
		$string = (!$timestamp)?date($token):date($token,$timestamp);
		$date = str_replace($string,$strings[$string],$date);
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
 * @param string $domain Optional. Domain to retrieve the translated text
 * @return void
 **/
function _jse ( $text, $domain = 'default' ) {
	echo json_encode(translate( $text, $domain ));
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
function _object_r ($object) {
	$Shopp = Shopp::object();
	ob_start();
	print_r($object);
	$result = ob_get_contents();
	ob_end_clean();
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
if ( ! function_exists('_var_dump') ) {
	function _var_dump() {
		$args = func_get_args();
		ob_start();
		var_dump($args);
		$ret_val = ob_get_contents();
		ob_end_clean();
		return $ret_val;
	}
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
function add_query_string ($string,$url) {
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
function add_storefrontjs ($script,$global=false) {
	$Storefront =& ShoppStorefront();
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
function array_filter_keys ($array,$mask) {
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
function auto_ranges ($avg, $max, $min, $values) {

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
function convert_unit ($value = 0, $unit, $from=false) {
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
function copy_shopp_templates ($src,$target) {
	$builtin = array_filter(scandir($src),"filter_dotfiles");
	foreach ($builtin as $template) {
		$target_file = $target.'/'.$template;
		if (!file_exists($target_file)) {
			$src_file = file_get_contents($src.'/'.$template);
			$file = fopen($target_file,'w');
			$src_file = preg_replace('/^<\?php\s\/\*\*\s+(.*?\s)*?\*\*\/\s\?>\s/','',$src_file); // strip warning comments

			/* Translate Strings @since 1.1 */
			$src_file = preg_replace_callback('/\<\?php _(e)\(\'(.*?)\',\'Shopp\'\); \?\>/','preg_e_callback',$src_file);
			$src_file = preg_replace_callback('/_(_)\(\'(.*?)\',\'Shopp\'\)/','preg_e_callback',$src_file);
			$src_file = preg_replace('/\'\.\'/','',$src_file);

			fwrite($file,$src_file);
			fclose($file);
			chmod($target_file,0666);
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
function crc16 ($data) {
	$crc = 0xFFFF;
	for ($i = 0; $i < strlen($data); $i++) {
		$x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
		$x ^= $x >> 4;
		$crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
	}
	return $crc;
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
function remove_class_actions ( $tags = false, $class = 'stdClass', $priority = false ) {
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
function currency_format ($format=false) {
	$default = array("cpos"=>true,"currency"=>"$","precision"=>2,"decimals"=>".","thousands" => ",","grouping"=>3);
	$locale = shopp_setting('base_operations');

	if (!isset($locale['currency']) || !isset($locale['currency']['format'])) return $default;
	if (empty($locale['currency']['format']['currency'])) return $default;

	$f = array_merge($default,$locale['currency']['format']);
	if ($format !== false) $f = array_merge($f,(array)$format);
	return $f;
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
function datecalc($week=-1,$dayOfWeek=-1,$month=-1,$year=-1) {
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
function date_format_order ($fields=false) {
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

function debug_caller () {
	$backtrace  = debug_backtrace();
	$stack = array();

	foreach ( $backtrace as $caller )
		$stack[] = isset( $caller['class'] ) ?
			"{$caller['class']}->{$caller['function']}"
			: $caller['function'];

	return join( ', ', $stack );

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
function duration ($start,$end) {
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
function esc_attrs ($value) {
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
function filter_dotfiles ($name) {
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
 **/
function find_filepath ($filename, $directory, $root, &$found) {
	if (is_dir($directory)) {
		$Directory = @dir($directory);
		if ($Directory) {
			while (( $file = $Directory->read() ) !== false) {
				if (substr($file,0,1) == "." || substr($file,0,1) == "_") continue;				// Ignore .dot files and _directories
				if (is_dir($directory.'/'.$file) && $directory == $root)		// Scan one deep more than root
					find_filepath($filename,$directory.'/'.$file,$root, $found);	// but avoid recursive scans
				elseif ($file == $filename)
					$found[] = substr($directory,strlen($root)).'/'.$file;		// Add the file to the found list
			}
			return true;
		}
	}
	return false;
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
function file_mimetype ($file,$name=false) {
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
		case 'swf':	return 'application/x-shockwave-flash';

		// images
		case 'jpg': case 'jpeg': case 'jpe': return 'image/jpg';
		case 'png': case 'gif': case 'bmp': case 'tiff': return 'image/'.strtolower($matches[1]);
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
function floatvalue ($value, $round=true, $format=false) {
	$format = currency_format($format);
	extract($format,EXTR_SKIP);

	$float = false;
	if (is_float($value)) $float = $value;

	$value = preg_replace('/(\D\.|[^\d\,\.\-])/','',$value); // Remove any non-numeric string data
	$value = preg_replace('/\\'.$thousands.'/','',$value); // Remove thousands
	$v = (float)$value;

	if ('.' == $decimals && $v > 0) $float = $v;

	if (false === $float) {
		$value = preg_replace('/^\./','',$value); // Remove any decimals at the beginning of the string
		if ($precision > 0) // Don't convert decimals if not required
			$value = preg_replace('/\\'.$decimals.'/','.',$value); // Convert decimal delimter

		$float = (float)$value;
	}

	return $round?round($float,$precision):$float;
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
function force_ssl ($url,$rewrite=false) {
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
function gateway_path ($file) {
	return basename(dirname($file)).'/'.basename($file);
}

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
if (!function_exists('href_add_query_arg')) {
	function href_add_query_arg () {
		$args = func_get_args();
		$url = call_user_func_array('add_query_arg',$args);
		list($uri,$query) = explode("?",$url);
		return $uri.'?'.htmlspecialchars($query);
	}
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
function ini_size ($name) {
	$setting = ini_get($name);
	if (preg_match('/\d+\w+/',$setting) !== false) return $setting;
	else readableFileSize($setting);
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
function inputattrs ($options,$allowed=array()) {
	if (!is_array($options)) return "";
	if (empty($allowed)) {
		$allowed = array('autocomplete','accesskey','alt','checked','class','disabled','format',
			'minlength','maxlength','placeholder','readonly','required','size','src','tabindex','cols','rows',
			'title','value');
	}
	$string = "";
	$classes = "";

	if (isset($options['label']) && !isset($options['value'])) $options['value'] = $options['label'];
	foreach ($options as $key => $value) {
		if (!in_array($key,$allowed)) continue;
		switch($key) {
			case "class": $classes .= " $value"; break;
			case "checked":
				if (str_true($value)) $string .= ' checked="checked"';
				break;
			case "disabled":
				if (str_true($value)) {
					$classes .= " disabled";
					$string .= ' disabled="disabled"';
				}
				break;
			case "readonly":
				if (str_true($value)) {
					$classes .= " readonly";
					$string .= ' readonly="readonly"';
				}
				break;
			case "required": if (str_true($value)) $classes .= " required"; break;
			case "minlength": $classes .= " min$value"; break;
			case "format": $classes .= " $value"; break;
			default:
				$string .= ' '.$key.'="'.esc_attr($value).'"';
		}
	}
	if (!empty($classes)) $string .= ' class="'.esc_attr(trim($classes)).'"';
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
function is_robot() {
	$bots = array('Googlebot','TeomaAgent','Zyborg','Gulliver','Architext spider','FAST-WebCrawler','Slurp','Ask Jeeves','ia_archiver','Scooter','Mercator','crawler@fast','Crawler','InfoSeek sidewinder','Lycos_Spider_(T-Rex)','Fluffy the Spider','Ultraseek','MantraAgent','Moget','MuscatFerret','VoilaBot','Sleek Spider','KIT_Fireball','WebCrawler');
	if (!isset($_SERVER['HTTP_USER_AGENT'])) return apply_filters('shopp_agent_is_robot', true, '');
	foreach($bots as $bot)
		if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),strtolower($bot))) return apply_filters('shopp_agent_is_robot', true, esc_attr($_SERVER['HTTP_USER_AGENT']));
	return apply_filters('shopp_agent_is_robot', false, esc_attr($_SERVER['HTTP_USER_AGENT']));
}

/**
 * Used to test user level for deprecated SHOPP_USERLEVEL macro
 *
 * Utility function for checking to see if SHOPP_USERLEVEL is defined and whether current user has
 * that level of access. This function is deprecated, and should not be used.
 *
 * @author John Dillick
 * @since 1.1
 * @deprecated 1.2
 *
 * @return null
 **/
function is_shopp_userlevel () { return; }


/**
 * @deprecated Using WP function instead
 **/
function is_shopp_secure () {
	return is_ssl();
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
function linkencode ($url) {
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
function locate_shopp_template ($template_names, $load = false, $require_once = false ) {
	if ( !is_array($template_names) ) return '';

	$located = '';

	if ('off' != shopp_setting('theme_templates')) {
		$templates = array_map('shopp_template_prefix',$template_names);
		$located = locate_template($templates,false);
	}

	if ('' == $located) {
		foreach ( $template_names as $template_name ) {
			if ( !$template_name ) continue;

			if ( file_exists(SHOPP_PATH . '/templates' . '/' . $template_name)) {
				$located = SHOPP_PATH . '/templates' . '/' . $template_name;
				break;
			}

		}
	}

	if ( $load && '' != $located )
		load_template( $located, $require_once );

	return $located;
}

function lzw_compress ($s) {
	$code = 256;
	$dict = $out = array();
	$size = strlen($s);
	$w = $s{0};
	for ($i = 1; $i < $size; $i++) {
		$c = $s{$i};
		if (isset($dict[ $w.$c ])) $w .= $c;
		else {
			$out[] = strlen($w) > 1 ? $dict[$w] : $w{0};
			$dict[ $w.$c ] = $code++;
			$w = $c;
		}
	}
	$out[] = strlen($w) > 1 ? $dict[$w] : $w{0};
	$out = array_map('chr',$out);
	return join('',$out);
}

if (!function_exists('mkobject')) {
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

/**
 * Generates a timestamp from a MySQL datetime format
 *
 * @author Jonathan Davis
 * @since 1.0
 *
 * @param string $datetime A MySQL date time string
 * @return int A timestamp number usable by PHP date functions
 **/
function mktimestamp ($datetime) {
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
function mkdatetime ($timestamp) {
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
function mk24hour ($hour, $meridiem) {
	if ($hour < 12 && $meridiem == "PM") return $hour + 12;
	if ($hour == 12 && $meridiem == "AM") return 0;
	return $hour;
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
function menuoptions ($list,$selected=null,$values=false,$extend=false) {
	if (!is_array($list)) return "";

	$_ = array();
	// Extend the options if the selected value doesn't exist
	if ((!in_array($selected,$list) && !isset($list[$selected])) && $extend)
		$_[] = '<option value="'.esc_attr($selected).'">'.esc_html($selected).'</option>';
	foreach ($list as $value => $text) {

		$valueattr = $selectedattr = '';

		if ($values) $valueattr = ' value="'.esc_attr($value).'"';
		if (($values && (string)$value === (string)$selected)
			|| (!$values && (string)$text === (string)$selected))
				$selectedattr = ' selected="selected"';
		if (is_array($text)) {
			$label = $value;
			$_[] = '<optgroup label="'.esc_attr($label).'">';
			$_[] = menuoptions($text,$selected,$values);
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
function money ($amount,$format=false) {
	$format = currency_format($format);
	$number = numeric_format($amount, $format['precision'], $format['decimals'], $format['thousands'], $format['grouping']);
	if ($format['cpos']) return $format['currency'].$number;
	else return $number.$format['currency'];
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
function numeric_format ($number, $precision=2, $decimals='.', $separator=',', $grouping=array(3)) {
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

function parse_phone ($num) {
	if (empty($num)) return '';
	$raw = preg_replace('/[^\d]/','',$num);

	if (strlen($raw) == 7) sscanf($raw, "%3s%4s", $prefix, $exchange);
	if (strlen($raw) == 10) sscanf($raw, "%3s%3s%4s", $area, $prefix, $exchange);
	if (strlen($raw) == 11) sscanf($raw, "%1s%3s%3s%4s",$country, $area, $prefix, $exchange);

	return compact('country','area','prefix','exchange','raw');
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
function phone ($num) {
	if (empty($num)) return '';
	$parsed = parse_phone($num);
	extract($parsed);

	$string = "";
	$string .= (isset($country))?"$country ":"";
	$string .= (isset($area))?"($area) ":"";
	$string .= (isset($prefix))?$prefix:"";
	$string .= (isset($exchange))?"-$exchange":"";
	$string .= (isset($ext))?" x$ext":"";
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
function percentage ($amount,$format=false) {
	$format = currency_format($format);
	extract($format,EXTR_SKIP);
	$float = floatvalue($amount,true,$format);
	$percent = numeric_format($float, $precision, $decimals, $thousands, $grouping);
	if (strpos($percent,$decimals) !== false) { // Only remove trailing 0's after the decimal
		$percent = rtrim($percent,'0');
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
 **/
function preg_e_callback ($matches) {
	return ($matches[1] == 'e') ? __($matches[2],'Shopp') : "'".__($matches[2],'Shopp')."'";
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
function raw_request_url () {
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
function readableFileSize($bytes,$precision=1) {
	$units = array(__('bytes','Shopp'),'KB','MB','GB','TB','PB');
	$sized = $bytes*1;
	if ($sized == 0) return $sized;
	$unit = 0;
	while ($sized >= 1024 && ++$unit) $sized = $sized/1024;
	return round($sized,$precision)." ".$units[$unit];
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
function roundprice ($amount,$format=false) {
	$format = currency_format($format);
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
function rsa_encrypt ($data, $pkey){
	openssl_public_encrypt($data, $encrypted,$pkey);
	return ($encrypted)?$encrypted:false;
}

if(!function_exists('sanitize_path')){
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
	};
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
function scan_money_format ($format) {
	$f = array(
		"cpos" => true,
		"currency" => "",
		"precision" => 0,
		"decimals" => "",
		"thousands" => "",
		"grouping" => 3
	);

	$ds = strpos($format,'#'); $de = strrpos($format,'#')+1;
	$df = substr($format,$ds,($de-$ds));

	$f['cpos'] = true;
	if ($de == strlen($format)) $f['currency'] = substr($format,0,$ds);
	else {
		$f['currency'] = substr($format,$de);
		$f['cpos'] = false;
	}

	$found = array();
	if (!preg_match_all('/([^#]+)/',$df,$found) || empty($found)) return $f;

	$dl = $found[0];
	$dd = 0; // Decimal digits

	if (count($dl) > 1) {
		if ($dl[0] == $dl[1] && !isset($dl[2])) {
			$f['thousands'] = $dl[1];
			$f['precision'] = 0;
		} else {
			$f['decimals'] = $dl[count($dl)-1];
			$f['thousands'] = $dl[0];
		}
	} else $f['decimals'] = $dl[0];

	$dfc = $df;
	// Count for precision
	if (!empty($f['decimals']) && strpos($df,$f['decimals']) !== false) {
		list($dfc,$dd) = explode($f['decimals'],$df);
		$f['precision'] = strlen($dd);
	}

	if (!empty($f['thousands']) && strpos($df,$f['thousands']) !== false) {
		$groupings = explode($f['thousands'],$dfc);
		$grouping = array();
		while (list($i,$g) = each($groupings))
			if (strlen($g) > 1) array_unshift($grouping,strlen($g));
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
function set_wp_query_var ($var,$value) {
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
function get_wp_query_var ($key) {
	global $wp;
	if (isset($wp->query_vars[$key]))
		return $wp->query_vars[$key];
}

/**
 * Wraps mark-up in a #shopp container, if needed
 *
 * @deprecated Use Storefront::wrapper() instead
 **/
function shoppdiv ($string) {
	return Storefront::wrapper($string);
}

function shopp_daytimes () {
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
 * @param string $template Email template file path
 * @param array $data The data to populate the template with
 * @return boolean True on success, false on failure
 **/
function shopp_email ($template,$data=array()) {

	$debug = false;
	$in_body = false;
	$headers = array();
	$message = '';
	$to = '';
	$subject = '';
	$protected = array('from','to','subject','cc','bcc');
	$replacements = array(
		"$" => "\\\$",		// Treat $ signs as literals
		"€" => "&euro;",	// Fix euro symbols
		"¥" => "&yen;",		// Fix yen symbols
		"£" => "&pound;",	// Fix pound symbols
		"¤" => "&curren;"	// Fix generic currency symbols
	);

	if (false == strpos($template,"\n") && file_exists($template)) {
		$templatefile = $template;
		// Include to parse the PHP and Theme API tags
		ob_start();
		include($templatefile);
		$template = ob_get_contents();
		ob_end_clean();

		if (empty($template))
			return shopp_add_error(__('Could not open the email template because the file does not exist or is not readable.','Shopp'),'email_template',SHOPP_ADMIN_ERR,array('template'=>$templatefile));

	}

	// Sanitize line endings
	$template = str_replace(array("\r\n","\r"),"\n",$template);
	$f = explode("\n",$template);

	while ( list($linenum,$line) = each($f) ) {
		$line = rtrim($line);
		// Data replacement
		if ( preg_match_all("/\[(.+?)\]/",$line,$labels,PREG_SET_ORDER) ) {
			while ( list($i,$label) = each($labels) ) {
				$code = $label[1];
				if (empty($data)) $string = (isset($_POST[$code])?$_POST[$code]:'');
				else $string = apply_filters('shopp_email_data', $data[$code], $code);

				$string = str_replace(array_keys($replacements),array_values($replacements),$string);

				if (isset($string) && !is_array($string)) $line = preg_replace("/\[".$code."\]/",$string,$line);
			}
		}

		// Header parse
		if (!$in_body && false !== strpos($line,':')) {
			list($header,$value) = explode(':',$line);

			// Protect against header injection
			if (in_array(strtolower($header),$protected))
				$value = str_replace("\n","",urldecode($value));

			if ( 'to' == strtolower($header) ) $to = $value;
			elseif ( 'subject' == strtolower($header) ) $subject = $value;
			else $headers[] = $line;
		}

		// Catches the first blank line to begin capturing message body
		if ( !$in_body && empty($line) ) $in_body = true;
		if ( $in_body ) $message .= $line."\n";
	}

	// Use only the email address, discard everything else
	if (strpos($to,'<') !== false) {
		list($name, $email) = explode('<',$to);
		$to = trim(rtrim($email,'>'));
	}

	// If not already in place, setup default system email filters
	new ShoppEmailDefaultFilters();

	// Message filters first
	$headers = apply_filters('shopp_email_headers',$headers,$message);
	$message = apply_filters('shopp_email_message',$message,$headers);

	if (!$debug) return wp_mail($to,$subject,$message,$headers);

	header('Content-type: text/plain');
	echo "To: ".htmlspecialchars($to)."\n";
	echo "Subject: $subject\n\n";
	echo "Headers:\n";
	print_r($headers);

	echo "\nMessage:\n$message\n";
	exit();
}

/**
 * Ties the key status and update key together
 *
 * @author Jonathan Davis
 * @since 1.2
 *
 * @return void
 **/
function shopp_keybind ($data) {
	if (!isset($data[1]) || empty($data[1])) $data[1] = str_repeat('0',40);
	return pack(Lookup::keyformat(true),$data[0],$data[1]);
}

/**
 * Generates RSS markup in XML from a set of provided data
 *
 * @author Jonathan Davis
 * @since 1.0
 * @deprecated Functionality moved to the Storefront
 *
 * @param array $data The data to populate the RSS feed with
 * @return string The RSS markup
 **/
function shopp_rss ($data) {
	// RSS filters
	add_filter('shopp_rss_description','convert_chars');
	add_filter('shopp_rss_description','ent2ncr');

	$xmlns = '';
	if (is_array($data['xmlns']))
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

	if (is_array($data['items'])) {
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
function shopp_pagename ($page) {
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
function shopp_parse_options ($options) {

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
function shopp_redirect ($uri,$exit=true,$status=302) {
	shopp_debug("Redirecting to: $uri");
	wp_redirect($uri,$status);
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
function shopp_safe_redirect($location, $status = 302) {

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
		$location = shoppurl(false,'account');

	wp_redirect($location, $status);
}

/**
 * Determines the current taxrate from the store settings and provided options
 *
 * Contextually works out if the tax rate applies or not based on storefront
 * settings and the provided override options
 *
 * @author Jonathan Davis
 * @since 1.0
 *
 * @param string $override (optional) Specifies whether to override the default taxrate behavior
 * @param string $taxprice (optional) Supports a secondary contextual override
 * @return float The determined tax rate
 **/
function shopp_taxrate ($override=null,$taxprice=true,$Item=false) {
	$Taxes = new CartTax();
	$rated = false;
	$taxrate = 0;

	if ( shopp_setting_enabled('tax_inclusive') ) $rated = true;
	if ( ! is_null($override) ) $rated = $override;
	if ( ! str_true($taxprice) ) $rated = false;

	if ($rated) $taxrate = $Taxes->rate($Item);
	return $taxrate;
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
function shopp_template_prefix ($name) {
	return apply_filters('shopp_template_directory','shopp').'/'.$name;
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
function shopp_template_url ($name) {
	$themepath = get_stylesheet_directory();
	$themeuri = get_stylesheet_directory_uri();
	$builtin = SHOPP_PLUGINURI.'/templates';
	$template = rtrim(shopp_template_prefix(''),'/');

	$path = "$themepath/$template";

	if ('off' != shopp_setting('theme_templates')
			&& is_dir(sanitize_path( $path )) )
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
function shoppurl ($request=false,$page='catalog',$secure=null) {
	global $is_IIS;

	$structure = get_option('permalink_structure');
	$prettyurls = ('' != $structure);

	// Support IIS index.php/ prefixed permalinks
	if ( $is_IIS && 0 === strpos($structure,'/index.php/') )
		$path[] = 'index.php';

	$path[] = ShoppPages()->baseslug();

	// Build request path based on Storefront shopp_page requested
	if ('images' == $page) {
		$path[] = 'images';
		if (!$prettyurls) $request = array('siid'=>$request);
	} else {
		if ('confirm-order' == $page) $page = 'confirm'; // For compatibility with 1.1 addons
		if (false !== $page) {
			$Page = ShoppPages()->get($page);
			$page_slug = $Page->slug();
		}
		if ($page != 'catalog') {
			if (!empty($page_slug)) $path[] = $page_slug;
		}
	}

	// Change the URL scheme as necessary
	$scheme = null; // Full-auto
	if ($secure === false) $scheme = 'http'; // Contextually forced off
	elseif (($secure || is_ssl()) && !SHOPP_NOSSL) $scheme = 'https'; // HTTPS required

	$url = home_url(false,$scheme);
	if ($prettyurls) $url = home_url(join('/',$path),$scheme);
	if (strpos($url,'?') !== false) list($url,$query) = explode('?',$url);
	$url = trailingslashit($url);

	if (!empty($query)) {
		parse_str($query,$home_queryvars);
		if ($request === false) {
			$request = array();
			$request = array_merge($home_queryvars,$request);
		} else {
			$request = array($request);
			array_push($request,$home_queryvars);
		}
	}

	if (!$prettyurls) $url = isset($page_slug)?add_query_arg('shopp_page',$page_slug,$url):$url;

	// No extra request, return the complete URL
	if (!$request) return apply_filters('shopp_url',$url);

	// Filter URI request
	$uri = false;
	if (!is_array($request)) $uri = urldecode($request);
	if (is_array($request) && isset($request[0])) $uri = array_shift($request);
	if (!empty($uri)) $uri = join('/',array_map('urlencode',explode('/',$uri))); // sanitize

	$url = user_trailingslashit($url.$uri);

	if (!empty($request) && is_array($request)) {
		$request = array_map('urldecode',$request);
		$request = array_map('urlencode',$request);
		$url = add_query_arg($request,$url);
	}

	return apply_filters('shopp_url',$url);
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
 **/
function sort_tree ($items,$parent=0,$key=-1,$depth=-1) {
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
				$children = sort_tree($items, $item->id, count($result)-1, $depth);
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
function str_true ( $string, $istrue = array('yes', 'y', 'true','1','on','open') ) {
	if (is_array($string)) return false;
	if (is_bool($string)) return $string;
	return in_array(strtolower($string),$istrue);
}

/**
 * @deprecated
 **/
function value_is_true ($value) {
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
function valid_input ($type) {
	$inputs = array("text","hidden","checkbox","radio","button","submit");
	if (in_array($type,$inputs) !== false) return true;
	return false;
}

/**
 * Variable sprintf rendering for Shopp gettext wrappers
 *
 * @author Jonathan Davis
 * @since 1.3
 *
 * @param string $text The text to render with sprintf
 * @param array $args The argument list from the calling function
 * @return string The sprintf rendered string
 **/
function sprintf_gettext ( string $text, array $args, integer $parameters = null ) {
	if ( is_null($parameters) ) $parameters = 1;

	if ( count($args) > $parameters ) {
	    $args = array_slice($args, $parameters);
	  	return vsprintf($text, $args);
	}

	return $text; // Do nothing
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
	  if( ! class_exists($classname,false) ) return;
	  if( ! property_exists($classname, $property) ) return;

	  $vars = get_class_vars($classname);
	  return $vars[$property];
	}
}

if ( defined('SHOPP_PROXY_CONNECT') && SHOPP_PROXY_CONNECT ) {
	/**
	 * Converts legacy Shopp proxy config macros to WP_HTTP_Proxy config macros
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @deprecated SHOPP_PROXY_CONNECT
	 * @deprecated SHOPP_PROXY_SERVER
	 * @deprecated SHOPP_PROXY_USERPWD
	 *
	 * @return void
	 **/
	function shopp_convert_proxy_config () {
		if ( ! defined('SHOPP_PROXY_SERVER') || ! defined('SHOPP_PROXY_USERPWD') ) return;

		$host = SHOPP_PROXY_SERVER;
		$user = SHOPP_PROXY_USERPWD;

		if ( false !== strpos($host,':') ) list($host,$port) = explode(':',$host);
		if ( false !== strpos($user,':') ) list($user,$pwd) = explode(':',$user);

		if ( ! defined('WP_PROXY_HOST') )				define('WP_PROXY_HOST',$host);
		if ( ! defined('WP_PROXY_PORT') && $port )		define('WP_PROXY_PORT',$port);
		if ( ! defined('WP_PROXY_USERNAME') )			define('WP_PROXY_USERNAME',$user);
		if ( ! defined('WP_PROXY_PASSWORD') && $pwd )	define('WP_PROXY_PASSWORD',$pwd);
	}
	shopp_convert_proxy_config();
}

if ( ! function_exists('shopp_suhosin_warning') ) {
	/**
	 * Detect Suhosin enabled with problematic settings
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void Description...
	 **/
	function shopp_suhosin_warning () {

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
}

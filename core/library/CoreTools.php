<?php
/**
 * CoreTools.php
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

/** @var SHOPP_CLEAR_PNG Defines a transparent PNG image string suitable for use as an image src */
define('SHOPP_CLEAR_PNG', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA1BMVEX///+nxBvIAAAAAXRSTlMAQObYZgAAABRJREFUeF6VwIEAAAAAgKD9qWeo0AAwAAEnvySkAAAAAElFTkSuQmCC');

abstract class ShoppCoreTools {

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
	 * Trim whitespace from the beginning
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
	 * @since 1.5
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
	 * @since 1.5
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
	 * @since 1.5
	 *
	 * @return void
	 */
	public static function invalidate_cache() {
		wp_cache_incr( 'shopp_cache_key' );

		do_action('shopp_invalidate_cache');
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
	 * Recursively searches a nested array and returns the matching key
	 *
	 * @author Jonathan Davis
	 * @since 1.5
	 *
	 * @param string $needle The string to find
	 * @param array $haystack The array to search
	 * @return string The matching key
	 **/
	public static function array_search_deep ( $needle, array $haystack = array() ) {
		if ( empty($haystack) ) return false;

	    foreach ( $haystack as $key => $value ) {
	        if ( $needle === $value || ( is_array($value) && self::array_search_deep($needle, $value) !== false ) )
	            return $key;
	    }
	    return false;
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
	 * remove_class_actions
	 *
	 * Removes all WordPress actions/filters registered by a particular class or its children.
	 *
	 * @author John Dillick
	 * @since 1.@since 1.5.1
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
	 * Supports deprecated functions
	 *
	 * @since 
	 * 
	 * @return void Description...
	 **/
	public static function deprecated() {
		$args = func_get_args();
        if ( empty($args) )
            return;
        $method = array_shift($args);
        if ( method_exists('Shopp', $method) )
            return call_user_func_array(array('Shopp', $method), $args);
	}

}
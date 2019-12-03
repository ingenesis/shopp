<?php
/**
 * FSStorage
 *
 * Provides file system storage of store assets
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February 2010-2014
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.1
 * @subpackage FSStorage
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( ! defined('WP_CONTENT_DIR') ) define('WP_CONTENT_DIR', ABSPATH . 'wp-content');

/**
 * FSStorage
 *
 * Note that storage modules cannot use ShoppError for logging errors
 * as they are used in another context where WordPress is not fully loaded.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class FSStorage extends StorageModule implements StorageEngine {

	public $path = '';
    public $webroot = '';

	/**
	 * FSStorage constructor
	 */
	public function __construct () {
		parent::__construct();
		$this->name = Shopp::__('File system');
        $this->webroot = apply_filters('shopp_fsstorage_webroot', $_SERVER['DOCUMENT_ROOT']);
	}


	public function actions () {
		add_action('wp_ajax_shopp_storage_suggestions', array(&$this, 'suggestions'));
 		add_filter('shopp_verify_stored_file', array(&$this, 'verify'));

		// Override access checks when resuming a previous download
		if (isset($_SERVER['HTTP_RANGE']) && !empty($_SERVER['HTTP_RANGE']))
			add_filter( 'shopp_download_forbidden', '__return_false' );
	}

	public function context ( $context ) {
		chdir(WP_CONTENT_DIR);
		$this->context = $context;
		if (isset($this->settings['path'][ $context ]))
			$this->path = realpath($this->settings['path'][ $context ]);
	}

	public function save ( $asset, $data, $type = 'binary' ) {

		$error = false;
		if (empty($data)) $error = "$this->module: There is no file data to store.";
		
		$filepath = self::sanitize($this->path . '/' . $asset->filename);
		switch ( $type ) {
			case 'upload':
				if (move_uploaded_file($data, $filepath)) break;
				else $error = "$this->module: Could not move the uploaded file to the storage repository.";
				$buffer = ob_get_contents();
				break;
			case 'file':
				if ( ! is_readable($data) ) $error = "$this->module: Could not read the file.";
				elseif (copy($data, $filepath)) break;
				else $error = "$this->module: Could not move the file to the storage repository.";
				break;
			default:
				if (file_put_contents($filepath, $data) > 0) break;
				else $error = "$this->module: Could not store the file data.";
		}

		if ( $error ) {
			$error = new ShoppError($error, 'storage_engine_save', SHOPP_ADMIN_ERR);
			return $error;
		}
		
		// Set correct file permissions
		$filestat = stat($filepath);
		$perms = $filestat['mode'] & 0000666;
		@ chmod( $filepath, $perms );
		
		return $asset->filename;
		
	}

	public function exists ( $uri ) {
		$filepath = self::sanitize($this->path . "/" . $uri);
		return (file_exists($filepath) && is_readable($filepath));
	}

	public function load ( $uri ) {
		return file_get_contents(self::sanitize($this->path . '/' . $uri));
	}

	public function meta ( $uri = false, $null = false ) {
		$_ = array();
		$_['size'] = filesize(self::sanitize($this->path.'/'.$uri));
		$_['mime'] = file_mimetype(self::sanitize($this->path.'/'.$uri));
		return $_;
	}

	public function output ( $uri, $etag = false ) {
		$filepath = self::sanitize($this->path . '/' . $uri);

		if ($this->context == "download") {
			if ( ! is_file($filepath) ) {
				header("Status: 404 Forbidden");  // File not found?!
				return false;
			}

			$size = @filesize($filepath);
			$modified = @filemtime($filepath);

			$range = ''; $start = ''; $end = '';
			// Handle resumable downloads
			if ( isset($_SERVER['HTTP_RANGE']) ) {
				list($units, $reqrange) = explode('=', $_SERVER['HTTP_RANGE'], 2);
				if ( $units == 'bytes' ) {
					// Use first range - http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
					list($range, $extra) = explode(',', $reqrange, 2);
					// Determine download chunk to grab
					if ( ! empty($range) ) list($start, $end) = explode('-', $range, 2);

				}
			}

		    // Set start and end based on range (if set), or set defaults
		    // also check for invalid ranges.
		    $end = ( empty($end) ) ? ($size - 1) : min(abs(intval($end)), ($size - 1));
		    $start = ( empty($start) || $end < abs(intval($start)) ) ? 0 : max(abs(intval($start)), 0);

	        // Only send partial content header if downloading a piece of the file (IE workaround)
	        if ( $start > 0 || $end < ($size - 1) ) header('HTTP/1.1 206 Partial Content');

	        header('Accept-Ranges: bytes');
	        header('Content-Range: bytes '.$start . '-' . $end . '/' . $size);
		    header('Content-length: ' . ($end - $start + 1));

			// WebKit/Safari resumable download support headers
		    header('Last-modified: ' . date('D, d M Y H:i:s O', $modified));
			if ( isset($etag) ) header('ETag: ' . $etag);

			$file = fopen($filepath, 'rb');
			fseek($file, $start);

			// Detmerine memory available for optimum packet size
			$packet = $limit = $memory = ini_get('memory_limit');
			sscanf($limit, '%d%s', $limit, $unit);
			switch ($unit{0}) {
			    case 'G': case 'g': $limit *= 1073741824; break;
			    case 'M': case 'm': $limit *= 1048576; break;
			    case 'K': case 'k': $limit *= 1024; break;
			}
			$memory = $limit - memory_get_usage(true);

			// Use 90% of availble memory for read buffer size, 4K minimum (less chunks, less overhead, faster throughput)
			$packet = max(4096,apply_filters('shopp_fsstorage_download_read_buffer', floor($memory*0.9)));
			$packet = ( $packet > 8192 ) ? 8192 : $packet;

			while( ! feof($file) && connection_status() == 0 ) {
				$buffer = fread($file, $packet);
				echo $buffer;	// Output
				unset($buffer); // Free memory immediately
 				flush();		// Flush output to web server
			}
			fclose($file);
		} else readfile($filepath);
	}

	public function settings ( $context ) {
		$error = false;
		chdir(WP_CONTENT_DIR);

		if ( ! is_array($this->settings) )
			$this->settings = array('path' => array('image' => false, 'download' => false));

		foreach ( $this->settings['path'] as $method => &$path ) {
			$error = false;
			$path = stripslashes($path);
			$p = self::sanitize(realpath($path));

				if	( empty($path) ) continue;
			elseif	( ! file_exists($p) ) $error = Shopp::__('The path does not exist.');
			elseif	( ! is_dir($p) ) $error = Shopp::__('The path supplied is not a directory.');
			elseif	( ! is_writable($p) ) $error = Shopp::__('The path must be **writable** by the web server.');
			elseif	( ! is_readable($p) ) $error = Shopp::__('The path must be **readable** by the web server.');

			if ( $error !== false )
				$label[ $method ] = '<span class="error">' . $error . '</span>';

		}

		if ( ! isset($this->settings['path'][ $context ]) ) $this->settings['path'][ $context ] = false;
		$this->ui[ $context ]->text(0, array(
			'name'  => 'path',
			'value' => $this->settings['path'][ $context ],
			'size'  => 40,
			'label' => Shopp::__('The file system path to your storage directory.')
		));

	}

	public function suggestions () {
		if ( ! $this->handles('download') ) return;
		check_admin_referer('wp_ajax_shopp_storage_suggestions');
		if ( empty($_GET['q']) || strlen($_GET['q'] ) < 3 ) return;
		if ( $_GET['t'] == "image" ) $this->context('image');
		else $this->context('download');

		$Shopp = Shopp::object();
		if ( $Shopp->Storage->engines[ $this->context ] != $this->module ) return;

		$directory = false;	// The directory to search
		$search = false;	// The file name to search for
		$relpath = false;	// The related path
		$sep = DIRECTORY_SEPARATOR;

		$url = parse_url($_GET['q']);
		if ( (isset($url['scheme'] ) && $url['scheme'] != 'file') || !isset($url['path']) )
			return;

		$query = self::sanitize($url['path']);
		$search = basename($query);
		if ( strlen($search) < 3 ) return;

		if ( $url['scheme'] == "file" ) {
			$directory = dirname($query);
			$uri = array($url['scheme'] . ':', '', $directory);
			$relpath = join("/", $uri).'/';
		}
		if ( ! $directory && $query[0] == "/" ) $directory = dirname($query);
		if ( ! $directory ) {
			$directory = realpath($this->path . $sep . dirname($query));
			$relpath = dirname($query);
			$relpath = ( $relpath == "." ) ? false : $relpath . $sep;
		}

		$Directory = @dir($directory);
		if ( $Directory ) {
			while ( ( $file = $Directory->read() ) !== false ) {
				if ( substr($file,0,1) == "." || substr($file, 0 ,1) == "_" ) continue;
				if ( strpos(strtolower($file), strtolower($search)) === false ) continue;
				if ( is_dir($directory . $sep . $file) ) $results[] = $relpath . $file . $sep;
				else $results[] = $relpath . $file;
			}
		}
		echo join("\n", $results);
		exit();
	}

	public function verify ( $uri ) {
		if ( ! $this->handles('download') ) return $uri;

		$this->context('download');
		$path = trailingslashit(self::sanitize($this->path));

		$url = $path . $uri;
		if ( ! file_exists($url) ) die('NULL');
		if ( is_dir($url) ) die('ISDIR');
		if ( ! is_readable($url) ) die('READ');

		die('OK');
	}

    /**
     * Provides a direct URL for the file asset (if it can be determined).
     *
     * In atypical installations this may not work as expected - if so direct URLs can be turned off completely by
     * defining SHOPP_DIRECT_ASSET_URLS as false in wp-config.php. Note also that the web root as assumed to be the
     * same as ABSPATH but this can be modified using the "shopp_fsstorage_webroot" filter (see the constructor) ...
     * in practice this does not necessarily have to be the true web root, but the ability to modify it exists to handle
     * special cases.
     *
     * @param $uri
     * @return mixed bool | string
     */
    public function direct ( $uri ) {
        if ( defined('SHOPP_DIRECT_ASSET_URLS') && ! SHOPP_DIRECT_ASSET_URLS ) return false;
        $path = $this->finddirect($uri);
        return ( false === $path ) ? false : $path;
    }

	/**
	 * Tries to find the public URL for Shopp product images stored using the FSStorage engine. Not bulletproof, it
	 * assumes that either the directory is subordinate to the web root (assumed to be ABSPATH, however this is
	 * filterable) or is anyway relative to wp-content.
	 *
	 * If it cannot determine the public URL (in the case of cached images, for example, they may not have been cached
	 * to disk yet) it will return false.
	 *
	 * @param string $uri
	 * @return string | bool
	 */
	protected function finddirect ( $uri ) {
		$paths = array_map( // Normalize directory separators as URL-compliant forward-slashes
			array('self', 'sanitize'),
			array(
				// The web server's document root
				'webroot' => $this->webroot,

				// The base directory of WordPress
				'wpdir' => apply_filters( 'shopp_fsstorage_homedir', ABSPATH ),

				// Obtain the storage path or bail out early if it is not accessible/is invalid
				'storagepath' => $this->storagepath()
			)
		);
		extract($paths, EXTR_SKIP);

		// Obtain the storage path or bail out early if it is not accessible/is invalid
		if ( ! $storagepath ) return false;

		// The base URL (home URL) of the site
		$baseurl = untrailingslashit( apply_filters( 'shopp_fsstorage_homeurl', get_option( 'home' ) ) );

		// Ensure the file exists
		if ( ! file_exists( $storagepath . "/$uri" ) ) return false;

		// Determine if the storage path is inside the webroot (they should have the same initial set of "segments")
		if ( 0 != strpos( $storagepath, $webroot ) ) return false;

		// Is WordPress installed and accessed from within a subdirectory of webroot? Adjust $baseurl to remove the subdir if so
		if ( $webroot !== $wpdir && false !== strpos($wpdir, $webroot) ) {
			$path = untrailingslashit( str_replace( $webroot, '', $wpdir ) );
			$inpath = strrpos( $baseurl, $path );
			if ( $inpath ) $baseurl = substr( $baseurl, 0, $inpath );
		}

		// Determine if the storage path is under the WordPress directory and if so use it as the canonical webroot
		if ( false !== strpos($storagepath, $wpdir) && false === strpos($storagepath, $webroot) ) {
			$webroot = $wpdir;
		}

		// Supposing the image directory isn't the WP root, append the trailing component
		if ( $storagepath !== $webroot ) {
			$path = str_replace( trailingslashit( $webroot ), '', $storagepath );
			$url = trailingslashit( $baseurl ) . $path;
		} else $url = $baseurl;

		return trailingslashit( $url ) . $uri;
	}

	/**
	 * Returns the storage path: if it is relative to wp-content then it is expanded to a complete filepath. If a valid
	 * path can't be found (the directory doesn't exist or isn't accessible) bool false is returned.
	 *
	 * @return string | bool
	 */
	protected function storagepath () {
	    if ( is_dir($this->path) ) return $this->path;

		$wp_content_path = trailingslashit(WP_CONTENT_DIR) . $this->path;
		if ( is_dir($wp_content_path) ) return $wp_content_path;

		return false;
	}

    /**
     * Combines the object's base_url and uri properties (uri is dynamically assigned)
     * into a single directly accessible URL.
     */
    protected function set_direct_url () {
        if ( property_exists($this, 'uri') )
			$this->direct_url = $this->base_url . $this->uri;
    }

    static private function sanitize ( $path ) {
		return str_replace(DIRECTORY_SEPARATOR, '/', $path);
	}

} // END class FSStorage
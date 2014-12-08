<?php
/**
 * ImageServer
 * Provides low-overhead image service support
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 12 December, 2009
 * @package shopp
 * @subpackage image
 **/

// Reduce image display issues by hiding warnings/notices
ini_set('display_errors', 0);

if ( ! defined('SHORTINIT') ) define('SHORTINIT',true);
define('SHOPP_IMGSERVER_LOADED', true);

$path = ImageServer::path();

// Create a "stub" global Shopp object for use by Asset objects (as the $Shopp
// global will not otherwise be present for them to populate)
if ( ! isset($GLOBALS['Shopp']) ) $GLOBALS['Shopp'] = new stdClass;

// Make core Shopp functionality available
if ( ! defined('WPINC') ) define('WPINC', 'wp-includes'); // Stop 403s from unauthorized direct access

// Core functions and lazy loader
if ( ! class_exists('ShoppLoader') )
	require "$path/core/library/Loader.php";

// Barebones bootstrap (say that 5x fast) for WordPress
if ( ! defined('ABSPATH') && $loadfile = ShoppLoader::find_wpload()) {
	require($loadfile);
	global $table_prefix;
}


// Stub i18n for compatibility
if ( ! function_exists('__')) {
	// Localization API is not available at this point
	function __ ($string,$domain=false) {
		return $string;
	}
}

ShoppDeveloperAPI::load( dirname(ShoppLoader::basepath()), array('core','settings') );

// Start the server
new ImageServer;
exit;

/**
 * ImageServer class
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package image
 **/
class ImageServer {

	static $prettyurls = '{^/.+?/images/(\d+)/.*$}';

	private $caching = true;	// Set to false to force off image caching

	private $request = false;
	private $parameters = array();
	private $args = array('width','height','scale','sharpen','quality','fill');
	private $scaling = array('all','matte','crop','width','height');
	private $width;
	private $height;
	private $scale = 0;
	private $sharpen = 0;
	private $quality = 80;
	private $fill = false;
	private $valid = false;
	private $Image = false;

	public function __construct () {

		$this->setup();
		$this->request();

		if ( $this->load() )
			$this->render();
		else $this->error();

	}

	/**
	 * Parses the request to determine the image to load
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function request () {
		$matches = array();
		if ( isset($_GET['siid']) ) $request = $_GET['siid'];
		elseif ( 0 != preg_match(self::$prettyurls, $_SERVER['REQUEST_URI'], $matches) )
			$request = $matches[1];	// Get requested image from pretty URL format

		$this->request = apply_filters('shopp_imageserver_request', $request);

		if ( empty($this->request) ) return; // No valid image request, bail

		$clearpng = ( '000' == substr($this->request, 0, 3) );

		$args = array();
		foreach ( $_GET as $arg => $v ) {
			if ( false !== strpos($arg, ',') ) {
				$args = explode(',', $arg);
				if ( ! $clearpng )
					$this->valid = array_pop($args);
			}
		}

		$parameters = array_pad($args, count($this->args), '');
		$this->parameters = apply_filters('shopp_imageserver_parameters', array_combine($this->args, $parameters) );

		// Drop empty parameters
		$this->parameters = array_filter($this->parameters, array('ImageAsset', 'notempty'));

		// Setup properties
		foreach ( $this->parameters as $property => $value )
			if ( property_exists($this, $property) ) $this->$property = intval($value);


		if ( $this->height == 0 && $this->width > 0 ) $this->height = $this->width;
		if ( $this->width == 0 && $this->height > 0 ) $this->width = $this->height;

		$this->scale = $this->scaling[ $this->scale ];

		// Handle clear image requests (used in product gallery to reserve DOM dimensions)
		if ( $clearpng ) $this->clearpng();

	}

	/**
	 * Loads the requested image for display
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @return boolean Status of the image load
	 **/
	public function load () {

		$cache = 'image_' . $this->request . ($this->valid ? '_' . $this->valid : '');
		$cached = wp_cache_get($cache, 'shopp_image');
		if ( $cached ) return ($this->Image = $cached);

		$this->Image = new ImageAsset($this->request);

		if ( max($this->width, $this->height) > 0 ) $this->loadsized();

		wp_cache_set($cache, $this->Image, 'shopp_image');

		do_action('shopp_imageserver_load', $this->Image);

		if ( ! empty($this->Image->id) || ! empty($this->Image->data) ) return true;
		else return false;
	}

	public function loadsized () {
		// Same size requested, skip resizing
		if ( $this->Image->width == $this->width && $this->Image->height == $this->height ) return;

		$Cached = new ImageAsset(array(
			'parent' => $this->Image->id,
			'context'=>'image',
			'type'=>'image',
			'name'=>'cache_' . implode('_', $this->parameters)
		));

		// Use the cached version if it exists, otherwise resize the image
		if ( ! empty($Cached->id) && $this->caching ) $this->Image = $Cached;
		else $this->resize(); // No cached copy exists, recreate
	}

	public function resize () {

		if ( $this->valid != ImageAsset::checksum($this->Image->id, $this->parameters) ) {
			header('HTTP/1.1 401 Unauthorized');
			die('<h1>Not Authorized</h1>');
		}

		do_action('shopp_imageserver_preprocess', $this->Image, $this->parameters);

		$Resized = new ImageProcessor($this->Image->retrieve(), $this->Image->width, $this->Image->height);
		$scaled = $this->Image->scaled($this->width, $this->height, $this->scale);

		$alpha = ( 'image/png' == $this->Image->mime );
		if ( -1 == $this->fill ) $alpha = true;
		$Resized->scale($scaled['width'], $scaled['height'], $this->scale, $alpha, $this->fill);

		// Post sharpen
		if ( ! $alpha && $this->sharpen > 0 )
			$Resized->sharpen($this->sharpen);

		$ResizedImage = new ImageAsset();
		$ResizedImage->copydata($this->Image, false, array());
		$ResizedImage->name = 'cache_' . implode('_', $this->parameters);
		$ResizedImage->filename = $ResizedImage->name . '_' . $ResizedImage->filename;
		$ResizedImage->parent = $this->Image->id;
		$ResizedImage->context = 'image';
		$ResizedImage->mime = 'image/jpeg';
		$ResizedImage->id = false;
		$ResizedImage->width = $Resized->width();
		$ResizedImage->height = $Resized->height();
		$ResizedImage->settings = $this->parameters;

		do_action('shopp_imageserver_processed', $ResizedImage, $this->parameters);


		$ResizedImage->data = $Resized->imagefile($this->quality);


		if ( empty($ResizedImage->data) ) return false;

		$ResizedImage->size = strlen($ResizedImage->data);

		$this->Image = $ResizedImage;
		if ( false === $ResizedImage->store( $ResizedImage->data ) )
			return false;

		$ResizedImage->save();

	}

	/**
	 * Output the image to the browser
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @return void
	 **/
	public function render () {

		// Show the not found image if the image is not found
		$found = $this->Image->found();
		if ( ! $found ) return $this->error();

		// Handle image redirects (for cloud storage engines)
		$headers = ! ( is_array($found) && isset($found['redirect']) );

		// Output the image
		@ob_clean(); // try to catch errant data in buffer
		$this->Image->output($headers);
	}

	/**
	 * Output a default image when the requested image is not found
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @return void
	 **/
	public function error () {
		header("HTTP/1.1 404 Not Found");
		$notfound = ImageServer::path() . '/core/ui/icons/notfound.png';
		if ( defined('SHOPP_NOTFOUND_IMAGE') && file_exists(SHOPP_NOTFOUND_IMAGE) )
			$notfound = SHOPP_NOTFOUND_IMAGE;
		if ( ! file_exists($notfound)) die('<h1>404 Image Not Found</h1>');
		else {
			header( 'HTTP/1.1 404 Image Not Found' );
			$this->headers(basename($notfound), @filesize($notfound));
			@readfile($notfound);
		}
		die();
	}

	/**
	 * Renders a transparent PNG of the requested dimensions
	 *
	 * Used in the product gallery to reserve DOM dimensions so the
	 * gallery is rendered with the proper layout
	 *
	 * @author Jonathan Davis
	 * @since 1.1.7
	 *
	 * @return void
	 **/
	public function clearpng () {
		$max = 1920;
		$this->width = min($max,$this->width);
		$this->height = min($max,$this->height);
		$ImageData = new ImageProcessor(false,$this->width,$this->height);
		$ImageData->canvas($this->width,$this->height,true);
		$image = $ImageData->imagefile(100);
		$this->headers('clear.png', @strlen($image));
		ob_clean(); // try to catch errant data in buffer
		die($image);
	}

	/**
	 * Outputs uniform image server headers
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $file The name of the file
	 * @param int $length The size of the file in bytes (strlen)
	 * @return void
	 **/
	public function headers ( $file, $length ) {
		header("Cache-Control: no-cache, must-revalidate");
		header("Content-type: image/png");
		header("Content-Disposition: inline; filename=$file");
		header("Content-Description: Delivered by WordPress/Shopp Image Server");
		header("Content-length: $length");
	}

	/**
	 * Sets up the Shopp stub environment and ShoppSettings
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.3
	 *
	 * @return void
	 **/
	public function setup () {

		global $Shopp;

		if ( ! defined('SHOPP_PATH') )
			define('SHOPP_PATH', self::path() );
		if ( ! defined('SHOPP_STORAGE') )
			define('SHOPP_STORAGE', SHOPP_PATH . '/storage');
		if ( ! defined('SHOPP_ADDONS') )
			define('SHOPP_ADDONS', WP_CONTENT_DIR . '/shopp-addons');

		$Shopp->Storage = new StorageEngines();

		ShoppSettings();

		$modules = shopp_setting('imaging_modules');

		// Only load image modules if necessary
		if ( ! empty($modules) )
			new ShoppImagingModules();

	}

	public static function path () {
		return str_replace('\\', '/', realpath( dirname(dirname(__FILE__)) ) );
	}

}
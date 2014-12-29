<?php
/**
 * Asset.php
 *
 * Catalog assets classes (metadata, images, downloads)
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * FileAsset class
 *
 * Foundational class to provide a useable asset framework built on the meta
 * system introduced in Shopp 1.1.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage asset
 **/
class FileAsset extends ShoppMetaObject {

	public $mime;
	public $size;
	public $storage;
	public $uri;
	public $context = 'product';
	public $type = 'asset';
	public $_xcols = array('mime', 'size', 'storage', 'uri');

	public function __construct ( $id = false ) {
		$this->init(self::$table);
		$this->extensions();
		if ( ! $id ) return;
		$this->load($id);

		if ( ! empty($this->id) )
			$this->expopulate();
	}

	/**
	 * Populate extended fields loaded from the ShoppMetaObject
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function expopulate () {
		parent::expopulate();

		if ( is_string($this->uri) )
			$this->uri = stripslashes($this->uri);
	}

	/**
	 * Store the file data using the preferred storage engine
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param $data Data to save
	 * @param $type (optional) Type of data to save
	 * @return mixed
	 **/
	public function store ( $data, $type = 'binary' ) {
		$Engine = $this->engine();

		$saved = $Engine->save($this, $data, $type);
		if ( empty($saved) || is_a($saved, 'ShoppError') ) return false;

		$this->uri = $saved;
		return true;
	}

	/**
	 * Retrieve the resource data
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function retrieve () {
		$Engine = $this->engine();
		return $Engine->load($this->uri);
	}

	/**
	 * Retreive resource meta information
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function readmeta () {
		$Engine = $this->engine();
		list($this->size, $this->mime) = array_values($Engine->meta($this->uri, $this->name));
	}

	/**
	 * Determine if the resource exists
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function found ( $uri = false ) {
		if ( ! empty($this->data) ) return true;
		if ( ! $uri && ! $this->uri ) return false;
		if ( ! $uri ) $uri = $this->uri;
		$Engine = $this->engine();
		return $Engine->exists($uri);
	}

	/**
	 * Determine the storage engine to use
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 **/
	public function &engine () {
		global $Shopp; // Must remain global reference, not singleton to prevent loading core plugin in image server context

		if ( ! isset($Shopp->Storage) )	$Shopp->Storage = new StorageEngines;
		$StorageEngines = $Shopp->Storage;

		$Engine = false;
		if ( empty($this->storage) )
			$this->storage = $StorageEngines->type($this->type);

		$Engine = $StorageEngines->get($this->storage);

		if ( false === $Engine ) // If no engine found, force DBStorage (to provide a working StorageEngine to the Asset)
		$Engine = $StorageEngines->activate('DBStorage');

		if ( false === $Engine ) // If no engine is available at all, we're screwed.
		wp_die('No Storage Engine available. Cannot continue.');

		$Engine->context($this->type);

		return $Engine;
	}

	/**
	 * Stub for extensions
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function extensions () {
		/** Not Implemented **/
	}

	public static function mimetypes () {
		add_filter('mime_types', array(__CLASS__, 'xmime'));
	}

	public static function xmime ( array $mimetypes = array() ) {
		static $extensions = array(
			'ac3' => 'audio/ac3',
			'aif|aifc|aiff' => 'audio/x-aiff',
			'ai|eps|ps' => 'application/postscript',
			'au|snd' => 'audio/basic',
			'cat' => 'application/vnd.ms-pkiseccat',
			'clp' => 'application/x-msclip',
			'crd' => 'application/x-mscardfile',
			'dll' => 'application/x-msdownload',
			'doc|dot|word|w6w' => 'application/msword',
			'epub' => 'application/epub+zip',
			'gtar' => 'application/x-gtar',
			'ics|ifb' => 'text/calendar',
			'ief' => 'image/ief',
			'jpe|jpeg|jpg' => 'image/jpeg',
			'm13|m14|mvb' => 'application/x-msmediaview',
			'mny' => 'application/x-msmoney',
			'mobi' => 'application/x-mobipocket-ebook',
			'movie' => 'video/x-sgi-movie',
			'mp3' => 'audio/x-mpeg',
			'mp4' => 'video/mp4',
			'mpa' => 'audio/MPA',
			'mpe|mpeg|mpg' => 'video/mpeg',
			'msg' => 'application/vnd.ms-outlook',
			'pict' => 'image/pict',
			'pub' => 'application/x-mspublisher',
			'scd' => 'application/x-msschedule',
			'sst' => 'application/vnd.ms-pkicertstore',
			'stl' => 'application/vnd.ms-pkistl',
			'trm' => 'application/x-msterminal',
			'wmf' => 'application/x-msmetafile',
			'xla|xlc|xlm|xls|xlt|xlw' => 'application/vnd.ms-excel'
		);
		return array_merge($mimetypes, $extensions);
	}

} // END class FileAsset

/**
 * ImageAsset class
 *
 * A specific implementation of the FileAsset class that provides helper
 * methods for imaging-specific tasks.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ImageAsset extends FileAsset {

	const WIDTH = 0;
	const HEIGHT = 1;
	const SCALE = 2;
	const SHARPEN = 3;
	const QUALITY = 4;
	const FILL = 5;

	public static $defaults = array(
		'scaling' => array('all', 'matte', 'crop', 'width', 'height'),
		'sharpen' => 0,
		'quality' => 80,
		'fill' => 16777215
	);

	public $width;
	public $height;
	public $alt;
	public $title;
	public $settings;
	public $filename;
	public $type = 'image';

	/**
	 * Returns a URL for a resized image.
	 *
	 * If direct mode is enabled (which it is by
	 * default) and the image is already cached to the file system then a URL for that
	 * file will be returned.
	 *
	 * In all other cases a Shopp Image Server URL will be returned.
	 *
	 * @param $width
	 * @param $height
	 * @param $scale
	 * @param $sharpen
	 * @param $quality
	 * @param $fill
	 * @return string
	 */
	public function url ( $width = null, $height = null, $scale = null, $sharpen = null, $quality = null, $fill = null ) {

		$request = array();

		$url = Shopp::url( '' != get_option('permalink_structure') ? trailingslashit($this->id) . $this->filename : $this->id, 'images' );

		// Get the current URI
		$uri = $this->uri;

		// Handle resize requests
		$params = func_get_args();
		if ( count($params) > 0 ) {
			list($width, $height, $scale, $sharpen, $quality, $fill) = $params;
			$request = $this->resizing($width, $height, $scale, $sharpen, $quality, $fill);

			// Build the path to the cached copy of the file (if it exists)
			$size = $this->cachefile( $request );
			$uri = "cache_{$size}_{$this->filename}"; // Override the URI for the request
		}

		// Ask the engine if we have a direct URL
		$direct_url = $this->engine()->direct($uri);
		if ( false !== $direct_url ) return $direct_url;

		if ( empty($request) ) return $url;
		else return Shopp::add_query_string( $request, $url);
	}

	/**
	 * Takes the comma separated output of the resizing() method and returns the
	 * equivalent filename component.
	 *
	 * @param string $request
	 * @return string A valid string for file names
	 */
	protected function cachefile ( $request ) {
		$query = explode(',', $request);
		array_pop($query); // Lop off the validation variable
		return implode('_', $query);
	}


	public function output ( $headers = true ) {

		if ( $headers ) {
			$Engine = $this->engine();
			$data = $this->retrieve($this->uri);

			$etag = md5($data);
			$offset = 31536000;

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
				if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->modified ||
					trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
					header("HTTP/1.1 304 Not Modified");
					header("Content-type: {$this->mime}");
					exit;
				}
			}

			header("Cache-Control: public, max-age=$offset");
			header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $offset ) . ' GMT');
			header('Last-Modified: '.date('D, d M Y H:i:s', $this->modified).' GMT');
			if (!empty($etag)) header('ETag: '.$etag);

			header("Content-type: $this->mime");

			$filename = empty($this->filename) ? "image-$this->id.jpg" : $this->filename;
			header('Content-Disposition: inline; filename="'.$filename.'"');
			header("Content-Description: Delivered by WordPress/Shopp Image Server ({$this->storage})");
			do_action('shopp_image_output_headers', $this);
		}

		if (!empty($data)) echo $data;
		else $Engine->output($this->uri);
		@ob_flush(); @flush();
		return;
	}

	public function scaled ( $width, $height, $fit = 'all' ) {
		if ( preg_match('/^\d+$/', $fit) )
			$fit = self::$defaults['scaling'][$fit];

		$d = array('width'=>$this->width,'height'=>$this->height);
		switch ($fit) {
			case "width": return $this->scaledWidth($width,$height); break;
			case "height": return $this->scaledHeight($width,$height); break;
			case "crop":
			case "matte":
				$d['width'] = $width;
				$d['height'] = $height;
				break;
			case "all":
			default:
				if ($width/$this->width < $height/$this->height) return $this->scaledWidth($width,$height);
				else return $this->scaledHeight($width,$height);
				break;
		}

		return $d;
	}

	public function scaledWidth ( $width, $height ) {
		$d = array('width' => $this->width, 'height' => $this->height);
		$scale = $width / $this->width;
		$d['width'] = $width;
		$d['height'] = ceil($this->height * $scale);
		return $d;
	}

	public function scaledHeight ( $width, $height ) {
		$d = array('width' => $this->width, 'height' => $this->height);
		$scale = $height / $this->height;
		$d['height'] = $height;
		$d['width'] = ceil($this->width * $scale);
		return $d;
	}

	/**
	 * Generate a resizing request message
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param $width
	 * @param $height
	 * @param bool $scale = null
	 * @param bool $sharpen = null
	 * @param bool $quality = null
	 * @param bool $fill = null
	 * @return string
	 */
	public function resizing ( $width, $height, $scale = null, $sharpen = null, $quality = null, $fill = null ) {
		$args = func_get_args();
		$numargs = func_num_args();

		// Catch any remaining cases where the theme API passes bool false instead of null for defaults args
		for ( $i = 2; $i < $numargs; $i++)
			if ( false === $args[$i] ) $args[$i] = null;

		$args = array(
			self::WIDTH => (int) $args[ self::WIDTH ],
			self::HEIGHT => ( 0 == $args[ self::HEIGHT ] ) ? (int) $args[ self::WIDTH ] : (int) $args[ self::HEIGHT ],
			self::SCALE => ( isset($args[ self::SCALE ]) ) ? (int) $args[ self::SCALE ] : 0,
			self::SHARPEN => ( isset($args[ self::SHARPEN ]) ) ? (int) $args[ self::SHARPEN ] : self::$defaults['sharpen'],
			self::QUALITY => ( isset($args[ self::QUALITY ]) ) ? (int) $args[ self::QUALITY ] : self::$defaults['quality'],
			self::FILL => ( isset($args[ self::FILL ]) ) ? (int) $args[ self::FILL ] : self::$defaults['fill']
		);

		// Form the checksummed message
		$message = rtrim(join(',', $args), ',');
		$validation = self::checksum($this->id, $args);
		$message .= ",$validation";
		return $message;
	}

	/**
	 * Builds a salted checksum from the image request parameters
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param integer $id The image id
	 * @param array $args The parameters array
	 * @return string The generated checksum
	 **/
	public static function checksum ( $id, array $args ) {
		$key = defined('SECRET_AUTH_KEY') && '' != SECRET_AUTH_KEY ? SECRET_AUTH_KEY : DB_PASSWORD;
		array_unshift($args, $id);
		$args = array_filter($args, array(__CLASS__, 'notempty'));
		$message = join(',', $args);
		return sprintf('%u', crc32($key . $message));
	}

	/**
	 * Helper to filter for non-empty parameters
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param scalar $value The value to evaluate
	 * @return boolean True if not empty, false otherwise
	 **/
	public static function notempty ( $value ) {
		return ( '' !== $value && false !== $value );
	}

	public function extensions () {
		array_push($this->_xcols, 'filename', 'width', 'height', 'alt', 'title', 'settings');
	}

	/**
	 * unique - returns true if the the filename is unique, or can be made unique reasonably
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return bool true on success, false on fail
	 **/
	public function unique () {
		$Existing = new ImageAsset();
		$Existing->uri = $this->filename;
		$limit = 100;
		while ( $Existing->found() ) { // Rename the filename of the image if it already exists
			list( $name, $ext ) = explode(".", $Existing->uri);
			$_ = explode("-", $name);
			$last = count($_) - 1;
			$suffix = $last > 0 ? intval($_[$last]) + 1 : 1;
			if ( $suffix == 1 ) $_[] = $suffix;
			else $_[$last] = $suffix;
			$Existing->uri = join("-", $_).'.'.$ext;
			if ( ! $limit-- ) return false;
		}
		if ( $Existing->uri !== $this->filename )
			$this->filename = $Existing->uri;
		return true;
	}
}

/**
 * ProductImage class
 *
 * An ImageAsset used in a product context.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ProductImage extends ImageAsset {
	public $context = 'product';

	/**
	 * Truncate image data when stored in a session
	 *
	 * A ProductImage can be stored in the session with a cart Item object. We
	 * strip out unnecessary fields here to keep the session data as small as
	 * possible.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array
	 **/
	public function __sleep () {
		$ignore = array('numeral', 'created', 'modified', 'parent');
		$properties = get_object_vars($this);
		$session = array();
		foreach ( $properties as $property => $value ) {
			if ( substr($property, 0, 1) == "_" ) continue;
			if ( in_array($property,$ignore) ) continue;
			$session[] = $property;
		}
		return $session;
	}
}

/**
 * CategoryImage class
 *
 * An ImageAsset used in a category context.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage asset
 **/
class CategoryImage extends ImageAsset {
	public $context = 'category';
}

/**
 * DownloadAsset class
 *
 * A specific implementation of a FileAsset that includes helper methods
 * for downloading routines.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage asset
 **/
class DownloadAsset extends FileAsset {

	public $type = 'download';
	public $context = 'product';
	public $etag = "";
	public $purchased = false;

	public function loadby_dkey ($key) {
		$pricetable = ShoppDatabaseObject::tablename(ShoppPrice::$table);

		$Purchased = new ShoppPurchased($key,'dkey');
		if ( ! empty($Purchased->id) ) {
			// Handle purchased line-item downloads
			$Purchase = new ShoppPurchase($Purchased->purchase);
			$record = sDB::query("SELECT download.* FROM $this->_table AS download INNER JOIN $pricetable AS pricing ON pricing.id=download.parent WHERE pricing.id=$Purchased->price AND download.context='price' AND download.type='download' ORDER BY modified DESC LIMIT 1");
			$this->populate($record);
			$this->expopulate();
			$this->purchased = $Purchased->id;
		} else {
			// Handle purchased line-item meta downloads (addon downloads)
			$this->load(array(
				'context' => 'purchased',
				'type' => 'download',
				'name' => $key
			));
			$this->expopulate();
			$this->purchased = $this->parent;
		}

		$this->etag = $key;
	}

	public function purchased () {
		if (!class_exists('ShoppPurchased')) require(SHOPP_MODEL_PATH."/Purchased.php");
		if (!$this->purchased) return false;
		return new ShoppPurchased($this->purchased);
	}

	public function download ($dkey=false) {
		$found = $this->found();
		if ( ! $found ) return shopp_add_error(Shopp::__('Download failed. &quot;%s&quot; could not be found.', $this->name), 'false');

		add_action('shopp_download_success',array($this,'downloaded'));

		// send immediately if the storage engine is redirecting
		if ( isset($found['redirect']) ) {
			$this->send();
			exit();
		}

		// Close the session in case of long download
		@session_write_close();

		// Don't want interference from the server
		if ( function_exists('apache_setenv') ) @apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);

		set_time_limit(0);	// Don't timeout on long downloads

		// Use HTTP/1.0 Expires to support bad browsers (trivia: timestamp used is the Shopp 1.0 release date)
		header('Expires: '.date('D, d M Y H:i:s O',1230648947));

		header('Cache-Control: maxage=0, no-cache, must-revalidate');
		header('Content-type: application/octet-stream');
		header("Content-Transfer-Encoding: binary");
		header('Content-Disposition: attachment; filename="'.$this->name.'"');
		header('Content-Description: Delivered by ' . ShoppVersion::agent());

		ignore_user_abort(true);
		if ( ob_get_length() !== false )
			while(@ob_end_flush()); // Clear all open output buffers

		$this->send();	// Send the file data using the storage engine

		flush(); // Flush output to browser (to poll for connection)
		if ( connection_aborted() ) return shopp_add_error(Shopp::__('Connection broken. Download attempt failed.'), SHOPP_COMM_ERR);

		return true;
	}

	public function downloaded ($Purchased=false) {
		if (false === $Purchased) return;
		$Purchased->downloads++;
		$Purchased->save();
	}

	public function send () {
		$Engine = $this->engine();
		$Engine->output($this->uri,$this->etag);
	}

}

class ProductDownload extends DownloadAsset {
	public $context = 'price';
}

// Prevent loading image setting classes when run in image server script context
if ( !class_exists('ListFramework') ) return;

/**
 * ImageSetting
 *
 * Data model for handling image setting data
 *
 * @since 1.2
 * @package shopp
 **/
class ImageSetting extends ShoppMetaObject {

	static $qualities = array(100, 92, 80, 70, 60);
	static $fittings = array('all', 'matte', 'crop', 'width', 'height');

	public $width;
	public $height;
	public $fit = 0;
	public $quality = 100;
	public $sharpen = 100;
	public $bg = false;
	public $context = 'setting';
	public $type = 'image_setting';
	public $_xcols = array('width', 'height', 'fit', 'quality', 'sharpen', 'bg');

	public function __construct ( $id = false, $key = 'id' ) {
		$this->init(self::$table);
		$this->load($id,$key);
	}

	/**
	 * Provides a translated list of image "fit" setting labels
	 *
	 * @since 1.2
	 *
	 * @return array List of translated settings labels
	 **/
	public function fit_menu () {
		return array(
			Shopp::__('All'),
			Shopp::__('Fill'),
			Shopp::__('Crop'),
			Shopp::__('Width'),
			Shopp::__('Height')
		);
	}

	/**
	 * Provides a menu of readable image quality labels
	 *
	 * @since 1.3
	 *
	 * @return array List of quality labels
	 **/
	public function quality_menu () {
		return array(
			Shopp::__('Highest quality, largest file size'),
			Shopp::__('Higher quality, larger file size'),
			Shopp::__('Balanced quality &amp; file size'),
			Shopp::__('Lower quality, smaller file size'),
			Shopp::__('Lowest quality, smallest file size')
		);
	}

	/**
	 * Converts a numeric ImageSetting fit setting to a Theme API compatible option name
	 *
	 * @since 1.3
	 *
	 * @param integer $setting The numeric ImageSetting value
	 * @return string The option name
	 **/
	public function fit ( $setting ) {
		if ( isset(self::$fittings[ $setting ]) )
			return self::$fittings[ $setting ];
		return self::$fittings[0];
	}

	/**
	 * Converts a numeric ImageSetting quality setting to a Theme API compatible option value
	 *
	 * @since 1.3
	 *
	 * @param integer $setting The numeric ImageSetting value
	 * @return integer The option value
	 **/
	public function quality ( $setting ) {
		if ( isset(self::$qualities[ $setting ])  )
			return self::$qualities[ $setting ];
		return self::$qualities[2];
	}

	/**
	 * Convert the ImageSetting values to a Theme API compatible array of image options
	 *
	 * @since 1.3
	 *
	 * @param string $prefix (optional) Prefix for the option keys
	 * @return array The image options
	 **/
	public function options ( $prefix = '' ) {
		$settings = array();
		$properties = array('width', 'height', 'fit', 'quality', 'sharpen', 'bg');
		foreach ( $properties as $property ) {
			$value = $this->{$property};
			if ( 'quality' == $property ) $value = $this->quality((int)$this->{$property});
			if ( 'fit' == $property ) $value = $this->fit((int)$this->{$property});
			$settings[ $prefix . $property ] = $value;
		}
		return $settings;
	}

} // END class ImageSetting

/**
 * Loads the collection of image settings
 *
 * @since 1.2
 * @package shopp
 **/
class ImageSettings extends ListFramework {

	private static $object;

	private function __construct () {
		$ImageSetting = new ImageSetting();
		$table = $ImageSetting->_table;
		$where = array(
			"type='$ImageSetting->type'",
			"context='$ImageSetting->context'"
		);
		$options = compact('table', 'where');
		$query = sDB::select($options);
		$this->populate(sDB::query($query, 'array', array($ImageSetting, 'loader'), false, 'name'));
		$this->found = sDB::found();
	}

	/**
	 * Prevents cloning the DB singleton
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function __clone () {
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}

	/**
	 * Provides a reference to the instantiated singleton
	 *
	 * The ImageSettings class uses a singleton to ensure only one DB object is
	 * instantiated at any time
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return DB Returns a reference to the DB object
	 **/
	public static function &object () {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

} // END class ImageSettings
<?php
/**
 * Image.php
 *
 * Image processing library
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, April 2008
 * @package Shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Reprocesses images (scaling, etc)
 *
 * @author Jonathan Davis
 * @since 1.1
 **/
class ImageProcessor {

	private $src;
	private $processed;
	private $width = false;
	private $height = false;
	private $axis = 'y';
	private $aspect = 1;
	private $dx = 0;
	private $dy = 0;
	private $alpha = false;
	private $complexity = false;

	public function __construct ( $data, $width, $height ) {

		$this->src = new StdClass();
		$this->src->width = $width;
		$this->src->height = $height;
		$this->src->aspect = $width / $height;

		if ( $data ) $this->src->image = imagecreatefromstring($data);
		else $this->src->image = false;
	}

	public function width () {
		return $this->width;
	}

	public function height () {
		return $this->height;
	}

	public function canvas ( $width, $height, $alpha = false ) {
		$this->processed = ImageCreateTrueColor($width, $height);
		if ( $alpha ) {
			ImageAlphaBlending($this->processed, false);
			$transparent = ImageColorAllocateAlpha($this->processed, 0, 0, 0, 127);
			ImageFill($this->processed, 0, 0, $transparent);
			ImageSaveAlpha($this->processed, true);
			$this->alpha = true;
		}
	}

	public function scale ( $width, $height, $fit = 'all', $alpha = false, $fill = false, $dx = false, $dy = false, $cropscale = false ) {
		$this->aspect = $width / $height;

		// Allocate a new true color image
		$this->canvas($width, $height, $alpha);

		// Determine the dimensions to use for resizing
		$this->dimensions($width, $height, $fit, $dx, $dy, $cropscale);

		// Setup background fill color
		$white = array('red' => 255, 'green' => 255, 'blue' => 255);
		$rgb = false;

		if ( false !== $fill ) {
			if ( is_int($fill) ) $rgb = $this->hexrgb($fill);
			if ( ! is_array($rgb) ) $rgb = $white;
		} else { // Sample from the corner pixels
			if ( $this->src->image ) {
				$topleft = @ImageColorAt($this->src->image, 0, 0);
				$bottomright = @ImageColorAt($this->src->image, $this->src->width - 1, $this->src->height - 1);
				if ( $topleft == $bottomright ) $rgb = $this->hexrgb($topleft);
				else {
					// Use average of sampled colors for background
					$tl_rgb = $this->hexrgb($topleft);
					$br_rgb = $this->hexrgb($bottomright);
					$rgb = $white;
					$keys = array_keys($rgb);
					foreach ( $keys as $color ) $rgb[$color] = floor( ($tl_rgb[ $color ] + $br_rgb[ $color ]) / 2 );
				}
			}
			if ( ! is_array($rgb) ) $rgb = $white;
		}

		if ( ! $alpha ) {
			// Allocate the color in the image palette
			$matte = ImageColorAllocate($this->processed, $rgb['red'], $rgb['green'], $rgb['blue']);

			// Fill the canvas
			ImageFill($this->processed,0,0,$matte);
		}

		if (!$this->src->image) {
			// Determine the mock dimensions from the resample operation
			if ( 'crop' == $fit ) {
				$this->width = min($width, $this->width);
				$this->height = min($height, $this->height);
			} elseif ( false !== $fill ) {
				$this->width = $width;
				$this->height = $height;
			}
			return;
		}
		// Resample the image
		ImageCopyResampled(
			$this->processed, $this->src->image,
			$this->dx, $this->dy, 					// dest_x, dest_y
			0, 0, 									// src_x, src_y
			$this->width, $this->height, 			// dest_width, dest_height
			$this->src->width, $this->src->height	// src_width, src_height
		);
		$this->width = imagesx($this->processed);
		$this->height = imagesy($this->processed);
		if ( function_exists('apply_filters') )
			return apply_filters('shopp_image_scale', $this);
	}

	private function dimensions ( $width, $height, $fit = 'all', $dx = false, $dy = false, $cropscale = false ) {
		if ( $this->src->width <= $width && $this->src->height <= $height ) {
			$this->width = $this->src->width;
			$this->height = $this->src->height;
			if ( 'matte' == $fit ) $this->center($width, $height, $dx, $dy);
			return false;
		}

		if ( 'crop' == $fit ) {
			if ( $this->src->aspect / $this->aspect <= 1 )
				$this->axis = 'x'; // Scale & crop
		} elseif ( $this->src->aspect / $this->aspect >= 1 )
			$this->axis = 'x'; // Scale & fit (with or without matte)

		$this->resized($width, $height, $this->axis);

		if ( 'crop' == $fit ) { // Center cropped image on the canvas
			if ( false !== $cropscale ) {
				$this->width = $this->src->width * $cropscale;
				$this->height = $this->src->height * $cropscale;
			} elseif ( $this->src->width <= $width || $this->src->height <= $height ) {
				$this->width = $this->src->width;
				$this->height = $this->src->height;
			}
		}

		$this->center($width, $height, $dx, $dy);

		return true;
	}

	private function center ( $width, $height, $dx = false, $dy = false ) {
		$this->dx = false !== $dx ? $dx : ($this->width - $width) * -0.5;
		$this->dy = false !== $dy ? $dy : ($this->height - $height) * -0.5;
	}

	private function resized ( $width, $height, $axis = 'y' ) {
		if ( 'x' == $axis ) list($this->width, $this->height) = array($width, $this->ratioHeight($width));
		else list($this->width, $this->height) = array($this->ratioWidth($height), $height);
	}

	private function ratioWidth ( $height ) {
		$s_height = $height/$this->src->height;
		return ceil($this->src->width * $s_height);
	}

	private function ratioHeight ( $width ) {
		$s_width = $width/$this->src->width;
		return ceil($this->src->height * $s_width);
	}

	/**
	 * Return the processed image
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return string Binary string of the image data
	 **/
	public function imagefile ( $quality = 80 ) {
		if ( ! isset($this->processed) ) $image =& $this->src->image;
		else $image = &$this->processed;

		imageinterlace($image, true);						// For progressive loading
		ob_start();  										// Start capturing output buffer stream
		if ( $this->alpha ) imagepng($image);				// Output the image to the stream
		else imagejpeg($image, null, $quality);
		$buffer = ob_get_contents(); 						// Get the bugger
		ob_end_clean(); 									// Clear the buffer
		return $buffer;										// Send it back
	}

	/**
	 * Calculate the visual complexity of the image
	 *
	 * Provides a fast method for determining visual complexity of
	 * an image by comparing a raw image size to jpeg image file size.
	 * The JPEG compression algorithm is really good at compressing
	 * repetitive areas (low detail) areas of an image and gives us
	 * a good enough indicator for the complexity in an image.
	 *
	 * @author Jonathan Davis
	 * @since 1.3.4
	 *
	 * @return float A complexity amount (jpeg size to raw gd size)
	 **/
	public function complexity () {

		$image =& $this->src->image;

		if ( false !== $this->complexity ) return $this->complexity;

		ob_start(); imagegd($image);
		$source = strlen(ob_get_clean());

		ob_start();	imagejpeg($image);
		$jpeg = strlen(ob_get_clean());

		$this->complexity = 0.7 - ( $jpeg / $source );

		return $this->complexity;

	}

	/**
	 * Performs an unsharp mask on the image
	 *
	 * Smart unsharp masking looks at the visual complexity
	 * of the image to determine how to vary the strength of
	 * the sharpening and the narrowness of the edge threshold.
	 *
	 * @author Jonathan Davis
	 * @since 1.3.4
	 * @version 1.0
	 *
	 * @param int $amount Strength of the sharpening (0-100)
	 * @param int $threshold The narrowness of the sharpening filter (0-255)
	 * @return void
	 **/
	public function sharpen ( $amount = 100, $threshold = null ) {

		if ( ! isset($this->processed) ) $image =& $this->src->image;
		else $image = &$this->processed;

		// Don't unsharp mask if we have a very low detail image
		$complexity = $this->complexity();
		if ( $complexity < 0.15 ) return;

	    if ( $amount > 100 ) $amount = 100;

		// Calibration to a reasonable strength
		$amount = ( $amount * (1 + $complexity) ) * 0.01618;

		// Calibrate the threshold to the image complexity
		if ( empty($threshold) ) $threshold = 10 - ( $complexity * 13 );
	    if ( $threshold > 255 ) $threshold = 255;

		// Create a gausian blur for edge detection
	    $w = imagesx($image); $h = imagesy($image);
	    $blur = imagecreatetruecolor($w, $h);
        imagecopy ($blur, $image, 0, 0, 0, 0, $w, $h);
		imagefilter($blur, IMG_FILTER_GAUSSIAN_BLUR);

		// Walk through each pixel of the image
        for ( $x = 0; $x < $w - 1 ; $x++ ) { // each row
            for ( $y = 0; $y < $h - 1 ; $y++ ) { // each pixel

				$changed = false;
				$pixel = array(
					'src' => imagecolorsforindex($image, imagecolorat($image, $x, $y) ),
					'blur' => imagecolorsforindex($blur, imagecolorat($blur, $x, $y) )
				);
				$channels = array_keys($pixel['src']);

				// If masked pixel channel value differs enough ($threshold) from the original,
				// we found an edge. Boost the edge contrast by a strength ($amount) of the
				// color channel difference between the blurred mask pixel and the source image pixel.
				// Otherwise leave the pixel alone to preserve smooth surfaces/gradients.
				foreach ( $channels as $channel ) {

					$src = $pixel['src'][ $channel ];
					$mask = $pixel['blur'][ $channel ];

					// Calculate the difference
					$diff = $src - $mask;

					// If the difference is more than the threshold, we're near an edge
					if ( abs($diff) >= $threshold ) {
						// Change the pixel channel with the difference factored by the strength amount
						$value = ( $amount * $diff ) + $src;

						// Ceiling/floor values (if is faster than min/max)
						if ( $value > 255 ) $value = 255;
						elseif ( $value < 0 ) $value = 0;

						// Save the channel value
						$$channel = $value;
						$changed = true;
					} else $$channel = $src;
				}

				if ( $changed ) { // Update the pixel in the image with the new color
					$color = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
					imagesetpixel($image, $x, $y, $color);
				}
            }
        }

	    imagedestroy($blur);
		imagefilter($image, IMG_FILTER_SMOOTH, 16); // Just enough to anti-alias edges

	}

	/**
	 * Convert a decimal-encoded hexadecimal color to RGB color values
	 *
	 * Uses bit-shifty voodoo to pick the color channels apart.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $color Decimal color value
	 * @return array RGB color values
	 **/
	private function hexrgb ( $color ) {
		return array(
			'red'   => ( 0xFF & ($color >> 0x10) ),
			'green' => ( 0xFF & ($color >> 0x8) ),
			'blue'  => ( 0xFF & $color)
		);
	}

	public function __toString () {
		$data = new StdClass();
		foreach ( $this as $id => $entry )
			$data->$id = (string)$entry;

		return json_encode($data);
	}

} // class ImageProcessor

/**
 * ShoppImagingSystem
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopp
 **/
interface ShoppImagingModule {
}

/**
 * ShoppImageModules
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopp
 **/
class ShoppImagingModules extends ModuleLoader {

	protected $interface = 'ShoppImagingModule';
	protected $paths =  array(SHOPP_ADDONS);

	/**
	 * constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct () {

		// Prevent imaging modules that are not enabled from loading
		add_filter('shopp_modules_valid_shoppimagingmodule', array('ShoppImagingModules', 'enabled'), 10, 2 );

		$this->installed(); // Find modules
		$this->load(true);

	}

	/**
	 * Determines if an imaging module is enabled
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if enabled, false otherwise
	 **/
	public static function enabled ( $valid, $Module ) {
		$enabled = shopp_setting('imaging_modules');
		if ( empty($enabled) ) $enabled = array();
		return in_array($Module->classname, (array)$enabled);
	}

} // END class ShoppImageModules
<?php
/**
 * AssetDevAPITests
 *
 *
 * @author John Dillick
 * @version 1.0
 * @copyright Ingenesis Limited, 28 November, 2011
 * @package AssetDevAPITests
 **/
class AssetDevAPITests extends ShoppTestCase {
	protected $product;
	protected $category;
	protected $assets;
	protected $files;

	function setUp () {
		// Create Product
		$data = array(
			'name' => "AssetDevAPITests Product",
			'publish' => array( 'flag' => true ),
			'description' => "Product Download Test",
		);
		$data['single'] = array(
			'type' => 'Download',
			'price' => 1.00,
		);
		$this->product = shopp_add_product($data)->id;
		$this->category = shopp_add_product_category( "AssetDevAPITests Category" );
		$this->assets = array(
			'product' => array(),
			'category' => array(),
			'download' => array(),
		);
		$path = dirname(__FILE__).'/Assets/';
		$this->files = array(
			$path."1.png",
			$path."2.png",
			$path."3.png",
			$path."4.png",
			$path."5.png",
		);
	}

	function tearDown () {
		$image_path = realpath(WP_CONTENT_DIR."/".shopp_setting('image_path'));
		$download_path = realpath(WP_CONTENT_DIR."/".shopp_setting('products_path'));

		foreach ( $this->assets as $type => $assets ) {
			foreach ( $assets as $i => $asset) {
				switch ( $type ) {
					case "product":
					case "category":
						$Asset = 'product' == $type ? new ProductImage($asset) : new CategoryImage($asset);
						unlink($image_path."/".$Asset->filename);
						shopp_rmv_image ( $asset, $type );
						break;
					case "download":
						$Asset = new ProductDownload($asset);
						unlink($download_path."/".$Asset->filename);
						shopp_rmv_product_download ( $asset );
						break;
				}
				unset($this->assets[$type][$i]);
			}
		}
		if ( $this->product ) shopp_rmv_product( $this->product );
		if ( $this->category ) shopp_rmv_product_category( $this->category );
		unset($this->product,$this->category,$this->assets,$this->files);
	}

	// shopp_add_image ( $id, $context, $file )
	function test_shopp_add_image () {
		foreach ( $this->files as $file ) {
			$this->assets['product'][$file] = shopp_add_image ( $this->product, 'product', $file );
			$this->assets['category'][$file] = shopp_add_image ( $this->category, 'category', $file );
		}
		$this->assertTrue( ! empty($this->assets['product']) && ! empty($this->assets['category']) );
		foreach ( $this->assets as $type => $assets ) {
			foreach ( $assets as $file => $image ) {
				$this->assertTrue( (bool) $image );
				$Image = 'product' == $type ? new ProductImage($image) : new CategoryImage($image);
				$this->assertEquals( $image, $Image->id );
				$this->assertEquals( $this->$type, $Image->parent );
				$this->assertEquals( 'original', $Image->name );
				$this->assertEquals( 'image', $Image->type );
				$this->assertEquals( 'image/png', $Image->mime );
				$this->assertTrue( $Image->size > 0 );
			}
		}
	}

	// shopp_add_product_image ( $product, $file )
	function test_shopp_add_product_image () {
		$type = 'product';

		foreach ( $this->files as $file ) {
			$this->assets[$type][$file] = shopp_add_product_image ( $this->$type, $file );
		}
		$this->assertTrue( ! empty($this->assets[$type]) );
		$assets = $this->assets[$type];
		foreach ( $assets as $file => $image ) {
			$this->assertTrue( (bool) $image );
			$Image = new ProductImage($image);
			$this->assertEquals( $image, $Image->id );
			$this->assertEquals( $this->$type, $Image->parent );
			$this->assertEquals( 'original', $Image->name );
			$this->assertEquals( 'image', $Image->type );
			$this->assertEquals( 'image/png', $Image->mime );
			$this->assertTrue( $Image->size > 0 );
		}
	}

	// shopp_add_category_image ( $category, $file )
	function test_shopp_add_category_image () {
		$type = 'category';

		foreach ( $this->files as $file ) {
			$this->assets[$type][$file] = shopp_add_category_image ( $this->$type, $file );
		}
		$this->assertTrue( ! empty($this->assets[$type]) );
		$assets = $this->assets[$type];
		foreach ( $assets as $file => $image ) {
			$this->assertTrue( (bool) $image );
			$Image = new ProductImage($image);
			$this->assertEquals( $image, $Image->id );
			$this->assertEquals( $this->$type, $Image->parent );
			$this->assertEquals( 'original', $Image->name );
			$this->assertEquals( 'image', $Image->type );
			$this->assertEquals( 'image/png', $Image->mime );
			$this->assertTrue( $Image->size > 0 );
		}
	}

	// shopp_add_product_download ( $product, $file, $variant )
	function test_shopp_add_product_download () {
		$file = $this->files[0];
		$this->assets['download'][$file] = shopp_add_product_download ( $this->product, $file );
		$this->assertTrue( ! empty($this->assets['download']) );
		$assets = $this->assets['download'];
		$Price = shopp_product_variant( array('product'=>$this->product), 'product' );

		foreach ( $assets as $file => $download ) {
			$this->assertTrue( (bool) $download );
			$Download = new ProductDownload($download);
			$this->assertEquals( $download, $Download->id );
			$this->assertEquals( $Price->id, $Download->parent );
			$this->assertEquals( basename($file), $Download->name );
			$this->assertEquals( 'download', $Download->type );
			$this->assertEquals( 'image/png', $Download->mime );
			$this->assertTrue( $Download->size > 0 );
		}
	}

	// shopp_rmv_product_image ( $image )
	function test_shopp_rmv_product_image () {
		$image_path = realpath(WP_CONTENT_DIR."/".shopp_setting('image_path'));
		$file = $this->files[0];
		$asset = shopp_add_product_image ( $this->product, $file );

		$this->AssertTrue((bool) $asset);
		$Asset = new ProductImage($asset);
		$this->AssertTrue( ! empty( $Asset->id ) && $Asset->id );

		unlink($image_path."/".$Asset->filename);
		shopp_rmv_product_image ( $asset );
		$Asset = new ProductImage($asset);
		$this->AssertTrue( empty( $Asset->id ) );
	}

	// shopp_rmv_category_image ( $image )
	function test_shopp_rmv_category_image () {
		$image_path = realpath(WP_CONTENT_DIR."/".shopp_setting('image_path'));
		$file = $this->files[0];
		$asset = shopp_add_category_image ( $this->category, $file );

		$this->AssertTrue((bool) $asset);
		$Asset = new CategoryImage($asset);
		$this->AssertTrue( ! empty( $Asset->id ) && $Asset->id );

		unlink($image_path."/".$Asset->filename);
		shopp_rmv_category_image ( $asset );
		$Asset = new CategoryImage($asset);
		$this->AssertTrue( empty( $Asset->id ) );
	}

	// shopp_rmv_image ( $image, $context )
	function test_shopp_rmv_image () {
		$image_path = realpath(WP_CONTENT_DIR."/".shopp_setting('image_path'));
		$file = $this->files[0];
		$asset = shopp_add_image ( $this->product, 'product', $file );

		$this->AssertTrue((bool) $asset);
		$Asset = new ProductImage($asset);
		$this->AssertTrue( ! empty( $Asset->id ) && $Asset->id );

		unlink($image_path."/".$Asset->filename);
		shopp_rmv_image ( $asset, 'product' );
		$Asset = new ProductImage($asset);
		$this->AssertTrue( empty( $Asset->id ) );
	}

	// shopp_rmv_product_download ( $download )
	function test_shopp_rmv_product_download () {
		$download_path = realpath(WP_CONTENT_DIR."/".shopp_setting('products_path'));
		$file = $this->files[0];
		$asset = shopp_add_product_download ( $this->product, $file );
		$this->AssertTrue((bool) $asset);

		$Asset = new ProductDownload($asset);
		$this->AssertTrue( ! empty( $Asset->id ) && $Asset->id );

		unlink($download_path."/".$Asset->filename);
		shopp_rmv_product_download ( $asset );
		$Asset = new ProductDownload($asset);
		$this->AssertTrue( empty( $Asset->id ) );
	}
}

?>
<?php
class MetaAPITests extends ShoppTestCase {

	static function setUpBeforeClass () {

		shopp_add_product(array(
			'name' => 'Command Uniform',
			'publish' => array('flag' => true),
			'specs' => array(
				'Department' => 'Command',
				'Color' => 'Gold'
			),
			'variants' => array(
				'menu' => array(
					'Size' => array('Small','Medium','Large','Brikar')
				),
				0 => array(
					'option' => array('Size' => 'Small'),
					'type' => 'Shipped',
					'price' => 19.99,
					'sale' => array('flag'=>true, 'price' => 9.99),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 5,
						'sku' => 'SFU-001-S'
					)
				),
				1 => array(
					'option' => array('Size' => 'Medium'),
					'type' => 'Shipped',
					'price' => 22.55,
					'sale' => array('flag'=>true, 'price' => 19.99),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 15,
						'sku' => 'SFU-001-M'
					)
				),
				2 => array(
					'option' => array('Size' => 'Large'),
					'type' => 'Shipped',
					'price' => 32.95,
					'sale' => array('flag'=>true, 'price' => 24.95),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 1,
						'sku' => 'SFU-001-L'
					)
				),

			)
		));

	}

	function test_bug1130_shopp_set_meta () {
		$Product = shopp_product('command-uniform', 'slug');
		$Price = reset($Product->prices);

		// remove all meta records of name settings for this priceline
		$meta = shopp_rmv_meta ( $Price->id, 'price', 'settings');

		$settings = array(
			'dimensions' => array(
				'weight'=> 1.1,
				'height'=> 2,
				'width' => 10,
				'length' => 10
			)
		);

		$return = shopp_set_meta ( $Price->id, 'price', 'settings', $settings );
		$this->AssertTrue($return);

		$return = shopp_set_meta ( $Price->id, 'price', 'settings', $settings );
		$this->AssertTrue($return);

		// get all meta records for this priceline
		$meta = shopp_meta ( $Price->id, 'price', 'settings');
		$this->AssertEquals($settings, $meta);

	}

	function test_shopp_set_meta () {
		$Product = shopp_product('command-uniform', 'slug');
		$Price = reset($Product->prices);
		$return = shopp_set_meta ( $Price->id, 'price', 'mypricesetting', 'hello world' );
		$meta = shopp_meta ( $Price->id, 'price', 'mypricesetting');
		$this->AssertEquals('hello world', $meta);
	}

	function test_shopp_meta () {
		$data = array(
			'name' => "Download Product Test",
			'single' => array(),
			'publish' => array( 'flag' => true ),
			'description' =>
				"Testing Download"
		);
		$data['single'] = array(
			'type' => 'Download',
			'price' => 41.00,
		);

		$Product = shopp_add_product($data);

		file_put_contents ( 'testdownload.txt' , 'my test download file' );
		shopp_add_product_download ( $Product->id, realpath('testdownload.txt') );
		$ProductVariant = shopp_product_variant(array('product'=>$Product->id), 'product');
		unlink( 'testdownload.txt');

		$download = shopp_meta ( $ProductVariant->id, 'price', 'testdownload.txt', 'download' );

		$this->AssertTrue(is_object($download));
		$this->AssertEquals('text/plain', $download->mime);
		$this->AssertEquals('21', $download->size);
		$this->AssertTrue(is_int($download->uri));
	}

	function test_shopp_product_meta () {
		$Product = shopp_product('command-uniform', 'slug');

		$this->AssertTrue(shopp_product_has_meta($Product->id, 'options'));

		$meta = shopp_product_meta ( $Product->id, 'options');
		$this->AssertTrue(is_array($meta) && ! empty($meta));
	}

	function test_shopp_product_meta_list () {
		$Product = shopp_product('command-uniform', 'slug');
		$specs = shopp_product_meta_list($Product->id,'spec');
		$this->AssertTrue(is_array($specs));
		$this->AssertEquals('Command', $specs['Department']);
		$this->AssertEquals('Gold', $specs['Color']);
	}

	function test_shopp_rmv_meta() {
		$Product = shopp_product('command-uniform', 'slug');
		shopp_set_product_meta($Product->id, 'Manliness Factor', 'High', 'spec');
		$this->AssertEquals('High', shopp_product_meta($Product->id, 'Manliness Factor', 'spec'));
		shopp_rmv_product_meta($Product->id, 'Manliness Factor', 'spec');
		$this->AssertEquals(false, (bool) shopp_product_meta($Product->id, 'Manliness Factor', 'spec') );
	}

	function test_shopp_meta_exists () {
		$Product = shopp_product('command-uniform', 'slug');
		shopp_set_meta ( 11, 'testcontext', 'testname', 'testvalue', 'testtype' );

		// shopp_meta_exists ( $name = false, $context = false, $type = 'meta' )
		$this->AssertTrue(shopp_meta_exists('testname', 'testcontext', 'testtype'));

		// shopp_rmv_meta ( $id = false, $context = false, $name = false, $type = 'meta' )
		$this->AssertTrue( shopp_rmv_meta( 11, 'testcontext', 'testname', 'testtype' ) );
		$this->AssertFalse( shopp_meta_exists('testname', 'testcontext', 'testtype') );
	}

}
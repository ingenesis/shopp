<?php
/**
*
*/
class MetaAPITests extends ShoppTestCase {


	function test_bug1130_shopp_set_meta () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Price = shopp_product_variant(258);

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
		$this->AssertEquals('a:1:{s:10:"dimensions";a:4:{s:6:"weight";d:1.1000000000000001;s:6:"height";i:2;s:5:"width";i:10;s:6:"length";i:10;}}',
			serialize($meta));

	}

	function test_shopp_set_meta () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Price = shopp_product_variant(174);
		$return = shopp_set_meta ( $Price->id, 'price', 'mypricesetting', 'hello world' );
		$meta = shopp_meta ( $Price->id, 'price', 'mypricesetting');
		$this->AssertEquals('hello world', $meta);
	}

	function test_shopp_meta () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
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

		$download = shopp_meta ( $ProductVariant->id, 'price', 'testdownload.txt', 'download' );

		$this->AssertTrue(is_object($download));
		$this->AssertEquals('text/plain', $download->mime);
		$this->AssertEquals('21', $download->size);
		$this->AssertEquals('testdownload.txt', $download->uri);
	}

	function test_shopp_product_meta () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Product = new Product('Smart & Sexy - Ruffle Mesh Bustier and Thong Panty Set', 'name');

		$this->AssertTrue(shopp_product_has_meta($Product->id, 'options'));

		$meta = shopp_product_meta ( $Product->id, 'options');
		$this->AssertTrue(is_array($meta) && ! empty($meta));
	}

	function test_shopp_product_meta_list () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Product = new Product("Men's Black Stainless Steel & CZ Engraved Band", 'name');
		$specs = shopp_product_meta_list($Product->id,'spec');
		$this->AssertTrue(is_array($specs));
		$this->AssertEquals('9GW107', $specs['Model No.']);
		$this->AssertEquals('Men', $specs['Gender']);
	}

	function test_shopp_rmv_meta() {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Product = new Product("Men's Black Stainless Steel & CZ Engraved Band", 'name');
		shopp_set_product_meta($Product->id, 'Manliness Factor', 'High', 'spec');
		$this->AssertEquals('High', shopp_product_meta($Product->id, 'Manliness Factor', 'spec'));
		shopp_rmv_product_meta($Product->id, 'Manliness Factor', 'spec');
		$this->AssertEquals(false, (bool) shopp_product_meta($Product->id, 'Manliness Factor', 'spec') );
	}

	function test_shopp_meta_exists () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		shopp_set_meta ( 11, 'testcontext', 'testname', 'testvalue', 'testtype' );

		// shopp_meta_exists ( $name = false, $context = false, $type = 'meta' )
		$this->AssertTrue(shopp_meta_exists('testname', 'testcontext', 'testtype'));

		// shopp_rmv_meta ( $id = false, $context = false, $name = false, $type = 'meta' )
		$this->AssertTrue( shopp_rmv_meta( 11, 'testcontext', 'testname', 'testtype' ) );
		$this->AssertFalse( shopp_meta_exists('testname', 'testcontext', 'testtype') );
	}

}
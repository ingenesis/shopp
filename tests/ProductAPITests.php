<?php
/**
 * ProductAPITests
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 14 October, 2009
 * @package shopp
 **/
class ProductAPITests extends ShoppTestCase {

	static $category = false;
	static $tag = false;

	static function setUpBeforeClass () {

		// capture original settings
		// $this->_set_setting('inventory','on');
		// $this->_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:3:"USA";s:8:"currency";a:2:{s:4:"code";s:3:"USD";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:1:"$";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:8:"imperial";s:6:"region";i:0;s:7:"country";s:2:"US";s:4:"zone";s:2:"OH";s:3:"vat";b:0;}'));
		// $this->_set_setting('taxrates',shopp_setting('taxrates'));

		$Shopp = Shopp::object();
		$Shopp->Flow->Controller = new Storefront();

		self::$category = $Uniforms = shopp_add_product_category('Uniforms');
		$CommandDivision = shopp_add_product_category('Command Division', '', $Uniforms);

		$starfleet = shopp_add_product_tag('Starfleet');
		self::$tag = $federation = shopp_add_product_tag('Federation');

		$Product = shopp_add_product(array(
			'name' => 'Command Uniform',
			'publish' => array('flag' => true),
			'featured' => true,
			'summary' => 'Starfleet standard issue gold command division uniforms for your crew.',
			'description' => "Command uniforms ranged in color depending on the type of fabric used. While they were generally described as gold, some of the darker variants had a distinct greenish hue, while the dress uniforms, and a captain's alternate tunic were clearly green.",
			'tags' => array('terms' => array('Starfleet', 'Federation')),
			'categories'=> array('terms' => array($Uniforms, $CommandDivision)),
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
				3 => array(
					'option' => array('Size' => 'Brikar'),
					'type' => 'Shipped',
					'price' => 55.00,
					'sale' => array('flag'=>true, 'price' => 35.00),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 2.1, 'length' => 0.3, 'width' => 0.9, 'height' => 1.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 1,
						'sku' => 'SFU-001-B'
					)
				),

			)
		));

		ShoppProduct($Product);
	}

	static function tearDownAfterClass () {
		$Product = shopp_product('command-uniform','slug');
		shopp_rmv_product($Product->id);
		// restore original settings
		// $this->_restore_setting('inventory');
		// $this->_restore_setting('base_operations');
		// $this->_restore_setting('taxrates');
		// parent::tearDown();
	}

	function test_product_id () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-id');
		$this->assertTrue( ! empty(ShoppProduct()->id) );
		$this->assertEquals( ShoppProduct()->id, $output );
	}

	function test_product_name () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-name');
		$this->assertEquals('Command Uniform', $output);
	}

	function test_product_slug () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-slug');
		$this->assertEquals('command-uniform', $output);
	}

	function test_product_url () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '?shopp_product=command-uniform', $output);
	}

	function test_product_description () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-description');
		$this->assertEquals("937a005226c77e01321a3d88a276b11c", md5($output));
	}

	function test_product_summary () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-summary');
		$this->assertEquals("9da1c49f90a584dd46290f690a404914",md5($output));
	}

	function test_product_found () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','found'));
		$original = ShoppProduct();
		ShoppProduct(new Product(-1));
		$this->assertFalse(shopp('product','found'));
		ShoppProduct($original);
	}

	function test_product_isfeatured () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','isfeatured'));
		$this->assertTrue(shopp('product','is-featured'));
	}

	function test_product_price () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-price');
		$this->assertEquals("$19.99 &mdash; $55.00",$output);
	}

	function test_product_onsale () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','onsale'));
	}

	function test_product_saleprice () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-saleprice');
		$this->assertEquals("$9.99 &mdash; $35.00",$output);
	}

	function test_product_prices_withvat () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$output = shopp('product.get-price');
		$this->assertEquals("$19.99 &mdash; $55.00",$output);

		$output = shopp('product.get-price','taxes=on');
		$this->assertEquals("$21.99 &mdash; $60.50",$output);

		$output = shopp('product.get-saleprice');
		$this->assertEquals("$9.99 &mdash; $35.00",$output);

		$output = shopp('product.get-saleprice','taxes=on');
		$this->assertEquals("$10.99 &mdash; $38.50",$output);
	}

	function test_product_hassavings () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','has-savings'));
	}

	function test_product_savings () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-savings');
		$this->assertEquals("$2.56 &mdash; $20.00", $output);

		$output = shopp('product.get-savings','show=percent');
		$this->assertEquals("11% &mdash; 50%", $output);
	}

	function test_product_weight () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-weight');
		$this->assertEquals("0.1 - 2.1 ", $output);
	}

	function test_product_thumbnail () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-thumbnail');
		$this->assertXmlStringEqualsXmlString('<img src="http://shopptest/store/images/652/UlitimateMatrixBRCollections.jpg?96,96,2395623139" alt="Ultimate Matrix Collection" width="96" height="96"/>',$output);
		$this->assertValidMarkup($output);
	}

	function test_product_gallery () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = shopp('product.get-gallery');
		$this->assertValidMarkup($output);
	}

	// function test_product_quantity () {
	// 	ob_start();
	// 	shopp('product','quantity','input=text');
	// 	$output = ob_get_contents();
	// 	ob_end_clean();
	// 	$this->assertValidMarkup($output);
	//
	// 	ob_start();
	// 	shopp('product','quantity','input=menu&options=1-3,5,10-15');
	// 	$output = ob_get_contents();
	// 	ob_end_clean();
	//
	// 	$expected = array(
	// 		'tag' => 'select',
	// 		'attributes' => array(
	// 			'name' => 'products[81][quantity]',
	// 			'id' => 'quantity-81'
	// 		),
	// 		'children' => array(
	// 				'count' => 10,
	// 				'only' => array('tag' => 'option')
	// 		)
	// 	);
	//
	// 	$this->assertTag($expected,$output,"++ $output",true);
	// 	$this->assertValidMarkup($output);
	// }

	function test_product_freeshipping () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertFalse(shopp('product','freeshipping'));
	}

	function test_product_hasimages () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		global $Shopp;
		$this->assertTrue(shopp('product','hasimages'));
		$this->assertTrue(shopp('product','has-images'));
		$this->assertEquals(1,count($Shopp->Product->images));
	}

	function test_product_hascategories () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','has-categories'));
		$this->assertEquals(2,count(ShoppProduct()->categories));
	}

	function test_product_incategory_byname () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','in-category','name=Uniforms'));
	}

	function test_product_incategory_byslug () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','in-category','slug=command-division'));
	}

	function test_product_incategory_byid () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','in-category','id=' . self::$category));
	}

	function test_product_category_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		ob_start();
		if (shopp('product','has-categories'))
			while(shopp('product','categories')) shopp('product','category');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Command DivisionUniforms',$output);
	}

	function test_product_image_tags () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		ob_start();
		if (shopp('product','has-images'))
			while(shopp('product','images')) shopp('product','image');
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('<img src="http://shopptest/store/images/652/UlitimateMatrixBRCollections.jpg?96,96,2395623139" alt="Ultimate Matrix Collection" width="96" height="96"  />',$output);
	}

	function test_product_hastags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','hastags'));
		$this->assertTrue(shopp('product','has-tags'));
		$this->assertEquals(2, count(ShoppProduct()->tags));
	}

	function test_product_tag_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		ob_start();
		if (shopp('product','has-tags'))
			while(shopp('product','tags')) shopp('product','tag');
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('FederationStarfleet',$output);
	}

	function test_product_tagged_byname () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','tagged','name=Starfleet'));
	}

	function test_product_tagged_byid () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','tagged','id='.self::$tag));
	}


	function test_product_hasspecs () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','hasspecs'));
		$this->assertTrue(shopp('product','has-specs'));

		$this->assertEquals(2, count(ShoppProduct()->specs));
	}

	function test_product_spec_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = array();
		if (shopp('product','has-specs'))
			while(shopp('product','specs')) $output[] = shopp('product','get-spec');

		$expected = array(
			'Department: Command',
			'Color: Gold'
		);
		$this->AssertEquals(count($output), count($expected));
		foreach ( $expected as $spec_content )
			$this->assertTrue(in_array($spec_content, $output));

	}

	function test_product_spec_tags_byname () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		ob_start();
		if (shopp('product','has-specs'))
			while(shopp('product','specs')) shopp('product','spec','name');
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('DepartmentColor',$output);

	}

	function test_product_spec_tags_bycontent () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$output = array();
		if (shopp('product','has-specs'))
			while(shopp('product','specs')) $output[] = shopp('product','get-spec','content');

		$expected = array(
			'Command',
			'Gold'
		);
		$this->AssertEquals(count($output), count($expected));
		foreach ( $expected as $spec_content )
			$this->assertTrue(in_array($spec_content, $output));
	}

	function test_product_outofstock () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertFalse(shopp('product','outofstock'));
	}

	function test_product_hasvariations () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$this->assertTrue(shopp('product','has-variations'));
	}

	function test_product_variations_menus () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$output = shopp('product.get-variations','mode=single');
		$this->assertValidMarkup($output);

		$output = shopp('product.get-variations','mode=multi');
		$this->assertValidMarkup($output);

	}

	function test_product_variation_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		ob_start();
		if (shopp('product','has-variations')) {
			while(shopp('product','variations')) {
				echo shopp('product','get-variation','id')."|";
				echo shopp('product','get-variation','label')."|";
				echo shopp('product','get-variation','type')."|";
				echo shopp('product','get-variation','sku')."|";
				echo shopp('product','get-variation','price')."|";
				echo shopp('product','get-variation','saleprice')."|";
				echo shopp('product','get-variation','stock')."|";
				echo shopp('product','get-variation','weight')."|";
				echo shopp('product','get-variation','shipfee')."|";
				echo shopp('product','get-variation','sale')."|";
				echo shopp('product','get-variation','shipping')."|";
				echo shopp('product','get-variation','tax')."|";
				echo shopp('product','get-variation','inventory')."|";
			}
		}
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('101|Small|Shipped|SFU-001-S|$19.99|$9.99|5|0|$0.00|1|1|1|1|102|Medium|Shipped|SFU-001-M|$22.55|$19.99|15|0|$0.00|1|1|1|1|103|Large|Shipped|SFU-001-L|$32.95|$24.95|1|0|$0.00|1|1|1|1|104|Brikar|Shipped|SFU-001-B|$55.00|$35.00|1|0|$0.00|1|1|1|1|',$output);

	}

	function test_product_input () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Product = shopp_product('command-uniform', 'slug');
		$output = shopp('product.get-input','type=text&name=Testing');

		$markup = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'text',
				'name' => 'products[' . $Product->id . '][data][Testing]',
				'id' => 'data-testing-' . $Product->id
			)
		);
		$this->assertTag($markup,$output,$output,true);
		$this->assertValidMarkup($output);
	}

	function test_product_addtocart () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		$Product = shopp_product('command-uniform', 'slug');

		shopp_set_setting('inventory','on');
		ShoppProduct()->outofstock = true;
		$output = shopp('product.get-addtocart');
		$this->assertEquals('<span class="outofstock">' . shopp_setting('outofstock_text') . '</span>', $output);
		ShoppProduct()->outofstock = false;
		shopp_set_setting('inventory','off');

		$output = shopp('product.get-addtocart');

		$markup = array(
			'tag' => 'input',
			'attributes' => array('type' => 'hidden','name' => 'products[' . $Product->id . '][product]','value' => $Product->id)
		);
		$this->assertTag($markup,$output,'',true);

		$markup = array(
			'tag' => 'input',
			'attributes' => array('type' => 'hidden','name' => 'cart','value' => 'add')
		);
		$this->assertTag($markup,$output,'',true);

		$markup = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'addtocart')
		);
		$this->assertTag($markup,$output,'',true);

		$this->assertValidMarkup($output);
	}


} // end ProductAPITests class
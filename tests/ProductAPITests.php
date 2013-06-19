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

	function setUp () {
		global $Shopp;
		parent::setUp();

		// capture original settings
		$this->_set_setting('inventory','on');
		$this->_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:3:"USA";s:8:"currency";a:2:{s:4:"code";s:3:"USD";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:1:"$";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:8:"imperial";s:6:"region";i:0;s:7:"country";s:2:"US";s:4:"zone";s:2:"OH";s:3:"vat";b:0;}'));
		$this->_set_setting('taxrates',shopp_setting('taxrates'));

		$Shopp->Flow->Controller = new Storefront();
		ShoppCatalog(new Catalog());

		$Product = shopp_product("Ultimate Matrix Collection", 'name');
		ShoppProduct($Product);
	}

	function tearDown () {
		// restore original settings
		$this->_restore_setting('inventory');
		$this->_restore_setting('base_operations');
		$this->_restore_setting('taxrates');
		parent::tearDown();
	}

	function test_product_id () {
		ob_start();
		shopp('product','id');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertTrue( ! empty(ShoppProduct()->id) );
		$this->assertEquals( ShoppProduct()->id, $output );
	}

	function test_product_name () {
		ob_start();
		shopp('product','name');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("Ultimate Matrix Collection",$output);
	}

	function test_product_slug () {
		ob_start();
		shopp('product','slug');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("ultimate-matrix-collection",$output);
	}

	function test_product_url () {
		global $Shopp;
		ob_start();
		shopp('product','url');
		$output = ob_get_contents();
		ob_end_clean();
		if ('' != get_option('permalink_structure')) $this->assertEquals('http://shopptest/store/ultimate-matrix-collection/',$output);
	}

	function test_product_description () {
		ob_start();
		shopp('product','description');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("6926c1177e6a3019cabd3525dea0921c",md5($output));
	}

	function test_product_summary () {
		ob_start();
		shopp('product','summary');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("3b31a462a3ec3b704934bdc5ae960af6",md5($output));
	}

	function test_product_found () {
		global $Shopp;
		$this->assertTrue(shopp('product','found'));
		$original = $Shopp->Product;
		$Shopp->Product = new Product(-1);
		$this->assertFalse(shopp('product','found'));
		$Shopp->Product = $original;
	}

	function test_product_isfeatured () {
		$this->assertTrue(shopp('product','isfeatured'));
		$this->assertTrue(shopp('product','is-featured'));
	}

	function test_product_price () {
		ob_start();
		shopp('product','price');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$34.86 &mdash; $129.95",$output);
	}

	function test_product_onsale () {
		$this->assertTrue(shopp('product','onsale'));
	}

	function test_product_saleprice () {
		ob_start();
		shopp('product','saleprice');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$15.06 &mdash; $63.86",$output);
	}

	function test_product_prices_withvat () {
		global $Shopp;

		shopp_set_setting('base_operations', array(
			'name' => 'USA',
		    'currency' => array(
		            'code' => 'USD',
		            'format' => array(
		                    'cpos' => 1,
		                    'currency' => '$',
		                    'precision' => 2,
		                    'decimals' => '.',
		                    'thousands' => ',',
							'grouping' => 3
		                ),
		        ),
		    'units' => 'imperial',
		    'region' => 0,
		    'country' => 'US',
		    'zone' => 'OH',
		    'vat' => true,
		));
		shopp_set_setting('taxrates', array(
			0 => array('rate' => 15,'country'=>'*')
		));

		ob_start();
		shopp('product','price');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$34.86 &mdash; $129.95",$output);

		ob_start();
		shopp('product','price','taxes=on');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$40.09 &mdash; $149.44",$output);

		ob_start();
		shopp('product','saleprice');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$15.06 &mdash; $63.86",$output);

		ob_start();
		shopp('product','saleprice','taxes=on');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$17.32 &mdash; $73.44",$output);
	}

	function test_product_hassavings () {
		$this->assertTrue(shopp('product','has-savings'));
	}

	function test_product_savings () {
		ob_start();
		shopp('product','savings');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("$19.80 &mdash; $66.09",$output);

		ob_start();
		shopp('product','savings','show=percent');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("51% &mdash; 57%",$output);
	}

	function test_product_weight () {
		ob_start();
		shopp('product','weight');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals("0.2 - 1.15 lb",$output);
	}

	function test_product_thumbnail () {
		ob_start();
		shopp('product','thumbnail');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertXmlStringEqualsXmlString('<img src="http://shopptest/store/images/652/UlitimateMatrixBRCollections.jpg?96,96,2395623139" alt="Ultimate Matrix Collection" width="96" height="96"/>',$output);
		$this->assertValidMarkup($output);
	}

	function test_product_gallery () {
		ob_start();
		shopp('product','gallery');
		$output = ob_get_contents();
		ob_end_clean();
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
		$this->assertFalse(shopp('product','freeshipping'));
	}

	function test_product_hasimages () {
		global $Shopp;
		$this->assertTrue(shopp('product','hasimages'));
		$this->assertTrue(shopp('product','has-images'));
		$this->assertEquals(1,count($Shopp->Product->images));
	}

	function test_product_hascategories () {
		global $Shopp;
		$this->assertTrue(shopp('product','has-categories'));
		$this->assertEquals(3,count($Shopp->Product->categories));
	}

	function test_product_incategory_byname () {
		$this->assertTrue(shopp('product','in-category','name=Entertainment'));
	}

	function test_product_incategory_byslug () {
		$this->assertTrue(shopp('product','in-category','slug=movies-tv'));
	}

	function test_product_incategory_byid () {
		$this->assertTrue(shopp('product','in-category','id=49'));
	}

	function test_product_category_tags () {
		ob_start();
		if (shopp('product','has-categories'))
			while(shopp('product','categories')) shopp('product','category');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Blu-RayEntertainmentMovies & TV',$output);
	}

	function test_product_image_tags () {
		ob_start();
		if (shopp('product','has-images'))
			while(shopp('product','images')) shopp('product','image');
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('<img src="http://shopptest/store/images/652/UlitimateMatrixBRCollections.jpg?96,96,2395623139" alt="Ultimate Matrix Collection" width="96" height="96"  />',$output);
	}

	function test_product_hastags () {
		global $Shopp;
		$this->assertTrue(shopp('product','hastags'));
		$this->assertTrue(shopp('product','has-tags'));
		$this->assertEquals(6,count($Shopp->Product->tags));
	}

	function test_product_tag_tags () {
		ob_start();
		if (shopp('product','has-tags'))
			while(shopp('product','tags')) shopp('product','tag');
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('bluraymatrixmovietrilogywachowskiwarner',$output);
	}

	function test_product_tagged_byname () {
		$this->assertTrue(shopp('product','tagged','name=matrix'));
	}

	function test_product_tagged_byid () {
		$this->assertTrue(shopp('product','tagged','id=57'));
	}


	function test_product_hasspecs () {
		global $Shopp;
		$this->assertTrue(shopp('product','hasspecs'));
		$this->assertTrue(shopp('product','has-specs'));

		$this->assertEquals(8, count(ShoppProduct()->specs));
	}

	function test_product_spec_tags () {
		$output = array();
		if (shopp('product','has-specs'))
			while(shopp('product','specs')) $output[] = shopp('product','get-spec');

		$expected = array(
			'Rating: R Rated',
			'Studio: Warner Home Video',
			'Run Time (in minutes): 415',
			'Format: Blu-Ray, DVD',
			'Language: English',
			'Screen Format: Widescreen',
			'Director: The Wachowski Brothers'
		);
		$this->AssertEquals(count($output), count($expected));
		foreach ( $expected as $spec_content )
			$this->assertTrue(in_array($spec_content, $output));

	}

	function test_product_spec_tags_byname () {
		ob_start();
		if (shopp('product','has-specs'))
			while(shopp('product','specs')) shopp('product','spec','name');
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('RatingStudioRun Time (in minutes)FormatLanguageScreen FormatDirector',$output);

	}

	function test_product_spec_tags_bycontent () {
		$output = array();
		if (shopp('product','has-specs'))
			while(shopp('product','specs')) $output[] = shopp('product','get-spec','content');

		$expected = array(
			'R Rated',
			'Warner Home Video',
			'415',
			'Blu-Ray, DVD',
			'English',
			'Widescreen',
			'The Wachowski Brothers'
		);
		$this->AssertEquals(count($output), count($expected));
		foreach ( $expected as $spec_content )
			$this->assertTrue(in_array($spec_content, $output));
	}

	function test_product_outofstock () {
		$this->assertFalse(shopp('product','outofstock'));
	}

	function test_product_hasvariations () {
		$this->assertTrue(shopp('product','has-variations'));
	}

	function test_product_variations_menus () {
		global $Shopp;

		ob_start();
		shopp('product','variations','mode=single');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($output);

		ob_start();
		shopp('product','variations','mode=multi');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($output);

	}

	function test_product_variation_tags () {
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
		$this->assertEquals('228|Blu-Ray|Shipped|BR-81|$129.95|$63.86|25|1.15 lb|$0.00|1|1|1|1|256|DVD|Shipped||$34.86|$15.06|0|0.2 lb|$0.00|1|1|1||',$output);

	}

	function test_product_input () {
		ob_start();
		shopp('product','input','type=text&name=Testing');
		$output = ob_get_contents();
		ob_end_clean();

		$markup = array(
			'tag' => 'input',
			'attributes' => array(
				'type' => 'text',
				'name' => 'products[94][data][Testing]',
				'id' => 'data-testing-94'
			)
		);
		$this->assertTag($markup,$output,'',true);
		$this->assertValidMarkup($output);
	}

	function test_product_addtocart () {
		global $Shopp;

		$Shopp->Product->outofstock = true;
		ob_start();
		shopp('product','addtocart');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('<span class="outofstock">Out of stock</span>',$output);
		$Shopp->Product->outofstock = false;

		ob_start();
		shopp('product','addtocart');
		$output = ob_get_contents();
		ob_end_clean();

		$markup = array(
			'tag' => 'input',
			'attributes' => array('type' => 'hidden','name' => 'products[94][product]','value' => '94')
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

?>
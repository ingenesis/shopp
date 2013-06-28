<?php
/**
 * CategoryAPITests
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 30 November, 2009
 * @package shopp
 * @subpackage tests
 **/
class CategoryAPITests extends ShoppTestCase {

	static $ships = array(
			'Constellation', 'Constitution', 'Defiant', 'Enterprise', 'Excalibur', 'Exeter', 'Farragut',
			'Hood', 'Intrepid', 'Lexington', 'Pegasus', 'Potemkin', 'Yorktown'
	);

	static $HeavyCruiser;
	static $Cheyenne;
	static $Constitution;

	static function setUpBeforeClass () {

		self::$HeavyCruiser = shopp_add_product_category('Heavy Cruiser', 'A large multi-purpose starship.');
		self::$Cheyenne = shopp_add_product_category('Cheyenne', '', self::$HeavyCruiser);
		self::$Constitution = shopp_add_product_category('Constitution', '', self::$HeavyCruiser);

		foreach ( self::$ships as $ship ) {
			$product = array(
				'name' => $ship,
				'publish' => array( 'flag' => true ),
				'categories'=> array('terms' => array(self::$HeavyCruiser, self::$Constitution, self::$Cheyenne)),
				'single' => array(
					'type' => 'Shipped',
					'price' => 99.99,
				)
			);
			$Product = shopp_add_product($product);
		}

		shopp('storefront.category','load=true&id='.self::$HeavyCruiser);
		shopp('category.load-products');
	}

	function tearDown () {
	}

	function test_category_url () {
		$actual = shopp('category.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '?shopp_category=heavy-cruiser', $actual);
	}

	function test_category_id () {
		$actual = shopp('category.get-id');
		$this->assertEquals(self::$HeavyCruiser, $actual);
	}

	function test_category_name () {
		$actual = shopp('category.get-name');
		$this->assertEquals('Heavy Cruiser',$actual);
	}

	function test_category_slug () {
		$actual = shopp('category.get-slug');
		$this->assertEquals('heavy-cruiser',$actual);
	}

	function test_category_description () {
		$actual = shopp('category.get-description');
		$this->assertEquals('A large multi-purpose starship.',$actual);
	}

	function test_category_products () {
		ob_start();
		if ( shopp('category','has-products') )
			while(shopp('category','products')) shopp('product','name');
		$output = ob_get_clean();

		$this->assertEquals(join('', self::$ships), $output);
	}

	function test_category_total () {
		$actual = shopp('category.get-total');
		$this->assertEquals('13', $actual);
	}

	function test_category_row () {
		shopp('category','has-products');

		// print_r(ShoppCollection());
		for ($i = 0; $i < 3; $i++) {
			shopp('category','products');
			shopp('category','row');
		}
		$this->assertTrue(shopp('category','row'));
	}

	function test_category_categories_tags () {
		ob_start();
		if ( shopp('category','has-categories') )
			while( shopp('category','subcategories') )
				shopp('subcategory','name');
		$actual = ob_get_clean();

		$this->assertEquals('CheyenneConstitution', $actual);
	}

	function test_category_subcategorylist () {
		ob_start();
		shopp('category','subcategory-list');
		$actual = ob_get_clean();

		$this->assertValidMarkup($actual);
	}

	function test_category_issubcategory () {
		$this->assertFalse(shopp('category','is-subcategory'));
		shopp('storefront.get-category', 'load=true&slug=constitution');
		$this->assertTrue(shopp('category','is-subcategory'));
	}

	function test_category_sectionlist () {
		ob_start();
		shopp('category','section-list');
		$actual = ob_get_clean();

		$this->assertValidMarkup($actual);
	}

	function test_category_pagination () {
		$actual = shopp('category.get-pagination');
		$this->assertValidMarkup($actual);
	}

	function test_category_facetedmenu () {
		ob_start();
		if ( shopp('category','has-faceted-menu') )
			shopp('category','faceted-menu');
		$actual = ob_get_clean();
		$this->assertValidMarkup($actual);
	}

/*
	function test_category_coverimage () {
		shopp('catalog','category','id=17&load=1');

		ob_start();
		shopp('category','thumbnail');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertXmlStringEqualsXmlString('<img src="http://shopptest/store/images/691/idIconGaming.gif?96,96,3901571377" title="Games" alt="Controller Icon" width="96" height="95"/>',$actual,$actual);
		$this->assertValidMarkup($actual);
	}

	function test_category_image_tags () {
		ob_start();
		if (shopp('category','has-images'))
			while(shopp('category','images')) shopp('category','image');
		$output = ob_get_contents();
		ob_end_clean();
	}
*/

} // end CategoryAPITests class

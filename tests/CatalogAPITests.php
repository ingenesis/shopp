<?php
/**
 * CatalogAPITests
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 21 October, 2009
 * @package
 **/
class CatalogAPITests extends ShoppTestCase {

	static $HeavyCruiser;
	static $ships = array(
			'Constellation', 'Defiant', 'Enterprise', 'Excalibur', 'Exeter', 'Farragut',
			'Hood', 'Intrepid', 'Lexington', 'Pegasus', 'Potemkin', 'Yorktown'
	);

	static function product ($category) {
		return array(
			'name' => 'NCC-'. round(rand()*1000,0),
			'publish' => array( 'flag' => true ),
			'categories'=> array('terms' => array($category)),
			'single' => array(
				'type' => 'Shipped',
				'price' => round(rand()*10,2),
			)
		);
	}

	static function setUpBeforeClass () {

		$Shopp = Shopp::object();
		$Shopp->Flow->handler('Storefront');

		$Product = shopp_add_product($product);
		$category = shopp_add_product_category('Battle Cruiser');
		$product = shopp_add_product(self::product($category));

		foreach ( self::$ships as $ship ) {
			$category = shopp_add_product_category($ship, '', self::$HeavyCruiser);
			shopp_add_product(self::product($category));
		}

	}

	function test_catalog_url () {
		$actual = shopp('catalog.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/?shopp_page=shop', $actual);
	}

	// Can't get this to work yet, need better http environment emulator
	// function test_catalog_iscatalog () {
	// 	global $Shopp;
	// 	$this->http('http://shopptest/');
	// 	$Shopp->Catalog->type = 'catalog';
	// 	$this->assertTrue(shopp('catalog','is-catalog'));
	// }

	// function test_catalog_tagcloud () {
	// 	$actual = shopp('storefront.get-tagcloud');
	// 	$this->assertValidMarkup($actual);
	// }

	function test_catalog_categories () {
		$this->assertTrue(shopp('storefront','has-categories'));

		$Shopp = Shopp::object();
		$expected = 13;
		$this->assertEquals($expected,count($Shopp->Catalog->categories));
		for ($i = 0; $i < $expected; $i++)
			$this->assertTrue(shopp('catalog','categories'));
	}

	function test_catalog_categorylist () {
		ob_start();
		shopp('catalog','category-list');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','category-list','hierarchy=on&showall=on&linkall=on');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_views () {
		ob_start();
		shopp('catalog','views');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_orderbylist () {
		global $Shopp;
		$_SERVER['REQUEST_URI'] = "/";
		$Shopp->Catalog = new ShoppCatalog();
		$Shopp->Category = new NewProducts();
		ob_start();
		shopp('catalog','orderby-list');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','orderby-list','dropdown=false');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_breadcrumb () {
		ob_start();
		shopp('catalog','breadcrumb');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_search () {
		ob_start();
		shopp('catalog','search');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','search','type=menu');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_collections () {
		$Storefront = new Storefront();
		ob_start();
		shopp('catalog','catalog-products','show=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','new-products','show=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','featured-products','show=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','onsale-products','show=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','bestseller-products','show=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','random-products','show=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','tag-products','show=3&tag=wordpress');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','related-products','show=3&product=114');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);

		ob_start();
		shopp('catalog','search-products','show=3&search=wordpress');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_catalog_category () {
		ob_start();
		shopp('catalog','category','show=3&id=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_catalog_product () {
		ob_start();
		shopp('catalog','product','id=114');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_catalog_sideproduct () {
		ob_start();
		shopp('catalog','sideproduct','source=product&product=114');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_storefront_accountmenu () {
		ShoppStorefront()->dashboard();

		ob_start();
		while (shopp('storefront','account-menu')) {
			shopp('storefront','account-menuitem');
			echo ' ';
			shopp('storefront','account-menuitem','url');
			echo ' ';
		}
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('My Account http://' . WP_TESTS_DOMAIN . '/?shopp_page=account&profile Downloads http://' . WP_TESTS_DOMAIN . '/?shopp_page=account&downloads Your Orders http://' . WP_TESTS_DOMAIN . '/?shopp_page=account&orders Logout http://' . WP_TESTS_DOMAIN . '/?shopp_page=account&logout ',$actual);
	}

} // end CatalogAPITests class
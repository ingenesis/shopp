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

	function setUp () {
		global $Shopp;
		parent::setUp();
		$Shopp->Flow->handler('Storefront');
		$Shopp->Catalog = false;
		$Shopp->Catalog = new Catalog();
	}

	function test_catalog_url () {
		ob_start();
		shopp('catalog','url');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('http://shopptest/store/',$actual);
	}

	// @deprecated
	// function test_catalog_type () {
	// 	ob_start();
	// 	shopp('catalog','type');
	// 	$actual = ob_get_contents();
	// 	ob_end_clean();
	// 	$this->assertEquals('catalog',$actual);
	// }

	// Can't get this to work yet, need better http environment emulator
	// function test_catalog_iscatalog () {
	// 	global $Shopp;
	// 	$this->http('http://shopptest/');
	// 	$Shopp->Catalog->type = 'catalog';
	// 	$this->assertTrue(shopp('catalog','is-catalog'));
	// }

	function test_catalog_tagcloud () {
		ob_start();
		shopp('catalog','tagcloud');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_categories () {
		global $Shopp;
		shopp('catalog','has-categories');
		$this->assertTrue(shopp('catalog','has-categories'));
		$expected = 22;
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
		$Shopp->Catalog = new Catalog();
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

		$this->assertEquals('My Account http://shopptest/store/account/?profile Downloads http://shopptest/store/account/?downloads Your Orders http://shopptest/store/account/?orders Logout http://shopptest/store/account/?logout ',$actual);
	}

} // end CatalogAPITests class

?>
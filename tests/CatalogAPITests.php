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

	static $HeavyCruiser  = 'Battle Cruiser';
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
		$Shopp->Flow->handler('ShoppStorefront');

		$Product = shopp_add_product($product);
		self::$HeavyCruiser = shopp_add_product_category('Battle Cruiser');
		$product = shopp_add_product(self::product($HeavyCruiser));

		foreach ( self::$ships as $ship ) {
			$category = shopp_add_product_category($ship, '', self::$HeavyCruiser);
			if ( 'Potemkin' == $ship ) continue;
			shopp_add_product(self::product($category));
		}

	}

	function test_catalog_url () {
		// $this->markTestSkipped('Skipped.');
		$actual = shopp('catalog.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/?shopp_page=shop', $actual);
	}

	function test_catalog_categories () {
		// $this->markTestSkipped('Skipped.');
		$this->assertTrue(shopp('storefront','has-categories'));

		$Shopp = Shopp::object();
		$expected = 12;
		$this->assertEquals($expected,count($Shopp->Catalog->categories));
		for ($i = 0; $i < $expected; $i++)
			$this->assertTrue(shopp('catalog','categories'));
	}

	function test_catalog_categorylist () {
		// $this->markTestSkipped('Skipped.');
		ob_start();
		shopp('storefront.category-list');
		$actual = ob_get_clean();

		$this->assertValidMarkup($actual);
		$expected = array(
			'tag' => 'ul',
			'attributes' => array('class' => 'children'),
			'children' => array(
				'count' => count(self::$ships) - 1,
				'only' => array('tag' => 'li')
			)
		);
		$this->assertTag($expected, $actual, 'storefront.category-list failed');

		ob_start();
		shopp('storefront.category-list', 'before=<span>Before</span>&after=<span>After</span>');
		$actual = ob_get_clean();
		$expected = array('tag' => 'span', 'content' => 'Before');
		$this->assertTag($expected, $actual, 'category-list before failed');
		$expected = array('tag' => 'span', 'content' => 'After');
		$this->assertTag($expected, $actual, 'category-list after failed');

		ob_start();
		shopp('storefront.category-list', 'class=css-class');
		$actual = ob_get_clean();
		$expected = array('tag' => 'ul', 'attributes' => array('class' => 'shopp-categories-menu css-class'));
		$this->assertTag($expected, $actual, 'category-list class failed');

		ob_start();
		shopp('storefront.category-list', 'exclude=' . self::$HeavyCruiser);
		$actual = ob_get_clean();
		$expected = array('tag' => 'a', 'content' => 'Battle Cruiser');
		$this->assertNotTag($expected, $actual, 'category-list exclude failed', true);

		ob_start();
		shopp('storefront.category-list', 'orderby=name&order=DESC');
		$actual = strip_tags(ob_get_clean());
		$actual = str_replace(array("\t", "\n"),"",$actual);
		$expected = 'Battle CruiserYorktownPegasusLexingtonIntrepidHoodFarragutExeterExcaliburEnterpriseDefiantConstellation';
		$this->assertEquals($expected, $actual, 'category-list orderby/order DESC failed');

		ob_start();
		shopp('storefront.category-list', 'hierarchy=on');
		$actual = ob_get_clean();
		$expected = array('tag' => 'li', 'content' => 'Battle Cruiser', 'child' => array('tag' => 'ul'));
		$this->assertTag($expected, $actual, 'category-list hierarchy=on failed');

		ob_start();
		shopp('storefront.category-list', 'hierarchy=on&depth=1');
		$actual = ob_get_clean();
		$expected = array('tag' => 'ul', 'children' => array('count' => 1));
		$this->assertTag($expected, $actual, 'category-list depth=1 failed');

		ob_start();
		shopp('storefront.category-list', 'hierarchy=on&depth=2');
		$actual = ob_get_clean();
		$expected = array('tag' => 'ul', 'attributes' => array('class'=>'children'),'children' => array('count' => count(self::$ships)-1));
		$this->assertTag($expected, $actual, 'category-list depth=2 failed');

		ob_start();
		shopp('storefront.category-list', 'childof=' . self::$HeavyCruiser);
		$actual = ob_get_clean();
		$expected = array('tag' => 'ul', 'children' => array('count' => count(self::$ships) - 1));
		$this->assertTag($expected, $actual, 'category-list childof failed');

		ob_start();
		shopp('storefront.category-list', 'wraplist=off&section=on&sectionterm=' . self::$HeavyCruiser);
		$actual = ob_get_clean();
		$expected = array('tag' => 'ul', 'children' => array('count' => count(self::$ships) - 1));
		$this->assertTag($expected, $actual, 'category-list section failed');

		ob_start();
		shopp('storefront.category-list', 'showall=on');
		$actual = ob_get_clean();
		$expected = array('tag' => 'li', 'content' => 'Potemkin');
		$this->assertTag($expected, $actual, 'category-list showall=on failed');

		ob_start();
		shopp('storefront.category-list', 'showall=off');
		$actual = ob_get_clean();
		$expected = array('tag' => 'a', 'content' => 'Potemkin');
		$this->assertNotTag($expected, $actual, 'category-list showall=off failed');

		ob_start();
		shopp('storefront.category-list', 'showall=on&linkall=on');
		$actual = ob_get_clean();
		$expected = array('tag' => 'a', 'content' => 'Potemkin');
		$this->assertTag($expected, $actual, 'category-list linkall=on failed');

		ob_start();
		shopp('storefront.category-list', 'showall=on&linkall=off');
		$actual = ob_get_clean();
		$expected = array('tag' => 'a', 'content' => 'Potemkin');
		$this->assertNotTag($expected, $actual, 'category-list linkall=off failed');

		ob_start();
		shopp('storefront.category-list', 'wraplist=off&hierarchy=off');
		$actual = ob_get_clean();
		$expected = array('tag' => 'ul');
		$this->assertNotTag($expected, $actual, 'category-list wraplist=off failed');

		ob_start();
		shopp('storefront.category-list', 'showsmart=before');
		$actual = strip_tags(ob_get_clean());
		$actual = str_replace(array("\t", "\n"),"",$actual);
		$expected = 'Catalog ProductsNew ProductsFeatured ProductsOn SaleBestsellersRecently ViewedRandom ProductsBattle CruiserConstellationDefiantEnterpriseExcaliburExeterFarragutHoodIntrepidLexingtonPegasusYorktown';
		$this->assertEquals($expected, $actual);

		ob_start();
		shopp('storefront.category-list', 'showsmart=after');
		$actual = strip_tags(ob_get_clean());
		$actual = str_replace(array("\t", "\n"),"",$actual);

		$expected = 'Battle CruiserConstellationDefiantEnterpriseExcaliburExeterFarragutHoodIntrepidLexingtonPegasusYorktownCatalog ProductsNew ProductsFeatured ProductsOn SaleBestsellersRecently ViewedRandom Products';
		$this->assertEquals($expected, strip_tags($actual));

		ob_start();
		shopp('storefront.category-list', 'dropdown=on');
		$actual = ob_get_clean();
		$this->assertValidMarkup($actual);
		$expected = array('tag' => 'form', 'attributes' => array('class' => 'category-list-menu'),'child' => array('tag' => 'select', 'attributes' => array('name' =>'shopp_cats')));
		$this->assertTag($expected, $actual, 'category-list dropdown=on failed');
		$expected = array('tag' => 'select', 'children' => array('count'=>count(self::$ships)+1));
		$this->assertTag($expected, $actual, 'category-list dropdown=on failed');

	}

	function test_catalog_views () {
		// $this->markTestSkipped('Skipped.');
		ob_start();
		shopp('catalog','views');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_orderbylist () {
		// $this->markTestSkipped('Skipped.');
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
		// $this->markTestSkipped('Skipped.');
		ob_start();
		shopp('catalog','breadcrumb');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertValidMarkup($actual);
	}

	function test_catalog_search () {
		// $this->markTestSkipped('Skipped.');
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
		// $this->markTestSkipped('Skipped.');
		$Storefront = new ShoppStorefront();
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
		// $this->markTestSkipped('Skipped.');
		ob_start();
		shopp('catalog','category','show=3&id=3');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_catalog_product () {
		// $this->markTestSkipped('Skipped.');
		ob_start();
		shopp('catalog','product','id=114');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_catalog_sideproduct () {
		// $this->markTestSkipped('Skipped.');
		ob_start();
		shopp('catalog','sideproduct','source=product&product=114');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_storefront_accountmenu () {
		// $this->markTestSkipped('Skipped.');
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
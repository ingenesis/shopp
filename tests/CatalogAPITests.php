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
	static $products = array();

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
		$Shopp->Flow->controller('ShoppStorefront');

		$Product = shopp_add_product($product);
		self::$HeavyCruiser = shopp_add_product_category('Battle Cruiser');
		$product = shopp_add_product(self::product($HeavyCruiser));

		foreach ( self::$ships as $ship ) {
			$category = shopp_add_product_category($ship, '', self::$HeavyCruiser);
			if ( 'Potemkin' == $ship ) continue;
			shopp_add_product(self::product($category));
		}

	}

	public static function tearDownAfterClass () {
		foreach ($products as $id)
			shopp_rmv_product($id);

		shopp_rmv_product_category(self::$HeavyCruiser);
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
		$actual = shopp('storefront.get-category-list');

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

        $actual = shopp('storefront.get-category-list', 'before=<span>Before</span>&after=<span>After</span>');
        $expected = array('tag' => 'span', 'content' => 'Before');
        $this->assertTag($expected, $actual, 'category-list before failed');
        $expected = array('tag' => 'span', 'content' => 'After');
        $this->assertTag($expected, $actual, 'category-list after failed');

        $actual = shopp('storefront.get-category-list', 'class=css-class');
        $expected = array('tag' => 'ul', 'attributes' => array('class' => 'shopp-categories-menu css-class'));
        $this->assertTag($expected, $actual, 'category-list class failed');

        $actual = shopp('storefront.get-category-list', 'exclude=' . self::$HeavyCruiser);
        $expected = array('tag' => 'a', 'content' => 'Battle Cruiser');
        $this->assertNotTag($expected, $actual, 'category-list exclude failed', true);

        $actual = shopp('storefront.get-category-list', 'orderby=name&order=DESC');
        $actual = strip_tags($actual);
        $actual = str_replace(array("\t", "\n"),"",$actual);
        $expected = 'Battle CruiserYorktownPegasusLexingtonIntrepidHoodFarragutExeterExcaliburEnterpriseDefiantConstellation';
        $this->assertEquals($expected, $actual, 'category-list orderby/order DESC failed');

        $actual = shopp('storefront.get-category-list', 'hierarchy=on');
        $expected = array('tag' => 'li', 'content' => 'Battle Cruiser', 'child' => array('tag' => 'ul'));
        $this->assertTag($expected, $actual, 'category-list hierarchy=on failed');

        $actual = shopp('storefront.get-category-list', 'hierarchy=on&depth=1');
        $expected = array('tag' => 'ul', 'children' => array('count' => 1));
        $this->assertTag($expected, $actual, 'category-list depth=1 failed');

        $actual = shopp('storefront.get-category-list', 'hierarchy=on&depth=2');
        $expected = array('tag' => 'ul', 'attributes' => array('class'=>'children'),'children' => array('count' => count(self::$ships)-1));
        $this->assertTag($expected, $actual, 'category-list depth=2 failed');

        $actual = shopp('storefront.get-category-list', 'childof=' . self::$HeavyCruiser);
        $expected = array('tag' => 'ul', 'children' => array('count' => count(self::$ships) - 1));
        $this->assertTag($expected, $actual, 'category-list childof failed');

        $actual = shopp('storefront.get-category-list', 'wraplist=off&section=on&sectionterm=' . self::$HeavyCruiser);
        $expected = array('tag' => 'ul', 'children' => array('count' => count(self::$ships) - 1));
        $this->assertTag($expected, $actual, 'category-list section failed');

        $actual = shopp('storefront.get-category-list', 'showall=on');
        $expected = array('tag' => 'li', 'content' => 'Potemkin');
        $this->assertTag($expected, $actual, 'category-list showall=on failed');

        $actual = shopp('storefront.get-category-list', 'showall=off');
        $expected = array('tag' => 'a', 'content' => 'Potemkin');
        $this->assertNotTag($expected, $actual, 'category-list showall=off failed');

        $actual = shopp('storefront.get-category-list', 'showall=on&linkall=on');
        $expected = array('tag' => 'a', 'content' => 'Potemkin');
        $this->assertTag($expected, $actual, 'category-list linkall=on failed');

        $actual = shopp('storefront.get-category-list', 'showall=on&linkall=off');
        $expected = array('tag' => 'a', 'content' => 'Potemkin');
        $this->assertNotTag($expected, $actual, 'category-list linkall=off failed');

        $actual = shopp('storefront.get-category-list', 'wraplist=off&hierarchy=off');
        $expected = array('tag' => 'ul');
        $this->assertNotTag($expected, $actual, 'category-list wraplist=off failed');

        $actual = shopp('storefront.get-category-list', 'showsmart=before');
        $actual = strip_tags($actual);
        $actual = str_replace(array("\t", "\n"),"",$actual);
        $expected = 'Catalog ProductsNew ProductsFeatured ProductsOn SaleBestsellersRecently ViewedRandom ProductsBattle CruiserConstellationDefiantEnterpriseExcaliburExeterFarragutHoodIntrepidLexingtonPegasusYorktown';
        $this->assertEquals($expected, $actual);

                $actual = shopp('storefront.get-category-list', 'showsmart=after');
                $actual = strip_tags($actual);
                $actual = str_replace(array("\t", "\n"),"",$actual);
                $expected = 'Battle CruiserConstellationDefiantEnterpriseExcaliburExeterFarragutHoodIntrepidLexingtonPegasusYorktownCatalog ProductsNew ProductsFeatured ProductsOn SaleBestsellersRecently ViewedRandom Products';
                $this->assertEquals($expected, strip_tags($actual));

        $actual = shopp('storefront.get-category-list', 'dropdown=on');
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

		$actual = shopp('catalog.get-catalog-products','show=3');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-new-products','show=3');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-featured-products','show=3');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-onsale-products','show=3');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-bestseller-products','show=3');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-random-products','show=3');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-tag-products','show=3&tag=wordpress');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-related-products','show=3&product=114');
		$this->assertValidMarkup($actual);

		$actual = shopp('catalog.get-search-products','show=3&search=wordpress');
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
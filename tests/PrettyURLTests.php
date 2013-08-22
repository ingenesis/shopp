<?php
/**
 * URLTests
 *
 *
 * @author Jonathan Davis, Dave Kress
 * @version 1.0
 * @copyright Ingenesis Limited, 28 November, 2011
 * @package
 **/

/**
 * Initialize
 **/

class PrettyURLTests extends ShoppTestCase {

	static function setUpBeforeClass () {

		global $wp_rewrite;
        $wp_rewrite->init();
		$wp_rewrite->set_permalink_structure('/%postname%/');
		$wp_rewrite->flush_rules();

		// update_option('permalink_structure','/%postname%/');
		// flush_rewrite_rules();

		// Re-register taxonomies
		$Shopp = Shopp::object();
		$Shopp->pages();
		$Shopp->collections();
		$Shopp->taxonomies();
		$Shopp->products();

		$HeavyCruiser = shopp_add_product_category('Heavy Cruiser');


		$args = array(
			'name' => 'USS Enterprise',
			'publish' => array('flag' => true),
			'single' => array(
				'type' => 'Shipped',
				'price' => 1701,
		        'sale' => array(
		            'flag' => true,
		            'price' => 17.01
		        ),
				'taxed'=> true,
				'shipping' => array('flag' => true, 'fee' => 1.50, 'weight' => 52.7, 'length' => 285.9, 'width' => 125.6, 'height' => 71.5),
				'inventory' => array(
					'flag' => true,
					'stock' => 1,
					'sku' => 'NCC-1701'
				)
			),
			'specs' => array(
				'Class' => 'Constitution',
				'Category' => 'Heavy Cruiser',
				'Decks' => 23,
				'Officers' => 40,
				'Crew' => 390,
				'Max Vistors' => 50,
				'Max Accommodations' => 800,
				'Phaser Force Rating' => '2.5 MW',
				'Torpedo Force Rating' => '9.7 isotons'
				),
			'categories'=> array('terms' => array($HeavyCruiser))
		);

		shopp_add_product($args);

		$shipsoftheline = array(
			'Constellation', 'Constitution', 'Defiant', 'Enterprise', 'Excalibur', 'Exeter', 'Farragut',
			'Hood', 'Intrepid', 'Lexington', 'Potemkin', 'Yorktown', 'Pegasus'
		);

		foreach ($shipsoftheline as $ship) {
			$product = array(
				'name' => $ship,
				'publish' => array( 'flag' => true ),
				'categories'=> array('terms' => array($HeavyCruiser)),
				'single' => array(
					'type' => 'Shipped',
					'price' => 99.99,
				)
			);
			shopp_add_product($product);
		}

	}

	static function tearDownAfterClass () {
		parent::tearDownAfterClass();
		global $wp_rewrite;
        $wp_rewrite->init();
		$wp_rewrite->set_permalink_structure('');
		$wp_rewrite->flush_rules();

		// update_option('permalink_structure','/%postname%/');
		// flush_rewrite_rules();

		// Re-register taxonomies
		$Shopp = Shopp::object();
		$Shopp->pages();
		$Shopp->collections();
		$Shopp->taxonomies();
		$Shopp->products();

	}


	function test_cart_url () {
		$actual = shopp('cart.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/cart/', $actual);
	}

	function test_checkout_url () {
		$actual = shopp('checkout.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/checkout/', $actual);
	}

	function test_account_url () {
		$actual = shopp('customer.get-accounturl');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/account/', $actual);
	}

	function test_product_url () {

		$Product = shopp_product('uss-enterprise', 'slug');
		ShoppProduct($Product);

		$actual = shopp('product.get-url');

		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/uss-enterprise/', $actual);

	}

	function test_category_url () {

		shopp('storefront.category', 'slug=heavy-cruiser&load=true');
		$actual = shopp('category.get-url');

		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/category/heavy-cruiser/', $actual);

	}

	function test_category_paginated_url () {

		shopp_set_setting('catalog_pagination',10);
		shopp('storefront.category', 'slug=heavy-cruiser&load=true');
		shopp('collection', 'load-products'); // Load the products

		$actual = shopp('collection.get-pagination');

		$markup = array(
			'tag' => 'a',
			'attributes' => array(
				'href' => 'http://' . WP_TESTS_DOMAIN . '/shop/category/heavy-cruiser/page/2/',
			),
			'content' => '2'
		);

		$this->assertTag($markup, $actual, $actual, true);
		$this->assertValidMarkup($actual);
	}

	function test_category_feed_url () {
		shopp('storefront','category','slug=heavy-cruiser&load=true');
		$actual = shopp('category.get-feed-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/category/heavy-cruiser/feed',$actual);
	}

	function test_catalog_url () {
		$actual = shopp('storefront.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/', $actual);
	}

	function test_catalogproducts_url () {
		shopp('storefront.catalog-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/catalog/',$actual);
	}

	function test_newproducts_url () {
		shopp('storefront.new-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/new/',$actual);
	}

	function test_featuredproducts_url () {
		shopp('storefront.featured-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/featured/',$actual);
	}

	function test_onsaleproducts_url () {
		shopp('storefront.onsale-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/onsale/',$actual);

	}

	function test_bestsellerproducts_url () {
		shopp('storefront.bestseller-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/bestsellers/',$actual);
	}

	function test_alsoboughtproducts_url () {
		shopp('storefront.alsobought-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/alsobought/',$actual);
	}

	function test_randomproducts_url () {
		shopp('storefront.random-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/random/',$actual);
	}


	function test_relatedproducts_url () {
		shopp('storefront.related-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/related/',$actual);
	}

	function test_tagproducts_url () {
		shopp('storefront.tag-products','load=true');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/tag/',$actual);
	}

	function test_searchproducts_url () {
		shopp('storefront.search-products','load=true&search=uss+enterprise');
		$actual = shopp('collection.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/shop/collection/search-results/?s=uss+enterprise&s_cs=1',$actual);
	}

} // end PrettyURLTests class
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

class DefaultURLTests extends ShoppTestCase {

	private $permastruct = false;
	private $extra_permastructs = false;

	function setUp () {
		parent::setUp();

		global $wp_rewrite;
		$this->extra_permastructs = $wp_rewrite->extra_permastructs;
		$this->permastruct = get_option('permalink_structure');
		update_option('permalink_structure','');
		$wp_rewrite->extra_permastructs = array();
		flush_rewrite_rules();

	}

	function tearDown() {

		parent::tearDown();
		// Set back to original (Pretty)
		global $wp_rewrite;
		$wp_rewrite->extra_permastructs = $this->extra_permastructs;
		update_option('permalink_structure', $this->permastruct);
		flush_rewrite_rules();
		unset($this->extra_permastructs,$this->permastruct);

	}

	function test_cart_url () {
		ob_start();
		shopp('cart','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_page=cart',$actual);
	}

	function test_checkout_url () {
		ob_start();
		shopp('checkout','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_page=checkout',$actual);
	}

	function test_account_url () {
		ob_start();
		shopp('customer','accounturl');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_page=account',$actual);
	}

	function test_product_url () {

		$Product = shopp_product("Ultimate Matrix Collection", 'name');
		ShoppProduct($Product);

		ob_start();
		shopp('product','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_product=ultimate-matrix-collection',$actual);
	}

	function test_category_url () {
		shopp('catalog','category','slug=apparel&load=true');
		ob_start();
		shopp('category','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_category=apparel',$actual);
	}

	function test_category_paginated_url () {

		shopp('catalog','category','slug=books&load=true');
		shopp('collection','has-products');

		ob_start();
		shopp('collection','pagination');
		$actual = ob_get_contents();
		ob_end_clean();


		$markup = array(
			'tag' => 'a',
			'attributes' => array(
				'href' => 'http://shopptest/?shopp_category=books&paged=2',
			),
			'content' => '2'
		);
		$this->assertTag($markup,$actual,var_export($markup,true).' does not match '.$actual);
		$this->assertValidMarkup($actual);
	}

	function test_category_feed_url () {

		shopp('catalog','category','slug=apparel&load=true');
		ob_start();
		shopp('category','feed-url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_category=apparel&src=category_rss',$actual);
	}


	function test_catalog_url () {
		ob_start();
		shopp('catalog','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_page=store',$actual);
	}

	function test_catalogproducts_url () {

		shopp('catalog','catalog-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=catalog',$actual);
	}

	function test_newproducts_url () {

		shopp('catalog','new-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=new',$actual);
	}

	function test_featuredproducts_url () {

		shopp('catalog','featured-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=featured',$actual);
	}

	function test_onsaleproducts_url () {

		shopp('catalog','onsale-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=onsale',$actual);
	}

	function test_bestsellerproducts_url () {

		shopp('catalog','bestseller-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=bestsellers',$actual);
	}

	function test_alsoboughtproducts_url () {

		shopp('catalog','alsobought-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=alsobought',$actual);
	}

	function test_randomproducts_url () {

		shopp('catalog','random-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=random',$actual);
	}

	function test_relatedproducts_url () {

		shopp('catalog','related-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=related',$actual);
	}

	function test_tagproducts_url () {

		shopp('catalog','tag-products','tag=action&load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=tag',$actual);
	}

	function test_searchproducts_url () {


		shopp('catalog','search-products','search=Star+Wars&load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/?shopp_collection=search-results&s=Star+Wars&s_cs=1',$actual);
	}


} // end DefaultURLTests class

?>
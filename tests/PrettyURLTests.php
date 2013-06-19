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

	function test_cart_url () {

		ob_start();
		shopp('cart','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/cart/',$actual);
	}

	function test_checkout_url () {

		ob_start();
		shopp('checkout','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/checkout/',$actual);
	}

	function test_account_url () {

		ob_start();
		shopp('customer','accounturl');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/account/',$actual);
	}

	function test_product_url () {

		$Product = shopp_product("Ultimate Matrix Collection", 'name');
		ShoppProduct($Product);

		ob_start();
		shopp('product','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/ultimate-matrix-collection/',$actual);
	}

	function test_category_url () {

		shopp('catalog','category','slug=apparel&load=true');
		ob_start();
		shopp('category','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/category/apparel/',$actual);
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
				'href' => 'http://shopptest/store/category/books/page/2/',
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

		$this->assertEquals('http://shopptest/store/category/apparel/feed',$actual);
	}

	function test_catalog_url () {

		ob_start();
		shopp('catalog','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/',$actual);
	}

	function test_catalogproducts_url () {

	    shopp('catalog','catalog-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/catalog/',$actual);
	}

	function test_newproducts_url () {

	    shopp('catalog','new-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/new/',$actual);
	}

	function test_featuredproducts_url () {

	    shopp('catalog','featured-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/featured/',$actual);
	}

	function test_onsaleproducts_url () {

	    shopp('catalog','onsale-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/onsale/',$actual);
	}

	function test_bestsellerproducts_url () {

	    shopp('catalog','bestseller-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/bestsellers/',$actual);
	}

	function test_alsoboughtproducts_url () {

	    shopp('catalog','alsobought-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/alsobought/',$actual);
	}

	function test_randomproducts_url () {

	    shopp('catalog','random-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/random/',$actual);
	}

	function test_relatedproducts_url () {

	    shopp('catalog','related-products','load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/related/',$actual);
	}

	function test_tagproducts_url () {

	    shopp('catalog','tag-products','tag=action&load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/tag/',$actual);
	}

	function test_searchproducts_url () {

	    shopp('catalog','search-products','search=Star+Wars&load=true');
		ob_start();
		shopp('collection','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('http://shopptest/store/collection/search-results/?s=Star+Wars&s_cs=1',$actual);
	}

} // end PrettyURLTests class

?>
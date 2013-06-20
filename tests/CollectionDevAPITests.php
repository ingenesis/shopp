<?php
/**
* tests for api/collection.php
*/
class CollectionDevAPITests extends ShoppTestCase
{

	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		if ( ! ShoppStorefront() ) ShoppStorefront(new Storefront());
	}

	function test_shopp_add_product_category () {
		global $test_shopp_add_product_category;

		$this->AssertFalse( ! $parent = shopp_add_product_category('Unit Test Category Parent','Product Category for Unit Testing') );
		$this->AssertFalse( ! $child = shopp_add_product_category('Unit Test Category Child', 'Product Category for Unit Testing', $parent) );
		$this->AssertFalse( ! $grandchild = shopp_add_product_category('Unit Test Category Grand-Child', 'Product Category for Unit Testing', $child) );
		$this->AssertFalse( ! $secondgrandchild = shopp_add_product_category('Unit Test Category 2nd Grand-Child', 'Product Category for Unit Testing', $child) );
		$this->AssertFalse( ! $ggrandchild = shopp_add_product_category('Unit Test Category Great Grand-Child', 'Product Category for Unit Testing', $secondgrandchild) );

		// check hierarchy
		$hierarchy = _get_term_hierarchy(ProductCategory::$taxon);
		$this->AssertTrue(in_array($parent, array_keys($hierarchy)));
		$this->AssertTrue(in_array($child, $hierarchy[$parent]));

		$this->AssertTrue(in_array($child, array_keys($hierarchy)));
		$this->AssertTrue(in_array($grandchild, $hierarchy[$child]));
		$this->AssertTrue(in_array($secondgrandchild, $hierarchy[$child]));

		$this->AssertTrue(in_array($secondgrandchild, array_keys($hierarchy)));
		$this->AssertTrue(in_array($ggrandchild, $hierarchy[$secondgrandchild]));

		$test_shopp_add_product_category = array($parent, $child, $grandchild, $secondgrandchild, $ggrandchild);
		foreach ( $test_shopp_add_product_category as $cat ) {
			$this->AssertTrue( is_a($Cat = shopp_product_category($cat), 'ProductCategory'));
			$this->AssertTrue( $Cat->id == $cat );
		}

	}

	function test_shopp_rmv_product_category () {
		global $test_shopp_add_product_category;
		foreach ( $test_shopp_add_product_category as $destroy ) {
			$this->AssertTrue( shopp_rmv_product_category($destroy) );
			$this->AssertFalse( shopp_product_category($destroy) );
		}
	}

	function test_shopp_product_categories() {
		$cats = shopp_product_categories();
		foreach ( $cats as $index => $Cat ) {
			$this->AssertTrue( is_a($Cat, 'ProductCategory'));
			$this->AssertEquals( $Cat->id, $index );
		}

		$cats = shopp_product_categories(array('index'=>'slug'));
		foreach ( $cats as $index => $Cat ) {
			$this->AssertTrue( is_a($Cat, 'ProductCategory'));
			$this->AssertEquals( $Cat->slug, $index );
		}

		$cats = shopp_product_categories(array('index'=>'name'));
		foreach ( $cats as $index => $Cat ) {
			$this->AssertTrue( is_a($Cat, 'ProductCategory'));
			$this->AssertEquals( $Cat->name, $index );
		}

		$cats = shopp_product_categories(array('load'=>array(), 'hide_empty'=>true));
		foreach ( $cats as $index => $Cat ) {
			$this->AssertTrue( is_a($Cat, 'ProductCategory'));
			if ( $Cat->count ) $this->AssertTrue( ! empty($Cat->products) );
			else $this->AssertTrue( empty($Cat->products) );
		}

	}

	function test_shopp_product_tag() {
		$Tag = shopp_product_tag('action');
		$this->AssertEquals($Tag->name, 'action');
		$this->AssertEquals($Tag->slug, 'action');
		$this->AssertTrue( ! empty($Tag->products) );

		$id = $Tag->id;
		$Tag = shopp_product_tag($id);

		$this->AssertEquals($Tag->name, 'action');
		$this->AssertEquals($Tag->slug, 'action');
		$this->AssertTrue( ! empty($Tag->products) );
	}

	function test_shopp_add_product_tag() {
		$tagid = shopp_add_product_tag('unit test');
		$Tag = shopp_product_tag('unit test');
		$this->AssertEquals($Tag->name, 'unit test');
		$this->AssertEquals($Tag->slug, 'unit-test');
	}

	function test_shopp_rmv_product_tag () {
		$this->AssertTrue(shopp_rmv_product_tag('unit test'));
		$Tag = shopp_product_tag('unit test');
		$this->AssertFalse($Tag);
	}

	function test_shopp_product_term () {
		$Tag = shopp_product_tag('action');
		$Term = shopp_product_term($Tag->id, ProductTag::$taxon);
		$this->AssertEquals($Term->name, 'action');
		$this->AssertEquals($Term->slug, 'action');
		$this->AssertTrue( ! empty($Term->products) );

		shopp_register_taxonomy('product_term_test');

		$Product = shopp_add_product(array('name'=>'shopp_product_term_test', 'publish'=>array('flag'=>true)));
		$term = shopp_add_product_term('shopp_product_term_test1', 'shopp_product_term_test');
		shopp_product_add_terms ( $Product->id, $terms = array($term), 'shopp_product_term_test' );

		$Term = shopp_product_term($term, 'shopp_product_term_test');
		$this->AssertTrue(is_a($Term, 'ProductTaxonomy'));
		$this->AssertEquals('shopp_product_term_test', $Term->taxonomy);
		$this->AssertEquals(1, count($Term->products));
		$this->AssertEquals('shopp_product_term_test', reset($Term->products)->name);
	}

	function test_shopp_term_products() {
		$Products = shopp_term_products('shopp_product_term_test1', 'shopp_product_term_test');
		$this->AssertEquals(1, count($Products));
		$this->AssertEquals('shopp_product_term_test', reset($Products)->name);
	}

	function test_shopp_rmv_product_term () {
		$Term = term_exists('shopp_product_term_test1', 'shopp_product_term_test');
		$this->AssertTrue(shopp_rmv_product_term($Term['term_id'], 'shopp_product_term_test'));
	}

	function test_shopp_product_tags() {
		$tags = shopp_product_tags();
		foreach ( $tags as $index => $ProductTag ) {
			$this->AssertTrue( is_a($ProductTag, 'ProductTag'));
			$this->AssertEquals( $ProductTag->id, $index );
		}

		$tags = shopp_product_tags(array('index'=>'slug'));
		foreach ( $tags as $index => $ProductTag ) {
			$this->AssertTrue( is_a($ProductTag, 'ProductTag'));
			$this->AssertEquals( $ProductTag->slug, $index );
		}

		$tags = shopp_product_tags(array('index'=>'name'));
		foreach ( $tags as $index => $ProductTag ) {
			$this->AssertTrue( is_a($ProductTag, 'ProductTag'));
			$this->AssertEquals( $ProductTag->name, $index );
		}

		$tags = shopp_product_tags(array('load'=>array(), 'hide_empty'=>true));
		foreach ( $tags as $index => $ProductTag ) {
			$this->AssertTrue( is_a($ProductTag, 'ProductTag'));
			$this->AssertTrue( ! empty($ProductTag->products) );
		}

	}

	function test_shopp_category_products () {
		global $wpdb;
		$Cat = new ProductCategory("Men's", "name");
		$count = $Cat->count;
		$Products = shopp_category_products ( (int) $Cat->id );

		$this->AssertTrue(! empty($Products) );
	}

	function test_shopp_tag_products() {
		$Products = shopp_tag_products( 'wachowski' );
		$this->AssertTrue( ! empty( $Products ) );
	}

	function test_shopp_catalog_count () {
		$count = shopp_catalog_count();
		$this->AssertEquals(116, $count);
	}

	function test_shopp_category_count () {
		$counts = array(
			"apparel"=>15,
			"bathing-suites"=>0,
			"blue-ray"=>5,
			"books"=>29,
			"charities"=>2,
			"donations"=>3,
			"dresses"=>0,
			"entertainment"=>64,
			"for-her"=>5,
			"for-him"=>6,
			"foundations"=>1,
			"games"=>14,
			"underwear"=>6,
			"jeans"=>3,
			"jewelry"=>32,
			"mens"=>9,
			"movies-tv"=>21,
			"music"=>0,
			"pendants-necklaces"=>13,
			"rings"=>9,
			"suits"=>1,
			"systems"=>0,
			"t-shirts-2"=>0,
			"t-shirts"=>5,
			"video-games"=>14,
			"watches"=>11,
			"womens"=>6
		);
		foreach(shopp_product_categories() as $Category) {
			$count = shopp_category_count($Category->id);
			if ( in_array($Category->slug, array_keys($counts)) ) $this->AssertEquals($Category->slug.$counts[$Category->slug], $Category->slug.$count);
		}
	}

	function test_shopp_product_categories_count () {
		$Product = shopp_product('1/10 Carat Diamond Journey Heart Pendant in Yellow Gold', 'name');
		$count = shopp_product_categories_count($Product->id);
		$this->AssertEquals(2, $count);
	}

	function test_shopp_product_category () {
		$cat = shopp_product_category(3);
		$this->AssertEquals(15, count($cat->products));
		$this->AssertEquals('Apparel', $cat->name);
	}

	function test_shopp_subcategories () {
		$this->AssertEquals(15, count(shopp_category_products ( 3 )));
	}

	function test_shopp_subcategory_count () {
		$this->AssertEquals(9, shopp_subcategory_count(3));
	}

}
?>
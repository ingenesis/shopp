<?php
/**
* tests for api/collection.php
*/
class CollectionDevAPITests extends ShoppTestCase {

	static $category;

	static function setUpBeforeClass () {
		if ( ! ShoppStorefront() ) ShoppStorefront(new Storefront());

		$starfleet = shopp_add_product_tag('Starfleet');
		$federation = shopp_add_product_tag('Federation');
		self::$category = shopp_add_product_category('Heavy Cruiser');

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
			'tags' => array('terms' => array('Starfleet', 'Federation')),
			'categories'=> array('terms' => array(self::$category)),
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
				)
		);

		$Product = shopp_add_product($args);

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
		$Tag = shopp_product_tag('Starfleet');
		$this->AssertEquals('Starfleet', $Tag->name);
		$this->AssertEquals('starfleet', $Tag->slug);
		$this->AssertTrue( ! empty($Tag->products) );

		$id = $Tag->id;
		$Tag = shopp_product_tag($id);

		$this->AssertEquals('Starfleet', $Tag->name);
		$this->AssertEquals('starfleet', $Tag->slug);
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
		$Tag = shopp_product_tag('Starfleet');
		$Term = shopp_product_term($Tag->id, ProductTag::$taxon);
		$this->AssertEquals('Starfleet', $Tag->name);
		$this->AssertEquals('starfleet', $Tag->slug);
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
		shopp_register_taxonomy('product_term_test');

		$Product = shopp_add_product(array('name'=>'shopp_product_term_test', 'publish'=>array('flag'=>true)));
		$term = shopp_add_product_term('shopp_product_term_test1', 'shopp_product_term_test');
		shopp_product_add_terms ( $Product->id, $terms = array($term), 'shopp_product_term_test' );

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
		$Cat = new ProductCategory('heavy-cruiser', 'slug');
		$Products = shopp_category_products ( (int) $Cat->id );
		$this->AssertTrue(! empty($Products) );
	}

	function test_shopp_tag_products() {
		$Products = shopp_tag_products( 'Federation' );
		$this->AssertTrue( ! empty( $Products ) );
	}

	function test_shopp_catalog_count () {
		$count = shopp_catalog_count();
		$this->AssertEquals(3, $count);
	}

	function test_shopp_category_count () {
		$count = shopp_category_count(self::$category);
		$this->AssertEquals(1, $count);
	}

	function test_shopp_product_categories_count () {
		$Product = shopp_product('uss-enterprise', 'slug');
		$count = shopp_product_categories_count($Product->id);
		$this->AssertEquals(1, $count);
	}

	function test_shopp_product_category () {
		$cat = shopp_product_category(self::$category);
		$this->AssertEquals(1, count($cat->products));
		$this->AssertEquals('Heavy Cruiser', $cat->name);
	}

	function test_shopp_subcategories () {
		$this->AssertEquals(1, count(shopp_category_products ( self::$category )));
	}

	function test_shopp_subcategory_count () {
		$this->AssertEquals(0, shopp_subcategory_count(self::$category));
	}

}
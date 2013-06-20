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

	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		parent::setUp();
		global $Shopp;

		if (!$Shopp->Catalog) $Shopp->Catalog = new Catalog();

		shopp('catalog','category','id=3&load=true');
	}

	function test_category_url () {
		ob_start();
		shopp('category','url');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('http://shopptest/store/category/apparel/',$actual);
	}

	function test_category_id () {
		ob_start();
		shopp('category','id');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('3',$actual);
	}

	function test_category_name () {
		ob_start();
		shopp('category','name');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Apparel',$actual);
	}

	function test_category_slug () {
		ob_start();
		shopp('category','slug');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('apparel',$actual);
	}

	function test_category_description () {
		ob_start();
		shopp('category','description');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);

	}

	function test_category_products () {
		ob_start();
		if (shopp('category','has-products'))
			while(shopp('category','products')) shopp('product','id');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1293022456941366568',$output);
	}

	function test_category_total () {
		shopp('category','has-products');
		ob_start();
		shopp('category','total');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('15',$actual);
	}

	function test_category_row () {
		shopp('category','has-products');
		for ($i = 0; $i < 3; $i++) {
			shopp('category','products');
			shopp('category','row');
		}
		$this->assertTrue(shopp('category','row'));
	}

	function test_category_categories_tags () {
		ob_start();
		if (shopp('category','has-categories'))
			while(shopp('category','subcategories')) shopp('subcategory','id');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('5151711719',$output);
	}

	function test_category_subcategorylist () {
		ob_start();
		shopp('category','subcategory-list');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_category_issubcategory () {
		$this->assertFalse(shopp('category','is-subcategory'));
		shopp('catalog','category','id=17&load=true');
		$this->assertTrue(shopp('category','is-subcategory'));
	}

	function test_category_sectionlist () {
		ob_start();
		shopp('category','section-list');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_category_pagination () {
		ob_start();
		shopp('category','pagination');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_category_facetedmenu () {
		ob_start();
		if (shopp('category','has-faceted-menu'))
			shopp('category','faceted-menu');
		$actual = ob_get_contents();
		ob_end_clean();
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
*/

	function test_category_image_tags () {
		ob_start();
		if (shopp('category','has-images'))
			while(shopp('category','images')) shopp('category','image');
		$output = ob_get_contents();
		ob_end_clean();
	}

} // end CategoryAPITests class

?>
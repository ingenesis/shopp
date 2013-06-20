<?php
/**
 * ShippingAPITests
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 20 October, 2009
 * @package
 **/

/**
 * Initialize
 **/

class ShippingAPITests extends ShoppTestCase {

	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
	}

	function test_shipping_hasestimates () {
		$Order =& ShoppOrder();
		$Order->Cart->clear();

		$Product = new Product(81); $Price = false;
		$Order->Cart->add(1,$Product,$Price,false);
		$Order->Cart->totals();
		$this->assertTrue(shopp('shipping','hasestimates'));

	}

	function test_shipping_methodname () {
		$Order =& ShoppOrder();
		$Order->Cart->clear();

		$Product = new Product(81); $Price = false;
		$Order->Cart->add(1,$Product,$Price,false);
		$Order->Cart->totals();

		ob_start();
		if (shopp('shipping','hasestimates'))
			while(shopp('shipping','methods'))
				shopp('shipping','method-name');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Standard',$actual);
	}

	function test_shipping_methodcost () {
		$Order =& ShoppOrder();
		$Order->Cart->clear();
		$Order->Shipping->country = 'US';

		$Product = new Product(81); $Price = false;
		$Order->Cart->add(1,$Product,$Price,false);
		$Order->Cart->totals();

		ob_start();
		if (shopp('shipping','hasestimates'))
			while(shopp('shipping','methods'))
				shopp('shipping','method-cost');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$3.00',$actual);
	}

	function test_shipping_methodselector () {
		$Order =& ShoppOrder();
		$Order->Cart->clear();

		$Product = new Product(81); $Price = false;
		$Order->Cart->add(1,$Product,$Price,false);
		$Order->Cart->totals();

		ob_start();
		if (shopp('shipping','hasestimates')) shopp('shipping','methods');
		shopp('shipping','method-selector');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'radio','name' => 'shipmethod','value' => 'OrderRates-0','class' => 'shopp shipmethod')
		);
		$this->assertTag($expected,$actual,$actual,true);
	}

	function test_shipping_methoddelivery () {
		$Order =& ShoppOrder();
		$Order->Cart->clear();

		$Product = new Product(81); $Price = false;
		$Order->Cart->add(1,$Product,$Price,false);
		$Order->Cart->totals();

		ob_start();
		if (shopp('shipping','hasestimates')) shopp('shipping','methods');
		shopp('shipping','method-delivery');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertTrue(!empty($actual),'Shipping method delivery timeframe should not be empty.');
	}


} // end ShippingAPITests class

?>
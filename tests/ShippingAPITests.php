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

class ShippingAPITests extends ShoppTestCase {

	function setUp () {
		parent::setUp();

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
				)
		);

		shopp_add_product($args);
	}

	function test_shipping_hasestimates () {

		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);
		ShoppOrder()->Cart->totals();

		$this->assertTrue(shopp('shipping','hasestimates'));

	}

	function test_shipping_methodname () {

		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);
		ShoppOrder()->Cart->totals();

		ob_start();
		if (shopp('shipping','hasestimates'))
			while(shopp('shipping','methods'))
				shopp('shipping','method-name');
		$actual = ob_get_clean();

		$this->assertEquals('StandardPriority',$actual);
	}

	function test_shipping_methodcost () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);
		ShoppOrder()->Cart->totals();

		ob_start();
		if ( shopp('shipping','hasestimates') )
			while( shopp('shipping','methods') )
				shopp('shipping','method-cost');
		$actual = ob_get_clean();

		$this->assertEquals('$9.87$21.09',$actual);
	}

	function test_shipping_methodselector () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);
		ShoppOrder()->Cart->totals();

		ob_start();
		if ( shopp('shipping','hasestimates') )
			shopp('shipping','methods');
		shopp('shipping','method-selector');
		$actual = ob_get_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'radio','name' => 'shipmethod','value' => 'OrderRates-0','class' => 'shopp shipmethod')
		);
		$this->assertTag($expected,$actual,$actual,true);

	}

	function test_shipping_methoddelivery () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);
		ShoppOrder()->Cart->totals();

		ob_start();
		if (shopp('shipping','hasestimates')) shopp('shipping','methods');
		shopp('shipping','method-delivery');
		$actual = ob_get_clean();

		$this->assertTrue( ! empty($actual), 'Shipping method delivery timeframe should not be empty.');
	}


} // end ShippingAPITests class
<?php
/**
 * TaxTests
 *
 * @version 1.0
 * @copyright Ingenesis Limited, May 2017
 * @package shopp
 **/

class TaxTests extends ShoppTestCase {

	static function setUpBeforeClass () {
		$Uniforms = shopp_add_product_category('Uniforms', 'For properly dressed Starfleet officers.');

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
		);

		shopp_add_product($args);

		$args = array(
			'name' => 'Galileo',
			'publish' => array('flag' => true),
			'single' => array(
				'type' => 'Shipped',
				'price' => 17019,
				'sale' => array(
					'flag' => true,
					'price' => 17.019
				),
				'taxed'=> true,
				'shipping' => array('flag' => true, 'fee' => 0.9, 'weight' => 2.8, 'length' => 6.1, 'width' => 1.9, 'height' => 1.5),
				'inventory' => array(
					'flag' => true,
					'stock' => 1,
					'sku' => 'NCC-1701/9'
				)
			),
			'specs' => array(
				'Class' => 'Class-F',
				'Category' => 'Shuttlecraft',
			)
		);

		shopp_add_product($args);

		$args = array(
			'name' => 'Command Uniform',
			'publish' => array('flag' => true),
			'categories'=> array('terms' => array($Uniforms)),
			'specs' => array(
				'Department' => 'Command',
				'Color' => 'Gold'
			),
			'variants' => array(
				'menu' => array(
					'Size' => array('Small','Medium','Large')
				),
				0 => array(
					'option' => array('Size' => 'Small'),
					'type' => 'Shipped',
					'price' => 100,
					//'sale' => array('flag' => false, 'price' => 50),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 5,
						'sku' => 'SFU-001-S'
					)
				),
				1 => array(
					'option' => array('Size' => 'Large'),
					'type' => 'Shipped',
					'price' => 200,
					//'sale' => array('flag' => false, 'price' => 100),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 1,
						'sku' => 'SFU-001-L'
					)
				),
			),
			'addons'=> array(
				'menu' => array('Pips' => array('Black Pip', 'Gold Pip')),
				0 => array(
					'option' => array('Pips' => 'Black Pip'),
					'type' => 'Shipped',
					'price' => 10.00,
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 1.0),
					'inventory' => array('flag' => false),
				),
				1 => array(
					'option' => array('Pips' => 'Gold Pip'),
					'type' => 'Shipped',
					'price' => 20.00,
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 1.0),
					'inventory' => array('flag' => false),
				)
			)
		);

		shopp_add_product($args);

	}

	static function tearDownAfterClass () {
		parent::tearDownAfterClass();
		self::resetTests();
	}

	static function resetTests () {
		ShoppOrder()->clear();

		ShoppOrder()->Billing = new BillingAddress;
		ShoppOrder()->Shipping = new ShippingAddress;

		$args = array(
			array(
				'rate' => '10%',
				'compound' => 'off',
				'country' => '*',
				'logic' => 'any',
				'haslocals' => false
			)
		);

		shopp_set_setting('taxes','on');
		shopp_set_setting('taxrates', serialize($args));

		shopp_set_setting('tax_shipping', 'off');
		shopp_set_setting('tax_inclusive', 'off');
		shopp_set_setting('base_operations', array());

	}

	function setUp () {
		parent::setUp();
		self::resetTests();

		remove_all_actions('shopp_calculate_shipping_init');
		remove_all_actions('shopp_calculate_item_shipping');
		remove_all_actions('shopp_calculate_shipping');


		$shippingrates = array(
			'label' => 'Standard',
			'mindelivery' => '4d',
			'maxdelivery' => '7d',
			'fallback' => 'off',
			'table' => array(
				0 => array(
					'destination' => '*',
					'rate' => 10.00
				),
			)
		);

		$Shopp = Shopp::object();

		shopp_set_setting('ItemRates-0', serialize($shippingrates));
		shopp_set_setting('active_shipping',array(
			'ItemRates' => array( 0 => true )
		));
		$Shopp->Shipping->active = array();
		$Shopp->Shipping->activated();
		$Shopp->Shipping->load();
	}

	private function number ($amount) {
		return Shopp::numeric_format(abs($amount), 2, '.', '', 3);
	}

	function test_eu_overlap_settings () {
		$Order = ShoppOrder();

		ShoppBaseLocale()->save('FR');
		shopp_set_setting('tax_inclusive', 'on');
		shopp_set_setting('tax_shipping', 'off');

		$taxrates = array(
			array(
				'rate' => '20%',
				'compound' => 'off',
				'country' => 'EUVAT',
				'logic' => 'any',
				'haslocals' => false
			),
			array(
				'rate' => '19%',
				'compound' => 'off',
				'country' => 'DE',
				'logic' => 'any',
				'haslocals' => false
			),
			array(
				'rate' => '10%',
				'compound' => 'off',
				'country' => 'FR',
				'logic' => 'any',
				'rules' => array(
					array(
						'p' => 'product-category',
						'v' => 'Uniforms'
					)
				),
				'haslocals' => false
			)
		);
		shopp_set_setting('taxrates', serialize($taxrates));

		$Product = shopp_product('command-uniform', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$Items = shopp_cart_items();
		$Item = reset($Items);

		$data = array('country' => 'FR');
		$Order->Billing->updates($data);
		$Order->Shipping->updates($data);
		$Order->locate();

		$Totals = $Order->Cart->totals();

		// Item Pricing
		$this->assertEquals('100.00', $this->number($Item->unitprice), 'Cart line item unit price:');
		$this->assertEquals('100.00', $this->number($Item->total), 'Cart line item total:');

		// Cart totals
		$this->assertEquals('100.00', $this->number($Totals->total('order')), 'Cart order amount:');
		$this->assertEquals('10.00', $this->number($Totals->total('shipping')), 'Cart shipping amount:');
		$this->assertEquals('9.09', $this->number($Totals->total('tax')), 'Cart tax amount:');
		$this->assertEquals('110.00', $this->number($Totals->total('total')), 'Cart total amount:');

	}

	// function test_salestax_baseline () {
	// 	$this->markTestSkipped('Not ready yet.');
	//
	// }
	//
	// function test_vat_baseline () {
	// 	$this->markTestSkipped('Not ready yet.');
	//
	// }
	//
	// function test_vat_conditional_tag () {
	// 	$this->markTestSkipped('Not ready yet.');
	//
	// }
	//
	// function test_vat_conditional_category () {
	// 	$this->markTestSkipped('Not ready yet.');
	//
	// }
	//
	// function test_vat_conditional_customer () {
	// 	$this->markTestSkipped('Not ready yet.');
	//
	// }
	//
	// function test_vat_conditional_product_name () {
	// 	$this->markTestSkipped('Not ready yet.');
	//
	// }

	/**
	 * Tests a complex VAT scenario
	 *
	 * With Standard Rate VAT, this scenario tests a product with an addon
	 * where the starting location is different than the base of operations
	 * but still a location where Standard Rate VAT applies. Then the location
	 * is changed to Germany where Germany's different Standard Rate VAT applies.
	 * Finally the location changes outside of EU VAT.
	 **/
	function test_vat_scenario1 () {
		//$this->markTestSkipped('Skipped');

		$Order = ShoppOrder();

		ShoppBaseLocale()->save('FR');

		shopp_set_setting('tax_inclusive', 'on');
		shopp_set_setting('tax_shipping', 'on');

		$taxrates = array(
			array(
				'rate' => '20%',
				'compound' => 'off',
				'country' => 'EUVAT',
				'logic' => 'any',
				'haslocals' => false
			),
			array(
				'rate' => '19%',
				'compound' => 'off',
				'country' => 'DE',
				'logic' => 'any',
				'haslocals' => false
			)
		);
		shopp_set_setting('taxrates', serialize($taxrates));

		$Product = shopp_product('command-uniform', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$Items = shopp_cart_items();
		$Item = reset($Items);
		$itemkey = key($Items); // Reliably obtain the itemkey

		$addons = shopp_product_addons($Product->id);
		$addon = array_shift($addons); // First available addon

		shopp_add_cart_item_addon($itemkey, $addon->id);

		$data = array('country' => 'GB');
		$Order->Billing->updates($data);
		$Order->Shipping->updates($data);
		$Order->locate();

		$Totals = $Order->Cart->totals();

		// Item Pricing
		$this->assertEquals('110.00', $this->number($Item->unitprice), 'Cart line item unit price:');
		$this->assertEquals('110.00', $this->number($Item->total), 'Cart line item total:');

		// Cart totals
		$this->assertEquals('110.00', $this->number($Totals->total('order')), 'Cart order amount:');
		$this->assertEquals('10.00', $this->number($Totals->total('shipping')), 'Cart shipping amount:');
		$this->assertEquals('20.00', $this->number($Totals->total('tax')), 'Cart tax amount:');
		$this->assertEquals('120.00', $this->number($Totals->total('total')), 'Cart total amount:');

		$data = array('country' => 'DE');

		$Order->Billing->updates($data);
		$Order->Shipping->updates($data);
		$Order->locate();

		$Totals = $Order->Cart->totals();
		
		// Item Pricing
		$this->assertEquals('109.08', $this->number($Item->unitprice), 'Cart line item unit price:');
		$this->assertEquals('109.08', $this->number($Item->total), 'Cart line item total:');

		// Cart totals
		$this->assertEquals('109.08', $this->number($Totals->total('order')), 'Cart order amount:');
		$this->assertEquals('10.00', $this->number($Totals->total('shipping')), 'Cart shipping amount:');
		$this->assertEquals('19.00', $this->number($Totals->total('tax')), 'Cart tax amount:');
		$this->assertEquals('119.08', $this->number($Totals->total('total')), 'Cart total amount:');

		$data = array('country' => 'FJ');

		$Order->Billing->updates($data);
		$Order->Shipping->updates($data);
		$Order->locate();

		$Totals = $Order->Cart->totals();

		$Items = shopp_cart_items();
		$Item = reset($Items);

		// Item Pricing
		$this->assertEquals('91.67', $this->number($Item->unitprice), 'Cart line item unit price:');
		$this->assertEquals('91.67', $this->number($Item->total), 'Cart line item total:');

		// Cart totals
		$this->assertEquals('91.67', $this->number($Totals->total('order')), 'Cart order amount:');
		$this->assertEquals('10.00', $this->number($Totals->total('shipping')), 'Cart shipping amount:');
		$this->assertEquals('0', $this->number($Totals->total('tax')), 'Cart tax amount:');
		$this->assertEquals('101.67', $this->number($Totals->total('total')), 'Cart total amount:');

	}

}
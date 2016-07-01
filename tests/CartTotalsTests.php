<?php
/**
 * CartTotalsTests
 *
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  2 February, 2011
 * @package shopp
 * @subpackage
 **/

/**
 * CartTotalsTests
 *
 * @author
 * @since 1.1
 * @package shopp
 **/
class CartTotalsTests extends ShoppTestCase {

	static function setUpBeforeClass () {

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
			'specs' => array(
				'Department' => 'Command',
				'Color' => 'Gold'
			),
			'variants' => array(
				'menu' => array(
					'Size' => array('Small','Medium','Large','Brikar')
				),
				0 => array(
					'option' => array('Size' => 'Small'),
					'type' => 'Shipped',
					'price' => 19.99,
					'sale' => array('flag'=>true, 'price' => 9.99),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 5,
						'sku' => 'SFU-001-S'
					)
				),
				1 => array(
					'option' => array('Size' => 'Medium'),
					'type' => 'Shipped',
					'price' => 22.55,
					'sale' => array('flag'=>true, 'price' => 19.99),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 15,
						'sku' => 'SFU-001-M'
					)
				),
				2 => array(
					'option' => array('Size' => 'Large'),
					'type' => 'Shipped',
					'price' => 32.95,
					'sale' => array('flag'=>true, 'price' => 24.95),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 1,
						'sku' => 'SFU-001-L'
					)
				),

			)
		);

		shopp_add_product($args);

		$promos = array(
			array(
				'name' => '2 PC Off',
				'status' => 'enabled',
				'starts' => 1,
				'ends' => 1,
				'type' => 'Percentage Off',
				'target' => 'Cart',
				'discount' => '2.0',
				'search' => 'any',
				'rules' => array(
					1 => array(
						'property' => 'Promo code',
						'logic' => 'Is equal to',
						'value' => '2percent'
				))
			),
			array(
				'name' => '5 PC Off Item',
				'status' => 'enabled',
				'starts' => 1,
				'ends' => 1,
				'type' => 'Percentage Off',
				'target' => 'Cart Item',
				'discount' => 5.0,
				'search' => 'all',
				'rules' => array(
					"item" => array( // item rules
						array(
							"property" => "Name",
							"logic" => "Is equal to",
							"value" => "USS Enterprise"
							)
					),
					1 => array(
						'property' => 'Promo code',
						'logic' => 'Is equal to',
						'value' => '5off'
				))
			),
			array(
				'name' => 'Free Shipping',
				'status' => 'enabled',
				'starts' => 1,
				'ends' => 1,
				'type' => 'Free Shipping',
				'target' => 'Cart',
				'discount' => '0',
				'search' => 'any',
				'rules' => array(
					1 => array(
						'property' => 'Promo code',
						'logic' => 'Is equal to',
						'value' => 'FreeShip'
				))
			),

			array(
				'name' => '$1 Off',
				'status' => 'enabled',
				'starts' => 1,
				'ends' => 1,
				'type' => 'Amount Off',
				'target' => 'Cart Item',
				'discount' => 1,
				'search' => 'all',
				'rules' => array(
					'item' => array( // item rules
						array(
							'property' => 'Name',
							'logic' => 'Is equal to',
							'value' => 'USS Enterprise'
							),
						array(
							'property' => 'Quantity',
							'logic' => 'Is greater than',
							'value' => '10'
						)
					),
					1 => array(
						'property'=>'Total quantity',
						'logic'=>'Is greater than',
						'value'=>'10'
					))
			),

			array(
				'name' => 'Buy 5 Get 1 Free!',
				'status' => 'enabled',
				'starts' => 1,
				'ends' => 1,
				'type' => 'Buy X Get Y Free',
				'target' => 'Cart Item',
				'buyqty' => 5,
				'getqty' => 1,
				'search' => 'all',
				'rules' => array(
					'item' => array( // item rules
						array(
							'property' => 'Name',
							'logic' => 'Is equal to',
							'value' => 'USS Enterprise'
							)
					),
					1 => array(
						'property' => 'Promo code',
						'logic' => 'Is equal to',
						'value' => 'buy5get1'
					))
			),

			array(
				'name' => '3 Dollars Off',
				'status' => 'enabled',
				'starts' => 1,
				'ends' => 1,
				'type' => 'Amount Off',
				'target' => 'Cart',
				'discount' => '3.0',
				'search' => 'any',
				'rules' => array(
					1 => array(
						'property' => 'Promo code',
						'logic' => 'Is equal to',
						'value' => '3DollarsOff'
					))
			)
		);
		foreach ($promos as $data) {
			$Promotion = new ShoppPromo();
			$Promotion->updates($data);
			$Promotion->save();
		}

		ShoppOrder()->Promotions->clear();
		ShoppOrder()->Promotions->load();
	}

	static function tearDownAfterClass () {
		self::resetTests();
	}

	static function resetTests () {
		ShoppOrder()->clear();

		ShoppBaseLocale()->save('US', 'OH');

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
		shopp_set_setting('taxrates',serialize($args));

		shopp_set_setting('tax_shipping', 'off');
		shopp_set_setting('tax_inclusive','off');
		shopp_set_setting('base_operations', array());

	}

	function setUp () {

		parent::setUp();
		self::resetTests();

	}

	function test_cart_base_case () {
		$Product = shopp_product('uss-enterprise','slug');
		$options = array('number' => true,'return' => true);

		shopp_set_setting('tax_shipping', 'off');
		shopp_add_cart_product($Product->id,1);

		$expected = '17.01';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '1.70';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '30.08';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	function test_cart_tax_shipping () {
		$Product = shopp_product('uss-enterprise','slug');
		$options = array('number' => true,'return' => true);

		shopp_set_setting('tax_shipping', 'on');
		shopp_add_cart_product($Product->id,1);

		$expected = '17.01';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '2.84';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '31.22';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	function test_cart_address_taxes () {
		$Order = ShoppOrder();
		$Product = shopp_product('uss-enterprise','slug');
		$options = array('number' => true,'return' => true);

		$data = array(
			'address' => '24-593 Federation Dr',
			'city' => 'San Francisco',
			'state' => 'CA',
			'country' => 'US',
			'postcode' => '94123',
			'residential' => true
		);

		$Order->Billing->updates($data);
		$Order->Shipping->updates($data);
		$Order->locate();

		$args = array(
			array(
				'rate' => '10%',
				'compound' => 'off',
				'country' => 'US',
				'zone' => 'CA',
				'logic' => 'any',
				'haslocals' => false
			)
		);
		shopp_set_setting('taxrates', serialize($args));
		$taxrates = shopp_setting('taxrates');
		shopp_add_cart_product($Product->id, 1);

		$expected = '1.70';
		$actual = shopp('cart.tax', $options);
		$this->assertEquals($expected, $actual, 'Cart tax assertion failed');

		$data = array(
			'city' => 'Columbus',
			'state' => 'OH',
			'country' => 'US',
			'postcode' => '43002',
			'residential' => true
		);

		$Order->Billing->updates($data);
		$Order->Shipping->updates($data);
		$Order->locate();
		$Order->Cart->totals();

		$expected = '0.00';
		$actual = shopp('cart.tax', $options);
		$this->assertEquals($expected, $actual, 'Cart no tax assertion failed');

	}

	function test_cart_order_percent_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('uss-enterprise','slug');

		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('2percent');

		$expected = '17.01';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.34';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '1.70';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '29.74';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand total assertion failed');

	}

	function test_cart_item_percent_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('uss-enterprise','slug');

		shopp_add_cart_product($Product->id, 1);
		shopp_add_cart_promocode('5off');

		$expected = '17.01';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.85';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '1.62';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '29.15';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand total assertion failed');


	}

	function test_cart_shipping_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('uss-enterprise','slug');

		shopp_add_cart_product($Product->id, 1);
		shopp_add_cart_promocode('FreeShip');

		$expected = '17.01';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.0';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '1.70';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '0.0';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '18.71';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}


	function test_cart_vat_base_case () {
		$Product = shopp_product('uss-enterprise','slug');
		$options = array('return' => true,'money'=>true,'wrap'=>false);

		ShoppBaseLocale()->save('GB');
		shopp_set_setting('tax_inclusive','on');

		shopp_add_cart_product($Product->id, 1);

		$expected = '£17.01';
		while( shopp('cart', 'items') ){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£1.55';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£28.38';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');


	}

	function test_cart_vat_taxed_shipping () {
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$Product = shopp_product('uss-enterprise','slug');

		ShoppBaseLocale()->save('GB');
		shopp_set_setting('tax_inclusive','on');
		shopp_set_setting('tax_shipping','on');

		shopp_add_cart_product($Product->id,1);

		$expected = '£17.01';
		while( shopp('cart', 'items') ){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$taxrates = shopp_setting('taxrates');
		$expected = '£2.58';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£28.38';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');


	}

	function test_cart_vat_item_percent_discount () {
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$Product = shopp_product('uss-enterprise','slug');

		ShoppBaseLocale()->save('GB');
		shopp_set_setting('tax_inclusive','on');

		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('5off');

		$expected = '£17.01';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.85';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£1.47';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£27.53';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	public function test_cartitem_amountoff_promocode_multi_qty () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('uss-enterprise','slug');

		shopp_add_cart_product($Product->id, 10);
		shopp_add_cart_promocode('5off');

		while( shopp('cart', 'items') ) {
			$expected = '17.01';
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$expected = '170.10';
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '170.10';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '8.51';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '16.16';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '24.87';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '202.62';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');
	}

	public function test_cartitem_amoutoff_multi_qty () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('uss-enterprise','slug');

		shopp_add_cart_product($Product->id, 15);

		shopp('cart', 'items');
		$expected = '17.01';
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$expected = '255.15';
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '255.15';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '15.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '24.02';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '32.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '296.54';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	public function test_cartitem_bogof_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('uss-enterprise','slug');

		shopp_add_cart_product($Product->id, 6);
		shopp_add_cart_promocode('buy5get1');

		shopp('cart', 'items');
		$expected = '17.01';
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$expected = '102.06';
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '102.06';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '17.01';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '8.51';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '18.87';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '112.43';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}


	function test_cart_vat_order_percent_discount () {
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$Product = shopp_product('uss-enterprise','slug');

		ShoppBaseLocale()->save('GB');
		shopp_set_setting('tax_inclusive','on');

		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('2percent');

		$expected = '£17.01';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.34';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£1.55';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£11.37';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£28.04';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	function test_cart_vat_shipping_discount () {
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$Product = shopp_product('uss-enterprise','slug');

		ShoppBaseLocale()->save('GB');
		shopp_set_setting('tax_inclusive','on');

		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('FreeShip');

		$expected = '£17.01';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£17.01';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£1.55';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£17.01';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

} // end CartTotalsTests class
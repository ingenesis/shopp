<?php

/**
* CartDevAPITests - cart dev api test suite
*/
class CartDevAPITests extends ShoppTestCase {

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
			),
			'addons' => array(
				'menu' => array(
					'Luxury Fittings' => array('Champagne Holder', 'Phaser Rack', 'Map Holder')
				),
				0 => array(
					'option' => array('Luxury Fittings' => 'Champagne Holder'),
					'type' => 'Shipped',
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 1.0),
					'inventory' => array('flag' => false),
					'price' => 10.00
				),
				1 => array(
					'option' => array('Luxury Fittings' => 'Phaser Rack'),
					'type' => 'Shipped',
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 5.5, 'length' => 0.35, 'width' => 0.5, 'height' => 1),
					'inventory' => array('flag' => false),
					'price' => 20.00
				),
				2 => array(
					'option' => array('Luxury Fittings' => 'Map Holder'),
					'type' => 'Shipped',
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.22, 'length' => 0.3, 'width' => 0.3, 'height' => 0.3),
					'inventory' => array('flag' => false),
					'price' => 40.00
				)
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
				)
			)
		);

		shopp_add_product($args);

	}

	static function resetTests () {
		ShoppOrder()->clear();
		shopp_set_setting('tax_shipping', 'off');
	}

	function setUp () {
		parent::setUp();
		self::resetTests();
	}

	function test_shopp_empty_cart () {
		shopp_empty_cart();
		$this->AssertTrue(ShoppOrder()->Cart->count() == 0);
	}

	function test_shopp_add_cart_product () {

		$Product = shopp_product('uss-enterprise', 'slug');
		shopp_add_cart_product($Product->id, 2);

		$Cart = ShoppOrder()->Cart->Totals;

		$items = shopp_cart_items();
		$item = reset($items);

		$this->AssertEquals(1, count($items));
		$this->AssertEquals('USS Enterprise', $item->name);
		$this->AssertEquals('uss-enterprise', $item->slug);
		$this->AssertEquals(2, $item->quantity);
		$this->AssertEquals(17.01, $item->unitprice);
		$this->AssertEquals(34.02, $item->totald);
		$this->AssertEquals(34.02, $item->total);

		$this->AssertEquals(2, $Cart->total('quantity'));
		$this->AssertEquals(34.02, $Cart->total('order'));
		$this->AssertEquals(9.87, $Cart->total('shipping'));
		$this->AssertEquals(3.4, $Cart->total('tax'));
		$this->AssertEquals(34.02+9.87+3.4, $Cart->total('total'));

		$Product = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$items = shopp_cart_items();
		reset($items);
		$item = next($items);

		$this->AssertEquals(2, count($items));
		$this->AssertEquals('Galileo', $item->name);
		$this->AssertEquals('galileo', $item->slug);
		$this->AssertEquals(1, $item->quantity);
		$this->AssertEquals(17.02, $item->unitprice);
		$this->AssertEquals(17.02, $item->totald);
		$this->AssertEquals(17.02, $item->total);

		$this->AssertEquals(34.02 + 17.02, $Cart->total('order'));
		$this->AssertEquals(66.01, $Cart->total('total'));
	}

	// this test will fail if the above shopp_add_cart_product test does not run
	function test_shopp_cart_item () {
		$Enterprise = shopp_product('uss-enterprise', 'slug');
		shopp_add_cart_product($Enterprise->id, 2);
		$Galileo = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Galileo->id, 1);

		$item = shopp_cart_item(0);
		$this->AssertEquals('USS Enterprise', $item->name);
		$this->AssertEquals('uss-enterprise', $item->slug);
		$this->AssertEquals(2, $item->quantity);
		$this->AssertEquals(17.01, $item->unitprice);
		$this->AssertEquals(34.02, $item->totald);
		$this->AssertEquals(34.02, $item->total);

		$item = shopp_cart_item('recent-cartitem');
		$this->AssertEquals('Galileo', $item->name);
		$this->AssertEquals('galileo', $item->slug);
		$this->AssertEquals(1, $item->quantity);
		$this->AssertEquals(17.02, $item->unitprice);
		$this->AssertEquals(17.02, $item->totald);
		$this->AssertEquals(17.02, $item->total);

	}

	// this test will fail if the above shopp_add_cart_product test does not run
	function test_shopp_rmv_cart_item () {
		$Enterprise = shopp_product('uss-enterprise', 'slug');
		shopp_add_cart_product($Enterprise->id, 2);
		$Galileo = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Galileo->id, 1);

		$removal = shopp_rmv_cart_item(1);

		$items = shopp_cart_items();
		$this->AssertEquals(1, count($items));

		$item = reset($items);

		$this->AssertEquals('USS Enterprise', $item->name);
		$this->AssertEquals('uss-enterprise', $item->slug);
		$this->AssertEquals(2, $item->quantity);
		$this->AssertEquals(17.01, $item->unitprice);
		$this->AssertEquals(34.02, $item->totald);
		$this->AssertEquals(34.02, $item->total);

		$Cart = ShoppOrder()->Cart->Totals;

		$this->AssertEquals(34.02, $Cart->total('order'));
		$this->AssertEquals(9.87, $Cart->total('shipping'));
		$this->AssertEquals(3.4, $Cart->total('tax'));
		$this->AssertEquals(34.02 + 9.87 + 3.4, $Cart->total());

		shopp_rmv_cart_item(0);

		$Totals = ShoppOrder()->Cart->Totals;

		$this->AssertEquals(0, $Totals->subtotal);
		$this->AssertEquals(false, $Totals->shipping);
		$this->AssertEquals(0, $Totals->total);

	}

	function test_shopp_add_cart_variant () {
		$Product = shopp_product('command-uniform', 'slug');

		shopp_add_cart_product($Product->id, 2);

		$variants = shopp_product_variants('command-uniform', 'slug');
		$Variant = $variants[1];
		shopp_add_cart_variant ( $Variant->id, 1 );

		$item = shopp_cart_item('recent-cartitem');

		$this->AssertEquals('Command Uniform', $item->name);
		$this->AssertEquals('Medium', $item->option->label);
		$this->AssertEquals(1, $item->quantity);
		$this->AssertEquals(19.99, $item->unitprice);
		$this->AssertEquals(19.99, $item->total);

		foreach( shopp_cart_items() as $i => $item ) {
			shopp_rmv_cart_item($i);
		}
	}

	function test_shopp_add_cart_addon() {
		$Product = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$Items = shopp_cart_items();
		$itemkey = key($Items); // Reliably obtain the itemkey

		$addons = shopp_product_addons($Product->id);
		$addon = array_shift($addons); // First available addon

		$this->assertTrue( shopp_add_cart_item_addon($itemkey, $addon->id) );

		$successfully_added = false;
		foreach ( shopp_cart_item_addons($itemkey) as $existing )
			if ( $existing->id == $addon->id ) $successfully_added = true;

		$this->assertTrue($successfully_added);
	}

	/**
	 * @depends test_shopp_add_cart_addon
	 */
	function test_shopp_rmv_cart_addon() {
		$Product = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$Items = shopp_cart_items();
		$itemkey = key($Items); // Reliably obtain the itemkey

		$addons = shopp_product_addons($Product->id);
		$addon = array_shift($addons); // First available addon

		shopp_add_cart_item_addon($itemkey, $addon->id);

		$this->assertTrue(shopp_rmv_cart_item_addon($itemkey, $addon->id));

		$successfully_removed = true;
		foreach ( shopp_cart_item_addons($itemkey) as $existing )
			if ( $existing->id == $addon->id ) $successfully_removed = false;

		$this->assertTrue($successfully_removed);
	}

	/**
	 * @depends test_shopp_add_cart_addon
	 */
	function test_shopp_cart_item_addons() {
		$Product = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$Items = shopp_cart_items();
		$itemkey = key($Items); // Reliably obtain the itemkey

		$addons = shopp_product_addons($Product->id);
		$addon_p = array_shift($addons); // 1st available addon
		$addon_q = array_shift($addons); // 2nd available addon

		shopp_add_cart_item_addon($itemkey, $addon_p->id);
		$added = shopp_cart_item_addons($itemkey);
		$this->assertTrue(is_array($added));
		$this->assertCount(1, $added);

		shopp_add_cart_item_addon($itemkey, $addon_q->id);
		$added = shopp_cart_item_addons($itemkey);
		$this->assertCount(2, $added);
	}

	/**
	 * @depends test_shopp_cart_item_addons
	 */
	function test_shopp_cart_item_addons_count() {
		$Product = shopp_product('galileo', 'slug');
		shopp_add_cart_product($Product->id, 1);

		$Items = shopp_cart_items();
		$itemkey = key($Items); // Reliably obtain the itemkey

		$addons = shopp_product_addons($Product->id);
		$addon_p = array_shift($addons); // 1st available addon
		$addon_q = array_shift($addons); // 2nd available addon

		shopp_add_cart_item_addon($itemkey, $addon_p->id);
		$count = shopp_cart_item_addons_count($itemkey);
		$this->assertEquals(1, $count);

		shopp_add_cart_item_addon($itemkey, $addon_q->id);
		$count = shopp_cart_item_addons_count($itemkey);
		$this->assertEquals(2, $count);
	}
}
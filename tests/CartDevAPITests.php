<?php

/**
* CartDevAPITests - cart dev api test suite
*/
class CartDevAPITests extends ShoppTestCase {

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
			// 'single' => array(
			// 	'type' => 'Shipped',
			// 	'price' => 22.55,
			// 		        'sale' => array(
			// 		            'flag' => false,
			// 		            'price' => 0.00
			// 		        ),
			// 	'taxed'=> true,
			// 	'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
			// 	'inventory' => array(
			// 		'flag' => true,
			// 		'stock' => 32,
			// 		'sku' => 'SFU-001'
			// 	)
			// ),
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

		shopp_set_setting('tax_shipping', 'on');
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
		$this->AssertEquals(5.1, $Cart->total('tax'));
		$this->AssertEquals(48.99, $Cart->total('total'));

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
		$this->AssertEquals(3.40, $Cart->total('tax'));
		$this->AssertEquals(34.02 + 9.87 + 3.40, $Cart->total());

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

}
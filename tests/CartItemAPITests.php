<?php
/**
 * ProductAPITests
 *
 *
 * @author John Dillick
 * @version 1.0
 * @copyright Ingenesis Limited, 14 October, 2009
 * @package
 **/

class CartItemAPITests extends ShoppTestCase {

	static $image = false;

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

		$Product = shopp_add_product($args);
		$path = dirname(__FILE__) . '/data/';
		self::$image = shopp_add_image ( $Product->id, 'product', $path . '1.png' );
	}

	// Tax config for test storefront:
	// 10% worldwide
	// 5% Ohio, US

	// example for adding product to cart by the product id and price id
	function test_cartitem_addbypriceid () {
		$Product = shopp_product('command-uniform','slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_variant($prices[1]->id, 1);

		$this->assertTrue(shopp('cart','hasitems'));
		$this->assertEquals(1, shopp('cart.get-totalitems'));

	}

	// example for adding product to cart by the product id and the option number
	function test_cartitem_addbyoptionid () {
		$Product = shopp_product('command-uniform','slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_variant($prices[1]->optionkey,1,'optionkey');

		$this->assertTrue(shopp('cart','hasitems'));
		$this->assertEquals(1, shopp('cart.get-totalitems'));
	}

	function test_cartitem_name () {
		$Product = shopp_product('command-uniform','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);

		ob_start();
		while(shopp('cart', 'items')) shopp('cartitem','name');
		$actual = ob_get_clean();
		$expected = 'Command Uniform';
		$this->assertEquals($expected, $actual);
	}

	function test_cartitem_url () {
		$Product = shopp_product('command-uniform', 'slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);

		ob_start();
		while(shopp('cart', 'items')) shopp('cartitem','url');
		$actual = ob_get_clean();

		$expected = 'http://' . WP_TESTS_DOMAIN . '?shopp_product=' . $Product->slug;
		$this->assertEquals($expected, $actual);
	}

	function test_cartitem_sku () {
		$Product = shopp_product('command-uniform', 'slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_variant($prices[2]->id,1);

		ob_start();
		while(shopp('cart', 'items')) shopp('cartitem','sku');
		$actual = ob_get_clean();

		$expected = 'SFU-001-L';
		$this->assertEquals($expected, $actual);
	}

	function test_cartitem_unitprice () {
		$Product = shopp_product('command-uniform', 'slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);

		while( shopp('cart', 'items') ){
			$actual = shopp('cartitem.get-unitprice');

			$this->assertEquals('$9.99', $actual);

			$actual = shopp('cartitem.get-unitprice', 'currency=off&taxes=true');
			$this->assertEquals('10.989', $actual);
		}
	}

	function test_cartitem_tax () {
		$Product = shopp_product('command-uniform', 'slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		while(shopp('cart', 'items')){
			// ob_start();
			// shopp('cartitem','tax');
			// $actual = ob_get_contents();
			// ob_end_clean();
			//
			// $expected = "$0.00";
			// $this->assertEquals($expected, $actual);

			$actual = shopp('cartitem.get-tax','taxes=true');

			$expected = '$1.00';
			$this->assertEquals($expected, $actual);
		}
	}

	function test_cartitem_quantity () {
		$Product = shopp_product('command-uniform', 'slug');
		$Secondary = shopp_product('galileo', 'slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_product($Secondary->id,6);

		$this->assertEquals(2, ShoppOrder()->Cart->count());
		while(shopp('cart', 'items')){
			$item = ShoppOrder()->Cart->current();
			$actual = shopp('cartitem.get-quantity');

			if( $item->name == $Secondary->name ) $this->assertEquals('6', $actual);
		}

	}

	function test_cartitem_total () {
		$Product = shopp_product('command-uniform', 'slug');
		$prices = shopp_product_variants($Product->id);
		shopp_empty_cart();

		shopp_add_cart_variant($prices[2]->id, 3);

		while(shopp('cart', 'items')) {
			$actual = shopp('cartitem.get-total');
			$this->assertEquals('$74.85', $actual);

			$actual = shopp('cartitem.get-total', 'taxes=on&currency=off');
			$this->assertEquals('82.335', $actual);
		}
	}

	function test_cartitem_quantityinput () {
		$Product = shopp_product('command-uniform', 'slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$this->assertEquals(1, ShoppOrder()->Cart->count());

		while( shopp('cart', 'items') ) {
			$Item = ShoppOrder()->Cart->current();
			$actual = shopp('cartitem.get-quantity', 'input=menu');
ob_get_contents();

			ob_start();
?><select name="items[<?php echo $Item->fingerprint(); ?>][quantity]"><option selected="selected" value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option><option value="35">35</option><option value="40">40</option><option value="45">45</option><option value="50">50</option><option value="60">60</option><option value="70">70</option><option value="80">80</option><option value="90">90</option><option value="100">100</option></select><?php
			$expected = ob_get_clean();

			$this->assertEquals($expected,$actual);
			$this->assertValidMarkup($actual);

			$actual = shopp('cartitem.get-quantity', 'input=text&class=myClass');

			ob_start();
			?><input type="text" name="items[<?php echo $Item->fingerprint(); ?>][quantity]" id="items-<?php echo $Item->fingerprint(); ?>-quantity"  size="5" value="1" class="myClass"/><?php
			$expected = ob_get_clean();

			$this->assertEquals($expected,$actual);
			$this->assertValidMarkup($actual);
		}
	}

	function test_cartitem_remove () {
		$Product = shopp_product('command-uniform', 'slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);

		while( shopp('cart', 'items') ) {
			$Item = ShoppOrder()->Cart->current();
			$actual = shopp('cartitem.get-remove', 'input=button&label=My Label&class=myclass');

			ob_start();
			?><button type="submit" name="remove[<?php echo $Item->fingerprint(); ?>]" value="<?php echo $Item->fingerprint(); ?>" class="myclass" tabindex="">My Label</button><?php
			$expected = ob_get_clean();

			$this->assertEquals($expected,$actual);
			$this->assertValidMarkup($actual);

			$actual = shopp('cartitem.get-remove', 'label=My Label 2&class=myclass2');

			ob_start();
			?><a href="http://<?php echo WP_TESTS_DOMAIN ?>/?shopp_page=cart&amp;cart=update&amp;item=<?php echo $Item->fingerprint(); ?>&amp;quantity=0" class="myclass2">My Label 2</a><?php
			$expected = ob_get_contents();
			ob_end_clean();

			$this->assertEquals($expected,$actual);
			$this->assertValidMarkup($actual);
		}
	}

	function test_cartitem_coverimage () {
		// $this->markTestSkipped('Images not implemented in test suite products yet.');
		$Product = shopp_product('command-uniform', 'slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);

		shopp('cart', 'items');
		$actual = shopp('cartitem.get-coverimage','class=cart-thumb&width=200&height=220');
		$imageid = self::$image;

		$expected = array(
			'tag' => 'img',
			'attributes' => array('src' => 'http://' . WP_TESTS_DOMAIN . '?siid=' . $imageid . '&200,220,'. self::imgrequesthash($imageid,array(200,220)), 'alt' => 'original', 'width' => '200', 'height' => '203', 'class' => 'cart-thumb')
		);
		$this->assertTag($expected,$actual,$actual,true);

		$this->assertValidMarkup($actual);

	}

	function test_cartitem_options (){
		$Product = shopp_product('command-uniform', 'slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		while( shopp('cart', 'items') ){
			$Item = ShoppOrder()->Cart->current();
			$id = $Item->fingerprint();
			$actual = shopp('cartitem.get-options','before=<div>&after=</div>');

			ob_start();
			?><div><input type="hidden" name="items[<?php echo $id ?>][product]" value="<?php echo $Product->id; ?>"/> <select name="items[<?php echo $id ?>][price]" id="items-<?php echo $id ?>-price"><option value="<?php echo $prices[0]->id; ?>" selected="selected"><?php echo $prices[0]->label; ?></option><option value="<?php echo $prices[1]->id; ?>"><?php echo $prices[1]->label; ?>  (+$10.00)</option><option value="<?php echo $prices[2]->id; ?>"><?php echo $prices[2]->label; ?>  (+$14.96)</option><option value="<?php echo $prices[3]->id; ?>"><?php echo $prices[3]->label; ?>  (-$9.99)</option></select></div><?php
			$expected = ob_get_contents();
			ob_end_clean();

			$this->assertEquals($expected, $actual);
			$this->assertValidMarkup($actual);
		}
	}

	function test_cartitem_inputs (){
		$Product = shopp_product('command-uniform', 'slug');
		shopp_empty_cart();

		$data = array('customField1' => "Custom Value1\nWith Newline", 'customField2' => 'Custom Value2');
		shopp_add_cart_product($Product->id,1,false,$data);

		while( shopp('cart', 'items') ){
			$this->assertTrue(shopp('cartitem', 'hasinputs'));
			while( shopp('cartitem', 'inputs') ) {

				$name = shopp('cartitem.get-input', 'name');

				$actual = shopp('cartitem.get-input');

				$this->assertTrue(isset($name));
				$expected = '<p>' . str_replace("\n", "<br />\n", $data[ $name ]) . '</p>' . "\n";

				$this->assertEquals($expected, $actual);
			}
		}
	}

	function test_cartitem_inputslist (){
		$Product = shopp_product('command-uniform', 'slug');
		shopp_empty_cart();

		$data = array('customField1' => "Custom Value1\nWith Newline", 'customField2' => 'Custom Value2', 'merryxmas'=>'Merry Christmas');
		shopp_add_cart_product($Product->id,1,false,$data);

		while( shopp('cart', 'items') ){
			$this->assertTrue(shopp('cartitem', 'hasinputs'));

			while( shopp('cartitem', 'inputs') ) {
				$this->assertTrue(shopp('cartitem', 'hasinputs'));

				$actual = shopp('cartitem.get-inputslist');
				$this->assertTrue( ! empty($actual) );

				ob_start();
				?><ul><li><strong>customField1</strong>: <p>Custom Value1<br />
With Newline</p>
</li><li><strong>customField2</strong>: <p>Custom Value2</p>
</li><li><strong>merryxmas</strong>: <p>Merry Christmas</p>
</li></ul><?php
				$expected = ob_get_clean();

				$this->assertEquals($expected, $actual, $actual);
				$this->assertValidMarkup($actual);

				$this->assertTrue(shopp('cartitem', 'hasinputs'));

				$actual = shopp('cartitem.get-inputslist','before=<div>&after=</div>&class=customdata&exclude=merryxmas');
				$this->assertTrue(!empty($actual));

				ob_start();
				?><div><ul class="customdata"><li><strong>customField1</strong>: <p>Custom Value1<br />
With Newline</p>
</li><li><strong>customField2</strong>: <p>Custom Value2</p>
</li></ul></div><?php
				$expected = ob_get_clean();

				$this->assertEquals($expected, $actual, $actual);
				$this->assertValidMarkup($actual);
			}
		}
	}

} // end CartItemAPITests class
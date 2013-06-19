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

	// Tax config for test storefront:
	// 10% worldwide
	// 5% Ohio, US

	// example for adding product to cart by the product id and price id
	function test_cartitem_addbypriceid () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_variant($prices[2]->id,1);

		$this->assertTrue(shopp('cart','hasitems'));
		$this->assertEquals(1,count(ShoppOrder()->Cart->contents));

	}

	// example for adding product to cart by the product id and the option number
	function test_cartitem_addbyoptionid () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_variant($prices[2]->optionkey,1,'optionkey');

		$this->assertTrue(shopp('cart','hasitems'));
		$this->assertEquals(1,count(ShoppOrder()->Cart->contents));
	}

	function test_cartitem_name () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		ob_start();
		while(shopp('cart', 'items')) shopp('cartitem','name');
		$actual = ob_get_contents();
		ob_end_clean();
		$expected = 'Code Is Poetry T-Shirt';
		$this->assertEquals($expected, $actual);
	}

	function test_cartitem_url () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		ob_start();
		while(shopp('cart', 'items')) shopp('cartitem','url');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = "http://shopptest/store/code-is-poetry-t-shirt/";
		$this->assertEquals($expected, $actual);
	}

	function test_cartitem_sku () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');
		$prices = shopp_product_variants($Product->id);

		shopp_empty_cart();
		shopp_add_cart_variant($prices[2]->id,1);

		ob_start();
		while(shopp('cart', 'items')) shopp('cartitem','sku');
		$actual = ob_get_contents();
		ob_end_clean();
		$expected = 'WPT-003';
		$this->assertEquals($expected, $actual);
	}

	function test_cartitem_unitprice () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		while(shopp('cart', 'items')){
			ob_start();
			shopp('cartitem','unitprice');
			$actual = ob_get_contents();
			ob_end_clean();

			$expected = '$9.01';
			$this->assertEquals($expected, $actual);

			ob_start();
			shopp('cartitem', 'unitprice', 'currency=off&taxes=true');
			$actual = ob_get_contents();
			ob_end_clean();
			$expected = '9.911';
			$this->assertEquals($expected, $actual);
		}
	}

	function test_cartitem_tax () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');

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

			$actual = "";
			ob_start();
			shopp('cartitem','tax','taxes=true');
			$actual = ob_get_contents();
			ob_end_clean();

			$expected = '$0.90';
			$this->assertEquals($expected, $actual);
		}
	}

	function test_cartitem_quantity () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');
		$Secondary = shopp_product('knowing','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_product($Secondary->id,6);

		$this->assertEquals(count(ShoppOrder()->Cart->contents),2);
		while(shopp('cart', 'items')){
			$item = current(ShoppOrder()->Cart->contents);
			ob_start();
			shopp('cartitem','quantity');
			$actual = ob_get_contents();
			ob_end_clean();

			if($item->name == $Secondary->name) $this->assertEquals('6', $actual);
		}

	}

	function test_cartitem_total () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');
		$prices = shopp_product_variants($Product->id);
		shopp_empty_cart();

		shopp_empty_cart();
		shopp_add_cart_variant($prices[2]->id,3);

		while(shopp('cart', 'items')){
			ob_start();
			shopp('cartitem','total');
			$actual = ob_get_contents();
			ob_end_clean();

			$expected = '$33.33';
			$this->assertEquals($expected, $actual);

			ob_start();
			shopp('cartitem','total', 'taxes=on&currency=off');
			$actual = ob_get_contents();
			ob_end_clean();

			$expected = "36.663";
			$this->assertEquals($expected, $actual);
		}
	}

	function test_cartitem_quantityinput () {
		$Product = shopp_product('code-is-poetry-t-shirt','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$this->assertEquals(count(ShoppOrder()->Cart->contents),1);

		while(shopp('cart', 'items')){
			ob_start();
			shopp('cartitem','quantity', 'input=menu');
			$actual = ob_get_contents();
			ob_end_clean();

			ob_start();
?><select name="items[0][quantity]"><option selected="selected" value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option><option value="35">35</option><option value="40">40</option><option value="45">45</option><option value="50">50</option><option value="60">60</option><option value="70">70</option><option value="80">80</option><option value="90">90</option><option value="100">100</option></select><?php
			$expected = ob_get_contents();
			ob_end_clean();

			$this->assertEquals($expected,$actual);
			$this->assertValidMarkup($actual);

			ob_start();
			shopp('cartitem','quantity', 'input=text&class=myClass');
			$actual = ob_get_contents();
			ob_end_clean();

			ob_start();
			?><input type="text" name="items[0][quantity]" id="items-0-quantity"  size="5" value="1" class="myClass"/><?php
			$expected = ob_get_contents();
			ob_end_clean();
			$this->assertEquals($expected,$actual);
			$this->assertValidMarkup($actual);
		}
	}

		function test_cartitem_remove () {
			$Product = shopp_product('code-is-poetry-t-shirt','slug');
			shopp_empty_cart();
			shopp_add_cart_product($Product->id,1);

			while(shopp('cart', 'items')){
				ob_start();
				shopp('cartitem','remove', 'input=button&label=My Label&class=myclass');
				$actual = ob_get_contents();
				ob_end_clean();

				ob_start();
				?><button type="submit" name="remove[0]" value="0" class="myclass" tabindex="">My Label</button><?php
				$expected = ob_get_contents();
				ob_end_clean();

				$this->assertEquals($expected,$actual);
				$this->assertValidMarkup($actual);

				ob_start();
				shopp('cartitem','remove', 'label=My Label 2&class=myclass2');
				$actual = ob_get_contents();
				ob_end_clean();

				ob_start();
				?><a href="http://shopptest/store/cart/?cart=update&amp;item=0&amp;quantity=0" class="myclass2">My Label 2</a><?php
				$expected = ob_get_contents();
				ob_end_clean();

				$this->assertEquals($expected,$actual);
				$this->assertValidMarkup($actual);
			}
		}

		function test_cartitem_coverimage () {
			$Product = shopp_product('code-is-poetry-t-shirt','slug');
			$Secondary = shopp_product('knowing','slug');

			shopp_empty_cart();
			shopp_add_cart_product($Product->id,1);

			while(shopp('cart', 'items')){
				ob_start();
				shopp('cartitem','coverimage','class=cart-thumb&width=200&height=220');
				$actual = ob_get_contents();
				ob_end_clean();

				ob_start();
				?><img src="http://shopptest/store/images/689/?200,220,141353768" alt="Code Is Poetry T-Shirt" width="200" height="200" class="cart-thumb" /><?php
				$expected = ob_get_contents();
				ob_end_clean();

				$this->assertEquals($expected, $actual);
				$this->assertValidMarkup($actual);
			}

			shopp_empty_cart();
			shopp_add_cart_product($Secondary->id,1);

			while(shopp('cart', 'items')){
				ob_start();
				shopp('cartitem','coverimage');
				$actual = ob_get_contents();
				ob_end_clean();

				ob_start();
				?><img src="http://shopptest/store/images/645/?48,48,3449720891" alt="Knowing" width="48" height="48" /><?php
				$expected = ob_get_contents();
				ob_end_clean();

				$this->assertEquals($expected, $actual);
				$this->assertValidMarkup($actual);
			}

		}

		function test_cartitem_options (){
			$Product = shopp_product('code-is-poetry-t-shirt','slug');
			$prices = shopp_product_variants($Product->id);

			shopp_empty_cart();
			shopp_add_cart_product($Product->id,1);

			while(shopp('cart', 'items')){
				ob_start();
				shopp('cartitem','options','before=<div>&after=</div>');
				$actual = ob_get_contents();
				ob_end_clean();

				ob_start();
				?><div><input type="hidden" name="items[0][product]" value="<?php echo $Product->id; ?>"/> <select name="items[0][price]" id="items-0-price"><option value="<?php echo $prices[0]->id; ?>" selected="selected"><?php echo $prices[0]->label; ?></option><option value="<?php echo $prices[1]->id; ?>"><?php echo $prices[1]->label; ?>  (+$0.88)</option><option value="<?php echo $prices[2]->id; ?>"><?php echo $prices[2]->label; ?>  (+$2.10)</option><option value="<?php echo $prices[3]->id; ?>"><?php echo $prices[3]->label; ?>  (+$4.21)</option><option value="<?php echo $prices[4]->id; ?>"><?php echo $prices[4]->label; ?>  (+$6.54)</option></select></div><?php
				$expected = ob_get_contents();
				ob_end_clean();

				$this->assertEquals($expected, $actual);
				$this->assertValidMarkup($actual);
			}
		}

		function test_cartitem_inputs (){
			$Product = shopp_product('code-is-poetry-t-shirt','slug');
			shopp_empty_cart();

			$data = array('customField1' => "Custom Value1\nWith Newline", 'customField2' => 'Custom Value2');
			shopp_add_cart_product($Product->id,1,false,$data);

			while(shopp('cart', 'items')){
				$this->assertTrue(shopp('cartitem', 'hasinputs'));
				while(shopp('cartitem', 'inputs')) {
					ob_start();
					shopp('cartitem', 'input', 'name');
					$name = ob_get_contents();
					ob_end_clean();

					ob_start();
					shopp('cartitem', 'input');
					$actual = ob_get_contents();
					ob_end_clean();

					$this->assertTrue(isset($name));
					$expected = $data[$name];

					$this->assertEquals($expected, $actual);
				}
			}
		}

		function test_cartitem_inputslist (){
			$Product = shopp_product('code-is-poetry-t-shirt','slug');
			shopp_empty_cart();

			$data = array('customField1' => "Custom Value1\nWith Newline", 'customField2' => 'Custom Value2', 'merryxmas'=>'Merry Christmas');
			shopp_add_cart_product($Product->id,1,false,$data);

			while(shopp('cart', 'items')){
				$this->assertTrue(shopp('cartitem', 'hasinputs'));
				while(shopp('cartitem', 'inputs')) {
					$this->assertTrue(shopp('cartitem', 'hasinputs'));
					ob_start();
					shopp('cartitem', 'inputslist');
					$actual = ob_get_contents();
					ob_end_clean();
					$this->assertTrue(!empty($actual));

					ob_start();
					?><ul><li><strong>customField1</strong>: Custom Value1
With Newline</li><li><strong>customField2</strong>: Custom Value2</li><li><strong>merryxmas</strong>: Merry Christmas</li></ul><?php
					$expected = ob_get_contents();
					ob_end_clean();

					$this->assertEquals($expected, $actual);
					$this->assertValidMarkup($actual);

					$this->assertTrue(shopp('cartitem', 'hasinputs'));
					ob_start();
					shopp('cartitem', 'inputslist','before=<div>&after=</div>&class=customdata&exclude=merryxmas');
					$actual = ob_get_contents();
					ob_end_clean();
					$this->assertTrue(!empty($actual));

					ob_start();
					?><div><ul class="customdata"><li><strong>customField1</strong>: Custom Value1
With Newline</li><li><strong>customField2</strong>: Custom Value2</li></ul></div><?php
					$expected = ob_get_contents();
					ob_end_clean();

					$this->assertEquals($expected, $actual);
					$this->assertValidMarkup($actual);
				}
			}
		}

} // end CartItemAPITests class

?>
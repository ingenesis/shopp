<?php
/**
 * CartAPITests
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 19 October, 2009
 * @package
 **/

/**
 * Initialize
 **/

class CartAPITests extends ShoppTestCase {

	function setUp () {
		parent::setUp();

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
			$Promotion = new Promotion();
			$Promotion->updates($data);
			$Promotion->save();
		}


	}

	function test_cart_url () {
		$actual = shopp('cart','url','return=1');
		$expected = 'http://'.WP_TESTS_DOMAIN.'/?shopp_page=cart'; // Ugly permalinks straight out of the box
		$this->assertEquals($expected, $actual);
	}

	function test_cart_hasitems () {
		shopp_empty_cart();
		$this->assertFalse(shopp('cart','hasitems'));

		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id, 1);

		$this->assertTrue(shopp('cart','hasitems'));
	}

	function test_cart_totalitems () {
		$FirstProduct = shopp_product('uss-enterprise','slug');
		$SecondProduct = shopp_product('galileo','slug');

		shopp_empty_cart();

		$actual = shopp('cart.get-totalitems');
		$this->assertEquals(0, $actual);

		shopp_add_cart_product($FirstProduct->id, 1);

		$actual = shopp('cart.get-totalitems');
		$this->assertEquals(1, $actual);

		shopp_add_cart_product($SecondProduct->id, 1);

		$actual = shopp('cart.get-totalitems');
		$this->assertEquals(2, $actual);

		shopp_add_cart_product($FirstProduct->id, 3);

		$actual = shopp('cart.get-totalitems');
		$this->assertEquals(2, $actual);
	}

	function test_cart_totalquantity () {
		$FirstProduct = shopp_product('uss-enterprise','slug');
		$SecondProduct = shopp_product('galileo','slug');

		shopp_empty_cart();

		$actual = shopp('cart.get-totalquantity');
		$this->assertEquals(0, $actual);

		shopp_add_cart_product($FirstProduct->id, 1);

		$actual = shopp('cart.get-totalquantity');
		$this->assertEquals(1, $actual);

		shopp_add_cart_product($SecondProduct->id, 1);

		$actual = shopp('cart.get-totalquantity');
		$this->assertEquals(2, $actual);

		shopp_add_cart_product($FirstProduct->id, 3);

		$actual = shopp('cart.get-totalquantity');
		$this->assertEquals(5, $actual);
	}

	function test_cart_itemlooping () {
		$FirstProduct = shopp_product('uss-enterprise','slug');
		$SecondProduct = shopp_product('galileo','slug');
		shopp_empty_cart();

		shopp_add_cart_product($FirstProduct->id, 1);
		shopp_add_cart_product($SecondProduct->id, 1);

		ob_start();
		if (shopp('cart','hasitems'))
			while(shopp('cart','items'))
				shopp('cartitem','name');
		$actual = ob_get_clean();

		$this->assertEquals('USS EnterpriseGalileo',$actual);
	}

	function test_cart_lastitem () {
		$FirstProduct = shopp_product('uss-enterprise','slug');
		$SecondProduct = shopp_product('galileo','slug');
		shopp_empty_cart();

		shopp_add_cart_product($FirstProduct->id, 1);

		$Item = shopp('cart','lastitem', 'return=true');
		$this->assertEquals($FirstProduct->name, $Item->name);

		shopp_add_cart_product($SecondProduct->id, 1);

		$Item = shopp('cart', 'lastitem', 'return=true');
		$this->assertEquals($SecondProduct->name, $Item->name);

		shopp_add_cart_product($FirstProduct->id, 1);
		$Item = shopp('cart', 'lastitem', 'return=true');
		$this->assertEquals($FirstProduct->name, $Item->name);
	}

	function test_cart_haspromos () {
		$Cart = ShoppOrder()->Cart;
		$Discounts = ShoppOrder()->Discounts;
		shopp_empty_cart();

		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id, 1);

		$this->assertFalse(shopp('cart','haspromos'));

		$_REQUEST['promocode'] = '2percent';

		$Discounts->request();
		$Cart->totals();
		unset($_REQUEST['promocode']);

		$this->assertTrue(shopp('cart','haspromos'));
	}

	function test_cart_totalpromos () {

		shopp_set_setting('promo_limit',0);
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();

		shopp_add_cart_product($Product->id, 1);
		ShoppOrder()->Discounts->clear();

		$actual = shopp('cart.get-totalpromos');
		$this->assertEquals(0, $actual);

		shopp_add_cart_promocode('2percent');

		$actual = shopp('cart.get-totalpromos');
		$this->assertEquals(1, $actual);
	}

	function test_cart_promo_name () {
		shopp_set_setting('promo_limit',0);
		shopp_empty_cart();
		ShoppOrder()->Discounts->clear();

		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('3DollarsOff');

		ob_start();
		if ( shopp('cart','haspromos') )
			while( shopp('cart','promos') )
				shopp('cart','promo-name');
		$actual = ob_get_clean();

		$this->assertEquals('3 Dollars Off2 PC Off', $actual);

		ob_start();
		if (shopp('cart','haspromos'))
			while(shopp('cart','promos'))
				shopp('cart','promo-discount');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('$3.00 Off!2% Off!',$actual);
	}


	function test_cart_function_tag () {

		ob_start();
		shopp('cart','function');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'hidden','name' => 'cart','value' => 'true')
		);
		$this->assertTag($expected,$actual,'',true);

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'update','id'=>'hidden-update')
		);
		$this->assertTag($expected,$actual,'',true);

		$this->assertValidMarkup($actual);
	}

	function test_cart_emptybutton () {
		ob_start();
		shopp('cart','empty-button');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'empty','id' => 'empty-button')
		);
		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_cart_updatebutton () {
		ob_start();
		shopp('cart','update-button');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'update','class' => 'update-button')
		);
		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_cart_sidecart () {

		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		$actual = shopp('cart.get-sidecart');

		$expected = array(
			'tag' => 'div',
			'attributes' => array('id' => 'shopp-cart-ajax')
		);
		$this->assertTag($expected,$actual,'',true);
		$this->assertTrue(!empty($actual));
	}

	function test_cart_hasdiscount () {
		shopp_empty_cart();
		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		shopp_add_cart_promocode('2percent');

		$this->assertTrue(shopp('cart','hasdiscount'));
	}

	function test_cart_discount () {
		shopp_set_setting('promo_limit', 0);
		shopp_empty_cart();
		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		shopp_add_cart_promocode('2percent');

		$actual = shopp('cart.get-discount');

		$expected = array(
			'tag' => 'span',
			'attributes' => array('class' => 'shopp-cart cart-discount'),
			'content' => '$0.34'
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);

		shopp_add_cart_promocode('3DollarsOff');

		$actual = shopp('cart.get-discount');

		$expected = array(
			'tag' => 'span',
			'attributes' => array('class' => 'shopp-cart cart-discount'),
			'content' => '$3.00'
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);

	}

	function test_cart_promosavailable () {
		shopp_set_setting('promo_limit', 0);
		shopp_empty_cart();
		ShoppOrder()->Discounts->clear();

		$Product = shopp_product('uss-enterprise','slug');

		$this->assertTrue(shopp('cart','promos-available'));
		shopp_set_setting('promo_limit', 1);
		$this->assertTrue(shopp('cart','promos-available'));

		shopp_add_cart_promocode('2percent');

		$this->assertFalse(shopp('cart','promos-available'));

	}

	function test_cart_promocode () {
		shopp_empty_cart();
		ShoppOrder()->Discounts->clear();

		$actual = shopp('cart.get-promo-code');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'promocode','id' => 'promocode')
		);

		$this->assertTag($expected,$actual,$actual,true);

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'update','id' => 'apply-code')
		);
		$this->assertTag($expected,$actual,$actual,true);

		$this->assertValidMarkup($actual);
	}

	function test_cart_hasshippingmethods () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);


		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id, 1);

		$this->assertTrue(shopp('cart','has-shipping-methods'));
	}

	function test_cart_needsshipped () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id, 1);

		$this->assertTrue(shopp('cart','needs-shipped'));
	}

	function test_cart_hasshipcosts () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$this->assertTrue(shopp('cart','has-ship-costs'));
	}

	function test_cart_needsshippingestimates () {
		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$this->assertTrue(shopp('cart','needs-shipping-estimates'));
	}

	function test_cart_shippingestimates () {

		$Product = shopp_product('uss-enterprise','slug');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		shopp_set_setting('target_markets', array('US' => 'USA','UK' => 'United Kingdom'));

		$actual = shopp('cart.get-shipping-estimates');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'shipping[country]','id' => 'shipping-country')
		);
		$this->assertTag($expected,$actual,$actual,true);

		shopp_set_setting('target_markets', array('US' => 'USA'));
		$actual = shopp('cart.get-shipping-estimates');
		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'hidden','name' => 'shipping[country]','id' => 'shipping-country')
		);
		$this->assertTag($expected,$actual,$actual,true);

		$actual = shopp('cart.get-shipping-estimates','postcode=on');
		$expected = array(
			'tag' => 'input',
			'attributes' => array('name' => 'shipping[postcode]','id' => 'shipping-postcode')
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);

	}

	function test_cart_subtotal () {
		shopp_empty_cart();
		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		$actual= shopp('cart.get-subtotal');

		$expected = array(
			'tag' => 'span',
			'attributes' => array('class' => 'shopp-cart cart-subtotal'),
			'content' => '$17.01'
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_cart_shipping () {
		shopp_empty_cart();
		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		$actual = shopp('cart.get-shipping');

		$expected = array(
			'tag' => 'span',
			'attributes' => array('class' => 'shopp-cart cart-shipping'),
			'content' => '$9.87'
		);
		$this->assertTag($expected,$actual,$actual,true);

		$this->assertValidMarkup($actual);
	}

	function test_cart_hastaxes () {
		shopp_empty_cart();
		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		$this->assertTrue(shopp('cart','has-taxes'));
	}

	function test_cart_tax () {
		shopp_empty_cart();
		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		$actual = shopp('cart.get-tax');

		$expected = array(
			'tag' => 'span',
			'attributes' => array('class' => 'shopp-cart cart-tax'),
			'content' => '$3.40'
		);

		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_cart_total () {
		shopp_empty_cart();

		$Product = shopp_product('uss-enterprise','slug');
		shopp_add_cart_product($Product->id,1);

		$actual = shopp('cart.get-total','number=1');
		$this->assertEquals('30.28',$actual);

		$actual = shopp('cart.get-total');

		$expected = array(
			'tag' => 'span',
			'attributes' => array('class' => 'shopp-cart cart-total'),
			'content' => '$30.28'
		);
		$this->assertTag($expected,$actual,$actual,true);

		$this->assertValidMarkup($actual);
	}

} // end CartAPITests class
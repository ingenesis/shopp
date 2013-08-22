<?php
/**
 * CheckoutAPITests
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 20 October, 2009
 * @package shopp
 **/
class CheckoutAPITests extends ShoppTestCase {

	static function setUpBeforeClass () {
		$Shopp = Shopp::object();
		$Shopp->Flow->handler('Storefront');

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

		$Product = shopp_add_product($args);
		shopp_add_cart_product($Product->id, 1);

	}

	public function test_checkout_url () {
		$actual = shopp('checkout.get-url');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/?shopp_page=checkout',$actual);
	}

	function test_checkout_function () {
		$actual = shopp('checkout.get-function');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'hidden','name' => 'checkout')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);

		$this->assertValidMarkup($actual);
	}

	function test_checkout_error () {
		$Errors = ShoppErrorStorefrontNotices::object();
		$Errors->clear();
		new ShoppError('Test Error');
		$actual = shopp('checkout.get-error');
		$this->assertEquals('<li>Test Error</li>', $actual);
	}

	function test_checkout_cartsummary () {

		$actual = shopp('checkout.get-cart-summary');
		$this->assertTrue( ! empty($actual) );
		$this->assertValidMarkup($actual);
	}

	function test_checkout_notloggedin () {
		shopp_set_setting('account_system', 'wordpress');
		ShoppOrder()->Customer = new Customer();
		$this->assertTrue(shopp('checkout','notloggedin'));

		$Login = new ShoppLogin();
		$Account = new Customer();
		$Login->login($Account);

		$this->assertFalse(shopp('checkout','notloggedin'));
	}

	function test_checkout_loggedin () {
		shopp_set_setting('account_system', 'wordpress');

		ShoppOrder()->Customer = new Customer();
		$this->assertFalse(shopp('checkout','loggedin'));

		$Login = new ShoppLogin();
		$Account = new Customer();
		$Login->login($Account);
		$this->assertTrue(shopp('checkout','loggedin'));
	}

	function test_checkout_accountlogin () {
		$actual = shopp('checkout.get-account-login');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'account-login','id' => 'account-login')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);

		$this->assertValidMarkup($actual);
	}

	function test_checkout_passwordlogin () {
		$actual = shopp('checkout.get-password-login');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'password','name' => 'password-login','id' => 'password-login')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);

		$this->assertValidMarkup($actual);
	}

	function test_checkout_loginbutton () {
		$actual = shopp('checkout.get-login-button');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'submit-login','id' => 'submit-login')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);

		$this->assertValidMarkup($actual);
	}

	function test_checkout_firstname () {
		$actual = shopp('checkout.get-firstname');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'firstname','id' => 'firstname')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_lastname () {
		$actual = shopp('checkout.get-lastname');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'lastname','id' => 'lastname')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_email () {
		$actual = shopp('checkout.get-email');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'email','id' => 'email')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_loginname () {
		$actual = shopp('checkout.get-loginname');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'loginname','id' => 'login')
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_password () {
		$actual = shopp('checkout.get-password');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'password','name' => 'password','id' => 'password')
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_confirmpassword () {
		$actual = shopp('checkout.get-confirm-password');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'password','name' => 'confirm-password','id' => 'confirm-password')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_phone () {
		$actual = shopp('checkout.get-phone');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'phone','id' => 'phone')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_company () {
		$actual = shopp('checkout.get-company');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'company','id' => 'company')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_customerinfo () {
		$actual = shopp('checkout.get-customer-info','type=text&name=Test');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'info[Test]','id' => 'customer-info-test')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	// function test_checkout_shipping () {
	// 	$shipping = shopp('checkout','shipping');
	// 	$this->assertTrue($shipping);
	// }

	function test_checkout_shipping_address () {
		$actual = shopp('checkout.get-shipping-address');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'shipping[address]','id' => 'shipping-address')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_shipping_xaddress () {
		$actual = shopp('checkout.get-shipping-xaddress');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'shipping[xaddress]','id' => 'shipping-xaddress')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_shipping_city () {
		$actual = shopp('checkout.get-shipping-city');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'shipping[city]','id' => 'shipping-city')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_shipping_state () {
		$actual = shopp('checkout.get-shipping-state');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'shipping[state]','id' => 'shipping-state-menu')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'shipping[state]','id' => 'shipping-state')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);

		$actual = shopp('checkout.get-shipping-state','type=text');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'shipping[state]','id' => 'shipping-state')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_shipping_postcode () {
		$actual = shopp('checkout.get-shipping-postcode');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'shipping[postcode]','id' => 'shipping-postcode')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_shipping_country () {
		$actual = shopp('checkout.get-shipping-country');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'shipping[country]','id' => 'shipping-country')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_samebillingaddress () {
		$actual = shopp('checkout.get-same-billing-address');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'checkbox','name' => 'sameaddress','id' => 'same-address-billing')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_sameshippingaddress () {
		$actual = shopp('checkout.get-same-shipping-address');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'checkbox','name' => 'sameaddress','id' => 'same-address-shipping')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_card_required () {
		$this->assertFalse(shopp('checkout','card-required'));
	}

	function test_checkout_billing_address () {
		$actual = shopp('checkout.get-billing-address');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[address]','id' => 'billing-address')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_xaddress () {
		$actual = shopp('checkout.get-billing-xaddress');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[xaddress]','id' => 'billing-xaddress')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_city () {
		$actual = shopp('checkout.get-billing-city');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[city]','id' => 'billing-city')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_state () {
		$actual = shopp('checkout.get-billing-state');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'billing[state]','id' => 'billing-state-menu')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[state]','id' => 'billing-state')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);

		$actual = shopp('checkout.get-billing-state','type=text');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[state]','id' => 'billing-state')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_postcode () {
		$actual = shopp('checkout.get-billing-postcode');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[postcode]','id' => 'billing-postcode')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_country () {
		$actual = shopp('checkout.get-billing-country');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'billing[country]','id' => 'billing-country')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_card () {
		$actual = shopp('checkout.get-billing-card');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[card]','id' => 'billing-card')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_cardtype () {
		$actual = shopp('checkout.get-billing-cardtype');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'billing[cardtype]','id' => 'billing-cardtype')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_cardexpires_mm () {
		$actual = shopp('checkout.get-billing-cardexpires-mm');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'billing[cardexpires-mm]','id' => 'billing-cardexpires-mm')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);

		$actual = shopp('checkout.get-billing-cardexpires-mm','type=text');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[cardexpires-mm]','id' => 'billing-cardexpires-mm')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);

	}

	function test_checkout_billing_cardexpires_yy () {
		$actual = shopp('checkout.get-billing-cardexpires-yy');

		$expected = array(
			'tag' => 'select',
			'attributes' => array('name' => 'billing[cardexpires-yy]','id' => 'billing-cardexpires-yy')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);

		$actual = shopp('checkout.get-billing-cardexpires-yy','type=text');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[cardexpires-yy]','id' => 'billing-cardexpires-yy')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_cardholder () {
		$actual = shopp('checkout.get-billing-cardholder');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[cardholder]','id' => 'billing-cardholder')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_billing_cvv () {
		$actual = shopp('checkout.get-billing-cvv');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'billing[cvv]','id' => 'billing-cvv')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_orderdata () {
		$actual = shopp('checkout.get-order-data','type=text&name=Test');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'data[Test]','id' => 'order-data-test')
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_submit () {
		$actual = shopp('checkout.get-submit');

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'process','id' => 'checkout-button')
		);
		$this->assertTag($expected,$actual,"++ $actual",true);
		$this->assertValidMarkup($actual);
	}

	function test_checkout_confirmbutton () {
		$actual = shopp('checkout.get-confirm-button');

		$expected = array(
			'tag' => 'a',
			'attributes' => array('href' => 'http://' . WP_TESTS_DOMAIN . '/?shopp_page=checkout'),
			'content' => 'Return to Checkout'
		);
		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);

	}

} // end CheckoutAPITests class
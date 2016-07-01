<?php
/**
 * CustomerAPITests$1
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  1 December, 2009
 * @package shopp
 * @subpackage tests
 **/
class CustomerAPITests extends ShoppTestCase {

	public static function setUpBeforeClass () {

		shopp_set_setting('account_system', 'wordpress');

		$Shopp = Shopp::object();
		$Shopp->Flow->controller('ShoppStorefront');

		// Create the WordPress account
		$wpuser = wp_insert_user(array(
			'user_login' => 'bones',
			'user_pass' => 'imadoctornotanengineer',
			'user_email' => 'mccoy@starfleet.gov',
			'display_name' => 'Dr. Leonard McCoy',
			'nickname' => 'Bones',
			'first_name' => 'Leonard',
			'last_name' => 'McCoy'
		));

		$customerid = shopp_add_customer(array(
			'wpuser' => $wpuser,
			'firstname' => 'Leonard',
			'lastname' => 'McCoy',
			'email' => 'mccoy@starfleet.gov',
			'phone' => '999-999-1701',
			'company' => 'Starfleet Command',
			'marketing' => 'no',
			'type' => 'Tax-Exempt',
			'saddress' => '24-593 Federation Dr',
			'sxaddress' => 'Shipping',
			'scity' => 'San Francisco',
			'sstate' => 'CA',
			'scountry' => 'US',
			'spostcode' => '94123',
			'baddress' => '24-593 Federation Dr',
			'bxaddress' => 'Billing',
			'bcity' => 'San Francisco',
			'bstate' => 'CA',
			'bcountry' => 'US',
			'bpostcode' => '94123',
			'residential' => true
		));

		shopp_add_product(array(
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
		));
	}

	static function tearDownAfterClass () {
		shopp_set_setting('account_system', 'none');
		$Customer = shopp_customer('mccoy@starfleet.gov','email');
		wp_delete_user( $Customer->wpuser );
	}

	function test_customer_accounturl () {
		$actual = shopp('customer.get-accounturl');
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/?shopp_page=account', $actual);
	}

	function test_customer_recoverurl () {
		ob_start();
		shopp('customer','recover-url');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/?shopp_page=account&recover',$actual);
	}

	function test_customer_process () {
		$this->assertEquals(null,shopp('customer','process'));
	}

	function test_customer_loggedin () {
		$this->assertTrue(shopp('customer','loggedin'));
	}

	function test_customer_notloggedin () {
		$this->assertFalse(shopp('customer','notloggedin'));
	}

	function test_customer_loginlabel () {
		ob_start();
		shopp('customer','login-label');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Login Name',$actual);
	}

	function test_customer_accountlogin () {
		ob_start();
		shopp('customer','account-login');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'account-login','id' => 'account-login')
		);
		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_passwordlogin () {
		ob_start();
		shopp('customer','password-login');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'password','name' => 'password-login','id' => 'password-login')
		);
		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_recoverbutton () {
		ob_start();
		shopp('customer','recover-button');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'recover-login','id' => 'recover-button')
		);
		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_loginbutton () {
		ob_start();
		shopp('customer','login-button');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_customer_accounts () {
		ob_start();
		shopp('customer','accounts');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('wordpress',$actual);
	}

	function test_customer_orderlookup () {
		ob_start();
		shopp('customer','order-lookup');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertValidMarkup($actual);
	}

	function test_customer_firstname () {
		ob_start();
		shopp('customer','firstname');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'firstname','id' => 'firstname')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_lastname () {
		ob_start();
		shopp('customer','lastname');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'lastname','id' => 'lastname')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_company () {
		ob_start();
		shopp('customer','company');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'company','id' => 'company')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_email () {
		ob_start();
		shopp('customer','email');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'email','id' => 'email')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_loginname () {
		ob_start();
		shopp('customer','loginname');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'loginname','id' => 'login','autocomplete'=>'off')
		);

		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_password () {
		ob_start();
		shopp('customer','password');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'password','name' => 'password','id' => 'password')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_confirmpassword () {
		ob_start();
		shopp('customer','confirm-password');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'password','name' => 'confirm-password','id' => 'confirm-password')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_phone () {
		ob_start();
		shopp('customer','phone');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'phone','id' => 'phone')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_info_tags () {
		$this->assertFalse(shopp('customer','hasinfo'));
		ob_start();
		shopp('customer','info','type=text&name=Test Field');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'text','name' => 'info[Test Field]','id' => 'customer-info-test-field')
		);

		$this->assertTag($expected,$actual,$actual,true);
		$this->assertValidMarkup($actual);
	}

	function test_customer_savebutton () {
		ob_start();
		shopp('customer','save-button');
		$actual = ob_get_contents();
		ob_end_clean();

		$expected = array(
			'tag' => 'input',
			'attributes' => array('type' => 'submit','name' => 'save','id' => 'save-button')
		);

		$this->assertTag($expected,$actual,'',true);
		$this->assertValidMarkup($actual);
	}

	// function test_customer_download_tags () {
	//
	// }

	function test_customer_purchase_tags () {
		$Customer = shopp_customer('mccoy@starfleet.gov', 'email');

		$Product = shopp_product('uss-enterprise', 'slug');
		shopp_add_cart_product ( $Product->id, 1 );
		$Purchase = shopp_add_order($Customer->id);

		ob_start();
		if (shopp('customer','has-purchases')) {
			while (shopp('customer','purchases'))
				shopp('customer','order');

		}
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('http://' . WP_TESTS_DOMAIN . '/?shopp_page=account&orders=1',$actual);
	}

} // end CustomerAPITests class
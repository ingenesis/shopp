<?php
/**
 * PurchaseAPITests
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  1 December, 2009
 * @package
 * @subpackage
 **/
class PurchaseAPITests extends ShoppTestCase {

	static $order = false;

	static function setUpBeforeClass () {

		shopp_set_setting('target_markets', array(
			'US' => 'USA'
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

		// Create the WordPress account
		$wpuser = wp_insert_user(array(
			'user_login' => 'spock',
			'user_pass' => 'livelongandprosper',
			'user_email' => 'spock@starfleet.gov',
			'display_name' => 'Commander Spock',
			'nickname' => 'Spock',
			'first_name' => "S'chn T'gai",
			'last_name' => 'Spock'
		));

		$customerid = shopp_add_customer(array(
			'wpuser' => $wpuser,
			'firstname' => "S'chn T'gai",
			'lastname' => 'Spock',
			'email' => 'spock@starfleet.gov',
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

		shopp_empty_cart();
		$Customer = shopp_customer('spock@starfleet.gov', 'email');

		// $Customer->Billing->cardtype = 'Visa';
		// $Customer->Billing->cardexpires = '';
		// $Customer->Billing->cardholder = 'Spock';

		$Product = shopp_product('uss-enterprise', 'slug');
		shopp_add_cart_product ( $Product->id, 1 );

		$Purchase = shopp_add_order($Customer);
		$Purchase = shopp_order($Purchase->id);
		$Purchase->card = '1111';
		$Purchase->cardexpires = mktime(0,0,0,12,0,2265);
		$Purchase->cardholder = $Customer->lastname;

		remove_action('shopp_authed_order_event',array(ShoppOrder(),'notify'));
		remove_action('shopp_authed_order_event',array(ShoppOrder(),'accounts'));
		remove_action('shopp_authed_order_event',array(ShoppOrder(),'success'));
		shopp_add_order_event($Purchase->id, 'authed', array(
			'txnid' => $Purchase->id.'TEST',	// Transaction ID
			'amount' => $Purchase->total,		// Gross amount authorized
			'gateway' => 'GatewayFramework',	// Gateway handler name (module name from @subpackage)
			'paymethod' => 'TestSuite',			// Payment method (payment method label from payment settings)
			'paytype' => 'visa',			// Type of payment (check, MasterCard, etc)
			'payid' => '1111'						// Payment ID (last 4 of card or check number)
		));

		shopp_add_order_event($Purchase->id, 'captured', array(
			'txnid' => $Purchase->id.'TEST',	// Transaction ID
			'amount' => $Purchase->total,		// Gross amount authorized
			'gateway' => 'GatewayFramework',	// Gateway handler name (module name from @subpackage)
			'fees' => 0.0,						// Transaction fees taken by the gateway net revenue = amount-fees

		));

		ShoppPurchase( $Purchase );
		self::$order = $Purchase;

	}

	function test_purchase_id () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-id');
		$this->assertEquals(self::$order->id, $actual);
	}

	function test_purchase_date () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-date');
		$this->assertEquals(date('F j, Y g:i a', self::$order->created), $actual);
	}

	function test_purchase_card () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-card');
		$this->assertEquals('XXXXXXXXXXXX1111', $actual);
	}

	function test_purchase_cardtype () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-cardtype');
		$this->assertEquals('api',$actual);
	}

	function test_purchase_transactionid () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-transactionid');
		$this->assertEquals(self::$order->id.'TEST', $actual);
	}

	function test_purchase_firstname () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-firstname');
		$this->assertEquals('S&#039;chn T&#039;gai',$actual);
	}

	function test_purchase_lastname () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-lastname');
		$this->assertEquals('Spock',$actual);
	}

	function test_purchase_company () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-company');
		$this->assertEquals('Starfleet Command',$actual);
	}

	function test_purchase_email () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-email');
		$this->assertEquals('spock@starfleet.gov',$actual);
	}

	function test_purchase_phone () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-phone');
		$this->assertEquals('999-999-1701',$actual);
	}

	function test_purchase_address () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-address');
		$this->assertEquals('24-593 Federation Dr',$actual);
	}

	function test_purchase_xaddress () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-xaddress');
		$this->assertEquals('Billing',$actual);
	}

	function test_purchase_city () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-city');
		$this->assertEquals('San Francisco',$actual);
	}

	function test_purchase_state () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-state');
		$this->assertEquals('California',$actual);
	}

	function test_purchase_postcode () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-postcode');

		$this->assertEquals('94123',$actual);
	}

	function test_purchase_country () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-country');
		$this->assertEquals('USA',$actual);
	}

	function test_purchase_shipaddress () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shipaddress');
		$this->assertEquals('24-593 Federation Dr',$actual);
	}

	function test_purchase_shipxaddress () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shipxaddress');
		$this->assertEquals('Shipping',$actual);
	}

	function test_purchase_shipcity () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shipcity');
		$this->assertEquals('San Francisco',$actual);
	}

	function test_purchase_shipstate () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shipstate');
		$this->assertEquals('California',$actual);
	}

	function test_purchase_shippostcode () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shippostcode');
		$this->assertEquals('94123',$actual);
	}

	function test_purchase_shipcountry () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shipcountry');
		$this->assertEquals('USA',$actual);
	}

	function test_purchase_shipmethod () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-shipmethod');
		$this->assertEquals('', $actual);
	}

	function test_purchase_items_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-totalitems');
		$this->assertEquals('1', $actual);
	}

	function test_purchase_item_id () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-id');
		$this->assertEquals('1', $actual);
	}

	function test_purchase_item_product () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$Product = shopp_product('uss-enterprise','slug');
		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-product');
		$this->assertEquals($Product->id, $actual);
	}

	function test_purchase_item_price () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-price');
		$this->assertEquals('1',$actual);
	}

	function test_purchase_item_name () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-name');
		$this->assertEquals('USS Enterprise', $actual);
	}

	function test_purchase_item_description () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-description');
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_options () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-options');
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_sku () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-sku');
		$this->assertEquals('NCC-1701',$actual);
	}

	function test_purchase_item_download () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-download');
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_quantity () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-quantity');
		$this->assertEquals('1',$actual);
	}

	function test_purchase_item_unitprice () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-unitprice');
		$this->assertEquals('$17.01',$actual);
	}

	function test_purchase_item_total () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$actual = shopp('purchase.get-item-total');
		$this->assertEquals('$17.01',$actual);
	}

	function test_purchase_item_input_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		ob_start();
		if (shopp('purchase','item-has-inputs'))
			while(shopp('purchase','item-inputs'))
				shopp('purchase','item-input','name');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$output);

	}

	function test_purchase_item_inputs_list () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		shopp('purchase','has-items');
		$output = shopp('purchase.get-item-inputs-list');
		$this->assertEquals('',$output);

	}

	function test_purchase_data_tags () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		ob_start();
		if (shopp('purchase','hasdata'))
			while(shopp('purchase','orderdata'))
				shopp('purchase','data','name');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$output);
	}

	function test_purchase_haspromo () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$this->assertFalse(shopp('purchase','haspromo','name=Test'));
	}

	function test_purchase_subtotal () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-subtotal');
		$this->assertEquals('$17.01',$actual);
	}

	function test_purchase_hasfrieght () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$this->assertTrue(shopp('purchase','hasfreight'));
	}

	function test_purchase_freight () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-freight');
		$this->assertEquals('$9.87',$actual);
	}

	function test_purchase_hasdiscount () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$this->assertFalse(shopp('purchase','hasdiscount'));
	}

	function test_purchase_discount () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-discount');
		$this->assertEquals('$0.00',$actual);
	}

	function test_purchase_hastax () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$this->assertTrue(shopp('purchase','hastax'));
	}

	function test_purchase_tax () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-tax');
		$this->assertEquals('$1.70',$actual);
	}

	function test_purchase_total () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-total');
		$this->assertEquals('$28.58',$actual);
	}

	function test_purchase_status () {
        // $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

		$actual = shopp('purchase.get-status');
		$this->assertEquals('Pending',$actual);
	}

} // end PurchaseAPITests class
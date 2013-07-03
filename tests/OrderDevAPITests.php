<?php
/**
* Tests for the order dev api
*/

class OrderDevAPITests extends ShoppTestCase {

	static function setUpBeforeClass () {
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

	}

	static function tearDownAfterClass () {

	}

	function test_shopp_add_order () {

		shopp_empty_cart();
		$Customer = shopp_customer('spock@starfleet.gov', 'email');
		$Product = shopp_product('uss-enterprise', 'slug');
		shopp_add_cart_product ( $Product->id, 1 );

		$Purchase = shopp_add_order($Customer->id);
		$Purchase = shopp_order($Purchase->id);

		$this->AssertTrue( ! empty($Purchase->id) );
		$this->AssertEquals( $Customer->id, $Purchase->customer );
		$this->AssertEquals( 1, count($Purchase->purchased) );

		$Purchased = reset($Purchase->purchased);
		$this->AssertEquals( 'USS Enterprise', $Purchased->name );

		remove_action('shopp_authed_order_event',array(ShoppOrder(),'notify'));
		remove_action('shopp_authed_order_event',array(ShoppOrder(),'accounts'));
		remove_action('shopp_authed_order_event',array(ShoppOrder(),'success'));
		shopp_add_order_event($Purchase->id, 'authed', array(
			'txnid' => $Purchase->id.'TEST',	// Transaction ID
			'amount' => $Purchase->total,		// Gross amount authorized
			'gateway' => 'GatewayFramework',	// Gateway handler name (module name from @subpackage)
			'paymethod' => 'TestSuite',			// Payment method (payment method label from payment settings)
			'paytype' => 'TestSuite',			// Type of payment (check, MasterCard, etc)
			'payid' => ''						// Payment ID (last 4 of card or check number)
		));

		// $this->AssertEquals('authed',$Purchase->txnstatus);
		$this->AssertEquals(28.58, shopp_order_amt_balance($Purchase->id));
		$this->AssertEquals(28.58, shopp_order_amt_invoiced($Purchase->id));
		$this->AssertEquals(28.58, shopp_order_amt_authorized($Purchase->id));
		$this->AssertEquals(0, shopp_order_amt_captured($Purchase->id));
		$this->AssertEquals(0, shopp_order_amt_refunded($Purchase->id));
		$this->AssertFalse(shopp_order_is_void($Purchase->id));

		shopp_add_order_event($Purchase->id, 'captured', array(
			'txnid' => $Purchase->id.'TEST',	// Transaction ID
			'amount' => $Purchase->total,		// Gross amount authorized
			'gateway' => 'GatewayFramework',	// Gateway handler name (module name from @subpackage)
			'fees' => 0.0,						// Transaction fees taken by the gateway net revenue = amount-fees

		));

		$this->AssertEquals(28.58, shopp_order_amt_captured($Purchase->id));
	}

	function test_shopp_last_order() {
		$Purchase = shopp_last_order();

		$Purchased = reset($Purchase->purchased);

		$this->AssertTrue( ! empty($Purchase->id) );
		$this->AssertEquals( 1, count($Purchase->purchased) );
		$this->AssertEquals( 'USS Enterprise', $Purchased->name );
	}

	function test_shopp_order_lines () {
		$lines = shopp_order_lines(shopp_last_order()->id);
		$this->AssertEquals(count($lines), shopp_order_line_count(shopp_last_order()->id));
	}

	function test_shopp_add_order_line () {
		$Purchase = shopp_last_order();
		$pid = $Purchase->id;

	    $this->AssertEquals( 17.01, $Purchase->subtotal );
	    $this->AssertEquals( 9.87, $Purchase->freight );
	    $this->AssertEquals( 1.7, $Purchase->tax );
	    $this->AssertEquals( 28.58, $Purchase->total );

		$item = array(
			'product' => 0, // product id of line item
			'price' => 0, // variant id of line item
			'name' => 'My fake item', // name of item
			'description' => 'My off the cuff item for adding to the order', // description of item
			'optionlabel' => 'My fake item', // string label of variant combination of this item
			'quantity' => 2, // quantity of items on this line
			'unitprice' => 10, // unit price
			'unittax' => 1, // unit tax
			'shipping' => 0, // line item shipping cost
			'total' => 20, // line item total cost
			'data' => array('test'=>'value') // associative array of item "data" key value pairs
			);

		shopp_add_order_line($pid, $item);

		$this->AssertEquals(2, shopp_order_line_count($pid));

		$Purchase = shopp_last_order();
	    $this->AssertEquals( 17.01 + $item['total'], $Purchase->subtotal );
	    $this->AssertEquals( 9.87, $Purchase->freight );
	    $this->AssertEquals( 1.7 + ( $item['unittax'] * $item['quantity'] ), $Purchase->tax );
	    $this->AssertEquals( 28.58 + $item['total'] + ( $item['unittax'] * $item['quantity'] ), $Purchase->total );
	}

	function test_shopp_rmv_order_line() {
		$Purchase = shopp_last_order();
		$pid = $Purchase->id;

		$this->AssertEquals(2, shopp_order_line_count($pid));

		$Purchase = shopp_last_order();
	    $this->AssertEquals( 37.01, $Purchase->subtotal );
	    $this->AssertEquals( 9.87, $Purchase->freight );
	    $this->AssertEquals( 3.7, $Purchase->tax );
	    $this->AssertEquals( 50.58, $Purchase->total );

		shopp_rmv_order_line($pid, 1);
		$this->AssertEquals(1, shopp_order_line_count($pid));

		$Purchase = shopp_last_order();

	    $this->AssertEquals( 17.01, $Purchase->subtotal );
	    $this->AssertEquals( 9.87, $Purchase->freight );
	    $this->AssertEquals( 1.7, $Purchase->tax );
	    $this->AssertEquals( 28.58, $Purchase->total );
	}


	function test_shopp_add_order_line_download (){
		// Create Product
		$data = array(
			'name' => "Product Download",
			'publish' => array( 'flag' => true ),
			'description' => "Product Download Test",
			'packaging' => true
		);
		$data['single'] = array(
			'type' => 'Download',
			'price' => 1.00,
		);
		$DownloadProduct = shopp_add_product($data);

		// Create Item object
		$Item = new Item($DownloadProduct, false);
		$Item->quantity(1);

		shopp_add_order_line(shopp_last_order()->id, $Item );

		// Add a download file after item has already be added
		$download = shopp_add_product_download ( $DownloadProduct->id,  dirname(__FILE__).'/data/1.png');

		$this->AssertTrue(shopp_add_order_line_download ( shopp_last_order()->id, 1, $download ));
	}

	function test_shopp_rmv_order() {
		$pid = shopp_last_order()->id;
		$this->AssertTrue(shopp_rmv_order($pid));
		$this->AssertFalse($pid == shopp_last_order()->id);
	}

}
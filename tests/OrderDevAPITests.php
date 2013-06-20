<?php

/**
* Tests for the order dev api
*/
class OrderDevAPITests extends ShoppTestCase {

	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		shopp_empty_cart();
		$this->_set_setting('tax_shipping', 'off');
	}

	function tearDown () {
		$this->_restore_setting('tax_shipping');
	}

	function test_shopp_add_order () {
		global $Shopp;

		$user = get_user_by('login', 'jdillick');
		$data = array(
			'wpuser' => $user->ID,
			'firstname' => "John",
			'lastname' => "Dillick",
			'email' => $user->user_email,
			'phone' => '999-999-9999',
			'company' => 'Ingenesis Limited',
			'marketing' => 'no',
			'type' => 'Tax-Exempt',
			'saddress' => '1 N Main Street',
			'sxaddress' => 'Attn: John Dillick',
			'scity' => 'San Jose',
			'sstate' => 'CA',
			'scountry' => 'US',
			'spostcode' => '95131',
			'baddress' => '1 N Main Street',
			'bxaddress' => 'Attn: John Dillick',
			'bcity' => 'San Jose',
			'bstate' => 'CA',
			'bcountry' => 'US',
			'bpostcode' => '95131',
			'residential' => true
		);
		$cid = shopp_add_customer($data);

		$Product = shopp_product('1/10 Carat Diamond Journey Heart Pendant in Yellow Gold', 'name');
		shopp_add_cart_product ( $Product->id, 1 );
		$Purchase = shopp_add_order($cid);
		$Purchase->load_purchased();

		$this->AssertTrue( ! empty($Purchase->id) );
		$this->AssertEquals( $cid, $Purchase->customer );
		$this->AssertEquals( 1, count($Purchase->purchased) );

		$Purchased = reset($Purchase->purchased);
		$this->AssertEquals( '1/10 Carat Diamond Journey Heart Pendant in Yellow Gold', $Purchased->name );

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
		$this->AssertEquals(51.4, shopp_order_amt_balance($Purchase->id));
		$this->AssertEquals(51.4, shopp_order_amt_invoiced($Purchase->id));
		$this->AssertEquals(51.4, shopp_order_amt_authorized($Purchase->id));
		$this->AssertEquals(0, shopp_order_amt_captured($Purchase->id));
		$this->AssertEquals(0, shopp_order_amt_refunded($Purchase->id));
		$this->AssertFalse(shopp_order_is_void($Purchase->id));

		shopp_add_order_event($Purchase->id, 'captured', array(
			'txnid' => $Purchase->id.'TEST',	// Transaction ID
			'amount' => $Purchase->total,		// Gross amount authorized
			'gateway' => 'GatewayFramework',	// Gateway handler name (module name from @subpackage)
			'fees' => 0.0,						// Transaction fees taken by the gateway net revenue = amount-fees

		));

		$this->AssertEquals(51.4, shopp_order_amt_captured($Purchase->id));
	}

	function test_shopp_last_order() {
		$Purchase = shopp_last_order();
		$Purchased = reset($Purchase->purchased);

		$this->AssertTrue( ! empty($Purchase->id) );
		$this->AssertEquals( 1, count($Purchase->purchased) );
		$this->AssertEquals( '1/10 Carat Diamond Journey Heart Pendant in Yellow Gold', $Purchased->name );
	}

	function test_shopp_order_lines() {
		$lines = shopp_order_lines(shopp_last_order()->id);
		$this->AssertEquals(count($lines), shopp_order_line_count(shopp_last_order()->id));
	}

	function test_shopp_add_order_line() {
		$Purchase = shopp_last_order();
		$pid = $Purchase->id;

	    $this->AssertEquals( 44, $Purchase->subtotal );
	    $this->AssertEquals( 3, $Purchase->freight );
	    $this->AssertEquals( 4.4, $Purchase->tax );
	    $this->AssertEquals( 51.4, $Purchase->total );

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
	    $this->AssertEquals( 44 + $item['total'], $Purchase->subtotal );
	    $this->AssertEquals( 3, $Purchase->freight );
	    $this->AssertEquals( 4.4 + ( $item['unittax'] * $item['quantity'] ), $Purchase->tax );
	    $this->AssertEquals( 51.4 + $item['total'] + ( $item['unittax'] * $item['quantity'] ), $Purchase->total );
	}

	function test_shopp_rmv_order_line() {
		$Purchase = shopp_last_order();
		$pid = $Purchase->id;

		$this->AssertEquals(2, shopp_order_line_count($pid));

		$Purchase = shopp_last_order();
	    $this->AssertEquals( 64, $Purchase->subtotal );
	    $this->AssertEquals( 3, $Purchase->freight );
	    $this->AssertEquals( 6.4, $Purchase->tax );
	    $this->AssertEquals( 73.4, $Purchase->total );

		shopp_rmv_order_line($pid, 1);
		$this->AssertEquals(1, shopp_order_line_count($pid));

		$Purchase = shopp_last_order();

	    $this->AssertEquals( 44, $Purchase->subtotal );
	    $this->AssertEquals( 3, $Purchase->freight );
	    $this->AssertEquals( 4.4, $Purchase->tax );
	    $this->AssertEquals( 51.4, $Purchase->total );
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
		$download = shopp_add_product_download ( $DownloadProduct->id,  dirname(__FILE__).'/Assets/1.png');
		$this->AssertTrue(shopp_add_order_line_download ( shopp_last_order()->id, 1, $download ));
	}

	function test_shopp_rmv_order() {
		$pid = shopp_last_order()->id;
		$this->AssertTrue(shopp_rmv_order($pid));
		$this->AssertFalse($pid == shopp_last_order()->id);
	}
}

?>
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

	function setUp () {
		parent::setUp();
		$_SERVER['REQUEST_URI'] = "/";
		ShoppPurchase( new Purchase(1) );
		ShoppPurchase()->load_purchased();
	}

	function test_purchase_id () {
		ob_start();
		shopp('purchase','id');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1',$actual);
	}

	function test_purchase_date () {
		ob_start();
		shopp('purchase','date');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('December 1, 2009 1:03 am',$actual);
	}

	function test_purchase_card () {
		ob_start();
		shopp('purchase','card');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('XXXXXXXXXXXX1111',$actual);
	}

	function test_purchase_cardtype () {
		ob_start();
		shopp('purchase','cardtype');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Visa',$actual);
	}

	function test_purchase_transactionid () {
		ob_start();
		shopp('purchase','transactionid');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('TESTMODE',$actual);
	}

	function test_purchase_firstname () {
		ob_start();
		shopp('purchase','firstname');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Jonathan',$actual);
	}

	function test_purchase_lastname () {
		ob_start();
		shopp('purchase','lastname');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Davis',$actual);
	}

	function test_purchase_company () {
		ob_start();
		shopp('purchase','company');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Ingenesis Limited',$actual);
	}

	function test_purchase_email () {
		ob_start();
		shopp('purchase','email');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('jond@ingenesis.net',$actual);
	}

	function test_purchase_phone () {
		ob_start();
		shopp('purchase','phone');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('(555) 555-5555',$actual);
	}

	function test_purchase_address () {
		ob_start();
		shopp('purchase','address');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1 N Main St',$actual);
	}

	function test_purchase_xaddress () {
		ob_start();
		shopp('purchase','xaddress');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);
	}

	function test_purchase_city () {
		ob_start();
		shopp('purchase','city');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('San Jose',$actual);
	}

	function test_purchase_state () {
		ob_start();
		shopp('purchase','state');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('California',$actual);
	}

	function test_purchase_postcode () {
		ob_start();
		shopp('purchase','postcode');
		$actual = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('95131',$actual);
	}

	function test_purchase_country () {
		ob_start();
		shopp('purchase','country');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('USA',$actual);
	}

	function test_purchase_shipaddress () {
		ob_start();
		shopp('purchase','shipaddress');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1 N Main St',$actual);
	}

	function test_purchase_shipxaddress () {
		ob_start();
		shopp('purchase','shipxaddress');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);
	}

	function test_purchase_shipcity () {
		ob_start();
		shopp('purchase','shipcity');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('San Jose',$actual);
	}

	function test_purchase_shipstate () {
		ob_start();
		shopp('purchase','shipstate');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('California',$actual);
	}

	function test_purchase_shippostcode () {
		ob_start();
		shopp('purchase','shippostcode');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('95131',$actual);
	}

	function test_purchase_shipcountry () {
		ob_start();
		shopp('purchase','shipcountry');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('USA',$actual);
	}

	function test_purchase_shipmethod () {
		ob_start();
		shopp('purchase','shipmethod');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Standard',$actual);
	}

	function test_purchase_items_tags () {
		ob_start();
		shopp('purchase','totalitems');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1',$actual);
	}

	function test_purchase_item_id () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-id');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1',$actual);
	}

	function test_purchase_item_product () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-product');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('24',$actual);
	}

	function test_purchase_item_price () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-price');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('29',$actual);
	}

	function test_purchase_item_name () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-name');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Aion',$actual);
	}

	function test_purchase_item_description () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-description');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_options () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-options');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_sku () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-sku');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_download () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-download');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$actual);
	}

	function test_purchase_item_quantity () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-quantity');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('1',$actual);
	}

	function test_purchase_item_unitprice () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-unitprice');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$49.82',$actual);
	}

	function test_purchase_item_total () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-total');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$49.82',$actual);
	}

	function test_purchase_item_input_tags () {
		shopp('purchase','items');
		ob_start();
		if (shopp('purchase','item-has-inputs'))
			while(shopp('purchase','item-inputs'))
				shopp('purchase','item-input','name');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$output);

	}

	function test_purchase_item_inputs_list () {
		shopp('purchase','items');
		ob_start();
		shopp('purchase','item-inputs-list');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$output);

	}

	function test_purchase_data_tags () {
		ob_start();
		if (shopp('purchase','hasdata'))
			while(shopp('purchase','orderdata'))
				shopp('purchase','data','name');
		$output = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('',$output);
	}

	function test_purchase_haspromo () {
		$this->assertFalse(shopp('purchase','haspromo','name=Test'));
	}

	function test_purchase_subtotal () {
		ob_start();
		shopp('purchase','subtotal');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$49.82',$actual);
	}

	function test_purchase_hasfrieght () {
		$this->assertTrue(shopp('purchase','hasfreight'));
	}

	function test_purchase_freight () {
		ob_start();
		shopp('purchase','freight');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$3.00',$actual);
	}

	function test_purchase_hasdiscount () {
		$this->assertFalse(shopp('purchase','hasdiscount'));
	}

	function test_purchase_discount () {
		ob_start();
		shopp('purchase','discount');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$0.00',$actual);
	}

	function test_purchase_hastax () {
		$this->assertTrue(shopp('purchase','hastax'));
	}

	function test_purchase_tax () {
		ob_start();
		shopp('purchase','tax');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$7.47',$actual);
	}

	function test_purchase_total () {
		ob_start();
		shopp('purchase','total');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('$60.29',$actual);
	}

	function test_purchase_status () {
		ob_start();
		shopp('purchase','status');
		$actual = ob_get_contents();
		ob_end_clean();
		$this->assertEquals('Pending',$actual);
	}

} // end PurchaseAPITests class

?>
<?php
/**
 * CartTotalsTests
 *
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  2 February, 2011
 * @package shopp
 * @subpackage
 **/

/**
 * CartTotalsTests
 *
 * @author
 * @since 1.1
 * @package shopp
 **/
class CartTotalsTests extends ShoppTestCase {

	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
	}

	function test_cart_base_case () {
		$Product = shopp_product('aion','slug');
		$options = array('number' => true,'return' => true);

		shopp_set_setting('tax_shipping', 'off');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$expected = '$49.82';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '49.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '4.982';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '57.80';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	function test_cart_tax_shipping () {
		$Product = shopp_product('aion','slug');
		$options = array('number' => true,'return' => true);

		shopp_set_setting('tax_shipping', 'on');
		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$expected = '$49.82';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '49.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '5.282';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '58.10';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');
		shopp_set_setting('tax_shipping', 'off');

	}

	function test_cart_item_percent_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('cart-item-promo');

		$expected = '$59.82';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '19.7406';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '4.00794';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '47.09';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	function test_cart_order_percent_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('2percent');

		$expected = '$59.82';
		shopp('cart', 'items');
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '1.1964';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '5.982';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '67.6';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	function test_cart_shipping_discount () {
		$options = array('number' => true,'return' => true);
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('FreeShip');

		$expected = '$59.82';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '5.982';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '0.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '65.8';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}


	function test_cart_vat_base_case () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$default_base = shopp_setting('base_operations');
		shopp_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:14:"United Kingdom";s:8:"currency";a:2:{s:4:"code";s:3:"GBP";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:2:"£";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:6:"metric";s:6:"region";i:3;s:7:"country";s:2:"GB";s:4:"zone";N;s:3:"vat";b:1;}'));
		shopp_set_setting('tax_inclusive','on');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$expected = '£65.80';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£5.98';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£68.80';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');
		shopp_set_setting('base_operations',$default_base);

	}

	function test_cart_vat_taxed_shipping () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');
		$default_base = shopp_setting('base_operations');
		shopp_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:14:"United Kingdom";s:8:"currency";a:2:{s:4:"code";s:3:"GBP";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:2:"£";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:6:"metric";s:6:"region";i:3;s:7:"country";s:2:"GB";s:4:"zone";N;s:3:"vat";b:1;}'));
		shopp_set_setting('tax_inclusive','on');
		shopp_set_setting('tax_shipping','on');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);

		$options = array('return' => true,'money'=>true,'wrap'=>false);

		$expected = '£65.80';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£6.25';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£2.73';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£68.80';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

		shopp_set_setting('tax_shipping', 'off');
		shopp_set_setting('base_operations',$default_base);

	}

	function test_cart_vat_item_percent_discount () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');
		$default_base = shopp_setting('base_operations');
		shopp_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:14:"United Kingdom";s:8:"currency";a:2:{s:4:"code";s:3:"GBP";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:2:"£";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:6:"metric";s:6:"region";i:3;s:7:"country";s:2:"GB";s:4:"zone";N;s:3:"vat";b:1;}'));
		shopp_set_setting('tax_inclusive','on');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('cart-item-promo');

		$options = array('return' => true,'money'=>true,'wrap'=>false);

		$expected = '£63.83';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£19.74';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£4.01';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£47.09';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

		shopp_set_setting('tax_inclusive','off');
		shopp_set_setting('base_operations',$default_base);
	}

	public function test_cartitem_amountoff_promocode_multi_qty () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,10);

		$options = array('number' => true,'return' => true);

		// Create promo
		$Promotion = new Promotion();
		$Promotion->status = "enabled";
		$Promotion->type = "Amount Off";
		$Promotion->discount = 1; // $1 off
		$Promotion->target = "Cart Item";
		$Promotion->search = "all";
		$Promotion->rules = array(
			"item" => array( // item rules
				array(
					"property" => "Name",
					"logic" => "Is equal to",
					"value" => "Marvel Ultimate Alliance 2"
					)
			),
			1 => array(
				"property"=>"Promo code",
				"logic"=>"Is equal to",
				"value"=>"cartitemamount"
			)
		);

		global $Shopp;
		$Shopp->Promotions->promotions = array($Promotion);
		shopp_add_cart_promocode('cartitemamount');

		shopp('cart', 'items');
		$expected = '$59.82';
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$expected = '$598.20';
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '598.20';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '10.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '58.82';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '650.02';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');
	}

	public function test_cartitem_amoutoff_multi_qty () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');

		$options = array('number' => true,'return' => true);

		// Create promo
		$Promotion = new Promotion();
		$Promotion->status = "enabled";
		$Promotion->type = "Amount Off";
		$Promotion->discount = 1; // $1 off
		$Promotion->target = "Cart Item";
		$Promotion->search = "all";
		$Promotion->rules = array(
			"item" => array( // item rules
				array(
					"property" => "Name",
					"logic" => "Is equal to",
					"value" => "Marvel Ultimate Alliance 2"
					),
				array(
					"property" => "Quantity",
					"logic" => "Is greater than",
					"value" => "9"
				)
			),
			1 => array(
				"property"=>"Total quantity",
				"logic"=>"Is greater than",
				"value"=>"1"
			)
		);

		global $Shopp;
		$Shopp->Promotions->promotions = array($Promotion);

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,10);

		shopp('cart', 'items');
		$expected = '$59.82';
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$expected = '$598.20';
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '598.20';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '10.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '58.82';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '650.02';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

	}

	public function test_cartitem_buy_x_get_y () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');
		$options = array('number' => true,'return' => true);

		// Create promo
		$Promotion = new Promotion();
		$Promotion->status = "enabled";
		$Promotion->type = "Buy X Get Y Free";
		$Promotion->buyqty = 5;
		$Promotion->getqty = 1;
		$Promotion->target = "Cart Item";
		$Promotion->search = "all";
		$Promotion->rules = array(
			"item" => array( // item rules
				array(
					"property" => "Name",
					"logic" => "Is equal to",
					"value" => "Marvel Ultimate Alliance 2"
					)
			),
			1 => array(
				"property"=>"Promo code",
				"logic"=>"Is equal to",
				"value"=>"buy5get1"
			)
		);


		global $Shopp;
		$Shopp->Promotions->promotions = array($Promotion);

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,6);
		shopp_add_cart_promocode('buy5get1');

		shopp('cart', 'items');
		$expected = '$59.82';
		$actual = shopp('cartitem','unitprice',$options);
		$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
		$expected = '$358.92';
		$actual = shopp('cartitem','total',$options);
		$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		shopp('cart', 'items');

		$expected = '358.92';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '59.82';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '29.91';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '332.01';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

		$Shopp->Promotions->promotions = array();
		$Shopp->Promotions->load();
	}


	function test_cart_vat_order_percent_discount () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$default_base = shopp_setting('base_operations');
		shopp_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:14:"United Kingdom";s:8:"currency";a:2:{s:4:"code";s:3:"GBP";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:2:"£";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:6:"metric";s:6:"region";i:3;s:7:"country";s:2:"GB";s:4:"zone";N;s:3:"vat";b:1;}'));
		shopp_set_setting('tax_inclusive','on');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('2percent');

		$expected = '£65.80';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£1.20';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£5.98';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£3.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£67.60';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

		shopp_set_setting('tax_inclusive','off');
		shopp_set_setting('base_operations',$default_base);
	}

	function test_cart_vat_shipping_discount () {
		$Product = shopp_product('marvel-ultimate-alliance-2','slug');
		$options = array('return' => true,'money'=>true,'wrap'=>false);
		$default_base = shopp_setting('base_operations');
		shopp_set_setting('base_operations', unserialize('a:7:{s:4:"name";s:14:"United Kingdom";s:8:"currency";a:2:{s:4:"code";s:3:"GBP";s:6:"format";a:6:{s:4:"cpos";b:1;s:8:"currency";s:2:"£";s:9:"precision";i:2;s:8:"decimals";s:1:".";s:9:"thousands";s:1:",";s:8:"grouping";a:1:{i:0;i:3;}}}s:5:"units";s:6:"metric";s:6:"region";i:3;s:7:"country";s:2:"GB";s:4:"zone";N;s:3:"vat";b:1;}'));
		shopp_set_setting('tax_inclusive','on');

		shopp_empty_cart();
		shopp_add_cart_product($Product->id,1);
		shopp_add_cart_promocode('FreeShip');

		$expected = '£65.80';
		while(shopp('cart', 'items')){
			$actual = shopp('cartitem','unitprice',$options);
			$this->assertEquals($expected, $actual,'Cart line item unit price assertion failed');
			$actual = shopp('cartitem','total',$options);
			$this->assertEquals($expected, $actual,'Cart line item total assertion failed');
		}

		$expected = '£59.82';
		$actual = shopp('cart','subtotal',$options);
		$this->assertEquals($expected, $actual,'Cart subtotal assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','discount',$options);
		$this->assertEquals($expected, $actual,'Cart discount assertion failed');

		$expected = '£5.98';
		$actual = shopp('cart','tax',$options);
		$this->assertEquals($expected, $actual,'Cart tax assertion failed');

		$expected = '£0.00';
		$actual = shopp('cart','shipping',$options);
		$this->assertEquals($expected, $actual,'Cart shipping assertion failed');

		$expected = '£65.80';
		$actual = shopp('cart','total',$options);
		$this->assertEquals($expected, $actual,'Cart grand Total assertion failed');

		shopp_set_setting('tax_inclusive','off');
		shopp_set_setting('base_operations',$default_base);
	}


} // end CartTotalsTests class

?>
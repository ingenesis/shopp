<?php
/**
 * Test for the customer dev api
 *
 * @author John Dillick
 * @since 1.2
 **/
class CustomerDevAPITests extends ShoppTestCase {

	static function setUpBeforeClass () {

		shopp_set_setting('account_system', 'wordpress');

		// Create the WordPress account
		$wpuser = wp_insert_user(array(
			'user_login' => 'jimkirk',
			'user_pass' => 'imcaptainkirk!',
			'user_email' => 'jkirk@starfleet.gov',
			'display_name' => 'Captain James T. Kirk',
			'nickname' => 'Jim',
			'first_name' => 'James',
			'last_name' => 'Kirk'
		));

		$customerid = shopp_add_customer(array(
			'wpuser' => $wpuser,
			'firstname' => 'James',
			'lastname' => 'Kirk',
			'phone' => '555-555-5555',
			'email' => 'jkirk@starfleet.gov',
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
		shopp_set_setting('account_system', 'none');
		$Customer = shopp_customer('jkirk@starfleet.gov','email');
		wp_delete_user( $Customer->wpuser );
	}

	function test_customer_by_email () {

		// Lookup by email
		$Customer = shopp_customer('jkirk@starfleet.gov', 'email');

		$this->AssertFalse(!$Customer);
		$this->AssertEquals('James', $Customer->firstname);
		$this->AssertEquals('Kirk', $Customer->lastname);
		$this->AssertEquals('555-555-5555', $Customer->phone);
		$this->AssertEquals('Starfleet Command', $Customer->company);

		$this->AssertFalse(!$Customer->Billing);
		$this->AssertEquals('24-593 Federation Dr', $Customer->Billing->address);
		$this->AssertEquals('San Francisco', $Customer->Billing->city);
		$this->AssertEquals('CA', $Customer->Billing->state);
		$this->AssertEquals('US', $Customer->Billing->country);
		$this->AssertEquals('94123', $Customer->Billing->postcode);

		$this->AssertFalse(!$Customer->Shipping);
		$this->AssertEquals('24-593 Federation Dr', $Customer->Shipping->address);
		$this->AssertEquals('San Francisco', $Customer->Shipping->city);
		$this->AssertEquals('CA', $Customer->Shipping->state);
		$this->AssertEquals('US', $Customer->Shipping->country);
		$this->AssertEquals('94123', $Customer->Shipping->postcode);

	}

	function test_customer_by_wpuser () {
		// Lookup by email
		$EmailCustomer = shopp_customer('jkirk@starfleet.gov', 'email');

		// Lookup by WordPress user ID
		$Customer = shopp_customer($EmailCustomer->wpuser, 'wpuser');

		$this->AssertFalse(!$Customer);
		$this->AssertEquals('James', $Customer->firstname);
		$this->AssertEquals('Kirk', $Customer->lastname);
		$this->AssertEquals('555-555-5555', $Customer->phone);
		$this->AssertEquals('Starfleet Command', $Customer->company);

		$this->AssertFalse(!$Customer->Billing);
		$this->AssertEquals('24-593 Federation Dr', $Customer->Billing->address);
		$this->AssertEquals('San Francisco', $Customer->Billing->city);
		$this->AssertEquals('CA', $Customer->Billing->state);
		$this->AssertEquals('US', $Customer->Billing->country);
		$this->AssertEquals('94123', $Customer->Billing->postcode);

		$this->AssertFalse(!$Customer->Shipping);
		$this->AssertEquals('24-593 Federation Dr', $Customer->Shipping->address);
		$this->AssertEquals('San Francisco', $Customer->Shipping->city);
		$this->AssertEquals('CA', $Customer->Shipping->state);
		$this->AssertEquals('US', $Customer->Shipping->country);
		$this->AssertEquals('94123', $Customer->Shipping->postcode);

	}

	function test_shopp_customer_by_id () {
		// Lookup by email
		$EmailCustomer = shopp_customer('jkirk@starfleet.gov', 'email');

		//Lookup by Shopp customer id
		$Customer = shopp_customer($EmailCustomer->id);

		$this->AssertFalse(!$Customer);
		$this->AssertEquals('James', $Customer->firstname);
		$this->AssertEquals('Kirk', $Customer->lastname);
		$this->AssertEquals('555-555-5555', $Customer->phone);
		$this->AssertEquals('Starfleet Command', $Customer->company);

		$this->AssertFalse(!$Customer->Billing);
		$this->AssertEquals('24-593 Federation Dr', $Customer->Billing->address);
		$this->AssertEquals('San Francisco', $Customer->Billing->city);
		$this->AssertEquals('CA', $Customer->Billing->state);
		$this->AssertEquals('US', $Customer->Billing->country);
		$this->AssertEquals('94123', $Customer->Billing->postcode);

		$this->AssertFalse(!$Customer->Shipping);
		$this->AssertEquals('24-593 Federation Dr', $Customer->Shipping->address);
		$this->AssertEquals('San Francisco', $Customer->Shipping->city);
		$this->AssertEquals('CA', $Customer->Shipping->state);
		$this->AssertEquals('US', $Customer->Shipping->country);
		$this->AssertEquals('94123', $Customer->Shipping->postcode);
	}

	function test_shopp_customer_exists () {
		$Customer = shopp_customer('jkirk@starfleet.gov', 'email');

		$cid = $Customer->id;
		$wpuser = $Customer->wpuser;
		$email = $Customer->email;
		$Customer = false;

		$this->AssertTrue(shopp_customer_exists($cid));
		$this->AssertTrue(shopp_customer_exists($wpuser, 'wpuser'));
		$this->AssertTrue(shopp_customer_exists($email, 'email'));
		$this->AssertFalse(@shopp_customer_exists(99999));
		$this->AssertFalse(@shopp_customer_exists(99999, 'wpuser'));
		$this->AssertFalse(@shopp_customer_exists('bogus@example.com', 'email'));
	}

	function test_shopp_customer_marketing () {
		$Customer = shopp_customer('jkirk@starfleet.gov', 'email');
		$cid = $Customer->id;

		// starts marketing set to off
		$this->AssertFalse(shopp_customer_marketing($cid));

		// turn on marketing to this customer
		$this->AssertTrue(shopp_customer_marketing($cid, true));
		$this->AssertTrue(shopp_customer_marketing($cid));

		// turn off marketing to this customer
		$this->AssertFalse(shopp_customer_marketing($cid, false));
		$this->AssertFalse(shopp_customer_marketing($cid));
	}

	function test_shopp_customer_marketing_list () {
		$cids = array();
		$cids[] = shopp_customer('jkirk@starfleet.gov', 'email')->id;
		$user = get_user_by('login', 'jimkirk');
		$cids[] = shopp_customer($user->ID, 'wpuser')->id;
		$this->AssertTrue( $cids[0] && $cids[1] );

		// turn on marketing for both accounts
		$this->AssertTrue(shopp_customer_marketing($cids[0], true));
		$this->AssertTrue(shopp_customer_marketing($cids[1], true));

		// get marketing list
		$marketing = shopp_customer_marketing_list();
		$this->AssertFalse( ! $marketing );
		$this->AssertTrue('yes' == $marketing[$cids[0]]->marketing);
		$this->AssertTrue('yes' == $marketing[$cids[1]]->marketing);
	}

	function test_shopp_add_customer () {
		$data = array(
			'wpuser' => 1701,
			'firstname' => "Montgomery",
			'lastname' => "Scott",
			'email' => 'scotty@starfleet.gov',
			'phone' => '999-999-9999',
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
		);
		$cid = shopp_add_customer($data);
		$this->AssertFalse(!$cid);

		$Customer = shopp_customer($cid);
		$this->AssertFalse(!$Customer);

		$this->AssertEquals(1701, $Customer->wpuser);
		$this->AssertEquals('Montgomery', $Customer->firstname);
		$this->AssertEquals('Scott', $Customer->lastname);
		$this->AssertEquals('scotty@starfleet.gov', $Customer->email);
		$this->AssertEquals('999-999-9999', $Customer->phone);
		$this->AssertEquals('Starfleet Command', $Customer->company);
		$this->AssertEquals('no', $Customer->marketing);
		$this->AssertEquals('Tax-Exempt', $Customer->type);

		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($cid, $Customer->$at->customer);
			$this->AssertEquals('24-593 Federation Dr', $Customer->$at->address);
			$this->AssertEquals($at, $Customer->$at->xaddress);
			$this->AssertEquals('San Francisco', $Customer->$at->city);
			$this->AssertEquals('CA', $Customer->$at->state);
			$this->AssertEquals('US', $Customer->$at->country);
			$this->AssertEquals('94123', $Customer->$at->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', $Customer->$at->residential);
		}
	}

	function test_shopp_rmv_customer_address () {
		$customer = shopp_customer('scotty@starfleet.gov', 'email');

		shopp_rmv_customer_address($customer->Shipping->id);
		shopp_rmv_customer_address($customer->Billing->id);
		$customer_id = $customer->id;

		$customer = false;
		$customer = shopp_customer($customer_id);
		$this->AssertTrue( ! $customer->Billing->id );
		$this->AssertTrue( ! $customer->Shipping->id );
	}

	function test_shopp_add_customer_address () {
		$data = array(
			'address' => '24-593 Federation Dr',
			'xaddress' => 'Attn: Chief Engineer',
			'city' => 'San Francisco',
			'state' => 'CA',
			'country' => 'US',
			'postcode' => '94123',
			'residential' => true
		);
		$customer = shopp_customer('scotty@starfleet.gov', 'email');
		shopp_add_customer_address($customer->id, $data, 'both');
		$customerid = $customer->id;

		$customer = false;
		$customer = shopp_customer($customerid);

		$this->AssertEquals($customerid, $customer->id);
		$this->AssertEquals('Montgomery', $customer->firstname);
		$this->AssertEquals('Scott', $customer->lastname);
		$this->AssertEquals('scotty@starfleet.gov', $customer->email);
		$this->AssertEquals('999-999-9999', $customer->phone);
		$this->AssertEquals('Starfleet Command', $customer->company);
		$this->AssertEquals('no', $customer->marketing);
		$this->AssertEquals('Tax-Exempt', $customer->type);

		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($customer->id, $customer->$at->customer);
			$this->AssertEquals('24-593 Federation Dr', $customer->$at->address);
			$this->AssertEquals('Attn: Chief Engineer', $customer->$at->xaddress);
			$this->AssertEquals('San Francisco', $customer->$at->city);
			$this->AssertEquals('CA', $customer->$at->state);
			$this->AssertEquals('US', $customer->$at->country);
			$this->AssertEquals('94123', $customer->$at->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', $customer->$at->residential);
		}
	}

	function test_shopp_address () {
		$customer = shopp_customer('scotty@starfleet.gov', 'email');
		$Billing = shopp_address($customer->id, 'billing');
		$Shipping = shopp_address($customer->id, 'shipping');

		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($customer->id, ${$at}->customer);
			$this->AssertEquals('24-593 Federation Dr', ${$at}->address);
			$this->AssertEquals('Attn: Chief Engineer', ${$at}->xaddress);
			$this->AssertEquals('San Francisco', ${$at}->city);
			$this->AssertEquals('CA', ${$at}->state);
			$this->AssertEquals('US', ${$at}->country);
			$this->AssertEquals('94123', ${$at}->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', ${$at}->residential);
		}

	}

	function test_shopp_customer_address_count () {
		$customer = shopp_customer('scotty@starfleet.gov', 'email');
		$this->AssertEquals(2, shopp_customer_address_count($customer->id));
	}

	function test_shopp_customer_addresses () {
		$customer = shopp_customer('scotty@starfleet.gov', 'email');
		$addresses = shopp_customer_addresses($customer->id);
		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($customer->id, $addresses[strtolower($at)]->customer);
			$this->AssertEquals('24-593 Federation Dr', $addresses[strtolower($at)]->address);
			$this->AssertEquals('Attn: Chief Engineer', $addresses[strtolower($at)]->xaddress);
			$this->AssertEquals('San Francisco', $addresses[strtolower($at)]->city);
			$this->AssertEquals('CA', $addresses[strtolower($at)]->state);
			$this->AssertEquals('US', $addresses[strtolower($at)]->country);
			$this->AssertEquals('94123', $addresses[strtolower($at)]->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', $addresses[strtolower($at)]->residential);
		}
	}

	function test_shopp_rmv_customer () {
		$customer = shopp_customer('scotty@starfleet.gov', 'email');
		$customerid = $customer->id;
		shopp_rmv_customer($customer->id);
		$this->AssertFalse( shopp_customer($customerid) );
	}

}
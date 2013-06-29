<?php
/**
 * Test for the customer dev api
 *
 * @author John Dillick
 * @since 1.2
 **/
class CustomerDevAPITests extends ShoppTestCase {

	static $customers;

	static function setUpBeforeClass () {
		self::$customers = array(
			array(
				'firstname' => 'James',
				'lastname' => 'Kirk',
				'phone' => '555-555-5555',
				'email' => 'jkirk@starfleet.gov',
				'company' => 'Starfleet Command'

			)
					//24-593 Federation Drive, San Francisco, CA

		);


		foreach ($customers as $data) {
			$Customer = new Customer();
			$Customer->updates($data);
			$Customer->save();
			print_r($Customer);
		}

	}
	function setUp () {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');

	}

	function test_shopp_customer () {

		// Lookup by email
		$Customer = shopp_customer('jkirk@starfleet.gov', 'email');

		$this->AssertFalse(!$Customer);
		$this->AssertEquals('James', $Customer->firstname);
		$this->AssertEquals('Kirk', $Customer->lastname);
		$this->AssertEquals('555-555-5555', $Customer->phone);
		$this->AssertEquals('Starfleet Command', $Customer->company);

		$this->AssertFalse(!$Customer->Billing);
		$this->AssertEquals('1 N Main Street', $Customer->Billing->address);
		$this->AssertEquals('San Jose', $Customer->Billing->city);
		$this->AssertEquals('CA', $Customer->Billing->state);
		$this->AssertEquals('US', $Customer->Billing->country);
		$this->AssertEquals('95131', $Customer->Billing->postcode);

		$this->AssertFalse(!$Customer->Shipping);
		$this->AssertEquals('1 N Main Street', $Customer->Shipping->address);
		$this->AssertEquals('San Jose', $Customer->Shipping->city);
		$this->AssertEquals('CA', $Customer->Shipping->state);
		$this->AssertEquals('US', $Customer->Shipping->country);
		$this->AssertEquals('95131', $Customer->Shipping->postcode);

		$ID = $Customer->wpuser;

		$Customer = false;

		// Lookup by WordPress user ID
		$Customer = shopp_customer($ID, 'wpuser');

		$this->AssertFalse(!$Customer);
		$this->AssertEquals('Jonathan', $Customer->firstname);
		$this->AssertEquals('Davis', $Customer->lastname);
		$this->AssertEquals('555-555-5555', $Customer->phone);
		$this->AssertEquals('Ingenesis Limited', $Customer->company);

		$this->AssertFalse(!$Customer->Billing);
		$this->AssertEquals('1 N Main Street', $Customer->Billing->address);
		$this->AssertEquals('San Jose', $Customer->Billing->city);
		$this->AssertEquals('CA', $Customer->Billing->state);
		$this->AssertEquals('US', $Customer->Billing->country);
		$this->AssertEquals('95131', $Customer->Billing->postcode);

		$this->AssertFalse(!$Customer->Shipping);
		$this->AssertEquals('1 N Main Street', $Customer->Shipping->address);
		$this->AssertEquals('San Jose', $Customer->Shipping->city);
		$this->AssertEquals('CA', $Customer->Shipping->state);
		$this->AssertEquals('US', $Customer->Shipping->country);
		$this->AssertEquals('95131', $Customer->Shipping->postcode);

		$cid = $Customer->id;
		$Customer = false;

		//Lookup by Shopp customer id
		$Customer = shopp_customer($cid);

		$this->AssertFalse(!$Customer);
		$this->AssertEquals('Jonathan', $Customer->firstname);
		$this->AssertEquals('Davis', $Customer->lastname);
		$this->AssertEquals('555-555-5555', $Customer->phone);
		$this->AssertEquals('Ingenesis Limited', $Customer->company);

		$this->AssertFalse(!$Customer->Billing);
		$this->AssertEquals('1 N Main Street', $Customer->Billing->address);
		$this->AssertEquals('San Jose', $Customer->Billing->city);
		$this->AssertEquals('CA', $Customer->Billing->state);
		$this->AssertEquals('US', $Customer->Billing->country);
		$this->AssertEquals('95131', $Customer->Billing->postcode);

		$this->AssertFalse(!$Customer->Shipping);
		$this->AssertEquals('1 N Main Street', $Customer->Shipping->address);
		$this->AssertEquals('San Jose', $Customer->Shipping->city);
		$this->AssertEquals('CA', $Customer->Shipping->state);
		$this->AssertEquals('US', $Customer->Shipping->country);
		$this->AssertEquals('95131', $Customer->Shipping->postcode);
	}

	function test_shopp_customer_exists () {
		$Customer = shopp_customer('shopp@shopplugin.net', 'email');
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
		$Customer = shopp_customer('shopp@shopplugin.net', 'email');
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
		$cids[] = shopp_customer('shopp@shopplugin.net', 'email')->id;
		$user = get_user_by('login', 'admin');
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
		$this->AssertFalse(!$cid);

		$Customer = shopp_customer($cid);
		$this->AssertFalse(!$Customer);

		$this->AssertEquals($user->ID, $Customer->wpuser);
		$this->AssertEquals('John', $Customer->firstname);
		$this->AssertEquals('Dillick', $Customer->lastname);
		$this->AssertEquals($user->user_email, $Customer->email);
		$this->AssertEquals('999-999-9999', $Customer->phone);
		$this->AssertEquals('Ingenesis Limited', $Customer->company);
		$this->AssertEquals('no', $Customer->marketing);
		$this->AssertEquals('Tax-Exempt', $Customer->type);

		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($cid, $Customer->$at->customer);
			$this->AssertEquals('1 N Main Street', $Customer->$at->address);
			$this->AssertEquals('Attn: John Dillick', $Customer->$at->xaddress);
			$this->AssertEquals('San Jose', $Customer->$at->city);
			$this->AssertEquals('CA', $Customer->$at->state);
			$this->AssertEquals('US', $Customer->$at->country);
			$this->AssertEquals('95131', $Customer->$at->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', $Customer->$at->residential);
		}
	}

	function test_shopp_rmv_customer_address () {
		$user = get_user_by('login', 'jdillick');
		$customer = shopp_customer($user->ID, 'wpuser');
		shopp_rmv_customer_address($customer->Shipping->id);
		shopp_rmv_customer_address($customer->Billing->id);

		$customer = shopp_customer($customer->id);
		$this->AssertTrue( ! $customer->Billing->id );
		$this->AssertTrue( ! $customer->Shipping->id );
	}

	function test_shopp_add_customer_address () {
		$data = array(
			'address' => '1 N Main Street',
			'xaddress' => 'Attn: John Dillick',
			'city' => 'San Jose',
			'state' => 'CA',
			'country' => 'US',
			'postcode' => '95131',
			'residential' => true
		);
		$user = get_user_by('login', 'jdillick');
		$customer = shopp_customer($user->ID, 'wpuser');
		shopp_add_customer_address($customer->id, $data, 'both');

		$customer = shopp_customer($customer->id);
		$this->AssertEquals($user->ID, $customer->wpuser);
		$this->AssertEquals('John', $customer->firstname);
		$this->AssertEquals('Dillick', $customer->lastname);
		$this->AssertEquals($user->user_email, $customer->email);
		$this->AssertEquals('999-999-9999', $customer->phone);
		$this->AssertEquals('Ingenesis Limited', $customer->company);
		$this->AssertEquals('no', $customer->marketing);
		$this->AssertEquals('Tax-Exempt', $customer->type);

		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($customer->id, $customer->$at->customer);
			$this->AssertEquals('1 N Main Street', $customer->$at->address);
			$this->AssertEquals('Attn: John Dillick', $customer->$at->xaddress);
			$this->AssertEquals('San Jose', $customer->$at->city);
			$this->AssertEquals('CA', $customer->$at->state);
			$this->AssertEquals('US', $customer->$at->country);
			$this->AssertEquals('95131', $customer->$at->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', $customer->$at->residential);
		}
	}

	function test_shopp_address () {
		$user = get_user_by('login', 'jdillick');
		$customer = shopp_customer($user->ID, 'wpuser');
		$Billing = shopp_address($customer->id, 'billing');
		$Shipping = shopp_address($customer->id, 'shipping');

		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($customer->id, ${$at}->customer);
			$this->AssertEquals('1 N Main Street', ${$at}->address);
			$this->AssertEquals('Attn: John Dillick', ${$at}->xaddress);
			$this->AssertEquals('San Jose', ${$at}->city);
			$this->AssertEquals('CA', ${$at}->state);
			$this->AssertEquals('US', ${$at}->country);
			$this->AssertEquals('95131', ${$at}->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', ${$at}->residential);
		}

	}

	function test_shopp_customer_address_count () {
		$user = get_user_by('login', 'jdillick');
		$customer = shopp_customer($user->ID, 'wpuser');
		$this->AssertEquals(2, shopp_customer_address_count($customer->id));
	}

	function test_shopp_customer_addresses () {
		$user = get_user_by('login', 'jdillick');
		$customer = shopp_customer($user->ID, 'wpuser');
		$addresses = shopp_customer_addresses($customer->id);
		$aTypes = array('Billing', 'Shipping');
		foreach ( $aTypes as $at ) {
			$this->AssertEquals($customer->id, $addresses[strtolower($at)]->customer);
			$this->AssertEquals('1 N Main Street', $addresses[strtolower($at)]->address);
			$this->AssertEquals('Attn: John Dillick', $addresses[strtolower($at)]->xaddress);
			$this->AssertEquals('San Jose', $addresses[strtolower($at)]->city);
			$this->AssertEquals('CA', $addresses[strtolower($at)]->state);
			$this->AssertEquals('US', $addresses[strtolower($at)]->country);
			$this->AssertEquals('95131', $addresses[strtolower($at)]->postcode);
			if ( 'Shipping' == $at ) $this->AssertEquals('on', $addresses[strtolower($at)]->residential);
		}
	}

	function test_shopp_rmv_customer () {
		$user = get_user_by('login', 'jdillick');
		$customer = shopp_customer($user->ID, 'wpuser');
		shopp_rmv_customer($customer->id);
		$this->AssertFalse(shopp_customer($user->ID, 'wpuser'));
	}

}
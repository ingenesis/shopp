<?php
/**
 * Customer API
 *
 * Plugin api function for customers
 *
 * @copyright Ingenesis Limited, June 23, 2011
 * @license   GNU GPL version 3 ( or later = false ) {@see license.txt}
 * @package   Shopp/API/Customer
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Get a ShoppCustomer with ShoppBilling and ShoppShipping
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (optional) customer id, WordPress user associated customer, email address associated with customer, or false to load the current global customer object
 * @param string $key (optional default:customer) customer for lookup by customer id, wpuser to lookup by WordPress user, or email to lookup by email address
 * @return mixed, stdClass representation of the customer, bool false on failure
 **/
function shopp_customer ( $customer = false, $key = 'customer' ) {
	$Customer = false;
	if ( ! $customer ) {
		$Customer = &ShoppCustomer();
		return $Customer;
	}

	if ( 'wpuser' == $key ) {
		if ( 'wordpress' != shopp_setting('account_system') ) {
			shopp_debug(__FUNCTION__ . " failed: Customer $customer could not be found.");
			return false;
		}
		$Customer = new ShoppCustomer($customer, 'wpuser');
	} else if ( 'email' == $key ) {
		$Customer = new ShoppCustomer($customer, 'email');
	} else {
		$Customer = new ShoppCustomer($customer);
	}

	if ( ! $Customer->id ) {
		shopp_debug(__FUNCTION__ . " failed: Customer $customer could not be found.");
		return false;
	}

	$Customer->Billing = new BillingAddress($Customer->id, 'customer');
	$Customer->Shipping = new ShippingAddress($Customer->id, 'customer');
	if ( ! $Customer->id ) $Customer->Shipping->copydata($Customer->Billing);

	return $Customer;
}

/**
 * Find out if the customer exists
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (required) customer id, WordPress user associated customer, or email address associated with customer.
 * @param string $key (optional default:customer) customer for lookup by customer id, wpuser to lookup by WordPress user, or email to lookup by email address
 * @return bool true if the customer exists, else false
 **/
function shopp_customer_exists ( $customer = false, $key = 'customer' ) {
	if ( ! $customer ) {
		shopp_debug(__FUNCTION__ . " failed: customer parameter required.");
		return false;
	}
	$Customer = shopp_customer($customer, $key);
	return ( false != $Customer );
}

/**
 * Set or get the marketing status for a customer.
 *
 * @api
 * @since 1.2
 *
 * @param int $customer customer id to check or set
 * @param mixed $flag (optional default:null) null to return the marketing status, true to turn on marketing for a customer, false to turn off marketing for a customer
 * @return bool true if marketing accepted, false on failure and if marketing is not accepted.
 **/
function shopp_customer_marketing ( $customer = false, $flag = null ) {
	$Customer = shopp_customer($customer);

	if ( $Customer ) {
		if ( null === $flag ) return (isset($Customer->marketing) && "yes" == $Customer->marketing);

		$Customer->marketing = ( $flag ? "yes" : "no" );
		$Customer->save();
		return "yes" == $Customer->marketing;
	}

	return false;
}

/**
 * Get a list of customer names, type, and email addresses for marketing
 *
 * @api
 * @since 1.2
 *
 * @param bool $exclude true to exclude customers that do not allow marketing, false to include all customers
 * @return array list of customers for marketing
 **/
function shopp_customer_marketing_list ( $exclude = false ) {
	$table = ShoppDatabaseObject::tablename(ShoppCustomer::$table);
	$where = ( $exclude ? "WHERE marketing='yes'" : "");
	$results = db::query( "SELECT id, firstname, lastname, email, marketing, type FROM $table $where", 'array' );

	$marketing = array();
	foreach ( $results as $c ) {
		if ( ! isset($c->id) ) continue;
		$marketing[$c->id] = $c;
	}
	return $marketing;
}

/**
 * Create a new customer record
 *
 * @api
 * @since 1.2
 *
 * @param array $data data to create the new customer record from, including: wpuser, firstname, lastname, email, phone, company, marketing, type, saddress, sxaddress, scity, sstate, scountry, spostcode, sgeocode, residential, baddress, bxaddress, bcity, bstate, bcountry, bpostcode, bgeocode
 * @return bool|int returns false on failure, and the new customer id on success
 **/
function shopp_add_customer ( $data = array() ) {
	if ( empty($data) ) {
		shopp_debug("shopp_add_customer - no customer data supplied.");
		return false;
	}

	$map = array('wpuser', 'firstname', 'lastname', 'email', 'phone', 'company', 'marketing', 'type');
	$address_map = array( 'saddress' => 'address', 'baddress' => 'address', 'sxaddress' => 'xaddress', 'bxaddress' => 'xaddress', 'scity' => 'city', 'bcity' => 'city', 'sstate' => 'state', 'bstate' => 'state', 'scountry' => 'country', 'bcountry' => 'country', 'spostcode' => 'postcode', 'bpostcode' => 'postcode', 'sgeocode' => 'geocode', 'bgeocode' => 'geocode', 'residential'=>'residential' );

	// handle duplicate or missing wpuser
	if ( isset($data['wpuser']) ) {
		$c = new ShoppCustomer($data['wpuser'], 'wpuser');
		if ( $c->id ) {
			shopp_debug(__FUNCTION__ . " failed: Customer with WordPress user id {$data['wpuser']} already exists.");
			return false;
		}
	} else if ( "wordpress" == shopp_setting('account_system') ) {
		shopp_debug(__FUNCTION__ . " failed: Wordpress account id must by specified in data array with key wpuser.");
		return false;
	}

	// handle duplicate or missing email address
	if ( isset($data['email']) ) {
		$c = new ShoppCustomer($data['email'], 'email');
		if ( $c->id ) {
			shopp_debug(__FUNCTION__ . " failed: Customer with email {$data['email']} already exists.");
			return false;
		}
	} else {
		shopp_debug(__FUNCTION__ . " failed: Email address must by specified in data array with key email.");
		return false;
	}

	// handle missing first or last name
	if ( ! isset($data['firstname']) || ! isset($data['lastname']) ) {
		shopp_debug("shopp_add_customer failure: Data array missing firstname or lastname.");
		return false;
	}

	$shipping = array();
	$billing = array();
	$Customer = new ShoppCustomer();

	foreach ( $data as $key => $value ) {
		if ( in_array($key, $map) ) $Customer->{$key} = $value;
		elseif( SHOPP_DEBUG && ! in_array( $key, array_keys($address_map) ) )
			shopp_debug("shopp_add_customer notice: Invalid customer data $key");
		if ( in_array( $key, array_keys($address_map) ) ) {
			$type = ( 's' == substr($key, 0, 1) ? 'shipping' : 'billing' );
			${$type}[$address_map[$key]] = $value;
		}
	}

	$Customer->save();
	if ( ! $Customer->id ) {
		shopp_debug(__FUNCTION__ . " failed: Could not create customer.");
		return false;
	}
	if ( ! empty($shipping) ) shopp_add_customer_address( $Customer->id, $shipping, 'shipping' );
	if ( ! empty($billing) ) shopp_add_customer_address( $Customer->id, $billing, 'billing' );

	return $Customer->id;
} // end shopp_add_customer

/**
 * Update customer information for a given customer record
 *
 * @api
 * @since 1.3
 *
 * @param int $customer The ID of the customer record to update
 * @param array $data An associative array of customer data to update
 * @return int|bool Customer ID on success, false otherwise
 **/
function shopp_set_customer ( $customer = false, $data = array() ) {

	if ( ! $customer ) {
		shopp_debug(__FUNCTION__ . " failed: customer parameter required.");
		return false;
	}

	if ( empty($data) ) {
		shopp_debug(__FUNCTION__ . " failed: no customer data supplied.");
		return false;
	}

	$Customer = new ShoppCustomer($customer);
	if ( empty($Customer->id) ) {
		shopp_debug(__FUNCTION__ . " failed: No such customer with id $customer");
		return false;
	}

	$map = array('wpuser', 'firstname', 'lastname', 'email', 'phone', 'company', 'marketing', 'type');
	$address_map = array( 'saddress' => 'address', 'baddress' => 'address', 'sxaddress' => 'xaddress', 'bxaddress' => 'xaddress', 'scity' => 'city', 'bcity' => 'city', 'sstate' => 'state', 'bstate' => 'state', 'scountry' => 'country', 'bcountry' => 'country', 'spostcode' => 'postcode', 'bpostcode' => 'postcode', 'sgeocode' => 'geocode', 'bgeocode' => 'geocode', 'residential'=>'residential' );

	// handle duplicate or missing wpuser
	if ( isset($data['wpuser']) ) {
		$c = new ShoppCustomer($data['wpuser'], 'wpuser');
		if ( $c->id ) {
			shopp_debug(__FUNCTION__ . " failed: Customer with WordPress user id {$data['wpuser']} already exists.");
			return false;
		}
	} else if ( "wordpress" == shopp_setting('account_system') ) {
		shopp_debug(__FUNCTION__ . " failed: Wordpress account id must by specified in data array with key wpuser.");
		return false;
	}

	// handle duplicate or missing email address
	if ( isset($data['email']) ) {
		$c = new ShoppCustomer($data['email'], 'email');
		if ( $c->id ) {
			shopp_debug(__FUNCTION__ . " failed: Customer with email {$data['email']} already exists.");
			return false;
		}
	} else {
		shopp_debug(__FUNCTION__ . " failed: Email address must by specified in data array with key email.");
		return false;
	}

	// handle missing first or last name
	if ( ! isset($data['firstname']) || ! isset($data['lastname']) ) {
		shopp_debug("shopp_add_customer failure: Data array missing firstname or lastname.");
		return false;
	}

	$shipping = array();
	$billing = array();

	foreach ( $data as $key => $value ) {
		if ( in_array($key, $map) ) $Customer->{$key} = $value;
		elseif( SHOPP_DEBUG && ! in_array( $key, array_keys($address_map) ) )
			shopp_debug("shopp_add_customer notice: Invalid customer data $key");
		if ( in_array( $key, array_keys($address_map) ) ) {
			$type = ( 's' == substr($key, 0, 1) ? 'shipping' : 'billing' );
			${$type}[$address_map[$key]] = $value;
		}
	}

	$Customer->save();
	if ( ! $Customer->id ) {
		shopp_debug(__FUNCTION__ . " failed: Could not create customer.");
		return false;
	}
	if ( ! empty($shipping) ) shopp_add_customer_address( $Customer->id, $shipping, 'shipping' );
	if ( ! empty($billing) ) shopp_add_customer_address( $Customer->id, $billing, 'billing' );

	return $Customer->id;
} // end shopp_set_customer

/**
 * An alias for shopp_add_customer_address
 *
 * @see shopp_add_customer_address
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (required) the customer id the address is added to
 * @param array $data (required) key value pairs for address, values can be keyed 'address', 'xaddress', 'city', 'state', 'postcode', 'country', 'geocode',  and 'residential' (residential added to shipping address)
 * @param string $type (optional default: billing) billing, shipping, or both
 * @return mixed int id for one address creation/update, array of ids if created/updated both shipping and billing, bool false on error
 */
function shopp_set_customer_address ( $customer = false, $data = false, $type = 'billing' ) {
	return shopp_add_customer_address ( $customer, $data, $type );
}

/**
 * Add or update an address for a customer
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (required) the customer id the address is added to
 * @param array $data (required) key value pairs for address, values can be keyed 'address', 'xaddress', 'city', 'state', 'postcode', 'country', 'geocode',  and 'residential' (residential added to shipping address)
 * @param string $type (optional default: billing) billing, shipping, or both
 * @return mixed int id for one address creation/update, array of ids if created/updated both shipping and billing, bool false on error
 **/
function shopp_add_customer_address ( $customer = false, $data = false, $type = 'billing' ) {
	if ( ! $customer ) {
		shopp_debug(__FUNCTION__ . " failed: Customer id required.");
		return false;
	}

	if ( empty($data) ) {
		shopp_debug(__FUNCTION__ . " failed: data array is empty");
		return false;
	}

	$map = array( 'address', 'xaddress', 'city', 'state', 'postcode', 'country', 'geocode', 'residential' );
	$address = array();

	foreach ( $map as $property ) {
		if ( isset($data[$property]) ) $address[$property] = $data[$property];
		if ( isset($data[$property]) && 'residential' == $property ) $address[$property] = Shopp::str_true($data[$property]) ? "on" : "off";
	}

	if ( in_array($type, array('billing','both')) ) {
		$Billing = new BillingAddress($customer, 'customer');
		$Billing->customer = $customer;
	}

	if ( in_array($type, array('shipping','both')) ) {
		$Shipping = new ShippingAddress($customer, 'customer');
		$Shipping->customer = $customer;
	}

	if ( 'billing' == $type ) {
		$Billing->updates($address);
		if ( apply_filters('shopp_validate_address', true, $Billing) ) {
			$Billing->save();
			return $Billing->id;
		}
	} else if ( 'shipping' == $type ) {
		$Shipping->updates($address);
		if ( apply_filters('shopp_validate_address', true, $Shipping) ) {
			$Shipping->save();
			return $Shipping->id;
		}
	} else { // both
		$Billing->updates($address);
		$Shipping->updates($address);
		if ( apply_filters('shopp_validate_address', true, $Billing) && apply_filters('shopp_validate_address', true, $Shipping) ) {
			$Billing->save();
			$Shipping->save();
			return array('billing' => $Billing->id, 'shipping' => $Shipping->id);
		}
	}

	shopp_debug(__FUNCTION__ . " failed: one or more addresses did not validate.");
	return false;
}

/**
 * Remove a customer, and data associated with the customer
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (required) id of the customer to remove
 * @return bool true on success, false on failure
 **/
function shopp_rmv_customer ( $customer = false ) {
	if ( ! $customer ) {
		shopp_debug(__FUNCTION__ . " failed: Customer id required.");
		return false;
	}

	$Customer = new ShoppCustomer($customer);
	if ( empty($Customer->id) ) {
		shopp_debug("shopp_rmv_customer notice: No such customer with id $customer");
		return false;
	}

	// Remove Addresses
	$Billing = new BillingAddress($customer, 'customer');
	$Shipping = new ShippingAddress($customer, 'customer');
	if ( ! empty($Billing->id) ) $Billing->delete();
	if ( ! empty($Shipping->id) ) $Shipping->delete();

	// Remove Meta records
	$metas = shopp_meta ( $customer, 'customer' );
	foreach( $metas as $meta ) shopp_rmv_meta ( $meta->id );

	// Remove Customer record
	$Customer->delete();

	return true;
}

/**
 * Return an address record by customer id
 *
 * @api
 * @since 1.2
 *
 * @param int $id (required) the address id to retrieve, or customer id
 * @param string $type (optional default:billing) 'billing' to lookup billing address by customer id, 'shipping' to lookup shipping adress by customer id, or 'id' to lookup by address id
 * @return ShoppAddress object
 **/
function shopp_address ( $id = false, $type = 'billing' ) {
	if ( ! $id ) {
		shopp_debug(__FUNCTION__ . " failed: Missing id parameter.");
		return false;
	}

	if ( 'billing' == $type ) {
		// use customer id to find billing address
		$Address = new BillingAddress($id, 'customer');
	} else if ( 'shipping' == $type ) {
		// use customer id to find shipping address
		$Address = new ShippingAddress($id, 'customer');
	} else {
		// lookup by address id
		$Address = new ShoppAddress($id, 'id');
	}

	if ( empty($Address->id) ) {
		shopp_debug(__FUNCTION__ . " failed: No such address with id $address.");
		return false;
	}

	return $Address;
}

/**
 * Get count of addresses stored on customer record
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (required) the customer id
 * @return int number of address records that exist for the customer
 **/
function shopp_customer_address_count ( $customer = false ) {
	if ( ! $customer ) {
		shopp_debug(__FUNCTION__ . " failed: customer id required.");
		return false;
	}
	$table = ShoppDatabaseObject::tablename(Address::$table);
	$customer = db::escape($customer);
	$results = db::query("SELECT COUNT(*) as addresses FROM $table WHERE customer=$customer");
	return ( is_object($results) && $results->addresses ? $results->addresses : 0 );
}

/**
 * Get list of addresses for a customer
 *
 * @api
 * @since 1.2
 *
 * @param int $customer (required) the customer id
 * @return array list of addresses
 **/
function shopp_customer_addresses ( $customer = false ) {
	if ( ! $customer ) {
		shopp_debug(__FUNCTION__ . " failed: customer id required.");
		return false;
	}

	$Billing = shopp_address($customer, 'billing');
	$Shipping = shopp_address($customer, 'shipping');

	return array( 'billing' => $Billing, 'shipping' => $Shipping );
}

/**
 * Remove an address
 *
 * @api
 * @since 1.2
 *
 * @param int $address the address id to remove
 * @return bool true on success, false on failure
 **/
function shopp_rmv_customer_address ( $address = false ) {
	if ( ! $address ) {
		shopp_debug(__FUNCTION__ . " failed: Missing address id parameter.");
		return false;
	}

	$Address = new ShoppAddress($address);

	if ( empty($Address->id) ) {
		shopp_debug(__FUNCTION__ . " failed: No such address with id $address.");
		return false;
	}

	return $Address->delete();
}
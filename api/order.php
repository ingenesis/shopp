<?php
/**
 * Order API
 *
 * Set of api calls for retrieving, storing, modifying orders, and sending order events.
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, June 23, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * shopp_orders - get a list of purchases
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param mixed $from (optional) mktime or SQL datetime, get purchases after this date/time.
 * @param mixed $to (optional) mktime or SQL datetime, get purchased before this date/time.
 * @param bool $items (optional default:true) load purchased items into the records, slightly slower operation
 * @param array $customers (optional) list of int customer ids to limit the purchases to.  All customers by default.
 * @param int $limit (optional default:false) maximimum number of results to get, false for no limit
 * @param string $order (optional default:DESC) DESC or ASC, for sorting in ascending or descending order.
 * @return array of Purchase objects
 **/
function shopp_orders ( $from = false, $to = false, $items = true, $customers = array(), $limit = false, $order = 'DESC' ) {
	$pt = DatabaseObject::tablename(ShoppPurchase::$table);
	$pd = DatabaseObject::tablename(ShoppPurchased::$table);

	$where = array();
	if ( $from ) {
		if ( 1 == preg_match('/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/', $from) )
			$where[] = "'$from' < created";
		else if ( is_int($from) )
			$where[] = "FROM_UNIXTIME($from) < created";
	}

	if ( $to ) {
		if ( 1 == preg_match('/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/', $to) )
			$where[] = "'$to' >= created";
		else if ( is_int($from) )
			$where[] = "FROM_UNIXTIME($to) >= created";
	}

	if ( ! empty($customers) ) {
		$set = db::escape(implode(',',$customers));
		$where[] = "0 < FIND_IN_SET(customer,'".$set."')";
	}

	$where = empty($where)?'':'WHERE '.implode(' AND ', $where);

	if ( (int)$limit > 0 ) $limit = " LIMIT $limit";
	else $limit = '';

	$query = "SELECT * FROM $pt $where ORDER BY id ".('DESC' == $order ? "DESC" : "ASC").$limit;
	$orders = DB::query($query, 'array', '_shopp_order_purchase');
	if ( $items ) $orders = DB::query("SELECT * FROM $pd AS pd WHERE 0 < FIND_IN_SET(pd.purchase,'".implode(",", array_keys($orders))."')", 'array', '_shopp_order_purchased', $orders);

	return $orders;
}

/**
 * _shopp_order_purchase - helper function for shopp_orders
 *
 * @author John Dillick
 * @since 1.2
 *
 **/
function _shopp_order_purchase ( &$records, &$record ) {
	$records[$record->id] = new ShoppPurchase();
	$records[$record->id]->populate($record);
}

/**
 * _shopp_order_purchased - helper function for shopp_orders
 *
 * @author John Dillick
 * @since 1.2
 *
 **/
function _shopp_order_purchased ( &$records, &$purchased, $orders ) {
	if ( isset($orders[$purchased->purchase]) ) {
		if ( ! isset($records[$purchased->purchase]) ) $records[$purchased->purchase] = $orders[$purchased->purchase];

		if ( ! isset($records[$purchased->purchase]->purchased) ) {
			$records[$purchased->purchase]->purchased = array();
		}

		$records[$purchased->purchase]->purchased[$purchased->id] = new ShoppPurchased();
		$records[$purchased->purchase]->purchased[$purchased->id]->populate($purchased);
		if ( "yes" == $purchased->addons ) {
			$records[$purchased->purchase]->purchased[$purchased->id]->addons = new ObjectMeta($purchased->id, 'purchased', 'addon');
		}
	}

}

/**
 * shopp_order_count - get an order count, total or during or a time period
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param mixed $from (optional) mktime or SQL datetime, get purchases after this date/time.
 * @param mixed $to (optional) mktime or SQL datetime, get purchased before this date/time.
 * @return int number of orders found
 **/
function shopp_order_count ($from = false, $to = false) {
	return count( shopp_orders( $from, $to, false ) );
}

/**
 * shopp_customer_orders - get a list of orders for a particular customer
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $customer (required) the customer id to load the orders for
 * @param mixed $from (optional) mktime or SQL datetime, get purchases after this date/time.
 * @param mixed $to (optional) mktime or SQL datetime, get purchased before this date/time.
 * @param bool $items (optional default:true) load purchased items into the records, slightly slower operation
 * @return array of Purchase objects
 **/
function shopp_customer_orders ( $customer = false, $from, $to, $items ) {
	if ( ! $customer || ! shopp_customer_exists($customer) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid or missing customer id.");
		return false;
	}

	$defaults = array('from' => false, 'to' => false, 'items' => true);
	$settings = wp_parse_args( func_get_args(), $defaults );

	extract($settings);

	return shopp_orders( $from, $to, $items, array($customer) );
}

/**
 * shopp_recent_orders - load orders for a specified time range in the past
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $time number of time units (period) to go back
 * @param string $period the time period, can be days, weeks, months, years.
 * @return array of Purchase objects
 **/
function shopp_recent_orders ($time = 1, $period = 'day') {
	$periods = array('day', 'days', 'week', 'weeks', 'month', 'months', 'year', 'years');

	if ( ! in_array($period, $periods) ) {
		shopp_debug(__FUNCTION__." failed: Invalid period $period.  Use one of (".implode(", ", $periods).")");
		return false;
	}

	if ( ! $time || ! is_int($time) || $time < 0 ) $time = 1;

	$from = strtotime("$time $period ago");

	return shopp_orders($from);
}

/**
 * shopp_recent_orders - load orders for a specified time range in the past for a particular customer
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $customer (required) the customer id to load the orders for
 * @param int $time number of time units (period) to go back
 * @param string $period the time period, can be days, weeks, months, years.
 * @return array of Purchase objects
 **/
function shopp_recent_customer_orders ($customer = false, $time = 1, $period = 'day') {
	if ( ! $customer || ! shopp_customer_exists($customer) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid or missing customer id.");
		return false;
	}

	$periods = array('day', 'days', 'week', 'weeks', 'month', 'months', 'year', 'years');

	if ( ! in_array($period, $periods) ) {
		shopp_debug(__FUNCTION__." failed: Invalid period $period.  Use one of (".implode(", ", $periods).")");
		return false;
	}

	if ( ! $time || ! is_int($time) || $time < 0 ) $time = 1;

	$from = strtotime("$time $period ago");

	return shopp_customer_orders ( $customer, $from );
}

/**
 * shopp_last_order - get the most recent order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @return ShoppPurchase object or false on failure
 **/
function shopp_last_order () {
	$orders = shopp_orders ( false, false, true, array(), 1);

	if ( is_array($orders) && ! empty($orders) ) return reset($orders);
	return false;
}

/**
 * shopp_last_customer_order - load the most recent order for a particular customer
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $customer (required) the customer id to load the order for
 * @return ShoppPurchase object or false on failure
 **/
function shopp_last_customer_order ( $customer = false ) {
	if ( ! $customer || ! shopp_customer_exists($customer) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid or missing customer id.");
		return false;
	}
	$orders = shopp_orders ( false, false, true, array($customer), 1);

	if ( is_array($orders) && ! empty($orders) ) return reset($orders);
	return false;
}

/**
 * shopp_order - load a specified order by id
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id, or the transaction id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return ShoppPurchase or false on failure
 **/
function shopp_order ( $id = false, $by = 'id' ) {
	if ( ! $id || ! $Purchase = shopp_order_exists($id, $by) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid or missing order id.");
		return false;
	}

	$Purchase->load_purchased();
	$Purchase->load_events();
	return $Purchase;
}

/**
 * shopp_order_amt_balance
 *
 * get the current amount balance left uncharged on order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id or txn id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return float|bool float amount balance on the order, bool false if order does not exist
 **/
function shopp_order_amt_balance ( $id = false, $by = 'id' ) {
	$Purchase = shopp_order( $id, $by);
	if ( ! empty($Purchase->id) ) return $Purchase->balance;
	return false;
}

/**
 * shopp_order_amt_invoiced
 *
 * get the current amount invoiced on order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id or txn id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return float|bool float amount invoiced on the order, bool false if order does not exist
 **/
function shopp_order_amt_invoiced ( $id = false, $by = 'id' ) {
	$Purchase = shopp_order( $id, $by);
	if ( ! empty($Purchase->id) ) return $Purchase->invoiced;
	return false;
}

/**
 * shopp_order_amt_authorized
 *
 * get the current amount authorized on order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id or txn id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return float|bool float amount authorized on the order, bool false if order does not exist
 **/
function shopp_order_amt_authorized ( $id = false, $by = 'id' ) {
	$Purchase = shopp_order( $id, $by);
	if ( ! empty($Purchase->id) ) return $Purchase->authorized;
	return false;
}

/**
 * shopp_order_amt_captured
 *
 * get the current amount captured on order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id or txn id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return float|bool float amount captured on the order, bool false if order does not exist
 **/
function shopp_order_amt_captured ( $id = false, $by = 'id' ) {
	$Purchase = shopp_order( $id, $by);
	if ( ! empty($Purchase->id) ) return $Purchase->captured;
	return false;
}

/**
 * shopp_order_amt_refunded
 *
 * get the current amount refunded on order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id or txn id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return float|bool float amount refunded on the order, bool false if order does not exist
 **/
function shopp_order_amt_refunded ( $id = false, $by = 'id' ) {
	$Purchase = shopp_order( $id, $by);
	if ( ! empty($Purchase->id) ) return $Purchase->refunded;
	return false;
}

/**
 * shopp_order_is_void
 *
 * find out if the order has been voided
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id or txn id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return bool|null true/false voided or null if order does not exist
 **/
function shopp_order_is_void ( $id = false, $by = 'id' ) {
	$Purchase = shopp_order( $id, $by);
	if ( ! empty($Purchase->id) ) return $Purchase->voided;
	return null;
}

/**
 * shopp_order_exists - determine if an order exists with the specified id, or transaction id.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $id the order id, or the transaction id
 * @param string $by (optional default:id) lookup by 'id', or 'trans'
 * @return Purchase|bool Purchase object returned if the order exists, else returns false
 **/
function shopp_order_exists ( $id = false, $by = 'id' ) {
	$Purchase = new ShoppPurchase();

	if ( $by == 'trans' ) {
		$Purchase->load($id,'txnid');
	} else {
		$Purchase->load($id);
	}

	if ( ! $Purchase->id ) return false;
	return $Purchase;
}

/**
 * shopp_add_order - create an order from the cart and associate with a customer
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $customer the customer that the order will be created for
 * @return bool|ShoppPurchase false on failure, Purchase object of recently created order on success
 **/
function shopp_add_order ( $customer = false ) {
	// check customer
	if ( ! $Customer = shopp_customer( (int) $customer) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid customer.");
		return false;
	}

	if ( ! shopp_cart_items_count() ) {
		shopp_debug(__FUNCTION__ . " failed: No items in cart.");
		return false;
	}

	$Order = ShoppOrder();
	$Order->Customer = $Customer;
	$Order->Billing = $Customer->Billing;
	$Order->Billing->cardtype = 'api';
	$Order->Shipping = $Customer->Shipping;

	shopp_add_order_event(false, 'purchase', array(
		'gateway' => 'GatewayFramework'
	));

	shopp_empty_cart();

	return ( $Purchase = ShoppPurchase() ) ? $Purchase : false ;
}

/**
 * shopp_rmv_order - remove an order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param init $id id of order to remove
 * @return bool true on success, false on failure
 **/
function shopp_rmv_order ($id) {
	if ( $Purchase = shopp_order_exists($id) ) {
		$Purchase->load_purchased();
		foreach ( $Purchase->purchased as $P ) {
			$Purchased = new ShoppPurchased();
			$Purchased->populate($P);
			$Purchased->delete();
		}
		$Purchase->delete();
	} else return false;

	return true;
}

/**
 * shopp_add_order_line - add a line item to an order.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order (required) the order id to add the line item to
 * @param array|Item $data data to create the free-form item from (see $item_fields for allowed data), or alternately an Item object
 * @return bool|Purchased item object - false on failure, new order line item on success.
 **/
function shopp_add_order_line ( $order = false, $data = array() ) {
	$item_fields = array(
		'product', // product id of line item
		'price', // variant id of line item
		'download', // download asset id for line item
		'dkey', // unique download key to assign to download item
		'name', // name of item
		'description', // description of item
		'optionlabel', // string label of variant combination of this item
		'sku', // sku of item
		'quantity', // quantity of items on this line
		'unitprice', // unit price
		'unittax', // unit tax
		'shipping', // line item shipping cost
		'total', // line item total cost
		'type', // Shipped, Download, Virtual, Membership, Subscription
		'addons', // array of addons
		'variation', // array of key => value (optionmenu => option) pairs for the variant combination
		'data' // associative array of item "data" key value pairs
		);

	if ( ! $Purchase = shopp_order_exists($order) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid order id.");
		return false;
	}

	// Create and save a new ShoppPurchased item object
	$Purchased = new ShoppPurchased;
	if ( is_object($data) && is_a($data, 'Item') ) {
		$Purchased->copydata($data);
		if ($data->inventory) $data->unstock();
	} else {
		// build purchased line item
		$Purchased->unitprice = $Purchased->unittax = $Purchased->shipping = $Purchased->total = 0;
		foreach ( $data as $key => $value ) {
			if ( ! in_array($key, $item_fields) ) continue;
			$Purchased->{$key} = $value;
		}
		if ( ! isset($Purchased->type) ) $Purchase->type = 'Shipped';
	}
	$Purchased->purchase = $order;
	if (!empty($Purchased->download)) $Purchased->keygen();
	$Purchased->save();

	// Update the Purchase
	$Purchase->subtotal += $Purchased->unitprice * $Purchased->quantity;

	$Purchase->tax += $Purchased->unittax * $Purchased->quantity;
	$Purchase->freight += $Purchased->shipping;

	$total_added = $Purchased->total + ($Purchased->unittax * $Purchased->quantity) + $Purchased->shipping;
	$Purchase->total += $total_added;
	$Purchase->save();

	// invoice new amount
	shopp_add_order_event($Purchase->id,'invoiced',array(
		'gateway' => $Purchase->gateway,			// Gateway handler name (module name from @subpackage)
		'amount' => $total_added					// Capture of entire order amount
	));

	return ( ! empty($Purchased->id) ? $Purchased : false );
}

/**
 * shopp_rmv_order_line - remove an order line by index
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order (required) the order id to remove the line from
 * @param int $line (optional default:0) the index of the line to remove
 * @return bool true on success, false on failure
 **/
function shopp_rmv_order_line ( $order = false, $line = 0 ) {
	$Lines = shopp_order_lines($order);

	$ids = array_keys($Lines);
	if (!isset($ids[$line])) return false;

	$id = $ids[$line];

	if ( empty($Lines) || $line >= count($Lines) ) return false;

	$Purchased = new ShoppPurchased();
	$Purchased->populate($Lines[$id]);
	$Purchase = shopp_order($order);

	$Purchase->subtotal -= $Purchased->unitprice * $Purchased->quantity;
	$Purchase->tax -= $Purchased->unittax * $Purchased->quantity;
	$Purchase->freight -= $Purchased->shipping;
	$total_removed = $Purchased->total + ($Purchased->unittax * $Purchased->quantity) + $Purchased->shipping;
	$Purchase->total -= $total_removed;
	$Purchased->delete();
	$Purchase->save();

	if ( $Purchase->balance && $Purchase->balance >= $total_removed ) {
		// invoice new amount
		shopp_add_order_event($Purchase->id,'amt-voided',array(
			'amount' => $total_removed					// Capture of entire order amount
		));
	}

	return true;
}

/**
 * shopp_order_lines - get a list of the items associated with an order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order (required) the order id
 * @return bool|array false on failure, array of ShoppPurchased line item objects on success
 **/
function shopp_order_lines ( $order = false ) {
	$Order = shopp_order( $order );
	if ( $Order ) return $Order->purchased;
	return false;
}

/**
 * shopp_order_line_count - get the number of line items in a specified order
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id
 * @return int the number of line items
 **/
function shopp_order_line_count ( $order = false ) {
	$lines = shopp_order_lines($order);
	if ( $lines ) return count($lines);
	return 0;
}

/**
 * shopp_add_order_line_download - attach a download asset to a order line
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id to add the download asset to
 * @param int $line the order line item to add the download asset to
 * @param int $download the download asset id
 * @return bool true on success, false on failure
 **/
function shopp_add_order_line_download ( $order = false, $line = 0, $download = false ) {
	$Lines = shopp_order_lines($order);

	$ids = array_keys($Lines);

	if ( empty($Lines) || $line >= count($Lines) || ! isset($ids[$line]) )
		return false;

	$id = $ids[$line];

	$DL = new ProductDownload($download);
	if ( empty($DL->id) ) {
		shopp_debug(__FUNCTION__ . " failed: Invalid or missing download asset id.");
		return false;
	}

	$Purchased = new ShoppPurchased;
	$Purchased->populate($Lines[$id]);

	$Purchased->download = $download;
	$Purchased->keygen();
	$Purchased->save();
	return true;
}

/**
 * shopp_rmv_order_line_download - remove a download asset from a line item
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id to remove the download asset from
 * @param int $line the order line item to remove the download asset from
 * @return bool true on success, false on failure
 **/
function shopp_rmv_order_line_download ( $order = false, $line = 0 ) {
	$Lines = shopp_order_lines($order);
	$ids = array_keys($Lines);

	if ( empty($Lines) || $line >= count($Lines) || ! isset($ids[$line]) )
		return false;

	$id = $ids[$line];
	$Purchased = new ShoppPurchased;
	$Purchased->populate($Lines[$id]);

	$Purchase->download = 0;
	$Purchase->dkey = '';
	$Purchase->save();
	return true;
}

/**
 * shopp_order_data
 *
 * Retrieve one or or all order data entries
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id from which to retrieve the data entry
 * @param string $name (optional) the key/index of the data entry to retrieve from the data, false for the whole array
 * @return mixed one named order data value, or an array of data, false if no value can be found
 **/
function shopp_order_data ( $order = false, $name = false ) {
	if ( ($Purchase = shopp_order_exists($order)) && isset($Purchase->data) && is_array($Purchase->data) ) {
		if ( false === $name ) return $Purchase->data;
		if ( isset($Purchase->data[$name]) ) return $Purchase->data[$name];
	}
	return false;
}

/**
 * shopp_set_order_data
 *
 * set an order data entry
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id to which to set the order data entry
 * @param string $name the key/index of the order data entry
 * @param mixed $value the value to set to the order data
 * @return bool true on success, false on failure
 **/
function shopp_set_order_data ( $order = false, $name = false, $value = false ) {
	if ( ! ( $Purchase = shopp_order_exists($order) ) || ! $name ) {
		shopp_debug(__FUNCTION__ . " failed: Order id and name parameters are required.");
		return false;
	}

	if ( ! isset($Purchase->data) || ! is_array($Purchase->data) ) $Purchase->data = array();

	$Purchase->data[$name] = $value;
	$Purchase->save();
	return true;
}

/**
 * shopp_rmv_order_data
 *
 * Remove one or all order data entries.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id from which to remove the order data
 * @param string $name (optional default:false) the key/index of the order data entry to remove, false to remove all entries
 * @return bool true on success, false on failure
 **/
function shopp_rmv_order_data ( $order = false, $name = false ) {
	if ( ! $order || ! ( $Purchase = shopp_order_exists($order) ) ) {
		shopp_debug(__FUNCTION__ . " failed: Order id parameter is required.");
		return false;
	}
	if ( ! $name ) $Purchase->data = array();
	else if ( isset($Purchase->data[$name]) ) unset($Purchase->data[$name]);
	$Purchase->save();
	return true;
}

/**
 * shopp_order_line_data_count - return the count of the line item data array
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id
 * @param int $line the order line item
 * @return int|bool count number of entries in the line item data array for a given line item, false if line item doesn't exist
 **/
function shopp_order_line_data_count ($order = false, $line = 0 ) {
	$Lines = shopp_order_lines($order);
	if ( empty($Lines) || $line >= count($Lines) || ! isset($Lines[$line]) )
		return false;

	if ( is_array($Lines[$line]->data) ) return count($Lines[$line]->data);
	return 0;
}

/**
 * shopp_order_line_data - return the line item data
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id
 * @param int $line the order line item
 * @param string $name (optional) the key/index to the order line-item data to retrieve
 * @return mixed false if the line-item does not exist, the value if name is specified, else the data entries array
 **/
function shopp_order_line_data ($order = false, $line = 0, $name = false) {
	$Lines = shopp_order_lines($order);
	if ( empty($Lines) || $line >= count($Lines) || ! isset($Lines[$line]) )
		return false;

	if ( is_array($Lines[$line]->data) && ! empty($Lines[$line]->data) ) {
		if ( $name && in_array($name, array_keys($Lines[$line]->data)) ) return $Lines[$line]->data[$name];
		return $Lines[$line]->data;
	}
	return false;
}

/**
 * shopp_add_order_line_data - add one or more key=>value pair to the line item data array.  The specified data is merged with existing data.
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order the order id
 * @param int $line the order line item
 * @param array $data new key=>value pairs to add to the line item
 * @return bool true on success, false on failure
 **/
function shopp_add_order_line_data ( $order = false, $line = 0, $data = array() ) {
	$Lines = shopp_order_lines($order);
	if ( empty($Lines) || $line >= count($Lines) || ! isset($Lines[$line]) )
		return false;
	$Purchased = new ShoppPurchased();
	$Purchased->populate($Lines[$line]);

	if ( ! is_array($Purchased->data) ) $Purchased->data = array();

	$Purchased->data = array_merge($Purchased->data, $data);
	$Purchased->save();
	return true;
}

/**
 * shopp_rmv_order_line_data - remove all or one data key=>value pair from the order line data array
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order (required) the order id
 * @param int $line (required) the order line item
 * @param string $name (optional default:false) the key to remove, removes all data when false
 * @return bool true on success, false on failure
 **/
function shopp_rmv_order_line_data ($order = false, $line = 0, $name = false) {
	$Lines = shopp_order_lines($order);
	if ( empty($Lines) || $line >= count($Lines) || ! isset($Lines[$line]) )
		return false;
	$Purchased = new ShoppPurchased();
	$Purchased->populate($Lines[$line]);

	if ( ! is_array($Purchased->data) ) $Purchased->data = array();
	if ( $name && in_array($name, array_keys($Purchased->data) ) ) unset($Purchased->data[$name]);

	$Purchased->save();
}

/**
 * shopp_add_order_event - log an order event
 *
 * @author John Dillick
 * @since 1.2
 *
 * @param int $order (conditionally required default:false) Will be false for purchase events, but needs the order id otherwise.
 * @param string $type (required) the order event type
 * @param array $message (optional default:array()) the event message protocol
 * @return bool true on success, false on error
 **/
function shopp_add_order_event ( $order = false, $type = false, $message = array() ) {
	if ( false !== $order && ! shopp_order_exists($order) ) {
		shopp_debug(__FUNCTION__ . " '$type' failed: Invalid order id.");
		return false;
	}

	if ( ! $type || ! OrderEvent::handler($type)) {
		shopp_debug(__FUNCTION__ . " failed: Missing or invalid order event type");
		return false;
	}

	return OrderEvent::add($order,$type,$message);
}
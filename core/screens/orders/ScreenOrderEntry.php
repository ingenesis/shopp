<?php
/**
 * ScreenOrderEntry.php
 *
 * Renders the order entry editor
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrderEntry extends ShoppScreenOrderManager {

	public function load () {
		return ShoppPurchase(new ShoppPurchase());
	}

	public function layout () {

		$Purchase = ShoppPurchase();

		ShoppUI::register_column_headers($this->id, apply_filters('shopp_order_manager_columns', array(
			'items' => Shopp::__('Items'),
			'qty'   => Shopp::__('Quantity'),
			'price' => Shopp::__('Price'),
			'total' => Shopp::__('Total')
		)));

		new ShoppAdminOrderContactBox(
			$this->id,
			'topside',
			'core',
			array('Purchase' => $Purchase)
		);

		new ShoppAdminOrderBillingAddressBox(
			$this->id,
			'topic',
			'core',
			array('Purchase' => $Purchase)
		);


		new ShoppAdminOrderShippingAddressBox(
			$this->id,
			'topsider',
			'core',
			array('Purchase' => $Purchase)
		);

		new ShoppAdminOrderManageBox(
			$this->id,
			'normal',
			'core',
			array('Purchase' => $Purchase, 'Gateway' => $Purchase->gateway())
		);

		if ( isset($Purchase->data) && '' != join('', (array)$Purchase->data) || apply_filters('shopp_orderui_show_orderdata', false) )
			new ShoppAdminOrderDataBox(
				$this->id,
				'normal',
				'core',
				array('Purchase' => $Purchase)
			);

		if ( count($Purchase->events) > 0 )
			new ShoppAdminOrderHistoryBox(
				$this->id,
				'normal',
				'core',
				array('Purchase' => $Purchase)
			);

		new ShoppAdminOrderNotesBox(
			$this->id,
			'normal',
			'core',
			array('Purchase' => $Purchase)
		);

		do_action('shopp_order_new_layout');
	}

	function screen () {
		if ( ! current_user_can('shopp_orders') )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		$Purchase = ShoppPurchase();
		$Purchase->Customer = new ShoppCustomer($Purchase->customer);
		$Gateway = $Purchase->gateway();

        // if ( ! empty($_POST['send-note']) ){
        //     $user = wp_get_current_user();
        //     shopp_add_order_event($Purchase->id,'note',array(
        //         'note' => stripslashes($_POST['note']),
        //         'user' => $user->ID
        //     ));
        //
        //     $Purchase->load_events();
        // }
        //
        // if ( isset($_POST['submit-shipments']) && isset($_POST['shipment']) && !empty($_POST['shipment']) ) {
        //     $shipments = $_POST['shipment'];
        //     foreach ((array)$shipments as $shipment) {
        //         shopp_add_order_event($Purchase->id,'shipped',array(
        //             'tracking' => $shipment['tracking'],
        //             'carrier' => $shipment['carrier']
        //         ));
        //     }
        //     $this->notice(Shopp::__('Shipping notice sent.'));
        //
        //     // Save shipping carrier default preference for the user
        //     $userid = get_current_user_id();
        //     $setting = 'shopp_shipping_carrier';
        //     if ( ! get_user_meta($userid, $setting, true) )
        //         add_user_meta($userid, $setting, $shipment['carrier']);
        //     else update_user_meta($userid, $setting, $shipment['carrier']);
        //
        //     unset($_POST['ship-notice']);
        //     $Purchase->load_events();
        // }
        //
        // if (isset($_POST['order-action']) && 'refund' == $_POST['order-action']) {
        //     if ( ! current_user_can('shopp_refund') )
        //         wp_die(__('You do not have sufficient permissions to carry out this action.','Shopp'));
        //
        //     $user = wp_get_current_user();
        //     $reason = (int)$_POST['reason'];
        //     $amount = Shopp::floatval($_POST['amount']);
        //
        //     if (!empty($_POST['message'])) {
        //         $message = $_POST['message'];
        //         $Purchase->message['note'] = $message;
        //     }
        //
        //     if (!Shopp::str_true($_POST['send'])) { // Force the order status
        //         shopp_add_order_event($Purchase->id,'notice',array(
        //             'user' => $user->ID,
        //             'kind' => 'refunded',
        //             'notice' => __('Marked Refunded','Shopp')
        //         ));
        //         shopp_add_order_event($Purchase->id,'refunded',array(
        //             'txnid' => $Purchase->txnid,
        //             'gateway' => $Gateway->module,
        //             'amount' => $amount
        //         ));
        //         shopp_add_order_event($Purchase->id,'voided',array(
        //             'txnorigin' => $Purchase->txnid,    // Original transaction ID (txnid of original Purchase record)
        //             'txnid' => time(),                    // Transaction ID for the VOID event
        //             'gateway' => $Gateway->module        // Gateway handler name (module name from @subpackage)
        //         ));
        //     } else {
        //         shopp_add_order_event($Purchase->id,'refund',array(
        //             'txnid' => $Purchase->txnid,
        //             'gateway' => $Gateway->module,
        //             'amount' => $amount,
        //             'reason' => $reason,
        //             'user' => $user->ID
        //         ));
        //     }
        //
        //     if (!empty($_POST['message']))
        //         $this->addnote($Purchase->id,$_POST['message']);
        //
        //     $Purchase->load_events();
        // }
        //
        // if (isset($_POST['order-action']) && 'cancel' == $_POST['order-action']) {
        //     if ( ! current_user_can('shopp_void') )
        //         wp_die(__('You do not have sufficient permissions to carry out this action.','Shopp'));
        //
        //     // unset($_POST['refund-order']);
        //     $user = wp_get_current_user();
        //     $reason = (int)$_POST['reason'];
        //
        //     $message = '';
        //     if (!empty($_POST['message'])) {
        //         $message = $_POST['message'];
        //         $Purchase->message['note'] = $message;
        //     } else $message = 0;
        //
        //
        //     if (!Shopp::str_true($_POST['send'])) { // Force the order status
        //         shopp_add_order_event($Purchase->id,'notice',array(
        //             'user' => $user->ID,
        //             'kind' => 'cancelled',
        //             'notice' => __('Marked Cancelled','Shopp')
        //         ));
        //         shopp_add_order_event($Purchase->id,'voided',array(
        //             'txnorigin' => $Purchase->txnid,    // Original transaction ID (txnid of original Purchase record)
        //             'txnid' => time(),            // Transaction ID for the VOID event
        //             'gateway' => $Gateway->module        // Gateway handler name (module name from @subpackage)
        //         ));
        //     } else {
        //         shopp_add_order_event($Purchase->id,'void',array(
        //             'txnid' => $Purchase->txnid,
        //             'gateway' => $Gateway->module,
        //             'reason' => $reason,
        //             'user' => $user->ID,
        //             'note' => $message
        //         ));
        //     }
        //
        //     if ( ! empty($_POST['message']) )
        //         $this->addnote($Purchase->id,$_POST['message']);
        //
        //     $Purchase->load_events();
        // }
        //
        // if ( isset($_POST['billing']) && is_array($_POST['billing']) ) {
        //
        //     $Purchase->updates($_POST['billing']);
        //     $Purchase->save();
        //
        // }
        //
        // if ( isset($_POST['shipping']) && is_array($_POST['shipping']) ) {
        //
        //     $shipping = array();
        //     foreach( $_POST['shipping'] as $name => $value )
        //         $shipping[ "ship$name" ] = $value;
        //
        //     $Purchase->updates($shipping);
        //     $Purchase->shipname = $shipping['shipfirstname'] . ' ' . $shipping['shiplastname'];
        //
        //     $Purchase->save();
        // }
        //
        //
        // if ( isset($_POST['order-action']) && 'update-customer' == $_POST['order-action'] && ! empty($_POST['customer'])) {
        //     $Purchase->updates($_POST['customer']);
        //     $Purchase->save();
        // }
        //
        // if ( isset($_POST['cancel-edit-customer']) ){
        //     unset($_POST['order-action'],$_POST['edit-customer'],$_POST['select-customer']);
        // }
        //
        // // Create a new customer
        // if ( isset($_POST['order-action']) && 'new-customer' == $_POST['order-action'] && ! empty($_POST['customer']) && ! isset($_POST['cancel-edit-customer'])) {
        //     $Customer = new ShoppCustomer();
        //     $Customer->updates($_POST['customer']);
        //     $Customer->password = wp_generate_password(12,true);
        //     if ( 'wordpress' == shopp_setting('account_system') ) $Customer->create_wpuser();
        //     else unset($_POST['loginname']);
        //     $Customer->save();
        //     if ( (int)$Customer->id > 0 ) {
        //         $Purchase->customer = $Customer->id;
        //         $Purchase->copydata($Customer);
        //         $Purchase->save();
        //
        //         // New billing address, create record for new customer
        //         if ( isset($_POST['billing']) && is_array($_POST['billing']) && empty($_POST['billing']['id']) ) {
        //             $Billing = new BillingAddress($_POST['billing']);
        //             $Billing->customer = $Customer->id;
        //             $Billing->save();
        //         }
        //
        //         // New shipping address, create record for new customer
        //         if ( isset($_POST['shipping']) && is_array($_POST['shipping']) && empty($_POST['shipping']['id']) ) {
        //             $Shipping = new ShippingAddress($_POST['shipping']);
        //             $Shipping->customer = $Customer->id;
        //             $Shipping->save();
        //         }
        //
        //     } else $this->notice(Shopp::__('An unknown error occured. The customer could not be created.'), 'error');
        // }
        //
        // if ( isset($_GET['order-action']) && 'change-customer' == $_GET['order-action'] && ! empty($_GET['customerid'])) {
        //     $Customer = new ShoppCustomer((int)$_GET['customerid']);
        //     if ( (int)$Customer->id > 0) {
        //         $Purchase->copydata($Customer);
        //         $Purchase->customer = $Customer->id;
        //         $Purchase->save();
        //     } else $this->notice(Shopp::__('The selected customer was not found.'), 'error');
        // }
        //
        // if ( isset($_POST['save-item']) && isset($_POST['lineid']) ) {
        //
        //     if ( isset($_POST['lineid']) && '' == $_POST['lineid'] ) {
        //         $lineid = 'new';
        //     } else $lineid = (int)$_POST['lineid'];
        //
        //     $name = $_POST['itemname'];
        //     if ( ! empty( $_POST['product']) ) {
        //         list($productid, $priceid) = explode('-', $_POST['product']);
        //         $Product = new ShoppProduct($productid);
        //         $Price = new ShoppPrice($priceid);
        //         $name = $Product->name;
        //         if ( Shopp::__('Price & Delivery') != $Price->label )
        //             $name .= ": $Price->label";
        //     }
        //
        //     // Create a cart representation of the order to recalculate order totals
        //     $Cart = new ShoppCart();
        //
        //     $taxcountry = $Purchase->country;
        //     $taxstate = $Purchase->state;
        //     if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
        //         $taxcountry = $Purchase->shipcountry;
        //         $taxstate = $Purchase->shipstate;
        //     }
        //     ShoppOrder()->Tax->location($taxcountry, $taxstate);
        //
        //     if ( 'new' == $lineid ) {
        //         $NewLineItem = new ShoppPurchased();
        //         $NewLineItem->purchase = $Purchase->id;
        //         $Purchase->purchased[] = $NewLineItem;
        //     }
        //
        //     foreach ( $Purchase->purchased as &$Purchased ) {
        //         $CartItem = new ShoppCartItem($Purchased);
        //
        //         if ( $Purchased->id == $lineid || ('new' == $lineid && empty($Purchased->id) ) ) {
        //
        //             if ( ! empty( $_POST['product']) ) {
        //                 list($CartItem->product, $CartItem->priceline) = explode('-', $_POST['product']);
        //             } elseif ( ! empty($_POST['id']) ) {
        //                 list($CartItem->product, $CartItem->priceline) = explode('-', $_POST['id']);
        //             }
        //
        //             $CartItem->name = $name;
        //             $CartItem->unitprice = Shopp::floatval($_POST['unitprice']);
        //             $Cart->additem((int)$_POST['quantity'], $CartItem);
        //             $CartItem = $Cart->get($CartItem->fingerprint());
        //
        //             $Purchased->name = $CartItem->name;
        //             $Purchased->product = $CartItem->product;
        //             $Purchased->price = $CartItem->priceline;
        //             $Purchased->quantity = $CartItem->quantity;
        //             $Purchased->unitprice = $CartItem->unitprice;
        //             $Purchased->total = $CartItem->total;
        //             $Purchased->save();
        //
        //         } else $Cart->additem($CartItem->quantity, $CartItem);
        //
        //     }
        //
        //     $Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );
        //
        //     $Purchase->total = $Cart->total();
        //     $Purchase->subtotal = $Cart->total('order');
        //     $Purchase->discount = $Cart->total('discount');
        //     $Purchase->tax = $Cart->total('tax');
        //     $Purchase->freight = $Cart->total('shipping');
        //     $Purchase->save();
        //     $Purchase->load_purchased();
        //
        // }
        //
        // if ( ! empty($_POST['save-totals']) ) {
        //
        //     $totals = array();
        //     if ( ! empty($_POST['totals']) )
        //         $totals = $_POST['totals'];
        //
        //     $objects = array(
        //         'tax' => 'OrderAmountTax',
        //         'shipping' => 'OrderAmountShipping',
        //         'discount' => 'OrderAmountDiscount'
        //     );
        //
        //     $methods = array(
        //         'fee' => 'fees',
        //         'tax' => 'taxes',
        //         'shipping' => 'shipfees',
        //         'discount' => 'discounts'
        //     );
        //
        //     $total = 0;
        //     foreach ( $totals as $property => $fields ) {
        //         if ( empty($fields) ) continue;
        //
        //         if ( count($fields) > 1 ) {
        //             if ( isset($fields['labels']) ) {
        //                 $labels = $fields['labels'];
        //                 unset($fields['labels']);
        //                 $fields = array_combine($labels, $fields);
        //             }
        //
        //             $fields = array_map(array('Shopp', 'floatval'), $fields);
        //
        //             $entries = array();
        //             $OrderAmountObject = isset($objects[ $property ]) ? $objects[ $property ] : 'OrderAmountFee';
        //             foreach ( $fields as $label => $amount )
        //                 $entries[] = new $OrderAmountObject(array('id' => count($entries) + 1, 'label' => $label, 'amount' => $amount));
        //
        //             $savetotal = isset($methods[ $property ]) ? $methods[ $property ] : 'fees';
        //             $Purchase->$savetotal($entries);
        //
        //             $sum = array_sum($fields);
        //             if ( $sum > 0 )
        //                 $Purchase->$property = $sum;
        //
        //         } else $Purchase->$property = Shopp::floatval($fields[0]);
        //
        //         $total += ('discount' == $property ? $Purchase->$property * -1 : $Purchase->$property );
        //
        //     }
        //
        //     $Purchase->total = $Purchase->subtotal + $total;
        //     $Purchase->save();
        // }
        //
        // if ( ! empty($_GET['rmvline']) ) {
        //     $lineid = (int)$_GET['rmvline'];
        //     if ( isset($Purchase->purchased[ $lineid ]) ) {
        //         $Purchase->purchased[ $lineid ]->delete();
        //         unset($Purchase->purchased[ $lineid ]);
        //     }
        //
        //     $Cart = new ShoppCart();
        //
        //     $taxcountry = $Purchase->country;
        //     $taxstate = $Purchase->state;
        //     if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
        //         $taxcountry = $Purchase->shipcountry;
        //         $taxstate = $Purchase->shipstate;
        //     }
        //     ShoppOrder()->Tax->location($taxcountry, $taxstate);
        //
        //     foreach ( $Purchase->purchased as &$Purchased )
        //         $Cart->additem($Purchased->quantity, new ShoppCartItem($Purchased));
        //
        //     $Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );
        //
        //     $Purchase->total = $Cart->total();
        //     $Purchase->subtotal = $Cart->total('order');
        //     $Purchase->discount = $Cart->total('discount');
        //     $Purchase->tax = $Cart->total('tax');
        //     $Purchase->freight = $Cart->total('shipping');
        //     $Purchase->save();
        //
        //     $Purchase->load_purchased();
        // }
        //
        // if (isset($_POST['charge']) && $Gateway && $Gateway->captures) {
        //     if ( ! current_user_can('shopp_capture') )
        //         wp_die(__('You do not have sufficient permissions to carry out this action.','Shopp'));
        //
        //     $user = wp_get_current_user();
        //
        //     shopp_add_order_event($Purchase->id,'capture',array(
        //         'txnid' => $Purchase->txnid,
        //         'gateway' => $Purchase->gateway,
        //         'amount' => $Purchase->capturable(),
        //         'user' => $user->ID
        //     ));
        //
        //     $Purchase->load_events();
        // }

		// $targets = shopp_setting('target_markets');
		// $default = array('' => '&nbsp;');
		// $Purchase->_countries = array_merge($default, ShoppLookup::countries());
		//
		// $regions = Lookup::country_zones();
		// $Purchase->_billing_states = array_merge($default, (array)$regions[ $Purchase->country ]);
		// $Purchase->_shipping_states = array_merge($default, (array)$regions[ $Purchase->shipcountry ]);
		//
		// // Setup shipping carriers menu and JS data
		// $carriers_menu = $carriers_json = array();
		// $shipping_carriers = (array) shopp_setting('shipping_carriers'); // The store-preferred shipping carriers
		// $shipcarriers = Lookup::shipcarriers(); // The full list of available shipping carriers
		// $notrack = Shopp::__('No Tracking'); // No tracking label
		// $default = get_user_meta(get_current_user_id(), 'shopp_shipping_carrier', true);
		//
		// if ( isset($shipcarriers[ $default ]) ) {
		//	 $carriers_menu[ $default ] = $shipcarriers[ $default ]->name;
		//	 $carriers_json[ $default ] = array($shipcarriers[ $default ]->name, $shipcarriers[ $default ]->trackpattern);
		// } else {
		//	 $carriers_menu['NOTRACKING'] = $notrack;
		//	 $carriers_json['NOTRACKING'] = array($notrack, false);
		// }
		//
		//	 $serviceareas = array('*', ShoppBaseLocale()->country());
		//	 foreach ( $shipcarriers as $code => $carrier ) {
		//	 if ( $code == $default ) continue;
		//	 if ( ! empty($shipping_carriers) && ! in_array($code, $shipping_carriers) ) continue;
		//		 if ( ! in_array($carrier->areas, $serviceareas) ) continue;
		//		 $carriers_menu[ $code ] = $carrier->name;
		//		 $carriers_json[ $code ] = array($carrier->name, $carrier->trackpattern);
		//	 }
		//
		// if ( isset($shipcarriers[ $default ]) ) {
		//	 $carriers_menu['NOTRACKING'] = $notrack;
		//	 $carriers_json['NOTRACKING'] = array($notrack, false);
		// }
		//
		// if ( empty($statusLabels) ) $statusLabels = array('');

		$Purchase->taxes();
		$Purchase->discounts();

		// $columns = get_column_headers($this->id);
		// $hidden = get_hidden_columns($this->id);

		include $this->ui('new.php');
	}

}
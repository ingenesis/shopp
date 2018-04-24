<?php
/**
 * ScreenOrderManager.php
 *
 * Screen controller for the Order Manager / Editor
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrderManager extends ShoppScreenController {

    /**
     * Load the requested order
     *
     * @since 1.5
     *
     * @return void
     **/
	public function load() {
		$id = (int) $this->request('id');
		if ( $id > 0 ) {
			ShoppPurchase( new ShoppPurchase($id) );
			ShoppPurchase()->load_purchased();
			ShoppPurchase()->load_events();
		} else ShoppPurchase( new ShoppPurchase() );
	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	public function assets() {

		wp_enqueue_script('postbox');

		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('selectize');

		shopp_enqueue_script('orders');
		shopp_custom_script('orders', 'var address = [], carriers = ' . json_encode($this->shipcarriers()) . ';');
		shopp_localize_script( 'orders', '$om', array(
			'co'	 => Shopp::__('Cancel Order'),
			'mr'	 => Shopp::__('Mark Refunded'),
			'pr'	 => Shopp::__('Process Refund'),
			'dnc'	 => Shopp::__('Do Not Cancel'),
			'ro'	 => Shopp::__('Refund Order'),
			'cancel' => Shopp::__('Cancel'),
			'rr'	 => Shopp::__('Reason for refund'),
			'rc'	 => Shopp::__('Reason for cancellation'),
			'mc'	 => Shopp::__('Mark Cancelled'),
			'stg'	 => Shopp::__('Send to gateway')
		));

		shopp_enqueue_script('address');
		shopp_custom_script('address', 'var regions = ' . json_encode(ShoppLookup::country_zones()) . ';');

		do_action('shopp_order_management_scripts');
	}

    /**
     * Specify operation handlers
     *
     * @since 1.5
     *
     * @return array List of operation handler method names
     **/
	public function ops() {
		return array(
			'remove_item',
			'save_item',
			'save_totals',
		);
	}

    /**
     * Handler for removing a line item from the order
     *
     * @since 1.5
     *
     * @return void
     **/
	public function remove_item() {

		if ( ! $this->form('rmvline') ) return;

		$Purchase = new ShoppPurchase($this->form('id'));
		if ( ! $Purchase->exists() ) return;

		$lineid = (int)$this->form('rmvline');
		if ( isset($Purchase->purchased[ $lineid ]) ) {
			$Purchase->purchased[ $lineid ]->delete();
			unset($Purchase->purchased[ $lineid ]);
		}

		$Cart = new ShoppCart();

		$taxcountry = $Purchase->country;
		$taxstate = $Purchase->state;
		if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
			$taxcountry = $Purchase->shipcountry;
			$taxstate = $Purchase->shipstate;
		}
		ShoppOrder()->Tax->location($taxcountry, $taxstate);

		foreach ( $Purchase->purchased as &$Purchased )
			$Cart->additem($Purchased->quantity, new ShoppCartItem($Purchased));

		$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

		$Purchase->total = $Cart->total();
		$Purchase->subtotal = $Cart->total('order');
		$Purchase->discount = $Cart->total('discount');
		$Purchase->tax = $Cart->total('tax');
		$Purchase->freight = $Cart->total('shipping');
		$Purchase->save();

		$Purchase->load_purchased();

		$this->notice(Shopp::__('Item removed from the order.'));

	}

    /**
     * Handler for saving changes to a line item on the order
     *
     * @since 1.5
     *
     * @return void
     **/
	public function save_item() {

		if ( false === $this->form('save-item') || false === $lineid = $this->form('lineid') ) return;

		$Purchase = new ShoppPurchase($this->request('id'));
		if ( ! $Purchase->exists() ) return;

		$new = ( '' === $lineid );
		$name = $this->form('itemname');
		if ( $this->form('product') ) {
			list($productid, $priceid) = explode('-', $this->form('product'));
			$Product = new ShoppProduct($productid);
			$Price = new ShoppPrice($priceid);
			$name = $Product->name;
			if ( Shopp::__('Price & Delivery') != $Price->label )
				$name .= ": $Price->label";
		}

		// Create a cart representation of the order to recalculate order totals
		$Cart = new ShoppCart();

		$taxcountry = $Purchase->country;
		$taxstate = $Purchase->state;
		if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
			$taxcountry = $Purchase->shipcountry;
			$taxstate = $Purchase->shipstate;
		}
		ShoppOrder()->Tax->location($taxcountry, $taxstate);

		if ( $new ) {
			$NewLineItem = new ShoppPurchased();
			$NewLineItem->purchase = $Purchase->id;
			$Purchase->purchased[] = $NewLineItem;
		}

		foreach ( $Purchase->purchased as &$Purchased ) {
			$CartItem = new ShoppCartItem($Purchased);

			if ( $Purchased->id == $lineid || ( $new && empty($Purchased->id) ) ) {

				if ( ! empty( $_POST['product']) ) {
					list($CartItem->product, $CartItem->priceline) = explode('-', $this->form('product'));
				} elseif ( ! empty($_POST['id']) ) {
					list($CartItem->product, $CartItem->priceline) = explode('-', $this->form('id'));
				}

				$CartItem->name = $name;
				$CartItem->unitprice = Shopp::floatval($this->form('unitprice'));
				$Cart->additem((int)$this->form('quantity'), $CartItem);
				$CartItem = $Cart->get($CartItem->fingerprint());

				$Purchased->name	  = $CartItem->name;
				$Purchased->product   = $CartItem->product;
				$Purchased->price	 = $CartItem->priceline;
				$Purchased->quantity  = $CartItem->quantity;
				$Purchased->unitprice = $CartItem->unitprice;
				$Purchased->total	 = $CartItem->total;

				$Purchased->save();

			} else $Cart->additem($CartItem->quantity, $CartItem);

			$this->notice(Shopp::__('Updates saved.'));

		}

		$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

		$Purchase->total = $Cart->total();
		$Purchase->subtotal = $Cart->total('order');
		$Purchase->discount = $Cart->total('discount');
		$Purchase->tax = $Cart->total('tax');
		$Purchase->freight = $Cart->total('shipping');
		$Purchase->save();
		$Purchase->load_purchased();

	}

    /**
     * Handler for saving changes to order totals
     *
     * @since 1.5
     *
     * @return void
     **/
	public function save_totals() {
		if ( ! $this->form('save-totals') ) return;

		$Purchase = new ShoppPurchase($this->request('id'));
		if ( ! $Purchase->exists() ) return;

		$totals = array();
		if ( $this->form('totals') )
			$totals = $this->form('totals');

		$total = 0;
		foreach ( $totals as $property => $fields ) {
			if ( empty($fields) ) continue;

			if ( count($fields) > 1 )
				$this->tallyfields($Purchase, $property, $fields);
			else $Purchase->$property = Shopp::floatval($fields[0]);

			$total += ('discount' == $property ? $Purchase->$property * -1 : $Purchase->$property );
		}

		$Purchase->total = $Purchase->subtotal + $total;
		$Purchase->save();
	}

    /**
     * Helper for tallying up multi-line tax, shipping, discounts and fees
     *
     * @since 1.5
     * @param ShoppPurchase $Purchase The order we're working on
     * @param string $property The property getting tallied up
     * @param array $fields The list of fields to tally
     * @return void
     **/
	protected function tallyfields ( &$Purchase, $property, array $fields ) {
		$objects = array(
			'tax' => 'OrderAmountTax',
			'shipping' => 'OrderAmountShipping',
			'discount' => 'OrderAmountDiscount'
		);

		$methods = array(
			'fee' => 'fees',
			'tax' => 'taxes',
			'shipping' => 'shipfees',
			'discount' => 'discounts'
		);

		if ( isset($fields['labels']) ) {
			$labels = $fields['labels'];
			unset($fields['labels']);
			if ( count($fields) > count($labels) )
				array_pop($fields); // Remove the total

			$fields = array_combine($labels, $fields);
		}

		$fields = array_map(array('Shopp', 'floatval'), $fields);

		$entries = array();
		$OrderAmountObject = isset($objects[ $property ]) ? $objects[ $property ] : 'OrderAmountFee';
		foreach ( $fields as $label => $amount )
			$entries[] = new $OrderAmountObject(array('id' => count($entries) + 1, 'label' => $label, 'amount' => $amount));

		$tally = isset($methods[ $property ]) ? $methods[ $property ] : 'fees';
		$Purchase->$tally($entries);

		$sum = array_sum($fields);
		if ( $sum > 0 )
			$Purchase->$property = $sum;
	}

    /**
     * Provide a list of shipping carriers
     *
     * @since 1.5
     *
     * @return array A list of shipping carriers
     **/
	public function shipcarriers() {

		$shipcarriers = ShoppLookup::shipcarriers(); // The full list of available shipping carriers
		$selectcarriers = (array) shopp_setting('shipping_carriers'); // The store-preferred shipping carriers

		$default = get_user_meta(get_current_user_id(), 'shopp_shipping_carrier', true); // User's last used carrier

		// Add "No Tracking" option
		$shipcarriers['NOTRACKING'] = json_decode('{"name":"' . Shopp::__('No tracking') . '","trackpattern":false,"areas":"*"}');
		$selectcarriers[] = 'NOTRACKING';

		$carriers = array();
		$serviceareas = array('*', ShoppBaseLocale()->country());
		foreach ( $shipcarriers as $code => $carrier ) {
			if ( ! empty($selectcarriers) && ! in_array($code, $selectcarriers) ) continue;
			if ( ! in_array($carrier->areas, $serviceareas) ) continue;
			$carriers[ $code ] = array($carrier->name, $carrier->trackpattern);
		}

		$first = isset($carriers[ $default ]) ? $default : 'NOTRACKING';
		return array($first => $carriers[ $first ]) + $carriers;
	}

	/**
	 * Provides overall layout for the order manager interface
	 *
	 * Makes use of WordPress postboxes to generate panels (box) content
	 * containers that are customizable with drag & drop, collapsable, and
	 * can be toggled to be hidden or visible in the interface.
	 *
	 * @return
	 **/
	public function layout () {

		$Purchase = ShoppPurchase();

		$default = array('' => '&nbsp;');
		$Purchase->_countries = array_merge($default, ShoppLookup::countries());

		$regions = ShoppLookup::country_zones();
		$Purchase->_billing_states = array_merge($default, (array)$regions[ $Purchase->country ]);
		$Purchase->_shipping_states = array_merge($default, (array)$regions[ $Purchase->shipcountry ]);

		ShoppUI::register_column_headers($this->id, apply_filters('shopp_order_manager_columns', array(
			'items' => Shopp::__('Items'),
			'qty'   => Shopp::__('Quantity'),
			'price' => Shopp::__('Price'),
			'total' => Shopp::__('Total')
		)));

		$references = array('Purchase' => $Purchase);

		new ShoppAdminOrderContactBox($this, 'side', 'core', $references);
		new ShoppAdminOrderBillingAddressBox($this, 'side', 'high', $references);

		if ( ! empty($Purchase->shipaddress) )
			new ShoppAdminOrderShippingAddressBox($this, 'side', 'core', $references);


		new ShoppAdminOrderManageBox($this, 'normal', 'core', $references);

		if ( isset($Purchase->data) && '' != join('', (array)$Purchase->data) || apply_filters('shopp_orderui_show_orderdata', false) )
			new ShoppAdminOrderDataBox($this, 'normal', 'core', $references);

		if ( count($Purchase->events) > 0 )
			new ShoppAdminOrderHistoryBox($this, 'normal', 'core', $references);

		new ShoppAdminOrderNotesBox($this, 'normal', 'core', $references);

		do_action('shopp_order_manager_layout');

	}

	/**
	 * Interface processor for the order manager
	 *
	 * @return void
	 **/
	public function screen () {

		if ( ! current_user_can('shopp_orders') )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		$Purchase = ShoppPurchase();
		$Purchase->Customer = new ShoppCustomer($Purchase->customer);

		$Purchase->taxes();
		$Purchase->discounts();

		include $this->ui('order.php');
	}

} // class ShoppScreenOrderManager


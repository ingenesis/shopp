<?php
/**
 * Purchase.php
 *
 * Order invoice logging
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, March, 2008
 * @package shopp
 * @subpackage purchase
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPurchase extends ShoppDatabaseObject {

	static $table = 'purchase';

	public $purchased = array();
	public $columns = array();
	public $message = array();
	public $data = array();
	public $discounts = array();

	// Balances
	public $invoiced = false;	// Amount invoiced
	public $authorized = false;	// Amount authorized
	public $captured = false;	// Amount captured
	public $refunded = false;	// Amount refunded
	public $voided = false;		// Order cancelled prior to capture
	public $balance = 0;		// Current balance

	public $inventory = false;
	public $downloads = false;
	public $shippable = false;
	public $shipped = false;
	public $stocked = false;

	public function __construct ( $id = false, $key = false ) {

		$this->init(self::$table);
		if ( ! $id ) return true;
		$this->load($id, $key);
		if ( ! empty($this->shipmethod) ) $this->shippable = true;

	}

	public function load_events () {
		$this->events = OrderEvent::events($this->id);
		$this->invoiced = false;
		$this->authorized = false;
		$this->captured = false;
		$this->refunded = false;
		$this->voided = false;
		$this->balance = 0;

		foreach ( $this->events as $Event ) {
			switch ( $Event->name ) {
				case 'invoiced': $this->invoiced += $Event->amount; break;
				case 'authed': $this->authorized += $Event->amount; break;
				case 'captured': $this->captured += $Event->amount; break;
				case 'refunded': $this->refunded += $Event->amount; break;
				case 'voided': $Event->amount = $this->balance; $this->voided += $Event->amount; $Event->credit = true; break;
				case 'shipped': $this->shipped = true; $this->shipevent = $Event; break;
			}
			if ( isset($Event->transactional) ) {
				$this->txnevent = $Event;

				if ( $Event->credit ) $this->balance -= $Event->amount;
				elseif ( $Event->debit ) $this->balance += $Event->amount;
			}
		}
	}

	/**
	 * Load the purchased records for this order
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean True if successfully loaded, false otherwise
	 **/
	public function load_purchased () {
		if ( empty($this->id) ) return false;

		$table = ShoppDatabaseObject::tablename(Purchased::$table);
		$price = ShoppDatabaseObject::tablename(ShoppPrice::$table);

		$this->purchased = sDB::query(
			"SELECT pd.*,pr.inventory FROM $table AS pd LEFT JOIN $price AS pr ON pr.id=pd.price WHERE pd.purchase=$this->id ORDER BY pd.id ASC",
			'array',
			array($this, 'purchases')
		);

		return true;
	}

	/**
	 * Callback for loading purchased objects from a record set
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records A reference to the loaded record set
	 * @param object $record A reference to the individual record to process
	 * @return void
	 **/
	public function purchases ( array &$records, &$record ) {

		$ShoppPurchased = 'ShoppPurchased';
		if ( ! class_exists($ShoppPurchased) ) return;

		$Purchased = new $ShoppPurchased();
		$Purchased->populate($record);

		$index = $record->id;

		if ( ! empty($Purchased->download) ) $this->downloads = true;
		if ( 'Shipped' == $Purchased->type ) $this->shippable = true;
		if ( isset($record->inventory) ) {
			$Purchased->inventory = Shopp::str_true($record->inventory);
			if ( $Purchased->inventory ) $this->stocked = true;
		}

		if ( is_string($Purchased->data) )
			$Purchased->data = maybe_unserialize($Purchased->data);

		if ( 'yes' == $Purchased->addons ) { // Map addons and set flags

			$Purchased->addons = new ObjectMeta($Purchased->id, 'purchased', 'addon');

			if ( ! $Purchased->addons )
				$Purchased->addons = new ObjectMeta();

			foreach ( $Purchased->addons->meta as $Addon ) {
				$addon = $Addon->value;
				if ( 'Download' == $addon->type ) $this->downloads = true;
				if ( 'Shipped' == $addon->type ) $this->shippable = true;
				if ( Shopp::str_true($addon->inventory) ) $this->stocked = true;
			}

		}

		$records[ $index ] = $Purchased;
	}

	/**
	 * Set or load the discounts applied to this order
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppDiscounts $ShoppDiscounts The ShoppDiscounts object from the order to add to this purchase
	 * @return array List of discounts applied
	 **/
	public function discounts ( ShoppDiscounts $ShoppDiscounts = null ) {
		if ( empty($this->id) ) return false;

		if ( isset($ShoppDiscounts) ) { // Save the given discounts
			$discounts = array();
			foreach ( $ShoppDiscounts as $Discount )
				$discounts[ $Discount->id() ] = new ShoppPurchaseDiscount($Discount);

			shopp_set_meta($this->id, 'purchase', 'discounts', $discounts);
			$this->discounts = $discounts;
			ShoppPromo::used(array_keys($discounts));
		}

		if ( empty($this->discounts) ) $this->discounts = shopp_meta($this->id, 'purchase', 'discounts');
		return $this->discounts;
	}

	/**
	 * Set or load the taxes applied to this order
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $OrderTaxes A list of OrderAmountItemTax entries
	 * @return array The list of taxes applied to the order
	 **/
	public function taxes ( array $OrderTaxes = array() ) {
		if ( empty($this->id) ) return false;

		if ( ! empty($OrderTaxes) ) { // Save the given taxes
			$taxes = array();
			foreach ( (array) $OrderTaxes as $Tax )
				$taxes[ $Tax->id() ] = new ShoppPurchaseTax($Tax);
			shopp_set_meta($this->id, 'purchase', 'taxes', $taxes);
			$this->taxes = $taxes;
		}

		if ( empty($this->taxes) ) $this->taxes = shopp_meta($this->id, 'purchase', 'taxes');
		return $this->taxes;
	}

	/**
	 * Creates or retrieves temporary account registration information for the order
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array A list of the registration objects
	 **/
	public function registration ( $args = false ) {
		if ( empty($this->id) ) return false;
		$args = func_get_args();

		if ( count($args) == 1 && 'cleanup' == reset($args) )
			return shopp_rmv_meta($this->id, 'purchase', 'registration');

		$registration = array();
		$objectmap = array(
			'ShoppCustomer' => 'Customer',
			'BillingAddress' => 'Billing',
			'ShippingAddress' => 'Shipping'
		);

		foreach ( $args as $Object ) {
			$class = is_object($Object) ? get_class($Object) : '';

			$Record = new StdClass;
			$properties = array_keys($Object->_datatypes);
			foreach ( $properties as $property )
				if ( isset($Object->$property) ) $Record->$property = $Object->$property;

			if ( 'ShoppCustomer' == $class ) { // hash the password before storage
				$Object->hashpass();
				if ( isset($Object->passhash) )
					$Record->passhash = $Object->passhash;
				$Record->loginname = $Object->loginname;
			}

			if ( isset($objectmap[ $class ]) )
				$registration[ $objectmap[ $class ] ] = $Record;
		}

		if ( ! empty($registration) )
			shopp_set_meta($this->id, 'purchase', 'registration', $registration);

		$meta = shopp_meta($this->id, 'purchase', 'registration');

		return $meta;
	}

	/**
	 * Detects when the purchase has been voided
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @return boolean
	 **/
	public function isrefunded () {
		if ( empty($this->events) ) $this->load_events();
		return ( $this->refunded == $this->captured );
	}

	/**
	 * Detects when the purchase has been voided
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @return boolean
	 **/
	public function isvoid () {
		if ( empty($this->events) ) $this->load_events();
		return ($this->voided > 0 && $this->voided >= $this->invoiced);
	}

	/**
	 * Detects when the purchase has been paid in full
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @return boolean
	 **/
	public function ispaid () {
		if ( empty($this->events) ) $this->load_events();
		$legacy = ( 0 == $this->captured && in_array($this->txnstatus, array('CHARGED','captured')) );
		return ($this->captured == $this->total || $legacy);
	}

	public function capturable () {
		if ( empty($this->events) ) $this->load_events();
		if ( ! $this->authorized ) return 0.0;
		return ($this->authorized - (float)$this->captured);
	}

	public function refundable () {
		if ( empty($this->events) ) $this->load_events();
		if (!$this->captured) return 0.0;
		return ($this->captured - (float)$this->refunded);
	}

	public function gateway () {
		$Gateways = Shopp::object()->Gateways;

		$processor = $this->gateway;
		if ( 'ShoppFreeOrder' == $processor ) return $Gateways->freeorder;

		$Gateway = $Gateways->get($processor);

		if ( ! $Gateway ) {
			foreach ( $Gateways->active as $Gateway )
				if ( $processor == $Gateway->name )
					return $Gateway;
		} else return $Gateway;

		return false;
	}

	public static function unstock ( UnstockOrderEvent $Event ) {
		if ( empty($Event->order) ) return shopp_debug('Can not unstock. No event order.');

		$Purchase = $Event->order();
		if ( ! $Purchase->stocked ) return true; // no inventory in purchase

		$prices = array();
		$allocated = array();
		foreach ( $Purchase->purchased as $Purchased ) {
			if ( is_a($Purchased->addons, 'ObjectMeta') && ! empty($Purchased->addons->meta) ) {
				foreach ( $Purchased->addons->meta as $index => $Addon ) {
					if ( ! Shopp::str_true($Addon->value->inventory) ) continue;

					$allocated[ $Addon->value->id ] = new PurchaseStockAllocation(array(
						'purchased' => $Purchased->id,
						'addon' => $index,
						'sku' => $Addon->value->sku,
						'price' => $Addon->value->id,
						'quantity' => $Purchased->quantity
					));

					$prices[ $Addon->value->id ] = array(
						$Purchased->name,
						isset($prices[ $Addon->value->id ]) ? $prices[ $Addon->value->id ][1] + $Purchased->quantity : $Purchased->quantity
					);
				}
			}

			if ( ! Shopp::str_true($Purchased->inventory) ) continue;

			$allocated[ $Purchased->id ] = new PurchaseStockAllocation(array(
				'purchased' => $Purchased->id,
				'sku' => $Purchased->sku,
				'price' => $Purchased->price,
				'quantity' => $Purchased->quantity
			));

			$prices[ $Purchased->price ] = array(
				$Purchased->name,
				isset($prices[ $Purchased->price ]) ? $prices[ $Purchased->price ][1] + $Purchased->quantity : $Purchased->quantity
			);
		}
		if ( empty($allocated) ) return;

		$pricetable = ShoppDatabaseObject::tablename(ShoppPrice::$table);
		$lowlevel = shopp_setting('lowstock_level');
		foreach ( $prices as $price => $data ) {
			list($productname, $qty) = $data;
			sDB::query("UPDATE $pricetable SET stock=(stock-" . (int)$qty . ") WHERE id='$price' LIMIT 1");
			$inventory = sDB::query("SELECT label, stock, stocked FROM $pricetable WHERE id='$price' LIMIT 1", 'auto');

			$product = "$productname, $inventory->label";
			if ( 0 == $inventory->stock ) {
				shopp_add_error(Shopp::__('%s is now out-of-stock!', $product), SHOPP_STOCK_ERR);
			} elseif ( ($inventory->stock / $inventory->stocked * 100) <= $lowlevel ) {
				shopp_add_error(Shopp::__('%s has low stock levels and should be re-ordered soon.', $product), SHOPP_STOCK_ERR);
			}
		}

		$Event->unstocked($allocated);

		// If out of stock products should be hidden from catalog,
		// go ahead and invalidate the cache after unstock
		if ( ! Shopp::str_true( shopp_setting('outofstock_catalog') ) )
			Shopp::cache_invalidate();
	}

	/**
	 * Determines if the order has a given order event
	 *
	 * @since 1.3.5
	 *
	 * @param string $event The event to check for
	 * @return boolean True if the event occurred
	 **/
	public function did ( $event ) {

		if ( empty($this->events) ) $this->load_events(); // Load events
		foreach ( (array) $this->events as $entry )
			if ( $event == $entry->name ) return true;

		return false;

	}

	/**
	 * Updates a purchase order with transaction information from order events
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param OrderEvent $Event The order event passed by the action hook
	 * @return void
	 **/
	public static function event ( $Event ) {

		$Purchase = $Event->order();

		if ( ! $Purchase ) {
			shopp_debug('Cannot update. No event order.');
			return;
		}

		// Transaction status is the same as the event, no update needed
		if ( $Purchase->txnstatus == $Event->name ) {
			shopp_debug('Transaction status (' . $Purchase->txnstatus . ') for purchase order #' . $Purchase->id . ' is the same as the new event, no update necessary.');
			return;
		}

		$status = false;
		$txnid = false;

		// Set transaction status from event name
		$txnstatus = $Event->name;

		if ( 'refunded' == $txnstatus ) { // Determine if this is fully refunded (previous refunds + this refund amount)
			if ( empty($Purchase->events) ) $Purchase->load_events(); // Not refunded if less than captured, so don't update txnstatus
			if ( $Purchase->refunded + $Event->amount < $Purchase->captured ) $txnstatus = false;
		}
		if ( 'voided' == $txnstatus ) { // Determine if the transaction has been cancelled
			if ( empty($Purchase->events) ) $Purchase->load_events();
			if ( $Purchase->captured ) $txnstatus = false; // If previously captured, don't mark voided
		}
		if ( 'shipped' == $txnstatus ) $txnstatus = false; // 'shipped' is not a valid txnstatus

		// Set order workflow status from status label mapping
		$labels = (array)shopp_setting('order_status');
		$events = (array)shopp_setting('order_states');
		$key = array_search($Event->name, $events);
		if ( false !== $key && isset($labels[ $key ]) ) $status = (int)$key;

		// Set the transaction ID if available
		if ( isset($Event->txnid) && !empty($Event->txnid) ) $txnid = $Event->txnid;

		$updates = compact('txnstatus', 'txnid', 'status');
		$updates = array_filter($updates);

		$data = sDB::escape($updates);
		$data = array_map( function ( $value ) {
			return "'$value'";
        }, $data);
		$dataset = ShoppDatabaseObject::dataset($data);

		if ( ! empty($dataset) ) {
			$table = ShoppDatabaseObject::tablename(self::$table);
			$query = "UPDATE $table SET $dataset WHERE id='$Event->order' LIMIT 1";
			sDB::query($query);
		}

		$Purchase->updates($updates);

		return;
	}

	/**
	 * Send email notifications on order events
	 *
	 * @author Marc Neuhaus, Jonathan Davis
	 * @since 1.2
	 *
	 * @param $Event OrderEvent $event The OrderEvent object passed by the hook
	 * @return void
	 */
	public static function notifications ( $Event ) {

		$Purchase = $Event->order();
		if ( ! $Purchase ) return; // Only handle notifications for events relating to this order

		$defaults = array('note');

		$Purchase->message['event'] = $Event;
		if ( ! empty($Event->note) ) $Purchase->message['note'] = &$Event->note;

		// Generic filter hook for specifying global email messages
		$messages = apply_filters('shopp_order_event_emails', array(
			'customer' => array(
				"$Purchase->firstname $Purchase->lastname",		// Recipient name
				$Purchase->email,							// Recipient email address
				Shopp::__('Your order with %s has been updated', shopp_setting('business_name')), // Subject
				"email-$Event->name.php"),				// Template
			'merchant' => array(
				'',										// Recipient name
				shopp_setting('merchant_email'),		// Recipient email address
				Shopp::__('Order #%s: %s', 'Shopp', $Purchase->id, $Event->label()), // Subject
				"email-merchant-$Event->name.php")		// Template
		), $Event);

		// Event-specific hook for event specific email messages
		$messages = apply_filters('shopp_' . $Event->name . '_order_event_emails', $messages, $Event);

		foreach ( $messages as $name => $message ) {
			list($addressee, $email, $subject, $template) = $message;

			$templates = array($template);

			// Add note kind-specific template support
			if ( isset($Event->kind) && ! empty($Event->kind) ) {
				list($basename, $php) = explode('.', $template);
				$notekind = "$basename-$Event->kind.$php";
				array_unshift($templates, $notekind);
			}

			// Always send messages to customers for default event types (note, etc)
			if ( in_array($Event->name, $defaults) && 'customer' == $name )
				$templates[] = 'email.php';

			$file = Shopp::locate_template( $templates );
			// Send email if the specific template is available
			// and if an email has not already been sent to the recipient
			if ( ! empty($file) && ! in_array($email, $Event->_emails) ) {

				if ( $Purchase->email($addressee, $email, $subject, array($template)) )
					$Event->_emails[] = $email;

			}
		}

	}

	/**
	 * Separate class of order notifications for "successful" orders
	 *
	 * A successful order is conditionally based on the type of order being processed. An order
	 * is successful on the "authed" order event for shipped orders (any order that has any shipped
	 * items including mixed-type orders) or, it will fire on the "captured" order event
	 * for non-tangible orders (downloads, donation, virtual, etc)
	 *
	 * Keeping this behavior behind the success markers (authed/captured) prevents email
	 * servers from getting overloaded if the server is getting hit with bot-triggered order
	 * attempts.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public static function success ( $Purchase ) {

		$templates = array('email-order.php', 'order.php', 'order.html');

		// Generic filter hook for specifying global email messages
		$messages = apply_filters('shopp_order_success_emails', array(
			'customer' => array(
				"$Purchase->firstname $Purchase->lastname",									// Recipient name
				$Purchase->email,														// Recipient email address
				Shopp::__('Your order with %s', shopp_setting('business_name')),	// Subject
				$templates),														// Templates
			'merchant' => array(
				shopp_setting('business_name'),										// Recipient name
				shopp_setting('merchant_email'),									// Recipient email address
				Shopp::__('New Order - %s', $Purchase->id),								// Subject
				array_merge(array('email-merchant-order.php'), $templates))			// Templates
		), $Purchase);

		// Remove merchant notification if disabled in receipt copy setting
		if ( ! shopp_setting_enabled('receipt_copy') ) unset($messages['merchant']);

		foreach ( $messages as $message ) {
			list($addressee, $email, $subject, $templates) = $message;

			// Send email if the specific template is available
			// and if an email has not already been sent to the recipient
			$Purchase->email($addressee, $email, $subject, $templates);
		}

	}

	public function email ( $addressee, $address, $subject, array $templates = array() ) {
		global $is_IIS;

		shopp_debug("ShoppPurchase::email(): $addressee,$address,$subject,"._object_r($templates));

		// Build the e-mail message data
		$email['from'] = Shopp::email_from( shopp_setting('merchant_email'), shopp_setting('business_name') );
		if ($is_IIS) $email['to'] = Shopp::email_to( $address );
		else $email['to'] = Shopp::email_to( $address, $addressee );
		$email['subject'] = $subject;
		$email['receipt'] = $this->receipt();
		$email['url'] = get_bloginfo('url');
		$email['sitename'] = get_bloginfo('name');
		$email['orderid'] = $this->id;

		$email = apply_filters('shopp_email_receipt_data', $email);
		$email = apply_filters('shopp_purchase_email_message', $email);
		$this->message = array_merge($this->message,$email);

		// Load and process the template file
		$defaults = array('email.php','order.php','order.html');
		$emails = array_merge((array)$templates,$defaults);

		$template = Shopp::locate_template($emails);

		if ( ! file_exists($template) ) {
			shopp_add_error(Shopp::__('A purchase notification could not be sent because the template for it does not exist.'), SHOPP_ADMIN_ERR);
			return false;
		}

		// Send the email
		if (Shopp::email($template, $this->message)) {
			shopp_debug('A purchase notification was sent to: ' . $this->message['to']);
			return true;
		}

		shopp_debug('A purchase notification FAILED to be sent to: ' . $this->message['to']);
		return false;
	}

	/**
	 * Copy properties from a source object to this Purchase object
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param Object $Object The object to copy properties from
	 * @param string $prefix A prefix to use for matching source object properties
	 * @param array $ignores A list of properties to ignore
	 * @return void
	 **/
	public function copydata ( $Object, $prefix = '', $ignores = false ) {
		if ( ! is_array($ignores) || $ignores === false ) 
			$ignores = array('_datatypes', '_table', '_key', '_lists', 'id', 'created', 'modified');
		else
			$ignores = array_merge(array('_datatypes', '_table', '_key', '_lists', 'id', 'created', 'modified'), $ignores);

		foreach( get_object_vars($Object) as $property => $value ) {
			$property = $prefix . $property;
			if ( property_exists($this, $property) && ! in_array($property, $ignores) )
				$this->{$property} = $value;
		}
	}

	public static function exportcolumns () {
		$prefix = "o.";
		return array(
			$prefix.'id'	        => Shopp::__('Order ID'),
			$prefix.'ip'	        => Shopp::__('Customer\'s IP Address'),
			$prefix.'firstname'	    => Shopp::__('Customer\'s First Name'),
			$prefix.'lastname'	    => Shopp::__('Customer\'s Last Name'),
			$prefix.'email'	        => Shopp::__('Customer\'s Email Address'),
			$prefix.'phone'	        => Shopp::__('Customer\'s Phone Number'),
			$prefix.'company'	    => Shopp::__('Customer\'s Company'),
			$prefix.'card'	        => Shopp::__('Credit Card Number'),
			$prefix.'cardtype'	    => Shopp::__('Credit Card Type'),
			$prefix.'cardexpires'	=> Shopp::__('Credit Card Expiration Date'),
			$prefix.'cardholder'	=> Shopp::__('Credit Card Holder\'s Name'),
			$prefix.'address'	    => Shopp::__('Billing Street Address'),
			$prefix.'xaddress'	    => Shopp::__('Billing Street Address 2'),
			$prefix.'city'	        => Shopp::__('Billing City'),
			$prefix.'state'	        => Shopp::__('Billing State/Province'),
			$prefix.'country'	    => Shopp::__('Billing Country'),
			$prefix.'postcode'	    => Shopp::__('Billing Postal Code'),
			$prefix.'shipname'	    => Shopp::__('Shipping Name'),
			$prefix.'shipaddress'	=> Shopp::__('Shipping Street Address'),
			$prefix.'shipxaddress'	=> Shopp::__('Shipping Street Address 2'),
			$prefix.'shipcity'	    => Shopp::__('Shipping City'),
			$prefix.'shipstate'     => Shopp::__('Shipping State/Province'),
			$prefix.'shipcountry'	=> Shopp::__('Shipping Country'),
			$prefix.'shippostcode'	=> Shopp::__('Shipping Postal Code'),
			$prefix.'shipmethod'	=> Shopp::__('Shipping Method'),
			$prefix.'shipoption'	=> Shopp::__('Shipping Option'),
			'discounts.value'	    => Shopp::__('Discounts Applied'),
			$prefix.'subtotal'	    => Shopp::__('Order Subtotal'),
			$prefix.'discount'	    => Shopp::__('Order Discount'),
			$prefix.'freight'	    => Shopp::__('Order Shipping Fees'),
			$prefix.'tax'	        => Shopp::__('Order Taxes'),
			$prefix.'total'	        => Shopp::__('Order Total'),
			$prefix.'fees'	        => Shopp::__('Transaction Fees'),
			$prefix.'txnid'	        => Shopp::__('Transaction ID'),
			$prefix.'txnstatus'	    => Shopp::__('Transaction Status'),
			$prefix.'gateway'	    => Shopp::__('Payment Gateway'),
			$prefix.'paymethod'	    => Shopp::__('Payment Method'),
			$prefix.'status'	    => Shopp::__('Order Status'),
			$prefix.'data'	        => Shopp::__('Order Data'),
			$prefix.'created'	    => Shopp::__('Order Date'),
			$prefix.'modified'	    => Shopp::__('Order Last Updated')
		);
	}

	// Display a sales receipt
	public function receipt ( $template = 'receipt.php' ) {
		if ( empty($this->purchased) ) $this->load_purchased();

		if ( 'receipt.php' == $template ) { // If not overridden
			$context = ShoppStorefront::intemplate(); // Set receipt context
			if ( ! empty($context) )
				$template = "receipt-$context";
		}

		ob_start();
		locate_shopp_template(array($template, 'receipt.php'), true);
		$content = ob_get_clean();

		return apply_filters('shopp_order_receipt', $content);
	}

	public function save () {
		$new = false;
		if ( empty($this->id) ) $new = true;

		if ( ! empty($this->card) )
			$this->card = PayCard::truncate($this->card);

		parent::save();
	}

	public function delete () {
		$table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		sDB::query("DELETE FROM $table WHERE parent='$this->id' AND context='purchase'");
		parent::delete();
	}

	public function delete_purchased () {
		if ( empty($this->purchased) ) $this->load_purchased();
		foreach ( $this->purchased as $item ) {
			$Purchased = new ShoppPurchased();
			$Purchased->populate($item);
			$Purchased->delete();
		}
	}

	public function lock () {
		if ( empty($this->id) ) return false;

 		$locked = 0;
 		for ( $attempts = 0; $attempts < 3 && $locked == 0; $attempts++ ) {
 			$locked = sDB::query("SELECT GET_LOCK('$this->id'," . SHOPP_TXNLOCK_TIMEOUT . ") AS locked", 'auto', 'col', 'locked');
			if ( 0 == $locked ) sleep(1); // Wait a sec before trying again
 		}

 		if ( 1 == $locked ) return true;

		shopp_debug("Purchase lock for order #$this->id failed. Could not achieve a lock.");
		return false;
 	}

 	/**
 	 * Unlocks a transaction lock
 	 *
 	 * @author Jonathan Davis
 	 * @since 1.2.1
 	 *
 	 * @return boolean
 	 **/
	public function unlock () {
		if ( empty($this->id) ) return false;
 		$unlocked = sDB::query("SELECT RELEASE_LOCK('$this->id') as unlocked", 'auto', 'col', 'unlocked');
 		return ( 1 == $unlocked );
 	}

} // end Purchase class

class PurchaseStockAllocation extends AutoObjectFramework {

	public $purchased = 0; // purchased id
	public $addon = false;	// index of addons
	public $sku = '';		// sku
	public $price = 0; 	// price id
	public $quantity = 0;	// quantity

}

class PurchasesExport {

	public $sitename = "";
	public $headings = false;
	public $data = false;
	public $defined = array();
	public $purchase_cols = array();
	public $purchased_cols = array();
	public $selected = array();
	public $recordstart = true;
	public $content_type = "text/plain";
	public $extension = "txt";
	public $date_format = 'F j, Y';
	public $time_format = 'g:i:s a';
	public $set = 0;
	public $limit = 1024;

	public function __construct () {

		$this->purchase_cols = ShoppPurchase::exportcolumns();
		$this->purchased_cols = ShoppPurchased::exportcolumns();
		$this->defined = array_merge($this->purchase_cols,$this->purchased_cols);

		$this->sitename = get_bloginfo('name');
		$this->headings = (shopp_setting('purchaselog_headers') == 'on');
		$this->selected = shopp_setting('purchaselog_columns');
		$this->date_format = get_option('date_format');
		$this->time_format = get_option('time_format');
		shopp_set_setting('purchaselog_lastexport', current_time('timestamp'));
	}

	public function query ( $request = array() ) {
		$defaults = array(
			'status' => false,
			's' => false,
			'start' => false,
			'end' => false
		);
		$request = array_merge($defaults, $_GET);
		extract($request);


		if ( ! empty($start) ) {
			list($month, $day, $year) = explode('/', $start);
			$start = mktime(0, 0, 0, $month, $day, $year);
		}

		if ( ! empty($end) ) {
			list($month, $day, $year) = explode('/', $end);
			$end = mktime(23, 59, 59, $month, $day, $year);
		}

		$where = array();
		$joins = array();
		if ( ! empty($status) || $status === '0' ) $where[] = "status='" . sDB::escape($status) . "'";
		if ( ! empty($s) ) {
			$s = stripslashes($s);
			$search = array();
			if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $s, $props, PREG_SET_ORDER) > 0 ) {
				foreach ( $props as $query ) {
					$keyword = sDB::escape( ! empty($query[2]) ? $query[2] : $query[3] );
					switch(strtolower($query[1])) {
						case "txn": 		$search[] = "txnid='$keyword'"; break;
						case "company":		$search[] = "company LIKE '%$keyword%'"; break;
						case "gateway":		$search[] = "gateway LIKE '%$keyword%'"; break;
						case "cardtype":	$search[] = "cardtype LIKE '%$keyword%'"; break;
						case "address": 	$search[] = "(address LIKE '%$keyword%' OR xaddress='%$keyword%')"; break;
						case "city": 		$search[] = "city LIKE '%$keyword%'"; break;
						case "province":
						case "state": 		$search[] = "state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode":	$search[] = "postcode='$keyword'"; break;
						case "country": 	$search[] = "country='$keyword'"; break;
						case "promo":
						case "discount":
											$meta_table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
											$joins[ $meta_table ] = "INNER JOIN $meta_table AS discounts ON discounts.parent = o.id AND discounts.name='discounts' AND discounts.context='purchase'";
											$search[] = "discounts.value LIKE '%$keyword%'"; break;
					}
				}
				if ( empty($search) ) $search[] = "(o.id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%$s%')";
				$where[] = "(".join(' OR ',$search).")";
			} elseif ( strpos($s,'@') !== false ) {
				 $where[] = "email='".sDB::escape($s)."'";
			} else $where[] = "(o.id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%".sDB::escape($s)."%')";
		}
		if ( ! empty($start) && !empty($end) ) $where[] = '(UNIX_TIMESTAMP(o.created) >= '.$start.' AND UNIX_TIMESTAMP(o.created) <= '.$end.')';
		if ( ! empty($customer) ) $where[] = "customer=".intval($customer);
		$where = ! empty($where) ? "WHERE ".join(' AND ', $where) : '';

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$purchasedtable = ShoppDatabaseObject::tablename(ShoppPurchased::$table);
		$offset = ($this->set * $this->limit);

		$c = 0; $columns = array(); $purchasedcols = false; $discountcols = false; $addoncols = false;
		foreach ( $this->selected as $column ) {
			$columns[] = "$column AS col".$c++;
			if ( false !== strpos($column, 'p.') ) $purchasedcols = true;
			if ( false !== strpos($column, 'discounts') ) $discountcols = true;
			if ( false !== strpos($column, 'addons') ) $addoncols = true;
		}
		if ( $purchasedcols ) $FROM = "FROM $purchasedtable AS p INNER JOIN $purchasetable AS o ON o.id=p.purchase";
		else $FROM = "FROM $purchasetable AS o";

		if ( $discountcols ) {
			$meta_table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
			$joins[ $meta_table ] = "LEFT JOIN $meta_table AS discounts ON discounts.parent = o.id AND discounts.name='discounts' AND discounts.context='purchase'";
		}

		if ( $addoncols ) {
			$meta_table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
			$joins[ $meta_table.'_2' ] = "LEFT JOIN $meta_table AS addons ON addons.parent = p.id AND addons.type='addon' AND addons.context='purchased' AND addons.name = addons.name"; // addons.name eval forces use of lookup INDEX
		}

		$joins = join(' ', $joins);

		$query = "SELECT ".join(",",$columns)." $FROM $joins $where ORDER BY o.created ASC LIMIT $offset,$this->limit";
		$this->data = sDB::query($query, 'array');
	}

	// Implement for exporting all the data
	public function output () {
		if ( ! $this->data ) $this->query();
		if ( ! $this->data ) Shopp::redirect( add_query_arg( array_merge($_GET,array('src' => null) ), admin_url('admin.php') ) );

		header("Content-type: $this->content_type; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"$this->sitename Purchase Log.$this->extension\"");
		header("Content-Description: Delivered by " . ShoppVersion::agent());
		header("Cache-Control: maxage=1");
		header("Pragma: public");

		$this->begin();
		if ( $this->headings ) $this->heading();
		$this->records();
		$this->end();
	}

	public function begin() {}

	public function heading () {
		foreach ($this->selected as $name)
			$this->export($this->defined[$name]);
		$this->record();
	}

	public function records () {
		while (!empty($this->data)) {
			foreach ($this->data as $record) {
				foreach(get_object_vars($record) as $column)
					$this->export($this->parse($column));
				$this->record();
			}
			$this->set++;
			$this->query();
		}
	}

	public function parse ( $column ) {
		if ( sDB::serialized($column) ) {
			$list = unserialize($column);
			$column = "";
			foreach ( $list as $name => $value ) {
				if ( is_a($value, 'ShoppPurchaseDiscount') ) {
					$Discount = $value;
					$column .= ( empty($column) ? "" : ";" ) . trim("$Discount->id:$Discount->name (" . money($Discount->discount) . ") $Discount->code");
				} else $column .= ( empty($column) ? "" : ";" ) . "$name:$value";
			}
		}
		return $this->escape($column);
	}

	public function end() {}

	// Implement for exporting a single value
	public function export ( $value ) {
		echo ($this->recordstart?"":"\t").$value;
		$this->recordstart = false;
	}

	public function record () {
		echo "\n";
		$this->recordstart = true;
	}

	public static function settings () {
		/** Placeholder **/
	}

	public function escape ($value) {
		return $value;
	}

}

class PurchasesTabExport extends PurchasesExport {

	public function __construct () {
		parent::__construct();
		$this->output();
	}

	public function escape ($value) {
		$value = str_replace(array("\n", "\r"), ' ', $value); // No newlines
		if ( false !== strpos($value, "\t") && false === strpos($value, '"') )	// Quote tabs
			$value = '"' . $value . '"';
		return $value;
	}

}

class PurchasesCSVExport extends PurchasesExport {

	public function __construct () {
		parent::__construct();
		$this->content_type = "text/csv";
		$this->extension = "csv";
		$this->output();
	}

	public function export ($value) {
		echo ( $this->recordstart ? "" : "," ) . $value;
		$this->recordstart = false;
	}

	public function escape ($value) {
		$value = str_replace('"', '""', $value);
		if ( preg_match('/^\s|[,"\n\r]|\s$/',$value) )
			$value = '"'.$value.'"';
		return $value;
	}

}

class PurchasesIIFExport extends PurchasesExport {

	public function __construct () {
		parent::__construct();
		$this->content_type = "application/qbooks";
		$this->extension = "iif";
		$account = shopp_setting('purchaselog_iifaccount');
		if (empty($account)) $account = "Merchant Account";
		$this->selected = array(
			"'\nTRNS'",
			"DATE_FORMAT(o.created,'\"%m/%d/%Y\"')",
			"'\"$account\"'",
			"CONCAT('\"',o.firstname,' ',o.lastname,'\"')",
			"'\"Shopp Payment Received\"'",
			"o.total-o.fees",
			"''",
			"'\nSPL'",
			"DATE_FORMAT(o.created,'\"%m/%d/%Y\"')",
			"'\"Other Income\"'",
			"CONCAT('\"',o.firstname,' ',o.lastname,'\"')",
			"o.total*-1",
			"'\nSPL'",
			"DATE_FORMAT(o.created,'\"%m/%d/%Y\"')",
			"'\"Other Expenses\"'",
			"'Fee'",
			"o.fees",
			"''",
			"'\nENDTRNS'"
		);
		$this->output();
	}

	public function begin () {
		echo "!TRNS\tDATE\tACCNT\tNAME\tCLASS\tAMOUNT\tMEMO\n!SPL\tDATE\tACCNT\tNAME\tAMOUNT\tMEMO\n!ENDTRNS";
	}

	public function export ($value) {
		echo (substr($value,0,1) != "\n")?"\t".$value:$value;
	}

	public function record () { }

	public static function settings () { ?>
		<div id="iif-settings" class="hidden">
			<input type="text" id="iif-account" name="settings[purchaselog_iifaccount]" value="<?php echo shopp_setting('purchaselog_iifaccount'); ?>" size="30"/><br />
			<label for="iif-account"><small><?php Shopp::_e('QuickBooks account name for transactions'); ?></small></label>
		</div>
		<script type="text/javascript">
		/* <![CDATA[ */
		jQuery(document).ready( function($) {
			$('#purchaselog-format').change(function () {
				if ($(this).val() == "iif") {
					$('#export-columns').hide();
					$('#iif-settings').show();
					$('#iif-account').focus();
				} else {
					$('#export-columns').show();
					$('#iif-settings').hide();
				}
			}).change();
		});
		/* ]]> */
		</script>
		<?php
	}
}

// Attach the notification system to order events
add_action('shopp_order_event', array('ShoppPurchase', 'notifications'));
add_action('shopp_order_notifications', array('ShoppPurchase', 'success'));

// Automatically update the orders from order events
$updates = array('invoiced', 'authed', 'captured', 'shipped', 'refunded', 'voided');
foreach ( $updates as $event ) // Scheduled before default actions so updates are reflected in later actions
	add_action( 'shopp_' . $event . '_order_event', array('ShoppPurchase', 'event'), 5 );

// Handle unstock event
add_action('shopp_unstock_order_event', array('ShoppPurchase', 'unstock'));

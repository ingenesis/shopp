<?php
/**
 * Purchase class
 * Order invoice logging
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

require('Purchased.php');

class Purchase extends DatabaseObject {
	static $table = "purchase";

	var $purchased = array();
	var $columns = array();
	var $message = array();
	var $data = array();

	// Balances
	var $invoiced = false;		// Amount invoiced
	var $authorized = false;	// Amount authorized
	var $captured = false;		// Amount captured
	var $refunded = false;		// Amount refunded
	var $voided = false;		// Order cancelled prior to capture
	var $balance = 0;			// Current balance

	var $downloads = false;
	var $shipable = false;
	var $shipped = false;
	var $stocked = false;

	function __construct ($id=false,$key=false) {

		$this->init(self::$table);
		if (!$id) return true;
		$this->load($id,$key);
		if (!empty($this->shipmethod)) $this->shipable = true;
		if (!empty($this->id)) $this->listeners();
	}

	function listeners () {
		// Attach the notification system to order events
		add_action( 'shopp_order_event', array($this, 'notifications') );
		add_action( 'shopp_order_notifications', array($this, 'success') );

	}

	function load_purchased () {

		$table = DatabaseObject::tablename(Purchased::$table);
		$meta = DatabaseObject::tablename(MetaObject::$table);
		$price = DatabaseObject::tablename(Price::$table);
		$Purchased = new Purchased();
		if (empty($this->id)) return false;
		$this->purchased = DB::query("SELECT pd.*,pr.inventory FROM $table AS pd LEFT JOIN $price AS pr ON pr.id=pd.price WHERE pd.purchase=$this->id", 'array', array($Purchased, 'loader') );
		foreach ( $this->purchased as &$purchase) {
			if (!empty($purchase->download)) $this->downloads = true;
			if ('Shipped' == $purchase->type) $this->shipable = true;
			if ( isset($purchase->inventory) && Shopp::str_true($purchase->inventory) ) $this->stocked = true;
			if ( is_string($purchase->data) ) $purchase->data = unserialize($purchase->data);
			if ('yes' == $purchase->addons) {
				$purchase->addons = new ObjectMeta($purchase->id,'purchased','addon');
				if (!$purchase->addons) $purchase->addons = new ObjectMeta();
				foreach ( $purchase->addons->meta as $Addon ) {
					$addon = $Addon->value;
					if ( 'Download' == $addon->type ) $this->downloads = true;
					if ( 'Shipped' == $addon->type ) $this->shipable = true;
					if ( Shopp::str_true($addon->inventory) ) $this->stocked = true;
				}
			}
		}

		return true;
	}

	function load_events () {
		$this->events = OrderEvent::instance()->events($this->id);
		$this->invoiced = false;
		$this->authorized = false;
		$this->captured = false;
		$this->refunded = false;
		$this->voided = false;
		$this->balance = 0;

		foreach ($this->events as $Event) {
			switch ($Event->name) {
				case 'invoiced': $this->invoiced += $Event->amount; break;
				case 'authed': $this->authorized += $Event->amount; break;
				case 'captured': $this->captured += $Event->amount; break;
				case 'refunded': $this->refunded += $Event->amount; break;
				case 'voided': $Event->amount = $this->balance; $this->voided += $Event->amount; $Event->credit = true; break;
				case 'shipped': $this->shipped = true; $this->shipevent = $Event; break;
			}
			if (isset($Event->transactional)) {
				$this->txnevent = $Event;

				if ($Event->credit) $this->balance -= $Event->amount;
				elseif ($Event->debit) $this->balance += $Event->amount;
			}
		}

		// Legacy support - @todo Remove in 1.3
		if (isset($this->txnstatus) && !empty($this->txnstatus)) {
			switch ($this->txnstatus) {
				case 'CHARGED': $this->authorized = $this->captured = true; break;
				case 'VOID': $this->voided = true; $this->balance = 0; break;
			}
		}

	}

	/**
	 * Detects when the purchase has been voided
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @return boolean
	 **/
	function isrefunded () {
		if (empty($this->events)) $this->load_events();
		return ($this->refunded == $this->captured);
	}

	/**
	 * Detects when the purchase has been voided
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @return boolean
	 **/
	function isvoid () {
		if (empty($this->events)) $this->load_events();
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
	function ispaid () {
		if ( empty($this->events) ) $this->load_events();

		$legacypaid = ( 0 == $this->captured && 'CHARGED' == $Purchase->txnstatus );

		return ($this->captured == $this->total || $legacypaid);
	}

	static function unstock ( UnstockOrderEvent $Event ) {
		if (empty($Event->order)) return new ShoppError('Can not unstock. No event order.',false,SHOPP_DEBUG_ERR);

		// If global purchase context is not a loaded Purchase object, load the purchase associated with the order
		$Purchase = ShoppPurchase();
		if (!isset($Purchase->id) || empty($Purchase->id) || $Event->order != $Purchase->id) {
			$Purchase = new Purchase($Event->order);
		}

		if ( empty($Purchase->purchased) ) $Purchase->load_purchased();
		if ( ! $Purchase->stocked ) return true; // no inventory in purchase

		$prices = array();
		$allocated = array();
		foreach ( $Purchase->purchased as $Purchased ) {
			if ( is_a($Purchased->addons, 'ObjectMeta') && ! empty($Purchased->addons->meta) ) {
				foreach ( $Purchased->addons->meta as $index => $Addon ) {
					if ( ! Shopp::str_true($Addon->value->inventory) ) continue;

					$allocated[$Addon->value->id] = new PurchaseStockAllocation(array(
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

			$allocated[$Purchased->id] = new PurchaseStockAllocation(array(
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

		$pricetable = DatabaseObject::tablename(Price::$table);
		$lowlevel = shopp_setting('lowstock_level');
		foreach ( $prices as $price => $data ) {
			list($productname, $qty) = $data;
			DB::query("UPDATE $pricetable SET stock=(stock-" . (int)$qty . ") WHERE id='$price' LIMIT 1");
			$inventory = DB::query("SELECT label, stock, stocked FROM $pricetable WHERE id='$price' LIMIT 1", 'auto');

			$product = "$productname, $inventory->label";
			if ( 0 == $inventory->stock ) {
				new ShoppError(sprintf(__('%s is now out-of-stock!', 'Shopp'), $product), 'outofstock_warning', SHOPP_STOCK_ERR);
			} elseif ( ($inventory->stock / $inventory->stocked * 100) <= $lowlevel ) {
				new ShoppError(sprintf(__('%s has low stock levels and should be re-ordered soon.', 'Shopp'), $product), 'lowstock_warning', SHOPP_STOCK_ERR);
			}
		}

		$Event->unstocked($allocated);
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
	static function status_event ($Event) {
		if (empty($Event->order)) return new ShoppError('Cannot update. No event order.',false,SHOPP_DEBUG_ERR);

		// If global purchase context is not a loaded Purchase object, load the purchase associated with the order
		$Purchase = ShoppPurchase();
		if (!isset($Purchase->id) || empty($Purchase->id)) $Purchase = new Purchase($Event->order);

		// Loaded Purchase does not match the one for the event
		if ($Purchase->id != $Event->order) return new ShoppError('Cannot update. Loaded purchase does not match the purchase for the event.',false,SHOPP_DEBUG_ERR);

		// Transaction status is the same as the event, no update needed
		if ($Purchase->txnstatus == $Event->name)
			return new ShoppError('Transaction status ('.$Purchase->txnstatus.') for purchase order #'.$Purchase->id.' is the same as the new event, no update necessary.',false,SHOPP_DEBUG_ERR);

		$status = false;
		$txnid = false;

		// Set transaction status from event name
		$txnstatus = $Event->name;

		if ( 'refunded' == $txnstatus) { // Determine if this is fully refunded (previous refunds + this refund amount)
			if (empty($Purchase->events)) $Purchase->load_events(); // Not refunded if less than captured, so don't update txnstatus
			if ($Purchase->refunded+$Event->amount < $Purchase->captured) $txnstatus = false;
		}
		if ( 'voided' == $txnstatus) { // Determine if the transaction has been cancelled
			if (empty($Purchase->events)) $Purchase->load_events();
			if ($Purchase->captured) $txnstatus = false; // If previously captured, don't mark voided
		}

		if ( 'shipped' == $txnstatus) $txnstatus = false; // 'shipped' is not a valid txnstatus

		// Set order workflow status from status label mapping
		$labels = (array)shopp_setting('order_status');
		$events = (array)shopp_setting('order_states');
		$key = array_search($Event->name,$events);
		if (false !== $key && isset($labels[$key])) $status = (int)$key;

		// Set the transaction ID if available
		if (isset($Event->txnid) && !empty($Event->txnid)) $txnid = $Event->txnid;

		$updates = compact('txnstatus','txnid','status');
		$updates = array_filter($updates);

		$data = DB::escape($updates);
		$data = array_map(create_function('$value','return "\'$value\'";'),$data);
		$dataset = DatabaseObject::dataset($data);

		if ( ! empty($dataset) ) {
			$table = DatabaseObject::tablename(self::$table);
			$query = "UPDATE $table SET $dataset WHERE id='$Event->order' LIMIT 1";
			DB::query($query);
		}

		$Purchase->updates($updates);
	}

	function capturable () {
		if (!$this->authorized) return 0.0;
		return ($this->authorized - (float)$this->captured);
	}

	function refundable () {
		if (!$this->captured) return 0.0;
		return ($this->captured - (float)$this->refunded);
	}

	function gateway () {
		$Shopp = Shopp::object();

		$processor = $this->gateway;
		if ('FreeOrder' == $processor) return $Shopp->Gateways->freeorder;
		if (isset($Shopp->Gateways->active[$processor])) return $Shopp->Gateways->active[$processor];
		else {
			foreach ($Shopp->Gateways->active as $Gateway) {
				if ($processor != $Gateway->name) continue;
				return $Gateway;
				break;
			}
		}
		return false;
	}

	/**
	 * Send email notifications on order events
	 *
	 * @author Marc Neuhaus, Jonathan Davis
	 * @since 1.2
	 *
	 * @param OrderEvent $event The OrderEvent object passed by the hook
	 * @return void
	 **/
	function notifications ($Event) {
		if ($Event->order != $this->id) return; // Only handle notifications for events relating to this order

		$defaults = array('note');

		$this->message['event'] = $Event;
		if (!empty($Event->note)) $this->message['note'] = &$Event->note;

		// Generic filter hook for specifying global email messages
		$messages = apply_filters('shopp_order_event_emails',array(
			'customer' => array(
				"$this->firstname $this->lastname",		// Recipient name
				$this->email,							// Recipient email address
				sprintf(__('Your order with %s has been updated', 'Shopp'), shopp_setting('business_name')), // Subject
				"email-$Event->name.php"),				// Template
			'merchant' => array(
				'',										// Recipient name
				shopp_setting('merchant_email'),		// Recipient email address
				sprintf(__('Order #%s: %s', 'Shopp'), $this->id, $Event->label()), // Subject
				"email-merchant-$Event->name.php")		// Template
		));

		// Event-specific hook for event specific email messages
		$messages = apply_filters('shopp_'.$Event->name.'_order_event_emails',$messages);

		foreach ($messages as $name => $message) {
			list($addressee,$email,$subject,$template) = $message;

			$templates = array($template);

			// Add note kind-specific template support
			if (isset($Event->kind) && !empty($Event->kind)) {
				list($basename,$php) = explode('.',$template);
				$notekind = "$basename-$Event->kind.$php";
				array_unshift($templates,$notekind);
			}

			// Always send messages to customers for default event types (note, etc)
			if (in_array($Event->name,$defaults) && 'customer' == $name)
				$templates[] = 'email.php';

			$file = locate_shopp_template($templates);
			// Send email if the specific template is available
			// and if an email has not already been sent to the recipient
			if ( ! empty($file) && ! in_array($email,$Event->_emails) ) {

				if ( $this->email($addressee,$email,$subject,array($template)) )
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
	function success ($Purchase) {
		if ($Purchase->id != $this->id) return; // Only handle notifications for events relating to this order

		// Set the global purchase object to enable the Theme API
		ShoppPurchase($Purchase);

		$templates = array('email-order.php','order.php','order.html');

		// Generic filter hook for specifying global email messages
		$messages = apply_filters('shopp_order_success_emails',array(
			'customer' => array(
				"$this->firstname $this->lastname",										// Recipient name
				$this->email,															// Recipient email address
				sprintf(__('Your order with %s', 'Shopp'), shopp_setting('business_name')), // Subject
				array('email-order.php','order.php','order.html')),						// Templates
			'merchant' => array(
				shopp_setting('business_name')	,										// Recipient name
				shopp_setting('merchant_email'),										// Recipient email address
				sprintf(__('New Order - %s', 'Shopp'), $this->id),					 	// Subject
				array_merge(array('email-merchant-order.php'),$templates))				// Templates
		));

		// Remove merchant notification if disabled in receipt copy setting
		if (!shopp_setting_enabled('receipt_copy')) unset($messages['merchant']);

		foreach ($messages as $name => $message) {
			list($addressee,$email,$subject,$templates) = $message;

			// Send email if the specific template is available
			// and if an email has not already been sent to the recipient
			$this->email($addressee,$email,$subject,$templates);
		}

	}

	/**
	 * Deprecated
	 *
	 * @deprecated
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function notification ($addressee,$address,$subject,$template='order.php',$receipt='receipt.php') {
		$this->email($addressee,$address,$subject,array($template));
	}

	function email ($addressee,$address,$subject,$templates=array()) {
		global $Shopp,$is_IIS;

		new ShoppError("Purchase::email(): $addressee,$address,$subject,"._object_r($templates),false,SHOPP_DEBUG_ERR);

		// Build the e-mail message data
		$_ = array();
		$email['from'] = '"'.wp_specialchars_decode( shopp_setting('business_name'), ENT_QUOTES ).'"';
		if (shopp_setting('merchant_email'))
			$email['from'] .= ' <'.shopp_setting('merchant_email').'>';
		if ($is_IIS) $email['to'] = $address;
		else $email['to'] = '"'.wp_specialchars_decode( $addressee, ENT_QUOTES ).'" <'.$address.'>';
		$email['subject'] = $subject;
		$email['receipt'] = $this->receipt();
		$email['url'] = get_bloginfo('siteurl');
		$email['sitename'] = get_bloginfo('name');
		$email['orderid'] = $this->id;

		$email = apply_filters('shopp_email_receipt_data',$email);
		$email = apply_filters('shopp_purchase_email_message',$email);
		$this->message = array_merge($this->message,$email);

		// Load and process the template file
		$defaults = array('email.php','order.php','order.html');
		$emails = array_merge((array)$templates,$defaults);

		$template = locate_shopp_template($emails);

		if (!file_exists($template))
			return new ShoppError(__('A purchase notification could not be sent because the template for it does not exist.','purchase_notification_template',SHOPP_ADMIN_ERR));

		// Send the email
		if (shopp_email($template,$this->message)) {
			shopp_debug('A purchase notification was sent to: '.$this->message['to']);
			return true;
		}

		shopp_debug('A purchase notification FAILED to be sent to: '.$this->message['to']);
		return false;
	}

	function copydata ($Object,$prefix="") {
		$ignores = array("_datatypes","_table","_key","_lists","id","created","modified");
		foreach(get_object_vars($Object) as $property => $value) {
			$property = $prefix.$property;
			if (property_exists($this,$property) &&
				!in_array($property,$ignores))
				$this->{$property} = $value;
		}
	}

	function exportcolumns () {
		$prefix = "o.";
		return array(
			$prefix.'id' => __('Order ID','Shopp'),
			$prefix.'ip' => __('Customer\'s IP Address','Shopp'),
			$prefix.'firstname' => __('Customer\'s First Name','Shopp'),
			$prefix.'lastname' => __('Customer\'s Last Name','Shopp'),
			$prefix.'email' => __('Customer\'s Email Address','Shopp'),
			$prefix.'phone' => __('Customer\'s Phone Number','Shopp'),
			$prefix.'company' => __('Customer\'s Company','Shopp'),
			$prefix.'card' => __('Credit Card Number','Shopp'),
			$prefix.'cardtype' => __('Credit Card Type','Shopp'),
			$prefix.'cardexpires' => __('Credit Card Expiration Date','Shopp'),
			$prefix.'cardholder' => __('Credit Card Holder\'s Name','Shopp'),
			$prefix.'address' => __('Billing Street Address','Shopp'),
			$prefix.'xaddress' => __('Billing Street Address 2','Shopp'),
			$prefix.'city' => __('Billing City','Shopp'),
			$prefix.'state' => __('Billing State/Province','Shopp'),
			$prefix.'country' => __('Billing Country','Shopp'),
			$prefix.'postcode' => __('Billing Postal Code','Shopp'),
			$prefix.'shipname' => __('Shipping Name','Shopp'),
			$prefix.'shipaddress' => __('Shipping Street Address','Shopp'),
			$prefix.'shipxaddress' => __('Shipping Street Address 2','Shopp'),
			$prefix.'shipcity' => __('Shipping City','Shopp'),
			$prefix.'shipstate' => __('Shipping State/Province','Shopp'),
			$prefix.'shipcountry' => __('Shipping Country','Shopp'),
			$prefix.'shippostcode' => __('Shipping Postal Code','Shopp'),
			$prefix.'shipmethod' => __('Shipping Method','Shopp'),
			$prefix.'promos' => __('Promotions Applied','Shopp'),
			$prefix.'subtotal' => __('Order Subtotal','Shopp'),
			$prefix.'discount' => __('Order Discount','Shopp'),
			$prefix.'freight' => __('Order Shipping Fees','Shopp'),
			$prefix.'tax' => __('Order Taxes','Shopp'),
			$prefix.'total' => __('Order Total','Shopp'),
			$prefix.'fees' => __('Transaction Fees','Shopp'),
			$prefix.'txnid' => __('Transaction ID','Shopp'),
			$prefix.'txnstatus' => __('Transaction Status','Shopp'),
			$prefix.'gateway' => __('Payment Gateway','Shopp'),
			$prefix.'status' => __('Order Status','Shopp'),
			$prefix.'data' => __('Order Data','Shopp'),
			$prefix.'created' => __('Order Date','Shopp'),
			$prefix.'modified' => __('Order Last Updated','Shopp')
		);
	}

	// Display a sales receipt
	function receipt ($template='receipt.php') {
		if (empty($this->purchased)) $this->load_purchased();

		ob_start();
		locate_shopp_template(array($template,'receipt.php'),true);
		$content = ob_get_contents();
		ob_end_clean();

		return apply_filters('shopp_order_receipt',$content);
	}

	function save () {
		$new = false;
		if (empty($this->id)) $new = true;

		if (!empty($this->card) && strlen($this->card) > 4)
			$this->card = substr($this->card,-4);
		parent::save();

		if ($new && !empty($this->id)) $this->listeners();
	}

	function delete () {
		$table = DatabaseObject::tablename(MetaObject::$table);
		DB::query("DELETE LOW_PRIORITY FROM $table WHERE parent='$this->id' AND context='purchase'");
		parent::delete();
	}

	function delete_purchased () {
		if (empty($this->purchased)) $this->load_purchased();
		foreach ($this->purchased as $item) {
			$Purchased = new Purchased();
			$Purchased->populate($item);
			$Purchased->delete();
		}
	}


} // end Purchase class

class PurchaseStockAllocation extends AutoObjectFramework {

	var $purchased = 0; // purchased id
	var $addon = false;	// index of addons
	var $sku = '';		// sku
	var $price = 0; 	// price id
	var $quantity = 0;	// quantity

}

class PurchasesExport {
	var $sitename = "";
	var $headings = false;
	var $data = false;
	var $defined = array();
	var $purchase_cols = array();
	var $purchased_cols = array();
	var $selected = array();
	var $recordstart = true;
	var $content_type = "text/plain";
	var $extension = "txt";
	var $date_format = 'F j, Y';
	var $time_format = 'g:i:s a';
	var $set = 0;
	var $limit = 1024;

	function __construct () {
		$Shopp = Shopp::object();

		$this->purchase_cols = Purchase::exportcolumns();
		$this->purchased_cols = Purchased::exportcolumns();
		$this->defined = array_merge($this->purchase_cols,$this->purchased_cols);

		$this->sitename = get_bloginfo('name');
		$this->headings = (shopp_setting('purchaselog_headers') == "on");
		$this->selected = shopp_setting('purchaselog_columns');
		$this->date_format = get_option('date_format');
		$this->time_format = get_option('time_format');
		shopp_set_setting('purchaselog_lastexport',current_time('timestamp'));
	}

	function query ($request=array()) {
		$defaults = array(
			'status' => false,
			's' => false,
			'start' => false,
			'end' => false
		);
		$request = array_merge($defaults,$_GET);
		extract($request);


		if (!empty($start)) {
			list($month,$day,$year) = explode('/',$start);
			$start = mktime(0,0,0,$month,$day,$year);
		}

		if (!empty($end)) {
			list($month,$day,$year) = explode('/',$end);
			$end = mktime(23,59,59,$month,$day,$year);
		}

		$where = array();
		if (!empty($status) || $status === '0') $where[] = "status='".DB::escape($status)."'";
		if (!empty($s)) {
			$s = stripslashes($s);
			$search = array();
			if (preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/',$s,$props,PREG_SET_ORDER) > 0) {
				foreach ($props as $query) {
					$keyword = DB::escape( ! empty($query[2]) ? $query[2] : $query[3] );
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
					}
				}
				if (empty($search)) $search[] = "(o.id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%$s%')";
				$where[] = "(".join(' OR ',$search).")";
			} elseif (strpos($s,'@') !== false) {
				 $where[] = "email='".DB::escape($s)."'";
			} else $where[] = "(o.id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%".DB::escape($s)."%')";
		}
		if (!empty($start) && !empty($end)) $where[] = '(UNIX_TIMESTAMP(o.created) >= '.$start.' AND UNIX_TIMESTAMP(o.created) <= '.$end.')';
		if (!empty($customer)) $where[] = "customer=".intval($customer);
		$where = !empty($where) ? "WHERE ".join(' AND ',$where) : '';

		$purchasetable = DatabaseObject::tablename(Purchase::$table);
		$purchasedtable = DatabaseObject::tablename(Purchased::$table);
		$offset = ($this->set*$this->limit);

		$c = 0; $columns = array(); $purchasedcols = false;
		foreach ($this->selected as $column) {
			$columns[] = "$column AS col".$c++;
			if ( false !== strpos($column, 'p.') ) $purchasedcols = true;
		}
		if ($purchasedcols) $FROM = "FROM $purchasedtable AS p INNER JOIN $purchasetable AS o ON o.id=p.purchase";
		else $FROM = "FROM $purchasetable AS o";

		$query = "SELECT ".join(",",$columns)." $FROM $where ORDER BY o.created ASC LIMIT $offset,$this->limit";
		$this->data = DB::query($query,'array');
	}

	// Implement for exporting all the data
	function output () {
		if (!$this->data) $this->query();
		if (!$this->data) shopp_redirect(add_query_arg(array_merge($_GET,array('src' => null)),admin_url('admin.php')));

		header("Content-type: $this->content_type; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"$this->sitename Purchase Log.$this->extension\"");
		header("Content-Description: Delivered by WordPress/Shopp ".SHOPP_VERSION);
		header("Cache-Control: maxage=1");
		header("Pragma: public");

		$this->begin();
		if ($this->headings) $this->heading();
		$this->records();
		$this->end();
	}

	function begin() {}

	function heading () {
		foreach ($this->selected as $name)
			$this->export($this->defined[$name]);
		$this->record();
	}

	function records () {
		while (!empty($this->data)) {
			foreach ($this->data as $key => $record) {
				foreach(get_object_vars($record) as $column)
					$this->export($this->parse($column));
				$this->record();
			}
			$this->set++;
			$this->query();
		}
	}

	function parse ($column) {
		if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/",$column)) {
			$list = unserialize($column);
			$column = "";
			foreach ($list as $name => $value)
				$column .= (empty($column)?"":";")."$name:$value";
		}
		return $this->escape($column);
	}

	function end() {}

	// Implement for exporting a single value
	function export ($value) {
		echo ($this->recordstart?"":"\t").$value;
		$this->recordstart = false;
	}

	function record () {
		echo "\n";
		$this->recordstart = true;
	}

	function settings () {
		/** Placeholder **/
	}

	function escape ($value) {
		return $value;
	}

}

class PurchasesTabExport extends PurchasesExport {

	function __construct () {
		parent::__construct();
		$this->output();
	}

	function escape ($value) {
		$value = str_replace(array("\n", "\r"), ' ', $value); // No newlines
		if ( false !== strpos($value, "\t") && false === strpos($value,'"') )	// Quote tabs
			$value = '"' . $value . '"';
		return $value;
	}

}

class PurchasesCSVExport extends PurchasesExport {

	function __construct () {
		parent::__construct();
		$this->content_type = "text/csv";
		$this->extension = "csv";
		$this->output();
	}

	function export ($value) {
		echo ($this->recordstart?"":",").$value;
		$this->recordstart = false;
	}

	function escape ($value) {
		$value = str_replace('"','""',$value);
		if ( preg_match('/^\s|[,"\n\r]|\s$/',$value) )
			$value = '"'.$value.'"';
		return $value;
	}

}

class PurchasesXLSExport extends PurchasesExport {

	function __construct () {
		parent::__construct();
		$this->content_type = "application/vnd.ms-excel";
		$this->extension = "xls";
		$this->c = 0; $this->r = 0;
		$this->output();
	}

	function begin () {
		echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
	}

	function end () {
		echo pack("ss", 0x0A, 0x00);
	}

	function export ($value) {
		if (preg_match('/^[\d\.]+$/',$value)) {
		 	echo pack("sssss", 0x203, 14, $this->r, $this->c, 0x0);
			echo pack("d", $value);
		} else {
			$l = strlen($value);
			echo pack("ssssss", 0x204, 8+$l, $this->r, $this->c, 0x0, $l);
			echo $value;
		}
		$this->c++;
	}

	function record () {
		$this->c = 0;
		$this->r++;
	}
}

class PurchasesIIFExport extends PurchasesExport {
	function __construct () {
		$Shopp = Shopp::object();
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

	function begin () {
		echo "!TRNS\tDATE\tACCNT\tNAME\tCLASS\tAMOUNT\tMEMO\n!SPL\tDATE\tACCNT\tNAME\tAMOUNT\tMEMO\n!ENDTRNS";
	}

	function export ($value) {
		echo (substr($value,0,1) != "\n")?"\t".$value:$value;
	}

	function record () { }

	function settings () {
		$Shopp = Shopp::object();
		?>
		<div id="iif-settings" class="hidden">
			<input type="text" id="iif-account" name="settings[purchaselog_iifaccount]" value="<?php echo shopp_setting('purchaselog_iifaccount'); ?>" size="30"/><br />
			<label for="iif-account"><small><?php _e('QuickBooks account name for transactions','Shopp'); ?></small></label>
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

// Automatically update the orders from order events
$updates = array('invoiced','authed','captured','shipped','refunded','voided');
foreach ($updates as $event) // Scheduled before default actions so updates are reflected in later actions
	add_action( 'shopp_'.$event.'_order_event', array('Purchase','status_event'), 5 );

// Handle unstock event
add_action('shopp_unstock_order_event', array('Purchase','unstock'));

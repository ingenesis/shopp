<?php
/**
 * Events.php
 * Order event management
 *
 * @author Jonathan Davis
 * @version 1.9
 * @copyright Ingenesis Limited, February 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage orderevents
 **/


 /**
  * Provides a unified interface for generating and accessing system order events
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class OrderEvent extends SingletonFramework {

 	private static $instance;
 	private $handlers = array();

 	static function instance () {
 		if (!self::$instance instanceof self)
 			self::$instance = new self;
 		return self::$instance;
 	}

 	static function register ($type,$class) {
 		$Dispatch = self::instance();
 		$Dispatch->handlers[$type] = $class;
 	}

 	static function add ($order,$type,$message=array()) {
 		$Dispatch = self::instance();

 		if (!isset($Dispatch->handlers[$type]))
 			return trigger_error('OrderEvent type "'.$type.'" does not exist.',E_USER_ERROR);

 		$Event = $Dispatch->handlers[$type];
 		$message['order'] = $order;
 		$OrderEvent = new $Event($message);
 		if (!isset($OrderEvent->_exception)) return $OrderEvent;
 		return false;
 	}

 	static function events ($order) {
 		$Dispatch = self::instance();
 		$Object = new OrderEventMessage();
 		$meta = $Object->_table;
 		$query = "SELECT *
 					FROM $meta
 					WHERE context='$Object->context'
 						AND type='$Object->type'
 						AND parent='$order'
 					ORDER BY created,id";
 		return DB::query($query,'array',array($Object,'loader'),'name');
 	}

 	static function handler ($name) {
 		$Dispatch = self::instance();
 		if (isset($Dispatch->handlers[$name]))
 			return $Dispatch->handlers[$name];
 	}

 }

 /**
  * Defines the base message protocol for the Shopp Order Event subsystem.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class OrderEventMessage extends MetaObject {

 	// Mapped properties should be added (not exclude standard properties)
 	var $_addmap = true;
 	var $_map = array('order' => 'parent','amount' => 'numeral');
 	var $_xcols = array();
 	var $_emails = array();		// Registry to track emails messages are dispatched to
 	var $context = 'purchase';
 	var $type = 'event';

 	var $message = array();		// Message protocol to be defined by sub-classes

 	var $order = false;
 	var $amount = 0.0;
 	var $txnid = false;

 	function __construct ($data=false) {
 		$this->init(self::$table);
 		if (!$data) return;

 		$message = $this->msgprops();

 		if (is_int($data)) $this->load($data);

  		$this->context = 'purchase';
 		$this->type = 'event';

 		if (!is_array($data)) return;

 		/* Creating a new event */
 		$data = $this->filter($data);

 		// Ensure the data is provided
 		$missing = array_diff($this->_xcols,array_keys($data));

 		if (!empty($missing)) {
 			$params = array();
 			foreach ($missing as $key) $params[] = "'$key' [{$message[$key]}]";
 			trigger_error(sprintf('Required %s parameters missing (%s)',get_class($this),join(', ',$params)),E_USER_ERROR);
 			return $this->_exception = true;
 		}

 		// Automatically populate the object and save it
 		$this->copydata($data);
 		$this->save();

 		if (empty($this->id)) {
 			new ShoppError(sprintf('An error occured while saving a new %s',get_class($this)),false,SHOPP_DEBUG_ERR);
 			return $this->_exception = true;
 		}

 		$action = sanitize_key($this->name);

 		new ShoppError(sprintf('%s dispatched.',get_class($this)),false,SHOPP_DEBUG_ERR);

 		if (isset($this->gateway)) {
 			$gateway = sanitize_key($this->gateway);
 			do_action_ref_array('shopp_'.$gateway.'_'.$action,array($this));
 		}

 		do_action_ref_array('shopp_'.$action.'_order_event',array($this));
 		do_action_ref_array('shopp_order_event',array($this));


 	}

 	function msgprops () {
 		$message = $this->message;
 		unset($this->message);
 		if (isset($message) && !empty($message)) {
 			foreach ($message as $property => &$default) {
 				$this->$property = false;
 				$this->_xcols[] = $property;
 				$default = $this->datatype($default);
 			}
 		}
 		return $message;
 	}

 	function datatype ($var) {
 		if (is_array($var)) return 'array';
 		if (is_bool($var)) return 'boolean';
 		if (is_float($var)) return 'float';
 		if (is_int($var)) return 'integer';
 		if (is_null($var)) return 'NULL';
 		if (is_numeric($var)) return 'numeric';
 		if (is_object($var)) return 'object';
 		if (is_resource($var)) return 'resource';
 		if (is_string($var)) return 'string';
 		return 'unknown type';
 	}

 	/**
 	 * Callback for loading concrete OrderEventMesssage objects from a record set
 	 *
 	 * @author Jonathan Davis
 	 * @since 1.2
 	 *
 	 * @param array $records A reference to the loaded record set
 	 * @param object $record Result record data object
 	 * @return void
 	 **/
 	function loader (&$records,&$record,$type=false,$index='id',$collate=false) {
 		if ($type !== false && isset($record->$type) && class_exists(OrderEvent::handler($record->$type))) {
 			$OrderEventClass = OrderEvent::handler($record->$type);
 		} elseif (isset($this)) {
 			if ($index == 'id') $index = $this->_key;
 			$OrderEventClass = get_class($this);
 		}
 		$index = isset($record->$index)?$record->$index:'!NO_INDEX!';
 		$Object = new $OrderEventClass(false);
 		$Object->msgprops();
 		$Object->populate($record);
 		if (method_exists($Object,'expopulate'))
 			$Object->expopulate();

 		if ($collate) {
 			if (!isset($records[$index])) $records[$index] = array();
 			$records[$index][] = $Object;
 		} else $records[$index] = $Object;
 	}

 	function filter ($msg) {
 		return $msg;
 	}

 	/**
 	 * Report the event state label from system preferences
 	 *
 	 * @author Marc Neuhaus
 	 * @since 1.2
 	 *
 	 * @return string The label of the event
 	 **/
 	function label () {
 		if ( '' == $this->name ) return '';

 		$states = (array)shopp_setting('order_states');
 		$labels = (array)shopp_setting('order_status');

 		$index = array_search($this->name, $states);

 		if( $index > 0 && isset($labels[$index]) )
 			return $labels[$index];
 	}

 } // END class OrderEvent

 /**
  * Intermediary class to set the message as a posting CREDIT transaction
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class CreditOrderEventMessage extends OrderEventMessage {
 	var $transactional = true;	// Mark the order event as a balance adjusting event
 	var $credit = true;
 	var $debit = false;
 }

 /**
  * Intermediary class to set the message as a posting DEBIT transaction
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class DebitOrderEventMessage extends OrderEventMessage {
 	var $transactional = true;	// Mark the order event as a balance adjusting event
 	var $debit = true;
 	var $credit = false;
 }

 /**
  * Shopper initiated purchase (sales order) command message
  *
  * This message is the key message that starts the entire ordering process. As the first
  * step, this event triggers the creation of a new order in the system. In accounting terms
  * this document acts as the Sales Order, and is stored in Shopp as a Purchase record.
  *
  * In most cases, after record creation an InvoicedOrderEvent sets up the transactional
  * debit against the purchase total prior to an AuthOrderEvent
  *
  * When generating an PurchaseOrderEvent message using shopp_add_order_event() in a
  * payment gateway, it is necessary to pass a (boolean) false value as the first
  * ($order) parameter since the purchase record is created against the AuthedOrderEvent
  * message.
  *
  * Example: shopp_add_order_event(false,'purchase',array(...));
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class PurchaseOrderEvent extends OrderEventMessage {
 	var $name = 'purchase';
 	var $message = array(
 		'gateway' => ''		// Gateway (class name) to process authorization through
 	);
 }
 OrderEvent::register('purchase','PurchaseOrderEvent');

 /**
  * Invoiced transaction message
  *
  * Represents the merchant's agreement to the sales order allowing the transaction to
  * take place. Shopp then debits against the purchase total.
  *
  * In accounting terms the debit is against the merchant's account receivables, and
  * implicitly credits sales accounts indicating an amount owed to the merchant by a customer.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class InvoicedOrderEvent extends DebitOrderEventMessage {
 	var $name = 'invoiced';
 	var $message = array(
 		'gateway' => '',		// Gateway (class name) to process authorization through
 		'amount' => 0.0			// Amount invoiced for the order
 	);
 }
 OrderEvent::register('invoiced','InvoicedOrderEvent');

 /**
  * Shopper initiated authorization command message
  *
  * Triggers the gateway(s) responsible for the order to initiate a payment
  * authorization request
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class AuthOrderEvent extends OrderEventMessage {
 	var $name = 'auth';
 	var $message = array(
 		'gateway' => '',		// Gateway (class name) to process authorization through
 		'amount' => 0.0			// Amount to capture (charge)
 	);
 }
 OrderEvent::register('auth','AuthOrderEvent');

 /**
  * Payment authorization message
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class AuthedOrderEvent extends OrderEventMessage {
 	var $name = 'authed';
 	var $capture = false;
 	var $message = array(
 		'txnid' => '',			// Transaction ID
 		'amount' => 0.0,		// Gross amount authorized
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 		'paymethod' => '',		// Payment method (payment method label from payment settings)
 		'paytype' => '',		// Type of payment (check, MasterCard, etc)
 		'payid' => ''			// Payment ID (last 4 of card or check number)
 	);

 	function __construct ($data) {

 		$this->lock($data);

 		if (isset($data['capture']) && true === $data['capture'])
 			$this->capture = true;

 		parent::__construct($data);

 		$this->unlock();

 	}

 	function filter ($msg) {

 		if (empty($msg['payid'])) return $msg;
 		$paycards = Lookup::paycards();
 		foreach ($paycards as $card) { // If it looks like a payment card number, truncate it
 			if (!empty($msg['payid']) && $card->match($msg['payid']) && $msg['paytype'] == $card->name);
 				$msg['payid'] = substr($msg['payid'],-4);
 		}

 		return $msg;
 	}

 	/**
 	 * Create a lock for transaction processing
 	 *
 	 * @author Jonathan Davis
 	 * @since 1.2.1
 	 *
 	 * @return boolean
 	 **/
 	function lock ($data) {
 		if (!isset($data['order'])) return false;

 		$order = $data['order'];
 		$locked = 0;
 		for ($attempts = 0; $attempts < 3 && $locked == 0; $attempts++)
 			$locked = DB::query("SELECT GET_LOCK('$order',".SHOPP_TXNLOCK_TIMEOUT.") AS locked",'auto','col','locked');

 		if ($locked == 1) return true;

 		new ShoppError(sprintf(__('Purchase authed lock for order %s failed. Could not achieve a lock.','Shopp'),$order),'order_txn_lock',SHOPP_TRXN_ERR);
 		shopp_redirect( Shopp::shoppurl(false,'checkout',$this->security()) );

 	}

 	/**
 	 * Unlocks a transaction lock
 	 *
 	 * @author Jonathan Davis
 	 * @since 1.2.1
 	 *
 	 * @return boolean
 	 **/
 	function unlock () {
 		if (!$this->order) return false;
 		$unlocked = DB::query("SELECT RELEASE_LOCK('$this->order') as unlocked",'auto','col','unlocked');
 		return ($unlocked == 1);
 	}

 }
 OrderEvent::register('authed','AuthedOrderEvent');


 /**
  * Unstock authorization message
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class UnstockOrderEvent extends OrderEventMessage {
 	var $name = 'unstock';
 	protected $allocated = array();

 	/**
 	 * Filter the message to include allocated item data set by the Purchase handler
 	 *
 	 * @author Jonathan Davis
 	 * @since 1.2.1
 	 *
 	 * @return array The updated message
 	 **/
 	function filter ($message) {
 		$this->_xcols[] = 'allocated';
 		$message['allocated'] = false;
 		return $message;
 	}

 	/**
 	 * Get the allocated item objects
  	*
  	* @author Jonathan Davis, John Dillick
  	* @since 1.2.1
  	*
  	* @param int $id (optional) the purchased item id
  	* @return mixed if id is provided, the allocated object, else array of allocated objects
  	**/
 	function allocated ( $id = false ) {
 		if ( $id && isset($this->allocated[$id]) ) return $this->allocated[$id];
 		return $this->allocated;
 	}

 	/**
 	 * Set the allocated item objects
  	*
  	* @author Jonathan Davis, John Dillick
  	* @since 1.2.1
  	*
  	* @param array $allocated the array of allocated item objects
  	* @return boolean success
  	**/
 	function unstocked ( $allocated = array() ) {
 		if ( empty($allocated) ) return false;
 		$this->allocated = $allocated;
 		$this->save();
 		return true;
 	}
 }
 OrderEvent::register('unstock','UnstockOrderEvent');


 /**
  * Shopper initiated authorization and capture command message
  *
  * Triggers the gateway(s) responsible for the order to initiate a payment
  * authorization request with capture
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class SaleOrderEvent extends OrderEventMessage {
 	var $name = 'sale';
 	var $message = array(
 		'gateway' => '',		// Gateway (class name) to process authorization through
 		'amount' => 0.0			// Amount to capture (charge)
 	);
 }
 OrderEvent::register('sale','SaleOrderEvent');

 /**
  * Recurring billing payment message
  *
  * The rebill message is used to adjust the running balance for an order to accommodate
  * a new recurring payment event. It debits the order so the RecapturedOrderEvent
  * credit can apply against it and keep the account balanced.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class RebillOrderEvent extends DebitOrderEventMessage {
 	var $name = 'rebill';
 	var $message = array(
 		'txnid' => '',			// Transaction ID
 		'gateway' => '',		// Gateway class name (module name from @subpackage)
 		'amount' => 0.0,		// Gross amount authorized
 		'fees' => 0.0,			// Transaction fees taken by the gateway net revenue = amount-fees
 		'paymethod' => '',		// Payment method (check, MasterCard, etc)
 		'payid' => ''			// Payment ID (last 4 of card or check number)
 	);
 }
 OrderEvent::register('rebill','RebillOrderEvent');

 /**
  * Merchant initiated capture command message
  *
  * Triggers the gateway(s) responsible for the order to initiate a capture
  * request to capture the previously authorized amount.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class CaptureOrderEvent extends OrderEventMessage {
 	var $name = 'capture';
 	var $message = array(
 		'txnid' => '',			// Transaction ID of the prior AuthedOrderEvent
 		'gateway' => '',		// Gateway (class name) to process capture through
 		'amount' => 0.0,		// Amount to capture (charge)
 		'user' => 0				// User for user-initiated captures
 	);
 }
 OrderEvent::register('capture','CaptureOrderEvent');

 /**
  * Captured funds message
  *
  * This message notifies the Shopp order system that funds were successfully
  * captured by the responsible gateway. It is typically fired by the gateway
  * after receiving the payment gateway server response from a
  * CaptureOrderEvent initiated capture request.
  *
  * A CapturedOrderEvent will credit the merchant's accounts receivable cancelling the
  * debit of an AuthedOrderEvent message.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class CapturedOrderEvent extends CreditOrderEventMessage {
 	var $name = 'captured';
 	var $message = array(
 		'txnid' => '',			// Transaction ID of the CAPTURE event
 		'amount' => 0.0,		// Amount captured
 		'fees' => 0.0,			// Transaction fees taken by the gateway net revenue = amount-fees
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('captured','CapturedOrderEvent');

 /**
  * Recurring payment captured message
  *
  * A recaptured message notifies the Shopp order system that funds were successfully
  * captured by the responsible gateway in connection with a recurring billing agreement.
  *
  * A RecaptureOrderEvent is triggered by a payment gateway when it receives a
  * remote notification message from the upstream payment gateway server that a recurring
  * payment has been successfully processed.
  *
  * A RebillOrderEvent must be triggered against the Purchase record first before
  * adding the RecapturedOrderEvent so that running balance remains accurate.
  *
  * Similar to the CapturedOrderEvent, the RecapturedOrderEvent is a payment received that
  * credits the merchant's accounts receivable.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class RecapturedOrderEvent extends CreditOrderEventMessage {
 	var $name = 'recaptured';
 	var $message = array(
 		'txnorigin' => '',		// Original transaction ID (txnid of original Purchase record)
 		'txnid' => '',			// Transaction ID of the recurring payment event
 		'amount' => 0.0,		// Amount captured
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 		'balance' => 0.0,		// Balance of the billing agreement
 		'nextdate' => 0,		// Timestamp of the next scheduled payment
 		'status' => ''			// Status of the billing agreement
 	);
 }
 OrderEvent::register('recaptured','RecapturedOrderEvent');

 /**
  * Merchant initiated refund command message
  *
  * Triggers the responsible payment gateway to initiate a refund request to the
  * payment gateway server.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class RefundOrderEvent extends OrderEventMessage {
 	var $name = 'refund';
 	var $message = array(
 		'txnid' => '',
 		'gateway' => '',		// Gateway (class name) to process refund through
 		'amount' => 0.0,
 		'user' => 0,
 		'reason' => 0
 	);

 	function filter ($msg) {
 		$reasons = shopp_setting('cancel_reasons');
 		$msg['reason'] = $reasons[ $msg['reason'] ];
 		return $msg;
 	}

 }
 OrderEvent::register('refund','RefundOrderEvent');

 /**
  * Refunded amount message
  *
  * This event message indicates a successful refund that re-debits the merchant's
  * account receivables.
  *
  * This message will cause Shopp's order system to automatically add a VoidedOrderEvent
  * to apply to the order in order to keep an accurate account balance.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class RefundedOrderEvent extends DebitOrderEventMessage {
 	var $name = 'refunded';
 	var $message = array(
 		'txnid' => '',			// Transaction ID for the REFUND event
 		'amount' => 0.0,		// Amount refunded
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('refunded','RefundedOrderEvent');

 /**
  * Merchant initiated void command message
  *
  * Used to cancel an order prior to successful capture. This triggers the responsible gateway to
  * initiate a void request.
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class VoidOrderEvent extends OrderEventMessage {
 	var $name = 'void';
 	var $message = array(
 		'txnid' => 0,			// Transaction ID for the authorization
 		'gateway' => '',		// Gateway (class name) to process capture through
 		'user' => 0,			// The WP user ID processing the void
 		'reason' => 0,			// The reason code
 		'note' => 0			// The reason code
 	);

 	function filter ($msg) {
 		$reasons = shopp_setting('cancel_reasons');
 		$msg['reason'] = $reasons[ $msg['reason'] ];
 		return $msg;
 	}

 }
 OrderEvent::register('void','VoidOrderEvent');

 /**
  * Used to cancel an order through the payment gateway service
  *
  * @author John Dillick
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class AmountVoidedEvent extends CreditOrderEventMessage {
 	var $name = 'amt-voided';
 	var $message = array(
 		'amount' => 0.0		// Amount voided
 	);
 }
 OrderEvent::register('amt-voided','AmountVoidedEvent');

 /**
  * Used to cancel the balance of an order from either an Authed or Refunded event
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class VoidedOrderEvent extends CreditOrderEventMessage {
 	var $name = 'voided';
 	var $message = array(
 		'txnorigin' => '',		// Original transaction ID (txnid of original Purchase record)
 		'txnid' => '',			// Transaction ID for the VOID event
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('voided','VoidedOrderEvent');

 /**
  * Used to send a message to the customer on record for the order
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class NoteOrderEvent extends OrderEventMessage {
 	var $name = 'note';
 	var $message = array(
 		'user' => 0,			// The WP user ID of the note author
 		'note' => ''			// The message to send
 	);
 }
 OrderEvent::register('note','NoteOrderEvent');

 /**
  * A generic order event that can be used to specify a custom order event notice in the order history
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class NoticeOrderEvent extends OrderEventMessage {
 	var $name = 'notice';
 	var $message = array(
 		'user' => 0,			// The WP user ID associated with the notice
 		'kind' => '',			// Free form notice type to be used for classifying types of notices
 		'notice' => ''			// The message to log
 	);
 }
 OrderEvent::register('notice','NoticeOrderEvent');

 /**
  * Used to log a transaction review notice to the order
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class ReviewOrderEvent extends OrderEventMessage {
 	var $name = 'review';
 	var $message = array(
 		'kind' => '',			// The kind of fraud review: AVS (address verification system), CVN (card verification number), FRT (fraud review team)
 		'note' => ''			// The message to log for the order
 	);

 }
 OrderEvent::register('review','ReviewOrderEvent');

 /**
  * Failure messages
  *
  * Failure messages log transaction attempt failures which may be caused by
  * communication errors or another problem with the request (not enough funds,
  * security declines, etc)
  **/

 class AuthFailOrderEvent extends OrderEventMessage {
 	var $name = 'auth-fail';
 	var $message = array(
 		'amount' => 0.0,		// Amount to be authorized
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('auth-fail','AuthFailOrderEvent');

 class CaptureFailOrderEvent extends OrderEventMessage {
 	var $name = 'capture-fail';
 	var $message = array(
 		'amount' => 0.0,		// Amount to be captured
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('capture-fail','CaptureFailOrderEvent');

 class RecaptureFailOrderEvent extends OrderEventMessage {
 	var $name = 'recapture-fail';
 	var $message = array(
 		'amount' => 0.0,		// Amount of the recurring payment
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 		'retrydate' => 0		// Timestamp of the next attempt to recapture
 	);
 }
 OrderEvent::register('recapture-fail','RecaptureFailOrderEvent');

 class RefundFailOrderEvent extends OrderEventMessage {
 	var $name = 'refund-fail';
 	var $message = array(
 		'amount' => 0.0,		// Amount to be refunded
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('refund-fail','RefundFailOrderEvent');

 class VoidFailOrderEvent extends OrderEventMessage {
 	var $name = 'void-fail';
 	var $message = array(
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('void-fail','VoidFailOrderEvent');

 /**
  * Logs manual processing decryption events
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class DecryptOrderEvent extends OrderEventMessage {
 	var $name = 'decrypt';
 	var $message = array(
 		'user' => 0				// WordPress user id
 	);
 }
 OrderEvent::register('decrypt','DecryptOrderEvent');

 /**
  * Logs shipment events
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  **/
 class ShippedOrderEvent extends OrderEventMessage {
 	var $name = 'shipped';
 	var $message = array(
 		'tracking' => '',		// Tracking number (you know, for tracking)
 		'carrier' => '',		// Carrier ID (name, eg. UPS, USPS, FedEx)
 	);
 }
 OrderEvent::register('shipped','ShippedOrderEvent');

 /**
  * Logs download access
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  **/
 class DownloadOrderEvent extends OrderEventMessage {
 	var $name = 'download';
 	var $message = array(
 		'purchased' => 0,		// Purchased line item ID (or add-on meta record ID)
 		'download' => 0,		// Download ID (meta record)
 		'ip' => '',				// IP address of the download
 		'customer' => 0			// Authenticated customer
 	);
 }
 OrderEvent::register('download','DownloadOrderEvent');

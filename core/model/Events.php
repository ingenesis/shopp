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

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

 /**
  * Provides a unified interface for generating and accessing system order events
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class OrderEvent extends SingletonFramework {

 	private static $object;
 	private $handlers = array();

 	static function object () {
 		if ( ! self::$object instanceof self )
 			self::$object = new self;
 		return self::$object;
 	}

 	static function register ( $type, $class ) {
 		$Dispatch = self::object();
 		$Dispatch->handlers[$type] = $class;
 	}

 	static function add ( $order, $type, array $message = array() ) {
 		$Dispatch = self::object();

 		if ( ! isset($Dispatch->handlers[ $type ]) )
 			return trigger_error('OrderEvent type "' . $type . '" does not exist.', E_USER_ERROR);

 		$Event = $Dispatch->handlers[$type];
 		$message['order'] = $order;
 		$OrderEvent = new $Event($message);
 		if ( ! isset($OrderEvent->_exception) ) return $OrderEvent;
 		return false;
 	}

 	static function events ( $order ) {
 		$Dispatch = self::object();
 		$Object = new OrderEventMessage();
 		$meta = $Object->_table;
 		$query = "SELECT *
 					FROM $meta
 					WHERE context='$Object->context'
 						AND type='$Object->type'
 						AND parent='$order'
 					ORDER BY created,id";
 		return sDB::query($query, 'array', array($Object, 'loader'), 'name');
 	}

 	static function handler ( $name ) {
 		$Dispatch = self::object();
 		if ( isset($Dispatch->handlers[ $name ]) )
 			return $Dispatch->handlers[ $name ];
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
 class OrderEventMessage extends ShoppMetaObject {

 	// Mapped properties should be added (not exclude standard properties)
	public $_addmap = true;
	public $_map = array('order' => 'parent','amount' => 'numeral');
	public $_xcols = array();
	public $_emails = array();		// Registry to track emails messages are dispatched to
	public $context = 'purchase';
	public $type = 'event';

	public $message = array();		// Message protocol to be defined by sub-classes

	public $order = false;
	public $amount = 0.0;
	public $txnid = false;

	public function __construct ( $data = false ) {
 		$this->init(self::$table);
 		if ( ! $data ) return;

 		$message = $this->msgprops();

 		if ( is_int($data) ) $this->load($data);

  		$this->context = 'purchase';
 		$this->type = 'event';

 		if ( ! is_array($data) ) return;

 		/* Creating a new event */
 		$data = $this->filter($data);

 		// Ensure the data is provided
 		$missing = array_diff($this->_xcols, array_keys($data));

 		if ( ! empty($missing) ) {
 			$params = array();
 			foreach ( $missing as $key ) $params[] = "'$key' [{$message[$key]}]";
 			trigger_error(sprintf('Required %s parameters missing (%s)', get_class($this), join(', ', $params)), E_USER_ERROR);
 			return $this->_exception = true;
 		}

 		// Automatically populate the object and save it
 		$this->copydata($data);
 		$this->save();

 		if ( empty($this->id) ) {
			shopp_debug(sprintf('An error occured while saving a new %s', get_class($this)));
 			return $this->_exception = true;
 		}

 		$action = sanitize_key($this->name);

		shopp_debug(sprintf('%s dispatched.', get_class($this)));

 		if ( isset($this->gateway) ) {
 			$gateway = sanitize_key($this->gateway);
			if ( 0 === strpos($gateway, 'shopp') )
				$gateway = substr($gateway, 5);
 			do_action_ref_array('shopp_' . $gateway . '_' . $action, array($this));
 		}

 		do_action_ref_array('shopp_' . $action . '_order_event', array($this));
 		do_action_ref_array('shopp_order_event', array($this));

 	}

	public function msgprops () {
 		$message = $this->message;
 		unset($this->message);
 		if ( isset($message) && ! empty($message) ) {
 			foreach ( $message as $property => &$default ) {
 				$this->$property = false;
 				$this->_xcols[] = $property;
 				$default = $this->datatype($default);
 			}
 		}
 		return $message;
 	}

	public function datatype ( $var ) {
 		if ( is_array($var) ) return 'array';
 		if ( is_bool($var) ) return 'boolean';
 		if ( is_float($var) ) return 'float';
 		if ( is_int($var) ) return 'integer';
 		if ( is_null($var) ) return 'NULL';
 		if ( is_numeric($var) ) return 'numeric';
 		if ( is_object($var) ) return 'object';
 		if ( is_resource($var) ) return 'resource';
 		if ( is_string($var) ) return 'string';
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
	public function loader ( array &$records, &$record, $type = false, $index = 'id', $collate = false ) {
 		if ( $type !== false && isset($record->$type) && class_exists(OrderEvent::handler($record->$type)) ) {
 			$OrderEventClass = OrderEvent::handler($record->$type);
 		} elseif ( isset($this) ) {
 			if ( 'id' == $index ) $index = $this->_key;
 			$OrderEventClass = get_class($this);
 		}
 		$index = isset($record->$index) ? $record->$index : '!NO_INDEX!';
 		$Object = new $OrderEventClass(false);
 		$Object->msgprops();
 		$Object->populate($record);
 		if (method_exists($Object, 'expopulate'))
 			$Object->expopulate();

 		if ( $collate ) {
 			if ( ! isset($records[ $index ])) $records[ $index ] = array();
 			$records[ $index ][] = $Object;
 		} else $records[ $index ] = $Object;
 	}

	public function filter ( $msg ) {
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
	public function label () {
 		if ( '' == $this->name ) return '';

 		$states = (array)shopp_setting('order_states');
 		$labels = (array)shopp_setting('order_status');

 		$index = array_search($this->name, $states);

 		if ( $index > 0 && isset($labels[ $index ]) )
 			return $labels[ $index ];
 	}

	public function order () {
		if ( empty($this->order) ) return false;

		// If global purchase context is not a loaded Purchase object, load the purchase associated with the order
		$Purchase = ShoppPurchase();
		if ( ! isset($Purchase->id) || empty($Purchase->id) || $this->order != $Purchase->id )
			$Purchase = ShoppPurchase( new ShoppPurchase($this->order) );

		if ( ! isset($Purchase->id) || empty($Purchase->id) ) return false;

		if ( empty($Purchase->purchased) ) $Purchase->load_purchased();

		return $Purchase;
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
	public $transactional = true;	// Mark the order event as a balance adjusting event
	public $credit = true;
	public $debit = false;
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
	public $transactional = true;	// Mark the order event as a balance adjusting event
	public $debit = true;
	public $credit = false;
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
  * When generating an PurchaseOrderEvent message using shopp_add_order_event(), it is
  * necessary to pass a (boolean) false value as the first ($order) parameter since
  * the purchase record is created with the PurchaseOrderEvent message.
  *
  * Example: shopp_add_order_event(false,'purchase',array(...));
  *
  * @author Jonathan Davis
  * @since 1.2
  * @package shopp
  * @subpackage orderevent
  **/
 class PurchaseOrderEvent extends OrderEventMessage {
	public $name = 'purchase';
	public $message = array(
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
	public $name = 'invoiced';
	public $message = array(
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
	public $name = 'auth';
	public $message = array(
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
	public $name = 'authed';
	public $capture = false;
	public $message = array(
 		'txnid' => '',			// Transaction ID
 		'amount' => 0.0,		// Gross amount authorized
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 		'paymethod' => '',		// Payment method (payment method label from payment settings)
 		'paytype' => '',		// Type of payment (check, MasterCard, etc)
 		'payid' => ''			// Payment ID (last 4 of card or check number)
 	);

	public function __construct ( $data ) {

 		$this->lock($data);

 		if ( isset($data['capture']) && true === $data['capture'] )
 			$this->capture = true;

 		parent::__construct($data);

 		$this->unlock();

 	}

	public function filter ( $msg ) {

 		if ( empty($msg['payid']) ) return $msg;

		$msg['payid'] = PayCard::truncate($msg['payid']);

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
	public function lock ( $data ) {
 		if ( ! isset($data['order']) ) return false;

 		$order = $data['order'];
 		$locked = 0;
 		for ( $attempts = 0; $attempts < 3 && $locked == 0; $attempts++ ) {
 			$locked = sDB::query("SELECT GET_LOCK('$order'," . SHOPP_TXNLOCK_TIMEOUT . ") AS locked", 'auto', 'col', 'locked');
			if ( 0 == $locked ) sleep(1); // Wait a sec before trying again
 		}

 		if ( 1 == $locked ) return true;

		shopp_debug("Purchase authed lock for order #$order failed. Could not achieve a lock.");
 		Shopp::redirect( Shopp::url( false, 'thanks', ShoppOrder()->security() ) );
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
 		if ( ! $this->order ) return false;
 		$unlocked = sDB::query("SELECT RELEASE_LOCK('$this->order') as unlocked", 'auto', 'col', 'unlocked');
 		return ( 1 == $unlocked );
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
	public $name = 'unstock';
 	protected $allocated = array();

 	/**
 	 * Filter the message to include allocated item data set by the Purchase handler
 	 *
 	 * @author Jonathan Davis
 	 * @since 1.2.1
 	 *
 	 * @return array The updated message
 	 **/
	public function filter ($message) {
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
	public function allocated ( $id = false ) {
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
	public function unstocked ( $allocated = array() ) {
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
	public $name = 'sale';
	public $message = array(
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
	public $name = 'rebill';
	public $message = array(
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
	public $name = 'capture';
	public $message = array(
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
	public $name = 'captured';
	public $message = array(
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
	public $name = 'recaptured';
	public $message = array(
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
	public $name = 'refund';
	public $message = array(
 		'txnid' => '',
 		'gateway' => '',		// Gateway (class name) to process refund through
 		'amount' => 0.0,
 		'user' => 0,
 		'reason' => 0
 	);

	public function filter ($msg) {
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
	public $name = 'refunded';
	public $message = array(
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
	public $name = 'void';
	public $message = array(
 		'txnid' => 0,			// Transaction ID for the authorization
 		'gateway' => '',		// Gateway (class name) to process capture through
 		'user' => 0,			// The WP user ID processing the void
 		'reason' => 0,			// The reason code
 		'note' => 0			// The reason code
 	);

	public function filter ($msg) {
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
	public $name = 'amt-voided';
	public $message = array(
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
	public $name = 'voided';
	public $message = array(
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
	public $name = 'note';
	public $message = array(
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
	public $name = 'notice';
	public $message = array(
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
	public $name = 'review';
	public $message = array(
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
	public $name = 'auth-fail';
	public $message = array(
 		'amount' => 0.0,		// Amount to be authorized
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('auth-fail','AuthFailOrderEvent');

 class CaptureFailOrderEvent extends OrderEventMessage {
	public $name = 'capture-fail';
	public $message = array(
 		'amount' => 0.0,		// Amount to be captured
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('capture-fail','CaptureFailOrderEvent');

 class RecaptureFailOrderEvent extends OrderEventMessage {
	public $name = 'recapture-fail';
	public $message = array(
 		'amount' => 0.0,		// Amount of the recurring payment
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => '',		// Gateway handler name (module name from @subpackage)
 		'retrydate' => 0		// Timestamp of the next attempt to recapture
 	);
 }
 OrderEvent::register('recapture-fail','RecaptureFailOrderEvent');

 class RefundFailOrderEvent extends OrderEventMessage {
	public $name = 'refund-fail';
	public $message = array(
 		'amount' => 0.0,		// Amount to be refunded
 		'error' => '',			// Error code (if provided)
 		'message' => '',		// Error message reported by the gateway
 		'gateway' => ''			// Gateway handler name (module name from @subpackage)
 	);
 }
 OrderEvent::register('refund-fail','RefundFailOrderEvent');

 class VoidFailOrderEvent extends OrderEventMessage {
	public $name = 'void-fail';
	public $message = array(
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
	public $name = 'decrypt';
	public $message = array(
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
	public $name = 'shipped';
	public $message = array(
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
	public $name = 'download';
	public $message = array(
 		'purchased' => 0,		// Purchased line item ID (or add-on meta record ID)
 		'download' => 0,		// Download ID (meta record)
 		'ip' => '',				// IP address of the download
 		'customer' => 0			// Authenticated customer
 	);
 }
 OrderEvent::register('download','DownloadOrderEvent');

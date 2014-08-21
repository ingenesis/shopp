<?php
/**
 * Totals.php
 *
 * Order totals calculator
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, February 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @version 1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * OrderTotals
 *
 * Manages order total registers
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1.0
 **/
class OrderTotals extends ListFramework {

	const TOTAL = 'total';
	protected $register = array( self::TOTAL => null );	// Registry of "register" entries

	protected $checks   = array();	// Track changes in the column registers

	public function __construct () {
		$this->add('total', new OrderTotal( array('amount' => 0.0) ));
	}

	/**
	 * Add a new register entry
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param OrderTotalAmount $Entry A new OrderTotalAmount class entry object
	 * @return void
	 **/
	public function register ( OrderTotalAmount $Entry ) {
		$register = $Entry->register($this);

		if ( ! isset($this->register[ $register ]) ) $this->register[ $register ] = array();
		if ( ! isset($this->register[ $register ][ $Entry->id() ]) )
			$this->register[ $register ][ $Entry->id() ] = $Entry;
		else $this->register[ $register ][ $Entry->id() ]->update($Entry);

		$this->total($register);
	}

	/**
	 * Get a specific register OrderAmount class entry
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The register to find the entry in
	 * @param string $id The entry identifier
	 * @return OrderAmount The order amount entry
	 **/
	public function &entry ( $register, $id = null ) {
		$false = false;
		if ( ! isset($this->register[ $register ]) ) return $false;
		$Register = &$this->register[ $register ];

		// If id is not provided, return the entire register
		if ( ! isset($id) ) return $Register;

		if ( ! isset($Register[$id]) ) return false;
		return $Register[$id];
	}

	/**
	 * Take off an OrderAmount entry from the register
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The register to find the entry in
	 * @param string $id The entry identifier
	 * @return boolean True if succesful, false otherwise
	 **/
	public function takeoff ( $register, $id ) {

		if ( ! isset($this->register[ $register ]) ) return false;
		$Register = &$this->register[ $register ];

		if ( ! isset($Register[ $id ])) return false;

		unset($Register[ $id ]);
		return true;
	}

	/**
	 * Empties a specified register
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The register to find the entry in
	 * @return boolean True if successful
	 **/
	public function reset ( $register ) {

		if ( ! isset($this->register[ $register ]) ) return false;
		$Register = &$this->register[ $register ];
		$Register = array();

		return true;
	}

	/**
	 * Update a specific register entry
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The name of the register to update
	 * @param array $entry An associative array with an id and amount: array('id' => 1, 'amount' => 10.00)
	 * @param float $amount The amount to change
	 * @return boolean True for success, false otherwise
	 **/
	public function update ( $register, $entry ) {
		if ( ! isset($this->register[ $register ]) ) return false;

		$Register = &$this->register[ $register ];
		if ( ! is_array($entry) || ! isset($entry['id']) || ! isset($entry['amount']) ) return false;

		$id = $entry['id'];
		$amount = $entry['amount'];

		if ( ! isset($Register[$id]) ) return false;
		$Entry = $Register[$id];

		// Set the new amount
		$Entry->amount($amount);

		// Recalculate the total for this register and the grand totals
		$this->total($register);

		return true;
	}

	/**
	 * Get the total amount of a register
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The register to calculate/get totals for
	 * @return mixed Returns the total amount as a float, or boolean false if the register doesn't exist
	 **/
	public function total ( $register = self::TOTAL ) {

		if ( empty($register) ) $register = self::TOTAL;
		if ( ! isset($this->register[ $register ]) ) return false;

		if ( $this->exists($register) ) $Total = &$this->get( $register ); // &$this->_list[ $register ];
		else $Total = &$this->add( $register, false );

		$Register = &$this->register[ $register ];

		// Return the current total for the register if it hasn't changed
		if ( ! $this->haschanged($register) && self::TOTAL != $register )
			return (float)$Total->amount();

		// Calculate a new total amount for the register
		$Total = new OrderTotal( array('amount' => 0.0) );

		if ( ! empty($Register) ) {

			foreach ( $Register as $Entry ) {
				$amount = $Entry->amount();
				if ( OrderTotalAmount::CREDIT == $Entry->column() ) 	// Set the amount based on transaction column
					$amount = $Entry->amount() * OrderTotalAmount::CREDIT;
				$Total->amount( $Total->amount() + $amount );
			}

			// Do not include entry in grand total if it is not a balance adjusting register
			if ( null === $Entry->column() ) return $Total->amount();

		}

		// For other registers, add or update that register's total entry for it in the totals register
		$GrandTotal = &$this->register[ self::TOTAL ];

		if ( ! isset($GrandTotal[ $register ]) ) // Add a new total entry
			$GrandTotal[ $register ] = new OrderTotal( array('id' => $register, 'amount' => $Total->amount() ) );
		else $GrandTotal[ $register ]->amount($Total->amount()); // Update the existing entry amount with the new total

		// If the total register did change, re-calculate the total register
		if ( $this->haschanged('total') ) $this->total();

		// Return the newly calculated amount
		return apply_filters( "shopp_ordertotals_{$register}_total", $Total->amount(), $Register );
	}

	/**
	 * Determines if the register has changed since last checked
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The name of the register to check
	 * @return boolean True when the register has changed
	 **/
	public function haschanged ( $register ) {
		$check = isset($this->checks[ $register ]) ? $this->checks[$register] : 0;
		$this->checks[$register] = hash('crc32b', serialize($this->register[$register]) );
		if ( 0 == $check ) return true;
		return ( $check != $this->checks[$register] );
	}

	public function data () {
		$this->total();
		return json_decode( (string)$this );
	}

	public function __toString () {
		$data = new StdClass();
		foreach ( $this as $id => $entry )
			$data->$id = $entry->amount();

		return json_encode($data);
	}

	public function __sleep () {
		return array_keys( get_object_vars($this) );
	}

}


/**
 * Central registration system for order total "registers"
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderTotalRegisters {

	private static $instance;
	private static $handlers = array();

	/**
	 * Provides access to the singleton instance
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return OrderTotalsRegisters
	 **/
	static public function instance () {
		if ( ! self::$instance instanceof self)
			self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Adds registration for a new order total register and its handler class
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $class The name of the register amount handling class
	 * @return void
	 **/
 	static public function register ( $class ) {
		$register = get_class_property($class, 'register');
 		self::$handlers[ $register ] = $class;
 	}

	/**
	 * Gets the class handle for a given register
	 *
	 * @author Jonathan Davis
	 * @since
	 *
	 * @param string $register The register name
	 * @return string The class name of the handler
	 **/
 	static private function handler ( $register ) {
 		if ( isset(self::$handlers[ $register ]) )
 			return self::$handlers[ $register ];
		return false;
 	}

	/**
	 * Adds a new amount
	 *
	 * @author Jonathan Davis
	 * @since
	 *
	 * @param string $register The register to add an amount
	 * @param array $message The amount options
	 * @return OrderTotalAmount An constructed OrderTotalAmount object
	 **/
 	static public function add ( OrderTotals $Totals, $register, array $options = array() ) {
		$RegisterClass = self::handler($register);

 		if ( false === $RegisterClass )
 			return trigger_error(__CLASS__ . ' register "' . $register . '" does not exist.', E_USER_ERROR);

		$Amount = new $RegisterClass($options);
 		if ( isset($Amount->_exception) ) return false;

		$Totals->register($Amount);

 		return $Amount;
 	}

}

/**
 * Provides the base functionality of order total amount objects
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
abstract class OrderTotalAmount {

	// Transaction type constants
	const DEBIT = 1;
	const CREDIT = -1;

	static public $register = '';		// Register name
	protected $id = '';					// Identifier name/id
	protected $column = null;			// A flag to determine the role of the amount
	protected $amount = 0.0;			// The amount the amount type
	protected $parent = false;			// The parent OrderTotals instance

	// protected $required = array('amount');

	public function __construct ( array $options = array() ) {

		// $properties = array_keys($options);
		// $provided = array_intersect($this->required,$properties);

		// if ($provided != $this->required) {
		// 	trigger_error('The required options for this ' . __CLASS__ . ' were not provided: ' . join(',',array_diff($this->required,$provided)) );
		// 	return $this->_exception = true;
		// }

		$this->populate($options);
	}

	/**
	 * Populates the object properties from a provided associative array of options
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $options An associative array to define construction of the object state
	 * @return void
	 **/
	protected function populate ( array $options ) {
		foreach ($options as $name => $value)
			if ( isset($this->$name) ) $this->$name = $value;
	}

	/**
	 * Default implementation to set an ID for the object with a fast checksum and return it
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string The object ID
	 **/
	public function id () {
		// Generate a quick checksum if no ID was given
		if ( empty($this->id) ) $this->id = hash('crc32b', serialize($this));
		return $this->id;
	}

	/**
	 * Provide the register this total belongs to and capture the parent OrderTotals controller
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param OrderTotals $OrderTotals The OrderTotals parent controller
	 * @return string The totals "register" this object will belong to
	 **/
	public function register ( OrderTotals $OrderTotals ) {
		$this->parent = $OrderTotals;
		$class = get_class($this);

		if ( ! class_exists($class, false) ) return '';
		if ( ! property_exists($class, 'register') ) return '';

		$vars = get_class_vars($class);
		return $vars[ 'register' ];
		// return $class::$register; // Use when PHP 5.3 is minimum requirement
	}

	/**
	 * Update this amount object from another OrderTotalAmount instance
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param OrderTotalAmount $OrderTotalAmount The OrderTotalAmount object to update
	 * @return void
	 **/
	public function update ( OrderTotalAmount $OrderTotalAmount ) {
		$this->amount( $OrderTotalAmount->amount() );
	}

	/**
	 * Updates or retrieves the amount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param float $value The value of the amount
	 * @return float The current amount
	 **/
	public function &amount ( $value = null ) {
		if ( ! is_null($value) ) $this->amount = $value;
		$amount = (float)round($this->amount, $this->precision());
		return $amount;
	}

	/**
	 * The amount adjustment column (DEBIT or CREDIT)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return int The transaction column
	 **/
	public function column () {
		return $this->column;
	}

	/**
	 * Removes this entry from the parent OrderTotals controller
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function remove () {
		if ( ! $this->parent ) return;
		$register = get_class_property(get_class($this), 'register');
		$OrderTotals = $this->parent;
		$OrderTotals->takeoff($register, $this->id);
	}

	public function __toString () {
		return $this->amount();
	}

	private function precision () {
		$format = currency_format();
		return $format['precision'];
	}

}

/**
 * Defines 'total' register entries
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderTotal extends OrderTotalAmount {
	static public $register = 'total';

	public function label () {
		return Shopp::__('Total');
	}

}
OrderTotalRegisters::register('OrderTotal');

/**
 * Intermediate class for debit column adjustment amounts
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountDebit extends OrderTotalAmount {
	protected $column = OrderTotalAmount::DEBIT;
}

/**
 * Intermediate class for credit column adjustment amounts
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountCredit extends OrderTotalAmount {
	protected $column = OrderTotalAmount::CREDIT;
}

/**
 * Defines a 'discount' amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountDiscount extends OrderAmountCredit {
	static public $register = 'discount';

	protected $setting = false;	// The related discount/promo setting
	protected $code = false;	// The code used

	public function label () {
		return Shopp::__('Discounts');
	}

}
OrderTotalRegisters::register('OrderAmountDiscount');

/**
 * Defines a customer account credit amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountAccountCredit extends OrderAmountCredit {
	static public $register = 'account';

	public function label () {
		return Shopp::__('Credit');
	}
}
OrderTotalRegisters::register('OrderAmountAccountCredit');

/**
 * Defines a gift certificate credit amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountGiftCertificate extends OrderAmountCredit {
	static public $register = 'certificate';

	public function label () {
		return Shopp::__('Gift Certificate');
	}
}
OrderTotalRegisters::register('OrderAmountGiftCertificate');

/**
 * Defines a gift card credit amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountGiftCard extends OrderAmountCredit {
	static public $register = 'giftcard';

	public function label () {
		return Shopp::__('Gift Card');
	}
}
OrderTotalRegisters::register('OrderAmountGiftCard');

/**
 * Defines a generic fee amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountFee extends OrderAmountDebit {
	static public $register = 'fee';
	protected $quantity = 0;

	public function label () {
		return Shopp::__('Fee');
	}
}
OrderTotalRegisters::register('OrderAmountFee');

/**
 * Defines a cart line item total amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountCartItem extends OrderAmountDebit {
	static public $register = 'order';

	protected $unit = 0;

	/**
	 * Constructs from a ShoppCartItem
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item The Cart Item to construct from
	 * @return void
	 **/
	public function __construct ( ShoppCartItem $Item ) {
		$this->unit = &$Item->unitprice;
		$this->amount = &$Item->total;
		$this->id = $Item->fingerprint();
	}

	/**
	 * Provides the label
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function label () {
		return Shopp::__('Subtotal');
	}

}
OrderTotalRegisters::register('OrderAmountItem');

class OrderAmountItemDiscounts extends OrderAmountDebit {

	static public $register = 'discount';

	/**
	 * Constructs from a ShoppCartItem
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item The Cart Item to construct from
	 * @return void
	 **/
	public function __construct ( ShoppOrderDiscount $Discount ) {
		$this->amount = $Discount->amount();
		$this->id = $Discount->promo;
	}

	/**
	 * Provides the label
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string
	 **/
	public function label () {
		return Shopp::__('Discounts');
	}

}
OrderTotalRegisters::register('OrderAmountItemDiscounts');

/**
 * A generic tax amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountTax extends OrderAmountDebit {
	static public $register = 'tax';

	protected $setting = false;	// The related tax setting
	protected $rate = 0.0;	// The applied rate
	protected $items = array();

	public function label () {
		return Shopp::__('Tax');
	}

	public function rate () {
		return (float) $this->rate;
	}

	public function column () {
		// Do not add inclusive taxes to totals tally
		if ( shopp_setting_enabled('tax_inclusive') )
			return null;
		return $this->column;
	}

}
OrderTotalRegisters::register('OrderAmountTax');


/**
 * Defines an item tax entry
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountItemTax extends OrderAmountTax {
	static public $register = 'tax';

	protected $rate = 0.0;	// The applied rate
	protected $items = array(); // Store the item tax amounts
	protected $label = '';

	public function __construct ( ShoppItemTax &$Tax, $itemid ) {

		$this->items[ $itemid ] = &$Tax->total;
		$this->label = &$Tax->label;
		$this->rate = &$Tax->rate;
		$this->id = &$Tax->label;
		$this->amount = $this->total();
	}

	public function unapply ( $itemid ) {
		if ( isset($this->items[ $itemid ]) );
			unset($this->items[ $itemid ]);

		if ( empty($this->items) )	// No longer applies to any item
			$this->remove();		// Remove the register entry

		else $this->amount();		// Recalculate total tax amount
	}

	public function update ( OrderTotalAmount $Updates ) {
		$this->items( $Updates->items() );
		$this->amount();
	}

	public function items ( array $items = null ) {
		if ( isset($items) )
			$this->items = array_merge($this->items, $items);
		return $this->items;
	}

	public function total () {
		$this->items = array_filter($this->items); // Filter out empty rates

		if ( empty($this->items) ) { // no longer applies to any item
			$this->remove();
			return null;
		}

		return (float) array_sum($this->items());
	}

	public function &amount ( $value = null ) {
		return parent::amount($this->total());
	}

	public function label () {
		if ( empty($this->label) ) return Shopp::__('Tax');
		return $this->label;
	}

}
OrderTotalRegisters::register('OrderAmountItemTax');

/**
 * Defines an item tax entry
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountShippingTax extends OrderAmountTax {
	static public $register = 'tax';

	protected $rate = 0.0;	// The applied rate
	protected $label = '';

	public function __construct ( $taxable ) {
		$Tax = ShoppOrder()->Tax;

		$taxes = array();
		$Tax->rates($taxes);
		$firstrate = reset($taxes);
		if ( $firstrate )
			$this->rate = $firstrate->rate;

		$this->id = 'shipping';
		$this->amount = ShoppTax::calculate($taxes, $taxable);
		$this->label = Shopp::__('Shipping Tax');
	}

}
OrderTotalRegisters::register('OrderAmountShippingTax');

/**
 * Cart item quantity register
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountCartItemQuantity extends OrderTotalAmount {
	static public $register = 'quantity';

	public function __construct ( ShoppCartItem $Item ) {
		$this->amount = &$Item->quantity;
		$this->id = $Item->fingerprint();
	}

	public function label () {
		return Shopp::__('quantity');
	}
}
OrderTotalRegisters::register('OrderAmountItemQuantity');


/**
 * Defines a shipping amount
 *
 * @author Jonathan Davis
 * @since 1.3
 **/
class OrderAmountShipping extends OrderAmountDebit {

	static public $register = 'shipping';
	protected $setting = false;
	protected $delivery = false;
	protected $items = array();

	public function label () {
		return Shopp::__('Shipping');
	}
}
OrderTotalRegisters::register('OrderAmountShipping');
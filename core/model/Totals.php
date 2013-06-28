<?php
/**
 * Totals.php
 * Order totals calculator
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage ordertotals
 **/

/**
 * OrderTotals
 *
 * Manages order total registers
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1.0
 * @package ordertotals
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
	public function &entry ( string $register, string $id ) {
		if ( ! isset($this->register[ $register ]) ) return false;
		$Register = &$this->register[ $register ];

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
	public function takeoff ( string $register, string $id ) {

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
	public function clear ( string $register ) {

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
	 * @param string $id The id string of the entry
	 * @param float $amount The amount to change
	 * @return boolean True for success, false otherwise
	 **/
	public function update ( string $register, string $id, float $amount ) {
		if ( ! isset($this->register[ $register ]) ) return false;

		$Register = &$this->register[ $register ];

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
		if ( ! $this->changed($register) && self::TOTAL != $register )
			return (float)$Total->amount();

		// Calculate a new total amount for the register
		$Total = new OrderTotal( array('amount' => 0.0) );
		if ( empty($Register) ) return $Total->amount();

		foreach ( $Register as $Entry) {
			$amount = $Entry->amount();
			if ( OrderTotalAmount::CREDIT == $Entry->column() ) 	// Set the amount based on transaction column
				$amount = $Entry->amount() * OrderTotalAmount::CREDIT;
			$Total->amount( $Total->amount() + $amount );
		}

		// Do not include entry in grand total if it is not a balance adjusting register
		if ( null === $Entry->column() ) return $Total->amount();

		// For other registers, add or update that register's total entry for it in the totals register
		$GrandTotal = &$this->register[ self::TOTAL ];

		if ( ! isset($GrandTotal[ $register ]) ) // Add a new total entry
			$GrandTotal[ $register ] = new OrderTotal( array('id' => $register, 'amount' => $Total->amount() ) );
		else $GrandTotal[ $register ]->amount($Total->amount()); // Update the existing entry amount with the new total

		// If the total register did change, re-calculate the total register
		if ( $this->changed('total') ) $this->total();

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
	public function changed ( string $register ) {
		$check = isset($this->checks[ $register ]) ? $this->checks[$register] : 0;
		$this->checks[$register] = hash('crc32b', serialize($this->register[$register]) );
		if ( 0 == $check ) return true;
		return ( $check != $this->checks[$register] );
	}

	public function data () {
		return json_decode( (string)$this );
	}

	public function __toString () {
		$data = new StdClass();
		foreach ( $this as $id => $entry )
			$data->$id = (string)$entry;

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
 * @package ordertotals
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
 	static public function register ( string $class ) {
 		$_this = self::instance();
		$register = get_class_property($class,'register');
 		$_this->handlers[ $register ] = $class;
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
 	static private function handler ( string $register ) {
 		$_this = self::instance();
 		if ( isset($_this->handlers[ $register ]) )
 			return $_this->handlers[ $register ];
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
 	static public function add ( OrderTotals $Totals, string $register, array $options = array() ) {
 		$_this = self::instance();
		$RegisterClass = $_this->handler($register);

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
 * @package ordertotals
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
		return $class::$register; // @todo Test if this will cause problems in PHP 5.2.4 calling via magic method?
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
	public function &amount ( float $value = null ) {
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
		$register = get_class_property(get_class($this), 'register');
		$OrderTotals = $this->parent;
		$OrderTotals->takeoff($register, $this->id);
	}

	public function __toString () {
		return (string)$this->amount();
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
 * @package ordertotals
 **/
class OrderTotal extends OrderTotalAmount {
	static public $register = 'total';

	public function label () {
		return __('Total','Shopp');
	}

}
OrderTotalRegisters::register('OrderTotal');

/**
 * Intermediate class for debit column adjustment amounts
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountDebit extends OrderTotalAmount {
	protected $column = OrderTotalAmount::DEBIT;
}

/**
 * Intermediate class for credit column adjustment amounts
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountCredit extends OrderTotalAmount {
	protected $column = OrderTotalAmount::CREDIT;
}

/**
 * Defines a 'discount' amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountDiscount extends OrderAmountCredit {
	static public $register = 'discount';

	protected $setting = false;	// The related discount/promo setting
	protected $code = false;	// The code used

	public function label () {
		return __('Discounts','Shopp');
	}

}
OrderTotalRegisters::register('OrderAmountDiscount');

/**
 * Defines a customer account credit amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountAccountCredit extends OrderAmountCredit {
	static public $register = 'account';

	public function label () {
		return __('Credit','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountAccountCredit');

/**
 * Defines a gift certificate credit amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountGiftCertificate extends OrderAmountCredit {
	static public $register = 'certificate';

	public function label () {
		return __('Gift Certificate','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountGiftCertificate');

/**
 * Defines a gift card credit amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountGiftCard extends OrderAmountCredit {
	static public $register = 'giftcard';

	public function label () {
		return __('Gift Card','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountGiftCard');

/**
 * Defines a generic fee amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountFee extends OrderAmountDebit {
	static public $register = 'fee';
	protected $quantity = 0;

	public function label () {
		return __('Fee','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountFee');

/**
 * Defines a cart line item total amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
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

		add_action('shopp_cart_remove_item',array($this,'remove'));
	}

	/**
	 * Provides the label
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void Description...
	 **/
	public function label () {
		return __('Subtotal','Shopp');
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

		add_action('shopp_cart_remove_item',array($this,'remove'));
	}

	/**
	 * Provides the label
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return strin
	 **/
	public function label () {
		return __('Discounts','Shopp');
	}

}
OrderTotalRegisters::register('OrderAmountItemDiscounts');

/**
 * Defines an item tax entry
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountItemTax extends OrderAmountDebit {
	static public $register = 'tax';

	protected $rate = 0.0;	// The applied rate
	protected $items = array(); // Store the item tax amounts
	protected $label = '';

	public function __construct ( ShoppItemTax &$Tax, string $itemid ) {
		$this->items[ $itemid ] = &$Tax->total;
		$this->label = &$Tax->label;
		$this->rate = &$Tax->rate;
		$this->id = &$Tax->label;
		$this->amount = $this->total();

		add_action('shopp_cart_remove_item',array($this,'remove'));
	}

	public function removal () {
		list($id,$Item,) = func_get_args();

		if ( empty($this->items) )
			return parent::remove();

		if ( isset($this->items[$id]) ) {
			unset($this->items[ $id ]);
			$this->total();
		}
	}

	public function update ( OrderTotalAmount $Updates ) {
		$this->items( $Updates->items() );
		$this->total();
	}

	public function items ( array $items = null ) {
		if ( isset($items) )
			$this->items = array_merge($this->items, $items);
		return $this->items;
	}

	public function total () {
		return array_sum($this->items());
	}

	public function &amount ( float $value = null ) {
		return parent::amount($this->total());
	}

	public function label () {
		if ( empty($this->label) ) return __('Tax','Shopp');
		return $this->label;
	}

}
OrderTotalRegisters::register('OrderAmountItemTax');

/**
 * Defines an item tax entry
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountShippingTax extends OrderAmountDebit {
	static public $register = 'tax';

	protected $rate = 0.0;	// The applied rate
	protected $label = '';

	public function __construct ( float $taxable ) {
		$Tax = ShoppOrder()->Tax;

		$taxes = array();
		$Tax->rates($taxes);
		$firstrate = reset($taxes);
		$this->rate = $firstrate->rate;
		$this->id = 'shipping';
		$this->amount = $Tax->calculate($taxes, $taxable);
		$this->label = __('Shipping Tax','Shopp');

	}

}
OrderTotalRegisters::register('OrderAmountShippingTax');

class OrderAmountCartItemQuantity extends OrderTotalAmount {
	static public $register = 'quantity';

	public function __construct ( ShoppCartItem $Item ) {
		$this->amount = &$Item->quantity;
		$this->id = $Item->fingerprint();

		add_action('shopp_cart_remove_item', array($this, 'remove'));

	}

	public function label () {
		return __('quantity','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountItemQuantity');

/**
 * A generic tax amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountTax extends OrderAmountDebit {
	static public $register = 'tax';
	protected $setting = false;	// The related tax setting
	protected $rate = 0.0;	// The applied rate
	protected $items = array();

	public function label () {
		return __('Tax','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountTax');

/**
 * Defines a shipping amount
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package ordertotals
 **/
class OrderAmountShipping extends OrderAmountDebit {

	static public $register = 'shipping';
	protected $setting = false;
	protected $delivery = false;
	protected $items = array();

	public function label () {
		return __('Shipping','Shopp');
	}
}
OrderTotalRegisters::register('OrderAmountShipping');

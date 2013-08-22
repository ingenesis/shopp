<?php
/**
 * Discounts.php
 *
 * Handles order discounts
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, May 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage order
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Order Discounts manager
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1.2
 * @package discounts
 **/
class ShoppDiscounts extends ListFramework {

	private $removed = array(); // List of removed discounts
	private $codes = array();	// List of applied codes
	private $request = false;	// Current code request

	/**
	 * Get or set the current request
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $request The request string to set
	 * @return string The current request
	 **/
	public function request ( string $request = null ) {

		if ( isset($request) ) $this->request = $request;
		return $this->request;

	}

	/**
	 * Handle parsing and routing discount code related requests
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function requests () {

		if ( isset($_REQUEST['promocode']) && ! empty($_REQUEST['promocode']) )
			$this->request( trim($_REQUEST['promocode']) );

		if ( isset($_REQUEST['removecode']) && ! empty($_REQUEST['removecode']) )
			$this->undiscount(trim($_REQUEST['removecode']));

	}

	/**
	 * Calculate the discount amount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return float The total discount amount
	 **/
	public function amount () {

		do_action('shopp_calculate_discounts');

		$this->match();

		$deferred = array();
		$discounts = array();
		foreach ( $this as $Discount ) {

			if ( ShoppOrderDiscount::ORDER == $Discount->target() && ShoppOrderDiscount::PERCENT_OFF == $Discount->type() ) {
				$deferred[] = $Discount;
				continue;
			}

			$Discount->calculate();
			$discounts[] = $Discount->amount();
		}

		foreach ( $deferred as $Discount ) {
			$amount = array_sum($discounts);
			$Discount->calculate($amount);
			$discounts[] = $Discount->amount();
		}

		$amount = array_sum($discounts);

		$Cart = ShoppOrder()->Cart->Totals;
		if ( $Cart->total('order') < $amount )
			$amount = $Cart->total('order');

		return (float)$amount;

	}

	/**
	 * Match the promotions that apply to the order
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function match () {

		$Promotions = ShoppOrder()->Promotions;

		if ( ! $Promotions->available() )
			$Promotions->load();

		// Match applied first
		$Promotions->sort( array($this, 'sortapplied') );

		// Iterate over each promo to determine whether it applies
		$discount = 0;
		foreach ( $Promotions as $Promo ) {
			$apply = false;

			if ( $this->removed($Promo) ) break;

			// Cancel matching if max number of discounts reached
			if ( $this->maxed($Promo) ) break;

			$matches = 0;
			$total = 0;

			// Match the promo rules against the cart properties
			foreach ($Promo->rules as $index => $rule) {
				if ( 'item' === $index ) continue;

				$total++; // Count the total 'non-item' rules

				$Rule = new ShoppDiscountRule($rule, $Promo);
				if ( $match = $Rule->match() ) {
					if ( 'any' == $Promo->search ) {
						$apply = true; // Stop matching rules once **any** of them apply
						break;
					} else $matches++; // Add to the matches tally
				}

			}

			// The matches tally must equal to total 'non-item' rules in order to apply
			if ( 'all' == $Promo->search && $matches == $total ) $apply = true;

			if ( apply_filters('shopp_apply_discount', $apply, $Promo) ) $this->apply($Promo); // Add the Promo as a new discount
			else $this->reset($Promo);

		} // End promos loop

		// Check for failed promo codes

		if ( empty($this->request) || $this->codeapplied( $this->request ) ) return;
		else {
			shopp_add_error( Shopp::__('"%s" is not a valid code.', $this->request) );
			$this->request = false;
		}
	}

	/**
	 * Adds a discount entry for a promotion that applies
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.3
	 *
	 * @param Object $Promotion The pseudo-Promotion object to apply
	 * @param float $discount The calculated discount amount
	 * @return void
	 **/
	private function apply ( ShoppOrderPromo $Promo ) {
		$Discount = new ShoppOrderDiscount($Promo);

		// Match line item discount targets
		if ( isset($Promo->rules['item']) ) {
			$this->items($Promo, $Discount);
		} //else $Promo->applied = $Discount;

		$this->applycode($Promo);

		$this->add($Promo->id,$Discount);

	}

	/**
	 * Match promotion item rules and add the matching items to the discount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo A promotion object
	 * @param ShoppOrderDiscount $Discount A discount object
	 * @return void
	 **/
	private function items ( ShoppOrderPromo $Promo, ShoppOrderDiscount $Discount ) {
		$Cart = ShoppOrder()->Cart;

		$rules = $Promo->rules['item'];

		$discounts = array();

		// See if an item rule matches
		foreach ( $Cart as $id => $Item ) {
			if ( 'Donation' == $Item->type ) continue; // Skip donation items
			$matches = 0;

			foreach ( $rules as $rule ) {
				$ItemRule = new ShoppDiscountRule($rule, $Promo);
				if ( $ItemRule->match($Item) && ! $Discount->hasitem($id) ) $matches++;
			}

			if ( count($rules) == $matches ) // all conditions must match
				$Discount->item( $Item );

		}

	}

	/**
	 * Match and apply a promo code
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo A promotion object
	 * @return void
	 **/
	private function applycode ( ShoppOrderPromo $Promo ) {

		// Determine which promocode matched
		$rules = array_filter($Promo->rules, array($this, 'coderules'));

		$request = strtolower($this->request);

		foreach ( $rules as $rule ) {

			$CodeRule = new ShoppDiscountRule($rule, $Promo);

			if ( ! $CodeRule->match() ) continue;

			// Prevent customers from reapplying codes
			if ( $this->codeapplied($request) ) {
				shopp_add_error( sprintf(__('%s has already been applied.', 'Shopp'), $value) );
				$this->request = false;
			}

			if ( ! $this->codeapplied( $rule['value'] ) )
				$this->codes[ strtolower($rule['value']) ] = array();

			$this->codes[ strtolower($rule['value']) ] = $Promo->id;

		}

	}

	/**
	 * Remove an applied discount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param integer $id The discount ID to remove
	 * @return void
	 **/
	private function undiscount ( integer $id ) {

		if ( ! $this->exists($id) ) return false;

		$Discount = $this->get($id);

		$_REQUEST['cart'] = true;

		$this->remove($id);

		if ( isset($this->codes[ $Discount->code() ]) ) {
			unset($this->codes[ $Discount->code() ]);
			return;
		}

		// If no code was found, block the discount from being auto-applied again
		$this->removed[ $id ] = true;

	}

	/**
	 * Determines if a give ShoppOrderPromo has been previously removed
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo The promo object to check
	 * @return boolean True if removed, false otherwise
	 **/
	private function removed ( ShoppOrderPromo $Promo ) {
		return isset($this->removed[ $Promo->id ]);
	}

	/**
	 * Reset a promotion
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo A promotion object
	 * @return void
	 **/
	private function reset ( ShoppOrderPromo $Promo ) {

		$this->remove($Promo->id);		// Remove it from the discount stack if it is there

	}

	/**
	 * Detects if the maximum number of promotions have been applied
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo A promotion object
	 * @return boolean True if the max was reached, false otherwise
	 **/
	private function maxed ( ShoppOrderPromo $Promo ) {

		$promolimit = (int)shopp_setting('promo_limit');

		// If promotion limit has been reached and the promo has
		// not already applied as a cart discount, cancel the loop
		if ( $promolimit && ( $this->count() + 1 ) > $promolimit && ! $this->exists($Promo->id) ) {
			if ( ! empty($this->request) )
				shopp_add_error(Shopp::__('No additional codes can be applied.'));
			return true;
		}

		return false;
	}

	/**
	 * Helper method to sort active discounts before other promos
	 *
	 * Sorts active discounts to the top of the available promo list
	 * to enable efficient promo limit enforcement
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return integer
	 **/
	public function sortapplied ( $a, $b ) {
		return $this->exists($a) && ! $this->exists($b) ? -1 : 1;
	}

	/**
	 * Helper method to identify a rule as a promo code rule
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $rule The rule to test
	 * @return boolean
	 **/
	public function coderules ( array $rule ) {
		return isset($rule['property']) && 'promo code' == strtolower($rule['property']);
	}

	/**
	 * Checks if a given code has been applied to the order
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $code The code to check
	 * @return boolean True if the code is applied, false otherwise
	 **/
	public function codeapplied ( string $code ) {
		return isset( $this->codes[ strtolower($code) ]);
	}

	public function clear () {
		parent::clear();
		$this->codes = array();
		$this->request = false;
		ShoppOrder()->Shiprates->free(false);
	}

	/**
	 * Preserves only the necessary properties when storing the object
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function __sleep () {
		return array('codes', 'removed', '_added', '_checks', '_list');
	}


}

/**
 * Evaluates discount rules
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package discounts
 **/
class ShoppDiscountRule {

	private $promo = false;			// A reference to the originating promotion object
	private $property = false;		// The rule property name
	private $logic = false;			// The logical comparison operation to match with
	private $value = false;			// The value to match

	/**
	 * Constructs a ShoppDiscountRule
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $rule The rule array to convert
	 * @param ShoppOrderPromo $Promo The originating promotion object for the rule
	 * @return void
	 **/
	public function __construct ( array $rule, ShoppOrderPromo $Promo ) {

		$this->promo = $Promo;

		// Populate the rule
		foreach ( $rule as $name => $value ) {
			if ( property_exists($this,$name) )
				$this->$name = $value;
		}

	}

	/**
	 * Calls the matching algorithm to match the rule
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item (optional) A cart Item to use for matching
	 * @return boolean True for match, false no match
	 **/
	public function match ( ShoppCartItem $Item = null ) {

		// Determine the subject data to match against
		$subject = $this->subject();

		if ( is_callable($subject) ) {
			// If the subject is a callback, use it for matching
			return call_user_func($subject, $Item);
		} else {
			// Evaluate the subject using standard matching
			return $this->evaluate($subject);
		}

	}

	/**
	 * Determine the appropriate subject data or matching callback
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return mixed The subject or callback
	 **/
	private function subject () {

		$Cart = ShoppOrder()->Cart;

		$property = strtolower($this->property);

		switch ( $property ) {
			case 'any item amount':
			case 'any item name':
			case 'any item quantity':
			case 'category':
			case 'discount amount':
			case 'name':
			case 'quantity':
			case 'tag name':
			case 'total price':
			case 'unit price':
			case 'variant':
			case 'variation':			return array($this, 'items');

			case 'promo code': 			return array($this, 'code');

			case 'promo use count':		return $this->promo->uses;
			case 'total quantity':		return $Cart->Totals->total('quantity');
			case 'shipping amount':		return $Cart->Totals->total('shipping');
			case 'subtotal amount':		return $Cart->Totals->total('order');
			case 'customer type':		return ShoppOrder()->Customer->type;
			case 'ship-to country':		return ShoppOrder()->Shipping->country;
			default:					return apply_filters('shopp_discounts_subject_' . sanitize_key($property), false);
		}

	}

	/**
	 * Match a discount code
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if match, false for no match
	 **/
	private function code () {
		$this->value = strtolower($this->value);
		// Match previously applied codes
		$Discounts = ShoppOrder()->Discounts;
		if ( $Discounts->codeapplied($this->value) ) return true;

		// Match new codes
		$request = strtolower($Discounts->request());

		// No code provided, nothing will match
		if ( empty($request) ) return false;

		return $this->evaluate($request);
	}

	/**
	 * Determine the item subject data and match against it
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item The Item to match against
	 * @return boolean True if match, false for no match
	 **/
	private function items ( ShoppCartItem $Item = null ) {
		if ( ! isset($Item) ) return false;

		$property = strtolower($this->property);

		switch ( $property ) {
			case 'total price':
			case 'any item amount':		$subject = (float)$Item->total; break;
			case 'name':
			case 'any item name':		$subject = $Item->name; break;
			case 'quantity':
			case 'any item quantity':	$subject = (int)$Item->quantity; break;
			case 'category':			$subject = (array)$Item->categories; break;
			case 'discount amount':		$subject = (float)$Item->discount; break;
			case 'tag name':			$subject = (array)$Item->tags; break;
			case 'unit price':			$subject = (float)$Item->unitprice; break;
			case 'variant':
			case 'variation':			$subject = $Item->option->label; break;
			case 'input name':			$subject = $Item->data; break;
			case 'input value':			$subject = $Item->data; break;

		}

		return $this->evaluate($subject);
	}

	/**
	 * Evaluates if the rule value matches the given subject using the selected rule logic
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param mixed $subject The subject data to match against
	 * @return boolean True for a match, false for no match
	 **/
	private function evaluate ( $subject ) {

		$property = $this->property;
		$op = strtolower($this->logic);
		$value = $this->value;

		switch( $op ) {
			// String or Numeric operations
			case 'is equal to':

				$type = 'string';
			 	if ( isset(Promotion::$values[ $property ]) && 'price' == Promotion::$values[ $property ] )
					$type = 'float';

				return $this->isequalto($subject,$value,$type);

			case 'is not equal to':

				$type = 'string';
			 	if ( isset(Promotion::$values[ $property ]) && 'price' == Promotion::$values[ $property ] )
					$type = 'float';

				return ! $this->isequalto($subject,$value,$type);

			// String operations
			case 'contains':					return $this->contains($subject, $value);
			case 'does not contain':			return ! $this->contains($subject, $value);
			case 'begins with': 				return $this->beginswith($subject, $value);
			case 'ends with':					return $this->endswith($subject, $value);

			// Numeric operations
			case 'is greater than':				return (Shopp::floatval($subject,false) > Shopp::floatval($value,false));
			case 'is greater than or equal to':	return (Shopp::floatval($subject,false) >= Shopp::floatval($value,false));
			case 'is less than':				return (Shopp::floatval($subject,false) < Shopp::floatval($value,false));
			case 'Is less than or equal to':	return (Shopp::floatval($subject,false) <= Shopp::floatval($value,false));
		}

		return false;
	}

	/**
	 * Matches subject and value using equal to
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param mixed $subject The subject data to compare
	 * @param mixed $value The value to match against
	 * @param string $type The data type matching (string or float)
	 * @return boolean True for a match, false for no match
	 **/
	private function isequalto ( $subject, $value, $type = 'string' ) {

		if ( 'float' == $type ) {
			$subject = Shopp::floatval($subject);
			$value = Shopp::floatval($value);
			return ( $subject != 0 && $value != 0 && $subject == $value );
		}

		if ( is_array($subject) ) return in_array($value, $subject);

		return ("$subject" === "$value");

	}

	/**
	 * Matches a subject that contains the value
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param mixed $subject The subject data to compare
	 * @param mixed $value The value to match against
	 * @return boolean True for a match, false for no match
	 **/
	private function contains ( $subject, $value ) {

		if ( is_array($subject) ) {
			foreach ( $subject as $s )
				if ( $this->contains( (string)$s, $value) ) return true;
			return false;
		}

		return ( false !== stripos($subject,$value) );
	}

	/**
	 * Matches a subject that begins with the value
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param mixed $subject The subject data to compare
	 * @param mixed $value The value to match against
	 * @return boolean True for a match, false for no match
	 **/
	private function beginswith ( $subject, $value ) {

		if ( is_array($subject) ) {
			foreach ( $subject as $s )
				if ( $this->beginswith((string)$s, $value) ) return true;
			return false;
		}

		return 0 === stripos($subject,$value);

	}

	/**
	 * Matches a subject that ends with the value
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param mixed $subject The subject data to compare
	 * @param mixed $value The value to match against
	 * @return boolean True for a match, false for no match
	 **/
	private function endswith ( $subject, $value ) {

		if ( is_array($subject) ) {
			foreach ($subject as $s)
				if ( $this->endswith((string)$s, $value) ) return true;
			return false;
		}

		return stripos($subject,$value) === strlen($subject) - strlen($value);

	}

}

/**
 * A discount entry
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package discounts
 **/
class ShoppOrderDiscount {

	// Discount types
	const AMOUNT_OFF = 1;
	const PERCENT_OFF = 2;
	const SHIP_FREE = 4;
	const BOGOF = 8;

	// Discount targets
	const ITEM = 1;
	const ORDER = 2;

	private $id = false;					// The originating promotion object id
	private $name = '';						// The name of the promotion
	private $amount = 0.00;					// The total amount of the discount
	private $type = self::AMOUNT_OFF;		// The discount type
	private $target = self::ORDER;			// The discount target
	private $discount = false;				// The calculated discount amount
	private $code = false;					// The code associated with the discount
	private $shipfree = false;				// A flag for free shipping
	private $items = array();				// A list of items the discount applies to

	/**
	 * Converts a Promotion object to a Discount object
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo The promotion object to convert
	 * @return void
	 **/
	public function __construct ( ShoppOrderPromo $Promo ) {

		$this->id((int)$Promo->id);
		$this->name($Promo->name);
		$this->code($Promo->code);
		$this->discount($Promo);
		$this->calculate();

	}

	/**
	 * Gets or sets the discount promotion id
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param integer $id The id of a source promotion object
	 * @return integer The id of the discount
	 **/
	public function id ( integer $id = null ) {
		if ( isset($id) ) $this->id = $id;
		return $this->id;
	}

	/**
	 * Gets or sets the name of the discount
	 *
	 * Used as the label for the discount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $name The name to set
	 * @return string The name of the discount
	 **/
	public function name ( string $name = null ) {
		if ( isset($name) ) $this->name = $name;
		return $this->name;
	}

	/**
	 * Get the total amount of the discount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return float The amount of th discount
	 **/
	public function amount () {
		return (float)$this->amount;
	}

	public function calculate ( $discounts = 0 ) {
		$Items = ShoppOrder()->Cart;
		$Cart = ShoppOrder()->Cart->Totals;

		switch ( $this->type ) {
			case self::SHIP_FREE:	if ( self::ORDER == $this->target ) $this->shipfree(true); //$this->amount = $Cart->total('shipping'); break;
			case self::AMOUNT_OFF:	$this->amount = $this->discount(); break;
			case self::PERCENT_OFF:
				$subtotal = $Cart->total('order');
				if ( $discounts > 0 ) $subtotal -= $discounts;
				// $subtotal = $Cart->total('order') - $Cart->total('discount');
				$this->amount = $subtotal * ($this->discount() / 100);
				break;
		}

		if ( ! empty($this->items) ) {

			$discounts = array();
			foreach ( $this->items as $id => $unitdiscount ) {
				$Item = $Items->get($id);

				if ( self::BOGOF == $this->type() ) {
					if ( ! is_array( $Item->bogof) ) $Item->bogof = array();
					$Item->bogof[ $this->id() ] = $unitdiscount;
				}
				else $Item->discount += $unitdiscount;

				$Item->totals();
				$Cart->total('tax'); // Recalculate taxes

				$discounts[] = $Item->discounts;
			}

			$this->amount = array_sum($discounts);
		}

	}

	/**
	 * Gets or sets the code for this discount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $code The code to set as the discount code
	 * @return string The code for the discount
	 **/
	public function code ( string $code = null ) {
		if ( isset($code) ) $this->code = $code;
		return $this->code;
	}

	/**
	 * Gets or sets the free shipping flag
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param boolean $shipfree The setting for free shipping
	 * @return boolean The free shipping status of the discount
	 **/
	public function shipfree ( boolean $shipfree = null ) {
		if ( isset($shipfree) ) {
			$this->shipfree = $shipfree;
			ShoppOrder()->Shiprates->free($shipfree);
		}
		return $this->shipfree;
	}

	/**
	 * Gets or sets and converts the discount type
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $type The discount type
	 * @return integer The ShoppOrderDiscount type
	 **/
	public function type ( string $type = null ) {
		if ( isset($type) ) {
			switch ( strtolower($type) ) {
				case 'percentage off':		$this->type = self::PERCENT_OFF; break;
				case 'amount off':			$this->type = self::AMOUNT_OFF; break;
				case 'free shipping':		$this->type = self::SHIP_FREE; break;
				case 'buy x get y free':	$this->type = self::BOGOF; break;
			}
		}

		return $this->type;
	}

	/**
	 * Gets or sets and converts the discount target
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $target The target string to convert
	 * @return integer the ShoppOrderDiscount target
	 **/
	public function target ( string $target = null ) {
		if ( isset($target) ) {
			switch ( strtolower($target) ) {
				case 'cart item':	$this->target = self::ITEM; break;
				case 'cart':		$this->target = self::ORDER; break;
			}
		}

		return $this->target;
	}

	/**
	 * Gets or sets the discount amount
	 *
	 * The discount amount (as opposed to the ShoppOrderDiscount->amount) is
	 * used as the basis for calculating the ShoppOrderDiscount->amount.
	 * In this way it prepares the different discount type amounts to a useable
	 * value for calculating in currency amounts.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo The promotion object to determine the discount amount from
	 * @return mixed The discount amount
	 **/
	public function discount ( ShoppOrderPromo $Promo = null ) {

		if ( isset($Promo) ) {

			$target = $this->target($Promo->target);
			$type = $this->type($Promo->type);
			$this->discount = $Promo->discount;

			if ( self::BOGOF == $type )
				$this->discount = array($Promo->buyqty, $Promo->getqty);
		}

		return $this->discount;
	}

	/**
	 * Gets or sets the items the discount applies to
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $items A list of items to set the discount to apply to
	 * @return array The list of items
	 **/
	public function items ( array $items = array() ) {
		if ( ! empty($items) ) $this->items = $items;
		return $this->items;
	}

	/**
	 * Adds an item discount amount entry to the applied item discounts list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item The item object to calculate a discount from
	 * @return float The item discount amount
	 **/
	public function item ( ShoppCartItem $Item ) {

		// These must result in the discount applied to the *unit price*!
		switch ( $this->type ) {
			case self::PERCENT_OFF:	$amount = $Item->unitprice * ($this->discount() / 100); break;
			case self::AMOUNT_OFF:	$amount = $this->discount(); break;
			case self::SHIP_FREE:	$Item->freeshipping = true; $amount = 0;
			case self::BOGOF:
				list($buy, $get) = $this->discount();

				// The total quantity per discount
				$buying = ($buy + $get);

				// The number of times the discount will apply
				$amount = ($Item->quantity / $buying );

				// Keep the BOGOF factor floored when quantity over buying has remainders
				if ( $Item->quantity % $buying ) $amount = (int)floor($amount);

				break;

		}

		$this->items[ $Item->fingerprint() ] = (float)$amount;

		return $amount;
	}

	/**
	 * Determines if a give item id exists in the items list
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $key The item id key
	 * @return boolean True if it exists, false otherwise
	 **/
	public function hasitem ( string $key ) {
		return isset($this->items[ $key ]);
	}

}

/**
 * Loads the available promotions
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package discounts
 **/
class ShoppPromotions extends ListFramework {

	static $targets = array('Cart', 'Cart Item');

	protected $loaded = false;
	protected $promos = null;

	/**
	 * Detect if promotions exist and pre-load if so.
	 */
	public function __construct() {
		ShoppingObject::store( 'promos', $this->promos );
	}


	/**
	 * Returns the status of loaded promotions.
	 *
	 * Calling this method causes promotions to be loaded from the db, unless it was called earlier in the session with
	 * a negative result - in which case it will not cause further queries for the lifetime of the session.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if there are promotions loaded, false otherwise
	 **/
	public function available () {
		if ( null === $this->promos || true === $this->promos ) {
			$this->load();
			$this->promos = $this->count() > 0;
		}
		return $this->promos;
	}

	/**
	 * Load active promotions
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of loaded ShoppOrderPromo objects
	 **/
	public function load () {

		if ( $this->loaded ) return; // Don't load twice in one request

		$table = DatabaseObject::tablename(Promotion::$table);
		$where = array(
			"status='enabled'",
			Promotion::activedates(),
			"target IN ('" . join("','", self::$targets) . "')"
		);
		$orderby = 'target DESC';

		$queryargs = compact('table', 'where', 'orderby');
		$query = DB::select( $queryargs );
		$loaded = DB::query($query, 'array', array('ShoppPromotions', 'loader') );

		if ( ! $loaded || 0 == count($loaded) ) return;

		$this->populate($loaded);
		$this->loaded = true;
	}

	/**
	 * Converts loaded records to ShoppOrderPromo entries
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $records The record set to populate
	 * @param stdClass $record The loaded record
	 * @param string|Object $DatabaseObject The class name or object instance for the record
	 * @param string $index (optional) The column to index records by
	 * @param boolean $collate Flag to collect/group records with matching index columns
	 * @return void
	 **/
	public static function loader ( &$records, &$record, $DatabaseObject = false, $index = 'id', $collate = false ) {
		$index = isset($record->$index) ? $record->$index : '!NO_INDEX!';

		$Object = new ShoppOrderPromo($record);

		if ( $collate ) {
			if ( ! isset($records[ $index ]) ) $records[ $index ] = array();
			$records[ $index ][] = $Object;
		} else $records[ $index ] = $Object;
	}

	public function clear () {
		parent::clear();
		$this->promos = null;
		$this->loaded = false;
	}

}

/**
 * A ShoppOrderPromo record
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package discounts
 **/
class ShoppOrderPromo {

	public function __construct ( $record ) {
		$properties = get_object_vars($record);
		foreach ( $properties as $name => $value )
			$this->$name = maybe_unserialize($value);

		foreach( $this->rules as $rule ) // Capture code
			if ( isset($rule['property']) && 'Promo code' == $rule['property'] )
				$this->code = $rule['value'];

	}

}
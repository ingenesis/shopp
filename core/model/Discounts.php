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

	private $removed  	= array(); 	// List of removed discounts
	private $codes 		= array();	// List of applied codes
	private $request 	= false;	// Current code request
	private $credit 	= false;	// Credit request types
	private $shipping 	= false;	// Track shipping discount changes

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
				$deferred[] = $Discount; // Percentage off the order must be deferred to after all other discounts
				continue;
			} elseif ( ShoppOrderDiscount::CREDIT == $Discount->type() ) continue;

			$Discount->calculate();
			$discounts[] = $Discount->amount();
		}

		foreach ( $deferred as $Discount ) {
			$amount = array_sum($discounts);
			$Discount->calculate($amount);
			$discounts[] = $Discount->amount();
		}

		$amount = array_sum($discounts);

		$Cart = ShoppOrder()->Cart;
		if ( $Cart->total('order') < $amount )
			$amount = $Cart->total('order');

		return (float) $amount;

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
		$Promotions->sort( array($this, 'sortapplied'), 'keys' );

		// Reset shipping flag to track changes
		$this->shipping = false;

		// Iterate over each promo to determine whether it applies
		$discount = 0;
		foreach ( $Promotions as $Promo ) {

			// Cancel matching if max number of discounts reached
			if ( $this->maxed($Promo) && ! $this->applied($Promo) ) continue;

			$apply   = false;
			$matches = 0;
			$total   = 0;

			// Match the promo rules against the cart properties
			foreach ( $Promo->rules as $index => $rule ) {
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

			if ( apply_filters('shopp_apply_discount', $apply, $Promo) )
				$this->apply($Promo); // Add the Promo as a new discount
			else $this->reset($Promo);

		} // End promos loop

		// Check for failed promo codes
		if ( empty($this->request) || $this->codeapplied( $this->request ) ) return;

		if( $this->validcode($this->request) ) {
			shopp_add_error( Shopp::__('&quot;%s&quot; does not apply to the current order.', $this->request) );
			$this->request = false;
		} else {
			shopp_add_error( Shopp::__('&quot;%s&quot; is not a valid code.', $this->request) );
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

		$exists = $this->exists($Promo->id);
		$itemrules = isset($Promo->rules['item']);

		// If it already is applied and the promo does not have item rules
		if ( $exists && ! $itemrules ) return;

		$this->applycode($Promo); // Apply the appropriate discount code from the promo

		// Build the discount to apply to the order
		$Discount = new ShoppOrderDiscount();
		$Discount->ShoppOrderPromo($Promo);

		// Check matching line item discount targets to apply the discount to
		if ( $itemrules ) {

			$this->items($Promo, $Discount);
			$items = $Discount->items();

			if ( empty($items) ) { // No items match
				if ( $exists ) $this->reset($Promo); // If it was applied, remove the discount
				return; // Do not apply the discount
			}

		}

		$this->add($Promo->id, $Discount);
		$this->shipping($Discount); // Flag shipping changes

	}

	private function applied ( ShoppOrderPromo $Promo ) {
		return $this->exists($Promo->id);
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
				if ( $ItemRule->match($Item) && ! $Discount->hasitem($id) ) {
					$matches++;
				}
			}

			if ( apply_filters( 'shopp_discount_cart_item_matches', count( $rules ) === $matches, $Item, $Discount ) ) { // all conditions must match
				$Discount->item( $Item );
			}

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
		$request = $this->request();

		if ( empty($request) ) return; // Skip if there is no code request was made

		// Determine which promocode matched
		$rules = array_filter($Promo->rules, array($this, 'coderules'));

		foreach ( $rules as $rule ) {

			$CodeRule = new ShoppDiscountRule($rule, $Promo);

			if ( $CodeRule->match() )
				$this->addcode($rule['value'], $Promo);

		}

	}

	/**
	 * Adds an applied discount code
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $code The code to apply
	 * @param ShoppOrderPromo $Promo The promotion the code comes from
	 * @return boolean True if successful, false otherwise
	 **/
	public function addcode ( $code, ShoppOrderPromo $Promo ) {

		$code = trim(strtolower($code));
		$request = strtolower($this->request());

		// Prevent customers from reapplying codes
		if ( ! empty($request) && $code == $request && $this->codeapplied($code) ) {
			shopp_add_error( Shopp::__('&quot;%s&quot; has already been applied.', esc_html($code)) );
			$this->request = false; // Reset request after the request is processed
			return false;
		}

		if ( ! $this->codeapplied( $code ) )
			$this->codes[ $code ] = array();

		$this->codes[ $code ] = $Promo->id;
		$Promo->code = $code;

		$this->request = false; // Reset request after the request is successfully processed (#2808)

		return true;
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
	private function undiscount ( $id ) {

		if ( ! $this->exists($id) ) return false;

		$Discount = $this->get($id);
		$Discount->unapply();

		$_REQUEST['cart'] = true;

		$this->remove($Discount->id()); // Remove it from the discount stack if it is there
		if ( $this->shipping($Discount) ) // Flag shipping changes
			$Discount->shipfree(false);   // Remove free shipping if this is a free shipping discount (@see #2885)

		if ( isset($this->codes[ $Discount->code() ]) ) {
			unset($this->codes[ $Discount->code() ]);
			return;
		}

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
		$this->undiscount((int)$Promo->id);
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
	 * Get or set the current request
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $request The request string to set
	 * @return string The current request
	 **/
	public function request ( $request = null ) {

		if ( isset($request) ) $this->request = $request;
		return $this->request;

	}

	public function credit ( $request = null ) {

		if ( isset($request) ) $this->credit = $request;
		return $this->credit;

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

		if ( isset($_REQUEST['discount']) && ! empty($_REQUEST['discount']) )
			$this->request( trim($_REQUEST['discount']) );
		elseif ( isset($_REQUEST['credit']) && ! empty($_REQUEST['credit']) )
			$this->credit( trim($_REQUEST['credit']) );

		if ( isset($_REQUEST['removecode']) && ! empty($_REQUEST['removecode']) )
			$this->undiscount(trim($_REQUEST['removecode']));

	}

	/**
	 * Applies credits to the order discounts register
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return float The total amount credited
	 **/
	public function credits () {
		$credits = array();

		$CartTotals = ShoppOrder()->Cart->Totals;

		// Wipe out any existing credit calculations
		$CartTotals->takeoff('discount', 'credit');
		$discounts = $CartTotals->total('discount'); // Resum the applied discounts (without credits)

		foreach ( $this as $Discount ) {
			if ( $Discount->type() != ShoppOrderDiscount::CREDIT ) continue;
			$Discount->calculate(); // Recalculate based on current total to apply an appropriate amount
			$credits[] = $Discount->amount();
			// need to save the credit to the discount register before calculating again
			$CartTotals->register( new OrderAmountDiscount( array('id' => 'credit', 'amount' => $Discount->amount() ) ) );
		}

		$amount = array_sum($credits);
		if ( $amount > 0 )
			$CartTotals->register( new OrderAmountDiscount( array('id' => 'credit', 'amount' => $amount ) ) );

		return (float) $amount;
	}


	/**
	 * Report shipping discount changes
	 *
	 * @author Jonathan Davis
	 * @since 1.3.2
	 *
	 * @param ShoppOrderDiscount $Discount (optional) The applied or unapplied discount to check for shipping changes
	 * @return boolean True if shipping discounts changed, false otherwise
	 **/
	public function shipping ( ShoppOrderDiscount $Discount = null ) {

		if ( isset($Discount) && ShoppOrderDiscount::SHIP_FREE == $Discount->type() )
			$this->shipping = true;

		return $this->shipping;
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
	public function codeapplied ( $code ) {
		return isset( $this->codes[ strtolower($code) ]);
	}

	/**
	 * Checks if a given code is attached to a valid rule
	 *
	 * @author Matthew Sigley
	 * @since 1.3
	 *
	 * @param string $code The code to check
	 * @return boolean True if the code is valid, false otherwise
	 **/
	public function validcode ( $code ) {
		$Promotions = ShoppOrder()->Promotions;

		foreach( $Promotions as $promo ) {
			if( empty($promo->code) ) continue;
			if( strtolower($code) == strtolower($promo->code) ) return true;
		}
		return false;
	}

	/**
	 * Provides a list of the codes applied
	 *
	 * @clifgriffin U CAN HAZ INTERFACE
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of applied codes
	 **/
	public function codes () {
		return array_keys($this->codes);
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

	private $promo 		= false;		// A reference to the originating promotion object
	private $property 	= false;		// The rule property name
	private $logic 		= false;		// The logical comparison operation to match with
	private $value 		= false;		// The value to match

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
			if ( property_exists($this, $name) )
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

		if ( is_callable( $subject ) ) {
			// If the subject is a callback, use it for matching
			return call_user_func( $subject, $Item, $this );
		} else {
			// Evaluate the subject using standard matching
			return $this->evaluate( $subject );
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
			case 'sku':
			case 'tag name':
			case 'total price':
			case 'unit price':
			case 'variant':
			case 'variation':		return array($this, 'items');

			case 'promo code':		return array($this, 'code');

			case 'promo use count':		return $this->promo->uses;

			case 'discounts applied':	return ShoppOrder()->Discounts->count();
			case 'total quantity':		return $Cart->total('quantity');
			case 'shipping amount':		return $Cart->total('shipping');
			case 'subtotal amount':		return $Cart->total('order');
			case 'customer type':		return ShoppOrder()->Customer->type;
			case 'ship-to country':		return ShoppOrder()->Shipping->country;
			default:			return apply_filters('shopp_discounts_subject_' . sanitize_key($property), false, $this->promo);
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
	 * Determine the item subject data and match against it.
	 *
	 * @param ShoppCartItem $Item The Item to match against
	 * @return boolean True if match, false for no match
	 **/
	private function items ( ShoppCartItem $Item = null ) {
		// Are we matching against a specific, individual item?
		if ( null !== $Item ) return $this->item( $Item );

		// Do we have items in the cart?
		$items = shopp_cart_items();
		if ( empty( $items ) ) return false;

		// If we do, let's see if any of them yield a match
		foreach ( $items as $Item )
			if ( true === $this->item( $Item ) ) return true;

		return false;
	}

	/**
	 * Match the rule against a specific item.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item The Item to match against
	 * @return boolean True if match, false for no match
	 **/
	private function item ( ShoppCartItem $Item = null ) {
		if ( ! isset($Item) ) return false;

		$property = strtolower($this->property);

		switch ( $property ) {
			case 'total price':
			case 'any item amount':		$subject = (float) $Item->total; break;
			case 'name':
			case 'any item name':		$subject = $Item->name; break;
			case 'quantity':
			case 'any item quantity':	$subject = (int) $Item->quantity; break;
			case 'category':		$subject = (array) $Item->categories; break;
			case 'discount amount':		$subject = (float) $Item->discount; break;
			case 'sku':			$subject = $Item->sku; break;
			case 'tag name':		$subject = (array) $Item->tags; break;
			case 'unit price':		$subject = (float) $Item->unitprice; break;
			case 'variant':
			case 'variation':		$subject = $Item->option->label; break;
			case 'input name':		$subject = $Item->data; break;
			case 'input value':		$subject = $Item->data; break;

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
	public function evaluate ( $subject ) {

		$property = $this->property;
		$op       = strtolower($this->logic);
		$value    = $this->value;

		// To make evaluate work correctly when
		// $subject includes htmlencoded characters like &amp;
		if ( is_array($subject) ) {
        	foreach ( $subject as $key => $sub )
        		$subject[ $key ] = htmlspecialchars_decode($sub);
        } elseif ( is_string($subject) ) {
			$subject = htmlspecialchars_decode($subject);
		}

		switch( $op ) {
			// String or Numeric operations
			case 'is equal to':

				$type = 'string';
			 	if ( isset(ShoppPromo::$values[ $property ]) && 'price' == ShoppPromo::$values[ $property ] )
					$type = 'float';

				return $this->isequalto($subject, $value, $type);

			case 'is not equal to':

				$type = 'string';
			 	if ( isset(ShoppPromo::$values[ $property ]) && 'price' == ShoppPromo::$values[ $property ] )
					$type = 'float';

				return ! $this->isequalto($subject, $value, $type);

			// String operations
			case 'contains':				return $this->contains($subject, $value);
			case 'does not contain':			return ! $this->contains($subject, $value);
			case 'begins with': 				return $this->beginswith($subject, $value);
			case 'ends with':				return $this->endswith($subject, $value);

			// Numeric operations
			case 'is greater than':				return (Shopp::floatval($subject, false) >  Shopp::floatval($value, false));
			case 'is greater than or equal to':		return (Shopp::floatval($subject, false) >= Shopp::floatval($value, false));
			case 'is less than':				return (Shopp::floatval($subject, false) <  Shopp::floatval($value, false));
			case 'is less than or equal to':		return (Shopp::floatval($subject, false) <= Shopp::floatval($value, false));
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

		return ( false !== stripos($subject, $value) );
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

		return 0 === stripos($subject, $value);

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

		return stripos($subject, $value) === strlen($subject) - strlen($value);

	}

	public function get_property() {
		return $this->property;
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
	const AMOUNT_OFF 	= 1;
	const PERCENT_OFF 	= 2;
	const SHIP_FREE 	= 4;
	const BOGOF 		= 8;
	const CREDIT 		= 16;

	// Discount targets
	const ITEM 	= 1;
	const ORDER = 2;

	private $id 		= false;			// The originating promotion object id
	private $name 		= '';				// The name of the promotion
	private $amount 	= 0.00;				// The total amount of the discount
	private $type 		= self::AMOUNT_OFF;		// The discount type
	private $target 	= self::ORDER;			// The discount target
	private $discount 	= false;			// The calculated discount amount
	private $code 		= false;			// The code associated with the discount
	private $shipfree 	= false;			// A flag for free shipping
	private $items 		= array();			// A list of items the discount applies to

	/**
	 * Converts a ShoppOrderPromo object to a Discount object
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppOrderPromo $Promo The promotion object to convert
	 * @return void
	 **/
	public function ShoppOrderPromo ( ShoppOrderPromo $Promo ) {
		$this->id((int)$Promo->id);
		$this->name($Promo->name);
		$this->code($Promo->code);

		$target = $this->target($Promo->target);
		$type = $this->type($Promo->type);
		$this->discount = $Promo->discount;

		if ( self::BOGOF == $type )
			$this->discount = array($Promo->buyqty, $Promo->getqty);

		$this->calculate();
	}
	/**
	 * Determines if the discount applies to (affects) the order
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if the discount affects the order, false otherwise
	 **/
	public function applies () {
		$applies = ( $this->amount() > 0 || ( $this->amount() == 0 && $this->shipfree() ) || count($this->items) > 0 );
		return apply_filters('shopp_discount_applies', $applies, $this );
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
	public function id ( $id = null ) {
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
	public function name ( $name = null ) {
		if ( isset($name) ) $this->name = $name;

		if ( $this->type() == self::CREDIT ) // Add remaining
			return $this->name . ' ' . Shopp::__('(%s remaining)', money($this->discount() - $this->amount()));

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
		$Cart = ShoppOrder()->Cart;

		switch ( $this->type ) {
			case self::SHIP_FREE:	if ( self::ORDER == $this->target ) $this->shipfree(true); $this->amount = 0; break;
			case self::AMOUNT_OFF:	$this->amount = $this->discount(); break;
			case self::CREDIT:
				$total = $Cart->total();

				// Find current credits applied
				$credit = 0;
				if ( $credits = $Cart->Totals->entry('discount', 'credit') && method_exists($credits, 'amount') )
					$credit = $credits->amount();

				$this->amount = min($this->discount(), $total);

				break; // Apply the smaller of either the order total or available discount
			case self::PERCENT_OFF:
				$subtotal = $Cart->total('order');
				if ( $discounts > 0 ) $subtotal -= $discounts;
				$this->amount = $subtotal * ($this->discount() / 100);
				break;
		}

		if ( ! empty($this->items) ) {
			$removed = array();
			$discounts = array();
			foreach ( $this->items as $id => $unitdiscount ) {
				$Item = $Cart->get($id);
				if ( empty($Item) ) {
					$removed[] = $id;
					continue;
				}

				if ( self::BOGOF == $this->type() ) {
					if ( ! is_array( $Item->bogof) ) $Item->bogof = array();
					$Item->bogof[ $this->id() ] = $unitdiscount;
				} else $Item->discount += $unitdiscount;

				// Track prior discounts applied to the item
				$itemdiscounts = $Item->discounts;

 			   	// Recalculate Item discounts & taxes
 				$Item->discounts();
				$Item->taxes();

				// Add any new discount amount to the stack (prevents compounding percentage discounts)
				if ( $Item->discounts - $itemdiscounts > 0 )
					$discounts[] = ($Item->discounts - $itemdiscounts);
			}

			foreach ( $removed as $id )
				unset($this->items[ $id ]);

			if ( empty($this->items) )
				$this->unapply();

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
	public function code ( $code = null ) {
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
	public function shipfree ( $shipfree = null ) {
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
	public function type ( $type = null ) {
		if ( isset($type) ) {
			switch ( strtolower($type) ) {
				case 'percentage off':		$this->type = self::PERCENT_OFF; break;
				case 'amount off':		$this->type = self::AMOUNT_OFF; break;
				case 'free shipping':		$this->type = self::SHIP_FREE; break;
				case 'buy x get y free':	$this->type = self::BOGOF; break;
				default:			if ( is_int($type) ) $this->type = $type;
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
	public function target ( $target = null ) {
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
	public function discount ( $amount = null ) {

		if ( isset($amount) ) $this->discount = (float) $amount;
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
		$amount = 0;

		// These must result in the discount applied to the *unit price*!
		switch ( $this->type ) {
			case self::PERCENT_OFF:	$amount = $Item->unitprice * ($this->discount() / 100); break;
			case self::AMOUNT_OFF:	$amount = $this->discount(); break;
			case self::SHIP_FREE:	$Item->freeshipping = true; $this->shipping = true; $amount = 0; break;
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

		$amount                              = apply_filters( 'shopp_discount_item_amount', (float) $amount, $Item, $this );
		$this->items[ $Item->fingerprint() ] = $amount;

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
	public function hasitem ( $key ) {
		return isset($this->items[ $key ]);
	}

	/**
	 * Unapply the discount
	 *
	 * This primarily involves resetting the Cart Item->freeshipping property.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void Description...
	 **/
	public function unapply () {
		$Cart = ShoppOrder()->Cart;

		if ( self::SHIP_FREE == $this->type ) {
			$Shiprates = ShoppOrder()->Shiprates;
			foreach ( $this->items as $id => $item ) {
				$CartItem = $Cart->get($id);
				$CartItem->freeshipping = false;
			}
		}

	}

}

/**
 * Storage class for discounts applied to a purchase and saved in a ShoppPurchase record
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopp
 **/
class ShoppPurchaseDiscount {

	public $id 		= false;				// The originating promotion object id
	public $name 		= '';					// The name of the promotion
	public $amount 		= 0.00;					// The total amount of the discount
	public $type 		= ShoppOrderDiscount::AMOUNT_OFF;	// The discount type
	public $target 		= ShoppOrderDiscount::ORDER;		// The discount target
	public $discount 	= false;				// The calculated discount amount (float or array for BOGOFs)
	public $code 		= false;				// The code associated with the discount
	public $shipfree 	= false;				// A flag for free shipping

	public function __construct ( ShoppOrderDiscount $Discount ) {

		$this->id 	= $Discount->id();
		$this->name 	= $Discount->name();
		$this->type 	= $Discount->type();
		$this->target 	= $Discount->target();
		$this->discount = $Discount->discount();
		$this->code 	= $Discount->code();
		$this->shipfree = $Discount->shipfree();
		$this->amount 	= $Discount->amount();

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
	public function __construct () {
		Shopping::restore( 'promos', $this->promos );
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

		$table = ShoppDatabaseObject::tablename(ShoppPromo::$table);
		$where = array(
			"status='enabled'",
			ShoppPromo::activedates(),
			"target IN ('" . join("','", self::$targets) . "')"
		);
		$orderby = 'target DESC';

		$queryargs = compact('table', 'where', 'orderby');
		$query     = sDB::select( $queryargs );
		$loaded    = sDB::query($query, 'array', array('ShoppPromotions', 'loader') );

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

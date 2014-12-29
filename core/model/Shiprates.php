<?php
/**
 * Shiprates.php
 *
 * Provides shipping service rate options
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage shiprates
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Finds applicable service rates
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shiprates
 **/
class ShoppShiprates extends ListFramework {

	private $selected = false;		// The currently selected shipping method
	private $fees = array();		// Tracks per-item (key) merchant shipping fees (value)
	private $shippable = array();	// Tracks the shippable item ids (key) and if they are free (value)
	private $free = false;			// Free shipping
	private $realtime = false;		// Flag for when realtime shipping systems are enabled

	private $request = false;		// The generated request checksum
	private $track = array();		// modules register properties for the change checksum hash

	/**
	 * Determines if the shipping system is disabled
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function disabled () {

		// If shipping is disabled
		if ( ! shopp_setting_enabled('shipping') ) return true;

		return false;
	}

	/**
	 * Returns the currently selected shiprate service
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $selected (optional) The slug to set as the selected shiprate service option
	 * @return ShoppShiprateService The currently selected shiprate service
	 **/
	public function selected ( $selected = null ) {

		if ( is_null($selected) ) {
			if ( ! $this->exists( $this->selected ) )
				return false;
		}

		if ( $this->exists( $selected ) )
			$this->selected = $selected;

		return $this->get( $this->selected );

	}

	/**
	 * Add a shippable item to track properties
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppShippableItem $Item A ShoppShippableItem compatible item
	 * @return void
	 **/
	public function item ( ShoppShippableItem $Item ) {

		if ( ! $Item->shippable ) return; // Don't track the item

		$this->shippable[ $Item->id ] = $Item->shipsfree;
		$this->fees[ $Item->id ] = ($Item->fees * $Item->quantity);

	}

	/**
	 * Remove a line item shipping entry if it exists
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $id The item id to remove
	 * @return void
	 **/
	public function takeoff ( $id ) {

		if ( isset($this->shippable[ $id ]) )
			unset($this->shippable[ $id ]);


		if ( isset($this->fees[ $id ]) )
			unset($this->fees[ $id ]);

	}

	/**
	 * Provides the total custom merchant-defined shipping fees
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return float The shipping fee amount
	 **/
	public function fees ( ShoppShiprateService $Service ) {
		return (float) apply_filters('shopp_shipping_fees', shopp_setting('order_shipfee') + array_sum($this->fees), $Service, $this->fees );
	}

	/**
	 * Checks or sets if shipping is free
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param boolean $free Flag to set the free shipping value
	 * @return boolean True if free, false otherwise
	 **/
	public function free ( $free = null ) {

		if ( isset($free) ) // Override the free setting if the free flag is set
			$this->free = $free;

		return $this->free;
	}

	public function realtime () {
		return $this->realtime;
	}

	/**
	 * Returns the amount of the currently selected service
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return float The cost amount of the selected shiprate service
	 **/
	public function amount () {

		$selection = $this->selected();
		if ( false === $selection ) return false;	// Check selection first, since a selection must be made
		$amount = $selection->amount;				// regardless of free shipping

		// Override the amount for free shipping or when all items in the order ship free
		if ( $this->free() || count($this->shippable) == array_sum($this->shippable) ) $amount = 0;

		return (float)$amount;
	}

	/**
	 * Adds data tracking to check for request changes
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $name The name of the value to track
	 * @param mixed $value The data to track stored as a reference
	 * @return void
	 **/
	public function track ( $name, &$value ) {
		$this->track[ $name ] = &$value;
	}

	/**
	 * Determines if any shipping services are available
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if there are shipping services, false if not or if shipping is disabled
	 **/
	public function exist () {

		if ( $this->disabled() ) return false;

		if ( $this->count() == 0 ) return false;

		return true;

	}

	/**
	 * Calculates the shipping rate amounts using active shipping modules
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return float The shipping rate service amount, or false if disabled
	 **/
	public function calculate () {

		if ( $this->disabled() ) return (float) 0;			// Shipping disabled

		if ( $this->free() ) return (float) 0;			// Free shipping for this order

		if ( empty($this->shippable) ) {					// No shippable items in the order
			parent::clear();								// Clear any current rates
			return (float) 0;							// Don't calculate any new rates
		}

		if ( $this->requested() ) 						// Return the current amount if the request hasn't changed
			return (float)$this->amount();

		do_action('shopp_calculate_shipping_init');		// Initialize shipping modules

		parent::clear();									// clear existing rates before we pull new ones

		$this->items();									// Send items to shipping modules that package them

		$this->modules();								// Calculate active shipping module service methods

		$lowest = $this->lowrate();						// Find the lowest cost option to use as a default selection

		// If nothing is currently, select the lowest cost option
		if ( ! $this->selected() && false !== $lowest )
			$this->selected( $lowest->slug );

		do_action('shopp_shiprates_calculated');

		// Return the amount
		return (float)$this->amount();

	}

	public function clear () {
		parent::clear();

		$this->free = false;
		$this->shippable = array();
		$this->fees = array();
		$this->track = array();

	}

	/**
	 * Provides shippable items to shipping rate calculator modules
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	private function items () {
		foreach ( $this->shippable as $id => $free ) {

			$CartItem = shopp_cart_item($id);
			if ( ! $CartItem ) continue;

			$Item = new ShoppShippableItem( $CartItem );
			$this->item($Item);
			do_action('shopp_calculate_item_shipping', $id, $Item);
		}
	}

	/**
	 * Runs the shipping module calculations to populate the applicable shipping service rate options
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	private function modules () {

		$Notices = ShoppErrorStorefrontNotices();
		$notices = $Notices->count();
		$services = array();

		// Run shipping module aggregate shipping calculations
		do_action_ref_array('shopp_calculate_shipping', array(&$services, ShoppOrder() ));

		// No shipping options were generated, try fallback calculators for realtime rate failures
		if ( empty($services) && $this->realtime ) {
			do_action('shopp_calculate_fallback_shipping_init');
			do_action_ref_array('shopp_calculate_fallback_shipping', array(&$services, ShoppOrder() ));
		}

		if ( empty($services) ) return false; // Still no rates, bail

		// Suppress new errors from shipping systems if there are services available
		$newnotices = $Notices->count() - $notices;
		if ( $newnotices > 0 ) $Notices->rollback($newnotices);

		// Add all order shipping fees and item shipping fees
		foreach ( $services as $service )
			$service->amount += $this->fees($service);

		parent::clear();
		$this->populate($services);
		// $this->sort('self::sort');

	}

	/**
	 * Determines the lowest cost
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return ShoppShiprateService
	 **/
	private function lowrate () {

		$estimate = false;
		foreach ($this as $name => $option) {

			// Skip if not to be included
			if ( ! $option->estimate ) continue;

			// If the option amount is less than current estimate
			// Update the estimate to use this option instead
			if ( ! $estimate || $option->amount < $estimate->amount )
				$estimate = $option;
		}

		return $estimate;

	}

	/**
	 * Determines if the request has changed
	 *
	 * This method uses a fast hash to checksum all of the variable
	 * data that might be used to calculate shipping service rates.
	 * The last request is kept and checked against the current
	 * request to see if anything has changed. If nothing has
	 * changed, the shipping calculations can be skipped and the
	 * current shipping service rates are kept along with the current
	 * shipping rate amount.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if the current request is the same as the prior request
	 **/
	private function requested () {

		if ( is_string($this->track) ) $request = $this->track;
		else $request = hash('crc32b', serialize($this->track));

		// If the request is the same and we have some shipping options,
		// then this request has been made, don't re-run all the shipping modules
		if ( $this->request == $request && $this->count() > 0 ) return true;
		$this->request = $request;
		return false;
	}

	public function __sleep () {
		return array('selected','fees','shippable','free','request','_list','_added','_checks');
	}

}

/**
 * ShippingOption class
 *
 * A data structure for order shipping options
 *
 * @author Jonathan Davis
 * @since 1.1
 * @version 1.2
 * @package shopp
 * @subpackage shiprates
 **/
class ShoppShiprateService {

	public $name;				// Name of the shipping option
	public $slug;				// URL-safe name of the shipping option @since 1.2
	public $amount;				// Amount (cost) of the shipping option
	public $delivery;			// Estimated delivery of the shipping option
	public $estimate;			// Include option in estimate
	public $items = array();	// Item shipping rates for this shipping option

	/**
	 * Builds a shipping option from a configured/calculated
	 * shipping rate array
	 *
	 * Example:
	 * new ShippingOption(array(
	 * 		'name' => 'Name of Shipping Rate Method',
	 * 		'slug' => 'rate-method-slug',
	 * 		'amount' => 0.99,
	 * 		'delivery' => '1d-2d',
	 * 		'items' => array(
	 * 			0 => 0.99,
	 * 			1 => 0.50
	 * 		)
	 * ));
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $rate The calculated shipping rate
	 * @param boolean $estimate Flag to be included/excluded from estimates
	 * @return void
	 **/
	public function __construct ( array $rate, $estimate = true ) {

		if (!isset($rate['slug'])) // Fire off an error if the slug is not provided
			return ( ! shopp_debug('A slug (string) value is required in the $rate array parameter when constructing a new ShoppShiprateService') );

		$this->name = $rate['name'];
		$this->slug = $rate['slug'];
		$this->amount = $rate['amount'];
		$this->estimate = $estimate;

		if ( ! empty($rate['delivery']) )
			$this->delivery = $rate['delivery'];
		if ( ! empty($rate['items']) )
			$this->items = $rate['items'];
	}

} // END class ShippingOption

if ( ! class_exists('ShippingOption',false) ) {
	class ShippingOption extends ShoppShiprateService {
	}
}

/**
 * Converts a line item object to on that is compatible with the ShoppShiprates system
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shiprates
 **/
class ShoppShippableItem {

	private $class;
	private $Object;

	public $shippable = false;
	public $id = false;
	public $quantity = 0;
	public $fees = 0;
	public $weight = 0;
	public $length = 0;
	public $width = 0;
	public $height = 0;
	public $shipsfree = false;

	function __construct ( $Object ) {

		$this->Object = $Object;
		$this->class = get_class($Object);

		switch ( $this->class ) {
			case 'ShoppCartItem': $this->ShoppCartItem(); break;
		}

	}

	function ShoppCartItem () {
		$Item = $this->Object;
		$this->shippable = $Item->shipped;

		if ( ! $this->shippable ) return false;

		$this->id        = $Item->fingerprint();
		$this->quantity  =& $Item->quantity;
		$this->fees      =& $Item->shipfee;
		$this->weight    =& $Item->weight;
		$this->length    =& $Item->length;
		$this->width     =& $Item->width;
		$this->height    =& $Item->height;
		$this->shipsfree =& $Item->freeshipping;
	}

}
<?php
/**
 * Tax.php
 * Tax manager
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @version 1.0
 * @since 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppTax
 *
 * Manages order total registers
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1.0
 * @package taxes
 **/
class ShoppTax {

	const ALL = '*';			// Wildcard for all locations
	const EUVAT = 'EUVAT';		// Special country value for European Union

	private $address = array(	// The address to apply taxes for
		'country' => false,
		'zone' => false,
		'locale' => false
	);

	private $Item = false;		// The ShoppTaxableItem to calculate taxes for
	private $Customer = false;	// The current ShoppCustomer to calculate taxes for

	/**
	 * Converts a provided item to a ShoppTaxableItem
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param Object $Item An item object to convert to a ShoppTaxableItem
	 * @return void
	 **/
	public function item ( $Item ) {
        $this->Item = is_a($Item, 'ShoppTaxableItem') ? $Item : new ShoppTaxableItem($Item);
        return $this->Item;
	}

	/**
	 * Filters the tax settings based on
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array A list of tax rate settings
	 **/
	public function settings () {
		if ( ! shopp_setting_enabled('taxes') ) return false;

		$taxrates = shopp_setting('taxrates');

		$fallbacks = array();
		$settings = array();
		foreach ( (array) $taxrates as $setting ) {

			$defaults = array(
				'rate' => 0,
				'country' => '',
				'zone' => '',
				'haslocals' => false,
				'logic' => 'any',
				'rules' => array(),
				'localrate' => 0,
				'compound' => false,
				'label' => Shopp::__('Tax')

			);
			$setting = array_merge($defaults, $setting);
			extract($setting);

			if ( ! $this->taxcountry($country) ) continue;
			if ( ! $this->taxzone($zone) ) continue;
			if ( ! $this->taxrules($rules, $logic) ) continue;

			// Capture fall back tax rates
			if ( empty($zone) && ( self::ALL == $country || self::EUVAT == $country ) ) {
				$fallbacks[] = $setting;
				continue;
			}

			$key = hash('crc32b', serialize($setting)); // Key settings before local rates

			$setting['localrate'] = 0;
			if ( isset($setting['locals']) && is_array($setting['locals']) && isset($setting['locals'][ $this->address['locale'] ]) )
				$setting['localrate'] = $setting['locals'][ $this->address['locale'] ];

			$settings[ $key ] = $setting;
		}

		if ( empty($settings) && ! empty($fallbacks) )
			$settings = $fallbacks;

		$settings = apply_filters('shopp_cart_taxrate_settings', $settings); // @deprecated Use shopp_tax_rate_settings instead
		return apply_filters('shopp_tax_rate_settings', $settings);
	}

	/**
	 * Determines the applicable tax rates for a given taxable item
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param Object $Item A taxable item object
	 * @return array The list of applicable tax rates as ShoppItemTax entries for a given item
	 **/
	public function rates ( array &$rates, ShoppTaxableItem $Item = null  ) {

		if ( isset($Item) ) $this->Item = $Item;
		if ( ! is_array($rates) ) $rates = array();

		$settings = $this->settings();        
        
        $baserate = self::baserate($this->Item);
        
		foreach ( (array) $settings as $key => $setting ) {

			// Add any local rate to the base rate, then divide by 100 to prepare the rate to be applied
			$rate = self::taxrate($setting);

			if ( ! isset($rates[ $key ]) ) $rates[ $key ] = new ShoppItemTax();
			$ShoppItemTax = $rates[ $key ];

			$ShoppItemTax->update(array(
				'label' => $setting['label'],
				'rate' => $rate,
                'baserate' => $baserate,
				'amount' => 0.00,
				'total' => 0.00,
				'compound' => $setting['compound']
			));

		}
        
		// get list of existing rates that no longer match
		$unapply = array_keys(array_diff_key($rates, (array) $settings));
		foreach ( $unapply as $key )
			$rates[ $key ]->amount = $rates[ $key ]->total = null;

		if ( empty($settings) ) $rates = array();

		$rates = apply_filters( 'shopp_cart_taxrate', $rates ); // @deprecated Use shopp_tax_rates
		$rates = apply_filters( 'shopp_tax_rates', $rates );

	}
    
	/**
	 * Converts a tax rate setting to a decimal rate to use for tax calculations
	 *
	 * @author Jonathan Davis
	 * @since 1.4
	 *
	 * @param array $setting A tax rate setting to convert
	 * @return float The effective decimal rate
	 **/
    protected static function taxrate ($setting) {
        $rate = isset($setting['rate']) ? self::float($setting['rate']) : 0;
        $localrate = isset($setting['localrate']) ? self::float($setting['localrate']) : 0;
        
        return ( $rate + $localrate ) / 100;
    }

	/**
	 * Evaluates if the given country matches the taxable address
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $country The country code
	 * @return boolean True if the country matches or false
	 **/
	protected function taxcountry ( $country ) {
		if ( empty($country) ) return false;
		$EU = self::EUVAT == $country && in_array($this->address['country'], Lookup::country_euvat());
		return apply_filters('shopp_tax_country', ( self::ALL == $country || $EU || $this->address['country'] == $country ),  $this->address['country'], $country);
	}

	/**
	 * Evaluates if the given zone (state/province) matches the taxable address
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $zone The name of the zone
	 * @return boolean True if the zone matches or false
	 **/
	protected function taxzone ( $zone ) {
		if ( empty($zone) ) return true;
		return ($this->address['zone'] == $zone);
	}

	/**
	 * Evaluates the tax rules against the taxable Item or ShoppCustomer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $rules The list of tax rules to test
	 * @return boolean True if the rules match enough to apply, false otherwise
	 **/
	protected function taxrules ( array $rules, $logic ) {
		if ( empty($rules) ) return true;

		$apply = false;
		$matches = 0;
		foreach ( $rules as $rule ) {
			$match = false;
			if ( is_a($this->Item, 'ShoppTaxableItem') && false !== strpos($rule['p'],'product') ) {
				$match = $this->Item->taxrule($rule);
			} elseif ( is_a($this->Customer, 'ShoppCustomer') && false !== strpos($rule['p'],'customer') ) {
				switch ( $rule['p'] ) {
					case "customer-type": $match = strtolower($rule['v']) == strtolower($this->Customer->type); break;
				}
			}

			if ( $match ) $matches++;
		}

		if ( 'any' == $logic && $matches > 0 ) $apply = true;
		if ( 'all' == $logic && count($rules) == $matches ) $apply = true;

		return apply_filters('shopp_tax_rate_match_rule', $apply, $rule, $this);
	}


	/**
	 * Sets the taxable address for applying the proper tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 * @deprecated Use ShoppTax->location()
	 *
	 * @param BillingAddress $Billing The billing address
	 * @param ShippingAddress $Shipping The shipping address
	 * @param boolean $shipped Flag if the order is shippable
	 * @return array An associative array containing the country, zone and locale
	 **/
	public function address ( BillingAddress $Billing, ShippingAddress $Shipping = null, $shipped = false ) {
		$Address = $Billing;
		if ( $shipped && null !== $Shipping || shopp_setting_enabled('tax_destination') ) // @todo add setting for "Apply tax to the shipping address"
			$Address = $Shipping;

		$country = $Address->country;
		$zone = $Address->state;
		$locale = false;

		// Locale is always tracked with the billing address even though it is may be a shipping locale
		if ( isset($Billing->locale) ) $locale = $Billing->locale;

		$this->address = array_merge(apply_filters('shopp_taxable_address', compact('country','zone','locale')));

		return $this->address;
	}

	/**
	 * Sets the taxable location (address) for matching tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3.2
	 *
	 * @param string $country The country code
	 * @param string $state The state name or code
	 * @param string $locale (optional) The locale name
	 * @return array An associative array containing the country, zone and locale
	 **/
	public function location ( $country = null, $state = null, $locale = null ) {
		$address = apply_filters('shopp_taxable_address', array(
			'country' => $country,
			'zone' => $state,
			'locale' => $locale
		));

		$this->address = array_merge($this->address, array_filter($address, create_function('$e', 'return ! is_null($e);')));

		return $this->address;
	}

	/**
	 * Sets the working customer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCustomer	$Customer The session customer to use for Customer rule conditions
	 * @return void
	 **/
	public function customer ( ShoppCustomer $Customer ) {
		$this->Customer = $Customer;
	}

	/**
	 * Provides the base rate that applies to the given product
	 *
	 * @since 1.3
	 * @param object $Item A compatible taxable object
	 * @return float The base tax rate
	 **/
	public static function baserate ( $Item = null ) {
		$BaseTax = new ShoppTax();
        $BaseTax->item($Item);
		$BaseTax->location(ShoppBaseLocale()->country(), false, false);
        
        $settings = $BaseTax->settings();
		return self::taxrate( reset($settings) );
	}

	/**
	 * Provides an adjustment amount based on EU tax zone differences
	 *
	 * The adjustment amount is a +/- difference to apply to a
	 * product or cart item price to account for the tax rate difference
	 * between the base of operations tax rate that applies to the item
	 * and the tax rate that applies to the current shipping location.
	 *
	 * @since 1.4
	 * @param float $amount The taxable amount
	 * @param array $rates The applicable tax rates for a taxable item
	 * @param object $Item A compatible taxable object
	 * @return float The signed adjustment amount
	 **/
	public static function adjustment ( $amount, $rates, $Item ) {
		if ( ! shopp_setting_enabled('tax_inclusive') ) return 0;

		$baserate = self::baserate($Item);
		$appliedrate = self::appliedrate($rates);

		$appliedrate = isset($appliedrate->rate) ? $appliedrate->rate : 0;
        
		if ( $baserate == $appliedrate )
			return 0;

		$netamount = (float)$amount / (1 + $baserate);
		$appliedtax = ( $netamount * $appliedrate );

		return ( $netamount + $appliedtax ) - $amount;
	}

	/**
	 * Provides a single applied rate from a list of taxrates
	 *
	 * Some tax amounts will get annulled when a tax setting is superceded by
	 * other tax rate settings. It is an important part of how the TaxTotal system
	 * detects differences to recalculate, but in some scenarios, these
	 * anulled rates need ignored to find the actual applicable tax rate.
	 *
	 * @since 1.4
	 * @param array $rates The list of applicable item tax rates
	 * @return ShoppItemTax The applied rate
	 **/
	public static function appliedrate ( array $rates ) {
		foreach ( $rates as $label => $rate )
			if ( ! is_null($rate->amount) )
				return $rate;
	}

	/**
	 * Formats tax rates to a precision beyond the currency format
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param scalar $amount An amount to convert to a float
	 * @return float The float amount
	 **/
	private static function float ( $amount ) {
		$format = ShoppBaseCurrency()->settings();
		$format['precision'] = 6; // Override the precision to 6 digits
		return Shopp::floatval($amount, true, $format);
	}

	/**
	 * Calculate taxes for a taxable amount using the given tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $rates A list of ShoppItemTax objects
	 * @param float $taxable The amount to calculate taxes on
	 * @return float The total tax amount
	 **/
	public static function calculate ( array &$rates, $taxable ) {
		$compound = $taxable;
		$total = 0;
		$inclusive = shopp_setting_enabled('tax_inclusive');
		foreach ( $rates as $label => $taxrate ) {

			if ( is_null($taxrate->total) ) continue; 		// Skip taxes flagged to be removed from the item

			$taxrate->amount = 0; // Reset the captured tax amount @see Issue #2430

			// Calculate tax amount
			$tax = self::tax($taxable, $taxrate);

			if ( $taxrate->compound ) {
				$tax = self::tax($compound, $taxrate);
				$compound += $tax;						 	// Set compound taxable amount for next compound rate
			}

			$taxrate->amount = $tax;						// Capture the tax amount calculate for this taxrate
			$total += $tax;									// Sum all of the taxes to get the total tax for the item

		}

		return $total;
	}

	/**
	 * Calculates the tax amount
	 *
	 * The tax calculation used is dependent on the inclusive tax setting
	 *
	 * @since 1.4
	 * @param float $amount The amount to calculate tax against
	 * @param ShoppItemTax $rate The tax rate
	 * @return float The tax amount
	 **/
	private static function tax ($amount, $rate) {
		$inclusive = shopp_setting_enabled('tax_inclusive');
        
		if ( $inclusive ) // Determine the net taxable amount by factoring out the baserate
            $amount = $amount / ( 1 + $rate->baserate);
            
        $tax = $amount * $rate->rate;

		return $tax;
	}

	/**
	 * Provides the tax exclusive amount of a given tax inclusive amount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $rates A list of ShoppItemTax objects
	 * @param float $amount The amount including tax
	 * @return float The amount excluding tax
	 **/
	public static function exclusive ( array &$rates, $amount ) {
		$taxrate = 0;
		foreach ( $rates as $tax )
			$taxrate += $tax->rate;
		return (float) $amount / (1 + $taxrate);
	}

	/**
	 * Calculates the total tax amount factored by quantity for the given tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $taxes the list of applicable ShoppItemTax entries
	 * @param int $quantity The quantity to factor tax amounts by
	 * @return float $total
	 **/
	public function total ( array &$taxes, $quantity ) {
		$total = 0;
		foreach ( $taxes as $label => &$tax ) {
			$tax->total = $tax->amount * $quantity;
			$total += $tax->total;
		}

		return (float)$total;
	}

	/**
	 * Determines if EU VAT applies in a given country and setting
	 *
	 * @since 1.3
	 * @param string $country The country code to check
	 * @param string $setting The tax rate setting country code
	 * @return bool True if EU VAT applies to the country, false otherwise
	 **/
	public static function euvat ( $country, $setting ) {
		if ( self::EUVAT != $setting ) return false;
		return in_array($country, Lookup::country_euvat());
	}

	/**
	 * Ensures the working address is serialized in the session
	 *
	 * @since 1.3
	 *
	 * @return array The list of properties to serialize
	 **/
	public function __sleep () {
		return array('address');
	}

}

/**
 * Adapter class that translates other product/item classes to a ShoppTax compatible object
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package taxes
 **/
class ShoppTaxableItem {

	private $class;
	private $Object;

	function __construct ( $Object ) {

		$this->Object = $Object;
		$this->class = get_class($Object);

	}

	/**
	 * Routes the tax rule comparison to the proper object class handler
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if the rule matches the value
	 **/
	public function taxrule ( $rule ) {

		$property = $rule['p'];
		$value = $rule['v'];

		if ( method_exists($this, $this->class) )
			return call_user_func(array($this, $this->class), $property, $value);

		return false;
	}

	/**
	 * Evaluates tax rules for ShoppCartItem objects
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $property The property to test
	 * @param string $value The value to match
	 * @return boolean True if matched or false
	 **/
	private function ShoppCartItem ( $property, $value ) {
		$CartItem = $this->Object;
		switch ( $property ) {
			case 'product-name': return ($value == $CartItem->name); break;
			case 'product-tags': return in_array($value, $CartItem->tags); break;
			case 'product-category': return in_array($value, $CartItem->categories); break;
		}
		return false;
	}

	/**
	 * Evaluates tax rules for Product objects
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $property The property to test
	 * @param string $value The value to match
	 * @return boolean True if matched or false
	 **/
	private function ShoppProduct ( $property, $value ) {
		$Product = $this->Object;
		switch ( $property ) {
			case 'product-name': return ($value == $Product->name); break;
			case 'product-tags':
				if ( empty($Product->tags) ) $Product->load_data( array('tags') );
				foreach ($Product->tags as $tag) if ($value == $tag->name) return true;
				break;
			case 'product-category':
				if ( empty($Product->categories) ) $Product->load_data( array('categories') );
				foreach ($Product->categories as $category) if ($value == $category->name) return true;
		}
		return false;
	}

	/**
	 * Evaluates tax rules for ShoppPurchased objects
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $property The property to test
	 * @param string $value The value to match
	 * @return boolean True if matched or false
	 **/
	private function purchased () {
		// @todo Complete ShoppPurchased tax rule match for ShoppTaxableItem
	}

}

/**
 * Defines a ShoppItemTax object
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package taxes
 **/
class ShoppItemTax extends AutoObjectFramework {

	public $label = '';
	public $rate = 0.00;
    public $baserate = 0.00;
	public $amount = 0.00;
	public $total = 0.00;
	public $compound = false;

}

/**
 * Storage class for taxes applied to a purchase and saved in a ShoppPurchase record
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package shopp
 **/
class ShoppPurchaseTax {

	public $id = false;						// The originating Tax object id
	public $name = '';						// The name of the Tax
	public $rate = 0.00;					// Tax rate
    public $baserate = 0.00;                // Tax rate at base of operations
	public $amount = 0.00;					// The total amount of taxes

	public function __construct ( OrderAmountTax $Tax ) {

		$this->id = $Tax->id();
		$this->name = $Tax->label();
		$this->rate = $Tax->rate();
		$this->amount = $Tax->amount();

	}

}
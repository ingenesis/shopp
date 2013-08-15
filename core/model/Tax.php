<?php
/**
 * Tax.php
 * Tax manager
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage taxes
 **/

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
		return new ShoppTaxableItem($Item);
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
		foreach ( $taxrates as $setting ) {

			$defaults = array(
				'rate' => 0,
				'country' => '',
				'zone' => '',
				'haslocals' => false,
				'logic' => 'any',
				'rules' => array(),
				'localrate' => 0,
				'compound' => false,
				'label' => __('Tax','Shopp')

			);
			$setting = array_merge($defaults,$setting);
			extract($setting);

			if ( ! $this->taxcountry($country) ) continue;
			if ( ! $this->taxzone($zone) ) continue;
			if ( ! $this->taxrules($rules) ) continue;

			// Capture fall back tax rates
			if ( self::ALL == $country && empty($zone) ) $fallbacks[] = $setting;

			$settings[] = $setting;

		}

		if ( empty($settings) && ! empty($fallbacks) ) $settings = $fallbacks;

		$settings = apply_filters('shopp_cart_taxrate_settings',$settings); // @deprecated Use shopp_tax_rate_settings instead
		return apply_filters('shopp_tax_rate_settings',$settings);
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

		$settings = $this->settings();
		foreach ($settings as $setting) {
			$localrate = false;
			if ( isset($setting['locals']) && is_array($setting['locals']) && isset($setting['locals'][ $this->address['locale'] ]) )
				$localrate = $setting['locals'][ $this->address['locale'] ];

			// Add any local rate to the base rate, then divide by 100 to prepare the rate to be applied
			$rate = ( self::float($setting['rate']) + self::float($localrate) ) / 100;

			$key = hash('crc32b', $setting['label'] . $rate );
			if ( ! isset($rates[ $key ]) ) $rates[ $key ] = new ShoppItemTax();
			$ShoppItemTax = $rates[ $key ];

			$ShoppItemTax->update(array(
				'label' => $setting['label'],
				'rate' => $rate,
				'amount' => 0.00,
				'total' => 0.00,
				'compound' => $setting['compound']
			));

		}

		$rates = apply_filters( 'shopp_cart_taxrate', $rates ); // @deprecated Use shopp_tax_rates
		$rates = apply_filters( 'shopp_tax_rates', $rates );

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
	protected function taxcountry ( string $country ) {
		if ( empty($country) ) return false;
		return ($this->address['country'] == $country || self::ALL == $country);
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
	protected function taxzone ( string $zone ) {
		if ( empty($zone) ) return true;
		return ($this->address['zone'] == $zone);
	}

	/**
	 * Evaluates the tax rules against the taxable Item or Customer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $rules The list of tax rules to test
	 * @return boolean True if the rules match enough to apply, false otherwise
	 **/
	protected function taxrules ( array $rules ) {
		if ( empty($rules) ) return true;

		$apply = false;
		$matches = 0;

		foreach ($setting['rules'] as $rule) {
			$match = false;

			if ( false !== $this->Item && false !== strpos($rule['p'],'product') ) {
				$match = $this->Item->taxrule($rule);
			} elseif ( false !== strpos($rule['p'],'customer')) {
				$match = $this->Customer->taxrule($rule);
			}

			if ($match) $matches++;
		}
		if ( 'any' == $setting['logic'] && $matches > 0) $apply = true;
		if ( 'all' == $setting['logic'] && count($setting['rules']) == $matches ) $apply = true;

		return apply_filters('shopp_tax_rate_match_rule',$apply,$rule,$this);
	}


	/**
	 * Sets the taxable address for applying the proper tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3
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

		$this->address = array_merge(apply_filters('shopp_taxable_address',compact('country','zone','locale')));

		return $this->address;
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
		$base = shopp_setting('base_operations');
		$format = $base['currency']['format'];
		$format['precision'] = 3;
		return Shopp::floatval($amount,true,$format);
	}

	/**
	 * Calculate taxes for a taxable amount using the given tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function calculate ( array &$rates, float $taxable ) {

		$compound = 0;
		$total = 0;
		foreach ($rates as $label => $taxrate) {

			$tax = ( $taxable * $taxrate->rate );			// Tax amount

			if ( $taxrate->compound ) {

				if ( 0 == $compound ) $compound = $taxable;	// Set initial compound taxable amount
				else $tax = ($compound * $taxrate->rate);	// Compounded tax amount

				$compound += $tax;						 	// Set compound taxable amount for next compound rate
			}

			$taxrate->amount += $tax;						// Capture the tax amount calculate for this taxrate
			$total += $tax;									// Sum all of the taxes to get the total tax for the item

		}

		return $total;

	}

	/**
	 * Calculates the total tax amount factored by quantity for the given tax rates
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $quantity The quantity to factor tax amounts by
	 * @param array $rates the list of applicable ShoppItemTax entries
	 * @return float $total
	 **/
	public function total ( array &$taxes, integer $quantity ) {

		$total = 0;
		foreach ( $taxes as $label => &$taxrate ) {
			$taxrate->total = $taxrate->amount * $quantity;
			$total += $taxrate->total;
		}

		return (float)$total;

	}

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

		if ( method_exists($this,$this->class) )
			return call_user_func($this->class,$rule['p'],$rule['v']);

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
	private function ShoppCartItem ( string $property, string $value ) {
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
	private function Product ( string $property, string $value ) {
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
	 * Evaluates tax rules for Purchased objects
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
	public $amount = 0.00;
	public $total = 0.00;
	public $compound = false;

}

<?php
/**
 * Address.php
 *
 * Provides foundational address data management framework
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.2
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Address
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ShoppAddress extends ShoppDatabaseObject {

	static $table = 'address';

	/**
	 * Address constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct ( $id = false, $key = 'id' ) {
		$this->init(self::$table);
		$this->load($id, $key);
	}

	/**
	 * Overloads the default load to update location details after load
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param integer $id The ID to lookup the record by
	 * @param string $key The column to use for matching the ID against
	 * @return boolean True if successfully loaded, false otherwise
	 **/
	public function load ( $id = false, $key = 'id' ) {

		if ( 'customer' == $key )
			$loaded = parent::load( array('customer' => $id, 'type' => $this->type) );
		else $loaded = parent::load($id, $key);

		$this->locate();

		return $loaded;
	}

	/**
	 * Determines the domestic area name from a U.S. ZIP code or
	 * Canadian postal code.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @return string
	 **/
	public function postmap () {
		if ( empty($this->postcode) || empty($this->country) ) return false;

		$postcode = $this->postcode;
		$patterns = Lookup::postcode_patterns();

		if ( ! isset($patterns[ $this->country ]) || empty($patterns[ $this->country ]) ) return false;

		$pattern = $patterns[ $this->country ];
		if (!preg_match("/$pattern/",$postcode)) return false;

		do_action('shopp_map_' . strtolower($this->country) . '_postcode', $this);
	}

	/**
	 * Sets the address location for calculating tax and shipping estimates
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function locate ( $data = false ) {
		$base = shopp_setting('base_operations');
		$markets = shopp_setting('target_markets');
		$countries = Lookup::countries();
		$regions = Lookup::regions();

		if ( $data ) $this->updates($data);

		if ( empty($this->country) ) {
			// If the target markets are set to single country, use that target as default country
			// otherwise default to the base of operations for tax and shipping estimates
			if (1 == count($markets)) $this->country = key($markets);
			else $this->country = $base['country'];
		}

		// Update state if postcode changes for tax updates
		if ( isset($this->postcode) ) $this->postmap();

		$this->region = false;
		if ( isset($countries[ $this->country ]) && isset($regions[ $countries[ $this->country ]['region'] ]) )
			$this->region = $regions[ $countries[ $this->country ]['region'] ];

	}

} // END class Address


/**
 * BillingAddress class
 *
 * Billing Address
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, 21 February, 2011
 * @package address
 **/

class BillingAddress extends ShoppAddress {

	public $type = 'billing';

	public $locale = false;

	public $card = false;
	public $cardtype = false;
	public $cardexpires = false;
	public $cardholder = false;

	public static function exportcolumns () {
		$prefix = 'b.';
		return array(
			$prefix . 'address' => Shopp::__('Billing Street Address'),
			$prefix . 'xaddress' => Shopp::__('Billing Street Address 2'),
			$prefix . 'city' => Shopp::__('Billing City'),
			$prefix . 'state' => Shopp::__('Billing State/Province'),
			$prefix . 'country' => Shopp::__('Billing Country'),
			$prefix . 'postcode' => Shopp::__('Billing Postal Code'),
		);
	}

	public function fromshipping () {
		$Shipping = ShoppOrder()->Shipping;

		$fields = array($this->address, $this->xaddress, $this->city);
		$address = join('', $fields);

		if ( empty($address) )
			$this->copydata($Shipping, '', array('type'));
	}

} // end BillingAddress class

/**
 * ShippingAddress class
 *
 * The shipping address manager
 *
 * @author Jonathan Davis
 * @version 1.2
 * @copyright Ingenesis Limited, 21 February, 2011
 * @package address
 **/
class ShippingAddress extends ShoppAddress {

	public $type = 'shipping';
	public $method = false;
	public $residential = 'on';

	/**
	 * Registry of supported export fields
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array
	 **/
	public static function exportcolumns () {
		$prefix = 's.';
		return array(
			$prefix . 'address' => Shopp::__('Shipping Street Address'),
			$prefix . 'xaddress' => Shopp::__('Shipping Street Address 2'),
			$prefix . 'city' => Shopp::__('Shipping City'),
			$prefix . 'state' => Shopp::__('Shipping State/Province'),
			$prefix . 'country' => Shopp::__('Shipping Country'),
			$prefix . 'postcode' => Shopp::__('Shipping Postal Code'),
		);
	}

} // END class ShippingAddress

class PostcodeMapping {

	public static function uszip ($Address) {
		PostcodeMapping::prefixcode(substr($Address->postcode, 0, 3), $Address);
	}

	public static function capost ($Address) {
		PostcodeMapping::prefixcode(strtoupper($Address->postcode{0}), $Address);
	}

	public static function aupost ($Address) {
		PostcodeMapping::numericrange($Address);
	}

	/**
	 * Lookup and determine the state/region based on numeric postcode ranges.
	 *
	 * @param ShoppAddress $Address
	 */
	public static function numericrange (Address $Address) {
		$postcode = $Address->postcode;
		$postcodes = Lookup::postcodes();
		if ( ! isset($postcodes[$Address->country]) ) return;

		foreach ( $postcodes[$Address->country] as $state => $ranges ) {
			$ranges = (array) $ranges; // One or more ranges may be provided

			foreach ( $ranges as $range ) {
				list($min, $max) = explode('-', $range);
				if ( $postcode >= $min && $postcode <= $max ) {
					$match = $state;
					break;
				}
			}

			if ( isset($match) ) break;
		}

		if ( isset($match) ) $Address->state = $match;
	}

	/**
	 * Lookup country state/province by postal code prefix
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $prefix The postal code prefix
	 * @param ShoppAddress $Address
	 * @return void
	 **/
	public static function prefixcode ($prefix, ShoppAddress $Address) {
		$postcodes = Lookup::postcodes();
		if ( ! isset($postcodes[ $Address->country ]) ) return;

		$codemap =& $postcodes[ $Address->country ];
		$state = isset($codemap[ strtoupper($prefix) ]) ? $codemap[ $prefix ] : false;

		if ( is_array($state) ) { // Handle multiple states in the same postal code prefix. Props msigley
			if ( in_array($Address->state, $state) ) $state = false; // Don't replace current state if it is in the postcode prefix
			else $state = $state[0];
		}

		if ( ! $state) return;

		$Address->state = $state;

	}

} // class PostcodeMapping

add_action('shopp_map_au_postcode',   array('PostcodeMapping', 'aupost'));
add_action('shopp_map_us_postcode',   array('PostcodeMapping', 'uszip'));
add_action('shopp_map_usaf_postcode', array('PostcodeMapping', 'uszip'));
add_action('shopp_map_usat_postcode', array('PostcodeMapping', 'uszip'));
add_action('shopp_map_ca_postcode',   array('PostcodeMapping', 'capost'));
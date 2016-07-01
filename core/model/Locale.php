<?php
/**
 * Locale.php
 *
 * Provides locale information
 *
 * @copyright Ingenesis Limited, December 2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Locale
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppLocale {

	protected $code = '';
	protected $name = '';
	protected $units = 'metric';
	protected $region = '';
	protected $addressing = '';
	protected $Currency = '';

	public function __construct ( $code = null ) {
		if ( isset($code) )
			$this->lookup($code);
	}

	public function name () {
		return $this->name;
	}

	public function country () {
		return $this->code;
	}

	public function code () {
		return substr($this->code, 0, 2);
	}

	/**
	 * Provides the currency code
	 **/
	public function currency () {
		return $this->Currency;
	}

	/**
	 * Provides the measurement unit system (e.g. metric, imperial)
	 **/
	public function units () {
		return $this->units;
	}

	public function region () {
		return $this->region;
	}

	public function states () {
		$source = $this->data("states/$code.php");
		if ( ! $source ) return false;
		return include $this->data("states/$code.php");
	}

	public function division () {
		$labels = ShoppLookup::country_divisions($this->code);
		return $labels[0];
	}

	public function divisions () {
		$labels = ShoppLookup::country_divisions($this->code);
		return $labels[1];
	}

	public function addressing () {
		return $this->addressing;
	}

	public function lookup ( $code = false ) {

		$countries = ShoppLookup::countries();
		if ( ! isset($countries[ $code ]) ) return false;

		$this->code = $code;
		$this->name = $countries[ $code ];

		// Currency
		$currencies = include $this->data('currencies.php');
		$currency = Shopp::array_search_deep($code, $currencies);
		$this->Currency = new ShoppLocaleCurrency($currency, $code);

		// Region
		$regions = include $this->data('regions.php');
		$this->region = Shopp::array_search_deep($code, $regions);

		// Addressing
		$addrs = include $this->data('addressing.php');
		if ( isset($addrs[ $code ]) )
			$this->addressing = $addrs[ $code ];
		else $this->addressing = SHOPP_DEFAULT_ADDRESSING;

		// Units
		$imperial = array_flip(ShoppLookup::imperial_units());
		if ( isset($imperial[ $code ]) )
			$this->units = 'imperial';

	}

	private function data ( $file ) {
		$path = join('/', array(SHOPP_PATH, 'locales', $file));
		if ( is_readable($path) )
			return $path;
	}

} // end ShoppLocale

class ShoppBaseLocale extends ShoppLocale {

	protected $state = '';

	public function __construct ( $code = null ) {

		if ( $base = shopp_setting('base_locale') ) {

			if ( isset($base[0]) )
				$this->code = $base[0];

			if ( isset($base[1]) )
				$this->state = $base[1];

		} else $this->code = $this->legacy();

		if ( empty($this->code) )
			$this->code = 'US';

		parent::__construct($this->code);

	}

	public function legacy () {
		$base = shopp_setting('base_operations');

		$country = $base['country'];
		if ( ! empty($base['zone']) )
			$state = $base['zone'];

		$this->save($country, $state);

		return $country;
	}

	public function save ( $country, $state = '' ) {

		$this->country = $country;
		$this->state = $state;

		shopp_set_setting('base_locale', array($country, $state));

		shopp_set_setting('tax_inclusive', // Automatically set the inclusive tax setting
			in_array($country, ShoppLookup::country_inclusive_taxes()) ? 'on' : 'off'
		);

		$this->lookup($country);

	}

	public function state () {
		return $this->state;
	}

	public function settings () {
		return array(
			'name' => $this->name,
			'currency' => array(
				'code' => $this->Currency->code(),
				'format' => $this->Currency->settings()
			),
			'units' => $this->units,
			'region' => $this->region,
			'country' => $this->code,
			'zone' => $this->state
		);
	}


}

class ShoppLocaleCurrency {

	const BEFORE = 1;
	const AFTER = 2;

	private $position = self::BEFORE;
	private $symbol = '';
	private $precision = 2;
	private $decimals = '.';
	private $thousands = ',';
	private $grouping = array(3);

	public function __construct ( $code, $country ) {
		$currencies = $this->currencies();
		if ( ! isset($currencies[ $code ]) ) return false;
		$this->code = $code;
		if ( count($currencies[ $code ]) > 1 )
			$json_format = Shopp::array_search_deep($country, $currencies[ $code ]);
		else $json_format = key($currencies[ $code ]);

		$format = json_decode($json_format);

		list(
			$this->position,
			$this->symbol,
			$this->precision,
			$this->decimals,
			$this->thousands,
			$this->grouping
		) = (array) $format;
	}

	public function code () {
		return $this->code;
	}

	public function position () {
		return $this->position;
	}

	public function symbol () {
		return $this->symbol;
	}

	public function precision () {
		return $this->precision;
	}

	public function decimals () {
		return $this->decimals;
	}

	public function thousands () {
		return $this->thousands;
	}

	public function grouping () {
		return $this->grouping;
	}

	public function format ( $amount ) {

		return $amount;
	}

	public function settings () {
		return array(
			'cpos' => $this->position(),
			'currency' => $this->symbol(),
			'precision' => $this->precision(),
			'decimals' => $this->decimals(),
			'thousands' => $this->thousands(),
			'grouping' => $this->grouping()
		);
	}

	private function currencies () {
		$path = join('/', array(SHOPP_PATH, 'locales', 'currencies.php'));
		if ( is_readable($path) )
			return include $path;
	}

}


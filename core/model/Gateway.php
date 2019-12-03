<?php
/**
 * Gateway.php
 *
 * Prototype classes for local and remote payment systems
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, March, 2009
 * @package shopp
 * @subpackage gateways
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * GatewayModule interface
 *
 * Provides a template for required gateway methods
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage gateways
 **/
interface GatewayModule {

	/**
	 * Used for rendering the gateway settings UI
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function settings();

}

/**
 * GatewayFramework class
 *
 * Provides default helper methods for gateway modules.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @version 1.2
 * @package shopp
 * @subpackage gateways
 **/
abstract class GatewayFramework {

	public $name      = false;		// The proper name of the gateway
	public $module    = false;		// The module class name of the gateway

	// Supported features
	public $cards     = false;		// A list of supported payment cards
	public $authonly  = false;		// Forces auth-only order processing
	public $saleonly  = false;		// Forces sale-only order processing
	public $captures  = false;		// Supports capture separate of authorization
	public $recurring = false;		// Supports recurring billing
	public $refunds   = false;		// Remote refund support flag

	// Config settings
	public $secure = true;			// Flag for requiring encrypted checkout process
	public $multi  = false;			// Flag to enable a multi-instance gateway

	// Loaded settings
	public $session   = false;		// The current shopping session ID
	public $Order     = false;		// The current customer's Order
	public $baseop    = false; 		// Base of operation setting
	public $currency  = false;		// The base of operations currency code
	public $precision = 2;			// Currency precision
	public $decimals  = '.';		// Default decimal separator
	public $thousands = '';			// Default thousands separator
	public $settings  = array();	// List of settings for the module
	public $codes     = array(200);	// List of valid response codes

	/**
	 * Setup the module for runtime
	 *
	 * Auto-loads settings for the module and setups defaults
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {

		$this->module  = get_class($this);
		$this->session = ShoppShopping()->session;
		$this->Order   = ShoppOrder();

		if ( 'ShoppFreeOrder' != $this->module ) { // There are no settings for ShoppFreeOrder
			$this->settings = shopp_setting($this->module);

			// @todo Remove legacy gateway settings migrations
			// Attempt to copy old settings if this is a new prefixed gateway class
			if ( empty($this->settings) && false !== strpos($this->module, 'Shopp') ) {
				$legacy = substr($this->module, 5);
				$this->settings = shopp_setting($legacy);
				if ( ! empty($this->settings) )
					shopp_set_setting($this->module, $this->settings);
			}
		}

		if ( ! isset($this->settings['label']) && $this->cards )
			$this->settings['label'] = Shopp::__('Credit Card');

		$this->baseop    = shopp_setting('base_operations');
		$this->currency  = $this->baseop['currency']['code'];
		$this->precision = $this->baseop['currency']['format']['precision'];

		$this->_loadcards();

		$gateway = GatewayModules::hookname($this->module);

		add_action('shopp_init', array($this, 'myactions'), 30);
		add_action('shopp_' . $gateway . '_refunded', array($this, 'cancelorder') );

		if ( $this->authonly )
			add_filter('shopp_purchase_order_' . $gateway . '_processing', function () {
				return "auth";
			} );
		elseif ( $this->saleonly )
			add_filter('shopp_purchase_order_' . $gateway . '_processing', function () {
				return "sale";
			} );

	}

	/**
	 * Provides a salted hash to identify this processor in requests
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string salted hash payment processor id
	 **/
	protected function id () {
		return hash('crc32b', NONCE_SALT . get_class($this));
	}

	/**
	 * Initialize a list of gateway module settings
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $name The name of a setting
	 * @param string $name... (optional) Additional setting names to initialize
	 * @return void
	 **/
	public function setup () {
		$settings = func_get_args();
		foreach ( $settings as $name )
			if ( ! isset($this->settings[ $name ]) )
				$this->settings[ $name ] = false;
	}

	/**
	 * Determine if the current order should be processed by this module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function myorder () {
		return ( $this->Order->Payments->processor() == $this->module );
	}

	public function myactions () {
		if ( $this->myorder() && method_exists($this, 'actions') )
			$this->actions();
	}

	/**
	 * Provides the currency code for use in gateway transactions
	 *
	 * @author Jonathan Davis
	 * @since 1.2.6
	 *
	 * @return string The currency code
	 **/
	public function currency () {
		// Use gateway default currency or USD if gateway doesn't specify
		$currency = isset($this->currency) && 3 == strlen($this->currency) ? strtoupper($this->currency) : 'USD';

		if ( ! empty($this->baseop) && isset($this->baseop['currency']) && isset($this->baseop['currency']['code']) )
			$currency = $this->baseop['currency']['code'];

		return apply_filters('shopp_gateway_currency', $currency);
	}

	/**
	 * Generate a unique transaction ID using a timestamp
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string
	 **/
	public function txnid () {
		return time();
	}

	public function captured ( $Order ) {
		shopp_add_order_event($Order->id, 'captured', array(
			'txnid'   => $Order->txnid,				// Can be either the original transaction ID or an ID for this transaction
			'amount'  => $Order->total,				// Capture of entire order amount
			'fees'    => $Order->fees,				// Order Fees
			'gateway' => $Order->gateway			// Gateway handler name (module name from @subpackage)
		));
	}

	/**
	 * Generic connection manager for sending data
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $data The encoded data to send
	 * @param string $url (optional) The API endpoint URL to connect to
	 * @param array $options (optional) WP_Http options
	 * @return string Raw response
	 **/
	public function send ( $data, $url = false ) {

		// Adds optional support for options
		$parameters = func_num_args();
		$args = func_get_args();
		if ( $parameters > 2 ) $options = $args[ $parameters - 1 ];
		else $options = array();

		$defaults = array(
			'method'      => 'POST',
			'timeout'     => SHOPP_GATEWAY_TIMEOUT,
			'redirection' => 7,
			'httpversion' => '1.0',
			'user-agent'  => SHOPP_GATEWAY_USERAGENT.'; '.get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     => array(),
			'cookies'     => array(),
			'body'        => $data,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => true,
		);
		$params = array_merge($defaults, $options);

		$connection = new WP_Http();
		$result = $connection->request($url, $params);

		if ( is_wp_error($result) ) {
			$errors = array(); foreach ($result->errors as $errname => $msgs) $errors[] = join(' ',$msgs);
			$errors = join(' ',$errors);

			new ShoppError($this->name.": " . Lookup::errors('gateway', 'fail') . " $errors " . Lookup::errors('contact', 'admin') . " (WP_HTTP)", 'gateway_comm_err', SHOPP_COMM_ERR);
			return false;
		} elseif ( empty($result) || !isset($result['response']) ) {
			new ShoppError($this->name.": " . Lookup::errors('gateway', 'noresponse'), 'gateway_comm_err', SHOPP_COMM_ERR);
			return false;
		} else extract($result);

		if ( ! in_array($response['code'], $this->codes) ) {
			$error = Lookup::errors('gateway', 'http-' . $response['code']);
			if ( empty($error) ) $error = Lookup::errors('gateway', 'http-unknown');
			new ShoppError($this->name . ": $error", 'gateway_comm_err', SHOPP_COMM_ERR);
			return false;
		}

		return $body;
	}

	/**
	 * Helper to encode a data structure into a URL-compatible format
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $data Key/value pairs of data to encode
	 * @return string
	 **/
	public function encode ( array $data ) {
		$data = stripslashes_deep($data);
		return http_build_query($data);
	}

	/**
	 * Formats a data structure into POST-able form elements
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $data Key/value pairs of data to format into form elements
	 * @return string
	 **/
	public function format ( array $data ) {
		$query = '';
		foreach( $data as $key => $value ) {
			if ( is_array($value) ) {
				foreach( $value as $item )
					$query .= '<input type="hidden" name="' . $key . '[]" value="' . esc_attr($item) . '" />';
			} else {
				$query .= '<input type="hidden" name="' . $key . '" value="' . esc_attr($value) . '" />';
			}
		}
		return $query;
	}

	/**
	 * Provides the accepted PayCards for the gateway
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array
	 **/
	public function cards () {
		$accepted = array();

		if ( ! empty($this->settings['cards']) ) $accepted = $this->settings['cards'];
		if ( empty($accepted) && is_array($this->cards) )
			$accepted = array_keys($this->cards);

		$pcs = Lookup::paycards();
		$cards = array();
		foreach ( $accepted as $card ) {
			$card = strtolower($card);
			if ( isset($pcs[ $card ]) ) $cards[ $card ] = $pcs[ $card ];
		}

		return $cards;
	}

	/**
	 * Formats monetary amounts for handing off to the gateway
	 *
	 * Supports specifying an order total by name (subtotal, tax, shipping, total)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string|float|int $amount The amount (or name of the amount total) to format
	 * @return string Formatted amount
	 **/
	public function amount ( $amount, array $format = array() ) {

		$register = false;

		if ( is_string($amount) ) {
			$register = $amount;
			$Cart = ShoppOrder()->Cart;
			$amount = $Cart->total($amount);
			if ( false === $amount ) $register = false;
		} elseif ( ! ( is_int($amount) || is_float($amount) ) ) return $amount;

		$defaults = array(
			'precision' => $this->precision,
			'decimals'  => $this->decimals,
			'thousands' => $this->thousands,
		);
		$format = array_merge($defaults, $format);
		extract($format);

		if ( ! empty($register) ) // Allow targeting specific amounts for filtering
			$amount = apply_filters("shopp_gateway_{$register}_amount", $amount);

		$amount = apply_filters('shopp_gateway_amount', abs($amount));

		return number_format($amount, $precision, $decimals, $thousands);
	}

	/**
	 * Zeros out tax amounts when tax inclusive is enabled
	 *
	 * Prevents inclusive-tax unaware payment systems (such as PayPal Standard)
	 * from adding extra taxes to the order
	 *
	 * @author Jonathan Davis
	 * @since 1.3.2
	 *
	 * @param float $amount The tax amount to filter
	 * @return float The correct amount
	 **/
	public function notaxinclusive ( $amount ) {
		if ( shopp_setting_enabled('tax_inclusive') ) return 0.0;
		else return $amount;
	}

	public function ascii_filter ( $string ) {
		return preg_replace('/[^\x20-\x7F]/', '', $string);
	}

	public function cancelorder ( RefundedOrderEvent $Refunded ) {
		$order = $Refunded->order;
		$Purchase = new ShoppPurchase($order);
		if ( $Refunded->amount != $Purchase->total ) return;

		// If not a partial refund, cancel the remaining balance
		shopp_add_order_event($order, 'voided', array(
			'txnorigin' => $Refunded->txnid,
			'txnid'     => '',
			'gateway'   => $this->module
		));
	}

	/**
	 * Loads the enabled payment cards
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	private function _loadcards () {

		if ( empty($this->settings['cards']) )
			$this->settings['cards'] = $this->cards;

		if ( $this->cards ) {
			$cards = array();
			$pcs = Lookup::paycards();
			foreach ( $this->cards as $card ) {
				$card = strtolower($card);
				if ( isset($pcs[ $card ]) ) $cards[] = $pcs[ $card ];
			}
			$this->cards = $cards;
		}
	}

	/**
	 * Generate the settings UI for the module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $module The module class name
	 * @param string $name The formal name of the module
	 * @return void
	 **/
	public function initui ( $module, $name ) {
		if ( ! isset($this->settings['label']) ) $this->settings['label'] = $name;
		$this->ui = new GatewaySettingsUI($this, $name);
		$this->settings();
	}

	public function uitemplate () {
		$this->ui->template();
	}

	public function ui ( $id = false ) {
		$editor = $this->ui->generate($id);
		$settings = $this->settings;
		if ( false !== $id && isset($this->settings[ $id ]) )
			$settings = $this->settings[ $id ];

		foreach ( (array)$settings as $name => $value )
			$data['{$' . $name . '}'] = $value;

		return str_replace(array_keys($data), $data, $editor);
	}

} // END class GatewayFramework


/**
 * GatewayModules class
 *
 * Gateway module file manager to load gateways that are active.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage gateways
 **/
class GatewayModules extends ModuleLoader {

	protected $interface = 'GatewayModule';
	protected $paths =  array(SHOPP_GATEWAYS, SHOPP_ADDONS);

	public $selected  = false;		// The chosen gateway to process the order
	public $installed = array();
	public $secure    = false;		// SSL-required flag
	public $freeorder = false;

	/**
	 * Initializes the gateway module loader
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {

		// Get hooks in place before getting things started
		add_action('shopp_module_loaded', array($this, 'properties'));

		$this->installed();
		$this->activated();

		add_action('shopp_init', array($this, 'load'));
		add_action('shopp_init', array($this, 'freeorder'));
	}

	/**
	 * Determines the activated gateway modules
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of module names for the activated modules
	 **/
	public function activated () {
		$Shopp           = Shopp::object();
		$this->activated = array();
		$gateways        = explode(',', shopp_setting('active_gateways'));
		$modules         = array_keys($this->modules);

		foreach ( $gateways as $gateway ) {
			$moduleclass = $this->moduleclass($gateway);
			if ( ! empty($moduleclass) )
				$this->activated[] = $moduleclass;
		}
		
		$this->activated =  array_unique($this->activated);

		return $this->activated;
	}

	public function freeorder () {
		$this->freeorder = new ShoppFreeOrder();
	}

	/**
	 * Sets Gateway system settings flags based on activated modules
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $module Activated module class name
	 * @return void
	 **/
	public function properties ( $module ) {
		if ( ! isset($this->active[ $module ]) ) return;
		$this->active[ $module ]->name = $this->modules[ $module ]->name;
		if ($this->active[ $module ]->secure) $this->secure = true;
	}

	/**
	 * Get a specified gateway
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $gateway The gateway hook name
	 * @return GatewayFramework The activated gateway object.
	 **/
	public function &get ( $gateway ) {

		if ( empty($this->active) )
			$this->settings(); // Reload settings

		if ( 'ShoppFreeOrder' == $gateway )
			return $this->freeorder;
		elseif ( isset($this->active[ $gateway ]) )
			return $this->active[ $gateway ];
		elseif ( isset($this->active[ "Shopp$gateway" ]) ) // @see #3256
			return $Gateways->active[ "Shopp$gateway" ];

		$false = false;
		return $false;

	}

	/**
	 * Loads all the installed gateway modules for the payments settings
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function settings () {
		$this->recache();
		$this->load(true);
	}

	/**
	 * Registers gateway module help tabs
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function help () {
		foreach ( $this->modules as $class => &$module ) {
			if ( ! method_exists($class, 'help') ) continue;
			get_current_screen()->add_help_tab( array(
				'id'      => $class,
				'title'   => $module->name,
				'content' => call_user_func( array($class, 'help') )
			));
		}
	}

	/**
	 * Initializes the settings UI for each loaded module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function ui () {
		foreach ( $this->active as $package => &$module )
			$module->initui($package, $this->modules[ $package ]->name);
	}

	public function templates () {
		foreach ( $this->active as $package => &$module )
			$module->uitemplate($package, $this->modules[ $package ]->name);
	}

	public static function hookname ( $classname ) {
		if ( 0 === stripos($classname, 'shopp') )
			$classname = substr($classname, 5);
		return sanitize_key($classname);
	}

	/**
	 * Returns the classname of a valid module
	 *
	 * @since 1.3.4
	 *
	 * @return string|bool Class name of the module, or false if not a valid module
	 **/
	public function moduleclass ( $gateway ) {

		// Handled suffixed multi-instance gateways names (e.g. OfflinePayments-0)
		if ( false !== strpos($gateway, '-') )
			list($gateway, $id) = explode('-', $gateway);

		// Check for namespaced and non-namespaced derivitives
		$namespaced = 'Shopp' . $gateway;

		$module = false;
		if ( isset($this->modules[ $namespaced ]) ) $module = $this->modules[ $namespaced ];
		elseif ( isset($this->modules[ $gateway ]) ) $module = $this->modules[ $gateway ];

		// If no valid module exists return false, otherwise provide the class name
		if ( empty($module) ) return false;
		else return $module->classname;

	}


} // END class GatewayModules

class GatewaySettingsUI extends ModuleSettingsUI {

	public $multi    = false;
	public $instance = false;

	public function __construct ($Module, $name) {
		parent::__construct($Module, $name);
		if ( $Module->multi ) {
			$this->multi = true;
			shopp_custom_script('payments', '$ps[\'' . strtolower($Module->module) . '\'] = ' . json_encode($Module->settings) . ";\n");
		}
	}

	public function generate ( $id = false ) {
		$instance = false;
		$label = $this->label;
		if ( $this->multi ) {
			$instance = '[${instance}]';
			$label = '${label}';
		}

		$_ = array();
		$_[] = '<tr class="${editing_class}"><td colspan="7">';
		$_[] = '<table class="form-table shopp-settings"><tr>';
		$_[] = '<th scope="row" colspan="4">' . $this->name . '<input type="hidden" name="gateway" value="' . $this->module . $instance . '" /></th>';
		$_[] = '</tr><tr>';
		$_[] = '<td><input type="text" name="settings[' . $this->module . ']' . $instance . '[label]" value="' . $label . '" id="' . $this->id . '-label" size="16" class="selectall" /><br />';
		$_[] = '<label for="' . $this->id . '-label">' . Shopp::__('Option Name') . '</label></td>';

		foreach ( $this->markup as $markup ) {
			$_[] = '<td>';
			if ( empty($markup) ) $_[] = '&nbsp;';
			else $_[] = join("\n", $markup);
			$_[] = '</td>';
		}

		$_[] = '</tr><tr>';
		$_[] = '<td colspan="4">';
		$_[] = '<p class="textright">';
		$_[] = '<a href="${cancel_href}" class="button-secondary cancel alignleft">' . __('Cancel') . '</a>';
		$_[] = '<input type="submit" name="save" value="' . __('Save Changes') . '" class="button-primary" /></p>';
		$_[] = '</td>';
		$_[] = '</tr></table>';
		$_[] = '</td></tr>';

		return join("\n", $_);

	}

	/**
	 * Renders a multiple-select widget from a list of payment cards
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $column The table column to add the element to
	 * @param array $attributes Element attributes; pass a 'selected' attribute as an array to set the selected payment cards
	 * @param array $options The available payment cards in the menu
	 *
	 * @return void
	 **/
	public function cardmenu ( $column = 0, array $attributes = array(), array $cards = array() ) {
		$options = array();
		foreach ( $cards as $card )
			$options[ strtolower($card->symbol) ] = $card->name;
		$this->multimenu($column, $attributes, $options);
	}

	public function input ( $column = 0, array $attributes = array() ) {
		$this->multifield($attributes);
		parent::input($column, $attributes);
	}

	public function textarea ( $column = 0, array $attributes = array() ) {
		$this->multifield($attributes);
		parent::textarea($column, $attributes);
	}

	public function multifield ( &$attributes ) {
		if ($this->multi) {
			$attributes['value'] = '${'. $attributes['name']. '}';
			$attributes['name']  = '${instance}]['. $attributes['name'];
		}
		return $attributes;
	}

	public function behaviors ( $script ) {
		shopp_custom_script('payments', $script);
	}


}

/**
 * Handles order processing for free orders
 *
 * @author Jonathan Davis
 * @package shopp
 * @since 1.2
 **/
class ShoppFreeOrder extends GatewayFramework {

	public $secure   = false;	// SSL not required
	public $refunds  = true;	// Supports refunds
	public $saleonly = true;

	/**
	 * Setup the ShoppFreeOrder gateway
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function __construct () {
		parent::__construct();
		$this->name = Shopp::__('Free Order');

		add_action('shopp_freeorder_sale', array($this, 'capture'));
		add_action('shopp_freeorder_refund', array($this, 'void'));
		add_action('shopp_freeorder_void', array($this, 'void'));
	}

	public function capture ( OrderEventMessage $Event ) {
		shopp_add_order_event($Event->order, 'captured', array(
			'txnid'     => time(),
			'fees'      => 0,
			'paymethod' => Shopp::__('Free Order'),
			'paytype'   => '',
			'payid'     => '',
			'amount'    => $Event->amount,
			'gateway'   => $this->module
		));
	}

	public function void ( OrderEventMessage $Event ) {
		$Purchase = new ShoppPurchase($Event->order);
		shopp_add_order_event($Purchase->id, 'voided', array(
			'txnorigin' => $Purchase->txnid,	// Original transaction ID (txnid of original Purchase record)
			'txnid'     => time(),					// Transaction ID for the VOID event
			'gateway'   => $Event->gateway		// Gateway handler name (module name from @subpackage)
		));

	}

} // END class ShoppFreeOrder

/**
 * PayCard class
 *
 * Implements structured payment card (credit card) behaviors including
 * card number validation and extra security field requirements.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage gateways
 **/
class PayCard {

	public $name;
	public $symbol;
	public $pattern = false;
	public $csc = false;
	public $inputs = array();

	public function __construct ( $name, $symbol, $pattern, $csc = false, $inputs = array() ) {
		$this->name    = $name;
		$this->symbol  = $symbol;
		$this->pattern = $pattern;
		$this->csc     = $csc;
		$this->inputs  = $inputs;
	}

	public function validate ( $pan ) {
		$n = self::sanitize($pan);
		if ( empty($n) ) return false;
		if ( strlen($n) == 4 && is_numeric($n) ) return true;
		return ( $this->match($n) && self::checksum($n) );
	}

	public function match ( $number ) {
		if ( empty($this->pattern) ) return true; // Can't test an empty pattern
		return preg_match($this->pattern, $number);
	}

	/**
	 * Calculate and validate the PAN checksum
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $number The PAN number to validate
	 * @return boolean True if valid, false otherwise
	 **/
	public static function checksum ( $number ) {
		$code = strrev($number);
		for ( $cs = $i = 0; $i < strlen($code); $i++ ) {
			$d = intval($code[ $i ]);
			if ( $i & 1 ) $d *= 2;
			$cs += $d % 10;
			if ( $d > 9 ) $cs += 1;
		}
		return ( $cs % 10 == 0 );
	}

	/**
	 * Santizes a string value for digits-only
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $value The PAN value to sanitize
	 * @return string The digit-only string
	 **/
	public static function sanitize ( $value ) {
		$value = str_replace(array('-', '+'),'', $value); // Remove plus and minus that filter_var won't catch
		$value = filter_var($value, FILTER_SANITIZE_NUMBER_INT); // Remove any other non-digit
		return $value;
	}

	/**
	 * Detects PAN values and truncates it to the last 4 digits
	 *
	 * PCI DSS requirement 3 says first 6 and last 4 digits of PAN allowed
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $value The (possible) PAN id
	 * @return string The truncated value
	 **/
	public static function truncate ( $value ) {
		$pan = self::sanitize($value); // Sanitize first
		if ( is_numeric($pan) && strlen($pan) > 10 )
			return substr($pan, -4);
		return $value;
	}

	/**
	 * Masks a payment card PAN to last 4 digits with a masking character
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $value The PAN value to mask
	 * @param string $value The masking character to use
	 * @return string The masked PAN value
	 **/
	public static function mask ( $value, $mask = null ) {
		if ( empty($value) ) return $value;

		if ( ! self::checksum($value) ) return $value;

		if ( ! isset($mask) ) $mask = 'X';
		$n = self::sanitize($value);
		$length = max(strlen($value) - 4, 12);
		if ( self::checksum($n) );
			return str_repeat($mask, $length) . self::truncate($n);
	}

} // end class PayCard

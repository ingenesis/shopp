<?php
/**
 * Gateway classes
 *
 * Generic prototype classes for local and remote payment systems
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, March 17, 2009
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
	 * Used for setting up event listeners
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function actions();

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

	var $name = false;			// The proper name of the gateway
	var $module = false;		// The module class name of the gateway

	// Supported features
	var $cards = false;			// A list of supported payment cards
	var $authonly = false;		// Forces auth-only order processing
	var $saleonly = false;		// Forces sale-only order processing
	var $captures = false;		// Supports capture separate of authorization
	var $recurring = false;		// Supports recurring billing
	var $refunds = false;		// Remote refund support flag

	// Config settings
	var $xml = false;			// Flag to load and enable XML parsing
	var $soap = false;			// Flag to load and SOAP client helper
	var $secure = true;			// Flag for requiring encrypted checkout process
	var $multi = false;			// Flag to enable a multi-instance gateway

	// Loaded settings
	var $session = false;		// The current shopping session ID
	var $Order = false;			// The current customer's Order
	var $baseop = false; 		// Base of operation setting
	var $currency = false;		// The base of operations currency code
	var $precision = 2;			// Currency precision
	var $decimals = '.';		// Default decimal separator
	var $thousands = '';		// Default thousands separator
	var $settings = array();	// List of settings for the module
	var $codes = array(200);    // List of valid response codes

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
	function __construct () {

		$this->session = ShoppShopping()->session;
		$this->Order = &ShoppOrder();
		$this->module = get_class($this);

		if ('FreeOrder' != $this->module) // There are no settings for FreeOrder
			$this->settings = shopp_setting($this->module);

		if (!isset($this->settings['label']) && $this->cards)
			$this->settings['label'] = __('Credit Card','Shopp');

		if ( $this->xml && ! class_exists('xmlQuery') ) require_once(SHOPP_MODEL_PATH."/XML.php");
		if ( $this->soap && ! class_exists('nusoap_base') ) require_once(SHOPP_MODEL_PATH."/SOAP.php");

		$this->baseop = shopp_setting('base_operations');
		$this->currency = $this->baseop['currency']['code'];
		$this->precision = $this->baseop['currency']['format']['precision'];

		$this->_loadcards();

		add_action('shopp_init',array($this,'myactions'),30);
		$gateway = sanitize_key($this->module);
		add_action('shopp_'.$gateway.'_refunded',array($this,'cancelorder'));

		if ($this->authonly)
			add_filter('shopp_purchase_order_'.$gateway.'_processing',create_function('','return "auth";'));
		elseif ($this->saleonly)
			add_filter('shopp_purchase_order_'.$gateway.'_processing',create_function('','return "sale";'));

	}

	function myactions () {
		if ($this->myorder() && method_exists($this,'actions'))
			$this->actions();
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
	function setup () {
		$settings = func_get_args();
		foreach ($settings as $name)
			if (!isset($this->settings[$name]))
				$this->settings[$name] = false;
	}

	/**
	 * Determine if the current order should be processed by this module
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	function myorder ( $mine = false ) {
		if ( true === $mine ) {
			$this->Order->processor($this->module);
			return true;
		}
		return ($this->Order->Payments->processor() == $this->module);
	}

	/**
	 * Provides the currency code for use in gateway transactions
	 *
	 * @author Jonathan Davis
	 * @since 1.2.6
	 *
	 * @return string The currency code
	 **/
	function currency () {
		$currency = 'USD'; // Default to USD

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
	function txnid () {
		return time();
	}

	function captured ($Order) {
		shopp_add_order_event($Order->id,'captured',array(
			'txnid' => $Order->txnid,				// Can be either the original transaction ID or an ID for this transaction
			'amount' => $Order->total,				// Capture of entire order amount
			'fees' => $Order->fees,					// Order Fees
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
	 * @param string $url The URL to connect to
	 * @param string $deprecated DO NOT USE
	 * @param array $options
	 * @return string Raw response
	 **/
	function send ($data, $url = false, $deprecated = false, $options = array()) {

		$defaults = array(
			'method' => 'POST',
			'timeout' => SHOPP_GATEWAY_TIMEOUT,
			'redirection' => 7,
			'httpversion' => '1.0',
			'user-agent' => SHOPP_GATEWAY_USERAGENT.'; '.get_bloginfo( 'url' ),
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'body' => $data,
			'compress' => false,
			'decompress' => true,
			'sslverify' => false
		);
		$params = array_merge($defaults,$options);

		$connection = new WP_Http();
		$result = $connection->request($url,$params);

		if (is_wp_error($result)) {
			$errors = array(); foreach ($result->errors as $errname => $msgs) $errors[] = join(' ',$msgs);
			$errors = join(' ',$errors);

			new ShoppError($this->name.": ".Lookup::errors('gateway','fail')." $errors ".Lookup::errors('contact','admin')." (WP_HTTP)",'gateway_comm_err',SHOPP_COMM_ERR);
			return false;
		} elseif (empty($result) || !isset($result['response'])) {
			new ShoppError($this->name.": ".Lookup::errors('gateway','noresponse'),'gateway_comm_err',SHOPP_COMM_ERR);
			return false;
		} else extract($result);

		if ( ! in_array($response['code'], $this->codes) ) {
			$error = Lookup::errors('gateway','http-'.$response['code']);
			if (empty($error)) $error = Lookup::errors('gateway','http-unkonwn');
			new ShoppError($this->name.": $error",'gateway_comm_err',SHOPP_COMM_ERR);
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
	function encode ($data) {
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
	function format ($data) {
		$query = "";
		foreach($data as $key => $value) {
			if (is_array($value)) {
				foreach($value as $item)
					$query .= '<input type="hidden" name="'.$key.'[]" value="'.esc_attr($item).'" />';
			} else {
				$query .= '<input type="hidden" name="'.$key.'" value="'.esc_attr($value).'" />';
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
	function cards () {
		$accepted = array();
		if (!empty($this->settings['cards'])) $accepted = $this->settings['cards'];
		if (empty($accepted) && is_array($this->cards)) $accepted = array_keys($this->cards);
		$pcs = Lookup::paycards();
		$cards = array();
		foreach ($accepted as $card) {
			$card = strtolower($card);
			if (isset($pcs[$card])) $cards[$card] = $pcs[$card];
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
	function amount ($amount,$format=array()) {

		if (is_string($amount)) {
			$Totals = ShoppOrder()->Cart->Totals;
			if (!isset($Totals->$amount)) return false;
			$amount = $Totals->$amount;
		} elseif ( ! ( is_int($amount) || is_float($amount) ) ) return $amount;

		$defaults = array(
			'precision' => $this->precision,
			'decimals' => $this->decimals,
			'thousands' => $this->thousands,
		);
		$format = array_merge($defaults,$format);
		extract($format);

		$amount = apply_filters('shopp_gateway_amount', $amount);
		return number_format($amount,$precision,$decimals,$thousands);
	}

	function ascii_filter ($string) {
		return preg_replace('/[^\x20-\x7F]/','',$string);
	}

	function cancelorder (RefundedOrderEvent $Refunded) {
		$order = $Refunded->order;
		$Purchase = new Purchase($order);
		if ($Refunded->amount != $Purchase->total) return;

		// If not a partial refund, cancel the remaining balance
		shopp_add_order_event($order,'voided',array(
			'txnorigin' => $Refunded->txnid,
			'txnid' => '',
			'gateway' => $this->module
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
		if (empty($this->settings['cards'])) $this->settings['cards'] = $this->cards;
		if ($this->cards) {
			$cards = array();
			$pcs = Lookup::paycards();
			foreach ($this->cards as $card) {
				$card = strtolower($card);
				if (isset($pcs[$card])) $cards[] = $pcs[$card];
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
	function initui ($module,$name) {
		if (!isset($this->settings['label'])) $this->settings['label'] = $name;
		$this->ui = new GatewaySettingsUI($this,$name);
		$this->settings();
	}

	function uitemplate () {
		$this->ui->template();
	}

	function ui ($id=false) {
		$editor = $this->ui->generate($id);
		$settings = $this->settings;
		if (false !== $id && isset($this->settings[$id]))
			$settings = $this->settings[$id];

		foreach ((array)$settings as $name => $value)
			$data['{$'.$name.'}'] = $value;

		return str_replace(array_keys($data),$data,$editor);
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

	var $selected = false;		// The chosen gateway to process the order
	var $installed = array();
	var $secure = false;		// SSL-required flag
	var $freeorder = false;

	/**
	 * Initializes the gateway module loader
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void Description...
	 **/
	function __construct () {

		$this->path = SHOPP_GATEWAYS;

		// Get hooks in place before getting things started
		add_action('shopp_module_loaded',array(&$this,'properties'));

		$this->installed();
		$this->activated();

		add_action('shopp_init',array($this,'load'));
		add_action('shopp_init',array($this,'freeorder'));
	}

	/**
	 * Determines the activated gateway modules
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of module names for the activated modules
	 **/
	function activated () {
		$Shopp = Shopp::object();
		$this->activated = array();
		$gateways = explode(",",shopp_setting('active_gateways'));
		$modules = array_keys($this->modules);
		foreach ($gateways as $gateway) {
			if (false !== strpos($gateway,'-')) list($gateway,$id) = explode('-',$gateway);
			if (in_array($gateway,$modules) && !in_array($gateway,$this->activated))
				$this->activated[] = $this->modules[$gateway]->subpackage;
		}
		return $this->activated;
	}

	function freeorder () {
		$this->freeorder = new FreeOrder();
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
	function properties ($module) {
		if (!isset($this->active[$module])) return;
		$this->active[$module]->name = $this->modules[$module]->name;
		if ($this->active[$module]->secure) $this->secure = true;
	}

	/**
	 * Get a specified gateway
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function &get ($gateway) {
		if (empty($this->active)) $this->settings();
		if (!isset($this->active[$gateway])) return false;
		return $this->active[$gateway];
	}

	/**
	 * Loads all the installed gateway modules for the payments settings
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function settings () {
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
	function help () {
		foreach ($this->modules as $class => &$module) {
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
	function ui () {
		foreach ($this->active as $package => &$module)
			$module->initui($package,$this->modules[$package]->name);
	}

	function templates () {
		foreach ($this->active as $package => &$module)
			$module->uitemplate($package,$this->modules[$package]->name);
	}

} // END class GatewayModules

class GatewaySettingsUI extends ModuleSettingsUI {

	var $multi = false;
	var $instance = false;

	function __construct ($Module,$name) {
		parent::__construct($Module,$name);
		if ($Module->multi) {
			$this->multi = true;
			shopp_custom_script('payments', '$ps[\''.strtolower($Module->module).'\'] = '.json_encode($Module->settings).";\n");
		}
	}

	function generate ($id=false) {
		$instance = false;
		$label = $this->label;
		if ($this->multi) {
			$instance = '[${instance}]';
			$label = '${label}';
		}

		$_ = array();
		$_[] = '<tr class="${editing_class}"><td colspan="7">';
		$_[] = '<table class="form-table shopp-settings"><tr>';
		$_[] = '<th scope="row" colspan="4">'.$this->name.'<input type="hidden" name="gateway" value="'.$this->module.$instance.'" /></th>';
		$_[] = '</tr><tr>';
		$_[] = '<td><input type="text" name="settings['.$this->module.']'.$instance.'[label]" value="'.$label.'" id="'.$this->id.'-label" size="16" class="selectall" /><br />';
		$_[] = '<label for="'.$this->id.'-label">'.__('Option Name','Shopp').'</label></td>';

		foreach ($this->markup as $markup) {
			$_[] = '<td>';
			if (empty($markup)) $_[] = '&nbsp;';
			else $_[] = join("\n",$markup);
			$_[] = '</td>';
		}

		$_[] = '</tr><tr>';
		$_[] = '<td colspan="4">';
		$_[] = '<p class="textright">';
		$_[] = '<a href="${cancel_href}" class="button-secondary cancel alignleft">'.__('Cancel','Shopp').'</a>';
		$_[] = '<input type="submit" name="save" value="'.__('Save Changes','Shopp').'" class="button-primary" /></p>';
		$_[] = '</td>';
		$_[] = '</tr></table>';
		$_[] = '</td></tr>';

		return join("\n",$_);

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
	function cardmenu ($column=0,$attributes=array(),$cards=array()) {
		$options = array();
		foreach ($cards as $card) $options[strtolower($card->symbol)] = $card->name;
		$this->multimenu($column,$attributes,$options);
	}

	function input ($column=0,$attributes=array()) {
		$this->multifield($attributes);
		parent::input($column,$attributes);
	}

	function textarea ($column=0,$attributes=array()) {
		$this->multifield($attributes);
		parent::textarea($column,$attributes);
	}

	function multifield (&$attributes) {
		if ($this->multi) {
			$attributes['value'] = '${'.$attributes['name'].'}';
			$attributes['name'] = '${instance}]['.$attributes['name'];
		}
		return $attributes;
	}

	function behaviors ($script) {
		shopp_custom_script('payments',$script);
	}


}

/**
 * FreeOrder class
 *
 * Handles order processing for free orders
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class FreeOrder extends GatewayFramework {

	var $secure = false;	// SSL not required
	var $refunds = true;	// Supports refunds
	var $saleonly = true;

	/**
	 * Setup the FreeOrder gateway
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function __construct () {
		parent::__construct();
		$this->name = __('Free Order','Shopp');

		add_action('shopp_freeorder_sale',array($this,'capture'));
		add_action('shopp_freeorder_refund',array($this,'void'));
		add_action('shopp_freeorder_void',array($this,'void'));
	}


	function capture (OrderEventMessage $Event) {
		shopp_add_order_event($Event->order,'captured',array(
			'txnid' => time(),
			'fees' => 0,
			'paymethod' => __('Free Order','Shopp'),
			'paytype' => '',
			'payid' => '',
			'amount' => $Event->amount,
			'gateway' => $this->module
		));
	}

	function void (OrderEventMessage $Event) {
		$Purchase = new Purchase($Event->order);
		shopp_add_order_event($Purchase->id,'voided',array(
			'txnorigin' =>  $Purchase->txnid,	// Original transaction ID (txnid of original Purchase record)
			'txnid' => time(),					// Transaction ID for the VOID event
			'gateway' => $Event->gateway		// Gateway handler name (module name from @subpackage)
		));

	}

} // END class FreeOrder

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

	var $name;
	var $symbol;
	var $pattern = false;
	var $csc = false;
	var $inputs = array();

	function __construct ($name,$symbol,$pattern,$csc=false,$inputs=array()) {
		$this->name = $name;
		$this->symbol = $symbol;
		$this->pattern = $pattern;
		$this->csc = $csc;
		$this->inputs = $inputs;
	}

	function validate ( $pan ) {
		$n = preg_replace('/\D/', '', $pan);
		if ( strlen($n) == 4 ) return true;
		return ( $this->match($n) && $this->checksum($n) );
	}

	function match ($number) {
		if ($this->pattern && !preg_match($this->pattern,$number)) return false;
		return true;
	}

	function checksum ($number) {
		$code = strrev($number);
		for ($cs = $i = 0; $i < strlen($code); $i++) {
			$d = intval($code[$i]);
			if ($i & 1) $d *= 2;
			$cs += $d % 10;
			if ($d > 9) $cs += 1;
		}
		return ($cs % 10 == 0);
	}

}

?>
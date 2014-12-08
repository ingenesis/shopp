<?php
/**
 * Scripts.php
 *
 * Controller for browser script queueing and delivery
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May  5, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage scripts
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Scripts
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
/** From BackPress */
if ( ! class_exists('WP_Scripts') ) {
	require( ABSPATH . WPINC . '/class.wp-dependencies.php' );
	require( ABSPATH . WPINC . '/class.wp-scripts.php' );
}

class ShoppScripts extends WP_Scripts {

	public function __construct() {
		do_action_ref_array( 'shopp_default_scripts', array(&$this) );

		add_action('wp_enqueue_scripts', array(&$this,'wp_dependencies'),1);
		add_action('admin_head', array(&$this,'wp_dependencies'),1);

		add_action('wp_head', array(&$this,'print_head_scripts'),15);
		add_action('admin_head', array(&$this,'print_head_scripts'),15);
		add_action('wp_footer', array(&$this,'print_footer_scripts'),15);
		add_action('admin_footer', array(&$this,'print_footer_scripts'),15);

	}

	public function do_item( $handle, $group = false ) {
		if(parent::do_item($handle,$group))
			$this->print_code .= $this->print_script_custom($handle);
	}

	public function print_head_scripts() {
		global $concatenate_scripts;

		if ( ! did_action('shopp_print_scripts') )
			do_action('shopp_print_scripts');

		script_concat_settings();
		$this->do_concat = $concatenate_scripts;
		$this->do_head_items();

		if ( apply_filters('shopp_print_head_scripts', true) )
			$this->print_script_request();

		$this->reset();
		return $this->done;
	}

	public function print_footer_scripts() {
		global $concatenate_scripts;

		if ( ! did_action('shopp_print_footer_scripts') )
			do_action('shopp_print_footer_scripts');

		script_concat_settings();
		$concatenate_scripts = defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : true;
		$this->do_concat = $concatenate_scripts;
		$this->do_footer_items();

		if ( apply_filters('shopp_print_footer_scripts', true) )
			$this->print_script_request();

		$this->reset();
		return $this->done;
	}

	public function print_script_request () {
		global $compress_scripts;

		$zip = $compress_scripts ? 1 : 0;
		if ( $zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP )
			$zip = 'gzip';

		$debug = ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '&debug=1' : '';

		if ( !empty($this->concat) ) {
			$ver = md5("$this->concat_version");
			if (shopp_setting('script_server') == 'plugin') {
				$src = trailingslashit(get_bloginfo('url')) . "?sjsl=" . trim($this->concat, ', ') . "&c={$zip}&ver=$ver" . $debug;
				if (is_ssl()) $src = str_replace('http://','https://',$src);
			} else $src = SHOPP_PLUGINURI . "/services/scripts.php?c={$zip}&load=" . trim($this->concat, ', ') . "&ver=$ver" . $debug;
			echo "<script type='text/javascript' src='" . esc_attr($src) . "'></script>\n";
		}

		if ( !empty($this->print_code) ) {
			echo "<script type='text/javascript'>\n";
			echo "/* <![CDATA[ */\n";
			echo $this->print_code;
			echo "/* ]]> */\n";
			echo "</script>\n";
		}

		if ( !empty($this->print_html) )
			echo $this->print_html;
	}

	public function print_scripts_l10n( $handle, $echo = true ) {
		if ( empty($this->registered[$handle]->extra['l10n']) || empty($this->registered[$handle]->extra['l10n'][0]) || !is_array($this->registered[$handle]->extra['l10n'][1]) )
			return false;

		$object_name = $this->registered[$handle]->extra['l10n'][0];

		$data = "var $object_name = {";
		$eol = '';
		foreach ( $this->registered[$handle]->extra['l10n'][1] as $var => $val ) {
			if ( 'l10n_print_after' == $var ) {
				$after = $val;
				continue;
			}
			$data .= "$eol$var: \"" . esc_js( $val ) . '"';
			$eol = ",";
		}
		$data .= "};\n";
		$data .= isset($after) ? "$after\n" : '';

		if ( $echo ) {
			echo "<script type='text/javascript'>\n";
			echo "/* <![CDATA[ */\n";
			echo $data;
			echo "/* ]]> */\n";
			echo "</script>\n";
			return true;
		} else {
			return $data;
		}
	}


	public function all_deps ( $handles, $recursion = false, $group = false ) {
		$r = parent::all_deps( $handles, $recursion );
		if ( !$recursion )
			$this->to_do = apply_filters( 'shopp_print_scripts_array', $this->to_do );
		return $r;
	}

	public function add ( $handle, $src, $deps = array(), $ver = false, $args = null ) {

		$debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;	// Determine if we are debugging the scripts
		if ( isset($_GET['debug']) && 1 == $_GET['debug'] ) $debug = true;
		$extension = '.js';									// Use .js extension for script files
		$suffix = '.min';									// Use .min for suffix
		$minsrc = str_replace($extension, $suffix . $extension, $src);

		// Add the suffix when not debugging and the suffix isn't already used (the file is not available unminified)
		if ( ! $debug && false === strpos( $src, $suffix . $extension ) && file_exists(ShoppLoader::basepath() . $minsrc ) )
			$src = $minsrc;

		return parent::add( $handle, $src, $deps, $ver, $args);
	}

	public function wp_dependencies () {
		global $wp_scripts;

		if ( !is_a($wp_scripts, 'WP_Scripts') )
			$wp_scripts = new WP_Scripts();

		$wpscripts = array_keys($wp_scripts->registered);

		$deps = array();
		foreach ($this->queue as $handle) {
			if (!isset($this->registered[$handle]) || !isset($this->registered[$handle]->deps)) continue;
			$wpdeps = array_intersect($this->registered[$handle]->deps,$wpscripts);
			$mydep = array_diff($this->registered[$handle]->deps,$wpdeps);
			$this->registered[$handle]->deps = $mydep;
		}

		if (!empty($wpdeps)) foreach ((array)$wpdeps as $handle) wp_enqueue_script($handle);

	}

	public function print_script_custom ($handle) {
		return !empty($this->registered[$handle]->extra['code'])?$this->registered[$handle]->extra['code']:false;
	}


} // END class ShoppScripts

function shopp_default_scripts (&$scripts) {

	$script = basename(__FILE__);
	$schema = ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off' ) ? 'https://' : 'http://';
	if ( defined('SHOPP_PLUGINURI') ) $url = SHOPP_PLUGINURI . '/core';
	else $url = preg_replace("|$script.*|i", '', $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

	$scripts->base_url = $url;
	$scripts->default_version = mktime(false,false,false,1,1,2010);
	$scripts->default_dirs = array('/ui/behaviors/','/ui/products');

	// Short checksum for cache control that changes with Shopp versions while masking it somewhat
	$version = hash('crc32b', ABSPATH . ShoppVersion::release());
	$version = (time());

	$scripts->add('shopp', '/ui/behaviors/shopp.js', array('jquery'), $version);
	$scripts->add_data('shopp', 'group', 1);

	$scripts->add('jquery-tmpl', '/ui/behaviors/jquery/jquery.tmpl.min.js', array('jquery'), $version);
	$scripts->add_data('jquery-tmpl', 'group', 1);

	$scripts->add('address', '/ui/behaviors/address.js', array('jquery','shopp'), $version);
	$scripts->add_data('address', 'group', 1);

	$scripts->add('cart', '/ui/behaviors/cart.js', array('jquery','shopp'), $version);
	$scripts->add_data('cart', 'group', 1);

	$scripts->add('catalog', '/ui/behaviors/catalog.js', array('jquery','shopp'), $version);
	$scripts->add_data('catalog', 'group', 1);

	$scripts->add('calendar', '/ui/behaviors/calendar.js', array('jquery','shopp'), $version);
	$scripts->add_data('calendar', 'group', 1);

	$scripts->add('daterange', '/ui/behaviors/daterange.js', array('jquery','shopp', 'calendar'), $version);
	$scripts->add_data('daterange', 'group', 1);

	$scripts->add('checkout', '/ui/behaviors/checkout.js', array('jquery','shopp'), $version);
	$scripts->add_data('checkout', 'group', 1);

	$scripts->add('colorbox', '/ui/behaviors/colorbox.min.js', array('jquery'), $version);
	$scripts->add_data('colorbox', 'group', 1);

	$scripts->add('ocupload', '/ui/behaviors/ocupload.js', array('jquery'), $version);
	$scripts->add_data('ocupload', 'group', 1);

	$scripts->add('orders', '/ui/behaviors/orders.js', array('jquery'), $version);
	$scripts->add_data('orders', 'group', 1);

	$scripts->add('scalecrop', '/ui/behaviors/scalecrop.js', array('jquery','jquery-ui-core','jquery-ui-draggable'), $version);
	$scripts->add_data('scalecrop', 'group', 1);

	$scripts->add('priceline', '/ui/behaviors/priceline.js', array('jquery','shopp'), $version);
	$scripts->add_data('priceline', 'group', 1);

	$scripts->add('editors', '/ui/behaviors/editors.js', array('jquery','jquery-ui-sortable'), $version);
	$scripts->add_data('editors', 'group', 1);

	$scripts->add('product-editor', '/ui/products/editor.js', array('jquery','priceline'), $version);
	$scripts->add_data('product-editor', 'group', 1);

	$scripts->add('category-editor', '/ui/categories/category.js', array('jquery','priceline'), $version);
	$scripts->add_data('category-editor', 'group', 1);

	$scripts->add('category-arrange', '/ui/categories/arrange.js', array('jquery','shopp'), $version);
	$scripts->add_data('category-arrange', 'group', 1);

	$scripts->add('products-arrange', '/ui/categories/products.js', array('jquery'), $version);
	$scripts->add_data('products-arrange', 'group', 1);

	$scripts->add('setup', '/ui/behaviors/setup.js', array('jquery'), $version);
	$scripts->add_data('setup', 'group', 1);

	$scripts->add('pageset', '/ui/behaviors/pageset.js', array('jquery'), $version);
	$scripts->add_data('pageset', 'group', 1);

	$scripts->add('payments', '/ui/behaviors/payments.js', array('jquery'), $version);
	$scripts->add_data('payments', 'group', 1);

	$scripts->add('storage', '/ui/behaviors/storage.js', array('jquery'), $version);
	$scripts->add_data('storage', 'group', 1);

	$scripts->add('shiprates', '/ui/behaviors/shiprates.js', array('jquery'), $version);
	$scripts->add_data('shiprates', 'group', 1);

	$scripts->add('taxrates', '/ui/behaviors/taxrates.js', array('jquery'), $version);
	$scripts->add_data('taxrates', 'group', 1);

	$scripts->add('imageset', '/ui/behaviors/imageset.js', array('jquery'), $version);
	$scripts->add_data('imageset', 'group', 1);

	$scripts->add('system', '/ui/behaviors/system.js', array('jquery'), $version);
	$scripts->add_data('system', 'group', 1);

	$scripts->add('shopp-swfobject', '/ui/behaviors/swfupload/plugins/swfupload.swfobject.min.js', array(), $version);
	$scripts->add_data('shopp-swfobject', 'group', 1);

	$scripts->add('shopp-swfupload-queue', '/ui/behaviors/swfupload/plugins/swfupload.queue.min.js', array(), $version);
	$scripts->add_data('shopp-swfupload-queue', 'group', 1);

	$scripts->add('spin', '/ui/behaviors/spin.js', array('jquery'), $version);
	$scripts->add_data('spin', 'group', 1);

	$scripts->add('swfupload', '/ui/behaviors/swfupload/swfupload.min.js', array('jquery','shopp-swfobject'), $version);
	$scripts->add_data('swfupload', 'group', 1);

	$scripts->add('suggest', '/ui/behaviors/suggest.js', array('jquery'), $version);
	$scripts->add_data('suggest', 'group', 1);

	$scripts->add('search-select', '/ui/behaviors/searchselect.js', array('jquery'), $version);
	$scripts->add_data('search-select', 'group', 1);

	$scripts->add('membership-editor', '/ui/memberships/editor.js', array('jquery','jquery-tmpl','search-select'), $version);
	$scripts->add_data('membership-editor', 'group', 1);

	$scripts->add('labelset', '/ui/behaviors/labelset.js', array('jquery','jquery-tmpl'), $version);
	$scripts->add_data('labelset', 'group', 1);

	$scripts->add('flot', '/ui/behaviors/flot/jquery.flot.min.js', array('jquery'), $version);
	$scripts->add_data('flot', 'group', 1);

	$scripts->add('flot-time', '/ui/behaviors/flot/jquery.flot.time.min.js', array('jquery'), $version);
	$scripts->add_data('flot-time', 'group', 1);

	$scripts->add('flot-grow', '/ui/behaviors/flot/jquery.flot.grow.min.js', array('flot'), $version);
	$scripts->add_data('flot-grow', 'group', 1);

	$scripts->add('jvectormap', '/ui/behaviors/jvectormap.min.js', array('jquery'), $version);
	$scripts->add_data('jvectormap', 'group', 1);

	$scripts->add('worldmap', '/ui/behaviors/worldmap.min.js', array('jvectormap'), $version);
	$scripts->add_data('worldmap', 'group', 1);

	$scripts->add('reports', '/ui/behaviors/reports.js', array(), $version);
	$scripts->add_data('reports', 'group', 1);

}

add_action('shopp_default_scripts', 'shopp_default_scripts');

function shopp_default_script_settings () {

	$base = array();

	$settings = Shopp::currency_format();
	if ( ! empty($settings) ) {
		$currency = array(
			// Currency formatting
			'cp' => $settings['cpos'],
			'c'  => $settings['currency'],
			'p'  => (int)$settings['precision'],
			't'  => $settings['thousands'],
			'd'  => $settings['decimals']
		);
		if ( isset($settings['grouping']) )
			$currency['g'] = is_array($settings['grouping']) ? join(',',$settings['grouping']) : $settings['grouping'];

	}
	if ( ! is_admin() ) $base = array('nocache' => is_shopp_page('account'));

	// Validation alerts
	shopp_localize_script('catalog', '$cv', array(
		'field' => __('Your %s is required.','Shopp'),
		'email' => __('The e-mail address you provided does not appear to be a valid address.','Shopp'),
		'minlen' => __('The %s you entered is too short. It must be at least %d characters long.','Shopp'),
		'pwdmm' => __('The passwords you entered do not match. They must match in order to confirm you are correctly entering the password you want to use.','Shopp'),
		'chkbox' => __('%s must be checked before you can proceed.','Shopp')
	));

	// Checkout page settings & localization
	shopp_localize_script('checkout', '$co', array(
		'ajaxurl' =>    admin_url('admin-ajax.php'),
		'loginname' =>  Shopp::__('You did not enter a login.'),
		'loginpwd' =>   Shopp::__('You did not enter a password to login with.'),
		'badpan' =>     Shopp::__('Not a valid card number.'),
		'submitting' => Shopp::__('Submitting&hellip;'),
		'error' =>      Shopp::__('An error occurred while submitting your order. Please try submitting your order again.'),
		'timeout' =>    (int)SHOPP_SUBMIT_TIMEOUT
	));

	// Validation alerts
	shopp_localize_script('cart', '$ct', array(
		'items' => __('Items','Shopp'),
		'total' => __('Total','Shopp'),
	));

	// Calendar localization
	shopp_localize_script('calendar', '$cal', array(
		// Month names
		'jan' => __('January', 'Shopp'),
		'feb' => __('February', 'Shopp'),
		'mar' => __('March', 'Shopp'),
		'apr' => __('April', 'Shopp'),
		'may' => __('May', 'Shopp'),
		'jun' => __('June', 'Shopp'),
		'jul' => __('July', 'Shopp'),
		'aug' => __('August', 'Shopp'),
		'sep' => __('September', 'Shopp'),
		'oct' => __('October', 'Shopp'),
		'nov' => __('November', 'Shopp'),
		'dec' => __('December', 'Shopp'),

		// Weekday names
		'sun' => __('Sun', 'Shopp'),
		'mon' => __('Mon', 'Shopp'),
		'tue' => __('Tue', 'Shopp'),
		'wed' => __('Wed', 'Shopp'),
		'thu' => __('Thu', 'Shopp'),
		'fri' => __('Fri', 'Shopp'),
		'sat' => __('Sat', 'Shopp')
	));

	// Product editor: unsaved changes warning
	shopp_localize_script('product-editor', '$msg', array(
		'confirm' => __('The changes you made will be lost if you navigate away from this page.', 'Shopp')
	));

	$defaults = apply_filters('shopp_js_settings', array_merge($currency, $base));
	shopp_localize_script('shopp', '$s',$defaults);
}

add_action('shopp_print_scripts', 'shopp_default_script_settings', 100);
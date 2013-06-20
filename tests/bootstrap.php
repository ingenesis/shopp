<?php
/**
 * Shopp customized unit testing bootstrapper. Incorporates and adapts much of the WP unittest
 * bootstrapper.
 */

// Test rig setup
define('SHOPP_UNSUPPORTED', false);
define('SHOPP_UNITTEST_DIR', __DIR__);
define('SHOPP_UNITTEST_CONFIG', SHOPP_UNITTEST_DIR.'/wp-tests-config.php');
define('WP_TESTS_FORCE_KNOWN_BUGS', false);
define('DISABLE_WP_CRON', true); // Stop HTTP requests during testing (which is in CLI mode)
define('WP_MEMORY_LIMIT', -1);
define('WP_MAX_MEMORY_LIMIT', -1);

// Vars we explicitly need to globalize (else they will not be placed in the global scope during testing)
global $table_prefix, $wp_embed, $wp_locale, $_wp_deprecated_widgets_callbacks, $wp_widget_factory,
	   $wpdb, $current_site, $current_blog, $wp_rewrite, $shortcode_tags, $wp, $wp_version, $Shopp;

if ( !is_readable( SHOPP_UNITTEST_CONFIG ) )
	die( "ERROR: wp-tests-config.php is missing! Please use wp-tests-config-sample.php to create a config file.\n" );

require_once 'PHPUnit/Autoload.php';
require_once SHOPP_UNITTEST_CONFIG;

define('WP_UNITTEST_DIR', realpath(ABSPATH . '../includes'));
define('DIR_TESTDATA', WP_UNITTEST_DIR . '/data');

// Simulate the HTTP request (since we're actually in CLI mode here)
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

$multisite = (int) ( defined( 'WP_TESTS_MULTISITE') && WP_TESTS_MULTISITE );
system( WP_PHP_BINARY . ' ' . escapeshellarg( WP_UNITTEST_DIR.'/install.php' ) . ' ' . escapeshellarg( SHOPP_UNITTEST_CONFIG ) . ' ' . $multisite );

if ( $multisite ) {
	echo "Running as multisite..." . PHP_EOL;
	define( 'MULTISITE', true );
	define( 'SUBDOMAIN_INSTALL', false );
	define( 'DOMAIN_CURRENT_SITE', WP_TESTS_DOMAIN );
	define( 'PATH_CURRENT_SITE', '/' );
	define( 'SITE_ID_CURRENT_SITE', 1 );
	define( 'BLOG_ID_CURRENT_SITE', 1 );
	$GLOBALS['base'] = '/';
} else {
	echo "Running as single site... To run multisite, use -c multisite.xml" . PHP_EOL;
}
unset( $multisite );

require WP_UNITTEST_DIR . '/functions.php';

// Preset WordPress options used to activate themes, plugins, as well as  other settings.
$GLOBALS['wp_tests_options'] = array('active_plugins' => array( 'shopp/Shopp.php' ));

function wp_tests_options( $value ) {
	$key = substr( current_filter(), strlen( 'pre_option_' ) );
	return $GLOBALS['wp_tests_options'][$key];
}

foreach ( array_keys( $GLOBALS['wp_tests_options'] ) as $key ) {
	tests_add_filter( 'pre_option_'.$key, 'wp_tests_options' );
}

// function shopp_tests_run () {
// 	$GLOBALS['Shopp'] = new Shopp();
// }

// We want a clean Shopp installation
function shopp_tests_install () {
	$Installation = new ShoppInstallation;
	$Installation->install();
}

function shopp_test_setup() {
	$Installation->setup();
	$Installation->images();
	$Installation->roles();
}

// tests_add_filter('muplugins_loaded','shopp_tests_run',100);
tests_add_filter('plugins_loaded','shopp_tests_install');
tests_add_filter('shopp_init', 'shopp_tests_setup', 1);

// Load WordPress
require_once ABSPATH . '/wp-settings.php';

// Delete any default posts & related data
_delete_all_posts();

require WP_UNITTEST_DIR . '/testcase.php';
require WP_UNITTEST_DIR . '/testcase-xmlrpc.php';
require WP_UNITTEST_DIR . '/testcase-ajax.php';
require WP_UNITTEST_DIR . '/exceptions.php';
require WP_UNITTEST_DIR . '/utils.php';
require SHOPP_UNITTEST_DIR.'/testcase.php';

/**
 * A child class of the PHP test runner.
 *
 * Not actually used as a runner. Rather, used to access the protected
 * longOptions property, to parse the arguments passed to the script.
 *
 * If it is determined that phpunit was called with a --group that corresponds
 * to an @ticket annotation (such as `phpunit --group 12345` for bugs marked
 * as #WP12345), then it is assumed that known bugs should not be skipped.
 *
 * If WP_TESTS_FORCE_KNOWN_BUGS is already set in wp-tests-config.php, then
 * how you call phpunit has no effect.
 */
class WP_PHPUnit_TextUI_Command extends PHPUnit_TextUI_Command {
	function __construct( $argv ) {
		$options = PHPUnit_Util_Getopt::getopt(
			$argv,
			'd:c:hv',
			array_keys( $this->longOptions )
		);
		$ajax_message = true;
		foreach ( $options[0] as $option ) {
			switch ( $option[0] ) {
				case '--exclude-group' :
					$ajax_message = false;
					continue 2;
				case '--group' :
					$groups = explode( ',', $option[1] );
					foreach ( $groups as $group ) {
						if ( is_numeric( $group ) || preg_match( '/^(UT|Plugin)\d+$/', $group ) )
							WP_UnitTestCase::forceTicket( $group );
					}
					$ajax_message = ! in_array( 'ajax', $groups );
					continue 2;
			}
		}
		if ( $ajax_message )
			echo "Not running ajax tests... To execute these, use --group ajax." . PHP_EOL;
	}
}
new WP_PHPUnit_TextUI_Command( $_SERVER['argv'] );
return;

// Test data
$productdefinitions = array(
	array(
		'name' => '1930s Chrome Art Deco Decanter',
		'slug' => '1930s-decanter',
		'publish' => array('flag' => true)
	),
	array(
		'name' => 'Multi-Prism Spectroscope',
		'slug' => 'multi-prism-spectroscope',
		'publish' => array('flag' => true)
	),
	array(
		'name' => 'Code is Poetry T-Shirt',
		'slug' => 'code-is-poetry-t-shirt',
		'publish' => array('flag' => true)
	)
);

$lastyear = new DateTime();
$lastyear = $lastyear->modify('-1 year')->format('Y-m-d H:i:s');

$nextyear = new DateTime();
$nextyear = $nextyear->modify('+1 year')->format('Y-m-d H:i:s');

$promodefinitions = array(
	array(
		'name' => '2 PC Off',
		'status' => 'enabled',
		'type' => 'Percentage Off',
		'target' => 'Cart',
		'discount' => '2.0',
		'search' => 'any',
		'starts' => $lastyear,
		'ends' => $nextyear,
		'rules' => array(
			1 => array(
				'property' => 'Promo code',
				'logic' => 'Is equal to',
				'value' => '2Percent'
			))
	),
	array(
		'name' => '3 Dollars Off',
		'status' => 'enabled',
		'type' => 'Amount Off',
		'target' => 'Cart Item',
		'discount' => '3.0',
		'search' => 'any',
		'starts' => $lastyear,
		'ends' => $nextyear,
		'rules' => array(
			'item' => array(
				'property' => 'Quantity',
                'logic' => 'Is greater than',
                'value' => 0
			),
			1 => array(
				'property' => 'Promo code',
				'logic' => 'Is equal to',
				'value' => '3DollarsOff'
			))
	)
);

foreach ($productdefinitions as $productdata) {
	shopp_add_product($productdata);
}

foreach ($promodefinitions as $promodata) {
	$Promotion = new Promotion();
	$Promotion->updates($promodata);
	$Promotion->save();
	$Promotion->id;
}
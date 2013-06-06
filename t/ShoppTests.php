#!/usr/bin/php -q
<?php
/**
 * ShoppTests
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  6 October, 2009
 * @package
 **/

/**
 * Initialize
 **/

require('PHPUnit/Autoload.php');
require('xHTMLvalidator.php');

// Abstraction Layer
class ShoppTestCase extends PHPUnit_Framework_TestCase {


	protected $backupGlobals = FALSE;
	var $_time_limit = 120; // max time in seconds for a single test function
	var $shopp_settings = array(); // testing settings, so tests can play nice

	function setUp() {
		// error types taken from PHPUnit_Framework_TestResult::run
		$this->_phpunit_err_mask = E_USER_ERROR | E_NOTICE | E_STRICT;
		$this->_old_handler = set_error_handler(array(&$this, '_error_handler'));
		if (is_null($this->_old_handler)) {
			restore_error_handler();
		}

		set_time_limit($this->_time_limit);
		$db =& DB::get();
		if (!$db->dbh) $db->connect(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	}

	function tearDown() {
		$Shopp = Shopp::object();
		// $Shopp->Catalog = false;
		// $Shopp->Category = false;
		// $Shopp->Product = false;
		unset($this->shopp_settings);
		if (!is_null($this->_old_handler)) {
			restore_error_handler();
		}
	}

	function assertValidMarkup ($string) {
		$validator = new xHTMLvalidator();
		$this->assertTrue($validator->validate($string),
			'Failed to validate: '.$validator->showErrors()."\n$string");
	}

	/**
	 * Treat any error, which wasn't handled by PHPUnit as a failure
	 */
	function _error_handler($errno, $errstr, $errfile, $errline) {
		// @ in front of statement
		if (error_reporting() == 0) {
			return;
		}
		// notices and strict warnings are passed on to the phpunit error handler but don't trigger an exception
		if ($errno | $this->_phpunit_err_mask) {
			PHPUnit_Util_ErrorHandler::handleError($errno, $errstr, $errfile, $errline);
		}
		// warnings and errors trigger an exception, which is included in the test results
		else {

			//TODO: we should raise custom exception here, sth like WP_PHPError
			throw new PHPUnit_Framework_Error(
				$errstr,
				$errno,
				$errfile,
				$errline,
				$trace
			);
		}
	}

	function _set_setting ( $setting, $value ) {
		$this->shopp_settings[$setting] = shopp_setting($setting);
		shopp_set_setting($setting, $value);
	}

	function _restore_setting ( $setting ) {
		if ( isset($this->shopp_settings[$setting]) ) {
			shopp_set_setting($setting, $this->shopp_settings[$setting]);
			unset($this->shopp_settings[$setting]);
		}
	}

} // end ShoppTestCase class

function shopp_run_tests($classes, $classname='') {
	$suite = new PHPUnit_Framework_TestSuite();
	foreach ($classes as $testcase)
	if (!$classname or strtolower($testcase) == strtolower($classname)) {
		$suite->addTestSuite($testcase);
	}

	#return PHPUnit::run($suite);
	$result = new PHPUnit_Framework_TestResult;
	require('PHPUnit/TextUI/ResultPrinter.php');
	$printer = new PHPUnit_TextUI_ResultPrinter(NULL,true,true);
	$result->addListener($printer);
	return array($suite->run($result), $printer);
}

function get_all_test_cases() {
	$test_classes = array();
	$skipped_classes = explode(',',SHOPP_SKIP_TESTS);
	$all_classes = get_declared_classes();
	// only classes that extend ShoppTestCase and have names that don't start with _ are included
	foreach ($all_classes as $class)
		if ($class{0} != '_' && is_descendent_class('ShoppTestCase', $class) && !in_array($class,$skipped_classes))
			$test_classes[] = $class;
	return $test_classes;
}

function is_descendent_class($parent, $class) {

	$ancestor = strtolower(get_parent_class($class));

	while ($ancestor) {
		if ($ancestor == strtolower($parent)) return true;
		$ancestor = strtolower(get_parent_class($ancestor));
	}

	return false;
}

function get_shopp_test_files($dir) {
	$tests = array();
	$dh = opendir($dir);
	while (($file = readdir($dh)) !== false) {
		if ($file{0} == '.') continue;
		$path = realpath($dir . DIRECTORY_SEPARATOR . $file);
		$fileparts = pathinfo($file);
		if (is_file($path) and $fileparts['extension'] == 'php')
			$tests[] = $path;
		elseif (is_dir($path))
			$tests = array_merge($tests, get_shopp_test_files($path));
	}
	closedir($dh);

	return $tests;
}

function shopptests_print_result($printer, $result) {
	$printer->printResult($result, timer_stop());
}

// Override error logging to hide stderr messages from being output by PHPUnit
ini_set('error_log','/dev/null');

// Main Procedures
require('wp-config.php');

if (!defined('SHOPP_SQL_DATAFILE')) define('SHOPP_SQL_DATAFILE','shopptest.sql');
system('mysql -u '.DB_USER.' --password='.DB_PASSWORD.' '.DB_NAME.' < '.SHOPP_SQL_DATAFILE);

require(ABSPATH.'wp-settings.php');
add_action('shopp_init',create_function('','error_reporting(0); ini_set("display_errors",false);'));

if (defined('SHOPP_IMAGES_PATH')) shopp_set_setting('image_path', SHOPP_IMAGES_PATH);
if (defined('SHOPP_PRODUCTS_PATH')) shopp_set_setting('products_path', SHOPP_PRODUCTS_PATH);
if (!defined('SHOPP_SKIP_TESTS')) define('SHOPP_SKIP_TESTS','');

define('SHOPP_TESTS_DIR',dirname(__FILE__).'/tests');
$files = get_shopp_test_files(SHOPP_TESTS_DIR);

foreach ($files as $file) require($file);
$tests = get_all_test_cases();

list ($result, $printer) = shopp_run_tests($tests);
shopptests_print_result($printer,$result);

?>
<?php

require 'xHTMLvalidator.php';

// Abstraction Layer
class ShoppTestCase extends WP_UnitTestCase {


	protected $backupGlobals = FALSE;
	public $shopp_settings = array(); // testing settings, so tests can play nice

	static function setUpBeforeClass () {
		set_time_limit(0);

		global $wpdb;
		$wpdb->suppress_errors = false;
		$wpdb->show_errors = true;
		$wpdb->db_connect();

	}

	static function tearDownAfterClass () {
		self::resetTables();
	}

	static function transaction () {
		sDB::query( 'SET autocommit = 0;' );
		sDB::query( 'START TRANSACTION;' );
	}

	static function rollback () {
		sDB::query( 'ROLLBACK' );
	}

	function setUp() {
		ini_set('display_errors', 1 );
		// $this->factory = new WP_UnitTest_Factory;
		// $this->clean_up_global_scope();
		// $this->start_transaction();

		// error types taken from PHPUnit_Framework_TestResult::run
		$this->_phpunit_err_mask = E_STRICT;
		$this->_old_handler = set_error_handler(array($this, '_error_handler'));
		if ( is_null($this->_old_handler) ) {
			restore_error_handler();
		}

	}

	function tearDown() {
		unset($this->shopp_settings);
		if (!is_null($this->_old_handler)) {
			restore_error_handler();
		}
	}

	static function resetTables () {
		$classes = array(
			'Address','ShoppProduct','Promotion','ProductSummary','Price','Customer','Purchase','Purchased'
		);
		foreach ($classes as $classname) {
			$table = DatabaseObject::tablename(get_class_property($classname, 'table'));
			sDB::query('DELETE FROM '. $table);
		}

		global $wpdb;
		$skipped = array('options');
		foreach ($wpdb->tables as $table) {
			if ( in_array($table, $skipped) ) continue;
			sDB::query('DELETE FROM ' . $wpdb->$table);
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
		if ( error_reporting() == 0 ) {
			return;
		}

		$pattern = '/^Argument (\d)+ passed to (?:(\w+)::)?(\w+)\(\) must be an instance of (\w+), (\w+) given/';

		$typehints = array(
			'boolean'   => 'is_bool',
			'integer'   => 'is_int',
			'float'     => 'is_float',
			'string'    => 'is_string',
			'resource'  => 'is_resource'
		);

		if ( E_RECOVERABLE_ERROR == $errno && preg_match( $pattern, $errstr, $matches ) ) {

			list($matched, $index, $class, $function, $hint, $type) = $matches;

            list($null,$backtrace,) = debug_backtrace();

			if ( isset($typehints[$hint]) ) {
				if ($backtrace['function'] == $function) {
					$argument = $backtrace['args'][$index - 1];

					if ( call_user_func($typehints[$hint],$argument) ) return;
				}
			}
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

	static function imgrequesthash ($id, $args) {
		$key = (defined('SECRET_AUTH_KEY') && SECRET_AUTH_KEY != '') ? SECRET_AUTH_KEY : DB_PASSWORD;

		if ($args[1] == 0) $args[1] = $args[0];

		$message = rtrim(join(',',$args),',');

		return sprintf('%u',crc32($key.$id.','.$message));
	}

} // end ShoppTestCase class

class ShoppFactory {

}


class ShoppProductFactory {

}
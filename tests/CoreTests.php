<?php
/**
 * Core Tests for Shopp library functions.
 *
 * @copyright Ingenesis Limited, 23 July 2013
 */
class CoreTests extends ShoppTestCase {
	public function test_unsupported () {
		$this->assertTrue(defined('SHOPP_UNSUPPORTED'));
	}
}
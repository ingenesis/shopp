<?php
/**
 * Log.php
 *
 * Log settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since	 @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenLog extends ShoppSettingsScreenController {

	/**
	 * Processes form updates for the screen
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {
		if ( ! isset($_POST['resetlog']) ) return;

		ShoppErrorLogging()->reset();
		$this->notice(Shopp::__('The log file has been reset.'));
	}

	/**
	 * Render the screen UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {
		include $this->ui('log.php');
	}

}
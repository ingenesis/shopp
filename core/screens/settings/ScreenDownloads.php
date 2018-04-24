<?php
/**
 * Downloads.php
 *
 * Download settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Downloads settings screen controller
 *
 * @since 1.5
 * @package Shopp/Admin/Settings
 **/
class ShoppScreenDownloads extends ShoppSettingsScreenController {

	/**
	 * Register processing operation methods
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function ops() {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	/**
	 * Update settings from the form
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {
 		shopp_set_formsettings();
		$this->notice(Shopp::__('Downloads settings saved.'));
	}

	/**
	 * Renders the screen
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {
		$downloads = array('1', '2', '3', '5', '10', '15', '25', '100');
		$time = array(
			'1800'     => Shopp::__('%d minutes', 30),
			'3600'     => Shopp::__('%d hour', 1),
			'7200'     => Shopp::__('%d hours', 2),
			'10800'    => Shopp::__('%d hours', 3),
			'21600'    => Shopp::__('%d hours', 6),
			'43200'    => Shopp::__('%d hours', 12),
			'86400'    => Shopp::__('%d day', 1),
			'172800'   => Shopp::__('%d days', 2),
			'259200'   => Shopp::__('%d days', 3),
			'604800'   => Shopp::__('%d week', 1),
			'2678400'  => Shopp::__('%d month', 1),
			'7952400'  => Shopp::__('%d months', 3),
			'15901200' => Shopp::__('%d months', 6),
			'31536000' => Shopp::__('%d year', 1),
		);

		include $this->ui('downloads.php');
	}

}
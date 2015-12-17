<?php
/**
 * Setup.php
 *
 * The Shopp settings Setup screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Shopp admin settings Setup screen controller
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppScreenSetup extends ShoppSettingsScreenController {

	public function assets () {
		shopp_enqueue_script('setup');
		shopp_localize_script('setup', '$ss', array(
			'loading' => Shopp::__('Loading&hellip;'),
			'prompt' => Shopp::__('Select your %s&hellip;', '%s'),
		));
		shopp_enqueue_script('selectize');
		$this->nonce($this->request('page'));
	}

	public function ops () {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	public function updates () {
		if ( ! isset($_POST['settings']['target_markets']) )
			asort($_POST['settings']['target_markets']);

		// Save all other settings
		shopp_set_formsettings();

		$update = false;

		// Update country changes
		$country = ShoppBaseLocale()->country();
		if ( $country != $this->form('country') ) {
			$country = strtoupper($this->form('country'));
			$countries = ShoppLookup::countries();

			// Validate the country
			if ( ! isset($countries[ $country ]) )
				return $this->notice(Shopp::__('The country provided is not valid.'), 'error');

			$update = true;
		}

		// Update state changes
		$state = ShoppBaseLocale()->state();
		if ( ShoppBaseLocale()->state() != $this->form('state') ) {
			$state = strtoupper($this->form('state'));
			$states = ShoppLookup::country_zones(array($country));

			// Validate the state
			if ( ! empty($states) && ! isset($states[ $country ][ $state ]) )
				return $this->notice(Shopp::__('The %s provided is not valid.', ShoppBaseLocale()->division()), 'error');

			$update = true;
		}

		// Save base locale changes
		if ( $update )
			ShoppBaseLocale()->save($country, $state);

		$this->notice(Shopp::__('Shopp settings saved.'));
	}

	public function screen () {

		if ( ! current_user_can('shopp_settings') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Welcome screen handling
		if ( ! empty($_POST['setup']) )
			shopp_set_setting('display_welcome', 'off');

		$countries = ShoppLookup::countries();
		$basecountry = ShoppBaseLocale()->country();
		$countrymenu = Shopp::menuoptions($countries, $basecountry, true);
		$basestates = ShoppLookup::country_zones(array($basecountry));
		$statesmenu = '';
		if ( ! empty($basestates) )
			$statesmenu = Shopp::menuoptions($basestates[ $basecountry ], ShoppBaseLocale()->state(), true);

		$targets = shopp_setting('target_markets');
		if ( is_array($targets) )
			$targets = array_map('stripslashes', $targets);
		if ( ! $targets ) $targets = array();

		$zones_ajaxurl = wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_country_zones');

		include $this->ui('setup.php');

	}

} // class ShoppScreenSetup

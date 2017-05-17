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

	/**
	 * Setup extra js/css assets needed.
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
	public function assets () {
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('jquery-ui-sortable');
		shopp_enqueue_script('setup');
		shopp_localize_script('setup', '$ss', array(
			'loading' => Shopp::__('Loading&hellip;'),
			'prompt' => Shopp::__('Select your %s&hellip;', '%s'),
		));
		shopp_enqueue_script('selectize');
		$this->nonce($this->request('page'));
	}

	/**
	 * Queue up operation handlers.
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
	public function ops () {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	/**
	 * Process setting changes.
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
	public function updates () {

		// Save all other settings
		$this->saveform();

		$update = false;

		// Update country changes
		$country = ShoppBaseLocale()->country();
		$formcountry = strtoupper($this->form('country'));
		if ( ! empty($formcountry) && ShoppBaseLocale()->country() != $formcountry ) {
			$country = $formcountry;
			$countries = ShoppLookup::countries();

			// Validate the country
			if ( ! empty($country) && ! isset($countries[ $country ]) )
				return $this->notice(Shopp::__('The country provided is not valid.'), 'error');

			$update = true;
		}

		// Update state changes
		$formstate = strtoupper($this->form('state'));
		if ( ! empty($formstate) && ShoppBaseLocale()->state() != $formstate ) {
			$state = $formstate;
			$states = ShoppLookup::country_zones(array($country));

			// Validate the state
			if ( ! empty($states) && ! isset($states[ $country ][ $state ]) )
				return $this->notice(Shopp::__('The %s provided is not valid.', ShoppBaseLocale()->division()), 'error');

			$update = true;
		}

		// Save base locale changes
		if ( $update && ! empty($country) )
			ShoppBaseLocale()->save($country, $state);


		// Sort target_markets, if requested, before saving
		if ( isset($this->posted['sort_markets']) ) {
			$sort = $this->posted['sort_markets'];

			if ( 'alpha' == $sort )
				asort($this->form['target_markets']);
			elseif ( 'region' == $sort )
				$this->form['target_markets'] = $this->regionsort($this->form['target_markets']);

			$this->saveform(); // Save form again after sorting target markets

		}

		if ( $update )
			$this->notice(Shopp::__('Shopp settings saved.'));
	}

	/**
	 * Prepare data and show the UI.
	 *
	 * @since 1.4
	 *
	 * @return void
	 */
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

	/**
	 * Helper method to sort target markets by region.
	 *
	 * Note that the sort order within the region is defined by the order specified in
	 * the core/locales/regions.php file.
	 *
	 * @since 1.4
	 *
	 * @param array $targets The list of enabled target markets
	 * @return array The region-sorted list of enabled target markets
	 */
	private static function regionsort ( array $targets ) {
		$locale = ShoppBaseLocale()->region();
		$base = ShoppBaseLocale()->country();

		$regionsdata = ShoppLookup::regions(true);

		$baselocale = $regionsdata[ $locale ];
		unset($regionsdata[ $locale ]);

		$regions = array($locale => $baselocale) + $regionsdata;

		$marketcodes = array_keys($targets);
		$sorted = array();
		$sorted[ $base ] = $targets[ $base ]; // Add base locale country first
		foreach ( $regions as $name => $countries ) {
			foreach ( $countries as $country ) {
				if ( $country == $base ) continue; // Skip base locale
				if ( in_array($country, $marketcodes) )
					$sorted[ $country ] = $targets[ $country ];
			}
		}

		return $sorted;
	}

} // class ShoppScreenSetup
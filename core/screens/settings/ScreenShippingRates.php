<?php
/**
 * ScreenShippingRates.php
 *
 * Shipping Rates screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenShipping extends ShoppSettingsScreenController {

	/**
	 * Enqueue required script/style assets for the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('shiprates');
		shopp_localize_script( 'shiprates', '$ps', array(
			'confirm' => __('Are you sure you want to remove this shipping rate?','Shopp'),
		));

		$this->nonce($this->request('page'));
	}

	/**
	 * Setup the layout for the screen
	 * 
	 * This is used to initialize any metaboxes or tables.
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function layout() {
		$this->table('ShoppScreenShippingRatesTable');
	}

	/**
	 * Register action request methods
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function actions() {
		add_action('shopp_admin_settings_ops', array($this, 'delete') );
	}

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
		$this->notice(Shopp::__('Shipping settings saved.'));
	}

	/**
	 * Process deleting requested shipping rate settings
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function delete() {
		$delete = $this->request('delete');

		if ( false === $delete ) return;

		$active = (array) shopp_setting('active_shipping');

		$index = false;
		if ( strpos($delete, '-') !== false )
			list($delete, $index) = explode('-', $delete);

		if ( ! array_key_exists($delete, $active) )
			return $this->notice(Shopp::__('The requested shipping method could not be deleted because it does not exist.'), 'error');

		if ( is_array($active[ $delete ]) ) {
			if ( array_key_exists($index, $active[ $delete ]) ) {
				unset($active[ $delete ][ $index ]);

				if ( empty($active[ $delete ]) )
					unset($active[ $delete ]);
			}
		} else unset($active[ $delete ]);

		shopp_set_setting('active_shipping', $active);

		$this->notice(Shopp::__('Shipping method setting removed.'));

		Shopp::redirect($this->url());
	}

	/**
	 * Render the screen UI
	 *
	 * @todo this method needs a lot of cleanup
	 * 
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {

		$shipcarriers = Lookup::shipcarriers();
		$serviceareas = array('*', ShoppBaseLocale()->code());
		foreach ( $shipcarriers as $c => $record ) {
			if ( ! in_array($record->areas, $serviceareas) ) continue;
			$carriers[ $c ] = $record->name;
		}
		unset($shipcarriers);

		$shipping_carriers = shopp_setting('shipping_carriers');
		if ( empty($shipping_carriers) )
			$shipping_carriers = array_keys($carriers);

		$imperial = 'imperial' == ShoppBaseLocale()->units();
		$weights = $imperial ?
					array('oz' => Shopp::__('ounces (oz)'), 'lb' => Shopp::__('pounds (lbs)')) :
					array('g'  => Shopp::__('gram (g)'),    'kg' => Shopp::__('kilogram (kg)'));

		$weightsmenu = Shopp::menuoptions($weights, shopp_setting('weight_unit'), true);

		$dimensions = $imperial ?
				 		array('in' => Shopp::__('inches (in)'),      'ft' => Shopp::__('feet (ft)')) :
						array('cm' => Shopp::__('centimeters (cm)'), 'm'  => Shopp::__('meters (m)'));

		$dimsmenu = Shopp::menuoptions($dimensions, shopp_setting('dimension_unit'), true);

		$rates = shopp_setting('shipping_rates');
		if (!empty($rates)) ksort($rates);

		$Shopp = Shopp::object();
		$Shipping = $Shopp->Shipping;
		$Shipping->settings(); // Load all installed shipping modules for settings UIs

		$methods = $Shopp->Shipping->methods;

		$edit = false;
		if ( isset($_REQUEST['id']) ) $edit = (int)$_REQUEST['id'];

		$active = shopp_setting('active_shipping');
		if (!$active) $active = array();

		if (isset($_POST['module'])) {

			$setting = false;
			$module = isset($_POST['module'])?$_POST['module']:false;
			$id = isset($_POST['id'])?$_POST['id']:false;

			if ($id == $module) {
				if (isset($_POST['settings'])) shopp_set_formsettings();
				/** Save shipping service settings **/
				$active[$module] = true;
				shopp_set_setting('active_shipping',$active);
				$updated = __('Shipping settings saved.','Shopp');
				// Cancel editing if saving
				if (isset($_POST['save'])) unset($_REQUEST['id']);

				$Errors = ShoppErrors();
				do_action('shopp_verify_shipping_services');

				if ($Errors->exist()) {
					// Get all addon related errors
					$failures = $Errors->level(SHOPP_ADDON_ERR);
					if (!empty($failures)) {
						$updated = __('Shipping settings saved but there were errors: ','Shopp');
						foreach ($failures as $error)
							$updated .= '<p>'.$error->message(true,true).'</p>';
					}
				}

			} else {
				/** Save shipping calculator settings **/

				$setting = $_POST['id'];
				if (empty($setting)) { // Determine next available setting ID
					$index = 0;
					if (is_array($active[$module])) $index = count($active[$module]);
					$setting = "$module-$index";
				}

				// Cancel editing if saving
				if (isset($_POST['save'])) unset($_REQUEST['id']);

				$setting_module = $setting; $id = 0;
				if (false !== strpos($setting,'-'))
					list($setting_module,$id) = explode('-',$setting);

				// Prevent fishy stuff from happening
				if ($module != $setting_module) $module = false;

				// Save shipping calculator settings
				$Shipper = $Shipping->get($module);
				if ($Shipper && isset($_POST[$module])) {
					$Shipper->setting($id);

					$_POST[$module]['label'] = stripslashes($_POST[$module]['label']);

					// Sterilize $values
					foreach ($_POST[$module]['table'] as $i => &$row) {
						if (isset($row['rate'])) $row['rate'] = Shopp::floatval($row['rate']);
						if (!isset($row['tiers'])) continue;

						foreach ($row['tiers'] as &$tier) {
							if (isset($tier['rate'])) $tier['rate'] = Shopp::floatval($tier['rate']);
						}
					}

					// Delivery estimates: ensure max equals or exceeds min
					ShippingFramework::sensibleestimates($_POST[$module]['mindelivery'], $_POST[$module]['maxdelivery']);

					shopp_set_setting($Shipper->setting, $_POST[$module]);
					if (!array_key_exists($module, $active)) $active[$module] = array();
					$active[$module][(int) $id] = true;
					shopp_set_setting('active_shipping', $active);
					$this->notice(Shopp::__('Shipping settings saved.'));
				}

			}
		}

		$postcodes = ShoppLookup::postcodes();
		foreach ( $postcodes as &$postcode)
			$postcode = ! empty($postcode);

		$lookup = array(
			'regions' => array_merge(array('*' => Shopp::__('Anywhere')), ShoppLookup::regions()),
			'regionmap' => ShoppLookup::regions('id'),
			'countries' => ShoppLookup::countries(),
			'areas' => ShoppLookup::country_areas(),
			'zones' => ShoppLookup::country_zones(),
			'postcodes' => $postcodes
		);

		$ShippingTemplates = new TemplateShippingUI();
		add_action('shopp_shipping_module_settings', array($Shipping, 'templates'));

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('shipping.php');
	}

}
<?php
/**
 * ShippingRates.php
 *
 * Shipping Rates screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenShipping extends ShoppSettingsScreenController {

	public function assets () {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('shiprates');
		shopp_localize_script( 'shiprates', '$ps', array(
			'confirm' => __('Are you sure you want to remove this shipping rate?','Shopp'),
		));

		$this->nonce($this->request('page'));
	}

	public function layout () {
		$this->table('ShoppShippingRatesTable');
	}

	public function actions () {
		add_action('shopp_admin_settings_ops', array($this, 'delete') );
	}

	public function ops () {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	public function updates () {
 		shopp_set_formsettings();
		$this->notice(Shopp::__('Shipping settings saved.'));
	}

	public function delete () {
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

	public function screen () {

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

		$weightsmenu = menuoptions($weights, shopp_setting('weight_unit'), true);

		$dimensions = $imperial ?
				 		array('in' => Shopp::__('inches (in)'),      'ft' => Shopp::__('feet (ft)')) :
						array('cm' => Shopp::__('centimeters (cm)'), 'm'  => Shopp::__('meters (m)'));

		$dimsmenu = menuoptions($dimensions, shopp_setting('dimension_unit'), true);

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

} // class ShoppScreenShipping


/**
 * Shipping Rates Table UI renderer
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppShippingRatesTable extends ShoppAdminTable {

	public function prepare_items() {
		$active = (array)shopp_setting('active_shipping');

		$Shopp = Shopp::object();
		$Shipping = $Shopp->Shipping;

		$Shipping->settings(); // Load all installed shipping modules for settings UIs
		$Shipping->ui(); // Setup setting UIs

		$settings = array();	    // Registry of loaded settings for table-based shipping rates for JS
		$this->items = array();	    // Registry for activated shipping rate modules
		$this->installed = array(); // Registry of available shipping modules installed

		foreach ( $Shipping->active as $name => $Module ) {
			if ( version_compare($Shipping->modules[ $name ]->since, '1.2' ) == -1 ) continue; // Skip 1.1 modules, they are incompatible

			$default_name = strtolower($name);
			$fullname = $Module->methods();
			$this->installed[ $name ] = $fullname;

			if ( $Module->ui->tables ) {
				$defaults[ $default_name ] = $Module->ui->settings();
				$defaults[ $default_name ]['name'] = $fullname;
				$defaults[ $default_name ]['label'] = Shopp::__('Shipping Method');
			}

			if ( array_key_exists($name, $active) )
				$ModuleSetting = $active[ $name ];
			else continue; // Not an activated shipping module, go to the next one

			$Entry = new StdClass();
			$Entry->id = sanitize_title_with_dashes($name);
			$Entry->label = $Shipping->modules[ $name ]->name;
			$Entry->type = $Shipping->modules[ $name ]->name;

			$Entry->setting = $name;
			if ( $this->request('id') == $Entry->setting )
				$Entry->editor = $Module->ui();

			// Setup shipping service shipping rate entries and settings
			if ( ! is_array($ModuleSetting) ) {
				$Entry->destinations = array($Shipping->active[ $name ]->destinations);
				$this->items[ $name ] = $Entry;
				continue;
			}

			// Setup shipping calcualtor shipping rate entries and settings
			foreach ( $ModuleSetting as $id => $m ) {
				$Entry->setting = "$name-$id";
				$Entry->settings = shopp_setting($Entry->setting);

				if ( $this->request('id') == $Entry->setting )
					$Entry->editor = $Module->ui();

				if ( isset($Entry->settings['label']) )
					$Entry->label = $Entry->settings['label'];

				$Entry->destinations = array();

				$min = $max = false;
				if ( isset($Entry->settings['table']) && is_array($Entry->settings['table']) ) {

					foreach ( $Entry->settings['table'] as $tablerate ) {
						$destination = false;
						$d = ShippingSettingsUI::parse_location($tablerate['destination']);

						if ( ! empty($d['zone']) )        $Entry->destinations[] = $d['zone'] . ' (' . $d['countrycode'] . ')';
						elseif ( ! empty($d['area']) )    $Entry->destinations[] = $d['area'];
						elseif ( ! empty($d['country']) ) $Entry->destinations[] = $d['country'];
						elseif ( ! empty($d['region']) )  $Entry->destinations[] = $d['region'];
					}

					if ( ! empty($Entry->destinations) )
						$Entry->destinations = array_keys(array_flip($Entry->destinations)); // Combine duplicate destinations
				}

				$this->items[ $Entry->setting ] = $Entry;

				$settings[ $Entry->setting ] = shopp_setting($Entry->setting);
				$settings[ $Entry->setting ]['id'] = $Entry->setting;
				$settings[ $Entry->setting ] = array_merge($defaults[ $default_name ], $settings[ $Entry->setting ]);
				if ( isset($settings[ $Entry->setting ]['table']) ) {
					usort($settings[ $Entry->setting ]['table'], array('ShippingFramework', '_sorttier'));
					foreach ( $settings[ $Entry->setting ]['table'] as &$r ) {
						if ( isset($r['tiers']) )
							usort($r['tiers'], array('ShippingFramework', '_sorttier'));
					}
				}
			} // end foreach ( $ModuleSetting )

		} // end foreach ( $Shipping->active )

		$this->set_pagination_args( array(
			'total_items' => count($this->items),
			'total_pages' => 1
		) );

		$postcodes = ShoppLookup::postcodes();
		foreach ( $postcodes as &$postcode)
			$postcode = ! empty($postcode);

		$lookup = array(
			'regions'   => array_merge(array('*' => Shopp::__('Anywhere')), ShoppLookup::regions()),
			'regionmap' => ShoppLookup::regions('id'),
			'countries' => ShoppLookup::countries(),
			'areas'     => ShoppLookup::country_areas(),
			'zones'     => ShoppLookup::country_zones(),
			'postcodes' => $postcodes
		);

		shopp_custom_script('shiprates', '
			var shipping = ' . json_encode(array_map('sanitize_title_with_dashes',array_keys($this->installed))) . ',
				defaults = ' . json_encode($defaults) . ',
				settings = ' . json_encode($settings) . ',
				lookup   = ' . json_encode($lookup) . ';'
		);

	}

	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;

		echo  '<select name="id" id="shipping-option-menu">'
			. '	<option value="">' . Shopp::__('Add a shipping method&hellip;') . '</option>'
			. '	' . Shopp::menuoptions($this->installed, false, true)
			. '</select>'
			. '<button type="submit" name="add-shipping-option" id="add-shipping-option" class="button-secondary hide-if-js" tabindex="9999">' . Shopp::__('Add Shipping Option') . '</button>';

	}

	public function get_columns() {
		return array(
			'name'         => Shopp::__('Name'),
			'type'         => Shopp::__('Type'),
			'destinations' => Shopp::__('Destinations'),
		);
	}

	public function no_items() {
		Shopp::_e('No shipping methods, yet.');
	}

	public function editor( $Item ) {

		$deliverymenu = ShoppLookup::timeframes_menu();

		echo '<script id="delivery-menu" type="text/x-jquery-tmpl">'
		   . Shopp::menuoptions($deliverymenu, false, true)
		   . '</script>';

		$data = array(
			'${mindelivery_menu}' => Shopp::menuoptions($deliverymenu, $Item->settings['mindelivery'], true),
			'${maxdelivery_menu}' => Shopp::menuoptions($deliverymenu, $Item->settings['maxdelivery'], true),
			'${fallbackon}' => ( 'on' ==  $Item->settings['fallback'] ) ? 'checked="checked"' : '',
			'${cancel_href}' => $this->url
		);

		echo ShoppUI::template($Item->editor, $data);

	}

	public function column_name( $Item ) {

		$edit = wp_nonce_url(add_query_arg('id', $Item->setting));
		$delete = wp_nonce_url(add_query_arg('delete', $Item->setting));

		return '<a href="' . esc_url($edit) . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($Item->label) . '&quot;" class="edit row-title">'
			 . esc_html($Item->label)
			 . '</a>' . "\n"

			 . '<div class="row-actions">'
			 . '	<span class="edit"><a href="' . esc_url($edit) . '" title="' . Shopp::__('Edit'). ' &quot;' . esc_attr($Item->label) . '&quot;" class="edit">' . Shopp::__('Edit') . '</a> | </span><span class="delete"><a href="' . esc_url($delete) . '" title="' . Shopp::__('Delete') . ' &quot;' . esc_attr($Item->label) . '&quot;" class="delete">' . Shopp::__('Delete') . '</a></span>'
			 . '</div>';

	}

	public function column_type( $Item ) {
		return esc_html($Item->type);
	}

	public function column_destinations( $Item ) {
		return join(', ', array_map('esc_html', $Item->destinations));
	}

} // end ShoppShippingRatesTable

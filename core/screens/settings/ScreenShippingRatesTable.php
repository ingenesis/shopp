<?php
/**
 * ScreenShippingRatesTable.php
 *
 * Shipment boxes table UI renderer
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenShippingRatesTable extends ShoppAdminTable {

	/**
	 * Prepare shipping rate setting data for rendering in the table
	 *
	 * @since 1.4
	 * @return void
	 **/
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

	/**
	 * Render custom table navigation elements
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;

		echo  '<select name="id" id="shipping-option-menu">'
			. '	<option value="label" disabled selected>' . Shopp::__('Add a shipping method&hellip;') . '</option>'
			. '	' . Shopp::menuoptions($this->installed, false, true)
			. '</select>'
			. '<button type="submit" name="add-shipping-option" id="add-shipping-option" class="button-secondary hide-if-js" tabindex="9999">' . Shopp::__('Add Shipping Option') . '</button>';

	}

	/**
	 * Provide the list of columns for the table
	 *
	 * @since 1.4
	 * @return array The list of columns to render for the table
	 **/
	public function get_columns() {
		return array(
			'name'         => Shopp::__('Name'),
			'type'         => Shopp::__('Type'),
			'destinations' => Shopp::__('Destinations'),
		);
	}

	/**
	 * Renders a default message when no items are available
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function no_items() {
		Shopp::_e('No shipping methods, yet.');
	}

	/**
	 * Renders the editor template
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return void
	 **/
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

	/**
	 * Renders the name column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
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

	/**
	 * Renders the type column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_type( $Item ) {
		return esc_html($Item->type);
	}

	/**
	 * Renders the destination column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_destinations( $Item ) {
		return join(', ', array_map('esc_html', $Item->destinations));
	}

}
<?php
/**
 * ScreenTaxes.php
 *
 * Taxes settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Taxes settings screen controller
 *
 * @since 1.5
 **/
class ShoppScreenTaxes extends ShoppSettingsScreenController {

	/**
	 * Enqueue required script/style assets for the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('ocupload');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('taxrates');
		shopp_enqueue_script('suggest');
		shopp_localize_script('taxrates', '$tr', array(
			'confirm' => __('Are you sure you want to remove this tax rate?','Shopp'),
		));
	}

	/**
	 * Register action request methods
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function actions() {
		add_action('shopp_admin_settings_actions', array($this, 'delete') );
	}
    
	/**
	 * Gets the current tax rate settings
	 *
	 * @since 1.5
	 * 
	 * @return array The list of current tax rate settings
	 **/
    public static function settings() {
        $rates = array();
        $setting = shopp_setting('taxrates');
        if ( ! empty($setting) ) 
            $rates = $setting;
        
        return $rates;
    }

	/**
	 * Delete the requested tax rate entry
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function delete() {
		$delete = $this->request('delete');
		if ( false === $delete) return;

		check_admin_referer('shopp_delete_taxrate');

		$rates = (array)shopp_setting('taxrates');

		if ( empty($rates[ $delete ]) )
			return $this->notice(Shopp::__('Could not delete the tax rate because that tax setting was not found.'));

		array_splice($rates, $delete, 1);
		shopp_set_setting('taxrates', $rates);

		$this->notice(Shopp::__('Tax rate deleted.'));

		Shopp::redirect(add_query_arg(array('delete' => null, '_wpnonce' =>null)));

	}

	// public function ops() {
	// 	add_action('shopp_admin_settings_ops', array($this, 'addrule') );
	// 	add_action('shopp_admin_settings_ops', array($this, 'deleterule') );
	// 	add_action('shopp_admin_settings_ops', array($this, 'addlocals') );
	// 	add_action('shopp_admin_settings_ops', array($this, 'rmvlocals') );
	// 	add_action('shopp_admin_settings_ops', array($this, 'upload') );
	// 	add_action('shopp_admin_settings_ops', array($this, 'updates') );
	// }

	/**
	 * Setup the layout for the screen
	 * 
	 * This is used to initialize any metaboxes or tables.
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function layout() {
		$this->table('ShoppScreenTaxesTable');
	}

	/**
	 * Handle saving form updates
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {

        $rates = self::settings();
        
		$updates = $this->form('taxrates');
		if ( ! empty($updates) ) {
			if ( array_key_exists('new', $updates) ) {
				$rates[] = $updates['new'];
			} else $rates = array_replace($rates, $updates);

			// Re-sort taxes from generic to most specific
			usort($rates, array($this, 'sortrates'));
			$rates = stripslashes_deep($rates);

			shopp_set_setting('taxrates', $rates);
			unset($_POST['settings']['taxrates']);

			$this->notice(Shopp::__('Tax rates saved.'));
		}

		$inclusive = $this->form('tax_inclusive');
		$shipping = $this->form('tax_shipping');
		if ( ! ( empty($inclusive) || empty($shipping) ) ) {
			shopp_set_formsettings(); // Save other tax settings
			$this->notice(Shopp::__('Tax settings saved.'));
		}

	}

	/**
	 * Add a tax rule
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function addrule() {
		if ( ! isset($_POST['addrule']) ) return;
		$id = $_POST['id'];
		$rates = shopp_setting('taxrates');
		$rates[ $id ]['rules'][] = array('p' => '', 'v' => '');
		shopp_set_setting('taxrates', $rates);
	}

	/**
	 * Delete a tax rule
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function deleterule() {
		if ( empty($_POST['deleterule']) ) return;

		$rates = shopp_setting('taxrates');
		list($id, $row) = explode(',', $_POST['deleterule']);

		if ( empty($rates[ $id ]['rules']) ) return;

		array_splice($rates[ $id ]['rules'], $row, 1);
		shopp_set_setting('taxrates', $rates);
	}

	/**
	 * Add local tax rates
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function addlocals() {
		if ( empty($_POST['add-locals']) ) return;

		$id = $_POST['id'];
		$rates = shopp_setting('taxrates');
		$rates[ $id ]['haslocals'] = true;
		shopp_set_setting('taxrates', $rates);
	}

	/**
	 * Remove local tax rates
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function rmvlocals() {
		if ( empty($_POST['remove-locals']) ) return;

		$id = $_POST['id'];
		$rates = shopp_setting('taxrates');
		$rates[ $id ]['haslocals'] = false;
		$rates[ $id ]['locals'] = array();
		shopp_set_setting('taxrates', $rates);
	}

	/**
	 * Helper to sort tax rates from most specific to most generic
	 *
	 * (more specific) <------------------------------------> (more generic)
	 * more/less conditions, local taxes, country/zone, country, All Markets
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $rates The tax rate settings to sort
	 * @return int The sorting value
	 **/
	public function sortrates( $a, $b ) {

		$args = array('a' => $a, 'b' => $b);
		$scoring = array('a' => 0 ,'b' => 0);

		foreach ( $args as $key => $rate ) {
			$score = &$scoring[ $key ];

			// More conditional rules are more specific
			if ( isset($rate['rules']) ) $score += count($rate['rules']);

			// If there are local rates add to specificity
			if ( isset($rate['haslocals']) && $rate['haslocals'] ) $score++;

			if ( isset($rate['zone']) && $rate['zone'] ) $score++;

			if ( '*' != $rate['country'] ) $score++;

			$score += (float)$rate['rate'] / 100;
		}

		if ( $scoring['a'] == $scoring['b'] ) return 0;
		else return ( $scoring['a'] > $scoring['b'] ? 1 : -1 );
	}

	public function upload() {
		if ( ! isset($_FILES['ratefile']) ) return false;

		$upload = $_FILES['ratefile'];
		$filename = $upload['tmp_name'];
		if ( empty($filename) && empty($upload['name']) && ! isset($_POST['upload']) ) return false;

		$error = false;

		if ( $upload['error'] != 0 )
			return $this->notice(ShoppLookup::errors('uploads', $upload['error']));

		if ( ! is_readable($filename) )
			return $this->notice(ShoppLookup::errors('uploadsecurity', 'is_readable'));

		if ( empty($upload['size']) )
			return $this->notice(ShoppLookup::errors('uploadsecurity', 'is_empty'));

		if ( $upload['size'] != filesize($filename) )
			return $this->notice(ShoppLookup::errors('uploadsecurity', 'filesize_mismatch'));

		if ( ! is_uploaded_file($filename) )
			return $this->notice(ShoppLookup::errors('uploadsecurity', 'is_uploaded_file'));

		$data = file_get_contents($upload['tmp_name']);
		$cr = array("\r\n", "\r");

		$formats = array(0 => false, 3 => 'xml', 4 => 'tab', 5 => 'csv');
		preg_match('/((<[^>]+>.+?<\/[^>]+>)|(.+?\t.+?[\n|\r])|(.+?,.+?[\n|\r]))/', $data, $_);
		$format = $formats[ count($_) ];
		if ( ! $format )
			return $this->notice(Shopp::__('The uploaded file is not properly formatted as an XML, CSV or tab-delimmited file.'));

		$_ = array();
		switch ( $format ) {
			case 'xml':
				/*
				Example XML import file:
					<localtaxrates>
						<taxrate name="Kent">1</taxrate>
						<taxrate name="New Castle">0.25</taxrate>
						<taxrate name="Sussex">@since 1.5</taxrate>
					</localtaxrates>

				Taxrate record format:
					<taxrate name="(Name of locality)">(Percentage of the supplemental tax)</taxrate>

				Tax rate percentages should be represented as percentage numbers, not decimal percentages:
					1.25	= 1.25%	(0.0125)
					10		= 10%	(0.1)
				*/
				$XML = new xmlQuery($data);
				$taxrates = $XML->tag('taxrate');

				while ( $rate = $taxrates->each() ) {
					$name = $rate->attr(false, 'name');
					$value = $rate->content();
					$_[ $name ] = $value;
				}
				break;
			case 'csv':
				ini_set('auto_detect_line_endings', true);

				if ( ( $csv = fopen($upload['tmp_name'], 'r') ) === false )
					return $this->notice(ShoppLookup::errors('uploadsecurity', 'is_readable'));

				while ( ( $data = fgetcsv($csv, 1000) ) !== false )
					$_[ $data[0] ] = ! empty($data[1]) ? $data[1] : 0;

				fclose($csv);
				ini_set('auto_detect_line_endings',false);
				break;
			case 'tab':
			default:
				$data = str_replace($cr, "\n", $data);
				$lines = explode("\n", $data);
				foreach ( $lines as $line ) {
					list($key, $value) = explode("\t", $line);
					$_[ $key ] = $value;
				}
		}

		if ( empty($_) )
			return $this->notice(Shopp::__('No useable tax rates could be found. The uploaded file may not be properly formatted.'));

		$id = $_POST['id'];
		$rates = shopp_setting('taxrates');
		$rates[ $id ]['locals'] = apply_filters('shopp_local_taxrates_upload', $_);
		shopp_set_setting('taxrates', $rates);
	}

	/**
	 * Render the screen UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('taxes.php');

	}

	/**
	 * Output the multipart form encoding type attribute
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function formattrs() {
		echo ' enctype="multipart/form-data" accept="text/plain,text/xml"';
	}

}
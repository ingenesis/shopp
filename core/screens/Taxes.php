<?php
/**
 * Taxes.php
 *
 * Taxes settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Taxes settings screen controller
 *
 * @since 1.4
 **/
class ShoppScreenTaxes extends ShoppSettingsScreenController {

	public function assets() {
		shopp_enqueue_script('ocupload');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('taxrates');
		shopp_enqueue_script('suggest');
		shopp_localize_script('taxrates', '$tr', array(
			'confirm' => __('Are you sure you want to remove this tax rate?','Shopp'),
		));
	}

	public function actions() {
		add_action('shopp_admin_settings_actions', array($this, 'delete') );
	}

	public function delete() {
		$delete = $this->request('delete');
		if ( false === $delete) return;

		check_admin_referer('shopp_delete_taxrate');

		$rates = shopp_setting('taxrates');

		if ( empty($rates[ $delete ]) )
			return $this->notice(Shopp::__('Could not delete the tax rate because that tax setting was not found.'));

		array_splice($rates, $delete, 1);
		shopp_set_setting('taxrates', $rates);

		$this->notice(Shopp::__('Tax rate deleted.'));

		Shopp::redirect(add_query_arg(array('delete' => null, '_wpnonce' =>null)));

	}

	public function ops() {
		add_action('shopp_admin_settings_ops', array($this, 'addrule') );
		add_action('shopp_admin_settings_ops', array($this, 'deleterule') );
		add_action('shopp_admin_settings_ops', array($this, 'addlocals') );
		add_action('shopp_admin_settings_ops', array($this, 'rmvlocals') );
		add_action('shopp_admin_settings_ops', array($this, 'upload') );
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	public function layout() {
		$this->table('ShoppTaxesRatesTable');
	}

	public function updates() {

		$rates = shopp_setting('taxrates');

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
		}

		shopp_set_formsettings(); // Save other tax settings

		$this->notice(Shopp::__('Tax settings saved.'));

		Shopp::redirect(add_query_arg());
	}

	public function addrule () {
		if ( ! isset($_POST['addrule']) ) return;
		$id = $_POST['id'];
		$rates = shopp_setting('taxrates');
		$rates[ $id ]['rules'][] = array('p' => '', 'v' => '');
		shopp_set_setting('taxrates', $rates);
	}

	public function deleterule () {
		if ( empty($_POST['deleterule']) ) return;

		$rates = shopp_setting('taxrates');
		list($id, $row) = explode(',', $_POST['deleterule']);

		if ( empty($rates[ $id ]['rules']) ) return;

		array_splice($rates[ $id ]['rules'], $row, 1);
		shopp_set_setting('taxrates', $rates);
	}

	public function addlocals () {
		if ( empty($_POST['add-locals']) ) return;

		$id = $_POST['id'];
		$rates = shopp_setting('taxrates');
		$rates[ $id ]['haslocals'] = true;
		shopp_set_setting('taxrates', $rates);
	}

	public function rmvlocals () {
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
	public function sortrates ( $a, $b ) {

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

			$score += $rate['rate'] / 100;
		}

		if ( $scoring['a'] == $scoring['b'] ) return 0;
		else return ( $scoring['a'] > $scoring['b'] ? 1 : -1 );
	}

	public function upload () {
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
						<taxrate name="Sussex">1.4</taxrate>
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

	public function screen() {

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('taxes.php');

	}

	public function formattrs() {
		echo ' enctype="multipart/form-data" accept="text/plain,text/xml"';
	}

} // class ShoppScreenTaxes

/**
 * Renders the taxes settings table
 *
 * @since 1.4
 **/
class ShoppTaxesRatesTable extends ShoppAdminTable {

	/** @var string $conditional_ui The conditional rules user interface template. */
	public $conditional_ui = '';

	/** @var string $localrate_ui The local rates user interface template. */
	public $localrate_ui = '';

	/** @var array $template The item property template. */
	static $template = array(
		'id'        => false,
		'rate'      => 0,
		'country'   => false,
		'zone'      => false,
		'rules'     => array(),
		'locals'    => array(),
		'haslocals' => false
	);

	public function prepare_items() {

		$this->id = 'taxrates';

		$defaults = array(
			'paged' => 1,
			'per_page' => 25,
			'action' => false,
		);
		$args = array_merge($defaults, $_GET);
		extract($args, EXTR_SKIP);

		$rates = (array)shopp_setting('taxrates');

		$this->items = array();
		foreach ( $rates as $index => $taxrate )
			$this->items[ $index ] = array_merge(self::$template, array('id' => $index), $taxrate);

		$specials = array(ShoppTax::ALL => Shopp::__('All Markets'));

		if ( ShoppTax::euvat(false, ShoppBaseLocale()->country(), ShoppTax::EUVAT) )
			$specials[ ShoppTax::EUVAT ] = Shopp::__('European Union');

		$this->countries = array_filter(array_merge($specials, (array) shopp_setting('target_markets')));
		$this->zones = 	ShoppLookup::country_zones();

		$total = count($this->items);
		$this->set_pagination_args( array(
			'total_items' => $total,
			'total_pages' => $total / $per_page,
			'per_page' => $per_page
		) );

		shopp_custom_script('taxrates', '
			var suggurl = "' . wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_suggestions') . '",
				rates   = ' . json_encode($this->items) . ',
				zones   = ' . json_encode($this->zones) . ',
				lookup  = ' . json_encode(ShoppLookup::localities()) . ',
				taxrates = [];
		');
	}

	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;
		echo '<button type="submit" name="addrate" id="addrate" class="button-secondary" tabindex="9999">' . Shopp::__('Add Tax Rate') . '</button>';
	}

	public function get_columns() {
		return array(
			'rate'        => Shopp::__('Rate'),
			'local'       => Shopp::__('Local Rates'),
			'conditional' => Shopp::__('Conditional'),
		);
	}

	public function no_items() {
		Shopp::_e('No tax rates, yet.');
	}

	protected function editing( $Item ) {
		return ( (string) $Item['id'] === $this->request('id') );
	}

	public function editor( $Item ) {
		extract($Item);

		$conditions = array();
		foreach ( $rules as $ruleid => $rule ) {
			$conditionals = array(
				'${id}' => $edit,
				'${ruleid}' => $ruleid,
				'${property_menu}' => $this->property_menu($rule['p']),
				'${rulevalue}' => esc_attr($rule['v'])
			);
			$conditions[] = str_replace(array_keys($conditionals), $conditionals, $this->template_conditional());
		}

		$localrates = array();
		foreach ($locals as $localename => $localerate) {
			$localrate_data = array(
				'${id}' => $edit,
				'${localename}' => $localename,
				'${localerate}' => (float)$localerate,
			);
			$localrates[] = str_replace(array_keys($localrate_data), $localrate_data, $this->template_localrate());
		}

		$data = array(
			'${id}'           => $id,
			'${rate}'         => percentage($rate, array('precision' => 4)),
			'${countries}'    => menuoptions($this->countries, $country, true),
			'${zones}'        => ! empty($zones[ $country ]) ? menuoptions($zones[ $country ], $zone, true) : '',
			'${conditions}'   => join('', $conditions),
			'${haslocals}'    => $haslocals,
			'${localrates}'   => join('', $localrates),
			'${instructions}' => $localerror ? '<p class="error">' . $localerror . '</p>' : $instructions,
			'${compounded}'   => Shopp::str_true($compound) ? 'checked="checked"' : '',
			'${cancel_href}'  => add_query_arg(array('id' => null, '_wpnonce' => null))
		);

		if ( $conditions )
			$data['no-conditions'] = '';

		if ( ! empty($zones[ $country ]) )
			$data['no-zones'] = '';

		if ( $haslocals )
			$data['no-local-rates'] = '';
		else $data['has-local-rates'] = '';

		if ( count($locals) > 0 )
			$data['instructions'] = 'hidden';

		echo ShoppUI::template($this->editor, $data);
	}

	/**
	 * Gets the generated conditional rules property menu options.
	 *
	 * @since 1.4
	 *
	 * @param string $selected The currently selected option.
	 * @return string The generated menu options.
	 **/
	public function property_menu( $selected = false ) {
		return Shopp::menuoptions(array(
			'product-name'     => Shopp::__('Product name is'),
			'product-tags'     => Shopp::__('Product is tagged'),
			'product-category' => Shopp::__('Product in category'),
			'customer-type'    => Shopp::__('Customer type is')
		), $selected, true);
	}

	/**
	 * Get or set the conditional user interface markup.
	 *
	 * @since 1.4
	 *
	 * @param string $template Set the markup template.
	 * @return string The conditionals user interface markup.
	 **/
	public function template_conditional( $template = null ) {
		if ( isset($template) )
			$this->conditional_ui = $template;
		return $this->conditional_ui;
	}
	public function template_localrate( $template = null ) {
		if ( isset($template) )
			$this->localrate_ui = $template;
		return $this->localrate_ui;
	}

	public function column_rate( $Item ) {
		extract($Item);
		$rate = Shopp::percentage(Shopp::floatval($rate), array('precision'=>4));
		$location = $this->countries[ $country ];

		$label = "$rate &mdash; $location";


		$edit_link = wp_nonce_url(add_query_arg('id', $id), 'shopp_edit_taxrate');
		$delete_link = wp_nonce_url(add_query_arg('delete', $id), 'shopp_delete_taxrate');

		echo '<a class="row-title edit" href="' . esc_url($edit_link) . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($label) . '&quot;">' . esc_html($label) . '</a>';
		echo $this->row_actions( array(
			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
			'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>',
		) );

	}

	public function column_local( $Item ) {
		$this->checkbox($Item['haslocals'], $Item['haslocals'] ? Shopp::__('This tax setting has local tax rates defined.') : Shopp::__('No local tax rates are defined.'));
	}

	public function column_conditional( $Item ) {
		$conditionals = count($Item['rules']) > 0;
		$this->checkbox($conditionals, $conditionals ? Shopp::__('This tax setting has conditional rules defined.') : Shopp::__('No conditions are defined for this tax rate.'));
	}

	protected function checkbox( $set, $title ) {
		echo '<div class="checkbox ' . ( $set ? ' checked' : '' ) . '" title="' . esc_html($title) . '"><span class="hidden">' . esc_html($title) . '</div>';
	}

} // class ShoppTaxesRatesTable

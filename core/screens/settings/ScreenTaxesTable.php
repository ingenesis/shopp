<?php
/**
 * ScreenTaxesTable.php
 *
 * Tax rates table UI renderer
 *
 * @copyright Ingenesis Limited, February 2015-2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenTaxesTable extends ShoppAdminTable {

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

	/**
	 * Prepare tax rate setting data for rendering in the table
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function prepare_items() {

		$this->id = 'taxrates';

		$defaults = array(
			'paged' => 1,
			'per_page' => 25,
			'action' => false,
		);
		$args = array_merge($defaults, $_GET);
		extract($args, EXTR_SKIP);

		$rates = ShoppScreenTaxes::settings();

		$this->items = array();
		foreach ( $rates as $index => $taxrate )
			$this->items[ $index ] = array_merge(self::$template, array('id' => $index), $taxrate);

		$specials = array(
            ShoppTax::ALL => Shopp::__('All Markets'),
            ShoppTax::EUVAT => Shopp::__('European Union')
        );
        
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

	/**
	 * Render custom table navigation elements
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;
		echo '<button type="submit" name="addrate" id="addrate" class="button-secondary" tabindex="9999">' . Shopp::__('Add Tax Rate') . '</button>';
	}

	/**
	 * Provide the list of columns for the table
	 *
	 * @since 1.5
	 * @return array The list of columns to render for the table
	 **/
	public function get_columns() {
		return array(
			'rate'        => Shopp::__('Rate'),
			'local'       => Shopp::__('Local Rates'),
			'conditional' => Shopp::__('Conditional'),
		);
	}

	/**
	 * Renders a default message when no items are available
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function no_items() {
		Shopp::_e('No tax rates, yet.');
	}

	/**
	 * Determines if editing is requested for the current item
	 *
	 * @since 1.5
	 * @return boolean True if it is an edit request, false otherwise
	 **/
	protected function editing( $Item ) {
		return ( (string) $Item['id'] === $this->request('id') );
	}

	/**
	 * Renders the editor template
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return void
	 **/
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
	 * @since 1.5
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
	 * @since 1.5
	 *
	 * @param string $template Set the markup template.
	 * @return string The conditionals user interface markup.
	 **/
	public function template_conditional( $template = null ) {
		if ( isset($template) )
			$this->conditional_ui = $template;
		return $this->conditional_ui;
	}
	
	/**
	 * Get or set the local rate UI markup
	 *
	 * @since 1.5
	 *
	 * @param string $template Set the markup template.
	 * @return string The conditionals user interface markup.
	 **/
	public function template_localrate( $template = null ) {
		if ( isset($template) )
			$this->localrate_ui = $template;
		return $this->localrate_ui;
	}

	/**
	 * Renders the rate column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_rate( $Item ) {
		extract($Item);
		$rate = Shopp::percentage(Shopp::floatval($rate), array('precision'=>4));
		$location = $this->countries[ $country ];

		$label = "$rate &mdash; $location";


		$edit_link = wp_nonce_url(add_query_arg('id', $id), 'shopp_edit_taxrate');
		$delete_link = wp_nonce_url(add_query_arg('delete', $id), 'shopp_delete_taxrate');

		return '<a class="row-title edit" href="' . esc_url($edit_link) . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($label) . '&quot;">' . esc_html($label) . '</a>' .
                $this->row_actions( array(
        			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
        			'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>',
        		) );

	}

	/**
	 * Renders the local column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_local( $Item ) {
		return $this->checkbox($Item['haslocals'], $Item['haslocals'] ? Shopp::__('This tax setting has local tax rates defined.') : Shopp::__('No local tax rates are defined.'));
	}

	/**
	 * Renders the conditional column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_conditional( $Item ) {
		$conditionals = count($Item['rules']) > 0;
		return $this->checkbox($conditionals, $conditionals ? Shopp::__('This tax setting has conditional rules defined.') : Shopp::__('No conditions are defined for this tax rate.'));
	}

	/**
	 * Helper to render the checkbox markup
	 *
	 * @since 1.5
	 * @param boolean $set True if the checkbox is checked, false otherwise
	 * @param string $title The item title
	 * @return string The markup to output
	 **/
	protected function checkbox( $set, $title ) {
		return '<div class="checkbox ' . ( $set ? ' checked' : '' ) . '" title="' . esc_html($title) . '"><span class="hidden">' . esc_html($title) . '</div>';
	}

}
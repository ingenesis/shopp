<?php
/**
 * ScreenPaymentsTable.php
 *
 * Payments table UI renderer
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPaymentsTable extends ShoppAdminTable {

	/**
	 * Prepare payments setting item data for rendering in the table
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function prepare_items() {

		$defaults = array(
			'paged' => 1,
			'per_page' => 25,
			'action' => false,
		);
		$args = array_merge($defaults, $_GET);
		extract($args, EXTR_SKIP);

		$start = ( $per_page * ( $paged - 1 ) );
		$edit = false;

		$Gateways = Shopp::object()->Gateways;

		$Gateways->settings();	// Load all installed gateways for settings UIs
		do_action('shopp_setup_payments_init');

		$Gateways->ui();		// Setup setting UIs

		$activated = $Gateways->activated();
		foreach ( $activated as $slug => $classname ) {
			$Gateway = $Gateways->get($classname);
			$Gateway->payid = $slug;
			$this->items[] = $Gateway;
			if ( $this->request('id') == $slug )
				$this->editor = $Gateway->ui();
		}

        add_action('shopp_gateway_module_settings', array($Gateways, 'templates'));

		$total = count($this->items);
		$this->set_pagination_args( array(
			'total_items' => $total,
			'total_pages' => $total / $per_page,
			'per_page' => $per_page
		) );

        $installed = array();
        foreach ( (array)$Gateways->modules as $slug => $module )
            $installed[ $slug ] = $module->name;
        asort($installed);

        $this->installed = $installed;

        shopp_custom_script('payments', 'var gateways = ' . json_encode(array_map('sanitize_title_with_dashes',array_keys($installed))) . ';'
            . ( $event ? "jQuery(document).ready(function($) { $(document).trigger('" . $event . "Settings',[$('#payments-settings-table tr." . $event . "-editing')]); });" : '' )
        );

	}

	/**
	 * Render custom table navigation elements
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;

		echo  '<select name="id" id="payment-option-menu">'
			. '	<option value="label" disabled selected>' . Shopp::__('Add a payment system&hellip;') . '</option>'
			. '	' . Shopp::menuoptions($this->installed, false, true)
			. '</select>'
			. '<button type="submit" name="add-payment-option" id="add-payment-option" class="button-secondary hide-if-js" tabindex="9999">' . Shopp::__('Add Payment System') . '</button>';

	}

	/**
	 * Provide the list of columns for the table
	 *
	 * @since 1.5
	 * @return array The list of columns to render for the table
	 **/
	public function get_columns() {
		return array(
			'name'      => Shopp::__('Name'),
			'processor' => Shopp::__('Processor'),
			'payments'  => Shopp::__('Payments'),
			'ssl'       => Shopp::__('SSL'),
			'captures'  => Shopp::__('Captures'),
			'recurring' => Shopp::__('Recurring'),
			'refunds'   => Shopp::__('Refunds'),
		);
	}

	/**
	 * Renders a default message when no items are available
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function no_items() {
		Shopp::_e('No payment methods, yet.');
	}

	/**
	 * Determines if editing is requested for the current item
	 *
	 * @since 1.5
	 * @return boolean True if it is an edit request, false otherwise
	 **/
	protected function editing( $Item ) {
		return ( $Item->payid === $this->request('id') );
	}

	/**
	 * Renders the editor template
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return void
	 **/
	public function editor( $Item ) {
		$data = array(
			'${editing_class}' => "$event-editing",
			'${cancel_href}' => add_query_arg(array('id' => null, '_wpnonce' => null )),
			'${instance}' => $id
		);

		// Handle payment data value substitution for multi-instance payment systems
		foreach ( $payment as $name => $value )
			$data['${' . $name . '}'] = $value;

		echo ShoppUI::template($this->editor, $data);
	}

	public function column_name( $Item ) {
		$label = empty($Item->settings['label']) ? Shopp::__('(no label)') : $Item->settings['label'];

		$edit_link = wp_nonce_url(add_query_arg('id', $Item->payid), 'shopp_edit_gateway');
		$delete_link = wp_nonce_url(add_query_arg('delete', $Item->payid), 'shopp_delete_gateway');

		return '<a class="row-title edit" href="' . esc_url($edit_link) . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($label) . '&quot;">' . esc_html($label) . '</a>' .
                $this->row_actions( array(
        			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
        			'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>',
        		) );

	}

	/**
	 * Renders the name column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_processor( $Item ) {
		return esc_html($Item->name);
	}

	/**
	 * Renders the payments column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_payments( $Item ) {
		if ( empty($Item->settings['cards']) ) return;

		$cards = array();
		foreach ( (array) $Item->settings['cards'] as $symbol ) {
			$Paycard = ShoppLookup::paycard($symbol);
			if ( $Paycard ) $cards[] = $Paycard->name;
		}

		return esc_html(join(', ', $cards));
	}

	/**
	 * Renders the ssl column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_ssl( $Item ) {
		return $this->checkbox( $Item->secure, $Item->secure ? Shopp::__('SSL/TLS Required'): Shopp::__('No SSL/TLS Required') );
	}

	/**
	 * Renders the captures column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_captures( $Item ) {
		return $this->checkbox( $Item->captures, $Item->captures ? Shopp::__('Supports delayed payment capture') : Shopp::__('No delayed payment capture support') );
	}

	/**
	 * Renders the recurring column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_recurring( $Item ) {
		return $this->checkbox( $Item->recurring, $Item->recurring ? Shopp::__('Supports recurring payments') : Shopp::__('No recurring payment support') );
	}

	/**
	 * Renders refunds captures column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_refunds( $Item ) {
		return $this->checkbox( $Item->refunds, $Item->refunds ? Shopp::__('Supports refund and void processing') : Shopp::__('No refund or void support') );
	}

	/**
	 * Renders the checkbox column for the current item
	 *
	 * @since 1.5
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	protected function checkbox( $set, $title ) {
		return '<div class="checkbox ' . ( $set ? ' checked' : '' ) . '" title="' . esc_html($title) . '"><span class="hidden">' . esc_html($title) . '</div>';
	}

}
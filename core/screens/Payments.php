<?php
/**
 * Payments.php
 *
 * Payments screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Payments screen controller
 *
 * @since 1.4
 **/
class ShoppScreenPayments extends ShoppSettingsScreenController {

	public function assets () {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('payments');
		shopp_localize_script( 'payments', '$ps', array(
			'confirm' => __('Are you sure you want to remove this payment system?','Shopp'),
		));
	}

	public function layout () {
		$this->table('ShoppPaymentsSettingsTable');
	}

	public function actions () {
		add_action('shopp_admin_settings_actions', array($this, 'delete') );
	}

	public function ops () {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	public function delete () {
		$delete = $this->request('delete');
		if ( false === $delete ) return;

		check_admin_referer('shopp_delete_gateway');

		$gateways = array_keys(Shopp::object()->Gateways->activated());

		if ( ! in_array($delete, $gateways) )
			return $this->notice(Shopp::__('The requested payment system could not be deleted because it does not exist.'), 'error');

		$position = array_search($delete, $gateways);
		array_splice($gateways, $position, 1);
		shopp_set_setting('active_gateways', join(',', $gateways));

		$this->notice(Shopp::__('Payment system removed.'));

		Shopp::redirect(add_query_arg(array('delete' => null, '_wpnonce' => null)));
	}

	public function updates () {

		$form = $this->form();
		if ( empty($form) ) return;

		do_action('shopp_save_payment_settings');
		$Gateways = Shopp::object()->Gateways;

		$gateways = array_keys($Gateways->activated());
		$gateway = key($form);

		// Handle Multi-instance payment systems
		$indexed = false;
		if ( preg_match('/\[(\d+)\]/', $gateway, $matched) ) {
			$indexed = '-' . $matched[1];
			$gateway = str_replace($matched[0], '', $gateway);
		}

		// Merge the existing gateway settings with the newly updated settings
		if ( isset($Gateways->active[ $gateway ]) ) {
			$Gateway = $Gateways->active[ $gateway ];
			// Cannot use array_merge() because it adds numeric index values instead of overwriting them
			$this->form[ $gateway ] = (array) $this->form[ $gateway ] + (array) $Gateway->settings;
		}

		// Add newly activated gateways
		if ( ! in_array($gateway . $indexed, $gateways) ) {
			$gateways[] =  $gateway . $indexed;
			shopp_set_setting('active_gateways', join(',', $gateways));
		}

		// Save the gateway settings
		shopp_set_formsettings();

		$this->notice(Shopp::__('Shopp payments settings saved.'));

		Shopp::redirect(add_query_arg());

	}

	public function screen () {
		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('payments.php');
	}

} // class ShoppScreenPayments

/**
 * Payments Table UI renderer
 *
 * @since 1.4
 **/
class ShoppPaymentsSettingsTable extends ShoppAdminTable {

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

	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;

		echo  '<select name="id" id="payment-option-menu">'
			. '	<option>' . Shopp::__('Add a payment system&hellip;') . '</option>'
			. '	' . Shopp::menuoptions($this->installed, false, true)
			. '</select>'
			. '<button type="submit" name="add-payment-option" id="add-payment-option" class="button-secondary hide-if-js" tabindex="9999">' . Shopp::__('Add Payment System') . '</button>';

	}

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

	public function no_items() {
		Shopp::_e('No payment methods, yet.');
	}

	protected function editing( $Item ) {
		return ( $Item->payid === $this->request('id') );
	}

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

		echo '<a class="row-title edit" href="' . esc_url($edit_link) . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($label) . '&quot;">' . esc_html($label) . '</a>';

		echo $this->row_actions( array(
			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
			'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>', 
		) );

	}

	public function column_processor( $Item ) {
		echo esc_html($Item->name);
	}

	public function column_payments( $Item ) {
		if ( empty($Item->settings['cards']) ) return;

		$cards = array();
		foreach ( (array) $Item->settings['cards'] as $symbol ) {
			$Paycard = ShoppLookup::paycard($symbol);
			if ( $Paycard ) $cards[] = $Paycard->name;
		}

		echo esc_html(join(', ', $cards));
	}

	public function column_ssl( $Item ) {
		$this->checkbox( $Item->secure, $Item->secure ? Shopp::__('SSL/TLS Required'): Shopp::__('No SSL/TLS Required') );
	}

	public function column_captures( $Item ) {
		$this->checkbox( $Item->captures, $Item->captures ? Shopp::__('Supports delayed payment capture') : Shopp::__('No delayed payment capture support') );
	}

	public function column_recurring( $Item ) {
		$this->checkbox( $Item->recurring, $Item->recurring ? Shopp::__('Supports recurring payments') : Shopp::__('No recurring payment support') );
	}

	public function column_refunds( $Item ) {
		$this->checkbox( $Item->refunds, $Item->refunds ? Shopp::__('Supports refund and void processing') : Shopp::__('No refund or void support') );
	}

	protected function checkbox( $set, $title ) {
		echo '<div class="checkbox ' . ( $set ? ' checked' : '' ) . '" title="' . esc_html($title) . '"><span class="hidden">' . esc_html($title) . '</div>';
	}

} // class ShoppPaymentsSettingsTable
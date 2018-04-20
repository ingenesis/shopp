<?php
/**
 * ScreenOrders.php
 *
 * Orders settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrdersManagement extends ShoppSettingsScreenController {

	/**
	 * Enqueue required script and style assets for the UI
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('labelset');
		shopp_localize_script('labelset', '$sl', array(
			'prompt' => Shopp::__('Are you sure you want to remove this order status label?'),
		));
	}

	/**
	 * Process and save form updates
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function updates() {
		$form = $this->form();
		if ( empty($form) ) return;

		check_admin_referer('shopp-setup-management');

		// Recount terms when this setting changes
		$inventory = $this->form('inventory');
		if ( $inventory && $inventory != shopp_setting('inventory') )
			$this->update_term_count();

		shopp_set_formsettings();

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$next = sDB::query("SELECT IF ((MAX(id)) > 0,(MAX(id)+1),1) AS id FROM $purchasetable LIMIT 1");

		$next_order_id = intval($this->form('next_order_id'));
		if ( $next_order_id >= $next->id && sDB::query("ALTER TABLE $purchasetable AUTO_INCREMENT=" . sDB::escape($next_order_id) ) )
			$next_setting = $next_order_id;
		shopp_set_setting('next_order_id', $next_setting);

		shopp_set_setting('order_shipfee', Shopp::floatval($this->form('order_shipfee')));

		$this->notice(Shopp::__('Order management settings saved.'), 'notice', 20);

	}

	/**
	 * Render the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {
		if ( ! current_user_can('shopp_settings_checkout') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$next = sDB::query("SELECT IF ((MAX(id)) > 0,(MAX(id)+1),1) AS id FROM $purchasetable LIMIT 1");
		$next_setting = shopp_setting('next_order_id');

		if ( $next->id > $next_setting )
			$next_setting = $next->id;

		$states = array(
			Shopp::__('Map the label to an order state:') =>
				array_merge(array('' => ''), Lookup::txnstatus_labels())
		);

		$statusLabels = shopp_setting('order_status');
		$statesLabels = shopp_setting('order_states');
		$reasonLabels = shopp_setting('cancel_reasons');

		if ( empty($reasonLabels) ) $reasonLabels = array(
			Shopp::__('Not as described or expected'),
			Shopp::__('Wrong size'),
			Shopp::__('Found better prices elsewhere'),
			Shopp::__('Product is missing parts'),
			Shopp::__('Product is defective or damaged'),
			Shopp::__('Took too long to deliver'),
			Shopp::__('Item out of stock'),
			Shopp::__('Customer request to cancel'),
			Shopp::__('Item discontinued'),
			Shopp::__('Other reason')
		);

		$promolimit = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '15', '20', '25');

		$lowstock = shopp_setting('lowstock_level');
		if ( empty($lowstock) )
			$lowstock = 0;

		include $this->ui('management.php');
	}

	/**
	 * Updates Shopp product category term counts
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	private function update_term_count () {
		$taxonomy = ProductCategory::$taxon;
		$terms = get_terms( $taxonomy, array('hide_empty' => 0,'fields' => 'ids') );
		if ( ! empty($terms) )
			wp_update_term_count_now( $terms, $taxonomy );

	}

}
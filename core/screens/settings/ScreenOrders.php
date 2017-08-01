<?php
/**
 * ScreenOrders.php
 *
 * Orders settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrders extends ShoppSettingsScreenController {

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
			'prompt' => __('Are you sure you want to remove this order status label?','Shopp'),
		));
	}

	/**
	 * Render the UI
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function screen() {
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_settings_checkout') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$next = sDB::query("SELECT IF ((MAX(id)) > 0,(MAX(id)+1),1) AS id FROM $purchasetable LIMIT 1");
		$next_setting = shopp_setting('next_order_id');

		if ($next->id > $next_setting) $next_setting = $next->id;

		$term_recount = false;

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-setup-management');

			$next_order_id = $_POST['settings']['next_order_id'] = intval($_POST['settings']['next_order_id']);

			if ($next_order_id >= $next->id) {
				if ( sDB::query("ALTER TABLE $purchasetable AUTO_INCREMENT=" . sDB::escape($next_order_id) ) )
					$next_setting = $next_order_id;
			}

			$_POST['settings']['order_shipfee'] = Shopp::floatval($_POST['settings']['order_shipfee']);

			// Recount terms when this setting changes
			if ( isset($_POST['settings']['inventory']) &&
				$_POST['settings']['inventory'] != shopp_setting('inventory')) {
				$term_recount = true;
			}

			shopp_set_formsettings();
			$this->notice(Shopp::__('Management settings saved.'), 'notice', 20);
		}

		if ($term_recount) {
			$taxonomy = ProductCategory::$taxon;
			$terms = get_terms( $taxonomy, array('hide_empty' => 0,'fields'=>'ids') );
			if ( ! empty($terms) )
				wp_update_term_count_now( $terms, $taxonomy );
		}


		$states = array(
			__('Map the label to an order state:','Shopp') => array_merge(array('' => ''),Lookup::txnstatus_labels())
		);
		$statusLabels = shopp_setting('order_status');
		$statesLabels = shopp_setting('order_states');
		$reasonLabels = shopp_setting('cancel_reasons');

		if (empty($reasonLabels)) $reasonLabels = array(
			__('Not as described or expected','Shopp'),
			__('Wrong size','Shopp'),
			__('Found better prices elsewhere','Shopp'),
			__('Product is missing parts','Shopp'),
			__('Product is defective or damaaged','Shopp'),
			__('Took too long to deliver','Shopp'),
			__('Item out of stock','Shopp'),
			__('Customer request to cancel','Shopp'),
			__('Item discontinued','Shopp'),
			__('Other reason','Shopp')
		);

		$promolimit = array('1','2','3','4','5','6','7','8','9','10','15','20','25');

		$lowstock = shopp_setting('lowstock_level');
		if (empty($lowstock)) $lowstock = 0;

		include $this->ui('management.php');
	}

}
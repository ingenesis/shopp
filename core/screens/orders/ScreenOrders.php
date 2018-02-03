<?php
/**
 * ScreenOrders.php
 *
 * Orders table screen controller
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrders extends ShoppScreenController {

	protected $ui = 'orders';

	/**
	 * Register the action handlers.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function actions() {
		return array(
			'action'
		);
	}

	/**
	 * Handle bulk actions.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function action () {
		if ( false === $this->request('action') ) return;

		$selected = (array) $this->request('selected');

		$action = Shopp::__('Updated');
		if ( 'delete' == $this->request('action') ) {
			$handler = array($this, 'delete');
			$action = Shopp::__('Deleted');
		} elseif ( is_numeric($this->request('action')) ) {
			$handler = array($this, 'status');
		}

		$processed = 0;
		foreach ( $selected as $selection ) {
			if ( call_user_func($handler, $selection) )
				$processed++;
		}

		if ( 1 == $processed )
			$this->notice(Shopp::__('%s Order <strong>#%d</strong>.', $action, reset($selected)));
		elseif ( $processed > 1 )
			$this->notice(Shopp::__('%s <strong>%d</strong> orders.', $action, $processed));


		shopp_redirect($this->url(array(
			'action' => false,
			'selected' => false,
		)));
	}

	/**
	 * Delete an order with a given ID.
	 *
	 * @since 1.4
	 *
	 * @param string $id The ShoppPurchase ID to delete.
	 * @return bool True if deleted successfully, false otherwise.
	 **/
	public function delete ( $id ) {

		$Purchase = new ShoppPurchase($id);
		if ( ! $Purchase->exists() ) return false;

		$Purchase->delete_purchased();
		$Purchase->delete();

		return true;

	}

	/**
	 * Update the status of an order.
	 *
	 * @since 1.4
	 *
	 * @param string $id The ShoppPurchase ID to update.
	 * @return bool True if deleted successfully, false otherwise.
	 **/
	public function status ( $id ) {

		$Purchase = new ShoppPurchase($id);
		if ( ! $Purchase->exists() ) return false;

		$status = (int) $this->request('action');
		$Purchase->status = $status;
		$Purchase->save();

		return true;

	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function assets () {
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('daterange');
		do_action('shopp_order_admin_scripts');
	}

	/**
	 * Setup the admin table
	 *
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function layout () {
		$this->table('ShoppScreenOrdersTable');
		add_screen_option( 'per_page', array(
			'label' => Shopp::__('Orders Per Page'),
			'default' => 20,
			'option' => 'edit_' . ShoppProduct::$posttype . '_per_page'
		));
		
	}

	/**
	 * Interface processor for the orders list interface
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function screen () {

		$Table = $this->table('ShoppScreenOrdersTable');
		$Table->prepare_items();

		include $this->ui('orders.php');
	}

	public static function navigation () {

		$labels = shopp_setting('order_status');

		if ( empty($labels) ) return false;

		$all = array('' => Shopp::__('All Orders'));

		$table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);

		$alltotal = sDB::query("SELECT count(*) AS total FROM $table", 'auto', 'col', 'total');
		$r = sDB::query("SELECT status,COUNT(status) AS total FROM $table GROUP BY status ORDER BY status ASC", 'array', 'index', 'status');

		$labels = (array) ( $all + $labels );

		echo '<ul class="subsubsub">';
		foreach ( $labels as $id => $label ) {
			$args = array('status' => $id, 'id' => null);
			$url = add_query_arg(array_merge($_GET, $args));

			$status = isset($_GET['status']) ? $_GET['status'] : '';
			if ( is_numeric($status) ) $status = intval($status);
			$classes = $status === $id ? ' class="current"' : '';

			$separator = '| ';
			if ( '' === $id ) {
				$separator = '';
				$total = $alltotal;
			}

			if ( isset($r[ $id ]) )
				$total = (int) $r[ $id ]->total;

			echo '	<li>' . $separator . '<a href="' . esc_url($url) . '"' . $classes . '>' . esc_html($label) . '</a>&nbsp;<span class="count">(' . esc_html($total) . ')</span></li>';

		}
		echo '</ul>';
	}

}
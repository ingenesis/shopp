<?php
/**
 * AdminOrders.php
 *
 * Flow controller for order management interfaces
 *
 * @copyright Ingenesis Limited, January 2010-2017
 * @package \Shopp\Screens\Orders
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Shopp order admin controller
 *
 * @since 1.5
 **/
class ShoppAdminOrders extends ShoppAdminController {

	protected $ui = 'orders';

	/**
	 * Route the screen requests to the screen controller
	 *
	 * @since 1.5
	 *
	 * @return string The screen controller class in charge of the request
	 **/
	protected function route () {
		if ( false !== strpos($this->request('page'), 'orders-new') )
			return 'ShoppScreenOrderEntry';
		elseif ( $this->request('id') )
			return 'ShoppScreenOrderManager';
		else return 'ShoppScreenOrders';
	}

	/**
	 * Retrieves the number of orders in each customized order status label
	 *
	 * @return array|bool The list of order counts by status, or false
	 **/
	public static function status_counts () {
		$table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$labels = shopp_setting('order_status');

		if ( empty($labels) ) return false;

		$statuses = array();

		$alltotal = sDB::query("SELECT count(*) AS total FROM $table", 'auto', 'col', 'total');
		$r = sDB::query("SELECT status,COUNT(status) AS total FROM $table GROUP BY status ORDER BY status ASC", 'array', 'index', 'status');
		$all = array('' => Shopp::__('All Orders'));

		$labels = (array) ( $all + $labels );

		foreach ( $labels as $id => $label ) {
			$status = new StdClass();
			$status->label = $label;
			$status->id = $id;
			$status->total = 0;
			if ( isset($r[ $id ]) ) $status->total = (int) $r[ $id ]->total;
			if ( '' === $id ) $status->total = $alltotal;
			$statuses[ $id ] = $status;
		}

		return $status;
	}

}
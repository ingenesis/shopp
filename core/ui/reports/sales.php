<?php
/**
 * sales.php
 *
 * Sales report
 *
 * @copyright Ingenesis Limited, June 2012
 * @package Shopp\Reports\Sales
 * @version 1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class SalesReport extends ShoppReportFramework implements ShoppReport {

	var $periods = true;

	function setup () {
		$this->setchart(array(
			'yaxis' => array('tickFormatter' => 'asMoney')
		));
		$this->chartseries( __('Total','Shopp'), array('column' => 'total') );
	}

	function query () {
		extract($this->options, EXTR_SKIP);

		$where = array();

		$where[] = "o.created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";
		$where[] = "o.txnstatus IN ('authed', 'captured', 'CHARGED')";

		$where = join(" AND ",$where);

		$id = $this->timecolumn('o.created');
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');

		$query = "SELECT CONCAT($id) AS id,
							UNIX_TIMESTAMP(o.created) AS period,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.subtotal) AS subtotal,
							SUM(o.tax) AS tax,
							SUM(o.freight) AS shipping,
							SUM(o.discount) AS discounts,
							SUM(o.total) AS total,
							AVG(o.total) AS orderavg,
							SUM( (SELECT SUM(p.quantity) FROM $purchased_table AS p WHERE o.id = p.purchase) ) AS items,
							(SELECT AVG(p.unitprice) FROM $purchased_table AS p WHERE o.id = p.purchase) AS itemavg
					FROM $orders_table AS o
					WHERE $where
					GROUP BY CONCAT($id)";

		return $query;

	}

	function columns () {
		return array(
			'period'=>__('Period','Shopp'),
			'orders'=>__('Orders','Shopp'),
			'items'=>__('Items','Shopp'),
			'subtotal'=>__('Subtotal','Shopp'),
			'tax'=>__('Tax','Shopp'),
			'shipping'=>__('Shipping','Shopp'),
			'discounts'=>__('Discounts','Shopp'),
			'total'=>__('Total','Shopp'),
			'orderavg'=>__('Average Order','Shopp'),
			'itemavg'=>__('Average Items','Shopp')
		);
	}

	function scores () {
		return array(
			__('Total','Shopp') => money( isset($this->totals->total) ? $this->totals->total : 0 ),
			__('Orders','Shopp') => intval( isset($this->totals->orders) ? $this->totals->orders : 0 ),
			__('Average Order','Shopp') => money( isset($this->totals->total) && isset($this->totals->orders) ? $this->totals->total/$this->totals->orders : 0)
		);
	}

	static function orders ($data) {
		return intval( isset($data->orders) ? $data->orders : 0);
	}

	static function items ($data) {
		return intval( isset($data->items) ? $data->items : 0);
	}

	static function subtotal ($data) {
		return money( isset($data->subtotal) ? $data->subtotal : 0 );
	}

	static function tax ($data) {
		return money( isset($data->tax) ? $data->tax : 0 );
	}

	static function shipping ($data) {
		return money( isset($data->shipping) ? $data->shipping : 0 );
	}

	static function discounts ($data) {
		return money( isset($data->discounts) ? $data->discounts : 0 );
	}

	static function total ($data) {
		return money( isset($data->total) ? $data->total : 0 );
	}

	static function orderavg ($data) {
		return money( isset($data->orderavg) ? $data->orderavg : 0 );
	}

	static function itemavg ($data) {
		return money( isset($data->itemavg) ? $data->itemavg : 0 );
	}

}

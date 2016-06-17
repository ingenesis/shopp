<?php

class ShippingReport extends ShoppReportFramework implements ShoppReport {

	var $periods = true;

	function setup () {
		$this->setchart(array(
			'yaxis' => array('tickFormatter' => 'asMoney')
		));

		$this->chartseries( Shopp::__('Shipping'), array('column' => 'shipping') );
	}

	function query () {
		extract($this->options, EXTR_SKIP);

		$where = array();
		$where[] = "o.freight > 0";
		$where[] = "o.created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";

		$where = join(" AND ", $where);
		$id = $this->timecolumn('o.created');
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$query = "SELECT CONCAT($id) AS id,
							UNIX_TIMESTAMP(o.created) as period,
							SUM( ( SELECT SUM(p.quantity) FROM $purchased_table AS p WHERE o.id = p.purchase AND p.type='Shipped' ) ) AS items,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.subtotal) AS subtotal,
							SUM(o.freight) AS shipping
					FROM $orders_table AS o
					WHERE $where
					GROUP BY CONCAT($id)";

		return $query;
	}

	function columns () {
	 	return array(
			'period'   => Shopp::__('Period'),
			'orders'   => Shopp::__('Orders'),
			'items'    => Shopp::__('Items'),
			'subtotal' => Shopp::__('Subtotal'),
			'shipping' => Shopp::__('Shipping')
		);
	}

	static function orders ( $data ) { return intval($data->orders); }

	static function items ( $data ) { return intval($data->items); }

	static function subtotal ( $data ) { return money($data->subtotal); }

	static function shipping ( $data ) { return money($data->shipping); }

}
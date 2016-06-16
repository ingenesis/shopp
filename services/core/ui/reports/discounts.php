<?php

class DiscountsReport extends ShoppReportFramework implements ShoppReport {

	var $periods = true;

	function setup () {
		$this->setchart(array(
			'yaxis' => array('tickFormatter' => 'asMoney')
		));

		$this->chartseries( Shopp::__('Discounts'), array('column' => 'discounts') );
	}

	function query () {
		extract($this->options, EXTR_SKIP);

		$where = array();
		$where[] = "o.created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";

		$where = join(" AND ", $where);
		$id = $this->timecolumn('o.created');
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$query = "SELECT CONCAT($id) AS id,
							UNIX_TIMESTAMP(o.created) as period,
							SUM( ( SELECT SUM(p.quantity) FROM $purchased_table AS p WHERE o.id = p.purchase ) ) AS items,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.subtotal) as subtotal,
							SUM(o.discount) as discounts
					FROM $orders_table AS o
					WHERE $where
					GROUP BY CONCAT($id)";

		return $query;
	}

	function columns() {
	 	return array(
			'period'    => Shopp::__('Period'),
			'orders'    => Shopp::__('Orders'),
			'items'     => Shopp::__('Items'),
			'subtotal'  => Shopp::__('Subtotal'),
			'discounts' => Shopp::__('Discounts')
		);
	}

	static function orders( $data ) {
		return intval( isset($data->orders) ? $data->orders : 0);
	}

	static function items( $data ) {
		return intval( isset($data->items) ? $data->items : 0);
	}

	static function subtotal( $data ) {
		return money( isset($data->subtotal) ? $data->subtotal : 0 );
	}

	static function discounts( $data ) {
		return money( isset($data->discounts) ? $data->discounts : 0 );
	}

}
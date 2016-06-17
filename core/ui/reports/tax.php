<?php

class TaxReport extends ShoppReportFramework implements ShoppReport {

	public $periods = true;

	public function setup () {
		$this->setchart(array(
			'yaxis' => array('tickFormatter' => 'asMoney')
		));

		$this->chartseries( __('Taxable','Shopp'), array('column' => 'taxable') );
		$this->chartseries( __('Total Tax','Shopp'), array('column' => 'tax') );
	}

	public function query () {
		extract($this->options, EXTR_SKIP);

		$where = array();

		$where[] = "o.created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";
		$where[] = "o.txnstatus IN ('authed', 'captured', 'CHARGED')";

		$where = join(" AND ",$where);
		$id = $this->timecolumn('o.created');
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');

		$query = "SELECT CONCAT($id) AS id,
							UNIX_TIMESTAMP(o.created) as period,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.subtotal) as subtotal,
							( SELECT SUM(IF(p.unittax > 0,p.total,0)) FROM $purchased_table AS p WHERE o.id = p.purchase ) AS taxable,
							( SELECT AVG(p.unittax/p.unitprice) FROM $purchased_table AS p WHERE o.id = p.purchase ) AS rate,
							SUM(o.tax) as tax
					FROM $orders_table AS o
					WHERE $where
					GROUP BY CONCAT($id)";

		return $query;
	}

	public function columns () {
		return array(
			'period'   => Shopp::__('Period'),
			'orders'   => Shopp::__('Orders'),
			'subtotal' => Shopp::__('Subtotal'),
			'taxable'  => Shopp::__('Taxable Amount'),
			'rate'     => Shopp::__('Tax Rate'),
			'tax'      => Shopp::__('Total Tax')
		);
	}

	public static function orders ( $data ) { return intval($data->orders); }

	public static function subtotal ( $data ) { return money($data->subtotal); }

	public static function taxable ( $data ) { return money($data->taxable); }

	public static function tax ( $data ) { return money($data->tax); }

	public static function rate ( $data ) { return percentage($data->rate * 100); }

}
<?php

class TaxReport extends ShoppReportFramework implements ShoppReport {

	var $periods = true;

	function setup () {
		$this->setchart(array(
			'yaxis' => array('tickFormatter' => 'asMoney')
		));

		$this->chartseries( __('Taxable','Shopp'), array('column' => 'taxable') );
		$this->chartseries( __('Total Tax','Shopp'), array('column' => 'tax') );
	}

	function query () {
		extract($this->options, EXTR_SKIP);

		$where = array();

		$where[] = "$starts < " . self::unixtime('o.created');
		$where[] = "$ends > " . self::unixtime('o.created');

		$where = join(" AND ",$where);
		$id = $this->timecolumn('o.created');
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$query = "SELECT CONCAT($id) AS id,
							UNIX_TIMESTAMP(o.created) as period,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.subtotal) as subtotal,
							SUM(IF(p.unittax > 0,p.total,0)) AS taxable,
							AVG(p.unittax/p.unitprice) AS rate,
							SUM(o.tax) as tax
					FROM $purchased_table AS p
					JOIN $orders_table AS o ON p.purchase=o.id
					WHERE $where
					GROUP BY CONCAT($id)";

		return $query;
	}

	function columns () {
		return array(
			'period'=>__('Period','Shopp'),
			'orders'=>__('Orders','Shopp'),
			'subtotal'=>__('Subtotal','Shopp'),
			'taxable'=>__('Taxable Amount','Shopp'),
			'rate'=>__('Tax Rate','Shopp'),
			'tax'=>__('Total Tax','Shopp')
		);
	}

	static function orders ($data) { return intval($data->orders); }

	static function subtotal ($data) { return money($data->subtotal); }

	static function taxable ($data) { return money($data->taxable); }

	static function tax ($data) { return money($data->tax); }

	static function rate ($data) { return percentage($data->rate*100); }

}
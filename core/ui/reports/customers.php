<?php

class CustomersReport extends ShoppReportFramework implements ShoppReport {

	function setup () {
		$this->setchart(array(
			'series' => array('bars' => array('show' => true,'lineWidth'=>0,'fill'=>true,'barWidth' => 0.75),'points'=>array('show'=>false),'lines'=>array('show'=>false)),
			'xaxis' => array('show' => false),
			'yaxis' => array('tickFormatter' => 'asMoney')
		));
	}

	function query () {
		extract($this->options, EXTR_SKIP);

		$where = array();

		$where[] = "$starts < " . self::unixtime('o.created');
		$where[] = "$ends > " . self::unixtime('o.created');

		$where = join(" AND ",$where);

		$orderd = 'desc';
		if ( in_array( $order, array('asc','desc') ) ) $orderd = strtolower($order);

		$ordercols = 'orders';
		switch ($orderby) {
			case 'orders': $ordercols = 'orders'; break;
			case 'sold': $ordercols = 'sold'; break;
			case 'grossed': $ordercols = 'grossed'; break;
		}
		$ordercols = "$ordercols $orderd";

		$id = 'c.id';
		$purchase_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$customer_table = ShoppDatabaseObject::tablename('customer');

		$query = "SELECT $id AS id,
							CONCAT(c.firstname,' ',c.lastname) AS customer,
							COUNT(DISTINCT p.id) AS sold,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.total) AS grossed
					FROM $customer_table as c
					JOIN $purchase_table AS o ON c.id=o.customer
					JOIN $purchased_table AS p ON p.purchase=o.id
					WHERE $where
					GROUP BY $id ORDER BY $ordercols";

		return $query;
	}

	function chartseries ($label,$options = array() ) {
		if ( ! $this->Chart ) $this->initchart();
		extract($options);
		$this->Chart->series($record->customer,array( 'color' => '#1C63A8','data'=>array( array($index,$record->grossed) ) ));
	}

	function filters () {
		ShoppReportFramework::rangefilter();
		ShoppReportFramework::filterbutton();
	}

	function columns () {
		return array(
			'customer'=>__('Customer','Shopp'),
			'orders'=>__('Orders','Shopp'),
			'sold'=>__('Items','Shopp'),
			'grossed'=>__('Grossed','Shopp')
		);
	}

	function sortcolumns () {
		return array(
			'orders'=>'orders',
			'sold'=>'sold',
			'grossed'=>'grossed'
		);
	}

	static function customer ($data) { return trim($data->customer); }

	static function orders ($data) { return intval($data->orders); }

	static function sold ($data) { return intval($data->sold); }

	static function grossed ($data) { return money($data->grossed); }

}
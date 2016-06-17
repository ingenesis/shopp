<?php

class CustomersReport extends ShoppReportFramework implements ShoppReport {

	function setup () {
		$this->setchart(array(
			'series' => array(
				'bars' => array(
					'show'      => true,
					'lineWidth' => 0,
					'fill'      => true,
					'barWidth'  => 0.75
				),
				'points' => array('show' => false),
				'lines'  => array('show' => false)
			),
			'xaxis' => array('show' => false),
			'yaxis' => array('tickFormatter' => 'asMoney')
		));
	}

	function query () {
		$this->options = array_merge(array( // Define default URL query parameters
			'orderby' => 'orders',
			'order' => 'desc'
		), $this->options);
		extract($this->options, EXTR_SKIP);

		$where = array();

		$where[] = "o.created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";

		$where = join(" AND ",$where);

		if ( ! in_array( $order, array('asc', 'desc') ) ) $order = 'desc';
		if ( ! in_array( $orderby, array('orders', 'sold', 'grossed') ) ) $orderby = 'orders';
		$ordercols = "$orderby $order";

		$id = 'c.id';
		$purchase_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$customer_table = ShoppDatabaseObject::tablename('customer');

		$query = "SELECT $id AS id,
							CONCAT(c.firstname,' ',c.lastname) AS customer,
							SUM( (SELECT SUM(p.quantity) FROM $purchased_table AS p WHERE o.id = p.purchase) ) AS sold,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.total) AS grossed
					FROM $purchase_table as o
					INNER JOIN $customer_table AS c ON c.id=o.customer
					WHERE $where
					GROUP BY $id ORDER BY $ordercols";

		return $query;
	}

	function chartseries ( $label, array $options = array() ) {
		if ( ! $this->Chart ) $this->initchart();
		extract($options);
		$this->Chart->series($record->customer, array( 'color' => '#1C63A8', 'data' => array( array($index, $record->grossed) ) ));
	}

	function filters () {
		ShoppReportFramework::rangefilter();
		ShoppReportFramework::filterbutton();
	}

	function columns () {
		return array(
			'customer' => __('Customer', 'Shopp'),
			'orders'   => __('Orders', 'Shopp'),
			'sold'     => __('Items', 'Shopp'),
			'grossed'  => __('Grossed', 'Shopp')
		);
	}

	function sortcolumns () {
		return array(
			'orders'  => 'orders',
			'sold'    => 'sold',
			'grossed' => 'grossed'
		);
	}

	static function customer ( $data ) { return trim($data->customer); }

	static function orders ( $data ) { return intval($data->orders); }

	static function sold ( $data ) { return intval($data->sold); }

	static function grossed ( $data ) { return money($data->grossed); }

}
<?php
class PaymentTypesReport extends ShoppReportFramework implements ShoppReport {

	function setup () {
		$this->setchart(array(
			'series' => array('bars' => array('show' => true, 'lineWidth' => 0, 'fill' => true, 'barWidth' => 0.75), 'points' => array('show' => false), 'lines' => array('show' => false)),
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
		$where[] = "o.txnstatus IN ('authed','captured')";

		$where = join(" AND ",$where);

		if ( ! in_array( $order, array('asc', 'desc') ) ) $order = 'desc';
		if ( ! in_array( $orderby, array('orders', 'sold', 'grossed') ) ) $orderby = 'orders';
		$ordercols = "$orderby $order";

		$id = "o.cardtype";

		$purchase_table = ShoppDatabaseObject::tablename('purchase');

		$query = "SELECT CONCAT($id) AS id,
							COUNT(DISTINCT o.id) AS orders,
							SUM(o.total) AS grossed
					FROM $purchase_table AS o
					WHERE $where
					GROUP BY CONCAT($id) ORDER BY $ordercols";

		return $query;

	}

	function chartseries ( $label, array $options = array() ) {
		if ( ! $this->Chart ) $this->initchart();
		extract($options);

		$this->Chart->series($record->id, array( 'color' => '#1C63A8', 'data'=> array( array($index, $record->grossed) ) ));
	}

	function filters () {
		ShoppReportFramework::rangefilter();
		ShoppReportFramework::filterbutton();
	}

	function columns () {
		return array(
			'paymenttype' => Shopp::__('Payment Type'),
			'orders'      => Shopp::__('Orders'),
			'grossed'     => Shopp::__('Grossed')
		);
	}

	function sortcolumns () {
		return array(
			'paymenttype' => 'paymenttype',
			'orders'      => 'orders',
			'sold'        => 'sold',
			'grossed'     => 'grossed'
		);
	}

	static function paymenttype ( $data ) { return trim($data->id); }

	static function orders ( $data ) { return intval($data->orders); }

	static function sold ( $data ) { return intval($data->sold); }

	static function grossed ( $data ) { return money($data->grossed); }

}
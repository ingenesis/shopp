<?php

class ProductsReport extends ShoppReportFramework implements ShoppReport {

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

		$id = "o.product,' ',o.price";
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$product_table = WPDatabaseObject::tablename(Product::$table);
		$summary_table = ShoppDatabaseObject::tablename(ProductSummary::$table);
		$price_table = ShoppDatabaseObject::tablename('price');
		$query = "SELECT CONCAT($id) AS id,
							CONCAT(p.post_title,' ',pr.label) AS product,
							COUNT(DISTINCT o.id) AS sold,
							COUNT(DISTINCT o.purchase) AS orders,
							SUM(o.total) AS grossed
					FROM $purchased_table AS o
					JOIN $summary_table AS s ON s.product=o.product
					JOIN $product_table AS p ON p.ID=o.product
					JOIN $price_table AS pr ON pr.id=o.price
					WHERE $where
					GROUP BY CONCAT($id) ORDER BY $ordercols";

		return $query;
	}

	function chartseries ($label,$options = array() ) {
		if ( ! $this->Chart ) $this->initchart();
		extract($options);
		$this->Chart->series($record->product,array( 'color' => '#1C63A8','data'=>array( array($index,$record->grossed) ) ));
	}

	function filters () {
		ShoppReportFramework::rangefilter();
		ShoppReportFramework::filterbutton();
	}

	function columns () {
		return array(
			'product'=>__('Product','Shopp'),
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

	static function product ($data) { return trim($data->product); }

	static function orders ($data) { return intval($data->orders); }

	static function sold ($data) { return intval($data->sold); }

	static function grossed ($data) { return money($data->grossed); }

}
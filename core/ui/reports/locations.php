<?php

class LocationsReport extends ShoppReportFramework implements ShoppReport {

	var $map = array();

	function setup () {

		shopp_enqueue_script('jvectormap');
		shopp_enqueue_script('worldmap');

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

		$id = "o.country";
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');
		$product_table = WPDatabaseObject::tablename(Product::$table);
		$summary_table = ShoppDatabaseObject::tablename(ProductSummary::$table);
		$price_table = ShoppDatabaseObject::tablename('price');
		$query = "SELECT CONCAT($id) AS id,
							o.country AS country,
							COUNT(DISTINCT o.id) AS orders,
							COUNT(DISTINCT p.id) AS items,
							SUM(o.total) AS grossed
					FROM $orders_table AS o
					JOIN $purchased_table AS p ON p.purchase=o.id
					WHERE $where
					GROUP BY CONCAT($id) ORDER BY $ordercols";

		return $query;
	}

	function chartseries ( $label, $options = array() ) {
		extract($options);
		$this->map[$record->country] = $record->grossed;
	}

	function table () { ?>
		<div id="map"></div>
		<script type="text/javascript">
		var d = <?php echo json_encode($this->map); ?>;
		</script>
<?php
		parent::table();
	}

	function filters () {
		ShoppReportFramework::rangefilter();
		ShoppReportFramework::filterbutton();
	}

	function columns () {
		return array(
			'country'=>__('Country','Shopp'),
			'orders'=>__('Orders','Shopp'),
			'items'=>__('Items','Shopp'),
			'grossed'=>__('Grossed','Shopp')
		);
	}

	function sortcolumns () {
		return array(
			'orders'=>__('Orders','Shopp'),
			'items'=>__('Items','Shopp'),
			'grossed'=>__('Grossed','Shopp')
		);
	}

	static function country ($data) { $countries = Lookup::countries(); return $countries[$data->country]['name']; }

	static function orders ($data) { return intval($data->orders); }

	static function items ($data) { return intval($data->items); }

	static function grossed ($data) { return money($data->grossed); }

}
<?php

class LocationsReport extends ShoppReportFramework implements ShoppReport {

	var $map = array();

	function setup () {

		shopp_enqueue_script('jvectormap');
		shopp_enqueue_script('worldmap');

	}

	function query () {
		$this->options = array_merge(array( // Define default URL query parameters
			'orderby' => 'orders',
			'order' => 'desc'
		), $this->options);
		extract($this->options, EXTR_SKIP);

		$where = array();

		$where[] = "o.created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";
		$where[] = "o.txnstatus IN ('authed', 'captured', 'CHARGED')";

		$where = join(" AND ",$where);

		if ( ! in_array( $order, array('asc', 'desc') ) ) $order = 'desc';
		if ( ! in_array( strtolower($orderby), array('orders', 'sold', 'grossed') ) ) $orderby = 'orders';
		$ordercols = "$orderby $order";

		$id = "o.country";
		$orders_table = ShoppDatabaseObject::tablename('purchase');
		$purchased_table = ShoppDatabaseObject::tablename('purchased');

		$query = "SELECT CONCAT($id) AS id,
							o.country AS country,
							COUNT(DISTINCT o.id) AS orders,
							SUM( (SELECT SUM(p.quantity) FROM $purchased_table AS p WHERE o.id = p.purchase) ) AS items,
							SUM(o.subtotal) AS grossed
					FROM $orders_table AS o
					WHERE $where
					GROUP BY CONCAT($id) ORDER BY $ordercols";

		return $query;
	}

	function chartseries ( $label, array $options = array() ) {
		extract($options);
		$this->map[$record->country] = (float)$record->grossed;
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

	static function country ($data) {
		$countries = Lookup::countries();
		if ( isset($countries[$data->country]) )
			return $countries[$data->country]['name'];
		return $data->country;
	}

	static function orders ($data) { return intval($data->orders); }

	static function items ($data) { return intval($data->items); }

	static function grossed ($data) { return money($data->grossed); }

}
<?php

class InventoryReport extends ShoppReportFramework implements ShoppReport {

	function setup () {
		$this->setchart(array(
			'series' => array('limit' => 41,'bars' => array('show' => true,'lineWidth'=>0,'fill'=>0.8,'barWidth' => 0.75),'points'=>array('show'=>false),'lines'=>array('show'=>false)),
			'xaxis' => array('show' => false)
		));
	}

	function query () {
		$this->options = array_merge(array( // Define default URL query parameters
			'orderby' => '(pr.stock/pr.stocked)',
			'order' => 'ASC'
		), $this->options);
		extract($this->options, EXTR_SKIP);

		$where = array();
		$where[] = "pr.inventory='on'";
		$where = join(" AND ", $where);

		if ( ! in_array( strtoupper($order), array('ASC', 'DESC') ) ) $order = 'DESC';
		if ( ! in_array( strtolower($orderby), array('inventory', 'sku') ) ) $orderby = '(pr.stock/pr.stocked)';
		$ordercols = "$orderby $order";

		$id = "pr.product,' ',pr.id";
		$product_table = WPDatabaseObject::tablename(ShoppProduct::$table);
		$summary_table = ShoppDatabaseObject::tablename(ProductSummary::$table);
		$price_table = ShoppDatabaseObject::tablename('price');
		$query = "SELECT CONCAT($id) AS id,
							CONCAT(p.post_title, CASE WHEN pr.label='Price & Delivery' THEN '' ELSE CONCAT(' ', pr.label) END) AS product,
							pr.stock AS inventory,
							pr.stocked AS stocked,
							(pr.stock/pr.stocked)*100 AS level,
							pr.sku AS sku
					FROM $price_table AS pr
					JOIN $product_table AS p ON p.ID=pr.product
					WHERE $where
					GROUP BY CONCAT($id) ORDER BY $ordercols";

		return $query;
	}

	function chartseries ( $label, array $options = array() ) {

		if ( ! $this->Chart ) $this->initchart();
		extract($options);
		if ( $record->stocked == 0 ) return;

		$threshold = shopp_setting('lowstock_level');

		$warning = ( $record->inventory / $record->stocked ) * 100 < $threshold;
		$this->Chart->series($record->product, array(
			'color' => $warning ? '#A90007' : '#CB4B16',
			'data' => array( array($index, $record->stocked) )
		));

		$backordered = ( 0 > $record->inventory );
		$this->Chart->series($record->product, array(
			'color' => $backordered ? '#dc322f' : '#1C63A8',
			'data' => array( array($index, $record->inventory) )
		));

	}

	function filters () { /** Override filters **/	}

	function columns () {
		return array(
			'product'   => Shopp::__('Product'),
			'sku'	    => Shopp::__('SKU'),
			'inventory' => Shopp::__('Inventory'),
			'level'     => Shopp::__('Stock Level'),
		);
	}

	function excludecolumns () {
			return array(
			'sku' => 'SKU',
		);
	}

	function sortcolumns () {
		return array(
			'inventory' => 'inventory',
			'sku' => 'sku',
		);
	}

	static function product ( $data ) { return trim($data->product); }

	static function inventory ( $data ) { return intval($data->inventory); }
	
	static function sku ( $data ) { return trim($data->sku); }

	static function level ( $data ) { return round(floatval($data->level), 1) . '%'; }

	static function sold ( $data ) { return intval($data->sold); }

	static function grossed ( $data ) { return money($data->grossed); }

}

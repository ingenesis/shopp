<?php
	$targets = array(
		'Catalog' => __('catalog product','Shopp'),
		'Cart' => __('shopping cart','Shopp'),
		'Cart Item' => __('cart item','Shopp'),

	);

	$target = '<select name="target" id="promotion-target" class="small">';
	$target .= Shopp::menuoptions($targets,$Promotion->target,true);
	$target .= '</select>';

	if (empty($Promotion->search)) $Promotion->search = "all";

	$logic = '<select name="search" class="small">';
	$logic .= Shopp::menuoptions(array('any' => Shopp::__('any'),'all' => strtolower(Shopp::__('All'))), $Promotion->search, true);
	$logic .= '</select>';

?>
<p><strong><?php printf(__('Apply discount to %s','Shopp'),$target,$logic); ?> <strong id="target-property"></strong></strong></p>
<table class="form-table" id="cartitem"></table>

<p><strong><?php printf(__('When %s of these conditions match the','Shopp'),$logic); ?> <strong id="rule-target">:</strong></strong></p>

<table class="form-table" id="rules"></table>
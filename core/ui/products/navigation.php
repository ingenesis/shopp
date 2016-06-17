<?php
	$subs = apply_filters('shopp_products_subsubsub',$subs);
	if (empty($subs)) return;
?>
<ul class="subsubsub"><?php
	$links = array();
	foreach($subs as $name => $sub) {
		extract($sub);
		if ('0' == $sub['total']) continue;
		$suburl = remove_query_arg(array('apply','selected','pagenum','view'),$url);
		$filter = 'all' != $name ? array('view'=>$name) : array('view'=>null);
		$link = esc_url(add_query_arg($filter,$suburl));
		$class = ($this->view == $name?' class="current"':false);
		$links[] = sprintf('<li><a href="%s"%s>%s</a> <span class="count">(%d)</span>',$link,$class,$sub['label'],$sub['total']);
	}
	echo join(' | </li>',$links);
?></ul>
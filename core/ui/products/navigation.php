<?php
	$views = apply_filters('shopp_products_subsubsub', $views);
	if ( empty($views) ) return;
?>
<ul class="subsubsub"><?php
	$links = array();
	foreach( $views as $name => $view ) {
		extract($view);
        
		if ( '0' == $total )
			continue;

        $viewurl = remove_query_arg(array('apply', 'action', 'selected', 'paged', 'view'), $url);
		$filter = 'all' != $name ? array('view' => $name) : array('view' => null);
		$link = esc_url( add_query_arg($filter, $viewurl) );
        
		$class = $this->view == $name ? ' class="current"' : false;

		$links[] = sprintf('<li><a href="%s"%s>%s</a> <span class="count">(%d)</span>', $link, $class, $label, $total);
	}
	echo join(' | </li>', $links);
?></ul>
<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php Shopp::_e('Orders'); ?></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" id="orders" method="get">
	<?php include("navigation.php"); ?>
	<div>
		<input type="hidden" name="page" value="<?php echo $page; ?>" />
	</div>
	<div class="clear"></div>

	<p id="post-search" class="search-box">
		<input type="text" id="orders-search-input" class="search-input" name="s" value="<?php echo esc_attr($s); ?>" />
		<input type="submit" value="<?php Shopp::_e('Search Orders'); ?>" class="button" />
	</p>

	<?php if (current_user_can('shopp_financials')): ?>
	<ul class="summary">
		<li><strong><?php echo $ordercount->total; ?></strong> <span><?php Shopp::_e('Orders'); ?></span></li>
		<li><strong><?php echo Shopp::money($ordercount->sales); ?></strong> <span><?php Shopp::_e('Total Sales'); ?></span></li>
		<li><strong><?php echo Shopp::money($ordercount->avgsale); ?></strong> <span><?php Shopp::_e('Average Sale'); ?></span></li>
	</ul>
	<?php endif; ?>

	<div class="tablenav">
		<?php do_action('shopp_manage_orders_before_actions'); ?>

		<div class="alignleft actions">	
		<?php if (current_user_can('shopp_delete_orders')): ?><button type="submit" id="delete-button" name="deleting" value="order" class="button-secondary"><?php _e('Delete'); ?></button><?php endif; ?>
		</div>
		<div class="alignleft actions">
			<select name="newstatus">
				<?php echo Shopp::menuoptions($statusLabels,false,true); ?>
			</select>
			<button type="submit" id="update-button" name="update" value="order" class="button-secondary"><?php Shopp::_e('Update'); ?></button>
		</div>

		<div class="alignleft actions filtering">
				<select name="range" id="range">
					<?php echo Shopp::menuoptions($ranges,$range,true); ?>
				</select><div id="dates" class="hide-if-js"><div id="start-position" class="calendar-wrap"><input type="text" id="start" name="start" value="<?php echo $startdate; ?>" size="10" class="search-input selectall" /></div>
					<small>to</small>
					<div id="end-position" class="calendar-wrap"><input type="text" id="end" name="end" value="<?php echo $enddate; ?>" size="10" class="search-input selectall" /></div>
				</div>
				<button type="submit" id="filter-button" name="filter" value="order" class="button-secondary"><?php Shopp::_e('Filter'); ?></button>
		</div>

		<?php do_action('shopp_manage_orders_after_actions'); ?>

			<?php $ListTable->page_navigation('top'); ?>

		<div class="clear"></div>
	</div>
	<div class="clear"></div>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php print_column_headers(ShoppAdmin()->screen()); ?></tr>
		</thead>
		<tfoot>
		<tr><?php print_column_headers(ShoppAdmin()->screen(),false); ?></tr>
		</tfoot>
	<?php if (count($Orders) > 0): ?>
		<tbody id="orders-table" class="list orders">
		<?php
			$columns = get_column_headers(ShoppAdmin()->screen());
			$hidden = get_hidden_columns(ShoppAdmin()->screen());

			$url = add_query_arg('page','shopp-orders', admin_url('admin.php') );

			$even = false; foreach ($Orders as $Order):

			$classes = array();

			$viewurl = add_query_arg('id',$Order->id,$url);
			$customer = '' == trim($Order->firstname.$Order->lastname) ? "(" . Shopp::__('no contact name') . ")" : ucfirst("{$Order->firstname} {$Order->lastname}");
			$customerurl = add_query_arg( array( 'page' => 'shopp-customers', 'id' => $Order->customer ), $url );

			$txnstatus = isset($txnstatus_labels[$Order->txnstatus]) ? $txnstatus_labels[$Order->txnstatus] : $Order->txnstatus;
			$classes[] = strtolower(preg_replace('/[^\w]/', '_', $Order->txnstatus));

			$Gateway = $Gateways->get($Order->gateway);
			if ( $Gateway ) $gateway = $Gateway->name;

			$addrfields = array('city', 'state', 'country');
			$format = '%3$s, %2$s &mdash; %1$s';
			if (empty($Order->shipaddress))
				$location = sprintf($format,$Order->country,$Order->state,$Order->city);
			else $location = sprintf($format,$Order->shipcountry,$Order->shipstate,$Order->shipcity);

			$location = ltrim($location,' ,');
			if (0 === strpos($location,'&mdash;'))
				$location = str_replace('&mdash; ','',$location);
			$location = str_replace(',  &mdash;',' &mdash;',$location);

			if (!$even) $classes[] = "alternate";
			do_action_ref_array('shopp_order_row_css',array(&$classes,&$Order));
			$even = !$even;
		?>
		<tr class="<?php echo join(' ', $classes); ?>">
		<?php
			foreach ( $columns as $column => $column_title ) {
				$classes = array($column,"column-$column");
				if ( in_array($column, $hidden) ) $classes[] = 'hidden';

				$wrap_open = $column == "cb" ? "<th scope='row' class='check-column'>" : '<td class="' . esc_attr(join(' ', $classes)) .'">';
				echo apply_filters('shopp_manage_orders_column_wrap_close', $wrap_open, $column, $Order );

				do_action("shopp_manage_orders_column_{$column}_before", $Order);

				switch ( $column ) {
					case 'cb':
					?>
						<input type='checkbox' name='selected[]' value='<?php echo esc_attr($Order->id); ?>' />
					<?php
					break;

					case 'order':
					?>
						<a class='row-title' href='<?php echo esc_url($viewurl); ?>' title='<?php Shopp::_e('View Order #%d', $Order->id); ?>'><?php Shopp::_e('Order #%d', $Order->id); ?></a>
					<?php
					break;

					case 'name':
					?>
						<a href="<?php echo esc_url($customerurl); ?>"><?php echo esc_html($customer); ?></a><?php echo !empty($Order->company)?"<br />".esc_html($Order->company):""; ?>
					<?php
					break;

					case 'destination':
					?>
						<?php echo esc_html($location); ?>
					<?php
					break;

					case 'txn':
					?>
						<?php echo $Order->txnid; ?><br /><?php echo esc_html($gateway); ?>
					<?php
					break;

					case 'date':
					?>
						<?php echo date("Y/m/d", mktimestamp($Order->created)); ?><br />
						<strong><?php echo $statusLabels[$Order->status]; ?></strong>
					<?php
					break;

					case 'total':
					?>
						<?php echo money($Order->total); ?><br /><span class="status"><?php echo $txnstatus; ?></span>
					<?php
					break;

					default:
					?>
						<?php do_action( 'shopp_manage_orders_custom_column', $column, $Order ); ?>
						<?php do_action( 'shopp_manage_orders_' . sanitize_key($column) . '_column', $column, $Order ); ?>
					<?php
					break;

				} // end switch ( $column )

				do_action("shopp_manage_orders_column_after", $column, $Order);
				do_action("shopp_manage_orders_column_{$column}_after", $Order);

				$wrap_close = $column == "cb" ? "</th>" : "</td>";
				echo apply_filters('shopp_manage_orders_column_wrap_close', $wrap_close, $column, $Order );

			} // end foreach ( $columns…)
		?>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="7"><?php
		Shopp::_e('No %s orders yet.', (
			isset($_GET['status'],$statusLabels[$_GET['status']]) ? strtolower($statusLabels[$_GET['status']]) : ''
		)); ?></td></tr></tbody>
	<?php endif; ?>
	</table>

	</form>

	<div class="tablenav">
		<?php if (current_user_can('shopp_financials') && current_user_can('shopp_export_orders')): ?>
		<div class="alignleft actions">
			<form action="<?php echo esc_url( add_query_arg(urlencode_deep(array_merge(stripslashes_deep($_GET),array('src'=>'export_purchases'))),admin_url('admin.php')) ); ?>" id="log" method="post">
			<button type="button" id="export-settings-button" name="export-settings" class="button-secondary"><?php Shopp::_e('Export Options'); ?></button>
			<div id="export-settings" class="hidden">
			<div id="export-columns" class="multiple-select">
				<ul>
					<li<?php $even = true; if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="selectall_columns" id="selectall_columns" /><label for="selectall_columns"><strong><?php Shopp::_e('Select All'); ?></strong></label></li>
					<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="hidden" name="settings[purchaselog_headers]" value="off" /><input type="checkbox" name="settings[purchaselog_headers]" id="purchaselog_headers" value="on" /><label for="purchaselog_headers"><strong><?php Shopp::_e('Include column headings'); ?></strong></label></li>

					<?php $even = true; foreach ($exportcolumns as $name => $label): ?>
						<?php if ( $name == 'cb' ) continue; ?>
						<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="settings[purchaselog_columns][]" value="<?php echo $name; ?>" id="column-<?php echo $name; ?>" <?php echo in_array($name,$selected)?' checked="checked"':''; ?> /><label for="column-<?php echo $name; ?>" ><?php echo $label; ?></label></li>
					<?php endforeach; ?>

				</ul>
			</div>
			<?php PurchasesIIFExport::settings(); ?>
			<br />
			<select name="settings[purchaselog_format]" id="purchaselog-format">
				<?php echo menuoptions($exports,$formatPref,true); ?>
			</select>
			</div>
			<button type="submit" id="download-button" name="download" value="export" class="button-secondary"<?php if (count($Orders) < 1) echo ' disabled="disabled"'; ?>><?php Shopp::_e('Download'); ?></button>
			<div class="clear"></div>
			</form>
		</div>
		<?php endif; ?>

		<?php $ListTable->pagination('bottom'); ?>

		<div class="clear"></div>
	</div>
</div>

<script type="text/javascript">
var lastexport = new Date(<?php echo date("Y,(n-1),j", shopp_setting('purchaselog_lastexport')); ?>);

jQuery(document).ready( function($) {
	new DateRange('#range', '#start', '#end', '#dates');

	columns.init(pagenow);

	$('#selectall').change( function() {
		$('#orders-table th input').each( function () {
			if (this.checked) this.checked = false;
			else this.checked = true;
		});
	});

	$('#delete-button').click(function() {
		if (confirm("<?php echo addslashes(Shopp::__('Are you sure you want to delete the selected orders?')); ?>")) return true;
		else return false;
	});

	$('#update-button').click(function() {
		if (confirm("<?php echo addslashes(Shopp::__('Are you sure you want to update the status of the selected orders?')); ?>")) return true;
		else return false;
	});

	$('#export-settings-button').click(function () { $('#export-settings-button').hide(); $('#export-settings').removeClass('hidden'); });
	$('#selectall_columns').change(function () {
		if ($(this).attr('checked')) $('#export-columns input').not(this).attr('checked',true);
		else $('#export-columns input').not(this).attr('checked',false);
	});
	$('input.current-page').unbind('mouseup.select').bind('mouseup.select',function () { this.select(); });

});

</script>

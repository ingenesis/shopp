<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php _e('Customers','Shopp'); ?> <a href="<?php echo esc_url( add_query_arg('id','new', $action) ); ?>" class="add-new-h2"><?php _e('Add New','Shopp'); ?></a></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<form action="<?php echo esc_url($action); ?>" id="orders-list" method="get">
	<div>
		<input type="hidden" name="page" value="<?php echo $page; ?>" />
		<input type="hidden" name="status" value="<?php echo $status; ?>" />
	</div>

	<br class="clear" />
	<p id="post-search" class="search-box">
		<input type="text" id="customers-search-input" class="search-input" name="s" value="<?php echo esc_attr($s); ?>" />
		<input type="submit" value="<?php _e('Search','Shopp'); ?>" class="button" />
	</p>

	<div class="tablenav">
		<div class="alignleft actions inline">
		<?php if(current_user_can('shopp_delete_customers')): ?><button type="submit" id="delete-button" name="deleting" value="customer" class="button-secondary"><?php _e('Delete','Shopp'); ?></button><?php endif; ?>
			<div class="filtering">
				<select name="range" id="range">
					<?php echo Shopp::menuoptions($ranges,$range,true); ?>
				</select>
				<div id="dates">
					<div id="start-position" class="calendar-wrap"><input type="text" id="start" name="start" value="<?php echo $startdate; ?>" size="10" class="search-input selectall" /></div>
					<small><?php _e('to','Shopp'); ?></small>
					<div id="end-position" class="calendar-wrap"><input type="text" id="end" name="end" value="<?php echo $enddate; ?>" size="10" class="search-input selectall" /></div>
				</div>
				<button type="submit" id="filter-button" name="filter" value="customers" class="button-secondary"><?php _e('Filter','Shopp'); ?></button>
			</div>
			</div>

			<?php $ListTable->pagination('top'); ?>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php print_column_headers(ShoppAdmin()->screen()); ?></tr>
		</thead>
		<tfoot>
		<tr><?php print_column_headers(ShoppAdmin()->screen(), false); ?></tr>
		</tfoot>
	<?php if (count($Customers) > 0): ?>
		<tbody id="customers-table" class="list orders">
		<?php
			$hidden = get_hidden_columns(ShoppAdmin()->screen());

			$even = false;
			foreach ($Customers as $Customer):

			$CustomerName = (empty($Customer->firstname) && empty($Customer->lastname))?'('.__('no contact name','Shopp').')':"{$Customer->firstname} {$Customer->lastname}";
			?>
		<tr<?php if (!$even) echo " class='alternate'"; $even = !$even; ?>>
			<th scope='row' class='check-column'><input type='checkbox' name='selected[]' value='<?php echo $Customer->id; ?>' /></th>
			<td class="customer-name column-customer-name"><a class='row-title' href='<?php echo esc_url( add_query_arg(array('page'=>'shopp-customers','id'=>$Customer->id),admin_url('admin.php'))); ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($CustomerName); ?>&quot;'><?php echo esc_html($CustomerName); ?></a><?php echo !empty($Customer->company)?"<br />".esc_html($Customer->company):""; ?></td>
			<td class="customer-login column-customer-login<?php echo in_array('customer-login',$hidden)?' hidden':''; ?>"><?php echo esc_html($Customer->user_login); ?></td>
			<td class="email column-email<?php echo in_array('email',$hidden)?' hidden':''; ?>"><a href="mailto:<?php echo esc_attr($Customer->email); ?>"><?php echo esc_html($Customer->email); ?></a></td>

			<td class="customer-location column-customer-location<?php echo in_array('customer-location',$hidden)?' hidden':''; ?>"><?php
				$location = '';
				$location = $Customer->city;
				if (!empty($location) && !empty($Customer->state)) $location .= ', ';
				$location .= $Customer->state;
				if (!empty($location) && !empty($Customer->country))
					$location .= ' &mdash; ';
				$location .= $Customer->country;
				echo esc_html($location);
				 ?></td>
			<td class="customer-orders column-customer-orders<?php echo in_array('customer-orders',$hidden)?' hidden':''; ?>"><a href="<?php echo esc_url( add_query_arg(array('page'=>'shopp-orders','customer'=>$Customer->id),admin_url('admin.php'))); ?>"><?php echo $Customer->orders; ?> &mdash; <?php echo money($Customer->total); ?></a></td>
			<td class="customer-joined column-customer-joined<?php echo in_array('customer-joined',$hidden)?' hidden':''; ?>"><?php echo date("Y/m/d",mktimestamp($Customer->created)); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="7"><?php _e('No customers yet.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>

	</form>
	<div class="tablenav">
		<?php if(current_user_can('shopp_export_customers')): ?>
		<div class="alignleft actions">
			<form action="<?php echo esc_url(add_query_arg(array_merge($_GET,array('src'=>'export_customers')),admin_url("admin.php"))); ?>" id="log" method="post">
			<button type="button" id="export-settings-button" name="export-settings" class="button-secondary"><?php _e('Export Options','Shopp'); ?></button>
			<div id="export-settings" class="hidden">
			<div id="export-columns" class="multiple-select">
				<ul>
					<li<?php $even = true; if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="selectall_columns" id="selectall_columns" /><label for="selectall_columns"><strong><?php _e('Select All','Shopp'); ?></strong></label></li>
					<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="hidden" name="settings[customerexport_headers]" value="off" /><input type="checkbox" name="settings[customerexport_headers]" id="purchaselog_headers" value="on" /><label for="purchaselog_headers"><strong><?php _e('Include column headings','Shopp'); ?></strong></label></li>

					<?php $even = true; foreach ($columns as $name => $label): ?>
						<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="settings[customerexport_columns][]" value="<?php echo $name; ?>" id="column-<?php echo $name; ?>" <?php echo in_array($name,$selected)?' checked="checked"':''; ?> /><label for="column-<?php echo $name; ?>" ><?php echo $label; ?></label></li>
					<?php endforeach; ?>

				</ul>
			</div><br />
			<select name="settings[customerexport_format]">
				<?php echo menuoptions($exports,$formatPref,true); ?>
			</select></div>
			<button type="submit" id="download-button" name="download" value="export" class="button-secondary"><?php _e('Download','Shopp'); ?></button>
			</form>
		</div>
		<?php endif; ?>

		<?php $ListTable->pagination('bottom'); ?>

		<div class="clear"></div>
	</div>
</div>

<div id="start-calendar" class="calendar"></div>
<div id="end-calendar" class="calendar"></div>

<script type="text/javascript">
var lastexport = new Date(<?php echo date("Y,(n-1),j",shopp_setting('customerexport_lastexport')); ?>);

jQuery(document).ready( function($) {

	new DateRange('#range', '#start', '#end', '#dates');

	columns.init(pagenow);

	$('#selectall').change( function() {
		$('#customers-table th input').each( function () {
			if (this.checked) this.checked = false;
			else this.checked = true;
		});
	});

	$('#delete-button').click(function() {
		if (confirm("<?php echo addslashes(__('Are you sure you want to delete the selected customers?','Shopp')); ?>")) return true;
		else return false;
	});

	$('#export-settings-button').click(function () { $('#export-settings-button').hide(); $('#export-settings').removeClass('hidden'); });
	$('#selectall_columns').change(function () {
		if ($(this).attr('checked')) $('#export-columns input').not(this).attr('checked',true);
		else $('#export-columns input').not(this).attr('checked',false);
	});
});

</script>
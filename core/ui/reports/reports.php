<div class="wrap shopp">
	<div class="icon32"></div>
	<h2><?php echo $report_title; ?></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" id="report" method="get">
	<?php include("navigation.php"); ?>
	<div>
		<input type="hidden" name="page" value="<?php echo $page; ?>" />
		<input type="hidden" name="report" value="<?php echo $report; ?>" />
		<input type="hidden" name="scale" value="<?php echo $scale; ?>" />
	</div>
	<div class="clear"></div>

	<div class="tablenav">
		<div class="alignleft actions inline">
			<div class="filtering">
				<?php do_action('shopp_report_filter_controls'); ?>
			</div>
		</div>

		<?php $ListTable->pagination( 'top' ); ?>

		<div class="clear"></div>
	</div>
	<div class="clear"></div>

	<?php $Report->chart(); ?>

	<?php $Report->scoreboard(); ?>

	<?php $Report->table(); ?>

	</form>

	<div class="tablenav">
		<?php if (current_user_can('shopp_financials')): ?>
		<div class="alignleft actions">
			<form action="<?php echo esc_url( add_query_arg(urlencode_deep(array_merge(stripslashes_deep($_GET),array('src'=>'export_reports'))),admin_url('admin.php')) ); ?>" id="log" method="post">
			<?php
			$columns = $Report->columns();
			$settings = shopp_setting("{$report}_report_export");

			$selected = (array)$settings['columns'];
			?>
			<button type="button" id="export-settings-button" name="export-settings" class="button-secondary"><?php _e('Export Options','Shopp'); ?></button>
			<div id="export-settings" class="hidden">
			<div id="export-columns" class="multiple-select">
				<ul>
					<li<?php $even = true; if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="selectall_columns" id="selectall_columns" /><label for="selectall_columns"><strong><?php _e('Select All','Shopp'); ?></strong></label></li>
					<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="hidden" name="settings[<?php echo $report; ?>_report_export][headers]" value="off" /><input type="checkbox" name="settings[<?php echo $report; ?>_report_export][headers]" id="export_headers" value="on" <?php echo Shopp::str_true($settings['headers'])?' checked="checked"':''; ?> /><label for="export_headers"><strong><?php _e('Include column headings','Shopp'); ?></strong></label></li>
					<?php
					$even = true;
					foreach ($columns as $name => $label): ?>
						<li<?php if ($even) echo ' class="odd"'; $even = !$even; ?>><input type="checkbox" name="settings[<?php echo $report; ?>_report_export][columns][]" value="<?php echo $name; ?>" id="column-<?php echo $name; ?>" <?php echo in_array($name,$selected)?' checked="checked"':''; ?> /><label for="column-<?php echo $name; ?>" ><?php echo $label; ?></label></li>
					<?php endforeach; ?>

				</ul>
			</div>
			<br />
			<select name="settings[report_export_format]" id="report-format">
				<?php echo menuoptions($exports,shopp_setting('report_export_format'),true); ?>
			</select>
			</div>
			<button type="submit" id="download-button" name="download" value="export" class="button-secondary"<?php if ($Report->total < 1) echo ' disabled="disabled"'; ?>><?php _e('Download','Shopp'); ?></button>
			<div class="clear"></div>
			</form>
		</div>
		<?php endif; ?>


		<?php $ListTable->pagination('bottom'); ?>

		<div class="clear"></div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready( function() {
	var pagenow = 'toplevel_page_shopp-reports';
	columns.init(pagenow);
});

</script>
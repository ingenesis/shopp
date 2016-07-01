<div class="wrap shopp orders">

	<div class="icon32"></div>
	<h2><?php Shopp::_e('Orders'); ?> <a href="<?php echo esc_url($this->url(array('id' => 'new'))); ?>" class="add-new-h2"><?php Shopp::_e('Add New'); ?></a>
	<?php if ( current_user_can('shopp_financials') ): ?>
		<span class="summary"><strong><?php echo Shopp::money($Table->ordercount->sales); ?></strong> <span><?php Shopp::_e('Total Sales'); ?></span>&nbsp;&nbsp;&nbsp;
		<strong><?php echo Shopp::money($Table->ordercount->avgsale); ?></strong> <span><?php Shopp::_e('Average Sale'); ?></span></span>
	<?php endif; ?></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<form action="<?php echo esc_url($this->url()); ?>" id="orders" method="get">

	<input type="hidden" name="page" value="<?php echo $this->request('page'); ?>" />
<?php if ( $this->request('status') ): ?>
	<input type="hidden" name="status" value="<?php echo $this->request('status'); ?>" />
	<?php endif; ?>

	<?php ShoppScreenOrders::navigation(); ?>

	<p id="post-search" class="search-box">
		<input type="text" id="orders-search-input" class="search-input" name="s" value="<?php echo esc_attr($_GET['s']); ?>" />
		<input type="submit" value="<?php Shopp::_e('Search Orders'); ?>" class="button" />
	</p>

	<?php $Table->display(); ?>


	</div>

<script type="text/javascript">
var lastexport = new Date(<?php echo date("Y,(n-1),j", shopp_setting('purchaselog_lastexport')); ?>);

jQuery(document).ready( function($) {
	new DateRange('#range','#start','#end','#dates');

	columns.init(pagenow);

	$('#selectall').change( function() {
		$('#orders-table th input').each( function () {
			if (this.checked) this.checked = false;
			else this.checked = true;
		});
	});

	$('#delete-button').click(function() {
		if (confirm("<?php echo addslashes(__('Are you sure you want to delete the selected orders?','Shopp')); ?>")) return true;
		else return false;
	});

	$('#update-button').click(function() {
		if (confirm("<?php echo addslashes(__('Are you sure you want to update the status of the selected orders?','Shopp')); ?>")) return true;
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

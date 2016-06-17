<div class="wrap shopp">
	<div class="icon32"></div>
	<h2><?php _e('Memberships','Shopp'); ?> <a href="<?php echo esc_url( add_query_arg(array('page'=>$this->Admin->pagename('memberships'),'id'=>'new'),admin_url('admin.php'))); ?>" class="button add-new"><?php _e('Add New','Shopp'); ?></a></h2>

	<form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" id="memberships" method="get">
	<div>
		<input type="hidden" name="page" value="<?php echo $page; ?>" />
		<input type="hidden" name="status" value="<?php echo $status; ?>" />
	</div>

	<br class="clear" />
	<p id="post-search" class="search-box">
		<input type="text" id="memberships-search-input" class="search-input" name="s" value="<?php echo esc_attr($s); ?>" />
		<input type="submit" value="<?php _e('Search','Shopp'); ?>" class="button" />
	</p>

	<div class="tablenav">
		<div class="alignleft actions inline">
		<?php if(current_user_can('shopp_delete_customers')): ?><button type="submit" id="delete-button" name="deleting" value="customer" class="button-secondary"><?php _e('Delete','Shopp'); ?></button><?php endif; ?>
			</div>
			<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php print_column_headers('shopp_page_shopp-memberships'); ?></tr>
		</thead>
		<tfoot>
		<tr><?php print_column_headers('shopp_page_shopp-memberships',false); ?></tr>
		</tfoot>
	<?php if (sizeof($MemberPlans) > 0): ?>
		<tbody id="customers-table" class="list orders">
		<?php
			$hidden = get_hidden_columns('shopp_page_shopp-memberships');

			$even = false;
			foreach ($MemberPlans as $MemberPlan):
				$MemberPlanName = empty($MemberPlan->name)?'('.__('no membership name','Shopp').')':$MemberPlan->name;

				$editurl =  add_query_arg(array('page'=>'shopp-memberships','id'=>$MemberPlan->id),admin_url('admin.php'));
				$deleteurl = esc_attr(add_query_arg(
						array_merge($_GET,array('page'=>'shopp-memberships','delete[]'=>$MemberPlan->id,'deleting'=>'membership')),
						admin_url('admin.php')
						));

			?>
		<tr<?php if (!$even) echo " class='alternate'"; $even = !$even; ?>>
			<th scope='row' class='check-column'><input type='checkbox' name='delete[]' value='<?php echo $MemberPlan->id; ?>' /></th>
			<td class="name column-name"><a class='row-title' href='<?php echo $editurl; ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($MemberPlanName); ?>&quot;'><?php echo esc_html($MemberPlanName); ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($MemberPlanName); ?>&quot;"><?php _e('Edit','Shopp'); ?></a> | </span>
					<span class='delete'><a class='submitdelete' title='<?php _e('Delete','Shopp'); ?> &quot;<?php echo esc_attr($MemberPlanName); ?>&quot;' href="<?php echo esc_url($deleteurl); ?>" rel="<?php echo $MemberPlan->id; ?>"><?php _e('Delete','Shopp'); ?></a></span>
				</div>
			</td>
			<td class="type column-type<?php echo in_array('type',$hidden)?' hidden':''; ?>"><?php echo esc_html($Customer->user_login); ?></td>
			<td class="products column-products<?php echo in_array('products',$hidden)?' hidden':''; ?>"> 0 </td>
			<td class="members column-members<?php echo in_array('members',$hidden)?' hidden':''; ?>"> 0 </td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php _e('No memberships found.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>

	</form>
	<div class="tablenav">
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
</div>

<script type="text/javascript">

jQuery(document).ready( function() {
	var $=jQuery;

$('#selectall').change( function() {
	$('#memberships th input').each( function () {
		if (this.checked) this.checked = false;
		else this.checked = true;
	});
});


$('a.submitdelete').click(function () {
	if (confirm("<?php _e('You are about to delete this membership!\n \'Cancel\' to stop, \'OK\' to delete.','Shopp'); ?>")) {
		$('<input type="hidden" name="delete[]" />').val($(this).attr('rel')).appendTo('#memberships');
		$('<input type="hidden" name="deleting" />').val('membership').appendTo('#memberships');
		$('#memberships').submit();
		return false;
	} else return false;
});

$('#delete-button').click(function() {
	if (confirm("<?php echo addslashes(__('Are you sure you want to delete the selected memberships?','Shopp')); ?>")) {
		$('<input type="hidden" name="memberships" value="list" />').appendTo($('#memberships'));
		return true;
	} else return false;
});

pagenow = 'shopp_page_shopp-memberships';
columns.init(pagenow);

});

</script>
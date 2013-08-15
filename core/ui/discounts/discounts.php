<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php Shopp::_e('Discounts'); ?> <a href="<?php echo esc_url(add_query_arg(array_merge($_GET,array('page'=>'shopp-promotions','id'=>'new')),admin_url('admin.php'))); ?>" class="add-new-h2"><?php Shopp::_e('Add New'); ?></a></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<form action="<?php echo esc_url($url); ?>" id="promotions" method="get">
	<div>
		<input type="hidden" name="page" value="shopp-promotions" />
	</div>

	<p id="post-search" class="search-box">
		<input type="text" id="promotions-search-input" name="s" class="search-input" value="<?php echo esc_attr($s); ?>" />
		<input type="submit" value="<?php _e('Search Discounts','Shopp'); ?>" class="button" />
	</p>

	<div class="tablenav">
		<div class="alignleft actions">
			<select name="action" id="actions">
				<option value="" selected="selected"><?php _e('Bulk Actions&hellip;','Shopp'); ?></option>
				<option value="enable"><?php _e('Enable','Shopp'); ?></option>
				<option value="disable"><?php _e('Disable','Shopp'); ?></option>
				<option value="delete"><?php _e('Delete','Shopp'); ?></option>
			</select>
			<input type="submit" value="<?php esc_attr_e('Apply','Shopp'); ?>" name="apply" id="apply" class="button-secondary action" />
		</div>

		<div class="alignleft actions">
		<select name="status">
			<option value=""><?php _e('View All Discounts','Shopp'); ?></option>
			<?php echo Shopp::menuoptions($states,$status,true); ?>
		</select>
		<select name="type">
			<option value=""><?php _e('View All Types','Shopp'); ?></option>
			<?php echo Shopp::menuoptions($types,$type,true); ?>
		</select>
		<input type="submit" id="filter-button" value="<?php _e('Filter','Shopp'); ?>" class="button-secondary" />
		</div>

		<?php $ListTable->pagination('top'); ?>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php print_column_headers($this->screen); ?></tr>
		</thead>
		<tfoot>
		<tr><?php print_column_headers($this->screen,false); ?></tr>
		</tfoot>
	<?php if (sizeof($Promotions) > 0): ?>
		<tbody class="list promotions">
		<?php
			$hidden = get_hidden_columns($this->screen);

			$even = false;
			foreach ($Promotions as $Promotion):
			$editurl = add_query_arg(array('id'=>$Promotion->id),$url);
			$deleteurl = add_query_arg(array('selected'=>$Promotion->id,'action'=>'delete'),$url);
			$duplicateurl = add_query_arg(array('selected'=>$Promotion->id,'action'=>'duplicate'),$url);
			$enableurl = add_query_arg(array('selected'=>$Promotion->id,'action'=>'enable'),$url);
			$disableurl = add_query_arg(array('selected'=>$Promotion->id,'action'=>'disable'),$url);

			$PromotionName = empty($Promotion->name)?'('.__('no promotion name').')':$Promotion->name;
		?>
		<tr<?php if (!$even) echo " class='alternate'"; $even = !$even; ?>>
			<th scope='row' class='check-column'><input type='checkbox' name='selected[]' value='<?php echo $Promotion->id; ?>' class="selected" /></th>
			<td width="33%" class="name column-name"><a class='row-title' href='<?php echo esc_url($editurl); ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($PromotionName); ?>&quot;'><?php echo esc_html($PromotionName); ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($PromotionName); ?>&quot;"><?php _e('Edit','Shopp'); ?></a> | </span>
					<span class='duplicate'><a href="<?php echo esc_url($duplicateurl); ?>" title="<?php _e('Duplicate','Shopp'); ?> &quot;<?php echo esc_attr($PromotionName); ?>&quot;"><?php _e('Duplicate','Shopp'); ?></a> | </span>
					<span class='delete'><a class='delete' title='<?php _e('Delete','Shopp'); ?> &quot;<?php echo esc_attr($PromotionName); ?>&quot;' href="<?php echo esc_url($deleteurl); ?>" rel="<?php echo $Promotion->id; ?>"><?php _e('Delete','Shopp'); ?></a> | </span>

					<?php if ('disabled' == $Promotion->status): ?>
<span class='enable'><a href="<?php echo esc_url($enableurl); ?>" title="<?php _e('Enable','Shopp'); ?> &quot;<?php echo esc_attr($PromotionName); ?>&quot;"><?php _e('Enable','Shopp'); ?></a></span>
					<?php else: ?>
					<span class='disable'><a href="<?php echo esc_url($disableurl); ?>" title="<?php _e('Disable','Shopp'); ?> &quot;<?php echo esc_attr($PromotionName); ?>&quot;"><?php _e('Disable','Shopp'); ?></a></span>
					<?php endif; ?>
				</div>

			</td>
			<td class="discount column-discount<?php echo in_array('discount',$hidden)?' hidden':''; ?>"><?php
				if ($Promotion->type == "Percentage Off") echo Shopp::percentage((float)$Promotion->discount);
				if ($Promotion->type == "Amount Off") echo Shopp::money((float)$Promotion->discount);
				if ($Promotion->type == "Free Shipping") echo shopp_setting("free_shipping_text");
				if ($Promotion->type == "Buy X Get Y Free") echo __('Buy','Shopp').' '.$Promotion->buyqty.' '.__('Get','Shopp').' '.$Promotion->getqty.' '.__('Free','Shopp');
			?></td>
			<td class="applied column-applied<?php echo in_array('applied',$hidden)?' hidden':''; ?>"><?php echo $Promotion->target; ?></td>
			<td class="eff column-eff<?php echo in_array('eff',$hidden)?' hidden':''; ?>"><strong><?php echo $states[$Promotion->status]; ?></strong><?php
				$starts = (mktimestamp($Promotion->starts) > 1) ?
				                 Shopp::_d(get_option('date_format'), Shopp::mktimestamp($Promotion->starts)) :
				                 Shopp::_d(get_option('date_format'), Shopp::mktimestamp($Promotion->created));
				$ends = (Shopp::mktimestamp($Promotion->ends) > 1) ?
				               " — " . Shopp::_d(get_option('date_format'), Shopp::mktimestamp($Promotion->ends)) :
				               ", " . __('does not expire','Shopp');
				echo "<br />".$starts.$ends;
			?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="5"><?php _e('No promotions found.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	</form>
	<div class="tablenav">
		<?php $ListTable->pagination('bottom'); ?>
		<div class="clear"></div>
	</div>
</div>

<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready( function($) {
	var m = {
			none:<?php Shopp::_jse('Select some promotions!','Shopp'); ?>,
			enable:<?php Shopp::_jse('Are you sure you want to enable the selected promotions?','Shopp'); ?>,
			disable:<?php Shopp::_jse('Are you sure you want to disable the selected promotions?','Shopp'); ?>,
			delete:<?php Shopp::_jse('Are you sure you want to delete the selected promotions?','Shopp'); ?>
		},form = $('#promotions');

	pagenow = 'shopp_page_shopp-promotions';
	columns.init(pagenow);

	$('#selectall').change(function() {
		form.find('th input').each( function () {
			var $this = $(this),checked = $(this).attr('checked');
			$this.attr('checked',!checked);
		});
	});

	$('#actions').change(function () {
		var $this = $(this),action = $this.val();
		if (form.find('input.selected:checked').size() == 0) { alert(m.none); return $this.val(''); }
		if (confirm(m[action])) form.submit();
		$this.val('');
	});

	$('a.delete').click(function (e) {
		var action = $.getQueryVar('action',$(this).attr('href'));
		if (m[action] && confirm(m[action])) return true;
		e.preventDefault();
		return false;
	});

});
/* ]]> */
</script>
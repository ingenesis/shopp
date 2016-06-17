<?php
function save_meta_box ($Promotion) {
?>

<div id="misc-publishing-actions">
	<div class="misc-pub-section misc-pub-section-last">

	<label for="discount-status"><input type="hidden" name="status" value="disabled" /><input type="checkbox" name="status" id="discount-status" value="enabled"<?php echo ($Promotion->status == "enabled")?' checked="checked"':''; ?> /> &nbsp;<?php _e('Enabled','Shopp'); ?></label>
	</div>

	<div class="misc-pub-section misc-pub-section-last">

	<div id="start-position" class="calendar-wrap"><?php
		$dateorder = Shopp::date_format_order(true);
		$previous = false;
		foreach ($dateorder as $type => $format):
			if ($previous == "s" && $type[0] == "s") continue;
	 		if ("month" == $type): ?><input type="text" name="starts[month]" id="starts-month" title="<?php _e('Month','Shopp'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->starts>1)?date("n",$Promotion->starts):''; ?>" class="selectall" /><?php elseif ("day" == $type): ?><input type="text" name="starts[date]" id="starts-date" title="<?php _e('Day','Shopp'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->starts>1)?date("j",$Promotion->starts):''; ?>" class="selectall" /><?php elseif ("year" == $type): ?><input type="text" name="starts[year]" id="starts-year" title="<?php _e('Year','Shopp'); ?>" size="5" maxlength="4" value="<?php echo ($Promotion->starts>1)?date("Y",$Promotion->starts):''; ?>" class="selectall" /><?php elseif ($type[0] == "s"): echo "/"; endif; $previous = $type[0];  endforeach; ?></div>
	<p><?php _e('Start promotion on this date.','Shopp'); ?></p>

	<div id="end-position" class="calendar-wrap"><?php
		$previous = false;
		foreach ($dateorder as $type => $format):
			if ($previous == "s" && $type[0] == "s") continue;
			if ("month" == $type): ?><input type="text" name="ends[month]" id="ends-month" title="<?php _e('Month','Shopp'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->ends>1)?date("n",$Promotion->ends):''; ?>" class="selectall" /><?php elseif ("day" == $type): ?><input type="text" name="ends[date]" id="ends-date" title="<?php _e('Day','Shopp'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->ends>1)?date("j",$Promotion->ends):''; ?>" class="selectall" /><?php elseif ("year" == $type): ?><input type="text" name="ends[year]" id="ends-year" title="<?php _e('Year','Shopp'); ?>" size="5" maxlength="4" value="<?php echo ($Promotion->ends>1)?date("Y",$Promotion->ends):''; ?>" class="selectall" /><?php elseif ($type[0] == "s"): echo "/"; endif; $previous = $type[0];  endforeach; ?></div>
	<p><?php _e('End the promotion on this date.','Shopp'); ?></p>

	</div>

</div>

<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save','Shopp'); ?>" />
</div>
<?php
}
ShoppUI::addmetabox('save-promotion', __('Save','Shopp').$Admin->boxhelp('promo-editor-save'), 'save_meta_box', 'shopp_page_shopp-discounts', 'side', 'core');

function discount_meta_box ($Promotion) {
	$types = ShoppAdminDiscounter::types(); ?>
<p><span>
<select name="type" id="discount-type">
	<?php echo menuoptions($types, $Promotion->type, true); ?>
</select></span>
<span id="discount-row">
	&mdash;
	<input type="text" name="discount" id="discount-amount" value="<?php echo $Promotion->discount; ?>" size="10" class="selectall" />
</span>
<span id="bogof-row">
	&mdash;
	&nbsp;<?php _e('Buy','Shopp'); ?> <input type="text" name="buyqty" id="buy-x" value="<?php echo $Promotion->buyqty; ?>" size="5" class="selectall" /> <?php _e('Get','Shopp'); ?> <input type="text" name="getqty" id="get-y" value="<?php echo $Promotion->getqty; ?>" size="5" class="selectall" />
</span></p>
<p><?php _e('Select the discount type and amount.','Shopp'); ?></p>

<?php
}
ShoppUI::addmetabox('promotion-discount', __('Discount','Shopp').$Admin->boxhelp('promo-editor-discount'), 'discount_meta_box', 'shopp_page_shopp-discounts', 'normal', 'core');

function rules_meta_box ($Promotion) {
	$targets = array(
		'Catalog' => __('catalog product','Shopp'),
		'Cart' => __('shopping cart','Shopp'),
		'Cart Item' => __('cart item','Shopp'),

	);

	$target = '<select name="target" id="promotion-target" class="small">';
	$target .= menuoptions($targets,$Promotion->target,true);
	$target .= '</select>';

	if (empty($Promotion->search)) $Promotion->search = "all";

	$logic = '<select name="search" class="small">';
	$logic .= menuoptions(array('any' => Shopp::__('any'),'all' => strtolower(Shopp::__('All'))), $Promotion->search, true);
	$logic .= '</select>';

?>
<p><strong><?php printf(__('Apply discount to %s','Shopp'),$target,$logic); ?> <strong id="target-property"></strong></strong></p>
<table class="form-table" id="cartitem"></table>

<p><strong><?php printf(__('When %s of these conditions match the','Shopp'),$logic); ?> <strong id="rule-target">:</strong></strong></p>

<table class="form-table" id="rules"></table>
<?php
}
ShoppUI::addmetabox('promotion-rules', __('Conditions','Shopp').$Admin->boxhelp('promo-editor-conditions'), 'rules_meta_box', 'shopp_page_shopp-discounts', 'normal', 'core');
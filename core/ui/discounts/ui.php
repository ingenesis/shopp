<?php
function save_meta_box ($Promotion) {
?>

<div id="misc-publishing-actions">
	<div class="misc-pub-section misc-pub-section-last">

	<label for="discount-status"><input type="hidden" name="status" value="disabled" /><input type="checkbox" name="status" id="discount-status" value="enabled"<?php echo ($Promotion->status == "enabled")?' checked="checked"':''; ?> /> &nbsp;<?php Shopp::_e('Enabled'); ?></label>
	</div>

	<div class="misc-pub-section misc-pub-section-last">

	<div id="start-position" class="calendar-wrap"><?php
		$dateorder = Shopp::date_format_order(true);
		$previous = false;
		foreach ($dateorder as $type => $format):
			if ( $previous == "s" && $type[0] == "s" ) continue;
	 		if ( "month" == $type ): ?><input type="text" name="starts[month]" id="starts-month" title="<?php Shopp::_e('Month'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->starts>1)?date("n",$Promotion->starts):''; ?>" class="selectall" /><?php elseif ("day" == $type): ?><input type="text" name="starts[date]" id="starts-date" title="<?php Shopp::_e('Day'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->starts>1)?date("j",$Promotion->starts):''; ?>" class="selectall" /><?php elseif ("year" == $type): ?><input type="text" name="starts[year]" id="starts-year" title="<?php Shopp::_e('Year'); ?>" size="5" maxlength="4" value="<?php echo ($Promotion->starts>1)?date("Y",$Promotion->starts):''; ?>" class="selectall" /><?php elseif ($type[0] == "s"): echo "/"; endif; $previous = $type[0];  endforeach; ?></div>
	<p><?php Shopp::_e('Start promotion on this date.'); ?></p>

	<div id="end-position" class="calendar-wrap"><?php
		$previous = false;
		foreach ($dateorder as $type => $format):
			if ($previous == "s" && $type[0] == "s") continue;
			if ("month" == $type): ?><input type="text" name="ends[month]" id="ends-month" title="<?php Shopp::_e('Month'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->ends>1)?date("n",$Promotion->ends):''; ?>" class="selectall" /><?php elseif ("day" == $type): ?><input type="text" name="ends[date]" id="ends-date" title="<?php Shopp::_e('Day'); ?>" size="3" maxlength="2" value="<?php echo ($Promotion->ends>1)?date("j",$Promotion->ends):''; ?>" class="selectall" /><?php elseif ("year" == $type): ?><input type="text" name="ends[year]" id="ends-year" title="<?php _e('Year'); ?>" size="5" maxlength="4" value="<?php echo ($Promotion->ends>1)?date("Y",$Promotion->ends):''; ?>" class="selectall" /><?php elseif ($type[0] == "s"): echo "/"; endif; $previous = $type[0];  endforeach; ?></div>
	<p><?php Shopp::_e('End the promotion on this date.'); ?></p>

	</div>

</div>

<div id="major-publishing-actions">
	<input type="submit" class="button-primary" name="save" value="<?php Shopp::_e('Save'); ?>" />
</div>
<?php
}
ShoppUI::addmetabox('save-promotion', Shopp::__('Save') . $Admin->boxhelp('promo-editor-save'), 'save_meta_box', 'shopp_page_shopp-discounts', 'side', 'core');

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
	&nbsp;<?php Shopp::_e('Buy'); ?> <input type="text" name="buyqty" id="buy-x" value="<?php echo $Promotion->buyqty; ?>" size="5" class="selectall" /> <?php Shopp::_e('Get'); ?> <input type="text" name="getqty" id="get-y" value="<?php echo $Promotion->getqty; ?>" size="5" class="selectall" />
</span></p>
<p><?php Shopp::_e('Select the discount type and amount.'); ?></p>

<?php
}
ShoppUI::addmetabox('promotion-discount', Shopp::__('Discount') . $Admin->boxhelp('promo-editor-discount'), 'discount_meta_box', 'shopp_page_shopp-discounts', 'normal', 'core');

function rules_meta_box ($Promotion) {
	$targets = array(
		'Catalog'	=> Shopp::__('catalog product'),
		'Cart'	    => Shopp::__('shopping cart'),
		'Cart Item'	=> Shopp::__('cart item'),

	);

	$target = '<select name="target" id="promotion-target" class="small">';
	$target .= menuoptions($targets,$Promotion->target,true);
	$target .= '</select>';

	if (empty($Promotion->search)) $Promotion->search = "all";

	$logic = '<select name="search" class="small">';
	$logic .= menuoptions(array('any' => Shopp::__('any'),'all' => strtolower(Shopp::__('All'))), $Promotion->search, true);
	$logic .= '</select>';

?>
<p><strong><?php Shopp::_e('Apply discount to %s', $target); ?> <strong id="target-property"></strong></strong></p>
<table class="form-table" id="cartitem"></table>

<p><strong><?php Shopp::_e('When %s of these conditions match the', $logic); ?> <strong id="rule-target">:</strong></strong></p>

<table class="form-table" id="rules"></table>
<?php
}
ShoppUI::addmetabox('promotion-rules', Shopp::__('Conditions') . $Admin->boxhelp('promo-editor-conditions'), 'rules_meta_box', 'shopp_page_shopp-discounts', 'normal', 'core');
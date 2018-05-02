<?php wp_nonce_field('shopp-setup-management'); ?>

<table class="form-table">

	<tr>
		<th scope="row" valign="top"><label for="confirm_url"><?php _e('Order Confirmation','Shopp'); ?></label></th>
		<td><input type="radio" name="settings[order_confirmation]" value="" id="order_confirmation_ontax"<?php if ( 'always' != shopp_setting('order_confirmation') ) echo ' checked="checked"' ?> /> <label for="order_confirmation_ontax"><?php _e('Show only when the order total changes','Shopp'); ?></label><br />
			<input type="radio" name="settings[order_confirmation]" value="always" id="order_confirmation_always"<?php if ( 'always' == shopp_setting('order_confirmation') ) echo ' checked="checked"' ?> /> <label for="order_confirmation_always"><?php _e('Show for all orders','Shopp') ?></label></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="receipt_copy_both"><?php _e('Receipt Emails','Shopp'); ?></label></th>
		<td><input type="radio" name="settings[receipt_copy]" value="0" id="receipt_copy_customer_only"<?php if (shopp_setting('receipt_copy') == "0") echo ' checked="checked"'; ?> /> <label for="receipt_copy_customer_only"><?php _e('Send to Customer Only','Shopp'); ?></label><br />
			<input type="radio" name="settings[receipt_copy]" value="1" id="receipt_copy_both"<?php if (shopp_setting('receipt_copy') == "1") echo ' checked="checked"'; ?> /> <label for="receipt_copy_both"><?php _e('Send to Customer &amp; Merchant Email','Shopp'); ?></label> (<?php _e('see','Shopp'); ?> <a href="?page=shopp-setup"><?php _e('Shopp Setup','Shopp'); ?></a>)</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="account-system-none"><?php _e('Customer Accounts','Shopp'); ?></label></th>
		<td><input type="radio" name="settings[account_system]" value="none" id="account-system-none"<?php if(shopp_setting('account_system') == "none") echo ' checked="checked"' ?> /> <label for="account-system-none"><?php _e('No Accounts','Shopp'); ?></label><br />
			<input type="radio" name="settings[account_system]" value="shopp" id="account-system-shopp"<?php if(shopp_setting('account_system') == "shopp") echo ' checked="checked"' ?> /> <label for="account-system-shopp"><?php _e('Enable Account Logins','Shopp'); ?></label><br />
			<input type="radio" name="settings[account_system]" value="wordpress" id="account-system-wp"<?php if(shopp_setting('account_system') == "wordpress") echo ' checked="checked"' ?> /> <label for="account-system-wp"><?php _e('Enable Account Logins integrated with WordPress Users','Shopp'); ?></label></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="backorders-toggle"><?php Shopp::_e('Inventory Backorders'); ?></label></th>
		<td><input type="hidden" name="settings[backorders]" value="off" /><input type="checkbox" name="settings[backorders]" value="on" id="backorders-toggle"<?php if ( shopp_setting_enabled('backorders') ) echo ' checked="checked"'?> /><label for="backorders-toggle"> <?php _e('Allow backorders','Shopp'); ?></label><br />
		<?php _e('Allows customers to order products that cannot be fulfilled because of a lack of available product in-stock. Disable to prevent customers from ordering more product than is available in-stock.','Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="lowstock-level"><?php _e('Low Inventory','Shopp'); ?></label></th>
		<td>
			<?php
				$values = array_reverse(array_merge(range(0,25),range(30,50,5),range(60,90,10)));
				$labels = $values;
				array_walk($labels,create_function('&$val','$val = "$val%";'));
				$levels = array_combine($values,$labels);
			?>
			<select name="settings[lowstock_level]" id="lowstock-level">
			<?php echo Shopp::menuoptions($levels,$lowstock,true); ?>
			</select><br />
        	<?php _e('Select the level for low stock warnings.','Shopp'); ?>
		</td>
	</tr>
	
	<tr>
		<th scope="row" valign="top"><label for="promo-limit"><?php Shopp::_e('Discount Limit'); ?></label></th>
		<td><select name="settings[promo_limit]" id="promo-limit">
			<option value="">&infin;</option>
			<?php echo Shopp::menuoptions($promolimit,shopp_setting('promo_limit')); ?>
			</select>
			<label> <?php _e('per order','Shopp'); ?></label>
		</td>
	</tr>
	
	<tr>
		<th scope="row" valign="top"><label for="accounting-serial"><?php _e('Next Order Number','Shopp'); ?></label></th>
		<td><input type="number" name="settings[next_order_id]" id="accounting-serial" value="<?php echo esc_attr($next_setting); ?>" size="7" class="selectall" /><br />
			<?php _e('Set the next order number to sync with your accounting systems.','Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="order_handling_fee"><?php _e('Order Handling Fee','Shopp'); ?></label></th>
		<td><input type="text" name="settings[order_shipfee]" value="<?php echo Shopp::money(shopp_setting('order_shipfee')); ?>" id="order_handling_fee" size="7" class="right selectall money" /><br />
        <?php _e('Handling fee applied once to each order with shipped products.','Shopp'); ?></td>
	</tr>
	
	<tr>
		<th scope="row" valign="top"><label for="order-processing-min"><?php _e('Order Processing','Shopp'); ?></label></th>
		<td>
		<select name="settings[order_processing_min]" id="order-processing">
				<?php echo Shopp::menuoptions(Lookup::timeframes_menu(),shopp_setting('order_processing_min'),true); ?>
		</select> &mdash; <select name="settings[order_processing_max]" id="order-processing">
					<?php echo Shopp::menuoptions(Lookup::timeframes_menu(),shopp_setting('order_processing_max'),true); ?>
		</select><br />
		<?php _e('Set the estimated time range for processing orders for shipment.','Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="cart-toggle"><?php _e('Order Status Labels','Shopp'); ?></label></th>
		<td>
		<ol id="order-statuslabels" class="labelset">

		</ol>
		<?php _e('Set custom order status labels. Map them to order states for automatic order handling. Remember to click <strong>Save Changes</strong> below!','Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="cart-toggle"><?php _e('Order Cancellation Reasons','Shopp'); ?></label></th>
		<td>
		<ol id="order-cancelreasons" class="labelset">
		</ol>
		<?php _e('Set custom order cancellation reasons. Remember to click <strong>Save Changes</strong> below!','Shopp'); ?></td>
	</tr>
</table>

<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>

<script id="statusLabel" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<li id="status-${id}">
	<span>
	<input type="text" name="settings[order_status][${id}]" id="status-${id}" size="14" value="${label}" /><button type="button" class="delete">
		<span class="shoppui-minus"><span class="hidden"><?php Shopp::_e('Add'); ?></span></span>
	</button><select name="settings[order_states][${id}]" id="state-${id}">
	<?php echo Shopp::menuoptions($states,'',true); ?>
	</select>
	<button type="button" class="add">
		<span class="shoppui-plus"><span class="hidden"><?php Shopp::_e('Add'); ?></span></span>
	</button>
	</span>
</li>
<?php $statusui = ob_get_contents(); ob_end_clean(); echo $statusui; ?>
</script>

<script id="reasonLabel" type="text/x-jquery-tmpl">
<li id="status-${id}">
	<span>
	<input type="text" name="settings[cancel_reasons][${id}]" id="reason-${id}" size="40" value="${label}" /><button type="button" class="delete"><span class="shoppui-minus"><span class="hidden"><?php Shopp::_e('Add'); ?></span></span></button><button type="button" class="add">
		<span class="shoppui-plus"><span class="hidden"><?php Shopp::_e('Add'); ?></span></span>
	</button>
	</span>
</li>
</script>

<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function ($) {
	var labels = <?php echo json_encode($statusLabels); ?>,
		states = <?php echo json_encode($statesLabels); ?>,
		reasons = <?php echo json_encode($reasonLabels); ?>;
	$('#order-statuslabels').labelset(labels, '#statusLabel');
	$("#order-statuslabels select").each(function(){
		var menuid = $(this).attr('id'),
			id = menuid.substr(menuid.indexOf('-') + 1);

		if ( states != null && states[id] != undefined )
			$(this).val(states[id]);
	});
	$('#order-cancelreasons').labelset(reasons, '#reasonLabel');
});
/* ]]> */
</script>

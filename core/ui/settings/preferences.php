<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<div class="icon32"></div>
	<h2><?php _e('Store Preferences','Shopp'); ?></h2>

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


	<form name="settings" id="checkout" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-preferences'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="dashboard-toggle"><?php _e('Dashboard Widgets','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[dashboard]" value="off" /><input type="checkbox" name="settings[dashboard]" value="on" id="dashboard-toggle"<?php if (shopp_setting('dashboard') == "on") echo ' checked="checked"'?> /><label for="dashboard-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
	            <?php _e('Check this to display store performance metrics and more on the WordPress Dashboard.','Shopp'); ?></td>
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
			<tr>
				<th scope="row" valign="top"><label for="accounting-serial"><?php _e('Next Order Number','Shopp'); ?></label></th>
				<td><input type="number" name="settings[next_order_id]" id="accounting-serial" value="<?php echo esc_attr($next_setting); ?>" size="7" class="selectall" /><br />
					<?php _e('Set the next order number to sync with your accounting systems.','Shopp'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="promo-limit"><?php _e('Promotions Limit','Shopp'); ?></label></th>
				<td><select name="settings[promo_limit]" id="promo-limit">
					<option value="">&infin;</option>
					<?php echo menuoptions($promolimit,shopp_setting('promo_limit')); ?>
					</select>
					<label> <?php _e('per order','Shopp'); ?></label>
				</td>
			</tr>
		</table>

		<h3><?php _e('Checkout Preferences','Shopp')?></h3>
		<table class="form-table">

			<tr>
				<th scope="row" valign="top"><label for="shopping-cart-toggle"><?php _e('Shopping Cart','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[shopping_cart]" value="off" /><input type="checkbox" name="settings[shopping_cart]" value="on" id="shopping-cart-toggle"<?php if (shopp_setting_enabled('shopping_cart')) echo ' checked="checked"'?> /><label for="shopping-cart-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
	            <?php _e('Uncheck this to disable the shopping cart and checkout. Useful for catalog-only sites.','Shopp'); ?></td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="confirm_url"><?php _e('Order Confirmation','Shopp'); ?></label></th>
				<td><input type="radio" name="settings[order_confirmation]" value="" id="order_confirmation_ontax"<?php if ( 'always' != shopp_setting('order_confirmation') ) echo ' checked="checked"' ?> /> <label for="order_confirmation_ontax"><?php _e('Show only when the order total changes','Shopp'); ?></label><br />
					<input type="radio" name="settings[order_confirmation]" value="always" id="order_confirmation_always"<?php if ( 'always' == shopp_setting('order_confirmation') ) echo ' checked="checked"' ?> /> <label for="order_confirmation_always"><?php _e('Show for all orders','Shopp') ?></label></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="receipt_copy_both"><?php _e('Receipt Emails','Shopp'); ?></label></th>
				<td><input type="radio" name="settings[receipt_copy]" value="0" id="receipt_copy_customer_only"<?php if (shopp_setting('receipt_copy') == "0") echo ' checked="checked"'; ?> /> <label for="receipt_copy_customer_only"><?php _e('Send to Customer Only','Shopp'); ?></label><br />
					<input type="radio" name="settings[receipt_copy]" value="1" id="receipt_copy_both"<?php if (shopp_setting('receipt_copy') == "1") echo ' checked="checked"'; ?> /> <label for="receipt_copy_both"><?php _e('Send to Customer &amp; Shop Owner Email','Shopp'); ?></label> (<?php _e('see','Shopp'); ?> <a href="?page=shopp-settings"><?php _e('General Settings','Shopp'); ?></a>)</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="account-system-none"><?php _e('Customer Accounts','Shopp'); ?></label></th>
				<td><input type="radio" name="settings[account_system]" value="none" id="account-system-none"<?php if(shopp_setting('account_system') == "none") echo ' checked="checked"' ?> /> <label for="account-system-none"><?php _e('No Accounts','Shopp'); ?></label><br />
					<input type="radio" name="settings[account_system]" value="shopp" id="account-system-shopp"<?php if(shopp_setting('account_system') == "shopp") echo ' checked="checked"' ?> /> <label for="account-system-shopp"><?php _e('Enable Account Logins','Shopp'); ?></label><br />
					<input type="radio" name="settings[account_system]" value="wordpress" id="account-system-wp"<?php if(shopp_setting('account_system') == "wordpress") echo ' checked="checked"' ?> /> <label for="account-system-wp"><?php _e('Enable Account Logins integrated with WordPress Users','Shopp'); ?></label></td>
			</tr>

		</table>

		<h3><?php _e('Product Downloads','Shopp')?></h3>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="download-limit"><?php _e('Download Limit','Shopp'); ?></label></th>
				<td><select name="settings[download_limit]" id="download-limit">
					<option value="">&infin;</option>
					<?php echo menuoptions($downloads,shopp_setting('download_limit')); ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="download-timelimit"><?php _e('Time Limit','Shopp'); ?></label></th>
				<td><select name="settings[download_timelimit]" id="download-timelimit">
					<option value=""><?php _e('No Limit','Shopp'); ?></option>
					<?php echo menuoptions($time,shopp_setting('download_timelimit'),true); ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="download-restriction"><?php _e('IP Restriction','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[download_restriction]" value="off" />
					<label for="download-restriction"><input type="checkbox" name="settings[download_restriction]" id="download-restriction" value="ip" <?php echo (shopp_setting('download_restriction') == "ip")?'checked="checked" ':'';?> /> <?php _e('Restrict to the computer the product is purchased from','Shopp'); ?></label></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="download-quantity"><?php _e('Download Quantity','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[download_quantity]" value="off" />
					<label for="download-quantity"><input type="checkbox" name="settings[download_quantity]" id="download-quantity" value="on" <?php echo (shopp_setting_enabled('download_quantity') ? 'checked="checked" ':'');?> /> <?php _e('Enable quantity selection for download products','Shopp'); ?></label></td>
			</tr>
		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>


<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function ($) {
	var labels = <?php echo json_encode($statusLabels); ?>,
		states = <?php echo json_encode($statesLabels); ?>,
		reasons = <?php echo json_encode($reasonLabels); ?>;
	$('#order-statuslabels').labelset(labels,'#statusLabel');
	$("#order-statuslabels select").each(function(i,menu){
		var menuid = $(menu).attr('id'),
			id = menuid.substr(menuid.indexOf('-')+1);

		if(states != null && states[id] != undefined)
			$(this).find("option[value="+states[id]+"]").attr("selected", "selected");
	});
	$('#order-cancelreasons').labelset(reasons,'#reasonLabel');
});
/* ]]> */
</script>
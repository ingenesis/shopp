<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

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
		<?php wp_nonce_field('shopp-setup-management'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="dashboard-toggle"><?php Shopp::_e('Dashboard Widgets'); ?></label></th>
				<td><input type="hidden" name="settings[dashboard]" value="off" /><input type="checkbox" name="settings[dashboard]" value="on" id="dashboard-toggle"<?php if (shopp_setting('dashboard') == "on") echo ' checked="checked"'?> /><label for="dashboard-toggle"> <?php Shopp::_e('Enabled'); ?></label><br />
	            <?php Shopp::_e('Check this to display store performance metrics and more on the WordPress Dashboard.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="cart-toggle"><?php Shopp::_e('Order Status Labels'); ?></label></th>
				<td>
				<ol id="order-statuslabels" class="labelset">

				</ol>
				<?php Shopp::_e('Set custom order status labels. Map them to order states for automatic order handling. Remember to click <strong>Save Changes</strong> below!'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="cart-toggle"><?php Shopp::_e('Order Cancellation Reasons'); ?></label></th>
				<td>
				<ol id="order-cancelreasons" class="labelset">
				</ol>
				<?php Shopp::_e('Set custom order cancellation reasons. Remember to click <strong>Save Changes</strong> below!'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="accounting-serial"><?php Shopp::_e('Next Order Number'); ?></label></th>
				<td><input type="number" name="settings[next_order_id]" id="accounting-serial" value="<?php echo esc_attr($next_setting); ?>" size="7" class="selectall" /><br />
					<?php Shopp::_e('Set the next order number to sync with your accounting systems.'); ?></td>
			</tr>
		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php Shopp::_e('Save Changes'); ?>" /></p>
	</form>
</div>


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

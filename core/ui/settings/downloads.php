<div class="wrap shopp">
	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<form name="settings" id="checkout" action="<?php echo add_query_arg('tab', $this->tab, esc_url($this->url)); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-downloads'); ?>

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

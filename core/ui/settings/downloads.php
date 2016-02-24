<table class="form-table">
	<tr>
<th scope="row" valign="top"><label for="download-limit"><?php Shopp::_e('Download Limit'); ?></label></th>
		<td><select name="settings[download_limit]" id="download-limit">
			<option value="">&infin;</option>
	<?php echo menuoptions($downloads, shopp_setting('download_limit')); ?>
			</select>
		</td>
	</tr>
	<tr>
<th scope="row" valign="top"><label for="download-timelimit"><?php Shopp::_e('Time Limit'); ?></label></th>
		<td><select name="settings[download_timelimit]" id="download-timelimit">
	<option value=""><?php Shopp::_e('No Limit'); ?></option>
	<?php echo menuoptions($time, shopp_setting('download_timelimit'), true); ?>
			</select>
		</td>
	</tr>
	<tr>
<th scope="row" valign="top"><label for="download-restriction"><?php Shopp::_e('IP Restriction'); ?></label></th>
		<td><input type="hidden" name="settings[download_restriction]" value="off" />
	<label for="download-restriction"><input type="checkbox" name="settings[download_restriction]" id="download-restriction" value="ip" <?php echo 'ip' == shopp_setting('download_restriction') ?'checked="checked" ' : '';?> /> <?php Shopp::_e('Enabled'); ?><br /><?php _e('Enable to restrict downloads to the IP address the product is purchased from.','Shopp'); ?></label></td>
	</tr>
	<tr>
<th scope="row" valign="top"><label for="download-quantity"><?php Shopp::_e('Download Quantity'); ?></label></th>
		<td><input type="hidden" name="settings[download_quantity]" value="off" />
	<label for="download-quantity"><input type="checkbox" name="settings[download_quantity]" id="download-quantity" value="on" <?php echo shopp_setting_enabled('download_quantity') ? 'checked="checked" ' : '';?> /> <?php Shopp::_e('Enabled'); ?></label><br />
	<?php Shopp::_e('Allow shoppers to change the item quantity for downloads.')?></td>
	</tr>
</table>

<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>

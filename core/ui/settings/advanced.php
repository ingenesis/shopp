<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<form name="settings" id="system" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-system-advanced'); ?>

		<table class="form-table">

			<tr>
				<th scope="row" valign="top"><label for="uploader-toggle"><?php _e('Upload System','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[uploader_pref]" value="browser" /><input type="checkbox" name="settings[uploader_pref]" value="flash" id="uploader-toggle"<?php if (shopp_setting('uploader_pref') == "flash") echo ' checked="checked"'?> /><label for="uploader-toggle"> <?php _e('Enable Flash-based uploading','Shopp'); ?></label><br />
	            <?php _e('Enable to use Adobe Flash uploads for accurate upload progress. Disable this setting if you are having problems uploading.','Shopp'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="script-server"><?php _e('Script Loading','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[script_server]" value="script" /><input type="checkbox" name="settings[script_server]" value="plugin" id="script-server"<?php if (shopp_setting('script_server') == "plugin") echo ' checked="checked"'?> /><label for="script-server"> <?php _e('Load behavioral scripts through WordPress','Shopp'); ?></label><br />
	            <?php _e('Enable this setting when experiencing problems loading scripts with the Shopp Script Server','Shopp'); ?>
				<div><input type="hidden" name="settings[script_loading]" value="catalog" /><input type="checkbox" name="settings[script_loading]" value="global" id="script-loading"<?php if (shopp_setting('script_loading') == "global") echo ' checked="checked"'?> /><label for="script-loading"> <?php _e('Enable Shopp behavioral scripts site-wide','Shopp'); ?></label><br />
	            <?php _e('Enable this to make Shopp behaviors available across all of your WordPress posts and pages.','Shopp'); ?></div>

				</td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="error-notifications"><?php _e('Error Notifications','Shopp'); ?></label></th>
				<td><ul id="error_notify">
					<?php foreach ($notification_errors as $id => $level): ?>
						<li><input type="checkbox" name="settings[error_notifications][]" id="error-notification-<?php echo $id; ?>" value="<?php echo $id; ?>"<?php if (in_array($id,$notifications)) echo ' checked="checked"'; ?>/><label for="error-notification-<?php echo $id; ?>"> <?php echo $level; ?></label></li>
					<?php endforeach; ?>
					</ul>
					<label for="error-notifications"><?php _e("Send email notifications of the selected errors to the merchant's email address.","Shopp"); ?></label>
	            </td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="error-logging"><?php _e('Logging','Shopp'); ?></label></th>
				<td><select name="settings[error_logging]" id="error-logging">
					<?php echo menuoptions($errorlog_levels,shopp_setting('error_logging'),true); ?>
					</select><br />
					<label for="error-notifications"><?php _e("Limit logging errors up to the level of the selected error type.","Shopp"); ?></label>
	            </td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="rebuild-index"><?php _e('Search Index','Shopp'); ?></label></th>
				<td><button type="button" id="rebuild-index" name="rebuild" class="button-secondary"><?php _e('Rebuild Product Search Index','Shopp'); ?></button><br />
	            <?php _e('Update search indexes for all the products in the catalog.','Shopp'); ?></td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="product-summaries"><?php _e('Product Summaries','Shopp'); ?></label></th>
				<td><button type="submit" id="product-summaries" name="resum" value="true" class="button-secondary"><?php _e('Recalculate Product Summaries','Shopp'); ?></button><br />
	            <?php _e('Recalculate product summaries to update aggregate pricing, discounts and sales data.','Shopp'); ?></td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="image-cache"><?php _e('Image Cache','Shopp'); ?></label></th>
				<td><button type="submit" id="image-cache" name="rebuild" value="true" class="button-secondary"><?php _e('Delete Cached Images','Shopp'); ?></button><br />
	            <?php _e('Removes all cached images so that they will be recreated.','Shopp'); ?></td>
			</tr>


		</table>
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

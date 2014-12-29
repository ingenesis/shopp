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
					<p><?php _e('Enable to use Adobe Flash uploads for accurate upload progress. Disable this setting if you are having problems uploading.','Shopp'); ?></p></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="script-server"><?php _e('Script Loading','Shopp'); ?></label></th>
				<td><p><input type="hidden" name="settings[script_loading]" value="catalog" /><input type="checkbox" name="settings[script_loading]" value="global" id="script-loading"<?php if (shopp_setting('script_loading') == "global") echo ' checked="checked"'?> /><label for="script-loading"> <?php _e('Enable Shopp behavioral scripts site-wide','Shopp'); ?></label></p>
	            <p><?php _e('Enable this to make Shopp behaviors available across all of your WordPress posts and pages.','Shopp'); ?></p>
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
				<th scope="row"><label for="shopp-services-helper"><?php Shopp::_e('Services'); ?></label></th>
				<td><?php if ( $this->helper_installed() ): ?>
					<p><button type="submit" id="shopp-services-helper" name="shopp_services_helper" value="remove" class="button-secondary"><?php Shopp::_e('Uninstall Services Helper'); ?></button></p>
					<p><?php Shopp::_e('Uninstall the services helper'); ?>

					<p><label><?php Shopp::_e('Load select plugins for Shopp service requests:'); ?></label></p>
					<div id="shopp_services_plugins" class="multiple-select">
						<ul>
							<?php
								$even = true;
								foreach ( $service_plugins as $name => $plugin ):
									$classes = array();
									if ( $even ) $classes[] = 'odd';
							?>
								<li<?php if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="shopp_services_plugins[<?php echo $name; ?>]" value="<?php echo $plugin; ?>" id="plugin-<?php echo sanitize_key($name); ?>" checked="checked" /><label for="plugin-<?php echo sanitize_key($name); ?>" accesskey="<?php echo substr($name,0,1); ?>"><?php echo $plugin; ?></label></li>
							<?php $even = !$even; endforeach; $classes = array(); ?>
							<li<?php if ( $even ) $classes[] = 'odd'; $classes[] = 'hide-if-no-js'; if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; $even = !$even; ?>><input type="checkbox" name="selectall_plugins" id="selectall_plugins" /><label for="selectall_plugins"><strong><?php Shopp::_e('Select All'); ?></strong></label></li>
							<?php
								foreach ( $plugins as $name => $plugin):
									$plugin = $plugin['Name'];
									if ( SHOPP_PLUGINFILE == $name ) continue;
									$classes = array();
									if ( $even ) $classes[] = 'odd';
								?>
							<?php if ( ! in_array($plugin, $service_plugins) ): ?>
							<li<?php if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="shopp_services_plugins[<?php echo $name; ?>]" value="<?php echo $plugin; ?>" id="plugin-<?php echo sanitize_key($name); ?>" /><label for="plugin-<?php echo sanitize_key($name); ?>"><?php echo $plugin; ?></label></li>
							<?php $even = !$even; endif; endforeach; ?>
						</ul>
					</div>
				<?php else: ?>
					<button type="submit" id="shopp-services-helper" name="shopp_services_helper" value="install" class="button-secondary"><?php Shopp::_e('Install Services Helper'); ?></button>
					<p><?php Shopp::_e('Install the services helper to improve performance and reliability'); ?>
				<?php endif; ?>

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

<script type="text/javascript">
jQuery(document).ready( function($) {
	$('#selectall_plugins').change(function () {
		if ($(this).attr('checked')) $('#shopp_services_plugins input').not(this).attr('checked',true);
		else $('#shopp_services_plugins input').not(this).attr('checked',false);
	});
});
</script>

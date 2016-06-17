<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<!-- shopp_storage_engine_settings -->
	<?php do_action('shopp_storage_engine_settings'); ?>

	<form name="settings" id="system" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-system-storage'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="image-storage"><?php _e('Image Storage','Shopp'); ?></label></th>
				<td><select name="settings[image_storage]" id="image-storage">
					<?php echo Shopp::menuoptions($storage,shopp_setting('image_storage'),true); ?>
					</select><input type="submit" name="image-settings" value="<?php _e('Settings&hellip;','Shopp'); ?>" class="button-secondary hide-if-js"/>
					<div id="image-storage-engine" class="storage-settings"><?php if ($ImageStorage) echo $ImageStorage->ui('image'); ?></div>
	            </td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="download-storage"><?php _e('Product File Storage','Shopp'); ?></label></th>
				<td><select name="settings[product_storage]" id="download-storage">
					<?php echo Shopp::menuoptions($storage,shopp_setting('product_storage'),true); ?>
					</select><input type="submit" name="download-settings" value="<?php _e('Settings&hellip;','Shopp'); ?>" class="button-secondary hide-if-js"/>
					<div id="download-storage-engine" class="storage-settings"><?php if ($DownloadStorage) echo $DownloadStorage->ui('download'); ?></div>
	            </td>
			</tr>
		</table>
		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
var engines=<?php echo json_encode($engines); ?>,storageset = <?php echo json_encode($storageset); ?>;
/* ]]> */
</script>

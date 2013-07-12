<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<div class="icon32"></div>
	<h2><?php _e('Shopp Setup','Shopp'); ?></h2>

	<form name="settings" id="general" action="<?php echo $this->url; ?>" method="post">
		<?php wp_nonce_field('shopp-settings-activation'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="update-key"><?php _e('Support Key','Shopp'); ?></label></th>
				<td>
					<input type="<?php echo $type; ?>" name="updatekey" id="update-key" size="54" value="<?php echo esc_attr($key); ?>"<?php echo ($activated)?' readonly="readonly"':''; ?> />
					<span class="connecting"><input type="submit" id="activation-button" name="activation-button" class="button-secondary" value="<?php echo $button; ?>" /></span>
					<input type="hidden" name="activation" value="<?php echo $action; ?>" />
					<div id="activation-status" class="hide-if-js<?php echo " $status_class"; ?>"><?php echo $keystatus; ?></div>
	            </td>
			</tr>
		</table>
	</form>

	<form name="settings" id="general" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-general'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="base_operations"><?php _e('Base of Operations','Shopp'); ?></label></th>
				<td><select name="settings[base_operations][country]" id="base_operations">
					<option value="">&nbsp;</option>
						<?php echo Shopp::menuoptions($countries,$operations['country'],true); ?>
					</select>
					<select name="settings[base_operations][zone]" id="base_operations_zone"<?php if (!isset($zones)): ?>disabled="disabled" class="hide-if-no-js"<?php endif; ?>>
						<?php echo Shopp::menuoptions($zones,$operations['zone'],true); ?>
					</select>
					<br />
	            	<?php _e('Select your primary business location.','Shopp'); ?><br />
					<?php if (!empty($operations['country'])): ?>
		            <strong><?php _e('Currency','Shopp'); ?>: </strong><?php echo Shopp::money(1000.00); ?>
					<?php if (shopp_setting_enabled('tax_inclusive')): ?><strong>(+<?php _e('tax','Shopp'); ?>)</strong><?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="target_markets"><?php _e('Target Markets','Shopp'); ?></label></th>
				<td>
					<div id="target_markets" class="multiple-select">
						<ul>
							<?php
								$even = true;
								foreach ($targets as $iso => $country):
									$classes = array();
									if ($even) $classes[] = 'odd';
							?>
								<li<?php if (!empty($classes)) echo ' class="'.join(' ',$classes).'"'; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" checked="checked" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso,0,1); ?>"><?php echo $country; ?></label></li>
							<?php $even = !$even; endforeach; $classes = array(); ?>
							<li<?php if ($even) $classes[] = 'odd'; $classes[] = 'hide-if-no-js'; if (!empty($classes)) echo ' class="'.join(' ',$classes).'"'; $even = !$even; ?>><input type="checkbox" name="selectall_targetmarkets"  id="selectall_targetmarkets" /><label for="selectall_targetmarkets"><strong><?php _e('Select All','Shopp'); ?></strong></label></li>
							<?php
								foreach ($countries as $iso => $country):
									$classes = array();
									if ($even) $classes[] = 'odd';
								?>
							<?php if (!in_array($country,$targets)): ?>
							<li<?php if (!empty($classes)) echo ' class="'.join(' ',$classes).'"'; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso,0,1); ?>"><?php echo $country; ?></label></li>
							<?php $even = !$even; endif; endforeach; ?>
						</ul>
					</div>
					<br />
	            <?php _e('Select the markets you are selling products to.','Shopp'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="merchant_email"><?php _e('Merchant Email','Shopp'); ?></label></th>
				<td><input type="text" name="settings[merchant_email]" value="<?php echo esc_attr(shopp_setting('merchant_email')); ?>" id="merchant_email" size="30" /><br />
	            <?php _e('Enter the email address for the owner of this shop to receive e-mail notifications.','Shopp'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="business-name"><?php _e('Business Name','Shopp'); ?></label></th>
				<td><input type="text" name="settings[business_name]" value="<?php echo esc_attr(shopp_setting('business_name')); ?>" id="business-name" size="54" /><br />
	            <?php _e('Enter the legal name of your company or organization.','Shopp'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="business-address"><?php _e('Business Address','Shopp'); ?></label></th>
				<td><textarea name="settings[business_address]" id="business-address" cols="47" rows="4"><?php echo esc_attr(shopp_setting('business_address')); ?></textarea><br />
	            <?php _e('Enter the mailing address for your business.','Shopp'); ?></td>
			</tr>

		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
	var activated = <?php echo ($activated)?'true':'false'; ?>,
		zones_url = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_country_zones'); ?>',
		act_key_url = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_activate_key'); ?>',
		deact_key_url = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_deactivate_key'); ?>';
/* ]]> */
</script>
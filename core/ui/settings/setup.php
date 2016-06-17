<div class="wrap shopp">
	<?php if (!empty($updated)): ?><div id="message" class="updated fade"><p><?php echo $updated; ?></p></div><?php endif; ?>

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<form name="settings" id="general" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-setup'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="base_operations"><?php Shopp::_e('Base of Operations'); ?></label></th>
				<td><select name="settings[base_operations][country]" id="base_operations">
					<option value="">&nbsp;</option>
						<?php echo Shopp::menuoptions( $countries, $operations['country'], true ); ?>
					</select>
					<select name="settings[base_operations][zone]" id="base_operations_zone"<?php if ( ! isset($zones) ): ?>disabled="disabled" class="hide-if-no-js"<?php endif; ?>>
						<?php if ( isset($zones) ) echo Shopp::menuoptions( $zones, $operations['zone'], true ); ?>
					</select>
					<br />
	            	<?php Shopp::_e('Select your primary business location.'); ?><br />
					<?php if ( ! empty($operations['country']) ): ?>
		            <strong><?php Shopp::_e('Currency'); ?>: </strong><?php echo Shopp::money(1000.00); ?>
					<?php if (shopp_setting_enabled('tax_inclusive')): ?><strong>(+<?php echo strtolower(Shopp::__('Tax')); ?>)</strong><?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="target_markets"><?php Shopp::_e('Target Markets'); ?></label></th>
				<td>
					<div id="target_markets" class="multiple-select">
						<ul>
							<?php
								$even = true; ?>
							<li<?php if ($even) $classes[] = 'odd'; $classes[] = 'hide-if-no-js'; if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; $even = !$even; ?>><input type="checkbox" name="selectall_targetmarkets"  id="selectall_targetmarkets" /><label for="selectall_targetmarkets"><strong><?php Shopp::_e('Select All'); ?></strong></label></li>
							<?php foreach ($targets as $iso => $country):
									$classes = array();
									if ($even) $classes[] = 'odd';
							?>
								<li<?php if (!empty($classes)) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" checked="checked" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso,0,1); ?>"><?php echo $country; ?></label></li>
							<?php $even = !$even; endforeach; $classes = array(); ?>
							<?php
								foreach ($countries as $iso => $country):
									$classes = array();
									if ($even) $classes[] = 'odd';
								?>
							<?php if ( ! in_array($country, $targets) ): ?>
							<li<?php if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso,0,1); ?>"><?php echo $country; ?></label></li>
							<?php $even = !$even; endif; endforeach; ?>
						</ul>
					</div>
					<br />
	            <?php Shopp::_e('Select the markets you are selling products to.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="merchant_email"><?php Shopp::_e('Merchant Email'); ?></label></th>
				<td><input type="text" name="settings[merchant_email]" value="<?php echo esc_attr(shopp_setting('merchant_email')); ?>" id="merchant_email" size="30" /><br />
	            <?php Shopp::_e('Enter one or more comma separated email addresses at which the shop owner/staff should receive e-mail notifications.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="business-name"><?php Shopp::_e('Business Name'); ?></label></th>
				<td><input type="text" name="settings[business_name]" value="<?php echo esc_attr(shopp_setting('business_name')); ?>" id="business-name" size="54" /><br />
	            <?php Shopp::_e('Enter the legal name of your company or organization.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="business-address"><?php Shopp::_e('Business Address'); ?></label></th>
				<td><textarea name="settings[business_address]" id="business-address" cols="47" rows="4"><?php echo esc_attr(shopp_setting('business_address')); ?></textarea><br />
	            <?php Shopp::_e('Enter the mailing address for your business.'); ?></td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="maintenance-toggle"><?php Shopp::_e('Maintenance Mode'); ?></label></th>
				<td><input type="hidden" name="settings[maintenance]" value="off" /><input type="checkbox" name="settings[maintenance]" value="on" id="maintenance-toggle"<?php if ( shopp_setting_enabled('maintenance') ) echo ' checked="checked"'?> /><label for="maintenance-toggle"> <?php Shopp::_e('Enable maintenance mode'); ?></label><br />
	            <?php Shopp::_e('All storefront pages will display a maintenance mode message.'); ?></td>
			</tr>


		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
	var zones_url = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_country_zones'); ?>';
/* ]]> */
</script>

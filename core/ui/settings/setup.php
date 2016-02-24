<table class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="base_operations"><?php _e('Base of Operations', 'Shopp'); ?></label></th>
		<td><select name="settings[country]" id="base_operations">
			<option value="">&nbsp;</option>
				<?php echo $countrymenu; ?>
			</select>
			<select name="settings[state]" id="base_operations_zone"<?php if ( empty($statesmenu) ): ?>disabled="disabled" class="hide-if-no-js"<?php else: ?>  placeholder="<?php Shopp::_e('Select your %s&hellip;', strtolower(ShoppBaseLocale()->division())); ?>"<?php endif; ?>>
				<?php echo $statesmenu; ?>
			</select>
			<br />
        	<?php _e('Select your primary business location.', 'Shopp'); ?><br />
			<?php if ( ! empty($operations['country']) ): ?>
            <strong><?php _e('Currency', 'Shopp'); ?>: </strong><?php echo Shopp::money(1000.00); ?>
			<?php if ( shopp_setting_enabled('tax_inclusive') ): ?><strong>(+<?php echo strtolower(Shopp::__('Tax')); ?>)</strong><?php endif; ?>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="target_markets"><?php _e('Target Markets', 'Shopp'); ?></label></th>
		<td>
			<div id="target_markets" class="multiple-select">
				<ul>
					<?php
						$even = true; $classes = array(); ?>
					<li<?php if ( $even ) $classes[] = 'odd'; $classes[] = 'hide-if-no-js'; $classes[] = 'quick-select'; if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; $even = !$even; ?>><input type="checkbox" name="selectall_targetmarkets"  id="selectall_targetmarkets" /><label for="selectall_targetmarkets"><strong><?php _e('Select All', 'Shopp'); ?></strong></label></li>
					<?php foreach ($targets as $iso => $country):
							$classes = array();
							if ( $even ) $classes[] = 'odd';
					?>
						<li<?php if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" checked="checked" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso, 0, 1); ?>"><?php echo $country; ?></label></li>
					<?php $even = !$even; 
						endforeach; 
						foreach ($countries as $iso => $country):
							$classes = array();
							if ( $even ) $classes[] = 'odd';
						?>
					<?php if ( ! in_array($country, $targets) ): ?>
					<li<?php if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="settings[target_markets][<?php echo $iso; ?>]" value="<?php echo $country; ?>" id="market-<?php echo $iso; ?>" /><label for="market-<?php echo $iso; ?>" accesskey="<?php echo substr($iso, 0, 1); ?>"><?php echo $country; ?></label></li>
					<?php $even = !$even; 
							endif; 
						endforeach; ?>
				</ul>
			</div>
			<br />
        <?php _e('Select the markets you are selling products to.', 'Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="merchant_email"><?php _e('Merchant Email', 'Shopp'); ?></label></th>
		<td><input type="text" name="settings[merchant_email]" value="<?php echo esc_attr(shopp_setting('merchant_email')); ?>" id="merchant_email" size="30" /><br />
        <?php _e('Enter one or more comma separated email addresses at which the shop owner/staff should receive e-mail notifications.', 'Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="business-name"><?php _e('Business Name', 'Shopp'); ?></label></th>
		<td><input type="text" name="settings[business_name]" value="<?php echo esc_attr(shopp_setting('business_name')); ?>" id="business-name" size="54" /><br />
        <?php _e('Enter the legal name of your company or organization.', 'Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="business-address"><?php _e('Business Address', 'Shopp'); ?></label></th>
		<td><textarea name="settings[business_address]" id="business-address" cols="47" rows="4"><?php echo esc_attr(shopp_setting('business_address')); ?></textarea><br />
        <?php _e('Enter the mailing address for your business.', 'Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="maintenance-toggle"><?php _e('Maintenance Mode', 'Shopp'); ?></label></th>
		<td><input type="hidden" name="settings[maintenance]" value="off" /><input type="checkbox" name="settings[maintenance]" value="on" id="maintenance-toggle"<?php if ( shopp_setting_enabled('maintenance') ) echo ' checked="checked"'?> /><label for="maintenance-toggle"> <?php _e('Enable maintenance mode','Shopp'); ?></label><br />
        <?php _e('All storefront pages will display a maintenance mode message.', 'Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="dashboard-toggle"><?php _e('Dashboard Widgets', 'Shopp'); ?></label></th>
		<td><input type="hidden" name="settings[dashboard]" value="off" /><input type="checkbox" name="settings[dashboard]" value="on" id="dashboard-toggle"<?php if (shopp_setting('dashboard') == "on") echo ' checked="checked"'?> /><label for="dashboard-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
        <?php _e('Check this to display store performance metrics and more on the WordPress Dashboard.', 'Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="shopping-cart-toggle"><?php _e('Shopping Cart', 'Shopp'); ?></label></th>
		<td><input type="hidden" name="settings[shopping_cart]" value="off" /><input type="checkbox" name="settings[shopping_cart]" value="on" id="shopping-cart-toggle"<?php if (shopp_setting_enabled('shopping_cart')) echo ' checked="checked"'?> /><label for="shopping-cart-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
        <?php _e('Uncheck this to disable the shopping cart and checkout. Useful for catalog-only sites.', 'Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="shipping-toggle"><?php _e('Calculate Shipping', 'Shopp'); ?></label></th>
		<td><input type="hidden" name="settings[shipping]" value="off" /><input type="checkbox" name="settings[shipping]" value="on" id="shipping-toggle"<?php if ( shopp_setting_enabled('shipping') ) echo ' checked="checked"'?> /><label for="shipping-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
        <?php _e('Enables shipping cost calculations. Disable if you are exclusively selling intangible products.', 'Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="taxes-toggle"><?php _e('Calculate Taxes', 'Shopp'); ?></label></th>
		<td><input type="hidden" name="settings[taxes]" value="off" /><input type="checkbox" name="settings[taxes]" value="on" id="taxes-toggle"<?php if (shopp_setting('taxes') == "on") echo ' checked="checked"'; ?> /><label for="taxes-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
        <?php _e('Enables tax calculations.  Disable if you are exclusively selling non-taxable items.', 'Shopp'); ?></td>
	</tr>

	<tr>
		<th scope="row" valign="top"><label for="shipping-toggle"><?php _e('Track Inventory', 'Shopp'); ?></label></th>
		<td><input type="hidden" name="settings[inventory]" value="off" /><input type="checkbox" name="settings[inventory]" value="on" id="inventory-toggle"<?php if ( shopp_setting_enabled('inventory') ) echo ' checked="checked"'?> /><label for="inventory-toggle"> <?php _e('Enable inventory tracking','Shopp'); ?></label><br />
        <?php _e('Enables inventory tracking. Disable if you are exclusively selling intangible products or not keeping track of product stock.', 'Shopp'); ?></td>
	</tr>
</table>

<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes', 'Shopp'); ?>" /></p>

<script type="text/javascript">
/* <![CDATA[ */
	var zones_url = '<?php echo $zones_ajaxurl; ?>';
/* ]]> */
</script>
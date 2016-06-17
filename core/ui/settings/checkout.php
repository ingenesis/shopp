<div class="wrap shopp">
	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<form name="settings" id="checkout" action="<?php echo esc_url($this->url); ?>"  method="post">
		<?php wp_nonce_field('shopp-settings-checkout'); ?>

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
					<input type="radio" name="settings[receipt_copy]" value="1" id="receipt_copy_both"<?php if (shopp_setting('receipt_copy') == "1") echo ' checked="checked"'; ?> /> <label for="receipt_copy_both"><?php _e('Send to Customer &amp; Merchant Email','Shopp'); ?></label> (<?php _e('see','Shopp'); ?> <a href="?page=shopp-setup"><?php _e('Shopp Setup','Shopp'); ?></a>)</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="account-system-none"><?php _e('Customer Accounts','Shopp'); ?></label></th>
				<td><input type="radio" name="settings[account_system]" value="none" id="account-system-none"<?php if(shopp_setting('account_system') == "none") echo ' checked="checked"' ?> /> <label for="account-system-none"><?php _e('No Accounts','Shopp'); ?></label><br />
					<input type="radio" name="settings[account_system]" value="shopp" id="account-system-shopp"<?php if(shopp_setting('account_system') == "shopp") echo ' checked="checked"' ?> /> <label for="account-system-shopp"><?php _e('Enable Account Logins','Shopp'); ?></label><br />
					<input type="radio" name="settings[account_system]" value="wordpress" id="account-system-wp"<?php if(shopp_setting('account_system') == "wordpress") echo ' checked="checked"' ?> /> <label for="account-system-wp"><?php _e('Enable Account Logins integrated with WordPress Users','Shopp'); ?></label></td>
			</tr>

			<tr>
				<th scope="row" valign="top"><label for="promo-limit"><?php _e('Discount Limit','Shopp'); ?></label></th>
				<td><select name="settings[promo_limit]" id="promo-limit">
					<option value="">&infin;</option>
					<?php echo menuoptions($promolimit,shopp_setting('promo_limit')); ?>
					</select>
					<label> <?php _e('per order','Shopp'); ?></label>
				</td>
			</tr>

		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

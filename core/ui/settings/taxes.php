<div class="wrap shopp">
	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>


	<?php $this->taxes_menu(); ?>

	<form name="settings" id="taxes" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-taxes'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="taxes-toggle"><?php _e('Calculate Taxes','Shopp'); ?></label></th>
				<td><input type="hidden" name="settings[taxes]" value="off" /><input type="checkbox" name="settings[taxes]" value="on" id="taxes-toggle"<?php if (shopp_setting('taxes') == "on") echo ' checked="checked"'; ?> /><label for="taxes-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
	            <?php _e('Enables tax calculations.  Disable if you are exclusively selling non-taxable items.','Shopp'); ?></td>
			</tr>
			<tr>
					<th scope="row" valign="top"><label for="inclusive-tax-toggle"><?php _e('Inclusive Taxes','Shopp'); ?></label></th>
					<td><input type="hidden" name="settings[tax_inclusive]" value="off" /><input type="checkbox" name="settings[tax_inclusive]" value="on" id="inclusive-tax-toggle"<?php if (shopp_setting('tax_inclusive') == "on") echo ' checked="checked"'; ?> /><label for="inclusive-tax-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
		            <?php _e('Enable to include taxes in the price of goods.','Shopp'); ?></td>
			</tr>
			<tr>
					<th scope="row" valign="top"><label for="tax-shipping-toggle"><?php _e('Tax Shipping','Shopp'); ?></label></th>
					<td><input type="hidden" name="settings[tax_shipping]" value="off" /><input type="checkbox" name="settings[tax_shipping]" value="on" id="tax-shipping-toggle"<?php if (shopp_setting('tax_shipping') == "on") echo ' checked="checked"'; ?> /><label for="tax-shipping-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
		            <?php _e('Enable to include shipping and handling in taxes.','Shopp'); ?></td>
			</tr>
			<?php do_action('shopp_taxes_settings_table'); ?>
		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
/* ]]> */
</script>

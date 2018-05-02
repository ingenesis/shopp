<script id="delivery-menu" type="text/x-jquery-tmpl">
<?php echo Shopp::menuoptions(ShoppLookup::timeframes_menu(), false, true); ?>
</script>

<?php $Table->display(); ?>

<table class="form-table">

	<tr>
		<th scope="row" valign="top"><label><?php _e('Shipping Carriers','Shopp'); ?></label></th>
		<td>
		<div id="carriers" class="multiple-select">
			<ul>
				<li<?php $even = true;
				$classes[] = 'odd hide-if-no-js'; if (!empty($classes)) echo ' class="'.join(' ',$classes).'"'; $even = !$even; ?>><input type="checkbox" name="selectall"  id="selectall" /><label for="selectall"><strong><?php _e('Select All','Shopp'); ?></strong></label><input type="hidden" name="settings[shipping_carriers]" value="off" /></li>
				<?php
					foreach ($carriers as $code => $carrier):
						$classes = array();
						if ($even) $classes[] = 'odd';
				?>
					<li<?php if (!empty($classes)) echo ' class="'.join(' ',$classes).'"'; ?>><input type="checkbox" name="settings[shipping_carriers][]" value="<?php echo $code; ?>" id="carrier-<?php echo $code; ?>"<?php if (in_array($code,$shipping_carriers)) echo ' checked="checked"'; ?> /><label for="carrier-<?php echo $code; ?>" accesskey="<?php echo substr($code,0,1); ?>"><?php echo $carrier; ?></label></li>
				<?php $even = !$even; endforeach; ?>
			</ul>
		</div><br />
		<label><?php _e('Select the shipping carriers you will be using for shipment tracking.','Shopp'); ?></label>
		</td>
	</tr>
	<?php $Shopp = Shopp::object(); if ($Shopp->Shipping->realtime): ?>
	<tr>
		<th scope="row" valign="top"><label for="packaging"><?php _e('Packaging','Shopp'); ?></label></th>
		<td>
		<select name="settings[shipping_packaging]" id="packaging">
				<?php echo Shopp::menuoptions(Lookup::packaging_types(), shopp_setting('shipping_packaging'),true); ?>
		</select><br />
		<?php _e('Determines packaging method used for real-time shipping quotes.','Shopp'); ?></td>
	</tr>
	<tr>
		<th scope="row" valign="top"><label for="packaging"><?php _e('Package Limit','Shopp'); ?></label></th>
		<td>
		<select name="settings[shipping_package_weight_limit]" id="packaging_weight_limit">
				<?php echo Shopp::menuoptions(apply_filters('shopp_package_weight_limits', array('-1'=>'∞','10'=>10,'20'=>20,'30'=>30,'40'=>40,'50'=>50,'60'=>60,'70'=>70,'80'=>80,'90'=>90,'100'=>100,'150'=>150,'200'=>200,'250'=>250,'300'=>300,'350'=>350,'400'=>400,'450'=>450,'500'=>500,'550'=>550,'600'=>600,'650'=>650,'700'=>700,'750'=>750,'800'=>800)),
						shopp_setting('shipping_package_weight_limit'),true); ?>
		</select><br />
		<?php _e('The maximum weight allowed for a package.','Shopp'); ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th scope="row" valign="top"><label for="weight-unit"><?php _e('Units','Shopp'); ?></label></th>
		<td>
		<select name="settings[weight_unit]" id="weight-unit">
				<?php echo $weightsmenu; ?>
		</select>
		<select name="settings[dimension_unit]" id="dimension-unit">
				<?php echo $dimsmenu; ?>
		</select><br />
		<?php _e('Standard weight &amp; dimension units used for all products.','Shopp'); ?></td>
	</tr>

</table>

<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>

<?php do_action('shopp_shipping_module_settings'); ?>


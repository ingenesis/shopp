<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>


	<?php $this->shipping_menu(); ?>

	<form name="settings" id="shipping" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-settings-shipping'); ?>

		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="shipping-toggle"><?php Shopp::_e('Calculate Shipping'); ?></label></th>
				<td><input type="hidden" name="settings[shipping]" value="off" /><input type="checkbox" name="settings[shipping]" value="on" id="shipping-toggle"<?php if ( shopp_setting_enabled('shipping') ) echo ' checked="checked"'?> /><label for="shipping-toggle"> <?php Shopp::_e('Enabled'); ?></label><br />
	            <?php Shopp::_e('Enables shipping cost calculations. Disable if you are exclusively selling intangible products.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="shipping-toggle"><?php Shopp::_e('Track Inventory'); ?></label></th>
				<td><p><input type="hidden" name="settings[inventory]" value="off" /><input type="checkbox" name="settings[inventory]" value="on" id="inventory-toggle"<?php if ( shopp_setting_enabled('inventory') ) echo ' checked="checked"'?> /><label for="inventory-toggle"> <?php Shopp::_e('Enable inventory tracking'); ?></label><br />
	            <?php Shopp::_e('Enables inventory tracking. Disable if you are exclusively selling intangible products or not keeping track of product stock.'); ?></p>


				<input type="hidden" name="settings[backorders]" value="off" /><input type="checkbox" name="settings[backorders]" value="on" id="backorders-toggle"<?php if ( shopp_setting_enabled('backorders') ) echo ' checked="checked"'?> /><label for="backorders-toggle"> <?php Shopp::_e('Allow backorders'); ?></label><br />
				<?php Shopp::_e('Allows customers to order products that cannot be fulfilled because of a lack of available product in-stock. Disable to prevent customers from ordering more product than is available in-stock.'); ?>
			</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label><?php Shopp::_e('Shipping Carriers'); ?></label></th>
				<td>
				<div id="carriers" class="multiple-select">
					<ul>
						<li<?php $even = true;
						$classes[] = 'odd hide-if-no-js'; if (!empty($classes)) echo ' class="'.join(' ',$classes).'"'; $even = !$even; ?>><input type="checkbox" name="selectall"  id="selectall" /><label for="selectall"><strong><?php Shopp::_e('Select All'); ?></strong></label><input type="hidden" name="settings[shipping_carriers]" value="off" /></li>
						<?php
							foreach ($carriers as $code => $carrier):
								$classes = array();
								if ($even) $classes[] = 'odd';
						?>
							<li<?php if ( ! empty($classes) ) echo ' class="' . join(' ', $classes) . '"'; ?>><input type="checkbox" name="settings[shipping_carriers][]" value="<?php echo $code; ?>" id="carrier-<?php echo $code; ?>"<?php if (in_array($code, $shipping_carriers)) echo ' checked="checked"'; ?> /><label for="carrier-<?php echo $code; ?>" accesskey="<?php echo substr($code, 0, 1); ?>"><?php echo $carrier; ?></label></li>
						<?php $even = !$even; endforeach; ?>
					</ul>
				</div><br />
				<label><?php Shopp::_e('Select the shipping carriers you will be using for shipment tracking.'); ?></label>
				</td>
			</tr>
			<?php $Shopp = Shopp::object(); if ( $Shopp->Shipping->realtime ): ?>
			<tr>
				<th scope="row" valign="top"><label for="packaging"><?php Shopp::_e('Packaging'); ?></label></th>
				<td>
				<select name="settings[shipping_packaging]" id="packaging">
						<?php echo menuoptions(Lookup::packaging_types(), shopp_setting('shipping_packaging'), true); ?>
				</select><br />
				<?php Shopp::_e('Determines packaging method used for real-time shipping quotes.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="packaging"><?php Shopp::_e('Package Limit'); ?></label></th>
				<td>
				<select name="settings[shipping_package_weight_limit]" id="packaging_weight_limit">
						<?php echo menuoptions(apply_filters('shopp_package_weight_limits', array('-1' => '∞', '10' => 10, '20' => 20, '30' => 30, '40' => 40, '50' => 50, '60' => 60, '70' => 70, '80' => 80, '90' => 90, '100' => 100, '150' => 150, '200' => 200, '250' => 250, '300' => 300, '350' => 350, '400' => 400, '450' => 450, '500' => 500, '550' => 550, '600' => 600, '650' => 650, '700' => 700, '750' => 750, '800' => 800)),
								shopp_setting('shipping_package_weight_limit'),true); ?>
				</select><br />
				<?php Shopp::_e('The maximum weight allowed for a package.'); ?></td>
			</tr>
			<?php endif; ?>
			<tr>
				<th scope="row" valign="top"><label for="weight-unit"><?php Shopp::_e('Units'); ?></label></th>
				<td>
				<select name="settings[weight_unit]" id="weight-unit">
						<?php
							if ($base['units'] == "imperial") $units = array("oz" => Shopp::__("ounces (oz)"), "lb" => Shopp::__("pounds (lbs)"));
							else $units = array("g" => Shopp::__("gram (g)"), "kg" => Shopp::__("kilogram (kg)"));
							echo menuoptions($units,shopp_setting('weight_unit'), true);
						?>
				</select>
				<select name="settings[dimension_unit]" id="dimension-unit">
						<?php
							if ($base['units'] == "imperial") $units = array("in" => Shopp::__("inches (in)"), "ft" => Shopp::__("feet (ft)"));
							else $units = array("cm" => Shopp::__("centimeters (cm)"), "m" => Shopp::__("meters (m)"));
							echo menuoptions($units,shopp_setting('dimension_unit'), true);
						?>
				</select><br />
				<?php Shopp::_e('Standard weight &amp; dimension units used for all products.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="order-processing-min"><?php Shopp::_e('Order Processing'); ?></label></th>
				<td>
				<select name="settings[order_processing_min]" id="order-processing">
						<?php echo menuoptions(Lookup::timeframes_menu(), shopp_setting('order_processing_min'), true); ?>
				</select> &mdash; <select name="settings[order_processing_max]" id="order-processing">
							<?php echo menuoptions(Lookup::timeframes_menu(), shopp_setting('order_processing_max'), true); ?>
				</select><br />
				<?php Shopp::_e('Set the estimated time range for processing orders for shipment.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="lowstock-level"><?php Shopp::_e('Low Inventory'); ?></label></th>
				<td>
					<?php
						$values = array_reverse(array_merge(range(0, 25), range(30, 50, 5), range(60, 100, 10)));
						$labels = $values;
						array_walk( $labels, function( &$val ) {
							$val = "$val%";
                        } );
						$levels = array_combine($values, $labels);
					?>
					<select name="settings[lowstock_level]" id="lowstock-level">
					<?php echo menuoptions($levels, $lowstock, true); ?>
					</select><br />
	            	<?php Shopp::_e('Select the level for low stock warnings.'); ?>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="order_handling_fee"><?php Shopp::_e('Order Handling Fee'); ?></label></th>
				<td><input type="text" name="settings[order_shipfee]" value="<?php echo money(shopp_setting('order_shipfee')); ?>" id="order_handling_fee" size="7" class="right selectall money" /><br />
	            <?php Shopp::_e('Handling fee applied once to each order with shipped products.'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="free_shipping_text"><?php Shopp::_e('Free Shipping Text'); ?></label></th>
				<td><input type="text" name="settings[free_shipping_text]" value="<?php echo esc_attr(shopp_setting('free_shipping_text')); ?>" id="free_shipping_text" /><br />
	            <?php Shopp::_e('Text used to highlight no shipping costs (examples: Free shipping! or Shipping Included)'); ?></td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="outofstock-text"><?php Shopp::_e('Out-of-stock Notice'); ?></label></th>
				<td><input type="text" name="settings[outofstock_text]" value="<?php echo esc_attr(shopp_setting('outofstock_text')); ?>" id="outofstock-text" /><br />
	            <?php Shopp::_e('Text used to notify the customer the product is out-of-stock or on backorder.'); ?></td>
			</tr>
		</table>

		<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes'); ?>" /></p>
	</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
jQuery(document).ready(function($) {
	quickSelects();
	$('#selectall').change(function () {
		if ($(this).attr('checked')) $('#carriers input').not(this).attr('checked',true);
		else $('#carriers input').not(this).attr('checked',false);
	});

});
/* ]]> */
</script>

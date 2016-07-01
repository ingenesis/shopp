<?php
	if ( count(shopp_setting('target_markets')) == 0 )
		echo '<div class="error"><p>' . Shopp::__('No target markets have been selected in your store setup.') . '</p></div>';
?>

<script id="property-menu" type="text/x-jquery-tmpl"><?php
	$propertymenu = array(
		'product-name'     => Shopp::__('Product name is'),
		'product-tags'     => Shopp::__('Product is tagged'),
		'product-category' => Shopp::__('Product in category'),
		'customer-type'    => Shopp::__('Customer type is')
	);
	echo Shopp::menuoptions($propertymenu, false,true);
?></script>

	<script id="countries-menu" type="text/x-jquery-tmpl"><?php
		echo Shopp::menuoptions($Table->countries, false, true);
	?></script>

<script id="conditional" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<li>
	<?php echo ShoppUI::button('delete','deleterule'); ?>
	<select name="settings[taxrates][${id}][rules][${ruleid}][p]" class="property">${property_menu}</select>&nbsp;<input type="text" name="settings[taxrates][${id}][rules][${ruleid}][v]" size="25" class="value" value="${rulevalue}" />
	<?php echo ShoppUI::button('add','addrule'); ?></li>
<?php
	$conditional = ob_get_clean();
	echo $Table->template_conditional($conditional);
 ?>
</script>

<script id="localrate" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<li><label title="${localename}"><input type="text" name="settings[taxrates][${id}][locals][${localename}]" size="6" value="${localerate}" />&nbsp;${localename}</label></li>
<?php
	$localrateui = ob_get_clean();
	echo $Table->template_localrate($localrateui);
?>
</script>

<script id="editor" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<tr class="inline-edit-row ${classnames}" id="${id}">
	<td colspan="3"><input type="hidden" name="id" value="${id}" /><input type="hidden" name="editing" value="true" />
	<table id="taxrate-editor">
		<tr>
		<td scope="row" valign="top" class="rate"><input type="text" name="settings[taxrates][${id}][rate]" id="tax-rate" value="${rate}" size="7" class="selectall" tabindex="1" /><br /><label for="tax-rate"><?php _e('Tax Rate','Shopp'); ?></label><br />
		<input type="hidden" name="settings[taxrates][${id}][compound]" value="off" /><label><input type="checkbox" id="tax-compound" name="settings[taxrates][${id}][compound]" value="on" ${compounded} tabindex="4" />&nbsp;<?php Shopp::_e('Compound'); ?></label></td>
		<td scope="row" class="conditions">
		<select name="settings[taxrates][${id}][country]" class="country" tabindex="2">${countries}</select><select name="settings[taxrates][${id}][zone]" class="zone no-zones" tabindex="3">${zones}</select>
		<?php echo ShoppUI::button('add','addrule'); ?>
		<?php
			$options = array('any' => Shopp::__('any'), 'all' => strtolower(Shopp::__('All')));
			$menu = '<select name="settings[taxrates][${id}][logic]" class="logic">'.menuoptions($options,false,true).'</select>';
		?>
			<div class="conditionals no-conditions">
				<p><label><?php printf(__('Apply tax rate when %s of the following conditions match','Shopp'),$menu); ?>:</label></p>
				<ul>
				${conditions}
				</ul>
			</div>
		</td>
			<td>
				<div class="local-rates panel subpanel no-local-rates">
					<div class="label"><label><?php _e('Local Rates','Shopp'); echo ShoppAdminMetabox::help('settings-taxes-localrates'); ?> <span class="counter"></span><input type="hidden" name="settings[taxrates][${id}][haslocals]" value="${haslocals}" class="has-locals" /></label></div>
					<div class="ui">
						<p class="instructions"><?php Shopp::_e('No local regions have been setup for this location. Local regions can be specified by uploading a formatted local rates file.'); ?></p>
						${errors}
						<ul>${localrates}</ul>
						<div class="upload">
							<h3><?php Shopp::_e('Upload Local Tax Rates'); ?></h3>
							<input type="hidden" name="MAX_FILE_SIZE" value="1048576" />
							<input type="file" name="ratefile" class="hide-if-js" />
							<button type="submit" name="upload" class="button-secondary upload"><?php Shopp::_e('Upload'); ?></button>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="3">
			<p class="textright">
			<a href="${cancel_href}" class="button-secondary cancel alignleft"><?php Shopp::_e('Cancel'); ?></a>
			<button type="submit" name="add-locals" class="button-secondary locals-toggle add-locals has-local-rates"><?php Shopp::_e('Add Local Rates'); ?></button>
			<button type="submit" name="remove-locals" class="button-secondary locals-toggle rm-locals no-local-rates"><?php Shopp::_e('Remove Local Rates'); ?></button>
			<input type="submit" class="button-primary" name="submit" value="<?php Shopp::_e('Save Changes'); ?>" />
			</p>
			</td>
		</tr>
	</table>
	</td>
</tr>
<?php
	$editor = ob_get_clean();
	echo $Table->editorui($editor);
?>
</script>


<?php $Table->display(); ?>

<table class="form-table">
	<tr>
			<th scope="row" valign="top"><label for="inclusive-tax-toggle"><?php _e('Inclusive Taxes','Shopp'); ?></label></th>
			<td><input type="hidden" name="settings[tax_inclusive]" value="off" /><input type="checkbox" name="settings[tax_inclusive]" value="on" id="inclusive-tax-toggle"<?php if (shopp_setting('tax_inclusive') == "on") echo ' checked="checked"'; ?> /><label for="inclusive-tax-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
            <?php _e('Enable to include taxes in the price of goods.','Shopp'); ?></td>
	</tr>
	<tr>
			<th scope="row" valign="top"><label for="tax-shipping-toggle"><?php _e('Tax Shipping','Shopp'); ?></label></th>
			<td><input type="hidden" name="settings[tax_shipping]" value="off" /><input type="checkbox" name="settings[tax_shipping]" value="on" id="tax-shipping-toggle"<?php if (shopp_setting('tax_shipping') == "on") echo ' checked="checked"'; ?> /><label for="tax-shipping-toggle"> <?php _e('Enabled','Shopp'); ?></label><br />
            <?php _e('Enable to calculate tax for shipping and handling fees.','Shopp'); ?></td>
	</tr>
	<?php do_action('shopp_taxes_settings_table'); ?>
</table>

<p class="submit"><input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" /></p>
<p><input type="hidden" name="featured" value="off" /><input type="checkbox" name="featured" value="on" id="featured" tabindex="12" <?php if ($Product->featured == "on") echo ' checked="checked"'?> /><label for="featured"> <?php _e('Featured Product','Shopp'); ?></label></p>
<p><input type="hidden" name="variants" value="off" /><input type="checkbox" name="variants" value="on" id="variations-setting" tabindex="13"<?php if ($Product->variants == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php _e('Variants','Shopp'); ?><?php echo ShoppAdminMetabox::help('product-editor-variations'); ?></label></p>
<p><input type="hidden" name="addons" value="off" /><input type="checkbox" name="addons" value="on" id="addons-setting" tabindex="13"<?php if ($Product->addons == "on") echo ' checked="checked"'?> /><label for="addons-setting"> <?php _e('Add-ons','Shopp'); ?><?php echo ShoppAdminMetabox::help('product-editor-addons'); ?></label></p>

<?php if (shopp_setting_enabled('tax_inclusive')): ?>
	<p><input type="hidden" name="meta[excludetax]" value="off" /><input type="checkbox" name="meta[excludetax]" value="on" id="excludetax-setting" tabindex="18"  <?php if(isset($Product->meta['excludetax']) && Shopp::str_true($Product->meta['excludetax']->value)) echo 'checked="checked"'; ?> /> <label for="excludetax-setting"><?php _e('Exclude Taxes','Shopp'); ?></label></p>
<?php endif; ?>

<?php if ($shiprealtime): ?>
<p><input type="hidden" name="meta[packaging]" value="off" /><input type="checkbox" name="meta[packaging]" value="on" id="packaging-setting" tabindex="18"  <?php if(isset($Product->meta['packaging']) && $Product->meta['packaging']->value == "on") echo 'checked="checked"'; ?> /> <label for="packaging-setting"><?php _e('Separate Packaging','Shopp'); ?></label></p>
<?php endif; ?>


<p><input type="hidden" name="comment_status" value="closed" /><input type="checkbox" name="comment_status" value="open" id="allow-comments" tabindex="18"  <?php if(Shopp::str_true($Product->comment_status)) echo 'checked="checked"'; ?> /> <label for="allow-comments"><?php _e('Comments','Shopp'); ?></label>

<p><input type="hidden" name="ping_status" value="closed" /><input type="checkbox" name="ping_status" value="open" id="allow-trackpings" tabindex="18"  <?php if(Shopp::str_true($Product->ping_status)) echo 'checked="checked"'; ?> /> <label for="allow-trackpings"><?php _e('Trackbacks & Pingbacks','Shopp'); ?></label>

<p><input type="hidden" name="meta[processing]" value="off" /><input type="checkbox" name="meta[processing]" value="on" id="process-time" tabindex="18"  <?php if(isset($Product->meta['processing']) && Shopp::str_true($Product->meta['processing']->value)) echo 'checked="checked"'; ?> /> <label for="process-time"><?php _e('Processing Time','Shopp'); ?></label>

<div id="processing" class="hide-if-js">
	<select name="meta[minprocess]"><?php echo menuoptions(Lookup::timeframes_menu(),isset($Product->meta['minprocess'])?$Product->meta['minprocess']->value:false,true); ?></select> &mdash;
	<select name="meta[maxprocess]"><?php echo menuoptions(Lookup::timeframes_menu(),isset($Product->meta['maxprocess'])?$Product->meta['maxprocess']->value:false,true); ?></select>
</div>

</p>
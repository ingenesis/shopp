<?php shopp_admin_screen_tabs(); ?>
<script id="editor" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<tr class="inline-edit-row ${classnames}" id="edit-boxes-setting-${id}">
	<td colspan="2">
	<input type="hidden" name="id" value="${id}" /><label><input type="text" name="name" value="${name}" /><br /><?php Shopp::_e('Name'); ?></label>
	<p class="submit">
	<a href="<?php echo $this->url(); ?>" class="button-secondary cancel"><?php Shopp::_e('Cancel'); ?></a>
	</p>
	</td>
	<td class="dimensions column-dimensions">
	<span><label><input type="text" name="width" value="${width}" size="4" class="selectall" /> &times;<br /><?php Shopp::_e('Width'); ?></label></span>
	<span><label><input type="text" name="height" value="${height}" size="4" class="selectall" /><br /><?php Shopp::_e('Height'); ?></label></span>
	</td>
	<td class="fit column-fit">
	<label>
	<select name="fit" class="fit-menu">
	<?php foreach ( $fit_menu as $index => $option ): ?>
	<option value="<?php echo $index; ?>"${select_fit_<?php echo $index; ?>}><?php echo $option; ?></option>
	<?php endforeach; ?>
	</select><br /><?php Shopp::_e('Fit'); ?></label>
	</td>
	<td class="quality column-quality">
	<label><select name="quality" class="quality-menu">
	<?php foreach ( $quality_menu as $index => $option ): ?>
	<option value="<?php echo $index; ?>"${select_quality_<?php echo $index; ?>}><?php echo $option; ?></option>
	<?php endforeach; ?>
	</select><br /><?php Shopp::_e('Quality'); ?></label>
	</td>
	<td class="sharpen column-sharpen">
	<label><input type="text" name="sharpen" value="${sharpen}" size="5" class="percentage selectall" /><br /><?php Shopp::_e('Sharpen'); ?></label>
	<p class="submit">
	<input type="submit" class="button-primary" name="save" value="<?php Shopp::_e('Save Changes'); ?>" />
	</p>
	</td>
</tr>
<?php
	$editor = ob_get_clean();
	echo $Table->editorui($editor);
?>
</script>

<?php $Table->display(); ?>

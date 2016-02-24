<script id="editor" type="text/x-jquery-tmpl">
<?php ob_start(); ?>
<tr class="inline-edit-row ${classnames}" id="${id}">
	<td>
	<label><input type="text" name="settings[storefront_pages][${name}][title]" value="${title}" /><br /><?php _e('Title','Shopp'); ?></label>
	<p class="submit">
	<a href="<?php echo $this->url(); ?>" class="button-secondary cancel"><?php _e('Cancel','Shopp'); ?></a>
	</p>
	</td>
	<td class="slug column-slug">
	<label><input type="text" name="settings[storefront_pages][${name}][slug]" value="${slug}" /><br /><?php _e('Slug','Shopp'); ?></label>
	<p class="submit">
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" />
	</p>
	</td>
	<td class="description column-description">
	${description}
	</td>
</tr>
<?php
	$editor = ob_get_clean();
	echo $Table->editorui($editor);
?>
</script>

<?php $Table->display(); ?>
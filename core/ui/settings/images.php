<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<!-- <h2><?php _e('Image Settings','Shopp'); ?> <a href="<?php echo esc_url( add_query_arg(array('page'=>$this->Admin->pagename('settings-images'),'id'=>'new'),admin_url('admin.php'))); ?>" class="button add-new"><?php _e('Add New','Shopp'); ?></a></h2> -->

	<form action="<?php echo esc_url(wp_nonce_url($this->url,'shopp-settings-images')); ?>" id="images" method="post">
	<div>
		<?php wp_nonce_field('shopp-settings-images'); ?>
	</div>

	<br class="clear" />

	<script id="editor" type="text/x-jquery-tmpl">
	<?php ob_start(); ?>
	<tr class="inline-edit-row ${classnames}" id="edit-image-setting-${id}">
		<td colspan="2">
		<input type="hidden" name="id" value="${id}" /><label><input type="text" name="name" value="${name}" /><br /><?php _e('Name','Shopp'); ?></label>
		<p class="submit">
		<a href="<?php echo $this->url; ?>" class="button-secondary cancel"><?php _e('Cancel','Shopp'); ?></a>
		</p>
		</td>
		<td class="dimensions column-dimensions">
		<span><label><input type="text" name="width" value="${width}" size="4" class="selectall" /> &times;<br /><?php _e('Width','Shopp'); ?></label></span>
		<span><label><input type="text" name="height" value="${height}" size="4" class="selectall" /><br /><?php _e('Height','Shopp'); ?></label></span>
		</td>
		<td class="fit column-fit">
		<label>
		<select name="fit" class="fit-menu">
		<?php foreach ($fit_menu as $index => $option): ?>
		<option value="<?php echo $index; ?>"${select_fit_<?php echo $index; ?>}><?php echo $option; ?></option>
		<?php endforeach; ?>
		</select><br /><?php _e('Fit','Shopp'); ?></label>
		</td>
		<td class="quality column-quality">
		<label><select name="quality" class="quality-menu">
		<?php foreach ($quality_menu as $index => $option): ?>
		<option value="<?php echo $index; ?>"${select_quality_<?php echo $index; ?>}><?php echo $option; ?></option>
		<?php endforeach; ?>
		</select><br /><?php _e('Quality','Shopp'); ?></label>
		</td>
		<td class="sharpen column-sharpen">
		<label><input type="text" name="sharpen" value="${sharpen}" size="5" class="percentage selectall" /><br /><?php _e('Sharpen','Shopp'); ?></label>
		<p class="submit">
		<input type="submit" class="button-primary" name="save" value="<?php _e('Save Changes','Shopp'); ?>" />
		</p>
		</td>
	</tr>
	<?php $editor = ob_get_contents(); ob_end_clean(); echo $editor; ?>
	</script>

	<div class="tablenav top">

		<div class="alignleft actions">
		<select name="action" id="actions">
			<option value="" selected="selected"><?php _e('Bulk Actions&hellip;','Shopp'); ?></option>
			<?php echo menuoptions($actions_menu,false,true); ?>
		</select>
		<input type="submit" value="<?php esc_attr_e('Apply','Shopp'); ?>" name="apply" id="apply" class="button-secondary action" />
		</div>

		<div class="alignleft actions">
			<a href="<?php echo esc_url(add_query_arg('id', 'new', $this->url)); ?>" class="button"><?php _e('Add New','Shopp'); ?></a>
		</div>

		<?php $ListTable->pagination('top'); ?>

		<br class="clear" />
	</div>
	<div class="clear"></div>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php ShoppUI::print_column_headers('shopp_page_shopp-settings-images'); ?></tr>
		</thead>
		<tfoot>
		<tr><?php ShoppUI::print_column_headers('shopp_page_shopp-settings-images',false); ?></tr>
		</tfoot>
	<?php if (count($settings) > 0 || 'new' == $edit): ?>
		<tbody id="image-setting-table" class="list">
		<?php
			$hidden = get_hidden_columns('shopp_page_shopp-settings-pages');

			$even = false;

			if ('new' == $edit) {
				$editor = preg_replace('/\${\w+}/','',$editor);
				echo str_replace(array_keys($template_data),$template_data,$editor);
			}

			foreach ($settings as $setting):
				$editurl = wp_nonce_url(add_query_arg(array('id'=>$setting->id),$this->url),'shopp-settings-images');
				$deleteurl = wp_nonce_url(add_query_arg(array('delete'=>$setting->id),$this->url),'shopp-settings-images');

				$classes = array();
				if (!$even) $classes[] = 'alternate'; $even = !$even;



				if (isset(ImageSetting::$qualities[ $setting->quality ])) {
					$quality = ImageSetting::$qualities[ $setting->quality ];
				} else $quality = $setting->quality;

				$quality = percentage($quality,array('precision' => 0));

				if ($edit == $setting->id) {
					$template_data = array(
						'${id}' => $setting->id,
						'${name}' => $setting->name,
						'${width}' => $setting->width,
						'${height}' => $setting->height,
						'${sharpen}' => $setting->sharpen,
						'${select_fit_'.$setting->fit.'}' => ' selected="selected"',
						'${select_quality_'.$quality.'}' => ' selected="selected"'
					);

					$editor = str_replace(array_keys($template_data),$template_data,$editor);
					$editor = preg_replace('/\${\w+}/','',$editor);
					echo $editor;
					continue;
				}

			?>
		<tr class="<?php echo join(' ',$classes); ?>" id="image-setting-<?php echo $setting->id; ?>">
			<th scope='row' class='check-column'><input type='checkbox' name='selected[]' value='<?php echo $setting->id; ?>' /></th>
			<td class="title column-title"><a class="row-title edit" href="<?php echo $editurl; ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($setting->name); ?>&quot;"><?php echo esc_html($setting->name); ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($setting->name); ?>&quot;" class="edit"><?php _e('Edit','Shopp'); ?></a> | </span><span class='delete'><a href="<?php echo esc_url($deleteurl); ?>" title="<?php _e('Delete','Shopp'); ?> &quot;<?php echo esc_attr($setting->name); ?>&quot;" class="delete"><?php _e('Delete','Shopp'); ?></a></span>
				</div>
			</td>
			<td class="dimensions column-dimensions"><?php echo esc_html("$setting->width &times; $setting->height"); ?></td>
			<td class="scaling column-scaling"><?php echo esc_html($fit_menu[$setting->fit]); ?></td>
			<td class="quality column-quality"><?php echo esc_html($quality); ?></td>
			<td class="sharpen column-sharpen"><?php echo esc_html("$setting->sharpen%"); ?></td>

		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody id="image-setting-table" class="list"><tr class="empty"><td colspan="6"><?php _e('No predefined image settings available, yet.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	</form>
	<div class="tablenav bottom">
		<?php $ListTable->pagination( 'bottom' ); ?>
		<br class="clear" />
	</div>

</div>
<script type="text/javascript">
/* <![CDATA[ */
var images = <?php echo json_encode($json_settings); ?>;
/* ]]> */
</script>

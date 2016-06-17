<div class="wrap shopp">

	<div class="icon32"></div>
	<?php

		shopp_admin_screen_tabs();
		do_action('shopp_admin_notices');

	?>

	<form action="<?php echo esc_url($this->url); ?>" id="pages" method="post">
	<div>
		<?php wp_nonce_field('shopp-settings-pages'); ?>
	</div>

	<br class="clear" />

	<script id="editor" type="text/x-jquery-tmpl">
	<?php ob_start(); ?>
	<tr class="inline-edit-row ${classnames}" id="${id}">
		<td>
		<label><input type="text" name="settings[storefront_pages][${name}][title]" value="${title}" /><br /><?php _e('Title','Shopp'); ?></label>
		<p class="submit">
		<a href="<?php echo $this->url; ?>" class="button-secondary cancel"><?php _e('Cancel','Shopp'); ?></a>
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
	<?php $editor = ob_get_contents(); ob_end_clean(); echo $editor; ?>
	</script>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php print_column_headers('shopp_page_shopp-settings-pages'); ?></tr>
		</thead>
		<tfoot>
		<tr><?php print_column_headers('shopp_page_shopp-settings-pages',false); ?></tr>
		</tfoot>
	<?php if (sizeof($pages) > 0): ?>
		<tbody id="customers-table" class="list">
		<?php
			$hidden = get_hidden_columns('shopp_page_shopp-settings-pages');

			$edit = false;
			if (isset($_GET['edit']) && isset($pages[$_GET['edit']])) $edit = $_GET['edit'];

			$even = false;
			foreach ($pages as $name => $page):
				$title = empty($page['title'])?'('.__('not set','Shopp').')':$page['title'];
				$slug = empty($page['slug'])?'':$page['slug'];
				$description = empty($page['description'])?'':$page['description'];
				$editurl = add_query_arg(array('edit'=>$name),$this->url);

				$classes = array();
				if (!$even) $classes[] = 'alternate'; $even = !$even;

				if ($edit == $name) {
					$template_data = array(
						'${id}' => "edit-$name-page",
						'${name}' => $name,
						'${title}' => $page['title'],
						'${slug}' => $page['slug'],
						'${description}' => $page['description'],
						'${classnames}' => join(' ',$classes)
					);

					$editor = str_replace(array_keys($template_data),$template_data,$editor);
					echo $editor;
					continue;
				}

			?>
		<tr class="<?php echo join(' ',$classes); ?>" id="page-<?php echo $name; ?>">
			<td class="title column-title"><a class="row-title edit" href="<?php echo $editurl; ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($title); ?>&quot;" class="edit"><?php echo esc_html($title); ?></a>
				<div class="row-actions">
					<span class='edit'><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($title); ?>&quot;" class="edit"><?php _e('Edit','Shopp'); ?></a></span>
				</div>
			</td>
			<td class="slug column-slug"><?php echo esc_html($slug); ?></td>
			<td class="description column-description"><?php echo esc_html($description); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php _e('No Shopp pages available! The sky is falling! Contact the Help Desk, stat!','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	
	</form>
</div>
<script type="text/javascript">
/* <![CDATA[ */
var pages = <?php echo json_encode($pages); ?>;
/* ]]> */
</script>

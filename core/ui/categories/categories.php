<div class="wrap shopp">
	<div class="icon32"></div>
	<h2><?php Shopp::_e('Categories'); ?> <a href="<?php echo esc_url(add_query_arg(array_merge(stripslashes_deep($_GET), array('page'=> $this->page(), 'id'=> 'new')), admin_url('admin.php'))); ?>" class="add-new-h2"><?php Shopp::_e('Add New'); ?></a></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<form action="<?php echo esc_url($url); ?>" id="categories" method="get">
	<?php include('navigation.php'); ?>

	<p id="post-search" class="search-box">
		<input type="text" id="categories-search-input" class="search-input" name="s" value="<?php echo esc_attr(stripslashes($s)); ?>" />
		<input type="submit" value="<?php Shopp::_e('Search Categories'); ?>" class="button" />
	</p>

	<div class="clear">
		<input type="hidden" name="page" value="<?php echo $this->page(); ?>" />
		<?php wp_nonce_field('shopp_categories_manager'); ?>
	</div>

	<div class="tablenav top">
		<div class="alignleft actions">
		<select name="action" id="actions">
			<option value="" selected="selected"><?php Shopp::_e('Bulk Actions&hellip;'); ?></option>
			<?php echo Shopp::menuoptions(array('delete' => __('Delete')), false, true); ?>
		</select>
		<input type="submit" value="<?php esc_attr_e('Apply','Shopp'); ?>" id="apply" class="button action" />
		</div>

		<?php $ListTable->page_navigation( 'top' ); ?>
	</div>

	<table class="widefat" cellspacing="0">
		<thead>
		<tr><?php ShoppUI::print_column_headers($this->screen); ?></tr>
		</thead>
		<tfoot>
		<tr><?php ShoppUI::print_column_headers($this->screen,false); ?></tr>
		</tfoot>
	<?php if (count($Categories) > 0): ?>
		<tbody id="categories-table" class="list categories">
		<?php
		$columns = get_column_headers($this->screen);
		$hidden = get_hidden_columns($this->screen);

		$even = false;
		foreach ($Categories as $Category):
		?>
			<tr<?php if ( ! $even ) echo " class='alternate'"; $even = ! $even; ?>>
		<?php
		foreach ( $columns as $column => $column_title ) {
			$classes = array($column, "column-$column");
			if ( in_array($column, $hidden) ) $classes[] = 'hidden';

			switch ($column) {
				case 'cb':
				?>
					<th scope='row' class='check-column'><input type='checkbox' name='selected[]' value='<?php echo esc_attr($Category->id); ?>' /></th>
				<?php
				break;

				case 'id':
				?>
					<td><?php echo esc_attr($Category->id); ?></td>
				<?php
				break;				

				case 'name':
					$adminurl = add_query_arg(array_merge($_GET, array('page' => $this->Admin->pagename('categories'))), admin_url('admin.php'));
					$editurl = wp_nonce_url(add_query_arg('id', $Category->id, $adminurl), 'shopp_categories_manager');
					$deleteurl = wp_nonce_url(add_query_arg('action', 'delete', $editurl), 'shopp_categories_manager');

					$CategoryName = empty($Category->name) ? '(' . Shopp::__('no category name') . ')' : $Category->name;

				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>"><a class='row-title' href='<?php echo $editurl; ?>' title='<?php _e('Edit'); ?> &quot;<?php echo esc_attr($CategoryName); ?>&quot;'><?php echo str_repeat("&#8212; ",$Category->level); echo esc_html($CategoryName); ?></a>
						<div class="row-actions">
							<span class='edit'><a href="<?php echo esc_url($editurl); ?>" title="<?php _e('Edit'); ?> &quot;<?php echo esc_attr($CategoryName); ?>&quot;"><?php _e('Edit'); ?></a> | </span>
							<span class='delete'><a class='submitdelete' title='<?php _e('Delete'); ?> &quot;<?php echo esc_attr($CategoryName); ?>&quot;' href="<?php echo esc_url($deleteurl); ?>" rel="<?php echo $Category->id; ?>"><?php _e('Delete'); ?></a> | </span>
							<span class='view'><a href="<?php shopp($Category, 'url'); ?>" title="<?php Shopp::_e('View'); ?> &quot;<?php echo esc_attr($CategoryName); ?>&quot;" rel="permalink" target="_blank"><?php Shopp::_e('View'); ?></a></span>
						</div>
					</td>
				<?php
				break;

				case 'slug':
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>"><?php echo $Category->slug; ?></td>
				<?php
				break;

				case 'products':
					$adminurl = add_query_arg(array_merge($_GET, array('page' => $this->Admin->pagename('products'))), admin_url('admin.php'));
					$classes[] = 'num';
				?>
					<td width="5%" class="<?php echo esc_attr(join(' ',$classes)); ?>"><a href="<?php echo $adminurl . '&shopp_category=' . $Category->slug; ?>"><?php echo $Category->count; ?></a></td>
				<?php
				break;

				case 'templates':
					$classes[] = 'num';
					$enabled = isset($Category->spectemplate) && Shopp::str_true($Category->spectemplate);
					$title = $enabled ? Shopp::__('Product detail templates enabled') : '';
				?>
					<td width="5%" class="<?php echo esc_attr(join(' ',$classes)); ?>">
						<div class="checkbox<?php echo $enabled ? ' checked': ''; ?>" title="<?php echo $title; ?>"><span class="hidden"><?php echo $title; ?></div>
					</td>
				<?php
				break;

				case 'menus':
					$classes[] = 'num';
					$enabled = isset($Category->facetedmenus) && Shopp::str_true($Category->facetedmenus);
					$title = $enabled ? Shopp::__('Faceted search menus enabled') : '';
				?>
					<td width="5%" class="<?php echo esc_attr(join(' ',$classes)); ?>">
						<div class="checkbox<?php echo $enabled ? ' checked': ''; ?>" title="<?php echo $title; ?>"><span class="hidden"><?php echo $title; ?></div>
					</td>
				<?php
				break;

				default:
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
				<?php
					do_action( 'shopp_manage_categories_custom_column', $column, $Category );
					do_action( 'manage_'.ProductCategory::$taxon.'_custom_column', $column, $Category );
				?>
					</td>
				<?php
				break;

			}

		} /* $columns */
		?>
		</tr>
		<?php endforeach; /* $Categories */ ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php Shopp::_e('No categories found.'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	</form>
	<div class="tablenav"><?php $ListTable->page_navigation( 'bottom' ); ?></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {

	columns.init(pagenow);

	$('#selectall').change( function() {
		$('#categories-table th input').each( function () {
			if ( this.checked ) this.checked = false;
			else this.checked = true;
		});
	});

	$('#categories-table a.submitdelete').click(function () {
		if (confirm("<?php Shopp::_e('You are about to delete this category!\n \'Cancel\' to stop, \'OK\' to delete.'); ?>"))
			window.location = $(this).attr('href');
		return false;
	});

});
</script>

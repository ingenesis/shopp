<div class="wrap shopp">
	<div class="icon32"></div>
	<h2><?php Shopp::_e('Arrange Products for &quot;%s&quot;', $CategoryProducts->name); ?></h2>

	<?php do_action('shopp_admin_notice'); ?>

	<form action="" id="products" method="get">
	<div>
		<input type="hidden" name="page" value="<?php echo $this->Admin->pagename('categories'); ?>" />
		<input type="hidden" name="category" id="category-id" value="<?php echo $CategoryProducts->term_taxonomy_id; ?>" />
	</div>

	<div class="tablenav">
		<div class="alignleft actions">
			<a href="<?php echo esc_url(add_query_arg(array_merge(stripslashes_deep($_GET),array('page'=>$this->Admin->pagename('categories'),'a'=>null)),admin_url('admin.php'))); ?>" class="button add-new">&larr; <?php printf(__('Return to %s','Shopp'),$CategoryProducts->name); ?></a>
		</div>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>

	<table id="arrange-products" class="widefat" cellspacing="0">
		<thead>
		<tr><?php ShoppUI::print_column_headers($this->screen); ?></tr>
		</thead>
		<tfoot>
		<tr><?php ShoppUI::print_column_headers($this->screen, false); ?></tr>
		</tfoot>
	<?php if (sizeof($CategoryProducts) > 0): ?>
		<tbody id="categories-table" class="list categories">
		<?php
		$columns = get_column_headers($this->screen);
		$hidden = get_hidden_columns($this->screen);

		$even = false;
		foreach ( $CategoryProducts as $Product ): ?>
		<tr<?php if (!$even) echo " class='alternate'"; $even = !$even; ?>>
		<?php
		foreach ($columns as $column => $column_title) {
			$classes = array($column, "column-$column");
			if ( in_array($column,$hidden) ) $classes[] = 'hidden';

			switch ($column) {
				case 'name':
				?>
					<th scope='row' class='move-column'><button type="button" name="top" alt="<?php $title = Shopp::__('Move to the top&hellip;'); echo $title; ?>" class="moveto shoppui-step-top"><span class="hidden"><?php echo $title; ?></span></button><button type="button" name="bottom" alt="<?php $title = Shopp::__('Move to the bottom&hellip;'); echo $title; ?>" class="moveto shoppui-step-bottom"><span class="hidden"><?php echo $title; ?></span></button></th>
				<?php
				break;

				case 'title':
					$editurl = esc_url(esc_attr(add_query_arg(array_merge(stripslashes_deep($_GET),
						array('page'=>$this->Admin->pagename('products'),
								'id'=>$Product->id)),
								admin_url('admin.php'))));

					$ProductName = empty($Product->name)?'('.__('no product name','Shopp').')':$Product->name;
				?>
				<td class="<?php echo esc_attr(join(' ',$classes)); ?>"><a class='row-title' href='<?php echo $editurl; ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($ProductName); ?>&quot;'><?php echo esc_html($ProductName); ?></a>
				<input type="hidden" name="position[<?php echo $Product->id; ?>]" value="<?php echo $Product->priority; ?>" /></td>
				<?php
				break;


				case 'sold':
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
						<?php echo $Product->sold; ?>
					</td>
				<?php
				break;

				case 'gross':
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
						<?php echo money($Product->grossed); ?>
					</td>
				<?php
				break;

				case 'price':
					if ( Shopp::str_true($Product->sale) ) $classes[] = 'sale';
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>"><?php
						shopp($Product, 'price');
						if ( Shopp::str_true($Product->sale) ) echo '&nbsp;<span class="shoppui-tag" title="' . Shopp::__('On Sale') . '"><span class="hidden">' . Shopp::__('On Sale') . '</span></span>';
					?>
					</td>
				<?php
				break;

				case 'inventory':
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
					<?php // @todo Link inventory number to Inventory view while filtering for the product/variants
					if ( Shopp::str_true($Product->inventory) ) {
						$stockclass = array('stock');
						if (!empty($Product->lowstock) && 'none' != $Product->lowstock) $stockclass[] = "lowstock $Product->lowstock";
					 	echo '<span class="'.join(' ',$stockclass).'">'.$Product->stock.'</span>';
					}
					?>
					</td>
				<?php
				break;

				case 'featured':
				?>
					<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
						<span class="feature<?php echo Shopp::str_true($Product->featured) ? ' featured ' : ' '; ?>shoppui-star"><span class="hidden"><?php Shopp::_e('Featured'); ?></span></span>
					</td>
				<?php
				break;

			} // end switch ($column)
		} // end foreach ($columns)
		?>

			<!-- <th scope="row" class='move-column'><button type="button" name="top" alt="<?php $title = Shopp::__('Move to the top&hellip;'); echo $title; ?>" class="moveto shoppui-step-top"><span class="hidden"><?php echo $title; ?></span></button><button type="button" name="bottom" alt="<?php $title = Shopp::__('Move to the bottom&hellip;'); echo $title; ?>" class="moveto shoppui-step-bottom"><span class="hidden"><?php echo $title; ?></span></button></th>
			<td><a class='row-title' href='<?php echo $editurl; ?>' title='<?php _e('Edit','Shopp'); ?> &quot;<?php echo $ProductName; ?>&quot;'><?php echo $ProductName; ?></a>
			<input type="hidden" name="position[<?php echo $Product->id; ?>]" value="<?php echo $Product->priority; ?>" /></td> -->
		</tr>
		<?php endforeach; ?>
		</tbody>
	<?php else: ?>
		<tbody><tr><td colspan="6"><?php _e('No products found.','Shopp'); ?></td></tr></tbody>
	<?php endif; ?>
	</table>
	</form>
	<div class="tablenav">
		<?php if ($page_links) echo "<div class='tablenav-pages'>$page_links</div>"; ?>
		<div class="clear"></div>
	</div>
</div>

<script type="text/javascript">
var updates_url = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'),'wp_ajax_shopp_category_products_order'); ?>';
jQuery(document).ready( function() {
	pagenow = 'shopp_page_shopp-categories';
	columns.init(pagenow);
});
</script>
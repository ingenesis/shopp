<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php printf(__('Order #%d','Shopp'),(int)$Purchase->id); ?></h2>

	<?php $this->notices(); ?>

	<?php include("navigation.php"); ?>
	<br class="clear" />

	<div id="order">
		<form action="<?php echo ShoppAdminController::url( array('id' => $Purchase->id) ); ?>" method="post" id="order-updates">
			<div class="title">
				<div id="titlewrap">
					<span class="date"><?php echo Shopp::_d(get_option('date_format'), $Purchase->created); ?> <small><?php echo date(get_option('time_format'),$Purchase->created); ?></small>

					<div class="alignright">

						<?php if ($Purchase->shipped): ?>
						<div class="stamp shipped<?php if ($Purchase->isvoid()) echo ' void'; ?>"><div class="type"><?php _e('Shipped','Shopp'); ?></div><div class="ing">&nbsp;</div></div>
						<?php endif; ?>

						<?php if ( $Purchase->ispaid() && ! $Purchase->isvoid() ): ?>
						<div class="stamp paid"><div class="type"><?php _e('Paid','Shopp'); ?></div><div class="ing">&nbsp;</div></div>
						<?php elseif ($Purchase->isvoid()): ?>
						<div class="stamp void"><div class="type"><?php _e('Void','Shopp'); ?></div><div class="ing">&nbsp;</div></div>
						<?php endif; ?>

					</div>

				</div>
			</div>

			<?php if ( count($Purchase->purchased) > 0 ): ?>
				<tbody id="items" class="list items">
				<?php
				$columns = get_column_headers($this->screen);
				$hidden = get_hidden_columns($this->screen);
			?>
			<script id="item-editor" type="text/x-jquery-tmpl">
			<?php $colspan = count(get_column_headers($this->screen)); ob_start(); ?>
			<?php
				foreach ($columns as $column => $column_title) {
					$classes = array($column,"column-$column");
					if ( in_array($column, $hidden) ) $classes[] = 'hidden';

					switch ($column) {
						case 'cb':
							?>
								<th scope='row' class='check-column'></th>
							<?php
							break;
						case 'items':
							?>
								<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
								<input type="text" name="name" value="${itemname}" size="40" />
								<div class="controls">
								<input type="hidden" name="lineid" value="${lineid}"/>
								<input type="submit" name="cancel-edit-item" value="<?php _e('Cancel','Shopp'); ?>" class="button-secondary" />
								</div>
								</td>
							<?php
							break;
						case 'qty':
							$classes[] = 'num';
							?>
								<td class="<?php echo esc_attr(join(' ',$classes)); ?>"><input type="text" name="quantity" value="${quantity}" size="5" /></td>
							<?php
							break;
						case 'price':
							$classes[] = 'money';
							?>
								<td class="<?php echo esc_attr(join(' ',$classes)); ?>"><input type="text" name="unitprice" value="${unitprice}" size="10" /></td>
							<?php
							break;
						case 'total':
							$classes[] = 'money';
							?>
							<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
								<input type="text" name="total" value="${total}" size="10" class="focus-edit" />
								<div class="controls">
								<input type="submit" name="save-item" value="<?php _e('Save Changes','Shopp'); ?>" class="button-primary alignright" />
								</div>
							</td>
							<?php
							break;
						default:
							?>
								<td class="<?php echo esc_attr(join(' ',$classes)); ?>"></td>
							<?php
							break;
					}
				}
				?>
			<?php $itemeditor = ob_get_contents(); ob_end_clean(); ?>
			</script>
			<?php endif; ?>

			<table class="widefat" cellspacing="0">
				<thead>
					<tr><?php ShoppUI::print_column_headers($this->screen); ?></tr>
				</thead>
				<tfoot>
				<?php $colspan = count(get_column_headers($this->screen))-1; ?>
				<tr class="totals">
					<td scope="row" colspan="<?php echo ($colspan); ?>" class="label"><?php _e('Subtotal','Shopp'); ?></td>
					<td class="money"><?php echo money($Purchase->subtotal); ?></td>
				</tr>
				<?php if ( $Purchase->discounts() ): ?>
				<tr class="totals">
					<td scope="row" colspan="<?php echo $colspan; ?>" class="label"><?php _e('Discount','Shopp'); ?></td>
					<td class="money"><?php echo money($Purchase->discount); ?>
						<?php if ( $Purchase->discounts() ): ?>
						<ul class="promos">
						<?php foreach ( $Purchase->discounts as $id => $Discount ): ?>
							<li><small><a href="<?php echo esc_url( add_query_arg(array('page' => $this->Admin->pagename('discounts'), 'id' => $id), admin_url('admin.php'))); ?>"><?php echo esc_html($Discount->name); ?></a><?php if ( isset($Discount->code) ) echo " - " . esc_html($Discount->code); ?></small></li>
						<?php endforeach; ?>
						</ul>
						<?php endif; ?>
						</td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty($Purchase->shipoption) ): ?>
				<tr class="totals">
					<td scope="row" colspan="<?php echo $colspan; ?>" class="label shipping"><span class="method"><?php echo apply_filters('shopp_order_manager_shipping_method',$Purchase->shipoption); ?></span> <?php _e('Shipping','Shopp'); ?></td>
					<td class="money"><?php echo money($Purchase->freight); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ($Purchase->tax > 0): ?>
				<tr class="totals">
					<td scope="row" colspan="<?php echo $colspan; ?>" class="label"><?php _e('Tax','Shopp'); ?></td>
					<td class="money"><?php echo money($Purchase->tax); ?></td>
				</tr>
				<?php endif; ?>
				<tr class="totals total">
					<td scope="row" colspan="<?php echo $colspan; ?>" class="label"><?php _e('Total','Shopp'); ?></td>
					<td class="money"><?php echo money($Purchase->total); ?></td>
				</tr>
				</tfoot>
				<?php if ( count($Purchase->purchased) > 0 ): ?>
					<tbody id="items" class="list items">
					<?php
					$columns = get_column_headers($this->screen);
					$hidden = get_hidden_columns($this->screen);

					$even = false;
					foreach ($Purchase->purchased as $id => $Item):
						$taxrate = round($Item->unittax/$Item->unitprice,4);
						$rowclasses = array("lineitem-$id");
						if ( ! $even ) $rowclasses[] = 'alternate';
						$even = ! $even;

						$itemname = $Item->name . ( ! empty($Item->optionlabel) ?" ($Item->optionlabel)" : '');


					?>
						<tr class="<?php echo esc_attr(join(' ',$rowclasses)); ?>">
					<?php
						if ( isset($_GET['editline']) && (int)$_GET['editline'] == $id ) {
							$data = array(
								'${lineid}'    => (int)$_GET['editline'],
								'${itemname}'  => $itemname,
								'${quantity}'  => $Item->quantity,
								'${unitprice}' => money($Item->unitprice),
								'${total}'     => money($Item->total)
							);
							echo ShoppUI::template($itemeditor, $data);
						} else {

							foreach ($columns as $column => $column_title) {
								$classes = array($column, "column-$column");
								if ( in_array($column, $hidden) ) $classes[] = 'hidden';

								ob_start();
								switch ( $column ) {
									case 'items':
									ShoppProduct( new ShoppProduct($Item->product) ); // @todo Find a way to make this more efficient by loading product slugs with load_purchased()?
									$viewurl = shopp('product.get-url');
									$editurl = ShoppAdminController::url( array('id' => $Purchase->id, 'editline'=> $id) );
									$rmvurl = ShoppAdminController::url( array('id' => $Purchase->id, 'rmvline'=> $id) );
									$producturl = add_query_arg( array('page' => 'shopp-products', 'id' => $Item->product), admin_url('admin.php') );
										?>
											<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
												<a href="<?php echo $producturl; ?>">
	                                                <?php
	                                                $Product = new ShoppProduct($Item->product);
	                                                $Product->load_data( array('images') );
	                                                $Image = reset($Product->images);

	                                                if ( ! empty($Image) ) { 
	                                                    $image_id = apply_filters('shopp_order_item_image_id', $Image->id, $Item, $Product); ?>
	                                                    <img src="?siid=<?php echo $image_id ?>&amp;<?php echo $Image->resizing(38, 0, 1) ?>" width="38" height="38" class="alignleft" />
	                                                <?php
	                                                }
	                                                echo apply_filters('shopp_purchased_item_name', $itemname); ?>
	                                            </a>
												<div class="row-actions">
													<!-- <span class='edit'><a href="<?php echo $editurl; ?>" title="<?php _e('Edit','Shopp'); ?> &quot;<?php echo esc_attr($Item->name); ?>&quot;"><?php _e('Edit','Shopp'); ?></a> | </span>
													<span class='delete'><a href="<?php echo $rmvurl; ?>" title="<?php echo esc_attr(sprintf(__('Remove %s from the order','Shopp'), "&quot;$Item->name&quot;")); ?>" class="delete"><?php _e('Remove','Shopp'); ?></a> | </span> -->
													<span class='view'><a href="<?php echo $viewurl;  ?>" title="<?php _e('View','Shopp'); ?> &quot;<?php echo esc_attr($Item->name); ?>&quot;" target="_blank"><?php _e('View','Shopp'); ?></a></span>
												</div>

												<?php if ( (is_array($Item->data) && ! empty($Item->data))  || ! empty($Item->sku) || (! empty($Item->addons) && 'no' != $Item->addons) ): ?>
												<ul>
												<?php if (!empty($Item->sku)): ?><li><small><?php _e('SKU','Shopp'); ?>: <strong><?php echo $Item->sku; ?></strong></small></li><?php endif; ?>

												<?php if ( isset($Item->addons) && isset($Item->addons->meta) ): ?>
													<?php foreach ( (array)$Item->addons->meta as $id => $addon ):
														if ( "inclusive" != $Purchase->taxing )
															$addonprice = $addon->value->unitprice + ( $addon->value->unitprice * $taxrate );
														else $addonprice = $addon->value->unitprice;

														?>
														<li><small><?php echo apply_filters('shopp_purchased_addon_name', $addon->name); ?><?php if ( ! empty($addon->value->sku) ) echo apply_filters('shopp_purchased_addon_sku',' [SKU: ' . $addon->value->sku . ']'); ?>: <strong><?php echo apply_filters('shopp_purchased_addon_unitprice', money($addonprice)); ?></strong></small></li>
													<?php endforeach; ?>
												<?php endif; ?>
												<?php foreach ( (array)$Item->data as $name => $value ): ?>
													<li><small><?php echo apply_filters('shopp_purchased_data_name', $name); ?>: <strong><?php echo apply_filters('shopp_purchased_data_value', $value); ?></strong></small></li>
												<?php endforeach; ?>
												<?php endif; ?>
												<?php do_action_ref_array('shopp_after_purchased_data', array($Item, $Purchase)); ?>
												</ul>
											</td>
										<?php
										break;

									case 'qty':
										$classes[] = 'num';
										?>
											<td class="<?php echo esc_attr(join(' ', $classes)); ?>"><?php echo $Item->quantity; ?></td>
										<?php
										break;

									case 'price':
									$classes[] = 'money';
										?>
											<td class="<?php echo esc_attr(join(' ', $classes)); ?>"><?php echo money($Item->unitprice); ?></td>
										<?php
										break;

									case 'total':
										$classes[] = 'money';
										?>
											<td class="<?php echo esc_attr(join(' ', $classes)); ?>"><?php echo money($Item->total); ?></td>
										<?php
										break;

									default:
										?>
											<td class="<?php echo esc_attr(join(' ', $classes)); ?>">
											<?php do_action( 'shopp_manage_order_' . sanitize_key($column) .'_column_data', $column, $Product, $Item, $Purchase ); ?>
											</td>
										<?php
										break;
								}
								$output = ob_get_contents();
								ob_end_clean();
								echo apply_filters('shopp_manage_order_' . $column . '_column', $output);
							}
						}
					?>
					<?php endforeach; ?>
				<?php endif; ?>
			</table>
			</form>


			<div id="poststuff" class="poststuff">

			<div class="meta-boxes">

				<div id="column-one" class="column left-column">
					<?php do_meta_boxes('toplevel_page_shopp-orders', 'side', $Purchase); ?>
				</div>
				<div id="main-column">
					<div id="column-two" class="column right-column">
						<?php do_meta_boxes('toplevel_page_shopp-orders', 'normal', $Purchase); ?>
					</div>
				</div>
				<br class="clear" />
			</div>

			<?php wp_nonce_field('shopp-save-order'); ?>
			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
			</div>
	</div> <!-- #order -->

</div>

<iframe id="print-receipt" name="receipt" src="<?php echo wp_nonce_url(admin_url('admin-ajax.php').'?action=shopp_order_receipt&amp;id='.$Purchase->id,'wp_ajax_shopp_order_receipt'); ?>" width="400" height="100" class="invisible"></iframe>

<script type="text/javascript">
/* <![CDATA[ */
var carriers = <?php echo json_encode($carriers_json); ?>;

jQuery(document).ready(function() {
	var $=jQuery,
		noteurl = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_order_note_message'); ?>';

	// close postboxes that should be closed
	$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
	postboxes.add_postbox_toggles('toplevel_page_shopp-orders');

	$('.postbox a.help').click(function () {
		$(this).colorbox({iframe:true,open:true,innerWidth:768,innerHeight:480,scrolling:false});
		return false;
	});

	$('#notification').hide();
	$('#notify-customer').click(function () {
		$('#notification').animate({
			height: "toggle",
			opacity:"toggle"
		}, 500);
	});

	$('#notation').hide();
	$('#add-note-button').click(function (e) {
		e.preventDefault();
		$('#add-note-button').hide();
		$('#notation').animate({
			height: "toggle",
			opacity:"toggle"
		}, 500);
	});

	$('#cancel-note-button').click(function (e) {
		e.preventDefault();
		$('#add-note-button').animate({opacity:"toggle"},500);
		$('#notation').animate({
			height: "toggle",
			opacity:"toggle"
		}, 500);
	});

	$('#order-notes table tr').hover(function () {
		$(this).find('.notectrls').animate({
			opacity:"toggle"
		}, 500);

	},function () {
		$(this).find('.notectrls').animate({
			opacity:"toggle"
		}, 100);

	});

	$('td .deletenote').click(function (e) {
		if (!confirm('Are you sure you want to delete this note?'))
			e.preventDefault();
	});

	$('td .editnote').click(function () {
		var editbtn = $(this).attr('disabled',true).addClass('updating'),
			cell = editbtn.parents('td'),
			note = cell.find('div'),
			ctrls = cell.find('span.notectrls'),
			meta = cell.find('p.notemeta'),
			idattr = note.attr('id').split("-"),
			id = idattr[1];
		$.get(noteurl+'&action=shopp_order_note_message&id='+id,false,function (msg) {
			editbtn.removeAttr('disabled').removeClass('updating');
			if (msg == '1') return;
			var editor = $('<textarea name="note-editor['+id+']" cols="50" rows="10" />').val(msg).prependTo(cell);
				ui = $('<div class="controls alignright">'+
						'<button type="button" name="cancel" class="cancel-edit-note button-secondary">Cancel</button>'+
						'<button type="submit" name="edit-note['+id+']" class="save-note button-primary">Save Note</button></div>').appendTo(meta),
				cancel = ui.find('button.cancel-edit-note').click(function () {
						editor.remove();
						ui.remove();
						note.show();
						ctrls.addClass('notectrls');
					});
			note.hide();
			ctrls.hide().removeClass('notectrls');
		});

	});

	$('#customer').click(function () {
		window.location = "<?php echo add_query_arg(array('page'=>$this->Admin->pagename('customers'),'id'=>$Purchase->customer),admin_url('admin.php')); ?>";
	});

<?php do_action_ref_array('shopp_order_admin_script',array(&$Purchase)); ?>

});
/* ]]> */
</script>

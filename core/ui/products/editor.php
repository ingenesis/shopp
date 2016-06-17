<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php Shopp::_e('Product Editor'); ?> <a href="<?php echo esc_url( add_query_arg(array('page' => $this->Admin->pagename('products'), 'id' => 'new'), admin_url('admin.php'))); ?>" class="add-new-h2"><?php Shopp::_e('Add New'); ?></a> </h2>

	<?php do_action('shopp_admin_notices'); ?>

	<div id="ajax-response"></div>
	<form name="product" id="product" action="<?php echo esc_url($this->url); ?>" method="post">
		<?php wp_nonce_field('shopp-save-product'); ?>
		<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>

		<div id="poststuff" class="metabox-holder has-right-sidebar">

			<div id="side-info-column" class="inner-sidebar">
			<?php

				do_action('submitpage_box');
				do_meta_boxes($post_type, 'side', $Product);

			?>
			</div>

			<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">

				<div id="titlediv">
					<div id="titlewrap">
						<label class="hide-if-no-js<?php if (!empty($Product->name)) echo ' hidden'; ?>" id="title-prompt-text" for="title"><?php Shopp::_e('Enter product name'); ?></label>
						<input name="name" id="title" type="text" value="<?php echo esc_attr($Product->name); ?>" size="30" tabindex="1" autocomplete="off" />
					</div>
					<div class="inside">
						<?php if ('' != get_option('permalink_structure') && !empty($Product->id)): ?>
							<div id="edit-slug-box"><strong><?php Shopp::_e('Permalink'); ?>:</strong>
							<span id="sample-permalink"><?php echo $permalink; ?><span id="editable-slug" title=<?php Shopp::_jse('Click to edit this part of the permalink'); ?>><?php echo esc_attr($Product->slug); ?></span><span id="editable-slug-full"><?php echo esc_attr($Product->slug); ?></span><?php echo user_trailingslashit(""); ?></span>
							<span id="edit-slug-buttons">
								<button type="button" class="edit button"><?php Shopp::_e('Edit'); ?></button><?php if ($Product->status == "publish"): ?><a href="<?php echo esc_url(shopp($Product,'get-url')); ?>" id="view-product" class="view button"><?php Shopp::_e('View'); ?></a><?php endif; ?></span>
							<span id="editor-slug-buttons">
								<button type="button" class="save button"><?php Shopp::_e('Save'); ?></button> <button type="button" class="cancel button"><?php Shopp::_e('Cancel'); ?></button>
							</span>
							</div>
						<?php else: ?>
							<?php if (!empty($Product->id)): ?>
							<div id="edit-slug-box"><strong><?php Shopp::_e('Product ID'); ?>:</strong>
							<span id="editable-slug"><?php echo $Product->id; ?></span>
							</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
				
				<?php do_action( 'edit_form_after_title', $Product ); ?>

				<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
				<?php
					$media_buttons = ( defined('SHOPP_EDITOR_MEDIA_BTNS') && SHOPP_EDITOR_MEDIA_BTNS );
					wp_editor($Product->description, 'content', array( 'media_buttons' => $media_buttons ));
				?>
				</div>
			<?php
			do_meta_boxes(get_current_screen()->id, 'normal', $Product);
			do_meta_boxes(get_current_screen()->id, 'advanced', $Product);

			do_meta_boxes($post_type, 'normal', $Product);
			do_meta_boxes($post_type, 'advanced', $Product);

			?>
			</div>
			</div>
			<div class="clear">&nbsp;</div>
		</div> <!-- #poststuff -->
	</form>
</div>

<?php do_action('shopp_product_editor_templates'); ?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery('.hide-if-no-js').removeClass('hide-if-no-js');
var flashuploader 	    = <?php echo ($uploader == 'flash' && !(false !== strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mac') && apache_mod_loaded('mod_security'))) ? 'true' : 'false'; ?>,
	product 	        = <?php echo (!empty($Product->id))?$Product->id:'false'; ?>,
	prices 	            = <?php echo json_encode($Product->prices) ?>,
	specs 	            = <?php $specs = array(); foreach ( $Product->specs as $Spec ) $specs[] = $Spec->json(array('context', 'type', 'numeral', 'sortorder', 'created', 'modified')); echo json_encode($specs); ?>,
	options 	        = <?php echo json_encode($Product->options) ?>,
	priceTypes 	        = <?php echo json_encode($priceTypes) ?>,
	billPeriods 	    = <?php echo json_encode($billPeriods) ?>,
	shiprates 	        = <?php echo json_encode($shiprates); ?>,
	uidir 	            = '<?php echo SHOPP_ADMIN_URI; ?>',
	siteurl 	        = '<?php bloginfo('url'); ?>',
	screenid 	        = '<?php echo get_current_screen()->id; ?>',
	canonurl 	        = '<?php echo trailingslashit(Shopp::url()); ?>',
	adminurl 	        = '<?php echo admin_url(); ?>',
	sugg_url 	        = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), "wp_ajax_shopp_storage_suggestions"); ?>',
	tagsugg_url 	    = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), "wp_ajax_shopp_suggestions"); ?>',
	spectemp_url 	    = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), "wp_ajax_shopp_spec_template"); ?>',
	opttemp_url 	    = '<?php echo wp_nonce_url(admin_url('admin-ajax.php'), "wp_ajax_shopp_options_template"); ?>',
	addcategory_url 	= '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "wp_ajax_shopp_add_category"); ?>',
	editslug_url 	    = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "wp_ajax_shopp_edit_slug"); ?>',
	fileverify_url 	    = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "wp_ajax_shopp_verify_file"); ?>',
	fileimport_url 	    = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "wp_ajax_shopp_import_file"); ?>',
	imageul_url 	    = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "wp_ajax_shopp_upload_image"); ?>',
	adminpage 	        = '<?php echo $this->Admin->pagename('products'); ?>',
	request 	        = <?php echo json_encode(stripslashes_deep($_GET)); ?>,
	filesizeLimit 	    = <?php echo wp_max_upload_size(); ?>,
	weightUnit 	        = '<?php echo shopp_setting('weight_unit'); ?>',
	dimensionUnit 	    = '<?php echo shopp_setting('dimension_unit'); ?>',
	storage 	        = '<?php echo shopp_setting('product_storage'); ?>',
	productspath 	    = '<?php /* realpath needed for relative paths */ chdir(WP_CONTENT_DIR); echo addslashes(trailingslashit(sanitize_path(realpath(shopp_setting('products_path'))))); ?>',
	imageupload_debug 	= <?php echo (defined('SHOPP_IMAGEUPLOAD_DEBUG') && SHOPP_IMAGEUPLOAD_DEBUG)?'true':'false'; ?>,
	fileupload_debug 	= <?php echo (defined('SHOPP_FILEUPLOAD_DEBUG') && SHOPP_FILEUPLOAD_DEBUG)?'true':'false'; ?>,
	dimensionsRequired	= <?php echo $Shopp->Shipping->dimensions?'true':'false'; ?>,
	startWeekday	    = <?php echo get_option('start_of_week'); ?>,
	calendarTitle	    = '<?php $df = date_format_order(true); $format = $df["month"]." ".$df["year"]; echo $format; ?>',

	// Warning/Error Dialogs
	DELETE_IMAGE_WARNING	    = <?php Shopp::_jse('Are you sure you want to delete this product image?'); ?>,
	SERVER_COMM_ERROR	        = <?php Shopp::_jse('There was an error communicating with the server.'); ?>,

	// Dynamic interface label translations
	LINK_ALL_VARIATIONS	        = <?php Shopp::_jse('Link All Variations'); ?>,
	UNLINK_ALL_VARIATIONS	    = <?php Shopp::_jse('Unlink All Variations'); ?>,
	LINK_VARIATIONS	            = <?php Shopp::_jse('Link Variations'); ?>,
	UNLINK_VARIATIONS	        = <?php Shopp::_jse('Unlink Variations'); ?>,
	ADD_IMAGE_BUTTON_TEXT	    = <?php Shopp::_jse('Add New Image'); ?>,
	UPLOAD_FILE_BUTTON_TEXT	    = <?php Shopp::_jse('Upload File'); ?>,
	SELECT_FILE_BUTTON_TEXT	    = <?php Shopp::_jse('Select File'); ?>,
	SAVE_BUTTON_TEXT	        = <?php Shopp::_jse('Save'); ?>,
	CANCEL_BUTTON_TEXT	        = <?php Shopp::_jse('Cancel'); ?>,
	TYPE_LABEL	                = <?php Shopp::_jse('Type'); ?>,
	PRICE_LABEL	                = <?php Shopp::_jse('Price'); ?>,
	AMOUNT_LABEL	            = <?php Shopp::_jse('Amount'); ?>,
	SALE_PRICE_LABEL	        = <?php Shopp::_jse('Sale Price'); ?>,
	NOT_ON_SALE_TEXT	        = <?php Shopp::_jse('Not on sale'); ?>,
	NOTAX_LABEL	                = <?php Shopp::_jse('Not Taxed'); ?>,
	SHIPPING_LABEL	            = <?php Shopp::_jse('Shipping'); ?>,
	FREE_SHIPPING_TEXT	        = <?php Shopp::_jse('Free Shipping'); ?>,
	WEIGHT_LABEL	            = <?php Shopp::_jse('Weight'); ?>,
	LENGTH_LABEL	            = <?php Shopp::_jse('Length'); ?>,
	WIDTH_LABEL	                = <?php Shopp::_jse('Width'); ?>,
	HEIGHT_LABEL	            = <?php Shopp::_jse('Height'); ?>,
	DIMENSIONAL_WEIGHT_LABEL	= <?php Shopp::_jse('3D Weight'); ?>,
	SHIPFEE_LABEL	            = <?php Shopp::_jse('Extra Fee'); ?>,
	SHIPFEE_XTRA	            = <?php Shopp::_jse('Amount added to shipping costs for each unit ordered (for handling costs, etc)'); ?>,
	INVENTORY_LABEL	            = <?php Shopp::_jse('Inventory'); ?>,
	NOT_TRACKED_TEXT	        = <?php Shopp::_jse('Not Tracked'); ?>,
	IN_STOCK_LABEL	            = <?php Shopp::_jse('In Stock'); ?>,
	BILLCYCLE_LABEL	            = <?php Shopp::_jse('Billing Cycle'); ?>,
	TRIAL_LABEL	                = <?php Shopp::_jse('Trial Period'); ?>,
	NOTRIAL_TEXT	            = <?php Shopp::_jse('No trial period'); ?>,
	TIMES_LABEL	                = <?php Shopp::_jse('times'); ?>,
	MEMBERSHIP_LABEL	        = <?php Shopp::_jse('Membership'); ?>,
	OPTION_MENU_DEFAULT	        = <?php Shopp::_jse('Option Menu'); ?>,
	NEW_OPTION_DEFAULT	        = <?php Shopp::_jse('New Option'); ?>,
	ADDON_GROUP_DEFAULT	        = <?php Shopp::_jse('Add-on Group'); ?>,
	SKU_LABEL	                = <?php Shopp::_jse('SKU'); ?>,
	SKU_LABEL_HELP	            = <?php Shopp::_jse('Stock Keeping Unit'); ?>,
	SKU_XTRA	                = <?php Shopp::_jse('Enter a unique stock keeping unit identification code.'); ?>,
	DONATIONS_VAR_LABEL	        = <?php Shopp::_jse('Accept variable amounts'); ?>,
	DONATIONS_MIN_LABEL	        = <?php Shopp::_jse('Amount required as minimum'); ?>,
	PRODUCT_DOWNLOAD_LABEL	    = <?php Shopp::_jse('Product Download'); ?>,
	NO_PRODUCT_DOWNLOAD_TEXT	= <?php Shopp::_jse('No product download'); ?>,
	NO_DOWNLOAD	                = <?php Shopp::_jse('No download file'); ?>,
	UNKNOWN_UPLOAD_ERROR	    = <?php Shopp::_jse('An unknown error occurred. The upload could not be saved.'); ?>,
	DEFAULT_PRICELINE_LABEL	    = <?php Shopp::_jse('Price & Delivery'); ?>,
	FILE_NOT_FOUND_TEXT	        = <?php Shopp::_jse('The file you specified could not be found.'); ?>,
	FILE_NOT_READ_TEXT	        = <?php Shopp::_jse('The file you specified is not readable and cannot be used.'); ?>,
	FILE_ISDIR_TEXT	            = <?php Shopp::_jse('The file you specified is a directory and cannot be used.'); ?>,
	FILE_UNKNOWN_IMPORT_ERROR	= <?php Shopp::_jse('An unknown error occured while attempting to attach the file.'); ?>,
	IMAGE_DETAILS_TEXT	        = <?php Shopp::_jse('Image Details'); ?>,
	IMAGE_DETAILS_TITLE_LABEL	= <?php Shopp::_jse('Title'); ?>,
	IMAGE_DETAILS_ALT_LABEL	    = <?php Shopp::_jse('Alt'); ?>,
	IMAGE_DETAILS_DONE	        = <?php Shopp::_jse('OK'); ?>,
	IMAGE_DETAILS_CROP_LABEL	= <?php Shopp::_jse('Cropped images'); ?>,
	TAG_SEARCHSELECT_LABEL	    = <?php Shopp::_jse('Type to search tags or wait for popular tags&hellip;'); ?>;
/* ]]> */
</script>
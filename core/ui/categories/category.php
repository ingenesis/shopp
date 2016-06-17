<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php _e('Category Editor','Shopp'); ?></h2>

	<?php do_action('shopp_admin_notices'); ?>

	<div id="ajax-response"></div>
	<form name="category" id="category" action="<?php echo admin_url('admin.php'); ?>" method="post">
		<?php wp_nonce_field('shopp-save-category'); ?>

		<div id="poststuff" class="metabox-holder has-right-sidebar">

			<div id="side-info-column" class="inner-sidebar">

			<?php
			do_action('submitpage_box');
			$side_meta_boxes = do_meta_boxes('shopp_page_shopp-category', 'side', $Category);
			?>
			</div>

			<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
			<div id="post-body-content" class="has-sidebar-content">

				<div id="titlediv">
					<div id="titlewrap">
						<input name="name" id="title" type="text" value="<?php echo esc_attr($Category->name); ?>" size="30" tabindex="1" autocomplete="off" />
					</div>
					<div class="inside">
						<?php if ('' != get_option('permalink_structure') && !empty($Category->id)): ?>
						<div id="edit-slug-box"><strong><?php _e('Permalink','Shopp'); ?>:</strong>
						<span id="sample-permalink"><?php echo $permalink; ?><span id="editable-slug" title="<?php _e('Click to edit this part of the permalink','Shopp'); ?>"><?php echo esc_attr($Category->slug); ?></span><span id="editable-slug-full"><?php echo esc_attr($Category->slug); ?></span>/</span>
						<span id="edit-slug-buttons">
							<button type="button" class="edit button"><?php _e('Edit','Shopp'); ?></button><?php if (!empty($Category->id)): ?><a href="<?php echo esc_url(shopp($Category,'get-url')); ?>" id="view-product" class="view button"><?php _e('View','Shopp'); ?></a><?php endif; ?></span>
						<span id="editor-slug-buttons">
							<button type="button" class="save button"><?php _e('Save','Shopp'); ?></button> <button type="button" class="cancel button"><?php _e('Cancel','Shopp'); ?></button>
						</span>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
				<?php
					$media_buttons = ( defined('SHOPP_EDITOR_MEDIA_BTNS') && SHOPP_EDITOR_MEDIA_BTNS );
					wp_editor($Category->description, 'content', array( 'media_buttons' => $media_buttons ));
					wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				?>
				</div>

			<?php
			do_meta_boxes('shopp_page_shopp-category', 'normal', $Category);
			do_meta_boxes('shopp_page_shopp-category', 'advanced', $Category);
			?>

			</div>
			</div>
			<div class="clear">&nbsp;</div>
		</div> <!-- #poststuff -->
	</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
var flashuploader = <?php echo ($uploader == 'flash' && !(false !== strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mac') && apache_mod_loaded('mod_security')))?'true':'false'; ?>,
	category = <?php echo (!empty($Category->id))?$Category->id:'false'; ?>,
	product = false,
	details = <?php echo json_encode($Category->specs) ?>,
	priceranges = <?php echo json_encode($Category->priceranges) ?>,
	options = <?php echo json_encode($Category->options) ?>,
	prices = <?php echo json_encode($Category->prices) ?>,
	uidir = '<?php echo SHOPP_ADMIN_URI; ?>',
	siteurl = '<?php bloginfo('url'); ?>',
	adminurl = '<?php echo admin_url(); ?>',
	canonurl = '<?php echo trailingslashit(Shopp::url( '' != get_option('permalink_structure') ? get_class_property('ProductCategory','namespace') : $Category->taxonomy.'=' )); ?>',
	ajaxurl = adminurl+'admin-ajax.php',
	addcategory_url = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "shopp-ajax_add_category"); ?>',
	editslug_url = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "wp_ajax_shopp_edit_slug"); ?>',
	fileverify_url = '<?php echo wp_nonce_url(admin_url()."admin-ajax.php", "shopp-ajax_verify_file"); ?>',
	adminpage = '<?php echo $this->Admin->pagename('categories'); ?>',
	request = <?php echo json_encode(stripslashes_deep($_GET)); ?>,
	worklist = <?php echo json_encode($this->categories(true)); ?>,
	filesizeLimit = <?php echo wp_max_upload_size(); ?>,
	priceTypes = <?php echo json_encode($priceTypes) ?>,
	billPeriods = <?php echo json_encode($billPeriods) ?>,
	weightUnit = '<?php echo shopp_setting('weight_unit'); ?>',
	dimensionUnit = '<?php echo shopp_setting('dimension_unit'); ?>',
	dimensionsRequired = <?php echo $Shopp->Shipping->dimensions?'true':'false'; ?>,
	storage = '<?php echo shopp_setting('product_storage'); ?>',
	productspath = '<?php /* realpath needed for relative paths */ chdir(WP_CONTENT_DIR); echo addslashes(trailingslashit(sanitize_path(realpath(shopp_setting('products_path'))))); ?>',
	imageupload_debug = <?php echo (defined('SHOPP_IMAGEUPLOAD_DEBUG') && SHOPP_IMAGEUPLOAD_DEBUG)?'true':'false'; ?>,
	fileupload_debug = <?php echo (defined('SHOPP_FILEUPLOAD_DEBUG') && SHOPP_FILEUPLOAD_DEBUG)?'true':'false'; ?>,

	// Warning/Error Dialogs
	DELETE_IMAGE_WARNING = "<?php _e('Are you sure you want to delete this category image?','Shopp'); ?>",
	SERVER_COMM_ERROR = "<?php _e('There was an error communicating with the server.','Shopp'); ?>",

	// Translatable dynamic interface labels
	NEW_DETAIL_DEFAULT = "<?php _e('Detail Name','Shopp'); ?>",
	NEW_OPTION_DEFAULT = "<?php _e('New Option','Shopp'); ?>",
	FACETED_DISABLED = "<?php _e('Faceted menu disabled','Shopp'); ?>",
	FACETED_AUTO = "<?php _e('Build faceted menu automatically','Shopp'); ?>",
	FACETED_RANGES = "<?php _e('Build as custom number ranges','Shopp'); ?>",
	FACETED_CUSTOM = "<?php _e('Build from preset options','Shopp'); ?>",
	ADD_IMAGE_BUTTON_TEXT = "<?php _e('Add New Image','Shopp'); ?>",
	SAVE_BUTTON_TEXT = "<?php _e('Save','Shopp'); ?>",
	CANCEL_BUTTON_TEXT = "<?php _e('Cancel','Shopp'); ?>",
	OPTION_MENU_DEFAULT = "<?php _e('Option Menu','Shopp'); ?>",
	NEW_OPTION_DEFAULT = "<?php _e('New Option','Shopp'); ?>",

	UPLOAD_FILE_BUTTON_TEXT = "<?php _e('Upload&nbsp;File','Shopp'); ?>",
	TYPE_LABEL = "<?php _e('Type','Shopp'); ?>",
	PRICE_LABEL = "<?php _e('Price','Shopp'); ?>",
	AMOUNT_LABEL = "<?php _e('Amount','Shopp'); ?>",
	SALE_PRICE_LABEL = "<?php _e('Sale Price','Shopp'); ?>",
	NOT_ON_SALE_TEXT = "<?php _e('Not on Sale','Shopp'); ?>",
	NOTAX_LABEL = "<?php _e('Not Taxed','Shopp'); ?>",
	SHIPPING_LABEL = "<?php _e('Shipping','Shopp'); ?>",
	FREE_SHIPPING_TEXT = "<?php _e('Free Shipping','Shopp'); ?>",
	WEIGHT_LABEL = <?php _jse('Weight','Shopp'); ?>,
	LENGTH_LABEL = <?php _jse('Length','Shopp'); ?>,
	WIDTH_LABEL = <?php _jse('Width','Shopp'); ?>,
	HEIGHT_LABEL = <?php _jse('Height','Shopp'); ?>,
	DIMENSIONAL_WEIGHT_LABEL = <?php _jse('3D Weight','Shopp'); ?>,
	SHIPFEE_LABEL = "<?php _e('Handling Fee','Shopp'); ?>",
	SHIPFEE_XTRA = "<?php _e('Amount added to shipping costs for each unit ordered (for handling costs, etc)','Shopp'); ?>",
	INVENTORY_LABEL = "<?php _e('Inventory','Shopp'); ?>",
	NOT_TRACKED_TEXT = "<?php _e('Not Tracked','Shopp'); ?>",
	IN_STOCK_LABEL = "<?php _e('In Stock','Shopp'); ?>",
	SKU_LABEL = "<?php _e('SKU','Shopp'); ?>",
	SKU_LABEL_HELP = "<?php _e('Stock Keeping Unit','Shopp'); ?>",
	SKU_XTRA = "<?php _e('Enter a unique stock keeping unit identification code.','Shopp'); ?>",
	DONATIONS_VAR_LABEL = "<?php _e('Accept variable amounts','Shopp'); ?>",
	DONATIONS_MIN_LABEL = "<?php _e('Amount required as minimum','Shopp'); ?>",
	BILLCYCLE_LABEL = <?php _jse('Billing Cycle','Shopp'); ?>,
	TRIAL_LABEL = <?php _jse('Trial Period','Shopp'); ?>,
	NOTRIAL_TEXT = <?php _jse('No trial period','Shopp'); ?>,
	TIMES_LABEL = <?php _jse('times','Shopp'); ?>,
	MEMBERSHIP_LABEL = <?php _jse('Membership','Shopp'); ?>,
	PRODUCT_DOWNLOAD_LABEL = "<?php _e('Product Download','Shopp'); ?>",
	NO_PRODUCT_DOWNLOAD_TEXT = "<?php _e('No product download','Shopp'); ?>",
	NO_DOWNLOAD = "<?php _e('No download file','Shopp'); ?>",
	UNKNOWN_UPLOAD_ERROR = "<?php _e('An unknown error occurred. The upload could not be saved.','Shopp'); ?>",
	DEFAULT_PRICELINE_LABEL = "<?php _e('Price & Delivery','Shopp'); ?>",
	FILE_NOT_FOUND_TEXT = "<?php _e('The file you specified could not be found.','Shopp'); ?>",
	FILE_NOT_READ_TEXT = "<?php _e('The file you specified is not readable and cannot be used.','Shopp'); ?>",
	FILE_ISDIR_TEXT = "<?php _e('The file you specified is a directory and cannot be used.','Shopp'); ?>",
	IMAGE_DETAILS_TEXT = "<?php _e('Image Details','Shopp'); ?>",
	IMAGE_DETAILS_TITLE_LABEL = "<?php _e('Title','Shopp'); ?>",
	IMAGE_DETAILS_ALT_LABEL = "<?php _e('Alt','Shopp'); ?>",
	IMAGE_DETAILS_DONE = "<?php _e('OK','Shopp'); ?>",
	IMAGE_DETAILS_CROP_LABEL = "<?php _e('Cropped images','Shopp'); ?>";
/* ]]> */
</script>
<div class="wrap shopp">

	<div class="icon32"></div>
	<h2><?php Shopp::_e('Category Editor'); ?></h2>

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
						<div id="edit-slug-box"><strong><?php Shopp::_e('Permalink'); ?>:</strong>
						<span id="sample-permalink"><?php echo $permalink; ?><span id="editable-slug" title="<?php Shopp::_e('Click to edit this part of the permalink'); ?>"><?php echo esc_attr($Category->slug); ?></span><span id="editable-slug-full"><?php echo esc_attr($Category->slug); ?></span>/</span>
						<span id="edit-slug-buttons">
							<button type="button" class="edit button"><?php Shopp::_e('Edit'); ?></button><?php if (!empty($Category->id)): ?><a href="<?php echo esc_url(shopp($Category,'get-url')); ?>" id="view-product" class="view button"><?php Shopp::_e('View'); ?></a><?php endif; ?></span>
						<span id="editor-slug-buttons">
							<button type="button" class="save button"><?php Shopp::_e('Save'); ?></button> <button type="button" class="cancel button"><?php Shopp::_e('Cancel'); ?></button>
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
	details = <?php echo isset($Category->specs) ? json_encode($Category->specs) : '' ?>,
	priceranges = <?php echo isset($Category->priceranges) ? json_encode($Category->priceranges) : '' ?>,
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
	DELETE_IMAGE_WARNING = "<?php Shopp::_e('Are you sure you want to delete this category image?'); ?>",
	SERVER_COMM_ERROR = "<?php Shopp::_e('There was an error communicating with the server.'); ?>",

	// Translatable dynamic interface labels
	NEW_DETAIL_DEFAULT = "<?php Shopp::_e('Detail Name'); ?>",
	NEW_OPTION_DEFAULT = "<?php Shopp::_e('New Option'); ?>",
	FACETED_DISABLED = "<?php Shopp::_e('Faceted menu disabled'); ?>",
	FACETED_AUTO = "<?php Shopp::_e('Build faceted menu automatically'); ?>",
	FACETED_RANGES = "<?php Shopp::_e('Build as custom number ranges'); ?>",
	FACETED_CUSTOM = "<?php Shopp::_e('Build from preset options'); ?>",
	ADD_IMAGE_BUTTON_TEXT = "<?php Shopp::_e('Add New Image'); ?>",
	SAVE_BUTTON_TEXT = "<?php Shopp::_e('Save'); ?>",
	CANCEL_BUTTON_TEXT = "<?php Shopp::_e('Cancel'); ?>",
	OPTION_MENU_DEFAULT = "<?php Shopp::_e('Option Menu'); ?>",
	NEW_OPTION_DEFAULT = "<?php Shopp::_e('New Option') ?>",

	UPLOAD_FILE_BUTTON_TEXT = "<?php Shopp::_e('Upload File'); ?>",
	TYPE_LABEL = "<?php Shopp::_e('Type'); ?>",
	PRICE_LABEL = "<?php Shopp::_e('Price'); ?>",
	AMOUNT_LABEL = "<?php Shopp::_e('Amount'); ?>",
	SALE_PRICE_LABEL = "<?php Shopp::_e('Sale Price'); ?>",
	NOT_ON_SALE_TEXT = "<?php Shopp::_e('Not on Sale'); ?>",
	NOTAX_LABEL = "<?php Shopp::_e('Not Taxed'); ?>",
	SHIPPING_LABEL = "<?php Shopp::_e('Shipping'); ?>",
	FREE_SHIPPING_TEXT = "<?php Shopp::_e('Free Shipping'); ?>",
	WEIGHT_LABEL = <?php Shopp::_jse('Weight'); ?>,
	LENGTH_LABEL = <?php Shopp::_jse('Length'); ?>,
	WIDTH_LABEL = <?php Shopp::_jse('Width'); ?>,
	HEIGHT_LABEL = <?php Shopp::_jse('Height'); ?>,
	DIMENSIONAL_WEIGHT_LABEL = <?php Shopp::_jse('3D Weight'); ?>,
	SHIPFEE_LABEL = "<?php Shopp::_e('Handling Fee'); ?>",
	SHIPFEE_XTRA = "<?php Shopp::_e('Amount added to shipping costs for each unit ordered (for handling costs, etc)'); ?>",
	INVENTORY_LABEL = "<?php Shopp::_e('Inventory'); ?>",
	NOT_TRACKED_TEXT = "<?php Shopp::_e('Not Tracked'); ?>",
	IN_STOCK_LABEL = "<?php Shopp::_e('In Stock'); ?>",
	SKU_LABEL = "<?php Shopp::_e('SKU'); ?>",
	SKU_LABEL_HELP = "<?php Shopp::_e('Stock Keeping Unit'); ?>",
	SKU_XTRA = "<?php Shopp::_e('Enter a unique stock keeping unit identification code.'); ?>",
	DONATIONS_VAR_LABEL = "<?php Shopp::_e('Accept variable amounts'); ?>",
	DONATIONS_MIN_LABEL = "<?php Shopp::_e('Amount required as minimum'); ?>",
	BILLCYCLE_LABEL = <?php Shopp::_jse('Billing Cycle'); ?>,
	TRIAL_LABEL = <?php Shopp::_jse('Trial Period'); ?>,
	NOTRIAL_TEXT = <?php Shopp::_jse('No trial period'); ?>,
	TIMES_LABEL = <?php Shopp::_jse('times'); ?>,
	MEMBERSHIP_LABEL = <?php Shopp::_jse('Membership'); ?>,
	PRODUCT_DOWNLOAD_LABEL = "<?php Shopp::_e('Product Download'); ?>",
	NO_PRODUCT_DOWNLOAD_TEXT = "<?php Shopp::_e('No product download'); ?>",
	NO_DOWNLOAD = "<?php Shopp::_e('No download file'); ?>",
	UNKNOWN_UPLOAD_ERROR = "<?php Shopp::_e('An unknown error occurred. The upload could not be saved.'); ?>",
	DEFAULT_PRICELINE_LABEL = "<?php Shopp::_e('Price & Delivery'); ?>",
	FILE_NOT_FOUND_TEXT = "<?php Shopp::_e('The file you specified could not be found.'); ?>",
	FILE_NOT_READ_TEXT = "<?php Shopp::_e('The file you specified is not readable and cannot be used.'); ?>",
	FILE_ISDIR_TEXT = "<?php Shopp::_e('The file you specified is a directory and cannot be used.'); ?>",
	IMAGE_DETAILS_TEXT = "<?php Shopp::_e('Image Details'); ?>",
	IMAGE_DETAILS_TITLE_LABEL = "<?php Shopp::_e('Title'); ?>",
	IMAGE_DETAILS_ALT_LABEL = "<?php Shopp::_e('Alt'); ?>",
	IMAGE_DETAILS_DONE = "<?php Shopp::_e('OK'); ?>",
	IMAGE_DETAILS_CROP_LABEL = "<?php Shopp::_e('Cropped images'); ?>";
/* ]]> */
</script>
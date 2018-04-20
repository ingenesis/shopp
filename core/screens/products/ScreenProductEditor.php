<?php
/**
 * ScreenProductEditor.php
 *
 * Screen controller for the catalog products editor screen
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenProductEditor extends ShoppScreenController {

	/**
	 * Load the requested product for the editor
	 *
	 * @since 1.4
	 * @return ShoppProduct The loaded product based on the request
	 **/
	public function load () {
		global $post;

		$id = $this->request('id');
		$Product = new ShoppProduct($id);
		$Product->load_data();
		ShoppProduct($Product);

		// Adds CPT compatibility support for third-party plugins/themes
		if ( is_null($post) )
			$post = get_post($Shopp->Product->id);

		return ShoppProduct();
	}

	/**
	 * Setup the screen UI for the product editor
	 *
	 * @return void
	 **/
	public function screen () {
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ( empty($Shopp->Product) ) {
			$Product = new ShoppProduct();
			$Product->status = "publish";
		} else $Product = $Shopp->Product;

		$Product->slug = apply_filters('editable_slug', $Product->slug);
		$permalink = trailingslashit(Shopp::url());

		$pricetypes = ShoppPrice::types();
		$billperiods = ShoppPrice::periods();

		$workflows = array(
			'continue' => Shopp::__('Continue Editing'),
			'close'	=> Shopp::__('Products Manager'),
			'new'	  => Shopp::__('New Product'),
			'next'	 => Shopp::__('Edit Next'),
			'previous' => Shopp::__('Edit Previous')
		);

		$taglist = array();
		foreach ( $Product->tags as $tag )
			$taglist[] = $tag->name;

		if ( $Product->id && ! empty($Product->images) ) {
			$ids = join(',', array_keys($Product->images));
			$CoverImage = reset($Product->images);
			$image_table = $CoverImage->_table;
			$Product->cropped = sDB::query("SELECT * FROM $image_table WHERE context='image' AND type='image' AND '2'=SUBSTRING_INDEX(SUBSTRING_INDEX(name,'_',4),'_',-1) AND parent IN ($ids)",'array','index','parent');
		}

		$shiprates = shopp_setting('shipping_rates');
		if ( ! empty($shiprates) )
			ksort($shiprates);

		//$_POST['action'] = add_query_arg(array_merge($_GET, array('page' => ShoppAdmin::pagename('products'))), admin_url('admin.php'));
		$post_type = ShoppProduct::posttype();

		// Re-index menu options to maintain order in JS #2930
		self::keyoptions($Product->options);

		do_action('add_meta_boxes', ShoppProduct::$posttype, $Product);
		do_action('add_meta_boxes_' . ShoppProduct::$posttype, $Product);

		do_action('do_meta_boxes', ShoppProduct::$posttype, 'normal', $Product);
		do_action('do_meta_boxes', ShoppProduct::$posttype, 'advanced', $Product);
		do_action('do_meta_boxes', ShoppProduct::$posttype, 'side', $Product);

		include $this->ui('editor.php', array($post_type, $workflows, $permalink, $pricetypes, $billperiods));
	}

	/**
	 * Re-key product options to maintain order in JS @see #2930
	 *
	 * @since 1.4
	 * @param array $options The array of product options
	 * @return void
	 **/
	protected static function keyoptions ( &$options ) {
		if ( isset($options['v']) || isset($options['a']) ) {
			$optiontypes = array_keys($options);
			foreach ( $optiontypes as $type ) {
				foreach( $options[ $type ] as $id => $menu ) {
					$options[ $type ][ $type . $id ] = $menu;
					$options[ $type ][ $type . $id ]['options'] = array_values($menu['options']);
					unset($options[ $type ][ $id ]);
				}
			}
		} else {
			foreach ( $options as &$menu )
				$menu['options'] = array_values($menu['options']);
		}
	}

	/**
	 * Handles saving updates from the product editor
	 *
	 * Saves all product related information which includes core product data
	 * and supporting elements such as images, digital downloads, tags,
	 * assigned categories, specs and pricing variations.
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param ShoppProduct $Product
	 * @return void
	 **/
	public function save ( ShoppProduct &$Product ) {
		check_admin_referer('shopp-save-product');

		if ( ! current_user_can('shopp_products') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		ShoppSettings()->saveform(); // Save workflow setting

		$Update = new ShoppAdminProductUpdate($Product);

		$Update->status();
		$Update->updates();

		do_action('shopp_pre_product_save');
		$Product->save();

		$Update->prices();
		$Product->load_sold($Product->id); // Refresh accurate product sales stats
		$Product->sumup();
		$Update->trimprices(); // Must occur after sumup()

		$Update->images();
		$Update->taxonomies();
		$Update->specs();
		$Update->meta();

		// Reload product to refresh all of the saved data
		// so everything is fresh for shopp_product_saved
		$Product = $this->load();
		$Product->load_data();

		do_action_ref_array('shopp_product_saved', array(&$Product));
	}

	/**
	 * AJAX behavior to process uploaded files intended as digital product downloads
	 *
	 * Handles processing a file upload from a temporary file to a
	 * the correct storage container (DB, file system, etc)
	 *
	 * @since 1.3
	 * @param array $file File upload data
	 * @param array $data Posted data
	 * @return string JSON encoded result with DB id, filename, type & size
	 **/
	public static function downloads ($file, $data) {

		set_time_limit(0);        // Try to prevent timeouts
		self::uploaderrs($file);  // Catch any upload errors before proceeding

		$stagedfile = $file['tmp_name'];

		// Handle chunked file uploads
		if ( isset($data['dzchunkindex']) && isset($data['dztotalchunkcount']) ) {
			$PartialUpload = new ShoppPartialUpload($file, $data);
			$stagedfile = $PartialUpload->process();
		}

		FileAsset::mimetypes();

		// Save the uploaded file
		$DownloadFile = new ProductDownload();
		$DownloadFile->parent = 0;
		$DownloadFile->context = "price";
		$DownloadFile->type = "download";
		$DownloadFile->name = $file['name'];
		$DownloadFile->filename = $DownloadFile->name;

		$mimedata = wp_check_filetype_and_ext($stagedfile, $DownloadFile->name);
		$DownloadFile->mime = ! empty($mimedata['type']) ? $mimedata['type'] : 'application/octet-stream';
		if ( ! empty($mimedata['proper_filename']) )
			$DownloadFile->name = $DownloadFile->filename = $mimedata['proper_filename'];

		$DownloadFile->size = filesize($stagedfile);
		$DownloadFile->store($stagedfile, 'file');

		$Error = ShoppErrors()->code('storage_engine_save');
		if ( ! empty($Error) )
            wp_die($Error->message(true), 500);

		$DownloadFile->save();

		do_action('add_product_download', $DownloadFile, $file);

		header('Content-Type: application/json');
        wp_die(json_encode(array(
			'id' => $DownloadFile->id,
			'name' => stripslashes($DownloadFile->name),
			'type' => $DownloadFile->mime,
			'size' => $DownloadFile->size
		)));
	}

	/**
	 * Provide upload errors back to the browser
	 *
	 * @since 1.4
	 * @param array $file The uploaded file data
	 * @return boolean False if no errors, exits if there are errors
	 **/
    private static function uploaderrs ($file) {
		$error = array();

		if ( ! empty($file['error']) )
			$error[500] = ShoppLookup::errors('uploads', $file['error']);

		if ( ! is_uploaded_file($file['tmp_name']) )
			$error[500] = Shopp::__('The file could not be saved because the uploaded file was not found on the server.');

		if ( ! is_readable($file['tmp_name']) )
			$error[500] = Shopp::__('The file could not be saved because the web server does not have permission to read the upload from the server\'s temporary directory.');

		if ( 0 == $file['size'] )
			$error[400] = Shopp::__('The file could not be saved because the selected file is empty.');

		if ( 0 == filesize($file['tmp_name']) )
			$error[500] = Shopp::__('The file could not be saved because the uploaded file is empty.');

		if ( ! empty($error) )
            wp_die(current($error), key($error));

        return false;
    }

	/**
	 * AJAX behavior to process uploaded images
	 *
	 * @since 1.3
	 * @return string JSON encoded result with thumbnail id and src
	 **/
	public static function images ($file, $parent = false, $context = false) {

		$ContextClasses = array(
			'category' => 'CategoryImage',
			'product' => 'ProductImage'
		);

		if ( ! in_array(strtolower($context), array_keys($ContextClasses)) )
            wp_die(Shopp::__('The file could not be saved because the request did not specify whether it is a product or a category image.'), 400);

        self::uploaderrs($file);

		// Save the source image
		$Image = new $ContextClasses[ $context ]();
		$Image->parent = $parent;
		$Image->type = 'image';
		$Image->name = 'original';
		$Image->filename = $file['name'];
		list($Image->width, $Image->height, $Image->mime, $Image->attr) = getimagesize($file['tmp_name']);
		$Image->mime = image_type_to_mime_type($Image->mime);
		$Image->size = filesize($file['tmp_name']);

		if ( ! $Image->unique() )
			wp_die(Shopp::__('Server error: the image already exists and a new file could not be generated.'), 500);

		$Image->store($file['tmp_name'], 'upload');
		$Error = ShoppErrors()->code('storage_engine_save');
		if ( ! empty($Error) )
            wp_die($Error->message(true), 500);

		$Image->save();

		if ( empty($Image->id) )
            wp_die(Shopp::__('The image reference was not saved to the database.'), 500);

		header('Content-Type: application/json');
		wp_die(json_encode(array('id' => $Image->id)));
	}


	/**
	 * Enqueue scripts and style dependencies
	 *
	 * @since 1.2
	 * @return void
	 **/
	public function assets () {
		wp_enqueue_script('jquery-ui-draggable');
		wp_enqueue_script('postbox');
		wp_enqueue_script('wp-lists');

		if ( user_can_richedit() ) {
			wp_enqueue_script('editor');
			wp_enqueue_script('quicktags');
			add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 20 );
		}

		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('editors');
		shopp_enqueue_script('scalecrop');
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('product-editor');
		shopp_enqueue_script('priceline');
		shopp_enqueue_script('dropzone');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('suggest');
		shopp_enqueue_script('search-select');

		do_action('shopp_product_editor_scripts');
	}

	/**
	 * Provides overall layout for the product editor interface
	 *
	 * Makes use of WordPress postboxes to generate panels (box) content
	 * containers that are customizable with drag & drop, collapsable, and
	 * can be toggled to be hidden or visible in the interface.
	 *
	 * @author Jonathan Davis
	 * @return
	 **/
	public function layout () {
		$Product = $this->Model;
		$Product->load_data();

		new ShoppAdminProductSaveBox($this, 'side', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));

		// Load all Shopp product taxonomies
		foreach ( get_object_taxonomies(ShoppProduct::$posttype) as $taxonomy_name ) {
			$taxonomy = get_taxonomy($taxonomy_name);
			$label = $taxonomy->labels->name;

			if ( is_taxonomy_hierarchical($taxonomy_name) )
				new ShoppAdminProductCategoriesBox(ShoppProduct::$posttype, 'side', 'core', array( 'Product' => $Product, 'taxonomy' => $taxonomy_name, 'label' => $label ));
			else new ShoppAdminProductTaggingBox(ShoppProduct::$posttype, 'side', 'core', array( 'Product' => $Product, 'taxonomy' => $taxonomy_name, 'label' => $label ));

		}

		new ShoppAdminProductSettingsBox($this, 'side', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductSummaryBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductDetailsBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductImagesBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
		new ShoppAdminProductPricingBox($this, 'normal', 'core', array('Product' => $Product, 'posttype' => ShoppProduct::$posttype));
	}

}
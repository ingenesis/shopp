<?php
/**
 * ScreenCategoryEditor.php
 *
 * Screen controller for the category editor screen.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenCategoryEditor extends ShoppScreenController {

	/**
	 * Load scripts needed for the user interface
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets () {
		wp_enqueue_script('postbox');
		if ( user_can_richedit() ) {
			wp_enqueue_script('editor');
			wp_enqueue_script('quicktags');
			add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 20 );
		}

		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('editors');
		shopp_enqueue_script('category-editor');
		shopp_enqueue_script('priceline');
		shopp_enqueue_script('ocupload');
		shopp_enqueue_script('dropzone');
		shopp_enqueue_script('jquery-tmpl');

		do_action('shopp_category_editor_scripts');
	}

	/**
	 * Provides the core interface layout for the category editor
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function layout () {

		$Category = $this->Model;

		new ShoppAdminCategorySaveBox($this, 'side', 'core', array('Category' => $Category));
		new ShoppAdminCategorySettingsBox($this, 'side', 'core', array('Category' => $Category));

		new ShoppAdminCategoryImagesBox($this, 'normal', 'core', array('Category' => $Category));
		new ShoppAdminCategoryTemplatesBox($this, 'normal', 'core', array('Category' => $Category));

	}

	/**
	 * Load a requested category for the editor
	 *
	 * Handles requested category ID by default, or a blank new category object,
	 * or a workflow requested category ID.
	 *
	 * @since 1.4
	 * @return ProductCategory The loaded category
	 */
	public function load () {

		// Load the requested category ID by default
		$id = (int)$this->request('id');

		// Override to create a new category
		if ( $this->request('new') )
			$id = false;

		// Override with workflow ID
		if ( $this->request('workflow') )
			$id = $this->request('workflow');

		$Category = new ProductCategory($id);

		$meta = array('specs', 'priceranges', 'options', 'prices');
		foreach ( $meta as $prop )
			if ( ! isset($Category->$prop) ) $Category->$prop = array();

		// $Category = ShoppCollection();
		// if ( empty($Category) ) $Category = new ProductCategory();

		$Category->load_meta();
		$Category->load_images();

		return $Category;
	}

	/**
	 * Setup the user interface for the category editor
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function screen () {
		global $CategoryImages;
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$Category = $this->Model;

		$Price = new ShoppPrice();
		$priceTypes = ShoppPrice::types();
		$billPeriods = ShoppPrice::periods();

		// Build permalink for slug editor
		$permalink = trailingslashit(Shopp::url()) . "category/";
		$Category->slug = apply_filters('editable_slug', $Category->slug);

		$uploader = shopp_setting('uploader_pref');
		if (!$uploader) $uploader = 'flash';

		do_action('add_meta_boxes', ProductCategory::$taxon, $Category);
		do_action('add_meta_boxes_'.ProductCategory::$taxon, $Category);

		do_action('do_meta_boxes', ProductCategory::$taxon, 'normal', $Category);
		do_action('do_meta_boxes', ProductCategory::$taxon, 'advanced', $Category);
		do_action('do_meta_boxes', ProductCategory::$taxon, 'side', $Category);

		include $this->ui('category.php');
	}

	/**
	 * Overload Screen process() save calls
	 *
	 * This is a no-op method to allow ShoppAdminCategories::save() to handle saving
	 * during ShoppAdminCategories::workflow()
	 *
	 * @since 1.4
	 * @return void
	 */
	public function save () {
		return;
	}

}
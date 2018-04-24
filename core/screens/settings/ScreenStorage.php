<?php
/**
 * ScreenStorage.php
 *
 * Storage settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenStorage extends ShoppSettingsScreenController {

	/**
	 * Enqueue required script/style assets for the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('storage');
	}

	/**
	 * Setup the layout for the screen
	 * 
	 * This is used to initialize anything before the screen UI is rendered.
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function layout() {
		$Storage = Shopp::object()->Storage;
		$Storage->settings();
		$Storage->ui();
	}

	/**
	 * Handle saving form updates
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {
		$Storage = Shopp::object()->Storage;
		shopp_set_formsettings();

		// Re-initialize Storage Engines with new settings
		$Storage->settings();

		if ( ! empty($_POST['rebuild']) ) {
			$assets = ShoppDatabaseObject::tablename(ProductImage::$table);
			$query = "DELETE FROM $assets WHERE context='image' AND type='image'";
			if ( sDB::query($query) )
				$this->notice(Shopp::__('All cached images have been cleared.'));
		}

		$this->notice(Shopp::__('Shopp system settings saved.'));

	}

	/**
	 * Render the screen
	 * 
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {

		$Storage = Shopp::object()->Storage;
		$Storage->settings();	// Load all installed storage engines for settings UIs

		// Build the storage options menu
		$storage = $engines = $storageset = array();
		foreach ( $Storage->active as $module ) {
			$storage[ $module->module ] = $module->name;
			$engines[ $module->module ] = sanitize_title_with_dashes($module->module);
			$storageset[ $module->module ] = $Storage->get($module->module)->settings;
		}

		$Storage->ui();		// Setup setting UIs

		$ImageStorage = false;
		$DownloadStorage = false;
		if ( isset($_POST['image-settings']) )
			$ImageStorage = $Storage->get(shopp_setting('image_storage'));

		if ( isset($_POST['download-settings']) )
			$DownloadStorage = $Storage->get(shopp_setting('product_storage'));

		add_action('shopp_storage_engine_settings', array($Storage, 'templates'));

		include $this->ui('storage.php');
	}

}
<?php
/**
 * Images.php
 *
 * Image settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since	 @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Screen controller for the image settings screen
 *
 * @since 1.5
 * @package Shopp/Admin/Settings
 **/
class ShoppScreenImages extends ShoppSettingsScreenController {

	/**
	 * Lookout for image size delete requests or defer to the parent's posted() method
	 *
	 * @return bool
	 */
	public function posted() {
		if ( $this->request( 'delete' ) ) return true;
		else return parent::posted();
	}

	/**
	 * Enqueue script or style assets needed for the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('imageset');
		shopp_localize_script( 'imageset', '$is', array(
			'confirm' => __('Are you sure you want to remove this image preset?','Shopp'),
		));
	}

	/**
	 * Register processing operation methods
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function ops() {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
		add_action('shopp_admin_settings_ops', array($this, 'delete') );
	}

	/**
	 * Handle saving image setting changes from form updates
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {
		$updates = $this->form();
		if ( empty($updates['name']) ) return;

		$ImageSetting = new ShoppImageSetting($updates['id']);

		$updates['name']	= sanitize_title_with_dashes($updates['name']);
		$updates['sharpen'] = floatval(str_replace('%', '', $updates['sharpen']));

		$ImageSetting->updates($updates);
		$ImageSetting->save();

		$this->notice(Shopp::__('Image setting &quot;%s&quot; saved.', $updates['name']));
	}

	/**
	 * Process deleting image settings
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function delete() {
		$requests = $this->form('selected');
		if ( empty($requests) )
			$requests = array( $this->request('delete') );

		$requests = array_filter($requests);

		if ( empty($requests) ) return;

		$deleted = 0;
		foreach ( $requests as $delete ) {
			$Record = new ShoppImageSetting( (int) $delete );
			if ( $Record->delete() )
				$deleted++;
		}

		if ( $deleted > 0 )
			$this->notice(Shopp::_n('%d setting deleted.', '%d settings deleted.', $deleted));

	}

	/**
	 * Setup the layout for the screen
	 * 
	 * This is used to initialize any metaboxes or tables.
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function layout() {
		$this->table('ShoppScreenImagesTable');
	}

	/**
	 * Render the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {

		$fit_menu = ShoppImageSetting::fit_menu();
		$quality_menu = ShoppImageSetting::quality_menu();

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('images.php');

	}

}
<?php
/**
 * ScreenShipmentBoxes.php
 *
 * Shipment boxes screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenShipmentBoxes extends ShoppSettingsScreenController {

	/**
	 * Enqueue script or style assets needed for the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('boxesset');
		shopp_localize_script( 'boxesset', '$is', array(
			'confirm' => __('Are you sure you want to remove this box setting?','Shopp'),
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
	 * Update settings from the form
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {
		$updates = $this->form();
		if ( empty($updates['name']) ) return;

		$ImageSetting = new ShoppImageSetting($updates['id']);

		$updates['name']    = sanitize_title_with_dashes($updates['name']);
		$updates['sharpen'] = floatval(str_replace('%', '', $updates['sharpen']));

		$ImageSetting->updates($updates);
		$ImageSetting->save();

		$this->notice(Shopp::__('Image setting &quot;%s&quot; saved.', $updates['name']));
	}

	/**
	 * Handle deleting a shipment box setting
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
		$this->table('ShoppShipmentBoxesTable');
	}

	/**
	 * Render the screen UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {

		$fit_menu = ShoppImageSetting::fit_menu();
		$quality_menu = ShoppImageSetting::quality_menu();

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('shipboxes.php');

	}

}
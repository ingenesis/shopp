<?php
/**
 * Pages.php
 *
 * Pages settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPages extends ShoppSettingsScreenController {

	/**
	 * Enqueue required script and style assets for the UI
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('pageset');
	}

	/**
	 * Setup the layout for the screen
	 * 
	 * This is used to initialize any metaboxes or tables.
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function layout() {
		$this->table('ShoppScreenPagesTable');
	}

	/**
	 * Process and save form updates
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function updates() {
		$CatalogPage = ShoppPages()->get('catalog');
		$catalog_slug = $CatalogPage->slug();
		$defaults = ShoppPages()->settings();
		$this->form['storefront_pages'] = array_merge($defaults, $this->form('storefront_pages'));
		shopp_set_formsettings();

		// Re-register page, collection, taxonomies and product rewrites
		// so that the new slugs work immediately
		$Shopp = Shopp::object();
		$Shopp->pages();
		$Shopp->collections();
		$Shopp->taxonomies();
		$Shopp->products();

		// If the catalog slug changes
		// $hardflush is false (soft flush... plenty of fiber, no .htaccess update needed)
		$hardflush = ( ShoppPages()->baseslug() != $catalog_slug );
		flush_rewrite_rules($hardflush);
	}

	/**
	 * Render the screen UI
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function screen() {

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('pages.php');

	}

	/**
	 * Provide a clean URL for this screen
	 *
	 * @since 1.4
	 * @param array $params A list of parameters to set for the URL
	 * @return string The proper URL for this screen
	 **/
	public function url( $params = array() ) {
		$url = parent::url($params);
		return remove_query_arg('edit', $url);
	}

}
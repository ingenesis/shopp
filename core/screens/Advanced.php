<?php
/**
 * Advanced.php
 *
 * Advanced settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenAdvanced extends ShoppSettingsScreenController {

	public function assets () {
		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('system');
		shopp_localize_script( 'system', '$sys', array(
			'indexing' => __('Product Indexing','Shopp'),
			'indexurl' => wp_nonce_url(add_query_arg('action','shopp_rebuild_search_index',admin_url('admin-ajax.php')),'wp_ajax_shopp_rebuild_search_index')
		));
	}

	public function screen () {

		if ( ! empty($_POST['save']) ) {

			if ( ! isset($_POST['settings']['error_notifications']) )
				$_POST['settings']['error_notifications'] = array();

			shopp_set_formsettings();

			// Reinitialize Error System
			ShoppErrors()->reporting( (int)shopp_setting('error_logging') );
			ShoppErrorLogging()->loglevel( (int)shopp_setting('error_logging') );
			ShoppErrorNotification()->setup();

			if ( isset($_POST['shopp_services_plugins']) && $this->helper_installed() ) {
				add_option('shopp_services_plugins'); // Add if it doesn't exist
				update_option('shopp_services_plugins', $_POST['shopp_services_plugins']);
			}

			$this->notice(Shopp::__('Advanced settings saved.'));

		} elseif ( ! empty($_POST['rebuild']) ) {
			$assets = ShoppDatabaseObject::tablename(ProductImage::$table);
			$query = "DELETE FROM $assets WHERE context='image' AND type='image'";
			if ( sDB::query($query) )
				$this->notice(Shopp::__('All cached images have been cleared.'));

		} elseif ( ! empty($_POST['resum']) ) {
			$summaries = ShoppDatabaseObject::tablename(ProductSummary::$table);
			$query = "UPDATE $summaries SET modified='" . ProductSummary::RECALCULATE . "'";
			if ( sDB::query($query) )
				$this->notice(Shopp::__('Product summaries are set to recalculate.'));

		} elseif ( isset($_POST['shopp_services_helper']) ) {
			$plugin = 'ShoppServices.php';
			$source = SHOPP_PATH . "/core/library/$plugin";
			$install = WPMU_PLUGIN_DIR . '/' . $plugin;

			if ( false === ( $creds = request_filesystem_credentials($this->url, '', false, false, null) ) )
				return true; // stop the normal page form from displaying

			if ( ! WP_Filesystem($creds) ) { // credentials were no good, ask for them again
				request_filesystem_credentials($this->url, '', false, false, null);
				return true;
			}

			global $wp_filesystem;

			if ( 'install' == $_POST['shopp_services_helper'] ) {

				if ( ! $wp_filesystem->exists($install) ) {
					if ( $wp_filesystem->exists(WPMU_PLUGIN_DIR) || $wp_filesystem->mkdir(WPMU_PLUGIN_DIR, FS_CHMOD_DIR) ) {
						// Install the mu-plugin helper
						$wp_filesystem->copy($source, $install, true, FS_CHMOD_FILE);
					} else $this->notice(Shopp::_mi('The services helper could not be installed because the `mu-plugins` directory could not be created. Check the file permissions of the `%s` directory on the web aserver.', WP_CONTENT_DIR), 'error');
				}

				if ( $wp_filesystem->exists($install) ) {
					shopp_set_setting('shopp_services_helper', 'on');
					$this->notice(Shopp::__('Services helper installed.'));
				} else $this->notice(Shopp::__('The services helper failed to install.'), 'error');

			} elseif ( 'remove' == $_POST['shopp_services_helper'] ) {
				global $wp_filesystem;

				if ( $wp_filesystem->exists($install) )
					$wp_filesystem->delete($install);

				if ( ! $wp_filesystem->exists($install) ) {
					shopp_set_setting('shopp_services_helper', 'off');
					$this->notice(Shopp::__('Services helper uninstalled.'));
				} else {
					$this->notice(Shopp::__('Services helper could not be uninstalled.'), 'error');
				}
			}
		}

		$notifications = shopp_setting('error_notifications');
		if ( empty($notifications) ) $notifications = array();

		$notification_errors = array(
			SHOPP_TRXN_ERR  => Shopp::__('Transaction Errors'),
			SHOPP_AUTH_ERR  => Shopp::__('Login Errors'),
			SHOPP_ADDON_ERR => Shopp::__('Add-on Errors'),
			SHOPP_COMM_ERR  => Shopp::__('Communication Errors'),
			SHOPP_STOCK_ERR => Shopp::__('Inventory Warnings')
		);

		$errorlog_levels = array(
			0               => Shopp::__('Disabled'),
			SHOPP_ERR       => Shopp::__('General Shopp Errors'),
			SHOPP_TRXN_ERR  => Shopp::__('Transaction Errors'),
			SHOPP_AUTH_ERR  => Shopp::__('Login Errors'),
			SHOPP_ADDON_ERR => Shopp::__('Add-on Errors'),
			SHOPP_COMM_ERR  => Shopp::__('Communication Errors'),
			SHOPP_STOCK_ERR => Shopp::__('Inventory Warnings'),
			SHOPP_ADMIN_ERR => Shopp::__('Admin Errors'),
			SHOPP_DB_ERR    => Shopp::__('Database Errors'),
			SHOPP_PHP_ERR   => Shopp::__('PHP Errors'),
			SHOPP_ALL_ERR   => Shopp::__('All Errors'),
			SHOPP_DEBUG_ERR => Shopp::__('Debugging Messages')
		);

		$plugins = get_plugins();
		$service_plugins = get_option('shopp_services_plugins');

		include $this->ui('advanced.php');
	}

	public function helper_installed () {
		$plugins = wp_get_mu_plugins();
		foreach ( $plugins as $plugin )
			if ( false !== strpos($plugin, 'ShoppServices.php') ) return true;
		return false;
	}

	public static function install_services_helper () {
		if ( ! self::filesystemcreds() ) {

		}
	}

	protected static function filesystemcreds () {
		if ( false === ( $creds = request_filesystem_credentials($this->url, '', false, false, null) ) ) {
			return false; // stop the normal page form from displaying
		}

		if ( ! WP_Filesystem($creds) ) { // credentials were no good, ask for them again
			request_filesystem_credentials($this->url, $method, true, false, $form_fields);
			return false;
		}
		return $creds;
	}

} // class ShoppScreenAdvanced
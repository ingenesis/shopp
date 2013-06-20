<?php
/**
 * Support.php
 *
 * Shopp Support class for shopplugin.com resources
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May 2013
 * @license (@see license.txt)
 * @package shopp
 * @since 1.3
 * @subpackage suppport
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppSupport {

	const HOMEPAGE  = 'https://shopplugin.com/';
	const WORKSHOPP = 'https://workshopp.com/';
	const SUPPORT   = 'https://shopplugin.com/support/';
	const FORUMS    = 'https://shopplugin.com/forums/';
	const STORE     = 'https://shopplugin.com/store/';
	const DOCS      = 'https://shopplugin.com/docs/';
	const API       = 'https://shopplugin.com/api/';
	const KB        = 'https://shopplugin.com/kb/';

	/**
	 * Checks for available updates
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of available updates
	 **/
	public static function updates () {

		if ( ! wp_next_scheduled('shopp_check_updates') )
			wp_schedule_event(time(), 'twicedaily', 'shopp_check_updates');

		global $pagenow;
		if ( is_admin()
			&& 'plugins.php' == $pagenow
			&& isset($_GET['action'])
			&& 'deactivate' == $_GET['action']) return array();

		$updates = new StdClass();
		if (function_exists('get_site_transient')) $plugin_updates = get_site_transient('update_plugins');
		else $plugin_updates = get_transient('update_plugins');

		switch ( current_filter() ) {
			case 'load-update-core.php': $timeout = 60; break; // 1 minute
			case 'load-plugins.php': // 1 hour
			case 'load-update.php': $timeout = 3600; break;
			default: $timeout = 43200; // 12 hours
		}

		$justchecked = isset( $plugin_updates->last_checked_shopp ) && $timeout > ( time() - $plugin_updates->last_checked_shopp );
		$changed = isset($plugin_updates->response[SHOPP_PLUGINFILE]);
		if ( $justchecked && ! $changed ) return;

		$Shopp = Shopp::object();
		$addons = array_merge(
			$Shopp->Gateways->checksums(),
			$Shopp->Shipping->checksums(),
			$Shopp->Storage->checksums()
		);

		$request = array('ShoppServerRequest' => 'update-check');
		/**
		 * Update checks collect environment details for faster support service only,
		 * none of it is linked to personally identifiable information.
		 **/
		$data = array(
			'core' => SHOPP_VERSION,
			'addons' => join("-", $addons),
			'site' => get_bloginfo('url'),
			'wp' => get_bloginfo('version').(is_multisite()?' (multisite)':''),
			'mysql' => mysql_get_server_info(),
			'php' => phpversion(),
			'uploadmax' => ini_get('upload_max_filesize'),
			'postmax' => ini_get('post_max_size'),
			'memlimit' => ini_get('memory_limit'),
			'server' => $_SERVER['SERVER_SOFTWARE'],
			'agent' => $_SERVER['HTTP_USER_AGENT']
		);

		$response = ShoppSupport::callhome($request, $data);

		if ($response == '-1') return; // Bad response, bail
		$response = unserialize($response);
		unset($updates->response);

		if (isset($response->key) && !Shopp::str_true($response->key)) shopp_set_setting( 'updatekey', array(0) );

		if (isset($response->addons)) {
			$updates->response[SHOPP_PLUGINFILE.'/addons'] = $response->addons;
			unset($response->addons);
		}

		if (isset($response->id))
			$updates->response[SHOPP_PLUGINFILE] = $response;

		if (isset($updates->response)) {
			shopp_set_setting('updates', $updates);

			// Add Shopp to the WP plugin update notification count
			if ( isset($updates->response[SHOPP_PLUGINFILE]) )
				$plugin_updates->response[SHOPP_PLUGINFILE] = $updates->response[SHOPP_PLUGINFILE];

		} else unset($plugin_updates->response[SHOPP_PLUGINFILE]); // No updates, remove Shopp from the plugin update count

		$plugin_updates->last_checked_shopp = time();
		if ( function_exists('set_site_transient') ) set_site_transient('update_plugins', $plugin_updates);
		else set_transient('update_plugins', $plugin_updates);

		return $updates;
	}

	/**
	 * Loads the change log for an available update
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public static function changelog () {
		if ( 'shopp' != $_REQUEST['plugin'] ) return;

		$request = array('ShoppServerRequest' => 'changelog');

		if ( isset($_GET['core']) && ! empty($_GET['core']) )
			$request['core'] = $_GET['core'];

		if ( isset($_GET['addon']) && ! empty($_GET['addon']) )
			$request['addons'] = $_GET['addon'];

		$data = array();
		$response = ShoppSupport::callhome($request, $data);

		include SHOPP_ADMIN_PATH . '/help/changelog.php';
		exit;
	}

	/**
	 * Reports on the availability of new updates and the update key
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public static function status () {
		$updates = shopp_setting('updates');
		$keysetting = ShoppSupport::key();
		$key = $keysetting['k'];

		$activated = ShoppSupport::activated();
		$core = isset($updates->response[ SHOPP_PLUGINFILE ]) ? $updates->response[ SHOPP_PLUGINFILE ] : false;
		$addons = isset($updates->response[ SHOPP_PLUGINFILE . '/addons' ]) ? $updates->response[ SHOPP_PLUGINFILE . '/addons'] : false;

		$plugin_name = 'Shopp';
		$plugin_slug = strtolower($plugin_name);
		$store_url = ShoppSupport::STORE;
		$account_url = "$store_url/account/";

		if ( ! empty($core)	// Core update available
				&& isset($core->new_version)	// New version info available
				&& version_compare($core->new_version, SHOPP_VERSION, '>') // New version is greater than current version
			) {
			$details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&core=' . $core->new_version . '&TB_iframe=true&width=600&height=800');
			$update_url = wp_nonce_url('update.php?action=shopp&plugin=' . SHOPP_PLUGINFILE, 'upgrade-plugin_shopp');

			if ( true || ! $activated ) { // Key not active
				$update_url = $store_url;
				$message = Shopp::__(
					'There is a new version of %1$s available. %2$s View version %5$s details %4$s or %3$s purchase a %1$s key %4$s to get access to automatic updates and official support services.',
					$plugin_name, '<a href="' . $details_url . '" class="thickbox" title="' . esc_attr($plugin_name) . '">', '<a href="' . $update_url .'">', '</a>', $core->new_version
				);

				shopp_set_setting('updates', false);
			} else {
				$message = Shopp::__(
					'There is a new version of %1$s available. %2$s View version %5$s details %4$s or %3$s upgrade automatically %4$s.',
					$plugin_name, '<a href="'.$details_url.'" class="thickbox" title="'.esc_attr($plugin_name).'">', '<a href="'.$update_url.'">', '</a>', $core->new_version
				);
			}

			echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">'.$message.'</div></td></tr>';

			return;
		}

		if ( ! $activated ) { // No update available, key not active
			$message = Shopp::__(
				'Please activate a valid %1$s access key for automatic updates and official support services. %2$s Find your %1$s access key %4$s or %3$s purchase a new key at the Shopp Store. %4$s',
				$plugin_name, '<a href="'.$account_url.'" target="_blank">', '<a href="'.$store_url.'" target="_blank">', '</a>'
			);

			echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">'.$message.'</div></td></tr>';
			shopp_set_setting('updates', false);

			return;
		}

	    if ( $addons ) {
			// Addon update messages
			foreach ( $addons as $addon ) {
				$details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=shopp&addon='.($addon->slug).'&TB_iframe=true&width=600&height=800');
				$update_url = wp_nonce_url('update.php?action=shopp&addon='.$addon->slug.'&type='.$addon->type, 'upgrade-shopp-addon_'.$addon->slug);
				$message = Shopp::__(
					'There is a new version of the %1$s add-on available. %2$s View version %5$s details %4$s or %3$s upgrade automatically %4$s.',
					esc_html($addon->name), '<a href="'.$details_url.'" class="thickbox" title="'.esc_attr($addon->name).'">', '<a href="'.esc_url($update_url).'">', '</a>', esc_html($addon->new_version)
				);

				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">'.$message.'</div></td></tr>';
			}
		}

	}

	/**
	 * Communicates with the Shopp update service server
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $request (optional) A list of request variables to send
	 * @param array $data (optional) A list of data variables to send
	 * @param array $options (optional)
	 * @return string The response from the server
	 **/
	public static function callhome ($request=array(), $data=array(), $options=array()) {
		$query = http_build_query(array_merge(array('ver'=>'1.1'), $request), '', '&');
		$data = http_build_query($data, '', '&');

		$defaults = array(
			'method' => 'POST',
			'timeout' => 20,
			'redirection' => 7,
			'httpversion' => '1.0',
			'user-agent' => SHOPP_GATEWAY_USERAGENT.'; '.get_bloginfo( 'url' ),
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'body' => $data,
			'compress' => false,
			'decompress' => true,
			'sslverify' => false
		);
		$params = array_merge($defaults, $options);

		$URL = ShoppSupport::HOMEPAGE . "?$query";

		$connection = new WP_Http();
		$result = $connection->request($URL, $params);
		extract($result);

		if ( isset($response['code']) && 200 != $response['code'] ) { // Fail, fallback to http instead
			$URL = str_replace('https://', 'http://', $URL);
			$connection = new WP_Http();
			$result = $connection->request($URL, $params);
			extract($result);
		}

		if ( is_wp_error($result) ) {
			$errors = array(); foreach ($result->errors as $errname => $msgs) $errors[] = join(' ', $msgs);
			$errors = join(' ', $errors);

			shopp_add_error("Shopp: ".Lookup::errors('callhome', 'fail')." $errors ".Lookup::errors('contact', 'admin')." (WP_HTTP)", SHOPP_COMM_ERR);

			return false;
		} elseif ( empty($result) || !isset($result['response']) ) {
			shopp_add_error("Shopp: ".Lookup::errors('callhome', 'noresponse'), SHOPP_COMM_ERR);
			return false;
		} else extract($result);

		if ( isset($response['code']) && 200 != $response['code'] ) {
			$error = Lookup::errors('callhome', 'http-'.$response['code']);
			if (empty($error)) $error = Lookup::errors('callhome', 'http-unkonwn');
			shopp_add_error("Shopp: $error", 'callhome_comm_err', SHOPP_COMM_ERR);
			return $body;
		}

		return $body;

	}


	public static function activate ( string $key ) {
		return self::request($key, 'activate');
	}

	public static function deactivate ( string $key ) {
		return self::request($key, 'deactivate');
	}

	/**
	 * Activates or deactivates a support key
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return stdClass The server response
	 **/
	public static function request ( string $key, string $action ) {
		$actions = array('deactivate', 'activate');
		if (!in_array($action, $actions)) $action = reset($actions);
		$action = "$action-key";

		$request = array( 'ShoppServerRequest' => $action, 'key' => $key, 'site' => get_bloginfo('siteurl') );
		$response = ShoppSupport::callhome($request);
		$result = json_decode($response);

		$result = apply_filters('shopp_update_key', $result);

		shopp_set_setting( 'updatekey', $result );

		return $response;
	}

	/**
	 * Loads the key setting
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return key data
	 **/
	public static function key () {
		$updatekey = shopp_setting('updatekey');

		// @deprecated Will be removed eventually
		if ( is_array($updatekey) ) {
			$keys = array('s', 'k', 't');
			return array_combine(array_slice($keys, 0, count($updatekey)), $updatekey);
		}

		$data = base64_decode($updatekey);
		if ( empty($data) ) return false;
		return unpack(Lookup::keyformat(), $data);
	}

	/**
	 * Determines if the support key is activated
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if activated, false otherwise
	 **/
	public static function activated () {
		$key = ShoppSupport::key();
		return ('1' == $key['s']);
	}

} // END class ShoppSupport
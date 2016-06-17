<?php
/**
 * Support.php
 *
 * Shopp Support class for shopplugin.com resources
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, May 2013
 * @license (@see license.txt)
 * @package shopp
 * @version 1.0
 * @since 1.3
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


	public static function pluginsnag ( $file, $plugin_data ) {

		if ( self::earlyupdates() ) return;

		$current = get_site_transient( 'update_plugins' );
		if ( isset( $current->response[ SHOPP_PLUGINFILE ] ) ) return;

		if ( is_network_admin() || ! is_multisite() ) {
		$wp_list_table = _get_list_table('WP_Plugins_List_Table');
			echo '<tr class="plugin-update-tr active"><th colspan="' . $wp_list_table->get_column_count() . '" class="check-column plugin-update colspanchange"><div class="update-message">';
			echo self::buykey();
			echo '<br class="clear" /><br /><style type="text/css">#shopp th,#shopp td{border-bottom:0;-webkit-box-shadow:none;box-shadow:none;}
				#shopp+.plugin-update-tr .update-message {box-sizing:border-box;} #shopp+.plugin-update-tr .button {float:left; margin:20px 20px 0 0;} #shopp+.plugin-update-tr big { display:block; } #shopp+.plugin-update-tr .update-message::before {content:"";}</style>';
			echo '</div></td></tr>';
		}

	}

	public static function addons ( $meta, $plugin) {
		if ( SHOPP_PLUGINFILE != $plugin ) return $meta;

		$Shopp = Shopp::object();
		$builtin = array(
			'Shopp2Checkout', 'ShoppPayPalStandard', 'ShoppOfflinePayment', 'ShoppTestMode',
			'FreeOption', 'ItemQuantity', 'ItemRates', 'OrderAmount', 'OrderRates', 'OrderWeight', 'PercentageAmount',
			'DBStorage', 'FSStorage'
		);
		$builtin = array_flip($builtin);

		$modules = array_merge(
			$Shopp->Gateways->modules,
			$Shopp->Shipping->modules,
			$Shopp->Storage->modules
		);

		$installed = array_diff_key($modules, $builtin);

		if ( empty($installed) ) return $meta;
		$label = Shopp::_mi('**Add-ons:**');
		foreach ( $installed as $addon ) {
			$entry = array($label, $addon->name, $addon->version);
			if ( $label ) $label = '';
			$meta[] = trim(join(' ', $entry));
		}
		return $meta;
	}

	public static function earlyupdates () {
		$updates = shopp_setting('updates');

		$core = isset($updates->response[ SHOPP_PLUGINFILE ]) ? $updates->response[ SHOPP_PLUGINFILE ] : false;
		$addons = isset($updates->response[ SHOPP_PLUGINFILE . '/addons' ]) ? $updates->response[ SHOPP_PLUGINFILE . '/addons'] : false;

		if ( ! $core && ! $addons ) return false;

		$plugin_name = 'Shopp';
		$plugin_slug = strtolower($plugin_name);
		$store_url = ShoppSupport::STORE;
		$account_url = "$store_url/account/";

		$updates = array();

		if ( ! empty($core)	// Core update available
				&& isset($core->new_version)	// New version info available
				&& version_compare($core->new_version, ShoppVersion::release(), '>') // New version is greater than current version
			) {
			$details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&core=' . $core->new_version . '&TB_iframe=true&width=600&height=800');

			$updates[] = Shopp::_mi('%2$s Shopp %1$s is available %3$s from shopplugin.com now.', $core->new_version, '<a href="' . $details_url . '" class="thickbox" title="' . esc_attr($plugin_name) . '">', '</a>');
		}

	    if ( ! empty($addons) ) {
			// Addon update messages
			$addonupdates = array();
			foreach ( (array)$addons as $addon )
				$addonupdates[] = $addon->name . ' ' . $addon->new_version;

			if ( count($addons) > 1 ) {
				$last = array_pop($addonupdates);
				$updates[] = Shopp::_mi('Add-on updates are available for %s &amp; %s.', join(', ', $addonupdates), $last);
			} elseif ( count($addons) == 1 )
				$updates[] = Shopp::_mi('An add-on update is available for %s.', $addonupdates[0]);
		}

		if ( is_network_admin() || ! is_multisite() ) {

			$wp_list_table = _get_list_table('WP_Plugins_List_Table');
			echo '<tr class="plugin-update-tr"><th colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange"><div class="update-message">';
			Shopp::_emi(
				'You&apos;re missing out on important updates! %1$s &nbsp; %2$s Buy a Shopp Support Key! %3$s', empty($updates) ? '' : join(' ', $updates), '<a href="' . ShoppSupport::STORE . '" class="button button-primary">', '</a>'
			);
			echo '<style type="text/css">#shopp th,#shopp td{border-bottom:0;}</style>';
			echo '</div></td></tr>';
		}


		return true;
	}

	public static function wpupdate ( $file, $plugin_data ) {
		echo ' '; echo self::buykey();
	}

	public static function reminder () {
		$userid = get_current_user_id();
		$lasttime = get_user_meta($userid, 'shopp_nonag', true);
		$dismissed = ( current_time('timestamp') - $lasttime ) < ( rand(2,5) * 86400 );
		if ( ! current_user_can('shopp_settings') || ShoppSupport::activated() || $dismissed ) return '';

		$url = add_query_arg('action', 'shopp_nonag', wp_nonce_url(admin_url('admin-ajax.php'), 'wp_ajax_shopp_nonag'));
		$_ = array();
		$_[] = '<div id="shopp-activation-nag" class="update-nag">';

		$_[] = '<p class="dismiss shoppui-remove-sign alignright"></p>';

		$_[] = '<p class="nag">' . self::buykey() . '</p>';
		$_[] = '</div>';

		$_[] = '<script type="text/javascript">';
		$_[] = 'jQuery(document).ready(function($){var id="#shopp-activation-nag",el=$(id).click(function(){window.open($(this).find("a").attr("href"),"_blank");}).find(".dismiss").click(function(){$(id).remove();$.ajax(\'' . $url . '\');});});';
		$_[] = '</script>';
		return join('', $_);
	}

	public static function buykey () {
		return Shopp::_mi('%s<big>Upgrade for Support</big>%s<big>You&apos;re missing out on **expert support**, **early access** to Shopp updates, and **one-click add-on updates**!</big>Don&apos;t have a Shopp Support Key? Support the project and get expert support, buy one today!', '<a href="' . ShoppSupport::STORE . '?utm_source=wpadmin-' . $_REQUEST['page'] . '&utm_medium=Shopp&utm_campaign=Plugin" class="button button-primary" target="_blank">', '</a>');
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
		$query = http_build_query(array_merge(array('ver'=>'1.2'), $request), '', '&');
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
		// error_log('CALLHOME REQUEST ------------------');
		// error_log($URL);
		// error_log(json_encode($params));
		$connection = new WP_Http();
		$result = $connection->request($URL, $params);
		// error_log(json_encode($result));
		// error_log('-------------- END CALLHOME REQUEST');
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

			shopp_add_error("Shopp: ".Lookup::errors('callhome', 'fail')." $errors ".Lookup::errors('contact', 'admin')." (WP_HTTP)", SHOPP_ADMIN_ERR);

			return false;
		} elseif ( empty($result) || !isset($result['response']) ) {
			shopp_add_error("Shopp: ".Lookup::errors('callhome', 'noresponse'), SHOPP_ADMIN_ERR);
			return false;
		} else extract($result);

		if ( isset($response['code']) && 200 != $response['code'] ) {
			$error = Lookup::errors('callhome', 'http-'.$response['code']);
			if (empty($error)) $error = Lookup::errors('callhome', 'http-unkonwn');
			shopp_add_error("Shopp: $error", 'callhome_comm_err', SHOPP_ADMIN_ERR);
			return $body;
		}

		return $body;

	}

	/**
	 * Checks if the support key is activated
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if activated, false otherwise
	 **/
	public static function activated () {
		if ( class_exists('ShoppSupportKey', false) )
			return ShoppSupportKey::activated();
		return false;
	}

} // END class ShoppSupport
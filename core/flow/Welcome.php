<?php

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminWelcome extends ShoppAdminController {

	protected $ui = 'help';

	public function __construct () {
		parent::__construct();

		$uri = SHOPP_ADMIN_URI . '/styles';
		$version = dechex(crc16(SECURE_AUTH_SALT . Shopp::VERSION));
		wp_enqueue_style('shopp.welcome', "$uri/welcome.css", array(), $version, 'screen');
	}

	public function admin () {
		switch ( $this->pagename ) {
			case 'credits': return $this->credits();
			default: return $this->welcome();
		}
	}

	public function welcome () {
		$Shopp = Shopp::object();
		include $this->ui('welcome.php');
		if ( shopp_setting_enabled('display_welcome') )
			shopp_set_setting('display_welcome', 'off');
		return true;
	}

	public function heading () {
		$display_version = Shopp::VERSION;

		Shopp::_em('
# Welcome to Shopp %s

Thank you for using Shopp! E-commerce just got a little easier and more secure. Enjoy!', $display_version);
?>

		<div class="shopp-badge"><div class="logo">Shopp</div><span class="version"><?php printf( __( 'Version %s' ), $display_version ); ?></span></div>

		<?php
			$this->tabs(array(
				'shopp-welcome' => __('What&#8217;s New'),
				'shopp-credits' => __('Credits'),
			));
		?>

		<?php
	}

	public function credits () {
		$Shopp = Shopp::object();
		include $this->ui('credits.php');
	}

	public function contributors () {

		$contributors = get_transient('shopp_contributors');
		if ( ! empty($contributors) ) return $contributors;

		$response = wp_remote_get( 'https://api.github.com/repos/ingenesis/shopp/contributors', array('sslverify' => false) );

		if ( 200 != wp_remote_retrieve_response_code($response) || is_wp_error($response) )
			return array();

		$contributors = json_decode( wp_remote_retrieve_body($response) );
		if ( ! is_array( $contributors ) ) return array();

		// Get full name and company if available
		$top = 0;
		foreach ( $contributors as $contributor ) {
			$response = wp_remote_get( $contributor->url, array('sslverify' => false) );
			$contributor->name = $contributor->login;
			$contributor->company = '';
			$contrubutor->link = $contributor->html_url;
			if ( $top++ > 45 ) continue; // Top 30 contributors only (API requests are rate-limited to 60/hour)

			if ( 200 != wp_remote_retrieve_response_code($response) || is_wp_error($response) ) continue;
			$user = json_decode( wp_remote_retrieve_body($response) );
			$contributor->user = $user;
			if ( isset($user->name) ) {
				$contributor->name = $user->name;
				if ( $user->company != $user->name )
					$contributor->company = $user->company;
				if ( isset($user->blog) ) $contributor->link = $user->blog;
			}

		}

		set_transient('shopp_contributors', $contributors, 86400);

		return $contributors;

	}
}
<?php
/**
 * AdminScreenWelcome.php
 *
 * Admin welcome screen controller
 *
 * @copyright Ingenesis Limited, August 2013-2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Welcome
 * @since	 @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminScreenWelcome extends ShoppScreenController {

	/**
	 * Constructor
	 **/
	public function __construct() {
		parent::__construct();
		
		$this->ui = 'help';
		
		$this->tabs = array(
			'shopp-welcome' => __('What&#8217;s New'),
			'shopp-credits' => __('Credits'),
		);

		$uri = SHOPP_ADMIN_URI . '/styles';
		shopp_enqueue_style('welcome');
	}

	/**
	 * Routes rendering to the correct UI rendering method
	 *
	 * @since 1.5
	 * @return string Return the UI rendering method
	 **/
	public function route() {
		switch ( $this->pagename ) {
			case 'credits': return $this->credits();
			default: return $this->ui();
		}
	}

	/**
	 * The welcome UI rendering method
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function welcome() {
		$Shopp = Shopp::object();
		include $this->ui('welcome.php');
		// Displayed the welcome, turn display_welcome flag off
		shopp_set_setting('display_welcome', 'off');
	}

	/**
	 * Disable the help tab
	 *
	 * @since 1.5
	 * @return string Empty string
	 **/
	public function help() {
		return '';
	}

	/**
	 * Render the welcome heading with tab navigation and version number
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function heading() {
		$display_version = ShoppVersion::release();

		Shopp::_em('
# Welcome to Shopp %s

Thank you for using Shopp! E-commerce just got a little easier and more secure. Enjoy!', $display_version);
?><div class="shopp-badge"><div class="logo">Shopp</div><span class="version"><?php printf( __( 'Version %s' ), $display_version ); ?></span></div><?php

		$this->tabs();
	}

	/**
	 * Render the credits UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function credits() {
		$Shopp = Shopp::object();
		include $this->ui('credits.php');
	}

	/**
	 * Download the current list of contributors from the GitHub API
	 *
	 * @since 1.5
	 * @return array A list of contributors
	 **/
	public function contributors() {

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
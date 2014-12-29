<?php
/**
 * Styles.php
 *
 * Controller for browser stylesheet queueing and delivery
 *
 * @copyright Ingenesis Limited, May 2014
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package Shopp\Styles
 * @version 1.0
 * @since 1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_action('shopp_default_styles', array('ShoppStyles', 'defaults'));

class ShoppStyles extends WP_Styles {

	public function __construct() {

		do_action('shopp_default_styles', $this);

		add_action('wp_head', array($this, 'dostyles'), 8);
		add_action('admin_print_styles', array($this, 'dostyles'), 15);

	}

	public static function defaults ( ShoppStyles $styles ) {
		$script = basename(__FILE__);
		$schema = ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off' ) ? 'https://' : 'http://';
		if ( defined('SHOPP_PLUGINURI') ) $url = SHOPP_PLUGINURI . '/core';
		else $url = preg_replace("|$script.*|i", '', $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		$styles->base_url = $url;

		// Short checksum for cache control that changes with Shopp versions while masking it somewhat
		$styles->default_version = hash('crc32b', ABSPATH . ShoppVersion::release());
		$styles->default_dirs = array('/ui/styles/');

		$styles->add('admin', '/ui/styles/admin.css');
		$styles->add('admin-rtl', '/ui/styles/rtl.css');
		$styles->add('backmenu', '/ui/styles/backmenu.css');
		$styles->add('catalog', '/ui/styles/catalog.css');
		$styles->add('colorbox', '/ui/styles/colorbox.css');
		$styles->add('dashboard', '/ui/styles/dashboard.css');
		$styles->add('icons', '/ui/styles/icons.css');
		$styles->add('menus', '/ui/styles/menu.css');
		$styles->add('welcome', '/ui/styles/welcome.css'); // Fix icons

	}

	public function dostyles () {
		global $concatenate_scripts;

		if ( ! did_action('shopp_print_styles') )
			do_action('shopp_print_styles');

		$this->do_concat = defined('CONCATENATE_SCRIPTS') ? CONCATENATE_SCRIPTS : true;

		$this->do_items();

		if ( ! empty($this->do_concat) )
			echo $this->linked();

		$this->reset();

		return $this->done;
	}

	public function linked () {
			global $compress_scripts;

			$zip = $compress_scripts ? 1 : 0;
			if ( $zip && defined('ENFORCE_GZIP') && ENFORCE_GZIP )
				$zip = 'gzip';

			$debug = (int)( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG );

			if ( ! empty($this->concat) ) {

				$ver = hash('crc32b', "$this->concat_version");
				$stylesheets = trim($this->concat, ', ');
				$url = trailingslashit(get_bloginfo('url')) . 'sp-styles.css';

				$href = add_query_arg(array(
					'load' => $stylesheets,
					'c' => $zip,
					'ver' => $ver,
					'debug' => $debug
				), $url);

				echo "<link rel='stylesheet' href='" . esc_attr($href) . "' type='text/css' media='all' />\n";

			}

			if ( ! empty($this->print_html) )
				echo $this->print_html;
	}

	public function in_default_dir ( $src ) {
		if ( ! $this->default_dirs )
			return true;

		foreach ( (array) $this->default_dirs as $test ) {
			if ( false !== strpos($src, $test) )
				return true;
		}
		return false;
	}

}
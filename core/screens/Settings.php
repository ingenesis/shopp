<?php
/**
 * Settings.php
 *
 * Settings screen controller
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Screen/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Routes the admin setting screen requests
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppAdminSettings extends ShoppAdminPostController {

	protected $ui = 'settings';

	protected function route () {
		switch ( $this->slug() ) {
			case 'advanced':     return 'ShoppScreenAdvanced';
			case 'checkout':     return 'ShoppScreenCheckout';
			case 'downloads':    return 'ShoppScreenDownloads';
			case 'images':       return 'ShoppScreenImages';
			case 'log':          return 'ShoppScreenLog';
			case 'orders':       return 'ShoppScreenOrders';
			case 'pages':        return 'ShoppScreenPages';
			case 'payments':     return 'ShoppScreenPayments';
			case 'presentation': return 'ShoppScreenPresentation';
			case 'shipping':     return 'ShoppScreenShipping';
			case 'boxes':   	 return 'ShoppScreenShipmentBoxes';
			case 'storage':      return 'ShoppScreenStorage';
			case 'taxes':        return 'ShoppScreenTaxes';
			default:             return 'ShoppScreenSetup';
		}
	}

	protected function slug () {
		$page = strtolower($this->request('page'));
		return substr($page, strrpos($page, '-') + 1);
	}

}

/**
 * Shopp Settings screen controller helper
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppSettingsScreenController extends ShoppScreenController {

	public $template = false;
    
	public function title () {
		if ( isset($this->title) )
			return $this->title;
		else return ShoppAdminPages()->Page->label;
	}

	public function ops () {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	public function updates () {
 		shopp_set_formsettings();
		$this->notice(Shopp::__('Settings saved.'));
	}

	protected function ui ( $file ) {
		$template = join('/', array(SHOPP_ADMIN_PATH, $this->ui, $file));

		if ( 'settings.php' == $file )
			$template = false;

		if ( is_readable($template) ) {
			$this->template = $template;
		} else {
			$this->notice(Shopp::__('The requested screen was not found.'), 'error');
		}

		return join('/', array(SHOPP_ADMIN_PATH, $this->ui, 'settings.php'));
	}


	/**
	 * Renders screen tabs from a given associative array
	 *
	 * The tab array uses a tab page slug as the key and the
	 * localized title as the value.
	 *
	 * @since 1.3
	 *
	 * @param array $tabs The tab map array
	 * @return void
	 **/
	protected function tabs () {

		global $plugin_page;

		$tabs = ShoppAdminPages()->tabs( $plugin_page );
		$first = current($tabs);
		$default = $first[1];

		$markup = '';
		foreach ( $tabs as $index => $entry ) {
			list($title, $tab, $parent, $icon) = $entry;

			$slug = substr($tab, strrpos($tab, '-') + 1);

			// Check settings to ensure enabled
			if ( $this->hiddentab($slug) )
				continue;

			$classes = array($tab);

			if ( ($plugin_page == $parent && $default == $tab) || $plugin_page == $tab )
				$classes[] = 'current';

			$url = add_query_arg(array('page' => $tab), admin_url('admin.php'));
			$markup .= '<li class="' . esc_attr(join(' ', $classes)) . '"><a href="' . esc_url($url) . '">'
					. '	<div class="shopp-settings-icon ' . $icon . '"></div>'
					. '	<div class="shopp-settings-label">' . esc_html($title) . '</div>'
					. '</a></li>';
		}

		$pagehook = sanitize_key($plugin_page);
		return '<div id="shopp-settings-menu" class="clearfix"><ul class="wp-submenu">' . apply_filters('shopp_admin_' . $pagehook . '_screen_tabs', $markup) . '</ul></div>';

	}

	/**
	 * Determines hidden settings screens
	 *
	 * @since 1.4
	 *
	 * @param string $slug The tab slug name
	 * @return bool True if the tab should be hidden, false otherwise
	 **/
	protected function hiddentab ( $slug ) {

		$settings = array(
			'shipping'  => 'shipping',
			'boxes'     => 'shipping',
			'taxes'     => 'taxes',
			'orders'    => 'shopping_cart',
			'payments'  => 'shopping_cart',
			'downloads' => 'shopping_cart'
		);

		if ( ! isset($settings[ $slug ]) ) return false;
		$setting = $settings[ $slug ];

		return ( ! shopp_setting_enabled($setting) );

	}

	public function posted () {
		parent::posted();
		$this->posted = $this->form;
        
        $save = $this->form('save'); // Keep save button state
        
		if ( ! empty($_POST['settings']) )
			$this->form = ShoppRequestProcessing::process($_POST['settings'], $this->defaults);
        
        if ( ! empty($save) )
            $this->form['save'] = true;

		return true;
	}

	public function saveform () {
		$_POST['settings'] = $this->form;
		return shopp_set_formsettings();
	}

	public function formattrs () {
		return '';
	}

} // class ShoppSettingsScreenController
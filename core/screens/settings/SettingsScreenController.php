<?php
/**
 * AdminSettings.php
 *
 * Generic setting screen controller
 * 
 * A middleware controller that provides additional helper functionality
 * for settings screens. All of the settings screens extend this class.
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppSettingsScreenController extends ShoppScreenController {

	/** @var string $template The UI template file to use for the screen */
	public $template = false;

    /**
     * Provides the title of the screen
     * 
     * Set the title property to override the page label.
     *
     * @since 1.4
     * @return string The title of the screen
     **/
	public function title() {
		if ( isset($this->title) )
			return $this->title;
		else return ShoppAdminPages()->Page->label;
	}

	/**
	 * Register processing operation methods
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function ops() {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	/**
	 * Handle saving changes from the form
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function updates() {
 		shopp_set_formsettings();
		$this->notice(Shopp::__('Settings saved.'));
	}

    /**
     * Get the path to include the correct UI template
     *
     * @since 1.4
     * @return string The path to the UI template file
     **/
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
	protected function tabs() {

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
	protected function hiddentab( $slug ) {

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

    /**
     * Process posted form changes
     *
     * @since 1.4
     * 
     * @return boolean True, always true
     **/
	public function posted() {
		parent::posted();
		$this->posted = $this->form;
        
        $save = $this->form('save'); // Keep save button state
        
		if ( ! empty($_POST['settings']) )
			$this->form = ShoppRequestProcessing::process($_POST['settings'], $this->defaults);
        
        if ( ! empty($save) )
            $this->form['save'] = true;

		return true;
	}

    /**
     * Save settings form changes
     *
     * @since 1.4
     * @return boolean True if saving form settings is successful, false otherwise
     **/
	public function saveform() {
		$_POST['settings'] = $this->form;
		return shopp_set_formsettings();
	}

    /**
     * Provides extra form attributes
     *
     * @since 1.4
     * @return void
     **/
	public function formattrs() {
		return '';
	}

} // class ShoppSettingsScreenController
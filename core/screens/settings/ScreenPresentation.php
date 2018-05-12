<?php
/**
 * ScreenPresentation.php
 *
 * Presentation settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPresentation extends ShoppSettingsScreenController {

	protected $template_path = '';
	protected $theme_path = '';

	/**
	 * Register processing operation methods
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function ops() {

		$this->template_path = SHOPP_PATH . '/templates';
		$this->theme_path = sanitize_path(STYLESHEETPATH . '/shopp');

		add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	/**
	 * Handle saving form updates
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function updates() {
		$form = $this->form();

		if ( Shopp::str_true($this->form('theme_templates')) && ! is_dir($this->theme_path) ) {
			$this->form['theme_templates'] = 'off';
			$this->notice(Shopp::__("Shopp theme templates can't be used because they don't exist."), 'error');
		}

		// Recount terms when this setting changes
		if ( $this->form('outofstock_catalog') != shopp_setting('outofstock_catalog') ) {
			$taxonomy = ProductCategory::$taxon;
			$terms = get_terms( $taxonomy, array('hide_empty' => 0,'fields' => 'ids') );
			if ( ! empty($terms) )
				wp_update_term_count_now( $terms, $taxonomy );
		}

		shopp_set_formsettings();

		if ( ! empty($form) )
			$this->notice(Shopp::__('Presentation settings saved.'), 'notice', 20);
	}

	/**
	 * Render the UI
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function screen() {
		$install = filter_input(INPUT_POST, 'install', FILTER_SANITIZE_STRING);

		$status = $this->template_ready();
		if ( $install )
			$status = $this->install_templates();

		$category_views = array('grid' => Shopp::__('Grid'), 'list' => Shopp::__('List'));
		$row_products = array(2, 3, 4, 5, 6, 7);

		$productOrderOptions = ProductCategory::sortoptions();
		$productOrderOptions['custom'] = Shopp::__('Custom');

		$orderOptions = array('ASC'  => Shopp::__('Order'),
							  'DESC' => Shopp::__('Reverse Order'),
							  'RAND' => Shopp::__('Shuffle'));

		$orderBy = array('sortorder' => Shopp::__('Custom arrangement'),
						 'created'   => Shopp::__('Upload date'));

		include $this->ui('presentation.php');
	}

	/**
	 * Installs Shopp theme template files into the currently active theme
	 *
	 * @since 1.3
	 * @version 1.5
	 * @return string Status label
	 **/
	protected function install_templates() {
		$install = filter_input(INPUT_POST, 'install', FILTER_SANITIZE_STRING);
		if ( ! $install )
			return false;

		$filesystem = Shopp::filesystem($this->url(), array('install'));
		if ( ! $filesystem )
			return false;


		$templates = array_keys($filesystem->dirlist($this->template_path, false));
		foreach ( $templates as $file ) {
			$source_file = "$this->template_path/$file";
			$target_file = "$this->theme_path/$file";

			if ( $filesystem->exists($target_file) )
				continue;

			$template = $filesystem->get_contents($source_file);
			$template = preg_replace('/^<\?php\s\/\*\*\s+(.*?\s)*?\*\*\/\s\?>\s/', '', $template);
			$filesystem->put_contents($target_file, $template);
		}

		return 'available';

	}

	/**
	 * Checks how template-ready the active theme is
	 *
	 * The status strings returned match messaging to prompt the user on
	 * what they can do to make their theme template-ready.
	 *
	 * @since 1.5
	 * @return string The status of template readiness
	 **/
	protected function template_ready() {
		$filesystem = Shopp::filesystem($this->url(), array('install'));
		if ( ! $filesystem )
			return 'filesystem';

		if ( ! $filesystem->is_writable(STYLESHEETPATH) )
			return 'directory';

		// Check for Shopp directory in theme and try to create it
		if ( ! $filesystem->exists($this->theme_path) )
			$filesystem->mkdir($this->theme_path);

		if ( ! $filesystem->is_dir($this->theme_path) )
			return 'directory';

		if ( ! $filesystem->is_writable($this->theme_path) )
			return 'permissions';

		$builtin = array_keys($filesystem->dirlist($this->template_path));
		$theme = array_keys($filesystem->dirlist($this->theme_path));

		if ( empty($theme) )
			return 'ready';

		if ( array_diff($builtin, $theme) )
			return 'incomplete';
	}
}
<?php
/**
 * Presentation.php
 *
 * Presetation settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPresentation extends ShoppSettingsScreenController {

	protected $template_path = '';
	protected $theme_path = '';

	public function ops() {

		$this->template_path = SHOPP_PATH . '/templates';
		$this->theme_path = sanitize_path(STYLESHEETPATH . '/shopp');

		add_action('shopp_admin_settings_ops', array($this, 'updates') );
		add_action('shopp_admin_settings_ops', array($this, 'install_templates') );
	}

	public function updates() {

		$builtin_path = SHOPP_PATH . '/templates';
		$theme_path = sanitize_path(STYLESHEETPATH . '/shopp');

		if ( Shopp::str_true($this->form('theme_templates')) && ! is_dir($theme_path) ) {
			$this->form['theme_templates'] = 'off';
			$this->notice(Shopp::__("Shopp theme templates can't be used because they don't exist."), 'error');
		}

		if ( ! $this->form('catalog_pagination') )
			$this->form['catalog_pagination'] = 0;

		// Recount terms when this setting changes
		if ( $this->form('outofstock_catalog') != shopp_setting('outofstock_catalog') ) {
			$taxonomy = ProductCategory::$taxon;
			$terms = get_terms( $taxonomy, array('hide_empty' => 0,'fields' => 'ids') );
			if ( ! empty($terms) )
				wp_update_term_count_now( $terms, $taxonomy );
		}

		shopp_set_formsettings();
		$this->notice(Shopp::__('Presentation settings saved.'), 'notice', 20);
	}

	public function install_templates() {
		if ( empty($_POST['install']) ) return;
		copy_shopp_templates($this->template_path, $this->theme_path);
	}


	public function screen() {

		$status = 'available';
		if ( ! is_dir($this->theme_path) ) $status = 'directory';
		else {
			if ( ! is_writable($this->theme_path) ) $status = 'permissions';
			else {
				$builtin = array_filter(scandir($this->template_path), 'filter_dotfiles');
				$theme = array_filter(scandir($this->theme_path), 'filter_dotfiles');

				if ( empty($theme) ) $status = 'ready';
				elseif ( array_diff($builtin, $theme) ) $status = 'incomplete';
			}
		}

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

} // class ShoppScreenPresentation
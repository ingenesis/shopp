<?php
/**
 * Setup.php
 *
 * Flow controller for the setup screens
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January, 2010
 * @package shopp
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Setup
 *
 * @package shopp
 * @author Jonathan Davis
 **/
class ShoppAdminSetup extends ShoppAdminController {

	protected $ui = 'settings';

	/**
	 * Setup constructor
	 *
	 * @return voidtax
	 * @author Jonathan Davis
	 **/
	public function __construct () {
		parent::__construct();

		switch ( $this->pagename ) {
			case 'pages':
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('pageset');
				$this->pages_ui();
				break;
			case 'images':
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('imageset');
				shopp_localize_script( 'imageset', '$is', array(
					'confirm' => __('Are you sure you want to remove this image preset?','Shopp'),
				));
				$this->images_ui();
				break;
			case 'management':
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('labelset');
				shopp_localize_script('labelset', '$sl', array(
					'prompt' => __('Are you sure you want to remove this order status label?','Shopp'),
				));
				break;
			case 'core':
			case 'setup':
				shopp_enqueue_script('setup');
				break;
		}

	}

	/**
	 * Parses settings interface requests
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function admin () {

		switch( $this->pagename ) {
			case 'pages':			return $this->pages();
			case 'images':			return $this->images();
			case 'presentation':	return $this->presentation();
			case 'checkout':		return $this->checkout();
			case 'downloads':		return $this->downloads();
			case 'management': 		return $this->management();
			default:				return $this->setup();
		}

	}

	/**
	 * Displays the General Settings screen and processes updates
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function setup () {
		if ( ! current_user_can('shopp_settings') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		// Welcome screen handling
		if ( ! empty($_POST['setup']) ) {
			$_POST['settings']['display_welcome'] = 'off';
			shopp_set_formsettings();
		}

		$country = (isset($_POST['settings']) && isset($_POST['settings']['base_operations']))?$_POST['settings']['base_operations']['country']:'';
		$countries = array();
		$countrydata = Lookup::countries();
		$country_zones = Lookup::country_zones();
		foreach ($countrydata as $iso => $c) {
			if ($country == $iso) $base_region = $c['region'];
			$countries[$iso] = $c['name'];
		}

		// Save settings
		if ( ! empty($_POST['save']) && isset($_POST['settings'])) {
			check_admin_referer('shopp-setup');

			if (isset($_POST['settings']['base_operations'])) {
				$baseop = &$_POST['settings']['base_operations'];

				$zone = isset($baseop['zone']) && isset($country_zones[ $country ]) && isset($country_zones[ $country ][ $baseop['zone'] ]) ? $baseop['zone']:false;
				if (isset($countrydata[$country])) $baseop = $countrydata[$country];
				$baseop['country'] = $country;
				$baseop['zone'] = $zone;
				$baseop['currency']['format'] = scan_money_format($baseop['currency']['format']);
				if ( is_array($baseop['currency']['format']) ) {
					$fields = array_keys($baseop['currency']['format']);
					foreach ($fields as $field)
						if (isset($baseop['currency'][$field])) $baseop['currency']['format'][$field] = $baseop['currency'][$field];
				}

				shopp_set_setting('tax_inclusive', // Automatically set the inclusive tax setting
					(in_array($country, Lookup::country_inclusive_taxes()) ? 'on' : 'off')
				);
			}

			if (!isset($_POST['settings']['target_markets']))
				asort($_POST['settings']['target_markets']);

			shopp_set_formsettings();
			$updated = __('Shopp settings saved.', 'Shopp');
		}

		$operations = shopp_setting('base_operations');
		if (isset($country_zones[ $operations['country'] ]))
			$zones = $country_zones[ $operations['country'] ];

		$targets = shopp_setting('target_markets');
		if (is_array($targets))	$targets = array_map('stripslashes',$targets);
		if (!$targets) $targets = array();

		include $this->ui('setup.php');
	}

	public function presentation () {
		if ( ! current_user_can('shopp_settings_presentation') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$builtin_path = SHOPP_PATH.'/templates';
		$theme_path = sanitize_path(STYLESHEETPATH.'/shopp');

		$term_recount = false;

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-presentation');
			$updated = __('Shopp presentation settings saved.','Shopp');

			if (isset($_POST['settings']['theme_templates'])
				&& $_POST['settings']['theme_templates'] == 'on'
				&& !is_dir($theme_path)) {
					$_POST['settings']['theme_templates'] = 'off';
					$updated = __('Shopp theme templates can\'t be used because they don\'t exist.','Shopp');
			}

			if (empty($_POST['settings']['catalog_pagination']))
				$_POST['settings']['catalog_pagination'] = 0;

			// Recount terms when this setting changes
			if ( isset($_POST['settings']['outofstock_catalog']) &&
				$_POST['settings']['outofstock_catalog'] != shopp_setting('outofstock_catalog')) {
				$term_recount = true;
			}

			shopp_set_formsettings();
			$this->notice(Shopp::__('Presentation settings saved.'), 'notice', 20);
		}

		if ($term_recount) {
			$taxonomy = ProductCategory::$taxon;
			$terms = get_terms( $taxonomy, array('hide_empty' => 0,'fields'=>'ids') );
			if ( ! empty($terms) )
				wp_update_term_count_now( $terms, $taxonomy );
		}

		// Copy templates to the current WordPress theme
		if (!empty($_POST['install'])) {
			check_admin_referer('shopp-settings-presentation');
			copy_shopp_templates($builtin_path,$theme_path);
		}

		$status = 'available';
		if (!is_dir($theme_path)) $status = 'directory';
		else {
			if (!is_writable($theme_path)) $status = 'permissions';
			else {
				$builtin = array_filter(scandir($builtin_path),'filter_dotfiles');
				$theme = array_filter(scandir($theme_path),'filter_dotfiles');
				if (empty($theme)) $status = 'ready';
				else if (array_diff($builtin,$theme)) $status = 'incomplete';
			}
		}

		$category_views = array('grid' => __('Grid','Shopp'),'list' => __('List','Shopp'));
		$row_products = array(2,3,4,5,6,7);
		$productOrderOptions = ProductCategory::sortoptions();
		$productOrderOptions['custom'] = __('Custom','Shopp');

		$orderOptions = array('ASC' => __('Order','Shopp'),
							  'DESC' => __('Reverse Order','Shopp'),
							  'RAND' => __('Shuffle','Shopp'));

		$orderBy = array('sortorder' => __('Custom arrangement','Shopp'),
						 'created' => __('Upload date','Shopp'));


		include $this->ui('presentation.php');
	}

	public function management () {
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_settings_checkout') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$purchasetable = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$next = sDB::query("SELECT IF ((MAX(id)) > 0,(MAX(id)+1),1) AS id FROM $purchasetable LIMIT 1");
		$next_setting = shopp_setting('next_order_id');

		if ($next->id > $next_setting) $next_setting = $next->id;

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-setup-management');

			$next_order_id = $_POST['settings']['next_order_id'] = intval($_POST['settings']['next_order_id']);

			if ($next_order_id >= $next->id) {
				if ( sDB::query("ALTER TABLE $purchasetable AUTO_INCREMENT=" . sDB::escape($next_order_id) ) )
					$next_setting = $next_order_id;
			}


			shopp_set_formsettings();
			$this->notice(Shopp::__('Management settings saved.'), 'notice', 20);
		}

		$states = array(
			__('Map the label to an order state:','Shopp') => array_merge(array('' => ''),Lookup::txnstatus_labels())
		);
		$statusLabels = shopp_setting('order_status');
		$statesLabels = shopp_setting('order_states');
		$reasonLabels = shopp_setting('cancel_reasons');

		if (empty($reasonLabels)) $reasonLabels = array(
			__('Not as described or expected','Shopp'),
			__('Wrong size','Shopp'),
			__('Found better prices elsewhere','Shopp'),
			__('Product is missing parts','Shopp'),
			__('Product is defective or damaaged','Shopp'),
			__('Took too long to deliver','Shopp'),
			__('Item out of stock','Shopp'),
			__('Customer request to cancel','Shopp'),
			__('Item discontinued','Shopp'),
			__('Other reason','Shopp')
		);

		include $this->ui('management.php');
	}

	public function checkout () {

		if ( ! current_user_can('shopp_settings_checkout') )
			wp_die(__('You do not have sufficient permissions to access this page.'));


		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-settings-checkout');

			shopp_set_formsettings();
			$this->notice(Shopp::__('Checkout settings saved.'), 'notice', 20);

		}

		$promolimit = array('1','2','3','4','5','6','7','8','9','10','15','20','25');

		include $this->ui('checkout.php');
	}


	public function downloads () {

		if ( ! current_user_can('shopp_settings_checkout') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$downloads = array('1','2','3','5','10','15','25','100');
		$time = array(
			'1800' => Shopp::__('%d minutes', 30),
			'3600' => Shopp::__('%d hour', 1),
			'7200' => Shopp::__('%d hours', 2),
			'10800' => Shopp::__('%d hours', 3),
			'21600' => Shopp::__('%d hours', 6),
			'43200' => Shopp::__('%d hours', 12),
			'86400' => Shopp::__('%d day', 1),
			'172800' => Shopp::__('%d days', 2),
			'259200' => Shopp::__('%d days', 3),
			'604800' => Shopp::__('%d week', 1),
			'2678400' => Shopp::__('%d month', 1),
			'7952400' => Shopp::__('%d months', 3),
			'15901200' => Shopp::__('%d months', 6),
			'31536000' => Shopp::__('%d year', 1),
		);

		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-settings-downloads');

			shopp_set_formsettings();
			$this->notice(Shopp::__('Downloads settings saved.'), 'notice', 20);

		}

		include $this->ui('downloads.php');

	}

	public function pages () {

		if ( ! current_user_can('shopp_settings') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-settings-pages');

			$CatalogPage = ShoppPages()->get('catalog');
			$catalog_slug = $CatalogPage->slug();
			$defaults = ShoppPages()->settings();
			$_POST['settings']['storefront_pages'] = array_merge($defaults,$_POST['settings']['storefront_pages']);
			shopp_set_formsettings();

			// Re-register page, collection, taxonomies and product rewrites
			// so that the new slugs work immediately
			$Shopp = Shopp::object();
			$Shopp->pages();
			$Shopp->collections();
			$Shopp->taxonomies();
			$Shopp->products();

			// If the catalog slug changes
			// $hardflush is false (soft flush... plenty of fiber, no .htaccess update needed)
			$hardflush = ( ShoppPages()->baseslug() != $catalog_slug );
			flush_rewrite_rules($hardflush);
		}

		$pages = ShoppPages()->settings();
		include $this->ui('pages.php');

	}

	public function pages_ui () {
		register_column_headers('shopp_page_shopp-settings-pages', array(
			'title'=>__('Title','Shopp'),
			'slug'=>__('Slug','Shopp'),
			'decription'=>__('Description','Shopp')
		));
	}

	public function images () {

		if ( ! current_user_can('shopp_settings') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$defaults = array(
			'paged' => 1,
			'per_page' => 25,
			'action' => false,
			'selected' => array(),
		);
		$args = array_merge($defaults,$_REQUEST);
		extract($args,EXTR_SKIP);

		$edit = false;
		if (isset($_GET['id']))  {
			$edit = (int)$_GET['id'];
			if ('new' == $_GET['id']) $edit = 'new';
		}

		if (isset($_GET['delete']) || 'delete' == $action) {
			check_admin_referer('shopp-settings-images');

			if (!empty($_GET['delete'])) $selected[] = (int)$_GET['delete'];
			$selected = array_filter($selected);
			foreach ($selected as $delete) {
				$Record = new ImageSetting( (int)$delete );
				$Record->delete();
			}
		}

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-settings-images');

			$ImageSetting = new ImageSetting($edit);
			$_POST['name'] = sanitize_title_with_dashes($_POST['name']);
			$_POST['sharpen'] = floatval(str_replace('%','',$_POST['sharpen']));
			$ImageSetting->updates($_POST);
			if (!empty($ImageSetting->name)) $ImageSetting->save();
		}

		$start = ($per_page * ($paged-1));

		$ImageSetting = new ImageSetting($edit);
		$table = $ImageSetting->_table;
		$columns = 'SQL_CALC_FOUND_ROWS *';
		$where = array(
			"type='$ImageSetting->type'",
			"context='$ImageSetting->context'"
		);
		$limit = "$start,$per_page";

		$options = compact('columns','useindex','table','joins','where','groupby','having','limit','orderby');
		$query = sDB::select($options);
		$settings = sDB::query($query,'array',array($ImageSetting,'loader'));
		$total = sDB::found();

		$num_pages = ceil($total / $per_page);
		$ListTable = ShoppUI::table_set_pagination( $this->screen, $total, $num_pages, $per_page );

		$fit_menu = $ImageSetting->fit_menu();
		$quality_menu = $ImageSetting->quality_menu();

		$actions_menu = array(
			'delete' => __('Delete','Shopp')
		);

		$json_settings = array();
		$skip = array('created','modified','numeral','context','type','sortorder','parent');
		foreach ($settings as &$Setting)
			if (method_exists($Setting,'json'))
				$json_settings[$Setting->id] = $Setting->json($skip);

		include $this->ui('images.php');
	}

	public function images_ui () {
		ShoppUI::register_column_headers('shopp_page_shopp-settings-images', array(
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'dimensions'=>__('Dimensions','Shopp'),
			'fit'=>__('Fit','Shopp'),
			'quality'=>__('Quality','Shopp'),
			'sharpness'=>__('Sharpness','Shopp')
		));
	}

}
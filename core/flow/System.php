<?php
/**
 * System.php
 *
 * Flow controller for settings management
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, January 2010-2013
 * @package shopp
 * @version 1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Setup
 *
 * @package shopp
 * @author Jonathan Davis
 **/
class ShoppAdminSystem extends ShoppAdminController {

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
			case 'taxes':
				shopp_enqueue_script('ocupload');
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('taxrates');
				shopp_enqueue_script('suggest');
				shopp_localize_script('taxrates', '$tr', array(
					'confirm' => Shopp::__('Are you sure you want to remove this tax rate?'),
				));

				$this->subscreens = array(
					'rates'	   => Shopp::__('Rates'),
					'settings' => Shopp::__('Settings')
				);

				if ( isset($_GET['sub']) ) $this->url = add_query_arg(array('sub' => esc_attr($_GET['sub'])), $this->url);
				else $_GET['sub'] = shopp_setting_enabled('taxes') ? 'rates' : 'settings';

				if ( shopp_setting_enabled('taxes') )
					$this->taxrate_ui();

				break;
			case 'advanced':
				shopp_enqueue_script('colorbox');
				shopp_enqueue_script('system');
				shopp_localize_script( 'system', '$sys', array(
					'indexing' => Shopp::__('Product Indexing'),
					'indexurl' => wp_nonce_url(add_query_arg('action', 'shopp_rebuild_search_index', admin_url('admin-ajax.php')), 'wp_ajax_shopp_rebuild_search_index')
				));
				break;
			case 'storage':
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('storage');
				break;
			case 'shipping':
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('shiprates');
				shopp_localize_script( 'shiprates', '$ps', array(
					'confirm' => Shopp::__('Are you sure you want to remove this shipping rate?'),
				));

				$this->subscreens = array(
					'rates'	   => Shopp::__('Rates'),
					'settings' => Shopp::__('Settings')
				);

				if ( isset($_GET['sub']) ) $this->url = add_query_arg(array('sub' => esc_attr($_GET['sub'])), $this->url);
				else $_GET['sub'] = shopp_setting_enabled('taxes') ? 'rates' : 'settings';

				if ( shopp_setting_enabled('shipping') )
					$this->shipping_ui();

				break;
			case 'payments':
			default:
				shopp_enqueue_script('jquery-tmpl');
				shopp_enqueue_script('payments');
				shopp_localize_script( 'payments', '$ps', array(
					'confirm' => Shopp::__('Are you sure you want to remove this payment system?'),
				));

				add_action("load-$this->screen", array($this, 'payments_help'), 20);

				$this->payments_ui();
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
		global $pagenow;

		switch($this->pagename) {
			case 'payments': 	$this->payments(); break;
			case 'shipping': 	$this->shipping(); break;
			case 'taxes': 		$this->taxes(); break;
			case 'storage': 	$this->storage(); break;
			case 'advanced': 	$this->advanced(); break;
			case 'log': 		$this->log(); break;
			default:		$this->payments();
		}
	}

	/**
	 * Renders the shipping settings screen and processes updates
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function shipping () {

		if ( ! current_user_can('shopp_settings_shipping') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$sub = 'settings';
		$term_recount = false;
		if ( shopp_setting_enabled('shipping') ) $sub = 'rates';
		if ( isset($_GET['sub']) && in_array( $_GET['sub'], array_keys($this->subscreens) ) )
			$sub = $_GET['sub'];

		if ( ! empty($_POST['save']) && empty($_POST['module']) ) {
			check_admin_referer('shopp-settings-shipping');

			$_POST['settings']['order_shipfee'] = Shopp::floatval($_POST['settings']['order_shipfee']);

			// Recount terms when this setting changes
			if ( isset($_POST['settings']['inventory']) &&
				$_POST['settings']['inventory'] != shopp_setting('inventory') ) {
				$term_recount = true;
			}

	 		shopp_set_formsettings();
			$updated = Shopp::__('Shipping settings saved.');
		}

		// Handle ship rates UI
		if ( 'rates' == $sub && 'on' == shopp_setting('shipping') ) return $this->shiprates();


		if ( $term_recount ) {
			$taxonomy = ProductCategory::$taxon;
			$terms    = get_terms( $taxonomy, array('hide_empty' => 0, 'fields' => 'ids') );
			if ( ! empty($terms) )
				wp_update_term_count_now( $terms, $taxonomy );
		}

		$base       = shopp_setting('base_operations');
		$regions    = Lookup::regions();
		$region     = $regions[ $base['region'] ];
		$useRegions = shopp_setting('shipping_regions');

		$areas = Lookup::country_areas();
		if ( isset($areas[ $base['country'] ]) && is_array($areas[ $base['country'] ]) && $useRegions == 'on' )
			$areas = array_keys($areas[$base['country']]);
		else $areas = array($base['country'] => $base['name']);
		unset($countries, $regions);

		$carrierdata  = Lookup::shipcarriers();
		$serviceareas = array('*', substr($base['country'], 0, 2));
		foreach ( $carrierdata as $c => $record ) {
			if ( ! in_array($record->areas, $serviceareas) ) continue;
			$carriers[ $c ] = $record->name;
		}
		unset($carrierdata);
		$shipping_carriers = shopp_setting('shipping_carriers');
		if ( empty($shipping_carriers) ) $shipping_carriers = array_keys($carriers);

		$rates = shopp_setting('shipping_rates');
		if ( ! empty($rates) ) ksort($rates);

		$lowstock = shopp_setting('lowstock_level');
		if ( empty($lowstock) ) $lowstock = 0;

		include $this->ui('shipping.php');
	}

	public function shiprates () {

		if ( ! current_user_can('shopp_settings_shipping') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$Shopp    = Shopp::object();
		$Shipping = $Shopp->Shipping;
		$Shipping->settings(); // Load all installed shipping modules for settings UIs

		$methods = $Shopp->Shipping->methods;

		$edit = false;
		if ( isset($_REQUEST['id']) ) $edit = (int)$_REQUEST['id'];

		$active = shopp_setting('active_shipping');
		if ( ! $active ) $active = array();

		if ( ! empty($_GET['delete']) ) {
			check_admin_referer('shopp_delete_shiprate');
			$delete = $_GET['delete'];
			$index  = false;
			if ( strpos($delete, '-') !== false )
				list($delete, $index) = explode('-', $delete);

			if ( array_key_exists($delete, $active) )  {
				if ( is_array($active[ $delete ]) ) {
					if ( array_key_exists($index, $active[ $delete ]) ) {
						unset($active[ $delete ][ $index ]);
						if ( empty($active[ $delete ]) ) unset($active[ $delete ]);
					}
				} else unset($active[$delete]);
				$updated = Shopp::__('Shipping method setting removed.');

				shopp_set_setting('active_shipping', $active);
			}
		}

		if ( isset($_POST['module']) ) {
			check_admin_referer('shopp-settings-shiprate');

			$setting = false;
			$module  = isset($_POST['module']) ? $_POST['module'] : false;
			$id      = isset($_POST['id']) ? $_POST['id'] : false;

			if ( $id == $module ) {
				if ( isset($_POST['settings']) ) shopp_set_formsettings();
				/** Save shipping service settings **/
				$active[ $module ] = true;
				shopp_set_setting('active_shipping', $active);
				$updated = Shopp::__('Shipping settings saved.');
				// Cancel editing if saving
				if ( isset($_POST['save']) ) unset($_REQUEST['id']);

				$Errors = ShoppErrors();
				do_action('shopp_verify_shipping_services');

				if ( $Errors->exist() ) {
					// Get all addon related errors
					$failures = $Errors->level(SHOPP_ADDON_ERR);
					if ( ! empty($failures) ) {
						$updated = Shopp::__('Shipping settings saved but there were errors: ');
						foreach ( $failures as $error )
							$updated .= '<p>' . $error->message(true, true) . '</p>';
					}
				}

			} else {
				/** Save shipping calculator settings **/

				$setting = $_POST['id'];
				if ( empty($setting) ) { // Determine next available setting ID
					$index = 0;
					if ( isset($active[ $module ]) && is_array($active[ $module ]) ) $index = count($active[ $module ]);
					$setting = "$module-$index";
				}

				// Cancel editing if saving
				if ( isset($_POST['save']) ) unset($_REQUEST['id']);

				$setting_module = $setting; $id = 0;
				if ( false !== strpos($setting, '-') )
					list($setting_module, $id) = explode('-', $setting);

				// Prevent fishy stuff from happening
				if ( $module != $setting_module ) $module = false;

				// Save shipping calculator settings
				$Shipper = $Shipping->get($module);
				if ( $Shipper && isset($_POST[ $module ]) ) {
					$Shipper->setting($id);

					$_POST[ $module ]['label'] = stripslashes($_POST[ $module ]['label']);

					// Sterilize $values
					foreach ( $_POST[$module]['table'] as $i => &$row ) {
						if ( isset($row['rate']) ) $row['rate'] = Shopp::floatval($row['rate']);
						if ( ! isset($row['tiers']) ) continue;

						foreach ( $row['tiers'] as &$tier ) {
							if ( isset($tier['rate']) ) $tier['rate'] = Shopp::floatval($tier['rate']);
						}
					}

					// Delivery estimates: ensure max equals or exceeds min
					ShippingFramework::sensibleestimates($_POST[ $module ]['mindelivery'], $_POST[ $module ]['maxdelivery']);

					shopp_set_setting($Shipper->setting, $_POST[ $module ]);
					if ( ! array_key_exists($module, $active) ) $active[ $module ] = array();
					$active[ $module ][(int) $id] = true;
					shopp_set_setting('active_shipping', $active);
					$this->notice(Shopp::__('Shipping settings saved.'));
				}

			}
		}

		$Shipping->settings(); // Load all installed shipping modules for settings UIs

		$Shipping->ui(); // Setup setting UIs
		$installed = array();
		$shiprates = array();	// Registry for activated shipping rate modules
		$settings  = array();	// Registry of loaded settings for table-based shipping rates for JS

		foreach ( $Shipping->active as $name => $module ) {
			if ( version_compare($Shipping->modules[ $name ]->since, '1.2') == -1 ) continue; // Skip 1.1 modules, they are incompatible

			$default_name       = strtolower($name);
			$fullname           = $module->methods();
			$installed[ $name ] = $fullname;

			if ( $module->ui->tables ) {
				$defaults[ $default_name ]          = $module->ui->settings();
				$defaults[ $default_name ]['name']  = $fullname;
				$defaults[ $default_name ]['label'] = Shopp::__('Shipping Method');
			}

			if ( array_key_exists($name, $active) ) $ModuleSetting = $active[ $name ];
			else continue; // Not an activated shipping module, go to the next one

			// Setup shipping service shipping rate entries and settings
			if ( ! is_array($ModuleSetting) ) {
				$shiprates[ $name ] = $name;
				continue;
			}

			// Setup shipping calcualtor shipping rate entries and settings
			foreach ( $ModuleSetting as $id => $m ) {
				$setting = "$name-$id";
				$shiprates[ $setting ] = $name;

				$settings[ $setting ]       = shopp_setting($setting);
				$settings[ $setting ]['id'] = $setting;
				$settings[ $setting ]       = array_merge($defaults[ $default_name ], $settings[ $setting ]);
				if ( isset($settings[ $setting ]['table']) ) {
					usort($settings[ $setting ]['table'], array('ShippingFramework', '_sorttier'));
					foreach ( $settings[ $setting ]['table'] as &$r ) {
						if ( isset($r['tiers']) ) usort($r['tiers'], array('ShippingFramework', '_sorttier'));
					}
				}
			}

		}

		if ( isset($_REQUEST['id']) ) {
			$edit = $_REQUEST['id'];
			$id   = false;
			if ( strpos($edit, '-') !== false )
				list($module, $id) = explode('-', $edit);
			else $module = $edit;
			if ( isset($Shipping->active[ $module ]) ) {
				$Shipper = $Shipping->get($module);
				if ( ! $Shipper->singular ) {
					$Shipper->setting($id);
					$Shipper->initui($Shipping->modules[ $module ]->name); // Re-init setting UI with loaded settings
				}
				$editor = $Shipper->ui();
			}

		}

		asort($installed);

		$countrydata  = Lookup::countries();
		$countries    = $regionmap = $postcodes = array();
		$postcodedata = Lookup::postcodes();
		foreach ( $countrydata as $code => $country ) {
			$countries[ $code ] = $country['name'];
			if ( ! isset($regionmap[ $country['region'] ]) ) $regionmap[ $country['region'] ] = array();
			$regionmap[ $country['region'] ][] = $code;
			if ( isset($postcodedata[$code]) ) {
				if ( ! isset($postcodes[ $code ]) ) $postcodes[ $code ] = array();
				$postcodes[ $code ] = true;
			}
		}
		unset($countrydata);
		unset($postcodedata);


		$lookup = array(
			'regions'   => array_merge(array('*' => Shopp::__('Anywhere')), Lookup::regions()),
			'regionmap' => $regionmap,
			'countries' => $countries,
			'areas'	    => Lookup::country_areas(),
			'zones'	    => Lookup::country_zones(),
			'postcodes' => $postcodes
		);

		$ShippingTemplates = new TemplateShippingUI();
		add_action('shopp_shipping_module_settings', array($Shipping, 'templates'));
		include $this->ui('shiprates.php');
	}

	public function shipping_menu () {
		if ( ! shopp_setting_enabled('shipping') ) return;
		?>
		<ul class="subsubsub">
			<?php $i = 0; foreach ( $this->subscreens as $screen => $label ):  $url = add_query_arg(array('sub' => $screen), $this->url); ?>
				<li><a href="<?php echo esc_url($url); ?>"<?php if ( $_GET['sub'] == $screen ) echo ' class="current"'; ?>><?php echo $label; ?></a><?php if (count($this->subscreens)-1!=$i++): ?> | <?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
		<br class="clear" />
		<?php
	}

	public function shipping_ui () {
		register_column_headers('shopp_page_shopp-settings-shipping', array(
			'name'         => __('Name'),
			'type'         => Shopp::__('Type'),
			'destinations' => Shopp::__('Destinations')
		));
	}

	public function taxes () {
		if ( ! current_user_can('shopp_settings_taxes') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$sub = 'settings';
		if ( shopp_setting_enabled('taxes') ) $sub = 'rates';
		if ( isset($_GET['sub']) && in_array($_GET['sub'], array_keys($this->subscreens)) )
			$sub = $_GET['sub'];

		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-settings-taxes');
			shopp_set_formsettings();
			$updated = Shopp::__('Tax settings saved.');
		}

		// Handle ship rates UI
		if ( 'rates' == $sub && 'on' == shopp_setting('taxes') ) return $this->taxrates();

		include $this->ui('taxes.php');
	}

	public function taxes_menu () {
		if ( ! shopp_setting_enabled('taxes') ) return;
		?>
		<ul class="subsubsub">
			<?php $i = 0; foreach ($this->subscreens as $screen => $label):  $url = add_query_arg(array('sub' => $screen), $this->url); ?>
				<li><a href="<?php echo esc_url($url); ?>"<?php if ($_GET['sub'] == $screen) echo ' class="current"'; ?>><?php echo $label; ?></a><?php if (count($this->subscreens)-1!=$i++): ?> | <?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
		<br class="clear" />
		<?php
	}

	public function taxrate_ui () {
		register_column_headers('shopp_page_shopp-settings-taxrates', array(
			'rate'        => Shopp::__('Rate'),
			'local'       => Shopp::__('Local Rates'),
			'conditional' => Shopp::__('Conditional')
		));
	}

	public function taxrates () {
		if ( ! current_user_can('shopp_settings_taxes') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$edit = false;
		if ( isset($_REQUEST['id']) ) $edit = (int)$_REQUEST['id'];
		$localerror = false;

		$rates = shopp_setting('taxrates');
		if ( ! is_array($rates) ) $rates = array();

		if ( isset($_GET['delete']) ) {
			check_admin_referer('shopp_delete_taxrate');
			$delete = (int)$_GET['delete'];
			if ( isset($rates[ $delete ]) )
				array_splice($rates, $delete, 1);
			shopp_set_setting('taxrates', $rates);
		}

		if ( isset($_POST['editing']) ) $rates[ $edit ] = $_POST['settings']['taxrates'][ $edit ];
		if ( isset($_POST['addrule']) ) $rates[ $edit ]['rules'][] = array('p' => '', 'v' => '');
		if ( isset($_POST['deleterule']) ) {
			check_admin_referer('shopp-settings-taxrates');
			list($rateid, $row) = explode(',', $_POST['deleterule']);
			if ( isset($rates[ $rateid ]) && isset($rates[ $rateid ]['rules']) ) {
				array_splice($rates[ $rateid ]['rules'], $row, 1);
				shopp_set_setting('taxrates', $rates);
			}
		}

		if ( isset($rates[ $edit ]['haslocals']) )
			$rates[ $edit ]['haslocals'] = ($rates[ $edit ]['haslocals'] == 'true' || $rates[ $edit ]['haslocals'] == '1');
		if (isset($_POST['add-locals']) ) $rates[ $edit ]['haslocals'] = true;
		if (isset($_POST['remove-locals']) ) {
			$rates[ $edit ]['haslocals'] = false;
			$rates[ $edit ]['locals']    = array();
		}

		$upload = $this->taxrate_upload();
		if ( $upload !== false ) {
			if ( isset($upload['error']) ) $localerror = $upload['error'];
			else $rates[ $edit ]['locals'] = $upload;
		}

		if ( isset($_POST['editing']) ) {
			// Re-sort taxes from generic to most specific
			usort($rates, array($this, 'taxrates_sorting'));
			$rates = stripslashes_deep($rates);
			shopp_set_setting('taxrates', $rates);
		}
		if ( isset($_POST['addrate']) ) $edit = count($rates);
		if ( isset($_POST['submit']) ) $edit  = false;

		$base = shopp_setting('base_operations');
		$specials = array(ShoppTax::ALL => Shopp::__('All Markets'));

		if ( ShoppTax::euvat(false, $base['country'], ShoppTax::EUVAT) )
			$specials[ ShoppTax::EUVAT ] = Shopp::__('European Union');

		$countries = array_merge($specials, (array)shopp_setting('target_markets'));

		$zones = Lookup::country_zones();

		include $this->ui('taxrates.php');
	}

	/**
	 * Helper to sort tax rates from most specific to most generic
	 *
	 * (more specific) <------------------------------------> (more generic)
	 * more/less conditions, local taxes, country/zone, country, All Markets
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $rates The tax rate settings to sort
	 * @return int The sorting value
	 **/
	public function taxrates_sorting ($a, $b) {

		$args    = array('a' => $a, 'b' => $b);
		$scoring = array('a' => 0, 'b' => 0);

		foreach ( $args as $key => $rate ) {
			$score = &$scoring[ $key ];

			// More conditional rules are more specific
			if ( isset($rate['rules']) ) $score += count($rate['rules']);

			// If there are local rates add to specificity
			if ( isset($rate['haslocals']) && $rate['haslocals'] ) $score++;

			if ( isset($rate['zone']) && $rate['zone'] ) $score++;

			if ( '*' != $rate['country'] ) $score++;

			$score += (float)$rate['rate'] / 100;
		}

		if ( $scoring['a'] == $scoring['b'] ) return 0;
		else return ( $scoring['a'] > $scoring['b'] ? 1 : -1 );
	}

	public function taxrate_upload () {
		if ( ! isset($_FILES['ratefile']) ) return false;

		$upload   = $_FILES['ratefile'];
		$filename = $upload['tmp_name'];
		if ( empty($filename) && empty($upload['name']) && ! isset($_POST['upload']) ) return false;

		$error = false;

		if ( $upload['error'] != 0 ) return array('error' => Lookup::errors('uploads', $upload['error']));
		if ( ! is_readable($filename) ) return array('error' => Lookup::errors('uploadsecurity', 'is_readable'));
		if ( empty($upload['size']) ) return array('error' => Lookup::errors('uploadsecurity', 'is_empty'));
		if ( $upload['size'] != filesize($filename) ) return array('error' => Lookup::errors('uploadsecurity', 'filesize_mismatch'));
		if ( ! is_uploaded_file($filename) ) return array('error' => Lookup::errors('uploadsecurity', 'is_uploaded_file'));

		$data = file_get_contents($upload['tmp_name']);
		$cr   = array("\r\n", "\r");

		$formats = array(0 => false, 3 => 'xml', 4 => 'tab', 5 => 'csv');
		preg_match('/((<[^>]+>.+?<\/[^>]+>)|(.+?\t.+?[\n|\r])|(.+?,.+?[\n|\r]))/', $data, $_);
		$format = $formats[count($_)];
		if ( ! $format ) return array('error' => Shopp::__('The uploaded file is not properly formatted as an XML, CSV or tab-delimmited file.'));

		$_ = array();
		switch ( $format ) {
			case 'xml':
				/*
				Example XML import file:
					<localtaxrates>
						<taxrate name="Kent">1</taxrate>
						<taxrate name="New Castle">0.25</taxrate>
						<taxrate name="Sussex">1.4</taxrate>
					</localtaxrates>

				Taxrate record format:
					<taxrate name="(Name of locality)">(Percentage of the supplemental tax)</taxrate>

				Tax rate percentages should be represented as percentage numbers, not decimal percentages:
					1.25	= 1.25%	(0.0125)
					10		= 10%	(0.1)
				*/
				if ( ! class_exists('xmlQuery') )
					require(SHOPP_MODEL_PATH . '/XML.php');
				$XML      = new xmlQuery($data);
				$taxrates = $XML->tag('taxrate');
				while( $rate = $taxrates->each() ) {
					$name       = $rate->attr(false, 'name');
					$value      = $rate->content();
					$_[ $name ] = $value;
				}
				break;
			case 'csv':
				ini_set('auto_detect_line_endings', true);
				if ( ($csv = fopen($upload['tmp_name'], 'r')) === false )
					return array('error' => Lookup::errors('uploadsecurity', 'is_readable'));
				while ( ($data = fgetcsv($csv, 1000)) !== false )
					$_[$data[0]] = !empty($data[1]) ? $data[1] : 0;
				fclose($csv);
				ini_set('auto_detect_line_endings', false);
				break;
			case 'tab':
			default:
				$data  = str_replace($cr, "\n", $data);
				$lines = explode("\n", $data);
				foreach ( $lines as $line ) {
					list($key, $value) = explode("\t", $line);
					$_[ $key ] = $value;
				}
		}

		if ( empty($_) ) array('error' => Shopp::__('No useable tax rates could be found. The uploaded file may not be properly formatted.'));

		return apply_filters('shopp_local_taxrates_upload', $_);
	}

	public function payments () {
		if ( ! current_user_can('shopp_settings_payments') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$Shopp    = Shopp::object();
		$Gateways = $Shopp->Gateways;
		$editor   = '';

	 	$active_gateways = shopp_setting('active_gateways');
		if ( ! $active_gateways ) $gateways = array();
		else $gateways = explode(',', $active_gateways);

		$gateways = array_filter($gateways, array($Gateways, 'moduleclass'));

		if ( ! empty($_GET['delete']) ) {
			$delete = $_GET['delete'];
			check_admin_referer('shopp_delete_gateway');
			if ( in_array($delete, $gateways) )  {
				$position = array_search($delete, $gateways);
				array_splice($gateways, $position, 1);
				shopp_set_setting('active_gateways', join(',', $gateways));
			}
		}

		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-settings-payments');
			do_action('shopp_save_payment_settings');

			if ( isset($_POST['gateway']) ) {
				$gateway = $_POST['gateway'];

				// Handle Multi-instance payment systems
				$indexed = false;
				if ( preg_match('/\[(\d+)\]/', $gateway, $matched) ) {

					$indexed = '-' . $matched[1];
					$gateway = str_replace($matched[0], '', $gateway);

					// Merge the existing gateway settings with the newly updated settings
					if ( isset($Gateways->active[ $gateway ]) ) {
						$Gateway = $Gateways->active[ $gateway ];
						// Cannot use array_merge() because it adds numeric index values instead of overwriting them
						$_POST['settings'][ $gateway ] = (array) $_POST['settings'][ $gateway ] + (array) $Gateway->settings;
					}

				}

				if ( ! empty($gateway) && isset($Gateways->active[ $gateway ])
						&& ! in_array($gateway . $indexed, $gateways) ) {
					$gateways[] =  $gateway . $indexed;

					// Cleanup any invalid entries
					$gateways = array_filter($gateways); // Remove empty entries
					$gateways = array_flip(array_flip($gateways)); // Remove duplicates

					shopp_set_setting('active_gateways', join(',', $gateways));
				}

			} // END isset($_POST['gateway'])

			shopp_set_formsettings();
			$updated = Shopp::__('Shopp payments settings saved.');
		}

		$Gateways->settings();	// Load all installed gateways for settings UIs
		do_action('shopp_setup_payments_init');

		$installed = array();
		foreach ( (array)$Gateways->modules as $slug => $module )
			$installed[$slug] = $module->name;

		$edit = false;
		$Gateways->ui();		// Setup setting UIs

		if ( isset($_REQUEST['id']) && Shopp::__('Add a payment system...') != $_REQUEST['id'] ) {
			$edit    = $_REQUEST['id'];
			$gateway = $edit;
			$id      = false;		// Instance ID for multi-instance gateways
			if ( false !== strpos($edit, '-') ) list($gateway, $id) = explode('-', $gateway);
			if ( isset($Gateways->active[ $gateway ]) ) {
				$Gateway = $Gateways->get($gateway);
				if ( $Gateway->multi && false === $id ) {
					unset($Gateway->settings['cards'], $Gateway->settings['label']);
					$id = count($Gateway->settings);
				}
				$editor = $Gateway->ui($id);
			}
		}

		asort($installed);

		add_action('shopp_gateway_module_settings', array($Gateways, 'templates'));
		include $this->ui('payments.php');
	}

	public function payments_help () {
		$Shopp = Shopp::object();
		$Gateways = $Shopp->Gateways;
		$Gateways->settings();	// Load all installed gateways for help tabs
		$Gateways->help();
	}

	public function payments_ui () {
		register_column_headers('shopp_page_shopp-settings-payments', array(
			'name'      => __('Name'),
			'processor' => Shopp::__('Processor'),
			'payments'  => Shopp::__('Payments'),
			'ssl'       => Shopp::__('SSL'),
			'captures'  => Shopp::__('Captures'),
			'recurring' => Shopp::__('Recurring'),
			'refunds'   => Shopp::__('Refunds')
		));
	}

	public function advanced () {
		if ( ! current_user_can('shopp_settings_system') )
			wp_die(__('You do not have sufficient permissions to access this page.'));


		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-system-advanced');

			if ( ! isset($_POST['settings']['error_notifications']) )
				$_POST['settings']['error_notifications'] = array();

			shopp_set_formsettings();

			// Reinitialize Error System
			ShoppErrors()->reporting( (int)shopp_setting('error_logging') );
			ShoppErrorLogging()->loglevel( (int)shopp_setting('error_logging') );
			ShoppErrorNotification()->setup();

			if ( isset($_POST['shopp_services_plugins']) && $this->helper_installed() ) {
				add_option('shopp_services_plugins'); // Add if it doesn't exist
				update_option('shopp_services_plugins', $_POST['shopp_services_plugins']);
			}

			$this->notice(Shopp::__('Advanced settings saved.'));

		} elseif ( ! empty($_POST['rebuild']) ) {
			check_admin_referer('shopp-system-advanced');
			$assets = ShoppDatabaseObject::tablename(ProductImage::$table);
			$query  = "DELETE FROM $assets WHERE context='image' AND type='image'";
			if ( sDB::query($query) )
				$this->notice(Shopp::__('All cached images have been cleared.'));

		} elseif ( ! empty($_POST['resum']) ) {
			check_admin_referer('shopp-system-advanced');
			$summaries = ShoppDatabaseObject::tablename(ProductSummary::$table);
			$query     = "UPDATE $summaries SET modified='" . ProductSummary::RECALCULATE . "'";
			if ( sDB::query($query) )
				$this->notice(Shopp::__('Product summaries are set to recalculate.'));

		} elseif ( isset($_POST['shopp_services_helper']) ) {
			check_admin_referer('shopp-system-advanced');

			$plugin  = 'ShoppServices.php';
			$source  = SHOPP_PATH . "/core/library/$plugin";
			$install = WPMU_PLUGIN_DIR . '/' . $plugin;

			if ( false === ( $creds = request_filesystem_credentials($this->url, '', false, false, null) ) )
				return true; // stop the normal page form from displaying

			if ( ! WP_Filesystem($creds) ) { // credentials were no good, ask for them again
				request_filesystem_credentials($this->url, '', false, false, null);
				return true;
			}

			global $wp_filesystem;

			if ( 'install' == $_POST['shopp_services_helper'] ) {

				if ( ! $wp_filesystem->exists($install) ) {
					if ( $wp_filesystem->exists(WPMU_PLUGIN_DIR) || $wp_filesystem->mkdir(WPMU_PLUGIN_DIR, FS_CHMOD_DIR) ) {
						// Install the mu-plugin helper
						$wp_filesystem->copy($source, $install, true, FS_CHMOD_FILE);
					} else $this->notice(Shopp::_mi('The services helper could not be installed because the `mu-plugins` directory could not be created. Check the file permissions of the `%s` directory on the web aserver.', WP_CONTENT_DIR), 'error');
				}

				if ( $wp_filesystem->exists($install) ) {
					shopp_set_setting('shopp_services_helper', 'on');
					$this->notice(Shopp::__('Services helper installed.'));
				} else $this->notice(Shopp::__('The services helper failed to install.'), 'error');

			} elseif ( 'remove' == $_POST['shopp_services_helper'] ) {
				global $wp_filesystem;

				if ( $wp_filesystem->exists($install) )
					$wp_filesystem->delete($install);

				if ( ! $wp_filesystem->exists($install) ) {
					shopp_set_setting('shopp_services_helper', 'off');
					$this->notice(Shopp::__('Services helper uninstalled.'));
				} else {
					$this->notice(Shopp::__('Services helper could not be uninstalled.'), 'error');
				}
			}
		}

		$notifications = shopp_setting('error_notifications');
		if ( empty($notifications) ) $notifications = array();

		$notification_errors = array(
			SHOPP_TRXN_ERR  => Shopp::__('Transaction Errors'),
			SHOPP_AUTH_ERR  => Shopp::__('Login Errors'),
			SHOPP_ADDON_ERR => Shopp::__('Add-on Errors'),
			SHOPP_COMM_ERR  => Shopp::__('Communication Errors'),
			SHOPP_STOCK_ERR => Shopp::__('Inventory Warnings')
		);

		$errorlog_levels = array(
			0               => Shopp::__('Disabled'),
			SHOPP_ERR       => Shopp::__('General Shopp Errors'),
			SHOPP_TRXN_ERR  => Shopp::__('Transaction Errors'),
			SHOPP_AUTH_ERR  => Shopp::__('Login Errors'),
			SHOPP_ADDON_ERR => Shopp::__('Add-on Errors'),
			SHOPP_COMM_ERR  => Shopp::__('Communication Errors'),
			SHOPP_STOCK_ERR => Shopp::__('Inventory Warnings'),
			SHOPP_ADMIN_ERR => Shopp::__('Admin Errors'),
			SHOPP_DB_ERR    => Shopp::__('Database Errors'),
			SHOPP_PHP_ERR   => Shopp::__('PHP Errors'),
			SHOPP_ALL_ERR   => Shopp::__('All Errors'),
			SHOPP_DEBUG_ERR => Shopp::__('Debugging Messages')
		);

		$plugins         = get_plugins();
		$service_plugins = get_option('shopp_services_plugins');

		include $this->ui('advanced.php');
	}

	public function helper_installed () {
		$plugins = wp_get_mu_plugins();
		foreach ( $plugins as $plugin )
			if ( false !== strpos($plugin, 'ShoppServices.php') ) return true;
		return false;
	}

//	public static function install_services_helper () {
//		if ( ! self::filesystemcreds() ) {
//
//		}
//	}
//
//	protected static function filesystemcreds () {
//		if ( false === ( $creds = request_filesystem_credentials($this->url, '', false, false, null) ) ) {
//			return false; // stop the normal page form from displaying
//		}
//
//		if ( ! WP_Filesystem($creds) ) { // credentials were no good, ask for them again
//			request_filesystem_credentials($this->url, $method, true, false, $form_fields);
//			return false;
//		}
//		return $creds;
//	}

	public function log () {
		if ( isset($_POST['resetlog']) ) {
			check_admin_referer('shopp-system-log');
			ShoppErrorLogging()->reset();
			$this->notice(Shopp::__('The log file has been reset.'));
		}

		include $this->ui('log.php');
	}

	public function storage () {
		$Shopp   = Shopp::object();
		$Storage = $Shopp->Storage;
		$Storage->settings();	// Load all installed storage engines for settings UIs


		if ( ! empty($_POST['save']) ) {
			check_admin_referer('shopp-system-storage');

			shopp_set_formsettings();

			// Re-initialize Storage Engines with new settings
			$Storage->settings();

			$this->notice(Shopp::__('Shopp system settings saved.'));

		} elseif ( ! empty($_POST['rebuild']) ) {
			$assets = ShoppDatabaseObject::tablename(ProductImage::$table);
			$query  = "DELETE FROM $assets WHERE context='image' AND type='image'";
			if (sDB::query($query))
				$updated = Shopp::__('All cached images have been cleared.');
		}

		// Build the storage options menu
		$storage = $engines = $storageset = array();
		foreach ( $Storage->active as $module ) {
			$storage[ $module->module ] = $module->name;
			$engines[ $module->module ] = sanitize_title_with_dashes($module->module);
			$storageset[ $module->module ] = $Storage->get($module->module)->settings;
		}

		$Storage->ui();		// Setup setting UIs

		$ImageStorage    = false;
		$DownloadStorage = false;
		if ( isset($_POST['image-settings']) )
			$ImageStorage = $Storage->get(shopp_setting('image_storage'));

		if ( isset($_POST['download-settings']) )
			$DownloadStorage = $Storage->get(shopp_setting('product_storage'));

		add_action('shopp_storage_engine_settings', array($Storage, 'templates'));

		include $this->ui('storage.php');
	}

	public function storage_ui () {
		$Shopp = Shopp::object();
		$Shopp->Storage->settings();
		$Shopp->Storage->ui();
	}

	public static function reindex () {

		check_admin_referer('wp_ajax_shopp_rebuild_search_index');

		shopp_empty_search_index();

		add_action('shopp_rebuild_search_index_init', array('ShoppAdminSystem', 'reindex_init'), 10, 3);
		add_action('shopp_rebuild_search_index_progress', array('ShoppAdminSystem', 'reindex_progress'), 10, 3);
		add_action('shopp_rebuild_search_index_completed', array('ShoppAdminSystem', 'reindex_completed'), 10, 3);

		shopp_rebuild_search_index();

		exit;

	}

	public static function reindex_init ( $indexed, $total, $start ) {
		echo str_pad(header("X-Accel-Buffering: no").header("Content-Encoding: none").'<html><body><script type="text/javascript">var indexProgress = 0;</script>' . "\n", 4096, ' ');
		@ob_flush();
		@flush();
	}

	public static function reindex_progress ( $indexed, $total, $start ) {
		if ( $total == 0 ) return;
		echo str_pad('<script type="text/javascript">indexProgress = ' . $indexed/(int)$total . ';</script>' . "\n", 4096, ' ');
		if ( ob_get_length() ) {
			@ob_flush();
			@flush();
		}
	}

	public static function reindex_completed ( $indexed, $total, $start ) {
		echo str_pad('</body><html>'."\n", 4096, ' ');
		if ( ob_get_length() )
			@ob_end_flush();
	}


} // END class System

<?php
/**
 * Install.php
 *
 * Flow controller for installation and upgrades
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January  6, 2010
 * @package shopp
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppInstallation
 *
 * @package shopp
 * @author Jonathan Davis
 **/
class ShoppInstallation extends FlowController {

	static $errors = array();
	static $nextstep = array();

	/**
	 * Install constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	function __construct () {
		add_action('shopp_activate',array($this,'activate'));
		add_action('shopp_deactivate',array($this,'deactivate'));
		add_action('shopp_reinstall',array($this,'install'));
		add_action('shopp_setup',array($this,'setup'));
		add_action('shopp_setup',array($this,'images'));
		add_action('shopp_setup',array($this,'roles'));
		add_action('shopp_setup',array($this,'maintenance'));
		add_action('shopp_autoupdate',array($this,'update'));

		$this->errors = array(
			'header' => __('Shopp Activation Error','Shopp'),
			'intro' => __('Sorry! Shopp cannot be activated for this WordPress install.'),
			'dbprivileges' => __('Shopp cannot be installed because the database privileges do not allow Shopp to create new tables.', 'Shopp'),
			'nodbschema-install' => sprintf(__('Could not install the Shopp database tables because the table definitions file is missing. (%s)', 'Shopp'), SHOPP_DBSCHEMA),
			'nodbschema-upgrade' => sprintf(__('Could not upgrade the Shopp database tables because the table definitions file is missing. (%s)', 'Shopp'), SHOPP_DBSCHEMA),
			'nextstep' => sprintf(__('Try contacting your web hosting provider or server administrator for help. For more information about this error, see the %sShopp Documentation%s', 'Shopp'), '<a href="' . SHOPP_DOCS . '">', '</a>'),
			'continue' => __('Return to Plugins page')
		);

		self::$nextstep = array(
			'dbprivileges' => sprintf(__('Try contacting your web hosting provider or server administrator for help. For more information about this error, see the %sShopp Documentation%s', 'Shopp'), '<a href="' . SHOPP_DOCS . '">', '</a>'),
			'nodbschema-install' => sprintf(__('For more information about this error, see the %sShopp Documentation%s', 'Shopp'), '<a href="' . SHOPP_DOCS . '">', '</a>'),
			'nodbschema-upgrade' => sprintf(__('For more information about this error, see the %sShopp Documentation%s', 'Shopp'), '<a href="' . SHOPP_DOCS . '">', '</a>'),
		);
	}

	/**
	 * Initializes the plugin for use
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function activate () {

		// If the database schema version number is not available,
		// No tables exist, so this is a new install

		if ( 0 === ShoppSettings::dbversion() )
			$this->install();

		// Force the Shopp init action to register needed taxonomies & CPTs
		do_action('shopp_init');

		// Process any DB upgrades (if needed)
		$this->upgrades();

		do_action('shopp_setup');

		if ( ShoppSettings()->available() && shopp_setting('db_version') )
			shopp_set_setting('maintenance', 'off');

		if ( shopp_setting_enabled('show_welcome') )
			shopp_set_setting('display_welcome', 'on');

		shopp_set_setting('updates', false);
		shopp_set_setting('rebuild_rewrites', 'on');

		return true;
	}

	/**
	 * Resets plugin data when deactivated
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function deactivate () {
		$Shopp = Shopp::object();

		// Update rewrite rules (cleanup Shopp rewrites)
		remove_action('shopp_init', array($Shopp, 'pages'));
		remove_filter('rewrite_rules_array', array($Shopp, 'rewrites'));
		flush_rewrite_rules();

		shopp_set_setting('data_model', '');

		if (function_exists('get_site_transient')) $plugin_updates = get_site_transient('update_plugins');
		else $plugin_updates = get_transient('update_plugins');
		unset($plugin_updates->response[SHOPP_PLUGINFILE]);
		if (function_exists('set_site_transient')) set_site_transient('update_plugins', $plugin_updates);
		else set_transient('update_plugins', $plugin_updates);

		return true;
	}

	/**
	 * Installs the database tables and content gateway pages
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function install () {
		global $wpdb, $wp_rewrite, $wp_version, $table_prefix;

		if ( ! file_exists(SHOPP_DBSCHEMA) )
			$this->error('nodbschema-install');

		// Remove any old product post types and taxonomies to prevent duplication of irrelevant data
		DB::query("DELETE FROM $wpdb->posts WHERE post_type='" . Product::$posttype . "'");
		DB::query("DELETE FROM $wpdb->term_taxonomy WHERE taxonomy='" . ProductCategory::$taxon . "' OR taxonomy='" . ProductTag::$taxon . "'");

		// Install tables
		ob_start();
		include(SHOPP_DBSCHEMA);
		$schema = ob_get_clean();

		DB::loaddata($schema);
		unset($schema);

	}

	/**
	 * Performs database upgrades when required
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function upgrades () {
		$installed = ShoppSettings::dbversion();

		// No upgrades required
		if ( $installed == DB::$version ) return;

		shopp_set_setting('shopp_setup', '');
		shopp_set_setting('maintenance', 'on');

		// Process any database schema changes
		$this->upschema();

		if ( $installed < 1100 ) $this->upgrade_110();
		if ( $installed < 1200 ) $this->upgrade_120();

		ShoppSettings()->save('db_version', DB::$version);

	}

	public function error ($message) {
		$string = '<h1>'.$this->errors['header'].'</h1><p>'.$this->errors['intro'].'</h1></p><ul>';
		if (isset($this->errors[$message])) $string .= "<li>{$this->errors[$message]}</li>";
		$string .= '</ul><p>'.$this->nextstep[$message].'</p><p><a class="button" href="javascript:history.go(-1);">&larr; '.$this->errors['continue'].'</a></p>';
		wp_die($string);
	}

	/**
	 * Updates the database schema
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function upschema () {
		// Test to ensure Shopp can create/drop tables
		$testtable = 'shopp_db_permissions_test_'.time();
		$tests = array("CREATE TABLE $testtable ( id INT )", "DROP TABLE $testtable");
		foreach ($tests as $testquery) {
			$db = DB::get();
			DB::query($testquery);
			$error = mysql_error($db->dbh);
			if (!empty($error)) $this->error('dbprivileges');
		}

		// Make sure dbDelta() is available
		if ( ! function_exists('dbDelta') )
			require(ABSPATH.'wp-admin/includes/upgrade.php');

		// Check for the schema definition file
		if (!file_exists(SHOPP_DBSCHEMA)) $this->error('nodbschema-upgrade');

		ob_start();
		include(SHOPP_DBSCHEMA);
		$schema = ob_get_contents();
		ob_end_clean();

		// Update the table schema
		// Strip SQL comments
		$schema = preg_replace('/--\s?(.*?)\n/', "\n", $schema);
		$tables = preg_replace('/;\s+/', ';', $schema);

		ob_start(); // Suppress dbDelta errors
		$changes = dbDelta($tables);
		ob_end_clean();

		shopp_set_setting('db_updates', $changes);
	}

	/**
	 * Installed roles and capabilities used for Shopp
	 *
	 * Capabilities						Role
	 * _______________________________________________
	 *
	 * shopp_settings					admin
	 * shopp_settings_checkout
	 * shopp_settings_payments
	 * shopp_settings_shipping
	 * shopp_settings_taxes
	 * shopp_settings_presentation
	 * shopp_settings_system
	 * shopp_settings_update
	 * shopp_financials					merchant
	 * shopp_promotions
	 * shopp_products
	 * shopp_categories
	 * shopp_orders						shopp-csr
	 * shopp_customers
	 * shopp_menu
	 *
	 * @author John Dillick
	 * @since 1.1
	 *
	 **/
	public function roles () {
		global $wp_roles; // WP_Roles roles container

		if ( ! $wp_roles ) $wp_roles = new WP_Roles();

		$shopp_roles = apply_filters('shopp_user_roles', array(
			'administrator'		=>	'Administrator',
			'shopp-merchant'	=>	__('Merchant', 'Shopp'),
			'shopp-csr'			=>	__('Customer Service Rep', 'Shopp')
		));

		$caps['shopp-csr'] = array(
				'shopp_customers',
				'shopp_orders',
				'shopp_menu',
				'read'
		);
		$caps['shopp-merchant'] = array_merge($caps['shopp-csr'], array(
				'shopp_categories',
				'shopp_products',
				'shopp_memberships',
				'shopp_promotions',
				'shopp_financials',
				'shopp_export_orders',
				'shopp_export_customers',
				'shopp_delete_orders',
				'shopp_delete_customers',
				'shopp_capture',
				'shopp_void',
				'shopp_refund'
		));
		$caps['administrator'] = array_merge($caps['shopp-merchant'], array(
				'shopp_settings_update',
				'shopp_settings_system',
				'shopp_settings_presentation',
				'shopp_settings_taxes',
				'shopp_settings_shipping',
				'shopp_settings_payments',
				'shopp_settings_checkout',
				'shopp_settings'
		));

		$caps = apply_filters('shopp_role_caps', $caps, $shopp_roles);
		foreach ( $shopp_roles as $role => $display ) {
			if ( $wp_roles->is_role($role) ) {
				foreach( $caps[$role] as $cap ) $wp_roles->add_cap($role, $cap, true);
			} else {
				$wp_roles->add_role($role, $display, array_combine($caps[$role], array_fill(0, count($caps[$role]), true)));
			}
		}
	}

	public function images () {
		$settings = array(
			'gallery-previews' => array('fit' => 'all', 'size' => 240, 'quality' => 100),
			'gallery-thumbnails' => array('fit' => 'all', 'size' => 64, 'quality' => 100),
			'thumbnails' => array('fit' => 'all', 'size' => 96, 'quality' => 100)
		);

		// Determine if any of the default settings exist to prevent overwriting changes
		$defaults = array_keys($settings);
		$ImageSetting = new ImageSetting();
		$options = array(
			'columns' => 'name',
			'table' => $ImageSetting->_table,
			'where' => array(
				"type='$ImageSetting->type'",
				"context='$ImageSetting->context'",
				"name IN ('".join("', '", $defaults)."')"
			),
			'limit' => count($defaults)
		);
		$query = DB::select($options);
		$existing = DB::query($query, 'array', 'col', 'name');

		// Get the settings that need setup
		$setup = array_diff($defaults, $existing);

		foreach ($setup as $setting)
			shopp_set_image_setting($setting, $settings[ $setting ]);

	}

	/**
	 * Initializes default settings or resets missing settings
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function setup () {

		$Settings = ShoppSettings();

		$Settings->setup('show_welcome', 'on');
		$Settings->setup('display_welcome', 'on');

		// General Settings
		$Settings->setup('shipping', 'on');
		$Settings->setup('order_status', array(__('Pending', 'Shopp'), __('Completed', 'Shopp')));
		$Settings->setup('shopp_setup', 'completed');
		$Settings->setup('maintenance', 'off');
		$Settings->setup('dashboard', 'on');

		// Preferences
		$Settings->setup('order_confirmation', '');
		$Settings->setup('receipt_copy', '1');
		$Settings->setup('account_system', 'none');
		$Settings->setup('shopping_cart', 'on');
		$Settings->setup('cancel_reasons', array(
			__('Not as described or expected', 'Shopp'),
			__('Wrong size', 'Shopp'),
			__('Found better prices elsewhere', 'Shopp'),
			__('Product is missing parts', 'Shopp'),
			__('Product is defective or damaged', 'Shopp'),
			__('Took too long to deliver', 'Shopp'),
			__('Item out of stock', 'Shopp'),
			__('Customer request to cancel', 'Shopp'),
			__('Item discontinued', 'Shopp'),
			__('Other reason', 'Shopp')
		));

		// Shipping
		$Settings->setup('active_shipping', '');
		$Settings->setup('shipping', '');
		$Settings->setup('inventory', '');
		$Settings->setup('shipping_packaging', 'like');
		$Settings->setup('shipping_package_weight_limit', '-1');

		// Taxes
		$Settings->setup('tax_inclusive', '');
		$Settings->setup('taxes', '');
		$Settings->setup('taxrates', '');
		$Settings->setup('tax_shipping', '');

		// Presentation Settings
		$Settings->setup('theme_templates', 'off');
		$Settings->setup('row_products', '3');
		$Settings->setup('catalog_pagination', '25');
		$Settings->setup('default_product_order', 'title');
		$Settings->setup('product_image_order', 'ASC');
		$Settings->setup('product_image_orderby', 'sortorder');

		// System Settings
		$Settings->setup('uploader_pref', 'flash');
		$Settings->setup('script_loading', 'global');
		$Settings->setup('script_server', 'plugin');

		// Pre-inits
		$Settings->setup('active_catalog_promos', '');

		$Settings->setup('version', Shopp::VERSION);

		$this->images(); // Setup default image settings

	}

	/**
	 * Post activation maintenance
	 *
	 * @author Jonathan Davis
	 * @since 1.2.6
	 *
	 * @return void
	 **/
	function maintenance () {
		global $wpdb;

		$db_version = ShoppSettings::dbversion();

		if ( $db_version <= 1149 ) {
			// Set mass packaging setting to 'all' for current realtime shipping rates {@see bug #1835}
			if ('mass' == shopp_setting('shipping_packaging'))
				shopp_set_setting('shipping_packaging','all');

			// Fix all product modified timestamps (for 1.2.6)
			$post_type = 'shopp_product';
			$post_modified = DB::mkdatetime( current_time('timestamp') );
			$post_modified_gmt = DB::mkdatetime( current_time('timestamp') + (get_option( 'gmt_offset' ) * 3600) );
			DB::query("UPDATE $wpdb->posts SET post_modified='$post_modified', post_modified_gmt='$post_modified_gmt' WHERE post_type='$post_type' AND post_modified='0000-00-00 00:00:00'");
		}
	}

	/**
	 * Shopp 1.1.0 upgrades
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function upgrade_110 () {
		$db = DB::get();
		$meta_table = DatabaseObject::tablename('meta');
		$setting_table = DatabaseObject::tablename('setting');

		// Update product status from the 'published' column
		$product_table = DatabaseObject::tablename('product');
		$db->query("UPDATE $product_table SET status=CAST(published AS unsigned)");

		// Set product publish date based on the 'created' date column
		$db->query("UPDATE $product_table SET publish=created WHERE status='publish'");

		// Update Catalog
		$catalog_table = DatabaseObject::tablename('catalog');
		$db->query("UPDATE $catalog_table set parent=IF(category!=0, category, tag), type=IF(category!=0, 'category', 'tag')");

		// Update specs
		$meta_table = DatabaseObject::tablename('meta');
		$spec_table = DatabaseObject::tablename('spec');
		$now = current_time('mysql');
		$db->query("INSERT INTO $meta_table (parent, context, type, name, value, numeral, sortorder, created, modified)
					SELECT product, 'product', 'spec', name, content, numeral, sortorder, '$now', '$now' FROM $spec_table");

		// Update purchase table
		$purchase_table = DatabaseObject::tablename('purchase');
		$db->query("UPDATE $purchase_table SET txnid=transactionid, txnstatus=transtatus");

		// Update image assets
		$meta_table = DatabaseObject::tablename('meta');
		$asset_table = DatabaseObject::tablename('asset');
		$db->query("INSERT INTO $meta_table (parent, context, type, name, value, numeral, sortorder, created, modified)
							SELECT parent, context, 'image', 'processing', CONCAT_WS('::', id, name, value, size, properties, LENGTH(data)), '0', sortorder, created, modified FROM $asset_table WHERE datatype='image'");
		$records = $db->query("SELECT id, value FROM $meta_table WHERE type='image' AND name='processing'", 'array');
		foreach ($records as $r) {
			list($src, $name, $value, $size, $properties, $datasize) = explode("::", $r->value);
			$p = unserialize($properties);
			$value = new StdClass();
			if (isset($p['width'])) $value->width = $p['width'];
			if (isset($p['height'])) $value->height = $p['height'];
			if (isset($p['alt'])) $value->alt = $p['alt'];
			if (isset($p['title'])) $value->title = $p['title'];
			$value->filename = $name;
			if (isset($p['mimetype'])) $value->mime = $p['mimetype'];
			$value->size = $size;

			if ($datasize > 0) {
				$value->storage = "DBStorage";
				$value->uri = $src;
			} else {
				$value->storage = "FSStorage";
				$value->uri = $name;
			}
			$value = mysql_real_escape_string(serialize($value));
			$db->query("UPDATE $meta_table set name='original', value='$value' WHERE id=$r->id");
		}

		// Update product downloads
		$meta_table = DatabaseObject::tablename('meta');
		$asset_table = DatabaseObject::tablename('asset');
		$query = "INSERT INTO $meta_table (parent, context, type, name, value, numeral, sortorder, created, modified)
					SELECT parent, context, 'download', 'processing', CONCAT_WS('::', id, name, value, size, properties, LENGTH(data)), '0', sortorder, created, modified FROM $asset_table WHERE datatype='download' AND parent != 0";
		$db->query($query);
		$records = $db->query("SELECT id, value FROM $meta_table WHERE type='download' AND name='processing'", 'array');
		foreach ($records as $r) {
			list($src, $name, $value, $size, $properties, $datasize) = explode("::", $r->value);
			$p = unserialize($properties);
			$value = new StdClass();
			$value->filename = $name;
			$value->mime = $p['mimetype'];
			$value->size = $size;
			if ($datasize > 0) {
				$value->storage = "DBStorage";
				$value->uri = $src;
			} else {
				$value->storage = "FSStorage";
				$value->uri = $name;
			}
			$value = mysql_real_escape_string(serialize($value));
			$db->query("UPDATE $meta_table set name='$name', value='$value' WHERE id=$r->id");
		}

		// Update promotions
		$promo_table = DatabaseObject::tablename('promo');
		$records = $db->query("UPDATE $promo_table SET target='Cart' WHERE scope='Order'", 'array');

		$FSStorage = array('path' => array());
		// Migrate Asset storage settings
		$image_storage = shopp_setting('image_storage_pref');
		if ($image_storage == "fs") {
			$image_storage = "FSStorage";
			$FSStorage['path']['image'] = shopp_setting('image_path');
		} else $image_storage = "DBStorage";
		shopp_set_setting('image_storage', $image_storage);

		$product_storage = shopp_setting('product_storage_pref');
		if ($product_storage == "fs") {
			$product_storage = "FSStorage";
			$FSStorage['path']['download'] = shopp_setting('products_path');
		} else $product_storage = "DBStorage";
		shopp_set_setting('product_storage', $product_storage);

		if (!empty($FSStorage['path'])) shopp_set_setting('FSStorage', $FSStorage);

		// Preserve payment settings

		// Determine active gateways
		$active_gateways = array(shopp_setting('payment_gateway'));
		$xco_gateways = (array)shopp_setting('xco_gateways');
		if (!empty($xco_gateways))
			$active_gateways = array_merge($active_gateways, $xco_gateways);

		// Load 1.0 payment gateway settings for active gateways
		$gateways = array();
		foreach ($active_gateways as $reference) {
			list($dir, $filename) = explode('/', $reference);
			$gateways[] = preg_replace('/[^\w+]/', '', substr($filename, 0, strrpos($filename, '.')));
		}

		$where = "name like '%".join("%' OR name like '%", $gateways)."%'";
		$query = "SELECT name, value FROM $setting_table WHERE $where";
		$result = $db->query($query, 'array');
		$paycards = Lookup::paycards();

		// Convert settings to 1.1-compatible settings
		$active_gateways = array();
		foreach ($result as $_) {
			$active_gateways[] = $_->name;		// Add gateway to the active gateways list
			$setting = unserialize($_->value);	// Parse the settings

			// Get rid of legacy settings
			unset($setting['enabled'], $setting['path'], $setting['billing-required']);

			// Convert accepted payment cards
			$accepted = array();
			if (isset($setting['cards']) && is_array($setting['cards'])) {
				foreach ($setting['cards'] as $cardname) {
					// Normalize card names
					$cardname = str_replace(
						array(	"Discover",
								"Diner’s Club",
								"Diners"
						),
						array(	"Discover Card",
								"Diner's Club",
								"Diner's Club"
						),
						$cardname);

					foreach ($paycards as $card)
						if ($cardname == $card->name) $accepted[] = $card->symbol;
				}
				$setting['cards'] = $accepted;
			}
			shopp_set_setting($_->name, $setting); // Save the gateway settings
		}
		// Save the active gateways to populate the payment settings page
		shopp_set_setting('active_gateways', join(', ', $active_gateways));

		// Preserve update key
		$oldkey = shopp_setting('updatekey');
		if (!empty($oldkey)) {
			$newkey = array(
				($oldkey['status'] == "activated"?1:0),
				$oldkey['key'],
				$oldkey['type']
			);
			shopp_set_setting('updatekey', $newkey);
		}

		$this->roles(); // Setup Roles and Capabilities

	}

	public function upgrade_120 () {
		global $wpdb;

		$db_version = ShoppSettings::dbversion();

		// Clear the shopping session table
		$shopping_table = DatabaseObject::tablename('shopping');
		DB::query("DELETE FROM $shopping_table");

		if ($db_version <= 1140) {
			$summary_table = DatabaseObject::tablename('summary');
			// Force summaries to rebuild
			DB::query("UPDATE $summary_table SET modified='0000-00-00 00:00:01'");
		}

		$purchase_table = DatabaseObject::tablename('purchase');
		DB::query("UPDATE $purchase_table SET txnstatus='captured' WHERE txnstatus='CHARGED'");

		if ($db_version <= 1130) {

			// Move settings to meta table
			$meta_table = DatabaseObject::tablename('meta');
			$setting_table = DatabaseObject::tablename('setting');

			DB::query("INSERT INTO $meta_table (context, type, name, value, created, modified) SELECT 'shopp', 'setting', name, value, created, modified FROM $setting_table");

			// Clean up unnecessary duplicate settings
			shopp_rmv_setting('data_model');
			shopp_rmv_setting('updates');
			shopp_rmv_setting('shopp_setup');
			shopp_rmv_setting('maintenance');

			// Re-load the Shopp settings registry
			ShoppSettings()->load();

			shopp_set_setting('maintenance', 'on');
			$db_version = intval(shopp_setting('db_version'));

			// Force inventory in 1.2 on to mimic 1.1 behavior (inventory tracking always on)
			shopp_set_setting('inventory', 'on');

			// Convert Shopp 1.1.x shipping settings to Shopp 1.2-compatible settings
			$active_shipping = array();
			$regions = Lookup::regions();
			$countries = Lookup::countries();
			$areas = Lookup::country_areas();

			$calcnaming = array(
				'FlatRates::order' => 'OrderRates',
				'FlatRates::item' => 'ItemRates',
				'FreeOption' => 'FreeOption',
				'ItemQuantity::range' => 'ItemQuantity',
				'OrderAmount::range' => 'OrderAmount',
				'OrderWeight::range' => 'OrderWeight'
			);
			$shipping_rates = shopp_setting('shipping_rates');
			foreach ((array)$shipping_rates as $id => $old) {
				if (isset($calcnaming[ $old['method'] ])) {
					// Add to active setting registry for that calculator class
					$calcname = $calcnaming[ $old['method'] ];
					if (!isset($$calcname) && !is_array($$calcname)) $$calcname = array();
					${$calcname}[] = true;
					$active_shipping[$calcname] = $$calcname;

					// Define the setting name
					$settingid = end(array_keys( $$calcname ));
					$setting_name = $calcname.'-'.$settingid;
				} else {
					// Not a calculator, must be a shipping rate provider module, add it to the active roster
					$active_shipping[ $old['name'] ] = true;
					continue;
				}

				$new = array();

				$new['label'] = $old['name'];
				list($new['mindelivery'], $new['maxdelivery']) = explode('-', $old['delivery']);
				$new['fallback'] = 'off'; // Not used in legacy settings

				$oldkeys = array_keys($old);

				$old_destinations = array_diff($oldkeys, array('name', 'delivery', 'method', 'max'));
				$table = array();
				foreach ($old_destinations as $old_dest) {
					$_ = array();

					if ('Worldwide' == $old_dest) $d = '*';

					$region = array_search($old_dest, $regions);
					if (false !== $region) $d = "$region";

					if (isset($countries[ $old_dest ])) {
						$country = $countries[ $old_dest ];
						$region =  $country['region'];
						$d = "$region, $old_dest";
					}
					foreach ($areas as $countrykey => $countryarea) {
						$areakeys = array_keys($countryarea);
						$area = array_search($old_dest, $areakeys);
						if (false !== $area) {
							$country = $countrykey;
							$region = $countries[ $countrykey ]['region'];
							$area = $areakeys[ $area ];
							$d = "$region, $country, $area";
							break;
						}
					}

					$_['destination'] = $d;
					$_['postcode'] = '*'; // Postcodes are new in 1.2, hardcode to wildcard
					if (isset($old['max']) && !empty($old['max'])) { // Capture tiered rates
						$_['tiers'] = array();
						$prior = 1;
						foreach ($old['max'] as $index => $oldthreshold) {
							$tier = array('threshold' => 0, 'rate' => 0);
							if ( in_array($oldthreshold, array('+', '>')))
								$tier['threshold'] = $prior+1;
							elseif ( 1 == $oldthreshold )
								$tier['threshold'] = 1;
							else $tier['threshold'] = $prior+1;
							$prior = $oldthreshold;
							$tier['rate'] = $old[$old_dest][$index];
							$_['tiers'][] = $tier;
						}
					} else $_['rate'] = $old[$old_dest][0]; // Capture flat rates

					$table[] = $_;
				}
				$new['table'] = $table;
				shopp_set_setting($setting_name, $new); // Save the converted settings

			} // End foreach($shipping_rates) to convert old shipping calculator setting format

			shopp_set_setting('active_shipping', $active_shipping); // Save the active shipping options

		}

		if ($db_version <= 1121) {
			$address_table = DatabaseObject::tablename('address');
			$billing_table = DatabaseObject::tablename('billing');
			$shipping_table = DatabaseObject::tablename('shipping');

			// Move billing address data to the address table
			DB::query("INSERT INTO $address_table (customer, type, address, xaddress, city, state, country, postcode, created, modified)
						SELECT customer, 'billing', address, xaddress, city, state, country, postcode, created, modified FROM $billing_table");

			DB::query("INSERT INTO $address_table (customer, type, address, xaddress, city, state, country, postcode, created, modified)
						SELECT customer, 'shipping', address, xaddress, city, state, country, postcode, created, modified FROM $shipping_table");
		}

		// Migrate to WP custom posts & taxonomies
		if ($db_version <= 1131) {

			// Copy products to posts
				$catalog_table = DatabaseObject::tablename('catalog');
				$product_table = DatabaseObject::tablename('product');
				$price_table = DatabaseObject::tablename('price');
				$summary_table = DatabaseObject::tablename('summary');
				$meta_table = DatabaseObject::tablename('meta');
				$category_table = DatabaseObject::tablename('category');
				$tag_table = DatabaseObject::tablename('tag');
				$purchased_table = DatabaseObject::tablename('purchased');
				$index_table = DatabaseObject::tablename('index');

				$post_type = 'shopp_product';

				// Create custom post types from products, temporarily use post_parent for link to original product entry
				DB::query("INSERT INTO $wpdb->posts (post_type, post_name, post_title, post_excerpt, post_content, post_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_parent)
							SELECT '$post_type', slug, name, summary, description, status, created, created, modified, modified, id FROM $product_table");

				// Update purchased table product column with new Post ID so sold counts can be updated
				DB::query("UPDATE $purchased_table AS pd JOIN $wpdb->posts AS wp ON wp.post_parent=pd.product AND wp.post_type='$post_type' SET pd.product=wp.ID");

				// Update product links for prices and meta
				DB::query("UPDATE $price_table AS price JOIN $wpdb->posts AS wp ON price.product=wp.post_parent SET price.product=wp.ID WHERE wp.post_type='$post_type'");
				DB::query("UPDATE $meta_table AS meta JOIN $wpdb->posts AS wp ON meta.parent=wp.post_parent AND wp.post_type='$post_type' AND meta.context='product' SET meta.parent=wp.ID");
				DB::query("UPDATE $index_table AS i JOIN $wpdb->posts AS wp ON i.product=wp.post_parent AND wp.post_type='$post_type' SET i.product=wp.ID");

				// Preliminary summary data
				DB::query("INSERT INTO $summary_table (product, featured, variants, addons, modified)
						   SELECT wp.ID, p.featured, p.variations, p.addons, '0000-00-00 00:00:01'
						   FROM $product_table AS p
						   JOIN $wpdb->posts as wp ON p.id=wp.post_parent AND wp.post_type='$post_type'");

				// Move product options column to meta setting
				DB::query("INSERT INTO $meta_table (parent, context, type, name, value)
						SELECT wp.ID, 'product', 'meta', 'options', options
						FROM $product_table AS p
						JOIN $wpdb->posts AS wp ON p.id=wp.post_parent AND wp.post_type='$post_type'");

			// Migrate Shopp categories and tags to WP taxonomies

				// Are there tag entries in the meta table? Old dev data present use meta table tags. No? use tags table.
				$dev_migration = ($db_version >= 1120);

				// Copy categories and tags to WP taxonomies
				$tag_current_table = $dev_migration?"$meta_table WHERE context='catalog' AND type='tag'":$tag_table;

				$terms = DB::query("(SELECT id, 'shopp_category' AS taxonomy, name, parent, description, slug FROM $category_table)
											UNION
										(SELECT id, 'shopp_tag' AS taxonomy, name, 0 AS parent, '' AS description, name AS slug FROM $tag_current_table) ORDER BY id", 'array');

				// Prep category images for the move
				$category_image_offset = 65535;
				DB::query("UPDATE $meta_table set parent=parent+$category_image_offset WHERE context='category' AND type='image'");

				$mapping = array();
				$children = array();
				$tt_ids = array();
				foreach ($terms as $term) {
					$term_id = (int) $term->id;
					$taxonomy = $term->taxonomy;
					if (!isset($mapping[$taxonomy])) $mapping[$taxonomy] = array();
					if (!isset($children[$taxonomy])) $children[$taxonomy] = array();
					$name = $term->name;
					$parent = $term->parent;
					$description = $term->description;
					$slug = (strpos($term->slug, ' ') === false)?$term->slug:sanitize_title_with_dashes($term->slug);
					$term_group = 0;

					if ($exists = DB::query("SELECT term_id, term_group FROM $wpdb->terms WHERE slug = '$slug'", 'array')) {
						$term_group = $exists[0]->term_group;
						$id = $exists[0]->term_id;
						$num = 2;
						do {
							$alternate = DB::escape($slug."-".$num++);
							$alternate_used = DB::query("SELECT slug FROM $wpdb->terms WHERE slug='$alternate'");
						} while ($alternate_used);
						$slug = $alternate;

						if ( empty($term_group) ) {
							$term_group = DB::query("SELECT MAX(term_group) AS term_group FROM $wpdb->terms GROUP BY term_group", 'auto', 'col', 'term_group');
							DB::query("UPDATE $wpdb->terms SET term_group='$term_group' WHERE term_id='$id'");
						}
					}

					// Move the term into the terms table
					$wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->terms (name, slug, term_group) VALUES (%s, %s, %d)", $name, $slug, $term_group) );
					$mapping[$taxonomy][$term_id] = (int) $wpdb->insert_id; // Map the old id to the new id
					$term_id = $mapping[$taxonomy][$term_id]; // Update the working id to the new id
					if (!isset($tt_ids[$taxonomy])) $tt_ids[$taxonomy] = array();

					if ( 'shopp_category' == $taxonomy ) {

						// If the parent term has already been added to the terms table, set the new parent id
						if (isset($mapping[$taxonomy][$parent])) $parent = $mapping[$taxonomy][$parent];
						else { // Parent hasn't been created, keep track of children for the parent to do a mass update when the parent term record is created
							if (!isset($children[$taxonomy][$parent])) $children[$taxonomy][$parent] = array();
							$children[$taxonomy][$parent][] = $term_id;
						}

						if (!empty($children[$taxonomy][$term->id])) // If there are children already created for this term, update their parent to our new id
							$wpdb->query( "UPDATE $wpdb->term_taxonomy SET parent=$term_id WHERE term_id IN (".join(', ', $children[$taxonomy][$term->id]).")" );

						// Associate the term to the proper taxonomy and parent terms
						$wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ( %d, %s, %s, %d, %d)", $term_id, $taxonomy, $description, $parent, 0) );
						$tt_ids[$taxonomy][$term_id] = (int) $wpdb->insert_id;

						if (!empty($term_id)) {
							// Move category settings to meta
							$metafields = array('spectemplate', 'facetedmenus', 'variations', 'pricerange', 'priceranges', 'specs', 'options', 'prices');
							foreach ($metafields as $field)
								DB::query("INSERT INTO $meta_table (parent, context, type, name, value)
											SELECT $term_id, 'category', 'meta', '$field', $field
											FROM $category_table
											WHERE id=$term->id");


							// Update category images to new term ids
							DB::query("UPDATE $meta_table set parent='$term_id' WHERE parent='".((int)$term->id+$category_image_offset)."' AND context='category' AND type='image'");
						}
					}

					if ( 'shopp_tag' == $taxonomy ) {
						$wpdb->query( $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ( %d, %s, %s, %d, %d)", $term_id, $taxonomy, $description, $parent, 0) );
						$tt_ids[$taxonomy][$term_id] = (int) $wpdb->insert_id;
					}

				}				update_option('shopp_category_children', '');

			// Re-catalog custom post type_products term relationships (new taxonomical catalog) from old Shopp catalog table

				$wp_taxonomies = array(
					0 => 'shopp_category',
					1 => 'shopp_tag',
					'category' => 'shopp_category',
					'tag' => 'shopp_tag'
				);

				$cols = 'wp.ID AS product, c.parent, c.type';
				$where = "type='category' OR type='tag'";
				if ($db_version >= 1125) {
					$cols = 'wp.ID AS product, c.parent, c.taxonomy, c.type';
					$where = "taxonomy=0 OR taxonomy=1";
				}

				$rels = DB::query("SELECT $cols FROM $catalog_table AS c LEFT JOIN $wpdb->posts AS wp ON c.product=wp.post_parent AND wp.post_type='$post_type' WHERE $where", 'array');

				foreach ((array)$rels as $r) {
					$object_id = $r->product;
					$taxonomy = $wp_taxonomies[($db_version >= 1125?$r->taxonomy:$r->type)];
					$term_id = $mapping[$taxonomy][$r->parent];
					if ( !isset($tt_ids[$taxonomy]) ) continue;
					if ( !isset($tt_ids[$taxonomy][$term_id]) ) continue;

					$tt_id = $tt_ids[$taxonomy][$term_id];
					if ( empty($tt_id) ) continue;

					DB::query("INSERT $wpdb->term_relationships (object_id, term_taxonomy_id) VALUES ($object_id, $tt_id)");
				}

				if (isset($tt_ids['shopp_category']))
					wp_update_term_count_now($tt_ids['shopp_category'], 'shopp_category');

				if (isset($tt_ids['shopp_tag']))
					wp_update_term_count_now($tt_ids['shopp_tag'], 'shopp_tag');

				// Clear custom post type parents
				DB::query("UPDATE $wpdb->posts SET post_parent=0 WHERE post_type='$post_type'");

		} // END if ($db_version <= 1131)

		if ($db_version <= 1133) {

			// Ditch old WP pages for pseudorific new ones
			$search = array();
			$shortcodes = array('[catalog]', '[cart]', '[checkout]', '[account]');
			foreach ($shortcodes as $string) $search[] = "post_content LIKE '%$string%'";
			$results = DB::query("SELECT ID, post_title AS title, post_name AS slug, post_content FROM $wpdb->posts WHERE post_type='page' AND (".join(" OR ", $search).")", 'array');

			$pages = $trash = array();
			foreach ($results as $post) {
				$trash[] = $post->ID;
				foreach ($shortcodes as $code) {
					if (strpos($post->post_content, $code) === false) continue;
					$pagename = trim($code, '[]');
					$pages[$pagename] = array('title' => $post->title, 'slug' => $post->slug);
				} // end foreach $shortcodes
			} // end foreach $results

			shopp_set_setting('storefront_pages', $pages);

			DB::query("UPDATE $wpdb->posts SET post_name=CONCAT(post_name, '-deprecated'), post_status='trash' where ID IN (".join(', ', $trash).")");
		}

		// Move needed price table columns to price meta records
		if ($db_version <= 1135) {
			$meta_table = DatabaseObject::tablename('meta');
			$price_table = DatabaseObject::tablename('price');

			// Move 'options' to meta 'options' record
			DB::query("INSERT INTO $meta_table (parent, context, type, name, value, created, modified)
						SELECT id, 'price', 'meta', 'options', options, created, modified FROM $price_table");

			// Merge 'weight', 'dimensions' and 'donation' columns to a price 'settings' record
			DB::query("INSERT INTO $meta_table (parent, context, type, name, value, created, modified)
							SELECT id, 'price', 'meta', 'settings',
							CONCAT('a:2:{s:10:\"dimensions\";',
								IF(weight = 0 AND dimensions = '0', 'a:0:{}',
									IF(dimensions = '0',
										CONCAT(
											'a:1:{s:6:\"weight\";s:', CHAR_LENGTH(weight), ':\"', weight, '\";}'
										), dimensions
									)
								), 's:8:\"donation\";', IF(donation='', 'N;', donation), '}'
							), created, modified FROM $price_table");

		} // END if ($db_version <= 1135)

		if ($db_version <= 1145) {
			// Update purchase gateway property to use gateway class names
			// for proper order event handling on 1.1-generated orders
			$gateways = array(
				'PayPal Standard' => 'PayPalStandard',
				'PayPal Expresss' => 'PayPalExpress',
				'PayPal Pro' => 'PayPalPro',
				'2Checkout.com' => '_2Checkout',
				'Authorize.Net' => 'AuthorizeNet',
				'Google Checkout' => 'GoogleCheckout',
				'HSBC ePayments' => 'HSBCepayments',
				'iDeal Mollie' => 'iDealMollie',
				'Manual Processing' => 'ManualProcessing',
				'Merchant Warrior' => 'MerchantWarrior',
				'Offline Payment' => 'OfflinePayment',
				'PayPal Payflow Pro' => 'PayflowPro',
				'Test Mode' => 'TestMode'
			);
			foreach ($gateways as $name => $classname)
				DB::query("UPDATE $purchase_table SET gateway='$classname' WHERE gateway='$name'");
		} // END if ($db_version <= 1145)

		if ($db_version <= 1148) {
			$price_table = DatabaseObject::tablename('price');
			DB::query("UPDATE $price_table SET optionkey=(options*7001) WHERE context='addon'");
		}

	}

	/**
	 * Perform automatic updates for the core plugin and addons
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function update () {
		global $parent_file, $submenu_file;

		$plugin = isset($_REQUEST['plugin']) ? trim($_REQUEST['plugin']) : '';
		$addon = isset($_REQUEST['addon']) ? trim($_REQUEST['addon']) : '';
		$type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : '';

		if ( ! current_user_can('update_plugins') )
			wp_die(__('You do not have sufficient permissions to update plugins for this blog.'));

		if (SHOPP_PLUGINFILE == $plugin) {
			// check_admin_referer('upgrade-plugin_' . $plugin);
			$title = __('Upgrade Shopp', 'Shopp');
			$parent_file = 'plugins.php';
			$submenu_file = 'plugins.php';
			require(ABSPATH.'wp-admin/admin-header.php');

			$nonce = 'upgrade-plugin_' . $plugin;
			$url = 'update.php?action=shopp&plugin=' . $plugin;

			$upgrader = new ShoppCore_Upgrader( new Shopp_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
			$upgrader->upgrade($plugin);

			include(ABSPATH.'/wp-admin/admin-footer.php');
		} elseif ('gateway' == $type ) {
			// check_admin_referer('upgrade-shopp-addon_' . $plugin);
			$title = sprintf(__('Upgrade Shopp Add-on', 'Shopp'), 'Shopp');
			$parent_file = 'plugins.php';
			$submenu_file = 'plugins.php';
			require(ABSPATH.'wp-admin/admin-header.php');

			$nonce = 'upgrade-shopp-addon_' . $plugin;
			$url = 'update.php?action=shopp&addon='.$addon.'&type='.$type;

			$upgrader = new ShoppAddon_Upgrader( new Shopp_Upgrader_Skin( compact('title', 'nonce', 'url', 'addon') ) );
			$upgrader->upgrade($addon, 'gateway');

			include(ABSPATH.'/wp-admin/admin-footer.php');

		} elseif ('shipping' == $type ) {
			// check_admin_referer('upgrade-shopp-addon_' . $plugin);
			$title = sprintf(__('Upgrade Shopp Add-on', 'Shopp'), 'Shopp');
			$parent_file = 'plugins.php';
			$submenu_file = 'plugins.php';
			require(ABSPATH.'wp-admin/admin-header.php');

			$nonce = 'upgrade-shopp-addon_' . $plugin;
			$url = 'update.php?action=shopp&addon='.$addon.'&type='.$type;

			$upgrader = new ShoppAddon_Upgrader( new Shopp_Upgrader_Skin( compact('title', 'nonce', 'url', 'addon') ) );
			$upgrader->upgrade($addon, 'shipping');

			include(ABSPATH.'/wp-admin/admin-footer.php');
		} elseif ('storage' == $type ) {
			// check_admin_referer('upgrade-shopp-addon_' . $plugin);
			$title = sprintf(__('Upgrade Shopp Add-on', 'Shopp'), 'Shopp');
			$parent_file = 'plugins.php';
			$submenu_file = 'plugins.php';
			require(ABSPATH.'wp-admin/admin-header.php');

			$nonce = 'upgrade-shopp-addon_' . $plugin;
			$url = 'update.php?action=shopp&addon='.$addon.'&type='.$type;

			$upgrader = new ShoppAddon_Upgrader( new Shopp_Upgrader_Skin( compact('title', 'nonce', 'url', 'addon') ) );
			$upgrader->upgrade($addon, 'storage');

			include(ABSPATH.'/wp-admin/admin-footer.php');
		}
	}

} // END class ShoppInstallation

if (!class_exists('Plugin_Upgrader'))
	require(ABSPATH."wp-admin/includes/class-wp-upgrader.php");

/**
 * Shopp_Upgrader class
 *
 * Provides foundational functionality specific to Shopp update
 * processing classes.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage installation
 **/
class Shopp_Upgrader extends Plugin_Upgrader {

	public function download_package($package) {

		if ( ! preg_match('!^(http|https|ftp)://!i', $package) && file_exists($package) ) //Local file or remote?
			return $package; //must be a local file..

		if ( empty($package) )
			return new WP_Error('no_package', $this->strings['no_package']);

		$this->skin->feedback('downloading_package', $package);

		$key = ShoppSupport::key();
		$vars = array('VERSION', 'KEY', 'URL');
		$values = array(urlencode(Shopp::VERSION), urlencode($key['k']), urlencode(get_bloginfo('siteurl')));
		$package = str_replace($vars, $values, $package);

		$download_file = $this->download_url($package);

		if ( is_wp_error($download_file) )
			return new WP_Error('download_failed', $this->strings['download_failed'], $download_file->get_error_message());

		return $download_file;
	}

	public function download_url ( $url ) {
		//WARNING: The file is not automatically deleted, The script must unlink() the file.
		if ( ! $url )
			return new WP_Error('http_no_url', __('Invalid URL Provided'));

		$request = parse_url($url);
		parse_str($request['query'], $query);
		$tmpfname = wp_tempnam($query['update'].".zip");
		if ( ! $tmpfname )
			return new WP_Error('http_no_file', __('Could not create Temporary file'));

		$handle = @fopen($tmpfname, 'wb');
		if ( ! $handle )
			return new WP_Error('http_no_file', __('Could not create Temporary file'));

		$response = wp_remote_get($url, array('timeout' => 300));

		if ( is_wp_error($response) ) {
			fclose($handle);
			unlink($tmpfname);
			return $response;
		}

		if ( $response['response']['code'] != '200' ){
			fclose($handle);
			unlink($tmpfname);
			return new WP_Error('http_404', trim($response['response']['message']));
		}

		fwrite($handle, $response['body']);
		fclose($handle);

		return $tmpfname;
	}

	public function unpack_package($package, $delete_package = true, $clear_working = true) {
		global $wp_filesystem;

		$this->skin->feedback('unpack_package');

		$upgrade_folder = $wp_filesystem->wp_content_dir() . 'upgrade/';

		//Clean up contents of upgrade directory beforehand.
		if ($clear_working) {
			$upgrade_files = $wp_filesystem->dirlist($upgrade_folder);
			if ( !empty($upgrade_files) ) {
				foreach ( $upgrade_files as $file )
					$wp_filesystem->delete($upgrade_folder . $file['name'], true);
			}
		}

		//We need a working directory
		$working_dir = $upgrade_folder . basename($package, '.zip');

		// Clean up working directory
		if ( $wp_filesystem->is_dir($working_dir) )
			$wp_filesystem->delete($working_dir, true);

		// Unzip package to working directory
		$result = unzip_file($package, $working_dir); //TODO optimizations, Copy when Move/Rename would suffice?

		// Once extracted, delete the package if required.
		if ( $delete_package )
			unlink($package);

		if ( is_wp_error($result) ) {
			$wp_filesystem->delete($working_dir, true);
			return $result;
		}
		$this->working_dir = $working_dir;

		return $working_dir;
	}

}

/**
 * ShoppCore_Upgrader class
 *
 * Adds auto-update support for the core plugin.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage installation
 **/
class ShoppCore_Upgrader extends Shopp_Upgrader {

	public function upgrade_strings() {
		$this->strings['up_to_date'] = __('Shopp is at the latest version.', 'Shopp');
		$this->strings['no_package'] = __('Shopp upgrade package not available.', 'Shopp');
		$this->strings['downloading_package'] = sprintf(__('Downloading update from <span class="code">%s</span>.'), SHOPP_HOME);
		$this->strings['unpack_package'] = __('Unpacking the update.', 'Shopp');
		$this->strings['deactivate_plugin'] = __('Deactivating Shopp.', 'Shopp');
		$this->strings['remove_old'] = __('Removing the old version of Shopp.', 'Shopp');
		$this->strings['remove_old_failed'] = __('Could not remove the old Shopp.', 'Shopp');
		$this->strings['process_failed'] = __('Shopp upgrade Failed.', 'Shopp');
		$this->strings['process_success'] = __('Shopp upgraded successfully.', 'Shopp');
	}

	public function upgrade($plugin) {
		$this->init();
		$this->upgrade_strings();

		$current = shopp_setting('updates');
		if ( !isset( $current->response[ $plugin ] ) ) {
			$this->skin->set_result(false);
			$this->skin->error('up_to_date');
			$this->skin->after();
			return false;
		}

		// Get the URL to the zip file
		$r = $current->response[ $plugin ];

		add_filter('upgrader_pre_install', array(&$this, 'addons'), 10, 2);
		// add_filter('upgrader_pre_install', array(&$this, 'deactivate_plugin_before_upgrade'), 10, 2);
		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'), 10, 4);

		// Turn on Shopp's maintenance mode
		shopp_set_setting('maintenance', 'on');

		$this->run(array(
					'package' => $r->package,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => true,
					'clear_working' => true,
					'hook_extra' => array(
					'plugin' => $plugin
					)
				));

		// Cleanup our hooks, incase something else does a upgrade on this connection.
		remove_filter('upgrader_pre_install', array(&$this, 'addons'));
		// remove_filter('upgrader_pre_install', array(&$this, 'deactivate_plugin_before_upgrade'));
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Turn off Shopp's maintenance mode
		shopp_set_setting('maintenance', 'off');

		// Force refresh of plugin update information
		shopp_set_setting('updates', false);
	}

	public function addons ($return, $plugin) {
		$current = shopp_setting('updates');

		if ( !isset( $current->response[ $plugin['plugin'].'/addons' ] ) ) return $return;
		$addons = $current->response[ $plugin['plugin'].'/addons' ];

		if (count($addons) > 0) {
			$upgrader = new ShoppAddon_Upgrader( $this->skin );
			$upgrader->addon_core_updates($addons, $this->working_dir);
		}
		$this->init(); // Get the current skin controller back for the core upgrader
		$this->upgrade_strings(); // Reinstall our upgrade strings for core
		$this->skin->feedback('<h4>'.__('Finishing Shopp upgrade...', 'Shopp').'</h4>');
	}

}

/**
 * ShoppAddon_Upgrader class
 *
 * Adds auto-update support for individual Shopp add-ons.
 *
 * Extensions derived from the WordPress WP_Upgrader & Plugin_Upgrader classes:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage installation
 **/
class ShoppAddon_Upgrader extends Shopp_Upgrader {

	public $addon = false;
	public $addons_dir = false;
	public $destination = false;

	public function upgrade_strings () {
		$this->strings['up_to_date'] = __('The add-on is at the latest version.', 'Shopp');
		$this->strings['no_package'] = __('Upgrade package not available.');
		$this->strings['downloading_package'] = sprintf(__('Downloading update from <span class="code">%s</span>.'), SHOPP_HOME);
		$this->strings['unpack_package'] = __('Unpacking the update.');
		$this->strings['deactivate_plugin'] = __('Deactivating the add-on.', 'Shopp');
		$this->strings['remove_old'] = __('Removing the old version of the add-on.', 'Shopp');
		$this->strings['remove_old_failed'] = __('Could not remove the old add-on.', 'Shopp');
		$this->strings['process_failed'] = __('Add-on upgrade Failed.', 'Shopp');
		$this->strings['process_success'] = __('Add-on upgraded successfully.', 'Shopp');
		$this->strings['include_success'] = __('Add-on included successfully.', 'Shopp');
	}

	public function install ($package) {

		$this->init();
		$this->install_strings();

		$this->run(array(
					'package' => $package,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => false, //Do not overwrite files.
					'clear_working' => true,
					'hook_extra' => array()
					));

		// Force refresh of plugin update information
		shopp_set_setting('updates', false);

	}

	public function addon_core_updates ($addons, $working_core) {

		$this->init();
		$this->upgrade_strings();

		$current = shopp_setting('updates');

		add_filter('upgrader_destination_selection', array(&$this, 'destination_selector'), 10, 2);

		$all = count($addons);
		$i = 1;
		foreach ($addons as $addon) {

			// Get the URL to the zip file
			$this->addon = $addon->slug;

			$this->show_before = sprintf( '<h4>' . __('Updating addon %1$d of %2$d...') . '</h4>', $i++, $all );

			switch ($addon->type) {
				case "gateway": $addondir = '/shopp/gateways'; break;
				case "shipping": $addondir = '/shopp/shipping'; break;
				case "storage": $addondir = '/shopp/storage'; break;
				default: $addondir = '/';
			}

			$this->run(array(
						'package' => $addon->package,
						'destination' => $working_core.$addondir,
						'clear_working' => false,
						'with_core' => true,
						'hook_extra' => array(
							'addon' => $addon
						)
			));
		}

		// Cleanup our hooks, in case something else does an upgrade on this connection.
		remove_filter('upgrader_destination_selection', array(&$this, 'destination_selector'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

	}

	public function upgrade ($addon, $type) {
		$this->init();
		$this->upgrade_strings();

		switch ($type) {
			case "gateway": $this->addons_dir = SHOPP_GATEWAYS; break;
			case "shipping": $this->addons_dir = SHOPP_SHIPPING; break;
			case "storage": $this->addons_dir = SHOPP_STORAGE; break;
			default: $this->addons_dir = SHOPP_PLUGINDIR;
		}

		$current = shopp_setting('updates');
		if ( !isset( $current->response[ SHOPP_PLUGINFILE.'/addons' ][$addon] ) ) {
			$this->skin->set_result(false);
			$this->skin->error('up_to_date');
			$this->skin->after();
			return false;
		}

		// Get the URL to the zip file
		$r = $current->response[ SHOPP_PLUGINFILE.'/addons' ][$addon];
		$this->addon = $r->slug;

		add_filter('upgrader_destination_selection', array(&$this, 'destination_selector'), 10, 2);

		$this->run(array(
					'package' => $r->package,
					'destination' => $this->addons_dir,
					'clear_destination' => true,
					'clear_working' => true,
					'hook_extra' => array(
						'addon' => $addon
					)
		));

		// Cleanup our hooks, in case something else does an upgrade on this connection.
		remove_filter('upgrader_destination_selection', array(&$this, 'destination_selector'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of plugin update information
		shopp_set_setting('updates', false);
	}

	public function run ($options) {
		global $wp_filesystem;
		$defaults = array( 	'package' => '', //Please always pass this.
							'destination' => '', //And this
							'clear_destination' => false,
							'clear_working' => true,
							'is_multi' => false,
							'with_core' => false,
							'hook_extra' => array() //Pass any extra $hook_extra args here, this will be passed to any hooked filters.
						);

		$options = wp_parse_args($options, $defaults);
		extract($options);

		//Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR, $destination) );
		if ( ! $res ) //Mainly for non-connected filesystem.
			return false;

		if ( is_wp_error($res) ) {
			$this->skin->error($res);
			return $res;
		}

		if ( !$with_core ) // call $this->header separately if running multiple times
			$this->skin->header();

		$this->skin->before();

		//Download the package (Note, This just returns the filename of the file if the package is a local file)
		$download = $this->download_package( $package );
		if ( is_wp_error($download) ) {
			$this->skin->error($download);
			return $download;
		}

		//Unzip's the file into a temporary directory
		$working_dir = $this->unpack_package( $download, true, ($with_core)?false:true );
		if ( is_wp_error($working_dir) ) {
			$this->skin->error($working_dir);
			return $working_dir;
		}

		// Determine the final destination
		$source_files = array_keys( $wp_filesystem->dirlist($working_dir) );
		if ( 1 == count($source_files)) {
			$this->destination = $source_files[0];
			if ($wp_filesystem->is_dir(trailingslashit($destination) . trailingslashit($source_files[0])))
				$destination = trailingslashit($destination) . trailingslashit($source_files[0]);
			// else $destination = trailingslashit($destination) . $source_files[0];
		}

		//With the given options, this installs it to the destination directory.
		$result = $this->install_package( array(
											'source' => $working_dir,
											'destination' => $destination,
											'clear_destination' => $clear_destination,
											'clear_working' => $clear_working,
											'hook_extra' => $hook_extra
										) );

		$this->skin->set_result($result);

		if ( is_wp_error($result) ) {
			$this->skin->error($result);
			$this->skin->feedback('process_failed');
		} else {
			// Install Suceeded
			if ($with_core) $this->skin->feedback('include_success');
			else $this->skin->feedback('process_success');
		}

		if ( !$with_core ) {
			$this->skin->after();
			$this->skin->footer();
		}

		return $result;
	}

	public function plugin_info () {
		if ( ! is_array($this->result) )
			return false;
		if ( empty($this->result['destination_name']) )
			return false;

		$plugin = get_plugins('/' . $this->result['destination_name']); //Ensure to pass with leading slash
		if ( empty($plugin) )
			return false;

		$pluginfiles = array_keys($plugin); //Assume the requested plugin is the first in the list

		return $this->result['destination_name'] . '/' . $pluginfiles[0];
	}

	public function install_package ($args = array()) {
		global $wp_filesystem;
		$defaults = array( 'source' => '', 'destination' => '', //Please always pass these
						'clear_destination' => false, 'clear_working' => false,
						'hook_extra' => array());

		$args = wp_parse_args($args, $defaults);
		extract($args);

		@set_time_limit( 300 );

		if ( empty($source) || empty($destination) )
			return new WP_Error('bad_request', $this->strings['bad_request']);

		$this->skin->feedback('installing_package');

		$res = apply_filters('upgrader_pre_install', true, $hook_extra);
		if ( is_wp_error($res) )
			return $res;

		//Retain the Original source and destinations
		$remote_source = $source;
		$local_destination = $destination;

		$source_isdir = true;
		$source_files = array_keys( $wp_filesystem->dirlist($remote_source) );
		$remote_destination = $wp_filesystem->find_folder($local_destination);

		//Locate which directory to copy to the new folder, This is based on the actual folder holding the files.
		if ( 1 == count($source_files) && $wp_filesystem->is_dir( trailingslashit($source) . $source_files[0] . '/') ) //Only one folder? Then we want its contents.
			$source = trailingslashit($source) . trailingslashit($source_files[0]);
		elseif ( count($source_files) == 0 )
				return new WP_Error('bad_package', $this->strings['bad_package']); //There are no files?
		else $source_isdir = false; //Its only a single file, The upgrader will use the foldername of this file as the destination folder. foldername is based on zip filename.

		//Hook ability to change the source file location..
		$source = apply_filters('upgrader_source_selection', $source, $remote_source, $this);
		if ( is_wp_error($source) )
			return $source;

		//Has the source location changed? If so, we need a new source_files list.
		if ( $source !== $remote_source )
			$source_files = array_keys( $wp_filesystem->dirlist($source) );

		//Protection against deleting files in any important base directories.
		if ((
			in_array( $destination, array(ABSPATH, WP_CONTENT_DIR, WP_PLUGIN_DIR, WP_CONTENT_DIR . '/themes', SHOPP_GATEWAYS, SHOPP_SHIPPING, SHOPP_STORAGE) ) ||
			in_array( basename($destination), array(basename(SHOPP_GATEWAYS), basename(SHOPP_SHIPPING), basename(SHOPP_STORAGE)) )
		) && $source_isdir) {
			$remote_destination = trailingslashit($remote_destination) . trailingslashit(basename($source));
			$destination = trailingslashit($destination) . trailingslashit(basename($source));
		}

		// Clear destination
		if ( $wp_filesystem->is_dir($remote_destination) && $source_isdir ) {
			if ( $clear_destination ) {
				//We're going to clear the destination if theres something there
				$this->skin->feedback('remove_old');
				$removed = $wp_filesystem->delete($remote_destination, true);
				$removed = apply_filters('upgrader_clear_destination', $removed, $local_destination, $remote_destination, $hook_extra);

				if ( is_wp_error($removed) )
					return $removed;
				else if ( ! $removed )
					return new WP_Error('remove_old_failed', $this->strings['remove_old_failed']);
			} else {
				//If we're not clearing the destination folder and something exists there allready, Bail.
				//But first check to see if there are actually any files in the folder.
				$_files = $wp_filesystem->dirlist($remote_destination);
				if ( ! empty($_files) ) {
					$wp_filesystem->delete($remote_source, true); //Clear out the source files.
					return new WP_Error('folder_exists', $this->strings['folder_exists'], $remote_destination );
				}
			}
		}

		// Create destination if needed
		if (!$wp_filesystem->exists($remote_destination) && $source_isdir) {
			if (!$wp_filesystem->mkdir($remote_destination, FS_CHMOD_DIR) )
				return new WP_Error('mkdir_failed', $this->strings['mkdir_failed'], $remote_destination);
		}

		// Copy new version of item into place.
		$result = copy_dir($source, $remote_destination);
		if ( is_wp_error($result) ) {
			if ( $clear_working )
				$wp_filesystem->delete($remote_source, true);
			return $result;
		}

		//Clear the Working folder?
		if ( $clear_working )
			$wp_filesystem->delete($remote_source, true);

		$destination_name = basename( str_replace($local_destination, '', $destination) );
		if ( '.' == $destination_name )
			$destination_name = '';

		$this->result = compact('local_source', 'source', 'source_name', 'source_files', 'destination', 'destination_name', 'local_destination', 'remote_destination', 'clear_destination', 'delete_source_dir');

		$res = apply_filters('upgrader_post_install', true, $hook_extra, $this->result);
		if ( is_wp_error($res) ) {
			$this->result = $res;
			return $res;
		}

		//Bombard the calling function will all the info which we've just used.
		return $this->result;
	}

	public function source_selector ($source, $remote_source) {
		global $wp_filesystem;

		$source_files = array_keys( $wp_filesystem->dirlist($source) );
		if (count($source_files) == 1) $source = trailingslashit($source).$source_files[0];

		return $source;
	}

	public function destination_selector ($destination, $remote_destination) {
		global $wp_filesystem;

		if (strpos(basename($destination), '.tmp') !== false)
			$destination = trailingslashit(dirname($destination));

		return $destination;
	}

}

/**
 * Shopp_Upgrader_Skin class
 *
 * Shopp-ifies the auto-upgrade process.
 *
 * Extensions derived from the WordPress Plugin_Upgrader_Skin class:
 * @see wp-admin/includes/class-wp-upgrader.php
 *
 * @copyright WordPress {@link http://codex.wordpress.org/Copyright_Holders}
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage installation
 **/
class Shopp_Upgrader_Skin extends Plugin_Upgrader_Skin {

	/**
	 * Custom heading for Shopp
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;
		echo '<div class="wrap shopp">';
		echo screen_icon();
		echo '<h2>' . $this->options['title'] . '</h2>';
	}

	/**
	 * Displays a return to plugins page button after installation
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function after() {
		$this->feedback('<a href="' . admin_url('plugins.php') . '" title="' . esc_attr__('Return to Plugins page') . '" target="_parent" class="button-secondary">' . __('Return to Plugins page') . '</a>');
	}

} // END class Shopp_Upgrader_Skin

?>
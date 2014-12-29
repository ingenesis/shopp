<?php
/**
 * Install.php
 *
 * Flow controller for installation and upgrades
 *
 * @version 1.0
 * @copyright Ingenesis Limited, January 2010-2014
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppInstallation extends ShoppFlowController {

	static $errors = array();
	static $nextstep = array();

	/**
	 * Install constructor
	 *
	 * @return void
	 **/
	function __construct () {

		add_action('shopp_activate',   array($this, 'activate'));
		add_action('shopp_deactivate', array($this, 'deactivate'));
		add_action('shopp_reinstall',  array($this, 'install'));

		add_action('shopp_setup', array($this, 'setup'));
		add_action('shopp_setup', array($this, 'images'));
		add_action('shopp_setup', array($this, 'roles'));
		add_action('shopp_setup', array($this, 'maintenance'));

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

		add_action('init', 'flush_rewrite_rules', 99);
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
		sDB::query("DELETE FROM $wpdb->posts WHERE post_type='" . ShoppProduct::$posttype . "'");
		sDB::query("DELETE FROM $wpdb->term_taxonomy WHERE taxonomy='" . ProductCategory::$taxon . "' OR taxonomy='" . ProductTag::$taxon . "'");

		// Install tables
		ob_start();
		include(SHOPP_DBSCHEMA);
		$schema = ob_get_clean();

		sDB::loaddata($schema);
		unset($schema);
	}

	public function upgrade () {

		// Process any DB upgrades (if needed)
		$this->upgrades();

		do_action('shopp_setup');

		if ( ShoppSettings()->available() && shopp_setting('db_version') )
			shopp_set_setting('maintenance', 'off');

		if ( shopp_setting_enabled('show_welcome') )
			shopp_set_setting('display_welcome', 'on');

		shopp_set_setting('updates', false);

		flush_rewrite_rules();

		return true;
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
		if ( $installed == ShoppVersion::db() ) return;

		shopp_set_setting('shopp_setup', '');
		shopp_set_setting('maintenance', 'on');

		if ( $installed < 1100 ) $this->upgrade_110();
		if ( $installed < 1200 ) $this->upgrade_120();
		if ( $installed < 1300 ) $this->upgrade_130();

		$db = sDB::object();
		file_put_contents(SHOPP_PATH . '/shopp_queries.txt', json_encode($db->queries));

		ShoppSettings()->save('db_version', ShoppVersion::db());

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
	public function upschema ( $filename = 'schema.sql' ) {

		$path = SHOPP_PATH . '/core/schema';
		$schema = "$path/$filename";

		// Check for the schema definition file
		if ( ! file_exists($schema) ) $this->error('nodbschema-upgrade');

		// Test to ensure Shopp can create/drop tables
		$testtable = 'shopp_db_permissions_test_'.time();
		$tests = array("CREATE TABLE $testtable ( id INT )", "DROP TABLE $testtable");
		foreach ($tests as $testquery) {
			$db = sDB::get();
			sDB::query($testquery);
			$error = mysql_error($db->dbh);
			if (!empty($error)) $this->error('dbprivileges');
		}

		// Make sure dbDelta() is available
		if ( ! function_exists('dbDelta') )
			require(ABSPATH.'wp-admin/includes/upgrade.php');


		ob_start();
		include($schema);
		$schema = ob_get_clean();

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
	 * shopp_export_orders
	 * shopp_export_customers
	 * shopp_delete_orders
	 * shopp_delete_customers
	 * shopp_void
	 * shopp_refund
	 * shopp_orders						shopp-csr
	 * shopp_customers
	 * shopp_capture
	 * shopp_menu
	 *
	 * @author John Dillick
	 * @since 1.1
	 *
	 * @return void
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
				'shopp_capture',
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
		$query = sDB::select($options);
		$existing = sDB::query($query, 'array', 'col', 'name');

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
		$Settings->setup('catalog_pagination', '24');
		$Settings->setup('default_product_order', 'title');
		$Settings->setup('product_image_order', 'ASC');
		$Settings->setup('product_image_orderby', 'sortorder');

		// System Settings
		$Settings->setup('uploader_pref', 'flash');
		$Settings->setup('script_loading', 'global');
		$Settings->setup('script_server', 'plugin');

		// Pre-inits
		$Settings->setup('active_catalog_promos', '');

		$Settings->setup('version', ShoppVersion::release());

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
			$post_modified = sDB::mkdatetime( current_time('timestamp') );
			$post_modified_gmt = sDB::mkdatetime( current_time('timestamp') + (get_option( 'gmt_offset' ) * 3600) );
			sDB::query("UPDATE $wpdb->posts SET post_modified='$post_modified', post_modified_gmt='$post_modified_gmt' WHERE post_type='$post_type' AND post_modified='0000-00-00 00:00:00'");
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

		// 1.1 schema changes
		$db_version = ShoppSettings::dbversion();
		if ( $db_version < 1100 ) return; // Skip db_version is not less than 1100
			$this->upschema('schema-110.sql');

		$meta_table = ShoppDatabaseObject::tablename('meta');
		$setting_table = ShoppDatabaseObject::tablename('setting');
		$product_table = ShoppDatabaseObject::tablename('product');


		// Update product status from the 'published' column
		sDB::query("UPDATE $product_table SET status=CAST(published AS unsigned)");

		// Set product publish date based on the 'created' date column
		sDB::query("UPDATE $product_table SET publish=created WHERE status='publish'");

		// Update Catalog
		$catalog_table = ShoppDatabaseObject::tablename('catalog');
		sDB::query("UPDATE $catalog_table set parent=IF(category!=0, category, tag), type=IF(category!=0, 'category', 'tag')");

		// Update specs
		$meta_table = ShoppDatabaseObject::tablename('meta');
		$spec_table = ShoppDatabaseObject::tablename('spec');
		$now = current_time('mysql');
		sDB::query("INSERT INTO $meta_table (parent, context, type, name, value, numeral, sortorder, created, modified)
					SELECT product, 'product', 'spec', name, content, numeral, sortorder, '$now', '$now' FROM $spec_table");

		// Update purchase table
		$purchase_table = ShoppDatabaseObject::tablename('purchase');
		sDB::query("UPDATE $purchase_table SET txnid=transactionid, txnstatus=transtatus");

		// Update image assets
		$meta_table = ShoppDatabaseObject::tablename('meta');
		$asset_table = ShoppDatabaseObject::tablename('asset');
		sDB::query("INSERT INTO $meta_table (parent, context, type, name, value, numeral, sortorder, created, modified)
							SELECT parent, context, 'image', 'processing', CONCAT_WS('::', id, name, value, size, properties, LENGTH(data)), '0', sortorder, created, modified FROM $asset_table WHERE datatype='image'");
		$records = sDB::query("SELECT id, value FROM $meta_table WHERE type='image' AND name='processing'", 'array');
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
			sDB::query("UPDATE $meta_table set name='original', value='$value' WHERE id=$r->id");
		}

		// Update product downloads
		$meta_table = ShoppDatabaseObject::tablename('meta');
		$asset_table = ShoppDatabaseObject::tablename('asset');
		$query = "INSERT INTO $meta_table (parent, context, type, name, value, numeral, sortorder, created, modified)
					SELECT parent, context, 'download', 'processing', CONCAT_WS('::', id, name, value, size, properties, LENGTH(data)), '0', sortorder, created, modified FROM $asset_table WHERE datatype='download' AND parent != 0";
		sDB::query($query);
		$records = sDB::query("SELECT id, value FROM $meta_table WHERE type='download' AND name='processing'", 'array');
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
			sDB::query("UPDATE $meta_table set name='$name', value='$value' WHERE id=$r->id");
		}

		// Update promotions
		$promo_table = ShoppDatabaseObject::tablename('promo');
		$records = sDB::query("UPDATE $promo_table SET target='Cart' WHERE scope='Order'", 'array');

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
		$result = sDB::query($query, 'array');
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

		// 1.2 schema changes
		$db_version = ShoppSettings::dbversion();
		if ( $db_version < 1120 )
			$this->upschema('schema-120.sql');

		global $wpdb;

		// Clear the shopping session table
		$shopping_table = ShoppDatabaseObject::tablename('shopping');
		sDB::query("DELETE FROM $shopping_table");

		if ($db_version <= 1140) {
			$summary_table = ShoppDatabaseObject::tablename('summary');
			// Force summaries to rebuild
			sDB::query("UPDATE $summary_table SET modified='0000-00-00 00:00:01'");
		}

		$purchase_table = ShoppDatabaseObject::tablename('purchase');
		sDB::query("UPDATE $purchase_table SET txnstatus='captured' WHERE txnstatus='CHARGED'");
		sDB::query("UPDATE $purchase_table SET txnstatus='voided' WHERE txnstatus='VOID'");

		if ($db_version <= 1130) {

			// Move settings to meta table
			$meta_table = ShoppDatabaseObject::tablename('meta');
			$setting_table = ShoppDatabaseObject::tablename('setting');

			sDB::query("INSERT INTO $meta_table (context, type, name, value, created, modified) SELECT 'shopp', 'setting', name, value, created, modified FROM $setting_table");

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
			$address_table = ShoppDatabaseObject::tablename('address');
			$billing_table = ShoppDatabaseObject::tablename('billing');
			$shipping_table = ShoppDatabaseObject::tablename('shipping');

			// Move billing address data to the address table
			sDB::query("INSERT INTO $address_table (customer, type, address, xaddress, city, state, country, postcode, created, modified)
						SELECT customer, 'billing', address, xaddress, city, state, country, postcode, created, modified FROM $billing_table");

			sDB::query("INSERT INTO $address_table (customer, type, address, xaddress, city, state, country, postcode, created, modified)
						SELECT customer, 'shipping', address, xaddress, city, state, country, postcode, created, modified FROM $shipping_table");
		}

		// Migrate to WP custom posts & taxonomies
		if ($db_version <= 1131) {

			// Copy products to posts
				$catalog_table = ShoppDatabaseObject::tablename('catalog');
				$product_table = ShoppDatabaseObject::tablename('product');
				$price_table = ShoppDatabaseObject::tablename('price');
				$summary_table = ShoppDatabaseObject::tablename('summary');
				$meta_table = ShoppDatabaseObject::tablename('meta');
				$category_table = ShoppDatabaseObject::tablename('category');
				$tag_table = ShoppDatabaseObject::tablename('tag');
				$purchased_table = ShoppDatabaseObject::tablename('purchased');
				$index_table = ShoppDatabaseObject::tablename('index');

				$post_type = 'shopp_product';

				// Create custom post types from products, temporarily use post_parent for link to original product entry
				sDB::query("INSERT INTO $wpdb->posts (post_type, post_name, post_title, post_excerpt, post_content, post_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_parent)
							SELECT '$post_type', slug, name, summary, description, status, created, created, modified, modified, id FROM $product_table");

				// Update purchased table product column with new Post ID so sold counts can be updated
				sDB::query("UPDATE $purchased_table AS pd JOIN $wpdb->posts AS wp ON wp.post_parent=pd.product AND wp.post_type='$post_type' SET pd.product=wp.ID");

				// Update product links for prices and meta
				sDB::query("UPDATE $price_table AS price JOIN $wpdb->posts AS wp ON price.product=wp.post_parent SET price.product=wp.ID WHERE wp.post_type='$post_type'");
				sDB::query("UPDATE $meta_table AS meta JOIN $wpdb->posts AS wp ON meta.parent=wp.post_parent AND wp.post_type='$post_type' AND meta.context='product' SET meta.parent=wp.ID");
				sDB::query("UPDATE $index_table AS i JOIN $wpdb->posts AS wp ON i.product=wp.post_parent AND wp.post_type='$post_type' SET i.product=wp.ID");

				// Preliminary summary data
				sDB::query("INSERT INTO $summary_table (product, featured, variants, addons, modified)
						   SELECT wp.ID, p.featured, p.variations, p.addons, '0000-00-00 00:00:01'
						   FROM $product_table AS p
						   JOIN $wpdb->posts as wp ON p.id=wp.post_parent AND wp.post_type='$post_type'");

				// Move product options column to meta setting
				sDB::query("INSERT INTO $meta_table (parent, context, type, name, value)
						SELECT wp.ID, 'product', 'meta', 'options', options
						FROM $product_table AS p
						JOIN $wpdb->posts AS wp ON p.id=wp.post_parent AND wp.post_type='$post_type'");

			// Migrate Shopp categories and tags to WP taxonomies

				// Are there tag entries in the meta table? Old dev data present use meta table tags. No? use tags table.
				$dev_migration = ($db_version >= 1120);

				// Copy categories and tags to WP taxonomies
				$tag_current_table = $dev_migration?"$meta_table WHERE context='catalog' AND type='tag'":$tag_table;

				$terms = sDB::query("(SELECT id, 'shopp_category' AS taxonomy, name, parent, description, slug FROM $category_table)
											UNION
										(SELECT id, 'shopp_tag' AS taxonomy, name, 0 AS parent, '' AS description, name AS slug FROM $tag_current_table) ORDER BY id", 'array');

				// Prep category images for the move
				$category_image_offset = 65535;
				sDB::query("UPDATE $meta_table set parent=parent+$category_image_offset WHERE context='category' AND type='image'");

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

					if ($exists = sDB::query("SELECT term_id, term_group FROM $wpdb->terms WHERE slug = '$slug'", 'array')) {
						$term_group = $exists[0]->term_group;
						$id = $exists[0]->term_id;
						$num = 2;
						do {
							$alternate = sDB::escape($slug."-".$num++);
							$alternate_used = sDB::query("SELECT slug FROM $wpdb->terms WHERE slug='$alternate'");
						} while ($alternate_used);
						$slug = $alternate;

						if ( empty($term_group) ) {
							$term_group = sDB::query("SELECT MAX(term_group) AS term_group FROM $wpdb->terms GROUP BY term_group", 'auto', 'col', 'term_group');
							sDB::query("UPDATE $wpdb->terms SET term_group='$term_group' WHERE term_id='$id'");
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
								sDB::query("INSERT INTO $meta_table (parent, context, type, name, value)
											SELECT $term_id, 'category', 'meta', '$field', $field
											FROM $category_table
											WHERE id=$term->id");


							// Update category images to new term ids
							sDB::query("UPDATE $meta_table set parent='$term_id' WHERE parent='".((int)$term->id+$category_image_offset)."' AND context='category' AND type='image'");
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

				$rels = sDB::query("SELECT $cols FROM $catalog_table AS c LEFT JOIN $wpdb->posts AS wp ON c.product=wp.post_parent AND wp.post_type='$post_type' WHERE $where", 'array');

				foreach ((array)$rels as $r) {
					$object_id = $r->product;
					$taxonomy = $wp_taxonomies[($db_version >= 1125?$r->taxonomy:$r->type)];
					$term_id = $mapping[$taxonomy][$r->parent];
					if ( !isset($tt_ids[$taxonomy]) ) continue;
					if ( !isset($tt_ids[$taxonomy][$term_id]) ) continue;

					$tt_id = $tt_ids[$taxonomy][$term_id];
					if ( empty($tt_id) ) continue;

					sDB::query("INSERT $wpdb->term_relationships (object_id, term_taxonomy_id) VALUES ($object_id, $tt_id)");
				}

				if (isset($tt_ids['shopp_category']))
					wp_update_term_count_now($tt_ids['shopp_category'], 'shopp_category');

				if (isset($tt_ids['shopp_tag']))
					wp_update_term_count_now($tt_ids['shopp_tag'], 'shopp_tag');

				// Clear custom post type parents
				sDB::query("UPDATE $wpdb->posts SET post_parent=0 WHERE post_type='$post_type'");

		} // END if ($db_version <= 1131)

		if ($db_version <= 1133) {

			// Ditch old WP pages for pseudorific new ones
			$search = array();
			$shortcodes = array('[catalog]', '[cart]', '[checkout]', '[account]');
			foreach ($shortcodes as $string) $search[] = "post_content LIKE '%$string%'";
			$results = sDB::query("SELECT ID, post_title AS title, post_name AS slug, post_content FROM $wpdb->posts WHERE post_type='page' AND (".join(" OR ", $search).")", 'array');

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

			sDB::query("UPDATE $wpdb->posts SET post_name=CONCAT(post_name, '-deprecated'), post_status='trash' where ID IN (".join(', ', $trash).")");
		}

		// Move needed price table columns to price meta records
		if ($db_version <= 1135) {
			$meta_table = ShoppDatabaseObject::tablename('meta');
			$price_table = ShoppDatabaseObject::tablename('price');

			// Move 'options' to meta 'options' record
			sDB::query("INSERT INTO $meta_table (parent, context, type, name, value, created, modified)
						SELECT id, 'price', 'meta', 'options', options, created, modified FROM $price_table");

			// Merge 'weight', 'dimensions' and 'donation' columns to a price 'settings' record
			sDB::query("INSERT INTO $meta_table (parent, context, type, name, value, created, modified)
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
				sDB::query("UPDATE $purchase_table SET gateway='$classname' WHERE gateway='$name'");
		} // END if ($db_version <= 1145)

		if ($db_version <= 1148) {
			$price_table = ShoppDatabaseObject::tablename('price');
			sDB::query("UPDATE $price_table SET optionkey=(options*7001) WHERE context='addon'");
		}

		if ( $db_verison <= 1150 ) {
			$meta_table = ShoppDatabaseObject::tablename('meta');
			sDB::query("DELETE $meta_table FROM $meta_table LEFT OUTER JOIN (SELECT MAX(id) AS keepid FROM $meta_table WHERE context='category' AND type='meta' GROUP BY parent, name) AS keepRowTable ON $meta_table.id = keepRowTable.keepid WHERE keepRowTable.keepid IS NULL AND context='category' AND type='meta'");
		}

	}

	public function upgrade_130 () {
		global $wpdb;
		$db_version = ShoppSettings::dbversion();

		if ( $db_version < 1201 ) {
			// 1.3 schema changes
			$this->upschema();

			// All existing sessions must be cleared and restarted, 1.3 & 1.3.6 sessions are not compatible with any prior version of Shopp
 		   	ShoppShopping()->reset();
			$sessions_table = ShoppDatabaseObject::tablename('shopping');
			sDB::query("DELETE FROM $sessions_table");

			// Remove all the temporary PHP native session data from the options table
			sDB::query("DELETE FROM from $wpdb->options WHERE option_name LIKE '__php_session_*'");
		}

		if ( $db_version < 1200 ) {

			$meta_table = ShoppDatabaseObject::tablename('meta');
			sDB::query("UPDATE $meta_table SET value='on' WHERE name='theme_templates' AND (value != '' AND value != 'off')");
			sDB::query("DELETE FROM $meta_table WHERE type='image' AND value LIKE '%O:10:\"ShoppError\"%'"); // clean up garbage from legacy bug
			sDB::query("DELETE FROM $meta_table WHERE CONCAT('', name *1) = name AND context = 'category' AND type = 'meta'"); // clean up bad category meta

			// Update purchase gateway values to match new prefixed class names
			$gateways = array(
				'PayPalStandard' => 'ShoppPayPalStandard',
				'_2Checkout' => 'Shopp2Checkout',
				'OfflinePayment' => 'ShoppOfflinePayment',
				'TestMode' => 'ShoppTestMode',
				'FreeOrder' => 'ShoppFreeOrder'

			);
			foreach ( $gateways as $name => $classname )
				sDB::query("UPDATE $purchase_table SET gateway='$classname' WHERE gateway='$name'");

			$activegateways = explode(',', shopp_setting('active_gateways'));
			foreach ( $activegateways as &$setting )
				if ( false === strpos($setting, 'Shopp') )
					$setting = str_replace(array_keys($gateways), $gateways, $setting);
			shopp_set_setting('active_gateways', join(',', $activegateways));

		}

		if ( $db_version < 1200 && shopp_setting_enabled('tax_inclusive') ) {

			$price_table = ShoppDatabaseObject::tablename('price');

			$taxrates = shopp_setting('taxrates');
			$baseop = shopp_setting('base_operations');

			$taxtaxes = array();	// Capture taxonomy condition tax rates
			$basetaxes = array();	// Capture base of operations rate(s)
			foreach ( $taxrates as $rate ) {

				if ( ! ( $baseop['country'] == $rate['country'] || ShoppTax::ALL == $rate['country'] ) ) continue;
				if ( ! empty($rate['zone']) && $baseop['zone'] != $rate['zone'] ) continue;

				if ( ! empty($rate['rules']) && $rate['logic'] == 'any' ) { // Capture taxonomy conditional rates
					foreach ( $rate['rules'] as $raterule ) {
						if ( 'product-category' == $raterule['p'] ) $taxname = ProductCategory::$taxon . '::'. $raterule['v'];
						elseif ('product-tags' == $raterule['p'] ) $taxname = ProductTag::$taxon . '::'. $raterule['v'];
						$taxtaxes[ $taxname ] = Shopp::floatval($rate['rate']) / 100;
					}
				} else $basetaxes[] = Shopp::floatval($rate['rate']) / 100;
			}

			// Find products by in each taxonomy termno
			$done = array(); // Capture each set into the "done" list
			foreach ( $taxtaxes as $taxterm => $taxrate ) {
				list($taxonomy, $name) = explode('::', $taxterm);
				$Collection = new ProductCollection;
				$Collection->load(array('ids' => true, 'taxquery' => array(array('taxonomy' => $taxonomy, 'field' => 'name', 'terms' => $name))));
				$query = "UPDATE $price_table SET price=price+(price*$taxrate) WHERE tax='on' AND product IN (" . join(',', $Collection->products). ")";

				sDB::query($query);
				$done = array_merge($done, $Collection->products);
			}

			// Update the rest of the prices (skipping those we've already done) with the tax rate that matches the base of operations
			$taxrate = array_sum($basetaxes); // Merge all the base taxes into a single rate
			$done = empty($done) ? '' : " AND product NOT IN (" . join(',', $done) . ")";
			$query = "UPDATE $price_table SET price=price+(price*$taxrate) WHERE tax='on'$done";

			sDB::query($query);
		}

	}

}

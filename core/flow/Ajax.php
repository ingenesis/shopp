<?php
/**
 * Ajax.php
 *
 * Handles AJAX calls from Shopp interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February  6, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage ajax
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppAjax
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ShoppAjax {

	/**
	 * ShoppAjax constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct () {

		if ( isset($_POST['action']) && 'add-menu-item' == $_POST['action'] ) {
			// Boot the Admin controller to handle AJAX added WP nav menu items
			new ShoppAdmin();
		}

		// File uploads require unprivileged access
		add_action('wp_ajax_nopriv_shopp_upload_image', array($this, 'upload_image'));
		add_action('wp_ajax_nopriv_shopp_upload_file', array($this, 'upload_file'));
		add_action('wp_ajax_shopp_upload_image', array($this, 'upload_image'));
		add_action('wp_ajax_shopp_upload_file', array($this, 'upload_file'));

		// Actions that can happen on front end whether or not logged in
		add_action('wp_ajax_nopriv_shopp_ship_costs', array($this, 'shipping_costs'));
		add_action('wp_ajax_shopp_ship_costs', array($this, 'shipping_costs'));

		// Below this line must have nonce protection (all admin ajax go below)
		if ( ! isset($_REQUEST['_wpnonce']) ) return;

		add_action('wp_ajax_shopp_category_products', array($this, 'category_products'));
		add_action('wp_ajax_shopp_order_receipt', array($this, 'receipt'));
		add_action('wp_ajax_shopp_category_children', array($this, 'category_children'));
		add_action('wp_ajax_shopp_category_order', array($this, 'category_order'));
		add_action('wp_ajax_shopp_category_products_order', array($this, 'products_order'));
		add_action('wp_ajax_shopp_country_zones', array($this, 'country_zones'));
		add_action('wp_ajax_shopp_spec_template', array($this, 'load_spec_template'));
		add_action('wp_ajax_shopp_options_template', array($this, 'load_options_template'));
		add_action('wp_ajax_shopp_add_category', array($this, 'add_category'));
		add_action('wp_ajax_shopp_edit_slug', array($this, 'edit_slug'));
		add_action('wp_ajax_shopp_order_note_message', array($this, 'order_note_message'));
		add_action('wp_ajax_shopp_activate_key', array($this, 'activate_key'));
		add_action('wp_ajax_shopp_deactivate_key', array($this, 'deactivate_key'));
		add_action('wp_ajax_shopp_rebuild_search_index', array('ShoppAdminSystem', 'reindex'));
		add_action('wp_ajax_shopp_upload_local_taxes', array($this, 'upload_local_taxes'));
		add_action('wp_ajax_shopp_feature_product', array($this, 'feature_product'));
		add_action('wp_ajax_shopp_update_inventory', array($this, 'update_inventory'));
		add_action('wp_ajax_shopp_import_file', array($this, 'import_file'));
		add_action('wp_ajax_shopp_storage_suggestions', array($this, 'storage_suggestions'), 11);
		add_action('wp_ajax_shopp_select_customer', array($this, 'select_customer'));
		add_action('wp_ajax_shopp_suggestions', array($this, 'suggestions'));
		add_action('wp_ajax_shopp_verify_file', array($this, 'verify_file'));
		add_action('wp_ajax_shopp_gateway', array($this, 'gateway_ajax'));
		add_action('wp_ajax_shopp_debuglog', array($this, 'logviewer'));
		add_action('wp_ajax_shopp_nonag', array($this, 'nonag'));

	}

	public function receipt () {
		check_admin_referer('wp_ajax_shopp_order_receipt');
		if ( 0 == intval($_GET['id']) ) die('-1');

		ShoppPurchase( new ShoppPurchase((int)$_GET['id']));

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
			\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
		<html><head><title>".get_bloginfo('name').' &mdash; ' . Shopp::__('Order') . ' #' . shopp( 'purchase', 'get-id' ) . "</title>";
			echo '<style type="text/css">body { padding: 20px; font-family: Arial,Helvetica,sans-serif; }</style>';
			echo "<link rel='stylesheet' href='".shopp_template_url('shopp.css')."' type='text/css' />";
		echo "</head><body>";
		echo apply_filters('shopp_admin_order_receipt',shopp('purchase','get-receipt','template=receipt-admin.php'));

		if ( isset($_GET['print']) &&  'auto' == $_GET['print'] )
			echo '<script type="text/javascript">window.onload = function () { window.print(); window.close(); }</script>';

		echo "</body></html>";
		exit();
	}

	public function category_menu () {
		check_admin_referer('wp_ajax_shopp_category_menu');

		$Categorize = new Categorize();
		echo '<option value="">' . Shopp::esc_attr__('Select a category&hellip;') . '</option>';
		echo '<option value="catalog-products">' . Shopp::esc_attr__('All Products') . '</option>';
		echo $Categorize->menu();
		exit();
	}

	public function category_products () {
		check_admin_referer('wp_ajax_shopp_category_products');
		if ( ! isset($_GET['category']) ) return;
		$category = $_GET['category'];

		$Warehouse = new ShoppAdminWarehouse;
		echo $Warehouse->category($category);
		exit();
	}

	public function country_zones () {
		check_admin_referer('wp_ajax_shopp_country_zones');
		$zones = Lookup::country_zones();
		if ( isset($_GET['country']) && isset($zones[ $_GET['country'] ]) )
			echo json_encode($zones[ $_GET['country'] ]);
		else echo json_encode(false);
		exit();
	}

	public function load_spec_template () {
		check_admin_referer('wp_ajax_shopp_spec_template');

		$Category = new ProductCategory((int)$_GET['category']);
		$Category->load_meta();

		if ( isset($Category->specs) )
			echo json_encode($Category->specs);
		else
			echo json_encode(false);
		exit();
	}

	public function load_options_template() {
		check_admin_referer('wp_ajax_shopp_options_template');

		$Category = new ProductCategory((int)$_GET['category']);
		$Category->load_meta();

		$result = new stdClass();
		$result->options = isset($Category->options) ? $Category->options : array();
		$result->prices  = isset($Category->prices) ? $Category->prices : array();

		echo json_encode($result);
		exit();
	}

	public function upload_image () {
		$file = isset($_FILES['file']) ? $_FILES['file'] : false;
		$parent = isset($_GET['parent']) ? (int)$_GET['parent'] : false;
		$type = isset($_GET['type']) ? $_GET['type'] : false;
		ShoppAdminWarehouse::images($file, $parent, $type);
	}

	public function upload_file () {
		$file = isset($_FILES['file']) ? $_FILES['file'] : false;
		ShoppAdminWarehouse::downloads($file, $_POST);
	}

	public function add_category () {
		// Add a category in the product editor
		check_admin_referer('wp_ajax_shopp_add_category');
		if ( empty($_GET['name']) ) die(0);

		$Catalog = new ShoppCatalog();
		$Catalog->load_categories();

		$Category         = new ProductCategory();
		$Category->name   = $_GET['name'];
		$Category->slug   = sanitize_title_with_dashes($Category->name);
		$Category->parent = $_GET['parent'];

		// Work out pathing
		$paths = array();
		if ( ! empty($Category->slug) ) $paths = array($Category->slug);  // Include self

		$parentkey = -1;
		// If we're saving a new category, lookup the parent
		if ( $Category->parent > 0 ) {
			array_unshift($paths, $Catalog->categories[ $Category->parent ]->slug);
			$parentkey = $Catalog->categories[ $Category->parent ]->parent;
		}

		while ($category_tree = $Catalog->categories[ $parentkey ]) {
			array_unshift($paths, $category_tree->slug);
			$parentkey = $category_tree->parent;
		}

		if (count($paths) > 1) $Category->uri = join("/",$paths);
		else $Category->uri = $paths[0];

		$Category->save();
		echo json_encode($Category);
		exit();

	}

	/**
	 * Handles Product/Category slug editor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function edit_slug () {
		check_admin_referer( 'wp_ajax_shopp_edit_slug' );

		$defaults = array(
			'slug'  => false,
			'type'  => false,
			'id'    => false,
			'title' => false,
		);
		$p = array_merge( $defaults, $_POST );
		extract( $p );

		if ( false === $slug || false === $id ) die('-1');

		switch ( $type ) {
			case 'category':
				$Category = new ProductCategory( $_POST['id'] );
				if ( $slug == $Category->slug ) die('-1');
				$term = get_term( $Category->id, $Category->taxonomy );

				$Category->slug = wp_unique_term_slug( sanitize_title_with_dashes( $slug ), $term );
				$Category->save();

				echo apply_filters( 'editable_slug', $Category->slug );
				break;
			case 'product':
				$Product = new ShoppProduct( $_POST['id'] );
				if ( $slug == $Product->slug ) die( '-1' ); // Same as before? Nothing to do so bail

				// Regardless of the true post status, we'll pass 'publish' here to ensure we get a unique slug back
				$Product->slug = wp_unique_post_slug( sanitize_title_with_dashes($slug), $Product->id, 'publish', ShoppProduct::posttype(), 0 );
				$Product->save();

				echo apply_filters( 'editable_slug', $Product->slug );
				break;
		}
		exit;
	}

	public function shipping_costs () {
		if ( ! isset($_GET['method']) ) die(0);
		$Order = ShoppOrder();

		$Shiprates = $Order->Shiprates;
		$selected = $Shiprates->selected();


		if ( $selected === false || ( isset($selected->slug) && $_GET['method'] != $selected->slug) ) {
			$Shiprates->selected( $_GET['method'] );
		}

		echo (string)$Order->Cart->totals();
		exit();
	}

	public function order_note_message () {
		check_admin_referer('wp_ajax_shopp_order_note_message');
		if ( ! isset($_GET['id']) ) die('1');

		$Note = new ShoppMetaObject(array('id' => intval($_GET['id']),'type'=>'order_note'));
		die($Note->value->message);
	}

	public function activate_key () {
		check_admin_referer('wp_ajax_shopp_activate_key');
		echo ShoppSupport::activate($_GET['key']);
		exit();
	}

	public function deactivate_key () {
		check_admin_referer('wp_ajax_shopp_deactivate_key');
		$sitekey = ShoppSupport::key();
		$key     = $sitekey['k'];
		echo ShoppSupport::deactivate($key);
		exit();
	}

	public function suggestions () {
		check_admin_referer('wp_ajax_shopp_suggestions');

		if ( isset($_GET['t']) ) { // @legacy
			switch ( $_GET['t'] ) {
				case "product-name":     $_GET['s'] = 'shopp_products'; break;
				case "product-tags":     $_GET['s'] = 'shopp_tag'; break;
				case "product-category": $_GET['s'] = 'shopp_category'; break;
				case "customer-type":    $_GET['s'] = 'shopp_customer_types'; break;
			}
		}

		if ( isset($_GET['s']) ) {
			global $wpdb;

			$source     = strtolower($_GET['s']);
			$q          = $_GET['q'];
			$orderlimit = '';

			do_action('shopp_suggestions_from_' . $source);

			$joins = $where = array();
			switch ($source) {
				case 'wp_posts':
					$id      = 'ID';
					$name    = 'post_title';
					$table   = $wpdb->posts;
					$where[] = "post_type='post'";
					break;
				case 'wp_pages':
					$id      = 'ID';
					$name    = 'post_title';
					$table   = $wpdb->posts;
					$where[] = "post_type='page'";
					break;
				case 'wp_categories':
					$id      = 't.term_id';
					$name    = 'name';
					$table   = "$wpdb->terms AS t";
					$joins[] = "INNER JOIN  $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id";
					$where[] = "tt.taxonomy = 'category'";
					break;
				case 'wp_tags':
					$id      = 't.term_id';
					$name    = 'name';
					$table   = "$wpdb->terms AS t";
					$joins[] = "INNER JOIN  $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id";
					$where[] = "tt.taxonomy = 'post_tag'";
					break;
				case 'wp_media':
					$id      = 'ID';
					$name    = 'post_title';
					$table   = $wpdb->posts;
					$where[] = "post_type='attachment'";
					break;
				case 'wp_users':
					$id    = 'ID';
					$name  = 'user_login';
					$table = $wpdb->users;
					break;
				case 'shopp_memberships':
					$id      = 'id';
					$name    = 'name';
					$table   = ShoppDatabaseObject::tablename('meta');
					$where[] = "context='membership'";
					$where[] = "type='membership'";
					break;
				case 'shopp_products':
					$id      = 'ID';
					$name    = 'post_title';
					$table   = $wpdb->posts;
					$where[] = "post_type='" . ShoppProduct::$posttype . "'";
					break;
				case 'shopp_promotions':
					$id    = 'id';
					$name  = 'name';
					$table = ShoppDatabaseObject::tablename(ShoppPromo::$table);
					break;
				case 'shopp_downloads':
					$id      = 'id';
					$name    = 'name';
					$table   = ShoppDatabaseObject::tablename('meta');
					$where[] = "context='price'";
					$where[] = "type='download'";
					break;
				case 'shopp_target_markets':
					$markets = shopp_setting('target_markets');
					$results = array();
					foreach ( $markets as $id => $market ) {
						if ( strpos(strtolower($market), strtolower($_GET['q'])) !== false ) {
							$_         = new StdClass();
							$_->id     = $id;
							$_->name   = stripslashes($market);
							$results[] = $_;
						}
					}
					echo json_encode($results);
					exit();
					break;
				case 'shopp_customer_types':
					$types = Lookup::customer_types();
					$results = array();
					foreach ( $types as $id => $type ) {
						if ( strpos(strtolower($type), strtolower($_GET['q'])) !== false ) {
							$_         = new StdClass();
							$_->id     = $id;
							$_->name   = $type;
							$results[] = $_;
						}
					}
					echo json_encode($results);
					exit();
					break;
				default:
					if ( taxonomy_exists($_GET['s']) ) {
						$taxonomy = $_GET['s'];
						$id      = 't.term_id';
						$name    = 'name';
						$table   = "$wpdb->terms AS t";
						$joins[] = "INNER JOIN  $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id";
						$where[] = "tt.taxonomy = '" . $taxonomy . "'";
						if ( 'shopp_popular_tags' == strtolower($q) ) {
							$q = '';
							$orderlimit = "ORDER BY tt.count DESC LIMIT 15";
						}
					}
					break;
			}

			if ( ! empty($q) )
				$where[] = "$name LIKE '%" . sDB::escape($q) . "%'";
			$where = join(' AND ', $where);
			$joins = join(' ', $joins);

			$query = "SELECT $id AS id, $name AS name FROM $table $joins WHERE $where $orderlimit";
			$items = sDB::query($query, 'array');
			echo json_encode($items);
			exit();
		}

	}

	public function select_customer () {
		// check_admin_referer('wp_ajax_shopp_select_customer');
		$defaults = array(
			'page'     => false,
			'paged'    => 1,
			'per_page' => 7,
			'status'   => false,
			's'        => ''
		);

		$args = wp_parse_args($_REQUEST, $defaults);
		extract($args, EXTR_SKIP);

		if ( ! empty($s) ) {
			$s      = stripslashes($s);
			$search = sDB::escape($s);
			$where  = array();
			if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $s, $props, PREG_SET_ORDER) ) {
				foreach ( $props as $search ) {
					$keyword = ! empty($search[2]) ? $search[2] : $search[3];
					switch ( strtolower($search[1]) ) {
						case "company":  $where[] = "c.company LIKE '%$keyword%'"; break;
						case "login":    $where[] = "u.user_login LIKE '%$keyword%'"; break;
						case "address":  $where[] = "(b.address LIKE '%$keyword%' OR b.xaddress='%$keyword%')"; break;
						case "city":     $where[] = "b.city LIKE '%$keyword%'"; break;
						case "province":
						case "state":    $where[] = "b.state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode": $where[] = "b.postcode='$keyword'"; break;
						case "country":  $where[] = "b.country='$keyword'"; break;
					}
				}
			} elseif ( strpos($s,'@') !== false ) {
				 $where[] = "c.email LIKE '%$search%'";
			} elseif ( is_numeric($s) ) {
				$where[] = "c.phone='$search'";
			} else $where[] = "(CONCAT(c.firstname,' ',c.lastname) LIKE '%$search%' OR c.company LIKE '%$s%' OR u.user_login LIKE '%$s%')";

			$pagenum = absint( $paged );
			if ( empty($pagenum) ) $pagenum = 1;
			$index = ($per_page * ($pagenum-1));

			$customer_table = ShoppDatabaseObject::tablename(Customer::$table);
			$billing_table  = ShoppDatabaseObject::tablename(BillingAddress::$table);
			$purchase_table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
			global $wpdb;
			$users_table = $wpdb->users;

			$select = array(
				'columns' => 'SQL_CALC_FOUND_ROWS c.*,city,state,country,user_login',
				'table'   => "$customer_table as c",
				'joins'   => array(
						$billing_table => "LEFT JOIN $billing_table AS b ON b.customer=c.id AND b.type='billing'",
						$users_table   => "LEFT JOIN $users_table AS u ON u.ID=c.wpuser AND (c.wpuser IS NULL OR c.wpuser != 0)"
					),
				'where'   => $where,
				'groupby' => "c.id",
				'orderby' => "c.created DESC",
				'limit'   => "$index,$per_page"
			);
			$query = sDB::select($select);

		}
		// if (!empty($starts) && !empty($ends)) $where[] = ' (UNIX_TIMESTAMP(c.created) >= '.$starts.' AND UNIX_TIMESTAMP(c.created) <= '.$ends.')';

		$Customers = sDB::query($query, 'array', 'index', 'id');
		$url       = admin_url('admin-ajax.php');
		?>
		<html>
		<head>
			<link rel="stylesheet" id="wp-admin"  href="<?php echo admin_url('css/wp-admin.css'); ?>" type="text/css" media="all" />
			<link rel="stylesheet" id="shopp-admin"  href="<?php echo SHOPP_ADMIN_URI.'/styles/admin.css'; ?>" type="text/css" media="all" />
		</head>
		<body id="customer-select">
		<?php
		if ( ! empty($Customers) ): ?>
		<ul>
			<?php foreach ( $Customers as $Customer ): ?>
			<li><a href="<?php echo add_query_arg(array('order-action'=>'change-customer','page'=>$_GET['page'],'id'=>(int)$_GET['id'],'customerid'=>$Customer->id),admin_url('admin.php')); ?>" target="_parent">
			<?php
			$wp_user = get_userdata($Customer->wpuser);
			$userlink = add_query_arg('user_id', $Customer->wpuser, admin_url('user-edit.php'));
 			echo get_avatar( $Customer->wpuser, 48 );
			?>
			<?php echo "<strong>$Customer->firstname $Customer->lastname</strong>"; ?><?php if ( ! empty($Customer->company) ) echo ", $Customer->company"; ?>
			<?php if ( ! empty($Customer->email) ) echo "<br />$Customer->email"; ?>
			<?php if ( ! empty($Customer->phone) ) echo "<br />$Customer->phone"; ?>
			</a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php else: ?>
		<?php Shopp::_e('No customers found.'); ?>
		<?php endif; ?>
		</body>
		</html>
		<?php
		exit();
	}

	public function upload_local_taxes () {
		check_admin_referer('shopp-settings-taxrates');
		$rates = ShoppAdminSystem::taxrate_upload();
		echo json_encode($rates);
		exit();
	}

	public function feature_product () {
		check_admin_referer('wp_ajax_shopp_feature_product');

		if ( empty($_GET['feature']) ) die('0');

		$Product = new ProductSummary((int)$_GET['feature']);
		if ( empty($Product->product) ) die('0');

		$Product->featured = ('on' == $Product->featured) ? 'off' : 'on';
		$Product->save();
		echo $Product->featured;
		exit();
	}

	public function update_inventory () {

		check_admin_referer('wp_ajax_shopp_update_inventory');

		$restocked = shopp_product_variant_set_stock((int) $_GET['id'], (int) $_GET['stock'], 'restock');

		if ( $restocked ) {
			echo '1';
			exit;
		} else die('0');

	}

	public function import_file () {
		check_admin_referer('wp_ajax_shopp_import_file');
		$Shopp  = Shopp::object();
		$Engine =& $Shopp->Storage->engines['download'];

		$error    = function ( $s ) {
			die( json_encode( array( "error" => $s ) ) );
        };

		if ( empty($_REQUEST['url']) )
			$error(Shopp::__('No file import URL was provided.'));
		$url      = $_REQUEST['url'];
		$request  = parse_url($url);
		$headers  = array();
		$filename = basename($request['path']);

		$_         = new StdClass();
		$_->name   = $filename;
		$_->stored = false;

		$File           = new ProductDownload();
		$stored         = false;
		$File->engine(); // Set engine from storage settings
		$File->uri      = sanitize_path($url);
		$File->type     = "download";
		$File->name     = $filename;
		$File->filename = $filename;

		if ( $File->found() ) {
			// File in storage, look up meta from storage engine
			$File->readmeta();
			$_->stored = true;
			$_->path   = $File->uri;
			$_->size   = $File->size;
			$_->mime   = $File->mime;
			if ( $_->mime == "application/octet-stream" || $_->mime == "text/plain" )
				$mime = file_mimetype($File->name);
			if ( $mime == "application/octet-stream" || $mime == "text/plain" )
				$_->mime = $mime;
		} else {
			if ( ! ($importfile = @tempnam(sanitize_path(realpath(SHOPP_TEMP_PATH) ), 'shp')) ) $error(Shopp::__('A temporary file could not be created for importing the file.', $importfile));
			if ( ! ($incoming   = @fopen($importfile, 'w')) ) $error(Shopp::__('A temporary file at %s could not be opened for importing.', $importfile));

			if ( ! ($file = @fopen(linkencode($url), 'rb')) ) $error(Shopp::__('The file at %s could not be opened for importing.', $url));
			$data = @stream_get_meta_data($file);

			if ( isset($data['timed_out']) && $data['timed_out'] ) $error(Shopp::__('The connection timed out while trying to get information about the target file.'));

			if ( isset($data['wrapper_data']) ) {
				foreach ( $data['wrapper_data'] as $d ) {
					if ( strpos($d, ':') === false ) continue;
					list($name, $value) = explode(': ', $d);
					if ( $rel = strpos($value, ';') ) $headers[ $name ] = substr($value, 0, $rel);
					else $headers[ $name ] = $value;
				}
			}

			$tmp = basename($importfile);

			$_->path = $importfile;
			if (empty($headers)) {
				// Stat file data directly if no stream data available
				$_->size = filesize($url);
				$_->mime = file_mimetype($url);
			} else {
				// Use the stream data
				$_->size = $headers['Content-Length'];
				$_->mime = $headers['Content-Type'] == 'text/plain' ? file_mimetype($_->name) : $headers['Content-Type'];
			}
		}

		// Mimetype must be set or we'll have problems in the UI
		if ( ! $_->mime ) $_->mime = "application/octet-stream";

		echo str_repeat(' ', 1024) . "\n"; // Minimum browser data
		echo '<script type="text/javascript">var importFile = ' . json_encode($_) . ';</script>'."\n";
		echo '<script type="text/javascript">var importProgress = 0;</script>' . "\n";
		if ( $_->stored )
			exit();
		@ob_flush();
		@flush();

		$progress  = 0;
		$bytesread = 0;
		fseek($file, 0);
		$packet    = 1024*1024;
		set_time_limit(0); // Prevent timeouts
		while( ! feof($file) ) {
			if ( connection_status() !== 0 ) return false;
			$buffer = fread($file, $packet);
			if ( ! empty($buffer) ) {
				fwrite($incoming, $buffer);
				$bytesread += strlen($buffer);
				echo '<script type="text/javascript">importProgress = ' . ( $bytesread / (int)$_->size ) . ';</script>'."\n";
				@ob_flush();
				@flush();
			}
		}
		@ob_end_flush();
		fclose($file);
		fclose($incoming);

		wp_die('');
	}

	public function storage_suggestions () { exit(); }

	public function verify_file () {
		check_admin_referer('wp_ajax_shopp_verify_file');
		$Settings = &ShoppSettings();
		chdir(WP_CONTENT_DIR); // relative file system path context for realpath
		$url     = $_POST['url'];
		$request = parse_url($url);

		if ( $request['scheme'] == 'http' ) {
			$results = get_headers(linkencode($url));
			if ( substr($url,-1) == '/' ) die("ISDIR");
			if ( strpos($results[0], '200' ) === false) die("NULL");
		} else {
			$url = str_replace('file://', '', $url);

			if ($url{0} != '/' || substr($url, 0, 2) == "./" || substr($url, 0, 3) == "../")
				$result = apply_filters('shopp_verify_stored_file', $url);

			$url = sanitize_path(realpath($url));
			if ( ! file_exists($url) ) die('NULL');
			if (is_dir($url) ) die('ISDIR');
			if ( ! is_readable($url) ) die('READ');

		}

		die('OK');

	}

	public function category_children () {
		check_admin_referer('wp_ajax_shopp_category_children');

		if ( empty($_GET['parent']) ) die('0');
		$parent  = $_GET['parent'];

		$columns = array('id', 'parent', 'priority', 'name', 'uri', 'slug');

		$filters['columns'] = 'cat.' . join(',cat.', $columns);
		$filters['parent']  = $parent;

		$Catalog = new ShoppCatalog();
		$Catalog->outofstock = true;
		$Catalog->load_categories($filters);

		$columns[] = 'depth';
		foreach ( $Catalog->categories as &$Category ) {
			$properties = get_object_vars($Category);
			foreach ( $properties as $property => $value )
				if ( ! in_array($property, $columns) ) unset($Category->$property);
		}

		die(json_encode($Catalog->categories));
	}

	public function category_order () {
		check_admin_referer('wp_ajax_shopp_category_order');
		if ( empty($_POST['position']) || ! is_array($_POST['position']) ) die('0');

		$table   = ShoppDatabaseObject::tablename(ProductCategory::$table);
		$updates = $_POST['position'];
		foreach ( $updates as $id => $position )
			sDB::query("UPDATE $table SET priority='$position' WHERE id='$id'");
		die('1');
	}

	public function products_order () {
		check_admin_referer('wp_ajax_shopp_category_products_order');
		if ( empty($_POST['category']) || empty($_POST['position']) || !is_array($_POST['position']) ) die('0');

		global $wpdb;
		$updates = $_POST['position'];
		$category = (int)$_POST['category'];
		foreach ( (array)$updates as $id => $position )
			sDB::query("UPDATE $wpdb->term_relationships SET term_order='" . ((int)$position) . "' WHERE object_id='" . ((int)$id) . "' AND term_taxonomy_id='$category'");
		die('1');
	}

	public function gateway_ajax () {
		check_admin_referer('wp_ajax_shopp_gateway');
		if ( isset($_POST['pid']) ) {
			$Purchase = new ShoppPurchase($_POST['pid']);
			if ( $Purchase->gateway ) do_action('shopp_gateway_ajax_' . sanitize_title_with_dashes($Purchase->gateway), $Purchase);
		}
		exit();
	}

	/**
	 * Automatic refresh of the log is possible when $_REQUEST['refresh'] is set to something other than 'off'.
	 *
	 * @todo Investigate if it is possible to inject a formatted error log message as an XSS vector
	 */
	public function logviewer () {
		check_admin_referer('wp_ajax_shopp_debuglog'); ?>
		<html>
		<head>
		<?php if ( isset( $_REQUEST['refresh'] ) && 'off' !== $_REQUEST['refresh'] ): ?>
		<meta http-equiv="refresh" content="10">
		<?php endif; ?>
		<style type="text/css">
		body { margin: 0; padding: 0; font-family:monospace;font-size:1em;line-height:1em;}
		ol { list-style:decimal;padding-left:5em;background:#ececec;margin-left:0; margin-bottom: 1px; }
		ol li { background:white;margin:0;padding:5px; }
		a { color: #606060; text-decoration: none; }
		</style>
		</head>
		<body>
		<ol><?php $log = ShoppErrorLogging()->tail(1000); $size = count($log);
				foreach ( $log as $n => $line ) {
					if ( empty($line) ) continue;
					echo '<li' . ($n+1 == $size ? ' id="bottom"' : '') . '>' . $line . '</li>';
				}

		?></ol>
		<script type="text/javascript">
		document.getElementById('bottom').scrollIntoView();
		</script></body></html><?php exit();
	}

	public function nonag () {
		check_admin_referer('wp_ajax_shopp_nonag');
		$id = get_current_user_id();
		update_user_meta($id, 'shopp_nonag', (string)current_time('timestamp'));
	}

}
<?php
/**
 * ShoppAdmin
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January 6, 2010
 * @package shopp
 * @subpackage admin
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppAdmin
 *
 * @author Jonathan Davis
 * @package admin
 * @since 1.1
 **/
class ShoppAdmin extends FlowController {

	private $Pages = array();	// List of admin pages
	private $Menus = array();	// List of initialized WordPress menus
	public $Ajax = array();	// List of AJAX controllers
	public $MainMenu = false;
	public $Page = false;


	/**
	 * Initialize the capabilities, mapping to pages
	 *
	 * Capabilities						Role
	 * _______________________________________________
	 *
	 * shopp_settings					administrator
	 * shopp_settings_checkout
	 * shopp_settings_payments
	 * shopp_settings_shipping
	 * shopp_settings_taxes
	 * shopp_settings_presentation
	 * shopp_settings_system
	 * shopp_settings_update
	 * shopp_financials					shopp-merchant
	 * shopp_promotions
	 * shopp_products
	 * shopp_categories
	 * shopp_orders						shopp-csr
	 * shopp_customers
	 * shopp_menu
	 *
	 * @public $caps
	 **/
	public $caps = array(
		'main'=>'shopp_menu',
		'orders'=>'shopp_orders',
		'customers'=>'shopp_customers',
		'reports'=>'shopp_financials',
		'memberships'=>'shopp_products',
		'products'=>'shopp_products',
		'categories'=>'shopp_categories',
		'promotions'=>'shopp_promotions',
		'settings'=>'shopp_settings',
		'settings-preferences'=>'shopp_settings',
		'settings-payments'=>'shopp_settings_payments',
		'settings-shipping'=>'shopp_settings_shipping',
		'settings-taxes'=>'shopp_settings_taxes',
		'settings-pages'=>'shopp_settings_presentation',
		'settings-presentation'=>'shopp_settings_presentation',
		'settings-images'=>'shopp_settings_presentation',
		'settings-system'=>'shopp_settings_system'
	);

	/**
	 * Admin constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function __construct () {
		parent::__construct();

		$this->legacyupdate();
		// Add Dashboard Widgets
		add_action('wp_dashboard_setup', array($this, 'dashboard'));
		add_action('admin_print_styles-index.php', array($this, 'dashboard_css'));
		add_action('admin_init', array($this, 'tinymce'));
		add_action('load-plugins.php',array($this, 'pluginspage'));
		add_action('switch_theme',array($this, 'themepath'));
		add_filter('favorite_actions', array($this, 'favorites'));
		add_filter('shopp_admin_boxhelp', array($this, 'support'));
		add_action('load-update.php', array($this, 'admin_css'));
		add_action('admin_menu',array($this, 'taxonomies'),20);
		add_action('load-nav-menus.php',array($this, 'navmenus')); // @todo redundant? (Check with JD)
		add_action('wp_update_nav_menu_item', array($this, 'navmenu_items'));
		add_action('wp_setup_nav_menu_item',array($this, 'navmenu_setup'));

		add_filter('wp_dropdown_pages', array($this, 'storefront_pages'));


		// Add the default Shopp pages
		$this->addpage('orders',__('Orders','Shopp'),'Service','Managing Orders');
		$this->addpage('customers',__('Customers','Shopp'),'Account','Managing Customers');
		$this->addpage('reports',__('Reports','Shopp'),'Report','Reports');

		$this->addpage('products',__('Products','Shopp'),'Warehouse','Editing a Product','products');
		$this->addpage('categories',__('Categories','Shopp'),'Categorize','Editing a Category','products');

		$taxonomies = get_object_taxonomies(Product::$posttype, 'object');
		foreach ( $taxonomies as $t ) {
			if ($t->name == 'shopp_category') continue;
			$pagehook = str_replace('shopp_','',$t->name);
			$this->addpage($pagehook,$t->labels->menu_name,'Categorize','Editing Taxonomies','products');
		}

		$this->addpage('promotions',__('Promotions','Shopp'),'Promote','Running Sales & Promotions','products');
		// Not yet... $this->addpage('memberships',__('Memberships','Shopp'),'Members','Memberships & Access','products');
		$this->addpage('settings',__('Setup','Shopp'),'Setup','General Settings','settings');
		$this->addpage('settings-payments',__('Payments','Shopp'),'Setup','Payments Settings',"settings");
		$this->addpage('settings-shipping',__('Shipping','Shopp'),'Setup','Shipping Settings',"settings");
		$this->addpage('settings-taxes',__('Taxes','Shopp'),'Setup','Taxes Settings',"settings");
		$this->addpage('settings-pages',__('Pages','Shopp'),'Setup','Page Settings',"settings");
		$this->addpage('settings-images',__('Images','Shopp'),'Setup','Image Settings',"settings");
		$this->addpage('settings-presentation',__('Presentation','Shopp'),'Setup','Presentation Settings',"settings");
		$this->addpage('settings-preferences',__('Preferences','Shopp'),'Setup','Store Preferences',"settings");
		$this->addpage('settings-system',__('System','Shopp'),'Setup','System Settings',"settings");

		// Filter hook for adding/modifying Shopp admin menus
		apply_filters('shopp_admin_menus',$this);
		do_action('shopp_admin_menu'); // @deprecated

		reset($this->Pages);
		$this->MainMenu = key($this->Pages);

		wp_enqueue_style('shopp.menu',SHOPP_ADMIN_URI.'/styles/menu.css',array(),SHOPP_VERSION,'screen');

		// Set the currently requested page and menu
		if ( isset($_GET['page']) && false !== strpos($_GET['page'],basename(SHOPP_PATH)) ) $page = $_GET['page'];
		else return;

		if (isset($this->Pages[$page])) $this->Page = $this->Pages[$page];
		if (isset($this->Menus[$page])) $this->Menu = $this->Menus[$page];

	}

	function storefront_pages ($menu) {
		$CatalogPage = ShoppPages()->get('catalog');
		$shoppid = ShoppCatalogPage::frontid(); // uses impossibly long number ("Shopp" in decimal)

		$id = "<select name='page_on_front' id='page_on_front'>\n";
		if ( false === strpos($menu,$id) ) return $menu;
		$token = '<option value="0">&mdash; Select &mdash;</option>';

		if ( $shoppid == get_option('page_on_front') ) $selected = ' selected="selected"';
		$storefront = '<optgroup label="' . __('Shopp','Shopp') . '"><option value="' . $shoppid . '"' . $selected . '>' . esc_html($CatalogPage->title()) . '</option></optgroup><optgroup label="' . __('WordPress') . '">';

		$newmenu = str_replace($token,$token.$storefront,$menu);

		$token = '</select>';
		$newmenu = str_replace($token,'</optgroup>'.$token,$newmenu);
		return $newmenu;
	}

	/**
	 * Generates the Shopp admin menu
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function menus () {
		global $Shopp,$menu;

		$access = $this->caps['main'];
		if (Shopp::maintenance()) $access = 'manage_options';

		$this->topmenu('main','Shopp',$access,'orders',50);
		$this->topmenu('catalog',__('Catalog','Shopp'),$access,'products',50);
		$this->topmenu('setup',__('Setup','Shopp'),$access,'settings',50);

		// Add after the Shopp menus to avoid being purged by the duplicate separator check
		$menu[49] = array( '', 'read', 'separator-shopp', '', 'wp-menu-separator' );

		// Add menus to WordPress admin
		foreach ($this->Pages as $page) $this->addmenu($page);

		// Add admin JavaScript & CSS
		add_action('admin_enqueue_scripts', array($this, 'behaviors'),50);

		if (Shopp::maintenance()) return;

		// Add contextual help menus
		foreach ($this->Menus as $pagename => $screen) {
			add_action("load-$screen", array($this, 'help'));
		}
	}

	/**
	 * Registers a new page to the Shopp admin pages
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $name The internal reference name for the page
	 * @param string $label The label displayed in the WordPress admin menu
	 * @param string $controller The name of the controller to use for the page
	 * @param string $doc The title of the documentation article on docs.shopplugin.net
	 * @param string $parent The internal reference for the parent page
	 * @return void
	 **/
	function addpage ($name,$label,$controller,$doc=false,$parent=false) {
		$page = $this->pagename($name);
		if (!empty($parent)) $parent = $this->pagename($parent);
		$this->Pages[$page] = new ShoppAdminPage($name,$page,$label,$controller,$doc,$parent);
	}

	/**
	 * Adds a ShoppAdminPage entry to the Shopp admin menu
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 * @param mixed $page ShoppAdminPage object
	 **/
	function addmenu ($page) {
		$Shopp = Shopp::object();
		$name = $page->page;

		$controller = array(&$Shopp->Flow,'admin');
		if (shopp_setting_enabled('display_welcome') &&  empty($_POST['setup']))
			$controller = array($this,'welcome');
		if (Shopp::maintenance()) $controller = array($this,'reactivate');

		do_action('shopp_add_menu_'.$page->page);

		$capability = "none";
		if (isset($this->caps[$page->name]))
			$capability = $this->caps[$page->name];

		$taxonomies = get_object_taxonomies(Product::$posttype, 'names');
		if (in_array('shopp_'.$page->name,$taxonomies)) $capability = 'shopp_categories';

		$this->Menus[$page->page] = add_submenu_page(
			($page->parent)?$page->parent:$this->MainMenu,
			$page->label,
			$page->label,
			$capability,
			$name,
			$controller
		);

	}

	function topmenu ($name,$label,$access,$page,$position=50) {
		global $Shopp,$menu;

		while (isset($menu[$position])) $position++;

		$this->Menus[$page] = add_menu_page(
			$label,										// Page title
			$label,										// Menu title
			$access,									// Access level
			$this->pagename($page),						// Page
			array(&$Shopp->Flow,'parse'),				// Handler
			SHOPP_ADMIN_URI.'/icons/clear.png',			// Icon
			$position									// Menu position
		);

		do_action_ref_array('shopp_add_topmenu_'.$page,array($this->Menus[$page]));

	}

	/**
	 * Provide admin support for custom Shopp taxonomies
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function taxonomies () {
		global $menu,$submenu;
		if (!is_array($submenu)) return;

		$taxonomies = get_object_taxonomies(Product::$posttype);
		foreach ($submenu['shopp-products'] as &$submenus) {
			$taxonomy_name = str_replace('-','_',$submenus[2]);
			if (!in_array($taxonomy_name,$taxonomies)) continue;
			$submenus[2] = 'edit-tags.php?taxonomy='.$taxonomy_name;
			add_filter('manage_edit-'.$taxonomy_name.'_columns', array($this,'taxonomy_cols'));
			add_filter('manage_'.$taxonomy_name.'_custom_column', array($this,'taxonomy_product_column'), 10, 3);
		}

		add_action('admin_print_styles-edit-tags.php',array($this,'admin_css'));
		add_action('admin_head-edit-tags.php', array($this,'taxonomy_menu'));
	}

	function taxonomy_menu () {
		global $parent_file,$taxonomy;
		$taxonomies = get_object_taxonomies(Product::$posttype);
		if (in_array($taxonomy,$taxonomies)) $parent_file = 'shopp-products';
	}

	function taxonomy_cols ($cols) {
		return array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name'),
			'description' => __('Description'),
			'slug' => __('Slug'),
			'products' => __('Products','Shopp')
		);
	}

	function taxonomy_product_column ($markup, $name, $term_id) {
		global $taxonomy;
		if ('products' != $name) return;
		$term = get_term($term_id,$taxonomy);
		return '<a href="admin.php?page=shopp-products&'.$taxonomy.'='.$term->slug.'">'.$term->count.'</a>';
	}

	/**
	 * Takes an internal page name reference and builds the full path name
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $page The internal reference name for the page
	 * @return string The fully qualified resource name for the admin page
	 **/
	function pagename ($page) {
		return "shopp-$page";
	}

	/**
	 * Gets the name of the controller for the current request or the specified page resource
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $page (optional) The fully qualified reference name for the page
	 * @return string|boolean The name of the controller or false if not available
	 **/
	function controller ( $page = false ) {
		if (!$page && isset($this->Page->controller)) return $this->Page->controller;
		if (isset($this->Pages[$page])) return $this->Pages[$page]->controller;
		$screen = get_current_screen();
		return false;
	}

	/**
	 * Dynamically includes necessary JavaScript and stylesheets for the admin
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function behaviors () {
		global $Shopp,$wp_version,$hook_suffix;
		if (!in_array($hook_suffix,$this->Menus)) return;
		$this->admin_css();

		shopp_enqueue_script('shopp');

		$settings = array_filter(array_keys($this->Pages),array($this,'get_settings_pages'));
		if (in_array($this->Page->page,$settings)) shopp_enqueue_script('settings');

	}

	/**
	 * Queues the admin stylesheets
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void Description...
	 **/
	function admin_css () {

		global $taxonomy;
		if (isset($taxonomy)) { // Prevent loading styles if not on Shopp taxonomy editor
			$taxonomies = get_object_taxonomies(Product::$posttype);
			if (!in_array($taxonomy,$taxonomies)) return;
		}

		wp_enqueue_style('shopp.colorbox',SHOPP_ADMIN_URI.'/styles/colorbox.css',array(),'20110801','screen');
		wp_enqueue_style('shopp.admin',SHOPP_ADMIN_URI.'/styles/admin.css',array(),'20110801','screen');
		if ( 'rtl' == get_bloginfo('text_direction') )
			wp_enqueue_style('shopp.admin-rtl',SHOPP_ADMIN_URI.'/styles/rtl.css',array(),'20110801','all');

	}

	/**
	 * Adds contextually appropriate help information to interfaces
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function help () {

		$screen = get_current_screen();
		$pagename = array_search($screen->id, $this->Menus);

		if ('orders' == $pagename) $pagename = 'shopp-orders';

		if (!isset($this->Pages[$pagename])) return;
		$page = $this->Pages[$pagename];
		$url = SHOPP_DOCS.str_replace("+","_",urlencode($page->doc));
		$link = htmlspecialchars($page->doc);
		$content = '<a href="'.$url.'" target="_blank">'.$link.'</a>';

		$screenname = $this->Pages[$pagename]->name;

		if ( file_exists(SHOPP_PATH . "/core/ui/help/$screenname.php") )
			return include SHOPP_PATH . "/core/ui/help/$screenname.php";

		get_current_screen()->add_help_tab(array(
			'id' => 'shopp-help',
			'title' => __('Help'),
			'content' => $content
		));

		$target = substr($pagename,strrpos($pagename,'-')+1);
		if ($target == "orders" || $target == "customers") {
			ob_start();
			include(SHOPP_PATH."/core/ui/help/$target.php");
			$help = ob_get_contents();
			ob_end_clean();

			get_current_screen()->add_help_tab(array(
				'id' => 'shopp-help-'.$target,
				'title' => __('Advanced Search','Shopp'),
				'content' => $help
			));

		}

	}

	/**
	 * Returns a postbox help link to launch help screencasts
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $id The ID of the help resource
	 * @return string The anchor tag for the help link
	 **/
	function boxhelp ($id) {
		$helpurl = add_query_arg(array('src'=>'help','id'=>$id),admin_url('admin.php'));
		return apply_filters('shopp_admin_boxhelp','<a href="'.esc_url($helpurl).'" class="help"></a>');
	}

	/**
	 * Displays the welcome screen
	 *
	 * @return boolean
	 * @author Jonathan Davis
	 **/
	function welcome () {
		$Shopp = Shopp::object();
		if (shopp_setting('display_welcome') == "on" && empty($_POST['setup'])) {
			include(SHOPP_ADMIN_PATH."/help/welcome.php");
			return true;
		}
		return false;
	}

	/**
	 * Displays the re-activate screen
	 *
	 * @return boolean
	 * @author Jonathan Davis
	 **/
	function reactivate () {
		$Shopp = Shopp::object();
		include(SHOPP_ADMIN_PATH."/help/reactivate.php");
	}

	/**
	 * Adds a 'New Product' shortcut to the WordPress admin favorites menu
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $actions List of actions in the menu
	 * @return array Modified actions list
	 **/
	function favorites ($actions) {
		$key = esc_url(add_query_arg(array('page'=>$this->pagename('products'),'id'=>'new'),'admin.php'));
	    $actions[$key] = array(__('New Product','Shopp'),8);
		return $actions;
	}

	/**
	 * Initializes the Shopp dashboard widgets
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function dashboard () {
		$dashboard = shopp_setting('dashboard');
		if ( ! ( current_user_can('shopp_financials') && str_true($dashboard) ) ) return false;

		wp_add_dashboard_widget('dashboard_shopp_stats', __('Sales Stats','Shopp'), array($this,'stats_widget'),
			array('all_link' => '','feed_link' => '','width' => 'half','height' => 'single')
		);

		wp_add_dashboard_widget('dashboard_shopp_orders', __('Recent Orders','Shopp'), array($this,'orders_widget'),
			array('all_link' => 'admin.php?page='.$this->pagename('orders'),'feed_link' => '','width' => 'half','height' => 'single')
		);

		if (shopp_setting_enabled('inventory')) {
			wp_add_dashboard_widget('dashboard_shopp_inventory', __('Low Inventory Monitor','Shopp'), array($this,'inventory_widget'),
				array('all_link' => 'admin.php?page='.$this->pagename('products'),'feed_link' => '','width' => 'half','height' => 'single')
			);
		}

	}

	/**
	 * Loads the Shopp admin CSS on the WordPress dashboard for widget styles
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function dashboard_css () {
		wp_enqueue_style('shopp.dashboard',SHOPP_ADMIN_URI.'/styles/dashboard.css',array(),SHOPP_VERSION,'screen');
	}

	/**
	 * Dashboard Widgets
	 */
	/**
	 * Renders the order stats widget
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function stats_widget ($args=false) {
		$Shopp = Shopp::object();

		$ranges = array(
			'today' => __('Today','Shopp'),
			'week' => __('This Week','Shopp'),
			'month' => __('This Month','Shopp'),
			'quarter' => __('This Quarter','Shopp'),
			'year' => __('This Year','Shopp'),
			'yesterday' => __('Yesterday','Shopp'),
			'lastweek' => __('Last Week','Shopp'),
			'last30' => __('Last 30 Days','Shopp'),
			'last90' => __('Last 3 Months','Shopp'),
			'lastmonth' => __('Last Month','Shopp'),
			'lastquarter' => __('Last Quarter','Shopp'),
			'lastyear' => __('Last Year','Shopp'),
		);

		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => '',
			'range' => $_GET['shopp-stats-range']
		);
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );

		if ( ! $range || !isset($ranges[ strtolower($range) ]) ) $range = 'last30';
		$purchasetable = DatabaseObject::tablename(Purchase::$table);

		$now = current_time('timestamp');
		$offset = get_option( 'gmt_offset' ) * 3600;
		$daytimes = 86400;
		$day = date('j',$now);
		$month = date('n',$now);
		$year = date('Y',$now);
		$end = $now;

		list($weekstart,$weekend) = array_values(get_weekstartend(current_time('mysql')));
		switch ($range) {
			case 'today': $start = mktime(0,0,0,$month,$day,$year); break;
			case 'week': $start = $weekstart; $end = $weekend; break;
			case 'month': $start = mktime(0,0,0,$month,1,$year); break;
			case 'quarter': $start = mktime(0,0,0,$month-(3 - ($month % 3)),1,$year); break;
			case 'year': $start = mktime(0,0,0,1,1,$year); break;
			case 'yesterday': $start = mktime(0,0,0,$month,$day-1,$year); $end = mktime(23,59,59,$month,$day-1,$year); break;
			case 'lastweek': $start = $weekstart-(7*$daytimes); $end = $weekstart-1; break;
			case 'last7': $start = $now - (7 * $daytimes); break;
			case 'last30': $start = $now - (30 * $daytimes); break;
			case 'last90': $start = $now - (90 * $daytimes); break;
			case 'lastmonth': $start = mktime(0,0,0,$month-1,1,$year); $end = mktime(0,0,0,$month,0,$year); break;
			case 'lastquarter': $start = mktime(0,0,0,($month-(3 - ($month % 3)))-3,1,$year); $end = mktime(23,59,59,date('n',$start)+3,0,$year); break;
			case 'lastyear': $start = mktime(0,0,0,$month,1,$year-1); $end = mktime(23,59,59,1,0,$year); break;
		}

		// Include authorizations, captures and old 1.1 tranaction status CHARGED in sales data
		$salestatus = array("'authed'","'captured'","'CHARGED'");

		$txnstatus = "txnstatus IN (".join(',',$salestatus).")";
		$daterange = "created BETWEEN '".DB::mkdatetime($start)."' AND '".DB::mkdatetime($end)."'";

		$query = "SELECT count(id) AS orders,
						SUM(total) AS sales,
						AVG(total) AS average,
		 				SUM(IF($daterange,1,0)) AS wkorders,
						SUM(IF($daterange,total,0)) AS wksales,
						AVG(IF($daterange,total,null)) AS wkavg
 					FROM $purchasetable WHERE $txnstatus";

		$results = DB::query($query);

		$RecentBestsellers = new BestsellerProducts(array('range' => array($start,$end),'show'=>5));
		$RecentBestsellers->load(array('pagination'=>false));
		$RecentBestsellers->maxsold = 0;
		foreach ($RecentBestsellers as $product) $RecentBestsellers->maxsold = max($RecentBestsellers->maxsold,$product->sold);

		$LifeBestsellers = new BestsellerProducts(array('show'=>5));
		$LifeBestsellers->load(array('pagination'=>false));
		$LifeBestsellers->maxsold = 0;
		foreach ($LifeBestsellers as $product) $LifeBestsellers->maxsold = max($LifeBestsellers->maxsold,$product->sold);

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		$orderscreen = add_query_arg('page',$this->pagename('orders'),admin_url('admin.php'));
		$productscreen = add_query_arg(array('page'=>$this->pagename('products')),admin_url('admin.php'));

		?>
		<div class="table"><table>
		<tr><th colspan="2"><form action="<?php echo admin_url('index.php'); ?>">
			<select name="shopp-stats-range" id="shopp-stats-range">
				<?php echo menuoptions($ranges,$range,true); ?>
			</select>
			<button type="submit" id="filter-button" name="filter" value="order" class="button-secondary hide-if-js"><?php _e('Filter','Shopp'); ?></button>
		</form>
		</th><th colspan="2"><?php _e('Lifetime','Shopp'); ?></th></tr>

		<tbody>
		<tr><td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo (int)$results->wkorders; ?></a></td><td class="label"><?php echo _n('Order', 'Orders', (int)$results->wkorders, 'Shopp'); ?></td>
		<td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo (int)$results->orders; ?></a></td><td class="label"><?php echo _n('Order', 'Orders', (int)$results->orders, 'Shopp'); ?></td></tr>

		<tr><td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->wksales); ?></a></td><td class="label"><?php _e('Sales','Shopp'); ?></td>
		<td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->sales); ?></a></td><td class="label"><?php _e('Sales','Shopp'); ?></td></tr>

		<tr><td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->wkavg); ?></a></td><td class="label"><?php _e('Average Order','Shopp'); ?></td>
		<td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->average); ?></a></td><td class="label"><?php _e('Average Order','Shopp'); ?></td></tr>

		<?php if (!empty($RecentBestsellers->products) || !empty($LifeBestsellers->products)): ?>
		<tr>
			<th colspan="2"><?php printf(__('Bestsellers %s','Shopp'),$ranges[$range]); ?></th>
			<th colspan="2"><?php printf(__('Lifetime Bestsellers','Shopp'),$ranges[$range]); ?></th>
		</tr>
		<?php
			reset($RecentBestsellers);
			reset($LifeBestsellers);
			$firstrun = true;
			while (true):
				list($recentid,$recent) = each($RecentBestsellers->products);
				list($lifetimeid,$lifetime) = each($LifeBestsellers->products);
				if (!$recent && !$lifetime) break;
			?>
			<tr>
				<?php if (empty($RecentBestsellers->products) && $firstrun) echo '<td colspan="2" rowspan="5">'.__('None','Shopp').'</td>'; ?>
				<?php if (!empty($recent->id)): ?>
				<td class="salesgraph">
					<div class="bar" style="width:<?php echo ($recent->sold/$RecentBestsellers->maxsold)*100; ?>%;"><?php echo $recent->sold; ?></div>
				</td>
				<td>
				<a href="<?php echo esc_url(add_query_arg('view','bestselling',$productscreen)); ?>"><?php echo esc_html($recent->name); ?></a>
				</td>
				<?php endif; ?>
				<?php if (empty($LifeBestsellers->products) && $firstrun) echo '<td colspan="2" rowspan="5">'.__('None','Shopp').'</td>'; ?>
				<?php if (!empty($lifetime->id)): ?>
				<td class="salesgraph">
					<div class="bar" style="width:<?php echo ($lifetime->sold/$LifeBestsellers->maxsold)*100; ?>%;"><?php echo $lifetime->sold; ?></div>
				</td>
				<td>
				<a href="<?php echo esc_url(add_query_arg('view','bestselling',$productscreen)); ?>"><?php echo esc_html($lifetime->name); ?></a>
				</td>
				<?php endif; ?>
			</tr>
		<?php $firstrun = false; endwhile; ?>
		<?php endif; ?>
		</tbody></table></div>
		<script type="text/javascript">
		jQuery(document).ready( function($) {
			$('#shopp-stats-range').change(function () {
				$(this).parents('form').submit();
			});
		});
		</script>
		<?php
		echo $after_widget;

	}

	/**
	 * Renders the recent orders dashboard widget
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function orders_widget ($args=null) {
		$Shopp = Shopp::object();
		$db = DB::get();
		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );
		$statusLabels = shopp_setting('order_status');

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		$purchasetable = DatabaseObject::tablename(Purchase::$table);
		$purchasedtable = DatabaseObject::tablename(Purchased::$table);

		$Orders = $db->query("SELECT p.*,count(i.id) as items FROM $purchasetable AS p LEFT JOIN $purchasedtable AS i ON i.purchase=p.id GROUP BY i.purchase ORDER BY created DESC LIMIT 6",'array');

		if (!empty($Orders)) {
		echo '<table class="widefat">';
		echo '<tr><th scope="col">'.__('Name','Shopp').'</th><th scope="col">'.__('Date','Shopp').'</th><th scope="col" class="num">'.__('Items','Shopp').'</th><th scope="col" class="num">'.__('Total','Shopp').'</th><th scope="col" class="num">'.__('Status','Shopp').'</th></tr>';
		echo '<tbody id="orders" class="list orders">';
		$even = false;
		foreach ($Orders as $Order) {
			echo '<tr'.((!$even)?' class="alternate"':'').'>';
			$even = !$even;
			echo '<td><a class="row-title" href="'.add_query_arg(array('page'=>$this->pagename('orders'),'id'=>$Order->id),admin_url('admin.php')).'" title="View &quot;Order '.$Order->id.'&quot;">'.((empty($Order->firstname) && empty($Order->lastname))?'(no contact name)':$Order->firstname.' '.$Order->lastname).'</a></td>';
			echo '<td>'.date("Y/m/d",mktimestamp($Order->created)).'</td>';
			echo '<td class="num">'.$Order->items.'</td>';
			echo '<td class="num">'.money($Order->total).'</td>';
			echo '<td class="num">'.$statusLabels[$Order->status].'</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		} else {
			echo '<p>'.__('No orders, yet.','Shopp').'</p>';
		}

		echo $after_widget;

	}

	/**
	 * Renders the bestselling products dashboard widget
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	function inventory_widget ($args=null) {

		$warnings = array(
			'none' => __('OK','Shopp'),
			'warning' => __('warning','Shopp'),
			'critical' => __('critical','Shopp'),
			'backorder' => __('backorder','Shopp')
		);

		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);

		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );

		$pt = DatabaseObject::tablename(Price::$table);
		$setting = ( shopp_setting('lowstock_level') );

		$where = array();

		$where[] = "pt.stock < pt.stocked AND pt.stock/pt.stocked < $setting";
		$where[] = "(pt.context='product' OR pt.context='variation') AND pt.type != 'N/A'";

		$loading = array(
			'columns' => "pt.id AS stockid,IF(pt.context='variation',CONCAT(p.post_title,': ',pt.label),p.post_title) AS post_title,pt.sku AS sku,pt.stock,pt.stocked",
			'joins' => array($pt => "LEFT JOIN $pt AS pt ON p.ID=pt.product"),
			'where' => $where,
			'groupby' => 'pt.id',
			'orderby' => '(pt.stock/pt.stocked) ASC',
			'published' => false,
			'pagination' => false,
			'limit' => 25
		);

		$Collection = new ProductCollection();
		$Collection->load($loading);

		$productscreen = add_query_arg(array('page'=>$this->pagename('products')),admin_url('admin.php'));

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		?>
		<table><tbody>
		<?php foreach ($Collection->products as $product): $product->lowstock($product->stock,$product->stocked); ?>
		<tr>
			<td class="amount"><?php echo $product->stock; ?></td>
			<td><span class="stock lowstock <?php echo $product->lowstock; ?>"><?php echo $warnings[ $product->lowstock ]; ?></span></td>
			<td><a href="<?php echo esc_url(add_query_arg('id',$product->id,$productscreen)); ?>"><?php echo $product->name; ?></a></td>
			<td><a href="<?php echo esc_url(add_query_arg('view','inventory',$productscreen)); ?>"><?php echo $product->sku; ?></a></td>
		</tr>
		<?php endforeach; ?>
		</tbody></table>

		<?php
		echo $after_widget;

	}

	/**
	 * Update the stored path to the activated theme
	 *
	 * Automatically updates the Shopp theme path setting when the
	 * a new theme is activated.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function themepath () {
		shopp_set_setting('theme_templates',addslashes(sanitize_path(STYLESHEETPATH.'/'."shopp")));
	}

	/**
	 * Report the current status of Shopp support
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	function support ( $status = true ) {
		if ( ! ShoppSupport::activated() ) return false;
		return $status;
	}

	/**
	 * Helper callback filter to identify editor-related pages in the pages list
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $pagename The full page reference name
	 * @return boolean True if the page is identified as an editor-related page
	 **/
	function get_editor_pages ($pagenames) {
		$filter = '-edit';
		if (substr($pagenames,strlen($filter)*-1) == $filter) return true;
		else return false;
	}

	/**
	 * Helper callback filter to identify settings pages in the pages list
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $pagename The page's full reference name
	 * @return boolean True if the page is identified as a settings page
	 **/
	function get_settings_pages ($pagenames) {
		$filter = '-settings';
		if (strpos($pagenames,$filter) !== false) return true;
		else return false;
	}

	/**
	 * Initializes the Shopp TinyMCE plugin
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void Description...
	 **/
	function tinymce () {
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) return;

		$len = strlen(ABSPATH); $p = '';
		for($i = 0; $i < $len; $i++) $p .= 'x'.dechex(ord(substr(ABSPATH,$i,1))+$len);

		// Add TinyMCE buttons when using rich editor
		if ('true' == get_user_option('rich_editing')) {
			global $pagenow,$plugin_page;
			$pages = array('post.php', 'post-new.php', 'page.php', 'page-new.php');
			$editors = array('shopp-products','shopp-categories');
			if(!(in_array($pagenow, $pages) || (in_array($plugin_page, $editors) && !empty($_GET['id']))))
				return false;

			wp_enqueue_script('shopp-tinymce',admin_url('admin-ajax.php').'?action=shopp_tinymce',array());
			wp_localize_script('shopp-tinymce', 'ShoppDialog', array(
				'title' => __('Insert Product Category or Product', 'Shopp'),
				'desc' => __('Insert a product or category from Shopp...', 'Shopp'),
				'p' => $p
			));

			add_filter('mce_external_plugins', array($this,'mceplugin'),5);
			add_filter('mce_buttons', array($this,'mcebutton'),5);
		}
	}

	/**
	 * Adds the Shopp TinyMCE plugin to the list of loaded plugins
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $plugins The current list of plugins to load
	 * @return array The updated list of plugins to laod
	 **/
	function mceplugin ($plugins) {
		// Add a changing query string to keep the TinyMCE plugin from being cached & breaking TinyMCE in Safari/Chrome
		$plugins['Shopp'] = SHOPP_ADMIN_URI.'/behaviors/tinymce/tinyshopp.js?ver='.time();
		return $plugins;
	}

	/**
	 * Adds the Shopp button to the TinyMCE editor
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array $buttons The current list of buttons in the editor
	 * @return array The updated list of buttons in the editor
	 **/
	function mcebutton ($buttons) {
		array_push($buttons, "|", "Shopp");
		return $buttons;
	}

	/**
	 * Handle auto-updates from Shopp 1.0
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function legacyupdate () {
		global $plugin_page;

		if ($plugin_page == 'shopp-settings-update'
			&& isset($_GET['updated']) && $_GET['updated'] == 'true') {
				wp_redirect(add_query_arg('page',$this->pagename('orders'),admin_url('admin.php')));
				exit();
		}
	}

	/**
	 * Suppress the standard WordPress plugin update message for the Shopp listing on the plugin page
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function pluginspage () {
		remove_action('after_plugin_row_'.SHOPP_PLUGINFILE,'wp_plugin_update_row');
	}

	function navmenus () {
		if (isset($_REQUEST['add-shopp-menu-item']) && isset($_REQUEST['menu-item'])) {
			// $pages = Storefront::pages_settings();

			$nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;

			foreach ((array)$_REQUEST['menu-item'] as $key => $item) {
				if (!isset($item['menu-item-shopp-page'])) continue;

				$requested = $item['menu-item-shopp-page'];

				$Page = ShoppPages()->get($requested);

				$menuitem = &$_REQUEST['menu-item'][$key];
				$menuitem['menu-item-db-id'] = 0;
				$menuitem['menu-item-object-id'] = $requested;
				$menuitem['menu-item-object'] = $requested;
				$menuitem['menu-item-type'] = ShoppPages::QUERYVAR;
				$menuitem['menu-item-title'] = $Page->title();
			}

		}
		add_meta_box( 'add-shopp-pages', __('Catalog Pages'), array($this,'shoppage_meta_box'), 'nav-menus', 'side', 'low' );
		add_meta_box( 'add-shopp-collections', __('Catalog Collections'), array($this,'shopp_collections_meta_box'), 'nav-menus', 'side', 'low' );
	}

	function navmenu_setup ($menuitem) {
		if ('shopp_page' == $menuitem->type) {
			$menuitem->type_label = 'Shopp';
		}
		if ('shopp_collection' == $menuitem->type) {
			$menuitem->type_label = 'Collection';
		}
		return $menuitem;
	}

	function shopp_collections_meta_box () {
		global $Shopp,$_nav_menu_placeholder, $nav_menu_selected_id;

		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		?>
		<br />
		<div class="shopp-collections-menu-item customlinkdiv" id="shopp-collections-menu-item">
			<div id="tabs-panel-shopp-collections" class="tabs-panel tabs-panel-active">

				<ul class="categorychecklist form-no-clear">

				<?php
					$collections = $Shopp->Collections;
					foreach ($collections as $slug => $CollectionClass):
						$menu = get_class_property($CollectionClass,'_menu');
						if ( ! $menu ) continue;
						$Collection = new $CollectionClass();
						$Collection->smart();
						$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
				?>
					<li>
						<label class="menu-item-title">
						<input type="checkbox" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-shopp-collection]" value="<?php echo $slug; ?>" class="menu-item-checkbox" /> <?php
							echo esc_html( $Collection->name );
						?></label>
						<input type="hidden" class="menu-item-db-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-db-id]" value="0" />
						<input type="hidden" class="menu-item-object-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="<?php echo $slug; ?>" />
						<input type="hidden" class="menu-item-object" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object]" value="<?php echo $slug; ?>" />
						<input type="hidden" class="menu-item-parent-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-parent-id]" value="0">
						<input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="<?php echo SmartCollection::$taxon; ?>" />
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php echo $Collection->name; ?>" />

					</li>
				<?php endforeach; ?>
				<?php
					// Promo Collections
					$select = DB::select(array(
						'table' => DatabaseObject::tablename(Promotion::$table),
						'columns' => 'SQL_CALC_FOUND_ROWS id,name',
						'where' => array("target='Catalog'","status='enabled'"),
						'orderby' => 'created DESC'
					));

					$Promotions = DB::query($select,'array');
					foreach ($Promotions as $promo):
						$slug = sanitize_title_with_dashes($promo->name);
				?>
					<li>
						<label class="menu-item-title">
						<input type="checkbox" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-shopp-collection]" value="<?php echo $slug; ?>" class="menu-item-checkbox" /> <?php
							echo esc_html( $promo->name );
						?></label>
						<input type="hidden" class="menu-item-db-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-db-id]" value="0" />
						<input type="hidden" class="menu-item-object-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="<?php echo $slug; ?>" />
						<input type="hidden" class="menu-item-object" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object]" value="<?php echo $slug; ?>" />
						<input type="hidden" class="menu-item-parent-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-parent-id]" value="0">
						<input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="<?php echo SmartCollection::$taxon; ?>" />
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php echo $promo->name; ?>" />

					</li>
				<?php endforeach; ?>
				</ul>

			</div>

			<p class="button-controls">
				<span class="list-controls">
					<a href="<?php
						echo esc_url(add_query_arg(
							array(
								'shopp-pages-menu-item' => 'all',
								'selectall' => 1,
							),
							remove_query_arg($removed_args)
						));
					?>#shopp-collections-menu-item" class="select-all"><?php _e('Select All'); ?></a>
				</span>

				<span class="add-to-menu">
					<span class="spinner"></span>
					<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-shopp-menu-item" id="submit-shopp-collections-menu-item" />
				</span>
			</p>

		</div><!-- /.customlinkdiv -->
		<?php

	}

	function shoppage_meta_box () {
		global $_nav_menu_placeholder, $nav_menu_selected_id;

		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		?>
		<br />
		<div class="shopp-pages-menu-item customlinkdiv" id="shopp-pages-menu-item">
			<div id="tabs-panel-shopp-pages" class="tabs-panel tabs-panel-active">

				<ul class="categorychecklist form-no-clear">

				<?php
					foreach (ShoppPages() as $name => $Page):
						$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
				?>
					<li>
						<label class="menu-item-title">
						<input type="checkbox" name="menu-item[<?php esc_html_e( $_nav_menu_placeholder ) ?>][menu-item-shopp-page]" value="<?php esc_attr_e( $pagetype ) ?>" class="menu-item-checkbox" /> <?php
							echo esc_html( $Page->title() );
						?></label>
						<input type="hidden" class="menu-item-db-id" name="menu-item[<?php esc_attr_e( $_nav_menu_placeholder ) ?>][menu-item-db-id]" value="0" />
						<input type="hidden" class="menu-item-object-id" name="menu-item[<?php esc_attr_e( $_nav_menu_placeholder ) ?>][menu-item-object-id]" value="<?php esc_attr_e( $name ) ?>" />
						<input type="hidden" class="menu-item-object" name="menu-item[<?php esc_attr_e( $_nav_menu_placeholder ) ?>][menu-item-object]" value="<?php esc_attr_e( $name ) ?>" />
						<input type="hidden" class="menu-item-parent-id" name="menu-item[<?php esc_attr_e( $_nav_menu_placeholder ) ?>][menu-item-parent-id]" value="0">
						<input type="hidden" class="menu-item-type" name="menu-item[<?php esc_attr_e( $_nav_menu_placeholder ) ?>][menu-item-type]" value="<?php esc_attr_e( ShoppPages::QUERYVAR ) ?>" />
						<input type="hidden" class="menu-item-title" name="menu-item[<?php esc_attr_e( $_nav_menu_placeholder ) ?>][menu-item-title]" value="<?php esc_attr_e( $Page->title() ) ?>" />

					</li>
				<?php endforeach; ?>
				</ul>

			</div>

			<p class="button-controls">
				<span class="list-controls">
					<a href="<?php
						echo esc_url(add_query_arg(
							array(
								'shopp-pages-menu-item' => 'all',
								'selectall' => 1,
							),
							remove_query_arg($removed_args)
						));
					?>#shopp-pages-menu-item" class="select-all"><?php _e('Select All'); ?></a>
				</span>

				<span class="add-to-menu">
					<span class="spinner"></span>
					<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-shopp-menu-item" id="submit-shopp-pages-menu-item" />
				</span>
			</p>

		</div><!-- /.customlinkdiv -->
		<?php

	}

	static function screen () {
		return get_current_screen()->id;
	}


} // END class ShoppAdmin

/**
 * ShoppAdminPage class
 *
 * A property container for Shopp's admin page meta
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package admin
 **/
class ShoppAdminPage {

	public $label = "";
	public $controller = "";
	public $doc = false;
	public $parent = false;

	function __construct ($name,$page,$label,$controller,$doc=false,$parent=false) {
		$this->name = $name;
		$this->page = $page;
		$this->label = $label;
		$this->controller = $controller;
		$this->doc = $doc;
		$this->parent = $parent;
	}

	function hook () {
		global $admin_page_hooks;
		if (isset($admin_page_hooks[$this->parent])) return $admin_page_hooks[$this->parent];
		return 'shopp';
	}

} // END class ShoppAdminPage


class ShoppUI {

	static function button ($type,$name,$options=array()) {
		$types = array(
			'add' => array('class' => 'add','imgalt' => '+', 'imgsrc' => '/add.png'),
			'delete' => array('class' => 'delete','imgalt' => '-','imgsrc' => '/delete.png')
		);
		if (isset($types[$type]))
			$options = array_merge($types[$type],$options);

		return '<button type="submit" name="'.$name.'"'.inputattrs($options).'><img src="'.SHOPP_ICONS_URI.$options['imgsrc'].'" alt="'.$options['imgalt'].'" width="16" height="16" /></button>';
	}

	static function template ($ui,$data=array()) {
		$ui = str_replace(array_keys($data),$data,$ui);
		return preg_replace('/\${[-\w]+}/','',$ui);
	}


	/**
	 * Register column headers for a particular screen.
	 *
	 * Compatibility function for Shopp list table views
	 *
	 * @since 1.2
	 *
	 * @param string $screen The handle for the screen to add help to. This is usually the hook name returned by the add_*_page() functions.
	 * @param array $columns An array of columns with column IDs as the keys and translated column names as the values
	 * @see get_column_headers(), print_column_headers(), get_hidden_columns()
	 */
	static function register_column_headers($screen, $columns) {
		$wp_list_table = new ShoppAdminListTable($screen, $columns);
	}

	/**
	 * Prints column headers for a particular screen.
	 *
	 * @since 1.2
	 */
	static function print_column_headers($screen, $id = true) {
		$wp_list_table = new ShoppAdminListTable($screen);

		$wp_list_table->print_column_headers($id);
	}

	static function table_set_pagination ($screen, $total_items, $total_pages, $per_page ) {
		$wp_list_table = new ShoppAdminListTable($screen);

		$wp_list_table->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );

		return $wp_list_table;
	}


} // END class ShoppUI


class ShoppAdminListTable extends WP_List_Table {
	public $_screen;
	public $_columns;
	public $_sortable;

	function __construct ( $screen, $columns = array()) {
		if ( is_string( $screen ) )
			$screen = convert_to_screen( $screen );

		$this->_screen = $screen;

		if ( !empty( $columns ) ) {
			$this->_columns = $columns;
			add_filter( 'manage_' . $screen->id . '_columns', array( &$this, 'get_columns' ), 0 );
		}

	}

	function get_column_info() {
		$columns = get_column_headers( $this->_screen );
		$hidden = get_hidden_columns( $this->_screen );
		$screen = get_current_screen();

		$_sortable = apply_filters( "manage_{$screen->id}_sortable_columns", $this->get_sortable_columns() );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}


		return array( $columns, $hidden, $sortable );
	}

	function get_columns() {
		return $this->_columns;
	}

	function get_sortable_columns () {
		$screen = get_current_screen();
		$sortables = array(
			'toplevel_page_shopp-products' => array(
				'name'=>'name',
				'price'=>'price',
				'sold'=>'sold',
				'gross'=>'gross',
				'inventory'=>'inventory',
				'sku'=>'sku',
				'date'=>array('date',true)
			)
		);
		if (isset($sortables[ $screen->id ])) return $sortables[ $screen->id ];

		return array();
	}

}

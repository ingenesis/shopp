<?php
/**
 * ShoppStorefront
 *
 * Flow controller for the front-end shopping interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January 12, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage storefront
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppStorefront
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage storefront
 **/
class ShoppStorefront extends ShoppFlowController {

	public $behaviors = array();	// Runtime JavaScript behaviors
	public $searching = false;		// Flags if a search request has been made
	public $Requested = false;		// Property for tracking the originally requested content

	public $shortcoded = array();	// WordPress post IDs that have already been shortcoded
	public $account = false;		// Account dashboard requests
	public $dashboard = array();	// Registry of account dashboard pages
	public $menus = array();		// Account dashboard menu registry

	// Session properties
	public $search = false;			// The search query string
	public $browsing = array();		// Browsing session settings (sortorder, current page slug)
	public $referrer = false;		// The referring page
	public $viewed = array();		// List of recent products viewed by customer

	static $template = '';			// Content template context

	public function __construct () {

		parent::__construct();

		ShoppCatalog();

		Shopping::restore( 'search',	$this->search );
		Shopping::restore( 'browsing',	$this->browsing );
		Shopping::restore( 'referrer',	$this->referrer );
		Shopping::restore( 'viewed',	$this->viewed );

		// Setup WP_Query overrides
		add_action( 'parse_query',		array($this, 'query') );
		add_filter( 'posts_request',	array($this, 'noquery'), 10, 2 );
		add_filter( 'posts_request',	array($this, 'onfront'), 10, 2 );
		add_filter( 'posts_results',	array($this, 'found'), 10, 2);
		add_filter( 'the_posts', 		array($this, 'posts'), 10, 2);

		add_action( 'wp', array($this, 'loaded') );
		add_action( 'wp', array($this, 'security') );
		add_action( 'wp', array($this, 'trackurl') );
		add_action( 'wp', array($this, 'viewed') );
		add_action( 'wp', array($this, 'cart') );
		add_action( 'wp', array($this, 'dashboard') );
		add_action( 'wp', array($this, 'shortcodes') );
		add_action( 'wp', array($this, 'behaviors') );

		add_filter( 'wp_get_nav_menu_items', array($this,'menulinks'), 10, 2 );
		add_filter( 'wp_list_pages', array($this,'securelinks') );

		// Wrap Shopp content in #shopp div  to enable CSS and Javascript
		add_filter( 'shopp_order_lookup',		array('Storefront', 'wrapper') );
		add_filter( 'shopp_order_confirmation',	array('Storefront', 'wrapper') );
		add_filter( 'shopp_errors_page',		array('Storefront', 'wrapper') );
		add_filter( 'shopp_catalog_template',	array('Storefront', 'wrapper') );
		add_filter( 'shopp_cart_template',		array('Storefront', 'wrapper') );
		add_filter( 'shopp_checkout_page',		array('Storefront', 'wrapper') );
		add_filter( 'shopp_account_template',	array('Storefront', 'wrapper') );
		add_filter( 'shopp_category_template',	array('Storefront', 'wrapper') );
		add_filter( 'shopp_order_receipt',		array('Storefront', 'wrapper') );
		add_filter( 'shopp_account_manager',	array('Storefront', 'wrapper') );
		add_filter( 'shopp_account_vieworder',	array('Storefront', 'wrapper') );
		add_filter( 'the_content',				array($this, 'autowrap'), 99 );

		add_action( 'wp_enqueue_scripts',		'shopp_dependencies' );
		add_action( 'shopp_storefront_init',	array($this, 'account') );
		add_filter( 'wp_nav_menu_objects',		array($this, 'menus') );

		// Maintenance mode overrides
		add_filter( 'search_template',		array($this, 'maintenance') );
		add_filter( 'taxonomy_template',	array($this, 'maintenance') );
		add_filter( 'page_template',		array($this, 'maintenance') );
		add_filter( 'single_template',		array($this, 'maintenance') );

		// Template rendering
		add_action( 'do_feed_rss2',			array($this, 'feed'), 1 );
		add_filter( 'search_template',		array($this, 'pages') );
		add_filter( 'page_template',		array($this, 'pages') );
		add_filter( 'archive_template',		array($this, 'pages') );
		add_filter( 'taxonomy_template',	array($this, 'collections') );
		add_filter( 'single_template',		array($this, 'single') );

	}

	/**
	 * Determines if the wp_query is for Shopp content
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return boolean True for Shopp requests, false otherwise
	 **/
	public function request ( $wp_query = false ) {
		return $wp_query && $wp_query->is_main_query() && is_shopp_query($wp_query) && ! is_shopp_product($wp_query);
	}

	/**
	 * Override the WP posts_request so the storefront controller can take over
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string|boolean The request, or false if a Shopp Storefront request
	 **/
	public function noquery ( $request, WP_Query $wp_query ) {
		if ( $this->request($wp_query) ) return false;
		return $request;
	}

	/**
	 * Sets the found count to avoid 404 pages when handling Shopp requests
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return int|boolean Number of posts found or, true if a Shopp Storefront request
	 **/
	public function found ( $found_posts, WP_Query $wp_query ) {
		if ( $this->request($wp_query) ) {
			$Page = new stdClass();
			$Page->ID = 0;
			return array( $Page ); // Short page stub to prevent PHP Notices in wp_query
		}
		return $found_posts;
	}

	/**
	 * Provide a stub to the wp_query posts property to mimic post functionality
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $posts The current list of posts
	 * @param object $wp_query The working WP_Query object
	 * @return array List of posts, or a list with the post stub for Shopp Storefront requests
	 **/
	public function posts ( $posts, WP_Query $wp_query ) {

		if ( $this->request($wp_query) ) {

			// Load the requested Storefront ShoppPage
			$Page = ShoppPages()->slugpage(ShoppPages::request());

			if ( is_shopp_collection($wp_query) ) $Page = new ShoppCollectionPage();
			elseif ( ! $Page ) $Page = new ShoppPage();

			if ( Shopp::maintenance() )
				$Page = new ShoppMaintenancePage();

			return array( $Page->poststub() );
		}

		if ( count($posts) == 1 ) { // @deprecated Legacy support to redirect old shortcode pages
			$shortcodes = join('|', ShoppPages()->names() );
			if ( preg_match("/\[($shortcodes)\]/", $posts[0]->post_content, $matches) ) {
				$shortcode = next($matches);
				if ( 'catalog' == $shortcode ) $shortcode = '';
				Shopp::redirect( Shopp::url($shortcode) );
				exit();
			}
		}

		return $posts;
	}

	/**
	 * Parse the query request and initialize Shopp content
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param object $wp_query The WP_Query object (passed via parse_query action)
	 * @return void
	 **/
	public function query ( WP_Query $wp_query ) {
		if ( ! $this->request($wp_query) ) return;

		$page	 	= $wp_query->get( ShoppPages::QUERYVAR );
		$posttype 	= $wp_query->get( 'post_type' );
		$product 	= $wp_query->get( ShoppProduct::$posttype );
		$collection = $wp_query->get( 'shopp_collection' );
		$searching 	= $wp_query->get( 's_cs' );
		$search 	= $wp_query->get( 's' );

		// Shopp requests are never automatic on the home page
		$wp_query->is_home = false;

		$catalog = ShoppPages()->baseslug();

		// Detect catalog page requests
		if ( is_archive() && $posttype == ShoppProduct::$posttype && '' == $product . $collection . $page . $search ) {
			$page = $catalog;
			$wp_query->set( ShoppPages::QUERYVAR, $page );
		}

		// Shopp request, remove noindex
		remove_action( 'wp_head', 'noindex', 1 );
		$wp_query->set( 'suppress_filters', false ); // Override default WP_Query request

		// Restore paged query var for Shopp's alpha-pagination support
		if ( isset($wp_query->query['paged']) && false != preg_match('/([A-Z]|0\-9)/i',$wp_query->query['paged']) )
			$wp_query->query_vars['paged'] = strtoupper($wp_query->query['paged']);

		// Handle Taxonomies
		if ( is_archive() ) {
			$taxonomies = get_object_taxonomies(ShoppProduct::$posttype, 'object');
			foreach ( $taxonomies as $t ) {
				if ( '' == $wp_query->get($t->query_var) ) continue;
				$taxonomy = $wp_query->get($t->query_var);
				if ( $t->hierarchical ) ShoppCollection( new ProductCategory($taxonomy, 'slug', $t->name) );
				else ShoppCollection( new ProductTag($taxonomy, 'slug', $t->name) );
				$page = false;
			}
		}

		$options = array();
		if ( $searching ) { // Catalog search
			$collection = 'search-results';
			$options = array('search' => $search);
		}

		// Handle Shopp Smart Collections
		if ( ! empty($collection) ) {
			// Overrides to enforce archive behavior
			$page = $catalog;

			// Promo Collection routing
			$promos = shopp_setting('active_catalog_promos');
			if ( isset($promos[ $collection ]) ) {
				$options['id'] = $promos[ $collection ][0];
				$collection = 'promo';
			}

			ShoppCollection( ShoppCatalog::load_collection($collection, $options) );
			if ( ! is_feed() ) ShoppCollection()->load( array( 'load' => array('coverimages') ) );

			// Provide a stub to the queried object for smart collections since WP has no parallel
			$post_archive = new stdClass();
			$post_archive->labels = new stdClass();
			$post_archive->labels->name = ShoppCollection()->name;
			$post_archive->post_title = ShoppCollection()->name; // Added so single_post_title will return the title properly
			$wp_query->queried_object = $post_archive;
			$wp_query->queried_object_id = 0;
		}

		$Collection = ShoppCollection();
		if ( ! empty($Collection) ) {
			$this->Requested = $Collection;
			add_action('wp_head', array($this, 'metadata') );
			remove_action('wp_head', 'feed_links', 2);
			add_action('wp_head', array($this, 'feedlinks'), 2);
		}

		if ( ! empty($page) ) {
			// Overrides to enforce page behavior
			$wp_query->set( ShoppPages::QUERYVAR, $page );
			$wp_query->is_page = true;
			$wp_query->is_singular = true;
			$wp_query->post_count = true;
			$wp_query->shopp_page = true;
			$wp_query->is_archive = false;
		}

	}

	/**
	 * Convert WP queried Shopp product post types to a Shopp Product
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param object $wp The main WP object from the 'wp' action
	 * @return void
	 **/
	public function loaded ( WP $wp ) {
		if ( ! is_shopp_product() ) return;

		// Get the loaded object (a Shopp product post type)
		global $wp_the_query;
		$object = $wp_the_query->get_queried_object();

		// Populate so we don't have to req-uery
		$Product = new ShoppProduct();
		$Product->populate($object);
		ShoppProduct($Product);
		$this->Requested = $Product;

	}

	/**
	 * Tracks a product as recently viewed
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param object $wp The main WP object from the 'wp' action
	 * @return void
	 **/
	public function viewed ( WP $wp ) {

		if ( ! is_shopp_product() ) return;
		if ( in_array($this->Requested->id, $this->viewed) ) return;

		array_unshift($this->viewed, $this->Requested->id);
		$this->viewed = array_slice($this->viewed, 0, apply_filters('shopp_recently_viewed_limit', 25) );

	}

	/**
	 * Track the URL as a referrer from catalog pages (collections, taxonomies, products)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param object $wp The main WP object from the 'wp' action
	 * @return void
	 **/
	public function trackurl ( WP $wp ) {

		if ( ! is_catalog_page() ) return;

		 // Track referrer for the cart referrer URL
		$referrer = get_bloginfo('url') . '/' . $wp->request;
		if ( ! empty($_GET) ) $referrer = add_query_arg($_GET, $referrer);
		$this->referrer = user_trailingslashit($referrer);

	}

	/**
	 * Render a maintenance message on the storefront when Shopp in maintenance mode
	 *
	 * Detects when maintenance is required and overrides all other storefront
	 * template output to display an overridable maintenance screen.
	 *
	 * Create a shopp-maintenanace.php template in your WordPress theme for a custom
	 * maintenance message.
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $template Template file path
	 * @return void
	 **/
	public function maintenance ( $template ) {
		// Only run if in maintenance mode
		if ( ! is_shopp_page() ) return $template;
		if ( ! Shopp::maintenance() ) return $template;

		// Remove normal Shopp Storefront template processing
		// so maintenance content takes over
		remove_filter('page_template', array($this, 'pages'));
		remove_filter('single_template', array($this, 'single'));

		// Build the page
		$Page = new ShoppMaintenancePage();
		$Page->poststub();

		// Send the template back to WordPress
		return locate_template( $Page->templates() );
	}

	public function onfront ( $request, WP_Query $wp_query ) {

		if ( ShoppCatalogPage::frontid() == get_option('page_on_front') ) {

			// @todo Detect when the catalog page is directly accessed by slug and redirect it to website root when it is set to page on front

			if ( ShoppCatalogPage::frontid() == $wp_query->get('page_id') ) {
				// Overrides to enforce page behavior
				$wp_query->set( ShoppPages::QUERYVAR, ShoppPages()->baseslug() );
				$wp_query->is_page = true;
				$wp_query->is_singular = true;
				$wp_query->post_count = true;
				$wp_query->shopp_page = true;
				$wp_query->is_archive = false;
				$request = str_replace('.ID = shopp', '.ID = NULL', $request);
			}

		}
		return $request;
	}

	/**
	 * Filters WP template handlers to render Shopp Storefront page templates
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.2.1
	 *
	 * @param string $template The template
	 * @return string The output of the templates
	 **/
	public function pages ( $template ) {
		// Catch smart collection pages
		if ( is_shopp_collection() )
			return $this->collections($template);

		// Get the requested storefront page identifier from the slug
		$page = ShoppPages::request();
		if ( empty($page) ) return $template;

		// Load the request Storefront page settings
		$Page = ShoppPages()->slugpage($page);
		if ( ! $Page ) return $template;

		if ( Shopp::maintenance() )
			$Page = new ShoppMaintenancePage();

		$Page->poststub();

		// Send the template back to WordPress
		return locate_template( $Page->templates() );
	}

	public function collections ( $template ) {
		if ( ! is_shopp_collection() ) return $template;

		$Page = new ShoppCollectionPage();

		return locate_template( $Page->templates() );
	}

	/**
	 * Filters WP template handlers to render a Shopp product page
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 * @version 1.2.1
	 *
	 * @param string $template The template
	 * @return string The output of the templates
	 **/
	public function single ($template) {
		if ( ! is_shopp_product() ) return $template;

		$Page = new ShoppProductPage();
		$Page->filters();
		return locate_template($Page->templates());
	}

	/**
	 * Handles RSS-feed requests
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function feed () {
		if ( ! is_shopp_collection()) return;
		$Collection = ShoppCollection();

	    $base = shopp_setting('base_operations');

		add_filter( 'shopp_rss_description', 'wptexturize' );
		add_filter( 'shopp_rss_description', 'convert_chars' );
		add_filter( 'shopp_rss_description', 'make_clickable', 9 );
		add_filter( 'shopp_rss_description', 'force_balance_tags', 25 );
		add_filter( 'shopp_rss_description', 'convert_smilies', 20 );
		add_filter( 'shopp_rss_description', 'wpautop', 30 );
		add_filter( 'shopp_rss_description', 'ent2ncr' );

		do_action_ref_array( 'shopp_collection_feed', array($Collection) );

		$rss = array( 'title' => trim( get_bloginfo('name') . ' ' . $Collection->name ),
			 			'link' => shopp($Collection, 'get-feed-url'),
					 	'description' => $Collection->description,
						'sitename' => get_bloginfo('name') . ' (' . get_bloginfo('url') . ')',
						'xmlns' => array( 'shopp'=>'http://shopplugin.net/xmlns',
							'g' => 'http://base.google.com/ns/1.0',
							'atom' => 'http://www.w3.org/2005/Atom',
							'content' => 'http://purl.org/rss/1.0/modules/content/')
						);
		$rss = apply_filters('shopp_rss_meta', $rss);

		$tax_inclusive = shopp_setting_enabled('tax_inclusive');

		$template = locate_shopp_template( array('feed-' . $Collection->slug . '.php', 'feed.php') );
		if ( ! $template ) $template = SHOPP_ADMIN_PATH . '/categories/feed.php';

		header('Content-type: application/rss+xml; charset=' . get_option('blog_charset') );
		include($template);
		exit();
	}

	/**
	 * Renders RSS feed link tags for category product feeds
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function feedlinks () {
		if ( empty( ShoppCollection()->name ) ) return;
		$title = apply_filters('shopp_collection_feed_title', sprintf('%s %s %s', get_bloginfo('name'), ShoppCollection()->name, __('Feed','Shopp')) );
		echo '<link rel="alternate" type="' . feed_content_type('rss') . '" title="' . esc_attr($title) . '" href="' . esc_attr(shopp('collection.get-feed-url')) . '" />' . "\n";
	}

	/**
	 * Forces SSL on pages when required by gateways that handle sensitive payment details onsite
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 * @version 1.2
	 *
	 * @return void
	 **/
	public function security () {

		$Shopp = Shopp::object();
	    if ( SHOPP_NOSSL || ! $Shopp->Gateways->secure || is_ssl() ) return;

		$redirect = false;
		if ( is_checkout_page() )	$redirect = 'checkout';
		if ( is_confirm_page() )	$redirect = 'confirm';
		if ( is_account_page() )	$redirect = 'account';

		if ( $redirect )
			Shopp::redirect( Shopp::url($_GET, $redirect, true) );

	}

	/**
	 * Adds nocache headers on sensitive account pages
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function account () {
		$request = get_query_var('acct');
		if ( ! empty($request) ) add_filter('wp_headers', array($this, 'nocache') );
	}

	/**
	 * Adds nocache headers to WP page headers
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $headers The current WP HTTP headers
	 * @return array Modified headers
	 **/
	public function nocache ( array $headers ) {
		$headers = array_merge( $headers, wp_get_nocache_headers() );
		return $headers;
	}

	/**
	 * Queues Shopp storefront javascript and styles as needed
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function behaviors () {
		$Shopp = Shopp::object();

		if ( is_ssl() ) {
			$urls = array('option_siteurl', 'option_home', 'option_url', 'option_wpurl', 'option_stylesheet_url', 'option_template_url', 'script_loader_src');
			foreach ( $urls as $filter ) add_filter($filter, 'force_ssl');
		}

		// Replace the WordPress canonical link
		remove_action( 'wp_head', 'rel_canonical' );

		add_action( 'wp_head', array($this, 'header') );
		add_action( 'wp_footer', array($this, 'footer') );
		wp_enqueue_style( 'shopp.catalog', SHOPP_ADMIN_URI.'/styles/catalog.css', array(), 20110511, 'screen' );
		wp_enqueue_style( 'shopp.icons', SHOPP_ADMIN_URI.'/styles/icons.css', array(), 20110511, 'screen' );
		wp_enqueue_style( 'shopp', Shopp::template_url('shopp.css'), array(), 20110511, 'screen' );
		wp_enqueue_style( 'shopp.colorbox', SHOPP_ADMIN_URI.'/styles/colorbox.css', array(), 20110511, 'screen' );

		$orderhistory = ( is_account_page() && isset($_GET['id']) && ! empty($_GET['id']) );

		if ( is_thanks_page() || $orderhistory )
			wp_enqueue_style('shopp.printable', SHOPP_ADMIN_URI . '/styles/printable.css', array(), 20110511, 'print' );

		$loading = shopp_setting('script_loading');

		if ( ! $loading || 'global' == $loading || ! empty($page) ) {
			shopp_enqueue_script('colorbox');
			shopp_enqueue_script('shopp');
			shopp_enqueue_script('catalog');
			shopp_enqueue_script('cart');
			if ( is_catalog_page() )
				shopp_custom_script('catalog', "var pricetags = {};\n" );
		}

		if ( is_checkout_page() ) {
			shopp_enqueue_script('address');
			shopp_enqueue_script('checkout');
		}

		if ( is_confirm_page() ) {
			shopp_enqueue_script('checkout');
		}

		if ( is_account_page() ) {
			shopp_enqueue_script('address');
			$regions = Lookup::country_zones();
			$js = 'var regions=' . json_encode($regions);
			add_storefrontjs($js, true);
		}

	}

	/**
	 * Adds 'keyword' and 'description' <meta> tags into the page markup
	 *
	 * The 'keyword' tag is a list of tags applied to a product.  No default 'keyword' meta
	 * is generated for categories, however, the 'shopp_meta_keywords' filter hook can be
	 * used to generate a custom list.
	 *
	 * The 'description' tag is generated from the product summary or category description.
	 * It can also be customized with the 'shopp_meta_description' filter hook.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function metadata () {
		$keywords = false;
		$description = false;
		if ( ! empty(ShoppProduct()->id) ) {
			if ( empty(ShoppProduct()->tags) ) ShoppProduct()->load_data( array('tags') );
			foreach( ShoppProduct()->tags as $tag )
				$keywords .= ( ! empty($keywords) ) ? ", {$tag->name}" : $tag->name;
			$description = ShoppProduct()->summary;
		} elseif ( isset(ShoppCollection()->description) ) {
			$description = ShoppCollection()->description;
		}
		$keywords = esc_attr( apply_filters('shopp_meta_keywords', $keywords) );
		$description = esc_attr( apply_filters('shopp_meta_description', $description) );
		?>
		<?php if ( $keywords ): ?><meta name="keywords" content="<?php echo $keywords; ?>" />
		<?php endif; ?>
		<?php if ( $description ): ?><meta name="description" content="<?php echo $description; ?>" />
		<?php endif;
	}

	/**
	 * Returns canonical product and category URLs
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $url The current url
	 * @return string The canonical url
	 **/
	public function canonurls ($url) {
		// Product catalog archive (landing) page URL
		if ( is_post_type_archive() && is_shopp_page('catalog') )
			return shopp('catalog.get-url');

		// Specific product/category URLs
		if ( ! empty($Shopp->Product->slug) ) return shopp('product.get-url');
		if ( ! empty($Shopp->Category->slug) ) {
			$paged = (int)get_query_var('paged');
			$url = shopp('category.get-url');
			if ( $paged > 1 ) $url = shopp('category.get-url',"page=$paged");
		}
		return $url;
	}

	/**
	 * Includes a canonical reference <link> tag for the catalog page
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function header () {
		$canonurl = $this->canonurls(false);
		// Add canonical URLs
		if ( is_shopp_page('catalog') && ! empty($canonurl) )
			echo '<link rel="canonical" href="' . apply_filters('shopp_canonical_link', $canonurl) . '" />';

		// Add noindex for cart, checkout, account pages
		if ( is_shopp_page('cart') || is_shopp_page('checkout') || is_shopp_page('account') )
			noindex();
	}

	/**
	 * Renders footer content and extra scripting as needed
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function footer () {

		$globals = false;
		if ( isset($this->behaviors['global']) ) {
			$globals = $this->behaviors['global'];
			unset($this->behaviors['global']);
		}

		$script = '';
		if ( ! empty($globals) ) $script .= "\t" . join("\n\t", $globals) . "\n";
		if ( ! empty($this->behaviors) ) {
			$script .= 'jQuery(window).ready(function($){' . "\n";
			$script .= "\t".join("\t\n",$this->behaviors) . "\n";
			$script .= '});' . "\n";
		}
		shopp_custom_script('shopp', $script);
	}

	/**
	 * Manages CSS relationship classes applied to Shopp elements appearing in a WordPress navigation menu
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $menuitems The provided WordPress menu items
	 * @return array Updated menu items with proper relationship classes
	 **/
	public function menus ($menuitems) {

		$is_shopp_page = is_shopp_page();
		$keymap = array();
		$parents = array();
		foreach ( $menuitems as $key => $item ) {
			$page = false;
			// Remove the faulty wp_page_menu (deprecated) class for Shopp pages
			if ( $is_shopp_page && in_array('current_page_parent', $item->classes) )
				unset($item->classes[ array_search('current_page_parent', $item->classes) ]);

			// Otherwise, skip dealing with any non-Shopp page
			if ( ShoppPages::QUERYVAR == $item->type ) {

				// Determine the queried Shopp page object name
				$Page = ShoppPages()->requested();
				if ( $Page && ! is_shopp_collection() ) $page = $Page->name();

				// Set the catalog as current page parent
				if ( 'catalog' == $item->object && ( $is_shopp_page || is_shopp_product() ) )
					$item->classes[] = 'current-page-parent';

				$keymap[$item->db_id] = $key;
			}

			if ( 'shopp_collection' == $item->type ) {
				$page = get_query_var($item->type);
				$keymap[$item->db_id] = $key;
			}

			if ( $page == $item->object ) {
				$item->classes[] = 'current-page-item';
				$item->classes[] = 'current-menu-item';
				$parents[] = $item->menu_item_parent;
			}

		}

		foreach ( (array)$parents as $parentid ) {
			if ( ! isset($keymap[ $parentid ]) ) continue;
			$parent = $menuitems[ $keymap[ $parentid ] ];
			$parent->classes[] = 'current-menu-parent';
			$parent->classes[] = 'current-page-parent';
			$parent->classes[] = 'current-menu-ancestor';
			$parent->classes[] = 'current-page-ancestor';

			$ancestor = $parent;
			while( 0 != $ancestor->menu_item_parent ) {
				$ancestor = $menuitems[ $keymap[ $ancestor->menu_item_parent ] ];
				$ancestor->classes[] = 'current-menu-ancestor';
				$ancestor->classes[] = 'current-page-ancestor';
			}
		}

		return $menuitems;
	}

	/**
	 * Overrides the URL properties for Shopp storefront pages and collections added to the WordPress Menu system
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $items Menu items from WordPress
	 * @return array Shopp-enabled menu items
	 **/
	public function menulinks ( array $items ) {
		foreach ( $items as &$item) {
			switch ( strtolower($item->type) ) {
				case ShoppPages::QUERYVAR: $item->url = Shopp::url(false,$item->object); break;
				case SmartCollection::$taxon:
					$namespace = get_class_property( 'SmartCollection' ,'namespace');
					$taxonomy = get_class_property( 'SmartCollection' ,'taxon');
					$prettyurls = ( '' != get_option('permalink_structure') );
					$item->url = Shopp::url( $prettyurls ? "$namespace/$item->object" : array($taxonomy=>$item->object), false );
					break;
			}
		}
		return $items;
	}

	/**
	 * Filters the WP page list transforming unsecured URLs to secure URLs
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function securelinks ( $items ) {
		$Shopp = Shopp::object();
		if ( ! $Shopp->Gateways->secure ) return $items;

		$hrefs = array(
			'checkout' => Shopp::url(false,'checkout'),
			'account' => Shopp::url(false,'account')
		);

		if ( empty($Shopp->Gateways->active) )
			return str_replace($hrefs['checkout'],Shopp::url(false,'cart'),$items);

		foreach ($hrefs as $href) {
			$secure_href = str_replace('http://','https://',$href);
			$items = str_replace($href,$secure_href,$items);
		}
		return $items;
	}

	/**
	 * Handles shopping cart requests
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function cart () {

		if ( isset($_REQUEST['shopping']) && 'reset' == strtolower($_REQUEST['shopping']) ) {
			ShoppShopping()->reset();
			Shopp::redirect( Shopp::url() );
		}

		if ( empty($_REQUEST['cart']) ) return true;

		do_action('shopp_cart_request');

		if ( isset($_REQUEST['checkout']) ) Shopp::redirect( Shopp::url(false, 'checkout', $this->security()) );

		if ( isset($_REQUEST['ajax']) ) {
			$Cart = ShoppOrder()->Cart;
			$Cart->totals();
			$Cart->ajax();
		}

		$redirect = false;
		if ( isset($_REQUEST['redirect']) ) $redirect = $_REQUEST['redirect'];
		switch ($redirect) {
			case 'checkout': Shopp::redirect( Shopp::url(false, $redirect, ShoppOrder()->security()) ); break;
			default:
				if ( ! empty($_REQUEST['redirect']) )
					Shopp::safe_redirect($_REQUEST['redirect']);
				else Shopp::redirect( Shopp::url(false, 'cart') );
		}

		exit;
	}

	/**
	 * Setup and process account dashboard page requests
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function dashboard () {
		$Order = ShoppOrder();

		$this->add_dashboard( 'logout', __('Logout', 'Shopp') );
		$this->add_dashboard( 'orders', __('Your Orders', 'Shopp'), true, array(ShoppCustomer(), 'load_orders') );
		$this->add_dashboard( 'downloads', __('Downloads', 'Shopp'), true, array(ShoppCustomer(), 'load_downloads') );
		$this->add_dashboard( 'profile', __('My Account', 'Shopp'), true );

		// Pages not in menu navigation
		$this->add_dashboard( 'login', __('Login to your Account'), false );
		$this->add_dashboard( 'recover', __('Password Recovery'), false );
		$this->add_dashboard( 'rp', __('Password Recovery'), false );
		$this->add_dashboard( 'menu', __('Dashboard', 'Shopp'), false );

		do_action( 'shopp_account_menu' );

		// Always handle customer profile updates
		add_action( 'shopp_account_management', array(ShoppCustomer(), 'profile') );

		// Add dashboard page specific handlers
		add_action( 'shopp_account_management', array($this, 'dashboard_handler') );

		$query = $_SERVER['QUERY_STRING'];
		$query = html_entity_decode($query);
		$query = explode('&', $query);

		$request = 'menu';
		$id = false;

		foreach ( $query as $queryvar ) {
			$value = false;
			if ( false !== strpos($queryvar, '=') ) list($key, $value) = explode('=', $queryvar);
			else $key = $queryvar;

			if ( in_array($key, array_keys($this->dashboard)) ) {
				$request = $key;
				$id = $value;
			}
		}

		$this->account = compact('request', 'id');

		$download_request = get_query_var('s_dl');
		if ( ! ShoppCustomer()->loggedin() ) {
			$screens = array('login', 'recover', 'rp');
			if ( ! in_array($this->account['request'], $screens) )
				$this->account = array('request' => 'login', 'id' => false);
		}

		do_action( 'shopp_account_management' );

		if ( 'rp' == $request ) ShoppAccountPage::resetpassword($_GET['rp']);
		if ( isset($_POST['recover-login']) ) ShoppAccountPage::recovery();

	}

	/**
	 * Account dashboard callback trigger
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function dashboard_handler () {
		$request = $this->account['request'];

		if ( isset($this->dashboard[$request])
			&& is_callable($this->dashboard[$request]->handler))
				call_user_func($this->dashboard[$request]->handler);

	}

	/**
	 * Registers a new account dashboard page
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $request The query request name associated with the page
	 * @param string $label The label (title) of the page
	 * @param boolean $visible Flag to show or hide the page in the menus
	 * @param string|array $callback The function callback for pre-page processing
	 * @param int $position The position of the page in the account menu list
	 * @return void
	 **/
	public function add_dashboard ( $request, $label, $visible = true, $callback = false, $position = 0 ) {
		$this->dashboard[$request] = new ShoppAccountDashboardPage($request, $label, $callback);
		if ($visible) array_splice($this->menus, $position, 0, array($this->dashboard[$request]) );
	}

	/**
	 * Sets handlers for Shopp shortcodes
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function shortcodes () {

		$this->shortcodes = array();

		// Additional shortcode functionality
		$this->shortcodes['catalog-product'] 	= array('ShoppShortcodes', 'product');
		$this->shortcodes['catalog-buynow'] 	= array('ShoppShortcodes', 'buynow');
		$this->shortcodes['catalog-collection']	= array('ShoppShortcodes', 'collection');

		foreach ( $this->shortcodes as $name => &$callback )
			if ( shopp_setting_enabled('maintenance') || ! ShoppSettings()->available() || Shopp::maintenance() )
				add_shortcode($name, array('', 'maintenance_shortcode') );
			else add_shortcode($name, $callback);

	}

	public function autowrap ( $content ) {
		if ( ! in_array(get_the_ID(), $this->shortcoded) ) return $content;
		return ShoppStorefront::wrapper($content);
	}

	/**
	 * Report on the currently loading template
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string The template file being loaded
	 **/
	public static function intemplate ( $template = null ) {
		if ( isset($template) )
			self::$template = basename($template);
		if ( empty(self::$template) ) return '';
		return self::$template;
	}

	/**
	 * Wraps mark-up in a #shopp container, if needed
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $string The content markup to be wrapped
	 * @param array $classes CSS classes to add to the container
	 * @return string The wrapped markup
	 **/
	static function wrapper ( $string ) {

		$classes = array('shoppage', 'shopp_page');

		$views = array('list', 'grid');
		$view = shopp_setting('default_catalog_view');
		if ( empty($view) ) $view = 'grid';

		// Handle catalog view style cookie preference
		if ( isset($_COOKIE['shopp_catalog_view']) ) $view = $_COOKIE['shopp_catalog_view'];
		if ( in_array($view, $views) ) $classes[] = $view;

		$boxes = shopp_setting('row_products');
		if ( empty($boxes) ) $boxes = 3;
		$classes[] = 'shopp_grid-' . abs($boxes);

		// Add collection slug
		$Collection = ShoppCollection();
		if ( ! empty($Collection) )
			if ( $category = shopp('collection.get-slug') ) $classes[] = $category;

		// Add product id & slug classes
		$Product = ShoppProduct();
		if ( ! empty($Product) ) {
			if ( $productid = shopp('product.get-id') ) $classes[] = 'product-' . $productid;
			if ( $product = shopp('product.get-slug') ) $classes[] = $product;
		}

		$classes = apply_filters( 'shopp_content_container_classes', $classes);
		$classes = esc_attr( join(' ', $classes) );

		$id = ( false === strpos($string, 'id="shopp"') ) ? ' id="shopp" ' : '';
		return '<div'. $id . ( ! empty($classes) ? ' class="' . $classes . '"' : '') . '>' . $string . '</div>';
	}

	/**
	 * Renders the errors template
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string The processed errors.php template file
	 **/
	static function errors ( array $templates = array('errors.php') ) {

		ob_start();
		locate_shopp_template( $templates, true );
		$content = ob_get_clean();

		return apply_filters('shopp_storefront_errors', $content);
	}

}
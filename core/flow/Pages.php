<?php
/**
 * Pages.php
 *
 * Storefront page management classes
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package storefront
 * @subpackage storefront
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access


// @todo Pages.php needs code documentation
class ShoppPages extends ListFramework {

	const QUERYVAR = 'shopp_page';

	private static $object;
	private $slugs = array();

	private function __construct () {
		add_action('shopp_init_storefront_pages', array('ShoppPages', 'permalinks') );
	}

	/**
	 * Singleton access method
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return ShoppPages The running ShoppPages object
	 **/
	public static function object () {
		if ( ! self::$object instanceof self )
			self::$object = new self();
		return self::$object;
	}

	public function register ( $StorefrontPageClass ) {

		if ( ! class_exists($StorefrontPageClass) ) return false;

		$name = get_class_property($StorefrontPageClass, 'name');
		$settings = shopp_setting('storefront_pages');

		do_action('shopp_register_page', $name, $settings);

		$options = array();

		if ( isset($settings[ $name ]) )
			$options = apply_filters( 'shopp_' . $name . '_page_settings', $settings[ $name ] );

		if ( $Page = new $StorefrontPageClass($options) ) {
			$this->slugs[ $Page->slug() ] = $name;
			ShoppPages()->add($name, $Page);
		}

	}

	public static function request () {
		return get_query_var(self::QUERYVAR);
	}

	public function requested () {
		if ( ! isset($this->slugs[ ShoppPages::request() ]) ) return false;
		$pagename = $this->slugs[ ShoppPages::request() ];
		return $this->get( $pagename );
	}

	public static function permalinks () {
		$var = ShoppPages::QUERYVAR;

		$pageslugs = ShoppPages()->slugs();
		$catalog = $pageslugs['catalog'];
		unset($pageslugs['catalog']);

		add_rewrite_tag("%$var%", '('.join('|', $pageslugs).')');
		add_permastruct($var, "$catalog/%$var%", false);
	}

	public function slugpage ( $slug ) {
		if ( ! isset($this->slugs[ $slug ]) ) return false;
		return $this->get( $this->slugs[ $slug ] );
	}

	public function baseslug () {
		$CatalogPage = $this->get('catalog');
		if ( ! $CatalogPage ) return '';
		return $CatalogPage->slug();
	}

	public function names () {
		return $this->keys();
	}

	public function slugs () {
		$slugs = array();
		foreach ( $this as $name => $Page )
			$slugs[ $name ] = $Page->slug();
		return $slugs;
	}

	public function settings () {
		$settings = array();
		foreach ( $this as $name => $Page ) {
			$settings[ $name ] = array(
				'slug'        => $Page->slug(),
				'title'       => $Page->title(),
				'description' => $Page->description()
			);
		}
		return $settings;
	}

}

/**
 * StorefrontPage
 *
 * A base utility class to provide basic WordPress content override behaviors
 * for rendering Shopp storefront content. ShoppPage classes use filters
 * to override the page title and content with information from Shopp provided
 * by template instructions in Shopp content templates.
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppPage {

	public static $name     = 'catalog';	// Internal name of the page
	public static $template = '';			// Additional specific page template (more specific than: shopp.php, page.php)

	protected $slug        = 'shopp';		// Slug of the page
	protected $title       = '';			// Title of the page
	protected $description = '';			// Translateable page description for admins
	protected $templates   = array();		// Additional Shopp content
	protected $edit        = array(			// Edit link parameters
		'page' => 'shopp-settings-pages'
	);

	public function __construct ( array $options = array() ) {
		$defaults = array(
			'title'       => $this->title,
			'slug'        => $this->slug,
			'description' => '',
			'edit'        => $this->edit
		);
		$options = array_merge($defaults, $options);

		foreach ( (array)$options as $name => $value )
			if ( isset($this->$name) ) $this->$name = $value;

	}

	public function editlink ( $link ) {
		$url = admin_url('admin.php');
		if ( ! empty($this->edit) ) $link = add_query_arg($this->edit, $url);
		return $link;
	}

	public function styleclass ( $classes ) {
		$classes[] = $this->name();
		return $classes;
	}

	public function name () {
		$classname = get_class($this);
		return get_class_property($classname, 'name');
	}

	public function head () {
		/* Implemented in concrete classes */
	}

	public function pagetemplate () {
		$classname = get_class($this);
		return get_class_property($classname, 'template');
	}

	public function content ( $content ) {
		return $content;
	}

	public function nocomment () {
		return array();
	}

	/**
	 * Provides the title for the page from settings
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string The page title
	 **/
	public function title () {
		$classname = get_class($this);
		$title     = apply_filters('shopp_' . get_class_property($classname, 'name') . '_pagetitle', $this->title); // @deprecated Use shopp_storefront_page_title or shopp_{$name}_storefront_page_title instead

		$title = apply_filters('shopp_' . get_class_property($classname, 'name') . '_storefront_page_title', $title);
		return apply_filters('shopp_storefront_page_title', $title);
	}

	public function slug () {
		$classname = get_class($this);
		$slug      = apply_filters('shopp_' . get_class_property($classname, 'name') . '_storefront_page_slug', $this->slug);
		return apply_filters('shopp_storefront_page_slug', $slug);
	}

	public function description () {
		return $this->description;
	}

	/**
	 * Determines page template names (page templates, not content templates)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of page templates
	 **/
	public function templates () {
		$templates = array('shopp.php', 'page.php');

		$name = $this->name();
		if ( ! empty($name) ) {
			array_unshift($templates, "$name.php"); // @deprecated
			array_unshift($templates, "shopp-$name.php");
		}

		$template = $this->pagetemplate();
		if ( ! empty($template) ) array_unshift($templates, "$template.php");

		$templates = apply_filters('shopp_' . $name . '_storefront_page_templates', $templates, $this->slug(), $this->title() );

		return $templates;
	}

	public function filters () {
		add_filter('shopp_content_container_classes', array($this, 'styleclass'));
		add_filter('body_class', array($this, 'styleclass'));
		add_filter('get_edit_post_link', array($this, 'editlink'));
		add_filter('get_edit_term_link', array($this, 'editlink'));
		add_filter('wp_head', array($this, 'head'), 20);
		add_filter('the_content', array($this, 'content'), 20);
		add_filter('the_excerpt', array($this, 'content'), 20);
		add_filter('comments_array', array($this, 'nocomment'));
		add_filter('wpseo_replacements', array($this, 'wpseo')); // compatibility helper for WPSEO
	    add_filter('get_the_excerpt', function ( $c ) {
		    remove_filter( 'get_the_excerpt', 'wp_trim_excerpt' );

		    return $c;
        }, 5);

	}

	public function wpseo ( $replacements ) {

		if ( is_shopp_page() && empty($replacements['%%title%%']) )
			$replacements['%%title%%'] = $this->title();

		return $replacements;

	}


	public function poststub () {
		global $wp_query;
		if ( ! $wp_query->is_main_query() ) return;

		$this->filters();

		$stub = new WPDatabaseObject;
		$stub->init('posts');
		$stub->ID             = 0;
		$stub->post_name      = '';
		$stub->comment_status = 'closed'; // Force comments closed
		$stub->post_title     = $this->title;
		$stub->post_content   = '';
		$stub->post_excerpt   = ' '; // Prevent wp_trim_excerpt from calling the_content filter
		$stub->post_type      = ShoppPages::QUERYVAR;

		// Setup labels
		$labels = new stdClass;
		$labels->name = $this->title();
		$stub->labels = $labels;

		$wp_query->queried_object = $stub;
		$wp_query->posts = array($stub);
		return $stub;
	}

}

/**
 * Renders the Shopp catalog storefront page
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppCatalogPage extends ShoppPage {

	const FRONTPAGE = '83104111112112';

	public static $name = 'catalog';
	protected $slug = 'shop';
	protected $templates = array('catalog.php');

	public function __construct($options = array()) {

		$defaults = array(
			'title'       => Shopp::__('Shop'),
			'description' => Shopp::__('The page title and base slug for products, categories & collections.'),
		);
		$options = array_merge($defaults, $options);

		parent::__construct($options);
	}

	public function templates () {
		$templates = parent::templates();
		if ( $this->is_frontpage() )
			array_unshift($templates, 'front-page.php');
		return $templates;
	}

	public function content ($content) {
		global $wp_query;
		// Test that this is the main query and it is a catalog page
		if ( ! $wp_query->is_main_query() || ! is_shopp_catalog_frontpage() ) return $content;

		shopp_debug('Displaying catalog page request: ' . $_SERVER['REQUEST_URI']);

		ob_start();
		locate_shopp_template(array('catalog.php'), true);
		$content = ob_get_clean();

		return apply_filters('shopp_catalog_template', $content);

	}

	static function frontid () {
		return self::FRONTPAGE;
	}

	public function styleclass ( $classes ) {
		if ( $this->is_frontpage() )
			$classes[] = 'home';
		$classes[] = $this->name();
		return $classes;
	}

	public function poststub () {
		global $wp_query;
		if ( ! $wp_query->is_main_query() ) return;

		$stub = parent::poststub();
		$wp_query->is_post_type_archive = false;
		if ( $this->is_frontpage() )
			$wp_query->is_home = true;

		return $stub;
	}

	public function is_frontpage () {
		return self::frontid() == get_option('page_on_front');
	}
}

/**
 * The account dashboard page
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppAccountPage extends ShoppPage {

	public static $name = 'account';

	protected $slug      = 'account';
	protected $templates = array('account.php');

	public function __construct ( $options = array() ) {

		$defaults = array(
			'title'       => Shopp::__('Account'),
			'description' => Shopp::__('Used to display customer account dashboard &amp; profile pages.'),
		);

		if ( 'none' == shopp_setting('account_system') ) {

			$defaults = array(
				'title'       => Shopp::__('Order Lookup'),
				'description' => Shopp::__('The order lookup page allows customers to lookup previous orders.'),
			);

		}

		$options = array_merge($defaults, $options);

		parent::__construct($options);
	}

	public function content ($content, $request = false) {
		if ( ! $request ) {
			global $wp_query;
			// Test that this is the main query and it is the account page
			if ( ! $wp_query->is_main_query() || ! is_shopp_page('account') ) return $content;
		}

		$widget = ( 'widget' === $request );
		if ( $widget ) $request = 'menu'; // Modify widget request to render the account menu

		$orderlookup = '';
		if ( 'none' == shopp_setting('account_system' ) )
			$orderlookup = shopp( 'customer', 'get-order-lookup' );

		// $download_request = get_query_var('s_dl');
		if ( ! $request ) $request = ShoppStorefront()->account['request'];

		$templates = array( 'account-'.$request.'.php', 'account.php' );
		$context   = ShoppStorefront::intemplate(); // Set account page context

		$Errors = ShoppErrorStorefrontNotices();
		ob_start();
		if ( apply_filters( 'shopp_show_account_errors', true ) && $Errors->exist() )
			echo ShoppStorefront::errors( array( "errors-$context", 'account-errors.php', 'errors.php' ) );

		if ( ! empty($orderlookup) ) {
			echo $orderlookup;
		} else {
			if ( 'login' == $request || ! ShoppCustomer()->loggedin() ) $templates = array( 'login-' . $request . '.php', 'login.php' );
			Shopp::locate_template( $templates, true );
		}
		$content = ob_get_clean();

		// Suppress the #shopp div for sidebar widgets
		if ( $widget ) $content = '<!-- id="shopp" -->' . $content;

		return apply_filters( 'shopp_account_template', $content, $request );

	}

	/**
	 * Password recovery processing
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.1
	 *
	 * @return void
	 **/
	static function recovery () {
		$errors = array();

		// Check email or login supplied
		if ( empty($_POST['account-login']) ) {
			if ( 'wordpress' == shopp_setting('account_system') ) $errors[] = new ShoppError(Shopp::__('Enter an email address or login name'));
			else $errors[] = new ShoppError(Shopp::__('Enter an email address'));
		} else {
			// Check that the account exists
			if ( strpos($_POST['account-login'], '@') !== false ) {
				$RecoveryCustomer = new ShoppCustomer($_POST['account-login'], 'email');
				if ( ! $RecoveryCustomer->id )
					$errors[] = new ShoppError(Shopp::__('There is no user registered with that email address.'), 'password_recover_noaccount', SHOPP_AUTH_ERR);
			} else {
				$user_data        = get_userdatabylogin($_POST['account-login']);
				$RecoveryCustomer = new ShoppCustomer($user_data->ID, 'wpuser');
				if ( empty($RecoveryCustomer->id) )
					$errors[] = new ShoppError(Shopp::__('There is no user registered with that login name.'), 'password_recover_noaccount', SHOPP_AUTH_ERR);
			}
		}

		// return errors
		if ( ! empty($errors) ) return;

		// Generate new key
		$RecoveryCustomer->activation = wp_generate_password(20, false);
		do_action_ref_array('shopp_generate_password_key', array(&$RecoveryCustomer));
		$RecoveryCustomer->save();

		$subject = apply_filters('shopp_recover_password_subject', Shopp::__('[%s] Password Recovery Request', get_option('blogname')));

		$_ = array();
		$_[] = 'From: ' . Shopp::email_from( shopp_setting('merchant_email'), shopp_setting('business_name') );
		$_[] = 'To: ' . $RecoveryCustomer->email;
		$_[] = 'Subject: ' . $subject;
		$_[] = 'Content-type: text/html';
		$_[] = '';
		$_[] = '<p>' . Shopp::__('A request has been made to reset the password for the following site and account:') . '<br />';
		$_[] = get_bloginfo('url') . '</p>';
		$_[] = '';
		$_[] = '<ul>';
		if ( isset($_POST['email-login']) )
			$_[] = '<li>' . Shopp::__('Email: %s', $RecoveryCustomer->email) . '</li>';
		if ( isset($_POST['loginname-login']) )
			$_[] = '<li>' . Shopp::__('Login name: %s', $user_data->user_login) . '</li>';
		if ( isset($_POST['account-login']) )
			$_[] = '<li>' . Shopp::__('Login: %s', $user_data->user_login) . '</li>';
		$_[] = '</ul>';
		$_[] = '';
		$_[] = '<p>' . Shopp::__('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.').'</p>';
		$_[] = '';
		$_[] = '<p>' . add_query_arg(array('rp' => $RecoveryCustomer->activation), Shopp::url(false, 'account')) . '</p>';
		$message = apply_filters('shopp_recover_password_message', $_);

		if ( ! Shopp::email(join("\n", $message)) ) {
			new ShoppError(__('The e-mail could not be sent.'), 'password_recovery_email', SHOPP_ERR);
			Shopp::redirect( add_query_arg( 'acct', 'recover', Shopp::url(false, 'account') ) );
		} else {
			new ShoppError(Shopp::__('Check your email address for instructions on resetting the password for your account.'), 'password_recovery_email', SHOPP_ERR);
		}

	}

	static function resetpassword ($activation) {
		if ( 'none' == shopp_setting('account_system') ) return;

		$user_data  = false;
		$activation = preg_replace('/[^a-z0-9]/i', '', $activation);

		$errors = array();
		if ( empty($activation) || ! is_string($activation) )
			$errors[] = new ShoppError(Shopp::__('Invalid key'));

		$RecoveryCustomer = new ShoppCustomer($activation, 'activation');
		if ( empty($RecoveryCustomer->id) )
			$errors[] = new ShoppError(Shopp::__('Invalid key'));

		if ( ! empty($errors) ) return false;

		// Generate a new random password
		$password = wp_generate_password();

		do_action_ref_array('password_reset', array($RecoveryCustomer, $password));

		$RecoveryCustomer->password = wp_hash_password($password);
		if ( 'wordpress' == shopp_setting('account_system') ) {
			$user_data = get_userdata($RecoveryCustomer->wpuser);
			wp_set_password($password, $user_data->ID);
		}

		$RecoveryCustomer->activation = '';
		$RecoveryCustomer->save();

		$subject = apply_filters('shopp_reset_password_subject', Shopp::__('[%s] New Password', get_option('blogname')));

		$_ = array();
		$_[] = 'From: ' . Shopp::email_from( shopp_setting('merchant_email'), shopp_setting('business_name'));
		$_[] = 'To: ' . $RecoveryCustomer->email;
		$_[] = 'Subject: ' . $subject;
		$_[] = 'Content-type: text/html';
		$_[] = '';
		$_[] = '<p>' . Shopp::__('Your new password for %s:', get_bloginfo('url')) . '</p>';
		$_[] = '';
		$_[] = '<ul>';
		if ( $user_data )
			$_[] = '<li>' . Shopp::__('Login name: %s', $user_data->user_login) . '</li>';
		$_[] = '<li>' . Shopp::__('Password: %s', $password) . '</li>';
		$_[] = '</ul>';
		$_[] = '';
		$_[] = '<p>' . Shopp::__('Click here to login: %s', Shopp::url(false, 'account')) . '</p>';
		$message = apply_filters('shopp_reset_password_message', $_);

		if ( ! Shopp::email(join("\n", $message)) ) {
			shopp_add_error(Shopp::__('The e-mail could not be sent.'));
			Shopp::redirect( add_query_arg( 'acct', 'recover', Shopp::url(false, 'account') ) );
		} else shopp_add_error(Shopp::__('Check your email address for your new password.'));

		unset($_GET['acct']);
	}

}

/**
 * CartStorefrontPage
 *
 * The shopping cart page
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppCartPage extends ShoppPage {

	public static $name = 'cart';
	protected $slug = 'cart';
	protected $templates = array('cart.php');

	public function __construct ( $options = array() ) {

		$defaults = array(
			'title'       => Shopp::__('Cart'),
			'description' => Shopp::__('Displays the shopping cart.'),
		);

		$options = array_merge($defaults, $options);

		parent::__construct($options);
	}

	public function content ($content) {
		global $wp_query;
		// Test that this is the main query and it is the cart page
		if ( ! $wp_query->is_main_query() || ! is_shopp_page('cart') ) return $content;

		ob_start();

		$Errors = ShoppErrorStorefrontNotices();
		if ( $Errors->exist() )
			echo ShoppStorefront::errors(array('errors-cart.php', 'errors.php'));

		locate_shopp_template(array('cart.php'), true);

		$content = ob_get_clean();

		return apply_filters('shopp_cart_template', $content);
	}

}

/**
 * The checkout page.
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppCheckoutPage extends ShoppPage {

	public static $name = 'checkout';
	protected $slug = 'checkout';
	protected $templates = array('checkout.php');

	public function __construct ( $options = array() ) {

		$defaults = array(
			'title'       => Shopp::__('Checkout'),
			'description' => Shopp::__('Displays the checkout form page.'),
		);

		$options = array_merge($defaults, $options);

		parent::__construct($options);
	}

	public function head () {?>
		<script type="text/javascript">//<![CDATA[ checkout JS-support detection
			document.documentElement.className += ' js-on'; //]]></script><?php
	}

	public function content ($content) {
		global $wp_query;
		// Test that this is the main query and it is the checkout page
		if ( ! $wp_query->is_main_query() || ! is_shopp_page('checkout') ) return $content;

		$Errors = ShoppErrors();

		do_action('shopp_init_checkout');

		ob_start();

		$Errors = ShoppErrorStorefrontNotices();
		if ( $Errors->exist() )
			echo ShoppStorefront::errors(array('errors-checkout.php', 'errors.php'));

		locate_shopp_template(array('checkout.php'), true);

		$content = ob_get_clean();

		return apply_filters('shopp_checkout_page', $content);
	}

}

/**
 * The confirmation page shown after submitting the checkout form. This page
 * is designed to give customers a chance to confirm order details. This is necessary
 * for situations where the address details change from the shopping cart shipping and
 * tax estimates to a final address that cause shipping and taxes to recalculate. The
 * customer must be given the opportunity to see the cost changes before proceeding
 * with the order.
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppConfirmPage extends ShoppPage {

	public static $name = 'confirm';
	protected $slug = 'confirm-order';
	protected $templates = array('confirm.php');

	public function __construct ( $options = array() ) {

		$defaults = array(
			'title'       => Shopp::__('Confirm Order'),
			'description' => Shopp::__('Displays an order summary to allow the customer to confirm the order before submitting for payment.'),
		);
		$options = array_merge($defaults, $options);

		parent::__construct($options);
	}

	public function content ($content) {
		global $wp_query;
		// Test that this is the main query and it is the confirm order page
		if ( ! $wp_query->is_main_query() || ! is_shopp_page('confirm') ) return $content;

		$Errors = ShoppErrors();
		$Order = ShoppOrder();

		do_action('shopp_init_confirmation');
		$Order->validated = $Order->isvalid();

		ob_start();
		ShoppStorefront()->_confirm_page_content = true;
		if ( $Errors->exist(SHOPP_COMM_ERR) )
			echo ShoppStorefront::errors(array('errors-confirm.php', 'errors.php'));

		locate_shopp_template(array('confirm.php'), true);
		$content = ob_get_contents();

		unset(ShoppStorefront()->_confirm_page_content);
		ob_end_clean();

		return apply_filters('shopp_order_confirmation', $content);
	}

}

/**
 * The thank you page shown after an order is successfully submitted.
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppThanksPage extends ShoppPage {

	public static $name = 'thanks';
	protected $slug = 'thanks';
	protected $templates = array('thanks.php');

	public function __construct ( $options = array() ) {

		$defaults = array(
			'title'       => Shopp::__('Thanks'),
			'description' => Shopp::__('The final page of the ordering process.'),
		);
		$options = array_merge($defaults, $options);

		parent::__construct($options);
	}

	public function content ($content) {

		global $wp_query;
		// Make sure this is the main query and it is the thanks page
		if ( ! $wp_query->is_main_query() || ! is_shopp_page('thanks') ) return $content;

		ob_start();
		locate_shopp_template(array('thanks.php'), true);
		$content = ob_get_contents();
		ob_end_clean();
		return apply_filters('shopp_thanks', $content);
	}

}

/**
 * Renders a maintenance message
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package storefront
 **/
class ShoppMaintenancePage extends ShoppPage {

	public static $name  = 'shopp-maintenance';
	protected $templates = array('maintenance.php');

	public function __construct ( $options = array() ) {

		$options['title'] = Shopp::__('We’re Sorry!');

		parent::__construct($options);

	}

	public function content ($content) {
		global $wp_query;
		if ( ! $wp_query->is_main_query() ) return $content;

		if ( '' != locate_shopp_template($this->templates) ) {
			ob_start();
			locate_shopp_template($this->templates, true);
			$content = ob_get_clean();
		} else $content = '<div id="shopp" class="update"><p>' . Shopp::__('The store is currently down for maintenance. We’ll be back soon!').'</p><div class="clear"></div></div>';

		return $content;
	}

}

/**
 * Handles rendering storefront the product page
 *
 * Renamed from ProductStorefrontPage
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppProductPage extends ShoppPage {

	public static $name = 'shopp-product';

	public function __construct ( $settings = array() ) {
		self::$template = 'single-' . ShoppProduct::$posttype;
		parent::__construct($settings);
	}

	public function editlink ( $link ) {
		return $link;
	}

	public function wp_title ( $title, $sep = null, $placement = null ) {
		if ( is_null($sep) ) $sep = '&mdash;';
		if ( is_null($placement) ) $placement = '';

		return $title;
	}

	public function content ( $content ) {
		global $wp_query;
		// Test that this is the main query and it is a product
		if ( ! $wp_query->is_main_query() || ! is_shopp_product() ) return $content;

		$Product = ShoppProduct();

		$templates = array('product.php');
		if ( isset($Product->id) && !empty($Product->id) )
			array_unshift($templates, 'product-'.$Product->id.'.php');

		if ( isset($Product->slug) && !empty($Product->slug) )
			array_unshift($templates, 'product-'.$Product->slug.'.php');

		// Load product summary data, before checking inventory
		if ( ! isset($Product->summed) ) $Product->load_data(array('summary'));

		if ( Shopp::str_true($Product->inventory) && $Product->stock < 1 )
			array_unshift($templates, 'product-outofstock.php');

		ob_start();
		locate_shopp_template($templates, true);
		$content = ob_get_contents();
		ob_end_clean();
		return ShoppStorefront::wrapper($content);
	}


	public function filters () {
		parent::filters();
		remove_filter('comments_array', array($this, 'nocomment'));
	}

}

/**
 * Responsible for Shopp product collections, custom categories, tags and other taxonomy pages for Shopp
 *
 * Renamed from CollectionStorefrontPage
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppCollectionPage extends ShoppPage {

	public static $name = 'shopp-collection';

	public $slug = 'catalog';
	public $templates = array('shopp-collection.php');

	public function __construct ( $settings = array() ) {

		$Collection = ShoppCollection();
		// Define the edit link for collections and taxonomies

		$editlink = add_query_arg('page', 'shopp-settings-pages', admin_url('admin.php'));

		if (isset($Collection->taxonomy) && isset($Collection->id)) {
			$page = 'edit-tags.php';
			$query = array(
				'action'   => 'edit',
				'taxonomy' => $Collection->taxonomy,
				'tag_ID'   => $Collection->id
			);
			if ('shopp_category' == $Collection->taxonomy) {
				$page  = 'admin.php';
				$query = array(
					'page' => 'shopp-categories',
					'id'   => $Collection->id
				);
			}
			$editlink = add_query_arg($query, admin_url($page));
		}

		$settings = array(
			'title' => shopp($Collection, 'get-name'),
			'edit'  => $editlink,
		);
		parent::__construct($settings);
	}

	public function filters () {
		parent::filters();
		add_filter('wp_title', array($this, 'unlabel'), 1, 3);
	}

	public function unlabel ( $title, $sep, $seplocation ) {
		global $wp_query;

		$query_object = $wp_query->queried_object;
		if ( empty($query_object->taxonomy) ) return $title;

		$tax = get_taxonomy($query_object->taxonomy);
		if ( empty($tax->labels->name) ) return $title;

		$taxlabel = 'right' == $seplocation ? $tax->labels->name . " $sep " : " $sep " . $tax->labels->name;
		$title = str_replace($taxlabel, '', $title);

		return $title;
	}

	public function editlink ( $link ) {
		return $this->edit;
	}

	/**
	 * Determines page template names (page templates, not content templates)
	 *
	 * Uses the following precedence:
	 * - taxonomy-shopp_taxonomy-slug.php
	 * - shopp-taxonomy-slug.php
	 * - taxonomy-shopp_taxonomy.php
	 * - shopp-taxonomy.php
	 * - shopp-collection.php
	 * - shopp.php
	 * - page.php
	 *
	 * @author Jonathan Davis
	 * @since 1.3.1
	 *
	 * @return array Collection page templates
	 **/
	public function templates () {
		global $wp_query;
		$templates = array('shopp.php', 'page.php');

		$taxonomy = self::$name;
		array_unshift($templates, $taxonomy . '.php');

		$object = $wp_query->queried_object;
		if ( ! empty($object->taxonomy) )
		$taxonomy = $object->taxonomy;

		$shopptax = str_replace('_', '-', $taxonomy);
		array_unshift($templates,
			'taxonomy-' . $taxonomy . '.php', // taxonomy-shopp-category.php
			$shopptax . '.php', // shopp-category.php
			'taxonomy.php', // taxonomy.php
			$taxonomy . '.php'  // shopp-collection.php
		);

		$slug = $object->slug;
		if ( ! empty($slug) ) {
			array_unshift($templates, str_replace('_', '-', $taxonomy) . '-' . $slug . '.php'); // shopp-category-slug.php
			array_unshift($templates, 'taxonomy-' . $taxonomy . '-' . $slug . '.php'); // taxonomy-shopp_category-slug.php
		}

		return $templates;
	}


	public function content ($content) {
		global $wp_query;
		// Only modify content for Shopp collections (Shopp smart collections and taxonomies)
		if ( ! $wp_query->is_main_query() ||  ! is_shopp_collection() ) return $content;
		remove_filter('the_content', array($this, 'content'), 20);

		$Collection = ShoppCollection();

		ob_start();
		if ( empty($Collection) ) locate_shopp_template(array('catalog.php'), true);
		else {
			$templates = array('category.php', 'collection.php');
			$ids = array('slug', 'id');
			foreach ( $ids as $property ) {
				if ( isset($Collection->$property) ) $id = $Collection->$property;
				array_unshift($templates, 'category-' . $id . '.php', 'collection-' . $id . '.php');
			}
			locate_shopp_template($templates, true);
		}
		$content = ob_get_clean();

		return apply_filters('shopp_category_template', $content);
	}

	public function poststub () {
		global $wp_query;
		if ( ! $wp_query->is_main_query() ) return;

		$query_object = $wp_query->queried_object;
		$stub = parent::poststub();
		$Collection = ShoppCollection();
		$query_object->name = $Collection->name;
		$query_object->slug = $Collection->slug;
		$wp_query->queried_object = $query_object;
		$wp_query->is_post_type_archive = false;

		return $stub;
	}

}

/**
 * Handles rendering shortcodes on the storefront
 *
 * Renamed from StorefrontShortcodes
 *
 * @author Jonathan Davis
 * @since 1.2.1
 * @package storefront
 **/
class ShoppShortcodes {

	/**
	 * Handles rendering the [product] shortcode
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $attrs The parsed shortcode attributes
	 * @return string The processed content
	 **/
	static function product ( $atts ) {
		$atts['template'] = array('product-shortcode.php', 'product.php');
		ShoppStorefront()->shortcoded[] = get_the_ID();
		return apply_filters('shopp_product_shortcode', shopp('storefront.get-product', $atts));
	}

	/**
	 * Handles rendering the [catalog-collection] shortcode
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $attrs The parsed shortcode attributes
	 * @return string The processed content
	 **/
	static function collection ( array $atts = array() ) {
		$Shopp = Shopp::object();
		$tag = 'category';
		if ( isset($atts['name']) ) {
			$Collection = new ProductCategory($atts['name'], 'name');
			unset($atts['name']);
		} elseif ( isset($atts['slug']) ) {
			foreach ( $Shopp->Collections as $SmartCollection ) {
				$slugs = SmartCollection::slugs($SmartCollection);
				if ( in_array($atts['slug'], $slugs) ) {
					$tag = $slugs[0] . "-collection";
					unset($atts['slug']);
					break;
				}
			}

		} elseif ( isset($atts['id']) ) {
			$Collection = new ProductCategory($atts['id']);
			unset($atts['id']);
		} else return '';

		ShoppCollection($Collection);

		$markup = shopp("catalog.get-$tag", $atts);
		ShoppStorefront()->shortcoded[] = get_the_ID();

		// @deprecated in favor of the shopp_collection_shortcode
		$markup = apply_filters('shopp_category_shortcode', $markup);
		return apply_filters('shopp_collection_shortcode', $markup);
	}

	/**
	 * Handles rendering the [product-buynow] shortcode
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $attrs The parsed shortcode attributes
	 * @return string The processed content
	 **/
	static function buynow ( array $atts = array() ) {

		$properties = array('name', 'slug', 'id');
		foreach ( $properties as $prop ) {
			if ( ! isset($atts[ $prop ]) ) continue;
			$Product = new ShoppProduct($atts[ $prop ], $prop);
		}

		if ( ! empty($Product->id) ) {
			ShoppProduct($Product);
		} elseif ( ! isset( ShoppProduct()->id ) ) {
			return "";
		}

		ob_start();
		?>
		<form action="<?php shopp('cart.url'); ?>" method="post" class="shopp product">
			<input type="hidden" name="redirect" value="checkout" />
			<?php if (isset($atts['variations'])): ?>
				<?php if(shopp('product.has-variations')): ?>
				<ul class="variations">
					<?php shopp('product.variations', 'mode=multiple&label=true&defaults=' . Shopp::__('Select an option') . '&before_menu=<li>&after_menu=</li>'); ?>
				</ul>
				<?php endif; ?>
			<?php endif; ?>
			<?php if (isset($atts['addons'])): ?>
				<?php if(shopp('product.has-addons')): ?>
					<ul class="addons">
						<?php shopp('product.addons', 'mode=menu&label=true&defaults=' . Shopp::__('Select an add-on') . '&before_menu=<li>&after_menu=</li>'); ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
			<p><?php if ( isset($atts['quantity']) ): $quantity = empty($atts['quantity']) ? 'class=selectall&input=menu' : html_entity_decode($atts['quantity']); ?>
				<?php shopp('product.quantity', $quantity); ?>
			<?php endif; ?>
			<?php
				$button = 'label=' . ( isset($atts['label']) ? $atts['label'] : Shopp::__('Buy Now') );
				$button .= ( isset($atts['ajax']) && Shopp::str_true($atts['ajax']) ? '&ajax=on' : '' );
				if ( isset($atts['button']) ) $button = html_entity_decode($atts['button']);
			?>
			<?php shopp('product.addtocart', $button); ?></p>
		</form>
		<?php
		$markup = ob_get_contents();
		ob_end_clean();

		ShoppStorefront()->shortcoded[] = get_the_ID();

		return apply_filters('shopp_buynow_shortcode', $markup);
	}

}

/**
 * A property container for Shopp's customer account page meta
 *
 * Renamed from ShoppDashboardPage
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package customer
 **/
class ShoppAccountDashboardPage {
	public $request = "";
	public $label   = "";
	public $handler = false;

	public function __construct ($request, $label, $handler) {
		$this->request = $request;
		$this->label   = $label;
		$this->handler = $handler;
	}

} // END class ShoppAccountDashboardPage

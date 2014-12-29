<?php
/**
 * Login.php
 *
 * Controller for handling logins
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, May 2012
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage logins
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * ShoppLogin
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ShoppLogin {

	const PROCESS = 'submit-login';

	public $Customer = false;
	public $Billing = false;
	public $Shipping = false;

	/**
	 * Constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function __construct () {

		$this->Customer = &ShoppOrder()->Customer;
		$this->Billing = &ShoppOrder()->Billing;
		$this->Shipping = &ShoppOrder()->Shipping;

		if ( 'none' == shopp_setting('account_system') ) return; // Disabled

		switch ( shopp_setting('account_system') ) {
			case 'shopp':
				add_action('shopp_logout', array($this, 'logout'));
				break;
			case 'wordpress':
				add_action('set_logged_in_cookie', array($this, 'wplogin'), 10, 4);
				add_action('wp_logout', array($this, 'logout'));
				add_action('shopp_logout', 'wp_logout', 1);
				break;
		}

		add_action('shopp_init', array($this, 'process'));
	}

	/**
	 * Handle Shopp login processing
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function process () {

		if ( ShoppRegistration::submitted() ) {
			new ShoppRegistration();
			add_action('shopp_customer_registered', array($this, 'login'));
		}

		if ( isset($_REQUEST['acct']) && 'logout' == $_REQUEST['acct'] || isset($_REQUEST['logout']) ) {
			// Set the last logged out action to save the session and redirect to remove the logout request
			add_action('shopp_logged_out', array($this, 'redirect'), 100);

			// Trigger the logout
			do_action('shopp_logout');
		}

		if ( 'wordpress' == shopp_setting('account_system') ) {

			// See if the wordpress user is already logged in
			$user = wp_get_current_user();

			// Wordpress user logged in, but Shopp customer isn't
			if ( ! empty($user->ID) && ! $this->Customer->loggedin() ) {
				if ( $Account = new ShoppCustomer($user->ID, 'wpuser') ) {
					$this->login($Account);
					$this->Customer->wpuser = $user->ID;
					return;
				}
			}

		}

		if ( ! self::submitted() ) return false;

		// Prevent checkout form from processing
		remove_all_actions('shopp_process_checkout');

		if ( ! isset($_POST['account-login']) || empty($_POST['account-login']) )
			return shopp_add_error( __('You must provide a valid login name or email address to proceed.','Shopp'), SHOPP_AUTH_ERR );

		// Add a login redirect as the very last action if a redirect parameter is provided in the request; Props @alansherwood
		if ( isset($_REQUEST['redirect']) )
			add_action('shopp_authed', array($this, 'redirect'), 100);

		$mode = 'loginname';
		if ( false !== strpos($_POST['account-login'], '@') ) $mode = 'email';
		$this->auth($_POST['account-login'], $_POST['password-login'], $mode);

	}

	/**
	 * Authorize login
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $id The supplied identifying credential
	 * @param string $password The password provided for login authentication
	 * @param string $type (optional) Type of identifying credential provided (defaults to 'email')
	 * @return void
	 **/
	public function auth ( $id, $password, $type = 'email') {

		do_action('shopp_auth');

		$errors = array(
			'empty_username' => __('The login field is empty.','Shopp'),
			'empty_password' => __('The password field is empty.','Shopp'),
			'invalid_email' => __('No customer account was found with that email.','Shopp'),
			'invalid_username' => __('No customer account was found with that login.','Shopp'),
			'incorrect_password' => __('The password is incorrect.','Shopp')
		);

		switch( shopp_setting('account_system') ) {
			case 'shopp':
				$Account = new ShoppCustomer($id, 'email');

				if ( empty($Account) ) {
					new ShoppError( $errors['invalid_email'], 'invalid_account', SHOPP_AUTH_ERR );
					return;
				}

				if ( ! wp_check_password($password, $Account->password) ) {
					new ShoppError( $errors['incorrect_password'], 'incorrect_password', SHOPP_AUTH_ERR );
					return;
				}

				$this->login($Account);
				break;

	  		case 'wordpress':
				if ( 'email' == $type ) {
					$user = get_user_by('email', $id);
					if ( $user ) $loginname = $user->user_login;
					else {
						new ShoppError( $errors['invalid_email'], 'invalid_account', SHOPP_AUTH_ERR );
						return;
					}
				} else $loginname = $id;

				$user = wp_authenticate($loginname, $password);
				if ( is_wp_error($user) ) { // WordPress User Authentication failed
					$code = $user->get_error_code();
					if ( isset($errors[ $code ]) ) new ShoppError( $errors[ $code ], 'invalid_account', SHOPP_AUTH_ERR );
					else {
						$messages = $user->get_error_messages();
						foreach ( $messages as $message )
							new ShoppError( sprintf(__('Unknown login error: %s'), $message), 'unknown_login_error', SHOPP_AUTH_ERR);
					}
					return;
				} else self::wpuser($user);
	  			break;
			default: return;
		}

		do_action('shopp_authed');

	}

	/**
	 * Login to the linked Shopp account when logging into WordPress
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param $cookie N/A
	 * @param $expire N/A
	 * @param $expiration N/A
	 * @param int $user_id The WordPress user ID
	 * @return void
	 **/
	public function wplogin ( $cookie, $expire, $expiration, $id ) {
		if ( $Account = new ShoppCustomer($id, 'wpuser') ) {
			$this->login($Account);
			add_action('wp_logout', array($this,'logout'));
		}
	}

	/**
	 * Helper to log a user into WordPress
	 */
	public static function wpuser ( WP_User $User ) {
		if ( ! is_a($User, 'WP_User') ) return false;
		wp_set_auth_cookie($User->ID, false, is_ssl());
		do_action('wp_login', $User->user_login, $User);
		wp_set_current_user($User->ID, $User->user_login);
		return true;
	}

	/**
	 * Initialize Shopp customer login data
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function login ( $Account ) {

		$this->Customer->copydata($Account, '', array()); // Copy account data to session customer
		$this->Customer->login(); // Mark the customer account as logged in
		unset($this->Customer->password); // Don't need the password in the session

		// Load the billing address
		$this->Billing->load($Account->id, 'customer');
		$clearfields = array('card', 'cardexpires', 'cardholder', 'cardtype');
		foreach ( $clearfields as $field )
			$this->Billing->$field = '';

		// Load the shipping address
		$this->Shipping->load($Account->id, 'customer');
		if ( empty($this->Shipping->id) )
			$this->Shipping->copydata($this->Billing);

		// Warning: Do not exit or redirect from shopp_login action or the login
		// process will not complete properly. Instead use the shopp_login_redirect filter hook
		do_action_ref_array('shopp_login', array($this->Customer));

	}

	/**
	 * Clear the Customer-related session data
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function logout () {
		$Shopp = Shopp::object();
		$Order = ShoppOrder();
		$Shopping = ShoppShopping();


		$this->Customer->clear();
		$this->Billing->clear();
		$this->Shipping->clear();
		$this->Shipping->locate();

		do_action('shopp_logged_out', $this->Customer);

	}

	/**
	 * Handle login redirects
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function redirect () {

		$redirect = false;
		$secure = ShoppOrder()->security();

		if ( isset($_REQUEST['redirect']) && ! empty($_REQUEST['redirect']) ) {
			if ( ShoppPages()->exists($_REQUEST['redirect']) ) $redirect = Shopp::url(false, $_REQUEST['redirect'], $secure);
			else $redirect = $_REQUEST['redirect'];
		}

		if ( ! $redirect ) $redirect = apply_filters('shopp_login_redirect', Shopp::url(false, 'account', $secure));

		Shopp::safe_redirect($redirect);
	}

	/**
	 * Determines if a login form request has been submitted
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return boolean True if a login process was detected, false otherwise
	 **/
	private static function submitted () {
		return isset($_POST[ self::PROCESS ]);
	}

} // END class ShoppLogin
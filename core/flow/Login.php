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

		$this->Customer = ShoppOrder()->Customer;
		$this->Billing = ShoppOrder()->Billing;
		$this->Shipping = ShoppOrder()->Shipping;

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
			if ( ! empty($user->ID) && ! $this->Customer->logged_in() ) {
				if ( $Account = new Customer($user->ID, 'wpuser') ) {
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
	public function auth ( string $id, string $password, $type = 'email') {

		$errors = array(
			'empty_username' => __('The login field is empty.','Shopp'),
			'empty_password' => __('The password field is empty.','Shopp'),
			'invalid_email' => __('No customer account was found with that email.','Shopp'),
			'invalid_username' => __('No customer account was found with that login.','Shopp'),
			'incorrect_password' => __('The password is incorrect.','Shopp')
		);

		switch(shopp_setting('account_system')) {
			case 'shopp':
				$Account = new Customer($id,'email');

				if (empty($Account)) {
					new ShoppError( $errors['invalid_email'],'invalid_account',SHOPP_AUTH_ERR );
					return;
				}

				if (!wp_check_password($password,$Account->password)) {
					new ShoppError( $errors['incorrect_password'],'incorrect_password',SHOPP_AUTH_ERR );
					return;
				}

				break;

  		case 'wordpress':
			if('email' == $type){
				$user = get_user_by_email($id);
				if ($user) $loginname = $user->user_login;
				else {
					new ShoppError( $errors['invalid_email'],'invalid_account',SHOPP_AUTH_ERR );
					return;
				}
			} else $loginname = $id;

			$user = wp_authenticate($loginname,$password);
			if (is_wp_error($user)) { // WordPress User Authentication failed
				$code = $user->get_error_code();
				if ( isset($errors[ $code ]) ) new ShoppError( $errors[ $code ],'invalid_account',SHOPP_AUTH_ERR );
				else {
					$messages = $user->get_error_messages();
					foreach ($messages as $message)
						new ShoppError( sprintf(__('Unknown login error: %s'),$message),'unknown_login_error',SHOPP_AUTH_ERR);
				}
				return;
			} else {
				wp_set_auth_cookie($user->ID);
				do_action('wp_login', $loginname);
				wp_set_current_user($user->ID,$user->user_login);

				return;
			}
  			break;
			default: return;
		}

		$this->login($Account);
		do_action('shopp_auth');

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
		if ( $Account = new Customer($id, 'wpuser') ) {
			$this->login($Account);
			add_action('wp_logout',array(&$this,'logout'));
		}
	}

	/**
	 * Initialize Shopp customer login data
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function login ($Account) {

		if ( $this->Customer->login ) return; // Prevent login pong (Shopp login <-> WP login)

		$this->Customer->copydata($Account, '', array());
		$this->Customer->login = true;
		unset($this->Customer->password);
		$this->Billing->load($Account->id, 'customer');
		$this->Billing->card = '';
		$this->Billing->cardexpires = '';
		$this->Billing->cardholder = '';
		$this->Billing->cardtype = '';
		$this->Shipping->load($Account->id, 'customer');
		if ( empty($this->Shipping->id) )
			$this->Shipping->copydata($this->Billing);

		// Login WP user if not logged in
		if ( 'wordpress' == shopp_setting('account_system') && ! get_current_user_id() ) {
			$user = get_user_by('id', $this->Customer->wpuser);
			@wp_set_auth_cookie($user->ID);
			wp_set_current_user($user->ID, $user->user_login);
		}

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

		$this->Customer = new Customer();
		$this->Billing = new BillingAddress();
		$this->Shipping = new ShippingAddress();
		$this->Shipping->locate();

		do_action_ref_array('shopp_logged_out', array($this->Customer));
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

		session_commit(); // Save the session just prior to redirect

		if ( isset($_POST['redirect']) && ! empty($_POST['redirect']) ) {
			if ( ShoppPages()->exists($_POST['redirect']) ) $redirect = Shopp::url(false, $_POST['redirect'], $secure);
			else $redirect = $_POST['redirect'];
		}

		if ( ! $redirect ) $redirect = Shopp::url(false,'account',$secure);

		shopp_safe_redirect($redirect);
		exit();
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

} // END class Login
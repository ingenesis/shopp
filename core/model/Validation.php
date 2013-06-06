<?php
/**
 * Validation.php
 *
 * Handles form validation
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, April 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage order
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Form validation library
 *
 * @author Jonathan Davis
 * @since 1.3
 * @version 1
 * @package order
 **/
class ShoppFormValidation {

	public static function names () {

		if ( apply_filters('shopp_firstname_required', empty($_POST['firstname'])) )
			return shopp_add_error( __('You must provide your first name.','Shopp') );

		if ( apply_filters('shopp_lastname_required', empty($_POST['lastname'])) )
			return shopp_add_error( __('You must provide your last name.','Shopp') );

		return true;
	}

	public static function email () {

		if ( apply_filters('shopp_email_valid', ! preg_match('!^' . self::RFC822_EMAIL . '$!', $_POST['email'])) )
			return shopp_add_error(__('You must provide a valid e-mail address.','Shopp'));

		return true;
	}

	public static function login () {
		$Customer = ShoppOrder()->Customer;

		if ( 'wordpress' == shopp_setting('account_system') && ! $Customer->logged_in() ) {
			require ABSPATH . '/wp-includes/registration.php';

			// Validate possible wp account names for availability
			if( isset($_POST['loginname']) ) {

				if( apply_filters('shopp_login_exists', username_exists($_POST['loginname'])) )
					return shopp_add_error( __('The login name you provided is not available.  Try logging in if you have previously created an account.','Shopp') );

			} else { // need to find an usuable login
				list($handle, ) = explode('@', $_POST['email']);
				if( ! username_exists($handle) ) $_POST['loginname'] = $handle;

				$handle = $_POST['firstname'] . substr($_POST['lastname'], 0, 1);
				if( ! isset($_POST['loginname']) && ! username_exists($handle)) $_POST['loginname'] = $handle;

				$handle = substr($_POST['firstname'],0,1) . $_POST['lastname'];
				if( ! isset($_POST['loginname']) && ! username_exists($handle)) $_POST['loginname'] = $handle;

				$handle .= rand(1000,9999);
				if( ! isset($_POST['loginname']) && ! username_exists($handle)) $_POST['loginname'] = $handle;

				if( apply_filters('shopp_login_required', ! isset($_POST['loginname'])) )
					return shopp_add_error( __('A login is not available for creation with the information you provided. Please try a different email address or name, or try logging in if you previously created an account.','Shopp') );

				$Customer->loginname = $_POST['loginname']; // Update the customer login name
			}

			shopp_debug('Login set to '. $_POST['loginname'] . ' for WordPress account creation.');

			$ExistingCustomer = new Customer($_POST['email'], 'email');

			if ( $Customer->guest && ! empty($ExistingCustomer->id) ) $Customer->id = $ExistingCustomer->id;
			if ( apply_filters('shopp_email_exists', ! $Customer->guest && (email_exists($_POST['email']) || ! empty($ExistingCustomer->id))) )
				return shopp_add_error( __('The email address you entered is already in use. Try logging in if you previously created an account, or enter another email address to create your new account.','Shopp') );
		} elseif ( 'shopp' == shopp_setting('account_system') && ! $Customer->logged_in() ) {
			$ExistingCustomer = new Customer($_POST['email'],'email');
			if ( apply_filters('shopp_email_exists', ! empty($ExistingCustomer->id)) )
				return shopp_add_error( __('The email address you entered is already in use. Try logging in if you previously created an account, or enter another email address to create a new account.','Shopp') );
		}

		// Validate WP account
		if ( apply_filters('shopp_login_required', (isset($_POST['loginname']) && empty($_POST['loginname']))) )
			return shopp_add_error( __('You must enter a login name for your account.','Shopp') );

		if ( isset($_POST['loginname']) ) {
			require ABSPATH . '/wp-includes/registration.php';
			if ( apply_filters('shopp_login_valid', ( ! validate_username($_POST['loginname']))) ) {
				$sanitized = sanitize_user( $_POST['loginname'], true );
				$illegal = array_diff( str_split($_POST['loginname']), str_split($sanitized) );
				return shopp_add_error( sprintf(__('The login name provided includes invalid characters: %s','Shopp'), esc_html(join(' ',$illegal))) );
			}

			if ( apply_filters('shopp_login_exists', username_exists($_POST['loginname'])) )
				return shopp_add_error( __('The login name is already in use. Try logging in if you previously created that account, or enter another login name for your new account.','Shopp') );
		}

		return true;

	}

	public static function passwords () {

		if ( isset($_POST['password']) ) {

			if ( apply_filters('shopp_passwords_required', (empty($_POST['password']) || empty($_POST['confirm-password']))) )
				return shopp_add_error( __('You must provide a password for your account and confirm it to ensure correct spelling.','Shopp') );

			if ( apply_filters('shopp_password_mismatch', ($_POST['password'] != $_POST['confirm-password'])) ) {
				$_POST['password'] = ''; $_POST['confirm-password'] = '';
				return shopp_add_error( __('The passwords you entered do not match. Please re-enter your passwords.','Shopp') );
			}

		}

		return true;

	}

	public static function billaddress () {

		if ( apply_filters('shopp_billing_address_required', isset($_POST['billing']['address'])
				&& ( empty($_POST['billing']['address']) || strlen($_POST['billing']['address']) < 4)) )
			return shopp_add_error( __('You must enter a valid street address for your billing information.','Shopp') );

		if ( apply_filters('shopp_billing_postcode_required', isset($_POST['billing']['postcode']) && empty($_POST['billing']['postcode'])) )
			return shopp_add_error( __('You must enter a valid postal code for your billing information.','Shopp ') );

		if ( apply_filters('shopp_billing_country_required', isset($_POST['billing']['country']) && empty($_POST['billing']['country']) ))
			return shopp_add_error( __('You must select a country for your billing information.','Shopp') );

		if ( apply_filters('shopp_billing_locale_required',isset($_POST['billing']['locale']) && empty($_POST['billing']['locale'])))
			return shopp_add_error( __('You must select a local jursidiction for tax purposes.','Shopp') );

		return true;
	}

	public static function shipaddress () {

		if ( apply_filters('shopp_shipping_address_required', isset($_POST['shipping']['address'])
				&& ( empty($_POST['shipping']['address']) || strlen($_POST['shipping']['address']) < 4)) )
			return shopp_add_error( __('You must enter a valid street address for your shipping address.','Shopp') );

		if ( apply_filters('shopp_shipping_postcode_required', isset($_POST['shipping']['postcode']) && empty($_POST['shipping']['postcode'])) )
			return shopp_add_error( __('You must enter a valid postal code for your shipping address.','Shopp') );

		if ( apply_filters('shopp_shipping_country_required', isset($_POST['shipping']['country']) && empty($_POST['shipping']['country']) ))
			return shopp_add_error( __('You must select a country for your shipping address.','Shopp') );

		return true;
	}

	public static function paycard () {

		if ( apply_filters('shopp_billing_card_required', isset($_POST['billing']['card']) && empty($_POST['billing']['card'])) )
			return shopp_add_error( __('You did not provide a credit card number.','Shopp') );

		if ( apply_filters('shopp_billing_cardtype_required', isset($_POST['billing']['card']) && empty($_POST['billing']['cardtype'])) )
			return shopp_add_error( __('You did not select a credit card type.','Shopp') );

		$card = Lookup::paycard( strtolower($_POST['billing']['cardtype']) );

		if ( apply_filters('shopp_billing_valid_cardtype', ! $card ))
			return shopp_add_error( __('The credit card type you provided is invalid.','Shopp') );

		if ( apply_filters('shopp_billing_valid_card', ! $card->validate($_POST['billing']['card']) ))
			return shopp_add_error( __('The credit card number you provided is invalid.','Shopp') );

		if ( apply_filters('shopp_billing_cardexpires_month_required',empty($_POST['billing']['cardexpires-mm'])) )
			return shopp_add_error( __('You did not enter the month the credit card expires.','Shopp') );

		if ( apply_filters('shopp_billing_cardexpires_year_required',empty($_POST['billing']['cardexpires-yy'])) )
			return shopp_add_error( __('You did not enter the year the credit card expires.','Shopp') );

		if ( apply_filters('shopp_billing_card_expired', intval($_POST['billing']['cardexpires-yy']) <= intval(date('y')) || intval($_POST['billing']['cardexpires-mm']) < intval(date('n')) ) )
			return shopp_add_error( __('The credit card expiration date you provided has already expired.','Shopp') );

		if ( apply_filters('shopp_billing_cardholder_required',strlen($_POST['billing']['cardholder']) < 2) )
			return shopp_add_error( __('You did not enter the name on the credit card you provided.','Shopp') );

		if ( apply_filters('shopp_billing_cvv_required',strlen($_POST['billing']['cvv']) < 3) )
			return shopp_add_error( __('You did not enter a valid security ID for the card you provided. The security ID is a 3 or 4 digit number found on the back of the credit card.','Shopp') );

		return true;
	}

	const RFC822_EMAIL = '([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22))*\\x40([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d))*';

}
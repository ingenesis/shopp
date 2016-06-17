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

	public static function names ( $result ) {

		if ( apply_filters('shopp_firstname_required', empty($_POST['firstname'])) )
			return shopp_add_error( Shopp::__('You must provide your first name.') );

		if ( apply_filters('shopp_lastname_required', empty($_POST['lastname'])) )
			return shopp_add_error( Shopp::__('You must provide your last name.') );

        return $result;

	}

	public static function email ( $result ) {

		if ( apply_filters('shopp_email_valid', empty($_POST['email']) || false === filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) )
			return shopp_add_error(Shopp::__('You must provide a valid e-mail address.'));

        return $result;
	}

	public static function login ( $result ) {
		$Customer = ShoppOrder()->Customer;

		if ( $Customer->loggedin() ) return $result;

		$accounts = shopp_setting('account_system');

		$pleaselogin = ' ' . Shopp::__('If you have an account with us, please login now.');

		// This specific !isset condition checks if the loginname is not provided
		// If no loginname is provided, but an account system is used, we need to
		// generate a new login name for the customer
		if ( 'wordpress' == $accounts && ! isset($_POST['loginname']) ) {

			ShoppLoginGenerator::object();
			$_POST['loginname'] = ShoppLoginGenerator::name();

			if ( apply_filters('shopp_login_required', empty($_POST['loginname'])) )
				return shopp_add_error( Shopp::__('A login could not be created with the information you provided. Enter a different name or email address.') . $pleaselogin );

			shopp_debug('Login set to '. $_POST['loginname'] . ' for WordPress account creation.');

		}

		// Validate unique email address for new account
		if ( in_array($accounts, array('wordpress', 'shopp')) && ! $Customer->session(ShoppCustomer::GUEST) ) {
			$ShoppCustomer = new ShoppCustomer($_POST['email'], 'email');
			if ( apply_filters('shopp_email_exists', ( 'wordpress' == $accounts ? email_exists($_POST['email']) : $ShoppCustomer->exists() )) )
				return shopp_add_error( Shopp::__('The email address you entered is already in use. Enter a different email address to create a new account.') . $pleaselogin );

		}

		// Validate WP login
		if ( isset($_POST['loginname']) ) {

			if ( apply_filters('shopp_login_required', empty($_POST['loginname'])) )
				return shopp_add_error( Shopp::__('You must enter a login name for your account.') );

			if ( apply_filters('shopp_login_valid', ( ! validate_username($_POST['loginname']))) ) {
				$sanitized = sanitize_user( $_POST['loginname'], true );
				$illegal = array_diff( str_split($_POST['loginname']), str_split($sanitized) );
				return shopp_add_error( Shopp::__('The login name provided includes invalid characters: %s', esc_html(join(' ', $illegal)) ));
			}

			if ( apply_filters('shopp_login_exists', username_exists($_POST['loginname'])) )
				return shopp_add_error( Shopp::__('&quot;%s&quot; is already in use. Enter a different login name to create a new account.', esc_html($_POST['loginname'])) . $pleaselogin );
		}

        return $result;
	}

	public static function passwords ( $result ) {

		if ( ! isset($_POST['password']) ) return $result;

		if ( apply_filters('shopp_passwords_required', (empty($_POST['password']) || empty($_POST['confirm-password']))) )
			return shopp_add_error( Shopp::__('You must provide a password for your account and confirm it for correct spelling.') );

		if ( apply_filters('shopp_password_mismatch', $_POST['password'] != $_POST['confirm-password']) ) {
			$_POST['password'] = ''; $_POST['confirm-password'] = '';
			return shopp_add_error( Shopp::__('The passwords you entered do not match. Please re-enter your passwords.') );
		}

        return $result;
	}

	public static function billaddress ( $result ) {
		$fields = ( isset($_POST['billing']) ) ? $_POST['billing'] : array();

		if ( apply_filters('shopp_billing_address_required', isset($fields['address']) && ( empty($fields['address']) || strlen($fields['address']) < 4)) )
			return shopp_add_error( Shopp::__('You must enter a valid street address for your billing information.') );

		if ( apply_filters('shopp_billing_postcode_required', isset($fields['postcode']) && empty($fields['postcode'])) )
			return shopp_add_error( Shopp::__('You must enter a valid postal code for your billing information.') );

		if ( apply_filters('shopp_billing_country_required', isset($fields['country']) && empty($fields['country']) ) )
			return shopp_add_error( Shopp::__('You must select a country for your billing information.') );

		if ( apply_filters('shopp_billing_locale_required', isset($fields['locale']) && empty($_POST['billing']['locale'])) )
			return shopp_add_error( Shopp::__('You must select a local jurisdiction for tax purposes.') );

        return $result;
	}

	public static function shipaddress ( $result ) {
		$fields = ( isset($_POST['shipping']) ) ? $_POST['shipping'] : array();

		if ( apply_filters('shopp_shipping_address_required', isset($fields['address'])	&& ( empty($fields['address']) || strlen($fields['address']) < 4 )) )
			return shopp_add_error( Shopp::__('You must enter a valid street address for your shipping address.') );

		if ( apply_filters('shopp_shipping_postcode_required', isset($fields['postcode']) && empty($fields['postcode'])) )
			return shopp_add_error( Shopp::__('You must enter a valid postal code for your shipping address.') );

		if ( apply_filters('shopp_shipping_country_required', isset($fields['country']) && empty($fields['country'])) )
			return shopp_add_error( Shopp::__('You must select a country for your shipping address.') );

        return $result;
	}

	public static function paycard ( $result ) {

		$fields = ( isset($_POST['billing']) ) ? $_POST['billing'] : array();

		if ( apply_filters('shopp_billing_card_required', isset($fields['card']) && empty($fields['card'])) )
			return shopp_add_error( Shopp::__('You did not provide a credit card number.') );

		if ( apply_filters('shopp_billing_cardtype_required', isset($fields['cardtype']) && empty($fields['cardtype'])) )
			return shopp_add_error( Shopp::__('You did not select a credit card type.') );

		$card = Lookup::paycard( strtolower($fields['cardtype']) );

		// Skip validating payment details for purchases not requiring a
		// payment (credit) card including free orders, remote checkout systems, etc
		if ( false === $card ) return $result;

		if ( apply_filters('shopp_billing_valid_card', ! $card->validate($fields['card']) ))
			return shopp_add_error( Shopp::__('The credit card number you provided is invalid.') );

		if ( apply_filters('shopp_billing_cardexpires_month_required', empty($fields['cardexpires-mm'])) )
			return shopp_add_error( Shopp::__('You did not enter the month the credit card expires.') );

		if ( apply_filters('shopp_billing_cardexpires_year_required', empty($fields['cardexpires-yy'])) )
			return shopp_add_error( Shopp::__('You did not enter the year the credit card expires.') );

		if ( apply_filters('shopp_billing_card_expired',
				intval($fields['cardexpires-yy']) < intval(date('y')) // Less than this year or equal to this year and less than this month
				|| ( intval($fields['cardexpires-yy']) == intval(date('y')) && intval($fields['cardexpires-mm']) < intval(date('n')) )
			) )
			return shopp_add_error( Shopp::__('The credit card expiration date you provided has already expired.') );

		if ( apply_filters('shopp_billing_cvv_required',strlen($fields['cvv']) < 3) )
			return shopp_add_error( Shopp::__('You did not enter a valid security ID for the card you provided. The security ID is a 3 or 4 digit number found on the back of the credit card.') );

        return $result;
	}

	public static function data ( $result ) {

		if ( ! isset($_POST['data']) ) return $result;

		$fields = $_POST['data'];

		if ( apply_filters('shopp_clickwrap_required', isset($fields['clickwrap']) && 'agreed' !== $fields['clickwrap']) )
			return shopp_add_error( Shopp::__('You must agree to the terms of sale.') );

        return $result;
	}

}
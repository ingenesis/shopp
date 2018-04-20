<?php
/**
 * AdminSettings.php
 *
 * Routes the admin setting screen requests
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminSettings extends ShoppAdminPostController {

	protected $ui = 'settings';

	/**
	 * Used to route control to screen controllers
	 *
	 * @since 1.4
	 * @return string Return the class name as a string of the subordinate screen controller
	 **/
	protected function route() {
		switch ( $this->slug() ) {
			case 'advanced':     return 'ShoppScreenAdvanced';
			case 'checkout':     return 'ShoppScreenCheckout';
			case 'downloads':    return 'ShoppScreenDownloads';
			case 'images':       return 'ShoppScreenImages';
			case 'log':          return 'ShoppScreenLog';
			case 'orders':       return 'ShoppScreenOrdersManagement';
			case 'pages':        return 'ShoppScreenPages';
			case 'payments':     return 'ShoppScreenPayments';
			case 'presentation': return 'ShoppScreenPresentation';
			case 'shipping':     return 'ShoppScreenShipping';
			case 'boxes':   	 return 'ShoppScreenShipmentBoxes';
			case 'storage':      return 'ShoppScreenStorage';
			case 'taxes':        return 'ShoppScreenTaxes';
			default:             return 'ShoppScreenSetup';
		}
	}

	/**
	 * Provides the slug for this page request
	 *
	 * @since 1.4
	 * @return string The page request slug
	 **/
	protected function slug() {
		$page = strtolower($this->request('page'));
		return substr($page, strrpos($page, '-') + 1);
	}

}
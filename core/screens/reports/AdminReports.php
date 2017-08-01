<?php
/**
 * AdminReports.php
 *
 * Routes admin report screen requests.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Reports
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminReports extends ShoppAdminController {

	protected $ui = 'reports';

	protected function route () {
		return 'ShoppScreenReports';
	}

}

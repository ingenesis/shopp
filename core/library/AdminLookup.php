<?php
/**
 * AdminLookup.php
 *
 * Super-controller providing Shopp integration with the WordPress Admin
 *
 * @copyright Ingenesis Limited, January 2010-2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Flow/Admin
 * @version   1.4
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * The Shopp admin flow controller.
 *
 * @since 1.1
 **/
class ShoppAdminLookup {

	/**
	 * Provides a list of supported shipping packaging methods
	 *
	 * @since 1.5
	 *
	 * @return array List of packaging methods
	 **/
	public static function daterange_options() {
		$_ = array(
			'all'         => Shopp::__('Show All'),
			'today'       => Shopp::__('Today'),
			'week'        => Shopp::__('This Week'),
			'month'       => Shopp::__('This Month'),
			'quarter'     => Shopp::__('This Quarter'),
			'year'        => Shopp::__('This Year'),
			'yesterday'   => Shopp::__('Yesterday'),
			'lastweek'    => Shopp::__('Last Week'),
			'last30'      => Shopp::__('Last 30 Days'),
			'last90'      => Shopp::__('Last 3 Months'),
			'lastmonth'   => Shopp::__('Last Month'),
			'lastquarter' => Shopp::__('Last Quarter'),
			'lastyear'    => Shopp::__('Last Year'),
			'lastexport'  => Shopp::__('Last Export'),
			'custom'      => Shopp::__('Custom Dates')
		);
		return apply_filters('shopp_daterange_options', $_);
	}

}
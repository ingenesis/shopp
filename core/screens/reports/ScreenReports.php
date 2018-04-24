<?php
/**
 * ScreenReports.php
 *
 * Controller for the reports screen
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Reports
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenReports extends ShoppScreenController {

	public $records = array();
	public $count = false;

	protected $ui = 'reports';

	private $view = 'dashboard';
	private $options = array();		// Processed options
	private $Report = false;

	/**
	 * Provides a list of available reports
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of reports
	 **/
	static function reports () {
		return apply_filters('shopp_reports',array(
			'sales' => array( 'class' => 'SalesReport', 'name' => __('Sales Report','Shopp'), 'label' => __('Sales','Shopp') ),
			'tax' => array( 'class' => 'TaxReport', 'name' => __('Tax Report','Shopp'), 'label' => __('Taxes','Shopp') ),
			'shipping' => array( 'class' => 'ShippingReport', 'name' => __('Shipping Report','Shopp'), 'label' => __('Shipping','Shopp') ),
			'discounts' => array( 'class' => 'DiscountsReport', 'name' => __('Discounts Report','Shopp'), 'label' => __('Discounts','Shopp') ),
			'customers' => array( 'class' => 'CustomersReport', 'name' => __('Customers Report','Shopp'), 'label' => __('Customers','Shopp') ),
			'locations' => array( 'class' => 'LocationsReport', 'name' => __('Locations Report','Shopp'), 'label' => __('Locations','Shopp') ),
			'products' => array( 'class' => 'ProductsReport', 'name' => __('Products Report','Shopp'), 'label' => __('Products','Shopp') ),
			'paytype' => array( 'class' => 'PaymentTypesReport', 'name' => __('Payment Types Report','Shopp'), 'label' => __('Payment Types','Shopp') ),
		));
	}

	/**
	 * Registers extra conditional reports
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $reports The list of registered reports
	 * @return array The modified list of registered reports
	 **/
	static function xreports ($reports) {
		if ( shopp_setting_enabled('inventory') )
			$reports['inventory'] = array( 'class' => 'InventoryReport', 'name' => __('Inventory Report','Shopp'), 'label' => __('Inventory','Shopp') );
		return $reports;
	}

	/**
	 * Parses the request for options
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array The defined request options
	 **/
	public static function options () {
		$defaults = array(
			'start' => date('n/j/Y',mktime(0,0,0)),
			'end' => date('n/j/Y',mktime(23,59,59)),
			'range' => '',
			'scale' => 'day',
			'report' => 'sales',
			'paged' => 1,
			'per_page' => 100,
			'num_pages' => 1
		);

		$today = mktime(23,59,59);

		$options = wp_parse_args($_GET,$defaults);

		if (!empty($options['start'])) {
			$startdate = $options['start'];
			list($sm,$sd,$sy) = explode("/",$startdate);
			$options['starts'] = mktime(0,0,0,$sm,$sd,$sy);
			date('F j Y',$options['starts']);
		}

		if (!empty($options['end'])) {
			$enddate = $options['end'];
			list($em,$ed,$ey) = explode("/",$enddate);
			$options['ends'] = mktime(23,59,59,$em,$ed,$ey);
			if ($options['ends'] > $today) $options['ends'] = $today;
		}

		$daterange = $options['ends'] - $options['starts'];

		if ( $daterange <= 86400 ) $_GET['scale'] = $options['scale'] = 'hour';

		$options['daterange'] = $daterange;

		$screen = get_current_screen();
		$options['screen'] = $screen->id;

		return $options;
	}

	/**
	 * Handles report loading
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return Report The loaded Report object
	 **/
	static function report () {
		$options = self::options();
		extract($options, EXTR_SKIP);

		$reports = self::reports();

		// Load the report
		$report = isset($_GET['report']) ? $_GET['report'] : 'sales';
		if ( empty($reports[ $report ]['class']) )
			return wp_die(Shopp::__('The requested report does not exist.'));

		$ReportClass = $reports[ $report ]['class'];
		$Report = new $ReportClass($options);
		$Report->load();

		return $Report;

	}

	/**
	 * Loads the report for the report admin screen
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function load () {
		if ( ! current_user_can('shopp_financials') ) return;
		add_filter('shopp_reports', array(__CLASS__, 'xreports'));
		$this->options = self::options();
		$this->Report = self::report();
	}

	public function assets () {
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('daterange');
		shopp_enqueue_script('reports');
	}

	/**
	 * Renders the admin screen
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function screen () {
		if ( ! current_user_can('shopp_financials') )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		extract($this->options, EXTR_SKIP);

		$Report = $this->Report;
		$Report->pagination();
		$ListTable = ShoppUI::table_set_pagination ($screen, $Report->total, $Report->pages, $per_page );

		$ranges = array(
			'all' => __('Show All Orders','Shopp'),
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
			'custom' => __('Custom Dates','Shopp')
			);

		$exports = array(
			'tab' => __('Tab-separated.txt','Shopp'),
			'csv' => __('Comma-separated.csv','Shopp'),
			);

		$format = shopp_setting('report_format');
		if ( ! $format ) $format = 'tab';

		$columns = array_merge(ShoppPurchase::exportcolumns(), ShoppPurchased::exportcolumns());
		$selected = shopp_setting('purchaselog_columns');
		if (empty($selected)) $selected = array_keys($columns);

		$reports = self::reports();

		$report_title = isset($reports[ $report ])? $reports[ $report ]['name'] : __('Report','Shopp');

		include $this->ui('reports.php');

	}

} 
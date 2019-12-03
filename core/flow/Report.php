<?php
/**
 * Report.php
 *
 * Flow controller for report interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, June 2012
 * @package shopp
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Report
 *
 * @package shopp
 * @since 1.3
 * @author Jonathan Davis
 **/
class ShoppAdminReport extends ShoppAdminController {

	public $records = array();
	public $count = false;

	private $view = 'dashboard';
	protected $ui = 'reports';

	private $defaults = array();	// Default request options
	private $options = array();		// Processed options
	private $Report = false;

	/**
	 * Service constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function __construct () {
		parent::__construct();

		shopp_enqueue_script('calendar');
		shopp_enqueue_script('daterange');
		shopp_enqueue_script('reports');

		add_filter('shopp_reports', array(__CLASS__, 'xreports'));
		add_action('load-' . $this->screen, array($this, 'loader'));
	}

	/**
	 * Provides a list of available reports
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array List of reports
	 **/
	static function reports () {
		return apply_filters('shopp_reports', array(
			'sales'     => array( 'class' => 'SalesReport',        'name' => Shopp::__('Sales Report'),         'label' => Shopp::__('Sales') ),
			'tax'       => array( 'class' => 'TaxReport',          'name' => Shopp::__('Tax Report'),           'label' => Shopp::__('Taxes') ),
			'shipping'  => array( 'class' => 'ShippingReport',     'name' => Shopp::__('Shipping Report'),      'label' => Shopp::__('Shipping') ),
			'discounts' => array( 'class' => 'DiscountsReport',    'name' => Shopp::__('Discounts Report'),     'label' => Shopp::__('Discounts') ),
			'customers' => array( 'class' => 'CustomersReport',    'name' => Shopp::__('Customers Report'),     'label' => Shopp::__('Customers') ),
			'locations' => array( 'class' => 'LocationsReport',    'name' => Shopp::__('Locations Report'),     'label' => Shopp::__('Locations') ),
			'products'  => array( 'class' => 'ProductsReport',     'name' => Shopp::__('Products Report'),      'label' => Shopp::__('Products') ),
			'paytype'   => array( 'class' => 'PaymentTypesReport', 'name' => Shopp::__('Payment Types Report'), 'label' => Shopp::__('Payment Types') ),
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
	static function xreports ( $reports ) {
		if ( shopp_setting_enabled('inventory') )
			$reports['inventory'] = array( 'class' => 'InventoryReport', 'name' => Shopp::__('Inventory Report'), 'label' => Shopp::__('Inventory') );
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
	static function request () {
		$defaults = array(
			'start'     => date('n/j/Y', mktime(0, 0, 0)),
			'end'       => date('n/j/Y', mktime(23, 59, 59)),
			'range'     => '',
			'scale'     => 'day',
			'report'    => 'sales',
			'paged'     => 1,
			'per_page'  => 100,
			'num_pages' => 1
		);

		$today = mktime(23, 59, 59);

		$options = wp_parse_args($_GET, $defaults);

		if ( ! empty($options['start']) ) {
			$startdate          = $options['start'];
			list($sm, $sd, $sy) = explode('/', $startdate);
			$options['starts']  = mktime(0, 0, 0, $sm, $sd, $sy);
			date('F j Y', $options['starts']);
		}

		if ( ! empty($options['end']) ) {
			$enddate            = $options['end'];
			list($em, $ed, $ey) = explode("/", $enddate);
			$options['ends']    = mktime(23, 59, 59, $em, $ed, $ey);
			if ( $options['ends'] > $today ) $options['ends'] = $today;
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
	static function load () {
		$options = self::request();
		extract($options, EXTR_SKIP);

		$reports = self::reports();

		// Load the report
		$report = isset($_GET['report']) ? $_GET['report'] : 'sales';

		if ( empty($reports[ $report ]['class']) )
			return wp_die(Shopp::__('The requested report does not exist.'));

		$ReportClass = $reports[ $report ]['class'];
		$Report = new $ReportClass( $options );
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
	public function loader () {
		if ( ! current_user_can('shopp_financials') ) return;
		$this->options = self::request();
		$this->Report = self::load();
	}

	/**
	 * Renders the admin screen
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function admin () {
		if ( ! current_user_can('shopp_financials') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		extract($this->options, EXTR_SKIP);

		$Report = $this->Report;
		$Report->pagination();
		$ListTable = ShoppUI::table_set_pagination ($this->screen, $Report->total, $Report->pages, $per_page );

		$ranges = array(
			'all'           => Shopp::__('Show All Orders'),
			'today'         => Shopp::__('Today'),
			'week'          => Shopp::__('This Week'),
			'month'         => Shopp::__('This Month'),
			'quarter'       => Shopp::__('This Quarter'),
			'year'          => Shopp::__('This Year'),
			'yesterday'     => Shopp::__('Yesterday'),
			'lastweek'      => Shopp::__('Last Week'),
			'last30'        => Shopp::__('Last 30 Days'),
			'last90'        => Shopp::__('Last 3 Months'),
			'lastmonth'     => Shopp::__('Last Month'),
			'lastquarter'   => Shopp::__('Last Quarter'),
			'lastyear'      => Shopp::__('Last Year'),
			'custom'        => Shopp::__('Custom Dates')
			);

		$exports = array(
			'tab' => Shopp::__('Tab-separated.txt'),
			'csv' => Shopp::__('Comma-separated.csv'),
			);

		$format = shopp_setting('report_format');
		if ( ! $format ) $format = 'tab';

		$columns  = array_merge(ShoppPurchase::exportcolumns(), ShoppPurchased::exportcolumns());
		$selected = shopp_setting('purchaselog_columns');
		if ( empty($selected) ) $selected = array_keys($columns);

		$reports = self::reports();

		$report_title = isset($reports[ $report ]) ? $reports[ $report ]['name'] : Shopp::__('Report');

		include $this->ui('reports.php');

	}

} // end class Report


/**
 * Defines the required interfaces for a report class
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package reports
 **/
interface ShoppReport {
	public function query();
	public function setup();
	public function table();
}

/**
 * ShoppReportFramework
 *
 * Provides the base functionality needed to rapidly build reports
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package reports
 **/
abstract class ShoppReportFramework {

	// Settings
	public $periods = false;		// A time period series report

	public $screen = false;			// The current WP screen
	public $Chart  = false;			// The report chart (if any)


	public $options = array();		// Options for the report
	public $data    = array();		// The processed report data
	public $totals  = false;		// The processed totals for the report


	public $range = false;			// Range of values in the report
	public $total = 0;				// Total number of records in the report
	public $pages = 1;				// Number of pages for the report
	public $daterange = false;

	private $columns = array();		// Helper to track columns in a report

	public function __construct ($request = array()) {
		$this->options = $request;
		$this->screen  = $this->options['screen'];
		$this->totals  = new StdClass();

		add_action('shopp_report_filter_controls', array($this, 'filters'));
		add_action("manage_{$this->screen}_columns", array($this, 'screencolumns'), 0);
		add_action("manage_{$this->screen}_sortable_columns", array($this, 'sortcolumns'), 0);
	}

	/**
	 * Load the report data
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function load () {
		extract($this->options);

		// Map out time period based reports with index matching keys and period values
		if ( $this->periods )
			$this->timereport($starts, $ends, $scale);

		$this->setup();

		$query = $this->query();

		if ( empty($query) ) return;

		$loaded = sDB::query( $query, 'array', array($this, 'process') );

		if ( $this->periods && $this->Chart ) {

			foreach ( $this->data as $index => &$record ) {

				if ( count(get_object_vars($record)) <= 1 ) {

					foreach ( $this->columns as $column )
						$record->$column = null;
				}

				foreach ( $this->chartseries as $series => $column ) {
					$data = isset($record->$column) ? $record->$column : 0;
					$this->chartdata($series, $record->period, $data);
				}
			}
		} else {
			$this->data = $loaded;
			$this->total = count($loaded);
		}

	}

	/**
	 * Processes loaded records into report data, and if necessary sends it to a chart series
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $records A reference to the working result record set
	 * @param object $record Loaded record from the query
	 * @return void
	 **/
	public function process ( &$records, &$record, $Object = false, $index = 'id', $collate = false ) {
		$index = isset($record->$index) ? $record->$index : '!NO_INDEX!';

		$columns = get_object_vars($record);
		if ( empty($this->columns) ) { // Map out the columns that are used
			$this->columns = array_diff(array_keys($columns), array('id', 'period'));
		}

		foreach ( $columns as $column => $value ) {

			if ( is_numeric($value) && 0 !== $value ) {

				if ( ! isset($this->totals->$column) ) $this->totals->$column = 0;

				$this->totals->$column += $value;
			} else $this->totals->$column = null;
		}

		if ( $this->periods && isset($this->data[ $index ]) ) {
			$record->period = $this->data[ $index ]->period;
			$this->data[ $index ] = $record;

			return;
		}

		if ( $collate ) {

			if ( ! isset($records[ $index ]) ) $records[ $index ] = $record;

			$records[ $index ][] = $record;
			return;
		}

		$id = count($records);
		$records[ $index ] = $record;

		$this->chartseries(false, array('index' => $id, 'record' => $record));
	}

	/**
	 * Calculates the number of pages needed
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function pagination () {
		extract($this->options,EXTR_SKIP);
		$this->pages   = ceil($this->total / $per_page);
		$_GET['paged'] = $this->options['paged'] = min($paged,$this->pages);
	}

	/**
	 * Initializes a time period report
	 *
	 * This maps out a list of calendar dates with periodical timestamps
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $starts Starting timestamp
	 * @param int $ends Ending timestamp
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return void
	 **/
	public function timereport ($starts, $ends, $scale) {
		$this->total = $this->range($starts, $ends, $scale);
		$i = 0;
		while ($i < $this->total) {
			$record = new StdClass();
			list ($index, $record->period) = self::timeindex($i++, $starts, $scale);
			$this->data[ $index ] = $record;
		}
	}

	/**
	 * Generates a timestamp with a date index value
	 *
	 * Timestamps are generated for each period based on the starting date and scale provided.
	 * The date index value is generated to match the query datetime id columns generated
	 * by the timecolumn() method below.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $i The period iteration
	 * @param int $starts The starting timestamp
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return array The date index and timestamp pair
	 **/
	static function timeindex ( $i, $starts, $scale ) {
		$month = date('n', $starts); $day = date('j', $starts); $year = date('Y', $starts);
		$index = $i;
		switch (strtolower($scale)) {
			case 'hour': $ts = mktime($i, 0, 0, $month, $day, $year); break;
			case 'week':
				$ts = mktime(0, 0, 0, $month, $day+($i*7), $year);
				$index = sprintf('%s %s', (int)date('W', $ts), date('Y', $ts));
				break;
			case 'month':
				$ts = mktime(0, 0, 0, $month+$i, 1, $year);
				$index = sprintf('%s %s', date('n', $ts), date('Y', $ts));
				break;
			case 'year':
				$ts = mktime(0, 0, 0, 1, 1, $year+$i);
				$index = sprintf('%s', date('Y', $ts));
				break;
			default:
				$ts = mktime(0, 0, 0, $month, $day+$i, $year);
				$index = sprintf('%s %s %s', date('j', $ts), date('n', $ts), date('Y', $ts));
				break;
		}

		return array($index, $ts);
	}

	/**
	 * Builds a date index SQL column
	 *
	 * This creates the SQL statement fragment for requesting a column that matches the
	 * date indexes generated by the timeindex() method above.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $column A datetime column value
	 * @return string Date index column SQL statement
	 **/
	public function timecolumn ( $column ) {
		switch ( strtolower($this->options['scale']) ) {
			case 'hour':	$_ = "HOUR($column)"; break;
			case 'week':	$_ = "WEEK($column,3), ' ', YEAR($column)"; break;
			case 'month':	$_ = "MONTH($column), ' ', YEAR($column)"; break;
			case 'year':	$_ = "YEAR($column)"; break;
			default:		$_ = "DAY($column), ' ', MONTH($column), ' ', YEAR($column)";
		}
		return $_;
	}

	/**
	 * Determines the range of periods between two dates for a given scale
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $starts The starting timestamp
	 * @param int $ends The ending timestamp
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return int The number of periods
	 **/
	public function range ( $starts, $ends, $scale = 'day') {
		$oneday = 86400;
		$years = date('Y', $ends)-date('Y', $starts);
		switch ( strtolower($scale) ) {
			case 'week':
				// Find the timestamp for the first day of the start date's week
				$startweekday = date('w', $starts);
				$startweekdate = $starts-($startweekday*86400);

				// Find the timestamp for the last day of the end date's' week
				$endweekday = date('w', $ends);
				$endweekdate = $ends+((6-$endweekday)*86400);

				$starts_week = (int)date('W', $startweekdate);
				$ends_week =  (int)date('W', $endweekdate);
				if ( $starts_week < 0 ) $starts_week += 52;
				elseif ($starts_week > $ends_week) $starts_week -= 52;

				return ($years*52)+$ends_week - $starts_week;
			case 'month':
				$starts_month = date('n', $starts);
				$ends_month = date('n', $ends);
				if ( $starts_month > $ends_month ) $starts_month -= 12;
				return (12*$years)+$ends_month-$starts_month+1;
			case 'year': return $years+1;
			case 'hour': return 24; break;
			default:
			case 'day': return ceil(($ends-$starts)/$oneday);
		}
	}

	/**
	 * Builds a readable week range string
	 *
	 * Example: December 1 - December 7 2008
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $ts A weekday timestamp
	 * @param array $formats The starting and ending date() formats
	 * @return string Formatted week range label
	 **/
	static function weekrange ( $ts, array $formats = array('F j', 'F j Y') ) {
		$weekday = date('w', $ts);
		$startweek = $ts - ( $weekday * 86400 );
		$endweek = $startweek + ( 6 * 86400 );

		return sprintf('%s - %s', date($formats[0], $startweek), date($formats[1], $endweek));
	}

	/**
	 * Standard renderer for period columns
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param object $data The source data record
	 * @param string $column The column key name
	 * @param string $title The column title label
	 * @param array $options The options for this report
	 * @return void
	 **/
	static function period ( $data, $column, $title, array $options ) {

		if ( Shopp::__('Total') == $data->period ) { echo Shopp::__('Total'); return; }
		if ( Shopp::__('Average') == $data->period ) { echo Shopp::__('Average'); return; }

		switch ( strtolower($options['scale']) ) {
			case 'hour': echo date('ga', $data->period); break;
			case 'day': echo date('l, F j, Y', $data->period); break;
			case 'week': echo ShoppReportFramework::weekrange($data->period); break;
			case 'month': echo date('F Y', $data->period); break;
			case 'year': echo date('Y', $data->period); break;
			default: echo $data->period; break;
		}
	}

	/**
	 * Standard export renderer for period columns
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param object $data The source data record
	 * @param string $column The column key name
	 * @param string $title The column title label
	 * @param array $options The options for this report
	 * @return void
	 **/
	static function export_period ($data, $column, $title, $options) {
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');
		$datetime    = "$date_format $time_format";

		switch (strtolower($options['scale'])) {
			case 'day': echo date($date_format, $data->period); break;
			case 'week': echo ShoppReportFramework::weekrange($data->period, array($date_format,$date_format)); break;
			default: echo date($datetime, $data->period); break;
		}
	}

	/**
	 * Returns a list of columns for this report
	 *
	 * This method is a placehoder. Columns should be specified in the concrete report subclass.
	 *
	 * The array should be defined as an associative array with column keys as the array key and
	 * a translatable column title as the value:
	 *
	 * array('orders' => Shopp::__('Orders'));
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array The list of column keys and column title labels
	 **/
	public function columns () { return array(); }

	/**
	 * Returns a list of columns for this report that should be excluded
	 * from 'Average' and 'Total' calculations in the report table
	 *
	 * This method is a placehoder. Columns should be specified in the concrete report subclass.
	 *
	 * The array should be defined as an associative array with column keys as the array key and
	 * a translatable column title as the value:
	 *
	 * array('orders' => Shopp::__('Orders'));
	 *
	 * @author Webshop1310
	 * @since 1.3
	 *
	 * @return array The list of column keys and column title labels
	 **/
	public function excludecolumns () { return array(); }


	/**
	 * Registers the report columns to the WP screen
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function screencolumns () { ShoppUI::register_column_headers($this->screen, $this->columns()); }

	/**
	 * Specifies columns that are sortable
	 *
	 * This method is a placehoder. Columns should be specified in the concrete report subclass.
	 *
	 * The array should be defined as an associative array with column keys as the array key
	 * and the value:
	 *
	 * array('orders' => 'orders');
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return array The list of column keys identifying sortable columns
	 **/
	public function sortcolumns () { return array(); }

	/**
	 * Default column value renderer
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $value The value to be rendered
	 * @return void
	 **/
	public function value ($value) {
		echo trim($value);
	}

	/**
	 * Specifies the scores to be added to the scoreboard
	 *
	 * This method is a placeholder. Scores should be specified in the concrete report subclass.
	 *
	 * The array should be defined as an associative array with the translateable label as keys and the
	 * score as the value:
	 *
	 * array(Shopp::__('Total') => $this->totals->total);
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function scores () {
		return array();
	}

	/**
	 * Renders the scoreboard
	 *
	 * @author Jonathan Davis
	 * @since 13
	 *
	 * @return void
	 **/
	public function scoreboard () {
		$scores = $this->scores();
		?>
		<table class="scoreboard">
			<tr>
				<?php foreach ($scores as $label => $score): ?>
				<td>
					<label><?php echo $label; ?></label>
					<big><?php echo $score; ?></big>
				</td>
				<?php endforeach; ?>
			</tr>
		</table>
		<?php
	}

	public function chart () {
		if ( $this->Chart ) $this->Chart->render();
	}

	/**
	 * Renders the report table to the WP admin screen
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function table () {
		extract($this->options, EXTR_SKIP);

		// Get only the records for this page
		$beginning = (int) ( $paged - 1 ) * $per_page;

		$report = array_values($this->data);
		$report = array_slice($report, $beginning, $beginning + $per_page, true );
		unset($this->data); // Free memory

	?>


			<table class="widefat" cellspacing="0">
				<thead>
				<tr><?php ShoppUI::print_column_headers($this->screen); ?></tr>
				</thead>
			<?php if ( false !== $report && count($report) > 0 ): ?>
				<tbody id="report" class="list stats">
				<?php
				$columns = get_column_headers($this->screen);
				$hidden = get_hidden_columns($this->screen);
				$exclude = $this->excludecolumns();

				$even = false;
				$records = 0;

				foreach( $report as $data ): ?>
				<?php

				if ( $records++ > $per_page ) {
					break;
				}
				?>
                <tr<?php if ( ! $even ) echo " class='alternate'"; $even = ! $even; ?>>
					<?php
					foreach ( $columns as $column => $column_title ) {
						$classes = array($column, "column-$column");
						if ( in_array($column, $hidden) ) $classes[] = 'hidden';

						if ( method_exists(get_class($this), $column) ): ?>
                            <td class="<?php echo esc_attr(join(' ', $classes)); ?>"><?php echo call_user_func(array($this, $column), $data, $column, $column_title, $this->options); ?></td>
						<?php else: ?>
                            <td class="<?php echo esc_attr(join(' ', $classes)); ?>">
								<?php do_action( 'shopp_manage_report_custom_column', $column, $column_title, $data );	?>
                            </td>
						<?php endif;
					} /* $columns */
					?>
                </tr>
                <?php endforeach; ?>

				<tr class="summary average">
					<?php
					$averages = clone $this->totals;
					$first = true;
					foreach ( $columns as $column => $column_title ):
						if ( $first ) {
							$averages->id = $averages->period = $averages->$column = Shopp::__('Average');
							$first = false;
						} else {
							$value = isset($averages->$column) ? $averages->$column : null;
							$total = isset($this->total) ? $this->total : 0;
							if ( null == $value ) $averages->$column = '';
							elseif ( 0 === $total ) $averages->$column = 0;
							else $averages->$column = ( $value / $total );
						}
						$classes = array($column,"column-$column");
						if ( in_array($column, $hidden) ) $classes[] = 'hidden';
					?>
						<td class="<?php echo esc_attr(join(' ', $classes)); ?>">
							<?php
								if ( method_exists(get_class($this), $column) && ! isset($exclude[ $column ]) )
									echo call_user_func(array($this, $column), $averages, $column, $column_title, $this->options);
								else do_action( 'shopp_manage_report_custom_column_average', $column, $column_title, $data );
							?>
						</td>
					<?php endforeach; ?>
				</tr>
				<tr class="summary total">
					<?php
					$first = true;
					foreach ( $columns as $column => $column_title ):
						if ( $first ) {
							$label = Shopp::__('Total');
							$this->totals->id = $this->totals->period = $this->totals->$column = $label;
							$first = false;
						}
						$classes = array($column,"column-$column");
						if ( in_array($column, $hidden) ) $classes[] = 'hidden';
					?>
						<td class="<?php echo esc_attr(join(' ', $classes)); ?>">
							<?php
								if ( method_exists(get_class($this), $column) && ! isset($exclude[ $column ]) )
									echo call_user_func(array($this, $column), $this->totals, $column, $column_title, $this->options);
								else do_action( 'shopp_manage_report_custom_column_total', $column, $column_title, $data );
							?>
						</td>
					<?php endforeach; ?>
				</tr>

				</tbody>
			<?php else: ?>
				<tbody><tr><td colspan="<?php echo count(get_column_headers($this->screen)); ?>"><?php Shopp::_e('No report data available.'); ?></td></tr></tbody>
			<?php endif; ?>
			<tfoot>
			<tr><?php ShoppUI::print_column_headers($this->screen, false); ?></tr>
			</tfoot>
			</table>
	<?php
	}

	/**
	 * Renders the filter controls to the WP admin screen
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function filters () {
		self::rangefilter();
		self::scalefilter();
		self::filterbutton();
	}

	/**
	 * Renders the date range filter control elements
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected static function rangefilter () { ?>
		<select name="range" id="range">
			<?php
				$defaults = array(
					'start' => '',
					'end'   => '',
					'range' => 'all'
				);
				$request = array_merge($defaults, $_GET);
				extract($request, EXTR_SKIP);

				$ranges = array(
					'today'       => Shopp::__('Today'),
					'week'        => Shopp::__('This Week'),
					'month'	      => Shopp::__('This Month'),
					'year'        => Shopp::__('This Year'),
					'quarter'     => Shopp::__('This Quarter'),
					'yesterday'   => Shopp::__('Yesterday'),
					'lastweek'    => Shopp::__('Last Week'),
					'last30'      => Shopp::__('Last 30 Days'),
					'last90'      => Shopp::__('Last 3 Months'),
					'lastmonth'   => Shopp::__('Last Month'),
					'lastquarter' => Shopp::__('Last Quarter'),
					'lastyear'    => Shopp::__('Last Year'),
					'custom'      => Shopp::__('Custom Dates')
				);
				echo menuoptions($ranges, $range, true);
			?>
		</select>
		<div id="dates" class="hide-if-js">
			<div id="start-position" class="calendar-wrap"><input type="text" id="start" name="start" value="<?php echo esc_attr($start); ?>" size="10" class="search-input selectall" /></div>
			<small><?php Shopp::_e('to'); ?></small>
			<div id="end-position" class="calendar-wrap"><input type="text" id="end" name="end" value="<?php echo esc_attr($end); ?>" size="10" class="search-input selectall" /></div>
		</div>
<?php
	}

	/**
	 * Renders the date scale filter control element
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected static function scalefilter () { ?>

		<select name="scale" id="scale">
		<?php
		$scale = isset($_GET['scale']) ? $_GET['scale'] : 'day';
		$scales = array(
			'hour'  => Shopp::__('By Hour'),
			'day'   => Shopp::__('By Day'),
			'week'  => Shopp::__('By Week'),
			'month' => Shopp::__('By Month')
		);

		echo menuoptions($scales, $scale, true);
		?>
		</select>

<?php
	}

	/**
	 * Renders the filter button element
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected static function filterbutton () {
		?><button type="submit" id="filter-button" name="filter" value="order" class="button-secondary"><?php Shopp::_e('Filter'); ?></button><?php
	}

	/**
	 * Creates a chart for this report
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected function initchart () {
		$this->Chart = new ShoppReportChart();
		if ( $this->periods ) $this->Chart->timeaxis('xaxis', $this->total, $this->options['scale']);
	}

	/**
	 * Sets chart options
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $options The options to set
	 * @return void
	 **/
	protected function setchart ( array $options = array() ) {
		if ( ! $this->Chart ) $this->initchart();
		$this->Chart->settings($options);
	}

	/**
	 * Sets chart data for a data series from the report
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $series The index of the series to set the data for
	 * @param scalar $x The value for the X-axis
	 * @param scalar $y The value for the Y-axis
	 * @return void
	 **/
	protected function chartdata ( $series, $x, $y ) {
		$this->Chart->data($series, $x, $y, $this->periods);
	}

	/**
	 * Sets up a chart series
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $label The label to use for the series (if none, use boolean false)
	 * @param array $options The series settings (and possible the data)
	 * @return void
	 **/
	protected function chartseries ( $label, array $options = array() ) {
		if ( ! $this->Chart ) $this->initchart();
		if ( isset($options['column']) ) $this->chartseries[] = $options['column'];	// Register the column to the data series index
		$this->Chart->series($label, $options);										// Initialize the series in the chart
	}


} // End class ShoppReportFramework

/**
 * ShoppReportChart
 *
 * An interface for creating charts using the Flot charting engine.
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package reports
 **/
class ShoppReportChart {

	private $data = array();

	public $options = array(
		'series' => array(
			'limit'      => 20,	// Limit the number of series
			'lines'      => array('show' => true, 'fill' => true, 'lineWidth' => 3),
			'points'     => array('show' => true),
			'shadowSize' => 0
		),
		'xaxis' => array(
			'color'           => '#545454',
			'tickColor'       => '#fff',
			'position'        => 'top',
			'mode'            => 'time',
			'timeformat'      => '%m/%d/%y',
			'tickSize'        => array(1,'day'),
			'twelveHourClock' => true
		),
		'yaxis' => array(
			'position'        => 'right',
			'autoscaleMargin' => 0.02,
		),
		'legend' => array(
			'show' => false
		),
		'grid' => array(
			'show'            => true,
			'hoverable'       => true,
			'borderWidth'     => 0,
			'borderColor'     => '#000',
			'minBorderMargin' => 10,
			'labelMargin'     => 10,
			'markingsColor'   => '#f7f7f7'
         ),
		// Solarized Color Palette
		'colors' => array('#1C63A8', '#618C03', '#1C63A8', '#1F756B', '#896204', '#CB4B16', '#A90007', '#A9195F', '#4B4B9A'),
	);

	/**
	 * Constructor
	 *
	 * Includes the client-side libraries needed for rendering the chart
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function __construct () {
		shopp_enqueue_script('flot');
		shopp_enqueue_script('flot-time');
		shopp_enqueue_script('flot-grow');
	}

	/**
	 * An interface for setting options on the chart instance
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $options An associative array of the options to set
	 * @return void
	 **/
	public function settings ($options) {
		foreach ( $options as $setting => $settings )
			$this->options[$setting] = wp_parse_args($settings, $this->options[$setting]);
	}

	/**
	 * Sets up an axis for time period charts
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $axis The axis to setup (xaxis, yaxis)
	 * @param int $range The number of periods on the axis
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return void
	 **/
	public function timeaxis ($axis, $range, $scale = 'day' ) {
		if ( ! isset($this->options[ $axis ]) ) return;

		$options = array();
		switch ( strtolower($scale) ) {
			case 'hour':
				$options['timeformat'] = '%h%p';
				$options['tickSize'] = array(2, 'hour');
				break;
			case 'day':
				$tickscale = ceil($range / 10);
				$options['tickSize'] = array($tickscale, 'day');
				$options['timeformat'] = '%b %d';
				break;
			case 'week':
				$tickscale = ceil($range/10)*7;
				$options['tickSize'] = array($tickscale, 'day');
				$options['minTickSize'] = array(7, 'day');
				$options['timeformat'] = '%b %d';
				break;
			case 'month':
				$tickscale = ceil($range / 10);
				$options['tickSize'] = array($tickscale, 'month');
				$options['timeformat'] = '%b %y';
				break;
			case 'year':
				$options['tickSize'] = array(12, 'month');
				$options['minTickSize'] = array(12, 'month');
				$options['timeformat'] = '%y';
				break;
		}

		$this->options[ $axis ] = wp_parse_args($options,$this->options[ $axis ]);
	}

	/**
	 * Sets up a data series for the chart
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $label The label to use (if any)
	 * @param array $options Associative array of setting options
	 * @return void
	 **/
	public function series ( $label, array $options = array() ) {
		if ( count($this->data) > $this->options['series']['limit'] ) return;
		$defaults = array(
			'label' => $label,
			'data'  => array(),
			'grow'  => array(				// Enables grow animation
				'active'        => true,
				'stepMode'      => 'linear',
				'stepDelay'     => false,
				'steps'         => 25,
				'stepDirection' => 'up'
			)
		);

		$settings = wp_parse_args($options, $defaults);

		$this->data[] = $settings;
	}

	/**
	 * Sets the data for a series
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param int $series The index number of the series to set data for
	 * @param scalar $x The data for the X-axis
	 * @param scalar $y The data for the Y-axis
	 * @param boolean $periods Settings flag for specified time period data
	 * @return void
	 **/
	public function data ( $series, $x, $y, $periods = false ) {
		if ( ! isset($this->data[$series]) ) return;

		if ( $periods ) {
			$tzoffset = date('Z');
			$x = ($x + $tzoffset) * 1000;
		}

		$this->data[ $series ]['data'][] = array($x, $y);

		// Setup the minimum scale for the y-axis from chart data
		$min = isset($this->options['yaxis']['min']) ? $this->options['yaxis']['min'] : $y;
		$this->options['yaxis']['min'] = (float)min($min, $y);

		if ( ! isset($this->datapoints) ) $this->datapoints = 0;
		$this->datapoints = max( $this->datapoints, count($this->data[ $series ]['data']) );
	}

	/**
	 * Renders the chart
	 *
	 * Outputs the markup elements for the chart canvas and sends the data to the client-side environment.
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function render () {
		if ( isset($this->datapoints) && $this->datapoints > 75 ) $this->options['series']['points'] = false;

		// if ( empty($this->data) && isset($this->options['series']['bars'])) { // Default empty bar chart
		// 	$this->data = array(array(
		// 		'data' => array(0,0)
		// 	));
		// 	$this->options['yaxis']['min'] = 0;
		// 	$this->options['yaxis']['max'] = 100;
		// }

		?>
		<script type="text/javascript">
		var d = <?php echo json_encode($this->data); ?>,
			co = <?php echo json_encode($this->options); ?>;
		</script>

		<div id="chart" class="flot"></div>
		<div id="chart-legend"></div>
<?php
	}

} // End class ShoppReportChart

/**
 * ShoppReportExportFramework
 *
 * Provides the base functionality for exporting a report
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package reports
 **/
abstract class ShoppReportExportFramework {

	public $ReportClass = '';
	public $columns     = array();
	public $headings    = true;
	public $data        = false;

	public $recordstart  = true;
	public $content_type = "text/plain; charset=UTF-8";
	public $extension    = "txt";
	public $set          = 0;
	public $limit        = 1024;

	public function __construct ( ShoppReportFramework $Report ) {

		$this->ReportClass = get_class($Report);
		$this->options     = $Report->options;

		$Report->load();

		$this->columns = $Report->columns();
		$this->data    = $Report->data;
		$this->records = $Report->total;

		$report = $this->options['report'];

		$settings = shopp_setting("{$report}_report_export");

		$this->headings = Shopp::str_true($settings['headers']);
		$this->selected = $settings['columns'];

	}

	/**
	 * Generates the output for the exported report
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function output () {
		if ( empty($this->data) ) Shopp::redirect( add_query_arg( array_merge( $_GET, array('src' => null) ), admin_url('admin.php') ) );

		$sitename = get_bloginfo('name');
		$report = $this->options['report'];
		$reports = ShoppAdminReport::reports();
		$name = $reports[$report]['name'];

		header("Content-type: $this->content_type");
		header("Content-Disposition: attachment; filename=\"$sitename $name.$this->extension\"");
		header("Content-Description: Delivered by " . ShoppVersion::agent());
		header("Cache-Control: maxage=1");
		header("Pragma: public");

		$this->begin();
		if ( $this->headings ) $this->heading();
		$this->records();
		$this->end();
	}

	/**
	 * Outputs the beginning of file marker (BOF)
	 *
	 * Can be used to include a byte order marker (BOM) that sets the endianess of the data
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function begin () { }

	/**
	 * Outputs the column headers when enabled
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function heading () {
		foreach ( $this->selected as $name )
			$this->export($this->columns[ $name ]);
		$this->record();
	}

	/**
	 * Outputs each of the record parts
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function records () {
		$options = array('scale' => $this->scale);
		// @todo Add batch export to reduce memory footprint and add scalability to report exports
		// while (!empty($this->data)) {
		foreach ( $this->data as $key => $record ) {
			if ( ! is_array($this->selected) ) continue;
			foreach ( $this->selected as $column ) {
				$title   = $this->columns[$column];
				$columns = get_object_vars($record);
				$value   = isset($columns[ $column ]) ? ShoppReportExportFramework::parse( $columns[ $column ] ) : false;
				if ( method_exists($this->ReportClass, "export_$column") )
					$value = call_user_func(array($this->ReportClass,"export_$column"), $record, $column, $title, $this->options);
				elseif ( method_exists($this->ReportClass, $column) )
					$value = call_user_func(array($this->ReportClass, $column), $record, $column, $title, $this->options);
				$this->export($value);
			}
			$this->record();
		}
		// 	$this->set++;
		// 	$this->query();
		// }
	}

	/**
	 * Parses column data and normalizes non-standard data
	 *
	 * Non-standard data refers to binary or serialized object strings
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param mixed $column A record value of any type
	 * @return string The normalized string column data
	 **/
	static function parse ( $column ) {
		if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/", $column)) {
			$list   = unserialize($column);
			$column = "";
			foreach ( $list as $name => $value )
				$column .= (empty($column) ? "" : ";") . "$name:$value";
		}
		return $column;
	}

	/**
	 * Outputs the end of file marker (EOF)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function end () { }

	/**
	 * Outputs each individual value in a record
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function export ($value) {
		echo ($this->recordstart ? "" : "\t") . $value;
		$this->recordstart = false;
	}

	/**
	 * Outputs the end of record marker (EOR)
	 *
	 * @author Jonathan Davis
	 * @since
	 *
	 * @return void
	 **/
	public function record () {
		echo "\n";
		$this->recordstart = true;
	}

} // End class ShoppReportExportFramework

/**
 * ShoppReportTabExport
 *
 * Concrete implementation of the export framework to export report data in
 * tab-delimmited file format.
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package report
 **/
class ShoppReportTabExport extends ShoppReportExportFramework {

	public function __construct( ShoppReportFramework $Report ) {
		parent::__construct( $Report );
		$this->output();
	}

}

/**
 * ShoppReportCSVExport
 *
 * Exports report data into comma-separated values (CSV) file format.
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package report
 **/
class ShoppReportCSVExport extends ShoppReportExportFramework {

	public function __construct ( ShoppReportFramework $Report ) {
		parent::__construct($Report);
		$this->content_type = "text/csv; charset=UTF-8";
		$this->extension = "csv";
		$this->output();
	}

	public function export ( $value ) {
		$value = str_replace('"', '""', $value);
		if ( preg_match('/^\s|[,"\n\r]|\s$/', $value) ) $value = '"' . $value . '"';
		echo ($this->recordstart ? "" : ",") . $value;
		$this->recordstart = false;
	}

}

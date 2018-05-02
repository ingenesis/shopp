<?php
/**
 * ReportFramework.php
 *
 * Provides a base framework for rendering reports
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Reports
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Defines the required interfaces for a report class
 **/
interface ShoppReport {
	public function query();
	public function setup();
	public function table();
}

abstract class ShoppReportFramework {

	// Settings
	public $periods = false;		// A time period series report

	public $screen = false;			// The current WP screen
	public $Chart = false;			// The report chart (if any)


	public $options = array();		// Options for the report
	public $data = array();			// The processed report data
	public $totals = false;			// The processed totals for the report


	public $range = false;			// Range of values in the report
	public $total = 0;				// Total number of records in the report
	public $pages = 1;				// Number of pages for the report
	public $daterange = false;

	private $columns = array();		// Helper to track columns in a report

	public function __construct ($request = array()) {
		$this->options = $request;
		$this->screen = $this->options['screen'];
		$this->totals = new StdClass();

		add_action('shopp_report_filter_controls', array($this, 'filters'));
		add_action("manage_{$this->screen}_columns", array($this, 'screencolumns'), 0);
		add_action("manage_{$this->screen}_sortable_columns", array($this, 'sortcolumns'), 0);
	}

	/**
	 * Load the report data
	 *
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

		foreach ($columns as $column => $value) {
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
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function pagination () {
		extract($this->options,EXTR_SKIP);
		$this->pages = ceil($this->total / $per_page);
		$_GET['paged'] = $this->options['paged'] = min($paged,$this->pages);
	}

	/**
	 * Initializes a time period report
	 *
	 * This maps out a list of calendar dates with periodical timestamps
	 *
	 * @since 1.3
	 *
	 * @param int $starts Starting timestamp
	 * @param int $ends Ending timestamp
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return void
	 **/
	public function timereport ($starts,$ends,$scale) {
		$this->total = $this->range($starts,$ends,$scale);
		$i = 0;
		while ($i < $this->total) {
			$record = new StdClass();
			list ($index,$record->period) = self::timeindex($i++,$starts,$scale);
			$this->data[$index] = $record;
		}
	}

	/**
	 * Generates a timestamp with a date index value
	 *
	 * Timestamps are generated for each period based on the starting date and scale provided.
	 * The date index value is generated to match the query datetime id columns generated
	 * by the timecolumn() method below.
	 *
	 * @since 1.3
	 *
	 * @param int $i The period iteration
	 * @param int $starts The starting timestamp
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return array The date index and timestamp pair
	 **/
	static function timeindex ( $i, $starts, $scale ) {
		$month = date('n',$starts); $day = date('j',$starts); $year = date('Y',$starts);
		$index = $i;
		switch (strtolower($scale)) {
			case 'hour': $ts = mktime($i,0,0,$month,$day,$year); break;
			case 'week':
				$ts = mktime(0,0,0,$month,$day+($i*7),$year);
				$index = sprintf('%s %s',(int)date('W',$ts),date('Y',$ts));
				break;
			case 'month':
				$ts = mktime(0,0,0,$month+$i,1,$year);
				$index = sprintf('%s %s',date('n',$ts),date('Y',$ts));
				break;
			case 'year':
				$ts = mktime(0,0,0,1,1,$year+$i);
				$index = sprintf('%s',date('Y',$ts));
				break;
			default:
				$ts = mktime(0,0,0,$month,$day+$i,$year);
				$index = sprintf('%s %s %s',date('j',$ts),date('n',$ts),date('Y',$ts));
				break;
		}

		return array($index,$ts);
	}

	/**
	 * Builds a date index SQL column
	 *
	 * This creates the SQL statement fragment for requesting a column that matches the
	 * date indexes generated by the timeindex() method above.
	 *
	 * @since 1.3
	 *
	 * @param int $column A datetime column value
	 * @return string Date index column SQL statement
	 **/
	public function timecolumn ( $column ) {
		switch ( strtolower($this->options['scale']) ) {
			case 'hour':	$_ = "HOUR($column)"; break;
			case 'week':	$_ = "WEEK($column,3),' ',YEAR($column)"; break;
			case 'month':	$_ = "MONTH($column),' ',YEAR($column)"; break;
			case 'year':	$_ = "YEAR($column)"; break;
			default:		$_ = "DAY($column),' ',MONTH($column),' ',YEAR($column)";
		}
		return $_;
	}

	/**
	 * Determines the range of periods between two dates for a given scale
	 *
	 * @since 1.3
	 *
	 * @param int $starts The starting timestamp
	 * @param int $ends The ending timestamp
	 * @param string $scale Scale of periods (hour, day, week, month, year)
	 * @return int The number of periods
	 **/
	public function range ( $starts, $ends, $scale = 'day') {
		$oneday = 86400;
		$years = date('Y',$ends)-date('Y',$starts);
		switch (strtolower($scale)) {
			case 'week':
				// Find the timestamp for the first day of the start date's week
				$startweekday = date('w',$starts);
				$startweekdate = $starts-($startweekday*86400);

				// Find the timestamp for the last day of the end date's' week
				$endweekday = date('w',$ends);
				$endweekdate = $ends+((6-$endweekday)*86400);

				$starts_week = (int)date('W',$startweekdate);
				$ends_week =  (int)date('W',$endweekdate);
				if ($starts_week < 0) $starts_week += 52;
				elseif ($starts_week > $ends_week) $starts_week -= 52;

				return ($years*52)+$ends_week - $starts_week;
			case 'month':
				$starts_month = date('n',$starts);
				$ends_month = date('n',$ends);
				if ($starts_month > $ends_month) $starts_month -= 12;
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
	 * @since 1.3
	 *
	 * @param object $data The source data record
	 * @param string $column The column key name
	 * @param string $title The column title label
	 * @param array $options The options for this report
	 * @return void
	 **/
	static function period ( $data, $column, $title, array $options ) {

		if ( __('Total','Shopp') == $data->period ) { echo __('Total','Shopp'); return; }
		if ( __('Average','Shopp') == $data->period ) { echo __('Average','Shopp'); return; }

		switch (strtolower($options['scale'])) {
			case 'hour': echo date('ga',$data->period); break;
			case 'day': echo date('l, F j, Y',$data->period); break;
			case 'week': echo ShoppReportFramework::weekrange($data->period); break;
			case 'month': echo date('F Y',$data->period); break;
			case 'year': echo date('Y',$data->period); break;
			default: echo $data->period; break;
		}
	}

	/**
	 * Standard export renderer for period columns
	 *
	 * @since 1.3
	 *
	 * @param object $data The source data record
	 * @param string $column The column key name
	 * @param string $title The column title label
	 * @param array $options The options for this report
	 * @return void
	 **/
	static function export_period ($data,$column,$title,$options) {
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');
		$datetime = "$date_format $time_format";

		switch (strtolower($options['scale'])) {
			case 'day': echo date($date_format,$data->period); break;
			case 'week': echo ShoppReportFramework::weekrange($data->period,array($date_format,$date_format)); break;
			default: echo date($datetime,$data->period); break;
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
	 * array('orders' => __('Orders','Shopp'));
	 *
	 * @since 1.3
	 *
	 * @return array The list of column keys and column title labels
	 **/
	public function columns () { return array(); }

	/**
	 * Registers the report columns to the WP screen
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function screencolumns () { ShoppUI::register_column_headers($this->screen,$this->columns()); }

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
	 * @since 1.3
	 *
	 * @return array The list of column keys identifying sortable columns
	 **/
	public function sortcolumns () { return array(); }

	/**
	 * Default column value renderer
	 *
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
	 * array(__('Total','Shopp') => $this->totals->total);
	 *
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

				$even = false;
				$records = 0;
				while ( list($id, $data) = each($report) ):
					if ( $records++ > $per_page ) break;
				?>
					<tr<?php if ( ! $even ) echo " class='alternate'"; $even = ! $even; ?>>
				<?php

					foreach ( $columns as $column => $column_title ) {
						$classes = array($column, "column-$column");
						if ( in_array($column, $hidden) ) $classes[] = 'hidden';

						if ( method_exists(get_class($this), $column) ): ?>
							<td class="<?php echo esc_attr(join(' ', $classes)); ?>"><?php echo call_user_func(array($this, $column), $data, $column, $column_title, $this->options); ?></td>
						<?php else: ?>
							<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
							<?php do_action( 'shopp_manage_report_custom_column', $column, $column_title, $data );	?>
							</td>
						<?php endif;
				} /* $columns */
				?>
				</tr>
				<?php endwhile; /* records */ ?>

				<tr class="summary average">
					<?php
					$averages = clone $this->totals;
					$first = true;
					foreach ($columns as $column => $column_title):
						if ( $first ) {
							$averages->id = $averages->period = $averages->$column = __('Average','Shopp');
							$first = false;
						} else {
							$value = isset($averages->$column) ? $averages->$column : null;
							$total = isset($this->total) ? $this->total : 0;
							if ( null == $value ) $averages->$column = '';
							elseif ( 0 === $total ) $averages->$column = 0;
							else $averages->$column = ( $value / $total );
						}
						$classes = array($column,"column-$column");
						if ( in_array($column,$hidden) ) $classes[] = 'hidden';
					?>
						<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
							<?php
								if ( method_exists(get_class($this),$column) )
									echo call_user_func(array($this,$column),$averages,$column,$column_title,$this->options);
								else do_action( 'shopp_manage_report_custom_column_average', $column, $column_title, $data );
							?>
						</td>
					<?php endforeach; ?>
				</tr>
				<tr class="summary total">
					<?php
					$first = true;
					foreach ($columns as $column => $column_title):
						if ( $first ) {
							$label = __('Total','Shopp');
							$this->totals->id = $this->totals->period = $this->totals->$column = $label;
							$first = false;
						}
						$classes = array($column,"column-$column");
						if ( in_array($column, $hidden) ) $classes[] = 'hidden';
					?>
						<td class="<?php echo esc_attr(join(' ',$classes)); ?>">
							<?php
								if ( method_exists(get_class($this), $column) )
									echo call_user_func(array($this, $column), $this->totals, $column, $column_title, $this->options);
								else do_action( 'shopp_manage_report_custom_column_total', $column, $column_title, $data );
							?>
						</td>
					<?php endforeach; ?>
				</tr>

				</tbody>
			<?php else: ?>
				<tbody><tr><td colspan="<?php echo count(get_column_headers($this->screen)); ?>"><?php _e('No report data available.','Shopp'); ?></td></tr></tbody>
			<?php endif; ?>
			<tfoot>
			<tr><?php ShoppUI::print_column_headers($this->screen,false); ?></tr>
			</tfoot>
			</table>
	<?php
	}

	/**
	 * Renders the filter controls to the WP admin screen
	 *
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
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected static function rangefilter () { ?>
		<select name="range" id="range">
			<?php
				$defaults = array(
					'start' => '',
					'end' => '',
					'range' => 'all'
				);
				$request = array_merge($defaults, $_GET);
				extract($request, EXTR_SKIP);

				$ranges = array(
					'today' => __('Today','HelpDesk'),
					'week' => __('This Week','HelpDesk'),
					'month' => __('This Month','HelpDesk'),
					'year' => __('This Year','HelpDesk'),
					'quarter' => __('This Quarter','HelpDesk'),
					'yesterday' => __('Yesterday','HelpDesk'),
					'lastweek' => __('Last Week','HelpDesk'),
					'last30' => __('Last 30 Days','HelpDesk'),
					'last90' => __('Last 3 Months','HelpDesk'),
					'lastmonth' => __('Last Month','HelpDesk'),
					'lastquarter' => __('Last Quarter','HelpDesk'),
					'lastyear' => __('Last Year','HelpDesk'),
					'custom' => __('Custom Dates','HelpDesk')
				);
				echo Shopp::menuoptions($ranges, $range, true);
			?>
		</select>
		<div id="dates" class="hide-if-js">
			<div id="start-position" class="calendar-wrap"><input type="text" id="start" name="start" value="<?php echo esc_attr($start); ?>" size="10" class="search-input selectall" /></div>
			<small><?php _e('to','Shopp'); ?></small>
			<div id="end-position" class="calendar-wrap"><input type="text" id="end" name="end" value="<?php echo esc_attr($end); ?>" size="10" class="search-input selectall" /></div>
		</div>
<?php
	}

	/**
	 * Renders the date scale filter control element
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected static function scalefilter () { ?>

		<select name="scale" id="scale">
		<?php
		$scale = isset($_GET['scale']) ? $_GET['scale'] : 'day';
		$scales = array(
			'hour' => __('By Hour','Shopp'),
			'day' => __('By Day','Shopp'),
			'week' => __('By Week','Shopp'),
			'month' => __('By Month','Shopp')
		);

		echo Shopp::menuoptions($scales,$scale,true);
		?>
		</select>

<?php
	}

	/**
	 * Renders the filter button element
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected static function filterbutton () {
		?><button type="submit" id="filter-button" name="filter" value="order" class="button-secondary"><?php _e('Filter','Shopp'); ?></button><?php
	}

	/**
	 * Creates a chart for this report
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected function initchart () {
		$this->Chart = new ShoppReportChart();
		if ($this->periods)	$this->Chart->timeaxis('xaxis',$this->total,$this->options['scale']);
	}

	/**
	 * Sets chart options
	 *
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
	 * @since 1.3
	 *
	 * @param int $series The index of the series to set the data for
	 * @param scalar $x The value for the X-axis
	 * @param scalar $y The value for the Y-axis
	 * @return void
	 **/
	protected function chartdata ( $series, $x, $y ) {
		$this->Chart->data($series,$x,$y,$this->periods);
	}

	/**
	 * Sets up a chart series
	 *
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


}
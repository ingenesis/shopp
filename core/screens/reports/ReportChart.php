<?php
/**
 * ReportChart.php
 *
 * Provides a base framework for creating report charts using the Flot charting engine.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Reports
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppReportChart {

	private $data = array();

	public $options = array(
		'series' => array(
			'limit' => 20,	// Limit the number of series
			'lines' => array('show' => true,'fill'=>true,'lineWidth'=>3),
			'points' => array('show' => true),
			'shadowSize' => 0
		),
		'xaxis' => array(
			'color' => '#545454',
			'tickColor' => '#fff',
			'position' => 'top',
			'mode' => 'time',
			'timeformat' => '%m/%d/%y',
			'tickSize' => array(1,'day'),
			'twelveHourClock' => true
		),
		'yaxis' => array(
			'position' => 'right',
			'autoscaleMargin' => 0.02,
		),
		'legend' => array(
			'show' => false
		),
		'grid' => array(
			'show' => true,
			'hoverable' => true,
			'borderWidth' => 0,
			'borderColor' => '#000',
			'minBorderMargin' => 10,
			'labelMargin' => 10,
			'markingsColor' => '#f7f7f7'
         ),
		// Solarized Color Palette
		'colors' => array('#1C63A8','#618C03','#1C63A8','#1F756B','#896204','#CB4B16','#A90007','#A9195F','#4B4B9A'),
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
		foreach ($options as $setting => $settings)
			$this->options[$setting] = wp_parse_args($settings,$this->options[$setting]);
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
	public function timeaxis ($axis,$range,$scale='day') {
		if ( ! isset($this->options[ $axis ])) return;

		$options = array();
		switch (strtolower($scale)) {
			case 'hour':
				$options['timeformat'] = '%h%p';
				$options['tickSize'] = array(2,'hour');
				break;
			case 'day':
				$tickscale = ceil($range / 10);
				$options['tickSize'] = array($tickscale,'day');
				$options['timeformat'] = '%b %d';
				break;
			case 'week':
				$tickscale = ceil($range/10)*7;
				$options['tickSize'] = array($tickscale,'day');
				$options['minTickSize'] = array(7,'day');
				$options['timeformat'] = '%b %d';
				break;
			case 'month':
				$tickscale = ceil($range / 10);
				$options['tickSize'] = array($tickscale,'month');
				$options['timeformat'] = '%b %y';
				break;
			case 'year':
				$options['tickSize'] = array(12,'month');
				$options['minTickSize'] = array(12,'month');
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
			'data' => array(),
			'grow' => array(				// Enables grow animation
				'active' => true,
				'stepMode' => 'linear',
				'stepDelay' => false,
				'steps' => 25,
				'stepDirection' => 'up'
			)
		);

		$settings = wp_parse_args($options,$defaults);

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
			$x = ($x+$tzoffset)*1000;
		}

		$this->data[$series]['data'][] = array($x,$y);

		// Setup the minimum scale for the y-axis from chart data
		$min = isset($this->options['yaxis']['min']) ? $this->options['yaxis']['min'] : $y;
		$this->options['yaxis']['min'] = (float)min($min,$y);

		if ( ! isset($this->datapoints) ) $this->datapoints = 0;
		$this->datapoints = max( $this->datapoints, count($this->data[$series]['data']) );
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

}
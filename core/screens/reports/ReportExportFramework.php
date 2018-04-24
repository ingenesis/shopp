<?php
/**
 * ReportExportFramework.php
 *
 * Provides a base functionality for exporting a report.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Reports
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppReportExportFramework {

	public $ReportClass = '';
	public $columns = array();
	public $headings = true;
	public $data = false;

	public $recordstart = true;
	public $content_type = "text/plain; charset=UTF-8";
	public $extension = "txt";
	public $set = 0;
	public $limit = 1024;

	public function __construct ( ShoppReportFramework $Report ) {

		$this->ReportClass = get_class($Report);
		$this->options = $Report->options;

		$Report->load();

		$this->columns = $Report->columns();
		$this->data = $Report->data;
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
		if ($this->headings) $this->heading();
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
		foreach ($this->data as $key => $record) {
			if ( ! is_array($this->selected) ) continue;
			foreach ($this->selected as $column) {
				$title = $this->columns[$column];
				$columns = get_object_vars($record);
				$value = isset($columns[ $column ]) ? ShoppReportExportFramework::parse( $columns[ $column ] ) : false;
				if ( method_exists($this->ReportClass,"export_$column") )
					$value = call_user_func(array($this->ReportClass,"export_$column"),$record,$column,$title,$this->options);
				elseif ( method_exists($this->ReportClass,$column) )
					$value = call_user_func(array($this->ReportClass,$column),$record,$column,$title,$this->options);
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
		if (preg_match("/^[sibNaO](?:\:.+?\{.*\}$|\:.+;$|;$)/",$column)) {
			$list = unserialize($column);
			$column = "";
			foreach ($list as $name => $value)
				$column .= (empty($column)?"":";")."$name:$value";
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
		echo ($this->recordstart?"":"\t").$value;
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

}
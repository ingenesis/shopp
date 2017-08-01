<?php
/**
 * ReportCSVExport.php
 *
 * Concrete implementation of the export framework to export report data in
 * tab-delimmited file format.
 *
 * @copyright Ingenesis Limited, July 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Reports
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppReportCSVExport extends ShoppReportExportFramework {

	public function __construct ( ShoppReportFramework $Report ) {
		parent::__construct($Report);
		$this->content_type = "text/csv; charset=UTF-8";
		$this->extension = "csv";
		$this->output();
	}

	public function export ( $value ) {
		$value = str_replace('"','""',$value);
		if (preg_match('/^\s|[,"\n\r]|\s$/',$value)) $value = '"'.$value.'"';
		echo ($this->recordstart?"":",").$value;
		$this->recordstart = false;
	}

}
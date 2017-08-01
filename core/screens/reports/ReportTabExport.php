<?php
/**
 * ReportTabExport.php
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

class ShoppReportTabExport extends ShoppReportExportFramework {

	public function __construct( ShoppReportFramework $Report ) {
		parent::__construct( $Report );
		$this->output();
	}

}

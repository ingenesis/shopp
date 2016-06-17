<?php
/**
 * Resources.php
 *
 * Processes resource requests for non-HTML data
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February  8, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage resources
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppResources {

	public $request = array();

	/**
	 * Resources constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function __construct ( array $request = array() ) {

		if ( empty($request) && ! ( defined('WP_ADMIN') && isset($request['src']) ) )
			return;

		$this->request = empty($request) ? $_GET : $request;

		add_action('shopp_resource_download', array($this, 'download'));

		// For secure, backend lookups
		if ( defined('WP_ADMIN') && is_user_logged_in() ) {
			add_action('shopp_resource_help', array($this, 'help'));

			if ( current_user_can('shopp_financials') ) {
				add_action('shopp_resource_export_reports', array($this, 'export_reports'));
				add_action('shopp_resource_export_purchases', array($this, 'export_purchases'));
				add_action('shopp_resource_export_customers', array($this, 'export_customers'));
			}
		}

		if ( ! empty( $this->request['src'] ) )
			do_action( 'shopp_resource_' . $this->request['src'], $this->request );

		exit();
	}

	/**
	 * Delivers order export files to the browser
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function export_purchases () {
		if ( ! current_user_can('shopp_financials') || ! current_user_can('shopp_export_orders') ) exit();

		if ( ! isset($_POST['settings']['purchaselog_columns']) ) {
			$Purchase = ShoppPurchase::exportcolumns();
			$Purchased = ShoppPurchased::exportcolumns();
			$_POST['settings']['purchaselog_columns'] = array_keys(array_merge($Purchase, $Purchased));
			$_POST['settings']['purchaselog_headers'] = 'on';
		}
		shopp_set_formsettings(); // Save workflow setting

		$format = shopp_setting('purchaselog_format');
		if ( empty($format) ) $format = 'tab';

		switch ( $format ) {
			case 'csv': new PurchasesCSVExport(); break;
			case 'xls': new PurchasesXLSExport(); break;
			case 'iif': new PurchasesIIFExport(); break;
			default: new PurchasesTabExport();
		}
		exit();

	}

	/**
	 * Delivers order export files to the browser
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function export_reports () {
		if ( ! current_user_can('shopp_financials') || ! current_user_can('shopp_export_orders') ) exit();

		add_filter('shopp_reports', array('ShoppAdminReport', 'xreports'));
		$reports = ShoppAdminReport::reports();
		$Report = ShoppAdminReport::load();

		if ( ! isset($_POST['settings']["{$report}_report_export"]) ) {
			$_POST['settings']["{$report}_report_export"]['columns'] = array_keys($Report->columns);
			$_POST['settings']["{$report}_report_export"]['headers'] = 'on';
		}
		shopp_set_formsettings(); // Save workflow setting

		$format = shopp_setting('report_export_format');

		switch ( $format ) {
			case 'csv': new ShoppReportCSVExport($Report); break;
			case 'xls': new ShoppReportXLSExport($Report); break;
			default: new ShoppReportTabExport($Report);
		}
		exit();

	}

	/**
	 * Delivers customer export files to the browser
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function export_customers () {
		if ( ! current_user_can('shopp_export_customers') ) exit();

		if ( ! isset($_POST['settings']['customerexport_columns']) ) {
			$Customer = ShoppCustomer::exportcolumns();
			$Billing = BillingAddress::exportcolumns();
			$Shipping = ShippingAddress::exportcolumns();
			$_POST['settings']['customerexport_columns'] =
			 	array_keys(array_merge($Customer, $Billing, $Shipping));
			$_POST['settings']['customerexport_headers'] = 'on';
		}

		shopp_set_formsettings(); // Save workflow setting

		$format = shopp_setting('customerexport_format');
		if (empty($format)) $format = 'tab';

		switch ( $format ) {
			case 'csv': new CustomersCSVExport(); break;
			case 'xls': new CustomersXLSExport(); break;
			default: new CustomersTabExport();
		}

		exit();
	}

	/**
	 * Handles product file download requests
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function download () {
		$Shopp = Shopp::object();
		$download = $this->request['shopp_download'];
		$Purchase = false;
		$Purchased = false;

		if (defined('WP_ADMIN')) {
			$forbidden = false;
			$Download = new ProductDownload($download);
		} else {
			$Order = ShoppOrder();
			$accounts = ('none' != shopp_setting('account_system'));

			$Download = new ProductDownload();
			$Download->loadby_dkey($download);

			$Purchased = $Download->purchased();

			$Purchase = new ShoppPurchase($Purchased->purchase);
			$Purchase->load_events();

			$name = $Purchased->name.(!empty($Purchased->optionlabel)?' ('.$Purchased->optionlabel.')':'');

			$forbidden = false;

			// Voided orders
			if ( $Purchase->isvoid() ) {
				shopp_add_error(Shopp::__('&quot;%s&quot; cannot be downloaded because the order has been cancelled.', $name));
				$forbidden = true;
			}

			// Purchase Completion check
			if ( ! $Purchase->ispaid() && ! SHOPP_PREPAYMENT_DOWNLOADS ) {
				shopp_add_error(Shopp::__('&quot;%s&quot; cannot be downloaded because payment has not been received yet.', $name));
				$forbidden = true;
			}

			// If accounts are used and this is not a guest account
			if ( $accounts && Shopp::__('Guest') != ShoppCustomer()->type ) {

				// User must be logged in when accounts are being used
				if ( ! ShoppCustomer()->loggedin() ) {
					shopp_add_error(Shopp::__('You must login to download purchases.'));
					$forbidden = true;
				}

				// Logged in account must be the owner of the purchase
				if ( ShoppCustomer()->id != $Purchase->customer ) {
					shopp_add_error(Shopp::__('You are not authorized to download the requested file.'));
					$forbidden = true;
				}

			}

			// Download limit checking
			if (shopp_setting('download_limit') // Has download credits available
					&& $Purchased->downloads + 1 > shopp_setting('download_limit')) {
				shopp_add_error(Shopp::__('&quot;%s&quot; is no longer available for download because the download limit has been reached.', $name));
				$forbidden = true;
			}

			// Download expiration checking
			if (shopp_setting('download_timelimit') // Within the timelimit
					&& $Purchased->created+shopp_setting('download_timelimit') < current_time('timestamp') ) {
				shopp_add_error(Shopp::__('&quot;%s&quot; is no longer available for download because it has expired.','Shopp', $name));
				$forbidden = true;
			}

			// IP restriction checks
			if ( 'ip' == shopp_setting('download_restriction') && ! empty($Purchase->ip) && $Purchase->ip != $_SERVER['REMOTE_ADDR']) {
				shopp_add_error(Shopp::__('&quot;%s&quot; cannot be downloaded because your computer could not be verified as the system the file was purchased from.', $name));
				$forbidden = true;
			}

			do_action_ref_array('shopp_download_request', array($Purchased));
		}

		if ( apply_filters('shopp_download_forbidden', $forbidden, $Purchased) ) {
			Shopp::redirect( add_query_arg('downloads', '', Shopp::url(false, 'account') ), true, 303 );
		}

		// Send the download
		$download = $Download->download();

		if ( is_a($download,'ShoppError') ) {
			// If the result is an error redirect to the account downloads page
			Shopp::redirect( add_query_arg( 'downloads', '', Shopp::url(false, 'account') ), true, 303 );
		} else {
			do_action_ref_array('shopp_download_success',array($Purchased, $Purchase, $Download)); // @deprecated use shopp_download_order_event instead

			shopp_add_order_event($Purchase->id, 'download', array(
				'purchased' => $Purchased->id,		// Purchased line item ID (or add-on meta record ID)
				'download' => $Download->id,		// Download ID (meta record)
				'ip' => ShoppShopping()->ip,		// IP address of the download
				'customer' => ShoppCustomer()->id	// Authenticated customer
			));
		}

		exit();
	}

	/**
	 * Grabs interface help screencasts
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function help () {
		if ( ! isset($_GET['id']) ) return;

		echo ShoppSupport::callhome(array(
			'ShoppScreencast' => $_GET['id'],
			'site' => get_bloginfo('url')
		));

		exit;
	}

}
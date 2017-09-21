<?php
/**
 * Service.php
 *
 * Flow controller for order management interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January 6, 2010
 * @package shopp
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Service
 *
 * @package shopp
 * @since 1.1
 * @author Jonathan Davis
 **/
class ShoppAdminService extends ShoppAdminController {

	public $orders = array();
	public $ordercount = false;

	protected $ui = 'orders';

	/**
	 * Service constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function __construct () {
		parent::__construct();

		if ( isset($_GET['id']) ) {
			wp_enqueue_script('postbox');
			shopp_enqueue_script('colorbox');
			shopp_enqueue_script('jquery-tmpl');
			shopp_enqueue_script('orders');
			shopp_localize_script( 'orders', '$om', array(
				'co'     => Shopp::__('Cancel Order'),
				'mr'     => Shopp::__('Mark Refunded'),
				'pr'     => Shopp::__('Process Refund'),
				'dnc'    => Shopp::__('Do Not Cancel'),
				'ro'     => Shopp::__('Refund Order'),
				'cancel' => __('Cancel'),
				'rr'     => Shopp::__('Reason for refund'),
				'rc'     => Shopp::__('Reason for cancellation'),
				'mc'     => Shopp::__('Mark Cancelled'),
				'stg'    => Shopp::__('Send to gateway')
			));
			shopp_enqueue_script('address');
			shopp_custom_script( 'address', 'var regions = '.json_encode(Lookup::country_zones()).';');

			add_action('load-' . $this->screen, array($this, 'workflow'));
			add_action('load-' . $this->screen, array($this, 'layout'));
			do_action('shopp_order_management_scripts');

		} else {
			add_action('load-' . $this->screen, array($this, 'loader'));
			add_action('admin_print_scripts', array($this, 'columns'));
		}
		do_action('shopp_order_admin_scripts');
	}

	/**
	 * admin
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function admin () {
		if ( ! empty($_GET['id']) ) $this->manager();
		else $this->orders();
	}

	public function workflow () {
		if ( preg_match("/\d+/", $_GET['id']) ) {
			ShoppPurchase( new ShoppPurchase($_GET['id']) );
			ShoppPurchase()->load_purchased();
			ShoppPurchase()->load_events();
		} else ShoppPurchase( new ShoppPurchase() );
	}

	/**
	 * Handles orders list loading
	 *
	 * @author Jonathan Davis
	 * @since 1.2.1
	 *
	 * @return void
	 **/
	public function loader () {
		if ( ! current_user_can('shopp_orders') ) return;

		$defaults = array(
			'page' 	    => false,
			'deleting'  => false,
			'selected'  => false,
			'update'    => false,
			'newstatus' => false,
			'pagenum'   => 1,
			'paged'     => 1,
			'per_page'  => 20,
			'start'     => '',
			'end'       => '',
			'status'    => false,
			's'         => '',
			'range'     => '',
			'startdate' => '',
			'enddate'   => '',
		);

		$args = array_merge($defaults, $_GET);
		extract($args, EXTR_SKIP);

		$url = add_query_arg(array_merge($_GET, array('page' => $this->Admin->pagename('orders'))), admin_url('admin.php'));

		if ( $page == "shopp-orders"
						&& !empty($deleting)
						&& !empty($selected)
						&& is_array($selected)
						&& current_user_can('shopp_delete_orders') ) {

			foreach( $selected as $selection ) {
				$Purchase = new ShoppPurchase($selection);
				$Purchase->load_purchased();

				foreach ( $Purchase->purchased as $purchased ) {
					$Purchased = new ShoppPurchased($purchased->id);
					$Purchased->delete();
				}

				$Purchase->delete();
			}

			if ( count($selected) == 1 ) $this->notice(Shopp::__('Order deleted.'));
			else $this->notice(Shopp::__('%d orders deleted.', count($selected)));
		}

		$statusLabels = shopp_setting('order_status');
		if ( empty($statusLabels) ) $statusLabels = array('');
		$txnstatus_labels = Lookup::txnstatus_labels();

		if ( $update == "order"
						&& !empty($selected)
						&& is_array($selected) ) {

			foreach( $selected as $selection ) {
				$Purchase = new ShoppPurchase($selection);
				$Purchase->status = $newstatus;
				$Purchase->save();
			}

			if ( count($selected) == 1 ) $this->notice(Shopp::__('Order status updated.'));
			else $this->notice(Shopp::__('%d orders updated.', count($selected)));
		}

		$Purchase = new ShoppPurchase();

		$offset = get_option( 'gmt_offset' ) * 3600;

		if ( ! empty($start) ) {
			$startdate = $start;
			list($month, $day, $year) = explode("/", $startdate);
			$starts = mktime(0, 0, 0, $month, $day, $year);
		}
		if ( ! empty($end) ) {
			$enddate = $end;
			list($month, $day, $year) = explode("/", $enddate);
			$ends = mktime(23, 59, 59, $month, $day, $year);
		}

		$pagenum = absint( $paged );
		$start = ($per_page * ($pagenum-1));

		$where = array();
		$joins = array();
		if ( ! empty($status) || $status === '0' ) $where[] = "status='".sDB::escape($status)."'";
		if ( ! empty($s) ) {
			$s = stripslashes($s);
			$search = array();
			if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $s, $props, PREG_SET_ORDER) > 0 ) {
				foreach ( $props as $query ) {
					$keyword = sDB::escape( ! empty($query[2] ) ? $query[2] : $query[3] );
					switch(strtolower($query[1])) {
						case "txn": 		$search[] = "txnid='$keyword'"; break;
						case "company":		$search[] = "company LIKE '%$keyword%'"; break;
						case "gateway":		$search[] = "gateway LIKE '%$keyword%'"; break;
						case "cardtype":	$search[] = "cardtype LIKE '%$keyword%'"; break;
						case "address": 	$search[] = "(address LIKE '%$keyword%' OR xaddress='%$keyword%')"; break;
						case "city": 		$search[] = "city LIKE '%$keyword%'"; break;
						case "province":
						case "state": 		$search[] = "state='$keyword'"; break;
						case "zip":
						case "zipcode":
						case "postcode":	$search[] = "postcode='$keyword'"; break;
						case "country": 	$search[] = "country='$keyword'"; break;
						case "promo":
						case "discount":
											$meta_table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
											$joins[$meta_table] = "INNER JOIN $meta_table AS m ON m.parent = o.id AND context='purchase'";
											$search[] = "m.value LIKE '%$keyword%'"; break;
						case "product":
											$purchased = ShoppDatabaseObject::tablename(Purchased::$table);
											$joins[$purchased] = "INNER JOIN $purchased AS p ON p.purchase = o.id";
											$search[] = "p.name LIKE '%$keyword%' OR p.optionlabel LIKE '%$keyword%' OR p.sku LIKE '%$keyword%'"; break;
					}
				}
				if ( empty($search) ) $search[] = "(id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%$s%')";
				$where[] = "(" . join(' OR ', $search) . ")";
			} elseif ( strpos($s,'@') !== false ) {
				 $where[] = "email='" . sDB::escape($s) . "'";
			} else $where[] = "(id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%" . sDB::escape($s) . "%')";
		}
		if ( ! empty($starts) && ! empty($ends) ) $where[] = "created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";

		if ( ! empty($customer) ) $where[] = "customer=" . intval($customer);
		$where = ! empty($where) ? "WHERE " . join(' AND ', $where) : '';
		$joins = join(' ', $joins);

		$countquery = "SELECT count(*) as total,SUM(IF(txnstatus IN ('authed', 'captured'), o.total, NULL)) AS sales, AVG(IF(txnstatus IN ('authed', 'captured'), o.total, NULL)) AS avgsale FROM $Purchase->_table AS o $joins $where ORDER BY o.created DESC LIMIT 1";
		$this->ordercount = sDB::query($countquery, 'object');

		$query = "SELECT o.* FROM $Purchase->_table AS o $joins $where ORDER BY created DESC LIMIT $start, $per_page";
		$this->orders = sDB::query($query,'array', 'index', 'id');

		$num_pages = ceil($this->ordercount->total / $per_page);
		if ( $paged > 1 && $paged > $num_pages ) Shopp::redirect( add_query_arg('paged', null, $url) );

	}

	/**
	 * Interface processor for the orders list interface
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function orders () {
		if ( ! current_user_can('shopp_orders') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		global $Shopp,$Orders;

		$defaults = array(
			'page'      => false,
			'update'    => false,
			'newstatus' => false,
			'paged'     => 1,
			'per_page'  => 20,
			'status'    => false,
			's'         => '',
			'range'     => '',
			'startdate' => '',
			'enddate'   => ''
		);

		$args = array_merge($defaults, $_GET);
		extract($args, EXTR_SKIP);

		$s = stripslashes($s);

		$statusLabels = shopp_setting('order_status');
		if ( empty($statusLabels) ) $statusLabels = array('');
		$txnstatus_labels = Lookup::txnstatus_labels();

		$Purchase = new ShoppPurchase();

		$Orders = $this->orders;
		$ordercount = $this->ordercount;
		$num_pages = ceil($ordercount->total / $per_page);

		$ListTable = ShoppUI::table_set_pagination ($this->screen, $ordercount->total, $num_pages, $per_page );

		$ranges = array(
			'all'         => Shopp::__('Show All Orders'),
			'today'       => Shopp::__('Today'),
			'week' 	      => Shopp::__('This Week'),
			'month'       => Shopp::__('This Month'),
			'quarter'     => Shopp::__('This Quarter'),
			'year' 	      => Shopp::__('This Year'),
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

		$exports = array(
			'tab' => Shopp::__('Tab-separated.txt'),
			'csv' => Shopp::__('Comma-separated.csv'),			
			'iif' => Shopp::__('Intuit&reg; QuickBooks.iif')
			);

		$formatPref = shopp_setting('purchaselog_format');
		if ( ! $formatPref ) $formatPref = 'tab';

		$exportcolumns = array_merge(ShoppPurchase::exportcolumns(),ShoppPurchased::exportcolumns());
		$selected = shopp_setting('purchaselog_columns');
		if ( empty($selected) ) $selected = array_keys($exportcolumns);

		$Gateways = $Shopp->Gateways;

		include $this->ui('orders.php');
	}

	/**
	 * Registers the column headers for the orders list interface
	 *
	 * Uses the WordPress 2.7 function register_column_headers to provide
	 * customizable columns that can be toggled to show or hide
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function columns () {
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('daterange');
		register_column_headers($this->screen, array(
			'cb'          => '<input type="checkbox" />',
			'order'       => Shopp::__('Order'),
			'name'        => __('Name'),
			'destination' => Shopp::__('Destination'),
			'txn'         => Shopp::__('Transaction'),
			'date'        => Shopp::__('Date'),
			'total'       => Shopp::__('Total'))
		);
	}

	/**
	 * Provides overall layout for the order manager interface
	 *
	 * Makes use of WordPress postboxes to generate panels (box) content
	 * containers that are customizable with drag & drop, collapsable, and
	 * can be toggled to be hidden or visible in the interface.
	 *
	 * @author Jonathan Davis
	 * @return
	 **/
	public function layout () {
		$Shopp = Shopp::object();
		$Admin =& $Shopp->Flow->Admin;
		ShoppUI::register_column_headers($this->screen, apply_filters('shopp_order_manager_columns', array(
			'items' => Shopp::__('Items'),
			'qty'   => Shopp::__('Quantity'),
			'price' => Shopp::__('Price'),
			'total' => Shopp::__('Total')
		)));
		include $this->ui('events.php');
		include $this->ui('ui.php');
		do_action('shopp_order_manager_layout');
	}

	/**
	 * Interface processor for the order manager
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function manager () {
		global $Shopp, $Notes;
		global $is_IIS;

		if ( ! current_user_can('shopp_orders') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$Purchase = ShoppPurchase();
		$Purchase->Customer = new ShoppCustomer($Purchase->customer);
		$Gateway = $Purchase->gateway();

		if ( ! empty($_POST["send-note"]) ){
			$user = wp_get_current_user();
			shopp_add_order_event($Purchase->id, 'note',array(
				'note' => stripslashes($_POST['note']),
				'user' => $user->ID
			));

			$Purchase->load_events();
		}

		// Handle Order note processing
		if ( ! empty($_POST['note']) )
			$this->addnote($Purchase->id, stripslashes($_POST['note']), !empty($_POST['send-note']));

		if ( ! empty($_POST['delete-note']) ) {
			$noteid = key($_POST['delete-note']);
			$Note = new ShoppMetaObject(array('id' => $noteid, 'type'=>'order_note'));
			$Note->delete();
		}

		if ( ! empty($_POST['edit-note']) ) {
			$noteid = key($_POST['note-editor']);
			$Note = new ShoppMetaObject(array('id' => $noteid, 'type' => 'order_note'));
			$Note->value->message = stripslashes($_POST['note-editor'][ $noteid ]);
			$Note->save();
		}
		$Notes = new ObjectMeta($Purchase->id,'purchase','order_note');

		if ( isset($_POST['submit-shipments']) && isset($_POST['shipment']) && !empty($_POST['shipment']) ) {
			$shipments = $_POST['shipment'];
			foreach ( (array)$shipments as $shipment ) {
				shopp_add_order_event($Purchase->id, 'shipped', array(
					'tracking'	=>	$shipment['tracking'],
					'carrier'	=>	$shipment['carrier']
				));
			}
			$updated = Shopp::__('Shipping notice sent.');

			// Save shipping carrier default preference for the user
			$userid = get_current_user_id();
			$setting = 'shopp_shipping_carrier';
			if ( ! get_user_meta($userid, $setting, true) )
				add_user_meta($userid, $setting, $shipment['carrier']);
			else update_user_meta($userid, $setting, $shipment['carrier']);

			unset($_POST['ship-notice']);
			$Purchase->load_events();
		}

		if ( isset($_POST['order-action']) && 'refund' == $_POST['order-action'] ) {
			if ( ! current_user_can('shopp_refund') )
				wp_die(__('You do not have sufficient permissions to carry out this action.'));

			$user = wp_get_current_user();
			$reason = (int)$_POST['reason'];
			$amount = Shopp::floatval($_POST['amount']);
			$Purchase->load_events();

			if ( ! empty($_POST['message']) ) {
				$message = $_POST['message'];
				$Purchase->message['note'] = $message;
			}

			if ( $amount <= $Purchase->captured - $Purchase->refunded ) {

				if ( ! Shopp::str_true($_POST['send']) ) { // Force the order status
					shopp_add_order_event($Purchase->id, 'notice', array(
						'user'   => $user->ID,
						'kind'   => 'refunded',
						'notice' => Shopp::__('Marked Refunded')
					));
					shopp_add_order_event($Purchase->id, 'refunded', array(
						'txnid'   => $Purchase->txnid,
						'gateway' => $Gateway->module,
						'amount'  => $amount
					));
					shopp_add_order_event($Purchase->id, 'voided', array(
						'txnorigin' => $Purchase->txnid,	// Original transaction ID (txnid of original Purchase record)
						'txnid'	    => time(),					// Transaction ID for the VOID event
						'gateway'   => $Gateway->module		// Gateway handler name (module name from @subpackage)
					));
				} else {
					shopp_add_order_event($Purchase->id, 'refund', array(
						'txnid'   => $Purchase->txnid,
						'gateway' => $Gateway->module,
						'amount'  => $amount,
						'reason'  => $reason,
						'user'    => $user->ID
					));
				}

				if ( ! empty($_POST['message']) )
					$this->addnote($Purchase->id, $_POST['message']);

				$Purchase->load_events();

			} else $this->notice(Shopp::__('Refund failed. Cannot refund more than the current balance.'), 'error');

		}

		if ( isset($_POST['order-action']) && 'cancel' == $_POST['order-action'] ) {
			if ( ! current_user_can('shopp_void') )
				wp_die(__('You do not have sufficient permissions to carry out this action.'));

			// unset($_POST['refund-order']);
			$user = wp_get_current_user();
			$reason = (int)$_POST['reason'];

			$message = '';
			if ( ! empty($_POST['message']) ) {
				$message = $_POST['message'];
				$Purchase->message['note'] = $message;
			} else $message = 0;


			if ( ! Shopp::str_true($_POST['send']) ) { // Force the order status
				shopp_add_order_event($Purchase->id, 'notice', array(
					'user'   => $user->ID,
					'kind'   => 'cancelled',
					'notice' => Shopp::__('Marked Cancelled')
				));
				shopp_add_order_event($Purchase->id, 'voided', array(
					'txnorigin' => $Purchase->txnid,	// Original transaction ID (txnid of original Purchase record)
					'txnid'     => time(),			// Transaction ID for the VOID event
					'gateway'   => $Gateway->module		// Gateway handler name (module name from @subpackage)
				));
			} else {
				shopp_add_order_event($Purchase->id, 'void', array(
					'txnid'   => $Purchase->txnid,
					'gateway' => $Gateway->module,
					'reason'  => $reason,
					'user'    => $user->ID,
					'note'    => $message
				));
			}

			if ( ! empty($_POST['message']) )
				$this->addnote($Purchase->id, $_POST['message']);

			$Purchase->load_events();
		}

		if ( isset($_POST['billing']) && is_array($_POST['billing']) ) {

			$Purchase->updates($_POST['billing']);
			$Purchase->save();

		}

		if ( isset($_POST['shipping']) && is_array($_POST['shipping']) ) {

			$shipping = array();
			foreach( $_POST['shipping'] as $name => $value )
				$shipping[ "ship$name" ] = $value;

			$Purchase->updates($shipping);
			$Purchase->shipname = $shipping['shipfirstname'] . ' ' . $shipping['shiplastname'];

			$Purchase->save();
		}


		if ( isset($_POST['order-action']) && 'update-customer' == $_POST['order-action'] && ! empty($_POST['customer']) ) {
			$Purchase->updates($_POST['customer']);
			$Purchase->save();
		}

		if ( isset($_POST['cancel-edit-customer']) ){
			unset($_POST['order-action'],$_POST['edit-customer'],$_POST['select-customer'] );
		}

		if ( isset($_POST['order-action']) && 'new-customer' == $_POST['order-action'] && ! empty($_POST['customer']) && ! isset($_POST['cancel-edit-customer']) ) {
			$Customer = new ShoppCustomer();
			$Customer->updates($_POST['customer']);
			$Customer->password = wp_generate_password(12,true);
			if ( 'wordpress' == shopp_setting('account_system') ) $Customer->create_wpuser();
			else unset($_POST['loginname']);
			$Customer->save();
			if ( (int)$Customer->id > 0 ) {
				$Purchase->copydata($Customer);
				$Purchase->save();
			} else $this->notice(Shopp::__('An unknown error occurred. The customer could not be created.'), 'error');
		}

		if ( isset($_GET['order-action']) && 'change-customer' == $_GET['order-action'] && ! empty($_GET['customerid']) ) {
			$Customer = new ShoppCustomer((int)$_GET['customerid']);
			if ( (int)$Customer->id > 0) {
				$Purchase->copydata($Customer);
				$Purchase->customer = $Customer->id;
				$Purchase->save();
			} else $this->notice(Shopp::__('The selected customer was not found.'), 'error');
		}

		if ( isset($_POST['save-item']) && ! empty($_POST['lineid']) ) {

			// Create a cart representation of the order to recalculate order totals
			$Cart = new ShoppCart();
			foreach ($Purchase->purchased as $OrderItem) {
				$CartItem = new Item($OrderItem);
				$Cart->contents[$OrderItem->id] = $CartItem;
			}

			$purchasedid = (int)$_POST['lineid'];
			$Purchased = $Purchase->purchased[$purchasedid];
			if ( $Purchased->id ) {

				$override_total = ( Shopp::floatval($_POST['total']) != $Purchased->total ); // Override total

				$Item = $Cart->contents[$purchasedid];
				$Item->quantity($_POST['quantity']);
				$Item->unitprice = Shopp::floatval($_POST['unitprice']);
				$Item->retotal();
				$Purchased->quantity = $Item->quantity;
				$Purchased->unitprice = $Item->unitprice;
				$Purchased->unittax = $Item->unittax;
				$Purchased->total = $Item->total;
				if ( $override_total ) $Purchased->total = Shopp::floatval($_POST['total']);
				$Purchased->save();
			}

			$Cart->retotal = true;
			$Cart->totals();
			$Purchase->copydata($Cart->Totals);
			$Purchase->save();

		}

		if ( isset($_POST['charge']) && $Gateway && $Gateway->captures ) {
			if ( ! current_user_can('shopp_capture') )
				wp_die(__('You do not have sufficient permissions to carry out this action.'));

			$user = wp_get_current_user();

			shopp_add_order_event($Purchase->id, 'capture', array(
				'txnid'   => $Purchase->txnid,
				'gateway' => $Purchase->gateway,
				'amount'  => $Purchase->capturable(),
				'user'    => $user->ID
			));

			$Purchase->load_events();
		}

		$base = shopp_setting('base_operations');
		$targets = shopp_setting('target_markets');

		$countries = array('' => '&nbsp;');
		$countrydata = Lookup::countries();
		foreach ( $countrydata as $iso => $c ) {

			if ( $base['country'] == $iso )
				$base_region = $c['region'];
			$countries[ $iso ] = $c['name'];
		}
		$Purchase->_countries = $countries;

		$regions = Lookup::country_zones();
		$Purchase->_billing_states = isset($regions[ $Purchase->country ]) ? array_merge(array('' => '&nbsp;'), (array)$regions[ $Purchase->country ]) : array();
		$Purchase->_shipping_states = isset($regions[ $Purchase->shipcountry ]) ? array_merge(array('' => '&nbsp;'), (array)$regions[ $Purchase->shipcountry ]) : array();


		// Setup shipping carriers menu and JS data
		$carriers_menu = $carriers_json = array();
		$shipping_carriers = (array) shopp_setting('shipping_carriers'); // The store-preferred shipping carriers
		$shipcarriers = Lookup::shipcarriers(); // The full list of available shipping carriers
		$notrack = Shopp::__('No Tracking'); // No tracking label
		$default = get_user_meta(get_current_user_id(), 'shopp_shipping_carrier', true);

		if ( isset($shipcarriers[ $default ]) ) {
			$carriers_menu[ $default ] = $shipcarriers[ $default ]->name;
			$carriers_json[ $default ] = array($shipcarriers[ $default ]->name, $shipcarriers[ $default ]->trackpattern);
		} else {
			$carriers_menu['NOTRACKING'] = $notrack;
			$carriers_json['NOTRACKING'] = array($notrack, false);
		}

		$serviceareas = array('*', $base['country']);
		foreach ( $shipcarriers as $code => $carrier ) {
			if ( $code == $default ) continue;
			if ( ! empty($shipping_carriers) && ! in_array($code, $shipping_carriers) ) continue;
			if ( ! in_array($carrier->areas, $serviceareas) ) continue;
			$carriers_menu[ $code ] = $carrier->name;
			$carriers_json[ $code ] = array($carrier->name, $carrier->trackpattern);
		}

		if ( isset($shipcarriers[ $default ]) ) {
			$carriers_menu['NOTRACKING'] = $notrack;
			$carriers_json['NOTRACKING'] = array($notrack, false);
		}

		if ( empty($statusLabels) ) $statusLabels = array('');

		include $this->ui('order.php');
	}

	/**
	 * Retrieves the number of orders in each customized order status label
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function status_counts () {
		$table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$labels = shopp_setting('order_status');

		if (empty($labels)) return false;
		$status = array();

		$alltotal = sDB::query("SELECT count(*) AS total FROM $table", 'auto', 'col', 'total');
		$r = sDB::query("SELECT status,COUNT(status) AS total FROM $table GROUP BY status ORDER BY status ASC", 'array', 'index', 'status');
		$all = array('' => Shopp::__('All Orders'));

		$labels = $all+$labels;

		foreach ($labels as $id => $label) {
			$_ = new StdClass();
			$_->label = $label;
			$_->id = $id;
			$_->total = 0;
			if ( isset($r[ $id ]) ) $_->total = (int)$r[$id]->total;
			if ('' === $id) $_->total = $alltotal;
			$status[$id] = $_;
		}

		return $status;
	}

	public function addnote ($order, $message, $sent = false) {
		$user = wp_get_current_user();
		$Note = new ShoppMetaObject();
		$Note->parent = $order;
		$Note->context = 'purchase';
		$Note->type = 'order_note';
		$Note->name = 'note';
		$Note->value = new stdClass();
		$Note->value->author = $user->ID;
		$Note->value->message = $message;
		$Note->value->sent = $sent;
		$Note->save();
	}

} // class Service

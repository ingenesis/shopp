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
 * Shopp order admin controller
 *
 * @since 1.4
 **/
class ShoppAdminOrders extends ShoppAdminController {

	protected $ui = 'orders';

	/**
	 * Route the screen requests to the screen controller
	 *
	 * @since 1.4
	 *
	 * @return string The screen controller class in charge of the request
	 **/
	protected function route () {
		if ( false !== strpos($this->request('page'), 'orders-new') )
			return 'ShoppScreenOrderEntry';
		elseif ( $this->request('id') )
			return 'ShoppScreenOrderManager';
		else return 'ShoppScreenOrders';
	}

	/**
	 * Retrieves the number of orders in each customized order status label
	 *
	 * @return array|bool The list of order counts by status, or false
	 **/
	public static function status_counts () {
		$table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$labels = shopp_setting('order_status');

		if ( empty($labels) ) return false;

		$statuses = array();

		$alltotal = sDB::query("SELECT count(*) AS total FROM $table", 'auto', 'col', 'total');
		$r = sDB::query("SELECT status,COUNT(status) AS total FROM $table GROUP BY status ORDER BY status ASC", 'array', 'index', 'status');
		$all = array('' => Shopp::__('All Orders'));

		$labels = (array) ( $all + $labels );

		foreach ( $labels as $id => $label ) {
			$status = new StdClass();
			$status->label = $label;
			$status->id = $id;
			$status->total = 0;
			if ( isset($r[ $id ]) ) $status->total = (int) $r[ $id ]->total;
			if ( '' === $id ) $status->total = $alltotal;
			$statuses[ $id ] = $status;
		}

		return $status;
	}

} // class ShoppAdminOrders

/**
 * Orders table screen controller
 *
 * @since 1.1
 **/
class ShoppScreenOrders extends ShoppScreenController {

	protected $ui = 'orders';

	/**
	 * Register the action handlers.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function actions() {
		return array(
			'action'
		);
	}

	/**
	 * Handle bulk actions.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function action () {
		if ( false === $this->request('action') ) return;

		$selected = (array) $this->request('selected');

		$action = Shopp::__('Updated');
		if ( 'delete' == $this->request('action') ) {
			$handler = array($this, 'delete');
			$action = Shopp::__('Deleted');
		} elseif ( is_numeric($this->request('action')) ) {
			$handler = array($this, 'status');
		}

		$processed = 0;
		foreach ( $selected as $selection ) {
			if ( call_user_func($handler, $selection) )
				$processed++;
		}

		if ( 1 == $processed )
			$this->notice(Shopp::__('%s Order <strong>#%d</strong>.', $action, reset($selected)));
		elseif ( $processed > 1 )
			$this->notice(Shopp::__('%s <strong>%d</strong> orders.', $action, $processed));


		shopp_redirect($this->url(array(
			'action' => false,
			'selected' => false,
		)));
	}

	/**
	 * Delete an order with a given ID.
	 *
	 * @since 1.4
	 *
	 * @param string $id The ShoppPurchase ID to delete.
	 * @return bool True if deleted successfully, false otherwise.
	 **/
	public function delete ( $id ) {

		$Purchase = new ShoppPurchase($id);
		if ( ! $Purchase->exists() ) return false;

		$Purchase->delete_purchased();
		$Purchase->delete();

		return true;

	}

	/**
	 * Update the status of an order.
	 *
	 * @since 1.4
	 *
	 * @param string $id The ShoppPurchase ID to update.
	 * @return bool True if deleted successfully, false otherwise.
	 **/
	public function status ( $id ) {

		$Purchase = new ShoppPurchase($id);
		if ( ! $Purchase->exists() ) return false;

		$status = (int) $this->request('action');
		$Purchase->status = $status;
		$Purchase->save();

		return true;

	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function assets () {
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('daterange');
		do_action('shopp_order_admin_scripts');
	}

	/**
	 * Setup the admin table
	 *
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function layout () {
		$this->table('ShoppOrdersTable');
	}

	/**
	 * Interface processor for the orders list interface
	 *
	 * @author Jonathan Davis
	 *
	 * @return void
	 **/
	public function screen () {

		$Orders = $this->orders;
		$ordercount = $this->ordercount;
		$num_pages = ceil($ordercount->total / $per_page);

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('orders.php');
	}

	/**
	 * Recalculate order totals
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	private function retotal ( ShoppPurchase $Purchase ) {
		$Cart = new ShoppCart();

		$taxcountry = $Purchase->country;
		$taxstate = $Purchase->state;
		if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
			$taxcountry = $Purchase->shipcountry;
			$taxstate = $Purchase->shipstate;
		}
		ShoppOrder()->Tax->location($taxcountry, $taxstate);

		foreach ( $Purchase->purchased as $index => &$Purchased )
			$Cart->additem($Purchased->quantity, new ShoppCartItem($Purchased));

		$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

		$Purchase->total = $Cart->total();
		$Purchase->subtotal = $Cart->total('order');
		$Purchase->discount = $Cart->total('discount');
		$Purchase->tax = $Cart->total('tax');
		$Purchase->freight = $Cart->total('shipping');
	}

	public static function navigation () {

		$labels = shopp_setting('order_status');

		if ( empty($labels) ) return false;

		$all = array('' => Shopp::__('All Orders'));

		$table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);

		$alltotal = sDB::query("SELECT count(*) AS total FROM $table", 'auto', 'col', 'total');
		$r = sDB::query("SELECT status,COUNT(status) AS total FROM $table GROUP BY status ORDER BY status ASC", 'array', 'index', 'status');

		$labels = (array) ( $all + $labels );

		echo '<ul class="subsubsub">';
		foreach ( $labels as $id => $label ) {
			$args = array('status' => $id, 'id' => null);
			$url = add_query_arg(array_merge($_GET, $args));

			$status = isset($_GET['status']) ? $_GET['status'] : '';
			if ( is_numeric($status) ) $status = intval($status);
			$classes = $status === $id ? ' class="current"' : '';

			$separator = '| ';
			if ( '' === $id ) {
				$separator = '';
				$total = $alltotal;
			}

			if ( isset($r[ $id ]) )
				$total = (int) $r[ $id ]->total;

			echo '	<li>' . $separator . '<a href="' . esc_url($url) . '"' . $classes . '>' . esc_html($label) . '</a>&nbsp;<span class="count">(' . esc_html($total) . ')</span></li>';

		}
		echo '</ul>';
	}

} // class ShoppScreenOrders

/**
 * Images Table UI renderer
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppOrdersTable extends ShoppAdminTable {

	/** @var private $gateways List of gateway modules */
	private $gateways = array();

	private $statuses = array();

	private $txnstatuses = array();

	public function prepare_items() {

		$defaults = array(
			'page' => false,
			'deleting' => false,
			'selected' => false,
			'update' => false,
			'newstatus' => false,
			'pagenum' => 1,
			'paged' => 1,
			'per_page' => 20,
			'start' => '',
			'end' => '',
			'status' => false,
			's' => '',
			'range' => '',
			'startdate' => '',
			'enddate' => '',
		);

		$args = array_merge($defaults, $this->request());
		extract($args, EXTR_SKIP);

		// $url = $this->url($_GET);

		$statusLabels = shopp_setting('order_status');
		if (empty($statusLabels)) $statusLabels = array('');
		$txnstatus_labels = Lookup::txnstatus_labels();

		$Purchase = new ShoppPurchase();

		$offset = get_option( 'gmt_offset' ) * 3600;

		if ( $this->request('start') ) {
			list($month, $day, $year) = explode("/", $this->request('start'));
			$starts = mktime(0, 0, 0, $month, $day, $year);
		}
		if ( $this->request('end') ) {
			list($month, $day, $year) = explode("/", $this->request('end'));
			$ends = mktime(23, 59, 59, $month, $day, $year);
		}
		$pagenum = absint( $paged );
		$start = ( $per_page * ( $pagenum - 1 ) );

		$where = array();
		$joins = array();
		if ( ! empty($status) || '0' === $status ) $where[] = "status='" . sDB::escape($status) . "'";
		if ( ! empty($s) ) {
			$s = stripslashes($s);
			$search = array();
			if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $s, $props, PREG_SET_ORDER) > 0 ) {
				foreach ( $props as $query ) {
					$keyword = sDB::escape( ! empty($query[2]) ? $query[2] : $query[3] );
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
			} elseif ( strpos($s, '@') !== false ) {
				 $where[] = "email='" . sDB::escape($s) . "'";
			} else $where[] = "(id='$s' OR CONCAT(firstname,' ',lastname) LIKE '%" . sDB::escape($s) . "%')";
		}

		if ( ! empty($starts) && ! empty($ends) ) $where[] = "created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";

		if ( ! empty($customer) ) $where[] = "customer=" . intval($customer);
		$where = ! empty($where) ? "WHERE " . join(' AND ', $where) : '';
		$joins = join(' ', $joins);

		$countquery = "SELECT count(*) as total,SUM(IF(txnstatus IN ('authed','captured'),total,NULL)) AS sales,AVG(IF(txnstatus IN ('authed','captured'),total,NULL)) AS avgsale FROM $Purchase->_table AS o $joins $where ORDER BY o.created DESC LIMIT 1";
		$this->ordercount = sDB::query($countquery, 'object');

		$query = "SELECT o.* FROM $Purchase->_table AS o $joins $where ORDER BY created DESC LIMIT $start,$per_page";
		$this->items = sDB::query($query, 'array', 'index', 'id');

		$num_pages = ceil($this->ordercount->total / $per_page);
		if ( $paged > 1 && $paged > $num_pages ) Shopp::redirect( add_query_arg('paged', null, $url) );


		$Gateways = Shopp::object()->Gateways;
		$this->gateways = array_merge($Gateways->modules, array('ShoppFreeOrder' => $Gateways->freeorder));

		$this->statuses = (array) shopp_setting('order_status');
		$this->txnstatuses = ShoppLookup::txnstatus_labels();

		// Convert other date formats to numeric but preserve the order of the month/day/year or day/month/year
		$date_format = get_option('date_format');
		$date_format = preg_replace("/[^A-Za-z0-9]/", '', $date_format);
		// Force month display to numeric with leading zeros
		$date_format = str_replace(array('n', 'F', 'M'), 'm/', $date_format);
		// Force day display to numeric with leading zeros
		$date_format = str_replace(array('j'), 'd/', $date_format);
		// Force year display to 4-digits
		$date_format = str_replace('y', 'Y/', $date_format);
		$date_format = preg_replace("/[^dmY0-9\/]/", '', $date_format);
		$this->dates = trim($date_format, '/');

		$this->set_pagination_args( array(
			'total_items' => $this->ordercount->total,
			'total_pages' => $this->ordercount->total / $per_page,
			'per_page' => $per_page
		) );
	}

	protected function get_bulk_actions( $which ) {
		if ( 'bottom' == $which ) return;
		$actions = array(
			'delete' => __( 'Delete' ),
		);
		$statuses = shopp_setting('order_status');

		return $actions + $statuses;
	}

	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) $this->bottom_tablenav();
		if ( 'top' == $which )    $this->top_tablenav();
	}

	protected function bottom_tablenav () {
		if ( ! current_user_can('shopp_financials') || ! current_user_can('shopp_export_orders') ) return;

		$exporturl = add_query_arg(urlencode_deep(array_merge(stripslashes_deep($_GET), array('src' => 'export_purchases'))));

		echo  '<div class="alignleft actions">'
			. '	</form><form action="' . esc_url($exporturl) . '" id="log" method="post">'
			. '		<button type="button" id="export-settings-button" name="export-settings" class="button-secondary">' . Shopp::__('Export Options') . '</button>'

			. '	<div id="export-settings" class="hidden">'
			. '		<div id="export-columns" class="multiple-select">'
			. '			<ul>';

		$even = true;

		echo '				<li' . ( $even ? '' : ' class="odd"' ) . '><input type="checkbox" name="selectall_columns" id="selectall_columns" /><label for="selectall_columns"><strong>' . Shopp::__('Select All') . '</strong></label></li>';

		$even = ! $even;

		echo '				<li' . ( $even ? '' : ' class="odd"' ) . '><input type="hidden" name="settings[purchaselog_headers]" value="off" /><input type="checkbox" name="settings[purchaselog_headers]" id="purchaselog_headers" value="on" /><label for="purchaselog_headers"><strong>' . Shopp::__('Include column headings') . '</strong></label></li>';

		$even = ! $even;

		$exportcolumns = array_merge(ShoppPurchase::exportcolumns(), ShoppPurchased::exportcolumns());
		$selected = shopp_setting('purchaselog_columns');
		if ( empty($selected) ) $selected = array_keys($exportcolumns);

		foreach ( $exportcolumns as $name => $label ) {
			if ( 'cb' == $name ) continue;
			echo '				<li' . ( $even ? '' : ' class="odd"' ) . '><input type="checkbox" name="settings[purchaselog_columns][]" value="' . esc_attr($name) . '" id="column-' . esc_attr($name) . '" ' . ( in_array($name, $selected) ? ' checked="checked"' : '' ) . ' /><label for="column-' . esc_attr($name) . '">' . esc_html($label) . '</label></li>';
			$even = ! $even;

		}

		echo  '			</ul>'
			. '		</div>';

		PurchasesIIFExport::settings();

		$exports = array(
			'tab' => Shopp::__('Tab-separated.txt'),
			'csv' => Shopp::__('Comma-separated.csv'),
			'iif' => Shopp::__('Intuit&reg; QuickBooks.iif')
		);

		$format = shopp_setting('purchaselog_format');
		if ( ! $format ) $format = 'tab';

		echo  '		<br />'
			. '		<select name="settings[purchaselog_format]" id="purchaselog-format">'
			. '			' . menuoptions($exports, $format, true)
			. '		</select>'
			. '		</div>'

			. '	<button type="submit" id="download-button" name="download" value="export" class="button-secondary"' . ( count($this->items) < 1 ? ' disabled="disabled"' : '' ) . '>' . Shopp::__('Download') . '</button>'
			. '	<div class="clear"></div>'
			. '	</form>'
			. '</div>';
	}

	protected function top_tablenav () {
		$range = $this->request('range') ? $this->request('range') : 'all';
		$ranges = array(
			'all'         => Shopp::__('Show All Orders'),
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
			'custom'      => Shopp::__('Custom Dates'),
		);

		echo  '<div class="alignleft actions">'
		  	. '<select name="range" id="range">'
			. '	' . Shopp::menuoptions($ranges, $range, true)
			. '</select>'

			. '<div id="dates" class="hide-if-js"><div id="start-position" class="calendar-wrap">'
			. '<input type="text" id="start" name="start" value="' . esc_attr($this->request('start')) . '" size="10" class="search-input selectall" />'
			. '</div>'

			. '&hellip;'

			. '<div id="end-position" class="calendar-wrap">'
			. '<input type="text" id="end" name="end" value="' . esc_attr($this->request('end')) . '" size="10" class="search-input selectall" />'
			. '</div></div>'

			. '<button type="submit" id="filter-button" name="filter" value="order" class="button-secondary">' . Shopp::__('Filter') . '</button>'
			. '</div>';

	}

	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />',
			'order'       => Shopp::__('Order'),
			'name'        => Shopp::__('Name'),
			'destination' => Shopp::__('Destination'),
			'txn'         => Shopp::__('Transaction'),
			'date'        => Shopp::__('Date'),
			'total'       => Shopp::__('Total')
		);
	}

	public function no_items() {
		Shopp::_e('No orders, yet.');
	}

	public function column_default( $Item ) {
		return '';
	}

	public function column_cb( $Item ) {
		return '<input type="checkbox" name="selected[]" value="' . $Item->id . '" />';
	}

	public function column_order( $Item ) {
		$url = add_query_arg('id', $Item->id);
		return '<a class="row-title" href="' . esc_url($url) . '" title="' . Shopp::__('View Order #%d', $Item->id) . '">' . Shopp::__('Order #%d', $Item->id) . '</a>';
	}

	public function column_name( $Item ) {
		if ( '' == trim($Item->firstname . $Item->lastname) )
			$customer = '(' . Shopp::__('no contact name') . ')';
		else $customer = ucfirst($Item->firstname . ' ' . $Item->lastname);

		$url = add_query_arg( array( 'page' => 'shopp-customers', 'id' => $Item->customer ) );

		return '<a href="' . esc_url($url) . '">' . esc_html($customer) . '</a>';
		if ( '' != trim($Item->company) )
			return "<br />" . esc_html($Item->company);
	}

	public function column_destination( $Item ) {
		$addrfields = array('city','state','country');
		$format = '%3$s, %2$s &mdash; %1$s';

		if ( empty($Item->shipaddress) )
			$location = sprintf($format, $Item->country, $Item->state, $Item->city);
		else $location = sprintf($format, $Item->shipcountry, $Item->shipstate, $Item->shipcity);

		$location = ltrim($location, ' ,');
		if ( 0 === strpos($location,'&mdash;') )
			$location = str_replace('&mdash; ', '', $location);
		$location = str_replace(',   &mdash;', ' &mdash;', $location);

		return esc_html($location);
	}

	public function column_txn( $Item ) {
		return $Item->txnid;

		if ( isset($this->gateways[ $Item->gateway ]) )
			echo '<br />' . esc_html($this->gateways[ $Item->gateway ]->name);
	}

	public function column_date( $Item ) {
		return date($this->dates, mktimestamp($Item->created));

		if ( isset($this->statuses[ $Item->status ]) )
			return '<br /><strong>' . esc_html($this->statuses[ $Item->status ]) . '</strong>';
	}

	public function column_total( $Item ) {
		return money($Item->total);

		$status = $Item->txnstatus;
		if ( isset($this->txnstatuses[ $Item->txnstatus ]) )
			$status = $this->txnstatuses[ $Item->txnstatus ];

		return '<br /><span class="status">' . esc_html($status) . '</span>';
	}

} // class ShoppOrdersTable

class ShoppScreenOrderManager extends ShoppScreenController {

	public function load() {
		$id = (int) $_GET['id'];
		if ( $id > 0 ) {
			ShoppPurchase( new ShoppPurchase($id) );
			ShoppPurchase()->load_purchased();
			ShoppPurchase()->load_events();
		} else ShoppPurchase( new ShoppPurchase() );
	}

	/**
	 * Enqueue the scripts
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function assets() {

		wp_enqueue_script('postbox');

		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('selectize');

		shopp_enqueue_script('orders');
		shopp_custom_script('orders', 'var address = [], carriers = ' . json_encode($this->shipcarriers()) . ';');
		shopp_localize_script( 'orders', '$om', array(
			'co'     => Shopp::__('Cancel Order'),
			'mr'     => Shopp::__('Mark Refunded'),
			'pr'     => Shopp::__('Process Refund'),
			'dnc'    => Shopp::__('Do Not Cancel'),
			'ro'     => Shopp::__('Refund Order'),
			'cancel' => Shopp::__('Cancel'),
			'rr'     => Shopp::__('Reason for refund'),
			'rc'     => Shopp::__('Reason for cancellation'),
			'mc'     => Shopp::__('Mark Cancelled'),
			'stg'    => Shopp::__('Send to gateway')
		));

		shopp_enqueue_script('address');
		shopp_custom_script('address', 'var regions = ' . json_encode(ShoppLookup::country_zones()) . ';');

		do_action('shopp_order_management_scripts');
	}

	public function ops() {
		return array(
			'remove_item',
			'save_item',
			'save_totals',
		);
	}

	public function remove_item() {

		if ( ! $this->form('rmvline') ) return;

		$Purchase = new ShoppPurchase($this->form('id'));
		if ( ! $Purchase->exists() ) return;

		$lineid = (int)$this->form('rmvline');
		if ( isset($Purchase->purchased[ $lineid ]) ) {
			$Purchase->purchased[ $lineid ]->delete();
			unset($Purchase->purchased[ $lineid ]);
		}

		$Cart = new ShoppCart();

		$taxcountry = $Purchase->country;
		$taxstate = $Purchase->state;
		if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
			$taxcountry = $Purchase->shipcountry;
			$taxstate = $Purchase->shipstate;
		}
		ShoppOrder()->Tax->location($taxcountry, $taxstate);

		foreach ( $Purchase->purchased as &$Purchased )
			$Cart->additem($Purchased->quantity, new ShoppCartItem($Purchased));

		$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

		$Purchase->total = $Cart->total();
		$Purchase->subtotal = $Cart->total('order');
		$Purchase->discount = $Cart->total('discount');
		$Purchase->tax = $Cart->total('tax');
		$Purchase->freight = $Cart->total('shipping');
		$Purchase->save();

		$Purchase->load_purchased();

		$this->notice(Shopp::__('Item removed from the order.'));

	}

	public function save_item() {

		if ( false === $this->form('save-item') || false === $lineid = $this->form('lineid') ) return;

		$Purchase = new ShoppPurchase($this->form('id'));
		if ( ! $Purchase->exists() ) return;

		$new = ( '' === $lineid );
		$name = $this->form('itemname');
		if ( $this->form('product') ) {
			list($productid, $priceid) = explode('-', $this->form('product'));
			$Product = new ShoppProduct($productid);
			$Price = new ShoppPrice($priceid);
			$name = $Product->name;
			if ( Shopp::__('Price & Delivery') != $Price->label )
				$name .= ": $Price->label";
		}

		// Create a cart representation of the order to recalculate order totals
		$Cart = new ShoppCart();

		$taxcountry = $Purchase->country;
		$taxstate = $Purchase->state;
		if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
			$taxcountry = $Purchase->shipcountry;
			$taxstate = $Purchase->shipstate;
		}
		ShoppOrder()->Tax->location($taxcountry, $taxstate);

		if ( $new ) {
			$NewLineItem = new ShoppPurchased();
			$NewLineItem->purchase = $Purchase->id;
			$Purchase->purchased[] = $NewLineItem;
		}

		foreach ( $Purchase->purchased as &$Purchased ) {
			$CartItem = new ShoppCartItem($Purchased);

			if ( $Purchased->id == $lineid || ( $new && empty($Purchased->id) ) ) {

				if ( ! empty( $_POST['product']) ) {
					list($CartItem->product, $CartItem->priceline) = explode('-', $this->form('product'));
				} elseif ( ! empty($_POST['id']) ) {
					list($CartItem->product, $CartItem->priceline) = explode('-', $this->form('id'));
				}

				$CartItem->name = $name;
				$CartItem->unitprice = Shopp::floatval($this->form('unitprice'));
				$Cart->additem((int)$this->form('quantity'), $CartItem);
				$CartItem = $Cart->get($CartItem->fingerprint());

				$Purchased->name      = $CartItem->name;
				$Purchased->product   = $CartItem->product;
				$Purchased->price     = $CartItem->priceline;
				$Purchased->quantity  = $CartItem->quantity;
				$Purchased->unitprice = $CartItem->unitprice;
				$Purchased->total     = $CartItem->total;

				$Purchased->save();

			} else $Cart->additem($CartItem->quantity, $CartItem);

			$this->notice(Shopp::__('Updates saved.'));

		}

		$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

		$Purchase->total = $Cart->total();
		$Purchase->subtotal = $Cart->total('order');
		$Purchase->discount = $Cart->total('discount');
		$Purchase->tax = $Cart->total('tax');
		$Purchase->freight = $Cart->total('shipping');
		$Purchase->save();
		$Purchase->load_purchased();

	}

	public function save_totals() {

		if ( ! $this->form('save-totals') ) return;

		$Purchase = new ShoppPurchase($this->form('id'));
		if ( ! $Purchase->exists() ) return;

		$totals = array();
		if ( $this->form('totals') )
			$totals = $this->form('totals');

		$objects = array(
			'tax' => 'OrderAmountTax',
			'shipping' => 'OrderAmountShipping',
			'discount' => 'OrderAmountDiscount'
		);

		$methods = array(
			'fee' => 'fees',
			'tax' => 'taxes',
			'shipping' => 'shipfees',
			'discount' => 'discounts'
		);

		$total = 0;
		foreach ( $totals as $property => $fields ) {
			if ( empty($fields) ) continue;

			if ( count($fields) > 1 ) {
				if ( isset($fields['labels']) ) {
					$labels = $fields['labels'];
					unset($fields['labels']);
					if ( count($fields) > count($labels) )
						$totalfield = array_pop($fields);

					$fields = array_combine($labels, $fields);
				}

				$fields = array_map(array('Shopp', 'floatval'), $fields);

				$entries = array();
				$OrderAmountObject = isset($objects[ $property ]) ? $objects[ $property ] : 'OrderAmountFee';
				foreach ( $fields as $label => $amount )
					$entries[] = new $OrderAmountObject(array('id' => count($entries) + 1, 'label' => $label, 'amount' => $amount));

				$savetotal = isset($methods[ $property ]) ? $methods[ $property ] : $fees;
				$Purchase->$savetotal($entries);

				$sum = array_sum($fields);
				if ( $sum > 0 )
					$Purchase->$property = $sum;

			} else $Purchase->$property = Shopp::floatval($fields[0]);

			$total += ('discount' == $property ? $Purchase->$property * -1 : $Purchase->$property );

		}

		$Purchase->total = $Purchase->subtotal + $total;
		$Purchase->save();
	}

	public function shipcarriers() {

		$shipcarriers = ShoppLookup::shipcarriers(); // The full list of available shipping carriers
		$selectcarriers = (array) shopp_setting('shipping_carriers'); // The store-preferred shipping carriers

		$default = get_user_meta(get_current_user_id(), 'shopp_shipping_carrier', true); // User's last used carrier

		// Add "No Tracking" option
		$shipcarriers['NOTRACKING'] = json_decode('{"name":"' . Shopp::__('No tracking') . '","trackpattern":false,"areas":"*"}');
		$selectcarriers[] = 'NOTRACKING';

		$carriers = array();
		$serviceareas = array('*', ShoppBaseLocale()->country());
		foreach ( $shipcarriers as $code => $carrier ) {
			if ( ! empty($selectcarriers) && ! in_array($code, $selectcarriers) ) continue;
			if ( ! in_array($carrier->areas, $serviceareas) ) continue;
			$carriers[ $code ] = array($carrier->name, $carrier->trackpattern);
		}

		$first = isset($carriers[ $default ]) ? $default : 'NOTRACKING';
		return array($first => $carriers[ $first ]) + $carriers;
	}

	/**
	 * Provides overall layout for the order manager interface
	 *
	 * Makes use of WordPress postboxes to generate panels (box) content
	 * containers that are customizable with drag & drop, collapsable, and
	 * can be toggled to be hidden or visible in the interface.
	 *
	 * @return
	 **/
	public function layout () {

		$Purchase = ShoppPurchase();

		$default = array('' => '&nbsp;');
		$Purchase->_countries = array_merge($default, ShoppLookup::countries());

		$regions = ShoppLookup::country_zones();
		$Purchase->_billing_states = array_merge($default, (array)$regions[ $Purchase->country ]);
		$Purchase->_shipping_states = array_merge($default, (array)$regions[ $Purchase->shipcountry ]);

		ShoppUI::register_column_headers($this->id, apply_filters('shopp_order_manager_columns', array(
			'items' => Shopp::__('Items'),
			'qty'   => Shopp::__('Quantity'),
			'price' => Shopp::__('Price'),
			'total' => Shopp::__('Total')
		)));

		$references = array('Purchase' => $Purchase);

		new ShoppAdminOrderContactBox($this, 'side', 'core', $references);
		new ShoppAdminOrderBillingAddressBox($this, 'side', 'high', $references);

		if ( ! empty($Purchase->shipaddress) )
			new ShoppAdminOrderShippingAddressBox($this, 'side', 'core', $references);


		new ShoppAdminOrderManageBox($this, 'normal', 'core', $references);

		if ( isset($Purchase->data) && '' != join('', (array)$Purchase->data) || apply_filters('shopp_orderui_show_orderdata', false) )
			new ShoppAdminOrderDataBox($this, 'normal', 'core', $references);

		if ( count($Purchase->events) > 0 )
			new ShoppAdminOrderHistoryBox($this, 'normal', 'core', $references);

		new ShoppAdminOrderNotesBox($this, 'normal', 'core', $references);

		do_action('shopp_order_manager_layout');

	}

	/**
	 * Interface processor for the order manager
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function screen () {

		if ( ! current_user_can('shopp_orders') )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		$Purchase = ShoppPurchase();
		$Purchase->Customer = new ShoppCustomer($Purchase->customer);
		$Gateway = $Purchase->gateway();








		// $targets = shopp_setting('target_markets');
		// $default = array('' => '&nbsp;');
		// $Purchase->_countries = array_merge($default, ShoppLookup::countries());
		//
		// $regions = ShoppLookup::country_zones();
		// $Purchase->_billing_states = array_merge($default, (array)$regions[ $Purchase->country ]);
		// $Purchase->_shipping_states = array_merge($default, (array)$regions[ $Purchase->shipcountry ]);

		// Setup shipping carriers menu and JS data
		// $carriers_menu = $carriers_json = array();
		// $shipping_carriers = (array) shopp_setting('shipping_carriers'); // The store-preferred shipping carriers
		// $shipcarriers = Lookup::shipcarriers(); // The full list of available shipping carriers
		// $notrack = Shopp::__('No Tracking'); // No tracking label
		// $default = get_user_meta(get_current_user_id(), 'shopp_shipping_carrier', true);
		//
		// if ( isset($shipcarriers[ $default ]) ) {
		// 	$carriers_menu[ $default ] = $shipcarriers[ $default ]->name;
		// 	$carriers_json[ $default ] = array($shipcarriers[ $default ]->name, $shipcarriers[ $default ]->trackpattern);
		// } else {
		// 	$carriers_menu['NOTRACKING'] = $notrack;
		// 	$carriers_json['NOTRACKING'] = array($notrack, false);
		// }
		//
		// $serviceareas = array('*', ShoppBaseLocale()->country());
		// foreach ( $shipcarriers as $code => $carrier ) {
		// if ( $code == $default ) continue;
		// if ( ! empty($shipping_carriers) && ! in_array($code, $shipping_carriers) ) continue;
		// 	if ( ! in_array($carrier->areas, $serviceareas) ) continue;
		// 	$carriers_menu[ $code ] = $carrier->name;
		// 	$carriers_json[ $code ] = array($carrier->name, $carrier->trackpattern);
		// }
		//
		// if ( isset($shipcarriers[ $default ]) ) {
		// 	$carriers_menu['NOTRACKING'] = $notrack;
		// 	$carriers_json['NOTRACKING'] = array($notrack, false);
		// }

		if ( empty($statusLabels) ) $statusLabels = array('');

		$Purchase->taxes();
		$Purchase->discounts();

		$columns = get_column_headers($this->id);
		$hidden = get_hidden_columns($this->id);

		include $this->ui('order.php');
	}

} // class ShoppScreenOrderManager

class ShoppScreenOrderEntry extends ShoppScreenOrderManager {

	public function load () {
		return ShoppPurchase(new ShoppPurchase());
	}

	public function layout () {

		$Purchase = ShoppPurchase();

		ShoppUI::register_column_headers($this->id, apply_filters('shopp_order_manager_columns',array(
			'items' => __('Items','Shopp'),
			'qty' => __('Quantity','Shopp'),
			'price' => __('Price','Shopp'),
			'total' => __('Total','Shopp')
		)));

		new ShoppAdminOrderContactBox(
			$this->id,
			'topside',
			'core',
			array('Purchase' => $Purchase)
		);

		new ShoppAdminOrderBillingAddressBox(
			$this->id,
			'topic',
			'core',
			array('Purchase' => $Purchase)
		);


		new ShoppAdminOrderShippingAddressBox(
			$this->id,
			'topsider',
			'core',
			array('Purchase' => $Purchase)
		);

		new ShoppAdminOrderManageBox(
			$this->id,
			'normal',
			'core',
			array('Purchase' => $Purchase, 'Gateway' => $Purchase->gateway())
		);

		if ( isset($Purchase->data) && '' != join('', (array)$Purchase->data) || apply_filters('shopp_orderui_show_orderdata', false) )
			new ShoppAdminOrderDataBox(
				$this->id,
				'normal',
				'core',
				array('Purchase' => $Purchase)
			);

		if ( count($Purchase->events) > 0 )
			new ShoppAdminOrderHistoryBox(
				$this->id,
				'normal',
				'core',
				array('Purchase' => $Purchase)
			);

		new ShoppAdminOrderNotesBox(
			$this->id,
			'normal',
			'core',
			array('Purchase' => $Purchase)
		);

		do_action('shopp_order_new_layout');
	}

	function screen () {
		if ( ! current_user_can('shopp_orders') )
			wp_die(__('You do not have sufficient permissions to access this page.','Shopp'));

		$Purchase = ShoppPurchase();
		$Purchase->Customer = new ShoppCustomer($Purchase->customer);
		$Gateway = $Purchase->gateway();

		if ( ! empty($_POST['send-note']) ){
			$user = wp_get_current_user();
			shopp_add_order_event($Purchase->id,'note',array(
				'note' => stripslashes($_POST['note']),
				'user' => $user->ID
			));

			$Purchase->load_events();
		}

		if ( isset($_POST['submit-shipments']) && isset($_POST['shipment']) && !empty($_POST['shipment']) ) {
			$shipments = $_POST['shipment'];
			foreach ((array)$shipments as $shipment) {
				shopp_add_order_event($Purchase->id,'shipped',array(
					'tracking' => $shipment['tracking'],
					'carrier' => $shipment['carrier']
				));
			}
			$updated = __('Shipping notice sent.','Shopp');

			// Save shipping carrier default preference for the user
			$userid = get_current_user_id();
			$setting = 'shopp_shipping_carrier';
			if ( ! get_user_meta($userid, $setting, true) )
				add_user_meta($userid, $setting, $shipment['carrier']);
			else update_user_meta($userid, $setting, $shipment['carrier']);

			unset($_POST['ship-notice']);
			$Purchase->load_events();
		}

		if (isset($_POST['order-action']) && 'refund' == $_POST['order-action']) {
			if ( ! current_user_can('shopp_refund') )
				wp_die(__('You do not have sufficient permissions to carry out this action.','Shopp'));

			$user = wp_get_current_user();
			$reason = (int)$_POST['reason'];
			$amount = Shopp::floatval($_POST['amount']);

			if (!empty($_POST['message'])) {
				$message = $_POST['message'];
				$Purchase->message['note'] = $message;
			}

			if (!Shopp::str_true($_POST['send'])) { // Force the order status
				shopp_add_order_event($Purchase->id,'notice',array(
					'user' => $user->ID,
					'kind' => 'refunded',
					'notice' => __('Marked Refunded','Shopp')
				));
				shopp_add_order_event($Purchase->id,'refunded',array(
					'txnid' => $Purchase->txnid,
					'gateway' => $Gateway->module,
					'amount' => $amount
				));
				shopp_add_order_event($Purchase->id,'voided',array(
					'txnorigin' => $Purchase->txnid,	// Original transaction ID (txnid of original Purchase record)
					'txnid' => time(),					// Transaction ID for the VOID event
					'gateway' => $Gateway->module		// Gateway handler name (module name from @subpackage)
				));
			} else {
				shopp_add_order_event($Purchase->id,'refund',array(
					'txnid' => $Purchase->txnid,
					'gateway' => $Gateway->module,
					'amount' => $amount,
					'reason' => $reason,
					'user' => $user->ID
				));
			}

			if (!empty($_POST['message']))
				$this->addnote($Purchase->id,$_POST['message']);

			$Purchase->load_events();
		}

		if (isset($_POST['order-action']) && 'cancel' == $_POST['order-action']) {
			if ( ! current_user_can('shopp_void') )
				wp_die(__('You do not have sufficient permissions to carry out this action.','Shopp'));

			// unset($_POST['refund-order']);
			$user = wp_get_current_user();
			$reason = (int)$_POST['reason'];

			$message = '';
			if (!empty($_POST['message'])) {
				$message = $_POST['message'];
				$Purchase->message['note'] = $message;
			} else $message = 0;


			if (!Shopp::str_true($_POST['send'])) { // Force the order status
				shopp_add_order_event($Purchase->id,'notice',array(
					'user' => $user->ID,
					'kind' => 'cancelled',
					'notice' => __('Marked Cancelled','Shopp')
				));
				shopp_add_order_event($Purchase->id,'voided',array(
					'txnorigin' => $Purchase->txnid,	// Original transaction ID (txnid of original Purchase record)
					'txnid' => time(),			// Transaction ID for the VOID event
					'gateway' => $Gateway->module		// Gateway handler name (module name from @subpackage)
				));
			} else {
				shopp_add_order_event($Purchase->id,'void',array(
					'txnid' => $Purchase->txnid,
					'gateway' => $Gateway->module,
					'reason' => $reason,
					'user' => $user->ID,
					'note' => $message
				));
			}

			if ( ! empty($_POST['message']) )
				$this->addnote($Purchase->id,$_POST['message']);

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


		if ( isset($_POST['order-action']) && 'update-customer' == $_POST['order-action'] && ! empty($_POST['customer'])) {
			$Purchase->updates($_POST['customer']);
			$Purchase->save();
		}

		if ( isset($_POST['cancel-edit-customer']) ){
			unset($_POST['order-action'],$_POST['edit-customer'],$_POST['select-customer']);
		}

		// Create a new customer
		if ( isset($_POST['order-action']) && 'new-customer' == $_POST['order-action'] && ! empty($_POST['customer']) && ! isset($_POST['cancel-edit-customer'])) {
			$Customer = new ShoppCustomer();
			$Customer->updates($_POST['customer']);
			$Customer->password = wp_generate_password(12,true);
			if ( 'wordpress' == shopp_setting('account_system') ) $Customer->create_wpuser();
			else unset($_POST['loginname']);
			$Customer->save();
			if ( (int)$Customer->id > 0 ) {
				$Purchase->customer = $Customer->id;
				$Purchase->copydata($Customer);
				$Purchase->save();

				// New billing address, create record for new customer
				if ( isset($_POST['billing']) && is_array($_POST['billing']) && empty($_POST['billing']['id']) ) {
					$Billing = new BillingAddress($_POST['billing']);
					$Billing->customer = $Customer->id;
					$Billing->save();
				}

				// New shipping address, create record for new customer
				if ( isset($_POST['shipping']) && is_array($_POST['shipping']) && empty($_POST['shipping']['id']) ) {
					$Shipping = new ShippingAddress($_POST['shipping']);
					$Shipping->customer = $Customer->id;
					$Shipping->save();
				}

			} else $this->notice(Shopp::__('An unknown error occured. The customer could not be created.'), 'error');
		}

		if ( isset($_GET['order-action']) && 'change-customer' == $_GET['order-action'] && ! empty($_GET['customerid'])) {
			$Customer = new ShoppCustomer((int)$_GET['customerid']);
			if ( (int)$Customer->id > 0) {
				$Purchase->copydata($Customer);
				$Purchase->customer = $Customer->id;
				$Purchase->save();
			} else $this->notice(Shopp::__('The selected customer was not found.'), 'error');
		}

		if ( isset($_POST['save-item']) && isset($_POST['lineid']) ) {

			if ( isset($_POST['lineid']) && '' == $_POST['lineid'] ) {
				$lineid = 'new';
			} else $lineid = (int)$_POST['lineid'];

			$name = $_POST['itemname'];
			if ( ! empty( $_POST['product']) ) {
				list($productid, $priceid) = explode('-', $_POST['product']);
				$Product = new ShoppProduct($productid);
				$Price = new ShoppPrice($priceid);
				$name = $Product->name;
				if ( Shopp::__('Price & Delivery') != $Price->label )
					$name .= ": $Price->label";
			}

			// Create a cart representation of the order to recalculate order totals
			$Cart = new ShoppCart();

			$taxcountry = $Purchase->country;
			$taxstate = $Purchase->state;
			if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
				$taxcountry = $Purchase->shipcountry;
				$taxstate = $Purchase->shipstate;
			}
			ShoppOrder()->Tax->location($taxcountry, $taxstate);

			if ( 'new' == $lineid ) {
				$NewLineItem = new ShoppPurchased();
				$NewLineItem->purchase = $Purchase->id;
				$Purchase->purchased[] = $NewLineItem;
			}

			foreach ( $Purchase->purchased as &$Purchased ) {
				$CartItem = new ShoppCartItem($Purchased);

				if ( $Purchased->id == $lineid || ('new' == $lineid && empty($Purchased->id) ) ) {

					if ( ! empty( $_POST['product']) ) {
						list($CartItem->product, $CartItem->priceline) = explode('-', $_POST['product']);
					} elseif ( ! empty($_POST['id']) ) {
						list($CartItem->product, $CartItem->priceline) = explode('-', $_POST['id']);
					}

					$CartItem->name = $name;
					$CartItem->unitprice = Shopp::floatval($_POST['unitprice']);
					$Cart->additem((int)$_POST['quantity'], $CartItem);
					$CartItem = $Cart->get($CartItem->fingerprint());

					$Purchased->name = $CartItem->name;
					$Purchased->product = $CartItem->product;
					$Purchased->price = $CartItem->priceline;
					$Purchased->quantity = $CartItem->quantity;
					$Purchased->unitprice = $CartItem->unitprice;
					$Purchased->total = $CartItem->total;
					$Purchased->save();

				} else $Cart->additem($CartItem->quantity, $CartItem);

			}

			$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

			$Purchase->total = $Cart->total();
			$Purchase->subtotal = $Cart->total('order');
			$Purchase->discount = $Cart->total('discount');
			$Purchase->tax = $Cart->total('tax');
			$Purchase->freight = $Cart->total('shipping');
			$Purchase->save();
			$Purchase->load_purchased();

		}

		if ( ! empty($_POST['save-totals']) ) {

			$totals = array();
			if ( ! empty($_POST['totals']) )
				$totals = $_POST['totals'];

			$objects = array(
				'tax' => 'OrderAmountTax',
				'shipping' => 'OrderAmountShipping',
				'discount' => 'OrderAmountDiscount'
			);

			$methods = array(
				'fee' => 'fees',
				'tax' => 'taxes',
				'shipping' => 'shipfees',
				'discount' => 'discounts'
			);

			$total = 0;
			foreach ( $totals as $property => $fields ) {
				if ( empty($fields) ) continue;

				if ( count($fields) > 1 ) {
					if ( isset($fields['labels']) ) {
						$labels = $fields['labels'];
						unset($fields['labels']);
						if ( count($fields) > count($labels) )
							$totalfield = array_pop($fields);

						$fields = array_combine($labels, $fields);
					}

					$fields = array_map(array('Shopp', 'floatval'), $fields);

					$entries = array();
					$OrderAmountObject = isset($objects[ $property ]) ? $objects[ $property ] : 'OrderAmountFee';
					foreach ( $fields as $label => $amount )
						$entries[] = new $OrderAmountObject(array('id' => count($entries) + 1, 'label' => $label, 'amount' => $amount));

					$savetotal = isset($methods[ $property ]) ? $methods[ $property ] : $fees;
					$Purchase->$savetotal($entries);

					$sum = array_sum($fields);
					if ( $sum > 0 )
						$Purchase->$property = $sum;

				} else $Purchase->$property = Shopp::floatval($fields[0]);

				$total += ('discount' == $property ? $Purchase->$property * -1 : $Purchase->$property );

			}

			$Purchase->total = $Purchase->subtotal + $total;
			$Purchase->save();
		}

		if ( ! empty($_GET['rmvline']) ) {
			$lineid = (int)$_GET['rmvline'];
			if ( isset($Purchase->purchased[ $lineid ]) ) {
				$Purchase->purchased[ $lineid ]->delete();
				unset($Purchase->purchased[ $lineid ]);
			}

			$Cart = new ShoppCart();

			$taxcountry = $Purchase->country;
			$taxstate = $Purchase->state;
			if ( ! empty($Purchase->shipcountry) && ! empty($Purchase->shipstate) ) {
				$taxcountry = $Purchase->shipcountry;
				$taxstate = $Purchase->shipstate;
			}
			ShoppOrder()->Tax->location($taxcountry, $taxstate);

			foreach ( $Purchase->purchased as &$Purchased )
				$Cart->additem($Purchased->quantity, new ShoppCartItem($Purchased));

			$Cart->Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Purchase->freight ) ) );

			$Purchase->total = $Cart->total();
			$Purchase->subtotal = $Cart->total('order');
			$Purchase->discount = $Cart->total('discount');
			$Purchase->tax = $Cart->total('tax');
			$Purchase->freight = $Cart->total('shipping');
			$Purchase->save();

			$Purchase->load_purchased();
		}


		if (isset($_POST['charge']) && $Gateway && $Gateway->captures) {
			if ( ! current_user_can('shopp_capture') )
				wp_die(__('You do not have sufficient permissions to carry out this action.','Shopp'));

			$user = wp_get_current_user();

			shopp_add_order_event($Purchase->id,'capture',array(
				'txnid' => $Purchase->txnid,
				'gateway' => $Purchase->gateway,
				'amount' => $Purchase->capturable(),
				'user' => $user->ID
			));

			$Purchase->load_events();
		}

		$targets = shopp_setting('target_markets');
		$default = array('' => '&nbsp;');
		$Purchase->_countries = array_merge($default, ShoppLookup::countries());

		$regions = Lookup::country_zones();
		$Purchase->_billing_states = array_merge($default, (array)$regions[ $Purchase->country ]);
		$Purchase->_shipping_states = array_merge($default, (array)$regions[ $Purchase->shipcountry ]);

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

			$serviceareas = array('*', ShoppBaseLocale()->country());
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

		$Purchase->taxes();
		$Purchase->discounts();

		$columns = get_column_headers($this->id);
		$hidden = get_hidden_columns($this->id);

		include $this->ui('new.php');
	}

} // class ShoppScreenOrderEditor

class ShoppAdminOrderNotesBox extends ShoppAdminMetabox {

	protected $id = 'order-notes';
	protected $view = 'orders/notes.php';

	protected function title() {
		return Shopp::__('Notes');
	}

	protected function init() {

		add_filter('shopp_order_note', 'esc_html');
		add_filter('shopp_order_note', 'wptexturize');
		add_filter('shopp_order_note', 'convert_chars');
		add_filter('shopp_order_note', 'make_clickable');
		add_filter('shopp_order_note', 'force_balance_tags');
		add_filter('shopp_order_note', 'convert_smilies');
		add_filter('shopp_order_note', 'wpautop');

		extract($this->references);
		$this->references['Notes'] = new ObjectMeta($Purchase->id, 'purchase', 'order_note');

	}

	protected function ops() {
		return array(
			'add',
			'edit',
			'delete',
			'send'
		);
	}

	public function add() {
		if ( ! $this->form('note') ) return;
		extract($this->references); // Extracts $Purchase

		$user = wp_get_current_user();
		$Note = new ShoppMetaObject();
		$Note->parent = $Purchase->id;
		$Note->context = 'purchase';
		$Note->type = 'order_note';
		$Note->name = 'note';
		$Note->value = new stdClass();
		$Note->value->author = $user->ID;
		$Note->value->message = stripslashes($this->form('note'));
		$Note->value->sent = ( 1 == $this->form('send-note') );

		$Note->save();

		if ( ! $Note->value->sent )
			$this->notice(Shopp::__('Added note.'));

	}

	public function delete() {
		if ( ! $this->form('delete-note') ) return;

		$id = key($this->form('delete-note'));

		$Note = new ShoppMetaObject(array('id' => $id, 'type' => 'order_note'));
		if ( ! $Note->exists() ) return;

		$Note->delete();

		$this->notice(Shopp::__('Note deleted.'));
	}

	public function edit() {
		if ( ! $this->form('edit-note') ) return;
		$edited = $this->form('note-editor');

		$id = key($edited);
		if ( empty($edited[ $id ]) ) return;

		$Note = new ShoppMetaObject(array('id' => $id, 'type' => 'order_note'));
		if ( ! $Note->exists() ) return;

		$Note->value->message = stripslashes($edited[ $id ]);
		$Note->save();

		$this->notice(Shopp::__('Note updated.'));
	}

	public function send() {

		if ( ! $this->form('send-note') ) return;

		extract($this->references); // Extracts $Purchase
		$user = wp_get_current_user();

		$sent = shopp_add_order_event($Purchase->id, 'note', array(
			'note' => $this->form('note'),
			'user' => $user->ID
		));

		$Purchase->load_events();

		$this->notice(Shopp::__('Note sent to <strong>%s</strong>.', $Purchase->email));

	}

} // end class ShoppAdminOrderNotesBox


class ShoppAdminOrderHistoryBox extends ShoppAdminMetabox {

	protected $id = 'order-history';
	protected $view = 'orders/history.php';

	protected function title () {
		return Shopp::__('Order History');
	}

} // class ShoppAdminOrderHistoryBox

class ShoppAdminOrderDataBox extends ShoppAdminMetabox {

	protected $id = 'order-data';
	protected $view = 'orders/data.php';

	protected function title () {
		return Shopp::__('Details');
	}

	public static function name ( $name ) {
		echo esc_html($name);
	}

	public static function data ( $name, $data ) {

		if ( $type = Shopp::is_image($data) ) {
			$src = "data:$type;base64," . base64_encode($data);
			$result = '<a href="' . $src . '" class="shopp-zoom"><img src="' . $src . '" /></a>';
		} elseif ( is_string($data) && false !== strpos(data, "\n") ) {
			$result = '<textarea name="orderdata[' . esc_attr($name) . ']" readonly="readonly" cols="30" rows="4">' . esc_html($data) . '</textarea>';
		} else {
			$result = esc_html($data);
		}

		echo $result;

	}

} // class ShoppAdminOrderDataBox

class ShoppAdminOrderContactBox extends ShoppAdminMetabox {

	protected $id = 'order-contact';
	protected $view = 'orders/contact.php';

	protected function title() {
		return Shopp::__('Customer');
	}

	protected function ops() {
		return array(
			'updates',
			'reassign',
			'add',
			'unedit'
		);
	}

	public function updates() {

		if ( 'update-customer' != $this->form('order-action') ) return;
		if ( ! $updates = $this->form('customer') ) return;
		if ( ! is_array($updates) ) return;

		extract($this->references, EXTR_SKIP);
		$Purchase->updates($updates);
		$Purchase->save();
	}


	public function reassign() {
		if ( 'change-customer' != $this->form('order-action') ) return;

		$Customer = new ShoppCustomer((int)$this->request('customerid'));
		if ( ! $Customer->exists() )
			return $this->notice(Shopp::__('The selected customer was not found.'), 'error');

		extract($this->references, EXTR_SKIP);

		$Purchase->copydata($Customer);
		$Purchase->customer = $Customer->id;
		$Purchase->save();
	}

	public function add() {
		if ( 'new-customer' != $this->form('order-action') ) return;
		if ( ! $updates = $this->form('customer') ) return;
		if ( ! is_array($updates) ) return;

		extract($this->references, EXTR_SKIP);

		// Create the new customer record
		$Customer = new ShoppCustomer();
		$Customer->updates($updates);
		$Customer->password = wp_generate_password(12, true);

		if ( 'wordpress' == shopp_setting('account_system') )
			$Customer->create_wpuser();
		else unset($this->form['loginname']);

		$Customer->save();

		if ( ! $Customer->exists() )
			return $this->notice(Shopp::__('An unknown error occured. The customer could not be created.'), 'error');

		$Purchase->customer = $Customer->id;
		$Purchase->copydata($Customer);
		$Purchase->save();

		// Create a new billing address record for the new customer
		if ( $billing = $this->form('billing') && is_array($billing) && empty($billing['id']) ) {
			$Billing = new BillingAddress($billing);
			$Billing->customer = $Customer->id;
			$Billing->save();
		}

		// Create a new shipping address record for the new customer
		if ( $shipping = $this->form('shipping') && is_array($shipping) && empty($shipping['id']) ) {
			$Shipping = new ShippingAddress($shipping);
			$Shipping->customer = $Customer->id;
			$Shipping->save();
		}

	}

	public function unedit() {
		if ( ! $this->form('cancel-edit-customer') ) return;
		unset($this->form['order-action'], $this->form['edit-customer'], $this->form['select-customer']);
	}


} // class ShoppAdminOrderContactBox

class ShoppAdminOrderBillingAddressBox extends ShoppAdminMetabox {

	protected $id = 'order-billing';
	protected $view = 'orders/billing.php';
	protected $type = 'billing';
	protected $Purchase = false;

	protected function title() {
		return Shopp::__('Billing Address');
	}

	protected function references() {
		$this->references['targets'] = shopp_setting('target_markets');
	}

	protected function ops () {
		return array('updates');
	}

	public function updates () {
		if ( ! $billing = $this->form('billing') ) return;
		if ( ! is_array($billing) ) return;

		extract($this->references, EXTR_SKIP);

		$Purchase->updates($billing);
		$Purchase->save();

		$this->notice(Shopp::__('Updated billing address.'));
	}

	public function purchase ( ShoppPurchase $Purchase = null ) {
		if ( isset($Purchase) )
			$this->Purchase = $Purchase;
	}

	public function editor() {
		$type = $this->type;
		$Purchase = $this->Purchase;

		ob_start();
		include $this->ui('orders/address.php');
		return ob_get_clean();
	}

	public function editing() {
		return isset($_POST['edit-' . $this->type . '-address']) || ! $this->has_address();
	}

	public function has_address() {
		$Purchase = $this->Purchase;
		$address = $Purchase->address . $Purchase->xaddress;
		return ! ( empty($address)
				|| empty($Purchase->city)
				|| empty($Purchase->postcode)
				|| empty($Purchase->country)
		);
	}

	public function data() {
		$Purchase = $this->Purchase;

		if ( empty($Purchase->_billing_states) && ! empty($Purchase->state) )
			$statemenu = array($Purchase->state => $Purchase->state);
		else Shopp::menuoptions($Purchase->_billing_states, $Purchase->state, true);

		return array(
			'${action}' => 'update-address',
			'${type}' => 'billing',
			'${firstname}' => $Purchase->firstname,
			'${lastname}' => $Purchase->lastname,
			'${address}' => $Purchase->address,
			'${xaddress}' => $Purchase->xaddress,
			'${city}' => $Purchase->city,
			'${state}' => $Purchase->state,
			'${postcode}' => $Purchase->postcode,
			'${country}' => $Purchase->country,
			'${statemenu}' => $statemenu,
			'${countrymenu}' => Shopp::menuoptions($Purchase->_countries, $Purchase->country, true)
		);
	}

	public function json( array $data = array() ) {
		$data = preg_replace('/\${([-\w]+)}/', '$1', json_encode($data));
		shopp_custom_script('orders', 'address["' . $this->type . '"] = ' . $data . ';');
	}

} // class ShoppAdminOrderBillingAddressBox

class ShoppAdminOrderShippingAddressBox extends ShoppAdminOrderBillingAddressBox {

	protected $id = 'order-shipping';
	protected $view = 'orders/shipping.php';
	protected $type = 'shipping';

	protected $Purchase = false;

	protected function title () {
		return Shopp::__('Shipping Address');
	}

	public function updates () {

		if ( ! $shipping = $this->form('shipping') ) return;
		if ( ! is_array($shipping) ) return;

		extract($this->references, EXTR_SKIP);

		$updates = array();
		foreach ( $shipping as $name => $value )
			$updates[ "ship$name" ] = $value;

		$Purchase->updates($updates);
		$Purchase->shipname = $updates['shipfirstname'] . ' ' . $updates['shiplastname'];
		$Purchase->save();

		$this->notice(Shopp::__('Shipping address updated.'));

	}

	public function has_address() {
		$Purchase = $this->Purchase;
		$address = $Purchase->shipaddress . $Purchase->shipxaddress;
		return ! ( empty($address)
								|| empty($Purchase->shipcity)
								|| empty($Purchase->shippostcode)
								|| empty($Purchase->shipcountry)
		);
	}

	public function data() {
		$Purchase = $this->Purchase;
		$names = explode(' ', $Purchase->shipname);

		$firstname = array_shift($names);
		$lastname = join(' ', $names);

		if ( empty($Purchase->_shipping_states) && ! empty($Purchase->shipstate) )
			$statemenu = array($Purchase->shipstate => $Purchase->shipstate);
		else Shopp::menuoptions($Purchase->_shipping_states, $Purchase->shipstate, true);

		return array(
			'${type}' => 'shipping',
			'${firstname}' => $firstname,
			'${lastname}' => $lastname,
			'${address}' => $Purchase->shipaddress,
			'${xaddress}' => $Purchase->shipxaddress,
			'${city}' => $Purchase->shipcity,
			'${state}' => $Purchase->shipstate,
			'${postcode}' => $Purchase->shippostcode,
			'${country}' => $Purchase->shipcountry,
			'${statemenu}' => $statemenu,
			'${countrymenu}' => Shopp::menuoptions($Purchase->_countries, $Purchase->shipcountry, true)
		);
	}


} // class ShoppAdminOrderShippingAddressBox


class ShoppAdminOrderManageBox extends ShoppAdminMetabox {

	protected $id = 'order-manage';
	protected $view = 'orders/manage.php';

	protected function title () {
		return Shopp::__('Management');
	}

	public function references() {
		$Purchase = $this->references['Purchase'];
		$Gateway = $Purchase->gateway();

		$this->references['gateway_name'] = $Gateway ? $Gateway->name : '';
		$this->references['gateway_refunds'] = $Gateway ? $Gateway->refunds : false;
		$this->references['gateway_captures'] = $Gateway ? $Gateway->captures : false;

		$carriers = $this->Screen->shipcarriers();
		$menu = array();
		foreach ( $carriers as $id => $entry )
			$menu[ $id ] = $entry[0];

		$this->references['carriers_menu'] = $menu;
	}

	protected function init() {
		extract($this->references);
		$Purchase->load_events();
	}

	protected function ops() {
		return array(
			'shipnotice',
			'refund',
			'cancel',
			'charge'
		);
	}

	public function shipnotice () {
		extract($this->references);
		if ( ! $shipments = $this->form('shipment') ) return;

		foreach ( (array) $shipments as $shipment ) {
			shopp_add_order_event($Purchase->id, 'shipped', array(
				'tracking' => $shipment['tracking'],
				'carrier' => $shipment['carrier']
			));
		}

		$this->notice(Shopp::__('Shipping notice sent.'));

		// Save shipping carrier default preference for the user
		$userid = get_current_user_id();
		$setting = 'shopp_shipping_carrier';
		if ( ! get_user_meta($userid, $setting, true) )
			add_user_meta($userid, $setting, $shipment['carrier']);
		else update_user_meta($userid, $setting, $shipment['carrier']);

	}

	public function refund() {
		if ( 'refund' != $this->form('order-action') ) return;

		if ( ! current_user_can('shopp_refund') )
			wp_die(Shopp::__('You do not have sufficient permissions to carry out this action.'));

		extract($this->references);
		$user = wp_get_current_user();
		$reason = (int)$_POST['reason'];
		$amount = Shopp::floatval($_POST['amount']);

		if ( $this->form('message') )
			$Purchase->message['note'] = $this->form('message');

		if ( Shopp::str_true($this->form('send')) ) {

			// Submit the refund request to the payment gateway
			shopp_add_order_event($Purchase->id, 'refund', array(
				'txnid'   => $Purchase->txnid,
				'gateway' => $Gateway->module,
				'amount'  => $amount,
				'reason'  => $reason,
				'user'    => $user->ID
			));

		} else {

			// Force the order status to be refunded (without talking to the gateway)

			// Email a refund notice to the customer
			shopp_add_order_event($Purchase->id, 'notice', array(
				'user'   => $user->ID,
				'kind'   => 'refunded',
				'notice' => Shopp::__('Marked Refunded')
			));

			// Log the refund event
			shopp_add_order_event($Purchase->id, 'refunded', array(
				'txnid'   => $Purchase->txnid,
				'amount'  => $amount,
				'gateway' => $Gateway->module

			));

			// Cancel the order
			shopp_add_order_event($Purchase->id, 'voided', array(
				'gateway'   => $Gateway->module,
				'txnorigin' => $Purchase->txnid,
				'txnid'     => current_time('timestamp')
			));

			$this->notice(Shopp::__('Order marked refunded.'));

		}

	}

	public function cancel () {
		if ( 'cancel' != $this->form('order-action') ) return;

		if ( ! current_user_can('shopp_void') )
			wp_die(Shopp::__('You do not have sufficient permissions to carry out this action.'));

		extract($this->references);

		// unset($_POST['refund-order']);
		$user = wp_get_current_user();
		$reason = (int)$_POST['reason'];

		$message = '';
		if ( $message = $this->form('message') )
			$Purchase->message['note'] = $message;

		if ( Shopp::str_true($this->form('send')) ) {

			// Submit the void request to the payment gateway
			shopp_add_order_event($Purchase->id, 'void', array(
				'gateway' => $Gateway->module,
				'txnid'   => $Purchase->txnid,
				'reason'  => $reason,
				'user'    => $user->ID,
				'note'    => $message
			));

		} else {

			// Force the order status to be cancelled (without talking to the gateway)

			// Email a notice to the customer
			shopp_add_order_event($Purchase->id, 'notice', array(
				'user'   => $user->ID,
				'kind'   => 'cancelled',
				'notice' => Shopp::__('Marked Cancelled')
			));

			// Cancel the order
			shopp_add_order_event($Purchase->id, 'voided', array(
				'gateway' => $Gateway->module,
				'txnorigin' => $Purchase->txnid,
				'txnid' => current_time('timestamp'),
			));

		}
	}

	public function charge() {
		if ( ! $this->form('charge') ) return;

		extract($this->references);
		if ( ! $gateway_captures ) return;

		if ( ! current_user_can('shopp_capture') )
			wp_die(Shopp::__('You do not have sufficient permissions to carry out this action.'));

		$user = wp_get_current_user();

		shopp_add_order_event($Purchase->id, 'capture', array(
			'txnid'   => $Purchase->txnid,
			'gateway' => $Purchase->gateway,
			'amount'  => $Purchase->capturable(),
			'user'    => $user->ID
		));
	}

} // class ShoppAdminOrderManageBox

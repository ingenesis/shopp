<?php
/**
 * ScreenOrdersTable.php
 *
 * Renders the order table for the orders manager
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrdersTable extends ShoppAdminTable {

	/** @var private $gateways List of gateway modules */
	private $gateways = array();

	/** @var private $statuses List of order status labels */
	private $statuses = array();

	/** @var private $txnstatus List of transaction status labels */
	private $txnstatuses = array();

	/**
	 * Load the order items for the table
	 *
	 * @since 1.5
	 *
	 * @return void
	 **/
	public function prepare_items() {

		$ItemsQuery = new ShoppScreenOrdersTableQuery();

		if ( $this->request('start') || $this->request('end') )
			$ItemsQuery->daterange($this->request('start'), $this->request('end'));

		if ( $this->request('status') )
			$ItemsQuery->status($this->request('status'));

		if ( $this->request('s') )
			$ItemsQuery->search($this->request('s'));

		if ( $this->request('customer') )
			$ItemsQuery->customer($this->request('customer'));

		$ItemsQuery->page($this->request('paged'));

		$this->ordercount = $ItemsQuery->count();
		$this->items = $ItemsQuery->items();

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

		$perpage = $ItemsQuery->perpage();

		$this->set_pagination_args( array(
			'total_items' => $this->ordercount->total,
			'total_pages' => $this->ordercount->total / $perpage,
			'per_page' => $perpage
		) );
	}

	/**
	 * Setup the bulk actions menu
	 *
	 * @since 1.5
	 * @param string $which Specify which bulk action menu to change ('top', 'bottom')
	 * @return array The list of actions
	 **/
	protected function get_bulk_actions( $which ) {
		if ( 'bottom' == $which ) return;
		$actions = array(
			'delete' => __( 'Delete' ),
		);
		$statuses = shopp_setting('order_status');

		return $actions + $statuses;
	}

	/**
	 * Show extra controls in the top or bottom table navigation
	 *
	 * @since 1.5
	 * @param string $which Specify which bulk action menu to change ('top', 'bottom')
	 * @return array The list of actions
	 **/
	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) $this->bottom_tablenav();
		if ( 'top' == $which )	$this->top_tablenav();
	}

    /**
     * Render the bottom table navigation
     *
     * @since 1.5
     *
     * @return void
     **/
	protected function bottom_tablenav() {
		if ( ! current_user_can('shopp_financials') || ! current_user_can('shopp_export_orders') ) return;

		$exporturl = add_query_arg(urlencode_deep(array_merge(stripslashes_deep($_GET), array('src' => 'export_purchases'))));

		echo  '<div class="alignleft actions">'
			. '	</form><form action="' . esc_url($exporturl) . '" id="log" method="post">'
			. '		<button type="button" id="export-settings-button" name="export-settings" class="button-secondary">' . Shopp::__('Export Options') . '</button>'

			. '	<div id="export-settings" class="export-settings hidden">'
			. '		<div id="export-columns" class="multiple-select">'
			. '			<ul>';

		echo '				<li><input type="checkbox" name="selectall_columns" id="selectall_columns" /><label for="selectall_columns"><strong>' . Shopp::__('Select All') . '</strong></label></li>';


		echo '				<li><input type="hidden" name="settings[purchaselog_headers]" value="off" /><input type="checkbox" name="settings[purchaselog_headers]" id="purchaselog_headers" value="on" /><label for="purchaselog_headers"><strong>' . Shopp::__('Include column headings') . '</strong></label></li>';

		$exportcolumns = array_merge(ShoppPurchase::exportcolumns(), ShoppPurchased::exportcolumns());
		$selected = shopp_setting('purchaselog_columns');
		if ( empty($selected) ) $selected = array_keys($exportcolumns);

		foreach ( $exportcolumns as $name => $label ) {
			if ( 'cb' == $name ) continue;
			echo '				<li><input type="checkbox" name="settings[purchaselog_columns][]" value="' . esc_attr($name) . '" id="column-' . esc_attr($name) . '" ' . ( in_array($name, $selected) ? ' checked="checked"' : '' ) . ' /><label for="column-' . esc_attr($name) . '">' . esc_html($label) . '</label></li>';
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

    /**
     * Render the top table navigation
     *
     * @since 1.5
     *
     * @return void
     **/
	protected function top_tablenav() {
		$range = $this->request('range') ? $this->request('range') : 'all';
		$ranges = ShoppAdminLookup::daterange_options();
		$ranges['all'] = Shopp::__('Show All Orders');

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

    /**
     * Specify the table columns
     *
     * @since 1.5
     *
     * @return void
     **/
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

    /**
     * Render text when no orders are available
     *
     * @since 1.5
     *
     * @return void
     **/
	public function no_items() {
		Shopp::_e('No orders, yet.');
	}

    /**
     * Render an empty column by default
     *
     * This method is used to render the column by default when no
     * specific renderer method matches the column name.
     *
     * @since 1.5
     *
     * @return string Empty string
     **/
	public function column_default() {
		return '';
	}

    /**
     * Render the order checkbox to select the order row for bulk actions
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The selection checkbox
     **/
	public function column_cb( $Item ) {
		return '<input type="checkbox" name="selected[]" value="' . $Item->id . '" />';
	}

    /**
     * Render the Order column description and convenience management controls
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The order column description and management controls
     **/
	public function column_order( $Item ) {
		$url = add_query_arg('id', $Item->id);
		return '<a class="row-title" href="' . esc_url($url) . '" title="' . Shopp::__('View Order #%d', $Item->id) . '">' . Shopp::__('Order #%d', $Item->id) . '</a>';
	}

    /**
     * Render the customer name for the order
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The customer name
     **/
	public function column_name( $Item ) {
		if ( '' == trim($Item->firstname . $Item->lastname) )
			$customer = '(' . Shopp::__('no contact name') . ')';
		else $customer = ucfirst($Item->firstname . ' ' . $Item->lastname);

		$url = add_query_arg( array( 'page' => 'shopp-customers', 'id' => $Item->customer ) );

		return '<a href="' . esc_url($url) . '">' . esc_html($customer) . '</a>';
		if ( '' != trim($Item->company) )
			return "<br />" . esc_html($Item->company);
	}

    /**
     * Render the order destination column
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The destination column
     **/
	public function column_destination( $Item ) {
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

    /**
     * Render the order transaction column
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The transaction column
     **/
	public function column_txn( $Item ) {
		return $Item->txnid;

		if ( isset($this->gateways[ $Item->gateway ]) )
			echo '<br />' . esc_html($this->gateways[ $Item->gateway ]->name);
	}

    /**
     * Render the order date column
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The formatted date column
     **/
	public function column_date( $Item ) {
		return date($this->dates, mktimestamp($Item->created));

		if ( isset($this->statuses[ $Item->status ]) )
			return '<br /><strong>' . esc_html($this->statuses[ $Item->status ]) . '</strong>';
	}

    /**
     * Render the order total column
     *
     * @since 1.5
     * @param ShoppPurchase $Item The order Item object
     * @return string The total column with the transaction status
     **/
	public function column_total( $Item ) {
		return money($Item->total);

		$status = $Item->txnstatus;
		if ( isset($this->txnstatuses[ $Item->txnstatus ]) )
			$status = $this->txnstatuses[ $Item->txnstatus ];

		return '<br /><span class="status">' . esc_html($status) . '</span>';
	}

}
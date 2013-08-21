<?php
/**
 * Dashboard.php
 *
 * Admin Dashboard controller
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, June 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.3
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

// @todo Move widgets to self contained classes and add a registration process to ShoppAdminDashboard
class ShoppAdminDashboard {

	/**
	 * Initializes the Shopp dashboard widgets
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function init () {

		$dashboard = shopp_setting('dashboard');

		if ( ! ( current_user_can('shopp_financials') && Shopp::str_true($dashboard) ) ) return false;

		wp_add_dashboard_widget('dashboard_shopp_stats', __('Sales Stats','Shopp'), array('ShoppAdminDashboard','stats_widget'),
			array('all_link' => '','feed_link' => '','width' => 'half','height' => 'single')
		);

		wp_add_dashboard_widget('dashboard_shopp_orders', __('Recent Orders','Shopp'), array('ShoppAdminDashboard', 'orders_widget'),
			array('all_link' => 'admin.php?page=' . ShoppAdmin()->pagename('orders'),'feed_link' => '','width' => 'half','height' => 'single')
		);

		if (shopp_setting_enabled('inventory')) {
			wp_add_dashboard_widget('dashboard_shopp_inventory', __('Low Inventory Monitor','Shopp'), array('ShoppAdminDashboard', 'inventory_widget'),
				array('all_link' => 'admin.php?page=' . ShoppAdmin()->pagename('products'),'feed_link' => '','width' => 'half','height' => 'single')
			);
		}

		add_action('admin_print_styles-index.php', array('ShoppAdminDashboard', 'styles'));

	}

	/**
	 * Loads the Shopp admin CSS on the WordPress dashboard for widget styles
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function styles () {
		wp_enqueue_style('shopp.dashboard', SHOPP_ADMIN_URI . '/styles/dashboard.css', array(), Shopp::VERSION, 'screen');
	}

	/**
	 * Dashboard Widgets
	 */
	/**
	 * Renders the order stats widget
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function stats_widget ( $args = false ) {
		$Shopp = Shopp::object();

		$ranges = array(
			'today' => __('Today','Shopp'),
			'week' => __('This Week','Shopp'),
			'month' => __('This Month','Shopp'),
			'quarter' => __('This Quarter','Shopp'),
			'year' => __('This Year','Shopp'),
			'yesterday' => __('Yesterday','Shopp'),
			'lastweek' => __('Last Week','Shopp'),
			'last30' => __('Last 30 Days','Shopp'),
			'last90' => __('Last 3 Months','Shopp'),
			'lastmonth' => __('Last Month','Shopp'),
			'lastquarter' => __('Last Quarter','Shopp'),
			'lastyear' => __('Last Year','Shopp'),
		);

		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => '',
			'range' => isset($_GET['shopp-stats-range']) ? $_GET['shopp-stats-range'] : ''
		);
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );

		if ( ! $range || !isset($ranges[ strtolower($range) ]) ) $range = 'last30';
		$purchasetable = DatabaseObject::tablename(Purchase::$table);

		$now = current_time('timestamp');
		$offset = get_option( 'gmt_offset' ) * 3600;
		$daytimes = 86400;
		$day = date('j',$now);
		$month = date('n',$now);
		$year = date('Y',$now);
		$end = $now;

		list($weekstart,$weekend) = array_values(get_weekstartend(current_time('mysql')));
		switch ($range) {
			case 'today': $start = mktime(0,0,0,$month,$day,$year); break;
			case 'week': $start = $weekstart; $end = $weekend; break;
			case 'month': $start = mktime(0,0,0,$month,1,$year); break;
			case 'quarter': $start = mktime(0,0,0,$month-(3 - ($month % 3)),1,$year); break;
			case 'year': $start = mktime(0,0,0,1,1,$year); break;
			case 'yesterday': $start = mktime(0,0,0,$month,$day-1,$year); $end = mktime(23,59,59,$month,$day-1,$year); break;
			case 'lastweek': $start = $weekstart-(7*$daytimes); $end = $weekstart-1; break;
			case 'last7': $start = $now - (7 * $daytimes); break;
			case 'last30': $start = $now - (30 * $daytimes); break;
			case 'last90': $start = $now - (90 * $daytimes); break;
			case 'lastmonth': $start = mktime(0,0,0,$month-1,1,$year); $end = mktime(0,0,0,$month,0,$year); break;
			case 'lastquarter': $start = mktime(0,0,0,($month-(3 - ($month % 3)))-3,1,$year); $end = mktime(23,59,59,date('n',$start)+3,0,$year); break;
			case 'lastyear': $start = mktime(0,0,0,$month,1,$year-1); $end = mktime(23,59,59,1,0,$year); break;
		}

		// Include authorizations, captures and old 1.1 tranaction status CHARGED in sales data
		$salestatus = array("'authed'","'captured'","'CHARGED'");

		$txnstatus = "txnstatus IN (".join(',',$salestatus).")";
		$daterange = "created BETWEEN '".DB::mkdatetime($start)."' AND '".DB::mkdatetime($end)."'";

		$query = "SELECT count(id) AS orders,
						SUM(total) AS sales,
						AVG(total) AS average,
		 				SUM(IF($daterange,1,0)) AS wkorders,
						SUM(IF($daterange,total,0)) AS wksales,
						AVG(IF($daterange,total,null)) AS wkavg
 					FROM $purchasetable WHERE $txnstatus";

		$results = sDB::query($query);

		$RecentBestsellers = new BestsellerProducts(array('range' => array($start,$end),'show'=>5));
		$RecentBestsellers->load(array('pagination'=>false));
		$RecentBestsellers->maxsold = 0;
		foreach ($RecentBestsellers as $product) $RecentBestsellers->maxsold = max($RecentBestsellers->maxsold,$product->sold);

		$LifeBestsellers = new BestsellerProducts(array('show'=>5));
		$LifeBestsellers->load(array('pagination'=>false));
		$LifeBestsellers->maxsold = 0;
		foreach ($LifeBestsellers as $product) $LifeBestsellers->maxsold = max($LifeBestsellers->maxsold,$product->sold);

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		$orderscreen = add_query_arg('page',ShoppAdmin()->pagename('orders'),admin_url('admin.php'));
		$productscreen = add_query_arg(array('page'=>ShoppAdmin()->pagename('products')),admin_url('admin.php'));

		?>
		<div class="table"><table>
		<tr><th colspan="2"><form action="<?php echo admin_url('index.php'); ?>">
			<select name="shopp-stats-range" id="shopp-stats-range">
				<?php echo menuoptions($ranges,$range,true); ?>
			</select>
			<button type="submit" id="filter-button" name="filter" value="order" class="button-secondary hide-if-js"><?php _e('Filter','Shopp'); ?></button>
		</form>
		</th><th colspan="2"><?php _e('Lifetime','Shopp'); ?></th></tr>

		<tbody>
		<tr><td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo (int)$results->wkorders; ?></a></td><td class="label"><?php echo _n('Order', 'Orders', (int)$results->wkorders, 'Shopp'); ?></td>
		<td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo (int)$results->orders; ?></a></td><td class="label"><?php echo _n('Order', 'Orders', (int)$results->orders, 'Shopp'); ?></td></tr>

		<tr><td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->wksales); ?></a></td><td class="label"><?php _e('Sales','Shopp'); ?></td>
		<td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->sales); ?></a></td><td class="label"><?php _e('Sales','Shopp'); ?></td></tr>

		<tr><td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->wkavg); ?></a></td><td class="label"><?php _e('Average Order','Shopp'); ?></td>
		<td class="amount"><a href="<?php echo esc_url($orderscreen); ?>"><?php echo money($results->average); ?></a></td><td class="label"><?php _e('Average Order','Shopp'); ?></td></tr>

		<?php if (!empty($RecentBestsellers->products) || !empty($LifeBestsellers->products)): ?>
		<tr>
			<th colspan="2"><?php printf(__('Bestsellers %s','Shopp'),$ranges[$range]); ?></th>
			<th colspan="2"><?php printf(__('Lifetime Bestsellers','Shopp'),$ranges[$range]); ?></th>
		</tr>
		<?php
			reset($RecentBestsellers);
			reset($LifeBestsellers);
			$firstrun = true;
			while (true):
				list($recentid,$recent) = each($RecentBestsellers->products);
				list($lifetimeid,$lifetime) = each($LifeBestsellers->products);
				if (!$recent && !$lifetime) break;
			?>
			<tr>
				<?php if (empty($RecentBestsellers->products) && $firstrun) echo '<td colspan="2" rowspan="5">'.__('None','Shopp').'</td>'; ?>
				<?php if (!empty($recent->id)): ?>
				<td class="salesgraph">
					<div class="bar" style="width:<?php echo ($recent->sold/$RecentBestsellers->maxsold)*100; ?>%;"><?php echo $recent->sold; ?></div>
				</td>
				<td>
				<a href="<?php echo esc_url(add_query_arg('view','bestselling',$productscreen)); ?>"><?php echo esc_html($recent->name); ?></a>
				</td>
				<?php endif; ?>
				<?php if (empty($LifeBestsellers->products) && $firstrun) echo '<td colspan="2" rowspan="5">'.__('None','Shopp').'</td>'; ?>
				<?php if (!empty($lifetime->id)): ?>
				<td class="salesgraph">
					<div class="bar" style="width:<?php echo ($lifetime->sold/$LifeBestsellers->maxsold)*100; ?>%;"><?php echo $lifetime->sold; ?></div>
				</td>
				<td>
				<a href="<?php echo esc_url(add_query_arg('view','bestselling',$productscreen)); ?>"><?php echo esc_html($lifetime->name); ?></a>
				</td>
				<?php endif; ?>
			</tr>
		<?php $firstrun = false; endwhile; ?>
		<?php endif; ?>
		</tbody></table></div>
		<script type="text/javascript">
		jQuery(document).ready( function($) {
			$('#shopp-stats-range').change(function () {
				$(this).parents('form').submit();
			});
		});
		</script>
		<?php
		echo $after_widget;

	}

	/**
	 * Renders the recent orders dashboard widget
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function orders_widget ($args=null) {
		$Shopp = Shopp::object();
		$db = DB::get();
		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);
		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );
		$statusLabels = shopp_setting('order_status');

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		$purchasetable = DatabaseObject::tablename(Purchase::$table);
		$purchasedtable = DatabaseObject::tablename(Purchased::$table);

		$Orders = $db->query("SELECT p.*,count(i.id) as items FROM $purchasetable AS p LEFT JOIN $purchasedtable AS i ON i.purchase=p.id GROUP BY i.purchase ORDER BY created DESC LIMIT 6",'array');

		if (!empty($Orders)) {
		echo '<table class="widefat">';
		echo '<tr><th scope="col">'.__('Name','Shopp').'</th><th scope="col">'.__('Date','Shopp').'</th><th scope="col" class="num">'.__('Items','Shopp').'</th><th scope="col" class="num">'.__('Total','Shopp').'</th><th scope="col" class="num">'.__('Status','Shopp').'</th></tr>';
		echo '<tbody id="orders" class="list orders">';
		$even = false;
		foreach ($Orders as $Order) {
			echo '<tr'.((!$even)?' class="alternate"':'').'>';
			$even = !$even;
			echo '<td><a class="row-title" href="'.add_query_arg(array('page'=>ShoppAdmin()->pagename('orders'),'id'=>$Order->id),admin_url('admin.php')).'" title="View &quot;Order '.$Order->id.'&quot;">'.((empty($Order->firstname) && empty($Order->lastname))?'(no contact name)':$Order->firstname.' '.$Order->lastname).'</a></td>';
			echo '<td>'.date("Y/m/d",mktimestamp($Order->created)).'</td>';
			echo '<td class="num">'.$Order->items.'</td>';
			echo '<td class="num">'.money($Order->total).'</td>';
			echo '<td class="num">'.$statusLabels[$Order->status].'</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		} else {
			echo '<p>'.__('No orders, yet.','Shopp').'</p>';
		}

		echo $after_widget;

	}

	/**
	 * Renders the bestselling products dashboard widget
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function inventory_widget ($args=null) {

		$warnings = array(
			'none' => __('OK','Shopp'),
			'warning' => __('warning','Shopp'),
			'critical' => __('critical','Shopp'),
			'backorder' => __('backorder','Shopp')
		);

		$defaults = array(
			'before_widget' => '',
			'before_title' => '',
			'widget_name' => '',
			'after_title' => '',
			'after_widget' => ''
		);

		if (!$args) $args = array();
		$args = array_merge($defaults,$args);
		if (!empty($args)) extract( $args, EXTR_SKIP );

		$pt = DatabaseObject::tablename(Price::$table);
		$setting = ( shopp_setting('lowstock_level') );

		$where = array();

		$where[] = "pt.stock < pt.stocked AND pt.stock/pt.stocked < $setting";
		$where[] = "(pt.context='product' OR pt.context='variation') AND pt.type != 'N/A'";

		$loading = array(
			'columns' => "pt.id AS stockid,IF(pt.context='variation',CONCAT(p.post_title,': ',pt.label),p.post_title) AS post_title,pt.sku AS sku,pt.stock,pt.stocked",
			'joins' => array($pt => "LEFT JOIN $pt AS pt ON p.ID=pt.product"),
			'where' => $where,
			'groupby' => 'pt.id',
			'orderby' => '(pt.stock/pt.stocked) ASC',
			'published' => false,
			'pagination' => false,
			'limit' => 25
		);

		$Collection = new ProductCollection();
		$Collection->load($loading);

		$productscreen = add_query_arg(array('page'=>ShoppAdmin()->pagename('products')),admin_url('admin.php'));

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		?>
		<table><tbody>
		<?php foreach ($Collection->products as $product): $product->lowstock($product->stock,$product->stocked); ?>
		<tr>
			<td class="amount"><?php echo $product->stock; ?></td>
			<td><span class="stock lowstock <?php echo $product->lowstock; ?>"><?php echo $warnings[ $product->lowstock ]; ?></span></td>
			<td><a href="<?php echo esc_url(add_query_arg('id',$product->id,$productscreen)); ?>"><?php echo $product->name; ?></a></td>
			<td><a href="<?php echo esc_url(add_query_arg('view','inventory',$productscreen)); ?>"><?php echo $product->sku; ?></a></td>
		</tr>
		<?php endforeach; ?>
		</tbody></table>

		<?php
		echo $after_widget;

	}

}
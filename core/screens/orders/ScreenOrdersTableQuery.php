<?php
/**
 * ScreenOrdersTableQuery.php
 *
 * Helper class to generate and query for order items for the orders table controller
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenOrdersTableQuery {
	
	public $views = array();

	protected $view = 'all';

	protected $select = 'o.*';
	protected $where = array();
	protected $joins = array();
	protected $limit = false;
	protected $order = 'DESC';
	protected $orderby = 'o.created';
	protected $debug = false;
	
	/**
	 * Constructor
	 * 
	 * Define available views for this screen and set the initial view
	 * 
	 * @param string $view The initial view
	 **/
	public function __construct () {
		$this->select = "o.*";
		$this->table = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
	}
	
	/**
	 * Query for order entry items
	 *
	 * @since 1.5
	 * 
	 * @return array List of order items
	 **/
	public function items() {
		$where = ! empty($this->where) ? "WHERE " . join(' AND ', $this->where) : '';
		$joins = join(' ', $this->joins);
		$query = "SELECT $this->select FROM $this->table AS o $joins $where ORDER BY $this->orderby $this->order LIMIT $this->limit";
		if ( $this->debug ) echo $query;
		return sDB::query($query, 'array', 'index', 'id');
	}
  
	/**
	 * Query for the total number of orders, sales total and average sale amounts
	 * 
	 * Uses the current request query parameters to update the totals.
	 *
	 * @since 1.5
	 * 
	 * @return object An object structuring containing the total, sales and avg sale numbers
	 **/
	public function count() {
		$selects = array(
			"count(*) as total",
			"SUM(IF(txnstatus IN ('authed','captured'),total,NULL)) AS sales",
			"AVG(IF(txnstatus IN ('authed','captured'),total,NULL)) AS avgsale"
		);
		$columns = join(',', $selects);
		$where = ! empty($this->where) ? "WHERE " . join(' AND ', $this->where) : '';
		$joins = join(' ', $this->joins);
		
		$query = "SELECT $columns FROM $this->table AS o $joins $where ORDER BY $this->orderby $this->order LIMIT 1";
		if ( $this->debug ) echo $query;
		return sDB::query($query, 'object');
	}
	
	/**
	 * Set the status index for the query
	 *
	 * @since 1.5
	 * @param int $status The status index number
	 * @return void
	 **/
	public function status( $status = 0 ) {
		$status = absint($status);
		if ( ! empty($status) ) 
			$this->where[] = "status='" . sDB::escape($status) . "'";
	}
	
	/**
	 * Set the date range for the query
	 *
	 * @since 1.5
	 * @param string $startdate The start date request in a formatted string "MM/DD/YYYY"
	 * @param string $enddate The end date request in a formatted string "MM/DD/YYYY"
	 * @return void
	 **/
	public function daterange( $startdate = false, $enddate = false ) {
		if ( empty($startdate) )
			$startdate = '01/01/2000';
		
		list($month, $day, $year) = explode('/', $startdate);
		$starts = mktime(0, 0, 0, $month, $day, $year);

		if ( empty($enddate) )
			$ends = time();
		else {
			list($month, $day, $year) = explode('/', $enddate);
			$ends = mktime(23, 59, 59, $month, $day, $year);
		}
		
		$this->where[] = "created BETWEEN '" . sDB::mkdatetime($starts) . "' AND '" . sDB::mkdatetime($ends) . "'";
	}
	
	/**
	 * Set the search query
	 *
	 * @since 1.5
	 * @param string $query The search query string
	 * @return void
	 **/
	public function search( $query ) {
		$query = stripslashes(strtolower($query));
		
		$namequery = "CONCAT(firstname,' ',lastname) LIKE '%" . sDB::escape($query) . "%')";
		$defaultquery = "(id='$query' OR $namequery";
		
		// Search by query:keyword
		if ( preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/', $query, $parts, PREG_SET_ORDER) > 0 ) {
			$search = array();
			foreach ( $parts as $keywords )
				$search += $this->keysearch($keywords);
			$this->where[] = "(" . join(' OR ', $search) . ")";
		} elseif ( strpos($query, '@') !== false ) { // Search by email if @ is detected
			 $this->where[] = "email='" . sDB::escape($query) . "'";
		} else $this->where[] = $defaultquery; // Otherwise, use default query
	}
	
	/**
	 * Helper to search by specified keywords
	 *
	 * @since 1.5
	 * @param array	$keywords The query type and keywords
	 * @return void
	 **/
	protected function keysearch( array $keywords ) {
		$search = array();
		
		list(,$query, $quoted, $keyword) = $keywords;
		$keyword = sDB::escape( ! empty($quoted) ? $quoted : $keyword );
		
		$sql = array(
			'txn'	  => "txnid='$keyword'",
			'company'  => "company LIKE '%$keyword%'",
			'gateway'  => "gateway LIKE '%$keyword%'",
			'cardtype' => "cardtype LIKE '%$keyword%'",
			'address'  => "(address LIKE '%$keyword%' OR xaddress='%$keyword%')",
			'city'	 => "city LIKE '%$keyword%'",
			'state'	=> "state='$keyword'",
			'postcode' => "postcode='$keyword'",
			'country'  => "country='$keyword'",
			'discount' => "m.value LIKE '%$keyword%'",
			'product'  => "p.name LIKE '%$keyword%' OR p.optionlabel LIKE '%$keyword%' OR p.sku LIKE '%$keyword%'"
		);
		
		$sql['province'] = $sql['state'];
		$sql['zip'] = $sql['zipcode'] = $sql['postcode'];
		$sql['promo'] = $sql['discount'];
		
		if ( isset($sql[ $query ]) )
			$search[] = $sql[ $query ];
		
		if ( in_array($query, array('promo', 'discount')) ) {
			$meta_table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
			$this->joins[ $meta_table ] = "INNER JOIN $meta_table AS m ON m.parent = o.id AND context='purchase'";
		}
		
		if ( 'product' == $query ) {
			$purchased_table = ShoppDatabaseObject::tablename(ShoppPurchased::$table);
			$this->joins[ $purchased_table ] = "INNER JOIN $purchased_table AS p ON p.purchase = o.id";
		}

		return $search;
	}
	
	/**
	 * Set the customer query
	 *
	 * @since 1.5
	 * @param int $id The customer id number
	 * @return void
	 **/
	public function customer( $id ) {
		$this->where[] = "customer=" . intval($id);
	}
	
	/**
	 * Set the current page limit query parameter
	 *
	 * @since 1.5
	 * @param int $page The page number to set
	 * @return void
	 **/
	public function page ( $page = 1 ) {
		if ( ! $page ) $page = 1;

		$page = absint($page);

		$perpage = $this->perpage();

		$start = $perpage * ( $page - 1 );
		$this->limit = "$start,$perpage";
	}
	
	/**
	 * Determine the per page option to use for this screen
	 *
	 * @since 1.5
	 * 
	 * @return int The number of entries to show per page
	 **/
	public function perpage () {
		$per_page_option = get_current_screen()->get_option( 'per_page' );
		$perpage = absint($per_page_option['default']);
		if ( false !== ( $user_perpage = get_user_option($per_page_option['option']) ) )
			$perpage = absint($user_perpage);
		return $perpage;
	}
	
}
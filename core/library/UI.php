<?php
/**
 * UI.php
 *
 * Shopp user interface library
 *
 * @copyright Ingenesis Limited, January 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Library/UI
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppAdminMetabox extends ShoppRequestFormFramework {

	/** @var type $var Description **/
	protected $defaults = array(
		'page' => '',
		'id' => false
	);

	/** @var type $var Description **/
	protected $references = array();

	/** @var type $var Description **/
	protected $id = '';

	/** @var type $var Description **/
	protected $view = '';

	/** @var type $var Description **/
	protected $title = '';

	/** @var ShoppScreen The URL for this admin screen */
	protected $Screen = false;

	public function __construct ( ShoppScreenController $Screen, $context, $priority, array $args = array() ) {
		Shopping::restore('admin_notices', $this->notices);

		$this->references = $args;
		$this->Screen = $Screen;

		$screenid = isset($args['posttype']) ? $args['posttype'] : $Screen->id;

		add_meta_box($this->id, $this->title() . self::help($this->id), array($this, 'box'), $screenid, $context, $priority, $args);

		// Parse query request
		if ( $this->query() ) {
			$this->actions();
			$this->handlers('actions', (array)$this->actions());
			do_action('shopp_metabox_' . $this->id . '_actions');
		}

		// Parse posted form
		if ( $this->posted() ) {
			$this->handlers('ops', (array)$this->ops());
			do_action('shopp_metabox_' . $this->id . '_ops');
		}

		$this->references();
		$this->init();

	}

	public function box () {
		extract($this->references);
		do_action('shopp_metabox_before_' . $this->id);
		include $this->ui();
		do_action('shopp_metabox_after_' . $this->id);
	}

	protected function title () {
		return 'Untitled';
	}

	protected function init () {
		/* Optionally implemented in concrete class */
	}

	protected function actions () {
		/* Optionally implemented in concrete class */
	}

	protected function ops () {
		/* Optionally implemented in concrete class */
	}

	protected function references () {
		/* Optionally implemented in concrete class */
	}

	protected function ui ( $view = null ) {
		if ( is_null($view) )
			$view = $this->view;
		$path = join('/', array(SHOPP_ADMIN_PATH, $view));
		if ( is_readable($path) )
			return $path;
	}

	public function url ( $params = array(), $resource = 'admin.php' ) {
		$request = ShoppRequestProcessing::process($_GET, $this->defaults);
		$defaults = array_intersect_key($request, $this->defaults);
		$params = array_merge($defaults, $params);
		return add_query_arg(array_map('esc_attr', $params), admin_url($resource));
	}

	public static function help ( $id ) {
		if ( ! ShoppSupport::activated() ) return '';

		$helpurl = add_query_arg(array('src' => 'help', 'id' => $id), admin_url('admin.php'));
		return apply_filters('shopp_admin_boxhelp', '<a href="' . esc_url($helpurl) . '" class="help shoppui-question"></a>');
	}

	/**
	 * Adds a notice to the screen
	 *
	 * @since 1.3
	 *
	 * @param string $message The message to add
	 * @param string $style `updated` (optional) The notice style class to use
	 * @param int $priority The priority (order) of the message
	 * @return void
	 **/
	protected function notice ( $message, $style = 'updated', $priority = 10 ) {
		$this->Screen->notice($message, $style, $priority);
	}

	private function handlers ( $action, array $methods = array() ) {
		if ( empty($methods) ) return;
		if ( ! in_array($action, array('actions', 'ops')) ) return;
		foreach ( $methods as $method ) {
			if ( is_callable(array($this, $method)) )
			add_action('shopp_metabox_' . $this->id . '_' . $action, array($this, $method));
		}
	}

} // end ShoppAdminMetaBox

class ShoppAdminMenusMetabox extends ShoppAdminMetabox {

	public function box () {
		global $_nav_menu_placeholder, $nav_menu_selected_id;

		$this->references['navmenu_placeholder'] = &$_nav_menu_placeholder;
		$this->references['navmenu_selected'] = &$nav_menu_selected_id;

		parent::box();
	}

	protected function selecturl () {
		return add_query_arg(array(
					'shopp-pages-menu-item' => 'all',
					'selectall' => 1,
			), remove_query_arg(array(
					'action',
					'customlink-tab',
					'edit-menu-item',
					'menu-item',
					'page-tab',
					'_wpnonce',
			))
		);
	}

} // end ShoppAdminMenusMetabox


class ShoppPagesMenusBox extends ShoppAdminMenusMetabox {

	protected $id = 'add-shopp-pages';
	protected $view = 'admin/pages.php';

	protected function title () {
		return Shopp::__('Catalog Pages');
	}

}

class ShoppCollectionsMenusBox extends ShoppAdminMenusMetabox {

	protected $id = 'add-shopp-collections';
	protected $view = 'admin/collections.php';

	protected function title () {
		return Shopp::__('Catalog Collections');
	}

	public function box () {

		$this->references['selecturl'] = $this->selecturl();
		$this->references['Shopp'] = Shopp::Object();

		parent::box();

	}

}

class ShoppUI {

	/**
	 * Container for metabox callback methods. Pattern: [ id => callback , ... ]
	 *
	 * @var array
	 */
	protected static $metaboxes = array();

	public static function cacheversion () {
		return hash('crc32b', ABSPATH . ShoppVersion::release());
	}

	public static function button ( $button, $name, array $options = array() ) {
		$buttons = array(
			'add' => array('class' => 'add', 'title' => Shopp::__('Add'), 'icon' => 'shoppui-plus', 'type' => 'submit'),
			'delete' => array('class' => 'delete', 'title' => Shopp::__('Delete'), 'icon' => 'shoppui-minus', 'type' => 'submit')
		);

		if ( isset($buttons[ $button ]) )
			$options = array_merge($buttons[ $button ], $options);

		$types = array('submit', 'button');
		if ( ! in_array($options['type'], $types) )
			$options['type'] = 'submit';

		$type = $options['type'];
		$title = $options['title'];
		$icon = $options['icon'];

		return '<button type="' . $type . '" name="' . $name . '"' . inputattrs($options) . '><span class="' . $icon . '"><span class="hidden">' . $title . '</span></span></button>';
	}

	public static function template ( $ui, array $data = array() ) {
		$ui = str_replace(array_keys($data), $data, $ui);
		return preg_replace('/\${[-\w]+}/', '', $ui);
	}

	/**
	 * Register column headers for a particular screen.
	 *
	 * Compatibility function for Shopp list table views
	 *
	 * @since 1.2
	 *
	 * @param string $screen The handle for the screen to add help to. This is usually the hook name returned by the add_*_page() functions.
	 * @param array $columns An array of columns with column IDs as the keys and translated column names as the values
	 * @see get_column_headers(), print_column_headers(), get_hidden_columns()
	 */
	public static function register_column_headers ( $screen, $columns ) {
		new ShoppAdminListTable($screen, $columns);
	}

	/**
	 * Prints column headers for a particular screen.
	 *
	 * @since 1.2
	 */
	public static function print_column_headers ( $screen, $id = true ) {
		$wp_list_table = new ShoppAdminListTable($screen);

		$wp_list_table->print_column_headers($id);
	}

	public static function table_set_pagination ( $screen, $total_items, $total_pages, $per_page ) {
		$wp_list_table = new ShoppAdminListTable($screen);

		$wp_list_table->set_pagination( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page
		) );

		return $wp_list_table;
	}

	/**
	 * Registers a new metabox for use within Shopp admin screens.
	 *
	 * @param string $id
	 * @param string $title
	 * @param $callback callable function
	 * @param string $posttype
	 * @param string $context [optional]
	 * @param string $priority [optional]
	 * @param array $args [optional]
	 */
	public static function addmetabox ( $id, $title, $callback, $posttype, $context = 'advanced', $priority = 'default', array $args = null ) {
		self::$metaboxes[$id] = $callback;
		$args = (array) $args;
		array_unshift($args, $id);
		add_meta_box($id, $title, array(__CLASS__, 'metabox'), $posttype, $context, $priority, $args);
	}

	/**
	 * Handles metabox callbacks - this allows additional output to be appended and prepended by devs using
	 * the shopp_metabox_before_{id} and shopp_metabox_after_{id} actions.
	 */
	public static function metabox($object, $args) {
		$id = array_shift($args['args']);
		$callback = isset(self::$metaboxes[$id]) ? self::$metaboxes[$id] : false;

		if (false === $callback) return;
		do_action('shopp_metabox_before_' . $id);
		call_user_func($callback, $object, $args);
		do_action('shopp_metabox_after_' . $id);
	}

} // END class ShoppUI


class ShoppAdminListTable extends WP_List_Table {

	public $screen;
	public $_screen;
	public $_columns;
	public $_sortable;

	public function __construct ( $screen, $columns = array()) {
		if ( is_string( $screen ) )
			$this->screen = convert_to_screen( $screen );

		$this->_screen = $this->screen;

		if ( !empty( $columns ) ) {
			$this->_columns = $columns;
			add_filter( 'manage_' . $this->screen->id . '_columns', array( $this, 'get_columns' ), 0 );
		}

	}

	public function get_column_info() {
		$columns = get_column_headers( $this->_screen );
		$hidden = get_hidden_columns( $this->_screen );
		$screen = get_current_screen();

		$_sortable = apply_filters( "manage_{$screen->id}_sortable_columns", $this->get_sortable_columns() );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}


		return array( $columns, $hidden, $sortable );
	}

	public function get_columns() {
		return $this->_columns;
	}

	public function get_sortable_columns () {
		$screen = get_current_screen();
		$sortables = array(
			'toplevel_page_shopp-products' => array(
				'name'=>'name',
				'price'=>'price',
				'sold'=>'sold',
				'gross'=>'gross',
				'inventory'=>'inventory',
				'sku'=>'sku',
				'date'=>array('date',true)
			)
		);
		if (isset($sortables[ $screen->id ])) return $sortables[ $screen->id ];

		return array();
	}

	// public wrapper to set pagination
	// @todo refactor this whole class to be used more effectively with Shopp MVC style UI
	public function set_pagination ( array $args ) {
		$this->set_pagination_args($args);
	}

}

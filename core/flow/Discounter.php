<?php
/**
 * Discounter.php
 *
 * Flow controller for discount management interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January, 2010
 * @package shopp
 * @subpackage discounts
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminDiscounter extends ShoppAdminController {

	protected $ui = 'discounts';

	/**
	 * Promote constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function __construct () {
		parent::__construct();

		$this->save();

		if ( ! empty($_GET['id']) ) {

			wp_enqueue_script('postbox');
			shopp_enqueue_script('colorbox');
			shopp_enqueue_script('calendar');
			shopp_enqueue_script('suggest');

			do_action('shopp_promo_editor_scripts');
			add_action('admin_head',array($this, 'layout'));

		} else add_action('admin_print_scripts', array($this, 'columns'));

		do_action('shopp_promo_admin_scripts'); // @deprecated
		do_action('shopp_admin_discount_scripts');

		$defaults = array(
			'page' => false,
			'action' => false,
			'selected' => array(),
		);
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);
		if (!is_array($selected)) $selected = array($selected);

		$url = add_query_arg(array_merge($_GET, array('page' => $this->page)), admin_url('admin.php'));
		$f = array('action', 'selected', 's');

		if ( $this->page == $page && ! empty($action) ) {
			switch ( $action ) {
				case 'enable': ShoppPromo::enableset($selected); break;
				case 'disable': ShoppPromo::disableset($selected); break;
				case 'delete': ShoppPromo::deleteset($selected); break;
				case 'duplicate': $P = new ShoppPromo($selected[0]); $P->duplicate(); break;
			}
			$url = remove_query_arg( $f, $url );

			wp_redirect($url);
			exit();
		}

	}

	/**
	 * Parses admin requests to determine which interface to render
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function admin () {
		if ( isset($_GET['id']) ) $this->editor();
		else $this->promotions();
	}

	/**
	 * Interface processor for the promotions list manager
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function promotions () {

		if ( ! current_user_can('shopp_promotions') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$table = ShoppDatabaseObject::tablename(ShoppPromo::$table);

		$defaults = array(
			'page' => false,
			'status' => false,
			'type' => false,
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			);

		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);

		$url = add_query_arg(array_merge($_GET, array('page'=>$this->page)), admin_url('admin.php'));
		$f = array('action','selected','s');
		$url = remove_query_arg($f, $url);

		$pagenum = absint( $paged );
		$start = ($per_page * ($pagenum-1));

		$where = array();
		if ( ! empty($s) ) $where[] = "name LIKE '%$s%'";
		if ( $status ) {
			$datesql = ShoppPromo::activedates();
			switch (strtolower($status)) {
				case 'active': $where[] = "status='enabled' AND $datesql"; break;
				case 'inactive': $where[] = "status='enabled' AND NOT $datesql"; break;
				case 'enabled': $where[] = "status='enabled'"; break;
				case 'disabled': $where[] = "status='disabled'"; break;
			}
		}
		if ( $type ) {
			switch (strtolower($type)) {
				case 'catalog': $where[] = "target='Catalog'"; break;
				case 'cart': $where[] = "target='Cart'"; break;
				case 'cartitem': $where[] = "target='Cart Item'"; break;
			}
		}

		$select = DB::select(array(
			'table' => $table,
			'columns' => 'SQL_CALC_FOUND_ROWS *',
			'where' => $where,
			'orderby' => 'created DESC',
			'limit' => "$start,$per_page"
		));

		$Promotions = DB::query($select,'array');
		$count = DB::found();

		$num_pages = ceil($count / $per_page);
		$ListTable = ShoppUI::table_set_pagination($this->screen, $count, $num_pages, $per_page );

		$states = array(
			'active' => __('Active','Shopp'),
			'inactive' => __('Not Active','Shopp'),
			'enabled' => __('Enabled','Shopp'),
			'disabled' => __('Disabled','Shopp')
		);

		$types = array(
			'catalog' => __('Catalog Discounts','Shopp'),
			'cart' => __('Cart Discounts','Shopp'),
			'cartitem' => __('Cart Item Discounts','Shopp')
		);

		$num_pages = ceil($count / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));

		include $this->ui('discounts.php');
	}


	protected function save () {
		if ( empty($_POST['save']) ) return;

		check_admin_referer('shopp-save-discount');

		if ( 'new' !== $_POST['id'] ) {
			$Promotion = new ShoppPromo($_POST['id']);
		} else $Promotion = new ShoppPromo();

		if ( ! empty($_POST['starts']['month']) && ! empty($_POST['starts']['date']) && ! empty($_POST['starts']['year']) )
			$_POST['starts'] = mktime(0, 0, 0, $_POST['starts']['month'], $_POST['starts']['date'], $_POST['starts']['year']);
		else $_POST['starts'] = 1;

		if ( ! empty($_POST['ends']['month']) && ! empty($_POST['ends']['date']) && ! empty($_POST['ends']['year']) )
			$_POST['ends'] = mktime(23, 59, 59, $_POST['ends']['month'], $_POST['ends']['date'], $_POST['ends']['year']);
		else $_POST['ends'] = 1;
		if ( isset($_POST['rules']) ) $_POST['rules'] = stripslashes_deep($_POST['rules']);

		$Promotion->updates($_POST);
		$Promotion->save();

		do_action_ref_array('shopp_promo_saved', array(&$Promotion));

		// $Promotion->reset_discounts();
		if ( 'Catalog' == $Promotion->target )
			$Promotion->catalog();

		// Set confirmation notice
		$this->notice(Shopp::__('Promotion has been updated!'));

		// Stay in the editor
		$url = add_query_arg('id', $Promotion->id, $this->url);
		wp_redirect($url);
		exit();
	}

	/**
	 * Registers the column headers for the promotions list interface
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function columns () {
		register_column_headers($this->screen, array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name','Shopp'),
			'discount' => __('Discount','Shopp'),
			'applied' => __('Type','Shopp'),
			'eff' => __('Status','Shopp'))
		);
	}

	/**
	 * Generates the layout for the promotion editor
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function layout () {
		$Shopp = Shopp::object();
		$Admin = $Shopp->Flow->Admin;

		include $this->ui('ui.php');
	}

	/**
	 * Interface processor for the promotion editor
	 * @author Jonathan Davis
	 * @return void
	 **/
	public function editor () {

		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_promotions') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ( 'new' !== $_GET['id'] ) {
			$Promotion = new ShoppPromo($_GET['id']);
			do_action('shopp_discount_promo_loaded', $Promotion);
		} else $Promotion = new ShoppPromo();

		$this->disabled_alert($Promotion);

		include $this->ui('editor.php');
	}

	/**
	 * Add a notice to make sure the merchant is aware that the promotion is not enabled (if that happens to be the
	 * case). If this is undesirable it can be turned off by adding some code to functions.php or another suitable
	 * location:
	 *
	 *  add_filter('shopp_hide_disabled_promo_warning', function() { return true; } ); // 5.3 style
	 */
	protected function disabled_alert ( ShoppPromo $Promotion ) {
		if ( 'enabled' === $Promotion->status || apply_filters('shopp_hide_disabled_promo_warning', false) ) return;
		$this->notice(Shopp::__('This promotion is not currently enabled.'), 'notice', 20);
	}

	public static function types () {
		$types = apply_filters('shopp_discount_types', array(
			'Percentage Off' => Shopp::__('Percentage Off'),
			'Amount Off' => Shopp::__('Amount Off'),
			'Free Shipping' => Shopp::__('Free Shipping'),
			'Buy X Get Y Free' => Shopp::__('Buy X Get Y Free')
		));
		return $types;
	}

	public static function scopes () {
		$scopes = apply_filters('shopp_discount_scopes', array(
			'Catalog' => Shopp::__('price'),
			'Cart' => Shopp::__('subtotal'),
			'Cart Item' => Shopp::__('unit price, where:')
		));
		echo json_encode($scopes);
	}

	public static function targets () {
		$targets = apply_filters('shopp_discount_targets', array(
			'Catalog' => Shopp::__('product'),
			'Cart' => Shopp::__('cart'),
			'Cart Item' => Shopp::__('cart')
		));
		echo json_encode($targets);
	}

	public static function rules () {
		$rules = apply_filters('shopp_discount_rules', array(
			'Name' => Shopp::__('Name'),
			'Category' => Shopp::__('Category'),
			'Variation' => Shopp::__('Variation'),
			'Price' => Shopp::__('Price'),
			'Sale price' => Shopp::__('Sale price'),
			'Type' => Shopp::__('Type'),
			'In stock' => Shopp::__('In stock'),

			'Tag name' => Shopp::__('Tag name'),
			'Unit price' => Shopp::__('Unit price'),
			'Total price' => Shopp::__('Total price'),
			'Input name' => Shopp::__('Input name'),
			'Input value' => Shopp::__('Input value'),
			'Quantity' => Shopp::__('Quantity'),

			'Any item name' => Shopp::__('Any item name'),
			'Any item amount' => Shopp::__('Any item amount'),
			'Any item quantity' => Shopp::__('Any item quantity'),
			'Total quantity' => Shopp::__('Total quantity'),
			'Shipping amount' => Shopp::__('Shipping amount'),
			'Subtotal amount' => Shopp::__('Subtotal amount'),
			'Discount amount' => Shopp::__('Discount amount'),

			'Customer type' => Shopp::__('Customer type'),
			'Ship-to country' => Shopp::__('Ship-to country'),

			'Promo code' => Shopp::__('Discount code'),
			'Promo use count' => Shopp::__('Discount use count'),

			'Is equal to' => Shopp::__('Is equal to'),
			'Is not equal to' => Shopp::__('Is not equal to'),
			'Contains' => Shopp::__('Contains'),
			'Does not contain' => Shopp::__('Does not contain'),
			'Begins with' => Shopp::__('Begins with'),
			'Ends with' => Shopp::__('Ends with'),
			'Is greater than' => Shopp::__('Is greater than'),
			'Is greater than or equal to' => Shopp::__('Is greater than or equal to'),
			'Is less than' => Shopp::__('Is less than'),
			'Is less than or equal to' => Shopp::__('Is less than or equal to')
		));

		echo json_encode($rules);
	}

	public static function conditions () {
		$conditions = apply_filters('shopp_discount_conditions', array(
			'Catalog' => array(
				'Name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Category' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_category'),
				'Variation' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text'),
				'Price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Sale price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Type' => array('logic' => array('boolean'), 'value' => 'text'),
				'In stock' => array('logic' => array('boolean', 'amount'), 'value' => 'text')
			),
			'Cart' => array(
				'Any item name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Any item quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Any item amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Total quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Shipping amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Subtotal amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Discount amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Customer type' => array('logic' => array('boolean'), 'value' => 'text', 'source' => 'shopp_customer_types'),
				'Ship-to country' => array('logic' => array('boolean'), 'value' => 'text', 'source' => 'shopp_target_markets', 'suggest' => 'alt'),
				'Promo use count' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Promo code' => array('logic' => array('boolean'), 'value' => 'text')
			),
			'Cart Item' => array(
				'Any item name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Any item quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Any item amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Total quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Shipping amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Subtotal amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Discount amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Customer type' => array('logic' => array('boolean'), 'value' => 'text', 'source' => 'shopp_customer_types'),
				'Ship-to country' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_target_markets'),
				'Promo use count' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Promo code' => array('logic' => array('boolean'), 'value' => 'text')
			),
			'Cart Item Target' => array(
				'Name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Category' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_category'),
				'Tag name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_tag'),
				'Variation' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text',),
				'Input name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text'),
				'Input value' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text'),
				'Quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'text'),
				'Unit price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Total price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Discount amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price')
			)
		));
		echo json_encode($conditions);
	}

	public static function logic () {
		$logic = apply_filters('shopp_discount_logic', array(
			'boolean' => array('Is equal to', 'Is not equal to'),
			'fuzzy' => array('Contains', 'Does not contain', 'Begins with', 'Ends with'),
			'amount' => array('Is greater than', 'Is greater than or equal to', 'Is less than', 'Is less than or equal to')
		));
		echo json_encode($logic);
	}

}
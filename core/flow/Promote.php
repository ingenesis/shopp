<?php
/**
 * Promote
 *
 * Flow controller for promotion management interfaces
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, January 6, 2010
 * @package shopp
 * @subpackage promotions
 **/

/**
 * Promote
 *
 * @package promotions
 * @author Jonathan Davis
 **/
class Promote extends AdminController {
	public $Notice = false;

	/**
	 * Promote constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	public function __construct () {
		parent::__construct();
		$this->save();

		if (!empty($_GET['id'])) {
			wp_enqueue_script('postbox');
			shopp_enqueue_script('colorbox');
			shopp_enqueue_script('calendar');
			shopp_enqueue_script('suggest');
			do_action('shopp_promo_editor_scripts');
			add_action('admin_head',array(&$this,'layout'));
		} else add_action('admin_print_scripts',array(&$this,'columns'));
		do_action('shopp_promo_admin_scripts');

		$defaults = array(
			'page' => false,
			'action' => false,
			'selected' => array(),
		);
		$args = array_merge($defaults,$_GET);
		extract($args,EXTR_SKIP);
		if (!is_array($selected)) $selected = array($selected);

		$url = add_query_arg(array_merge($_GET, array('page'=>'shopp-promotions')), admin_url('admin.php'));
		$f = array('action', 'selected', 's');
		if ( 'shopp-promotions' == $page && ! empty($action) ) {
			switch ( $action ) {
				case 'enable': Promotion::enableset($selected); break;
				case 'disable': Promotion::disableset($selected); break;
				case 'delete': Promotion::deleteset($selected); break;
				case 'duplicate': $P = new Promotion($selected[0]); $P->duplicate(); break;
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
		$Shopp = Shopp::object();
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
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_promotions') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		$table = DatabaseObject::tablename(Promotion::$table);

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

		$url = add_query_arg(array_merge($_GET,array('page'=>'shopp-promotions')),admin_url('admin.php'));
		$f = array('action','selected','s');
		$url = remove_query_arg( $f, $url );

		$pagenum = absint( $paged );
		$start = ($per_page * ($pagenum-1));

		$where = array();
		if ( ! empty($s) ) $where[] = "name LIKE '%$s%'";
		if ( $status ) {
			$datesql = Promotion::activedates();
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
			'catalog' => __('Catalog Promotions','Shopp'),
			'cart' => __('Cart Promotions','Shopp'),
			'cartitem' => __('Cart Item Promotions','Shopp')
		);

		$num_pages = ceil($count / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));

		include(SHOPP_PATH.'/core/ui/promotions/promotions.php');
	}


	protected function save() {
		if ( empty($_POST['save']) ) return;

		check_admin_referer('shopp-save-promotion');

		if ( 'new' !== $_POST['id'] ) {
			$Promotion = new Promotion($_POST['id']);
		} else $Promotion = new Promotion();

		if ( ! empty($_POST['starts']['month']) && ! empty($_POST['starts']['date']) && ! empty($_POST['starts']['year']) )
			$_POST['starts'] = mktime(0, 0, 0, $_POST['starts']['month'], $_POST['starts']['date'], $_POST['starts']['year']);
		else $_POST['starts'] = 1;

		if ( ! empty($_POST['ends']['month']) && ! empty($_POST['ends']['date']) && ! empty($_POST['ends']['year']) )
			$_POST['ends'] = mktime(23, 59, 59, $_POST['ends']['month'], $_POST['ends']['date'], $_POST['ends']['year']);
		else $_POST['ends'] = 1;
		if ( isset($_POST['rules']) ) $_POST['rules'] = stripslashes_deep($_POST['rules']);

		$Promotion->updates($_POST);
		$Promotion->save();

		do_action_ref_array('shopp_promo_saved',array(&$Promotion));

		// $Promotion->reset_discounts();
		if ($Promotion->target == "Catalog")
			$Promotion->catalog();

		// Stay in the editor
		$url = add_query_arg(array('page'=>'shopp-promotions', 'id' => $Promotion->id), admin_url('admin.php'));
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
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'discount'=>__('Discount','Shopp'),
			'applied'=>__('Type','Shopp'),
			'eff'=>__('Status','Shopp'))
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
		$Admin =& $Shopp->Flow->Admin;
		include SHOPP_PATH . '/core/ui/promotions/ui.php' ;
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
			$Promotion = new Promotion($_GET['id']);
		} else $Promotion = new Promotion();

		include SHOPP_PATH . '/core/ui/promotions/editor.php';
	}


} // end Promote class
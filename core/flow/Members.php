<?php
/**
 * Members.php
 *
 * Memberships admin and access controller
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, March 28, 2011
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Members
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 **/
class Members extends ShoppAdminController {

	/**
	 * Members constructor
	 *
	 * @return void
	 * @author Jonathan Davis
	 **/
	function __construct () {
		parent::__construct();
		if (!empty($_GET['id'])) {
			wp_enqueue_script('postbox');
			wp_enqueue_script('jquery-ui-draggable');
			shopp_enqueue_script('jquery-tmpl');
			shopp_enqueue_script('suggest');
			shopp_enqueue_script('search-select');
			shopp_enqueue_script('membership-editor');
			shopp_enqueue_script('colorbox');
			do_action('shopp_membership_editor_scripts');
			add_action('admin_head',array(&$this,'layout'));
		} else add_action('admin_print_scripts',array(&$this,'columns'));
		do_action('shopp_membership_admin_scripts');
	}

	/**
	 * Parses admin requests to determine the interface to render
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	function admin () {
		if (!empty($_GET['id'])) $this->editor();
		else $this->memeberships();
	}

	/**
	 * Interface processor for the customer list screen
	 *
	 * Handles processing customer list actions and displaying the
	 * customer list screen
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	function memeberships () {
		$Shopp = Shopp::object();

		$defaults = array(
			'page' => false,
			'deleting' => false,
			'delete' => false,
			'pagenum' => 1,
			'per_page' => 20,
			's' => '',
		);

		$args = array_merge($defaults,$_GET);
		extract($args, EXTR_SKIP);

		if ($page == $this->Admin->pagename('memberships')
				&& !empty($deleting)
				&& !empty($delete)
				&& is_array($delete)
				//&& current_user_can('shopp_delete_memberships')
			) {
			foreach($delete as $deletion) {
				$MemberPlan = new MemberPlan($deletion);
				$MemberPlan->delete();
			}
		}

		if (!empty($_POST['save'])) {
			check_admin_referer('shopp-save-membership');

			if ($_POST['id'] != "new") {
				$MemberPlan = new MemberPlan($_POST['id']);
			} else $MemberPlan = new MemberPlan();

			$MemberPlan->updates($_POST);
			$MemberPlan->save();

			$MemberPlan->load_stages();
			$stages = array_keys($MemberPlan->stages);


			// Process updates
			foreach ($_POST['stages'] as $i => $stage) {

				if (empty($stage['id'])) {
					$Stage = new MemberStage($MemberPlan->id);
					$stage['parent'] = $MemberPlan->id;
				} else $Stage = new MemberStage($MemberPlan->id,$stage['id']);

				$Stage->updates($stage);
				$Stage->sortorder = $i;
				$Stage->save();

				$Stage->content = array();
				$stage_updates[] = $Stage->id;

				// If the stage data did not save, go to the next stage record
				if (empty($Stage->id)) continue;

				foreach ($stage['rules'] as $type => $rules) {
					foreach ($rules as $rule) {
						$AccessRule = new MemberAccess($Stage->id,$type,$rule['access']);
						if (empty($AccessRule->id)) $AccessRule->save();

						// If access rule applies to all content, skip content cataloging
						if (strpos($AccessRule->value,'-all') !== false) continue;

						// Catalog content access rules for this access taxonomy
						foreach ($rule['content'] as $id => $name) {
							$CatalogEntry = new MemberContent($id,$AccessRule->id,$Stage->id);
							if (empty($CatalogEntry->id)) $CatalogEntry->save();
							$Stage->content[$AccessRule->id][$id] = $name;
						} // endforeach $rule['content']

					}// endforeach $rules
				} // endforeach $stage['rules']

				$Stage->save(); // Secondary save for specific content rules

			} // endforeach $_POST['stages']
		}

		$stageids = array_diff($stages,$stage_updates);
		if (!empty($stageids)) {
			$stagelist = join(',',$stageids);

			// Delete Catalog entries
			$ContentRemoval = new MemberContent();
			sDB::query("DELETE FROM $ContentRemoval->_table WHERE 0 < FIND_IN_SET(parent,'$stagelist')");

			// Delete Access taxonomies
			$AccessRemoval = new MemberAccess();
			sDB::query("DELETE FROM $AccessRemoval->_table WHERE 0 < FIND_IN_SET(parent,'$stagelist')");

			// Remove old stages
			$StageRemoval = new MemberStage();
			sDB::query("DELETE FROM $StageRemoval->_table WHERE 0 < FIND_IN_SET(id,'$stagelist')");

		}


		$pagenum = absint( $pagenum );
		if ( empty($pagenum) )
			$pagenum = 1;
		if( !$per_page || $per_page < 0 )
			$per_page = 20;
		$index = ($per_page * ($pagenum-1));

		if (!empty($start)) {
			$startdate = $start;
			list($month,$day,$year) = explode("/",$startdate);
			$starts = mktime(0,0,0,$month,$day,$year);
		}
		if (!empty($end)) {
			$enddate = $end;
			list($month,$day,$year) = explode("/",$enddate);
			$ends = mktime(23,59,59,$month,$day,$year);
		}

		$membership_table = ShoppDatabaseObject::tablename(MemberPlan::$table);
		$MemberPlan = new MemberPlan();

		$where = '';
		// if (!empty($s)) {
		// 	$s = stripslashes($s);
		// 	if (preg_match_all('/(\w+?)\:(?="(.+?)"|(.+?)\b)/',$s,$props,PREG_SET_ORDER)) {
		// 		foreach ($props as $search) {
		// 			$keyword = !empty($search[2])?$search[2]:$search[3];
		// 			switch(strtolower($search[1])) {
		// 				case "company": $where .= ((empty($where))?"WHERE ":" AND ")."c.company LIKE '%$keyword%'"; break;
		// 				case "login": $where .= ((empty($where))?"WHERE ":" AND ")."u.user_login LIKE '%$keyword%'"; break;
		// 				case "address": $where .= ((empty($where))?"WHERE ":" AND ")."(b.address LIKE '%$keyword%' OR b.xaddress='%$keyword%')"; break;
		// 				case "city": $where .= ((empty($where))?"WHERE ":" AND ")."b.city LIKE '%$keyword%'"; break;
		// 				case "province":
		// 				case "state": $where .= ((empty($where))?"WHERE ":" AND ")."b.state='$keyword'"; break;
		// 				case "zip":
		// 				case "zipcode":
		// 				case "postcode": $where .= ((empty($where))?"WHERE ":" AND ")."b.postcode='$keyword'"; break;
		// 				case "country": $where .= ((empty($where))?"WHERE ":" AND ")."b.country='$keyword'"; break;
		// 			}
		// 		}
		// 	} elseif (strpos($s,'@') !== false) {
		// 		 $where .= ((empty($where))?"WHERE ":" AND ")."c.email='$s'";
		// 	} else $where .= ((empty($where))?"WHERE ":" AND ")." (c.id='$s' OR CONCAT(c.firstname,' ',c.lastname) LIKE '%$s%' OR c.company LIKE '%$s%')";
		//
		// }
		// if (!empty($starts) && !empty($ends)) $where .= ((empty($where))?"WHERE ":" AND ").' (UNIX_TIMESTAMP(c.created) >= '.$starts.' AND UNIX_TIMESTAMP(c.created) <= '.$ends.')';

		$count = sDB::query("SELECT count(*) as total FROM $MemberPlan->_table AS c $where");
		$query = "SELECT *
					FROM $MemberPlan->_table
					WHERE parent='$MemberPlan->parent'
						AND context='$MemberPlan->context'
						AND type='$MemberPlan->type'
					LIMIT $index,$per_page";

		$MemberPlans = sDB::query($query,'array');

		$num_pages = ceil($count->total / $per_page);
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pagenum', '%#%' ),
			'format' => '',
			'total' => $num_pages,
			'current' => $pagenum
		));

		$authentication = shopp_setting('account_system');


		include(SHOPP_ADMIN_PATH."/memberships/memberships.php");

	}

	/**
	 * Registers the column headers for the customer list screen
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	function columns () {
		shopp_enqueue_script('calendar');
		register_column_headers('shopp_page_shopp-memberships', array(
			'cb'=>'<input type="checkbox" />',
			'name'=>__('Name','Shopp'),
			'type'=>__('Type','Shopp'),
			'products'=>__('Products','Shopp'),
			'members'=>__('Members','Shopp')
		));

	}

	/**
	 * Builds the interface layout for the customer editor
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	function layout () {
		global $Shopp,$ruletypes,$rulegroups;
		$Admin =& $Shopp->Flow->Admin;

		$rulegroups = apply_filters('shopp_access_rule_groups',array(
			'wp' => __('WordPress','Shopp'),
			'shopp' => __('Shopp','Shopp')
		));
		$ruletypes = apply_filters('shopp_access_rule_types',array(
			'wp_posts' => __('Posts','Shopp'),
			'wp_pages' => __('Pages','Shopp'),
			'wp_categories' => __('Categories','Shopp'),
			'wp_tags' => __('Tags','Shopp'),
			'wp_media' => __('Media','Shopp'),

			'shopp_memberships' => __('Memberships','Shopp'),
			'shopp_products' => __('Products','Shopp'),
			'shopp_categories' => __('Categories','Shopp'),
			'shopp_tags' => __('Tags','Shopp'),
			'shopp_promotions' => __('Promotions','Shopp'),
			'shopp_downloads' => __('Downloads','Shopp')
		));

		include(SHOPP_ADMIN_PATH."/memberships/ui.php");
	}

	/**
	 * Interface processor for the customer editor
	 *
	 * Handles rendering the interface, processing updated customer details
	 * and handing saving them back to the database
	 *
	 * @author Jonathan Davis
	 * @return void
	 **/
	function editor () {
		global $ruletypes, $rulegroups;
		$Shopp = Shopp::object();

		if ( ! current_user_can('shopp_memberships') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ($_GET['id'] != "new") {
			$MemberPlan = new MemberPlan($_GET['id']);
			if (empty($MemberPlan->id))
				wp_die(__('The requested membership record does not exist.','Shopp'));
			$MemberPlan->load_stages();
			$MemberPlan->load_access();
		} else $MemberPlan = new MemberPlan();

		$skip = array('created','modified','numeral','context','type','sortorder','parent');
		foreach ($MemberPlan->stages as &$Stage) {
			foreach ($Stage->rules as &$rules) {
				foreach ($rules as &$Access)
					if (method_exists($Access,'json'))
						$Access = $Access->json($skip);
			}
			$Stage = $Stage->json($skip);
		}
		include(SHOPP_ADMIN_PATH."/memberships/editor.php");
	}


} // END class Members
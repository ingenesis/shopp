<?php
/**
 * Admin.php
 *
 * Admin Developer API
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

/**
 * Add a menu to the Shopp menu area
 *
 * @author Jonathan Davis
 * @since 1.3
 *
 * @param string $label	The translated label to use for the menu
 * @param string $page The Shopp-internal menu page name (plugin prefix will be automatically added)
 * @param integer $position The index position of where to add the menu
 * @param mixed $handler The callback handler to use to handle the page
 * @param string $access The access capability required to see the menu
 * @return integer The position the menu was added
 **/
function shopp_admin_add_menu ( string $label, string $page, integer $position = null, $handler = false, string $access = null ) {

	global $menu;
	$Admin = ShoppAdmin();

	if ( is_null($position) ) $position = 35;
	if ( is_null($access) ) $access = 'manage_options';	// Restrictive access by default (for admins only)
	if ( false === $handler ) $handler = array(Shopp::object()->Flow, 'parse');

	if ( ! is_callable($handler) ) {
		shopp_debug(__FUNCTION__ . " failed: The specified callback handler is not valid.");
		return false;
	}

	while ( isset($menu[ $position ]) ) $position++;

	$menupage = add_menu_page(
		$label,										// Page title
		$label,										// Menu title
		$access,									// Access level
		$Admin->pagename($page),					// Page
		$handler,									// Handler
		SHOPP_ADMIN_URI . '/icons/clear.png',		// Icon
		$position									// Menu position
	);

	$Admin->menu($page, $menupage);

	do_action_ref_array("shopp_add_topmenu_$page", array($menupage)); // @deprecated
	do_action_ref_array("shopp_add_menu_$page", array($menupage));

	return $position;
}

/**
 * Add a sub-menu to a Shopp menu
 *
 * @author Jonathan Davis
 * @since 1.3
 *
 * @param string $label	The translated label to use for the menu
 * @param string $page The Shopp-internal menu page name (plugin prefix will be automatically added)
 * @param string $menu The Shopp-internal menu page name to append the submenu to
 * @param mixed $handler The callback handler to use to handle the page
 * @param string $access The access capability required to see the menu
 * @return integer The position the menu was added
 **/
function shopp_admin_add_submenu ( string $label, string $page, string $menu = null, $handler = false, string $access = null ) {

	$Admin = ShoppAdmin();
	if ( is_null($menu) ) $Admin->mainmenu();
	if ( is_null($access) ) $access = 'none'; // Restrict access by default
	if ( false === $handler ) $handler = array(Shopp::object()->Flow, 'admin');

	if ( ! is_callable($handler) ) {
		shopp_debug(__FUNCTION__ . " failed: The specified callback handler is not valid.");
		return false;
	}

	$menupage = add_submenu_page(
		$menu,
		$label,
		$label,
		$access,
		$page,
		$handler
	);

	$Admin->menu($page, $menupage);

	do_action("shopp_add_menu_$page");

	return $menupage;

}
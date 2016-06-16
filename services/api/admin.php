<?php
/**
 * Admin.php
 *
 * Admin Developer API
 *
 * @copyright Ingenesis Limited, June 2013
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/API/Admin
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Add a menu to the Shopp menu area
 *
 * @api
 * @since 1.3
 *
 * @param string $label	The translated label to use for the menu
 * @param string $page The Shopp-internal menu page name (plugin prefix will be automatically added)
 * @param integer $position The index position of where to add the menu
 * @param mixed $handler The callback handler to use to handle the page
 * @param string $access The access capability required to see the menu
 * @param string $icon The URL for the icon to use for the menu
 * @return integer The position the menu was added
 **/
function shopp_admin_add_menu ( $label, $page, $position = null, $handler = false, $access = null, $icon = null ) {

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
		$icon,										// Icon
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
 * @api
 * @since 1.3
 *
 * @param string $label	The translated label to use for the menu
 * @param string $page The Shopp-internal menu page name (plugin prefix will be automatically added)
 * @param string $menu The Shopp-internal menu page name to append the submenu to
 * @param mixed $handler The callback handler to use to handle the page
 * @param string $access The access capability required to see the menu
 * @return integer The position the menu was added
 **/
function shopp_admin_add_submenu ( $label, $page, $menu = null, $handler = false, $access = null ) {

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
	$Admin->addtab($page, $menu);

	do_action("shopp_add_menu_$page");

	return $menupage;

}

/**
 * Renders the screen tabs registered for the current plugin page
 *
 * @api
 * @since 1.3
 *
 * @return void
 **/
function shopp_admin_screen_tabs () {
	global $plugin_page;

	$tabs = ShoppAdmin()->tabs( $plugin_page );
	$first = current($tabs);
	$default = $first[1];

	$markup = array();
	foreach ( $tabs as $index => $entry ) {
		list($title, $tab, $parent) = $entry;
		$classes = array('nav-tab');
		if ( ($plugin_page == $parent && $default == $tab) || $plugin_page == $tab )
			$classes[] = 'nav-tab-active';
		$markup[] = '<a href="' . add_query_arg(array('page' => $tab), admin_url('admin.php')) . '" class="' . join(' ', $classes) . '">' . $title . '</a>';
	}

	$pagehook = sanitize_key($plugin_page);
	echo '<h2 class="nav-tab-wrapper">' . join('', apply_filters('shopp_admin_' . $pagehook . '_screen_tabs', $markup)) . '</h2>';
}
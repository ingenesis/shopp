<?php
/**
 * HK.php
 *
 * Hong Kong states
 *
 * @copyright Ingenesis Limited, December 2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Package
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

return array(
	'HONG KONG'       => Shopp::__('Hong Kong Island'),
	'KOWLOON'         => Shopp::__('Kowloon'),
	'NEW TERRITORIES' => Shopp::__('New Territories')
);
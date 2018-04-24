<?php
/**
 * AU.php
 *
 * Australian postal codes
 *
 * @copyright Ingenesis Limited, December 2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Locale
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

return array(
	'ACT' => array('0200-0299', '2600-2618', '2900-2920'),
	'NSW' => array('1000-2599', '2619-2898', '2921-2999'),
	'NT'  => array('0800-0999'),
	'QLD' => array('4000-4999', '9000-9999'),
	'SA'  => array('5000-5799', '5800-5999'),
	'TAS' => array('7000-7999'),
	'VIC' => array('3000-3999', '8000-8999'),
	'WA'  => array('6000-6797', '6800-6999'),
);
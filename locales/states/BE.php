<?php
/**
 * BE.php
 *
 * Belgium states
 *
 * @copyright Ingenesis Limited, December 2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Package
 * @version   1.0
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

return array(
	'VAN' => Shopp::__('Antwerpen'),
	'WBR' => Shopp::__('Brabant Wallon'),
	'BRU' => Shopp::__('Brussels Capital'),
	'WHT' => Shopp::__('Hainaut'),
	'WLG' => Shopp::__('Liège'),
	'VLI' => Shopp::__('Limburg'),
	'WLX' => Shopp::__('Luxembourg'),
	'WNA' => Shopp::__('Namur'),
	'VOV' => Shopp::__('Oost-Vlaanderen'),
	'VBR' => Shopp::__('Vlaams Brabant'),
	'VWV' => Shopp::__('West-Vlaanderen')
);
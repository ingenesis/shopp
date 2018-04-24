<?php
/**
 * addressing.php
 *
 * Provides address formatting
 *
 * @copyright Ingenesis Limited, February 2010-2014
 * @license   GNU GPL version 3 (or later) $@see license.txt
 * @package   Shopp/Locale/Countries
 * @version   @since 1.5
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( ! defined('SHOPP_DEFAULT_ADDRESSING') )
	define('SHOPP_DEFAULT_ADDRESSING', '$name\n$company\n$address\n$xaddress\n$city $state\n$postcode\n$country');

if ( ! defined('SHOPP_POSTCITY_ADDRESSING') )
	define('SHOPP_POSTCITY_ADDRESSING', '$company\n$name\n$address\n$xaddress\n$postcode $city\n$country');

return array(
	'AT' => SHOPP_POSTCITY_ADDRESSING,
	'AU' => '$name\n$company\n$address\n$xaddress\n$city $state $postcode\n$country',
	'BE' => SHOPP_POSTCITY_ADDRESSING,
	'CA' => '$company\n$name\n$address\n$xaddress\n$city $state $postcode\n$country',
	'CH' => SHOPP_POSTCITY_ADDRESSING,
	'CN' => '$country $postcode\n$state, $city, $xaddress, $address\n$company\n$name',
	'CZ' => SHOPP_POSTCITY_ADDRESSING,
	'DE' => SHOPP_POSTCITY_ADDRESSING,
	'DK' => SHOPP_POSTCITY_ADDRESSING,
	'EE' => SHOPP_POSTCITY_ADDRESSING,
	'ES' => '$name\n$company\n$address\n$xaddress\n$postcode $city\n$state\n$country',
	'FI' => SHOPP_POSTCITY_ADDRESSING,
	'FR' => '$company\n$name\n$address\n$xaddress\n$postcode $CITY\n$country',
	'HK' => '$company\n$firstname $LASTNAME\n$address\n$xaddress\n$CITY\n$STATE\n$country',
	'HU' => '$name\n$company\n$city\n$address\n$xaddress\n$postcode\n$country',
	'IN' => '$company\n$name\n$address\n$xaddress\n$city - $postcode\n$state, $country',
	'IS' => SHOPP_POSTCITY_ADDRESSING,
	'IT' => '$company\n$name\n$address\n$xaddress\n$postcode\n$city\n$STATE\n$country',
	'JP' => '$postcode\n$state$city$address\n$xaddress\n$company\n$lastname $firstname\n$country',
	'LI' => SHOPP_POSTCITY_ADDRESSING,
	'NL' => SHOPP_POSTCITY_ADDRESSING,
	'NO' => SHOPP_POSTCITY_ADDRESSING,
	'NZ' => '$name\n$company\n$address\n$xaddress\n$city $postcode\n$country',
	'PL' => SHOPP_POSTCITY_ADDRESSING,
	'SE' => SHOPP_POSTCITY_ADDRESSING,
	'SI' => SHOPP_POSTCITY_ADDRESSING,
	'SK' => SHOPP_POSTCITY_ADDRESSING,
	'TR' => '$name\n$company\n$address\n$xaddress\n$postcode $city $state\n$country',
	'TW' => '$company\n$lastname $firstname\n$address\n$xaddress\n$state, $city $postcode\n$country',
	'US' => '$name\n$company\n$address\n$xaddress\n$city, $ST $postcode\n$country',
	'VN' => '$name\n$company\n$address\n$city\n$country',
);
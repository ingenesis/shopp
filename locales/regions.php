<?php
/**
 * regions.php
 *
 * Maps countries to worldwide regions
 *
 * @copyright Ingenesis Limited, February 2010-2014
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Locale/Countries
 * @version   1.4
 * @since     1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

return array(
	Shopp::__('North America') => array('US', 'USAF', 'USAT', 'CA', 'BB', 'BS', 'BH', 'BM', 'CW', 'GP', 'JM', 'MX', 'PR', 'PM'),
	Shopp::__('Central America') => array('AI', 'AG', 'BZ', 'VG', 'KY', 'CR', 'CU', 'DM', 'DO', 'SV', 'GL', 'GT', 'HT', 'HN', 'MQ', 'MS', 'NI', 'PA', 'BL', 'KN', 'LC', 'MF', 'SX', 'TC'),
	Shopp::__('South America') => array('AR', 'AW', 'BO', 'BR', 'CL', 'CO', 'EC', 'FK', 'GF', 'GD', 'GY', 'PY', 'PE', 'VC', 'GS', 'SR', 'TT', 'UY', 'VE'),
	Shopp::__('Europe') => array('GB', 'AX', 'AL', 'AD', 'AT', 'BY', 'BE', 'BA', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FO', 'FI', 'FR', 'DE', 'GI', 'GR', 'GG', 'HU', 'IS', 'IE', 'IM', 'IT', 'JE', 'LV', 'LI', 'LT', 'LU', 'MK', 'MT', 'MD', 'MC', 'ME', 'NL', 'NO', 'PL', 'PT', 'RO', 'SM', 'RS', 'SK', 'SI', 'ES', 'SJ', 'SE', 'CH', 'UA', 'VA'),
	Shopp::__('Middle East') => array('IR', 'IQ', 'IL', 'JO', 'KW', 'LB', 'OM', 'PK', 'QA', 'SA', 'SY', 'AE'),
	Shopp::__('Africa') => array('DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD', 'KM', 'CG', 'CD', 'CI', 'DJ', 'EG', 'GQ', 'ER', 'ET', 'GA', 'GM', 'GH', 'GN', 'GW', 'KE', 'LS', 'LR', 'LY', 'MG', 'MW', 'ML', 'MR', 'MU', 'YT', 'MA', 'MZ', 'NA', 'NE', 'NG', 'RE', 'RW', 'SH', 'ST', 'SN', 'SC', 'SL', 'SO', 'ZA', 'SS', 'SD', 'SZ', 'TZ', 'TG', 'TN', 'UG', 'EH', 'ZM', 'ZW'),
	Shopp::__('Asia') => array('AF', 'AM', 'AZ', 'BD', 'BT', 'BN', 'MM', 'KH', 'CN', 'GE', 'HK', 'IN', 'JP', 'KZ', 'KG', 'LA', 'MO', 'MY', 'MV', 'MN', 'NP', 'PH', 'RU', 'SG', 'KR', 'LK', 'TW', 'TJ', 'TH', 'TR', 'TM', 'UZ', 'VN', 'YE'),
	Shopp::__('Oceania') => array('AS', 'AU', 'IO', 'CX', 'CC', 'CK', 'TL', 'FM', 'FJ', 'PF', 'TF', 'GU', 'HM', 'ID', 'KI', 'MH', 'NR', 'NC', 'NZ', 'NU', 'NF', 'MP', 'PW', 'PG', 'PN', 'WS', 'SB', 'TK', 'TO', 'TV', 'VU', 'WF')
);
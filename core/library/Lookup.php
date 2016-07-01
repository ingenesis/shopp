<?php
/**
 * Lookup.php
 *
 * Provides reference data tables
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, February  3, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage references
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Lookup
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ShoppLookup {

	/**
	 * Provides a lookup table worldwide regions
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param boolean $map Optional to retrieve the entire regions map instead of just labels
	 * @return array List of regions
	 **/
	public static function regions ( $map = false ) {
		$regions = include self::locale('regions.php');
		if ( false === $map ) $regions = array_keys($regions);
		if ( 'id' === $map ) $regions = array_values($regions);
		return apply_filters('shopp_regions', $regions);
	}

	/**
	 * Finds the translated region name for a specific region index
	 *
	 * @since 1.1
	 * @version 1.4
	 *
	 * @param type $var Description...
	 * @return string The translated region name
	 **/
	public static function region ( $country, $index = false ) {
		$regions = self::regions(true);
		$region = Shopp::array_search_deep($country, $regions);

		if ( empty($region) ) return false;

		if ( false !== $index )
			return array_search($region, array_keys($regions));

		return $region;
	}

	/**
	 * Returns a lookup table of supported country defaults
	 *
	 * The information in the following table has been derived from
	 * the ISO standard documents including ISO-3166 for 2-letter country
	 * codes and ISO-4217 for currency codes
	 *
	 * @see wiki ISO_3166-1 & ISO_4217
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array
	 **/
	public static function countries () {
		return apply_filters('shopp_countries', include self::locale('countries.php'));
	}

	/**
	 * Provides a lookup table of country zones (states/provinces)
	 *
	 * @see wiki ISO_3166-2
	 * @since 1.1
	 *
	 * @return array
	 **/
	public static function country_zones ( array $countries = array() ) {
		$states = array();
		if ( empty($countries) )
			$countries = array_keys(self::countries());

		foreach ( $countries as $country ) {
			$source = self::locale("states/$country.php");
			if ( ! $source ) continue;
			$list = include $source;
			if ( empty($list) ) continue;
			$states[ $country ] = $list;
		}

		return  apply_filters('shopp_country_zones', $states);
	}

	public static function country_divisions ( $country ) {
		$labels = array(
			'states'      => array(Shopp::__('State'), Shopp::__('States')),
			'provinces'   => array(Shopp::__('Province'), Shopp::__('Provinces')),
			'territories' => array(Shopp::__('Territory'), Shopp::__('Territories')),
			'districts'   => array(Shopp::__('District'), Shopp::__('Districts')),
			'regions'     => array(Shopp::__('Region'), Shopp::__('Regions')),
			'counties'    => array(Shopp::__('County'), Shopp::__('Counties')),
			'prefectures' => array(Shopp::__('Prefecture'), Shopp::__('Prefectures')),
		);

		$divisions = apply_filters('shopp_country_division_labels', array(
			'AR' => $labels['provinces'],
			'AT' => array(Shopp::__('Land'), Shopp::__('Lands')),
			'AU' => $labels['states'],
			'BD' => $labels['districts'],
			'BE' => $labels['provinces'],
			'BG' => $labels['regions'],
			'BR' => $labels['states'],
			'CA' => $labels['provinces'],
			'CN' => $labels['provinces'],
			'DE' => $labels['states'],
			'HK' => $labels['territories'],
			'HU' => $labels['counties'],
			'ID' => $labels['provinces'],
			'IR' => $labels['provinces'],
			'IT' => $labels['provinces'],
			'JP' => $labels['prefectures'],
			'MX' => $labels['states'],
			'NL' => $labels['provinces'],
			'US' => $labels['states'],
			'USAF' => $labels['states'],
			'USAT' => $labels['territories']
		));

		if ( isset($divisions[ $country ]) )
			return $divisions[ $country ];
		else return $labels['provinces'];

	}

	public static function imperial_units ( array $countries = array() ) {
		return apply_filters('shopp_imperial_country_units', array(
			'US', 'USAF', 'USAT', 'PR'
		));
	}

	/**
	 * Provides a lookup table of colloquial country areas
	 *
	 * @see wiki ISO_3166-2
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array
	 **/
	public static function country_areas () {
		$_ = array();
		$_['CA'] = array();
		$_['CA']['Northern Canada'] = array('YT','NT','NU');
		$_['CA']['Western Canada'] = array('BC','AB','SK','MB');
		$_['CA']['Eastern Canada'] = array('ON','QC','NB','PE','NS','NL');

		$_['BE'] = array();
		$_['BE']['Vlaamse Gewest']		= array('VAN','VLI','VOV','VBR','VWV');
		$_['BE']['Wallonne, Région']	= array('WBR','WHT','WLG','WLX','WNA');

		$_['SE'] = array();
		$_['SE']['K'] = 'Blekinge län';
		$_['SE']['W'] = 'Dalarnas län';
		$_['SE']['I'] = 'Gotlands län';
		$_['SE']['X'] = 'Gävleborgs län';
		$_['SE']['N'] = 'Hallands län';
		$_['SE']['Z'] = 'Jämtlands län';
		$_['SE']['F'] = 'Jönköpings län';
		$_['SE']['H'] = 'Kalmar län';
		$_['SE']['G'] = 'Kronobergs län';
		$_['SE']['BD'] = 'Norrbottens län';
		$_['SE']['M'] = 'Skåne län';
		$_['SE']['AB'] = 'Stockholms län';
		$_['SE']['D'] = 'Södermanlands län';
		$_['SE']['C'] = 'Uppsala län';
		$_['SE']['S'] = 'Värmlands län';
		$_['SE']['AC'] = 'Västerbottens län';
		$_['SE']['Y'] = 'Västernorrlands län';
		$_['SE']['U'] = 'Västmanlands län';
		$_['SE']['O'] = 'Västra Götalands län';
		$_['SE']['T'] = 'Örebro län';
		$_['SE']['E'] = 'Östergötlands län';

		$_['US'] = array();
		$_['US']['Continental US']		= array();
		$_['US']['Northeast US']		= array('MA','RI','NH','ME','VT','CT','NJ','NY','PA');
		$_['US']['Midwest US']			= array('OH','IN','MI','IA','WI','MN','SD','ND','IL','MO','KS','NE');
		$_['US']['South US'] 			= array('DE','DC','MD','VA','WV','NC','SC','GA','FL','AL','TN','MS','KY','LA','AR','OK','TX');
		$_['US']['West US'] 			= array('MT','CO','WY','ID','UT','AZ','NM','NV','CA','OR','WA','HI','AK');
		$_['US']['Continental US']		= array_merge($_['US']['Northeast US'],$_['US']['Midwest US'],$_['US']['South US'], array_diff($_['US']['West US'],array('HI','AK')) );

		$_['USAF'] = array();
		$_['USAF']['Americas'] = array('AA');
		$_['USAF']['Europe'] = array('AE');
		$_['USAF']['Pacific'] = array('AP');

		return apply_filters('shopp_areas', $_);
	}

	// ISO 3166-1 alpha 2 to numeric
	public static function country_numeric () {
		$_ = array('AF'=>'004','AX'=>'248','AL'=>'008','DZ'=>'012','AS'=>'016','AD'=>'020','AO'=>'024','AI'=>'660','AQ'=>'010','AG'=>'028','AR'=>'032','AM'=>'051','AW'=>'533','AU'=>'036','AT'=>'040','AZ'=>'031','BS'=>'044','BH'=>'048','BD'=>'050','BB'=>'052','BY'=>'112','BE'=>'056','BZ'=>'084','BJ'=>'204','BM'=>'060','BT'=>'064','BO'=>'068','BQ'=>'535','BA'=>'070','BW'=>'072','BV'=>'074','BR'=>'076','IO'=>'086','BN'=>'096','BG'=>'100','BF'=>'854','BI'=>'108','KH'=>'116','CM'=>'120','CA'=>'124','CV'=>'132','KY'=>'136','CF'=>'140','TD'=>'148','CL'=>'152','CN'=>'156','CX'=>'162','CC'=>'166','CO'=>'170','KM'=>'174','CG'=>'178','CD'=>'180','CK'=>'184','CR'=>'188','CI'=>'384','HR'=>'191','CU'=>'192','CW'=>'531','CY'=>'196','CZ'=>'203','DK'=>'208','DJ'=>'262','DM'=>'212','DO'=>'214','EC'=>'218','EG'=>'818','SV'=>'222','GQ'=>'226','ER'=>'232','EE'=>'233','ET'=>'231','FK'=>'238','FO'=>'234','FJ'=>'242','FI'=>'246','FR'=>'250','GF'=>'254','PF'=>'258','TF'=>'260','GA'=>'266','GM'=>'270','GE'=>'268','DE'=>'276','GH'=>'288','GI'=>'292','GR'=>'300','GL'=>'304','GD'=>'308','GP'=>'312','GU'=>'316','GT'=>'320','GG'=>'831','GN'=>'324','GW'=>'624','GY'=>'328','HT'=>'332','HM'=>'334','VA'=>'336','HN'=>'340','HK'=>'344','HU'=>'348','IS'=>'352','IN'=>'356','ID'=>'360','IR'=>'364','IQ'=>'368','IE'=>'372','IM'=>'833','IL'=>'376','IT'=>'380','JM'=>'388','JP'=>'392','JE'=>'832','JO'=>'400','KZ'=>'398','KE'=>'404','KI'=>'296','KP'=>'408','KR'=>'410','KW'=>'414','KG'=>'417','LA'=>'418','LV'=>'428','LB'=>'422','LS'=>'426','LR'=>'430','LY'=>'434','LI'=>'438','LT'=>'440','LU'=>'442','MO'=>'446','MK'=>'807','MG'=>'450','MW'=>'454','MY'=>'458','MV'=>'462','ML'=>'466','MT'=>'470','MH'=>'584','MQ'=>'474','MR'=>'478','MU'=>'480','YT'=>'175','MX'=>'484','FM'=>'583','MD'=>'498','MC'=>'492','MN'=>'496','ME'=>'499','MS'=>'500','MA'=>'504','MZ'=>'508','MM'=>'104','NA'=>'516','NR'=>'520','NP'=>'524','NL'=>'528','NC'=>'540','NZ'=>'554','NI'=>'558','NE'=>'562','NG'=>'566','NU'=>'570','NF'=>'574','MP'=>'580','NO'=>'578','OM'=>'512','PK'=>'586','PW'=>'585','PS'=>'275','PA'=>'591','PG'=>'598','PY'=>'600','PE'=>'604','PH'=>'608','PN'=>'612','PL'=>'616','PT'=>'620','PR'=>'630','QA'=>'634','RE'=>'638','RO'=>'642','RU'=>'643','RW'=>'646','BL'=>'652','SH'=>'654','KN'=>'659','LC'=>'662','MF'=>'663','PM'=>'666','VC'=>'670','WS'=>'882','SM'=>'674','ST'=>'678','SA'=>'682','SN'=>'686','RS'=>'688','SC'=>'690','SL'=>'694','SG'=>'702','SX'=>'534','SK'=>'703','SI'=>'705','SB'=>'090','SO'=>'706','ZA'=>'710','GS'=>'239','SS'=>'728','ES'=>'724','LK'=>'144','SD'=>'729','SR'=>'740','SJ'=>'744','SZ'=>'748','SE'=>'752','CH'=>'756','SY'=>'760','TW'=>'158','TJ'=>'762','TZ'=>'834','TH'=>'764','TL'=>'626','TG'=>'768','TK'=>'772','TO'=>'776','TT'=>'780','TN'=>'788','TR'=>'792','TM'=>'795','TC'=>'796','TV'=>'798','UG'=>'800','UA'=>'804','AE'=>'784','GB'=>'826','US'=>'840','UM'=>'581','UY'=>'858','UZ'=>'860','VU'=>'548','VE'=>'862','VN'=>'704','VG'=>'092','VI'=>'850','WF'=>'876','EH'=>'732','YE'=>'887','ZM'=>'894','ZW'=>'716');
		return apply_filters('shopp_country_numeric', $_);
	}

	// ISO 3166-1 alpha 2 to alpha 3
	public static function country_alpha3 () {
		$_ = array('AF'=>'AFG','AX'=>'ALA','AL'=>'ALB','DZ'=>'DZA','AS'=>'ASM','AD'=>'AND','AO'=>'AGO','AI'=>'AIA','AQ'=>'ATA','AG'=>'ATG','AR'=>'ARG','AM'=>'ARM','AW'=>'ABW','AU'=>'AUS','AT'=>'AUT','AZ'=>'AZE','BS'=>'BHS','BH'=>'BHR','BD'=>'BGD','BB'=>'BRB','BY'=>'BLR','BE'=>'BEL','BZ'=>'BLZ','BJ'=>'BEN','BM'=>'BMU','BT'=>'BTN','BO'=>'BOL','BQ'=>'BES','BA'=>'BIH','BW'=>'BWA','BV'=>'BVT','BR'=>'BRA','IO'=>'IOT','BN'=>'BRN','BG'=>'BGR','BF'=>'BFA','BI'=>'BDI','KH'=>'KHM','CM'=>'CMR','CA'=>'CAN','CV'=>'CPV','KY'=>'CYM','CF'=>'CAF','TD'=>'TCD','CL'=>'CHL','CN'=>'CHN','CX'=>'CXR','CC'=>'CCK','CO'=>'COL','KM'=>'COM','CG'=>'COG','CD'=>'COD','CK'=>'COK','CR'=>'CRI','CI'=>'CIV','HR'=>'HRV','CU'=>'CUB','CW'=>'CUW','CY'=>'CYP','CZ'=>'CZE','DK'=>'DNK','DJ'=>'DJI','DM'=>'DMA','DO'=>'DOM','EC'=>'ECU','EG'=>'EGY','SV'=>'SLV','GQ'=>'GNQ','ER'=>'ERI','EE'=>'EST','ET'=>'ETH','FK'=>'FLK','FO'=>'FRO','FJ'=>'FJI','FI'=>'FIN','FR'=>'FRA','GF'=>'GUF','PF'=>'PYF','TF'=>'ATF','GA'=>'GAB','GM'=>'GMB','GE'=>'GEO','DE'=>'DEU','GH'=>'GHA','GI'=>'GIB','GR'=>'GRC','GL'=>'GRL','GD'=>'GRD','GP'=>'GLP','GU'=>'GUM','GT'=>'GTM','GG'=>'GGY','GN'=>'GIN','GW'=>'GNB','GY'=>'GUY','HT'=>'HTI','HM'=>'HMD','VA'=>'VAT','HN'=>'HND','HK'=>'HKG','HU'=>'HUN','IS'=>'ISL','IN'=>'IND','ID'=>'IDN','IR'=>'IRN','IQ'=>'IRQ','IE'=>'IRL','IM'=>'IMN','IL'=>'ISR','IT'=>'ITA','JM'=>'JAM','JP'=>'JPN','JE'=>'JEY','JO'=>'JOR','KZ'=>'KAZ','KE'=>'KEN','KI'=>'KIR','KP'=>'PRK','KR'=>'KOR','KW'=>'KWT','KG'=>'KGZ','LA'=>'LAO','LV'=>'LVA','LB'=>'LBN','LS'=>'LSO','LR'=>'LBR','LY'=>'LBY','LI'=>'LIE','LT'=>'LTU','LU'=>'LUX','MO'=>'MAC','MK'=>'MKD','MG'=>'MDG','MW'=>'MWI','MY'=>'MYS','MV'=>'MDV','ML'=>'MLI','MT'=>'MLT','MH'=>'MHL','MQ'=>'MTQ','MR'=>'MRT','MU'=>'MUS','YT'=>'MYT','MX'=>'MEX','FM'=>'FSM','MD'=>'MDA','MC'=>'MCO','MN'=>'MNG','ME'=>'MNE','MS'=>'MSR','MA'=>'MAR','MZ'=>'MOZ','MM'=>'MMR','NA'=>'NAM','NR'=>'NRU','NP'=>'NPL','NL'=>'NLD','NC'=>'NCL','NZ'=>'NZL','NI'=>'NIC','NE'=>'NER','NG'=>'NGA','NU'=>'NIU','NF'=>'NFK','MP'=>'MNP','NO'=>'NOR','OM'=>'OMN','PK'=>'PAK','PW'=>'PLW','PS'=>'PSE','PA'=>'PAN','PG'=>'PNG','PY'=>'PRY','PE'=>'PER','PH'=>'PHL','PN'=>'PCN','PL'=>'POL','PT'=>'PRT','PR'=>'PRI','QA'=>'QAT','RE'=>'REU','RO'=>'ROU','RU'=>'RUS','RW'=>'RWA','BL'=>'BLM','SH'=>'SHN','KN'=>'KNA','LC'=>'LCA','MF'=>'MAF','PM'=>'SPM','VC'=>'VCT','WS'=>'WSM','SM'=>'SMR','ST'=>'STP','SA'=>'SAU','SN'=>'SEN','RS'=>'SRB','SC'=>'SYC','SL'=>'SLE','SG'=>'SGP','SX'=>'SXM','SK'=>'SVK','SI'=>'SVN','SB'=>'SLB','SO'=>'SOM','ZA'=>'ZAF','GS'=>'SGS','SS'=>'SSD','ES'=>'ESP','LK'=>'LKA','SD'=>'SDN','SR'=>'SUR','SJ'=>'SJM','SZ'=>'SWZ','SE'=>'SWE','CH'=>'CHE','SY'=>'SYR','TW'=>'TWN','TJ'=>'TJK','TZ'=>'TZA','TH'=>'THA','TL'=>'TLS','TG'=>'TGO','TK'=>'TKL','TO'=>'TON','TT'=>'TTO','TN'=>'TUN','TR'=>'TUR','TM'=>'TKM','TC'=>'TCA','TV'=>'TUV','UG'=>'UGA','UA'=>'UKR','AE'=>'ARE','GB'=>'GBR','US'=>'USA','UM'=>'UMI','UY'=>'URY','UZ'=>'UZB','VU'=>'VUT','VE'=>'VEN','VN'=>'VNM','VG'=>'VGB','VI'=>'VIR','WF'=>'WLF','EH'=>'ESH','YE'=>'YEM','ZM'=>'ZMB','ZW'=>'ZWE');
		return apply_filters('shopp_country_alpha3', $_);
	}

	/**
	 * Provides a list of country codes for countries that use VAT taxes
	 *
	 * @since 1.1
	 *
	 * @return array List of country codes
	 **/
	public static function country_inclusive_taxes () {
		$_ = array_merge(self::country_euvat(), self::country_gst());
		return (array)apply_filters('shopp_country_inclusive_taxes', $_);
	}

	public static function country_euvat () {
		$_ = array( // Includes 28 core member states plus dependent territories
			'AT','BE','BG','CY','CZ','DE','DK','ES','ET','EE',
			'FI','FR','GB','GR','HR','HU','IE','IM','IT','LB',
			'LT','LU','LV','MT','NL','PL','PT','RO','SE','SI',
			'SK');
		return (array)apply_filters('shopp_country_euvat', $_);
	}

	public static function country_gst () {
		$_ = array(
			'AU','JO','NZ'
		);
		return (array)apply_filters('shopp_country_gst', $_);
	}

	public static function country_no_postal_codes() {
		$_ = array(
			'AO','AG','AW','BS','BZ','BJ','BW','BF','BI','CM','CF','KM','CG','CD','CK','CI','DJ','DM','GQ','ER','FJ','TF','GM','GH','GD','GN','GY','HK','IE','JM','KE','KI','MO','MW','ML','MR','MU','MS','NR','AN','NU','KP','PA','QA','RW','KN','LC','ST','SA','SC','SL','SB','SO','ZA','SR','SY','TZ','TL','TK','TO','TT','TV','UG','AE','VU','YE','ZW'
		);

		return (array)apply_filters('shopp_country_no_postal_codes', $_);
	}

	public static function postcodes ( array $countries = array() ) {

		$postcodes = array();
		if ( empty($countries) )
			$countries = array_keys(self::countries());

		foreach ( $countries as $country ) {
			$source = self::locale("postcodes/$country.php");
			if ( ! $source ) continue;
			$list = include $source;
			if ( empty($list) ) continue;
			$postcodes[ $country ] = $list;
		}

		return (array)apply_filters('shopp_postcodes', $postcodes);

	}

	public static function postcode_patterns () {
		$_ = array(
			'AU' => '\d{4}',
			'CA' => '\w\d\w\s*\d\w\d',
			'US' => '(\d{5})(\-\d{4})?',
			'GB' => '(GIR 0AA)|(((A[BL]|B[ABDHLNRSTX]?|C[ABFHMORTVW]|D[ADEGHLNTY]|E[HNX]?|F[KY]|G[LUY]?|H[ADGPRSUX]|I[GMPV]|JE|K[ATWY]|L[ADELNSU]?|M[EKL]?|N[EGNPRW]?|O[LX]|P[AEHLOR]|R[GHM]|S[AEGKLMNOPRSTY]?|T[ADFNQRSW]|UB|W[ADFNRSV]|YO|ZE)[1-9]?[0-9]|((E|N|NW|SE|SW|W)1|EC[1-4]|WC[12])[A-HJKMNPR-Y]|(SW|W)([2-9]|[1-9][0-9])|EC[1-9][0-9]) [0-9][ABD-HJLNP-UW-Z]{2})',
		);
		$_['USAF'] = $_['USAT'] = $_['US'];
		$_['NZ'] = $_['AU'];

		return apply_filters('shopp_postcode_patterns',$_);

	}

	// ISO 4217 Currency Codes
	public static function currency_codes () {
		$_ =  array('AED'=>'784', 'AFN'=>'971', 'ALL'=>'008', 'AMD'=>'051', 'ANG'=>'532', 'AOA'=>'973', 'ARS'=>'032', 'AUD'=>'036', 'AWG'=>'533', 'AZN'=>'944', 'BAM'=>'977', 'BBD'=>'052', 'BDT'=>'050', 'BGN'=>'975', 'BHD'=>'048', 'BIF'=>'108', 'BMD'=>'060', 'BND'=>'096', 'BOB'=>'068', 'BOV'=>'984', 'BRL'=>'986', 'BSD'=>'044', 'BTN'=>'064', 'BWP'=>'072', 'BYR'=>'974', 'BZD'=>'084', 'CAD'=>'124', 'CDF'=>'976', 'CHE'=>'947', 'CHF'=>'756', 'CHW'=>'948', 'CLF'=>'990', 'CLP'=>'152', 'CNY'=>'156', 'COP'=>'170', 'COU'=>'970', 'CRC'=>'188', 'CUC'=>'931', 'CUP'=>'192', 'CVE'=>'132', 'CZK'=>'203', 'DJF'=>'262', 'DKK'=>'208', 'DOP'=>'214', 'DZD'=>'012', 'EGP'=>'818', 'ERN'=>'232', 'ETB'=>'230', 'EUR'=>'978', 'FJD'=>'242', 'FKP'=>'238', 'GBP'=>'826', 'GEL'=>'981', 'GHS'=>'936', 'GIP'=>'292', 'GMD'=>'270', 'GNF'=>'324', 'GTQ'=>'320', 'GYD'=>'328', 'HKD'=>'344', 'HNL'=>'340', 'HRK'=>'191', 'HTG'=>'332', 'HUF'=>'348', 'IDR'=>'360', 'ILS'=>'376', 'INR'=>'356', 'IQD'=>'368', 'IRR'=>'364', 'ISK'=>'352', 'JMD'=>'388', 'JOD'=>'400', 'JPY'=>'392', 'KES'=>'404', 'KGS'=>'417', 'KHR'=>'116', 'KMF'=>'174', 'KPW'=>'408', 'KRW'=>'410', 'KWD'=>'414', 'KYD'=>'136', 'KZT'=>'398', 'LAK'=>'418', 'LBP'=>'422', 'LKR'=>'144', 'LRD'=>'430', 'LSL'=>'426', 'LTL'=>'440', 'LVL'=>'428', 'LYD'=>'434', 'MAD'=>'504', 'MDL'=>'498', 'MGA'=>'969', 'MKD'=>'807', 'MMK'=>'104', 'MNT'=>'496', 'MOP'=>'446', 'MRO'=>'478', 'MUR'=>'480', 'MVR'=>'462', 'MWK'=>'454', 'MXN'=>'484', 'MXV'=>'979', 'MYR'=>'458', 'MZN'=>'943', 'NAD'=>'516', 'NGN'=>'566', 'NIO'=>'558', 'NOK'=>'578', 'NPR'=>'524', 'NZD'=>'554', 'OMR'=>'512', 'PAB'=>'590', 'PEN'=>'604', 'PGK'=>'598', 'PHP'=>'608', 'PKR'=>'586', 'PLN'=>'985', 'PYG'=>'600', 'QAR'=>'634', 'RON'=>'946', 'RSD'=>'941', 'RUB'=>'643', 'RWF'=>'646', 'SAR'=>'682', 'SBD'=>'090', 'SCR'=>'690', 'SDG'=>'938', 'SEK'=>'752', 'SGD'=>'702', 'SHP'=>'654', 'SLL'=>'694', 'SOS'=>'706', 'SRD'=>'968', 'STD'=>'678', 'SVC'=>'222', 'SYP'=>'760', 'SZL'=>'748', 'THB'=>'764', 'TJS'=>'972', 'TMT'=>'934', 'TND'=>'788', 'TOP'=>'776', 'TRY'=>'949', 'TTD'=>'780', 'TWD'=>'901', 'TZS'=>'834', 'UAH'=>'980', 'UGX'=>'800', 'USD'=>'840', 'USN'=>'997', 'USS'=>'998', 'UYI'=>'940', 'UYU'=>'858', 'UZS'=>'860', 'VEF'=>'937', 'VND'=>'704', 'VUV'=>'548', 'WST'=>'882', 'XAF'=>'950', 'XAG'=>'961', 'XAU'=>'959', 'XBA'=>'955', 'XBB'=>'956', 'XBC'=>'957', 'XBD'=>'958', 'XCD'=>'951', 'XDR'=>'960', 'XFU'=>'Nil', 'XOF'=>'952', 'XPD'=>'964', 'XPF'=>'953', 'XPT'=>'962', 'XSU'=>'994', 'XTS'=>'963', 'XUA'=>'965', 'XXX'=>'999', 'YER'=>'886', 'ZAR'=>'710', 'ZMK'=>'894', 'ZWL'=>'932');
		return apply_filters('shopp_currency_codes', $_);
	}

	/**
	 * Filter hook placeholder for contextually adding system locales
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of localities
	 **/
	public static function localities () {
		return apply_filters('shopp_localities',array());
	}

	public static function customer_types () {
		$_ = array(
			__('Retail','Shopp'),
			__('Guest','Shopp'),
			__('Wholesale','Shopp'),
			__('Referral','Shopp'),
			__('Tax-Exempt','Shopp')
		);
		return apply_filters('shopp_customer_types',$_);
	}


	/**
	 * Provides a list of supported payment cards
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of payment cards
	 **/
	public static function paycards () {
		$_ = array();
		$_['amex'] = new PayCard('American Express','Amex','/^3[47]\d{13}$/',4);
		$_['dc'] = new PayCard("Diner's Club",'DC','/^(30|36|38|39|54)\d{12}$/',3);
		$_['disc'] = new PayCard("Discover Card",'Disc','/^6(011|22[0-9]|4[4-9]0|5[0-9][0-9])\d{12}$/',3);
		$_['jcb'] = new PayCard('JCB','JCB','/^35(2[8-9]|[3-8][0-9])\d{12}$/',3);
		$_['dankort'] = new PayCard('Dankort','DK','/^5019\d{12}$/');
		$_['maes'] = new PayCard('Maestro','Maes','/^(5[06-8]|6\d)\d{10,17}$/',3, array('start'=>5,'issue'=>3));
		$_['mc'] = new PayCard('MasterCard','MC','/^(5[1-5]\d{4}|677189)\d{10}$/',3);
		$_['forbrugsforeningen'] = new PayCard('Forbrugsforeningen','forbrug','/^600722\d{10}$/');
		$_['lasr'] = new PayCard('Laser','Lasr','/^(6304|6706|6709|6771)\d{12,15}$/');
		$_['solo'] = new PayCard('Solo','Solo','/^(6334|6767)(\d{12}|\d{14,15})$/',3, array('start'=>5,'issue'=>3));
		$_['visa'] = new PayCard('Visa','Visa','/^4[0-9]{12}(?:[0-9]{3})?$/',3);
		return apply_filters('shopp_payment_cards',$_);
	}

	/**
	 * Provides a list of supported shipping packaging methods
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return array List of packaging methods
	 **/
	public static function packaging_types() {
		$_ = array(
			'like' => __('Only like items together', 'Shopp'),
			'piece' => __('Each piece separately', 'Shopp'),
			'all' => __('All together', 'Shopp'),
			'mass' => __('By total weight', 'Shopp'),
		);
		return apply_filters('shopp_packaging_types', $_);
	}

	/**
	 * Provides a list of settings for registered shipping carriers
	 *
	 * @since 1.2
	 *
	 * @return array List of shipping carrier settings
	 **/
	public static function shipcarriers () {
		$_ = array();

		// Postal carriers
		$_['usps'] = new ShippingCarrier(Shopp::__('US Postal Service'), 'http://usps.com/', 'US', 'https://tools.usps.com/go/TrackConfirmAction.action?tLabels=%s',
		'/^(91\d{18}|91\d{20})$/');
		$_['auspost'] = new ShippingCarrier(Shopp::__('Australia Post'), 'http://auspost.com.au/', 'AU', 'http://auspost.com.au/track/track.html?trackIds=%s', '/^(Z|[A-Z]{2}[A-Z0-9]{9}[A-Z]{2})/');
		$_['capost'] = new ShippingCarrier(Shopp::__('Canada Post'), 'http://canadapost.ca/', 'CA', 'http://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=%s', '/^(\d{16}|[A-Z]{2}[A-Z0-9]{9}[A-Z]{2})/');
		$_['china-post'] = new ShippingCarrier(Shopp::__('China Air Post'), 'http://183.com.cn/', 'CN');
		$_['ems-china'] = new ShippingCarrier(Shopp::__('EMS China'), 'http://www.ems.com.cn/', 'CN'); // EEXXXXXXXXXHK??
		$_['hongkong-post'] = new ShippingCarrier(Shopp::__('Hong Kong Post'), 'http://hongkongpost.hk', 'HK');
		$_['india-post'] = new ShippingCarrier(Shopp::__('India Post'), 'http://www.indiapost.gov.in/', 'IN');
		$_['japan-post'] = new ShippingCarrier(Shopp::__('Japan Post'), 'http://www.post.japanpost.jp/', 'JP');
		$_['parcelforce'] = new ShippingCarrier(Shopp::__('Parcelforce'), 'http://parcelforce.com/', 'UK');
		$_['post-danmark'] = new ShippingCarrier(Shopp::__('Post Danmark'), 'http://www.postdanmark.dk/', 'DK');
		$_['posten-norway'] = new ShippingCarrier(Shopp::__('Posten Norway'), 'http://www.posten.no/', 'NO');
		$_['posten-sweden'] = new ShippingCarrier(Shopp::__('Posten Sweden'), 'http://www.posten.se/', 'SE', 'http://www.posten.se/sv/Kundservice/Sidor/Sok-brev-paket.aspx?search=%s');
		$_['purolator'] = new ShippingCarrier(Shopp::__('Purolator'), 'http://purolator.com/', 'CA', 'http://shipnow.purolator.com/shiponline/track/purolatortrack.asp?pinno=%s');
		$_['russian-post'] = new ShippingCarrier(Shopp::__('Russian Post'), 'http://www.russianpost.ru/', 'RU', 'http://www.russianpost.ru/rp/servise/ru/home/postuslug/trackingpo');
		$_['thailand-post'] = new ShippingCarrier(Shopp::__('Thailand Post'), 'http://www.thailandpost.com/', 'TH');
		$_['nz-post'] = new ShippingCarrier(Shopp::__('New Zealand Post'), 'http://www.nzpost.co.nz/', 'NZ', 'http://www.nzpost.co.nz/tools/tracking-new?trackid=%s', '/[A-Z]{2}\d{9}[A-Z]{2}/i');

		// Global carriers - don't translate global carrier brand names
		$_['ups'] = new ShippingCarrier('UPS', 'http://ups.com/', '*', 'http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=%s', '/^(1Z[0-9A-Z]{16}|[\dT]\d{10})$/');
		$_['fedex'] = new ShippingCarrier('FedEx', 'http://fedex.com/', '*', 'http://www.fedex.com/Tracking?tracknumbers=%s', '/^(\d{12}|\d{15}|96\d{20}|96\d{17}|96\d{13}|96\d{10})$/');
		$_['aramex'] = new ShippingCarrier('Aramex', 'http://aramex.com/', '*', 'http://www.aramex.com/express/track_results_multiple.aspx?ShipmentNumber=%s', '/\d{10}/');
		$_['dhl'] = new ShippingCarrier('DHL', 'http://www.dhl.com/', '*', 'http://track.dhl-usa.com/TrackByNbr.asp?ShipmentNumber=%s', '/^([A-Z]{3}\d{7}|[A-Z]{5}\d{7})/');
		$_['tnt'] = new ShippingCarrier('TNT', 'http://tnt.com/', '*', 'http://parcels-row.tntpost.com/mytrackandtrace/trackandtrace.aspx?lang=en&B=%s', '/^([A-Z]{2}\d{9}[A-Z]{2}|\d{9})$/');

		return apply_filters('shopp_shipping_carriers', $_);
	}

	/**
	 * Gets a specified payment card
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return object PayCard object
	 **/
	public static function paycard ($card) {
		$cards = Lookup::paycards();
		if (isset($cards[strtolower($card)])) return $cards[strtolower($card)];
		return false;
	}

	/**
	 * A list of translatable payment status labels
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of payment status labels
	 **/
	public static function payment_status_labels () {
		$_ = array(
			'PENDING'  => Shopp::__('Pending'),
			'CHARGED'  => Shopp::__('Charged'),
			'REFUNDED' => Shopp::__('Refunded'),
			'VOID'     => Shopp::__('Void')
		);
		return apply_filters('shopp_payment_status_labels', $_);
	}

	public static function txnstatus_labels () {
		$_ = array(
			'review'      => Shopp::__('Review'),
			'purchase'    => Shopp::__('Purchase Order'),
			'invoiced'    => Shopp::__('Invoiced'),
			'authed'      => Shopp::__('Authorized'),
			'auth-failed' => Shopp::__('Declined'),
			'captured'    => Shopp::__('Paid'),
			'shipped'     => Shopp::__('Shipped'),
			'refunded'    => Shopp::__('Refunded'),
			'voided'      => Shopp::__('Void'),
			'closed'      => Shopp::__('Closed')
		);
		return apply_filters('shopp_txnstatus_labels', $_);
	}

	/**
	 * A list of stop words to be excluded from search indexes
	 *
	 * Stop words are commonly used words that are not particularly
	 * useful for searching because they would provide too much
	 * noise (irrelevant hits) in the results
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of stop words
	 **/
	public static function stopwords () {
		$_ = array(
	  	    'a', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by',
		    'for', 'if', 'in', 'into', 'is', 'it',
		    'no', 'not', 'of', 'on', 'or', 'such',
		    'that', 'the', 'their', 'then', 'there', 'these',
		    'they', 'this', 'to', 'was', 'will', 'with'
		);
		return apply_filters('shopp_index_stopwords',$_);
	}

	/**
	 * Provides index factor settings to use when building indexes
	 *
	 * Index factoring provides a configurable set of relevancy weights
	 * that are factored into the scoring of search results. Factors are
	 * in percentages, thus a factor of 50 gives the index half the
	 * relevancy of a normal index. Searching on an index with a factor
	 * of 200 doubles the relevancy of hits on matches in that index.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of index factor settings
	 **/
	public static function index_factors () {
		$_ = array(
			'name' => 200,
			'prices' => 160,
			'specs' => 75,
			'summary' => 100,
			'description' => 100,
			'categories' => 50,
			'tags' => 50
		);
		return apply_filters('shopp_index_factors',$_);
	}

	/**
	 * Returns the key binding format
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param boolean $m (optional) True to mask the format string
	 * @return string The format for key binding
	 **/
	public static function keyformat ( $m=false ) {
		$f = array(0x69,0x73,0x2f,0x48,0x34,0x30,0x6b);
		if (true === $m) $f = array_diff($f,array(0x73,0x2f,0x6b));
		return join('',array_map('chr',$f));
	}

	/**
	 * Generates a menu of order processing time frames
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array List of options
	 **/
	public static function timeframes_menu () {
		$units = array( 'd' => 11, 'w' => 7, 'm' => 4 );
		$_ = array();
		$min = 0;

		foreach ( $units as $u => $count ) {
			for ( $i = $min; $i < $count; $i++ ) {
				switch ($u) {
					case 'd': $_[$i.$u] = sprintf(_n('%d day','%d days',$i,'Shopp'), $i); break;
					case 'w': $_[$i.$u] = sprintf(_n('%d week','%d weeks',$i,'Shopp'), $i); break;
					case 'm': $_[$i.$u] = sprintf(_n('%d month','%d months',$i,'Shopp'), $i); break;
					break;
				}
			}
			$min = (0 === $min) ? ++$min : $min; // Increase the min number of units to one after the first loop (allow 0 days but not 0 weeks)
		}

		return apply_filters('shopp_timeframes_menu',$_);
	}

	/**
	 * Predefined error messages for common occurrences including common PHP-generated error codes
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $type Table reference type from the error array
	 * @param mixed $code
	 * @return string Error message
	 **/
	public static function errors ( $type, $code ) {

		$_ = array();
		$_['contact'] = array(
			'shopp-support' => __('For help with this, contact the Shopp %ssupport team%s.','Shopp'),
			'shopp-cs' => __('For help with this, contact Shopp %scustomer service%s.','Shopp'),
			'server-manager' => __('For help with this, contact your web hosting provider or server administrator.','Shopp'),
			'webmaster' => __('For help with this, contact your website developer.','Shopp'),
			'admin' => __('For help with this, contact the website administrator.','Shopp'),
		);

		/* PHP file upload errors */
		$_['uploads'] = array(
			UPLOAD_ERR_INI_SIZE => sprintf(
				__('The uploaded file is too big for the server.%s','Shopp'),
					sprintf(' '.__('Files must be less than %s.','Shopp')." {$_['contact']['server-manager']}",
					Shopp::ini_size('upload_max_filesize'))
			),
			UPLOAD_ERR_FORM_SIZE => sprintf(__('The uploaded file is too big.%s','Shopp'),
				isset($_POST['MAX_FILE_SIZE']) ? sprintf(' '.__('Files must be less than %s. Please try again with a smaller file.','Shopp'),readableFileSize($_POST['MAX_FILE_SIZE'])) : ''
			),
			UPLOAD_ERR_PARTIAL => __('The file upload did not complete correctly.','Shopp'),
			UPLOAD_ERR_NO_FILE => __('No file was uploaded.','Shopp'),
			UPLOAD_ERR_NO_TMP_DIR => __('The server is missing the necessary temporary folder.','Shopp')." {$_['contact']['server-manager']}",
			UPLOAD_ERR_CANT_WRITE => __('The file could not be saved to the server.%s','Shopp')." {$_['contact']['server-manager']}",
			UPLOAD_ERR_EXTENSION => __('The file upload was stopped by a server extension.','Shopp')." {$_['contact']['server-manager']}"
		);

		/* File upload security verification errors */
		$_['uploadsecurity'] = array(
			'is_uploaded_file' => __('The file specified is not a valid upload and is out of bounds. Nice try though!','Shopp'),
			'is_readable' => __('The uploaded file cannot be read by the web server and is unusable.','Shopp')." {$_['contact']['server-manager']}",
			'is_empty' => __('The uploaded file is empty.','Shopp'),
			'filesize_mismatch' => __('The size of the uploaded file does not match the size reported by the client. Something fishy going on?','Shopp')
		);

		$callhome_fail = __('Could not connect to the shopplugin.net server.','Shopp');
		$_['callhome'] = array(
			'fail' => $callhome_fail,
			'noresponse' => __('No response was sent back by the shopplugin.net server.','Shopp')." {$_['contact']['admin']}",
			'http-unknown' => __('The connection to the shopplugin.net server failed due to an unknown error.','Shopp')." {$_['contact']['admin']}",
			'http-400' => $callhome_fail.__("The server couldn't understand the request.",'Shopp')." {$_['contact']['admin']} (HTTP 400)",
			'http-401' => $callhome_fail.__('The server requires login authentication and denied access.','Shopp')." {$_['contact']['admin']} (HTTP 401)",
			'http-403' => $callhome_fail.__('The server refused the connection.','Shopp')." {$_['contact']['admin']} (HTTP 403)",
			'http-404' => __('The requested resource does not exist on the shopplugin.net server.','Shopp')." {$_['contact']['admin']} (HTTP 404)",
			'http-500' => __('The shopplugin.net server experienced an error and could not handle the request.','Shopp')." {$_['contact']['admin']} (HTTP 500)",
			'http-501' => __('The shopplugin.net server does not support the method of the request.','Shopp')." {$_['contact']['admin']} (HTTP 501)",
			'http-502' => __('The shopplugin.net server is acting as a gateway and received an invalid response from the upstream server.','Shopp')." {$_['contact']['admin']} (HTTP 502)",
			'http-503' => __('The shopplugin.net server is temporarily unavailable due to a high volume of traffic.','Shopp')." {$_['contact']['admin']} (HTTP 503)",
			'http-504' => __('The connected shopplugin.net server is acting as a gateway and received a connection timeout from the upstream server.','Shopp')." {$_['contact']['admin']} (HTTP 504)",
			'http-505' => __("The shopplugin.net server doesn't support the connection protocol version used in the request.",'Shopp')." {$_['contact']['admin']} (HTTP 505)"
		);

		$gateway_fail = __('Could not connect to the payment server.','Shopp');
		$_['gateway'] = array(
			'nogateways' => __('No payment system has been setup for the storefront.','Shopp')." {$_['contact']['admin']}",
			'fail' => $gateway_fail,
			'noresponse' => __('No response was sent back by the payment server.','Shopp')." {$_['contact']['admin']}",
			'http-unknown' => __('The connection to the payment server failed due to an unknown error.','Shopp')." {$_['contact']['admin']}",
			'http-400' => $gateway_fail.__("The server couldn't understand the request.",'Shopp')." {$_['contact']['admin']} (HTTP 400)",
			'http-401' => $gateway_fail.__('The server requires login authentication and denied access.','Shopp')." {$_['contact']['admin']} (HTTP 401)",
			'http-403' => $gateway_fail.__('The server refused the connection.','Shopp')." {$_['contact']['admin']} (HTTP 403)",
			'http-404' => __('The requested resource does not exist on the payment server.','Shopp')." {$_['contact']['admin']} (HTTP 404)",
			'http-500' => __('The payment server experienced an error and could not handle the request.','Shopp')." {$_['contact']['admin']} (HTTP 500)",
			'http-501' => __('The payment server does not support the method of the request.','Shopp')." {$_['contact']['admin']} (HTTP 501)",
			'http-502' => __('The connected payment server is acting as a gateway and received an invalid response from the upstream server.','Shopp')." {$_['contact']['admin']} (HTTP 502)",
			'http-503' => __('The payment server is temporarily unavailable due to a high volume of traffic.','Shopp')." {$_['contact']['admin']} (HTTP 503)",
			'http-504' => __('The connected payment server is acting as a gateway and received a connection timeout from the upstream server.','Shopp')." {$_['contact']['admin']} (HTTP 504)",
			'http-505' => __("The payment server doesn't support the connection protocol version used in the request.",'Shopp')." {$_['contact']['admin']} (HTTP 505)",
		);

		$shipping_fail = __('Could not connect to the shipping rates server.','Shopp');
		$_['shipping'] = array(
			'fail' => $shipping_fail,
			'noresponse' => __('No response was sent back by the shipping rates server.','Shopp')." {$_['contact']['admin']}",
			'http-unknown' => __('The connection to the shipping rates server failed due to an unknown error.','Shopp')." {$_['contact']['admin']}",
			'http-400' => $shipping_fail.__("The server couldn't understand the request.",'Shopp')." {$_['contact']['admin']} (HTTP 400)",
			'http-401' => $shipping_fail.__('The server requires login authentication and denied access.','Shopp')." {$_['contact']['admin']} (HTTP 401)",
			'http-403' => $shipping_fail.__('The server refused the connection.','Shopp')." {$_['contact']['admin']} (HTTP 403)",
			'http-404' => __('The requested resource does not exist on the shipping rates server.','Shopp')." {$_['contact']['admin']} (HTTP 404)",
			'http-500' => __('The shipping rates server experienced an error and could not handle the request.','Shopp')." {$_['contact']['admin']} (HTTP 500)",
			'http-501' => __('The shipping rates server does not support the method of the request.','Shopp')." {$_['contact']['admin']} (HTTP 501)",
			'http-502' => __('The connected shipping rates server is acting as a gateway and received an invalid response from the upstream server.','Shopp')." {$_['contact']['admin']} (HTTP 502)",
			'http-503' => __('The shipping rates server is temporarily unavailable due to a high volume of traffic.','Shopp')." {$_['contact']['admin']} (HTTP 503)",
			'http-504' => __('The connected shipping rates server is acting as a gateway and received a connection timeout from the upstream server.','Shopp')." {$_['contact']['admin']} (HTTP 504)",
			'http-505' => __("The shipping rates server doesn't support the connection protocol version used in the request.",'Shopp')." {$_['contact']['admin']} (HTTP 505)",
		);

		if (isset($_[$type]) && isset($_[$type][$code])) return $_[$type][$code];

		return false;
	}

	private static function locale ( $file ) {
		$path = join('/', array(SHOPP_PATH, 'locales', $file));
		if ( is_readable($path) )
			return $path;
	}

}

if ( ! class_exists('Lookup', false) ) {
	class Lookup extends ShoppLookup {}
}

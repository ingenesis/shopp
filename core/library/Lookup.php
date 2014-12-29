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
class Lookup {

	/**
	 * Provides a lookup table worldwide regions
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of regions
	 **/
	public static function regions () {
		$_ = array();
		$_[0] = __('North America','Shopp');
		$_[1] = __('Central America','Shopp');
		$_[2] = __('South America','Shopp');
		$_[3] = __('Europe','Shopp');
		$_[4] = __('Middle East','Shopp');
		$_[5] = __('Africa','Shopp');
		$_[6] = __('Asia','Shopp');
		$_[7] = __('Oceania','Shopp');
		return apply_filters('shopp_regions', $_);
	}

	/**
	 * Finds the translated region name for a specific region index
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string The translated region name
	 **/
	public static function region ($id) {
		$r = Lookup::regions();
		return $r[$id];
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
		$_ = array();
		$_['CA'] = array('name'=>__('Canada','Shopp'),'currency'=>array('code'=>'CAD','format'=>'$#,###.##'),'units'=>'metric','region'=>0);
		$_['US'] = array('name'=>__('USA','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'imperial','region'=>0);

		// Specialized countries for US Armed Forces and US Territories
	  $_['USAF'] = array('name'=>__('US Armed Forces','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'imperial','region'=>0);
	  $_['USAT'] = array('name'=>__('US Territories','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'imperial','region'=>0);

		$_['GB'] = array('name'=>__('United Kingdom','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>3);
		$_['AF'] = array('name'=>__('Afghanistan','Shopp'),'currency'=>array('code'=>'AFN','format'=>'؋ #,###.##'),'units'=>'metric','region'=>6);
		$_['AX'] = array('name'=>__('Åland Islands','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['AL'] = array('name'=>__('Albania','Shopp'),'currency'=>array('code'=>'ALL','format'=>'Lek #,###.##'),'units'=>'metric','region'=>3);
		$_['DZ'] = array('name'=>__('Algeria','Shopp'),'currency'=>array('code'=>'DZD','format'=>'#,###.## د.ج'),'units'=>'metric','region'=>5);
		$_['AS'] = array('name'=>__('American Samoa','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['AD'] = array('name'=>__('Andorra','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#.###,##'),'units'=>'metric','region'=>3);
		$_['AO'] = array('name'=>__('Angola','Shopp'),'currency'=>array('code'=>'AOA','format'=>'# ###,## Kz'),'units'=>'metric','region'=>5);
		$_['AI'] = array('name'=>__('Anguilla','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>1);
		$_['AG'] = array('name'=>__('Antigua and Barbuda','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>1);
		$_['AR'] = array('name'=>__('Argentina','Shopp'),'currency'=>array('code'=>'ARS','format'=>'$#.###,##'),'units'=>'metric','region'=>2);
		$_['AM'] = array('name'=>__('Armenia','Shopp'),'currency'=>array('code'=>'AMD','format'=>'####,## Դրամ'),'units'=>'metric','region'=>6);
		$_['AW'] = array('name'=>__('Aruba','Shopp'),'currency'=>array('code'=>'AWG','format'=>'ƒ#,###.##'),'units'=>'metric','region'=>2);
		$_['AU'] = array('name'=>__('Australia','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['AT'] = array('name'=>__('Austria','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['AZ'] = array('name'=>__('Azerbaijan','Shopp'),'currency'=>array('code'=>'AZN','format'=>'man. #.###,##'),'units'=>'metric','region'=>6);
		$_['BD'] = array('name'=>__('Bangladesh','Shopp'),'currency'=>array('code'=>'BDT','format'=>'#,###.##৳'),'units'=>'metric','region'=>6);
		$_['BB'] = array('name'=>__('Barbados','Shopp'),'currency'=>array('code'=>'BBD','format'=>'$#,###.##'),'units'=>'metric','region'=>0);
		$_['BS'] = array('name'=>__('Bahamas','Shopp'),'currency'=>array('code'=>'BSD','format'=>'$#,###.##'),'units'=>'metric','region'=>0);
		$_['BH'] = array('name'=>__('Bahrain','Shopp'),'currency'=>array('code'=>'BHD','format'=>'ب.د #,###.##'),'units'=>'metric','region'=>0);
		$_['BY'] = array('name'=>__('Belarus','Shopp'),'currency'=>array('code'=>'BYR','format'=>'BYR# ###,##'),'units'=>'metric','region'=>3);
		$_['BE'] = array('name'=>__('Belgium','Shopp'),'currency'=>array('code'=>'EUR','format'=>'#.###,## €'),'units'=>'metric','region'=>3);
		$_['BZ'] = array('name'=>__('Belize','Shopp'),'currency'=>array('code'=>'BZD','format'=>'$#,###.##'),'units'=>'metric','region'=>1);
		$_['BJ'] = array('name'=>__('Benin','Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['BM'] = array('name'=>__('Bermuda','Shopp'),'currency'=>array('code'=>'BMD','format'=>'BD$#,###.##'),'units'=>'metric','region'=>0);
		$_['BT'] = array('name'=>__('Bhutan','Shopp'),'currency'=>array('code'=>'BTN','format'=>'Nu. #,###.##'),'units'=>'metric','region'=>6);
		$_['BO'] = array('name'=>__('Bolivia','Shopp'),'currency'=>array('code'=>'BOB','format'=>'Bs #.###,##'),'units'=>'metric','region'=>2);
		$_['BA'] = array('name'=>__('Bosnia and Herzegovina','Shopp'),'currency'=>array('code'=>'BAM','format'=>'KM #.###,##'),'units'=>'metric','region'=>3);
		$_['BW'] = array('name'=>__('Botswana','Shopp'),'currency'=>array('code'=>'BWP','format'=>'P#,###.##'),'units'=>'metric','region'=>5);
		$_['BR'] = array('name'=>__('Brazil','Shopp'),'currency'=>array('code'=>'BRL','format'=>'R$#.###,##'),'units'=>'metric','region'=>2);
		$_['IO'] = array('name'=>__('British Indian Ocean Territory','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>7);
		$_['VG'] = array('name'=>__('British Virgin Islands','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>1);
		$_['BN'] = array('name'=>__('Brunei Darussalam','Shopp'),'currency'=>array('code'=>'BND','format'=>'$ #.###,##'),'units'=>'metric','region'=>6);
		$_['BG'] = array('name'=>__('Bulgaria','Shopp'),'currency'=>array('code'=>'BGN','format'=>'# ###,## лв.'),'units'=>'metric','region'=>3);
		$_['BF'] = array('name'=>__('Burkina Faso','Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['MM'] = array('name'=>__('Burma','Shopp'),'currency'=>array('code'=>'MMK','format'=>'K #,###.##'),'units'=>'metric','region'=>6);
		$_['BI'] = array('name'=>__('Burundi','Shopp'),'currency'=>array('code'=>'BIF','format'=>'# ###,## FBu'),'units'=>'metric','region'=>5);
		$_['KH'] = array('name'=>__('Cambodia','Shopp'),'currency'=>array('code'=>'KHR','format'=>'#.###,##៛'),'units'=>'metric','region'=>6);
		$_['CM'] = array('name'=>__('Cameroon','Shopp'),'currency'=>array('code'=>'XAF','format'=>'# ### FCFA'),'units'=>'metric','region'=>5);
		$_['CV'] = array('name'=>__('Cape Verde','Shopp'),'currency'=>array('code'=>'CVE','format'=>'CV$#.###,##'),'units'=>'metric','region'=>5);
		$_['KY'] = array('name'=>__('Cayman Islands','Shopp'),'currency'=>array('code'=>'KYD','format'=>'CI$#,###.##'),'units'=>'metric','region'=>1);
		$_['CF'] = array('name'=>__('Central African Republic','Shopp'),'currency'=>array('code'=>'XAF','format'=>'# ### FCFA'),'units'=>'metric','region'=>5);
		$_['TD'] = array('name'=>__('Chad','Shopp'),'currency'=>array('code'=>'XAF','format'=>'# ### FCFA'),'units'=>'metric','region'=>5);
		$_['CL'] = array('name'=>__('Chile','Shopp'),'currency'=>array('code'=>'CLP','format'=>'$#.###,##'),'units'=>'metric','region'=>2);
		$_['CN'] = array('name'=>__('China','Shopp'),'currency'=>array('code'=>'CNY','format'=>'¥#,###.##'),'units'=>'metric','region'=>6);
		$_['CX'] = array('name'=>__('Christmas Island','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['CC'] = array('name'=>__('Cocos (Keeling) Islands','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['CO'] = array('name'=>__('Colombia','Shopp'),'currency'=>array('code'=>'COP','format'=>'$#.###,##'),'units'=>'metric','region'=>2);
		$_['KM'] = array('name'=>__('Comoros','Shopp'),'currency'=>array('code'=>'KMF','format'=>'# ### FC'),'units'=>'metric','region'=>5);
		$_['CG'] = array('name'=>__('Congo-Brazzaville','Shopp'),'currency'=>array('code'=>'XAF','format'=>'# ### FCFA'),'units'=>'metric','region'=>5);
		$_['CD'] = array('name'=>__('Congo-Kinshasa','Shopp'),'currency'=>array('code'=>'CDF','format'=>'# ###,## FrCD'),'units'=>'metric','region'=>5);
		$_['CK'] = array('name'=>__('Cook Islands','Shopp'),'currency'=>array('code'=>'NZD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['CR'] = array('name'=>__('Costa Rica','Shopp'),'currency'=>array('code'=>'CRC','format'=>'₡#.###,##'),'units'=>'metric','region'=>1);
		$_['CI'] = array('name'=>__("Côte d'Ivoire",'Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['HR'] = array('name'=>__('Croatia','Shopp'),'currency'=>array('code'=>'HRK','format'=>'#.###,## kn'),'units'=>'metric','region'=>3);
		$_['CU'] = array('name'=>__('Cuba','Shopp'),'currency'=>array('code'=>'CUP','format'=>'CUP#.###,##'),'units'=>'metric','region'=>1);
		$_['CW'] = array('name'=>__('Curaçao','Shopp'),'currency'=>array('code'=>'ANG','format'=>'ƒ#.###,##'),'units'=>'metric','region'=>0);
		$_['CY'] = array('name'=>__('Cyprus','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#.###,##'),'units'=>'metric','region'=>3);
		$_['CZ'] = array('name'=>__('Czech Republic','Shopp'),'currency'=>array('code'=>'CZK','format'=>'# ###,## Kč'),'units'=>'metric','region'=>3);
		$_['DK'] = array('name'=>__('Denmark','Shopp'),'currency'=>array('code'=>'DKK','format'=>'#.###,## kr'),'units'=>'metric','region'=>3);
		$_['DJ'] = array('name'=>__('Djibouti','Shopp'),'currency'=>array('code'=>'DJF','format'=>'# ### Fdj'),'units'=>'metric','region'=>5);
		$_['DM'] = array('name'=>__('Dominica','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>1);
		$_['DO'] = array('name'=>__('Dominican Republic','Shopp'),'currency'=>array('code'=>'DOP','format'=>'$#,###.##'),'units'=>'metric','region'=>1);
		$_['TL'] = array('name'=>__('East Timor','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['EC'] = array('name'=>__('Ecuador','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>2);
		$_['SV'] = array('name'=>__('El Salvador','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>1);
		$_['EG'] = array('name'=>__('Egypt','Shopp'),'currency'=>array('code'=>'EGP','format'=>'£#,###.##'),'units'=>'metric','region'=>5);
		$_['GQ'] = array('name'=>__('Equatorial Guinea','Shopp'),'currency'=>array('code'=>'XAF','format'=>'# ### FCFA'),'units'=>'metric','region'=>5);
		$_['ER'] = array('name'=>__('Eritrea','Shopp'),'currency'=>array('code'=>'ERN','format'=>'Nfk,###.##'),'units'=>'metric','region'=>5);
		$_['EE'] = array('name'=>__('Estonia','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['ET'] = array('name'=>__('Ethiopia','Shopp'),'currency'=>array('code'=>'ETB','format'=>'Br#,###.##'),'units'=>'metric','region'=>5);
		$_['FK'] = array('name'=>__('Falkland Islands','Shopp'),'currency'=>array('code'=>'FKP','format'=>'FK£#,###.##'),'units'=>'metric','region'=>2);
		$_['FO'] = array('name'=>__('Faroe Islands','Shopp'),'currency'=>array('code'=>'DKK','format'=>'kr#.###,##'),'units'=>'metric','region'=>3);
		$_['FM'] = array('name'=>__('Federated States of Micronesia','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['FJ'] = array('name'=>__('Fiji','Shopp'),'currency'=>array('code'=>'FJD','format'=>'FJ$#,###.##'),'units'=>'metric','region'=>7);
		$_['FI'] = array('name'=>__('Finland','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>3);
		$_['FR'] = array('name'=>__('France','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>3);
		$_['GF'] = array('name'=>__('French Guiana','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>2);
		$_['PF'] = array('name'=>__('French Polynesia','Shopp'),'currency'=>array('code'=>'XPF','format'=>'#,###.##F'),'units'=>'metric','region'=>7);
		$_['TF'] = array('name'=>__('French Southern Lands','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>7);
		$_['GA'] = array('name'=>__('Gabon','Shopp'),'currency'=>array('code'=>'XAF','format'=>'# ### FCFA'),'units'=>'metric','region'=>5);
		$_['GM'] = array('name'=>__('Gambia','Shopp'),'currency'=>array('code'=>'GMD','format'=>'GMD#,###.##'),'units'=>'metric','region'=>5);
		$_['GE'] = array('name'=>__('Georgia','Shopp'),'currency'=>array('code'=>'GEL','format'=>'GEL #.###,##'),'units'=>'metric','region'=>6);
		$_['DE'] = array('name'=>__('Germany','Shopp'),'currency'=>array('code'=>'EUR','format'=>'#,###.## €'),'units'=>'metric','region'=>3);
		$_['GH'] = array('name'=>__('Ghana','Shopp'),'currency'=>array('code'=>'GHS','format'=>'₵#,###.##'),'units'=>'metric','region'=>5);
		$_['GI'] = array('name'=>__('Gibraltar','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>3);
		$_['GR'] = array('name'=>__('Greece','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['GL'] = array('name'=>__('Greenland','Shopp'),'currency'=>array('code'=>'DKK','format'=>'kr#.###,##'),'units'=>'metric','region'=>1);
		$_['GD'] = array('name'=>__('Grenada','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>2);
		$_['GP'] = array('name'=>__('Guadeloupe','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>0);
		$_['GU'] = array('name'=>__('Guam','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['GT'] = array('name'=>__('Guatemala','Shopp'),'currency'=>array('code'=>'GTQ','format'=>'Q#,###.##'),'units'=>'metric','region'=>1);
		$_['GG'] = array('name'=>__('Guernsey','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>3);
		$_['GN'] = array('name'=>__('Guinea','Shopp'),'currency'=>array('code'=>'GNF','format'=>'# ### FG'),'units'=>'metric','region'=>5);
		$_['GW'] = array('name'=>__('Guinea-Bissau','Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['GY'] = array('name'=>__('Guyana','Shopp'),'currency'=>array('code'=>'GYD','format'=>'G$#,###.##'),'units'=>'metric','region'=>2);
		$_['HT'] = array('name'=>__('Haiti','Shopp'),'currency'=>array('code'=>'HTG','format'=>'# ###,## HTG'),'units'=>'metric','region'=>1);
		$_['HM'] = array('name'=>__('Heard and McDonald Islands','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['HN'] = array('name'=>__('Honduras','Shopp'),'currency'=>array('code'=>'HNL','format'=>'L #,###.##'),'units'=>'metric','region'=>1);
		$_['HK'] = array('name'=>__('Hong Kong','Shopp'),'currency'=>array('code'=>'HKD','format'=>'$#,###.##'),'units'=>'metric','region'=>6);
		$_['HU'] = array('name'=>__('Hungary','Shopp'),'currency'=>array('code'=>'HUF','format'=>'# ### ### Ft','decimals'=>','),'units'=>'metric','region'=>3);
		$_['IS'] = array('name'=>__('Iceland','Shopp'),'currency'=>array('code'=>'ISK','format'=>'#.###.###, kr'),'units'=>'metric','region'=>3);
		$_['IN'] = array('name'=>__('India','Shopp'),'currency'=>array('code'=>'INR','format'=>'₨#,##,###.##'),'units'=>'metric','region'=>6);
		$_['ID'] = array('name'=>__('Indonesia','Shopp'),'currency'=>array('code'=>'IDR','format'=>'Rp #.###,##'),'units'=>'metric','region'=>7);
		$_['IR'] = array('name'=>__('Iran','Shopp'),'currency'=>array('code'=>'IRR','format'=>'#.###,##﷼'),'units'=>'metric','region'=>4);
		$_['IQ'] = array('name'=>__('Iraq','Shopp'),'currency'=>array('code'=>'IQD','format'=>'#.###,##د.ع'),'units'=>'metric','region'=>4);
		$_['IE'] = array('name'=>__('Ireland','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['IM'] = array('name'=>__('Isle of Man','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>3);
		$_['IL'] = array('name'=>__('Israel','Shopp'),'currency'=>array('code'=>'ILS','format'=>'#,###.## ₪'),'units'=>'metric','region'=>4);
		$_['IT'] = array('name'=>__('Italy','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['JM'] = array('name'=>__('Jamaica','Shopp'),'currency'=>array('code'=>'JMD','format'=>'$#,###.##'),'units'=>'metric','region'=>0);
		$_['JP'] = array('name'=>__('Japan','Shopp'),'currency'=>array('code'=>'JPY','format'=>'¥#,###,###.'),'units'=>'metric','region'=>6);
		$_['JE'] = array('name'=>__('Jersey','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>3);
		$_['JO'] = array('name'=>__('Jordan','Shopp'),'currency'=>array('code'=>'JOD','format'=>'#.###,## JD'),'units'=>'metric','region'=>4);
		$_['KZ'] = array('name'=>__('Kazakhstan','Shopp'),'currency'=>array('code'=>'KZT','format'=>'# ###,## 〒'),'units'=>'metric','region'=>6);
		$_['KE'] = array('name'=>__('Kenya','Shopp'),'currency'=>array('code'=>'KES','format'=>'Ksh#,###.##'),'units'=>'metric','region'=>5);
		$_['KI'] = array('name'=>__('Kiribati','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['KW'] = array('name'=>__('Kuwait','Shopp'),'currency'=>array('code'=>'KWD','format'=>'#.###,## د.ك'),'units'=>'metric','region'=>4);
		$_['KG'] = array('name'=>__('Kyrgyzstan','Shopp'),'currency'=>array('code'=>'KGS','format'=>'# ###,## som'),'units'=>'metric','region'=>6);
		$_['LA'] = array('name'=>__('Laos','Shopp'),'currency'=>array('code'=>'LAK','format'=>'#,###.## ₭'),'units'=>'metric','region'=>6);
		$_['LV'] = array('name'=>__('Latvia','Shopp'),'currency'=>array('code'=>'LVL','format'=>'# ###.## Ls'),'units'=>'metric','region'=>3);
		$_['LB'] = array('name'=>__('Lebanon','Shopp'),'currency'=>array('code'=>'LBP','format'=>'#.### ل.ل'),'units'=>'metric','region'=>4);
		$_['LS'] = array('name'=>__('Lesotho','Shopp'),'currency'=>array('code'=>'LSL','format'=>'M# ###,##'),'units'=>'metric','region'=>5);
		$_['LR'] = array('name'=>__('Liberia','Shopp'),'currency'=>array('code'=>'LRD','format'=>'LD$#,###.##'),'units'=>'metric','region'=>5);
		$_['LY'] = array('name'=>__('Libya','Shopp'),'currency'=>array('code'=>'LYD','format'=>'#.###,## ل.د'),'units'=>'metric','region'=>5);
		$_['LI'] = array('name'=>__('Liechtenstein','Shopp'),'currency'=>array('code'=>'CHF','format'=>"CHF #'###.##"),'units'=>'metric','region'=>3);
		$_['LT'] = array('name'=>__('Lithuania','Shopp'),'currency'=>array('code'=>'LTL','format'=>'#.###,## Lt'),'units'=>'metric','region'=>3);
		$_['LU'] = array('name'=>__('Luxembourg','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['MO'] = array('name'=>__('Macau','Shopp'),'currency'=>array('code'=>'MOP','format'=>'MOP$#,###.##'),'units'=>'metric','region'=>6);
		$_['MK'] = array('name'=>__('Macedonia','Shopp'),'currency'=>array('code'=>'MKD','format'=>'MKD #.###,##'),'units'=>'metric','region'=>3);
		$_['MG'] = array('name'=>__('Madagascar','Shopp'),'currency'=>array('code'=>'MGA','format'=>'# ### MGA'),'units'=>'metric','region'=>5);
		$_['MW'] = array('name'=>__('Malawi','Shopp'),'currency'=>array('code'=>'MWK','format'=>'MK #,###.##'),'units'=>'metric','region'=>5);
		$_['MY'] = array('name'=>__('Malaysia','Shopp'),'currency'=>array('code'=>'MYR','format'=>'RM#,###.##'),'units'=>'metric','region'=>6);
		$_['MV'] = array('name'=>__('Maldives','Shopp'),'currency'=>array('code'=>'MVR','format'=>'Rf#,###.##'),'units'=>'metric','region'=>6);
		$_['ML'] = array('name'=>__('Mali','Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['MT'] = array('name'=>__('Malta','Shopp'),'currency'=>array('code'=>'MTL','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['MH'] = array('name'=>__('Marshall Islands','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['MQ'] = array('name'=>__('Martinique','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>1);
		$_['MR'] = array('name'=>__('Mauritania','Shopp'),'currency'=>array('code'=>'MRO','format'=>'#,###.## UM'),'units'=>'metric','region'=>5);
		$_['MU'] = array('name'=>__('Mauritius','Shopp'),'currency'=>array('code'=>'MUR','format'=>'MU₨#,###'),'units'=>'metric','region'=>5);
		$_['YT'] = array('name'=>__('Mayotte','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>5);
		$_['MX'] = array('name'=>__('Mexico','Shopp'),'currency'=>array('code'=>'MXN','format'=>'$#,###.##'),'units'=>'metric','region'=>0);
		$_['MD'] = array('name'=>__('Moldova','Shopp'),'currency'=>array('code'=>'MDL','format'=>'#.###,## MDL'),'units'=>'metric','region'=>3);
		$_['MC'] = array('name'=>__('Monaco','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>3);
		$_['MN'] = array('name'=>__('Mongolia','Shopp'),'currency'=>array('code'=>'MNT','format'=>'# ###,##₮'),'units'=>'metric','region'=>6);
		$_['ME'] = array('name'=>__('Montenegro','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€ #,###.##'),'units'=>'metric','region'=>3);
		$_['MS'] = array('name'=>__('Montserrat','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>1);
		$_['MA'] = array('name'=>__('Morocco','Shopp'),'currency'=>array('code'=>'MAD','format'=>'#.###,## د.م.'),'units'=>'metric','region'=>5);
		$_['MZ'] = array('name'=>__('Mozambique','Shopp'),'currency'=>array('code'=>'MZN','format'=>'MTn#.###,##'),'units'=>'metric','region'=>5);
		$_['NA'] = array('name'=>__('Namibia','Shopp'),'currency'=>array('code'=>'NAD','format'=>'$#,###.##'),'units'=>'metric','region'=>5);
		$_['NR'] = array('name'=>__('Nauru','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['NP'] = array('name'=>__('Nepal','Shopp'),'currency'=>array('code'=>'NPR','format'=>'रू. #,###.##'),'units'=>'metric','region'=>6);
		$_['NL'] = array('name'=>__('Netherlands','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#.###,##'),'units'=>'metric','region'=>3);
		$_['NC'] = array('name'=>__('New Caledonia','Shopp'),'currency'=>array('code'=>'XPF','format'=>'#,###.##F'),'units'=>'metric','region'=>7);
		$_['NZ'] = array('name'=>__('New Zealand','Shopp'),'currency'=>array('code'=>'NZD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['NI'] = array('name'=>__('Nicaragua','Shopp'),'currency'=>array('code'=>'NIO','format'=>'C$ #,###.##'),'units'=>'metric','region'=>1);
		$_['NE'] = array('name'=>__('Niger','Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['NG'] = array('name'=>__('Nigeria','Shopp'),'currency'=>array('code'=>'NGN','format'=>'₦#,###.##'),'units'=>'metric','region'=>5);
		$_['NU'] = array('name'=>__('Niue','Shopp'),'currency'=>array('code'=>'NZD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['NF'] = array('name'=>__('Norfolk Island','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['MP'] = array('name'=>__('Northern Mariana Islands','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['NO'] = array('name'=>__('Norway','Shopp'),'currency'=>array('code'=>'NOK','format'=>'kr # ###,##'),'units'=>'metric','region'=>3);
		$_['OM'] = array('name'=>__('Oman','Shopp'),'currency'=>array('code'=>'OMR','format'=>'#.###,## ر.ع'),'units'=>'metric','region'=>4);
		$_['PK'] = array('name'=>__('Pakistan','Shopp'),'currency'=>array('code'=>'PKR','format'=>'₨#,###.##'),'units'=>'metric','region'=>4);
		$_['PW'] = array('name'=>__('Palau','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['PA'] = array('name'=>__('Panama','Shopp'),'currency'=>array('code'=>'USD','format'=>'$ #,###.##'),'units'=>'metric','region'=>1);
		$_['PG'] = array('name'=>__('Papua New Guinea','Shopp'),'currency'=>array('code'=>'PGK','format'=>'K#,###.##'),'units'=>'metric','region'=>7);
		$_['PY'] = array('name'=>__('Paraguay','Shopp'),'currency'=>array('code'=>'PYG','format'=>'₲#.###'),'units'=>'metric','region'=>2);
		$_['PE'] = array('name'=>__('Peru','Shopp'),'currency'=>array('code'=>'PEN','format'=>'S/. #,###.##'),'units'=>'metric','region'=>2);
		$_['PH'] = array('name'=>__('Philippines','Shopp'),'currency'=>array('code'=>'PHP','format'=>'Php #,###.##'),'units'=>'metric','region'=>6);
		$_['PN'] = array('name'=>__('Pitcairn Islands','Shopp'),'currency'=>array('code'=>'NZD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['PL'] = array('name'=>__('Poland','Shopp'),'currency'=>array('code'=>'PLN','format'=>'#.###,## zł'),'units'=>'metric','region'=>3);
		$_['PT'] = array('name'=>__('Portugal','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['PR'] = array('name'=>__('Puerto Rico','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'imperial','region'=>0);
		$_['QA'] = array('name'=>__('Qatar','Shopp'),'currency'=>array('code'=>'QAR','format'=>'####,## ر.ق'),'units'=>'metric','region'=>4);
		$_['RE'] = array('name'=>__('Réunion','Shopp'),'currency'=>array('code'=>'','format'=>'# ###,## €'),'units'=>'metric','region'=>5);
		$_['RO'] = array('name'=>__('Romania','Shopp'),'currency'=>array('code'=>'ROL','format'=>'#.###,## lei'),'units'=>'metric','region'=>3);
		$_['RU'] = array('name'=>__('Russia','Shopp'),'currency'=>array('code'=>'RUB','format'=>'# ###,## руб'),'units'=>'metric','region'=>6);
		$_['RW'] = array('name'=>__('Rwanda','Shopp'),'currency'=>array('code'=>'RWF','format'=>'RF #.###'),'units'=>'metric','region'=>5);
		$_['BL'] = array('name'=>__('Saint Barthélemy','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>1);
		$_['SH'] = array('name'=>__('Saint Helena','Shopp'),'currency'=>array('code'=>'SHP','format'=>'£#,###.##'),'units'=>'metric','region'=>5);
		$_['KN'] = array('name'=>__('Saint Kitts and Nevis','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>1);
		$_['LC'] = array('name'=>__('Saint Lucia','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>1);
		$_['MF'] = array('name'=>__('Saint Martin','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€ #,###.##'),'units'=>'metric','region'=>1);
		$_['PM'] = array('name'=>__('Saint Pierre and Miquelon','Shopp'),'currency'=>array('code'=>'EUR','format'=>'# ###,## €'),'units'=>'metric','region'=>0);
		$_['VC'] = array('name'=>__('Saint Vincent','Shopp'),'currency'=>array('code'=>'XCD','format'=>'EC$#,###.##'),'units'=>'metric','region'=>2);
		$_['WS'] = array('name'=>__('Samoa','Shopp'),'currency'=>array('code'=>'WST','format'=>'WS$#,###.##'),'units'=>'metric','region'=>7);
		$_['SM'] = array('name'=>__('San Marino','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€ #,###.##'),'units'=>'metric','region'=>3);
		$_['ST'] = array('name'=>__('São Tomé and Príncipe','Shopp'),'currency'=>array('code'=>'STD','format'=>'Db #,###.##'),'units'=>'metric','region'=>5);
		$_['SA'] = array('name'=>__('Saudi Arabia','Shopp'),'currency'=>array('code'=>'SAR','format'=>'####,## ر.س'),'units'=>'metric','region'=>4);
		$_['SN'] = array('name'=>__('Senegal','Shopp'),'currency'=>array('code'=>'XOF','format'=>'# ### CFA'),'units'=>'metric','region'=>5);
		$_['RS'] = array('name'=>__('Serbia','Shopp'),'currency'=>array('code'=>'RSD','format'=>'din. #,###'),'units'=>'metric','region'=>3);
		$_['SC'] = array('name'=>__('Seychelles','Shopp'),'currency'=>array('code'=>'SCR','format'=>'₨#,###'),'units'=>'metric','region'=>5);
		$_['SL'] = array('name'=>__('Sierra Leone','Shopp'),'currency'=>array('code'=>'SLL','format'=>'Le #,###.##'),'units'=>'metric','region'=>5);
		$_['SG'] = array('name'=>__('Singapore','Shopp'),'currency'=>array('code'=>'SGD','format'=>'$#,###.##'),'units'=>'metric','region'=>6);
		$_['SX'] = array('name'=>__('Sint Maarten','Shopp'),'currency'=>array('code'=>'ANG','format'=>'ƒ#.###,##'),'units'=>'metric','region'=>1);
		$_['SK'] = array('name'=>__('Slovakia','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['SI'] = array('name'=>__('Slovenia','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['SB'] = array('name'=>__('Solomon Islands','Shopp'),'currency'=>array('code'=>'SBD','format'=>'SI$#,###.##'),'units'=>'metric','region'=>7);
		$_['SO'] = array('name'=>__('Somalia','Shopp'),'currency'=>array('code'=>'SOS','format'=>'Ssh#,###'),'units'=>'metric','region'=>5);
		$_['ZA'] = array('name'=>__('South Africa','Shopp'),'currency'=>array('code'=>'ZAR','format'=>'R# ###,##'),'units'=>'metric','region'=>5);
		$_['GS'] = array('name'=>__('South Georgia','Shopp'),'currency'=>array('code'=>'GBP','format'=>'£#,###.##'),'units'=>'metric','region'=>2);
		$_['KR'] = array('name'=>__('South Korea','Shopp'),'currency'=>array('code'=>'KRW','format'=>'₩#,###.##'),'units'=>'metric','region'=>6);
		$_['SS'] = array('name'=>__('South Sudan','Shopp'),'currency'=>array('code'=>'SSP','format'=>'SSP #,###.##'),'units'=>'metric','region'=>5);
		$_['ES'] = array('name'=>__('Spain','Shopp'),'currency'=>array('code'=>'EUR','format'=>'#.###,## €'),'units'=>'metric','region'=>3);
		$_['LK'] = array('name'=>__('Sri Lanka','Shopp'),'currency'=>array('code'=>'LKR','format'=>'SL₨ #,###.##'),'units'=>'metric','region'=>6);
		$_['SD'] = array('name'=>__('Sudan','Shopp'),'currency'=>array('code'=>'SDG','format'=>'SDG #.###,##'),'units'=>'metric','region'=>5);
		$_['SR'] = array('name'=>__('Suriname','Shopp'),'currency'=>array('code'=>'SRD','format'=>'$#,###.##'),'units'=>'metric','region'=>2);
		$_['SJ'] = array('name'=>__('Svalbard and Jan Mayen','Shopp'),'currency'=>array('code'=>'NOK','format'=>'kr # ###,##'),'units'=>'metric','region'=>3);
		$_['SE'] = array('name'=>__('Sweden','Shopp'),'currency'=>array('code'=>'SEK','format'=>'# ### ###, kr'),'units'=>'metric','region'=>3);
		$_['SZ'] = array('name'=>__('Swaziland','Shopp'),'currency'=>array('code'=>'SZL','format'=>'E# ###,##'),'units'=>'metric','region'=>5);
		$_['CH'] = array('name'=>__('Switzerland','Shopp'),'currency'=>array('code'=>'CHF','format'=>"CHF #'###.##"),'units'=>'metric','region'=>3);
		$_['SY'] = array('name'=>__('Syria','Shopp'),'currency'=>array('code'=>'SYP','format'=>'£S#,###.##'),'units'=>'metric','region'=>4);
		$_['TW'] = array('name'=>__('Taiwan','Shopp'),'currency'=>array('code'=>'TWD','format'=>'NT$#,###.##'),'units'=>'metric','region'=>6);
		$_['TJ'] = array('name'=>__('Tajikistan','Shopp'),'currency'=>array('code'=>'TJS','format'=>'$#,###.##'),'units'=>'metric','region'=>6);
		$_['TZ'] = array('name'=>__('Tanzania','Shopp'),'currency'=>array('code'=>'TZS','format'=>'#,###.## TSh'),'units'=>'metric','region'=>5);
		$_['TH'] = array('name'=>__('Thailand','Shopp'),'currency'=>array('code'=>'THB','format'=>'#,###.##฿'),'units'=>'metric','region'=>6);
		$_['TG'] = array('name'=>__('Togo','Shopp'),'currency'=>array('code'=>'XOF','format'=>'CFA#,###'),'units'=>'metric','region'=>5);
		$_['TK'] = array('name'=>__('Tokelau','Shopp'),'currency'=>array('code'=>'NZD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['TO'] = array('name'=>__('Tonga','Shopp'),'currency'=>array('code'=>'TOP','format'=>'T$ #,###.##'),'units'=>'metric','region'=>7);
		$_['TT'] = array('name'=>__('Trinidad and Tobago','Shopp'),'currency'=>array('code'=>'TTD','format'=>'TT$#,###.##'),'units'=>'metric','region'=>2);
		$_['TN'] = array('name'=>__('Tunisia','Shopp'),'currency'=>array('code'=>'TND','format'=>'####,### د.ت'),'units'=>'metric','region'=>5);
		$_['TR'] = array('name'=>__('Turkey','Shopp'),'currency'=>array('code'=>'TRL','format'=>'#.###,## TL'),'units'=>'metric','region'=>6);
		$_['TM'] = array('name'=>__('Turkmenistan','Shopp'),'currency'=>array('code'=>'TMT','format'=>'#,###.## m'),'units'=>'metric','region'=>6);
		$_['TC'] = array('name'=>__('Turks and Caicos Islands','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>1);
		$_['TV'] = array('name'=>__('Tuvalu','Shopp'),'currency'=>array('code'=>'AUD','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['UG'] = array('name'=>__('Uganda','Shopp'),'currency'=>array('code'=>'UGX','format'=>'#,###.## USh'),'units'=>'metric','region'=>5);
		$_['UA'] = array('name'=>__('Ukraine','Shopp'),'currency'=>array('code'=>'UAH','format'=>'# ###,## ₴'),'units'=>'metric','region'=>3);
		$_['AE'] = array('name'=>__('United Arab Emirates','Shopp'),'currency'=>array('code'=>'AED','format'=>'Dhs. #,###.##'),'units'=>'metric','region'=>4);
		$_['UY'] = array('name'=>__('Uruguay','Shopp'),'currency'=>array('code'=>'UYP','format'=>'$#,###.##'),'units'=>'metric','region'=>2);
		$_['UZ'] = array('name'=>__('Uzbekistan','Shopp'),'currency'=>array('code'=>'UZS','format'=>'$#,###.##'),'units'=>'metric','region'=>6);
		$_['VU'] = array('name'=>__('Vanuatu','Shopp'),'currency'=>array('code'=>'VUV','format'=>'$#,###.##'),'units'=>'metric','region'=>7);
		$_['VA'] = array('name'=>__('Vatican City','Shopp'),'currency'=>array('code'=>'EUR','format'=>'€#,###.##'),'units'=>'metric','region'=>3);
		$_['VN'] = array('name'=>__('Vietnam','Shopp'),'currency'=>array('code'=>'VND','format'=>'#.###,## ₫'),'units'=>'metric','region'=>6);
		$_['VE'] = array('name'=>__('Venezuela','Shopp'),'currency'=>array('code'=>'VUB','format'=>'Bs. #,###.##'),'units'=>'metric','region'=>2);
		$_['WF'] = array('name'=>__('Wallis and Futuna','Shopp'),'currency'=>array('code'=>'XPF','format'=>'#,###.##F'),'units'=>'metric','region'=>7);
		$_['EH'] = array('name'=>__('Western Sahara','Shopp'),'currency'=>array('code'=>'MAD','format'=>'#.###,## درهم'),'units'=>'metric','region'=>5);
		$_['YE'] = array('name'=>__('Yemen','Shopp'),'currency'=>array('code'=>'YER','format'=>'#.###,## .ر.ي'),'units'=>'metric','region'=>6);
		$_['ZM'] = array('name'=>__('Zambia','Shopp'),'currency'=>array('code'=>'ZMK','format'=>'#,###.## ZK'),'units'=>'metric','region'=>5);
		$_['ZW'] = array('name'=>__('Zimbabwe','Shopp'),'currency'=>array('code'=>'USD','format'=>'$#,###.##'),'units'=>'metric','region'=>5);

		return apply_filters('shopp_countries', $_);
	}

	/**
	 * Provides a lookup table of country zones (states/provinces)
	 *
	 * @see wiki ISO_3166-2
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array
	 **/
	public static function country_zones () {
		$_ = array();
		$_['AR'] = array();
		$_['AR']['B'] = __('Buenos Aires','Shopp');
		$_['AR']['K'] = __('Catmarca','Shopp');
		$_['AR']['H'] = __('Chaco','Shopp');
		$_['AR']['U'] = __('Chubut','Shopp');
		$_['AR']['C'] = __('Córdoba','Shopp');
		$_['AR']['W'] = __('Corrientes','Shopp');
		$_['AR']['E'] = __('Entre Ríos','Shopp');
		$_['AR']['P'] = __('Formosa','Shopp');
		$_['AR']['Y'] = __('Jujuy','Shopp');
		$_['AR']['L'] = __('La Pampa','Shopp');
		$_['AR']['F'] = __('La Rioja','Shopp');
		$_['AR']['M'] = __('Mendoza','Shopp');
		$_['AR']['N'] = __('Misiones','Shopp');
		$_['AR']['Q'] = __('Neuquén','Shopp');
		$_['AR']['R'] = __('Río Negro','Shopp');
		$_['AR']['A'] = __('Salta','Shopp');
		$_['AR']['J'] = __('San Juan','Shopp');
		$_['AR']['D'] = __('San Luis','Shopp');
		$_['AR']['Z'] = __('Santa Cruz','Shopp');
		$_['AR']['S'] = __('Santa Fe','Shopp');
		$_['AR']['G'] = __('Santiago del Estero','Shopp');
		$_['AR']['V'] = __('Tierra del Fuego','Shopp');
		$_['AR']['T'] = __('Tucumán','Shopp');

		$_['AT'] = array();
		$_['AT']['1'] = __('Burgenland','Shopp');
		$_['AT']['2'] = __('Kärnten','Shopp');
		$_['AT']['3'] = __('Niederösterreich','Shopp');
		$_['AT']['4'] = __('Oberösterreich','Shopp');
		$_['AT']['5'] = __('Salzburg','Shopp');
		$_['AT']['6'] = __('Steiermark','Shopp');
		$_['AT']['7'] = __('Tirol','Shopp');
		$_['AT']['8'] = __('Vorarlberg','Shopp');
		$_['AT']['9'] = __('Wien','Shopp');

		$_['AU'] = array();
		$_['AU']['ACT'] = 'Australian Capital Territory';
		$_['AU']['NSW'] = 'New South Wales';
		$_['AU']['NT'] = 'Northern Territory';
		$_['AU']['QLD'] = 'Queensland';
		$_['AU']['SA'] = 'South Australia';
		$_['AU']['TAS'] = 'Tasmania';
		$_['AU']['VIC'] = 'Victoria';
		$_['AU']['WA'] = 'Western Australia';

		$_['BE'] = array();
		$_['BE']['VAN'] = __('Antwerpen','Shopp');
		$_['BE']['WBR'] = __('Brabant Wallon','Shopp');
		$_['BE']['BRU'] = __('Brussels Capital','Shopp');
		$_['BE']['WHT'] = __('Hainaut','Shopp');
		$_['BE']['WLG'] = __('Liège','Shopp');
		$_['BE']['VLI'] = __('Limburg','Shopp');
		$_['BE']['WLX'] = __('Luxembourg','Shopp');
		$_['BE']['WNA'] = __('Namur','Shopp');
		$_['BE']['VOV'] = __('Oost-Vlaanderen','Shopp');
		$_['BE']['VBR'] = __('Vlaams Brabant','Shopp');
		$_['BE']['VWV'] = __('West-Vlaanderen','Shopp');

		$_['DE']['BW'] = 'Baden-Württemberg';
		$_['DE']['BY'] = 'Bayern';
		$_['DE']['BE'] = 'Berlin';
		$_['DE']['BB'] = 'Brandenburg';
		$_['DE']['HB'] = 'Bremen';
		$_['DE']['HH'] = 'Hamburg';
		$_['DE']['HE'] = 'Hessen';
		$_['DE']['MV'] = 'Mecklenburg-Vorpommern';
		$_['DE']['NI'] = 'Niedersachsen';
		$_['DE']['NW'] = 'Nordrhein-Westfalen';
		$_['DE']['RP'] = 'Rheinland-Pfalz';
		$_['DE']['SL'] = 'Saarland';
		$_['DE']['SN'] = 'Sachsen';
		$_['DE']['ST'] = 'Sachsen-Anhalt';
		$_['DE']['SH'] = 'Schleswig-Holstein';
		$_['DE']['TH'] = 'Thüringen';

		$_['CA'] = array();
		$_['CA']['AB'] = 'Alberta';
		$_['CA']['BC'] = 'British Columbia';
		$_['CA']['MB'] = 'Manitoba';
		$_['CA']['NB'] = 'New Brunswick';
		$_['CA']['NL'] = 'Newfoundland';
		$_['CA']['NT'] = 'Northwest Territories';
		$_['CA']['NS'] = 'Nova Scotia';
		$_['CA']['NU'] = 'Nunavut';
		$_['CA']['ON'] = 'Ontario';
		$_['CA']['PE'] = 'Prince Edward Island';
		$_['CA']['QC'] = 'Quebec';
		$_['CA']['SK'] = 'Saskatchewan';
		$_['CA']['YT'] = 'Yukon Territory';

		$_['NL'] = array();
		$_['NL']['DR'] = __('Drenthe','Shopp');
		$_['NL']['FL'] = __('Flevoland','Shopp');
		$_['NL']['FR'] = __('Friesland','Shopp');
		$_['NL']['GE'] = __('Gelderland','Shopp');
		$_['NL']['GR'] = __('Groningen','Shopp');
		$_['NL']['LI'] = __('Limburg','Shopp');
		$_['NL']['NB'] = __('Noord-Brabant','Shopp');
		$_['NL']['NH'] = __('Noord-Holland','Shopp');
		$_['NL']['OV'] = __('Overijssel','Shopp');
		$_['NL']['UT'] = __('Utrecht','Shopp');
		$_['NL']['ZE'] = __('Zeeland','Shopp');
		$_['NL']['ZH'] = __('Zuid-Holland','Shopp');

		$_['US'] = array();
		$_['US']['AL'] = 'Alabama';
		$_['US']['AK'] = 'Alaska ';
		$_['US']['AZ'] = 'Arizona';
		$_['US']['AR'] = 'Arkansas';
		$_['US']['CA'] = 'California';
		$_['US']['CO'] = 'Colorado';
		$_['US']['CT'] = 'Connecticut';
		$_['US']['DE'] = 'Delaware';
		$_['US']['DC'] = 'District Of Columbia';
		$_['US']['FL'] = 'Florida';
		$_['US']['GA'] = 'Georgia';
		$_['US']['HI'] = 'Hawaii';
		$_['US']['ID'] = 'Idaho';
		$_['US']['IL'] = 'Illinois';
		$_['US']['IN'] = 'Indiana';
		$_['US']['IA'] = 'Iowa';
		$_['US']['KS'] = 'Kansas';
		$_['US']['KY'] = 'Kentucky';
		$_['US']['LA'] = 'Louisiana';
		$_['US']['ME'] = 'Maine';
		$_['US']['MD'] = 'Maryland';
		$_['US']['MA'] = 'Massachusetts';
		$_['US']['MI'] = 'Michigan';
		$_['US']['MN'] = 'Minnesota';
		$_['US']['MS'] = 'Mississippi';
		$_['US']['MO'] = 'Missouri';
		$_['US']['MT'] = 'Montana';
		$_['US']['NE'] = 'Nebraska';
		$_['US']['NV'] = 'Nevada';
		$_['US']['NH'] = 'New Hampshire';
		$_['US']['NJ'] = 'New Jersey';
		$_['US']['NM'] = 'New Mexico';
		$_['US']['NY'] = 'New York';
		$_['US']['NC'] = 'North Carolina';
		$_['US']['ND'] = 'North Dakota';
		$_['US']['OH'] = 'Ohio';
		$_['US']['OK'] = 'Oklahoma';
		$_['US']['OR'] = 'Oregon';
		$_['US']['PA'] = 'Pennsylvania';
		$_['US']['RI'] = 'Rhode Island';
		$_['US']['SC'] = 'South Carolina';
		$_['US']['SD'] = 'South Dakota';
		$_['US']['TN'] = 'Tennessee';
		$_['US']['TX'] = 'Texas';
		$_['US']['UT'] = 'Utah';
		$_['US']['VT'] = 'Vermont';
		$_['US']['VA'] = 'Virginia';
		$_['US']['WA'] = 'Washington';
		$_['US']['WV'] = 'West Virginia';
		$_['US']['WI'] = 'Wisconsin';
		$_['US']['WY'] = 'Wyoming';

		$_['USAF']['AA'] = 'Americas';
		$_['USAF']['AE'] = 'Europe';
		$_['USAF']['AP'] = 'Pacific';

		$_['USAT']['AS'] = 'American Samoa';
		$_['USAT']['GU'] = 'Guam';
		$_['USAT']['MP'] = 'Northern Mariana Islands';
		$_['USAT']['PR'] = 'Puerto Rico';
		$_['USAT']['UM'] = 'US Minor Outlying Islands';
		$_['USAT']['VI'] = 'Virgin Islands';

		return apply_filters('shopp_country_zones',$_);
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
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of country codes
	 **/
	public static function country_inclusive_taxes () {
		$_ = array_merge(self::country_euvat(), self::country_gst());

		// @deprecated shopp_vat_countries
		$_ = apply_filters('shopp_vat_countries', $_);
		// @deprecated shopp_tax_inclusive_countries
		$_ = apply_filters('shopp_tax_inclusive_countries', $_);
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

	public static function postcodes () {

		$_ = array();

		$_['AU'] = array(
			'ACT' => array('0200-0299', '2600-2618', '2900-2920'),
			'NSW' => array('1000-2599', '2619-2898', '2921-2999'),
			'NT' => array('0800-0999'),
			'QLD' => array('4000-4999', '9000-9999'),
			'SA' => array('5000-5799', '5800-5999'),
			'TAS' => array('7000-7999'),
			'VIC' => array('3000-3999', '8000-8999'),
			'WA' => array('6000-6797', '6800-6999'),
		);

		$_['CA'] = array('Y'=>'YT', 'X'=>array('NT','NU'), 'V'=>'BC', 'T'=>'AB', 'S'=>'SK', 'R'=>'MB', 'K'=>'ON', 'L'=>'ON', 'M'=>'ON', 'N'=>'ON', 'P'=>'ON', 'G'=>'QC', 'H'=>'QC', 'J'=>'QC', 'E'=>'NB', 'C'=>'PE', 'B'=>'NS', 'A'=>'NL');

		$_['US'] = array('005'=>'NY', '006'=>'PR', '007'=>'PR', '008'=>'VI', '009'=>'PR', '010'=>'MA', '011'=>'MA', '012'=>'MA', '013'=>'MA', '014'=>'MA', '015'=>'MA', '016'=>'MA', '017'=>'MA', '018'=>'MA', '019'=>'MA', '020'=>'MA', '021'=>'MA', '022'=>'MA', '023'=>'MA', '024'=>'MA', '025'=>'MA', '026'=>'MA', '027'=>'MA', '028'=>'RI', '029'=>'RI', '030'=>'NH', '031'=>'NH', '032'=>'NH', '033'=>'NH', '034'=>'NH', '035'=>'NH', '036'=>'NH', '037'=>'NH', '038'=>'NH', '039'=>'ME', '040'=>'ME', '041'=>'ME', '042'=>'ME', '043'=>'ME', '044'=>'ME', '045'=>'ME', '046'=>'ME', '047'=>'ME', '048'=>'ME', '049'=>'ME', '050'=>'VT', '051'=>'VT', '052'=>'VT', '053'=>'VT', '054'=>'VT', '055'=>'MA', '056'=>'VT', '057'=>'VT', '058'=>'VT', '059'=>'VT', '060'=>'CT', '061'=>'CT', '062'=>'CT', '063'=>'CT', '064'=>'CT', '065'=>'CT', '066'=>'CT', '067'=>'CT', '068'=>'CT', '069'=>'CT', '070'=>'NJ', '071'=>'NJ', '072'=>'NJ', '073'=>'NJ', '074'=>'NJ', '075'=>'NJ', '076'=>'NJ', '077'=>'NJ', '078'=>'NJ', '079'=>'NJ', '080'=>'NJ', '081'=>'NJ', '082'=>'NJ', '083'=>'NJ', '084'=>'NJ', '085'=>'NJ', '086'=>'NJ', '087'=>'NJ', '088'=>'NJ', '089'=>'NJ', '090'=>'AE', '091'=>'AE', '092'=>'AE', '093'=>'AE', '094'=>'AE', '095'=>'AE', '096'=>'AE', '097'=>'AE', '098'=>'AE', '100'=>'NY', '101'=>'NY', '102'=>'NY', '103'=>'NY', '104'=>'NY', '105'=>'NY', '106'=>'NY', '107'=>'NY', '108'=>'NY', '109'=>'NY', '110'=>'NY', '111'=>'NY', '112'=>'NY', '113'=>'NY', '114'=>'NY', '115'=>'NY', '116'=>'NY', '117'=>'NY', '118'=>'NY', '119'=>'NY', '120'=>'NY', '121'=>'NY', '122'=>'NY', '123'=>'NY', '124'=>'NY', '125'=>'NY', '126'=>'NY', '127'=>'NY', '128'=>'NY', '129'=>'NY', '130'=>'NY', '131'=>'NY', '132'=>'NY', '133'=>'NY', '134'=>'NY', '135'=>'NY', '136'=>'NY', '137'=>'NY', '138'=>'NY', '139'=>'NY', '140'=>'NY', '141'=>'NY', '142'=>'NY', '143'=>'NY', '144'=>'NY', '145'=>'NY', '146'=>'NY', '147'=>'NY', '148'=>'NY', '149'=>'NY', '150'=>'PA', '151'=>'PA', '152'=>'PA', '153'=>'PA', '154'=>'PA', '155'=>'PA', '156'=>'PA', '157'=>'PA', '158'=>'PA', '159'=>'PA', '160'=>'PA', '161'=>'PA', '162'=>'PA', '163'=>'PA', '164'=>'PA', '165'=>'PA', '166'=>'PA', '167'=>'PA', '168'=>'PA', '169'=>'PA', '170'=>'PA', '171'=>'PA', '172'=>'PA', '173'=>'PA', '174'=>'PA', '175'=>'PA', '176'=>'PA', '177'=>'PA', '178'=>'PA', '179'=>'PA', '180'=>'PA', '181'=>'PA', '182'=>'PA', '183'=>'PA', '184'=>'PA', '185'=>'PA', '186'=>'PA', '187'=>'PA', '188'=>'PA', '189'=>'PA', '190'=>'PA', '191'=>'PA', '192'=>'PA', '193'=>'PA', '194'=>'PA', '195'=>'PA', '196'=>'PA', '197'=>'DE', '198'=>'DE', '199'=>'DE', '200'=>'DC', '201'=>'VA', '202'=>'DC', '203'=>'DC', '204'=>'DC', '205'=>'DC', '206'=>'MD', '207'=>'MD', '208'=>'MD', '209'=>'MD', '210'=>'MD', '211'=>'MD', '212'=>'MD', '214'=>'MD', '215'=>'MD', '216'=>'MD', '217'=>'MD', '218'=>'MD', '219'=>'MD', '220'=>'VA', '221'=>'VA', '222'=>'VA', '223'=>'VA', '224'=>'VA', '225'=>'VA', '226'=>'VA', '227'=>'VA', '228'=>'VA', '229'=>'VA', '230'=>'VA', '231'=>'VA', '232'=>'VA', '233'=>'VA', '234'=>'VA', '235'=>'VA', '236'=>'VA', '237'=>'VA', '238'=>'VA', '239'=>'VA', '240'=>'VA', '241'=>'VA', '242'=>'VA', '243'=>'VA', '244'=>'VA', '245'=>'VA', '246'=>'VA', '247'=>'WV', '248'=>'WV', '249'=>'WV', '250'=>'WV', '251'=>'WV', '252'=>'WV', '253'=>'WV', '254'=>'WV', '255'=>'WV', '256'=>'WV', '257'=>'WV', '258'=>'WV', '259'=>'WV', '260'=>'WV', '261'=>'WV', '262'=>'WV', '263'=>'WV', '264'=>'WV', '265'=>'WV', '266'=>'WV', '267'=>'WV', '268'=>'WV', '270'=>'NC', '271'=>'NC', '272'=>'NC', '273'=>'NC', '274'=>'NC', '275'=>'NC', '276'=>'NC', '277'=>'NC', '278'=>'NC', '279'=>'NC', '280'=>'NC', '281'=>'NC', '282'=>'NC', '283'=>'NC', '284'=>'NC', '285'=>'NC', '286'=>'NC', '287'=>'NC', '288'=>'NC', '289'=>'NC', '290'=>'SC', '291'=>'SC', '292'=>'SC', '293'=>'SC', '294'=>'SC', '295'=>'SC', '296'=>'SC', '297'=>'SC', '298'=>'SC', '299'=>'SC', '300'=>'GA', '301'=>'GA', '302'=>'GA', '303'=>'GA', '304'=>'GA', '305'=>'GA', '306'=>'GA', '307'=>'GA', '308'=>'GA', '309'=>'GA', '310'=>'GA', '311'=>'GA', '312'=>'GA', '313'=>'GA', '314'=>'GA', '315'=>'GA', '316'=>'GA', '317'=>'GA', '318'=>'GA', '319'=>'GA', '320'=>'FL', '321'=>'FL', '322'=>'FL', '323'=>'FL', '324'=>'FL', '325'=>'FL', '326'=>'FL', '327'=>'FL', '328'=>'FL', '329'=>'FL', '330'=>'FL', '331'=>'FL', '332'=>'FL', '333'=>'FL', '334'=>'FL', '335'=>'FL', '336'=>'FL', '337'=>'FL', '338'=>'FL', '339'=>'FL', '340'=>'AA', '341'=>'FL', '342'=>'FL', '344'=>'FL', '346'=>'FL', '347'=>'FL', '349'=>'FL', '350'=>'AL', '351'=>'AL', '352'=>'AL', '354'=>'AL', '355'=>'AL', '356'=>'AL', '357'=>'AL', '358'=>'AL', '359'=>'AL', '360'=>'AL', '361'=>'AL', '362'=>'AL', '363'=>'AL', '364'=>'AL', '365'=>'AL', '366'=>'AL', '367'=>'AL', '368'=>'AL', '369'=>'AL', '370'=>'TN', '371'=>'TN', '372'=>'TN', '373'=>'TN', '374'=>'TN', '375'=>'TN', '376'=>'TN', '377'=>'TN', '378'=>'TN', '379'=>'TN', '380'=>'TN', '381'=>'TN', '382'=>'TN', '383'=>'TN', '384'=>'TN', '385'=>'TN', '386'=>'MS', '387'=>'MS', '388'=>'MS', '389'=>'MS', '390'=>'MS', '391'=>'MS', '392'=>'MS', '393'=>'MS', '394'=>'MS', '395'=>'MS', '396'=>'MS', '397'=>'MS', '398'=>'GA', '399'=>'GA', '400'=>'KY', '401'=>'KY', '402'=>'KY', '403'=>'KY', '404'=>'KY', '405'=>'KY', '406'=>'KY', '407'=>'KY', '408'=>'KY', '409'=>'KY', '410'=>'KY', '411'=>'KY', '412'=>'KY', '413'=>'KY', '414'=>'KY', '415'=>'KY', '416'=>'KY', '417'=>'KY', '418'=>'KY', '420'=>'KY', '421'=>'KY', '422'=>'KY', '423'=>'KY', '424'=>'KY', '425'=>'KY', '426'=>'KY', '427'=>'KY', '430'=>'OH', '431'=>'OH', '432'=>'OH', '433'=>'OH', '434'=>'OH', '435'=>'OH', '436'=>'OH', '437'=>'OH', '438'=>'OH', '439'=>'OH', '440'=>'OH', '441'=>'OH', '442'=>'OH', '443'=>'OH', '444'=>'OH', '445'=>'OH', '446'=>'OH', '447'=>'OH', '448'=>'OH', '449'=>'OH', '450'=>'OH', '451'=>'OH', '452'=>'OH', '453'=>'OH', '454'=>'OH', '455'=>'OH', '456'=>'OH', '457'=>'OH', '458'=>'OH', '459'=>'OH', '460'=>'IN', '461'=>'IN', '462'=>'IN', '463'=>'IN', '464'=>'IN', '465'=>'IN', '466'=>'IN', '467'=>'IN', '468'=>'IN', '469'=>'IN', '470'=>'IN', '471'=>'IN', '472'=>'IN', '473'=>'IN', '474'=>'IN', '475'=>'IN', '476'=>'IN', '477'=>'IN', '478'=>'IN', '479'=>'IN', '480'=>'MI', '481'=>'MI', '482'=>'MI', '483'=>'MI', '484'=>'MI', '485'=>'MI', '486'=>'MI', '487'=>'MI', '488'=>'MI', '489'=>'MI', '490'=>'MI', '491'=>'MI', '492'=>'MI', '493'=>'MI', '494'=>'MI', '495'=>'MI', '496'=>'MI', '497'=>'MI', '498'=>'MI', '499'=>'MI', '500'=>'IA', '501'=>'IA', '502'=>'IA', '503'=>'IA', '504'=>'IA', '505'=>'IA', '506'=>'IA', '507'=>'IA', '508'=>'IA', '509'=>'IA', '510'=>'IA', '511'=>'IA', '512'=>'IA', '513'=>'IA', '514'=>'IA', '515'=>'IA', '516'=>'IA', '520'=>'IA', '521'=>'IA', '522'=>'IA', '523'=>'IA', '524'=>'IA', '525'=>'IA', '526'=>'IA', '527'=>'IA', '528'=>'IA', '530'=>'WI', '531'=>'WI', '532'=>'WI', '534'=>'WI', '535'=>'WI', '537'=>'WI', '538'=>'WI', '539'=>'WI', '540'=>'WI', '541'=>'WI', '542'=>'WI', '543'=>'WI', '544'=>'WI', '545'=>'WI', '546'=>'WI', '547'=>'WI', '548'=>'WI', '549'=>'WI', '550'=>'MN', '551'=>'MN', '553'=>'MN', '554'=>'MN', '555'=>'MN', '556'=>'MN', '557'=>'MN', '558'=>'MN', '559'=>'MN', '560'=>'MN', '561'=>'MN', '562'=>'MN', '563'=>'MN', '564'=>'MN', '565'=>'MN', '566'=>'MN', '567'=>'MN', '569'=>'DC', '570'=>'SD', '571'=>'SD', '572'=>'SD', '573'=>'SD', '574'=>'SD', '575'=>'SD', '576'=>'SD', '577'=>'SD', '580'=>'ND', '581'=>'ND', '582'=>'ND', '583'=>'ND', '584'=>'ND', '585'=>'ND', '586'=>'ND', '587'=>'ND', '588'=>'ND', '590'=>'MT', '591'=>'MT', '592'=>'MT', '593'=>'MT', '594'=>'MT', '595'=>'MT', '596'=>'MT', '597'=>'MT', '598'=>'MT', '599'=>'MT', '600'=>'IL', '601'=>'IL', '602'=>'IL', '603'=>'IL', '604'=>'IL', '605'=>'IL', '606'=>'IL', '607'=>'IL', '608'=>'IL', '609'=>'IL', '610'=>'IL', '611'=>'IL', '612'=>'IL', '613'=>'IL', '614'=>'IL', '615'=>'IL', '616'=>'IL', '617'=>'IL', '618'=>'IL', '619'=>'IL', '620'=>'IL', '622'=>'IL', '623'=>'IL', '624'=>'IL', '625'=>'IL', '626'=>'IL', '627'=>'IL', '628'=>'IL', '629'=>'IL', '630'=>'MO', '631'=>'MO', '633'=>'MO', '634'=>'MO', '635'=>'MO', '636'=>'MO', '637'=>'MO', '638'=>'MO', '639'=>'MO', '640'=>'MO', '641'=>'MO', '644'=>'MO', '645'=>'MO', '646'=>'MO', '647'=>'MO', '648'=>'MO', '649'=>'MO', '650'=>'MO', '651'=>'MO', '652'=>'MO', '653'=>'MO', '654'=>'MO', '655'=>'MO', '656'=>'MO', '657'=>'MO', '658'=>'MO', '660'=>'KS', '661'=>'KS', '662'=>'KS', '664'=>'KS', '665'=>'KS', '666'=>'KS', '667'=>'KS', '668'=>'KS', '669'=>'KS', '670'=>'KS', '671'=>'KS', '672'=>'KS', '673'=>'KS', '674'=>'KS', '675'=>'KS', '676'=>'KS', '677'=>'KS', '678'=>'KS', '679'=>'KS', '680'=>'NE', '681'=>'NE', '683'=>'NE', '684'=>'NE', '685'=>'NE', '686'=>'NE', '687'=>'NE', '688'=>'NE', '689'=>'NE', '690'=>'NE', '691'=>'NE', '692'=>'NE', '693'=>'NE', '700'=>'LA', '701'=>'LA', '703'=>'LA', '704'=>'LA', '705'=>'LA', '706'=>'LA', '707'=>'LA', '708'=>'LA', '710'=>'LA', '711'=>'LA', '712'=>'LA', '713'=>'LA', '714'=>'LA', '716'=>'AR', '717'=>'AR', '718'=>'AR', '719'=>'AR', '720'=>'AR', '721'=>'AR', '722'=>'AR', '723'=>'AR', '724'=>'AR', '725'=>'AR', '726'=>'AR', '727'=>'AR', '728'=>'AR', '729'=>'AR', '730'=>'OK', '731'=>'OK', '733'=>'TX', '734'=>'OK', '735'=>'OK', '736'=>'OK', '737'=>'OK', '738'=>'OK', '739'=>'OK', '740'=>'OK', '741'=>'OK', '743'=>'OK', '744'=>'OK', '745'=>'OK', '746'=>'OK', '747'=>'OK', '748'=>'OK', '749'=>'OK', '750'=>'TX', '751'=>'TX', '752'=>'TX', '753'=>'TX', '754'=>'TX', '755'=>'TX', '756'=>'TX', '757'=>'TX', '758'=>'TX', '759'=>'TX', '760'=>'TX', '761'=>'TX', '762'=>'TX', '763'=>'TX', '764'=>'TX', '765'=>'TX', '766'=>'TX', '767'=>'TX', '768'=>'TX', '769'=>'TX', '770'=>'TX', '772'=>'TX', '773'=>'TX', '774'=>'TX', '775'=>'TX', '776'=>'TX', '777'=>'TX', '778'=>'TX', '779'=>'TX', '780'=>'TX', '781'=>'TX', '782'=>'TX', '783'=>'TX', '784'=>'TX', '785'=>'TX', '786'=>'TX', '787'=>'TX', '788'=>'TX', '789'=>'TX', '790'=>'TX', '791'=>'TX', '792'=>'TX', '793'=>'TX', '794'=>'TX', '795'=>'TX', '796'=>'TX', '797'=>'TX', '798'=>'TX', '799'=>'TX', '800'=>'CO', '801'=>'CO', '802'=>'CO', '803'=>'CO', '804'=>'CO', '805'=>'CO', '806'=>'CO', '807'=>'CO', '808'=>'CO', '809'=>'CO', '810'=>'CO', '811'=>'CO', '812'=>'CO', '813'=>'CO', '814'=>'CO', '815'=>'CO', '816'=>'CO', '820'=>'WY', '821'=>'WY', '822'=>'WY', '823'=>'WY', '824'=>'WY', '825'=>'WY', '826'=>'WY', '827'=>'WY', '828'=>'WY', '829'=>'WY', '830'=>'WY', '831'=>'WY', '832'=>'ID', '833'=>'ID', '834'=>'ID', '835'=>'ID', '836'=>'ID', '837'=>'ID', '838'=>'ID', '840'=>'UT', '841'=>'UT', '842'=>'UT', '843'=>'UT', '844'=>'UT', '845'=>'UT', '846'=>'UT', '847'=>'UT', '850'=>'AZ', '851'=>'AZ', '852'=>'AZ', '853'=>'AZ', '855'=>'AZ', '856'=>'AZ', '857'=>'AZ', '859'=>'AZ', '860'=>'AZ', '863'=>'AZ', '864'=>'AZ', '865'=>'AZ', '870'=>'NM', '871'=>'NM', '872'=>'NM', '873'=>'NM', '874'=>'NM', '875'=>'NM', '877'=>'NM', '878'=>'NM', '879'=>'NM', '880'=>'NM', '881'=>'NM', '882'=>'NM', '883'=>'NM', '884'=>'NM', '885'=>'TX', '889'=>'NV', '890'=>'NV', '891'=>'NV', '893'=>'NV', '894'=>'NV', '895'=>'NV', '897'=>'NV', '898'=>'NV', '900'=>'CA', '901'=>'CA', '902'=>'CA', '903'=>'CA', '904'=>'CA', '905'=>'CA', '906'=>'CA', '907'=>'CA', '908'=>'CA', '910'=>'CA', '911'=>'CA', '912'=>'CA', '913'=>'CA', '914'=>'CA', '915'=>'CA', '916'=>'CA', '917'=>'CA', '918'=>'CA', '919'=>'CA', '920'=>'CA', '921'=>'CA', '922'=>'CA', '923'=>'CA', '924'=>'CA', '925'=>'CA', '926'=>'CA', '927'=>'CA', '928'=>'CA', '930'=>'CA', '931'=>'CA', '932'=>'CA', '933'=>'CA', '934'=>'CA', '935'=>'CA', '936'=>'CA', '937'=>'CA', '938'=>'CA', '939'=>'CA', '940'=>'CA', '941'=>'CA', '942'=>'CA', '943'=>'CA', '944'=>'CA', '945'=>'CA', '946'=>'CA', '947'=>'CA', '948'=>'CA', '949'=>'CA', '950'=>'CA', '951'=>'CA', '952'=>'CA', '953'=>'CA', '954'=>'CA', '955'=>'CA', '956'=>'CA', '957'=>'CA', '958'=>'CA', '959'=>'CA', '960'=>'CA', '961'=>'CA', '962'=>'AP', '963'=>'AP', '964'=>'AP', '965'=>'AP', '966'=>'AP', '967'=>'HI', '968'=>'HI', '969'=>'GU', '970'=>'OR', '971'=>'OR', '972'=>'OR', '973'=>'OR', '974'=>'OR', '975'=>'OR', '976'=>'OR', '977'=>'OR', '978'=>'OR', '979'=>'OR', '980'=>'WA', '981'=>'WA', '982'=>'WA', '983'=>'WA', '984'=>'WA', '985'=>'WA', '986'=>'WA', '988'=>'WA', '989'=>'WA', '990'=>'WA', '991'=>'WA', '992'=>'WA', '993'=>'WA', '994'=>'WA', '995'=>'AK', '996'=>'AK', '997'=>'AK', '998'=>'AK', '999'=>'AK');

		$_['USAF'] = $_['US'];
		$_['USAT'] = $_['US'];

		return apply_filters('shopp_postcodes',$_);

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
		$_['usps'] = new ShippingCarrier(__('US Postal Service', 'Shopp'), 'http://usps.com/', 'US', 'https://tools.usps.com/go/TrackConfirmAction.action?tLabels=%s',
		'/^(91\d{18}|91\d{20})$/');
		$_['auspost'] = new ShippingCarrier(__('Australia Post', 'Shopp'), 'http://auspost.com.au/', 'AU', 'http://auspost.com.au/track/track.html?trackIds=%s', '/^(Z|[A-Z]{2}[A-Z0-9]{9}[A-Z]{2})/');
		$_['capost'] = new ShippingCarrier(__('Canada Post', 'Shopp'), 'http://canadapost.ca/', 'CA', 'http://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=%s', '/^(\d{16}|[A-Z]{2}[A-Z0-9]{9}[A-Z]{2})/');
		$_['china-post'] = new ShippingCarrier(__('China Air Post', 'Shopp'), 'http://183.com.cn/', 'CN');
		$_['ems-china'] = new ShippingCarrier(__('EMS China', 'Shopp'), 'http://www.ems.com.cn/', 'CN'); // EEXXXXXXXXXHK??
		$_['hongkong-post'] = new ShippingCarrier(__('Hong Kong Post', 'Shopp'), 'http://www.ems.com.cn/', 'CN');
		$_['india-post'] = new ShippingCarrier(__('India Post', 'Shopp'), 'http://www.indiapost.gov.in/', 'IN');
		$_['japan-post'] = new ShippingCarrier(__('Japan Post', 'Shopp'), 'http://www.indiapost.gov.in/', 'IN');
		$_['parcelforce'] = new ShippingCarrier(__('Parcelforce', 'Shopp'), 'http://parcelforce.com/', 'UK');
		$_['post-danmark'] = new ShippingCarrier(__('Post Danmark', 'Shopp'), 'http://www.postdanmark.dk/', 'DK');
		$_['posten-norway'] = new ShippingCarrier(__('Posten Norway', 'Shopp'), 'http://www.posten.no/', 'NO');
		$_['posten-sweden'] = new ShippingCarrier(__('Posten Sweden', 'Shopp'), 'http://www.posten.se/', 'NO');
		$_['purolator'] = new ShippingCarrier(__('Purolator', 'Shopp'), 'http://purolator.com/', 'CA', 'http://shipnow.purolator.com/shiponline/track/purolatortrack.asp?pinno=%s');
		$_['russian-post'] = new ShippingCarrier(__('Russian Post', 'Shopp'), 'http://www.russianpost.ru/', 'RU', 'http://www.russianpost.ru/rp/servise/ru/home/postuslug/trackingpo');
		$_['thailand-post'] = new ShippingCarrier(__('Thailand Post', 'Shopp'), 'http://www.thailandpost.com/', 'NO');
		$_['nz-post'] = new ShippingCarrier(__('New Zealand Post', 'Shopp'), 'http://www.nzpost.co.nz/', 'NZ', 'http://www.nzpost.co.nz/tools/tracking-new?trackid=%s', '/[A-Z]{2}\d{9}[A-Z]{2}/i');

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

}
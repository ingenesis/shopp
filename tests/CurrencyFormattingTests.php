<?php
/**
 * CurrencyFormatting
 *
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 15 September, 2010
 * @package shopp
 * @subpackage
 **/

/**
 * CurrencyFormatting
 *
 * @author
 * @since 1.1
 * @package shopp
 **/
class CurrencyFormatting extends ShoppTestCase {

	var $formats = array();
	var $values = array (0.0123456789,0.123456789,1.234,12.345,123.456,1234.567,12345.678,123456.789,1234567.899);

	function setUp() {
        $this->markTestSkipped('The '.__CLASS__.' unit tests have not been re-implemented.');
		parent::setUp();

		$countries = Lookup::countries();
		foreach ($countries as $code => $country)
			$this->formats[$code] = scan_money_format($country['currency']['format']);
	}

	function tearDown() {
		parent::tearDown();
		unset($this->formats,$this->values);
	}

	function provider () {
		return array(
			//				0.0123456789	0.123456789		1.234			12.345			123.456			 12345.678			1234567.899
			array('CA',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('US',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
		  array('USAF',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('GB',		'£0.01',		'£0.12',		'£1.23',		'£12.35',		'£123.46',		'£12,345.68',		'£1,234,567.90'),
			// 'DZ'
			array('AR',		'$0,01',		'$0,12',		'$1,23',		'$12,35',		'$123,46',		'$12.345,68',		'$1.234.567,90'),
			array('AW',		'ƒ0.01',		'ƒ0.12',		'ƒ1.23',		'ƒ12.35',		'ƒ123.46',		'ƒ12,345.68',		'ƒ1,234,567.90'),
			array('AU',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12 345.68',		'$1 234 567.90'),
			array('AT',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('BB',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('BS',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			// 'BH'
			array('BE',		'0,01 €',		'0,12 €',		'1,23 €',		'12,35 €',		'123,46 €',		'12.345,68 €',		'1.234.567,90 €'),
			array('BR',		'R$0,01',		'R$0,12',		'R$1,23',		'R$12,35',		'R$123,46',		'R$12.345,68',		'R$1.234.567,90'),
			array('BG',		'0,01 лв.',		'0,12 лв.',		'1,23 лв.',		'12,35 лв.',	'123,46 лв.',	'12 345,68 лв.',	'1 234 567,90 лв.'),
			array('CL',		'$0,01',		'$0,12',		'$1,23',		'$12,35',		'$123,46',		'$12.345,68',		'$1.234.567,90'),
			array('CN',		'¥0.01',		'¥0.12',		'¥1.23',		'¥12.35',		'¥123.46',		'¥12,345.68',		'¥1,234,567.90'),
			array('CO',		'$0,01',		'$0,12',		'$1,23',		'$12,35',		'$123,46',		'$12.345,68',		'$1.234.567,90'),
			array('CR',		'₡0,01',		'₡0,12',		'₡1,23',		'₡12,35',		'₡123,46',		'₡12.345,68',		'₡1.234.567,90'),
			array('HR',		'0,01 kn',		'0,12 kn',		'1,23 kn',		'12,35 kn',		'123,46 kn',	'12.345,68 kn',		'1.234.567,90 kn'),
			array('CY',		'€0,01',		'€0,12',		'€1,23',		'€12,35',		'€123,46',		'€12.345,68',		'€1.234.567,90'),
			array('CZ',		'0,01 Kč',		'0,12 Kč',		'1,23 Kč',		'12,35 Kč',		'123,46 Kč',	'12 345,68 Kč',		'1 234 567,90 Kč'),
			array('DK',		'0,01 kr',		'0,12 kr',		'1,23 kr',		'12,35 kr',		'123,46 kr',	'12.345,68 kr',		'1.234.567,90 kr'),
			array('DO',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('EC',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('EG',		'£0.01',		'£0.12',		'£1.23',		'£12.35',		'£123.46',		'£12,345.68',		'£1,234,567.90'),
			array('EE',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('FI',		'0,01 €',		'0,12 €',		'1,23 €',		'12,35 €',		'123,46 €',		'12 345,68 €',		'1 234 567,90 €'),
			array('FR',		'0,01 €',		'0,12 €',		'1,23 €',		'12,35 €',		'123,46 €',		'12 345,68 €',		'1 234 567,90 €'),
			array('DE',		'0.01 €',		'0.12 €',		'1.23 €',		'12.35 €',		'123.46 €',		'12,345.68 €',		'1,234,567.90 €'),
			array('GR',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('GP',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('GT',		'Q0.01',		'Q0.12',		'Q1.23',		'Q12.35',		'Q123.46',		'Q12,345.68',		'Q1,234,567.90'),
			array('HK',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('HU',		'0 Ft',			'0 Ft',			'1 Ft',			'12 Ft',		'123 Ft',		'12 346 Ft',		'1 234 568 Ft'),
			array('IS',		'0 kr.',		'0 kr.',		'1 kr.',		'12 kr.',		'123 kr.',		'12.346 kr.',		'1.234.568 kr.'),
			array('IN',		'₨0.01',		'₨0.12',		'₨1.23',		'₨12.35',		'₨123.46',		'₨12,345.68',		'₨12,34,567.90'),
			array('ID',		'Rp 0,01',		'Rp 0,12',		'Rp 1,23',		'Rp 12,35',		'Rp 123,46',	'Rp 12.345,68',		'Rp 1.234.567,90'),
			array('IE',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('IL',		'0.01 ₪',		'0.12 ₪',		'1.23 ₪',		'12.35 ₪',		'123.46 ₪',		'12,345.68 ₪',		'1,234,567.90 ₪'),
			array('IT',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('JM',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('JP',		'¥0',			'¥0',			'¥1',			'¥12',			'¥123',			'¥12,346',			'¥1,234,568'),
			array('LV',		'0.01 Ls',		'0.12 Ls',		'1.23 Ls',		'12.35 Ls',		'123.46 Ls',	'12 345.68 Ls',		'1 234 567.90 Ls'),
			array('LT',		'0,01 Lt',		'0,12 Lt',		'1,23 Lt',		'12,35 Lt',		'123,46 Lt',	'12.345,68 Lt',		'1.234.567,90 Lt'),
			array('LU',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('MY',		'RM0.01',		'RM0.12',		'RM1.23',		'RM12.35',		'RM123.46',		'RM12,345.68',		'RM1,234,567.90'),
			array('MT',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('MX',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('NL',		'€0,01',		'€0,12',		'€1,23',		'€12,35',		'€123,46',		'€12.345,68',		'€1.234.567,90'),
			array('NZ',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('NG',		'₦0.01',		'₦0.12',		'₦1.23',		'₦12.35',		'₦123.46',		'₦12,345.68',		'₦1,234,567.90'),
			array('NO',		'kr 0,01',		'kr 0,12',		'kr 1,23',		'kr 12,35',		'kr 123,46',	'kr 12 345,68',		'kr 1 234 567,90'),
			array('PK',		'₨0.01',		'₨0.12',		'₨1.23',		'₨12.35',		'₨123.46',		'₨12,345.68',		'₨1,234,567.90'),
			array('PE',		'S/. 0.01',		'S/. 0.12',		'S/. 1.23',		'S/. 12.35',	'S/. 123.46',	'S/. 12,345.68',	'S/. 1,234,567.90'),
			array('PH',		'Php 0.01',		'Php 0.12',		'Php 1.23',		'Php 12.35',	'Php 123.46',	'Php 12,345.68',	'Php 1,234,567.90'),
			array('PL',		'0,01 zł',		'0,12 zł',		'1,23 zł',		'12,35 zł',		'123,46 zł',	'12.345,68 zł',		'1.234.567,90 zł'),
			array('PT',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('PR',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('RO',		'0,01 lei',		'0,12 lei',		'1,23 lei',		'12,35 lei',	'123,46 lei',	'12.345,68 lei',	'1.234.567,90 lei'),
			array('RU',		'0,01 руб',		'0,12 руб',		'1,23 руб',		'12,35 руб',	'123,46 руб',	'12 345,68 руб',	'1 234 567,90 руб'),
			array('SG',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('SK',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('SI',		'€0.01',		'€0.12',		'€1.23',		'€12.35',		'€123.46',		'€12,345.68',		'€1,234,567.90'),
			array('ZA',		'R0,01',		'R0,12',		'R1,23',		'R12,35',		'R123,46',		'R12 345,68',		'R1 234 567,90'),
			array('KR',		'₩0.01',		'₩0.12',		'₩1.23',		'₩12.35',		'₩123.46',		'₩12,345.68',		'₩1,234,567.90'),
			array('ES',		'0,01 €',		'0,12 €',		'1,23 €',		'12,35 €',		'123,46 €',		'12.345,68 €',		'1.234.567,90 €'),
			array('VC',		'EC$0.01',		'EC$0.12',		'EC$1.23',		'EC$12.35',		'EC$123.46',	'EC$12,345.68',		'EC$1,234,567.90'),
			array('SE',		'0,01 kr',		'0,12 kr',		'1,23 kr',		'12,35 kr',		'123,46 kr',	'12 345,68 kr',		'1 234 567,90 kr'),
			array('CH',		"CHF 0.01",		"CHF 0.12",		"CHF 1.23",		"CHF 12.35",	"CHF 123.46",	"CHF 12'345.68",	"CHF 1'234'567.90"),
			array('TW',		'NT$0.01',		'NT$0.12',		'NT$1.23',		'NT$12.35',		'NT$123.46',	'NT$12,345.68',		'NT$1,234,567.90'),
			array('TH',		'0.01฿',		'0.12฿',		'1.23฿',		'12.35฿',		'123.46฿',		'12,345.68฿',		'1,234,567.90฿'),
			array('TT',		'TT$0.01',		'TT$0.12',		'TT$1.23',		'TT$12.35',		'TT$123.46',	'TT$12,345.68',		'TT$1,234,567.90'),
			array('TR',		'0,01 TL',		'0,12 TL',		'1,23 TL',		'12,35 TL',		'123,46 TL',	'12.345,68 TL',		'1.234.567,90 TL'),
			array('UA',		'0,01 ₴',		'0,12 ₴',		'1,23 ₴',		'12,35 ₴',		'123,46 ₴',		'12 345,68 ₴',		'1 234 567,90 ₴'),
			array('AE',		'Dhs. 0.01',	'Dhs. 0.12',	'Dhs. 1.23',	'Dhs. 12.35',	'Dhs. 123.46',	'Dhs. 12,345.68',	'Dhs. 1,234,567.90'),
			array('UY',		'$0.01',		'$0.12',		'$1.23',		'$12.35',		'$123.46',		'$12,345.68',		'$1,234,567.90'),
			array('VE',		'Bs. 0.01',		'Bs. 0.12',		'Bs. 1.23',		'Bs. 12.35',	'Bs. 123.46',	'Bs. 12,345.68',	'Bs. 1,234,567.90')
		);
	}


	/**
     * @dataProvider provider
     */
	function test_hundredths ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 0;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	/**
     * @dataProvider provider
     */
	function test_tenths ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 1;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	/**
     * @dataProvider provider
     */
	function test_whole ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 1;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	/**
     * @dataProvider provider
     */
	function test_tens ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 1;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	/**
     * @dataProvider provider
     */
	function test_hundreds ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 1;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	/**
     * @dataProvider provider
     */
	function test_thousands ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 1;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	/**
     * @dataProvider provider
     */
	function test_millions ($code,$hundredths,$tenths,$whole,$tens,$hundreds,$thousands,$millions) {
		$expected = array_values(compact('hundredths','tenths','whole','tens','hundreds','thousands','millions'));
		$testid = 1;
		$this->currency_assertions($this->values[$testid],$expected[$testid],$code);
	}

	function currency_assertions ($float,$expected,$code) {
		$format = $this->formats[$code];

		$formatted = money($float,$format);
		$this->assertEquals($expected,$formatted,"Formatting failed for country code $code from floating point number");
		$this->assertEquals($expected,money((string)$float,$format),"Formatting failed for country code $code from a string");
		$this->assertEquals(round($float,$format['precision']),floatvalue($formatted,true,$format),"Float value failed for country code $code");

		$nf = numeric_format($float,$format['precision'],$format['decimals'],$format['thousands'],$format['grouping']);
		$this->assertEquals(round($float,$format['precision']),floatvalue($nf,true,$format),"Float value failed to reverse the numeric format of $nf for country code $code ".serialize($format));

	}

} // end CurrencyFormatting class

?>
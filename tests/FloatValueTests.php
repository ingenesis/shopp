<?php
/**
 * FloatValueTests
 *
 *
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited,  4 January, 2012
 * @package shopp
 * @subpackage
 **/

/**
 * FloatValueTests
 *
 * @author
 * @since 1.2
 * @package shopp
 **/
class FloatValueTests extends ShoppTestCase {

    /**
     * @dataProvider dot_decimals_set
     */
	public function test_dot_decimals ($string,$expected) {
		$format = array(
			'cpos' => 1,
			'currency' => '$',
			'precision' => 2,
			'decimals' => '.',
			'thousands' => ',',
			'grouping' => array(3)
		);

		$float = floatvalue($string,false,$format);
		$this->assertEquals($expected,$float);
	}

	function dot_decimals_set () {
		return $values = array(
			// String to convert	// Expected Result
			array('$22,000.76%',	22000.76),
			array('$22.000,76%',	22.00076),
			array('22 000,76',		2200076),
	        array('22.000,76',		22.00076),
	        array('22,000.76',		22000.76),
	        array('22 000',			22000),
	        array('22,000',			22000),
	        array('22.000',			22),
	        array('22000.76',		22000.76),
	        array('22000,76',		2200076),
	        array('1.022.000,76',	1.022),
	        array('1,022,000.76',	1022000.76),
	        array('1,000,000',		1000000),
	        array('1.000.000',		1),
	        array('1022000.76',		1022000.76),
	        array('1022000,76',		102200076),
	        array('1022000',		1022000),
	        array('0.76',			0.76),
	        array('0,76',			76),
	        array('0.00',			0),
	        array('0,00',			0),
	        array('1.00',			1),
	        array('1,00',			100),
	        array('-22 000,76',		-2200076),
	        array('-22.000,76',		-22.00076),
	        array('-22,000.76',		-22000.76),
	        array('-22 000',		-22000),
	        array('-22,000',		-22000),
	        array('-22.000',		-22),
	        array('-22000.76',		-22000.76),
	        array('-22000,76',		-2200076),
	        array('-1.022.000,76',	-1.022),
	        array('-1,022,000.76',	-1022000.76),
	        array('-1,000,000',		-1000000),
	        array('-1.000.000',		-1),
	        array('-1022000.76',	-1022000.76),
	        array('-1022000,76',	-102200076),
	        array('-1022000',		-1022000),
	        array('-0.76',			-0.76),
	        array('-0,76',			-76),
	        array('-0.00',			0),
	        array('-0,00',			0),
	        array('-1.00',			-1),
	        array('-1,00',			-100)
		);

	}

   /**
     * @dataProvider comma_decimals_set
     */
	public function test_comma_decimals ($string,$expected) {
		$format = array(
			'cpos' => 1,
			'currency' => '$',
			'precision' => 2,
			'decimals' => ',',
			'thousands' => '.',
			'grouping' => array(3)
		);

		$float = Shopp::floatvalue($string, false, $format);
		$this->assertEquals($expected, $float);
	}

	function comma_decimals_set () {
		return $values = array(
			// String to convert	// Expected Result
			array('$22,000.76%',	22.00076),
			array('$22.000,76%',	22000.76),
			array('22 000,76',		22000.76),
	        array('22.000,76',		22000.76),
	        array('22,000.76',		22.00076),
	        array('22 000',			22000),
	        array('22,000',			22.000),
	        array('22.000',			22000),
	        array('22000.76',		2200076),
	        array('22000,76',		22000.76),
	        array('1.022.000,76',	1022000.76),
	        array('1,022,000.76',	1.022),
	        array('1,000,000',		1),
	        array('1.000.000',		1000000),
	        array('1022000.76',		102200076),
	        array('1022000,76',		1022000.76),
	        array('1022000',		1022000),
	        array('0.76',			76),
	        array('0,76',			0.76),
	        array('0.00',			0),
	        array('0,00',			0),
	        array('1.00',			100),
	        array('1,00',			1),
	        array('-22 000,76',		-22000.76),
	        array('-22.000,76',		-22000.76),
	        array('-22,000.76',		-22.00076),
	        array('-22 000',		-22000),
	        array('-22,000',		-22.000),
	        array('-22.000',		-22000),
	        array('-22000.76',		-2200076),
	        array('-22000,76',		-22000.76),
	        array('-1.022.000,76',	-1022000.76),
	        array('-1,022,000.76',	-1.022),
	        array('-1,000,000',		-1),
	        array('-1.000.000',		-1000000),
	        array('-1022000.76',	-102200076),
	        array('-1022000,76',	-1022000.76),
	        array('-1022000',		-1022000),
	        array('-0.76',			-76),
	        array('-0,76',			-0.76),
	        array('-0.00',			0),
	        array('-0,00',			0),
	        array('-1.00',			-100),
	        array('-1,00',			-1)
		);

	}

} // end FloatValueTests class
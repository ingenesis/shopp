<?php
/**
 * PayCardTests
 *
 *
 *
 * @copyright Ingenesis Limited, 15 September, 2016
 * @package shopp
 * @subpackage
 **/

/**
 * PayCardTests
 *
 * @author Jonathan Davis
 * @package shopp
 **/
class PayCardTests extends ShoppTestCase {

    static $paycards = array();

	static function setUpBeforeClass() {
		self::$paycards = ShoppLookup::paycards();
	}

	static function tearDownAfterClass () {
		self::$paycards = array();
	}

    /**
     * Test card valid and invalid card PANs
     */
	function pans () {
        
        return array(
            //    code,   PAN,                mask,         last4, match, checksum, validate
            
            // Valid cards
            array('amex', '370000000000002', 'XXXXXXXXXXXX', '0002', true, true, true),
            array('amex', '378282246310005', 'XXXXXXXXXXXX', '0005', true, true, true),
            array('amex', '371449635398431', '############', '8431', true, true, true),
            array('amex', '378734493671000', '............', '1000', true, true, true),

            array('dc', '30569309025904', '------------', '5904', true, true, true),
            array('dc', '36700102000000', '------------', '0000', true, true, true),
            array('dc', '36148900647913', '------------', '7913', true, true, true),
            array('dc', '38000000000006', '------------', '0006', true, true, true),
            array('dc', '38520000023237', '------------', '3237', true, true, true),

            array('disc', '6011000000000012', 'XXXXXXXXXXXX', '0012', true, true, true),
            array('disc', '6011111111111117', 'XXXXXXXXXXXX', '1117', true, true, true),
            array('disc', '6240008631401148', 'XXXXXXXXXXXX', '1148', true, true, true),
            array('disc', '6288997715452584', 'XXXXXXXXXXXX', '2584', true, true, true),

            array('jcb', '3530111333300000', 'XXXXXXXXXXXX', '0000', true, true, true),
            array('jcb', '3566002020360505', 'XXXXXXXXXXXX', '0505', true, true, true),
            array('jcb', '3569990000000009', 'XXXXXXXXXXXX', '0009', true, true, true),

            array('dankort', '5019717010103742', 'XXXXXXXXXXXX', '3742', true, true, true),

            array('maes', '5000000000000611', 'XXXXXXXXXXXX', '0611', true, true, true),
            array('maes', '5000000000000512', 'XXXXXXXXXXXX', '0512', true, true, true),
            array('maes', '5020332120751187', 'XXXXXXXXXXXX', '1187', true, true, true),
            array('maes', '5641825849493485', 'XXXXXXXXXXXX', '3485', true, true, true),
            array('maes', '6766000000000', 'XXXXXXXXXXXX', '0000', true, true, true),
            array('maes', '6759649826438453', 'XXXXXXXXXXXX', '8453', true, true, true),
            array('maes', '6799990100000000019', 'XXXXXXXXXXXXXXX', '0019', true, true, true),

            array('mc', '2223000010309703', 'XXXXXXXXXXXX', '9703', true, true, true),
            array('mc', '2223000010309711', 'XXXXXXXXXXXX', '9711', true, true, true),
            array('mc', '5105105105105100', 'XXXXXXXXXXXX', '5100', true, true, true),
            array('mc', '5424000000000015', 'XXXXXXXXXXXX', '0015', true, true, true),
            array('mc', '5454545454545454', 'XXXXXXXXXXXX', '5454', true, true, true),
            array('mc', '5555555555554444', 'XXXXXXXXXXXX', '4444', true, true, true),
            
            array('visa', '4007000000027', 'XXXXXXXXXXXX', '0027', true, true, true),
            array('visa', '4012888818888', 'XXXXXXXXXXXX', '8888', true, true, true),
            array('visa', '4012888888881881', 'XXXXXXXXXXXX', '1881', true, true, true),
            array('visa', '4111111111111111', 'XXXXXXXXXXXX', '1111', true, true, true),
            array('visa', '4012888888881881', 'XXXXXXXXXXXX', '1881', true, true, true),
            array('visa', '4222222222222', 'XXXXXXXXXXXX', '2222', true, true, true),
            array('visa', '4917610000000000003', 'XXXXXXXXXXXXXXX', '0003', true, true, true), // Visa Debit
            array('visa', '4484070000000000007', 'XXXXXXXXXXXXXXX', '0007', true, true, true), // V Pay 19-digit

            // Invalid cards
            array('amex', '34343434343434', 'XXXXXXXXXXXX', '3434', false, true, false),
            array('visa', '4191111111111121', 'XXXXXXXXXXXXXXX', '1121', true, false, false),
        );

	}
    
    function decorated_pans() {
        // Decorated pans for sanitization tests
        return array(
            //    pan, ## of expected sanitized digits
            array('3700 000000 00002', 15),
            array('3700+000000+00002', 15),
            array('4111-1111-1111-1111', 16),
            array('~4!1#1%1$1+1.1?1-1_1=1[1](1)1*1&1^', 16),
        );
    }

    /**
     * @dataProvider decorated_pans
     */
    function test_sanitize ($pan, $digits) {
        $sanitized = strlen(PayCard::sanitize($pan));
        $this->assertEquals($sanitized, $digits, "Sanitized PAN '$pan' should match the expected number of digits.");
    }

	/**
     * @dataProvider pans
     */
    function test_validation ($code, $pan, $mask, $last4, $match, $checksum, $validate) {
        $this->assertTrue(isset(self::$paycards[ $code ]), "Invalid PayCard code. No '$code' PayCard entry exists.");
        $PayCard = self::$paycards[ $code ];

        $matched = (int)$match;
        $this->assertEquals($PayCard->match($pan), $matched, "Valid PAN '$pan' failed to match the PAN pattern for '$code' PayCard type.");

        if ( $checksum )
            $this->assertTrue($PayCard->checksum($pan), "Valid PAN $pan failed to pass the checksum match for '$code' PayCard type.");
        else $this->assertFalse($PayCard->checksum($pan), "Invalid PAN '$pan' should not pass the checksum match for '$code' PayCard type.");
        
        if ( $validate )
            $this->assertTrue($PayCard->validate($pan), "Valid PAN $pan failed to validate for '$code' PayCard type.");
        else $this->assertFalse($PayCard->validate($pan), "Invalid PAN '$pan' should not be able to validate for '$code' PayCard type.");
        
    }

	/**
     * @dataProvider pans
     */
    function test_mask ($code, $pan, $mask, $last4, $match, $checksum) {
        $this->assertTrue(isset(self::$paycards[ $code ]), "Invalid PayCard code. No $code PayCard entry exists.");
        $PayCard = self::$paycards[ $code ];
        
        $this->assertEquals(PayCard::truncate($pan), $last4, "Truncating $pan failed to match expected last-4 $last4");

        if ( $checksum ) { // Only test masking if the PAN is expected to pass the checksum test
            $masked = PayCard::mask($pan, $mask{0});
            $expected = $mask . $last4;
            $this->assertEquals($masked, $expected, "Masking $masked failed to match expected $expected");
        }
    }

} // end PayCardTests class
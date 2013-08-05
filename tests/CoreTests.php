<?php
/**
 * Core Tests for Shopp library functions.
 *
 * @copyright Ingenesis Limited, 23 July 2013
 */
class CoreTests extends ShoppTestCase {
	const TRANSLATED = 'Translated!';

	public $domain = '';
	public $context = '';


	public function test_unsupported () {
		$this->assertTrue(defined('SHOPP_UNSUPPORTED'));
	}

	public function test_translate() {
		$this->setup_translation_filters();

		$translation = Shopp::translate('Some of the colonists objected to having an anatomically correct android running around without any clothes on');
		$this->assertTrue($translation === self::TRANSLATED);
		$this->assertTrue($this->domain === 'ShoppCore');
		$this->assertEmpty($this->context);

		$translation = Shopp::translate("Who knows if we're even dead or alive?", "Geordi La Forge's philosophy");
		$this->assertTrue($translation === self::TRANSLATED);
		$this->assertTrue($this->context === "Geordi La Forge's philosophy");
		$this->assertTrue($this->domain === 'ShoppCore');
	}

	/**
	 * @depends test_translate
	 */
	public function test___() {
		$string = 'It is the woman from Ceres. She was taken prisoner by the Martians during their last invasion of that world, and since then has been a slave in the palace of the Emperor.';
		$this->assertTrue( (Shopp::__($string) === $string) );

		$string = 'Apparently her great stature had enabled her to escape, while her %s had been %s.';
		$part_1 = 'masters';
		$part_2 = 'drowned';
		$complete = 'Apparently her great stature had enabled her to escape, while her masters had been drowned.';

		$this->assertTrue( (Shopp::__($string, $part_1, $part_2) === $complete) );
	}

	public function test__e() {
		$string = 'The fleet was, accordingly, concentrated, and we rapidly approached the great %s palace.';
		$part = 'Martian';
		$complete = 'The fleet was, accordingly, concentrated, and we rapidly approached the great Martian palace.';

		ob_start();
		Shopp::_e($string, $part);
		$result = ob_get_clean();

		$this->assertTrue( $result === $complete );
	}

	public function test__x() {
		$this->setup_translation_filters();

		$string = '"Let us take %s with us," I suggested, "and since she can speak the language of the Martians we shall probably have no difficulty in arriving at an understanding."';
		$part = 'Aina';
		$context = "Edison's Conquest of Mars";

		$translation = Shopp::_x($string, $context, $part);
		$this->assertTrue( $translation === self::TRANSLATED );
		$this->assertTrue( $this->context === $context );
		$this->assertTrue( $this->domain === 'ShoppCore' );
	}

	protected function setup_translation_filters() {
		add_filter('gettext', array($this, 'filter_gettext'), 10, 3);
		add_filter('gettext_with_context', array($this, 'filter_gettext_with_context'), 10, 4);
	}

	/**
	 * Substitutes a language translation to ensure the core lib functions are reaching WP's l10n/i18n funcs.
	 */
	public function filter_gettext($translation, $text, $domain) {
		remove_filter('gettext', array($this, 'filter_gettext'), 10, 3);
		$this->domain = $domain;
		return self::TRANSLATED;
	}

	/**
	 * As filter_gettext() but also records the context passed to WP's l10n/i18n (ie, to help ensure it is
	 * consistently ShoppCore that is being passed).
	 */
	public function filter_gettext_with_context($translation, $text, $context, $domain) {
		remove_filter('gettext_with_context', array($this, 'filter_gettext_with_context'), 10, 4);
		$this->domain = $domain;
		$this->context = $context;
		return self::TRANSLATED;
	}

	public function test_object_r() {
		$object = new stdClass;
		$object->some_property = 'some value';
		$representation = Shopp::object_r($object);

		$this->assertTrue( false !== strpos($representation, 'some_property') );
		$this->assertTrue( false !== strpos($representation, 'some value') );
	}

	public function test_var_dump() {
		$object = new stdClass;
		$object->some_property = 'some value';
		$representation = Shopp::var_dump($object);

		// Not testing against a string literal as presence of xdebug for instance may vary the result format
		$this->assertTrue( false !== strpos($representation, 'some_property') );
		$this->assertTrue( false !== strpos($representation, 'some value') );
	}

	public function test_add_query_string() {
		$url_1 = 'http://www.gutenberg.org';
		$url_2 = 'http://www.gutenberg.org?search=html';
		$query = 'book=Edison%27s+Conquest+of+Mars';
		$expected_1 = 'http://www.gutenberg.org?book=Edison%27s+Conquest+of+Mars';
		$expected_2 = 'http://www.gutenberg.org?search=html&book=Edison%27s+Conquest+of+Mars';

		$this->assertTrue( Shopp::add_query_string($query, $url_1) === $expected_1 );
		$this->assertTrue( Shopp::add_query_string($query, $url_2) === $expected_2 );
	}

	public function test_array_filter_keys() {
		$source = array(
			'Original Captain' => 'William Shatner',
			'Rebooted Captain' => 'Chris Pine',
			'Original Chief Engineer' => 'James Doohan',
			'Rebooted Chief Engineer' => 'Simon Pegg'
		);

		$mask = array('Original Captain', 'Rebooted Chief Engineer');
		$result = Shopp::array_filter_keys($source, $mask);

		$this->assertTrue( 2 === count($result) );
		$this->assertTrue( in_array('Simon Pegg', $result) );
	}

	public function test_convert_unit() {
		$this->assertTrue( 12.7 == Shopp::convert_unit(5, 'cm', 'in') );
		$this->assertTrue( 5 == Shopp::convert_unit(12.7, 'in', 'cm') );
		$this->assertTrue( 99.208 < Shopp::convert_unit(45, 'lb', 'kg') );
		$this->assertTrue( 99.209 > Shopp::convert_unit(45, 'lb', 'kg') );
		$this->assertTrue( 0 == Shopp::convert_unit(400, 'lb', 'splargons'));
	}

	/**
	 * Seems ini_sets for suhosin properties will not work on all runtimes, therefore we can't really simulate failure
	 * since we can't guarantee Suhosin being available in all test environments.
	 */
	public function test_suhosin_warning() {
		$is_bool = is_bool(Shopp::suhosin_warning());
		$this->assertTrue($is_bool);
	}

	public function test_crc16() {
		$tests = array(
			'But among the women of Mars, we saw none of these curious, and to our eyes monstrous, differences of development.',
			'One word of explanation may be needed concerning the failure of the Martians, with all their marvellous powers, to invent electrical ships like those of Mr. Edison and engines of destruction comparable with our disintegrators.',
			'The entire force of the terrible engine, almost capable of destroying a fort, fell upon the Martian Emperor, and not merely blew him into a cloud of atoms, but opened a great cavity in the ground on the spot where he had stood.'
		);
		$checks = array();

		for ($pass = 1; $pass < 3; $pass++) {
			foreach ($tests as $data) {
				$check = Shopp::crc16($data);

				$this->assertTrue( is_string($data) && ! empty($check) ); // Non-empty string?
				if (1 === $pass) $this->assertFalse(in_array($check, $checks)); // "Unique"?
				if (2 === $pass) $this->assertTrue(in_array($check, $checks)); // Consistent?

				$checks[] = $check;
			}
		}
	}

	public function test_remove_class_actions() {
		add_action('shopp_test_action_to_remove', array($this, 'uncallable_method'));
		add_action('shopp_test_action_to_ignore', array($this, 'uncallable_method'));

		$this->assertTrue(has_action('shopp_test_action_to_remove'));
		$this->assertTrue(has_action('shopp_test_action_to_ignore'));

		Shopp::remove_class_actions('shopp_test_action_to_remove', __CLASS__);
		$this->assertFalse(has_action('shopp_test_action_to_remove'));
		$this->assertTrue(has_action('shopp_test_action_to_ignore'));
	}

	public function test_currency_format() {
		$expected = array_flip(array('cpos', 'currency', 'precision', 'decimals', 'thousands', 'grouping'));
		$format = Shopp::currency_format();
		$anticipated_keys = array_intersect_key($format, $expected);

		$this->assertTrue(count($expected) === count($format)); // Right number of keys
		$this->assertTrue(count($expected) === count($anticipated_keys)); // Actual key names are as anticipated

		$new_currency = 'Intergalactic Talents'; // Mars has already adopted this standard
		$format = Shopp::currency_format(array('currency' => $new_currency));
		$this->assertTrue($new_currency === $format['currency']);
	}

	/**
	 * @depends test_currency_format
	 */
	public function test_floatval() {
		$format = Shopp::currency_format();
		$symbol = $format['currency'];
		$monetary_value = $symbol . '65.952';

		$float_value = Shopp::floatval($monetary_value); // Rounding is on by default
		$this->assertTrue(is_float($float_value));
		$this->assertTrue(65.95 === $float_value);

		$float_value = Shopp::floatval($monetary_value, false); // Turn rounding off
		$this->assertTrue(is_float($float_value));
		$this->assertTrue(65.952 === $float_value);

		$monetary_value = $symbol . '78@456.23'; // On Mars the tradition of using an ampersat as a thousands separator persists
		$float_value = Shopp::floatval($monetary_value, false, array('thousands' => '@'));
		$this->assertTrue(is_float($float_value));
		$this->assertTrue(78456.23 === $float_value);
	}
}
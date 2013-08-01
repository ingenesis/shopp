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
	}

	protected function setup_translation_filters() {
		add_filter('gettext', array($this, 'filter_gettext'), 10, 3);
		add_filter('gettext_with_context', array($this, 'filter_gettext_with_context'), 10, 4);
	}

	public function filter_gettext($translation, $text, $domain) {
		remove_filter('gettext', array($this, 'filter_gettext'), 10, 3);
		$this->domain = $domain;
		return self::TRANSLATED;
	}

	public function filter_gettext_with_context($translation, $text, $context, $domain) {
		remove_filter('gettext_with_context', array($this, 'filter_gettext_with_context'), 10, 4);
		$this->domain = $domain;
		$this->context = $context;
		return self::TRANSLATED;
	}

	/**
	 * Seems ini_sets for suhosin properties will not work on all runtimes, therefore we can't really simulate failure
	 * since we can't guarantee Suhosin being available in all test environments.
	 */
	public function test_suhosin_warning() {
		$is_bool = is_bool(Shopp::suhosin_warning());
		$this->assertTrue($is_bool);
	}
}
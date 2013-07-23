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
}
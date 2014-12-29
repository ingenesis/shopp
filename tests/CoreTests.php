<?php
/**
 * Core Tests for Shopp library functions.
 *
 * @copyright Ingenesis Limited, 23 July 2013
 */
class CoreTests extends ShoppTestCase {
	const TRANSLATED = 'Translated!';

	protected static $template_dir = '';
	protected static $template_dir_ready = false;

	public $domain = '';
	public $context = '';
	public $email = array();


	public static function setUpBeforeClass() {
		self::create_tpl_dir();
		self::create_test_product();
	}

	/**
	 * Ensure there are no custom Shopp templates in the target directory (in advance of test_copy_templates()
	 * running.
	 */
	protected static function create_tpl_dir() {
		self::$template_dir = trailingslashit( sys_get_temp_dir() ) . 'shopp_tpls';
		mkdir(self::$template_dir);

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$template_dir));
		$failure = false;

		foreach ($files as $file)
			if ( $file->isFile() && ! unlink( $file->getPathname() ) ) $failure = true;

		self::$template_dir_ready = ( ! $failure );
	}

	protected static function create_test_product() {
		shopp_add_product(array(
			'name' => 'Shopp-branded Plain White Tee',
			'publish' => array('flag' => true),
			'featured' => true,
			'summary' => 'Starfleet inspired standard issue of the Shopp crew.',
			'description' => 'Designed to inspire fear and envy among the WordPress community.',
			'tags' => array('terms' => array('Starfleet', 'Shopp')),
			'variants' => array(
				'menu' => array(
					'Size' => array('Small','Medium','Large','Brikar')
				),
				0 => array(
					'option' => array('Size' => 'Small'),
					'type' => 'Shipped',
					'price' => 19.99,
					'sale' => array('flag'=>true, 'price' => 9.99),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 5,
						'sku' => 'SFU-001-S'
					)
				),
				1 => array(
					'option' => array('Size' => 'Medium'),
					'type' => 'Shipped',
					'price' => 22.55,
					'sale' => array('flag'=>true, 'price' => 19.99),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 15,
						'sku' => 'SFU-001-M'
					)
				),
				2 => array(
					'option' => array('Size' => 'Large'),
					'type' => 'Shipped',
					'price' => 32.95,
					'sale' => array('flag'=>true, 'price' => 24.95),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 0.1, 'length' => 0.3, 'width' => 0.3, 'height' => 0.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 1,
						'sku' => 'SFU-001-L'
					)
				),
				3 => array(
					'option' => array('Size' => 'Brikar'),
					'type' => 'Shipped',
					'price' => 55.00,
					'sale' => array('flag'=>true, 'price' => 35.00),
					'shipping' => array('flag' => true, 'fee' => 0, 'weight' => 2.1, 'length' => 0.3, 'width' => 0.9, 'height' => 1.1),
					'inventory' => array(
						'flag' => true,
						'stock' => 1,
						'sku' => 'SFU-001-B'
					)
				),

			)
		));
	}

	static function tearDownAfterClass () {
		$Product = shopp_product('shopp-branded-plain-white-tee', 'slug');
		shopp_rmv_product($Product->id);
	}

	public function test_unsupported () {
		$this->assertTrue(defined('SHOPP_UNSUPPORTED'));
	}

	public function test_translate() {
		$this->setup_translation_filters();

		$translation = Shopp::translate('Some of the colonists objected to having an anatomically correct android running around without any clothes on');
		$this->assertTrue($translation === self::TRANSLATED);
		$this->assertTrue($this->domain === 'Shopp');
		$this->assertEmpty($this->context);

		$translation = Shopp::translate("Who knows if we're even dead or alive?", "Geordi La Forge's philosophy");
		$this->assertTrue($translation === self::TRANSLATED);
		$this->assertTrue($this->context === "Geordi La Forge's philosophy");
		$this->assertTrue($this->domain === 'Shopp');
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
		$this->assertTrue( $this->domain === 'Shopp' );
	}

	public function test__m() {
		$this->setup_translation_filters();

		// Confirm that the string is translated first of all
		$string = '"I have her tongue recognized! The language that she speaks, the roots of the great Indo-European, or Aryan stock, contains."';
		$translation = Shopp::_m($string);
		$this->assertCount( 1, $translation ); // An array-like structure containing one element (the above line of text) should be returned
		$this->assertEquals( self::TRANSLATED, current($translation)->gist ); // It should have passed through the usual WP l10n filters

		// Confirm Markdown-like functionality
		$string = '"How she here came, so many _millions of miles_ from the earth, a great mystery is."';
		$expected = '<p>"How she here came, so many <em>millions of miles</em> from the earth, a great mystery is."</p>';
		$translation = Shopp::_m($string);
		$this->assertEquals( $expected, $translation->getHtml() );
	}

	/**
	 * @depends test__m
	 */
	public function test__em() {
		$string = 'This announcement of the Heidelberg professor stirred us all most profoundly. _It not only deepened our interest in the beautiful girl whom we had rescued, but, in a dim way, it gave us reason to hope that we should yet discover some means of mastering the Martians by dealing them a blow from within._';
		$expected = '<p>This announcement of the Heidelberg professor stirred us all most profoundly. <em>It not only deepened our interest in the beautiful girl whom we had rescued, but, in a dim way, it gave us reason to hope that we should yet discover some means of mastering the Martians by dealing them a blow from within.</em></p>';

		ob_start();
		Shopp::_em($string);
		$translation = ob_get_clean();

		$this->assertEquals( $expected, $translation );
	}

	public function test__mx() {
		$this->setup_translation_filters();

		// Confirm that the string is translated first of all
		$string = 'It had been expected, the reader will remember, that the %s whom we had made prisoner on the asteroid, _might_ be of use to us in a similar way.';
		$part = 'Martian';
		$context = "Edison's Conquest of Mars";

		$translation = Shopp::_mx($string, $context, $part);
		$this->assertEquals( self::TRANSLATED, current($translation)->gist );
		$this->assertTrue( $this->context === $context );
		$this->assertTrue( $this->domain === 'Shopp' );

		// Confirm Markdown-like functionality
		$string = 'For that reason great efforts had been made to acquire _%s_ language, and _considerable progress_ had been effected in that direction.';
		$part = 'his';
		$context = "Edison's Conquest of Mars";
		$expected = '<p>For that reason great efforts had been made to acquire <em>his</em> language, and <em>considerable progress</em> had been effected in that direction.</p>';

		$translation = Shopp::_mx($string, $context, $part);
		$this->assertEquals( $expected, $translation->getHtml() );
	}

	/**
	 * @depends test__mx
	 */
	public function test__emx() {
		$string = 'But from the moment of our arrival at Mars itself, and especially %s the battles began, the prisoner had resumed his _savage_ and _uncommunicative_ disposition.';
		$part = 'after';
		$context = "Edison's Conquest of Mars";
		$expected = '<p>But from the moment of our arrival at Mars itself, and especially after the battles began, the prisoner had resumed his <em>savage</em> and <em>uncommunicative</em> disposition.</p>';

		ob_start();
		Shopp::_emx($string, $context, $part);
		$translation = ob_get_clean();

		$this->assertEquals( $expected, $translation );
	}

	public function test__d() {
		// Basic test assuming defaults (English language)
		$hogmanay_2013 = mktime(1, 1, 1, 12, 31, 2013); // Final Tuesday in December
		$this->assertEquals( 'Tuesday December', Shopp::_d('l F', $hogmanay_2013) );

		// Is it being passed through the l10n functions?
		$this->setup_translation_filters();
		$this->domain = '';
		$this->assertEquals( 'Tuesday December', Shopp::_d('l F', $hogmanay_2013) ); // Our translation filter is "run once" so we expect the final string output here to actually be the same
		$this->assertEquals( 'Shopp', $this->domain );
	}

	public function test__jse() {
		$this->setup_translation_filters();

		ob_start();
		Shopp::_jse('How an outlaw, such as he evidently was, who had been caught in the act of robbing the Martian gold mines, could expect to escape punishment on returning to his native planet it was difficult to see.');
		$translation = ob_get_clean();

		$this->assertEquals( '"' . self::TRANSLATED . '"', $translation ); // Translation will be a quoted string literal
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

	/**
	 * @todo add additional tests to ensure correct output, discuss with jond
	 */
	public function test_auto_ranges() {
		$set_of_4 = Shopp::auto_ranges(150, 5000, 100, 4);
		$set_of_7 = Shopp::auto_ranges(150, 5000, 100, 20); // Though we're requesting 20 steps it should cap this at 7

		$this->assertCount( 4, $set_of_4 );
		$this->assertCount( 7, $set_of_7 );
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

	public function test_copy_templates() {
		// Can we perform this test?
		if ( ! self::$template_dir_ready || ! is_writeable( self::$template_dir ) )
			$this->markTestSkipped('The template directory must be empty and writeable.');

		// Yes? Do it!
		$source = trailingslashit(SHOPP_PATH) . 'templates';
		$target = self::$template_dir;
		Shopp::copy_templates($source, $target);

		// Done? Test it!
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$template_dir));
		$list = array();

		foreach ($files as $file) {
			if ( ! $file->isFile() ) continue;
			$list[] = $file->getFilename();
		}

		$expected = array(
			'account.php',
			'account-downloads.php',
			'account-orders.php',
			'account-profile.php',
			'cart.php',
			'catalog.php',
			'category.php',
			'checkout.php',
			'confirm.php',
			'email.css',
			'email.php',
			'email-order.php',
			'email-shipped.php',
			'errors.php',
			'login.php',
			'login-recover.php',
			'product.php',
			'receipt.php',
			'shopp.css',
			'sidecart.php',
			'sideproduct.php',
			'summary.php',
			'thanks.php'
		);

		foreach ($expected as $tpl_override) {
			// Check that the expected templates made it across
			$this->assertContains($tpl_override, $list);

			// Check that the file header doc was stripped
			$sample = file_get_contents( trailingslashit(self::$template_dir) . $tpl_override);
			$header_stripped = ( 0 === preg_match('/^<\?php\s\/\*\*\s+(.*?\s)*?\*\*\/\s\?>\s/', $sample) );
			$this->assertTrue($header_stripped);
		}
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

	public function test_datecalc () {
		$thanksgiving = Shopp::datecalc(4, 4, 11, 2013);
		$this->assertTrue('2013-11-28' === date('Y-m-d', $thanksgiving));

		$mayan_apocalypse = Shopp::datecalc(3, 5, 12, 2012);
		$this->assertTrue('2012-12-21' === date('Y-m-d', $mayan_apocalypse));
	}

	public function test_date_format_order() {
		$this->force_uk_date_style();
		$format = Shopp::date_format_order();
		$expected = array_flip( array('day', 'month', 'year', 's0', 's1', 's2') );
		$present = array_intersect_key($expected, $format);
		$this->assertCount(6, $present);
	}

	public function test_debug_caller() {
		// Should end up with something akin to "debug_hop_final, debug_hop_initial, ..."
		$trace = $this->debug_hop_initial();

		$initial_hop = (false !== strpos($trace, 'debug_hop_initial'));
		$final_hop = (false !== strpos($trace, 'debug_hop_final'));

		$this->assertTrue($initial_hop);
		$this->assertTrue($final_hop);
	}

	protected function debug_hop_initial() {
		return self::debug_hop_final();
	}

	protected function debug_hop_final() {
		return Shopp::debug_caller();
	}

	/**
	 * Shopp's toast to Wills and Kate and young boy George. Also useful to ensure date formatting functions work
	 * as expected.
	 */
	protected function force_uk_date_style() {
		add_filter('pre_option_date_format', array($this, 'use_uk_date_format') );
	}

	public function use_uk_date_format() {
		remove_filter('pre_option_date_format', array($this, 'use_uk_date_format') );
		return 'jS F Y';
	}

	public function test_duration() {
		$begin = mktime(0, 0, 0, 1, 25, 1759); // Birth of Rabby Burns

		$end = mktime(0, 0, 0, 7, 21, 1796); // Death of Rabby Burns

		$poetic_era = (int)($end - $begin) / 86400; // 13,692 days
		$this->assertTrue( (int) $poetic_era === (int) Shopp::duration($begin, $end));
	}

	public function test_esc_attrs() {
		$source = array( 'nested' => array( '<child>', '<brat>' ) );
		$target = Shopp::esc_attrs($source);

		foreach ($target['nested'] as $checkval)
			$this->assertTrue(1 < strpos($checkval, '&gt;'));
	}

	/**
	 * Shopp::filter_dotfiles operates as a callback, so matches should return false and vice versa.
	 */
	public function test_filter_dotfiles() {
		foreach ( array('.', '.htaccess') as $match ) $this->assertFalse(Shopp::filter_dotfiles($match));
		$this->assertTrue(Shopp::filter_dotfiles('image.png'));
	}

	public function test_findfile () {
		$files = array();

		// There is at least one of these
		$result = Shopp::findfile('ball.png', SHOPP_PATH);
		$this->assertTrue($result);

		// We can expect at least 4 of these (Shopp 1.3)
		$result = Shopp::findfile('product.php', SHOPP_PATH, $files);
		$this->assertTrue($result);
		$this->assertGreaterThanOrEqual(4, count($files));

		// We may wish it to operate efficiently and stop at the first match
		$files = array();
		$result = Shopp::findfile('admin.php', ABSPATH, $files, false);
		$this->assertTrue($result);
		$this->assertCount(1, $files);

		// It should fail gracefully even when given ridiculous params
		$result = Shopp::findfile('@*Nyota Uhura', '/unworkable/~path');
		$this->assertFalse($result);
	}

	/**
	 * We use the string-matches-format assertion here since depending on which tool is used behind
	 * the scenes to determine the mime type there may or may not be charset information appended.
	 */
	public function test_file_mimetype() {
		$css = SHOPP_UNITTEST_DIR . '/bootstrap.css';
		$png = SHOPP_UNITTEST_DIR . '/1.png';
		$zip = SHOPP_UNITTEST_DIR . '/archive.zip';

		$this->assertStringMatchesFormat('text/css%A', Shopp::file_mimetype($css));
		$this->assertStringMatchesFormat('image/png%A', Shopp::file_mimetype($png));
		$this->assertStringMatchesFormat('application/zip%A', Shopp::file_mimetype($zip));
	}

	public function test_force_ssl() {
		$url = Shopp::force_ssl('http://shopplugin.net', true);
		$this->assertStringMatchesFormat('https://%A', $url);
	}

	public function test_gateway_path() {
		$path = '/var/public_html/wp-content/plugins/shopp/gateways/2Checkout/2Checkout.php';
		$expected = '2Checkout/2Checkout.php';
		$this->assertTrue($expected === Shopp::gateway_path($path));
	}

	public function test_ini_size() {
		$size = Shopp::ini_size('upload_max_filesize');
		$this->assertFalse( empty($size) );
	}

	public function test_input_attrs() {
		$this->assertEquals( ' class="magical"', Shopp::inputattrs(array('class' => 'magical')) );
		$this->assertEquals( ' class="magical &quot; onclick=&quot;alert()&quot; class=&quot;"', Shopp::inputattrs(array('class' => 'magical " onclick="alert()" class="')));
	}

	public function test_is_robot() {
		$restore = $_SERVER['HTTP_USER_AGENT'];

		$_SERVER['HTTP_USER_AGENT'] = '';
		$this->assertFalse(Shopp::is_robot());

		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot';
		$this->assertTrue(Shopp::is_robot());

		$_SERVER['HTTP_USER_AGENT'] = $restore;
	}

	public function test_linkencode() {
		$source = 'Spaces and ampersats @ may constitute less than 10% of typical strings.';
		$desired = 'Spaces%20and%20ampersats%20%40%20may%20constitute%20less%20than%2010%25%20of%20typical%20strings.';
		$this->assertTrue($desired === Shopp::linkencode($source));
	}

	public function test_locate_template() {
		$path = Shopp::locate_template(array('email-reorder.php', 'email-order.php', 'email.php'));
		$this->assertStringMatchesFormat('%Aemail-order.php', $path);
	}

	public function test_maintenance() {

		shopp_set_setting('db_version', ShoppVersion::db());
		$originalmode = shopp_setting('maintenance');

		shopp_set_setting('maintenance', 'on');
		$this->assertTrue(Shopp::maintenance());

		shopp_set_setting('maintenance', '0');
		$this->assertFalse(Shopp::maintenance());

		shopp_set_setting('maintenance', $originalmode);
		shopp_rmv_setting('db_version');

	}

	public function test_mktimestamp() {
		$stamp = mktime(0, 0, 0, 4, 5, 2063);
		$mysql = date('Y-m-d H:i:s', $stamp);
		$this->assertTrue($stamp === Shopp::mktimestamp($mysql));
	}

	public function test_mkdatetime() {
		$stamp = mktime(0, 0, 0, 4, 5, 2063);
		$mysql = date('Y-m-d H:i:s', $stamp);
		$this->assertTrue($mysql === Shopp::mkdatetime($stamp));
	}

	public function test_mk24hour() {
		$this->assertTrue(0 === Shopp::mk24hour('12', 'AM'));
		$this->assertTrue(12 === Shopp::mk24hour('12', 'PM'));
		$this->assertTrue(16 === Shopp::mk24hour('4', 'PM'));
	}

	public function test_menuoptions() {
		$html = Shopp::menuoptions(array('13' => 'Mars', '14' => 'Orion'), '13', array('13', '14'));
		$select = simplexml_load_string("<select> $html </select>");

		$this->assertTrue(isset($select->option));
		$this->assertEquals(2, count($select->option));

		$this->assertTrue(isset($select->option[0]['selected']));
		$this->assertEquals('Mars', (string) $select->option[0]);
		$this->assertEquals('13', (string) $select->option[0]['value']);

		$this->assertFalse(isset($select->option[1]['selected']));
		$this->assertEquals('Orion', (string) $select->option[1]);
		$this->assertEquals('14', (string) $select->option[1]['value']);
	}

	public function test_numeric_format() {
		$this->assertEquals('9,876.54', Shopp::numeric_format(9876.5421));
		$this->assertEquals('9876,5421',  Shopp::numeric_format(9876.5421, 4, ',', '', array(4)));
	}

	/**
	 * @depends test_numeric_format
	 */
	public function test_money() {
		$format = array(
			'precision' => 3,
			'decimals' => ',',
			'thousands' => ' ',
			'grouping' => 3,
			'cpos' => true,
			'currency' => '£'
		);

		$this->assertTrue('£6 543,210' === Shopp::money(6543.21, $format));
	}

	public function test_parse_phone() {
		$phone = Shopp::parse_phone('(100) 700 9000');
		$this->assertCount(4, $phone);
		$this->assertEquals('100', $phone['area']);
		$this->assertEquals('700', $phone['prefix']);
		$this->assertEquals('9000', $phone['exchange']);
		$this->assertEquals('1007009000', $phone['raw']);
	}

	public function test_phone() {
		$correct = '(987) 654-3210';
		$this->assertEquals($correct, Shopp::phone('9876543210'));
		$this->assertEquals($correct, Shopp::phone('+987-654 x3210'));
	}

	public function test_percentage() {
		$this->assertEquals('3.1416%', Shopp::percentage(M_PI, array('precision' => 4)));
		$this->assertEquals('3,1415927%', Shopp::percentage(M_PI, array('precision' => 7, 'decimals' => ',')));
		$this->assertEquals('2,718.28%', Shopp::percentage(M_E * 1000, array('precision' => 2)));
		$this->assertEquals('2 718.28%', Shopp::percentage(M_E * 1000, array('precision' => 2, 'thousands' => ' ')));
	}

	public function test_raw_request_url() {
		// Keep original values
		$host = $_SERVER['HTTP_HOST'];
		$request = $_SERVER['REQUEST_URI'];

		// Change for test purposes
		$_SERVER['HTTP_HOST'] = 'example.com';
		$_SERVER['REQUEST_URI'] = '/shop/category/space-boots/';

		$expected = 'http://example.com/shop/category/space-boots/';
		$this->assertEquals($expected, Shopp::raw_request_url());

		// Clean up
		$_SERVER['HTTP_HOST'] = $host;
		$_SERVER['REQUEST_URI'] = $request;
	}

	public function test_readable_file_size() {
		$this->assertEquals('4 KB', Shopp::readableFileSize('4096'));
		$this->assertEquals('24.3 KB', Shopp::readableFileSize('24832'));
		$this->assertEquals('588.1 MB', Shopp::readableFileSize('616628224'));
		$this->assertEquals('2.3 GB', Shopp::readableFileSize('2466516992'));
	}

	public function test_roundprice() {
		$this->assertEquals('54.99', Shopp::roundprice('54.985', array('precision' => 2)));
		$this->assertEquals('54.985', Shopp::roundprice('54.985', array('precision' => 3)));
	}

	public function test_rsa_encrypt() {
		$pem = file_get_contents(SHOPP_UNITTEST_DIR . '/data/security.pem');
		$encrypted = Shopp::rsa_encrypt('Counselor Troi is half-Betazoid.', $pem);
		$this->assertTrue(!empty($encrypted));
	}

	public function test_set_wp_query_var() {
		global $wp, $wp_query;
		Shopp::set_wp_query_var('custom_property', 'Shiney new space boots');

		$in_wp = (isset($wp->query_vars['custom_property']) and 'Shiney new space boots' === $wp->query_vars['custom_property']);
		$in_wp_query = (isset($wp_query->query_vars['custom_property']) and 'Shiney new space boots' === $wp_query->query_vars['custom_property']);

		$this->assertTrue($in_wp);
		$this->assertTrue($in_wp_query);
	}

	/**
	 * @depends test_set_wp_query_var
	 */
	public function test_get_wp_query_var() {
		Shopp::set_wp_query_var('warp_factor', '9');
		$this->assertTrue('9' === Shopp::get_wp_query_var('warp_factor'));
	}

	public function test_daytimes() {
		$this->assertEquals('3d', Shopp::daytimes('72h'));
		$this->assertEquals('4d', Shopp::daytimes('2d', '48h'));
		$this->assertEquals('8d', Shopp::daytimes('1w', '1d'));
		$this->assertEquals('40d', Shopp::daytimes('1m', '1w', '3d'));
	}

	public function test_email() {
		$valid_tpl_path = SHOPP_UNITTEST_DIR . '/data/email.php';
		$mail_success = Shopp::email( $valid_tpl_path, array() );
		$this->assertTrue(is_bool($mail_success));
	}

	public function test_rss() {
		$data = array(
			'link' => 'http://shopplugin.net/mars',
			'title' => 'We are going to Mars',
			'description' => 'Interesting news about our products.',
			'rss_language' => 'en_CA',
			'sitename' => 'Martian Shopping'
		);
		$rss = Shopp::rss($data);
		$this->assertValidMarkup($rss);
	}

	public function test_pagename() {
		$this->assertEquals('mars-admin-screen.php', Shopp::pagename('index.php/mars-admin-screen.php')); // IIS rewrites
		$this->assertEquals('mars-admin-screen.php', Shopp::pagename('mars-admin-screen.php'));
	}

	public function test_parse_options() {
		$options = Shopp::parse_options('status=%22Raised+eyebrows%22&parse=away'); // Encoded quotes
		$this->assertCount(2, $options);
		$this->assertEquals('"Raised eyebrows"', $options['status']); // Quotes should be decoded
	}

	public function test_taxrate() {
		$Product = shopp_product('shopp-branded-plain-white-tee', 'slug');
		$rate = Shopp::taxrate($Product);
		$this->assertEquals(0.1, $rate); // 10% is set during test suite bootstrap
	}

	public function test_template_prefix() {
		$this->filter_prefixes_once();
		$this->assertEquals('mars/cart.php', Shopp::template_prefix('cart.php'));
		$this->assertEquals('shopp/account.php', Shopp::template_prefix('account.php'));
	}

	protected function filter_prefixes_once() {
		add_filter('shopp_template_directory', array($this, 'alter_tpl_prefix'));
	}

	public function alter_tpl_prefix($prefix) {
		remove_filter('shopp_template_directory', array($this, 'alter_tpl_prefix'));
		return 'mars';
	}

	public function test_template_url() {
		$url = Shopp::template_url('shopp.css');
		$expected = SHOPP_PLUGINURI . '/templates/shopp.css';
		$this->assertEquals($expected, $url);
	}

	/**
	 * Largely taken care of by PrettyURLTests so we've got only a cursory test in here.
	 */
	public function test_url() {
		$url = Shopp::url();
		$expected = 'http://' . WP_TESTS_DOMAIN . '/?shopp_page=shop';
		$this->assertEquals($expected, $url);
	}

	public function test_str_true() {
		// str_true() has a number of defaults but should also be case insensitive and losely typed
		$true_defaults = array('yes', 'y', 'true', '1', 'on', 'open');
		$true_variants = array('Yes', 'Y', 'TRUE', 1, 'On', 'Open');

		foreach ( array_merge($true_defaults, $true_variants) as $is_true )
			$this->assertTrue(Shopp::str_true($is_true));

		// Should be possible to suggest an alternative set of defaults
		$new_defaults = array('aye', 'defo', 'for reals');
		$test_strs = array('AYE', 'Defo', 'For Reals');

		foreach ( $test_strs as $is_true)
			$this->assertTrue(Shopp::str_true($is_true, $new_defaults));

		// What if alternative defaults are provided and don't incorporate the original defaults
		$this->assertFalse(Shopp::str_true($true_defaults[1], $new_defaults));

		// All other values besides defaults ought to return false
		$possible_falses = array('no', 0, '0', 'no way', 'Please leave the bridge immediately.');

		foreach ( $possible_falses as $is_false )
			$this->assertFalse(Shopp::str_true($is_false));
	}

	public function test_valid_input() {
		$valid_types = array('text', 'hidden', 'checkbox', 'radio', 'button', 'submit');
		foreach ( $valid_types as $input_type ) $this->assertTrue(Shopp::valid_input($input_type));

		$invalid_types = array('password', 'secret', 'plugin-x-data-blah');
		foreach ( $invalid_types as $input_type ) $this->assertFalse(Shopp::valid_input($input_type));
	}

	public function test_scan_money_format() {
		$specs = array();
		$formats = array(
			'$#,###.##', // "Conventional"
			'# ###,##', // Space as the thousands separator
			'£#,###.###', // Precision of 3 decimal places
			'#,###.## £', // Trailing currency symbol
			'##,###.## Yatts', // Irregular groupings (ie, Indian format)
			'#,###. Kibbles' // No decimals in use
		);

		foreach ( $formats as $format )
			$specs[$format] = Shopp::scan_money_format($format);

		// Check all is in order with our conventional Canada/US style format
		$this->assertTrue($specs['$#,###.##']['cpos']);
		$this->assertEquals('$', $specs['$#,###.##']['currency']);
		$this->assertEquals('.', $specs['$#,###.##']['decimals']);
		$this->assertEquals(',', $specs['$#,###.##']['thousands']);
		$this->assertEquals(2, $specs['$#,###.##']['precision']);
		$this->assertEquals(3, $specs['$#,###.##']['grouping'][0]);

		// Symbol-less, space for thousands and comma for decimals
		$this->assertFalse($specs['# ###,##']['cpos']);
		$this->assertEquals('', $specs['# ###,##']['currency']);
		$this->assertEquals(',', $specs['# ###,##']['decimals']);
		$this->assertEquals(' ', $specs['# ###,##']['thousands']);

		// Pounds sterling and decimal precision of 3
		$this->assertTrue($specs['£#,###.###']['cpos']);
		$this->assertEquals('£', $specs['£#,###.###']['currency']);
		$this->assertEquals(3, $specs['£#,###.###']['precision']);

		// Trailing currency symbols
		$this->assertFalse($specs['#,###.## £']['cpos']);
		$this->assertEquals('£', $specs['#,###.## £']['currency']);

		// Irregular groupings and long currency symbols
		$this->assertEquals('Yatts', $specs['##,###.## Yatts']['currency']);
		$this->assertEquals(3, $specs['##,###.## Yatts']['grouping'][0]);
		$this->assertEquals(2, $specs['##,###.## Yatts']['grouping'][1]);

		// No decimals (ensure precision, groupings, thousands separator etc are not confused)
		$this->assertEquals(',', $specs['#,###. Kibbles']['thousands'], json_encode($specs['#,###. Kibbles']));
		$this->assertEquals(3, $specs['#,###. Kibbles']['grouping'][0]);
		$this->assertEquals(0, $specs['#,###. Kibbles']['precision']);
		$this->assertEquals('.', $specs['#,###. Kibbles']['decimals']);
	}
}
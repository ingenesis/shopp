<?php
/**
 * Search.php
 *
 * A set of classes for handling search related processes.
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, March  6, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.1
 * @subpackage search
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * IndexProduct class
 *
 * Generates a set of indexes for all Product property data
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage search
 **/
class IndexProduct {

	public $Product = false;
	public $properties = array(
		'name', 'prices', 'summary', 'description', 'specs', 'categories', 'tags'
	);

	/**
	 * Loads a specified product for indexing
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function __construct ($id) {
		$this->Product = new ShoppProduct($id);
		$this->Product->load_data(array('prices', 'specs', 'categories', 'tags'));
	}

	/**
	 * Saves product property indexes to the index table
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function index () {
		$properties = apply_filters('shopp_index_product_properties', $this->properties);
		foreach ( $properties as $property ) {
			switch ( $property ) {
				case "prices":
					$prices = array();
					foreach ( $this->Product->prices as $price ) {
						if ($price->type == "N/A") continue; // Skip disabled pricelines
						$prices[] = "$price->label $price->sku";
					}
					$content = join(' ', $prices);
					break;
				case "specs":
					$specs = array();
					foreach ( $this->Product->specs as $Spec )
						$specs[] = "$Spec->name $Spec->value";
					$content = join(' ', $specs);
					break;
				case "categories":
					$categories = array();
					foreach ( $this->Product->categories as $Category )
						$categories[] = $Category->name;
					$content = join(' ', $categories);
					break;
				case "tags":
					$tags = array();
					foreach ( $this->Product->tags as $Tag )
						$tags[] = $Tag->name;
					$content = join(' ', $tags);
					break;
				default: $content = $this->Product->{$property}; break;
			}
			$Indexer = new ContentIndex($this->Product->id, $property);
			$Indexer->save($content);
		}
	}

}

/**
 * ContentIndex class
 *
 * Builds a forward index of product content and
 * manages it in the database.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
class ContentIndex extends ShoppDatabaseObject {
	static $table = "index";

	private $_loaded = false;

	/**
	 * ContentIndex constructor
	 *
	 * @author Jonathan Davis
	 *
	 * @param int $product The ID of the indexed product
	 * @param string $type The type of index to load
	 * @return void
	 **/
	function __construct ( $product, $type ) {
		$this->init(self::$table);
		$this->load($product, $type);
	}

	/**
	 * Load an existing product property index if it exists
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $product Id of the indexed product
	 * @param string $type Type of product property indexed
	 * @return void
	 **/
	function load ( $product = false, $type = false ) {
		$this->product = $product;
		$this->type = $type;
		if ( empty($product) || empty($type) ) return false; // Nothing to load

		$r = sDB::query("SELECT id,created FROM $this->_table WHERE product='$product' AND type='$type' LIMIT 1");
		if ( ! empty($r->id) ) {
			$this->id = $r->id;
			$this->created = mktimestamp($r->created);
			$this->_loaded = true;
		}
	}

	/**
	 * Process content into an index and save it
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $content The content to index
	 * @return void
	 **/
	function save () {

		list($content,) = func_get_args();
		$content = apply_filters('shopp_index_content', $content, $this);
		if ( empty($this->product) || empty($this->type) || empty($content) )
			return false;

		$factoring = Lookup::index_factors();
		if ( isset($factoring[ $this->type ])) $this->factor = $factoring[ $this->type ];
		else $this->factor = 1;

		$this->terms = $content;

		parent::save();

	}

} // END class ContentIndex

if ( ! class_exists('SearchParser', false) ):
/**
 * SearchParser class
 *
 * Prepares a search query for natural language searching
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage search
 **/
class SearchParser extends SearchTextFilters {

	/**
	 * Setup the filtering for search query parsing
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function __construct () {
		add_filter('shopp_search_query', array('SearchParser', 'MarkupFilter'));
		add_filter('shopp_search_query', array('SearchParser', 'CurrencyFilter'));
		add_filter('shopp_search_query', array('SearchParser', 'StopFilter'));
		add_filter('shopp_search_query', array('SearchParser', 'AccentFilter'));
		add_filter('shopp_search_query', array('SearchParser', 'LowercaseFilter'));
		add_filter('shopp_search_query', array('SearchParser', 'NormalizeFilter'));
	}

	/**
	 * Parse price matching queries into a processing object
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $query A search query string
	 * @return object The price matching object
	 **/
	static function PriceMatching ( $query ) {
		$pricematch = self::_pricematch_regex();
		preg_match_all("/$pricematch/", $query, $matches, PREG_SET_ORDER);
		if ( empty($matches) ) return false;
		$_->op = $matches[0][0][0];
		$_->op = ( in_array($_->op, array("<", ">")) ) ? $_->op : '';
		$_->min = Shopp::floatval($matches[0][1]);
		$_->max = Shopp::floatval($matches[0][4]);
		$_->target = $_->min;
		if ( $_->max > 0 ) $_->op = "-"; // Range matching

		// Roundabout price match
		if ( empty($_->op) && empty($_->max) ) {
			$_->min = $_->target - ( $_->target / 2 );
			$_->max = $_->target + ( $_->target / 2 );
		}

		return $_;
	}

}
endif;

if ( ! class_exists('BooleanParser', false) ):
/**
 * BooleanParser class
 *
 * Prepares a search query for boolean matches
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage search
 **/
class BooleanParser extends SearchTextFilters {

	/**
	 * Setup the filtering for query parsing
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function __construct () {
		add_filter('shopp_boolean_search', array('BooleanParser', 'MarkupFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'CurrencyFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'AccentFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'StopFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'LowercaseFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'HyphensFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'NormalizeFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'StemFilter'));
		add_filter('shopp_boolean_search', array('BooleanParser', 'KeywordFilter'));
	}

}
endif;

if ( ! class_exists('ShortwordParser', false) ):
/**
 * ShortwordParser class
 *
 * Prepares a search query for shortword RegExp matching
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package shopp
 * @subpackage search
 **/
class ShortwordParser extends SearchTextFilters {

	/**
	 * Setup the filtering for shortword parsing
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function __construct () {
		add_filter('shopp_shortword_search', array('ShortwordParser', 'MarkupFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'CurrencyFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'AccentFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'LowercaseFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'ShortwordFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'StopFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'HyphensFilter'));
		add_filter('shopp_shortword_search', array('ShortwordParser', 'NormalizeFilter'));
	}

}
endif;

if ( ! class_exists('ContentParser', false) ):
class ContentParser extends SearchTextFilters {

	/**
	 * Setup the filtering for content parsing
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	function __construct () {
		add_filter('shopp_index_content', array('ContentParser', 'MarkupFilter'));
		add_filter('shopp_index_content', array('ContentParser', 'AccentFilter'));
		add_filter('shopp_index_content', array('ContentParser', 'LowercaseFilter'));
		add_filter('shopp_index_content', array('ContentParser', 'HyphensFilter'));
		add_filter('shopp_index_content', array('ContentParser', 'NormalizeFilter'));
		add_filter('shopp_index_content', array('ContentParser', 'StemFilter'));
	}

	/**
	 * Adds collapsed hyphenated words and expanded hyphenated words to the text for indexing.
	 *
	 * This addresses issue #2973 by making sure hypenated words (as found in SKUs and compound
	 * words) are collapsed and expanded. For example, "ice-skate" is collapsed to "iceskate"
	 * and expanded into two words "ice" and "skate" and added to the end of the indexed text.
	 *
	 * This helps handle hypenated SKUs like KSF-C005951A-TTRU which get added to the index as
	 * "KSFC005951ATTRU" and "KSF C005951A TTRU" to ensure total recall (classic movie: http://shopp.me/1oerMls).
	 *
	 * @since 1.3.5
	 *
	 * @param string $text The text with hypenated words to index
	 * @return string the text with hyphen collapsing and expansions
	 **/
	public static function HyphensFilter ( $text ) {

		// Find all hyphenated words
		preg_match_all("/\b\w+(\-\w+)+\b/", $text, $matched, PREG_SET_ORDER);

		foreach ( $matched as $match ) {
			list($hyphenated,) = $match;

			// hypens as spaces
			$text .= ' ' . str_replace('-', ' ', $hyphenated);

			// hyphens ignored
			$text .= ' ' .  str_replace('-', '', $hyphenated);

		}

		return $text;
	}

} // END class ContentParser
endif;

/**
 * SearchTextFilters class
 *
 * A foundational class for parsing a string text for
 * searching.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 **/
abstract class SearchTextFilters {

	/**
	 * Builds a regular express to match the current currency format
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param boolean $symbol (optional) Require currency symbol - required by default
	 * @return string The current currency regex pattern
	 **/
	public static function _currency_regex ($symbol=true) {
		$format = Shopp::currency_format();
		extract($format);

		$pre = ( $cpos ? '' . preg_quote($currency) . ( $symbol ? '' : '?' ) : '' );
		$amount = '[\d' . preg_quote($thousands) . ']+';
		$fractional = '(' . preg_quote($decimals) . '\d{' . $precision . '}?)?';
		$post = ( ! $cpos ? '' . preg_quote($currency) . ( $symbol ? '' : '?' ) : '' );
		return $pre . $amount . $fractional . $post;
	}

	/**
	 * Builds a regex pattern for price matching search queries
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string The price match query pattern
	 **/
	public static function _pricematch_regex () {
		$price = self::_currency_regex();
		$optprice = self::_currency_regex(false);
		return "[>|<]?\s?($price)(\-($optprice))?";
	}

	/**
	 * Strips HTML tags from the text
	 *
	 * Markup is not useful for indexing, so get rid of it to
	 * optimize index storage.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The text to process
	 * @return string text with markup tags removed
	 **/
	public static function MarkupFilter ( $text ) {
		return strip_tags($text);
	}

	/**
	 * Wrapper for transposing text to lowercase characters
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $string The text to transpose
	 * @return string Transposed text
	 **/
	public static function LowercaseFilter ( $text ) {
		return strtolower($text);
	}

	/**
	 * Removes stop words from the text
	 *
	 * Stop words are words that are common English words that
	 * are not particularly useful for searching.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The text to clean up
	 * @return string The cleaned text
	 **/
	public static function StopFilter ( $text ) {
		$stopwords = Lookup::stopwords();
		$replacements = implode('|', $stopwords);

		// Protect quoted strings
		$result = '';
		$unquoted = preg_split('/("[^"]*"|\'[^\']*\')/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

		while ( $unquoted )
			$result .= preg_replace("/\b($replacements)\b/", '', array_shift($unquoted)) . array_shift($unquoted);

		return $result;
	}

	/**
	 * Normalize the text
	 *
	 * Performs acronym & contraction collapsing and removes all other
	 * non-alphanumeric characters.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The text to normalize
	 * @return string normalized text
	 **/
	public static function NormalizeFilter ( $text ) {

		// Collapse words with periods, commas and apostrophes
		$text = preg_replace("/[\.\',]/", '', $text);

		// Translate any other non-word characters to spaces
		$text = preg_replace("/[^\w\d\s\p{L}\_\"]/u", ' ', $text);

		// Collapse the spaces
		$text = preg_replace("/\s+/m", ' ', $text);

		return trim($text);
	}

	/**
	 * Ignore hyphens in words
	 *
	 * @since 1.3.5
	 *
	 * @param string $text Text that might have hypens
	 * @return string text without hyphens
	 **/
	public static function HyphensFilter ( $text ) {
		return str_replace('-', '', $text);
	}

	/**
	 * Collates accented characters to plain text equivalents
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The text to convert
	 * @return string Converted text
	 **/
	public static function AccentFilter ( $text ) {
		if ( ! function_exists('remove_accents') )
			require( ABSPATH . WPINC . '/formatting.php' );
		return remove_accents($text);
	}

	/**
	 * Strips non-keywords from the query and adds boolean search markup
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The query string to parse
	 * @return string The boolean search string
	 **/
	public static function KeywordFilter ( $text ) {
		if ( ! defined('SHOPP_SEARCH_LOGIC') ) define('SHOPP_SEARCH_LOGIC', 'OR');
		$logic = strtoupper(SHOPP_SEARCH_LOGIC) == 'AND' ? '+' : '';

		$tokens = array();
		$token = strtok($text, ' ');
        while ( $token ) {
            // find double quoted tokens
            if ( '"' == $token{0} ) {
				$token .= ' ' . strtok('"') . '"';
				$tokens[] = $token;
			} else {
				$tokens[] = "$logic$token*";
			}
            $token = strtok(' ');
        }
		return implode(' ', $tokens);
	}

	/**
	 * Strips longer search terms from the query
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $text The query string to parse
	 * @return string The shortword search string
	 **/
	public static function ShortwordFilter ( $text ) {
		$text = preg_replace('/\b\w{4,}\b/', '', $text);
		$text = preg_replace('/ +/', '|', $text);
		return $text;
	}

	/**
	 * Removes price match queries from a search query
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The search query
	 * @return string search query without price search
	 **/
	public static function CurrencyFilter ( $text ) {
		$pricematch = self::_pricematch_regex();
		$text = preg_replace("/$pricematch/", '', $text);
		return $text;
	}

	/**
	 * Generates word stems that are added to the text
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $text The text to stem
	 * @return string The text plus the generated word stems
	 **/
	public static function StemFilter ( $text ) {
		// Filter out short words for stemming
		$source = preg_replace("/\b\w{1,3}\b/", '', $text);
		$_ = array();
		$token = strtok($source, ' ');
		while ($token) {
			$stem = PorterStemmer::Stem($token);
			if ( $stem != $token ) $_[] = $stem;
			$token = strtok(' ');
		}

		return ! empty($_) ? "$text " . join(' ', $_) : $text;
	}

} // END class SearchTextFilters

/**
 * PHP5 Implementation of the Porter Stemmer algorithm. Certain elements
 * were borrowed from the (broken) implementation by Jon Abernathy.
 *
 * @author Richard Heyes
 * @copyright Richard Heyes, 2005. All rights reserved. {@see http://www.phpguru.org/}
 * @since 1.1
 * @package shopp
 * @subpackage search
 **/
class PorterStemmer {

    private static $regex_consonant = '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)';
    private static $regex_vowel = '(?:[aeiou]|(?<![aeiou])y)';

    /**
     * Stems a word. Simple huh?
     *
	 * @author Richard Heyes
	 * @since 1.1
	 * @package shopp
     *
     * @param  $word Word to stem
     * @return string Stemmed word
     **/
    public static function Stem ( $word ) {
        if ( strlen($word) <= 2 )
            return $word;

        $word = self::step1ab($word);
        $word = self::step1c($word);
        $word = self::step2($word);
        $word = self::step3($word);
        $word = self::step4($word);
        $word = self::step5($word);

        return $word;
    }

    /**
     * Step 1
     **/
    private static function step1ab ( $word ) {
        // Part a
        if ( substr($word, -1) == 's') {
               self::replace($word, 'sses', 'ss')
            OR self::replace($word, 'ies', 'i')
            OR self::replace($word, 'ss', 'ss')
            OR self::replace($word, 's', '');
        }

        // Part b
        if ( substr($word, -2, 1) != 'e' OR !self::replace($word, 'eed', 'ee', 0) ) { // First rule
            $v = self::$regex_vowel;

            // ing and ed
            if ( preg_match("#$v+#", substr($word, 0, -3)) && self::replace($word, 'ing', '')
                OR preg_match("#$v+#", substr($word, 0, -2)) && self::replace($word, 'ed', '') ) { // Note use of && and OR, for precedence reasons

                // If one of above two test successful
                if (    !self::replace($word, 'at', 'ate')
                    AND !self::replace($word, 'bl', 'ble')
                    AND !self::replace($word, 'iz', 'ize')) {

                    // Double consonant ending
                    if (    self::doubleConsonant($word)
                        AND substr($word, -2) != 'll'
                        AND substr($word, -2) != 'ss'
                        AND substr($word, -2) != 'zz') {

                        $word = substr($word, 0, -1);

                    } else if (self::m($word) == 1 AND self::cvc($word)) {
                        $word .= 'e';
                    }
                }
            }
        }

        return $word;
    }


    /**
     * Step 1c
     *
     * @param string $word Word to stem
     **/
    private static function step1c ( $word ) {
        $v = self::$regex_vowel;

        if ( substr($word, -1) == 'y' && preg_match("#$v+#", substr($word, 0, -1)) ) {
            self::replace($word, 'y', 'i');
        }

        return $word;
    }


    /**
     * Step 2
     *
     * @param string $word Word to stem
     **/
    private static function step2 ( $word ) {
        switch (substr($word, -2, 1)) {
            case 'a':
                   self::replace($word, 'ational', 'ate', 0)
                OR self::replace($word, 'tional', 'tion', 0);
                break;

            case 'c':
                   self::replace($word, 'enci', 'ence', 0)
                OR self::replace($word, 'anci', 'ance', 0);
                break;

            case 'e':
                self::replace($word, 'izer', 'ize', 0);
                break;

            case 'g':
                self::replace($word, 'logi', 'log', 0);
                break;

            case 'l':
                   self::replace($word, 'entli', 'ent', 0)
                OR self::replace($word, 'ousli', 'ous', 0)
                OR self::replace($word, 'alli', 'al', 0)
                OR self::replace($word, 'bli', 'ble', 0)
                OR self::replace($word, 'eli', 'e', 0);
                break;

            case 'o':
                   self::replace($word, 'ization', 'ize', 0)
                OR self::replace($word, 'ation', 'ate', 0)
                OR self::replace($word, 'ator', 'ate', 0);
                break;

            case 's':
                   self::replace($word, 'iveness', 'ive', 0)
                OR self::replace($word, 'fulness', 'ful', 0)
                OR self::replace($word, 'ousness', 'ous', 0)
                OR self::replace($word, 'alism', 'al', 0);
                break;

            case 't':
                   self::replace($word, 'biliti', 'ble', 0)
                OR self::replace($word, 'aliti', 'al', 0)
                OR self::replace($word, 'iviti', 'ive', 0);
                break;
        }

        return $word;
    }


    /**
     * Step 3
     *
     * @param string $word String to stem
     **/
    private static function step3 ( $word ) {
        switch ( substr($word, -2, 1) ) {
            case 'a':
                self::replace($word, 'ical', 'ic', 0);
                break;

            case 's':
                self::replace($word, 'ness', '', 0);
                break;

            case 't':
                   self::replace($word, 'icate', 'ic', 0)
                OR self::replace($word, 'iciti', 'ic', 0);
                break;

            case 'u':
                self::replace($word, 'ful', '', 0);
                break;

            case 'v':
                self::replace($word, 'ative', '', 0);
                break;

            case 'z':
                self::replace($word, 'alize', 'al', 0);
                break;
        }

        return $word;
    }


    /**
     * Step 4
     *
     * @param string $word Word to stem
     **/
    private static function step4 ( $word ) {
        switch ( substr($word, -2, 1) ) {
            case 'a':
                self::replace($word, 'al', '', 1);
                break;

            case 'c':
                   self::replace($word, 'ance', '', 1)
                OR self::replace($word, 'ence', '', 1);
                break;

            case 'e':
                self::replace($word, 'er', '', 1);
                break;

            case 'i':
                self::replace($word, 'ic', '', 1);
                break;

            case 'l':
                   self::replace($word, 'able', '', 1)
                OR self::replace($word, 'ible', '', 1);
                break;

            case 'n':
                   self::replace($word, 'ant', '', 1)
                OR self::replace($word, 'ement', '', 1)
                OR self::replace($word, 'ment', '', 1)
                OR self::replace($word, 'ent', '', 1);
                break;

            case 'o':
                if (substr($word, -4) == 'tion' OR substr($word, -4) == 'sion') {
                   self::replace($word, 'ion', '', 1);
                } else {
                    self::replace($word, 'ou', '', 1);
                }
                break;

            case 's':
                self::replace($word, 'ism', '', 1);
                break;

            case 't':
                   self::replace($word, 'ate', '', 1)
                OR self::replace($word, 'iti', '', 1);
                break;

            case 'u':
                self::replace($word, 'ous', '', 1);
                break;

            case 'v':
                self::replace($word, 'ive', '', 1);
                break;

            case 'z':
                self::replace($word, 'ize', '', 1);
                break;
        }

        return $word;
    }

    /**
     * Step 5
     *
     * @param string $word Word to stem
     **/
    private static function step5 ( $word ) {
        // Part a
        if (substr($word, -1) == 'e') {
            if (self::m(substr($word, 0, -1)) > 1) {
                self::replace($word, 'e', '');

            } else if (self::m(substr($word, 0, -1)) == 1) {

                if (!self::cvc(substr($word, 0, -1))) {
                    self::replace($word, 'e', '');
                }
            }
        }

        // Part b
        if (self::m($word) > 1 AND self::doubleConsonant($word) AND substr($word, -1) == 'l')
            $word = substr($word, 0, -1);

        return $word;
    }

    /**
     * Replaces the first string with the second, at the end of the string. If third
     * arg is given, then the preceding string must match that m count at least.
     *
     * @param  $str   String to check
     * @param  $check Ending to check for
     * @param  $repl  Replacement string
     * @param  int    $m     Optional minimum number of m() to meet
     * @return bool          Whether the $check string was at the end
     *                       of the $str string. True does not necessarily mean
     *                       that it was replaced.
     **/
    private static function replace ( &$str, $check, $repl, $m = null ) {
        $len = 0 - strlen($check);

        if ( substr($str, $len) == $check ) {
            $substr = substr($str, 0, $len);
            if ( is_null($m) OR self::m($substr) > $m )
                $str = $substr . $repl;
            return true;
        }

        return false;
    }


    /**
     * What, you mean it's not obvious from the name?
     *
     * m() measures the number of consonant sequences in $str. if c is
     * a consonant sequence and v a vowel sequence, and <..> indicates arbitrary
     * presence,
     *
     * <c><v>       gives 0
     * <c>vc<v>     gives 1
     * <c>vcvc<v>   gives 2
     * <c>vcvcvc<v> gives 3
     *
     * @param  $str The string to return the m count for
     * @return int         The m count
     **/
    private static function m ( $str ) {
        $c = self::$regex_consonant;
        $v = self::$regex_vowel;

        $str = preg_replace("#^$c+#", '', $str);
        $str = preg_replace("#$v+$#", '', $str);

        preg_match_all("#($v+$c+)#", $str, $matches);

        return count($matches[1]);
    }


    /**
     * Returns true/false as to whether the given string contains two
     * of the same consonant next to each other at the end of the string.
     *
     * @param  $str String to check
     * @return bool        Result
     **/
    private static function doubleConsonant ( $str ) {
        $c = self::$regex_consonant;

        return preg_match("#$c{2}$#", $str, $matches) AND $matches[0]{0} == $matches[0]{1};
    }

    /**
     * Checks for ending CVC sequence where second C is not W, X or Y
     *
     * @param  $str String to check
     * @return bool        Result
     **/
    private static function cvc ( $str ) {
        $c = self::$regex_consonant;
        $v = self::$regex_vowel;

        return preg_match("#($c$v$c)$#", $str, $matches)
               AND strlen($matches[1]) == 3
               AND $matches[1]{2} != 'w'
               AND $matches[1]{2} != 'x'
               AND $matches[1]{2} != 'y';
    }

} // END class PorterStemmer

/**
 * A helper class to preserve token patterns in a string
 *
 * Used when token patterns need preserved through other string manipulation/transformations,
 * leaving the preserved tokens untouched.
 *
 * @since 1.3.8
 **/
class ShoppStringShield {

	private $pattern = false;
	private $tokens = array();

	/**
	 * Object constructor
	 *
	 * @param string $pattern The regex pattern for tokens to preserve
	 * @return void
	 **/
	public function __construct ( $pattern ) {
		$this->pattern = $pattern;
	}

	/**
	 * Protect all matching string tokens
	 *
	 * @param string $string The string to protect
	 * @return string The modified string with tokenized values
	 **/
	public function shield ( $string ) {
		return preg_replace_callback($this->pattern, array($this, 'tokenize'), $string);
	}

	/**
	 * Helper callback to do token replacement
	 *
	 * Builds a dictionary of tokens and the real string they represent.
	 *
	 * @internal
	 *
	 * @param array $matches The preg matches to preserve
	 * @return string The token representation of the string
	 **/
	public function tokenize ( $matches ) {
		$token = str_rot13($matches[0]);
		$this->tokens[ $token ] = $matches[0];
		return $token;
	}

	/**
	 * Restore the preserved tokens to the given string
	 *
	 * @param string $string The string with preserved tokens to restore
	 * @return string The modified string with tokens restored to the original strings
	 **/
	public function restore ( $string ) {
		return str_replace(array_keys($this->tokens), $this->tokens, $string);
	}

}
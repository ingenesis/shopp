<?php
/**
 * Markdownr
 *
 * A lightweight Markdown parser to generate HTML
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May 2013
 * @license (@see license.txt)
 * @package shopp
 * @since 1.3
 * @subpackage markdownr
 **/

// @todo Add reference link scanner [label][reference] / [reference]: url

Markdownr::add( 'MarkdownrHeader' );
Markdownr::add( 'MarkdownrRule' );
Markdownr::add( 'MarkdownrList' );
Markdownr::add( 'MarkdownrBlockquote' );
Markdownr::add( 'MarkdownrHTML' );
Markdownr::add( 'MarkdownrCode' );
Markdownr::add( 'MarkdownrParagraph' );

Markdownr::add( 'MarkdownrHardBreaks' );
Markdownr::add( 'MarkdownrStrong' );
Markdownr::add( 'MarkdownrEm' );
Markdownr::add( 'MarkdownrInlineCode' );
Markdownr::add( 'MarkdownrInlineLink' );
Markdownr::add( 'MarkdownrAutoLink' );
Markdownr::add( 'MarkdownrEmailLink' );

/**
 * Renders HTML from Markdown text
 *
 * $Markdown = new Markdown($text);
 * $HTML = $Markdown->html();
 * $Markdown->render(); // echo the HTML
 *
 * @author Jonathan Davis
 * @version 1.0
 * @package markdownr
 **/
class Markdownr {

	const ON = true;
	const NEWLINE = "\n";
	const NEWBLOCK = "\n\n";
	const SUBMARK = "\x1A"; // Substitution

	private $DOM = false;
	private $code = array();
	private $Parsers = array();

	private static $parsers = array();

	function __construct ( string $string ) {

		$this->DOM = new DOMDocument('1.0', 'UTF-8');
		$this->DOM->_parent = $this;

		$this->parse($string);

	}

	/**
	 * Adds a parser to the Markdownr engine
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $classname The class name of a MarkdownrBlock or MarkdownrInline class
	 * @return void
	 **/
	static function add ( string $classname ) {
		self::$parsers[] = $classname;
	}

	private function parse ( string $source ) {

		$parsing = array(
			'normalines',
			'codeblocks',
			// 'joinlists',
			'textblocks'
		);

		foreach ( $parsing as $callback )
			$source = call_user_func( array($this, $callback), $source );

		$blocks = array_filter($source);
		$elements = $this->blocks( $blocks );

		foreach ($elements as $element)
			$this->DOM->appendChild($element);

	}

	public function render () {
		echo $this->html();
	}

	public function html () {
		$HTML = $this->DOM->saveHTML();
		return htmlspecialchars_decode($HTML);
	}

	public function blocks ( array &$blocks, array $ignore = array() ) {

		$elements = array();
		foreach ( $blocks as $block ) {

			$block = trim($block);
			foreach ( self::$parsers as $Parser ) {

				if ( in_array($Parser, $ignore) ) continue;
				if ( 'MarkdownrBlock' != get_parent_class($Parser) ) continue;

				if ( $Parser::match($block) ) {
					$Block = new $Parser($this->DOM, $block);
					$elements[] = $Block->markup();
					$parsed = true;
					break;
				}
			}

		}

		return $elements;

	}

	public function inline ( string $text ) {
		$DOM = $this->DOM;

		$search = $text;
		$this->Parsers = array(); // Reset list of parsers for this inline run

		$found = $this->marked($search);
		if ( ! $found ) return array($DOM->createTextNode( $text ));

		// Build the node list
		$markup = array();
		foreach ( $this->Parsers as $Parser )
			$markup = array_merge($markup, $Parser->nodes());

		// Build text nodes
		$nodes = array(); $string = array();

		$phrases = explode(Markdownr::SUBMARK, $search);

		foreach ( $phrases as $phrase ) {
			if ( isset($markup[ $phrase ]) )
				$nodes[] = $markup[ $phrase ];
			else $nodes[] = $DOM->createTextNode($phrase);
		}

		return $nodes;

	}

	public function marked ( string &$search ) {

		$found = false;

		foreach ( self::$parsers as $parseclass ) {

			if ( false === strpos(get_parent_class($parseclass), 'MarkdownrInline') ) continue;

			if ( $parseclass::match($parseclass::$marks, $search) ) {

				$Parser = $this->parser($parseclass);
				$Parser->scan($search);
				$found = true;

			}

		}

		return $found;

	}

	private function parser ( string $classname ) {

		if ( ! isset($this->Parsers[ $classname ]) ) {
			$Parser = new $classname($this->DOM);
			$this->Parsers[ $classname ] = $Parser;
		}

		return $this->Parsers[ $classname ];
	}

	public function normalines ( string $text ) {
		$text = str_replace('    ', "\t", $text);
		return str_replace("\r", self::NEWLINE, $text);
	}

	public function textblocks ( string $text ) {
		return explode(self::NEWBLOCK, $text);
	}

	public function codeblocks ( string $text ) {
		MarkdownrCode::blocks($text);
		return $text;
	}

	public function joinlists ( string $text, string $token = null ) {

		$blocks = $this->textblocks($text);

		$text = $blocks[0];
		$previous = '';
		foreach ($blocks as $i => $block) {
			if ( 0 == $i ) continue;
			if ( MarkdownrList::unordered($previous) ) {
				if ( MarkdownrList::unordered($block) ) $text .= "\n$block";
				elseif ( "\t" == $block{0} ) $text .= "\n$block";
				elseif ( 4 == substr_count($block, ' ', 0, 5) ) $text .= "\n\t\n\t$block";
			}
			else $text .= "\n\n$block";
			$previous = substr($block, 0, 5);
		}

		return $text;
	}

	public static function key ( string $string ) {

		$key = array(
			Markdownr::SUBMARK,
			hash('crc32b', $string),
			Markdownr::SUBMARK
		);

		return join('', $key);

	}

}

class MarkdownrBlock {

	public static $pattern = '/^.*?$/';

	protected static $marks = array();

	protected $selfnest = false;
	protected $tag = 'div';

	function __construct ( DOMDocument $DOM, $content ) {
		$this->DOM = $DOM;
		$this->content = $content;
	}

	public function markup () {
		$DOM = $this->DOM;

		$Element = $DOM->createElement( $this->tag() );
		$this->content($Element);
		return $Element;
	}

	protected function content ( DOMElement $Element, string $content = null ) {

		if ( is_null($content) ) $content = $this->content;

		$parsing = array(
			'unmark',
			'nested'
		);

		foreach ( $parsing as $callback )
			$content = call_user_func( array($this, $callback), $content );

		if ( is_array($content) ) {
			foreach ( $content as $node )
				$Element->appendChild($node);
		} else $Element->appendChild($content);

	}

	// happens after unmarking so the provided text is stripped back to a "root" level for parsing
	protected function nested ( string $text ) {
		$Markdownr = $this->DOM->_parent;

		$blocks = self::indents($text);
		if ( empty($blocks) ) return $this->inline($text);

		if ( ! $this->selfnest )
			$ignore = array(get_class($this));

		$content = array();
		foreach ( $blocks as $block ) {

			if ( is_array($block) ) {
				$Nested = $Markdownr->blocks($block, $ignore);
				$content = array_merge($content, $Nested);

			} else $content = array_merge($content, $this->inline($block));

		}

		return $content;
	}

	protected function inline ( string $text ) {
		$Markdownr = $this->DOM->_parent;
		return $Markdownr->inline($text);
	}

	protected function tag () {
		return $this->tag;
	}

	public static function match ( string $text ) {
		return true;
	}

	protected function unmark ( string $text ) {
		$class = get_class($this);
		if ( ! isset($class::$marks) ) return $text;
		return trim( str_replace($class::$marks, '', $text) );
	}

	// group by newline
	// group by tabs
	// Designed to find indented lines and parse them as block groups
	public static function indents ( $text ) {

		$_ = array();		// Capture array
		$blocks = array();	// Block capture array
		$indent = false;	// Tracks indent indice
		$lines = explode(Markdownr::NEWLINE, $text);

		foreach ( $lines as $i => $line ) {

			$was = $indented;
			$indented = ( 0 === strpos($line, "\t") );

			if ( empty($line) ) {
				if ($was) { // Lookahead to capture conjoined lines
					$nextline = isset($lines[ $i + 1 ]) ? $lines[ $i + 1 ] : false;
					if ( 0 === strpos($nextline, "\t") ) {
						$_[$indent][] = $line;
						$indented = true;
						continue;
					}
				}

				self::nestblock($blocks, $_);

				// Reset for new capture list
				$indent = false;
				$_ = array();
				continue;
			} elseif ( $was != $indented ) {
				self::nestblock($blocks, $_);

				// Reset for new capture list
				$indent = false;
				$_ = array();
			}

			if ( $indented ) {
				if ( false === $indent )
					$indent = count($_);
				$_[$indent][] = $line;
			} else {
				$_[] = $line . Markdownr::NEWLINE;
			}

		}
		if ( ! empty($_) ) self::nestblock($blocks, $_);

		return $blocks;
	}

	public static function nestblock (&$blocks, $lines) {
		if ( 1 == count($block) ) $blocks[] = $lines[0];
		else $blocks = array_merge($blocks, $lines);
	}

}

class MarkdownrParagraph extends MarkdownrBlock {

	protected $tag = 'p';

}

class MarkdownrHTML extends MarkdownrBlock {

	public function markup () {
		$Markup = $this->DOM->createDocumentFragment();
		$Markup->appendXML( $this->content );
		return $Markup;
	}

	public static function match ( string $text ) {
		return ( $text != strip_tags($text) && 0 === strpos($text, '<') );
	}

}

class MarkdownrHeader extends MarkdownrBlock {

	protected static $marks = array('#', '=', '-');
	protected $tag = 'h1';

	protected function tag () {
		$tag = $this->tag{0};
		$level = self::setext( $this->content );
		if ( false === $level ) $level = self::atx( $this->content );
		if ( false === $level ) $level = 6;
		return "$tag$level";
	}

	protected static function atx ( string $text ) {
		$mark = self::$marks[0];
		$locate = strpos($text, $mark);
		if ( 0 === $locate ) {
			return substr_count($text, $mark, 0, 6);
		} else return false;
	}

	protected static function setext ( string $text ) {
		$underlines = array_slice(self::$marks, 1, 2, true);
		list($heading, $underline) = explode(Markdownr::NEWLINE, $text);
		if ( in_array($underline{0}, $underlines) && substr_count($underline, $underline{0}) > 1 )
			return array_search($underline{0}, $underlines);
		else return false;
	}

	public static function match ( string $text ) {
		if ( false !== self::atx($text) ) return true;
		elseif ( false !== self::setext($text) ) return true;
		return false;
	}

}

class MarkdownrList extends MarkdownrBlock {

	const ORDERED_LIST = 'ol';
	const UNORDERED_LIST = 'ul';

	protected static $marks = array('-', '+', '*');
	protected $selfnest = true;

	protected function tag () {
		$tag = $this->tag;
		if ( self::ordered($this->content) ) return self::ORDERED_LIST;
		else return self::UNORDERED_LIST;
	}

	public static function unordered ( string $text ) {
		if ( ! in_array($text{0}, self::$marks) ) return false;
		$index = array_search($text{0}, self::$marks);
		$token = self::$marks[ $index ]. ' ';
		if ( $token == substr($text, 0, strlen($token) ) ) return true;
		return false;
	}

	public static function ordered ( string $text ) {
		$number = false;
		sscanf($text, '%d.', $number);
		return ( intval($number) > 0 );
	}

	protected function listitems ( DOMElement $List ) {

		$token = Markdownr::NEWLINE . self::$marks[0];
		$content = str_replace(self::$marks, self::$marks[0], $this->content);

		$entries = explode($token, $content);

		$Previous = false;
		foreach ( $entries as $entry ) {
			$Element = $this->DOM->createElement( 'li' );
			$this->content($Element, $entry);
			$List->appendChild($Element);
			$Previous = $Element;
		}
	}

	public function markup () {
		$DOM = $this->DOM;

		$Element = $DOM->createElement( $this->tag() );
		$this->listitems($Element);
		return $Element;
	}

	public function unmark ( string $text ) {

		if ( self::ORDERED_LIST == $this->tag() ) {

			$number = false;
			sscanf($text, '%d. ', $number);
			return str_replace(Markdownr::NEWLINE . "$number. ", Markdownr::NEWLINE, $text);

		} else $text = ltrim($text, join('', self::$marks) . " \t");

		return $text;
	}

	public static function match ( string $text ) {
		if ( false !== self::unordered($text) ) return true;
		elseif ( false !== self::ordered($text) ) return true;
		return false;
	}

}

class MarkdownrRule extends MarkdownrBlock {

	protected static $marks = array('*', '-');
	protected $tag = 'hr';

	public static function match ( string $text ) {
		$valid = array_merge(self::$marks, array(' '));
		foreach ( self::$marks as $mark ) {
			if ( substr_count($text, $mark, 0 ) >= 3 ) { // Must have 3 or more of the marks

				if ( '' == str_replace($valid, '', $text) ) // No characters other than marks are a horizontal rule
					return true;

			}
		}

		return false;
	}

}


class MarkdownrBlockquote extends MarkdownrBlock {

	protected static $marks = array('>');
	protected $tag = 'blockquote';

	public static function match ( string $text ) {
		return ( $text{0} == self::$marks[0] );
	}
}


// @todo MarkdownrCode should focus on tab/indent code
class MarkdownrCode extends MarkdownrBlock {

	protected static $marks = array("\t");
	protected static $code = array();
	protected static $iblocks = array('MarkdownrList', 'MarkdownrHTML'); // List of block parsers with indent capability
	protected $tag = 'pre,code';

	public function markup () {
		$DOM = $this->DOM;

		$Element = $DOM->createElement( 'pre' );
		$Code = $DOM->createElement( 'code' );
		$this->content($Code);

		$Element->appendChild($Code);
		return $Element;
	}

	protected function content ( DOMElement $Element, string $content = null ) {
		$code = self::code( $this->content );
		if ( $code ) {
			$code = $this->unmark($code);
			$Node = $this->DOM->createTextNode($code);
			$Element->appendChild($Node);
		} else return parent::content($Element, $this->content);
	}

	public static function match ( string $text ) {
		$codes = array_keys( self::code() );

		foreach ($codes as $code)
			if ( false !== strpos($text, $code) ) return true;

		return false;
	}

	public static function code ( string $code = null ) {
		if ( is_null($code) )
			return self::$code;

		if ( isset(self::$code[ $code ]) )
			return self::$code[ $code ];

		return false;
	}

	public static function blocks ( string &$text ) {

		$blocks = self::indents( $text );

		$codes = array();
		foreach ( $blocks as $i => $block ) {
			$ignore = false;

			// Scan for tabbed code
			if ( ! is_array($block) ) continue;

			foreach ( self::$iblocks as $Parser ) { // Skip blocks inside indent-aware parsers
				if ( $i > 0 && $Parser::match($blocks[ $i - 1 ]) ) {
					$ignore = true;
					break;
				}
			}

			if ( $ignore ) continue;

			$string = join(Markdownr::NEWLINE, $block);
			$key = Markdownr::key($string);
			$codes[ $key ] = $string;
			$text = str_replace($string, $key, $text);
		}

		self::$code = $codes;

	}

}

class MarkdownrFencedCode extends MarkdownrBlock {

	protected static $marks = array('```', '~~~');
	protected $tag = 'pre,code';

	public function markup () {
		$DOM = $this->DOM;

		$Element = $DOM->createElement( 'pre' );
		$Code = $DOM->createElement( 'code' );
		$this->content($Code);

		$Element->appendChild($Code);
		return $Element;
	}

	protected function content ( DOMElement $Element, string $content = null ) {
		$hash = $this->unmark($this->content);
		$Markdownr = $this->DOM->_parent;
		$fenced = $Markdownr->code( $hash );
		if ( $fenced ) return parent::content($Element, $fenced);
		else return parent::content($Element, $this->content);
	}

	public static function match ( string $text ) {
		foreach ( self::$marks as $i => $fence ) {
			if ( $fence == substr($text, 0, strlen($fence)) ) return true;
		}
		return false;
	}

	public static function blocks ( string &$text ) {
		$codes = array();
		foreach ( self::$marks as $mark ) {
			$blocks = explode($mark, $text);

			$code = false;
			foreach ( $blocks as $block ) {

				if ( $code ) {
					$hash = hash('crc32b', $block);
					$codes[ $hash ] = $block;
					$text = str_replace($block, $hash, $text);
				}

				$code = !$code;

			}
		}

		return $codes;

	}

}
class MarkdownrInline {

	public static $marks = array();
	protected $tag = '';
	protected $markup = array();
	protected $nested = true;

	public function __construct ( DOMDocument $DOM ) {
		$this->DOM = $DOM;
	}

	public function scan ( string &$text ) {

		$class = get_class($this);
		$marks = $class::$marks;
		$token = $marks[0];

		// Normalize the text to the first mark (default mark)
		$search = str_replace($marks, $token, $text);

		$strings = array();
		$start = strpos($search, $token);

		while ( false !== $start ) {
			$offset = $start + strlen($token);

			// Capture the string to the next token
			$end = strpos($search, $token, $offset);
			$length = $end - $offset;

			if ( false === $end ) { // No ending mark found
				$length = 0; // Don't capture anything
				$start = false;  // no start mark will be able to be found either, prevents infinite loops
			}

			$string = substr($search, $offset, $length);

			// When tagged, capture the string
			if ( false !== $string )
				$strings[ $this->key($string) ] = $string;

			// Find next string
			if ( false !== $start ) {
				$offset = $end + strlen($token);
				$start = strpos($search, $token, $offset);
			}

		}

		// Remove the strings from the search text for other parsers
		// Nested tags are parsed recursively
		foreach ( $strings as $key => $string )
			$text = str_replace("$token$string$token", $key, $text);

		$this->markup = $strings;

		return ( ! empty($strings) );
	}

	public function key ($string) {

		$key = array(
			Markdownr::SUBMARK,
			hash('crc32b', "$this->tag-$string"),
			Markdownr::SUBMARK
		);

		return join('', $key);
	}

	public function nodes ( ) {
		$nodes = array();
		foreach ( $this->markup as $key => $string )
			$nodes[ trim($key, Markdownr::SUBMARK) ] = $this->render($string);
		return $nodes;
	}

	public function render ( $content ) {

		if ( is_a($content, 'DOMElement') )
			return $this->element($content);

		if ( $this->nested ) {
			// Parse nested inline tags
			$Markdownr = $this->DOM->_parent;
			$nodes = $Markdownr->inline($content);
			if ( is_array($nodes) ) {
				$Element = $this->tag();
				foreach ( $nodes as $node )
					$Element->appendChild($node);

			} else $Element = $this->tag($content);
		} else $Element = $this->tag($content);

		return $Element;

	}

	protected function element ( DOMElement $Element ) {

		$text = $Element->textContent;
		$Node = $this->tag($text);

		$Element->textContent = '';
		$Element->appendChild($Node);

		return $Element;
	}

	protected function tag ( string $text = null ) {
		return $this->DOM->createElement($this->tag, $text);
	}

	protected function attribute (DOMElement $Element, string $name, string $value ) {
		$Attribute = $this->DOM->createAttribute($name);
		$Attribute->value = $value;
		$Element->appendChild($Attribute);

	}

	protected function unmark ( string $text ) {
		$class = get_class($this);
		if ( ! isset($class::$marks) ) return $text;
		return trim( str_replace($class::$marks, '', $text) );
	}

	public static function match ( array $marks, string $text ) {

		foreach ( $marks as $mark )
			if ( false !== strpos($text, $mark) ) return true;

		return false;

	}

	protected static function strposall ( string $haystack, array $needles = array(), integer $offset = null ){
		if ( is_null($offset) ) $offset = 0;

	    $chr = array();
	    foreach( $needles as $needle )
	        $chr[] = strpos($haystack, $needle, $offset);

	    if ( empty($chr) ) return false;
	    return min($chr);
	}

}

class MarkdownrEm extends MarkdownrInline {

	public static $marks = array('*', '_');
	protected $tag = 'em';

}


class MarkdownrStrong extends MarkdownrInline {

	public static $marks = array('**', '__');
	protected $tag = 'strong';

}

class MarkdownrHardBreaks extends MarkdownrInline {

	protected $tag = 'br';
	protected $nested = false;
	private static $token = '';

	public function __construct ( DOMDocument $DOM ) {
		$this->DOM = $DOM;
	}

	public static function match ( array $marks, string $text ) {
		self::$token = '  ' . Markdownr::NEWLINE;
		return ( false !== strpos($text, self::$token) );
	}

	public function scan ( string &$text ) {
		$token = self::$token;

		$pos = true;
		$strings = array();
		while ( false !== $pos ) {

			$offset = $pos + strlen($token);

			// Capture the string to the next token
			$pos = strpos($text, $token, $offset);

			// When tagged, capture the string
			if ( false !== $pos )
				$strings[ $this->key($token) ] = $token;

		}

		// Add a space so the string is split with an
		// empty word to create an insert point for
		// the break tag
		foreach ( $strings as $key => $string )
			$text = str_replace($token, $key, $text);

		$this->markup = $strings;

		return ( ! empty($strings) );
	}


}


class MarkdownrInlineCode extends MarkdownrInline {

	public static $marks = array('`');
	protected $tag = 'code';
	protected $nested = false;

}

class MarkdownrInlineLink extends MarkdownrInline {

	public static $marks = array('[', ']', '(', ')');
	protected $tag = 'a';
	protected $nested = false;

	public static function match ( array $marks, string $text ) {

		if ( false !== strpos($text, '[') ) {

			$open = substr_count($text, '[');
			$close = substr_count($text, ']');

			if ( $open == $close ) return true;
		}

		return false;
	}

	public function scan ( string &$text ) {
		$class = get_class($marks);
		$marks = $class::$marks;

		$start = array($marks[0],$marks[2]);
		$end = array($marks[1], $marks[3]);
		$token = $marks[0];

		$pos = strpos($text, $start[0]);

		$strings = array();
		while ( false !== $pos ) {

			// Capture inside
			$offset = $pos + strlen($start[0]);

			// Capture the string to the next token
			$pos = strpos($text, $end[1], $offset);
			$string = substr($text, $offset - 1, $pos - $offset + 2);

			// When tagged, capture the string
			if ( false !== $string )
				$strings[ $this->key($string) ] = $string;

			if ( false !== $pos ) {
				// Find start of next link
				$offset = $pos + strlen($end[1]);
				$pos = strpos($text, $start[0], $offset);
			}

		}

		// Remove the strings from the search text for other parsers,
		// Nested tags are parsed recursively
		foreach ( $strings as $key => &$string ) {
			$text = str_replace($string, $key, $text);
			list($link, $url) = explode('](', $string);
			list($url, $title) = explode(' ', $url);

			// Cleanup the strings
			$string = array($link, $url, $title);
			$string = array_map(array($this, 'unmark'), $string);
		}

		$this->markup = $strings;

		return ( ! empty($strings) );
	}

	public function render ( $content ) {
		if ( is_a($content, 'DOMElement') )
			return $this->element($content);

		$Element = $this->tag();

		if ( isset($content[1]) )
			$this->attribute($Element, 'href', $content[1]);

		if ( isset($content[1]) )
			$this->attribute($Element, 'title', $content[2]);

		$link = $this->DOM->createTextNode($content[0]);
		$Element->appendChild($link);

		return $Element;

	}

	public function tag ( string $text = null ) {
		return $this->DOM->createElement($this->tag, $text);
	}

	protected function unmark ( string $text ) {
		$class = get_class($this);
		return trim($text, join('', $class::$marks).'"\' ' );
	}

}

class MarkdownrAutoLink extends MarkdownrInlineLink {

	public static $marks = array('http://', 'https://', 'shttp://', 'ftp://', 'tftp://', 'file://', 'skype://', 'facetime://', 'git://', 'irc://', 'bitcoin://');
	protected $tag = 'a';
	protected $nested = false;

	public static function match ( array $marks, string $text ) {
		foreach ( self::$marks as $scheme ) {
			if ( false !== strpos($text, $scheme) ) return true;
		}

		return false;
	}

	public function scan ( string &$text ) {
		$schemes = self::$marks;

		$strings = array();
		foreach ( $schemes as $scheme ) {
			$pos = strpos($text, $scheme);

			while ( false !== $pos ) {

				// Capture the string to the next token
				$end = self::strposall($text, array(' ',"\n","\t"), $pos);
				$length = $end - $pos;
				if ( false === $end ) $length = strlen($text);

				$string = substr($text, $offset, $length);

				// When tagged, capture the string
				if ( false !== $string )
					$strings[ $this->key($string) ] = $string;

				if ( false !== $pos ) {
					// Find start of next link
					$offset = $pos + $length;
					$pos = strpos($text, $scheme, $offset);
				}

			}
		}

		// Remove the strings from the search text for other parsers,
		// Nested tags are parsed recursively
		foreach ( $strings as $key => &$string ) {
			$text = str_replace($string, $key, $text);

			$link = $this->unmark($string);
			$url = $string;

			// Cleanup the strings
			$string = array($link, $url);

		}

		$this->markup = $strings;

		return ( ! empty($strings) );
	}

	protected function unmark ( string $text ) {
		$marks = self::$marks;
		$text = str_replace($marks, '', $text);
		return trim($text, '/ ');
	}

}

class MarkdownrEmailLink extends MarkdownrInlineLink {

	public static $marks = array('mailto:');
	protected $tag = 'a';
	protected $nested = false;

	const RFC822_EMAIL = '([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x22([^\\x0d\\x22\\x5c\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x22))*\\x40([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d)(\\x2e([^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+|\\x5b([^\\x0d\\x5b-\\x5d\\x80-\\xff]|\\x5c[\\x00-\\x7f])*\\x5d))*';


	public static function match ( array $marks, string $text ) {
		return ( false !== strpos($text, '@') );
	}

	public function scan ( string &$text ) {
		$found = preg_match_all('!' . self::RFC822_EMAIL . '!', $text, $matches);
		if ( ! $found ) return false;

		$emails = array();
		foreach ( $matches[0] as $key => &$string ) {
			$key = $this->key($string);
			$text = str_replace($string, $key, $text);

			$link = $string;
			$url = "mailto:$string";

			$entry = array($link, $url);
			$emails[ $key ] = $entry;
		}

		$this->markup = $emails;

		return ( ! empty($emails) );
	}

	protected function unmark ( string $text ) {
		$marks = self::$marks;
		$text = str_replace($marks, '', $text);
		return trim($text, '/ ');
	}

}
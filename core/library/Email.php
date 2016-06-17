<?php
/**
 * Email.php
 *
 * A collection of Email utility classes
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, November  3, 2011
 * @license (@see license.txt)
 * @package shopp
 * @since 1.2
 * @subpackage email
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppEmailDefaultFilters extends ShoppEmailFilters {

	private static $object = false;

	private function __construct () {
		add_filter('shopp_email_message', array('ShoppEmailDefaultFilters', 'FixSymbols'));
		add_filter('shopp_email_message', array('ShoppEmailDefaultFilters', 'AutoMultipart'));
		add_filter('shopp_email_message', array('ShoppEmailDefaultFilters', 'InlineStyles'), 99);
		add_action('shopp_email_completed', array('ShoppEmailDefaultFilters', 'RemoveAutoMultipart'));
		do_action('shopp_email_filters');
	}

	/**
	 * The singleton access method
	 *
	 * @author Jonathan Davis
	 * @since
	 *
	 * @return
	 **/
	public static function init () {
		if ( ! self::$object instanceof self )
			self::$object = new self;
		return self::$object;
	}

}

abstract class ShoppEmailFilters {

	static function InlineStyles ( $message ) {

		if ( false === strpos($message, '<html') ) return $message;
		$cssfile = Shopp::locate_template(array('email.css'));
		$stylesheet = file_get_contents($cssfile);

		if (!empty($stylesheet)) {
			$Emogrifier = new Emogrifier($message, $stylesheet);
			$message = $Emogrifier->emogrify();
		}

		return $message;

	}

	static function AutoMultipart ( $message ) {
		if ( false === strpos($message, '<html') ) return $message;
		remove_action('phpmailer_init', array('ShoppEmailDefaultFilters', 'NoAltBody'));
		add_action('phpmailer_init', array('ShoppEmailDefaultFilters', 'AltBody') );
		return $message;
	}

	static function RemoveAutoMultipart () {
		remove_action('phpmailer_init', array('ShoppEmailDefaultFilters', 'AltBody') );
		add_action('phpmailer_init', array('ShoppEmailDefaultFilters', 'NoAltBody'));
	}

	static function AltBody ( $phpmailer ) {
		// If DOMDocument isn't available, don't implement Textify (no alternate plaintext body will be sent)
 		if ( ! class_exists('DOMDocument') ) return;

		$Textify = new Textify($phpmailer->Body);
		$phpmailer->AltBody = $Textify->render();
	}

	static function NoAltBody ( $phpmailer ) {
		$phpmailer->AltBody = null;
	}

	static function FixSymbols ( $message ) {
		if ( ! defined( 'ENT_DISALLOWED' ) ) define( 'ENT_DISALLOWED', 128 ); // ENT_DISALLOWED added in PHP 5.4
		$entities = htmlentities( $message, ENT_NOQUOTES | ENT_DISALLOWED, 'UTF-8', false ); // Translate HTML entities (special symbols)
		return htmlspecialchars_decode( $entities ); // Translate HTML tags back
	}

}


/**
 * Textify
 * Convert HTML markup to plain text Markdown
 *
 * @copyright Copyright (c) 2011-2014 Ingenesis Limited
 * @author Jonathan Davis
 * @since 1.2
 * @package Textify
 **/
class Textify {

	private $markup = false;
	private $DOM = false;

	public function __construct ( $markup ) {
		$this->markup = $markup;
        $DOM = new DOMDocument();
        $DOM->loadHTML($markup);
		$DOM->normalizeDocument();
		$this->DOM = $DOM;
	}

	public function render () {
		$node = $this->DOM->documentElement;
		$HTML = new TextifyTag($node);
		return $HTML->render();
	}

}

/**
 * TextifyTag
 *
 * Foundational Textify rendering behavior
 *
 * @author Jonathan Davis
 * @since 1.2
 * @package textify
 **/
class TextifyTag {

	const NEWLINE = "\n";
	const STRPAD = " ";
	const CLASSPREFIX = 'Textify';
	const DEBUG = true;

	static $_marks = array(		// Default text decoration marks registry
		'inline' => '',
		'padding' => array('top' => ' ','right' => ' ','bottom'  => ' ','left' => ' '),
		'margins' => array('top' => ' ','right' => ' ','bottom'  => ' ','left' => ' '),
		'borders' => array('top' => '-','right' => '|','bottom'  => '-','left' => '|'),
		'corners' => array('top-left' => '&middot;', 'top-right' => '&middot;', 'bottom-right' => '&middot;', 'bottom-left' => '&middot;', 'middle-middle'=> '&middot;', 'top-middle' => '&middot;', 'middle-left' => '&middot;', 'middle-right' => '&middot;', 'bottom-middle' => '&middot;')
		);

	protected $node = false;		// The DOM node for the tag
	protected $renderer = false;	// The Textify Renderer object for this node

	protected $content = array();	// The rendered child/text content

	protected $height = 0;
	protected $width = array('max' => 0, 'min' => 0);

	protected $tag = '';			// Name of the tag
	protected $attrs = array();		// Name of the tag
	protected $styles = array();	// Parsed styles
	protected $textalign = 'left';	// Text alignment (left,center,right, justified)
	protected $legend = '';			// Tag legend

	protected $marks = array();		// Override-able text decoration marks registry

	protected $borders = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);
	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);

	public function __construct ( DOMNode &$tag ) {
		$this->node = $tag;
		$this->tag = $tag->tagName;

		$this->marks = array_merge(TextifyTag::$_marks,$this->marks);

		// Style attribute parser
		// if (isset($attrs['style'])) $this->style
	}

	/**
	 * Rendering engine
	 *
	 * Recursive processing of each node passed off to a renderer for
	 * text formatting and other rendering (borders, padding, markdown marks)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param DOMNode $node The DOMNode to render out
	 * @return string The rendered content
	 **/
	public function render ( DOMNode $node = null ) {

		if ( ! $node ) {
			$node = $this->node;
			if ( ! $node ) return false;
		}
		if ( $node->hasAttributes() ) {
			foreach ($node->attributes as $name => $attr) {
				if ('style' == $name) $this->styles($attr->value);
				else $this->attrs[$name] = $attr->value;
			}
		}

		// No child nodes, render it out to and send back the parent container
		if ( ! $node->hasChildNodes() ) return $this->layout();

		foreach ($node->childNodes as $index => $child) {
			if ( XML_TEXT_NODE == $child->nodeType || XML_CDATA_SECTION_NODE == $child->nodeType ) {
				$text = $child->nodeValue;
				if (!empty($text)) $this->append( $this->format($text) );
			} elseif ( XML_ELEMENT_NODE == $child->nodeType) {
				$Renderer = $this->renderer($child);
				$this->append( $Renderer->render(), isset($Renderer->block) );
			}
		}

		// All done, render it out and send it all back to the parent container
		return $this->layout();

	}

	/**
	 * Combines the assembled content
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string The final assembled content for the element
	 **/
	protected function layout () {
		// Follows box model standards

		$this->prepend( $this->before() );	// Add before content
		$this->append( $this->after() );	// Add after content

		$this->padding(); 					// Add padding box

		$this->dimensions();				// Calculate final dimensions

		$this->borders();					// Add border decoration box
		$this->margins();					// Add margins box

		// Send the string back to the parent renderer
		return join(TextifyTag::NEWLINE, $this->content);
 	}

	protected function append ( $content, $block = false ) {
		$lines = array_filter($this->lines($content));
		if ( empty($lines) ) return;

		if ( ! $block ) {
			// Stitch the content of the first new line to the last content in the line list
			$firstline = array_shift($lines);
			if ( ! is_null($firstline) && ! empty($this->content) ) {
				$id = count($this->content)-1;
				$this->content[ $id ] .= $firstline;

				// Determine if max width has changed
				$this->width['max'] = max($this->width['max'], strlen($this->content[ $id ]));
			} else $this->content[] = $firstline;
		}

		$this->content = array_merge($this->content, $lines);
	}

	protected function prepend ( $content ) {
		$lines = array_filter($this->lines($content));
		if ( empty($lines) ) return;

		// Stitch the content of the last new line to the first line of the current content line list
		$lastline = array_pop($lines);
		$firstline = isset($this->content[0]) ? $this->content[0] : '';
		$this->content[0] = $lastline . $firstline;
		$this->width['max'] = max($this->width['max'], strlen($this->content[0]));
		$this->content[0] = TextifyTag::whitespace($this->content[0]);

		$this->content = array_merge($lines, $this->content);
	}

	protected function lines ( $content ) {
		if ( is_array($content) ) $content = join('', $content);

		if ( empty($content) ) return array();
		$linebreaks = TextifyTag::NEWLINE;
		$wordbreaks = " \t";

		$maxline = 0; $maxword = 0;
		$lines = explode($linebreaks, $content);
		foreach ( (array) $lines as $line ) {
			$maxline = max($maxline, strlen($line));

			$word = false;
			$word = strtok($line, $wordbreaks);
			while ( false !== $word ) {
				$maxword = max($maxword, strlen($word));
				$word = strtok($wordbreaks);
			}
		}

		$this->width['min'] = max($this->width['min'], $maxword);
		$this->width['max'] = max($this->width['max'], $maxline);

		return $lines;
	}

	/**
	 * Calculate content min/max widths
	 *
	 * Maximum width is the longest contiguous (unbroken) line
	 * Minimum width is the longest word
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param string $content The content to calculate
	 * @return void
	 **/
	protected function dimensions () {
		$this->lines(join(TextifyTag::NEWLINE, $this->content));
	}

	protected function before () {
		// if (TextifyTag::DEBUG) return "&lt;$this->tag&gt;";
	}

	protected function format ( $text ) {
		return TextifyTag::whitespace($text);
	}

	protected function after () {
		// if (TextifyTag::DEBUG) return "&lt;/$this->tag&gt;";
	}

	protected function padding () { /* placeholder */ }

	protected function borders () { /* placeholder */ }

	protected function margins () { /* placeholder */ }


	/**
	 * Mark renderer
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	protected function marks ( $repeat = 1 ) {
		return str_repeat($this->marks['inline'], $repeat);
	}

	protected function linebreak () {
		return self::NEWLINE;
	}

	/**
	 * Collapses whitespace into a single space
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	static function whitespace ( $text ) {
		return preg_replace('/\s+/', ' ', $text);
	}

	protected function renderer ( DOMElement $tag ) {
		if ( isset($tag->Renderer) ) {
			$tag->Renderer->content = array();
			return $tag->Renderer;
		}

		$Tagname = ucfirst($tag->tagName);
		$Renderer = self::CLASSPREFIX . $Tagname;
		if ( ! class_exists($Renderer) ) $Renderer = __CLASS__;

		$tag->Renderer = new $Renderer($tag);
		return $tag->Renderer;
	}

	protected function parent () {
		return $this->node->parentNode->Renderer;
	}

	protected function styles ( $string ) {

	}

}

class TextifyInlineElement extends TextifyTag {

	public function before () { return $this->marks(); }

	public function after () { return $this->marks(); }

}

class TextifyA extends TextifyInlineElement {

	public function before () {
		return '<';
	}

	public function after () {
		$string = '';
		if ( isset($this->attrs['href']) && ! empty($this->attrs['href']) ) {
			$href = $this->attrs['href'];
			if ( '#' != $href{0} ) $string .= ': ' . $href;
		}
		return $string . '>';
	}

}

class TextifyEm extends TextifyInlineElement {

	protected $marks = array('inline' => '_');

}

class TextifyStrong extends TextifyInlineElement {

	protected $marks = array('inline' => '**');

}

class TextifyCode extends TextifyInlineElement {

	protected $marks = array('inline' => '`');

}


class TextifyBr extends TextifyInlineElement {

	public function layout () {
		$this->content = array(' ', ' ');
		return parent::layout();
	}

}

class TextifyBlockElement extends TextifyTag {

	protected $block = true;

	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);
	protected $borders = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);
	protected $padding = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);

	protected function width () {
		return $this->width['max'];
	}

	protected function box ( &$lines, $type = 'margins' ) {
		if ( ! isset($this->marks[ $type ]) ) return;

		$size = 0;
		$marks = array('top' => '','right' => '', 'bottom' => '', 'left' => '');
		if ( isset($this->marks[ $type ]) && ! empty($this->marks[ $type ]) )
			$marks = array_merge($marks, $this->marks[ $type ]);

 		if ( isset($this->$type) ) $sizes = $this->$type;

		$left = str_repeat($marks['left'], $sizes['left']);
		$right = str_repeat($marks['right'], $sizes['right']);

		$width = $this->width();
		$boxwidth = $width;
		foreach ( $lines as &$line ) {
			if ( empty($line) ) $line = $left . str_repeat(TextifyTag::STRPAD, $width) . $right;

			else $line = $left . str_pad($line, $width, TextifyTag::STRPAD) . $right;
			$boxwidth = max($boxwidth, strlen($line));
		}

		if ( $sizes['top'] ) {
			for ( $i = 0; $i < $sizes['top']; $i++ ) {
				$top = str_repeat($marks['top'], $boxwidth);
				if ( 'borders' == $type ) $this->legend($top);
				array_unshift($lines, $top);
			}
		}


		if ( $sizes['bottom']  )
			for ($i = 0; $i < $sizes['bottom']; $i++)
				array_push( $lines, str_repeat($marks['bottom'], $boxwidth) );

	}

	protected function padding () {
		$this->box($this->content, 'padding');
	}

	protected function borders () {
		$this->box($this->content, 'borders');
	}

	protected function margins () {
		$this->box($this->content, 'margins');
	}

	protected function legend ( $string ) {
		if ( TextifyTag::DEBUG ) $legend = $this->tag;
		else $legend = $this->legend;

		return substr($string, 0, 2) . $legend . substr($string, ( 2 + strlen($legend) ));
	}

}

class TextifyDiv extends TextifyBlockElement {
}

class TextifyHeader extends TextifyBlockElement {

	protected $level = 1;
	protected $marks = array('inline' => '#');
	protected $margins = array('top' => 1, 'right' => 0, 'bottom' => 1, 'left' => 0);

	protected function before () {
		$text = parent::before();
		$text .= $this->marks($this->level) . ' ';
		return $text;
	}

	protected function after () {
		$text = ' ' . $this->marks($this->level);
		$text .= parent::after();
		return $text;
	}

}

class TextifyH1 extends TextifyHeader {
	protected $marks = array('inline' => '=');

	public function before () {}

	public function format ($text) {
		$marks = $this->marks(strlen($text));
		return "$text\n$marks";
	}

	public function after () {}
}

class TextifyH2 extends TextifyH1 {
	protected $level = 2;
	protected $marks = array('inline' => '-');
}

class TextifyH3 extends TextifyHeader {
	protected $level = 3;
}

class TextifyH4 extends TextifyHeader {
	protected $level = 4;
}

class TextifyH5 extends TextifyHeader {
	protected $level = 5;
}

class TextifyH6 extends TextifyHeader {
	protected $level = 6;
}

class TextifyP extends TextifyBlockElement {
	protected $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 0);
}

class TextifyBlockquote extends TextifyBlockElement {

	public function layout () {
		$this->content = array_map(array($this, 'quote'), $this->content);
		return parent::layout();
 	}

	public function quote ($line) {
		return "> $line";
	}

}

class TextifyListContainer extends TextifyBlockElement {
	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 1, 'left' => 4);
	protected $counter = 0;

	public function additem () {
		return ++$this->counter;
	}

}

class TextifyDl extends TextifyListContainer {
	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 1, 'left' => 0);
}

class TextifyDt extends TextifyBlockElement {
}

class TextifyDd extends TextifyBlockElement {
	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 4);
}

class TextifyUl extends TextifyListContainer {
	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 1, 'left' => 4);
}

class TextifyOl extends TextifyListContainer {
	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 1, 'left' => 4);
}

class TextifyLi extends TextifyBlockElement {

	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0);
	protected $num = false;

	public function __construct ( DOMNode &$tag ) {
		parent::__construct($tag);
		$parent = $this->parent();
		if ( $parent && method_exists($parent, 'additem') )
			$this->num = $parent->additem();
	}

	public function before () {
		if ( 'TextifyOl' == get_class($this->parent()) ) return $this->num . '. ';
		else return '* ';
	}

}

class TextifyHr extends TextifyBlockElement {

	protected $margins = array('top' => 1, 'right' => 0, 'bottom' => 1, 'left' => 0);
	protected $marks = array('inline' => '-');

	public function layout () {
		$this->content = array($this->marks(75));
		return parent::layout();
	}

}

class TextifyTable extends TextifyBlockElement {

	protected $margins = array('top' => 0, 'right' => 0, 'bottom' => 1, 'left' => 0);

	private $rows = 0; // Total number of rows
	private $colwidths = array();

	/**
	 * Table layout engine
	 *
	 * Recursive processing of each node passed off to a renderer for
	 * text formatting and other rendering (borders, padding, markdown marks)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param DOMNode $node The DOMNode to render out
	 * @return string The rendered content
	 **/
	public function render ( DOMNode $node = null ) {

		if ( ! $node ) {
			$node = $this->node;
			if ( ! $node ) return false;
		}
		// No child nodes, render it out to and send back the parent container
		if ( ! $node->hasChildNodes() ) return $this->layout();

		// Step 1: Determine min/max dimensions from rendered content
		foreach ( $node->childNodes as $index => $child ) {
			if ( XML_TEXT_NODE == $child->nodeType || XML_CDATA_SECTION_NODE == $child->nodeType ) {
				$text = trim($child->nodeValue, "\t\n\r\0\x0B");
				if ( ! empty($text) ) $this->append( $this->format($text) );
			} elseif ( XML_ELEMENT_NODE == $child->nodeType) {
				$Renderer = $this->renderer($child);
				$this->append( $Renderer->render() );
			}
		}

		// Step 2: Reflow content based on width constraints
		$this->content = array();
		foreach ( $node->childNodes as $index => $child ) {
			if ( XML_TEXT_NODE == $child->nodeType || XML_CDATA_SECTION_NODE == $child->nodeType ) {
				$text = trim($child->nodeValue, "\t\n\r\0\x0B");
				if ( ! empty($text) ) $this->append( $this->format($text) );
			} elseif ( XML_ELEMENT_NODE == $child->nodeType ) {
				$Renderer = $this->renderer($child);
				$this->append( $Renderer->render() );
			}
		}

		// All done, render it out and send it all back to the parent container
		return $this->layout();

	}

	protected function append ( $content, $block = true ) {
		$lines = array_filter($this->lines($content));
		if ( empty($lines) ) return;

		// Stitch the content of the first new line to the last content in the line list
		$firstline = $lines[0];
		$lastline = false;

		if ( ! empty($this->content) )
			$lastline = $this->content[ count($this->content) - 1 ];

		if ( ! empty($lastline) && $lastline === $firstline ) array_shift($lines);

		$this->content = array_merge($this->content, $lines);
	}

	protected function borders () { /* disabled */ }

	public function addrow () {
		$this->layout[ $this->rows ] = array();
		return $this->rows++;
	}

	public function addrowcolumn ( $row = 0 ) {
		$col = false;
		if ( isset($this->layout[ $row ]) ) {
			$col = count($this->layout[ $row ]);
			$this->layout[ $row ][ $col ] = array();
		}
		return $col;
	}

	public function colwidth ( $column, $width = false ) {
		if ( ! isset($this->colwidths[ $column ]) ) $this->colwidths[ $column ] = 0;
		if ( false !== $width )
			$this->colwidths[ $column ] = max($this->colwidths[ $column ], $width);
		return $this->colwidths[ $column ];
	}

}

class TextifyTableTag extends TextifyBlockElement {

	protected $table = false; // Parent table layout

	public function __construct ( DOMNode &$tag ) {
		parent::__construct($tag);

		$tablenode = $this->tablenode();
		if ( ! $tablenode ) return; // Bail, can't determine table layout

		$this->table = $tablenode->Renderer;
	}

	/**
	 * Find the parent table node
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return DOMNode
	 **/
	public function tablenode () {
		$path = $this->node->getNodePath();
		if ( false === strpos($path, 'table') ) return false;

		$parent = $this->node;
		while ( 'table' != $parent->parentNode->tagName ) {
			$parent = $parent->parentNode;
		}
		return $parent->parentNode;
	}

}

class TextifyTr extends TextifyTableTag {

	private $row = 0;
	private $cols = 0;

	public function __construct ( DOMNode &$tag ) {
		parent::__construct($tag);

		$this->row = $this->table->addrow();
	}

	protected function layout () {
		$_ = array();
		$lines = array();
		foreach ( $this->content as $cells ) {
			$segments = explode("\n", $cells);
			$total = max(count($lines), count($segments));

			for ( $i = 0; $i < $total; $i++ ) {

				if ( ! isset($segments[ $i ]) ) continue;

				if ( isset($lines[ $i ]) && ! empty($lines[ $i ]) ) {
					$eol = strlen($lines[ $i ]) - 1;

					if ( ! empty($segments[ $i ]) && $lines[ $i ]{$eol} == $segments[ $i ]{0} )
						$lines[ $i ] .= substr($segments[ $i ], 1);
					else $lines[ $i ] .= $segments[ $i ];

				} else {
					if ( ! isset($lines[ $i ])) $lines[ $i ] = '';
					$lines[ $i ] .= $segments[ $i ];
				}
			}

		}
		$_[] = join("\n", $lines);
		return join('', $_);
	}

	protected function append ( $content, $block = true ) {
		$this->content[] = $content;
	}

	protected function format ( $text ) { /* disabled */ }

	public function addcolumn ( $column = 0 ) {
		$id = $this->table->addrowcolumn($this->row);
		$this->cols++;
		return $id;
	}

	public function tablerow () {
		return $this->row;
	}

	protected function padding () { /* Disabled */ }

}

class TextifyTd extends TextifyTableTag {

	protected $row = false;
	protected $col = 0;

	protected $padding = array('top' => 0, 'right' => 1, 'bottom' => 0, 'left' => 1);

	private $reported = false;

	public function __construct ( DOMNode &$tag ) {
		parent::__construct($tag);

		$row = $this->getrow();

		if ( 'TextifyTr' != get_class($row) ) {
			trigger_error(sprintf('A <%s> tag must occur inside a <tr>, not a <%s> tag.', $this->tag, $row->tag), E_USER_WARNING);
			return;
		}

		$this->row = $row->tablerow();
		$this->col = $row->addcolumn();
	}

	protected function margins () { /* disabled */ }

	protected function dimensions () {
		parent::dimensions();
		if ( $this->reported ) return;
		$this->table->colwidth($this->col, $this->width['max']);
		$this->reported = true;
	}

	public function width () {
		return $this->table->colwidth($this->col);
	}

	public function getrow () {
		return $this->node->parentNode->Renderer;
	}

}

class TextifyTh extends TextifyTd {

	public function before () { return '['; }

	public function after () { return ']'; }

}

class TextifyFieldset extends TextifyBlockElement {

}

class TextifyLegend extends TextifyBlockElement {

	public function format ($text) {
		$this->legend = $text;
		if (!$this->borders['top']) return '['.$text.']';
	}

}

class TextifyAddress extends TextifyBlockElement {

	// function append ($content,$block=false) {
	// 	$lines = array_filter($this->lines($content));
	// 	if (empty($lines)) return;
	//
	// 	$this->content = array_merge($this->content,$lines);
	//
	// }

}


/**
 * Emogrifier
 * This class provides functions for converting CSS styles into inline style attributes in your HTML code.
 * For more information, please see the README.md file.
 *
 * @author Cameron Brooks
 * @author Jaime Prado
 * @author Roman Ožana <ozana@omdesign.cz>
 * @copyright Copyright (c) 2008-2011 Pelago (http://www.pelagodesign.com/)
 * @see license.txt - THE EMOGRIFIER LICENSE
 *
 * @since 1.2
 */
class Emogrifier {
    /**
     * @var string
     */
    const ENCODING = 'UTF-8';

    /**
     * @var integer
     */
    const CACHE_KEY_CSS = 0;

    /**
     * @var integer
     */
    const CACHE_KEY_SELECTOR = 1;

    /**
     * @var integer
     */
    const CACHE_KEY_XPATH = 2;

    /**
     * @var integer
     */
    const CACHE_KEY_CSS_DECLARATION_BLOCK = 3;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var integer
     */
    const INDEX = 0;

    /**
     * for calculating nth-of-type and nth-child selectors
     *
     * @var integer
     */
    const MULTIPLIER = 1;

    /**
     * @var string
     */
    const ID_ATTRIBUTE_MATCHER = '/(\\w+)?\\#([\\w\\-]+)/';

    /**
     * @var string
     */
    const CLASS_ATTRIBUTE_MATCHER = '/(\\w+|[\\*\\]])?((\\.[\\w\\-]+)+)/';

    /**
     * @var string
     */
    private $html = '';

    /**
     * @var string
     */
    private $css = '';

    /**
     * @var array<string>
     */
    private $unprocessableHtmlTags = array('wbr');

    /**
     * @var array<array>
     */
    private $caches = array(
        self::CACHE_KEY_CSS => array(),
        self::CACHE_KEY_SELECTOR => array(),
        self::CACHE_KEY_XPATH => array(),
        self::CACHE_KEY_CSS_DECLARATION_BLOCK => array(),
    );

    /**
     * the visited nodes with the XPath paths as array keys
     *
     * @var array<\DOMNode>
     */
    private $visitedNodes = array();

    /**
     * the styles to apply to the nodes with the XPath paths as array keys for the outer array and the attribute names/values
     * as key/value pairs for the inner array
     *
     * @var array<array><string>
     */
    private $styleAttributesForNodes = array();

    /**
     * This attribute applies to the case where you want to preserve your original text encoding.
     *
     * By default, emogrifier translates your text into HTML entities for two reasons:
     *
     * 1. Because of client incompatibilities, it is better practice to send out HTML entities rather than unicode over email.
     *
     * 2. It translates any illegal XML characters that DOMDocument cannot work with.
     *
     * If you would like to preserve your original encoding, set this attribute to TRUE.
     *
     * @var boolean
     */
    public $preserveEncoding = FALSE;

    /**
     * The constructor.
     *
     * @param string $html the HTML to emogrify, must be UTF-8-encoded
     * @param string $css the CSS to merge, must be UTF-8-encoded
     */
    public function __construct($html = '', $css = '') {
        $this->setHtml($html);
        $this->setCss($css);
    }

    /**
     * The destructor.
     */
    public function __destruct() {
        $this->purgeVisitedNodes();
    }

    /**
     * Sets the HTML to emogrify.
     *
     * @param string $html the HTML to emogrify, must be UTF-8-encoded
     *
     * @return void
     */
    public function setHtml($html = '') {
        $this->html = $html;
    }

    /**
     * Sets the CSS to merge with the HTML.
     *
     * @param string $css the CSS to merge, must be UTF-8-encoded
     *
     * @return void
     */
    public function setCss($css = '') {
        $this->css = $css;
    }

    /**
     * Clears all caches.
     *
     * @return void
     */
    private function clearAllCaches() {
        $this->clearCache(self::CACHE_KEY_CSS);
        $this->clearCache(self::CACHE_KEY_SELECTOR);
        $this->clearCache(self::CACHE_KEY_XPATH);
        $this->clearCache(self::CACHE_KEY_CSS_DECLARATION_BLOCK);
    }

    /**
     * Clears a single cache by key.
     *
     * @param integer $key the cache key, must be CACHE_KEY_CSS, CACHE_KEY_SELECTOR, CACHE_KEY_XPATH or CACHE_KEY_CSS_DECLARATION_BLOCK
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function clearCache($key) {
        $allowedCacheKeys = array(self::CACHE_KEY_CSS, self::CACHE_KEY_SELECTOR, self::CACHE_KEY_XPATH, self::CACHE_KEY_CSS_DECLARATION_BLOCK);
        if (!in_array($key, $allowedCacheKeys, TRUE)) {
            throw new InvalidArgumentException('Invalid cache key: ' . $key, 1391822035);
        }

        $this->caches[$key] = array();
    }

    /**
     * Purges the visited nodes.
     *
     * @return void
     */
    private function purgeVisitedNodes() {
        $this->visitedNodes = array();
        $this->styleAttributesForNodes = array();
    }

    /**
     * Marks a tag for removal.
     *
     * There are some HTML tags that DOMDocument cannot process, and it will throw an error if it encounters them.
     * In particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
     *
     * Note: The tags will not be removed if they have any content.
     *
     * @param string $tagName the tag name, e.g., "p"
     *
     * @return void
     */
    public function addUnprocessableHtmlTag($tagName) {
        $this->unprocessableHtmlTags[] = $tagName;
    }

    /**
     * Drops a tag from the removal list.
     *
     * @param string $tagName the tag name, e.g., "p"
     *
     * @return void
     */
    public function removeUnprocessableHtmlTag($tagName) {
        $key = array_search($tagName, $this->unprocessableHtmlTags, TRUE);
        if ($key !== FALSE) {
            unset($this->unprocessableHtmlTags[$key]);
        }
    }

    /**
     * Applies the CSS you submit to the HTML you submit.
     *
     * This method places the CSS inline.
     *
     * @return string
     *
     * @throws \BadMethodCallException
     */
    public function emogrify() {
        if ($this->html === '') {
            throw new BadMethodCallException('Please set some HTML first before calling emogrify.', 1390393096);
        }

        $xmlDocument = $this->createXmlDocument();
        $xpath = new DOMXPath($xmlDocument);
        $this->clearAllCaches();

        // before be begin processing the CSS file, parse the document and normalize all existing CSS attributes (changes 'DISPLAY: none' to 'display: none');
        // we wouldn't have to do this if DOMXPath supported XPath 2.0.
        // also store a reference of nodes with existing inline styles so we don't overwrite them
        $this->purgeVisitedNodes();

        $nodesWithStyleAttributes = $xpath->query('//*[@style]');
        if ($nodesWithStyleAttributes !== FALSE) {
            /** @var $nodeWithStyleAttribute \DOMNode */
            foreach ($nodesWithStyleAttributes as $node) {
                $normalizedOriginalStyle = preg_replace_callback(
                    '/[A-z\\-]+(?=\\:)/S',
					create_function('$m', 'return strtolower($m[0]);'),
                    $node->getAttribute('style')
                );

                // in order to not overwrite existing style attributes in the HTML, we have to save the original HTML styles
                $nodePath = $node->getNodePath();
                if (!isset($this->styleAttributesForNodes[$nodePath])) {
                    $this->styleAttributesForNodes[$nodePath] = $this->parseCssDeclarationBlock($normalizedOriginalStyle);
                    $this->visitedNodes[$nodePath] = $node;
                }

                $node->setAttribute('style', $normalizedOriginalStyle);
            }
        }

        // grab any existing style blocks from the html and append them to the existing CSS
        // (these blocks should be appended so as to have precedence over conflicting styles in the existing CSS)
        $allCss = $this->css;

        $allCss .= $this->getCssFromAllStyleNodes($xpath);

        $cssParts = $this->splitCssAndMediaQuery($allCss);

        $cssKey = md5($cssParts['css']);
        if (!isset($this->caches[self::CACHE_KEY_CSS][$cssKey])) {
            // process the CSS file for selectors and definitions
            preg_match_all('/(?:^|[\\s^{}]*)([^{]+){([^}]*)}/mis', $cssParts['css'], $matches, PREG_SET_ORDER);

            $allSelectors = array();
            foreach ($matches as $key => $selectorString) {
                // if there is a blank definition, skip
                if (!strlen(trim($selectorString[2]))) {
                    continue;
                }

                // else split by commas and duplicate attributes so we can sort by selector precedence
                $selectors = explode(',', $selectorString[1]);
                foreach ($selectors as $selector) {
                    // don't process pseudo-elements and behavioral (dynamic) pseudo-classes; ONLY allow structural pseudo-classes
                    if (strpos($selector, ':') !== FALSE && !preg_match('/:\\S+\\-(child|type)\\(/i', $selector)) {
                        continue;
                    }

                    $allSelectors[] = array('selector' => trim($selector),
                                             'attributes' => trim($selectorString[2]),
                                             // keep track of where it appears in the file, since order is important
                                             'line' => $key,
                    );
                }
            }

            // now sort the selectors by precedence
            usort($allSelectors, array($this,'sortBySelectorPrecedence'));

            $this->caches[self::CACHE_KEY_CSS][$cssKey] = $allSelectors;
        }

        foreach ($this->caches[self::CACHE_KEY_CSS][$cssKey] as $value) {
            // query the body for the xpath selector
            $nodesMatchingCssSelectors = $xpath->query($this->translateCssToXpath($value['selector']));

            /** @var $node \DOMNode */
            foreach ($nodesMatchingCssSelectors as $node) {
                // if it has a style attribute, get it, process it, and append (overwrite) new stuff
                if ($node->hasAttribute('style')) {
                    // break it up into an associative array
                    $oldStyleDeclarations = $this->parseCssDeclarationBlock($node->getAttribute('style'));
                } else {
                    $oldStyleDeclarations = array();
                }
                $newStyleDeclarations = $this->parseCssDeclarationBlock($value['attributes']);
                $node->setAttribute('style', $this->generateStyleStringFromDeclarationsArrays($oldStyleDeclarations, $newStyleDeclarations));
            }
        }

        // now iterate through the nodes that contained inline styles in the original HTML
        foreach ($this->styleAttributesForNodes as $nodePath => $styleAttributesForNode) {
            $node = $this->visitedNodes[$nodePath];
            $currentStyleAttributes = $this->parseCssDeclarationBlock($node->getAttribute('style'));
            $node->setAttribute('style', $this->generateStyleStringFromDeclarationsArrays($currentStyleAttributes, $styleAttributesForNode));
        }

        // This removes styles from your email that contain display:none.
        // We need to look for display:none, but we need to do a case-insensitive search. Since DOMDocument only supports XPath 1.0,
        // lower-case() isn't available to us. We've thus far only set attributes to lowercase, not attribute values. Consequently, we need
        // to translate() the letters that would be in 'NONE' ("NOE") to lowercase.
        $nodesWithStyleDisplayNone = $xpath->query('//*[contains(translate(translate(@style," ",""),"NOE","noe"),"display:none")]');
        // The checks on parentNode and is_callable below ensure that if we've deleted the parent node,
        // we don't try to call removeChild on a nonexistent child node
        if ($nodesWithStyleDisplayNone->length > 0) {
            /** @var $node \DOMNode */
            foreach ($nodesWithStyleDisplayNone as $node) {
                if ($node->parentNode && is_callable(array($node->parentNode,'removeChild'))) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        $this->copyCssWithMediaToStyleNode($cssParts, $xmlDocument);

        if ($this->preserveEncoding && function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($xmlDocument->saveHTML(), self::ENCODING, 'HTML-ENTITIES');
        } else {
            return $xmlDocument->saveHTML();
        }
    }


    /**
     * This method merges old or existing name/value array with new name/value array
     * and then generates a string of the combined style suitable for placing inline.
     * This becomes the single point for CSS string generation allowing for consistent
     * CSS output no matter where the CSS originally came from.
     * @param array $oldStyles
     * @param array $newStyles
     * @return string
     */
    private function generateStyleStringFromDeclarationsArrays(array $oldStyles, array $newStyles) {
        $combinedStyles = array_merge($oldStyles, $newStyles);
        $style = '';
        foreach ($combinedStyles as $attributeName => $attributeValue) {
            $style .= (strtolower(trim($attributeName)) . ': ' . trim($attributeValue) . '; ');
        }
        return trim($style);
    }


    /**
     * Copies the media part from CSS array parts to $xmlDocument.
     *
     * @param array $cssParts
     * @param \DOMDocument $xmlDocument
     * @return void
     */
    public function copyCssWithMediaToStyleNode(array $cssParts, DOMDocument $xmlDocument) {
        if (isset($cssParts['media']) && $cssParts['media'] !== '') {
            $this->addStyleElementToDocument($xmlDocument, $cssParts['media']);
        }
    }

    /**
     * Returns CSS content.
     *
     * @param \DOMXPath $xpath
     * @return string
     */
    private function getCssFromAllStyleNodes(DOMXPath $xpath) {
        $styleNodes = $xpath->query('//style');

        if ($styleNodes === FALSE) {
            return '';
        }

        $css = '';
        /** @var $styleNode \DOMNode */
        foreach ($styleNodes as $styleNode) {
            $css .= "\n\n" . $styleNode->nodeValue;
            $styleNode->parentNode->removeChild($styleNode);
        }

        return $css;
    }

    /**
     * Adds a style element with $css to $document.
     *
     * @param \DOMDocument $document
     * @param string $css
     * @return void
     */
    private function addStyleElementToDocument(DOMDocument $document, $css) {
        $styleElement = $document->createElement('style', $css);
        $styleAttribute = $document->createAttribute('type');
        $styleAttribute->value = 'text/css';
        $styleElement->appendChild($styleAttribute);

        $head = $this->getOrCreateHeadElement($document);
        $head->appendChild($styleElement);
    }

    /**
     * Returns the existing or creates a new head element in $document.
     *
     * @param \DOMDocument $document
     * @return \DOMNode the head element
     */
    private function getOrCreateHeadElement(DOMDocument $document) {
        $head = $document->getElementsByTagName('head')->item(0);

        if ($head === NULL) {
            $head = $document->createElement('head');
            $html = $document->getElementsByTagName('html')->item(0);
            $html->insertBefore($head, $document->getElementsByTagName('body')->item(0));
        }

        return $head;
    }

    /**
     * Splits input CSS code to an array where:
     *
     * - key "css" will be contains clean CSS code
     * - key "media" will be contains all valuable media queries
     *
     * Example:
     *
     * The CSS code
     *
     *   "@import "file.css"; h1 { color:red; } @media { h1 {}} @media tv { h1 {}}"
     *
     * will be parsed into the following array:
     *
     *   "css" => "h1 { color:red; }"
     *   "media" => "@media { h1 {}}"
     *
     * @param string $css
     * @return array
     */
    private function splitCssAndMediaQuery($css) {
        $media = '';

        $css = preg_replace_callback(
            '#@media\\s+(?:only\\s)?(?:[\\s{\(]|screen|all)\\s?[^{]+{.*}\\s*}\\s*#misU',
			create_function('$matches, &$media', '$media .= $matches[0];'),
            $css
        );

        // filter the CSS
        $search = array(
            // get rid of css comment code
            '/\\/\\*.*\\*\\//sU',
            // strip out any import directives
            '/^\\s*@import\\s[^;]+;/misU',
            // strip remains media enclosures
            '/^\\s*@media\\s[^{]+{(.*)}\\s*}\\s/misU',
        );

        $replace = array(
            '',
            '',
            '',
        );

        // clean CSS before output
        $css = preg_replace($search, $replace, $css);

        return array('css' => $css, 'media' => $media);
    }

    /**
     * Creates a DOMDocument instance with the current HTML.
     *
     * @return \DOMDocument
     */
    private function createXmlDocument() {
        $xmlDocument = new DOMDocument;
        $xmlDocument->encoding = self::ENCODING;
        $xmlDocument->strictErrorChecking = FALSE;
        $xmlDocument->formatOutput = TRUE;
        $libXmlState = libxml_use_internal_errors(TRUE);
        $xmlDocument->loadHTML($this->getUnifiedHtml());
        libxml_clear_errors();
        libxml_use_internal_errors($libXmlState);
        $xmlDocument->normalizeDocument();

        return $xmlDocument;
    }

    /**
     * Returns the HTML with the non-ASCII characters converts into HTML entities and the unprocessable HTML tags removed.
     *
     * @return string the unified HTML
     *
     * @throws \BadMethodCallException
     */
    private function getUnifiedHtml() {
        if (!empty($this->unprocessableHtmlTags)) {
            $unprocessableHtmlTags = implode('|', $this->unprocessableHtmlTags);
            $bodyWithoutUnprocessableTags = preg_replace('/<\\/?(' . $unprocessableHtmlTags . ')[^>]*>/i', '', $this->html);
        } else {
            $bodyWithoutUnprocessableTags = $this->html;
        }

		if ( function_exists('mb_convert_encoding') )
			return mb_convert_encoding($bodyWithoutUnprocessableTags, 'HTML-ENTITIES', self::ENCODING);
		else return htmlspecialchars_decode(utf8_decode(htmlentities($bodyWithoutUnprocessableTags, ENT_COMPAT, self::ENCODING, false)));
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return integer
     */
    private function sortBySelectorPrecedence(array $a, array $b) {
        $precedenceA = $this->getCssSelectorPrecedence($a['selector']);
        $precedenceB = $this->getCssSelectorPrecedence($b['selector']);

        // We want these sorted in ascending order so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last.
        // The parenthesis around the -1 are necessary to avoid a PHP_CodeSniffer warning about missing spaces around
        // arithmetic operators.
        // @see http://forge.typo3.org/issues/55605
        $precedenceForEquals = ($a['line'] < $b['line'] ? (-1) : 1);
        $precedenceForNotEquals = ($precedenceA < $precedenceB ? (-1) : 1);
        return ($precedenceA === $precedenceB) ? $precedenceForEquals : $precedenceForNotEquals;
    }

    /**
     * @param string $selector
     *
     * @return integer
     */
    private function getCssSelectorPrecedence($selector) {
        $selectorKey = md5($selector);
        if (!isset($this->caches[self::CACHE_KEY_SELECTOR][$selectorKey])) {
            $precedence = 0;
            $value = 100;
            // ids: worth 100, classes: worth 10, elements: worth 1
            $search = array('\\#','\\.','');

            foreach ($search as $s) {
                if (trim($selector == '')) {
                    break;
                }
                $number = 0;
                $selector = preg_replace('/' . $s . '\\w+/', '', $selector, -1, $number);
                $precedence += ($value * $number);
                $value /= 10;
            }
            $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey] = $precedence;
        }

        return $this->caches[self::CACHE_KEY_SELECTOR][$selectorKey];
    }

    /**
     * Right now, we support all CSS 1 selectors and most CSS2/3 selectors.
     *
     * @see http://plasmasturm.org/log/444/
     *
     * @param string $paramCssSelector
     *
     * @return string
     */
    private function translateCssToXpath($paramCssSelector) {
        $cssSelector = ' ' . $paramCssSelector . ' ';
        $cssSelector = preg_replace_callback('/\s+\w+\s+/',
			create_function('$matches', 'return strtolower($matches[0]);'),
            $cssSelector
        );
        $cssSelector = trim($cssSelector);
        $xpathKey = md5($cssSelector);
        if (!isset($this->caches[self::CACHE_KEY_XPATH][$xpathKey])) {
            // returns an Xpath selector
            $search = array(
                // Matches any element that is a child of parent.
                '/\\s+>\\s+/',
                // Matches any element that is an adjacent sibling.
                '/\\s+\\+\\s+/',
                // Matches any element that is a descendant of an parent element element.
                '/\\s+/',
                // first-child pseudo-selector
                '/([^\\/]+):first-child/i',
                // last-child pseudo-selector
                '/([^\\/]+):last-child/i',
                // Matches attribute only selector
                '/^\\[(\\w+)\\]/',
                // Matches element with attribute
                '/(\\w)\\[(\\w+)\\]/',
                // Matches element with EXACT attribute
                '/(\\w)\\[(\\w+)\\=[\'"]?(\\w+)[\'"]?\\]/',
            );
            $replace = array(
                '/',
                '/following-sibling::*[1]/self::',
                '//',
                '*[1]/self::\\1',
                '*[last()]/self::\\1',
                '*[@\\1]',
                '\\1[@\\2]',
                '\\1[@\\2="\\3"]',
            );

            $cssSelector = '//' . preg_replace($search, $replace, $cssSelector);

            $cssSelector = preg_replace_callback(self::ID_ATTRIBUTE_MATCHER, array($this, 'matchIdAttributes'), $cssSelector);
            $cssSelector = preg_replace_callback(self::CLASS_ATTRIBUTE_MATCHER, array($this, 'matchClassAttributes'), $cssSelector);

            // Advanced selectors are going to require a bit more advanced emogrification.
            // When we required PHP 5.3, we could do this with closures.
            $cssSelector = preg_replace_callback(
                '/([^\\/]+):nth-child\\(\s*(odd|even|[+\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
                array($this, 'translateNthChild'), $cssSelector
            );
            $cssSelector = preg_replace_callback(
                '/([^\\/]+):nth-of-type\\(\s*(odd|even|[+\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i',
                array($this, 'translateNthOfType'), $cssSelector
            );

            $this->caches[self::CACHE_KEY_SELECTOR][$xpathKey] = $cssSelector;
        }
        return $this->caches[self::CACHE_KEY_SELECTOR][$xpathKey];
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function matchIdAttributes(array $match) {
        return (strlen($match[1]) ? $match[1] : '*') . '[@id="' . $match[2] . '"]';
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function matchClassAttributes(array $match) {
        return (strlen($match[1]) ? $match[1] : '*') . '[contains(concat(" ",@class," "),concat(" ","' .
            implode(
                '"," "))][contains(concat(" ",@class," "),concat(" ","',
                explode('.', substr($match[2], 1))
            ) . '"," "))]';
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function translateNthChild(array $match) {
        $result = $this->parseNth($match);

        if (isset($result[self::MULTIPLIER])) {
            if ($result[self::MULTIPLIER] < 0) {
                $result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);
                return sprintf('*[(last() - position()) mod %u = %u]/self::%s', $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
            } else {
                return sprintf('*[position() mod %u = %u]/self::%s', $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
            }
        } else {
            return sprintf('*[%u]/self::%s', $result[self::INDEX], $match[1]);
        }
    }

    /**
     * @param array $match
     *
     * @return string
     */
    private function translateNthOfType(array $match) {
        $result = $this->parseNth($match);

        if (isset($result[self::MULTIPLIER])) {
            if ($result[self::MULTIPLIER] < 0) {
                $result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);
                return sprintf('%s[(last() - position()) mod %u = %u]', $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
            } else {
                return sprintf('%s[position() mod %u = %u]', $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
            }
        } else {
            return sprintf('%s[%u]', $match[1], $result[self::INDEX]);
        }
    }

    /**
     * @param array $match
     *
     * @return array
     */
    private function parseNth(array $match) {
        if (in_array(strtolower($match[2]), array('even','odd'))) {
            $index = strtolower($match[2]) == 'even' ? 0 : 1;
            return array(self::MULTIPLIER => 2, self::INDEX => $index);
        } elseif (stripos($match[2], 'n') === FALSE) {
            // if there is a multiplier
            $index = intval(str_replace(' ', '', $match[2]));
            return array(self::INDEX => $index);
        } else {
            if (isset($match[3])) {
                $multipleTerm = str_replace($match[3], '', $match[2]);
                $index = intval(str_replace(' ', '', $match[3]));
            } else {
                $multipleTerm = $match[2];
                $index = 0;
            }

            $multiplier = str_ireplace('n', '', $multipleTerm);

            if (!strlen($multiplier)) {
                $multiplier = 1;
            } elseif ($multiplier == 0) {
                return array(self::INDEX => $index);
            } else {
                $multiplier = intval($multiplier);
            }

            while ($index < 0) {
                $index += abs($multiplier);
            }

            return array(self::MULTIPLIER => $multiplier, self::INDEX => $index);
        }
    }

    /**
     * Parses a CSS declaration block into property name/value pairs.
     *
     * Example:
     *
     * The declaration block
     *
     *   "color: #000; font-weight: bold;"
     *
     * will be parsed into the following array:
     *
     *   "color" => "#000"
     *   "font-weight" => "bold"
     *
     * @param string $cssDeclarationBlock the CSS declaration block without the curly braces, may be empty
     *
     * @return array the CSS declarations with the property names as array keys and the property values as array values
     */
    private function parseCssDeclarationBlock($cssDeclarationBlock) {
        if (isset($this->caches[self::CACHE_KEY_CSS_DECLARATION_BLOCK][$cssDeclarationBlock])) {
            return $this->caches[self::CACHE_KEY_CSS_DECLARATION_BLOCK][$cssDeclarationBlock];
        }

        $properties = array();
        $declarations = explode(';', $cssDeclarationBlock);
        foreach ($declarations as $declaration) {
            $matches = array();
            if (!preg_match('/ *([A-Za-z\\-]+) *: *([^;]+) */', $declaration, $matches)) {
                continue;
            }
            $propertyName = strtolower($matches[1]);
            $propertyValue = $matches[2];
            $properties[$propertyName] = $propertyValue;
        }
        $this->caches[self::CACHE_KEY_CSS_DECLARATION_BLOCK][$cssDeclarationBlock] = $properties;

        return $properties;
    }
} // END class Emogrifier
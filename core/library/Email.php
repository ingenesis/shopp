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
			$Emogrifier = new \Pelago\Emogrifier($message, $stylesheet);
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
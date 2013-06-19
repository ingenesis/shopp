<?php
/**
 * Email
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

class ShoppEmailDefaultFilters extends ShoppEmailFilters {

	function __construct () {
		do_action('shopp_email_filters');
		add_filter('shopp_email_message',array('ShoppEmailDefaultFilters','AutoMultipart'));
		add_filter('shopp_email_message',array('ShoppEmailDefaultFilters','InlineStyles'),99);
	}

}

abstract class ShoppEmailFilters {

	static function InlineStyles ($message) {

		if ( false === strpos($message,'<html>') ) return $message;
		$cssfile = locate_shopp_template(array('email.css'));
		$stylesheet = file_get_contents($cssfile);

		if (!empty($stylesheet)) {
			$Emogrifier = new Emogrifier($message,$stylesheet);
			$message = $Emogrifier->emogrify();
		}

		return $message;

	}

	static function AutoMultipart ($message) {
		add_action('phpmailer_init',array('ShoppEmailDefaultFilters','AltBody') );
		return $message;
	}

	static function AltBody ($phpmailer) {
		$Textify = new Textify($phpmailer->Body);
		$phpmailer->AltBody = $Textify->render();
	}

}


/**
 * Textify
 * Convert HTML markup to plain text Markdown
 *
 * @copyright Copyright (c) 2011 Ingenesis Limited
 * @author Jonathan Davis
 * @since 1.2
 * @package textify
 **/
class Textify {

	private $markup = false;
	private $DOM = false;

	function __construct ($markup) {
		$this->markup = $markup;
        $DOM = new DOMDocument();
        $DOM->loadHTML($markup);
		$DOM->normalizeDocument();
		$this->DOM = $DOM;
	}

	function render () {
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
		'padding' => array('top'=>' ','right'=>' ','bottom' =>' ','left'=>' '),
		'margins' => array('top'=>' ','right'=>' ','bottom' =>' ','left'=>' '),
		'borders' => array('top'=>'-','right'=>'|','bottom' =>'-','left'=>'|'),
		'corners' => array('top-left'=>'&middot;','top-right' => '&middot;','bottom-right' => '&middot;','bottom-left' => '&middot;','middle-middle'=> '&middot;','top-middle'=>'&middot;','middle-left'=>'&middot;','middle-right'=>'&middot;','bottom-middle'=>'&middot;')
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

	protected $borders = array('top'=>0,'right'=>0,'bottom'=>0,'left'=>0);
	protected $margins = array('top'=>0,'right'=>0,'bottom'=>0,'left'=>0);

	function __construct (&$tag) {
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
	function render ($node = false) {

		if ( !$node ) {
			$node = $this->node;
			if (!$node) return false;
		}
		if ($node->hasAttributes()) {
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
	function layout () {
		// Follows box model standards

		$this->prepend( $this->before() );	// Add before content
		$this->append( $this->after() );	// Add after content

		$this->padding(); 					// Add padding box

		$this->dimensions();				// Calculate final dimensions

		$this->borders();					// Add border decoration box
		$this->margins();					// Add margins box

		// Send the string back to the parent renderer
		return join(TextifyTag::NEWLINE,$this->content);
 	}


	function append ($content,$block=false) {
		$lines = array_filter($this->lines($content));
		if (empty($lines)) return;

		if (!$block) {
			// Stitch the content of the first new line to the last content in the line list
			$firstline = array_shift($lines);
			if (!is_null($firstline) && !empty($this->content)) {
				$id = count($this->content)-1;
				$this->content[ $id ] .= $firstline;

				// Determine if max width has changed
				$this->width['max'] = max($this->width['max'],strlen($this->content[$id]));
			} else $this->content[] = $firstline;
		}

		$this->content = array_merge($this->content,$lines);
	}

	function prepend ($content) {
		$lines = array_filter($this->lines($content));
		if (empty($lines)) return;

		// Stitch the content of the last new line to the first line of the current content line list
		$lastline = array_pop($lines);
		$this->content[0] = $lastline.$this->content[0];
		$this->width['max'] = max($this->width['max'],strlen($this->content[0]));
		$this->content[0] = TextifyTag::whitespace($this->content[0]);

		$this->content = array_merge($lines,$this->content);
	}

	function lines ($content) {
		if (is_array($content)) $content = join('',$content);

		if (empty($content)) return array();
		$linebreaks = TextifyTag::NEWLINE;
		$wordbreaks = " \t";

		$maxline = 0; $maxword = 0;
		$lines = explode($linebreaks,$content);
		foreach ((array)$lines as $line) {
			$maxline = max($maxline,strlen($line));

			$word = false;
			$word = strtok($line,$wordbreaks);
			while (false !== $word) {
				$maxword = max($maxword,strlen($word));
				$word = strtok($wordbreaks);
			}
		}

		$this->width['min'] = max($this->width['min'],$maxword);
		$this->width['max'] = max($this->width['max'],$maxline);

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
	function dimensions () {
		$this->lines(join(TextifyTag::NEWLINE,$this->content));
	}

	function before () {
		// if (TextifyTag::DEBUG) return "&lt;$this->tag&gt;";
	}

	function format ($text) {
		return TextifyTag::whitespace($text);
	}

	function after () {
		// if (TextifyTag::DEBUG) return "&lt;/$this->tag&gt;";
	}

	function padding () { /* placeholder */ }

	function borders () { /* placeholder */ }

	function margins () { /* placeholder */ }


	/**
	 * Mark renderer
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	function marks ($repeat = 1) {
		return str_repeat($this->marks['inline'],$repeat);
	}

	function linebreak () {
		return self::NEWLINE;
	}

	/**
	 * Collapses whitespace into a single space
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void Description...
	 **/
	static function whitespace ($text) {
		return preg_replace('/\s+/', ' ', $text);
	}

	function renderer ($tag) {
		if (isset($tag->Renderer)) {
			$tag->Renderer->content = array();
			return $tag->Renderer;
		}

		$Tagname = ucfirst($tag->tagName);
		$Renderer = self::CLASSPREFIX.$Tagname;
		if (!class_exists($Renderer)) $Renderer = __CLASS__;

		$tag->Renderer = new $Renderer($tag);
		return $tag->Renderer;
	}

	function parent () {
		return $this->node->parentNode->Renderer;
	}

	function styles ($string) {

	}

}

class TextifyInlineElement extends TextifyTag {

	function before () { return $this->marks(); }

	function after () { return $this->marks(); }

}

class TextifyA extends TextifyInlineElement {

	function before () {
		return '<';
	}

	function after () {
		$string = '';
		if (isset($this->attrs['href']) && !empty($this->attrs['href'])) {
			$href = $this->attrs['href'];
			if ('#' != $href{0}) $string .= ': '.$href;
		}
		return $string.'>';
	}

}

class TextifyEm extends TextifyInlineElement {

	var $marks = array('inline' => '_');

}

class TextifyStrong extends TextifyInlineElement {

	var $marks = array('inline' => '**');

}

class TextifyCode extends TextifyInlineElement {

	var $marks = array('inline' => '`');

}


class TextifyBr extends TextifyInlineElement {

	function layout () {
		$this->content = array(' ',' ');
		return parent::layout();
	}

}

class TextifyBlockElement extends TextifyTag {

	protected $block = true;

	protected $margins = array('top' => 0,'right' => 0,'bottom' => 0,'left' => 0);
	protected $borders = array('top' => 0,'right' => 0,'bottom' => 0,'left' => 0);
	protected $padding = array('top' => 0,'right' => 0,'bottom' => 0,'left' => 0);

	function width () {
		return $this->width['max'];
	}

	function box (&$lines,$type='margins') {
		if (!isset($this->marks[$type])) return;

		$size = 0;
		$marks = array('top' => '','right' => '', 'bottom' => '', 'left' => '');
		if (isset($this->marks[ $type ]) && !empty($this->marks[ $type ]))
			$marks = array_merge($marks,$this->marks[ $type ]);

 		if ( isset($this->$type) ) $sizes = $this->$type;

		$left = str_repeat($marks['left'],$sizes['left']);
		$right = str_repeat($marks['right'],$sizes['right']);

		$width = $this->width();
		$boxwidth = $width;
		foreach ($lines as &$line) {
			if (empty($line)) $line = $left.str_repeat(TextifyTag::STRPAD,$width).$right;

			else $line = $left.str_pad($line,$width,TextifyTag::STRPAD).$right;
			$boxwidth = max($boxwidth,strlen($line));
		}

		if ( $sizes['top'] ) {
			for ($i = 0; $i < $sizes['top']; $i++) {
				$top = str_repeat($marks['top'],$boxwidth);
				if ('borders' == $type) $this->legend($top);
				array_unshift( $lines, $top );
			}
		}


		if ( $sizes['bottom']  )
			for ($i = 0; $i < $sizes['bottom']; $i++)
				array_push( $lines, str_repeat($marks['bottom'],$boxwidth) );

	}

	function padding () {
		$this->box($this->content,'padding');
	}

	function borders () {
		$this->box($this->content,'borders');
	}

	function margins () {
		$this->box($this->content,'margins');
	}

	function legend ($string) {
		if (TextifyTag::DEBUG) $legend = $this->tag;
		else $legend = $this->legend;

		return substr($string,0,2).$legend.substr($string,(2+strlen($legend)));
	}

}

class TextifyDiv extends TextifyBlockElement {
}

class TextifyHeader extends TextifyBlockElement {

	var $level = 1;
	var $marks = array('inline' => '#');
	var $margins = array('top' => 1,'right' => 0,'bottom' => 1,'left' => 0);

	function before () {
		$text = parent::before();
		$text .= $this->marks($this->level).' ';
		return $text;
	}

	function after () {
		$text = ' '.$this->marks($this->level);
		$text .= parent::after();
		return $text;
	}

}

class TextifyH1 extends TextifyHeader {
	var $marks = array('inline' => '=');

	function before () {}

	function format ($text) {
		$marks = $this->marks(strlen($text));
		return "$text\n$marks";
	}

	function after () {}
}

class TextifyH2 extends TextifyH1 {
	var $level = 2;
	var $marks = array('inline' => '-');
}

class TextifyH3 extends TextifyHeader {
	var $level = 3;
}

class TextifyH4 extends TextifyHeader {
	var $level = 4;
}

class TextifyH5 extends TextifyHeader {
	var $level = 5;
}

class TextifyH6 extends TextifyHeader {
	var $level = 6;
}

class TextifyP extends TextifyBlockElement {
	var $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 0);
}

class TextifyBlockquote extends TextifyBlockElement {

	function layout () {
		$this->content = array_map(array($this,'quote'),$this->content);
		return parent::layout();
 	}

	function quote ($line) {
		return "> $line";
	}

}

class TextifyListContainer extends TextifyBlockElement {
	var $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 4);
	var $counter = 0;

	function additem () {
		return ++$this->counter;
	}

}

class TextifyDl extends TextifyListContainer {
	var $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 0);
}

class TextifyDt extends TextifyBlockElement {
}

class TextifyDd extends TextifyBlockElement {
	var $margins = array('top' => 0,'right' => 0,'bottom' => 0,'left' => 4);
}

class TextifyUl extends TextifyListContainer {
	var $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 4);
}

class TextifyOl extends TextifyListContainer {
	var $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 4);
}

class TextifyLi extends TextifyBlockElement {

	var $margins = array('top' => 0,'right' => 0,'bottom' => 0,'left' => 0);
	var $num = false;

	function __construct(&$tag) {
		parent::__construct($tag);
		$parent = $this->parent();
		if ($parent && method_exists($parent,'additem'))
			$this->num = $parent->additem();
	}

	function before () {
		if ('TextifyOl' == get_class($this->parent())) return $this->num.'. ';
		else return '* ';
	}

}

class TextifyHr extends TextifyBlockElement {

	var $margins = array('top' => 1,'right' => 0,'bottom' => 1,'left' => 0);
	var $marks = array('inline' => '-');

	function layout () {
		$this->content = array($this->marks(75));
		return parent::layout();
	}

}

class TextifyTable extends TextifyBlockElement {

	var $margins = array('top' => 0,'right' => 0,'bottom' => 1,'left' => 0);

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
	function render ($node = false) {

		if ( !$node ) {
			$node = $this->node;
			if (!$node) return false;
		}
		// No child nodes, render it out to and send back the parent container
		if ( ! $node->hasChildNodes() ) return $this->layout();

		// Step 1: Determine min/max dimensions from rendered content
		foreach ($node->childNodes as $index => $child) {
			if ( XML_TEXT_NODE == $child->nodeType || XML_CDATA_SECTION_NODE == $child->nodeType ) {
				$text = trim($child->nodeValue,"\t\n\r\0\x0B");
				if (!empty($text)) $this->append( $this->format($text) );
			} elseif ( XML_ELEMENT_NODE == $child->nodeType) {
				$Renderer = $this->renderer($child);
				$this->append( $Renderer->render() );
			}
		}

		// Step 2: Reflow content based on width constraints
		$this->content = array();
		foreach ($node->childNodes as $index => $child) {
			if ( XML_TEXT_NODE == $child->nodeType || XML_CDATA_SECTION_NODE == $child->nodeType ) {
				$text = trim($child->nodeValue,"\t\n\r\0\x0B");
				if (!empty($text)) $this->append( $this->format($text) );
			} elseif ( XML_ELEMENT_NODE == $child->nodeType) {
				$Renderer = $this->renderer($child);
				$this->append( $Renderer->render() );
			}
		}

		// All done, render it out and send it all back to the parent container
		return $this->layout();

	}

	function append ($content,$block=true) {
		$lines = array_filter($this->lines($content));
		if (empty($lines)) return;

		// Stitch the content of the first new line to the last content in the line list
		$firstline = $lines[0];
		$lastline = false;

		if ( ! empty($this->content) )
			$lastline = $this->content[ count($this->content)-1 ];

		if (!empty($lastline) && $lastline === $firstline) array_shift($lines);

		$this->content = array_merge($this->content,$lines);
	}

	function borders () { /* disabled */ }

	function addrow () {
		$this->layout[$this->rows] = array();
		return $this->rows++;
	}

	function addrowcolumn ($row = 0) {
		$col = false;
		if (isset($this->layout[$row])) {
			$col = count($this->layout[$row]);
			$this->layout[$row][$col] = array();
		}
		return $col;
	}

	function colwidth ($column,$width=false) {
		if ( ! isset($this->colwidths[$column]) ) $this->colwidths[$column] = 0;
		if (false !== $width)
			$this->colwidths[$column] = max($this->colwidths[$column],$width);
		return $this->colwidths[$column];
	}

}

class TextifyTableTag extends TextifyBlockElement {

	protected $table = false; // Parent table layout

	function __construct ($tag) {
		parent::__construct($tag);

		$tablenode = $this->tablenode();
		if (!$tablenode) return; // Bail, can't determine table layout

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
	function tablenode () {
		$path = $this->node->getNodePath();
		if (false === strpos($path,'table')) return false;

		$parent = $this->node;
		while ('table' != $parent->parentNode->tagName) {
			$parent = $parent->parentNode;
		}
		return $parent->parentNode;
	}

}

class TextifyTr extends TextifyTableTag {

	private $row = 0;
	private $cols = 0;

	function __construct ($tag) {
		parent::__construct($tag);

		$this->row = $this->table->addrow();
	}

	function layout () {
		$_ = array();
		$lines = array();
		foreach ($this->content as $cells) {
			$segments = explode("\n",$cells);
			$total = max(count($lines),count($segments));

			for ($i = 0; $i < $total; $i++) {

				if (!isset($segments[$i])) continue;

				if (isset($lines[$i]) && !empty($lines[$i])) {
					$eol = strlen($lines[$i])-1;

					if (!empty($segments[$i]) &&  $lines[$i]{$eol} == $segments[$i]{0}) $lines[$i] .= substr($segments[$i],1);
					else $lines[$i] .= $segments[$i];

				} else {
					if (!isset($lines[$i])) $lines[$i] = '';
					$lines[$i] .= $segments[$i];
				}
			}

		}
		$_[] = join("\n",$lines);
		return join('',$_);
	}

	function append ($content,$block=true) {
		$this->content[] = $content;
	}

	function format ($text) { /* disabled */ }

	function addcolumn ($column = 0) {
		$id = $this->table->addrowcolumn($this->row);
		$this->cols++;
		return $id;
	}

	function tablerow () {
		return $this->row;
	}

	function padding () { /* Disabled */ }

}

class TextifyTd extends TextifyTableTag {

	var $row = false;
	var $col = 0;

	protected $padding = array('top' => 0,'right' => 1,'bottom' => 0,'left' => 1);

	private $reported = false;

	function __construct ($tag) {
		parent::__construct($tag);

		$row = $this->getrow();
		$this->row = $row->tablerow();
		$this->col = $row->addcolumn();
	}

	function margins () { /* disabled */ }

	function dimensions () {
		parent::dimensions();
		if ($this->reported) return;
		$this->table->colwidth($this->col,$this->width['max']);
		$this->reported = true;
	}

	function width () {
		return $this->table->colwidth($this->col);
	}

	function getrow () {
		return $this->node->parentNode->Renderer;
	}

}

class TextifyTh extends TextifyTd {

	function before () { return '['; }
	function after () { return ']'; }

}

class TextifyFieldset extends TextifyBlockElement {

}

class TextifyLegend extends TextifyBlockElement {

	function format ($text) {
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
 *
 * @author Pelago
 * @copyright Copyright (c) 2008-2011 Pelago (http://www.pelagodesign.com/)
 * @see license.txt - THE EMOGRIFIER LICENSE
 * @since 1.2
 * @package email
 **/
class Emogrifier {

	const CACHE_CSS = 0;
	const CACHE_SELECTOR = 1;
	const CACHE_XPATH = 2;

    private $html = '';
    private $css = '';
    private $unprocessableHTMLTags = array('wbr');
    private $caches = array();

    // this attribute applies to the case where you want to preserve your original text encoding.
    // by default, emogrifier translates your text into HTML entities for two reasons:
    // 1. because of client incompatibilities, it is better practice to send out HTML entities rather than unicode over email
    // 2. it translates any illegal XML characters that DOMDocument cannot work with
    // if you would like to preserve your original encoding, set this attribute to true.
    public $preserveEncoding = false;

    public function __construct($html = '', $css = '') {
        $this->html = $html;
        $this->css  = $css;
        $this->clearCache();
    }

    public function setHTML($html = '') { $this->html = $html; }
    public function setCSS($css = '') {
        $this->css = $css;
        $this->clearCache(self::CACHE_CSS);
    }

    public function clearCache($key = null) {
        if (!is_null($key)) {
            if (isset($this->caches[$key])) $this->caches[$key] = array();
        } else {
            $this->caches = array(
                self::CACHE_CSS       => array(),
                self::CACHE_SELECTOR  => array(),
                self::CACHE_XPATH     => array(),
            );
        }
    }

	// there are some HTML tags that DOMDocument cannot process, and will throw an error if it encounters them.
    // in particular, DOMDocument will complain if you try to use HTML5 tags in an XHTML document.
	// these functions allow you to add/remove them if necessary.
	// it only strips them from the code (does not remove actual nodes).
    public function addUnprocessableHTMLTag($tag) { $this->unprocessableHTMLTags[] = $tag; }
    public function removeUnprocessableHTMLTag($tag) {
        if (($key = array_search($tag,$this->unprocessableHTMLTags)) !== false)
            unset($this->unprocessableHTMLTags[$key]);
    }

    // applies the CSS you submit to the html you submit. places the css inline
	public function emogrify() {
	    $body = $this->html;
	    // process the CSS here, turning the CSS style blocks into inline css
	    if (count($this->unprocessableHTMLTags)) {
            $unprocessableHTMLTags = implode('|',$this->unprocessableHTMLTags);
            $body = preg_replace("/<($unprocessableHTMLTags)[^>]*>/i",'',$body);
	    }

		if (function_exists('mb_detect_encoding'))
			$encoding = mb_detect_encoding($body);
		else $encoding = 'UTF-8';

		if (function_exists('mb_convert_encoding'))
			$body = mb_convert_encoding($body, 'HTML-ENTITIES', $encoding);

        $xmldoc = new DOMDocument;
		$xmldoc->encoding = 'UTF-8';
		$xmldoc->strictErrorChecking = false;
		$xmldoc->formatOutput = true;
        $xmldoc->loadHTML($body);
		$xmldoc->normalizeDocument();

		$xpath = new DOMXPath($xmldoc);

        // before be begin processing the CSS file, parse the document and normalize all existing CSS attributes (changes 'DISPLAY: none' to 'display: none');
        // we wouldn't have to do this if DOMXPath supported XPath 2.0.
        $nodes = @$xpath->query('//*[@style]');
        if ($nodes->length > 0) foreach ($nodes as $node) $node->setAttribute('style',preg_replace('/[A-z\-]+(?=\:)/Se',"strtolower('\\0')",$node->getAttribute('style')));

        // filter the CSS
        $search = array(
            '/\/\*.*\*\//sU', // get rid of css comment code
            '/^\s*@import\s[^;]+;/misU', // strip out any import directives
            '/^\s*@media\s[^{]+{\s*}/misU', // strip any empty media enclosures
            '/^\s*@media\s+((aural|braille|embossed|handheld|print|projection|speech|tty|tv)\s*,*\s*)+{.*}\s*}/misU', // strip out all media types that are not 'screen' or 'all' (these don't apply to email)
            '/^\s*@media\s[^{]+{(.*})\s*}/misU', // get rid of remaining media type enclosures
        );

        $replace = array(
            '',
            '',
            '',
            '',
            '\\1',
        );

		$css = preg_replace($search, $replace, $this->css);

        $csskey = md5($css);
        if (!isset($this->caches[self::CACHE_CSS][$csskey])) {

            // process the CSS file for selectors and definitions
            preg_match_all('/(^|[^{}])\s*([^{]+){([^}]*)}/mis', $css, $matches, PREG_SET_ORDER);

            $all_selectors = array();
            foreach ($matches as $key => $selectorString) {
                // if there is a blank definition, skip
                if (!strlen(trim($selectorString[3]))) continue;

                // else split by commas and duplicate attributes so we can sort by selector precedence
                $selectors = explode(',',$selectorString[2]);
                foreach ($selectors as $selector) {
                    // don't process pseudo-classes
                    if (strpos($selector,':') !== false) continue;
                    $all_selectors[] = array('selector' => trim($selector),
                                             'attributes' => trim($selectorString[3]),
                                             'index' => $key, // keep track of where it appears in the file, since order is important
                    );
                }
            }

            // now sort the selectors by precedence
            usort($all_selectors, array($this,'sortBySelectorPrecedence'));

            $this->caches[self::CACHE_CSS][$csskey] = $all_selectors;
        }

        foreach ($this->caches[self::CACHE_CSS][$csskey] as $value) {

            // query the body for the xpath selector
            $nodes = $xpath->query($this->translateCSStoXpath(trim($value['selector'])));

            foreach($nodes as $node) {
                // if it has a style attribute, get it, process it, and append (overwrite) new stuff
                if ($node->hasAttribute('style')) {
                    // break it up into an associative array
                    $oldStyleArr = $this->cssStyleDefinitionToArray($node->getAttribute('style'));
                    $newStyleArr = $this->cssStyleDefinitionToArray($value['attributes']);

                    // new styles overwrite the old styles (not technically accurate, but close enough)
                    $combinedArr = array_merge($oldStyleArr,$newStyleArr);
                    $style = '';
                    foreach ($combinedArr as $k => $v) $style .= (strtolower($k) . ':' . $v . ';');
                } else {
                    // otherwise create a new style
                    $style = trim($value['attributes']);
                }
                $node->setAttribute('style',$style);
            }
        }

		// This removes styles from your email that contain display:none. You could comment these out if you want.
        $nodes = $xpath->query('//*[contains(translate(@style," ",""),"display:none")]');
        // the checks on parentNode and is_callable below are there to ensure that if we've deleted the parent node,
        // we don't try to call removeChild on a nonexistent child node
        if ($nodes->length > 0) foreach ($nodes as $node) if ($node->parentNode && is_callable(array($node->parentNode,'removeChild'))) $node->parentNode->removeChild($node);

        if ($this->preserveEncoding && function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($xmldoc->saveHTML(), $encoding, 'HTML-ENTITIES');
        } else {
            return $xmldoc->saveHTML();
        }
	}

    private function sortBySelectorPrecedence($a, $b) {
        $precedenceA = $this->getCSSSelectorPrecedence($a['selector']);
        $precedenceB = $this->getCSSSelectorPrecedence($b['selector']);

        // we want these sorted ascendingly so selectors with lesser precedence get processed first and
        // selectors with greater precedence get sorted last
        return ($precedenceA == $precedenceB) ? ($a['index'] < $b['index'] ? -1 : 1) : ($precedenceA < $precedenceB ? -1 : 1);
    }

    private function getCSSSelectorPrecedence($selector) {
        $selectorkey = md5($selector);
        if (!isset($this->caches[self::CACHE_SELECTOR][$selectorkey])) {
            $precedence = 0;
            $value = 100;
            $search = array('\#','\.',''); // ids: worth 100, classes: worth 10, elements: worth 1

            foreach ($search as $s) {
                if (trim($selector == '')) break;
                $num = 0;
                $selector = preg_replace('/'.$s.'\w+/','',$selector,-1,$num);
                $precedence += ($value * $num);
                $value /= 10;
            }
            $this->caches[self::CACHE_SELECTOR][$selectorkey] = $precedence;
        }

        return $this->caches[self::CACHE_SELECTOR][$selectorkey];
    }

	// right now we support all CSS 1 selectors and /some/ CSS2/3 selectors.
	// http://plasmasturm.org/log/444/
	private function translateCSStoXpath($css_selector) {

        $css_selector = trim($css_selector);
        $xpathkey = md5($css_selector);
        if (!isset($this->caches[self::CACHE_XPATH][$xpathkey])) {
            // returns an Xpath selector
            $search = array(
                               '/\s+>\s+/', // Matches any element that is a child of parent.
                               '/\s+\+\s+/', // Matches any element that is an adjacent sibling.
                               '/\s+/', // Matches any element that is a descendant of an parent element element.
                               '/(\w)\[(\w+)\]/', // Matches element with attribute
                               '/(\w)\[(\w+)\=[\'"]?(\w+)[\'"]?\]/', // Matches element with EXACT attribute
                               '/(\w+)?\#([\w\-]+)/e', // Matches id attributes
                               '/(\w+|[\*\]])?((\.[\w\-]+)+)/e', // Matches class attributes
            );
            $replace = array(
                               '/',
                               '/following-sibling::*[1]/self::',
                               '//',
                               '\\1[@\\2]',
                               '\\1[@\\2="\\3"]',
                               "(strlen('\\1') ? '\\1' : '*').'[@id=\"\\2\"]'",
                               "(strlen('\\1') ? '\\1' : '*').'[contains(concat(\" \",@class,\" \"),concat(\" \",\"'.implode('\",\" \"))][contains(concat(\" \",@class,\" \"),concat(\" \",\"',explode('.',substr('\\2',1))).'\",\" \"))]'",
            );
            $this->caches[self::CACHE_SELECTOR][$xpathkey] = '//'.preg_replace($search,$replace,$css_selector);
        }
        return $this->caches[self::CACHE_SELECTOR][$xpathkey];
	}

	private function cssStyleDefinitionToArray($style) {
	    $definitions = explode(';',$style);
	    $retArr = array();
	    foreach ($definitions as $def) {
            if (empty($def) || strpos($def, ':') === false) continue;
    	    list($key,$value) = explode(':',$def,2);
    	    if (empty($key) || strlen(trim($value)) === 0) continue;
    	    $retArr[trim($key)] = trim($value);
	    }
	    return $retArr;
	}
} // END class Emogrifier

?>
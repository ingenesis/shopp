<?php
/**
 * Markdown.php
 *
 * An Markdown parser
 *
 * @author Jonathan Davis, Maxim S. Tsepkov
 * @version 1.0
 * @copyright Maxim S. Tsepkov, 2011
 * @copyright Ingenesis Limited, May 2013
 * @license (@see license.txt)
 * @package shopp
 * @since 1.3
 * @subpackage markdownr
 *
 * Copyright (C) 2011, Maxim S. Tsepkov
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class MarkdownText extends ArrayObject {
    /**
     * Flag indicating that object has been passed through filters.
     *
     * @var bool
     */
    protected $_isFiltered = false;

    protected static $_defaultFilters = null;

    protected static $_factoryDefaultFilters = array(
        'Hr',
        'ListsBulleted',
        'ListsNumbered',
        'Blockquote',
        'Code',
        'Entities',
        'HeaderAtx',
        'HeaderSetext',
        'Img',
        'Linebreak',
        'Link',
        'Emphasis',
        'Paragraph',
        'Unescape'
    );

    /**
     * Array of custom filters.
     * Default filters are used if not set.
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * Constructor.
     *
     * @param mixed $markdown  String, array or stringable object.
     * @param array $filters   Optional filters instead of defaults.
     * @throws \InvalidArgumentException
     */
    public function __construct($markdown = array(), array $filters = null) {
        // break string by newlines, platform-independent
        if (is_string($markdown) || method_exists($markdown, '__toString')) {
            $markdown = explode("\n", (string) $markdown);
            $markdown = array_map(
				function ( $markdown ) {
					return trim($markdown, "\r");
				},
                $markdown
            );
        }

        if (is_array($markdown)) {
            foreach ($markdown as $no => $value) {
                if ($value instanceof MarkdownLine) {
                    $this[$no] = $value;
                } else {
                    $this[$no] = new MarkdownLine($value);
                }
            }
        } else {
            throw new InvalidArgumentException(
                'MarkdownText constructor expects array, string or stringable object.'
            );
        }

        if ($filters !== null) {
            $this->setFilters($filters);
        } else {
            $this->setFilters(self::getDefaultFilters());
        }
    }

    public function __toString() {
        return $this->getHtml();
    }

    public function getHtml() {
        if (!$this->_isFiltered) {
            foreach ($this->_filters as $filter) {
                $filter->preFilter($this);
            }

            foreach ($this->_filters as $filter) {
                $filter->filter($this);
            }

            foreach ($this->_filters as $filter) {
                $filter->postFilter($this);
            }

            $this->_isFiltered = true;
        }

        return implode("\n", (array) $this);
    }

    public function getFilters() {
        return $this->_filters;
    }

    public function offsetSet($index, $newval) {
        if ($newval instanceof MarkdownLine) {
            parent::offsetSet($index, $newval);
        } else {
            $newval = (string) $newval;
            if ($index !== null && isset($this[$index])) {
                // keep existing object
                $this[$index]->gist = $newval;
            }  else {
                // add new element
                parent::offsetSet($index, new MarkdownLine($newval));
            }
        }
    }

    /**
     * Define filters for this MarkdownText instance.
     *
     * Each filter may be defined either as a string or as a Filter instance.
     * If filter is a string, corresponding class will be attempted to autoload.
     *
     * Returns filters array with all members instantiated.
     *
     * @param array $filters
     * @throws \InvalidArgumentException
     * @return array
     */
    public function setFilters(array $filters) {
        $this->_filters = array();

        foreach ($filters as $key => $filter) {
            if (is_string($filter) && ctype_alnum($filter)) {
                $classname = 'Markdown' . $filter;
                $filter = new $classname;
            }

            if (!$filter instanceof MarkdownFilter) {
				var_dump($filter);
                throw new InvalidArgumentException(
                    '$filters must be an array which elements ' .
                    'are either an alphanumeric string or a Filter instance'
                );
            }

            $this->_filters[$key] = $filter;
        }

        return $this->_filters;
    }

    public static function getFactoryDefaultFilters() {
        return self::$_factoryDefaultFilters;
    }

    public function insert($offset, $lines) {
        if (!is_array($lines)) {
            $lines = array($lines);
        }

        $result = (array) $this;

        $slice = array_splice($result, $offset);
        $result = array_merge($result, $lines, $slice);

        foreach ($result as $key => $val) {
            if (!$val instanceof MarkdownLine) {
                $val = new MarkdownLine($val);
            }
            $this[$key] = $val;
        }

        return $this;
    }

    /**
     * @return array
     */
    public static function getDefaultFilters() {
        if (!self::$_defaultFilters) {
            self::$_defaultFilters = self::$_factoryDefaultFilters;
        }

        return self::$_defaultFilters;
    }

    /**
     * @param array $filters
     * @return Filter
     */
    public static function setDefaultFilters(array $filters) {
        self::$_defaultFilters = $filters;
    }
}

abstract class MarkdownFilter {
    /**
     * Empty constructor is used to avoid a bug in PHP 5.3.2
     * GitHub Issue: https://github.com/garygolden/markdown-oo-php/issues/20
     */
    public function __construct() {}

    /**
     * List of characters which copies as is after \ char.
     *
     * @var array
     */
    protected static $_escapableChars = array(
        '\\', '`', '*', '_', '{', '}', '[', ']',
        '(' , ')', '#', '+', '-', '.', '!'
    );

    /**
     * Block-level HTML tags.
     *
     * @var array
     */
    protected static $_blockTags = array(
        'p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre',
        'table', 'dl', 'ol', 'ul', 'script', 'noscript', 'form', 'fieldset',
        'iframe', 'math', 'ins', 'del', 'article', 'aside', 'header', 'hgroup',
        'footer', 'nav', 'section', 'figure', 'figcaption'
    );

    abstract public function filter(MarkdownText $text);
    public function preFilter(MarkdownText $text) {}
    public function postFilter(MarkdownText $text) {}
}

class MarkdownLine implements ArrayAccess {
    const NONE        = 0;
    const NOMARKDOWN  = 1;
    const BLOCKQUOTE  = 2;
    const CODEBLOCK   = 4;
    const HEADER      = 8;
    const HR          = 16;
    const IMG         = 32;
    const LINEBREAK   = 64;
    const LINK        = 128;
    const LISTS       = 256;
    const PARAGRAPH   = 512;

    public $gist  = '';
    public $flags = self::NONE;

    /**
     * Constructor.
     *
     * @param string|object $gist
     * @throws \InvalidArgumentException
     */
    public function __construct($gist = null) {
        if ($gist !== null) {
            if (is_string($gist) || method_exists($gist, '__toString')) {
                $this->gist = (string) $gist;
            } else {
                throw new InvalidArgumentException(
                    'MarkdownLine constructor expects string or a stringable object.'
                );
            }
        }
    }

    public function __toString() {
        return $this->gist;
    }

    public function append($gist) {
        $this->gist .= $gist;
        return $this;
    }

    public function prepend($gist) {
        $this->gist = $gist . $this->gist;
        return $this;
    }

    public function wrap($tag) {
        $this->gist = "<$tag>" . $this->gist . "</$tag>";

        return $this;
    }

    public function outdent() {
        $this->gist = preg_replace('/^(\t| {1,4})/uS', '', $this->gist);
        return $this;
    }

    public function isBlank() {
        return empty($this->gist) || preg_match('/^\s*$/u', $this->gist);
    }

    public function isIndented() {
        if (isset($this->gist[0]) && $this->gist[0] == "\t") {
            return true;
        }
        if (substr($this->gist, 0, 4) == '    ') {
            return true;
        } else {
            return false;
        }
    }

    public function offsetExists($offset) {
        return isset($this->gist[$offset]);
    }

    public function offsetGet($offset) {
        return $this->gist[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->gist[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->gist[$offset]);
    }
}

class MarkdownStack extends SplStack {

    const PARAGRAPH  = 1;
    const BLOCKQUOTE = 2;
    const CODEBLOCK  = 4;

    protected $_paragraphs = false;

    public function apply(MarkdownText $text, $tag) {
        $listOpened = false;
        $itemOpened = false;

        while (!$this->isEmpty()) {
            $item = $this->shift();

            // process paragraphs
            if ($this->_paragraphs) {
                $item[ key($item) ] = '<p>' . current($item);
                end($item);
                $item[ key($item) ] = current($item) . '</p>';
                reset($item);
            }

            // process <li>
            $item[ key($item) ] = '<li>' . current($item);
            end($item);
            $item[ key($item) ] = current($item) . '</li>';
            reset($item);

            // process <ul>/<ol>
            if (!$listOpened) {
                $item[ key($item) ] = "<$tag>" . current($item);
                $listOpened = true;
            }

            foreach ($item as $no => $line) {
                $line = new MarkdownLine($line);
                $line->flags |= MarkdownLine::LISTS;
                $text[$no] = $line;
            }
        }
        $text[$no]->gist = $text[$no] . "</$tag>";

        $this->reset();

        return $text;
    }

    public function addItem(array $lines) {
        $this->push($lines);

        return $this;
    }

    public function appendMarkdownLine(array $line, $flags = 0) {
        $item = $this->pop();
        $item += $line;
        $this->push($item);

        return $this;
    }

    public function paragraphize($bool = true) {
        $this->_paragraphs = (bool) $bool;

        return $this;
    }

    public function reset() {
        while (!$this->isEmpty()) {
            $this->pop();
        }

        $this->_paragraphs = false;

        return $this;
    }

    public function toArray() {
        $result = array();

        foreach($this as $key => $val) {
            $result[$key] = $val;
        }

        return $result;
    }
}

/**
 * Translate email-style blockquotes.
 *
 * Definitions:
 * <ul>
 *   <li>blockquote is indicated by < at the start of line</li>
 *   <li>blockquotes can be nested</li>
 *   <li>lazy blockquotes are allowed</li>
 *   <li>Blockquote ends with \n\n</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownBlockquote extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        $stack = null;

        foreach($text as $no => $line) {

            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            if (!$stack) {
                if (isset($line->gist[0]) && $line->gist[0] == '>') {
                    $stack = new MarkdownText();
                }
            }

            if($stack) {
                $line->flags |= MarkdownLine::BLOCKQUOTE;
                $line->gist   = preg_replace('/^> ?/u', '', $line->gist);
                $stack[$no]   = $line;

                if (!isset($nextline) || $nextline->isBlank()) {
                    $stack = $this->filter($stack);
                    $stack[ key($stack) ]->prepend('<blockquote>');
                    end($stack);
                    $stack[ key($stack) ]->append('</blockquote>');
                    $stack = null;
                }
            }
        }

        return $text;
    }
}

/**
 * Translate code blocks and spans.
 *
 * Definitions of code block:
 * <ul>
 *   <li>code block is indicated by indent at least 4 spaces or 1 tab</li>
 *   <li>one level of indentation is removed from each line of the code block</li>
 *   <li>code block continues until it reaches a line that is not indented</li>
 *   <li>within a code block, ampersands (&) and angle brackets (< and >)
 *      are automatically converted into HTML entities</li>
 * </ul>
 *
 * Definitions of code span:
 * <ul>
 *   <li>span of code is indicated by backtick quotes (`)</li>
 *   <li>to include one or more backticks the delimiters must
 *     contain multiple backticks</li>
 * </ul>
 *
 * @todo Require codeblock to be surrounded by blank lines.
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownCode extends MarkdownFilter {
    /**
     * Flags lines containing codeblocks.
     * Other filters must avoid parsing markdown on that lines.
     *
     * @see \Markdown\MarkdownFilter::preMarkdownFilter()
     */
    public function preMarkdownFilter(MarkdownText $text) {
        foreach($text as $no => $line) {
            if ($line->isIndented()) {
                $line->flags |= MarkdownLine::NOMARKDOWN + MarkdownLine::CODEBLOCK;
            } elseif ($line->isBlank()) {
                $prev_no = $no;
                do {
                    $prev_no -= 1;
                    $prevline = isset($text[$prev_no]) ? $text[$prev_no] : null;
                } while ($prevline !== null && $prevline->isBlank());

                $next_no = $no;
                do {
                    $next_no += 1;
                    $nextline = isset($text[$next_no]) ? $text[$next_no] : null;
                } while ($nextline !== null && $nextline->isBlank());

                if ($prevline !== null && $prevline->isIndented() && $nextline !== null && $nextline->isIndented()) {
                    $line->flags |= MarkdownLine::NOMARKDOWN + MarkdownLine::CODEBLOCK;
                }
            }

        }
    }

    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        $insideCodeBlock = false;

        foreach ($text as $no => $line) {
            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            if ($line->flags & MarkdownLine::CODEBLOCK) {
                $line->outdent();
                $line->gist = htmlspecialchars($line, ENT_NOQUOTES);
                if (!$insideCodeBlock) {
                    $line->prepend('<pre><code>');
                    $insideCodeBlock = true;
                }
                if (!$nextline || !($nextline->flags & MarkdownLine::CODEBLOCK)) {
                    $line->append('</code></pre>');
                    $insideCodeBlock = false;
                }
            } else {
                $line->gist = preg_replace_callback(
                    '/(?<!\\\)(`+)(?!`)(?P<code>.+?)(?<!`)\1(?!`)/u',
                    function ( $values ) {
	                    $line = trim($values["code"]);
	                    $line = htmlspecialchars($line, ENT_NOQUOTES);
	                    return "<code>" . $line . "</code>";
                    },
                    $line->gist
                );
            }
        }

        return $text;
    }
}

/**
 * Implements &lt;em&gt; and &lt;strong&gt;
 *
 * Definitions:
 * <ul>
 *   <li>text wrapped with one * or _ will be wrapped with an HTML &lt;em&gt; tag</li>
 *   <li>double *’s or _’s will be wrapped with an HTML &lt;strong&gt; tag</li>
 *   <li>the same character must be used to open and close an emphasis span</li>
 *   <li>emphasis can be used in the middle of a word</li>
 *   <li>if an * or _ is surrounded by spaces,
 *      it’ll be treated as a literal asterisk or an underscore</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownEmphasis extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach ($text as $no => $line) {
            if ($line->flags & MarkdownLine::NOMARKDOWN) {
                continue;
            }

            // avoid parsing markdown within a tag
            $noTags = strip_tags($line->gist);

            // strong
            $matches = array();
            $pattern = '/(?<!\\\\)(\*\*|__)(?=\S)(.+?[*_]*)(?<=\S)(?<!\\\\)\1/u';
            preg_match_all($pattern, $noTags, $matches);
            foreach($matches[0] as $match) {
                $replace = '<strong>' . substr($match, 2);
                $replace = substr($replace, 0, -2) . '</strong>';
                $line->gist = str_replace($match, $replace, $line->gist);
            }

            // emphasis
            $matches = array();
            $pattern = '/(?<!\\\\)\b([*_])(?!\s)(.+?)(?<![\\\\\s])\1\b/u';
            preg_match_all($pattern, $noTags, $matches);
            foreach($matches[0] as $match) {
                $replace = '<em>' . substr($match, 1);
                $replace = substr($replace, 0, -1) . '</em>';
                $line->gist = str_replace($match, $replace, $line->gist);
            }
        }

        return $text;
    }
}

/**
 * Translates & and &lt; to &amp;amp; and &amp;lt;
 *
 * Definitions:
 * <ul>
 *   <li>Transform & to &amp;amp; and < to &amp;lt;</li>
 *   <li>do NOT transform if & is part of html entity, e.g. &amp;copy;</li>
 *   <li>do NOT transform < if it's part of html tag</li>
 *   <li>ALWAYS transfrom & and < within code spans and blocks</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownEntities extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach($text as $no => $line) {
            // escape & outside of html entity
            $line->gist = preg_replace('/&(?!(?:#\d+|#x[a-fA-F0-9]+|\w+);)/uS', '&amp;', $line);

            // escape < outside of html tag
            $line->gist = preg_replace('/<(?![A-z\\/])/uS', '&lt;', $line);
        }
    }
}

/**
 * Translates ### style headers.
 *
 * Definitions:
 * <ul>
 *   <li>use 1-6 hash characters at the start of the line</li>
 *   <li>number of opening hashes determines the header level</li>
 *   <li>closing hashes don’t need to match the number of hashes used to open</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownHeaderAtx extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach($text as $no => $line) {
            if ($line->flags & MarkdownLine::NOMARKDOWN) continue;

            if (preg_match('/^#+\s*\w/uS', $line)) {
                $html = rtrim($line, '#');
                $level = substr_count($html, '#', 0, min(6, strlen($html)));
                $html = "<h$level>" . trim(substr($html, $level)) . "</h$level>";
                $line->gist = $html;
            }
        }
    }
}

/**
 * Translates ==== style headers.
 *
 * Definitions:
 * <ul>
 *   <li>first-level headers are "underlined" using =</li>
 *   <li>second-level headers are "underlined" using -</li>
 *   <li>any number of underlining =’s or -’s will work.</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownHeaderSetext extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach($text as $no => $line) {
            if ($no == 0) continue; // processing 1st line makes no sense
            if ($line->flags & MarkdownLine::NOMARKDOWN) continue;

            $prevline = isset($text[$no - 1]) ? $text[$no - 1] : null;

            if (preg_match('/^=+$/uS', $line) && $prevline !== null && !$prevline->isBlank()) {
                $prevline->wrap('h1');
                $line->gist = '';
            }
            else if (preg_match('/^-+$/uS', $line) && $prevline !== null && !$prevline->isBlank()) {
                $prevline->wrap('h2');
                $line->gist = '';
            }
        }

        return $text;
    }
}

/**
 * Translates horizontal rules.
 *
 * Definitions:
 * <ul>
 *   <li>horizontal rule produced by placing three or more
 *      hyphens, asterisks, or underscores on a line by themselves</li>
 *   <li>spaces can be used between the hyphens or asterisks</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownHr extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach($text as $no => $line) {
            if ($line->flags & MarkdownLine::NOMARKDOWN) continue;

            $line->gist = preg_replace(
                '/^(?:[*\-_]\s*){2,}$/u',
                '<hr />',
                $line->gist
            );
        }

        return $text;
    }
}

/**
 * Translates images.
 *
 * Definitions:
 * <ul>
 *   <li>image syntax is resemble the syntax for links
 *      but with an exclamation mark (!) before first bracket</li>
 *   <li>brackets contain alt attribute</li>
 *   <li>Markdown has no syntax for specifying the dimensions of an image</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Igor Gaponov <jiminy96@gmail.com>
 * @version 1.0
 */
class MarkdownImg extends MarkdownLink {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        $this->_mark = '!';
        $this->_format = '<img src="%s"%s alt="%s" />';

        return parent::filter($text);
    }
}

/**
 * Translates linebreaks.
 *
 * Definitions:
 * <ul>
 *   <li>linebreak is indicated by two or more spaces and (\n)
 *      at the end of line</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownLinebreak extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach($text as $no => $line) {
            if (substr($line, -2) === '  ') {
                $line->gist = substr($line, 0, -2) . '<br />';
            }
        }

        return $text;
    }
}

/**
 * Translates links.
 *
 * Definitions:
 * <ul>
 *   <li>link text is delimited by [square brackets]</li>
 *   <li>inline-style URL is inside the parentheses with an optional title in quotes</li>
 *   <li>reference-style links use a second set of square brackets with link label</li>
 *   <li>link definitions can be placed anywhere in document</li>
 *   <li>link definition names may consist of letters, numbers, spaces, and punctuation
 *      — but they are not case sensitive</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownLink extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        $links = array();
        foreach($text as $no => $line) {
            if (preg_match('/^ {0,3}[!]?\[([\w ]+)\]:\s+<?(.+?)>?(\s+[\'"(].*?[\'")])?\s*$/uS', $line, $match)) {
                $link =& $links[ strtolower($match[1]) ];
                $link['href']  = $match[2];
                $link['title'] = null;
                if (isset($match[3])) {
                    $link['title'] = trim($match[3], ' \'"()');
                }
                else if (isset($text[$no + 1])) {
                    if (preg_match('/^ {0,3}[!]?[\'"(].*?[\'")]\s*$/uS', $text[$no + 1], $match)) {
                        $link['title'] = trim($match[0], ' \'"()');
                        $text[$no + 1]->gist = '';
                    }
                }
                // erase line
                $line->gist = '';
            }
        }
        unset($link, $match, $no, $line);

        foreach($text as $no => $line) {
            $line->gist = preg_replace_callback(
                '/[!]?\[(.*?)\]\((.*?)(\s+"[\w ]+")?\)/uS',
                function ( $match ) {
	                if (!isset($match[3])) {
		                $match[3] = null;
	                }

	                if (substr($match[0],0,1) == "!") {
		                return MarkdownLink::buildImage($match[1], $match[2], $match[3]);
	                } else {
		                return MarkdownLink::buildHtml($match[1], $match[2], $match[3]);
	                }
                },
                $line->gist
            );

            if (preg_match_all('/\[(.+?)\] ?\[([\w ]*)\]/uS', $line, $matches, PREG_SET_ORDER)) {
                foreach($matches as &$match) {
                    $ref = !empty($match[2]) ? $match[2] : $match[1];
                    $ref = strtolower(trim($ref));
                    if (isset($links[$ref])) {
                        $link =& $links[$ref];
                        $html = MarkdownLink::buildHtml($match[1], $link['href'], $link['title']);
                        $line->gist = str_replace($match[0], $html, $line);
                    }
                }
            }
        }

        return $text;
    }

    public static function buildHtml($content, $href, $title = null) {
        $link = '<a href="' . trim($href) . '"';
        if (!empty($title)) {
            $link .= ' title="' . trim($title, ' "') . '"';
        }
        $link .= '>' . trim($content) . '</a>';

        return $link;
    }

    public static function buildImage($alt = null, $src, $title = null) {
        $link = '<img src="' . trim($src) . '"';
        if (!empty($alt)) {
            $link .= ' alt="' . trim($alt, ' "') . '"';
        }
        if (!empty($title)) {
            $link .= ' title="' . trim($title, ' "') . '"';
        }
        $link .= '>';

        return $link;
    }
}

/**
 * Abstract class for all list's types
 *
 * Definitions:
 * <ul>
 *   <li>list items may consist of multiple paragraphs</li>
 *   <li>each subsequent paragraph in a list item
 *      must be indented by either 4 spaces or one tab</li>
 * </ul>
 *
 * @todo Readahead list lines and pass through blockquote and code filters.
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
abstract class MarkdownLists extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        $stack = new MarkdownStack();

		$class = get_class($this); // @todo remove with PHP 5.3 requirement

        foreach ($text as $no => $line) {
            $prevline = isset($text[$no - 1]) ? $text[$no - 1] : null;
            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            // match list marker, add a new list item
            if (($marker = $this->matchMarker($line)) !== false) {
                if (!$stack->isEmpty() && $prevline !== null && (!isset($nextline) || $nextline->isBlank())) {
                    $stack->paragraphize();
                }

                $stack->addItem(array($no => substr($line, strlen($marker))));

                continue;
            }

            // we are inside a list
            if (!$stack->isEmpty()) {
                // a blank line
                if ($line->isBlank()) {
                    // two blank lines in a row
                    if ($prevline !== null && $prevline->isBlank()) {
                        // end of list
                        $stack->apply($text, self::_static($class, 'TAG'));
                    }
                } else { // not blank line
                    if ($line->isIndented()) {
                        // blockquote
                        if (substr(ltrim($line), 0, 1) == '>') {
                            $line->gist = substr(ltrim($line), 1);
                            if (substr(ltrim($prevline), 0, 1) != '>') {
                                $line->prepend('<blockquote>');
                            }
                            if (substr(ltrim($nextline), 0, 1) != '>') {
                                $line->append('</blockquote>');
                            }
                        // codeblock
                        } else if (substr($line, 0, 2) == "\t\t" || substr($line, 0, 8) == '        ') {
                            $line->gist = ltrim(htmlspecialchars($line, ENT_NOQUOTES));
                            if (!(substr($prevline, 0, 2) == "\t\t" || substr($prevline, 0, 8) == '        ')) {
                                $line->prepend('<pre><code>');
                            }
                            if (!(substr($nextline, 0, 2) == "\t\t" || substr($nextline, 0, 8) == '        ')) {
                                $line->append('</code></pre>');
                            }
                        } elseif (!isset($prevline) || $prevline->isBlank()) {
                            // new paragraph inside a list item
                            $line->gist = '</p><p>' . ltrim($line);
                        } else {
                            $line->gist = ltrim($line);
                        }
                    } elseif (!isset($prevline) || $prevline->isBlank()) {
                        // end of list
                        $stack->apply($text, self::_static($class, 'TAG'));
                        continue;
                    } else { // unbroken text inside a list item
                        // add text to current list item
                        $line->gist = ltrim($line);
                    }

                    $stack->appendMarkdownLine(array($no => $line));
                }
            }
        }

        // if there is still stack, flush it
        if (!$stack->isEmpty()) {
            $stack->apply($text, self::_static($class, 'TAG'));
        }

        return $text;
    }

	protected static function _static ( $class, $constant ) {
		if ( ! class_exists($class, false) ) return '';

		$R = new ReflectionClass($class);
		$constants = $R->getConstants();

		if ( isset($constants[ $constant ]) ) return $constants[ $constant ];
		return '';
	}

    abstract protected function matchMarker($line);
}

/**
 * Translates bulleted lists.
 *
 * Definitions:
 * <ul>
 *   <li>asterisks, pluses, and hyphens — interchangably — as list markers</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownListsBulleted extends MarkdownLists {
    const TAG = 'ul';

    protected function matchMarker($line) {
        if (preg_match('/^ {0,3}[*+-]\s+/uS', $line, $matches)) {
            return $matches[0];
        } else {
            return false;
        }
    }
}

/**
 * Translates numbered lists.
 *
 * Definitions:
 * <ul>
 *   <li>ordered lists use numbers followed by periods</li>
 *   <li>actual numbers in the list have no effect on the HTML output</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownListsNumbered extends MarkdownLists {
    const TAG = 'ol';

    protected function matchMarker($line){
        if (preg_match('/^ {0,3}\d+\.\s+/uS', $line, $matches)) {
            return $matches[0];
        } else {
            return false;
        }
    }
}

/**
 * Translates paragraphs.
 *
 * Definitions:
 * <ul>
 *   <li>paragraph is simply one or more consecutive lines of text,
 *      separated by one or more blank lines</li>
 *   <li>normal paragraphs should not be indented</li>
 *   <li>block level inline html must be separated with blank lines
 *      and start and end tags should not be indented</li>
 * </ul>
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownParagraph extends MarkdownFilter {
    /**
     * Flag block-level HTML with NOMARKDOWN.
     *
     * @see MarkdownFilter::preMarkdownFilter()
     */
    public function preMarkdownFilter(MarkdownText $text) {
        $ex = sprintf('/^<(%s)/iuS', implode('|', self::$_blockTags));

        $inHtml = false;
        foreach($text as $no => $line) {
            $prevline = isset($text[$no - 1]) ? $text[$no - 1] : null;
            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            if (!$inHtml) {
                if (!isset($prevline) || $prevline->isBlank()) {
                    if (preg_match($ex, $line, $matches)) {
                        $inHtml = $matches[1];
                    }
                }
            }

            if ($inHtml) {
                $line->flags |= MarkdownLine::NOMARKDOWN;
                if (!isset($nextline) || $nextline->isBlank()) {
                    $inHtml = false;
                }
            }
        }
    }

    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        // FIXME
        // code below flags HTML blocks again
        $ex = sprintf('/^<(%s)/iuS', implode('|', self::$_blockTags));

        $inHtml = false;
        foreach($text as $no => $line) {
            $prevline = isset($text[$no - 1]) ? $text[$no - 1] : null;
            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            if (!$inHtml) {
                if (!isset($prevline) || $prevline->isBlank()) {
                    if (preg_match($ex, $line, $matches)) {
                        $inHtml = $matches[1];
                    }
                }
            }

            if ($inHtml) {
                $line->flags |= MarkdownLine::NOMARKDOWN;
                if (!isset($nextline) || $nextline->isBlank()) {
                    $inHtml = false;
                }
            }
        }


        $inParagraph = false;

        foreach($text as $no => $line) {
            if ($line->flags & MarkdownLine::NOMARKDOWN + MarkdownLine::LISTS) continue;
            if ($line->isBlank()) continue;

            $prevline = isset($text[$no - 1]) ? $text[$no - 1] : null;
            $nextline = isset($text[$no + 1]) ? $text[$no + 1] : null;

            if (!$inParagraph && (!isset($prevline) || $prevline->isBlank())) {
                $line->gist = '<p>' . $line;
                $inParagraph = true;
            }
            if ($inParagraph && (!isset($nextline) || $nextline->isBlank())) {
                $line->gist = $line . '</p>';
                $inParagraph = false;
            }
        }
    }
}

/**
 * Removes backslashes (\) before special symbols.
 *
 * This filter should be run latest,
 * to let other filters be aware of backslashes.
 *
 * @package Markdown
 * @subpackage MarkdownFilter
 * @author Max Tsepkov <max@garygolden.me>
 * @version 1.0
 */
class MarkdownUnescape extends MarkdownFilter {
    /**
     * Pass given text through the filter and return result.
     *
     * @see MarkdownFilter::filter()
     * @param string $text
     * @return string $text
     */
    public function filter(MarkdownText $text) {
        foreach($text as $no => $line) {
            $line->gist = preg_replace(
                '/\\\\([' . preg_quote(implode('', self::$_escapableChars), '/') . '])/uS',
                '$1',
                $line
            );
        }

        return $text;
    }
}
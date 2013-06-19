<?php
/**
 * Generates a searchable document object model from valid XML
 *
 * Usage: $XML = new xmlQuery($source);
 *
 * $source can be another xmlQuery DOM object or a string of XML markup
 *
 * The xmlQuery object uses several helper methods to find data in the
 * parsed document object model (DOM).  Each method takes a selector argument
 * that can be used to filter the returned results.  {@see xmlQuery::parsequery()}
 *
 * The helper methods will contextually return different result structures based
 * on the query and the structure of the target DOM.  The primary search methods
 * include:
 *  $xmlQuery->tag() to find and filter the DOM to a specific set of tags
 * 	$xmlQuery->content() to return the content in a tag (or tags)
 * 	$xmlQuery->attr() to return a specific attribute (or all attributes) from a tag (or tags)
 *
 * The $xmlQuery->each() method can be used to iterate through the DOM nodes that
 * match the provided selector argument: while($xmlQuery->each()) { … }
 *
 * @author Jonathan Davis, leoSr
 * @since 1.1
 * @package shopp
 * @subpackage XML
 * @copyright Ingenesis Limited, May 2010
 **/
class xmlQuery {

	var $dom = array();
	var $_loop = false;

	function __construct ($data=false) {
		if (!is_array($data)) $this->parse($data);
		else $this->dom =& $data;
		return true;
	}

	/**
	 * Parses a string of XML-markup into a structured document object model
	 *
	 * $DOM['_a'] Attributes
	 * $DOM['_c'] Child nodes
	 * $DOM['_v'] Content value
	 * $DOM['_p'] Recursive entries
	 *
	 * XML markup parsing and resulting DOM structure and insert functions by leoSr:
	 * http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
	 *
	 * @author Jonathan Davis, leoSr
	 * @since 1.1
	 *
	 * @param string $markup String of XML markup
	 * @return boolean
	 **/
	function parse (&$markup) {
		$markup = $this->clean($markup,true);
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parse_into_struct($parser, $markup, $vals, $index);
		xml_parser_free($parser);

		$data = array();
		$working = &$data;
		foreach ($vals as $r) {
			$t = $r['tag'];
			switch ($r['type']) {
				case 'open':
					if ( isset( $working[$t] ) ) {
						if ( isset( $working[$t][0] ) ) $working[$t][] = array();
						else $working[$t] = array( $working[$t], array() );
						$cv = &$working[$t][count( $working[$t] )-1];
					} else $cv = &$working[$t];
					if ( isset( $r['attributes'] ) ) { foreach ( $r['attributes'] as $k => $v ) $cv['_a'][$k] = $this->clean($v); }
					$cv['_c'] = array();
					$cv['_c']['_p'] = &$working;
					$working = &$cv['_c'];
					break;
				case 'complete':
					if ( isset( $working[$t] ) ) { // same as open
						if ( isset( $working[$t][0] ) ) $working[$t][] = array();
						else $working[$t] = array( $working[$t], array() );
						$cv = &$working[$t][count( $working[$t] )-1];
					} else $cv = &$working[$t];
					if ( isset( $r['attributes'] ) ) { foreach ($r['attributes'] as $k => $v) $cv['_a'][$k] = $this->clean($v); }
					$cv['_v'] = isset( $r['value'] ) ? $this->clean($r['value']) : '';
					break;
				case 'close':
					$working = &$working['_p'];
					break;
			}
		}

		$this->remove_p($data);
		$this->dom = $data;
		return true;
	}


	/**
	 * Encode and decode characters mis-handled by the XML parser
	 *
	 * @author Jonathan Davis
	 * @since 1.1.6
	 *
	 * @param string $markup The markup to encode/decode
	 * @param boolean $encode True to encode the markup, omit to decode (default)
	 * @return string The encoded/decoded markup
	 **/
	function clean (&$markup,$encode=false) {
		if (!is_string($markup)) return $markup;
		$entities = array('&' => '__amp__','<br>' => '__br__');

		if ($encode) {
			$markup = html_entity_decode($markup);
			$markup = str_replace(array_keys($entities),array_values($entities),$markup);
			return $markup;
		}

		$markup = str_replace(array_values($entities),array_keys($entities),$markup);
		return $markup;
	}

	/**
	 * Removes recursive results in the tree
	 *
	 * @author Jonathan Davis, leoSr
	 * @since 1.1
	 *
	 * @param array $data A branch of data in the tree
	 * @return void
	 **/
	private function remove_p (&$data) {
		foreach ($data as $k => $v) {
			if ($k === '_p') unset($data[$k]);
			elseif (is_array($data[$k])) $this->remove_p($data[$k]);
		}
	}

	/**
	 * Uses recursion to generate XML-markup from the DOM
	 *
	 * @author Jonathan Davis, leoSr
	 * @since 1.1
	 *
	 * @return string XML markup
	 **/
	function markup ($data=false, $depth=0, $tag='', $selfclosing = array('area','base','basefont','br','hr','input','img','link','meta'), $xhtml = true) {
		if (!$data) $data = $this->dom;
		$_=array();
		foreach ($data as $element=>$r) {
			if (isset($r[0])) {
				$_[]=$this->markup($r, $depth, $element, $selfclosing);
			} else {
				if ($tag) $element=$tag;
				$sp=str_repeat("\t", $depth);
				$_[] = "$sp<$element";
				if (isset($r['_a'])) { foreach ($r['_a'] as $at => $av) $_[] = ' '.$at.'="'.($av).'"'; }
				if (in_array($element,$selfclosing)) { $_[] = ($xhtml)?" />\n":">\n"; continue; }
				$_[] = ">".((isset($r['_c'])) ? "\n" : '');
				if (isset($r['_c'])) $_[] = $this->markup($r['_c'], $depth+1,'',$selfclosing);
				elseif (isset($r['_v'])) $_[] = ($r['_v']);
				$_[] = (isset($r['_c']) ? $sp : '')."</$element>\n";
			}

		}
		return implode('', $_);
	}

	/**
	 * Adds a new element to the data tree as a child of the $target element
	 *
	 * @author Jonathan Davis, leoSr
	 * @since 1.1
	 *
	 * @param mixed $target The target element to attach the new element to
	 * @param array $element A structured element created with xmlQuery::element()
	 * @return boolean
	 **/
	function &add ($target,$element) {
		$true = true;
		$working = $element;
		$element = key($working);
		if ($target !== false) {
			if (is_array($target)) $node = &$target;
			else $found =& $this->search($target);
			if (empty($found)) return false;

			$node =& $found[0];
			if (!isset($node['_c'])) $node['_c'][$element] =& $working[$element];
			elseif (isset($node['_c'][$element])) {
				if (!isset($node['_c'][$element][0])) {
					$_ = $node['_c'][$element];
					$node['_c'][$element] = array($_);
				}
				$node['_c'][$element][] =& $working[$element];

			} else $node['_c'][$element] =& $working[$element];
			return $true;

		} else $this->dom[$element] =& $working[$element];
		return $true;
	}

	/**
	 * Creates a structured element for addition to the DOM
	 *
	 * When creating children to attach with an element, use
	 * this method to create the child elements and pass them in
	 * with the $children parameter.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $name The tag name of the new element
	 * @param array $attrs (optional) An associative array of attribute name/value pairs
	 * @param string $content (optional) String contents of the element
	 * @param array $children (optional) Child xmlQuery::element generated elements
	 * @return array The structured element
	 **/
	function &element ($name,$attrs=array(),$content=false,$children=array()) {
		$_ = array();
		$_[$name] = array();
		if (!empty($attrs) && is_array($attrs)) $_[$name]['_a'] = $attrs;
		if ($content) $_[$name]['_v'] = $content;
		if (!empty($children))
			foreach ($children as $childname => $child)
				$_[$name]['_c'][$childname] = $child;
		return $_;
	}

	/**
	 * Finds a tag element in the DOM
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param mixed $tag (optional) Tag to find
	 * @return xmlQuery The matching tags
	 **/
	function tag ($tag=false) {
		if (!$tag) return new xmlQuery(reset($this->dom));

		$found = $this->find($tag);
		if (!empty($found)) return new xmlQuery($found);
		return false;
	}

	/**
	 * Gets a specific element from a list of matching elements
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $index (optional) Element number to retreive
	 * @return xmlQuery The specified element
	 **/
	function get ($index=0) {
		if (isset($this->dom[$index]))
			return new xmlQuery($this->dom[$index]);
		return false;
	}

	/**
	 * Get name of the first container node in the DOM
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string|boolean The name of the container node or false if none found
	 **/
	function context () {
		reset($this->dom);
		$context = key($this->dom);
		if (empty($context)) return false;
		else return $context;
	}

	/**
	 * Iterate through each of the results in the current DOM
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean True if a node exists, false if not
	 **/
	function each () {
		if (!$this->_loop) {
			$this->_loop = true;
			return new xmlQuery(array(current($this->dom)));
		}

		$next = next($this->dom);
		if ($next) return new xmlQuery(array($next));

		reset($this->dom);
		$this->_loop = false;

		return false;
	}

	/**
	 * Gets the content (or contents) of a tag
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param mixed $tag (optional) Tag to find
	 * @return string|array A string of a single value or an array of strings for each matching value
	 **/
	function content ($tag=false) {
		if (!$tag) return count($this->dom) == 1 && !empty($this->dom[0]['_v'])?$this->dom[0]['_v']:false;

		$found = $this->find($tag);
		if (isset($found['_v'])) $found = array($found);
		$_ = array();
		foreach ($found as $entry)
			$_[] = $entry['_v'];
		if (count($_) == 1) return $_[0];
		else return $_;

	}

	/**
	 * Gets an attribute (or attributes) of a tag
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $attr (optional) Attribute to retrieve
	 * @param mixed $tag (optional) Tag to find
	 * @return string|array A single attribute value or an array of attribute values
	 **/
	function attr ($tag=false,$attr=false) {
		if (!is_string($attr)) $attr = false;
		if (!$tag) {
			$dom = (count($this->dom) == 1)?$this->dom[0]:$this->dom;
			if (!isset($dom['_a'])) return false;
			if (!$attr) return $dom['_a'];
			return (isset($dom['_a'][$attr]))?$dom['_a'][$attr]:false;
		}

		$found = $this->find($tag);
		if (isset($found['_a'])) $found = array($found);
		$_ = array();
		foreach ($found as $entry) {
			if (!empty($entry['_a'])) {
				if (!$attr) $_[] = $entry['_a'];
				if (isset($entry['_a'][$attr]))
					$_[] = $entry['_a'][$attr];
			}
		}

		if (count($_) == 1) return $_[0];
		else return $_;

		return false;
	}

	/**
	 * Recursively find elements in the DOM matching the query
	 *
	 * Finds elements that match a specifically formatted
	 * query string to select elements {@see xmlQuery::parsequery()}
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $query Search query
	 * @param array $dom (optional) The DOM to search, defaults to the DOM of this instance
	 * @return array DOM array structure
	 **/
	private function find ($query,&$dom=false) {
		if (!$dom) $dom = &$this->dom;
		if (!is_array($dom)) $dom = array($dom);
		if (is_string($query)) $query = $this->parsequery($query);

		$patterns = $this->patterns();
		extract($patterns);

		$results = array();
		// Iterate through the query sets
		foreach ($query as $i => $q) {

			$operator = false;
			$_ = array();

			// Iterate through each target of the query set
			foreach ($q as $target) {

				if (is_string($target) && preg_match("/$delimiters/",$target)) {
					// Target is an operator, skip to next target
					$operator = $target; continue;
				}

				if (is_array($target)) {
					$tag = isset($target[0])?$target[0]:false;
					$subselect = isset($target[1])?$target[1]:false;
					$attributes = isset($target[2])?$target[2]:false;
				} else $tag = $target;

				if ($operator !== false) {
					// Operator detected for this target
					$last = count($_)-1;
					switch ($operator) {
						case " ":	// Branch search
						case ">":	// Child search
							foreach ($_ as $in => $r) {
								$entry = array($in => $r);
								$found = &$this->search($tag,$attributes,$entry);
								if (!empty($found)) $_[$in] = $found[0];
							}
							break;
						case "+":	// Next sibling search
							// Format a recursive query
							$r_query = array($i => array($target));
							$found = &$this->find($r_query,$dom);
							$nexts = array();
							foreach ($found as $n => $f)
								if ($f == $_[$last]) $nexts[] = $found[$n+1];
							array_splice($_,$last,1,$nexts);
						break;
					}
					$operator = false;
					continue;
				}

				// Recursive dom search for the tag and any attributes
				$found = $this->search($tag,$attributes,$dom);
				$_ = array_merge($_,$found);

			}

			if (!empty($subselect)) {
				// Subselect detected
				// Post process this target's search results
				list($selector,$filter) = $subselect;
				switch ($selector) {
					case "first": $_ = $_[0]; break;
					case "last": $_ = $_[count($_)-1]; break;
					case "even": $_ = self::array_key_filter($_,array(&$this,'_filter_even')); break;
					case "odd": $_ = self::array_key_filter($_,array(&$this,'_filter_odd')); break;
					case "eq": $_ = isset($_[$filter])?$_[$filter]:false;
					case "gt": $_ = self::array_key_filter($_,array(&$this,'_filter_gt'),$filter); break;
					case "lt": $_ = self::array_key_filter($_,array(&$this,'_filter_lt'),$filter); break;
				}
			}

			// Save the results from this query target
			// into the total result list
			$results = array_merge($results,$_);
		}

		return $results;
	}

	/**
	 * Recursively searches the DOM for matching tag/attributes
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $tag The tag name to search for
	 * @param array $attributes (optional) The attribute criteria
	 * @param array $dom (optional) The DOM to search
	 * @param boolean $recursive (optional) Turn on/off recursive searching
	 * @return array List of matching elements
	 **/
	private function search ($tag,$attributes=array(),&$dom=false,$recursive=true) {
		if (!$dom) $dom = &$this->dom;
		if (!is_array($dom)) $dom = array($dom);

		$_ = array();
		// Iterate through the elements of the DOM and find matches
		foreach($dom as $key => &$element) {
			$match = false;

			if ($recursive) {
				if (isset($element['_c']) && !empty($element['_c'])) {
					// Search child elements/nodes first
					$found = &$this->search($tag,$attributes,$element['_c']);
					$_ = array_merge($_,$found);
				} elseif (count($element) > 0 && isset($element[0])) {
					// Search a collection of a single tag
					foreach ($element as $b => $branch) {
						$entry = array($b => $branch);
						$found = &$this->search($tag,$attributes,$entry,true);
						$_ = array_merge($_,$found);
					}
				}
			}

			if ($key !== $tag) continue;

			// Matched tag already, if attribute search is set check that those match too
			if (empty($attributes)) $match = true;
			else foreach ($attributes as $attr => $search) // Match attributes
				if (isset($element['_a']) && isset($element['_a'][$search[1]]) && !isset($search[3])
					|| isset($element['_a']) && isset($element['_a'][$search[1]])
					&& $this->match($element['_a'][$search[1]],$search[2],$search[3]))
						$match = true;

			if (!$match) return;

			// Element matched, save it to our results

			// If this is a branch, append the branch entries as individual results
			if (count($element) > 0 && isset($element[0]))
				$_ = array_merge($_,$element);
			else $_[] =& $element;
		}

		return $_;

	}

	/**
	 * Parses XML query selectors
	 *
	 * Examples:
	 * tagname:first[attribute=value],secondtag > childtag
	 *
	 * Match nexttag preceded by previoustag:
	 * previoustag + nexttag
	 *
	 * Attribute Matching
	 * attribute=value (exact match)
	 * attribute!=value (not equal)
	 * attribute*=alu (contains 'alu')
	 * attribute~=value (contains word)
	 * attribute^=val (starts with)
	 * attribute$=lue (ends with)
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $query Formatted query string
	 * @return array Structured query usable by find()
	 **/
	function parsequery ($query) {

		$patterns = $this->patterns();
		extract($patterns);

		$queries = preg_split("/\s*,\s*/",$query,-1);
		foreach ($queries as &$query) {
			$query = preg_split("/\s*($delimiters)\s*/",$query,-1,PREG_SPLIT_DELIM_CAPTURE);

			foreach ($query as $i => &$q) {
				if (!preg_match("/^($tags)(?:$subselects)?((?:$attrs)*)$/",$q,$_)) continue;
				$q = array($_[1]);
				if (!empty($_[2])) {
					$q[1] = array($_[2],$_[3]);
				}
				if (!empty($_[4])) {
					preg_match_all("/$attrs/",$_[4],$a,PREG_SET_ORDER);
					$q[2] = $a;
				}
			}
		}

		return $queries;
	}

	/**
	 * Returns a set of query patterns for centralized reference
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array Defined patterns
	 **/
	private function patterns () {
		$_ = array(
			'tags' => '[\w0-9\.\-_]+',
			'subselects' => '\:(\w+)(?:\((.+?)\))?',
			'ops' => array(
				'contains' => '\*=',
				'containsword' => '~=',
				'startswith' => '\^=',
				'endswith' => '\$=',
				'equal' => '=',
				'notequal' => '!='
			),
			'delimiters' => '[>\+ ]'
		);
		$_['attrs'] = "\[(\w+)(".join('|',$_['ops']).")?(\w+)?\]";

		return $_;
	}

	/**
	 * Compares a source string with a search string using a given operation
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $source The source to compare
	 * @param string $op The operation to use
	 * @param string $search The search string
	 * @return boolean
	 **/
	private function match ($source,$op,$search) {
		switch ($op) {
			case "=": return ($source == $search); break;
			case "!=": return ($source != $search); break;
			case "*=": return (strpos($source,$search) !== false); break;
			case "~=": $words = explode(" ",$source); return (in_array($search,$words)); break;
			case "^=": return (substr_compare($source,$search,0,strlen($search)) == 0); break;
			case "$=": return (substr_compare($source,$search,strlen($search)*-1) == 0); break;
		}
		return false;
	}

	/**
	 * Helper filter to find odd-number index array elements
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $key Value of array index
	 * @return boolean
	 **/
	private function _filter_odd ($key) {
		return ($key & 1);
	}

	/**
	 * Helper filter to find even-number index array elements
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $key Value of array index
	 * @return boolean
	 **/
	private function _filter_even ($key) {
		return (!($key & 1));
	}

	/**
	 * Helper filter to find array indexes greater than a specified amount
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $key Value of array index
	 * @param string $value Value of array entry
	 * @param string $filter The comparison amount
	 * @return boolean
	 **/
	private function _filter_gt ($key,$value,$filter) {
		return ($key > $filter);
	}

	/**
	 * Helper filter to find array indexes less than a specified amount
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param string $key Value of array index
	 * @param string $value Value of array entry
	 * @param string $filter The comparison amount
	 * @return boolean
	 **/
	private function _filter_lt ($key,$value,$filter) {
		return ($key < $filter);
	}

	/**
	 * Uses a callback to filter arrays based on key/value pairs
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $array The source array
	 * @param array $callback The callback function name
	 * @param string $filter The filter amount
	 * @return array The filtered array
	 **/
	private static function array_key_filter ($array, $callback, $filter=false) {
		$_ = array();
		foreach ($array as $key => $value)
			if (call_user_func($callback,$key,$value,$filter)) $_[$key] = $value;
		return $_;
	}

} // END class xmlQuery

?>
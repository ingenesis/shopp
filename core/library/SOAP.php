<?php
/**
 * SOAP.php
 *
 * SOAP client support from the NuSOAP web services toolkit
 *
 * @version 1.123
 * @license GNU LGPL {@see license.txt}
 * @package shopp
 * @since 1.2
 * @subpackage SOAP
 **/

if (!class_exists('nusoap_base')) {
	/*
	$Id: nusoap.php,v 1.123 2010/04/26 20:15:08 snichol Exp $

	NuSOAP - Web Services Toolkit for PHP

	Copyright (c) 2002 NuSphere Corporation

	This library is free software; you can redistribute it and/or
	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

	The NuSOAP project home is:
	http://sourceforge.net/projects/nusoap/

	The primary support for NuSOAP is the Help forum on the project home page.

	If you have any questions or comments, please email:

	Dietrich Ayala
	dietrich@ganx4.com
	http://dietrich.ganx4.com/nusoap

	NuSphere Corporation
	http://www.nusphere.com

	*/

	/*
	 *	Some of the standards implmented in whole or part by NuSOAP:
	 *
	 *	SOAP 1.1 (http://www.w3.org/TR/2000/NOTE-SOAP-20000508/)
	 *	WSDL 1.1 (http://www.w3.org/TR/2001/NOTE-wsdl-20010315)
	 *	SOAP Messages With Attachments (http://www.w3.org/TR/SOAP-attachments)
	 *	XML 1.0 (http://www.w3.org/TR/2006/REC-xml-20060816/)
	 *	Namespaces in XML 1.0 (http://www.w3.org/TR/2006/REC-xml-names-20060816/)
	 *	XML Schema 1.0 (http://www.w3.org/TR/xmlschema-0/)
	 *	RFC 2045 Multipurpose Internet Mail Extensions (MIME) Part One: Format of Internet Message Bodies
	 *	RFC 2068 Hypertext Transfer Protocol -- HTTP/1.1
	 *	RFC 2617 HTTP Authentication: Basic and Digest Access Authentication
	 */
	$GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'] = 9;

	class nusoap_base {

		var $title = 'NuSOAP';
		var $version = '0.9.5';
		var $revision = '$Revision: 1.123 $';
		var $error_str = '';
	    var $debug_str = '';
		var $charencoding = true;
		var $debugLevel;
		var $XMLSchemaVersion = 'http://www.w3.org/2001/XMLSchema';
	    var $soap_defencoding = 'ISO-8859-1';
		var $namespaces = array(
			'SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/',
			'xsd' => 'http://www.w3.org/2001/XMLSchema',
			'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
			'SOAP-ENC' => 'http://schemas.xmlsoap.org/soap/encoding/'
			);
		var $usedNamespaces = array();
		var $typemap = array(
		'http://www.w3.org/2001/XMLSchema' => array(
			'string'=>'string','boolean'=>'boolean','float'=>'double','double'=>'double','decimal'=>'double',
			'duration'=>'','dateTime'=>'string','time'=>'string','date'=>'string','gYearMonth'=>'',
			'gYear'=>'','gMonthDay'=>'','gDay'=>'','gMonth'=>'','hexBinary'=>'string','base64Binary'=>'string',
						'anyType'=>'string','anySimpleType'=>'string',
						'normalizedString'=>'string','token'=>'string','language'=>'','NMTOKEN'=>'','NMTOKENS'=>'','Name'=>'','NCName'=>'','ID'=>'',
			'IDREF'=>'','IDREFS'=>'','ENTITY'=>'','ENTITIES'=>'','integer'=>'integer','nonPositiveInteger'=>'integer',
			'negativeInteger'=>'integer','long'=>'integer','int'=>'integer','short'=>'integer','byte'=>'integer','nonNegativeInteger'=>'integer',
			'unsignedLong'=>'','unsignedInt'=>'','unsignedShort'=>'','unsignedByte'=>'','positiveInteger'=>''),
		'http://www.w3.org/2000/10/XMLSchema' => array(
			'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
			'float'=>'double','dateTime'=>'string',
			'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
		'http://www.w3.org/1999/XMLSchema' => array(
			'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
			'float'=>'double','dateTime'=>'string',
			'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
		'http://soapinterop.org/xsd' => array('SOAPStruct'=>'struct'),
		'http://schemas.xmlsoap.org/soap/encoding/' => array('base64'=>'string','array'=>'array','Array'=>'array'),
	    'http://xml.apache.org/xml-soap' => array('Map')
		);

		var $xmlEntities = array('quot' => '"','amp' => '&',
			'lt' => '<','gt' => '>','apos' => "'");


		function nusoap_base() {
			$this->debugLevel = $GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'];
		}


		function getGlobalDebugLevel() {
			return $GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'];
		}


		function setGlobalDebugLevel($level) {
			$GLOBALS['_transient']['static']['nusoap_base']['globalDebugLevel'] = $level;
		}


		function getDebugLevel() {
			return $this->debugLevel;
		}


		function setDebugLevel($level) {
			$this->debugLevel = $level;
		}


		function debug($string){
			if ($this->debugLevel > 0) {
				$this->appendDebug($this->getmicrotime().' '.get_class($this).": $string\n");
			}
		}


		function appendDebug($string){
			if ($this->debugLevel > 0) {
												$this->debug_str .= $string;
			}
		}


		function clearDebug() {
									$this->debug_str = '';
		}


		function &getDebug() {
									return $this->debug_str;
		}


		function &getDebugAsXMLComment() {
									while (strpos($this->debug_str, '--')) {
				$this->debug_str = str_replace('--', '- -', $this->debug_str);
			}
			$ret = "<!--\n" . $this->debug_str . "\n-->";
	    	return $ret;
		}


		function expandEntities($val) {
			if ($this->charencoding) {
		    	$val = str_replace('&', '&amp;', $val);
		    	$val = str_replace("'", '&apos;', $val);
		    	$val = str_replace('"', '&quot;', $val);
		    	$val = str_replace('<', '&lt;', $val);
		    	$val = str_replace('>', '&gt;', $val);
		    }
		    return $val;
		}


		function getError(){
			if($this->error_str != ''){
				return $this->error_str;
			}
			return false;
		}


		function setError($str){
			$this->error_str = $str;
		}


		function isArraySimpleOrStruct($val) {
	        $keyList = array_keys($val);
			foreach ($keyList as $keyListValue) {
				if (!is_int($keyListValue)) {
					return 'arrayStruct';
				}
			}
			return 'arraySimple';
		}


		function serialize_val($val,$name=false,$type=false,$name_ns=false,$type_ns=false,$attributes=false,$use='encoded',$soapval=false) {
			$this->debug("in serialize_val: name=$name, type=$type, name_ns=$name_ns, type_ns=$type_ns, use=$use, soapval=$soapval");
			$this->appendDebug('value=' . $this->varDump($val));
			$this->appendDebug('attributes=' . $this->varDump($attributes));

	    	if (is_object($val) && get_class($val) == 'soapval' && (! $soapval)) {
	    		$this->debug("serialize_val: serialize soapval");
	        	$xml = $val->serialize($use);
				$this->appendDebug($val->getDebug());
				$val->clearDebug();
				$this->debug("serialize_val of soapval returning $xml");
				return $xml;
	        }
						if (is_numeric($name)) {
				$name = '__numeric_' . $name;
			} elseif (! $name) {
				$name = 'noname';
			}
						$xmlns = '';
	        if($name_ns){
				$prefix = 'nu'.rand(1000,9999);
				$name = $prefix.':'.$name;
				$xmlns .= " xmlns:$prefix=\"$name_ns\"";
			}
						if($type_ns != '' && $type_ns == $this->namespaces['xsd']){
							    				$type_prefix = 'xsd';
			} elseif($type_ns){
				$type_prefix = 'ns'.rand(1000,9999);
				$xmlns .= " xmlns:$type_prefix=\"$type_ns\"";
			}
						$atts = '';
			if($attributes){
				foreach($attributes as $k => $v){
					$atts .= " $k=\"".$this->expandEntities($v).'"';
				}
			}
						if (is_null($val)) {
	    		$this->debug("serialize_val: serialize null");
				if ($use == 'literal') {
										$xml = "<$name$xmlns$atts/>";
					$this->debug("serialize_val returning $xml");
		        	return $xml;
	        	} else {
					if (isset($type) && isset($type_prefix)) {
						$type_str = " xsi:type=\"$type_prefix:$type\"";
					} else {
						$type_str = '';
					}
					$xml = "<$name$xmlns$type_str$atts xsi:nil=\"true\"/>";
					$this->debug("serialize_val returning $xml");
		        	return $xml;
	        	}
			}
	        	        if($type != '' && isset($this->typemap[$this->XMLSchemaVersion][$type])){
	    		$this->debug("serialize_val: serialize xsd built-in primitive type");
	        	if (is_bool($val)) {
	        		if ($type == 'boolean') {
		        		$val = $val ? 'true' : 'false';
		        	} elseif (! $val) {
		        		$val = 0;
		        	}
				} else if (is_string($val)) {
					$val = $this->expandEntities($val);
				}
				if ($use == 'literal') {
					$xml = "<$name$xmlns$atts>$val</$name>";
					$this->debug("serialize_val returning $xml");
		        	return $xml;
	        	} else {
					$xml = "<$name$xmlns xsi:type=\"xsd:$type\"$atts>$val</$name>";
					$this->debug("serialize_val returning $xml");
		        	return $xml;
	        	}
	        }
						$xml = '';
			switch(true) {
				case (is_bool($val) || $type == 'boolean'):
			   		$this->debug("serialize_val: serialize boolean");
	        		if ($type == 'boolean') {
		        		$val = $val ? 'true' : 'false';
		        	} elseif (! $val) {
		        		$val = 0;
		        	}
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>$val</$name>";
					} else {
						$xml .= "<$name$xmlns xsi:type=\"xsd:boolean\"$atts>$val</$name>";
					}
					break;
				case (is_int($val) || is_long($val) || $type == 'int'):
			   		$this->debug("serialize_val: serialize int");
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>$val</$name>";
					} else {
						$xml .= "<$name$xmlns xsi:type=\"xsd:int\"$atts>$val</$name>";
					}
					break;
				case (is_float($val)|| is_double($val) || $type == 'float'):
			   		$this->debug("serialize_val: serialize float");
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>$val</$name>";
					} else {
						$xml .= "<$name$xmlns xsi:type=\"xsd:float\"$atts>$val</$name>";
					}
					break;
				case (is_string($val) || $type == 'string'):
			   		$this->debug("serialize_val: serialize string");
					$val = $this->expandEntities($val);
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>$val</$name>";
					} else {
						$xml .= "<$name$xmlns xsi:type=\"xsd:string\"$atts>$val</$name>";
					}
					break;
				case is_object($val):
			   		$this->debug("serialize_val: serialize object");
			    	if (get_class($val) == 'soapval') {
			    		$this->debug("serialize_val: serialize soapval object");
			        	$pXml = $val->serialize($use);
						$this->appendDebug($val->getDebug());
						$val->clearDebug();
			        } else {
						if (! $name) {
							$name = get_class($val);
							$this->debug("In serialize_val, used class name $name as element name");
						} else {
							$this->debug("In serialize_val, do not override name $name for element name for class " . get_class($val));
						}
						foreach(get_object_vars($val) as $k => $v){
							$pXml = isset($pXml) ? $pXml.$this->serialize_val($v,$k,false,false,false,false,$use) : $this->serialize_val($v,$k,false,false,false,false,$use);
						}
					}
					if(isset($type) && isset($type_prefix)){
						$type_str = " xsi:type=\"$type_prefix:$type\"";
					} else {
						$type_str = '';
					}
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>$pXml</$name>";
					} else {
						$xml .= "<$name$xmlns$type_str$atts>$pXml</$name>";
					}
					break;
				break;
				case (is_array($val) || $type):
										$valueType = $this->isArraySimpleOrStruct($val);
	                if($valueType=='arraySimple' || preg_match('/^ArrayOf/',$type)){
				   		$this->debug("serialize_val: serialize array");
						$i = 0;
						if(is_array($val) && count($val)> 0){
							foreach($val as $v){
		                    	if(is_object($v) && get_class($v) ==  'soapval'){
									$tt_ns = $v->type_ns;
									$tt = $v->type;
								} elseif (is_array($v)) {
									$tt = $this->isArraySimpleOrStruct($v);
								} else {
									$tt = gettype($v);
		                        }
								$array_types[$tt] = 1;
																$xml .= $this->serialize_val($v,'item',false,false,false,false,$use);
								++$i;
							}
							if(count($array_types) > 1){
								$array_typename = 'xsd:anyType';
							} elseif(isset($tt) && isset($this->typemap[$this->XMLSchemaVersion][$tt])) {
								if ($tt == 'integer') {
									$tt = 'int';
								}
								$array_typename = 'xsd:'.$tt;
							} elseif(isset($tt) && $tt == 'arraySimple'){
								$array_typename = 'SOAP-ENC:Array';
							} elseif(isset($tt) && $tt == 'arrayStruct'){
								$array_typename = 'unnamed_struct_use_soapval';
							} else {
																if ($tt_ns != '' && $tt_ns == $this->namespaces['xsd']){
									 $array_typename = 'xsd:' . $tt;
								} elseif ($tt_ns) {
									$tt_prefix = 'ns' . rand(1000, 9999);
									$array_typename = "$tt_prefix:$tt";
									$xmlns .= " xmlns:$tt_prefix=\"$tt_ns\"";
								} else {
									$array_typename = $tt;
								}
							}
							$array_type = $i;
							if ($use == 'literal') {
								$type_str = '';
							} else if (isset($type) && isset($type_prefix)) {
								$type_str = " xsi:type=\"$type_prefix:$type\"";
							} else {
								$type_str = " xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"".$array_typename."[$array_type]\"";
							}
												} else {
							if ($use == 'literal') {
								$type_str = '';
							} else if (isset($type) && isset($type_prefix)) {
								$type_str = " xsi:type=\"$type_prefix:$type\"";
							} else {
								$type_str = " xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"xsd:anyType[0]\"";
							}
						}
												$xml = "<$name$xmlns$type_str$atts>".$xml."</$name>";
					} else {
										   		$this->debug("serialize_val: serialize struct");
						if(isset($type) && isset($type_prefix)){
							$type_str = " xsi:type=\"$type_prefix:$type\"";
						} else {
							$type_str = '';
						}
						if ($use == 'literal') {
							$xml .= "<$name$xmlns$atts>";
						} else {
							$xml .= "<$name$xmlns$type_str$atts>";
						}
						foreach($val as $k => $v){
														if ($type == 'Map' && $type_ns == 'http://xml.apache.org/xml-soap') {
								$xml .= '<item>';
								$xml .= $this->serialize_val($k,'key',false,false,false,false,$use);
								$xml .= $this->serialize_val($v,'value',false,false,false,false,$use);
								$xml .= '</item>';
							} else {
								$xml .= $this->serialize_val($v,$k,false,false,false,false,$use);
							}
						}
						$xml .= "</$name>";
					}
					break;
				default:
			   		$this->debug("serialize_val: serialize unknown");
					$xml .= 'not detected, got '.gettype($val).' for '.$val;
					break;
			}
			$this->debug("serialize_val returning $xml");
			return $xml;
		}


	    function serializeEnvelope($body,$headers=false,$namespaces=array(),$style='rpc',$use='encoded',$encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'){

		$this->debug("In serializeEnvelope length=" . strlen($body) . " body (max 1000 characters)=" . substr($body, 0, 1000) . " style=$style use=$use encodingStyle=$encodingStyle");
		$this->debug("headers:");
		$this->appendDebug($this->varDump($headers));
		$this->debug("namespaces:");
		$this->appendDebug($this->varDump($namespaces));

			    $ns_string = '';
		foreach(array_merge($this->namespaces,$namespaces) as $k => $v){
			$ns_string .= " xmlns:$k=\"$v\"";
		}
		if($encodingStyle) {
			$ns_string = " SOAP-ENV:encodingStyle=\"$encodingStyle\"$ns_string";
		}

				if($headers){
			if (is_array($headers)) {
				$xml = '';
				foreach ($headers as $k => $v) {
					if (is_object($v) && get_class($v) == 'soapval') {
						$xml .= $this->serialize_val($v, false, false, false, false, false, $use);
					} else {
						$xml .= $this->serialize_val($v, $k, false, false, false, false, $use);
					}
				}
				$headers = $xml;
				$this->debug("In serializeEnvelope, serialized array of headers to $headers");
			}
			$headers = "<SOAP-ENV:Header>".$headers."</SOAP-ENV:Header>";
		}
				return
		'<?xml version="1.0" encoding="'.$this->soap_defencoding .'"?'.">".
		'<SOAP-ENV:Envelope'.$ns_string.">".
		$headers.
		"<SOAP-ENV:Body>".
			$body.
		"</SOAP-ENV:Body>".
		"</SOAP-ENV:Envelope>";
	    }


	    function formatDump($str){
			$str = htmlspecialchars($str);
			return nl2br($str);
	    }


		function contractQname($qname){
									if (strrpos($qname, ':')) {
								$name = substr($qname, strrpos($qname, ':') + 1);
								$ns = substr($qname, 0, strrpos($qname, ':'));
				$p = $this->getPrefixFromNamespace($ns);
				if ($p) {
					return $p . ':' . $name;
				}
				return $qname;
			} else {
				return $qname;
			}
		}


		function expandQname($qname){
						if(strpos($qname,':') && !preg_match('/^http:\/\//',$qname)){
								$name = substr(strstr($qname,':'),1);
								$prefix = substr($qname,0,strpos($qname,':'));
				if(isset($this->namespaces[$prefix])){
					return $this->namespaces[$prefix].':'.$name;
				} else {
					return $qname;
				}
			} else {
				return $qname;
			}
		}


		function getLocalPart($str){
			if($sstr = strrchr($str,':')){
								return substr( $sstr, 1 );
			} else {
				return $str;
			}
		}


		function getPrefix($str){
			if($pos = strrpos($str,':')){
								return substr($str,0,$pos);
			}
			return false;
		}


		function getNamespaceFromPrefix($prefix){
			if (isset($this->namespaces[$prefix])) {
				return $this->namespaces[$prefix];
			}
						return false;
		}


		function getPrefixFromNamespace($ns) {
			foreach ($this->namespaces as $p => $n) {
				if ($ns == $n || $ns == $p) {
				    $this->usedNamespaces[$p] = $n;
					return $p;
				}
			}
			return false;
		}


		function getmicrotime() {
			if (function_exists('gettimeofday')) {
				$tod = gettimeofday();
				$sec = $tod['sec'];
				$usec = $tod['usec'];
			} else {
				$sec = time();
				$usec = 0;
			}
			return strftime('%Y-%m-%d %H:%M:%S', $sec) . '.' . sprintf('%06d', $usec);
		}


	    function varDump($data) {
			ob_start();
			var_dump($data);
			$ret_val = ob_get_contents();
			ob_end_clean();
			return $ret_val;
		}


		function __toString() {
			return $this->varDump($this);
		}
	}




	function timestamp_to_iso8601($timestamp,$utc=true){
		$datestr = date('Y-m-d\TH:i:sO',$timestamp);
		$pos = strrpos($datestr, "+");
		if ($pos === FALSE) {
			$pos = strrpos($datestr, "-");
		}
		if ($pos !== FALSE) {
			if (strlen($datestr) == $pos + 5) {
				$datestr = substr($datestr, 0, $pos + 3) . ':' . substr($datestr, -2);
			}
		}
		if($utc){
			$pattern = '/'.
			'([0-9]{4})-'.				'([0-9]{2})-'.				'([0-9]{2})'.				'T'.						'([0-9]{2}):'.				'([0-9]{2}):'.				'([0-9]{2})(\.[0-9]*)?'. 			'(Z|[+\-][0-9]{2}:?[0-9]{2})?'. 			'/';

			if(preg_match($pattern,$datestr,$regs)){
				return sprintf('%04d-%02d-%02dT%02d:%02d:%02dZ',$regs[1],$regs[2],$regs[3],$regs[4],$regs[5],$regs[6]);
			}
			return false;
		} else {
			return $datestr;
		}
	}


	function iso8601_to_timestamp($datestr){
		$pattern = '/'.
		'([0-9]{4})-'.			'([0-9]{2})-'.			'([0-9]{2})'.			'T'.					'([0-9]{2}):'.			'([0-9]{2}):'.			'([0-9]{2})(\.[0-9]+)?'. 		'(Z|[+\-][0-9]{2}:?[0-9]{2})?'. 		'/';
		if(preg_match($pattern,$datestr,$regs)){
						if($regs[8] != 'Z'){
				$op = substr($regs[8],0,1);
				$h = substr($regs[8],1,2);
				$m = substr($regs[8],strlen($regs[8])-2,2);
				if($op == '-'){
					$regs[4] = $regs[4] + $h;
					$regs[5] = $regs[5] + $m;
				} elseif($op == '+'){
					$regs[4] = $regs[4] - $h;
					$regs[5] = $regs[5] - $m;
				}
			}
			return gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			} else {
			return false;
		}
	}


	function usleepWindows($usec)
	{
		$start = gettimeofday();

		do
		{
			$stop = gettimeofday();
			$timePassed = 1000000 * ($stop['sec'] - $start['sec'])
			+ $stop['usec'] - $start['usec'];
		}
		while ($timePassed < $usec);
	}


	class nusoap_fault extends nusoap_base {

		var $faultcode;

		var $faultactor;

		var $faultstring;

		var $faultdetail;


		function nusoap_fault($faultcode,$faultactor='',$faultstring='',$faultdetail=''){
			parent::nusoap_base();
			$this->faultcode = $faultcode;
			$this->faultactor = $faultactor;
			$this->faultstring = $faultstring;
			$this->faultdetail = $faultdetail;
		}


		function serialize(){
			$ns_string = '';
			foreach($this->namespaces as $k => $v){
				$ns_string .= "\n  xmlns:$k=\"$v\"";
			}
			$return_msg =
				'<?xml version="1.0" encoding="'.$this->soap_defencoding.'"?>'.
				'<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"'.$ns_string.">\n".
					'<SOAP-ENV:Body>'.
					'<SOAP-ENV:Fault>'.
						$this->serialize_val($this->faultcode, 'faultcode').
						$this->serialize_val($this->faultactor, 'faultactor').
						$this->serialize_val($this->faultstring, 'faultstring').
						$this->serialize_val($this->faultdetail, 'detail').
					'</SOAP-ENV:Fault>'.
					'</SOAP-ENV:Body>'.
				'</SOAP-ENV:Envelope>';
			return $return_msg;
		}
	}


	class soap_fault extends nusoap_fault {
	}

	class nusoap_xmlschema extends nusoap_base  {

				var $schema = '';
		var $xml = '';
				var $enclosingNamespaces;
				var $schemaInfo = array();
		var $schemaTargetNamespace = '';
				var $attributes = array();
		var $complexTypes = array();
		var $complexTypeStack = array();
		var $currentComplexType = null;
		var $elements = array();
		var $elementStack = array();
		var $currentElement = null;
		var $simpleTypes = array();
		var $simpleTypeStack = array();
		var $currentSimpleType = null;
				var $imports = array();
				var $parser;
		var $position = 0;
		var $depth = 0;
		var $depth_array = array();
		var $message = array();
		var $defaultNamespace = array();


		function nusoap_xmlschema($schema='',$xml='',$namespaces=array()){
			parent::nusoap_base();
			$this->debug('nusoap_xmlschema class instantiated, inside constructor');
						$this->schema = $schema;
			$this->xml = $xml;

						$this->enclosingNamespaces = $namespaces;
			$this->namespaces = array_merge($this->namespaces, $namespaces);

						if($schema != ''){
				$this->debug('initial schema file: '.$schema);
				$this->parseFile($schema, 'schema');
			}

						if($xml != ''){
				$this->debug('initial xml file: '.$xml);
				$this->parseFile($xml, 'xml');
			}

		}


		function parseFile($xml,$type){
						if($xml != ""){
				$xmlStr = @join("",@file($xml));
				if($xmlStr == ""){
					$msg = 'Error reading XML from '.$xml;
					$this->setError($msg);
					$this->debug($msg);
				return false;
				} else {
					$this->debug("parsing $xml");
					$this->parseString($xmlStr,$type);
					$this->debug("done parsing $xml");
				return true;
				}
			}
			return false;
		}


		function parseString($xml,$type){
						if($xml != ""){

		    			    	$this->parser = xml_parser_create();
		    			    	xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

		    			    	xml_set_object($this->parser, $this);

		    					if($type == "schema"){
			    	xml_set_element_handler($this->parser, 'schemaStartElement','schemaEndElement');
			    	xml_set_character_data_handler($this->parser,'schemaCharacterData');
				} elseif($type == "xml"){
					xml_set_element_handler($this->parser, 'xmlStartElement','xmlEndElement');
			    	xml_set_character_data_handler($this->parser,'xmlCharacterData');
				}

			    			    if(!xml_parse($this->parser,$xml,true)){
									$errstr = sprintf('XML error parsing XML schema on line %d: %s',
					xml_get_current_line_number($this->parser),
					xml_error_string(xml_get_error_code($this->parser))
					);
					$this->debug($errstr);
					$this->debug("XML payload:\n" . $xml);
					$this->setError($errstr);
		    	}

				xml_parser_free($this->parser);
			} else{
				$this->debug('no xml passed to parseString()!!');
				$this->setError('no xml passed to parseString()!!');
			}
		}


		function CreateTypeName($ename) {
			$scope = '';
			for ($i = 0; $i < count($this->complexTypeStack); $i++) {
				$scope .= $this->complexTypeStack[$i] . '_';
			}
			return $scope . $ename . '_ContainedType';
		}


		function schemaStartElement($parser, $name, $attrs) {

						$pos = $this->position++;
			$depth = $this->depth++;
						$this->depth_array[$depth] = $pos;
			$this->message[$pos] = array('cdata' => '');
			if ($depth > 0) {
				$this->defaultNamespace[$pos] = $this->defaultNamespace[$this->depth_array[$depth - 1]];
			} else {
				$this->defaultNamespace[$pos] = false;
			}

						if($prefix = $this->getPrefix($name)){
								$name = $this->getLocalPart($name);
			} else {
	        	$prefix = '';
	        }

	        	        if(count($attrs) > 0){
	        	foreach($attrs as $k => $v){
	                					if(preg_match('/^xmlns/',$k)){
	                		                		                	if($ns_prefix = substr(strrchr($k,':'),1)){
	                									$this->namespaces[$ns_prefix] = $v;
						} else {
							$this->defaultNamespace[$pos] = $v;
							if (! $this->getPrefixFromNamespace($v)) {
								$this->namespaces['ns'.(count($this->namespaces)+1)] = $v;
							}
						}
						if($v == 'http://www.w3.org/2001/XMLSchema' || $v == 'http://www.w3.org/1999/XMLSchema' || $v == 'http://www.w3.org/2000/10/XMLSchema'){
							$this->XMLSchemaVersion = $v;
							$this->namespaces['xsi'] = $v.'-instance';
						}
					}
	        	}
	        	foreach($attrs as $k => $v){
	                	                $k = strpos($k,':') ? $this->expandQname($k) : $k;
	                $v = strpos($v,':') ? $this->expandQname($v) : $v;
	        		$eAttrs[$k] = $v;
	        	}
	        	$attrs = $eAttrs;
	        } else {
	        	$attrs = array();
	        }
						switch($name){
				case 'all':							case 'choice':
				case 'group':
				case 'sequence':
										$this->complexTypes[$this->currentComplexType]['compositor'] = $name;
																			break;
				case 'attribute':		            		            	$this->xdebug("parsing attribute:");
	            	$this->appendDebug($this->varDump($attrs));
					if (!isset($attrs['form'])) {
												$attrs['form'] = $this->schemaInfo['attributeFormDefault'];
					}
	            	if (isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])) {
						$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
						if (!strpos($v, ':')) {
														if ($this->defaultNamespace[$pos]) {
																$attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'] = $this->defaultNamespace[$pos] . ':' . $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
							}
						}
	            	}
	                if(isset($attrs['name'])){
						$this->attributes[$attrs['name']] = $attrs;
						$aname = $attrs['name'];
					} elseif(isset($attrs['ref']) && $attrs['ref'] == 'http://schemas.xmlsoap.org/soap/encoding/:arrayType'){
						if (isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])) {
		                	$aname = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
		                } else {
		                	$aname = '';
		                }
					} elseif(isset($attrs['ref'])){
						$aname = $attrs['ref'];
	                    $this->attributes[$attrs['ref']] = $attrs;
					}

					if($this->currentComplexType){							$this->complexTypes[$this->currentComplexType]['attrs'][$aname] = $attrs;
					}
										if(isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType']) || $this->getLocalPart($aname) == 'arrayType'){
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
	                	$prefix = $this->getPrefix($aname);
						if(isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])){
							$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
						} else {
							$v = '';
						}
	                    if(strpos($v,'[,]')){
	                        $this->complexTypes[$this->currentComplexType]['multidimensional'] = true;
	                    }
	                    $v = substr($v,0,strpos($v,'[')); 	                    if(!strpos($v,':') && isset($this->typemap[$this->XMLSchemaVersion][$v])){
	                        $v = $this->XMLSchemaVersion.':'.$v;
	                    }
	                    $this->complexTypes[$this->currentComplexType]['arrayType'] = $v;
					}
				break;
				case 'complexContent':						$this->xdebug("do nothing for element $name");
				break;
				case 'complexType':
					array_push($this->complexTypeStack, $this->currentComplexType);
					if(isset($attrs['name'])){
																		$this->xdebug('processing named complexType '.$attrs['name']);
												$this->currentComplexType = $attrs['name'];
						$this->complexTypes[$this->currentComplexType] = $attrs;
						$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
																																																if(isset($attrs['base']) && preg_match('/:Array$/',$attrs['base'])){
							$this->xdebug('complexType is unusual array');
							$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
						} else {
							$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
						}
					} else {
						$name = $this->CreateTypeName($this->currentElement);
						$this->xdebug('processing unnamed complexType for element ' . $this->currentElement . ' named ' . $name);
						$this->currentComplexType = $name;
												$this->complexTypes[$this->currentComplexType] = $attrs;
						$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
																																																if(isset($attrs['base']) && preg_match('/:Array$/',$attrs['base'])){
							$this->xdebug('complexType is unusual array');
							$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
						} else {
							$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
						}
					}
					$this->complexTypes[$this->currentComplexType]['simpleContent'] = 'false';
				break;
				case 'element':
					array_push($this->elementStack, $this->currentElement);
					if (!isset($attrs['form'])) {
						if ($this->currentComplexType) {
							$attrs['form'] = $this->schemaInfo['elementFormDefault'];
						} else {
														$attrs['form'] = 'qualified';
						}
					}
					if(isset($attrs['type'])){
						$this->xdebug("processing typed element ".$attrs['name']." of type ".$attrs['type']);
						if (! $this->getPrefix($attrs['type'])) {
							if ($this->defaultNamespace[$pos]) {
								$attrs['type'] = $this->defaultNamespace[$pos] . ':' . $attrs['type'];
								$this->xdebug('used default namespace to make type ' . $attrs['type']);
							}
						}
																																																if ($this->currentComplexType && $this->complexTypes[$this->currentComplexType]['phpType'] == 'array') {
							$this->xdebug('arrayType for unusual array is ' . $attrs['type']);
							$this->complexTypes[$this->currentComplexType]['arrayType'] = $attrs['type'];
						}
						$this->currentElement = $attrs['name'];
						$ename = $attrs['name'];
					} elseif(isset($attrs['ref'])){
						$this->xdebug("processing element as ref to ".$attrs['ref']);
						$this->currentElement = "ref to ".$attrs['ref'];
						$ename = $this->getLocalPart($attrs['ref']);
					} else {
						$type = $this->CreateTypeName($this->currentComplexType . '_' . $attrs['name']);
						$this->xdebug("processing untyped element " . $attrs['name'] . ' type ' . $type);
						$this->currentElement = $attrs['name'];
						$attrs['type'] = $this->schemaTargetNamespace . ':' . $type;
						$ename = $attrs['name'];
					}
					if (isset($ename) && $this->currentComplexType) {
						$this->xdebug("add element $ename to complexType $this->currentComplexType");
						$this->complexTypes[$this->currentComplexType]['elements'][$ename] = $attrs;
					} elseif (!isset($attrs['ref'])) {
						$this->xdebug("add element $ename to elements array");
						$this->elements[ $attrs['name'] ] = $attrs;
						$this->elements[ $attrs['name'] ]['typeClass'] = 'element';
					}
				break;
				case 'enumeration':						$this->xdebug('enumeration ' . $attrs['value']);
					if ($this->currentSimpleType) {
						$this->simpleTypes[$this->currentSimpleType]['enumeration'][] = $attrs['value'];
					} elseif ($this->currentComplexType) {
						$this->complexTypes[$this->currentComplexType]['enumeration'][] = $attrs['value'];
					}
				break;
				case 'extension':						$this->xdebug('extension ' . $attrs['base']);
					if ($this->currentComplexType) {
						$ns = $this->getPrefix($attrs['base']);
						if ($ns == '') {
							$this->complexTypes[$this->currentComplexType]['extensionBase'] = $this->schemaTargetNamespace . ':' . $attrs['base'];
						} else {
							$this->complexTypes[$this->currentComplexType]['extensionBase'] = $attrs['base'];
						}
					} else {
						$this->xdebug('no current complexType to set extensionBase');
					}
				break;
				case 'import':
				    if (isset($attrs['schemaLocation'])) {
						$this->xdebug('import namespace ' . $attrs['namespace'] . ' from ' . $attrs['schemaLocation']);
	                    $this->imports[$attrs['namespace']][] = array('location' => $attrs['schemaLocation'], 'loaded' => false);
					} else {
						$this->xdebug('import namespace ' . $attrs['namespace']);
	                    $this->imports[$attrs['namespace']][] = array('location' => '', 'loaded' => true);
						if (! $this->getPrefixFromNamespace($attrs['namespace'])) {
							$this->namespaces['ns'.(count($this->namespaces)+1)] = $attrs['namespace'];
						}
					}
				break;
				case 'include':
				    if (isset($attrs['schemaLocation'])) {
						$this->xdebug('include into namespace ' . $this->schemaTargetNamespace . ' from ' . $attrs['schemaLocation']);
	                    $this->imports[$this->schemaTargetNamespace][] = array('location' => $attrs['schemaLocation'], 'loaded' => false);
					} else {
						$this->xdebug('ignoring invalid XML Schema construct: include without schemaLocation attribute');
					}
				break;
				case 'list':						$this->xdebug("do nothing for element $name");
				break;
				case 'restriction':						$this->xdebug('restriction ' . $attrs['base']);
					if($this->currentSimpleType){
						$this->simpleTypes[$this->currentSimpleType]['type'] = $attrs['base'];
					} elseif($this->currentComplexType){
						$this->complexTypes[$this->currentComplexType]['restrictionBase'] = $attrs['base'];
						if(strstr($attrs['base'],':') == ':Array'){
							$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
						}
					}
				break;
				case 'schema':
					$this->schemaInfo = $attrs;
					$this->schemaInfo['schemaVersion'] = $this->getNamespaceFromPrefix($prefix);
					if (isset($attrs['targetNamespace'])) {
						$this->schemaTargetNamespace = $attrs['targetNamespace'];
					}
					if (!isset($attrs['elementFormDefault'])) {
						$this->schemaInfo['elementFormDefault'] = 'unqualified';
					}
					if (!isset($attrs['attributeFormDefault'])) {
						$this->schemaInfo['attributeFormDefault'] = 'unqualified';
					}
				break;
				case 'simpleContent':						if ($this->currentComplexType) {							$this->complexTypes[$this->currentComplexType]['simpleContent'] = 'true';
					} else {
						$this->xdebug("do nothing for element $name because there is no current complexType");
					}
				break;
				case 'simpleType':
					array_push($this->simpleTypeStack, $this->currentSimpleType);
					if(isset($attrs['name'])){
						$this->xdebug("processing simpleType for name " . $attrs['name']);
						$this->currentSimpleType = $attrs['name'];
						$this->simpleTypes[ $attrs['name'] ] = $attrs;
						$this->simpleTypes[ $attrs['name'] ]['typeClass'] = 'simpleType';
						$this->simpleTypes[ $attrs['name'] ]['phpType'] = 'scalar';
					} else {
						$name = $this->CreateTypeName($this->currentComplexType . '_' . $this->currentElement);
						$this->xdebug('processing unnamed simpleType for element ' . $this->currentElement . ' named ' . $name);
						$this->currentSimpleType = $name;
												$this->simpleTypes[$this->currentSimpleType] = $attrs;
						$this->simpleTypes[$this->currentSimpleType]['phpType'] = 'scalar';
					}
				break;
				case 'union':						$this->xdebug("do nothing for element $name");
				break;
				default:
					$this->xdebug("do not have any logic to process element $name");
			}
		}


		function schemaEndElement($parser, $name) {
						$this->depth--;
						if(isset($this->depth_array[$this->depth])){
	        	$pos = $this->depth_array[$this->depth];
	        }
						if ($prefix = $this->getPrefix($name)){
								$name = $this->getLocalPart($name);
			} else {
	        	$prefix = '';
	        }
						if($name == 'complexType'){
				$this->xdebug('done processing complexType ' . ($this->currentComplexType ? $this->currentComplexType : '(unknown)'));
				$this->xdebug($this->varDump($this->complexTypes[$this->currentComplexType]));
				$this->currentComplexType = array_pop($this->complexTypeStack);
							}
			if($name == 'element'){
				$this->xdebug('done processing element ' . ($this->currentElement ? $this->currentElement : '(unknown)'));
				$this->currentElement = array_pop($this->elementStack);
			}
			if($name == 'simpleType'){
				$this->xdebug('done processing simpleType ' . ($this->currentSimpleType ? $this->currentSimpleType : '(unknown)'));
				$this->xdebug($this->varDump($this->simpleTypes[$this->currentSimpleType]));
				$this->currentSimpleType = array_pop($this->simpleTypeStack);
			}
		}


		function schemaCharacterData($parser, $data){
			$pos = $this->depth_array[$this->depth - 1];
			$this->message[$pos]['cdata'] .= $data;
		}


		function serializeSchema(){

			$schemaPrefix = $this->getPrefixFromNamespace($this->XMLSchemaVersion);
			$xml = '';
						if (sizeof($this->imports) > 0) {
				foreach($this->imports as $ns => $list) {
					foreach ($list as $ii) {
						if ($ii['location'] != '') {
							$xml .= " <$schemaPrefix:import location=\"" . $ii['location'] . '" namespace="' . $ns . "\" />\n";
						} else {
							$xml .= " <$schemaPrefix:import namespace=\"" . $ns . "\" />\n";
						}
					}
				}
			}
						foreach($this->complexTypes as $typeName => $attrs){
				$contentStr = '';
								if(isset($attrs['elements']) && (count($attrs['elements']) > 0)){
					foreach($attrs['elements'] as $element => $eParts){
						if(isset($eParts['ref'])){
							$contentStr .= "   <$schemaPrefix:element ref=\"$element\"/>\n";
						} else {
							$contentStr .= "   <$schemaPrefix:element name=\"$element\" type=\"" . $this->contractQName($eParts['type']) . "\"";
							foreach ($eParts as $aName => $aValue) {
																if ($aName != 'name' && $aName != 'type') {
									$contentStr .= " $aName=\"$aValue\"";
								}
							}
							$contentStr .= "/>\n";
						}
					}
										if (isset($attrs['compositor']) && ($attrs['compositor'] != '')) {
						$contentStr = "  <$schemaPrefix:$attrs[compositor]>\n".$contentStr."  </$schemaPrefix:$attrs[compositor]>\n";
					}
				}
								if(isset($attrs['attrs']) && (count($attrs['attrs']) >= 1)){
					foreach($attrs['attrs'] as $attr => $aParts){
						$contentStr .= "    <$schemaPrefix:attribute";
						foreach ($aParts as $a => $v) {
							if ($a == 'ref' || $a == 'type') {
								$contentStr .= " $a=\"".$this->contractQName($v).'"';
							} elseif ($a == 'http://schemas.xmlsoap.org/wsdl/:arrayType') {
								$this->usedNamespaces['wsdl'] = $this->namespaces['wsdl'];
								$contentStr .= ' wsdl:arrayType="'.$this->contractQName($v).'"';
							} else {
								$contentStr .= " $a=\"$v\"";
							}
						}
						$contentStr .= "/>\n";
					}
				}
								if (isset($attrs['restrictionBase']) && $attrs['restrictionBase'] != ''){
					$contentStr = "   <$schemaPrefix:restriction base=\"".$this->contractQName($attrs['restrictionBase'])."\">\n".$contentStr."   </$schemaPrefix:restriction>\n";
										if ((isset($attrs['elements']) && count($attrs['elements']) > 0) || (isset($attrs['attrs']) && count($attrs['attrs']) > 0)){
						$contentStr = "  <$schemaPrefix:complexContent>\n".$contentStr."  </$schemaPrefix:complexContent>\n";
					}
				}
								if($contentStr != ''){
					$contentStr = " <$schemaPrefix:complexType name=\"$typeName\">\n".$contentStr." </$schemaPrefix:complexType>\n";
				} else {
					$contentStr = " <$schemaPrefix:complexType name=\"$typeName\"/>\n";
				}
				$xml .= $contentStr;
			}
						if(isset($this->simpleTypes) && count($this->simpleTypes) > 0){
				foreach($this->simpleTypes as $typeName => $eParts){
					$xml .= " <$schemaPrefix:simpleType name=\"$typeName\">\n  <$schemaPrefix:restriction base=\"".$this->contractQName($eParts['type'])."\">\n";
					if (isset($eParts['enumeration'])) {
						foreach ($eParts['enumeration'] as $e) {
							$xml .= "  <$schemaPrefix:enumeration value=\"$e\"/>\n";
						}
					}
					$xml .= "  </$schemaPrefix:restriction>\n </$schemaPrefix:simpleType>";
				}
			}
						if(isset($this->elements) && count($this->elements) > 0){
				foreach($this->elements as $element => $eParts){
					$xml .= " <$schemaPrefix:element name=\"$element\" type=\"".$this->contractQName($eParts['type'])."\"/>\n";
				}
			}
						if(isset($this->attributes) && count($this->attributes) > 0){
				foreach($this->attributes as $attr => $aParts){
					$xml .= " <$schemaPrefix:attribute name=\"$attr\" type=\"".$this->contractQName($aParts['type'])."\"\n/>";
				}
			}
						$attr = '';
			foreach ($this->schemaInfo as $k => $v) {
				if ($k == 'elementFormDefault' || $k == 'attributeFormDefault') {
					$attr .= " $k=\"$v\"";
				}
			}
			$el = "<$schemaPrefix:schema$attr targetNamespace=\"$this->schemaTargetNamespace\"\n";
			foreach (array_diff($this->usedNamespaces, $this->enclosingNamespaces) as $nsp => $ns) {
				$el .= " xmlns:$nsp=\"$ns\"";
			}
			$xml = $el . ">\n".$xml."</$schemaPrefix:schema>\n";
			return $xml;
		}


		function xdebug($string){
			$this->debug('<' . $this->schemaTargetNamespace . '> '.$string);
		}


		function getPHPType($type,$ns){
			if(isset($this->typemap[$ns][$type])){
								return $this->typemap[$ns][$type];
			} elseif(isset($this->complexTypes[$type])){
								return $this->complexTypes[$type]['phpType'];
			}
			return false;
		}


		function getTypeDef($type){
						if (substr($type, -1) == '^') {
				$is_element = 1;
				$type = substr($type, 0, -1);
			} else {
				$is_element = 0;
			}

			if((! $is_element) && isset($this->complexTypes[$type])){
				$this->xdebug("in getTypeDef, found complexType $type");
				return $this->complexTypes[$type];
			} elseif((! $is_element) && isset($this->simpleTypes[$type])){
				$this->xdebug("in getTypeDef, found simpleType $type");
				if (!isset($this->simpleTypes[$type]['phpType'])) {
															$uqType = substr($this->simpleTypes[$type]['type'], strrpos($this->simpleTypes[$type]['type'], ':') + 1);
					$ns = substr($this->simpleTypes[$type]['type'], 0, strrpos($this->simpleTypes[$type]['type'], ':'));
					$etype = $this->getTypeDef($uqType);
					if ($etype) {
						$this->xdebug("in getTypeDef, found type for simpleType $type:");
						$this->xdebug($this->varDump($etype));
						if (isset($etype['phpType'])) {
							$this->simpleTypes[$type]['phpType'] = $etype['phpType'];
						}
						if (isset($etype['elements'])) {
							$this->simpleTypes[$type]['elements'] = $etype['elements'];
						}
					}
				}
				return $this->simpleTypes[$type];
			} elseif(isset($this->elements[$type])){
				$this->xdebug("in getTypeDef, found element $type");
				if (!isset($this->elements[$type]['phpType'])) {
										$uqType = substr($this->elements[$type]['type'], strrpos($this->elements[$type]['type'], ':') + 1);
					$ns = substr($this->elements[$type]['type'], 0, strrpos($this->elements[$type]['type'], ':'));
					$etype = $this->getTypeDef($uqType);
					if ($etype) {
						$this->xdebug("in getTypeDef, found type for element $type:");
						$this->xdebug($this->varDump($etype));
						if (isset($etype['phpType'])) {
							$this->elements[$type]['phpType'] = $etype['phpType'];
						}
						if (isset($etype['elements'])) {
							$this->elements[$type]['elements'] = $etype['elements'];
						}
						if (isset($etype['extensionBase'])) {
							$this->elements[$type]['extensionBase'] = $etype['extensionBase'];
						}
					} elseif ($ns == 'http://www.w3.org/2001/XMLSchema') {
						$this->xdebug("in getTypeDef, element $type is an XSD type");
						$this->elements[$type]['phpType'] = 'scalar';
					}
				}
				return $this->elements[$type];
			} elseif(isset($this->attributes[$type])){
				$this->xdebug("in getTypeDef, found attribute $type");
				return $this->attributes[$type];
			} elseif (preg_match('/_ContainedType$/', $type)) {
				$this->xdebug("in getTypeDef, have an untyped element $type");
				$typeDef['typeClass'] = 'simpleType';
				$typeDef['phpType'] = 'scalar';
				$typeDef['type'] = 'http://www.w3.org/2001/XMLSchema:string';
				return $typeDef;
			}
			$this->xdebug("in getTypeDef, did not find $type");
			return false;
		}


	    function serializeTypeDef($type){
	    			if($typeDef = $this->getTypeDef($type)){
			$str .= '<'.$type;
		    if(is_array($typeDef['attrs'])){
			foreach($typeDef['attrs'] as $attName => $data){
			    $str .= " $attName=\"{type = ".$data['type']."}\"";
			}
		    }
		    $str .= " xmlns=\"".$this->schema['targetNamespace']."\"";
		    if(count($typeDef['elements']) > 0){
			$str .= ">";
			foreach($typeDef['elements'] as $element => $eData){
			    $str .= $this->serializeTypeDef($element);
			}
			$str .= "</$type>";
		    } elseif($typeDef['typeClass'] == 'element') {
			$str .= "></$type>";
		    } else {
			$str .= "/>";
		    }
				return $str;
		}
	    	return false;
	    }


		function typeToForm($name,$type){
						if($typeDef = $this->getTypeDef($type)){
								if($typeDef['phpType'] == 'struct'){
					$buffer .= '<table>';
					foreach($typeDef['elements'] as $child => $childDef){
						$buffer .= "
						<tr><td align='right'>$childDef[name] (type: ".$this->getLocalPart($childDef['type'])."):</td>
						<td><input type='text' name='parameters[".$name."][$childDef[name]]'></td></tr>";
					}
					$buffer .= '</table>';
								} elseif($typeDef['phpType'] == 'array'){
					$buffer .= '<table>';
					for($i=0;$i < 3; $i++){
						$buffer .= "
						<tr><td align='right'>array item (type: $typeDef[arrayType]):</td>
						<td><input type='text' name='parameters[".$name."][]'></td></tr>";
					}
					$buffer .= '</table>';
								} else {
					$buffer .= "<input type='text' name='parameters[$name]'>";
				}
			} else {
				$buffer .= "<input type='text' name='parameters[$name]'>";
			}
			return $buffer;
		}


		function addComplexType($name,$typeClass='complexType',$phpType='array',$compositor='',$restrictionBase='',$elements=array(),$attrs=array(),$arrayType=''){
			$this->complexTypes[$name] = array(
		    'name'		=> $name,
		    'typeClass'	=> $typeClass,
		    'phpType'	=> $phpType,
			'compositor'=> $compositor,
		    'restrictionBase' => $restrictionBase,
			'elements'	=> $elements,
		    'attrs'		=> $attrs,
		    'arrayType'	=> $arrayType
			);

			$this->xdebug("addComplexType $name:");
			$this->appendDebug($this->varDump($this->complexTypes[$name]));
		}


		function addSimpleType($name, $restrictionBase='', $typeClass='simpleType', $phpType='scalar', $enumeration=array()) {
			$this->simpleTypes[$name] = array(
		    'name'			=> $name,
		    'typeClass'		=> $typeClass,
		    'phpType'		=> $phpType,
		    'type'			=> $restrictionBase,
		    'enumeration'	=> $enumeration
			);

			$this->xdebug("addSimpleType $name:");
			$this->appendDebug($this->varDump($this->simpleTypes[$name]));
		}


		function addElement($attrs) {
			if (! $this->getPrefix($attrs['type'])) {
				$attrs['type'] = $this->schemaTargetNamespace . ':' . $attrs['type'];
			}
			$this->elements[ $attrs['name'] ] = $attrs;
			$this->elements[ $attrs['name'] ]['typeClass'] = 'element';

			$this->xdebug("addElement " . $attrs['name']);
			$this->appendDebug($this->varDump($this->elements[ $attrs['name'] ]));
		}
	}


	class XMLSchema extends nusoap_xmlschema {
	}

	class soapval extends nusoap_base {

		var $name;

		var $type;

		var $value;

		var $element_ns;

		var $type_ns;

		var $attributes;


	  	function soapval($name='soapval',$type=false,$value=-1,$element_ns=false,$type_ns=false,$attributes=false) {
			parent::nusoap_base();
			$this->name = $name;
			$this->type = $type;
			$this->value = $value;
			$this->element_ns = $element_ns;
			$this->type_ns = $type_ns;
			$this->attributes = $attributes;
	    }


		function serialize($use='encoded') {
			return $this->serialize_val($this->value, $this->name, $this->type, $this->element_ns, $this->type_ns, $this->attributes, $use, true);
	    }


		function decode(){
			return $this->value;
		}
	}

	class soap_transport_http extends nusoap_base {

		var $url = '';
		var $uri = '';
		var $digest_uri = '';
		var $scheme = '';
		var $host = '';
		var $port = '';
		var $path = '';
		var $request_method = 'POST';
		var $protocol_version = '1.0';
		var $encoding = '';
		var $outgoing_headers = array();
		var $incoming_headers = array();
		var $incoming_cookies = array();
		var $outgoing_payload = '';
		var $incoming_payload = '';
		var $response_status_line;			var $useSOAPAction = true;
		var $persistentConnection = false;
		var $ch = false;			var $ch_options = array();			var $use_curl = false;				var $proxy = null;					var $username = '';
		var $password = '';
		var $authtype = '';
		var $digestRequest = array();
		var $certRequest = array();

		function soap_transport_http($url, $curl_options = NULL, $use_curl = false){
			parent::nusoap_base();
			$this->debug("ctor url=$url use_curl=$use_curl curl_options:");
			$this->appendDebug($this->varDump($curl_options));
			$this->setURL($url);
			if (is_array($curl_options)) {
				$this->ch_options = $curl_options;
			}
			$this->use_curl = $use_curl;
			preg_match('/\$Revisio' . 'n: ([^ ]+)/', $this->revision, $rev);
			$this->setHeader('User-Agent', $this->title.'/'.$this->version.' ('.$rev[1].')');
		}


		function setCurlOption($option, $value) {
			$this->debug("setCurlOption option=$option, value=");
			$this->appendDebug($this->varDump($value));
			curl_setopt($this->ch, $option, $value);
		}


		function setHeader($name, $value) {
			$this->outgoing_headers[$name] = $value;
			$this->debug("set header $name: $value");
		}


		function unsetHeader($name) {
			if (isset($this->outgoing_headers[$name])) {
				$this->debug("unset header $name");
				unset($this->outgoing_headers[$name]);
			}
		}


		function setURL($url) {
			$this->url = $url;

			$u = parse_url($url);
			foreach($u as $k => $v){
				$this->debug("parsed URL $k = $v");
				$this->$k = $v;
			}

						if(isset($u['query']) && $u['query'] != ''){
	            $this->path .= '?' . $u['query'];
			}

						if(!isset($u['port'])){
				if($u['scheme'] == 'https'){
					$this->port = 443;
				} else {
					$this->port = 80;
				}
			}

			$this->uri = $this->path;
			$this->digest_uri = $this->uri;

						if (!isset($u['port'])) {
				$this->setHeader('Host', $this->host);
			} else {
				$this->setHeader('Host', $this->host.':'.$this->port);
			}

			if (isset($u['user']) && $u['user'] != '') {
				$this->setCredentials(urldecode($u['user']), isset($u['pass']) ? urldecode($u['pass']) : '');
			}
		}


		function io_method() {
			if ($this->use_curl || ($this->scheme == 'https') || ($this->scheme == 'http' && $this->authtype == 'ntlm') || ($this->scheme == 'http' && is_array($this->proxy) && $this->proxy['authtype'] == 'ntlm'))
				return 'curl';
			if (($this->scheme == 'http' || $this->scheme == 'ssl') && $this->authtype != 'ntlm' && (!is_array($this->proxy) || $this->proxy['authtype'] != 'ntlm'))
				return 'socket';
			return 'unknown';
		}


		function connect($connection_timeout=0,$response_timeout=30){
		  			  			  			  												$this->debug("connect connection_timeout $connection_timeout, response_timeout $response_timeout, scheme $this->scheme, host $this->host, port $this->port");
		  if ($this->io_method() == 'socket') {
			if (!is_array($this->proxy)) {
				$host = $this->host;
				$port = $this->port;
			} else {
				$host = $this->proxy['host'];
				$port = $this->proxy['port'];
			}

						if($this->persistentConnection && isset($this->fp) && is_resource($this->fp)){
				if (!feof($this->fp)) {
					$this->debug('Re-use persistent connection');
					return true;
				}
				fclose($this->fp);
				$this->debug('Closed persistent connection at EOF');
			}

						if ($this->scheme == 'ssl') {
				$host = 'ssl://' . $host;
			}
			$this->debug('calling fsockopen with host ' . $host . ' connection_timeout ' . $connection_timeout);

						if($connection_timeout > 0){
				$this->fp = @fsockopen( $host, $this->port, $this->errno, $this->error_str, $connection_timeout);
			} else {
				$this->fp = @fsockopen( $host, $this->port, $this->errno, $this->error_str);
			}

						if(!$this->fp) {
				$msg = 'Couldn\'t open socket connection to server ' . $this->url;
				if ($this->errno) {
					$msg .= ', Error ('.$this->errno.'): '.$this->error_str;
				} else {
					$msg .= ' prior to connect().  This is often a problem looking up the host name.';
				}
				$this->debug($msg);
				$this->setError($msg);
				return false;
			}

						$this->debug('set response timeout to ' . $response_timeout);
			socket_set_timeout( $this->fp, $response_timeout);

			$this->debug('socket connected');
			return true;
		  } else if ($this->io_method() == 'curl') {
			if (!extension_loaded('curl')) {
					$this->setError('The PHP cURL Extension is required for HTTPS or NLTM.  You will need to re-build or update your PHP to include cURL or change php.ini to load the PHP cURL extension.');
				return false;
			}
						if (defined('CURLOPT_CONNECTIONTIMEOUT'))
				$CURLOPT_CONNECTIONTIMEOUT = CURLOPT_CONNECTIONTIMEOUT;
			else
				$CURLOPT_CONNECTIONTIMEOUT = 78;
			if (defined('CURLOPT_HTTPAUTH'))
				$CURLOPT_HTTPAUTH = CURLOPT_HTTPAUTH;
			else
				$CURLOPT_HTTPAUTH = 107;
			if (defined('CURLOPT_PROXYAUTH'))
				$CURLOPT_PROXYAUTH = CURLOPT_PROXYAUTH;
			else
				$CURLOPT_PROXYAUTH = 111;
			if (defined('CURLAUTH_BASIC'))
				$CURLAUTH_BASIC = CURLAUTH_BASIC;
			else
				$CURLAUTH_BASIC = 1;
			if (defined('CURLAUTH_DIGEST'))
				$CURLAUTH_DIGEST = CURLAUTH_DIGEST;
			else
				$CURLAUTH_DIGEST = 2;
			if (defined('CURLAUTH_NTLM'))
				$CURLAUTH_NTLM = CURLAUTH_NTLM;
			else
				$CURLAUTH_NTLM = 8;

			$this->debug('connect using cURL');
						$this->ch = curl_init();
						$hostURL = ($this->port != '') ? "$this->scheme://$this->host:$this->port" : "$this->scheme://$this->host";
						$hostURL .= $this->path;
			$this->setCurlOption(CURLOPT_URL, $hostURL);
						if (ini_get('safe_mode') || ini_get('open_basedir')) {
				$this->debug('safe_mode or open_basedir set, so do not set CURLOPT_FOLLOWLOCATION');
				$this->debug('safe_mode = ');
				$this->appendDebug($this->varDump(ini_get('safe_mode')));
				$this->debug('open_basedir = ');
				$this->appendDebug($this->varDump(ini_get('open_basedir')));
			} else {
				$this->setCurlOption(CURLOPT_FOLLOWLOCATION, 1);
			}
						$this->setCurlOption(CURLOPT_HEADER, 1);
						$this->setCurlOption(CURLOPT_RETURNTRANSFER, 1);
															if ($this->persistentConnection) {
																								$this->persistentConnection = false;
				$this->setHeader('Connection', 'close');
			}
						if ($connection_timeout != 0) {
				$this->setCurlOption($CURLOPT_CONNECTIONTIMEOUT, $connection_timeout);
			}
			if ($response_timeout != 0) {
				$this->setCurlOption(CURLOPT_TIMEOUT, $response_timeout);
			}

			if ($this->scheme == 'https') {
				$this->debug('set cURL SSL verify options');
																				$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);
				$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);

								if ($this->authtype == 'certificate') {
					$this->debug('set cURL certificate options');
					if (isset($this->certRequest['cainfofile'])) {
						$this->setCurlOption(CURLOPT_CAINFO, $this->certRequest['cainfofile']);
					}
					if (isset($this->certRequest['verifypeer'])) {
						$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $this->certRequest['verifypeer']);
					} else {
						$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, 1);
					}
					if (isset($this->certRequest['verifyhost'])) {
						$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, $this->certRequest['verifyhost']);
					} else {
						$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 1);
					}
					if (isset($this->certRequest['sslcertfile'])) {
						$this->setCurlOption(CURLOPT_SSLCERT, $this->certRequest['sslcertfile']);
					}
					if (isset($this->certRequest['sslkeyfile'])) {
						$this->setCurlOption(CURLOPT_SSLKEY, $this->certRequest['sslkeyfile']);
					}
					if (isset($this->certRequest['passphrase'])) {
						$this->setCurlOption(CURLOPT_SSLKEYPASSWD, $this->certRequest['passphrase']);
					}
					if (isset($this->certRequest['certpassword'])) {
						$this->setCurlOption(CURLOPT_SSLCERTPASSWD, $this->certRequest['certpassword']);
					}
				}
			}
			if ($this->authtype && ($this->authtype != 'certificate')) {
				if ($this->username) {
					$this->debug('set cURL username/password');
					$this->setCurlOption(CURLOPT_USERPWD, "$this->username:$this->password");
				}
				if ($this->authtype == 'basic') {
					$this->debug('set cURL for Basic authentication');
					$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_BASIC);
				}
				if ($this->authtype == 'digest') {
					$this->debug('set cURL for digest authentication');
					$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_DIGEST);
				}
				if ($this->authtype == 'ntlm') {
					$this->debug('set cURL for NTLM authentication');
					$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_NTLM);
				}
			}
			if (is_array($this->proxy)) {
				$this->debug('set cURL proxy options');
				if ($this->proxy['port'] != '') {
					$this->setCurlOption(CURLOPT_PROXY, $this->proxy['host'].':'.$this->proxy['port']);
				} else {
					$this->setCurlOption(CURLOPT_PROXY, $this->proxy['host']);
				}
				if ($this->proxy['username'] || $this->proxy['password']) {
					$this->debug('set cURL proxy authentication options');
					$this->setCurlOption(CURLOPT_PROXYUSERPWD, $this->proxy['username'].':'.$this->proxy['password']);
					if ($this->proxy['authtype'] == 'basic') {
						$this->setCurlOption($CURLOPT_PROXYAUTH, $CURLAUTH_BASIC);
					}
					if ($this->proxy['authtype'] == 'ntlm') {
						$this->setCurlOption($CURLOPT_PROXYAUTH, $CURLAUTH_NTLM);
					}
				}
			}
			$this->debug('cURL connection set up');
			return true;
		  } else {
			$this->setError('Unknown scheme ' . $this->scheme);
			$this->debug('Unknown scheme ' . $this->scheme);
			return false;
		  }
		}


		function send($data, $timeout=0, $response_timeout=30, $cookies=NULL) {

			$this->debug('entered send() with data of length: '.strlen($data));

			$this->tryagain = true;
			$tries = 0;
			while ($this->tryagain) {
				$this->tryagain = false;
				if ($tries++ < 2) {
										if (!$this->connect($timeout, $response_timeout)){
						return false;
					}

										if (!$this->sendRequest($data, $cookies)){
						return false;
					}

										$respdata = $this->getResponse();
				} else {
					$this->setError("Too many tries to get an OK response ($this->response_status_line)");
				}
			}
			$this->debug('end of send()');
			return $respdata;
		}



		function sendHTTPS($data, $timeout=0, $response_timeout=30, $cookies) {
			return $this->send($data, $timeout, $response_timeout, $cookies);
		}


		function setCredentials($username, $password, $authtype = 'basic', $digestRequest = array(), $certRequest = array()) {
			$this->debug("setCredentials username=$username authtype=$authtype digestRequest=");
			$this->appendDebug($this->varDump($digestRequest));
			$this->debug("certRequest=");
			$this->appendDebug($this->varDump($certRequest));
						if ($authtype == 'basic') {
				$this->setHeader('Authorization', 'Basic '.base64_encode(str_replace(':','',$username).':'.$password));
			} elseif ($authtype == 'digest') {
				if (isset($digestRequest['nonce'])) {
					$digestRequest['nc'] = isset($digestRequest['nc']) ? $digestRequest['nc']++ : 1;


										$A1 = $username. ':' . (isset($digestRequest['realm']) ? $digestRequest['realm'] : '') . ':' . $password;

										$HA1 = md5($A1);

										$A2 = $this->request_method . ':' . $this->digest_uri;

										$HA2 =  md5($A2);


					$unhashedDigest = '';
					$nonce = isset($digestRequest['nonce']) ? $digestRequest['nonce'] : '';
					$cnonce = $nonce;
					if ($digestRequest['qop'] != '') {
						$unhashedDigest = $HA1 . ':' . $nonce . ':' . sprintf("%08d", $digestRequest['nc']) . ':' . $cnonce . ':' . $digestRequest['qop'] . ':' . $HA2;
					} else {
						$unhashedDigest = $HA1 . ':' . $nonce . ':' . $HA2;
					}

					$hashedDigest = md5($unhashedDigest);

					$opaque = '';
					if (isset($digestRequest['opaque'])) {
						$opaque = ', opaque="' . $digestRequest['opaque'] . '"';
					}

					$this->setHeader('Authorization', 'Digest username="' . $username . '", realm="' . $digestRequest['realm'] . '", nonce="' . $nonce . '", uri="' . $this->digest_uri . $opaque . '", cnonce="' . $cnonce . '", nc=' . sprintf("%08x", $digestRequest['nc']) . ', qop="' . $digestRequest['qop'] . '", response="' . $hashedDigest . '"');
				}
			} elseif ($authtype == 'certificate') {
				$this->certRequest = $certRequest;
				$this->debug('Authorization header not set for certificate');
			} elseif ($authtype == 'ntlm') {
								$this->debug('Authorization header not set for ntlm');
			}
			$this->username = $username;
			$this->password = $password;
			$this->authtype = $authtype;
			$this->digestRequest = $digestRequest;
		}


		function setSOAPAction($soapaction) {
			$this->setHeader('SOAPAction', '"' . $soapaction . '"');
		}


		function setEncoding($enc='gzip, deflate') {
			if (function_exists('gzdeflate')) {
				$this->protocol_version = '1.1';
				$this->setHeader('Accept-Encoding', $enc);
				if (!isset($this->outgoing_headers['Connection'])) {
					$this->setHeader('Connection', 'close');
					$this->persistentConnection = false;
				}
												$this->encoding = $enc;
			}
		}


		function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 'basic') {
			if ($proxyhost) {
				$this->proxy = array(
					'host' => $proxyhost,
					'port' => $proxyport,
					'username' => $proxyusername,
					'password' => $proxypassword,
					'authtype' => $proxyauthtype
				);
				if ($proxyusername != '' && $proxypassword != '' && $proxyauthtype = 'basic') {
					$this->setHeader('Proxy-Authorization', ' Basic '.base64_encode($proxyusername.':'.$proxypassword));
				}
			} else {
				$this->debug('remove proxy');
				$proxy = null;
				unsetHeader('Proxy-Authorization');
			}
		}



		function isSkippableCurlHeader(&$data) {
			$skipHeaders = array(	'HTTP/1.1 100',
									'HTTP/1.0 301',
									'HTTP/1.1 301',
									'HTTP/1.0 302',
									'HTTP/1.1 302',
									'HTTP/1.0 401',
									'HTTP/1.1 401',
									'HTTP/1.0 200 Connection established');
			foreach ($skipHeaders as $hd) {
				$prefix = substr($data, 0, strlen($hd));
				if ($prefix == $hd) return true;
			}

			return false;
		}


		function decodeChunked($buffer, $lb){
						$length = 0;
			$new = '';

									$chunkend = strpos($buffer, $lb);
			if ($chunkend == FALSE) {
				$this->debug('no linebreak found in decodeChunked');
				return $new;
			}
			$temp = substr($buffer,0,$chunkend);
			$chunk_size = hexdec( trim($temp) );
			$chunkstart = $chunkend + strlen($lb);
						while ($chunk_size > 0) {
				$this->debug("chunkstart: $chunkstart chunk_size: $chunk_size");
				$chunkend = strpos( $buffer, $lb, $chunkstart + $chunk_size);

							  	if ($chunkend == FALSE) {
			  	    $chunk = substr($buffer,$chunkstart);
								    	$new .= $chunk;
			  	    $length += strlen($chunk);
			  	    break;
				}

			  				  	$chunk = substr($buffer,$chunkstart,$chunkend-$chunkstart);
			  				  	$new .= $chunk;
			  				  	$length += strlen($chunk);
			  				  	$chunkstart = $chunkend + strlen($lb);

			  	$chunkend = strpos($buffer, $lb, $chunkstart) + strlen($lb);
				if ($chunkend == FALSE) {
					break; 				}
				$temp = substr($buffer,$chunkstart,$chunkend-$chunkstart);
				$chunk_size = hexdec( trim($temp) );
				$chunkstart = $chunkend;
			}
			return $new;
		}


		function buildPayload($data, $cookie_str = '') {

						if ($this->request_method != 'GET') {
				$this->setHeader('Content-Length', strlen($data));
			}

						if ($this->proxy) {
				$uri = $this->url;
			} else {
				$uri = $this->uri;
			}
			$req = "$this->request_method $uri HTTP/$this->protocol_version";
			$this->debug("HTTP request: $req");
			$this->outgoing_payload = "$req\r\n";

						foreach($this->outgoing_headers as $k => $v){
				$hdr = $k.': '.$v;
				$this->debug("HTTP header: $hdr");
				$this->outgoing_payload .= "$hdr\r\n";
			}

						if ($cookie_str != '') {
				$hdr = 'Cookie: '.$cookie_str;
				$this->debug("HTTP header: $hdr");
				$this->outgoing_payload .= "$hdr\r\n";
			}

						$this->outgoing_payload .= "\r\n";

						$this->outgoing_payload .= $data;
		}


		function sendRequest($data, $cookies = NULL) {
						$cookie_str = $this->getCookiesForRequest($cookies, (($this->scheme == 'ssl') || ($this->scheme == 'https')));

						$this->buildPayload($data, $cookie_str);

		  if ($this->io_method() == 'socket') {
						if(!fputs($this->fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
				$this->setError('couldn\'t write message data to socket');
				$this->debug('couldn\'t write message data to socket');
				return false;
			}
			$this->debug('wrote data to socket, length = ' . strlen($this->outgoing_payload));
			return true;
		  } else if ($this->io_method() == 'curl') {
																		$curl_headers = array();
			foreach($this->outgoing_headers as $k => $v){
				if ($k == 'Connection' || $k == 'Content-Length' || $k == 'Host' || $k == 'Authorization' || $k == 'Proxy-Authorization') {
					$this->debug("Skip cURL header $k: $v");
				} else {
					$curl_headers[] = "$k: $v";
				}
			}
			if ($cookie_str != '') {
				$curl_headers[] = 'Cookie: ' . $cookie_str;
			}
			$this->setCurlOption(CURLOPT_HTTPHEADER, $curl_headers);
			$this->debug('set cURL HTTP headers');
			if ($this->request_method == "POST") {
		  		$this->setCurlOption(CURLOPT_POST, 1);
		  		$this->setCurlOption(CURLOPT_POSTFIELDS, $data);
				$this->debug('set cURL POST data');
		  	} else {
		  	}
						foreach ($this->ch_options as $key => $val) {
				$this->setCurlOption($key, $val);
			}

			$this->debug('set cURL payload');
			return true;
		  }
		}


		function getResponse(){
			$this->incoming_payload = '';

		  if ($this->io_method() == 'socket') {
		    		    $data = '';
		    while (!isset($lb)){

								if(feof($this->fp)) {
					$this->incoming_payload = $data;
					$this->debug('found no headers before EOF after length ' . strlen($data));
					$this->debug("received before EOF:\n" . $data);
					$this->setError('server failed to send headers');
					return false;
				}

				$tmp = fgets($this->fp, 256);
				$tmplen = strlen($tmp);
				$this->debug("read line of $tmplen bytes: " . trim($tmp));

				if ($tmplen == 0) {
					$this->incoming_payload = $data;
					$this->debug('socket read of headers timed out after length ' . strlen($data));
					$this->debug("read before timeout: " . $data);
					$this->setError('socket read of headers timed out');
					return false;
				}

				$data .= $tmp;
				$pos = strpos($data,"\r\n\r\n");
				if($pos > 1){
					$lb = "\r\n";
				} else {
					$pos = strpos($data,"\n\n");
					if($pos > 1){
						$lb = "\n";
					}
				}
								if (isset($lb) && preg_match('/^HTTP\/1.1 100/',$data)) {
					unset($lb);
					$data = '';
				}			}
						$this->incoming_payload .= $data;
			$this->debug('found end of headers after length ' . strlen($data));
						$header_data = trim(substr($data,0,$pos));
			$header_array = explode($lb,$header_data);
			$this->incoming_headers = array();
			$this->incoming_cookies = array();
			foreach($header_array as $header_line){
				$arr = explode(':',$header_line, 2);
				if(count($arr) > 1){
					$header_name = strtolower(trim($arr[0]));
					$this->incoming_headers[$header_name] = trim($arr[1]);
					if ($header_name == 'set-cookie') {
												$cookie = $this->parseCookie(trim($arr[1]));
						if ($cookie) {
							$this->incoming_cookies[] = $cookie;
							$this->debug('found cookie: ' . $cookie['name'] . ' = ' . $cookie['value']);
						} else {
							$this->debug('did not find cookie in ' . trim($arr[1]));
						}
	    			}
				} else if (isset($header_name)) {
										$this->incoming_headers[$header_name] .= $lb . ' ' . $header_line;
				}
			}

						if (isset($this->incoming_headers['transfer-encoding']) && strtolower($this->incoming_headers['transfer-encoding']) == 'chunked') {
				$content_length =  2147483647;					$chunked = true;
				$this->debug("want to read chunked content");
			} elseif (isset($this->incoming_headers['content-length'])) {
				$content_length = $this->incoming_headers['content-length'];
				$chunked = false;
				$this->debug("want to read content of length $content_length");
			} else {
				$content_length =  2147483647;
				$chunked = false;
				$this->debug("want to read content to EOF");
			}
			$data = '';
			do {
				if ($chunked) {
					$tmp = fgets($this->fp, 256);
					$tmplen = strlen($tmp);
					$this->debug("read chunk line of $tmplen bytes");
					if ($tmplen == 0) {
						$this->incoming_payload = $data;
						$this->debug('socket read of chunk length timed out after length ' . strlen($data));
						$this->debug("read before timeout:\n" . $data);
						$this->setError('socket read of chunk length timed out');
						return false;
					}
					$content_length = hexdec(trim($tmp));
					$this->debug("chunk length $content_length");
				}
				$strlen = 0;
			    while (($strlen < $content_length) && (!feof($this->fp))) {
			    	$readlen = min(8192, $content_length - $strlen);
					$tmp = fread($this->fp, $readlen);
					$tmplen = strlen($tmp);
					$this->debug("read buffer of $tmplen bytes");
					if (($tmplen == 0) && (!feof($this->fp))) {
						$this->incoming_payload = $data;
						$this->debug('socket read of body timed out after length ' . strlen($data));
						$this->debug("read before timeout:\n" . $data);
						$this->setError('socket read of body timed out');
						return false;
					}
					$strlen += $tmplen;
					$data .= $tmp;
				}
				if ($chunked && ($content_length > 0)) {
					$tmp = fgets($this->fp, 256);
					$tmplen = strlen($tmp);
					$this->debug("read chunk terminator of $tmplen bytes");
					if ($tmplen == 0) {
						$this->incoming_payload = $data;
						$this->debug('socket read of chunk terminator timed out after length ' . strlen($data));
						$this->debug("read before timeout:\n" . $data);
						$this->setError('socket read of chunk terminator timed out');
						return false;
					}
				}
			} while ($chunked && ($content_length > 0) && (!feof($this->fp)));
			if (feof($this->fp)) {
				$this->debug('read to EOF');
			}
			$this->debug('read body of length ' . strlen($data));
			$this->incoming_payload .= $data;
			$this->debug('received a total of '.strlen($this->incoming_payload).' bytes of data from server');

						if(
				(isset($this->incoming_headers['connection']) && strtolower($this->incoming_headers['connection']) == 'close') ||
				(! $this->persistentConnection) || feof($this->fp)){
				fclose($this->fp);
				$this->fp = false;
				$this->debug('closed socket');
			}

						if($this->incoming_payload == ''){
				$this->setError('no response from server');
				return false;
			}


		  } else if ($this->io_method() == 'curl') {
						$this->debug('send and receive with cURL');
			$this->incoming_payload = curl_exec($this->ch);
			$data = $this->incoming_payload;

	        $cErr = curl_error($this->ch);
			if ($cErr != '') {
	        	$err = 'cURL ERROR: '.curl_errno($this->ch).': '.$cErr.'<br>';
	        					foreach(curl_getinfo($this->ch) as $k => $v){
					$err .= "$k: $v<br>";
				}
				$this->debug($err);
				$this->setError($err);
				curl_close($this->ch);
		    	return false;
			} else {
															}
						$this->debug('No cURL error, closing cURL');
			curl_close($this->ch);

						$savedata = $data;
			while ($this->isSkippableCurlHeader($data)) {
				$this->debug("Found HTTP header to skip");
				if ($pos = strpos($data,"\r\n\r\n")) {
					$data = ltrim(substr($data,$pos));
				} elseif($pos = strpos($data,"\n\n") ) {
					$data = ltrim(substr($data,$pos));
				}
			}

			if ($data == '') {
								$data = $savedata;
				while (preg_match('/^HTTP\/1.1 100/',$data)) {
					if ($pos = strpos($data,"\r\n\r\n")) {
						$data = ltrim(substr($data,$pos));
					} elseif($pos = strpos($data,"\n\n") ) {
						$data = ltrim(substr($data,$pos));
					}
				}
			}

						if ($pos = strpos($data,"\r\n\r\n")) {
				$lb = "\r\n";
			} elseif( $pos = strpos($data,"\n\n")) {
				$lb = "\n";
			} else {
				$this->debug('no proper separation of headers and document');
				$this->setError('no proper separation of headers and document');
				return false;
			}
			$header_data = trim(substr($data,0,$pos));
			$header_array = explode($lb,$header_data);
			$data = ltrim(substr($data,$pos));
			$this->debug('found proper separation of headers and document');
			$this->debug('cleaned data, stringlen: '.strlen($data));
						foreach ($header_array as $header_line) {
				$arr = explode(':',$header_line,2);
				if(count($arr) > 1){
					$header_name = strtolower(trim($arr[0]));
					$this->incoming_headers[$header_name] = trim($arr[1]);
					if ($header_name == 'set-cookie') {
												$cookie = $this->parseCookie(trim($arr[1]));
						if ($cookie) {
							$this->incoming_cookies[] = $cookie;
							$this->debug('found cookie: ' . $cookie['name'] . ' = ' . $cookie['value']);
						} else {
							$this->debug('did not find cookie in ' . trim($arr[1]));
						}
	    			}
				} else if (isset($header_name)) {
										$this->incoming_headers[$header_name] .= $lb . ' ' . $header_line;
				}
			}
		  }

			$this->response_status_line = $header_array[0];
			$arr = explode(' ', $this->response_status_line, 3);
			$http_version = $arr[0];
			$http_status = intval($arr[1]);
			$http_reason = count($arr) > 2 ? $arr[2] : '';

	 			 		if (isset($this->incoming_headers['location']) && ($http_status == 301 || $http_status == 302)) {
	 			$this->debug("Got $http_status $http_reason with Location: " . $this->incoming_headers['location']);
	 			$this->setURL($this->incoming_headers['location']);
				$this->tryagain = true;
				return false;
			}

	 			 		if (isset($this->incoming_headers['www-authenticate']) && $http_status == 401) {
	 			$this->debug("Got 401 $http_reason with WWW-Authenticate: " . $this->incoming_headers['www-authenticate']);
	 			if (strstr($this->incoming_headers['www-authenticate'], "Digest ")) {
	 				$this->debug('Server wants digest authentication');
	 					 				$digestString = str_replace('Digest ', '', $this->incoming_headers['www-authenticate']);

	 					 				$digestElements = explode(',', $digestString);
	 				foreach ($digestElements as $val) {
	 					$tempElement = explode('=', trim($val), 2);
	 					$digestRequest[$tempElement[0]] = str_replace("\"", '', $tempElement[1]);
	 				}

						 				if (isset($digestRequest['nonce'])) {
	 					$this->setCredentials($this->username, $this->password, 'digest', $digestRequest);
	 					$this->tryagain = true;
	 					return false;
	 				}
	 			}
				$this->debug('HTTP authentication failed');
				$this->setError('HTTP authentication failed');
				return false;
	 		}

			if (
				($http_status >= 300 && $http_status <= 307) ||
				($http_status >= 400 && $http_status <= 417) ||
				($http_status >= 501 && $http_status <= 505)
			   ) {
				$this->setError("Unsupported HTTP response status $http_status $http_reason (soapclient->response has contents of the response)");
				return false;
			}

						if(isset($this->incoming_headers['content-encoding']) && $this->incoming_headers['content-encoding'] != ''){
				if(strtolower($this->incoming_headers['content-encoding']) == 'deflate' || strtolower($this->incoming_headers['content-encoding']) == 'gzip'){
	    				    			if(function_exists('gzinflate')){
																								$this->debug('The gzinflate function exists');
						$datalen = strlen($data);
						if ($this->incoming_headers['content-encoding'] == 'deflate') {
							if ($degzdata = @gzinflate($data)) {
		    					$data = $degzdata;
		    					$this->debug('The payload has been inflated to ' . strlen($data) . ' bytes');
		    					if (strlen($data) < $datalen) {
		    									    					$this->debug('The inflated payload is smaller than the gzipped one; try again');
									if ($degzdata = @gzinflate($data)) {
				    					$data = $degzdata;
				    					$this->debug('The payload has been inflated again to ' . strlen($data) . ' bytes');
									}
		    					}
		    				} else {
		    					$this->debug('Error using gzinflate to inflate the payload');
		    					$this->setError('Error using gzinflate to inflate the payload');
		    				}
						} elseif ($this->incoming_headers['content-encoding'] == 'gzip') {
							if ($degzdata = @gzinflate(substr($data, 10))) {									$data = $degzdata;
		    					$this->debug('The payload has been un-gzipped to ' . strlen($data) . ' bytes');
		    					if (strlen($data) < $datalen) {
		    									    					$this->debug('The un-gzipped payload is smaller than the gzipped one; try again');
									if ($degzdata = @gzinflate(substr($data, 10))) {
				    					$data = $degzdata;
				    					$this->debug('The payload has been un-gzipped again to ' . strlen($data) . ' bytes');
									}
		    					}
		    				} else {
		    					$this->debug('Error using gzinflate to un-gzip the payload');
								$this->setError('Error using gzinflate to un-gzip the payload');
		    				}
						}
																								$this->incoming_payload = $header_data.$lb.$lb.$data;
	    			} else {
						$this->debug('The server sent compressed data. Your php install must have the Zlib extension compiled in to support this.');
						$this->setError('The server sent compressed data. Your php install must have the Zlib extension compiled in to support this.');
					}
				} else {
					$this->debug('Unsupported Content-Encoding ' . $this->incoming_headers['content-encoding']);
					$this->setError('Unsupported Content-Encoding ' . $this->incoming_headers['content-encoding']);
				}
			} else {
				$this->debug('No Content-Encoding header');
			}

			if(strlen($data) == 0){
				$this->debug('no data after headers!');
				$this->setError('no data present after HTTP headers');
				return false;
			}

			return $data;
		}


		function setContentType($type, $charset = false) {
			$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
		}


		function usePersistentConnection(){
			if (isset($this->outgoing_headers['Accept-Encoding'])) {
				return false;
			}
			$this->protocol_version = '1.1';
			$this->persistentConnection = true;
			$this->setHeader('Connection', 'Keep-Alive');
			return true;
		}



		function parseCookie($cookie_str) {
			$cookie_str = str_replace('; ', ';', $cookie_str) . ';';
			$data = preg_split('/;/', $cookie_str);
			$value_str = $data[0];

			$cookie_param = 'domain=';
			$start = strpos($cookie_str, $cookie_param);
			if ($start > 0) {
				$domain = substr($cookie_str, $start + strlen($cookie_param));
				$domain = substr($domain, 0, strpos($domain, ';'));
			} else {
				$domain = '';
			}

			$cookie_param = 'expires=';
			$start = strpos($cookie_str, $cookie_param);
			if ($start > 0) {
				$expires = substr($cookie_str, $start + strlen($cookie_param));
				$expires = substr($expires, 0, strpos($expires, ';'));
			} else {
				$expires = '';
			}

			$cookie_param = 'path=';
			$start = strpos($cookie_str, $cookie_param);
			if ( $start > 0 ) {
				$path = substr($cookie_str, $start + strlen($cookie_param));
				$path = substr($path, 0, strpos($path, ';'));
			} else {
				$path = '/';
			}

			$cookie_param = ';secure;';
			if (strpos($cookie_str, $cookie_param) !== FALSE) {
				$secure = true;
			} else {
				$secure = false;
			}

			$sep_pos = strpos($value_str, '=');

			if ($sep_pos) {
				$name = substr($value_str, 0, $sep_pos);
				$value = substr($value_str, $sep_pos + 1);
				$cookie= array(	'name' => $name,
				                'value' => $value,
								'domain' => $domain,
								'path' => $path,
								'expires' => $expires,
								'secure' => $secure
								);
				return $cookie;
			}
			return false;
		}


		function getCookiesForRequest($cookies, $secure=false) {
			$cookie_str = '';
			if ((! is_null($cookies)) && (is_array($cookies))) {
				foreach ($cookies as $cookie) {
					if (! is_array($cookie)) {
						continue;
					}
		    		$this->debug("check cookie for validity: ".$cookie['name'].'='.$cookie['value']);
					if ((isset($cookie['expires'])) && (! empty($cookie['expires']))) {
						if (strtotime($cookie['expires']) <= time()) {
							$this->debug('cookie has expired');
							continue;
						}
					}
					if ((isset($cookie['domain'])) && (! empty($cookie['domain']))) {
						$domain = preg_quote($cookie['domain']);
						if (! preg_match("'.*$domain$'i", $this->host)) {
							$this->debug('cookie has different domain');
							continue;
						}
					}
					if ((isset($cookie['path'])) && (! empty($cookie['path']))) {
						$path = preg_quote($cookie['path']);
						if (! preg_match("'^$path.*'i", $this->path)) {
							$this->debug('cookie is for a different path');
							continue;
						}
					}
					if ((! $secure) && (isset($cookie['secure'])) && ($cookie['secure'])) {
						$this->debug('cookie is secure, transport is not');
						continue;
					}
					$cookie_str .= $cookie['name'] . '=' . $cookie['value'] . '; ';
		    		$this->debug('add cookie to Cookie-String: ' . $cookie['name'] . '=' . $cookie['value']);
				}
			}
			return $cookie_str;
	  }
	}

	class wsdl extends nusoap_base {
			    var $wsdl;
	    	    var $schemas = array();
	    var $currentSchema;
	    var $message = array();
	    var $complexTypes = array();
	    var $messages = array();
	    var $currentMessage;
	    var $currentOperation;
	    var $portTypes = array();
	    var $currentPortType;
	    var $bindings = array();
	    var $currentBinding;
	    var $ports = array();
	    var $currentPort;
	    var $opData = array();
	    var $status = '';
	    var $documentation = false;
	    var $endpoint = '';
	    	    var $import = array();
	    	    var $parser;
	    var $position = 0;
	    var $depth = 0;
	    var $depth_array = array();
				var $proxyhost = '';
	    var $proxyport = '';
		var $proxyusername = '';
		var $proxypassword = '';
		var $timeout = 0;
		var $response_timeout = 30;
		var $curl_options = array();			var $use_curl = false;							var $username = '';						var $password = '';						var $authtype = '';						var $certRequest = array();

	    function wsdl($wsdl = '',$proxyhost=false,$proxyport=false,$proxyusername=false,$proxypassword=false,$timeout=0,$response_timeout=30,$curl_options=null,$use_curl=false){
			parent::nusoap_base();
			$this->debug("ctor wsdl=$wsdl timeout=$timeout response_timeout=$response_timeout");
	        $this->proxyhost = $proxyhost;
	        $this->proxyport = $proxyport;
			$this->proxyusername = $proxyusername;
			$this->proxypassword = $proxypassword;
			$this->timeout = $timeout;
			$this->response_timeout = $response_timeout;
			if (is_array($curl_options))
				$this->curl_options = $curl_options;
			$this->use_curl = $use_curl;
			$this->fetchWSDL($wsdl);
	    }


		function fetchWSDL($wsdl) {
			$this->debug("parse and process WSDL path=$wsdl");
			$this->wsdl = $wsdl;
	        	        if ($this->wsdl != "") {
	            $this->parseWSDL($this->wsdl);
	        }
	        	        	    	$imported_urls = array();
	    	$imported = 1;
	    	while ($imported > 0) {
	    		$imported = 0;
	    			    		foreach ($this->schemas as $ns => $list) {
	    			foreach ($list as $xs) {
						$wsdlparts = parse_url($this->wsdl);				            foreach ($xs->imports as $ns2 => $list2) {
			                for ($ii = 0; $ii < count($list2); $ii++) {
			                	if (! $list2[$ii]['loaded']) {
			                		$this->schemas[$ns]->imports[$ns2][$ii]['loaded'] = true;
			                		$url = $list2[$ii]['location'];
									if ($url != '') {
										$urlparts = parse_url($url);
										if (!isset($urlparts['host'])) {
											$url = $wsdlparts['scheme'] . '://' . $wsdlparts['host'] . (isset($wsdlparts['port']) ? ':' .$wsdlparts['port'] : '') .
													substr($wsdlparts['path'],0,strrpos($wsdlparts['path'],'/') + 1) .$urlparts['path'];
										}
										if (! in_array($url, $imported_urls)) {
						                	$this->parseWSDL($url);
					                		$imported++;
					                		$imported_urls[] = $url;
					                	}
									} else {
										$this->debug("Unexpected scenario: empty URL for unloaded import");
									}
								}
							}
			            }
	    			}
	    		}
	    						$wsdlparts = parse_url($this->wsdl);		            foreach ($this->import as $ns => $list) {
	                for ($ii = 0; $ii < count($list); $ii++) {
	                	if (! $list[$ii]['loaded']) {
	                		$this->import[$ns][$ii]['loaded'] = true;
	                		$url = $list[$ii]['location'];
							if ($url != '') {
								$urlparts = parse_url($url);
								if (!isset($urlparts['host'])) {
									$url = $wsdlparts['scheme'] . '://' . $wsdlparts['host'] . (isset($wsdlparts['port']) ? ':' . $wsdlparts['port'] : '') .
											substr($wsdlparts['path'],0,strrpos($wsdlparts['path'],'/') + 1) .$urlparts['path'];
								}
								if (! in_array($url, $imported_urls)) {
				                	$this->parseWSDL($url);
			                		$imported++;
			                		$imported_urls[] = $url;
			                	}
							} else {
								$this->debug("Unexpected scenario: empty URL for unloaded import");
							}
						}
					}
	            }
			}
	        	        foreach($this->bindings as $binding => $bindingData) {
	            if (isset($bindingData['operations']) && is_array($bindingData['operations'])) {
	                foreach($bindingData['operations'] as $operation => $data) {
	                    $this->debug('post-parse data gathering for ' . $operation);
	                    $this->bindings[$binding]['operations'][$operation]['input'] =
							isset($this->bindings[$binding]['operations'][$operation]['input']) ?
							array_merge($this->bindings[$binding]['operations'][$operation]['input'], $this->portTypes[ $bindingData['portType'] ][$operation]['input']) :
							$this->portTypes[ $bindingData['portType'] ][$operation]['input'];
	                    $this->bindings[$binding]['operations'][$operation]['output'] =
							isset($this->bindings[$binding]['operations'][$operation]['output']) ?
							array_merge($this->bindings[$binding]['operations'][$operation]['output'], $this->portTypes[ $bindingData['portType'] ][$operation]['output']) :
							$this->portTypes[ $bindingData['portType'] ][$operation]['output'];
	                    if(isset($this->messages[ $this->bindings[$binding]['operations'][$operation]['input']['message'] ])){
							$this->bindings[$binding]['operations'][$operation]['input']['parts'] = $this->messages[ $this->bindings[$binding]['operations'][$operation]['input']['message'] ];
						}
						if(isset($this->messages[ $this->bindings[$binding]['operations'][$operation]['output']['message'] ])){
	                   		$this->bindings[$binding]['operations'][$operation]['output']['parts'] = $this->messages[ $this->bindings[$binding]['operations'][$operation]['output']['message'] ];
	                    }
	                    						if (isset($bindingData['style']) && !isset($this->bindings[$binding]['operations'][$operation]['style'])) {
	                        $this->bindings[$binding]['operations'][$operation]['style'] = $bindingData['style'];
	                    }
	                    $this->bindings[$binding]['operations'][$operation]['transport'] = isset($bindingData['transport']) ? $bindingData['transport'] : '';
	                    $this->bindings[$binding]['operations'][$operation]['documentation'] = isset($this->portTypes[ $bindingData['portType'] ][$operation]['documentation']) ? $this->portTypes[ $bindingData['portType'] ][$operation]['documentation'] : '';
	                    $this->bindings[$binding]['operations'][$operation]['endpoint'] = isset($bindingData['endpoint']) ? $bindingData['endpoint'] : '';
	                }
	            }
	        }
		}


	    function parseWSDL($wsdl = '') {
			$this->debug("parse WSDL at path=$wsdl");

	        if ($wsdl == '') {
	            $this->debug('no wsdl passed to parseWSDL()!!');
	            $this->setError('no wsdl passed to parseWSDL()!!');
	            return false;
	        }

	        	        $wsdl_props = parse_url($wsdl);

	        if (isset($wsdl_props['scheme']) && ($wsdl_props['scheme'] == 'http' || $wsdl_props['scheme'] == 'https')) {
	            $this->debug('getting WSDL http(s) URL ' . $wsdl);
	        			        $tr = new soap_transport_http($wsdl, $this->curl_options, $this->use_curl);
				$tr->request_method = 'GET';
				$tr->useSOAPAction = false;
				if($this->proxyhost && $this->proxyport){
					$tr->setProxy($this->proxyhost,$this->proxyport,$this->proxyusername,$this->proxypassword);
				}
				if ($this->authtype != '') {
					$tr->setCredentials($this->username, $this->password, $this->authtype, array(), $this->certRequest);
				}
				$tr->setEncoding('gzip, deflate');
				$wsdl_string = $tr->send('', $this->timeout, $this->response_timeout);
												$this->appendDebug($tr->getDebug());
								if($err = $tr->getError() ){
					$errstr = 'Getting ' . $wsdl . ' - HTTP ERROR: '.$err;
					$this->debug($errstr);
		            $this->setError($errstr);
					unset($tr);
		            return false;
				}
				unset($tr);
				$this->debug("got WSDL URL");
	        } else {
	            	        	if (isset($wsdl_props['scheme']) && ($wsdl_props['scheme'] == 'file') && isset($wsdl_props['path'])) {
	        		$path = isset($wsdl_props['host']) ? ($wsdl_props['host'] . ':' . $wsdl_props['path']) : $wsdl_props['path'];
	        	} else {
	        		$path = $wsdl;
	        	}
	            $this->debug('getting WSDL file ' . $path);
	            if ($fp = @fopen($path, 'r')) {
	                $wsdl_string = '';
	                while ($data = fread($fp, 32768)) {
	                    $wsdl_string .= $data;
	                }
	                fclose($fp);
	            } else {
	            	$errstr = "Bad path to WSDL file $path";
	            	$this->debug($errstr);
	                $this->setError($errstr);
	                return false;
	            }
	        }
	        $this->debug('Parse WSDL');
	        	        	        $this->parser = xml_parser_create();
	        	        	        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
	        	        xml_set_object($this->parser, $this);
	        	        xml_set_element_handler($this->parser, 'start_element', 'end_element');
	        xml_set_character_data_handler($this->parser, 'character_data');
	        	        if (!xml_parse($this->parser, $wsdl_string, true)) {
	            	            $errstr = sprintf(
					'XML error parsing WSDL from %s on line %d: %s',
					$wsdl,
	                xml_get_current_line_number($this->parser),
	                xml_error_string(xml_get_error_code($this->parser))
	                );
	            $this->debug($errstr);
				$this->debug("XML payload:\n" . $wsdl_string);
	            $this->setError($errstr);
	            return false;
	        }
				        xml_parser_free($this->parser);
	        $this->debug('Parsing WSDL done');
						if($this->getError()){
				return false;
			}
	        return true;
	    }


	    function start_element($parser, $name, $attrs)
	    {
	        if ($this->status == 'schema') {
	            $this->currentSchema->schemaStartElement($parser, $name, $attrs);
	            $this->appendDebug($this->currentSchema->getDebug());
	            $this->currentSchema->clearDebug();
	        } elseif (preg_match('/schema$/', $name)) {
	        	$this->debug('Parsing WSDL schema');
	            	            $this->status = 'schema';
	            $this->currentSchema = new nusoap_xmlschema('', '', $this->namespaces);
	            $this->currentSchema->schemaStartElement($parser, $name, $attrs);
	            $this->appendDebug($this->currentSchema->getDebug());
	            $this->currentSchema->clearDebug();
	        } else {
	            	            $pos = $this->position++;
	            $depth = $this->depth++;
	            	            $this->depth_array[$depth] = $pos;
	            $this->message[$pos] = array('cdata' => '');
	            	            if (count($attrs) > 0) {
						                foreach($attrs as $k => $v) {
	                    if (preg_match('/^xmlns/',$k)) {
	                        if ($ns_prefix = substr(strrchr($k, ':'), 1)) {
	                            $this->namespaces[$ns_prefix] = $v;
	                        } else {
	                            $this->namespaces['ns' . (count($this->namespaces) + 1)] = $v;
	                        }
	                        if ($v == 'http://www.w3.org/2001/XMLSchema' || $v == 'http://www.w3.org/1999/XMLSchema' || $v == 'http://www.w3.org/2000/10/XMLSchema') {
	                            $this->XMLSchemaVersion = $v;
	                            $this->namespaces['xsi'] = $v . '-instance';
	                        }
	                    }
	                }
	                	                foreach($attrs as $k => $v) {
	                    $k = strpos($k, ':') ? $this->expandQname($k) : $k;
	                    if ($k != 'location' && $k != 'soapAction' && $k != 'namespace') {
	                        $v = strpos($v, ':') ? $this->expandQname($v) : $v;
	                    }
	                    $eAttrs[$k] = $v;
	                }
	                $attrs = $eAttrs;
	            } else {
	                $attrs = array();
	            }
	            	            if (preg_match('/:/', $name)) {
	                	                $prefix = substr($name, 0, strpos($name, ':'));
	                	                $namespace = isset($this->namespaces[$prefix]) ? $this->namespaces[$prefix] : '';
	                	                $name = substr(strstr($name, ':'), 1);
	            }
					            	            switch ($this->status) {
	                case 'message':
	                    if ($name == 'part') {
				            if (isset($attrs['type'])) {
			                    $this->debug("msg " . $this->currentMessage . ": found part (with type) $attrs[name]: " . implode(',', $attrs));
			                    $this->messages[$this->currentMessage][$attrs['name']] = $attrs['type'];
	            			}
				            if (isset($attrs['element'])) {
			                    $this->debug("msg " . $this->currentMessage . ": found part (with element) $attrs[name]: " . implode(',', $attrs));
				                $this->messages[$this->currentMessage][$attrs['name']] = $attrs['element'] . '^';
				            }
	        			}
	        			break;
				    case 'portType':
				        switch ($name) {
				            case 'operation':
				                $this->currentPortOperation = $attrs['name'];
				                $this->debug("portType $this->currentPortType operation: $this->currentPortOperation");
				                if (isset($attrs['parameterOrder'])) {
				                	$this->portTypes[$this->currentPortType][$attrs['name']]['parameterOrder'] = $attrs['parameterOrder'];
				        		}
				        		break;
						    case 'documentation':
						        $this->documentation = true;
						        break;
						    						    default:
						        $m = isset($attrs['message']) ? $this->getLocalPart($attrs['message']) : '';
						        $this->portTypes[$this->currentPortType][$this->currentPortOperation][$name]['message'] = $m;
						        break;
						}
				    	break;
					case 'binding':
					    switch ($name) {
					        case 'binding':
					            					            if (isset($attrs['style'])) {
					            $this->bindings[$this->currentBinding]['prefix'] = $prefix;
						    	}
						    	$this->bindings[$this->currentBinding] = array_merge($this->bindings[$this->currentBinding], $attrs);
						    	break;
							case 'header':
							    $this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus]['headers'][] = $attrs;
							    break;
							case 'operation':
							    if (isset($attrs['soapAction'])) {
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['soapAction'] = $attrs['soapAction'];
							    }
							    if (isset($attrs['style'])) {
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['style'] = $attrs['style'];
							    }
							    if (isset($attrs['name'])) {
							        $this->currentOperation = $attrs['name'];
							        $this->debug("current binding operation: $this->currentOperation");
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['name'] = $attrs['name'];
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['binding'] = $this->currentBinding;
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation]['endpoint'] = isset($this->bindings[$this->currentBinding]['endpoint']) ? $this->bindings[$this->currentBinding]['endpoint'] : '';
							    }
							    break;
							case 'input':
							    $this->opStatus = 'input';
							    break;
							case 'output':
							    $this->opStatus = 'output';
							    break;
							case 'body':
							    if (isset($this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus])) {
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus] = array_merge($this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus], $attrs);
							    } else {
							        $this->bindings[$this->currentBinding]['operations'][$this->currentOperation][$this->opStatus] = $attrs;
							    }
							    break;
						}
						break;
					case 'service':
						switch ($name) {
						    case 'port':
						        $this->currentPort = $attrs['name'];
						        $this->debug('current port: ' . $this->currentPort);
						        $this->ports[$this->currentPort]['binding'] = $this->getLocalPart($attrs['binding']);

						        break;
						    case 'address':
						        $this->ports[$this->currentPort]['location'] = $attrs['location'];
						        $this->ports[$this->currentPort]['bindingType'] = $namespace;
						        $this->bindings[ $this->ports[$this->currentPort]['binding'] ]['bindingType'] = $namespace;
						        $this->bindings[ $this->ports[$this->currentPort]['binding'] ]['endpoint'] = $attrs['location'];
						        break;
						}
						break;
				}
						switch ($name) {
				case 'import':
				    if (isset($attrs['location'])) {
	                    $this->import[$attrs['namespace']][] = array('location' => $attrs['location'], 'loaded' => false);
	                    $this->debug('parsing import ' . $attrs['namespace']. ' - ' . $attrs['location'] . ' (' . count($this->import[$attrs['namespace']]).')');
					} else {
	                    $this->import[$attrs['namespace']][] = array('location' => '', 'loaded' => true);
						if (! $this->getPrefixFromNamespace($attrs['namespace'])) {
							$this->namespaces['ns'.(count($this->namespaces)+1)] = $attrs['namespace'];
						}
	                    $this->debug('parsing import ' . $attrs['namespace']. ' - [no location] (' . count($this->import[$attrs['namespace']]).')');
					}
					break;
																				case 'message':
					$this->status = 'message';
					$this->messages[$attrs['name']] = array();
					$this->currentMessage = $attrs['name'];
					break;
				case 'portType':
					$this->status = 'portType';
					$this->portTypes[$attrs['name']] = array();
					$this->currentPortType = $attrs['name'];
					break;
				case "binding":
					if (isset($attrs['name'])) {
											if (strpos($attrs['name'], ':')) {
				    		$this->currentBinding = $this->getLocalPart($attrs['name']);
						} else {
				    		$this->currentBinding = $attrs['name'];
						}
						$this->status = 'binding';
						$this->bindings[$this->currentBinding]['portType'] = $this->getLocalPart($attrs['type']);
						$this->debug("current binding: $this->currentBinding of portType: " . $attrs['type']);
					}
					break;
				case 'service':
					$this->serviceName = $attrs['name'];
					$this->status = 'service';
					$this->debug('current service: ' . $this->serviceName);
					break;
				case 'definitions':
					foreach ($attrs as $name => $value) {
						$this->wsdl_info[$name] = $value;
					}
					break;
				}
			}
		}


		function end_element($parser, $name){
						if ( preg_match('/schema$/', $name)) {
				$this->status = "";
	            $this->appendDebug($this->currentSchema->getDebug());
	            $this->currentSchema->clearDebug();
				$this->schemas[$this->currentSchema->schemaTargetNamespace][] = $this->currentSchema;
	        	$this->debug('Parsing WSDL schema done');
			}
			if ($this->status == 'schema') {
				$this->currentSchema->schemaEndElement($parser, $name);
			} else {
								$this->depth--;
			}
						if ($this->documentation) {
												$this->documentation = false;
			}
		}


		function character_data($parser, $data)
		{
			$pos = isset($this->depth_array[$this->depth]) ? $this->depth_array[$this->depth] : 0;
			if (isset($this->message[$pos]['cdata'])) {
				$this->message[$pos]['cdata'] .= $data;
			}
			if ($this->documentation) {
				$this->documentation .= $data;
			}
		}


		function setCredentials($username, $password, $authtype = 'basic', $certRequest = array()) {
			$this->debug("setCredentials username=$username authtype=$authtype certRequest=");
			$this->appendDebug($this->varDump($certRequest));
			$this->username = $username;
			$this->password = $password;
			$this->authtype = $authtype;
			$this->certRequest = $certRequest;
		}

		function getBindingData($binding)
		{
			if (is_array($this->bindings[$binding])) {
				return $this->bindings[$binding];
			}
		}


		function getOperations($portName = '', $bindingType = 'soap') {
			$ops = array();
			if ($bindingType == 'soap') {
				$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap/';
			} elseif ($bindingType == 'soap12') {
				$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap12/';
			} else {
				$this->debug("getOperations bindingType $bindingType may not be supported");
			}
			$this->debug("getOperations for port '$portName' bindingType $bindingType");
						foreach($this->ports as $port => $portData) {
				$this->debug("getOperations checking port $port bindingType " . $portData['bindingType']);
				if ($portName == '' || $port == $portName) {
										if ($portData['bindingType'] == $bindingType) {
						$this->debug("getOperations found port $port bindingType $bindingType");
																								if (isset($this->bindings[ $portData['binding'] ]['operations'])) {
							$ops = array_merge ($ops, $this->bindings[ $portData['binding'] ]['operations']);
						}
					}
				}
			}
			if (count($ops) == 0) {
				$this->debug("getOperations found no operations for port '$portName' bindingType $bindingType");
			}
			return $ops;
		}


		function getOperationData($operation, $bindingType = 'soap')
		{
			if ($bindingType == 'soap') {
				$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap/';
			} elseif ($bindingType == 'soap12') {
				$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap12/';
			}
						foreach($this->ports as $port => $portData) {
								if ($portData['bindingType'] == $bindingType) {
															foreach(array_keys($this->bindings[ $portData['binding'] ]['operations']) as $bOperation) {
												if ($operation == $bOperation) {
							$opData = $this->bindings[ $portData['binding'] ]['operations'][$operation];
						    return $opData;
						}
					}
				}
			}
		}


		function getOperationDataForSoapAction($soapAction, $bindingType = 'soap') {
			if ($bindingType == 'soap') {
				$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap/';
			} elseif ($bindingType == 'soap12') {
				$bindingType = 'http://schemas.xmlsoap.org/wsdl/soap12/';
			}
						foreach($this->ports as $port => $portData) {
								if ($portData['bindingType'] == $bindingType) {
										foreach ($this->bindings[ $portData['binding'] ]['operations'] as $bOperation => $opData) {
						if ($opData['soapAction'] == $soapAction) {
						    return $opData;
						}
					}
				}
			}
		}


		function getTypeDef($type, $ns) {
			$this->debug("in getTypeDef: type=$type, ns=$ns");
			if ((! $ns) && isset($this->namespaces['tns'])) {
				$ns = $this->namespaces['tns'];
				$this->debug("in getTypeDef: type namespace forced to $ns");
			}
			if (!isset($this->schemas[$ns])) {
				foreach ($this->schemas as $ns0 => $schema0) {
					if (strcasecmp($ns, $ns0) == 0) {
						$this->debug("in getTypeDef: replacing schema namespace $ns with $ns0");
						$ns = $ns0;
						break;
					}
				}
			}
			if (isset($this->schemas[$ns])) {
				$this->debug("in getTypeDef: have schema for namespace $ns");
				for ($i = 0; $i < count($this->schemas[$ns]); $i++) {
					$xs = &$this->schemas[$ns][$i];
					$t = $xs->getTypeDef($type);
					$this->appendDebug($xs->getDebug());
					$xs->clearDebug();
					if ($t) {
						$this->debug("in getTypeDef: found type $type");
						if (!isset($t['phpType'])) {
														$uqType = substr($t['type'], strrpos($t['type'], ':') + 1);
							$ns = substr($t['type'], 0, strrpos($t['type'], ':'));
							$etype = $this->getTypeDef($uqType, $ns);
							if ($etype) {
								$this->debug("found type for [element] $type:");
								$this->debug($this->varDump($etype));
								if (isset($etype['phpType'])) {
									$t['phpType'] = $etype['phpType'];
								}
								if (isset($etype['elements'])) {
									$t['elements'] = $etype['elements'];
								}
								if (isset($etype['attrs'])) {
									$t['attrs'] = $etype['attrs'];
								}
							} else {
								$this->debug("did not find type for [element] $type");
							}
						}
						return $t;
					}
				}
				$this->debug("in getTypeDef: did not find type $type");
			} else {
				$this->debug("in getTypeDef: do not have schema for namespace $ns");
			}
			return false;
		}


	    function webDescription(){
	    	global $HTTP_SERVER_VARS;

			if (isset($_SERVER)) {
				$PHP_SELF = $_SERVER['PHP_SELF'];
			} elseif (isset($HTTP_SERVER_VARS)) {
				$PHP_SELF = $HTTP_SERVER_VARS['PHP_SELF'];
			} else {
				$this->setError("Neither _SERVER nor HTTP_SERVER_VARS is available");
			}

			$b = '
			<html><head><title>NuSOAP: '.$this->serviceName.'</title>
			<style type="text/css">
			    body    { font-family: arial; color: #000000; background-color: #ffffff; margin: 0px 0px 0px 0px; }
			    p       { font-family: arial; color: #000000; margin-top: 0px; margin-bottom: 12px; }
			    pre { background-color: silver; padding: 5px; font-family: Courier New; font-size: x-small; color: #000000;}
			    ul      { margin-top: 10px; margin-left: 20px; }
			    li      { list-style-type: none; margin-top: 10px; color: #000000; }
			    .content{
				margin-left: 0px; padding-bottom: 2em; }
			    .nav {
				padding-top: 10px; padding-bottom: 10px; padding-left: 15px; font-size: .70em;
				margin-top: 10px; margin-left: 0px; color: #000000;
				background-color: #ccccff; width: 20%; margin-left: 20px; margin-top: 20px; }
			    .title {
				font-family: arial; font-size: 26px; color: #ffffff;
				background-color: #999999; width: 100%;
				margin-left: 0px; margin-right: 0px;
				padding-top: 10px; padding-bottom: 10px;}
			    .hidden {
				position: absolute; visibility: hidden; z-index: 200; left: 250px; top: 100px;
				font-family: arial; overflow: hidden; width: 600;
				padding: 20px; font-size: 10px; background-color: #999999;
				layer-background-color:#FFFFFF; }
			    a,a:active  { color: charcoal; font-weight: bold; }
			    a:visited   { color: #666666; font-weight: bold; }
			    a:hover     { color: cc3300; font-weight: bold; }
			</style>
			<script language="JavaScript" type="text/javascript">
			<!--
			// POP-UP CAPTIONS...
			function lib_bwcheck(){ //Browsercheck (needed)
			    this.ver=navigator.appVersion
			    this.agent=navigator.userAgent
			    this.dom=document.getElementById?1:0
			    this.opera5=this.agent.indexOf("Opera 5")>-1
			    this.ie5=(this.ver.indexOf("MSIE 5")>-1 && this.dom && !this.opera5)?1:0;
			    this.ie6=(this.ver.indexOf("MSIE 6")>-1 && this.dom && !this.opera5)?1:0;
			    this.ie4=(document.all && !this.dom && !this.opera5)?1:0;
			    this.ie=this.ie4||this.ie5||this.ie6
			    this.mac=this.agent.indexOf("Mac")>-1
			    this.ns6=(this.dom && parseInt(this.ver) >= 5) ?1:0;
			    this.ns4=(document.layers && !this.dom)?1:0;
			    this.bw=(this.ie6 || this.ie5 || this.ie4 || this.ns4 || this.ns6 || this.opera5)
			    return this
			}
			var bw = new lib_bwcheck()
			//Makes crossbrowser object.
			function makeObj(obj){
			    this.evnt=bw.dom? document.getElementById(obj):bw.ie4?document.all[obj]:bw.ns4?document.layers[obj]:0;
			    if(!this.evnt) return false
			    this.css=bw.dom||bw.ie4?this.evnt.style:bw.ns4?this.evnt:0;
			    this.wref=bw.dom||bw.ie4?this.evnt:bw.ns4?this.css.document:0;
			    this.writeIt=b_writeIt;
			    return this
			}
			// A unit of measure that will be added when setting the position of a layer.
			//var px = bw.ns4||window.opera?"":"px";
			function b_writeIt(text){
			    if (bw.ns4){this.wref.write(text);this.wref.close()}
			    else this.wref.innerHTML = text
			}
			//Shows the messages
			var oDesc;
			function popup(divid){
			    if(oDesc = new makeObj(divid)){
				oDesc.css.visibility = "visible"
			    }
			}
			function popout(){ // Hides message
			    if(oDesc) oDesc.css.visibility = "hidden"
			}
			//-->
			</script>
			</head>
			<body>
			<div class=content>
				<br><br>
				<div class=title>'.$this->serviceName.'</div>
				<div class=nav>
					<p>View the <a href="'.$PHP_SELF.'?wsdl">WSDL</a> for the service.
					Click on an operation name to view it&apos;s details.</p>
					<ul>';
					foreach($this->getOperations() as $op => $data){
					    $b .= "<li><a href='#' onclick=\"popout();popup('$op')\">$op</a></li>";
					    					    $b .= "<div id='$op' class='hidden'>
					    <a href='#' onclick='popout()'><font color='#ffffff'>Close</font></a><br><br>";
					    foreach($data as $donnie => $marie){ 							if($donnie == 'input' || $donnie == 'output'){ 							    $b .= "<font color='white'>".ucfirst($donnie).':</font><br>';
							    foreach($marie as $captain => $tenille){ 									if($captain == 'parts'){ 									    $b .= "&nbsp;&nbsp;$captain:<br>";
						                									    	foreach($tenille as $joanie => $chachi){
												$b .= "&nbsp;&nbsp;&nbsp;&nbsp;$joanie: $chachi<br>";
									    	}
						        											} else {
									    $b .= "&nbsp;&nbsp;$captain: $tenille<br>";
									}
							    }
							} else {
							    $b .= "<font color='white'>".ucfirst($donnie).":</font> $marie<br>";
							}
					    }
						$b .= '</div>';
					}
					$b .= '
					<ul>
				</div>
			</div></body></html>';
			return $b;
	    }


		function serialize($debug = 0)
		{
			$xml = '<?xml version="1.0" encoding="ISO-8859-1"?>';
			$xml .= "\n<definitions";
			foreach($this->namespaces as $k => $v) {
				$xml .= " xmlns:$k=\"$v\"";
			}
						if (isset($this->namespaces['wsdl'])) {
				$xml .= " xmlns=\"" . $this->namespaces['wsdl'] . "\"";
			}
			if (isset($this->namespaces['tns'])) {
				$xml .= " targetNamespace=\"" . $this->namespaces['tns'] . "\"";
			}
			$xml .= '>';
						if (sizeof($this->import) > 0) {
				foreach($this->import as $ns => $list) {
					foreach ($list as $ii) {
						if ($ii['location'] != '') {
							$xml .= '<import location="' . $ii['location'] . '" namespace="' . $ns . '" />';
						} else {
							$xml .= '<import namespace="' . $ns . '" />';
						}
					}
				}
			}
						if (count($this->schemas)>=1) {
				$xml .= "\n<types>\n";
				foreach ($this->schemas as $ns => $list) {
					foreach ($list as $xs) {
						$xml .= $xs->serializeSchema();
					}
				}
				$xml .= '</types>';
			}
						if (count($this->messages) >= 1) {
				foreach($this->messages as $msgName => $msgParts) {
					$xml .= "\n<message name=\"" . $msgName . '">';
					if(is_array($msgParts)){
						foreach($msgParts as $partName => $partType) {
														if (strpos($partType, ':')) {
							    $typePrefix = $this->getPrefixFromNamespace($this->getPrefix($partType));
							} elseif (isset($this->typemap[$this->namespaces['xsd']][$partType])) {
							    							    $typePrefix = 'xsd';
							} else {
							    foreach($this->typemap as $ns => $types) {
							        if (isset($types[$partType])) {
							            $typePrefix = $this->getPrefixFromNamespace($ns);
							        }
							    }
							    if (!isset($typePrefix)) {
							        die("$partType has no namespace!");
							    }
							}
							$ns = $this->getNamespaceFromPrefix($typePrefix);
							$localPart = $this->getLocalPart($partType);
							$typeDef = $this->getTypeDef($localPart, $ns);
							if ($typeDef['typeClass'] == 'element') {
								$elementortype = 'element';
								if (substr($localPart, -1) == '^') {
									$localPart = substr($localPart, 0, -1);
								}
							} else {
								$elementortype = 'type';
							}
							$xml .= "\n" . '  <part name="' . $partName . '" ' . $elementortype . '="' . $typePrefix . ':' . $localPart . '" />';
						}
					}
					$xml .= '</message>';
				}
			}
						if (count($this->bindings) >= 1) {
				$binding_xml = '';
				$portType_xml = '';
				foreach($this->bindings as $bindingName => $attrs) {
					$binding_xml .= "\n<binding name=\"" . $bindingName . '" type="tns:' . $attrs['portType'] . '">';
					$binding_xml .= "\n" . '  <soap:binding style="' . $attrs['style'] . '" transport="' . $attrs['transport'] . '"/>';
					$portType_xml .= "\n<portType name=\"" . $attrs['portType'] . '">';
					foreach($attrs['operations'] as $opName => $opParts) {
						$binding_xml .= "\n" . '  <operation name="' . $opName . '">';
						$binding_xml .= "\n" . '    <soap:operation soapAction="' . $opParts['soapAction'] . '" style="'. $opParts['style'] . '"/>';
						if (isset($opParts['input']['encodingStyle']) && $opParts['input']['encodingStyle'] != '') {
							$enc_style = ' encodingStyle="' . $opParts['input']['encodingStyle'] . '"';
						} else {
							$enc_style = '';
						}
						$binding_xml .= "\n" . '    <input><soap:body use="' . $opParts['input']['use'] . '" namespace="' . $opParts['input']['namespace'] . '"' . $enc_style . '/></input>';
						if (isset($opParts['output']['encodingStyle']) && $opParts['output']['encodingStyle'] != '') {
							$enc_style = ' encodingStyle="' . $opParts['output']['encodingStyle'] . '"';
						} else {
							$enc_style = '';
						}
						$binding_xml .= "\n" . '    <output><soap:body use="' . $opParts['output']['use'] . '" namespace="' . $opParts['output']['namespace'] . '"' . $enc_style . '/></output>';
						$binding_xml .= "\n" . '  </operation>';
						$portType_xml .= "\n" . '  <operation name="' . $opParts['name'] . '"';
						if (isset($opParts['parameterOrder'])) {
						    $portType_xml .= ' parameterOrder="' . $opParts['parameterOrder'] . '"';
						}
						$portType_xml .= '>';
						if(isset($opParts['documentation']) && $opParts['documentation'] != '') {
							$portType_xml .= "\n" . '    <documentation>' . htmlspecialchars($opParts['documentation']) . '</documentation>';
						}
						$portType_xml .= "\n" . '    <input message="tns:' . $opParts['input']['message'] . '"/>';
						$portType_xml .= "\n" . '    <output message="tns:' . $opParts['output']['message'] . '"/>';
						$portType_xml .= "\n" . '  </operation>';
					}
					$portType_xml .= "\n" . '</portType>';
					$binding_xml .= "\n" . '</binding>';
				}
				$xml .= $portType_xml . $binding_xml;
			}
						$xml .= "\n<service name=\"" . $this->serviceName . '">';
			if (count($this->ports) >= 1) {
				foreach($this->ports as $pName => $attrs) {
					$xml .= "\n" . '  <port name="' . $pName . '" binding="tns:' . $attrs['binding'] . '">';
					$xml .= "\n" . '    <soap:address location="' . $attrs['location'] . ($debug ? '?debug=1' : '') . '"/>';
					$xml .= "\n" . '  </port>';
				}
			}
			$xml .= "\n" . '</service>';
			return $xml . "\n</definitions>";
		}


		function parametersMatchWrapped($type, &$parameters) {
			$this->debug("in parametersMatchWrapped type=$type, parameters=");
			$this->appendDebug($this->varDump($parameters));

						if (strpos($type, ':')) {
				$uqType = substr($type, strrpos($type, ':') + 1);
				$ns = substr($type, 0, strrpos($type, ':'));
				$this->debug("in parametersMatchWrapped: got a prefixed type: $uqType, $ns");
				if ($this->getNamespaceFromPrefix($ns)) {
					$ns = $this->getNamespaceFromPrefix($ns);
					$this->debug("in parametersMatchWrapped: expanded prefixed type: $uqType, $ns");
				}
			} else {
												$this->debug("in parametersMatchWrapped: No namespace for type $type");
				$ns = '';
				$uqType = $type;
			}

						if (!$typeDef = $this->getTypeDef($uqType, $ns)) {
				$this->debug("in parametersMatchWrapped: $type ($uqType) is not a supported type.");
				return false;
			}
			$this->debug("in parametersMatchWrapped: found typeDef=");
			$this->appendDebug($this->varDump($typeDef));
			if (substr($uqType, -1) == '^') {
				$uqType = substr($uqType, 0, -1);
			}
			$phpType = $typeDef['phpType'];
			$arrayType = (isset($typeDef['arrayType']) ? $typeDef['arrayType'] : '');
			$this->debug("in parametersMatchWrapped: uqType: $uqType, ns: $ns, phptype: $phpType, arrayType: $arrayType");

						if ($phpType != 'struct') {
				$this->debug("in parametersMatchWrapped: not a struct");
				return false;
			}

						if (isset($typeDef['elements']) && is_array($typeDef['elements'])) {
				$elements = 0;
				$matches = 0;
				foreach ($typeDef['elements'] as $name => $attrs) {
					if (isset($parameters[$name])) {
						$this->debug("in parametersMatchWrapped: have parameter named $name");
						$matches++;
					} else {
						$this->debug("in parametersMatchWrapped: do not have parameter named $name");
					}
					$elements++;
				}

				$this->debug("in parametersMatchWrapped: $matches parameter names match $elements wrapped parameter names");
				if ($matches == 0) {
					return false;
				}
				return true;
			}

									$this->debug("in parametersMatchWrapped: no elements type $ns:$uqType");
			return count($parameters) == 0;
		}


		function serializeRPCParameters($operation, $direction, $parameters, $bindingType = 'soap') {
			$this->debug("in serializeRPCParameters: operation=$operation, direction=$direction, XMLSchemaVersion=$this->XMLSchemaVersion, bindingType=$bindingType");
			$this->appendDebug('parameters=' . $this->varDump($parameters));

			if ($direction != 'input' && $direction != 'output') {
				$this->debug('The value of the \$direction argument needs to be either "input" or "output"');
				$this->setError('The value of the \$direction argument needs to be either "input" or "output"');
				return false;
			}
			if (!$opData = $this->getOperationData($operation, $bindingType)) {
				$this->debug('Unable to retrieve WSDL data for operation: ' . $operation . ' bindingType: ' . $bindingType);
				$this->setError('Unable to retrieve WSDL data for operation: ' . $operation . ' bindingType: ' . $bindingType);
				return false;
			}
			$this->debug('in serializeRPCParameters: opData:');
			$this->appendDebug($this->varDump($opData));

						$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
			if(($direction == 'input') && isset($opData['output']['encodingStyle']) && ($opData['output']['encodingStyle'] != $encodingStyle)) {
				$encodingStyle = $opData['output']['encodingStyle'];
				$enc_style = $encodingStyle;
			}

						$xml = '';
			if (isset($opData[$direction]['parts']) && sizeof($opData[$direction]['parts']) > 0) {
				$parts = &$opData[$direction]['parts'];
				$part_count = sizeof($parts);
				$style = $opData['style'];
				$use = $opData[$direction]['use'];
				$this->debug("have $part_count part(s) to serialize using $style/$use");
				if (is_array($parameters)) {
					$parametersArrayType = $this->isArraySimpleOrStruct($parameters);
					$parameter_count = count($parameters);
					$this->debug("have $parameter_count parameter(s) provided as $parametersArrayType to serialize");
										if ($style == 'document' && $use == 'literal' && $part_count == 1 && isset($parts['parameters'])) {
						$this->debug('check whether the caller has wrapped the parameters');
						if ($direction == 'output' && $parametersArrayType == 'arraySimple' && $parameter_count == 1) {
																					$this->debug("change simple array to associative with 'parameters' element");
							$parameters['parameters'] = $parameters[0];
							unset($parameters[0]);
						}
						if (($parametersArrayType == 'arrayStruct' || $parameter_count == 0) && !isset($parameters['parameters'])) {
							$this->debug('check whether caller\'s parameters match the wrapped ones');
							if ($this->parametersMatchWrapped($parts['parameters'], $parameters)) {
								$this->debug('wrap the parameters for the caller');
								$parameters = array('parameters' => $parameters);
								$parameter_count = 1;
							}
						}
					}
					foreach ($parts as $name => $type) {
						$this->debug("serializing part $name of type $type");
												if (isset($opData[$direction]['encodingStyle']) && $encodingStyle != $opData[$direction]['encodingStyle']) {
							$encodingStyle = $opData[$direction]['encodingStyle'];
							$enc_style = $encodingStyle;
						} else {
							$enc_style = false;
						}
																		if ($parametersArrayType == 'arraySimple') {
							$p = array_shift($parameters);
							$this->debug('calling serializeType w/indexed param');
							$xml .= $this->serializeType($name, $type, $p, $use, $enc_style);
						} elseif (isset($parameters[$name])) {
							$this->debug('calling serializeType w/named param');
							$xml .= $this->serializeType($name, $type, $parameters[$name], $use, $enc_style);
						} else {
														$this->debug('calling serializeType w/null param');
							$xml .= $this->serializeType($name, $type, null, $use, $enc_style);
						}
					}
				} else {
					$this->debug('no parameters passed.');
				}
			}
			$this->debug("serializeRPCParameters returning: $xml");
			return $xml;
		}


		function serializeParameters($operation, $direction, $parameters)
		{
			$this->debug("in serializeParameters: operation=$operation, direction=$direction, XMLSchemaVersion=$this->XMLSchemaVersion");
			$this->appendDebug('parameters=' . $this->varDump($parameters));

			if ($direction != 'input' && $direction != 'output') {
				$this->debug('The value of the \$direction argument needs to be either "input" or "output"');
				$this->setError('The value of the \$direction argument needs to be either "input" or "output"');
				return false;
			}
			if (!$opData = $this->getOperationData($operation)) {
				$this->debug('Unable to retrieve WSDL data for operation: ' . $operation);
				$this->setError('Unable to retrieve WSDL data for operation: ' . $operation);
				return false;
			}
			$this->debug('opData:');
			$this->appendDebug($this->varDump($opData));

						$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
			if(($direction == 'input') && isset($opData['output']['encodingStyle']) && ($opData['output']['encodingStyle'] != $encodingStyle)) {
				$encodingStyle = $opData['output']['encodingStyle'];
				$enc_style = $encodingStyle;
			}

						$xml = '';
			if (isset($opData[$direction]['parts']) && sizeof($opData[$direction]['parts']) > 0) {

				$use = $opData[$direction]['use'];
				$this->debug("use=$use");
				$this->debug('got ' . count($opData[$direction]['parts']) . ' part(s)');
				if (is_array($parameters)) {
					$parametersArrayType = $this->isArraySimpleOrStruct($parameters);
					$this->debug('have ' . $parametersArrayType . ' parameters');
					foreach($opData[$direction]['parts'] as $name => $type) {
						$this->debug('serializing part "'.$name.'" of type "'.$type.'"');
												if(isset($opData[$direction]['encodingStyle']) && $encodingStyle != $opData[$direction]['encodingStyle']) {
							$encodingStyle = $opData[$direction]['encodingStyle'];
							$enc_style = $encodingStyle;
						} else {
							$enc_style = false;
						}
																		if ($parametersArrayType == 'arraySimple') {
							$p = array_shift($parameters);
							$this->debug('calling serializeType w/indexed param');
							$xml .= $this->serializeType($name, $type, $p, $use, $enc_style);
						} elseif (isset($parameters[$name])) {
							$this->debug('calling serializeType w/named param');
							$xml .= $this->serializeType($name, $type, $parameters[$name], $use, $enc_style);
						} else {
														$this->debug('calling serializeType w/null param');
							$xml .= $this->serializeType($name, $type, null, $use, $enc_style);
						}
					}
				} else {
					$this->debug('no parameters passed.');
				}
			}
			$this->debug("serializeParameters returning: $xml");
			return $xml;
		}


		function serializeType($name, $type, $value, $use='encoded', $encodingStyle=false, $unqualified=false)
		{
			$this->debug("in serializeType: name=$name, type=$type, use=$use, encodingStyle=$encodingStyle, unqualified=" . ($unqualified ? "unqualified" : "qualified"));
			$this->appendDebug("value=" . $this->varDump($value));
			if($use == 'encoded' && $encodingStyle) {
				$encodingStyle = ' SOAP-ENV:encodingStyle="' . $encodingStyle . '"';
			}

				    	if (is_object($value) && get_class($value) == 'soapval') {
	    		if ($value->type_ns) {
	    			$type = $value->type_ns . ':' . $value->type;
			    	$forceType = true;
			    	$this->debug("in serializeType: soapval overrides type to $type");
	    		} elseif ($value->type) {
		    		$type = $value->type;
			    	$forceType = true;
			    	$this->debug("in serializeType: soapval overrides type to $type");
		    	} else {
		    		$forceType = false;
			    	$this->debug("in serializeType: soapval does not override type");
		    	}
		    	$attrs = $value->attributes;
		    	$value = $value->value;
		    	$this->debug("in serializeType: soapval overrides value to $value");
		    	if ($attrs) {
		    		if (!is_array($value)) {
		    			$value['!'] = $value;
		    		}
		    		foreach ($attrs as $n => $v) {
		    			$value['!' . $n] = $v;
		    		}
			    	$this->debug("in serializeType: soapval provides attributes");
			    }
	        } else {
	        	$forceType = false;
	        }

			$xml = '';
			if (strpos($type, ':')) {
				$uqType = substr($type, strrpos($type, ':') + 1);
				$ns = substr($type, 0, strrpos($type, ':'));
				$this->debug("in serializeType: got a prefixed type: $uqType, $ns");
				if ($this->getNamespaceFromPrefix($ns)) {
					$ns = $this->getNamespaceFromPrefix($ns);
					$this->debug("in serializeType: expanded prefixed type: $uqType, $ns");
				}

				if($ns == $this->XMLSchemaVersion || $ns == 'http://schemas.xmlsoap.org/soap/encoding/'){
					$this->debug('in serializeType: type namespace indicates XML Schema or SOAP Encoding type');
					if ($unqualified && $use == 'literal') {
						$elementNS = " xmlns=\"\"";
					} else {
						$elementNS = '';
					}
					if (is_null($value)) {
						if ($use == 'literal') {
														$xml = "<$name$elementNS/>";
						} else {
														$xml = "<$name$elementNS xsi:nil=\"true\" xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\"/>";
						}
						$this->debug("in serializeType: returning: $xml");
						return $xml;
					}
					if ($uqType == 'Array') {
												return $this->serialize_val($value, $name, false, false, false, false, $use);
					}
			    	if ($uqType == 'boolean') {
			    		if ((is_string($value) && $value == 'false') || (! $value)) {
							$value = 'false';
						} else {
							$value = 'true';
						}
					}
					if ($uqType == 'string' && gettype($value) == 'string') {
						$value = $this->expandEntities($value);
					}
					if (($uqType == 'long' || $uqType == 'unsignedLong') && gettype($value) == 'double') {
						$value = sprintf("%.0lf", $value);
					}
																				if (!$this->getTypeDef($uqType, $ns)) {
						if ($use == 'literal') {
							if ($forceType) {
								$xml = "<$name$elementNS xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\">$value</$name>";
							} else {
								$xml = "<$name$elementNS>$value</$name>";
							}
						} else {
							$xml = "<$name$elementNS xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\"$encodingStyle>$value</$name>";
						}
						$this->debug("in serializeType: returning: $xml");
						return $xml;
					}
					$this->debug('custom type extends XML Schema or SOAP Encoding namespace (yuck)');
				} else if ($ns == 'http://xml.apache.org/xml-soap') {
					$this->debug('in serializeType: appears to be Apache SOAP type');
					if ($uqType == 'Map') {
						$tt_prefix = $this->getPrefixFromNamespace('http://xml.apache.org/xml-soap');
						if (! $tt_prefix) {
							$this->debug('in serializeType: Add namespace for Apache SOAP type');
							$tt_prefix = 'ns' . rand(1000, 9999);
							$this->namespaces[$tt_prefix] = 'http://xml.apache.org/xml-soap';
														$tt_prefix = $this->getPrefixFromNamespace('http://xml.apache.org/xml-soap');
						}
						$contents = '';
						foreach($value as $k => $v) {
							$this->debug("serializing map element: key $k, value $v");
							$contents .= '<item>';
							$contents .= $this->serialize_val($k,'key',false,false,false,false,$use);
							$contents .= $this->serialize_val($v,'value',false,false,false,false,$use);
							$contents .= '</item>';
						}
						if ($use == 'literal') {
							if ($forceType) {
								$xml = "<$name xsi:type=\"" . $tt_prefix . ":$uqType\">$contents</$name>";
							} else {
								$xml = "<$name>$contents</$name>";
							}
						} else {
							$xml = "<$name xsi:type=\"" . $tt_prefix . ":$uqType\"$encodingStyle>$contents</$name>";
						}
						$this->debug("in serializeType: returning: $xml");
						return $xml;
					}
					$this->debug('in serializeType: Apache SOAP type, but only support Map');
				}
			} else {
												$this->debug("in serializeType: No namespace for type $type");
				$ns = '';
				$uqType = $type;
			}
			if(!$typeDef = $this->getTypeDef($uqType, $ns)){
				$this->setError("$type ($uqType) is not a supported type.");
				$this->debug("in serializeType: $type ($uqType) is not a supported type.");
				return false;
			} else {
				$this->debug("in serializeType: found typeDef");
				$this->appendDebug('typeDef=' . $this->varDump($typeDef));
				if (substr($uqType, -1) == '^') {
					$uqType = substr($uqType, 0, -1);
				}
			}
			if (!isset($typeDef['phpType'])) {
				$this->setError("$type ($uqType) has no phpType.");
				$this->debug("in serializeType: $type ($uqType) has no phpType.");
				return false;
			}
			$phpType = $typeDef['phpType'];
			$this->debug("in serializeType: uqType: $uqType, ns: $ns, phptype: $phpType, arrayType: " . (isset($typeDef['arrayType']) ? $typeDef['arrayType'] : '') );
						if ($phpType == 'struct') {
				if (isset($typeDef['typeClass']) && $typeDef['typeClass'] == 'element') {
					$elementName = $uqType;
					if (isset($typeDef['form']) && ($typeDef['form'] == 'qualified')) {
						$elementNS = " xmlns=\"$ns\"";
					} else {
						$elementNS = " xmlns=\"\"";
					}
				} else {
					$elementName = $name;
					if ($unqualified) {
						$elementNS = " xmlns=\"\"";
					} else {
						$elementNS = '';
					}
				}
				if (is_null($value)) {
					if ($use == 'literal') {
												$xml = "<$elementName$elementNS/>";
					} else {
						$xml = "<$elementName$elementNS xsi:nil=\"true\" xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\"/>";
					}
					$this->debug("in serializeType: returning: $xml");
					return $xml;
				}
				if (is_object($value)) {
					$value = get_object_vars($value);
				}
				if (is_array($value)) {
					$elementAttrs = $this->serializeComplexTypeAttributes($typeDef, $value, $ns, $uqType);
					if ($use == 'literal') {
						if ($forceType) {
							$xml = "<$elementName$elementNS$elementAttrs xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\">";
						} else {
							$xml = "<$elementName$elementNS$elementAttrs>";
						}
					} else {
						$xml = "<$elementName$elementNS$elementAttrs xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\"$encodingStyle>";
					}

					if (isset($typeDef['simpleContent']) && $typeDef['simpleContent'] == 'true') {
						if (isset($value['!'])) {
							$xml .= $value['!'];
							$this->debug("in serializeType: serialized simpleContent for type $type");
						} else {
							$this->debug("in serializeType: no simpleContent to serialize for type $type");
						}
					} else {
												$xml .= $this->serializeComplexTypeElements($typeDef, $value, $ns, $uqType, $use, $encodingStyle);
					}
					$xml .= "</$elementName>";
				} else {
					$this->debug("in serializeType: phpType is struct, but value is not an array");
					$this->setError("phpType is struct, but value is not an array: see debug output for details");
					$xml = '';
				}
			} elseif ($phpType == 'array') {
				if (isset($typeDef['form']) && ($typeDef['form'] == 'qualified')) {
					$elementNS = " xmlns=\"$ns\"";
				} else {
					if ($unqualified) {
						$elementNS = " xmlns=\"\"";
					} else {
						$elementNS = '';
					}
				}
				if (is_null($value)) {
					if ($use == 'literal') {
												$xml = "<$name$elementNS/>";
					} else {
						$xml = "<$name$elementNS xsi:nil=\"true\" xsi:type=\"" .
							$this->getPrefixFromNamespace('http://schemas.xmlsoap.org/soap/encoding/') .
							":Array\" " .
							$this->getPrefixFromNamespace('http://schemas.xmlsoap.org/soap/encoding/') .
							':arrayType="' .
							$this->getPrefixFromNamespace($this->getPrefix($typeDef['arrayType'])) .
							':' .
							$this->getLocalPart($typeDef['arrayType'])."[0]\"/>";
					}
					$this->debug("in serializeType: returning: $xml");
					return $xml;
				}
				if (isset($typeDef['multidimensional'])) {
					$nv = array();
					foreach($value as $v) {
						$cols = ',' . sizeof($v);
						$nv = array_merge($nv, $v);
					}
					$value = $nv;
				} else {
					$cols = '';
				}
				if (is_array($value) && sizeof($value) >= 1) {
					$rows = sizeof($value);
					$contents = '';
					foreach($value as $k => $v) {
						$this->debug("serializing array element: $k, $v of type: $typeDef[arrayType]");
												if (!in_array($typeDef['arrayType'],$this->typemap['http://www.w3.org/2001/XMLSchema'])) {
						    $contents .= $this->serializeType('item', $typeDef['arrayType'], $v, $use);
						} else {
						    $contents .= $this->serialize_val($v, 'item', $typeDef['arrayType'], null, $this->XMLSchemaVersion, false, $use);
						}
					}
				} else {
					$rows = 0;
					$contents = null;
				}
												if ($use == 'literal') {
					$xml = "<$name$elementNS>"
						.$contents
						."</$name>";
				} else {
					$xml = "<$name$elementNS xsi:type=\"".$this->getPrefixFromNamespace('http://schemas.xmlsoap.org/soap/encoding/').':Array" '.
						$this->getPrefixFromNamespace('http://schemas.xmlsoap.org/soap/encoding/')
						.':arrayType="'
						.$this->getPrefixFromNamespace($this->getPrefix($typeDef['arrayType']))
						.":".$this->getLocalPart($typeDef['arrayType'])."[$rows$cols]\">"
						.$contents
						."</$name>";
				}
			} elseif ($phpType == 'scalar') {
				if (isset($typeDef['form']) && ($typeDef['form'] == 'qualified')) {
					$elementNS = " xmlns=\"$ns\"";
				} else {
					if ($unqualified) {
						$elementNS = " xmlns=\"\"";
					} else {
						$elementNS = '';
					}
				}
				if ($use == 'literal') {
					if ($forceType) {
						$xml = "<$name$elementNS xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\">$value</$name>";
					} else {
						$xml = "<$name$elementNS>$value</$name>";
					}
				} else {
					$xml = "<$name$elementNS xsi:type=\"" . $this->getPrefixFromNamespace($ns) . ":$uqType\"$encodingStyle>$value</$name>";
				}
			}
			$this->debug("in serializeType: returning: $xml");
			return $xml;
		}


		function serializeComplexTypeAttributes($typeDef, $value, $ns, $uqType) {
			$this->debug("serializeComplexTypeAttributes for XML Schema type $ns:$uqType");
			$xml = '';
			if (isset($typeDef['extensionBase'])) {
				$nsx = $this->getPrefix($typeDef['extensionBase']);
				$uqTypex = $this->getLocalPart($typeDef['extensionBase']);
				if ($this->getNamespaceFromPrefix($nsx)) {
					$nsx = $this->getNamespaceFromPrefix($nsx);
				}
				if ($typeDefx = $this->getTypeDef($uqTypex, $nsx)) {
					$this->debug("serialize attributes for extension base $nsx:$uqTypex");
					$xml .= $this->serializeComplexTypeAttributes($typeDefx, $value, $nsx, $uqTypex);
				} else {
					$this->debug("extension base $nsx:$uqTypex is not a supported type");
				}
			}
			if (isset($typeDef['attrs']) && is_array($typeDef['attrs'])) {
				$this->debug("serialize attributes for XML Schema type $ns:$uqType");
				if (is_array($value)) {
					$xvalue = $value;
				} elseif (is_object($value)) {
					$xvalue = get_object_vars($value);
				} else {
					$this->debug("value is neither an array nor an object for XML Schema type $ns:$uqType");
					$xvalue = array();
				}
				foreach ($typeDef['attrs'] as $aName => $attrs) {
					if (isset($xvalue['!' . $aName])) {
						$xname = '!' . $aName;
						$this->debug("value provided for attribute $aName with key $xname");
					} elseif (isset($xvalue[$aName])) {
						$xname = $aName;
						$this->debug("value provided for attribute $aName with key $xname");
					} elseif (isset($attrs['default'])) {
						$xname = '!' . $aName;
						$xvalue[$xname] = $attrs['default'];
						$this->debug('use default value of ' . $xvalue[$aName] . ' for attribute ' . $aName);
					} else {
						$xname = '';
						$this->debug("no value provided for attribute $aName");
					}
					if ($xname) {
						$xml .=  " $aName=\"" . $this->expandEntities($xvalue[$xname]) . "\"";
					}
				}
			} else {
				$this->debug("no attributes to serialize for XML Schema type $ns:$uqType");
			}
			return $xml;
		}


		function serializeComplexTypeElements($typeDef, $value, $ns, $uqType, $use='encoded', $encodingStyle=false) {
			$this->debug("in serializeComplexTypeElements for XML Schema type $ns:$uqType");
			$xml = '';
			if (isset($typeDef['extensionBase'])) {
				$nsx = $this->getPrefix($typeDef['extensionBase']);
				$uqTypex = $this->getLocalPart($typeDef['extensionBase']);
				if ($this->getNamespaceFromPrefix($nsx)) {
					$nsx = $this->getNamespaceFromPrefix($nsx);
				}
				if ($typeDefx = $this->getTypeDef($uqTypex, $nsx)) {
					$this->debug("serialize elements for extension base $nsx:$uqTypex");
					$xml .= $this->serializeComplexTypeElements($typeDefx, $value, $nsx, $uqTypex, $use, $encodingStyle);
				} else {
					$this->debug("extension base $nsx:$uqTypex is not a supported type");
				}
			}
			if (isset($typeDef['elements']) && is_array($typeDef['elements'])) {
				$this->debug("in serializeComplexTypeElements, serialize elements for XML Schema type $ns:$uqType");
				if (is_array($value)) {
					$xvalue = $value;
				} elseif (is_object($value)) {
					$xvalue = get_object_vars($value);
				} else {
					$this->debug("value is neither an array nor an object for XML Schema type $ns:$uqType");
					$xvalue = array();
				}
								if (count($typeDef['elements']) != count($xvalue)){
					$optionals = true;
				}
				foreach ($typeDef['elements'] as $eName => $attrs) {
					if (!isset($xvalue[$eName])) {
						if (isset($attrs['default'])) {
							$xvalue[$eName] = $attrs['default'];
							$this->debug('use default value of ' . $xvalue[$eName] . ' for element ' . $eName);
						}
					}
										if (isset($optionals)
					    && (!isset($xvalue[$eName]))
						&& ( (!isset($attrs['nillable'])) || $attrs['nillable'] != 'true')
						){
						if (isset($attrs['minOccurs']) && $attrs['minOccurs'] <> '0') {
							$this->debug("apparent error: no value provided for element $eName with minOccurs=" . $attrs['minOccurs']);
						}
												$this->debug("no value provided for complexType element $eName and element is not nillable, so serialize nothing");
					} else {
												if (isset($xvalue[$eName])) {
						    $v = $xvalue[$eName];
						} else {
						    $v = null;
						}
						if (isset($attrs['form'])) {
							$unqualified = ($attrs['form'] == 'unqualified');
						} else {
							$unqualified = false;
						}
						if (isset($attrs['maxOccurs']) && ($attrs['maxOccurs'] == 'unbounded' || $attrs['maxOccurs'] > 1) && isset($v) && is_array($v) && $this->isArraySimpleOrStruct($v) == 'arraySimple') {
							$vv = $v;
							foreach ($vv as $k => $v) {
								if (isset($attrs['type']) || isset($attrs['ref'])) {
																	    $xml .= $this->serializeType($eName, isset($attrs['type']) ? $attrs['type'] : $attrs['ref'], $v, $use, $encodingStyle, $unqualified);
								} else {
																	    $this->debug("calling serialize_val() for $v, $eName, false, false, false, false, $use");
								    $xml .= $this->serialize_val($v, $eName, false, false, false, false, $use);
								}
							}
						} else {
							if (is_null($v) && isset($attrs['minOccurs']) && $attrs['minOccurs'] == '0') {
															} elseif (is_null($v) && isset($attrs['nillable']) && $attrs['nillable'] == 'true') {
															    $xml .= $this->serializeType($eName, isset($attrs['type']) ? $attrs['type'] : $attrs['ref'], $v, $use, $encodingStyle, $unqualified);
							} elseif (isset($attrs['type']) || isset($attrs['ref'])) {
															    $xml .= $this->serializeType($eName, isset($attrs['type']) ? $attrs['type'] : $attrs['ref'], $v, $use, $encodingStyle, $unqualified);
							} else {
															    $this->debug("calling serialize_val() for $v, $eName, false, false, false, false, $use");
							    $xml .= $this->serialize_val($v, $eName, false, false, false, false, $use);
							}
						}
					}
				}
			} else {
				$this->debug("no elements to serialize for XML Schema type $ns:$uqType");
			}
			return $xml;
		}


		function addComplexType($name,$typeClass='complexType',$phpType='array',$compositor='',$restrictionBase='',$elements=array(),$attrs=array(),$arrayType='') {
			if (count($elements) > 0) {
				$eElements = array();
		    	foreach($elements as $n => $e){
		            		            $ee = array();
		            foreach ($e as $k => $v) {
			            $k = strpos($k,':') ? $this->expandQname($k) : $k;
			            $v = strpos($v,':') ? $this->expandQname($v) : $v;
			            $ee[$k] = $v;
			    	}
		    		$eElements[$n] = $ee;
		    	}
		    	$elements = $eElements;
			}

			if (count($attrs) > 0) {
		    	foreach($attrs as $n => $a){
		            		            foreach ($a as $k => $v) {
			            $k = strpos($k,':') ? $this->expandQname($k) : $k;
			            $v = strpos($v,':') ? $this->expandQname($v) : $v;
			            $aa[$k] = $v;
			    	}
		    		$eAttrs[$n] = $aa;
		    	}
		    	$attrs = $eAttrs;
			}

			$restrictionBase = strpos($restrictionBase,':') ? $this->expandQname($restrictionBase) : $restrictionBase;
			$arrayType = strpos($arrayType,':') ? $this->expandQname($arrayType) : $arrayType;

			$typens = isset($this->namespaces['types']) ? $this->namespaces['types'] : $this->namespaces['tns'];
			$this->schemas[$typens][0]->addComplexType($name,$typeClass,$phpType,$compositor,$restrictionBase,$elements,$attrs,$arrayType);
		}


		function addSimpleType($name, $restrictionBase='', $typeClass='simpleType', $phpType='scalar', $enumeration=array()) {
			$restrictionBase = strpos($restrictionBase,':') ? $this->expandQname($restrictionBase) : $restrictionBase;

			$typens = isset($this->namespaces['types']) ? $this->namespaces['types'] : $this->namespaces['tns'];
			$this->schemas[$typens][0]->addSimpleType($name, $restrictionBase, $typeClass, $phpType, $enumeration);
		}


		function addElement($attrs) {
			$typens = isset($this->namespaces['types']) ? $this->namespaces['types'] : $this->namespaces['tns'];
			$this->schemas[$typens][0]->addElement($attrs);
		}


		function addOperation($name, $in = false, $out = false, $namespace = false, $soapaction = false, $style = 'rpc', $use = 'encoded', $documentation = '', $encodingStyle = ''){
			if ($use == 'encoded' && $encodingStyle == '') {
				$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
			}

			if ($style == 'document') {
				$elements = array();
				foreach ($in as $n => $t) {
					$elements[$n] = array('name' => $n, 'type' => $t, 'form' => 'unqualified');
				}
				$this->addComplexType($name . 'RequestType', 'complexType', 'struct', 'all', '', $elements);
				$this->addElement(array('name' => $name, 'type' => $name . 'RequestType'));
				$in = array('parameters' => 'tns:' . $name . '^');

				$elements = array();
				foreach ($out as $n => $t) {
					$elements[$n] = array('name' => $n, 'type' => $t, 'form' => 'unqualified');
				}
				$this->addComplexType($name . 'ResponseType', 'complexType', 'struct', 'all', '', $elements);
				$this->addElement(array('name' => $name . 'Response', 'type' => $name . 'ResponseType', 'form' => 'qualified'));
				$out = array('parameters' => 'tns:' . $name . 'Response' . '^');
			}

						$this->bindings[ $this->serviceName . 'Binding' ]['operations'][$name] =
			array(
			'name' => $name,
			'binding' => $this->serviceName . 'Binding',
			'endpoint' => $this->endpoint,
			'soapAction' => $soapaction,
			'style' => $style,
			'input' => array(
				'use' => $use,
				'namespace' => $namespace,
				'encodingStyle' => $encodingStyle,
				'message' => $name . 'Request',
				'parts' => $in),
			'output' => array(
				'use' => $use,
				'namespace' => $namespace,
				'encodingStyle' => $encodingStyle,
				'message' => $name . 'Response',
				'parts' => $out),
			'namespace' => $namespace,
			'transport' => 'http://schemas.xmlsoap.org/soap/http',
			'documentation' => $documentation);
									if($in)
			{
				foreach($in as $pName => $pType)
				{
					if(strpos($pType,':')) {
						$pType = $this->getNamespaceFromPrefix($this->getPrefix($pType)).":".$this->getLocalPart($pType);
					}
					$this->messages[$name.'Request'][$pName] = $pType;
				}
			} else {
	            $this->messages[$name.'Request']= '0';
	        }
			if($out)
			{
				foreach($out as $pName => $pType)
				{
					if(strpos($pType,':')) {
						$pType = $this->getNamespaceFromPrefix($this->getPrefix($pType)).":".$this->getLocalPart($pType);
					}
					$this->messages[$name.'Response'][$pName] = $pType;
				}
			} else {
	            $this->messages[$name.'Response']= '0';
	        }
			return true;
		}
	}

	class nusoap_parser extends nusoap_base {

		var $xml = '';
		var $xml_encoding = '';
		var $method = '';
		var $root_struct = '';
		var $root_struct_name = '';
		var $root_struct_namespace = '';
		var $root_header = '';
	    var $document = '';							var $status = '';
		var $position = 0;
		var $depth = 0;
		var $default_namespace = '';
		var $namespaces = array();
		var $message = array();
	    var $parent = '';
		var $fault = false;
		var $fault_code = '';
		var $fault_str = '';
		var $fault_detail = '';
		var $depth_array = array();
		var $debug_flag = true;
		var $soapresponse = NULL;			var $soapheader = NULL;				var $responseHeaders = '';			var $body_position = 0;
						var $ids = array();
				var $multirefs = array();
				var $decode_utf8 = true;


		function nusoap_parser($xml,$encoding='UTF-8',$method='',$decode_utf8=true){
			parent::nusoap_base();
			$this->xml = $xml;
			$this->xml_encoding = $encoding;
			$this->method = $method;
			$this->decode_utf8 = $decode_utf8;

						if(!empty($xml)){
								$pos_xml = strpos($xml, '<?xml');
				if ($pos_xml !== FALSE) {
					$xml_decl = substr($xml, $pos_xml, strpos($xml, '?>', $pos_xml + 2) - $pos_xml + 1);
					if (preg_match("/encoding=[\"']([^\"']*)[\"']/", $xml_decl, $res)) {
						$xml_encoding = $res[1];
						if (strtoupper($xml_encoding) != $encoding) {
							$err = "Charset from HTTP Content-Type '" . $encoding . "' does not match encoding from XML declaration '" . $xml_encoding . "'";
							$this->debug($err);
							if ($encoding != 'ISO-8859-1' || strtoupper($xml_encoding) != 'UTF-8') {
								$this->setError($err);
								return;
							}
													} else {
							$this->debug('Charset from HTTP Content-Type matches encoding from XML declaration');
						}
					} else {
						$this->debug('No encoding specified in XML declaration');
					}
				} else {
					$this->debug('No XML declaration');
				}
				$this->debug('Entering nusoap_parser(), length='.strlen($xml).', encoding='.$encoding);
								$this->parser = xml_parser_create($this->xml_encoding);
												xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
				xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $this->xml_encoding);
								xml_set_object($this->parser, $this);
								xml_set_element_handler($this->parser, 'start_element','end_element');
				xml_set_character_data_handler($this->parser,'character_data');

								if(!xml_parse($this->parser,$xml,true)){
				    				    $err = sprintf('XML error parsing SOAP payload on line %d: %s',
				    xml_get_current_line_number($this->parser),
				    xml_error_string(xml_get_error_code($this->parser)));
					$this->debug($err);
					$this->debug("XML payload:\n" . $xml);
					$this->setError($err);
				} else {
					$this->debug('in nusoap_parser ctor, message:');
					$this->appendDebug($this->varDump($this->message));
					$this->debug('parsed successfully, found root struct: '.$this->root_struct.' of name '.$this->root_struct_name);
										$this->soapresponse = $this->message[$this->root_struct]['result'];
										if($this->root_header != '' && isset($this->message[$this->root_header]['result'])){
						$this->soapheader = $this->message[$this->root_header]['result'];
					}
										if(sizeof($this->multirefs) > 0){
						foreach($this->multirefs as $id => $hrefs){
							$this->debug('resolving multirefs for id: '.$id);
							$idVal = $this->buildVal($this->ids[$id]);
							if (is_array($idVal) && isset($idVal['!id'])) {
								unset($idVal['!id']);
							}
							foreach($hrefs as $refPos => $ref){
								$this->debug('resolving href at pos '.$refPos);
								$this->multirefs[$id][$refPos] = $idVal;
							}
						}
					}
				}
				xml_parser_free($this->parser);
			} else {
				$this->debug('xml was empty, didn\'t parse!');
				$this->setError('xml was empty, didn\'t parse!');
			}
		}


		function start_element($parser, $name, $attrs) {
									$pos = $this->position++;
						$this->message[$pos] = array('pos' => $pos,'children'=>'','cdata'=>'');
									$this->message[$pos]['depth'] = $this->depth++;

						if($pos != 0){
				$this->message[$this->parent]['children'] .= '|'.$pos;
			}
						$this->message[$pos]['parent'] = $this->parent;
						$this->parent = $pos;
						$this->depth_array[$this->depth] = $pos;
						if(strpos($name,':')){
								$prefix = substr($name,0,strpos($name,':'));
								$name = substr(strstr($name,':'),1);
			}
						if ($name == 'Envelope' && $this->status == '') {
				$this->status = 'envelope';
			} elseif ($name == 'Header' && $this->status == 'envelope') {
				$this->root_header = $pos;
				$this->status = 'header';
			} elseif ($name == 'Body' && $this->status == 'envelope'){
				$this->status = 'body';
				$this->body_position = $pos;
						} elseif($this->status == 'body' && $pos == ($this->body_position+1)) {
				$this->status = 'method';
				$this->root_struct_name = $name;
				$this->root_struct = $pos;
				$this->message[$pos]['type'] = 'struct';
				$this->debug("found root struct $this->root_struct_name, pos $this->root_struct");
			}
						$this->message[$pos]['status'] = $this->status;
						$this->message[$pos]['name'] = htmlspecialchars($name);
						$this->message[$pos]['attrs'] = $attrs;

				        $attstr = '';
			foreach($attrs as $key => $value){
	        	$key_prefix = $this->getPrefix($key);
				$key_localpart = $this->getLocalPart($key);
					            if($key_prefix == 'xmlns'){
					if(preg_match('/^http:\/\/www.w3.org\/[0-9]{4}\/XMLSchema$/',$value)){
						$this->XMLSchemaVersion = $value;
						$this->namespaces['xsd'] = $this->XMLSchemaVersion;
						$this->namespaces['xsi'] = $this->XMLSchemaVersion.'-instance';
					}
	                $this->namespaces[$key_localpart] = $value;
										if($name == $this->root_struct_name){
						$this->methodNamespace = $value;
					}
					        } elseif($key_localpart == 'type'){
	        		if (isset($this->message[$pos]['type']) && $this->message[$pos]['type'] == 'array') {
	        				        		} else {
		            	$value_prefix = $this->getPrefix($value);
		                $value_localpart = $this->getLocalPart($value);
						$this->message[$pos]['type'] = $value_localpart;
						$this->message[$pos]['typePrefix'] = $value_prefix;
		                if(isset($this->namespaces[$value_prefix])){
		                	$this->message[$pos]['type_namespace'] = $this->namespaces[$value_prefix];
		                } else if(isset($attrs['xmlns:'.$value_prefix])) {
							$this->message[$pos]['type_namespace'] = $attrs['xmlns:'.$value_prefix];
		                }
											}
				} elseif($key_localpart == 'arrayType'){
					$this->message[$pos]['type'] = 'array';

					$expr = '/([A-Za-z0-9_]+):([A-Za-z]+[A-Za-z0-9_]+)\[([0-9]+),?([0-9]*)\]/';
					if(preg_match($expr,$value,$regs)){
						$this->message[$pos]['typePrefix'] = $regs[1];
						$this->message[$pos]['arrayTypePrefix'] = $regs[1];
		                if (isset($this->namespaces[$regs[1]])) {
		                	$this->message[$pos]['arrayTypeNamespace'] = $this->namespaces[$regs[1]];
		                } else if (isset($attrs['xmlns:'.$regs[1]])) {
							$this->message[$pos]['arrayTypeNamespace'] = $attrs['xmlns:'.$regs[1]];
		                }
						$this->message[$pos]['arrayType'] = $regs[2];
						$this->message[$pos]['arraySize'] = $regs[3];
						$this->message[$pos]['arrayCols'] = $regs[4];
					}
								} elseif ($key_localpart == 'nil'){
					$this->message[$pos]['nil'] = ($value == 'true' || $value == '1');
								} elseif ($key != 'href' && $key != 'xmlns' && $key_localpart != 'encodingStyle' && $key_localpart != 'root') {
					$this->message[$pos]['xattrs']['!' . $key] = $value;
				}

				if ($key == 'xmlns') {
					$this->default_namespace = $value;
				}
								if($key == 'id'){
					$this->ids[$value] = $pos;
				}
								if($key_localpart == 'root' && $value == 1){
					$this->status = 'method';
					$this->root_struct_name = $name;
					$this->root_struct = $pos;
					$this->debug("found root struct $this->root_struct_name, pos $pos");
				}
	            	            $attstr .= " $key=\"$value\"";
			}
	        			if(isset($prefix)){
				$this->message[$pos]['namespace'] = $this->namespaces[$prefix];
				$this->default_namespace = $this->namespaces[$prefix];
			} else {
				$this->message[$pos]['namespace'] = $this->default_namespace;
			}
	        if($this->status == 'header'){
	        	if ($this->root_header != $pos) {
		        	$this->responseHeaders .= "<" . (isset($prefix) ? $prefix . ':' : '') . "$name$attstr>";
		        }
	        } elseif($this->root_struct_name != ''){
	        	$this->document .= "<" . (isset($prefix) ? $prefix . ':' : '') . "$name$attstr>";
	        }
		}


		function end_element($parser, $name) {
						$pos = $this->depth_array[$this->depth--];

	        			if(strpos($name,':')){
								$prefix = substr($name,0,strpos($name,':'));
								$name = substr(strstr($name,':'),1);
			}

						if(isset($this->body_position) && $pos > $this->body_position){
								if(isset($this->message[$pos]['attrs']['href'])){
										$id = substr($this->message[$pos]['attrs']['href'],1);
										$this->multirefs[$id][$pos] = 'placeholder';
										$this->message[$pos]['result'] =& $this->multirefs[$id][$pos];
	            				} elseif($this->message[$pos]['children'] != ''){
										if(!isset($this->message[$pos]['result'])){
						$this->message[$pos]['result'] = $this->buildVal($pos);
					}
								} elseif (isset($this->message[$pos]['xattrs'])) {
					if (isset($this->message[$pos]['nil']) && $this->message[$pos]['nil']) {
						$this->message[$pos]['xattrs']['!'] = null;
					} elseif (isset($this->message[$pos]['cdata']) && trim($this->message[$pos]['cdata']) != '') {
		            	if (isset($this->message[$pos]['type'])) {
							$this->message[$pos]['xattrs']['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
						} else {
							$parent = $this->message[$pos]['parent'];
							if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
								$this->message[$pos]['xattrs']['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
							} else {
								$this->message[$pos]['xattrs']['!'] = $this->message[$pos]['cdata'];
							}
						}
					}
					$this->message[$pos]['result'] = $this->message[$pos]['xattrs'];
								} else {
	            						if (isset($this->message[$pos]['nil']) && $this->message[$pos]['nil']) {
						$this->message[$pos]['xattrs']['!'] = null;
					} elseif (isset($this->message[$pos]['type'])) {
						$this->message[$pos]['result'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
					} else {
						$parent = $this->message[$pos]['parent'];
						if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
							$this->message[$pos]['result'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
						} else {
							$this->message[$pos]['result'] = $this->message[$pos]['cdata'];
						}
					}


				}
			}

	        	        if($this->status == 'header'){
	        	if ($this->root_header != $pos) {
		        	$this->responseHeaders .= "</" . (isset($prefix) ? $prefix . ':' : '') . "$name>";
		        }
	        } elseif($pos >= $this->root_struct){
	        	$this->document .= "</" . (isset($prefix) ? $prefix . ':' : '') . "$name>";
	        }
						if ($pos == $this->root_struct){
				$this->status = 'body';
				$this->root_struct_namespace = $this->message[$pos]['namespace'];
			} elseif ($pos == $this->root_header) {
				$this->status = 'envelope';
			} elseif ($name == 'Body' && $this->status == 'body') {
				$this->status = 'envelope';
			} elseif ($name == 'Header' && $this->status == 'header') { 				$this->status = 'envelope';
			} elseif ($name == 'Envelope' && $this->status == 'envelope') {
				$this->status = '';
			}
						$this->parent = $this->message[$pos]['parent'];
		}


		function character_data($parser, $data){
			$pos = $this->depth_array[$this->depth];
			if ($this->xml_encoding=='UTF-8'){
																if($this->decode_utf8){
					$data = utf8_decode($data);
				}
			}
	        $this->message[$pos]['cdata'] .= $data;
	        	        if($this->status == 'header'){
	        	$this->responseHeaders .= $data;
	        } else {
	        	$this->document .= $data;
	        }
		}


		function get_response(){
			return $this->soapresponse;
		}


		function get_soapbody(){
			return $this->soapresponse;
		}


		function get_soapheader(){
			return $this->soapheader;
		}


		function getHeaders(){
		    return $this->responseHeaders;
		}


		function decodeSimple($value, $type, $typens) {
						if ((!isset($type)) || $type == 'string' || $type == 'long' || $type == 'unsignedLong') {
				return (string) $value;
			}
			if ($type == 'int' || $type == 'integer' || $type == 'short' || $type == 'byte') {
				return (int) $value;
			}
			if ($type == 'float' || $type == 'double' || $type == 'decimal') {
				return (double) $value;
			}
			if ($type == 'boolean') {
				if (strtolower($value) == 'false' || strtolower($value) == 'f') {
					return false;
				}
				return (boolean) $value;
			}
			if ($type == 'base64' || $type == 'base64Binary') {
				$this->debug('Decode base64 value');
				return base64_decode($value);
			}
						if ($type == 'nonPositiveInteger' || $type == 'negativeInteger'
				|| $type == 'nonNegativeInteger' || $type == 'positiveInteger'
				|| $type == 'unsignedInt'
				|| $type == 'unsignedShort' || $type == 'unsignedByte') {
				return (int) $value;
			}
						if ($type == 'array') {
				return array();
			}
						return (string) $value;
		}


		function buildVal($pos){
			if(!isset($this->message[$pos]['type'])){
				$this->message[$pos]['type'] = '';
			}
			$this->debug('in buildVal() for '.$this->message[$pos]['name']."(pos $pos) of type ".$this->message[$pos]['type']);
						if($this->message[$pos]['children'] != ''){
				$this->debug('in buildVal, there are children');
				$children = explode('|',$this->message[$pos]['children']);
				array_shift($children); 								if(isset($this->message[$pos]['arrayCols']) && $this->message[$pos]['arrayCols'] != ''){
	            	$r=0; 	            	$c=0; 	            	foreach($children as $child_pos){
						$this->debug("in buildVal, got an MD array element: $r, $c");
						$params[$r][] = $this->message[$child_pos]['result'];
					    $c++;
					    if($c == $this->message[$pos]['arrayCols']){
					    	$c = 0;
							$r++;
					    }
	                }
	            				} elseif($this->message[$pos]['type'] == 'array' || $this->message[$pos]['type'] == 'Array'){
	                $this->debug('in buildVal, adding array '.$this->message[$pos]['name']);
	                foreach($children as $child_pos){
	                	$params[] = &$this->message[$child_pos]['result'];
	                }
	            	            } elseif($this->message[$pos]['type'] == 'Map' && $this->message[$pos]['type_namespace'] == 'http://xml.apache.org/xml-soap'){
	                $this->debug('in buildVal, Java Map '.$this->message[$pos]['name']);
	                foreach($children as $child_pos){
	                	$kv = explode("|",$this->message[$child_pos]['children']);
	                   	$params[$this->message[$kv[1]]['result']] = &$this->message[$kv[2]]['result'];
	                }
	            	            			    } else {
		    			                $this->debug('in buildVal, adding Java Vector or generic compound type '.$this->message[$pos]['name']);
					if ($this->message[$pos]['type'] == 'Vector' && $this->message[$pos]['type_namespace'] == 'http://xml.apache.org/xml-soap') {
						$notstruct = 1;
					} else {
						$notstruct = 0;
		            }
	            		            	foreach($children as $child_pos){
	            		if($notstruct){
	            			$params[] = &$this->message[$child_pos]['result'];
	            		} else {
	            			if (isset($params[$this->message[$child_pos]['name']])) {
	            					            				if ((!is_array($params[$this->message[$child_pos]['name']])) || (!isset($params[$this->message[$child_pos]['name']][0]))) {
	            					$params[$this->message[$child_pos]['name']] = array($params[$this->message[$child_pos]['name']]);
	            				}
	            				$params[$this->message[$child_pos]['name']][] = &$this->message[$child_pos]['result'];
	            			} else {
						    	$params[$this->message[$child_pos]['name']] = &$this->message[$child_pos]['result'];
						    }
	                	}
	                }
				}
				if (isset($this->message[$pos]['xattrs'])) {
	                $this->debug('in buildVal, handling attributes');
					foreach ($this->message[$pos]['xattrs'] as $n => $v) {
						$params[$n] = $v;
					}
				}
								if (isset($this->message[$pos]['cdata']) && trim($this->message[$pos]['cdata']) != '') {
	                $this->debug('in buildVal, handling simpleContent');
	            	if (isset($this->message[$pos]['type'])) {
						$params['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
					} else {
						$parent = $this->message[$pos]['parent'];
						if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
							$params['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
						} else {
							$params['!'] = $this->message[$pos]['cdata'];
						}
					}
				}
				$ret = is_array($params) ? $params : array();
				$this->debug('in buildVal, return:');
				$this->appendDebug($this->varDump($ret));
				return $ret;
			} else {
	        	$this->debug('in buildVal, no children, building scalar');
				$cdata = isset($this->message[$pos]['cdata']) ? $this->message[$pos]['cdata'] : '';
	        	if (isset($this->message[$pos]['type'])) {
					$ret = $this->decodeSimple($cdata, $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
					$this->debug("in buildVal, return: $ret");
					return $ret;
				}
				$parent = $this->message[$pos]['parent'];
				if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
					$ret = $this->decodeSimple($cdata, $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
					$this->debug("in buildVal, return: $ret");
					return $ret;
				}
	           	$ret = $this->message[$pos]['cdata'];
				$this->debug("in buildVal, return: $ret");
	           	return $ret;
			}
		}
	}


	class soap_parser extends nusoap_parser {
	}

	?><?php




	class nusoap_client extends nusoap_base  {

		var $username = '';						var $password = '';						var $authtype = '';						var $certRequest = array();				var $requestHeaders = false;			var $responseHeaders = '';				var $responseHeader = NULL;				var $document = '';						var $endpoint;
		var $forceEndpoint = '';			    var $proxyhost = '';
	    var $proxyport = '';
		var $proxyusername = '';
		var $proxypassword = '';
		var $portName = '';					    var $xml_encoding = '';					var $http_encoding = false;
		var $timeout = 0;						var $response_timeout = 30;				var $endpointType = '';					var $persistentConnection = false;
		var $defaultRpcParams = false;			var $request = '';						var $response = '';						var $responseData = '';					var $cookies = array();				    var $decode_utf8 = true;				var $operations = array();				var $curl_options = array();			var $bindingType = '';					var $use_curl = false;


		var $fault;

		var $faultcode;

		var $faultstring;

		var $faultdetail;


		function nusoap_client($endpoint,$wsdl = false,$proxyhost = false,$proxyport = false,$proxyusername = false, $proxypassword = false, $timeout = 0, $response_timeout = 30, $portName = ''){
			parent::nusoap_base();
			$this->endpoint = $endpoint;
			$this->proxyhost = $proxyhost;
			$this->proxyport = $proxyport;
			$this->proxyusername = $proxyusername;
			$this->proxypassword = $proxypassword;
			$this->timeout = $timeout;
			$this->response_timeout = $response_timeout;
			$this->portName = $portName;

			$this->debug("ctor wsdl=$wsdl timeout=$timeout response_timeout=$response_timeout");
			$this->appendDebug('endpoint=' . $this->varDump($endpoint));

						if($wsdl){
				if (is_object($endpoint) && (get_class($endpoint) == 'wsdl')) {
					$this->wsdl = $endpoint;
					$this->endpoint = $this->wsdl->wsdl;
					$this->wsdlFile = $this->endpoint;
					$this->debug('existing wsdl instance created from ' . $this->endpoint);
					$this->checkWSDL();
				} else {
					$this->wsdlFile = $this->endpoint;
					$this->wsdl = null;
					$this->debug('will use lazy evaluation of wsdl from ' . $this->endpoint);
				}
				$this->endpointType = 'wsdl';
			} else {
				$this->debug("instantiate SOAP with endpoint at $endpoint");
				$this->endpointType = 'soap';
			}
		}


		function call($operation,$params=array(),$namespace='http://tempuri.org',$soapAction='',$headers=false,$rpcParams=null,$style='rpc',$use='encoded'){
			$this->operation = $operation;
			$this->fault = false;
			$this->setError('');
			$this->request = '';
			$this->response = '';
			$this->responseData = '';
			$this->faultstring = '';
			$this->faultcode = '';
			$this->opData = array();

			$this->debug("call: operation=$operation, namespace=$namespace, soapAction=$soapAction, rpcParams=$rpcParams, style=$style, use=$use, endpointType=$this->endpointType");
			$this->appendDebug('params=' . $this->varDump($params));
			$this->appendDebug('headers=' . $this->varDump($headers));
			if ($headers) {
				$this->requestHeaders = $headers;
			}
			if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
				$this->loadWSDL();
				if ($this->getError())
					return false;
			}
						if($this->endpointType == 'wsdl' && $opData = $this->getOperationData($operation)){
								$this->opData = $opData;
				$this->debug("found operation");
				$this->appendDebug('opData=' . $this->varDump($opData));
				if (isset($opData['soapAction'])) {
					$soapAction = $opData['soapAction'];
				}
				if (! $this->forceEndpoint) {
					$this->endpoint = $opData['endpoint'];
				} else {
					$this->endpoint = $this->forceEndpoint;
				}
				$namespace = isset($opData['input']['namespace']) ? $opData['input']['namespace'] :	$namespace;
				$style = $opData['style'];
				$use = $opData['input']['use'];
								if($namespace != '' && !isset($this->wsdl->namespaces[$namespace])){
					$nsPrefix = 'ns' . rand(1000, 9999);
					$this->wsdl->namespaces[$nsPrefix] = $namespace;
				}
	            $nsPrefix = $this->wsdl->getPrefixFromNamespace($namespace);
								if (is_string($params)) {
					$this->debug("serializing param string for WSDL operation $operation");
					$payload = $params;
				} elseif (is_array($params)) {
					$this->debug("serializing param array for WSDL operation $operation");
					$payload = $this->wsdl->serializeRPCParameters($operation,'input',$params,$this->bindingType);
				} else {
					$this->debug('params must be array or string');
					$this->setError('params must be array or string');
					return false;
				}
	            $usedNamespaces = $this->wsdl->usedNamespaces;
				if (isset($opData['input']['encodingStyle'])) {
					$encodingStyle = $opData['input']['encodingStyle'];
				} else {
					$encodingStyle = '';
				}
				$this->appendDebug($this->wsdl->getDebug());
				$this->wsdl->clearDebug();
				if ($errstr = $this->wsdl->getError()) {
					$this->debug('got wsdl error: '.$errstr);
					$this->setError('wsdl error: '.$errstr);
					return false;
				}
			} elseif($this->endpointType == 'wsdl') {
								$this->appendDebug($this->wsdl->getDebug());
				$this->wsdl->clearDebug();
				$this->setError('operation '.$operation.' not present in WSDL.');
				$this->debug("operation '$operation' not present in WSDL.");
				return false;
			} else {
												$nsPrefix = 'ns' . rand(1000, 9999);
								$payload = '';
				if (is_string($params)) {
					$this->debug("serializing param string for operation $operation");
					$payload = $params;
				} elseif (is_array($params)) {
					$this->debug("serializing param array for operation $operation");
					foreach($params as $k => $v){
						$payload .= $this->serialize_val($v,$k,false,false,false,false,$use);
					}
				} else {
					$this->debug('params must be array or string');
					$this->setError('params must be array or string');
					return false;
				}
				$usedNamespaces = array();
				if ($use == 'encoded') {
					$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
				} else {
					$encodingStyle = '';
				}
			}
						if ($style == 'rpc') {
				if ($use == 'literal') {
					$this->debug("wrapping RPC request with literal method element");
					if ($namespace) {
												$payload = "<$nsPrefix:$operation xmlns:$nsPrefix=\"$namespace\">" .
									$payload .
									"</$nsPrefix:$operation>";
					} else {
						$payload = "<$operation>" . $payload . "</$operation>";
					}
				} else {
					$this->debug("wrapping RPC request with encoded method element");
					if ($namespace) {
						$payload = "<$nsPrefix:$operation xmlns:$nsPrefix=\"$namespace\">" .
									$payload .
									"</$nsPrefix:$operation>";
					} else {
						$payload = "<$operation>" .
									$payload .
									"</$operation>";
					}
				}
			}
						$soapmsg = $this->serializeEnvelope($payload,$this->requestHeaders,$usedNamespaces,$style,$use,$encodingStyle);
			$this->debug("endpoint=$this->endpoint, soapAction=$soapAction, namespace=$namespace, style=$style, use=$use, encodingStyle=$encodingStyle");
			$this->debug('SOAP message length=' . strlen($soapmsg) . ' contents (max 1000 bytes)=' . substr($soapmsg, 0, 1000));
						$return = $this->send($this->getHTTPBody($soapmsg),$soapAction,$this->timeout,$this->response_timeout);
			if($errstr = $this->getError()){
				$this->debug('Error: '.$errstr);
				return false;
			} else {
				$this->return = $return;
				$this->debug('sent message successfully and got a(n) '.gettype($return));
	           	$this->appendDebug('return=' . $this->varDump($return));

								if(is_array($return) && isset($return['faultcode'])){
					$this->debug('got fault');
					$this->setError($return['faultcode'].': '.$return['faultstring']);
					$this->fault = true;
					foreach($return as $k => $v){
						$this->$k = $v;
						$this->debug("$k = $v<br>");
					}
					return $return;
				} elseif ($style == 'document') {
															return $return;
				} else {
										if(is_array($return)){
																		if(sizeof($return) > 1){
							return $return;
						}
												$return = array_shift($return);
						$this->debug('return shifted value: ');
						$this->appendDebug($this->varDump($return));
	           			return $return;
										} else {
						return "";
					}
				}
			}
		}


		function checkWSDL() {
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			$this->debug('checkWSDL');
						if ($errstr = $this->wsdl->getError()) {
				$this->appendDebug($this->wsdl->getDebug());
				$this->wsdl->clearDebug();
				$this->debug('got wsdl error: '.$errstr);
				$this->setError('wsdl error: '.$errstr);
			} elseif ($this->operations = $this->wsdl->getOperations($this->portName, 'soap')) {
				$this->appendDebug($this->wsdl->getDebug());
				$this->wsdl->clearDebug();
				$this->bindingType = 'soap';
				$this->debug('got '.count($this->operations).' operations from wsdl '.$this->wsdlFile.' for binding type '.$this->bindingType);
			} elseif ($this->operations = $this->wsdl->getOperations($this->portName, 'soap12')) {
				$this->appendDebug($this->wsdl->getDebug());
				$this->wsdl->clearDebug();
				$this->bindingType = 'soap12';
				$this->debug('got '.count($this->operations).' operations from wsdl '.$this->wsdlFile.' for binding type '.$this->bindingType);
				$this->debug('**************** WARNING: SOAP 1.2 BINDING *****************');
			} else {
				$this->appendDebug($this->wsdl->getDebug());
				$this->wsdl->clearDebug();
				$this->debug('getOperations returned false');
				$this->setError('no operations defined in the WSDL document!');
			}
		}


		function loadWSDL() {
			$this->debug('instantiating wsdl class with doc: '.$this->wsdlFile);
			$this->wsdl = new wsdl('',$this->proxyhost,$this->proxyport,$this->proxyusername,$this->proxypassword,$this->timeout,$this->response_timeout,$this->curl_options,$this->use_curl);
			$this->wsdl->setCredentials($this->username, $this->password, $this->authtype, $this->certRequest);
			$this->wsdl->fetchWSDL($this->wsdlFile);
			$this->checkWSDL();
		}


		function getOperationData($operation){
			if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
				$this->loadWSDL();
				if ($this->getError())
					return false;
			}
			if(isset($this->operations[$operation])){
				return $this->operations[$operation];
			}
			$this->debug("No data for operation: $operation");
		}


		function send($msg, $soapaction = '', $timeout=0, $response_timeout=30) {
			$this->checkCookies();
						switch(true){
								case preg_match('/^http/',$this->endpoint):
					$this->debug('transporting via HTTP');
					if($this->persistentConnection == true && is_object($this->persistentConnection)){
						$http =& $this->persistentConnection;
					} else {
						$http = new soap_transport_http($this->endpoint, $this->curl_options, $this->use_curl);
						if ($this->persistentConnection) {
							$http->usePersistentConnection();
						}
					}
					$http->setContentType($this->getHTTPContentType(), $this->getHTTPContentTypeCharset());
					$http->setSOAPAction($soapaction);
					if($this->proxyhost && $this->proxyport){
						$http->setProxy($this->proxyhost,$this->proxyport,$this->proxyusername,$this->proxypassword);
					}
	                if($this->authtype != '') {
						$http->setCredentials($this->username, $this->password, $this->authtype, array(), $this->certRequest);
					}
					if($this->http_encoding != ''){
						$http->setEncoding($this->http_encoding);
					}
					$this->debug('sending message, length='.strlen($msg));
					if(preg_match('/^http:/',$this->endpoint)){
											$this->responseData = $http->send($msg,$timeout,$response_timeout,$this->cookies);
					} elseif(preg_match('/^https/',$this->endpoint)){
																			                   																					$this->responseData = $http->sendHTTPS($msg,$timeout,$response_timeout,$this->cookies);
					} else {
						$this->setError('no http/s in endpoint url');
					}
					$this->request = $http->outgoing_payload;
					$this->response = $http->incoming_payload;
					$this->appendDebug($http->getDebug());
					$this->UpdateCookies($http->incoming_cookies);

										if ($this->persistentConnection) {
						$http->clearDebug();
						if (!is_object($this->persistentConnection)) {
							$this->persistentConnection = $http;
						}
					}

					if($err = $http->getError()){
						$this->setError('HTTP Error: '.$err);
						return false;
					} elseif($this->getError()){
						return false;
					} else {
						$this->debug('got response, length='. strlen($this->responseData).' type='.$http->incoming_headers['content-type']);
						return $this->parseResponse($http->incoming_headers, $this->responseData);
					}
				break;
				default:
					$this->setError('no transport found, or selected transport is not yet supported!');
				return false;
				break;
			}
		}


	    function parseResponse($headers, $data) {
			$this->debug('Entering parseResponse() for data of length ' . strlen($data) . ' headers:');
			$this->appendDebug($this->varDump($headers));
	    	if (!isset($headers['content-type'])) {
				$this->setError('Response not of type text/xml (no content-type header)');
				return false;
	    	}
			if (!strstr($headers['content-type'], 'text/xml')) {
				$this->setError('Response not of type text/xml: ' . $headers['content-type']);
				return false;
			}
			if (strpos($headers['content-type'], '=')) {
				$enc = str_replace('"', '', substr(strstr($headers["content-type"], '='), 1));
				$this->debug('Got response encoding: ' . $enc);
				if(preg_match('/^(ISO-8859-1|US-ASCII|UTF-8)$/i',$enc)){
					$this->xml_encoding = strtoupper($enc);
				} else {
					$this->xml_encoding = 'US-ASCII';
				}
			} else {
								$this->xml_encoding = 'ISO-8859-1';
			}
			$this->debug('Use encoding: ' . $this->xml_encoding . ' when creating nusoap_parser');
			$parser = new nusoap_parser($data,$this->xml_encoding,$this->operation,$this->decode_utf8);
						$this->appendDebug($parser->getDebug());
						if($errstr = $parser->getError()){
				$this->setError( $errstr);
								unset($parser);
				return false;
			} else {
								$this->responseHeaders = $parser->getHeaders();
								$this->responseHeader = $parser->get_soapheader();
								$return = $parser->get_soapbody();
	            	            $this->document = $parser->document;
								unset($parser);
								return $return;
			}
		 }


		function setCurlOption($option, $value) {
			$this->debug("setCurlOption option=$option, value=");
			$this->appendDebug($this->varDump($value));
			$this->curl_options[$option] = $value;
		}


		function setEndpoint($endpoint) {
			$this->debug("setEndpoint(\"$endpoint\")");
			$this->forceEndpoint = $endpoint;
		}


		function setHeaders($headers){
			$this->debug("setHeaders headers=");
			$this->appendDebug($this->varDump($headers));
			$this->requestHeaders = $headers;
		}


		function getHeaders(){
			return $this->responseHeaders;
		}


		function getHeader(){
			return $this->responseHeader;
		}


		function setHTTPProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '') {
			$this->proxyhost = $proxyhost;
			$this->proxyport = $proxyport;
			$this->proxyusername = $proxyusername;
			$this->proxypassword = $proxypassword;
		}


		function setCredentials($username, $password, $authtype = 'basic', $certRequest = array()) {
			$this->debug("setCredentials username=$username authtype=$authtype certRequest=");
			$this->appendDebug($this->varDump($certRequest));
			$this->username = $username;
			$this->password = $password;
			$this->authtype = $authtype;
			$this->certRequest = $certRequest;
		}


		function setHTTPEncoding($enc='gzip, deflate'){
			$this->debug("setHTTPEncoding(\"$enc\")");
			$this->http_encoding = $enc;
		}


		function setUseCURL($use) {
			$this->debug("setUseCURL($use)");
			$this->use_curl = $use;
		}


		function useHTTPPersistentConnection(){
			$this->debug("useHTTPPersistentConnection");
			$this->persistentConnection = true;
		}


		function getDefaultRpcParams() {
			return $this->defaultRpcParams;
		}


		function setDefaultRpcParams($rpcParams) {
			$this->defaultRpcParams = $rpcParams;
		}


		function getProxy() {
			$r = rand();
			$evalStr = $this->_getProxyClassCode($r);
						if ($this->getError()) {
				$this->debug("Error from _getProxyClassCode, so return NULL");
				return null;
			}
						eval($evalStr);
						eval("\$proxy = new nusoap_proxy_$r('');");
						$proxy->endpointType = 'wsdl';
			$proxy->wsdlFile = $this->wsdlFile;
			$proxy->wsdl = $this->wsdl;
			$proxy->operations = $this->operations;
			$proxy->defaultRpcParams = $this->defaultRpcParams;
						$proxy->soap_defencoding = $this->soap_defencoding;
			$proxy->username = $this->username;
			$proxy->password = $this->password;
			$proxy->authtype = $this->authtype;
			$proxy->certRequest = $this->certRequest;
			$proxy->requestHeaders = $this->requestHeaders;
			$proxy->endpoint = $this->endpoint;
			$proxy->forceEndpoint = $this->forceEndpoint;
			$proxy->proxyhost = $this->proxyhost;
			$proxy->proxyport = $this->proxyport;
			$proxy->proxyusername = $this->proxyusername;
			$proxy->proxypassword = $this->proxypassword;
			$proxy->http_encoding = $this->http_encoding;
			$proxy->timeout = $this->timeout;
			$proxy->response_timeout = $this->response_timeout;
			$proxy->persistentConnection = &$this->persistentConnection;
			$proxy->decode_utf8 = $this->decode_utf8;
			$proxy->curl_options = $this->curl_options;
			$proxy->bindingType = $this->bindingType;
			$proxy->use_curl = $this->use_curl;
			return $proxy;
		}


		function _getProxyClassCode($r) {
			$this->debug("in getProxy endpointType=$this->endpointType");
			$this->appendDebug("wsdl=" . $this->varDump($this->wsdl));
			if ($this->endpointType != 'wsdl') {
				$evalStr = 'A proxy can only be created for a WSDL client';
				$this->setError($evalStr);
				$evalStr = "echo \"$evalStr\";";
				return $evalStr;
			}
			if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
				$this->loadWSDL();
				if ($this->getError()) {
					return "echo \"" . $this->getError() . "\";";
				}
			}
			$evalStr = '';
			foreach ($this->operations as $operation => $opData) {
				if ($operation != '') {
										if (sizeof($opData['input']['parts']) > 0) {
						$paramStr = '';
						$paramArrayStr = '';
						$paramCommentStr = '';
						foreach ($opData['input']['parts'] as $name => $type) {
							$paramStr .= "\$$name, ";
							$paramArrayStr .= "'$name' => \$$name, ";
							$paramCommentStr .= "$type \$$name, ";
						}
						$paramStr = substr($paramStr, 0, strlen($paramStr)-2);
						$paramArrayStr = substr($paramArrayStr, 0, strlen($paramArrayStr)-2);
						$paramCommentStr = substr($paramCommentStr, 0, strlen($paramCommentStr)-2);
					} else {
						$paramStr = '';
						$paramArrayStr = '';
						$paramCommentStr = 'void';
					}
					$opData['namespace'] = !isset($opData['namespace']) ? 'http://testuri.com' : $opData['namespace'];
					$evalStr .= "// $paramCommentStr
		function " . str_replace('.', '__', $operation) . "($paramStr) {
			\$params = array($paramArrayStr);
			return \$this->call('$operation', \$params, '".$opData['namespace']."', '".(isset($opData['soapAction']) ? $opData['soapAction'] : '')."');
		}
		";
					unset($paramStr);
					unset($paramCommentStr);
				}
			}
			$evalStr = 'class nusoap_proxy_'.$r.' extends nusoap_client {
		'.$evalStr.'
	}';
			return $evalStr;
		}


		function getProxyClassCode() {
			$r = rand();
			return $this->_getProxyClassCode($r);
		}


		function getHTTPBody($soapmsg) {
			return $soapmsg;
		}


		function getHTTPContentType() {
			return 'text/xml';
		}


		function getHTTPContentTypeCharset() {
			return $this->soap_defencoding;
		}


	    function decodeUTF8($bool){
			$this->decode_utf8 = $bool;
			return true;
	    }


		function setCookie($name, $value) {
			if (strlen($name) == 0) {
				return false;
			}
			$this->cookies[] = array('name' => $name, 'value' => $value);
			return true;
		}


		function getCookies() {
			return $this->cookies;
		}


		function checkCookies() {
			if (sizeof($this->cookies) == 0) {
				return true;
			}
			$this->debug('checkCookie: check ' . sizeof($this->cookies) . ' cookies');
			$curr_cookies = $this->cookies;
			$this->cookies = array();
			foreach ($curr_cookies as $cookie) {
				if (! is_array($cookie)) {
					$this->debug('Remove cookie that is not an array');
					continue;
				}
				if ((isset($cookie['expires'])) && (! empty($cookie['expires']))) {
					if (strtotime($cookie['expires']) > time()) {
						$this->cookies[] = $cookie;
					} else {
						$this->debug('Remove expired cookie ' . $cookie['name']);
					}
				} else {
					$this->cookies[] = $cookie;
				}
			}
			$this->debug('checkCookie: '.sizeof($this->cookies).' cookies left in array');
			return true;
		}


		function UpdateCookies($cookies) {
			if (sizeof($this->cookies) == 0) {
								if (sizeof($cookies) > 0) {
					$this->debug('Setting new cookie(s)');
					$this->cookies = $cookies;
				}
				return true;
			}
			if (sizeof($cookies) == 0) {
								return true;
			}
						foreach ($cookies as $newCookie) {
				if (!is_array($newCookie)) {
					continue;
				}
				if ((!isset($newCookie['name'])) || (!isset($newCookie['value']))) {
					continue;
				}
				$newName = $newCookie['name'];

				$found = false;
				for ($i = 0; $i < count($this->cookies); $i++) {
					$cookie = $this->cookies[$i];
					if (!is_array($cookie)) {
						continue;
					}
					if (!isset($cookie['name'])) {
						continue;
					}
					if ($newName != $cookie['name']) {
						continue;
					}
					$newDomain = isset($newCookie['domain']) ? $newCookie['domain'] : 'NODOMAIN';
					$domain = isset($cookie['domain']) ? $cookie['domain'] : 'NODOMAIN';
					if ($newDomain != $domain) {
						continue;
					}
					$newPath = isset($newCookie['path']) ? $newCookie['path'] : 'NOPATH';
					$path = isset($cookie['path']) ? $cookie['path'] : 'NOPATH';
					if ($newPath != $path) {
						continue;
					}
					$this->cookies[$i] = $newCookie;
					$found = true;
					$this->debug('Update cookie ' . $newName . '=' . $newCookie['value']);
					break;
				}
				if (! $found) {
					$this->debug('Add cookie ' . $newName . '=' . $newCookie['value']);
					$this->cookies[] = $newCookie;
				}
			}
			return true;
		}
	}

	if (!extension_loaded('soap')) {
		class soapclient extends nusoap_client {
		}
	}

} // END class_exists('nusoap_base')

?>
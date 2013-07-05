<?php
/**
* ShoppPurchaseThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppPurchaseThemeAPI
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_purchase_item_input_data','esc_html');
add_filter('shopp_purchase_item_input_data', 'wptexturize');
add_filter('shopp_purchase_item_input_data', 'convert_chars');
add_filter('shopp_purchase_item_input_data', 'wpautop');

add_filter('shopp_purchase_item_input_data','esc_html');
add_filter('shopp_purchase_item_input_data', 'wptexturize');
add_filter('shopp_purchase_item_input_data', 'convert_chars');
add_filter('shopp_purchase_item_input_data', 'wpautop');

add_filter('shopp_purchase_order_data', 'esc_html');
add_filter('shopp_purchase_order_data', 'wptexturize');
add_filter('shopp_purchase_order_data', 'convert_chars');
add_filter('shopp_purchase_order_data', 'wpautop');

/**
 * Provides shopp('purchase') theme API functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppPurchaseThemeAPI implements ShoppAPI {
	static $context = 'Purchase';
	static $register = array(
		'address' => 'address',
		'card' => 'card',
		'cardtype' => 'card_type',
		'city' => 'city',
		'company' => 'company',
		'country' => 'country',
		'customer' => 'customer',
		'data' => 'data',
		'date' => 'date',
		'discount' => 'discount',
		'email' => 'email',
		'emailfrom' => 'email_from',
		'emailsubject' => 'email_subject',
		'emailto' => 'email_to',
		'emailevent' => 'email_event',
		'emailnote' => 'email_note',
		'firstname' => 'first_name',
		'freight' => 'freight',
		'hasdata' => 'has_data',
		'hasitems' => 'has_items',
		'haspromo' => 'has_promo',
		'hasdiscount' => 'has_discount',
		'hasdownloads' => 'has_downloads',
		'hasfreight' => 'has_freight',
		'hastax' => 'has_tax',
		'id' => 'id',
		'itemaddons' => 'item_addons',
		'itemaddon' => 'item_addon',
		'itemaddonslist' => 'item_addons_list',
		'itemdescription' => 'item_description',
		'itemdownload' => 'item_download',
		'itemhasaddons' => 'item_has_addons',
		'itemhasinputs' => 'item_has_inputs',
		'itemid' => 'item_id',
		'iteminput' => 'item_input',
		'iteminputs' => 'item_inputs',
		'iteminputslist' => 'item_inputs_list',
		'itemname' => 'item_name',
		'itemoptions' => 'item_options',
		'itemprice' => 'item_price',
		'itemproduct' => 'item_product',
		'itemquantity' => 'item_quantity',
		'itemsku' => 'item_sku',
		'itemtotal' => 'item_total',
		'itemunitprice' => 'item_unit_price',
		'itemtype' => 'item_type',
		'items' => 'items',
		'lastname' => 'last_name',
		'notpaid' => 'not_paid',
		'orderdata' => 'order_data',
		'paid' => 'paid',
		'payment' => 'payment',
		'paymethod' => 'paymethod',
		'phone' => 'phone',
		'postcode' => 'postcode',
		'promolist' => 'promo_list',
		'gateway' => 'gateway',
		'receipt' => 'receipt',
		'shipname' => 'ship_name',
		'shipaddress' => 'ship_address',
		'shipcity' => 'ship_city',
		'shipcountry' => 'ship_country',
		'shipmethod' => 'ship_method',
		'shippostcode' => 'ship_postcode',
		'shipstate' => 'ship_state',
		'shipxaddress' => 'ship_xaddress',
		'state' => 'state',
		'status' => 'status',
		'subtotal' => 'subtotal',
		'tax' => 'tax',
		'total' => 'total',
		'totalitems' => 'total_items',
		'txnid' => 'txnid',
		'transactionid' => 'txnid',
		'url' => 'url',
		'xaddress' => 'xaddress'
	);

	static function _apicontext () { return 'purchase'; }

	/**
	 * _setobject - returns the global context object used in the shopp('purchase') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'Purchase') ) return $Object;

		if ( strtolower($object) != 'purchase' ) return $Object; // not mine, do nothing
		else {
			return ShoppPurchase();
		}
	}

	static function address ($result, $options, $O) { return esc_html($O->address); }

	static function card ($result, $options, $O) { return (!empty($O->card))?sprintf("%'X16d",$O->card):''; }

	static function card_type ($result, $options, $O) { return $O->cardtype; }

	static function city ($result, $options, $O) { return esc_html($O->city); }

	static function company ($result, $options, $O) { return esc_html($O->company); }

	static function country ($result, $options, $O) {
		$countries = shopp_setting('target_markets');
		return $countries[$O->country];
	}

	static function customer ($result, $options, $O) { return $O->customer; }

	static function data ($result, $options, $O) {
		if (!is_array($O->data)) return false;
		$data = current($O->data);
		$name = key($O->data);
		if (isset($options['name'])) return esc_html($name);
		return apply_filters('shopp_purchase_order_data', $data);
	}

	static function date ($result, $options, $O) {
		if (empty($options['format'])) $options['format'] = get_option('date_format').' '.get_option('time_format');
		return _d($options['format'], is_int($O->created) ? $O->created : sDB::mktime($O->created));
	}

	static function discount ($result, $options, $O) { return money($O->discount); }

	static function email ($result, $options, $O) { return esc_html($O->email); }

	// email_* tags are for email headers. The trailing PHP_EOL is to account for PHP ticket #21891
	// where trailing newlines are removed, despite the PHP docs saying they will be included.

	static function email_from ($result, $options, $O) { if (isset($O->message['from'])) return ($O->message['from'].PHP_EOL); }

	static function email_to ($result, $options, $O) { if (isset($O->message['to'])) return ($O->message['to'].PHP_EOL); }

	static function email_subject ($result, $options, $O) { if (isset($O->message['subject'])) return ($O->message['subject'].PHP_EOL); }

	static function email_event ($result, $options, $O) {
		if (!isset($O->message['event'])) return '';
		extract($options);

		$Event = $O->message['event'];
		if (isset($Event->$name)) {
			$string = $Event->$name;

			if ('shipped' == $Event->name) {
				$carriers = Lookup::shipcarriers();
				$carrier = $carriers[$Event->carrier];
				if ('carrier' == $name) $string = $carrier->name;
				if ('tracking' == $name && Shopp::str_true($link)) return'<a href="'.esc_url(sprintf($carrier->trackurl,$string)).'">'.esc_html($string).'</a>';
			}

			return esc_html($string);
		}

		return '';
	}

	static function email_note ($result, $options, $O) {
		if ( isset($O->message['note']) )
			return esc_html($O->message['note']);
	}

	static function first_name ($result, $options, $O) {
		return esc_html($O->firstname);
	}

	static function freight ($result, $options, $O) {
		return money($O->freight);
	}

	static function gateway ($result, $options, $O) {
		return $O->gateway;
	}

	static function has_data ($result, $options, $O) {
		reset($O->data);
		return (is_array($O->data) && count($O->data) > 0);
	}

	static function has_discount ($result, $options, $O) {
		return ($O->discount > 0);
	}

	static function has_downloads ($result, $options, $O) {
		reset($O->downloads);
		return ($O->downloads);
	}

	static function has_freight ($result, $options, $O) {
		return ( $O->shipable || ! empty($O->shipmethod) || $O->freight > 0 );
	}

	static function has_items ($result, $options, $O) {
		if ( ! method_exists($O, 'load_purchased') ) return false;
		if ( empty($O->purchased) ) $O->load_purchased();
		reset($O->purchased);
		return (count($O->purchased) > 0);
	}

	static function has_promo ($result, $options, $O) {
		if ( empty($options['name']) ) return false;
		return in_array($options['name'],$O->promos);
	}

	static function has_tax ($result, $options, $O) {
		return ( $O->tax > 0 );
	}

	static function id ($result, $options, $O) { return $O->id; }

	static function item_addons ($result, $options, $O) {
		$item = current($O->purchased);
		if (!isset($O->_itemaddons_loop)) {
			reset($item->addons->meta);
			$O->_itemaddons_loop = true;
		} else next($item->addons->meta);

		if (current($item->addons->meta) !== false) return true;
		else {
			unset($O->_itemaddons_loop);
			return false;
		}
	}

	static function item_addon ($result, $options, $O) {
		$item = current($O->purchased);
		$addon = current($item->addons->meta);
		if ( false === $item || false === $addon ) return '';

		if (isset($options['id'])) return esc_html($addon->id);
		if (isset($options['name'])) return esc_html($addon->name);
		if (isset($options['label'])) return esc_html($addon->name);
		if (isset($options['type'])) return esc_html($addon->value->type);
		if (isset($options['onsale'])) return $addon->value->sale;
		if (isset($options['inventory'])) return $addon->value->inventory;
		if (isset($options['sku'])) return esc_html($addon->value->sku);
		if (isset($options['unitprice'])) return money($addon->value->unitprice);

		if (isset($options['download'])) {
			$link = false;
			if (isset($addon->value->download) && isset($addon->value->dkey)) {
				$label = __('Download','Shopp');
				if ( isset($options['linktext']) && $options['linktext'] != '' ) $label = $options['linktext'];

				$dkey = $addon->value->dkey;
				$request = '' == get_option('permalink_structure')?"download/$dkey":array('shopp_download'=>$dkey);
				$url = Shopp::url($request,'catalog');

				$link = '<a href="'.$url.'">'.$label.'</a>';
				return esc_html($link);
			}
			return '';
		}

		return money($addon->value->unitprice);
	}

	static function item_addons_list ($result, $options, $O) {
		$item = current($O->purchased);
		if (empty($item->addons) || (is_string($item->addons) && !Shopp::str_true($item->addons))) return false;
		$defaults = array(
			'prices' => "on",
			'download' => __('Download','Shopp'),
			'before' => '',
			'after' => '',
			'classes' => '',
			'excludes' => ''
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$class = !empty($classes)?' class="'.join(' ',explode(',',$classes)).'"':'';
		$taxrate = 0;
		if ($item->unitprice > 0)
			$taxrate = round($item->unittax/$item->unitprice,4);

		$result = $before.'<ul'.$class.'>';
		foreach ($item->addons->meta as $id => $addon) {
			if (in_array($addon->name,$excludes)) continue;
			if ($O->taxing == "inclusive")
				$price = $addon->value->unitprice+($addon->value->unitprice*$taxrate);
			else $price = $addon->value->unitprice;

			$link = false;
			if (isset($addon->value->download) && isset($addon->value->dkey)) {
				$dkey = $addon->value->dkey;
				$request = '' == get_option('permalink_structure')?array('src'=>'download','shopp_download'=>$dkey):"download/$dkey";
				$url = Shopp::url($request,'account');
				$link = '<br /><a href="'.$url.'">'.$download.'</a>';
			}

			$pricing = Shopp::str_true($prices)?" (".money($price).")":"";
			$result .= '<li>'.esc_html($addon->name.$pricing).$link.'</li>';
		}
		$result .= '</ul>'.$after;
		return $result;
	}

	static function item_description ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->description;
	}

	static function item_type ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->type;
	}

	static function item_download ($result, $options, $O) {
		$item = current($O->purchased);
		if (empty($item->download)) return "";
		if (!isset($options['label'])) $options['label'] = __('Download','Shopp');
		$classes = "";
		if (isset($options['class'])) $classes = ' class="'.$options['class'].'"';
		$request = '' == get_option('permalink_structure') ? array('src'=>'download','shopp_download'=>$item->dkey) : "download/$item->dkey";
		$url = Shopp::url($request,'account');
		return '<a href="'.$url.'"'.$classes.'>'.$options['label'].'</a>';
	}

	static function item_has_addons ($result, $options, $O) {
		$item = current($O->purchased);
		reset($item->addons);
		return (count($item->addons) > 0);
	}

	static function item_has_inputs ($result, $options, $O) {
		$item = current($O->purchased);
		reset($item->data);
		return ( count($item->data) > 0 );
	}

	static function item_id ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->id;
	}

	static function item_input ($result, $options, $O) {
		$item = current($O->purchased);
		$data = current($item->data);
		$name = key($item->data);
		if (isset($options['name'])) return esc_html($name);
		return esc_html($data);
	}

	static function item_inputs ($result, $options, $O) {
		$item = current($O->purchased);
		if (!isset($O->_iteminputs_loop)) {
			reset($item->data);
			$O->_iteminputs_loop = true;
		} else next($item->data);

		if (current($item->data) !== false) return true;
		else {
			unset($O->_iteminputs_loop);
			return false;
		}
	}

	static function item_inputs_list ($result, $options, $O) {
		$item = current($O->purchased);
		if (empty($item->data)) return false;
		$before = ""; $after = ""; $classes = ""; $excludes = array();
		if (!empty($options['class'])) $classes = ' class="'.$options['class'].'"';
		if (!empty($options['exclude'])) $excludes = explode(",",$options['exclude']);
		if (!empty($options['before'])) $before = $options['before'];
		if (!empty($options['after'])) $after = $options['after'];

		$result .= $before.'<ul'.$classes.'>';
		foreach ($item->data as $name => $data) {
			if (in_array($name,$excludes)) continue;
			$result .= '<li><strong>'.esc_html($name).'</strong>: '.apply_filters('shopp_purchase_item_input_data', $data).'</li>';
		}
		$result .= '</ul>'.$after;
		return $result;
	}

	static function item_name ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->name;
	}

	static function item_options ($result, $options, $O) {
		if (!isset($options['after'])) $options['after'] = "";
		$item = current($O->purchased);
		return (!empty($item->optionlabel))?$options['before'].$item->optionlabel.$options['after']:'';
	}

	static function item_price ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->price;
	}

	static function item_product ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->product;
	}

	static function item_quantity ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->quantity;
	}

	static function items ($result, $options, $O) {
		if ( ! isset($O->_items_loop) ) {
			reset($O->purchased);
			$O->_items_loop = true;
		} else next($O->purchased);

		if (current($O->purchased) !== false) return true;
		else {
			unset($O->_items_loop);
			return false;
		}
	}

	static function item_sku ($result, $options, $O) {
		$item = current($O->purchased);
		return $item->sku;
	}

	static function item_total ($result, $options, $O) {
		$item = current($O->purchased);
		$amount = $item->total+($O->taxing == 'inclusive'?$item->unittax*$item->quantity:0);
		return money($amount);
	}

	static function item_unit_price ($result, $options, $O) {
		$item = current($O->purchased);
		$amount = $item->unitprice+($O->taxing == 'inclusive'?$item->unittax:0);
		return money($amount);
	}

	static function last_name ($result, $options, $O) { return esc_html($O->lastname); }

	static function not_paid ($result, $options, $O) { return !self::paid($result,$options,$O); }

	static function order_data ($result, $options, $O) {
		if (!isset($O->_data_loop)) {
			reset($O->data);
			$O->_data_loop = true;
		} else next($O->data);

		if (current($O->data) !== false) return true;
		else {
			unset($O->_data_loop);
			return false;
		}
	}

	static function paid ($result, $options, $O) { return in_array($O->txnstatus,array('captured')); }

	static function payment ($result, $options, $O) {
		$labels = Lookup::txnstatus_labels();
		return isset($labels[$O->txnstatus])?$labels[$O->txnstatus]:$O->txnstatus;
	}

	static function paymethod ($result, $options, $O) { return $O->paymethod; }

	static function phone ($result, $options, $O) { return esc_html($O->phone); }

	static function postcode ($result, $options, $O) { return esc_html($O->postcode); }

	static function promo_list ($result, $options, $O) {
		$output = "";
		if (!empty($O->promos)) {
			$output .= '<ul>';
			foreach ($O->promos as $promo)
				$output .= '<li>'.$promo.'</li>';
			$output .= '</ul>';
		}
		return $output;
	}

	static function receipt ($result, $options, $O) {
		$template = '';
		if ( isset($options['template']) && !empty($options['template']) )
			return $O->receipt($options['template']);
		return $O->receipt();
	}

	static function ship_name ($result, $options, $O) { return esc_html($O->shipname); }

	static function ship_address ($result, $options, $O) { return esc_html($O->shipaddress); }

	static function ship_city ($result, $options, $O) { return esc_html($O->shipcity); }

	static function ship_country ($result, $options, $O) {
		$Shopp = Shopp::object();
		$countries = shopp_setting('target_markets');
		return $countries[$O->shipcountry];
	}

	static function ship_method ($result, $options, $O) { return esc_html($O->shipoption); }

	static function ship_postcode ($result, $options, $O) { return esc_html($O->shippostcode); }

	static function ship_state ($result, $options, $O) {
		$state = esc_html($O->shipstate);
		if (strlen($O->state > 2)) return $state;
		$regions = Lookup::country_zones();

		if (isset($regions[$O->country])) {
			$states = $regions[$O->country];
			if (isset($states[$O->shipstate]))
				return esc_html($states[$O->shipstate]);
		}

		return $state;
	}

	static function ship_xaddress ($result, $options, $O) { return esc_html($O->shipxaddress); }

	static function state ($result, $options, $O) {
		$state = esc_html($O->state);
		if (strlen($O->state) > 2) return $state;
		$regions = Lookup::country_zones();

		if (isset($regions[$O->country])) {
			$states = $regions[$O->country];
			if (isset($states[$O->state]))
				return esc_html($states[$O->state]);
		}

		return $state;
	}

	static function status ($result, $options, $O) {
		$Shopp = Shopp::object();
		$labels = shopp_setting('order_status');
		if (empty($labels)) $labels = array('');
		return $labels[$O->status];
	}

	static function subtotal ($result, $options, $O) { return money($O->subtotal); }

	static function tax ($result, $options, $O) { return money($O->tax); }

	static function total ($result, $options, $O) { return money($O->total); }

	static function total_items ($result, $options, $O) { return count($O->purchased); }

	static function txnid ($result, $options, $O) { return $O->txnid; }

	static function url ($result, $options, $O) { return Shopp::url(false,'account'); }

	static function xaddress ($result, $options, $O) { return esc_html($O->xaddress); }

}

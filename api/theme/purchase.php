<?php
/**
 * purchase.php
 *
 * ShoppPurchaseThemeAPI provides shopp('purchase') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2014
 * @package Shopp\API\Theme\Purchase
 * @version 1.3
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_purchase_item_input_name', 'esc_html');

add_filter('shopp_purchase_item_input_data', 'esc_html');
add_filter('shopp_purchase_item_input_data', 'wptexturize');
add_filter('shopp_purchase_item_input_data', 'convert_chars');
add_filter('shopp_purchase_item_input_data', 'wpautop');

add_filter('shopp_purchase_order_data', 'esc_html');
add_filter('shopp_purchase_order_data', 'wptexturize');
add_filter('shopp_purchase_order_data', 'convert_chars');
add_filter('shopp_purchase_order_data', 'wpautop');

add_filter('shopp_themeapi_purchase_emailnote', 'esc_html');
add_filter('shopp_themeapi_purchase_emailnote', 'wptexturize');
add_filter('shopp_themeapi_purchase_emailnote', 'convert_chars');
add_filter('shopp_themeapi_purchase_emailnote', 'wpautop');

/**
 * Provides shopp('purchase') theme API functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 **/
class ShoppPurchaseThemeAPI implements ShoppAPI {

	/**
	 * @var array The registry of available `shopp('purchase')` properties
	 * @internal
	 **/
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
		'discountlist' => 'discount_list',
		'email' => 'email',
		'emailfrom' => 'email_from',
		'emailsubject' => 'email_subject',
		'emailto' => 'email_to',
		'emailevent' => 'email_event',
		'emailnote' => 'email_note',
		'firstname' => 'first_name',
		'hasdata' => 'has_data',
		'hasitems' => 'has_items',
		'haspromo' => 'has_discount',
		'hasdiscount' => 'has_discount',
		'hasdownloads' => 'has_downloads',
		'hasshipping' => 'has_shipping',
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
		'gateway' => 'gateway',
		'receipt' => 'receipt',
		'shipping' => 'shipping',
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
		'xaddress' => 'xaddress',

		'promolist' => 'discount_list', // @deprecated purchase.promo-list replaced by purchase.discount-list
		'freight' => 'shipping', // @deprecated purchase.freight replaced by purchase.shipping
		'hasfreight' => 'has_shipping', // @deprecated purchase.has-freight replaced by purchase.has-shipping

		'_money'
	);

	/**
	 * Provides the authoritative Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		return 'purchase';
	}


	/**
	 * Returns the proper global context object used in a shopp('collection') call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param ShoppPurchase $Object The ShoppOrder object to set as the working context
	 * @param string        $context The context being worked on by the Theme API
	 * @return ShoppPurchase The active object context
	 **/
	public static function _setobject ( $Object, $object ) {
		if ( is_object($Object) && is_a($Object, 'Purchase') ) return $Object;

		if ( strtolower($object) != 'purchase' ) return $Object; // not mine, do nothing
		else {
			return ShoppPurchase();
		}
	}

	/**
	 * Filter callback to add standard monetary option behaviors
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param string        $result    The output
	 * @param array         $options   The options
	 * - **money**: `on` (on, off) Format the amount in the current currency format
	 * - **number**: `off` (on, off) Provide the unformatted number (floating point)
	 * @param string    $property  The tag property name
	 * @param ShoppPurchase $O         The working object
	 * @return ShoppPurchase The active ShoppPurchase context
	 **/
	public static function _money ($result, $options, $property, $O) {
		// Passthru for non-monetary results
		$monetary = array(
			'freight', // @deprecated purchase.freight uses purchase.shipping
			'subtotal', 'discount', 'shipping', 'itemaddon', 'itemtotal', 'itemunitprice', 'tax', 'total'
		);
		if ( ! in_array($property, $monetary) || ! is_numeric($result) ) return $result;

		// Special case for purchase.item-addon `unitprice` option
		if ( 'itemaddon' == $property && ! in_array('uniprice', $options) ) return $result;

		// @deprecated currency parameter
		if ( isset($options['currency']) ) $options['money'] = $options['currency'];

		$defaults = array(
			'money' => 'on',
			'number' => false,
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if ( Shopp::str_true($number) ) return $result;
		if ( Shopp::str_true($money)  ) $result = Shopp::money( Shopp::roundprice($result) );

		return $result;
	}

	/**
	 * Provides the billing street address
	 *
	 * @api `shopp('purchase.address')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The street address
	 **/
	public static function address ( $result, $options, $O ) {
		return esc_html($O->address);
	}

	/**
	 * Provides a last four digits of the payment card PAN used to make payment
	 *
	 * @api `shopp('purchase.card')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The last 4 digits
	 **/
	public static function card ( $result, $options, $O ) {
		return ( ! empty($O->card) ) ? sprintf("%'X16d", $O->card) : '';
	}

	/**
	 * Provides the payment card type
	 *
	 * @api `shopp('purchase.cardtype')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The payment card type
	 **/
	public static function card_type ( $result, $options, $O ) {
		return esc_html($O->cardtype);
	}

	/**
	 * Provides the city for the billing address
	 *
	 * @api `shopp('purchase.city')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The billing city
	 **/
	public static function city ( $result, $options, $O ) {
		return esc_html($O->city);
	}

	/**
	 * Provides the customer company name
	 *
	 * @api `shopp('purchase.company')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The company name
	 **/
	public static function company ( $result, $options, $O ) {
		return esc_html($O->company);
	}

	/**
	 * Provides the country for the billing address
	 *
	 * @api `shopp('purchase.country')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The country name
	 **/
	public static function country ( $result, $options, $O ) {
		$countries = shopp_setting('target_markets');
		return $countries[ $O->country ];
	}

	/**
	 * Provides the database ID of the customer account
	 *
	 * @api `shopp('purchase.customer')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The customer ID
	 **/
	public static function customer ( $result, $options, $O ) {
		return $O->customer;
	}

	/**
	 * Provides a customer order data entry from the data loop
	 *
	 * @api `shopp('purchase.data')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **name**: Provide the data entry specified by the given name or, without setting a value provides the name of the entry rather than the data
	 * @param ShoppPurchase $O       The working object
	 * @return string The data entry
	 **/
	public static function data ( $result, $options, $O ) {
		if ( ! is_array($O->data) ) return false;

		$data = current($O->data);
		$name = key($O->data);

		if ( ! empty($options['name']) ) {
			if ( isset($O->data[ $options['name'] ]) )
				$data = $O->data[ $options['name'] ];
			else $data = false;
		} elseif ( isset($options['name']) ) return esc_html($name);

		return apply_filters('shopp_purchase_order_data', $data);
	}

	/**
	 * Provides the date the purchase order was created
	 *
	 * @api `shopp('purchase.date')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **format**: Sets the PHP date formatting to use. Defaults to the WordPress date and time formats
	 * @param ShoppPurchase $O       The working object
	 * @return string The purchase order date
	 **/
	public static function date ( $result, $options, $O ) {
		if (empty($options['format'])) $options['format'] = get_option('date_format').' '.get_option('time_format');
		return _d($options['format'], is_int($O->created) ? $O->created : sDB::mktime($O->created));
	}

	/**
	 * Provides the discount amount
	 *
	 * @api `shopp('purchase.discount')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The discount amount
	 **/
	public static function discount ( $result, $options, $O ) {
		return (float) abs($O->discount);
	}

	/**
	 * Provides the customer email address
	 *
	 * @api `shopp('purchase.email')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The email address
	 **/
	public static function email ( $result, $options, $O ) {
		return esc_html($O->email);
	}

	/**
	 * Provides the notification message sender email address
	 *
	 * email_* tags are for email headers. The trailing PHP_EOL is to account for PHP ticket #21891
	 * where trailing newlines are removed, despite the PHP docs saying they will be included.
	 *
	 * @api `shopp('purchase.email-from')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The message sender email address
	 **/
	public static function email_from ( $result, $options, $O ) {
		if ( isset($O->message['from']) ) return ($O->message['from'] . PHP_EOL);
	}

	/**
	 * Provides the notification message recipient email address
	 *
	 *
	 *
	 * @api `shopp('purchase.email-to')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The recipient email address
	 **/
	public static function email_to ( $result, $options, $O ) {
		if ( isset($O->message['to']) ) return ($O->message['to'] . PHP_EOL);
	}

	/**
	 * Provides the notification message subject
	 *
	 * @api `shopp('purchase.email-subject')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The message subject
	 **/
	public static function email_subject ( $result, $options, $O ) {
		if ( isset($O->message['subject']) ) return ($O->message['subject'] . PHP_EOL);
	}

	/**
	 * Provides a property of the current notification event
	 *
	 * @api `shopp('purchase.email-event')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The event property
	 **/
	public static function email_event ( $result, $options, $O ) {
		if ( ! isset($O->message['event']) ) return '';
		extract($options);

		$Event = $O->message['event'];
		if ( isset($Event->$name) ) {
			$string = $Event->$name;

			if ( 'shipped' == $Event->name ) {
				$carriers = Lookup::shipcarriers();
				$carrier = $carriers[ $Event->carrier ];
				if ( 'carrier' == $name ) $string = $carrier->name;
				if ( 'tracking' == $name && Shopp::str_true($link) ) {
					$params = apply_filters('shopp_shipped_trackurl_params', array($string), $Event->order());
					return'<a href="' . esc_url(vsprintf($carrier->trackurl, $params)) . '">' . esc_html($string) . '</a>';
				}
			}

			return esc_html($string);
		}

		return '';
	}

	/**
	 * Provides the notification message to include in the email
	 *
	 * @api `shopp('purchase.email-note')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The message note
	 **/
	public static function email_note ( $result, $options, $O ) {
		if ( isset($O->message['note']) )
			return $O->message['note'];
	}

	/**
	 * Provides the customer's first name
	 *
	 * @api `shopp('purchase.first-name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The customer first name
	 **/
	public static function first_name ( $result, $options, $O ) {
		return esc_html($O->firstname);
	}

	/**
	 * Provides the name of the payment gateway that processed the order payment
	 *
	 * @api `shopp('purchase.gateway')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The gateway name
	 **/
	public static function gateway ( $result, $options, $O ) {
		return $O->gateway;
	}

	/**
	 * Checks if the order has custom data assigned to it
	 *
	 * @api `shopp('purchase.has-data')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if custom data exists, false otherwise
	 **/
	public static function has_data ( $result, $options, $O ) {
		reset($O->data);
		return ( is_array($O->data) && count($O->data) > 0 );
	}

	/**
	 * Checks if any promo discounts were applied to the order
	 *
	 * @api `shopp('purchase.has-discount')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if a discount is applied, false otherwise
	 **/
	public static function has_discount ( $result, $options, $O ) {
		if ( isset($options['name']) ) {
			$discounts = $O->discounts();
			if ( empty($discounts) ) return false;
			foreach ( $discounts as $discount )
				if ( $discount->name == $options['name'] ) return true;
			return false;
		}
		return ( abs($O->discount) > 0 );
	}

	/**
	 * Checks if the order contains any downloadable items
	 *
	 * @api `shopp('purchase.has-downloads')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if downloads exist, false otherwise
	 **/
	public static function has_downloads ( $result, $options, $O ) {
		if ( is_array($O->downloads) )
			reset($O->downloads);
		return $O->downloads;
	}

	/**
	 * Checks if the order has any items
	 *
	 * @api `shopp('purchase.has-items')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if items exist, false otherwise
	 **/
	public static function has_items ( $result, $options, $O ) {
		if ( ! method_exists($O, 'load_purchased') ) return false;
		if ( empty($O->purchased) ) $O->load_purchased();
		reset($O->purchased);
		return count($O->purchased) > 0;
	}

	/**
	 * Checks if the order has shippable items
	 *
	 * @api `shopp('purchase.has-shipping')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if shippable items exist
	 **/
	public static function has_shipping ( $result, $options, $O ) {
		return ( $O->shippable || ! empty($O->shipmethod) || $O->freight > 0 );
	}

	/**
	 * Checks if a tax amount is applied to the order
	 *
	 * @api `shopp('purchase.has_tax')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if the order has tax, false otherwise
	 **/
	public static function has_tax ( $result, $options, $O ) {
		return ( $O->tax > 0 );
	}

	/**
	 * Provides the order database ID
	 *
	 * @api `shopp('purchase.id')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The order ID
	 **/
	public static function id ( $result, $options, $O ) {
		return $O->id;
	}

	/**
	 * Iterates over the addons from the current item in the items loop
	 *
	 * @api `shopp('purchase.item-addons')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if the next addon exists, false otherwise
	 **/
	public static function item_addons ( $result, $options, $O ) {
		$item = current($O->purchased);
		if ( ! isset($O->_itemaddons_loop) ) {
			reset($item->addons->meta);
			$O->_itemaddons_loop = true;
		} else next($item->addons->meta);

		if ( current($item->addons->meta) !== false ) return true;
		else {
			unset($O->_itemaddons_loop);
			return false;
		}
	}

	/**
	 * Provides a property list of the current addon of the current item if the items and item-addons loops
	 *
	 * @api `shopp('purchase.item-addon')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **id**: Provides the database ID of the addon
	 * - **name**: Provides the name of the addon
	 * - **label**: Provides the label of the addon
	 * - **type**: Provides the addon type
	 * - **onsale**: Checks if the item is on sale or not
	 * - **inventory**: Checks if inventory is tracked for the item or not
	 * - **sku**: Provides the SKU for the item addon
	 * - **unitprice**: Provides the regular price amount for the addon
	 * - **download**: Provides the download link markup for the item addon
	 * @param ShoppPurchase $O       The working object
	 * @return string The addon property
	 **/
	public static function item_addon ( $result, $options, $O ) {
		$item = current($O->purchased);
		$addon = current($item->addons->meta);
		if ( false === $item || false === $addon ) return '';

		if ( isset($options['id']) ) return esc_html($addon->id);
		if ( isset($options['name']) ) return esc_html($addon->name);
		if ( isset($options['label']) ) return esc_html($addon->name);
		if ( isset($options['type']) ) return esc_html($addon->value->type);
		if ( isset($options['onsale']) ) return $addon->value->sale;
		if ( isset($options['inventory']) ) return $addon->value->inventory;
		if ( isset($options['sku']) ) return esc_html($addon->value->sku);
		if ( isset($options['unitprice']) ) return (float) $addon->value->unitprice;

		if ( isset($options['download']) ) {
			$link = false;
			if ( isset($addon->value->download) && isset($addon->value->dkey) ) {
				$label = __('Download', 'Shopp');
				if ( isset($options['linktext']) && $options['linktext'] != '' ) $label = $options['linktext'];

				$dkey = $addon->value->dkey;
				$request = '' == get_option('permalink_structure') ? "download/$dkey" : array('shopp_download' => $dkey);
				$url = Shopp::url($request, 'catalog');

				$link = '<a href="' . $url . '">' . $label . '</a>';
				return esc_html($link);
			}
			return '';
		}

		return (float) $addon->value->unitprice;
	}

	/**
	 * Provides markup for the list of addons of the current item of the addons loop
	 *
	 * @api `shopp('purchase.item-addons-list')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **before**: ` ` Markup to add before the widget
	 * - **after**: ` ` Markup to add after the widget
	 * - **class**: The class attribute specifies one or more class-names for an element
	 * - **download**: The label for the download link for addon downloads
	 * - **excludes**: Used to specify addon names to exclude from the list. Multiple addons can be excluded by separating them with a comma: `Addon Label 1,Addon Label 2...`
	 * - **prices**: `on` (on, off) Shows or hides prices with the addon label
	 * @param ShoppPurchase $O       The working object
	 * @return string The list markup
	 **/
	public static function item_addons_list ( $result, $options, $O ) {
		$item = current($O->purchased);
		if ( empty($item->addons) || ( is_string($item->addons) && ! Shopp::str_true($item->addons) ) ) return false;
		$defaults = array(
			'prices' => 'on',
			'download' => Shopp::__('Download'),
			'before' => '',
			'after' => '',
			'class' => '',
			'excludes' => ''
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! empty($classes) ) $class .= ( empty($class) ? '': ' ' ) . join(' ', explode(',', $classes) ); // supports deprecated `classes` option
		$class = ! empty($class) ? ' class="' . $class . '"' : '';
		$taxrate = 0;
		if ( $item->unitprice > 0 )
			$taxrate = round($item->unittax / $item->unitprice, 4);

		$result = $before.'<ul' . $class . '>';
		foreach ( $item->addons->meta as $id => $addon ) {
			if ( in_array($addon->name, $excludes) ) continue;
			if ( 'inclusive' == $O->taxing )
				$price = $addon->value->unitprice + ( $addon->value->unitprice * $taxrate );
			else $price = $addon->value->unitprice;

			$link = false;
			if ( isset($addon->value->download) && isset($addon->value->dkey) ) {
				$dkey = $addon->value->dkey;
				$request = '' == get_option('permalink_structure') ? array('src' => 'download', 'shopp_download' => $dkey) : "download/$dkey";
				$url = Shopp::url($request, 'account');
				$link = '<br /><a href="' . $url . '">' . $download . '</a>';
			}

			$pricing = Shopp::str_true($prices) ? " (" . money($price) . ")" : '';
			$result .= '<li>' . esc_html($addon->name . $pricing) . $link . '</li>';
		}
		$result .= '</ul>' . $after;
		return $result;
	}

	/**
	 * Provides the item description inherited from the product summary for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-description')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The description markup
	 **/
	public static function item_description ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->description;
	}

	/**
	 * Provides the item type for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-type')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item product type
	 **/
	public static function item_type ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->type;
	}

	/**
	 * Provides the item download link for the current item in the items loop, if it is a download
	 *
	 * @api `shopp('purchase.item-download')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **label**: The label used for the download link
	 * @param ShoppPurchase $O       The working object
	 * @return string The link markup
	 **/
	public static function item_download ( $result, $options, $O ) {
		$item = current($O->purchased);
		if ( empty($item->download) ) return '';
		if ( ! isset($options['label']) ) $options['label'] = Shopp::__('Download');
		$classes = '';
		if ( isset($options['class']) ) $classes = ' class="' . $options['class'] . '"';
		$request = '' == get_option('permalink_structure') ? array('src' => 'download', 'shopp_download' => $item->dkey) : "download/$item->dkey";
		$url = Shopp::url($request, 'account');
		return '<a href="' . $url . '"' . $classes . '>' . $options['label'] . '</a>';
	}

	/**
	 * Checks if the current item in the items loop has any addons
	 *
	 * @api `shopp('purchase.item-has-addons')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if addons exist, false otherwise
	 **/
	public static function item_has_addons ( $result, $options, $O ) {
		$item = current($O->purchased);
		reset($item->addons);
		return ( count($item->addons) > 0 );
	}

	/**
	 * Checks if the current item in the items loop has custom product input data
	 *
	 * @api `shopp('purchase.item-has-inputs')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if custom input exists, false otherwise
	 **/
	public static function item_has_inputs ( $result, $options, $O ) {
		$item = current($O->purchased);
		reset($item->data);
		return ( count($item->data) > 0 );
	}

	/**
	 * Provides the database ID of the current item in the items loop
	 *
	 * @api `shopp('purchase.item-id')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item id
	 **/
	public static function item_id ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->id;
	}

	/**
	 * Provides the input data or input name for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-input')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **name**: Provide the input name rather than the input value
	 * @param ShoppPurchase $O       The working object
	 * @return string The item input data
	 **/
	public static function item_input ( $result, $options, $O ) {
		$item = current($O->purchased);
		$data = current($item->data);
		$name = key($item->data);
		if ( isset($options['name']) )
			return apply_filters('shopp_purchase_item_input_name', $name);
		return apply_filters('shopp_purchase_item_input_data', $data, $name);
	}

	/**
	 * Iterates over the custom inputs for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-inputs')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if the next item exists, false otherwise
	 **/
	public static function item_inputs ( $result, $options, $O ) {
		$item = current($O->purchased);
		if ( ! isset($O->_iteminputs_loop) ) {
			reset($item->data);
			$O->_iteminputs_loop = true;
		} else next($item->data);

		if ( current($item->data) !== false ) return true;
		else {
			unset($O->_iteminputs_loop);
			return false;
		}
	}

	/**
	 * Provides markup for an unordered list of the custom inputs for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-inputs-list')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **before**: Markup to add before the list
	 * - **after**: Markup to add after the list
	 * - **class**:
	 * - **exclude**:
	 * @param ShoppPurchase $O       The working object
	 * @return string The list markup
	 **/
	public static function item_inputs_list ( $result, $options, $O ) {
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
			$result .= '<li><strong>'.apply_filters('shopp_purchase_item_input_name', $name).'</strong>: '.apply_filters('shopp_purchase_item_input_data', $data, $name).'</li>';
		}
		$result .= '</ul>'.$after;
		return $result;
	}

	/**
	 * Provides the name for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item name
	 **/
	public static function item_name ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->name;
	}

	/**
	 * Provides the option label for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-options')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item option
	 **/
	public static function item_options ( $result, $options, $O ) {
		if ( ! isset($options['after']) ) $options['after'] = '';
		$item = current($O->purchased);
		return ( ! empty($item->optionlabel) ) ? $options['before'] . $item->optionlabel . $options['after'] : '';
	}

	/**
	 * Provides the regular price for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-price')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item price
	 **/
	public static function item_price ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->price;
	}

	/**
	 * Provides the database ID for the product custom post type of the current item in the items loop
	 *
	 * @api `shopp('purchase.item-product')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The product ID
	 **/
	public static function item_product ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->product;
	}

	/**
	 * Provides the quantity ordered for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-quantity')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item quantity ordered
	 **/
	public static function item_quantity ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->quantity;
	}

	/**
	 * Iterates over the items in the order
	 *
	 * @api `shopp('purchase.items')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if the next item exists, false otherwise
	 **/
	public static function items ( $result, $options, $O ) {
		if ( ! isset($O->_items_loop) ) {
			reset($O->purchased);
			$O->_items_loop = true;
		} else next($O->purchased);

		if ( current($O->purchased) !== false ) return true;
		else {
			unset($O->_items_loop);
			return false;
		}
	}

	/**
	 * Provides the SKU for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-sku')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The item SKU
	 **/
	public static function item_sku ( $result, $options, $O ) {
		$item = current($O->purchased);
		return $item->sku;
	}

	/**
	 * Provides the total cost for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-total')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **taxes**: (on,off) Used to override including or excluding taxes from the total
	 * @param ShoppPurchase $O       The working object
	 * @return string The item total cost
	 **/
	public static function item_total ( $result, $options, $O ) {
		$item = current($O->purchased);

		$taxes = isset( $options['taxes'] ) ? Shopp::str_true( $options['taxes'] ) : null;

		$total = (float) $item->total;
		$total = self::_taxes($total, $item, $taxes);
		return (float) $total;
	}


	/**
	 * Provides the unit price for the current item in the items loop
	 *
	 * @api `shopp('purchase.item-unit-price')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * - **taxes**: (on,off) Used to override including or excluding taxes from the total
	 * @param ShoppPurchase $O       The working object
	 * @return string The item total cost
	 **/
	public static function item_unit_price ( $result, $options, $O ) {
		$item = current($O->purchased);

		$taxes = isset( $options['taxes'] ) ? Shopp::str_true( $options['taxes'] ) : null;

		$unitprice = (float) $item->unitprice;
		$unitprice = self::_taxes($unitprice, $item, $taxes);
		return (float) $unitprice;

	}

	/**
	 * Provides the last name of the customer
	 *
	 * @api `shopp('purchase.last-name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The customer last name
	 **/
	public static function last_name ( $result, $options, $O ) {
		return esc_html($O->lastname);
	}

	/**
	 * Checks if the order is not paid
	 *
	 * @api `shopp('purchase.not-paid')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if not paid, false otherwise
	 **/
	public static function not_paid ( $result, $options, $O ) {
		return ! self::paid($result, $options, $O);
	}

	/**
	 * Iterates over the custom order data inputs
	 *
	 * @api `shopp('purchase.order-data')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if the next order data exists, false otherwise
	 **/
	public static function order_data ( $result, $options, $O ) {
		if ( ! isset($O->_data_loop) ) {
			reset($O->data);
			$O->_data_loop = true;
		} else next($O->data);

		if ( false !== current($O->data) ) return true;
		else {
			unset($O->_data_loop);
			return false;
		}
	}

	/**
	 * Checks if the order has been paid
	 *
	 * @api `shopp('purchase.paid')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return bool True if paid, false otherwise
	 **/
	public static function paid ( $result, $options, $O ) {
		return in_array($O->txnstatus, array('captured'));
	}

	/**
	 * Provides the current payment status (the transaction status)
	 *
	 * @api `shopp('purchase.payment')`
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The pay
	 **/
	public static function payment ( $result, $options, $O ) {
		$labels = Lookup::txnstatus_labels();
		return isset($labels[ $O->txnstatus ]) ? $labels[ $O->txnstatus ] : $O->txnstatus;
	}

	/**
	 * Provides the payment method used for the order
	 *
	 * @api `shopp('purchase.paymethod')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The payment method
	 **/
	public static function paymethod ( $result, $options, $O ) {
		return $O->paymethod;
	}

	/**
	 * Provides the customer phone number
	 *
	 * @api `shopp('purchase.phone')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The customer phone number
	 **/
	public static function phone ( $result, $options, $O ) {
		return esc_html($O->phone);
	}

	/**
	 * Provides the billing address postal code
	 *
	 * @api `shopp('purchase.postcode')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The postal code
	 **/
	public static function postcode ( $result, $options, $O ) {
		return esc_html($O->postcode);
	}

	/**
	 * Provides markup for an unorder list of discounts applied to the order
	 *
	 * @api `shopp('purchase.discount-list')`
	 * @since 1.2
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The discount list markup
	 **/
	public static function discount_list ( $result, $options, $O ) {
		$output = '';
		$discounts = $O->discounts();
		if ( ! empty($discounts) ) {
			$output .= '<ul>';
			foreach ( $discounts as $id => $Discount )
				$output .= '<li>' . esc_html($Discount->name) . '</li>';
			$output .= '</ul>';
		}
		return $output;
	}

	/**
	 * Provides the receipt markup generated from the receipt template
	 *
	 * @api `shopp('purchase.receipt')`
	 * @since 1.1
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The receipt markup
	 **/
	public static function receipt ( $result, $options, $O ) {
		$template = '';
		if ( isset($options['template']) && ! empty($options['template']) )
			return $O->receipt($options['template']);
		return $O->receipt();
	}

	/**
	 * Provides the addressee for the shipping address
	 *
	 * @api `shopp('purchase.ship-name')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping addressee name
	 **/
	public static function ship_name ( $result, $options, $O ) {
		return esc_html($O->shipname);
	}

	/**
	 * Provides the shipping street address line
	 *
	 * @api `shopp('purchase.ship-address')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping street address
	 **/
	public static function ship_address ( $result, $options, $O ) {
		return esc_html($O->shipaddress);
	}

	/**
	 * Provides the shipping address city name
	 *
	 * @api `shopp('purchase.ship-city')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping address city
	 **/
	public static function ship_city ( $result, $options, $O ) {
		return esc_html($O->shipcity);
	}

	/**
	 * Provides the shipping address country name
	 *
	 * @api `shopp('purchase.ship-country')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping address country
	 **/
	public static function ship_country ( $result, $options, $O ) {
		$Shopp = Shopp::object();
		$countries = shopp_setting('target_markets');
		return $countries[ $O->shipcountry ];
	}

	/**
	 * Provides the shipping method chosen for the order
	 *
	 * @api `shopp('purchase.ship-method')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping method
	 **/
	public static function ship_method ( $result, $options, $O ) {
		return esc_html($O->shipoption);
	}

	/**
	 * Provides the shipping address postal code
	 *
	 * @api `shopp('purchase.ship-postcode')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping address postal code
	 **/
	public static function ship_postcode ( $result, $options, $O ) {
		return esc_html($O->shippostcode);
	}

	/**
	 * Provides the shipping address state/province name
	 *
	 * @api `shopp('purchase.ship-state')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping state
	 **/
	public static function ship_state ( $result, $options, $O ) {
		$state = esc_html($O->shipstate);
		if ( strlen( $O->state > 2 ) ) return $state;
		$regions = Lookup::country_zones();

		if ( isset($regions[ $O->country ])) {
			$states = $regions[ $O->country ];
			if (isset($states[ $O->shipstate ]))
				return esc_html($states[ $O->shipstate ]);
		}

		return $state;
	}

	/**
	 * Provides the extra shipping street address line
	 *
	 * @api `shopp('purchase.ship-xaddress')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The extra shipping street address line
	 **/
	public static function ship_xaddress ( $result, $options, $O ) {
		return esc_html($O->shipxaddress);
	}

	/**
	 * Provides the total shipping cost amount
	 *
	 * @api `shopp('purchase.shipping')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The shipping cost
	 **/
	public static function shipping ( $result, $options, $O ) {
		return (float) $O->freight;
	}

	/**
	 * Provides the billing address state/province name
	 *
	 * @api `shopp('purchase.state')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The billing address state name
	 **/
	public static function state ( $result, $options, $O ) {
		$state = esc_html($O->state);
		if ( strlen($O->state) > 2 ) return $state;
		$regions = Lookup::country_zones();

		if ( isset($regions[ $O->country ]) ) {
			$states = $regions[ $O->country ];
			if ( isset($states[ $O->state ]) )
				return esc_html($states[ $O->state ]);
		}

		return $state;
	}

	/**
	 * Provides the merchant processing status for the order
	 *
	 * @api `shopp('purchase.status')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The order processing status
	 **/
	public static function status ( $result, $options, $O ) {
		$Shopp = Shopp::object();
		$labels = shopp_setting('order_status');
		if ( empty($labels) ) $labels = array('');
		return $labels[ $O->status ];
	}

	/**
	 * Provides the order subtotal, the sum of all the line item totals
	 *
	 * @api `shopp('purchase.subtotal')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The subtotal amount
	 **/
	public static function subtotal ( $result, $options, $O ) {
		return (float) $O->subtotal;
	}

	/**
	 * Provides the total tax for the order
	 *
	 * @api `shopp('purchase.tax')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The order tax
	 **/
	public static function tax ( $result, $options, $O ) {
		return (float) $O->tax;
	}

	/**
	 * Provides the order grand total (the sum of all item costs, fees and discounts)
	 *
	 * @api `shopp('purchase.total')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The total amount
	 **/
	public static function total ( $result, $options, $O ) {
		return (float) $O->total;
	}

	/**
	 * Provides the total number of line item entries in the order
	 *
	 * This call should not be confused with the total quantity (sum of all line item quantities)
	 *
	 * @api `shopp('purchase.total-items')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The number of line items
	 **/
	public static function total_items ( $result, $options, $O ) {
		return count($O->purchased);
	}

	/**
	 * Provides the transaction ID for the payment
	 *
	 * @api `shopp('purchase.txnid')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string The transaction ID
	 **/
	public static function txnid ( $result, $options, $O ) {
		return $O->txnid;
	}

	/**
	 * Provides the URL of for the order
	 *
	 * @api `shopp('context.property')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return void
	 **/
	public static function url ( $result, $options, $O ) {
		return Shopp::url(array('order' => $Purchase->id), 'account');
	}

	/**
	 * Provides the extra billing street address line
	 *
	 * @api `shopp('purchase.xaddress')`
	 * @since 1.0
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return string THe extra billing address
	 **/
	public static function xaddress ( $result, $options, $O ) {
		return esc_html($O->xaddress);
	}

	/**
	 * Checks if inclusive taxes were applied to the taxable item costs
	 *
	 * @internal
	 * @since 1.3
	 *
	 * @param string        $result  The output
	 * @param array         $options The options
	 * @param ShoppPurchase $O       The working object
	 * @return void
	 **/
	private static function _inclusive_taxes ( ShoppPurchase $O ) {
		return ( 'inclusive' == $O->taxing );
	}

	/**
	 * Helper to apply or exclude taxes from a single amount based on inclusive tax settings and the tax option
	 *
	 * internal
	 * @since 1.3
	 *
	 * @param float $amount The amount to add taxes to, or exclude taxes from
	 * @param ShoppProduct $O The product to get properties from
	 * @param boolean $istaxed Whether the amount can be taxed
	 * @param boolean $taxoption The Theme API tax option given the the tag
	 * @param array $taxrates A list of taxrates that apply to the product and amount
	 * @return float The amount with tax added or tax excluded
	 **/
	private static function _taxes ( $amount, ShoppPurchased $Item, $taxoption = null, $quantity = 1) {

		$inclusivetax = self::_inclusive_taxes(ShoppPurchase());
		if ( isset($taxoption) && ( $inclusivetax ^ $taxoption ) ) {

			if ( $taxoption ) $amount += ( $Item->unittax * $quantity );
			else $amount = $amount -= ( $Item->unittax * $quantity );
		}

		return (float) $amount;
	}

}

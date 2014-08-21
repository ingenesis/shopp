<?php
/**
 * Item.php
 *
 * Cart line items generated from product/price objects
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, April, 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage cart
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCartItem {

	static $api = 'cartitem';		// Theme API name

	public $product = false;		// The source product ID
	public $priceline = false;		// The source price ID
	public $category = false;		// The breadcrumb category
	public $sku = false;			// The SKU of the product/price combination
	public $type = false;			// The type of the product price object
	public $name = false;			// The name of the source product
	public $description = false;	// Short description from the product summary
	public $option = false;			// The option ID of the price object
	public $variant = array();		// The selected variant
	public $variants = array();		// The available variants
	public $addons = array();		// The addons added to the item
	public $image = false;			// The cover image for the product
	public $data = array();			// Custom input data
	public $processing = array();	// Per item order processing delays
	public $quantity = 0;			// The selected quantity for the line item
	public $bogof = false;			// The BOGOF discount
	public $qtydelta = 0;			// The change in quantity
	public $addonsum = 0;			// The sum of selected addons
	public $unitprice = 0;			// Per unit price
	public $priced = 0;				// Per unit price after discounts are applied
	public $totald = 0;				// Total price after discounts
	public $subprice = 0;			// Regular price for subscription payments
	public $unittax = 0;			// Per unit tax amount
	public $pricedtax = 0;			// Per unit tax amount after discounts are applied
	public $tax = 0;				// Sum of the per unit tax amount for the line item
	public $taxes = array();		// A list of taxes that apply
	public $taxrate = 0;			// Tax rate for the item
	public $taxable = array();		// Per unit taxable amounts (baseprice & add-ons that are taxed)
	public $total = 0;				// Total cost of the line item (unitprice x quantity)
	public $discount = 0;			// Discount applied to each unit
	public $discounts = 0;			// Sum of per unit discounts (discount for the line)
	public $weight = 0;				// Unit weight of the line item (unit weight)
	public $length = 0;				// Unit length of the line item (unit length)
	public $width = 0;				// Unit width of the line item (unit width)
	public $height = 0;				// Unit height of the line item (unit height)
	public $shipfee = 0;			// Shipping fees for each unit of the line item
	public $download = false;		// Download ID of the asset from the selected price object
	public $shipping = false;		// Shipping setting of the selected price object
	public $recurring = false;		// Recurring flag when the item requires recurring billing
	public $shipped = false;		// Shipped flag when the item needs shipped
	public $inventory = false;		// Inventory setting of the selected price object
	public $istaxed = false;		// Taxable setting of the selected price object
	public $includetax = false;		// Taxes are incuded in the price
	public $freeshipping = false;	// Free shipping status of the selected price object
	public $packaging = false;		// Should the item be packaged separately

	/**
	 * Constructs a line item from a Product object and identified price object
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param object $Product Product object
	 * @param mixed $pricing A list of price IDs; The option key of a price object; or a Price object
	 * @param int $category (optional)The breadcrumb category ID where the product was added from
	 * @param array $data (optional) Custom data associated with the line item
	 * @param array $addons (optional) A set of addon options
	 * @return void
	 **/
	public function __construct ( ShoppProduct $Product = null, $pricing = null, $category = false, array $data = array(), array $addons = array() ) {

		$args = func_get_args();
		if ( empty($args) ) return;

		if ( get_class($Product) == 'ShoppPurchased' ) $this->load_purchased($Product);
		else $this->load($Product, $pricing, $category, $data, $addons);

	}

	/**
	 * Loads or constructs the Item object from product and product pricing parameters
	 *
	 * @author John Dillick, Jonathan Davis
	 * @since 1.2
	 *
	 * @param object $Product Product object
	 * @param mixed $pricing A list of price IDs; The option key of a price object; or a Price object
	 * @param int $category (optional)The breadcrumb category ID where the product was added from
	 * @param array $data (optional) Custom data associated with the line item
	 * @param array $addons (optional) A set of addon options
	 * @return void
	 **/
	public function load ( $Product, $pricing, $category = false, $data = array(), $addons = array() ) {
		$Product->load_data();

		// If option ids are passed, lookup by option key, otherwise by id
		$Price = false;
		if ( is_array($pricing) && ! empty($pricing) ) {
			$optionkey = $Product->optionkey($pricing);
			if ( ! isset($Product->pricekey[ $optionkey ]) ) $optionkey = $Product->optionkey($pricing, true); // deprecated prime
			if ( isset($Product->pricekey[ $optionkey ]) ) $Price = $Product->pricekey[ $optionkey ];
		} elseif ( is_numeric($pricing) ) {
			$Price = $Product->priceid[ $pricing ];
		} elseif ( is_a($pricing, 'ShoppPrice') ) {
			$Price = $pricing;
		}

		// Find single product priceline
		if ( ! $Price && ! Shopp::str_true($Product->variants) ) {
			foreach ( $Product->prices as &$Price ) {
				$stock = true;
				if ( Shopp::str_true($Price->inventory) && 1 > $Price->stock ) $stock = false;
				if ( 'product' == $Price->context && 'N/A' != $Price->type && $stock ) break;
			}
		}

		// Find first available variant priceline
		if ( ! $Price && Shopp::str_true($Product->variants) ) {
			foreach ( $Product->prices as &$Price ) {
				$stock = true;
				if ( Shopp::str_true($Price->inventory) && 1 > $Price->stock ) $stock = false;
				if ( 'variation' == $Price->context && 'N/A' != $Price->type && $stock ) break;
			}
		}

		if ( isset($Product->id) ) $this->product = $Product->id;
		if ( isset($Price->id) ) $this->priceline = $Price->id;

		$this->name = $Product->name;
		$this->slug = $Product->slug;

		$this->category = $category;
		$this->categories = $this->namelist($Product->categories);
		$this->tags = $this->namelist($Product->tags);
		$this->image = current($Product->images);
		$this->description = $Product->summary;

		if ( shopp_setting_enabled('taxes') ) // Must init taxable above addons roll-up #2825
			$this->taxable = array(); // Re-init during ShoppCart::change() loads #2922

		// Product has variants
		if ( Shopp::str_true($Product->variants) && empty($this->variants) )
			$this->variants($Product->prices);

		// Product has Addons
		if ( Shopp::str_true($Product->addons) ) {
			if ( ! empty($this->addons) ) // Compute addon differences
				$addons = array_diff($addons, array_keys($this->addons));
			$this->addons($this->addonsum, $addons, $Product->prices);
		}

		if ( isset($Price->id) )
			$this->option = $this->mapprice($Price);

		$this->sku = $Price->sku;
		$this->type = $Price->type;
		$this->sale = Shopp::str_true($Product->sale);
		$this->freeshipping = ( isset($Price->freeshipping) ? $Price->freeshipping : false );

		$baseprice = roundprice( $this->sale ? $Price->promoprice : $Price->price );
		$this->unitprice = $baseprice + $this->addonsum;

		if ( shopp_setting_enabled('taxes') ) {
			if ( Shopp::str_true($Price->tax) ) $this->taxable[] = $baseprice;
			$this->istaxed =  array_sum($this->taxable) > 0 ;
			$this->includetax = shopp_setting_enabled('tax_inclusive');
			if ( isset($Product->excludetax) && Shopp::str_true($Product->excludetax) )
				$this->includetax = false;
		}

		if ( 'Donation' == $this->type )
			$this->donation = $Price->donation;

		$this->inventory = Shopp::str_true($Price->inventory) && shopp_setting_enabled('inventory');

		$this->data = stripslashes_deep(esc_attrs($data));

		// Handle Recurrences
		if ($this->has_recurring()) {
			$this->subprice = $this->unitprice;
			$this->recurrences();
			if ( $this->is_recurring() && $this->has_trial() ) {
				$trial = $this->trial();
				$this->unitprice = $trial['price'];
			}
		}

		// Map out the selected menu name and option
		if ( Shopp::str_true($Product->variants) ) {
			$selected = explode(',', $this->option->options); $s = 0;
			$variants = isset($Product->options['v']) ? $Product->options['v'] : $Product->options;
			foreach ( (array) $variants as $i => $menu ) {
				foreach( (array) $menu['options'] as $option ) {
					if ( $option['id'] == $selected[ $s ] ) {
						$this->variant[ $menu['name'] ] = $option['name']; break;
					}
				}
				$s++;
			}
		}

		$this->packaging = Shopp::str_true( shopp_product_meta($Product->id, 'packaging') );

		if ( ! empty($Price->download) ) $this->download = $Price->download;

		if ( 'Shipped' == $Price->type ) $this->shipped = true;

		if ( $this->shipped ) {
			$dimensions = array(
				'weight' => 0,
				'length' => 0,
				'width' => 0,
				'height' => 0
			);

			if ( Shopp::str_true($Price->shipping) ) {
				$this->shipfee = $Price->shipfee;
				if ( isset($Price->dimensions) )
					$dimensions = array_merge($dimensions, $Price->dimensions);
			} else $this->freeshipping = true;

			if ( isset($Product->addons) && Shopp::str_true($Product->addons) ) {
				$this->addons($dimensions, $addons, $Product->prices, 'dimensions');
				$this->addons($this->shipfee, $addons, $Product->prices, 'shipfee');
			}

			foreach ( $dimensions as $dimension => $value ) {
				$this->$dimension = $value;
			}
			if ( isset($Product->processing) && Shopp::str_true($Product->processing) ) {
				if ( isset($Product->minprocess) ) $this->processing['min'] = $Product->minprocess;

				if ( isset($Product->maxprocess) ) $this->processing['max'] = $Product->maxprocess;
			}

		}

	}

	/**
	 * Loads or constructs item properties from a purchased product
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function load_purchased ( $Purchased ) {

		$this->load(new ShoppProduct($Purchased->product), $Purchased->price, false);

		// Copy matching properties
		$properties = get_object_vars($Purchased);
		foreach((array)$properties as $property => $value) {
			if ( property_exists($this,$property) )
					$this->{$property} = sDB::clean($value);
		}

	}

	/**
	 * Validates the line item
	 *
	 * Ensures the product and price object exist in the catalog and that
	 * inventory is available for the selected price variant.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function valid () {
		// no product or no price specified
		if ( ! $this->product || ! $this->priceline ) {
			new ShoppError(__('The product could not be added to the cart because it could not be found.','Shopp'), 'cart_item_invalid', SHOPP_ERR);
			return false;
		}

		// the item is not in stock
		if ( ! $this->instock() ) {
			new ShoppError(__('The product could not be added to the cart because it is not in stock.','Shopp'), 'cart_item_invalid', SHOPP_ERR);
			return false;
		}
		return true;
	}

	/**
	 * Provides the polynomial fingerprint of the item for detecting uniqueness
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return string
	 **/
	public function fingerprint () {
		$_  = $this->product.$this->priceline;
		if ( empty($_) ) $_ = $this->name;
		if ( empty($_) ) $_ = mktime();
		if ( ! empty($this->addons) )	$_ .= serialize($this->addons);
		if ( ! empty($this->data) )		$_ .= serialize($this->data);
		return __CLASS__ . '_' . hash('crc32', $_);
	}

	/**
	 * Sets the quantity of the line item
	 *
	 * Sets the quantity only if stock is available or
	 * the donation amount to the donation minimum.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $qty The quantity to set the line item to
	 * @return void
	 **/
	public function quantity ( $qty = false ) {
		$current = $this->quantity;
		if ( false === $qty ) return $current;

		if ( $this->type == 'Donation' && Shopp::str_true($this->donation['var']) ) {
			if ( ! ( Shopp::str_true($this->donation['min']) && Shopp::floatval($qty) < $this->unitprice ) )
				$this->unitprice = Shopp::floatval($qty,false);
			$this->quantity = 1;
			$qty = 1;
		}

		if ( in_array($this->type, array('Membership','Subscription')) || 'Download' == $this->type && ! shopp_setting_enabled('download_quantity') ) {
			return ($this->quantity = 1);
		}

		$qty = preg_replace('/[^\d+]/','',$qty);
		$this->quantity = (int)$qty;

		if ( ! $this->instock($qty) ) {

			$levels = array($this->option->stock);
			foreach ($this->addons as $addon) // Take into account stock levels of any addons
				if ( Shopp::str_true($addon->inventory) ) $levels[] = $addon->stock;

			if ( $qty > $min = min($levels) ) {

				if ( shopp_setting_enabled('backorders') ) {
					$this->backordered = $qty - $min;
					shopp_add_error(Shopp::__('&quot;%s&quot; is not available in the requested quantity. %d of the items will be backordered with delayed delivery.', $this->name, $this->backordered));
				} else {
					shopp_add_error(Shopp::__('&quot;%s&quot; is not available in the requested quantity.', $this->name));
					if ( ! $min ) return; // don't set min to item quantity if no stock
					$this->quantity = $min;
				}

			}
		}

		$this->qtydelta = $this->quantity - $current;
		if ( 0 != $this->qtydelta ) $this->totals();
	}

	/**
	 * Adds a specified quantity to the line item
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function add ( $qty ) {
		if ( $this->type == 'Donation' && Shopp::str_true($this->donation['var']) ) {
			$qty = Shopp::floatval($qty);
			$this->quantity = $this->unitprice;
		}
		$this->quantity($this->quantity + $qty);
	}

	/**
	 * Generates an option menu of available price variants
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param int $selection (optional) The selected price option
	 * @param float $taxrate (optional) The tax rate to apply to pricing information
	 * @return string
	 **/
	public function options ($selection = '') {
		if (empty($this->variants)) return '';

		$string = '';
		foreach($this->variants as $option) {
			if ($option->type == 'N/A') continue;
			$currently = (Shopp::str_true($option->sale)?$option->promoprice:$option->price)+$this->addonsum;
			$difference = (float)($currently+$this->unittax)-($this->unitprice+$this->unittax);

			$price = '';
			if ($difference > 0) $price = '  (+'.money($difference).')';
			if ($difference < 0) $price = '  (-'.money(abs($difference)).')';

			$selected = '';
			if ($selection == $option->id) $selected = ' selected="selected"';
			$disabled = '';
			if ( Shopp::str_true($option->inventory) && $option->stock < $this->quantity )
				$disabled = ' disabled="disabled"';

			$string .= '<option value="'.$option->id.'"'.$selected.$disabled.'>'.$option->label.$price.'</option>';
		}
		return $string;

	}

	/**
	 * Populates the variants from a collection of price objects
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $prices A list of Price objects
	 * @return void
	 **/
	public function variants ($prices) {
		foreach ($prices as $price)	{
			if ('N/A' == $price->type || 'variation' != $price->context) continue;
			$pricing = $this->mapprice($price);
			if ($pricing) $this->variants[] = $pricing;
		}
	}

	/**
	 * Sums values of the applied addons properties
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $prices A list of Price objects
	 * @return void
	 **/
	public function addons ( &$sum, array $addons, array $prices, $property = 'pricing' ) {

		foreach ( $prices as $p )	{
			if ( 'N/A' == $p->type || 'addon' != $p->context ) continue;
			$pricing = $this->mapprice($p);

			if ( empty($pricing) ) continue; // Skip if no pricing available
			if ( in_array( ($pricing->id * -1), $addons ) ) { // If the addon is marked for removal
				unset($this->addons[ $pricing->id ]); // Remove it from our addons list
				continue;
			}

			if ( ! in_array($pricing->id, $addons)) continue;

			if ( 'Shipped' == $p->type ) $this->shipped = true;

			if ( 'pricing' == $property ) {
				$pricing->unitprice = Shopp::str_true($p->sale) ? $p->promoprice : $p->price;
				$this->addons[ $pricing->id ] = $pricing;
				$sum += $pricing->unitprice;

				if ( shopp_setting_enabled('taxes') && Shopp::str_true($pricing->tax) )
					$this->taxable[] = $pricing->unitprice;

			} elseif ( 'dimensions' == $property ) {
				if ( ! Shopp::str_true($p->shipping) || 'Shipped' != $p->type ) continue;
				foreach ( $p->dimensions as $dimension => $value )
					$sum[ $dimension ] += $value;
			} elseif ( 'shipfee' == $property ) {
				if ( ! Shopp::str_true($p->shipping) ) continue;
				$sum += $pricing->shipfee;
			} else {
				if ( isset($pricing->$property) ) $sum += $pricing->$property;
			}
		}

	}

	/**
	 * Maps price object properties
	 *
	 * Populates only the necessary properties from a price object
	 * to a variant option to cut down on line item data size
	 * for better serialization performance.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param Object $price Price object to minimize
	 * @return object An Item variant object
	 **/
	public function mapprice ($price) {
		$map = array(
			'id','type','label','sale','promoprice','price',
			'tax','inventory','stock','sku','options','dimensions',
			'shipfee','download','recurring'
		);
		$_ = new stdClass();
		foreach ($map as $property) {
			if (empty($price->options) && $property == 'label') continue;
			if (isset($price->{$property})) $_->{$property} = $price->{$property};
		}
		return $_;
	}

	/**
	 * Collects a list of name properties from a list of objects
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $items List of objects with a name property to grab
	 * @return array List of names
	 **/
	public function namelist ($items) {
		$list = array();
		foreach ($items as $item) $list[$item->id] = $item->name;
		return $list;
	}

	/**
	 * Sets the current subscription payment plan status
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	public function recurrences () {
		if (empty($this->option->recurring)) return;

		// if free subscription, don't process as subscription
		if ( 0 == $this->option->promoprice ) return;
		extract($this->option->recurring);

		$term_labels = array(
			// _nx_noop( singular, plural, context)
			'trial' => array(
				'd' => _nx_noop("%s for the first day.",  "%s for the first %s days.", 		"Trial term label: '$10 for the first day.' or '$5 for the first 10 days.'"),
				'w' => _nx_noop("%s for the first week.", "%s for the first %s weeks.", 	"Trial term label: '$10 for the first week.' or '$5 for the first 10 weeks.'"),
				'm' => _nx_noop("%s for the first month.","%s for the first %s months.", 	"Trial term label: '$10 for the first month.' or '$5 for the first 10 months.'"),
				'y' => _nx_noop("%s for the first year.", "%s for the first %s years.", 	"Trial term label: '$10 for the first year.' or '$5 for the first 10 years.'"),
			),
			'freetrial' => array(
				'd' => _nx_noop("Free for the first day.",   "Free for the first %s days.",		"Free trial label."),
				'w' => _nx_noop("Free for the first week.",  "Free for the first %s weeks.",	"Free trial label."),
				'm' => _nx_noop("Free for the first month.", "Free for the first %s months.", 	"Free trial label."),
				'y' => _nx_noop("Free for the first year.",  "Free for the first %s years.", 	"Free trial label."),
			),
			'aftertrial' => array(
				'd' => _nx_noop("%s per day after the trial period.", "%s every %s days after the trial period.",		"Subscription term label: '$10 per day after the trial period.' or '$5 every 10 days after the trial period.'"),
				'w' => _nx_noop("%s per week after the trial period.", "%s every %s weeks after the trial period.",		"Subscription term label: '$10 per week after the trial period.' or '$5 every 10 weeks after the trial period.'"),
				'm' => _nx_noop("%s per month after the trial period.", "%s every %s months after the trial period.",	"Subscription term label: '$10 per month after the trial period.' or '$5 every 10 months after the trial period.'"),
				'y' => _nx_noop("%s per year after the trial period.", "%s every %s years after the trial period.", 	"Subscription term label: '$10 per year after the trial period.' or '$5 every 10 years after the trial period.'"),
			),
			'period' => array(
				'd' => _nx_noop("%s per day.", "%s every %s days.", 	"Subscription term label: '$10 per day.' or '$5 every 10 days.'"),
				'w' => _nx_noop("%s per week.", "%s every %s weeks.", 	"Subscription term label: '$10 per week.' or '$5 every 10 weeks.'"),
				'm' => _nx_noop("%s per month.", "%s every %s months.", "Subscription term label: '$10 per month.' or '$5 every 10 months.'"),
				'y' => _nx_noop("%s per year.", "%s every %s years.", 	"Subscription term label: '$10 per year.' or '$5 every 10 years.'"),
			),
		);

		$rebill_labels = array(
			0 => __('Subscription rebilled unlimited times.', 'Shopp'),
			1 => _n_noop('Subscription rebilled once.', 'Subscription rebilled %s times.'),
		);

		// Build Trial Label
		if ( Shopp::str_true($trial) ) {
			// pick untranlated label
			$trial_label = ( $trialprice > 0 ? $term_labels['trial'][$trialperiod] : $term_labels['freetrial'][$trialperiod] );

			// pick singular or plural translation
			$trial_label = translate_nooped_plural($trial_label, $trialint, 'Shopp');

			// string replacements
			if ( $trialprice > 0 ) {
				$trial_label = sprintf($trial_label, money($trialprice), $trialint);
			} else {
				$trial_label = sprintf($trial_label, $trialint);
			}

			$this->data[_x('Trial Period','Item trial period label','Shopp')] = $trial_label;
		}

		// pick untranslated label
		$normal = Shopp::str_true($trial) ? 'aftertrial' : 'period';
		$subscription_label = $term_labels[$normal][$period];

		// pick singular or plural translation
		$subscription_label = translate_nooped_plural($subscription_label, $interval);
		$subscription_label = sprintf($subscription_label, money($this->subprice), $interval);

		// pick rebilling label and translate if plurals
		$rebill_label = sprintf(translate_nooped_plural($rebill_labels[1], $cycles, 'Shopp'), $cycles);
		if ( ! $cycles ) $rebill_label =  $rebill_labels[0];

		$this->data[_x('Subscription','Subscription terms label','Shopp')] = array($subscription_label,$rebill_label);

		$this->recurring = true;
	}

	/**
	 * Unstock the item from inventory
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function unstock () {
		// no inventory tracking system wide
		if ( ! shopp_setting_enabled('inventory') ) return;

		// collect list of ids to update
		$ids = array();
		if ( $this->inventory ) $ids[] = $this->priceline;
		if ( ! empty($this->addons) ) {
			foreach ($this->addons as $addon) {
				if ( Shopp::str_true($addon->inventory) )
					$ids[] = $addon->id;
			}
		}

		// no inventory tracked base item or addons
		if ( empty($ids) ) return;

		// Update stock in the database
		$pricetable = ShoppDatabaseObject::tablename(ShoppPrice::$table);
		foreach ( $ids as $priceline ) {
			db::query("UPDATE $pricetable SET stock=stock-{$this->quantity} WHERE id='{$priceline}'");
		}

		// Force summary update to get new stock warning levels on next load
		$summarytable = ShoppDatabaseObject::tablename(ProductSummary::$table);
		db::query("UPDATE $summarytable SET modified='".ProductSummary::$_updates."' WHERE product='{$this->product}'");

		// Update
		if ( ! empty($this->addons) ) {
			foreach ($this->addons as &$Addon) {
				if ( Shopp::str_true($addon->inventory) ) {
					$Addon->stock -= $this->quantity;
				}
			}
		}

		// Update stock in the model
		if ( $this->inventory ) $this->option->stock = $this->option->stock - $this->quantity;
	}

	/**
	 * Verifies the item is in stock
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function instock ( $qty = false ) {
		if ( ! shopp_setting_enabled('inventory') ) return true;

		if ( ! $this->inventory ) {
			// base item doesn't track inventory and no addons
			if ( empty($this->addons) ) return true;

			$addon_inventory = false;
			foreach ($this->addons as $addon) {
				if ( Shopp::str_true($addon->inventory) )
					$addon_inventory = true;
			}

			// base item doesn't track inventory, but an addon does
			if ( ! $addon_inventory ) return true;
		}

		// need to get the current minimum stock for item + addons
		$this->option->stock = $this->getstock();

		if ( $qty ) return $this->option->stock >= $qty;
		return ( $this->option->stock > 0 );
	}

	/**
	 * Determines the minimum stock level of the item and its addons
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return int The amount of stock available
	 **/
	public function getstock () {
		$stock = apply_filters('shopp_cartitem_stock',false,$this);
		if ($stock !== false) return $stock;

		$table = ShoppDatabaseObject::tablename(ShoppPrice::$table);
		$ids = array($this->priceline);

		if ( ! empty($this->addons) ) {
			foreach ($this->addons as $addon) {
				if ( Shopp::str_true($addon->inventory) )
					$ids[] = $addon->id;
			}
		}

		$result = db::query("SELECT min(stock) AS stock FROM $table WHERE 0 < FIND_IN_SET(id,'".join(',',$ids)."')");
		if (isset($result->stock)) return $result->stock;

		return $this->option->stock;
	}

	/**
	 * is_recurring()
	 *
	 * Tests if the item is recurring
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return bool true if recurring, false otherwise
	 **/
	public function is_recurring () {
		$recurring = ($this->recurring && ! empty($this->option) && ! empty($this->option->recurring));
		return apply_filters('shopp_cartitem_recurring', $recurring, $this);
	}

	public function has_recurring () {
		return (! empty($this->option) && ! empty($this->option->recurring));
	}

	/**
	 * has_trial()
	 *
	 * Tests if item is recurring and has a trial period
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return bool true if recurring and has trial, false otherwise
	 **/
	public function has_trial () {
		$trial = false;
		if ( $this->is_recurring() && Shopp::str_true($this->option->recurring['trial']) ) $trial = true;
		return apply_filters('shopp_cartitem_hastrial', $trial, $this);
	}

	/**
	 * trial()
	 *
	 * Gets the trial subscription settings for recurring items.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return mixed false if no trial, array of trial interval (interval), trial period (period), and trial price (price) if set
	 **/
	public function trial () {
		$trial = false;

		if ( $this->has_trial() ) {
			$trial = array(
				'interval' => $this->option->recurring['trialint'],
				'period' => $this->option->recurring['trialperiod'],
				'price' => $this->option->recurring['trialprice']
			);
		}

		return apply_filters('shopp_cartitem_trial_settings', $trial, $this);
	}

	/**
	 * recurring()
	 *
	 * Gets the recurring settings for a recurring item.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @return mixed false if not a recurring item, array of interval (interval), period (period), and number of cycles (cycles) if set.
	 **/
	public function recurring () {
		$recurring = false;

		if ( $this->is_recurring() ) {
			$recurring = array(
				'interval' => $this->option->recurring['interval'],
				'period' => $this->option->recurring['period'],
				'cycles' => $this->option->recurring['cycles']
			);
		}

		return apply_filters('shopp_cartitem_recurring_settings', $recurring, $this);
	}


	/**
	 * Match a rule to the item
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $rule A structured rule array
	 * @return boolean
	 **/
	public function match ($rule) {
		extract($rule);

		switch($property) {
			case 'Any item name': $subject = $this->name; break;
			case 'Any item quantity': $subject = (int)$this->quantity; break;
			case 'Any item amount': $subject = $this->total; break;

			case 'Name': $subject = $this->name; break;
			case 'Category': $subject = $this->categories; break;
			case 'Tag name': $subject = $this->tags; break;
			case 'Variation': $subject = $this->option->label; break;

			case 'Input name':
				foreach($this->data as $inputName=>$inputValue){
					if (ShoppPromo::match_rule($inputName, $logic, $value, $property)){
						return true;
					}
				}
				return false;
			case 'Input value':
				foreach($this->data as $inputName=>$inputValue){
					if (ShoppPromo::match_rule($inputValue, $logic, $value, $property)){
						return true;
					}
				}
				return false;

			case 'Quantity': $subject = $this->quantity; break;
			case 'Unit price': $subject = $this->unitprice; break;
			case 'Total price': $subject = $this->total; break;
			case 'Discount amount': $subject = $this->discount; break;

		}
		return ShoppPromo::match_rule($subject,$logic,$value,$property);
	}

	// @deprecated
	public function taxrule ($rule) {
		switch ($rule['p']) {
			case 'product-name': return ($rule['v'] == $this->name); break;
			case 'product-tags': return (in_array($rule['v'],$this->tags)); break;
			case 'product-category': return (in_array($rule['v'],$this->categories)); break;
		}
		return false;
	}

	public function rediscount () {
		$this->bogof = 0;
		$this->discount = 0;
	}

	/**
	 * Recalculates line item amounts
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function totals () {

		$this->bogof = 0;
		$this->discount = 0;

		do_action('shopp_cart_item_pretotal', $this);

		$this->priced = ( $this->unitprice - $this->discount );		// discounted unit price

		$this->discounts();
		if ( $this->istaxed )
			$this->taxes();

		$this->total = ( $this->unitprice * $this->quantity ); // total undiscounted, pre-tax line price
		$this->totald = ( $this->priced * $this->quantity ); // total discounted, pre-tax line price

		if ( $this->is_recurring() ) {
			$this->subprice = $this->priced;
			if ( $this->has_trial() ) {
				$this->subprice = ( $this->option->promoprice - $this->discount );
				$this->discounts = 0;
				$this->recurrences();
			}
		}

		do_action('shopp_cart_item_retotal', $this);

	}

	public function discounts () {
		$this->discounts = ( $this->discount * $this->quantity );	// total item discount figure

		// Buy X Get Y Free (Buy 1 Get 1 Free or BOGOF) discounts
		if ( is_array($this->bogof) ) {
			$this->bogof = array_sum($this->bogof);
			$this->discounts += $this->bogof * $this->unitprice;
		}
	}

	/**
	 * Calculate taxes that apply to the item
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param integer $quantity The taxable quantity of items
	 * @return void
	 **/
	public function taxes ( $quantity = 1 ) {
		if ( ! $this->istaxed ) return;

		$Tax = ShoppOrder()->Tax;
		if ( empty($Tax) ) $Tax = new ShoppTax; // ShoppTax support for Dev API calls

		// For all the price units (base product and any addons),
		// distribute discounts across taxable amounts using weighted averages
		$_ = array();
		if ( $this->unitprice > 0 ) {
			$taxable = 0;
   			foreach ( $this->taxable as $amount )
   				$_[] = $amount - ( ($amount / $this->unitprice) * $this->discount );
		}

		$taxable = (float) array_sum($_);
		$taxableqty = ( $this->bogof && $this->bogof != $this->quantity ) ? $this->quantity - $this->bogof : $this->quantity;

		$Tax->rates($this->taxes, $Tax->item($this));

		$this->unittax = ShoppTax::calculate($this->taxes, $taxable);
		$this->tax = $Tax->total($this->taxes, (int) $taxableqty);

		// Handle inclusive tax price adjustments for non-EU markets or alternate tax rate markets
		$adjustment = ShoppTax::adjustment($this->taxes);
		if ( 1 != $adjustment ) {

			if ( ! isset($this->taxprice) )
				$this->taxprice = $this->unitprice;

			// Modify the unitprice from the original tax inclusive price and update the discounted price
			$this->unitprice = ($this->taxprice / $adjustment);
			$this->priced = ( $this->unitprice - $this->discount );

		} elseif ( isset($this->taxprice) ) { // Undo tax price adjustments
			$this->unitprice = $this->taxprice;
			unset($this->taxprice);
		}

	}

}
<?php
/**
 * Cart.php
 *
 * The shopping cart system
 *
 * @author Jonathan Davis
 * @version 1.1
 * @copyright Ingenesis Limited, January 19, 2010
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @subpackage cart
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * The Shopp shopping cart
 *
 * @author Jonathan Davis
 * @since 1.3
 * @package
 **/
class ShoppCart extends ListFramework {

	// properties
	public $shipped = array();		// Reference list of shippable Items
	public $downloads = array();	// Reference list of digital Items
	public $recurring = array();	// Reference list of recurring Items
	public $discounts = array();	// List of promotional discounts applied
	public $promocodes = array();	// List of promotional codes applied
	public $processing = array(		// Min-Max order processing timeframe
		'min' => 0, 'max' => 0
	);
	public $checksum = false;		// Cart contents checksum to track changes

	// Object properties
	public $Added = false;			// Last Item added
	public $Totals = false;			// Cart OrderTotals system

	// Internal properties
	public $changed = false;		// Flag when Cart updates and needs retotaled
	public $added = false;			// The index of the last item added

	public $retotal = false;
	public $handlers = false;

	/**
	 * Cart constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function __construct () {
		$this->listeners();					// Establish our command listeners
	}

	/**
	 * Restablish listeners after being loaded from the session
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __wakeup () {
		$this->listeners();
	}

	public function __sleep () {
		$properties = array_keys( get_object_vars($this) );
		return array_diff($properties, array('shipped', 'downloads', 'recurring', 'Added', 'retotal', 'promocodes',' discounts'));
	}

	/**
	 * Listen for events to trigger cart functionality
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function listeners () {
		add_action('shopp_cart_request', array($this, 'request') );
		add_action('shopp_cart_updated', array($this, 'totals'), 100 );
		add_action('shopp_session_reset', array($this, 'clear') );

		add_action('shopp_cart_item_retotal', array($this, 'processtime') );
		add_action('shopp_init', array($this, 'tracking'));

		// Recalculate cart based on logins (for customer type discounts)
		add_action('shopp_login', array($this, 'total'));
		add_action('shopp_logged_out', array($this, 'total'));
	}

	/**
	 * Processes cart requests and updates the cart data
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function request () {

		$command = 'update'; // Default command
		$commands = array('add', 'empty', 'update', 'remove');

		$request = isset($_REQUEST['cart']) ? strtolower($_REQUEST['cart']) : false;

		if ( in_array( $request, $commands) )
			$command = $request;

		$allowed = array(
			'quantity' => 1,
			'product' => false,
			'products' => array(),
			'item' => false,
			'items' => array(),
			'remove' => array()
		);
		$request = array_intersect_key($_REQUEST,$allowed); // Filter for allowed arguments
		$request = array_merge($allowed, $request);			// Merge to defaults

		extract($request, EXTR_SKIP);

		switch( $command ) {
			case 'empty': $this->clear(); break;
			case 'remove': $this->removeitem( key($remove) ); break;
			case 'add':

				if ( false !== $product )
					$products[ $product ] = array('product' => $product);

				if ( apply_filters('shopp_cart_add_request', ! empty($products) && is_array($products)) ) {
					foreach ( $products as $product )
						$this->addrequest($product);
				}

				break;
			default:

				if ( false !== $item && $this->exists($item) )
					$items[ $item ] = array('quantity' => $quantity);

				if ( apply_filters('shopp_cart_remove_request', ! empty($remove) && is_array($remove)) ) {
					foreach ( $remove as $id => $value )
						$this->rmvitem($id);
				}

				if ( apply_filters('shopp_cart_update_request', ! empty($items) && is_array($items)) ) {
					foreach ( $items as $id => $item )
						$this->updates($id,$item);
				}

		}

		do_action('shopp_cart_updated', $this);

	}

	private function addrequest ( array $request ) {

		$defaults = array(
			'quantity' => 1,
			'product' => false,
			'price' => false,
			'category' => false,
			'item' => false,
			'options' => array(),
			'data' => array(),
			'addons' => array()
		);
		$request = array_merge($defaults, $request);
		extract($request, EXTR_SKIP);

		if ( '0' == $quantity ) return;

		$Product = new Product( (int)$product );
		if ( isset($options[0]) && ! empty($options[0]) ) $price = $options;

		if ( ! empty($Product->id) ) {
			if ( false !== $item )
				$result = $this->change($item, $Product, $price);
			else
				$result = $this->additem($quantity, $Product, $price, $category, $data, $addons);
		}

	}

	private function updates ( $item, array $request ) {
		$CartItem = $this->get($item);
		$defaults = array(
			'quantity' => 1,
			'product' => false,
			'price' => false
		);
		$request = array_merge($defaults, $request);
		extract($request, EXTR_SKIP);

		if ( $product == $CartItem->product && false !== $price && $price != $CartItem->priceline)
			$this->change($item,$product,$price);
		else $this->setitem($item,$quantity);

	}

	/**
	 * Responds to AJAX-based cart requests
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return string JSON response
	 **/
	public function ajax () {

		if ('html' == strtolower($_REQUEST['response'])) {
			echo shopp('cart','get-sidecart');
			exit();
		}
		$AjaxCart = new StdClass();
		$AjaxCart->url = shoppurl(false,'cart');
		$AjaxCart->label = __('Edit shopping cart','Shopp');
		$AjaxCart->checkouturl = shoppurl(false,'checkout',ShoppOrder()->security());
		$AjaxCart->checkoutLabel = __('Proceed to Checkout','Shopp');
		$AjaxCart->imguri = '' != get_option('permalink_structure')?trailingslashit(shoppurl('images')):shoppurl().'&siid=';
		$AjaxCart->Totals = clone($this->Totals);
		$AjaxCart->Contents = array();
		foreach( $this as $Item ) {
			$CartItem = clone($Item);
			unset($CartItem->options);
			$AjaxCart->Contents[] = $CartItem;
		}
		if (isset($this->added))
			$AjaxCart->Item = clone($this->added());
		else $AjaxCart->Item = new ShoppCartItem();
		unset($AjaxCart->Item->options);

		echo json_encode($AjaxCart);
		exit();
	}

	/**
	 * Adds a product as an item to the cart
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $quantity The quantity of the item to add to the cart
	 * @param Product $Product Product object to add to the cart
	 * @param Price $Price Price object to add to the cart
	 * @param int $category The id of the category navigated to find the product
	 * @param array $data Any custom item data to carry through
	 * @return boolean
	 **/
	public function additem ($quantity=1,&$Product,&$Price,$category=false,$data=array(),$addons=array()) {
		$NewItem = new ShoppCartItem($Product,$Price,$category,$data,$addons);

		if ( ! $NewItem->valid() || ! $this->addable($NewItem) ) return false;

		$id = $NewItem->fingerprint();

		if ( $this->exists($id) ) {
			$Item = $this->get($id);
			$Item->add($quantity);
			$this->added($id);
		} else {
			$NewItem->quantity($quantity);
			$this->add($id,$NewItem);
		}

		if ( ! $this->xitemstock( $this->added() ) )
			return $this->remove( $this->added() ); // Remove items if no cross-item stock available

		do_action_ref_array('shopp_cart_add_item',array($NewItem));

		return true;
	}

	/**
	 * Removes an item from the cart
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $item Index of the item in the Cart contents
	 * @return boolean
	 **/
	public function rmvitem ( scalar $id ) {
		$Item = $this->get($id);
		do_action_ref_array('shopp_cart_remove_item',array($Item->fingerprint(),$Item));
		$this->remove($id);
	}

	/**
	 * Changes the quantity of an item in the cart
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $item Index of the item in the Cart contents
	 * @param int $quantity New quantity to update the item to
	 * @return boolean
	 **/
	public function setitem ($item,$quantity) {

		if ( 0 == $this->count() ) return false;
		if ( 0 == $quantity ) return $this->remove($item);

		if ( $this->exists($item) ) {

			$Item = $this->get($item);
			$updated = ($quantity != $Item->quantity);
			$Item->quantity($quantity);

			if ( 0 == $Item->quantity() ) $this->remove($item);

			if ( $updated && ! $this->xitemstock($Item) )
				$this->remove($item); // Remove items if no cross-item stock available

		}

		return true;
	}

	/**
	 *
	 * Determine if the combinations of items in the cart is proper.
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 * @param Item $Item the item being added
	 * @return bool true if the item can be added, false if it would be improper.
	 **/
	public function addable ( $Item ) {
		$allowed = true;

		// Subscription products must be alone in the cart
		if ( 'Subscription' == $Item->type && $this->count() > 0 || $this->recurring() ) {
			new ShoppError(__('A subscription must be purchased separately. Complete your current transaction and try again.','Shopp'),'cart_valid_add_failed',SHOPP_ERR);
			return false;
		}

		return true;
	}

	/**
	 * Validates stock levels for cross-item quantities
	 *
	 * This function handles the case where the stock of an product variant is
	 * checked across items where an the variant may exist across several line items
	 * because of either add-ons or custom product inputs. {@see issue #1681}
	 *
	 * @author Jonathan Davis
	 * @since 1.2.2
	 *
	 * @param int|CartItem $item The index of an item in the cart or a cart Item
	 * @return boolean
	 **/
	public function xitemstock ( ShoppCartItem $Item ) {
		if ( ! shopp_setting_enabled('inventory') ) return true;

		// Build a cross-product map of the total quantity of ordered products to known stock levels
		$order = array();
		foreach ($this as $index => $cartitem) {
			if ( ! $cartitem->inventory ) continue;

			if ( isset($order[$cartitem->priceline]) ) $ordered = $order[$cartitem->priceline];
			else {
				$ordered = new StdClass();
				$ordered->stock = $cartitem->option->stock;
				$ordered->quantity = 0;
				$order[$cartitem->priceline] = $ordered;
			}

			$ordered->quantity += $cartitem->quantity;
		}

		// Item doesn't exist in the cart (at all) so automatically validate
		if (!isset($order[ $Item->priceline ])) return true;
		else $ordered = $order[ $Item->priceline ];

		$overage = $ordered->quantity - $ordered->stock;

		if ($overage < 1) return true; // No overage, the item is valid

		// Reduce ordered amount or remove item with error
		if ($overage < $Item->quantity) {
			new ShoppError(__('Not enough of the product is available in stock to fulfill your request.','Shopp'),'item_low_stock');
			$Item->quantity -= $overage;
			$Item->qtydelta -= $overage;
			return true;
		}

		new ShoppError(__('The product could not be added to the cart because it is not in stock.','Shopp'),'cart_item_invalid',SHOPP_ERR);
		return false;

	}

	/**
	 * Changes an item to a different product/price variation
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $item Index of the item to change
	 * @param Product $Product Product object to change to
	 * @param int|array|Price $pricing Price record ID or an array of pricing record IDs or a Price object
	 * @return boolean
	 **/
	public function change ( string $item, integer $product, integer $pricing, array $addons = array() ) {

		// Don't change anything if everything is the same
		if ( ! $this->exists($item) || ($this->get($item)->product == $product && $this->get($item)->price == $pricing) )
			return true;

		// If the updated product and price variation match
		// add the updated quantity of this item to the other item
		// and remove this one

		foreach ( $this as $id => $thisitem ) {
			if ($thisitem->product == $product && $thisitem->price == $pricing) {
				$this->update($id,$thisitem->quantity+$this->get($item)->quantity);
				$this->remove($item);
			}
		}

		// Maintain item state, change variant
		$Item = $this->get($item);
		$qty = $Item->quantity;
		$category = $Item->category;
		$data = $Item->data;
		$addons = array();
		foreach ($Item->addons as $addon)
			$addons[] = $addon->options;

		$UpdatedItem = new ShoppCartItem(new Product($product),$pricing,$category,$data,$addons);
		$UpdatedItem->quantity($qty);

		parent::update($item,$UpdatedItem);

		return true;
	}

	/**
	 * Determines if a specified item is already in this cart
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param Item $NewItem The new Item object to look for
	 * @return boolean|int	Item index if found, false if not found
	 **/
	public function hasitem ( ShoppCartItem $NewItem ) {
		// Find matching item fingerprints
		foreach ( $this as $i => $Item )
			if ($Item->fingerprint() === $NewItem->fingerprint()) return $i;
		return false;
	}

	/**
	 * Determines the order processing timeframes
	 *
	 *
	 **/
	public function processtime ( ShoppCartItem $Item ) {

		if ( isset($Item->processing['min']) )
			$this->processing['min'] = ShippingFramework::daytimes($this->processing['min'],$Item->processing['min']);

		if ( isset($Item->processing['max']) )
			$this->processing['max'] = ShippingFramework::daytimes($this->processing['max'],$Item->processing['max']);
	}

	public function tracking () {

		$Shopp = Shopp::object();
		$Order = ShoppOrder();

		$ShippingAddress = $Order->Shipping;
		$Shiprates = $Order->Shiprates;
		$ShippingModules = $Shopp->Shipping;

		// Tell Shiprates to track changes for this data...
		$Shiprates->track('shipcountry', $ShippingAddress->country);
		$Shiprates->track('shipstate', $ShippingAddress->Shipping->state);
		$Shiprates->track('shippostcode', $ShippingAddress->Shipping->postcode);

		$shipped = $this->shipped();
		$Shiprates->track('items', $this->shipped );

		$Shiprates->track('modules', $ShippingModules->active);
		$Shiprates->track('postcodes', $ShippingModules->postcodes);
		$Shiprates->track('realtime', $ShippingModules->realtime);

		add_action('shopp_cart_item_totals', array($Shiprates, 'init'));

	}

	/**
	 * Calculates the order Totals
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function totals () {

		// Setup totals counter
		if ( false === $this->Totals ) $this->Totals = new OrderTotals();

		$Totals = $this->Totals;

		do_action('shopp_cart_totals_init', $Totals);

		$Shipping = ShoppOrder()->Shiprates;
		$Discounts = ShoppOrder()->Discounts;

		// Identify downloadable products
		$downloads = $this->downloads();
		$shipped = $this->shipped();

		do_action('shopp_cart_item_totals');

		foreach ( $this as $id => $Item ) {

			$Totals->register( new OrderAmountCartItemQuantity($Item) );
			$Totals->register( new OrderAmountCartItem($Item) );

			foreach ( $Item->taxes as $taxid => $Tax )
				$Totals->register( new OrderAmountItemTax( $Tax, $id ) );

			$Shipping->item( new ShoppShippableItem($Item) );

		}

		$Shipping->calculate();

		$Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Shipping->amount() ) ) );

		// Calculate discounts
		$Totals->register( new OrderAmountDiscount( array('id' => 'cart', 'amount' => $Discounts->amount() ) ) );

		do_action_ref_array('shopp_cart_retotal', array(&$Totals) );

		return $Totals;
	}

	/**
	 * Determines if the current order has no cost
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean True if the entire order is free
	 **/
	public function orderisfree() {
		$status = ($this->count() > 0 && $this->Totals->total() == 0);
		return apply_filters('shopp_free_order', $status);
	}

	/**
	 * Finds shipped items in the cart and builds a reference list
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean True if there are shipped items in the cart
	 **/
	public function shipped () {
		return $this->filteritems('shipped');
	}

	/**
	 * Finds downloadable items in the cart and builds a reference list
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean True if there are shipped items in the cart
	 **/
	public function downloads () {
		return $this->filteritems('download');
	}

	/**
	 * Finds recurring payment items in the cart and builds a reference list
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return boolean True if there are recurring payment items in the cart
	 **/
	public function recurring () {
		return $this->filteritems('recurring');
	}

	private function filteritems ($type) {
		$types = array('shipped','downloads','recurring');
		if ( ! in_array($type,$types) ) return false;

		$this->$type = array();
		foreach ($this as $key => $item) {
			if ( ! $item->$type ) continue;
			$this->{$type}[$key] = $item;
		}

		return ! empty($this->$type);
	}

} // END class Cart


/**
 * Provides a data structure template for Cart totals
 *
 * @deprecated Replaced by the OrderTotals system
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage cart
 **/
class CartTotals {

	public $taxrates = array();		// List of tax figures (rates and amounts)
	public $quantity = 0;			// Total quantity of items in the cart
	public $subtotal = 0;			// Subtotal of item totals
	public $discount = 0;			// Subtotal of cart discounts
	public $itemsd = 0;				// Subtotal of cart item discounts
	public $shipping = 0;			// Subtotal of shipping costs for items
	public $taxed = 0;				// Subtotal of taxable item totals
	public $tax = 0;				// Subtotal of item taxes
	public $total = 0;				// Grand total

} // END class CartTotals

/**
 * CartPromotions class
 *
 * Helper class to load session promotions that can apply
 * to the cart
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage cart
 **/
class CartPromotions {

	public $promotions = array();

	/**
	 * OrderPromotions constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		$this->load();
	}

	/**
	 * Loads promotions applicable to this shopping session if needed
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function load () {

		// Already loaded
		if (!empty($this->promotions)) return true;

		$promos = DatabaseObject::tablename(Promotion::$table);
		$datesql = Promotion::activedates();
		$query = "SELECT * FROM $promos WHERE status='enabled' AND $datesql ORDER BY target DESC";

		$loaded = DB::query($query,'array','index','target',true);
		$cartpromos = array('Cart','Cart Item');
		$this->promotions = array();

		foreach ($cartpromos as $type)
			if (isset($loaded[$type]))
				$this->promotions = array_merge($this->promotions,$loaded[$type]);

		if (isset($loaded['Catalog'])) {
			$promos = array();
			foreach ($loaded['Catalog'] as $promo)
				$promos[ sanitize_title_with_dashes($promo->name) ] = array($promo->id,$promo->name);

			shopp_set_setting('active_catalog_promos',$promos);
		}

	}

	/**
	 * Reset and load all the active promotions
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function reload () {
		$this->promotions = array();	// Wipe loaded promotions
		$this->load();					// Re-load active promotions
	}

	/**
	 * Determines if there are promotions available for the order
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function available () {
		return (!empty($this->promotions));
	}

} // END class CartPromotions

/**
 * CartDiscounts class
 *
 * Manages the promotional discounts that apply to the cart
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage cart
 **/
class CartDiscounts {

	// Registries
	public $Cart = false;
	public $promos = array();

	// Settings
	public $limit = 0;

	// Internals
	public $itemprops = array('Any item name','Any item quantity','Any item amount');
	public $cartitemprops = array('Name','Category','Tag name','Variation','Input name','Input value','Quantity','Unit price','Total price','Discount amount');
	public $matched = array();

	/**
	 * Initializes discount calculations
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		$Shopp = Shopp::object();
		$this->limit = shopp_setting('promo_limit');
		$baseop = shopp_setting('base_operations');
		$this->precision = $baseop['currency']['format']['precision'];

		$this->Order = &$Shopp->Order;
		$this->Cart = &$Shopp->Order->Cart;
		$this->promos = &$Shopp->Promotions->promotions;

	}

	/**
	 * Calculates the discounts applied to the order
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return float The total discount amount
	 **/
	public function calculate () {
		$this->applypromos();

		$Cart = ShoppOrder()->Cart;

		$sum = array();
		foreach ($Cart->discounts as $Discount) {
			if (isset($Discount->items) && !empty($Discount->items)) {
				foreach ($Discount->items as $id => $amount) {

					if (isset($Cart->contents[$id])) {
						$Item = $Cart->contents[$id];

						if (shopp_setting_enabled('tax_inclusive') && 'Buy X Get Y Free' == $Discount->type) {
							// Specialized line item for inclusive tax model buy X get Y free discounts [bug #806]
							$Item->retotal();
							$Item->discounts += $amount; // total line item discount
						} else {
							$Item->discount += $amount; // unit discount
							$Item->retotal();
						}

						if ( $Item->discounts ) $Discount->applied += $Item->discounts; // total line item discount
						$sum[$Discount->id.$id] = $Item->discounts;
					}

				}
			} else $sum[$Discount->id] = $Discount->applied;

		}

		$discount = array_sum($sum);

		// Prevent the total of all discounts from being greater than the order subtotal
		if ( $discount > $Cart->Totals->total('order') )
			$discount = $Cart->Totals->total('order');

		return $discount;
	}

	/**
	 * Determines which promotions to apply to the order
	 *
	 * Matches promotion rules to conditions in the cart to determine which
	 * promotions apply.
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function applypromos () {

		usort($this->promos,array(&$this,'_active_discounts'));

		// Iterate over each promo to determine whether it applies
		$discount = 0;
		foreach ($this->promos as &$promo) {
			$applypromo = false;
			if (!is_array($promo->rules))
				$promo->rules = unserialize($promo->rules);

			// If promotion limit has been reached and the promo has
			// not already applied as a cart discount, cancel the loop
			if ($this->limit > 0 && count($this->Cart->discounts)+1 > $this->limit
				&& !isset($this->Cart->discounts[$promo->id])) {
				if (!empty($this->Cart->promocode)) {
					new ShoppError(__("No additional codes can be applied.","Shopp"),'cart_promocode_limit',SHOPP_ALL_ERR);
					$this->Cart->promocode = false;
				}
				break;
			}

			// Match the promo rules against the cart properties
			$matches = 0;
			$total = 0;
			foreach ($promo->rules as $index => $rule) {
				if ($index === "item") continue;
				$match = false;
				$total++;
				extract($rule);
				if ($property == "Promo code") {
					// See if a promo code rule matches
					$match = $this->promocode($rule);
				} elseif (in_array($property,$this->itemprops)) {
					// See if an item rule matches
					foreach ($this->Cart->contents as $id => &$Item)
						if ($match = $Item->match($rule)) break;
				} else {
					// Match cart aggregate property rules
					switch($property) {
						case "Promo use count": $subject = $promo->uses; break;
						case "Total quantity": $subject = $this->Cart->Totals->quantity; break;
						case "Shipping amount": $subject = $this->Cart->Totals->shipping; break;
						case "Subtotal amount": $subject = $this->Cart->Totals->subtotal; break;
						case "Customer type": $subject = $this->Order->Customer->type; break;
						case "Ship-to country": $subject = $this->Order->Shipping->country; break;
					}
					if (Promotion::match_rule($subject,$logic,$value,$property))
						$match = true;
				}

				if ($match && $promo->search == "all") $matches++;
				if ($match && $promo->search == "any") {
					$applypromo = true; break; // Kill the rule loop since the promo applies
				}

			} // End rules loop

			if ($promo->search == "all" && $matches == $total)
				$applypromo = true;

			if (!$applypromo) {
				$promo->applied = 0; 		// Reset promo applied discount
				if (!empty($promo->items))	// Reset any items applied to
					$promo->items = array();

				$this->remove($promo->id);	// Remove it from the discount stack if it is there

				continue; // Try next promotion
			}

			// Apply the promotional discount
			switch ($promo->type) {
				case "Amount Off": $discount = $promo->discount; break;
				case "Percentage Off":
					$discount = ($this->Cart->Totals->subtotal-$this->Cart->Totals->itemsd)
									* ($promo->discount/100);
					break;
				case "Free Shipping":
					if ($promo->target == "Cart") {
						$discount = 0;
						$promo->freeshipping = $this->Cart->Totals->shipping;
						$this->Cart->shipfree = true;
					}
					break;
			}
			$this->discount($promo,$discount);

		} // End promos loop

		// Promocode was/is applied
		if (empty($this->Cart->promocode)) return;
		if (isset($this->Cart->promocodes[strtolower($this->Cart->promocode)])
			&& is_array($this->Cart->promocodes[strtolower($this->Cart->promocode)])) return;

		$codes_applied = array_change_key_case($this->Cart->promocodes);
		if (!array_key_exists(strtolower($this->Cart->promocode),$codes_applied)) {
			new ShoppError(
				sprintf(__("%s is not a valid code.","Shopp"),$this->Cart->promocode),
				'cart_promocode_notfound',SHOPP_ALL_ERR);
			$this->Cart->promocode = false;
		}

	}

	/**
	 * Adds a discount entry for a promotion that applies
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param Object $Promotion The pseudo-Promotion object to apply
	 * @param float $discount The calculated discount amount
	 * @return void
	 **/
	public function discount ($promo,$discount) {

		$promo->applied = 0;		// Track total discount applied by the promo
		$promo->items = array();	// Track the cart items the rule applies to

		// Line item discounts
		if (isset($promo->rules['item'])) {

			// See if an item rule matches
			foreach ($this->Cart->contents as $id => &$Item) {
				if ('Donation' == $Item->type) continue;
				$matches = 0;
				foreach ($promo->rules['item'] as $rule) {
					if (!in_array($rule['property'],$this->cartitemprops)) continue;
					if ($Item->match($rule) && !isset($promo->items[$id])) $matches++;
				} // endforeach $promo->rules['item']

				if ($matches == count($promo->rules['item'])) { // all conditions must match

					// These must result in the discount applied to the *unit price*!
					switch ($promo->type) {
						case "Percentage Off": $discount = $Item->unitprice*($promo->discount/100); break;
						case "Amount Off": $discount = $promo->discount; break;
						case "Free Shipping": $discount = 0; $Item->freeshipping = true; break;
						case "Buy X Get Y Free":
							// With inclusive tax model, the discount must be applied to the line item discounts [bug #806]
							// The exclusive tax model needs a pre-tax unit price discount to avoid tax on the free item(s)
							if (shopp_setting_enabled('tax_inclusive'))
								$discount = $promo->getqty * ($Item->unitprice + $Item->unittax);
							else $discount = $Item->unitprice*( $promo->getqty / ($promo->buyqty + $promo->getqty ));
							break;
					}
					$promo->items[$id] = $discount;
				}
			}

			if ($promo->applied == 0 && empty($promo->items)) {
				if (isset($this->Cart->discounts[$promo->id]))
					unset($this->Cart->discounts[$promo->id]);
				return;
			}

			$this->Cart->Totals->itemsd += $promo->applied;
		} else {
			$promo->applied = $discount;
		}

		// Determine which promocode matched
		$promocode_rules = array_filter($promo->rules,array(&$this,'_filter_promocode_rule'));
		foreach ($promocode_rules as $rule) {
			extract($rule);

			$subject = strtolower($this->Cart->promocode);
			$promocode = strtolower($value);

			if (Promotion::match_rule($subject,$logic,$promocode,$property)) {
				// Prevent customers from reapplying codes
				if (isset($this->Cart->promocodes[$promocode])
						&& is_array($this->Cart->promocodes[$promocode])
						&& in_array($promo->id,$this->Cart->promocodes[$promocode])) {
					new ShoppError(sprintf(__("%s has already been applied.","Shopp"),$value),'cart_promocode_used',SHOPP_ALL_ERR);
					$this->Cart->promocode = false;
					return false;
				}
				// Add the code to the registry
				if (!isset($this->Cart->promocodes[$promocode])
					|| !is_array($this->Cart->promocodes[$promocode]))
					$this->Cart->promocodes[$promocode] = array();
				else $this->Cart->promocodes[$promocode][] = $promo->id;
				$this->Cart->promocode = false;
			}
		}

		$this->Cart->discounts[$promo->id] = $promo;
	}

	/**
	 * Removes an applied discount
	 *
	 * @author Jonathan Davis
	 * @since 1.1.5
	 *
	 * @param int $id The promo id to remove
	 * @return boolean True if successfully removed
	 **/
	public function remove ($id) {
		if (!isset($this->Cart->discounts[$id])) return false;

		unset($this->Cart->discounts[$id]);
		return true;
	}

	/**
	 * Matches a Promo Code rule to a code submitted from the shopping cart
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $rule The promo code rule
	 * @return boolean
	 **/
	public function promocode ($rule) {
		extract($rule);
		$promocode = strtolower($value);

		// Match previously applied codes
		if (isset($this->Cart->promocodes[$promocode])
			&& is_array($this->Cart->promocodes[$promocode])) return true;

		// Match new codes

		// No code provided, nothing will match
		if (empty($this->Cart->promocode)) return false;

		$subject = strtolower($this->Cart->promocode);
		return Promotion::match_rule($subject,$logic,$promocode,$property);
	}

	/**
	 * Helper method to sort active discounts before other promos
	 *
	 * Sorts active discounts to the top of the available promo list
	 * to enable efficient promo limit enforcement
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function _active_discounts ($a,$b) {
		$_ =& $this->Cart->discounts;
		return (isset($_[$a->id]) && !isset($_[$b->id]))?-1:1;
	}

	/**
	 * Helper method to identify a rule as a promo code rule
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $rule The rule to test
	 * @return boolean
	 **/
	public function _filter_promocode_rule ($rule) {
		return (isset($rule['property']) && $rule['property'] == "Promo code");
	}

} // END class CartDiscounts

/**
 * CartShipping class
 *
 * Mediator object for triggering ShippingModule calculations that are
 * then used for a lowest-cost shipping estimate to show in the cart.
 *
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage cart
 **/
class CartShipping {

	public $options = array();
	public $modules = false;
	public $disabled = false;
	public $fees = 0;
	public $handling = 0;

	/**
	 * CartShipping constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		$Shopp = Shopp::object();

		$this->Cart = &$Shopp->Order->Cart;
		$this->modules = &$Shopp->Shipping->active;
		$this->Shipping = &$Shopp->Order->Shipping;
		$this->Shipping->locate();

		$this->showpostcode = $Shopp->Shipping->postcodes;

		$this->handling = shopp_setting('order_shipfee');
		$this->realtime = $Shopp->Shipping->realtime;

	}

	public function status () {
		// If shipping is disabled, bail
		if (!shopp_setting_enabled('shipping')) return false;
		// If no shipped items, bail
		if (!$this->Cart->shipped()) return false;
		// If the cart is flagged for free shipping bail
		if ($this->Cart->freeshipping) return 0;
		return true;
	}

	/**
	 * Runs the shipping calculation modules
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function calculate () {

		$status = $this->status();
		if ($status !== true) return $status;

		// Initialize shipping modules
		do_action('shopp_calculate_shipping_init');

		$this->Cart->processing = array( 'min' => 0, 'max' => 0 );
		foreach ($this->Cart->shipped as $id => &$Item) {
			if ($Item->freeshipping) continue;
			// Calculate any product-specific shipping fee markups
			if ($Item->shipfee > 0) $this->fees += ($Item->quantity * $Item->shipfee);
			$this->Cart->processing['min'] = ShippingFramework::daytimes($this->Cart->processing['min'],$Item->processing['min']);
			$this->Cart->processing['max'] = ShippingFramework::daytimes($this->Cart->processing['max'],$Item->processing['max']);

			// Run shipping module item calculations
			do_action_ref_array('shopp_calculate_item_shipping',array($id,&$Item));
		}

		// Add order handling fee
		if ($this->handling > 0) $this->fees += $this->handling;

		// Run shipping module aggregate shipping calculations
		do_action_ref_array('shopp_calculate_shipping',array(&$this->options,$Shopp->Order));

		// No shipping options were generated, try fallback calculators for realtime rate failures
		if (empty($this->options)) {
			if ($this->realtime) {
				do_action('shopp_calculate_fallback_shipping_init');
				do_action_ref_array('shopp_calculate_fallback_shipping',array(&$this->options,$Shopp->Order));
			}
			if (empty($this->options)) return false; // Still no rates, bail
		}

		uksort($this->options,array('self','sort'));

		// Determine the lowest cost estimate
		$estimate = false;
		foreach ($this->options as $name => $option) {
			// Add in the fees
			$option->amount += apply_filters('shopp_cart_fees',$this->fees);

			// Skip if not to be included
			if (!$option->estimate) continue;

			// If the option amount is less than current estimate
			// Update the estimate to use this option instead
			if (!$estimate || $option->amount < $estimate->amount)
				$estimate = $option;
		}


		// Always return the selected shipping option if a valid/available method has been set
		if (empty($this->Shipping->method) || !isset($this->options[$this->Shipping->method])) {
				$this->Shipping->method = $estimate->slug;
				$this->Shipping->option = $estimate->name;
		}

		$amount = $this->options[$this->Shipping->method]->amount;
		$this->Cart->freeshipping = ($amount == 0);

		// Return the estimated amount
		return $amount;
	}

	/**
	 * Returns the currently calculated shipping options
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return array List of ShippingOption objects
	 **/
	public function options () {
		return $this->options;
	}

	/**
	 * Return the currently selected shipping method
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return float The shipping amount
	 **/
	public function selected () {

		$status = $this->status();
		if ($status !== true) return $status;

		if (!empty($this->Shipping->method) && isset($this->Cart->shipping[$this->Shipping->method]))
			return $this->Cart->shipping[$this->Shipping->method]->amount;
		$method = current($this->Cart->shipping);
		return $method->amount;
	}

	static function sort ($a,$b) {
		if ($a->amount == $b->amount) return 0;
		return ($a->amount < $b->amount) ? -1 : 1;
	}

} // END class CartShipping

/**
 * CartTax class
 *
 * Handles tax calculations
 *
 * @deprecated No longer used. Replaced by OrderTotals and ShoppTax
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage cart
 **/
class CartTax {

	public $Order = false;
	public $enabled = false;
	public $shipping = false;
	public $rates = array();

	/**
	 * CartTax constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return void
	 **/
	public function __construct () {
		$Shopp = Shopp::object();
		$this->Order = ShoppOrder();
		$base = shopp_setting('base_operations');
		$this->format = $base['currency']['format'];
		$this->inclusive = shopp_setting_enabled('tax_inclusive');
		$this->enabled = shopp_setting_enabled('taxes');
		$this->rates = shopp_setting('taxrates');
		$this->shipping = shopp_setting_enabled('tax_shipping');
	}

	/**
	 * Determine the applicable tax rate
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return float The tax rate (or false if no rate applies)
	 **/
	public function rate ($Item=false,$settings=false) {
		if (!$this->enabled) return false;
		if (!is_array($this->rates)) return false;

		$Customer = $this->Order->Customer;
		$Billing = $this->Order->Billing;
		$Shipping = $this->Order->Shipping;
		$country = $zone = $locale = $global = false;
		if ( is_admin() && !defined('DOING_AJAX') ) { // Always use the base of operations in the admin
			$base = shopp_setting('base_operations');
			$country = apply_filters('shopp_admin_tax_country',$base['country']);
			$zone = apply_filters('shopp_admin_tax_zone', (isset($base['zone'])?$base['zone']:false) );
		} elseif ( $this->Order->Cart->shipped() ) { // Use shipping address for shipped orders
			$country = $Shipping->country;
			$zone = $Shipping->state;
			if ( isset($Billing->locale) ) $locale = $Billing->locale; // exception for locale
		} else {
			$country = $Billing->country;
			$zone = $Billing->state;
			if ( isset($Billing->locale) ) $locale = $Billing->locale;
		}

		foreach ($this->rates as $setting) {
			$rate = false;
			if (isset($setting['locals']) && is_array($setting['locals'])) {
				$localmatch = true;
				if ( $country != $setting['country'] ) $localmatch = false;
				if ( isset($setting['zone']) && !empty($setting['zone']) && $zone != $setting['zone'] ) $localmatch = false;
				if ( $localmatch ) {
					$localrate = isset($setting['locals'][$locale])?$setting['locals'][$locale]:0;
					$rate = ($this->float($setting['rate'])+$this->float($localrate));
				}
			} elseif (isset($setting['zone']) && !empty($setting['zone'])) {
				if ($country == $setting['country'] && $zone == $setting['zone'])
					$rate = $this->float($setting['rate']);
			} elseif ($country == $setting['country'] || '*' == $setting['country']) {
				$rate = $this->float($setting['rate']);
			}

			// Match tax rules
			if (isset($setting['rules']) && is_array($setting['rules'])) {
				$applies = false;
				$matches = 0;

				foreach ($setting['rules'] as $rule) {
					$match = false;
					if ($Item !== false && strpos($rule['p'],'product') !== false) {
						$match = $Item->taxrule($rule);
					} elseif (strpos($rule['p'],'customer') !== false) {
						$match = $Customer->taxrule($rule);
					}

					$match = apply_filters('shopp_customer_taxrule_match',$match,$rule,$this);
					if ($match) $matches++;
				}
				if ($setting['logic'] == "all" && $matches == count($setting['rules'])) $applies = true;
				if ($setting['logic'] == "any" && $matches > 0) $applies = true;
				if (!$applies) continue;
			}
			// Grab the global setting if found
			if ($setting['country'] == "*") $global = $setting;

			if ($rate !== false) { // The first rate to fully apply wins
				if ($settings) return apply_filters('shopp_cart_taxrate_settings',$setting);
				return apply_filters('shopp_cart_taxrate',$rate/100);
			}

		}

		if ($global) {
			if ($settings) return apply_filters('shopp_cart_taxrate_settings',$global);
			return apply_filters('shopp_cart_taxrate',$this->float($global['rate'])/100);
		}
		return false;
	}

	public function float ($rate) {
		$format = $this->format;
		$format['precision'] = 3;
		return floatvalue($rate,true,$format);
	}

	/**
	 * Calculates total taxes
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return float Total tax amount
	 **/
	public function calculate () {
		$Totals =& $this->Order->Cart->Totals;

		$tiers = array();
		$taxes = 0;
		foreach ($this->Order->Cart->contents as $id => &$Item) {
			if (!$Item->istaxed) continue;
			$Item->taxrate = $this->rate($Item);

			if (!isset($tiers[$Item->taxrate])) $tiers[$Item->taxrate] = $Item->total;
			else $tiers[$Item->taxrate] += $Item->total;

			$taxes += $Item->tax;
		}

		if ($this->shipping) {
			if ($this->inclusive) // Remove the taxes from the shipping amount for inclusive-tax calculations
				$Totals->shipping = (floatvalue($Totals->shipping)/(1+$Totals->taxrate));
			$taxes += roundprice($Totals->shipping*$Totals->taxrate);
		}

		return $taxes;
	}

} // END class CartTax


if ( ! class_exists('Cart',false) ) {
	class Cart extends ShoppCart {

		/**
		 * @deprecated Stubbed for backwards-compatibility
		 **/
		public function changed ( $changed = false ) {
		}

		/**
		 * @deprecated Stubbed for backwards-compatibility
		 **/
		public function retotal () {
		}

	}
}
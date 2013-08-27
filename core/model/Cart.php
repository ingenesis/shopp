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
		add_action('shopp_login', array($this, 'totals'));
		add_action('shopp_logged_out', array($this, 'totals'));

		// Setup totals counter
		if ( false === $this->Totals ) $this->Totals = new OrderTotals();

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
						$this->updates($id, $item);
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

		$Product = new ShoppProduct( (int) $product );
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
		$AjaxCart->url = Shopp::url(false,'cart');
		$AjaxCart->label = __('Edit shopping cart','Shopp');
		$AjaxCart->checkouturl = Shopp::url(false,'checkout',ShoppOrder()->security());
		$AjaxCart->checkoutLabel = __('Proceed to Checkout','Shopp');
		$AjaxCart->imguri = '' != get_option('permalink_structure')?trailingslashit(Shopp::url('images')):Shopp::url().'&siid=';
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
	 * @param ShoppProduct $Product Product object to add to the cart
	 * @param ShoppPrice $Price Price object to add to the cart
	 * @param int $category The id of the category navigated to find the product
	 * @param array $data Any custom item data to carry through
	 * @return boolean
	 **/
	public function additem ( $quantity = 1, &$Product, &$Price, $category=false, $data=array(), $addons=array() ) {
		$NewItem = new ShoppCartItem($Product,$Price,$category,$data,$addons);

		if ( ! $NewItem->valid() || ! $this->addable($NewItem) ) return false;

		$id = $NewItem->fingerprint();

		if ( $this->exists($id) ) {
			$Item = $this->get($id);
			$Item->add($quantity);
			$this->added($id);
		} else {
			$NewItem->quantity($quantity);
			$this->add($id, $NewItem);
			$Item = $NewItem;
		}

		$Totals = ( false === $this->Totals ) ? new OrderTotals() : $this->Totals;
		$Shipping = ShoppOrder()->Shiprates;

		$Totals->register( new OrderAmountCartItemQuantity($Item) );
		$Totals->register( new OrderAmountCartItem($Item) );

		foreach ( $Item->taxes as $taxid => &$Tax )
			$Totals->register( new OrderAmountItemTax( $Tax, $id ) );

		$Shipping->item( new ShoppShippableItem($Item) );

		if ( ! $this->xitemstock( $this->added() ) )
			return $this->remove( $this->added() ); // Remove items if no cross-item stock available

		do_action_ref_array('shopp_cart_add_item', array($Item));

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
	public function rmvitem ( string $id ) {
		$Item = $this->get($id);

		$Totals = $this->Totals;
		$Shipping = ShoppOrder()->Shiprates;

		$Totals->takeoff(OrderAmountCartItemQuantity::$register, $id);
		$Totals->takeoff(OrderAmountCartItem::$register, $id);

		foreach ( $Item->taxes as $taxid => &$Tax ) {
			$TaxTotal = $Totals->entry( OrderAmountItemTax::$register, $Tax->label );
			if ( false !== $TaxTotal )
				$TaxTotal->unlink($id);
		}

		$Shipping->takeoff( $id );

		do_action_ref_array('shopp_cart_remove_item', array($Item->fingerprint(), $Item));
		return $this->remove($id);
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
	public function setitem ( $item, $quantity) {

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
	 * @fixme foreach over iterable items prevents addons from being added via cart API
	 * @param int $item Index of the item to change
	 * @param ShoppProduct $Product Product object to change to
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

		/*foreach ( $this as $id => $thisitem ) {
			if ($thisitem->product == $product && $thisitem->priceline == $pricing) {
				$this->update($id,$thisitem->quantity+$this->get($item)->quantity);
				$this->remove($item);
			}
		}*/

		// Maintain item state, change variant
		$Item = $this->get($item);
		$qty = $Item->quantity;
		$category = $Item->category;
		$data = $Item->data;

		foreach ($Item->addons as $addon)
			$addons[] = $addon->options;

		$UpdatedItem = new ShoppCartItem(new ShoppProduct($product), $pricing, $category, $data, $addons);
		$UpdatedItem->quantity($qty);

		parent::update($item, $UpdatedItem);

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
		$fingerprint = $NewItem->fingerprint();
		if ( $this->exists($fingerprint) )
			return $fingerprint;
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
		$ShippingModules = $Shopp->Shipping;

		$Order = ShoppOrder();
		$ShippingAddress = $Order->Shipping;
		$Shiprates = $Order->Shiprates;

		// Tell Shiprates to track changes for this data...
		$Shiprates->track('shipcountry', $ShippingAddress->country);
		$Shiprates->track('shipstate', $ShippingAddress->state);
		$Shiprates->track('shippostcode', $ShippingAddress->postcode);

		$shipped = $this->shipped();
		$Shiprates->track('items', $shipped);

		$Shiprates->track('modules', $ShippingModules->active);
		$Shiprates->track('postcodes', $ShippingModules->postcodes);
		$Shiprates->track('realtime', $ShippingModules->realtime);

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

		do_action('shopp_cart_item_totals'); // Update cart item totals

		$Shipping->calculate();
		$Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Shipping->amount() ) ) );

		if ( apply_filters( 'shopp_tax_shipping', shopp_setting_enabled('tax_shipping') ) ) {
			$Totals->register( new OrderAmountShippingTax( $Totals->total('shipping') ) );
		}

		// Calculate discounts
		$Totals->register( new OrderAmountDiscount( array('id' => 'cart', 'amount' => $Discounts->amount() ) ) );

		if ( $Shipping->free() && $Totals->total('shipping') > 0 ) {
			$Shipping->calculate();
			$Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Shipping->amount() ) ) );
		}

		do_action_ref_array('shopp_cart_retotal', array(&$Totals) );

		return $Totals;
	}

	/**
	 * Get a Cart register total amount
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $register The name of the register to get an amount for
	 * @return float The total amount for the register
	 **/
	public function total ( string $register ) {

		// Setup totals counter
		if ( false === $this->Totals ) $this->Totals = new OrderTotals();
		$Totals = $this->Totals;

		return $Totals->total($register);
	}

	/**
	 * Empties the cart
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function clear () {
		parent::clear();

		// Clear the item registers
		$this->Totals = new OrderTotals();

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
 * Helper class to load session promotions that can apply
 * to the cart
 *
 * @deprecated Do not use. Replaced by ShoppPromotions
 * @author Jonathan Davis
 * @since 1.1
 * @package shopp
 * @subpackage cart
 **/
class CartPromotions {

	public $promotions = array();

	/**
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function load () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function reload () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;

	}

	/**
	 * @deprecated Do not use
	 **/
	public function available () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartPromotions

/**
 * Manages the promotional discounts that apply to the cart
 *
 * @deprecated Do not use. Replaced with ShoppDiscounts
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
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
	}

	/**
	 * @deprecated Do not use
	 **/
	public function calculate () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function applypromos () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;	}

		/**
		 * @deprecated Do not use
		 **/
	public function discount ($promo,$discount) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function remove ($id) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function promocode ($rule) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function _active_discounts ($a,$b) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function _filter_promocode_rule ($rule) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartDiscounts

/**
 * Mediator object for triggering ShippingModule calculations that are
 * then used for a lowest-cost shipping estimate to show in the cart.
 *
 * @deprecated Do not use. Replaced by ShoppShiprates
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
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
	}

	/**
	 * @deprecated Do not use
	 **/
	public function status () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function calculate () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function options () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function selected () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	static function sort ($a,$b) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartShipping

/**
 * Handled tax calculations
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
	 * @deprecated Do not use
	 **/
	public function __construct () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function rate ($Item=false,$settings=false) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function float ($rate) {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

	/**
	 * @deprecated Do not use
	 **/
	public function calculate () {
		shopp_debug(__CLASS__ . ' is a deprecated class. Use the Theme API instead.');
		return false;
	}

} // END class CartTax
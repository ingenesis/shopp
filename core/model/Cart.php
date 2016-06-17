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
		add_action('shopp_cart_item_taxes', array($this, 'itemtaxes') );

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

		if ( isset($_REQUEST['empty']) )
			$_REQUEST['cart'] = 'empty';

		$request = isset($_REQUEST['cart']) ? strtolower($_REQUEST['cart']) : false;

		if ( in_array( $request, $commands) )
			$command = $request;

		$allowed = array(
			'quantity' => 1,
			'product' => false,
			'products' => array(),
			'item' => false,
			'items' => array(),
			'remove' => array(),
		);
		$request = array_intersect_key($_REQUEST,$allowed); // Filter for allowed arguments
		$request = array_merge($allowed, $request);			// Merge to defaults

		extract($request, EXTR_SKIP);

		switch( $command ) {
			case 'empty': $this->clear(); break;
			case 'remove': $this->rmvitem( key($remove) ); break;
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

		do_action('shopp_cart_updated', $this, $this->changed());

	}

	/**
	 * Handle add requests for new cart items
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $request The request to process
	 * @return void
	 **/
	private function addrequest ( array $request ) {

		$defaults = array(
			'quantity' => 1,
			'product' => false,
			'price' => false,
			'prices' => array(),
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
			else {
				if ( false !== $price )
					$prices[] = $price;

				if ( empty($prices) ) $prices[] = false; // Use default price for product if none provided
				foreach($prices as $price)
					$result = $this->additem($quantity, $Product, $price, $category, apply_filters('shopp_cartitem_data', $data), $addons);

			}
		}

	}


	/**
	 * Handle item update requests (quantity, or variant changes for example)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $item The item id (fingerprint) to change
	 * @param array $request The update request
	 * @return void
	 **/
	private function updates ( $item, array $request ) {
		$CartItem = $this->get($item);
		if ( ! $CartItem ) return;
		$defaults = array(
			'quantity' => 1,
			'product' => false,
			'price' => false
		);
		$request = array_merge($defaults, $request);
		extract($request, EXTR_SKIP);

		if ( $product == $CartItem->product && false !== $price && $price != $CartItem->priceline )
			$this->change($item,$product,$price);
		else $this->setitem($item,$quantity);

	}

	/**
	 * Responds to AJAX-based cart requests
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return void
	 **/
	public function ajax () {

		if ( 'html' == strtolower($_REQUEST['response']) ) {
			shopp('cart.sidecart');
			exit;
		}

		$AjaxCart = new StdClass();
		$AjaxCart->url = Shopp::url(false, 'cart');
		$AjaxCart->label = __('Edit shopping cart', 'Shopp');
		$AjaxCart->checkouturl = Shopp::url(false, 'checkout', ShoppOrder()->security());
		$AjaxCart->checkoutLabel = __('Proceed to Checkout', 'Shopp');
		$AjaxCart->imguri = '' != get_option('permalink_structure') ? trailingslashit(Shopp::url('images')) : Shopp::url() . '&siid=';
		$AjaxCart->Totals = json_decode( (string)$this->Totals );
		$AjaxCart->Contents = array();

		foreach ( $this as $Item ) {
			$CartItem = clone($Item);
			unset($CartItem->options);
			$AjaxCart->Contents[] = $CartItem;
		}

		if ( isset($this->added) )
			$AjaxCart->Item = clone($this->added());
		else $AjaxCart->Item = new ShoppCartItem();
		unset($AjaxCart->Item->options);

		echo json_encode($AjaxCart);
		exit;

	}

	/**
	 * Adds a product as an item to the cart
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param int $quantity The quantity of the item to add to the cart
	 * @param ShoppProduct|ShoppCartItem $Product Product object or cart item object to add to the cart
	 * @param mixed $Price Price object to add to the cart
	 * @param int $category The id of the category navigated to find the product
	 * @param array $data Any custom item data to carry through
	 * @return boolean
	 **/
	public function additem ( $quantity = 1, &$Product, &$Price, $category = false, $data = array(), $addons = array() ) {

		if ( 'ShoppCartItem' == get_class($Product) ) $NewItem = $Product;
		else {
			$NewItem = new ShoppCartItem($Product, $Price, $category, $data, $addons);
			if ( ! $NewItem->valid() || ! $this->addable($NewItem) ) return false;
		}

		do_action('shopp_cart_before_add_item', array($NewItem) );

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
	public function rmvitem ( $id ) {
		$Item = $this->get($id);

		$Totals = $this->Totals;
		$Shipping = ShoppOrder()->Shiprates;

		$Totals->takeoff(OrderAmountCartItemQuantity::$register, $id);
		$Totals->takeoff(OrderAmountCartItem::$register, $id);

		foreach ( $Item->taxes as $taxid => &$Tax ) {
			$TaxTotal = $Totals->entry( OrderAmountItemTax::$register, $Tax->label );
			if ( false !== $TaxTotal )
				$TaxTotal->unapply($id);
		}

		$Shipping->takeoff( $id );

		do_action('shopp_cart_remove_item', $id, $Item);

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
	public function setitem ( $item, $quantity ) {

		if ( 0 == $this->count() ) return false;
		if ( 0 == $quantity ) return $this->rmvitem($item);

		if ( $this->exists($item) ) {

			$Item = $this->get($item);
			$updated = ($quantity != $Item->quantity);
			$Item->quantity($quantity);

			ShoppOrder()->Shiprates->item( new ShoppShippableItem($Item) );

			if ( 0 == $Item->quantity() ) $this->rmvitem($item);

			if ( $updated && ! $this->xitemstock($Item) )
				$this->rmvitem($item); // Remove items if no cross-item stock available

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
		if ( ! shopp_setting_enabled('inventory') || shopp_setting_enabled('backorders') ) return true;

		// Build a cross-product map of the total quantity of ordered products to known stock levels
		$order = array();
		foreach ($this as $index => $cartitem) {
			if ( ! $cartitem->inventory ) continue;

			if ( isset($order[ $cartitem->priceline ]) ) $ordered = $order[ $cartitem->priceline ];
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
	public function change ( $item, $product, $pricing, array $addons = array() ) {

		// Don't change anything if everything is the same
		if ( ! $this->exists($item) || ($this->get($item)->product == $product && $this->get($item)->price == $pricing) )
			return true;

		// Maintain item state, change variant
		$Item = $this->get($item);
		$category = $Item->category;
		$data = $Item->data;

		$Item->load(new ShoppProduct($product), $pricing, $category, $data, $addons);
		ShoppOrder()->Shiprates->item( new ShoppShippableItem($Item) );

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
	 * Recalculates order processing time minimum and maximums across all items
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function processtime ( ShoppCartItem $Item ) {

		if ( isset($Item->processing['min']) )
			$this->processing['min'] = ShippingFramework::daytimes($this->processing['min'], $Item->processing['min']);

		if ( isset($Item->processing['max']) )
			$this->processing['max'] = ShippingFramework::daytimes($this->processing['max'], $Item->processing['max']);

	}

	/**
	 * Add new item taxes to the tax register
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param ShoppCartItem $Item The cart item from shopp_cart_item_retotal
	 * @return void
	 **/
	public function itemtaxes ( ShoppCartItem $Item ) {

		$itemid = $Item->fingerprint();
		if ( ! $this->exists($itemid) ) return;

		foreach ( $Item->taxes as $id => &$ItemTax )
			$this->Totals->register( new OrderAmountItemTax( $ItemTax, $itemid ) );

	}

	/**
	 * Adds change tracking to the shipping rates system for automagic recalculations
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return void
	 **/
	public function tracking () {

		$Shopp = Shopp::object();
		$ShippingModules = $Shopp->Shipping;

		$Order = ShoppOrder();
		$ShippingAddress = $Order->Shipping;
		$Shiprates = $Order->Shiprates;

		if ( empty($Shiprates) ) return;

		// Tell Shiprates to track changes for this data...
		$Shiprates->track('shipcountry', $ShippingAddress->country);
		$Shiprates->track('shipstate', $ShippingAddress->state);
		$Shiprates->track('shippostcode', $ShippingAddress->postcode);

		// Hash items for lower memory tracking
		$this->shipped();
		$Shiprates->track('items', $this->shipped);

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

		do_action('shopp_cart_item_totals', $Totals); // Update cart item totals

		$items = $this->keys(); // Use local array for iterating
		foreach ( $items as $itemid ) { // Allow other code to iterate the cart in this loop
			$Item = $this->get($itemid);
			$Item->totals();
		}

		$Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Shipping->calculate() ) ) );

		if ( apply_filters( 'shopp_tax_shipping', shopp_setting_enabled('tax_shipping') ) )
			$Totals->register( new OrderAmountShippingTax( $Totals->total('shipping') ) );

		// Calculate discounts
		$Totals->register( new OrderAmountDiscount( array('id' => 'cart', 'amount' => $Discounts->amount() ) ) );

		// Apply credits to discount the order
		$Discounts->credits();


		if ( $Discounts->shipping() ) // If shipping discounts changed, recalculate shipping amount
			$Totals->register( new OrderAmountShipping( array('id' => 'cart', 'amount' => $Shipping->calculate() ) ) );

		// Ensure taxes are recalculated
		$Totals->total('tax');

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
	public function total ( $register = null, $entry = null ) {

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

		$this->shipped = array();
		$this->downloads = array();
		$this->recurring = array();

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
		return $this->filteritems('downloads');
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

	/**
	 * Helper to filter a list of products by type
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $type The type of item to find (shipped, downloads or recurring)
	 * @return boolean True if the item is of the specified type, false otherwise
	 **/
	private function filteritems ( $type ) {
		$types = array('shipped', 'downloads', 'recurring');
		if ( ! in_array($type, $types) ) return false;

		$this->$type = array();
		foreach ($this as $key => $item) {
			$prop = rtrim($type, 's'); // No plural properties
			if ( ! $item->$prop ) continue;
			$this->{$type}[ $key ] = $item;
		}

		return ! empty($this->$type);
	}

}

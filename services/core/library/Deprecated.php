<?php
/**
 * Deprecated.php
 *
 * Deprecated class definitions.
 *
 * @author Barry Hughes
 * @copyright Ingenesis Limited, 27 August 2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @version 1.3
 * @since 1.3
 **/

// Prevent direct access
defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit;

// Allow devs to stop these definitions from being loaded
if ( defined('SHOPP_DEPRECATED_CLASSES') && false === SHOPP_DEPRECATED_CLASSES ) return;

// Straightforward aliases for deprecated classes
if ( ! class_exists('Address', false) ) { class Address extends ShoppAddress {} }
if ( ! class_exists('AdminController', false) ) { class AdminController extends ShoppAdminController {} }
if ( ! class_exists('Customer', false) ) { class Customer extends ShoppCustomer {} }
if ( ! class_exists('FlowController', false) ) { class FlowController extends ShoppFlowController {} }
if ( ! class_exists('MetaObject', false) ) { class MetaObject extends ShoppMetaObject {} }
if ( ! class_exists('Price', false) ) {	class Price extends ShoppPrice {} }
if ( ! class_exists('Product', false) ) { class Product extends ShoppProduct {} }
if ( ! class_exists('Promotion', false) ) { class Promotion extends ShoppPromo {} }
if ( ! class_exists('Purchase', false) ) { class Purchase extends ShoppPurchase {} }
if ( ! class_exists('Purchased', false) ) { class Purchased extends ShoppPurchased {} }
if ( ! class_exists('Storefront', false) ) { class Storefront extends ShoppStorefront {} }
if ( ! class_exists('DatabaseObject', false) ) { class DatabaseObject extends ShoppDatabaseObject {} }
if ( ! class_exists('Item', false) ) { class Item extends ShoppCartItem {} }

// The Cart class additionally needs stub methods for backwards compatibility
if ( ! class_exists('Cart', false) ) {
	class Cart extends ShoppCart {
		/**
		 * @deprecated Stubbed for backwards-compatibility
		 **/
		public function changed ( $changed = false ) {}

		/**
		 * @deprecated Stubbed for backwards-compatibility
		 **/
		public function retotal () {}
	}
}


/**
 * @deprecated Replaced by the OrderTotals system
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
 * @deprecated Do not use. Replaced by ShoppPromotions
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
 * @deprecated Do not use. Replaced with ShoppDiscounts
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
 * @deprecated Do not use. Replaced by ShoppShiprates
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
 * @deprecated No longer used. Replaced by OrderTotals and ShoppTax
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

/**
 * SplDoublyLinkedList, SplQueue and SplStack are compatibility shivs
 * for PHP 5.2 used by the Markdown library.
 *
 * @todo Remove SplDoublyLinkedList, SplQueue and SplStack classes with PHP 5.3 minimum requirements
 */
if ( ! class_exists('SplDoublyLinkedList', false) ) {

	/**
	 * A reimplementation of PHP 5.3 SplDoublyLinkedList datastructure.
	 * The only intended difference is the lacking implementation of
	 * the ArrayAccess interface in order to improve reliability.
	 *
	 * @author Tomasz Jędrzejewski
	 * @copyright Invenzzia Group <http://www.invenzzia.org/> and contributors.
	 * @license http://www.invenzzia.org/license/new-bsd New BSD License
	 */
	class SplDoublyLinkedList implements Countable, Iterator {

		const IT_MODE_LIFO = 2;
		const IT_MODE_FIFO = 0;
		const IT_MODE_DELETE = 1;
		const IT_MODE_KEEP = 0;

		/**
		 * The datastructure iteration mode.
		 * @var integer
		 */
		protected $_iteratorMode = 0;

		/**
		 * The data kept in this datastructure.
		 * @var array
		 */
		protected $_data = array();

		/**
		 * The number of elements in the datastructure.
		 * @var int
		 */
		protected $_count = 0;

		/**
		 * Iteration count.
		 * @var int
		 */
		protected $_itElement = 0;

		/**
		 * The iterator.
		 * @var int
		 */
		protected $_itKey = 0;

		/**
		 * The internal pointer.
		 * @var int
		 */
		protected $_i = 0;

		/**
		 * The internal pointer end
		 * @var int
		 */
		protected $_finish = 0;

		/**
		 * Returns true, if the specified datastructure is empty.
		 *
		 * @return boolean
		 */
		public function isEmpty () {
			return ($this->_count == 0);
		} // end isEmpty();

		/**
		 * Pushes the data into the datastructure.
		 *
		 * @param mixed $value The data to push
		 */
		public function push ( $value ) {
			array_push($this->_data, $value);
			$this->_count++;
		} // end push();

		/**
		 * Pops the data off the datastructure.
		 *
		 * @throws RuntimeException
		 * @return mixed
		 */
		public function pop () {
			if ( $this->_count > 0 ) {
				$this->_count--;
				return array_pop($this->_data);
			}
			throw new RuntimeException('Can\'t pop from an empty datastructure');
		} // end pop();

		/**
		 * Returns the top element of the datastructure without removing
		 * it.
		 *
		 * @throws RuntimeException
		 * @return mixed
		 */
		public function top () {
			if ( $this->_count == 0 ) {
				throw new RuntimeException('Can\'t peek at an empty datastructure');
			}
			return end($this->_data);
		} // end top();

		/**
		 * Returns the bottom element of the datastructure without removing
		 * it.
		 *
		 * @throws RuntimeException
		 * @return mixed
		 */
		public function bottom () {
			if($this->_count == 0) {
				throw new RuntimeException('Can\'t peek at an empty datastructure');
			}
			reset($this->_data);
			return current($this->_data);
		} // end bottom();

		/**
		 * Prepends the data to the beginning of the datastructure.
		 *
		 * @param mixed $data The data to prepend.
		 */
		public function unshift ( $data ) {
			$this->_count++;
			array_unshift($this->_data, $data);
		} // end enqueue();

		/**
		 * Shifts the data off the beginning of the datastructure.
		 *
		 * @return mixed
		 */
		public function shift () {
			if ( $this->_count > 0 ) {
				$this->_count--;
				return array_shift($this->_data);
			}
			throw new RuntimeException('Can\'t shift from an empty datastructure');
		} // end shift();

		/**
		 * Sets the iteration mode for the datastructure.
		 *
		 * @param int $mode The new mode.
		 */
		public function setIteratorMode ( $mode ) {
			$this->_iteratorMode = $mode;
		} // end setIteratorMode();

		/**
		 * Returns the current iteration mode for the datastructure.
		 *
		 * @return int
		 */
		public function getIteratorMode () {
			return $this->_iteratorMode;
		} // end getIteratorMode();

		/**
		 * Returns the number of elements in the datastructure.
		 *
		 * @return int
		 */
		public function count () {
			return $this->_count;
		} // end count();

		/**
		 * Rewinds the iterator to the beginning of the datastructure.
		 */
		public function rewind () {
			if ( ! ( $this->_iteratorMode & self::IT_MODE_LIFO ) ) {
				$this->_itElement = reset($this->_data);
				$this->_itKey = key($this->_data);
			} else {
				$this->_itElement = end($this->_data);
				$this->_itKey = key($this->_data);
			}
			$this->_i = 0;
			$this->_finish = $this->_count;
		} // end rewind();

		/**
		 * Moves the internal pointer to the next element according
		 * to the current iteration mode.
		 */
		public function next() {
			if ( ! ( $this->_iteratorMode & self::IT_MODE_LIFO ) ) {
				$this->_itElement = next($this->_data);
				$this->_itKey = key($this->_data);
				if ( $this->_iteratorMode & self::IT_MODE_DELETE ) {
					array_shift($this->_data);
					$this->_count--;
				}
			} else {
				if ( $this->_iteratorMode & self::IT_MODE_DELETE ) {
					array_pop($this->_data);
					$this->_itElement = end($this->_data);
					$this->_count--;
				} else {
					$this->_itElement = prev($this->_data);
				}
				$this->_itKey = key($this->_data);
			}
			$this->_i++;
		} // end next();

		/**
		 * Returns the currently pointed element. If the pointer
		 * went out of the border, the behaviour is undefined.
		 *
		 * @return mixed
		 */
		public function current () {
			return $this->_itElement;
		} // end current();

		/**
		 * Returns true, if the internal iterator is set to
		 * a valid position in the datastructure.
		 *
		 * @return boolean
		 */
		public function valid () {
			return ($this->_i < $this->_finish);
		} // end valid();

		/**
		 * Returns the key of the currently pointed element. If the pointer
		 * went out of the border, the behaviour is undefined.
		 *
		 * @return int
		 */
		public function key () {
			return $this->_itKey;
		} // end key();

	} // end SplDoublyLinkedList;
}

if ( ! class_exists('SplQueue', false) ) {
	/**
	 * A PHP 5.2 reimplementation of SplQueue datastructure.
	 *
	 * @author Tomasz Jędrzejewski
	 * @copyright Invenzzia Group <http://www.invenzzia.org/> and contributors.
	 * @license http://www.invenzzia.org/license/new-bsd New BSD License
	 */
	class SplQueue extends SplDoublyLinkedList {

		/**
		 * Enqueues a new element.
		 *
		 * @param mixed $data The data to enqueue.
		 */
		public function enqueue ( $data ) {
			array_push($this->_data, $data);
			$this->_count++;
		} // end enqueue();

		/**
		 * Dequeues an element off the datastructure.
		 *
		 * @throws RuntimeException
		 * @return The dequeued data.
		 */
		public function dequeue () {
			if ( $this->_count > 0 ) {
				$this->_count--;
				return array_shift($this->_data);
			}
			throw new RuntimeException('Can\'t shift from an empty datastructure');
		} // end dequeue();

		/**
		 * Sets the iteration mode for the datastructure. Note that it is impossible
		 * to change the iteration direction for queues. In this case, an exception
		 * is thrown.
		 *
		 * @throws RuntimeException
		 * @param int $mode The new mode.
		 */
		public function setIteratorMode ( $mode ) {
			if ( $mode & self::IT_MODE_LIFO ) {
				throw new RuntimeException('Iterators\' LIFO/FIFO modes for SplStack/SplQueue objects are frozen');
			}
			parent::setIteratorMode($mode);
		} // end setIteratorMode();
	} // end SplQueue;
}

if ( ! class_exists('SplStack', false) ) {

	/**
	 * A reimplementation of the SplStack datastructure from PHP 5.3.
	 *
	 * @author Tomasz Jędrzejewski
	 * @copyright Invenzzia Group <http://www.invenzzia.org/> and contributors.
	 * @license http://www.invenzzia.org/license/new-bsd New BSD License
	 */
	class SplStack extends SplDoublyLinkedList {

		/**
		 * The new default iteration mode.
		 * @var integer
		 */
		protected $_iteratorMode = 2;

		/**
		 * Sets the iteration mode for the datastructure. Note that it is impossible
		 * to change the iteration direction for stacks. In this case, an exception
		 * is thrown.
		 *
		 * @throws RuntimeException
		 * @param int $mode The new mode.
		 */
		public function setIteratorMode ( $mode ) {
			if( ! ( $mode & self::IT_MODE_LIFO ) ) {
				throw new RuntimeException('Iterators\' LIFO/FIFO modes for SplStack/SplQueue objects are frozen');
			}
			parent::setIteratorMode($mode);
		} // end setIteratorMode();
	} // end SplStack;

}
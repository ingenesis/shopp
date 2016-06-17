<?php
/**
 * Price.php
 *
 * Product price objects
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.0
 * @subpackage products
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPrice extends ShoppDatabaseObject {

	static $table = 'price';

	public function __construct ($id=false,$key=false) {
		$this->init(self::$table);
		if ($this->load($id,$key)) {
			$this->load_download();
			$this->load_settings();

			// Recalculate promo price from applied promotional discounts
			add_action('shopp_price_updates',array(&$this,'discounts'));
		}

	}

	public function delete () {
		if ( empty($this->id) ) return;

		$price = $this->id;
		parent::delete();

		// clean up meta entries for deleted price
		$metatable = ShoppDatabaseObject::tablename('meta');
		$query = "DELETE FROM $metatable WHERE context='price' and parent=$price";
		sDB::query($query);
	}

	public function metasetloader ( &$records, &$record, $id = 'id', $property = false, $collate = false, $merge = false ) {
		if (isset($this->prices) && !empty($this->prices)) $prices = &$this->prices;
		else $prices = array();

		$metamap = array(
			'download' => 'download',
			'options' => 'options',
			'settings' => 'settings'
		);
		$metaclass = array(
			'meta' => 'ShoppMetaObject'
		);

		if ('metatype' == $property)
			$property = isset($metamap[$record->type])?$metamap[$record->type]:'meta';

		if ('download' == $record->type) {
			$collate = false;
			$data = unserialize($record->value);
			foreach (get_object_vars($data) as $prop => $val) $record->{$prop} = $val;
			$clean = array('context','type','numeral','sortorder','created','modified','value');
			foreach ($clean as $prop) unset($record->{$prop});
		}

		if ( isset($record->type) && isset($metaclass[$record->type]) ) {
			$ObjectClass = $metaclass[$record->type];
			$Object = new $ObjectClass();
			$Object->populate($record);
			if (method_exists($Object,'expopulate'))
				$Object->expopulate();

			if (is_array($prices) && isset($prices[$Object->{$id}]))
				$target = $prices[$Object->{$id}];
			elseif (isset($this))
				$target = $this;

			if (!empty($target)) {
				if (is_array($Object->value))
					foreach ( $Object->value as $prop => $setting ) {
						$target->{$prop} = $setting;

						// Determine weight ranges from loaded price settings meta
 						if ('dimensions' == $prop && isset($setting['weight'])) {
							$product = is_array($this->products)?$this->products[$target->product]:$this->products;
							if(!isset($product->min['weight']) || $product->min['weight'] == 0) $product->min['weight'] = $product->max['weight'] = $setting['weight'];
							$product->min['weight'] = min($product->min['weight'],$setting['weight']);
							$product->max['weight'] = max($product->max['weight'],$setting['weight']);
						}

					}

				else $target->{$Object->name} = $Object->value;
			}

			return;

		}

		parent::metaloader($records,$record,$prices,$id,$property,$collate,$merge);
	}

	/**
	 * Loads a product download attached to the price object
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @return boolean
	 **/
	public function load_download () {
		if ($this->type != "Download") return false;
		$this->download = new ProductDownload();
		$this->download->load(array(
			'parent' => $this->id,
			'context' => 'price',
			'type' => 'download'
			));

		if (empty($this->download->id)) return false;
		return true;
	}

	public function load_settings () {
		$settings = shopp_meta ( $this->id, 'price', 'settings');
		if ( is_array( $settings ) ) {
			foreach ( $settings as $property => $setting ) {
				$this->{$property} = $setting;
			}
		}
	}

	/**
	 * Attaches a product download asset to the price object
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	public function attach_download ($id) {
		if (!$id) return false;

		$Download = new ProductDownload($id);
		$Download->parent = $this->id;
		$Download->save();

		do_action('attach_product_download',$id,$this->id);

		return true;
	}

	/**
	 * Updates price record with provided data
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $data An associative array of key/value data
	 * @param array $ignores A list of properties to ignore updating
	 * @return void
	 **/
	public function updates ( array $data, array $ignores = array() ) {
		parent::updates($data, $ignores);
		do_action('shopp_price_updates');
	}

	/**
	 * Calculates promotional discounts applied to the price record
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean True if a discount applies
	 **/
	public function discounts () {
		if (empty($this->discounts)) return false;
		$pricetag = Shopp::str_true($this->sale)?$this->saleprice:$this->price;
		$discount = ShoppPromo::pricing($pricetag,$this->discounts);
		$this->promoprice = $discount->pricetag;
		if ($discount->freeship) $this->freeship = true;
		return true;
	}

	public function disabled () {
		return ( 'N/A' == $this->type );
	}

	/**
	 * Returns structured product price line type values and labels
	 *
	 * Used for building selection UIs in the editors
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array
	 **/
	public static function types () {
		 return array(
			array('value'=>'Shipped','label'=>__('Shipped','Shopp')),
			array('value'=>'Virtual','label'=>__('Virtual','Shopp')),
			array('value'=>'Download','label'=>__('Download','Shopp')),
			array('value'=>'Donation','label'=>__('Donation','Shopp')),
			array('value'=>'Subscription','label'=>__('Subscription','Shopp')),
			array('value'=>'N/A','label'=>__('Disabled','Shopp')),
		);
	}

	/**
	 * Returns structured subscription period values and labels
	 *
	 * Used for building selector UIs in the editors. The structure
	 * is organized with plural labels first array[0] and singular
	 * labels are second array[1].
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return array
	 **/
	public static function periods () {
		return array(
			array(
				array('value'=>'d','label'=>__('days','Shopp')),
				array('value'=>'w','label'=>__('weeks','Shopp')),
				array('value'=>'m','label'=>__('months','Shopp')),
				array('value'=>'y','label'=>__('years','Shopp')),

			),
			array(
				array('value'=>'d','label'=>__('day','Shopp')),
				array('value'=>'w','label'=>__('week','Shopp')),
				array('value'=>'m','label'=>__('month','Shopp')),
				array('value'=>'y','label'=>__('year','Shopp')),
			)
		);
	}

} // END class Price
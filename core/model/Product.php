<?php
/**
 * Product.php
 *
 * Database management of catalog products
 *
 * @author Jonathan Davis
 * @version 1.1
 * @copyright Ingenesis Limited, July, 2010
 * @package shopp
 * @since 1.0
 * @subpackage products
 **/

class Product extends WPShoppObject {
	static $table = 'posts';
	static $_taxonomies = array(
		'shopp_category' => 'categories',
		'shopp_tag' => 'tags'
	);
	static $posttype = 'shopp_product';

	var $prices = array();
	var $pricekey = array();
	var $priceid = array();
	var $categories = array();
	var $tags = array();
	var $images = array();
	var $specs = array();
	var $specnames = array();
	var $meta = array();
	var $max = array();
	var $min = array();
	var $sale = false;
	var $outofstock = false;
	var $excludetax = false;
	var $variants = 'off';
	var $addons = 'off';
	var $freeship = 'off';
	var $inventory = 'off';
	var $checksum = false;
	var $stock = 0;
	var $options = 0;

	protected $_map = array(
		'id' => 'ID',
		'name' => 'post_title',
		'slug' => 'post_name',
		'summary' => 'post_excerpt',
		'description' => 'post_content',
		'status' => 'post_status',
		'type' => 'post_type',
		'publish' => 'post_date',
		'modified' => 'post_modified',
		'post_date_gmt' => 'post_date_gmt',
		'post_modified_gmt' => 'post_modified_gmt',
		'post_content_filtered' => 'post_content_filtered',
		'comment_status' => 'comment_status',
		'ping_status' => 'ping_status',
		'to_ping' => 'to_ping',
		'pinged' => 'pinged'
	);

	/**
	 * Product constructor
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function __construct ($id=false,$key='ID') {
		if (isset($this->_map[$key])) $key = $this->_map[$key];
		$this->init(self::$table,$key);
		$this->type = self::$posttype;
		$this->load($id,$key);
	}

	function save () {
		if ( ! isset($this->ID) ) $this->ID = $this->id ? $this->id : null;
		$this->post_content_filtered = $this->to_ping = $this->pinged = '';
		$this->post_modified = current_time('timestamp');
		$gmtoffset = get_option( 'gmt_offset' ) * 3600;
		$this->post_modified_gmt = current_time('timestamp')+$gmtoffset;
		if (is_null($this->publish)) $this->post_date_gmt = $this->post_modified_gmt;
		else $this->post_date_gmt = $this->publish+$gmtoffset;
		if ( false === has_action('shopp_save_product',array($this,'savepost')))
			add_action('shopp_save_product',array($this,'savepost'));
		parent::save();
	}

	/**
	 * Provides compatibility with other plugins that handle custom post types
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function savepost () {
		if ( empty($this->id)) return;
		do_action('save_post', $this->id, get_post($this->id));
	}

	static function posttype () {
		return self::$posttype;
	}

	static function labels () {
		return apply_filters( 'shopp_product_labels', array(
			'name' => __('Products','Shopp'),
			'singular_name' => __('Product','Shopp'),
			'edit_item' => __('Edit Product','Shopp'),
			'new_item' => __('New Product','Shopp')
		));
	}

	static function capabilities () {
		return apply_filters( 'shopp_product_capabilities', array(
			'edit_post' => 'shopp_products',
			'delete_post' => 'shopp_products'
		) );
	}

	/**
	 * Loads relational data into the Product object
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @todo Add restat option handling to pass on to the price loader
	 *
	 * @return void
	 **/
	function load_data ($options=array('prices','specs','images','categories','tags','meta','summary'),&$products=array()) {
		// Load summary before prices to ensure summary can be overridden by fresh pricing aggregation
		$loaders = array(
		//  'name'      'callback_method'
			'summary' 	 => 'load_summary',
			'meta' 		 => 'load_meta',
			'prices' 	 => 'load_prices',
			'specs' 	 => 'load_meta',
			'images' 	 => 'load_meta',
			'coverimages'=> 'load_coverimages',
			'categories' => 'load_taxonomies',
			'tags' 		 => 'load_taxonomies'

		);

		$options = array_map('strtolower',$options);
		$load = array_flip(array_intersect($options,array_keys($loaders)));
		$loadcalls = array_unique(array_values(array_intersect_key($loaders,$load)));

		if (!empty($products) ) {
			$ids = join(',',array_keys($products));
			$this->products = &$products;
		} else $ids = $this->id;

		if ( empty($ids) ) return;

		foreach ($loadcalls as $loadmethod) {
			if ( method_exists($this, $loadmethod) )
				call_user_func_array(array($this,$loadmethod),array($ids));
		}

	}

	/**
	 * Loads product aggregate summary data
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function load_summary ($ids) {
		if ( empty($ids) ) return;
		$Object = new ProductSummary();
		DB::query("SELECT *,modified AS summed FROM $Object->_table WHERE product IN ($ids)",'array',array($this,'sumloader'));
	}

	/**
	 * Loads price records and populates the product
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function load_prices ($ids) {
		if ( empty($ids) ) return;

		// Reset price property
		$this->prices = array();

		// Reset summary properties for correct price range and stock sums in single product (product page) loading contexts
		if (!empty($this->id) && $this->id == $ids) {
			$this->load_summary($ids);
			$this->resum();
		}

		// Load product sales counts
		// Must come before pricing so that the summary updates will include new sold/grossed amounts
		$this->load_sold($ids);

		$Object = new Price();
		DB::query("SELECT * FROM $Object->_table WHERE product IN ($ids) ORDER BY product,sortorder",'array',array($this,'pricing'));

		// Load price metadata that exists
		if (!empty($this->priceid)) {
			$prices = join(',',array_keys($this->priceid));
			$Object->prices = $this->priceid;
			$Object->products = ( isset($this->products) && !empty($this->products) )?$this->products:$this;
			$ObjectMeta = new ObjectMeta();
			// Sort by sort order then by the modified timestamp so the most recent changes are last and become the authoritative record
			DB::query("SELECT * FROM $ObjectMeta->_table WHERE context='price' AND parent IN ($prices) ORDER BY sortorder,modified",'array',array($Object,'metaloader'),'parent','metatype','name',false);
		}

		if ( isset($this->products) && !empty($this->products) ) {
			if (!isset($this->_last_product)) $this->_last_product = false;

			if ( $this->_last_product != false
					&& isset($this->products[$this->_last_product]) )
				$this->products[$this->_last_product]->sumup();
		} else $this->sumup();

	}

	/**
	 * Loads all product meta data (meta,specs,images)
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function load_meta ($ids) {
		if ( empty($ids) ) return;
		$table = DatabaseObject::tablename(MetaObject::$table);

		$imagesort = $this->image_order();
		$metasort = array('sortorder','sortorder ASC');
		if (in_array($imagesort,$metasort))
			DB::query("SELECT * FROM $table WHERE context='product' AND parent IN ($ids) ORDER BY sortorder",'array',array($this,'metaloader'),'parent','metatype','name',false);
		else { // Separate sort order for images
			DB::query("SELECT * FROM $table WHERE context='product' AND type != 'image' AND parent IN ($ids) ORDER BY sortorder",'array',array($this,'metaloader'),'parent','metatype','name',false);
			DB::query("SELECT * FROM $table WHERE context='product' AND type = 'image' AND parent IN ($ids) ORDER BY $imagesort",'array',array($this,'metaloader'),'parent','metatype','name',false);
		}
	}

	/**
	 * Loads the cover image (first image) of the product image set
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function load_coverimages ($ids) {
		if ( empty($ids) ) return;
		$table = DatabaseObject::tablename(MetaObject::$table);
		$sortorder = $this->image_order();
		DB::query("SELECT * FROM ( SELECT * FROM $table WHERE context='product' AND type='image' AND parent IN ($ids) ORDER BY $sortorder ) AS img GROUP BY parent",'array',array($this,'metaloader'),'parent','metatype','name',false);
	}

	/**
	 * Loads the aggregate product sales data
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function load_sold ($ids) {
		if ( empty($ids) ) return;
		$purchase = DatabaseObject::tablename(Purchase::$table);
		$purchased = DatabaseObject::tablename(Purchased::$table);
		$query = "SELECT p.product as id,sum(p.quantity) AS sold,sum(p.total) AS grossed FROM $purchased as p INNER JOIN $purchase AS o ON p.purchase=o.id WHERE p.product IN ($ids) AND o.txnstatus !='void' GROUP BY p.product";
		DB::query($query,'array',array($this,'sold'));
	}

	/**
	 * Loads assigned taxonomies
	 *
	 * Loads both shopp_category and shopp_tag built-in custom taxonomies as well
	 * as other user-defined taxonomies assigned to the Shopp product custom post type
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function load_taxonomies ($ids) {
		global $ShoppTaxonomies;

		if ( empty($ids) ) return;

		if (isset($this->products) && !empty($this->products)) {
			$products = &$this->products;
			$ids = array_keys($this->products);
		} else $ids = array($this->id);

		$taxonomies = get_object_taxonomies( self::$posttype );
		$terms = wp_get_object_terms($ids,$taxonomies,array('fields' => 'all_with_object_id'));

		foreach ( $terms as $term ) { // Map WP taxonomy data to object meta
			if ( ! isset($term->term_id) || empty($term->term_id) ) continue; 		// Skip invalid entries
			if ( ! isset($term->object_id) || empty($term->object_id) ) continue;	// Skip invalid entries
			if ( isset(Product::$_taxonomies[$term->taxonomy]) ) {
				$property = Product::$_taxonomies[$term->taxonomy];
			} else {
				$property = $term->taxonomy.'s';
			}

			if ( isset($products[$term->object_id]) )
				$target = $products[$term->object_id];
			else $target = $this;

			if ( ! isset($target->$property) ) $target->$property = array();

			if ( in_array( $term->taxonomy, array_keys($ShoppTaxonomies) ) ) { // Shopp ProductTaxonomy class type
				$target->{$property}[ $term->term_id ] = new $ShoppTaxonomies[$term->taxonomy];
				$target->{$property}[ $term->term_id ]->populate($term);
				continue;
			}
			$target->{$property}[ $term->term_id ] = $term;

		} // END foreach ($terms)
	}

	/**
	 * Callback for loading products from a record set
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $records A reference to the loaded record set
	 * @param object $record Result record data object
	 * @return void
	 **/
	function loader (&$records,&$record,$DatabaseObject=false,$index='id',$collate=false) {

		if (isset($this)) {
			$index = $this->_key;
			$DatabaseObject = get_class($this);
		} else $DatabaseObject = __CLASS__;
		$index = isset($record->$index)?$record->$index:'!NO_INDEX!';
		if (!isset($DatabaseObject) || !class_exists($DatabaseObject)) return;
		$Object = new $DatabaseObject();
		$Object->populate($record);

		// Added for inventory management support
		if (isset($record->stockid)) {
			$Object->stockid = $record->stockid;
			if (isset($record->stocked)) $Object->stocked = $record->stocked;
			if (isset($record->sku)) $Object->sku = $record->sku;
			$index = $record->stockid; // Rewrite index to index on price record id
		}

		$resum = false;
		if (isset($record->summed)) { // Loaded from the collection loader
			$Object->sumloader($records,$record);

			$update = DB::mktime(ProductSummary::$_updates);
			if (DB::mktime($record->summed) == $update) $resum = true; // Forced resum

		} else $resum = true;

		if ($resum) {
			// Keep track products that need resum build run
			if (!isset($this->resum)) $this->resum = array();
			$this->resum[$index] = $Object;
		}

		if ($collate) {
			if (!isset($records[$index])) $records[$index] = array();
			$records[$index][] = $Object;
		} else $records[$index] = $Object;
	}

	/**
	 * Callback for processing meta records into the appropriate product properties
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function metaloader (&$records,&$record,$id='id',$property=false,$collate=true,$merge=false) {

		if (isset($this->products) && !empty($this->products)) $products = &$this->products;
		else $products = array();

		$metamap = array(
			'image' => 'images',
			'setting' => 'settings',
			'spec' => 'specs'
		);

		$metaclass = array(
			'image' => 'ProductImage',
			'spec' => 'Spec',
			'meta' => 'MetaObject'
		);

		if ($property == 'metatype')
			$property = isset($metamap[$record->type])?$metamap[$record->type]:'meta';

		if (isset($metaclass[$record->type])) {
			$ObjectClass = $metaclass[$record->type];
			$Object = new $ObjectClass();
			$Object->populate($record);
			if (method_exists($Object,'expopulate'))
				$Object->expopulate();

			$target = false;
			if (is_array($products) && isset($products[$Object->{$id}]))
				$target = $products[$Object->{$id}];
			elseif (isset($this))
				$target = $this;

			if (!empty($Object->name) && $target)
				$target->{$Object->name} =& $Object->value;

			$record = $Object;

		}

		if ('images' == $property) {
			// Prevent double-loading images (can occur when images are specifically loaded, then all meta is generically loaded)
			if (isset($target->$property) && isset($target->{$property}[$record->id])) return;
			$collate = 'id';
		}

		if ('specs' == $property) {
			$property = 'specnames';
			parent::metaloader($records,$record,$products,$id,$property,$collate,$merge);

			$property = 'specs';
			$collate = 'id';
		}

		parent::metaloader($records,$record,$products,$id,$property,$collate,$merge);
	}

	/**
	 * Populates the product with summary data
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function sumloader (&$records,&$data) {

		$Summary = new ProductSummary();
		$properties = array_keys($Summary->_datatypes);
		$ignore = array('product','modified');

		foreach ($properties as $property) {
			if ($property{0} == '_') continue;
			if (in_array($property,$ignore)) continue;

			switch ($property) {
				case 'ranges':
					$ranges = explode(',',$data->{$property});
					$minmax = array('min','max'); $i = 0;
					foreach ($minmax as $m) {
						$range = &$this->$m;
						foreach (ProductSummary::$_ranges as $prop) {
							if (isset($ranges[$i])) $range[$prop] = (float)$ranges[$i++];
						}
					}
					break;
				case 'taxed':
					$taxed = explode(',',$data->{$property});
					foreach ($taxed as $pricetag) {
						if ( ! $pricetag ) continue;
						list($m,$name) = explode(' ',$pricetag);

						if (empty($m)) continue;
						$range = &$this->$m;
						$range[$name.'_tax'] = true;
					}
				default: $this->{$property} = isset($data->{$property})?($data->{$property}):false;
			}
			if ( isset($this->$property) ) {
				if ('float' == $Summary->_datatypes[$property]) $this->checksum .= (float)$this->$property;
				else $this->checksum .= $this->$property;
			}
		}
		$this->checksum = md5($this->checksum);

		if (isset($data->summed)) $this->summed = DB::mktime($data->summed);
		if (shopp_setting_enabled('inventory') && Shopp::str_true($this->inventory) && $this->stock <= 0) $this->outofstock = true;
	}

	/**
	 * Aggregates product pricing information
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $options shopp() tag option list
	 * @return void
	 **/
	function pricing (&$records,&$price,$restat=false) {

		if ( isset($this->products) && !empty($this->products) ) {
			if ( !isset($this->products[$price->product]) ) return false;

			if (!isset($this->_last_product)) $this->_last_product = false;

			if ( $this->_last_product != false
					&& $this->_last_product != $price->product
					&& isset($this->products[$this->_last_product]) ) {
				$this->products[$this->_last_product]->sumup();
			}

			if ($this->_last_product != $price->product)
				$this->products[$price->product]->resum();

			$target = &$this->products[$price->product];

			$this->_last_product = $price->product;
		} else $target = &$this;

		// Skip calulating variant pricing when variants are not enabled for the product
		if (!( isset($target->variants) && Shopp::str_true($target->variants) ) && 'variation' == $price->context) return;

		$target->prices[] = $price;

		// Force to floats
		$price->price = (float)$price->price;
		$price->saleprice = (float)$price->saleprice;
		$price->shipfee = (float)$price->shipfee;
		$price->promoprice = (float)Shopp::str_true($price->sale)?$price->saleprice:$price->price;

		// Build secondary lookup table using the price id as the key
		$target->priceid[$price->id] = $price;

		// Set promoprice before data aggregation
		if (Shopp::str_true($price->sale)) $price->promoprice = $price->saleprice;

		// Do not count disabled price lines or addon price lines in aggregate summary stats
		if ('N/A' == $price->type || 'addon' ==  $price->context) return;

		// Simple product or variant product is on sale
		if (Shopp::str_true($price->sale)) $target->sale = $price->sale;

		// Build third lookup table using the combined optionkey
		$target->pricekey[$price->optionkey] = $price;

		if (Shopp::str_true($price->inventory)) {
			$target->stock += $price->stock;
			$target->inventory = $price->inventory;
			$target->lowstock($price->stock,$price->stocked);
		}

		$freeshipping = false;
		if (!Shopp::str_true($price->shipping)) $freeshipping = true;

		// Calculate catalog discounts if not already calculated
		if (!empty($price->discounts)) {
			$discount = Promotion::pricing($price->promoprice,$price->discounts);
			if ($discount->freeship) $freeshipping = true;
			$price->promoprice = $discount->pricetag;
		}

		if ($price->promoprice < $price->price) $target->sale = $price->sale = 'on';

		// Grab price and saleprice ranges (minimum - maximum)
		if (!$price->price) $price->price = 0;

		// Variation range index/properties
		$varranges = array('price' => 'price','saleprice'=>'promoprice');
		if (Shopp::str_true($price->inventory)) $varranges['stock'] = 'stock';

		foreach ($varranges as $name => $prop) {
			if (!isset($price->$prop)) continue;

			if (!isset($target->min[$name]) || $target->min[$name] == 0) $target->min[$name] = $price->$prop;
			else $target->min[$name] = min($target->min[$name],$price->$prop);
			if ($target->min[$name] == $price->$prop) $target->min[$name.'_tax'] = ($price->tax == "on");

			if (!isset($target->max[$name])) $target->max[$name] = $price->$prop;
			else $target->max[$name] = max($target->max[$name],$price->$prop);
			if ($target->max[$name] == $price->$prop) $target->max[$name.'_tax'] = ($price->tax == "on");
		}

		// Determine savings ranges
		if (Shopp::str_true($target->sale)) {

			if ( ! isset($target->min['saved']) || $target->min['saved'] === false ) {
				$target->min['saved'] = $price->price;
				$target->min['savings'] = 100;
				$target->max['saved'] = $target->max['savings'] = 0;
			}

			$target->min['saved'] = min($target->min['saved'],($price->price-$price->promoprice));
			$target->max['saved'] = max($target->max['saved'],($price->price-$price->promoprice));

			// Find lowest savings percentage
			$delta = $price->price - $price->promoprice;
			if ( $price->price == 0 ) { // no savings possible
				$target->min['savings'] = 0;
			} else if ( $delta <= 0 ) { // total savings
				$target->max['savings'] = 100;
			} else {
				$savings = ( $delta / $price->price ) * 100;
				$target->min['savings'] = min($target->min['savings'], $savings);
				$target->max['savings'] = max($target->max['savings'], $savings);
			}
		}

		if ( shopp_setting_enabled('inventory') && Shopp::str_true($target->inventory) ) $target->outofstock = ($target->stock <= 0);
		if ( $freeshipping ) $target->freeship = 'on';

	}

	/**
	 * Detect if the product is currently published
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return boolean
	 **/
	function published () {
		return ('publish' == $this->status && current_time('timestamp') >= $this->publish);
	}

	/**
	 * Returns the number of this product sold
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return int
	 **/
	function sold (&$records,&$data) {

		if (isset($this->products) && !empty($this->products)) $products = &$this->products;
		else $products = array();

		$target = false;
		if (is_array($products) && isset($products[ $data->id ]))
			$target = $products[ $data->id ];
		elseif (isset($this))
			$target = $this;

		$target->sold = $data->sold;
		$target->grossed = $data->grossed;

	}

	/**
	 * Calculates aggregate product stats from posted price data
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param object $Price The price record to calculate against
	 * @return void
	 **/
	function sumprice ($Price) {
		if ($Price->type == 'N/A' || $Price->context == 'addon') return;

		if ($this->maxprice === false) $this->maxprice = (float)$Price->promoprice;
		else $this->maxprice = max($this->maxprice,$Price->promoprice);

		if ($this->minprice === false) $this->minprice = (float)$Price->promoprice;
		else $this->minprice = min($this->minprice,$Price->promoprice);

		if (Shopp::str_true($Price->sale)) $this->sale = $Price->sale;

		if (Shopp::str_true($Price->inventory)) {
			$this->inventory = $Price->inventory;
			$this->stock += $Price->stock;
			$this->lowstock($Price->stock,$Price->stocked);
		} elseif (!$this->inventory) $this->inventory = 'off';

	}

	/**
	 * Resets summary data to intial values so summation is accurate
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function resum () {
		$this->lowstock = 'none';
		$this->sale = $this->inventory = 'off';
		$this->stock = $this->stocked = 0;
		$this->maxprice = $this->minprice = false;
		$this->min = $this->max = array();
		$this->freeship = 'off';
		foreach ( ProductSummary::$_ranges as $index ) {
			$this->min[$index] = false;
			$this->max[$index] = false;
		}
	}

	/**
	 * Saves generated stats to the product record
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $stats The stat properties to update
	 * @return void
	 **/
	function sumup () {
		if (empty($this->id)) return;

		$Summary = new ProductSummary();
		$properties = array_keys($Summary->_datatypes);
		$minmax = array('min','max');
		$ignore = array('product','modified');

		$checksum = false;
		foreach ($properties as $property) {
			if ($property{0} == '_') continue;
			if (in_array($property,$ignore)) continue;
			switch ($property) {
				case 'minprice': $this->minprice = (float)$this->min[ Shopp::str_true($this->sale)?'saleprice':'price' ]; break;
				case 'maxprice': $this->maxprice = (float)$this->max[ Shopp::str_true($this->sale)?'saleprice':'price' ]; break;
				case 'ranges':
					$ranges = array();
					foreach ($minmax as $m) {
						$attr = $this->$m;
						foreach (ProductSummary::$_ranges as $name)
							if (isset($attr[$name])) $ranges[] = (float)$attr[$name];
					}
					break;
				case 'taxed':
					$taxable = array('price','saleprice');
					$taxed = array();
					foreach ($minmax as $m) {
						$attr = $this->$m;
						foreach ($taxable as $name)
							if (isset($attr[$name.'_tax']) && $attr[$name.'_tax']) $taxed[] = "$m $name";
					}
					break;
				default:

			}

			if ( isset($this->$property) ) {
				if ('float' == $Summary->_datatypes[$property]) $checksum .= (float)$this->$property;
				else $checksum .= $this->$property;
			}
		}

		if (md5($checksum) == $this->checksum) return;

		$Summary->copydata($this);
		if (isset($this->summed))
			$Summary->modified = $this->summed;
		$Summary->product = $this->id;
		$Summary->ranges = join(',',$ranges);
		$Summary->taxed = join(',',$taxed);
		$Summary->save();
	}

	/**
	 * Determines the aggregate lowstock level of the product for each price record
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void Description...
	 **/
	function lowstock ($stock,$stocked) {
		$lowstock_level = shopp_setting('lowstock_level');
		if ( false === $lowstock_level ) $lowstock_level = 5;
		$setting = ( shopp_setting('lowstock_level')/100 );

		$levels = array('none','warning','critical','backorder');
		$max = array_search($this->lowstock,$levels);
		$factors = array(0,1,3);

		$x = count($factors);
		foreach ($factors as $factor) {
			if ($stock < min(1,$setting*$factor) * $stocked ) break;
			$x--;
		}

		$this->lowstock = $levels[max($max,$x)];
	}

	/**
	 * Magic option key generator
	 *
	 * There is no Zul only XOR!
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array option ids
	 * @return int option key
	 **/
	function optionkey ($ids=array(),$deprecated=false) {
		if ($deprecated) $factor = 101;
		else $factor = 7001;
		if (empty($ids)) return 0;
		$key = 0;
		foreach ($ids as $set => $id)
			$key = $key ^ ($id*$factor);
		return $key;
	}

	function optionmap ( $variant = array(), $menus = array(), $type = 'variant', $return = 'all' ) {
		if ( empty($variant) || empty($menus) ) return;

		$selection = array();
		$mapping = array();
		$count = 1;

		// get saved product options
		$poptions = array();
		$pkey = 'addon' == $type ? 'a' : 'v';
		if ( isset($this->options[$pkey]) ) $poptions = $this->options[$pkey];

		foreach ( $menus as $menuname => $options ) {

			// get saved product menu
			$pmenu = array();
			foreach ( $poptions as $pmenu ) if ( $pmenu['name'] == $menuname ) break;

			$mapping[$menuname] = array();
			foreach ( $options as $option ) {

				// get save option id
				$poption = array();
				if ( isset($pmenu['options']) ) foreach ( $pmenu['options'] as $poption )
					if ( $poption['name'] == $option ) break;

				$id = isset($poption['id']) ? $poption['id'] : $count++;
				$mapping[$menuname][$option] = $id;
			}
		}
		if ( 'addon' == $type) {
			$type = key($variant);
			$option = current($variant);

			$selection[] = $mapping[$type][$option];
			if ( 'optionkey' == $return ) return $this->optionkey($selection);
			return array( $this->optionkey($selection), $selection[0], $option, $mapping );
		}

		foreach ( array_keys($variant) as $menuname ) {
			$selection[] = $mapping[ $menuname ][ $variant[ $menuname ] ];
		}

		if ( 'optionkey' == $return ) return $this->optionkey($selection);

		return array($this->optionkey($selection), implode(',', $selection), implode(', ', $variant), $mapping);
	}

	/**
	 * Updates the custom arrangement order of image assets
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array image ids
	 * @return void
	 **/
	function save_imageorder ($ordering) {
		$table = DatabaseObject::tablename(ProductImage::$table);
		foreach ($ordering as $i => $id)
			DB::query("UPDATE LOW_PRIORITY $table SET sortorder='$i' WHERE (id='$id' AND parent='$this->id' AND context='product' AND type='image')");
	}

	/**
	 * Translates image order settings to an appropriate SQL order by clause
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	function image_order () {
		$orderings = array('ASC','DESC','RAND');
		$ordering = shopp_setting('product_image_order');
		if (!in_array($ordering,$orderings)) $ordering = '';

		$columns = array('sortorder','created');
		$column = shopp_setting('product_image_orderby');
		if (!in_array($column,$columns)) $column = reset($columns);

		$sortorder = trim("$column $ordering");
		if ('RAND' == $ordering) $sortorder = 'RAND()';

		return $sortorder;
	}

	/**
	 * Links a set of image records to the product
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array image record ids
	 * @return void
	 **/
	function link_images ($images) {
		if (empty($images)) return;
		$table = DatabaseObject::tablename(ProductImage::$table);
		DB::query("UPDATE $table SET parent='$this->id',context='product' WHERE id IN (".join(',',$images).")");
	}

	/**
	 * Updates image details for all cached images of the product
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param array image record ids
 	 * @return void
	 **/
	function update_images ($images) {
		if (!is_array($images)) return;

		foreach ($images as $img) {

			$Image = new ProductImage($img['id']);
			$Image->title = stripslashes($img['title']);
			$Image->alt = stripslashes($img['alt']);

			if (!empty($img['cropping'])) {
				if (!class_exists('ImageProcessor'))
					require(SHOPP_MODEL_PATH."/Image.php");

				foreach ($img['cropping'] as $id => $cropping) {
					if (empty($cropping)) continue;
					$Cropped = new ProductImage($id);

					list($Cropped->settings['dx'],
						$Cropped->settings['dy'],
						$Cropped->settings['cropscale']) = explode(',', $cropping);
					extract($Cropped->settings);

					$Resized = new ImageProcessor($Image->retrieve(),$Image->width,$Image->height);
					$scaled = $Image->scaled($width,$height,$scale);
					$scale = $Cropped->_scaling[$scale];
					$quality = ($quality === false)?$Cropped->_quality:$quality;

					$Resized->scale($scaled['width'],$scaled['height'],$scale,$alpha,$fill,(int)$dx,(int)$dy,(float)$cropscale);

					// Post sharpen
					if ($sharpen !== false) $Resized->UnsharpMask($sharpen);
					$Cropped->data = $Resized->imagefile($quality);
					if (empty($Cropped->data)) return false;

					$Cropped->size = strlen($Cropped->data);
					if ($Cropped->store( $Cropped->data ) === false)
						return false;
					$Cropped->save();

				}
			}

			$Image->save();
		}

	}


	/**
	 * delete_images()
	 * Delete provided array of image ids, removing the source image and
	 * all related images (small and thumbnails) */
	function delete_images ($images) {
		$db = &DB::get();
		$imagetable = DatabaseObject::tablename(ProductImage::$table);
		$imagesets = "";
		foreach ($images as $image) {
			$imagesets .= (!empty($imagesets)?" OR ":"");
			$imagesets .= "((context='product' AND parent='$this->id' AND id='$image') OR (context='image' AND parent='$image'))";
		}
		if (!empty($imagesets))
			$db->query("DELETE FROM $imagetable WHERE type='image' AND ($imagesets)");
		return true;
	}

	/**
	 * Deletes all the associated records of the product
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return void
	 **/
	function delete () {
		$id = $this->id;
		if (empty($id)) return false;

		// Delete assignment to taxonomies (categories, tags, custom taxonomies)
		wp_delete_object_term_relationships($id, get_object_taxonomies(Product::$posttype));

		// Delete prices
		$table = DatabaseObject::tablename(Price::$table);
		DB::query("DELETE LOW_PRIORITY FROM $table WHERE product='$id'");

		// Delete images/files
		$table = DatabaseObject::tablename(ProductImage::$table);

		// Delete images
		$images = array();
		$src = DB::query("SELECT id FROM $table WHERE parent='$id' AND context='product' AND type='image'",'array');
		foreach ($src as $img) $images[] = $img->id;
		$this->delete_images($images);

		// Delete product meta (specs, images, downloads)
		$table = DatabaseObject::tablename(MetaObject::$table);
		DB::query("DELETE LOW_PRIORITY FROM $table WHERE parent='$id' AND context='product'");

		// Delete product summary
		$table = DatabaseObject::tablename(ProductSummary::$table);
		DB::query("DELETE FROM $table WHERE product='$id'");

		// Delete record
		DB::query("DELETE FROM $this->_table WHERE ID='$id'");

		do_action_ref_array('shopp_product_deleted',array($this));

	}

	/**
	 * Moves the product to the trash
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	function trash () {
		$id = $this->{$this->_key};
		DB::query("UPDATE $this->_table SET post_status='trash' WHERE ID='$id'");
	}

	/**
	 * Creates a duplicate product of this product's data
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 * @version 1.2
	 *
	 * @return void
	 **/
	function duplicate () {

		$original = $this->id;

		$this->load_data(); // Load everything
		$this->id = '';
		$this->name = $this->name.' '.__('copy','Shopp');
		$slug = sanitize_title_with_dashes($this->name);
		$this->slug = wp_unique_post_slug($slug, $this->id, $this->status, Product::posttype(), 0);
		$this->created = '';
		$this->modified = '';
		$this->status = 'draft'; // Set duplicated product to draft status

		$this->save();

		// Copy prices
		foreach ($this->prices as $price) {

			$Price = new Price();
			$Price->copydata($price);
			$Price->product = $this->id;
			$Price->save();

			// Copy Price record meta entries
			$meta = array('donation','recurring','membership','dimensions');
			$priceline['settings'] = array();
			$settings = array();
			foreach ($meta as $name)
				if ( isset($price->$name) ) $settings[$name] = $price->$name;

			shopp_set_meta($Price->id,'price','settings',$settings);
			shopp_set_meta($Price->id,'price','options',$price->options);

		}

		// Copy taxonomy assignments
		$terms = array();
		$taxonomies = get_object_taxonomies( self::$posttype );
		$assignments = wp_get_object_terms($original,$taxonomies,array('fields' => 'all_with_object_id'));
		foreach ( $assignments as $term ) { // Map WP taxonomy data to object meta
			if ( ! isset($term->term_id) || empty($term->term_id) ) continue; 		// Skip invalid entries
			if ( ! isset($term->taxonomy) || empty($term->taxonomy) ) continue; 	// Skip invalid entries

			if (!isset($terms[$term->taxonomy])) $terms[$term->taxonomy] = array();
			$terms[$term->taxonomy][] = (int)$term->term_id;
		}
		foreach ($terms as $taxonomy => $termlist)
			wp_set_object_terms( $this->id, $termlist, $taxonomy );

		$metadata = array('specs','images','settings','meta');
		foreach ($metadata as $metaset) {
			foreach ($this->$metaset as $meta) {
				$ObjectClass = get_class($meta);
				$Meta = new $ObjectClass();
				$Meta->copydata($meta);
				$Meta->parent = $this->id;
				$Meta->save();
			}
		}

		// Re-summarize product pricing
		$this->load_data(array('prices','summary'));

		// Duplicate (WP) post meta data
		foreach ( get_post_custom( $original ) as $key => $values ) {
			foreach ( (array) $values as $value )
				add_post_meta( $this->id, $key, $value );
		}
	}

	/**
	 * Matches the product against tax conditional rules
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array Conditional rule to match against
	 * @return boolean Match or no match
	 **/
	function taxrule ($rule) {
		switch ($rule['p']) {
			case "product-name": return ($rule['v'] == $this->name); break;
			case "product-tags":
				if (empty($this->tags)) $this->load_data(array('tags'));
				foreach ($this->tags as $tag) if ($rule['v'] == $tag->name) return true;
				break;
			case "product-category":
				if (empty($this->categories)) $this->load_data(array('categories'));
				foreach ($this->categories as $category) if ($rule['v'] == $category->name) return true;
		}
		return false;
	}

	/**
	 * Sets the status of a set of products
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $ids Set of product IDs to update
	 * @param string $status The status to set: publish, draft, trash
	 * @return boolean
	 **/
	static function publishset ($ids,$status) {

		if ( empty($ids) || ! is_array($ids) ) return false;
		$settings = array('publish', 'draft', 'trash');

		if ( ! in_array($status, $settings) ) return false;
		$table = WPShoppObject::tablename(self::$table);

		$time = current_time('timestamp');
		$post_date_gmt = DB::mkdatetime($time + (get_option( 'gmt_offset' ) * 3600));
		$post_date = DB::mkdatetime($time);

		DB::query("UPDATE $table SET post_status='$status', post_date='$post_date', post_date_gmt='$post_date_gmt', post_modified='$post_date', post_modified_gmt='$post_date_gmt' WHERE ID in (" . join(',', $ids) . ")");

		return true;
	}

	/**
	 * Updates the featured setting of a set of products
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $ids Set of product IDs to update
	 * @param string $setting Either 'on' or 'off'
	 * @return boolean
	 **/
	static function featureset ($ids,$setting) {
		if (empty($ids) || !is_array($ids)) return false;
		$settings = array('on','off');
		if (!in_array($setting,$settings)) return false;
		foreach ($ids as $id) {
			$Product = new ProductSummary((int)$id);
			$Product->featured = $setting;
			$Product->save();
		}
		return true;
	}

} // END class Product

// @todo Document ProductSummary class
class ProductSummary extends DatabaseObject {
	static $table = 'summary';
	static $_ranges = array('price','saleprice','saved','savings','weight');
	static $_updates = '0000-00-00 00:00:01';

	function __construct ($id=false,$key='product') {
		$this->init(self::$table);
		$this->_key = 'product';
		$this->load($id,$key);
	}

	function save () {
		$data = DB::prepare($this,$this->_map);

		$id = $this->{$this->_key};
		if (!empty($this->_map)) {
			$remap = array_flip($this->_map);
			if (isset($remap[$this->_key]))
				$id = $this->{$remap[$this->_key]};
		}

		// Insert new record
		$data['modified'] = "'".current_time('mysql')."'";
		$dataset = DatabaseObject::dataset($data);
		$query = "INSERT $this->_table SET $dataset ON DUPLICATE KEY UPDATE $dataset";
		$id = DB::query($query);
		do_action_ref_array('shopp_save_productsummary', array(&$this));
		return $id;

	}

} // END class ProductSummary

// @todo Document Spec class
class Spec extends MetaObject {

	function __construct ($id=false) {
		$this->init(self::$table);
		$this->load($id);
		$this->context = 'product';
		$this->type = 'spec';
	}

	function updates ($data,$ignores=array()) {
		parent::updates($data,$ignores);
		if (preg_match('/^.*?(\d+[\.\,\d]*).*$/',$this->value))
			$this->numeral = preg_replace('/^.*?(\d+[\.\,\d]*).*$/','$1',$this->value);
	}

} // END class Spec

?>

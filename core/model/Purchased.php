<?php
/**
 * Purchased class
 * Purchased line items for orders
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 28 March, 2008
 * @package shopp
 **/

class Purchased extends DatabaseObject {
	static $table = "purchased";

	public $inventory = false;

	function Purchased ($id=false,$key=false) {
		$this->init(self::$table);
		if ($this->load($id,$key)) return true;
		else return false;
	}

	function copydata ($Item) {
		parent::copydata ($Item);
		if ( isset($Item->option->label) )
			$this->optionlabel = $Item->option->label;

		$this->price = $Item->option->id;

		// Generate download link for downloadables
		if ( 'Download' == $Item->type && ! empty($this->download) ) {
			$this->keygen();
			$this->download = (int)$this->download->id; // Convert download property to integer ID
		}

		$this->addons = 'no';
		if (empty($Item->addons) || !is_array($Item->addons)) return true;
		$addons = array();
		// Create meta records for any addons
		foreach ((array)$Item->addons as $i => $Addon) {
			$Download = false;
			$Meta = new MetaObject(array(
				'parent' => $this->id,
				'context' => 'purchased',
				'type' => 'meta',
				'name' => $Addon->label
			));
			$Meta->context = 'purchased';
			$Meta->type = 'addon';
			$Meta->name = $Addon->label;
			$Meta->numeral = $Addon->unitprice;

			// Add a meta record to the purchased line item for an addon download
			if (!empty($Addon->download)) {
				$hash = array($this->name,$Addon->label,$this->purchase,$this->product,$this->price,$i,time());
				$Addon->dkey = sha1(join('',$hash));

				$Download = new MetaObject(array(
					'parent' => $this->id,
					'context' => 'purchased',
					'type' => 'download',
					'name' => $Addon->dkey
				));
				$Download->context = 'purchased';
				$Download->type = 'download';
				$Download->name = $Addon->dkey;
				$Download->value = $Addon->download;
			}

			$Meta->value = serialize($Addon);
			$addons[] = $Meta;
			if ($Download !== false) $addons[] = $Download;

		}
		$this->addons = $addons;
	}

	function save () {
		$addons = $this->addons;					// Save current addons model
		if (!empty($addons) && is_array($addons)) $this->addons = 'yes';	// convert property to usable flag
		parent::save();
		if (!empty($addons) && is_array($addons)) {
			foreach ($addons as $Addon) {
				$Addon->parent = $this->id;
				$Addon->save();
			}
		}

		// Update sold tallies
		if (!empty($this->product)) {
			$summary = DatabaseObject::tablename(ProductSummary::$table);
			DB::query("UPDATE $summary SET sold=sold+$this->quantity,grossed=grossed+$this->total WHERE product='$this->product'");
		}

		$this->addons = $addons; // restore addons model
	}

	function delete () {
		$table = DatabaseObject::tablename(MetaObject::$table);
		DB::query("DELETE LOW_PRIORITY FROM $table WHERE parent='$this->id' AND context='purchased'");
		parent::delete();
	}

	function keygen () {
		$message = ShoppCustomer()->email.serialize($this).current_time('mysql');
		$key = sha1($message);

		$limit = 25; $c = 0;
		while ((int)DB::query("SELECT count(*) AS found FROM $this->_table WHERE dkey='$key'",'auto','col','found') > 0) {
			$key = sha1($message.rand());
			if ($c++ > $limit) break;
		}

		$this->dkey = $key;
		do_action_ref_array('shopp_download_keygen',array(&$this));
	}

	function exportcolumns () {
		$prefix = "p.";
		return array(
			$prefix.'id' => __('Line Item ID','Shopp'),
			$prefix.'name' => __('Product Name','Shopp'),
			$prefix.'optionlabel' => __('Product Variation Name','Shopp'),
			$prefix.'description' => __('Product Description','Shopp'),
			$prefix.'sku' => __('Product SKU','Shopp'),
			$prefix.'quantity' => __('Product Quantity Purchased','Shopp'),
			$prefix.'unitprice' => __('Product Unit Price','Shopp'),
			$prefix.'total' => __('Product Total Price','Shopp'),
			$prefix.'data' => __('Product Data','Shopp'),
			$prefix.'downloads' => __('Product Downloads','Shopp')
			);
	}

} // end Purchased class

?>
<?php
/**
 * Purchased.php
 *
 * Purchased line items for orders
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, March 2008
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPurchased extends ShoppDatabaseObject {

	static $table = 'purchased';

	public $inventory = false;

	public function __construct ( $id = false, $key = false ) {
		$this->init(self::$table);
		if ( $this->load($id, $key) ) return true;
		else return false;
	}

	public function copydata ( $Item, $prefix = '', array $ignores = array() ) {
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
			$Meta = new ShoppMetaObject(array(
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

				$Download = new ShoppMetaObject(array(
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

	public function save () {
		$addons = $this->addons;					// Save current addons model
		if (!empty($addons) && is_array($addons)) $this->addons = 'yes';	// convert property to usable flag
		parent::save();
		if (!empty($addons) && is_array($addons)) {
			foreach ($addons as $Addon) {
				$Addon->parent = $this->id;
				$Addon->save();
			}
		}

		$this->addons = $addons; // restore addons model
	}

	public function delete () {
		$table = ShoppDatabaseObject::tablename(ShoppMetaObject::$table);
		sDB::query("DELETE FROM $table WHERE parent='$this->id' AND context='purchased'");
		parent::delete();
	}

	public function keygen () {
		$message = ShoppCustomer()->email.serialize($this).current_time('mysql');
		$key = sha1($message);

		$limit = 25; $c = 0;
		while ((int)sDB::query("SELECT count(*) AS found FROM $this->_table WHERE dkey='$key'",'auto','col','found') > 0) {
			$key = sha1($message.rand());
			if ($c++ > $limit) break;
		}

		$this->dkey = $key;
		do_action_ref_array('shopp_download_keygen',array(&$this));
	}

	public static function exportcolumns () {
		$prefix = "p.";
		return array(
			$prefix.'id' => __('Line Item ID','Shopp'),
			$prefix.'name' => __('Product Name','Shopp'),
			$prefix.'optionlabel' => __('Product Variation Name','Shopp'),
			'addons.name' => __('Product Add-on Name', 'Shopp'),
			$prefix.'description' => __('Product Description','Shopp'),
			$prefix.'sku' => __('Product SKU','Shopp'),
			$prefix.'quantity' => __('Product Quantity Purchased','Shopp'),
			$prefix.'unitprice' => __('Product Unit Price','Shopp'),
			$prefix.'total' => __('Product Total Price','Shopp'),
			$prefix.'data' => __('Product Data','Shopp'),
			$prefix.'downloads' => __('Product Downloads','Shopp')
			);
	}

}

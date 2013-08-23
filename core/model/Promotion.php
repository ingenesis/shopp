<?php
/**
 * Promotion class
 * Handles special promotion deals
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 2 September, 2008
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class Promotion extends DatabaseObject {
	static $table = "promo";

	static $values = array(
		"Name" => "text",
		"Category" => "text",
		"Variation" => "text",
		"Price" => "price",
		"Sale price" => "price",
		"Type" => "text",
		"In stock" => "text",
		"Any item name" => "text",
		"Any item quantity" => "text",
		"Any item amount" => "price",
		"Total quantity" => "text",
		"Shipping amount" => "price",
		"Subtotal amount" => "price",
		"Promo use count" => "text",
		"Promo code" => "text",
		"Ship-to country" => "text",
		"Customer type" => "text"
	);

	function __construct ($id=false) {
		$this->init(self::$table);
		if ($this->load($id)) return true;
		else return false;
	}

	function catalog () {

		$product_table = WPDatabaseObject::tablename(Product::$table);
		$price_table = DatabaseObject::tablename(Price::$table);

		$where_notdiscounted = array("0 = FIND_IN_SET($this->id,discounts)");
		$where = array();
		// Go through each rule to construct an SQL query
		// that gets all applicable product & price ids
		if (!empty($this->rules) && is_array($this->rules)) {
			foreach ($this->rules as $rule) {

				if (Promotion::$values[$rule['property']] == "price")
					$value = Shopp::floatval($rule['value']);
				else $value = $rule['value'];

				switch($rule['logic']) {
					case "Is equal to": $match = "='$value'"; break;
					case "Is not equal to": $match = "!='$value'"; break;
					case "Contains": $match = " LIKE '%$value%'"; break;
					case "Does not contain": $match = " NOT LIKE '%$value%'"; break;
					case "Begins with": $match = " LIKE '$value%'"; break;
					case "Ends with": $match = " LIKE '%$value'"; break;
					case "Is greater than": $match = "> $value"; break;
					case "Is greater than or equal to": $match = ">= $value"; break;
					case "Is less than": $match = "< $value"; break;
					case "Is less than or equal to": $match = "<= $value"; break;
				}

				switch($rule['property']) {
					case "Name":
						$where[] = "p.post_title$match";
						$joins[$product_table] = "INNER JOIN $product_table as p ON prc.product=p.id";
						break;
					case "Category":
						$where[] = "tm.name$match";
						global $wpdb;
						$joins[$wpdb->term_relationships] = "INNER JOIN $wpdb->term_relationships AS tr ON (prc.product=tr.object_id)";
						$joins[$wpdb->term_taxonomy] = "INNER JOIN $wpdb->term_taxonomy AS tt ON (tr.term_taxonomy_id=tt.term_taxonomy_id)";
						$joins[$wpdb->terms] = "INNER JOIN $wpdb->terms AS tm ON (tm.term_id=tt.term_id)";
						break;
					case "Variation": $where[] = "prc.label$match"; break;
					case "Price": $where[] = "prc.price$match"; break;
					case "Sale price": $where[] = "(prc.onsale='on' AND prc.saleprice$match)"; break;
					case "Type": $where[] = "prc.type$match"; break;
					case "In stock": $where[] = "(prc.inventory='on' AND prc.stock$match)"; break;
				}

			}

		}

		$operator = 'all' == strtolower($this->search)?' AND ': ' OR ';
		if (!empty($where)) $where = "WHERE ".join($operator,$where);
		else $where = false;

		if (!empty($joins)) $joins = join(' ',$joins);
		else $joins = false;

		// Find all the pricetags the promotion is *currently assigned* to
		$query = "SELECT id FROM $price_table WHERE 0 < FIND_IN_SET($this->id,discounts)";
		$current = DB::query($query,'array','col','id');

		// Find all the pricetags the promotion is *going to apply* to
		$query = "SELECT prc.id,prc.product,prc.discounts FROM $price_table AS prc
					$joins
					$where";
		$updates = DB::query($query,'array','col','id');

		// Determine which records need promo added to and removed from
		$added = array_diff($updates,$current);
		$removed = array_diff($current,$updates);

		// Add discounts to specific rows
		$query = "UPDATE $price_table
					SET discounts=CONCAT(discounts,IF(discounts='','$this->id',',$this->id'))
					WHERE id IN (".join(',',$added).")";
		if (!empty($added)) DB::query($query);

		// Remove discounts from pricetags that now don't match the conditions
		if (!empty($removed)) $this->uncatalog($removed);

		// Recalculate product stats for products with pricetags that have changed
		$Collection = new PromoProducts(array('id' => $this->id));
		$Collection->load( array('load'=>array('prices'),'pagination' => false) );
	}

	function uncatalog ( $pricetags ) {
		if ( empty($pricetags) ) return;

		$table = DatabaseObject::tablename(Price::$table);
		$discounted = DB::query("SELECT id,product,discounts,FIND_IN_SET($this->id,discounts) AS offset FROM $table WHERE id IN ('" . join(',', $pricetags) . "')", 'array');

		$products = array();
		foreach ( $discounted as $index => $pricetag ) {
			$products[] = $pricetag->product;
			$promos = explode(',', $pricetag->discounts);
			array_splice($promos, ($pricetag->offset - 1), 1); // Remove the located promotion ID from the discounts list
			DB::query("UPDATE LOW_PRIORITY $table SET discounts='" . join(',', $promos) . "' WHERE id=$pricetag->id");
		}

		// Force resum on products next load
		$summary = DatabaseObject::tablename('summary');
		DB::query("UPDATE LOW_PRIORITY $summary SET modified='" . ProductSummary::$_updates . "' WHERE product IN (" . join(',', $products). ")");
	}

	/**
	 * Finds all price records that have the specified list of discounts applied to them
	 *
	 * @author Jonathan Davis
	 * @since 1.2.4
	 *
	 * @param array $ids List of promotion IDs
	 * @return array List of price record IDs
	 **/
	static function discounted_prices ( $ids ) {
		$where = array();
		foreach ( $ids as $id )
			$where[ $id ] = "0 < FIND_IN_SET('$id',discounts)";
		$table = DatabaseObject::tablename('price');
		$query = "SELECT id FROM $table WHERE " . join(" OR ", $where);
		$pricetags = DB::query($query, 'array', 'col', 'id');
		return (array)$pricetags;
	}

	/**
	 * match_rule ()
	 * Determines if the value of a given subject matches the rule based
	 * on the specified operation */
	static function match_rule ($subject,$op,$value,$property=false) {
		switch($op) {
			// String or Numeric operations
			case "Is equal to":
			 	if($property && Promotion::$values[$property] == 'price') {
					return ( Shopp::floatval($subject) != 0
					&& Shopp::floatval($value) != 0
					&& Shopp::floatval($subject) == Shopp::floatval($value));
				} else {
					if (is_array($subject)) return (in_array($value,$subject));
					return ("$subject" === "$value");
				}
				break;
			case "Is not equal to":
				if (is_array($subject)) return (!in_array($value,$subject));
				return ("$subject" !== "$value"
						|| (Shopp::floatval($subject) != 0
						&& Shopp::floatval($value) != 0
						&& Shopp::floatval($subject) != Shopp::floatval($value)));
						break;

			// String operations
			case "Contains":
				if (is_array($subject)) {
					foreach ($subject as $s)
						if (stripos($s,$value) !== false) return true;
					return false;
				}
				return (stripos($subject,$value) !== false); break;
			case "Does not contain":
				if (is_array($subject)) {
					foreach ($subject as $s)
						if (stripos($s,$value) !== false) return false;
					return true;
				}
				return (stripos($subject,$value) === false); break;
			case "Begins with":
				if (is_array($subject)) {
					foreach ($subject as $s)
						if (stripos($s,$value) === 0) return true;
					return false;
				}
				return (stripos($subject,$value) === 0); break;
			case "Ends with":
				if (is_array($subject)) {
					foreach ($subject as $s)
						if (stripos($s,$value) === strlen($s) - strlen($value)) return true;
					return false;
				}
				return  (stripos($subject,$value) === strlen($subject) - strlen($value)); break;

			// Numeric operations
			case "Is greater than":
				return (Shopp::floatval($subject,false) > Shopp::floatval($value,false));
				break;
			case "Is greater than or equal to":
				return (Shopp::floatval($subject,false) >= Shopp::floatval($value,false));
				break;
			case "Is less than":
				return (Shopp::floatval($subject,false) < Shopp::floatval($value,false));
				break;
			case "Is less than or equal to":
				return (Shopp::floatval($subject,false) <= Shopp::floatval($value,false));
				break;
		}

		return false;
	}

	/**
	 * Records when a specific promotion is used
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @param array $promos A list of Promotion ids of the promotions to be updated
	 * @return void
	 **/
	function used ($promos) {
		if (empty($promos) || !is_array($promos)) return;
		$table = DatabaseObject::tablename(self::$table);
		DB::query("UPDATE LOW_PRIORITY $table SET uses=uses+1 WHERE 0 < FIND_IN_SET(id,'".join(',',$promos)."')");
	}

	static function activedates () {

		// By default the promotion editor will save a value of 1
		// for the start and end dates if no date values are provided.
		// We can evaluate in SQL if the dates are set by checking
		// if they are more or less than the default. However, we
		// wse an offset amount as a buffer to account for how
		// MySQL's UNIX_TIMESTAMP() converts the datetime to a
		// UTC-based timestamp from the Jan 1, 1970 00:00:00 epoch
		// 43200 to represents 12-hours (UTC +/- 12 hours), then we
		// add 1 to account for the default amount set in the editor
		$offset = 43200 + 1;

		return "(
		    -- Promo is not date based
		    (
		        UNIX_TIMESTAMP(starts) <= $offset
		        AND
		        UNIX_TIMESTAMP(ends) <= $offset
		    )
		    OR
		    -- Promo has start and end dates, check that we are in between
		    (
		        UNIX_TIMESTAMP(starts) > $offset
		        AND
		        UNIX_TIMESTAMP(ends) > $offset
		        AND
		        (".current_time('timestamp')." BETWEEN UNIX_TIMESTAMP(starts) AND UNIX_TIMESTAMP(ends))
		    )
		    OR
		    -- Promo has _only_ a start date, check that we are after it
		    (
		        UNIX_TIMESTAMP(starts) > $offset
		        AND
		        UNIX_TIMESTAMP(ends) <= $offset
		        AND
		        UNIX_TIMESTAMP(starts) < ".current_time('timestamp')."
		    )
		    OR
		    -- Promo has _only_ an end date, check that we are before it
		    (
		        UNIX_TIMESTAMP(starts) <= $offset
		        AND
		        UNIX_TIMESTAMP(ends) > $offset
		        AND
		        ".current_time('timestamp')." < UNIX_TIMESTAMP(ends)
			)
	    )";
	}

	/**
	 * Duplicates a promotion
	 *
	 * @author Jonathan Davis
	 * @since 1.1
	 *
	 * @return Promotion The newly created Promotion object
	 **/
	function duplicate () {
		$Promotion = new Promotion();
		$Promotion->copydata($this);
		$Promotion->name = sprintf(__('%s copy','Shopp'),$Promotion->name);
		$Promotion->status = 'disabled';
		$Promotion->uses = 0;
		$Promotion->created = null;
		$Promotion->modified = null;
		$Promotion->save();
		return $Promotion;
	}

	/**
	 * Lookup group discounts by id
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return void
	 **/
	static function pricing ($pricetag,$ids) {
		$discount = new StdClass();
		$discount->pricetag = $pricetag;
		$discount->freeship = false;

		if (empty($pricetag) || empty($ids)) return $discount;

		$table = DatabaseObject::tablename(self::$table);
		$query = "SELECT type,SUM(discount) AS amount FROM $table WHERE 0 < FIND_IN_SET(id,'$ids') AND (discount > 0 OR type='Free Shipping') AND status='enabled' AND " . self::activedates() . " GROUP BY type ORDER BY type DESC";
		$discounts = DB::query($query,'array');
		if (empty($discounts)) return $discount;

		$freeship = false;
		// Apply discounts
		$a = $p = 0;
		foreach ($discounts as $r) {
			switch ($r->type) {
				case 'Amount Off': $a += $r->amount; break;
				case 'Percentage Off': $p += $r->amount; break;
				case 'Free Shipping': $discount->freeship = true; break;
			}
		}

		if ($a > 0) $pricetag -= $a; // Take amounts off first (to reduce merchant percentage discount burden)
		if ($p > 0)	$pricetag -= ($pricetag * ($p/100));

		$discount->pricetag = $pricetag;

		return $discount;
	}

	/**
	 * Deletes an entire set of promotions
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $ids List of promotion IDs to delete
	 * @return boolean Success/fail
	 **/
	static function deleteset ($ids) {
		if (empty($ids) || !is_array($ids)) return false;

		$prices = self::discounted_prices($ids);	// Get the discounted price records

		foreach ( $ids as $id ) {
			$Promo = new Promotion($id);
			if ( 'Catalog' == $Promo->target )
				$Promo->uncatalog($prices);			// Remove the deleted price discounts
		}


		$table = DatabaseObject::tablename(self::$table);
		DB::query("DELETE FROM $table WHERE id IN (" . join(',', $ids) . ")"); // Delete the promotions

		return true;
	}

	/**
	 * Enable an entire set of promotions
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $ids List of promotion IDs to enable
	 * @return boolean Success/fail
	 **/
	static function enableset ($ids) {
		if (empty($ids) || !is_array($ids)) return false;
		$table = DatabaseObject::tablename(self::$table);
		DB::query("UPDATE $table SET status='enabled' WHERE id IN (".join(',',$ids).")");

		$catalogpromos = DB::query("SELECT id FROM $table WHERE target='Catalog'",'array','col','id');
		foreach ($catalogpromos as $promoid) {
			$Promo = new Promotion($promoid);
			$Promo->catalog();
		}

		return true;
	}

	/**
	 * Disables an entire set of promotions
	 *
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @param array $ids List of promotion IDs to disable
	 * @return boolean Success/fail
	 **/
	static function disableset ($ids) {
		if (empty($ids) || !is_array($ids)) return false;
		$table = DatabaseObject::tablename(self::$table);
		DB::query("UPDATE $table SET status='disabled' WHERE id IN (".join(',',$ids).")");

		$catalogpromos = DB::query("SELECT id FROM $table WHERE target='Catalog'",'array','col','id');
		foreach ($catalogpromos as $promoid) {
			$Promo = new Promotion($promoid);
			$Promo->catalog();
		}

		return true;
	}


} // END class Promotion

<?php
/**
 * ScreenDiscountEditor.php
 *
 * Screen controller to display the discount editor.
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Discounts
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenDiscountEditor extends ShoppScreenController {

	public function load () {
		if ( $this->request('new') ) {
			$Promo = new ShoppPromo();
		} elseif ( $this->request('id') ) {
			$Promo = new ShoppPromo($this->request('id'));
			do_action('shopp_discount_promo_loaded', $Promo);
		}
		return $Promo;
	}

	public function assets () {
		wp_enqueue_script('postbox');
		shopp_enqueue_script('colorbox');
		shopp_enqueue_script('calendar');
		shopp_enqueue_script('suggest');

		do_action('shopp_promo_editor_scripts');
	}

	public function layout () {

		$Promotion = $this->Model;

		new ShoppAdminDiscountSaveBox($this, 'side', 'core', array('Promotion' => $Promotion));
		new ShoppAdminDiscountBox($this, 'normal', 'core', array('Promotion' => $Promotion));
		new ShoppAdminDiscountRulesBox($this, 'normal', 'core', array('Promotion' => $Promotion));

	}

	public function screen () {

		if ( ! current_user_can('shopp_promotions') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		if ( ! $this->request('new') ) {
			$Promotion = new ShoppPromo($this->request('id'));
			do_action('shopp_discount_promo_loaded', $Promotion);
		} else $Promotion = new ShoppPromo();

		// $this->disabled_alert($Promotion);

		include $this->ui('editor.php');
	}

	public function save ( ShoppPromo $Promo ) {

		$uncatalog = ( 'Catalog' == $Promo->target );
		$Promo->updates($this->form());

		$fields = array('month' => '', 'date' => '', 'year' => '');

		$starts = array_intersect_key((array)$this->form('starts'), $fields);
		if ( '' !== join('', $starts) )
			$Promo->starts = mktime(0, 0, 0, $starts['month'], $starts['date'], $starts['year']);

		$ends = array_intersect_key((array)$this->form('ends'), $fields);
		if ( '' !== join('', $ends) )
			$Promo->ends = mktime(23, 59, 59, $ends['month'], $ends['date'], $ends['year']);

		$rules = (array)$this->form('rules');
		foreach($rules as &$rule) {

			if ( 'promo code' == strtolower($rule['property']) )
				$rule['value'] = trim($rule['value']);

			if ( false !== stripos($rule['property'], 'country') && 'USA' == $rule['value'] )
				$rule['value'] = 'US'; // country-based rules must use 2-character ISO code, see #3129

		}
		$Promo->discount = Shopp::floatval($Promo->discount);
		$Promo->save();

		do_action_ref_array('shopp_promo_saved', array(&$Promo));

		// Apply catalog promotion discounts to catalog product price lines
		if ( 'Catalog' == $Promo->target ) {
			$Promo->catalog();
		} elseif ( $uncatalog ) {
			// Unapply catalog discounts for discounts that no longer target catalog products
			$priceids = ShoppPromo::discounted_prices(array($Promo->id));
			$Promo->uncatalog($priceids);
		}

		// Set confirmation notice
		$this->notice(Shopp::__('Promotion has been updated!'));

		// Stay in the editor
		if ( $this->request('new') )
			Shopp::redirect( $this->url(array('id' => $Promo->id, 'new' => null)) );

	}

	/**
	 * Add a notice to make sure the merchant is aware that the promotion is not enabled (if that happens to be the
	 * case). If this is undesirable it can be turned off by adding some code to functions.php or another suitable
	 * location:
	 *
	 *  add_filter('shopp_hide_disabled_promo_warning', function() { return true; } ); // 5.3 style
	 */
	protected function disabled_alert ( ShoppPromo $Promotion ) {
		if ( 'enabled' === $Promotion->status || apply_filters('shopp_hide_disabled_promo_warning', false) ) return;
		$this->notice(Shopp::__('This discount is not currently enabled.'), 'notice', 20);
	}

	public static function types () {
		$types = apply_filters('shopp_discount_types', array(
			'Percentage Off' => Shopp::__('Percentage Off'),
			'Amount Off' => Shopp::__('Amount Off'),
			'Free Shipping' => Shopp::__('Free Shipping'),
			'Buy X Get Y Free' => Shopp::__('Buy X Get Y Free')
		));
		return $types;
	}

	public static function scopes () {
		$scopes = apply_filters('shopp_discount_scopes', array(
			'Catalog' => Shopp::__('price'),
			'Cart' => Shopp::__('subtotal'),
			'Cart Item' => Shopp::__('unit price, where:')
		));
		echo json_encode($scopes);
	}

	public static function targets () {
		$targets = apply_filters('shopp_discount_targets', array(
			'Catalog' => Shopp::__('Product'),
			'Cart' => Shopp::__('Cart'),
			'Cart Item' => Shopp::__('Cart')
		));
		$targets = array_map('strtolower', $targets);
		echo json_encode($targets);
	}

	public static function rules () {
		$rules = apply_filters('shopp_discount_rules', array(
			'Name' => Shopp::__('Name'),
			'Category' => Shopp::__('Category'),
			'Variation' => Shopp::__('Variation'),
			'Price' => Shopp::__('Price'),
			'Sale price' => Shopp::__('Sale price'),
			'Type' => Shopp::__('Type'),
			'In stock' => Shopp::__('In stock'),

			'Tag name' => Shopp::__('Tag name'),
			'Unit price' => Shopp::__('Unit price'),
			'Total price' => Shopp::__('Total price'),
			'Input name' => Shopp::__('Input name'),
			'Input value' => Shopp::__('Input value'),
			'Quantity' => Shopp::__('Quantity'),

			'Any item name' => Shopp::__('Any item name'),
			'Any item amount' => Shopp::__('Any item amount'),
			'Any item quantity' => Shopp::__('Any item quantity'),
			'Total quantity' => Shopp::__('Total quantity'),
			'Shipping amount' => Shopp::__('Shipping amount'),
			'Subtotal amount' => Shopp::__('Subtotal amount'),
			'Discount amount' => Shopp::__('Discount amount'),

			'Customer type' => Shopp::__('Customer type'),
			'Ship-to country' => Shopp::__('Ship-to country'),

			'Promo code' => Shopp::__('Discount code'),
			'Promo use count' => Shopp::__('Discount use count'),
			'Discounts applied' => Shopp::__('Discounts applied'),

			'Is equal to' => Shopp::__('Is equal to'),
			'Is not equal to' => Shopp::__('Is not equal to'),
			'Contains' => Shopp::__('Contains'),
			'Does not contain' => Shopp::__('Does not contain'),
			'Begins with' => Shopp::__('Begins with'),
			'Ends with' => Shopp::__('Ends with'),
			'Is greater than' => Shopp::__('Is greater than'),
			'Is greater than or equal to' => Shopp::__('Is greater than or equal to'),
			'Is less than' => Shopp::__('Is less than'),
			'Is less than or equal to' => Shopp::__('Is less than or equal to')
		));

		echo json_encode($rules);
	}

	public static function conditions () {
		$conditions = apply_filters('shopp_discount_conditions', array(
			'Catalog' => array(
				'Name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Category' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_category'),
				'Variation' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text'),
				'Price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Sale price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Type' => array('logic' => array('boolean'), 'value' => 'text'),
				'In stock' => array('logic' => array('boolean', 'amount'), 'value' => 'number')
			),
			'Cart' => array(
				'Any item name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Any item quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Any item amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Total quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Shipping amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Subtotal amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Discount amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Customer type' => array('logic' => array('boolean'), 'value' => 'text', 'source' => 'shopp_customer_types'),
				'Ship-to country' => array('logic' => array('boolean'), 'value' => 'text', 'source' => 'shopp_target_markets', 'suggest' => 'alt'),
				'Discounts applied' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Promo use count' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Promo code' => array('logic' => array('boolean'), 'value' => 'text')
			),
			'Cart Item' => array(
				'Any item name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Any item quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Any item amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Total quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Shipping amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Subtotal amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Discount amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Customer type' => array('logic' => array('boolean'), 'value' => 'text', 'source' => 'shopp_customer_types'),
				'Ship-to country' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_target_markets'),
				'Discounts applied' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Promo use count' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Promo code' => array('logic' => array('boolean'), 'value' => 'text')
			),
			'Cart Item Target' => array(
				'Name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_products'),
				'Category' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_category'),
				'Tag name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text', 'source' => 'shopp_tag'),
				'Variation' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text',),
				'Input name' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text'),
				'Input value' => array('logic' => array('boolean', 'fuzzy'), 'value' => 'text'),
				'Quantity' => array('logic' => array('boolean', 'amount'), 'value' => 'number'),
				'Unit price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Total price' => array('logic' => array('boolean', 'amount'), 'value' => 'price'),
				'Discount amount' => array('logic' => array('boolean', 'amount'), 'value' => 'price')
			)
		));
		echo json_encode($conditions);
	}

	public static function logic () {
		$logic = apply_filters('shopp_discount_logic', array(
			'boolean' => array('Is equal to', 'Is not equal to'),
			'fuzzy' => array('Contains', 'Does not contain', 'Begins with', 'Ends with'),
			'amount' => array('Is greater than', 'Is greater than or equal to', 'Is less than', 'Is less than or equal to')
		));
		echo json_encode($logic);
	}

}
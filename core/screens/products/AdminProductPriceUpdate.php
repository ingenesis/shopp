<?php
/**
 * AdminProductPriceUpdate.php
 *
 * Controller for updating a product price from the submitted product form
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminProductPriceUpdate extends ShoppRequestFormFramework {

	/** @var ShoppProduct $Product The target ShoppProduct to update */
	private $Product = false;

	/** @var ShoppPrice $Price The target ShoppPrice to update */
	private $Price = false;

	/** @var array $sortorder The sort order for price options set by the user */
	private $sortorder = false;

	/**
	 * Constructor.
	 *
	 * @since 1.5
	 * @param ShoppProduct $Product The target ShoppProduct to update
	 * @param ShoppPrice $Price The target ShoppPrice to update
	 * @param array $form The processed form data
	 * @param array $sortorder The sort order for price options set by the user
	 * @return void
	 **/
	public function __construct( ShoppProduct $Product, ShoppPrice $Price, array $form, $sortorder) {
		$this->Product = $Product;
		$this->Price = $Price;
		$this->form = $form;
		$this->sortorder = $sortorder;
	}

	/**
	 * Update the price object from form data
	 *
	 * @since 1.5
	 * @param int $index The index of the price entry in the form data to match with the sortorder
	 * @return void
	 **/
	public function updates( $index ) {
		$form = $this->form;
		$form['sortorder'] = 1 + array_search($index, $this->sortorder);
		$form['shipfee'] = Shopp::floatval($form['shipfee']);

		if ( isset($form['recurring']['trialprice']) )
			$form['recurring']['trialprice'] = Shopp::floatval($form['recurring']['trialprice']);

		if ( $this->Price->stock != $form['stocked'] ) {
			$form['stock'] = (int) $form['stocked'];
			do_action('shopp_stock_product', $form['stock'], $this->Price, $this->Price->stock, $this->Price->stocklevel);
		} else unset($form['stocked']);

		$this->Price->updates($form);
		$this->Price->save();
	}

	/**
	 * Update meta data for the product
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function meta() {
		$form = $this->form;

		// Save 'price' meta records after saving the price record
		if ( isset($form['dimensions']) && is_array($form['dimensions']) )
			$form['dimensions'] = array_map(array('Shopp', 'floatval'), $form['dimensions']);

		$settings = array('donation', 'recurring', 'membership', 'dimensions');

		$form['settings'] = array();
		foreach ( $settings as $setting )
			if ( isset($form[ $setting ]) )
				$form['settings'][ $setting ] = $form[ $setting ];

		if ( ! empty($form['settings']) )
			shopp_set_meta($this->Price->id, 'price', 'settings', $form['settings']);

		if ( ! empty($form['options']) )
			shopp_set_meta($this->Price->id, 'price', 'options', $form['options']);
	}

	/**
	 * Update download for the product
	 *
	 * @since 1.5
	 * @return void
	 **/
	public function download() {
		$form = $this->form;
		if ( ! empty($form['download']) )
			$this->Price->attach_download($form['download']);
		elseif ( ! empty($form['downloadpath']) ) {
			$filename = ! empty($form['downloadfile']) ? $form['downloadfile'] : basename(sanitize_path($form['downloadpath']));
			$this->Price->attach_download_by_path($form['downloadpath'], $filename);
		}
	}

}
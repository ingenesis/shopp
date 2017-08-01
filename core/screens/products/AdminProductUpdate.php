<?php
/**
 * AdminProductUpdate.php
 *
 * Controller for updating a product from the submitted product form
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminProductUpdate extends ShoppRequestFormFramework {
	
	/** @var ShoppProduct $Product The target ShoppProduct object to update */
	private $Product = false;
	
	/**
	 * Constructor.
	 *
	 * @since 1.4
	 * @param ShoppProduct $Product The target ShoppProduct object to update
	 * @return void
	 **/
	public function __construct( ShoppProduct $Product ) {
		$this->Product = $Product;
		$this->posted();
	}
	
	/**
	 * Update the product publishing status and publish date
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function status() {

		$status = $this->form('status');
		$publish = (array)$this->form('publish');
		unset($this->form['publish']);
		
		$Product->publish = 0;
		
		// Save current status
		$this->form['prestatus'] = $this->Product->status;
		
		// Set publish date
		if ( 'publish' == $status ) {
			$fields = array('month' => '', 'date' => '', 'year' => '', 'hour' => '', 'minute' => '', 'meridiem' => '');
			$publish = array_intersect_key($publish, $fields);
			
			$publishfields = join('', $publish);
			$this->Product->publish = null;
			if ( ! empty($publishfields) ) {

				if ( 'PM' == $publish['meridiem'] && $publish['hour'] < 12 )
					$publish['hour'] += 12;
				
				$this->Product->publish = mktime($publish['hour'], $publish['minute'], 0, $publish['month'], $publish['date'], $publish['year']);
				
				$Product->status = 'future';
				unset($this->form['status']);
			}		
		}
	}
	
	/**
	 * Update the product with core product data from the form
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function updates () {
		// Set a unique product slug
		if ( empty($this->Product->slug) )
			$this->Product->slug = sanitize_title($this->form('name'));
		$this->Product->slug = wp_unique_post_slug($this->Product->slug, $this->Product->id, $this->Product->status, ShoppProduct::posttype(), 0);

		$this->Product->featured = 'off';
		$this->form['description'] = $this->form('content');
		
		$this->Product->updates($this->form(), array('meta', 'categories', 'prices', 'tags'));
	}
 
 	/**
 	 * Update or delete prices
 	 *
 	 * Depends on ShoppAdminProductPriceUpdate
 	 * 
 	 * @since 1.4
 	 * @return void
 	 **/
	public function prices() {
		
		$deleting = $this->form('deletePrices');
		
		if ( ! empty($deleting) ) {
			$deletes = explode(',', $deletes);

			foreach( $deletes as $option ) {
				$Price = new ShoppPrice($option);
				$Price->delete();
			}
		}
		
		$this->Product->resum();
		
		$formprice = $this->form('price');
		$sortorder = $this->form('sortorder');
		
		if ( ! is_array($formprice) )
			return;
		
		foreach ( $formprice as $index => $form ) {
			$id = empty($form['id']) ? null : intval($form['id']);
			$form['product'] = $this->Product->id;
			$Price = new ShoppPrice($id);
			$PriceUpdate = new ShoppAdminProductPriceUpdate($this->Product, $Price, $form, $sortorder);
			$PriceUpdate->updates($index);
			$PriceUpdate->meta();
			$PriceUpdate->download();

			$this->Product->sumprice($Price);
			unset($Price, $PriceUpdate);
		}

		$this->Product->load_sold($this->Product->id); // Refresh accurate product sales stats
		$this->Product->sumup();
	}
	
	/**
	 * Delete leftover ShoppPrice entries when the defined variant/addon options are deleted
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function trimprices() {
		// No variation options at all, delete all variation-pricelines
		if ( ! is_array($this->Product->prices) )
			return;
		
		$metadata = $this->form('meta');
		$options = isset($metadata['options']) ? stripslashes_deep($metadata['options']) : false;

		if ( ! empty($options['v']) && ! empty($options['a']) )
			return;
				
		foreach ( $this->Product->prices as $priceline ) {
			if ( $priceline->optionkey == 0 ) 
				continue; // Skip priceline if not tied to variation options
			elseif ( ! empty($options[ substr($priceline->context, 0, 1) ]) ) // skip priceline for 
				continue; // non-empty $options['a'] or $options['v'] depending on priceline context of 'addon' or 'variation'
						
			$Price = new ShoppPrice($priceline->id);
			$Price->delete();
		}
	}
  
  	/**
  	 * Delete, link or update images for the product
  	 *
  	 * @since 1.4
  	 * @return void
  	 **/
	public function images() {
		$deleting = $this->form('deleteImages');
		
		// Remove deleted images
		if ( ! empty($deleting) ) {
			$deletes = array($deleting);
			if ( false !== strpos($deleting, ',') ) 
				$deletes = explode(',', $deleting);
			$this->Product->delete_images($deletes);
		}

		$images = $this->form('images');
		$details = $this->form('imagedetails');
		
		// Update image data
		if ( is_array($images) ) {
			$this->Product->link_images($images);
			$this->Product->save_imageorder($images);

			$this->Product->update_images($details);
		}
	}
	
	/**
	 * Update taxonomies added or removed from the product
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function taxonomies() {
		// Update taxonomies after pricing summary is generated
		// Summary table entry is needed for ProductTaxonomy::recount() to
		// count properly based on aggregate product inventory, see #2968
		$taxonomies = get_object_taxonomies(ShoppProduct::$posttype);
		foreach ( $taxonomies as $taxonomy ) {
			$tags = '';
			$taxonomy_obj = get_taxonomy($taxonomy);
			$tax_input = $this->form('tax_input');

			if ( isset($tax_input[ $taxonomy ]) ) {
				$tags = $tax_input[ $taxonomy ];
				if ( is_array($tags) ) // array = hierarchical, string = non-hierarchical.
					$tags = array_filter($tags);
			}

			if ( current_user_can($taxonomy_obj->cap->assign_terms) )
				wp_set_post_terms( $this->Product->id, $tags, $taxonomy );
		}

		// Ensure taxonomy counts are updated on status changes, see #2968
		if ( $this->form('prestatus') != $this->form('status') ) {
			$Post = new StdClass;
			$Post->ID = $this->Product->id;
			$Post->post_type = ShoppProduct::$posttype;
			wp_transition_post_status($this->form('prestatus'), $this->Product->status, $Post);
		}
		
	}
	
	/**
	 * Delete or update product specs
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function specs() {
		
		$deleting = $this->form('deletedSpecs');
		if ( ! empty($deleting) ) { // Delete specs queued for removal
			$ids = array();
			$deletes = array_map('intval', explode(',', $deleting));

			$ids = sDB::escape(join(',', $deletes));
			$Spec = new Spec();
			sDB::query("DELETE FROM $Spec->_table WHERE id IN ($ids)");
		}
		
		$details = $this->form('details');
		if ( ! is_array($details) )
			return;
		
		$sortorder = $this->form('details-sortorder');
		foreach ( $details as $index => $spec ) {
			$id = isset($spec['new']) ? false : intval($spec['id']);
			if ( in_array($id, $deletes) )
				continue; // Skip deleted specs
			
			$Spec = new Spec($id); // Create or load an existing spec for updates
			$spec['parent'] = $this->Product->id;
			// Sort order is not 0-indexed, so start with 1
			$spec['sortorder'] = 1 + array_search($index, $sortorder);

			$Spec->updates($spec);
			$Spec->save();
		}
		
	}
	
	/**
	 * Update product meta data
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function meta() {
		
		$metadata = $this->form('meta');
		
		if ( ! is_array($metadata) )
			return;

		foreach ( $metadata as $name => $value ) {
			if ( isset($this->Product->meta[ $name ]) ) {
				$Meta = $this->Product->meta[ $name ];
				if ( is_array($Meta) ) 
					$Meta = reset($Meta);
			} else $Meta = new ShoppMetaObject(array(
				'parent' => $this->Product->id,
				'context' => 'product',
				'type' => 'meta',
				'name' => $name
			));
			
			$Meta->parent = $this->Product->id;
			$Meta->context = 'product';
			$Meta->name = $name;
			$Meta->value = $value;
			$Meta->save();
		}
	}

} 
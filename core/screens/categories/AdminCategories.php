<?php
/**
 * AdminCategories.php
 *
 * Admin controller for product category admin screens
 *
 * This controller routes requests to the proper category sub-screen, and
 * handles overall logic for deleting and saving categories. Special
 * logic is included to handle Category editor workflow behaviors.
 *
 * @todo Need to provide a way for notices to route from this controller to the proper screen display controller
 *
 * @copyright Ingenesis Limited, August 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Category
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminCategories extends ShoppAdminPostController {

	protected $ui = 'categories';

	protected function route () {

		$this->workflow();

		if ( 'products' == $this->request('a') && $this->request('id') )
			return 'ShoppScreenCategoryArrangeProducts';
		elseif ( $this->request('id') )
			return 'ShoppScreenCategoryEditor';
		else return 'ShoppScreenCategories';
	}

	/**
	 * Handles loading and saving categories in a workflow context
	 *
	 * @since 1.0
	 * @return void
	 **/
	public function workflow () {

		$id = $this->form('id');
		$Category = self::loader($id);

		if ( $this->form('save') ) // Save updates from the editor
			$Category = $this->save($Category);

		$settings = $this->form('settings');
		$workflow = isset($settings['workflow']) ? $settings['workflow'] : false;

		if ( ! $workflow ) return;

		$worklist = $this->worklist();
		$working = array_search($id, $this->worklist());
		$next = 'close';

		switch( $workflow ) {
			case 'new': $next = 'new'; break;
			case 'next': $next = isset($worklist[ ++$working ]) ? $worklist[ $working ] : 'close'; break;
			case 'previous': $next = isset($worklist[ --$working ]) ? $worklist[ $working ] : 'close'; break;
			case 'continue': $next = $Category->id; break;
			case 'close':
			default: $next = 'close';
		}

		if ( 'close' == $next ) {
			$reset = array('action' => null, 'id' => null, '_wpnonce' => null, );
			$redirect = add_query_arg(array_merge($_GET, $reset), admin_url('admin.php'));
			Shopp::redirect( $redirect );
			return;
		}

		$_GET['workflow'] = $next; // Rewrite the request
		$this->query(); // Reprocess the request query

	}

	/**
	 * Builds a list of category IDs based on the current request
	 *
	 * This is used for workflow next/previous handling.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function worklist () {

		$per_page_option = get_current_screen()->get_option( 'per_page' );

		$defaults = array(
			'paged' => 1,
			'per_page' => 20,
			's' => '',
			'a' => ''
		);
		$args = array_merge($defaults, $_GET);
		if ( false !== ( $user_per_page = get_user_option($per_page_option['option']) ) ) $args['per_page'] = $user_per_page;
		extract($args, EXTR_SKIP);

		if ('arrange' == $a)  {
			$this->init_positions();
			$per_page = 300;
		}

		$paged = absint( $paged );
		$start = ($per_page * ($paged-1));
		$end = $start + $per_page;

		$url = add_query_arg(array_merge($_GET,array('page'=>ShoppAdmin::pagename('categories'))),admin_url('admin.php'));

		$taxonomy = 'shopp_category';

		$filters = array('hide_empty' => 0, 'fields' => 'id=>parent');
		add_filter('get_shopp_category', array(__CLASS__, 'termcategory'),10,2);

		// $filters['limit'] = "$start,$per_page";
		if (!empty($s)) $filters['search'] = $s;

		$Categories = array(); $count = 0;
		$terms = get_terms( $taxonomy, $filters );
		if (empty($s)) {
			$children = _get_term_hierarchy($taxonomy);
			ProductCategory::tree($taxonomy, $terms, $children, $count, $Categories, $paged, $per_page);
			$this->categories = $Categories;
		} else {
			foreach ($terms as $id => $parent)
				$Categories[$id] = get_term($id,$taxonomy);
		}

		$ids = array_keys($Categories);
		return $ids;
	}

	/**
	 * Handles saving updated category information from the category editor
	 *
	 * @todo refactor complexity
	 * @todo avoid direct access to $_POST
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function save ( $Category ) {
		$Shopp = Shopp::object();

		check_admin_referer('shopp-save-category');

		if ( ! current_user_can('shopp_categories') )
			wp_die(__('You do not have sufficient permissions to access this page.'));

		shopp_set_formsettings(); // Save workflow setting

		if (empty($Category->meta))
			$Category->load_meta();

		$form = $this->form();

		$Category->name = $this->form('name');
		$Category->description = $this->form('content');
		$Category->parent = $this->form('parent');

		// Sanitize variation price template data
		$Category->prices = array();
		if ( is_array($this->form('price')) ) {
			$prices = $this->form('price');
			foreach ( $prices as &$pricing ) {
				$pricing['price']      = Shopp::floatval($pricing['price'], false);
				$pricing['saleprice']  = Shopp::floatval($pricing['saleprice'], false);
				$pricing['shipfee']    = Shopp::floatval($pricing['shipfee'], false);
				$pricing['dimensions'] = array_map(array('Shopp', 'floatval'), $pricing['dimensions']);
			}
		}

		$Category->specs = array();

		$metafields = array('spectemplate', 'facetedmenus', 'variations', 'pricerange', 'priceranges', 'specs', 'prices');
		$metadata = Shopp::array_filter_keys($this->form(), $metafields);

		// Add meta[options] inputs from varition templates to stored metadata
		$meta = $this->form('meta');
		if ( isset($meta['options']) )
			$metadata['options'] = $meta['options'];

		if ( empty($metadata['options']) || ( 1 == count($metadata['options']['v']) && ! isset($metadata['options']['v'][1]['options']) ) ) {
			// Remove prices or options if no templates are specified or if 1 empty option exists
			unset($metadata['options'], $metadata['prices']);
			$Category->options = $Category->prices = array();
		}

		// Update existing entries
		$updates = array();
		foreach ($Category->meta as $id => $MetaObject) {
			$name = $MetaObject->name;
			if ( isset($metadata[ $name ]) ) {
				$MetaObject->value = stripslashes_deep($metadata[ $name ]);
				$updates[] = $name;
			}
		}

		// Create any new missing meta entries
		$new = array_diff(array_keys($metadata), $updates); // Determine new entries from the exsting updates
		foreach ( $new as $name ) {
			if ( ! isset($metadata[ $name ]) ) continue;
			$Meta = new MetaObject();
			$Meta->name = $name;
			$Meta->value = stripslashes_deep($metadata[ $name ]);
			$Category->meta[] = $Meta;
		}

		$Category->save();

		$deletelist = $this->form('deleteImages');
		if ( ! empty($deletelist) ) {
			$deletes = array();
			if ( false !== strpos($deletelist, ',') )
				$deletes = explode(',', $deletelist);
			else $deletes = array($deletelist);
			$Category->delete_images($deletes);
		}

		$images = $this->form('images');
		if ( ! empty($images) && is_array($images) ) {
			$Category->link_images($images);
			$Category->save_imageorder($images);

			$imgdetails = $this->form('imagedetails');
			if ( ! empty($imagedetails) && is_array($imagedetails) ) {
				foreach($imagedetails as $i => $data) {
					$Image = new CategoryImage($data['id']);
					$Image->title = $data['title'];
					$Image->alt = $data['alt'];
					$Image->save();
				}
			}
		}

		do_action_ref_array('shopp_category_saved', array($Category));

		// TODO fix notice() call
		// $this->notice(Shopp::__('%s category saved.', '<strong>' . $Category->name . '</strong>'));

		return $Category;
	}

	/**
	 * Convert a term to a Product Category
	 *
	 * @since 1.4
	 * @return void
	 */
	public static function termcategory ( $term, $taxonomy ) {
		$Category = new ProductCategory();
		$Category->populate($term);
		return $Category;
	}

	/**
	 * Load a product category for editing
	 *
	 * @since 1.4
	 * @return void
	 */
	public static function loader ( $id ) {
		$Category = new ProductCategory($id);

		$meta = array('specs', 'priceranges', 'options', 'prices');
		foreach ( $meta as $prop )
			if ( ! isset($Category->$prop) ) $Category->$prop = array();

		return $Category;
	}


}
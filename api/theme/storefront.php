<?php
/**
* storefront.php
*
* ShoppStorefrontThemeAPI provides shopp('storefront') Theme API tags
*
* @api
* @copyright Ingenesis Limited 2012-2014
* @package Shopp\API\Theme\Storefront
* @version 1.3
* @since 1.2
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_themeapi_context_name', array('ShoppStorefrontThemeAPI', '_context_name'));

/**
 * Provides shopp('storefront') theme API functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 **/
class ShoppStorefrontThemeAPI implements ShoppAPI {

	/**
	 * @var array The registry of available `shopp('storefront')` properties
	 * @internal
	 **/
	static $register = array(
		'breadcrumb'      => 'breadcrumb',
		'businessname'    => 'business_name',
		'businessaddress' => 'business_address',
		'categories'      => 'categories',
		'category'        => 'category',
		'collection'      => 'category',
		'categorylist'    => 'category_list',
		'currency'        => 'currency',
		'display'         => 'type',
		'errors'          => 'errors',
		'type'            => 'type',
		'hascategories'   => 'has_categories',
		'isaccount'       => 'is_account',
		'iscart'          => 'is_cart',
		'iscategory'      => 'is_taxonomy', // @deprecated in favor of istaxonomy
		'istaxonomy'      => 'is_taxonomy',
		'iscollection'    => 'is_collection',
		'ischeckout'      => 'is_checkout',
		'islanding'       => 'is_frontpage',
		'isfrontpage'     => 'is_frontpage',
		'iscatalog'       => 'is_catalog',
		'isproduct'       => 'is_product',
		'orderbylist'     => 'orderby_list',
		'product'         => 'product',
		'recentshoppers'  => 'recent_shoppers',
		'search'          => 'search',
		'searchform'      => 'search_form',
		'sideproduct'     => 'side_product',
		'tagproducts'     => 'tag_products',
		'tagcloud'        => 'tag_cloud',
		'url'             => 'url',
		'views'           => 'views',
		'zoomoptions'     => 'zoom_options',

		'accountmenu'     => 'account_menu',
		'accountmenuitem' => 'account_menuitem',

	);

	/**
	 * Provides the authoritative Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		return 'storefront';
	}

	/**
	 * Remaps the other Theme API contexts to the authoritative context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _context_name ( $name ) {
		switch ( $name ) {
			case 'storefront':
			case 'catalog':
				return 'storefront';
				break;
		}
		return $name;
	}


	/**
	 * Returns the proper global context object used in a shopp('collection') call
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @param ShoppStorefront $Object The ShoppOrder object to set as the working context
	 * @param string          $context The context being worked on by the Theme API
	 * @return ShoppStorefront The active object context
	 **/
	public static function _setobject ( $Object, $context ) {
		if ( is_object($Object) && is_a($Object, 'ShoppCatalog') ) return $Object;

		switch ( strtolower($context) ) {
			case 'storefront':
			case 'catalog':
				return ShoppCatalog();
				break;
		}

		return $Object; // not mine, do nothing
	}

	/**
	 * Generates image markup
	 *
	 * Internal Theme API helper but must remain publicly available for other Theme API classes to access.
	 *
	 * @internal
	 *
	 * @param string $result  The output
	 * @param array  $options The options
	 * - **alt**: The alt property of the image
	 * - **bg**: The background color to use with the matte fit (#rrggbb)
	 * - **class**: Specifies the CSS class of the image
	 * - **fit**: The fit of unproportional images to the requested size:
	 *   - **all**: Scale the image down to fit within the new size (the final size may differ from the specified dimensions)
	 *   - **crop**: Scale the image down to fit by the smallest dimension to fill the entire image, cropping the extra off the other dimension (specific cropping adjustments can be made in the product editor)
	 *   - **height**: Scale the image down to fit the image in the new size by the height, cropping any extra width
	 *   - **matte**: Scale the image down to fit within the new size filling extra space with a background color
	 *   - **width**: Scale the image down to fit the image in the new size by the width, cropping any extra height
	 * - **id**: Specify the image to show by the database ID
	 * - **index**: Specify the index of the image to show
	 * - **property**: (id,url,src,title,alt,width,height,class) Provide a property of the image rather than the image markup
	 * - **quality**: The JPEG image quality (0-100%, default is 80)
	 * - **sharpen**: Apply an unsharp mask to the image (100%-500%, default is none)
	 * - **size**: The size to use for width and height of the image (used in place of width and height)
	 * - **title**: The title property of the image
	 * - **width**: The width of the image in pixels
	 * - **height**: The height of the image in pixels
	 * - **zoom**: `off` Enables the image zoom effect to view the original size image in a modal image viewer (Colorbox)
	 * - **zoomfx**: `shopp-zoom` Enables zoom (also known as lightbox) effects for alternate JavaScript-based modal content viewers.
	 * @param Object $O       The working object
	 * @return string The generated image markup
	 **/
	public static function image ( $result, $options, $O ) {
		if ( empty($O->images) ) return;

		// Compatibility defaults
		$_size   = 96;
		$_width  = shopp_setting('gallery_thumbnail_width');
		$_height = shopp_setting('gallery_thumbnail_height');

		if ( ! $_width ) $_width = $_size;
		if ( ! $_height ) $_height = $_size;

		$defaults = array(
			'alt'      => '',
			'bg'       => false,
			'class'    => '',
			'fit'      => null,
			'height'   => false,
			'id'       => false,
			'img'      => false,
			'index'    => false,
			'property' => false,
			'quality'  => null,
			'setting'  => '',
			'sharpen'  => null,
			'size'     => false,
			'title'    => '',
			'width'    => false,
			'zoom'     => false,
			'zoomfx'   => 'shopp-zoom',
		);

		// Populate defaults from named image settings to allow specific overrides
		if ( ! empty($options['setting']) ) {
			$setting       = $options['setting'];
			$ImageSettings = ImageSettings::object();
			$settings      = $ImageSettings->get($setting);
			if ( $settings ) $defaults = array_merge($defaults, $settings->options());
		}

		$options = array_merge($defaults, $options);
		extract($options);

		// Select image by database id
		if ( false !== $id ) {
			if ( isset($O->images[ $id ]) ) $Image = $O->images[ $id ];
			else {
				shopp_debug( sprintf('No %s image exists with the specified database ID of %d.', get_class($O), $id) );
				return '';
			}
		}

		// Select image by index position in the list
		if ( false !== $index ){
			$keys = array_keys($O->images);
			if( isset($keys[ $index ]) && isset($O->images[ $keys[ $index ] ]) )
				$Image = $O->images[ $keys[ $index ] ];
			else {
				shopp_debug( sprintf('No %s image exists at the specified index position %d.', get_class($O), $id) );
				return '';
			}
		}

		// Use the current image pointer by default
		if ( ! isset($Image) ) $Image = current($O->images);

		if ( false !== $size ) $width = $height = $size;
		if ( ! $width ) $width = $_width;
		if ( ! $height ) $height = $_height;

		$lowest_quality = min(ImageSetting::$qualities);

		$scale   = $fit ? array_search( $fit, ImageAsset::$defaults['scaling'] ) : null;
		$sharpen = $sharpen ? max( $sharpen, ImageAsset::$defaults['sharpen'] ) : null;
		$quality = $quality ? max( $quality, $lowest_quality ) : null;

		if ( 'transparent' == strtolower($bg) ) $fill = -1;
		else $fill = $bg ? hexdec(ltrim($bg, '#')) : false;

		list($width_a, $height_a) = array_values($Image->scaled($width, $height, $scale));
		if ( 'original' == $size ) {
			$width_a  = $Image->width;
			$height_a = $Image->height;
		}
		if ( $width_a  === false ) $width_a = $width;
		if ( $height_a === false ) $height_a = $height;

		$alt       = esc_attr( empty($alt) ? (empty($Image->alt) ? $Image->name : $Image->alt) : $alt );
		$title     = empty($title) ? $Image->title : $title;
		$titleattr = empty($title) ? '' : ' title="' . esc_attr($title) . '"';
		$classes   = empty($class) ? '' : ' class="' . esc_attr($class) . '"';

		$src = ( 'original' == $size ) ? $Image->url() : $Image->url($width, $height, $scale, $sharpen, $quality, $fill);

		switch ( strtolower($property) ) {
			case 'id': return $Image->id; break;
			case 'url':
			case 'src': return $src; break;
			case 'title': return $title; break;
			case 'alt': return $alt; break;
			case 'width': return $width_a; break;
			case 'height': return $height_a; break;
			case 'class': return $class; break;
		}

		$img = '<img src="' . $src . '"' . $titleattr . ' alt="' . $alt . '" width="' . (int) $width_a . '" height="' . (int) $height_a . '" ' . $classes . ' />';

		if ( Shopp::str_true($zoom) )
			return '<a href="' . $Image->url() . '" class="' . $zoomfx . '" rel="product-' . $O->id . '"' . $titleattr . '>' . $img . '</a>';

		return $img;
	}

	/**
	 * Provides markup for a navigation breadcrumb list
	 *
	 * @api `shopp('storefront.breadcrumb')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The breadcrumb markup
	 **/
	public static function breadcrumb ( $result, $options, $O ) {
		$Shopp = Shopp::object();

		$defaults = array(
			'separator' => '&nbsp;&raquo; ',
			'depth'		=> 7,

			'wrap' 		=> '<ul class="breadcrumb">',
			'endwrap' 	=> '</ul>',
			'before'	=> '<li>',
			'after'		=> '</li>'

		);
		$options = array_merge($defaults, $options);
		extract($options);

		$linked = $before . '%2$s<a href="%3$s">%1$s</a>' . $after;
		$list = $before . '%2$s<span>%1$s</span>' . $after;

		$CatalogPage = shopp_get_page('catalog');

		$Storefront = ShoppStorefront();

		// Add the Store front page (aka catalog page)
		$breadcrumb = array( $CatalogPage->title() => Shopp::url(false, 'catalog') );

		if ( is_account_page() ) {
			$Page = shopp_get_page('account');

			$breadcrumb += array($Page->title() => Shopp::url(false, 'account'));

			$request = $Storefront->account['request'];
			if (isset($Storefront->dashboard[ $request ]))
				$breadcrumb += array($Storefront->dashboard[ $request ]->label => Shopp::url(false, 'account'));

		} elseif ( is_shopp_cart_page() ) {
			$Page        = shopp_get_page('cart');
			$breadcrumb += array($Page->title() => Shopp::url(false, 'cart'));
		} elseif ( is_shopp_checkout_page() ) {
			$Cart = shopp_get_page('cart');
			$Checkout    = shopp_get_page('checkout');
			$breadcrumb += array($Cart->title() => Shopp::url(false, 'cart'));
			$breadcrumb += array($Checkout->title() => Shopp::url(false, 'checkout'));
		} elseif ( is_shopp_confirm_page() ) {
			$Cart        = shopp_get_page('cart');
			$Checkout    = shopp_get_page('checkout');
			$Confirm     = shopp_get_page('confirm');
			$breadcrumb += array($Cart->title() => Shopp::url(false, 'cart'));
			$breadcrumb += array($Checkout->title() => Shopp::url(false, 'checkout'));
			$breadcrumb += array($Confirm->title() => Shopp::url(false, 'confirm'));
		} elseif ( is_shopp_thanks_page() ) {
			$Page        = shopp_get_page('thanks');
			$breadcrumb += array($Page->title() => Shopp::url(false, 'thanks'));
		} elseif ( is_shopp_taxonomy() ) {
			$taxonomy    = ShoppCollection()->taxonomy;
			$ancestors   = array_reverse(get_ancestors(ShoppCollection()->id, $taxonomy));
			foreach ( $ancestors as $ancestor ) {
				$term = get_term($ancestor, $taxonomy);
				$breadcrumb[ $term->name ] = get_term_link($term->slug, $taxonomy);
			}
			$breadcrumb[ shopp('collection', 'get-name') ] = shopp('collection', 'get-url');
		} elseif ( is_shopp_collection() ) {
			// collections
			$breadcrumb[ ShoppCollection()->name ] = shopp('collection', 'get-url');
		} elseif ( is_shopp_product() ) {
			$categories = get_the_terms(ShoppProduct()->id, ProductCategory::$taxon);
			if ( $categories ) {
				$term = array_shift($categories);
				$ancestors = array_reverse(get_ancestors($term->term_id, ProductCategory::$taxon));
				foreach ( $ancestors as $ancestor ) {
					$parent_term = get_term($ancestor, ProductCategory::$taxon);
					$breadcrumb[ $parent_term->name ] = get_term_link($parent_term->slug, ProductCategory::$taxon);
				}
				$breadcrumb[ $term->name ] = get_term_link($term->slug, $term->taxonomy);
			}
			$breadcrumb[ shopp('product.get-name') ] = shopp('product.get-url');
		}

		$names = array_keys($breadcrumb);
		$last = end($names);
		$trail = '';
		foreach ( $breadcrumb as $name => $link )
			$trail .= sprintf( ( $last == $name ? $list : $linked ), $name, ( empty($trail) ? '' : $separator ), $link);

		return $wrap . $trail . $endwrap;
	}

	/**
	 * Provides the business name of the store
	 *
	 * @api `shopp('storefront.business-name')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The business name
	 **/
	public static function business_name ( $result, $options, $O ) {
		return esc_html(shopp_setting('business_name'));
	}

	/**
	 * Provides the business address of the store
	 *
	 * @api `shopp('storefront.business-address')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The business address
	 **/
	public static function business_address ( $result, $options, $O ) {
		return esc_html(shopp_setting('business_address'));
	}

	/**
	 * Iterates over the categories in the store
	 *
	 * @api `shopp('storefront.categories')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if the next category exists, false otherwise
	 **/
	public static function categories ( $result, $options, $O ) {
		$null = null;
		if ( ! isset($O->_category_loop) ) {
			reset($O->categories);
			$current = current($O->categories);
			if ( false !== $current ) ShoppCollection($current);
			$O->_category_loop = true;
		} else {
			$next = next($O->categories);
			if ( false !== $next ) ShoppCollection($next);
		}

		if ( current($O->categories) !== false ) return true;
		else {
			unset($O->_category_loop);
			reset($O->categories);
			ShoppCollection($null);
			if ( is_a(ShoppStorefront()->Requested, 'ProductCollection') )
				ShoppCollection(ShoppStorefront()->Requested);
			return false;
		}
	}

	/**
	 * Provides markup for the current collection or sets the current collection context
	 *
	 * @api `shopp('storefront.category')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **controls**: `on` (on,off) Override the display of control elements in the `category.php` template
	 * - **id**: Specify a collection by database ID to display through `shopp('collection...')` tags
	 * - **load**: `off` (on,off) Load the collection into the working context without automatically rendering it with the `category.php` template
	 * - **name**: Specify a collection by name to display through `shopp('collection...')` tags
	 * - **order**: `title` (bestselling,highprice,lowprice,newest,oldest,random,title) Sets the sort order for products for sortable collections
	 * - **pagination**: `numeric` (numeric,alpha) Set the pagination type to numbers `numeric` or alphabetic `alpha` pagination
	 * - **reset**: This option is used by itself to reset the working collection context to the previous collection context or empty
	 * - **show**: `1000` Limit the number of products to show in the category display
	 * - **slug**: Specify a collection by slug to display through `shopp('collection...')` tags
	 * - **title**: Sets the title to be shown for the collection, overriding the collection's own title
	 * - **view**: (grid,list) Set the initial view of the category regardless of any shopper preference. The shopper can still change the view using view controls (if available) to change the view again.
	 * @param ShoppStorefront $O       The working object
	 * @return string The category markup
	 **/
	public static function category ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();
		$reset = null;
		if ( isset($options['name']) ) ShoppCollection( new ProductCategory($options['name'], 'name') );
		elseif ( isset($options['slug']) ) ShoppCollection( new ProductCategory($options['slug'], 'slug') );
		elseif ( isset($options['id']) ) ShoppCollection( new ProductCategory($options['id']) );

		if ( isset($options['reset']) )
			return is_a($Storefront->Requested, 'ProductCollection') ? ShoppCollection($Storefront->Requested) : ShoppCollection($reset);
		if ( isset($options['title']) ) ShoppCollection()->name = $options['title'];
		if ( isset($options['show']) ) ShoppCollection()->loading['limit'] = $options['show'];
		if ( isset($options['pagination']) ) ShoppCollection()->loading['pagination'] = $options['pagination'];
		if ( isset($options['order']) ) ShoppCollection()->loading['order'] = $options['order'];
		if ( isset($options['taxquery']) ) ShoppCollection()->loading['taxquery'] = $options['taxquery'];

		if ( isset($options['load']) ) return true;
		if ( isset($options['controls']) && ! Shopp::str_true($options['controls']) )
			ShoppCollection()->controls = false;
		if ( isset($options['view']) ) {
			if ( 'grid' == $options['view'] ) ShoppCollection()->view = 'grid';
			else ShoppCollection()->view = 'list';
		}

		ob_start();
		$templates = array('category.php', 'collection.php');
		$ids = array('slug', 'id');
		foreach ( $ids as $property ) {
			if ( isset(ShoppCollection()->$property) ) $id = ShoppCollection()->$property;
			array_unshift($templates, 'category-' . $id . '.php', 'collection-' . $id . '.php');
		}
		locate_shopp_template($templates, true);
		$content = ob_get_clean();

		// Reset the current collection to previously requested collection or empty it
		if ( is_a($Storefront->Requested, 'ProductCollection') ) ShoppCollection($Storefront->Requested);
		else ShoppCollection($reset);

		if ( isset($options['wrap']) && Shopp::str_true($options['wrap']) )
			$content = ShoppStorefront::wrapper($content);

		return $content;
	}

	/**
	 * Provides markup for a list of storefront categories
	 *
	 * @api `shopp('storefront.category-list')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **before**: ` ` Markup to add before the list
	 * - **after**: ` ` Markup to add after the list
	 * - **childof**: Display only the child categories of a category specified by the given term id
	 * - **class**: The class attribute specifies one or more class-names for the list
	 * - **default**: `Select category&hellip;` The default option label shown when the list is shown as a menu
	 * - **depth**: `0` Limit the sub-category depth to show
	 * - **dropdown**: `off` (on,off) Generate the list as a drop-down menu
	 * - **empty**: `No categories` The default label to show when there are not categories available for the store
	 * - **exclude**: A list of term IDs to exclude from the list (comma-separated)
	 * - **excludetree**: List of parent term IDs to exclude (comma-separated)
	 * - **hierarchy**: `on` (on,off) Format the list to show the heirarchical relationships of categories and sub-categories
	 * - **include**: 'List of term IDs to include in the category list (excluding everything else) (comma-separated)
	 * - **linkall**: `off` (on,off) Provide a link to empty categories
	 * - **parent**: `off` Show categories with given parent term ID
	 * - **products**: `off` Show the count of products in the category
	 * - **number**: The maximum number of terms to show
	 * - **orderby**: `name` (id,count,name,slug) Specifies the property to sort categories by
	 * - **order**: `ASC` (ASC,DESC) Direction to sort categories ascending or descending
	 * - **showall**: `off` (on,off) Show all categories, empty or not
	 * - **section**: `off` (on,off) Render a category section (a branch of categories) rather than all categories
	 * - **sectionterm**: `off` (on,off) Term ID of the section to show
	 * - **selected**: The category term ID to auto-select when using the drop-down menu
	 * - **smart**: (before,after) Include smart collections either before or after the categories list
	 * - **title**: The title label to show above the list
	 * - **title_before**: `<h3 class="shopp-categories-title">` Markup to add before the title label
	 * - **title_after**: `</h3>` Markup to add after the title label
	 * - **taxonomy**: `shopp_category` The taxonomy to use
	 * - **wraplist**: `on` (on,off) Wrap list in <ul></ul> (when dropdown is off)
	 * @param ShoppStorefront $O       The working object
	 * @return string The list markup
	 **/
	public static function category_list ( $result, $options, $O ) {

		$defaults = array(

			'after'        => '',										// After list
			'before'       => '',										// Before list
			'childof'      => 0,										// Only child categories of given term id
			'class'        => '',										// CSS classes for the container
			'default'      => Shopp::__('Select category&hellip;'),
			'depth'        => 0,										// Depth level limit
			'dropdown'     => false,									// Render as a dropdown instead of list
			'empty'        => Shopp::__('No categories'),
			'exclude'      => '',										// List of term ids to exclude (comma-separated)
			'excludetree'  => '',										// List of parent term ids to exclude (comma-separated)
			'hierarchy'    => true,										// Show hierarchy
			'include'      => '',										// List of term ids to include (comma-separated)
			'linkall'      => false,									// Link to empty categories
			'parent'       => false,									// Show categories with given parent term id
			'products'     => false,									// Show products count
			'number'       => '',										// The maximum number of terms
			'orderby'      => 'name',									// Property to sort categories by (id, count, name, slug)
			'order'        => 'ASC',									// Direction to sort categories ascending or descending: (ASC, DESC)
			'showall'      => false,									// Show all categories, empty or not
			'section'      => false,									// Section (or branch of categories) to render
			'sectionterm'  => false, 									// Term id of the section to show
			'selected'     => false,									// Selected term_id to auto-select option when dropdown=true
			'smart'        => false,									// Include smart collections either before or after other collections (before, after)
			'title'        => '',										// Title/label to show above the list/menu
			'title_after'  => '</h3>',									// After title/label
			'title_before' => '<h3 class="shopp-categories-title">',	// Before title/label
			'taxonomy'     => ProductCategory::$taxon,					// Taxonomy to use
			'wraplist'     => true,										// Wrap list in <ul></ul> (only works when dropdown=false)

			// Deprecated options
			'linkcount' => false,
			'showsmart' => false,
		);

		$options = array_merge($defaults, $options);

		// Deprecated linkcount support
		if( $options['linkcount'] ) $options['products'] = true;

		$options['style'] = '';
		if ( Shopp::str_true($options['dropdown']) )
			$options['style'] = 'dropdown';
		elseif ( Shopp::str_true($options['hierarchy']) || Shopp::str_true($options['wraplist']) )
			$options['style'] = 'list';

		if ( ! empty($options['showsmart']) && empty($options['smart']) )
			$options['smart'] = $options['showsmart'];

		extract($options, EXTR_SKIP);

		if ( ! taxonomy_exists($taxonomy) )
			return false;

		$baseparent = 0;
		if ( Shopp::str_true($section) ) {

			if ( empty(ShoppCollection()->id) && empty($sectionterm) ) return false;

			if ( empty($sectionterm) )                // If sectionterm option is not specified,
				$sectionterm = ShoppCollection()->id; // use the current collection as target

			if ( ! empty(ShoppCollection()->id) && 0 == ShoppCollection()->parent )
				$childof = $sectionterm;
			else {
				$ancestors = get_ancestors($sectionterm, $options['taxonomy']);
				$childof = end($ancestors);
			}
		}

		// If hierarchy, use depth provided, otherwise flat
		$options['depth'] = $hierarchy ? $depth : -1;

		$lists = array('exclude', 'excludetree', 'include');
		foreach ( $lists as $values )
			if ( false !== strpos($$values, ',') )
				$$values = explode(',', $$values);

		$terms = get_terms( $options['taxonomy'], array(
			'hide_empty'   => ! $showall,
			'child_of'     => $childof,
			'fields'       => 'all',
			'orderby'      => $orderby,
			'order'        => $order,
			'exclude'      => $exclude,
			'exclude_tree' => $excludetree,
			'include'      => $include,
			'number'       => $number,
		));

		if ( empty( $class ) )
			$class = $taxonomy;

		$collections = self::_collections();

		switch ( $smart ) {
			case 'before': $terms = array_merge($collections, $terms); break;
			case 'after':  $terms = array_merge($terms, $collections); break;
		}

		if ( empty($terms) ) return '';

		if ( 'dropdown' == $style ) return self::_category_menu($terms, $depth, $options);
		else return self::_category_list($terms, $depth, $options);
	}

	/**
	 * Helper to load smart collections for category listings
	 *
	 * @internal
	 * @since 1.3
	 *
	 * @return array List of smart collections in term-compatible objects
	 **/
	private static function _collections () {
		$Shopp = Shopp::object();

		$collections = array();
		foreach ( $Shopp->Collections as $slug => $CollectionClass ) {
			if ( ! get_class_property($CollectionClass, '_menu') ) continue;
            
            // Stubs out a WP_Term compatible StdClass object
			$Collection = new StdClass;
			$Collection->term_id = 0;
			$Collection->name = call_user_func(array($CollectionClass, 'name'));
			$Collection->slug = $slug;
			$Collection->term_group = 0;
			$Collection->taxonomy = get_class_property('SmartCollection','taxon');
			$Collection->description = '';
			$Collection->parent = 0;
            $Collection->query_var = ''; //$Collection->taxonomy;
			$collections[] = $Collection;
		}

		return $collections;
	}

	/**
	 * Builds the category dropdown menu markup
	 *
	 * @internal
	 * @since 1.3.1
	 *
	 * @param array $terms The list of terms to use
	 * @param int $depth The depth to render
	 * @param array $options The list of options
	 * @return string The drop-down menu markup
	 **/
	private static function _category_menu ( $terms, $depth, $options ) {
		extract($options, EXTR_SKIP);
		$Categories = new ShoppCategoryDropdownWalker;

		$menu = '';

		if ( ! empty($title) ) $menu .= $title;
		$classes = array($class);
		$classes[] = 'shopp-categories-menu';
		$class = empty($classes) ? '' : ' class="' . trim(join(' ', $classes)) . '"';
		$menu .= '<form action="/" method="get" class="category-list-menu"><select name="shopp_cats" ' . $class . '>';
		if ( ! empty($default) )
			$menu .= '<option value="">' . $default . '</option>';

		$menu .= $Categories->walk($terms, $depth, $options);

		$menu .= '</select></form>';

		return $before . $menu . $after;
	}

	/**
	 * Builds markup for an unordered list of categories
	 *
	 * @internal
	 * @since 1.3.1
	 *
	 * @param array $terms The list of terms to use
	 * @param int $depth The depth to render
	 * @param array $options The list of options
	 * @return string The drop-down menu markup
	 **/
	private static function _category_list ( $terms, $depth, $options ) {
		extract($options, EXTR_SKIP);
		$Categories = new ShoppCategoryWalker;

		$classes = array($class);
		$classes[] = 'shopp-categories-menu';
		$class = empty($classes) ? '' : ' class="' . trim(join(' ', $classes)) . '"';

		$list = '';
		if ( ! empty($title) ) $list .= $title_before . $title . $title_after;

		if ( Shopp::str_true($wraplist) ) $list .= '<ul' . $class . '>';

		$list .= $Categories->walk($terms, $depth, $options);

		if ( Shopp::str_true($wraplist) ) $list .= '</ul>';
		return $before . $list . $after;
	}

	/**
	 * Provides the currency code set for the store
	 *
	 * @api `shopp('storefront.currency')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The currency code
	 **/
	public static function currency ( $result, $options, $O ) {
		$baseop = shopp_setting('base_operations');
		$currency = $baseop['currency']['code'];
		return $currency;
	}

	/**
	 * Provides notice and error messages
	 *
	 * @api `shopp('storefront.errors')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **before**: ` ` Markup to add before the list
	 * - **after**: ` ` Markup to add after the list
	 * @param ShoppStorefront $O       The working object
	 * @return string The errors list
	 **/
	public static function errors ( $result, $options, $O ) {

		$Errors = ShoppErrorStorefrontNotices();
		if ( ! $Errors->exist() ) return false;

		$defaults = array(
			'before' => '<li>',
			'after'  => '</li>'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$result = '';
		while ( $Errors->exist() )
			$result .=  $before . $Errors->message() . $after;

		return $result;
	}

	/**
	 * Provides the type of catalog page being displayed
	 *
	 * Can be one of storefront, category, product
	 *
	 * @api `shopp('storefront.type')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The content type
	 **/
	public static function type ( $result, $options, $O ) {
		return $O->type;
	}

	/**
	 * Checks if the storefront has any categories
	 *
	 * @api `shopp('storefront.has-categories')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **showsmart**: (before,after) Include smart collections before or after the categories
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if categories exist, false otherwise
	 **/
	public static function has_categories ( $result, $options, $O ) {
		$defaults = array(
			'showsmart' => false
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( empty($O->categories) ) $O->load_categories($options, $showsmart);
		else { // Make sure each entry is a valid ProductCollection to prevent fatal errors @bug #2017
			foreach ( $O->categories as $id => $term ) {
				if (  $Category instanceof ProductCollection ) continue;
				$ProductCategory = new ProductCategory();
				$ProductCategory->populate($term);
				$O->categories[ $id ] = $ProductCategory;
			}
			reset($O->categories);
			return true;
		}
		reset($O->categories);
		return ( count($O->categories) > 0 );
	}

	/**
	 * Checks if the current page is the account page
	 *
	 * @api `shopp('storefront.is-account')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the account page, false otherwise
	 **/
	public static function is_account ( $result, $options, $O ) {
		return is_shopp_account_page();
	}

	/**
	 * Checks if the current page is the cart page
	 *
	 * @api `shopp('storefront.is-cart')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the cart page, false otherwise
	 **/
	public static function is_cart ( $result, $options, $O ) {
		return is_shopp_cart_page();
	}

	/**
	 * Checks if the current page is the catalog landing page
	 *
	 * @api `shopp('storefront.is-catalog')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the catalog page, false otherwise
	 **/
	public static function is_catalog ( $result, $options, $O ) {
		return is_shopp_catalog_page();
	}

	/**
	 * Checks if the current page is the checkout page
	 *
	 * @api `shopp('storefront.is-checkout')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the checkout page, false otherwise
	 **/
	public static function is_checkout ( $result, $options, $O ) {
		return is_shopp_checkout_page();
	}

	/**
	 * Checks if the current page is a collection page
	 *
	 * @api `shopp('storefront.is-collection')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the collection page, false otherwise
	 **/
	public static function is_collection ( $result, $options, $O ) {
		return is_shopp_collection();
	}

	/**
	 * Checks if the current page is the catalog on the front page of the site
	 *
	 * @api `shopp('storefront.is-frontpage')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the frontpage page, false otherwise
	 **/
	public static function is_frontpage ( $result, $options, $O ) {
		return is_shopp_catalog_frontpage();
	}

	/**
	 * Checks if the current page is a product page
	 *
	 * @api `shopp('storefront.is-product')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the product page, false otherwise
	 **/
	public static function is_product ( $result, $options, $O ) {
		return is_shopp_product();
	}

	/**
	 * Checks if the current page is a taxonomy page
	 *
	 * @api `shopp('storefront.is-taxonomy')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if it is the taxonomy page, false otherwise
	 **/
	public static function is_taxonomy ( $result, $options, $O ) {
		return is_shopp_taxonomy();
	}

	/**
	 * Provides markup for a category sort order selection widget
	 *
	 * The widget can be a drop-down menu or an unordered list of links
	 *
	 * @api `shopp('storefront.orderby-list')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The order-by markup
	 **/
	public static function orderby_list ( $result, $options, $O ) {

		$Collection = ShoppCollection();
		$Storefront = ShoppStorefront();

		// Some internals can suppress this control
		if ( isset($Collection->controls) ) return false;
		if ( isset($Collection->loading['order']) || isset($Collection->loading['sortorder']) ) return false;

		$defaultsort = array(
			'title',
			shopp_setting('default_product_order'),
			isset($Storefront->browsing['sortorder']) ? $Storefront->browsing['sortorder'] : false
		);
		foreach ( $defaultsort as $setting )
			if ( ! empty($setting)) $default = $setting;

		// Setup defaults
		$options = wp_parse_args($options, array(
			'dropdown' => false,
			'default'  => $default,
			'title'    => ''
		));
		extract($options, EXTR_SKIP);

		// Get the sort option labels
		$menuoptions = ProductCategory::sortoptions();
		// Don't show custom product order for smart categories
		if ( $Collection->smart ) unset($menuoptions['custom']);

		$_ = array();
		$request = $_SERVER['REQUEST_URI'];
		
		if ( Shopp::str_true($dropdown) ) {
			$_[] = $title;
			$_[] = '<form action="' . esc_url($request) . '" method="get" id="shopp-' . $Collection->slug . '-orderby-menu">';

			if ( '' == get_option('permalink_structure') ) {
				foreach ($_GET as $key => $value)
					if ( 'sort' != $key ) $_[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
			}

			$_[] = '<select name="sort" class="shopp-orderby-menu ' . ( isset($class) ? esc_attr($class) : '' ) . '">';			
			$_[] = menuoptions($menuoptions, $default, true);
			$_[] = '</select>';
			$_[] = '</form>';
		} else {
			foreach ( $menuoptions as $value => $label ) {
				$href  = esc_url(add_query_arg(array('sort' => $value), $request));
				$class = ( $default == $value ? ' class="current"' : '' );
				$_[]   = '<li><a href="' . $href . '"' . $class . '>' . $label . '</a></li>';
			}
		}

		return join('',$_);
	}

	/**
	 * Provides markup for the current product or sets the current product context
	 *
	 * @api `shopp('storefront.product')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **id**: Specify a product by custom post type database ID to display through `shopp('product...')` tags
	 * - **load**: `off` (on,off,next,previous) `on` oads the product into the working context without automatically rendering it with the `category.php` template, `next` loads the next product in the collection into the working context, `previous` loads the previous product in the collection
	 * - **name**: Specify a product by name to display through `shopp('product...')` tags
	 * - **reset**: This option is used by itself to reset the working product context to the previously loaded product context or empty
	 * - **slug**: Specify a product by slug to display through `shopp('product...')` tags
	 * @param ShoppStorefront $O       The working object
	 * @return string The category markup
	 **/
	public static function product ( $result, $options, $O ) {
		$Shopp = Shopp::object();
		$Storefront = ShoppStorefront();

		if ( isset($options['name']) ) ShoppProduct(new ShoppProduct($options['name'],'name'));
		elseif ( isset($options['slug']) ) ShoppProduct(new ShoppProduct($options['slug'],'slug'));
		elseif ( isset($options['id']) ) ShoppProduct(new ShoppProduct($options['id']));

		if ( isset($options['reset']) )
			return ( $Storefront->Requested && is_a($Storefront->Requested, 'ShoppProduct') ? ShoppProduct($Storefront->Requested) : false );

		if ( isset(ShoppProduct()->id) && isset($Shopp->Category->slug) ) {
			$Category = clone($Shopp->Category);

			if ( isset($options['load']) ) {
				if ( 'next' == $options['load'] ) ShoppProduct($Category->adjacent_product(1));
				elseif ( 'previous' == $options['load'] ) ShoppProduct($Category->adjacent_product(-1));
			} else {
				if ( isset($options['next']) && Shopp::str_true($options['next']) )
					ShoppProduct($Category->adjacent_product(1));
				elseif ( isset($options['previous']) && Shopp::str_true($options['previous']) )
					ShoppProduct($Category->adjacent_product(-1));
			}
		}

		if ( isset($options['load']) ) return true;

		$Product = ShoppProduct();

		// Expand base template file names to support product-id and product-slug specific versions
		// product-id templates will be highest priority, followed by slug versions and the generic names
		$templates = isset($options['template']) ? $options['template'] : array('product.php');
		if ( ! is_array($templates) ) $templates = explode(',', $templates);

		$idslugs = array();
		$reversed = array_reverse($templates);
		foreach ( $reversed as $template ) {
			list($basename, $php) = explode('.', $template);
			if ( ! empty($Product->slug) ) array_unshift($idslugs, "$basename-$Product->slug.$php");
			if ( ! empty($Product->id) ) array_unshift($idslugs, "$basename-$Product->id.$php");
		}
		$templates = array_merge($idslugs, $templates);

		ob_start();
		locate_shopp_template($templates, true);
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Provides markup for a unordered list widget of recent shoppers
	 *
	 * @api `shopp('storefront.recent-shoppers')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **abbr**: `firstname` (firstname,lastname) Choose which name to abbreviate to maintain customer privacy
	 * - **city**: `on` (on,off) Include the customer's city name
	 * - **state**: `on` (on,off) Include the customer's state abbreviation
	 * - **avatar**: `on` (on,off) Include the customer's avatar
	 * - **size**: `48` Set the size of the customer avatar shown
	 * - **show**: `5` Set the limit of the number of recent shoppers to show
	 * @param ShoppStorefront $O       The working object
	 * @return string The markup for the widget
	 **/
	public static function recent_shoppers ( $result, $options, $O ) {
		$defaults = array(
			'abbr' => 'firstname',
			'city' => true,
			'state' => true,
			'avatar' => true,
			'size' => 48,
			'show' => 5
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$pt = ShoppDatabaseObject::tablename(ShoppPurchase::$table);
		$shoppers = sDB::query("SELECT firstname,lastname,email,city,state FROM $pt AS pt GROUP BY customer ORDER BY created DESC LIMIT $show",'array');

		if ( empty($shoppers) ) return '';

		$_ = array();
		$_[] = '<ul>';
		foreach ( $shoppers as $shopper ) {
			if ( '' == $shopper->firstname . $shopper->lastname ) continue;
			if ( 'lastname' == $abbr ) $name = "$shopper->firstname " . $shopper->lastname{0} . ".";
			else $name = $shopper->firstname{0} . ". $shopper->lastname";

			$img = '';
			if ( $avatar ) $img = get_avatar($shopper->email, $size, '', $name);

			$loc = '';
			if ( $state || $province ) $loc = $shopper->state;
			if ( $city ) $loc = "$shopper->city, $loc";

			$_[] = "<li><div>$img</div>$name <em>$loc</em></li>";
		}
		$_[] = '</ul>';

		return join('', $_);
	}

	/**
	 * Provides markup for the search form input to change a search query to perform a store catalog search
	 *
	 * @api `shopp('storefront.search')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **type**: `hidden` (checkbox,radio,menu,hidden) The input type to generate
	 * - **option**: `shopp` (blog,shopp) Specifies the input option value to use for an `radio` input **type**
	 * - **blog_option**: `Search the blog` The label to use for the blog search option
	 * - **shop_option**: `Search the shop` The label to use for the shop search option
	 * - **label_before**: Sets the text label to appear before the input
	 * - **label_after**: Sets the text label to apper after the input
	 * - **checked**: Sets the checked attribute for the `checkbox` input **type**
	 * @param ShoppStorefront $O       The working object
	 * @return string The input markup
	 **/
	public static function search ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();
		global $wp;

		$defaults = array(
			'type'         => 'hidden',
			'option'       => 'shopp',
			'blog_option'  => Shopp::__('Search the blog'),
			'shop_option'  => Shopp::__('Search the shop'),
			'label_before' => '',
			'label_after'  => '',
			'checked'      => false
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$searching = is_search(); // Flag when searching (the blog or shopp)
		$shopsearch = ( $Storefront !== false && $Storefront->searching ); // Flag when searching shopp

		$allowed = array('accesskey', 'alt', 'checked', 'class', 'disabled', 'format', 'id',
			'minlength', 'maxlength', 'readonly', 'required', 'size', 'src', 'tabindex', 'title', 'value');

		$options['value'] = ( 'shopp' == $option );

		// Reset the checked option
		unset($options['checked']);

		// If searching the blog, check the non-store search option
		if ( $searching && ! $shopsearch && $option != 'shopp' ) $options['checked'] = 'checked';

		// If searching the storefront, mark the store search option
		if ( $shopsearch && $option == 'shopp' ) $options['checked'] = 'checked';

		// Override any other settings with the supplied default 'checked' option
		if ( ! $searching && $checked ) $options['checked'] = $checked;

		switch ( $type ) {
			case 'checkbox':
				$input =  '<input type="checkbox" name="s_cs"' . inputattrs($options, $allowed) . ' />';
				break;
			case 'radio':
				$input =  '<input type="radio" name="s_cs"' . inputattrs($options, $allowed) . ' />';
				break;
			case 'menu':
				$allowed = array('accesskey', 'alt', 'class', 'disabled', 'format', 'id',
					'readonly', 'required', 'size', 'tabindex', 'title');

				$input = '<select name="s_cs"' . inputattrs($options, $allowed) . '>';
				$input .= '<option value="">' . $blog_option . '</option>';
				$input .= '<option value="1"' . ( $shopsearch || ( ! $searching && 'shopp' == $option ) ? ' selected="selected"' : '' ) . '>' . $shop_option . '</option>';
				$input .= '</select>';
				break;
			default:
				$allowed = array('alt', 'class', 'disabled', 'format', 'id', 'readonly', 'title', 'value');
				$input =  '<input type="hidden" name="s_cs"' . inputattrs($options, $allowed) . ' />';
				break;
		}

		$before = ( ! empty($label_before) ) ? '<label>' . $label_before : '<label>';
		$after = ( ! empty($label_after) ) ? $label_after . '</label>' : '</label>';
		return $before . $input . $after;
	}

	/**
	 * Provides markup for the product search form
	 *
	 * @api `shopp('storefront.search-form')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The form markup
	 **/
	public static function search_form ( $result, $options, $O ) {
		ob_start();
		get_search_form();
		$content = ob_get_clean();

		preg_match('/^(.*?<form[^>]*>)(.*?)(<\/form>.*?)$/is', $content, $_);
		list($all, $open, $content, $close) = $_;

		$markup = array(
			$open,
			$content,
			'<div><input type="hidden" name="s_cs" value="true" /></div>',
			$close
		);

		return join('', $markup);
	}

	/**
	 * Provides markup for displaying one or more products in an aside widget
	 *
	 * @api `shopp('storefront.side-product')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **source**: `product` (product,category) The source of the products to display
	 * - **product**: The product custom post-type database ID or list of IDs (comma-separated)
	 * - **category**: The taxonomy term ID or list of IDs (comma-separated)
	 * - **load**: (on) When set, this option will load the first product (or category) into the working product or category context
	 * @param ShoppStorefront $O       The working object
	 * @return string The widget markup
	 **/
	public static function side_product ( $result, $options, $O ) {
		$Shopp = Shopp::object();

		$content = false;
		$source = isset($options['source']) ? $options['source'] : 'product';
		if ( $source == 'product' && isset($options['product']) ) {

			 // Save original requested product
			if ( $Shopp->Product ) $Requested = $Shopp->Product;
			
			$products = explode(',', $options['product']);
			
			if ( ! is_array($products) ) $products = array($products);
			
			foreach ( $products as $product ) {
				$product = trim($product);
				if ( empty($product) ) continue;
			
				if ( preg_match('/^\d+$/', $product) )
					$Shopp->Product = new ShoppProduct($product);
				else $Shopp->Product = new ShoppProduct($product, 'slug');

				if ( empty($Shopp->Product->id) ) continue;
			
				if ( isset($options['load']) ) return true;
			
				ob_start();
				locate_shopp_template(array('sideproduct-' . $Shopp->Product->id . '.php', 'sideproduct.php'), true);
				$content .= ob_get_clean();
			}
			 // Restore original requested Product
			if ( ! empty($Requested) ) $Shopp->Product = $Requested;
			else $Shopp->Product = false;
		}

		if ( $source == 'category' && isset($options['category']) ) {
			 // Save original requested category
			if ( $Shopp->Category ) $Requested = $Shopp->Category;
			if ( $Shopp->Product ) $RequestedProduct = $Shopp->Product;
			
			if (empty($options['category'])) return false;

			if ( in_array($options['category'], array_keys($Shopp->Collections)) ) {
				$Category = ShoppCatalog::load_collection($options['category'], $options);
				ShoppCollection($Category);
			} elseif ( intval($options['category']) > 0 ) { // By ID
				ShoppCollection( new ProductCategory($options['category']) );
			} else {
				ShoppCollection( new ProductCategory($options['category'], 'slug') );
			}

			if ( isset($options['load']) ) return true;

			$options['load'] = array('coverimages');
			ShoppCollection()->load($options);

			$template = locate_shopp_template(array('sideproduct-' . $Shopp->Category->slug . '.php', 'sideproduct.php'));
			ob_start();
			foreach ( ShoppCollection()->products as &$product ) {
				ShoppProduct($product);
				load_template($template, false);
			}
			$content = ob_get_clean();

			 // Restore original requested category
			if ( ! empty($Requested) ) $Shopp->Category = $Requested;
			else $Shopp->Category = false;
			
			if ( ! empty($RequestedProduct) ) $Shopp->Product = $RequestedProduct;
			else $Shopp->Product = false;
		}

		return $content;
	}

	/**
	 * Provides markup for the Tag Products smart collection that displays products with specified tags
	 *
	 * @api `shopp('storefront.tag-products')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **tag**: Specifies the tag (or tags) used to find products (comma-separated)
	 * - **title**: Specifies the title to be shown for the category layout overriding the default category title.
	 * - **controls**: `on` Overrides the display of “control” elements in the category.php template that would otherwise be shown. Used to highlight a selection of products in a category without all of the extra category controls.
	 * - **show**: Limits the number of products to show in the category display. Without a limit, this category will show all the products in your catalog (no matter what category they are in).
	 * - **load**: `off` Loads the collection into the working context for use with `shopp('collection...')` tags
	 * - **pagination**: `numeric` (numeric,alpha) Set to alpha this option will use alphabetic pagination, otherwise it will default to numeric.
	 * - **order**: `bestselling` (bestselling,highprice,lowprice,newest,oldest,random,title) Set the sort order for the collection
	 * @param ShoppStorefront $O       The working object
	 * @return void
	 **/
	public static function tag_products ( $result, $options, $O ) {
		ShoppCollection( new TagProducts($options) );
		return self::category( $result, $options, $O );
	}

	/**
	 * Provides markup for a tag cloud widget
	 *
	 * A tag cloud provides a list of tag terms whose typeface size
	 * is weighted according to the tag's usage popularity
	 *
	 * @api `shopp('storefront.tag-cloud')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **orderby**: `name` (name,count) Order the tags by name or by the use count
	 * - **order**: `DESC` (ASC,DESC) Set the direction to sort the tags: ascending or descending
	 * - **number**: `45` Sets the maximum number of tags to show
	 * - **levels**: `7` Sets the maximum number of popularity levels to render
	 * - **format**: `list` (list,inline) Format as an unordered list or an inline list of tags
	 * - **link**: `view` (view,edit) Changes the link to link to view the products in the tag collection or edit the tag
	 * @param ShoppStorefront $O       The working object
	 * @return string The tag cloud markup
	 **/
	public static function tag_cloud ( $result, $options, $O ) {
		$defaults = array(
			'orderby' => 'name',
			'order'   => false,
			'number'  => 45,
			'levels'  => 7,
			'format'  => 'list',
			'link'    => 'view'
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$tags = get_terms( ProductTag::$taxon, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $number) );

		if ( empty($tags) ) return false;
		
		if ( ! empty($exclude) ) {
			$excludes = explode(',', $exclude);
			
			foreach ( $tags as $key => $tag ) {
				if ( in_array($tag->name, $excludes) )
					unset($tags[ $key ]);
			}	
		}

		$min = $max = false;
		foreach ( $tags as &$entry ) {
			$min = ! $min ? $entry->count : min($min, $entry->count);
			$max = ! $max ? $entry->count : max($max, $entry->count);

			$link_function = ( 'edit' == $link ? 'get_edit_tag_link' : 'get_term_link');
			$entry->link = $link_function(intval($entry->term_id), ProductTag::$taxon);
		}

		// Sorting
		$sorted = apply_filters( 'tag_cloud_sort', $tags, $options );
		if ( $sorted != $tags  ) $tags = &$sorted;
		else {
			if ( 'RAND' == $order ) shuffle($tags);
			else {
				if ( 'name' == $orderby )
					usort( $tags, function ( $a, $b ) {
						return strnatcasecmp( $a->name, $b->name );
					} );
				else
					usort( $tags, function ( $a, $b ) {
						return ($a->count > $b->count);
					} );

				if ( 'DESC' == $order ) $tags = array_reverse( $tags, true );
			}
		}

		// Markup
		if ( 'inline' == $format ) $markup = '<div class="shopp tagcloud">';
		if ( 'list' == $format ) $markup = '<ul class="shopp tagcloud">';

		foreach ( (array)$tags as $tag ) {

			$level = floor( (1 - $tag->count / $max) * $levels )+1;
			if ( 'list' == $format ) $markup .= '<li class="level-' . $level . '">';
			$markup .= '<a href="' . esc_url($tag->link) . '">' . $tag->name . '</a>';
			if ( 'list' == $format ) $markup .= '</li> ';

		}
		if ( 'list' == $format ) $markup .= '</ul>';
		if ( 'inline' == $format ) $markup .= '</div>';

		return $markup;
	}

	/**
	 * Provides the URL for the storefront catalog page
	 *
	 * @api `shopp('storefront.url')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The store URL
	 **/
	public static function url ( $result, $options, $O ) {
		return Shopp::url(false, 'catalog');
	}

	/**
	 * Provides markup for the view selector buttons widget
	 *
	 * @api `shopp('storefront.views')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The widget markup
	 **/
	public static function views ( $result, $options, $O ) {
		$Shopp = Shopp::object();
		if ( isset($Shopp->Category->controls) ) return false;
		
		$string = '';
		$string .= '<ul class="views">';
		
		if ( isset($options['label']) ) $string .= '<li>' . $options['label'] . '</li>';
		
		$string .= '<li><button type="button" class="grid"><span></span></button></li>';
		$string .= '<li><button type="button" class="list"><span></span></button></li>';
		$string .= '</ul>';
		
		return $string;
	}

	/**
	 * Sets the default options for the image viewer zoom effect used on the current page
	 *
	 * @api `shopp('storefront.zoom-options')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **transition**: `elastic` The transition type. Can be set to ‘elastic’, ‘fade’, or ‘none’.
	 * - **speed**: `350` Sets the speed of the fade and elastic transitions, in milliseconds.
	 * - **href**: `false` This can be used as an alternative anchor URL or to associate a URL for non-anchor elements such as images or form buttons. Example: $('h1').colorbox({href:'welcome.html'})
	 * - **title**: `false` This can be used as an anchor title alternative for ColorBox.
	 * - **rel**: `false` This can be used as an anchor rel alternative for ColorBox. This allows the user to group any combination of elements together for a gallery, or to override an existing rel so elements are not grouped together. Example: $('#example a').colorbox({rel:'group1'}) Note: The value can also be set to ‘nofollow’ to disable grouping.
	 * - **width**: `false` Set a fixed total width. This includes borders and buttons. Example: ‘100%’, ‘500px’, or 500
	 * - **height**: `false` Set a fixed total height. This includes borders and buttons. Example: ‘100%’, ‘500px’, or 500
	 * - **innerWidth**: `false` This is an alternative to ‘width’ used to set a fixed inner width. This excludes borders and buttons. Example: ‘50%’, ‘500px’, or 500
	 * - **innerHeight**: `false` This is an alternative to ‘height’ used to set a fixed inner height. This excludes borders and buttons. Example: ‘50%’, ‘500px’, or 500
	 * - **initialWidth**: `300` Set the initial width, prior to any content being loaded.
	 * - **initialHeight**: `100` Set the initial height, prior to any content being loaded.
	 * - **maxWidth**: `false` Set a maximum width for loaded content. Example: ‘100%’, 500, ‘500px’
	 * - **maxHeight**: `false` Set a maximum height for loaded content. Example: ‘100%’, 500, ‘500px’
	 * - **scalePhotos**: `true` If ‘true’ and if maxWidth, maxHeight, innerWidth, innerHeight, width, or height have been defined, ColorBox will scale photos to fit within the those values.
	 * - **scrolling**: `true` If ‘false’ ColorBox will hide scrollbars for overflowing content. This could be used on conjunction with the resize method (see below) for a smoother transition if you are appending content to an already open instance of ColorBox.
	 * - **iframe**: `false` If ‘true’ specifies that content should be displayed in an iFrame.
	 * - **inline**: `false` If ‘true’ a jQuery selector can be used to display content from the current page. Example: $('#inline').colorbox({inline:true, href:'#myForm'});
	 * - **html**: `false` This allows an HTML string to be used directly instead of pulling content from another source (ajax, inline, or iframe). Example: $.colorbox({html:'<p>Hello</p>'});
	 * - **photo**: `false` If true, this setting forces ColorBox to display a link as a photo. Use this when automatic photo detection fails (such as using a url like ‘photo.php’ instead of ‘photo.jpg’, ‘photo.jpg#1′, or ‘photo.jpg?pic=1′)
	 * - **opacity**: `0`85. The overlay opacity level. Range: 0 to 1.
	 * - **open**: `false` If true, the lightbox will automatically open with no input from the visitor.
	 * - **returnFocus**: `true` If true, focus will be returned when ColorBox exits to the element it was launched from.
	 * - **preloading**: `true` Allows for preloading of ‘Next’ and ‘Previous’ content in a shared relation group (same values for the ‘rel’ attribute), after the current content has finished loading. Set to ‘false’ to disable.
	 * - **overlayClose**: `true` If false, disables closing ColorBox by clicking on the background overlay.
	 * - **escKey**: `true` If false, will disable closing colorbox on esc key press.
	 * - **arrowKey**: `true` If false, will disable the left and right arrow keys from navigating between the items in a group.
	 * - **loop**: `true` If false, will disable the ability to loop back to the beginning of the group when on the last element.
	 * - **slideshow**: `false` If true, adds an automatic slideshow to a content group / gallery.
	 * - **slideshowSpeed**: `2500` Sets the speed of the slideshow, in milliseconds.
	 * - **slideshowAuto**: `true` If true, the slideshow will automatically start to play.
	 * - **slideshowStart**: `start`slideshow. Text for the slideshow start button.
	 * - **slideshowStop**: `stop`slideshow. Text for the slideshow stop button
	 * - **previous**: `previous` Text for the previous button in a shared relation group (same values for ‘rel’ attribute).
	 * - **next**: `next` Text for the next button in a shared relation group (same values for ‘rel’ attribute).
	 * - **close**: `close` Text for the close button. The ‘Esc’ key will also close ColorBox.
	 * - **current**: `image`%s of %s. Text format for the content group / gallery count. {current} and {total} are detected and replaced with actual numbers while ColorBox runs.
	 * - **onOpen**: `false` Callback that fires right before ColorBox begins to open.
	 * - **onLoad**: `false` Callback that fires right before attempting to load the target content.
	 * - **onComplete**: `false` Callback that fires right after loaded content is displayed.
	 * - **onCleanup**: `false` Callback that fires at the start of the close process.
	 * - **onClosed**: `false` Callback that fires once ColorBox is closed.
	 * @param ShoppStorefront $O       The working object
	 * @return void
	 **/
	public static function zoom_options ( $result, $options, $O ) {
		$defaults = array(				// Colorbox 1.3.15
			'transition'        => 'elastic',	// The transition type. Can be set to 'elastic', 'fade', or 'none'.
			'speed'             => 350,			// Sets the speed of the fade and elastic transitions, in milliseconds.
			'href'              => false,		// This can be used as an alternative anchor URL or to associate a URL for non-anchor elements such as images or form buttons. Example: $('h1').colorbox({href:'welcome.html'})
			'title'             => false,		// This can be used as an anchor title alternative for ColorBox.
			'rel'               => false,		// This can be used as an anchor rel alternative for ColorBox. This allows the user to group any combination of elements together for a gallery, or to override an existing rel so elements are not grouped together. Example: $('#example a').colorbox({rel:'group1'}) Note: The value can also be set to 'nofollow' to disable grouping.
			'width'             => false,		// Set a fixed total width. This includes borders and buttons. Example: '100%', '500px', or 500
			'height'            => false,		// Set a fixed total height. This includes borders and buttons. Example: '100%', '500px', or 500
			'innerWidth'        => false,		// This is an alternative to 'width' used to set a fixed inner width. This excludes borders and buttons. Example: '50%', '500px', or 500
			'innerHeight'       => false,		// This is an alternative to 'height' used to set a fixed inner height. This excludes borders and buttons. Example: '50%', '500px', or 500
			'initialWidth'      => 300,			// Set the initial width, prior to any content being loaded.
			'initialHeight'     => 100,			// Set the initial height, prior to any content being loaded.
			'maxWidth'          => false,		// Set a maximum width for loaded content. Example: '100%', 500, '500px'
			'maxHeight'         => false,		// Set a maximum height for loaded content. Example: '100%', 500, '500px'
			'scalePhotos'       => true,		// If 'true' and if maxWidth, maxHeight, innerWidth, innerHeight, width, or height have been defined, ColorBox will scale photos to fit within the those values.
			'scrolling'         => true,		// If 'false' ColorBox will hide scrollbars for overflowing content. This could be used on conjunction with the resize method (see below) for a smoother transition if you are appending content to an already open instance of ColorBox.
			'iframe'            => false,		// If 'true' specifies that content should be displayed in an iFrame.
			'inline'            => false,		// If 'true' a jQuery selector can be used to display content from the current page. Example:  $('#inline').colorbox({inline:true, href:'#myForm'});
			'html'              => false,		// This allows an HTML string to be used directly instead of pulling content from another source (ajax, inline, or iframe). Example: $.colorbox({html:'<p>Hello</p>'});
			'photo'             => false,		// If true, this setting forces ColorBox to display a link as a photo. Use this when automatic photo detection fails (such as using a url like 'photo.php' instead of 'photo.jpg', 'photo.jpg#1', or 'photo.jpg?pic=1')
			'opacity'           => 0.85,		// The overlay opacity level. Range: 0 to 1.
			'open'              => false,		// If true, the lightbox will automatically open with no input from the visitor.
			'returnFocus'       => true,		// If true, focus will be returned when ColorBox exits to the element it was launched from.
			'preloading'        => true,		// Allows for preloading of 'Next' and 'Previous' content in a shared relation group (same values for the 'rel' attribute), after the current content has finished loading. Set to 'false' to disable.
			'overlayClose'      => true,		// If false, disables closing ColorBox by clicking on the background overlay.
			'escKey'            => true, 		// If false, will disable closing colorbox on esc key press.
			'arrowKey'          => true, 		// If false, will disable the left and right arrow keys from navigating between the items in a group.
			'loop'              => true, 		// If false, will disable the ability to loop back to the beginning of the group when on the last element.
			'slideshow'         => false, 		// If true, adds an automatic slideshow to a content group / gallery.
			'slideshowSpeed'    => 2500, 		// Sets the speed of the slideshow, in milliseconds.
			'slideshowAuto'     => true, 		// If true, the slideshow will automatically start to play.

			'slideshowStart'    => Shopp::__('start slideshow'),	// Text for the slideshow start button.
			'slideshowStop'     => Shopp::__('stop slideshow'),		// Text for the slideshow stop button
			'previous'          => Shopp::__('previous'), 			// Text for the previous button in a shared relation group (same values for 'rel' attribute).
			'next'              => Shopp::__('next'), 				// Text for the next button in a shared relation group (same values for 'rel' attribute).
			'close'             => Shopp::__('close'),				// Text for the close button. The 'Esc' key will also close ColorBox.

			// Text format for the content group / gallery count. {current} and {total} are detected and replaced with actual numbers while ColorBox runs.
			'current'           => Shopp::__('image %s of %s', '{current}', '{total}'),

			'onOpen'            => false,		// Callback that fires right before ColorBox begins to open.
			'onLoad'            => false,		// Callback that fires right before attempting to load the target content.
			'onComplete'        => false,		// Callback that fires right after loaded content is displayed.
			'onCleanup'         => false,		// Callback that fires at the start of the close process.
			'onClosed'          => false		// Callback that fires once ColorBox is closed.
		);

		$booleans = array('scalePhotos', 'scrolling', 'iframe', 'inline', 'photo', 'open', 'returnFocus', 'overlayClose', 'escKey', 'arrowKey', 'loop', 'slideshow', 'slideshowAuto');

		// Map lowercase to proper-case option names
		$map = array_combine(array_map('strtolower', array_keys($defaults)), array_keys($defaults));

		// Get changed settings based on lower case
		$options = array_intersect_key($options, $map);
		$settings = array_intersect_key($map, $options);

		// Remap to proper-case names
		$options = array_combine($settings, $options);

		// Convert strings to booleans
		foreach ( $options as $name => &$value )
			if ( in_array($name, $booleans) ) $value = Shopp::str_true($value);

		$js = 'var cbo = ' . json_encode($options).';';
		add_storefrontjs($js, true);
	}

	/**
	 * Iterates over the account menu items
	 *
	 * @api `shopp('storefront.account-menu')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return bool True if the next menu item exists, false otherwise
	 **/
	public static function account_menu ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();
		if ( ! isset($Storefront->_menu_looping) ) {
			reset($Storefront->menus);
			$Storefront->_menu_looping = true;
		} else next($Storefront->menus);

		if ( current($Storefront->menus) !== false ) return true;
		else {
			unset($Storefront->_menu_looping);
			reset($Storefront->menus);
			return false;
		}
	}

	/**
	 * Provides the current account menu item from the account-menu loop
	 *
	 * @api `shopp('storefront.account-menuitem')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppStorefront $O       The working object
	 * @return string The account menu item entry
	 **/
	public static function account_menuitem ( $result, $options, $O ) {
		$Storefront = ShoppStorefront();
		$page = current($Storefront->menus);
		if ( array_key_exists('url', $options) ) return add_query_arg($page->request, '', Shopp::url(false, 'account', is_ssl()));
		if ( array_key_exists('action', $options) ) return $page->request;
		if ( array_key_exists('classes', $options) ) {
			$classes = array($page->request);
			if ($Storefront->account['request'] == $page->request) $classes[] = 'current';
			return join(' ', $classes);
		}
		if (array_key_exists('current', $options) && $Storefront->account['request'] == $page->request)
			return true;
		return $page->label;
	}

}


/**
 * Create HTML list of categories.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
class ShoppCategoryWalker extends Walker {

	public $tree_type = 'shopp_category';
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * Starts the list before the elements are added.
	 *
	 * @see Walker::start_lvl()
	 *
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of category. Used for tab indentation.
	 * @param array  $args   An array of arguments. Will only append content if style argument value is 'list'.
	 *                       @see wp_list_categories()
	 */
	public function start_lvl ( &$output, $depth = 0, $args = array() ) {
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	/**
	 * Ends the list of after the elements are added.
	 *
	 * @see Walker::end_lvl()
	 *
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int    $depth  Depth of category. Used for tab indentation.
	 * @param array  $args   An array of arguments. Will only append content if style argument value is 'list'.
	 *                       @wsee wp_list_categories()
	 */
	public function end_lvl ( &$output, $depth = 0, $args = array() ) {
		if ( 'list' != $args['style'] )
			return;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 *
	 * @since 2.1.0
	 *
	 * @param string $output   Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int    $depth    Depth of category in reference to parents. Default 0.
	 * @param array  $args     An array of arguments. @see wp_list_categories()
	 * @param int    $id       ID of the current category.
	 */
	public function start_el ( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		extract($args);

		$smartcollection = $category->taxonomy == get_class_property( 'SmartCollection', 'taxon');
		$categoryname = $category->name;

        $href = get_term_link($category);

		$classes = '';
		if ( 'list' == $args['style'] ) {
			$classes = 'cat-item cat-item-' . $category->term_id;

			$Collection = ShoppCollection();
			if ( isset($Collection->slug) && $Collection->slug == $category->slug)
				$classes .= ' current-cat current';

			if ( ! empty($Collection->parent) && $Collection->parent == $category->term_id)
				$classes .= ' current-cat-parent current-parent';
		}

		$total = isset($category->count) ? $category->count : false;

		$title = Shopp::__('View all &quot;%s&quot; products', $categoryname);

		$filtered = apply_filters('shopp_storefront_categorylist_link', compact('href', 'classes', 'categoryname', 'title', 'total'));
		extract($filtered, EXTR_OVERWRITE);

		$link = '<a href="' . esc_url( $href ) . '" title="' . esc_attr( $title ) . '" class="' . $classes . '">';
		$link .= $categoryname . '</a>';

		if ( empty($total) && ! Shopp::str_true($linkall) && ! $smartcollection )
			$link = $categoryname;

		if ( false !== $total && Shopp::str_true($products) )
			$link .= ' (' . intval($total) . ')';

		$link = apply_filters('shopp_storefront_categorylist_item', $link, compact('href', 'classes', 'categoryname', 'title', 'total', 'products', 'linkall', 'smartcollection'));

		if ( 'list' == $args['style'] ) {
			$output .= "\t<li";
			if ( ! empty($classes) ) $output .=  ' class="' . $classes . '"';
			$output .= ">$link\n";
		} else {
			$output .= "\t$link<br />\n";
		}
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @see Walker::end_el()
	 *
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page   Not used.
	 * @param int    $depth  Depth of category. Not used.
	 * @param array  $args   An array of arguments. Only uses 'list' for whether should append to output. @see wp_list_categories()
	 */
	public function end_el ( &$output, $page, $depth = 0, $args = array() ) {
		if ( 'list' != $args['style'] )
			return;

		$output .= "</li>\n";
	}

}

/**
 * Create HTML dropdown list of Shopp categories.
 *
 * @package shopp
 * @since 1.3
 * @uses Walker
 */
class ShoppCategoryDropdownWalker extends Walker {

	public $tree_type = 'category';
	public $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	/**
	 * Build the category menu
	 *
	 * @since 1.3.1
	 *
	 * @param string $output   Passed by reference. Used to append additional content.
	 * @param object $category Category data object.
	 * @param int    $depth    Depth of category. Used for padding.
	 * @param array  $args     Uses 'selected' and 'products' keys, if they exist. @see wp_dropdown_categories()
	 */
	public function start_el ( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$link = get_term_link($category);

		$cat_name = apply_filters('shopp_storefront_categorylist_option', $category->name, $category);
		$output .= "\t<option class=\"level-$depth\" value=\"" . $link . "\"";
		if ( $category->term_id == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;

		if ( Shopp::str_true($args['products']) && isset($category->count) )
			$output .= '&nbsp;&nbsp;('. $category->count .')';
		$output .= "</option>\n";
	}

}

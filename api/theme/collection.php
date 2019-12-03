<?php
/**
 * collection.php
 *
 * ShoppCollectionThemeAPI provides shopp('collection') Theme API tags
 *
 * @api
 * @copyright Ingenesis Limited, 2012-2013
 * @package Shopp\API\Theme\Collection
 * @version 1.3
 * @since 1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_themeapi_context_name', array('ShoppCollectionThemeAPI', '_context_name'));

// Default text filters for category/collection Theme API tags
add_filter('shopp_themeapi_collection_description', 'wptexturize');
add_filter('shopp_themeapi_collection_description', 'convert_chars');
add_filter('shopp_themeapi_collection_description', 'wpautop');
add_filter('shopp_themeapi_collection_description', 'do_shortcode',11);

/**
 * shopp('category','...') tags
 *
 * @since 1.0
 * @version 1.1
 **/
class ShoppCollectionThemeAPI implements ShoppAPI {

	/**
	 * @var array The registry of available `shopp('cart')` properties
	 * @internal
	 **/
	static $register = array(
		'carousel'        => 'carousel',
		'coverimage'      => 'coverimage',
		'description'     => 'description',
		'feedurl'         => 'feed_url',
		'hascategories'   => 'has_categories',
		'hasimages'       => 'has_images',
		'hasproducts'     => 'load_products',
		'loadproducts'    => 'load_products',
		'id'              => 'id',
		'image'           => 'image',
		'images'          => 'images',
		'issubcategory'   => 'is_subcategory',
		'link'            => 'url',
		'name'            => 'name',
		'pagination'      => 'pagination',
		'parent'          => 'parent',
		'products'        => 'products',
		'row'             => 'row',
		'sectionlist'     => 'section_list',
		'slideshow'       => 'slideshow',
		'slug'            => 'slug',
		'subcategories'   => 'subcategories',
		'subcategorylist' => 'subcategory_list',
		'total'           => 'total',
		'url'             => 'url',

		// Faceted menu tags
		'hasfacetedmenu'      => 'has_faceted_menu',
		'facetedmenu'         => 'faceted_menu',
		'isfacetfiltered'     => 'is_facet_filtered',
		'facetfilters'        => 'facet_filters',
		'facetfilter'         => 'facet_filter',
		'facetfiltered'       => 'facet_filtered',
		'facetmenus'          => 'facet_menus',
		'facetname'           => 'facet_name',
		'facetlabel'          => 'facet_label',
		'facetslug'           => 'facet_slug',
		'facetlink'           => 'facet_link',
		'facetmenuhasoptions' => 'facet_menu_has_options',
		'facetoptions'        => 'facet_options',
		'facetoptionlink'     => 'facet_option_link',
		'facetoptionlabel'    => 'facet_option_label',
		'facetoptioninput'    => 'facet_option_input',
		'facetoptionvalue'    => 'facet_option_value',
		'facetoptioncount'    => 'facet_option_count'
	);

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
			case 'collection':
			case 'category':
			case 'subcategory':
			return 'collection';
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
	 * @param ShoppOrder $Object The ShoppOrder object to set as the working context
	 * @param string     $context The context being worked on by the Theme API
	 * @return ShoppCollection The active object context
	 **/
	public static function _setobject ( $Object, $context ) {
		if( is_object($Object) && is_a($Object, 'ProductCollection') ) return $Object;

		switch ( $context ) {
			case 'collection':
			case 'category':
				return ShoppCollection();
				break;
			case 'subcategory':
				if ( isset(ShoppCollection()->child) )
					return ShoppCollection()->child;
				break;
		}
		return $Object;
	}

	/**
	 * Provides the authoritative Theme API context
	 *
	 * @internal
	 * @since 1.2
	 *
	 * @return string The Theme API context name
	 */
	public static function _apicontext () {
		return 'collection';
	}

	/**
	 * Provides a side-scrolling carousel of products loaded in the collection
	 *
	 * @api `shopp('collection.carousel')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **duration**: `500` The duration in microseconds of the transition effect (1000 = 1 second)
	 * - **fit**: `crop` (width, height, crop, matte, all) The fit of unproportional images
	 * - **imageheight**: `96` The maximum height of the images
	 * - **imagewidth**: `96` The maximum width of the images
	 * - **style**: `chevron-sign` (arrow, chevron-sign, circle-arrow, caret) The style of arrow icons to use
	 * @param ShoppCollection $O       The working object
	 * @return string The generated carousel markup
	 **/
	public static function carousel ( $result, $options, $O ) {
		$options['load'] = array('images');
		if ( ! $O->loaded ) $O->load($options);
		if ( count($O->products) == 0 ) return false;

		// Supported arrow styles
		$styles = array('arrow', 'chevron-sign', 'circle-arrow', 'caret');

		$defaults = array(
			'imagewidth'  => '96',
			'imageheight' => '96',
			'fit'         => 'all',
			'duration'    => 500,
			'style'       => 'chevron-sign'
		);
		$options = array_merge($defaults, $options);
		extract($options, EXTR_SKIP);

		if ( ! in_array($style, $styles) )
			$style = $defaults['style'];

		$_  = '<div class="carousel duration-' . $duration . '">';
		$_ .= '<div class="frame">';
		$_ .= '<ul>';

		foreach ( $O->products as $Product ) {

			if ( empty($Product->images) ) continue;

			$_ .= '<li><a href="' . $Product->tag('url') . '">';
			$_ .= $Product->tag('image', array('width' => $imagewidth, 'height' => $imageheight, 'fit' => $fit));
			$_ .= '</a></li>';
		}

		$_ .= '</ul></div>';
		$_ .= '<button type="button" name="left" class="left shoppui-' . $style . '-left"><span class="hidden">' . Shopp::__('Previous Page') . '</span></button>';
		$_ .= '<button type="button" name="right" class="right shoppui-' . $style . '-right"><span class="hidden">' . Shopp::__('Next Page') . '</span></button>';
		$_ .= '</div>';

		return $_;
	}

	/**
	 * Provides the markup for the category cover image
	 *
	 * @api `shopp('collection.coverimage')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
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
	 * - **property**: (id,url,src,title,alt,width,height,class) Provide a property of the image rather than the image markup
	 * - **quality**: The JPEG image quality (0-100%, default is 80)
	 * - **sharpen**: Apply an unsharp mask to the image (100%-500%, default is none)
	 * - **size**: The size to use for width and height of the image (used in place of width and height)
	 * - **title**: The title property of the image
	 * - **width**: The width of the image in pixels
	 * - **height**: The height of the image in pixels
	 * - **zoom**: Enables the image zoom effect to view the original size image in a modal image viewer (Colorbox)
	 * - **zoomfx**: `shopp-zoom` Enables zoom (also known as lightbox) effects for alternate JavaScript-based modal content viewers.
	 * @param ShoppCollection $O       The working object
	 * @return string The image markup
	 **/
	public static function coverimage ( $result, $options, $O ) {
		// Force select the first loaded image
		unset($options['id']);
		$options['index'] = 0;
		return self::image( $result, $options, $O );
	}

	/**
	 * Provides the category description
	 *
	 * @api `shopp('collection.description')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **collapse**: `on` (on,off) Provides an empty string instead of an empty wrapping container if no category description is set
	 * - **wrap**: `on` (on,off) Enable or disable the before and after settings off
	 * - **before**: `<div class="category-description">` Markup to add before the widget
	 * - **after**: `</div>` Markup to add after the widget
	 * @param ShoppCollection $O       The working object
	 * @return string The description markup
	 **/
	public static function description ( $result, $options, $O ) {
		$defaults = array(
			'collapse' => true,
			'wrap'     => true,
			'before'   => '<div class="category-description">' . "\n\n",
			'after'    => '</div>'
		);
		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		if ( ( Shopp::str_true($collapse) && empty($O->description)) || ! isset($O->description) ) return '';
		if ( ! Shopp::str_true($wrap) ) $before = $after = '';
		return $before . $O->description . $after;
	}

	/**
	 * Checks if any faceted menu filters have been applied to the current product collection
	 *
	 * @api `shopp('collection.is-facet-filtered')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if filtered, false otherwise
	 **/
	public static function is_facet_filtered ( $result, $options, $O ) {
		return ( count($O->filters) > 0 );
	}

	/**
	 * Iterate over the faceted menu filters that are applied to the current product collection view
	 *
	 * @api `shopp('collection.facet-filters')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the next filter exists, false otherwise
	 **/
	public static function facet_filters ( $result, $options, $O ) {
		if ( ! isset($O->_filters_loop) ) {
			reset($O->filters);
			$O->_filters_loop = true;
		} else next($O->filters);

		$slug = key($O->filters);
		if ( isset($O->facets[ $slug ]) )
			$O->facet = $O->facets[ $slug ];

		if ( current($O->filters) !== false ) return true;
		else {
			unset($O->_filters_loop, $O->facet);
			return false;
		}
	}

	/**
	 * Displays the current faceted menu filter from the facet-filters loop
	 *
	 * @api `shopp('collection.facet-filter')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current filter value
	 **/
	public static function facet_filter ( $result, $options, $O ) {
		if ( ! isset($O->_filters_loop) ) return false;
		return ProductCategoryFacet::range_labels($O->facet->selected);
	}

	/**
	 * Iterate over the faceted menus for a product category
	 *
	 * @api `shopp('collection.facet-menus')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the next menu exists, false otherwise
	 **/
	public static function facet_menus ( $result, $options, $O ) {
		if ( ! isset($O->_facets_loop) ) {
			reset($O->facets);
			$O->_facets_loop = true;
		} else next($O->facets);

		if ( current($O->facets) !== false ) return true;
		else {
			unset($O->_facets_loop);
			return false;
		}
	}

	/**
	 * Displays the current faceted menu name
	 *
	 * @api `shopp('collection.facet-name')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current facet name
	 **/
	public static function facet_name ( $result, $options, $O ) {
		if ( isset($O->_filters_loop) ) $facet = $O->facet;
		else $facet = current($O->facets);
		return $facet->name;
	}

	/**
	 * Display the label of the current facet from the loop
	 *
	 * @api `shopp('collection.facet-label')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current facet label
	 **/
	public static function facet_label ($result, $options, $O) {
		if ( isset($O->_filters_loop) ) $facet = $O->facet;
		else $facet = current($O->facets);
		return $facet->filters[ $facet->selected ]->label;
	}


	/**
	 * Display the current facet manu slug
	 *
	 * @api `shopp('collection.facet-slug')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current facet slug
	 **/
	public static function facet_slug ( $result, $options, $O ) {
		if ( isset($O->_filters_loop) ) $facet = $O->facet;
		else $facet = current($O->facets);
		return $facet->slug;
	}

	/**
	 * Display the current facet manu link
	 *
	 * @api `shopp('collection.facet-link')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current facet slug
	 **/
	public static function facet_link ( $result, $options, $O ) {
		if ( isset($O->_filters_loop) ) $facet = $O->facet;
		else $facet = current($O->facets);
		return $facet->link;
	}

	/**
	 * Check if the current facet has an active filter
	 *
	 * @api `shopp('collection.facet-filtered')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the facet has an active filter, false otherwise
	 **/
	public static function facet_filtered ( $result, $options, $O ) {
		if ( isset($O->_filters_loop) ) $facet = $O->facet;
		else $facet = current($O->facets);
		return ! empty($facet->selected);
	}

	/**
	 * Check if the facet has any filter options
	 *
	 * @api `shopp('collection.facet-menu-has-options')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the are filter options, false otherwise
	 **/
	public static function facet_menu_has_options ( $result, $options, $O ) {
		$facet = current($O->facets);
		return ( count($facet->filters) > 0 );
	}

	/**
	 * Iterate over the faceted menu's filter options
	 *
	 * @api `shopp('collection.facet-options')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the next option exists, false otherwise
	 **/
	public static function facet_options ( $result, $options, $O ) {
		$facet = current($O->facets);

		if ( ! isset($O->_facetoptions_loop) ) {
			reset($facet->filters);
			$O->_facetoptions_loop = true;
		} else next($facet->filters);

		if ( current($facet->filters) !== false ) return true;
		else {
			unset($O->_facetoptions_loop);
			return false;
		}
	}

	/**
	 * Provides the URL for enabling the current filter option
	 *
	 * @api `shopp('collection.facet-option-link')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current filter option link
	 **/
	public static function facet_option_link ( $result, $options, $O ) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return add_query_arg(urlencode($facet->slug), $option->param, $facet->link);
	}

	/**
	 * Displays the current faceted menu filter option label
	 *
	 * @api `shopp('collection.facet-option-label')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current filter option label
	 **/
	public static function facet_option_label ( $result, $options, $O ) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return $option->label;
	}

	/**
	 * Displays the current faceted menu filter option value
	 *
	 * @api `shopp('collection.facet-option-value')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The current filter option value
	 **/
	public static function facet_option_value ( $result, $options, $O ) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return $option->param;
	}

	/**
	 * Provides the number of products that match the current filter option
	 *
	 * @api `shopp('collection.facet-option-count')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The product count of the current filter option
	 **/
	public static function facet_option_count ( $result, $options, $O ) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return $option->count;
	}

	/**
	 * Provides markup for an input to activate the current filter option
	 *
	 * @api `shopp('collection.facet-option-input')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * - **type**: `checkbox` (checkbox,text,hidden,button,submit) The type of input to generate
	 * - **label**: The label of the current filter option
	 * - **value**: The value of the current filter option
	 * - **class**: `click-submit` The class attribute specifies one or more class-names for the input element
	 * @param ShoppCollection $O       The working object
	 * @return string Markup for the current filter option input
	 **/
	public static function facet_option_input ( $result, $options, $O ) {
		$facet = current($O->facets);
		$option = current($facet->filters);

		$defaults = array(
			'type'  => 'checkbox',
			'label' => $option->label,
			'value' => $option->param,
			'class' => 'click-submit'
		);
		if ( isset($options['class']) ) $options['class'] = trim($defaults['class'] . ' ' . $options['class']);
		$options = array_merge($defaults, $options);
		extract($options);
		if ( $option->param == $facet->selected ) $options['checked'] = 'checked';

		$_ = array();
		$_[] = '<form action="' . self::url(false, false, $O) . '" method="get">';
		$_[] = '<input type="hidden" name="s_ff" value="on" /><input type="hidden" name="' . $facet->slug . '" value="" />';
		$_[] = '<label><input type="' . $type . '" name="' . $facet->slug . '" value="' . $value . '"' . inputattrs($options) . ' />' . ( ! empty($label) ? '&nbsp;' . $label : '' ) . '</label>';
		$_[] = '</form>';
		return join('', $_);
	}

	/**
	 * Provides markup for a menu of faceted filter options to find products in the current category
	 *
	 * @api `shopp('collection.faceted-menu')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string Markup of the faceted menu for the collection
	 **/
	public static function faceted_menu ( $result, $options, $O ) {
		$_ = array();

		// Use a template if available
		$template = locate_shopp_template(array('facetedmenu-' . $O->slug . '.php', 'facetedmenu.php'));
		if ( $template ) {
			ob_start();
			include($template);
			return ob_get_clean();
		}

		if ( self::is_facet_filtered('', false, $O) ) {
			$_[] = '<ul>';
			while( self::facet_filters(false, false, $O) ) {
			 	$_[] = '<li>';
				$_[] = '<strong>' . self::facet_name(false, false, $O) . ':</strong> ';
				$_[] = self::facet_filter(false, false, $O);
				$_[] = sprintf(' <a href="%s" class="shoppui-remove-sign cancel"><span class="hidden">%s</span></a>', self::facet_link(false, false, $O), Shopp::__('Remove Filter'));
				$_[] = '</li>';
			}
			$_[] = '</ul>';
		}

		$_[] = '<ul class="faceted-menu">';
		while ( self::facet_menus(false, false, $O) ) {
			if ( self::facet_filtered(false, false, $O) ) continue;
			if ( ! self::facet_menu_has_options(false, false, $O) ) continue;
			$_[] = '<li>';
			$_[] = '<h4>' . self::facet_name(false, false, $O) . '</h4>';
			$_[] = '<ul class="facet-option ' . self::facet_slug(false, false, $O) . '">';

			while ( self::facet_options(false, false, $O) ) {
				$_[] = '<li>';
				$_[] = sprintf('<a href="%s">%s</a>', esc_url(self::facet_option_link(false, false, $O)), self::facet_option_label(false, false, $O));
				$_[] = ' <span class="count">' . self::facet_option_count(false, false, $O) . '</span>';
				$_[] = '</li>';
			}

			$_[] = '</ul>';
			$_[] = '</li>';

		}
		$_[] = '</ul>';

		return join('', $_);
	}

	/**
	 * Provides the URL for the collection's RSS feed
	 *
	 * @api `shopp('collection.feed-url')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The URL of the RSS feed
	 **/
	public static function feed_url ( $result, $options, $O ) {
		global $wp_rewrite;
		$url = self::url($result, $options, $O);
		if ( ! $wp_rewrite->using_permalinks() ) return add_query_arg(array('src' => 'category_rss'), $url);

		$query = false;
		if ( strpos($url, '?') !== false ) list($url, $query) = explode('?', $url);
		$url = trailingslashit($url) . 'feed';
		if ( $query ) $url = "$url?$query";
			return $url;
	}

	/**
	 * Checks if the current category has sub-categories and, if so, loads them
	 *
	 * This tag is used to detect and load sub-categories for use with the
	 * `shopp('collection.subcategories')` looping tag.
	 *
	 * @api `shopp('collection.has-categories')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if sub-categories exist, false otherwise
	 **/
	public static function has_categories ( $result, $options, $O ) {
		if ( empty($O->children) && method_exists($O, 'load_children') ) $O->load_children( $options );
		reset($O->children);
		return ( ! empty($O->children) );
	}

	/**
	 * Checks if the current category has a faceted menu setup
	 *
	 * @api `shopp('collection.has-faceted-menu')`
	 * @since 1.1
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if there are faceted menus, false otherwise
	 **/
	public static function has_faceted_menu ( $result, $options, $O ) {
		if ( ! is_a($O, 'ProductCategory') ) return false;
		if ( empty($O->meta) ) $O->load_meta();
		if ( property_exists($O,'facetedmenus') && Shopp::str_true($O->facetedmenus) ) {
			$O->load_facets();
			return true;
		}
		return false;
	}

	/**
	 * Checks if the category has images and loads them
	 *
	 * @api `shopp('collection.has-images')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if there are images
	 **/
	public static function has_images ( $result, $options, $O ) {

		if ( ! is_a($O, 'ProductCategory') ) return false;

		if ( empty($O->images) ) {
			$O->load_images();
			reset($O->images);
		}

		return ( ! empty($O->images) );

	}

	/**
	 * Provides the database term ID for the category
	 *
	 * @api `shopp('collection.id')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string|bool The id of the category or false
	 **/
	public static function id ( $result, $options, $O ) {
		if ( isset($O->term_id) ) return $O->term_id;
		return false;
	}

	/**
	 * Renders a custom category image
	 *
	 * @api `shopp('collection.image')`
	 * @see ShoppStorefrontThemeAPI::image() from api/theme/storefront.php
	 * @since 1.2
	 *
	 * @param string      $result  The output
	 * @param array       $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The image markup
	 **/
	public static function image ( $result, $options, $O ) {
		if ( ! self::has_images( $result, $options, $O )) return '';
		return ShoppStorefrontThemeAPI::image( $result, $options, $O );
	}

	/**
	 * Iterate over the images loaded for the category
	 *
	 * @api `shopp('collection.images')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the next image exists, false otherwise
	 **/
	public static function images ( $result, $options, $O ) {
		if ( ! isset($O->_images_loop) ) {
			reset($O->images);
			$O->_images_loop = true;
		} else next($O->images);

		if ( current($O->images) !== false ) return true;
		else {
			unset($O->_images_loop);
			return false;
		}
	}

	/**
	 * Checks if the current category is a subcategory of another
	 *
	 * @api `shopp('collection.is-subcategory')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if it is a subcategory, false otherwise
	 **/
	public static function is_subcategory ( $result, $options, $O ) {
		if ( isset($options['id']) ) return ( $O->parent == $options['id'] );
		return ( $O->parent != 0 );
	}

	/**
	 * Checks if there are products available to display and loads product data
	 *
	 * @api `shopp('collection.load-products')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if products are loaded, false otherwise
	 **/
	public static function load_products ( $result, $options, $O ) {
		if ( empty($O->id) && empty($O->slug) ) return false;

		if ( isset($options['load']) ) {
			$dataset = explode(',', $options['load']);
			$options['load'] = array();

			foreach ( $dataset as $name ) {

				if ( 'description' == trim(strtolower($name)) )
					$options['columns'] = 'p.post_content';

				$options['load'][] = trim($name);
			}
		 } else {
			$options['load'] = array('prices');
		}
		if ( ! $O->loaded ) $O->load($options);

		return count($O->products) > 0;
	}

	/**
	 * Provides the name of the collection
	 *
	 * @api `shopp('collection.name')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The collection name
	 **/
	public static function name ( $result, $options, $O ) {
		return $O->name;
	}

	/**
	 * Provides markup for a list of linked page numbers for pages of products in the collection
	 *
	 * @api `shopp('collection.pagination')`
	 * @since 1.0
	 *
	 * @param string          	$result  The output
	 * @param array           	$options The options
	 * - **activeclass**: `active` The class attribute specifies one or more class-names for the active element
	 * - **after**: `</div>` Markup to add after the pagination
	 * - **before**: `<div>` Markup to add before the pagination
	 * - **class**: `paging` The class attribute specifies one or more class-names for the <ul> tag.
	 * - **disabledclass**: `disabled` The class attribute specifies one or more class-names for the disabled element
	 * - **jumpback**: `&laquo;` The label for the jump backward
	 * - **jumpfwd**: `&raquo;` The label for the jump forward
	 * - **label**: `Pages:` The label for the pagination list
	 * - **linked**: `true` Add link to jump backward, jump forward label
	 * - **next**: `Next` The label for the next button
	 * - **nextclass**: `next` The class attribute specifies one or more class-names for the next element
	 * - **previous**: `Previous` The label for the previous button
	 * - **previousclass**: `previous` The class attribute specifies one or more class-names for the previous element
	 * - **show**: `1000` The maximum number of pages to show
	 * @param ShoppCollection 	$O       The working object
	 * @return string The pagination markup
	 **/
	public static function pagination ( $result, $options, $O ) {
		if ( ! $O->paged ) return '';

		$defaults = array(
			'activeclass'    => 'active',
			'after'          => '</div>',
			'before'         => '<div>',
			'class'          => 'paging',
			'disabledclass'  => 'disabled',
			'jumpback'       => '&laquo;',
			'jumpfwd'        => '&raquo;',
			'label'          => Shopp::__('Pages:'),
			'linked'         => true,
			'next'           => Shopp::__('Next'),
			'nextclass'     => 'next',
			'previous'       => Shopp::__('Previous'),
			'previousclass' => 'previous',
			'show'           => 1000
		);

		$options = array_merge($defaults, $options);
		extract($options);

		$_ = array();

		if ( isset($O->alpha) && $O->paged ) {
			$_[] = $before . $label;
			$_[] = '<ul class="' . esc_attr($class) . '">';

			foreach ( $O->alpha as $letter => $products ) {
				$link = $O->pagelink($letter);
				if ( $products > 0 ) $_[] = '<li><span><a href="' . esc_url($link) . '">' . $letter . '</a></span></li>';
				else $_[] = '<li><span>' . $letter . '</span></li>';
			}

			$_[] = '</ul>';
			$_[] = $after;

			return join("\n", $_);
		}

		if ( $O->pages > 1 ) {
			if ( $O->pages > $show ) $visible_pages = $show + 2;
			else $visible_pages = $O->pages + 1;
			$jumpsize = ceil( $visible_pages / 2 );

			$_[] = $before . $label;
			$_[] = '<ul class="' . esc_attr($class) . '">';

			// Add previous button
			if ( ! empty($previous) && $O->page > 1 ) {
				$prev = $O->page-1;
				$link = $O->pagelink($prev);
				$_[]  = '<li class="' . esc_attr($previousclass) . '"><span><a href="' . esc_url($link) . '" rel="prev">' . $previous . '</a></span></li>';
			} else $_[] = '<li class="' . esc_attr($previousclass) . ' ' . esc_attr($disabledclass) . '"><span>' . $previous . '</span></li>';
			// end previous button

			$center = floor( $show / 2);
			if ( $O->page <= $center ) {
				$i = 1;
			} else {
				$i = $O->page - $center;
				$visible_pages = $O->page + $center + 1;
				if ( $visible_pages > $O->pages ) {
					$visible_pages = $O->pages + 1;
					// to prevent negative pagenumbers
					$i = max( 1, $O->pages - $show);
				}

				if ( $i > 1 ) {
					$link = $O->pagelink(1);
					$_[]  = '<li><span><a href="' . esc_url($link) . '">1</a></span></li>';

					$pagenum = ( $O->page - $jumpsize );
					if ( $pagenum < 1 ) $pagenum = 1;
					$link = $O->pagelink($pagenum);

					if ( $i > 2 ) {
						if ( $linked === true ) $_[] = '<li><span><a href="' . esc_url($link) . '">' . $jumpback . '</a></span></li>';
						else $_[] = '<li><span>' . $jumpback . '</span></li>';
					}
				}
			}

			while ( $i < $visible_pages ) {
				$link = $O->pagelink($i);
				if ( $i == $O->page ) $_[] = '<li class="' . esc_attr($activeclass) . '"><span>' . $i . '</span></li>';
				else $_[] = '<li><span><a href="' . esc_url($link) . '">' . $i . '</a></span></li>';
				$i++;
			}

			if ( $O->pages > $visible_pages ) {
				$pagenum = ( $O->page + $jumpsize );
				if ( $pagenum > $O->pages ) $pagenum = $O->pages;
				$link = $O->pagelink($pagenum);
				if ( $linked === true ) $_[] = '<li><span><a href="' . esc_url($link) . '">' . $jumpfwd . '</a></span></li>';
				else $_[] = '<li><span>' . $jumpfwd . '</span></li>';
			}

			if ( $O->pages >= $visible_pages ) {
				$link = $O->pagelink($O->pages);
				$_[] = '<li><span><a href="' . esc_url($link) . '">' . $O->pages . '</a></span></li>';
			}

			// Add next button
			if ( ! empty($next) && $O->page < $O->pages) {
				$pagenum = $O->page + 1;
				$link = $O->pagelink($pagenum);
				$_[] = '<li class="' . esc_attr($nextclass) . '"><span><a href="' . esc_url($link) . '" rel="next">' . $next . '</a></span></li>';
			} else $_[] = '<li class="' . esc_attr($nextclass) . ' ' . esc_attr($disabledclass) . '"><span>' . $next . '</span></li>';
			$_[] = '</ul>';
			$_[] = $after;
		}
		return join("\n", $_);
	}


	/**
	 * Provides the category ID of the parent category for sub-categories
	 *
	 * @api `shopp('collection.parent')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string|bool The parent ID or false otherwise
	 **/
	public static function parent ( $result, $options, $O ) {
		return isset($O->parent) ? $O->parent : false;
	}

	/**
	 * Iterates over the products in the collection
	 *
	 * @api `shopp('collection.products')`
	 * @since 1.0
	 *
	 * @param string           $result  The output
	 * @param array            $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the next product exists, false otherwise
	 **/
	public static function products ( $result, $options, $O ) {
		if ( isset($options['looping']) ) return isset($O->_product_loop);

		$null = null;
		if ( ! isset($O->_product_loop) ) {
			reset($O->products);
			ShoppProduct(current($O->products));
			$O->_pindex = 0;
			$O->_rindex = false;
			$O->_product_loop = true;
		} else {
			if ( $Product = next($O->products) )
				ShoppProduct($Product);
			$O->_pindex++;
		}

		if ( current($O->products) !== false ) return true;
		else {
			unset($O->_product_loop);
			ShoppProduct($null);
			if ( is_a(ShoppStorefront()->Requested, 'ShoppProduct') )
				ShoppProduct(ShoppStorefront()->Requested);
			$O->_pindex = 0;
			return false;
		}
	}

	/**
	 * Checks if a new row is needed based on the products-per-row presentation setting
	 *
	 * @api `shopp('collection.row')`
	 * @since 1.1
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if a new row is needed, false otherwise
	 **/
	public static function row ( $result, $options, $O ) {
		if ( ! isset($O->_rindex) || $O->_rindex === false ) $O->_rindex = 0;
		else $O->_rindex++;

		if ( empty($options['products']) )
			$options['products'] = shopp_setting('row_products');

		return ( 0 == $O->_rindex || $O->_rindex > 0 && $O->_rindex % $options['products'] == 0 );
	}

	/**
	 * Provides a linked list of categories in the current category section
	 *
	 * A category section is a listing of all of the descendant (children) categories
	 * and ancestor (parents) categories up to the top-level parent.
	 *
	 * @api `shopp('collection.section-list')`
	 * @since 1.1
	 *
	 * @param string           $result  The output
	 * @param array            $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The list markup
	 **/
	public static function section_list ( $result, $options, $O ) {
		if ( ! isset($O->id) || empty($O->id) ) return false;
		$options['section'] = true;
		return ShoppStorefrontThemeAPI::category_list($result, $options, $O);
	}


	/**
	 * Provides markup for a slideshow of cover images for products in the collection
	 *
	 * @api `shopp('collection.slideshow')`
	 * @since 1.1
	 *
	 * @param string           $result  The output
	 * @param array            $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The slideshow markup
	 **/
	public static function slideshow ( $result, $options, $O ) {
		$options['load'] = array('images');
		if ( ! $O->loaded ) $O->load($options);
		if ( count($O->products) == 0 ) return false;

		$defaults = array(
			'fx'       => 'fade',
			'duration' => 1000,
			'delay'    => 7000,
			'order'    => 'normal'
		);
		$imgdefaults = array(
			'setting' => false,
			'width'   => '580',
			'height'  => '200',
			'size'    => false,
			'fit'     => 'crop',
			'sharpen' => false,
			'quality' => false,
			'bg'      => false,
		);

		$options = array_merge($defaults, $imgdefaults, $options);
		extract($options, EXTR_SKIP);

		$href = Shopp::url( '' != get_option('permalink_structure') ? trailingslashit('000') : '000', 'images');
		$imgsrc = add_query_string("$width, $height", $href);

		$string = '<ul class="slideshow ' . $fx . '-fx ' . $order . '-order duration-' . $duration . ' delay-' . $delay . '">';
		$string .= '<li class="clear"><img src="' . $imgsrc . '" width="' . $width . '" height="' . $height . '" /></li>';

		foreach ( $O->products as $Product ) {
			if ( empty($Product->images) ) continue;
			$string .= '<li><a href="' . $Product->tag('url') . '">';
			$imgoptions = array_filter(array_intersect_key($options, $imgdefaults));
			$string .= shopp($Product, 'get-image', $imgoptions);
			$string .= '</a></li>';
		}

		$string .= '</ul>';
		return $string;
	}

	/**
	 * Provides the collection slug
	 *
	 * @api `shopp('collection.slug')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The collection slug
	 **/
	public static function slug ( $result, $options, $O ) {
		if ( isset($O->slug) ) return urldecode($O->slug);
		return false;
	}

	/**
	 * Iterates over the subcategories of the current category
	 *
	 * @api `shopp('collection.subcategories')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return bool True if the next subcategory exists, false otherwise
	 **/
	public static function subcategories ( $result, $options, $O ) {
		if ( ! isset($O->_children_loop) ) {
			reset($O->children);
			$O->child = current($O->children);
			$O->_cindex = 0;
			$O->_children_loop = true;
		} else {
			$O->child = next($O->children);
			$O->_cindex++;
		}

		if ( $O->child !== false ) return true;
		else {
			unset($O->_children_loop);
			$O->_cindex = 0;
			$O->child = false;
			return false;
		}
	}

	/**
	 * Provides markup for a list of subcategories of the current category
	 *
	 * @api `shopp('collection.subcategories')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The subcategory list markup
	 **/
	public static function subcategory_list ( $result, $options, $O ) {
		if ( ! isset($O->id) || empty($O->id) ) return false;
		$options['childof'] = $O->id;
		$options['default'] = Shopp::__('Select a sub-category&hellip;');
		return ShoppStorefrontThemeAPI::category_list( $result, $options, $O );
	}

	/**
	 * Provides the total count of products in the collection
	 *
	 * @api `shopp('collection.total')`
	 * @since 1.2
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The total number of products
	 **/
	public static function total ( $result, $options, $O ) {
		return $O->loaded ? $O->total : false;
	}

	/**
	 * Provides the URL for the collection
	 *
	 * @api `shopp('collection.url')`
	 * @since 1.0
	 *
	 * @param string          $result  The output
	 * @param array           $options The options
	 * @param ShoppCollection $O       The working object
	 * @return string The collection URL
	 **/
	public static function url ( $result, $options, $O ) {

		$taxonomy = get_taxonomy($O->taxonomy);

		if ( $taxonomy ) {
			$url = get_term_link($O);
		} else {
			global $wp_rewrite;
			$slug = $O->slug;
			$termlink = $wp_rewrite->get_extra_permastruct($O->taxonomy);
			if ( empty($termlink) ) {
				$termlink = "?$O->taxonomy=$slug";
				$url = home_url($termlink);
			} else {
				$termlink = str_replace("%$O->taxonomy%", $slug, $termlink);
				$url = home_url( user_trailingslashit($termlink, 'category') );
			}
		}

		if ( isset($options['page']) ) {
			$url = $O->pagelink((int)$options['page']);	
		}

		return $url;
	}

}

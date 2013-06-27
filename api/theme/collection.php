<?php
/**
* ShoppCollectionThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppCollectionThemeAPI
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_themeapi_context_name', array('ShoppCollectionThemeAPI', '_context_name'));

// Default text filters for category/collection Theme API tags
add_filter('shopp_themeapi_category_description', 'wptexturize');
add_filter('shopp_themeapi_category_description', 'convert_chars');
add_filter('shopp_themeapi_category_description', 'wpautop');
add_filter('shopp_themeapi_category_description', 'do_shortcode',11);

/**
 * shopp('category','...') tags
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.0
 * @version 1.1
 * @see http://docs.shopplugin.net/Category_Tags
 *
 **/
class ShoppCollectionThemeAPI implements ShoppAPI {
	static $register = array(
		'carousel' => 'carousel',
		'coverimage' => 'coverimage',
		'description' => 'description',
		'feedurl' => 'feed_url',
		'hascategories' => 'has_categories',
		'hasimages' => 'has_images',
		'hasproducts' => 'load_products',
		'loadproducts' => 'load_products',
		'id' => 'id',
		'image' => 'image',
		'images' => 'images',
		'issubcategory' => 'is_subcategory',
		'link' => 'url',
		'name' => 'name',
		'pagination' => 'pagination',
		'parent' => 'parent',
		'products' => 'products',
		'row' => 'row',
		'sectionlist' => 'section_list',
		'slideshow' => 'slideshow',
		'slug' => 'slug',
		'subcategories' => 'subcategories',
		'subcategorylist' => 'subcategory_list',
		'total' => 'total',
		'url' => 'url',

		// Faceted menu tags
		'hasfacetedmenu' => 'has_faceted_menu',
		'facetedmenu' => 'faceted_menu',
		'isfacetfiltered' => 'is_facet_filtered',
		'facetfilters' => 'facet_filters',
		'facetfilter' => 'facet_filter',
		'facetfiltered' => 'facet_filtered',
		'facetmenus' => 'facet_menus',
		'facetname' => 'facet_name',
		'facetslug' => 'facet_slug',
		'facetlink' => 'facet_link',
		'facetmenuhasoptions' => 'facet_menu_has_options',
		'facetoptions' => 'facet_options',
		'facetoptionlink' => 'facet_option_link',
		'facetoptionlabel' => 'facet_option_label',
		'facetoptioninput' => 'facet_option_input',
		'facetoptionvalue' => 'facet_option_value',
		'facetoptioncount' => 'facet_option_count'
	);

	static function _context_name ( $name ) {
		switch ( $name ) {
			case 'collection':
			case 'category':
			case 'subcategory':
			return 'collection';
			break;
		}
		return $name;
	}

	static function _setobject ($Object, $context) {
		if( is_object($Object) && is_a($Object, 'ProductCollection') ) return $Object;

		switch ( $context ) {
			case 'collection':
			case 'category':
				return ShoppCollection();
				break;
			case 'subcategory':
				if (isset(ShoppCollection()->child))
					return ShoppCollection()->child;
				break;
		}
		return $Object;
	}

	static function _apicontext () { return 'collection'; }

	static function carousel ($result, $options, $O) {
		$options['load'] = array('images');
		if (!$O->loaded) $O->load($options);
		if (count($O->products) == 0) return false;

		$defaults = array(
			'imagewidth' => '96',
			'imageheight' => '96',
			'fit' => 'all',
			'duration' => 500
		);
		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		$string = '<div class="carousel duration-'.$duration.'">';
		$string .= '<div class="frame">';
		$string .= '<ul>';
		foreach ($O->products as $Product) {
			if (empty($Product->images)) continue;
			$string .= '<li><a href="'.$Product->tag('url').'">';
			$string .= $Product->tag('image',array('width'=>$imagewidth,'height'=>$imageheight,'fit'=>$fit));
			$string .= '</a></li>';
		}
		$string .= '</ul></div>';
		$string .= '<button type="button" name="left" class="left">&nbsp;</button>';
		$string .= '<button type="button" name="right" class="right">&nbsp;</button>';
		$string .= '</div>';
		return $string;
	}

	static function coverimage ($result, $options, $O) {
		// Force select the first loaded image
		unset($options['id']);
		$options['index'] = 0;
		return self::image($result, $options, $O);
	}

	static function description ($result, $options, $O) { return isset($O->description) ? $O->description : '';  }


	static function is_facet_filtered ($result, $options, $O) {
		return (count($O->filters) > 0);
	}

	static function facet_filters ($result, $options, $O) {
		if (!isset($O->_filters_loop)) {
			reset($O->filters);
			$O->_filters_loop = true;
		} else next($O->filters);

		$slug = key($O->filters);
		if (isset($O->facets[ $slug ]))
			$O->facet = $O->facets[ $slug ];

		if (current($O->filters) !== false) return true;
		else {
			unset($O->_filters_loop,$O->facet);
			return false;
		}

	}

	static function facet_filter ($result, $options, $O) {
		if (!isset($O->_filters_loop)) return false;
		return ProductCategoryFacet::range_labels($O->facet->selected);
	}

	static function facet_menus ($result, $options, $O) {
		if (!isset($O->_facets_loop)) {
			reset($O->facets);
			$O->_facets_loop = true;
		} else next($O->facets);

		if (current($O->facets) !== false) return true;
		else {
			unset($O->_facets_loop);
			return false;
		}
	}

	static function facet_name ($result, $options, $O) {
		if (isset($O->_filters_loop)) $facet = $O->facet;
		else $facet = current($O->facets);
		return $facet->name;
	}

	static function facet_slug ($result, $options, $O) {
		$facet = current($O->facets);
		return $facet->slug;
	}

	static function facet_link ($result, $options, $O) {
		if (isset($O->_filters_loop)) $facet = $O->facet;
		else $facet = current($O->facets);
		return $facet->link;
	}

	static function facet_filtered ($result, $options, $O) {
		if (isset($O->_filters_loop)) $facet = $O->facet;
		else $facet = current($O->facets);
		return !empty($facet->selected);
	}

	static function facet_menu_has_options ($result, $options, $O) {
		$facet = current($O->facets);
		return (count($facet->filters) > 0);
	}

	static function facet_options   ($result, $options, $O) {
		$facet = current($O->facets);

		if (!isset($O->_facetoptions_loop)) {
			reset($facet->filters);
			$O->_facetoptions_loop = true;
		} else next($facet->filters);

		if (current($facet->filters) !== false) return true;
		else {
			unset($O->_facetoptions_loop);
			return false;
		}

	}

	static function facet_option_link  ($result, $options, $O) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return add_query_arg(urlencode($facet->slug),$option->param,$facet->link);
	}

	static function facet_option_label  ($result, $options, $O) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return $option->label;
	}

	static function facet_option_value  ($result, $options, $O) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return $option->param;
	}

	static function facet_option_count  ($result, $options, $O) {
		$facet = current($O->facets);
		$option = current($facet->filters);
		return $option->count;
	}

	static function facet_option_input  ($result, $options, $O) {
		$facet = current($O->facets);
		$option = current($facet->filters);

		$defaults = array(
			'type' => 'checkbox',
			'label' => $option->label,
			'value' => $option->param,
			'class' => 'click-submit'
		);
		if (isset($options['class'])) $options['class'] = trim($defaults['class'].' '.$options['class']);
		$options = array_merge($defaults,$options);
		extract($options);
		if ($option->param == $facet->selected) $options['checked'] = 'checked';

		$_ = array();
		$_[] = '<form action="'.self::url(false,false,$O).'" method="get"><input type="hidden" name="s_ff" value="on" /><input type="hidden" name="'.$facet->slug.'" value="" />';
		$_[] = '<label><input type="'.$type.'" name="'.$facet->slug.'" value="'.$value.'"'.inputattrs($options).' />'.(!empty($label)?'&nbsp;'.$label:'').'</label>';
		$_[] = '</form>';
		return join('',$_);
	}

	static function faceted_menu ($result, $options, $O) {
		$_ = array();

		// Use a template if available
		$template = locate_shopp_template(array('facetedmenu-'.$O->slug.'.php','facetedmenu.php'));
		if ($template) {
			ob_start();
			include($template);
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}

		if (self::is_facet_filtered('',false,$O)) {
			$_[] = '<ul>';
			while(self::facet_filters(false,false,$O)) {
				$_[] = '<li>';
				$_[] = '<strong>'.self::facet_name(false,false,$O).':</strong> ';
				$_[] = self::facet_filter(false,false,$O);
				$_[] = sprintf(' <a href="%s" class="cancel">%s</a>',self::facet_link(false,false,$O),'X');
				$_[] = '</li>';
			}
			$_[] = '</ul>';
		}

		$_[] = '<ul class="faceted-menu">';
		while(self::facet_menus(false,false,$O)) {
			if (self::facet_filtered(false,false,$O)) continue;
			if (!self::facet_menu_has_options(false,false,$O)) continue;
			$_[] = '<li>';
			$_[] = '<h4>'.self::facet_name(false,false,$O).'</h4>';
			$_[] = '<ul class="facet-option '.self::facet_slug(false,false,$O).'">';
			while(self::facet_options(false,false,$O)) {
				$_[] = '<li>';
				$_[] = sprintf('<a href="%s">%s</a>',esc_url(self::facet_option_link(false,false,$O)),self::facet_option_label(false,false,$O));
				$_[] = ' <span class="count">'.self::facet_option_count(false,false,$O).'</span>';
				$_[] = '</li>';
			}
			$_[] = '</ul>';

			$_[] = '</li>';

		}
		$_[] = '</ul>';

		return join('',$_);
	}

	static function feed_url ($result, $options, $O) {
		global $wp_rewrite;
		$url = self::url($result,$options,$O);
		if ( ! $wp_rewrite->using_permalinks() ) return add_query_arg(array('src' => 'category_rss'), $url);

		$query = false;
		if ( strpos($url, '?') !== false ) list($url, $query) = explode('?', $url);
		$url = trailingslashit($url) . 'feed';
		if ( $query ) $url = "$url?$query";
			return $url;
	}

	static function has_categories ($result, $options, $O) {
		if ( empty($O->children) && method_exists($O, 'load_children') ) $O->load_children();
		reset($O->children);
		return ( ! empty($O->children) );
	}

	static function has_faceted_menu ($result, $options, $O) {
		if ( ! is_a($O, 'ProductCategory') ) return false;
		if ( empty($O->meta) ) $O->load_meta();
		if ( property_exists($O,'facetedmenus') && Shopp::str_true($O->facetedmenus) ) {
			$O->load_facets();
			return true;
		}
		return false;
	}

	static function has_images ($result, $options, $O) {
		if ( ! is_a($O, 'ProductCategory') ) return false;
		if ( empty($O->images) ) $O->load_images();
		reset($O->images);
		if ( empty($O->images) ) return false;
		return true;
	}

	static function id ($result, $options, $O) {
		if ( isset($O->term_id)) return $O->term_id;
		return false;
	}

	/**
	 * Renders a custom category image
	 *
	 * @see the image() method from theme/catalog.php
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	static function image ($result, $options, $O) {
		if (!self::has_images($result, $options, $O)) return '';
		return ShoppStorefrontThemeAPI::image($result, $options, $O);
	}

	static function images ($result, $options, $O) {
		if (!isset($O->_images_loop)) {
			reset($O->images);
			$O->_images_loop = true;
		} else next($O->images);

		if (current($O->images) !== false) return true;
		else {
			unset($O->_images_loop);
			return false;
		}
	}

	static function is_subcategory ($result, $options, $O) {
		if (isset($options['id'])) return ($this->parent == $options['id']);
		return ($O->parent != 0);
	}

	static function load_products ($result, $options, $O) {
		if (empty($O->id) && empty($O->slug)) return false;
		if (isset($options['load'])) {
			$dataset = explode(",",$options['load']);
			$options['load'] = array();
			foreach ($dataset as $name) {
				if ( 'description' == trim(strtolower($name)) )
					$options['columns'] = 'p.post_content';
				$options['load'][] = trim($name);
			}
		 } else {
			$options['load'] = array('prices');
		}
		if (!$O->loaded) $O->load($options);
		if (count($O->products) > 0) return true; else return false;
	}

	static function name ($result, $options, $O) { return $O->name; }

	static function pagination ($result, $options, $O) {
		if (!$O->paged) return "";

		$defaults = array(
			'label' => __("Pages:","Shopp"),
			'next' => __("next","Shopp"),
			'previous' => __("previous","Shopp"),
			'jumpback' => '&laquo;',
			'jumpfwd' => '&raquo;',
			'show' => 1000,
			'before' => '<div>',
			'after' => '</div>'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$_ = array();
		if (isset($O->alpha) && $O->paged) {
			$_[] = $before.$label;
			$_[] = '<ul class="paging">';
			foreach ($O->alpha as $letter => $products) {
				$link = $O->pagelink($letter);
				if ($products > 0) $_[] = '<li><a href="'.esc_url($link).'">'.$letter.'</a></li>';
				else $_[] = '<li><span>'.$letter.'</span></li>';
			}
			$_[] = '</ul>';
			$_[] = $after;
			return join("\n",$_);
		}

		if ($O->pages > 1) {

			if ( $O->pages > $show ) $visible_pages = $show + 1;
			else $visible_pages = $O->pages + 1;
			$jumps = ceil($visible_pages/2);
			$_[] = $before.$label;

			$_[] = '<ul class="paging">';
			if ( $O->page <= floor(($show) / 2) ) {
				$i = 1;
			} else {
				$i = $O->page - floor(($show) / 2);
				$visible_pages = $O->page + floor(($show) / 2) + 1;
				if ($visible_pages > $O->pages) $visible_pages = $O->pages + 1;
				if ($i > 1) {
					$link = $O->pagelink(1);
					$_[] = '<li><a href="'.esc_url($link).'">1</a></li>';

					$pagenum = ($O->page - $jumps);
					if ($pagenum < 1) $pagenum = 1;
					$link = $O->pagelink($pagenum);
					$_[] = '<li><a href="'.esc_url($link).'">'.$jumpback.'</a></li>';
				}
			}

			// Add previous button
			if (!empty($previous) && $O->page > 1) {
				$prev = $O->page-1;
				$link = $O->pagelink($prev);
				$_[] = '<li class="previous"><a href="'.esc_url($link).'">'.$previous.'</a></li>';
			} else $_[] = '<li class="previous disabled">'.$previous.'</li>';
			// end previous button

			while ($i < $visible_pages) {
				$link = $O->pagelink($i);
				if ( $i == $O->page ) $_[] = '<li class="active">'.$i.'</li>';
				else $_[] = '<li><a href="'.esc_url($link).'">'.$i.'</a></li>';
				$i++;
			}
			if ($O->pages > $visible_pages) {
				$pagenum = ($O->page + $jumps);
				if ($pagenum > $O->pages) $pagenum = $O->pages;
				$link = $O->pagelink($pagenum);
				$_[] = '<li><a href="'.esc_url($link).'">'.$jumpfwd.'</a></li>';
				$link = $O->pagelink($O->pages);
				$_[] = '<li><a href="'.esc_url($link).'">'.$O->pages.'</a></li>';
			}

			// Add next button
			if (!empty($next) && $O->page < $O->pages) {
				$pagenum = $O->page+1;
				$link = $O->pagelink($pagenum);
				$_[] = '<li class="next"><a href="'.esc_url($link).'">'.$next.'</a></li>';
			} else $_[] = '<li class="next disabled">'.$next.'</li>';

			$_[] = '</ul>';
			$_[] = $after;
		}
		return join("\n",$_);
	}

	static function parent ($result, $options, $O) { return isset($O->parent) ? $O->parent : false;  }

	static function products ($result, $options, $O) {
		if ( isset($options['looping']) ) return isset($O->_product_loop);

		$null = null;
		if (!isset($O->_product_loop)) {
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

		if (current($O->products) !== false) return true;
		else {
			unset($O->_product_loop);
			ShoppProduct($null);
			if ( is_a(ShoppStorefront()->Requested, 'Product') ) ShoppProduct(ShoppStorefront()->Requested);
			$O->_pindex = 0;
			return false;
		}
	}

	static function row ($result, $options, $O) {
		$Shopp = Shopp::object();
		if (!isset($O->_rindex) || $O->_rindex === false) $O->_rindex = 0;
		else $O->_rindex++;
		if (empty($options['products'])) $options['products'] = shopp_setting('row_products');
		if (isset($O->_rindex) && $O->_rindex > 0 && $O->_rindex % $options['products'] == 0) return true;
		else return false;
	}

	static function section_list ($result, $options, $O) {
		if (!isset($O->id) || empty($O->id)) return false;
		$options['section'] = true;
		return ShoppStorefrontThemeAPI::category_list($result, $options, $O);
	}

	static function slideshow ($result, $options, $O) {
		$options['load'] = array('images');
		if (!$O->loaded) $O->load($options);
		if (count($O->products) == 0) return false;

		$defaults = array(
			'fx' => 'fade',
			'duration' => 1000,
			'delay' => 7000,
			'order' => 'normal'
		);
		$imgdefaults = array(
			'setting' => false,
			'width' => '580',
			'height' => '200',
			'size' => false,
			'fit' => 'crop',
			'sharpen' => false,
			'quality' => false,
			'bg' => false,
		);

		$options = array_merge($defaults,$imgdefaults,$options);
		extract($options, EXTR_SKIP);

		$href = Shopp::url('' != get_option('permalink_structure')?trailingslashit('000'):'000','images');
		$imgsrc = add_query_string("$width,$height",$href);

		$string = '<ul class="slideshow '.$fx.'-fx '.$order.'-order duration-'.$duration.' delay-'.$delay.'">';
		$string .= '<li class="clear"><img src="'.$imgsrc.'" width="'.$width.'" height="'.$height.'" /></li>';
		foreach ($O->products as $Product) {
			if (empty($Product->images)) continue;
			$string .= '<li><a href="'.$Product->tag('url').'">';
			$imgoptions = array_filter(array_intersect_key($options,$imgdefaults));
			$string .= shopp($Product,'get-image',$imgoptions);
			$string .= '</a></li>';
		}
		$string .= '</ul>';
		return $string;
	}

	static function slug ($result, $options, $O) {
		if (isset($O->slug)) return urldecode($O->slug);
		return false;
	}

	static function subcategories ($result, $options, $O) {
		if (!isset($O->_children_loop)) {
			reset($O->children);
			$O->child = current($O->children);
			$O->_cindex = 0;
			$O->_children_loop = true;
		} else {
			$O->child = next($O->children);
			$O->_cindex++;
		}

		if ($O->child !== false) return true;
		else {
			unset($O->_children_loop);
			$O->_cindex = 0;
			$O->child = false;
			return false;
		}
	}

	static function subcategory_list ($result, $options, $O) {
		if (!isset($O->id) || empty($O->id)) return false;
		$options['childof'] = $O->id;
		$options['default'] = __('Select a sub-category&hellip;','Shopp');
		return ShoppStorefrontThemeAPI::category_list($result, $options, $O);
	}

	static function total ($result, $options, $O) { return $O->loaded?$O->total:false; }

	static function url ($result, $options, $O) {
		global $ShoppTaxonomies, $wp_rewrite;
		if ( property_exists($O,'id') && $O->id && isset($O->taxonomy) && ! in_array($O->taxonomy, array_keys($ShoppTaxonomies)) )
			return get_term_link( (int) $O->id, $O->taxonomy);

		$namespace = get_class_property( get_class($O) ,'namespace');
		$prettyurls = $wp_rewrite->using_permalinks();

		$url = Shopp::url( $prettyurls ? "$namespace/$O->slug" : array($O->taxonomy => $O->slug),false );
		if (isset($options['page'])) $url = $O->pagelink((int)$options['page']);
		return $url;
	}

}
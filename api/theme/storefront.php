<?php
/**
* ShoppStorefrontThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppStorefrontThemeAPI
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

add_filter('shopp_themeapi_context_name', array('ShoppStorefrontThemeAPI', '_context_name'));

class ShoppStorefrontThemeAPI implements ShoppAPI {
	static $register = array(
		'breadcrumb' => 'breadcrumb',
		'businessname' => 'business_name',
		'businessaddress' => 'business_address',
		'categories' => 'categories',
		'category' => 'category',
		'collection' => 'category',
		'categorylist' => 'category_list',
		'display' => 'type',
		'errors' => 'errors',
		'type' => 'type',
		'hascategories' => 'has_categories',
		'isaccount' => 'is_account',
		'iscart' => 'is_cart',
		'iscategory' => 'is_taxonomy', // @deprecated in favor of istaxonomy
		'istaxonomy' => 'is_taxonomy',
		'iscollection' => 'is_collection',
		'ischeckout' => 'is_checkout',
		'islanding' => 'is_frontpage',
		'isfrontpage' => 'is_frontpage',
		'iscatalog' => 'is_catalog',
		'isproduct' => 'is_product',
		'orderbylist' => 'orderby_list',
		'product' => 'product',
		'recentshoppers' => 'recent_shoppers',
		'search' => 'search',
		'searchform' => 'search_form',
		'sideproduct' => 'side_product',
		'tagproducts' => 'tag_products',
		'tagcloud' => 'tag_cloud',
		'url' => 'url',
		'views' => 'views',
		'zoomoptions' => 'zoom_options',

		'accountmenu' => 'account_menu',
		'accountmenuitem' => 'account_menuitem',

	);

	static function _apicontext () { return 'storefront'; }

	static function _context_name ( $name ) {
		switch ( $name ) {
			case 'storefront':
			case 'catalog':
			return 'storefront';
			break;
		}
		return $name;
	}

	/**
	 * _setobject - returns the global context object used in the shopp('product') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'ShoppCatalog') ) return $Object;

		switch ( strtolower($object) ) {
			case 'storefront':
			case 'catalog':
				return ShoppCatalog();
				break;
		}

		return $Object; // not mine, do nothing
	}

	static function image ($result, $options, $O) {
		// Compatibility defaults
		$_size = 96;
		$_width = shopp_setting('gallery_thumbnail_width');
		$_height = shopp_setting('gallery_thumbnail_height');
		if (!$_width) $_width = $_size;
		if (!$_height) $_height = $_size;

		$defaults = array(
			'img' => false,
			'id' => false,
			'index' => false,
			'class' => '',
			'setting' => '',
			'width' => false,
			'height' => false,
			'size' => false,
			'fit' => false,
			'sharpen' => false,
			'quality' => false,
			'bg' => false,
			'alt' => '',
			'title' => '',
			'zoom' => '',
			'zoomfx' => 'shopp-zoom',
			'property' => false
		);

		// Populate defaults from named image settings to allow specific overrides
		if (!empty($options['setting'])) {
			$setting = $options['setting'];
			$ImageSettings = ImageSettings::__instance();
			$settings = $ImageSettings->get($setting);
			if ($settings) $defaults = array_merge($defaults,$settings->options());
		}

		$options = array_merge($defaults,$options);
		extract($options);

		// Select image by database id
		if ($id !== false) {
			if (isset($O->images[$id])) $img = $O->images[$id];
			else {
				shopp_debug( sprintf('No %s image exists at with the specified database ID of %d.', get_class($O), $id) );
				return '';
			}
		}

		// Select image by index position in the list
		if ($index !== false){
			$keys = array_keys($O->images);
			if( isset($keys[$index]) && isset($O->images[ $keys[$index] ]) )
				$img = $O->images[$keys[$index]];
			else {
				shopp_debug( sprintf('No %s image exists at the specified index position %d.', get_class($O), $id) );
				return '';
			}
		}

		// Use the current image pointer by default
		if (!$img) $img = current($O->images);

		if ($size !== false) $width = $height = $size;
		if (!$width) $width = $_width;
		if (!$height) $height = $_height;

		$scale = $fit?array_search($fit,$img->_scaling):false;
		$sharpen = $sharpen?min($sharpen,$img->_sharpen):false;
		$quality = $quality?min($quality,$img->_quality):false;
		$fill = $bg?hexdec(ltrim($bg,'#')):false;
		if ('transparent' == strtolower($bg)) $fill = -1;

		list($width_a,$height_a) = array_values($img->scaled($width,$height,$scale));
		if ($size == "original") {
			$width_a = $img->width;
			$height_a = $img->height;
		}
		if ($width_a === false) $width_a = $width;
		if ($height_a === false) $height_a = $height;

		$alt = esc_attr(empty($alt)?(empty($img->alt)?$img->name:$img->alt):$alt);
		$title = empty($title)?$img->title:$title;
		$titleattr = empty($title)?'':' title="'.esc_attr($title).'"';
		$classes = empty($class)?'':' class="'.esc_attr($class).'"';

		$src = Shopp::url($img->id,'images');
		if ('' != get_option('permalink_structure')) $src = trailingslashit($src).$img->filename;

		if ($size != "original")
			$src = $img->resized_url($width, $height, $scale, $sharpen, $quality, $fill);

		switch (strtolower($property)) {
			case "id": return $img->id; break;
			case "url":
			case "src": return $src; break;
			case "title": return $title; break;
			case "alt": return $alt; break;
			case "width": return $width_a; break;
			case "height": return $height_a; break;
			case "class": return $class; break;
		}

		$imgtag = '<img src="'.$src.'"'.$titleattr.' alt="'.$alt.'" width="'.$width_a.'" height="'.$height_a.'" '.$classes.' />';

		if (Shopp::str_true($zoom))
			return '<a href="'.Shopp::url($img->id,'images').'/'.$img->filename.'" class="'.$zoomfx.'" rel="product-'.$O->id.'"'.$titleattr.'>'.$imgtag.'</a>';

		return $imgtag;
	}

	static function breadcrumb ($result, $options, $O) {
		$Shopp = Shopp::object();

		$defaults = array(
			'separator' => '&nbsp;&raquo; ',
			'depth'		=> 7,

			'wrap' 		=> '<ul class="breadcrumb">',
			'endwrap' 	=> '</ul>',
			'before'	=> '<li>',
			'after'		=> '</li>'

		);

		$options = array_merge($defaults,$options);
		extract($options);

		$linked = $before.'%2$s<a href="%3$s">%1$s</a>'.$after;
		$list = $before.'%2$s<strong>%1$s</strong>'.$after;

		$CatalogPage = shopp_get_page('catalog');

		$Storefront = ShoppStorefront();

		// Add the Store front page (aka catalog page)
		$breadcrumb = array( $CatalogPage->title() => Shopp::url(false, 'catalog') );

		if ( is_account_page() ) {
			$Page = shopp_get_page('account');

			$breadcrumb += array($Page->title() => Shopp::url(false, 'account'));

			$request = $Storefront->account['request'];
			if (isset($Storefront->dashboard[$request]))
				$breadcrumb += array($Storefront->dashboard[$request]->label => Shopp::url(false, 'account'));

		} elseif ( is_cart_page() ) {
			$Page = shopp_get_page('cart');
			$breadcrumb += array($Page->title() => Shopp::url(false, 'cart'));
		} elseif ( is_checkout_page() ) {
			$Cart = shopp_get_page('cart');
			$Checkout = shopp_get_page('checkout');
			$breadcrumb += array($Cart->title() => Shopp::url(false, 'cart'));
			$breadcrumb += array($Checkout->title() => Shopp::url(false, 'checkout'));
		} elseif ( is_confirm_page() ) {
			$Cart = shopp_get_page('cart');
			$Checkout = shopp_get_page('checkout');
			$Confirm = shopp_get_page('confirm');
			$breadcrumb += array($Cart->title() => Shopp::url(false, 'cart'));
			$breadcrumb += array($Checkout->title() => Shopp::url(false, 'checkout'));
			$breadcrumb += array($Confirm->title() => Shopp::url(false, 'confirm'));
		} elseif ( is_thanks_page() ) {
			$Page = shopp_get_page('thanks');
			$breadcrumb += array($Page->title() => Shopp::url(false, 'thanks'));
		} elseif ( is_shopp_taxonomy() ) {
			$taxonomy = ShoppCollection()->taxonomy;
			$ancestors = array_reverse(get_ancestors(ShoppCollection()->id, $taxonomy));
			foreach ($ancestors as $ancestor) {
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
				foreach ($ancestors as $ancestor) {
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
			$trail .= sprintf(($last == $name?$list:$linked), $name, (empty($trail)?'':$separator), $link);

		return $wrap.$trail.$endwrap;
	}

	static function business_name ($result, $options, $O) { return esc_html(shopp_setting('business_name')); }

	static function business_address ($result, $options, $O) { return esc_html(shopp_setting('business_address')); }

	static function categories ($result, $options, $O) {
		$null = null;
		if (!isset($O->_category_loop)) {
			reset($O->categories);
			ShoppCollection(current($O->categories));
			$O->_category_loop = true;
		} else {
			ShoppCollection(next($O->categories));
		}

		if (current($O->categories) !== false) return true;
		else {
			unset($O->_category_loop);
			reset($O->categories);
			ShoppCollection($null);
			if ( is_a(ShoppStorefront()->Requested, 'ProductCollection') ) ShoppCollection(ShoppStorefront()->Requested);
			return false;
		}
	}

	static function category ($result, $options, $O) {
		$Shopp = Shopp::object();
		$Storefront = ShoppStorefront();
		$reset = null;
		if (isset($options['name'])) ShoppCollection( new ProductCategory($options['name'],'name') );
		else if (isset($options['slug'])) ShoppCollection( new ProductCategory($options['slug'],'slug') );
		else if (isset($options['id'])) ShoppCollection( new ProductCategory($options['id']) );

		if (isset($options['reset']))
			return ( is_a($Storefront->Requested, 'ProductCollection') ? ( ShoppCollection($Storefront->Requested) ) : ShoppCollection($reset) );
		if (isset($options['title'])) ShoppCollection()->name = $options['title'];
		if (isset($options['show'])) ShoppCollection()->loading['limit'] = $options['show'];
		if (isset($options['pagination'])) ShoppCollection()->loading['pagination'] = $options['pagination'];
		if (isset($options['order'])) ShoppCollection()->loading['order'] = $options['order'];
		if (isset($options['taxquery'])) ShoppCollection()->loading['taxquery'] = $options['taxquery'];

		if (isset($options['load'])) return true;
		if (isset($options['controls']) && !Shopp::str_true($options['controls']))
			ShoppCollection()->controls = false;
		if (isset($options['view'])) {
			if ($options['view'] == "grid") ShoppCollection()->view = "grid";
			else ShoppCollection()->view = "list";
		}

		ob_start();
		$templates = array('category.php','collection.php');
		$ids = array('slug','id');
		foreach ($ids as $property) {
			if (isset(ShoppCollection()->$property)) $id = ShoppCollection()->$property;
			array_unshift($templates,'category-'.$id.'.php','collection-'.$id.'.php');
		}
		locate_shopp_template($templates, true);
		$content = ob_get_contents();
		ob_end_clean();

		// Reset the current collection to previously requested collection or empty it
		if ( is_a($Storefront->Requested, 'ProductCollection') ) ShoppCollection($Storefront->Requested);
		else ShoppCollection($reset);

		if (isset($options['wrap']) && Shopp::str_true($options['wrap'])) $content = Storefront::wrapper($content);

		return $content;
	}

	static function category_list ($result, $options, $O) {
		$defaults = array(
			'title' => '',
			'before' => '',
			'after' => '',
			'class' => '',
			'exclude' => '',
			'orderby' => 'name',
			'order' => 'ASC',
			'depth' => 0,
			'level' => 0,
			'childof' => 0,
			'section' => false,
			'parent' => false,
			'showall' => false,
			'linkall' => false,
			'linkcount' => false,
			'dropdown' => false,
			'default' => __('Select category&hellip;','Shopp'),
			'hierarchy' => false,
			'products' => false,
			'wraplist' => true,
			'showsmart' => false
			);

		$options = array_merge($defaults,$options);
		extract($options, EXTR_SKIP);

		$taxonomy = ProductCategory::$taxon;
		$termargs = array('hide_empty' => 0,'fields'=>'id=>parent','orderby'=>$orderby,'order'=>$order);

		$baseparent = 0;
		if (Shopp::str_true($section)) {
			if (!isset(ShoppCollection()->id)) return false;
			$sectionterm = ShoppCollection()->id;
			if (ShoppCollection()->parent == 0) $baseparent = $sectionterm;
			else $baseparent = end(get_ancestors($sectionterm,$taxonomy));
		}

		if (0 != $childof) $termargs['child_of'] = $baseparent = $childof;

		$O->categories = array(); $count = 0;
		$terms = get_terms( $taxonomy, $termargs );
		$children = _get_term_hierarchy($taxonomy);
		ProductCategory::tree($taxonomy,$terms,$children,$count,$O->categories,1,0,$baseparent);
		if ($showsmart == "before" || $showsmart == "after")
			$O->collections($showsmart);
		$categories = $O->categories;

		if (empty($categories)) return '';

		$string = "";
		if ($depth > 0) $level = $depth;
		$levellimit = $level;
		$exclude = explode(",",$exclude);
		$classes = ' class="shopp-categories-menu'.(empty($class)?'':' '.$class).'"';
		$wraplist = Shopp::str_true($wraplist);
		$hierarchy = Shopp::str_true($hierarchy);

		if (Shopp::str_true($dropdown)) {
			if (!isset($default)) $default = __('Select category&hellip;','Shopp');
			$string .= $title;
			$string .= '<form action="/" method="get" class="category-list-menu"><select name="shopp_cats" '.$classes.'>';
			$string .= '<option value="">'.$default.'</option>';
			foreach ($categories as &$category) {
				$link = $padding = $total = '';
				if ( ! isset($category->smart) ) {
					// If the parent of this category was excluded, add this to the excludes and skip
					if (!empty($category->parent) && in_array($category->parent,$exclude)) {
						$exclude[] = $category->id;
						continue;
					}
					if (!empty($category->id) && in_array($category->id,$exclude)) continue; // Skip excluded categories
					if ($category->count == 0 && !isset($category->smart) && !$category->_children && ! Shopp::str_true($showall)) continue; // Only show categories with products
					if ($levellimit && $category->level >= $levellimit) continue;

					if ($hierarchy && $category->level > $level) {
						$parent = &$previous;
						if (!isset($parent->path)) $parent->path = '/'.$parent->slug;
					}

					if ($hierarchy)
						$padding = str_repeat("&nbsp;",$category->level*3);
					$term_id = $category->term_id;
					$link = get_term_link( (int) $category->term_id, $category->taxonomy);
					if (is_wp_error($link)) $link = '';

					$total = '';
					if ( Shopp::str_true($products) && $category->count > 0) $total = ' ('.$category->count.')';
				} else {
					$category->level = 1;
					$namespace = get_class_property( 'SmartCollection' ,'namespace');
					$taxonomy = get_class_property( 'SmartCollection' ,'taxon');
					$prettyurls = ( '' != get_option('permalink_structure') );
					$link = Shopp::url( $prettyurls ? "$namespace/{$category->slug}" : array($taxonomy=>$category->slug),false );
				}
				$string .=
					'<option value="'.$link.'">'.$padding.$category->name.$total.'</option>';
				$previous = &$category;
				$level = $category->level;
			}

			$string .= '</select></form>';
		} else {
			$depth = 0;

			$string .= $title;
			if ($wraplist) $string .= '<ul'.$classes.'>';
			$Collection = ShoppCollection();
			foreach ($categories as &$category) {
				if (!isset($category->count)) $category->count = 0;
				if (!isset($category->level)) $category->level = 0;

				// If the parent of this category was excluded, add this to the excludes and skip
				if (!empty($category->parent) && in_array($category->parent,$exclude)) {
					$exclude[] = $category->id;
					continue;
				}

				if (!empty($category->id) && in_array($category->id,$exclude)) continue; // Skip excluded categories
				if ($levellimit && $category->level >= $levellimit) continue;
				if ($hierarchy && $category->level > $depth) {
					$parent = &$previous;
					if (!isset($parent->path)) $parent->path = $parent->slug;
					if (substr($string,-5,5) == "</li>") // Keep everything but the
						$string = substr($string,0,-5);  // last </li> to re-open the entry
					$active = '';


					if ( $Collection && property_exists($Collection,'parent') && $Collection->parent == $parent->id ) $active = ' active';

					$subcategories = '<ul class="children'.$active.'">';
					$string .= $subcategories;
				}

				if ($hierarchy && $category->level < $depth) {
					for ($i = $depth; $i > $category->level; $i--) {
						if (substr($string,strlen($subcategories)*-1) == $subcategories) {
							// If the child menu is empty, remove the <ul> to avoid breaking standards
							$string = substr($string,0,strlen($subcategories)*-1).'</li>';
						} else $string .= '</ul></li>';
					}
				}

				if ( ! isset($category->smart) ) {
					$link = get_term_link( (int) $category->term_id,$category->taxonomy);
					if (is_wp_error($link)) $link = '';
				} else {
					$namespace = get_class_property( 'SmartCollection' ,'namespace');
					$taxonomy = get_class_property( 'SmartCollection' ,'taxon');
					$prettyurls = ( '' != get_option('permalink_structure') );
					$link = Shopp::url( $prettyurls ? "$namespace/{$category->slug}" : array($taxonomy=>$category->slug),false );
				}

				$total = '';
				if ( Shopp::str_true($products) && $category->count > 0 ) $total = ' <span>('.$category->count.')</span>';

				$classes = array();
				if (isset($Collection->slug) && $Collection->slug == $category->slug)
					$classes[] = 'current';

				if (isset($Collection->parent) && $Collection->parent == $category->id)
					$classes[] = 'current-parent';

				if ( empty($classes) ) $classes = '';
				else $classes = ' class="'.join(' ',$classes).'"';

				$listing = '';

				if (!empty($link) && ($category->count > 0 || isset($category->smart) || Shopp::str_true($linkall)))
					$listing = '<a href="'.$link.'"'.$classes.'>'.esc_html($category->name).($linkcount?$total:'').'</a>'.(!$linkcount?$total:'');
				else $listing = $category->name;

				if (Shopp::str_true($showall) ||
					$category->count > 0 ||
					isset($category->smart) ||
					$category->_children)
					$string .= '<li'.$classes.'>'.$listing.'</li>';

				$previous = &$category;
				$depth = $category->level;
			}
			if ($hierarchy && $depth > 0)
				for ($i = $depth; $i > 0; $i--) {
					if (substr($string,strlen($subcategories)*-1) == $subcategories) {
						// If the child menu is empty, remove the <ul> to avoid breaking standards
						$string = substr($string,0,strlen($subcategories)*-1).'</li>';
					} else $string .= '</ul></li>';
				}
			if ($wraplist) $string .= '</ul>';
		}
		return $before.$string.$after;
		break;
	}

	static function errors ($result, $options, $O) {

		$Errors = ShoppErrorStorefrontNotices();
		if ( ! $Errors->exist() ) return false;

		$defaults = array(
			'before' => '<li>',
			'after' => '</li>'
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$result = '';
		while ( $Errors->exist() )
			$result .=  $before . $Errors->message() . $after;

		return $result;
	}

	static function type ($result, $options, $O) { return $O->type; }

	static function has_categories ($result, $options, $O) {
		$showsmart = isset($options['showsmart'])?$options['showsmart']:false;
		if ( empty($O->categories) ) $O->load_categories(array('where'=>'true'),$showsmart);
		else { // Make sure each entry is a valid ProductCollection to prevent fatal errors @bug #2017
			foreach ($O->categories as $id => $term) {
				if (  $Category instanceof ProductCollection ) continue;
				$ProductCategory = new ProductCategory();
				$ProductCategory->populate($term);
				$O->categories[$id] = $ProductCategory;
			}
			reset($O->categories);
			return true;
		}
		reset($O->categories);
		return ( count($O->categories) > 0 );
	}

	static function is_account ($result, $options, $O) { return is_account_page(); }

	static function is_cart ($result, $options, $O) { return is_cart_page(); }

	static function is_catalog ($result, $options, $O) { return is_catalog_page(); }

	static function is_checkout ($result, $options, $O) { return is_checkout_page(); }

	static function is_collection ($result, $options, $O) { return is_shopp_collection(); }

	static function is_frontpage ($result, $options, $O) { return is_catalog_frontpage(); }

	static function is_product ($result, $options, $O) { return is_shopp_product(); }

	static function is_taxonomy ($result, $options, $O) { return is_shopp_taxonomy(); }

	static function orderby_list ($result, $options, $O) {

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
		foreach ($defaultsort as $setting)
			if ( ! empty($setting)) $default = $setting;

		// Setup defaults
		$options = wp_parse_args($options,array(
			'dropdown' => false,
			'default' => $default,
			'title' => ''
		));
		extract($options,EXTR_SKIP);

		// Get the sort option labels
		$menuoptions = ProductCategory::sortoptions();
		// Don't show custom product order for smart categories
		if ($Collection->smart) unset($menuoptions['custom']);

		$_ = array();
		$request = $_SERVER['REQUEST_URI'];
		if ( Shopp::str_true($dropdown) ) {
			$_[] = $title;
			$_[] = '<form action="'.esc_url($request).'" method="get" id="shopp-'.$Collection->slug.'-orderby-menu">';
			if ( '' == get_option('permalink_structure') ) {
				foreach ($_GET as $key => $value)
					if ($key != 'sort') $_[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			$_[] = '<select name="sort" class="shopp-orderby-menu">';
			$_[] = menuoptions($menuoptions,$default,true);
			$_[] = '</select>';
			$_[] = '</form>';
		} else {
			foreach($menuoptions as $value => $label) {
				$href = esc_url(add_query_arg(array('sort' => $value),$request));
				$class = ($default == $value?' class="current"':'');
				$_[] = '<li><a href="'.$href.'"'.$class.'>'.$label.'</a></li>';
			}
		}

		return join('',$_);
	}

	static function product ($result, $options, $O) {
		$Shopp = Shopp::object();
		$Storefront = ShoppStorefront();

		if (isset($options['name'])) ShoppProduct(new Product($options['name'],'name'));
		else if (isset($options['slug'])) ShoppProduct(new Product($options['slug'],'slug'));
		else if (isset($options['id'])) ShoppProduct(new Product($options['id']));

		if (isset($options['reset']))
			return ( $Storefront->Requested && is_a($Storefront->Requested, 'Product') ? ShoppProduct($Storefront->Requested) : false );

		if (isset(ShoppProduct()->id) && isset($Shopp->Category->slug)) {
			$Category = clone($Shopp->Category);

			if (isset($options['load'])) {
				if ($options['load'] == "next") ShoppProduct($Category->adjacent_product(1));
				elseif ($options['load'] == "previous") ShoppProduct($Category->adjacent_product(-1));
			} else {
				if (isset($options['next']) && Shopp::str_true($options['next']))
					ShoppProduct($Category->adjacent_product(1));
				elseif (isset($options['previous']) && Shopp::str_true($options['previous']))
					ShoppProduct($Category->adjacent_product(-1));
			}
		}

		if (isset($options['load'])) return true;

		$Product = ShoppProduct();

		// Expand base template file names to support product-id and product-slug specific versions
		// product-id templates will be highest priority, followed by slug versions and the generic names
		$templates = isset($options['template']) ? $options['template'] : array('product.php');
		if (!is_array($templates)) $templates = explode(',',$templates);

		$idslugs = array();
		$reversed = array_reverse($templates);
		foreach ($reversed as $template) {
			list($basename,$php) = explode('.',$template);
			if (!empty($Product->slug)) array_unshift($idslugs,"$basename-$Product->slug.$php");
			if (!empty($Product->id)) array_unshift($idslugs,"$basename-$Product->id.$php");
		}
		$templates = array_merge($idslugs,$templates);

		ob_start();
		locate_shopp_template($templates,true);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	static function recent_shoppers ($result, $options, $O) {
		$defaults = array(
			'abbr' => 'firstname',
			'city' => true,
			'state' => true,
			'avatar' => true,
			'size' => 48,
			'show' => 5
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$pt = DatabaseObject::tablename(Purchase::$table);
		$shoppers = DB::query("SELECT firstname,lastname,email,city,state FROM $pt AS pt GROUP BY customer ORDER BY created DESC LIMIT $show",'array');

		if (empty($shoppers)) return '';

		$_ = array();
		$_[] = '<ul>';
		foreach ($shoppers as $shopper) {
			if ('' == $shopper->firstname.$shopper->lastname) continue;
			if ('lastname' == $abbr) $name = "$shopper->firstname ".$shopper->lastname{0}.".";
			else $name = $shopper->firstname{0}.". $shopper->lastname";

			$img = '';
			if ($avatar) $img = get_avatar($shopper->email,$size,'',$name);

			$loc = '';
			if ($state || $province) $loc = $shopper->state;
			if ($city) $loc = "$shopper->city, $loc";

			$_[] = "<li><div>$img</div>$name <em>$loc</em></li>";
		}
		$_[] = '</ul>';

		return join('',$_);
	}

	static function search ($result, $options, $O) {
		$Storefront =& ShoppStorefront();
		global $wp;

		$defaults = array(
			'type' => 'hidden',
			'option' => 'shopp',
			'blog_option' => __('Search the blog','Shopp'),
			'shop_option' => __('Search the shop','Shopp'),
			'label_before' => '',
			'label_after' => '',
			'checked' => false
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$searching = is_search(); // Flag when searching (the blog or shopp)
		$shopsearch = ($Storefront !== false && $Storefront->searching); // Flag when searching shopp

		$allowed = array('accesskey','alt','checked','class','disabled','format', 'id',
			'minlength','maxlength','readonly','required','size','src','tabindex','title','value');

		$options['value'] = ($option == 'shopp');

		// Reset the checked option
		unset($options['checked']);

		// If searching the blog, check the non-store search option
		if ($searching && !$shopsearch && $option != 'shopp') $options['checked'] = 'checked';

		// If searching the storefront, mark the store search option
		if ($shopsearch && $option == 'shopp') $options['checked'] = 'checked';

		// Override any other settings with the supplied default 'checked' option
		if (!$searching && $checked) $options['checked'] = $checked;

		switch ($type) {
			case 'checkbox':
				$input =  '<input type="checkbox" name="s_cs"'.inputattrs($options,$allowed).' />';
				break;
			case 'radio':
				$input =  '<input type="radio" name="s_cs"'.inputattrs($options,$allowed).' />';
				break;
			case 'menu':
				$allowed = array('accesskey','alt','class','disabled','format', 'id',
					'readonly','required','size','tabindex','title');

				$input = '<select name="s_cs"'.inputattrs($options,$allowed).'>';
				$input .= '<option value="">'.$blog_option.'</option>';
				$input .= '<option value="1"'.($shopsearch || (!$searching && $option == 'shopp')?' selected="selected"':'').'>'.$shop_option.'</option>';
				$input .= '</select>';
				break;
			default:
				$allowed = array('alt','class','disabled','format','id','readonly','title','value');
				$input =  '<input type="hidden" name="s_cs"'.inputattrs($options,$allowed).' />';
				break;
		}

		$before = (!empty($label_before))?'<label>'.$label_before:'<label>';
		$after = (!empty($label_after))?$label_after.'</label>':'</label>';
		return $before.$input.$after;
	}

	static function search_form ($result, $options, $O) {
		ob_start();
		get_search_form();
		$content = ob_get_contents();
		ob_end_clean();

		preg_match('/^(.*?<form[^>]*>)(.*?)(<\/form>.*?)$/is',$content,$_);
		list($all,$open,$content,$close) = $_;

		$markup = array(
			$open,
			$content,
			'<div><input type="hidden" name="s_cs" value="true" /></div>',
			$close
		);

		return join('',$markup);
	}

	static function side_product ($result, $options, $O) {
		$Shopp = Shopp::object();

		$content = false;
		$source = isset($options['source'])?$options['source']:'product';
		if ($source == 'product' && isset($options['product'])) {
			 // Save original requested product
			if ($Shopp->Product) $Requested = $Shopp->Product;
			$products = explode(',',$options['product']);
			if (!is_array($products)) $products = array($products);
			foreach ($products as $product) {
				$product = trim($product);
				if (empty($product)) continue;
				if (preg_match('/^\d+$/',$product))
					$Shopp->Product = new Product($product);
				else $Shopp->Product = new Product($product,'slug');

				if (empty($Shopp->Product->id)) continue;
				if (isset($options['load'])) return true;
				ob_start();
				locate_shopp_template(array('sideproduct-'.$Shopp->Product->id.'.php','sideproduct.php'),true);
				$content .= ob_get_contents();
				ob_end_clean();
			}
			 // Restore original requested Product
			if (!empty($Requested)) $Shopp->Product = $Requested;
			else $Shopp->Product = false;
		}

		if ($source == 'category' && isset($options['category'])) {
			 // Save original requested category
			if ($Shopp->Category) $Requested = $Shopp->Category;
			if ($Shopp->Product) $RequestedProduct = $Shopp->Product;
			if (empty($options['category'])) return false;

			if ( in_array($options['category'],array_keys($Shopp->Collections)) ) {
				$Category = ShoppCatalog::load_collection($options['category'],$options);
				ShoppCollection($Category);
			} elseif ( intval($options['category']) > 0) { // By ID
				ShoppCollection( new ProductCategory($options['category']) );
			} else {
				ShoppCollection( new ProductCategory($options['category'],'slug') );
			}

			if (isset($options['load'])) return true;

			$options['load'] = array('coverimages');
			ShoppCollection()->load($options);

			$template = locate_shopp_template(array('sideproduct-'.$Shopp->Category->slug.'.php','sideproduct.php'));
			ob_start();
			foreach (ShoppCollection()->products as &$product) {
				ShoppProduct($product);
				load_template($template,false);
			}
			$content = ob_get_contents();
			ob_end_clean();

			 // Restore original requested category
			if (!empty($Requested)) $Shopp->Category = $Requested;
			else $Shopp->Category = false;
			if (!empty($RequestedProduct)) $Shopp->Product = $RequestedProduct;
			else $Shopp->Product = false;
		}

		return $content;
	}

	static function tag_products ($result, $options, $O) {
		ShoppCollection( new TagProducts($options) );
		return self::category($result, $options, $O);
	}

	static function tag_cloud ($result, $options, $O) {
		$defaults = array(
			'orderby' => 'name',
			'order' => false,
			'number' => 45,
			'levels' => 7,
			'format' => 'list',
			'link' => 'view'
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$tags = get_terms( ProductTag::$taxon, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $number) );

		if ( empty($tags) ) return false;

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
					usort( $tags, create_function('$a, $b', 'return strnatcasecmp($a->name, $b->name);') );
				else
					usort( $tags, create_function('$a, $b', 'return ($a->count > $b->count);') );

				if ( 'DESC' == $order ) $tags = array_reverse( $tags, true );
			}
		}

		// Markup
		if ( 'inline' == $format ) $markup = '<div class="shopp tagcloud">';
		if ( 'list' == $format ) $markup = '<ul class="shopp tagcloud">';
		foreach ( (array)$tags as $tag ) {

			$level = floor( (1 - $tag->count / $max) * $levels )+1;
			if ( 'list' == $format ) $markup .= '<li class="level-' . $level . '">';
			$markup .= '<a href="' . esc_url($tag->link) . '" rel="tag">' . $tag->name . '</a>';
			if ( 'list' == $format ) $markup .= '</li> ';

		}
		if ('list' == $format) $markup .= '</ul>';
		if ('inline' == $format) $markup .= '</div>';

		return $markup;
	}

	static function url ($result, $options, $O) { return Shopp::url(false,'catalog'); }

	static function views ($result, $options, $O) {
		$Shopp = Shopp::object();
		if (isset($Shopp->Category->controls)) return false;
		$string = "";
		$string .= '<ul class="views">';
		if (isset($options['label'])) $string .= '<li>'.$options['label'].'</li>';
		$string .= '<li><button type="button" class="grid"><span></span></button></li>';
		$string .= '<li><button type="button" class="list"><span></span></button></li>';
		$string .= '</ul>';
		return $string;
	}

	static function zoom_options ($result, $options, $O) {
		$defaults = array(				// Colorbox 1.3.15
			'transition' => 'elastic',	// The transition type. Can be set to 'elastic', 'fade', or 'none'.
			'speed' => 350,				// Sets the speed of the fade and elastic transitions, in milliseconds.
			'href' => false,			// This can be used as an alternative anchor URL or to associate a URL for non-anchor elements such as images or form buttons. Example: $('h1').colorbox({href:'welcome.html'})
			'title' => false,			// This can be used as an anchor title alternative for ColorBox.
			'rel' => false,				// This can be used as an anchor rel alternative for ColorBox. This allows the user to group any combination of elements together for a gallery, or to override an existing rel so elements are not grouped together. Example: $('#example a').colorbox({rel:'group1'}) Note: The value can also be set to 'nofollow' to disable grouping.
			'width' => false,			// Set a fixed total width. This includes borders and buttons. Example: '100%', '500px', or 500
			'height' => false,			// Set a fixed total height. This includes borders and buttons. Example: '100%', '500px', or 500
			'innerWidth' => false,		// This is an alternative to 'width' used to set a fixed inner width. This excludes borders and buttons. Example: '50%', '500px', or 500
			'innerHeight' => false,		// This is an alternative to 'height' used to set a fixed inner height. This excludes borders and buttons. Example: '50%', '500px', or 500
			'initialWidth' => 300,		// Set the initial width, prior to any content being loaded.
			'initialHeight' => 100,		// Set the initial height, prior to any content being loaded.
			'maxWidth' => false,		// Set a maximum width for loaded content. Example: '100%', 500, '500px'
			'maxHeight' => false,		// Set a maximum height for loaded content. Example: '100%', 500, '500px'
			'scalePhotos' => true,		// If 'true' and if maxWidth, maxHeight, innerWidth, innerHeight, width, or height have been defined, ColorBox will scale photos to fit within the those values.
			'scrolling' => true,		// If 'false' ColorBox will hide scrollbars for overflowing content. This could be used on conjunction with the resize method (see below) for a smoother transition if you are appending content to an already open instance of ColorBox.
			'iframe' => false,			// If 'true' specifies that content should be displayed in an iFrame.
			'inline' => false,			// If 'true' a jQuery selector can be used to display content from the current page. Example:  $('#inline').colorbox({inline:true, href:'#myForm'});
			'html' => false,			// This allows an HTML string to be used directly instead of pulling content from another source (ajax, inline, or iframe). Example: $.colorbox({html:'<p>Hello</p>'});
			'photo' => false,			// If true, this setting forces ColorBox to display a link as a photo. Use this when automatic photo detection fails (such as using a url like 'photo.php' instead of 'photo.jpg', 'photo.jpg#1', or 'photo.jpg?pic=1')
			'opacity' => 0.85,			// The overlay opacity level. Range: 0 to 1.
			'open' => false,			// If true, the lightbox will automatically open with no input from the visitor.
			'returnFocus' => true,		// If true, focus will be returned when ColorBox exits to the element it was launched from.
			'preloading' => true,		// Allows for preloading of 'Next' and 'Previous' content in a shared relation group (same values for the 'rel' attribute), after the current content has finished loading. Set to 'false' to disable.
			'overlayClose' => true,		// If false, disables closing ColorBox by clicking on the background overlay.
			'escKey' => true, 			// If false, will disable closing colorbox on esc key press.
			'arrowKey' => true, 		// If false, will disable the left and right arrow keys from navigating between the items in a group.
			'loop' => true, 			// If false, will disable the ability to loop back to the beginning of the group when on the last element.
			'slideshow' => false, 		// If true, adds an automatic slideshow to a content group / gallery.
			'slideshowSpeed' => 2500, 	// Sets the speed of the slideshow, in milliseconds.
			'slideshowAuto' => true, 	// If true, the slideshow will automatically start to play.

			'slideshowStart' => __('start slideshow','Shopp'),	// Text for the slideshow start button.
			'slideshowStop' => __('stop slideshow','Shopp'),	// Text for the slideshow stop button
			'previous' => __('previous','Shopp'), 				// Text for the previous button in a shared relation group (same values for 'rel' attribute).
			'next' => __('next','Shopp'), 						// Text for the next button in a shared relation group (same values for 'rel' attribute).
			'close' => __('close','Shopp'),						// Text for the close button. The 'Esc' key will also close ColorBox.

			// Text format for the content group / gallery count. {current} and {total} are detected and replaced with actual numbers while ColorBox runs.
			'current' => sprintf(__('image %s of %s','Shopp'),'{current}','{total}'),

			'onOpen' => false,			// Callback that fires right before ColorBox begins to open.
			'onLoad' => false,			// Callback that fires right before attempting to load the target content.
			'onComplete' => false,		// Callback that fires right after loaded content is displayed.
			'onCleanup' => false,		// Callback that fires at the start of the close process.
			'onClosed' => false			// Callback that fires once ColorBox is closed.
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
		foreach ($options as $name => &$value)
			if ( in_array($name, $booleans) ) $value = Shopp::str_true($value);

		$js = 'var cbo = '.json_encode($options).';';
		add_storefrontjs($js, true);
	}

	static function account_menu ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		if (!isset($Storefront->_menu_looping)) {
			reset($Storefront->menus);
			$Storefront->_menu_looping = true;
		} else next($Storefront->menus);

		if (current($Storefront->menus) !== false) return true;
		else {
			unset($Storefront->_menu_looping);
			reset($Storefront->menus);
			return false;
		}
	}

	static function account_menuitem ($result, $options, $O) {
		$Storefront = ShoppStorefront();
		$page = current($Storefront->menus);
		if (array_key_exists('url',$options)) return add_query_arg($page->request,'',Shopp::url(false,'account'));
		if (array_key_exists('action',$options)) return $page->request;
		if (array_key_exists('classes',$options)) {
			$classes = array($page->request);
			if ($Storefront->account['request'] == $page->request) $classes[] = 'current';
			return join(' ',$classes);
		}
		if (array_key_exists('current',$options) && $Storefront->account['request'] == $page->request)
			return true;
		return $page->label;
	}


}

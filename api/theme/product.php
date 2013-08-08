<?php
/**
* ShoppProductThemeAPI - Provided theme api tags.
*
* @version 1.0
* @since 1.2
* @package shopp
* @subpackage ShoppProductThemeAPI
*
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

// Default text filters for product Theme API tags
add_filter('shopp_themeapi_product_name','convert_chars');
add_filter('shopp_themeapi_product_summary','convert_chars');
add_filter('shopp_themeapi_product_description', 'wptexturize');
add_filter('shopp_themeapi_product_description', 'convert_chars');
add_filter('shopp_themeapi_product_description', 'wpautop');
add_filter('shopp_themeapi_product_description', 'do_shortcode',11);
add_filter('shopp_themeapi_product_spec', 'wptexturize');
add_filter('shopp_themeapi_product_spec', 'convert_chars');
add_filter('shopp_themeapi_product_spec', 'do_shortcode', 11);

// WP auto embed support
global $wp_embed;
if ( ! is_object($wp_embed) ) $wp_embed = new WP_Embed;

add_filter('shopp_product_spec', array($wp_embed, 'run_shortcode'), 8);
add_filter('shopp_product_spec', array($wp_embed, 'autoembed'), 8);
add_filter('shopp_product_description', array($wp_embed, 'run_shortcode'), 8);
add_filter('shopp_product_description', array($wp_embed, 'autoembed'), 8);

/**
 * Provides shopp('product') template API functionality
 *
 * @author Jonathan Davis, John Dillick
 * @since 1.2
 *
 **/
class ShoppProductThemeAPI implements ShoppAPI {
	static $context = 'Product';
	static $register = array(
		'addon' => 'addon',
		'addons' => 'addons',
		'addtocart' => 'add_to_cart',
		'availability' => 'availability',
		'buynow' => 'buy_now',
		'categories' => 'categories',
		'category' => 'category',
		'coverimage' => 'coverimage',
		'description' => 'description',
		'donation' => 'quantity',
		'amount' => 'quantity',
		'quantity' => 'quantity',
		'found' => 'found',
		'freeshipping' => 'free_shipping',
		'gallery' => 'gallery',
		'hasaddons' => 'has_addons',
		'hascategories' => 'has_categories',
		'hassavings' => 'has_savings',
		'hasvariations' => 'has_variations',
		'hasimages' => 'has_images',
		'hasspecs' => 'has_specs',
		'hastags' => 'has_tags',
		'id' => 'id',
		'image' => 'image',
		'thumbnail' => 'image',
		'images' => 'images',
		'incart' => 'in_cart',
		'incategory' => 'in_category',
		'input' => 'input',
		'isfeatured' => 'is_featured',
		'link' => 'url',
		'url' => 'url',
		'name' => 'name',
		'onsale' => 'on_sale',
		'outofstock' => 'out_of_stock',
		'price' => 'price',
		'saleprice' => 'saleprice',
		'relevance' => 'relevance',
		'savings' => 'savings',
		'schema' => 'schema',
		'slug' => 'slug',
		'spec' => 'spec',
		'specs' => 'specs',
		'summary' => 'summary',
		'sku' => 'sku',
		'stock' => 'stock',
		'tag' => 'tag',
		'tagged' => 'tagged',
		'tags' => 'tags',
		'taxrate' => 'taxrate',
		'type' => 'type',
		'variation' => 'variation',
		'variations' => 'variations',
		'weight' => 'weight'
	);

	static function _apicontext () { return 'product'; }

	/**
	 * _setobject - returns the global context object used in the shopp('product') call
	 *
	 * @author John Dillick
	 * @since 1.2
	 *
	 **/
	static function _setobject ($Object, $object) {
		if ( is_object($Object) && is_a($Object, 'Product') ) return $Object;

		if ( strtolower($object) != 'product' ) return $Object; // not mine, do nothing
		else {
			return ShoppProduct();
		}
	}

	static function addon ($result, $options, $O) {
		$defaults = array(
			'input' => false,
			'units' => 'on',
			'promos' => null,
			'taxes' => null,
		);
		$options = array_merge($defaults,$options);
		extract($options,EXTR_SKIP);


		$defaults = array(
			'separator' => ' ',
			'units' => 'on',
			'promos' => 'on',
			'taxes' => null,
			'input' => null
		);
		$options = array_merge($defaults,$options);
		extract($options,EXTR_SKIP);

		$types = array('hidden','checkbox','radio');

		$addon = current($O->prices);

		$taxrate = shopp_taxrate($taxes,$addon->tax,$O);
		$taxes = is_null($taxes) ? self::_include_tax($O) : Shopp::str_true($taxes);
		if ( ! $taxes ) $taxrate = 0;

		$weightunit = Shopp::str_true($units) ? shopp_setting('weight_unit') : '';

		$_ = array();
		if (array_key_exists('id',$options)) 		$_[] = $addon->id;
		if (array_key_exists('label',$options)) 	$_[] = $addon->label;
		if (array_key_exists('type',$options)) 		$_[] = $addon->type;
		if (array_key_exists('sku',$options)) 		$_[] = $addon->sku;
		if (array_key_exists('price',$options)) 	$_[] = money($addon->price+($addon->price*$taxrate));
		if (array_key_exists('saleprice',$options)) {
			if (Shopp::str_true($promos)) $_[] = money($addon->promoprice+($addon->promoprice*$taxrate));
			else $_[] = money($addon->saleprice+($addon->saleprice*$taxrate));
		}
		if (array_key_exists('stock',$options)) 	$_[] = $addon->stock;
		if (array_key_exists('weight',$options)) 	$_[] = round($addon->weight, 3) . (false !== $weightunit ? " $weightunit" : false);
		if (array_key_exists('shipfee',$options))	$_[] = money(Shopp::floatval($addon->shipfee));
		if (array_key_exists('sale',$options))		return ($addon->sale == "on");
		if (array_key_exists('shipping',$options))	return ($addon->shipping == "on");
		if (array_key_exists('tax',$options))		return ($addon->tax == "on");
		if (array_key_exists('inventory',$options))	return ($addon->inventory == "on");
		if (in_array($input,$types))
			$_[] = '<input type="'.$input.'" name="products['.$O->id.'][addons][]" value="'.$addon->id.'"'.inputattrs($options).' />';

		return join($separator,$_);
	}

	static function addons ($result, $options, $O) {

		// Default mode is: loop
		if (!isset($options['mode'])) {
			if (!isset($O->_addon_loop)) {
				reset($O->prices);
				$O->_addon_loop = true;
			} else next($O->prices);

			$addon = current($O->prices);

			while (false !== $addon && ('N/A' == $addon->type || 'addon' != $addon->context))
				$addon = next($O->prices);

			if (current($O->prices) !== false) return true;
			else {
				$O->_addon_loop = false;
				return false;
			}
		}

		if ( shopp_setting_enabled('inventory') && $O->outofstock ) return false; // Completely out of stock, hide menus
		if (!isset($O->options['a'])) return false; // There are no addons, don't render menus

		$defaults = array(
			'defaults' => '',
			'disabled' => 'show',
			'before_menu' => '',
			'after_menu' => '',
			'mode' => 'menu',
			'label' => true,
			'required' => false,
			'required_error' => __('You must select addon options for this item before you can add it to your shopping cart.','Shopp'),
			'taxes' => null,
			'class' => '',
			);

		$options = array_merge($defaults,$options);
		extract($options);

		$addons = $O->options['a'];
		$idprefix = 'product-addons-';
		if ($required) $class = trim("$class validate");

		$_ = array();
		if ('single' == $mode) {
			if (!empty($before_menu)) $_[] = $before_menu;
			$menuid = $idprefix.$O->id;

			if (Shopp::str_true($label)) $_[] = '<label for="'.esc_attr($menuid).'">'. __('Options','Shopp').': </label> ';

			$_[] = '<select name="products['.$O->id.'][price]" id="'.esc_attr($menuid).'">';
			if (!empty($defaults)) $_[] = '<option value="">'.$defaults.'</option>';

			foreach ($O->prices as $pricetag) {
				if ($pricetag->context != "addon") continue;

				if (!is_null($taxes))
					$taxrate = shopp_taxrate(Shopp::str_true($taxes),$pricetag->tax,$O);
				else $taxrate = shopp_taxrate($taxes,$pricetag->tax,$O);

				$currently = Shopp::str_true($pricetag->sale)?$pricetag->promoprice:$pricetag->price;
				$disabled = Shopp::str_true($pricetag->inventory) && $pricetag->stock == 0?' disabled="disabled"':'';

				if ($taxrate > 0) $currently = $currently+($currently*$taxrate);

				$price = '  ('.money($currently).')';
				if ($pricetag->type != "N/A")
					$_[] = '<option value="'.$pricetag->id.'"'.$disabled.'>'.$pricetag->label.$price.'</option>';
			}

			$_[] = '</select>';

			if (!empty($after_menu)) $_[] = $after_menu;

		} else {
			if (!isset($O->options['a'])) return; // Bail if there are no addons

			$taxrate = shopp_taxrate($options['taxes'],true,$O);

			// Index addon prices by option
			$pricing = array();
			foreach ($O->prices as $pricetag) {
				if ($pricetag->context != "addon") continue;
				$pricing[$pricetag->optionkey] = $pricetag;
			}

			foreach ($addons as $id => $menu) {
				if (!empty($before_menu)) $_[] = $before_menu;
				$menuid = $idprefix.$menu['id'];
				if (Shopp::str_true($label)) $_[] = '<label for="'.esc_attr($menuid).'">'.esc_html($menu['name']).'</label> ';
				$category_class = shopp('collection','get-slug');
				$classes = array($class,$category_class,'addons');

				$_[] = '<select name="products['.$O->id.'][addons][]" class="'.trim(join(' ',$classes)).'" id="'.esc_attr($menuid).'" title="'.esc_attr($menu['name']).'">';
				if (!empty($defaults)) $_[] = '<option value="">'.$defaults.'</option>';

				foreach ($menu['options'] as $key => $option) {
					$pricetag = $pricing[ $O->optionkey(array($option['id'])) ];

					if (!is_null($taxes))
						$taxrate = shopp_taxrate(Shopp::str_true($taxes),$pricetag->tax,$O);
					else $taxrate = shopp_taxrate($taxes,$pricetag->tax,$O);

					$currently = Shopp::str_true($pricetag->sale) ? $pricetag->promoprice : $pricetag->price;
					if ($taxrate > 0) $currently = $currently+($currently*$taxrate);
					$_[] = '<option value="'.$pricetag->id.'">'.$option['name'].' (+'.money($currently).')</option>';
				}

				$_[] = '</select>';
			}

			if (!empty($after_menu)) $_[] = $after_menu;
		}


		if ($required)
			add_storefrontjs("$('#".$menuid."').parents('form').bind('shopp_validate',function () { if ('' == $('#".$menuid."').val()) this.shopp_validation = ['".$required_error."', $('#".$menuid."').get(0) ]; }); ");

		return join('',$_);
	}

	static function add_to_cart ($result, $options, $O) {
		if (!shopp_setting_enabled('shopping_cart')) return '';
		$defaults = array(
			'ajax' => false,
			'class' => 'addtocart',
			'label' => __('Add to Cart','Shopp'),
			'redirect' => false,
		);
		$options = array_merge($defaults,$options);
		extract($options);

		$classes = array();
		if (!empty($class)) $classes = explode(' ',$class);

		$string = "";
		if ( shopp_setting_enabled('inventory') && $O->outofstock )
			return '<span class="outofstock">'.esc_html(shopp_setting('outofstock_text')).'</span>';

		if ($redirect)
			$string .= '<input type="hidden" name="redirect" value="'.esc_attr($redirect).'" />';

		$string .= '<input type="hidden" name="products['.$O->id.'][product]" value="'.$O->id.'" />';

		if ( ! Shopp::str_true($O->variants) && ! empty($O->prices) ) { // If variants are off, locate the default price line
			foreach ($O->prices as $price) {
				if ('product' == $price->context) {
					$default = $price; break;
				}
			}
			if ('N/A' == $default->type) return false; // Disable add to cart if the default price is disabled
			$string .= '<input type="hidden" name="products['.$O->id.'][price]" value="'.$default->id.'" />';
		}

		$collection = isset(ShoppCollection()->slug)?shopp('collection','get-slug'):false;
		if (!empty($collection)) {
			$string .= '<input type="hidden" name="products['.$O->id.'][category]" value="'.esc_attr($collection).'" />';
		}

		$string .= '<input type="hidden" name="cart" value="add" />';
		if (!$ajax) {
			$options['class'] = join(' ',$classes);
			$string .= '<input type="submit" name="addtocart" '.inputattrs($options).' />';
		} else {
			if ('html' == $ajax) $classes[] = 'ajax-html';
			else $classes[] = 'ajax';
			$options['class'] = join(' ',$classes);
			$string .= '<input type="hidden" name="ajax" value="true" />';
			$string .= '<input type="button" name="addtocart" '.inputattrs($options).' />';
		}

		return $string;
	}

	static function availability ( $result, $options, $O ) {
		return ! ( shopp_setting_enabled('inventory') && $O->outofstock );
	}

	static function buy_now ($result, $options, $O) {
		if (!isset($options['value'])) $options['value'] = __("Buy Now","Shopp");
		return self::addtocart($result, $options, $O);
	}

	static function categories ($result, $options, $O) {
		if (!isset($O->_categories_loop)) {
			reset($O->categories);
			$O->_categories_loop = true;
		} else next($O->categories);

		if (current($O->categories) !== false) return true;
		else {
			unset($O->_categories_loop);
			return false;
		}
	}

	static function category ($result, $options, $O) {
		$category = current($O->categories);
		if (isset($options['show'])) {
			if ($options['show'] == "id") return $category->id;
			if ($options['show'] == "slug") return $category->slug;
		}
		return $category->name;
	}

	static function coverimage ($result, $options, $O) {
		// Force select the first loaded image
		unset($options['id']);
		$options['index'] = 0;
		return self::image($result, $options, $O);
	}

	static function description ($result, $options, $O) {
		// @deprecated filter hook, no longer needed, use shopp_themeapi_product_description
		$description = apply_filters('shopp_product_description',$O->description);
		return $description;
	}

	static function found ($result, $options, $O) {
		if (empty($O->id)) return false;
		// Prevent re-loading individual product data in category loops
		if ( ShoppCollection() && shopp('collection.products','looping=true') ) return true;
		$loadable = array('prices','coverimages','images','specs','tags','categories','summary');
		$defaults = array(
			'load' => false
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if (false !== strpos($load,',')) $load = explode(',',$load);
		$load = array_intersect($loadable,(array)$load);
		if (empty($load)) $load = array('summary','prices','images','specs','tags','categories');
		$O->load_data($load);
		return true;
	}

	static function free_shipping ($result, $options, $O) {
		if (empty($O->prices)) $O->load_data(array('prices'));
		return Shopp::str_true($O->freeship);
	}

	static function gallery ($result, $options, $O) {
		if (empty($O->images)) $O->load_data(array('images'));
		if (empty($O->images)) return false;
		$styles = '';
		$_size = 240;
		$_width = shopp_setting('gallery_small_width');
		$_height = shopp_setting('gallery_small_height');

		if (!$_width) $_width = $_size;
		if (!$_height) $_height = $_size;

		$defaults = array(

			// Layout settings
			'margins' => 20,
			'rowthumbs' => false,
			// 'thumbpos' => 'after',

			// Preview image settings
			'p_setting' => false,
			'p_size' => false,
			'p_width' => false,
			'p_height' => false,
			'p_fit' => false,
			'p_sharpen' => false,
			'p_quality' => false,
			'p_bg' => false,
			'p_link' => true,
			'rel' => '',

			// Thumbnail image settings
			'thumbsetting' => false,
			'thumbsize' => false,
			'thumbwidth' => false,
			'thumbheight' => false,
			'thumbfit' => false,
			'thumbsharpen' => false,
			'thumbquality' => false,
			'thumbbg' => false,

			// Effects settings
			'zoomfx' => 'shopp-zoom',
			'preview' => 'click',
			'colorbox' => '{}'


		);

		// Populate defaults from named settings, if provided
		$ImageSettings = ImageSettings::__instance();

		if (!empty($options['p_setting'])) {
			$settings = $ImageSettings->get( $options['p_setting']);
			if ($settings) $defaults = array_merge($defaults,$settings->options('p_'));
		}

		if (!empty($options['thumbsetting'])) {
			$settings = $ImageSettings->get( $options['thumbsetting']);
			if ($settings) $defaults = array_merge($defaults,$settings->options('thumb'));
		}

		$optionset = array_merge($defaults,$options);

		// Translate dot-notation options to underscore
		$options = array();
		$keys = array_keys($optionset);
		foreach ($keys as $key)
			$options[str_replace('.','_',$key)] = $optionset[$key];

		extract($options);


		if ($p_size > 0)
			$_width = $_height = $p_size;

		$width = $p_width > 0 ? $p_width : $_width;
		$height = $p_height > 0 ? $p_height : $_height;

		$preview_width = $width;

		$previews = '<ul class="previews">';
		$firstPreview = true;

		// Find the max dimensions to use for the preview spacing image
		$maxwidth = $maxheight = 0;

		foreach ($O->images as $img) {
			$scale = $p_fit ? array_search($p_fit, $img->_scaling) : false;
			$scaled = $img->scaled($width, $height, $scale);
			$maxwidth = max($maxwidth, $scaled['width']);
			$maxheight = max($maxheight, $scaled['height']);
		}

		if ($maxwidth == 0) $maxwidth = $width;
		if ($maxheight == 0) $maxheight = $height;

		$p_link = Shopp::str_true($p_link);

		// Setup preview images
		foreach ($O->images as $img) {
			$scale = $p_fit ? array_search($p_fit, $img->_scaling) : false;
			$sharpen = $p_sharpen ? min($p_sharpen, $img->_sharpen) : false;
			$quality = $p_quality ? min($p_quality, $img->_quality) : false;
			$fill = $p_bg ? hexdec(ltrim($p_bg, '#')) : false;
			if ('transparent' == strtolower($p_bg)) $fill = -1;
			$scaled = $img->scaled($width, $height, $scale);

			if ($firstPreview) { // Adds "filler" image to reserve the dimensions in the DOM

				$href = Shopp::url('' != get_option('permalink_structure')?trailingslashit('000'):'000','images');
				$previews .= '<li'.(($firstPreview)?' class="fill"':'').'>';
				$previews .= '<img src="'.add_query_string("$maxwidth,$maxheight",$href).'" alt=" " width="'.$maxwidth.'" height="'.$maxheight.'" />';
				$previews .= '</li>';
			}
			$title = !empty($img->title)?' title="'.esc_attr($img->title).'"':'';
			$alt = esc_attr(!empty($img->alt)?$img->alt:$img->filename);

			$previews .= '<li id="preview-'.$img->id.'"'.(($firstPreview)?' class="active"':'').'>';

            if ($img->directly_accessible()) $href = $img->direct_url;
			else $href = Shopp::url('' != get_option('permalink_structure')?trailingslashit($img->id).$img->filename:$img->id,'images');

			if ($p_link) $previews .= '<a href="'.$href.'" class="gallery product_'.$O->id.' '.$options['zoomfx'].'"'.(!empty($rel)?' rel="'.$rel.'"':'').''.$title.'>';
			// else $previews .= '<a name="preview-'.$img->id.'">'; // If links are turned off, leave the <a> so we don't break layout
			$previews .= '<img src="'.$img->resized_url($width,$height,$scale,$sharpen,$quality,$fill).'"'.$title.' alt="'.$alt.'" width="'.$scaled['width'].'" height="'.$scaled['height'].'" />';
			if ($p_link) $previews .= '</a>';
			$previews .= '</li>';
			$firstPreview = false;
		}
		$previews .= '</ul>';

		$thumbs = "";
		$twidth = $preview_width+$margins;

		if (count($O->images) > 1) {
			$default_size = 64;
			$_thumbwidth = shopp_setting('gallery_thumbnail_width');
			$_thumbheight = shopp_setting('gallery_thumbnail_height');
			if (!$_thumbwidth) $_thumbwidth = $default_size;
			if (!$_thumbheight) $_thumbheight = $default_size;

			if ($thumbsize > 0) $thumbwidth = $thumbheight = $thumbsize;

			$width = $thumbwidth > 0?$thumbwidth:$_thumbwidth;
			$height = $thumbheight > 0?$thumbheight:$_thumbheight;

			$firstThumb = true;
			$thumbs = '<ul class="thumbnails">';
			foreach ($O->images as $img) {
				$scale = $thumbfit?array_search($thumbfit,$img->_scaling):false;
				$sharpen = $thumbsharpen?min($thumbsharpen,$img->_sharpen):false;
				$quality = $thumbquality?min($thumbquality,$img->_quality):false;
				$fill = $thumbbg?hexdec(ltrim($thumbbg,'#')):false;
				if ('transparent' == strtolower($thumbbg)) $fill = -1;

				$scaled = $img->scaled($width,$height,$scale);

				$title = !empty($img->title)?' title="'.esc_attr($img->title).'"':'';
				$alt = esc_attr(!empty($img->alt)?$img->alt:$img->name);

				$thumbs .= '<li id="thumbnail-'.$img->id.'" class="preview-'.$img->id.(($firstThumb)?' first':'').'">';
				$thumbs .= '<img src="'.$img->resized_url($width,$height,$scale,$sharpen,$quality,$fill).'"'.$title.' alt="'.$alt.'" width="'.$scaled['width'].'" height="'.$scaled['height'].'" />';
				$thumbs .= '</li>'."\n";
				$firstThumb = false;
			}
			$thumbs .= '</ul>';

		}
		if ($rowthumbs > 0) $twidth = ($width+$margins+2)*(int)$rowthumbs;

		$result = '<div id="gallery-'.$O->id.'" class="gallery">'.$previews.$thumbs.'</div>';
		$script = "\t".'ShoppGallery("#gallery-'.$O->id.'","'.$preview.'"'.($twidth?",$twidth":"").');';
		add_storefrontjs($script);

		return $result;
	}

	static function has_addons ($result, $options, $O) {
		reset($O->prices);
		return ( Shopp::str_true($O->addons) && ! empty($O->options['a']) );
	}

	static function has_categories ($result, $options, $O) {

		if ( empty($O->categories) )
			$O->load_data(array('categories'));

		reset($O->categories);

		if ( count($O->categories) > 0 ) return true;
		else return false;

	}

	static function has_images ($result, $options, $O) {

		if ( empty($O->images) )
			$O->load_data(array('images'));

		reset($O->images);
		return ( ! empty($O->images) );

	}

	static function has_savings ($result, $options, $O) {
		return ( Shopp::str_true($O->sale) && $O->min['saved'] > 0 );
	}

	static function has_specs ($result, $options, $O) {

		if ( empty($O->specs) )
			$O->load_data(array('specs'));

		reset($O->specs);

		if ( count($O->specs) > 0 ) return true;
		else return false;

	}

	static function has_tags ($result, $options, $O) {

		if ( empty($O->tags) )
			$O->load_data(array('tags'));

		reset($O->tags);

		if ( count($O->tags) > 0 ) return true;
		else return false;

	}

	static function has_variations ($result, $options, $O) {

		if ( ! Shopp::str_true($O->variants) ) return false;

		// Only load again if needed
		$load = array();
		if ( empty($O->options) ) $load[] = 'meta';
		if ( empty($O->prices) ) $load[] = 'prices';
		if ( ! empty($load) ) $O->load_data($load);

		reset($O->prices);
		return ( ! empty($O->options['v']) || ! empty($O->options) );

	}

	static function id ($result, $options, $O) {
		return $O->id;
	}

	/**
	 * Renders a product image
	 *
	 * @see the image() method from theme/catalog.php
	 * @author Jonathan Davis
	 * @since 1.2
	 *
	 * @return string
	 **/
	static function image ($result, $options, $O) {
		if ( empty($O->images) ) $O->load_data( array('images') );
		return ShoppStorefrontThemeAPI::image($result, $options, $O);
	}

	static function images ($result, $options, $O) {
		if (!$O->images) return false;
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

	static function in_cart ($result, $options, $O) {
		$Order = ShoppOrder();
		$cartitems = $Order->Cart->contents;
		if (empty($cartitems)) return false;
		foreach ((array)$cartitems as $Item)
			if ($Item->product == $O->id) return true;
		return false;
	}

	static function in_category ($result, $options, $O) {
		if (empty($O->categories)) $O->load_data(array('categories'));
		if (isset($options['id'])) $field = "id";
		if (isset($options['name'])) $field = "name";
		if (isset($options['slug'])) $field = "slug";
		foreach ($O->categories as $category)
			if ($category->{$field} == $options[$field]) return true;
		return false;
	}

	static function input ($result, $options, $O) {
		$defaults = array(
			'type' => 'text',
			'name' => false,
			'value' => ''
		);
		$options = array_merge($defaults,$options);

		// Ensure we have a title attribute (catalog.js depends on this)
		$options['title'] = ($options['name'] !== false)
			? $options['name']
			: __('product input field', 'Shopp');

		extract($options,EXTR_SKIP);

		$select_attrs = array('title','required','class','disabled','required','size','tabindex','accesskey');
		$submit_attrs = array('title','class','value','disabled','tabindex','accesskey');

		if (empty($type) || (!in_array($type,array('menu','textarea')) && !Shopp::valid_input($options['type'])) ) $type = $defaults['type'];


		if (empty($name)) return '';
		$slug = sanitize_title_with_dashes($name);
		$id = "data-$slug-{$O->id}";

		if ('menu' == $type) {
			$result = '<select name="products['.$O->id.'][data]['.$name.']" id="'.$id.'"'.inputattrs($options,$select_attrs).'>';
			if (isset($options['options']))
				$menuoptions = preg_split('/,(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/',$options['options']);
			if (is_array($menuoptions)) {
				foreach($menuoptions as $option) {
					$selected = "";
					$option = trim($option,'"');
					if (isset($options['default']) && $options['default'] == $option)
						$selected = ' selected="selected"';
					$result .= '<option value="'.$option.'"'.$selected.'>'.$option.'</option>';
				}
			}
			$result .= '</select>';
		} elseif ('textarea' == $type) {
			if (isset($options['cols'])) $cols = ' cols="'.$options['cols'].'"';
			if (isset($options['rows'])) $rows = ' rows="'.$options['rows'].'"';
			$result .= '<textarea name="products['.$O->id.'][data]['.$name.']" id="'.$id.'"'.$cols.$rows.inputattrs($options).'>'.esc_html($value).'</textarea>';
		} else {
			$result = '<input type="'.$type.'" name="products['.$O->id.'][data]['.$name.']" id="'.$id.'"'.inputattrs($options).' />';
		}

		return $result;
	}

	static function is_featured ($result, $options, $O) { return ($O->featured == "on"); }

	static function name ($result, $options, $O) { return apply_filters('shopp_product_name',$O->name); }

	static function on_sale ($result, $options, $O) {
		if (empty($O->prices)) $O->load_data(array('prices','summary'));
		if (empty($O->prices)) return false;
		return Shopp::str_true($O->sale);
	}

	static function out_of_stock ($result, $options, $O) {
		if ( shopp_setting_enabled('inventory') && $O->outofstock ) {
			$label = isset($options['label'])?$options['label']:shopp_setting('outofstock_text');
			$string = '<span class="outofstock">'.$label.'</span>';
			return $string;
		} else return false;
	}

	static function price ($result, $options, $O) {
		$defaults = array(
			'taxes' => null,
			'starting' => '',
			'separator' => ' &mdash; ',
			'property' => 'price',
			'money' => 'on',
			'number' => false,
			'high' => false,
			'low' => false,
			'disabled' => __('Currently unavailable', 'Shopp')
		);
		$options = array_merge($defaults, $options);
		extract($options);

		if ( ! is_null($taxes) ) $taxes = Shopp::str_true($taxes);

		if ( ! Shopp::str_true($O->sale) ) $property = 'price';

		// Handle inclusive/exclusive tax presentation options (product editor setting or api option)
		$taxes = is_null($taxes) ? self::_include_tax($O) : Shopp::str_true( $taxes );

		$min = isset($O->min[ $property ]) ? $O->min[ $property ] : false;
		$taxmin = isset($O->min[ $property . '_tax' ]) ? $O->min[ $property . '_tax' ] : false; // flag to apply tax to min price (from summary)
		$mintaxrate = $taxes && $taxmin ? $min * shopp_taxrate($taxes, $taxmin, $O) : 0;

		$max = isset($O->max[ $property ]) ? $O->max[ $property ] : false;
		$taxmax = isset($O->max[ $property . '_tax' ]) ? $O->max[ $property . '_tax' ] : false; // flag to apply tax to max price (from summary)
		$maxtaxrate = $taxes && $taxmax ? $max * shopp_taxrate($taxes, $taxmax, $O) : 0;

		if ( ! $taxes ) $taxrate = 0;

		if ( $min == $max || ! empty($starting) || Shopp::str_true($low) ) {
			$prices = array($min + $mintaxrate);
		} elseif ( Shopp::str_true($high) ) {
			$prices = array($max + $maxtaxrate);
		} else {
			$prices = array(
				$min + $mintaxrate,
				$max + $maxtaxrate
			);
		}

		// Pricing disabled? Ensure price data has been loaded first
		if ( empty($O->prices) ) $O->load_data( array('prices') );
		if ( 1 === count($O->prices) && 'N/A' === $O->prices[0]->type ) return $disabled;

		$prices = array_map('roundprice', $prices);
		if ( Shopp::str_true($number)  ) return join($separator, $prices);
		if ( Shopp::str_true($money)   ) $prices = array_map('money', $prices);
		if ( ! empty($starting) && $min != $max ) $prices = "$starting {$prices[0]}";

		if ( is_array($prices) ) return join($separator, $prices);
		else return $prices;
	}

	static function saleprice ($result, $options, $O) {
		$options['property'] = 'saleprice';
		return self::price($result, $options, $O);
	}

	static function quantity ($result, $options, $O) {
		if ( ! shopp_setting_enabled('shopping_cart') ) return '';
		if ( shopp_setting_enabled('inventory') && $O->outofstock ) return '';

		$inputs = array('text','menu');
		$defaults = array(
			'value' => 1,
			'input' => 'text', // accepts text,menu
			'labelpos' => 'before',
			'label' => '',
			'options' => '1-15,20,25,30,40,50,75,100',
			'size' => 3
		);
		$options = array_merge($defaults,$options);
		$_options = $options;
		extract($_options);

		unset($_options['label']); // Interferes with the text input value when passed to inputattrs()
		$labeling = '<label for="quantity-'.$O->id.'">'.$label.'</label>';


		if ( ! isset($O->_prices_loop) ) reset($O->prices);
		$variation = current($O->prices);

		if ( ! shopp_setting_enabled('download_quantity') && ! empty($O->prices) ) {
			$downloadonly = true;
			foreach ( $O->prices as $variant ) {
				if ( 'Download' != $variant->type && 'N/A' != $variant->type )
					$downloadonly = false;
			}
			if ( $downloadonly ) return '';
		}


		$_ = array();

		if ("before" == $labelpos) $_[] = $labeling;
		if ("menu" == $input) {
			if (Shopp::str_true($O->inventory) && isset($O->max['stock']) && $O->max['stock'] == 0) return "";

			if (strpos($options,",") !== false) $options = explode(",",$options);
			else $options = array($options);

			$qtys = array();
			foreach ((array)$options as $v) {
				if (strpos($v,"-") !== false) {
					$v = explode("-",$v);
					if ($v[0] >= $v[1]) $qtys[] = $v[0];
					else for ($i = $v[0]; $i < $v[1]+1; $i++) $qtys[] = $i;
				} else $qtys[] = $v;
			}
			$_[] = '<select name="products['.$O->id.'][quantity]" id="quantity-'.$O->id.'">';
			foreach ($qtys as $qty) {
				$amount = $qty;
				if ( $variation && 'Donation' == $variation->type && Shopp::str_true($variation->donation['var']) ) {
					if ($variation->donation['min'] == "on" && $amount < $variation->price) continue;
					$amount = money($amount);
					$value = $variation->price;
				} else {
					if (Shopp::str_true($O->inventory) && $amount > $O->max['stock']) continue;
				}
				$selected = ($qty == $value ? ' selected="selected"' : '');
				$_[] = '<option'.$selected.' value="'.$qty.'">'.$amount.'</option>';
			}
			$_[] = '</select>';
		} elseif (Shopp::valid_input($input)) {
			if (  $variation && 'Donation' == $variation->type && Shopp::str_true($variation->donation['var']) ) {
				if ($variation->donation['min']) $_options['value'] = $variation->price;
				$_options['class'] .= " currency";
			}
			$_[] = '<input type="'.$input.'" name="products['.$O->id.'][quantity]" id="quantity-'.$O->id.'"'.inputattrs($_options).' />';
		}

		if ("after" == $labelpos) $_[] = $labeling;
		return join("\n",$_);
	}

	static function relevance ($result, $options, $O) { return (string)$O->score; }

	static function savings ($result, $options, $O) {
		if (!isset($options['taxes'])) $options['taxes'] = null;

		$taxrate = shopp_taxrate($options['taxes']);
		$range = false;

		if (!isset($options['show'])) $options['show'] = '';
		if ($options['show'] == "%" || $options['show'] == "percent") {
			if ($O->options > 1) {
				if (round($O->min['savings']) != round($O->max['savings'])) {
					$range = array($O->min['savings'],$O->max['savings']);
					sort($range);
				}
				if (!$range) return percentage($O->min['savings'],array('precision' => 0)); // No price range
				else return percentage($range[0],array('precision' => 0))." &mdash; ".percentage($range[1],array('precision' => 0));
			} else return percentage($O->max['savings'],array('precision' => 0));
		} else {
			if ($O->options > 1) {
				if (round($O->min['saved']) != round($O->max['saved'])) {
					$range = array($O->min['saved'],$O->max['saved']);
					sort($range);
				}
				if (!$range) return money($O->min['saved']+($O->min['saved']*$taxrate)); // No price range
				else return money($range[0]+($range[0]*$taxrate))." &mdash; ".money($range[1]+($range[1]*$taxrate));
			} else return money($O->max['saved']+($O->max['saved']*$taxrate));
		}
	}

	static function schema ($result, $options, $O) {
		$template = locate_shopp_template( array('product-' . $O->slug . '-schema.php', 'product-schema.php') );
		if ( ! $template ) $template = SHOPP_ADMIN_PATH . '/products/schema.php';
		ob_start();
		include($template);
		$result = ob_get_clean();
		return $result;
	}

	static function slug ($result, $options, $O) {
		return $O->slug;
	}

	static function spec ($result, $options, $O) {
		$showname = false;
		$showcontent = false;
		$defaults = array(
			'separator' => ': ',
			'delimiter' => ', ',
			'name' => false,
			'index' => false,
			'content' => false,
		);
		if (isset($options['name'])) $showname = true;
		if (isset($options['content'])) $showcontent = true;
		$options = array_merge($defaults,$options);
		extract($options);

		$string = '';

		if ( !empty($name) ) {
			if ( ! isset($O->specnames[$name]) ) return apply_filters('shopp_product_spec',false);
			$spec = $O->specnames[$name];
			if (is_array($spec)) {
				if ($index) {
					foreach ($spec as $id => $item)
						if (($id+1) == $index) $content = $item->value;
				} else {
					$values = array();
					foreach ($spec as $item) $values[] = $item->value;
					$content = join($delimiter,$values);
				}
			} else $content = $spec->value;

			return apply_filters('shopp_product_spec',$content);
		}

		// Spec loop handling
		$spec = current($O->specnames);

		if (is_array($spec)) {
			$values = array();
			foreach ($spec as $id => $entry) {
				$specname = $entry->name;
				$values[] = $entry->value;
			}
			$specvalue = join($delimiter,$values);
		} else {
			$specname = $spec->name;
			$specvalue = $spec->value;
		}

		if ($showname && $showcontent)
			$string = $spec->name.$separator.apply_filters('shopp_product_spec',$specvalue);
		elseif ($showname) $string = $specname;
		elseif ($showcontent) $string = apply_filters('shopp_product_spec',$specvalue);
		else $string = $specname.$separator.apply_filters('shopp_product_spec',$specvalue);
		return $string;
	}

	static function sku ( $result, $options, $O ) {
		if ( empty($O->prices) ) $O->load_data(array('prices'));

		if ( 1 == count($O->prices) && $O->prices[0]->sku )
			return $O->prices[0]->sku;

		$skus = array();
		foreach ($O->prices as $price)
			if ( 'N/A' != $price->type && $price->sku ) $skus[$price->sku] = $price->sku;

		if ( ! empty($skus) ) return join(',', $skus);
		return '';
	}

	static function specs ($result, $options, $O) {
		if (!isset($O->_specs_loop)) {
			reset($O->specnames);
			$O->_specs_loop = true;
		} else next($O->specnames);

		if (current($O->specnames) !== false) return true;
		else {
			unset($O->_specs_loop);
			return false;
		}
	}

	static function stock ($result, $options, $O) {
		return (int)$O->stock;
	}

	static function summary ($result, $options, $O) { return apply_filters('shopp_product_summary',$O->summary); }

	static function tag ($result, $options, $O) {
		$tag = current($O->tags);
		if (isset($options['show'])) {
			if ($options['show'] == "id") return $tag->id;
		}
		return $tag->name;
	}

	static function tagged ($result, $options, $O) {
		if (empty($O->tags)) $O->load_data(array('tags'));
		if (isset($options['id'])) $field = "id";
		if (isset($options['name'])) $field = "name";
		foreach ($O->tags as $tag)
			if ($tag->{$field} == $options[$field]) return true;
		return false;
	}

	static function tags ($result, $options, $O) {
		if (!isset($O->_tags_loop)) {
			reset($O->tags);
			$O->_tags_loop = true;
		} else next($O->tags);

		if (current($O->tags) !== false) return true;
		else {
			unset($O->_tags_loop);
			return false;
		}
	}

	static function taxrate ($result, $options, $O) { return shopp_taxrate(null,true,$O); }

	static function type ($result, $options, $O) {
		if (empty($O->prices)) $O->load_data(array('prices'));

		if (1 == count($O->prices))
			return $O->prices[0]->type;

		$types = array();
		foreach ($O->prices as $price)
			if ('N/A' != $price->type) $types[$price->type] = $price->type;

		return join(',',$types);
	}

	static function url ($result, $options, $O) {
		global $wp_rewrite;
		return Shopp::url( $wp_rewrite->using_permalinks() ? $O->slug : array(Product::$posttype => $O->slug), false );
	 }

	static function variation ($result, $options, $O) {
		$defaults = array(
			'separator' => ' ',
			'units' => 'on',
			'promos' => 'on',
			'taxes' => null
		);
		$options = array_merge($defaults,$options);
		extract($options,EXTR_SKIP);

		$weightunit = Shopp::str_true($units) ? shopp_setting('weight_unit') : '';

		$variation = current($O->prices);

		$taxrate = shopp_taxrate($taxes,$variation->tax,$O);
		$taxes = is_null($taxes) ? self::_include_tax($O) : Shopp::str_true($taxes);
		if ( ! $taxes ) $taxrate = 0;

		$_ = array();
		if (array_key_exists('id',$options)) 		$_[] = $variation->id;
		if (array_key_exists('label',$options))		$_[] = $variation->label;
		if (array_key_exists('type',$options))		$_[] = $variation->type;
		if (array_key_exists('sku',$options))		$_[] = $variation->sku;
		if (array_key_exists('price',$options)) 	$_[] = money($variation->price+($variation->price*$taxrate));
		if (array_key_exists('saleprice',$options)) {
			if (Shopp::str_true($promos)) $_[] = money($variation->promoprice+($variation->promoprice*$taxrate));
			else $_[] = money($variation->saleprice+($variation->saleprice*$taxrate));
		}
		if (array_key_exists('stock',$options)) 	$_[] = $variation->stock;
		if (array_key_exists('weight',$options)) 	$_[] = round($variation->weight, 3) . ($weightunit ? " $weightunit" : false);
		if (array_key_exists('shipfee',$options)) 	$_[] = money(Shopp::floatval($variation->shipfee));
		if (array_key_exists('sale',$options)) 		return Shopp::str_true($variation->sale);
		if (array_key_exists('shipping',$options))	return Shopp::str_true($variation->shipping);
		if (array_key_exists('tax',$options))		return Shopp::str_true($variation->tax);
		if (array_key_exists('inventory',$options))	return Shopp::str_true($variation->inventory);

		return join($separator,$_);
	}

	static function variations ($result, $options, $O) {
		$string = "";

		if (!isset($options['mode'])) {
			if (!isset($O->_prices_loop)) {
				reset($O->prices);
				$O->_prices_loop = true;
			} else next($O->prices);
			$price = current($O->prices);

			if ($price && ($price->type == 'N/A' || $price->context != 'variation'))
				$price = next($O->prices);

			if ($price !== false) return true;
			else {
				unset($O->_prices_loop);
				return false;
			}
		}

		if ( shopp_setting_enabled('inventory') && $O->outofstock ) return false; // Completely out of stock, hide menus
		if (!isset($options['taxes'])) $options['taxes'] = null;

		$defaults = array(
			'defaults' => '',
			'disabled' => 'show',
			'pricetags' => 'show',
			'before_menu' => '',
			'after_menu' => '',
			'format' => '%l (%p)',
			'label' => 'on',
			'mode' => 'multiple',
			'taxes' => null,
			'required' => __('You must select the options for this item before you can add it to your shopping cart.','Shopp')
			);
		$options = array_merge($defaults,$options);
		extract($options);

		$taxes = is_null($taxes) ? self::_include_tax($O) : Shopp::str_true($taxes);

		if ('single' == $mode) {
			if (!empty($options['before_menu'])) $string .= $options['before_menu']."\n";
			if (Shopp::str_true($options['label'])) $string .= '<label for="product-options'.$O->id.'">'. __('Options', 'Shopp').': </label> '."\n";

			$string .= '<select name="products['.$O->id.'][price]" id="product-options'.$O->id.'">';
			if (!empty($options['defaults'])) $string .= '<option value="">'.$options['defaults'].'</option>'."\n";

			foreach ($O->prices as $pricing) {
				if ('variation' != $pricing->context) continue;

				$taxrate = shopp_taxrate($taxes,$pricing->tax);
				if ( ! $taxes ) $taxrate = 0;

				$currently = Shopp::str_true($pricing->sale)?$pricing->promoprice:$pricing->price;
				$disabled = Shopp::str_true($pricing->inventory) && $pricing->stock == 0?' disabled="disabled"':'';

				if ($taxes && $taxrate > 0) $currently = $currently+($currently*$taxrate);

				$discount = 100-round($pricing->promoprice*100/$pricing->price);
				$_ = new StdClass();
				if ($pricing->type != 'Donation')
					$_->p = money($currently);
				$_->l = $pricing->label;
				$_->i = Shopp::str_true($pricing->inventory);
				if ($_->i) $_->s = $pricing->stock;
				$_->u = $pricing->sku;
				$_->tax = Shopp::str_true($pricing->tax);
				$_->t = $pricing->type;
				if ($pricing->promoprice != $pricing->price)
					$_->r = money($pricing->price);
				if ($discount > 0)
					$_->d = $discount;

				if ('N/A' != $pricetag->type)
					$string .= '<option value="'.$pricetag->id.'"'.$disabled.'>'.self::_variant_formatlabel($format,$_).'</option>'."\n";
			}
			$string .= '</select>';
			if (!empty($options['after_menu'])) $string .= $options['after_menu']."\n";

		} else {
			if (!isset($O->options)) return;

			$menuoptions = $O->options;
			if (!empty($O->options['v'])) $menuoptions = $O->options['v'];

			$baseop = shopp_setting('base_operations');
			$precision = $baseop['currency']['format']['precision'];

			$taxrate = shopp_taxrate($taxes,true,$O);
			if ( ! $taxes ) $taxrate = 0;

			$pricekeys = array();
			foreach ($O->pricekey as $key => $pricing) {
				$discount = 100-round($pricing->promoprice*100/$pricing->price);
				$_ = new StdClass();
				if ($pricing->type != 'Donation')
					$_->p = (float)apply_filters('shopp_product_variant_price', (Shopp::str_true($pricing->sale) ? $pricing->promoprice : $pricing->price) );
				$_->i = Shopp::str_true($pricing->inventory);
				$_->s = $_->i ? (int)$pricing->stock : false;
				$_->u = $pricing->sku;
				$_->tax = Shopp::str_true($pricing->tax);
				$_->t = $pricing->type;
				if ($pricing->promoprice != $pricing->price)
					$_->r = $pricing->price;
				if ($discount > 0)
					$_->d = $discount;
				$pricekeys[$key] = $_;
			}

			$jsoptions = array('prices'=> $pricekeys,'format' => $format);
			if ( 'hide' == $options['disabled'] ) $jsoptions['disabled'] = false;
			if ( 'hide' == $options['pricetags'] ) $jsoptions['pricetags'] = false;
			if ( ! empty($taxrate) ) $jsoptions['taxrate'] = $taxrate;


			$collection_class = ShoppCollection() && isset(ShoppCollection()->slug) ? 'category-' . ShoppCollection()->slug : '';

			ob_start();
?><?php if (!empty($options['defaults'])): ?>
$s.opdef = true;
<?php endif; ?>
<?php if (!empty($options['required'])): ?>
$s.opreq = "<?php echo $options['required']; ?>";
<?php endif; ?>
new ProductOptionsMenus(<?php printf("'select%s.product%d.options'",$collection_class,$O->id); ?>,<?php echo json_encode($jsoptions); ?>);
<?php

			$script = ob_get_contents();
			ob_end_clean();

			add_storefrontjs($script);

			foreach ($menuoptions as $id => $menu) {
				if (!empty($options['before_menu'])) $string .= $options['before_menu']."\n";
				if (Shopp::str_true($options['label'])) $string .= '<label for="options-'.$menu['id'].'">'.$menu['name'].'</label> '."\n";
				$string .= '<select name="products['.$O->id.'][options][]" class="'.$collection_class.' product'.$O->id.' options" id="options-'.$menu['id'].'">';
				if (!empty($options['defaults'])) $string .= '<option value="">'.$options['defaults'].'</option>'."\n";
				foreach ($menu['options'] as $key => $option)
					$string .= '<option value="'.$option['id'].'">'.$option['name'].'</option>'."\n";

				$string .= '</select>';
				if (!empty($options['after_menu'])) $string .= $options['after_menu']."\n";
			}
		}

		return $string;
	}

	static function _variant_formatlabel ($format,$var) {
		$v = get_object_vars($var);
		$tokens = join('',array_keys($v));
		$t = addslashes(serialize($v));
		$p = '([^\s]*)';
		$label = preg_replace_callback(
			"/$p(%([a-zA-Z]))$p/",
			create_function('$m','
				$t = unserialize("'.$t.'");
				if ( ! array_key_exists($m[3],$t) ) return "";
				return $m[1].$t[ $m[3] ].$m[4];
			'),
			$format
		);

		return trim($label);

	}

	static function weight ($result, $options, $O) {
		if(empty($O->prices)) $O->load_data(array('prices'));
		$defaults = array(
			'unit' => shopp_setting('weight_unit'),
			'min' => $O->min['weight'],
			'max' => $O->max['weight'],
			'units' => true,
			'convert' => false
		);
		$options = array_merge($defaults,$options);
		extract($options);

		if(!isset($O->min['weight'])) return false;

		if ($convert !== false) {
			$min = convert_unit($min,$convert);
			$max = convert_unit($max,$convert);
			if (is_null($units)) $units = true;
			$unit = $convert;
		}

		$range = false;
		if ($min != $max) {
			$range = array($min,$max);
			sort($range);
		}

		$string = ($min == $max)?round($min,3):round($range[0],3)." - ".round($range[1],3);
		$string .= Shopp::str_true($units) ? " $unit" : "";
		return $string;
	}

	static function _include_tax ($O) {
		return ( shopp_setting_enabled('tax_inclusive') && ! Shopp::str_true($O->excludetax) );
	}

}

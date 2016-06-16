<?php
/**
 * ShoppProductWidget class
 * A WordPress widget that shows a selection of products
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppProductWidget') ) {

	class ShoppProductWidget extends WP_Widget {

	    function __construct() {
	        parent::__construct(false,
				$name = Shopp::__('Shopp Product'),
				array('description' => Shopp::__('Highlight specific store products'))
			);
	    }

	    function widget($args, $options) {
			$Shopp = Shopp::object();
			extract($args);

			$title = $before_title . $options['title'] . $after_title;
			unset($options['title']);

			$content = shopp('storefront', 'get-sideproduct', $options);

			if ( empty($content) ) return false;
			echo $before_widget . $title . $content . $after_widget;
	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

	    function form( $options ) {
	    	$defaults = array(
	    		'title'	    => '',
	    		'source'	=> '',
	    		'category'	=> '',
	    		'limit'	    => '',
	    		'order'		=> '',
	    		'product'	=> '',
	    		);
	    	
	    	$options = array_merge($defaults, $options);
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

			<p><select id="<?php echo $this->get_field_id('source'); ?>" name="<?php echo $this->get_field_name('source'); ?>" class="widefat"><option value="category"<?php echo $options['source'] == "category" ? ' selected="selected"' : ''; ?>><?php Shopp::_e('From a category'); ?></option><option value="product"<?php echo $options['source'] == "product" ? ' selected="selected"' : ''; ?>><?php Shopp::_e('By product'); ?></option></select></p>

			<?php
				if ('' != get_option('permalink_structure')) $label = Shopp::__('Category Slug/ID');
				else $label = Shopp::__('Category ID');
			 ?>
			<p id="<?php echo $this->get_field_id('category-fields'); ?>" class="hidden">
				<label for="<?php echo $this->get_field_id('category'); ?>"><?php echo $label; ?></label>
				<input type="text" name="<?php echo $this->get_field_name('category'); ?>" id="<?php echo $this->get_field_id('category'); ?>" class="widefat" value="<?php echo $options['category']; ?>">
				<br /><br />
				<select id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>">
					<?php $limits = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 25);
						echo menuoptions($limits, $options['limit']); ?>
				</select>
				<select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
					<?php
						$sortoptions = array(
							"bestselling"	=> Shopp::__('Bestselling'),
							"highprice"	    => Shopp::__('Highest Price'),
							"lowprice"	    => Shopp::__('Lowest Price'),
							"newest"	    => Shopp::__('Newest'),
							"oldest"	    => Shopp::__('Oldest'),
							"chaos"	        => Shopp::__('Random')
						);
						echo menuoptions($sortoptions, $options['order'], true);
					?>
				</select>
				<label for="<?php echo $this->get_field_id('order'); ?>"><?php Shopp::_e('products'); ?></label>
			</p>

			<?php
				if ( '' != get_option('permalink_structure') ) $label = Shopp::__('Product Slug/ID(s)');
				else $label = Shopp::__('Product ID(s)');
			 ?>
			<p id="<?php echo $this->get_field_id('product-fields'); ?>" class="hidden">
				<label for="<?php echo $this->get_field_id('product'); ?>"><?php echo $label; ?></label>
				<input type="text" name="<?php echo $this->get_field_name('product'); ?>" id="<?php echo $this->get_field_id('product'); ?>" class="widefat" value="<?php echo $options['product']; ?>">
				<small><?php Shopp::_e('Use commas to specify multiple products')?></small></p>

			<script type="text/javascript">
			(function($) {
				$(window).ready(function () {
					var categoryui = $('#<?php echo $this->get_field_id("category-fields"); ?>');
					var productui = $('#<?php echo $this->get_field_id("product-fields"); ?>');
					$('#<?php echo $this->get_field_id("source"); ?>').change(function () {
						if ($(this).val() == "category") {
							productui.hide();
							categoryui.show();
						}
						if ($(this).val() == "product") {
							categoryui.hide();
							productui.show();
						}
					}).change();
				});
			})(jQuery)
			</script>
			<?php
	    }

	} // class ShoppProductWidget

}
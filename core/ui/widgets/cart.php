<?php
/**
 * ShoppCartWidget class
 * A WordPress widget to show the contents of the shopping cart
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppCartWidget') ) {

	class ShoppCartWidget extends WP_Widget {

	    function __construct () {
	        parent::__construct(false,
				$name =__('Shopp Cart','Shopp'),
				array('description' => __('The customer\'s shopping cart','Shopp'))
			);
	    }

	    function widget($args, $options) {
			if (!empty($args)) extract($args);

			if (empty($options['title'])) $options['title'] = __('Your Cart','Shopp');
			$title = $before_title.$options['title'].$after_title;

			if ('on' == $options['hide-empty'] && shopp_cart_items_count() == 0) return;

			$sidecart = shopp('cart','get-sidecart',$options);
			if (empty($sidecart)) return;
			echo $before_widget.$title.$sidecart.$after_widget;
	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

	    function form($options) {
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

			<p>
			<input type="hidden" name="<?php echo $this->get_field_name('hide-empty'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hide-empty'); ?>" name="<?php echo $this->get_field_name('hide-empty'); ?>" value="on"<?php echo $options['hide-empty'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('hide-empty'); ?>"> <?php _e('Hide when cart is empty','Shopp'); ?></label></p>
			<?php
	    }

	} // class ShoppCartWidget

}
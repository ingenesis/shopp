<?php
/**
 * ShoppCategorySectionWidget class
 * A WordPress widget that provides a navigation menu of a Shopp category section (branch)
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppCategorySectionWidget') ) {

	class ShoppCategorySectionWidget extends WP_Widget {

	    function __construct() {
	        parent::__construct(false,
				$name = __('Shopp Category Section','Shopp'),
				array('description' => __('A list or dropdown of store categories'))
			);
	    }

	    function widget($args, $options) {
			$Shopp = Shopp::object();
			extract($args);

			$title = $before_title.$options['title'].$after_title;
			unset($options['title']);
			if (empty(ShoppCollection()->id)) return false;
			$menu = shopp(ShoppCollection(),'get-section-list',$options);
			echo $before_widget.$title.$menu.$after_widget;
	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

	    function form($options) {
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

			<p>
			<input type="hidden" name="<?php echo $this->get_field_name('dropdown'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>" value="on"<?php echo $options['dropdown'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('dropdown'); ?>"> <?php _e('Show as dropdown','Shopp'); ?></label><br />
			<input type="hidden" name="<?php echo $this->get_field_name('products'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('products'); ?>" name="<?php echo $this->get_field_name('products'); ?>" value="on"<?php echo $options['products'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('products'); ?>"> <?php _e('Show product counts','Shopp'); ?></label><br />
			<input type="hidden" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hierarchy'); ?>" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="on"<?php echo $options['hierarchy'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('hierarchy'); ?>"> <?php _e('Show hierarchy','Shopp'); ?></label><br />
			</p>
			<?php
	    }

	} // class ShoppCategorySectionWidget

}
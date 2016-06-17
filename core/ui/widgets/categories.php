<?php
/**
 * ShoppCategoriesWidget class
 * A WordPress widget that provides a navigation menu of Shopp categories
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppCategoriesWidget') ) {

	class ShoppCategoriesWidget extends WP_Widget {

	    function __construct () {
	        parent::__construct(false,
				$name = __('Shopp Categories','Shopp'),
				array('description' => __('A list or dropdown of store categories','Shopp'))
			);
	    }

	    function widget($args, $options) {
			extract($args);

			$title = $before_title.$options['title'].$after_title;
			unset($options['title']);
			$menu = shopp('catalog','get-category-list', $options);
			echo $before_widget.$title.$menu.$after_widget;
	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

	    function form($options) {
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','Shopp'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

			<p>
			<input type="hidden" name="<?php echo $this->get_field_name('dropdown'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>" value="on"<?php echo $options['dropdown'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('dropdown'); ?>"> <?php _e('Show as dropdown','Shopp'); ?></label><br />
			<input type="hidden" name="<?php echo $this->get_field_name('products'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('products'); ?>" name="<?php echo $this->get_field_name('products'); ?>" value="on"<?php echo $options['products'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('products'); ?>"> <?php _e('Show product counts','Shopp'); ?></label><br />
			<input type="hidden" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hierarchy'); ?>" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="on"<?php echo $options['hierarchy'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('hierarchy'); ?>"> <?php _e('Show hierarchy','Shopp'); ?></label><br />
			<input type="hidden" name="<?php echo $this->get_field_name('depth'); ?>" value="0" /><input type="checkbox" id="<?php echo $this->get_field_id('depth'); ?>" name="<?php echo $this->get_field_name('depth'); ?>" value="1"<?php echo $options['depth'] == "1"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('depth'); ?>"> <?php _e('Hide child categories','Shopp'); ?></label><br />
			</p>
			<p><label for="<?php echo $this->get_field_id('showsmart'); ?>"><?php _e('Smart Categories:','Shopp'); ?>
				<select id="<?php echo $this->get_field_id('showsmart'); ?>" name="<?php echo $this->get_field_name('showsmart'); ?>" class="widefat"><option value="false"><?php _e('Hide','Shopp'); ?></option><option value="before"<?php echo $options['showsmart'] == "before"?' selected="selected"':''; ?>><?php _e('Include before custom categories','Shopp'); ?></option><option value="after"<?php echo $options['showsmart'] == "after"?' selected="selected"':''; ?>><?php _e('Include after custom categories','Shopp'); ?></option></select></label></p>
			<?php
	    }

	} // class ShoppCategoriesWidget

}

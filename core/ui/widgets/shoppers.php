<?php
/**
 * ShoppShoppersWidget class
 * A WordPress widget to show a list of recent shoppers
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 26 June, 2011
 * @package shopp
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppShoppersWidget') ) {

	class ShoppShoppersWidget extends WP_Widget {

	    function __construct () {
	        parent::__construct(
				'shopp-recent-shoppers',
				__('Shopp Recent Shoppers','Shopp'),
				array('description' => __('Lists recent shoppers on your store','Shopp'))
			);
	    }

	    function widget($args, $options) {
			if (!empty($args)) extract($args);

			if (empty($options['title'])) $options['title'] = __('Recent Shoppers','Shopp');
			$title = $before_title.$options['title'].$after_title;
			$content = shopp('catalog','get-recent-shoppers',$options);

			if (empty($content)) return false; // No recent shoppers, hide it

			echo $before_widget.$title.$content.$after_widget;
	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

		function showerrors () {
			return false;
		}

	    function form($options) {
			$format_options = array(
				'firstname' => __('J. Doe'),
				'lastname' => __('John D.')
			);

			$location_options = array(
				'none' => __('No location'),
				'state' => __('State/Province'),
				'city,state' => __('City, State/Province')
			);

			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
			<p><select name="<?php echo $this->get_field_name('abbr'); ?>">
			<?php echo menuoptions($format_options,$options['abbr'],true); ?>
			</select><label> <?php _e('Name format','Shopp'); ?></label></p>

			<p><label><input type="hidden" name="<?php echo $this->get_field_name('city'); ?>" value="off" /><input type="checkbox" name="<?php echo $this->get_field_name('city'); ?>" value="on" <?php echo $options['city'] == "on"?' checked="checked"':''; ?> /> <?php _e('Show city'); ?></label><br />
			<label><input type="hidden" name="<?php echo $this->get_field_name('state'); ?>" value="off" /><input type="checkbox" name="<?php echo $this->get_field_name('state'); ?>" value="on" <?php echo $options['state'] == "on"?' checked="checked"':''; ?> /> <?php _e('Show state/province'); ?></label></p>

			<p>
			<label><input type="hidden" name="<?php echo $this->get_field_name('avatar'); ?>" value="off" /><input type="checkbox" name="<?php echo $this->get_field_name('avatar'); ?>" value="on" <?php echo $options['avatar'] == "on"?' checked="checked"':''; ?>/> <?php _e('Show Avatar'); ?></label> &nbsp; <input type="text" name="<?php echo $this->get_field_name('size'); ?>" size="5" value="<?php echo $options['size']; ?>" /><label> <?php _e('pixels','Shopp'); ?></label>
			</p>
			<?php
	    }

	} // END class ShoppShoppersWidget

}
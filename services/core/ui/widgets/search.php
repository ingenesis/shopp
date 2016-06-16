<?php
/**
 * ShoppSearchWidget class
 * A WordPress widget for showing a storefront-enabled search form
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
**/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppSearchWidget') ) {

	class ShoppSearchWidget extends WP_Widget {

	    function __construct () {
	        parent::__construct(
			'shopp-search',
			Shopp::__('Shopp Search'),
			array('description' => Shopp::__('A search form for your store')));
	    }

	    function widget($args, $options) {
			$Shopp = Shopp::object();
			if (!empty($args)) extract($args);

			if (empty($options['title'])) $options['title'] = Shopp::__('Shop Search');
			$title = $before_title.$options['title'].$after_title;

			$content = shopp('catalog','get-searchform');
			echo $before_widget.$title.$content.$after_widget;
	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

	    function form($options) {
	    	$defaults = array(
				'title' => '',
				);
	    	
			$options = array_merge($defaults, $options);	    	
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
			<?php
	    }

	} // END class ShoppSearchWidget

}
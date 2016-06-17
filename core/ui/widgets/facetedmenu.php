<?php
/**
 * ShoppFacetedMenuWidget
 *
 * A WordPress widget for showing a drilldown search menu for category products
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppFacetedMenuWidget') ) {

	class ShoppFacetedMenuWidget extends WP_Widget {

	    function __construct () {
	        parent::__construct(false,
				$name = Shopp::__('Shopp Faceted Menu'),
				array('description' => Shopp::__('Category products drill-down search menu'))
			);
	    }

        /**
		 * Display the widget content
         *
         * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
         * @param array $options The settings for the particular instance of the widget
         */
	    function widget ( $args, $options ) {

			if ( ! empty($args) )
				extract($args);

			if ( empty($options['title']) ) $options['title'] = Shopp::__('Product Filters');
			$title = $before_title . $options['title'] . $after_title;

			$Collection = ShoppCollection();
			if ( empty($Collection) ) return;

			if ( '' != shopp('collection.get-id') && shopp('collection.has-faceted-menu') ) {
				$menu = shopp('collection.get-faceted-menu', $options);
				echo $before_widget . $title . $menu . $after_widget;
			}

	    }

	    function update ( $new_instance, $old_instance ) {
	        return $new_instance;
	    }

		/**
		 * Renders the settings for this widget
		 *
		 * @param array $options The settings for the particular instance of the widget
		 * @return void
		 **/
	    function form ( $options ) {
	    	$defaults = array(
				'title' => '',
				);
	    	
			$options = array_merge($defaults, $options);	    	
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
			<?php
	    }

	} // class ShoppFacetedMenuWidget

}
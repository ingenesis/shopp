<?php
/**
 * facetedmenu.php
 *
 * A WordPress widget for showing a drilldown search menu for category products
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppFacetedMenuWidget extends WP_Widget {

    function __construct () {
        parent::__construct(false,
			Shopp::__('Shopp Faceted Menu'),
			array(
				'description' => Shopp::__('Category products drill-down search menu'),
				'classname' => 'shopp-faceted-menu-widget'
			)
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

		if ( empty($options['title']) ) $options['title'] = __('Product Filters');
		$title = $before_title . $options['title'] . $after_title;

		$Collection = ShoppCollection();
		if ( empty($Collection) ) return;

		if ( '' != shopp('collection.get-id') && shopp('collection.has-faceted-menu') ) {
			$menu = shopp('collection.get-faceted-menu', $options);
			echo $before_widget . $title . $menu . $after_widget;
		}

    }

	/**
	 * Renders the settings for this widget
	 *
	 * @param array $options The settings for the particular instance of the widget
	 * @return void
	 **/
    function form ( $options ) {
		$options = array_merge(array(
			'title' => ''
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
		<?php
    }

}


<?php
/**
 * search.php
 *
 * A WordPress widget for showing a storefront-enabled search form
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppSearchWidget extends WP_Widget {

    function __construct () {
        parent::__construct(
			'shopp-search-widget',
			Shopp::__('Shopp Search'),
			array(
				'description' => Shopp::__('A search form for your store'),
				'classname' => 'shopp-search-widget'
			)
		);
    }

    function widget($args, $options) {
		if ( ! empty($args) )
			extract($args);

		if ( empty($options['title']) ) $options['title'] = Shopp::__('Shop Search');
		$title = $before_title . $options['title'] . $after_title;

		echo $before_widget . $title . shopp('catalog.get-searchform') . $after_widget;
    }

    function form($options) {
		$options = array_merge(array(
			'title' => '',
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
		<?php
    }

}
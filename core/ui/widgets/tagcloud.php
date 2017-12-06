<?php
/**
 * tagcloud.php
 *
 * A WordPress widget that shows a cloud of the most popular product tags
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppTagCloudWidget extends WP_Widget {

    function __construct() {
        parent::__construct(false,
			Shopp::__('Shopp Tag Cloud'),
			array(
				'description' => Shopp::__('Popular product tags in a cloud format'),
				'classname' => 'shopp-tag-cloud-widget'
			)
		);
    }

    function widget ($args, $options) {
		if ( ! empty($args) ) extract($args);

		if ( empty($options['title']) )
			$options['title'] = Shopp::__('Product Tags');
		$title = $before_title . $options['title'] . $after_title;

		echo $before_widget . $title . shopp('catalog.get-tagcloud', $options) . $after_widget;
    }

    function form ($options) {
		$options = array_merge(array(
			'title' => '',
			'exclude' => ''
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude' ); ?></label> <input type="text" value="<?php echo $options['exclude']; ?>" name="<?php echo $this->get_field_name('exclude'); ?>" id="<?php echo $this->get_field_id('exclude'); ?>" class="widefat" />
			<br />
			<small><?php _e( 'Tags, separated by commas.', 'Shopp' ); ?></small>
		</p>
		<?php
    }

}
<?php
/**
 * shoppers.php
 *
 * A WordPress widget to show a list of recent shoppers
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppShoppersWidget extends WP_Widget {

	private $defaults = array(
		'title' => '',
		'abbr' => 'firstname',
		'city' => 'off',
		'state' => 'off',
		'avatar' => 'off',
		'size' => 48,
		'show' => 5
	);

    function __construct () {
        parent::__construct(
			'shopp-recent-shoppers-widget',
			Shopp::__('Shopp Recent Shoppers'),
			array(
				'description' => Shopp::__('Lists recent shoppers on your store'),
				'classname' => 'shopp-recent-shoppers-widget'
			)
		);
    }

    function widget ($args, $options) {
		$options = array_merge($this->defaults, $options);

		if ( ! empty($args) ) extract($args);

		if ( empty($options['title']) )
			$options['title'] = Shopp::__('Recent Shoppers');

		$title = $before_title . $options['title'] . $after_title;

		$content = shopp('catalog.get-recent-shoppers', $options);

		if ( empty($content) ) return false; // No recent shoppers, hide it

		echo $before_widget . $title . $content . $after_widget;
    }

    function form ($options) {
		$options = array_merge($this->defaults, $options);

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

		<p><label><?php Shopp::_e('Name format'); ?><select name="<?php echo $this->get_field_name('abbr'); ?>">
		<?php echo menuoptions($format_options, $options['abbr'], true); ?>
		</select></label></p>

		<p><label><input type="hidden" name="<?php echo $this->get_field_name('city'); ?>" value="off" /><input type="checkbox" name="<?php echo $this->get_field_name('city'); ?>" value="on" <?php echo $options['city'] == "on"?' checked="checked"':''; ?> /> <?php _e('Show city'); ?></label><br />
		<label><input type="hidden" name="<?php echo $this->get_field_name('state'); ?>" value="off" /><input type="checkbox" name="<?php echo $this->get_field_name('state'); ?>" value="on" <?php echo $options['state'] == "on"?' checked="checked"':''; ?> /> <?php _e('Show state/province'); ?></label></p>

		<p><label><input type="hidden" name="<?php echo $this->get_field_name('avatar'); ?>" value="off" /><input type="checkbox" name="<?php echo $this->get_field_name('avatar'); ?>" value="on" <?php echo $options['avatar'] == "on"?' checked="checked"':''; ?>/> <?php _e('Show Avatar'); ?></label></p>

		<p><label><?php Shopp::_e('Avatar size in pixels:'); ?><input type="number" name="<?php echo $this->get_field_name('size'); ?>" size="5" value="<?php echo $options['size']; ?>" /></label></p>
		<p><label><?php Shopp::_e('Number of shoppers to show:'); ?><input type="number" name="<?php echo $this->get_field_name('show'); ?>" size="5" value="<?php echo $options['show']; ?>" /></label></p>
		<?php
    }

}
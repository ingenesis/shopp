<?php
/**
 * section.php
 *
 * A WordPress widget that provides a navigation menu of a Shopp category section (branch)
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCategorySectionWidget extends WP_Widget {

    function __construct() {
        parent::__construct(false,
			$name = Shopp::__('Shopp Category Section'),
			array('description' => Shopp::__('A list or dropdown of store categories'))
		);
    }

    function widget ($args, $options) {
		extract($args);

		$title = $before_title . $options['title'] . $after_title;
		unset($options['title']);
		if ( empty(ShoppCollection()->id) ) return false;

		$menu = shopp(ShoppCollection(), 'get-section-list', $options);
		echo $before_widget . $title . $menu . $after_widget;
    }

    function form ($options) {
		$options = array_merge(array(
			'title' => '',
			'dropdown' => 'off',
			'products' => 'off',
			'hierarchy' => 'off'
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

		<p>
		<input type="hidden" name="<?php echo $this->get_field_name('dropdown'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>" value="on"<?php echo $options['dropdown'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('dropdown'); ?>"> <?php Shopp::_e('Show as dropdown'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('products'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('products'); ?>" name="<?php echo $this->get_field_name('products'); ?>" value="on"<?php echo $options['products'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('products'); ?>"> <?php Shopp::_e('Show product counts'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hierarchy'); ?>" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="on"<?php echo $options['hierarchy'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('hierarchy'); ?>"> <?php Shopp::_e('Show hierarchy'); ?></label><br />
		</p>
		<?php
    }

}
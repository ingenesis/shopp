<?php
/**
 * categories.php
 *
 * A WordPress widget that provides a navigation menu of Shopp categories
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCategoriesWidget extends WP_Widget {

    function __construct () {
        parent::__construct(false,
			Shopp::__('Shopp Categories'),
			array(
				'description' => Shopp::__('A list or dropdown of store categories'),
				'classname' => 'shopp-categories-widget'
			)
		);
    }

    function widget ( $args, $options ) {
		extract($args);

		$title = $before_title . $options['title'] . $after_title;
		unset($options['title']);
		$menu = shopp('catalog','get-category-list', $options);
		echo $before_widget . $title . $menu . $after_widget;
    }

    function form ( $options ) {
		$options = array_merge(array(
			'title' => '',
			'dropdown' => 'off',
			'products' => 'off',
			'hierarchy' => 'off',
			'depth' => '0',
			'showsmart' => 'hide',
			'exclude'	=> ''
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

		<p>
		<input type="hidden" name="<?php echo $this->get_field_name('dropdown'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>" value="on"<?php echo $options['dropdown'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('dropdown'); ?>"> <?php Shopp::_e('Show as dropdown'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('products'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('products'); ?>" name="<?php echo $this->get_field_name('products'); ?>" value="on"<?php echo $options['products'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('products'); ?>"> <?php Shopp::_e('Show product counts'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hierarchy'); ?>" name="<?php echo $this->get_field_name('hierarchy'); ?>" value="on"<?php echo $options['hierarchy'] == "on"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('hierarchy'); ?>"> <?php Shopp::_e('Show hierarchy'); ?></label><br />
		<input type="hidden" name="<?php echo $this->get_field_name('depth'); ?>" value="0" /><input type="checkbox" id="<?php echo $this->get_field_id('depth'); ?>" name="<?php echo $this->get_field_name('depth'); ?>" value="1"<?php echo $options['depth'] == "1"?' checked="checked"':''; ?> /><label for="<?php echo $this->get_field_id('depth'); ?>"> <?php Shopp::_e('Hide child categories'); ?></label><br />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude' ); ?></label> <input type="text" name="<?php echo $this->get_field_name('exclude'); ?>" id="<?php echo $this->get_field_id('exclude'); ?>" class="widefat" value="<?php echo $options['exclude']; ?>" />
			<br />
			<small><?php Shopp::_e( 'Category ID\'s, separated by commas.' ); ?></small>
		</p>
		<p><label for="<?php echo $this->get_field_id('showsmart'); ?>"><?php Shopp::_e('Smart Categories:'); ?>
			<select id="<?php echo $this->get_field_id('showsmart'); ?>" name="<?php echo $this->get_field_name('showsmart'); ?>" class="widefat"><option value="false"><?php _e('Hide'); ?></option><option value="before"<?php echo $options['showsmart'] == "before"?' selected="selected"':''; ?>><?php Shopp::_e('Include before custom categories'); ?></option><option value="after"<?php echo $options['showsmart'] == "after"?' selected="selected"':''; ?>><?php Shopp::_e('Include after custom categories'); ?></option></select></label></p>
		<?php
    }

}
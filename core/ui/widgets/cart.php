<?php
/**
 * cart.php
 *
 * A WordPress widget to show the contents of the shopping cart
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppCartWidget extends WP_Widget {

    function __construct () {
        parent::__construct(false,
			Shopp::__('Shopp Cart'),
			array(
				'description' => Shopp::__("The customer's shopping cart"),
				'classname' => 'shopp-cart-widget'
			)
		);
    }

    function widget ($args, $options) {
		$defaults = array(
			'hide-empty' => '',
			'title' => Shopp::__('Your Cart')
		);
		$options = array_merge($defaults, $options);

		if ( ! empty($args) ) extract($args);

		if ( Shopp::str_true($options['hide-empty']) && shopp_cart_items_count() == 0 ) return;

		$title = $before_title . $options['title'] . $after_title;

		$sidecart = shopp('cart.get-sidecart', $options);
		if ( empty($sidecart) ) return;

		echo $before_widget . $title . $sidecart . $after_widget;
    }

    function form ($options) {
		$options = array_merge(array(
			'title' => '',
			'hide-empty' => 'off'
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>

		<p><input type="hidden" name="<?php echo $this->get_field_name('hide-empty'); ?>" value="off" /><input type="checkbox" id="<?php echo $this->get_field_id('hide-empty'); ?>" name="<?php echo $this->get_field_name('hide-empty'); ?>" value="on"<?php echo Shopp::str_true($options['hide-empty']) ? ' checked="checked"' : ''; ?> /><label for="<?php echo $this->get_field_id('hide-empty'); ?>"> <?php _e('Hide when cart is empty','Shopp'); ?></label></p>
		<?php
    }

}
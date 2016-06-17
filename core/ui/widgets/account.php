<?php
/**
 * ShoppAccountWidget class
 * A WordPress widget to show the account login or account menu if logged in
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, 8 June, 2009
 * @package shopp
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

if ( class_exists('WP_Widget') && ! class_exists('ShoppAccountWidget') ) {

	class ShoppAccountWidget extends WP_Widget {

	    function __construct() {

			if ( 'none' == shopp_setting('account_system') ) {
				return parent::__construct(
					'shopp-order-lookup',
					__('Shopp Order Lookup','Shopp'),
					array('description' => __('Lookup orders by order number and email','Shopp'))
				);
			}

	        parent::__construct(
				'shopp-account',
				__('Shopp Account','Shopp'),
				array('description' => __('Customer account management dashboard','Shopp'))
			);
	    }

	    function widget($args, $options) {
			if (!empty($args)) extract($args);

			$loggedin = ShoppCustomer()->loggedin();
			// Hide login form on account page when not logged in to prevent duplicate forms
			if (is_account_page() && !$loggedin) return '';

			$defaults = array(
				'title' => $loggedin?__('Your Account','Shopp'):__('Login','Shopp'),
			);
			$options = array_merge($defaults,$options);
			extract($options);

			$title = $before_title.$title.$after_title;


			remove_filter('shopp_show_account_errors',array($this,'showerrors'));
			$Page = new ShoppAccountPage();

			$menu = $Page->content('','widget');
			echo $before_widget.$title.$menu.$after_widget;

	    }

	    function update($new_instance, $old_instance) {
	        return $new_instance;
	    }

		function showerrors () {
			return false;
		}

	    function form($options) {
			?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
			<?php
	    }

	} // END class ShoppAccountWidget

}

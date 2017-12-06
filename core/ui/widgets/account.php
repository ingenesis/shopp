<?php
/**
 * account.php
 *
 * A WordPress widget to show the account login or account menu if logged in
 *
 * @copyright Ingenesis Limited, May 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/UI/Widgets
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAccountWidget extends WP_Widget {

    function __construct () {

		if ( 'none' == shopp_setting('account_system') ) {
			return parent::__construct(
				'shopp-order-lookup-widget',
				Shopp::__('Shopp Order Lookup'),
				array(
					'description' => Shopp::__('Lookup orders by order number and email'),
					'classname' => 'shopp-order-lookup-widget'
				)
			);

		}

        parent::__construct(
			'shopp-account-widget',
			Shopp::__('Shopp Account'),
			array(
				'description' => Shopp::__('Customer account management dashboard'),
				'classname' => 'shopp-account-widget'
			)
		);

    }

    function widget ($args, $options) {
		if ( ! empty($args) ) extract($args);

		$loggedin = ShoppCustomer()->loggedin();
		// Hide login form on account page when not logged in to prevent duplicate forms
		if (is_account_page() && !$loggedin) return '';

		$defaults = array(
			'title' => $loggedin ? Shopp::__('Your Account') : Shopp::__('Login'),
		);
		$options = array_merge($defaults, $options);
		extract($options);

		$title = $before_title . $title . $after_title;

		remove_filter('shopp_show_account_errors', '__return_false');
		$Page = new ShoppAccountPage();

		$menu = $Page->content('', 'widget');
		echo $before_widget . $title . $menu . $after_widget;

    }

    function form ($options) {
		$options = array_merge(array(
			'title' => ''
		), $options);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
		<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title'); ?>" class="widefat" value="<?php echo $options['title']; ?>"></p>
		<?php
    }

}
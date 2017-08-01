<?php
/**
 * ScreenPayments.php
 *
 * Payments screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPayments extends ShoppSettingsScreenController {

	/**
	 * Enqueue required script and style assets for the UI
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('payments');
		shopp_localize_script( 'payments', '$ps', array(
			'confirm' => __('Are you sure you want to remove this payment system?','Shopp'),
		));
	}

	/**
	 * Setup the layout for the screen
	 * 
	 * This is used to initialize any metaboxes or tables.
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function layout() {
		$this->table('ShoppScreenPaymentsTable');
	}

	/**
	 * Register action request methods
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function actions() {
		add_action('shopp_admin_settings_actions', array($this, 'delete') );
	}

	/**
	 * Register processing operation methods
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function ops() {
        add_action('shopp_admin_settings_ops', array($this, 'add') );
        add_action('shopp_admin_settings_ops', array($this, 'updates') );
	}

	/**
	 * Delete requested payment entry
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function delete() {
		$delete = $this->request('delete');
		if ( false === $delete ) return;

		check_admin_referer('shopp_delete_gateway');

		$gateways = array_keys(Shopp::object()->Gateways->activated());

		if ( ! in_array($delete, $gateways) )
			return $this->notice(Shopp::__('The requested payment system could not be deleted because it does not exist.'), 'error');

		$position = array_search($delete, $gateways);
		array_splice($gateways, $position, 1);
		shopp_set_setting('active_gateways', join(',', $gateways));

		$this->notice(Shopp::__('Payment system removed.'));

		Shopp::redirect(add_query_arg(array('delete' => null, '_wpnonce' => null)));
	}
    
	/**
	 * Add a payment entry for a specified payment gateway
	 *
	 * @since 1.4
	 * @return void
	 **/
    public function add() {
		$form = $this->form();
		if ( empty($form) ) return;
        
        $id = $this->form('id');
        if ( empty($id) ) return;

        $gateway = $id;
        $index = false;

        if ( false !== strpos($id, '-') ) 
            list($gateway, $index) = explode('-', $gateway);
        
        if ( isset($Gateways->active[ $gateway ]) ) {
            $Gateway = $Gateways->get($gateway);
            if ( $Gateway->multi && false === $index ) {
                unset($Gateway->settings['cards'], $Gateway->settings['label']);
                $index = count($Gateway->settings);
            }
        }
        
    }

	/**
	 * Handle saving form updates
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function updates() {
		$form = $this->form();
		if ( empty($form) ) return;

		do_action('shopp_save_payment_settings');
		$Gateways = Shopp::object()->Gateways;

		$gateways = array_keys($Gateways->activated());
		$gateway = key($form);
        
		// Handle Multi-instance payment systems
		$indexed = false;
		if ( preg_match('/\[(\d+)\]/', $gateway, $matched) ) {
			$indexed = '-' . $matched[1];
			$gateway = str_replace($matched[0], '', $gateway);
		}

		// Merge the existing gateway settings with the newly updated settings
		if ( isset($Gateways->active[ $gateway ]) ) {
			$Gateway = $Gateways->active[ $gateway ];
			// Cannot use array_merge() because it adds numeric index values instead of overwriting them
			$this->form[ $gateway ] = (array) $this->form[ $gateway ] + (array) $Gateway->settings;
		}

		// Add newly activated gateways
		if ( ! in_array($gateway . $indexed, $gateways) ) {
			$gateways[] =  $gateway . $indexed;
			shopp_set_setting('active_gateways', join(',', $gateways));
		}

		// Save the gateway settings
		shopp_set_formsettings();

		$this->notice(Shopp::__('Shopp payments settings saved.'));

        if ( $this->form('save') )
    		Shopp::redirect(add_query_arg(array('id' => null)));
        
		Shopp::redirect(add_query_arg());
	}

	/**
	 * Render the screen UI
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function screen() {
		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('payments.php');
	}

}
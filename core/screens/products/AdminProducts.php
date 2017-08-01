<?php
/**
 * AdminProducts.php
 *
 * Products admin request router
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Orders
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminProducts extends ShoppAdminPostController {

	protected $ui = 'products';
    
    /**
     * Handles the admin page request
     *
     * @since 1.4
     * 
     * @return string ShoppScreenController The screen controller class name to handle the request
     **/
	protected function route () {
		// @todo implement post type editor
		// if ( ! empty($this->request('post')) && ShoppProduct::posttype() == $this->request('post_type') && 'edit' == $this->request('action') )
		// 	return 'ShoppScreenProductEditor';

		if ( $this->request('id') )
			return 'ShoppScreenProductEditor';
		else return 'ShoppScreenProducts';
	}

}
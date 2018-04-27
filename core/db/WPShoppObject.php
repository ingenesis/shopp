<?php
/**
 * WPShoppObject.php
 *
 * A foundational Shopp/WordPress CPT DatabaseObject
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.2
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class WPShoppObject extends WPDatabaseObject {

	/* @var	string The post type name for this object */
	static $posttype = 'shopp_post';

	/**
	 * Handles loading a WPShoppObject
	 *
	 * @return void
	 **/
	public function load () {
		$args = func_get_args();
		if ( empty($args[0]) ) return false;

		if ( count($args) == 2 ) {
			list($id, $key) = $args;
			if ( empty($key) ) $key = $this->_key;
			$p = array($key => $id);
		}
		if ( is_array($args[0]) ) $p = $args[0];

		$class = get_class($this);
		$p['post_type'] = get_class_property($class, 'posttype');

		parent::load($p);
	}

	/**
	 * Defines the labels for the post type object
	 *
	 * @return array The list of labels
	 **/
	public static function labels () {
		return array(
			'name' => Shopp::__('Posts'),
			'singular_name' => Shopp::__('Post')
		);
	}

	/**
	 * Defines the capabilities for managing this post type object
	 *
	 * @return array List of defined capabilities
	 **/
	public static function capabilities () {
		return apply_filters( 'shopp_product_capabilities', array(
			'edit_post' => self::$posttype,
			'delete_post' => self::$posttype
		) );
	}

	/**
	 * Defines the editor support for this post type object
	 *
	 * @return array The list of supported editor features
	 **/
	public static function supports () {
		return array(
			'title',
			'editor'
		);
	}

	/**
	 * Registers this post type object with WordPress
	 *
	 * @param string $class The class name for this object
	 * @param string $slug The slug for the post type
	 * @return void
	 **/
	public static function register ( $class, $slug ) {
		$posttype = get_class_property($class, 'posttype');
		register_post_type( $posttype, array(
			'labels' => call_user_func(array($class, 'labels')),
			'capabilities' => call_user_func(array($class, 'capabilities')),
			'supports' => call_user_func(array($class, 'supports')),
			'rewrite' => array( 'slug' => $slug, 'with_front' => false ),
			'public' => true,
			'has_archive' => true,
			'show_ui' => false,
			'_edit_link' => 'admin.php?page=shopp-products&id=%d'
		));
	}
}
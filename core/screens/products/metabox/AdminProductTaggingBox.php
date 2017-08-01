<?php
/**
 * AdminProductTaggingBox.php
 *
 * Product editor tags metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminProductTaggingBox extends ShoppAdminTaxonomyMetabox {

	protected $view = 'products/tagging.php';

}

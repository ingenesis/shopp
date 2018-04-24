<?php
/**
 * AdminTaxonomyMetabox.php
 *
 * Product editor generic taxonomy metabox. Used as a basis for the Categories, 
 * Tags and custom taxonomy metaboxes.
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     @since 1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppAdminTaxonomyMetabox extends ShoppAdminMetabox {

	protected $id = '-taxonomy-box';

	public function __construct ( $posttype, $context, $priority, array $args = array() ) {

		$this->references = $args;
		$this->init();
		$this->request($_POST);

		$this->label = $this->references['label'];
		$this->id = $this->references['taxonomy'] . $this->id;

		add_meta_box($this->id, $this->title() . self::help($this->id), array($this, 'box'), $posttype, $context, $priority, $args);

	}

	protected function title () {
		return $this->references['label'];
	}

}
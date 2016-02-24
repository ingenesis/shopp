<?php
/**
 * Pages.php
 *
 * Pages settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPages extends ShoppSettingsScreenController {

	public function assets () {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('pageset');
	}

	public function layout () {
		$this->table('ShoppPagesSettingsTable');
	}

	public function updates () {
		$CatalogPage = ShoppPages()->get('catalog');
		$catalog_slug = $CatalogPage->slug();
		$defaults = ShoppPages()->settings();
		$this->form['storefront_pages'] = array_merge($defaults, $this->form('storefront_pages'));
		shopp_set_formsettings();

		// Re-register page, collection, taxonomies and product rewrites
		// so that the new slugs work immediately
		$Shopp = Shopp::object();
		$Shopp->pages();
		$Shopp->collections();
		$Shopp->taxonomies();
		$Shopp->products();

		// If the catalog slug changes
		// $hardflush is false (soft flush... plenty of fiber, no .htaccess update needed)
		$hardflush = ( ShoppPages()->baseslug() != $catalog_slug );
		flush_rewrite_rules($hardflush);
	}

	public function screen () {

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('pages.php');

	}
	
	public function url ( $params = array() ) {
		$url = parent::url($params);
		return remove_query_arg('edit', $url);
	}
	

} // class ShoppScreenPages

/**
 * Pages Table UI renderer
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppPagesSettingsTable extends ShoppAdminTable {

	public function prepare_items() {
		$this->id = 'pages';
		$settings = ShoppPages()->settings();

		$template = array(
			'id'          => '',
			'name'        => '',
			'title'       => '',
			'slug'        => '',
			'description' => ''
		);

		foreach ( $settings as $name => $page ) {
			$page['name'] = $name;
			$page['id'] = $name;
			$this->items[ $name ] = (object) array_merge($template, $page);
		}
		
		$per_page = 25;
		$total = count($this->items);
		$this->set_pagination_args( array(
			'total_items' => $total,
			'total_pages' => $total / $per_page,
			'per_page' => $per_page
		) );

		shopp_custom_script('pageset', 'var pages = ' . json_encode($this->items) . ';');

	}

	public function get_columns() {
		return array(
			'title'      => Shopp::__('Title'),
			'slug'       => Shopp::__('Slug'),
			'description' => Shopp::__('Description'),
		);
	}

	public function no_items() {
		Shopp::_e('No Shopp pages available! The sky is falling! Contact the Help Desk, stat!');
	}

	protected function editing( $Item ) {
		return ( $Item->id == $this->request('edit') );
	}

	public function editor( $Item ) {
		$data = array(
			'${id}' => "edit-{$Item->name}-page",
			'${name}' => $Item->name,
			'${title}' => $Item->title,
			'${slug}' => $Item->slug,
			'${description}' => $Item->description
		);
		
		echo ShoppUI::template($this->editor, $data);
	}

	public function column_default( $Item ) {
		echo '.';
	}

	public function column_title( $Item ) {
		$title = empty($Item->title) ? '(' . Shopp::__('not set') . ')' : $Item->title;

		$edit_link = wp_nonce_url(add_query_arg('edit', $Item->id), 'shopp-settings-images');

		echo '<a class="row-title edit" href="' . $edit_link . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($title) . '&quot;">' . esc_html($title) . '</a>';

		echo $this->row_actions( array(
			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>'
		) );

	}

	public function column_slug( $Item ) {
		echo esc_html($Item->slug);
	}

	public function column_description( $Item ) {
		echo esc_html($Item->description);
	}

} // class ShoppPagesSettingsTable
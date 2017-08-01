<?php
/**
 * ScreenPagesTable.php
 *
 * Pages table UI renderer
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenPagesTable extends ShoppAdminTable {

	/**
	 * Prepare page setting item data for rendering in the table
	 *
	 * @since 1.4
	 * @return void
	 **/
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
	
	/**
	 * Provide the list of columns for the table
	 *
	 * @since 1.4
	 * @return array The list of columns to render for the table
	 **/
	public function get_columns() {
		return array(
			'title'      => Shopp::__('Title'),
			'slug'       => Shopp::__('Slug'),
			'description' => Shopp::__('Description'),
		);
	}

	/**
	 * Renders a default message when no items are available
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function no_items() {
		Shopp::_e('No Shopp pages available! The sky is falling! Contact the Help Desk, stat!');
	}

	/**
	 * Determines if editing is requested for the current item
	 *
	 * @since 1.4
	 * @return boolean True if it is an edit request, false otherwise
	 **/
	protected function editing( $Item ) {
		return ( $Item->id == $this->request('edit') );
	}

	/**
	 * Renders the editor template
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return void
	 **/
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

	/**
	 * Renders a fallback default column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_default( $Item ) {
		return '';
	}

	/**
	 * Renders a title column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_title( $Item ) {
		$title = empty($Item->title) ? '(' . Shopp::__('not set') . ')' : $Item->title;

		$edit_link = wp_nonce_url(add_query_arg('edit', $Item->id), 'shopp-settings-pages');

		return '<a class="row-title edit" href="' . $edit_link . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($title) . '&quot;">' . esc_html($title) . '</a>';

		return $this->row_actions( array(
			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>'
		) );
	}

	/**
	 * Renders a slug column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_slug( $Item ) {
		return esc_html($Item->slug);
	}

	/**
	 * Renders a description column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_description( $Item ) {
		return esc_html($Item->description);
	}

}
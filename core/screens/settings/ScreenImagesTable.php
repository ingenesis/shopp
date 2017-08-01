<?php
/**
 * ScreenImagesTable.php
 *
 * Image settings table UI renderer
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since	 1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenImagesTable extends ShoppAdminTable {

	/**
	 * Prepare image setting item data for rendering in the table
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function prepare_items() {
		$defaults = array(
			'paged' => 1,
			'per_page' => 25,
			'action' => false,
			'selected' => array(),
		);
		$args = array_merge($defaults, $_GET);
		extract($args, EXTR_SKIP);

		$start = ( $per_page * ( $paged - 1 ) );
		$edit = false;
		$ImageSetting = new ShoppImageSetting($edit);
		$table = $ImageSetting->_table;
		$columns = 'SQL_CALC_FOUND_ROWS *';
		$where = array(
			"type='$ImageSetting->type'",
			"context='$ImageSetting->context'"
		);
		$limit = "$start,$per_page";

		$options = compact('columns', 'useindex', 'table', 'joins', 'where', 'groupby', 'having', 'limit', 'orderby');
		$query = sDB::select($options);

		$this->items = sDB::query($query, 'array', array($ImageSetting, 'loader'));
		$found = sDB::found();

		$json = array();
		$skip = array('created', 'modified', 'numeral', 'context', 'type', 'sortorder', 'parent');
		foreach ( $this->items as &$Item)
			if ( method_exists($Item, 'json') )
				$json[ $Item->id ] = $Item->json($skip);

		shopp_custom_script('imageset', 'var images = ' . json_encode($json) . ';');

		$this->set_pagination_args( array(
			'total_items' => $found,
			'total_pages' => $found / $per_page,
			'per_page' => $per_page
		) );
	}

	/**
	 * Provide a list of actions for the bulk actions menu
	 *
	 * @since 1.4
	 * @return array The list of bulk actions
	 **/
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete' ),
		);
	}
	
	/**
	 * Render custom table navigation elements
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;
		echo  '	<div class="alignleft actions">'
			. '		<a href="' . esc_url(add_query_arg('id', 'new')) . '" class="button add-new">' . Shopp::__('Add New') . '</a>'
			. '	</div>';
	}

	/**
	 * Provide the list of columns for the table
	 *
	 * @since 1.4
	 * @return array The list of columns to render for the table
	 **/
	public function get_columns() {
		return array(
			'cb'		 => '<input type="checkbox" />',
			'name'	   => Shopp::__('Name'),
			'dimensions' => Shopp::__('Dimensions'),
			'fit'		=> Shopp::__('Fit'),
			'quality'	=> Shopp::__('Quality'),
			'sharpness'  => Shopp::__('Sharpness')
		);
	}

	/**
	 * Renders a default message when no items are available
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function no_items() {
		Shopp::_e('No predefined image settings available, yet.');
	}

	/**
	 * Determines if editing is requested for the current item
	 *
	 * @since 1.4
	 * @return boolean True if it is an edit request, false otherwise
	 **/
	protected function editing( $Item ) {
		return ( $Item->id == $this->request('id') );
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
			'${id}' => $Item->id,
			'${name}' => $Item->name,
			'${width}' => $Item->width,
			'${height}' => $Item->height,
			'${sharpen}' => $Item->sharpen,
			'${select_fit_' . $Item->fit . '}' => ' selected="selected"',
			'${select_quality_' . $Item->quality . '}' => ' selected="selected"'
		);
		echo ShoppUI::template($this->editor, $data);
	}
	
	/**
	 * Renders the checkbox column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_cb( $Item ) {
		return '<input type="checkbox" name="selected[]" value="' . $Item->id . '" />';
	}

	/**
	 * Renders the name column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_name( $Item ) {
		$edit_link = wp_nonce_url(add_query_arg('id', $Item->id), 'shopp-settings-images');
		$delete_link = wp_nonce_url(add_query_arg('delete', $Item->id), 'shopp-settings-images');

		return '<a class="row-title edit" href="' . $editurl . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($Item->name) . '&quot;">' . esc_html($Item->name) . '</a>' .
				$this->row_actions( array(
					'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
					'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>',
				) );
	}

	/**
	 * Renders the dimensions column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_dimensions( $Item ) {
		return esc_html("$Item->width &times; $Item->height");
	}

	/**
	 * Renders the fit column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_fit( $Item ) {
		$menu = ShoppImageSetting::fit_menu();
		$fit = isset($menu[ $Item->fit ]) ? $menu[ $Item->fit ] : '?';
		return esc_html($fit);
	}

	/**
	 * Renders the quality column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_quality( $Item ) {
		$quality = isset(ShoppImageSetting::$qualities[ $Item->quality ]) ?
						ShoppImageSetting::$qualities[ $Item->quality ] :
						$Item->quality;

		$quality = percentage($quality, array('precision' => 0));
		return esc_html($quality);
	}

	/**
	 * Renders the sharpness column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_sharpness( $Item ) {
		return esc_html("$Item->sharpen%");
	}

}
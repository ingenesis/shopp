<?php
/**
 * ShoppScreenShipmentBoxesTable.php
 *
 * Shipment boxes table UI renderer
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Admin\Settings
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppScreenShipmentBoxesTable extends ShoppAdminTable {

	/**
	 * Prepare shipment boxes setting item data for rendering in the table
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function prepare_items() {
		/* @todo Complete this method */
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
			'cb'        => '<input type="checkbox" />',
			'name'      => Shopp::__('Name'),
			'length'    => Shopp::__('Length'),
			'width'     => Shopp::__('Width'),
			'height'    => Shopp::__('Height'),
			'maxweight' => Shopp::__('Max Weight'),
		);
	}

	/**
	 * Renders a default message when no items are available
	 *
	 * @since 1.4
	 * @return void
	 **/
	public function no_items() {
		Shopp::_e('No shipment boxes defined, yet.');
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
	 * Renders the checkbox column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_cb( $Item ) {
		echo '<input type="checkbox" name="selected[]" value="' . $Item->id . '" />';
	}

	/**
	 * Renders a name column for the current item
	 *
	 * @since 1.4
	 * @param stdClass $Item The item data to render
	 * @return string The markup to output
	 **/
	public function column_name( $Item ) {
		echo '<a class="row-title edit" href="' . $editurl . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($Item->name) . '&quot;">' . esc_html($Item->name) . '</a>';

		$edit_link = wp_nonce_url(add_query_arg('id', $Item->id), 'shopp-settings-images');
		$delete_link = wp_nonce_url(add_query_arg('delete', $Item->id), 'shopp-settings-images');

		echo $this->row_actions( array(
			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
			'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>',
		) );

	}


}
<?php
/**
 * Images.php
 *
 * Image settings screen controller
 *
 * @copyright Ingenesis Limited, February 2015
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Admin/Settings
 * @version   1.0
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Screen controller for the image settings screen
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppScreenImages extends ShoppSettingsScreenController {

	/**
	 * Lookout for image size delete requests or else defer to the parent class's
	 * posted() method.
	 *
	 * @return bool
	 */
	public function posted() {
		if ( $this->request( 'delete' ) ) return true;
		else return parent::posted();
	}

	public function assets() {
		shopp_enqueue_script('jquery-tmpl');
		shopp_enqueue_script('imageset');
		shopp_localize_script( 'imageset', '$is', array(
			'confirm' => __('Are you sure you want to remove this image preset?','Shopp'),
		));
	}

	public function ops () {
		add_action('shopp_admin_settings_ops', array($this, 'updates') );
		add_action('shopp_admin_settings_ops', array($this, 'delete') );
	}

	public function updates () {
		$updates = $this->form();
		if ( empty($updates['name']) ) return;

		$ImageSetting = new ShoppImageSetting($updates['id']);

		$updates['name']    = sanitize_title_with_dashes($updates['name']);
		$updates['sharpen'] = floatval(str_replace('%', '', $updates['sharpen']));

		$ImageSetting->updates($updates);
		$ImageSetting->save();

		$this->notice(Shopp::__('Image setting &quot;%s&quot; saved.', $updates['name']));
	}

	public function delete () {
		$requests = $this->form('selected');
		if ( empty($requests) )
			$requests = array( $this->request('delete') );

		$requests = array_filter($requests);

		if ( empty($requests) ) return;

		$deleted = 0;
		foreach ( $requests as $delete ) {
			$Record = new ShoppImageSetting( (int) $delete );
			if ( $Record->delete() )
				$deleted++;
		}

		if ( $deleted > 0 )
			$this->notice(Shopp::_n('%d setting deleted.', '%d settings deleted.', $deleted));

	}

	public function layout() {
		$this->table('ShoppImageSettingsTable');
	}

	public function screen() {

		$fit_menu = ShoppImageSetting::fit_menu();
		$quality_menu = ShoppImageSetting::quality_menu();

		$Table = $this->table();
		$Table->prepare_items();

		include $this->ui('images.php');

	}

} // class ShoppScreenImages

/**
 * Images Table UI renderer
 *
 * @since 1.4
 * @package Shopp/Admin/Settings
 **/
class ShoppImageSettingsTable extends ShoppAdminTable {

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

	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete' ),
		);
	}

	public function extra_tablenav( $which ) {
		if ( 'bottom' == $which ) return;
		echo  '	<div class="alignleft actions">'
			. '		<a href="' . esc_url(add_query_arg('id', 'new')) . '" class="button add-new">' . Shopp::__('Add New') . '</a>'
			. '	</div>';
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'name'       => Shopp::__('Name'),
			'dimensions' => Shopp::__('Dimensions'),
			'fit'        => Shopp::__('Fit'),
			'quality'    => Shopp::__('Quality'),
			'sharpness'  => Shopp::__('Sharpness')
		);
	}

	public function no_items() {
		Shopp::_e('No predefined image settings available, yet.');
	}

	protected function editing( $Item ) {
		return ( $Item->id == $this->request('id') );
	}

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

	public function column_cb( $Item ) {
		echo '<input type="checkbox" name="selected[]" value="' . $Item->id . '" />';
	}

	public function column_name( $Item ) {
		echo '<a class="row-title edit" href="' . $editurl . '" title="' . Shopp::__('Edit') . ' &quot;' . esc_attr($Item->name) . '&quot;">' . esc_html($Item->name) . '</a>';

		$edit_link = wp_nonce_url(add_query_arg('id', $Item->id), 'shopp-settings-images');
		$delete_link = wp_nonce_url(add_query_arg('delete', $Item->id), 'shopp-settings-images');

		echo $this->row_actions( array(
			'edit' => '<a class="edit" href="' . $edit_link . '">' . __( 'Edit' ) . '</a>',
			'delete' => '<a class="delete" href="' . $delete_link . '">' . __( 'Delete' ) . '</a>',
		) );

	}

	public function column_dimensions( $Item ) {
		echo esc_html("$Item->width &times; $Item->height");
	}

	public function column_fit( $Item ) {
		$menu = ShoppImageSetting::fit_menu();
		$fit = isset($menu[ $Item->fit ]) ? $menu[ $Item->fit ] : '?';
		echo esc_html($fit);
	}

	public function column_quality( $Item ) {
		$quality = isset(ShoppImageSetting::$qualities[ $Item->quality ]) ?
						ShoppImageSetting::$qualities[ $Item->quality ] :
						$Item->quality;

		$quality = percentage($quality, array('precision' => 0));
		echo esc_html($quality);
	}

	public function column_sharpness( $Item ) {
		echo esc_html("$Item->sharpen%");
	}

} // class ShoppImageSettingsTable
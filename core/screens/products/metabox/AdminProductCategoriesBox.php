<?php
/**
 * AdminProductCategoriesBox.php
 *
 * Product editor categories metabox
 *
 * @copyright Ingenesis Limited, June 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   \Shopp\Screens\Products
 * @since     1.4
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Product editor categories meta box
 *
 * @since 1.4
 **/
class ShoppAdminProductCategoriesBox extends ShoppAdminTaxonomyMetabox {

	protected $view = 'products/categories.php';

	public static function popular_terms_checklist ( $post_ID, $taxonomy, $number = 10 ) {
		if ( $post_ID )
			$checked_terms = wp_get_object_terms($post_ID, $taxonomy, array('fields'=>'ids'));
		else
			$checked_terms = array();

		$terms = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $number, 'hierarchical' => false ) );

		$tax = get_taxonomy($taxonomy);
		if ( ! current_user_can($tax->cap->assign_terms) )
			$disabled = 'disabled="disabled"';
		else
			$disabled = '';

		$popular_ids = array();
		foreach ( (array) $terms as $term ) {
			$popular_ids[] = $term->term_id;
			$id = "popular-$taxonomy-$term->term_id";
			$checked = in_array( $term->term_id, $checked_terms ) ? 'checked="checked"' : '';
			?>

			<li id="<?php echo $id; ?>" class="popular-category">
				<label class="selectit">
				<input id="in-<?php echo $id; ?>" type="checkbox" <?php echo $checked; ?> value="<?php echo (int) $term->term_id; ?>" <?php echo $disabled ?>/>
					<?php echo esc_html( apply_filters( 'the_category', $term->name ) ); ?>
				</label>
			</li>

			<?php
		}
		return $popular_ids;
	}

}

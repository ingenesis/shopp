<?php

function save_meta_box ($Product) {
	$Shopp = Shopp::object();

	$workflows = array(
		"continue" => __('Continue Editing','Shopp'),
		"close" => __('Products Manager','Shopp'),
		"new" => __('New Product','Shopp'),
		"next" => __('Edit Next','Shopp'),
		"previous" => __('Edit Previous','Shopp')
		);


	$date_format = get_option('date_format');
	$time_format = get_option('time_format');

?>
	<div id="misc-publishing-actions">
		<input type="hidden" name="id" value="<?php echo $Product->id; ?>" />

		<div class="misc-pub-section misc-pub-section-last">
			<input type="hidden" name="status" value="draft" /><input type="checkbox" name="status" value="publish" id="published" tabindex="11" <?php if ($Product->status == "publish") echo ' checked="checked"'?> /><label for="published"><strong> <?php if ($Product->published() && !empty($Product->id)) _e('Published','Shopp'); else _e('Publish','Shopp'); ?></strong> <span id="publish-status"><?php if ($Product->publish>1) printf(__('on: %s', 'Shopp'),"</span><br />".date($date_format.' @ '.$time_format,$Product->publish)); else echo "</span>"; ?></label> <span id="schedule-toggling"><button type="button" name="schedule-toggle" id="schedule-toggle" class="button-secondary"><?php if ($Product->publish>1) _e('Edit','Shopp'); else _e('Schedule','Shopp'); ?></button></span>

			<div id="scheduling">
				<div id="schedule-calendar" class="calendar-wrap">
					<?php
						$previous = false;
						$dateorder = Shopp::date_format_order(true);
						foreach ($dateorder as $type => $format):
							if ($previous == "s" && $type[0] == "s") continue;
							if ("month" == $type): ?><input type="text" name="publish[month]" id="publish-month" title="<?php _e('Month','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("n",$Product->publish):''; ?>" class="publishdate selectall" /><?php elseif ("day" == $type): ?><input type="text" name="publish[date]" id="publish-date" title="<?php _e('Day','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("j",$Product->publish):''; ?>" class="publishdate selectall" /><?php elseif ("year" == $type): ?><input type="text" name="publish[year]" id="publish-year" title="<?php _e('Year','Shopp'); ?>" size="4" maxlength="4" value="<?php echo ($Product->publish>1)?date("Y",$Product->publish):''; ?>" class="publishdate selectall" /><?php elseif ($type[0] == "s"): echo "/"; endif; $previous = $type[0]; ?><?php endforeach; ?>
					 <br />
					<input type="text" name="publish[hour]" id="publish-hour" title="<?php _e('Hour','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("g",$Product->publish):date('g'); ?>" class="publishdate selectall" />:<input type="text" name="publish[minute]" id="publish-minute" title="<?php _e('Minute','Shopp'); ?>" size="2" maxlength="2" value="<?php echo ($Product->publish>1)?date("i",$Product->publish):date('i'); ?>" class="publishdate selectall" />
					<select name="publish[meridiem]" class="publishdate">
					<?php echo Shopp::menuoptions(array('AM' => __('AM','Shopp'),'PM' => __('PM','Shopp')),date('A',$Product->publish),true); ?>
					</select>
				</div>
			</div>

		</div>

	</div>
	<div id="major-publishing-actions">
		<select name="settings[workflow]" id="workflow">
		<?php echo menuoptions($workflows,shopp_setting('workflow'),true); ?>
		</select>
	<input type="submit" class="button-primary" name="save" value="<?php _e('Save Product','Shopp'); ?>" />
	</div>
<?php
}
ShoppUI::addmetabox(
	'save-product',
	__('Save','Shopp').$Admin->boxhelp('product-editor-save'),
	'save_meta_box',
	Product::$posttype,
	'side',
	'core'
);

function shopp_popular_terms_checklist( $post_ID, $taxonomy, $default = 0, $number = 10, $echo = true ) {
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
		if ( !$echo ) // hack for AJAX use
			continue;
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

function shopp_categories_meta_box ($Product,$options) {
	$defaults = array('taxonomy' => 'shopp_category');
	if ( !isset($options['args']) || !is_array($options['args']) ) $options = array();
	else $options = $options['args'];
	extract( wp_parse_args($options, $defaults), EXTR_SKIP );
	$tax = get_taxonomy($taxonomy);
?>
<div id="taxonomy-<?php echo $taxonomy; ?>" class="category-metabox">
	<div id="<?php echo $taxonomy; ?>-pop" class="multiple-select category-menu tabs-panel hide-if-no-js hidden">
		<ul id="<?php echo $taxonomy; ?>-checklist-pop" class="form-no-clear">
			<?php $popular_ids = shopp_popular_terms_checklist($Product->id,$taxonomy); ?>
		</ul>
	</div>

	<div id="<?php echo $taxonomy; ?>-all" class="multiple-select category-menu tabs-panel">
		<ul id="<?php echo $taxonomy; ?>-checklist" data-wp-lists="list:<?php echo $taxonomy; ?>" class="list:<?php echo $taxonomy; ?> form-no-clear">
		<?php wp_terms_checklist($Product->id, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids) ) ?>
		</ul>
	</div>

	<div id="<?php echo $taxonomy; ?>-add" class="new-category hide-if-no-js">
	<input type="text" name="new<?php echo $taxonomy; ?>" value="" id="new-<?php echo $taxonomy; ?>-name" /><br />
	<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => $tax->labels->parent_item.'&hellip;', 'tab_index' => 3 ) ); ?>

	<input type="button" id="<?php echo $taxonomy; ?>-add-submit" value="<?php _e('Add'); ?>" data-wp-lists="add:<?php echo $taxonomy ?>-checklist:<?php echo $taxonomy ?>-add" class="add:<?php echo $taxonomy ?>-checklist:taxonomy-<?php echo $taxonomy ?> button <?php echo $taxonomy ?>-add-submit" tabindex="3" />
	<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
	<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
	</div>

	<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
		<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php _e('Show All'); ?></a></li>
		<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Popular','Shopp' ); ?></a></li>
		<li class="hide-if-no-js new-category"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"  class="new-category-tab"><?php _e( 'New Category' ); ?></a></li>
	</ul>
</div><?php
}

function shopp_tags_meta_box ($Product, $options) {
	$defaults = array('taxonomy' => 'shopp_tag');
	if ( !isset($options['args']) || !is_array($options['args']) ) $options = array();
	else $options = $options['args'];
	extract( wp_parse_args($options, $defaults), EXTR_SKIP );
	$tax = get_taxonomy($taxonomy);
	$disabled = !current_user_can($tax->cap->assign_terms) ? 'disabled="disabled"' : '';

?>
<div id="taxonomy-<?php echo $taxonomy; ?>" class="tags-metabox">
<div class="hide-if-no-js">
<p><?php echo sprintf(__('Type a tag name and press %s tab to add it.','Shopp'),'<abbr title="'.__('tab key','Shopp').'">&#8677;</abbr>'); ?></p>
</div>
<div class="nojs-tags hide-if-js">
<p><?php echo $tax->labels->add_or_remove_items; ?></p>
<textarea name="<?php echo "tax_input[$taxonomy]"; ?>" rows="3" cols="20" class="tags" id="tax-input-<?php echo $taxonomy; ?>" <?php echo $disabled; ?>><?php echo esc_attr(get_terms_to_edit( $Product->id, $taxonomy )); ?></textarea></div>
</div>
<?php
}

// Load all Shopp product taxonomies
$Shopp = Shopp::object();
foreach ( get_object_taxonomies(ShoppProduct::$posttype) as $taxonomy_name ) {
	$taxonomy = get_taxonomy($taxonomy_name);
	$label = $taxonomy->labels->name;
	if ( is_taxonomy_hierarchical($taxonomy_name) )
		ShoppUI::addmetabox($taxonomy_name.'-box', $label.$Admin->boxhelp('product-editor-categories'), 'shopp_categories_meta_box', ShoppProduct::$posttype, 'side', 'core', array( 'taxonomy' => $taxonomy_name ));
	else ShoppUI::addmetabox($taxonomy_name.'-box', $label.$Admin->boxhelp('product-editor-tags'), 'shopp_tags_meta_box', ShoppProduct::$posttype, 'side', 'core', array( 'taxonomy' => $taxonomy_name ));

}

function settings_meta_box ($Product) {
	$Shopp = Shopp::object();
	$Admin =& $Shopp->Flow->Admin;

?>
	<p><input type="hidden" name="featured" value="off" /><input type="checkbox" name="featured" value="on" id="featured" tabindex="12" <?php if ($Product->featured == "on") echo ' checked="checked"'?> /><label for="featured"> <?php _e('Featured Product','Shopp'); ?></label></p>
	<p><input type="hidden" name="variants" value="off" /><input type="checkbox" name="variants" value="on" id="variations-setting" tabindex="13"<?php if ($Product->variants == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php _e('Variants','Shopp'); ?><?php echo $Admin->boxhelp('product-editor-variations'); ?></label></p>
	<p><input type="hidden" name="addons" value="off" /><input type="checkbox" name="addons" value="on" id="addons-setting" tabindex="13"<?php if ($Product->addons == "on") echo ' checked="checked"'?> /><label for="addons-setting"> <?php _e('Add-ons','Shopp'); ?><?php echo $Admin->boxhelp('product-editor-addons'); ?></label></p>

	<?php if (shopp_setting_enabled('tax_inclusive')): ?>
		<p><input type="hidden" name="meta[excludetax]" value="off" /><input type="checkbox" name="meta[excludetax]" value="on" id="excludetax-setting" tabindex="18"  <?php if(isset($Product->meta['excludetax']) && Shopp::str_true($Product->meta['excludetax']->value)) echo 'checked="checked"'; ?> /> <label for="excludetax-setting"><?php _e('Exclude Taxes','Shopp'); ?></label></p>
	<?php endif; ?>

	<?php if ($Shopp->Shipping->realtime): ?>
	<p><input type="hidden" name="meta[packaging]" value="off" /><input type="checkbox" name="meta[packaging]" value="on" id="packaging-setting" tabindex="18"  <?php if(isset($Product->meta['packaging']) && $Product->meta['packaging']->value == "on") echo 'checked="checked"'; ?> /> <label for="packaging-setting"><?php _e('Separate Packaging','Shopp'); ?></label></p>
	<?php endif; ?>


	<p><input type="hidden" name="comment_status" value="closed" /><input type="checkbox" name="comment_status" value="open" id="allow-comments" tabindex="18"  <?php if(Shopp::str_true($Product->comment_status)) echo 'checked="checked"'; ?> /> <label for="allow-comments"><?php _e('Comments','Shopp'); ?></label>

	<p><input type="hidden" name="ping_status" value="closed" /><input type="checkbox" name="ping_status" value="open" id="allow-trackpings" tabindex="18"  <?php if(Shopp::str_true($Product->ping_status)) echo 'checked="checked"'; ?> /> <label for="allow-trackpings"><?php _e('Trackbacks & Pingbacks','Shopp'); ?></label>

	<p><input type="hidden" name="meta[processing]" value="off" /><input type="checkbox" name="meta[processing]" value="on" id="process-time" tabindex="18"  <?php if(isset($Product->meta['processing']) && Shopp::str_true($Product->meta['processing']->value)) echo 'checked="checked"'; ?> /> <label for="process-time"><?php _e('Processing Time','Shopp'); ?></label>

	<div id="processing" class="hide-if-js">
		<select name="meta[minprocess]"><?php echo menuoptions(Lookup::timeframes_menu(),isset($Product->meta['minprocess'])?$Product->meta['minprocess']->value:false,true); ?></select> &mdash;
		<select name="meta[maxprocess]"><?php echo menuoptions(Lookup::timeframes_menu(),isset($Product->meta['maxprocess'])?$Product->meta['maxprocess']->value:false,true); ?></select>
	</div>

	</p>

<?php
}
ShoppUI::addmetabox(
	'product-settings',
	__('Settings','Shopp').$Admin->boxhelp('product-editor-settings'),
	'settings_meta_box',
	ShoppProduct::$posttype,
	'side',
	'core'
);

function summary_meta_box ($Product) {
?>
	<textarea name="summary" id="summary" rows="2" cols="50" tabindex="6"><?php echo $Product->summary ?></textarea><br />
    <label for="summary"><?php _e('A brief description of the product to draw the customer\'s attention.','Shopp'); ?></label>
<?php
}
ShoppUI::addmetabox(
	'product-summary',
	__('Summary','Shopp').$Admin->boxhelp('product-editor-summary'),
	'summary_meta_box',
	get_current_screen()->id,
	'normal',
	'core'
);

function details_meta_box ($Product) {
?>
	<ul class="details multipane">
		<li>
			<div id="details-menu" class="multiple-select menu">
			<input type="hidden" name="deletedSpecs" id="test" class="deletes" value="" />
			<ul></ul>
			</div>
		</li>
		<li><div id="details-list" class="list"><ul></ul></div></li>
	</ul><br class="clear" />
	<div id="new-detail">
	<button type="button" id="addDetail" class="button-secondary" tabindex="8"><small><?php _e('Add Product Detail','Shopp'); ?></small></button>
	<p><?php _e('Build a list of detailed information such as dimensions or features of the product.','Shopp'); ?></p>
	</div>
<?php
}
ShoppUI::addmetabox(
	'product-details-box',
	__('Details &amp; Specs','Shopp').$Admin->boxhelp('product-editor-details'),
	'details_meta_box',
	get_current_screen()->id,
	'normal',
	'core'
);

function images_meta_box ($Product) {
?>
	<div id="confirm-delete-images" class="notice hidden"><p><?php _e('Save the product to confirm deleted images.','Shopp'); ?></p></div>
	<ul id="lightbox">
	<?php foreach ( (array) $Product->images as $i => $Image ): ?>
		<li id="image-<?php echo (int)$Image->id; ?>"><input type="hidden" name="images[]" value="<?php echo $Image->id; ?>" />
			<div id="image-<?php echo (int)$Image->id; ?>-details" title="<?php _e('Double-click images to edit their details&hellip;','Shopp'); ?>">
				<img src="?siid=<?php echo (int)$Image->id; ?>&amp;<?php echo $Image->resizing(96,0,1); ?>" width="96" height="96" />
				<input type="hidden" name="imagedetails[<?php echo (int)$i; ?>][id]" value="<?php echo (int)$Image->id; ?>" />
				<input type="hidden" name="imagedetails[<?php echo (int)$i; ?>][title]" value="<?php echo $Image->title; ?>" class="imagetitle" />
				<input type="hidden" name="imagedetails[<?php echo (int)$i; ?>][alt]" value="<?php echo $Image->alt; ?>"  class="imagealt" />
				<?php
					if ( isset($Product->cropped) && count($Product->cropped) > 0 && isset($Product->cropped[ $Image->id ]) ):

						$cropped = is_array($Product->cropped[ $Image->id ]) ? $Product->cropped[ $Image->id ] : array($Product->cropped[$Image->id]);

						foreach ($cropped as $cache):
							$cropimage = unserialize($cache->value);
							$cropdefaults = array('dx' => '','dy' => '','cropscale' => '');
							$cropsettings = array_intersect_key($cropimage->settings, $cropdefaults);
							$cropping = ( array_filter($cropsettings) == array() ) ? '' : join(',', array_merge($cropdefaults, $cropsettings));
							$c = "$cropimage->width:$cropimage->height";
				?>
					<input type="hidden" name="imagedetails[<?php echo $i; ?>][cropping][<?php echo $cache->id; ?>]" alt="<?php echo $c; ?>" value="<?php echo $cropping; ?>" class="imagecropped" />
				<?php endforeach; endif; ?>
			</div>
			<?php echo ShoppUI::button('delete', 'deleteImage', array('type' => 'button', 'class' => 'delete', 'value' => $Image->id, 'title' => Shopp::__('Remove image&hellip;')) ); ?>
			</li>
	<?php endforeach; ?>
	</ul>
	<div class="clear"></div>
	<input type="hidden" name="product" value="<?php echo $_GET['id']; ?>" id="image-product-id" />
	<input type="hidden" name="deleteImages" id="deleteImages" value="" />
	<div id="swf-uploader-button"></div>
	<div id="browser-uploader">
		<button type="button" name="image_upload" id="image-upload" class="button-secondary"><small><?php _e('Add New Image','Shopp'); ?></small></button><br class="clear"/>
	</div>
<?php
}
ShoppUI::addmetabox(
	'product-images',
	 __('Product Images','Shopp').$Admin->boxhelp('product-editor-images'),
	'images_meta_box',
	get_current_screen()->id,
	'normal',
	'core'
);

function pricing_meta_box ($Product) {
?>
<div id="prices-loading"><span class="shoppui-spinner shoppui-spinfx shoppui-spinfx-steps8"></div>
<div id="product-pricing"></div>

<div id="variations">
	<div id="variations-menus" class="panel">
		<div class="pricing-label">
			<label><?php _e('Variation Option Menus','Shopp'); ?></label>
		</div>
		<div class="pricing-ui">
			<p><?php _e('Create the menus and menu options for the product\'s variations.','Shopp'); ?></p>
			<ul class="multipane options">
				<li><div id="variations-menu" class="multiple-select menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="addVariationMenu" class="button-secondary" tabindex="14"><small><?php _e('Add Menu','Shopp'); ?></small></button>
					</div>
				</li>

				<li>
					<div id="variations-list" class="multiple-select options"></div>
					<div class="controls right">
						<button type="button" id="linkOptionVariations" class="button-secondary" tabindex="17"><small><?php _e('Link All Variations','Shopp'); ?></small></button>
					<button type="button" id="addVariationOption" class="button-secondary" tabindex="15"><small><?php _e('Add Option','Shopp'); ?></small></button>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
<br />
<div id="variations-pricing"></div>
</div>

<div id="addons">
	<div id="addons-menus" class="panel">
		<div class="pricing-label">
			<label><?php _e('Add-on Option Menus','Shopp'); ?></label>
		</div>
		<div class="pricing-ui">
			<p><?php _e('Create the menus and menu options for the product\'s add-ons.','Shopp'); ?></p>
			<ul class="multipane options">
				<li><div id="addon-menu" class="multiple-select menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="newAddonGroup" class="button-secondary" tabindex="14"><small> <?php _e('New Add-on Group','Shopp'); ?></small></button>
					</div>
				</li>

				<li>
					<div id="addon-list" class="multiple-select options"></div>
					<div class="controls right">
					<button type="button" id="addAddonOption" class="button-secondary" tabindex="15"><small> <?php _e('Add Option','Shopp'); ?></small></button>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
<div id="addon-pricing"></div>
</div>

<div><input type="hidden" name="deletePrices" id="deletePrices" value="" />
	<input type="hidden" name="prices" value="" id="prices" /></div>

<div id="chooser">
	<p><label for="import-url"><?php _e('Attach file by URL','Shopp'); ?>&hellip;</label></p>
	<p><span class="fileimporter"><input type="text" name="url" id="import-url" class="fileimport" /><span class="shoppui-spin-align"><span class="status"></span></span></span><button class="button-secondary" id="attach-file"><small><?php _e('Attach File','Shopp'); ?></small></button><br /><span><label for="import-url">file:///path/to/file.zip<?php if (!in_array('http',stream_get_wrappers())): ?>, http://server.com/file.zip<?php endif; ?></label></span></p>
	<label class="alignleft"><?php _e('Select a file from your computer','Shopp'); ?>:</label>
	<div class=""><div id="flash-upload-file"></div><button id="ajax-upload-file" class="button-secondary"><small><?php _e('Upload File','Shopp'); ?></small></button></div>
</div>

<?php
}
ShoppUI::addmetabox(
	'product-pricing-box',
	__('Pricing','Shopp').$Admin->boxhelp('product-editor-pricing'),
	'pricing_meta_box',
	get_current_screen()->id,
	'advanced',
	'core'
);


/** Templates **/
function priceline_ui () {

}
add_action('shopp_product_editor_templates','priceline_ui');

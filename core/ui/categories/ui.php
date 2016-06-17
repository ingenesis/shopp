<?php
function save_meta_box ($Category) {
	$Shopp = Shopp::object();

	$workflows = array(
		"continue" => __('Continue Editing','Shopp'),
		"close" => __('Category Manager','Shopp'),
		"new" => __('New Category','Shopp'),
		"next" => __('Edit Next','Shopp'),
		"previous" => __('Edit Previous','Shopp')
		);

?>
	<div id="major-publishing-actions">
		<input type="hidden" name="id" value="<?php echo $Category->id; ?>" />
		<select name="settings[workflow]" id="workflow">
		<?php echo Shopp::menuoptions($workflows,shopp_setting('workflow'),true); ?>
		</select>
		<input type="submit" class="button-primary" name="save" value="<?php _e('Update','Shopp'); ?>" />
	</div>
<?php
}
ShoppUI::addmetabox('save-category', __('Save','Shopp').$Admin->boxhelp('category-editor-save'), 'save_meta_box', 'shopp_page_shopp-category', 'side', 'core');

function settings_meta_box ($Category) {
	$Shopp = Shopp::object();
	$tax = get_taxonomy($Category->taxonomy);
?>
	<p><?php wp_dropdown_categories( array( 'taxonomy' => $Category->taxonomy, 'selected' => $Category->parent, 'hide_empty' => 0, 'name' => 'parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => $tax->labels->parent_item.'&hellip;', 'tab_index' => 3 ) );?>
<label><span><?php _e('Categories, unlike tags, can be or have nested sub-categories.','Shopp'); ?></span></label></p>

	<p class="toggle"><input type="hidden" name="spectemplate" value="off" /><input type="checkbox" name="spectemplate" value="on" id="spectemplates-setting" tabindex="11" <?php if (isset($Category->spectemplate) && $Category->spectemplate == "on") echo ' checked="checked"'?> /><label for="spectemplates-setting"> <?php _e('Product Details Template','Shopp'); ?><br /><span><?php _e('Predefined details for products created in this category','Shopp'); ?></span></label></p>
	<p id="facetedmenus-setting" class="toggle"><input type="hidden" name="facetedmenus" value="off" /><input type="checkbox" name="facetedmenus" value="on" id="faceted-setting" tabindex="12" <?php if (isset($Category->facetedmenus) && $Category->facetedmenus == "on") echo ' checked="checked"'?> /><label for="faceted-setting"><?php _e('Faceted Menus','Shopp'); ?><br /><span><?php _e('Build drill-down filter menus based on the details template of this category','Shopp'); ?></span></label></p>
	<p class="toggle"><input type="hidden" name="variations" value="off" /><input type="checkbox" name="variations" value="on" id="variations-setting" tabindex="13"<?php if (isset($Category->variations) && $Category->variations == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php _e('Variations','Shopp'); ?><br /><span><?php _e('Predefined selectable product options for products created in this category','Shopp'); ?></span></label></p>
	<?php if (isset($Category->count) && $Category->count > 1): ?>
	<p class="toggle"><a href="<?php echo add_query_arg(array('page'=>'shopp-categories','id'=>$Category->id,'a'=>'products'),admin_url('admin.php')); ?>" class="button-secondary"><?php _e('Arrange Products','Shopp'); ?></a></p>
	<?php endif; ?>

	<?php
}
ShoppUI::addmetabox('category-settings', __('Settings','Shopp').$Admin->boxhelp('category-editor-settings'), 'settings_meta_box', 'shopp_page_shopp-category', 'side', 'core');

function images_meta_box ($Category) {
?>
	<ul id="lightbox">
		<?php if (isset($Category->images) && !empty($Category->images)): ?>
		<?php foreach ((array)$Category->images as $i => $Image): ?>
			<li id="image-<?php echo $Image->id; ?>"><input type="hidden" name="images[]" value="<?php echo $Image->id; ?>" />
			<div id="image-<?php echo $Image->id; ?>-details">
				<img src="?siid=<?php echo $Image->id; ?>&amp;<?php echo $Image->resizing(96,0,1); ?>" width="96" height="96" />
				<input type="hidden" name="imagedetails[<?php echo $i; ?>][id]" value="<?php echo $Image->id; ?>" />
				<input type="hidden" name="imagedetails[<?php echo $i; ?>][title]" value="<?php echo $Image->title; ?>" class="imagetitle" />
				<input type="hidden" name="imagedetails[<?php echo $i; ?>][alt]" value="<?php echo $Image->alt; ?>"  class="imagealt" />
				<?php
					if (count($Image->cropped) > 0):
						foreach ($Image->cropped as $cache):
							$cropping = join(',',array($cache->settings['dx'],$cache->settings['dy'],$cache->settings['cropscale']));
							$c = "$cache->width:$cache->height"; ?>
					<input type="hidden" name="imagedetails[<?php echo $i; ?>][cropping][<?php echo $cache->id; ?>]" alt="<?php echo $c; ?>" value="<?php echo $cropping; ?>" class="imagecropped" />
				<?php endforeach; endif;?>
			</div>
			<?php echo ShoppUI::button('delete', 'deleteImage', array('type' => 'button', 'class' => 'delete deleteButton', 'value' => $Image->id, 'title' => Shopp::__('Remove image&hellip;')) ); ?>
			</li>
		<?php endforeach; endif; ?>
	</ul>
	<div class="clear"></div>
	<input type="hidden" name="category" value="<?php echo $_GET['id']; ?>" id="image-category-id" />
	<input type="hidden" name="deleteImages" id="deleteImages" value="" />
	<div id="swf-uploader-button"></div>
	<div id="swf-uploader">
	<button type="button" class="button-secondary" name="add-image" id="add-image" tabindex="10"><small><?php _e('Add New Image','Shopp'); ?></small></button></div>
	<div id="browser-uploader">
		<button type="button" name="image_upload" id="image-upload" class="button-secondary"><small><?php _e('Add New Image','Shopp'); ?></small></button><br class="clear"/>
	</div>

	<p><?php _e('Double-click images to edit their details. Save the product to confirm deleted images.','Shopp'); ?></p>
<?php
}
ShoppUI::addmetabox('category-images', __('Category Images','Shopp').$Admin->boxhelp('category-editor-images'), 'images_meta_box', 'shopp_page_shopp-category', 'normal', 'core');

function templates_meta_box ($Category) {
	$pricerange_menu = array(
		"disabled" => __('Price ranges disabled','Shopp'),
		"auto" => __('Build price ranges automatically','Shopp'),
		"custom" => __('Use custom price ranges','Shopp'),
	);

?>
<p><?php _e('Setup template values that will be copied into new products that are created and assigned this category.','Shopp'); ?></p>
<div id="templates"></div>

<div id="details-template" class="panel">
	<div class="pricing-label">
		<label><?php _e('Product Details','Shopp'); ?></label>
	</div>
	<div class="pricing-ui">

	<ul class="details multipane">
		<li><input type="hidden" name="deletedSpecs" id="deletedSpecs" value="" />
			<div id="details-menu" class="multiple-select options">
				<ul></ul>
			</div>
			<div class="controls">
			<button type="button" id="addDetail" class="button-secondary"><small><?php _e('Add Detail','Shopp'); ?></small></button>
			</div>
		</li>
		<li id="details-facetedmenu">
			<div id="details-list" class="multiple-select options">
				<ul></ul>
			</div>
			<div class="controls">
			<button type="button" id="addDetailOption" class="button-secondary"><small><?php _e('Add Option','Shopp'); ?></small></button>
			</div>
		</li>
	</ul>

	</div>
	<div class="clear"></div>
</div>
<div class="clear"></div>

<div id="price-ranges" class="panel">
	<div class="pricing-label">
		<label><?php _e('Price Range Search','Shopp'); ?></label>
	</div>
	<div class="pricing-ui">
	<select name="pricerange" id="pricerange-facetedmenu">
		<?php echo menuoptions($pricerange_menu,$Category->pricerange,true); ?>
	</select>
	<ul class="details multipane">
		<li><div id="pricerange-menu" class="multiple-select options"><ul class=""></ul></div>
			<div class="controls">
			<button type="button" id="addPriceLevel" class="button-secondary"><small><?php _e('Add Price Range','Shopp'); ?></small></button>
			</div>
		</li>
	</ul>
	<div class="clear"></div>

	<p><?php _e('Configure how you want price range options in this category to appear.','Shopp'); ?></p>

</div>
<div class="clear"></div>
<div id="pricerange"></div>
</div>

<div id="variations-template">
	<div id="variations-menus" class="panel">
		<div class="pricing-label">
			<label><?php _e('Variation Option Menus','Shopp'); ?></label>
		</div>
		<div class="pricing-ui">
			<p><?php _e('Create a predefined set of variation options for products in this category.','Shopp'); ?></p>
			<ul class="multipane">
				<li><div id="variations-menu" class="multiple-select options menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="addVariationMenu" class="button-secondary"><?php _e('Add Option Menu','Shopp'); ?></button>
					</div>
				</li>

				<li>
					<div id="variations-list" class="multiple-select options"></div>
					<div class="controls">
					<button type="button" id="addVariationOption" class="button-secondary"><?php _e('Add Option','Shopp'); ?></button>
					</div>
				</li>
			</ul>
			<div class="clear"></div>
		</div>
	</div>
<br />
<div id="variations-pricing"></div>
</div>


<?php
}
ShoppUI::addmetabox('templates_menus', __('Product Templates &amp; Menus','Shopp').$Admin->boxhelp('category-editor-templates'), 'templates_meta_box', 'shopp_page_shopp-category', 'advanced', 'core');

?>
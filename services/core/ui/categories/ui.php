<?php
function save_meta_box ($Category) {
	$Shopp = Shopp::object();

	$workflows = array(
		"continue"	=> Shopp::__('Continue Editing'),
		"close"	    => Shopp::__('Category Manager'),
		"new"	    => Shopp::__('New Category'),
		"next"	    => Shopp::__('Edit Next'),
		"previous"	=> Shopp::__('Edit Previous')
		);

?>
	<div id="major-publishing-actions">
		<input type="hidden" name="id" value="<?php echo $Category->id; ?>" />
		<select name="settings[workflow]" id="workflow">
		<?php echo Shopp::menuoptions($workflows,shopp_setting('workflow'),true); ?>
		</select>
		<input type="submit" class="button-primary" name="save" value="<?php Shopp::_e('Update'); ?>" />
	</div>
<?php
}
ShoppUI::addmetabox('save-category', Shopp::__('Save').$Admin->boxhelp('category-editor-save'), 'save_meta_box', 'shopp_page_shopp-category', 'side', 'core');

function settings_meta_box ($Category) {
	$Shopp = Shopp::object();
	$tax = get_taxonomy($Category->taxonomy);
?>
	<p><?php wp_dropdown_categories( array( 'taxonomy' => $Category->taxonomy, 'selected' => $Category->parent, 'hide_empty' => 0, 'name' => 'parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => $tax->labels->parent_item.'&hellip;', 'tab_index' => 3 ) );?>
<label><span><?php Shopp::_e('Categories, unlike tags, can be or have nested sub-categories.'); ?></span></label></p>

	<p class="toggle"><input type="hidden" name="spectemplate" value="off" /><input type="checkbox" name="spectemplate" value="on" id="spectemplates-setting" tabindex="11" <?php if (isset($Category->spectemplate) && $Category->spectemplate == "on") echo ' checked="checked"'?> /><label for="spectemplates-setting"> <?php Shopp::_e('Product Details Template'); ?><br /><span><?php Shopp::_e('Predefined details for products created in this category'); ?></span></label></p>
	<p id="facetedmenus-setting" class="toggle"><input type="hidden" name="facetedmenus" value="off" /><input type="checkbox" name="facetedmenus" value="on" id="faceted-setting" tabindex="12" <?php if (isset($Category->facetedmenus) && $Category->facetedmenus == "on") echo ' checked="checked"'?> /><label for="faceted-setting"><?php Shopp::_e('Faceted Menus'); ?><br /><span><?php Shopp::_e('Build drill-down filter menus based on the details template of this category'); ?></span></label></p>
	<p class="toggle"><input type="hidden" name="variations" value="off" /><input type="checkbox" name="variations" value="on" id="variations-setting" tabindex="13"<?php if (isset($Category->variations) && $Category->variations == "on") echo ' checked="checked"'?> /><label for="variations-setting"> <?php Shopp::_e('Variations'); ?><br /><span><?php Shopp::_e('Predefined selectable product options for products created in this category'); ?></span></label></p>
	<?php if (isset($Category->count) && $Category->count > 1): ?>
	<p class="toggle"><a href="<?php echo add_query_arg(array('page'=>'shopp-categories','id'=>$Category->id,'a'=>'products'),admin_url('admin.php')); ?>" class="button-secondary"><?php Shopp::_e('Arrange Products'); ?></a></p>
	<?php endif; ?>

	<?php
}
ShoppUI::addmetabox('category-settings', Shopp::__('Settings') . $Admin->boxhelp('category-editor-settings'), 'settings_meta_box', 'shopp_page_shopp-category', 'side', 'core');

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
	<button type="button" class="button-secondary" name="add-image" id="add-image" tabindex="10"><small><?php Shopp::_e('Add New Image'); ?></small></button></div>
	<div id="browser-uploader">
		<button type="button" name="image_upload" id="image-upload" class="button-secondary"><small><?php Shopp::_e('Add New Image'); ?></small></button><br class="clear"/>
	</div>

	<p><?php Shopp::_e('Double-click images to edit their details. Save the product to confirm deleted images.'); ?></p>
<?php
}
ShoppUI::addmetabox('category-images', Shopp::__('Category Images') . $Admin->boxhelp('category-editor-images'), 'images_meta_box', 'shopp_page_shopp-category', 'normal', 'core');

function templates_meta_box ($Category) {
	$pricerange_menu = array(
		"disabled"	=> Shopp::__('Price ranges disabled'),
		"auto"	    => Shopp::__('Build price ranges automatically'),
		"custom"	=> Shopp::__('Use custom price ranges'),
	);

?>
<p><?php Shopp::_e('Setup template values that will be copied into new products that are created and assigned this category.'); ?></p>
<div id="templates"></div>

<div id="details-template" class="panel">
	<div class="pricing-label">
		<label><?php Shopp::_e('Product Details'); ?></label>
	</div>
	<div class="pricing-ui">

	<ul class="details multipane">
		<li><input type="hidden" name="deletedSpecs" id="deletedSpecs" value="" />
			<div id="details-menu" class="multiple-select options">
				<ul></ul>
			</div>
			<div class="controls">
			<button type="button" id="addDetail" class="button-secondary"><small><?php Shopp::_e('Add Detail'); ?></small></button>
			</div>
		</li>
		<li id="details-facetedmenu">
			<div id="details-list" class="multiple-select options">
				<ul></ul>
			</div>
			<div class="controls">
			<button type="button" id="addDetailOption" class="button-secondary"><small><?php Shopp::_e('Add Option'); ?></small></button>
			</div>
		</li>
	</ul>

	</div>
	<div class="clear"></div>
</div>
<div class="clear"></div>

<div id="price-ranges" class="panel">
	<div class="pricing-label">
		<label><?php Shopp::_e('Price Range Search'); ?></label>
	</div>
	<div class="pricing-ui">
	<select name="pricerange" id="pricerange-facetedmenu">
		<?php echo menuoptions($pricerange_menu, $Category->pricerange, true); ?>
	</select>
	<ul class="details multipane">
		<li><div id="pricerange-menu" class="multiple-select options"><ul class=""></ul></div>
			<div class="controls">
			<button type="button" id="addPriceLevel" class="button-secondary"><small><?php Shopp::_e('Add Price Range'); ?></small></button>
			</div>
		</li>
	</ul>
	<div class="clear"></div>

	<p><?php Shopp::_e('Configure how you want price range options in this category to appear.'); ?></p>

</div>
<div class="clear"></div>
<div id="pricerange"></div>
</div>

<div id="variations-template">
	<div id="variations-menus" class="panel">
		<div class="pricing-label">
			<label><?php Shopp::_e('Variation Option Menus'); ?></label>
		</div>
		<div class="pricing-ui">
			<p><?php Shopp::_e('Create a predefined set of variation options for products in this category.'); ?></p>
			<ul class="multipane">
				<li><div id="variations-menu" class="multiple-select options menu"><ul></ul></div>
					<div class="controls">
						<button type="button" id="addVariationMenu" class="button-secondary"><?php Shopp::_e('Add Option Menu'); ?></button>
					</div>
				</li>

				<li>
					<div id="variations-list" class="multiple-select options"></div>
					<div class="controls">
					<button type="button" id="addVariationOption" class="button-secondary"><?php Shopp::_e('Add Option'); ?></button>
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
ShoppUI::addmetabox('templates_menus', Shopp::__('Product Templates &amp; Menus') . $Admin->boxhelp('category-editor-templates'), 'templates_meta_box', 'shopp_page_shopp-category', 'advanced', 'core');

?>
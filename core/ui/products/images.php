	<script id="lightbox-image-template" type="text/x-jquery-tmpl">
		<div>
		<?php ob_start(); ?>
		<li class="dz-preview dz-file-preview">
			<div class="dz-details" title="<?php Shopp::_e('Double-click images to edit their details&hellip;'); ?>">
				<img data-dz-thumbnail width="120" height="120" class="dz-image" />
			</div>
			<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
			<div class="dz-error-mark"><span>&times;</span></div>
			<div class="dz-error-message"><span data-dz-errormessage></span></div>
			<?php echo ShoppUI::button('delete', 'deleteImage', array('type' => 'button', 'class' => 'delete', 'value' => '${imageid}', 'title' => Shopp::__('Remove image&hellip;'), 'data-dz-remove' => true) ); ?>
			
			<input type="hidden" name="imagedetails[${index}][id]" value="${imageid}" />
			<input type="hidden" name="imagedetails[${index}][title]" value="${title}" class="imagetitle" />
			<input type="hidden" name="imagedetails[${index}][alt]" value="${alt}"  class="imagealt" />			
		</li>
		<?php $preview = ob_get_clean(); echo $preview; ?>
		</div>
	</script>

	<div id="confirm-delete-images" class="notice hidden"><p><?php _e('Save the product to confirm deleted images.','Shopp'); ?></p></div>
	<ul class="lightbox-dropzone">
	<?php foreach ( (array) $Product->images as $i => $Image ) {
			echo ShoppUI::template($preview, array(
				'${index}' => $i,
				'${imageid}' => $Image->id,
				'${title}' => $Image->title,
				'${alt}' => $Image->alt,
				'data-dz-thumbnail' => sprintf('src="?siid=%d&amp;%s"', $Image->id, $Image->resizing(120, 0, 1)),
			));
	} ?>
	</ul>
	<div class="clear"></div>

	<input type="hidden" name="product" value="<?php echo $_GET['id']; ?>" id="image-product-id" />
	<input type="hidden" name="deleteImages" id="deleteImages" value="" />

	<button type="button" name="image_upload" class="button-secondary image-upload"><small><?php Shopp::_e('Add New Image'); ?></small></button>
	
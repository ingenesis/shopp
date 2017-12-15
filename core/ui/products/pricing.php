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

<div id="filechooser">
	<p><label for="import-url"><?php Shopp::_e('Attach file by URL'); ?>&hellip;</label></p>
	<p><span class="fileimporter"><input type="text" name="url" id="import-url" class="fileimport" /><span class="shoppui-spin-align"><span class="status"></span></span></span><button class="button-secondary" id="attach-file"><small><?php Shopp::_e('Attach File'); ?></small></button><br /><span><label for="import-url">file:///path/to/file.zip<?php if (!in_array('http',stream_get_wrappers())): ?>, http://server.com/file.zip<?php endif; ?></label></span></p>
	<div><button id="filechooser-upload-file" class="button-secondary filechooser-upload"><small><?php Shopp::_e('Upload a file from your device'); ?></small></button></div>
</div>

<script id="filechooser-upload-template" type="text/x-jquery-tmpl">
<div>
    <div class="dz-preview dz-file-preview">
    	<div class="icon shoppui-file"></div>
        <span class="name dz-filename" data-dz-name></span>
        <small class="size dz-size" data-dz-size></small>
    	<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
    	<div class="dz-error-message"><span data-dz-errormessage></span></div>
    </div>
</div>
</script>

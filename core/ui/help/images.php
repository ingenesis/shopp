<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Image Settings

Image dimensions for each Shopp image used in the storefront website are defined in the layout instructions found in the Shopp template files. The templates can have hard coded image size instructions, or use a named image setting. When a template uses a named setting, you can adjust the settings using the Image settings screen found in the WordPress admin under the Shopp menus: **Shopp** &rarr; **Setup** &rarr; **Images**.

Creating new image settings will require technical implementation in the Shopp template files. If you’re not familiar with the process, it is usually best left to a developer.If your templates were created for you by a developer, you will mostly edit existing settings.

#### To edit an image setting:

* Click the name of the image setting or the **Edit** link below the name.

You can adjust the following image properties using Shopp’s image settings manager: dimensions (width and height), fit, quality, and sharpness.',

'Setup Images help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'dimensions',
	'title'   => Shopp::__('Dimensions'),
	'content' => Shopp::_mx(

'### Dimensions

For dimensions larger than the original image, Shopp will place the full-size image into the new image dimensions without resizing. Shopp cannot upscale images to larger sizes.

The dimensions specify the width and height of the image in pixels. The resized image will always fit within these dimensions though, depending on the Fit setting you select, the final dimensions may be smaller. If the dimensions of the image size are larger than the original full-size image uploaded, the full-size image will be placed directly into the image without resizing it.
',

'Setup Images help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'fit',
	'title'   => Shopp::__('Fit'),
	'content' => Shopp::_mx(

'### Fit

The Shopp Image Server can resize images using a number of different fit techniques. Each fit setting has slightly different results so you may need to experiment to get just the right look.

* **all**
Scale the image down to fit all dimensions within the new size (the final size may differ from the specified width and height settings).
* **fill**
Scale the image down to fit within the new size filling extra space to match the exact width and height settings with a background color.
* **crop**
Scale the image down to fit the width and height settings by using the smallest dimension to fill the entire image, cropping the extra off the other dimension. Specific cropping adjustments can be made to the image from the image detail editor in the product editor.
* **width**
Scale the image down to fit the image in the new size by the width, cropping off any extra height.
* **height**
Scale the image down to fit the image in the new size by the height, cropping any extra width off.

<table>
<thead><tr><th>Setting Name</th><th>Value</th></tr></thead>
<tbody>
	<tr><td>Highest quality, largest file size</td><td>100%</td></tr>
	<tr><td>Higher quality, larger file size</td><td>92%</td></tr>
	<tr><td>Balanced quality &amp; file size</td><td>80%</td></tr>
	<tr><td>Lower quality, smaller file size</td><td>70%</td></tr>
	<tr><td>Lowest quality, smallest file size</td><td>60%</td></tr>
</tbody></table>',

'Setup Images help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'quality',
	'title'   => Shopp::__('Quality'),
	'content' => Shopp::_mx(

'### Quality

Shopp resized images are generated as JPG or PNG images. When generating JPG format images, the quality setting will set the JPG quality. Shopp settings are skewed toward higher quality settings to preserve the quality presentation of the image at the most balanced representation. The available quality settings map to the quality percentage values shown in the table above.
',

'Setup Images help tab')
) );


get_current_screen()->add_help_tab( array(
	'id'      => 'sharpness',
	'title'   => Shopp::__('Sharpness'),
	'content' => Shopp::_mx(

'### Sharpness

The sharpness value applies a professional-grade unsharp mask filter to increase the sharpness of images as they are reduced. Typically when reducing the size of an image, the details of the image blur together as the pixels combine at smaller scale. Using the unsharp mask is a proven way to restore crisp details in an image. You can set the sharpness of the image at a range of anywhere between 100%-500%. Anything setting less than 100% is ignored.',

'Setup Images help tab')
) );

get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Setup Images help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS,
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));
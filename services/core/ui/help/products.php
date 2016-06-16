<?php
$Manager = Shopp::_mx(
'Building your catalog begins with the product manager. From here you can add new products, edit or delete existing products and even manage product inventory.

The product manager has several views for finding products in your catalog.

* **All** shows all products in the catalog.
* **Published** shows products that are publicly accessible on the storefront.
* **Drafts** shows products that are not yet published to the storefront.
* **On Sale** shows products that have an active sale price or promotional discount that applies.
* **Features** displays products that are marked to be featured.
* **Bestselling** shows all products ordered by products that sell most often to products that sell least often over the entire lifetime of the store. It also displays product sales information including total quantity sold and gross sales amounts.
* **Inventory** is a view that is only available when the **Inventory Tracking** setting is enabled under the **Shopp** &rarr; **System** &rarr; **Shipping** &rarr; **Settings** screen. It shows the current stock levels for all products and product variants in the catalog. The inventory screen provides quick and easy inventory management allowing you to change the stock levels of products.
* **Trash** shows products that have been moved to the trash to be deleted. You can move products back out of the trash or delete them permanently.

',

'Products help tab');

$NewProduct = Shopp::_mx(
'### Add a New Product

From the product manager, click the **Add New** button next to the **Products** title at the top of the page.

### Admin Bar

Alternatively, you can use the shortcut in the WordPress Admin Bar at the very top of the screen available on any screen in the WordPress Admin. From the Admin Bar select **New** &rarr; **Product**.

Either of these actions will bring you to a blank **Product Editor** where you can begin building your product.',

'Products help tab');

$Editor = Shopp::_mx(
'The product editor in Shopp is a collection of tools for building and managing a products in your catalog. Learning to use it effectively is key to efficiently manage your online storefront.

The product editor includes the following editing tools most of which are organized into editor panels.

* Name
* Description
* Save
* Catalog Categories
* Catalog Tags
* Settings
* Summary
* Details & Specs
* Images
* Pricing

### Customizing the Editor

Similar to the WordPress post or pages editor, the panels in the Shopp product editor can be collapsed by clicking the down arrows ▼ at the top-right of each panel (or anywhere on the panel title bar. Panels can also be reorganized using drag-and-drop to move them around. Click-and-drag on a panel&apos;s title bar to move the panel to a new location in the editor. Panel positions and collapsed panels are saved and will stay that way for your login only. Other authorized users will be able to customize the appearance of the product editor for themselves.',

'Products help tab');

$Saving = Shopp::_mx(
'The save panel includes saving and publishing controls. It is usually located in the upper-right of the product editor screen.

### Publishing

Click the checkbox or the word **Published** to publish the product to your catalog. The product must be saved with the Published setting on before it will appear in your catalog. Products that are not published will be hidden and completely inaccessible from the storefront catalog.

### Scheduling

Similar to the posts and pages editors in WordPress, you can schedule the product to publish at a specific date and time. Click the **Schedule** button to reveal the scheduling settings. Click on any of the date fields to select a date from the pop-up calendar that appears. You cannot schedule products to publish in the past through the pop-up calendar, but you can force past dates by typing in a specific date in the fields. Use the tab ⇥ button to quickly jump through each of the scheduling fields.

Click the **Save Product** button to save the product.

### Workflows

Before saving, use the workflow menu to set your next work action. Setting a workflow action will automatically redirect you where you want to go after saving the product. Once set, the setting stays that way until changed again. The available actions are:

**Continue Editing**
Reloads the current product so that you can continue making changes.

**Products Manager**
Directs you back to the Products Manager to select another product to work on.

**New Product**
Loads a new, blank product editor for quickly adding a lot of new products, one after another.

**Edit Next**
Edits the next product in the current Products Manager list. You can use searching and filters to get a specific list of products, then work through each product in the list using this workflow setting.

**Edit Previous**
Edits the previous product in the current Products Manager list.
',
'Products help tab');


$Specs = Shopp::_mx(
'The Details & Specs panel is used to track individual details (or specs) of a product. For example, if you are selling a bookshelf, you might track the dimensions of height, width, depth and perhaps the number of shelves. Likewise, for a DVD movie, you might track the running time, director, actors, studio, etc.

### To add a new details:

1. Click the Add Product Detail button
2. A new entry appears in the menu on the right with a generic name
3. The new entry is automatically highlighted; just start typing to change the name
4. Clicking the name of the entry or the arrow to the right of it will select the detail. A new content area will be displayed to the right of the menu list
5. Type the information associated with the detail in the text area to the right of the menu of details

### To assign the order of details:

1. Move your mouse over the name of a detail to show the move ￼ icon for that detail
2. Click-and-hold the move icon to drag the detail up and down in the list

### Google Merchant Center Specs
For merchants that have signed up to use Google Merchant Center (also known as Google Base), you can use the product details and specs to set up Google Merchant Center feed details. Shopp will recognize specially named product specs and use those details to include the information necessary for Google Merchant Center feeds.

See the [Google Merchant Center Products Feed Specification](http://support.google.com/merchants/bin/answer.py?hl=en&answer=188494#US) documentation for more information on each value, when it is used, and how the value should be formatted. Understanding how these values are used by the Google Merchant Center, and applying them properly to the details of your product will make your feed to the Google Merchant Center more effective in marketing your products.

Shopp will recognize the following specs to the appropriate Google Merchant Center values:

* **Brand:** when found, will be used for the product brand in the Google product stream. Used in conjunction with the below GTIN and MPN details.
* **UPC**, **EAN**, **JAN**, **ISBN-13**, **ISBN-10**, and **ISBN**: when any of these detail labels are found, will be used for the Global Trade Item Number (GTIN) in the Google product stream. Used in conjunction with the Brand and MPN details.
* **MPN**: when found, will be used for the Manufacturer Part Number (MPN) in the Google product stream. Used in conjunction with the above Brand and GTIN details.
* **Color**: when found, will be used for the color of the US apparel item in the Google product stream.
* **Material**: when found, will be used for the material of the US apparel item (such as Leather, Denim, Suede, etc.) in the Google product stream.
* **Pattern**: when found, will be used for the pattern or graphic of a US apparel item (such as Tiger, Polka Dot, Striped, Paisley, etc.) in the Google product stream.
* **Size**: when found, will be used for the size of a US apparel or shoe item (such as S, M, L, or 9, 10, etc.) in the Google product stream.
* **Gender**: when found, will be used for the gender value (Male, Female, and Unisex) for US apparel items.
* **Age Group**: when found, will be used for the age group value (Adult or Kids) for US apparel items.
* **Google Product Category**: when found will be used for the full Google product taxonomy for Apparel & Accessories, Media, and Software items in the US, UK, Germany, France, or Japan. See the Google product taxonomy page for more information of the full taxonomy names.

',
'Products help tab');

$Categories = Shopp::_mx(
'Products are shown in the catalog collected into groups known as categories. They are the primary means of organizing your catalog. There are two types of categories: [custom categories](%s) that you create, and [smart collections](%s) that are preprogrammed to collect products automatically.

Custom categories that you create are hierarchical. This means that each category can have one or more subcategories, or children. Products can be assigned to one or more categories at the same time.

In the Product Editor, use the Category panel to assign the current product to custom categories you’ve created. By default, it is located directly below the Save panel.

Click the checkbox or the category name to assign the current product to one or more of the custom categories you’ve created in your catalog.

You can add categories that don&apos;t currently exist right from the product editor.

#### To add a category in the product editor:

1. Click the **New Category** button.
2. Enter a name for the new category in the text field that appears.
3. Select a parent category (if needed) to make the new category a child.
4. Click the **Add** button to create the category.
5. The newly created category appears in the category list above and is automatically selected.

From the Category Editor, categories can be set up with predefined templates for product details and variation options, which can be used in the Product Editor to assist in speeding up data entry. Assigning a product to a category that has template presets will automatically copy those templates into the product (if they do not already exist). This will cut down on some of the effort needed to create many products that share a common set of details and variation options.
',
'Products help tab',

ShoppSupport::DOCS . 'the-catalog/custom-categories/',
ShoppSupport::DOCS . 'the-catalog/smart-collections/'

);

$Tagging = Shopp::_mx(
'Tags are a keyword or term that helps describe the product so that it can be found by browsing or searching. Unlike categories, tags have no hierarchy and allow for free-form organization.
Tags are used in the <meta> tag of the product page, so they have a marketing purpose behind them as well. The most popular tags are collected across your entire catalog to build the Tag Cloud widget.

￼Tags can also be used in your custom WordPress menus, and can be viewed as product collections on the storefront.

Enter tags into the entry field. You can enter multiple tags at one time by separating each tag with a comma (,), then click the **Add** button.',
'Products help tab');

$Settings = Shopp::_mx('
The Settings panel in the product editor includes several options for the product:

### Featured Product

Sets the product to appear in the Feature Products collection.

### Variants

Turns on the product variant options features for the product, which allow you to set up variant product option menus, and manage each product variant.

### Add-ons

Turns on the product add-on options features for the product, which allow you to set add-on option menus, and manage each product add-on.

### Separate Packaging

When using a third-party online shipping estimate service, this setting ensures the shipping estimate for the current product will always be quoted as a separate package.
',
'Products help tab');

$sidebar = Shopp::_mx(

'**For more information:**

[Variants](%s)

[Downloads & Images](%s)

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)
',

// Translator context
'Products help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'the-catalog/variants/',
ShoppSupport::DOCS . 'downloads-images/',
ShoppSupport::DOCS . 'the-catalog/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

);

get_current_screen()->add_help_tab( array(
	'id'      => 'manager',
	'title'   => Shopp::__('Product Manager'),
	'content' => $Manager
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'new-product',
	'title'   => Shopp::__('Adding Products'),
	'content' =>  $NewProduct
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'editor',
	'title'   => Shopp::__('Editing Products'),
	'content' => $Editor
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'saving',
	'title'   => Shopp::__('Saving & Workflows'),
	'content' => $Saving
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'specs',
	'title'   => Shopp::__('Details & Specs'),
	'content' => $Specs
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'categories',
	'title'   => Shopp::__('Catalog Categories'),
	'content' => $Categories
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'tagging',
	'title'   => Shopp::__('Tagging'),
	'content' => $Tagging
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'settings',
	'title'   => Shopp::__('Settings'),
	'content' => $Settings
) );

get_current_screen()->set_help_sidebar($sidebar);
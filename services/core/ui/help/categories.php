<?php
$Overview = Shopp::_mx(
'Similar to WordPress posts, your Shopp product catalog can be organized into your own set of custom categories. Because these categories are defined by you, you can choose which products belong to each category.

Beyond organizing products, Shopp adds a few extra tools to make custom categories even more useful:

* You can set the order products are arranged.
* Add preset product details and specs for common characteristics to save time and work effort.
* Create preset product variants to speed up entering new products.
* Set up navigational facets to build advanced category filters that aim your customers directly to the perfect product.
',
'Categories help tab');


$Managing = Shopp::_mx(
'### Category Manager

The **Category Manager** is used to create new custom categories, as well as edit and delete existing categories. To view the **Category Manager**, log in to the WordPress Admin, and navigate to **Catalog** &rarr; **Categories**.

Custom product categories are **hierarchical**, meaning each category can have a parent category, as well as child categories. The Category Manager displays your categories by relatives to make them easy to find. You can also search categories by name.

The Category Manager also shows you at a glance how many products are in each category, which categories use templates, and which categories are have faceted navigation menus setup.

To create a new category, click the **New Category** button. To edit a category, click the name of an existing category, to enter the **Category Editor**.
',
'Categories help tab');

$Editor = Shopp::_mx(
'### Category Editor

The **Category Editor** in Shopp is a collection of tools for building and managing custom categories in your catalog. Using the category editor efficiently can make building your storefront easier and more consistent.

The category editor includes the following editing tools organized into editor panels:￼

* Name field for defining the category name.
* A description editor tool, similar to the editor used for product and post descriptions, which includes common word processing features.
* A **Category Images** tool panel, for uploading and managing images that represent your category.
* A **Product Templates &amp; Menus** panel, used to manage product detail templates, faceted menu controls, and product option templates.
* A **Save** panel, similar to that of the product editor, with built-in workflow controls to make it easier to quickly create a lot of new categories.
* A **Settings** panel, which is used to set the parent category, and also to enable **Product Detail Templates**, **Variation Templates**, and **Faceted Menu** controls in the category editor.  When editing a category that already contains products, you can access the product arrangement tool here.

#### Customizing the Editor

Similar to the product editor, the panels in the Shopp category editor can be collapsed by clicking the down arrows ▼ at the top-right of each panel (or anywhere on the panel title bar. Panels can also be reorganized using drag-and-drop to move them around. Click-and-drag on a panel’s title bar to move the panel to a new location in the editor. Your panel arrangement will only be saved for your user login.

',
'Categories help tab');


$Templates = Shopp::_mx(
'### Product Details Template

Products assigned to the same category will often share a number of identical or similar details and specifications. To make the set up of each of those details in new products faster you can use the **Product Details Template** tool in the category editor.

To enable the **Product Details Template** tool, check the box labeled **Product Details Template** in the Settings panel of the category editor.

Once the **Product Details Template** setting is checked, the **Product Details** tool will automatically appear in the **Product Templates &amp; Menus** panel. This tool is used to create a common set of product details that can be applied to products  when they are assigned to the category.￼

These details are used as a starting point in the **Specs &amp; Details** tool that appears in the **Product Editor**. To add a new detail template, click the **Add Detail** button at the bottom of the **Product Details** tool, and label the new detail. For example, if you were creating a new category called Movies, you might want to automatically prompt for the movie’s director, screen writer, producer, and rating. To do so, simply click the **Add Detail** button for each of these details, and label them Director, Screen Writer, Producer, etc.

When saved to the category, these details will automatically be copied to a product when the product is assigned to this category in the **Product Editor**.

Product Detail templates are also used as the foundation for navigational facets that Shopp can use to build drill-down down product filtering menus for the category on the storefront. Note that when the **Product Details Template** setting is enabled from the Settings panel, the ￼Faceted Menu setting is then made available. See below for more details on how Faceted Menus are managed in your category.
',
'Categories help tab');

$VariantTemplates = Shopp::_mx(
'### Variant Template

When creating new products in a category, often the entire set of products share the same set of variation options.  The Category Editor provides a template feature for creating a set of selectable options to new products.

#### To enable Variant Templates:￼

1. Login to the WordPress Admin and navigate to the Category Manager.
2. Select a category link to open the Category Editor, or click the **Add Category** button.
3. Check the box labeled **Variations**, from the Setting panel of the Category Editor.

After clicking the **Variations**￼ setting box in the Category Editor, a **Variation Option Menus** sub-panel will appear in the Product Templates & Menus panel.

In the Category Editor, this **Variation Option Menus** feature works exactly like the identical menu that appears in **Pricing** panel of the **Product Editor**.

#### To add a product variation menu template:

1. In the **Product Templates &amp; Menus** panel, at the bottom of the **Variation Option Menus** tool, click the **Add Option Menu** button.
2. Click on the new option menu template,￼ now appearing in the left list box, and relabel it appropriately (for the characteristic the options describes, such as Size, Color, etc.)

#### To add options for a menu template:

1. Click once on the desired option menu entry, located in the left list box of the **Variation Options Menus** ￼tool. The corresponding option templates will appear in the right list box.
2. Click the **Add Option** button, located under the right list box, to add a new menu option to the currently selected option menu.
3. Rename the menu option templates appropriately for your new options, as you would like it to appear for customers in the product option menu.

Now that you have created a set of product variant templates, a price line editor sub-panels will appears for each combination of options. These editors work much in the same way the price line editors work in the Product Editor.
',
'Categories help tab');

$Facets = Shopp::_mx(
'### Faceted Menus

Many shopping sites provide a lot of features to make finding the right product easier for customers. By creating product categories, you can make it easier for a customer to find the right group of products, but often the category has so many products that finding the product that is the right fit can still be difficult.

Faceted Menus are one powerful way of limiting the products that are displayed to your customer to only those that fit the customer’s exacting specifications. For instance, if a customer is shopping for movies for their children, it would be convenient if the movies in the store could be filtered to those rated G or PG.

While this can be accomplished by simply adding additional categories, too many categories for niche characteristics can make things difficult to manage for the store owner, and often cause frustrating empty categories. Faceted menus help here, by letting the merchant specify these niche characteristics in the details template. Shopp automatically displays menus for these details.

> Product Details Templates must be enabled before the Faceted Menus setting will be available.

#### To enable Faceted Menus in the category editor:

1. Login to the WordPress Admin, and navigate to **Shopp** &rarr; **Catalog** &rarr; **Categories** to get to the Shopp Category Manager.
2. Open the Category Editor for a product category.
3. In the **Settings** panel, check that **Product Details Templates** are enabled as they are required to set up **Faceted Menus**.
4. Click the box labeled **Faceted Menus** in the Settings panel.

After enabling **Faceted Menus**, additional tools for the faceted menus will automatically appear in the **Product Templates &amp; Menus** panel.

* In the **Product Details** tool, a new faceted menu control listbox will appear to the right of the category product details labels. Each product detail that is specified in the product detail template can be used when building a drill-down menu. Shopp automatically creates a set of drill-down menu options for the category when more than one new product is created with details template.
* A **Price Range Search** tool is added below the Product Details tool. This faceted menu tool in the category editor will allow you to add a price range menu to the category’s faceted navigation menus.

### Displaying Faceted Menus

To display a faceted menu on your storefront for a category, you must include a faceted menu in your theme.  A faceted menu can either be added to a widget area of your theme, by adding the **Shopp Faceted Menu** widget, or can be added by your theme designer, using the Shopp Theme API.

See the [Widgets](%s) section of this document, or have your web developer consult the [Theme API reference](%s).
',
'Categories help tab',
ShoppSupport::DOCS . 'the-catalog/widgets/',
ShoppSupport::API . 'category/theme/'
);

$DetailFacets = Shopp::_mx(
'### Product Details Facet

In the Product Details tool of the **Product Templates &amp; Menus** panel, each product detail can used to build a faceted menu. By default, the faceted menus for each product detail is disabled.

#### To create a faceted menu from a product detail:

1. Click the label of the product detail you wish to use for your facet menu.  The labels appear in the list box on the left of the **Product Details** tool.
2. Select one of the faceted menu options from the list box on the right of the product detail you have selected.

There are three options for creating a faceted menu from a product detail:

#### Build faceted menu automatically

Select **Build faceted menu automatically** to have Shopp create new facet menu options from whatever value is used for the product detail when the detail is filled out in the Product Editor. Use this option when you are unsure of what typed of options are likely to be appear in the product detail. For instance, a movie Director detail.

#### Build as custom number ranges￼

Select **Build as custom number ranges** to set up specific numeric options. When selecting this setting, an Add Option button will appear under the list box.

Click the **Add Option** button to add several numeric options.￼ These options will be used to create a menu for this product detail when a new product is assigned to this category in the Product Editor.

#### Build from preset options

Select **Build from preset options** to specify a number of possible string options for the product detail. Like custom number ranges, when ￼this option is selected, an **Add Option** button will appear under the faceted option list box.

Click the **Add Option** button to add several strings options for this product detail. Again, as with numeric ranges, these options will be used to create a menu for this product detail when a new product is assigned to this category in the Product Editor.
',
'Categories help tab');

$PriceFacet = Shopp::_mx(
'### Faceted Menu Price Ranges

When faceted menus are enabled for a category, under the **Product Details** tool in the **Product Templates &amp; Menus** panel, a **Price Range Search** tool will appear. This feature is used to create product price-based navigation menus for the category.
There are two types of price range faceted menu settings that can be selected:

#### Build price ranges automatically

Select **Build price ranges automatically** to have Shopp create price range filters in the faceted menu for this category. This is the recommended price range setting, as Shopp does a great job of creating good price ranges based on products added to the category.

#### Use custom price ranges

If you would like exact control to set the price ranges that will be used for your faceted menu, select the **Use custom price ranges** option.

 When selecting this option, a list box will be added to the￼ Price Range Search tool, and an Add Price Range button will appear.

Click the **Add Price Range** button for each price range you would like to have appear in the facet menu for this category. Be aware that the price range will only appear in the facet menu if there are products that are priced in that range.
',
'Categories help tab');

$Arrange = Shopp::_mx(
'###  Arrange Products

Shopp allows you to specify a custom order for products appearing in your custom categories. You can set categories to show products in your custom arrangement by choosing the **Custom** option of the **Product Order** setting under **Shopp** &rarr; **Setup** &rarr; **Presentation** settings.

After you have added a number of products to your custom product category, you can use the **Category Editor** to arrange the products into a specific order.

#### To arrange products in a category:

1. Login to the WordPress admin, and open the Shopp Category Manager.
2. Select the category link for the custom category that you would like to arrange.
3. Click the **Arrange Products** button, located at the bottom of the Settings panel, to reveal the current arrangement of products in this category.
4. Using your mouse, **click and drag** one of the product to the desired location.  Alternately, you can use the up/down buttons to send a product instantly to the top or bottom of the listing. Your custom arrangement is saved automatically.
',
'Categories help tab');


$sidebar = Shopp::_mx(

'**For more information:**

[Category & Tag Pages](%s)

[Smart Collections](%s)

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)
',

// Translator context
'Categories help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'the-catalog/category-tag-pages/',
ShoppSupport::DOCS . 'the-catalog/smart-collections/',
ShoppSupport::DOCS . 'the-catalog/managing-custom-categories/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

);

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => $Overview
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'managing',
	'title'   => Shopp::__('Managing Categories'),
	'content' =>  $Managing
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'editor',
	'title'   => Shopp::__('Editing Categories'),
	'content' => $Editor
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'templates',
	'title'   => Shopp::__('Templates'),
	'content' => $Templates
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'variant-templates',
	'title'   => Shopp::__('Variant Templates'),
	'content' => $VariantTemplates
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'faceted',
	'title'   => Shopp::__('Faceted Menus'),
	'content' => $Facets
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'detail-facets',
	'title'   => Shopp::__('Detail Facets'),
	'content' => $DetailFacets
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'price-facet',
	'title'   => Shopp::__('Price Ranges'),
	'content' => $PriceFacet
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'arrange',
	'title'   => Shopp::__('Arrange Products'),
	'content' => $Arrange
) );

get_current_screen()->set_help_sidebar($sidebar);

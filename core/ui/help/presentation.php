<?php

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Presentation

The **Presentation** settings allow you to adjust preferences that affect the look of your storefront.

#### Templates

By default, Shopp will use a set of starter **content** templates that are included as part of the plugin files. You can create a copy of the built-in template files to use as a starting point for your own, custom content layouts for your product pages, categories, shopping cart and checkout pages.

To setup custom content templates for your active theme, you will need to locate your theme files on your web server through SSH or FTP access.  Inside your theme files, create a new directory named `shopp/`. You will need to make sure the web server has write privileges on the `shopp/` directory so that Shopp can handle copying the templates for you.

#### Catalog Inventory

You can set out-of-stock products to be shown or hidden in your storefront. Setting out-of-stock products to be visible will allow the products to continue to be picked up by search engines and retain their page authority even while not being able to be ordered. In the default starter templates, products will have their add to cart buttons hidden and disabled, replaced with an out-of-stock message displayed instead.

#### Catalog View

The **Catalog View** sets the default view for product collections, including categories and tags. In the default starter templates, this setting can be overridden by the shopper clicking on the view buttons to change their layout. When a visitor changes the view layout, it overrides this default setting and their chosen view sticks with them until they change it again.

#### Grid Rows

The grid view shows products in aligned pattern of rows and columns – a number of products across and a number of products down. This setting changes the number of products show across a single row of the grid view.

#### Pagination

The number of products shown per page of a collection of products is controlled with this setting.

#### Product Order

The **Product Order** sets the initial sort order for products in category and tag collections. In the default starter templates, a product sort order menu is shown to shoppers and can be changed by visitors browsing your storefront.

#### Image Order

The **Image Order** setting will set the sort order for product and category images presented in your templates. Images can be set to sort normally with the **Order** option, in **Reverse** and **Shuffle** for a random order using either the **Custom arrangement** you set in the product editor image panel, or the **Upload date** of the images.

',

'Presentation help tab')
) );


get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Presentation help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS,
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));

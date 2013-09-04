<?php
get_current_screen()->add_help_tab( array(
	'id'      => 'pages',
	'title'   => Shopp::__('Pages'),
	'content' => Shopp::_mx(

'### Pages

Shopp uses dynamic storefront pages to display store content to shoppers. These pages allow the look and feel of your WordPress website’s theme to wrap around the storefront content including products, product categories, product collections, the shopping cart, checkout form and customer account dashboard.

<table>
<thead><tr><th>Page%s</th><th>Used for&hellip;</th></tr></thead>
<tbody>
<tr><td>Store</td><td>Displaying storefront product catalog content such as product collections and products detail pages.</td></tr>
<tr><td>Account</td><td>Displays the account pages including account login, account dashboard menu and account dashboard sub-pages.</td></tr>
<tr><td>Cart</td><td>Displays the shopping cart page.</td></tr>
<tr><td>Checkout</td><td>Displays the checkout form.</td></tr>
<tr><td>Confirm Order</td><td>Displays the confirm order page to confirm changes to an order. This page is usually only displayed in special circumstances such as before submitting the order to an offsite payment processor, or when the totals (like shipping &amp; taxes) from the shopping cart change because of updated billing address or shipping address information.</td></tr>
<tr><td>Thanks</td><td>Shown at the end of the checkout process when the order is successful.</td></tr>
</tbody>
</table>

Changing the catalog page **slug** will change the base website address for everything else in the store: products, categories, the cart and checkout pages.

You can change the Shopp page titles and even the URL slug in the **WordPress Admin** menus under **Shopp** &rarr; **Setup** &rarr; **Pages**. All the storefront page website addresses are based upon the **catalog page**.

Normally Shopp is set up so that the **catalog page** uses `shop` as its **page slug**. So any of the other storefront pages, such as the shopping **cart page** will use a web address similar to:

    http://website.com/shop/cart/


If the **page slug** for the **catalog page** is changed to `store` the web address for the shopping cart page would change to something like this:

    http://website.com/store/cart/

A product named **WordPress T-Shirt** that has a slug of `wordpress-t-shirt` would have a web address similar to:

    http://website.com/store/wordpress-t-shirt/

A category named **Apparel** with the slug `apparel` would have a website address similar to:

    http://website.com/store/category/apparel/

',

'System Pages help tab',

str_repeat('&nbsp;', 20)
) ) );

get_current_screen()->add_help_tab( array(
	'id'      => 'maintenance',
	'title'   => Shopp::__('Maintenance'),
	'content' => Shopp::_mx(

'#### Maintenance Mode

Enable maintenance mode to disable the storefront and show a maintenance message page instead of the normal storefront pages. This setting allows the storefront to be disabled while working on the store.

The maintenance mode page layout can be customized with both a custom page layout template file and a Shopp content template file.

A custom maintenance page layout file named `shopp-maintenance.php` can be added to the active theme to provide a custom layout (header, content area, sidebars and footer).

A custom Shopp content template file named `maintenance.php` can also be added to the active theme `shopp/` content template files to display a custom maintenance message treatment.

',

'System Pages help tab')
) );


get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'System Pages help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS,
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));

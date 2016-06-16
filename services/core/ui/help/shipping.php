<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'rates',
	'title'   => Shopp::__('Rates'),
	'content' => Shopp::_mx(

'### Shipping Rates

The **Rates** screen sets up shipping method rates calculated for your customers at checkout. The **Rates** screen will not be available until you enable **Calculate Shipping** in the **Shipping Settings** screen.

Shipping rates are setup using either **shipping rate calculators** or by using a **Shopp Shipping Add-on** that integrates with a **real-time shipping service rate provider**. Additiona real-time shipping rate integrations are available for purchase on the Shopp Store as Shopp add-ons.

Shopp shipping rates calculators can be set up to add shipping method selections and cost estimates to your storefront.

Shopp shipping calculators come in two types built-in types, Flat Rate Calculators and Tiered Rate Calculators, as well as a number of real-time shipping systems, which are available as add-on modules. See [[Additional Shipping Systems]] for more information.

#### To enable shipping rate calculators:

Navigate in your **WordPress Admin** to **Shopp** &rarr; **System** &rarr; **Shipping**. If shipping rate calculation is enabled, the shipping rate manager will appear. Otherwise, check the box labeled **Calculate Shipping**, and click the **Save Changes** button. Next, click **Rates** just below the tabs to view the shipping rate manager.
',

'Shipping Settings help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'settings',
	'title'   => Shopp::__('Settings'),
	'content' => Shopp::_mx(

'### Shipping Settings

The **Settings** screen allows you to adjust shipping and inventory settings. The **Rates** screen will not be available until you enable **Calculate Shipping** in the **Shipping Settings** screen.

#### Calculate Shipping

If the **Calculate Shipping** setting is disabled, you will not be able to setup shipping rates and no shipping costs will be shown to customers.

When the **Calculate Shipping** setting has been enabled, the **Shipping** screen will display add a new navigation menu with **Rates** and **Settings**. The configurable settings will be moved to the **Settings** menu.

#### Track Inventory

Enable inventory tracking to track product stock levels, add inventory management controls and add inventory reports. Disabling this feature means that no stock levels will be checked or updated for products when customers place orders. While disabled the product inventory management screen and inventory reports will not be available.

#### Shipping Carriers

Select the shipping carriers that you will be using to deliver shipped items to add them to the shipment notice menus in the **Order Manager**. The **Shipping Carriers** setting does not affect which carriers are used for shipping rate estimates. Enabled carriers are included in the shipment notice menu to speed up data entry while entering shipment tracking information to fulfill an order.

#### Packaging

The packaging method allows you to set a packaging strategy the system should use to group items to send to a shipping service provider for real-time shipping cost estimates.

* Only like items together - Package similar line items (same product variants) together into a package
* Each piece separately - Separate each item into a different package
* All together - Attempts to fit as many items into a single box as possible by the weight and dimension of the items
* By total weight - Fits as many items into a single box as possible by the weight of the items

#### Package Limit

The **Package Limit** setting will set the default maximum weight for your boxes and is used in conjunction with the **All together** and **By total weight** packaging methods. Products are added to a "package" until the maximum package limit is reached and a new package is created for reamining products in an order.

#### Units

Sets the standard units of measure for product weight and dimensions. These units of measure are the units used in the product editor when setting the weight or length of a product.

#### Order Processing

The **Order Processing** setting is designed to allow you to set an estimated shipment processing delay to include in shipping delivery estimates. The order processing time refers to the time it takes from receiving the initial order to: pack the products for shipment and hand over the packages to a shipping service provider. This setting is used as the default store-wide order processing timeframe. Specific products can also set additional order processing delays that are added to the default order processing range chosen for this setting.

#### Low Inventory

Setting a **Low Inventory** value will allow Shopp to help inform you when items get to a low stock level. Set at what level Shopp will consider inventory for a product to be low. For example, setting it to 10 percent, Shopp will consider 10 or less remaining products out of 100 initially stocked as a low stock level. Or, if 50 products are initially stocked, a Low Inventory setting of 15 percent will treat 8 or less remaining products as low.

#### Order Handling Fee

An arbitrary **Order Handling Fee** is added to the shipping costs of all orders. This fee is included as part of the estimated shipping costs calculated for customer orders.

#### Free Shipping Text

A universal label that can be used to indicate free shipping for items in an order.

#### Out of Stock Notice

A universal label you can set for Shopp to use for "out-of-stock" products. In the default starter templates this label is shown on product pages in place of the "add to cart" buttons.
',

'Shipping Settings help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'calculators',
	'title'   => Shopp::__('Calculators'),
	'content' => Shopp::_mx(

'### Shipping Calculators

The shipping calculators built-in to Shopp provide shipping costs dependent primarily on the destination of the order. Each calculator can be used multiple times so that you can set up different shipping options with varied cost structures to give customers more choices. A good example would be setting up a flat rate shipping calculator with the same destinations, but different rates for a **Standard** shipping option and an **Expedited** shipping option.

The built-in shipping calculators all share the same destination options. Each calculator can apply to several different destinations from generic regions to very specific locations. For example, you can set up a shipping option with rates for anywhere in the world, for specific continental regions, or countries within those regions, or areas within a country, all the way down to specific states (or provinces) and even a list of zip code ranges (for USA, Canada and Australia).

#### To add a shipping rate calculator:

* Go to the **Shipping Rates** admin page from **Shopp** &rarr; **Setup** &rarr; **Shipping**.
* Select the desired rate calculator from the menu labeled **Add a shipping method**, to open the rate calculator editor.
* Add an **Option Name** for the rate calculator. This name will be displayed to the customer as a shipping option on the checkout page.
* Set the **Estimated Delivery** range, which will be used on the checkout page to calculate delivery dates.
* If you will be using a real-time shipping system, you can select the box for **Real-time rates fallback**, if you wish the shipping option to be used only when real-time rates from the service provider are unavailable.
* From the **Destination** menu, select the shipping destination for the rate, or rate table.
* If the destination is the US or Canada, you may also enter a Postal Code for the rate, or rate table. See Using Postal Codes below.
* Set up the **Rate** or rate table for the destination.

#### Adding or removing a line to a rate table: ####

* To add a new line to a rate table, click the plus on the right side of the line.
* To remove a line from a rate table, click the minus on the left side of the line.

#### To add a destination to a rate calculator:

* Go to the **Shipping Rates** admin page from **Shopp** &rarr; **Setup** &rarr; **Shipping**.
* Click the **edit** link under the label of the desired rate calculator, to open the rate calculator editor.
* Click the **Add Destination Rate** button at the bottom of the rate calculator editor.

### Tips For Using Postal Codes

When adding destinations to rate calculators for destinations in the US and Canada, you can add postal code matching rules for the rate. The postal code field for the destination can include a single postal code, or a set of comma-separated rules.

A complete postal code can be used:

> `95131`

An asterisks (*) can be used to match multiple postal codes.

> `95*`

A hyphen (-) can be used to specify a range of numeric postal codes:

> `95000-95999`

A list of postal codes and postal code rules can be separated by commas:

> `95131,95*,95000-95999`
',

'Shipping Settings help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'flatrates',
	'title'   => Shopp::__('Flat Rates'),
	'content' => Shopp::_mx(

'Flat rate calculators provide the simplest cost structure to manage. A single rate is matched to a specific destination. When the customer provides shipping address information through the shipping estimate form in the shopping cart, or by completing their full shipping address during checkout, the most specifically matching shipping destination rate is used.

An easy example to understand how it works is to set up a flat rate shipping option named **Standard** with the following destination rates for a business shipping packages from Colorado:

* Anywhere – $15
* North America - $10
* Continental US - $5
* Hawaii - $7
* Alaska - $7
* Colorado - $4

For this example, customers that use a United Kingdom shipping address will see the **Standard** shipping option with a cost of $15 (Anywhere). A customer with a Virginia, USA address will see the Standard shipping option with a cost of $5 (Continental US). A customer with a Mexico shipping address will see the Standard shipping option with a cost of $10 (North America). The state specific rates are more specific than any of the other destinations and will only match for customers with shipping addresses in those states.

The following are the flat rate shipping calculators built-in to Shopp:

* **Flat Order Rates**
Flat Order Rates provides shipping rates to cover the entire order. Multiple rates can be added, one for each shipping destination.
* **Flat Item Rates**
Flat Item Rates provides per-item shipping rates. The rates entered for each destination is multiplied by the quantity of items in the order. For example, if a customer orders 2 T-shirts and 1 pair of socks, a shipping rate of $3 will be multiplied by 3, the total quantity of items, for a total cost of $9.
* **Free Option**
The Free Option shipping calculator provides $0 shipping costs. This is useful for offering a free pickup option for brick and mortar businesses that can offer it. Like the other shipping systems in Shopp, you can specify which destinations the option applies to, even narrowing availability down to the postal code level (for US and Canada).
',

'Shipping Settings help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'tieredrates',
	'title'   => Shopp::__('Tiered Rates'),
	'content' => Shopp::_mx(

'Tiered shipping rate calculators, also known as table rates, are more complex than flat rate shipping calculators. Although they are more complex, they can also be far more accurate at calculating real-world shipping costs.

A tiered rate calculator sets a destination to a table of shipping rates based on a tiered variable such as item quantity or order amount. For any given destination, you can set up separate tables that determine the shipping rate based on an order factor like the quantity of items ordered. See the example tables below to see how a tiered item table can be setup.
In the example, ordering 1 item would result in a shipping cost of $5. An order of 3 items would also be $5. Ordering 7 items would cost $10 in shipping. Ordering 10, or 20 or even 100 items would have $15 in shipping costs.

A completely different rate structure can be set up for each destination providing comprehensive coverage and precision.

* **Item Quantity Tiers**
Using the Item Quantity Tiers calculator, destination rate tables are set up for different levels of item quantities being ordered. See the example above for details on setting up Item Quantity Tiers.

<table>
<tr><th>Rates by Item Quantity</th></tr>
<tr><td>1 item and above</td><td>$5.00</tr>
<tr><td>5 items and above</td><td>$10.00</tr>
<tr><td>10 items and above</td><td>$15.00</tr>
</table>

* **Order Amount Tiers**
Order Amount Tiers uses destination rate tables based on the grand total cost of the entire order. Rates can be set at levels based on the total value of the order for each destination. Shipping to countries farther away would typically have higher rates, and destinations closer to the shipping location would have lower rates.

<table>
<tr><th>Rates by Order Subtotal ($)</th></tr>
<tr><td>$20.00 and above</td><td>$5.00</tr>
<tr><td>$50.00 and above</td><td>$7.50</tr>
<tr><td>$100.00 and above</td><td>$15.00</tr>
</table>

* **Order Weight Tiers**
Order Weight Tiers sets the shipping rate using destination rate tables based on the total weight of the entire order. The weight of the product multiplied by the quantity of the product being ordered is added together for the total order weight. The rate that matches the level of the total order weight is used to determine the shipping cost for the specific destination(s).

<table>
<tr><th>Rates by Order Weight (kg)</th></tr>
<tr><td>1kg and above</td><td>€5.00</tr>
<tr><td>5kg and above</td><td>€7.50</tr>
<tr><td>10kg and above</td><td>€15.00</tr>
</table>


* **Percentage Amount Tiers**
The Percentage Amount Tiers shipping calculator uses table rates based on a portion of order subtotal specified by a percentage amount. The order subtotal is the result of adding all the item totals together. It does not include taxes, shipping or discount totals. Instead of setting a direct shipping rate amount, the shipping rate is determined by a percent value of the order subtotal. Using the example table, an order with a subtotal of $25 would result in shipping costs of $2.50. An order subtotal of $75 would have $5.62 in shipping costs. A subtotal of $250 would have $12.50 of shipping costs.
',

'Shipping Settings help tab')
) );

get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Reports help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'shipping/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));
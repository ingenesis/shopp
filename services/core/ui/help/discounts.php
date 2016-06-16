<?php
$Overview = Shopp::_mx(
'Shopp’s promotion toolset makes it easy to give customers discounts at checkout or run sales on your storefront. A powerful conditions-based rule system provides a wide variety of ways to award and restrict discounts.

To create a discount you need to select a type of discount, a set of conditions that must be met in order for the discount to apply, and availability settings to limit when the promotion can be used.

Discounts can be applied to products in your storefront catalog, an item in the customer’s shopping cart, or the entire shopping cart order.
',
'Discounts help tab');

$Types = Shopp::_mx(
'There are 4 discount types available:

* Percentage Off
* Amount Off
* Free Shipping
* Buy X Get Y Free

#### Percentage Off Discounts

The Percentage Off setting sets a percentage of the price to take off the price of a catalog product, the subtotal of a customer’s order, or the unit price of an item in the customer’s shopping cart.

For example, if a product is $100 and a percentage off value of 10% is used, the discount amount will be $10, reducing the product price to $90 (100 - (100 × 0.10) = 90).

#### Amount Off Discounts

The Amount Off setting takes a discount amount off of the price of a catalog product, the subtotal of a customer’s order, or the unit price of an item in the customer’s shopping cart.

For example, if a product is $25 and the amount off value is set to $5, the discount amount of $5 will reduce the product price to $20 (25 - 5 = 20).

#### Free Shipping

The free shipping setting will set a free shipping override on a catalog product, or for all shipping costs of a customer’s shopping cart, or for a specific item in the customer’s shopping cart.

#### Buy X Get Y Free

This specialized discount allows you to set up a quantity based discount to an item in the customer’s shopping cart. This type of discount can only be applied to specified shopping cart items. You can use it to set up a Buy 2 Get 1 Free promotion.

Shoppers will need to add the total quantity of items specified by Buy and the Get settings to get the discount. For example, in a Buy 2 Get 1 Free promotion, shoppers would need to add a total quantity of 3 items to get the third item for free. The discount amount is calculated as the full unit price of the target shopping cart item.

For example, using a Buy 2 Get 1 Free setting targeted with conditions to a cart item that sells for $5, the discount amount of $5 is applied to the normally $15 item subtotal for a total item price of $10 (15 - 5 = 10). The promotion also works in multiples so that using the same discount, if a customer adds 6 items to their shopping cart, the discount amount would double to $10 giving an item subtotal price of $20 (30 - 10 = 20).',
'Discounts help tab');

$Conditions = Shopp::_mx(
'The conditions give you a powerful way to apply promotions to everything in your store down to specific products in your catalog or orders with specific items in them.

Before setting up conditions, you will need to first decide how the rules will be matched. If set to match all conditions, every condition you add must be matched for the discount to be applied (whether to a product in the catalog, or an item in the cart, or to the shopping cart subtotal). If set to match any of the conditions, only one condition needs to match for the discount to be applied.

#### Targeting Where Discounts Apply

While setting up conditions, you’ll also need to decide where a promotional discount will apply. You can set up discounts to apply to: catalog products, the customer’s shopping cart or a specific shopping cart item.

#### Using Promotions Effectively

It is important to understand how Shopp will apply discounts for Shopping Cart Promotions, so you know how these promotions will affect your bottom-line, and how to potentially avoid awkward customer service issues. Be aware that it is your responsibility to understand how a promotion will apply. Never assume that it is impossible for a customer to get a better deal than what you intended when you set up your promotion.

#### Limiting the Number of Promotions

Sometimes you want to be sure that a customer can&apos;t "game" your store by stacking countless promotions in the cart to reduce the total to near nothing. The first limiting factor is the Promotions Limit setting. You can set this limit in from the **Setup** &rarr; **Preferences** screen.
Login to the WordPress Admin and using the menus navigate to **Shopp** &rarr; **Setup** &rarr; **Preferences**, and set the **Promotions Limit**.

The default setting is to allow an unlimited number of promotions to apply to the shopping cart. If you make use of promotions, we recommend setting a **Promotions Limit**.

For each order, the total number of promotions applied to the cart will be limited to this number. When a customer reaches the limit, no other matching promotions will be allowed to apply, either automatically or by a **promo code**.

It is also worth noting that **promo codes** are not analogous to **coupons**, (or one-time use codes). While a **promo code** cannot be used twice on a single order, it can be used by customers repeatedly on separate orders unless a **promo use count** condition is set.
',
'Discounts help tab');


$Catalog = Shopp::_mx(
'### Catalog Product Discounts

Catalog product discounts will show up in the storefront when shopper’s browse the catalog. Discounts will appear as sale prices where ever the product is listed in the storefront catalog.

When a promotional discount is applied to catalog products, the discount amount is taken off of the regular price of the product. Any sale price set on the product is replaced by the reduced price of the promotion (or promotions) that apply.

#### Catalog Product Rules

For discounts applied to products in the catalog, the following rules are available:
Name
Match a product by the product name.

* **Category**
Match a product if it is assigned to a specific category.
* **Variant**
Match a product based on the name of a product variant.
* **Price**
Match a product based on the price of the product or variant.
* **Sale Price**
Match a product based on the sale price of the product or variant (as set in the product editor, not by any other promotions.)
* **Type**
Match a product based on the type of product (Shipped, Download, Donation, etc)
* **In Stock**
Match a product based on the number of the product or variant in stock
',
'Discounts help tab');

$Cart = Shopp::_mx(
'### Shopping Cart & Cart Item Rules

Since the shopping cart and cart items share the same rules, it can be confusing to understand where the discount really applies.

The selected target setting specified **where** the discount is applied to. The rules select **when** the discount should apply.

If the discount target is set to **shopping cart**, the discount will be taken off the subtotal amount of the shopping cart not an item in cart, even if item criteria are specified in the conditions for the discount. All conditions are satisfied independently against the entire shopping cart, not against a specific item in the cart, even if item rules are set in the conditions.

When a promotion is set to **Apply discount to *shopping cart* subtotal when *any* of these conditions match the cart** any condition that matches the shopping cart will trigger the discount to apply to the order. In contrast, using **Apply discount to *shopping cart* subtotal when *all* of these conditions match the cart** will require that all conditions match the shopping cart at the same time. That means two item conditions can be satisfied by two different items in the shopping cart.

For promotions applied to a customer’s shopping cart or shopping cart item, the following rules are available:

* **Any item name**
Match orders where any item exists in the shopping cart with the specified item name.
* **Any item quantity**
Match orders where any item in the shopping cart has the quantity specified.
* **Any item amount**
Match orders where any line item in the shopping cart has a subtotal price specified.
* **Total quantity**
Match orders where the total quantity of items in the shopping cart matches the specified value.
* **Shipping amount**
Match orders based on the shipping cost of the order.
* **Subtotal amount**
Match orders based on the shopping cart subtotal (the total of all the items ordered before discounts, taxes and shipping are applied.)
* **Discount amount**
Match orders based on the shopping cart discount total.
* **Customer type**
Match orders based on the type of customer specified (the customer type can be set in the customer account profile editor.)
* **Ship-to Country**
Match orders based on the shipping address country specified.
* **Promo use count**
Match orders based on the number of times the current promotion has been successfully used by customers.
* **Promo code**
Match orders where the shopper has supplied the code as specified here in the conditions.
',
'Discounts help tab');

$Item = Shopp::_mx(
'### Cart Item Discounts

In many ways cart item discounts work the same way that discounts targeting the shopping cart do. They share the same conditional rules for determining when the discount applies. The most important distinctions of cart item discounts in contrast to shopping cart discounts is that taxes are calculated after per-item discounts and cart item discounts use a second set of conditions to determine which item or items in the cart to discount.

#### Cart Item Discount Targeting Rules

The following are rules that can be used to target items in a customer’s shopping cart:

* **Name**
Match a cart item by the name of the product.
* **Category**
Match a cart item by the name of a category to which the product is assigned.
* **Tag Name**
Match a cart item by the name of a tag the product has been assigned.
* **Variant**
Match a cart item by the name of a product variant.
* **Input Name**
Match a cart item by the name of a custom product input.
* **Input Value**
Match a cart item by the value of a custom product input.
* **Quantity**
Match a cart item by the quantity of product in the shopping cart.
* **Unit Price**
Match a cart item by the unit price of a product in the shopping cart.
* **Total Price**
Match a cart item by the total price of the line item in the shopping cart.
* **Discount amount**
Match a cart item by the discount amount of the line item in the shopping cart.
',
'Discounts help tab');

$sidebar = Shopp::_mx(

'**For more information:**

[Taxes & Discounts](%s)

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)
',

// Translator context
'Discounts help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'taxes/taxes-discounts/',
ShoppSupport::DOCS . 'the-catalog/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

);

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => $Overview
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'types',
	'title'   => Shopp::__('Discount Types'),
	'content' => $Types
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'conditions',
	'title'   => Shopp::__('Conditions'),
	'content' => $Conditions
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'catalog',
	'title'   => Shopp::__('Catalog Discounts'),
	'content' => $Catalog
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'cart',
	'title'   => Shopp::__('Cart Discounts'),
	'content' => $Cart
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'item',
	'title'   => Shopp::__('Item Discounts'),
	'content' => $Item
) );

get_current_screen()->set_help_sidebar($sidebar);
<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Checkout Settings

#### Shopping Cart

Enable the shopping cart to allow visitors to select products and place orders. Disabling this option will turn off the shopping cart, hide all add to cart buttons and prevent placing orders.

#### Order Confirmation

During checkout, Shopp will show a confirm order page any time the order total amount changes due to information entered on the checkout page. Changing a shipping address can affect the shipping costs, changing the total which may not get reflected directly on the checkout page. A change in order price requires that the customer be notified of the change prior to submitting the order. The confirm order screen can also be set to be displayed for every customer during checkout.

#### Receipt Emails

Receipt emails can be setup to send only to the customer, or to both the customer and the **Merchant Email** set on the **Shopp Setup** screen.

#### Customer Accounts

Shopp supports three modes of customer management:

* **No Accounts**
Customer records and orders are tracked, but no login to the store is created for your customer. Customer records provide a historic log of customer activity where a single customer may have many customer records for each order they place on the site.
* **Enable Account Logins**
A customer login is created (associated with the email address of the customer) to login to their account dashboard to access past orders and downloads, and to make future checkouts faster and easier. During checkout, customers set a password to access their account. They can then login to their account on the store’s account page with their email address and their specified password.
* **Enable Account Logins integrated with WordPress Users**
A WordPress user is created when the order is final, and it is associated with a Shopp customer to track their purchases. During checkout, customers set both a login name and a password to access their account. The login name they set is used as the WordPress user login name when the account is created. Customers can then login to their account on the store’s account page with their WordPress user name and their specified password.

#### Discounts Limit

Specify a limit to the number of discounts that can be applied to a single order. The default setting is no limit.

',

'Checkout help tab')
) );

get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Checkout help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS,
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));


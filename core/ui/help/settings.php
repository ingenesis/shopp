<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Shopp Setup

The **Shopp Setup** screen provides the basic setup required to begin using Shopp.

At a minimum you should setup the:

- Base of Operations
- Target Markets
- Merchant Email
',

'Shopp Setup help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'support-key',
	'title'   => Shopp::__('Support Key'),
	'content' => Shopp::_mx(

'### Support Key

If you have a Shopp <strong>support key</strong>, you can activate your Shopp installation with the Shopp support services from shopplugin.com to get access to:

- Troubleshooting help from the Shopp Support Team
- Video walkthroughs in the Shopp administration screens
- Automatic updates for Shopp and any official Shopp Addons.

#### Activating

To activate your support services for this installation, copy your Shopp support key from your order receipt to the **Support Key** box and click the **Activate Key** button.

#### De-activating

To de-activate your support key, click the **De-activate Key** button.

De-activating a single-site key will release the key&apos;s registration so that it can be used on another site.

If you do not have access to the site the key was last activated on, you will need to contact **Shopp Customer Service** to request de-activation of your key.',

'Shopp Setup help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'base-operations',
	'title'   => Shopp::__('Base of Operations'),
	'content' => Shopp::_mx(

'### Base of Operations

The Base of Operations sets up the primary location where your business operations occur.

The location you choose is used to automatically determine the currency, currency formatting, units of measure and tax behaviors for your store.',

'Shopp Setup help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'target-markets',
	'title'   => Shopp::__('Target Markets'),
	'content' => Shopp::_mx(

'### Target Markets

The **Target Markets** setting restricts the countries available to customers when specifying their billing and shipping addresses.

This effectively allows you to restrict which countries your store will allow sales to.

Click the **Select All** toggle to select all of the countries supported by Shopp. To deselect all the previously selected countries, click the **Select All** toggle again.',

'Shopp Setup help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'merchant-email',
	'title'   => Shopp::__('Merchant Email'),
	'content' => Shopp::_mx(

'### Merchant Email

The **Merchant Email** field allows you to specify one (or more) email addresses where merchant notification emails should be sent. It is also used as the default **From** email in all emails sent from Shopp to customers.

Notifications include:

- New orders
- Low inventory warnings
- Transaction errors
',

'Shopp Setup help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'business',
	'title'   => Shopp::__('Business Details'),
	'content' => Shopp::_mx(

'### Business Name & Address

The business name and address is generally used on email receipts sent to the customer. A developer, however, can use the Shopp Theme API to show these details in any theme file.',

'Shopp Setup help tab')
) );


get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)',

// Translator context
'Reports help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'reports/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));
<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'setup',
	'title'   => Shopp::__('Shopp Setup'),
	'content' => Shopp::_mx(

'### Shopp Setup

After installation, you will need to customize your store settings in order for your new Shopp installation to work properly. This basic set up should be completed before beginning to use Shopp.

#### Update Key

Copy your Shopp support key from your order receipt to the Update Key box and click the **Activate Key** button. Activating your key provides access to automatic updates, video tutorials throughout Shopp, and registers your site domain for official support from the support team.

#### Base of Operations

Select the location your store primarily operates out of to automatically set up currency, measurements and tax behaviors.

#### Target Markets

Select the markets you wish to sell to. Click the **Select All** toggle to select everything at once. Click the **Select All** toggle again to deselect all previously selected markets.

#### Merchant Email

Enter an email address to receive new order, payment and other email notices from the storefront.

#### Business Name & Address

Enter the name and address of the business for the storefront. These details are able to be included on the order receipt and in any outgoing email notices.
',

'Shopp Setup help tab')
) );

get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Shopp Setup help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS,
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));

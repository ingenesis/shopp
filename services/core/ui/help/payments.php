<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Payment Settings

The **Payment Settings** screen sets up payment systems available to your customers at checkout.

Each activated payment system will add a row to the settings table. To the right of the payment system row, are check boxes indicating capabilities of that payment system.

- SSL: Uses and requires SSL
- Captures: Provides onsite payment authorization and capture capabilities
- Recurring: Provides recurring payment support
- Refunds: Includes refund and order cancellation (void) capabilities

**To add a payment system:**

- Click the menu labeled **Add a payment system...**
- Select the payment system you want to activate on the site

**To edit a payment system:**

- Click the **Name** of the payment system in the table
- or, move your mouse over the table row and click the **Edit** link

**To remove a payment system:**

Move your mouse over the table row of the payment system and click the **Delete** link.
',

'Payment Settings help tab')
) );


get_current_screen()->add_help_tab( array(
	'id'      => 'Option Name',
	'title'   => Shopp::__('Option Name'),
	'content' => Shopp::_mx(

'### Option Name

While editing a payment system, each payment system allows you to enter an **Option Name**. It is used as the name of the payment option displayed to the customer at checkout.

If you offer multiple options through multiple payment systems, the customer will be prompted to select which payment option they prefer to use.
',

'Payment Settings help tab')
) );


get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

[Offsite & Onsite Payments](%s)

[PCI-DSS Compliance](%s)

[Additional Providers](%s)

',


// Translator context
'Payments help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'payment-processing/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT,
ShoppSupport::DOCS . 'payment-processing/offsite-onsite-payments/',
ShoppSupport::DOCS . 'payment-processing/pci-dss-compliance/',
ShoppSupport::DOCS . 'payment-processing/additional-providers/'

));
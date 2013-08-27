<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Downloads

Restrictions can be set to limit the access of downloads that have been purchased.

#### Download Limit

The download limit allows you to restrict the number of downloads of a purchased product. By default, an unlimited number of downloads are allowed.

#### Time Limit

The time limit setting provides access to a purchased product download for a limited amount of time starting from when the payment was received.

### IP Restriction

This can allow restricting access to the computer the download was bought from. However, as IP addresses are sometimes randomly assigned, and can easily be cloaked, this option has limited usefulness for most cases.

#### Download Quantity

This setting prevents a quantity selection of digital goods. It is a useful setting when you prefer to prevent the possibility of buying the same digital good more than once in an order.
',

'Downloads help tab')
) );


get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

',

// Translator context
'Context help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS,
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

));

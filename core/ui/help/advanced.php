<?php

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => Shopp::_mx(

'### Advanced

This screen provides access to advanced troubleshooting and maintenance tools.

#### Script Loading

There are two settings that can adjust how Shopp handles loading JavaScript libraries:

- **Load behavioral scripts through WordPress**
Enable this setting only if you are having problems with the Shopp Script Server. The Shopp Script Server offers several advantages including much lower overhead and script compression. The script server, however, can be loaded through WordPress, but every script request will flow through WordPress and will load all the active plugins and themes, using more memory and processor time to serve the JavaScript libraries.
- **Enable Shopp behavioral scripts site-wide**
Ordinarily, Shopp will only load its JavaScript on the Shopp pages that require them. If you need the Shopp JavaScript behaviors on other WordPress pages (like the homepage) then this setting should be enabled.

#### Error Notifications

E-mail notifications for specific types of logged errors and notices can be subscribed to using the email addresses included in the merchant email address setting in the general setup screen. Simply select the notifications to subscribe to:

- **Transaction Errors**
Transaction errors are for payment processing related errors.
- **Login Errors**
Login errors will notify you of login issues occuring on the site.
- **Add-on Errors**
These errors and notices are related to loading and using Shopp add-ons.
- **Communication Errors**
Communication errors are for issues with Shopp sending messages to other servers (such as shipping rate providers or payment gateways)
- **Inventory Warnings**
The inventory warnings include notification when items are out-of-stock or have a low stock level.

#### Logging

Use this setting to change the level of detail in the log file. Not all messages logged in the log file are **errors**. Some messages are notifications of potential issues, not true errors. When enabled, and messages are logged, a **Log** tab will appear in the **System** screens.

#### Search Index

Click the **Rebuild Product Search Index** to clear the current search engine index and recreate the index table for the entire product catalog.

#### Product Summaries

The product summaries is a special data set that builds a table of summarized information to speed up query performance in large collections of products. From time to time it can become out of date on some products or stuck. Click the **Recalculate Product Summaries** to mark all products in the catalog to have their summary data recalculated.

#### Image Cache

Deletes current cached image data, including all resized images, allowing them to be regenerated.
',

'Advanced help tab')
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

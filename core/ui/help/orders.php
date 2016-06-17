<?php

$Overview = Shopp::_mx(

'### Orders Manager

The order management system in Shopp gives you access to your store&apos;s order history and allows you to move orders through a custom order processing workflow. The order management screen is accessible by clicking the Shopp menu, or **Shopp** &rarr; **Orders** in the **WordPress Admin**.

As orders are placed by customers, they will appear in your **Order Manager**. You can view orders in different order processing states using the **Order Status Menu**. The total number of orders for a status are listed beside the label. You can also filter orders by date ranges and searching.',

'Orders Manager help tab');

$Filtering = Shopp::_mx(

'### Filtering

To filter orders by date, click the drop-down menu initially labelled **Show All Orders**. Selecting a date range option will reveal the start date and end date fields showing the exact date range for the chosen preset range. Click the **Filter** button to filter orders by the given date range.',

'Orders Manager help tab');

ob_start(); ?>
<table border="0" class="advsearch">
<tr><td><strong><?php Shopp::_e('Email'); ?>:</strong></td><td>help.desk@shopplugin.net</td></tr>
<tr><td><strong><?php Shopp::_e('Transaction ID','Shopp'); ?>:</strong></td><td>txn:95M27911DT480180V</td></tr>
<tr><td><strong><?php Shopp::_e('Gateway','Shopp'); ?>:</strong></td><td>gateway:"paypal express"<br />gateway:firstdata</td></tr>
<tr><td><strong><?php Shopp::_e('Credit Card Type','Shopp'); ?>:</strong></td><td>cardtype:visa</td></tr>
<tr><td><strong><?php Shopp::_e('Company','Shopp'); ?>:</strong></td><td>company:"Ingenesis Limited"<br />company:automattic</td></tr>
<tr><td><strong><?php Shopp::_e('Address (lines 1 or 2)','Shopp'); ?>:</strong></td><td>address:"1 main st"</td></tr>
<tr><td><strong><?php Shopp::_e('City','Shopp'); ?>:</strong></td><td>city:"san jose"<br />city:columbus</td></tr>
<tr><td><strong><?php Shopp::_e('State/Province','Shopp'); ?>:</strong></td><td>state:"new york"<br />province:ontario</td></tr>
<tr><td><strong><?php Shopp::_e('Zip/Postal Codes','Shopp'); ?>:</strong></td><td>zip:95131<br />postcode:M1P1C0</td></tr>
<tr><td><strong><?php Shopp::_e('Country','Shopp'); ?>:</strong></td><td>country:US</td></tr>
<tr><td><strong><?php Shopp::_e('Product','Shopp'); ?>:</strong></td><td>product:"acme widget"<br />product:widget<br />product:SKU123</td></tr>
<tr><td><strong><?php Shopp::_e('Discounts','Shopp'); ?>:</strong></td><td>discount:"25% off"<br />discount:code123</td></tr>
</table>
<?php $table = ob_get_clean();

$Search = Shopp::_mx(

'### Search

Using the search field, you can find orders by entering a specific order number, the customer’s email address or any part of the customer’s name. Click the **Search Orders** button to run the search.

### Advanced Search

Advanced search operations can be performed by using specially formatted search terms. The format for searching specific order information follows a pattern that includes a keyword identifying what order information to search followed by a colon (:) and a search term:

All of these advanced searches can be used:

',

'Orders Manager help tab') . $table;

$Exporting = Shopp::_mx(
'### Exporting

Orders can be exported in a variety of formats with any number of order data columns needed.

**Shopp supported order export formats:**

* Tab-separated.txt
* Comma-separated.csv
* Microsoft&copy; Excel.xls
* Intuit&copy; QuickBooks.iif

For Tab-separated, comma-separated and Microsoft® Excel file formats select the columns to include for each record in the export. Enable the Include column headings option to include column names in the first line of the export file.

When exporting to QuickBooks IIF format, enter the name of the QuickBooks account sales transactions should be recorded to. The name must match exactly the name of the appropriate QuickBooks account.

### Continuous Periodic Exports

Using filtering and searching you can isolate specific orders to export. Additionally, using date range filtering you can set up periodic exporting. Shopp stores the end date of each export that has date range filters enabled. This allows selecting the Last Export option from the date range drop-down menu which will automatically set the start field to the end date of the last completed export, and the end date field will be set to today’s current date.',

'Orders Manager help tab');

$sidebar = Shopp::_mx(
'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)',

// Translators context
'Orders Manager help tab (sidebar)',

// URL replacements
ShoppSupport::DOCS . 'orders-customers/managing-orders/',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT

);

get_current_screen()->add_help_tab( array(
	'id'      => 'overview',
	'title'   => Shopp::__('Overview'),
	'content' => $Overview
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'filtering',
	'title'   => Shopp::__('Filtering'),
	'content' =>  $Filtering
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'exporting',
	'title'   => Shopp::__('Exporting'),
	'content' =>  $Exporting
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'search',
	'title'   => Shopp::__('Search'),
	'content' => $Search
) );

get_current_screen()->set_help_sidebar($sidebar);
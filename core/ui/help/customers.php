<?php

$Overview = Shopp::_mx(

'### Customers Manager

The customer manager provides a list of customer accounts or customer records depending on the account mode set in Shopp&apos;s **Setup** &rarr; **Preferences** screen.

When the account system is set to **No Accounts**, the customer manager will list customer records. Returning customers that use the same information during checkout will create duplicate records because in this mode, customer records are kept as a historical log, not as active accounts. Each customer record is linked directly to an order in the system.

When using either of the account enabled modes (**Enable Account Logins** or **Enable Account Logins integrated with WordPress Users**) the customer records are listed as accounts that can be created, updated or deleted. Using an account login mode, each customer record can include multiple orders, and the number and gross sales of a customer&apos;s orders are including in the listing. To view a customer&apos;s orders, click the order totals. The order manager will be loaded with orders placed by the selected customer.',

'Customers Manager help tab');

$Filtering = Shopp::_mx(

'### Filtering

Like the order manager, the customer manager allows filtering the customer list by date. To filter customers by date, click the drop-down menu labelled **Show All Customers**. Selecting a specific date range option will reveal start and end dates with the exact preset date range for the preset option selected from the menu. Click the **Filter** button to filter customers by the date range.',

'Customers Manager help tab');

ob_start(); ?>
<table border="0" class="advsearch">
<tr><td><strong><?php Shopp::_e('Email'); ?>:</strong></td><td>help.desk@shopplugin.net</td></tr>
<tr><td><strong><?php Shopp::_e('Company'); ?>:</strong></td><td>company:"Ingenesis Limited"<br />company:automattic</td></tr>
<tr><td><strong><?php Shopp::_e('Login'); ?>:</strong></td><td>login:admin</td></tr>
<tr><td><strong><?php Shopp::_e('Address (lines 1 or 2)'); ?>:</strong></td><td>address:"1 main st"</td></tr>
<tr><td><strong><?php Shopp::_e('City'); ?>:</strong></td><td>city:"san jose"<br />city:columbus</td></tr>
<tr><td><strong><?php Shopp::_e('State/Province'); ?>:</strong></td><td>state:"new york"<br />province:ontario</td></tr>
<tr><td><strong><?php Shopp::_e('Zip/Postal Codes'); ?>:</strong></td><td>zip:95131<br />postcode:M1P1C0</td></tr>
<tr><td><strong><?php Shopp::_e('Country'); ?>:</strong></td><td>country:US</td></tr>
</table>
<?php $table = ob_get_clean();

$Search = Shopp::_mx(

'### Search

Using the search field, you can find orders by entering the customer&apos;s email address or any part of the customer&apos;s name. Click the **Search Customers** button to run the search.

### Advanced Search

Also like the order manager, advanced search operations can be performed by using specially formatted search terms. The format for searching specific order information follows a pattern that includes a keyword, identifying what order information to search, followed by a colon (:) and the search term.

All of these advanced searches can be used:

',

'Customers Manager help tab') . $table;

$Exporting = Shopp::_mx(
'### Exporting

Customers can be exported in a variety of formats with any number of customer data columns needed.

**Shopp supported order export formats:**

* Tab-separated.txt
* Comma-separated.csv
* Microsoft&copy; Excel.xls

For Tab-separated, comma-separated and Microsoft® Excel file formats select the columns to include for each record in the export. Enable the Include column headings option to include column names in the first line of the export file.

### Continuous Periodic Exports

Using filtering and searching you can isolate specific orders to export. Additionally, using date range filtering you can set up periodic exporting. Shopp stores the end date of each export that has date range filters enabled. This allows selecting the Last Export option from the date range drop-down menu which will automatically set the start field to the end date of the last completed export, and the end date field will be set to today’s current date.',

'Customers Manager help tab');

$sidebar = Shopp::_mx(
'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)',

// Translator context
'Customers Manager help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . 'orders-customers/customer-manager/',
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
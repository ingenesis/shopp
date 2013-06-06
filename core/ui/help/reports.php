<?php

$Overview = Shopp::_mx(

'### Reports

Shopp includes a several reports to help you keep tabs on your business. Learning how to read these reports properly can help you monitor your Key Performance Indicators (KPI) as it relates to your business&apos;s e-commerce operations.

#### Sales Report

The sales report shows the total amount of sales for your store. It highlights sales in the currency amount of your store, but also includes other helpful information like number of orders and number of items per order.',

'Reports help tab');

$Filtering = Shopp::_mx(

'### Filtering

Reports can be filtered by date and time scales to help you see the trends over different periods of time. The reports will initially load **Today** with the start and end dates set to today&apos;s date.

To change the date, you use the menu to quickly select preset date ranges. Setting a date range will automatically set the start and end dates to the exact preset date range for the option selected from the menu.

Set a custom date range by clicking the start or end date fields. A calendar will appear to make selecting the date easier, or you can simply type the new date.

Click the **Filter** button to apply your new date range and filter the current report.

### Time Scales

Report data can be scaled by time ranges so that you can spot trends easier using large scales such as "years", or zoom in to the data to see weekly or even hourly trends.

The following time scales are available:

- By Hour
- By Day
- By Week
- By Month',

'Report help tab');

$Exporting = Shopp::_mx(
'### Exporting

Reports can be exported in a variety of formats with any of the columns from the report.

**Shopp supported order export formats:**

* Tab-separated.txt
* Comma-separated.csv
* Microsoft&copy; Excel.xls

Click the **Export Options** button to select the columns you want to include in the report and choose the file format for the export file.

Enable the **Include column headings** option to include column names in the first line of the export file.

To download the export file, click the **Download** button.',

'Reports help tab');

$sidebar = Shopp::_mx(

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

get_current_screen()->set_help_sidebar($sidebar);
<?php
get_current_screen()->add_help_tab( array(
	'id'      => 'settings',
	'title'   => Shopp::__('Settings'),
	'content' => Shopp::_mx(

'### Calculate Taxes

#### To calculate taxes in Shopp, enable the Calculate Taxes setting:

1. Login to your **WordPress Admin** and navigate to **Shopp** &rarr; **Setup** &rarr; **Taxes**
2. Click the checkbox next to Enabled for the **Calculate Taxes** setting
3. Click the **Save Changes** button

### Inclusive Taxes

The **Inclusive Taxes** setting will automatically include taxes, determined by the applicable tax rate, into the price of products in the product editor and the product catalog. This setting is primarily useful to European countries where including taxes in the pricing of goods is required by law.

### Tax Shipping

Enable the Tax Shipping setting to include shipping costs as part of the tax calculations in the shopping cart.
',

'Taxes Settings help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'rates',
	'title'   => Shopp::__('Rates'),
	'content' => Shopp::_mx(

'### Tax Rates

For the most basic tax needs, simply set a tax rate, select the country, and, if needed, the state (or province) the rate applies to. For example, enter 5 for 5% (5 percent), or 0.5 for 0.5% (one-half percent). This sets the base tax rate for the selected location. In the shopping cart after the shipping estimate form is completed or during checkout after the shipping address is completed, the sales tax for the order will be calculated based on the rate that applies.

#### To set a tax rate:

1. Login to your WordPress admin and navigate to the **Shopp** &rarr; **System** &rarr; **Taxes** screen.
2. Click the **Add Tax Rate** button, to open the tax rate editor.
3. Enter the desired **Tax Rate** percentage.
4. Select the target tax jurisdiction, which defaults to **All Markets**. Tax rate jurisdictions are limited to the target markets selected in the Shopp Setup admin page.
5. For some countries, for example the USA, you can additionally choose a state/province.
6. Click the **Save Changes** button to complete the tax rate setting.
',

'Taxes help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'local-rates',
	'title'   => Shopp::__('Local Rates'),
	'content' => Shopp::_mx(

'### Local Rates

If you need to include additional taxes for a county or a municipal government tax that is added to the base rate, you can click the **Add Local Rates** button to enable supplemental local tax jurisdictions.

> Remember to use a meaningful local tax rate name, so that it can be easily identified by the customer.

Local rates can be specified by formatting a file in a XML, CSV or Tab-delimited format. The file format is simple and straightforward only requiring the name of the local tax jurisdiction and the additional tax rate. Simply create a file with the names and rates. Rates should be provided using the same numbering system as used in the Shopp tax settings screen where a whole number represents a whole percentage amount and decimal numbers are fractional percentages.

Below are example local tax jurisdiction file formats for the counties of the state of Nevada, USA (although Nevada does not use local tax rates).

#### XML Format

<pre>
<localtaxrates>
<taxrate name="Humboldt">0</taxrate>
<taxrate name="Washoe">0.875</taxrate>
<taxrate name="Lyon">0.25</taxrate>
<taxrate name="Storey">0.75</taxrate>
<taxrate name="Carson">0.625</taxrate>
<taxrate name="Douglas">0.25</taxrate>
<taxrate name="Pershing">0.25</taxrate>
<taxrate name="Churchill">0.75</taxrate>
<taxrate name="Mineral">0</taxrate>
<taxrate name="Lander">0.25</taxrate>
<taxrate name="Eureka">0</taxrate>
<taxrate name="Nye">0.25</taxrate>
<taxrate name="Elko">0</taxrate>
<taxrate name="White Pine">0.725</taxrate>
<taxrate name="Lincoln">0.25</taxrate>
<taxrate name="Clark">1.25</taxrate>
</localtaxrates>
</pre>

#### CSV Format

<pre>
Humboldt,0
Washoe,0.875
Lyon,0.25
Storey,0.75
Carson,0.625
Douglas,0.25
Pershing,0.25
Churchill,0.75
Mineral,0
Lander,0.25
Eureka,0
Nye,0.25
Elko,0
“White Pine”,0.725
Lincoln,0.25
Clark,1.25
</pre>

#### Tab-delimited Format

<pre>
Humboldt	0
Washoe	0.875
Lyon	0.25
Storey	0.75
Carson	0.625
Douglas	0.25
Pershing	0.25
Churchill	0.75
Mineral	0
Lander	0.25
Eureka	0
Nye	0.25
Elko	0
White Pine	0.725
Lincoln	0.25
Clark	1.25
</pre>

When you are finished creating the file, save it to your computer (somewhere you can easily locate).

#### To upload a formatted local rates file:

1. Create a formatted local rates file.
2. Login to your WordPress admin and navigate to the **Shopp** &rarr; **Setup** &rarr; **Taxes** screen.
3. Choose a tax rate and click the Edit link, or add a new tax rate by clicking the **Add Tax Rate** button to open the tax rate editor.
4. Click the **Add Local Rates** button.
5. Click the **Upload** button.
6. Select the formatted local rates file from your computer.
7. Click the **Save Changes** button to save the local rates.

During the checkout process, when a customer enters an address that matches a tax jurisdiction with local rates, a menu will be displayed for the customer to choose their specific tax jurisdiction from the labels you set.
',

'Context help tab')
) );

get_current_screen()->add_help_tab( array(
	'id'      => 'conditional-taxes',
	'title'   => Shopp::__('Conditional Taxes'),
	'content' => Shopp::_mx(

'### Conditional Taxes

In some cases you may need to support multiple tax rates that apply on specific products depending on conditions other than the geography of the customer. Shopp has support for conditional rules to apply different tax rates.

To add a conditional rule, click the plus button ￼ next to the country/state menus. Shopp tax rates can conditionally apply based on 4 criteria:

> Remember that it is possible to have two categories with the same name.

* The name of the product
* A name of a category the product is assigned to
* A name of a tag assigned to the product
* A customer type

You can specify multiple conditional rules and match **any** of the conditions specified or require matching **all** conditions. In the case of multiple tax rates applying at the same time, the most specific tax rate to match will apply.

To add additional conditions for the tax rate, click the plus button ￼ on the right of the last condition.
',

'Context help tab')
) );


get_current_screen()->set_help_sidebar( Shopp::_mx(

'**For more information:**

[Shopp User Guide](%s)

[Community Forums](%s)

[Shopp Support Help Desk](%s)

[Taxes & Discounts](%s)

',

// Translator context
'Taxes help tab (sidebar)',

// Sidebar URL replacements
ShoppSupport::DOCS . '/taxes',
ShoppSupport::FORUMS,
ShoppSupport::SUPPORT,
ShoppSupport::DOCS . '/taxes/taxes-discounts'

));

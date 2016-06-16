=== Webshop1310 ===
Tags: ecommerce, e-commerce, wordpress ecommerce, webshop1310, shopp, shop, shopping, cart, store, storefront, sales, sell, catalog, checkout, accounts, secure, variations, variants, reports, downloads, digital, downloadable, inventory, stock, shipping, taxes, shipped, addons, widgets, shortcodes
Requires at least: 3.5
Tested up to: 4.5.2
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A professional, high-performance e-commerce development plugin for WordPress.

== Description ==

Webshop1310 is based on Shopp, version 1.3.10 (http://www.shopplugin.net).
All Shopp add-ons will work with Webshop1310 the same way they do with Shopp.

== Installation ==

= Before You Install =

**There are a few things to do before setting up Webshop1310:**

* Ensure your website's server environment meets the Server Requirements (listed below)
* If you plan to use an onsite checkout process, you will need to buy and install an SSL certificate on your server. For details, read about [SSL Certificates](https://shopplugin.com/docs/payment-processing/ssl-setup-certificates/) in the Payment Processing section.

= Server Requirements =

To install and use Webshop1310, you will need to ensure that the following minimum technical requirements are met by your web hosting environment:

* WordPress 3.5 or higher
* MySQL 5 or higher
* PHP 5.2 or higher
* GD 2 library support (compiled into PHP)

Webshop1310 will not work properly (or at all) without these technologies.

If your web hosting does not provide these technologies you can try contacting your host’s technical support team and request them. Some hosts may charge additional recurring costs to enable these updates on your website.

To quickly and easily verify that your web hosting will run Webshop1310, you can use the [Shopp Requirements Check Plugin](https://shopplugin.net/extra/shopp-requirements-check/). It can be found as a free download from the Shopp Community Plugins directory.

**To install Webshop1310:**

1. Download the plugin file: `webshop1310.zip`
2. Unzip the `webshop1310.zip` file. This will create a `shopp/` directory, and add the Webshop1310 plugin files to this directory.
3. Using an FTP program, or the file transfer method your hosting provider recommends, upload the `shopp/` directory to the `(wordpress)/wp-content/plugins/` directory on your host where you have installed WordPress.
4. Login to your WordPress administration page and click the **Plugins** menu
5. Find **Webshop1310** in the list of available plugins and click **Activate**. The Webshop1310 menus will appear on the left.
6. Click on any of the Webshop1310 menus to start setting up your new Webshop1310 store!

At a minimum you should setup a **Base of Operations** location to setup Webshop1310 to work for your location. For details see our [Getting Started Guide](https://shopplugin.com/docs/getting-started/)

== Changelog ==
= 1.3.10.1 =
Reformatted __('text', 'Shopp') to Shopp::__('text')
Updated code to make the software PHP7 ready
Added SKU and ID field to Products Overview
Added ID field to Category Overview
Added SKU field to Products Report
Added excludecolumns function to ShoppReportFramework
Added exclude option to Category Widget
Adjusted code to remove PHP warnings/notices
Adjusted code to make shopp_setting('business_name') return blogname in case the business name is not set
Added toggle option to System->Advanced tab to enable/disable image server setting in .htaccess 
Inventory Manager respects WP screenoption 'per page'
Fixed code to restore Flash upload fro Downloadable products
Added SKU option for Cart Item discount 

== Frequently Asked Questions ==

= Do you have a product importer? =

People at [Shoppdeveloper.com](http://www.shoppdeveloper.com/shopp-bulk-product-importer/) created a plugin developed that will handle CSV imports.

= Do I need an SSL certificate? =

If you plan to take credit card numbers on your website, you **must** install and activate an SSL certificate to secure communication between your website visitors and your web server. You can find affordable SSL Certificates on the [Shopp Store](https://shopplugin.com/store/category/trust-services/). If you plan to take payments through an offsite payment system, such as PayPal Payments Standard, you do **not** need an SSL certificate. Even if you don't need one, an SSL certificate can boost your storefront's credibility and does provide protection for other sensitive customer information.

= Do I have to purchase an add-on to run my store? =

No! Webshop1310 includes two popular payment platforms with the free download: PayPal Payments Standard and 2Checkout along with Offline Payments and Test Mode payments systems. In addition there are 7 shipping calculators included for free. 20 starter template files and 2 stylesheet templates (compatible with all WordPress themes that follow theme development guidelines) are included for free. Everything you need to get your online storefront up and running is already included. No add-ons are necessary unless you are looking for specific integrations with preferred payment systems or real-time shipping rates.

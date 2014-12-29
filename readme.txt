=== Shopp ===
Contributors: jond, barry.hughes, clifgriffin, jdillick, lorenzocaum, chaoix
Donate link: http://shopp.me/shopp-donate
Tags: ecommerce, e-commerce, wordpress ecommerce, shopp, shop, shopping, cart, store, storefront, sales, sell, catalog, checkout, accounts, secure, variations, variants, reports, downloads, digital, downloadable, inventory, stock, shipping, taxes, shipped, addons, widgets, shortcodes
Requires at least: 3.5
Tested up to: 3.9
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A professional, high-performance e-commerce development plugin for WordPress.

== Description ==

Shopp adds an infinitely flexible WordPress ecommerce plugin and secure shopping cart that runs thousands of successful online storefronts.

Shopp was introduced in 2008 as a premium plugin by the team at Ingenesis Limited with 15-years of experience in the e-commerce industry. Today, Shopp is free and developed by a community of volunteers to provide a solid e-commerce toolkit with the best engineered framework for developers to build upon.

= Simplified, streamlined, sublime. =

Run sales, add new products, update inventory, ship orders. It’s all there, and it’s easy. Shopp consistently wins accolades for it’s WordPress-native administration tools giving it the most natural management experience you’ll find in a WordPress e-commerce solution. Get in, do your thing and get on with your real life.

E-commerce software shouldn’t tell you how to manage your business. You should be able tell your e-commerce software how you manage your business. Shopp lets you do just that. Create your own order processing labels and easily move orders through each step of the order fulfillment workflow either automatically or take control of it yourself.

Export orders to accounting systems like Intuit® QuickBooks®, or to spreadsheets like Microsoft® Excel®. Shopp remembers your last export date so its easy to do ongoing exports on a regular basis.

= Safe & Sound =

Your website will be in good hands (or bytes) running on Shopp. Our comprehensive security builds on the solid toolkit of WordPress to let the good guys in and keep bad guys out. We use all the tricks of the trade plus a few ideas of our own to protect your store and your customers.

Shopp is so secure it passes PCI vulnerability scans every day and we can prove it! [See the McAfee SECURE seal on our website](https://www.mcafeesecure.com/RatingVerify?ref=shopplugin.net). 

= Wired for Performance =

Engineered beyond the WordPress blogging architecture, Shopp goes to great lengths to deliver the best performance possible. 

Strategic use of custom database tables (for native decimal columns) improves load performance for catalogs of thousands, hundreds of thousands or even millions of products. A summary table is designed to build a transparent queriable cache of product pricing data to allow for simpler queries. Simpler queries means faster results. Faster results mean a snappy website!

A specialized record processor takes record data right from the database lookup and sets them up into a working data model in a single pass. This reduces CPU utilization and memory consumption freeing more resources to handle more visits. More visitors means more opportunity for making sales.

Did we mention speed is now a crucial SEO metric? Yeah, it is.

= Make it so. =

Shopp is built for developers because, let's face it, every ecommerce project is customized. A suite of APIs give you a powerful toolkit to create personalized ecommerce experiences for any business. The Shopp APIs offer a consistent, composable, unit test proven, battle hardened development platform so programmers stay in their zone of happiness. There are hundreds of theme functions and hundreds more WordPress hooks... enough to make a grown geek cry. See it for yourself and [explore the full API reference](https://shopplugin.com/api/).

= Not too much. Not too little. =

Going into battle with an ecommerce project often means theme wrangling. When it does, Shopp gives you a manageable number of starter templates to work with. You're not boxed in by shortcode-only page drop-ins and you don't have to cope with 100 different template parts to get your job done like other systems. Shopp starts with just a dozen template parts, a couple email templates and a couple stylesheets. Not too much, and not too little. Dynamic templates names allow you to create as many new template parts as you need for anything your job requires. Manageable **and** flexible.

= Your success is our success. =

With our support services, you get access to the professionals. Our team of experts is your team of experts. Get questions answered. Get help troubleshooting problems. Get the inside information to make the most from WordPress and your Shopp-powered ecommerce website. Just buy a support key or priority support service and relax knowing we've got your back. We also provide basic support once a week in our WordPress.org forum, but the best support experience is on our website from the [Shopp support help desk](https://shopplugin.com/support).

== Installation ==

= Before You Install =

**There are a few things to do before setting up Shopp:**

* Ensure your website's server environment meets the Server Requirements (listed below)
* If you plan to use an onsite checkout process, you will need to buy and install an SSL certificate on your server. For details, read about [SSL Certificates](https://shopplugin.com/docs/payment-processing/ssl-setup-certificates/) in the Payment Processing section.

= Server Requirements =

To install and use Shopp, you will need to ensure that the following minimum technical requirements are met by your web hosting environment:

* WordPress 3.5 or higher
* MySQL 5 or higher
* PHP 5.2 or higher
* GD 2 library support (compiled into PHP)

Shopp will not work properly (or at all) without these technologies.

If your web hosting does not provide these technologies you can try contacting your host’s technical support team and request them. Some hosts may charge additional recurring costs to enable these updates on your website.

To quickly and easily verify that your web hosting will run Shopp, you can use the [Shopp Requirements Check Plugin](https://shopplugin.net/extra/shopp-requirements-check/). It can be found as a free download from the Shopp Community Plugins directory.

**To install Shopp:**

1. Download the plugin file: `shopp.zip`
2. Unzip the `shopp.zip` file. This will create a `shopp/` directory and add the Shopp plugin files to this directory.
3. Using an FTP program, or the file transfer method your hosting provider recommends, upload the `shopp/` directory to the `(wordpress)/wp-content/plugins/` directory on your host where you have installed WordPress.
4. Login to your WordPress administration page and click the **Plugins** menu
5. Find **Shopp** in the list of available plugins and click **Activate**. The Shopp menus will appear on the left.
6. Click on any of the Shopp menus to start setting up your new Shopp store!

At a minimum you should setup a **Base of Operations** location to setup Shopp to work for your location. For details see our [Getting Started Guide](https://shopplugin.com/docs/getting-started/)

== Changelog ==

= 1.3 =

* Added reports with charting and exports
* Relabeled Promotions to Discounts
* Added icon font and other vector art
* Implemented PHP smart loading
* Improved checkout experience and templates
* Added schema.org support
* Refactored classes for better encapsulation and tidier interfaces
* Added direct URL support to storage engines
* Introduced support for `wp-content/shopp-addons/` directory
* Improved tax and discount calculations
* Added compound tax support
* Implemented totals register system
* Improved session handling
* Fixed slow queries
* Improved order management
* Redesigned unit tests 
* implemented continuous integration developement

= 1.2 =

* Converted products to a WordPress custom post type
* Converted categories and tags to WordPress taxonomies
* Added order event system
* Re-engineered APIs to be pluggable
* Introduced Developer API
* WordPress Menus support
* Replaced WordPress Page placeholders to Shopp virtual pages
* New notification email templates
* Added subscription support to PayPal Standard
* Added support for authorization-only transactions
* Added support for refund and void transactions
* Added shipment notices
* Redesigned shipping calculators
* Query speed optimizations
* DB record loader infrastructure
* Auto-generate plain text email alternative 
* Implemented unit tests

= 1.1 =

* Product addons
* Storefront Search Engine
* Tax system improvements
* Image & Script Servers
* Scriptabe email templates
* Cart Item discounts
* Passes PCI scans
* Offline Payments
* 2Checkout and Google Checkout included
* New Theme API tags

= 1.0 =

* Initial release
* Content templates
* Shortcodes
* Widgets
* Product Variants editor
* PayPal Standard


== Frequently Asked Questions ==

= Do you have a demo version? =

We have an [online demo](http://demo.shopplugin.net) you can play around with. You can also [login to the admin](http://demo.shopplugin.net/wp-login.php).

= Where can I see examples of Shopp in action? =

The [Showcase](https://shopplugin.net/blog/category/showcase/) has lots of live storefronts running Shopp.

= Do you have a product importer? =

The Shopp [Community Extras directory](https://shopplugin.com/extras/plugins/) includes a free download for a [Shopp Importer](https://shopplugin.com/extra/shopp-importer/) plugin developed by a third-party that will handle CSV imports.

= Do I need an SSL certificate? =

If you plan to take credit card numbers on your website, you **must** install and activate an SSL certificate to secure communication between your website visitors and your web server. You can find affordable SSL Certificates on the [Shopp Store](https://shopplugin.com/store/category/trust-services/). If you plan to take payments through an offsite payment system, such as PayPal Payments Standard, you do **not** need an SSL certificate. Even if you don't need one, an SSL certificate can boost your storefront's credibility and does provide protection for other sensitive customer information.

= I thought Shopp was a premium plugin? =

Shopp was a free-speech plugin sold exclusively through the Shopp website. Today, Shopp is a free (as in speech) and free (as in beer) WordPress plugin. Basic, limited support is provided once a week through the WordPress forum for Shopp. Full premium support from our team of experts is available through the official Shopp support help desk with the purchase of a Shopp support key from the [Shopp Store](https://shopplugin.com/store/).

= Do I have to purchase an add-on to run my store? =

No! Shopp includes two popular payment platforms with the free download: PayPal Payments Standard and 2Checkout along with Offsite Payments and Test Mode payments systems. In addition there are 7 shipping calculators included for free. 20 starter template files and 2 stylesheet templates (compatible with all WordPress themes that follow theme development guidelines) are included for free. Everything you need to get your online storefront up and running is already included. No add-ons are necessary unless you are looking for specific integrations with preferred payment systems or real-time shipping rates.

= What if I have an emergency and my store goes down? =

Incident support is available from our team by buying a priority support credit from the Shopp Store.
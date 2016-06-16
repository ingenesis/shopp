<?php $meta = ShoppDatabaseObject::tablename('meta'); ?>
DROP TABLE IF EXISTS <?php echo $meta; ?>;
CREATE TABLE <?php echo $meta; ?> (								-- Meta records table
	id bigint(20) unsigned NOT NULL auto_increment,				-- (8) Primary key
	parent bigint(20) unsigned NOT NULL default '0',			-- (8) ID of the parent record
	context varchar(16) NOT NULL default 'product',				-- (1-17) Type of object of the parent record
	type varchar(16) NOT NULL default 'meta',					-- (1-17) Type of the meta record
	name varchar(255) NOT NULL default '',						-- (1-256) Name of the meta record
	value longtext NOT NULL,									-- (4-4GB) Value blob of the meta record
	numeral decimal(16,6) NOT NULL default '0.0000',			-- (10) Numerically indexed representation of the 'value' column
	sortorder int(10) unsigned NOT NULL default '0',			-- (4) Sort order for the meta record
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Modification date
	PRIMARY KEY id (id),
	KEY lookup (name,parent,context,type)						-- Find by object record and meta type
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $summary = ShoppDatabaseObject::tablename('summary'); ?>
DROP TABLE IF EXISTS <?php echo $summary; ?>;
CREATE TABLE <?php echo $summary; ?> (							-- Summary table for product state records
	product bigint(20) unsigned NOT NULL default '0',			-- (8) Product ID (wp_posts ID)
	sold bigint(20) NOT NULL default '0',						-- (8) Total number sold of product
	grossed decimal(16,6) NOT NULL default '0.00',				-- (10) Gross sales
	maxprice decimal(16,6) NOT NULL default '0.00',				-- (10) Maximum price of all product's price records
	minprice decimal(16,6) NOT NULL default '0.00',				-- (10) Minimum price of all product's price records
	ranges char(200) NOT NULL default '',						-- (200) Set of minimum and maximum values
	taxed set('max price','min price','max saleprice','min saleprice'),	-- (2) Which pricetags are taxed
	lowstock enum('none','warning','critical','backorder') NOT NULL,	-- (1) Product low stock warning status
	stock int(10) NOT NULL default '0',							-- (4) Total stock of all product price records
	inventory enum('off','on') NOT NULL,						-- (1) Product has inventory flag
	featured enum('off','on') NOT NULL,							-- (1) Featured product setting
	variants enum('off','on') NOT NULL,							-- (1) Product has variants flag
	addons enum('off','on') NOT NULL,							-- (1) Product has addons flag
	sale enum('off','on') NOT NULL,								-- (1) Product is on sale flag
	freeship enum('off','on') NOT NULL,							-- (1) Product free shipping available
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Modification date
	PRIMARY KEY product (product),
	KEY bestselling (sold,product),								-- Catalog index by most sold
	KEY featured (featured,product),							-- Catalog index by featured setting
	KEY lowprice (minprice,product)								-- Catalog index by lowest price
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $price = ShoppDatabaseObject::tablename('price'); ?>
DROP TABLE IF EXISTS <?php echo $price; ?>;
CREATE TABLE <?php echo $price; ?> (							-- Price table
	id bigint(20) unsigned NOT NULL auto_increment,				-- (8) Primary key
	product bigint(20) unsigned NOT NULL default '0',			-- (8) Product ID (wp_posts ID)
	context enum('product','variation','addon') NOT NULL,		-- (1) Contextual usage of the price record
	type enum('Shipped','Virtual','Download','Donation','Subscription','Membership','N/A') NOT NULL,  -- (1) Variant/Add-on product type
	optionkey bigint(20) unsigned NOT NULL default '0',			-- (8) Variant option key (sort order agnostic key)
	label varchar(255) NOT NULL default '',						-- (1-256) Price record label
	sku varchar(100) NOT NULL default '',						-- (1-101) Assigned SKU (Stock Keeping Unit) code
	price decimal(16,6) NOT NULL default '0.00',				-- (10) Regular price
	saleprice decimal(16,6) NOT NULL default '0.00',			-- (10) Sale price
	promoprice decimal(16,6) NOT NULL default '0.00',			-- (10) Promo price (calculated promotion price)
	cost decimal(16,6) NOT NULL default '0.00',					-- (10) Actual cost/value of the priced product
	shipfee decimal(12,6) NOT NULL default '0',					-- (8) Shipping fee mark-up
	stock int(10) NOT NULL default '0',							-- (4) Number of product in inventory
	stocked int(10) NOT NULL default '0',						-- (4) Number of product last stocked
	inventory enum('off','on') NOT NULL,						-- (1) Flag for product with inventory tracking
	sale enum('off','on') NOT NULL,								-- (1) Flag to activate sale price
	shipping enum('on','off') NOT NULL,							-- (1) Flag to enable shipping for product
	tax enum('on','off') NOT NULL,								-- (1) Flag to enable tax calculations for product
	discounts varchar(255) NOT NULL default '',					-- (1-256) Promotion IDs that apply to the price
	sortorder int(10) unsigned NOT NULL default '0',			-- (4) Sort order for the price record
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Modification date
	PRIMARY KEY id (id),
	KEY product (product),										-- Lookup by product ID
	KEY context (context)										-- Lookup by context
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $index = ShoppDatabaseObject::tablename('index'); ?>
DROP TABLE IF EXISTS <?php echo $index; ?>;
CREATE TABLE <?php echo $index; ?> (							-- Search index table
	id bigint(20) unsigned NOT NULL auto_increment,				-- (8) Primary key
	product bigint(20) unsigned NOT NULL default '0',			-- (8) Product ID (wp_posts ID) of the product
	terms longtext NOT NULL,									-- (4-4GB) Terms text blob (indexed content to search) for recall
	factor tinyint(3) unsigned NOT NULL default '0',			-- (1) Search weighting factor to boost power of hits for precision
	type varchar(16) NOT NULL default 'description',			-- (1-17) Type of product content indexed
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Modification date
	PRIMARY KEY id (id),
	FULLTEXT search (terms)										-- Full-text index for search operations
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $asset = ShoppDatabaseObject::tablename('asset'); ?>
DROP TABLE IF EXISTS <?php echo $asset; ?>;
CREATE TABLE <?php echo $asset; ?> (							-- Binary storage repo
	id bigint(20) unsigned NOT NULL auto_increment,				-- (8) Primary key
	data longblob NOT NULL,										-- (4-4GB)Storage blob
	PRIMARY KEY id (id)
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $shopping = ShoppDatabaseObject::tablename('shopping'); ?>
DROP TABLE IF EXISTS <?php echo $shopping; ?>;
CREATE TABLE <?php echo $shopping; ?> (							-- Active shopping sessions
	session varchar(32) NOT NULL,								-- (1-33) PHP Session ID
	customer bigint(20) unsigned NOT NULL default '0',			-- (8) Related customer record
	ip varchar(15) NOT NULL default '0.0.0.0',					-- (1-15) IP of client
	data longblob NOT NULL,										-- (4-4GB) Session data blob
	stash tinyint(3) unsigned NOT NULL default '0',				-- (1) Flag to store a session
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- (10) Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (10) Modification date
	PRIMARY KEY session (session),
	KEY customer (customer)										-- Lookup by customer
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $customer = ShoppDatabaseObject::tablename('customer'); ?>
DROP TABLE IF EXISTS <?php echo $customer; ?>;
CREATE TABLE <?php echo $customer; ?> (							-- Customer account records
	id bigint(20) unsigned NOT NULL auto_increment,				-- (8) Primary key
	wpuser bigint(20) unsigned NOT NULL default '0',			-- (8) Related WP user login (wp_users ID)
	password varchar(64) NOT NULL default '',					-- (1-65) Hashed password
	firstname varchar(32) NOT NULL default '',					-- (1-33) First name
	lastname varchar(32) NOT NULL default '',					-- (1-33) Last name
	email varchar(96) NOT NULL default '',						-- (1-97) Email address
	phone varchar(24) NOT NULL default '',						-- (1-25) Phone number
	company varchar(100) NOT NULL default '',					-- (1-101) Company name
	marketing enum('yes','no') NOT NULL default 'no',			-- (1) Opt-in marketing flag
	activation varchar(20) NOT NULL default '',					-- (8) Activation key
	type varchar(100) NOT NULL default '',						-- (1-101) Customer type
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Modification date
	PRIMARY KEY id (id),
	KEY wordpress (wpuser),										-- Lookup by wp_users ID
	KEY type (type)												-- Lookup by customer type
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $address = ShoppDatabaseObject::tablename('address'); ?>
DROP TABLE IF EXISTS <?php echo $address; ?>;
CREATE TABLE <?php echo $address; ?> (							-- Customer physical addresses
	id bigint(20) unsigned NOT NULL auto_increment,				-- (8) Primary key
	customer bigint(20) unsigned NOT NULL default '0',			-- (8) Customer record ID
	type enum('billing','shipping') NOT NULL default 'billing',	-- (1) Type of address record
	name varchar(100) NOT NULL default '',						-- (1-101) Addressee name (name of address, or contact name)
	address varchar(100) NOT NULL default '',					-- (1-101) Street address
	xaddress varchar(100) NOT NULL default '',					-- (1-101) Street address, line 2
	city varchar(100) NOT NULL default '',						-- (1-101) City
	state varchar(100) NOT NULL default '',						-- (1-101) State/province
	country varchar(2) NOT NULL default '',						-- (1-2) Country (ISO code)
	postcode varchar(10) NOT NULL default '',					-- (1-11) Postal code (or US ZIP code)
	geocode varchar(16) NOT NULL default '',					-- (1-17) Geocode data
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- (8) Modification date
	PRIMARY KEY id (id),
	KEY ref (customer,type)										-- Lookup by customer and address record type
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $purchase = ShoppDatabaseObject::tablename('purchase'); ?>
DROP TABLE IF EXISTS <?php echo $purchase; ?>;
CREATE TABLE <?php echo $purchase; ?> (							-- Purchase log
	id bigint(20) unsigned NOT NULL auto_increment,				-- Primary key
	customer bigint(20) unsigned NOT NULL default '0',			-- Customer account ID
	shipping bigint(20) unsigned NOT NULL default '0',			-- Shipping address ID
	billing bigint(20) unsigned NOT NULL default '0',			-- Billing address ID
	currency bigint(20) unsigned NOT NULL default '0',			-- 
	ip varchar(15) NOT NULL default '0.0.0.0',					-- IP of the buyer
	firstname varchar(32) NOT NULL default '',					-- First name of buyer
	lastname varchar(32) NOT NULL default '',					-- Last name of buyer
	email varchar(96) NOT NULL default '',						-- Email address
	phone varchar(24) NOT NULL default '',						-- Phone
	company varchar(100) NOT NULL default '',					-- Company Name
	card varchar(4) NOT NULL default '',						-- Partial payment card PAN storage
	cardtype varchar(32) NOT NULL default '',					-- Payment card type 
	cardexpires date NOT NULL default '0000-00-00',				-- Payment card expiration date
	cardholder varchar(96) NOT NULL default '',					-- Payment card holder name
	address varchar(100) NOT NULL default '',					-- Street address
	xaddress varchar(100) NOT NULL default '',					-- Street address, line 2
	city varchar(100) NOT NULL default '',						-- City
	state varchar(100) NOT NULL default '',						-- State
	country varchar(2) NOT NULL default '',						-- Country
	postcode varchar(10) NOT NULL default '',					-- Post code
	locale varchar(100) NOT NULL default '',					-- Tax locale
	shipname varchar(100) NOT NULL default '',					-- Ship to name
	shipaddress varchar(100) NOT NULL default '',				-- Shipping address
	shipxaddress varchar(100) NOT NULL default '',				-- Shipping address, line 2
	shipcity varchar(100) NOT NULL default '',					-- Shipping address city
	shipstate varchar(100) NOT NULL default '',					-- Shipping address state
	shipcountry varchar(2) NOT NULL default '',					-- Shipping address country
	shippostcode varchar(10) NOT NULL default '',				-- Shipping address postal code
	geocode varchar(16) NOT NULL default '',					-- Geocode for the shipping destination
	promos varchar(255) NOT NULL default '',					-- List of promotions that apply
	subtotal decimal(16,6) NOT NULL default '0.00',				-- Subtotal of the order
	freight decimal(16,6) NOT NULL default '0.00',				-- Freight cost for shipping
	tax decimal(16,6) NOT NULL default '0.00',					-- Taxed amount
	total decimal(16,6) NOT NULL default '0.00',				-- Grand total of the order
	discount decimal(16,6) NOT NULL default '0.00',				-- Amount discounted from the order
	fees decimal(16,6) NOT NULL default '0.00',					-- Order fees assessed from transaction
	taxing enum('exclusive','inclusive') default 'exclusive',	-- Tax application (exclusive,inclusive)
	txnid varchar(64) NOT NULL default '',						-- Transaction ID
	txnstatus varchar(64) NOT NULL default '',					-- Transaction Status
	gateway varchar(64) NOT NULL default '',					-- Payment gateway used
	paymethod varchar(100) NOT NULL default '',					-- Payment option selected
	shipmethod varchar(100) NOT NULL default '',				-- Shipping method selected
	shipoption varchar(100) NOT NULL default '',				-- Shipping method label
	status tinyint(3) unsigned NOT NULL default '0',			-- Order fulfillment status
	data longtext NOT NULL,										-- Order data blob
	secured text NOT NULL,										-- Secured data blob
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- Modification date
	PRIMARY KEY id (id),
	KEY customer (customer)										-- Indexed lookup by customer
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $purchased = ShoppDatabaseObject::tablename('purchased'); ?>
DROP TABLE IF EXISTS <?php echo $purchased; ?>;
CREATE TABLE <?php echo $purchased; ?> (						-- Line items purchased in an order
	id bigint(20) unsigned NOT NULL auto_increment,				-- Primary key
	purchase bigint(20) unsigned NOT NULL default '0',			-- Parent order ID (shopp_purchase id)
	product bigint(20) unsigned NOT NULL default '0',			-- Source product id (wp_posts ID)
	price bigint(20) unsigned NOT NULL default '0',				-- Source price record id (shopp_price id)
	download bigint(20) unsigned NOT NULL default '0',			-- Download ID (shopp_meta 'download' record id)
	dkey varchar(255) NOT NULL default '',						-- Download key
	name varchar(255) NOT NULL default '',						-- Purchased item name
	description text NOT NULL,									-- Purchased item description
	optionlabel varchar(255) NOT NULL default '',				-- Purchased option label
	type varchar(100) NOT NULL default '',						-- Purchased item type
	sku varchar(100) NOT NULL default '',						-- Purchased item SKU
	quantity int(10) unsigned NOT NULL default '0',				-- Quantity purchased
	downloads int(10) unsigned NOT NULL default '0',			-- Number of downloads against this purchased item
	unitprice decimal(16,6) NOT NULL default '0.00',			-- Unit price of purchased item
	unittax decimal(16,6) NOT NULL default '0.00',				-- Unit taxes assessed for the purchased item
	unitdiscount decimal(16,6) NOT NULL default '0.00',			-- Unit discount applied
	discounts decimal(16,6) NOT NULL default '0.00',			-- Total line item discounts applied
	shipping decimal(16,6) NOT NULL default '0.00',				-- Shipping costs specific to the purchased item
	total decimal(16,6) NOT NULL default '0.00',				-- Total cost of the line item
	addons enum('yes','no') NOT NULL default 'no',				-- Purchased item has add-ons
	variation text NOT NULL,									-- Product variant data
	data longtext NOT NULL,										-- Product custom data
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- Modification date
	PRIMARY KEY id (id),
	KEY purchase (purchase),									-- Lookup by purchase record id
	KEY price (price),											-- Lookup by price record id
	KEY product (product),										-- Lookup by product record id
	KEY dkey (dkey(8))											-- Download key lookup
) ENGINE=MyIsAM DEFAULT CHARSET=utf8;

<?php $promo = ShoppDatabaseObject::tablename('promo'); ?>
DROP TABLE IF EXISTS <?php echo $promo; ?>;
CREATE TABLE <?php echo $promo; ?> (							-- Promotions
	id bigint(20) unsigned NOT NULL auto_increment,				-- Primary key
	name varchar(255) NOT NULL default '',						-- Name of the promotion
	status enum('disabled','enabled') default 'disabled',		-- Status of the promotion
	type enum('Percentage Off','Amount Off','Free Shipping','Buy X Get Y Free') default 'Percentage Off',	-- Type of promotion	
	target enum('Catalog','Cart','Cart Item') default 'Catalog',	-- Target type the promotion applies to
	discount decimal(16,6) NOT NULL default '0.00',				-- Discount amount
	buyqty int(10) NOT NULL default '0',						-- Buy X Get Y quantity required to buy
	getqty int(10) NOT NULL default '0',						-- Buy X Get Y quantity given
	uses int(10) NOT NULL default '0',							-- Usage counter (number of times the promotion has been used)
	search enum('all','any') default 'all',						-- Boolean conditional join operation
	code varchar(255) NOT NULL default '',						-- Promotion code
	rules text NOT NULL,										-- Rules blob
	starts datetime NOT NULL default '0000-00-00 00:00:00',		-- Start date of promotion availability
	ends datetime NOT NULL default '0000-00-00 00:00:00',		-- End date of promotion availability
	created datetime NOT NULL default '0000-00-00 00:00:00',	-- Creation date
	modified datetime NOT NULL default '0000-00-00 00:00:00',	-- Modification date
	PRIMARY KEY id (id),
	KEY catalog (status,target)									-- Catalog lookup by status and target
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
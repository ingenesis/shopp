<?php
/**
 * Loader.php
 *
 * Controller for lazy loading application code
 *
 * @author Jonathan Davis
 * @version 1.3
 * @copyright Ingenesis Limited, March 2013
 * @package shopp
 * @subpackage autoload
 **/

( defined( 'WPINC' ) || defined('SHORTINIT') ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppLoader {

	private static $instance;				// Singleton instance

	protected static $classmap = array();	// A map of class names to files
	protected static $basepath = '';		// Tracks the base path of files in the classmap

	private static $excludes = array();

	/**
	 * Setup the loader and register the autoloader
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $path The base path for this loader to use
	 * @return void
	 **/
	private function __construct () {
		spl_autoload_register(array($this,'load'));
	}

	static public function &instance () {
		if ( ! self::$instance instanceof self )
			self::$instance = new self;
		return self::$instance;
	}

	static public function basepath ( $path ) {
		self::$basepath = self::sanitize($path);
	}

	static private function sanitize ( $path ) {
		return str_replace('\\', '/', realpath($path));
	}

	/**
	 * Imports a new class map to the loader without overriding existing entries
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param array $new Associative array with class names as keys and file paths as values
	 * @param string $basepath The base path to use (if any). Use '' to use full paths, '.' to use paths relative to the base path of the loader instance, or pass a directory path to use as the base path
	 * @return boolean True if successful
	 **/
	public function map ( $new = array(), $basepath = '.' ) {
		if ( empty($new) ) return false;

		if ( '.' == $basepath ) $basepath = self::$basepath;

		$fullpath = create_function('$f','return "' . $basepath . '" . $f;');
		if ( ! empty($basepath) ) $new = array_map($fullpath, $new);

		self::$classmap = array_merge($new,self::$classmap);
		return true;
	}

	/**
	 * Adds a single new entry to map a class to a file for loading
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $classname The name of the class to add
	 * @param string $filepath The full path to the file, or begin path with . to use the base path of the loader instance
	 * @return boolean True if successful
	 **/
	public function add ( $classname, $filepath ) {
		$class = strtolower($classname);

		if ( empty($class) || isset(self::$classmap[ $class ]) ) return false;
		if ( empty($filepath) || ! is_readable($filepath) ) return false;

		self::$classmap[ $class ] = $filepath;
		return true;
	}

	/**
	 * Autoload handler
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $class The name of the class to load
	 * @return boolean True if successful, false otherwise
	 **/
	public function load ( $class ) {

		if ( $this->excluded($class) ) return true;
		elseif ( $this->classmap($class) ) return true;
		elseif ( defined('SHOPP_DEBUG') && SHOPP_DEBUG && $this->scanner($class) ) return true;

		return false;
	}

	/**
	 * Require a file based on the class map
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $class The name of the class to load
	 * @return boolean True if successful, false otherwise
	 **/
	protected function classmap ( $class ) {
		$classname = strtolower($class);
		if ( isset(self::$classmap[ $classname ]) )
			return (1 == require self::$classmap[ $classname ]);
		return false;
	}

	protected function excluded ( $class ) {
		$classname = strtolower($class);
		// Ignore WordPress classes prefixed with wp_, and anything in the excludes list
		return ( 'wp_' == substr($classname,0,2) ) || in_array($classname,self::$excludes);
	}

	/**
	 * Recursively scan files in the base path to add to the classmap
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $class The name of the class to load
	 * @param string $path (optional) The path to scan. Uses the basepath of the loader by default.
	 * @return boolean True if succesful, false otherwise
	 **/
	protected function scanner ( $class, $path = '' ) {

		if ( empty($path) ) $path = self::$basepath;
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator( $path ), RecursiveIteratorIterator::SELF_FIRST);
		foreach( $objects as $name => $object )
			$this->scanfile( $name, $path );

		if ( $this->classmap($class) ) return true;
		return false;
	}

	/**
	 * Scans a file for class, interface and trait declarations and adds them to the class map
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @param string $filename The full path to the file to scan
	 * @return boolean True if successful, false otherwise
	 **/
	protected function scanfile ( $filename ) {
		if ( false === strpos($filename,'.php') ) return;

		$comment = false; // Track comment blocks
		$file = fopen($filename, 'r');
		while ( false !== ($line = fgets($file,200)) ) {
			// Skip comment blocks;
			if ( $comment || false !== strpos($line,'/*') ) {
				$comment = true;
				if (false !== strpos($line,'*/')) $comment = false;
				continue;
			}

			// Find class/interface/trait definitions
			if ( false === ($token = strpos($line,'class '))
				 && false === ($token = strpos($line,'interface '))
				 && false === ($token = strpos($line,'trait '))
			) continue;

			// Skip inline comments and strings that start before the token
			if ( false !== ($comments = strpos($line,'//')) && $comments < $token ) continue;
			if ( false !== ($string = strpos($line,'"')) && $string < $token ) continue;
			if ( false !== ($string = strpos($line,"'")) && $string < $token ) continue;

			// Skip tokens that do not start the line or have whitespace preceding it
			if ( 0 != $token && ! in_array( substr($line,$token-1,1), array(' ',"\t","\n","\r")) ) continue;

			list($token,$classname) = explode( ' ', substr($line,$token) );
			$class = strtolower($classname);

			// Skip classes that already exist in the class map
			if ( isset(self::$classmap[ $class ]) ) continue;

			trigger_error("ShoppLoader discovered a missing class declared for $classname in $filename.",E_USER_NOTICE);

			$this->add($class,$filename);

		} // endwhile;

		fclose($file);
	}

	static function find_wpload () {

		$configfile = 'wp-config.php';
		$loadfile = 'wp-load.php';
		$wp_abspath = false;

		$syspath = explode('/', $_SERVER['SCRIPT_FILENAME']);
		$uripath = explode('/', $_SERVER['SCRIPT_NAME']);
		$rootpath = array_diff($syspath, $uripath);
		$root = '/' . join('/', $rootpath);

		$filepath = dirname( ! empty($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : __FILE__ );

		if ( file_exists(self::sanitize($root) . '/' . $loadfile))
			$wp_abspath = $root;

		if ( isset($_SERVER['SHOPP_WP_ABSPATH'])
			&& file_exists(self::sanitize($_SERVER['SHOPP_WP_ABSPATH']) . '/' . $configfile) ) {
			// SetEnv SHOPP_WPCONFIG_PATH /path/to/wpconfig
			// and SHOPP_ABSPATH used on webserver site config
			$wp_abspath = $_SERVER['SHOPP_WP_ABSPATH'];

		} elseif ( strpos($filepath, $root) !== false ) {
			// Shopp directory has DOCUMENT_ROOT ancenstor, find wp-config.php
			$fullpath = explode ('/', self::sanitize($filepath) );
			while ( ! $wp_abspath && null !== array_pop($fullpath) ) {
				if (file_exists( sanitize_path(join('/', $fullpath)) . '/' . $loadfile ))
					$wp_abspath = join('/', $fullpath);
			}

		} elseif ( file_exists(self::sanitize($root).'/'.$loadfile) ) {
			$wp_abspath = $root; // WordPress install in DOCUMENT_ROOT
		} elseif ( file_exists(self::sanitize(dirname($root)) . '/' . $loadfile) ) {
			$wp_abspath = dirname($root); // wp-config up one directory from DOCUMENT_ROOT
	    } else {
	        /* Last chance, do or die */
			$filepath = self::sanitize($filepath);
	        if ( false !== ($pos = strpos($filepath, 'wp-content/plugins')) )
	            $wp_abspath = substr($filepath, 0, --$pos);
	    }

		$wp_load_file = self::sanitize($wp_abspath) . "/$loadfile";

		if ( $wp_load_file !== false ) return $wp_load_file;
		return false;

	}

	/**
	 * Indicates if Shopp is being activated. This can be useful for systems such as the Settings object
	 * which will wish to avoid database operations before the schema is available, etc.
	 *
	 * @return bool
	 */
	public static function is_activating () {
		global $action, $plugin;
		return ( ($action === 'activate' || $action === 'error_scrape') && $plugin === SHOPP_PLUGINFILE);
	}
}

function &ShoppLoader () {
	return ShoppLoader::instance();
}

ShoppLoader()->basepath( dirname(dirname(__FILE__)) );
ShoppLoader()->map(array(
	'account' => '/flow/Account.php',
	'address' => '/model/Address.php',
	'admincontroller' => '/flow/Flow.php',
	'alsoboughtproducts' => '/model/Collection.php',
	'amountvoidedevent' => '/model/Events.php',
	'amountvoidedeventrenderer' => '/ui/orders/events.php',
	'authedorderevent' => '/model/Events.php',
	'authedordereventrenderer' => '/ui/orders/events.php',
	'authfailorderevent' => '/model/Events.php',
	'authfailordereventrenderer' => '/ui/orders/events.php',
	'authorderevent' => '/model/Events.php',
	'authordereventrenderer' => '/ui/orders/events.php',
	'autoobjectframework' => '/library/Framework.php',
	'bestsellerproducts' => '/model/Collection.php',
	'billingaddress' => '/model/Address.php',
	'booleanparser' => '/model/Search.php',
	'capturedorderevent' => '/model/Events.php',
	'capturedordereventrenderer' => '/ui/orders/events.php',
	'capturefailorderevent' => '/model/Events.php',
	'capturefailordereventrenderer' => '/ui/orders/events.php',
	'captureorderevent' => '/model/Events.php',
	'captureordereventrenderer' => '/ui/orders/events.php',
	'cart' => '/model/Cart.php',
	'cartdiscounts' => '/model/Cart.php',
	'cartpromotions' => '/model/Cart.php',
	'cartshipping' => '/model/Cart.php',
	'carttax' => '/model/Cart.php',
	'carttotals' => '/model/Cart.php',
	'shoppcatalog' => '/library/Catalog.php',
	'catalogproducts' => '/model/Collection.php',
	'categorize' => '/flow/Categorize.php',
	'categoryimage' => '/model/Asset.php',
	'contentindex' => '/model/Search.php',
	'contentparser' => '/model/Search.php',
	'creditordereventmessage' => '/model/Events.php',
	'customer' => '/model/Customer.php',
	'customeraccountpage' => '/model/Customer.php',
	'customerscsvexport' => '/model/Customer.php',
	'customersexport' => '/model/Customer.php',
	'customersreport' => '/ui/reports/customers.php',
	'customerstabexport' => '/model/Customer.php',
	'customersxlsexport' => '/model/Customer.php',
	'databaseobject' => '/library/DB.php',
	'db' => '/library/DB.php',
	'debitordereventmessage' => '/model/Events.php',
	'decryptorderevent' => '/model/Events.php',
	'decryptordereventrenderer' => '/ui/orders/events.php',
	'discountsreport' => '/ui/reports/discounts.php',
	'downloadasset' => '/model/Asset.php',
	'downloadorderevent' => '/model/Events.php',
	'downloadordereventrenderer' => '/ui/orders/events.php',
	'emogrifier' => '/library/Email.php',
	'failureordereventrender' => '/ui/orders/events.php',
	'featuredproducts' => '/model/Collection.php',
	'fileasset' => '/model/Asset.php',
	'flowcontroller' => '/flow/Flow.php',
	'freeorder' => '/model/Gateway.php',
	'gatewayframework' => '/model/Gateway.php',
	'gatewaymodule' => '/model/Gateway.php',
	'gatewaymodules' => '/model/Gateway.php',
	'gatewaysettingsui' => '/model/Gateway.php',
	'imageasset' => '/model/Asset.php',
	'imageprocessor' => '/model/Image.php',
	'imageserver' => '/image.php',
	'imagesetting' => '/model/Asset.php',
	'imagesettings' => '/model/Asset.php',
	'indexproduct' => '/model/Search.php',
	'inventoryreport' => '/ui/reports/inventory.php',
	'invoicedorderevent' => '/model/Events.php',
	'invoicedordereventrenderer' => '/ui/orders/events.php',
	'item' => '/model/Item.php',
	'listframework' => '/library/Framework.php',
	'locationsreport' => '/ui/reports/locations.php',
	'lookup' => '/library/Lookup.php',
	'markdownr' => '/library/Markdownr.php',
	'markdownrblock' => '/library/Markdownr.php',
	'markdownrparagraph' => '/library/Markdownr.php',
	'markdownrhtml' => '/library/Markdownr.php',
	'markdownrheader' => '/library/Markdownr.php',
	'markdownrlist' => '/library/Markdownr.php',
	'markdownrrule' => '/library/Markdownr.php',
	'markdownrblockquote' => '/library/Markdownr.php',
	'markdownrcode' => '/library/Markdownr.php',
	'markdownrfencedcode' => '/library/Markdownr.php',
	'markdownrinline' => '/library/Markdownr.php',
	'markdownrem' => '/library/Markdownr.php',
	'markdownrstrong' => '/library/Markdownr.php',
	'markdownrhardbreaks' => '/library/Markdownr.php',
	'markdownrinlinecode' => '/library/Markdownr.php',
	'markdownrinlinelink' => '/library/Markdownr.php',
	'markdownrautolink' => '/library/Markdownr.php',
	'markdownremaillink' => '/library/Markdownr.php',
	'memberaccess' => '/model/Membership.php',
	'membercontent' => '/model/Membership.php',
	'memberplan' => '/model/Membership.php',
	'members' => '/flow/Members.php',
	'membership' => '/model/Membership.php',
	'memberstage' => '/model/Membership.php',
	'metaobject' => '/model/Meta.php',
	'metasetobject' => '/model/Meta.php',
	'mixproducts' => '/model/Collection.php',
	'modulefile' => '/library/Modules.php',
	'moduleloader' => '/library/Modules.php',
	'modulesettingsui' => '/library/Modules.php',
	'newproducts' => '/model/Collection.php',
	'noteorderevent' => '/model/Events.php',
	'noteordereventrenderer' => '/ui/orders/events.php',
	'noticeorderevent' => '/model/Events.php',
	'noticeordereventrenderer' => '/ui/orders/events.php',
	'nusoap_base' => '/library/SOAP.php',
	'nusoap_client' => '/library/SOAP.php',
	'nusoap_fault' => '/library/SOAP.php',
	'nusoap_parser' => '/library/SOAP.php',
	'nusoap_xmlschema' => '/library/SOAP.php',
	'objectmeta' => '/model/Meta.php',
	'onsaleproducts' => '/model/Collection.php',
	'orderamountaccountcredit' => '/model/Totals.php',
	'orderamountcartitem' => '/model/Totals.php',
	'orderamountcartitemquantity' => '/model/Totals.php',
	'orderamountcredit' => '/model/Totals.php',
	'orderamountdebit' => '/model/Totals.php',
	'orderamountdiscount' => '/model/Totals.php',
	'orderamountfee' => '/model/Totals.php',
	'orderamountgiftcard' => '/model/Totals.php',
	'orderamountgiftcertificate' => '/model/Totals.php',
	'orderamountitem' => '/model/Totals.php',
	'orderamountitemdiscounts' => '/model/Totals.php',
	'orderamountitemquantity' => '/model/Totals.php',
	'orderamountitemtax' => '/model/Totals.php',
	'orderamountshipping' => '/model/Totals.php',
	'orderamounttax' => '/model/Totals.php',
	'orderevent' => '/model/Events.php',
	'ordereventmessage' => '/model/Events.php',
	'ordereventrenderer' => '/ui/orders/events.php',
	'ordertotal' => '/model/Totals.php',
	'ordertotalamount' => '/model/Totals.php',
	'ordertotalregisters' => '/model/Totals.php',
	'ordertotals' => '/model/Totals.php',
	'paycard' => '/model/Gateway.php',
	'porterstemmer' => '/model/Search.php',
	'postcodemapping' => '/model/Address.php',
	'price' => '/model/Price.php',
	'product' => '/model/Product.php',
	'productcategory' => '/model/Collection.php',
	'productcategoryfacet' => '/model/Collection.php',
	'productcategoryfacetfilter' => '/model/Collection.php',
	'productcollection' => '/model/Collection.php',
	'productdownload' => '/model/Asset.php',
	'productimage' => '/model/Asset.php',
	'productsreport' => '/ui/reports/products.php',
	'productsummary' => '/model/Product.php',
	'producttag' => '/model/Collection.php',
	'producttaxonomy' => '/model/Collection.php',
	'promoproducts' => '/model/Collection.php',
	'promote' => '/flow/Promote.php',
	'promotion' => '/model/Promotion.php',
	'purchase' => '/model/Purchase.php',
	'purchased' => '/model/Purchased.php',
	'purchaseorderevent' => '/model/Events.php',
	'purchasescsvexport' => '/model/Purchase.php',
	'purchasesexport' => '/model/Purchase.php',
	'purchasesiifexport' => '/model/Purchase.php',
	'purchasestabexport' => '/model/Purchase.php',
	'purchasestockallocation' => '/model/Purchase.php',
	'purchasesxlsexport' => '/model/Purchase.php',
	'randomproducts' => '/model/Collection.php',
	'rebillorderevent' => '/model/Events.php',
	'recapturedorderevent' => '/model/Events.php',
	'recapturefailorderevent' => '/model/Events.php',
	'refundedorderevent' => '/model/Events.php',
	'refundedordereventrenderer' => '/ui/orders/events.php',
	'refundfailorderevent' => '/model/Events.php',
	'refundfailordereventrenderer' => '/ui/orders/events.php',
	'refundorderevent' => '/model/Events.php',
	'refundordereventrenderer' => '/ui/orders/events.php',
	'relatedproducts' => '/model/Collection.php',
	'report' => '/flow/Report.php',
	'revieworderevent' => '/model/Events.php',
	'reviewordereventrenderer' => '/ui/orders/events.php',
	'saleorderevent' => '/model/Events.php',
	'saleordereventrenderer' => '/ui/orders/events.php',
	'salesreport' => '/ui/reports/sales.php',
	'sdb' => '/library/DB.php',
	'searchparser' => '/model/Search.php',
	'searchresults' => '/model/Collection.php',
	'searchtextfilters' => '/model/Search.php',
	'service' => '/flow/Service.php',
	'sessionobject' => '/library/DB.php',
	'shoppsettings' => '/model/Settings.php',
	'setup' => '/flow/Setup.php',
	'shippedorderevent' => '/model/Events.php',
	'shippedordereventrenderer' => '/ui/orders/events.php',
	'shippingaddress' => '/model/Address.php',
	'shippingcarrier' => '/model/Shipping.php',
	'shippingframework' => '/model/Shipping.php',
	'shippingmodule' => '/model/Shipping.php',
	'shippingmodules' => '/model/Shipping.php',
	'shippingoption' => '/model/Cart.php',
	'shippingpackage' => '/model/Shipping.php',
	'shippingpackageinterface' => '/model/Shipping.php',
	'shippingpackageitem' => '/model/Shipping.php',
	'shippingpackager' => '/model/Shipping.php',
	'shippingpackaginginterface' => '/model/Shipping.php',
	'shippingreport' => '/ui/reports/shipping.php',
	'shippingsettingsui' => '/model/Shipping.php',
	'shoppaccountdashboardpage' => '/flow/Pages.php',
	'shoppaccountpage' => '/flow/Pages.php',
	'shoppaccountwidget' => '/ui/widgets/account.php',
	'shoppaddon_upgrader' => '/flow/Install.php',
	'shoppadmin' => '/flow/Admin.php',
	'shoppadminlisttable' => '/flow/Admin.php',
	'shoppadminpage' => '/flow/Admin.php',
	'shoppadmindashboard' => '/flow/Dashboard.php',
	'shoppajax' => '/flow/Ajax.php',
	'shoppapi' => '/library/API.php',
	'shoppapifile' => '/library/API.php',
	'shoppapimodules' => '/library/API.php',
	'shoppcart' => '/model/Cart.php',
	'shoppcartitem' => '/model/Item.php',
	'shoppcartpage' => '/flow/Pages.php',
	'shoppcartwidget' => '/ui/widgets/cart.php',
	'shoppcatalogpage' => '/flow/Pages.php',
	'shoppcategorieswidget' => '/ui/widgets/categories.php',
	'shoppcategorysectionwidget' => '/ui/widgets/section.php',
	'shoppcheckout' => '/flow/Checkout.php',
	'shoppcheckoutpage' => '/flow/Pages.php',
	'shoppcollectionpage' => '/flow/Pages.php',
	'shoppconfirmpage' => '/flow/Pages.php',
	'shoppcore_upgrader' => '/flow/Install.php',
	'shoppdeveloperapi' => '/library/API.php',
	'shoppdiscountrule' => '/model/Discounts.php',
	'shoppdiscounts' => '/model/Discounts.php',
	'shoppemaildefaultfilters' => '/library/Email.php',
	'shoppemailfilters' => '/library/Email.php',
	'shopperror' => '/library/Error.php',
	'shopperrorlogging' => '/library/Error.php',
	'shopperrornotification' => '/library/Error.php',
	'shopperrors' => '/library/Error.php',
	'shopperrorstorefrontnotices' => '/library/Error.php',
	'shoppfacetedmenuwidget' => '/ui/widgets/facetedmenu.php',
	'shoppflow' => '/flow/Flow.php',
	'shoppformvalidation' => '/library/Validation.php',
	'shopping' => '/model/Shopping.php',
	'shoppingobject' => '/model/Shopping.php',
	'shoppinstallation' => '/flow/Install.php',
	'shoppitemtax' => '/model/Tax.php',
	'shoppcore' => '/library/Core.php',
	'shopploader' => '/library/Loader.php',
	'shopplogin' => '/flow/Login.php',
	'shoppmaintenancepage' => '/flow/Pages.php',
	'shopporder' => '/flow/Order.php',
	'shopporderdiscount' => '/model/Discounts.php',
	'shopporderpromo' => '/model/Discounts.php',
	'shopppage' => '/flow/Pages.php',
	'shopppages' => '/flow/Pages.php',
	'shopppaymentoption' => '/model/Payments.php',
	'shopppayments' => '/model/Payments.php',
	'shoppproductpage' => '/flow/Pages.php',
	'shoppproductwidget' => '/ui/widgets/product.php',
	'shopppromotions' => '/model/Discounts.php',
	'shoppregistration' => '/flow/Registration.php',
	'shoppremoteapifile' => '/library/API.php',
	'shoppremoteapimodules' => '/library/API.php',
	'shoppremoteapiserver' => '/remote.php',
	'shoppremoteapiserviceframework' => '/flow/Remote.php',
	'shoppreport' => '/flow/Report.php',
	'shoppreportchart' => '/flow/Report.php',
	'shoppreportcsvexport' => '/flow/Report.php',
	'shoppreportexportframework' => '/flow/Report.php',
	'shoppreportframework' => '/flow/Report.php',
	'shoppreporttabexport' => '/flow/Report.php',
	'shoppreportxlsexport' => '/flow/Report.php',
	'shoppresources' => '/flow/Resources.php',
	'shopprestservice' => '/flow/Remote.php',
	'shoppscripts' => '/flow/Scripts.php',
	'shoppsearchwidget' => '/ui/widgets/search.php',
	'shoppshippableitem' => '/model/Shiprates.php',
	'shoppshiprates' => '/model/Shiprates.php',
	'shoppshiprateservice' => '/model/Shiprates.php',
	'shoppshopperswidget' => '/ui/widgets/shoppers.php',
	'shoppshortcodes' => '/flow/Pages.php',
	'shoppsupport' => '/library/Support.php',
	'shopptagcloudwidget' => '/ui/widgets/tagcloud.php',
	'shopptax' => '/model/Tax.php',
	'shopptaxableitem' => '/model/Tax.php',
	'shoppthankspage' => '/flow/Pages.php',
	'shopptmceloader' => '/ui/behaviors/tinymce/dialog.php',
	'shoppui' => '/flow/Admin.php',
	'shopp_upgrader' => '/flow/Install.php',
	'shopp_upgrader_skin' => '/flow/Install.php',
	'shortwordparser' => '/model/Search.php',
	'singletonframework' => '/library/Framework.php',
	'smartcollection' => '/model/Collection.php',
	'soapclient' => '/library/SOAP.php',
	'soapval' => '/library/SOAP.php',
	'soap_fault' => '/library/SOAP.php',
	'soap_parser' => '/library/SOAP.php',
	'soap_transport_http' => '/library/SOAP.php',
	'spec' => '/model/Product.php',
	'storageengine' => '/model/Asset.php',
	'storageengines' => '/model/Asset.php',
	'storagemodule' => '/model/Asset.php',
	'storagesettingsui' => '/model/Asset.php',
	'storefront' => '/flow/Storefront.php',
	'subscriberframework' => '/model/library/Framework.php',
	'tagproducts' => '/model/Collection.php',
	'taxreport' => '/ui/reports/tax.php',
	'templateshippingui' => '/model/Shipping.php',
	'textify' => '/library/Email.php',
	'textifya' => '/library/Email.php',
	'textifyaddress' => '/library/Email.php',
	'textifyblockelement' => '/library/Email.php',
	'textifyblockquote' => '/library/Email.php',
	'textifybr' => '/library/Email.php',
	'textifycode' => '/library/Email.php',
	'textifydd' => '/library/Email.php',
	'textifydiv' => '/library/Email.php',
	'textifydl' => '/library/Email.php',
	'textifydt' => '/library/Email.php',
	'textifyem' => '/library/Email.php',
	'textifyfieldset' => '/library/Email.php',
	'textifyh1' => '/library/Email.php',
	'textifyh2' => '/library/Email.php',
	'textifyh3' => '/library/Email.php',
	'textifyh4' => '/library/Email.php',
	'textifyh5' => '/library/Email.php',
	'textifyh6' => '/library/Email.php',
	'textifyheader' => '/library/Email.php',
	'textifyhr' => '/library/Email.php',
	'textifyinlineelement' => '/library/Email.php',
	'textifylegend' => '/library/Email.php',
	'textifyli' => '/library/Email.php',
	'textifylistcontainer' => '/library/Email.php',
	'textifyol' => '/library/Email.php',
	'textifyp' => '/library/Email.php',
	'textifystrong' => '/library/Email.php',
	'textifytable' => '/library/Email.php',
	'textifytabletag' => '/library/Email.php',
	'textifytag' => '/library/Email.php',
	'textifytd' => '/library/Email.php',
	'textifyth' => '/library/Email.php',
	'textifytr' => '/library/Email.php',
	'textifyul' => '/library/Email.php',
	'txnfailordereventrenderer' => '/ui/orders/events.php',
	'txnordereventrenderer' => '/ui/orders/events.php',
	'unstockorderevent' => '/model/Events.php',
	'unstockordereventrenderer' => '/ui/orders/events.php',
	'viewedproducts' => '/model/Collection.php',
	'voidedorderevent' => '/model/Events.php',
	'voidedordereventrenderer' => '/ui/orders/events.php',
	'voidfailorderevent' => '/model/Events.php',
	'voidfailordereventrenderer' => '/ui/orders/events.php',
	'voidorderevent' => '/model/Events.php',
	'voidordereventrenderer' => '/ui/orders/events.php',
	'warehouse' => '/flow/Warehouse.php',
	'wpdatabaseobject' => '/library/DB.php',
	'wpshoppobject' => '/library/DB.php',
	'wsdl' => '/library/SOAP.php',
	'xmlquery' => '/library/XML.php',
	'xmlschema' => '/library/SOAP.php'
));
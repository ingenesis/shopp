<?php
/**
 * scripts.php
 *
 * Provides script concatenation and compression
 *
 * @author Jonathan Davis
 * @version 1.0
 * @copyright Ingenesis Limited, May 2010-2013
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package shopp
 * @since 1.1
 * @subpackage scripts
 **/

$queryvars = array('load', 'sjsl');

$load = '';
foreach ($queryvars as $var) {
	if ( isset($_GET[ $var ]) ) {
		$load = $_GET[ $var ];
		break;
	}
}

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
$load = explode(',', $load);

if ( empty($load) ) exit();

/**
 * @ignore
 */
if ( ! function_exists('add_action') ) { function add_action() {} }

/**
 * @ignore
 */
if ( ! function_exists('do_action_ref_array') ) { function do_action_ref_array() {} }

function get_file( $path ) {

	if ( function_exists('realpath') )
		$path = realpath($path);

	if ( ! $path || ! @is_file($path) )
		return '';

	return @file_get_contents($path);
}

if ( ! defined('SHORTINIT') ) {
	define('SHORTINIT',true);

	require dirname(dirname(__FILE__)) . '/core/library/Loader.php';

	if ( ! defined('ABSPATH') && $loadfile = ShoppLoader::find_wpload() )
		define('ABSPATH', dirname($loadfile) . '/');

	if ( ! defined('WPINC') ) define('WPINC', 'wp-includes');

	date_default_timezone_set('UTC');
}

$ShoppScripts = new ShoppScripts();
shopp_default_scripts($ShoppScripts);

$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );
$expires_offset = 31536000;
$out = '';

foreach( $load as $handle ) {
	if ( ! isset( $ShoppScripts->registered[ $handle ] ) )
		continue;

	$path = ShoppLoader::basepath() . $ShoppScripts->registered[ $handle ]->src;
	$out .= get_file($path) . "\n";
}

header('Content-Type: application/x-javascript; charset=UTF-8');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");

if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') ) {
	header('Vary: Accept-Encoding'); // Handle proxies
	if ( false !== strpos( strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
		header('Content-Encoding: deflate');
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== strpos( strtolower($_SERVER['HTTP_ACCEPT_ENCODING']), 'gzip') && function_exists('gzencode') ) {
		header('Content-Encoding: gzip');
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
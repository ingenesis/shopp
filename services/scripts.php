<?php
/**
 * scripts.php
 *
 * Provides script concatenation and compression
 *
 * @copyright Ingenesis Limited, May 2010-2014
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package Shopp\Services\Scripts
 * @version 1.4
 * @since 1.1
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

$load = isset($_GET['load']) ? $_GET['load'] : '';
$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $load );
$load = (array) explode(',', $load);

if ( empty($load) ) exit;

$ShoppScripts = new ShoppScripts();
shopp_default_scripts($ShoppScripts);

$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );
$expires_offset = 31536000;
$out = '';

foreach( $load as $handle ) {
	if ( ! isset( $ShoppScripts->registered[ $handle ] ) )
		continue;

	$path = realpath(ShoppLoader::basepath() . $ShoppScripts->registered[ $handle ]->src);
	if ( ! $path || ! @is_file($path) ) continue;

	$out .= @file_get_contents($path) . "\n";
}

header('Content-Type: application/x-javascript; charset=UTF-8');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");

if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) {
	header('Vary: Accept-Encoding'); // Handle proxies
	if ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
		header('Content-Encoding: deflate');
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) {
		header('Content-Encoding: gzip');
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
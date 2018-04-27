<?php
/**
 * DBInterface.php
 *
 * An interface for Shopp DB engines.
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.3.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

interface ShoppDBInterface {

	public function connect( $host, $user, $password );
	public function tether( $connection );
	public function db( $database );
	public function ping();
	public function close();
	public function query( $query );
	public function error();
	public function affected();
	public function object( $results = null );
	public function free();
	public function escape( $string );
}
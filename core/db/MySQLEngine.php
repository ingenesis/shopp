<?php
/**
 * MySQLEngine.php
 *
 * Implements the original PHP MySQL extension
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.3.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppMySQLEngine implements ShoppDBInterface {

	private $connection;
	private $results;

	public function tether ( $connection ) {
		$this->connection = $connection;
	}

	public function connect ( $host, $user, $password ) {
		$this->connection = @mysql_connect($host, $user, $password);
		return $this->connection;
	}

	public function db ( $database ) {
		return @mysql_select_db($database, $this->connection);
	}

	public function ping () {
		return mysql_ping($this->connection);
	}

	public function close () {
		return @mysql_close($this->connection);
	}

	public function query ( $query ) {
		$this->result = @mysql_query($query, $this->connection);
		return $this->result;
	}

	public function error () {
		return mysql_error($this->connection);
	}

	public function affected () {
		return mysql_affected_rows($this->connection);
	}

	public function object ( $results = null ) {
		if ( empty($results) ) $results = $this->results;
		if ( ! is_resource($results) ) return false;
		return @mysql_fetch_object($results);
	}

	public function free () {
		if ( ! is_resource($this->result) ) return false;
		return mysql_free_result($this->result);
	}

	public function escape ( $string ) {
		return mysql_real_escape_string($string, $this->connection);
	}

}
<?php
/**
 * MySQLiEngine.php
 *
 * Implements the PHP mysqli extension
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since     1.3.3
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppMySQLiEngine implements ShoppDBInterface {

	private $connection;
	private $results;

	public function tether ( $connection ) {
		$this->connection = $connection;
	}

	public function connect ( $host, $user, $password ) {
		$this->connection = new mysqli();
		@$this->connection->real_connect($host, $user, $password);
		return $this->connection;
	}

	public function db ( $database ) {
		return @$this->connection->select_db($database);
	}

	public function ping () {
		return $this->connection->ping();
	}

	public function close () {
		return @$this->connection->close();
	}

	public function query ( $query ) {
		$this->results = @$this->connection->query($query);
		return $this->results;
	}

	public function error () {
		return $this->connection->error;
	}

	public function affected () {
		return $this->connection->affected_rows;
	}

	public function object ( $results = null ) {
		if ( empty($results) ) $results = $this->results;
		if ( ! is_a($results, 'mysqli_result') ) return false;
		return $results->fetch_object();
	}

	public function free () {
		if ( ! is_a($this->results, 'mysqli_result') ) return false;
		return $this->results->free();
	}

	public function escape ( $string ) {
		return $this->connection->real_escape_string($string);
	}

}
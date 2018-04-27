<?php
/**
 * ShoppDBManager.php
 *
 * Provides database connection management
 *
 * @copyright Ingenesis Limited, April 2018
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/DB
 * @version   1.0
 * @since.    1.5
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

abstract class ShoppDBManager extends SingletonFramework {

	/**
	 * Tethers an available WPDB connection
	 *
	 * @since 1.3.3
	 *
	 * @return void
	 **/
	protected function wpdb() {
		global $wpdb;

		if ( empty($wpdb->dbh) ) return;

		if ( ! isset($wpdb->use_mysqli) || ! $wpdb->use_mysqli )
			$this->api = new ShoppMySQLEngine();
		else $this->api = new ShoppMySQLiEngine();

		$this->api->tether($wpdb->dbh);
	}

	/**
	 * Sets up the appropriate database engine
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected function engine() {
		if ( ! function_exists('mysqli_connect') )
			$this->api = new ShoppMySQLEngine();
		else $this->api = new ShoppMySQLiEngine();
	}

	/**
	 * Connects to the database server
	 *
	 * @since 1.0
	 *
	 * @param string $host The host name of the server
	 * @param string $user The database username
	 * @param string $password The database password
	 * @param string $database The database name
	 * @return void
	 **/
	protected function connect( $host, $user, $password, $database ) {

		$this->engine();

		if ( $this->api->connect($host, $user, $password) )
			$this->db($database);
		else $this->error("Could not connect to the database server '$host'.");

	}

	/**
	 * Database system initialization error handler
	 *
	 * @since 1.3
	 *
	 * @return void
	 **/
	protected function error( $message ) {
		trigger_error($message);
	}

	/**
	 * Check if we have a good connection, and if not reconnect
	 *
	 * @author Jonathan Davis
	 * @since 1.1.7
	 *
	 * @return boolean
	 **/
	public function reconnect() {
		if ( $this->api->ping() ) return true;

		$this->api->close($this->dbh);
		$this->connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if ( $this->dbh ) {
			global $wpdb;
			$wpdb->dbh = $this->dbh;
		}
		return ! empty($this->dbh);
	}

	/**
	 * Selects the database to use for querying
	 *
	 * @author Jonathan Davis
	 * @since 1.0
	 *
	 * @param string $database The database name
	 * @return void
	 **/
	public function db( $database ) {
		if ( ! $this->api->select($database) )
			$this->error("Could not select the '$database' database.");

	}

}
<?php
/**
 * Session.php
 *
 * @copyright Ingenesis Limited, 2008-2014
 * @license GNU GPL version 3 (or later) {@see license.txt}
 * @package Shopp\Session
 * @since 1.0
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

/**
 * Provides a session management framework
 *
 * @version 2.0
 */
abstract class ShoppSessionFramework {

	/** @var string $id The generated session id */
	public $session;

	/** @var string $id The generated session id */
	private $secure = false;

	/** @var string $table The generated session id */
	protected $_table;

	/** @var string $ip The IP address of the session */
	public $ip;

	/** @var array $data The session data structure */
	public $data;

	/** @var int $stash Flag to mark a session to be stashed */
	public $stash = 0;

	/** @var int $created The creation timestamp of the session */
	public $created;

	/** @var int $modified the last modified timestamp of the session */
	public $modified;

	/** @var string self::ENCRYPTION An encryption marker */
	const ENCRYPTION = "\xA7\xBF\xBF";

	/**
	 * Begins the session
	 *
	 * @return void
	 **/
	public function __construct () {

		if ( ! defined('SHOPP_SECURE_KEY') )
			define('SHOPP_SECURE_KEY', 'shopp_sec_' . COOKIEHASH);

		if ( ! defined('SHOPP_SESSION_COOKIE') )
			define('SHOPP_SESSION_COOKIE', 'wp_shopp_' . COOKIEHASH);

		$this->trim(); // Cleanup stale sessions

		if ( $this->open() ) // Reopen an existing session
			add_action('shutdown', array($this, 'save')); // Save on shutdown
		else $this->cook(); // Cook a new session cookie

		shopp_debug('Session started ' . str_repeat('-', 64));

		$this->load(); // Load any existing session data (if available)

	}

	/**
	 * Get the session ID
	 *
	 * If no session ID exists, a new session id is generated.
	 *
	 * @since 1.3.6
	 *
	 * @param bool $resession Generate a new session id
	 * @return string The generated or current session id
	 **/
	public function session ( $resession = false ) {

		if ( $resession || empty($this->session) ) {

			$exists = true;

			while ( $exists ) {
				// Hash the IP address, current time with high-entropy salt
				$hash = hash('sha256', $this->ip . microtime() . $this->entropy());
				// Choose a randomish 32-byte segment of the hash
				$session = substr($hash, mt_rand(0, 32), 32);
				// Ensure the session ID doesn't already exist, or try again
				$exists = $this->exists($session);
				// When a unique ID is found, try to create the session record
				if ( ! $exists && ! $this->create($session) )
					$exists = true; // If it fails, consider it existing and try again
			}

		    $this->session = $session;
		}

		return $this->session;
	}

	/**
	 * Initialize the session
	 *
	 * @since 1.0
	 *
	 * @return bool True if a session cookie exists, false otherwise;
	 **/
	protected function open () {

		// Ensure a secure encryption key is generated
		$this->securekey();

		// Set the IP address for the session
		if ( ! empty($_SERVER['HTTP_CLIENT_IP']) ) {
			$this->ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
			$this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}

		if ( ! empty( $_COOKIE[ SHOPP_SESSION_COOKIE ] ) )
			return ( $this->session = $_COOKIE[ SHOPP_SESSION_COOKIE ] );

	}

	/**
	 * Load the session from the database
	 *
	 * @since 1.0
	 *
	 * @param string $session (optional) A session id to load
	 * @return bool True if session data was loaded successfully, false otherwise
	 **/
	protected function load ( $session = false ) {

		if ( empty($session) )
			$session = $this->session;

		if ( empty($session) ) return false;

		do_action('shopp_session_load');

		$query = "SELECT * FROM $this->_table WHERE session='$session'";
		$loaded = sDB::query($query, 'object');

		if ( empty($loaded) ) {

			// No session found in the database

			if ( ! empty($this->session) ) {
				$this->session(true); // Cookie exists, but no session in the database, re-session (new id)
				$this->cook();        // Ensure leftover session cookies are replaced for security reasons
			}

			return false;
		}

		$this->decrypt($loaded->data);
		if ( empty($loaded->data) ) return false;

		$this->session = $loaded->session;
		$this->ip = $loaded->ip;
		$this->data = unserialize($loaded->data);
		$this->stash = $loaded->stash;
		$this->created = sDB::mktime($loaded->created);
		$this->modified = sDB::mktime($loaded->modified);

		do_action('shopp_session_loaded');

		return true;

	}

	/**
	 * Set the session cookie
	 *
	 * @since 1.3.6
	 *
	 * @return bool True if a cookie was set, false otherwise
	 **/
	protected function cook () {

		if ( headers_sent() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG )
				trigger_error('Shopp session cookie cannot be set after output headers have been sent.', E_USER_NOTICE );
			return false;
		}

		if ( empty($this->modified) )
			$this->modified = time();

		return setcookie(
			SHOPP_SESSION_COOKIE,                          // Shopp session cookie name
			$this->session(),                   // Generated session id
			false,                                         // Expiration (false makes it expire with the session)
			COOKIEPATH,                                    // Path
			COOKIE_DOMAIN,                                 // Domain
			false,                                         // Secure
			apply_filters('shopp_httponly_session', false) // HTTP only
		);
	}

	/**
	 * Save session data
	 *
	 * Inserts a new session record, or updates an existing record. When the session
	 * needs secured @see ShoppSessionFramework::secured(), the session data is
	 * encrypted first.
	 *
	 * @since 1.0
	 *
	 * @return bool True if successful, false otherwise
	 **/
	public function save () {

		// Don't update the session for prefetch requests (via <link rel="next" /> tags) currently FF-only
		if ( isset($_SERVER['HTTP_X_MOZ']) && 'prefetch' == $_SERVER['HTTP_X_MOZ'] ) return false;

		if ( empty($this->session) ) return false; // Do not save if there is no session id

		if ( false === $this->data )
			return false; // Encryption failed because of no SSL, do not save

		$data = sDB::escape( addslashes(serialize($this->data)) );
		$this->encrypt($data);

		$now = current_time('mysql');
		$query = "UPDATE $this->_table SET ip='$this->ip',stash='$this->stash',data='$data',modified='$now' WHERE session='$this->session'";

		$result = sDB::query($query);

		if ( ! $result )
			trigger_error("Could not save session updates to the database.");

		do_action('shopp_session_saved');

		return true;
	}

	/**
	 * Randomly clean up stale sessions
	 *
	 * Clean up stale sessions on 1% of connections
	 *
	 * @return void
	 **/
	private function trim () {
		if ( ! mt_rand(0, 99) )
			$this->clean();
	}

	/**
	 * Garbage collection routine for cleaning up old and expired sessions.
	 *
	 * 1.3 Added support for shopping session cold storage
	 *
	 * @since 1.1
	 *
	 * @return bool True if successful, false otherwise
	 **/
	public function clean () {
		$timeout = SHOPP_SESSION_TIMEOUT;
		$now = current_time('mysql');

		if ( ! sDB::query("DELETE FROM $this->_table WHERE data='' OR $timeout < UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(modified)") )
			trigger_error("Could not delete expired sessions.");

		return true;
	}

	/**
	 * Check or set the security setting for the session
	 *
	 * @since 1.0
	 *
	 * @param bool $setting Sets the secured flag (true or false)
	 * @return bool True if the session mode is secure, false otherwise
	 **/
	public function secured ( $setting = null ) {
		if ( is_null($setting) ) return $this->secure;

		$secured = $this->secure;
		$this->secure = $setting;

		shopp_debug( $this->secure ? 'Switching the session to secure mode.' : 'Switching the session to unsecure mode.' );

		if ( $secured && ! $this->secure )
			$this->save(); // When changing out of secure mode, resave the unencrypted session

		return $this->secure;
	}

	/**
	 * Destroys the session
	 *
	 * @since 1.3.6
	 *
	 * @return void
	 **/
	protected function destroy () {
		unset($this->session, $this->ip, $this->data, $this->created, $this->modified);
	}

	/**
	 * Checks if a given session ID exists in the database.
	 *
	 * @since 1.3.6
	 *
	 * @param string $id The session id to check
	 * @return bool True if the session exists, false otherwise
	 **/
	protected function exists ( $id ) {

		$exists = sDB::query("SELECT session FROM $this->_table WHERE session='$id' LIMIT 1", 'auto', 'col', 'id');
		return ( ! empty($exists) );

	}

	/**
	 * Inserts a new empty session
	 *
	 * @since 1.3.6
	 *
	 * @param string $session The session ID to create a record for
	 * @return bool True if successful, false otherwise
	 **/
	protected function create ( $session ) {
		$now = current_time('mysql');
		$query = "INSERT $this->_table SET session='$session',data='',ip='$this->ip',created='$now',modified='$now'";
		return sDB::query($query);
	}

	/**
	 * Generate the session security key for encryption and decryption
	 *
	 * @since 1.0
	 * @version 1.4
	 *
	 * @return string|bool The secure key, or false if not available
	 **/
	private function securekey () {
		if ( ! is_ssl() ) return false;

		if ( ! empty($_COOKIE[ SHOPP_SECURE_KEY ]) )
			return $_COOKIE[ SHOPP_SECURE_KEY ];

		$entropy = $this->entropy();
		$key = hash('sha256', $this->session . microtime(true) . $this->ip . $entropy);
		$success = setcookie(SHOPP_SECURE_KEY, $key, 0, COOKIEPATH, COOKIE_DOMAIN, true, true);

		if ( $success ) return $key;
		else return false;
	}

	/**
	 * Encrypts the session data.
	 *
	 * The session data is passed by reference and will be encrypted
	 * if the stars are aligned (the secured flag is set over an SSL
	 * connection with a valid encryption key).
	 *
	 * The security key is kept on the client-side as a secure cookie
	 * so that the server only ever touches it for a short time.
	 *
	 * @param array $data The session data to encrypt
	 * @return void
	 **/
	private function encrypt ( &$data ) {

		if ( ! $this->secured() ) return;
		if ( ! is_ssl() ) return;
		if ( ! $key = $this->securekey() ) return;

		shopp_debug('Cart saving in secure mode!');
		$secure = self::ENCRYPTION . sDB::query("SELECT AES_ENCRYPT('$data','$key') AS data", 'auto', 'col', 'data');

		$db = sDB::object();
		$data = $db->api->escape($secure);

	}

	/**
	 * Decrypts the session data.
	 *
	 * The session data is passed by reference and will be decrypted
	 * as long as a valid encryption key is available (exists in the cookie).
	 *
	 * When no encryption key cookie is available (usually because of switching
	 * from HTTPS to HTTP) an unlock process is fired (handled by a concrete
	 * implementation).
	 *
	 * @since 1.3.6
	 *
	 * @param array $data The session data to possibly decrypt
	 * @return void
	 **/
	private function decrypt ( &$data ) {

		$BOF = strlen(self::ENCRYPTION);

		// Set the secured flag if the data is encrypted
		$this->secured = ( self::ENCRYPTION == substr($data, 0, $BOF) );
		if ( ! $this->secured ) return;

		if ( empty($_COOKIE[ SHOPP_SECURE_KEY ]) )
			return $this->unlock(); // No encryption key available, run unlock handler

		$key = $_COOKIE[ SHOPP_SECURE_KEY ];

		$db = sDB::object();
		$data = sDB::query("SELECT AES_DECRYPT('" . $db->api->escape(substr($data, $BOF)) . "','$key') AS data", 'auto', 'col', 'data');

	}

	/**
	 * Session unlock behavior
	 *
	 * No default behavior exists. It must be implemented in the concrete class.
	 *
	 * @since 1.3.6
	 **/
	abstract public function unlock ();

	/**
	 * Generate random bytes for high entropy
	 *
	 * @since 1.3.6
	 *
	 * @return string String of random bytes
	 **/
	private function entropy () {

		$entropy = '';

		if ( function_exists('openssl_random_pseudo_bytes') ) {
			$entropy = openssl_random_pseudo_bytes(64, $strong);
			// Don't use openssl if a strong crypto algo wasn't used
			if ( $strong !== true )
				$entropy = '';
		}

	    $entropy .= uniqid(mt_rand(), true);

		// Try adding entropy from the Unix random number generator
	    if ( is_readable('/dev/urandom') && $h = fopen('/dev/urandom', 'rb') ) {
			if ( function_exists('stream_set_read_buffer') )
				stream_set_read_buffer($h, 0);
	        $entropy .= @fread($h, 64);
	        fclose($h);
	    }

		// Try adding entropy from the Windows random number generator
		if ( class_exists('COM') ) {
			try {
				$CAPICOM = new COM('CAPICOM.Utilities.1');
				$entropy .= base64_decode($CAPICOM->GetRandom(64, 0));
			} catch ( Exception $E ) {}
		}

		return $entropy;
	}

}
<?php
/**
 * Version.php
 *
 * Provides Shopp release version information
 *
 * @author Jonathan Davis
 * @copyright Ingenesis Limited, September 2013
 * @package shopp
 * @subpackage shopplib
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppVersion {

	/** @type int MAJOR The major version number */
	const MAJOR = 1;

	/** @type int MINOR The minor version number */
	const MINOR = 4;

	/** @type int PATCH The maintenance patch version number */
	const PATCH = 0;

	/** @type string PRERELEASE The prerelease designation (dev, beta, RC1) */
	const PRERELEASE = 'dev';

	/** @type string CODENAME The release project code name */
	const CODENAME = 'Orion';

	/** @type int DB The database schema version */
	const DB = 1201;

	/**
	 * Provides the full plugin release version string
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string The current release version string
	 **/
	public static function release () {
		return self::MAJOR . '.' . self::MINOR . ( self::PATCH > 0 ? '.' . self::PATCH : '' ) . self::PRERELEASE;
	}

	/**
	 * The database schema version number
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return int The database schema version for this release
	 **/
	public static function db () {
		return self::DB;
	}

	/**
	 * Provides a salted checksum of the current release
	 *
	 * This is used primarily for front-end browser cache control for assets (JS, CSS)
	 *
	 * @author Jonathan Davis
	 * @since 1.3
	 *
	 * @return string A short hex string representing the current cache version
	 **/
	public static function cache () {
		return hash('crc32b', __FILE__ . ShoppVersion::release());
	}

	public static function agent () {
		return 'Shopp ' . self::MAJOR . '.' . self::MINOR . ' for WordPress';
	}

}
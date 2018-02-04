<?php
/**
 * PartialUpload.php
 *
 * Provides partial upload handling
 *
 * @copyright Ingenesis Limited, December 2017
 * @license   GNU GPL version 3 (or later) {@see license.txt}
 * @package   Shopp/Package
 * @version   1.0
 * @since
 **/

defined( 'WPINC' ) || header( 'HTTP/1.1 403' ) & exit; // Prevent direct access

class ShoppPartialUpload {

	/** @var int BUFFER_SIZE The input streaming buffer size to read in bytes */
	const BUFFER_SIZE = 4096;

	/** @var int $chunk The current chunk index */
	private $chunk = 0;

	/** @var int $var The total number of chunks */
	private $chunks = 0;

	/** @var boolean $finalchunk True for the final chunk */
	private $finalchunk = false;

	/** @var array $file The uploaded file details */
	private $file;

	/** @var string $directory The base directory for WordPress uploads */
	private $directory;

	/** @var string $completed The path and filename for the completely processed file */
	private $completed;

	/** @var string $partfile The path and filename for the current partial upload of the file */
	private $partfile;

	/**
	 * Constructor.
	 *
	 * @since 1.4
	 *
	 * @param array $file The uploaded file data
	 * @param array $data The posted data
	 * @return void
	 **/
	public function __construct($file, $data) {
		$this->file = $file;

		// Get dropzone chunk data
		$this->chunk = $data['dzchunkindex'];
		$this->chunks = $data['dztotalchunkcount'];
		$this->finalchunk = ( $this->chunk == ( $this->chunks - 1 ) );

		$directory = wp_upload_dir();

		$this->directory = trailingslashit($directory['basedir']);

		$this->completed = $this->directory . $this->file['name'];
		$this->partfile = $this->completed . ".part." . $this->chunk;
	}

	/**
	 * Process the partial upload
	 *
	 * Determines how to handle the partial upload to run the
	 * finish process on the final chunk. The path to the complete file
	 * is returned.
	 *
	 * @since 1.4
	 *
	 * @return string Returns the completed file name on the final upload
	 **/
	public function process() {
		move_uploaded_file($this->file['tmp_name'], $this->partfile);

		if ( $this->finalchunk )
			return $this->finish();

		$this->respond(200); // Tell the browser everything's A-OK for this chunk
	}

	/**
	 * Finish processing all of the partial uploads into a complete file.
	 *
	 * Assembles all of the file parts in order to create the complete file.
	 *
	 * @since 1.4
	 *
	 * @return string The completed file name.
	 **/
	private function finish() {
		if ( ! $out = @fopen($this->completed, 'wb' ) )
			$this->respond(500, 'Failed to create the file on the server.');

		// Assemble the parts together into a single file
		for ( $i = 0; $i < $this->chunks; $i++ ) {
			$partfile = $this->completed . '.part.' . $i;

			// Read binary input stream and append it to temp file
			if ( ! $in = @fopen($partfile, 'rb') )
				$this->respond(500, 'Failed to read partial file upload chunk.');

			while ( $buffer = fread($in, self::BUFFER_SIZE) )
				fwrite($out, $buffer);

			@fclose($in);
			@unlink($partfile); // Remove the part file
		}
		fclose($out);

		return $this->completed;
	}

	/**
	 * Send a response to the browser and stop execution.
	 *
	 * For unsuccessful status codes (anything other than 200)
	 * cleans up the remnant partial uploads.
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	private function respond($status = 200, $message = '') {
		if ( 200 != $status ) {
			shopp_debug("Upload failure cleanup for $this->completed part files:");
			shopp_debug("Failure message: $message");
			$this->cleanup();
		}

		wp_die($message, $status);
	}

	/**
	 * Clean up any left over part files for this upload
	 *
	 * @since 1.4
	 *
	 * @return void
	 **/
	public function cleanup() {
		for ( $i = 0; $i <= $this->chunk; $i++ )
			@unlink($this->completed . ".part." . $i);
	}

}
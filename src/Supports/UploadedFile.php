<?php

namespace Stackonet\WP\Framework\Supports;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class UploadedFile
 * This class is just to keep backward compatibility
 */
class UploadedFile extends \Stackonet\WP\Framework\Media\UploadedFile {
	/**
	 * Construct a new UploadedFile instance.
	 *
	 * @param string $file The full path to the uploaded file provided by the client.
	 * @param string|null $name The file name.
	 * @param string|null $type The file media type.
	 * @param int|null $size The file size in bytes.
	 * @param int $error The UPLOAD_ERR_XXX code representing the status of the upload.
	 * @param bool $sapi Indicates if the upload is in a SAPI environment.
	 */
	public function __construct( $file, $name = null, $type = null, $size = null, $error = UPLOAD_ERR_OK, $sapi = false ) {
		_deprecated_function( __CLASS__, '1.1.4', \Stackonet\WP\Framework\Media\UploadedFile::class );

		parent::__construct( $file, $name, $type, $size, $error, $sapi );
	}
}

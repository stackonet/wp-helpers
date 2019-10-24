<?php

namespace Stackonet\WP\Framework\Supports;

use Stackonet\WP\Framework\Interfaces\UploadedFileInterface;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UploadedFile implements UploadedFileInterface {

	/**
	 * The client-provided full path to the file
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * The client-provided file name.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The client-provided media type of the file.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The size of the file in bytes.
	 *
	 * @var int
	 */
	protected $size;

	/**
	 * A valid PHP UPLOAD_ERR_xxx code for the file upload.
	 *
	 * @var int
	 */
	protected $error = UPLOAD_ERR_OK;

	/**
	 * Indicates if the uploaded file has already been moved.
	 *
	 * @var bool
	 */
	protected $moved = false;

	/**
	 * Indicates if the upload is from a SAPI environment.
	 *
	 * @var bool
	 */
	protected $sapi = false;

	/**
	 * Create a normalized tree of UploadedFile instances from the Environment.
	 *
	 * @return array A normalized tree of UploadedFile instances or null if none are provided.
	 */
	public static function getUploadedFiles() {
		if ( isset( $_FILES ) ) {
			return static::parseUploadedFiles( $_FILES );
		}

		return array();
	}

	/**
	 * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
	 *
	 * @param array $uploadedFiles The non-normalized tree of uploaded file data.
	 *
	 * @return array A normalized tree of UploadedFile instances.
	 */
	private static function parseUploadedFiles( array $uploadedFiles ) {
		$parsed = array();
		foreach ( $uploadedFiles as $field => $uploadedFile ) {
			if ( ! isset( $uploadedFile['error'] ) ) {
				if ( is_array( $uploadedFile ) ) {
					$parsed[ $field ] = static::parseUploadedFiles( $uploadedFile );
				}
				continue;
			}

			$parsed[ $field ] = array();
			if ( ! is_array( $uploadedFile['error'] ) ) {
				$parsed[ $field ] = new static(
					$uploadedFile['tmp_name'],
					isset( $uploadedFile['name'] ) ? $uploadedFile['name'] : null,
					isset( $uploadedFile['type'] ) ? $uploadedFile['type'] : null,
					isset( $uploadedFile['size'] ) ? $uploadedFile['size'] : null,
					$uploadedFile['error'],
					true
				);
			} else {
				$subArray = array();
				foreach ( $uploadedFile['error'] as $fileIdx => $error ) {
					// normalise sub array and re-parse to move the input's keyname up a level
					$subArray[ $fileIdx ]['name']     = $uploadedFile['name'][ $fileIdx ];
					$subArray[ $fileIdx ]['type']     = $uploadedFile['type'][ $fileIdx ];
					$subArray[ $fileIdx ]['tmp_name'] = $uploadedFile['tmp_name'][ $fileIdx ];
					$subArray[ $fileIdx ]['error']    = $uploadedFile['error'][ $fileIdx ];
					$subArray[ $fileIdx ]['size']     = $uploadedFile['size'][ $fileIdx ];

					$parsed[ $field ] = static::parseUploadedFiles( $subArray );
				}
			}
		}

		return $parsed;
	}

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
		$this->file  = $file;
		$this->name  = $name;
		$this->type  = $type;
		$this->size  = $size;
		$this->error = $error;
		$this->sapi  = $sapi;
	}

	/**
	 * Moves the uploaded file to the upload directory and assigns it a unique name
	 * to avoid overwriting an existing uploaded file.
	 *
	 * @param string $directory directory to which the file is moved
	 * @param string $filename unique file name
	 *
	 * @return string new path of moved file
	 */
	public function moveUploadedFile( $directory, $filename = null ) {
		if ( empty( $filename ) ) {
			$extension = pathinfo( $this->getClientFilename(), PATHINFO_EXTENSION );
			$basename  = md5( uniqid( rand(), true ) );
			$filename  = sprintf( '%s.%0.8s', $basename, $extension );
		}

		$directory     = rtrim( $directory, DIRECTORY_SEPARATOR );
		$new_file_path = $directory . DIRECTORY_SEPARATOR . $filename;
		$this->moveTo( $new_file_path );

		return $new_file_path;
	}

	/**
	 * Move the uploaded file to a new location.
	 *
	 * Use this method as an alternative to move_uploaded_file(). This method is
	 * guaranteed to work in both SAPI and non-SAPI environments.
	 * Implementations must determine which environment they are in, and use the
	 * appropriate method (move_uploaded_file(), rename(), or a stream
	 * operation) to perform the operation.
	 *
	 * $targetPath may be an absolute path, or a relative path. If it is a
	 * relative path, resolution should be the same as used by PHP's rename()
	 * function.
	 *
	 * The original file or stream MUST be removed on completion.
	 *
	 * If this method is called more than once, any subsequent calls MUST raise
	 * an exception.
	 *
	 * When used in an SAPI environment where $_FILES is populated, when writing
	 * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
	 * used to ensure permissions and upload status are verified correctly.
	 *
	 * If you wish to move to a stream, use getStream(), as SAPI operations
	 * cannot guarantee writing to stream destinations.
	 *
	 * @see http://php.net/is_uploaded_file
	 * @see http://php.net/move_uploaded_file
	 *
	 * @param string $targetPath Path to which to move the uploaded file.
	 *
	 * @throws \InvalidArgumentException if the $path specified is invalid.
	 * @throws \RuntimeException on any error during the move operation, or on
	 *     the second or subsequent call to the method.
	 */
	public function moveTo( $targetPath ) {
		if ( $this->moved ) {
			throw new \RuntimeException( 'Uploaded file already moved' );
		}

		if ( ! is_writable( dirname( $targetPath ) ) ) {
			throw new \InvalidArgumentException( 'Upload target path is not writable' );
		}

		if ( $this->sapi ) {
			if ( ! is_uploaded_file( $this->file ) ) {
				throw new \RuntimeException( sprintf( '%1s is not a valid uploaded file', $this->file ) );
			}

			if ( ! move_uploaded_file( $this->file, $targetPath ) ) {
				throw new \RuntimeException( sprintf( 'Error moving uploaded file %1s to %2s', $this->name, $targetPath ) );
			}
		} else {
			if ( ! rename( $this->file, $targetPath ) ) {
				throw new \RuntimeException( sprintf( 'Error moving uploaded file %1s to %2s', $this->name, $targetPath ) );
			}
		}

		$this->moved = true;
	}

	/**
	 * Retrieve the file
	 *
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Retrieve the file size.
	 *
	 * Implementations SHOULD return the value stored in the "size" key of
	 * the file in the $_FILES array if available, as PHP calculates this based
	 * on the actual size transmitted.
	 *
	 * @return int|null The file size in bytes or null if unknown.
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * Retrieve the error associated with the uploaded file.
	 *
	 * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
	 *
	 * If the file was uploaded successfully, this method MUST return
	 * UPLOAD_ERR_OK.
	 *
	 * Implementations SHOULD return the value stored in the "error" key of
	 * the file in the $_FILES array.
	 *
	 * @see http://php.net/manual/en/features.file-upload.errors.php
	 *
	 * @return int One of PHP's UPLOAD_ERR_XXX constants.
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Retrieve the filename sent by the client.
	 *
	 * Do not trust the value returned by this method. A client could send
	 * a malicious filename with the intention to corrupt or hack your
	 * application.
	 *
	 * Implementations SHOULD return the value stored in the "name" key of
	 * the file in the $_FILES array.
	 *
	 * @return string|null The filename sent by the client or null if none
	 *     was provided.
	 */
	public function getClientFilename() {
		return $this->name;
	}

	/**
	 * Retrieve the media type sent by the client.
	 *
	 * Do not trust the value returned by this method. A client could send
	 * a malicious media type with the intention to corrupt or hack your
	 * application.
	 *
	 * Implementations SHOULD return the value stored in the "type" key of
	 * the file in the $_FILES array.
	 *
	 * @return string|null The media type sent by the client or null if none
	 *     was provided.
	 */
	public function getClientMediaType() {
		return $this->type;
	}

	/**
	 * Retrieve the file extension from filename sent by the client.
	 *
	 * @return string
	 */
	public function getClientExtension() {
		$extension = pathinfo( $this->getClientFilename(), PATHINFO_EXTENSION );

		return strtolower( $extension );
	}
}

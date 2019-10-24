<?php

namespace Stackonet\WP\Framework\Supports;

use Exception;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Attachment {

	/**
	 * Upload attachments
	 *
	 * @param array|UploadedFile $file
	 * @param string $dir
	 *
	 * @return array
	 */
	public static function upload( $file, $dir = null ) {
		$attachments = array();

		if ( $file instanceof UploadedFile ) {
			$attachments[] = self::uploadIndividualFile( $file, $dir );
		}
		if ( is_array( $file ) ) {
			foreach ( $file as $_file ) {
				if ( ! $_file instanceof UploadedFile ) {
					continue;
				}
				$attachments[] = self::uploadIndividualFile( $_file, $dir );
			}
		}

		return array_filter( $attachments );
	}

	/**
	 * Upload attachment
	 *
	 * @param UploadedFile $file
	 * @param string $dir
	 *
	 * @return array
	 */
	private static function uploadIndividualFile( $file, $dir = null ) {
		$upload_dir = wp_upload_dir();
		if ( empty( $dir ) ) {
			$dir = date( 'Y/m', time() );
		}

		$attachment_dir = join( DIRECTORY_SEPARATOR, array( $upload_dir['basedir'], $dir ) );
		$attachment_url = join( DIRECTORY_SEPARATOR, array( $upload_dir['baseurl'], $dir ) );

		// Make attachment directory in upload directory if not already exists
		if ( ! file_exists( $attachment_dir ) ) {
			wp_mkdir_p( $attachment_dir );
		}

		// Check if attachment directory is writable
		if ( ! wp_is_writable( $attachment_dir ) ) {
			return array();
		}

		// Check file has no error
		if ( $file->getError() !== UPLOAD_ERR_OK ) {
			return array();
		}

		// Upload file
		try {
			$filename = wp_unique_filename( $attachment_dir, $file->getClientFilename() );
			$new_file = $file->moveUploadedFile( $attachment_dir, $filename );
			// Set correct file permissions.
			$stat  = stat( dirname( $new_file ) );
			$perms = $stat['mode'] & 0000666;
			@ chmod( $new_file, $perms );

			// Insert the attachment.
			$attachment    = array(
				'guid'           => join( DIRECTORY_SEPARATOR, array( $attachment_url, $filename ) ),
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $file->getClientFilename() ),
				'post_status'    => 'inherit',
				'post_mime_type' => $file->getClientMediaType(),
			);
			$attachment_id = wp_insert_attachment( $attachment, $new_file );

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attachment_id, $new_file );
			wp_update_attachment_metadata( $attachment_id, $attach_data );

			if ( ! is_wp_error( $attachment_id ) ) {
				$attachment['attachment_path'] = $new_file;
				$attachment['attachment_id']   = $attachment_id;
			}

			return $attachment;

		} catch ( Exception $exception ) {
			return array();
		}
	}
}

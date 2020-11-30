<?php

namespace Stackonet\WP\Framework\Supports;

use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

class Filesystem {
	/**
	 * Get WordPress file system
	 *
	 * @return bool|WP_Filesystem_Base
	 */
	public static function get_filesystem() {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			/**
			 * you can safely run request_filesystem_credentials() without any issues and don't need
			 * to worry about passing in a URL
			 */
			$credentials = request_filesystem_credentials( site_url(), '', false, false, array() );

			/* initialize the API */
			if ( ! WP_Filesystem( $credentials ) ) {
				/* any problems and we exit */
				return false;
			}
		}

		// Set the permission constants if not already set.
		if ( ! defined( 'FS_CHMOD_DIR' ) ) {
			define( 'FS_CHMOD_DIR', 0755 );
		}

		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			define( 'FS_CHMOD_FILE', 0644 );
		}

		return $wp_filesystem;
	}

	/**
	 * Create uploads directory if it does not exist.
	 *
	 * @param string $dir directory path to be created.
	 *
	 * @return boolean True of the directory is created. False if directory is not created.
	 */
	public static function maybe_create_dir( string $dir ) {
		$filesystem = static::get_filesystem();
		if ( ! $filesystem instanceof WP_Filesystem_Base ) {
			return false;
		}
		// Create the upload dir if it doesn't exist.
		if ( ! $filesystem->is_dir( $dir ) ) {
			// Create the directory.
			if ( ! $filesystem->mkdir( $dir ) ) {
				return false;
			}

			// Add an index file for security.
			$filesystem->put_contents( rtrim( $dir, '/' ) . '/index.php', "<?php\n# Silence is golden." );
		}

		return true;
	}

	/**
	 * Returns an array of paths for the upload directory of the current site.
	 *
	 * @param string $sub_dir directory name to be created in the WordPress uploads directory.
	 *
	 * @return array
	 */
	public static function get_uploads_dir( string $sub_dir ) {
		$upload_dir = wp_get_upload_dir();

		// SSL workaround.
		if ( static::is_ssl() ) {
			$upload_dir['baseurl'] = str_ireplace( 'http://', 'https://', $upload_dir['baseurl'] );
		}

		// Build the paths.
		return array(
			'path' => $upload_dir['basedir'] . '/' . $sub_dir,
			'url'  => $upload_dir['baseurl'] . '/' . $sub_dir,
		);
	}

	/**
	 * Re-create CSS file
	 *
	 * @param string $contents
	 * @param string $file Path to file.
	 *
	 * @return bool|array
	 */
	public static function update_file_content( string $contents, string $file ) {
		$filesystem = static::get_filesystem();
		if ( ! $filesystem instanceof WP_Filesystem_Base ) {
			return false;
		}

		// Create directory if not exists
		static::maybe_create_dir( dirname( $file ) );

		// Create file
		if ( ! $filesystem->exists( $file ) ) {
			$filesystem->touch( $file, time() );
		}

		return $filesystem->put_contents( $file, $contents, 0644 );
	}

	/**
	 * Checks to see if the site has SSL enabled or not.
	 *
	 * @return bool
	 */
	public static function is_ssl() {
		if ( is_ssl() ) {
			return true;
		} elseif ( 0 === stripos( get_option( 'siteurl' ), 'https://' ) ) {
			return true;
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			return true;
		}

		return false;
	}
}

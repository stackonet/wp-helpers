<?php

namespace Stackonet\WP\Framework\Supports;

use Stackonet\WP\Framework\Media\Uploader;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class Attachment
 * This class is just to keep backward compatibility
 */
class Attachment extends Uploader {

	/**
	 * @inheritDoc
	 */
	public static function upload( $file, $dir = null ) {
		_deprecated_function( __FUNCTION__, '1.1.4', Uploader::class . '::upload()' );

		return parent::upload( $file, $dir );
	}
}

<?php

namespace Stackonet\WP\Framework\Supports;

use Exception;

defined( 'ABSPATH' ) || exit;

class Logger {
	/**
	 * Log error to error log
	 *
	 * @param mixed $log
	 *
	 * @return bool
	 */
	public static function log( $log ) {
		// Log Exception
		if ( $log instanceof Exception ) {
			return error_log( $log );
		}
		// Log array and object
		if ( is_array( $log ) || is_object( $log ) ) {
			return error_log( print_r( $log, true ) );
		}

		return error_log( $log );
	}
}

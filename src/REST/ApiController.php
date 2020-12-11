<?php

namespace Stackonet\WP\Framework\REST;

use DateTime;
use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Traits\ApiResponse;
use Stackonet\WP\Framework\Traits\ApiUtils;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class ApiController
 *
 * @package Stackonet\WP\Framework\REST
 */
class ApiController extends WP_REST_Controller {
	use ApiResponse, ApiUtils;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'stackonet/v1';

	/**
	 * Format date for ISO8601 (Y-m-d\TH:i:s)
	 *
	 * @param string $date_string
	 *
	 * @return string
	 */
	public static function format_date( string $date_string = 'now' ): string {
		try {
			return ( new DateTime( $date_string ) )->format( 'Y-m-d\TH:i:s' );
		} catch ( Exception $e ) {
			Logger::log( $e );
		}

		return $date_string;
	}

	/**
	 * Format date for REST Response
	 *
	 * @param string|int|DateTime $date
	 * @param string              $type
	 *
	 * @return DateTime|int|string
	 * @throws Exception
	 */
	public static function formatDate( $date, $type = 'iso' ) {
		_deprecated_function( __FUNCTION__, '1.1.8', __CLASS__ . '::format_date()' );

		if ( ! $date instanceof DateTime ) {
			$date = new DateTime( $date );
		}

		// Format ISO 8601 date
		if ( 'iso' == $type ) {
			return $date->format( DateTime::ISO8601 );
		}

		if ( 'mysql' == $type ) {
			return $date->format( 'Y-m-d' );
		}

		if ( 'timestamp' == $type ) {
			return $date->getTimestamp();
		}

		if ( 'view' == $type ) {
			$date_format = get_option( 'date_format' );

			return $date->format( $date_format );
		}

		if ( ! in_array( $type, [ 'raw', 'mysql', 'timestamp', 'view', 'iso' ] ) ) {
			return $date->format( $type );
		}

		return $date;
	}
}

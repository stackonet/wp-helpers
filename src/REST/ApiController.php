<?php

namespace Stackonet\WP\Framework\REST;

use DateTime;
use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use Stackonet\WP\Framework\Traits\ApiResponse;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class ApiController
 *
 * @package Stackonet\WP\Framework\REST
 */
class ApiController extends WP_REST_Controller {
	use ApiResponse;

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
	 * Generate pagination metadata
	 *
	 * @param int $total_items
	 * @param int $per_page
	 * @param int $current_page
	 *
	 * @return array
	 */
	public static function get_pagination_data( $total_items = 0, $per_page = 20, $current_page = 1 ): array {
		$current_page = max( intval( $current_page ), 1 );
		$per_page     = max( intval( $per_page ), 1 );
		$total_items  = intval( $total_items );

		return [
			"total_items"  => $total_items,
			"per_page"     => $per_page,
			"current_page" => $current_page,
			"total_pages"  => ceil( $total_items / $per_page ),
		];
	}

	/**
	 * Read sorting data
	 * Example ==============================================
	 * sort=<field1>+<ASC|DESC>[,<field2>+<ASC|DESC>][, ...]
	 * GET ...?sort=title+DESC
	 * GET ...?sort=title+DESC,author+ASC
	 *
	 * @param string|null $sort
	 *
	 * @return array
	 */
	public static function sanitize_sorting_data( $sort = null ): array {
		$sort_array = [];
		if ( ! ( is_string( $sort ) && ! empty( $sort ) ) ) {
			return $sort_array;
		}
		$sort_items = explode( ',', $sort );
		foreach ( $sort_items as $item ) {
			if ( strpos( $item, '+' ) == false ) {
				continue;
			}
			list( $field, $order ) = explode( '+', $item );
			$sort_array[] = [
				"field" => $field,
				"order" => strtoupper( $order ) == 'DESC' ? 'DESC' : 'ASC',
			];
		}

		return $sort_array;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params(): array {
		return [
			'context'  => $this->get_context_param(),
			'page'     => [
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page' => [
				'description'       => __( 'Maximum number of items to be returned in result set.' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'search'   => [
				'description'       => __( 'Limit results to those matching a string.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'sort'     => [
				'description'       => __( 'Sorting order. Example: title+DESC,author+ASC' ),
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_sorting_data' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}

	/**
	 * Format date for REST Response
	 *
	 * @param string|int|DateTime $date
	 * @param string $type
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

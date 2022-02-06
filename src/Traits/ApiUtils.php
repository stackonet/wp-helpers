<?php

namespace Stackonet\WP\Framework\Traits;

use DateTime;
use Exception;
use Stackonet\WP\Framework\Supports\Logger;

trait ApiUtils {

	/**
	 * Check if current user is an admin
	 *
	 * @return bool
	 */
	public function is_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if current user is an editor
	 *
	 * @return bool
	 */
	public function is_editor(): bool {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * Check if current user is logged in
	 *
	 * @return bool
	 */
	public function is_logged_in(): bool {
		return current_user_can( 'read' );
	}

	/**
	 * Format date for ISO8601 (Y-m-d\TH:i:s)
	 *
	 * @param string $date_string The date string.
	 *
	 * @return string
	 */
	public static function format_date( string $date_string = 'now' ): string {
		try {
			return ( new DateTime( $date_string ) )->format( 'Y-m-d\TH:i:s' );
		} catch ( Exception $e ) {
			Logger::log( $e );
		}

		return '0000-00-00Y00:00:00';
	}

	/**
	 * Generate pagination metadata
	 *
	 * @param int $total_items Total available items.
	 * @param int $per_page Items to show per page.
	 * @param int $current_page The current page.
	 *
	 * @return array
	 */
	public static function get_pagination_data( $total_items = 0, $per_page = 20, $current_page = 1 ): array {
		$current_page = max( intval( $current_page ), 1 );
		$per_page     = max( intval( $per_page ), 1 );
		$total_items  = intval( $total_items );

		return [
			'total_items'  => $total_items,
			'per_page'     => $per_page,
			'current_page' => $current_page,
			'total_pages'  => ceil( $total_items / $per_page ),
		];
	}

	/**
	 * Read sorting data
	 * Example ==============================================
	 * sort=<field1>+<ASC|DESC>[,<field2>+<ASC|DESC>][, ...]
	 * GET ...?sort=title+DESC
	 * GET ...?sort=title+DESC,author+ASC
	 *
	 * @param string|null $sort The sorting string.
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
			if ( strpos( $item, '+' ) === false ) {
				continue;
			}
			list( $field, $order ) = explode( '+', $item );
			$sort_array[]          = [
				'field' => $field,
				'order' => strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC',
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
			'page'     => [
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'per_page' => [
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'search'   => [
				'description'       => 'Limit results to those matching a string.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'sort'     => [
				'description'       => 'Sorting order. Example: title+DESC,author+ASC',
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_sorting_data' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}

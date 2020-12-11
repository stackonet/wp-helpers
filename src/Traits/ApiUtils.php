<?php

namespace Stackonet\WP\Framework\Traits;

trait ApiUtils {

	/**
	 * Generate pagination metadata
	 *
	 * @param int $total_items
	 * @param int $per_page
	 * @param int $current_page
	 *
	 * @return array
	 */
	public static function get_pagination_data( $total_items = 0, $per_page = 20, $current_page = 1 ) {
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
	public static function sanitize_sorting_data( $sort = null ) {
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
	public function get_collection_params() {
		return [
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
}

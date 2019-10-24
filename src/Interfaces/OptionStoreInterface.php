<?php

namespace Stackonet\WP\Framework\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface OptionStoreInterface
 * @package App\Interfaces
 */
interface OptionStoreInterface extends DataStoreInterface {

	/**
	 * Prepare item for response
	 *
	 * @param array $item
	 *
	 * @return array
	 */
	public function prepare_item_for_response( array $item );

	/**
	 * Prepare item for database
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepare_item_for_database( array $data );
}

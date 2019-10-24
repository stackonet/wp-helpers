<?php

namespace Stackonet\WP\Framework\Abstracts;

use Stackonet\WP\Framework\Interfaces\OptionStoreInterface;

defined( 'ABSPATH' ) || exit;

abstract class OptionModel extends AbstractModel implements OptionStoreInterface {

	/**
	 * @var string
	 */
	protected static $option_name;

	/**
	 * Prepare item for response
	 *
	 * @param array $item
	 *
	 * @return array
	 */
	abstract public function prepare_item_for_response( array $item );

	/**
	 * Prepare item for database
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	abstract public function prepare_item_for_database( array $data );

	/**
	 * Get options
	 *
	 * @return array
	 */
	protected static function get_options() {
		$option = get_option( self::$option_name );

		return is_array( $option ) ? $option : [];
	}

	/**
	 * Method to create a new record
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function create( array $data ) {
		$data                      = wp_parse_args( $data, $this->default_data );
		$data[ $this->primaryKey ] = uniqid();

		$options       = self::get_options();
		$sanitize_data = $this->prepare_item_for_database( $data );
		$options[]     = $sanitize_data;
		update_option( self::$option_name, $options );

		return $data;
	}

	/**
	 * Method to read a record.
	 *
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function read( $data ) {
		$options = self::get_options();
		$ids     = wp_list_pluck( $options, 'id' );
		$index   = array_search( $data, $ids );

		return false !== $index ? $options[ $index ] : [];
	}

	/**
	 * Updates a record in the database.
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function update( array $data ) {
		$options = [];
		$ids     = wp_list_pluck( $options, $this->primaryKey );
		$index   = array_search( $data[ $this->primaryKey ], $ids );
		if ( ! false !== $index ) {
			return $data;
		}
		$data              = wp_parse_args( $data, $options[ $index ] );
		$sanitize_data     = self::prepare_item_for_database( $data );
		$options[ $index ] = $sanitize_data;
		update_option( self::$option_name, $options );

		return $data;
	}

	/**
	 * Deletes a record from the database.
	 *
	 * @param int $data
	 *
	 * @return bool
	 */
	public function delete( $data = 0 ) {
		$options = self::get_options();
		$ids     = wp_list_pluck( $options, $this->primaryKey );
		$index   = array_search( $data, $ids );
		if ( false !== $index ) {
			array_splice( $options, $index, 1 );
			update_option( self::$option_name, $options );

			return true;
		}

		return false;
	}

	/**
	 * Count total records from the database
	 *
	 * @return array
	 */
	abstract public function count_records();
}

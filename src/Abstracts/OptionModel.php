<?php

namespace Stackonet\WP\Framework\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Class OptionModel
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
class OptionModel {

	/**
	 * The option name
	 *
	 * @var string
	 */
	protected $option_name;

	/**
	 * Primary key
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Default data
	 *
	 * @var array
	 */
	protected $default_data = [];

	/**
	 * Get options
	 *
	 * @return array
	 */
	public function get_options(): array {
		$option = get_option( $this->option_name );

		return is_array( $option ) ? $option : [];
	}

	/**
	 * Method to read a record.
	 *
	 * @param mixed $data The data to search.
	 *
	 * @return mixed
	 */
	public function get_option( $data ) {
		$options = $this->get_options();
		$ids     = wp_list_pluck( $options, $this->primary_key );
		$index   = array_search( $data, $ids, true );

		return false !== $index ? $options[ $index ] : [];
	}

	/**
	 * Method to create a new record
	 *
	 * @param array $data List of arguments to save.
	 *
	 * @return array
	 */
	public function create( array $data ): array {
		$data          = wp_parse_args( $data, $this->default_data );
		$sanitize_data = $this->prepare_item_for_database( $data );

		$sanitize_data[ $this->primary_key ] = $this->get_last_insert_id() + 1;

		$options   = $this->get_options();
		$options[] = $sanitize_data;
		update_option( $this->option_name, $options );
		$this->increase_last_insert_id();

		return $sanitize_data;
	}

	/**
	 * Updates a record in the database.
	 *
	 * @param array $data List of arguments to update.
	 *
	 * @return array
	 */
	public function update( array $data ): array {
		$options = $this->get_options();
		$ids     = wp_list_pluck( $options, $this->primary_key );
		$index   = array_search( $data[ $this->primary_key ], $ids, true );
		if ( false === $index ) {
			return $data;
		}
		$data              = wp_parse_args( $data, $options[ $index ] );
		$sanitize_data     = $this->prepare_item_for_database( $data );
		$options[ $index ] = $sanitize_data;
		update_option( $this->option_name, $options );

		return $sanitize_data;
	}

	/**
	 * Deletes a record from the database.
	 *
	 * @param int $data The record id to be deleted.
	 *
	 * @return bool
	 */
	public function delete( int $data = 0 ): bool {
		$options = $this->get_options();
		$ids     = wp_list_pluck( $options, $this->primary_key );
		$index   = array_search( $data, $ids, true );
		if ( false !== $index ) {
			array_splice( $options, $index, 1 );
			update_option( $this->option_name, $options );

			return true;
		}

		return false;
	}

	/**
	 * Get last insert id
	 *
	 * @return int
	 */
	protected function get_last_insert_id(): int {
		$option = get_option( $this->option_name . '_last_insert_id', 0 );

		return is_numeric( $option ) ? intval( $option ) : 0;
	}

	/**
	 * Increase last insert id
	 */
	protected function increase_last_insert_id() {
		$option = $this->get_last_insert_id();

		update_option( $this->option_name . '_last_insert_id', ( $option + 1 ) );
	}

	/**
	 * Prepare item for response
	 *
	 * @param array $item List of arguments.
	 *
	 * @return array
	 */
	public function prepare_item_for_response( array $item ): array {
		return $item;
	}

	/**
	 * Prepare item for database storage
	 *
	 * @param array $item List of arguments.
	 *
	 * @return array
	 */
	public function prepare_item_for_database( array $item ): array {
		return $item;
	}
}

<?php

namespace Stackonet\WP\Framework\Abstracts;

use ArrayAccess;
use JsonSerializable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Data
 *
 * @package Stackonet\WP\Framework\Abstracts
 */
class Data implements ArrayAccess, JsonSerializable {

	/**
	 * Object data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Data has been changed from initial state
	 *
	 * @var bool
	 */
	protected $dirty = false;

	/**
	 * Data constructor.
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = [] ) {
		if ( is_array( $data ) ) {
			$this->set_data( $data );
		}
	}

	/**
	 * String representation of the class
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode( $this->to_array() );
	}

	/**
	 * Get collection item for key
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( string $name ) {
		return $this->get( $name );
	}

	/**
	 * Does this collection have a given key?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset( string $name ) {
		return $this->has( $name );
	}

	/**
	 * Array representation of the class
	 *
	 * @return array
	 */
	public function to_array(): array {
		return $this->get_data();
	}

	/**
	 * Does this collection have a given key?
	 *
	 * @param string $key The data key
	 *
	 * @return bool
	 */
	public function has( string $key ): bool {
		return isset( $this->data[ $key ] );
	}

	/**
	 * Set collection item
	 *
	 * @param string $key The data key
	 * @param mixed $value The data value
	 */
	public function set( string $key, $value ) {
		$this->data[ $key ] = $value;

		$this->dirty = true;
	}

	/**
	 * Get collection item for key
	 *
	 * @param string $key The data key
	 * @param mixed $default The default value to return if data key does not exist
	 *
	 * @return mixed The key's value, or the default value
	 */
	public function get( string $key, $default = '' ) {
		if ( $this->has( $key ) ) {
			return $this->data[ $key ];
		}

		return $default;
	}

	/**
	 * Remove item from collection
	 *
	 * @param string $key The data key
	 */
	public function remove( string $key ) {
		if ( $this->has( $key ) ) {
			unset( $this->data[ $key ] );

			$this->dirty = true;
		}
	}

	/**
	 * Set data
	 *
	 * @param array $data
	 */
	public function set_data( array $data ) {
		foreach ( $data as $key => $value ) {
			$this->data[ $key ] = $value;
		}
	}

	/**
	 * Get data
	 *
	 * @return array
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Whether an offset exists
	 *
	 * @param mixed $offset An offset to check for.
	 *
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists( $offset ): bool {
		return $this->has( $offset );
	}

	/**
	 * Offset to retrieve
	 *
	 * @param mixed $offset The offset to retrieve.
	 *
	 * @return mixed Can return all value types.
	 */
	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	/**
	 * Offset to set
	 *
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
	}

	/**
	 * Offset to unset
	 *
	 * @param mixed $offset The offset to unset.
	 *
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		$this->remove( $offset );
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @return array data which can be serialized by json_encode
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}
}

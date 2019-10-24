<?php

namespace Stackonet\WP\Framework\Abstracts;

use Stackonet\WP\Framework\Interfaces\DataStoreInterface;

class PostTypeModel extends AbstractModel implements DataStoreInterface {

	/**
	 * Post type name
	 *
	 * @var string
	 */
	protected $post_type = 'post';

	/**
	 * Method to create a new record
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function create( array $data ) {
		$data['post_type'] = $this->post_type;

		$post_id = wp_insert_post( $data );

		return $post_id;
	}

	/**
	 * Method to read a record.
	 *
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function read( $data ) {
		// TODO: Implement read() method.
	}

	/**
	 * Updates a record in the database.
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function update( array $data ) {
		wp_update_post( $data );
	}

	/**
	 * Deletes a record from the database.
	 *
	 * @param mixed $data
	 *
	 * @return bool
	 */
	public function delete( $data = null ) {
		// TODO: Implement delete() method.
	}

	/**
	 * Count total records from the database
	 *
	 * @return array
	 */
	public function count_records() {
		// TODO: Implement count_records() method.
	}
}

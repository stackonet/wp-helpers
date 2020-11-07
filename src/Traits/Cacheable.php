<?php

namespace Stackonet\WP\Framework\Traits;

defined( 'ABSPATH' ) || exit;

trait Cacheable {

	/**
	 * Retrieves the cache contents from the cache by key and group.
	 *
	 * @param int|string $key The key under which the cache contents are stored.
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache contents on success
	 * @see WP_Object_Cache::get()
	 */
	public function get_cache( $key ) {
		return wp_cache_get( $key, $this->get_cache_group() );
	}

	/**
	 * Saves the data to the cache.
	 *
	 * @param int|string $key The cache key to use for retrieval later.
	 * @param mixed $data The contents to store in the cache.
	 * @param int $expire Optional. When to expire the cache contents, in seconds. Default one month
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set_cache( $key, $data, int $expire = 0 ): bool {
		if ( empty( $expire ) ) {
			$expire = MONTH_IN_SECONDS;
		}

		return wp_cache_set( $key, $data, $this->get_cache_group(), $expire );
	}

	/**
	 * Removes the cache contents matching key and group.
	 *
	 * @param int|string $key What the contents in the cache are called.
	 *
	 * @return bool True on successful removal, false on failure.
	 */
	public function delete_cache( $key ): bool {
		$response = wp_cache_delete( $key, $this->get_cache_group() );
		if ( $response ) {
			$this->set_cache_last_changed();
		}

		return $response;
	}

	/**
	 * Get cache key for collection
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_cache_key_for_collection( array $args = [] ): string {
		$last_changed = wp_cache_get_last_changed( $this->get_cache_group() );
		$hash         = md5( serialize( $args ) );
		$prefix       = $this->get_cache_prefix();

		return "$prefix:$hash:$last_changed";
	}

	/**
	 * Get cache key for single item
	 *
	 * @param int|string $id
	 *
	 * @return string
	 */
	public function get_cache_key_for_single_item( $id ): string {
		$prefix = $this->get_cache_prefix();

		return "$prefix:$id";
	}

	/**
	 * Set cache last changed
	 * Use this method when you create, update or delete item
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set_cache_last_changed(): bool {
		return wp_cache_set( 'last_changed', microtime(), $this->get_cache_group() );
	}

	/**
	 * Get cache group name
	 *
	 * @return string
	 */
	public function get_cache_group(): string {
		if ( ! empty( $this->cache_group ) ) {
			return $this->cache_group;
		}

		return $this->get_cache_prefix();
	}

	/**
	 * Get cache prefix
	 *
	 * @return string
	 */
	public function get_cache_prefix(): string {
		if ( ! empty( $this->table ) ) {
			return $this->table;
		}

		return md5( static::class );
	}
}

<?php

namespace Stackonet\WP\Examples\RestClient;

use Stackonet\WP\Framework\Supports\RestClient;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class WpPostRestClient extends RestClient {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Set API base URL
		$this->api_base_url = esc_url_raw( rest_url( 'wp/v2' ) );

		/**
		 * Add authorization
		 * This is just a test authorization using Basic Auth plugin
		 *
		 * @link https://github.com/WP-API/Basic-Auth
		 */
		$this->add_auth_header( base64_encode( 'sayful:sayful' ) );

		parent::__construct();
	}

	/**
	 * Get posts
	 *
	 * @return array|\WP_Error
	 */
	public function list_posts() {
		return $this->get( 'posts' );
	}

	/**
	 * Get a single post
	 *
	 * @return array|\WP_Error
	 */
	public function get_single_post() {
		return $this->get( 'posts/378' );
	}

	/**
	 * Create a new post
	 *
	 * @return array|\WP_Error
	 */
	public function create_post() {
		$data = [
			'title'   => 'New post using REST client ' . uniqid(),
			'content' => '<p>You can user REST client helper to handle REST request.</p>',
			'excerpt' => 'You can user REST client helper to handle REST request.',
		];

		return $this->post( 'posts', $data );
	}

	/**
	 * Update a single post
	 *
	 * @return array|\WP_Error
	 */
	public function update_single_post() {
		$data = [
			'title' => 'New post using REST client - Updated',
		];

		return $this->put( 'posts/378', $data );
	}

	/**
	 * Delete a single post
	 *
	 * @return array|\WP_Error
	 */
	public function delete_single_post() {
		return $this->delete( 'posts/378', [ 'force' => true ] );
	}
}

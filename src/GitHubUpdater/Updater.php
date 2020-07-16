<?php

namespace Stackonet\WP\Framework\GitHubUpdater;

use Stackonet\WP\Framework\Supports\RestClient;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class Updater extends RestClient {
	/**
	 * GitHub username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * GitHub repository name
	 *
	 * @var string
	 */
	protected $repository;

	/**
	 * Get GitHub release info
	 *
	 * @var array
	 */
	protected $releases = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$api_base_url = 'https://api.github.com/';
		parent::__construct( $api_base_url );
	}

	/**
	 * Get GitHub username
	 *
	 * @return string
	 */
	public function get_username() {
		return $this->username;
	}

	/**
	 * Set GitHub username
	 *
	 * @param string $username
	 *
	 * @return self
	 */
	public function set_username( $username ) {
		$this->username = $username;

		return $this;
	}

	/**
	 * Get repository name
	 *
	 * @return string
	 */
	public function get_repository() {
		return $this->repository;
	}

	/**
	 * Set repository name
	 *
	 * @param string $repository
	 *
	 * @return self
	 */
	public function set_repository( $repository ) {
		$this->repository = $repository;

		return $this;
	}

	/**
	 * Get a list of releases for a repo
	 *
	 * @return array|WP_Error
	 */
	public function get_releases() {
		if ( ! ( is_array( $this->releases ) && count( $this->releases ) ) ) {
			$endpoint       = 'repos/' . $this->get_username() . '/' . $this->get_repository() . '/releases';
			$this->releases = $this->get( $endpoint );
		}

		return $this->releases;
	}

	/**
	 * Get latest release for a repo
	 *
	 * @return array|WP_Error
	 */
	public function get_latest_release() {
		$releases = $this->get_releases();

		if ( is_wp_error( $releases ) ) {
			return $releases;
		}

		return is_array( $releases ) ? $releases[0] : [];
	}
}

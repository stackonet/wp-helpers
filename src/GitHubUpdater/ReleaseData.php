<?php

namespace Stackonet\WP\Framework\GitHubUpdater;

use Stackonet\WP\Framework\Abstracts\Data;

defined( 'ABSPATH' ) || exit;

class ReleaseData extends Data {

	/**
	 * Class constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data = [] ) {
		$this->data = $data;
	}

	/**
	 * Get version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->get( 'tag_name' );
	}

	/**
	 * Get changelog
	 *
	 * @return string
	 */
	public function get_changelog() {
		return $this->get( 'body' );
	}

	/**
	 * Get published time
	 *
	 * @return string
	 */
	public function get_published_at() {
		return $this->get( 'published_at' );
	}

	/**
	 * Get download url
	 *
	 * @return string
	 */
	public function get_download_url() {
		$download_url = $this->get( 'zipball_url' );
		$first_asset  = $this->get_first_assets();
		if ( isset( $first_asset['browser_download_url'] ) ) {
			$download_url = $first_asset['browser_download_url'];
		}

		return $download_url;
	}

	/**
	 * Get assets
	 *
	 * @return array
	 */
	public function get_assets() {
		$assets = $this->get( 'assets' );

		return is_array( $assets ) ? $assets : [];
	}

	/**
	 * Get first asset
	 *
	 * @return array
	 */
	public function get_first_assets() {
		$assets = $this->get_assets();

		return count( $assets ) ? $assets[0] : [];
	}
}

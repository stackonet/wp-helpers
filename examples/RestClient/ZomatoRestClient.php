<?php

namespace Stackonet\WP\Examples\RestClient;

use Stackonet\WP\Framework\Supports\RestClient;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class ZomatoRestClient extends RestClient {

	/**
	 * Api base url
	 *
	 * @var string
	 */
	protected $api_base_url = 'https://developers.zomato.com/api/v2.1/';

	/**
	 * Zomato API key
	 *
	 * @var string
	 */
	protected $user_key = '';

	public function __construct() {
		$this->user_key = '0689ee4ab88f8107c748db9e00259432=';
		$this->add_headers( 'user-key', $this->user_key );

		parent::__construct();
	}

	/**
	 * Get a list of categories. List of all restaurants categorized under a particular restaurant type can be
	 * obtained using /Search API with Category ID as inputs
	 */
	public function get_categories() {
		return $this->get( 'categories' );
	}
}

<?php

namespace Stackonet\WP\Framework\Supports;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class RestClient
 * @package Stackonet\WP\Framework\Supports
 */
class RestClient {
	/**
	 * API base URL
	 *
	 * @var string
	 */
	protected $api_base_url = '';

	/**
	 * User Agent
	 *
	 * @var string
	 */
	protected $user_agent = null;

	/**
	 * Request headers
	 *
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Additional request arguments
	 *
	 * @var array
	 */
	protected $request_args = array();

	/**
	 * RestClient constructor.
	 *
	 * @param string $api_base_url
	 */
	public function __construct( $api_base_url = null ) {
		if ( ! empty( $api_base_url ) ) {
			$this->api_base_url = $api_base_url;
		}
		$this->user_agent = get_option( 'blogname' );

		//setup defaults
		$this->set_request_arg( 'timeout', 30 )
		     ->set_request_arg( 'sslverify', false )
		     ->add_headers( 'User-Agent', $this->user_agent );

		return $this;
	}

	/**
	 * Add header
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return self
	 */
	public function add_headers( $key, $value = null ) {
		if ( ! is_array( $key ) ) {
			$this->headers[ $key ] = $value;

			return $this;
		}
		foreach ( $key as $header => $header_value ) {
			$this->headers[ $header ] = $header_value;
		}

		return $this;
	}

	/**
	 * Add authorization header
	 *
	 * @param string $credentials
	 * @param string $type
	 *
	 * @return self
	 */
	public function add_auth_header( $credentials, $type = 'Basic' ) {
		return $this->add_headers( 'Authorization', $type . ' ' . $credentials );
	}

	/**
	 * @param string $name
	 * @param null $value
	 *
	 * @return $this
	 */
	public function set_request_arg( $name = '', $value = null ) {
		$this->request_args[ $name ] = $value;

		return $this;
	}

	/**
	 * Get api endpoint
	 *
	 * @param string $endpoint
	 *
	 * @return string
	 */
	public function get_api_endpoint( $endpoint ) {
		return rtrim( $this->api_base_url, '/' ) . '/' . ltrim( $endpoint, '/' );
	}

	/**
	 * Performs an HTTP GET request and returns its response.
	 *
	 * @param string $endpoint
	 * @param array $parameters
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function get( $endpoint = '', array $parameters = [] ) {
		return $this->request( 'GET', $endpoint, $parameters );
	}

	/**
	 * Performs an HTTP POST request and returns its response.
	 *
	 * @param string $endpoint
	 * @param mixed $data
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function post( $endpoint = '', $data = null ) {
		return $this->request( 'POST', $endpoint, $data );
	}

	/**
	 * Performs an HTTP PUT request and returns its response.
	 *
	 * @param string $endpoint
	 * @param mixed $data
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function put( $endpoint = '', $data = null ) {
		return $this->request( 'PUT', $endpoint, $data );
	}

	/**
	 * Performs an HTTP DELETE request and returns its response.
	 *
	 * @param string $endpoint
	 * @param mixed $parameters
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function delete( $endpoint = '', $parameters = null ) {
		return $this->request( 'DELETE', $endpoint, $parameters );
	}

	/**
	 * Performs an HTTP request and returns its response.
	 *
	 * @param string $method Request method. Support GET, POST, PUT, DELETE
	 * @param string $endpoint
	 * @param null|string|array $request_body
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function request( $method = 'GET', $endpoint = '', $request_body = null ) {
		$request_url      = $this->get_api_endpoint( $endpoint );
		$base_args        = array( 'method' => $method, 'headers' => $this->headers, );
		$api_request_args = array_merge( $base_args, $this->request_args );
		if ( ! empty( $request_body ) ) {
			if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
				$api_request_args['body'] = $request_body;
			} else {
				$request_url = add_query_arg( $request_body, $request_url );
			}
		}

		$response = wp_remote_request( $request_url, $api_request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $response_body ) ) {
			return new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
		}

		if ( ! ( $response_code >= 200 && $response_code < 300 ) ) {
			$response_message = wp_remote_retrieve_response_message( $response );

			return new WP_Error( 'rest_error', $response_message, $response_body );
		}

		return $response_body;
	}
}

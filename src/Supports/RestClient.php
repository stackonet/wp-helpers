<?php

namespace Stackonet\WP\Framework\Supports;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class RestClient
 *
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
	protected $headers = [];

	/**
	 * Additional request arguments
	 *
	 * @var array
	 */
	protected $request_args = [];

	/**
	 * Global parameters that should send on every request
	 *
	 * @var array
	 */
	protected $global_parameters = [];

	/**
	 * Request info for debugging
	 *
	 * @var array
	 */
	protected $debug_info = [];

	/**
	 * Class constructor.
	 *
	 * @param string|null $api_base_url API base URL.
	 */
	public function __construct( ?string $api_base_url = null ) {
		if ( filter_var( $api_base_url, FILTER_VALIDATE_URL ) ) {
			$this->api_base_url = $api_base_url;
		}
		$this->user_agent = get_option( 'blogname' );

		// setup defaults.
		$this->set_request_arg( 'timeout', 30 );
		$this->set_request_arg( 'sslverify', false );
		$this->add_headers( 'User-Agent', $this->user_agent );

		return $this;
	}

	/**
	 * Add header
	 *
	 * @param string|array $key Header key. Or array of headers with key => value format.
	 * @param mixed        $value The value.
	 *
	 * @return self
	 */
	public function add_headers( $key, $value = null ) {
		if ( is_string( $key ) ) {
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
	 * @param string $credentials Authorization credentials.
	 * @param string $type Authorization type.
	 *
	 * @return static
	 */
	public function add_auth_header( string $credentials, string $type = 'Basic' ) {
		return $this->add_headers( 'Authorization', sprintf( '%s %s', $type, $credentials ) );
	}

	/**
	 * Set request argument.
	 *
	 * @param string $name Argument name.
	 * @param null   $value Argument value.
	 *
	 * @return static
	 */
	public function set_request_arg( string $name = '', $value = null ) {
		$this->request_args[ $name ] = $value;

		return $this;
	}

	/**
	 * Get api endpoint
	 *
	 * @param string $endpoint Rest URL Endpoint.
	 *
	 * @return string
	 */
	public function get_api_endpoint( string $endpoint = '' ): string {
		return rtrim( $this->api_base_url, '/' ) . '/' . ltrim( $endpoint, '/' );
	}

	/**
	 * Get global parameters
	 *
	 * @return array
	 */
	public function get_global_parameters(): array {
		return $this->global_parameters;
	}

	/**
	 * Set global parameter
	 *
	 * @param string $key data key.
	 * @param mixed  $value The value to be set.
	 *
	 * @return static
	 */
	public function set_global_parameter( string $key, $value ) {
		$this->global_parameters[ $key ] = $value;

		return $this;
	}

	/**
	 * Get debug info
	 *
	 * @return array
	 */
	public function get_debug_info(): array {
		return $this->debug_info;
	}

	/**
	 * Performs an HTTP GET request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param array  $parameters Additional parameters.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function get( string $endpoint = '', array $parameters = [] ) {
		return $this->request( 'GET', $endpoint, $parameters );
	}

	/**
	 * Performs an HTTP POST request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param mixed  $data The rest body content.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function post( string $endpoint = '', $data = null ) {
		return $this->request( 'POST', $endpoint, $data );
	}

	/**
	 * Performs an HTTP PUT request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param mixed  $data The rest body content.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function put( string $endpoint = '', $data = null ) {
		return $this->request( 'PUT', $endpoint, $data );
	}

	/**
	 * Performs an HTTP DELETE request and returns its response.
	 *
	 * @param string $endpoint The rest endpoint.
	 * @param mixed  $parameters Additional parameters.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function delete( string $endpoint = '', $parameters = null ) {
		return $this->request( 'DELETE', $endpoint, $parameters );
	}

	/**
	 * Performs an HTTP request and returns its response.
	 *
	 * @param string            $method Request method. Support GET, POST, PUT, DELETE.
	 * @param string            $endpoint The rest endpoint.
	 * @param null|string|array $request_body Request body or additional parameters for GET method.
	 *
	 * @return array|WP_Error The response array or a WP_Error on failure.
	 */
	public function request( string $method = 'GET', string $endpoint = '', $request_body = null ) {
		$request_url      = $this->get_api_endpoint( $endpoint );
		$base_args        = array(
			'method'  => $method,
			'headers' => $this->headers,
		);
		$api_request_args = array_merge( $base_args, $this->request_args );
		if ( ! empty( $request_body ) ) {
			if ( in_array( $method, [ 'POST', 'PUT' ], true ) ) {
				$api_request_args['body'] = $request_body;
			} else {
				$request_url = add_query_arg( $request_body, $request_url );
			}
		}

		// Add global parameters if any.
		if ( count( $this->get_global_parameters() ) ) {
			$request_url = add_query_arg( $this->get_global_parameters(), $request_url );
		}

		$response = wp_remote_request( $request_url, $api_request_args );

		$this->debug_info = [
			'request_url'  => $request_url,
			'request_args' => $api_request_args,
		];

		if ( is_wp_error( $response ) ) {
			$response->add_data( $this->debug_info, 'debug_info' );

			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $response_body ) ) {
			$response = new WP_Error( 'unexpected_response_type', 'Rest Client Error: unexpected response type' );
			$response->add_data( $this->debug_info, 'debug_info' );

			return $response;
		}

		if ( ! ( $response_code >= 200 && $response_code < 300 ) ) {
			$response_message = wp_remote_retrieve_response_message( $response );

			$response = new WP_Error( 'rest_error', $response_message, $response_body );
			$response->add_data( $this->debug_info, 'debug_info' );

			return $response;
		}

		return $response_body;
	}
}

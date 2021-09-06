<?php

namespace Stackonet\WP\Framework\Traits;

use WP_Error;
use WP_REST_Response;

trait ApiResponse {

	protected $statusCode = 200;
	protected $errorCode = null;
	protected $message = null;
	protected $data = null;
	protected $headers = [];

	/**
	 * Decode HTML entity
	 * WordPress encode html entity when saving on database.
	 * Convert then back to character before sending data
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function html_entity_decode( $value ) {
		return is_string( $value ) ?
			html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, get_option( 'blog_charset', 'UTF-8' ) ) :
			$value;
	}

	/**
	 * Get HTTP status code.
	 *
	 * @return integer
	 */
	public function getStatusCode(): int {
		return $this->statusCode;
	}

	/**
	 * Set HTTP status code.
	 *
	 * @param int $statusCode
	 *
	 * @return static
	 */
	public function setStatusCode( int $statusCode ) {
		$this->statusCode = $statusCode;

		return $this;
	}

	/**
	 * If it has error code
	 *
	 * @return bool
	 */
	public function has_error_code(): bool {
		return ! empty( $this->get_error_code() );
	}

	/**
	 * Get error code
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		if ( is_string( $this->errorCode ) && ! empty( $this->errorCode ) ) {
			return $this->errorCode;
		}
		$default = $this->get_default_error_message();

		return $default['code'] ?? '';
	}

	/**
	 * Set error code
	 *
	 * @param string|null $errorCode
	 */
	public function set_error_code( $errorCode ) {
		if ( is_string( $errorCode ) && ! empty( $errorCode ) ) {
			$this->errorCode = $errorCode;
		}

		return $this;
	}

	/**
	 * If it has a message
	 *
	 * @return bool
	 */
	public function has_message(): bool {
		return ! empty( $this->get_message() );
	}

	/**
	 * Get message
	 *
	 * @return string
	 */
	public function get_message(): string {
		if ( is_string( $this->message ) && ! empty( $this->message ) ) {
			return $this->message;
		}
		$default = $this->get_default_error_message();

		return $default['message'] ?? '';
	}

	/**
	 * Set message
	 *
	 * @param string|null $message
	 */
	public function set_message( $message ) {
		if ( is_string( $message ) && ! empty( $message ) ) {
			$this->message = $message;
		}

		return $this;
	}

	/**
	 * If it has data
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		return ! empty( $this->data );
	}

	/**
	 * Get data
	 *
	 * @return mixed
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Set data
	 *
	 * @param mixed $data
	 */
	public function set_data( $data ) {
		if ( ! empty( $data ) ) {
			$this->data = $data;
		}

		return $this;
	}

	/**
	 * Get additional response headers
	 *
	 * @return array
	 */
	public function get_headers(): array {
		return $this->headers;
	}

	/**
	 * Set additional response header
	 *
	 * @param array $headers
	 *
	 * @return static
	 */
	public function set_headers( array $headers ) {
		foreach ( $headers as $key => $value ) {
			$this->headers[ $key ] = $value;
		}

		return $this;
	}

	/**
	 * Check if it is a success response
	 *
	 * @return bool
	 */
	public function is_success_response(): bool {
		return ( $this->getStatusCode() >= 200 && $this->getStatusCode() < 300 );
	}

	/**
	 * Get default error message
	 *
	 * @return array
	 */
	protected function get_default_error_message(): array {
		$messages = [
			401 => [
				'code'    => 'rest_forbidden_context',
				'message' => 'Sorry, you are not allowed to access this resource.'
			],
			403 => [
				'code'    => 'rest_forbidden_context',
				'message' => 'Sorry, you are not allowed to access this resource.'
			],
			404 => [
				'code'    => 'rest_no_item_found',
				'message' => 'Sorry, no item found for your request.'
			],
			422 => [
				'code'    => 'rest_invalid_data_type',
				'message' => 'One or more fields has an error. Fix and try again.'
			],
			500 => [
				'code'    => 'rest_server_error',
				'message' => 'Sorry, something went wrong.'
			],
		];

		return $messages[ $this->getStatusCode() ] ?? [ 'code' => null, 'message' => null ];
	}

	/**
	 * Get formatted response
	 *
	 * @return array
	 */
	protected function get_formatted_response(): array {
		$response = [
			'success' => $this->is_success_response()
		];

		if ( $this->has_error_code() ) {
			$response['code'] = $this->get_error_code();
		}

		if ( $this->has_message() ) {
			$response['message'] = $this->get_message();
		}

		if ( $this->has_data() ) {
			if ( ! $this->is_success_response() ) {
				$response['errors'] = $this->get_data();
			} else {
				$response['data'] = is_array( $this->get_data() ) ?
					map_deep( $this->get_data(), [ $this, 'html_entity_decode' ] ) :
					$this->get_data();
			}
		}

		return $response;
	}

	/**
	 * Respond.
	 *
	 * @param mixed $data Response data. Default null.
	 * @param int $status Optional. HTTP status code. Default 200.
	 * @param array $headers Optional. HTTP header map. Default empty array.
	 *
	 * @return WP_REST_Response
	 */
	public function respond( $data = null, $status = null, $headers = [] ): WP_REST_Response {
		if ( empty( $data ) ) {
			$data = $this->get_formatted_response();
		}
		if ( empty( $status ) ) {
			$status = $this->getStatusCode();
		}
		if ( empty( $headers ) ) {
			$headers = $this->get_headers();
		}

		return new WP_REST_Response( $data, $status, $headers );
	}

	/**
	 * Response with WP_Error object
	 *
	 * @param WP_Error $error
	 *
	 * @return WP_REST_Response
	 */
	public function respondWithWpError( WP_Error $error ): WP_REST_Response {
		$this->set_message( $error->get_error_message() );
		$this->set_error_code( $error->get_error_code() );

		$error_data = $error->has_errors() && is_array( $error->get_error_data() ) ? $error->get_error_data() : [];
		if ( isset( $error_data['status'] ) ) {
			$status_code = is_numeric( $error_data['status'] ) ? intval( $error_data['status'] ) : 400;
			unset( $error_data['status'] );
		}

		if ( count( $error_data ) ) {
			$this->set_data( $error_data );
		}

		$this->setStatusCode( $status_code ?? 400 );

		return $this->respond();
	}

	/**
	 * Response error message
	 *
	 * @param string|array|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondWithError( $code = null, $message = null, $data = null ): WP_REST_Response {
		if ( $code instanceof WP_Error ) {
			return $this->respondWithWpError( $code );
		}

		if ( 1 === func_num_args() && is_array( $code ) ) {
			$this->set_data( $code );

			return $this->respond();
		}

		$this->set_error_code( $code );
		$this->set_message( $message );
		$this->set_data( $data );

		return $this->respond();
	}

	/**
	 * Response success message
	 *
	 * @param mixed $data
	 * @param string|null $message
	 * @param array $headers
	 *
	 * @return WP_REST_Response
	 */
	public function respondWithSuccess( $data = null, $message = null, $headers = array() ): WP_REST_Response {
		if ( 1 === func_num_args() && is_string( $data ) ) {
			list( $data, $message ) = array( null, $data );
		}

		$this->set_data( $data );
		$this->set_message( $message );
		$this->set_headers( $headers );

		return $this->respond();
	}

	/**
	 * 200 (OK)
	 * The request has succeeded.
	 * Use cases:
	 * --> update/retrieve data
	 * --> bulk creation
	 * --> bulk update
	 *
	 * @param mixed $data
	 * @param string|null $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondOK( $data = null, $message = null ): WP_REST_Response {
		return $this->setStatusCode( 200 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 201 (Created)
	 * The request has succeeded and a new resource has been created as a result of it.
	 * This is typically the response sent after a POST request, or after some PUT requests.
	 *
	 * @param mixed $data
	 * @param string|null $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondCreated( $data = null, $message = null ): WP_REST_Response {
		return $this->setStatusCode( 201 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 202 (Accepted)
	 * The request has been received but not yet acted upon.
	 * The response should include the Location header with a link towards the location where
	 * the final response can be polled & later obtained.
	 * Use cases:
	 * --> asynchronous tasks (e.g., report generation)
	 * --> batch processing
	 * --> delete data that is NOT immediate
	 *
	 * @param mixed $data
	 * @param string|null $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondAccepted( $data = null, $message = null ): WP_REST_Response {
		return $this->setStatusCode( 202 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 204 (No Content)
	 * There is no content to send for this request, but the headers may be useful.
	 * Use cases:
	 * --> deletion succeeded
	 *
	 * @param mixed $data
	 * @param string|null $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondNoContent( $data = null, $message = null ): WP_REST_Response {
		return $this->setStatusCode( 204 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 400 (Bad request)
	 * Server could not understand the request due to invalid syntax.
	 * Use cases:
	 * --> invalid/incomplete request
	 * --> return multiple client errors at once
	 *
	 * @param string|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondBadRequest( $code = null, $message = null, $data = null ): WP_REST_Response {
		return $this->setStatusCode( 400 )->respondWithError( $code, $message, $data );
	}

	/**
	 * 401 (Unauthorized)
	 * The request requires user authentication.
	 *
	 * @param string|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondUnauthorized( $code = null, $message = null, $data = null ): WP_REST_Response {
		return $this->setStatusCode( 401 )->respondWithError( $code, $message, $data );
	}

	/**
	 * 403 (Forbidden)
	 * The client is authenticated but not authorized to perform the action.
	 *
	 * @param string|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondForbidden( $code = null, $message = null, $data = null ): WP_REST_Response {
		return $this->setStatusCode( 403 )->respondWithError( $code, $message, $data );
	}

	/**
	 * 404 (Not Found)
	 * The server can not find requested resource. In an API, this can also mean that the endpoint is valid but
	 * the resource itself does not exist. Servers may also send this response instead of 403 to hide
	 * the existence of a resource from an unauthorized client.
	 *
	 * @param string|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondNotFound( $code = null, $message = null, $data = null ): WP_REST_Response {
		return $this->setStatusCode( 404 )->respondWithError( $code, $message, $data );
	}

	/**
	 * 422 (Unprocessable Entity)
	 * The request was well-formed but was unable to be followed due to semantic errors.
	 *
	 * @param string|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondUnprocessableEntity( $code = null, $message = null, $data = null ): WP_REST_Response {
		return $this->setStatusCode( 422 )->respondWithError( $code, $message, $data );
	}

	/**
	 * 500 (Internal Server Error)
	 * The server has encountered a situation it doesn't know how to handle.
	 *
	 * @param string|null $code
	 * @param string|null $message
	 * @param mixed $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondInternalServerError( $code = null, $message = null, $data = null ): WP_REST_Response {
		return $this->setStatusCode( 500 )->respondWithError( $code, $message, $data );
	}
}

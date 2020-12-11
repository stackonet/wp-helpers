<?php

namespace Stackonet\WP\Framework\Traits;

use WP_REST_Response;

trait ApiResponse {
	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	protected $statusCode = 200;

	/**
	 * Decode HTML entity
	 * WordPress encode html entity when saving on database.
	 * Convert then back to character before sending data
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function html_entity_decode( $value ) {
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
	 * Respond.
	 *
	 * @param mixed $data Response data. Default null.
	 * @param int $status Optional. HTTP status code. Default 200.
	 * @param array $headers Optional. HTTP header map. Default empty array.
	 *
	 * @return WP_REST_Response
	 */
	public function respond( $data = null, $status = 200, $headers = array() ): WP_REST_Response {
		return new WP_REST_Response( $data, $status, $headers );
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
		if ( 1 === func_num_args() && is_array( $code ) ) {
			list( $code, $message, $data ) = array( null, null, $code );
		}

		$status_code = $this->getStatusCode();
		$response    = [ 'success' => false ];

		if ( ! empty( $code ) && is_string( $code ) ) {
			$response['code'] = $code;
		}

		if ( ! empty( $message ) && is_string( $message ) ) {
			$response['message'] = $message;
		}

		if ( ! empty( $data ) ) {
			$response['errors'] = $data;
		}

		return $this->respond( $response, $status_code );
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

		$code     = $this->getStatusCode();
		$response = [ 'success' => true ];

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		if ( ! empty( $data ) ) {
			$response['data'] = map_deep( $data, function ( $value ) {
				return $this->html_entity_decode( $value );
			} );
		}

		return $this->respond( $response, $code, $headers );
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
		if ( empty( $code ) ) {
			$code = 'rest_forbidden_context';
		}

		if ( empty( $message ) ) {
			$message = 'Sorry, you are not allowed to access this resource.';
		}

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
		if ( empty( $code ) ) {
			$code = 'rest_forbidden_context';
		}

		if ( empty( $message ) ) {
			$message = 'Sorry, you are not allowed to access this resource.';
		}

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
		if ( empty( $code ) ) {
			$code = 'rest_no_item_found';
		}

		if ( empty( $message ) ) {
			$message = 'Sorry, no resource found for your request.';
		}

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
		if ( 1 === func_num_args() && is_array( $code ) ) {
			list( $code, $message, $data ) = array( null, null, $code );
		}

		if ( empty( $code ) ) {
			$code = 'rest_invalid_data_type';
		}

		if ( empty( $message ) ) {
			$message = 'One or more fields has an error. Fix and try again.';
		}

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
		if ( empty( $code ) ) {
			$code = 'rest_server_error';
		}

		if ( empty( $message ) ) {
			$message = 'Sorry, something went wrong.';
		}

		return $this->setStatusCode( 500 )->respondWithError( $code, $message, $data );
	}
}

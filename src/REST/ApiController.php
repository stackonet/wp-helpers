<?php

namespace Stackonet\WP\Framework\REST;

use DateTime;
use Exception;
use Stackonet\WP\Framework\Supports\Logger;
use WP_REST_Controller;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Class ApiController
 *
 * @package Stackonet\WP\Framework\REST
 */
class ApiController extends WP_REST_Controller {

	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	protected $statusCode = 200;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'stackonet/v1';

	/**
	 * Get HTTP status code.
	 *
	 * @return integer
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * Set HTTP status code.
	 *
	 * @param int $statusCode
	 *
	 * @return ApiController
	 */
	public function setStatusCode( $statusCode ) {
		$this->statusCode = $statusCode;

		return $this;
	}

	/**
	 * Respond.
	 *
	 * @param mixed $data    Response data. Default null.
	 * @param int   $status  Optional. HTTP status code. Default 200.
	 * @param array $headers Optional. HTTP header map. Default empty array.
	 *
	 * @return WP_REST_Response
	 */
	public function respond( $data = null, $status = 200, $headers = array() ) {
		return new WP_REST_Response( $data, $status, $headers );
	}

	/**
	 * Response error message
	 *
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondWithError( $code = null, $message = null, $data = null ) {
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
	 * @param mixed  $data
	 * @param string $message
	 * @param array  $headers
	 *
	 * @return WP_REST_Response
	 */
	public function respondWithSuccess( $data = null, $message = null, $headers = array() ) {
		if ( 1 === func_num_args() && is_string( $data ) ) {
			list( $data, $message ) = array( null, $data );
		}

		$code     = $this->getStatusCode();
		$response = [ 'success' => true ];

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		if ( ! empty( $data ) ) {
			$response['data'] = $data;
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
	 * @param mixed  $data
	 * @param string $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondOK( $data = null, $message = null ) {
		return $this->setStatusCode( 200 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 201 (Created)
	 * The request has succeeded and a new resource has been created as a result of it.
	 * This is typically the response sent after a POST request, or after some PUT requests.
	 *
	 * @param mixed  $data
	 * @param string $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondCreated( $data = null, $message = null ) {
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
	 * @param mixed  $data
	 * @param string $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondAccepted( $data = null, $message = null ) {
		return $this->setStatusCode( 202 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 204 (No Content)
	 * There is no content to send for this request, but the headers may be useful.
	 * Use cases:
	 * --> deletion succeeded
	 *
	 * @param mixed  $data
	 * @param string $message
	 *
	 * @return WP_REST_Response
	 */
	public function respondNoContent( $data = null, $message = null ) {
		return $this->setStatusCode( 204 )->respondWithSuccess( $data, $message );
	}

	/**
	 * 400 (Bad request)
	 * Server could not understand the request due to invalid syntax.
	 * Use cases:
	 * --> invalid/incomplete request
	 * --> return multiple client errors at once
	 *
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondBadRequest( $code = null, $message = null, $data = null ) {
		return $this->setStatusCode( 400 )->respondWithError( $code, $message, $data );
	}

	/**
	 * 401 (Unauthorized)
	 * The request requires user authentication.
	 *
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondUnauthorized( $code = null, $message = null, $data = null ) {
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
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondForbidden( $code = null, $message = null, $data = null ) {
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
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondNotFound( $code = null, $message = null, $data = null ) {
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
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondUnprocessableEntity( $code = null, $message = null, $data = null ) {
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
	 * @param string $code
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @return WP_REST_Response
	 */
	public function respondInternalServerError( $code = null, $message = null, $data = null ) {
		if ( empty( $code ) ) {
			$code = 'rest_server_error';
		}

		if ( empty( $message ) ) {
			$message = 'Sorry, something went wrong.';
		}

		return $this->setStatusCode( 500 )->respondWithError( $code, $message, $data );
	}

	/**
	 * Format date for REST Response
	 *
	 * @param string|int|DateTime $date
	 * @param string              $type
	 *
	 * @return DateTime|int|string
	 * @throws Exception
	 */
	public static function formatDate( $date, $type = 'iso' ) {
		_deprecated_function( __FUNCTION__, '1.1.8', static::class . '::format_date()' );

		if ( ! $date instanceof DateTime ) {
			$date = new DateTime( $date );
		}

		// Format ISO 8601 date
		if ( 'iso' == $type ) {
			return $date->format( DateTime::ISO8601 );
		}

		if ( 'mysql' == $type ) {
			return $date->format( 'Y-m-d' );
		}

		if ( 'timestamp' == $type ) {
			return $date->getTimestamp();
		}

		if ( 'view' == $type ) {
			$date_format = get_option( 'date_format' );

			return $date->format( $date_format );
		}

		if ( ! in_array( $type, [ 'raw', 'mysql', 'timestamp', 'view', 'iso' ] ) ) {
			return $date->format( $type );
		}

		return $date;
	}

	/**
	 * Format date for ISO8601 (Y-m-d\TH:i:s)
	 *
	 * @param string $date_string
	 *
	 * @return string
	 */
	public static function format_date( string $date_string = 'now' ) {
		try {
			return ( new DateTime( $date_string ) )->format( 'Y-m-d\TH:i:s' );
		} catch ( Exception $e ) {
			Logger::log( $e );
		}

		return $date_string;
	}

	/**
	 * Generate pagination metadata
	 *
	 * @param int $total_items
	 * @param int $per_page
	 * @param int $current_page
	 *
	 * @return array
	 */
	public static function get_pagination_data( $total_items = 0, $per_page = 20, $current_page = 1 ) {
		$current_page = max( intval( $current_page ), 1 );
		$per_page     = max( intval( $per_page ), 1 );
		$total_items  = intval( $total_items );

		return array(
			"total_items"  => $total_items,
			"per_page"     => $per_page,
			"current_page" => $current_page,
			"total_pages"  => ceil( $total_items / $per_page ),
		);
	}

	/**
	 * Read sorting data
	 * Example ==============================================
	 * sort=<field1>+<ASC|DESC>[,<field2>+<ASC|DESC>][, ...]
	 * GET ...?sort=title+DESC
	 * GET ...?sort=title+DESC,author+ASC
	 *
	 * @param string|null $sort
	 *
	 * @return array
	 */
	public static function sanitize_sorting_data( ?string $sort = null ) {
		$sort_array = [];
		$sort_items = explode( ',', $sort );
		foreach ( $sort_items as $item ) {
			if ( strpos( $item, '+' ) == false ) {
				continue;
			}
			list( $field, $order ) = explode( '+', $item );
			$sort_array[] = [
				"field" => $field,
				"order" => strtoupper( $order ) == 'DESC' ? 'DESC' : 'ASC',
			];
		}

		return $sort_array;
	}

	/**
	 * Retrieves the query params for the collections.
	 *
	 * @return array Query parameters for the collection.
	 */
	public function get_collection_params() {
		return [
			'context'         => $this->get_context_param(),
			'page'            => [
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			],
			'per_page'        => [
				'description'       => __( 'Maximum number of items to be returned in result set.' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'search'          => [
				'description'       => __( 'Limit results to those matching a string.' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'sort'            => [
				'type' => 'string',
			],
			'_fields'         => [],
			'_fields_exclude' => [],
		];
	}
}

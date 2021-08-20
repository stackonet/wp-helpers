<?php

namespace Stackonet\WP\Framework\Traits;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

trait ApiCrudOperations {
	use ApiResponse, ApiUtils;

	/**
	 * Get store class
	 *
	 * @return DataStoreInterface
	 */
	abstract public function get_store();

	/** ==== Start - Methods from \WP_REST_Controller::class ==== **/
	abstract public function prepare_response_for_collection( $response );

	abstract public function get_context_param( $args = array() );
	/** ==== End - Methods from \WP_REST_Controller::class ==== **/

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$namespace = $this->namespace ?? '';
		$rest_base = isset( $this->rest_base ) ? trim( $this->rest_base, '/' ) : '';
		if ( empty( $namespace ) || empty( $rest_base ) ) {
			_doing_it_wrong( __FUNCTION__, 'namespace and rest_base are required.', '2.1.0' );

			return;
		}

		register_rest_route( $namespace, $rest_base, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_collection_params(),
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
			],
		] );

		register_rest_route( $namespace, $rest_base . '/(?P<id>\d+)', [
			'args' => [
				'id' => [
					'description'       => __( 'Item unique id.' ),
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
					'minimum'           => 1,
				]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
			],
		] );

		register_rest_route( $namespace, $rest_base . '/(?P<id>\d+)/trash', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'trash_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Item unique id.' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'minimum'           => 1,
					]
				],
			],
		] );

		register_rest_route( $namespace, $rest_base . '/(?P<id>\d+)/restore', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'restore_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => [
					'id' => [
						'description'       => __( 'Item unique id.' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
						'minimum'           => 1,
					]
				],
			],
		] );

		register_rest_route( $namespace, $rest_base . '/batch', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'batch_operation' ],
				'args'                => [
					'create'  => [ 'type' => 'array', 'validate_callback' => 'rest_validate_request_arg' ],
					'update'  => [ 'type' => 'array', 'validate_callback' => 'rest_validate_request_arg' ],
					'trash'   => [ 'type' => 'array', 'validate_callback' => 'rest_validate_request_arg' ],
					'restore' => [ 'type' => 'array', 'validate_callback' => 'rest_validate_request_arg' ],
					'delete'  => [ 'type' => 'array', 'validate_callback' => 'rest_validate_request_arg' ],
				],
				'permission_callback' => [ $this, 'batch_operation_permissions_check' ],
			],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$permission = $this->get_items_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$items      = $this->get_store()->find_multiple( $request->get_params() );
		$count      = $this->get_store()->count_records( $request->get_params() );
		$count      = is_numeric( $count ) ? $count : 0;
		$pagination = static::get_pagination_data( $count, $per_page, $page );

		$response = new WP_REST_Response( [
			'items'      => $items,
			'pagination' => $pagination,
			'query_args' => $request->get_params()
		] );

		return $this->respondOK( $this->prepare_response_for_collection( $response ) );

	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function create_item( $request ) {
		$permission = $this->create_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$data = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondUnprocessableEntity();
		}

		$id   = $this->get_store()->create( $data );
		$item = $this->get_store()->find_single( $id );

		return $this->respondCreated( $item );
	}

	/**
	 * Retrieves one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$permission = $this->get_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		return $this->respondOK( $item );
	}

	/**
	 * Updates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_item( $request ) {
		$permission = $this->update_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		$data = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $data ) ) {
			return $this->respondUnprocessableEntity();
		}

		$this->get_store()->update( $data );
		$item = $this->get_store()->find_single( $id );

		return $this->respondOK( $item );
	}

	/**
	 * Deletes one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$permission = $this->delete_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		$this->get_store()->delete( $id );

		return $this->respondOK( $item );
	}

	/**
	 * Trash one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function trash_item( $request ) {
		$permission = $this->update_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		if ( $this->get_store()->trash( $id ) ) {
			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Restore one item from the trash collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function restore_item( $request ) {
		$permission = $this->update_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$id   = (int) $request->get_param( 'id' );
		$item = $this->get_store()->find_single( $id );
		if ( ! ( is_array( $item ) || $item instanceof Data ) ) {
			return $this->respondNotFound( null, 'No item found.' );
		}

		if ( $this->get_store()->restore( $id ) ) {
			return $this->respondOK();
		}

		return $this->respondInternalServerError();
	}

	/**
	 * Batch operation
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function batch_operation( $request ) {
		$permission = $this->batch_operation_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $this->respondUnauthorized();
		}

		$actions = $request->get_params();
		foreach ( $actions as $action => $data ) {
			$this->get_store()->batch( $action, $data );
		}

		return $this->respondAccepted();
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Checks if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * Batch operation permission check
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function batch_operation_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * Prepares one item for create or update operation.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return mixed|WP_Error The prepared item, or WP_Error object on failure.
	 */
	protected function prepare_item_for_database( $request ) {
		return $request->get_params();
	}
}

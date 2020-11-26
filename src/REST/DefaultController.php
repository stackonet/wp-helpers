<?php

namespace Stackonet\WP\Framework\REST;

use Stackonet\WP\Framework\Abstracts\Data;
use Stackonet\WP\Framework\Interfaces\DataStoreInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

abstract class DefaultController extends ApiController {

	/**
	 * Get store class
	 *
	 * @return DataStoreInterface
	 */
	abstract public function get_store();

	/**
	 * Get route name
	 *
	 * @return string
	 */
	abstract public function get_rest_base(): string;

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		$route = trim( $this->get_rest_base(), '/' );
		register_rest_route( $this->namespace, $route, [
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

		register_rest_route( $this->namespace, $route . '/(?P<id>\d+)', [
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

		register_rest_route( $this->namespace, $route . '/batch', [
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
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
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
	 * @inheritDoc
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * @inheritDoc
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
	 * @return bool
	 */
	public function batch_operation_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to access this resource.' ) );
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function prepare_item_for_database( $request ) {
		return $request->get_params();
	}
}

<?php

namespace Stackonet\WP\Examples\REST;

use Exception;
use Stackonet\WP\Framework\REST\ApiController;
use Stackonet\WP\Framework\Supports\Attachment;
use Stackonet\WP\Framework\Supports\UploadedFile;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class MediaController extends ApiController {
	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/media', [
			[ 'methods' => WP_REST_Server::READABLE, 'callback' => [ $this, 'get_items' ], ],
			[ 'methods' => WP_REST_Server::CREATABLE, 'callback' => [ $this, 'create_item' ], ],
		] );
		register_rest_route( $this->namespace, '/media/(?P<id>\d+)', [
			[ 'methods' => WP_REST_Server::DELETABLE, 'callback' => [ $this, 'delete_item' ], ],
		] );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		if ( ! $this->can_view_media() ) {
			return $this->respondUnauthorized();
		}

		$args = array(
			'posts_per_page' => 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
		);
		if ( ! current_user_can( 'manage_options' ) ) {
			$args['author'] = get_current_user_id();
		}
		$posts_array = get_posts( $args );

		$response = [];

		if ( ! count( $posts_array ) ) {
			return $this->respondOK( $response );
		}

		foreach ( $posts_array as $item ) {
			$response[] = $this->prepare_item_for_response( $item->ID, $request );
		}

		return $this->respondOK( $response );
	}

	/**
	 * Upload a file to a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response Response object.
	 * @throws Exception
	 */
	public function create_item( $request ) {
		if ( ! $this->can_upload_media() ) {
			return $this->respondUnauthorized();
		}

		$files = UploadedFile::getUploadedFiles();

		if ( ! isset( $files['file'] ) ) {
			return $this->respondForbidden();
		}

		if ( ! $files['file'] instanceof UploadedFile ) {
			return $this->respondForbidden();
		}

		$attachment_id = Attachment::uploadSingleFile( $files['file'] );
		if ( is_wp_error( $attachment_id ) ) {
			return $this->respondUnprocessableEntity( $attachment_id->get_error_code(), $attachment_id->get_error_message() );
		}

		$token = wp_generate_password( 20, false, false );
		update_post_meta( $attachment_id, '_delete_token', $token );

		$response = $this->prepare_item_for_response( $attachment_id, $request );

		return $this->respondOK( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id    = $request->get_param( 'id' );
		$token = $request->get_param( 'token' );

		$_post = get_post( $id );

		if ( ! $_post instanceof WP_Post ) {
			return $this->respondNotFound( null, 'Image not found!' );
		}

		if ( self::can_delete_media( $_post, $token ) ) {
			return $this->respondUnauthorized();
		}

		wp_delete_post( $id, true );

		return $this->respondOK( null, [ 'deleted' => true ] );
	}

	/**
	 * Any logged in user can upload media
	 *
	 * @return bool
	 */
	public function can_view_media() {
		if ( current_user_can( 'read' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Any logged in user can upload media
	 *
	 * @return bool
	 */
	public function can_upload_media() {
		if ( current_user_can( 'read' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * If current user can delete media
	 *
	 * @param WP_Post $post
	 * @param string $token
	 *
	 * @return bool
	 */
	private static function can_delete_media( $post, $token = '' ) {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( get_current_user_id() == $post->post_author ) {
			return true;
		}

		$delete_token = get_post_meta( $post->ID, '_delete_token', true );
		if ( $token == $delete_token ) {
			return true;
		}

		return false;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	public function prepare_item_for_response( $item, $request ) {
		$image_id       = $item;
		$title          = get_the_title( $image_id );
		$token          = get_post_meta( $image_id, '_delete_token', true );
		$attachment_url = wp_get_attachment_url( $image_id );
		$image          = wp_get_attachment_image_src( $image_id, 'thumbnail' );
		$full_image     = wp_get_attachment_image_src( $image_id, 'full' );

		$response = [
			'id'             => $image_id,
			'title'          => $title,
			'token'          => $token,
			'attachment_url' => $attachment_url,
			'thumbnail'      => [ 'src' => $image[0], 'width' => $image[1], 'height' => $image[2], ],
			'full'           => [ 'src' => $full_image[0], 'width' => $full_image[1], 'height' => $full_image[2], ],
		];

		return $response;
	}
}

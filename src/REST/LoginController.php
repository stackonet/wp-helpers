<?php

namespace Stackonet\WP\Framework\REST;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) or exit;

class LoginController extends ApiController {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( 'rest_api_init', array( self::$instance, 'register_routes' ) );
		}

		return self::$instance;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/web-login', [
			[
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'login' ],
				'args'     => $this->get_login_params()
			],
		] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function login( $request ) {
		if ( is_user_logged_in() ) {
			return $this->respondUnauthorized( 'already_logged_in', 'Sorry, Your are already logged in.' );
		}

		$user_login = $request->get_param( 'username' );
		$password   = $request->get_param( 'password' );
		$remember   = (bool) $request->get_param( 'remember' );

		if ( ! ( username_exists( $user_login ) || email_exists( $user_login ) ) ) {
			return $this->respondUnprocessableEntity( null, null, [
				'user_login' => [ 'No user found with this email' ]
			] );
		}

		if ( empty( $password ) ) {
			return $this->respondUnprocessableEntity( null, null, [
				'password' => [ 'Please provide password.' ]
			] );
		}

		$credentials = array(
			'user_login'    => $user_login,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$user = wp_signon( $credentials, false );

		if ( is_wp_error( $user ) ) {
			return $this->respondUnprocessableEntity(
				$user->get_error_code(), $user->get_error_message( $user->get_error_code() ),
				[ 'password' => [ 'Password is not correct.' ] ] );
		}

		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, false );

		return $this->respondOK( [ 'action' => 'reload' ] );
	}

	/**
	 * Retrieves the query params for the login.
	 *
	 * @return array Query parameters for the login.
	 */
	public function get_login_params() {
		return array(
			'username' => array(
				'description'       => __( 'Email address or username' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'password' => array(
				'description'       => __( 'User password' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'remember' => array(
				'description'       => __( 'Remember user for long time.' ),
				'type'              => 'boolean',
				'default'           => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}

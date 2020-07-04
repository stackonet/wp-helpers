<?php

namespace Stackonet\WP\Examples\WooCommerce;

use Stackonet\WP\Framework\Supports\ArrayHelper;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class MyAccountMenu {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Endpoint
	 */
	const ENDPOINT = 'example';

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// Hook to add rewrite endpoint
			add_action( 'init', [ self::$instance, 'custom_endpoints' ] );

			// Make sure to flash rewrite endpoint on plugin activation
			add_action( 'stackonet_toolkit_activation', [ self::$instance, 'custom_endpoints' ] );
			// register_activation_hook( __FILE__, [ self::$instance, 'custom_endpoints' ] );

			// Add our endpoint to publicly allowed query vars
			add_filter( 'query_vars', [ self::$instance, 'query_vars' ] );
			// Add our endpoint to WooCommerce my-account menu list
			add_filter( 'woocommerce_account_menu_items', [ self::$instance, 'menu_items' ] );
			// Change default title
			add_filter( 'the_title', [ self::$instance, 'endpoint_title' ] );
			// Display endpoint content
			add_action( 'woocommerce_account_' . static::ENDPOINT . '_endpoint', [ self::$instance, 'content' ] );
		}

		return self::$instance;
	}

	/**
	 * Get endpoint url
	 *
	 * @return string
	 */
	public static function get_url() {
		return wc_get_account_endpoint_url( static::ENDPOINT );
	}

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function custom_endpoints() {
		add_rewrite_endpoint( static::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add new query var.
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = static::ENDPOINT;

		return $vars;
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @param array $items
	 *
	 * @return array
	 */
	public function menu_items( $items ) {
		return ArrayHelper::insert_after( $items, 'orders', [
			static::ENDPOINT => __( 'Example' ),
		] );
	}

	/**
	 * Change endpoint title.
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	public function endpoint_title( $title ) {
		global $wp_query;

		$is_endpoint = isset( $wp_query->query_vars[ static::ENDPOINT ] );

		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			// New page title.
			$title = __( 'Example' );

			remove_filter( 'the_title', [ $this, 'endpoint_title' ] );
		}

		return $title;
	}

	/**
	 * Endpoint HTML content.
	 */
	public function content() {
		echo 'example content';
	}
}

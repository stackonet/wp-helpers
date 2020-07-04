<?php

namespace Stackonet\WP\Examples\WooCommerce;

// If this file is called directly, abort.
use WC_Order;
use WC_Order_Item_Product;

defined( 'ABSPATH' ) || exit;

class CustomProductDataToOrder {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	protected static $instance;

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// Step 1: Custom Field for Product
			add_action( 'woocommerce_before_add_to_cart_button', [ self::$instance, 'add_custom_fields' ] );

			// Step 2: Add Customer Data to WooCommerce Cart
			add_filter( 'woocommerce_add_cart_item_data', [ self::$instance, 'add_cart_item_data' ], 10, 3 );

			// Step 3: Display Details as Meta in Cart
			add_filter( 'woocommerce_get_item_data', [ self::$instance, 'get_item_data' ], 10, 2 );

			// Step 4: Add Custom Details as Order Line Items
			add_action( 'woocommerce_checkout_create_order_line_item',
				[ self::$instance, 'create_order_line_item' ], 10, 4 );

			// Step 5: Display on Order detail page and (Order received / Thank you page) and Order Emails
			add_filter( 'woocommerce_order_item_get_formatted_meta_data',
				[ self::$instance, 'order_item_get_formatted_meta_data' ], 10, 2 );

			// Step 6: hide Default display of our metabox
			add_filter( 'woocommerce_hidden_order_itemmeta', [ self::$instance, 'hidden_order_itemmeta' ] );
		}

		return self::$instance;
	}

	/**
	 * Add custom field on product page
	 *
	 * @return string
	 */
	public function add_custom_fields() {
		global $product;
		ob_start();
		?>
		<div class="wdm-custom-fields">
			<label for="_custom_product_field">Custom Field</label><br/>
			<input type="text" name="_custom_product_field"/>
		</div>
		<br/>
		<?php
		$content = ob_get_contents();
		ob_end_flush();

		return $content;
	}

	/**
	 * Add custom data to cart
	 *
	 * @param array $cart_item_data
	 * @param int $product_id
	 * @param int $variation_id
	 *
	 * @return array
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( isset( $_REQUEST['_custom_product_field'] ) ) {
			$cart_item_data['_custom_product_field'] = sanitize_text_field( $_REQUEST['_custom_product_field'] );
		}

		return $cart_item_data;
	}

	/**
	 * Display information as Meta on Cart & Checkout page
	 *
	 * @param array $item_data
	 * @param array $cart_item
	 *
	 * @return array
	 */
	public function get_item_data( $item_data, $cart_item ) {
		if ( array_key_exists( '_custom_product_field', $cart_item ) ) {
			$custom_details = $cart_item['_custom_product_field'];

			$item_data[] = array(
				'key'   => 'Custom Data',
				'value' => $custom_details
			);
		}

		return $item_data;
	}

	/**
	 * Add custom data to order line item
	 *
	 * @param WC_Order_Item_Product $item
	 * @param string $cart_item_key
	 * @param array $values
	 * @param WC_Order $order
	 */
	public function create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( array_key_exists( '_custom_product_field', $values ) ) {
			$item->add_meta_data( '_custom_product_field', $values['_custom_product_field'] );
		}
	}

	/**
	 * Display on Order detail page and (Order received / Thank you page)
	 *
	 * @param array $formatted_meta
	 * @param WC_Order_Item_Product $order_item
	 *
	 * @return mixed
	 */
	public function order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {
		$data = $order_item->get_meta( '_custom_product_field', true );
		if ( ! empty( $data ) ) {
			$formatted_meta[] = (object) array(
				'display_key'   => 'Custom Data',
				'display_value' => $data,
			);
		}


		return $formatted_meta;
	}

	/**
	 * Add our meta key to hidden order item meta list to hide default display
	 *
	 * @param array $keys
	 *
	 * @return array
	 */
	public function hidden_order_itemmeta( array $keys ) {
		$keys[] = '_custom_product_field';

		return $keys;
	}
}

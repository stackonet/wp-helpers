<?php

namespace Stackonet\WP\Framework\Supports;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sanitize
 * A simple wrapper class of static methods sanitizing value.
 *
 * @package Stackonet\WP\Framework\Supports
 */
class Sanitize {
	/**
	 * Sanitize number options.
	 *
	 * @param mixed $value The value to be sanitized.
	 *
	 * @return integer|double|string
	 */
	public static function number( $value ) {
		return is_numeric( $value ) ? $value : intval( $value );
	}

	/**
	 * Sanitize float number
	 *
	 * @param mixed $value
	 *
	 * @return float
	 */
	public static function float( $value ): float {
		return floatval( $value );
	}

	/**
	 * Sanitize integer number
	 *
	 * @param mixed $value
	 *
	 * @return int
	 */
	public static function int( $value ): int {
		return intval( $value );
	}

	/**
	 * Sanitize email
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function email( $value ): string {
		return sanitize_email( $value );
	}

	/**
	 * Sanitize url
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function url( $value ): string {
		return esc_url_raw( trim( $value ) );
	}

	/**
	 * Sanitizes a string
	 * - Checks for invalid UTF-8,
	 * - Converts single `<` characters to entities
	 * - Strips all tags
	 * - Removes line breaks, tabs, and extra whitespace
	 * - Strips octets
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function text( $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitizes a multiline string
	 * The function is like sanitize_text_field(), but preserves
	 * new lines (\n) and other whitespace, which are legitimate
	 * input in textarea elements.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function textarea( $value ): string {
		return sanitize_textarea_field( $value );
	}

	/**
	 * If a field has been 'checked' or not, meaning it contains
	 * one of the following values: 'yes', 'on', '1', 1, true, or 'true'.
	 * This can be used for determining if an HTML checkbox has been checked.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public static function checked( $value ): bool {
		return in_array( $value, array( 'yes', 'on', '1', 1, true, 'true' ), true );
	}

	/**
	 * Check if the given input is a valid date.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function date( $value ): string {
		$time = strtotime( $value );

		if ( $time ) {
			return date( 'Y-m-d', $time );
		}

		return '';
	}

	/**
	 * Sanitize short block html input
	 *
	 * @param $value
	 *
	 * @return string
	 */
	public static function html( $value ): string {
		return wp_kses( $value, static::allowed_html_tags_short_block() );
	}

	/**
	 * Array of allowed html tags on short block
	 *
	 * @return array
	 */
	private static function allowed_html_tags_short_block(): array {
		return [
			'div'    => [ 'class' => [], 'id' => [], ],
			'span'   => [ 'class' => [], 'id' => [], ],
			'ol'     => [ 'class' => [], 'id' => [], ],
			'ul'     => [ 'class' => [], 'id' => [], ],
			'li'     => [ 'class' => [], 'id' => [], ],
			'p'      => [ 'class' => [], 'id' => [], ],
			'a'      => [
				'href'   => [],
				'class'  => [],
				'id'     => [],
				'rel'    => [],
				'title'  => [],
				'target' => [],
			],
			'br'     => [],
			'em'     => [],
			'strong' => [],
		];
	}

	/**
	 * Sanitize mixed content
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public static function deep( $value ) {
		if ( is_null( $value ) || empty( $value ) ) {
			return $value;
		}
		if ( is_scalar( $value ) ) {
			if ( is_numeric( $value ) ) {
				return is_float( $value ) ? floatval( $value ) : intval( $value );
			}

			// Check if value contains HTML
			if ( $value != strip_tags( $value ) ) {
				return wp_kses_post( $value );
			}

			return sanitize_textarea_field( $value );
		}

		$sanitized_value = [];
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$sanitized_value[ $index ] = static::deep( $item );
			}
		}

		return $sanitized_value;
	}
}

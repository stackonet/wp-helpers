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
	 * @return float|integer
	 */
	public static function number( $value ) {
		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		if ( preg_match( '/^\\d+\\.\\d+$/', $value ) === 1 ) {
			return floatval( $value );
		}

		return intval( $value );
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
	 * @return string|bool|int
	 */
	public static function checked( $value ) {
		$true_values  = [ 'yes', 'on', '1', 1, true, 'true' ];
		$false_values = [ 'no', 'off', '0', 0, false, 'false' ];

		return in_array( $value, array_merge( $true_values, $false_values ), true ) ? $value : '';
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
	 * Sanitize colors.
	 *
	 * @param mixed $value The color.
	 *
	 * @return string
	 */
	public static function color( $value ): string {
		// If the value is empty, then return empty.
		if ( '' === $value || ! is_string( $value ) ) {
			return '';
		}

		// Trim unneeded whitespace.
		$value = str_replace( ' ', '', $value );

		// This pattern will check and match 3/6/8-character hex, rgb, rgba, hsl, & hsla colors.
		$pattern = '/^(\#[\da-f]{3}|\#[\da-f]{6}|\#[\da-f]{8}|';
		$pattern .= 'rgba\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))\)|';
		$pattern .= 'hsla\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))\)|';
		$pattern .= 'rgb\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)\)|';
		$pattern .= 'hsl\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\))$/';

		// Return the 1st match found.
		if ( 1 === preg_match( $pattern, $value ) ) {
			return $value;
		}

		// If no match was found, return an empty string.
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
		return wp_kses_post( $value );
	}

	/**
	 * Sanitize mixed content
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public static function deep( $value ) {
		if ( empty( $value ) ) {
			return $value;
		}
		if ( is_scalar( $value ) ) {
			if ( is_numeric( $value ) ) {
				return self::number( $value );
			}

			// Check if value contains HTML tags.
			if ( $value != strip_tags( $value ) ) {
				return self::html( $value );
			}

			return self::textarea( $value );
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

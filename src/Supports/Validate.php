<?php
/**
 * This file is part of Stackonet/WP/Framework.
 *
 * (c) Stackonet Services Ltd. <info@stackonet.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @package Stackonet/WP/Framework
 */

namespace Stackonet\WP\Framework\Supports;

defined( 'ABSPATH' ) || exit;

/**
 * Class Validate
 *
 * @package Stackonet\WP\Framework\Supports
 */
class Validate {

	/**
	 * Check if the value is present.
	 *
	 * @param string|string[] $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function required( $value ): bool {
		$value = preg_replace( '/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value );

		return ! empty( $value );
	}

	/**
	 * Check if the value is formatted as a valid URL.
	 *
	 * @param mixed $value The url to be validated.
	 *
	 * @return boolean
	 */
	public static function url( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Check if the value is a valid email.
	 *
	 * @param mixed $value The email to be validated.
	 *
	 * @return boolean
	 */
	public static function email( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
	}

	/**
	 * Check if the value is an integer, including
	 * numbers within strings. 1 and '1' are both classed as integers.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function int( $value ): bool {
		if ( is_int( $value ) ) {
			return true;
		}

		return ctype_digit( $value );
	}

	/**
	 * Check if the value is a number, including numbers within strings.
	 * Numeric strings consist of optional sign, any number of digits,
	 * optional decimal part and optional exponential part.
	 * Thus, +0123.45e6 is a valid numeric value.
	 * Hexadecimal (e.g. 0xf4c3b00c),
	 * Binary (e.g. 0b10100111001),
	 * Octal (e.g. 0777) notation is allowed too
	 * but only without sign, decimal and exponential part.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function number( $value ): bool {
		return is_numeric( $value );
	}

	/**
	 * Check if the value is alphabetic letters only.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function alpha( $value ): bool {
		return ctype_alpha( $value );
	}

	/**
	 * Check if the value is alphanumeric.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function alnum( $value ): bool {
		return ctype_alnum( $value );
	}

	/**
	 * Check if the value is alphanumeric.
	 * Dashes and underscores are permitted.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function alnumdash( $value ): bool {
		if ( ! is_scalar( $value ) ) {
			return false;
		}
		$input = str_replace( [ '-', '_' ], '', $value );

		return self::alnum( $input );
	}

	/**
	 * Check if the value is an array
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function array( $value ): bool {
		return is_array( $value );
	}

	/**
	 * Check if string length is greater than or equal to given int.
	 * To check the size of a number, pass the optional number option.
	 *
	 * @param mixed $value The value to be validated.
	 * @param float|int $min_value The minimum value to be validated against.
	 * @param boolean $is_number If the value is a number.
	 *
	 * @return boolean
	 */
	public static function min( $value, $min_value, bool $is_number = false ): bool {
		if ( ! is_scalar( $value ) ) {
			return false;
		}
		if ( $is_number ) {
			return (float) $value >= (float) $min_value;
		}

		return mb_strlen( $value ) >= (int) $min_value;
	}

	/**
	 * Check if string length is less than or equal to given int.
	 * To check the size of a number, pass the optional number option.
	 *
	 * @param mixed $value The value to be validated.
	 * @param integer|float $max_value The maximum value to be validated against.
	 * @param boolean $is_number If the value is a number.
	 *
	 * @return boolean
	 */
	public static function max( $value, $max_value, bool $is_number = false ): bool {
		if ( ! is_scalar( $value ) ) {
			return false;
		}
		if ( $is_number ) {
			return (float) $value <= (float) $max_value;
		}

		return mb_strlen( $value ) <= (int) $max_value;
	}

	/**
	 * Check if the given input is a valid date.
	 *
	 * @param mixed $value The value to be validated. The value must be at ISO 8601 (YYYY-MM-DD) format.
	 *
	 * @return boolean
	 */
	public static function date( $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		return (bool) preg_match( '/^\d{4}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $value );
	}

	/**
	 * Check if the given input is a valid time.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return bool
	 */
	public static function time( $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}
		// Validate 24 hours time.
		if ( preg_match( '/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $value ) ) {
			return true;
		}

		// Validate 12 hours time.
		return (bool) preg_match( '/^(1[0-2]|0?[1-9]):[0-5][0-9] (AM|PM)$/i', $value );
	}

	/**
	 * If a field has been 'checked' or not, meaning it contains
	 * one of the following values: 'yes', 'on', '1', 1, true, or 'true'.
	 * This can be used for determining if an HTML checkbox has been checked.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function checked( $value ): bool {
		return in_array( $value, [ 'yes', 'on', '1', 1, true, 'true' ], true );
	}

	/**
	 * Check if the value is a valid IP address.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function ip( $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Check if the value is a boolean value.
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return boolean
	 */
	public static function bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return true;
		}

		return in_array( $value, [ '1', 1, true, 'true', '0', 0, false, 'false' ], true );
	}

	/**
	 * Check if value is json
	 *
	 * @param mixed $string The value to be checked.
	 *
	 * @return bool
	 */
	public static function json( $string ): bool {
		if ( ! is_string( $string ) ) {
			return false;
		}
		json_decode( $string );

		return ( json_last_error() === JSON_ERROR_NONE );
	}

	/**
	 * Validate as phone number
	 * However, this will also match numbers that are not a valid phone number.
	 *
	 * @param mixed $phone_e164 The phone number in E164 format.
	 * Format must be a number up to fifteen digits in length
	 * Starting with a ‘+’ sign, country code (1 to 3 digits), subscriber number (max 12 digits).
	 * @param int $min_length Minimum number length.
	 *
	 * @return bool
	 */
	public static function phone( $phone_e164, int $min_length = 5 ): bool {
		$min = $min_length - 1;

		return ! ! ( is_string( $phone_e164 ) && preg_match( "/^\+[1-9]\d{{$min},14}$/", $phone_e164 ) );
	}

	/**
	 * Check if the value is user username or email address
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return bool
	 */
	public static function user_login( $value ): bool {
		return is_string( $value ) && ( username_exists( $value ) || email_exists( $value ) );
	}

	/**
	 * Check if the value is user username
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return bool
	 */
	public static function username( $value ): bool {
		return is_string( $value ) && username_exists( $value );
	}

	/**
	 * Check if the value is user email address
	 *
	 * @param mixed $value The value to be validated.
	 *
	 * @return bool
	 */
	public static function user_email( $value ): bool {
		return static::email( $value ) && email_exists( $value );
	}
}

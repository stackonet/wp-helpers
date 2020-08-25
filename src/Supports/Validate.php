<?php

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
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public static function required( $value ) {
		$value = preg_replace( '/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value );

		return ! empty( $value );
	}

	/**
	 * Check if the value is formatted as a valid URL.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function url( $value ) {
		return filter_var( $value, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Check if the value is a valid email.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function email( $value ) {
		return filter_var( $value, FILTER_VALIDATE_EMAIL ) !== false;
	}

	/**
	 * Check if the value is an integer, including
	 * numbers within strings. 1 and '1' are both classed as integers.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function int( $value ) {
		return is_numeric( $value ) && (int) $value == $value;
	}

	/**
	 * Check if the value is a number, including numbers within strings.
	 * Numeric strings consist of optional sign, any number of digits,
	 * optional decimal part and optional exponential part.
	 * Thus +0123.45e6 is a valid numeric value.
	 * Hexadecimal (e.g. 0xf4c3b00c),
	 * Binary (e.g. 0b10100111001),
	 * Octal (e.g. 0777) notation is allowed too
	 * but only without sign, decimal and exponential part.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function number( $value ) {
		return is_numeric( $value );
	}

	/**
	 * Check if the value is alphabetic letters only.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function alpha( $value ) {
		return (bool) preg_match( '/^[\pL\pM]+$/u', $value );
	}

	/**
	 * Check if the value is alphanumeric.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function alnum( $value ) {
		return (bool) preg_match( '/^[\pL\pM\pN]+$/u', $value );
	}

	/**
	 * Check if the value is alphanumeric.
	 * Dashes and underscores are permitted.
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function alnumdash( $value ) {
		return (bool) preg_match( '/^[\pL\pM\pN_-]+$/u', $value );
	}

	/**
	 * Check if the value is an array
	 *
	 * @param array $value
	 *
	 * @return boolean
	 */
	public static function is_array( $value ) {
		return is_array( $value );
	}

	/**
	 * Check if string length is greater than or equal to given int.
	 * To check the size of a number, pass the optional number option.
	 *
	 * @param mixed   $value
	 * @param integer $min_value
	 * @param boolean $is_number
	 *
	 * @return boolean
	 */
	public static function min( $value, $min_value, $is_number = false ) {
		if ( $is_number ) {
			return (float) $value >= (float) $min_value;
		}

		return mb_strlen( $value ) >= (int) $min_value;
	}

	/**
	 * Check if string length is less than or equal to given int.
	 * To check the size of a number, pass the optional number option.
	 *
	 * @param mixed   $value
	 * @param integer $max_value
	 * @param boolean $is_number
	 *
	 * @return boolean
	 */
	public static function max( $value, $max_value, $is_number = false ) {
		if ( $is_number ) {
			return (float) $value <= (float) $max_value;
		}

		return mb_strlen( $value ) <= (int) $max_value;
	}

	/**
	 * Checks if the value is within the intervals defined.
	 * This check is inclusive, so 5 is between 5 and 10.
	 *
	 * @param mixed   $value
	 * @param integer $min_value
	 * @param integer $max_value
	 *
	 * @return boolean
	 */
	public static function between( $value, $min_value, $max_value ) {
		return $value >= $min_value && $value <= $max_value;
	}

	/**
	 * Check if the given input is a valid date.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public static function date( $value ) {
		if ( $value instanceof \DateTime ) {
			return true;
		}

		if ( strtotime( $value ) === false ) {
			return false;
		}

		$date = date_parse( $value );

		return checkdate( $date['month'], $date['day'], $date['year'] );
	}

	/**
	 * Check if the given input is a valid time.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function time( $value ) {
		// Validate 24 hours time
		if ( preg_match( "/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $value ) ) {
			return true;
		}

		// Validate 12 hours time
		return (bool) preg_match( '/^(1[0-2]|0?[1-9]):[0-5][0-9] (AM|PM)$/i', $value );
	}

	/**
	 * Check if the given input has a match for the regular expression given
	 *
	 * @param mixed  $value
	 * @param string $regex
	 *
	 * @return boolean
	 */
	public static function regex( $value, $regex ) {
		return (bool) preg_match( $regex, $value );
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
	public static function checked( $value ) {
		return in_array( $value, array( 'yes', 'on', '1', 1, true, 'true' ), true );
	}

	/**
	 * Check if the value is a valid IP address.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public static function ip( $value ) {
		return filter_var( $value, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Check if the value is a boolean.
	 *
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public static function bool( $value ) {
		return is_bool( $value );
	}

	/**
	 * Checks if one given input matches the other.
	 * For example, checking if password matches password_confirm.
	 *
	 * @param mixed $value
	 * @param mixed $match_value
	 *
	 * @return boolean
	 */
	public static function matches( $value, $match_value ) {
		return $value === $match_value;
	}

	/**
	 * Check if the value is user username or email address
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function user_login( $value ) {
		return username_exists( $value ) || email_exists( $value );
	}

	/**
	 * Check if the value is user username
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function username( $value ) {
		return username_exists( $value );
	}

	/**
	 * Check if the value is user email address
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function user_email( $value ) {
		return email_exists( $value );
	}
}

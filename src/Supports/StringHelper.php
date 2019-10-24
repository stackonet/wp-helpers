<?php

namespace Stackonet\WP\Framework\Supports;

class StringHelper {

	/**
	 * Encoding used for mb_*() string functions
	 */
	const MB_ENCODING = 'UTF-8';

	/**
	 * Returns true if the haystack string starts with needle
	 *
	 * Note: case-sensitive
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function str_starts_with( $haystack, $needle ) {

		if ( self::multibyte_loaded() ) {

			if ( '' === $needle ) {
				return true;
			}

			return 0 === mb_strpos( $haystack, $needle, 0, self::MB_ENCODING );
		}

		$needle = self::str_to_ascii( $needle );

		if ( '' === $needle ) {
			return true;
		}

		return 0 === strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
	}

	/**
	 * Return true if the haystack string ends with needle
	 *
	 * Note: case-sensitive
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function str_ends_with( $haystack, $needle ) {

		if ( '' === $needle ) {
			return true;
		}

		if ( self::multibyte_loaded() ) {

			return mb_substr( $haystack, - mb_strlen( $needle, self::MB_ENCODING ), null, self::MB_ENCODING ) === $needle;
		}

		$haystack = self::str_to_ascii( $haystack );
		$needle   = self::str_to_ascii( $needle );

		return substr( $haystack, - strlen( $needle ) ) === $needle;
	}

	/**
	 * Returns true if the needle exists in haystack
	 *
	 * Note: case-sensitive
	 *
	 * @param string $haystack
	 * @param string $needle
	 *
	 * @return bool
	 */
	public static function str_exists( $haystack, $needle ) {

		if ( self::multibyte_loaded() ) {

			if ( '' === $needle ) {
				return false;
			}

			return false !== mb_strpos( $haystack, $needle, 0, self::MB_ENCODING );
		}

		$needle = self::str_to_ascii( $needle );

		if ( '' === $needle ) {
			return false;
		}

		return false !== strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
	}

	/**
	 * Truncates a given $string after a given $length if string is longer than
	 * $length. The last characters will be replaced with the $omission string
	 * for a total length not exceeding $length
	 *
	 * @param string $string text to truncate
	 * @param int $length total desired length of string, including omission
	 * @param string $omission omission text, defaults to '...'
	 *
	 * @return string
	 */
	public static function str_truncate( $string, $length, $omission = '...' ) {

		if ( self::multibyte_loaded() ) {

			// bail if string doesn't need to be truncated
			if ( mb_strlen( $string, self::MB_ENCODING ) <= $length ) {
				return $string;
			}

			$length -= mb_strlen( $omission, self::MB_ENCODING );

			return mb_substr( $string, 0, $length, self::MB_ENCODING ) . $omission;
		}

		$string = self::str_to_ascii( $string );

		// bail if string doesn't need to be truncated
		if ( strlen( $string ) <= $length ) {
			return $string;
		}

		$length -= strlen( $omission );

		return substr( $string, 0, $length ) . $omission;
	}

	/**
	 * Returns a string with all non-ASCII characters removed. This is useful
	 * for any string functions that expect only ASCII chars and can't
	 * safely handle UTF-8. Note this only allows ASCII chars in the range
	 * 33-126 (newlines/carriage returns are stripped)
	 *
	 * @param string $string string to make ASCII
	 *
	 * @return string
	 */
	public static function str_to_ascii( $string ) {
		// strip ASCII chars 32 and under
		$string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );

		// strip ASCII chars 127 and higher
		return filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );
	}

	/**
	 * Return a string with insane UTF-8 characters removed, like invisible
	 * characters, unused code points, and other weirdness. It should
	 * accept the common types of characters defined in Unicode.
	 *
	 * The following are allowed characters:
	 *
	 * p{L} - any kind of letter from any language
	 * p{Mn} - a character intended to be combined with another character without taking up extra space (e.g. accents, umlauts, etc.)
	 * p{Mc} - a character intended to be combined with another character that takes up extra space (vowel signs in many Eastern languages)
	 * p{Nd} - a digit zero through nine in any script except ideographic scripts
	 * p{Zs} - a whitespace character that is invisible, but does take up space
	 * p{P} - any kind of punctuation character
	 * p{Sm} - any mathematical symbol
	 * p{Sc} - any currency sign
	 *
	 * pattern definitions from http://www.regular-expressions.info/unicode.html
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function str_to_sane_utf8( $string ) {

		$sane_string = preg_replace( '/[^\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Zs}\p{P}\p{Sm}\p{Sc}]/u', '', $string );

		// preg_replace with the /u modifier can return null or false on failure
		return ( is_null( $sane_string ) || false === $sane_string ) ? $string : $sane_string;
	}

	/**
	 * Helper method to check if the multibyte extension is loaded, which
	 * indicates it's safe to use the mb_*() string methods
	 *
	 * @return bool
	 */
	protected static function multibyte_loaded() {
		return extension_loaded( 'mbstring' );
	}
}

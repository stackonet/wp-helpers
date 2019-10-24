<?php

namespace Stackonet\WP\Framework\Supports;

defined( 'ABSPATH' ) || exit;

class ArrayHelper {
	/**
	 * Insert the given element after the given key in the array
	 *
	 * Sample usage:
	 *
	 * given:
	 * array( 'item_1' => 'foo', 'item_2' => 'bar' )
	 *
	 * array_insert_after( $array, 'item_1', array( 'item_1.5' => 'w00t' ) )
	 *
	 * becomes:
	 * array( 'item_1' => 'foo', 'item_1.5' => 'w00t', 'item_2' => 'bar' )
	 *
	 *
	 * @param array $array array to insert the given element into
	 * @param string $insert_key key to insert given element after
	 * @param array $element element to insert into array
	 *
	 * @return array
	 */
	public static function insert_after( array $array, $insert_key, array $element ) {
		$new_array = array();

		foreach ( $array as $key => $value ) {

			$new_array[ $key ] = $value;
			if ( $insert_key == $key ) {

				foreach ( $element as $k => $v ) {
					$new_array[ $k ] = $v;
				}
			}
		}

		return $new_array;
	}

	/**
	 * Create multidimensional array unique for any single key index.
	 *
	 * Sample usage:
	 *
	 * given:
	 *
	 * $details = array(
	 *      array("id"=>"1", "name"=>"Mike",    "num"=>"9876543210"),
	 *      array("id"=>"2", "name"=>"Carissa", "num"=>"08548596258"),
	 *      array("id"=>"1", "name"=>"Mathew",  "num"=>"784581254"),
	 * )
	 *
	 * ArrayHelper::unique_multidim_array( $details, 'id' )
	 *
	 * becomes:
	 * array(
	 *      array("id"=>"1","name"=>"Mike","num"=>"9876543210"),
	 *      array("id"=>"2","name"=>"Carissa","num"=>"08548596258"),
	 * )
	 *
	 * @param array $array
	 * @param string $key
	 *
	 * @return array
	 */
	public static function unique_multidim_array( array $array, $key ) {
		$temp_array = array();
		$i          = 0;
		$key_array  = array();

		foreach ( $array as $val ) {
			if ( ! in_array( $val[ $key ], $key_array ) ) {
				$key_array[ $i ]  = $val[ $key ];
				$temp_array[ $i ] = $val;
			}
			$i ++;
		}

		return $temp_array;
	}

	/**
	 * Computes the difference of arrays
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return array
	 */
	public static function array_diff_recursive( array $array1, array $array2 ) {
		$aReturn = array();

		foreach ( $array1 as $mKey => $mValue ) {
			if ( array_key_exists( $mKey, $array2 ) ) {
				if ( is_array( $mValue ) ) {
					$aRecursiveDiff = static::array_diff_recursive( $mValue, $array2[ $mKey ] );
					if ( count( $aRecursiveDiff ) ) {
						$aReturn[ $mKey ] = $aRecursiveDiff;
					}
				} else {
					if ( $mValue != $array2[ $mKey ] ) {
						$aReturn[ $mKey ] = $mValue;
					}
				}
			} else {
				$aReturn[ $mKey ] = $mValue;
			}
		}

		return $aReturn;
	}
}

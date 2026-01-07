<?php
/**
 * Array helpers.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\ArrayHelper' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class ArrayHelper {

		/**
		 * Sort array by priority key.
		 *
		 * @param array  $data Array data to sort.
		 * @param string $sort_by Array key on which base array is sorting.
		 * @since 1.0.0
		 */
		public static function sort_by_priority( $data, $sort_by = 'priority' ) {
			$sorted = array();
			if ( is_array( $data ) && count( $data ) > 0 ) {
				foreach ( $data as $key => $row ) {
					$sorted[ $key ] = isset( $row[ $sort_by ] ) ? $row[ $sort_by ] : 1;
				}
				array_multisort( $sorted, SORT_ASC, $data );
			}
			return $data;
		}

		/**
		 * Multi-dimensional array supported array values.
		 *
		 * @param array $array_values Array data to extract.
		 * @since 1.0.0
		 */
		public static function array_values( $array_values ) {
			$values = array();
			array_walk_recursive(
				$array_values,
				function ( $k ) use ( &$values ) {
					$values[] = $k;
				}
			);
			return $values;
		}

		/**
		 * Polyfill for array_key_first() function added in PHP 7.3.
		 *
		 * Get the first key of the given array without affecting
		 * the internal array pointer.
		 *
		 * @param array $array_values An array.
		 * @since 1.0.0
		 * @return string|int|null The first key of array if the array
		 *                         is not empty; `null` otherwise.
		 */
		public static function array_key_first( $array_values ) {
			if ( function_exists( 'array_key_first' ) ) {
				return array_key_first( $array_values );
			}

			foreach ( $array_values as $key => $value ) {
				return $key;
			}
		}

		/**
		 * Polyfill for `array_key_last()` function added in PHP 7.3.
		 *
		 * Get the last key of the given array without affecting the
		 * internal array pointer.
		 *
		 * @param array $array_values An array.
		 * @since 1.0.0
		 * @return string|int|null The last key of array if the array
		 * .                        is not empty; `null` otherwise.
		 */
		public static function array_key_last( $array_values ) {
			if ( function_exists( 'array_key_last' ) ) {
				return array_key_last( $array_values );
			}

			if ( empty( $array_values ) ) {
				return null;
			}

			end( $array_values );

			return key( $array_values );
		}

		/**
		 * Map multidimentional array into single dimentional array as per array key provided.
		 *
		 * @param array  $data array to map.
		 * @param string $map_key map as per this key.
		 * @param bool   $is_number Whether the value will return number or not.
		 *
		 * @since 1.0.0
		 * @since 1.2.8 Fixed returning the empty array issue (trip includes and excludes not saved) if array value consist any object data.
		 * @return array
		 */
		public static function flat_map( $data, $map_key, $is_number = false ) {
			$mapped_data = array();

			$iterator = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( json_decode( wp_json_encode( $data ), true ) ) );

			foreach ( $iterator as $key => $value ) {
				if ( $key === $map_key && isset( $value ) ) {
					$mapped_data[] = $is_number ? (int) $value : $value;
				}
			}

			return $mapped_data;
		}

		/**
		 * Parse args recursively like wp_parse_args.
		 *
		 * @param array $array1 Array value.
		 * @param array $array2 Array default values to parse.
		 * @since 1.0.0
		 * @since 1.2.4 Fixed parsing value false to default value instead of saved false value. So setting false turns into true if default value is true.
		 * @since 1.2.8 Fixed default saved values on checkout form not displaying.
		 * @return array
		 */
		public static function wp_parse_args_recursive( &$array1, $array2 ) {
			$array1 = ! $array1 ? array() : (array) $array1;
			$array2 = ! $array2 ? array() : (array) $array2;

			$result = $array2;

			if ( count( $array1 ) > 0 ) {
				foreach ( $array1 as $k => &$v ) {

					if ( is_array( $v ) && isset( $result[ $k ] ) ) {
						$result[ $k ] = self::wp_parse_args_recursive( $v, $result[ $k ] );
					} else {
						$default_value = isset( $result[ $k ] ) ? $result[ $k ] : '';
						if ( 'value' === $k ) {
							$value = $v ? $v : $default_value; // For frontend like checkout form default value.
						} else {
							$value = $v ?? $default_value; // For Backend form editor value change like enable/disable.
						}
						$result[ $k ] = $value;
					}
				}
			}
			return $result;
		}

		/**
		 * Convert array in css.
		 *
		 * @param array $array_values Style array.
		 * @since 1.0.0
		 * @return string
		 */
		public static function array_to_css( $array_values ) {
			$css = '';
			foreach ( $array_values as $property => $value ) {
				$css .= $property . ': ' . $value . ';';
			}
			return $css;
		}

		/**
		 * Like str_replace, it will replace the string in the array.
		 *
		 * @param mixed $search Search [String | Array].
		 * @param mixed $replace Replace [String | Array].
		 * @param array $array_values Array Value.
		 * @since 1.0.0
		 * @return array
		 */
		public static function str_replace( $search, $replace, &$array_values ) {
			if ( is_array( $array_values ) || is_object( $array_values ) ) {
				// Iterate through the array and perform replacements on the values.
				foreach ( $array_values as &$value ) {
					if ( is_array( $value ) ) {
						self::str_replace( $search, $replace, $value );
					} elseif ( is_string( $value ) ) {
						$value = str_replace( $search, $replace, $value );
					}
				}
			} else {
				$array_values = str_replace( $search, $replace, $array_values );
			}
			return $array_values;
		}

		/**
		 * Convert all the objects in the array will convert into array.
		 * When playing with javascript, Non indexed array value is treated as object and return object.
		 *
		 * @param array $array_values Values to conver in arrays.
		 * @return array
		 */
		public static function object_to_array( $array_values ) {
			foreach ( $array_values as &$value ) {
				if ( is_object( $value ) ) {
					// Convert the object to an array.
					$value = (array) $value;
					// And then Recursively convert inner arrays.
					$value = self::object_to_array( $value );
				} elseif ( is_array( $value ) ) {
					// Recursively convert inner arrays.
					$value = self::object_to_array( $value );
				}
			}
			return $array_values;
		}
	}
}

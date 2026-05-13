<?php
/**
 * Data trait for plugin.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Traits;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Define Trait.
 */
trait DataTrait {

	/**
	 * Prefixes the provided $key string.
	 *
	 * @param string $key      Key to be prefixed.
	 * @param bool   $has_dash Is prefix dashed.
	 * @since 1.0.0
	 * @since 1.1.8 Added has dash param.
	 * @return string
	 */
	public static function get_prefix( $key, $has_dash = false ) {
		if ( ! $key ) {
			return $key;
		}

		$prefix = 'tripzzy_';
		if ( $has_dash ) {
			$prefix = 'tripzzy-';
		}
		$key = str_replace( $prefix, '', $key ); // Remove if prefixed already.
		return "{$prefix}{$key}";
	}

	/**
	 * Converts data to json.
	 *
	 * @param mixed $data Data that needs to be converted.
	 * @return string The JSON encoded string.
	 */
	public static function data_to_json( $data ) {
		if ( is_object( $data ) || is_array( $data ) ) { // only convert to json for object or array.
			$data = wp_json_encode( $data );
			return $data;
		}
		return $data;
	}

	/**
	 * Converts JSON string to data.
	 *
	 * @param string $maybe_json JSON string that needs to be converted.
	 * @return mixed
	 */
	public static function json_to_data( $maybe_json ) {
		if ( empty( $maybe_json ) ) {
			return $maybe_json;
		}

		// WP returns meta values as array of values when using get_post_meta( $id ) without key.
		// Tripzzy expects JSON string in many metas; clone plugins sometimes store serialized/escaped strings.
		if ( is_array( $maybe_json ) ) {
			if ( 1 === count( $maybe_json ) && isset( $maybe_json[0] ) ) {
				$maybe_json = $maybe_json[0];
			} elseif ( isset( $maybe_json['0'] ) && 1 === count( $maybe_json ) ) {
				$maybe_json = $maybe_json['0'];
			} else {
				// Fallback: if it is already a structured array/object, just return it.
				return $maybe_json;
			}
		}

		// Try unserialize first (some plugins store PHP-serialized arrays/strings).
		if ( is_string( $maybe_json ) && function_exists( 'maybe_unserialize' ) ) {
			$unserialized = maybe_unserialize( $maybe_json );
			if ( $unserialized !== $maybe_json ) {
				$maybe_json = $unserialized;
			}
		}

		// If we now have an array/object, return it.
		if ( is_array( $maybe_json ) || is_object( $maybe_json ) ) {
			return $maybe_json;
		}

		// Normalize to string for JSON decoding attempts.
		if ( ! is_string( $maybe_json ) ) {
			$maybe_json = self::data_to_json( $maybe_json );
		}

		// Some clone tools add slashes/escape quotes; attempt both raw and stripped.
		$decoded = json_decode( $maybe_json, true );
		if ( null === $decoded && is_string( $maybe_json ) ) {
			$stripped = stripslashes( $maybe_json );
			if ( $stripped !== $maybe_json ) {
				$decoded = json_decode( $stripped, true );
			}
		}

		if ( ! $decoded && ! is_array( $decoded ) ) { // Do not go inside even empty array as $decoded.
			return ( $maybe_json );
		}
		return ( $decoded );
	}
}

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

		if ( ! is_string( $maybe_json ) ) {
			$maybe_json = self::data_to_json( $maybe_json );
		}
		$decoded = json_decode( $maybe_json, true );

		if ( ! $decoded && ! is_array( $decoded ) ) { // Do not go inside even empty array as $decoded.
			return ( $maybe_json );
		}
		return ( $decoded );
	}
}

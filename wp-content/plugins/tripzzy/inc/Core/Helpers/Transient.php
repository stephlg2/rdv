<?php
/**
 * Class for handling Tripzzy's transients.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Traits\DataTrait;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Transient' ) ) {

	/**
	 * Class for handling Tripzzy's transients.
	 *
	 * @since 1.0.0
	 */
	class Transient {

		use DataTrait;

		/**
		 * Wrapper method for `set_transient`.
		 *
		 * @param string  $key Transient key.
		 * @param mixed   $value Value for transient.
		 * @param integer $expiration [Optional] Expiration time for the transient.
		 * @return bool
		 */
		public static function set( $key, $value, $expiration = 0 ) {
			return set_transient( self::get_prefix( $key ), self::data_to_json( $value ), $expiration );
		}

		/**
		 * Wrapper method for `get_transient`.
		 *
		 * @param string $key Transient key.
		 * @return mixed
		 */
		public static function get( $key ) {
			return self::json_to_data( get_transient( self::get_prefix( $key ) ) );
		}

		/**
		 * Wrapper method for `delete_transient`.
		 *
		 * @param string $key Transient key.
		 * @return bool
		 */
		public static function delete( $key ) {
			return delete_transient( self::get_prefix( $key ) );
		}

		/**
		 * Returns transient's expiry timeout value.
		 *
		 * @param string $key Transient key.
		 * @return int|null
		 */
		public static function get_timeout( $key ) {
			return get_option( '_transient_timeout_' . self::get_prefix( $key ) );
		}

		/**
		 * Get all transient keys to delete transient after specific task complete.
		 *
		 * @param string $key transient key.
		 * @return array
		 */
		public static function get_all_keys( $key ) {
			global $wpdb;
			$key  = self::get_prefix( $key );
			$key  = $wpdb->esc_like( '_transient_' . $key );
			$sql  = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
			$keys = $wpdb->get_results( $wpdb->prepare( $sql, $key . '%' ), ARRAY_A ); // @phpcs:ignore

			if ( is_wp_error( $keys ) ) {
				return array();
			}

			return array_map(
				function ( $key ) {
					// Remove string '_transient_tripzzy' from the option name.
					return ltrim( $key['option_name'], '_transient_tripzzy' );
				},
				$keys
			);
		}
	}
}

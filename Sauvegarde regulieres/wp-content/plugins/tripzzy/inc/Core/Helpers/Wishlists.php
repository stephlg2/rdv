<?php
/**
 * Trips.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Wishlists' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Wishlists {

		/**
		 * Meta Key.
		 *
		 * @var $meta_key.
		 */
		const WISHLIST_METAKEY = 'wishlists';

		/**
		 * Get wishlist of user.
		 *
		 * @param int $user_id Trip ID.
		 * @since 1.0.0
		 */
		public static function get( $user_id ) {
			if ( ! $user_id ) {
				return array();
			}

			$meta = MetaHelpers::get_user_meta( $user_id, self::WISHLIST_METAKEY, true );
			if ( ! $meta ) {
				$meta = array();
			}

			if ( ! is_array( $meta ) ) {
				$meta = array( $meta );
			}

			$meta = array_values( array_filter( array_unique( array_map( 'absint', $meta ) ) ) );

			return $meta;
		}



		/**
		 * Update wishlists.
		 *
		 * @param int    $user_id Trip id to save.
		 * @param object $data  Sanitized data.
		 * @since 1.0.0
		 */
		public static function update( $user_id, $data = array() ) {
			if ( ! $user_id ) {
				return;
			}

			$data = array_map( 'absint', $data );
			$meta = self::get( $user_id );

			// Add to wishilists.
			$key = array_search( $data['trip_id'], $meta, true );
			if ( isset( $data['value'] ) && $data['value'] ) {
				if ( false === $key ) {
					$meta[] = $data['trip_id'];
				}
			} elseif ( false !== $key ) { // remove from wishlists.
					array_splice( $meta, $key, 1 );
			}

			$meta = array_values( array_filter( array_unique( array_map( 'absint', $meta ) ) ) );

			MetaHelpers::update_user_meta( $user_id, self::WISHLIST_METAKEY, $meta );
			return $meta;
		}
	}
}

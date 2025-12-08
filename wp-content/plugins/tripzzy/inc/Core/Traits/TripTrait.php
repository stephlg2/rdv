<?php
/**
 * Trip trait for plugin.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Traits;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;

/**
 * Define Trait.
 */
trait TripTrait {
	use DataTrait;

	/**
	 * Get trip data including trip id and all trip metas.
	 *
	 * @param int $trip_id Trip id.
	 * @since 1.0.0
	 * @return mixed
	 */
	public static function get_data( $trip_id = null ) {
		$trip = self::$trip;
		if ( $trip_id ) {
			$all_meta  = MetaHelpers::get_post_meta( $trip_id );
			$trip_meta = $all_meta['tripzzy_trip'] ?? array();
			$trip_meta = self::json_to_data( $trip_meta[0] ?? array() );
		} elseif ( $trip ) {
			$trip_id   = $trip->ID;
			$all_meta  = self::$all_meta;
			$trip_meta = self::$trip_meta;
		}

		if ( ! $trip_id ) {
			return;
		}
		if ( 'tripzzy' !== get_post_type( $trip_id ) ) {
			return;
		}

		foreach ( $all_meta as $i => $meta ) {
			$data           = isset( $meta[0] ) ? $meta[0] : $meta;
			$data           = self::json_to_data( $data );
			$all_meta[ $i ] = $data;
		}
		$trip_meta = self::json_to_data( $trip_meta );

		$data = array(
			'trip_id'   => $trip_id,
			'all_meta'  => $all_meta, // All meta of this post.
			'trip_meta' => $trip_meta,
		);
		return $data;
	}
}

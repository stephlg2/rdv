<?php
/**
 * Duration Data upgrade.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\DataUpgrade;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Settings;

if ( ! class_exists( 'Tripzzy\Core\DataUpgrade\Upgrade130' ) ) {
	/**
	 * Upgrade data class.
	 */
	class Upgrade130 {
		use DataTrait;

		/**
		 * Upgrade Key.
		 *
		 * @var string
		 */
		protected static $upgrade_key = 'upgrade130';

		/**
		 * Init
		 */
		public static function init() {
			if ( ! tripzzy_has_upgrade_for( '1.3.0' ) ) {
				return;
			}
			self::upgrade();
		}

		/**
		 * Upgrade Recurring Dates month data. Convert array valuse to int from string.
		 */
		public static function upgrade() {
			$upgraded = MetaHelpers::get_option( self::$upgrade_key );
			if ( ! $upgraded ) {
				global $wpdb;
				$post_type = 'tripzzy';
				$post_ids  = $wpdb->get_results( $wpdb->prepare( "SELECT ID from {$wpdb->posts} where post_type=%s and post_status in( 'publish', 'draft' )", $post_type ) ); // @phpcs:ignore

				if ( is_array( $post_ids ) ) {

					$max_duration = MetaHelpers::get_option( 'max_duration', 0 );
					foreach ( $post_ids as $trip ) {
						$trip_id  = $trip->ID;
						$trip     = new Trip( $trip_id );
						$duration = $trip->get_duration( $trip_id );
						$unit     = $duration['duration_unit'][0] ?? 'days';

						if ( 'days' === $unit ) {
							$duration_days  = $duration['duration'][0] ?? 0;
							$duration_hours = $duration_days * 24;

							if ( $duration_days > $max_duration ) {
								MetaHelpers::update_option( 'max_duration', $duration_days );
							}
						} else {
							$duration_hours = $duration['duration'][0] ?? 0;
							$duration_days  = ceil( $duration_hours / 24 );
						}
						MetaHelpers::update_post_meta( $trip_id, 'duration_hours', $duration_hours );
						MetaHelpers::update_post_meta( $trip_id, 'duration_days', $duration_days );

					}
					MetaHelpers::update_option( self::$upgrade_key, true );
				}
			}
		}
	}
}

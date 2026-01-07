<?php
/**
 * Upgrade Data.
 *
 * @since 1.1.3
 * @package tripzzy
 */

namespace Tripzzy\Core\DataUpgrade;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Trip;

if ( ! class_exists( 'Tripzzy\Core\DataUpgrade\Upgrade113' ) ) {
	/**
	 * Upgrade data class.
	 */
	class Upgrade113 {
		use DataTrait;

		/**
		 * Upgrade Key.
		 *
		 * @var string
		 */
		protected static $upgrade_key = 'upgrade113';

		/**
		 * Init
		 */
		public static function init() {
			if ( ! tripzzy_has_upgrade_for( '1.1.3' ) ) {
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
					foreach ( $post_ids as $trip ) {
						$trip_id         = $trip->ID;
						$recurring_dates = MetaHelpers::get_post_meta( $trip_id, 'recurring_dates' );

						if ( is_array( $recurring_dates ) && isset( $recurring_dates['bymonth'] ) && is_array( $recurring_dates['bymonth'] ) && count( $recurring_dates['bymonth'] ) > 0 ) {
							$recurring_dates['bymonth'] = array_map( 'intval', $recurring_dates['bymonth'] );
							MetaHelpers::update_post_meta( $trip_id, 'recurring_dates', $recurring_dates );
						}
					}
					MetaHelpers::update_option( self::$upgrade_key, true );
				}
			}
		}
	}
}

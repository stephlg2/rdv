<?php
/**
 * Fix Min max price.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\DataUpgrade;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Trip;

if ( ! class_exists( 'Tripzzy\Core\DataUpgrade\Upgrade114' ) ) {
	/**
	 * Upgrade data class.
	 */
	class Upgrade114 {
		use DataTrait;

		/**
		 * Upgrade Key.
		 *
		 * @var string
		 */
		protected static $upgrade_key = 'upgrade114';

		/**
		 * Init
		 */
		public static function init() {
			if ( ! tripzzy_has_upgrade_for( '1.1.4' ) ) {
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
						$trip_id    = $trip->ID;
						$trip       = new Trip( $trip_id );
						$category   = $trip->package_category( $trip_id );
						$trip_price = $category ? $category->get_price() : 0;
						MetaHelpers::update_post_meta( $trip_id, 'trip_price', $trip_price );

						$min_price = MetaHelpers::get_option( 'min_price', 0 );
						$max_price = MetaHelpers::get_option( 'max_price', 0 );
						if ( $trip_price < $min_price ) {
							MetaHelpers::update_option( 'min_price', $trip_price );
						}
						if ( $trip_price > $max_price ) {
							MetaHelpers::update_option( 'max_price', $trip_price );
						}
					}
					MetaHelpers::update_option( self::$upgrade_key, true );
				}
			}
		}
	}
}

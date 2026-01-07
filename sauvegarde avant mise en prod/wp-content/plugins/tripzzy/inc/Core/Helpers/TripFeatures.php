<?php
/**
 * Trip Features.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripFeatures' ) ) {

	/**
	 * Trip Features Main Class.
	 *
	 * @since 1.0.0
	 */
	class TripFeatures {
		use TripTrait;

		/**
		 * Trip Object.
		 *
		 * @var $trip.
		 */
		public static $trip;

		/**
		 * All Post Metas.
		 *
		 * @var $all_meta.
		 */
		public static $all_meta;

		/**
		 * Only trip Metas.
		 *
		 * @var $trip_meta.
		 */
		public static $trip_meta;


		/**
		 * Trip Init.
		 *
		 * @param mixed $trip either trip id or trip object.
		 */
		public function __construct( $trip = null ) {
			if ( is_object( $trip ) ) {
				self::$trip = $trip;
			} elseif ( is_numeric( $trip ) ) {
				self::$trip = get_post( $trip );
			} else {
				self::$trip = get_post( get_the_ID() );
			}
			self::$all_meta  = get_post_meta( self::$trip->ID );
			self::$trip_meta = MetaHelpers::get_post_meta( self::$trip->ID, 'trip' );
		}

		/**
		 * Get Trip features data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_features( $trip_id );
		}

		/**
		 * Render Trip features to display it in frontend.
		 *
		 * @param int     $trip_id Trip id.
		 * @param boolean $display Deiplay or return the markup.
		 * @since 1.0.0
		 * @return void
		 */
		public static function render( $trip_id = 0, $display = true ) {
			if ( ! $trip_id ) {
				global $post;
				if ( ! $post ) {
					return;
				}
				$trip_id = $post->ID;
			}
			$settings = Settings::get();
			$enabled  = ! ! $settings['enable_trip_features'];
			$enabled  = apply_filters( 'tripzzy_filter_enable_trip_features', $enabled, $trip_id );
			if ( ! $enabled ) {
				return;
			}
			$features = self::get( $trip_id ); // Saved In post meta.
			ob_start();
			do_action( 'tripzzy_before_features', $trip_id );
			if ( is_array( $features ) && count( $features ) > 0 ) : ?>
				<div class="tripzzy-booking-features">
					<ul>
						<?php foreach ( $features as $feature ) : ?>
							<li> <?php echo wp_kses_post( do_shortcode( $feature['label'] ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
			endif;
			do_action( 'tripzzy_after_features', $trip_id );
			$content = ob_get_contents();
			ob_end_clean();
			if ( ! $display ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}
	}
}

<?php
/**
 * Trip Itineraries.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Bases\TaxonomyBase;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripItineraries' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripItineraries {
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
		 * Get Trip Infos data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_itineraries( $trip_id );
		}

		/**
		 * Render Trip infos to display it in frontend.
		 *
		 * @param int     $trip_id Trip id.
		 * @param boolean $display Deiplay or return the markup.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Added Hooks before and after descrition (tripzzy_itinerary_before_description, tripzzy_itinerary_after_description).
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

			$itineraries           = self::get( $trip_id ); // Saved In post meta.
			$settings              = Settings::get();
			$enable_itinerary_date = $settings['enable_itinerary_date'];
			$enable_itinerary_time = $settings['enable_itinerary_time'];
			$section_titles        = Trip::get_section_titles( $trip_id );
			$section_title         = $section_titles['itineraries'] ?? __( 'Itineraries', 'tripzzy' );
			ob_start();

			if ( is_array( $itineraries ) && count( $itineraries ) > 0 ) : ?>
				<div class="tripzzy-section" id="tripzzy-itineraries-section">
					<h3 class="tripzzy-section-title"><span><?php echo esc_html( $section_title ); ?></span> <a href="#" class="tripzzy-accordion-expand-close" data-expand="<?php esc_html_e( 'Expand all', 'tripzzy' ); ?>" data-close="<?php esc_html_e( 'Close all', 'tripzzy' ); ?>" ><?php esc_html_e( 'Expand all', 'tripzzy' ); ?></a></h3>
					<div class="tripzzy-section-inner tripzzy-itineraries-wrapper" >
						<ul class="tripzzy-accordion tripzzy-itineraries" >
						<?php
						foreach ( $itineraries as $itinerary ) :
							$title = ! empty( $itinerary['title'] ) ? $itinerary['title'] : __( 'Untitled', 'tripzzy' );
							?>
							<li>
								<span class="accordion-title itinerary-title"><?php echo esc_html( $title ); ?></span>
								<div class="accordion-content itinerary-content">
									<?php
									do_action( 'tripzzy_itinerary_before_description', $trip_id, $itinerary, $settings );
									if ( isset( $itinerary['description'] ) ) :
										echo wp_kses_post( do_shortcode( wpautop( $itinerary['description'] ) ) );
									endif;
									do_action( 'tripzzy_itinerary_after_description', $trip_id, $itinerary, $settings );
									if ( $enable_itinerary_date && ! empty( $itinerary['itinerary_date'] ) ) :
										?>
										<span class="tz-itinerary-date">
											<strong>
												<?php echo esc_html( $itinerary['itinerary_date'] ); ?>
											</strong>

										</span>
										<?php
									endif;
									$itinerary_times = $itinerary['itinerary_times'];
									if ( $enable_itinerary_time && is_array( $itinerary_times ) && count( $itinerary_times ) > 0 ) {
										?>
										<ul class="tz-itinerary-times">
										<?php
										foreach ( $itinerary_times as $itinerary_time ) {
											$minutes = 0 === absint( $itinerary_time['minutes'] ) ? $itinerary_time['minutes'] . '0' : $itinerary_time['minutes'];
											?>
											<li> <span class="tz-itinerary-time" ><strong><?php printf( '%s:%s', esc_html( $itinerary_time['hours'] ), esc_html( $minutes ) ); ?></strong></span> <?php echo esc_html( $itinerary_time['title'] ); ?></li>
											<?php

										}
										?>

										</ul>
										<?php
									}
									?>

								</div>
							</li>
						<?php endforeach; ?>
						</ul>
					</div>
				</div>
				<?php
			endif;

			$content = ob_get_contents();
			ob_end_clean();
			if ( ! $display ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}
	}
}

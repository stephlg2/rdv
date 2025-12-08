<?php
/**
 * Trip Highlights.
 *
 * @package tripzzy
 * @since 1.0.2
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Bases\TaxonomyBase;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripHighlights' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.2
	 */
	class TripHighlights {
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
		 * @since 1.0.2
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_highlights( $trip_id );
		}

		/**
		 * Render Trip infos to display it in frontend.
		 *
		 * @param int     $trip_id Trip id.
		 * @param boolean $display Deiplay or return the markup.
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

			$highlights            = self::get( $trip_id ); // Saved In post meta.
			$settings              = Settings::get();
			$enable_itinerary_date = $settings['enable_itinerary_date'];
			$enable_itinerary_time = $settings['enable_itinerary_time'];
			$section_titles        = Trip::get_section_titles( $trip_id );
			$section_title         = $section_titles['highlights'] ?? __( 'Highlights', 'tripzzy' );
			$overview              = Trip::get_overview( $trip_id );
			ob_start();

			if ( ! empty( $highlights ) || ! empty( $overview ) ) : ?>
				<div class="tripzzy-section"  id="tripzzy-highlights-section"  >
					<h3 class="tripzzy-section-title"><span><?php echo esc_html( $section_title ); ?></span> </h3>
					<div class="tripzzy-section-inner tripzzy-overview-wrapper">
						<?php if ( is_array( $highlights ) && count( $highlights ) > 0 ) : ?>
							<ul class="tripzzy-highlights" id="tripzzy-highlights">
							<?php
							foreach ( $highlights as $highlight ) :
								$title = $highlight['title'] ?? '';
								if ( ! $title ) {
									continue;
								}
								?>
								<li>
									<span class="highlight-icon"></span>
									<span class="highlight-text"><?php echo esc_html( $title ); ?></span>
								</li>
							<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="tripzzy-overview" id="tripzzy-overview">
							<?php echo wp_kses_post( do_shortcode( $overview ) ); ?>
						</div>
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

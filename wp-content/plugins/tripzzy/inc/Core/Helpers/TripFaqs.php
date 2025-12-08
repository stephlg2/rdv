<?php
/**
 * Trip Faqs.
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

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripFaqs' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripFaqs {
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
			return Trip::get_faqs( $trip_id );
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

			$faqs           = self::get( $trip_id ); // Saved In post meta.
			$section_titles = Trip::get_section_titles( $trip_id );
			$section_title  = $section_titles['faqs'] ?? __( 'FAQs', 'tripzzy' );
			ob_start();
			if ( is_array( $faqs ) && count( $faqs ) > 0 ) : ?>
				<div class="tripzzy-section"  id="tripzzy-faqs-section">
					<h3 class="tripzzy-section-title"><span><?php echo esc_html( $section_title ); ?></span></h3>
					<a href="#" class="tripzzy-accordion-expand-close" data-expand="<?php esc_html_e( 'Expand all', 'tripzzy' ); ?>" data-close="<?php esc_html_e( 'Close all', 'tripzzy' ); ?>" ><?php esc_html_e( 'Expand all', 'tripzzy' ); ?></a>
					<div class="tripzzy-section-inner tripzzy-faqs-wrapper"  id="tripzzy-faqs" >
						<ul class="tripzzy-accordion tripzzy-faqs" >
						<?php foreach ( $faqs as $faq ) : ?>
							<li>
								<span class="accordion-title faq-question"><?php echo esc_html( $faq['question'] ); ?></span>
								<div class="accordion-content faq-answer">
									<?php echo wp_kses_post( do_shortcode( $faq['answer'] ) ); ?>
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

<?php
/**
 * Gallery.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Image;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;


if ( ! class_exists( 'Tripzzy\Core\Helpers\TripGallery' ) ) {

	/**
	 * TripGallery Helpers.
	 *
	 * @since 1.0.0
	 */
	class TripGallery {
		use SingletonTrait;

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
		 * Constructor.
		 *
		 * @param mixed $trip Either object of trip id.
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
		 * Get Trip Gallery data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_gallery( $trip_id );
		}

		/**
		 * Render Trip gallery to display it in frontend.
		 *
		 * @param mixed   $args Either Trip id or array of arguments.
		 * @param boolean $has_return Render or return the markup.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Option enable_overlay and enable lightbox added in gallery render.
		 * @return void
		 */
		public static function render( $args = null, $has_return = false ) {
			// Fallbacks.
			$title_tag         = 'h3';
			$shortcode_content = ''; // If used this method in shortcode.
			$show_title        = true;
			$trip_id           = 0;
			if ( $args && ! is_array( $args ) ) {
				$trip_id = $args;
			}
			if ( ! $trip_id ) {
				global $post;
				if ( ! $post ) {
					return;
				}
				$trip_id = $post->ID;
			}
			$section_titles = Trip::get_section_titles( $trip_id );
			$section_title  = $section_titles['gallery'] ?? __( 'Gallery', 'tripzzy' );
			if ( is_array( $args ) ) {
				$trip_id           = $args['trip_id'] ?? 0;
				$section_title     = $args['title'] ?? $section_title;
				$title_tag         = $args['title_tag'] ?? 'h3';
				$shortcode_content = $args['shortcode_content'] ?? ''; // If used this method in shortcode.
				$show_title        = (bool) $args['show_title'] ?? true;
			}
			$gallery = self::get( $trip_id );
			if ( empty( $gallery ) ) {
				return;
			}
			$settings       = Settings::get();
			$has_overlay    = (bool) ( $settings['enable_overlay'] ?? true );
			$overlay_class  = $has_overlay ? 'tz-shine-overlay' : '';
			$has_lightbox   = (bool) ( $settings['enable_lightbox'] ?? true );
			$lightbox_class = $has_lightbox ? 'tripzzy-glightbox' : '';
			ob_start();
			?>
			<div class='tripzzy-section' id="tripzzy-gallery-section" >
					<?php if ( $show_title && ! empty( $section_title ) ) : ?>
						<<?php echo esc_attr( $title_tag ); ?> class="tripzzy-section-title"><?php echo esc_html( $section_title ); ?></<?php echo esc_attr( $title_tag ); ?>>
						<?php
					endif;
					if ( is_array( $gallery ) ) {
						?>
						<div class="tripzzy-section-inner tripzzy-image-gallery">
							<ul>
							<?php
							foreach ( $gallery as $index => $image_data ) :
								?>
									<li class="<?php echo esc_attr( $overlay_class ); ?> tz-scale-image">
										<a href="<?php echo esc_url( $image_data['url'] ?? '' ); ?>" class="<?php echo esc_attr( $lightbox_class ); ?>"><?php Image::get( $image_data['id'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<?php
					}
					?>
					<?php if ( $shortcode_content ) : ?>
						<?php echo wp_kses_post( $shortcode_content ); ?>
					<?php endif; ?>
				</div>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			if ( $has_return ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}
	}
}

<?php
/**
 * Trip Taxonomies Shortcode.
 *
 * @since 1.1.3
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Helpers\TripInfos;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Image;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\TripTaxonomiesShortcode' ) ) {
	/**
	 * Trip Taxonomies Shortcode Class.
	 *
	 * @since 1.1.3
	 */
	class TripTaxonomiesShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.1.3
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIP_TAXONOMIES'; // #1.

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_shortcode_args', array( $this, 'init_args' ) );
		}

		/**
		 * Add shortcode arguments to register Shortcode from base class.
		 *
		 * @since 1.1.3
		 */
		protected static function shortcode_args() {
			$args = array(
				'shortcode' => self::$shortcode,
				'callback'  => array( 'Tripzzy\Core\Shortcodes\TripTaxonomiesShortcode', 'render' ), // #2.
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.1.3
		 */
		protected static function default_atts() {
			$settings = Settings::get();
			$atts     = array(
				'taxonomy'       => 'tripzzy_trip_destination',
				'orderby'        => 'name',
				'order'          => 'asc',
				'number'         => 4,
				'enable_overlay' => (bool) ( $settings['enable_overlay'] ?? true ),
				'hide_empty'     => false,
			);
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Additional content for the shortcode.
		 * @since 1.1.3
		 * @since 1.2.2 Option enable_overlay added in trip taxonomy shortcode render.
		 */
		public static function render( $atts, $content = '' ) {
			$atts          = self::shortcode_atts( $atts );
			$tax_attr      = array(
				'taxonomy'   => $atts['taxonomy'],
				'orderby'    => $atts['orderby'],
				'order'      => $atts['order'],
				'number'     => $atts['number'], // no of items.
				'hide_empty' => filter_var( $atts['hide_empty'], FILTER_VALIDATE_BOOLEAN ),
			);
			$tripzzy_terms = get_terms( $tax_attr );

			$has_overlay   = (bool) ( $atts['enable_overlay'] ?? true );
			$overlay_class = $has_overlay ? 'tz-shine-overlay' : '';

			ob_start();
			?>
			<div>
				<div class="tripzzy-trip-categories" >
					<div class="tz-row tripzzy-trip-category-listings">
						<?php if ( is_array( $tripzzy_terms ) && count( $tripzzy_terms ) > 0 ) : ?>
							<?php
							foreach ( $tripzzy_terms as $tripzzy_term ) :
								/* translators: %d Term count */
								$term_count    = sprintf( _n( '%d trip', '%d trips', $tripzzy_term->count, 'tripzzy' ), $tripzzy_term->count );
								$thumbnail_url = MetaHelpers::get_term_meta( $tripzzy_term->term_id, 'taxonomy_image_url' );
								if ( ! $thumbnail_url ) {
									$thumbnail_url = Image::default_thumbnail_url();
								}
								?>
								<div class="tz-col">
									<div class="tripzzy-trip-category <?php echo esc_attr( $overlay_class ); ?>">
										<div class="tripzzy-trip-category-img">
											<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $tripzzy_term->name ); ?>" />
										</div>
										<h3 class="tripzzy-trip-category-title tripzzy-trip-category-bottom-content">
											<a href="<?php echo esc_url( get_term_link( $tripzzy_term->term_id ) ); ?>">
												<?php echo esc_html( $tripzzy_term->name ); ?>
												<span class="tripzzy-trip-category-count" >
													<?php echo esc_html( $term_count ); ?>
												</span>
											</a>
										</h3>
									</div>
								</div>
							<?php endforeach; ?>	
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

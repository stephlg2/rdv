<?php
/**
 * Trips Shortcode.
 *
 * @since 1.1.6
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Template;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\TripsShortcode' ) ) {
	/**
	 * Trips Shortcode Class.
	 *
	 * @since 1.1.6
	 */
	class TripsShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.1.6
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIPS'; // #1.

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_shortcode_args', array( $this, 'init_args' ) );
		}

		/**
		 * Add shortcode arguments to register Shortcode from base class.
		 *
		 * @since 1.1.6
		 */
		protected static function shortcode_args() {
			$args = array(
				'shortcode' => self::$shortcode,
				'callback'  => array( 'Tripzzy\Core\Shortcodes\TripsShortcode', 'render' ), // #2.
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.1.6
		 */
		protected static function default_atts() {
			$atts = array(
				'orderby'          => 'title',
				'order'            => 'asc',
				'posts_per_page'   => 3,
				'featured'         => false,
				'trip_destination' => '',
				'trip_type'        => '',
				'view_mode'        => 'grid',
			);
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Additional content for the shortcode.
		 * @since 1.1.6
		 */
		public static function render( $atts, $content = '' ) {
			$atts = self::shortcode_atts( $atts );

			$args = array(
				'post_type'      => 'tripzzy',
				'paged'          => 1,
				'orderby'        => $atts['orderby'],
				'order'          => $atts['order'],
				'posts_per_page' => $atts['posts_per_page'],
			);

			// Meta Query.
			$args['meta_query'] = array(); // @phpcs:ignore
			$featured           = filter_var( $atts['featured'], FILTER_VALIDATE_BOOLEAN );

			if ( $featured ) {
				$args['meta_query'][] = array(
					'key'     => 'tripzzy_featured',
					'value'   => '1',
					'compare' => '=',
				);
			}

			// Tax Query.
			$destinations = preg_replace( '/\s*,\s*/', ',', $atts['trip_destination'] );
			$destinations = explode( ',', $destinations );
			$destinations = array_filter(
				$destinations,
				function ( $value ) {
					return ! empty( $value );
				}
			);

			$trip_types = preg_replace( '/\s*,\s*/', ',', $atts['trip_type'] );
			$trip_types = explode( ',', $trip_types );
			$trip_types = array_filter(
				$trip_types,
				function ( $value ) {
					return ! empty( $value );
				}
			);

			if ( ! empty( $destinations ) || ! empty( $trip_types ) ) {
				$args['tax_query']['relation'] = 'AND';

				if ( ! empty( $destinations ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'tripzzy_trip_destination',
						'field'    => 'slug',
						'terms'    => $destinations,
					);
				}
				if ( ! empty( $trip_types ) ) {
					$args['tax_query'][] = array(
						'taxonomy' => 'tripzzy_trip_type',
						'field'    => 'slug',
						'terms'    => $trip_types,
					);
				}
			}

			$query             = new \WP_Query( $args );
			$tripzzy_view_mode = $atts['view_mode'] ?? 'grid_view';

			ob_start();
			?>
			<div class="tripzzy-trips <?php echo esc_attr( $tripzzy_view_mode ); ?>-view">
				<div class="tz-row tripzzy-trip-listings">
					<?php
					$has_post_class = true;
					while ( $query->have_posts() ) {
						$query->the_post();
						Template::get_template_part( 'content', 'archive-tripzzy', compact( 'has_post_class' ) );
					}
					wp_reset_postdata();
					?>
				</div><!-- /.tripzzy-trip-listings -->
			</div><!-- /.tripzzy-trips -->
			
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

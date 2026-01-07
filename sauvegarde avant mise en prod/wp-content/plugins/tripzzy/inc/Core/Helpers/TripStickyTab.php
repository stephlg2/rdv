<?php
/**
 * Trip Sticky Tab.
 *
 * @package tripzzy
 * @since 1.0.2
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;


if ( ! class_exists( 'Tripzzy\Core\Helpers\TripStickyTab' ) ) {

	/**
	 * TripStickyTab Helpers.
	 *
	 * @since 1.0.2
	 */
	class TripStickyTab {
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
		 * Defalut trip tab items.
		 *
		 * @since 1.0.2
		 */
		public static function get_default_sticky_tab_items() {
			$items = array(
				array(
					'link'          => '#tripzzy-highlights-section',
					'default_label' => __( 'Highlights', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripHighlights',
					'enabled'       => true, // Enable tab and its content.
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-map-section',
					'default_label' => __( 'Map', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripMap',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-itineraries-section',
					'default_label' => __( 'Itineraries', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripItineraries',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-trip-infos-section',
					'default_label' => __( 'Trip Infos', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\Tripinfos',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-includes-excludes-section',
					'default_label' => __( 'Includes & Excludes', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripIncludesExcludes',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-gallery-section',
					'default_label' => __( 'Gallery', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripGallery',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-availability-section',
					'default_label' => __( 'Availability', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripDates',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-faqs-section',
					'default_label' => __( 'Faqs', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\TripFaqs',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
				array(
					'link'          => '#tripzzy-reviews-section',
					'default_label' => __( 'Reviews', 'tripzzy' ),
					'label'         => '',
					'render_class'  => 'Tripzzy\Core\Helpers\Reviews',
					'enabled'       => true,
					'display'       => true, // Only hide tab from sticky tab.
				),
			);
			return apply_filters( 'tripzzy_filter_sticky_tab_items', $items );
		}

		/**
		 * Get Trip Sticky Tab items data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.2
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_sticky_tab_items( $trip_id );
		}

		/**
		 * Get Trip Sticky Tab items data as per trip id for the frontend.
		 *
		 * @since 1.0.2
		 *
		 * @return array
		 */
		public static function is_enabled() {
			$settings = Settings::get();
			return ! ! $settings['enable_sticky_tab'] ?? false;
		}

		/**
		 * Render Trip Stick Tab items to display it in frontend.
		 *
		 * @param mixed   $args Either Trip id or array of arguments.
		 * @param boolean $has_return Render or return the markup.
		 *
		 * @since 1.0.2
		 *
		 * @return void
		 */
		public static function render( $args, $has_return = false ) {
			if ( ! is_array( $args ) ) {
				$trip_id = $args;
			}
			if ( ! $trip_id ) {
				global $post;
				if ( ! $post ) {
					return;
				}
				$trip_id = $post->ID;
			}

			$tabs         = self::get( $trip_id );
			$default_tabs = self::get_default_sticky_tab_items();
			if ( empty( $tabs ) ) {
				return;
			}
			ob_start();
			if ( Page::is( 'trip' ) && self::is_enabled() && count( $tabs ) > 0 ) {
				$fse_class = tripzzy_is_fse_theme() ? 'has-global-padding wp-block-block' : '';
				?>
				<nav class="tripzzy-sticky-tab <?php echo esc_attr( $fse_class ); ?>" id="tripzzy-sticky-tab">
					<div class="tripzzy-container">
						<?php
						/**
						 * Hook added to add markup before container.
						 *
						 * @since 1.0.6
						 */
						do_action( 'tripzzy_before_sticky_tab' );
						?>
						<ul class="tripzzy-sticky-tab-items">
							<?php
							foreach ( $tabs as $tab ) :
								$enabled = ! ! $tab['enabled'] ?? false;
								$display = ! ! $tab['display'] ?? false;
								if ( ! $enabled || ! $display ) {
									continue;
								}

								$render_class = $tab['render_class'] ?? '';
								if ( ! $render_class ) {
									// check in default.
									$key = array_search( $tab['link'] ?? '', array_column( $default_tabs, 'link' ) ); // @phpcs:ignore
									if ( false !== $key ) {
										$render_class = $default_tabs[ $key ]['render_class'] ?? '';
									}
								}
								if ( ! $render_class || ( $render_class && ! class_exists( $render_class ) ) ) {
									continue;
								}
								ob_start();
								call_user_func( array( $render_class, 'render' ) );
								$content = ob_get_contents();
								ob_end_clean();

								// Vérifier si le contenu est vide (en ignorant les espaces blancs)
								if ( ! trim( $content ) ) {
									continue;
								}
								$label         = $tab['label'] ?? '';
								$default_label = $tab['default_label'] ?? '';
								?>
								<li class="tripzzy-sticky-tab-item">
									<a href="<?php echo esc_attr( $tab['link'] ?? '' ); ?>" data-tripzzy-smooth-scroll><?php echo esc_attr( ! empty( $label ) ? $label : $default_label ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php
						/**
						 * Hook added to add markup before container.
						 *
						 * @since 1.0.6
						 */
						do_action( 'tripzzy_after_sticky_tab' );
						?>
					</div>
				</nav>
				<?php
			}
			$content = ob_get_contents();
			ob_end_clean();
			if ( $has_return ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}
	}
}

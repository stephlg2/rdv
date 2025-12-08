<?php
/**
 * TripMap.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\EscapeHelper;


if ( ! class_exists( 'Tripzzy\Core\Helpers\TripMap' ) ) {

	/**
	 * TripMap Helpers.
	 *
	 * @since 1.0.0
	 */
	class TripMap {
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
		 * @param mixed $trip Object | int | null.
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
		 * Map options to use it in dropdown.
		 *
		 * @param bool $label_value_format Whether data return as object format like array( 'label' => 'United States', 'value' => 'US' );.
		 * @since 1.0.0
		 */
		public static function get_map_type_options( $label_value_format = false ) {
			$lists = self::get_all();

			if ( $label_value_format ) {
				$options = array_map(
					function ( $value, $label ) {
						return array(
							'label' => $label,
							'value' => $value,
						);
					},
					array_keys( $lists ),
					array_values( $lists )
				);
			} else {
				$options = $lists;
			}
			return $options;
		}

		/**
		 * Get All Available map list.
		 *
		 * @since 1.0.0
		 */
		private static function get_all() {
			$lists = array(
				'image'  => __( 'Image', 'tripzzy' ),
				'iframe' => __( 'Iframe', 'tripzzy' ),
			);
			if ( self::is_enabled( 'google_map' ) ) {
				$lists['google_map'] = __( 'Google Map', 'tripzzy' );
			}
			return apply_filters( 'tripzzy_filter_maps', $lists );
		}

		/**
		 * Check whether map is ebabled or not.
		 *
		 * @param string $map_type Type of map [image, iframe, google_map].
		 * @since 1.0.0
		 * @return bool
		 */
		public static function is_enabled( $map_type = '' ) {
			if ( ! $map_type ) {
				return;
			}
			$settings = Settings::get();
			switch ( $map_type ) :
				// Always true for image and iframe type.
				case 'image':
				case 'iframe':
					return true;
				case 'google_map':
					return ! ! isset( $settings['enable_google_map'] ) && $settings['enable_google_map'] && $settings['google_map_api_key'];
			endswitch;
		}

		/**
		 * Get Trip Map data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_map( $trip_id );
		}

		/**
		 * Render Trip infos to display it in frontend.
		 *
		 * @param mixed   $args Either Trip id or array of arguments.
		 * @param boolean $has_return Render or return the markup.
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
			$section_title  = $section_titles['map'] ?? __( 'Map', 'tripzzy' );
			if ( is_array( $args ) ) {
				$trip_id           = $args['trip_id'] ?? 0;
				$section_title     = $args['title'] ?? $section_title;
				$title_tag         = $args['title_tag'] ?? 'h3';
				$shortcode_content = $args['shortcode_content'] ?? ''; // If used this method in shortcode.
				$show_title        = ! ! $args['show_title'] ?? true;
			}

			$settings = Settings::get();
			$map_data = self::get( $trip_id );

			if ( empty( $map_data ) ) {
				return;
			}
			$map_type          = $map_data['map_type'];
			$map_specific_data = null;
			switch ( $map_type ) {
				case 'iframe':
					$map_specific_data = $map_data['map_iframe'];
					break;
				case 'image':
					$map_specific_data = $map_data['map_image'];

					break;
				case 'google_map':
					$map_specific_data = $settings['google_map_api_key'] ?? '';
					break;
			}
			if ( ! $map_specific_data ) {
				return;
			}
			ob_start();
			?>
			<div class='tripzzy-section' id="tripzzy-map-section">
				<?php if ( $show_title && ! empty( $section_title ) ) : ?>
					<<?php echo esc_attr( $title_tag ); ?> class="tripzzy-section-title"><?php echo esc_html( $section_title ); ?></<?php echo esc_attr( $title_tag ); ?>>
					<?php
				endif;
				?>
				<div class="tripzzy-section-inner tripzzy-trip-map">
					<?php

					$allowed_html = EscapeHelper::get_allowed_html();
					switch ( $map_type ) {
						case 'iframe':
							echo wp_kses( $map_specific_data, $allowed_html );
							break;
						case 'image':
							$map_image = $map_specific_data;
							$url       = $map_image[0]['url'] ?? '';
							if ( $url ) {
								?>
								<img src="<?php echo esc_url( $url ); ?>" />
								<?php
							}
							break;
						case 'google_map':
							$values = array(
								'key'         => $map_specific_data, // google map api key.
								'map_lat'     => $map_data['map_lat'] ?? '',
								'map_lng'     => $map_data['map_lng'] ?? '',
								'map_zoom'    => $map_data['map_zoom'] ?? '',
								'map_markers' => $map_data['map_markers'] ?? '',
							);
							?>
							<div id="tripzzy-google-map" data-map-data="<?php echo esc_attr( trim( wp_json_encode( $values ) ) ); ?>" ></div>
							<?php
							break;
					}
					?>
				</div>
				<?php
				if ( $shortcode_content ) :
					echo wp_kses( $shortcode_content, $allowed_html );
				endif;
				?>
			</div>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			if ( $has_return ) {
				return $content;
			}
			echo wp_kses( $content, $allowed_html );
		}
	}
}

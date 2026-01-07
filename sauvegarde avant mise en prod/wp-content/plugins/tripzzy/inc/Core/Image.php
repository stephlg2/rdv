<?php
/**
 * Tripzzy Images.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'Tripzzy\Core\Image' ) ) {
	/**
	 * Tripzzy Images.
	 *
	 * @since 1.0.0
	 */
	class Image {

		/**
		 * Get image by image id.
		 *
		 * @param int   $image_id Image id.
		 * @param array $image_size Default Image size for thumbnail.
		 * @since 1.0.0
		 */
		public static function get( $image_id, $image_size = 'tripzzy_thumbnail' ) {
			if ( ! $image_id ) {
				return;
			}
			$image_data = wp_get_attachment_image_src( $image_id, $image_size );
			if ( ! $image_data ) {
				return;
			}
			$src       = $image_data[0];
			$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$title     = get_the_title( $image_id );
			$image_alt = $image_alt ? $image_alt : $title;

			?>
			<img alt="<?php echo esc_attr( $image_alt ); ?>" src="<?php echo esc_url( $src ); ?>" title="<?php echo esc_attr( $title ); ?>"/>
			<?php
		}

		/**
		 * Get thumbnail by trip id.
		 *
		 * @param int   $trip_id Image id.
		 * @param array $image_size Default Image size for thumbnail.
		 * @since 1.0.0
		 */
		public static function get_thumbnail( $trip_id, $image_size = 'tripzzy_thumbnail' ) {
			if ( ! $trip_id ) {
				return;
			}

			$image_id = get_post_thumbnail_id( $trip_id );

			if ( ! $image_id ) {
				self::default_thumbnail();
				return;
			}

			$image_data = wp_get_attachment_image_src( $image_id, $image_size );
			if ( ! $image_data ) {
				self::default_thumbnail();
				return;
			}
			$src       = $image_data[0];
			$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$title     = get_the_title( $image_id );
			$image_alt = $image_alt ? $image_alt : $title;
			?>
			<img alt="<?php echo esc_attr( $image_alt ); ?>" src="<?php echo esc_url( $src ); ?>" title="<?php the_title(); ?>"/>
			<?php
		}

		/**
		 * Returns thumbnail url by trip id.
		 *
		 * @param int   $trip_id Image id.
		 * @param array $image_size Default Image size for thumbnail.
		 * @return string
		 */
		public static function get_thumbnail_url( $trip_id, $image_size = 'tripzzy_thumbnail' ) {
			$url = get_the_post_thumbnail_url( $trip_id, $image_size );
			return $url ? $url : self::default_thumbnail_url();
		}

		/**
		 * Get all registered image sizes.
		 *
		 * @since 1.3.0
		 * @return array
		 */
		public static function get_image_sizes() {
			global $_wp_additional_image_sizes;

			$sizes = array();

			foreach ( get_intermediate_image_sizes() as $_size ) {
				if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {

					$sizes[ $_size ]['label']  = ucwords( str_replace( '_', ' ', $_size ) );
					$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
					$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
					$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
				} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						'label'  => ucwords( str_replace( '_', ' ', $_size ) ),
						'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
						'height' => $_wp_additional_image_sizes[ $_size ]['height'],
						'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
					);
				}
			}

			return $sizes;
		}

		/**
		 * List of image size.
		 *
		 * @since 1.0.0
		 */
		private static function image_sizes() {
			$sizes = array(
				'tripzzy_thumbnail'        => array(
					'width'  => 520,
					'height' => 390,
					'crop'   => true,
					'label'  => __( 'Tripzzy Thumbnail', 'tripzzy' ),
				),
				'tripzzy_slider_thumbnail' => array(
					'width'  => 848,
					'height' => 420,
					'crop'   => true,
					'label'  => __( 'Tripzzy Slider Thumbnail', 'tripzzy' ),
				),
			);
			return apply_filters( 'tripzzy_filter_image_sizes', $sizes );
		}

		/**
		 * Fallback Thumbnail image.
		 *
		 * @param array $args Default fallback thumbnail args.
		 *
		 * @since 1.0.0
		 * @since 1.0.8 Added Default Thumbnail arguments.
		 */
		public static function default_thumbnail( $args = array() ) {

			$size   = $args['size'] ?? 'default';
			$width  = $args['width'] ?? '';
			$height = $args['height'] ?? '';
			$title  = $args['title'] ?? get_the_title();
			$url    = self::default_thumbnail_url( $size );

			// need to pass image size as param to use multiple sizes.
			?>
			<img alt="<?php echo esc_attr( $title ); ?>" src="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $title ); ?>" width="<?php echo esc_attr( $width ); ?>" height="<?php echo esc_attr( $height ); ?>" />
			<?php
		}

		/**
		 * Returns url of fallback thumbnail image.
		 *
		 * @param string $size Default thumbnail size.
		 * @since 1.0.0
		 * @since 1.0.8 Added Default Thumbnail size.
		 * @return string
		 */
		public static function default_thumbnail_url( $size = 'thumbnail' ) {
			$url = sprintf( '%sassets/images/thumbnail.jpg', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
			if ( 'thumbnail-small' === $size ) {
				$url = sprintf( '%sassets/images/thumbnail-small.jpg', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
			}
			return set_url_scheme( apply_filters( 'tripzzy_filter_default_thumbnail_url', $url ) );
		}

		/**
		 * Add Images Sizes for Tripzzy.
		 *
		 * @since 1.0.0
		 */
		public static function add_image_sizes() {
			$sizes = self::image_sizes();

			if ( is_array( $sizes ) && count( $sizes ) > 0 ) {
				foreach ( $sizes as $name => $data ) {
					add_image_size( $name, $data['width'], $data['height'], $data['crop'] );
				}
			}
		}

		/**
		 * List image size in image block.
		 *
		 * @param array $image_sizes List of available image sizes including default image size.
		 * @since 1.0.0
		 */
		public static function list_image_sizes( $image_sizes ) {
			$sizes = self::image_sizes();
			if ( is_array( $sizes ) && count( $sizes ) > 0 ) {
				foreach ( $sizes as $name => $data ) {
					$image_sizes[ $name ] = $data['label'];
				}
			}
			return $image_sizes;
		}

		/**
		 * Undocumented function
		 *
		 * @param int   $img_id Image ID of Taxonomy.
		 * @param array $args Taxonomy Image args.
		 * @since 1.0.8
		 * @return string
		 */
		public static function get_taxonomy_thumbnail( $img_id = '', $args = array() ) {
			if ( $img_id ) {
				$width  = $args['width'] ?? 60;
				$height = $args['height'] ?? 45;
				return wp_get_attachment_image( $img_id, array( $width, $height ) );
			}
			return self::default_thumbnail( $args );
		}
	}
}

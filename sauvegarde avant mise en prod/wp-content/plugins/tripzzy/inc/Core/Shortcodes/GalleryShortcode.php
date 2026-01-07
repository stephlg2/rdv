<?php
/**
 * Tripzzy Shortcode
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\TripGallery;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\GalleryShortcode' ) ) {
	/**
	 * Tripzzy Trip Gallery Class.
	 *
	 * @since 1.0.0
	 */
	class GalleryShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_GALLERY'; // #1.

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_shortcode_args', array( $this, 'init_args' ) );
		}

		/**
		 * Add shortcode arguments to register Shortcode from base class.
		 *
		 * @since 1.0.0
		 */
		protected static function shortcode_args() {
			$args = array(
				'shortcode' => self::$shortcode,
				'callback'  => array( 'Tripzzy\Core\Shortcodes\GalleryShortcode', 'render' ), // #2.
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.0.0
		 */
		protected static function default_atts() {
			$atts = array(
				'trip_id'    => 0,
				'title'      => __( 'Gallery', 'tripzzy' ),
				'title_tag'  => 'h3',
				'show_title' => true,
			);
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $shortcode_content Additional content for the shortcode.
		 * @since 1.0.0
		 */
		public static function render( $atts, $shortcode_content = '' ) {
			if ( ! isset( $atts['trip_id'] ) ) {
				return;
			}
			$atts                      = self::shortcode_atts( $atts );
			$atts['shortcode_content'] = $shortcode_content;
			return TripGallery::render( $atts['trip_id'], true );
		}
	}
}

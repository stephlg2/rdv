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
use Tripzzy\Core\Helpers\TripInfos;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\TripInfosShortcode' ) ) {
	/**
	 * Trip Info Shortcode Class.
	 *
	 * @since 1.0.0
	 */
	class TripInfosShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIP_INFOS'; // #1.

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\TripInfosShortcode', 'render' ), // #2.
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
				'trip_id' => 0,
			);
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Additional content for the shortcode.
		 * @since 1.0.0
		 */
		public static function render( $atts, $content = '' ) {
			if ( ! isset( $atts['trip_id'] ) ) {
				return;
			}
			$atts    = self::shortcode_atts( $atts );
			$content = TripInfos::render( $atts['trip_id'], true );

			return $content;
		}
	}
}

<?php
/**
 * Base Class For Tripzzy Shortcode Type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Bases;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Http\Nonce;

if ( ! class_exists( 'Tripzzy\Core\Bases\ShortcodeBase' ) ) {
	/**
	 * Base Class.
	 *
	 * @since 1.0.0
	 */
	class ShortcodeBase {
		use SingletonTrait;

		/**
		 * Shortcode arguments
		 *
		 * @var array
		 * @since 1.0.0
		 */
		private static $shortcode_args = array();

		/**
		 * Initialize Shortcode.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			// Add Shortcode.
			self::$shortcode_args = apply_filters( 'tripzzy_filter_shortcode_args', self::$shortcode_args );
			if ( is_array( self::$shortcode_args ) && ! empty( self::$shortcode_args ) ) {
				foreach ( self::$shortcode_args as $shortcode => $args ) {
					if ( ! shortcode_exists( $shortcode ) ) {
						add_shortcode( $shortcode, $args['callback'] );
					}
				}
			}
		}

		/**
		 * Get the Shortcode Key defined in the child class.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function get_key() {
			return static::$shortcode;
		}

		/**
		 * Shortcode arguments to create new Shortcode.
		 *
		 * @param array $shortcode_args Array arguments.
		 *
		 * @since 1.0.0
		 */
		public function init_args( $shortcode_args ) {
			$shortcode_args[ static::$shortcode ] = static::shortcode_args();
			return $shortcode_args;
		}

		/**
		 * Shortcode atts.
		 *
		 * @param array $atts Shortcode attributes.
		 * @since 1.0.0
		 */
		protected static function shortcode_atts( $atts ) {
			return shortcode_atts(
				static::default_atts(),
				$atts,
				static::$shortcode
			);
		}
	}
}

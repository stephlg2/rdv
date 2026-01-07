<?php
/**
 * Tripzzy Shortcode for trip search result page.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Template;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\TripSearchResultPageShortcode' ) ) {
	/**
	 * Tripzzy Search Result Class.
	 *
	 * @since 1.0.0
	 */
	class TripSearchResultPageShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIP_SEARCH_RESULT';

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\TripSearchResultPageShortcode', 'render' ),
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.0.0
		 */
		protected static function default_atts() {
			$atts = array();
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @since 1.0.0
		 */
		public static function render() {
			return ''; // Render handle by template redirect hook.
		}
	}
}

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
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\SearchForm;
use Tripzzy\Core\Template;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\TripSearchShortcode' ) ) {
	/**
	 * Tripzzy Search Form Class.
	 *
	 * @since 1.0.0
	 */
	class TripSearchShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIP_SEARCH';

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\TripSearchShortcode', 'render' ),
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Attributes added.
		 */
		protected static function default_atts() {
			$atts = array(
				'search_text'          => __( 'Search', 'tripzzy' ),
				'hide_price'           => false,
				'hide_destination'     => false,
				'hide_trip_type'       => false,
				'hide_trip_activities' => false,
			);
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Additional content for the shortcode.
		 * @since 1.0.0
		 * @since 1.1.6 Filter form as per Attributes and added button text.
		 * [hide_price, hide_destination, hide_trip_type, hide_trip_activities].
		 */
		public static function render( $atts, $content = '' ) {

			$atts = self::shortcode_atts( $atts );

			$fields = SearchForm::get_fields();

			// Hide form fields.
			$hide_price           = filter_var( $atts['hide_price'], FILTER_VALIDATE_BOOLEAN );
			$hide_destination     = filter_var( $atts['hide_destination'], FILTER_VALIDATE_BOOLEAN );
			$hide_trip_type       = filter_var( $atts['hide_trip_type'], FILTER_VALIDATE_BOOLEAN );
			$hide_trip_activities = filter_var( $atts['hide_trip_activities'], FILTER_VALIDATE_BOOLEAN );

			if ( $hide_price ) {
				$fields['tripzzy_price']['enabled'] = false;
			}
			if ( $hide_destination ) {
				$fields['destination']['enabled'] = false;
			}
			if ( $hide_trip_type ) {
				$fields['trip_type']['enabled'] = false;
			}
			if ( $hide_trip_activities ) {
				$fields['trip_activities']['enabled'] = false;
			}

			$attributes = array(
				'searchText' => $atts['search_text'],
				'fields'     => $fields,
			);
			ob_start();
			Template::get_template( 'trip-search', $attributes );
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

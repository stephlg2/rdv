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

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\TripOverviewShortcode' ) ) {
	/**
	 * Tripzzy Trip Overview Class.
	 *
	 * @since 1.0.0
	 */
	class TripOverviewShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIP_OVERVIEW';

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\TripOverviewShortcode', 'render' ),
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
			$trip_id = $atts['trip_id'];
			ob_start();
			?>
				<div class='tripzzy-trip-overview'>
					<?php echo wp_kses_post( do_shortcode( Trip::get_overview( $trip_id ) ) ); ?>
				</div>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

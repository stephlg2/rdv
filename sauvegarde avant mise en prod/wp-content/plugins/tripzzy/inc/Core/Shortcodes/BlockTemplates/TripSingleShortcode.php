<?php
/**
 * Tripzzy Shortcode For Blocks Section. A Legacy Shortcode for single page.
 *
 * @since 1.0.6
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes\BlockTemplates;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Template;
use Tripzzy\Core\Helpers\Strings;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\BlockTemplates\TripSingleShortcode' ) ) {
	/**
	 * Checkout Shortcode Class.
	 *
	 * @since 1.0.6
	 */
	class TripSingleShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.6
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_TRIP_SINGLE_BLOCK';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'tripzzy_filter_shortcode_args', array( $this, 'init_args' ) );
		}

		/**
		 * Add shortcode arguments to register Shortcode from base class.
		 *
		 * @since 1.0.6
		 */
		protected static function shortcode_args() {
			$args = array(
				'shortcode' => self::$shortcode,
				'callback'  => array( 'Tripzzy\Core\Shortcodes\BlockTemplates\TripSingleShortcode', 'render' ),
			);
			return $args;
		}

		/**
		 * Default Shortcode attributes list.
		 *
		 * @since 1.0.6
		 */
		protected static function default_atts() {
			$atts = array();
			return $atts;
		}

		/**
		 * Render Shortcode content.
		 *
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Additional content for the shortcode.
		 * @since 1.0.6
		 */
		public static function render( $atts, $content = '' ) {
			ob_start();
			?>
			<?php do_action( 'tripzzy_before_main_content' ); ?>
			<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
				<?php
				while ( have_posts() ) :
					the_post();
					Template::get_template_part( 'content', 'single-tripzzy' );
				endwhile; // end of the loop.
				?>
			</div>
			<?php
			do_action( 'tripzzy_after_main_content' );
			$content = ob_get_contents();
			ob_end_clean();
			$content = Strings::trim_nl( $content );
			return $content;
		}
	}
}

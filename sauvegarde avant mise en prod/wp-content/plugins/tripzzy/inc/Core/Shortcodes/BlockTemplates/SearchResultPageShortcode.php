<?php
/**
 * Tripzzy Shortcode For Blocks Section. A Legacy Shortcode for search result page.
 *
 * @since 1.0.6
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes\BlockTemplates;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\TripFilter;


if ( ! class_exists( 'Tripzzy\Core\Shortcodes\BlockTemplates\SearchResultPageShortcode' ) ) {
	/**
	 * Checkout Shortcode Class.
	 *
	 * @since 1.0.6
	 */
	class SearchResultPageShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.6
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_SEARCH_RESULT_BLOCK';

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\BlockTemplates\SearchResultPageShortcode', 'render' ),
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
			$tripzzy_view_mode = TripFilter::get_view_mode();
			$images_url        = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
			ob_start();
			do_action( 'tripzzy_before_main_content' ); ?>
			<section class="tripzzy-section">
				<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
					<div class="tz-row">
						<div class="tz-col tz-cols-3-lg">
							<?php do_action( 'tripzzy_archive_before_content' ); ?>
						</div>
						<div class="tz-col tz-cols-9-lg">
							<?php tripzzy_render_archive_toolbar(); ?>
							<div class="tripzzy-trips <?php echo esc_attr( $tripzzy_view_mode ); ?>-view">
								<?php do_action( 'tripzzy_archive_before_listing' ); ?>
								<div id="tripzzy-trip-listings" class="tz-row tripzzy-trip-listings" ></div><!-- /tripzzy-trip-listings -->
								<?php do_action( 'tripzzy_archive_after_listing' ); ?>
							</div>
						</div>
						<?php do_action( 'tripzzy_archive_after_content' ); ?>
					</div>
				</div>
			</section>
			<?php
			tripzzy_render_archive_list_item_template();
			do_action( 'tripzzy_after_main_content' );
			$content = ob_get_contents();
			ob_end_clean();
			$content = Strings::trim_nl( $content );
			return $content;
		}
	}
}

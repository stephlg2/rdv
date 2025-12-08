<?php
/**
 * Tripzzy Shortcode For Blocks Section. A Legacy Shortcode for Thankyou page.
 *
 * @since 1.0.6
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes\BlockTemplates;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\ErrorMessage;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\BlockTemplates\ThankyouPageShortcode' ) ) {
	/**
	 * Checkout Shortcode Class.
	 *
	 * @since 1.0.6
	 */
	class ThankyouPageShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.6
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_THANKYOU_BLOCK';

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\BlockTemplates\ThankyouPageShortcode', 'render' ),
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
		 * @since 1.1.5 Fixed link expired.
		 */
		public static function render( $atts, $content = '' ) {
			ob_start();
			do_action( 'tripzzy_before_main_content' ); ?>
			<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
				<div class="tripzzy-content">
					<div class="tripzzy-thank-you">
						<?php

						$tm_errors  = array();
						$key        = get_query_var( 'tripzzy_key' );
						$booking_id = get_query_var( 'booking_id' );
						$saved_key  = MetaHelpers::get_post_meta( $booking_id, 'key' );
						if ( ! $booking_id ) {
							$error_message = ErrorMessage::get( 'invalid_booking_id' );
							$tm_errors     = $error_message->errors;
						} elseif ( ! $key ) {
							$error_message = ErrorMessage::get( 'page_expired' );
							$tm_errors     = $error_message->errors;
						} elseif ( $key !== $saved_key ) {
							$error_message = ErrorMessage::get( 'invalid_key' );
							$tm_errors     = $error_message->errors;
						}
						if ( count( $tm_errors ) > 0 ) {
							?>
							<span class="tripzzy-error">
								<?php
								foreach ( $tm_errors as $tm_error ) {
									echo esc_html( $tm_error[0] );
								}
								?>
							</span>
							<?php
						} else {
							while ( have_posts() ) :
								the_post();
								the_content();
							endwhile; // end of the loop.
						}
						?>
					</div>
				</div>
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

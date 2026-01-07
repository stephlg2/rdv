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
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\Helpers\Notice;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Cart;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Bookings;


if ( ! class_exists( 'Tripzzy\Core\Shortcodes\ThankyouPageShortcode' ) ) {
	/**
	 * Thankyou page Shortcode Class.
	 *
	 * @since 1.0.0
	 */
	class ThankyouPageShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_THANKYOU'; // #1.

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\ThankyouPageShortcode', 'render' ), // #2.
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
		 * @since 1.1.8 Booking Summary and hooks added.
		 */
		public static function render() {
			$content    = '';
			$booking_id = get_query_var( 'booking_id' );

			ob_start();
			Bookings::render_booking_summary( $booking_id );

			/**
			 * Hook to add text before booking detail table on thankyou page.
			 *
			 * @since 1.1.8
			 */
			do_action( 'tripzzy_thankyou_page_before_booking_details', $booking_id );
			?>
			<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Booking Detail', 'tripzzy' ); ?></label>
			<?php
			Bookings::render_booking_trips( $booking_id );
			/**
			 * Hook to add text after booking detail table on thankyou page.
			 *
			 * @since 1.1.8
			 */
			do_action( 'tripzzy_thankyou_page_after_booking_details', $booking_id );
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

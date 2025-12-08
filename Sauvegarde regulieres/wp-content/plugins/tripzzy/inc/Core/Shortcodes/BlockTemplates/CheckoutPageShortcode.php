<?php
/**
 * Tripzzy Shortcode For Blocks Section. A Legacy Shortcode for Checkout page.
 *
 * @since 1.0.6
 * @since 1.2.0 Added option to show/hide apply coupon along with position.
 * @package tripzzy
 */

namespace Tripzzy\Core\Shortcodes\BlockTemplates;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;
use Tripzzy\Core\Bases\ShortcodeBase;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Coupon;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Image;
if ( ! class_exists( 'Tripzzy\Core\Shortcodes\BlockTemplates\CheckoutPageShortcode' ) ) {
	/**
	 * Checkout Shortcode Class.
	 *
	 * @since 1.0.6
	 */
	class CheckoutPageShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.6
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_CHECKOUT_BLOCK';

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\BlockTemplates\CheckoutPageShortcode', 'render' ),
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
			$cart          = tripzzy()->cart;
			$cart_contents = $cart->get_cart_contents();
			$cart_totals   = $cart->get_totals();
			$coupon_code   = Coupon::get_applied_coupon_code();
			$input_attr    = ! empty( $coupon_code ) ? 'disabled' : '';
			$settings      = Settings::get();
			ob_start();
			do_action( 'tripzzy_before_main_content' ); ?>
			<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
				<div class="tz-row">
						<?php do_action( 'tripzzy_before_checkout_form' ); ?>
					<div class="tz-col tz-cols-7-lg tz-cols-8-xl">
						<div class="tripzzy-checkout-form">
							<?php
							$hide_coupon     = ! ! ( $settings['hide_coupon_on_checkout'] ?? false );
							$coupon_position = $settings['coupon_position'] ?? 'left';
							if ( ! $hide_coupon && 'left' === $coupon_position ) {
								Template::get_template_part( 'layouts/default/partials/coupon', 'form' );
							}
							while ( have_posts() ) :
								the_post();
								the_content();
							endwhile; // end of the loop.
							?>
						</div>
					</div>
					<?php Template::get_template_part( 'layouts/default/partials/mini', 'cart' ); ?>
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

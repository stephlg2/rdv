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
use Tripzzy\Core\Helpers\Currencies;
use Tripzzy\Core\Payment\PaymentGateways;
use Tripzzy\Core\Cart;

if ( ! class_exists( 'Tripzzy\Core\Shortcodes\CheckoutPageShortcode' ) ) {
	/**
	 * Checkout Shortcode Class.
	 *
	 * @since 1.0.0
	 */
	class CheckoutPageShortcode extends ShortcodeBase {
		/**
		 * Shortcode name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $shortcode = 'TRIPZZY_CHECKOUT'; // #1.

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
				'callback'  => array( 'Tripzzy\Core\Shortcodes\CheckoutPageShortcode', 'render' ), // #2.
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
		 * @param array  $atts Shortcode attributes.
		 * @param string $content Additional content for the shortcode.
		 * @since 1.0.0
		 * @since 1.1.8 Payment description added.
		 * @since 1.2.3 Added class tripzzy-payment-mode-input in checkbox input.
		 */
		public static function render( $atts, $content = '' ) {
			$cart  = tripzzy()->cart;
			$items = $cart->get_cart_contents();

			if ( empty( $items ) ) {
				ob_start();
				/* translators: %s is the trips url. */
				$notice = sprintf( __( 'Please add <a href="%s">trips</a> in the cart first!', 'tripzzy' ), esc_url( Page::get_url( 'trips' ) ) );
				Notice::render( $notice );
				$content = ob_get_contents();
				ob_end_clean();
				return $content;
			}
			$atts = self::shortcode_atts( $atts );

			$cart_totals = $cart->get_totals();

			ob_start();
			if ( PaymentGateways::is_test_mode() ) {
				?>
				<div class="tripzzy-test-mode-notice">
					<div class="arrow-right"><span title="<?php esc_html_e( 'You are currently in sandbox mode. This box only visible in sandbox mode.', 'tripzzy' ); ?>"><?php esc_html_e( 'Test Mode', 'tripzzy' ); ?></span></div>
				</div>
				<?php
			}
			?>
			<form method="post" name="tripzzy_checkout" id="tripzzy-checkout-form" action="<?php echo esc_url( Page::get_url( 'checkout' ) ); ?>" >
				<?php CheckoutForm::render(); ?>
				<div class="tripzzy-form-field-wrapper">
					<input type="hidden" name="tripzzy_action" value="tripzzy_book_now" />
					<input type="hidden" name="payment_details" value="" id="tripzzy-payment-details" /> <!-- Add value from payment gateway -->
					<input type="hidden" name="currency" value="<?php echo esc_attr( Currencies::get_code() ); ?>" />
					<input type="hidden" name="payment_amount" value="<?php echo esc_attr( $cart_totals['net_total'] ?? 0 ); ?>" />
					<?php if ( PaymentGateways::has_enabled_gateway() ) : ?>
						<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Payment Mode', 'tripzzy' ); ?></label>		
						<div class="tripzzy-payment-options-wrapper">
						<div class="tripzzy-payment-options">
						<?php
						foreach ( PaymentGateways::get_enabled_gateways() as $gateway ) :
							$id                 = sprintf( 'tripzzy-payment-mode-%s', $gateway['name'] );
							$is_default_gateway = PaymentGateways::is_default_gateway( $gateway['name'] );
							$checked            = $is_default_gateway ? 'checked="checked"' : '';
							$checked_class      = $is_default_gateway ? 'checked' : '';
							$description        = $gateway['description'] ?? '';
							if ( ! $description && isset( $gateway['fields'] ) && isset( $gateway['fields']['description'] ) ) {
								$description = $gateway['fields']['description']['value'] ?? '';
							}
							?>
							<div class="tripzzy-payment-mode tripzzy-payment-option <?php echo esc_attr( $checked_class ); ?>" > <!-- @todo Need to remove tripzzy-payment-option class -->
								<input type="radio" data-tripzzy-payment-script="<?php echo esc_attr( trim( wp_json_encode( $gateway['scripts'] ) ) ); ?>" class="tripzzy-payment-mode-input" id="<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( $checked ); ?><?php echo esc_attr( $checked ); ?> name="payment_mode" value="<?php echo esc_attr( $gateway['name'] ); ?>" />
								<label for="<?php echo esc_attr( $id ); ?>" ><?php echo esc_html( $gateway['title'] ); ?>
							</label><?php if ( $description ) : ?>
									<div class="tripzzy-gateway-description" >
										<?php echo wp_kses_post( wpautop( $description ) ); ?>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
						</div>
						<div id="tripzzy-payment-button" class="tripzzy-payment-button tripzzy-is-processing" data-total="<?php echo esc_attr( $cart_totals['net_total'] ?? 0 ); ?>" data-currency="<?php echo esc_attr( Currencies::get_code() ); ?>" ></div></div>
					<?php else : ?>
						<input type="hidden" name="payment_mode" value="book_now_pay_later" />
						<input class="tz-btn tz-btn-solid" type="submit" name="tripzzy_book_now" value="<?php esc_html_e( 'Book Now', 'tripzzy' ); ?>" />
					<?php endif; ?>
					<script type="text/html" id="tmpl-tripzzy-book-now-pay-latter">
						<input class="tz-btn tz-btn-solid" type="submit" name="tripzzy_book_now" value="<?php esc_html_e( 'Book Now', 'tripzzy' ); ?>" />
					</script>
					<script type="text/html" id="tmpl-tripzzy-pay-now">
						<input class="tz-btn tz-btn-solid" type="submit" name="tripzzy_book_now" value="<?php esc_html_e( 'Pay Now', 'tripzzy' ); ?>" />
					</script>

					<div id="tripzzy-checkout-form-response-msg">
						<span class="title" id="tripzzy-checkout-form-response-title" ></span>
						<span class="message" id="tripzzy-checkout-form-response" ></span>
					</div>
				</div>
			</form>
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}
	}
}

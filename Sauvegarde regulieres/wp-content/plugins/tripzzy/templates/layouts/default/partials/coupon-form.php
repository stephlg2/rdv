<?php
/**
 * Checkout Page Coupon Form Template.
 *
 * @package tripzzy
 * @since   1.1.3
 * @since   1.2.0 Added coupon position class.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Coupon;

$cart          = tripzzy()->cart;
$cart_contents = $cart->get_cart_contents();
$coupon_code   = Coupon::get_applied_coupon_code();
$input_attr    = ! empty( $coupon_code ) ? 'disabled' : '';

$coupon_position = $args['coupon_position'] ?? 'left';
if ( count( $cart_contents ) > 0 ) : ?>
	<div class="tripzzy-promo-coupon-wrapper <?php echo esc_attr( $coupon_position ); ?>" id="tripzzy-promo-coupon-wrapper">
		<form>
			<div id="tripzzy-coupon-block" style="display:block;">
				<h5 class="tripzzy-apply-coupon-title">
					<p><?php echo esc_html( __( 'Have a Coupon code?', 'tripzzy' ) ); ?></p>
				</h5>
				<p><?php echo esc_html( __( 'Add your coupon code below to get your discount.', 'tripzzy' ) ); ?></p>
				<div class="tripzzy-coupon-inputs" id="tripzzy-coupon-inputs">
					<input type="text" class="input-text" id="tripzzy-coupon-code" value="<?php echo esc_attr( $coupon_code ); ?>" <?php echo esc_attr( $input_attr ); ?> placeholder="<?php echo esc_attr( __( 'Coupon code', 'tripzzy' ) ); ?>">
					<?php if ( ! empty( $coupon_code ) ) : ?>
						<button class="tz-btn tripzzy-clear-coupon-btn" id="tripzzy-clear-coupon-btn"><?php echo esc_html( __( 'Clear', 'tripzzy' ) ); ?></button>
					<?php else : ?>
						<input type="submit" class="tripzzy-apply-coupon-btn tz-btn tz-btn-solid tz-btn-sm" id="tripzzy-apply-coupon-btn" name="apply_coupon" value="<?php echo esc_attr( __( 'Apply', 'tripzzy' ) ); ?>">
					<?php endif; ?>
				</div>
			</div>
			<div id="tripzzy-coupon-response-msg">
			</div>
		</form>
	</div>
	<?php
endif;

<?php
/**
 * Mini Cart Template.
 *
 * @package tripzzy
 * @since   1.1.3
 * @since   1.2.0 Added option to show/hide apply coupon along with position.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Image;
use Tripzzy\Core\Template;

$cart          = tripzzy()->cart;
$cart_contents = $cart->get_cart_contents();
$cart_totals   = $cart->get_totals();
$settings      = Settings::get();

if ( count( $cart_contents ) > 0 ) : ?>
	<div class="tz-col tz-cols-5-lg tz-cols-4-xl">
		<div class="tripzzy-order-info">
			<h3><?php echo esc_attr( __( 'Order Summary', 'tripzzy' ) ); ?></h3>
			<div class="tripzzy-orders">
				<ol>
					<?php
					foreach ( $cart_contents as $cart_key => $item ) :
						$package_id = isset( $item['package_id'] ) ? $item['package_id'] : '';
						if ( ! $item['trip_id'] ) {
							continue;
						}
						$trip     = new Trip( $item['trip_id'] );
						$packages = $trip->packages();
						$package  = $packages->get_package( $package_id );
						$time     = $item['time'] ?? '';
						?>
						<li class="tripzzy-cart-item">
							<div class="tripzzy-cart-trip-thumbnail">
								<?php Image::get_thumbnail( $item['trip_id'] ); ?>
							</div>
							<div class="tripzzy-cart-trip-details">
								<span class="tripzzy-cart-trip-name"><a href="<?php echo esc_url( get_the_permalink( $item['trip_id'] ) ); ?>"><?php echo esc_html( $item['title'] ); ?></a></span>
								<span class="tripzzy-cart-trip-date"><strong><?php echo esc_attr( __( 'Date', 'tripzzy' ) ); ?>: </strong><?php echo esc_html( date_i18n( tripzzy_date_format(), strtotime( $item['start_date'] ) ) ); ?></span>
								<?php if ( $time ) : ?>
								<span class="tripzzy-cart-trip-time"><strong><?php echo esc_attr( __( 'Time', 'tripzzy' ) ); ?>: </strong><?php echo esc_html( date_i18n( tripzzy_time_format(), strtotime( $time ) ) ); ?></span>
								<?php endif; ?>
								<span class="tripzzy-cart-trip-package"><strong><?php echo esc_attr( __( 'Package', 'tripzzy' ) ); ?>: </strong><?php echo esc_html( $package->get_title() ); ?></span>
							</div>
							<div class="tripzzy-cart-price-wrap">
								<?php echo esc_html( Amount::display( $item['item_total'] ) ); ?>
							</div>
							<button class="tripzzy-remove-cart-item" data-cart-item-id="<?php echo esc_attr( $cart_key ); ?>" ><?php echo esc_attr( __( 'Remove', 'tripzzy' ) ); ?></button>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="tripzzy-cart-footer">
					<?php if ( $cart_totals['discount_total'] > 0 ) : ?>
						<div class="tripzzy-cart-subtotal-wrap">
							<div class="tripzzy-cart-gross-total">
								<span class="tripzzy-cart-price-label"><?php echo esc_attr( __( 'Subtotal', 'tripzzy' ) ); ?></span>
								<span class="tripzzy-cart-total-price gross-total"><?php echo esc_html( Amount::display( $cart_totals['gross_total'] ) ); ?></span>
							</div>
							<div class="tripzzy-cart-gross-total">
								<span class="tripzzy-cart-price-label"><?php echo esc_attr( __( 'Discount', 'tripzzy' ) ); ?></span>
								<span class="tripzzy-cart-total-price discount-total">(<?php echo esc_html( Amount::display( $cart_totals['discount_total'] ) ); ?>)</span>
							</div>
						</div>
					<?php endif; ?>
					<?php
					$hide_coupon     = (bool) ( $settings['hide_coupon_on_checkout'] ?? false );
					$coupon_position = $settings['coupon_position'] ?? 'left';
					if ( ! $hide_coupon && 'sidebar' === $coupon_position ) {
						Template::get_template_part( 'layouts/default/partials/coupon', 'form', compact( 'coupon_position' ) );
					}
					?>
					<div class="tripzzy-cart-nettotal-wrap">
						<div class="tripzzy-cart-net-total">
							<span class="tripzzy-cart-price-label"><?php echo esc_attr( __( 'Total', 'tripzzy' ) ); ?></span>
							<span class="tripzzy-cart-total-price net-total"><?php echo esc_html( Amount::display( $cart_totals['net_total'] ) ); ?></span>
						</div>
					</div>
					<!-- <a href="#" class="tz-btn tz-btn-solid tz-btn-full">Confirm order</a> -->
					<div id="tripzzy-cart-response-message"></div>
				</div>
			</div>
		</div>
	</div>
	<?php
endif;

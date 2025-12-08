<?php
/**
 * Checkout trip page
 *
 * @package tripzzy
 * @since   1.0.0
 * @since   1.1.1 Updated templte slug to checkout-tripzzy.php form checkout.php
 * @since   1.2.0 Added option to show/hide apply coupon along with position.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;
use Tripzzy\Core\Helpers\TripFilter;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Coupon;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Image;

$cart          = tripzzy()->cart;
$cart_contents = $cart->get_cart_contents();
$cart_totals   = $cart->get_totals();
$coupon_code   = Coupon::get_applied_coupon_code();
$input_attr    = ! empty( $coupon_code ) ? 'disabled' : '';
$queries       = Strings::get()['queries'];
$settings      = Settings::get();
get_header(); ?>
<?php do_action( 'tripzzy_before_main_content' ); ?>
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
<?php do_action( 'tripzzy_after_main_content' ); ?>
<?php
get_footer();

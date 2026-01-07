<?php
/**
 * Views: Coupon.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Loading;

if ( ! class_exists( 'Tripzzy\Admin\Views\CouponView' ) ) {
	/**
	 * CouponView Class.
	 *
	 * @since 1.0.0
	 */
	class CouponView {

		/**
		 * Coupon page html.
		 *
		 * @param object $coupon Post object.
		 * @since 1.0.0
		 */
		public static function render( $coupon ) {
			$coupon_id = $coupon->ID;
			?>
			<div class="tripzzy-coupon-page-wrapper">
				<div id="tripzzy-coupon-page" class="tripzzy-page tripzzy-coupon-page" >
					<?php Loading::render(); ?>
				</div>
			</div>
			<?php
		}
	}
}

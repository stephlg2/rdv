<?php
/**
 * Views: Trips.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Strings;

if ( ! class_exists( 'Tripzzy\Admin\Views\TripsView' ) ) {
	/**
	 * TripsView Class.
	 *
	 * @since 1.0.0
	 */
	class TripsView {

		/**
		 * Trip page html.
		 *
		 * @param object $trip Post object.
		 * @since 1.0.0
		 */
		public static function render( $trip ) {
			$trip_id = $trip->ID;
			$labels  = Strings::get()['labels'];
			?>
			<div class="tripzzy-trip-page-wrapper">
				<div id="tripzzy-trip-page" class="tripzzy-page tripzzy-trip-page">
					<?php echo esc_html( $labels['loading'] ?? '' ); ?>
				</div>
			</div>
			<?php
		}
	}
}

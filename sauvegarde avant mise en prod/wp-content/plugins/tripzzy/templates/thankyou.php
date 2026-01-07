<?php
/**
 * Thankyou Page Template.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Helpers\MetaHelpers;

get_header(); ?>
<?php do_action( 'tripzzy_before_main_content' ); ?>
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
<?php do_action( 'tripzzy_after_main_content' ); ?>
<?php
get_footer();

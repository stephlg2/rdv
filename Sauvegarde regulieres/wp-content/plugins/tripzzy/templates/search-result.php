<?php
/**
 * Search Result Page Template.
 *
 * @package tripzzy
 * @since   1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\TripFilter;

$tripzzy_view_mode = TripFilter::get_view_mode();
$images_url        = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
get_header(); ?>
<?php do_action( 'tripzzy_before_main_content' ); ?>
<section class="tripzzy-section">
	<div class="tripzzy-container"><!-- Main Wrapper element for Tripzzy -->
		<div class="tz-row">
			<div class="tz-col tz-cols-3-lg">
				<?php do_action( 'tripzzy_archive_before_content' ); ?>
			</div>
			<div class="tz-col tz-cols-9-lg">
				<?php tripzzy_render_archive_toolbar(); ?>
				<div class="tripzzy-trips <?php echo esc_attr( $tripzzy_view_mode ); ?>-view">
					<?php do_action( 'tripzzy_archive_before_listing' ); ?>
					<!-- ID: tripzzy-trip-listings is required -->
					<div id="tripzzy-trip-listings" class="tz-row tripzzy-trip-listings" ></div><!-- /tripzzy-trip-listings -->
					<?php do_action( 'tripzzy_archive_after_listing' ); ?>
				</div><!-- /tripzzy-trips -->
			</div>
			<?php do_action( 'tripzzy_archive_after_content' ); ?>
		</div>
	</div>
</section>
<?php
tripzzy_render_archive_list_item_template();
do_action( 'tripzzy_after_main_content' );
get_footer();

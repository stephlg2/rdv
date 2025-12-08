<?php
/**
 * Template for dates on single page.
 *
 * @since 1.0.0
 * @since 1.1.8 Localize date format.
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Loading;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Trip;

$trip = $args['trip'];

$labels         = Strings::get()['labels'] ?? array();
$section_titles = Trip::get_section_titles( get_the_ID() );
$section_title  = $section_titles['trip_date'] ?? __( 'Availability', 'tripzzy' );

// Vérifier s'il y a des dates disponibles
$dates            = $trip->dates();
$departure_months = $dates->departure_months();
$trip_dates       = $dates->get_dates();
$packages         = $trip->packages(); // all Packages.
$price_per_key    = $trip->price_per;
$has_availability = isset( $trip_dates[0] ) && $packages->total() > 0;

if ( $has_availability ) :
?>
<div class="tripzzy-section tripzzy-availability-section"  id="tripzzy-availability-section">
	<?php if ( ! empty( $section_title ) ) : ?>
		<h3 class="tripzzy-section-title"><?php echo esc_html( $section_title ); ?></h3>
	<?php endif; ?>
	<div class="tripzzy-section-inner tripzzy-pricing-date-list">
		<?php
		if ( isset( $trip_dates[0] ) && $packages->total() > 0 ) :

			$default_package_id = $packages->default_package_id;
			$default_package    = $packages->get_package();

			$itineraries = $trip::get_itineraries( get_the_ID() );
			?>
			<div class="tripzzy-departure-months" id="tripzzy-departure-months">
				<ul>
					<li class="selected-departure" data-departure-month='' ><button><?php echo esc_html( $labels['all'] ?? '' ); ?><span><?php echo esc_html( $labels['dep'] ?? '' ); ?></span></button></li>
					<?php
					foreach ( $departure_months as $departure_month ) :
						$departure_date = new \DateTime( $departure_month );
						?>
						<li data-departure-month="<?php echo esc_attr( $departure_date->format( 'Y-n-j' ) ); ?>"><button ><?php echo esc_html( date_i18n( 'M', $departure_date->getTimestamp() ) ); ?> <span><?php echo esc_html( $departure_date->format( 'Y' ) ); ?></span></button></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="tripzzy-dates-list">
				<input type="hidden" id="tripzzy-departure-month" value="" />
				<input type="hidden" id="tripzzy-is-all-departure" value="1" />  <!-- May be this is not required now. Helps to fetch either all dates or only specific month date on load more -->
				<input type="hidden" id="tripzzy-next-start-date" value="" /> <!-- Helps to fetch load more departure -->
				<input type="hidden" id="tripzzy-dates-current-page" value="1" /> 
				<div id="tripzzy-trip-dates" class="tripzzy-trip-dates tripzzy-is-processing">
				loading..
				</div>
			</div>
			<!-- Load More -->
			<div class="tripzzy-load-more-link">
				<?php Loading::render( array( 'id' => 'tripzzy-departure-list-loader-wrapper' ) ); ?>
				<a href="#" class="tz-btn tz-btn-solid tripzzy-load-more" id="tripzzy-load-more-departure">
					<?php echo esc_html( $labels['view_more_dep'] ?? '' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<?php

if ( ! has_action( 'wp_footer', 'tripzzy_booking_category_template_markup' ) ) {
	add_action( 'wp_footer', 'tripzzy_booking_category_template_markup' );
}
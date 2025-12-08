<?php

/**
 * Single trip check availability template.
 *
 * @package tripzzy
 * @since   1.1.9
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\TripFeatures;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Amount;

$strings  = Strings::get();
$labels   = $strings['labels'] ?? array();
$duration = Trip::get_duration(null, false);

$trip             = $args['trip'];
$price_per        = $trip->get_price_per();
$price_per_key    = $trip->price_per;
$packages         = $trip->packages();
$package          = $packages->get_package();
$categories       = $package ? $package->get_categories() : null;
$default_category = $trip->package_category();

// Vérifier s'il y a des dates disponibles
$dates            = $trip->dates();
$trip_dates       = $dates->get_dates();
$has_availability = isset( $trip_dates[0] ) && $packages->total() > 0;

?>
<div class="tripzzy-check-availability tripzzy-stiky-box" id="tripzzy-check-availability">
	<div class="tripzzy-check-availability-content">
		<div class="tripzzy-check-availability-top">
			<div class="tripzzy-booking-top-area">
				<?php
				$nights_display = '';
				if (isset($duration['duration'][1], $duration['duration_unit'][1]) && ! empty($duration['duration'][1])) {
					$unit_nights = strtolower($duration['duration_unit'][1]) === 'nights' ? 'nuits' : $duration['duration_unit'][1];
					$nights_display = ' (' . esc_html($duration['duration'][1] . ' ' . $unit_nights) . ')';
				}
				?>
				<div class="tripzzy-duration">
					<span class="tripzzy-duration-label"><?php echo esc_html($labels['duration'] ?? ''); ?></span>
					<strong>
						<?php echo esc_html($duration['duration'][0] . ' ' . $duration['duration_unit'][0]); ?>
					</strong>
					<?php if ($nights_display) : ?>
						<span class="tripzzy-duration-nights"><?php echo $nights_display; ?></span>
					<?php endif; ?>
				</div>
				<div class="tripzzy-trip-code">
					<span><?php echo esc_html($labels['trip_code'] ?? ''); ?></span> : <code><?php echo esc_html(Trip::get_code()); ?></code>
				</div>
			</div>
			<div class="tripzzy-booking-price-area tripzzy-price-per-<?php echo esc_attr($price_per_key); ?>">
				<?php
				if (is_array($categories) && count($categories) > 0) {
				?>
					<div class="tripzzy-price-item-wrapper">
						<?php
						foreach ($categories as $category) {
						?>

						<span class="tripzzy-price-label type-voyage">
									<?php echo esc_html__('Type de voyage :', 'tripzzy'); ?> <strong><?php echo esc_html($category->get_title()); ?></strong>
								</span>
							<div class="tripzzy-price-item">
								
								<?php if ($category->has_sale() && $category->get_sale_percent() > 0) : ?>
									<span class="tripzzy-discount">-<?php echo esc_html($category->get_sale_percent()); ?>%</span>
								<?php endif; ?>
								</span>
								<?php if ('person' === $price_per_key) : ?>
									<div class="tripzzy-price">
										<span class="tripzzy-price-from-text">
											<?php echo esc_html($labels['from'] ?? ''); ?>
											<?php if ($category->has_sale()) : ?>
												<del class="tripzzy-striked-price"><?php echo esc_html(number_format_i18n($category->get_regular_price(), 2)); ?> €</del>
											<?php endif; ?>
										</span>
										<span>
											<span class="tripzzy-booking-price"><?php echo esc_html(number_format_i18n($category->get_price(), 2)); ?> €</span> / <?php echo esc_html($price_per); ?>
										</span>
									</div>
								<?php endif; ?>
							</div>
						<?php
						}
						?>
					</div>
					<?php if ('group' === $price_per_key) : ?>
						<div class="tripzzy-price">
							<span class="tripzzy-price-from-text">
								<?php echo esc_html($labels['from'] ?? ''); ?>
								<?php if ($default_category->has_sale()) : ?>
									<del class="tripzzy-striked-price"><?php echo esc_html(number_format_i18n($default_category->get_regular_price(), 2)); ?> €</del>
								<?php endif; ?>
							</span>
							<span>
								<span class="tripzzy-booking-price"><?php echo esc_html(number_format_i18n($default_category->get_price(), 2)); ?> €</span> / <?php echo esc_html($price_per); ?>
							</span>
						</div>
				<?php
					endif;
				}
				?>
			</div>
			<?php TripFeatures::render(get_the_ID()); ?>
		</div>
		<div class="tripzzy-booking-actions">
			<div class="tripzzy-button-group vertical">
				<?php if ( $has_availability ) : ?>
					<a href='#tripzzy-availability-section' class='tz-btn tz-btn-solid dispo-btn' data-tripzzy-smooth-scroll><?php echo esc_html__( 'Voir les dates', 'tripzzy' ); ?></a>
				<?php endif; ?>
				<button data-tripzzy-drawer-trigger aria-controls="tripzzy-enquiry-form-wrapper" aria-expanded="false" type="button" id="tripzzy-enquiry-button" class='tz-btn tz-btn-outline'><?php echo esc_html($labels['make_enquiry'] ?? ''); ?></button>
			</div>


		</div>
	</div>
</div>
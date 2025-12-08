<?php
/**
 * Titlebar template.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

// Import necessary classes at top.
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Amount;
?>
<section class="avada-page-titlebar-wrapper" aria-label="<?php esc_attr_e( 'Page Title Bar', 'Avada' ); ?>">
	<div class="fusion-page-title-bar fusion-page-title-bar-<?php echo esc_attr( $content_type ); ?> fusion-page-title-bar-<?php echo esc_attr( $alignment ); ?>">
		<div class="fusion-page-title-row">
			<div class="fusion-page-title-wrapper">
				<div class="fusion-page-title-captions">
					<div class="degrades-image-post"></div>

					<?php if ( $title ) : ?>
						<?php // Add entry-title for rich snippets. ?>
						<?php $entry_title_class = ( Avada()->settings->get( 'disable_date_rich_snippet_pages' ) && Avada()->settings->get( 'disable_rich_snippet_title' ) ) ? 'entry-title' : ''; ?>
						<h1 class="<?php echo esc_attr( $entry_title_class ); ?>"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>

						<?php if ( $subtitle ) : ?>
							<p><?php echo $subtitle; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( 'center' === $alignment ) : // Render secondary content on center layout. ?>
						<?php if ( 'none' !== fusion_get_option( 'page_title_bar_bs' ) ) : ?>
							<div class="fusion-page-title-secondary">
								<?php echo $secondary_content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<?php
					// Tripzzy Duration & Price Section
					$strings  = Strings::get();
					$labels   = $strings['labels'] ?? array();
					$duration = Trip::get_duration( null, false );
					?>
					<?php if ( isset( $duration['duration'][0], $duration['duration_unit'][0] ) ) : ?>
						<?php
						// Prepare nights display if available.
						$nights_display = '';
						if (
							isset( $duration['duration'][1], $duration['duration_unit'][1] )
							&& ! empty( $duration['duration'][1] )
						) {
							$unit_nights = strtolower($duration['duration_unit'][1]) === 'nights' ? 'nuits' : $duration['duration_unit'][1];
							$nights_display = ' (' . esc_html( $duration['duration'][1] . ' ' . $unit_nights ) . ')';
						}
						?>
						<div class="tripzzy-duration">
							<span class="tripzzy-duration-label">
								<?php echo esc_html( $labels['duration'] ?? 'Durée' ); ?>
							</span>
							<strong>
								<?php echo esc_html( $duration['duration'][0] . ' ' . $duration['duration_unit'][0] ); ?>
							</strong>
							<?php if ( $nights_display ) : ?>
								<span class="tripzzy-duration-nights"><?php echo $nights_display; ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php
					// Pricing section
					global $post;
					$trip = new Trip( $post->ID );
					$price_per = strtolower( $trip->get_price_per() );
					$price_per_key = $trip->price_per;
					$packages = $trip->packages();
					$package = $packages->get_package();
					$categories = $package ? $package->get_categories() : null;
					$default_category = $trip->package_category();
					?>
					<?php if ( is_array( $categories ) && count( $categories ) > 0 ) : ?>
						<div class="tripzzy-booking-price-area tripzzy-price-per-<?php echo esc_attr( $price_per_key ); ?>">
							<div class="prix_title_bar">
								<?php foreach ( $categories as $category ) : ?>
									<div>
										<?php if ( 'person' === $price_per_key ) : ?>
											<div class="tripzzy-price">
												<?php echo esc_html( $labels['from'] ?? '' ); ?>
												<?php if ( $category->has_sale() ) : ?>
													<del class="tripzzy-striked-price"><?php echo esc_html( Amount::display( $category->get_regular_price() ) ); ?></del>
												<?php endif; ?>
												<span>
													<?php echo esc_html( $category->get_price() ); ?> €</span> / <?php echo esc_html( $price_per ); ?>
											</div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
							<?php if ( 'group' === $price_per_key && $default_category ) : ?>
								<div class="tripzzy-price">
									<span class="tripzzy-price-from-text">
										<?php echo esc_html( $labels['from'] ?? '' ); ?>
										<?php if ( $default_category->has_sale() ) : ?>
											<del class="tripzzy-striked-price"><?php echo esc_html( Amount::display( $default_category->get_regular_price() ) ); ?></del>
										<?php endif; ?>
									</span>
									<span>
										<?php echo esc_html( $default_category->get_price() ); ?> €</span> / <?php echo esc_html( $price_per ); ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="separator-image-post"></div>
				</div>

				<?php if ( 'center' !== $alignment ) : // Render secondary content on left/right layout. ?>
					<?php if ( 'none' !== fusion_get_option( 'page_title_bar_bs' ) ) : ?>
						<div class="fusion-page-title-secondary">
							<?php echo $secondary_content; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>

			</div>
			
		</div>
	</div>


</section>

<?php
/**
 * Tripzzy Slider.
 *
 * @package tripzzy
 * @since 1.0.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Image;
use Tripzzy\Core\Assets;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Strings;

$strings = Strings::get();
$labels  = $strings['labels'] ?? array();

$block_id = 'tz-' . ( $attributes['blockId'] ?? '' );

$show_navigation                 = ! ! $attributes['showNavigation'] ?? true;
$show_pagination                 = ! ! $attributes['showPagination'] ?? true;
$show_overlay                    = ! ! $attributes['showOverlay'] ?? true;
$overlay_color                   = $attributes['overlayColor'] ?? '';
$slider_height                   = $attributes['sliderHeight'] ?? '80vh';
$autoplay                        = ! ! $attributes['autoplay'] ?? true;
$autoplay_delay                  = $attributes['autoplayDelay'] ?? '3000';
$autoplay_disable_on_interaction = ! ! $attributes['autoplayDisableOnInteraction'] ?? true;
$slides_per_view                 = $attributes['slidesPerView'] ?? '1';
$slider_loop                     = ! ! $attributes['sliderLoop'] ?? true;
$show_title                      = ! ! $attributes['showTitle'] ?? true;
$show_price                      = ! ! $attributes['showPrice'] ?? true;
$show_description                = ! ! $attributes['showDescription'] ?? true;
$show_buttons                    = ! ! $attributes['showButtons'] ?? true;
$open_in_new_tab                 = ! ! $attributes['buttonOpenInNewTab'] ?? false;
$button_text                     = $attributes['buttonText'] ?? __( 'Book Now', 'tripzzy' );
$target                          = $open_in_new_tab ? '_blank' : '_self';

$data = $attributes['query'] ?? array();
$args = array(
	'post_type'      => 'tripzzy',
	'paged'          => 1,
	'orderby'        => $data['orderBy'] ?? 'title',
	'order'          => $data['order'] ?? 'asc',
	'posts_per_page' => $data['numberOfItems'] ?? 4,
);
// Meta Query.
$args['meta_query'] = array(); // @phpcs:ignore
$featured           = $data['featured'] ?? false;

if ( $featured ) {
	$args['meta_query'][] = array(
		'key'     => 'tripzzy_featured',
		'value'   => '1',
		'compare' => '=',
	);
}

$query = new \WP_Query( $args );

// Block wrapper class.
$wrapper_class = 'tripzzy-trip-slider-block';
if ( isset( $attributes['align'] ) && ! empty( $attributes['align'] ) ) {
	$align_class    = 'align' . $attributes['align'];
	$wrapper_class .= sprintf( ' %s', $align_class );
}
if ( isset( $attributes['className'] ) && ! empty( $attributes['className'] ) ) {
	$wrapper_class .= sprintf( ' %s', $attributes['className'] );
}

$styles = array(
	array(
		'selector' => sprintf( '#%s.tripzzy-trip-slider-block .tripzzy-slides swiper-slide img', $block_id ),
		'css'      => array(
			'height' => $slider_height,
		),
	),
);
?>
<style>
	.tripzzy-trip-slider-block#<?php echo esc_html( $block_id ); ?>{
		--swiper-theme-color: <?php echo esc_attr( $attributes['primaryColor'] ); ?>;
		--swiper-theme-color-hover: <?php echo esc_attr( $attributes['primaryColorHover'] ); ?>;
		--tripzzy-trip-slider-overlay-color: <?php echo esc_attr( $attributes['overlayColor'] ); ?>;
		--swiper-slider-height: <?php echo esc_attr( $attributes['sliderHeight'] ); ?>;
	}
<?php echo wp_kses_post( Assets::array_to_css( $styles ) ); ?>
</style>
<div id="<?php echo esc_attr( $block_id ); ?>" class="<?php echo esc_attr( $wrapper_class ); ?>">
	<div class="tripzzy-slides">
		<?php
		$has_post_class = true;
		if ( $query->have_posts() ) :
			?>
			<swiper-container
			navigation="<?php echo esc_attr( $show_navigation ? 'true' : 'false' ); ?>"
			pagination="<?php echo esc_attr( $show_pagination ? 'true' : 'false' ); ?>"
			<?php if ( $show_pagination ) : ?>
			pagination-clickable="true"
			<?php endif; ?>
			autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
			<?php if ( $autoplay ) : ?>
				autoplay-delay="<?php echo esc_attr( $autoplay_delay ); ?>"
				autoplay-disable-on-interaction="<?php echo esc_attr( $autoplay_disable_on_interaction ? 'true' : 'false' ); ?>"
			<?php endif; ?>

			slides-per-view="<?php echo esc_attr( $slides_per_view ); ?>"
			loop="<?php echo esc_attr( $slider_loop ? 'true' : 'false' ); ?>"
			>
			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				$trip_id   = get_the_ID();
				$trip      = new Trip( $trip_id );
				$category  = $trip->package_category();
				$price     = $category ? $category->get_price() : 0;
				$price_per = $trip->get_price_per();
				?>
				<swiper-slide class="swiper-slide">
					<div class="swiper-slide-contents alignfull is-layout-constrained">
						<?php if ( $show_title ) : ?>
							<div class="tripzzy-slide-title alignwide">
								<h3><?php the_title(); ?></h3>
							</div>
						<?php endif; ?>
						<?php if ( $show_price ) : ?>
							<div class="tripzzy-price alignwide">
								<span class="tripzzy-price-from-text">
									<?php echo esc_html( $labels['from'] ?? '' ); ?>
								</span>
								<span><span class="tripzzy-booking-price"><?php Amount::display( $price, true ); ?></span> / <small><?php echo esc_html( $price_per ); ?></small></span>
							</div>
						<?php endif; ?>
						<?php if ( $show_description ) : ?>
							<div class="tripzzy-slide-description alignwide">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>
						<?php if ( $show_buttons ) : ?>
							<div class="tripzzy-button-group alignwide">
								<a href="<?php the_permalink(); ?>" target="<?php echo esc_attr( $target ); ?>" class='tz-btn tz-btn-solid'><?php echo esc_html( $button_text ); ?></a>
							</div>
						<?php endif; ?>
					</div>
					<img src="<?php	echo esc_url( Image::get_thumbnail_url( get_the_ID(), 'full' ) ); ?>"  />
				</swiper-slide>
				<?php
			}
			wp_reset_postdata();
			?>
			</swiper-container>
			<?php
		endif;
		?>
	</div><!-- /.tripzzy-slides -->
</div><!-- /.tripzzy-slider-block -->

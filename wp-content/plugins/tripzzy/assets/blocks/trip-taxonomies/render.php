<?php
/**
 * Tripzzy Trips.
 *
 * @since 1.0.1
 * @since 1.0.5 API updated to v3 and settings and styles added.
 * @since 1.0.8 Default Thumbnail url added and also fixed style overlap if used multiple blcok used.
 * @since 1.1.1 Thumbnail alternative text added.
 * @since 1.1.2 Added inline style insted of style written in style tag.
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Assets;
use Tripzzy\Core\Blocks;

$block_id      = $attributes['blockId'] ?? '';
$text_align    = $attributes['textAlign'] ?? 'center';
$data          = $attributes['query'] ?? array();
$tripzzy_terms = get_terms(
	array(
		'taxonomy'   => $data['taxonomy'] ?? 'tripzzy_trip_destination',
		'orderby'    => $data['orderBy'] ?? 'name',
		'order'      => $data['order'] ?? 'asc',
		'number'     => $data['numberOfItems'] ?? 4,
		'hide_empty' => false,
	)
);
// Styles.
$gap               = $attributes['gap'] ?? '0';
$has_overlay       = (bool) $attributes['showOverlay'] && isset( $attributes['overlayColor'] );
$has_shine_overlay = (bool) $attributes['showShineOverlay'];
$show_count        = (bool) $attributes['showCount'];
$title_position    = $attributes['titlePosition'] ?? 'bottom';
$text_color        = Blocks::get_text_color( $attributes );

$block_class = array(
	'tripzzy-trip-taxonomies-block',
	'tripzzy-title-position-' . $title_position,
	'tz-block-' . $block_id,
	'has-text-align-' . $text_align,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $block_class ),
	)
);
$overlay            = $attributes['showOverlay'] && $attributes['overlayColor'] ? $attributes['overlayColor'] : 'transparent';
$css_vars           = array(
	'--tripzzy-category-image-height'         => $attributes['itemHeight'] ?? '300px',
	'--tripzzy-category-grid-template-column' => $attributes['col'] ?? '4',
	'--tripzzy-category-grid-gap'             => $attributes['gap'] ?? '20px',
	'--tripzzy-category-image-overlay'        => $overlay,
	'--tripzzy-category-heading-color'        => $text_color,
);
$styles             = array(
	array(
		'selector' => '.tz-block-' . $block_id . ' .tripzzy-trip-category-listings',
		'css'      => $css_vars,
	),
);

wp_register_style( 'tripzzy-' . $block_id, false, array(), TRIPZZY_VERSION );
wp_enqueue_style( 'tripzzy-' . $block_id );
wp_add_inline_style( 'tripzzy-' . $block_id, Assets::array_to_css( $styles ) );
?>

<div <?php echo wp_kses_data( $wrapper_attributes ); ?> >
	<div class="tripzzy-trip-categories" >
		<div class="tz-row tripzzy-trip-category-listings">
			<?php if ( is_array( $tripzzy_terms ) && count( $tripzzy_terms ) > 0 ) : ?>
				<?php
				foreach ( $tripzzy_terms as $tripzzy_term ) :
					/* translators: %d Term count */
					$term_count    = sprintf( _n( '%d trip', '%d trips', $tripzzy_term->count, 'tripzzy' ), $tripzzy_term->count );
					$thumbnail_url = MetaHelpers::get_term_meta( $tripzzy_term->term_id, 'taxonomy_image_url' );
					if ( ! $thumbnail_url ) {
						$thumbnail_url = Image::default_thumbnail_url();
					}
					?>
					<div class="tz-col">
						<div class="tripzzy-trip-category <?php echo esc_attr( $has_shine_overlay ? 'tz-shine-overlay' : '' ); ?>">
							<div class="tripzzy-trip-category-img">
								<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $tripzzy_term->name ); ?>" />
							</div>
							<h3 class="tripzzy-trip-category-title tripzzy-trip-category-bottom-content">
								<a href="<?php echo esc_url( get_term_link( $tripzzy_term->term_id ) ); ?>">
									<?php echo esc_html( $tripzzy_term->name ); ?>
									<?php if ( $show_count ) : ?>
										<span class="tripzzy-trip-category-count"><?php echo esc_html( $term_count ); ?></span>
									<?php endif; ?>
								</a>
							</h3>
						</div>
					</div>
				<?php endforeach; ?>	
			<?php endif; ?>
		</div>
	</div>
</div>

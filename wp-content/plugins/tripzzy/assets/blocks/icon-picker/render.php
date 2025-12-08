<?php
/**
 * Tripzzy Icon Picker.
 *
 * @since 1.0.8
 * @since 1.1.1 Screen reader text added for icon.
 * @since 1.1.2 Added inline style insted of style written in style tag.
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Assets;
use Tripzzy\Core\Blocks;

$icon_data = $attributes['iconData'] ?? array();

$block_id    = $attributes['blockId'] ?? '';
$text_align  = $attributes['textAlign'] ?? 'center';
$shape       = $icon_data['shape'] ?? null;
$view        = $icon_data['view'] ?? null;
$url         = $icon_data['url'] ?? null;
$font_size   = Blocks::get_font_size( $attributes, '30px' );
$block_class = array(
	'tripzzy-icon-block',
	'tz-block-' . $block_id,
	'has-text-align-' . $text_align,
);
if ( $shape ) {
	$block_class[] = 'tripzzy-shape-' . $shape;
}
if ( $view ) {
	$block_class[] = 'tripzzy-view-' . $view;
}
if ( $url ) {
	$block_class[] = 'has-link';
}

$icon               = $icon_data['icon'] ?? 'fas fa-star';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $block_class ),
	)
);

$css_vars = array( '--tripzzy-icon-size' => $font_size );
if ( isset( $attributes['primaryColor'] ) && ! empty( $attributes['primaryColor'] ) ) {
	$css_vars['--tripzzy-primary-color'] = $attributes['primaryColor'];
}
if ( isset( $attributes['secondaryColor'] ) && ! empty( $attributes['secondaryColor'] ) ) {
	$css_vars['--tripzzy-light-color'] = $attributes['secondaryColor'];
}
if ( isset( $attributes['primaryColorHover'] ) && ! empty( $attributes['primaryColorHover'] ) ) {
	$css_vars['--tripzzy-icon-color-hover'] = $attributes['primaryColorHover'];
}
if ( isset( $attributes['secondaryColorHover'] ) && ! empty( $attributes['secondaryColorHover'] ) ) {
	$css_vars['--tripzzy-icon-color-hover-alt'] = $attributes['secondaryColorHover'];
}

if ( isset( $attributes['padding'] ) && ! empty( $attributes['padding'] ) ) {
	$css_vars['--tripzzy-icon-padding'] = sprintf( '%s %s %s %s', $attributes['padding']['top'], $attributes['padding']['right'], $attributes['padding']['bottom'], $attributes['padding']['left'] );
}

if ( isset( $attributes['borderWidth'] ) && ! empty( $attributes['borderWidth'] ) ) {
	$css_vars['--tripzzy-icon-border-width'] = sprintf( '%s %s %s %s', $attributes['borderWidth']['top'], $attributes['borderWidth']['right'], $attributes['borderWidth']['bottom'], $attributes['borderWidth']['left'] );
}
if ( isset( $attributes['borderRadius'] ) && ! empty( $attributes['borderRadius'] ) ) {
	$css_vars['--tripzzy-icon-border-radius'] = sprintf( '%s %s %s %s', $attributes['borderRadius']['top'], $attributes['borderRadius']['right'], $attributes['borderRadius']['bottom'], $attributes['borderRadius']['left'] );
}
$styles = array(
	array(
		'selector' => '.tz-block-' . $block_id,
		'css'      => $css_vars,
	),
);
wp_register_style( 'tripzzy-' . $block_id, false, array(), TRIPZZY_VERSION );
wp_enqueue_style( 'tripzzy-' . $block_id );
wp_add_inline_style( 'tripzzy-' . $block_id, Assets::array_to_css( $styles ) );
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?> >
	<div class="tripzzy-icon-wrapper">
		<div class="tripzzy-icon ">
			<?php if ( $url ) : ?>
				<a href="<?php echo esc_url( $url ); ?>"><i class="<?php echo esc_attr( $icon ); ?>"></i><span class="screen-reader-text"><?php echo esc_url( $url ); ?></span></a>
			<?php else : ?>
				<i class="<?php echo esc_attr( $icon ); ?>"></i>
			<?php endif; ?>
		</div>
	</div>
</div>

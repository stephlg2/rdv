<?php
/**
 * Tripzzy Icon Box.
 *
 * @since 1.3.0
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Assets;
use Tripzzy\Core\Blocks;

$icon_data = $attributes['iconData'] ?? array();

$block_id        = $attributes['blockId'] ?? '';
$text_align      = $attributes['textAlign'] ?? 'center';
$shape           = $icon_data['shape'] ?? null;
$view            = $icon_data['view'] ?? null;
$url             = $icon_data['url'] ?? null;
$open_in_new_tab = $icon_data['openInNewTab'] ?? false;

$font_size   = Blocks::get_font_size( $attributes, '30px' );
$block_class = array(
	'tripzzy-icon-box-block',
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
$content            = $attributes['content'] ?? '';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $block_class ),
	)
);

$css_vars = array( '--tripzzy-icon-box-size' => $font_size );
if ( isset( $attributes['primaryColor'] ) && ! empty( $attributes['primaryColor'] ) ) {
	$css_vars['--tripzzy-primary-color'] = $attributes['primaryColor'];
}
if ( isset( $attributes['secondaryColor'] ) && ! empty( $attributes['secondaryColor'] ) ) {
	$css_vars['--tripzzy-light-color'] = $attributes['secondaryColor'];
}
if ( isset( $attributes['primaryColorHover'] ) && ! empty( $attributes['primaryColorHover'] ) ) {
	$css_vars['--tripzzy-icon-box-color-hover'] = $attributes['primaryColorHover'];
}
if ( isset( $attributes['secondaryColorHover'] ) && ! empty( $attributes['secondaryColorHover'] ) ) {
	$css_vars['--tripzzy-icon-box-color-hover-alt'] = $attributes['secondaryColorHover'];
}

if ( isset( $attributes['padding'] ) && ! empty( $attributes['padding'] ) ) {
	$css_vars['--tripzzy-icon-box-padding'] = sprintf( '%s %s %s %s', $attributes['padding']['top'], $attributes['padding']['right'], $attributes['padding']['bottom'], $attributes['padding']['left'] );
}

if ( isset( $attributes['borderWidth'] ) && ! empty( $attributes['borderWidth'] ) ) {
	$css_vars['--tripzzy-icon-box-border-width'] = sprintf( '%s %s %s %s', $attributes['borderWidth']['top'], $attributes['borderWidth']['right'], $attributes['borderWidth']['bottom'], $attributes['borderWidth']['left'] );
}
if ( isset( $attributes['borderRadius'] ) && ! empty( $attributes['borderRadius'] ) ) {
	$css_vars['--tripzzy-icon-box-border-radius'] = sprintf( '%s %s %s %s', $attributes['borderRadius']['top'], $attributes['borderRadius']['right'], $attributes['borderRadius']['bottom'], $attributes['borderRadius']['left'] );
}
if ( isset( $attributes['gap'] ) && ! empty( $attributes['gap'] ) ) {
	$css_vars['--tripzzy-icon-box-gap'] = $attributes['gap'];
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
	<?php if ( $url ) : ?>
		<a class="tripzzy-icon-box-wrapper" href="<?php echo esc_url( $url ); ?>"
			<?php if ( $open_in_new_tab ) : ?>
			target="_blank"
			<?php endif; ?>
		>
	<?php else : ?>
	<div class="tripzzy-icon-box-wrapper">
	<?php endif; ?>

		<span class="tripzzy-icon ">
			<i class="<?php echo esc_attr( $icon ); ?>"></i>
			<span class="screen-reader-text"><?php echo esc_url( $url ); ?></span>
		</span>
		<?php if ( $content ) : ?>
		<span class="tripzzy-box-content"><?php echo esc_html( $content ); ?></span>
		<?php endif; ?>
	<?php if ( $url ) : ?>
	</a>
	<?php else : ?>
	</div>
	<?php endif; ?>
</div>

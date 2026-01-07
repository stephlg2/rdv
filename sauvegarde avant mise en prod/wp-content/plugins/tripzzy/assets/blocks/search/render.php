<?php
/**
 * Tripzzy Render Search Form.
 *
 * @since 1.0.0
 * @since 1.0.5 API updated to v3 and settings. Wrapper div element with class 'tripzzy-trip-search-block' added.
 * @since 1.0.9 Used CSS Vars instead of full css.
 * @since 1.1.2 Added inline style insted of style written in style tag.
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Assets;
use Tripzzy\Core\Blocks;
$block_id = $attributes['blockId'] ?? '';
$layout   = $attributes['layout'] ?? 'row';

// Container Styles.
$container_width  = $attributes['container_width'] ?? '100%';
$gap              = $attributes['gap'] ?? '0';
$background_color = Blocks::get_background_color( $attributes );
$text_color       = Blocks::get_text_color( $attributes );
$font_size        = Blocks::get_font_size( $attributes );

// Need to enqueue as inline style later.
$block_class = array(
	'tripzzy-trip-search-block',
	'tz-block-' . $block_id,
);
if ( 'column' === $layout ) {
	$block_class[] = 'column-view';
}
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $block_class ),
	)
);

$css_vars = array(
	'--tripzzy-search-font-size'               => $font_size,
	'--tripzzy-search-icon-position-top'       => 'calc( (50% - (' . $font_size . ' / 2) ) - 4px )',
	'--tripzzy-search-field-gap'               => $gap,
	'--tripzzy-search-text-color'              => $text_color ? $text_color : '#444',
	'--tripzzy-search-button-color'            => $attributes['buttonColor'] ?? '',
	'--tripzzy-search-button-color-hover'      => $attributes['buttonColorHover'] ?? '',
	'--tripzzy-search-button-background'       => $attributes['buttonBackgroundColor'] ?? 'var(--tripzzy-primary-color)',
	'--tripzzy-search-button-background-hover' => $attributes['buttonBackgroundColorHover'] ?? 'var(--tripzzy-accent-color)',
);
$styles   = array(
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
	<?php Template::get_template( 'trip-search', $attributes ); ?>
</div>

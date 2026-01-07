<?php
/**
 * Escaping Html class
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\EscapeHelper' ) ) {
	/**
	 * Escaping Html class
	 *
	 * @since 1.0.0
	 */
	class EscapeHelper {

		/**
		 * Wrapper method for WordPress wp_kses escaping function.
		 *
		 * @param string $data raw data to clean.
		 * @since 1.0.0
		 */
		public static function wp_kses( $data ) {
			$allowed_html = self::get_allowed_html();
			return wp_kses( $data, $allowed_html );
		}

		/**
		 * Get Allowed HTML Tags for escaping.
		 *
		 * @since 1.0.0
		 * @since 1.1.4 Added tc-range-slider as allowed tag.
		 * @since 1.1.8 Added li as allowed tag.
		 * @since 1.2.4 Added step attr in input tag.
		 * @since 1.2.5 Added multiple, allow-multiple in select tag and readonly in input tag and add div tag.
		 * @since 1.2.7 Added title attr in div.
		 */
		public static function get_allowed_html() {
			$allowed_html             = wp_kses_allowed_html( 'post' );
			$allowed_html['form']     = array(
				'name'   => true,
				'id'     => true,
				'class'  => true,
				'action' => true,
				'method' => true,
			);
			$allowed_html['input']    = array(
				'type'        => true,
				'name'        => true,
				'value'       => true,
				'placeholder' => true,
				'id'          => true,
				'class'       => true,
				'required'    => true,
				'data-*'      => true,
				'style'       => true,
				'checked'     => true,
				'step'        => true,
				'readonly'    => true,
			);
			$allowed_html['select']   = array(
				'name'           => true,
				'value'          => true,
				'id'             => true,
				'class'          => true,
				'required'       => true,
				'data-*'         => true,
				'style'          => true,
				'multiple'       => true,
				'allow-multiple' => true,
			);
			$allowed_html['option']   = array(
				'value'    => true,
				'selected' => true,
			);
			$allowed_html['textarea'] = array(
				'name'        => true,
				'placeholder' => true,
				'id'          => true,
				'class'       => true,
				'required'    => true,
				'data-*'      => true,
				'style'       => true,
			);
			$allowed_html['iframe']   = array(
				'width'        => true,
				'height'       => true,
				'frameborder'  => true,
				'scrolling'    => true,
				'marginheight' => true,
				'marginwidth'  => true,
				'src'          => true,
			);
			$allowed_html['br']       = array(
				'class' => true,
				'id'    => true,
			);
			// SVG.
			$allowed_html['svg']  = array(
				'data-prefix' => true,
				'class'       => true,
				'data-icon'   => true,
				'xmlns'       => true,
				'viewBox'     => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
			);
			$allowed_html['path'] = array(
				'd' => true,
			);
			$allowed_html['li']   = array(
				'rel'    => true,
				'class'  => true,
				'id'     => true,
				'data-*' => true,
				'style'  => true,
				'title'  => true,
			);
			$allowed_html['div']  = array(
				'title'  => true,
				'class'  => true,
				'id'     => true,
				'style'  => true,
				'data-*' => true,
				'min'    => true,
				'max'    => true,
				'value'  => true,
				'value1' => true,
				'value2' => true,
			);

			$allowed_html['style'] = array(); // internal style.
			return apply_filters( 'tripzzy_filter_wp_kses_allowed_html_tags', $allowed_html );
		}
	}
}

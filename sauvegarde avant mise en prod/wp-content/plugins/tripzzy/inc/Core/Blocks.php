<?php
/**
 * Tripzzy Blocks Entry.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Traits.
use Tripzzy\Core\Traits\SingletonTrait;
if ( ! class_exists( 'Blocks' ) ) {

	/**
	 * Main Class.
	 *
	 * @since 1.0.0
	 */
	final class Blocks {

		use SingletonTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			foreach ( glob( sprintf( '%1$sinc/Core/Blocks/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
				$namespace  = 'Tripzzy\Core\Blocks';
				$class_name = basename( $filename, '.php' );
				if ( class_exists( $namespace . '\\' . $class_name ) ) {
					$name = $namespace . '\\' . $class_name;
					new $name();
				}
			}
		}

		/**
		 * Return text color for block as per block attributes.
		 *
		 * @param array $attributes All block attributes.
		 *
		 * @since 1.0.5
		 * @return string
		 */
		public static function get_text_color( $attributes = array() ) {
			$color = '';
			if ( isset( $attributes['textColor'] ) ) {
				$color = 'var(--wp--preset--color--' . $attributes['textColor'] . ')';
			} elseif ( isset( $attributes['style'] ) && isset( $attributes['style']['color']['text'] ) ) {
				$color = $attributes['style']['color']['text'];
			}
			return $color;
		}

		/**
		 * Return background color for block as per block attributes.
		 *
		 * @param array $attributes All block attributes.
		 *
		 * @since 1.0.5
		 * @return string
		 */
		public static function get_background_color( $attributes = array() ) {
			$background = '';
			if ( isset( $attributes['gradient'] ) ) {
				$background = 'var(--wp--preset--gradient--' . $attributes['gradient'] . ')';
			} elseif ( isset( $attributes['backgroundColor'] ) ) {
				$background = 'var(--wp--preset--color--' . $attributes['backgroundColor'] . ')';
			} elseif ( isset( $attributes['style'] ) && isset( $attributes['style']['color']['gradient'] ) ) {
				$background = $attributes['style']['color']['gradient'];
			} elseif ( isset( $attributes['style'] ) && isset( $attributes['style']['color']['background'] ) ) {
				$background = $attributes['style']['color']['background'];
			}
			return $background;
		}

		/**
		 * Return Font size for block as per block attributes.
		 *
		 * @param array  $attributes All block attributes.
		 * @param string $default_size Default font size.
		 *
		 * @since 1.0.5
		 * @since 1.0.8 Added default Font Size Param.
		 * @return string
		 */
		public static function get_font_size( $attributes = array(), $default_size = '13px' ) {
			if ( isset( $attributes['style'] ) && isset( $attributes['style']['typography'] ) && isset( $attributes['style']['typography']['fontSize'] ) ) {
				$font_size = $attributes['style']['typography']['fontSize'] ?? $default_size;
			} else {
				$font_size = isset( $attributes['fontSize'] ) ? 'var(--wp--preset--font-size--' . $attributes['fontSize'] . ')' : $default_size;
			}
			return $font_size;
		}
	}
}

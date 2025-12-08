<?php
/**
 * Text Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Textarea' ) ) {
	/**
	 * Text Input.
	 *
	 * @since 1.0.0
	 */
	class Textarea {
		/**
		 * Field array.
		 *
		 * @var $field
		 * @since 1.0.0
		 */
		protected $field;
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'textarea';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Textarea', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Textarea', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Textarea',
				'attr'  => array(),
			);
			return $attribute;
		}

		/**
		 * Render.
		 *
		 * @param array $field   Fields arguments data.
		 * @param bool  $display Display field flag. whether return or display.
		 */
		public static function render( $field = array(), $display = true ) {
			$validations = '';
			if ( isset( $field['validations'] ) ) {
				foreach ( $field['validations'] as $key => $attr ) {
					$validations .= sprintf( ' %s="%s" data-parsley-%s="%s"', esc_attr( $key ), esc_attr( $attr ), esc_attr( $key ), esc_attr( $attr ) );
				}
			}
			$attributes = '';
			if ( isset( $field['attributes'] ) ) {
				foreach ( $field['attributes'] as $attribute => $attribute_val ) {
					$attributes .= sprintf( ' %s="%s" ', esc_attr( $attribute ), esc_attr( $attribute_val ) );
				}
			}
			$required = '';
			if ( isset( $field['required'] ) && $field['required'] ) {
				$required = 'required="required"';
			}

			$before_field = '';
			if ( isset( $field['before_field'] ) ) {
				$before_field_class = isset( $field['before_field_class'] ) ? $field['before_field_class'] : '';
				$before_field       = sprintf( '<span class="tripzzy-before-field%s">%s</span>', esc_attr( $before_field_class ), wp_kses_post( $field['before_field'] ) );
			}
			$input_style = $field['input_style'] ?? array();
			$input_style = ! empty( $input_style ) ? ArrayHelper::array_to_css( $input_style ) : '';
			$output      = sprintf( '%s<textarea placeholder="%s" type="%s" id="%s" name="%s" %s class="%s" %s %s style="%s">%s</textarea>', $before_field, esc_attr( $field['placeholder'] ), esc_attr( self::$field_type ), esc_attr( $field['id'] ), esc_attr( $field['name'] ), $validations, esc_attr( $field['class'] ), $attributes, $required, $input_style, $field['value'] );

			if ( ! $display ) {
				return $output;
			}

			echo wp_kses_post( $output );
		}
	}
}

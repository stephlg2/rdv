<?php
/**
 * Text Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\EscapeHelper;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Text' ) ) {
	/**
	 * Text Input.
	 *
	 * @since 1.0.0
	 */
	class Text {
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
		protected static $field_type = 'text';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Text', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Text', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Text',
				'attr'  => array(),
			);
			return $attribute;
		}

		/**
		 * Render.
		 *
		 * @param array $field   Field arguments.
		 * @param bool  $display Display field flag. whether return or display.
		 */
		public static function render( $field = array(), $display = true ) {
			$enabled       = isset( $field['enabled'] ) ? $field['enabled'] : true; // by default ebabled.
			$force_enabled = isset( $field['force_enabled'] ) ? $field['force_enabled'] : false; // by default disabled.
			if ( $enabled || $force_enabled ) {
				$value       = isset( $field['value'] ) ? $field['value'] : '';
				$placeholder = isset( $field['placeholder'] ) && $field['placeholder'] ? sprintf( 'placeholder="%s"', esc_attr( $field['placeholder'] ) ) : '';

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
				$additional_attr = ''; // extra attribute for different input type. like number.
				if ( isset( $field['additional_attr'] ) ) {
					foreach ( $field['additional_attr'] as $attribute => $attribute_val ) {
						$additional_attr .= sprintf( ' %s="%s" ', esc_attr( $attribute ), esc_attr( $attribute_val ) );
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
				$output      = sprintf( '%s<input %s type="%s" id="%s" name="%s" value="%s" %s class="%s" %s %s %s style="%s">', $before_field, $placeholder, esc_attr( static::$field_type ), esc_attr( $field['id'] ), esc_attr( $field['name'] ), esc_attr( $value ), $validations, esc_attr( $field['class'] ), $attributes, $additional_attr, $required, $input_style );

				if ( ! $display ) {
					return $output;
				}

				$allowed_html = EscapeHelper::get_allowed_html();
				echo wp_kses( $output, $allowed_html );
			}
		}
	}
}

<?php
/**
 * Dropdown Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Taxonomy;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\EscapeHelper;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Dropdown' ) ) {
	/**
	 * Dropdown Input.
	 *
	 * @since 1.0.0
	 */
	class Dropdown {
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'dropdown';

		/**
		 * Dropdown Options
		 *
		 * @var $options
		 * @since 1.0.0
		 */
		protected static $options = null;

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Dropdown', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Dropdown', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Dropdown',
				'attr'  => array(
					'options',
				),
			);
			return $attribute;
		}

		/**
		 * Render.
		 *
		 * @param array $field   Field arguments.
		 * @param bool  $display Display field flag. whether return or display.
		 * @since 1.0.0
		 * @since 1.2.5 Support added for multiselect dropdown.
		 */
		public static function render( $field = array(), $display = true ) {
			$enabled       = isset( $field['enabled'] ) ? $field['enabled'] : true; // by default ebabled.
			$force_enabled = isset( $field['force_enabled'] ) ? $field['force_enabled'] : false; // by default disabled.
			if ( $enabled || $force_enabled ) {
				$validations = '';
				if ( isset( $field['validations'] ) ) {
					foreach ( $field['validations'] as $key => $attr ) {
						$validations .= sprintf( ' %s="%s" data-parsley-%s="%s"', esc_attr( $key ), esc_attr( $attr ), esc_attr( $key ), esc_attr( $attr ) );
					}
				}
				$attributes = '';
				$multiple   = false;
				if ( isset( $field['attributes'] ) ) {
					foreach ( $field['attributes'] as $attribute => $attribute_val ) {
						if ( 'multiple' === $attribute_val ) {
							$multiple = true;
						}
						if ( is_numeric( $attribute ) ) {
							$attributes .= $attribute_val;
						} else {
							$attributes .= sprintf( ' %s="%s" ', esc_attr( $attribute ), esc_attr( $attribute_val ) );
						}
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

				$field_name = $field['name'];
				if ( $multiple ) {
					$field_name .= '[]';
				}
				$input_style = $field['input_style'] ?? array();
				if ( $multiple ) {
					$input_style['display'] = 'none';
				}
				$input_style = ! empty( $input_style ) ? ArrayHelper::array_to_css( $input_style ) : '';
				$placeholder = $field['placeholder'] ?? '';
				$output      = sprintf( '%s<select id="%s" name="%s" class="%s" %s %s %s style="%s" data-placeholder="%s">', $before_field, esc_attr( $field['id'] ), esc_attr( $field_name ), esc_attr( $field['class'] ), $validations, $attributes, $required, $input_style, $placeholder );

				if ( isset( $field['options'] ) ) { // Directly set option value from array.
					$options = $field['options'];
				} elseif ( isset( $field['taxonomy'] ) && ! empty( $field['taxonomy'] ) && taxonomy_exists( $field['taxonomy'] ) ) { // Taxonomy dropdown.
					$options = Taxonomy::get_dropdown_options( $field['taxonomy'] );
				} elseif ( static::$options ) { // if options are passed via class.
					$options = static::$options;
				} else { // Dynamically set values from form fields.
					$options = $field['additional_attr']['options'] ?? array();
					$options = array_map(
						function ( $option ) {
							$data  = explode( ':', $option );
							$label = count( $data ) >= 2 ? $data[1] : $data[0];
							$value = $data[0];
							return array(
								'label' => $label,
								'value' => $value,
							);
						},
						$options
					);
				}
				// Set placeholder at first line of option.
				if ( ! empty( $placeholder ) && ! $multiple ) {
					$options = wp_parse_args(
						$options,
						array(
							array(
								'label' => $placeholder,
								'value' => '',
							),
						)
					);
				}

				foreach ( $options as $option ) {
					$label = $option['label'];
					$value = $option['value'];

					$selected = $value == $field['value'] ? 'selected' : ''; // @phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$output  .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), esc_attr( $selected ), esc_html( $label ) );
				}
				$output .= sprintf( '</select>' );

				if ( ! $display ) {
					return $output;
				}
				$allowed_html = EscapeHelper::get_allowed_html();
				echo wp_kses( $output, $allowed_html );
			}
		}
	}
}

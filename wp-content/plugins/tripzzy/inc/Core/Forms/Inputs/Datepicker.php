<?php
/**
 * Datepicker Input.
 *
 * @since 1.3.4
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\EscapeHelper;
use Tripzzy\Core\Helpers\Currencies;


if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Datepicker' ) ) {
	/**
	 * Datepicker Input.
	 *
	 * @since 1.3.4
	 */
	class Datepicker {
		/**
		 * Field array.
		 *
		 * @var $field
		 * @since 1.3.4
		 */
		protected $field;
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.3.4
		 */
		protected static $field_type = 'datepicker';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.3.4
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Datepicker', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.3.4
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Datepicker', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Datepicker',
				'attr'  => array(),
			);
			return $attribute;
		}

		/**
		 * Render.
		 *
		 * @param array $field   Field arguments.
		 * @param bool  $display Display field flag. whether return or display.
		 * @since 1.3.4
		 */
		public static function render( $field = array(), $display = true ) {
			$enabled       = isset( $field['enabled'] ) ? $field['enabled'] : true; // by default ebabled.
			$force_enabled = isset( $field['force_enabled'] ) ? $field['force_enabled'] : false; // by default disabled.
			if ( $enabled || $force_enabled ) {
				$placeholder = $field['placeholder'] ?? 'Select';
				$attributes  = '';
				if ( isset( $field['attributes'] ) ) {
					foreach ( $field['attributes'] as $attribute => $attribute_val ) {
						$attributes .= sprintf( ' %s="%s" ', esc_attr( $attribute ), esc_attr( $attribute_val ) );
					}
				}

				$before_field = '';
				if ( isset( $field['before_field'] ) ) {
					$before_field_class = isset( $field['before_field_class'] ) ? $field['before_field_class'] : '';
					$before_field       = sprintf( '<span class="tripzzy-before-field %s">%s</span>', esc_attr( $before_field_class ), wp_kses_post( $field['before_field'] ) );
				}

				$css_id        = ! empty( $field['id'] ) ? $field['id'] : 'tz-datepicker-slider';
				$wrapper_class = ! empty( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
				$mode          = $field['mode'] ?? '';
				$data_mode     = '';
				if ( $mode ) {
					$data_mode = sprintf( 'data-mode="%s"', $mode );
				}

				$value        = isset( $field['value'] ) && ! empty( $field['value'] ) ? esc_attr( wp_json_encode( $field['value'] ) ) : '';
				$output       = sprintf(
					'%s<div id="%s" class="tripzzy-datepicker-wrapper %s" >
						<input type="text" %s data-value="%s" class="test tripzzy-datepicker tripzzy-input sm" placeholder="%s" %s data-name="%s" readonly>
					</div>',
					$before_field,
					$css_id,
					$wrapper_class,
					$attributes,
					$value,
					$placeholder,
					$data_mode,
					$field['name'],
				);
				$allowed_html = EscapeHelper::get_allowed_html();
				if ( ! $display ) {
					return $output;
				}
				echo wp_kses( $output, $allowed_html );
			}
		}
	}
}

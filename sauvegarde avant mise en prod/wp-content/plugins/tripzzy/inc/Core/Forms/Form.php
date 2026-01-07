<?php
/**
 * Tripzzy Render Form Class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\FormBase;
use Tripzzy\Core\Forms\Input;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\EscapeHelper;

if ( ! class_exists( 'Tripzzy\Core\Forms\Form' ) ) {
	/**
	 * Tripzzy Form Class.
	 *
	 * @since 1.0.0
	 */
	class Form extends FormBase {

		/**
		 * Get Form fields by form id.
		 *
		 * @param int $form_id Form id to get form fields.
		 * @return array
		 */
		public static function get_fields_by_id( $form_id ) {

			if ( ! $form_id ) {
				return;
			}

			$field_type   = MetaHelpers::get_post_meta( $form_id, 'field_type' ); // Required to sync ids. [option and post meta data] and also generate Settings key.
			$settings_key = sprintf( '%s_id', $field_type );
			$all_forms    = Settings::form_data();
			$fields_class = isset( $all_forms[ $settings_key ] ) ? $all_forms[ $settings_key ] : '';
			if ( ! $fields_class ) {
				return array();
			}
			$fields = call_user_func( $fields_class . '::get_fields' );
			return $fields;
		}

		/**
		 * Render HTML as per arguement passed.
		 *
		 * @param array $args Form arguments.
		 * @param bool  $create_nonce Create nonce for parent only. Do not create nonce again while render repeator fields.
		 * @since 1.0.0
		 */
		public static function render( $args = array(), $create_nonce = true ) {
			$field_types = Input::get_field_types();

			$form_id = isset( $args['form_id'] ) ? $args['form_id'] : '';
			$fields  = isset( $args['fields'] ) ? $args['fields'] : array();
			ob_start();
			if ( $create_nonce ) {
				Nonce::create_field();
			}
			if ( $form_id || $fields ) {
				if ( ! $fields ) {
					$fields = self::get_fields_by_id( $form_id );
				}
				foreach ( $fields as $field ) {
					if ( isset( $field['type'] ) && ( $field['enabled'] || $field['force_enabled'] ) ) {
						$input_class = $field_types[ $field['type'] ]['class'] ?? '';
						if ( ! $input_class ) {
							continue;
						}
						if ( 'hidden' === $field['type'] ) {
							$input_class::render( $field );
							continue;
						}

						$title = isset( $field['label'] ) && $field['label'] ? $field['label'] : '';
						if ( ! $title ) {
							$title = isset( $field['placeholder'] ) && $field['placeholder'] ? $field['placeholder'] : '';
						}

						// Conditional.
						$is_repeator_field = 'repeator' === $field['type'];
						$is_wrapper_field  = 'wrapper' === $field['type'];
						$has_before_field  = isset( $field['before_field'] ) && $field['before_field'];

						$parent_class = array( 'tripzzy-form-field' => 'tripzzy-form-field' );
						$label_class  = array( 'tripzzy-form-label' => 'tripzzy-form-label' );
						if ( $is_repeator_field ) {
							$parent_class['tripzzy-repeator']      = 'tripzzy-repeator';
							$label_class['tripzzy-repeator-label'] = 'tripzzy-repeator-label';
						}
						if ( $has_before_field ) {
							$parent_class['has-before-field'] = 'has-before-field';
						}
						if ( $is_wrapper_field ) {
							unset( $parent_class['tripzzy-form-field'] );
							$parent_class['tripzzy-form-field-wrapper'] = 'tripzzy-form-field-wrapper ' . $field['class'];
							$label_class['tripzzy-form-label-wrapper']  = 'tripzzy-form-label-wrapper';
						}

						$parent_class = implode( ' ', $parent_class );
						$label_class  = implode( ' ', $label_class );

						// Custom style wrapper div.
						$custom_styles = $field['style'] ?? array();
						$custom_styles = ! empty( $custom_styles ) ? ArrayHelper::array_to_css( $custom_styles ) : '';
						?>
						<div class="<?php echo esc_attr( $parent_class ); ?>" title="<?php echo esc_attr( $title ); ?>" style="<?php echo esc_attr( $custom_styles ); ?>" >
							<label class="<?php echo esc_attr( $label_class ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
								<?php if ( $field['required'] && 'repeator' !== $field['type'] ) : ?>
									<span class="tripzzy-required">*</span>
								<?php endif; ?>
							</label>
								<?php
								if ( isset( $field['input_wrapper'] ) && $field['input_wrapper'] ) :
									$input_wrapper_class = isset( $field['input_wrapper_class'] ) ? $field['input_wrapper_class'] : '';
									?>
								<<?php echo esc_attr( $field['input_wrapper'] ); ?> class="<?php echo esc_attr( $input_wrapper_class ); ?>">
									<?php
							endif;
								$input_class::render( $field ); // Main field render.

								if ( isset( $field['input_description'] ) && $field['input_description'] ) :
									?>
									<p class="description" ><?php echo esc_html( $field['input_description'] ); ?></p>
									<?php
								endif;

								if ( isset( $field['input_wrapper'] ) && $field['input_wrapper'] ) :
									?>
								</<?php echo esc_attr( $field['input_wrapper'] ); ?>>
									<?php
							endif;
								?>
						</div>
						<?php
					}
				}
			}
			$content = ob_get_contents();
			ob_end_clean();
			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $content, $allowed_html );
		}

		/**
		 * Helper method to add field values to repeator fields.
		 *
		 * @param array $fields Repeator Fields.
		 * @param array $values Repeator Values.
		 */
		public static function repeator_field_values( $fields, $values ) {
			foreach ( $fields as $field_index => $field ) {
				if ( 'repeator' === $field['type'] ) {
					$repeator_fields        = $field['children'];
					$repeator_values        = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : array();
					$child_with_val         = self::repeator_field_values( $repeator_fields, $repeator_values );
					$field['children']      = $child_with_val;
					$fields[ $field_index ] = $field;
					return $fields;
				} else {
					$fallback_value = isset( $field['value'] ) ? $field['value'] : '';
					$value          = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : $fallback_value;

					$field['value']         = $value;
					$fields[ $field_index ] = $field;
				}
			}
			return $fields;
		}

		/**
		 * Helper method to add field values to wrapper fields.
		 *
		 * @param array $fields Repeator Fields.
		 * @param array $values Repeator Values.
		 */
		public static function wrapper_field_values( $fields, $values ) {
			foreach ( $fields as $field_index => $field ) {
				$fallback_value         = isset( $field['value'] ) ? $field['value'] : '';
				$value                  = isset( $values[ $field['name'] ] ) ? $values[ $field['name'] ] : $fallback_value;
				$field['value']         = $value;
				$fields[ $field_index ] = $field;
			}
			return $fields;
		}
	}
}

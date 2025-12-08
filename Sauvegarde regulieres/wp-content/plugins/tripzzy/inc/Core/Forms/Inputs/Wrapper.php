<?php
/**
 * Number Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Forms\Input;

use Tripzzy\Core\Forms\Inputs\Text;
if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Wrapper' ) ) {
	/**
	 * Wrapper field.
	 *
	 * @since 1.0.0
	 */
	class Wrapper {
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'wrapper';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Wrapper', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Wrapper', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Wrapper',
				'attr'  => array(),
			);
			return $attribute;
		}

		/**
		 * Render.
		 *
		 * @param array $field   Field arguments.
		 * @since 1.0.0
		 * @since 1.2.4 Fixed Displaying label of disabled inputs.
		 */
		public static function render( $field = array() ) {
			$field_types = Input::get_field_types();

			$wrapper_fields = $field['children'];
			?>
			<div class="tripzzy-input-field-wrapper">
			<?php

			foreach ( $wrapper_fields as $wrapper_field ) {

				if ( isset( $wrapper_field['type'] ) && ( $wrapper_field['enabled'] || $wrapper_field['force_enabled'] ) ) {
					// Redundant.
					$has_before_field   = isset( $wrapper_field['before_field'] ) && $wrapper_field['before_field'];
					$before_field_class = $has_before_field ? 'has-before-field' : '';

					$title = isset( $wrapper_field['label'] ) && $wrapper_field['label'] ? $wrapper_field['label'] : '';
					if ( ! $title ) {
						$title = isset( $wrapper_field['placeholder'] ) && $wrapper_field['placeholder'] ? $wrapper_field['placeholder'] : '';
					}

					$label_class = array( 'tripzzy-form-label' => 'tripzzy-form-label' );
					$label_class = implode( ' ', $label_class );
					?>
					<div class="tripzzy-form-field  <?php echo esc_attr( $before_field_class ); ?>" title="<?php echo esc_attr( $title ); ?>" >
						<label class="<?php echo esc_attr( $label_class ); ?>">
							<?php echo esc_html( $wrapper_field['label'] ); ?>
							<?php if ( $wrapper_field['required'] && 'repeator' !== $wrapper_field['type'] ) : ?>
								<span class="tripzzy-required">*</span>
							<?php endif; ?>
						</label>
						<?php

						$field_class = $field_types[ $wrapper_field['type'] ]['class'];

						// Main Repeator Field Render [How many fields]. [just temporary. need to modify later].
						$tmp_field = $wrapper_field;
						if ( isset( $wrapper_field['value'] ) && is_array( $wrapper_field['value'] ) && count( $wrapper_field['value'] ) > 0 ) {
							foreach ( $wrapper_field['value'] as $index => $val ) {
								$tmp_field['value'] = $val;
								$field_class::render( $tmp_field );
							}
						} else {
							$field_class::render( $wrapper_field );
						}
						?>
					</div>
					<?php
				}
			}
			?>
			</div>
			<?php
		}
	}
}

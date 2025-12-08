<?php
/**
 * Repeator input field.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Forms\Input;

use Tripzzy\Core\Forms\Inputs\Text;
if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Repeator' ) ) {
	/**
	 * Repeator field.
	 *
	 * @since 1.0.0
	 */
	class Repeator {
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'repeator';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Repeator', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Repeator', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Repeator',
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

			$repeator_fields = $field['children'];
			?>
			<div class="tripzzy-repeator-wrapper">
			<?php

			foreach ( $repeator_fields as $repeator_field ) {
				if ( isset( $repeator_field['type'] ) && ( $repeator_field['enabled'] || $repeator_field['force_enabled'] ) ) {
					$field_name = $field['name'] . '[' . $repeator_field['name'] . ']';

					// Redundant.
					$has_before_field   = isset( $repeator_field['before_field'] ) && $repeator_field['before_field'];
					$before_field_class = $has_before_field ? 'has-before-field' : '';

					$repeator_class = 'repeator' === $repeator_field['type'] ? 'tripzzy-repeator' : '';

					$title = isset( $repeator_field['label'] ) && $repeator_field['label'] ? $repeator_field['label'] : '';
					if ( ! $title ) {
						$title = isset( $repeator_field['placeholder'] ) && $repeator_field['placeholder'] ? $repeator_field['placeholder'] : '';
					}

					?>
					<div class="tripzzy-form-field <?php echo esc_attr( $repeator_class ); ?> <?php echo esc_attr( $before_field_class ); ?>" title="<?php echo esc_attr( $title ); ?>" >
						<?php if ( $repeator_field['label'] ) : ?>
							<label>
								<?php echo esc_html( $repeator_field['label'] ); ?>
								<?php if ( $repeator_field['required'] && 'repeator' !== $repeator_field['type'] ) : ?>
									<span class="tripzzy-required">*</span>
								<?php endif; ?>
							</label>
						<?php endif; ?>
						<?php
						if ( 'repeator' === $repeator_field['type'] ) {
							$children = isset( $repeator_field['children'] ) && ! empty( $repeator_field['children'] ) ? $repeator_field['children'] : array();
							if ( ! empty( $children ) ) {
								self::render_repeator( $children, $field_name );
							}
						} else {
							$field_class            = $field_types[ $repeator_field['type'] ]['class'];
							$repeator_field['name'] = $field_name . '[]';

							// Main Repeator Field Render [How many fields]. [just temporary. need to modify later].
							$tmp_field = $repeator_field;
							if ( isset( $repeator_field['value'] ) && is_array( $repeator_field['value'] ) && count( $repeator_field['value'] ) > 0 ) {
								foreach ( $repeator_field['value'] as $index => $val ) {
									$tmp_field['value'] = $val;
									$field_class::render( $tmp_field );
								}
							} else {
								$field_class::render( $repeator_field );
							}
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

		/**
		 * Render repeator fields recursivly.
		 *
		 * @param array  $repeator_fields Field arguments.
		 * @param string $field_name Field name.
		 */
		public static function render_repeator( $repeator_fields = array(), $field_name = '' ) {
			$field_types = Input::get_field_types();
			?>
			<div class="tripzzy-repeator-wrapper">
			<?php
			foreach ( $repeator_fields as $repeator_field ) {

				// Redundant.
				$has_before_field   = isset( $repeator_field['before_field'] ) && $repeator_field['before_field'];
				$before_field_class = $has_before_field ? 'has-before-field' : '';

				$repeator_class = 'repeator' === $repeator_field['type'] ? 'tripzzy-repeator' : '';

				$title = isset( $repeator_field['label'] ) && $repeator_field['label'] ? $repeator_field['label'] : '';
				if ( ! $title ) {
					$title = isset( $repeator_field['placeholder'] ) && $repeator_field['placeholder'] ? $repeator_field['placeholder'] : '';
				}
				?>
				<div class="tripzzy-form-field <?php echo esc_attr( $repeator_class ); ?> <?php echo esc_attr( $before_field_class ); ?>" title="<?php echo esc_attr( $title ); ?>" >
					<?php if ( $repeator_field['label'] ) : ?>
					<label><?php echo esc_html( $repeator_field['label'] ); ?></label>
					<?php endif; ?>
					<?php
					if ( 'repeator' === $repeator_field['type'] ) {
						$field_name .= '[' . $repeator_field['name'] . ']';
						$children    = isset( $repeator_field['children'] ) && ! empty( $repeator_field['children'] ) ? $repeator_field['children'] : array();
						if ( ! empty( $children ) ) {
							self::render_repeator( $children, $field_name );
						}
					} else {
						$field_class            = $field_types[ $repeator_field['type'] ]['class'];
						$repeator_field['name'] = $field_name . '[' . $repeator_field['name'] . '][]';

						// Main Repeator Field Render [How many fields]. [just temporary. need to modify later].
						$tmp_field = $repeator_field;
						if ( isset( $repeator_field['value'] ) && is_array( $repeator_field['value'] ) && count( $repeator_field['value'] ) > 0 ) {
							foreach ( $repeator_field['value'] as $index => $val ) {
								$tmp_field['value'] = $val;
								$field_class::render( $tmp_field );
							}
						} else {
							$field_class::render( $repeator_field );
						}
					}
					?>
				</div>
				<?php
			}
			?>
			</div>
			<?php
		}
	}
}

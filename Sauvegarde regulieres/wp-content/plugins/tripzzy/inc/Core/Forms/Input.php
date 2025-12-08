<?php
/**
 * Tripzzy Inputs Calss.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'Tripzzy\Core\Forms\Input' ) ) {
	/**
	 * Tripzzy Input Wrapper Class.
	 *
	 * @since 1.0.0
	 */
	class Input {

		/**
		 * Inlude all input types.
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			// Inputs.
			foreach ( glob( sprintf( '%1$sinc/Core/Forms/Inputs/*.php', TRIPZZY_ABSPATH ) ) as $filename ) {
				$namespace  = 'Tripzzy\Core\Forms\Inputs';
				$class_name = basename( $filename, '.php' );
				if ( method_exists( $namespace . '\\' . $class_name, 'init_attribute' ) ) {
					call_user_func( $namespace . '\\' . $class_name . '::init_attribute' );
				}
			}
		}

		/**
		 * Register and get all input types data to use list in input dropdown.
		 *
		 * @since 1.0.0
		 */
		public static function get_field_types() {
			return apply_filters( 'tripzzy_filter_field_attributes', array() );
		}
	}
}

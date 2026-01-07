<?php
/**
 * Number Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Forms\Inputs\Text;
if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Tel' ) ) {
	/**
	 * Tel Input.
	 *
	 * @since 1.0.0
	 */
	class Tel extends Text {
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'tel';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Tel', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Tel', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Tel',
				'attr'  => array(
					'step',
					'min',
					'max',
				),
			);
			return $attribute;
		}
	}
}

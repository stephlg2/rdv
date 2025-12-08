<?php
/**
 * Text Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Forms\Inputs\Text;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Hidden' ) ) {
	/**
	 * Hidden Input.
	 *
	 * @since 1.0.0
	 */
	class Hidden extends Text {
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'hidden';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( __CLASS__, 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Hidden', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Hidden',
				'attr'  => array(),
			);
			return $attribute;
		}
	}
}

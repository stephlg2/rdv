<?php
/**
 * Country Dropdown Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Forms\Inputs\Dropdown;

use Tripzzy\Core\Helpers\DropdownOptions;
use Tripzzy\Core\Helpers\Countries;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\CountryDropdown' ) ) {
	/**
	 * Country Dropdown Input.
	 *
	 * @since 1.0.0
	 */
	class CountryDropdown extends Dropdown {
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'country_dropdown';


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
			self::$options = Countries::get_dropdown_options( true );
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\CountryDropdown', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Country Dropdown', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\CountryDropdown',
				'attr'  => array(),
			);
			return $attribute;
		}
	}
}
